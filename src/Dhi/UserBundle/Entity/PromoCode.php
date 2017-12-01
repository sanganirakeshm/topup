<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="promo_code")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\PromoCodeRepository")
 */

class PromoCode {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    

	 /**
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;
	
	
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation", inversedBy="Promocode")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocations;
	
	
    /**
     * @ORM\Column(name="promocode", type="text")
     */
    protected $promoCode;

	/**
     * @ORM\Column(name="duration", type="integer")
     */
    protected $duration;
    
	/**
     * @ORM\Column(name="expired_at", type="datetime", nullable=true)
     *
     */
    protected $expiredAt;
	
	 /**
     * @ORM\Column(name="note", type="text" , nullable=true)
     */
    protected $note;

	/**
     * @ORM\Column(name="created_by", type="string", nullable=false)
     */
    protected $createdBy;

    
	/**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = true;
            

    /**
     * @ORM\Column(name="package_id", type="integer")
     *
     */
    protected $packageId;

	/**
     * @ORM\Column(name="noOfRedemption", type="integer", nullable=true)
     *
     */
    protected $noOfRedemption;

	/**
     * @ORM\Column(name="isBundle", type="integer", nullable=true)
     *
     */
    protected $isBundle;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(name="is_plan_expired", type="string", columnDefinition="ENUM('Yes','No')")
     */
    protected $isPlanExpired = "No";

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
     * Set promoCode
     *
     * @param string $promoCode
     * @return PromoCode
     */
    public function setPromoCode($promoCode)
    {
        $this->promoCode = $promoCode;

        return $this;
    }

    /**
     * Get promoCode
     *
     * @return string 
     */
    public function getPromoCode()
    {
        return $this->promoCode;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return PromoCode
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return boolean 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set packageId
     *
     * @param integer $packageId
     * @return PromoCode
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return integer 
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set service
     *
     * @param \Dhi\UserBundle\Entity\Service $service
     * @return PromoCode
     */
    public function setService(\Dhi\UserBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Dhi\UserBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * Set serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return PromoCode
     */
    public function setServiceLocations(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations = null)
    {
        $this->serviceLocations = $serviceLocations;

        return $this;
    }

    /**
     * Get serviceLocations
     *
     * @return \Dhi\AdminBundle\Entity\ServiceLocation 
     */
    public function getServiceLocations()
    {
        return $this->serviceLocations;
    }

    

    /**
     * Set expiredAt
     *
     * @param \DateTime $expiredAt
     * @return PromoCode
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * Get expiredAt
     *
     * @return \DateTime 
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     * @return PromoCode
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return string 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set duration
     *
     * @param integer $duration
     * @return PromoCode
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return integer 
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set isBundle
     *
     * @param integer $isBundle
     * @return PromoCode
     */
    public function setIsBundle($isBundle)
    {
        $this->isBundle = $isBundle;

        return $this;
    }

    /**
     * Get isBundle
     *
     * @return integer 
     */
    public function getIsBundle()
    {
        return $this->isBundle;
    }

    /**
     * Set noOfRedemption
     *
     * @param integer $noOfRedemption
     * @return PromoCode
     */
    public function setNoOfRedemption($noOfRedemption)
    {
        $this->noOfRedemption = $noOfRedemption;

        return $this;
    }

    /**
     * Get noOfRedemption
     *
     * @return integer 
     */
    public function getNoOfRedemption()
    {
        return $this->noOfRedemption;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PromoCode
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return PromoCode
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
     * Set note
     *
     * @param string $note
     * @return PromoCode
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set isPlanExpired
     *
     * @param string $isPlanExpired
     * @return PromoCode
     */
    public function setIsPlanExpired($isPlanExpired)
    {
        $this->isPlanExpired = $isPlanExpired;

        return $this;
    }

    /**
     * Get isPlanExpired
     *
     * @return string 
     */
    public function getIsPlanExpired()
    {
        return $this->isPlanExpired;
    }
}
