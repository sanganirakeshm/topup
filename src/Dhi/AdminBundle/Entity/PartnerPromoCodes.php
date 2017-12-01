<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * PartnerPromoCodes
 *
 * @ORM\Table(name="partner_promo_codes")
 * @ORM\Entity()
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\PartnerPromoCodesRepository") 
 */
class PartnerPromoCodes
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
     * @ORM\ManyToOne(targetEntity="PartnerPromoCodeBatch", inversedBy="promoCodes")
     * @ORM\JoinColumn(name="batch_id", referencedColumnName="id")
     */
    protected $batchId;
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocations;
    
    /**
     * @ORM\Column(name="partner_value", type="decimal", scale=2, precision=6)
     */
    protected $partnerValue = 0.00;
    
    /**
     * @ORM\Column(name="customer_value", type="decimal", scale=2, precision=6)
     */
    protected $customerValue = 0.00;
    
    /**
     * @ORM\Column(name="code", type="string", length=10, nullable=false, unique=true)
     */
    protected $code;
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Active','Inactive')")
     */
    protected $status;
    
    /**
     * @ORM\Column(name="is_redeemed", type="string", columnDefinition="ENUM('Yes','No')")
     */
    protected $isRedeemed = "No";
    
    /**
     * @ORM\Column(name="package_id", type="integer", length=11, nullable=false)
     */
    protected $packageId;

    /**
     * @ORM\Column(name="duration", type="integer", length=3, nullable=false)
     */
    protected $duration;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expiry_date", type="date", nullable=true)
     */
    protected $expirydate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="redeemed_date", type="date", nullable=true)
     */
    protected $redeemedDate;
    
    /**
     * @ORM\Column(name="redeemed_by", type="integer", nullable=true)
     */
    protected $redeemedBy;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;
    
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
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
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase" , mappedBy="discountedPartnerPromocode")
     */
    protected $discountServicePurchases;

    /**
     * @ORM\Column(name="note", type="string", nullable=true)
     */
    protected $note;

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
     * Set code
     *
     * @param string $code
     * @return PartnerPromoCodes
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return PartnerPromoCodes
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set isRedeemed
     *
     * @param string $isRedeemed
     * @return PartnerPromoCodes
     */
    public function setIsRedeemed($isRedeemed)
    {
        $this->isRedeemed = $isRedeemed;

        return $this;
    }

    /**
     * Get isRedeemed
     *
     * @return string 
     */
    public function getIsRedeemed()
    {
        return $this->isRedeemed;
    }

    /**
     * Set packageId
     *
     * @param integer $packageId
     * @return PartnerPromoCodes
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
     * Set duration
     *
     * @param integer $duration
     * @return PartnerPromoCodes
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
     * Set expirydate
     *
     * @param \DateTime $expirydate
     * @return PartnerPromoCodes
     */
    public function setExpirydate($expirydate)
    {
        $this->expirydate = $expirydate;

        return $this;
    }

    /**
     * Get expirydate
     *
     * @return \DateTime 
     */
    public function getExpirydate()
    {
        return $this->expirydate;
    }

    /**
     * Set redeemedBy
     *
     * @param integer $redeemedBy
     * @return PartnerPromoCodes
     */
    public function setRedeemedBy($redeemedBy)
    {
        $this->redeemedBy = $redeemedBy;

        return $this;
    }

    /**
     * Get redeemedBy
     *
     * @return integer 
     */
    public function getRedeemedBy()
    {
        return $this->redeemedBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PartnerPromoCodes
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
     * @return PartnerPromoCodes
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
     * Set createdBy
     *
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return PartnerPromoCodes
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
     * Set serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return PartnerPromoCodes
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
     * Set servicePurchaseId
     *
     * @param integer $servicePurchaseId
     * @return PartnerPromoCodes
     */
    public function setServicePurchaseId($servicePurchaseId)
    {
        $this->servicePurchaseId = $servicePurchaseId;

        return $this;
    }

    /**
     * Get servicePurchaseId
     *
     * @return integer 
     */
    public function getServicePurchaseId()
    {
        return $this->servicePurchaseId;
    }

    /**
     * Set partnerValue
     *
     * @param string $partnerValue
     * @return PartnerPromoCodes
     */
    public function setPartnerValue($partnerValue)
    {
        $this->partnerValue = $partnerValue;

        return $this;
    }

    /**
     * Get partnerValue
     *
     * @return string 
     */
    public function getPartnerValue()
    {
        return $this->partnerValue;
    }

    /**
     * Set customerValue
     *
     * @param string $customerValue
     * @return PartnerPromoCodes
     */
    public function setCustomerValue($customerValue)
    {
        $this->customerValue = $customerValue;

        return $this;
    }

    /**
     * Get customerValue
     *
     * @return string 
     */
    public function getCustomerValue()
    {
        return $this->customerValue;
    }

    /**
     * Set redeemedDate
     *
     * @param \DateTime $redeemedDate
     * @return PartnerPromoCodes
     */
    public function setRedeemedDate($redeemedDate)
    {
        $this->redeemedDate = $redeemedDate;

        return $this;
    }

    /**
     * Get redeemedDate
     *
     * @return \DateTime 
     */
    public function getRedeemedDate()
    {
        return $this->redeemedDate;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->discountServicePurchases = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add discountServicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $discountServicePurchases
     * @return PartnerPromoCodes
     */
    public function addDiscountServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $discountServicePurchases)
    {
        $this->discountServicePurchases[] = $discountServicePurchases;

        return $this;
    }

    /**
     * Remove discountServicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $discountServicePurchases
     */
    public function removeDiscountServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $discountServicePurchases)
    {
        $this->discountServicePurchases->removeElement($discountServicePurchases);
    }

    /**
     * Get discountServicePurchases
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDiscountServicePurchases()
    {
        return $this->discountServicePurchases;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return PartnerPromoCodes
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
     * Set batchId
     *
     * @param \Dhi\AdminBundle\Entity\PartnerPromoCodeBatch $batchId
     * @return PartnerPromoCodes
     */
    public function setBatchId(\Dhi\AdminBundle\Entity\PartnerPromoCodeBatch $batchId = null)
    {
        $this->batchId = $batchId;

        return $this;
    }

    /**
     * Get batchId
     *
     * @return \Dhi\AdminBundle\Entity\PartnerPromoCodeBatch 
     */
    public function getBatchId()
    {
        return $this->batchId;
    }

    /**
     * Set isPlanExpired
     *
     * @param string $isPlanExpired
     * @return PartnerPromoCodes
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
