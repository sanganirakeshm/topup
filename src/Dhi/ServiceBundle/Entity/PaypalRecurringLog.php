<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


/**
 * @ORM\Entity
 * @ORM\Table(name="paypal_recurring_log")
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\PaypalRecurringLogRepository")
 */

class PaypalRecurringLog {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="PaypalRecurringProfile", inversedBy="paypalRecurringLogs")
     * @ORM\JoinColumn(name="paypal_recurring_profile_id", referencedColumnName="id")
     */
    protected $paypalRecurringProfile;
    
    /**
     * @var string
     *
     * @ORM\Column(name="next_billing_dt", type="datetime", length=45, nullable=true)
     */
    protected $nextBillingDate;
    
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
     * Set nextBillingDate
     *
     * @param \DateTime $nextBillingDate
     * @return PaypalRecurringLog
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
     * Set isPurchaseNotificationSend
     *
     * @param boolean $isPurchaseNotificationSend
     * @return PaypalRecurringLog
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PaypalRecurringLog
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
     * @return PaypalRecurringLog
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
     * @return PaypalRecurringLog
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
}
