<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="deactivate_without_mac_userservice_log")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\DeactivateWithOutMacUserServiceLogRepository")
 */
class DeactivateWithOutMacUserServiceLog
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userServices")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="userServices")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase", inversedBy="deactiveMacUsersLog")
     * @ORM\JoinColumn(name="service_purchase_id", referencedColumnName="id")
     */
    protected $servicePurchase;
        
    /**
     * @ORM\ManyToOne(targetEntity="UserService", inversedBy="deactiveMacUsersLog")
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
     * @var string
     *
     * @ORM\Column(type="string", name="package_name", length=255)
     */
    protected $packageName;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_activated", type="boolean", nullable=true)
     */
    protected $isActivated = false;
    
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set packageId
     *
     * @param string $packageId
     * @return DeactivateWithOutMacUserServiceLog
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
     * Set packageName
     *
     * @param string $packageName
     * @return DeactivateWithOutMacUserServiceLog
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;

        return $this;
    }

    /**
     * Get packageName
     *
     * @return string 
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * Set isActivated
     *
     * @param boolean $isActivated
     * @return DeactivateWithOutMacUserServiceLog
     */
    public function setIsActivated($isActivated)
    {
        $this->isActivated = $isActivated;

        return $this;
    }

    /**
     * Get isActivated
     *
     * @return boolean 
     */
    public function getIsActivated()
    {
        return $this->isActivated;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return DeactivateWithOutMacUserServiceLog
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
     * @return DeactivateWithOutMacUserServiceLog
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return DeactivateWithOutMacUserServiceLog
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
     * @return DeactivateWithOutMacUserServiceLog
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
     * Set servicePurchase
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchase
     * @return DeactivateWithOutMacUserServiceLog
     */
    public function setServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchase = null)
    {
        $this->servicePurchase = $servicePurchase;

        return $this;
    }

    /**
     * Get servicePurchase
     *
     * @return \Dhi\ServiceBundle\Entity\ServicePurchase 
     */
    public function getServicePurchase()
    {
        return $this->servicePurchase;
    }

    /**
     * Set userService
     *
     * @param \Dhi\UserBundle\Entity\UserService $userService
     * @return DeactivateWithOutMacUserServiceLog
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
}
