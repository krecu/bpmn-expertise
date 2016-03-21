<?php

namespace ExpertiseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExpertiseField
 *
 * @ORM\Table(name="expertise_field", indexes={@ORM\Index(name="IDX_472B361987009189", columns={"expertise_user_id"})})
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class ExpertiseField
{
    /**
     * @var guid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\SequenceGenerator(sequenceName="expertise_field_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="field_id", type="string", length=255, nullable=true)
     */
    private $fieldId;

    /**
     * @var string
     *
     * @ORM\Column(name="comment", type="string", length=255, nullable=true)
     */
    private $comment;

    /**
     * @var text
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     */
    private $value;

    /**
     * @var \ExpertiseUser
     *
     * @ORM\ManyToOne(targetEntity="ExpertiseUser", cascade={"persist"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="expertise_user_id", referencedColumnName="id")
     * })
     */
    private $expertiseUser;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="time_created", type="datetime", nullable=true)
     */
    private $timeCreated;


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
     * Set fieldId
     *
     * @param string $fieldId
     * @return ExpertiseField
     */
    public function setFieldId($fieldId)
    {
        $this->fieldId = $fieldId;

        return $this;
    }

    /**
     * Get fieldId
     *
     * @return string
     */
    public function getFieldId()
    {
        return $this->fieldId;
    }

    /**
     * Set comment
     *
     * @param integer $comment
     * @return ExpertiseField
     */
    public function setComment($comment)
    {
        $this->comment = $comment;

        return $this;
    }

    /**
     * Get comment
     *
     * @return integer
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Set value
     *
     * @param boolean $value
     * @return ExpertiseField
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return boolean
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set expertiseUser
     *
     * @param \ExpertiseBundle\Entity\ExpertiseUser $expertiseUser
     * @return ExpertiseField
     */
    public function setExpertiseUser(\ExpertiseBundle\Entity\ExpertiseUser $expertiseUser = null)
    {
        $this->expertiseUser = $expertiseUser;

        return $this;
    }

    /**
     * Get expertiseUser
     *
     * @return \ExpertiseBundle\Entity\ExpertiseUser
     */
    public function getExpertiseUser()
    {
        return $this->expertiseUser;
    }

    /**
     * @return \DateTime
     */
    public function getTimeCreated()
    {
        return $this->timeCreated;
    }

    /**
     * @param \DateTime $timeCreated
     *
     * @return this
     */
    public function setTimeCreated($timeCreated)
    {
        $this->timeCreated = $timeCreated;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @return this
     */
    public function updatedTimestamps()
    {
        $this->setTimeCreated(new \DateTime('now'));

        return $this;
    }

  public function __toString(){
    return (string) $this->getTitle();
  }
}