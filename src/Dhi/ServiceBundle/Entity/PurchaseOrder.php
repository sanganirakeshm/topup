<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\Service;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="purchase_order",indexes={
        @Index(name="purchase_order_payment_status_idx", columns={"payment_status"}),
        @Index(name="purchase_order_order_number_idx", columns={"order_number"})
    })
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\PurchaseOrderRepository")
 */

class PurchaseOrder {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="purchaseOrders")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="order_number", length=255)
     */
    protected $orderNumber;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="PaymentMethod", inversedBy="purchaseOrders")
     * @ORM\JoinColumn(name="payment_method_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $paymentMethod;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="session_id", length=255)
     */
    protected $sessionId;
        
    /**
     * @var decimal
     *
     * @ORM\Column(name="total_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $totalAmount;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="refund_amount", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $refundAmount;

    /**
     * @var decimal
     *
     * @ORM\Column(name="refund_after_expired_amount", type="decimal", precision= 10, scale= 2, nullable=true)
     */
    protected $refundAfterExpiredAmount;

    /**
     * @ORM\OneToMany(targetEntity="ServicePurchase", mappedBy="purchaseOrder")
     */
    protected $servicePurchases;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\CompensationUserService", mappedBy="purchaseOrder")
     */
    protected $compensationUserService;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\UserService", mappedBy="purchaseOrder")
     */
    protected $userService;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\UserCreditLog", mappedBy="purchaseOrder")
     */
    protected $userCreditLogs;
        
    /**
     * @ORM\OneToOne(targetEntity="Milstar", inversedBy="purchaseOrder")
     * @ORM\JoinColumn(name="milstar_id", referencedColumnName="id")
     */
    protected $milstar;

    /**
     * @ORM\OneToOne(targetEntity="ChaseCheckout", inversedBy="purchaseOrder")
     * @ORM\JoinColumn(name="chase_id", referencedColumnName="id")
     */
    protected $chase;

    /**
     * @ORM\OneToOne(targetEntity="PaypalCheckout", inversedBy="purchaseOrder")
     * @ORM\JoinColumn(name="paypal_checkout_id", referencedColumnName="id")
     */
    protected $paypalCheckout;
    
    /**
     * @ORM\OneToOne(targetEntity="PaypalRecurringProfile", inversedBy="purchaseOrder")
     * @ORM\JoinColumn(name="paypal_recurring_profile_id", referencedColumnName="id")
     */
    protected $paypalRecurringProfile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="paypal_token", type="string", length=255, nullable=true)
     */
    protected $paypalToken;
    
    /**
     * @ORM\Column(name="payment_status", type="string", columnDefinition="ENUM('InProcess', 'Completed', 'PartiallyCompleted', 'Refunded', 'Failed','Voided', 'Expired')", options={"default":"InProcess", "comment":"InProcess, Completed, PartiallyCompleted, Refunded, Failed, Expired"})
     */
    protected $paymentStatus = 'InProcess';
    
    
    /**
     * @ORM\Column(name="payment_by", type="string", columnDefinition="ENUM('User', 'Admin')", options={"default":"User", "comment":"User, Admin"})
     */
    protected $paymentBy = 'User';
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="userPurchased")
     * @ORM\JoinColumn(name="payment_by_id", referencedColumnName="id")
     */
    protected $paymentByUser;
    
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
     * @var integer
     *
     * @ORM\Column(type="integer", name="compensation_validity", length=11, nullable=true)
     */
    protected $compensationValidity;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="eagle_cash_no", length=20, nullable=true)
     */
    protected $eagleCashNo;
    
    /**
     * @var string
     *
     * @ORM\Column(type="string", name="cac_card_no", length=20, nullable=true)
     */
    protected $CacCardNo;
    
    /**
     * @ORM\Column(name="ip_address", type="string", length=15, nullable=true)
     */
    protected $ipAddress;
    
    /**
     * @ORM\Column(name="purchase_email_sent", type="boolean", nullable=false, options={"default":false})
     */
    protected $purchaseEmailSent = false;
    
    /**
     * @ORM\Column(name="recurring_status", type="smallint", length=1, options={"comment":"1 => Success, 2 => Failed, 3 => New"})
     */
    protected $recurringStatus = 0;
       
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ChaseMerchantIds", inversedBy="purchaseOrders")
     * @ORM\JoinColumn(name="chase_merchant_id", referencedColumnName="id")
     */
    protected $chaseMerchantId;
    
    /**
     * @ORM\Column(name="is_default_chase_mid", type="boolean", nullable=false, options={"default":false})
     */
    protected $isDefaultChaseMid = false;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->servicePurchases = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userService = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userCreditLogs = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set orderNumber
     *
     * @param string $orderNumber
     * @return PurchaseOrder
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * Get orderNumber
     *
     * @return string 
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }

    /**
     * Set sessionId
     *
     * @param string $sessionId
     * @return PurchaseOrder
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
     * Set totalAmount
     *
     * @param string $totalAmount
     * @return PurchaseOrder
     */
    public function setTotalAmount($totalAmount)
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    /**
     * Get totalAmount
     *
     * @return string 
     */
    public function getTotalAmount()
    {
        return $this->totalAmount;
    }

    /**
     * Set refundAmount
     *
     * @param string $refundAmount
     * @return PurchaseOrder
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
     * Set paypalToken
     *
     * @param string $paypalToken
     * @return PurchaseOrder
     */
    public function setPaypalToken($paypalToken)
    {
        $this->paypalToken = $paypalToken;

        return $this;
    }

    /**
     * Get paypalToken
     *
     * @return string 
     */
    public function getPaypalToken()
    {
        return $this->paypalToken;
    }

    /**
     * Set paymentStatus
     *
     * @param string $paymentStatus
     * @return PurchaseOrder
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
     * Set paymentBy
     *
     * @param string $paymentBy
     * @return PurchaseOrder
     */
    public function setPaymentBy($paymentBy)
    {
        $this->paymentBy = $paymentBy;

        return $this;
    }

    /**
     * Get paymentBy
     *
     * @return string 
     */
    public function getPaymentBy()
    {
        return $this->paymentBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PurchaseOrder
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
     * @return PurchaseOrder
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
     * Set compensationValidity
     *
     * @param integer $compensationValidity
     * @return PurchaseOrder
     */
    public function setCompensationValidity($compensationValidity)
    {
        $this->compensationValidity = $compensationValidity;

        return $this;
    }

    /**
     * Get compensationValidity
     *
     * @return integer 
     */
    public function getCompensationValidity()
    {
        return $this->compensationValidity;
    }

    /**
     * Set eagleCashNo
     *
     * @param string $eagleCashNo
     * @return PurchaseOrder
     */
    public function setEagleCashNo($eagleCashNo)
    {
        $this->eagleCashNo = $eagleCashNo;

        return $this;
    }

    /**
     * Get eagleCashNo
     *
     * @return string 
     */
    public function getEagleCashNo()
    {
        return $this->eagleCashNo;
    }

    /**
     * Set CacCardNo
     *
     * @param string $cacCardNo
     * @return PurchaseOrder
     */
    public function setCacCardNo($cacCardNo)
    {
        $this->CacCardNo = $cacCardNo;

        return $this;
    }

    /**
     * Get CacCardNo
     *
     * @return string 
     */
    public function getCacCardNo()
    {
        return $this->CacCardNo;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return PurchaseOrder
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set purchaseEmailSent
     *
     * @param boolean $purchaseEmailSent
     * @return PurchaseOrder
     */
    public function setPurchaseEmailSent($purchaseEmailSent)
    {
        $this->purchaseEmailSent = $purchaseEmailSent;

        return $this;
    }

    /**
     * Get purchaseEmailSent
     *
     * @return boolean 
     */
    public function getPurchaseEmailSent()
    {
        return $this->purchaseEmailSent;
    }

    /**
     * Set recurringStatus
     *
     * @param integer $recurringStatus
     * @return PurchaseOrder
     */
    public function setRecurringStatus($recurringStatus)
    {
        $this->recurringStatus = $recurringStatus;

        return $this;
    }

    /**
     * Get recurringStatus
     *
     * @return integer 
     */
    public function getRecurringStatus()
    {
        return $this->recurringStatus;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return PurchaseOrder
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
     * Set paymentMethod
     *
     * @param \Dhi\ServiceBundle\Entity\PaymentMethod $paymentMethod
     * @return PurchaseOrder
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
     * Add servicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases
     * @return PurchaseOrder
     */
    public function addServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases[] = $servicePurchases;

        return $this;
    }

    /**
     * Remove servicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases
     */
    public function removeServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases->removeElement($servicePurchases);
    }

    /**
     * Get servicePurchases
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServicePurchases()
    {
        return $this->servicePurchases;
    }

    /**
     * Add userService
     *
     * @param \Dhi\UserBundle\Entity\UserService $userService
     * @return PurchaseOrder
     */
    public function addUserService(\Dhi\UserBundle\Entity\UserService $userService)
    {
        $this->userService[] = $userService;

        return $this;
    }

    /**
     * Remove userService
     *
     * @param \Dhi\UserBundle\Entity\UserService $userService
     */
    public function removeUserService(\Dhi\UserBundle\Entity\UserService $userService)
    {
        $this->userService->removeElement($userService);
    }

    /**
     * Get userService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * Add userCreditLogs
     *
     * @param \Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs
     * @return PurchaseOrder
     */
    public function addUserCreditLog(\Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs)
    {
        $this->userCreditLogs[] = $userCreditLogs;

        return $this;
    }

    /**
     * Remove userCreditLogs
     *
     * @param \Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs
     */
    public function removeUserCreditLog(\Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs)
    {
        $this->userCreditLogs->removeElement($userCreditLogs);
    }

    /**
     * Get userCreditLogs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserCreditLogs()
    {
        return $this->userCreditLogs;
    }

    /**
     * Set milstar
     *
     * @param \Dhi\ServiceBundle\Entity\Milstar $milstar
     * @return PurchaseOrder
     */
    public function setMilstar(\Dhi\ServiceBundle\Entity\Milstar $milstar = null)
    {
        $this->milstar = $milstar;

        return $this;
    }

    /**
     * Get milstar
     *
     * @return \Dhi\ServiceBundle\Entity\Milstar 
     */
    public function getMilstar()
    {
        return $this->milstar;
    }

    /**
     * Set paypalCheckout
     *
     * @param \Dhi\ServiceBundle\Entity\PaypalCheckout $paypalCheckout
     * @return PurchaseOrder
     */
    public function setPaypalCheckout(\Dhi\ServiceBundle\Entity\PaypalCheckout $paypalCheckout = null)
    {
        $this->paypalCheckout = $paypalCheckout;

        return $this;
    }

    /**
     * Get paypalCheckout
     *
     * @return \Dhi\ServiceBundle\Entity\PaypalCheckout 
     */
    public function getPaypalCheckout()
    {
        return $this->paypalCheckout;
    }

    /**
     * Set paypalRecurringProfile
     *
     * @param \Dhi\ServiceBundle\Entity\PaypalRecurringProfile $paypalRecurringProfile
     * @return PurchaseOrder
     */
    public function setPaypalRecurringProfile(\Dhi\ServiceBundle\Entity\PaypalRecurringProfile $paypalRecurringProfile = null)
    {
        $this->paypalRecurringProfile = $paypalRecurringProfile;

        return $this;
    }

    /**
     * Get paypalRecurringProfile
     *
     * @return \Dhi\ServiceBundle\Entity\PaypalRecurringProfile 
     */
    public function getPaypalRecurringProfile()
    {
        return $this->paypalRecurringProfile;
    }


    /**
     * Set paymentByUser
     *
     * @param \Dhi\UserBundle\Entity\User $paymentByUser
     * @return PurchaseOrder
     */
    public function setPaymentByUser(\Dhi\UserBundle\Entity\User $paymentByUser = null)
    {
        $this->paymentByUser = $paymentByUser;

        return $this;
    }

    /**
     * Get paymentByUser
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getPaymentByUser()
    {
        return $this->paymentByUser;
    }

    /**
     * Add compensationUserService
     *
     * @param \Dhi\UserBundle\Entity\CompensationUserService $compensationUserService
     * @return PurchaseOrder
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
     * Set chase
     *
     * @param \Dhi\ServiceBundle\Entity\ChaseCheckout $chase
     * @return PurchaseOrder
     */
    public function setChase(\Dhi\ServiceBundle\Entity\ChaseCheckout $chase = null)
    {
        $this->chase = $chase;

        return $this;
    }

    /**
     * Get chase
     *
     * @return \Dhi\ServiceBundle\Entity\ChaseCheckout 
     */
    public function getChase()
    {
        return $this->chase;
    }

    /**
     * Set isDefaultChaseMid
     *
     * @param boolean $isDefaultChaseMid
     * @return PurchaseOrder
     */
    public function setIsDefaultChaseMid($isDefaultChaseMid)
    {
        $this->isDefaultChaseMid = $isDefaultChaseMid;

        return $this;
    }

    /**
     * Get isDefaultChaseMid
     *
     * @return boolean 
     */
    public function getIsDefaultChaseMid()
    {
        return $this->isDefaultChaseMid;
    }

    /**
     * Set chaseMerchantId
     *
     * @param \Dhi\AdminBundle\Entity\ChaseMerchantIds $chaseMerchantId
     * @return PurchaseOrder
     */
    public function setChaseMerchantId(\Dhi\AdminBundle\Entity\ChaseMerchantIds $chaseMerchantId = null)
    {
        $this->chaseMerchantId = $chaseMerchantId;

        return $this;
    }

    /**
     * Get chaseMerchantId
     *
     * @return \Dhi\AdminBundle\Entity\ChaseMerchantIds 
     */
    public function getChaseMerchantId()
    {
        return $this->chaseMerchantId;
    }

    /**
     * Set refundAfterExpiredAmount
     *
     * @param string $refundAfterExpiredAmount
     * @return PurchaseOrder
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
}
