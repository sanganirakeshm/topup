<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


/**
 * @ORM\Entity
 * @ORM\Table(name="paypal_checkout")
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\PaypalCheckoutRepository")
 */

class PaypalCheckout {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="paypalCheckouts")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @var string
     *
     * @ORM\Column(name="buyer_email_address", type="string", length=255)
     */
    protected $buyerEmailAddress;

    /**
     * @var string
     *
     * @ORM\Column(name="paypal_payer_id", type="string", length=45, nullable=true)
     */
    protected $paypalPayerId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="paypal_payer_status", type="string", length=45, nullable=true)
     */
    protected $paypalPayerStatus;
    
    /**
     * @var string
     *
     * @ORM\Column(name="buyer_country_code", type="string", length=45)
     */
    protected $buyerCountryCode;
    
    /**
    * @var string
    *
    * @ORM\Column(name="paypal_transaction_id", type="string", length= 255)
    */
    protected $paypalTransactionId;
    
    /**
     * @var string
     *
     * @ORM\Column(name="paypal_authorization_id", type="string", length= 255, nullable=true)
     */
    protected $paypalAuthorizationId;
        
    
    /**
     * @var string
     *
     * @ORM\Column(name="cc_number", type="string", length=100, nullable=true)
     */
    protected $creditCardNo;
    
    /**
     * @var string
     *
     * @ORM\Column(name="cc_exp_date", type="string", length=50, nullable=true)
     */
    protected $ccExpiredDate;
    
    /**
    * @var string
    *
    * @ORM\Column(name="paypal_process_status", type="string", length=30, nullable=false, options={"comment":"DoExpressCheckOut, Completed, Refunded"})
    */
    protected $paypalProcessStatus;
    
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
     * @var decimal
     *
     * @ORM\Column(name="total_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $totalAmount;
                    
    /**
     * @ORM\OneToOne(targetEntity="PurchaseOrder", mappedBy="paypalCheckout")
     */
    protected $purchaseOrder;
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        
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
     * Set buyerEmailAddress
     *
     * @param string $buyerEmailAddress
     * @return PaypalCheckout
     */
    public function setBuyerEmailAddress($buyerEmailAddress)
    {
        $this->buyerEmailAddress = $buyerEmailAddress;

        return $this;
    }

    /**
     * Get buyerEmailAddress
     *
     * @return string 
     */
    public function getBuyerEmailAddress()
    {
        return $this->buyerEmailAddress;
    }

    /**
     * Set paypalPayerId
     *
     * @param string $paypalPayerId
     * @return PaypalCheckout
     */
    public function setPaypalPayerId($paypalPayerId)
    {
        $this->paypalPayerId = $paypalPayerId;

        return $this;
    }

    /**
     * Get paypalPayerId
     *
     * @return string 
     */
    public function getPaypalPayerId()
    {
        return $this->paypalPayerId;
    }

    /**
     * Set paypalPayerStatus
     *
     * @param string $paypalPayerStatus
     * @return PaypalCheckout
     */
    public function setPaypalPayerStatus($paypalPayerStatus)
    {
        $this->paypalPayerStatus = $paypalPayerStatus;

        return $this;
    }

    /**
     * Get paypalPayerStatus
     *
     * @return string 
     */
    public function getPaypalPayerStatus()
    {
        return $this->paypalPayerStatus;
    }

    /**
     * Set buyerCountryCode
     *
     * @param string $buyerCountryCode
     * @return PaypalCheckout
     */
    public function setBuyerCountryCode($buyerCountryCode)
    {
        $this->buyerCountryCode = $buyerCountryCode;

        return $this;
    }

    /**
     * Get buyerCountryCode
     *
     * @return string 
     */
    public function getBuyerCountryCode()
    {
        return $this->buyerCountryCode;
    }

    /**
     * Set paypalTransactionId
     *
     * @param string $paypalTransactionId
     * @return PaypalCheckout
     */
    public function setPaypalTransactionId($paypalTransactionId)
    {
        $this->paypalTransactionId = $paypalTransactionId;

        return $this;
    }

    /**
     * Get paypalTransactionId
     *
     * @return string 
     */
    public function getPaypalTransactionId()
    {
        return $this->paypalTransactionId;
    }

    /**
     * Set paypalAuthorizationId
     *
     * @param string $paypalAuthorizationId
     * @return PaypalCheckout
     */
    public function setPaypalAuthorizationId($paypalAuthorizationId)
    {
        $this->paypalAuthorizationId = $paypalAuthorizationId;

        return $this;
    }

    /**
     * Get paypalAuthorizationId
     *
     * @return string 
     */
    public function getPaypalAuthorizationId()
    {
        return $this->paypalAuthorizationId;
    }

    /**
     * Set creditCardNo
     *
     * @param string $creditCardNo
     * @return PaypalCheckout
     */
    public function setCreditCardNo($creditCardNo)
    {
        $this->creditCardNo = $creditCardNo;

        return $this;
    }

    /**
     * Get creditCardNo
     *
     * @return string 
     */
    public function getCreditCardNo()
    {
        return $this->creditCardNo;
    }

    /**
     * Set ccExpiredDate
     *
     * @param string $ccExpiredDate
     * @return PaypalCheckout
     */
    public function setCcExpiredDate($ccExpiredDate)
    {
        $this->ccExpiredDate = $ccExpiredDate;

        return $this;
    }

    /**
     * Get ccExpiredDate
     *
     * @return string 
     */
    public function getCcExpiredDate()
    {
        return $this->ccExpiredDate;
    }

    /**
     * Set paypalProcessStatus
     *
     * @param string $paypalProcessStatus
     * @return PaypalCheckout
     */
    public function setPaypalProcessStatus($paypalProcessStatus)
    {
        $this->paypalProcessStatus = $paypalProcessStatus;

        return $this;
    }

    /**
     * Get paypalProcessStatus
     *
     * @return string 
     */
    public function getPaypalProcessStatus()
    {
        return $this->paypalProcessStatus;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PaypalCheckout
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
     * @return PaypalCheckout
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
     * Set totalAmount
     *
     * @param string $totalAmount
     * @return PaypalCheckout
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return PaypalCheckout
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
     * @return PaypalCheckout
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
}
