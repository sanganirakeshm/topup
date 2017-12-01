<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\Service;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_aradial_purchase_history")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserAradialPurchaseHistoryRepository")
 */
class UserAradialPurchaseHistory
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userAradialPurchaseHistory")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;


    /**
     * @var string
     *
     * @ORM\Column(type="string", name="offer_id", length=255)
     */
    protected $offerId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="description", length=255)
     */
    protected $description;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", name="price", length=255)
     */
    protected $price;

    /**
     * @var integer
     * @ORM\Column(name="expiration_time", type="integer", length=11)
     */

    protected $expirationTime;

    /**
     * @ORM\Column(name="sale_expiration_date", type="datetime", nullable=true)
     */

    protected $saleExpirationDate;


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
     * Set offerId
     *
     * @param string $offerId
     * @return UserAradialPurchaseHistory
     */
    public function setOfferId($offerId)
    {
        $this->offerId = $offerId;

        return $this;
    }

    /**
     * Get offerId
     *
     * @return string
     */
    public function getOfferId()
    {
        return $this->offerId;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return UserAradialPurchaseHistory
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return UserAradialPurchaseHistory
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
     * Set price
     *
     * @param integer $price
     * @return UserAradialPurchaseHistory
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set expirationTime
     *
     * @param integer $expirationTime
     * @return UserAradialPurchaseHistory
     */
    public function setExpirationTime($expirationTime)
    {
        $this->expirationTime = $expirationTime;

        return $this;
    }

    /**
     * Get expirationTime
     *
     * @return integer 
     */
    public function getExpirationTime()
    {
        return $this->expirationTime;
    }

    /**
     * Set saleExpirationDate
     *
     * @param \DateTime $saleExpirationDate
     * @return UserAradialPurchaseHistory
     */
    public function setSaleExpirationDate($saleExpirationDate)
    {
        $this->saleExpirationDate = $saleExpirationDate;

        return $this;
    }

    /**
     * Get saleExpirationDate
     *
     * @return \DateTime 
     */
    public function getSaleExpirationDate()
    {
        return $this->saleExpirationDate;
    }
}
