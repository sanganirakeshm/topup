<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;



/**
 * @ORM\Entity
 * @ORM\Table(name="promo_customer")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\PromoCustomerRepository")
 */

class PromoCustomer {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

	/**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\PromoCode", inversedBy="promoCustomer")
     * @ORM\JoinColumn(name="promo_code_id", referencedColumnName="id")
     */
    protected $promoCodeId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="promoCustomer")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

	/**
     * @ORM\Column(name="redemp_time", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $redempTime;

	/**
     * @ORM\Column(name="promoCount", type="text")
     */
    protected $promoCount;

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PromoCodeCustomer
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return PromoCodeCustomer
     */
    public function setUser(\Dhi\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set redempTime
     *
     * @param \DateTime $redempTime
     * @return PromoCustomer
     */
    public function setRedempTime($redempTime)
    {
        $this->redempTime = $redempTime;

        return $this;
    }

    /**
     * Get redempTime
     *
     * @return \DateTime 
     */
    public function getRedempTime()
    {
        return $this->redempTime;
    }


    /**
     * Set promoCodeId
     *
     * @param \Dhi\UserBundle\Entity\PromoCode $promoCodeId
     * @return PromoCustomer
     */
    public function setPromoCodeId(\Dhi\UserBundle\Entity\PromoCode $promoCodeId = null)
    {
        $this->promoCodeId = $promoCodeId;

        return $this;
    }

    /**
     * Get promoCodeId
     *
     * @return \Dhi\UserBundle\Entity\PromoCode
     */
    public function getPromoCodeId()
    {
        return $this->promoCodeId;
    }

    /**
     * Set promoCount
     *
     * @param string $promoCount
     * @return PromoCustomer
     */
    public function setPromoCount($promoCount)
    {
        $this->promoCount = $promoCount;

        return $this;
    }

    /**
     * Get promoCount
     *
     * @return string 
     */
    public function getPromoCount()
    {
        return $this->promoCount;
    }
}
