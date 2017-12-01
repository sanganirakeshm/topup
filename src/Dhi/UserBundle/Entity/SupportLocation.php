<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="support_location")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\SupportLocationRepository")
 */

class SupportLocation {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(name="name", type="string", length=255)
     *
     */
    protected $name;

    /**
     * @ORM\OneToMany(targetEntity="Support", mappedBy="location")
     */    
     protected $support;
     
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $supportsite;
    
    /**

     * @ORM\Column(name="sequence_number", type="integer", length=10, nullable=true)
     *
     */
    protected $sequenceNumber;

     /**
     * @ORM\OneToOne(targetEntity="Dhi\UserBundle\Entity\SolarWindsSupportLocation", mappedBy="supportLocation")
     */
    protected $solarwindsSupportLocation;

    /**
     * @ORM\Column(name="is_deleted", type="boolean")
     *
     */
    protected $isDeleted = false;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return SupportLocation
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->support = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add support
     *
     * @param \Dhi\UserBundle\Entity\Support $support
     * @return SupportLocation
     */
    public function addSupport(\Dhi\UserBundle\Entity\Support $support)
    {
        $this->support[] = $support;

        return $this;
    }

    /**
     * Remove support
     *
     * @param \Dhi\UserBundle\Entity\Support $support
     */
    public function removeSupport(\Dhi\UserBundle\Entity\Support $support)
    {
        $this->support->removeElement($support);
    }

    /**
     * Get support
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSupport()
    {
        return $this->support;
    }

    /**
     * Set supportsite
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $supportsite
     * @return SupportLocation
     */
    public function setSupportsite(\Dhi\AdminBundle\Entity\WhiteLabel $supportsite = null)
    {
        $this->supportsite = $supportsite;

        return $this;
    }

    /**
     * Get supportsite
     *
     * @return \Dhi\AdminBundle\Entity\WhiteLabel 
     */
    public function getSupportsite()
    {
        return $this->supportsite;
    }

    /**
     * Set sequenceNumber
     *
     * @param integer $sequenceNumber
     * @return SupportLocation
     */
    public function setSequenceNumber($sequenceNumber)
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }

    /**
     * Get sequenceNumber
     *
     * @return integer 
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }


    /**
     * Set solarwindsSupportLocation
     *
     * @param \Dhi\UserBundle\Entity\SolarWindsSupportLocation $solarwindsSupportLocation
     * @return SupportLocation
     */
    public function setSolarwindsSupportLocation(\Dhi\UserBundle\Entity\SolarWindsSupportLocation $solarwindsSupportLocation = null)
    {
        $this->solarwindsSupportLocation = $solarwindsSupportLocation;

        return $this;
    }

    /**
     * Get solarwindsSupportLocation
     *
     * @return \Dhi\UserBundle\Entity\SolarWindsSupportLocation 
     */
    public function getSolarwindsSupportLocation()
    {
        return $this->solarwindsSupportLocation;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return SupportLocation
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Set createdBy
     *
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return SupportLocation
     */
    public function setCreatedBy(\Dhi\UserBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
}
