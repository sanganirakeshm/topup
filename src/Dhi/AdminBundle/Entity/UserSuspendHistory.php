<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


/**
 * @ORM\Entity
 * @ORM\Table(name="user_suspend_history")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\UserSuspendHistoryRepository")
 */
class UserSuspendHistory
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
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="userSuspendHistory")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Service", inversedBy="userSuspendHistory")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;

    /**
     * @var string
     * @ORM\Column(name="admin", type="string", length=255, nullable=true)
     */
    protected $admin;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\UserService", inversedBy="userSuspendHistory")
     * @ORM\JoinColumn(name="user_service_id", referencedColumnName="id")
     */
    protected $userService;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="package_id", length=255)
     */
    protected $packageId;

    /**
     * @ORM\Column(name="activation_date", type="datetime", nullable=true)
     */
    protected $activationDate;

    /**
     * @ORM\Column(name="expiry_date", type="datetime", nullable=true)
     */
    protected $expiryDate;

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
     * @var smallint
     *
     * @ORM\Column(name="api_status", type="smallint", length=1, options={"comment":"0 => active, 1 => deactive"})
     */
    protected $apiStatus = 0;

    /**
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", length=1, options={"comment":"0 => suspended, 1 => unsuspended"})
     */
    protected $status = 0;

    /**
     * @var integer
     *
     * @ORM\Column(type="integer", name="suspend_validity", nullable=true)
     */
    protected $suspendValidity;

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
     * Set admin
     *
     * @param string $admin
     * @return UserSuspendHistory
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return string 
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set packageId
     *
     * @param string $packageId
     * @return UserSuspendHistory
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return string 
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set activationDate
     *
     * @param \DateTime $activationDate
     * @return UserSuspendHistory
     */
    public function setActivationDate($activationDate)
    {
        $this->activationDate = $activationDate;

        return $this;
    }

    /**
     * Get activationDate
     *
     * @return \DateTime 
     */
    public function getActivationDate()
    {
        return $this->activationDate;
    }

    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return UserSuspendHistory
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserSuspendHistory
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
     * @return UserSuspendHistory
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
     * Set apiStatus
     *
     * @param integer $apiStatus
     * @return UserSuspendHistory
     */
    public function setApiStatus($apiStatus)
    {
        $this->apiStatus = $apiStatus;

        return $this;
    }

    /**
     * Get apiStatus
     *
     * @return integer 
     */
    public function getApiStatus()
    {
        return $this->apiStatus;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return UserSuspendHistory
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

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return UserSuspendHistory
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
     * Set service
     *
     * @param \Dhi\UserBundle\Entity\Service $service
     * @return UserSuspendHistory
     */
    public function setService(\Dhi\UserBundle\Entity\Service $service = null)
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get service
     *
     * @return \Dhi\UserBundle\Entity\Service 
     */
    public function getService()
    {
        return $this->service;
    }

    

    /**
     * Set userService
     *
     * @param \Dhi\UserBundle\Entity\UserService $userService
     * @return UserSuspendHistory
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
     * Set suspendValidity
     *
     * @param integer $suspendValidity
     * @return UserSuspendHistory
     */
    public function setSuspendValidity($suspendValidity)
    {
        $this->suspendValidity = $suspendValidity;

        return $this;
    }

    /**
     * Get suspendValidity
     *
     * @return integer 
     */
    public function getSuspendValidity()
    {
        return $this->suspendValidity;
    }
}
