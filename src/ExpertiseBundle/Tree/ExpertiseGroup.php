<?php
namespace ExpertiseBundle\Tree;

use AppBundle\Proxy\RpcRequestParams;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Security\Acl\Exception\Exception;
use ExpertiseBundle\Helper\Process;

use ExpertiseBundle\Entity\ExpertiseGroup as groupExpertise;

class ExpertiseGroup extends ExpertiseTree
{
    /** @var string Default expertise status */
    private $status = null;

    /** @var \ExpertiseBundle\Entity\ExpertiseGroup */
    private $entityGroup = null;

    /** @var string */
    private $groupId = null;

    /** @var string */
    private $entityId = null;

    /** @var string */
    private $entityType = null;

    /** @var string */
    private $groupType = null;

    /** @var string */
    private $process = null;

    /** @var Container $container */
    private $container = null;

    private $isInitProcess = false;

    private $isInitExpertise = false;

    /** @var \ExpertiseBundle\Tree\ExpertiseUser */
    private $userTree = null;

    public function __construct($groupId, $entityId, $entityType, $container, $logger)
    {
        $this->groupId = $groupId;
        $this->entityId = $entityId;
        $this->userId = "disabled";
        /* @todo WHATAFUCK?! */
        $this->entityType = $entityType;
        $this->container = $container;
        $this->getEntityGroup();
        $this->getProcess();
    }

    /**
     * Create new expertise in group
     * @return $this
     */
    public function createExpertise()
    {
        /**
         * IF process created and expertise not created
         * then create expertise
         */
        if ($this->getIsInitProcess() && !$this->getIsInitExpertise()) {
            try {
                $em = $this->container->get('doctrine.orm.entity_manager');
                $groupExpertise = new groupExpertise();
                $groupExpertise
                    ->setEntityStatus($this->getStatus())
                    ->setEntityType($this->getEntityType())
                    ->setEntityId($this->getEntityId())
                    ->setGroupId($this->getGroupId())
                    ->setBpmnId($this->getProcess()->getId());
                $em->persist($groupExpertise);
                $em->flush();
            } catch (\Exception $e) {
                $this->container->get('logger')->addDebug("ExpertiseGroup: expertise group not created with error:" . $e->getMessage());
            }
            if (!empty($groupExpertise->getId())) {
                $this->setEntityGroup($groupExpertise);
                $this->setIsInitExpertise(true);
            } else {
                $this->container->get('logger')->addDebug("ExpertiseGroup: expertise not created");
            }
        } else {
            $this->container->get('logger')->addDebug("ExpertiseGroup: attempt to create expertise without process");
        }

        return $this;
    }

    /**
     * Get expertise status by tree logic
     * @return null|string
     */
    public function getStatus()
    {
        $expertiseStatus = "notSet";
        if ($children = $this->getChildren()) {
            $statuses = [];
            foreach ($children as $child) {
                if (!empty($process = $this->getProcess())) {
                    $statuses[] = $process->getStatus();
                }
            }
            $countStatus = array_count_values($statuses);
            if (!empty($countStatus['agreed'])) {
                if ($countStatus['agreed'] == count($statuses)) {
                    $expertiseStatus = "agreed";
                }
            } else {
                $expertiseStatus = "draft";
            }
        } else {
            if (!empty($process = $this->getProcess())) {
                $expertiseStatus = $process->getStatus();
            }
        }
        return $expertiseStatus;
    }

    /**
     * Get expertiseUser in expertiseGroup by User and Role
     * @param $userId
     * @param $userRole
     * @return \ExpertiseBundle\Tree\ExpertiseUser|null
     */
    public function getUserExpertise($userId, $userRole)
    {
        $userTree = $this->getUsersTree();
        if ($userTree->getUserId() == $userId && $userTree->getUserRole() == $userRole) {
            return $userTree;
        } else {
            if ($userTree->getUserRoleType() == "parent") {
                /** @var \ExpertiseBundle\Tree\ExpertiseUser[] $children */
                $children = $userTree->getChildren();
                foreach ($children as $child) {
                    if ($child->getUserId() == $userId && $child->getUserRole() == $userRole) {
                        return $child;
                    }
                }
            }
        }

        return null;
    }


    public function getControls()
    {
        $process = $this->getProcess();
        if (!empty($process)) {
            $params = $process->getParams();
            if (!empty($params->controls)) {
                return $params->controls;
            }
        }
        return null;
    }

    /**
     * @param $status
     * @return $this
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get simple transform tree to inline array
     * @return \ExpertiseBundle\Tree\ExpertiseGroup[]
     */
    public function getList()
    {
        $expertise = [];
        $expertise[] = $this;
        $children = $this->getChildren();
        if (!empty($children)) {
            foreach ($children as $child) {
                $expertise = array_merge($expertise, $child->getList());
            }
        }

        return $expertise;
    }

