<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\Service;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="service_purchase",indexes={
        @Index(name="service_purchase_payment_status_idx", columns={"payment_status"}),
        @Index(name="service_purchase_is_addon_idx", columns={"is_addon"}),
        @Index(name="service_purchase_package_id_idx", columns={"package_id"}),
        @Index(name="service_purchase_purchase_type_idx", columns={"purchase_type"})
    })
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\ServicePurchaseRepository")
 */

class ServicePurchase {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="PurchaseOrder", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="purchase_order_id", referencedColumnName="id")
     */
    protected $purchaseOrder;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\Credit", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="credit_id", referencedColumnName="id")
     */
    protected $credit;

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
     * @ORM\Column(name="payment_status", type="string", columnDefinition="ENUM('New', 'Completed', 'NeedToRefund', 'Refunded', 'Failed','Voided', 'Expired', 'Refunded After Expired')", options={"default":"New", "comment":"New, Completed, NeedToRefund, Refunded, Failed, Expired, Refunded After Expired"})
     */
    protected $paymentStatus = 'New';

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
     * @var integer
     *
     * @ORM\Column(name="discount_rate", type="integer", nullable=true)
     */
    protected $discountRate;

	/**
     * @var decimal
     *
     * @ORM\Column(name="promo_discount", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $promoDiscount;

   

    /**
     * @var decimal
     *
     * @ORM\Column(name="payable_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $payableAmount;

    /**
     * @var decimal
     *
     * @ORM\Column(name="final_cost", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $finalCost;

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
     * @var string
     *
     * @ORM\Column(name="session_id", type="string", length=255)
     */
    protected $sessionId;

    /**
     * @var smallint
     *
     * @ORM\Column(name="recharge_status", type="smallint", length=1, options={"comment":"0 => New, 1 => Success, 2 => Failed"})
     */
    protected $rechargeStatus = 0;

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
     * @var boolean
     *
     * @ORM\Column(name="is_upgrade", type="boolean", nullable=false)
     */
    protected $isUpgrade = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="terms_use", type="boolean", nullable=false)
     */
    protected $termsUse = false;

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
     *
     * @ORM\ManyToOne(targetEntity="PaymentMethod", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $paymentMethod;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_addon", type="boolean", nullable=false)
     */
    protected $isAddon = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_credit", type="boolean", nullable=false)
     */
    protected $isCredit = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_compensation", type="boolean", nullable=false)
     */
    protected $isCompensation = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="purchase_type", type="string", length=20, nullable=true)
     */
    protected $purchase_type;

    /**
     * @ORM\Column(name="bundle_id", type="integer", nullable=true)
     */
    protected $bundle_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="bundle_name", type="string", length=20, nullable=true)
     */
    protected $bundle_name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="display_bundle_name", type="text", nullable=true)
     */
    protected $display_bundle_name;

    /**
     * @var decimal
     *
     * @ORM\Column(name="bundle_discount", type="decimal", precision= 10, scale= 2, nullable=true, options={"comment":"In Percentage"})
     */
    protected $bundleDiscount;

    /**
     * @var decimal
     *
     * @ORM\Column(name="display_bundle_discount", type="decimal", precision= 10, scale= 2, nullable=true, options={"comment":"In Amount"})
     */
    protected $displayBundleDiscount;

    /**
     * @ORM\OneToOne(targetEntity="Dhi\UserBundle\Entity\UserService" , mappedBy="servicePurchase")
     */
    protected $userService;

    /**
     * @var smallint
     *
     * @ORM\Column(name="bundle_applied", type="smallint", length=1, options={"default":"0"})
     */
    protected $bundleApplied = 0;

    /**
     * @var decimal
     *
     * @ORM\Column(name="discount_code__discount", type="decimal", precision= 10, scale= 2, nullable=true, options={"comment":"In Percentage"})
     */
    protected $DiscountCodeRate;

    /**
     * @var decimal
     *
     * @ORM\Column(name="discount_code__amount", type="decimal", precision= 10, scale= 2, nullable=true, options={"comment":"Amount"})
     */
    protected $DiscountCodeAmount;

	/**
     * @var smallint
     *
     * @ORM\Column(name="promo_code_applied", type="smallint", length=1, options={"default":"0", "comment":"1 = Customer promo code, 2 = Partner promo code"})
     */
    protected $promoCodeApplied = 0;

    /**
     * @var smallint
     *
     * @ORM\Column(name="discount_code_applied", type="smallint", length=1, options={"default":"0", "comment":"1 = Discount code, 2 = Partner code, 6 = Admin discount, 7 = In-App Discount"})
     */
    protected $DiscountCodeApplied = 0;

   /**
     * @ORM\Column(name="validity_type", type="string", columnDefinition="ENUM('HOURS', 'DAYS')", options={"default":"DAYS", "comment":"HOURS, DAYS"})
     */
    protected $validityType = 'DAYS';
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\BusinessPromoCodes", inversedBy="discountServicePurchases")
     * @ORM\JoinColumn(name="business_promo_id", referencedColumnName="id")
     */
    protected $discountedBusinessPromocode;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\PartnerPromoCodes", inversedBy="discountServicePurchases")
     * @ORM\JoinColumn(name="partner_promo_id", referencedColumnName="id")
     */
    protected $discountedPartnerPromocode;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\EmployeePromoCode", inversedBy="discountServicePurchases")
     * @ORM\JoinColumn(name="employee_promo_id", referencedColumnName="id")
     */
    protected $discountedEmployeePromocode;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\Promotion", inversedBy="discounts")
     * @ORM\JoinColumn(name="promotion_id", referencedColumnName="id")
     */
    protected $promotion;

    /**
     * @var decimal
     *
     * @ORM\Column(name="promotion_discount_per", type="decimal", precision= 10, scale= 2, nullable=true, options={"comment":"Percentage, If promotion discount applied"})
     */
    protected $promotionDiscountPer = 0;

    /**
     * @var decimal
     *
     * @ORM\Column(name="promotion_discount_amount", type="decimal", precision= 10, scale= 2, nullable=true, options={"comment":"Amount, If promotion discount applied"})
     */
    protected $promotionDiscountAmount = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_extend", type="boolean", nullable=false)
     */
    protected $isExtend = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_applied_by_admin", type="boolean", nullable=false)
     */
    protected $isAppliedByAdmin = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="paypal_credential", type="string", length=50, nullable=true)
     */
    protected $paypalCredential;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation", inversedBy="serviceLocaton")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected  $service_location_id;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\DeactivateWithOutMacUserServiceLog", mappedBy="servicePurchase")
     */
    protected $deactiveMacUsersLog;
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $whiteLabel;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_promotional_plan", type="boolean", nullable=false)
     */
    protected $isPromotionalPlan = false;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\ReferralPromoCode", inversedBy="referralCode")
     * @ORM\JoinColumn(name="referral_promo_code_id", referencedColumnName="id")
     */
    protected  $discountedReferralPromoCode;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_chase_deers", type="boolean", nullable=true, options={"comment":"0 => Chase, 1 => Chase Deers"})
     */
    protected $isChaseDeers;

    public function getActivationStatus(){

        if($this->rechargeStatus == 1){

            return 'Success';
        }

        if($this->rechargeStatus == 2){

            return 'Failed';
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
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set paymentStatus
     *
     * @param string $paymentStatus
     * @return ServicePurchase
     */
    public function setPaymentStatus($paymentStatus)
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * Get paymentStatus
     *
     * @return string
     */
    public function getPaymentStatus()
    {
        return $this->paymentStatus;
    }

    /**
     * Set actualAmount
     *
     * @param string $actualAmount
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set payableAmount
     *
     * @param string $payableAmount
     * @return ServicePurchase
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
     * Set unusedCredit
     *
     * @param string $unusedCredit
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set sessionId
     *
     * @param string $sessionId
     * @return ServicePurchase
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set rechargeStatus
     *
     * @param integer $rechargeStatus
     * @return ServicePurchase
     */
    public function setRechargeStatus($rechargeStatus)
    {
        $this->rechargeStatus = $rechargeStatus;

        return $this;
    }

    /**
     * Get rechargeStatus
     *
     * @return integer
     */
    public function getRechargeStatus()
    {
        return $this->rechargeStatus;
    }

    /**
     * Set bandwidth
     *
     * @param integer $bandwidth
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set isUpgrade
     *
     * @param boolean $isUpgrade
     * @return ServicePurchase
     */
    public function setIsUpgrade($isUpgrade)
    {
        $this->isUpgrade = $isUpgrade;

        return $this;
    }

    /**
     * Get isUpgrade
     *
     * @return boolean
     */
    public function getIsUpgrade()
    {
        return $this->isUpgrade;
    }

    /**
     * Set termsUse
     *
     * @param boolean $termsUse
     * @return ServicePurchase
     */
    public function setTermsUse($termsUse)
    {
        $this->termsUse = $termsUse;

        return $this;
    }

    /**
     * Get termsUse
     *
     * @return boolean
     */
    public function getTermsUse()
    {
        return $this->termsUse;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ServicePurchase
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
     * @return ServicePurchase
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
     * Set isAddon
     *
     * @param boolean $isAddon
     * @return ServicePurchase
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
     * Set isCredit
     *
     * @param boolean $isCredit
     * @return ServicePurchase
     */
    public function setIsCredit($isCredit)
    {
        $this->isCredit = $isCredit;

        return $this;
    }

    /**
     * Get isCredit
     *
     * @return boolean
     */
    public function getIsCredit()
    {
        return $this->isCredit;
    }

    /**
     * Set isCompensation
     *
     * @param boolean $isCompensation
     * @return ServicePurchase
     */
    public function setIsCompensation($isCompensation)
    {
        $this->isCompensation = $isCompensation;

        return $this;
    }

    /**
     * Get isCompensation
     *
     * @return boolean
     */
    public function getIsCompensation()
    {
        return $this->isCompensation;
    }

    /**
     * Set service
     *
     * @param \Dhi\UserBundle\Entity\Service $service
     * @return ServicePurchase
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return ServicePurchase
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
     * Set purchaseOrder
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return ServicePurchase
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
     * Set credit
     *
     * @param \Dhi\AdminBundle\Entity\Credit $credit
     * @return ServicePurchase
     */
    public function setCredit(\Dhi\AdminBundle\Entity\Credit $credit = null)
    {
        $this->credit = $credit;

        return $this;
    }

    /**
     * Get credit
     *
     * @return \Dhi\AdminBundle\Entity\Credit
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * Set paymentMethod
     *
     * @param \Dhi\ServiceBundle\Entity\PaymentMethod $paymentMethod
     * @return ServicePurchase
     */
    public function setPaymentMethod(\Dhi\ServiceBundle\Entity\PaymentMethod $paymentMethod = null)
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    /**
     * Get paymentMethod
     *
     * @return \Dhi\ServiceBundle\Entity\PaymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    /**
     * Set userService
     *
     * @param \Dhi\UserBundle\Entity\UserService $userService
     * @return ServicePurchase
     */
    public function setUserService(\Dhi\UserBundle\Entity\UserService $userService = null)
    {
        $this->userService = $userService;

        return $this;
    }

    /**
     * Get userService
     *
     * @return \Dhi\UserBundle\Entity\UserService
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * Set finalCost
     *
     * @param string $finalCost
     * @return ServicePurchase
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
     * Set promoDiscount
     *
     * @param string $promoDiscount
     * @return ServicePurchase
     */
    public function setPromoDiscount($promoDiscount)
    {
        $this->promoDiscount = $promoDiscount;

        return $this;
    }

    /**
     * Get promoDiscount
     *
     * @return string
     */
    public function getPromoDiscount()
    {
        return $this->promoDiscount;
    }

    
    /**
     * Set purchase_type
     *
     * @param string $purchaseType
     * @return ServicePurchase
     */
    public function setPurchaseType($purchaseType)
    {
        $this->purchase_type = $purchaseType;

        return $this;
    }

    /**
     * Get purchase_type
     *
     * @return string
     */
    public function getPurchaseType()
    {
        return $this->purchase_type;
    }

    /**
     * Set bundle_id
     *
     * @param integer $bundleId
     * @return ServicePurchase
     */
    public function setBundleId($bundleId)
    {
        $this->bundle_id = $bundleId;

        return $this;
    }

    /**
     * Get bundle_id
     *
     * @return integer
     */
    public function getBundleId()
    {
        return $this->bundle_id;
    }

    /**
     * Set bundleDiscount
     *
     * @param string $bundleDiscount
     * @return ServicePurchase
     */
    public function setBundleDiscount($bundleDiscount)
    {
        $this->bundleDiscount = $bundleDiscount;

        return $this;
    }

    /**
     * Get bundleDiscount
     *
     * @return string
     */
    public function getBundleDiscount()
    {
        return $this->bundleDiscount;
    }

    /**
     * Set bundle_name
     *
     * @param string $bundleName
     * @return ServicePurchase
     */
    public function setBundleName($bundleName)
    {
        $this->bundle_name = $bundleName;

        return $this;
    }

    /**
     * Get bundle_name
     *
     * @return string
     */
    public function getBundleName()
    {
        return $this->bundle_name;
    }

    /**
     * Set bundleApplied
     *
     * @param integer $bundleApplied
     * @return ServicePurchase
     */
    public function setBundleApplied($bundleApplied)
    {
        $this->bundleApplied = $bundleApplied;

        return $this;
    }

    /**
     * Get bundleApplied
     *
     * @return integer
     */
    public function getBundleApplied()
    {
        return $this->bundleApplied;
    }

    /**
     * Set display_bundle_name
     *
     * @param string $displayBundleName
     * @return ServicePurchase
     */
    public function setDisplayBundleName($displayBundleName)
    {
        $this->display_bundle_name = $displayBundleName;

        return $this;
    }

    /**
     * Get display_bundle_name
     *
     * @return string
     */
    public function getDisplayBundleName()
    {
        return $this->display_bundle_name;
    }

    /**
     * Set DiscountCodeRate
     *
     * @param string $discountCodeRate
     * @return ServicePurchase
     */
    public function setDiscountCodeRate($discountCodeRate)
    {
        $this->DiscountCodeRate = $discountCodeRate;

        return $this;
    }

    /**
     * Get DiscountCodeRate
     *
     * @return string 
     */
    public function getDiscountCodeRate()
    {
        return $this->DiscountCodeRate;
    }

    /**
     * Set DiscountCodeApplied
     *
     * @param integer $discountCodeApplied
     * @return ServicePurchase
     */
    public function setDiscountCodeApplied($discountCodeApplied)
    {
        $this->DiscountCodeApplied = $discountCodeApplied;

        return $this;
    }

    /**
     * Get DiscountCodeApplied
     *
     * @return integer 
     */
    public function getDiscountCodeApplied()
    {
        return $this->DiscountCodeApplied;
    }

    /**
     * Set promoCodeApplied
     *
     * @param integer $promoCodeApplied
     * @return ServicePurchase
     */
    public function setPromoCodeApplied($promoCodeApplied)
    {
        $this->promoCodeApplied = $promoCodeApplied;

        return $this;
    }

    /**
     * Get promoCodeApplied
     *
     * @return integer 
     */
    public function getPromoCodeApplied()
    {
        return $this->promoCodeApplied;
    }

    /**
     * Set isExtend
     *
     * @param boolean $isExtend
     * @return ServicePurchase
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
     * Set paypalCredential
     *
     * @param string $paypalCredential
     * @return ServicePurchase
     */
    public function setPaypalCredential($paypalCredential)
    {
        $this->paypalCredential = $paypalCredential;

        return $this;
    }

    /**
     * Get paypalCredential
     *
     * @return string 
     */
    public function getPaypalCredential()
    {
        return $this->paypalCredential;
    }    

    /**
     * Set discountedBusinessPromocode
     *
     * @param \Dhi\AdminBundle\Entity\BusinessPromoCodes $discountedBusinessPromocode
     * @return ServicePurchase
     */
    public function setDiscountedBusinessPromocode(\Dhi\AdminBundle\Entity\BusinessPromoCodes $discountedBusinessPromocode = null)
    {
        $this->discountedBusinessPromocode = $discountedBusinessPromocode;

        return $this;
    }

    /**
     * Get discountedBusinessPromocode
     *
     * @return \Dhi\AdminBundle\Entity\BusinessPromoCodes 
     */
    public function getDiscountedBusinessPromocode()
    {
        return $this->discountedBusinessPromocode;
    }

    /**
     * Set DiscountCodeAmount
     *
     * @param string $discountCodeAmount
     * @return ServicePurchase
     */
    public function setDiscountCodeAmount($discountCodeAmount)
    {
        $this->DiscountCodeAmount = $discountCodeAmount;

        return $this;
    }

    /**
     * Get DiscountCodeAmount
     *
     * @return string 
     */
    public function getDiscountCodeAmount()
    {
        return $this->DiscountCodeAmount;
    }

    /**
     * Set discountedPartnerPromocode
     *
     * @param \Dhi\AdminBundle\Entity\PartnerPromoCodes $discountedPartnerPromocode
     * @return ServicePurchase
     */
    public function setDiscountedPartnerPromocode(\Dhi\AdminBundle\Entity\PartnerPromoCodes $discountedPartnerPromocode = null)
    {
        $this->discountedPartnerPromocode = $discountedPartnerPromocode;

        return $this;
    }

    /**
     * Get discountedPartnerPromocode
     *
     * @return \Dhi\AdminBundle\Entity\PartnerPromoCodes 
     */
    public function getDiscountedPartnerPromocode()
    {
        return $this->discountedPartnerPromocode;
    }

    /**
     * Set service_location_id
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocationId
     * @return ServicePurchase
     */
    public function setServiceLocationId(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocationId = null)
    {
        $this->service_location_id = $serviceLocationId;

        return $this;
    }

    /**
     * Get service_location_id
     *
     * @return \Dhi\AdminBundle\Entity\ServiceLocation 
     */
    public function getServiceLocationId()
    {
        return $this->service_location_id;
    }

    /**
     * Set discountedEmployeePromocode
     *
     * @param \Dhi\AdminBundle\Entity\EmployeePromoCode $discountedEmployeePromocode
     * @return ServicePurchase
     */
    public function setDiscountedEmployeePromocode(\Dhi\AdminBundle\Entity\EmployeePromoCode $discountedEmployeePromocode = null)
    {
        $this->discountedEmployeePromocode = $discountedEmployeePromocode;

        return $this;
    }

    /**
     * Get discountedEmployeePromocode
     *
     * @return \Dhi\AdminBundle\Entity\EmployeePromoCode 
     */
    public function getDiscountedEmployeePromocode()
    {
        return $this->discountedEmployeePromocode;
    }

    /**
     * Set discountedReferralPromoCode
     *
     * @param \Dhi\UserBundle\Entity\ReferralPromoCode $discountedReferralPromoCode
     * @return ServicePurchase
     */
    public function setDiscountedReferralPromoCode(\Dhi\UserBundle\Entity\ReferralPromoCode $discountedReferralPromoCode = null)
    {
        $this->discountedReferralPromoCode = $discountedReferralPromoCode;

        return $this;
    }

    /**
     * Get discountedReferralPromoCode
     *
     * @return \Dhi\UserBundle\Entity\ReferralPromoCode 
     */
    public function getDiscountedReferralPromoCode()
    {
        return $this->discountedReferralPromoCode;
    }

    /**
     * Set displayBundleDiscount
     *
     * @param string $displayBundleDiscount
     * @return ServicePurchase
     */
    public function setDisplayBundleDiscount($displayBundleDiscount)
    {
        $this->displayBundleDiscount = $displayBundleDiscount;

        return $this;
    }

    /**
     * Get displayBundleDiscount
     *
     * @return string 
     */
    public function getDisplayBundleDiscount()
    {
        return $this->displayBundleDiscount;
    }

    /**
     * Set validityType
     *
     * @param string $validityType
     * @return ServicePurchase
     */
    public function setValidityType($validityType)
    {
        $this->validityType = $validityType;

        return $this;
    }

    /**
     * Get validityType
     *
     * @return string 
     */
    public function getValidityType()
    {
        return $this->validityType;
    }    

    /**
     * Set isAppliedByAdmin
     *
     * @param boolean $isAppliedByAdmin
     * @return ServicePurchase
     */
    public function setIsAppliedByAdmin($isAppliedByAdmin)
    {
        $this->isAppliedByAdmin = $isAppliedByAdmin;

        return $this;
    }

    /**
     * Get isAppliedByAdmin
     *
     * @return boolean 
     */
    public function getIsAppliedByAdmin()
    {
        return $this->isAppliedByAdmin;
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
     * @return ServicePurchase
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
     * Set promotion
     *
     * @param \Dhi\AdminBundle\Entity\Promotion $promotion
     * @return ServicePurchase
     */
    public function setPromotion(\Dhi\AdminBundle\Entity\Promotion $promotion = null)
    {
        $this->promotion = $promotion;

        return $this;
    }

    /**
     * Get promotion
     *
     * @return \Dhi\AdminBundle\Entity\Promotion 
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * Set promotionDiscountAmount
     *
     * @param string $promotionDiscountAmount
     * @return ServicePurchase
     */
    public function setPromotionDiscountAmount($promotionDiscountAmount)
    {
        $this->promotionDiscountAmount = $promotionDiscountAmount;

        return $this;
    }

    /**
     * Get promotionDiscountAmount
     *
     * @return string 
     */
    public function getPromotionDiscountAmount()
    {
        return $this->promotionDiscountAmount;
    }

    /**
     * Set promotionDiscountPer
     *
     * @param string $promotionDiscountPer
     * @return ServicePurchase
     */
    public function setPromotionDiscountPer($promotionDiscountPer)
    {
        $this->promotionDiscountPer = $promotionDiscountPer;

        return $this;
    }

    /**
     * Get promotionDiscountPer
     *
     * @return string 
     */
    public function getPromotionDiscountPer()
    {
        return $this->promotionDiscountPer;
    }

    /**
     * Set isChaseDeers
     *
     * @param boolean $isChaseDeers
     * @return ServicePurchase
     */
    public function setIsChaseDeers($isChaseDeers)
    {
        $this->isChaseDeers = $isChaseDeers;

        return $this;
    }

    /**
     * Get isChaseDeers
     *
     * @return boolean 
     */
    public function getIsChaseDeers()
    {
        return $this->isChaseDeers;
    }

    /**
     * Set whiteLabel
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel
     * @return ServicePurchase
     */
    public function setWhiteLabel(\Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel = null)
    {
        $this->whiteLabel = $whiteLabel;

        return $this;
    }

    /**
     * Get whiteLabel
     *
     * @return \Dhi\AdminBundle\Entity\WhiteLabel 
     */
    public function getWhiteLabel()
    {
        return $this->whiteLabel;
    }

    /**
     * Set isPromotionalPlan
     *
     * @param boolean $isPromotionalPlan
     * @return ServicePurchase
     */
    public function setIsPromotionalPlan($isPromotionalPlan)
    {
        $this->isPromotionalPlan = $isPromotionalPlan;

        return $this;
    }

    /**
     * Get isPromotionalPlan
     *
     * @return boolean 
     */
    public function getIsPromotionalPlan()
    {
        return $this->isPromotionalPlan;
    }
}
