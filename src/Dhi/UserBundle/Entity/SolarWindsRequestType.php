<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(name="solar_winds_request_type")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\SolarWindsRequestTypeRepository")
 */
class SolarWindsRequestType {
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

   /**
     * @ORM\Column(name="solar_wind_id", type="integer", nullable=true)
     *
     */
    protected $solarWindId;
 
    /**
     * @var string
     * @ORM\Column(name="request_type_name", type="string", length=255, nullable=false)
     */
    protected $requestTypeName;
    
    /**
     * @var \DateTime
     * @ORM\Column(name="import_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $importAt;
    
    /**
     * @ORM\OneToMany(targetEntity="SolarWindsSupportLocation", mappedBy="solarWindsRequestType")
     */    
     protected $solarwindsSupportLocation;
     
     /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    protected $isDeleted = false;


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
     * Set solarWindId
     *
     * @param integer $solarWindId
     * @return SolarWindsRequestType
     */
    public function setSolarWindId($solarWindId)
    {
        $this->solarWindId = $solarWindId;

        return $this;
    }

    /**
     * Get solarWindId
     *
     * @return integer 
     */
    public function getSolarWindId()
    {
        return $this->solarWindId;
    }

    /**
     * Set requestTypeName
     *
     * @param string $requestTypeName
     * @return SolarWindsRequestType
     */
    public function setRequestTypeName($requestTypeName)
    {
        $this->requestTypeName = $requestTypeName;

        return $this;
    }

    /**
     * Get requestTypeName
     *
     * @return string 
     */
    public function getRequestTypeName()
    {
        return $this->requestTypeName;
    }

    /**
     * Set importAt
     *
     * @param \DateTime $importAt
     * @return SolarWindsRequestType
     */
    public function setImportAt($importAt)
    {
        $this->importAt = $importAt;

        return $this;
    }

    /**
     * Get importAt
     *
     * @return \DateTime 
     */
    public function getImportAt()
    {
        return $this->importAt;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return SolarWindsRequestType
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
     * Constructor
     */
    public function __construct()
    {
        $this->solarwindsSupportLocation = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add solarwindsSupportLocation
     *
     * @param \Dhi\UserBundle\Entity\SolarWindsSupportLocation $solarwindsSupportLocation
     * @return SolarWindsRequestType
     */
    public function addSolarwindsSupportLocation(\Dhi\UserBundle\Entity\SolarWindsSupportLocation $solarwindsSupportLocation)
    {
        $this->solarwindsSupportLocation[] = $solarwindsSupportLocation;

        return $this;
    }

    /**
     * Remove solarwindsSupportLocation
     *
     * @param \Dhi\UserBundle\Entity\SolarWindsSupportLocation $solarwindsSupportLocation
     */
    public function removeSolarwindsSupportLocation(\Dhi\UserBundle\Entity\SolarWindsSupportLocation $solarwindsSupportLocation)
    {
        $this->solarwindsSupportLocation->removeElement($solarwindsSupportLocation);
    }

    /**
     * Get solarwindsSupportLocation
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSolarwindsSupportLocation()
    {
        return $this->solarwindsSupportLocation;
    }
}
