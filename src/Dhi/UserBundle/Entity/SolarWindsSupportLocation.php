<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * Bundle
 *
 * @ORM\Table(name="solar_wind_service_location")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\SolarWindsSupportLocationRepository")
 */
class SolarWindsSupportLocation
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\SolarWindsRequestType", inversedBy="solarwindsSupportLocation")
     * @ORM\JoinColumn(name="solar_wind_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $solarWindsRequestType;

    /**
     *
     * @ORM\OneToOne(targetEntity="Dhi\UserBundle\Entity\SupportLocation", inversedBy="solarwindsSupportLocation")
     * @ORM\JoinColumn(name="support_location_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $supportLocation;

     /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;
    
     /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    protected $updatedBy;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    protected $isDeleted = false;

     /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $supportsite;


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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return SolarWindsSupportLocation
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

   
    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return SolarWindsSupportLocation
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
     * @return SolarWindsSupportLocation
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

    /**
     * Set updatedBy
     *
     * @param \Dhi\UserBundle\Entity\User $updatedBy
     * @return SolarWindsSupportLocation
     */
    public function setUpdatedBy(\Dhi\UserBundle\Entity\User $updatedBy = null)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set supportsite
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $supportsite
     * @return SolarWindsSupportLocation
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
     * Set supportLocation
     *
     * @param \Dhi\UserBundle\Entity\SupportLocation $supportLocation
     * @return SolarWindsSupportLocation
     */
    public function setSupportLocation(\Dhi\UserBundle\Entity\SupportLocation $supportLocation = null)
    {
        $this->supportLocation = $supportLocation;

        return $this;
    }

    /**
     * Get supportLocation
     *
     * @return \Dhi\UserBundle\Entity\SupportLocation 
     */
    public function getSupportLocation()
    {
        return $this->supportLocation;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return SolarWindsSupportLocation
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set solarWindsRequestType
     *
     * @param \Dhi\UserBundle\Entity\SolarWindsRequestType $solarWindsRequestType
     * @return SolarWindsSupportLocation
     */
    public function setSolarWindsRequestType(\Dhi\UserBundle\Entity\SolarWindsRequestType $solarWindsRequestType = null)
    {
        $this->solarWindsRequestType = $solarWindsRequestType;

        return $this;
    }

    /**
     * Get solarWindsRequestType
     *
     * @return \Dhi\UserBundle\Entity\SolarWindsRequestType 
     */
    public function getSolarWindsRequestType()
    {
        return $this->solarWindsRequestType;
    }
}
