<?php
namespace ExpertiseBundle\Tree;

use AppBundle\Proxy\RpcRequestParams;
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\Annotations\Get;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\Security\Acl\Exception\Exception;
use ExpertiseBundle\Entity\ExpertiseUser as userExpertise;

class ExpertiseUser extends ExpertiseTree
{
    /** @var string Default expertise status */
    private $status = null;

    /** @var string */
    private $userId = null;

    /** @var string */
    private $userRole = null;

    /** @var string */
    private $userRoleType = null;

    /** @var string */
    private $entityId = null;

    /** @var string */
    private $entityType = null;

    /** @var \ExpertiseBundle\Tree\ExpertiseGroup $groupExpertise */
    private $groupExpertise = null;

    /** @var \ExpertiseBundle\Entity\ExpertiseUser */
    private $entityUser = null;

    /** @var string */
    private $process = null;

    /** @var Container $container */
    private $container = null;

    private $isInitProcess = false;

    private $isInitExpertise = false;

    /**
     * @param \Tree\Node\Node $entityId
     * @param $userId
     * @param $userRole
     * @param $userRoleType
     * @param \ExpertiseBundle\Tree\ExpertiseGroup $groupExpertise
     * @param $container
     * @param $logger
     */
    public function __construct(
      $entityId,
      $userId,
      $userRole,
      $userRoleType,
      $groupExpertise,
      $container,
      $logger
    ) {
        $this->entityId = $entityId;
        $this->userId = $userId;
        $this->userRole = $userRole;
        $this->entityType = $groupExpertise->getEntityType();
        $this->groupExpertise = $groupExpertise;
        $this->container = $container;
        $this->userRoleType = $userRoleType;

        $this->getEntityUser();
        $this->getProcess();
    }

    /**
     * Create new expertise in group
     * @return $this
     */
    public function createExpertise()
    {
        /**
         * Try create userExpertise
         * If Process created && expertise not created && groupExpertise created
         */
        if ($this->getIsInitProcess() && !$this->getIsInitExpertise(
          ) && $this->getGroupExpertise()->getIsInitExpertise()
        ) {
            $em = $this->container->get('doctrine.orm.entity_manager');
            $userExpertise = new userExpertise();
            $userExpertise->setExpertiseGroup(
                $this->getGroupExpertise()->getEntityGroup()
              )->setEntityStatus($this->getProcess()->getStatus())->setUserId(
                $this->getUserId()
              )->setRoleName($this->getUserRole())->setBpmnId(
                $this->getProcess()->getId()
              )->setProcessStatus('active');
            $em->persist($userExpertise);
            $em->flush();

            if (!empty($userExpertise)) {
                $this->setEntityUser($userExpertise);
                $this->setIsInitExpertise(true);
            } else {
                $this->setIsInitExpertise(false);
                $this->container->get('logger')->addDebug(
                  "ExpertiseUser: expertise not created with params:"
                );
            }
        } else {
            $this->container->get('logger')->addDebug(
              "ExpertiseUser: attempt to create expertise without process"
            );
            $this->setIsInitExpertise(false);
        }

        return $this;
    }

    /**
     * Create new BPMN process
     * @param array $params
     * @return $this
     */
    public function createProcess($params = [])
    {
        /**
         * If process not created create him
         */
        if (!$this->getIsInitProcess()) {
            if (empty($params)) {
                $param = [
                  'userId' => $this->getUserId(),
                  'userRole' => $this->getUserRole(),
                  'userRoleType' => $this->getUserRoleType(),
                  'entityId' => $this->getGroupExpertise()->getEntityId(),
                  'entityType' => $this->getGroupExpertise()->getEntityType(),
                  'groupId' => $this->getGroupExpertise()->getGroupId(),
                  'groupType' => $this->getGroupExpertise()->getGroupType(),
                ];

                $params = array_merge($params, $param);
            }

            /** @var \ExpertiseBundle\Services\ExpertiseProcess $processService */
            $processService = $this->container->get('app.process');
            $process = $processService->create(
              $params,
              $this->container->getParameter('expertise.process_id_user')
            );
            if (!empty($process)) {
                $this->setProcess($process);
                $this->setIsInitProcess(true);
            } else {
                $this->container->get('logger')->addDebug(
                  "ExpertiseUser: process not init with params:".json_encode(
                    $params
                  )
                );
                $this->setIsInitProcess(false);
            }
        }

        return $this;
    }

