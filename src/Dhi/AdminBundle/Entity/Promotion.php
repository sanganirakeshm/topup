<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * Promotion
 *
 * @ORM\Table(name="promotion")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\PromotionRepository")
 */
class Promotion
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
     * @ORM\Column(name="amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $amount;

    /**
     * @ORM\Column(name="amount_type", type="string", columnDefinition="ENUM('p','a')", options={"default":"0", "comment":"p = Percentage, a = Amount"})
     */
    protected $amountType;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=false)
     */
    protected $startDate;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=false)
     */
    protected $endDate;

    /**
     * @ORM\Column(name="banner_image", type="text", length=65535, nullable=true)
     *  @Assert\Image(
     *     minWidth = 1000,
     *     maxWidth = 2000,
     *     minHeight = 300,
     *     maxHeight = 600
     * )
     */
    protected $bannerImage;

    /**
     * @ORM\ManyToMany(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation")
     * @ORM\JoinTable(name="promotion_service_locations",
     *      joinColumns={@ORM\JoinColumn(name="promotion_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="service_location_id", referencedColumnName="id")}
     * )
     */
    protected $serviceLocations;

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
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     */
    protected $isActive = false;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase" , mappedBy="promotion")
     */
    protected $discounts;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->serviceLocations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set amount
     *
     * @param string $amount
     * @return Promotion
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
     * Set amountType
     *
     * @param integer $amountType
     * @return Promotion
     */
    public function setAmountType($amountType)
    {
        $this->amountType = $amountType;

        return $this;
    }

    /**
     * Get amountType
     *
     * @return integer 
     */
    public function getAmountType()
    {
        return $this->amountType;
    }

    /**
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return Promotion
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
     * @return Promotion
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
     * @return Promotion
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
     * @return Promotion
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
     * Set isActive
     *
     * @param boolean $isActive
     * @return Promotion
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Add serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return Promotion
     */
    public function addServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations[] = $serviceLocations;

        return $this;
    }

    /**
     * Remove serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     */
    public function removeServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations->removeElement($serviceLocations);
    }

    /**
     * Get serviceLocations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocations()
    {
        return $this->serviceLocations;
    }

    /**
     * Set createdBy
     *
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return Promotion
     */
    public function setCreatedBy(\Dhi\UserBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Add discounts
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $discounts
     * @return Promotion
     */
    public function addDiscount(\Dhi\ServiceBundle\Entity\ServicePurchase $discounts)
    {
        $this->discounts[] = $discounts;

        return $this;
    }

    /**
     * Remove discounts
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $discounts
     */
    public function removeDiscount(\Dhi\ServiceBundle\Entity\ServicePurchase $discounts)
    {
        $this->discounts->removeElement($discounts);
    }

    /**
     * Get discounts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDiscounts()
    {
        return $this->discounts;
    }

    /**
     * Set bannerImage
     *
     * @param string $bannerImage
     * @return Promotion
     */
    public function setBannerImage($bannerImage)
    {
        $this->bannerImage = $bannerImage;

        return $this;
    }

    /**
     * Get bannerImage
     *
     * @return string 
     */
    public function getBannerImage()
    {
        return $this->bannerImage;
    }
}
