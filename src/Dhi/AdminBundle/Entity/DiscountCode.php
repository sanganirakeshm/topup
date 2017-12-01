<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * Bundle
 *
 * @ORM\Table(name="discount_code")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\DiscountCodeRepository")
 * @UniqueEntity(
 *      fields={"discountCode"},
 *      message="Global Promo Code already exists."
 * )
 *
 */
class DiscountCode
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
     * @ORM\Column(name="discount_code", type="string", nullable=false,unique=true)
     */
    protected $discountCode;

    /**
     * @ORM\Column(name="created_by", type="string", nullable=false)
     */
    protected $createdBy;

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
     * @ORM\Column(name="discount_image", type="text", length=65535, nullable=true)
	 *  @Assert\Image(
     *     minWidth = 1000,
     *     maxWidth = 2000,
     *     minHeight = 300,
     *     maxHeight = 600
     * )
     */
    protected $discountImage;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    protected $startDate;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=false)
     */
    protected $endDate;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;

     /**
     * @ORM\Column(name="note", type="string", nullable=true)
     */
    protected $note;

    /**
     * @ORM\OneToMany(targetEntity="DiscountCodeServiceLocation", mappedBy="discountCodeId")
     */    
     protected $discountCodeServiceLocation;

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
     * Set discountCode
     *
     * @param string $discountCode
     * @return DiscountCode
     */
    public function setDiscountCode($discountCode)
    {
        $this->discountCode = $discountCode;

        return $this;
    }

    /**
     * Get discountCode
     *
     * @return string
     */
    public function getDiscountCode()
    {
        return $this->discountCode;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return DiscountCode
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return DiscountCode
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return DiscountCode
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
     * Set status
     *
     * @param boolean $status
     * @return DiscountCode
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
     * Set createdBy
     *
     * @param string $createdBy
     * @return DiscountCode
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
     * Set amountType
     *
     * @param string $amountType
     * @return DiscountCode
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
     * @return DiscountCode
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
     * Set discountImage
     *
     * @param string $discountImage
     * @return DiscountCode
     */
    public function setDiscountImage($discountImage)
    {
        $this->discountImage = $discountImage;

        return $this;
    }

    /**
     * Get discountImage
     *
     * @return string 
     */
    public function getDiscountImage()
    {
        return $this->discountImage;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return DiscountCode
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
     * Constructor
     */
    public function __construct()
    {
        $this->discountCodeServiceLocation = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add discountCodeServiceLocation
     *
     * @param \Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation
     * @return DiscountCode
     */
    public function addDiscountCodeServiceLocation(\Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation)
    {
        $this->discountCodeServiceLocation[] = $discountCodeServiceLocation;

        return $this;
    }

    /**
     * Remove discountCodeServiceLocation
     *
     * @param \Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation
     */
    public function removeDiscountCodeServiceLocation(\Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation)
    {
        $this->discountCodeServiceLocation->removeElement($discountCodeServiceLocation);
    }

    /**
     * Get discountCodeServiceLocation
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDiscountCodeServiceLocation()
    {
        return $this->discountCodeServiceLocation;
    }
}
