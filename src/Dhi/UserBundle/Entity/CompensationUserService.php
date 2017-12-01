<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="compensation_user_service")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\CompensationUserServiceRepository")
 */

class CompensationUserService {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Compensation", inversedBy="userService")
     * @ORM\JoinColumn(name="compensation_id", referencedColumnName="id")
     */
    protected $compensation;

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
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\UserService", inversedBy="compensationUserService")
     * @ORM\JoinColumn(name="user_service_id", referencedColumnName="id")
     */
    protected $userService;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\ServiceBundle\Entity\PurchaseOrder", inversedBy="compensationUserService")
     * @ORM\JoinColumn(name="purchase_order_id", referencedColumnName="id")
     */
    protected $purchaseOrder;

    /**
     * @var smallint
     *
     * @ORM\Column(name="is_email_sent", type="smallint", length=1, options={"comment":"0 => Not Sent, 1 => Sent"})
     */
    protected $isEmailSent = 0;

    /**
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", length=1, options={"comment":"0 => Failed, 1 => Success"})
     */
    protected $status = 0;

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
     * @return CompensationUserService
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
     * @return CompensationUserService
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
     * Set compensation
     *
     * @param \Dhi\UserBundle\Entity\Compensation $compensation
     * @return CompensationUserService
     */
    public function setCompensation(\Dhi\UserBundle\Entity\Compensation $compensation = null)
    {
        $this->compensation = $compensation;

        return $this;
    }

    /**
     * Get compensation
     *
     * @return \Dhi\UserBundle\Entity\Compensation 
     */
    public function getCompensation()
    {
        return $this->compensation;
    }

    /**
     * Set userService
     *
     * @param \Dhi\UserBundle\Entity\UserService $userService
     * @return CompensationUserService
     */
    public function setUserService(\Dhi\UserBundle\Entity\UserService $userService = null)
    {
        $this->userService = $userService;

        return $this;
    }

    /**
     * Get userService
     *
     * @return \Dhi\UserBundle\Entity\UserService 
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * Set isEmailSent
     *
     * @param integer $isEmailSent
     * @return CompensationUserService
     */
    public function setIsEmailSent($isEmailSent)
    {
        $this->isEmailSent = $isEmailSent;

        return $this;
    }

    /**
     * Get isEmailSent
     *
     * @return integer 
     */
    public function getIsEmailSent()
    {
        return $this->isEmailSent;
    }

    /**
     * Set purchaseOrder
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return CompensationUserService
     */
    public function setPurchaseOrder(\Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder = null)
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }

    /**
     * Get purchaseOrder
     *
     * @return \Dhi\ServiceBundle\Entity\PurchaseOrder 
     */
    public function getPurchaseOrder()
    {
        return $this->purchaseOrder;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return CompensationUserService
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer 
     */
    public function getStatus()
    {
        return $this->status;
    }
}
