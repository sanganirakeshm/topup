<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ServiceLocationDiscount
 *
 * @ORM\Table(name="service_location_discount")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ServiceLocationDiscountRepository")
 */
class ServiceLocationDiscount
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
     * @var decimal
     *
     * @ORM\Column(name="min_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $minAmount;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="max_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $maxAmount;
        
    /**
     * @var integer
     *
     * @ORM\Column(name="percentage", type="integer")
     */
    protected $percentage;

    /**
     * @ORM\ManyToOne(targetEntity="ServiceLocation", inversedBy="serviceLocationDiscounts")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocation;
                    

    

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
     * Set minAmount
     *
     * @param string $minAmount
     * @return ServiceLocationDiscount
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;

        return $this;
    }

    /**
     * Get minAmount
     *
     * @return string 
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * Set maxAmount
     *
     * @param string $maxAmount
     * @return ServiceLocationDiscount
     */
    public function setMaxAmount($maxAmount)
    {
        $this->maxAmount = $maxAmount;

        return $this;
    }

    /**
     * Get maxAmount
     *
     * @return string 
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * Set percentage
     *
     * @param integer $percentage
     * @return ServiceLocationDiscount
     */
    public function setPercentage($percentage)
    {
        $this->percentage = $percentage;

        return $this;
    }

    /**
     * Get percentage
     *
     * @return integer 
     */
    public function getPercentage()
    {
        return $this->percentage;
    }

    /**
     * Set serviceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation
     * @return ServiceLocationDiscount
     */
    public function setServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation = null)
    {
        $this->serviceLocation = $serviceLocation;

        return $this;
    }

    /**
     * Get serviceLocation
     *
     * @return \Dhi\AdminBundle\Entity\ServiceLocation 
     */
    public function getServiceLocation()
    {
        return $this->serviceLocation;
    }
}
