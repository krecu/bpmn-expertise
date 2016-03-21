<?php
namespace AppBundle\Services;

use AppBundle\Proxy\RpcRequestParams;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ProxyService
 * @package AppBundle\Services
 */
class ProxyService
{
    /** @var  Container $container */
    private $_container;

    /** @var Logger */
    private $_logger;

    /** @var Request */
    private $_originRequest = null;

    /** @var \Predis\Client */
    private $_redis;

    /** @var string */
    private $_exchange = 'eski';

    /** @var string */
    private $_token = null;

    /** @var object User object */
    private $_user = null;

    /** @var object Authorized user verify info */
    private $_authInfo = null;

    /**
     * @param Container $container
     * @param Logger $logger
     * @param \Predis\Client $redisClient
     */
    public function __construct($container, $logger, $redisClient)
    {
        $this->_container = $container;
        $this->_logger = $logger;
        $this->_redis = $redisClient;
        $this->_exchange = $container->getParameter(
          'rabbit_mq_exchange'
        );
        if ($this->_container->isScopeActive('request')) {
            $this->_originRequest = $this->_container->get('request');
        }
    }

    /**
     * Converting RPC response. Currently accepted string or JSON
     *
     * @param string $content
     * @return mixed
     */
    private function _convert($content)
    {
        if (is_string($content)) {
            try {
                $convertedContent = json_decode($content);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $convertedContent;
                }
            } catch (\Exception $e) {
                return $content;
            }
        }

