<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


/**
 * @ORM\Entity
 * @ORM\Table(name="recurring_payment_log")
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\RecurringPaymentLogRepository")
 */

class RecurringPaymentLog {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="PaypalRecurringProfile", inversedBy="recurringPaymentLogs")
     * @ORM\JoinColumn(name="paypal_recurring_profile_id", referencedColumnName="id")
     */
    protected $paypalRecurringProfile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="profile_id", type="string", length=100)
     */
    protected $profileId;

    /**
     * @var string
     *
     * @ORM\Column(name="profile_status", type="string", length=45, nullable=true)
     */
    protected $profileStatus;
    
    /**
     * @var string
     *
     * @ORM\Column(name="billing_dt", type="datetime", length=45, nullable=true)
     */
    protected $billingDate;
    
    /**
     * @var string
     *
     * @ORM\Column(name="next_billing_dt", type="datetime", length=45, nullable=true)
     */
    protected $nextBillingDate;
    
    /**
     * @var string
     *
     * @ORM\Column(name="final_due_dt", type="datetime", length=45, nullable=true)
     */
    protected $finalDueDate;
        
    /**
     * @var string
     *
     * @ORM\Column(name="num_completed_cycle", type="integer", length=10, nullable=true)
     */
    protected $numCompletedCycle;
    
    /**
    * @var string
    *
    * @ORM\Column(name="num_remaining_cycle", type="integer", length=10, nullable=true)
    */
    protected $numRemainingCycle;
    
    /**
     * @var string
     *
     * @ORM\Column(name="ack", type="string", length=100, nullable=true)
     */
    protected $ack;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $amount;
    
    /**
     * @var string
     *
     * @ORM\Column(name="is_purchase_notification_send", type="boolean", nullable=false, options={"default":false})
     */
    protected $isPurchaseNotificationSend = false;        
    
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set profileId
     *
     * @param string $profileId
     * @return RecurringPaymentLog
     */
    public function setProfileId($profileId)
    {
        $this->profileId = $profileId;

        return $this;
    }

    /**
     * Get profileId
     *
     * @return string 
     */
    public function getProfileId()
    {
        return $this->profileId;
    }

    /**
     * Set profileStatus
     *
     * @param string $profileStatus
     * @return RecurringPaymentLog
     */
    public function setProfileStatus($profileStatus)
    {
        $this->profileStatus = $profileStatus;

        return $this;
    }

    /**
     * Get profileStatus
     *
     * @return string 
     */
    public function getProfileStatus()
    {
        return $this->profileStatus;
    }

    /**
     * Set billingDate
     *
     * @param \DateTime $billingDate
     * @return RecurringPaymentLog
     */
    public function setBillingDate($billingDate)
    {
        $this->billingDate = $billingDate;

        return $this;
    }

    /**
     * Get billingDate
     *
     * @return \DateTime 
     */
    public function getBillingDate()
    {
        return $this->billingDate;
    }

    /**
     * Set nextBillingDate
     *
     * @param \DateTime $nextBillingDate
     * @return RecurringPaymentLog
     */
    public function setNextBillingDate($nextBillingDate)
    {
        $this->nextBillingDate = $nextBillingDate;

        return $this;
    }

    /**
     * Get nextBillingDate
     *
     * @return \DateTime 
     */
    public function getNextBillingDate()
    {
        return $this->nextBillingDate;
    }

    /**
     * Set finalDueDate
     *
     * @param \DateTime $finalDueDate
     * @return RecurringPaymentLog
     */
    public function setFinalDueDate($finalDueDate)
    {
        $this->finalDueDate = $finalDueDate;

        return $this;
    }

    /**
     * Get finalDueDate
     *
     * @return \DateTime 
     */
    public function getFinalDueDate()
    {
        return $this->finalDueDate;
    }

    /**
     * Set numCompletedCycle
     *
     * @param integer $numCompletedCycle
     * @return RecurringPaymentLog
     */
    public function setNumCompletedCycle($numCompletedCycle)
    {
        $this->numCompletedCycle = $numCompletedCycle;

        return $this;
    }

    /**
     * Get numCompletedCycle
     *
     * @return integer 
     */
    public function getNumCompletedCycle()
    {
        return $this->numCompletedCycle;
    }

    /**
     * Set numRemainingCycle
     *
     * @param integer $numRemainingCycle
     * @return RecurringPaymentLog
     */
    public function setNumRemainingCycle($numRemainingCycle)
    {
        $this->numRemainingCycle = $numRemainingCycle;

        return $this;
    }

    /**
     * Get numRemainingCycle
     *
     * @return integer 
     */
    public function getNumRemainingCycle()
    {
        return $this->numRemainingCycle;
    }

    /**
     * Set ack
     *
     * @param string $ack
     * @return RecurringPaymentLog
     */
    public function setAck($ack)
    {
        $this->ack = $ack;

        return $this;
    }

    /**
     * Get ack
     *
     * @return string 
     */
    public function getAck()
    {
        return $this->ack;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return RecurringPaymentLog
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
     * @return RecurringPaymentLog
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
     * Set paypalRecurringProfile
     *
     * @param \Dhi\ServiceBundle\Entity\PaypalRecurringProfile $paypalRecurringProfile
     * @return RecurringPaymentLog
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
     * Set isPurchaseNotificationSend
     *
     * @param boolean $isPurchaseNotificationSend
     * @return RecurringPaymentLog
     */
    public function setIsPurchaseNotificationSend($isPurchaseNotificationSend)
    {
        $this->isPurchaseNotificationSend = $isPurchaseNotificationSend;

        return $this;
    }

    /**
     * Get isPurchaseNotificationSend
     *
     * @return boolean 
     */
    public function getIsPurchaseNotificationSend()
    {
        return $this->isPurchaseNotificationSend;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return RecurringPaymentLog
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }
}
