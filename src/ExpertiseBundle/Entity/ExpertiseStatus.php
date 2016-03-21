<?php

namespace ExpertiseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ExpertiseStatus
 *
 * @ORM\Table(name="expertise_status")
 * @ORM\Entity
 */
class ExpertiseStatus
{
    /**
     * @var guid
     *
     * @ORM\Column(name="id", type="guid", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="UUID")
     * @ORM\SequenceGenerator(sequenceName="expertise_status_id_seq", allocationSize=1, initialValue=1)
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="machine_name", type="string", length=255, nullable=true)
     */
    private $machineName;



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
     * Set title
     *
     * @param string $title
     * @return ExpertiseStatus
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set machineName
     *
     * @param string $machineName
     * @return ExpertiseStatus
     */
    public function setMachineName($machineName)
    {
        $this->machineName = $machineName;

        return $this;
    }

    /**
     * Get machineName
     *
     * @return string 
     */
    public function getMachineName()
    {
        return $this->machineName;
    }

    public function __toString(){
        return (string) $this->getTitle();
    }
}
