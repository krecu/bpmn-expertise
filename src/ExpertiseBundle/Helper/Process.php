<?php
namespace ExpertiseBundle\Helper;

class Process {

    const PROCESS_COMPLETE = 4;

    private $id;

    private $processDefinitionId;

    private $status;

    private $params;

    private $task;

    private $entity;

    private $state;

    /**
     * @return mixed
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state) {
        $this->state = $state;

        return $this;
    }

    /**
     * @param mixed $entity
     */
    public function setEntity($entity) {
        $this->entity = $entity;
    }

    public function __construct() {
    }

    public function generateByJson($process) {
        if (!empty($process->globals->expertiseStatus)) {
            $this->setStatus($process->globals->expertiseStatus);
        } else {
            $this->setStatus("notSet");
        }
        $this->setProcessDefinitionId($process->processDefinitionId);
        $this->setParams($process->globals);
        $this->setId($process->id);
        $this->setEntity($process);
        $this->setTask($process);
        if (!empty($process->globals->processStatus)) {
            $this->setState($process->globals->processStatus);
        }

        if (!empty($process->task->params)) {
            foreach ($process->task->params as $param) {
                $this->params->{$param->key} = $param->value;
            }
        }

        return $this;
    }

    public function getControls($id = null) {

        $params = $this->getParams();

        if (!empty($params->controls)) {

            $controls = $params->controls;
            foreach ($controls as $key => $control) {
                $controls[$key]->params->id = $id;
                $controls[$key]->params->entityId = $params->expertiseStatus;
                $controls[$key]->params->entityId = $params->entityId;
                $controls[$key]->params->groupId = $params->groupId;
                $controls[$key]->params->entityType = $params->entityType;
                $controls[$key]->params->userRoleType = $params->userRoleType;
                $controls[$key]->params->userRole = $params->userRole;
                $controls[$key]->params->userId = $params->userId;
            }

            return $controls;
        }

        return null;
    }

    /**
     * @return mixed
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id) {
        $this->id = $id;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getProcessDefinitionId() {
        return $this->processDefinitionId;
    }

    /**
     * @param mixed $processDefinitionId
     */
    public function setProcessDefinitionId($processDefinitionId) {
        $this->processDefinitionId = $processDefinitionId;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status) {
        $this->status = $status;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * @param mixed $params
     */
    public function setParams($params) {
        $this->params = $params;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTask() {
        return $this->task;
    }

    /**
     * @param mixed $task
     */
    public function setTask($task) {
        $this->task = $task;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity() {
        return $this->entity;
    }

}