<?php
namespace ExpertiseBundle\Services;

use ExpertiseBundle\ExpertiseBundle;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use ExpertiseBundle\Tree\ExpertiseGroup;
use ExpertiseBundle\Tree\ExpertiseUser;
use ExpertiseBundle\Entity\ExpertiseUser as userExpertise;
use ExpertiseBundle\Entity\ExpertiseGroup as groupExpertise;

use ExpertiseBundle\Entity\ExpertiseField as userComment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Validator\Constraints\Date;

/**
 * Class ExpertiseService
 * @package AppBundle\Services
 */
class ExpertiseService
{

    /** @var  Container $container */
    private $container;

    /** @var Logger */
    private $logger;

    /** @var \AppKernel */
    private $kernel;

    /**
     * @param $container
     * @param $logger
     * @param $kernel
     */
    public function __construct($container, $logger, $kernel)
    {
        $this->container = $container;
        $this->logger = $logger;
        $this->kernel = $kernel;
    }

    public function storageQuery($queryParams)
    {
        $body = $queryParams;
        $request = new Request();

        $request->initialize(
            $body["query"],
            $body["request"],
            [],
            [],
            $body["files"],
            $body["server"],
            $body['content']
        );

        $headerBag = new HeaderBag();
        $headerBag->add($body["headers"]);
        $request->headers = $headerBag;

        try {

            $event = new GetResponseEvent($this->kernel, $request, 1);
            $this->container->get('event_dispatcher')->dispatch(KernelEvents::REQUEST, $event);

            /** @var \Symfony\Component\HttpFoundation\Response $response */
            $response = $this->kernel->handle($request);

            $responseArray = [
                'request' => [
                    'query' => $request->query->all(),
                    'request' => $request->request->all(),
                    'attributes' => $request->attributes->all(),
                    'cookies' => $request->cookies->all(),
                    'files' => $request->files->all(),
                    'server' => $request->server->all(),
                    'content' => $request->getContent(),
                ],
                'headers' => $response->headers->all(),
                'code' => $response->getStatusCode(),
                'content' => $response->getContent(),
            ];
            return json_encode($responseArray);

        } catch (\Exception $e) {
            $responseArray = [
                'request' => [
                    'query' => $request->query->all(),
                    'request' => $request->request->all(),
                    'attributes' => $request->attributes->all(),
                    'cookies' => $request->cookies->all(),
                    'files' => $request->files->all(),
                    'server' => $request->server->all(),
                    'content' => $request->getContent(),
                ],
                'headers' => [],
                'code' => 404,
                'content' => ['code' => 404, 'message' => $e->getMessage()]
            ];
            return json_encode($responseArray);
        }

        return null;
    }

    public function groupByExpertiseStatus($entityType, $groupId)
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $groups = $em->getRepository('ExpertiseBundle:ExpertiseGroup')
            ->findBy([
                'entityType' => $entityType,
                'groupId' => $groupId
            ]);

        $result = [];

        foreach ($groups as $group) {
            $result[] = [
                'id' => $group->getId(),
                'groupId' => $group->getId(),
                'entityId' => $group->getEntityId(),
                'entityType' => $group->getEntityType(),
                'status' => [
                    'id' => $group->getStatus()
                        ->getId(),
                    'title' => $group->getStatus()
                        ->getTitle(),
                    'machineName' => $group->getStatus()
                        ->getMachineName(),
                ],
            ];
        }

