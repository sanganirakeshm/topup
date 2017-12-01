<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="tikilive_promo_code")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\TikilivePromoCodeRepository")
 */

class TikilivePromoCode {

    /**
     * @var is_integer()
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(name="promocode", type="text")
     */
    protected $promoCode;
    
    /**
     * @var string
     * @ORM\Column(name="plan_name", type="string", length=255, nullable=false)
     */
    protected $planName;
    
    /**
     * @var string
     * @ORM\Column(name="batch_name", type="string", length=20, nullable=false)
     */
    protected $batchName;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = true;
   
    /**
     * @ORM\Column(name="is_redeemed", type="string", columnDefinition="ENUM('Yes','No')")
     */
    protected $isRedeemed = "No";

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="redeemed_date", type="datetime", nullable=true)
     */
    protected $displayDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tikilive_redeemed_date", type="datetime", nullable=true)
     */
    protected $redeemedDate;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="TikilivePromoCodeCustomer")
     * @ORM\JoinColumn(name="redeemed_by", referencedColumnName="id", nullable=true)
     */
    protected $redeemedBy;
   
    /**
     * @ORM\Column(name="purchase_id", type="integer", nullable=true)
     *
     */
    protected $purchaseId;
    
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
     * @ORM\Column(name="updated_by", type="integer", nullable=true)
     */
    protected $updatedBy;
    
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
     * @return TikilivePromoCode
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
     * Set planName
     *
     * @param string $planName
     * @return TikilivePromoCode
     */
    public function setPlanName($planName)
    {
        $this->planName = $planName;

        return $this;
    }

    /**
     * Get planName
     *
     * @return string 
     */
    public function getPlanName()
    {
        return $this->planName;
    }

    /**
     * Set batchName
     *
     * @param string $batchName
     * @return TikilivePromoCode
     */
    public function setBatchName($batchName)
    {
        $this->batchName = $batchName;

        return $this;
    }

    /**
     * Get batchName
     *
     * @return string 
     */
    public function getBatchName()
    {
        return $this->batchName;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return TikilivePromoCode
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
     * Set isRedeemed
     *
     * @param string $isRedeemed
     * @return TikilivePromoCode
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
     * Set purchaseId
     *
     * @param integer $purchaseId
     * @return TikilivePromoCode
     */
    public function setPurchaseId($purchaseId)
    {
        $this->purchaseId = $purchaseId;

        return $this;
    }

    /**
     * Get purchaseId
     *
     * @return integer 
     */
    public function getPurchaseId()
    {
        return $this->purchaseId;
    }

    /**
     * Set createdBy
     *
     * @param string $createdBy
     * @return TikilivePromoCode
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
     * @return TikilivePromoCode
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
     * @return TikilivePromoCode
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
     * Set updatedBy
     *
     * @param integer $updatedBy
     * @return TikilivePromoCode
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return integer 
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set redeemedBy
     *
     * @param \Dhi\UserBundle\Entity\User $redeemedBy
     * @return TikilivePromoCode
     */
    public function setRedeemedBy(\Dhi\UserBundle\Entity\User $redeemedBy = null)
    {
        $this->redeemedBy = $redeemedBy;

        return $this;
    }

    /**
     * Get redeemedBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getRedeemedBy()
    {
        return $this->redeemedBy;
    }


    /**
     * Set redeemedDate
     *
     * @param \DateTime $redeemedDate
     * @return TikilivePromoCode
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
     * Set displayDate
     *
     * @param \DateTime $displayDate
     * @return TikilivePromoCode
     */
    public function setDisplayDate($displayDate)
    {
        $this->displayDate = $displayDate;

        return $this;
    }

    /**
     * Get displayDate
     *
     * @return \DateTime 
     */
    public function getDisplayDate()
    {
        return $this->displayDate;
    }
}