        return $content;
    }

    /**
     * @param string $routeKey
     *
     * @return \OldSound\RabbitMqBundle\RabbitMq\RpcClient
     */
    public function getRpcClient($routeKey)
    {
        list($service) = explode('.', $routeKey);

        switch ($service) {
            case 'auth':
                return $this->_container->get('old_sound_rabbit_mq.auth_rpc');
            case 'bpm':
                return $this->_container->get('old_sound_rabbit_mq.bpm_rpc');
            case 'schema':
                return $this->_container->get('old_sound_rabbit_mq.schema_rpc');
            case 'reg':
                return $this->_container->get('old_sound_rabbit_mq.reg_api_rpc');
            case 'storage':
            default:
                return $this->_container->get('old_sound_rabbit_mq.storage_api_rpc');
        }
    }
    /**
     * Sending requests to RPC
     *
     * @param $routeKey
     * @param RpcRequestParams $params
     * @return mixed|Response
     * @throws \Exception
     */
    public function sendRequest($routeKey, RpcRequestParams $params = null)
    {
        $messageId = 'isz-' . $routeKey . '-' . microtime(true);

        if (!$params instanceof RpcRequestParams) {
            // Using original request params
            $params = $this->createParamsFromRequest();
        }
        // Check if this request require X-User-Id
        if ($params->isRequiredHeaderAuth()) {
            if (is_null($this->_token)) {
                $this->auth($params->getHeader('X-Access-Token'));
                if (is_object($this->_authInfo) && isset($this->_authInfo->_user_id) && !empty($this->_authInfo->_user_id)) {
                    $this->_token = $params->getHeader('X-Access-Token');
                }
            }

            if (!is_null($this->_authInfo)) {
                // Setting X-User-Id header instead of X-Access-Token
                $params->setHeader('X-User-Id', $this->_authInfo->user_id)
                    ->removeHeader('X-Access-Token');

                if (is_null($this->_user)) {
                    $this->loadUserEntity();
                }
            }
        }
        // Get RPC route by route key
        $client = $this->getRpcClient($routeKey);

        $this->_logger->addDebug("Sent request to '{$routeKey}' ($messageId) on exchange '{$this->_exchange}' with params '{" . $params->getJsonParams() . "}'");

        // Sending RPC call
        $client->addRequest(
            $params->getJsonParams(),
            $this->_exchange,
            $messageId,
            $routeKey,
            $params->getRequestTimeout());

        // Collecting results from RPC server
        try {
            $replies = $client->getReplies();

            if (!is_array($replies) || !isset($replies[$messageId])) {
                $this->_logger->addError("No replies on request to '{$routeKey}' ($messageId) on exchange '{$this->_exchange}' with params '{" . $params->getJsonParams() . "}'");
                throw new \Exception('No replies from RPC server ' . get_class($client));
            }

            $this->_logger->addDebug("Get '{$routeKey}' ($messageId) on exchange '{$this->_exchange}'");

            $responseObject = $this->_convert($replies[$messageId]);
            if (isset($responseObject->content)) {
                // If it comes from Symfony REST
                if (!empty($responseObject->code) && (($responseObject->code > 299) || ($responseObject->code < 200))) {
                    $responseObject->content = (!is_object($responseObject->content) && (!empty($responseObject->content))) ? $this->_convert($responseObject->content) : $responseObject->content;

                    if ($responseObject->content === null) {
                        $message = new \stdClass();
                        $message->code = 500;
                        $message->message = 500;
                    } else {
                        $message = json_encode($responseObject);
                    }

                    $this->_logger->addWarning("Get Error from RPC! routeKey: {$routeKey}, params: " . json_encode($params) . "; message: {$message}");
                    throw new HttpException(500, "Get Error from RPC! routeKey: {$routeKey}, params: " . json_encode($params) . "; message: {$message}");
                } else {
                    if ($params->isReturnAsResponse()) {
                        return new Response($responseObject->content, $responseObject->code, (array)$responseObject->headers);
                    } else {
                        return $this->_convert($responseObject->content);
                    }
                }
            } else {
                if (is_array($responseObject) || is_object($responseObject)) {
                    return $responseObject;
                }
                return new Response($responseObject);
            }
        } catch (\PhpAmqpLib\Exception\AMQPTimeoutException $e) {
            $this->_logger->addEmergency("RPC request timed out! Message: {$e->getMessage()} || routeKey: {$routeKey}, params: " . json_encode($params));
            throw new HttpException(500, "RPC timed out: {$routeKey}. Check logs for details.");
        }
    }

    /**
     * Check access token
     *
     * @param $token
     * @return bool|mixed
     */
    public function auth($token)
    {
        if (!$token) {
            /** @todo Exception + Log */
        }

        try {
//            /* пробуем достать данные по пользователю из кеша */
            $accessData = false;

//            if($this->_redis->isConnected()) {
//                $accessData = json_decode($this->_redis->get('auth-' . $token), true);
//            }

            /* если кеш пустой, то инициализируем и записываем в кеш данные по пользователю */
            if (!$accessData || empty($accessData)) {

                $params = new RpcRequestParams();
                $params->setParams([
                    "access_token" => $token
                ]);
                $params->requireHeaderAuth(false);

                $accessData = $this->sendRequest('auth.verify', $params);

                /* если авторизация удалась */
                if (isset($accessData->status) && $accessData->status == "success") {
                    $this->token = $token;
                    $accessData->expired_at = time() + $accessData->expires_in;

//                    if($this->_redis->isConnected()) {
//                        $this->_redis->set('auth-' . $token, json_encode($accessData));
//                    }

                    if (is_object($accessData) && isset($accessData->user_id) && !empty($accessData->user_id)) {
                        $this->_authInfo = $accessData;
                    }
                } else {
                    $this->_logger->addError("AppListener: auth verify error, with token={$token} get status={$accessData->status}");
                }
            }

        } catch (\Exception $e) {
            $this->_logger->addError("AppListener: " . $e->getMessage());
        }

        return $this;
    }

    /**
     * @return RpcRequestParams
     */
    public function createParamsFromRequest()
    {
        return new RpcRequestParams($this->_originRequest);
    }


    /**
     * Load user  from storage
     *
     * @return object
     */
    public function loadUserEntity()
    {
        $params = $this->createParamsFromRequest()
            ->setUrl("/api/users/{$this->_authInfo->user_id}")
            ->setMethod('GET');

        $this->_user = 'loading';

        $userData = $this->sendRequest('storage.api.query', $params);

        $data = $this->_convert($userData->getContent());

        if (is_object($data)) {
            $this->_user = $data;
        }

//        $this->logger->addError("AppListener: status={$response->getStatusCode()}");
//        throw new NotFoundHttpException('AppListener: status={$response->getStatusCode()}');
    }

    /**
     * Get full authorized info
     *
     * @return object
     */
    public function getAuthInfo()
    {
        $result = $this->_authInfo;
        $result->user_info = $this->_user;

        return $result;
    }
}