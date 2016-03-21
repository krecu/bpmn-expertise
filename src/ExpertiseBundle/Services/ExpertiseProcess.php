<?php
namespace ExpertiseBundle\Services;

use AppBundle\Proxy\RpcRequestParams;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use ExpertiseBundle\Helper\Process;

/**
 * Class ExpertiseService
 * @package ExpertiseBundle\Services
 */
class ExpertiseProcess {

    /** @var  Container $container */
    private $container;

    /** @var Logger */
    private $logger;

    /**
     * @param $container
     * @param $logger
     */
    public function __construct($container, $logger)
    {
        $this->container = $container;
        $this->logger = $logger;
    }

    public function create($params, $processDefinitionId){
        $proxyService = $this->container->get('app.proxy');
        $processParams = new RpcRequestParams();
        $processParams->requireHeaderAuth(false)->setParams([
          'processDefinitionId' => $processDefinitionId,
          'params' => $params
        ]);
        $result = $proxyService->sendRequest('bpm.start', $processParams);
        $process = new Process();
        return $process->generateByJson($result);
    }

    public function load($id){
        $proxyService = $this->container->get('app.proxy');
        $processParams = new RpcRequestParams();
        $processParams->requireHeaderAuth(false)->setParams([
          'processId' => $id
        ]);
        $result = $proxyService->sendRequest('bpm.status', $processParams);
        $process = new Process();
        return $process->generateByJson($result);
    }

    /**
     * @param $id
     * @param array $params
     * @return \ExpertiseBundle\Helper\Process
     * @throws \Exception
     */
    public function push($id, $params = []) {
        $proxyService = $this->container->get('app.proxy');
        $processParams = new RpcRequestParams();
        $params = [
          'processId' => $id,
          'params' => $params,
        ];

        $processParams->requireHeaderAuth(false)->setParams($params);
        $result = $proxyService->sendRequest('bpm.execute', $processParams);
        $process = new Process();
        return $process->generateByJson($result);
    }
}