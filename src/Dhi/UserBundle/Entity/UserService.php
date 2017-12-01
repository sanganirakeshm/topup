<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\Service;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_services",indexes={
        @Index(name="user_services_is_plan_active_idx", columns={"is_plan_active"}),
        @Index(name="user_services_is_addon_idx", columns={"is_addon"}),
        @Index(name="user_services_status_idx", columns={"status"}),
        @Index(name="user_services_package_id_idx", columns={"package_id"}),
        @Index(name="user_services_is_expired_idx", columns={"is_expired"}),
        @Index(name="user_services_refund_idx", columns={"refund"})
    })
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserServiceRepository")
 */
class UserService
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userServices")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="userServices")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;
    
    /**
     * @ORM\OneToOne(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase", inversedBy="userService")
     * @ORM\JoinColumn(name="service_purchase_id", referencedColumnName="id")
     */
    protected $servicePurchase;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="package_id", length=255)
     */
    protected $packageId;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="package_name", length=255)
     */
    protected $packageName;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="actual_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $actualAmount;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="total_discount", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $totalDiscount;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="final_cost", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $finalCost;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="discount_rate", type="integer", nullable=true)
     */
    protected $discountRate;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="unused_credit", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $unusedCredit;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="unused_days", type="integer", nullable=true)
     */
    protected $unusedDays;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="payable_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $payableAmount;
    
    /**
     * @ORM\Column(name="activation_date", type="datetime", nullable=true)     
     */
    protected $activationDate;
    
    /**
     * @ORM\Column(name="expiry_date", type="datetime", nullable=true)
     */
    protected $expiryDate;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="sent_exp_notification", type="boolean", nullable=false)
     */
    protected $sentExpiredNotification = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_addon", type="boolean", nullable=false)
     */
    protected $isAddon = false;
        
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="service_location_ip", length=15)
     */
    protected $serviceLocationIp;
    
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", name="bandwidth", nullable=true)
     */
    protected $bandwidth = 0;
    
    /**
     * @var integer
     *
     * @ORM\Column(type="integer", name="validity", nullable=true)
     */
    protected $validity;
    
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="refund", type="boolean", nullable=true)
     */
    protected $refund = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="refund_after_expired", type="boolean", nullable=true)
     */
    protected $refundAfterExpired = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_expired", type="boolean", nullable=true)
     */
    protected $isExpired = false;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="refund_after_expired_amount", type="decimal", precision= 10, scale= 2, options={"default":0}, nullable=true)
     */
    protected $refundAfterExpiredAmount;

    /**
     * @var decimal
     *
     * @ORM\Column(name="refund_amount", type="decimal", precision= 10, scale= 2, options={"default":0}, nullable=true)
     */
    protected $refundAmount;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\ServiceBundle\Entity\PurchaseOrder", inversedBy="userService")
     * @ORM\JoinColumn(name="purchase_order_id", referencedColumnName="id")
     */
    protected $purchaseOrder;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="refund_after_expired_by_id", referencedColumnName="id")
     */
    protected $refundAfterExpiredBy;

    /**
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="refundedByUserServices")
     * @ORM\JoinColumn(name="refunded_by_id", referencedColumnName="id")
     */
    protected $refundedBy;

    /**
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="expiredUserServices")
     * @ORM\JoinColumn(name="expired_by_id", referencedColumnName="id")
     */
    protected $expiredBy;

    /**
     * @ORM\Column(name="refund_after_expired_at", type="datetime", nullable=true)
     */
    protected $refundAfterExpiredAt;

    /**
     * @ORM\Column(name="refunded_at", type="datetime", nullable=true)
     */
    protected $refundedAt;

    /**
     * @ORM\Column(name="expired_at", type="datetime", nullable=true)
     */
    protected $expiredAt;

    /**
     * @ORM\Column(name="is_plan_active", type="boolean", nullable=false)
     */
    protected $isPlanActive = true;
	
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_extend", type="boolean", nullable=false)
     */
    protected $isExtend = false;
	
	/**
     * @var integer
     *
     * @ORM\Column(type="integer", name="actual_validity", nullable=true)
     */
    
    protected $actualValidity;
	
    /**
     * @ORM\Column(name="deactivated_at", type="datetime", nullable=true)
     */
    protected $deActivatedAt;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ServicePartner", inversedBy="deactivatedByUserServices")
     * @ORM\JoinColumn(name="deactivated_by_id", referencedColumnName="id")
     */
    protected $deActivatedBy;

    /**
     * @ORM\OneToMany(targetEntity="DeactivateWithOutMacUserServiceLog", mappedBy="userService")
     */
    protected $deactiveMacUsersLog;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\CompensationUserService", mappedBy="userService")
     */
    protected $compensationUserService;

    /**
     * @ORM\OneToMany(targetEntity="CustomerCompensationLog", mappedBy="userService")
     */
    protected $compensationLogUserService;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_extended_saparately", type="boolean", nullable=false)
     */
    protected $isExtendedSaparately = false;
    
    /**
     * @ORM\OneToOne(targetEntity="Dhi\AdminBundle\Entity\FreeRechargeCard", mappedBy="userService")
     */
    protected $freeRechargeCard;
    
    /**
     * @var smallint
     *
     * @ORM\Column(name="suspended_status", type="smallint", length=1, options={"comment":"0 => Default, 1 => suspended, 2 => unSuspended"})
     */
    protected $suspendedStatus = 0;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\AdminBundle\Entity\UserSuspendHistory", mappedBy="userService")
     */
    protected $userSuspendHistory;

    public function getDisplayStatus(){

        if($this->status == 1){

            return 'Active';
        }else{
            return 'Expired';
        }    
    }
    
    

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
     * Set packageId
     *
     * @param string $packageId
     * @return UserService
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return string 
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set packageName
     *
     * @param string $packageName
     * @return UserService
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;

        return $this;
    }

    /**
     * Get packageName
     *
     * @return string 
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * Set actualAmount
     *
     * @param string $actualAmount
     * @return UserService
     */
    public function setActualAmount($actualAmount)
    {
        $this->actualAmount = $actualAmount;

        return $this;
    }

    /**
     * Get actualAmount
     *
     * @return string 
     */
    public function getActualAmount()
    {
        return $this->actualAmount;
    }

    /**
     * Set totalDiscount
     *
     * @param string $totalDiscount
     * @return UserService
     */
    public function setTotalDiscount($totalDiscount)
    {
        $this->totalDiscount = $totalDiscount;

        return $this;
    }

    /**
     * Get totalDiscount
     *
     * @return string 
     */
    public function getTotalDiscount()
    {
        return $this->totalDiscount;
    }

    /**
     * Set discountRate
     *
     * @param integer $discountRate
     * @return UserService
     */
    public function setDiscountRate($discountRate)
    {
        $this->discountRate = $discountRate;

        return $this;
    }

    /**
     * Get discountRate
     *
     * @return integer 
     */
    public function getDiscountRate()
    {
        return $this->discountRate;
    }

    /**
     * Set unusedCredit
     *
     * @param string $unusedCredit
     * @return UserService
     */
    public function setUnusedCredit($unusedCredit)
    {
        $this->unusedCredit = $unusedCredit;

        return $this;
    }

    /**
     * Get unusedCredit
     *
     * @return string 
     */
    public function getUnusedCredit()
    {
        return $this->unusedCredit;
    }

    /**
     * Set unusedDays
     *
     * @param integer $unusedDays
     * @return UserService
     */
    public function setUnusedDays($unusedDays)
    {
        $this->unusedDays = $unusedDays;

        return $this;
    }

    /**
     * Get unusedDays
     *
     * @return integer 
     */
    public function getUnusedDays()
    {
        return $this->unusedDays;
    }

    /**
     * Set payableAmount
     *
     * @param string $payableAmount
     * @return UserService
     */
    public function setPayableAmount($payableAmount)
    {
        $this->payableAmount = $payableAmount;

        return $this;
    }

    /**
     * Get payableAmount
     *
     * @return string 
     */
    public function getPayableAmount()
    {
        return $this->payableAmount;
    }

    /**
     * Set activationDate
     *
     * @param \DateTime $activationDate
     * @return UserService
     */
    public function setActivationDate($activationDate)
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    /**
     * Get activationDate
     *
     * @return \DateTime 
     */
    public function getActivationDate()
    {
        return $this->activationDate;
    }

    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return UserService
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set sentExpiredNotification
     *
     * @param boolean $sentExpiredNotification
     * @return UserService
     */
    public function setSentExpiredNotification($sentExpiredNotification)
    {
        $this->sentExpiredNotification = $sentExpiredNotification;

        return $this;
    }

    /**
     * Get sentExpiredNotification
     *
     * @return boolean 
     */
    public function getSentExpiredNotification()
    {
        return $this->sentExpiredNotification;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return UserService
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
     * Set isAddon
     *
     * @param boolean $isAddon
     * @return UserService
     */
    public function setIsAddon($isAddon)
    {
        $this->isAddon = $isAddon;

        return $this;
    }

    /**
     * Get isAddon
     *
     * @return boolean 
     */
    public function getIsAddon()
    {
        return $this->isAddon;
    }

    /**
     * Set serviceLocationIp
     *
     * @param string $serviceLocationIp
     * @return UserService
     */
    public function setServiceLocationIp($serviceLocationIp)
    {
        $this->serviceLocationIp = $serviceLocationIp;

        return $this;
    }

    /**
     * Get serviceLocationIp
     *
     * @return string 
     */
    public function getServiceLocationIp()
    {
        return $this->serviceLocationIp;
    }

    /**
     * Set bandwidth
     *
     * @param integer $bandwidth
     * @return UserService
     */
    public function setBandwidth($bandwidth)
    {
        $this->bandwidth = $bandwidth;

        return $this;
    }

    /**
     * Get bandwidth
     *
     * @return integer 
     */
    public function getBandwidth()
    {
        return $this->bandwidth;
    }

    /**
     * Set validity
     *
     * @param integer $validity
     * @return UserService
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * Get validity
     *
     * @return integer 
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserService
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
     * @return UserService
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
     * Set refund
     *
     * @param boolean $refund
     * @return UserService
     */
    public function setRefund($refund)
    {
        $this->refund = $refund;

        return $this;
    }

    /**
     * Get refund
     *
     * @return boolean 
     */
    public function getRefund()
    {
        return $this->refund;
    }

    /**
     * Set refundAmount
     *
     * @param string $refundAmount
     * @return UserService
     */
    public function setRefundAmount($refundAmount)
    {
        $this->refundAmount = $refundAmount;

        return $this;
    }

    /**
     * Get refundAmount
     *
     * @return string 
     */
    public function getRefundAmount()
    {
        return $this->refundAmount;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return UserService
     */
    public function setUser(\Dhi\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set service
     *
     * @param \Dhi\UserBundle\Entity\Service $service
     * @return UserService
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
     * Set servicePurchase
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchase
     * @return UserService
     */
    public function setServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchase = null)
    {
        $this->servicePurchase = $servicePurchase;

        return $this;
    }

    /**
     * Get servicePurchase
     *
     * @return \Dhi\ServiceBundle\Entity\ServicePurchase 
     */
    public function getServicePurchase()
    {
        return $this->servicePurchase;
    }

    /**
     * Set purchaseOrder
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return UserService
     */
    public function setPurchaseOrder(\Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder = null)
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }

    /**
     * Get purchaseOrder
     *
     * @return \Dhi\ServiceBundle\Entity\PurchaseOrder 
     */
    public function getPurchaseOrder()
    {
        return $this->purchaseOrder;
    }

    /**
     * Set finalCost
     *
     * @param string $finalCost
     * @return UserService
     */
    public function setFinalCost($finalCost)
    {
        $this->finalCost = $finalCost;

        return $this;
    }

    /**
     * Get finalCost
     *
     * @return string 
     */
    public function getFinalCost()
    {
        return $this->finalCost;
    }

    /**
     * Set refundedById
     *
     * @param \Dhi\UserBundle\Entity\User $refundedById
     * @return UserService
     */
    public function setRefundedById(\Dhi\UserBundle\Entity\User $refundedById = null)
    {
        $this->refundedById = $refundedById;

        return $this;
    }

    /**
     * Get refundedById
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getRefundedById()
    {
        return $this->refundedById;
    }

    /**
     * Get refundedBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getRefundedBy()
    {
        return $this->refundedBy;
    }

    /**
     * Set refundedAt
     *
     * @param \DateTime $refundedAt
     * @return UserService
     */
    public function setRefundedAt($refundedAt)
    {
        $this->refundedAt = $refundedAt;

        return $this;
    }

    /**
     * Get refundedAt
     *
     * @return \DateTime 
     */
    public function getRefundedAt()
    {
        return $this->refundedAt;
    }

    /**
     * Set isExtend
     *
     * @param boolean $isExtend
     * @return UserService
     */
    public function setIsExtend($isExtend)
    {
        $this->isExtend = $isExtend;

        return $this;
    }

    /**
     * Get isExtend
     *
     * @return boolean 
     */
    public function getIsExtend()
    {
        return $this->isExtend;
    }

    /**
     * Set actualValidity
     *
     * @param integer $actualValidity
     * @return UserService
     */
    public function setActualValidity($actualValidity)
    {
        $this->actualValidity = $actualValidity;

        return $this;
    }

    /**
     * Get actualValidity
     *
     * @return integer 
     */
    public function getActualValidity()
    {
        return $this->actualValidity;
    }

    /**
     * Set isPlanActive
     *
     * @param \DateTime $isPlanActive
     * @return UserService
     */
    public function setIsPlanActive($isPlanActive)
    {
        $this->isPlanActive = $isPlanActive;

        return $this;
    }

    /**
     * Get isPlanActive
     *
     * @return \DateTime 
     */
    public function getIsPlanActive()
    {
        return $this->isPlanActive;
    }

    /**
     * Set deActivatedAt
     *
     * @param \DateTime $deActivatedAt
     * @return UserService
     */
    public function setDeActivatedAt($deActivatedAt)
    {
        $this->deActivatedAt = $deActivatedAt;

        return $this;
    }

    /**
     * Get deActivatedAt
     *
     * @return \DateTime 
     */
    public function getDeActivatedAt()
    {
        return $this->deActivatedAt;
    }

    /**
     * Set isExpired
     *
     * @param boolean $isExpired
     * @return UserService
     */
    public function setIsExpired($isExpired)
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    /**
     * Get isExpired
     *
     * @return boolean 
     */
    public function getIsExpired()
    {
        return $this->isExpired;
    }

    /**
     * Set expiredAt
     *
     * @param \DateTime $expiredAt
     * @return UserService
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
     * Set expiredBy
     *
     * @param \Dhi\UserBundle\Entity\User $expiredBy
     * @return UserService
     */
    public function setExpiredBy(\Dhi\UserBundle\Entity\User $expiredBy = null)
    {
        $this->expiredBy = $expiredBy;

        return $this;
    }

    /**
     * Get expiredBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getExpiredBy()
    {
        return $this->expiredBy;
    }

    /**
     * Set deActivatedBy
     *
     * @param \Dhi\AdminBundle\Entity\ServicePartner $deActivatedBy
     * @return UserService
     */
    public function setDeActivatedBy(\Dhi\AdminBundle\Entity\ServicePartner $deActivatedBy = null)
    {
        $this->deActivatedBy = $deActivatedBy;

        return $this;
    }

    /**
     * Get deActivatedBy
     *
     * @return \Dhi\AdminBundle\Entity\ServicePartner 
     */
    public function getDeActivatedBy()
    {
        return $this->deActivatedBy;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->deactiveMacUsersLog = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add deactiveMacUsersLog
     *
     * @param \Dhi\UserBundle\Entity\DeactivateWithOutMacUserServiceLog $deactiveMacUsersLog
     * @return UserService
     */
    public function addDeactiveMacUsersLog(\Dhi\UserBundle\Entity\DeactivateWithOutMacUserServiceLog $deactiveMacUsersLog)
    {
        $this->deactiveMacUsersLog[] = $deactiveMacUsersLog;

        return $this;
    }

    /**
     * Remove deactiveMacUsersLog
     *
     * @param \Dhi\UserBundle\Entity\DeactivateWithOutMacUserServiceLog $deactiveMacUsersLog
     */
    public function removeDeactiveMacUsersLog(\Dhi\UserBundle\Entity\DeactivateWithOutMacUserServiceLog $deactiveMacUsersLog)
    {
        $this->deactiveMacUsersLog->removeElement($deactiveMacUsersLog);
    }

    /**
     * Get deactiveMacUsersLog
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDeactiveMacUsersLog()
    {
        return $this->deactiveMacUsersLog;
    }

    /**
     * Add compensationUserService
     *
     * @param \Dhi\UserBundle\Entity\CompensationUserService $compensationUserService
     * @return UserService
     */
    public function addCompensationUserService(\Dhi\UserBundle\Entity\CompensationUserService $compensationUserService)
    {
        $this->compensationUserService[] = $compensationUserService;

        return $this;
    }

    /**
     * Remove compensationUserService
     *
     * @param \Dhi\UserBundle\Entity\CompensationUserService $compensationUserService
     */
    public function removeCompensationUserService(\Dhi\UserBundle\Entity\CompensationUserService $compensationUserService)
    {
        $this->compensationUserService->removeElement($compensationUserService);
    }

    /**
     * Get compensationUserService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCompensationUserService()
    {
        return $this->compensationUserService;
    }
   

    /**
     * Set isExtendedSaparately
     *
     * @param boolean $isExtendedSaparately
     * @return UserService
     */
    public function setIsExtendedSaparately($isExtendedSaparately)
    {
        $this->isExtendedSaparately = $isExtendedSaparately;

        return $this;
    }

    /**
     * Get isExtendedSaparately
     *
     * @return boolean 
     */
    public function getIsExtendedSaparately()
    {
        return $this->isExtendedSaparately;
    }

    /**
     * Add compensationLogUserService
     *
     * @param \Dhi\UserBundle\Entity\CustomerCompensationLog $compensationLogUserService
     * @return UserService
     */
    public function addCompensationLogUserService(\Dhi\UserBundle\Entity\CustomerCompensationLog $compensationLogUserService)
    {
        $this->compensationLogUserService[] = $compensationLogUserService;

        return $this;
    }

    /**
     * Remove compensationLogUserService
     *
     * @param \Dhi\UserBundle\Entity\CustomerCompensationLog $compensationLogUserService
     */
    public function removeCompensationLogUserService(\Dhi\UserBundle\Entity\CustomerCompensationLog $compensationLogUserService)
    {
        $this->compensationLogUserService->removeElement($compensationLogUserService);
    }

    /**
     * Get compensationLogUserService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCompensationLogUserService()
    {
        return $this->compensationLogUserService;
    }

    /**
     * Set freeRechargeCard
     *
     * @param \Dhi\AdminBundle\Entity\FreeRechargeCard $freeRechargeCard
     * @return UserService
     */
    public function setFreeRechargeCard(\Dhi\AdminBundle\Entity\FreeRechargeCard $freeRechargeCard = null)
    {
        $this->freeRechargeCard = $freeRechargeCard;

        return $this;
    }

    /**
     * Get freeRechargeCard
     *
     * @return \Dhi\AdminBundle\Entity\FreeRechargeCard 
     */
    public function getFreeRechargeCard()
    {
        return $this->freeRechargeCard;
    }

    /**
     * Set refundAfterExpired
     *
     * @param boolean $refundAfterExpired
     * @return UserService
     */
    public function setRefundAfterExpired($refundAfterExpired)
    {
        $this->refundAfterExpired = $refundAfterExpired;

        return $this;
    }

    /**
     * Get refundAfterExpired
     *
     * @return boolean 
     */
    public function getRefundAfterExpired()
    {
        return $this->refundAfterExpired;
    }

    /**
     * Set refundAfterExpiredAmount
     *
     * @param string $refundAfterExpiredAmount
     * @return UserService
     */
    public function setRefundAfterExpiredAmount($refundAfterExpiredAmount)
    {
        $this->refundAfterExpiredAmount = $refundAfterExpiredAmount;

        return $this;
    }

    /**
     * Get refundAfterExpiredAmount
     *
     * @return string 
     */
    public function getRefundAfterExpiredAmount()
    {
        return $this->refundAfterExpiredAmount;
    }

    /**
     * Set refundAfterExpiredBy
     *
     * @param \Dhi\UserBundle\Entity\User $refundAfterExpiredBy
     * @return UserService
     */
    public function setRefundAfterExpiredBy(\Dhi\UserBundle\Entity\User $refundAfterExpiredBy = null)
    {
        $this->refundAfterExpiredBy = $refundAfterExpiredBy;

        return $this;
    }

    /**
     * Get refundAfterExpiredBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getRefundAfterExpiredBy()
    {
        return $this->refundAfterExpiredBy;
    }

    /**
     * Set refundAfterExpiredAt
     *
     * @param \DateTime $refundAfterExpiredAt
     * @return UserService
     */
    public function setRefundAfterExpiredAt($refundAfterExpiredAt)
    {
        $this->refundAfterExpiredAt = $refundAfterExpiredAt;

        return $this;
    }

    /**
     * Get refundAfterExpiredAt
     *
     * @return \DateTime 
     */
    public function getRefundAfterExpiredAt()
    {
        return $this->refundAfterExpiredAt;
    }

    /**
     * Set refundedBy
     *
     * @param \Dhi\UserBundle\Entity\User $refundedBy
     * @return UserService
     */
    public function setRefundedBy(\Dhi\UserBundle\Entity\User $refundedBy = null)
    {
        $this->refundedBy = $refundedBy;

        return $this;
    }

    /**
     * Set suspendedStatus
     *
     * @param integer $suspendedStatus
     * @return UserService
     */
    public function setSuspendedStatus($suspendedStatus)
    {
        $this->suspendedStatus = $suspendedStatus;

        return $this;
    }

    /**
     * Get suspendedStatus
     *
     * @return integer 
     */
    public function getSuspendedStatus()
    {
        return $this->suspendedStatus;
    }

    /**
     * Add userSuspendHistory
     *
     * @param \Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory
     * @return UserService
     */
    public function addUserSuspendHistory(\Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory)
    {
        $this->userSuspendHistory[] = $userSuspendHistory;

        return $this;
    }

    /**
     * Remove userSuspendHistory
     *
     * @param \Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory
     */
    public function removeUserSuspendHistory(\Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory)
    {
        $this->userSuspendHistory->removeElement($userSuspendHistory);
    }

    /**
     * Get userSuspendHistory
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserSuspendHistory()
    {
        return $this->userSuspendHistory;
    }
}
