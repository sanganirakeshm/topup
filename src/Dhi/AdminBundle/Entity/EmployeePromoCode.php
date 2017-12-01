<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="employee_promo_code")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\EmployeePromoCodeRepository")
 */

class EmployeePromoCode {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="created_by", type="string", nullable=false)
     */
    protected $createdBy;
    
    /**
     * @var string
     * @ORM\Column(name="employee_name", type="string", length=255, nullable=true)
     */
    protected $employeeName;
    
    /**
     * @ORM\Column(name="code", type="text")
     */
    protected $employeePromoCode;

    /**
     * @var decimal
     *
     *  @ORM\Column(name="amount_type", type="string", columnDefinition="ENUM('percentage','amount')", options={"comment":"In Percentage , In Amount"})
     */
    protected $amountType;

    /**
     * @var decimal
     *
     * @ORM\Column(name="amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $amount;

    /**
     * @ORM\Column(name="no_of_redemption", type="integer", nullable=true, options={"default" = 0})
     *
     */
    protected $noOfRedemption;
    
	/**
     * @ORM\Column(name="reason", type="text")
     */
//    protected $reason;

    /**
     * @ORM\Column(name="note", type="text" , nullable=true)
     */
    protected $note;

	/**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = true;

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
     * @ORM\OneToMany(targetEntity="EmployeePromoCodeCustomer", mappedBy="EmployeePromoCodeId", cascade={"persist", "remove"})
     */
    protected $redeemedCustomer;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase", mappedBy="discountedEmployeePromocode")
     */
    protected $discountServicePurchases;

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
     * Set createdBy
     *
     * @param string $createdBy
     * @return EmployeePromoCode
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
     * Set employeeName
     *
     * @param string $employeeName
     * @return EmployeePromoCode
     */
    public function setEmployeeName($employeeName)
    {
        $this->employeeName = $employeeName;

        return $this;
    }

    /**
     * Get employeeName
     *
     * @return string 
     */
    public function getEmployeeName()
    {
        return $this->employeeName;
    }

    /**
     * Set employeePromoCode
     *
     * @param string $employeePromoCode
     * @return EmployeePromoCode
     */
    public function setEmployeePromoCode($employeePromoCode)
    {
        $this->employeePromoCode = $employeePromoCode;

        return $this;
    }

    /**
     * Get employeePromoCode
     *
     * @return string 
     */
    public function getEmployeePromoCode()
    {
        return $this->employeePromoCode;
    }

    /**
     * Set amountType
     *
     * @param string $amountType
     * @return EmployeePromoCode
     */
    public function setAmountType($amountType)
    {
        $this->amountType = $amountType;

        return $this;
    }

    /**
     * Get amountType
     *
     * @return string 
     */
    public function getAmountType()
    {
        return $this->amountType;
    }

    /**
     * Set amount
     *
     * @param string $amount
     * @return EmployeePromoCode
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

    /**
     * Set noOfRedemption
     *
     * @param integer $noOfRedemption
     * @return EmployeePromoCode
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
     * Set reason
     *
     * @param string $reason
     * @return EmployeePromoCode
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return EmployeePromoCode
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EmployeePromoCode
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
     * @return EmployeePromoCode
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
     * Constructor
     */
    public function __construct()
    {
        $this->redeemedCustomer = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add redeemedCustomer
     *
     * @param \Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $redeemedCustomer
     * @return EmployeePromoCode
     */
    public function addRedeemedCustomer(\Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $redeemedCustomer)
    {
        $this->redeemedCustomer[] = $redeemedCustomer;

        return $this;
    }

    /**
     * Remove redeemedCustomer
     *
     * @param \Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $redeemedCustomer
     */
    public function removeRedeemedCustomer(\Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $redeemedCustomer)
    {
        $this->redeemedCustomer->removeElement($redeemedCustomer);
    }

    /**
     * Get redeemedCustomer
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRedeemedCustomer()
    {
        return $this->redeemedCustomer;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return EmployeePromoCode
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
     * Add discountServicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $discountServicePurchases
     * @return EmployeePromoCode
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
}
