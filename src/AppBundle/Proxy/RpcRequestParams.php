<?php
namespace AppBundle\Proxy;

use Symfony\Component\HttpFoundation\Request;

/**
 * Class RpcRequestParams
 * @package AppBundle\Proxy
 */
class RpcRequestParams
{
    /**
     * @var array
     */
    private $_params = [
        'request' => [],
        'content' => [],
        'query' => [],
        'server' => [],
        'files' => [],
        'cookies' => [],
        'headers' => []
    ];

    /**
     * @var bool
     */
    private $_requireHeaderAuth = true;
    /**
     * @var bool
     */
    private $_returnAsResponse = true;

    /**
     * @var int Timeout in seconds
     */
    private $_requestTimeout = 15;

    /**
     * @return int
     */
    public function getRequestTimeout()
    {
        return $this->_requestTimeout;
    }

    /**
     * @param int $requestTimeout
     */
    public function setRequestTimeout($requestTimeout)
    {
        $this->_requestTimeout = $requestTimeout;
    }

    /**
     * @param Request $request
     */
    public function __construct(Request $request = null)
    {
        if ($request !== null && $request instanceof Request) {
            $this->setParamsValue('request', $request->request->all());
            $this->setParamsValue('content', $request->getContent());
            $this->setParamsValue('query', $request->query->all());
            $this->setParamsValue('server', $request->server->all());
            $this->setParamsValue('files', $request->files->all());
            $this->setParamsValue('cookies', $request->cookies->all());
            $this->setParamsValue('headers', $request->headers->all());
        }
    }

    /**
     * @param array $params
     * @return RpcRequestParams $this
     */
    public function setParams(array $params)
    {
        if (is_array($params) && !empty($params)) {
            unset($this->_params['request']);
            unset($this->_params['content']);
            unset($this->_params['query']);
            unset($this->_params['server']);
            unset($this->_params['files']);
            unset($this->_params['cookies']);
            unset($this->_params['headers']);

            foreach ($params as $key => $value) {
                $this->setParamsValue($key, $value);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getParams() {
        return $this->_params;
    }

    /**
     * @return string
     */
    public function getJsonParams() {
        return json_encode($this->getParams());
    }

    /**
     * @param $key
     * @param $value
     * @return RpcRequestParams $this
     */
    public function setParamsValue($key, $value)
    {
        if (is_string($key)) {
            $this->_params[$key] = $value;
        }

        return $this;
    }

    /**
     * @param $method
     * @return RpcRequestParams $this
     */
    public function setMethod($method)
    {
        if (is_string($method)) {
            $this->_params['server']["REQUEST_METHOD"] = $method;
        }

        return $this;
    }

    /**
     * @param $url
     * @return RpcRequestParams $this
     */
    public function setUrl($url)
    {
        if (is_string($url)) {
            $this->_params['server']["REQUEST_URI"] = $url;
            $this->_params['server']["SCRIPT_NAME"] = $url;
            $this->_params['server']["PHP_SELF"] = $url;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function getUrl()
    {
        if (isset($this->_params['server']["REQUEST_URI"])) {
            return $this->_params['server']["REQUEST_URI"];
        }

        return false;
    }

    /**
     * @param $headerName
     * @param $headerValue
     * @return RpcRequestParams $this
     */
    public function setHeader($headerName, $headerValue) {
        if(is_string($headerName) && strlen($headerName) > 0) {
            $this->_params['headers'][$headerName] = $headerValue;
        }

        return $this;
    }

    /**
     * @param $headerName
     * @return bool
     */
    public function getHeader($headerName) {
        if(is_string($headerName) && strlen($headerName) > 0) {
            if(!isset($this->_params['headers'][$headerName])) {
                $headerName = strtolower($headerName);
                if (!isset($this->_params['headers'][$headerName])) {
                    return false;
                }
            }
            if (is_array($this->_params['headers'][$headerName]) && !empty($this->_params['headers'][$headerName])) {
                return $this->_params['headers'][$headerName][0];
            }

            return $this->_params['headers'][$headerName];
        }

        return false;
    }

    /**
     * @param $headerName
     * @return RpcRequestParams $this
     */
    public function removeHeader($headerName) {
        if(is_string($headerName) && strlen($headerName) > 0) {
            unset($this->_params['headers'][$headerName]);
        }

        return $this;
    }

    /**
     * @param bool $value
     * @return RpcRequestParams $this
     */
    public function requireHeaderAuth($value = true) {
        $this->_requireHeaderAuth = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isRequiredHeaderAuth() {
        return $this->_requireHeaderAuth;
    }


    /**
     * @return boolean
     */
    public function isReturnAsResponse()
    {
        return $this->_returnAsResponse;
    }

    /**
     * @param boolean $returnAsResponse
     * @return RpcRequestParams $this
     */
    public function setReturnAsResponse($returnAsResponse = true)
    {
        $this->_returnAsResponse = $returnAsResponse;

        return $this;
    }
}