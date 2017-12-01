<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


/**
 * @ORM\Entity
 * @ORM\Table(name="paypal_recurring_profile")
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\PaypalRecurringProfileRepository")
 */

class PaypalRecurringProfile {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\OneToOne(targetEntity="PurchaseOrder", mappedBy="paypalRecurringProfile")
     */
    protected $purchaseOrder;
    
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
     * @ORM\Column(name="profile_start_dt", type="datetime", length=45, nullable=true)
     */
    protected $profileStartDate;
    
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
     * @ORM\Column(name="is_send_notification", type="boolean", nullable=false, options={"default":false})
     */
    protected $isSendNotification = false;
    

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
     * @return PaypalRecurringProfile
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
     * @return PaypalRecurringProfile
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
     * Set profileStartDate
     *
     * @param \DateTime $profileStartDate
     * @return PaypalRecurringProfile
     */
    public function setProfileStartDate($profileStartDate)
    {
        $this->profileStartDate = $profileStartDate;

        return $this;
    }

    /**
     * Get profileStartDate
     *
     * @return \DateTime 
     */
    public function getProfileStartDate()
    {
        return $this->profileStartDate;
    }

    /**
     * Set nextBillingDate
     *
     * @param \DateTime $nextBillingDate
     * @return PaypalRecurringProfile
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
     * @return PaypalRecurringProfile
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
     * @return PaypalRecurringProfile
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
     * @return PaypalRecurringProfile
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
     * @return PaypalRecurringProfile
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
     * @return PaypalRecurringProfile
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
     * @return PaypalRecurringProfile
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
     * Set purchaseOrder
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return PaypalRecurringProfile
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
     * Set isSendNotification
     *
     * @param boolean $isSendNotification
     * @return PaypalRecurringProfile
     */
    public function setIsSendNotification($isSendNotification)
    {
        $this->isSendNotification = $isSendNotification;

        return $this;
    }

    /**
     * Get isSendNotification
     *
     * @return boolean 
     */
    public function getIsSendNotification()
    {
        return $this->isSendNotification;
    }
}
