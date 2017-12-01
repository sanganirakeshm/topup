<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ServiceLocationDiscount
 *
 * @ORM\Table(name="global_discount")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\GlobalDiscountRepository")
 */
class GlobalDiscount
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
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     */
    protected $country;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="min_amount", type="integer", nullable=false)
     */
    protected $minAmount;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="max_amount", type="integer", nullable=false)
     */
    protected $maxAmount;
        
    /**
     * @var integer
     *
     * @ORM\Column(name="percentage", type="integer")
     */
    protected $percentage;
    

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
     * @param integer $minAmount
     * @return GlobalDiscount
     */
    public function setMinAmount($minAmount)
    {
        $this->minAmount = $minAmount;

        return $this;
    }

    /**
     * Get minAmount
     *
     * @return integer 
     */
    public function getMinAmount()
    {
        return $this->minAmount;
    }

    /**
     * Set maxAmount
     *
     * @param integer $maxAmount
     * @return GlobalDiscount
     */
    public function setMaxAmount($maxAmount)
    {
        $this->maxAmount = $maxAmount;

        return $this;
    }

    /**
     * Get maxAmount
     *
     * @return integer 
     */
    public function getMaxAmount()
    {
        return $this->maxAmount;
    }

    /**
     * Set percentage
     *
     * @param integer $percentage
     * @return GlobalDiscount
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
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return GlobalDiscount
     */
    public function setCountry(\Dhi\UserBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Dhi\UserBundle\Entity\Country 
     */
    public function getCountry()
    {
        return $this->country;
    }
}
