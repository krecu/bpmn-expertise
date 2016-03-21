<?php

namespace ExpertiseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExpertiseUser
 *
 * @ORM\Table(
 *   name="expertise_user",
 *   indexes={
 *     @ORM\Index(name="IDX_3F81F518EA75EB4A", columns={"expertise_group_id"}),
 *     @ORM\Index(name="IDX_3F81F5186BF700BD", columns={"status_id"})
 *   }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ExpertiseUser
{
	/**
	 * @var guid
	 *
	 * @ORM\Column(name="id", type="guid", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="UUID")
	 * @ORM\SequenceGenerator(sequenceName="expertise_user_id_seq", allocationSize=1, initialValue=1)
	 */
	private $id;

	/**
	 * @var guid
	 *
	 * @ORM\Column(name="user_id", type="guid", nullable=true)
	 */
	private $userId;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="time_start", type="datetime", nullable=true)
	 */
	private $timeStart;

	/**
	 * @var \DateTime
	 *
	 * @ORM\Column(name="time_end", type="datetime", nullable=true)
	 */
	private $timeEnd;

	/**
	 * @var ExpertiseGroup
	 *
	 * @ORM\ManyToOne(targetEntity="ExpertiseGroup", cascade={"persist"})
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="expertise_group_id", referencedColumnName="id")
	 * })
	 */
	private $expertiseGroup;

    /**
     * @var string
     *
     * @ORM\Column(name="bpmn_id", type="string", length=255, nullable=true)
     */
    private $bpmnId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="role_name", type="string", length=255, nullable=true)
	 */
	private $roleName;

	/**
	 * @var ExpertiseStatus
	 *
	 * @ORM\ManyToOne(targetEntity="ExpertiseStatus")
	 * @ORM\JoinColumns({
	 *   @ORM\JoinColumn(name="status_id", referencedColumnName="id")
	 * })
	 */
	private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="entity_status", type="string", length=255, nullable=true)
     */
    private $entityStatus;


    /**
     * @var string
     *
     * @ORM\Column(name="process_status", type="string", length=255, nullable=true)
     */
    private $processStatus;

    /**
     * @var ExpertiseField[]
     *
     * @ORM\OneToMany(targetEntity="ExpertiseField", mappedBy="expertiseUser")
     */
    private $expertiseFields;

	/**
	 * Get id
	 *
	 * @return guid
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * Set userId
	 *
	 * @param guid $userId
	 * @return $this
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;

		return $this;
	}

	/**
	 * Get userId
	 *
	 * @return guid
	 */
	public function getUserId()
	{
		return $this->userId;
	}

	/**
	 * Set timeStart
	 *
	 * @param \DateTime $timeStart
	 * @return $this
	 */
	public function setTimeStart($timeStart)
	{
		$this->timeStart = $timeStart;

		return $this;
	}

	/**
	 * Get timeStart
	 *
	 * @return \DateTime
	 */
	public function getTimeStart()
	{
		return $this->timeStart;
	}

	/**
	 * Set timeEnd
	 *
	 * @param \DateTime $timeEnd
	 * @return $this
	 */
	public function setTimeEnd($timeEnd)
	{
		$this->timeEnd = $timeEnd;

		return $this;
	}

	/**
	 * Get timeEnd
	 *
	 * @return \DateTime
	 */
	public function getTimeEnd()
	{
		return $this->timeEnd;
	}

	/**
	 * Set expertiseGroup
	 *
	 * @param \ExpertiseBundle\Entity\ExpertiseGroup $expertiseGroup
	 * @return $this
	 */
	public function setExpertiseGroup(\ExpertiseBundle\Entity\ExpertiseGroup $expertiseGroup = null)
	{
		$this->expertiseGroup = $expertiseGroup;

		return $this;
	}

	/**
	 * Get expertiseGroup
	 *
	 * @return \ExpertiseBundle\Entity\ExpertiseGroup
	 */
	public function getExpertiseGroup()
	{
		return $this->expertiseGroup;
	}


	/**
	 * Set roleName
	 *
	 * @param string $roleName
	 * @return $this
	 */
	public function setRoleName($roleName)
	{
		$this->roleName = $roleName;

		return $this;
	}

	/**
	 * Get roleName
	 *
	 * @return string
	 */
	public function getRoleName()
	{
		return $this->roleName;
	}

	/**
	 * Set status
	 *
	 * @param \ExpertiseBundle\Entity\ExpertiseStatus $status
	 * @return $this
	 */
	public function setStatus(\ExpertiseBundle\Entity\ExpertiseStatus $status = null)
	{
		$this->status = $status;
		return $this;
	}

	/**
	 * Get status
	 *
	 * @return \ExpertiseBundle\Entity\ExpertiseStatus
	 */
	public function getStatus()
	{
		return $this->status;
	}
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->expertiseFields = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add expertiseFields
     *
     * @param \ExpertiseBundle\Entity\ExpertiseField $expertiseFields
     * @return ExpertiseUser
     */
    public function addExpertiseField(\ExpertiseBundle\Entity\ExpertiseField $expertiseFields)
    {
        $this->expertiseFields[] = $expertiseFields;

        return $this;
    }

    /**
     * Remove expertiseFields
     *
     * @param \ExpertiseBundle\Entity\ExpertiseField $expertiseFields
     */
    public function removeExpertiseField(\ExpertiseBundle\Entity\ExpertiseField $expertiseFields)
    {
        $this->expertiseFields->removeElement($expertiseFields);
    }

    /**
     * Get expertiseFields
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getExpertiseFields()
    {
        return $this->expertiseFields;
    }

    /**
     * @return string
     */
    public function getEntityStatus()
    {
        return $this->entityStatus;
    }

    /**
     * @param string $entityStatus
     * @return $this
     */
    public function setEntityStatus($entityStatus)
    {
        $this->entityStatus = $entityStatus;
        return $this;
    }

    /**
     * @return string
     */
    public function getBpmnId()
    {
        return $this->bpmnId;
    }

    /**
     * @param string $bpmnId
     * @return $this
     */
    public function setBpmnId($bpmnId)
    {
        $this->bpmnId = $bpmnId;
        return $this;
    }

    /**
     *
     * @ORM\PrePersist
     */
    public function updatedTimestamps()
    {
        if ($this->getTimeStart() == null) {
            $this->setTimeStart(new \DateTime('now'));
        }
    }

    public function __toString(){
        return (string) $this->getUserId();
    }

    /**
     * @return string
     */
    public function getProcessStatus() {
        return $this->processStatus;
    }

    /**
     * @param string $processStatus
     * @return $this
     */
    public function setProcessStatus($processStatus) {
        $this->processStatus = $processStatus;

        return $this;
    }

}
