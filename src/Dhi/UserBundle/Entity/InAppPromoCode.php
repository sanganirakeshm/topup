<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="in_app_promo_code")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\InAppPromoCodeRepository")
 */

class InAppPromoCode {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation", inversedBy="InAppPromocode")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocations;
	
	
    /**
     * @ORM\Column(name="promocode", type="text")
     */
    protected $promoCode;

   
    /**
     * @ORM\Column(name="expired_at", type="datetime", nullable=true)
     *
     */
    protected $expiredAt;
	
    /**
     * @ORM\Column(name="note", type="text" , nullable=true)
     */
    protected $note;

    /**
     * @ORM\Column(name="amount", type="integer", length=10, nullable=false)
     */
    protected $amount;
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Active','Inactive')")
     */
    protected $status;
    
    /**
     * @ORM\Column(name="is_redeemed", type="string", columnDefinition="ENUM('Yes','No')")
     */
    protected $isRedeemed = "No";
    
    /**
     * @ORM\Column(name="created_by", type="string", nullable=false)
     */
    protected $createdBy;
    
    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
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
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="inAppPromoCodeCustomer")
     * @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=true)
     */
    protected $customer;

     /**
     * @ORM\Column(name="redeem_date", type="datetime", nullable=true)
     */
    protected $redeemDate;


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
     * Set promoCode
     *
     * @param string $promoCode
     * @return InAppPromoCode
     */
    public function setPromoCode($promoCode)
    {
        $this->promoCode = $promoCode;

        return $this;
    }

    /**
     * Get promoCode
     *
     * @return string 
     */
    public function getPromoCode()
    {
        return $this->promoCode;
    }

    /**
     * Set expiredAt
     *
     * @param \DateTime $expiredAt
     * @return InAppPromoCode
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
     * Set note
     *
     * @param string $note
     * @return InAppPromoCode
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
     * Set amount
     *
     * @param integer $amount
     * @return InAppPromoCode
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return InAppPromoCode
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
     * @return InAppPromoCode
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
     * Set createdBy
     *
     * @param string $createdBy
     * @return InAppPromoCode
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return InAppPromoCode
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
     * @return InAppPromoCode
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
     * Set redeemDate
     *
     * @param \DateTime $redeemDate
     * @return InAppPromoCode
     */
    public function setRedeemDate($redeemDate)
    {
        $this->redeemDate = $redeemDate;

        return $this;
    }

    /**
     * Get redeemDate
     *
     * @return \DateTime 
     */
    public function getRedeemDate()
    {
        return $this->redeemDate;
    }

    /**
     * Set serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return InAppPromoCode
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
     * Set customer
     *
     * @param \Dhi\UserBundle\Entity\User $customer
     * @return InAppPromoCode
     */
    public function setCustomer(\Dhi\UserBundle\Entity\User $customer = null)
    {
        $this->customer = $customer;

        return $this;
    }

    /**
     * Get customer
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getCustomer()
    {
        return $this->customer;
    }
}
