<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


/**
 * @ORM\Entity
 * @ORM\Table(name="service_api_error_log")
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\ServiceApiErrorLogRepository")
 */

class ServiceApiErrorLog {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @ORM\Column(name="api_type", type="string", columnDefinition="ENUM('Selevision', 'Aradial')", options={"comment":"Selevision, Aradial"})
     */
    protected $apiType;
    
    /**
     * @ORM\Column(name="action", type="string", columnDefinition="ENUM('ProfileUpdate', 'ChangePassword', 'ResetPassword', 'DeactivateCustomer','ReactivateCustomer')", options={"comment":"ProfileUpdate,ChangePassword,ResetPassword,DeactivateCustomerSelevision,ReactivateCustomerSelevision"})
     */
    protected $action;
    
    /**
     * @ORM\Column(name="status", type="boolean", nullable=false, options={"default":false})
     */
    protected $status = false;
    
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
     * Set apiType
     *
     * @param string $apiType
     * @return ServiceApiErrorLog
     */
    public function setApiType($apiType)
    {
        $this->apiType = $apiType;

        return $this;
    }

    /**
     * Get apiType
     *
     * @return string 
     */
    public function getApiType()
    {
        return $this->apiType;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return ServiceApiErrorLog
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return ServiceApiErrorLog
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ServiceApiErrorLog
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
     * @return ServiceApiErrorLog
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
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return ServiceApiErrorLog
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
}