        return $result;
    }


    /**
     * Create global tree group and users
     * @param $groupId
     * @param $entityId
     * @param $entityType
     * @param null $groupData
     * @return \ExpertiseBundle\Tree\ExpertiseGroup
     * @throws \Exception
     */
    public function getGroupExpertiseTree(
        $groupId,
        $entityId,
        $entityType,
        $groupData = null
    )
    {

        $this->logger->addInfo("Expertise: getGroupExpertiseTree by param:" . json_encode(['groupId' => $groupId, 'entityId' => $entityId, 'entityType' => $entityType]));
        /**
         * if groupData not init then grab him
         */

        if (empty($groupData)) {
            // @todo почему-то закомменченный редис
            $redis = $this->container->get('snc_redis.default');
            $key = md5($groupId)."new";
            $groupData = $redis->get($key);
            if (empty($groupData)) {
                $datasourceExpertiseGroup = $this->container->getParameter('expertise.datasorce.groups');
                $groupsRoutingKey = $this->container->getParameter('expertise.datasorce.groupsRoutingKey');
                $proxyService = $this->container->get('app.proxy');

                /* @todo hardcoded userId!!! */
                $params = $proxyService->createParamsFromRequest()
                    ->setReturnAsResponse(false)
                    ->setMethod("GET")
                    ->setUrl($datasourceExpertiseGroup . '/' . $groupId)
                    ->requireHeaderAuth(false)
                    ->setHeader('x-user-id', '1d03f765-fdaa-4b64-9f88-a772eba12e51');
                $groupData = $proxyService->sendRequest($groupsRoutingKey, $params);
                $redis->set($key, json_encode($groupData));
                $redis->expire($key, 100);
            } else {
                $groupData = json_decode($groupData);
            }
        }

        /**
         * If we not get tree data then exit;
         */
        if (empty($groupData)) {
            $this->logger->addInfo("Expertise: getGroupExpertiseTree error by param:" . json_encode(['groupId' => $groupId, 'entityId' => $entityId, 'entityType' => $entityType]));
            /* @todo Not return, need exception */
            return null;
        }

        /**
         * Create first lief
         */
        $expertiseNode = new ExpertiseGroup($groupId, $entityId, $entityType, $this->container, $this->logger);

        /**
         * If we have user data then create tree for him
         */
        $userGroupsData = [];
        if (!empty($groupData->usersExpertGroups)) {
            $this->logger->addInfo("Expertise: getGroupExpertiseTree create usersExpertGroups by param:" . json_encode($groupData->usersExpertGroups));
            $userGroups = $groupData->usersExpertGroups;
            foreach ($userGroups as $userGroup) {
                $userDataRole = $userGroup->expertRole;
                $userDataId = $userGroup->users->id;
                $userGroupsData[] = [
                    'role' => $userDataRole,
                    'userId' => $userDataId
                ];
            }
            $this->logger->addInfo("Expertise: getGroupExpertiseTree create usersExpertGroups complete:" . json_encode($userGroupsData));
        }

        /**
         * If we have children group then recurse create lief for him
         */
        if (!empty($groupData->children)) {
            $expertiseNode->setGroupType('parent');
            $userTree = $this->getUserExpertiseTree($userGroupsData, $expertiseNode);

            $expertiseNode->setUserTree($userTree);
            foreach ($groupData->children as $childGroup) {
                $childNode = $this->getGroupExpertiseTree($childGroup->id, $entityId, $entityType, $childGroup);
                $expertiseNode->addChild($childNode);
            }
        } else {
            $expertiseNode->setGroupType('child');
            $userTree = $this->getUserExpertiseTree($userGroupsData, $expertiseNode);
            $expertiseNode->setUserTree($userTree);
        }

        $this->logger->addInfo("Expertise: getGroupExpertiseTree complete by param:" . json_encode(['groupId' => $groupId, 'entityId' => $entityId, 'entityType' => $entityType]));
        return $expertiseNode;
    }

    /**
     * @param array $userGroups
     * @param \ExpertiseBundle\Tree\ExpertiseGroup $groupExpertise
     * @return \ExpertiseBundle\Tree\ExpertiseUser|null
     */
    public function getUserExpertiseTree($userGroups = [], $groupExpertise)
    {
        $this->logger->addInfo("Expertise: getUserExpertiseTree generate");

        /* @todo Захардкоженные веса */
        $weight = [
            'autor' => 0,
            'director_dz' => -1,
            'coordinator' => -2,
            'head' => 1,
            'chief' => 1,
            'curator' => 2,
            'leading_expert' => 3,
            'head_expert' => 3,
            'expert' => 4,
            // Для департамента заказчика
            'Specialist_depzak' => 0,
            'Rukovoditel_depzak' => -1,
            //
            'Rukovoditel_kurator' => 1,
            'Kurator_Jekspertnaja_Gruppa' => 2,
            'Vedushhij_Jespert_Jekspertnaja_Gruppa' => 3,
            'Jespert_Jekspertnaja_Gruppa' => 4,
            'Rukovoditel_koordinator' => 1,
        ];

        $userTree = null;
        $leaf = 0;
        while (!empty($userGroups)) {

            $bestPepper = null;

            $index = -1;
            foreach ($userGroups as $key => $userGroup) {
                if (empty($bestPepper)) {
                    $bestPepper = $userGroup;
                    $index = $key;
                }
                if ($weight[$bestPepper['role']] > $weight[$userGroup['role']]) {
                    $bestPepper = $userGroup;
                    $leaf++;
                    $index = $key;
                }
            }

            unset($userGroups[$index]);
            if (empty($userTree)) {
                $userTree = new ExpertiseUser($groupExpertise->getEntityId(), $bestPepper['userId'], $bestPepper['role'], "parent", $groupExpertise, $this->container, $this->logger);
            } else {
                $childUserTree = new ExpertiseUser($groupExpertise->getEntityId(), $bestPepper['userId'], $bestPepper['role'], "child", $groupExpertise, $this->container, $this->logger);
                $userTree->addChild($childUserTree);
            }

        }
        $this->logger->addInfo("Expertise: getUserExpertiseTree complete");
        return $userTree;
    }

    /**
     * Global start expertise
     * @param $params
     * @return int
     */
    public function startExpertise($params)
    {

        $groupId = $params['groupId'];
        $entityId = $params['entityId'];
        $entityType = $params['entityType'];

        if (!empty($groupId) && !empty($entityId) && !empty($entityType)) {

            /**
             * Get tree Expertise group with expertise users
             */
            $groupExpertiseTree = $this->getGroupExpertiseTree($groupId, $entityId, $entityType);


            /**
             * convert to simple inline array list
             */
            $groupExpertiseList = $groupExpertiseTree->getList();

            foreach ($groupExpertiseList as $groupExpertise) {

                /**
                 * Try create default process and expertise
                 * If on construct class we defined this elements,
                 * then function no need
                 */
                $groupExpertise->createProcess();

                $groupExpertise->createExpertise();

                /**
                 * If user list created
                 * try create expertise and process
                 */
                $usersExpertiseTree = $groupExpertise->getUsersTree();


                if (!empty($usersExpertiseTree)) {
                    $usersExpertiseList = $usersExpertiseTree->getList();
                    foreach ($usersExpertiseList as $usersExpertise) {
                        $usersExpertise->createProcess();
                        $usersExpertise->createExpertise();
                    }
                }

            }

            $entityGroup = $groupExpertiseTree->getEntityGroup();

            return [
                'expertiseId' => $entityGroup->getEntityId(),
                'status' => 'created',
                'stepComplete' => 1
            ];
        }

        return [
            'stepComplete' => 0
        ];
    }

    public function getExpertiseFieldsSchema()
    {
        $datasorceExpertiseGroup = $this->container->getParameter('expertise.datasorce.shemaRoutingKey');
        if ($datasorceExpertiseGroup == "none") {
            return [
                'root' => 'Экспертируемый обьект'
            ];
        }
        $proxyService = $this->container->get('app.proxy');
        $params = $proxyService->createParamsFromRequest()
            ->setReturnAsResponse(false)
            ->setUrl("get")
            ->requireHeaderAuth(false)
            ->setHeader('x-user-id', 'admin'); // @todo хардкод
        $shemaData = $proxyService->sendRequest($datasorceExpertiseGroup, $params);

        return $shemaData;
    }


    /**
     * get expertises status
     * @param $params
     * @return array
     */
    public function getStatusExpertises($params)
    {
        $entitiesId = $params['entitiesId'];
        unset($params['entitiesId']);
        $statuses = [];
        foreach ($entitiesId as $entityId) {
            $params['entityId'] = $entityId;
            $statuses[$entityId] = $this->getStatusExpertise($params);
        }

        return $statuses;
    }

    /**
     * Global start expertise
     * @param $params
     * @return array
     */
    public function getStatusExpertise($params)
    {
        $groupId = $params['groupId'];
        $entityId = $params['entityId'];
        $userId = $params['userId'];
        $entityType = $params['entityType'];
        $userRole = $params['userRole'];

        /* @todo Вообще никак не обрабатываются исключения, если if не проходит - ошибка непонятна */
        if (!empty($groupId) && !empty($entityId) && !empty($userId) && !empty($entityType) && !empty($userRole)) {
            $groupExpertiseTree = $this->getGroupExpertiseTree($groupId, $entityId, $entityType);

            if (!empty($groupExpertiseTree)) {
                $userExpertise = $groupExpertiseTree->getUserExpertise($userId, $userRole);
                if (!empty($userExpertise)) {
                    $process = $userExpertise->getProcess();
                    if (!empty($process)) {
                        $processParams = $process->getParams();
                        $processControls = $process->getControls($userExpertise->getEntityUser()
                            ->getId());
                        $commentsControls = [];

                        /**
                         * Если необходимо отобразить коменты то формирем их
                         */
                        if (isset($processParams->comments)) {
                            $historyExpertisesValue = [];
                            $historyExpertises = $userExpertise->getGroupExpertise()
                                ->getEntityGroup()
                                ->getExpertiseUsers();
                            foreach ($historyExpertises as $historyExpertise) {
                                $expertiseFields = $historyExpertise->getExpertiseFields();
                                if (!empty($expertiseFields)) {
                                    foreach ($expertiseFields as $expertiseField) {
                                        $historyExpertisesValue[$expertiseField->getFieldId()][] = [
                                            'userId' => $historyExpertise->getUserId(),
                                            'commentValue' => $expertiseField->getValue(),
                                            'commentBody' => $expertiseField->getComment(),
                                            "commentDate" => $expertiseField->getTimeCreated()
                                        ];
                                    }
                                }
                            }
                            $commentsFieldsSchema = $this->getExpertiseFieldsSchema();

                            foreach ($commentsFieldsSchema as $fieldName => $fieldLabel) {
                                $commentsControls[] = [
                                    "title" => "Оставить комментарий", // @todo хардкод
                                    "value" => "",
                                    "type" => "button",
                                    "name" => "op_save_comment",
                                    "params" => [
                                        "op" => "saveFieldExpertise",
                                        "id" => $userExpertise->getEntityUser()
                                            ->getId(),
                                        "commentField" => $fieldName,
                                        "commentValue" => "",
                                        "commentBody" => "",
                                        "commentName" => $fieldLabel,
                                    ],
                                    "history" => (!empty($historyExpertisesValue[$fieldName]) ? $historyExpertisesValue[$fieldName] : [])
                                ];
                            }
                        }

                        /**
                         * Если необходимо отобразить контрол делигирования то формируем его
                         */
                        if (isset($processParams->delegateUserExpertise)) {
                            $children = $userExpertise->getChildren();
                            $selectChildrenControl = [];
                            foreach ($children as $child) {
                                $selectChildrenControl[] = [
                                    'id' => $child->getEntityUser()
                                        ->getId(),
                                    'userId' => $child->getEntityUser()
                                        ->getUserId(),
                                ];
                            }
                            $processControls[] = (object)[
                                'type' => 'select',
                                'name' => 'delegateUserExpertise',
                                'title' => 'Делегировать', // @todo хардкод
                                'params' => (object)[
								  'op' => 'delegateUserExpertise',
								  'id' => $userExpertise->getEntityUser()->getId(),
								],
                                'values' => $selectChildrenControl,
                            ];
                        }

                        return [
                            'expertiseStatus' => $groupExpertiseTree->getStatus(),
                            'expertiseId' => $userExpertise->getEntityUser()->getId(),
                            'entityId' => $groupExpertiseTree->getEntityId(),
                            'controls' => $processControls,
                            'comments' => $commentsControls,
                            'taskWrapp' => $process->getTask(),
                            'processId' => $process->getId(),
                            'stepComplete' => 1
                        ];
                    }
                }
            }
        }

        return [
            'expertiseStatus' => 'notSet',
            'entityId' => $params['entityId'],
            'stepComplete' => 0
        ];
    }

    public function pushExpertise($params)
    {
        /**
         * Try load expertiseUser Entity
         */
        $em = $this->container->get('doctrine.orm.entity_manager');
		$em->flush();
		$em->clear();
        $expertiseUser = $em->getRepository('ExpertiseBundle:ExpertiseUser')
            ->findOneById($params['id']);

        if ($expertiseUser) {
            $groupId = $expertiseUser->getExpertiseGroup()
                ->getGroupId();
            $entityId = $expertiseUser->getExpertiseGroup()
                ->getEntityId();
            $entityType = $expertiseUser->getExpertiseGroup()
                ->getEntityType();
            $userId = $expertiseUser->getUserId();
            $userRole = $expertiseUser->getRoleName();

            if (!empty($groupId) && !empty($entityId) && !empty($entityType)) {
                $groupExpertiseTree = $this->getGroupExpertiseTree($groupId, $entityId, $entityType);
                $userExpertise = $groupExpertiseTree->getUserExpertise($userId, $userRole);
                $process = $userExpertise->pushProcess($params);

                $processParams = $process->getParams();
                $processControls = $process->getControls($userExpertise->getEntityUser()
                    ->getId());
                $commentsControls = [];
                if (isset($processParams->comments)) {
                    $historyExpertisesValue = [];

                    $historyExpertises = $userExpertise->getGroupExpertise()
                        ->getEntityGroup()
                        ->getExpertiseUsers();
                    foreach ($historyExpertises as $historyExpertise) {
                        $expertiseFields = $historyExpertise->getExpertiseFields();
                        if (!empty($expertiseFields)) {
                            foreach ($expertiseFields as $expertiseField) {
                                $historyExpertisesValue[$expertiseField->getFieldId()][] = [
                                    'userId' => $historyExpertise->getUserId(),
                                    'commentValue' => $expertiseField->getValue(),
                                    'commentBody' => $expertiseField->getComment(),
                                    "commentDate" => $expertiseField->getTimeCreated()
                                ];
                            }
                        }
                    }
                    $commentsFieldsSchema = $this->getExpertiseFieldsSchema();

                    foreach ($commentsFieldsSchema as $fieldName => $fieldLabel) {
                        $commentsControls[] = [
                            "title" => "Оставить комментарий", // @todo хардкод
                            "value" => "",
                            "type" => "button",
                            "name" => "op_save_comment",
                            "params" => [
                                "op" => "saveFieldExpertise",
                                "id" => $userExpertise->getEntityUser()
                                    ->getId(),
                                "commentField" => $fieldName,
                                "commentValue" => "",
                                "commentBody" => "",
                                "commentName" => $fieldLabel,
                            ],
                            "history" => (!empty($historyExpertisesValue[$fieldName]) ? $historyExpertisesValue[$fieldName] : [])
                        ];
                    }
                }

                if ($userExpertise->getChildren()) {
                    $children = $userExpertise->getChildren();
                    $selectChildrenControl = [];
                    foreach ($children as $child) {
                        $selectChildrenControl[] = [
                            'expertiseId' => $child->getEntityUser()
                                ->getId(),
                            'userId' => $child->getEntityUser()
                                ->getUserId(),
                        ];
                    }
                    $processControls[] = (object)[
                        'type' => 'select',
                        'title' => 'Выберите экспертирующих', // @todo хардкод
                        'params' => (object)['op' => 'createChildrenExpertise'],
                        'values' => $selectChildrenControl,
                    ];
                }

                return [
                    'expertiseStatus' => $process->getStatus(),
                    'expertiseId' => $userExpertise->getEntityUser()->getId(),
                    'controls' => $processControls,
                    'comments' => $commentsControls,
                    'processId' => $process->getId(),
                    'stepComplete' => 1
                ];
            }
        }

        return [
            'stepComplete' => 0
        ];
    }

    public function pushParentUserExpertise($params)
    {
        /**
         * Try load expertiseUser Entity
         */

        $em = $this->container->get('doctrine.orm.entity_manager');
        $em->flush();
        $em->clear();
        $expertiseUser = $em->getRepository('ExpertiseBundle:ExpertiseUser')
            ->findOneById($params['id']);
        if ($expertiseUser) {
            $groupId = $expertiseUser->getExpertiseGroup()
                ->getGroupId();
            $entityId = $expertiseUser->getExpertiseGroup()
                ->getEntityId();
            $entityType = $expertiseUser->getExpertiseGroup()
                ->getEntityType();
            $userId = $expertiseUser->getUserId();
            $userRole = $expertiseUser->getRoleName();
            if (!empty($groupId) && !empty($entityId) && !empty($entityType)) {

                $groupExpertiseTree = $this->getGroupExpertiseTree($groupId, $entityId, $entityType);
                $userExpertise = $groupExpertiseTree->getUserExpertise($userId, $userRole);
                /** @var \ExpertiseBundle\Tree\ExpertiseUser $parent */
                $parent = $userExpertise->getParent();
                if (!empty($parent)) {
                    $process = $parent->pushProcess();
                    if (!empty($process)) {
                        return [
                            'stepComplete' => 1
                        ];
                    }
                }
            }
        }

        return [
            'stepComplete' => 0
        ];
    }

    public function pushChildrenUserExpertise($params)
    {
        /**
         * Try load expertiseUser Entity
         */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $em->flush();
        $em->clear();
        $expertiseUser = $em->getRepository('ExpertiseBundle:ExpertiseUser')
            ->findOneById($params['id']);
        if ($expertiseUser) {
            $groupId = $expertiseUser->getExpertiseGroup()
                ->getGroupId();
            $entityId = $expertiseUser->getExpertiseGroup()
                ->getEntityId();
            $entityType = $expertiseUser->getExpertiseGroup()
                ->getEntityType();
            $userId = $expertiseUser->getUserId();
            $userRole = $expertiseUser->getRoleName();

            if (!empty($groupId) && !empty($entityId) && !empty($entityType)) {
                $groupExpertiseTree = $this->getGroupExpertiseTree($groupId, $entityId, $entityType);
                $userExpertise = $groupExpertiseTree->getUserExpertise($userId, $userRole);
                $expertiseStatus = $userExpertise->getStatus();
                /** @var \ExpertiseBundle\Tree\ExpertiseUser $parent */
                $children = $userExpertise->getChildren();
                if (!empty($children)) {
                    foreach ($children as $child) {
                        $process = $child->pushProcess(['expertiseStatus' => $expertiseStatus]);
                    }
                    if (!empty($process)) {
                        return [
                            'stepComplete' => 1
                        ];
                    }
                }
            }
        }

        return [
            'stepComplete' => 0
        ];
    }

    /**
     * Get children expertise status & state
     * @param $params
     * @return array
     */
    public function statusChildrenUserExpertise($params)
    {
        $groupId = $params['groupId'];
        $entityId = $params['entityId'];
        $entityType = $params['entityType'];
        $userId = $params['userId'];
        $userRole = $params['userRole'];
        if (!empty($groupId) && !empty($entityId) && !empty($entityType)) {
            $groupExpertiseTree = $this->getGroupExpertiseTree($groupId, $entityId, $entityType);
            $userExpertise = $groupExpertiseTree->getUserExpertise($userId, $userRole);
            try {
                /** @var \ExpertiseBundle\Tree\ExpertiseUser[] $childrenUserExpertise */
                $childrenUserExpertise = $userExpertise->getChildren();
                if (!empty($childrenUserExpertise)) {

                    $processStatus = $expertiseStatus = "";

                    $status = [];
                    $state = [];
                    foreach ($childrenUserExpertise as $childUserExpertise) {
                        $process = $childUserExpertise->getProcess();
                        $status[] = $process->getStatus();
                        $state[] = $process->getState();
                    }

                    /**
                     * Определим завершились ли процесы
                     * если завершились то вернем родительскому значение в переменной
                     * $processStatus = "complete";
                     */
                    $countState = array_count_values($state);
                    if (!empty($countState['complete'])) {
                        if ($countState['complete'] == count($state)) {
                            $processStatus = "complete";
                        }
                    }

                    /**
                     * Только тогда когда дочернии процессы завершились есть смысл
                     * вычислять и продвигать родителский процесс по статусу
                     */
                    if ($processStatus == "complete") {
                        $countStatus = array_count_values($status);
                        if (!empty($countStatus['agreed'])) {
                            if ($countStatus['agreed'] == count($status)) {
                                $expertiseStatus = "agreed";
                            }
                        } else {
                            $expertiseStatus = "draft";
                        }
                        return [
                            'expertiseStatus' => $expertiseStatus,
                            'processStatus' => $processStatus,
                            'stepComplete' => 1
                        ];
                    }

                    return [
                        'processStatus' => $processStatus,
                        'stepComplete' => 1
                    ];

                }
            } catch (Exception $e) {
                return [
                    'stepComplete' => 0
                ];
            }
        }
    }

    public function startChildrenUserExpertise($params)
    {
        if (!empty($params['childrenUserExpertise'])) {
            $childrenUserExpertise = explode(',', $params['childrenUserExpertise']);
            if (!empty($childrenUserExpertise)) {
                foreach ($childrenUserExpertise as $userExpertise) {
                    $params = [];
                    $params['id'] = $userExpertise;
                    $params['expertiseStatus'] = 'start';
                    $this->logger->addInfo("Expertise: startChildrenUserExpertise pushed by param:" . json_encode($params));
                    $this->pushExpertise($params);
                    $this->logger->addInfo("Expertise: startChildrenUserExpertise completed");
                }
                return [
                    'stepComplete' => 1
                ];
            }
        } else {
            return [
                'stepComplete' => 0
            ];
        }
    }

    public function createChildrenUserExpertise($params)
    {
        if (!empty($params['childrenUserExpertise'])) {
            $childrenUserExpertise = explode(',', $params['childrenUserExpertise']);
            if (!empty($childrenUserExpertise)) {
                $em = $this->container->get('doctrine.orm.entity_manager');
                $em->flush();
                $em->clear();
                foreach ($childrenUserExpertise as $userExpertise) {
                    /** @var \ExpertiseBundle\Entity\ExpertiseUser $expertiseUser */
                    $expertiseUser = $em->getRepository('ExpertiseBundle:ExpertiseUser')
                        ->findOneById($userExpertise);

                    $expertiseGroup = $expertiseUser->getExpertiseGroup();

                    $params = [
                        'userId' => $expertiseUser->getUserId(),
                        'userRole' => $expertiseUser->getRoleName(),
                        'userRoleType' => "child",
                        'entityId' => $expertiseGroup->getEntityId(),
                        'entityType' => $expertiseGroup->getEntityType(),
                        'groupId' => $expertiseGroup->getGroupId(),
                        'groupType' => "child",
                        'expertiseStatus' => "start",
                    ];

                    $processService = $this->container->get('app.process');
                    /** @var \ExpertiseBundle\Helper\Process $process */
                    $process = $processService->create(
                        $params,
                        $this->container->getParameter('expertise.process_id_user')
                    );

                    $newExpertiseUser = new userExpertise();
                    $newExpertiseUser->setEntityStatus($expertiseUser->getEntityStatus());
                    $newExpertiseUser->setExpertiseGroup($expertiseUser->getExpertiseGroup());
                    $newExpertiseUser->setUserId($expertiseUser->getUserId());
                    $newExpertiseUser->setRoleName($expertiseUser->getRoleName());
                    $newExpertiseUser->setBpmnId($process->getId());
                    $newExpertiseUser->setProcessStatus('active');
                    $em->persist($newExpertiseUser);
                    $em->flush();
                }
                return [
                    'stepComplete' => 1
                ];
            }
        } else {
            return [
                'stepComplete' => 0
            ];
        }
    }

    public function saveFieldExpertise($params = [])
    {
        $userExpertiseId = !empty($params['id']) ? $params['id'] : null;
        $commentField = !empty($params['commentField']) ? $params['commentField'] : null;
        $commentBody = !empty($params['commentBody']) ? $params['commentBody'] : null;
        $value = !empty($params['commentValue']) ? $params['commentValue'] : null;

        if (!empty($userExpertiseId) && !empty($commentField)) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $expertiseUser = $em->getRepository('ExpertiseBundle:ExpertiseUser')
                ->findOneById($params['id']);
            if (!empty($expertiseUser)) {
                $userComment = new userComment();
                $userComment->setExpertiseUser($expertiseUser);
                if (!empty($commentBody)) {
                    $userComment->setComment($commentBody);
                }
                $userComment->setFieldId($commentField);
                if (!empty($value)) {
                    $userComment->setValue($value);
                }
                $em->persist($userComment);
                $em->flush();

                return [
                    'stepComplete' => 1
                ];
            }
        }

        return [
            'stepComplete' => 1
        ];

    }

    /**
     * Save user expertise
     * @param array $params
     * @return array
     */
    public function saveUserExpertise($params = [])
    {
        $userExpertiseId = !empty($params['id']) ? $params['id'] : null;
        $userExpertiseStatus = !empty($params['expertiseStatus']) ? $params['expertiseStatus'] : null;

        if (!empty($userExpertiseId)) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $em->flush();
            $em->clear();
            /** @var \ExpertiseBundle\Entity\ExpertiseUser $expertiseUser */
            $expertiseUser = $em->getRepository('ExpertiseBundle:ExpertiseUser')
                ->findOneById($userExpertiseId);
            if (!empty($userExpertiseStatus)) {
                $expertiseUser->setEntityStatus($userExpertiseStatus);
                $expertiseUser->setProcessStatus("finished");
                $expertiseUser->setTimeEnd(new \DateTime('now'));
                $em->persist($expertiseUser);
                $em->flush();

                $groupId = $expertiseUser->getExpertiseGroup()
                    ->getGroupId();
                $entityId = $expertiseUser->getExpertiseGroup()
                    ->getEntityId();
                $entityType = $expertiseUser->getExpertiseGroup()
                    ->getEntityType();
                $userId = $expertiseUser->getUserId();
                $userRole = $expertiseUser->getRoleName();

                if (!empty($groupId) && !empty($entityId) && !empty($entityType)) {
                    $groupExpertiseTree = $this->getGroupExpertiseTree($groupId, $entityId, $entityType);
                    $userExpertise = $groupExpertiseTree->getUserExpertise($userId, $userRole);
                    /** @var \ExpertiseBundle\Tree\ExpertiseUser $parent */
                    $isRoot = $userExpertise->isRoot();
                    if ($isRoot) {
                        $groupExpertiseTree->pushProcess(['expertiseStatus' => $userExpertiseStatus]);
                        if (!empty($process)) {
                            return [
                                'stepComplete' => 1
                            ];
                        }
                    }
                }

                return [
                    'stepComplete' => 1
                ];
            }
        }

        return [
            'stepComplete' => 1
        ];

    }
}