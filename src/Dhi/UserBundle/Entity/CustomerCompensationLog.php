<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping\Index as Index;

/**
 *
 * @ORM\Table(name="customer_compensation_log",indexes={
        @Index(name="customer_compensation_log_status_idx", columns={"status"})
    })
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\CustomerCompensationLogRepository")
 */
class CustomerCompensationLog
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @ORM\ManyToOne(targetEntity="Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $services;

    /**
     * @ORM\ManyToOne(targetEntity="UserService", inversedBy="compensationLogUserService")
     * @ORM\JoinColumn(name="user_service_id", referencedColumnName="id")
     */
    protected $userService;

    /**
     * @ORM\ManyToOne(targetEntity="Compensation")
     * @ORM\JoinColumn(name="compensation_id", referencedColumnName="id")
     */
    protected $compensation;
    
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Failure', 'Success', 'Pending')", options={"default":"Failure", "comment":"Failure, Success, Pending"})
     */
 
    protected $status = 'Pending';
    
    /**
     * @var integer
     *
     * @ORM\Column(name="bonus", type="integer", length=11, nullable=true)
     */
    protected $bonus;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="api_error", type="string", length=255, nullable=true)
     */
    protected $apiError;
    
    
    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
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
     * Set status
     *
     * @param string $status
     * @return CustomerCompensationLog
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set bonus
     *
     * @param integer $bonus
     * @return CustomerCompensationLog
     */
    public function setBonus($bonus)
    {
        $this->bonus = $bonus;

        return $this;
    }

    /**
     * Get bonus
     *
     * @return integer 
     */
    public function getBonus()
    {
        return $this->bonus;
    }

    /**
     * Set selevisionError
     *
     * @param string $selevisionError
     * @return CustomerCompensationLog
     */
    public function setSelevisionError($selevisionError)
    {
        $this->selevisionError = $selevisionError;

        return $this;
    }

    /**
     * Get selevisionError
     *
     * @return string 
     */
    public function getSelevisionError()
    {
        return $this->selevisionError;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return CustomerCompensationLog
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
     * Set services
     *
     * @param \Dhi\UserBundle\Entity\Service $services
     * @return CustomerCompensationLog
     */
    public function setServices(\Dhi\UserBundle\Entity\Service $services = null)
    {
        $this->services = $services;

        return $this;
    }

    /**
     * Get services
     *
     * @return \Dhi\UserBundle\Entity\Service 
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return CustomerCompensationLog
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
     * @return CustomerCompensationLog
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
     * @return CustomerCompensationLog
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
     * Set apiError
     *
     * @param string $apiError
     * @return CustomerCompensationLog
     */
    public function setApiError($apiError)
    {
        $this->apiError = $apiError;

        return $this;
    }

    /**
     * Get apiError
     *
     * @return string 
     */
    public function getApiError()
    {
        return $this->apiError;
    }

    /**
     * Set userService
     *
     * @param \Dhi\UserBundle\Entity\UserService $userService
     * @return CustomerCompensationLog
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
