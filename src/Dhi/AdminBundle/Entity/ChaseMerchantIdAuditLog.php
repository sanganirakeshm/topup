<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ChaseMerchantIdAuditLog
 *
 * @ORM\Table(name="chase_merchant_id_audit_log")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ChaseMerchantIdAuditLogRepository")
 */
class ChaseMerchantIdAuditLog
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
    * @var integer
    *
    * @ORM\Column( name="service_location_wise_chase_merchant_id", type="integer", length=11, nullable=true)
    */
    protected $serviceLocationWiseChaseMerchantId;
    
    /**
     * @ORM\Column(name="service_location", type="string", length=50, nullable=true)
     */
    protected $serviceLocation;
    
    /**
     * @ORM\Column(name="old_chase_merchant_id", type="string", length=50, nullable=true)
     */
    protected $oldChaseMerchantId;
    
    /**
     * @ORM\Column(name="new_chase_merchant_id", type="string", length=50, nullable=true)
     */
    protected $newChaseMerchantId;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="old_updated_at", type="datetime", nullable=true)
     */
    protected $oldUpdatedAt;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="new_updated_at", type="datetime", nullable=true)
     */
    protected $newUpdatedAt;
    
    /**
     * @ORM\Column(name="old_updated_by", type="string", length=50, nullable=true)
     */
    protected $oldUpdatedBy;
    
    /**
     * @ORM\Column(name="new_updated_by", type="string", length=50, nullable=true)
     */
    protected $newUpdatedBy;
    
    /**
     * @ORM\Column(name="operation_type", type="string", columnDefinition="ENUM('Inserted','Updated','Deleted')", options={"comment":"Inserted, Updated ,Deleted"})
     */
    protected $operationType;
    
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
     * Set serviceLocationWiseChaseMerchantId
     *
     * @param integer $serviceLocationWiseChaseMerchantId
     * @return ChaseMerchantIdAuditLog
     */
    public function setServiceLocationWiseChaseMerchantId($serviceLocationWiseChaseMerchantId)
    {
        $this->serviceLocationWiseChaseMerchantId = $serviceLocationWiseChaseMerchantId;

        return $this;
    }

    /**
     * Get serviceLocationWiseChaseMerchantId
     *
     * @return integer 
     */
    public function getServiceLocationWiseChaseMerchantId()
    {
        return $this->serviceLocationWiseChaseMerchantId;
    }

    /**
     * Set serviceLocation
     *
     * @param string $serviceLocation
     * @return ChaseMerchantIdAuditLog
     */
    public function setServiceLocation($serviceLocation)
    {
        $this->serviceLocation = $serviceLocation;

        return $this;
    }

    /**
     * Get serviceLocation
     *
     * @return string 
     */
    public function getServiceLocation()
    {
        return $this->serviceLocation;
    }

    /**
     * Set oldChaseMerchantId
     *
     * @param string $oldChaseMerchantId
     * @return ChaseMerchantIdAuditLog
     */
    public function setOldChaseMerchantId($oldChaseMerchantId)
    {
        $this->oldChaseMerchantId = $oldChaseMerchantId;

        return $this;
    }

    /**
     * Get oldChaseMerchantId
     *
     * @return string 
     */
    public function getOldChaseMerchantId()
    {
        return $this->oldChaseMerchantId;
    }

    /**
     * Set newChaseMerchantId
     *
     * @param string $newChaseMerchantId
     * @return ChaseMerchantIdAuditLog
     */
    public function setNewChaseMerchantId($newChaseMerchantId)
    {
        $this->newChaseMerchantId = $newChaseMerchantId;

        return $this;
    }

    /**
     * Get newChaseMerchantId
     *
     * @return string 
     */
    public function getNewChaseMerchantId()
    {
        return $this->newChaseMerchantId;
    }

    /**
     * Set oldUpdatedAt
     *
     * @param \DateTime $oldUpdatedAt
     * @return ChaseMerchantIdAuditLog
     */
    public function setOldUpdatedAt($oldUpdatedAt)
    {
        $this->oldUpdatedAt = $oldUpdatedAt;

        return $this;
    }

    /**
     * Get oldUpdatedAt
     *
     * @return \DateTime 
     */
    public function getOldUpdatedAt()
    {
        return $this->oldUpdatedAt;
    }

    /**
     * Set newUpdatedAt
     *
     * @param \DateTime $newUpdatedAt
     * @return ChaseMerchantIdAuditLog
     */
    public function setNewUpdatedAt($newUpdatedAt)
    {
        $this->newUpdatedAt = $newUpdatedAt;

        return $this;
    }

    /**
     * Get newUpdatedAt
     *
     * @return \DateTime 
     */
    public function getNewUpdatedAt()
    {
        return $this->newUpdatedAt;
    }

    /**
     * Set oldUpdatedBy
     *
     * @param string $oldUpdatedBy
     * @return ChaseMerchantIdAuditLog
     */
    public function setOldUpdatedBy($oldUpdatedBy)
    {
        $this->oldUpdatedBy = $oldUpdatedBy;

        return $this;
    }

    /**
     * Get oldUpdatedBy
     *
     * @return string 
     */
    public function getOldUpdatedBy()
    {
        return $this->oldUpdatedBy;
    }

    /**
     * Set newUpdatedBy
     *
     * @param string $newUpdatedBy
     * @return ChaseMerchantIdAuditLog
     */
    public function setNewUpdatedBy($newUpdatedBy)
    {
        $this->newUpdatedBy = $newUpdatedBy;

        return $this;
    }

    /**
     * Get newUpdatedBy
     *
     * @return string 
     */
    public function getNewUpdatedBy()
    {
        return $this->newUpdatedBy;
    }

    /**
     * Set operationType
     *
     * @param string $operationType
     * @return ChaseMerchantIdAuditLog
     */
    public function setOperationType($operationType)
    {
        $this->operationType = $operationType;

        return $this;
    }

    /**
     * Get operationType
     *
     * @return string 
     */
    public function getOperationType()
    {
        return $this->operationType;
    }
}
