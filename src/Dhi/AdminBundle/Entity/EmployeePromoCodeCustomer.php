<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;
/**
 * Bundle
 *
 * @ORM\Table(name="employee_promo_code_customer")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\EmployeePromoCodeCustomerRepository")
 */
class EmployeePromoCodeCustomer
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
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\EmployeePromoCode", inversedBy="redeemedCustomer")
     * @ORM\JoinColumn(name="employee_promo_code_id", referencedColumnName="id")
     */
    protected $EmployeePromoCodeId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="employeePromoCodeCustomer")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

     /**
     * @ORM\Column(name="redeem_date", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    protected $redeemDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false, options={"default" = 0})
     */
    protected $status = true;

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
     * @return EmployeePromoCodeCustomer
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return EmployeePromoCodeCustomer
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
     * Set status
     *
     * @param boolean $status
     * @return EmployeePromoCodeCustomer
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
     * Set EmployeePromoCode
     *
     * @param \Dhi\AdminBundle\Entity\EmployeePromoCode $employeePromoCode
     * @return EmployeePromoCodeCustomer
     */
    public function setEmployeePromoCode(\Dhi\AdminBundle\Entity\EmployeePromoCode $employeePromoCode = null)
    {
        $this->EmployeePromoCode = $employeePromoCode;

        return $this;
    }

    /**
     * Get EmployeePromoCode
     *
     * @return \Dhi\AdminBundle\Entity\EmployeePromoCode 
     */
    public function getEmployeePromoCode()
    {
        return $this->EmployeePromoCode;
    }

    /**
     * Set EmployeePromoCodeId
     *
     * @param \Dhi\AdminBundle\Entity\EmployeePromoCode $employeePromoCodeId
     * @return EmployeePromoCodeCustomer
     */
    public function setEmployeePromoCodeId(\Dhi\AdminBundle\Entity\EmployeePromoCode $employeePromoCodeId = null)
    {
        $this->EmployeePromoCodeId = $employeePromoCodeId;

        return $this;
    }

    /**
     * Get EmployeePromoCodeId
     *
     * @return \Dhi\AdminBundle\Entity\EmployeePromoCode 
     */
    public function getEmployeePromoCodeId()
    {
        return $this->EmployeePromoCodeId;
    }
}