    public function getUsersTree()
    {
        return $this->userTree;
    }

    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param $container
     * @return $this
     */
    public function setContainer($container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * @return null
     */
    public function getGroupId()
    {
        return $this->groupId;
    }

    /**
     * @param $groupId
     * @return $this
     */
    public function setGroupId($groupId)
    {
        $this->groupId = $groupId;

        return $this;
    }

    /**
     * @return null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param $userId
     * @return $this
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return null
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param $entityId
     * @return $this
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return null
     */
    public function getEntityType()
    {
        return $this->entityType;
    }

    /**
     * @param $entityType
     * @return $this
     */
    public function setEntityType($entityType)
    {
        $this->entityType = $entityType;

        return $this;
    }

    /**
     * Create new BPMN process
     * @return $this
     */
    public function createProcess()
    {
        /**
         * If process not load with construct class
         * created him
         */
        if (!$this->getIsInitProcess()) {
            $params = [
                'entityId' => $this->getEntityId(),
                'groupId' => $this->getGroupId(),
                'entityType' => $this->getEntityType(),
                'groupType' => $this->getGroupType()
            ];
            $processService = $this->container->get('app.process');
            $process = $processService->create($params, $this->container->getParameter('expertise.process_id_group'));
            if (!empty($process)) {
                $this->setProcess($process);
                $this->isInitProcess = true;
            } else {
                $this->container->get('logger')->addDebug("ExpertiseGroup: process not init with params:" . json_encode($params));
                $this->isInitProcess = false;
            }
        }

        return $this;
    }

    /**
     * Get process data from bpmn
     * @return \ExpertiseBundle\Helper\Process
     */
    public function getProcess()
    {
        if ($this->getIsInitExpertise()) {
            $entityGroup = $this->getEntityGroup();
            $processService = $this->container->get('app.process');
            $logger = $this->container->get('logger');
            //$logger->addInfo("Expertise: getProcess error:" . json_encode($e->getMessage()));
            $process = $processService->load($entityGroup->getBpmnId());
            if (!empty($process)) {
                $this->setProcess($process);
                $this->isInitProcess = true;
            } else {
                $this->isInitProcess = false;
            }
        }
        return $this->process;
    }

    /**
     * @param \ExpertiseBundle\Helper\Process $process
     */
    public function setProcess($process)
    {
        $this->process = $process;
    }

    /**
     * Get expertiseGroup Entity value from storage
     * @return \ExpertiseBundle\Entity\ExpertiseGroup
     */
    public function getEntityGroup()
    {

        $groupId = $this->getGroupId();
        $entityId = $this->getEntityId();
        $entityType = $this->getEntityType();

        if (!empty($entityType) && !empty($groupId) && !empty($entityId) && !$this->getIsInitExpertise()) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $this->container->get('doctrine.orm.entity_manager');

            $entityGroupData = $em->getRepository('ExpertiseBundle:ExpertiseGroup')->findOneBy([
                'groupId' => $groupId,
                'entityId' => $entityId,
                'entityType' => $entityType
            ]);

            if (!empty($entityGroupData)) {
                $this->setEntityGroup($entityGroupData);
                $this->setIsInitExpertise(true);
            }
        }

        return $this->entityGroup;
    }

    /**
     * @param null $entityGroup
     */
    public function setEntityGroup($entityGroup)
    {
        $this->entityGroup = $entityGroup;
    }

    /**
     * @return string
     */
    public function getGroupType()
    {
        return $this->groupType;
    }

    /**
     * @param string $groupType
     */
    public function setGroupType($groupType)
    {
        $this->groupType = $groupType;
    }

    /**
     * @return null
     */
    public function getUserTree()
    {
        return $this->userTree;
    }

    /**
     * @param null $userTree
     */
    public function setUserTree($userTree)
    {
        $this->userTree = $userTree;
    }

    /**
     * @return boolean
     */
    public function getIsInitExpertise()
    {
        return $this->isInitExpertise;
    }

    /**
     * @param boolean $isInitExpertise
     */
    public function setIsInitExpertise($isInitExpertise)
    {
        $this->isInitExpertise = $isInitExpertise;
    }

    /**
     * @return boolean
     */
    public function getIsInitProcess()
    {
        return $this->isInitProcess;
    }

    /**
     * @param boolean $isInitProcess
     */
    public function setIsInitProcess($isInitProcess)
    {
        $this->isInitProcess = $isInitProcess;

        return $this;
    }


    /**
     * Push group expertise process
     * @param array $params
     * @return string
     */
    public function pushProcess($params = [])
    {
        if (!empty($this->getIsInitProcess())) {
            $processService = $this->container->get('app.process');
            $process = $processService->push(
                $this->getProcess()->getId(),
                $params
            );
            if (!empty($process)) {
                $this->setProcess($process);
                $this->setIsInitProcess(true);
            } else {
                $this->setIsInitProcess(false);
            }
        }
        return $this->process;
    }
}
