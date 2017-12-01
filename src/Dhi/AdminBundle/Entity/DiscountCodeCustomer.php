<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;
/**
 * Bundle
 *
 * @ORM\Table(name="discount_code_customer")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\DiscountCodeCustomerRepository")
 */
class DiscountCodeCustomer
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
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\DiscountCode", inversedBy="discountCodeCustomer")
     * @ORM\JoinColumn(name="discount_code_id", referencedColumnName="id")
     */
    protected $DiscountCodeId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="discountCodeCustomer")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

     /**
     * @ORM\Column(name="redeem_date", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
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
     * Set redeemDate
     *
     * @param \DateTime $redeemDate
     * @return DiscountCodeCustomer
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
     * Set DiscountCodeId
     *
     * @param \Dhi\AdminBundle\Entity\DiscountCode $discountCodeId
     * @return DiscountCodeCustomer
     */
    public function setDiscountCodeId(\Dhi\AdminBundle\Entity\DiscountCode $discountCodeId = null)
    {
        $this->DiscountCodeId = $discountCodeId;

        return $this;
    }

    /**
     * Get DiscountCodeId
     *
     * @return \Dhi\AdminBundle\Entity\DiscountCode
     */
    public function getDiscountCodeId()
    {
        return $this->DiscountCodeId;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return DiscountCodeCustomer
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
}
