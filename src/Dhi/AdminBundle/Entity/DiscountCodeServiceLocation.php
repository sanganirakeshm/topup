<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;
/**
 * Bundle
 *
 * @ORM\Table(name="discount_code_service_location")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\DiscountCodeServiceLocationRepository")
 */
class DiscountCodeServiceLocation
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
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\DiscountCode", inversedBy="discountCodeServiceLocation")
     * @ORM\JoinColumn(name="discount_code_id", referencedColumnName="id")
     */
    protected $discountCodeId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\serviceLocation", inversedBy="discountCodeServiceLocation")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocation;

     /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;


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
     * Set redeemDate
     *
     * @param \DateTime $redeemDate
     * @return DiscountCodeServiceLocation
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
     * Set discountCodeId
     *
     * @param \Dhi\AdminBundle\Entity\DiscountCode $discountCodeId
     * @return DiscountCodeServiceLocation
     */
    public function setDiscountCodeId(\Dhi\AdminBundle\Entity\DiscountCode $discountCodeId = null)
    {
        $this->discountCodeId = $discountCodeId;

        return $this;
    }

    /**
     * Get discountCodeId
     *
     * @return \Dhi\AdminBundle\Entity\DiscountCode
     */
    public function getDiscountCodeId()
    {
        return $this->discountCodeId;
    }

    /**
     * Set serviceLocation
     *
     * @param \Dhi\AdminBundle\Entity\serviceLocation $serviceLocation
     * @return DiscountCodeServiceLocation
     */
    public function setServiceLocation(\Dhi\AdminBundle\Entity\serviceLocation $serviceLocation = null)
    {
        $this->serviceLocation = $serviceLocation;

        return $this;
    }

    /**
     * Get serviceLocation
     *
     * @return \Dhi\AdminBundle\Entity\serviceLocation
     */
    public function getServiceLocation()
    {
        return $this->serviceLocation;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return DiscountCodeServiceLocation
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
}
