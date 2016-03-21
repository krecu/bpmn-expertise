<?php
namespace ExpertiseBundle\Consumer;

use Monolog\Logger;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class ExpertiseApiConsumer implements ConsumerInterface
{

    /** @var Container $container */
    private $container;

    /** @var Logger $logger */
    private $logger;


    /**
     * Implement construct
     * @param $container
     * @param $logger
     */
    public function __construct($container, $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     *
     * expertise.api.{METOD NAME}
     *
     * Execute op by routing_key
     * @param \PhpAmqpLib\Message\AMQPMessage $msg
     * @return array|bool
     */
    public function execute(AMQPMessage $msg)
    {
        $routingKey = $msg->get('routing_key');
        list($namespace, , $action) = explode(".", $routingKey);
        if ($namespace != "expertise") {
            return false;
        }
        /** @var \ExpertiseBundle\Services\ExpertiseService $expertiseService */
        $expertiseService = $this->container->get('app.expertise');

        $queryParams = json_decode($msg->body, 1);
        $params = ['stepComplete' => 0];

        switch ($action) {

            /** Get groped entity by type and status */
            case 'groupByExpertiseStatus' :
                $this->logger->addInfo("Expertise: groupByExpertiseStatus");

                return $expertiseService->groupByExpertiseStatus($queryParams['entityType'], $queryParams['groupId']);
                break;

            /** Push children user expertise */
            case 'pushChildrenUserExpertise' :
                $this->logger->addInfo("Expertise: pushChildrenUserExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->pushChildrenUserExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: pushChildrenUserExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Push user parent expertise */
            case 'pushParentUserExpertise' :
                $this->logger->addInfo("Expertise: pushParentUserExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->pushParentUserExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: pushParentUserExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Get children internal expertise status */
            case 'statusChildrenUserExpertise' :
                $this->logger->addInfo("Expertise: statusChildrenUserExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->statusChildrenUserExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: statusChildrenUserExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Create children expertise */
            case 'createChildrenUserExpertise' :
                $this->logger->addInfo("Expertise: createChildrenUserExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->createChildrenUserExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: createChildrenUserExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Get children internal expertise status */
            case 'startChildrenUserExpertise' :
                $this->logger->addInfo("Expertise: startChildrenUserExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->startChildrenUserExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: startChildrenUserExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Push expertise */
            case 'pushExpertise' :
                $this->logger->addInfo("Expertise: pushExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->pushExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: pushExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Save field expertise */
            case 'saveFieldExpertise' :
                $this->logger->addInfo("Expertise: saveFieldExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->saveFieldExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: saveFieldExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Save user expertise */
            case 'saveUserExpertise' :
                $this->logger->addInfo("Expertise: saveUserExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->saveUserExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: saveUserExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            /** Get expertise status */
            case 'getStatusExpertise' :
                $this->logger->addInfo("Expertise: getStatusExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->getStatusExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: getStatusExpertise error:" . json_encode($e->getMessage()));
                }

                break;

            /** Get expertise status */
            case 'startExpertise' :
                $this->logger->addInfo("Expertise: startExpertise with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->startExpertise($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: startExpertise error:" . json_encode($e->getMessage()));
                }
                break;

            case 'getStatusExpertises' :
                $this->logger->addInfo("Expertise: getStatusExpertises with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->getStatusExpertises($queryParams);
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: getStatusExpertises error:" . json_encode($e->getMessage()));
                }
                break;

            /** Get expertise status */
            case 'query' :
                $this->logger->addInfo("Expertise: query with params:" . json_encode($queryParams));
                try {
                    $params = $expertiseService->storageQuery($queryParams);
                    return $params;
                } catch (Exception $e) {
                    $this->logger->addInfo("Expertise: query error:" . json_encode($e->getMessage()));
                }
                break;
        }

        return ['status' => 'success', 'data' => $params];

    }

}