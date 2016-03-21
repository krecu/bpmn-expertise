<?php

namespace ExpertiseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ExpertiseGroup
 *
 * @ORM\Table(name="expertise_group", indexes={@ORM\Index(name="IDX_711E37846BF700BD", columns={"status_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ExpertiseGroup
{
	/**
	 * @var guid
	 *
	 * @ORM\Column(name="id", type="guid", nullable=false)
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="UUID")
	 * @ORM\SequenceGenerator(sequenceName="expertise_group_id_seq", allocationSize=1, initialValue=1)
	 */
	private $id;

	/**
	 * @var guid
	 *
	 * @ORM\Column(name="group_id", type="guid", nullable=true)
	 */
	private $groupId;

	/**
	 * @var guid
	 *
	 * @ORM\Column(name="entity_id", type="guid", nullable=true)
	 */
	private $entityId;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="entity_type", type="string", length=255, nullable=true)
	 */
	private $entityType;

	/**
	 * @var string
	 *
	 * @ORM\Column(name="bpmn_id", type="string", length=255, nullable=true)
	 */
	private $bpmnId;

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
	 * @var \ExpertiseStatus
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
     * @var \expertiseUser[]
     *
     * @ORM\OneToMany(targetEntity="ExpertiseUser", mappedBy="expertiseGroup")
     */
    private $expertiseUsers;

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
	 * Set groupId
	 *
	 * @param guid $groupId
	 * @return ExpertiseGroup
	 */
	public function setGroupId($groupId)
	{
		$this->groupId = $groupId;

		return $this;
	}

	/**
	 * Get groupId
	 *
	 * @return guid
	 */
	public function getGroupId()
	{
		return $this->groupId;
	}

	/**
	 * Set entityId
	 *
	 * @param guid $entityId
	 * @return ExpertiseGroup
	 */
	public function setEntityId($entityId)
	{
		$this->entityId = $entityId;

		return $this;
	}

	/**
	 * Get entityId
	 *
	 * @return guid
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * Set entityType
	 *
	 * @param string $entityType
	 * @return ExpertiseGroup
	 */
	public function setEntityType($entityType)
	{
		$this->entityType = $entityType;

		return $this;
	}

	/**
	 * Get entityType
	 *
	 * @return string
	 */
	public function getEntityType()
	{
		return $this->entityType;
	}

	/**
	 * Set entityType
	 *
	 * @param string $bpmnId
	 * @return BpmnId
	 */
	public function setBpmnId($bpmnId)
	{
		$this->bpmnId = $bpmnId;

		return $this;
	}

	/**
	 * Get bpmnId
	 *
	 * @return string
	 */
	public function getBpmnId()
	{
		return $this->bpmnId;
	}

	/**
	 * Set timeStart
	 *
	 * @param \DateTime $timeStart
	 * @return ExpertiseGroup
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
	 * @return ExpertiseGroup
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
	 * Set status
	 *
	 * @param \ExpertiseBundle\Entity\ExpertiseStatus $status
	 * @return ExpertiseGroup
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
        $this->expertiseUsers = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add expertiseUsers
     *
     * @param \ExpertiseBundle\Entity\ExpertiseUser $expertiseUsers
     * @return ExpertiseGroup
     */
    public function addExpertiseUser(\ExpertiseBundle\Entity\ExpertiseUser $expertiseUsers)
    {
        $this->expertiseUsers[] = $expertiseUsers;

        return $this;
    }

    /**
     * Remove expertiseUsers
     *
     * @param \ExpertiseBundle\Entity\ExpertiseUser $expertiseUsers
     */
    public function removeExpertiseUser(\ExpertiseBundle\Entity\ExpertiseUser $expertiseUsers)
    {
        $this->expertiseUsers->removeElement($expertiseUsers);
    }

    /**
     * Get expertiseUsers
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getExpertiseUsers()
    {
        return $this->expertiseUsers;
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
     */
    public function setEntityStatus($entityStatus)
    {
        $this->entityStatus = $entityStatus;

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



}