    /**
     * Get userExpertise process
     * @return \ExpertiseBundle\Helper\Process
     */
    public function getProcess()
    {
        if (!$this->getIsInitProcess() && $this->getIsInitExpertise()) {
            /** @var \ExpertiseBundle\Services\ExpertiseProcess $processService */
            $processService = $this->container->get('app.process');
            $process = $processService->load(
              $this->getEntityUser()->getBpmnId()
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

    /**
     * Set process
     * @param string $process
     * @return $this
     */
    public function setProcess($process)
    {
        $this->process = $process;

        return $this;
    }

    /**
     * Get userExpertise
     * @return \ExpertiseBundle\Entity\ExpertiseUser
     */
    public function getEntityUser()
    {
        if ($this->getGroupExpertise()->getIsInitExpertise(
          ) && !$this->getIsInitExpertise()
        ) {
            /** @var \Doctrine\ORM\EntityManager $em */
            $em = $this->container->get('doctrine.orm.entity_manager');
            $entityUserData = $em->getRepository(
              'ExpertiseBundle:ExpertiseUser'
            )->findOneBy(
              [
                'expertiseGroup' => $this->getGroupExpertise()
                                         ->getEntityGroup()
                                         ->getId(),
                'userId' => $this->getUserId(),
                'roleName' => $this->getUserRole(),
                'processStatus' => 'active',
              ]
            );

            if (!empty($entityUserData)) {
                $this->setEntityUser($entityUserData);
                $this->setIsInitExpertise(true);
            } else {
                $this->setIsInitExpertise(false);
            }
        }

        return $this->entityUser;
    }

    /**
     * Set userExpertise
     * @param \ExpertiseBundle\Entity\ExpertiseUser $entityUser
     * @return $this
     */
    public function setEntityUser($entityUser)
    {
        $this->entityUser = $entityUser;

        return $this;
    }

    /**
     * Get userExpertise status
     * @return string
     */
    public function getStatus()
    {
        return $this->getProcess()->getStatus();
    }

    /**
     * Set userExpertise status
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        $entityUser = $this->getEntityUser();
        $entityUser->setEntityStatus($status);
        $em = $this->container->get('doctrine.orm.entity_manager');
        $em->clear();
        $em->persist($entityUser);
        $em->flush();
//        $this->setIsInitExpertise(false);
//        $this->getEntityUser();

        return $this;
    }

    /**
     * Get relative groupExpertise
     * @return ExpertiseGroup
     */
    public function getGroupExpertise()
    {
        return $this->groupExpertise;
    }

    /**
     * Set relative groupExpertise
     * @param ExpertiseGroup $groupExpertise
     * @return ExpertiseUser
     */
    public function setGroupExpertise($groupExpertise)
    {
        $this->groupExpertise = $groupExpertise;

        return $this;
    }

    /**
     * Get user role type
     * @return string
     */
    public function getUserRoleType()
    {
        return $this->userRoleType;
    }

    /**
     * Set user Role type {parent|child}
     * @param string $userRoleType
     * @return ExpertiseUser
     */
    public function setUserRoleType($userRoleType)
    {
        $this->userRoleType = $userRoleType;

        return $this;
    }

    /**
     * get user Id
     * @return string
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set user id
     * @param string $userId
     * @return ExpertiseUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get user role
     * @return string
     */
    public function getUserRole()
    {
        return $this->userRole;
    }

    /**
     * Set user role {autor|curator ...}
     * @param string $userRole
     * @return ExpertiseUser
     */
    public function setUserRole($userRole)
    {
        $this->userRole = $userRole;

        return $this;
    }

    /**
     * Marker: get created process
     * @return boolean
     */
    public function getIsInitProcess()
    {
        return $this->isInitProcess;
    }

    /**
     * Marker: set created process
     * @param boolean $isInitProcess
     * @return $this
     */
    public function setIsInitProcess($isInitProcess)
    {
        $this->isInitProcess = $isInitProcess;

        return $this;
    }

    /**
     * Marker: get created expertise
     * @return boolean
     */
    public function getIsInitExpertise()
    {
        return $this->isInitExpertise;
    }

    /**
     * Marker: set created expertise
     * @param boolean $isInitExpertise
     * @return $this
     */
    public function setIsInitExpertise($isInitExpertise)
    {
        $this->isInitExpertise = $isInitExpertise;

        return $this;
    }

    /**
     * Get simple transform tree to inline array
     * @return \ExpertiseBundle\Tree\ExpertiseUser[]
     */
    public function getList()
    {
        $expertise = [];
        $expertise[] = $this;
        /** @var \ExpertiseBundle\Tree\ExpertiseUser[] $children */
        $children = $this->getChildren();
        if (!empty($children)) {
            foreach ($children as $child) {
                $expertise = array_merge($expertise, $child->getList());
            }
        }

        return $expertise;
    }

    /**
     * Push user expertise process
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
                // @todo need VERY VERY careful testing
                //$this->setStatus($process->getStatus());
            } else {
                $this->setIsInitProcess(false);
            }
        }
        return $this->process;
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

}


