<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ApiStatusLog
 *
 * @ORM\Table(name="api_status_log")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ApiStatusLogRepository")
 */
class ApiStatusLog
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
     * @ORM\Column(name="api_name", type="integer", nullable=false)
     */
    /**
     * @ORM\Column(name="api_name", type="string", columnDefinition="ENUM('Selevision','Aradial')", options={"comment":"Selevision, Aradial"})
     */
    protected $apiName;
    
    /**
     * @ORM\Column(name="api_status", type="boolean", nullable=false)
     */
    protected $apiStatus = false;
    
    /**
     * @ORM\Column(name="total_failed", type="integer", nullable=false)
     */
    protected $totalFailed;
    
    /**
     * @ORM\Column(name="is_send_notification", type="boolean", nullable=false)
     */
    protected $isSendNotification = false;
            
    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime
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
     * Set apiName
     *
     * @param string $apiName
     * @return ApiStatusLog
     */
    public function setApiName($apiName)
    {
        $this->apiName = $apiName;

        return $this;
    }

    /**
     * Get apiName
     *
     * @return string 
     */
    public function getApiName()
    {
        return $this->apiName;
    }

    /**
     * Set apiStatus
     *
     * @param boolean $apiStatus
     * @return ApiStatusLog
     */
    public function setApiStatus($apiStatus)
    {
        $this->apiStatus = $apiStatus;

        return $this;
    }

    /**
     * Get apiStatus
     *
     * @return boolean 
     */
    public function getApiStatus()
    {
        return $this->apiStatus;
    }

    /**
     * Set totalFailed
     *
     * @param integer $totalFailed
     * @return ApiStatusLog
     */
    public function setTotalFailed($totalFailed)
    {
        $this->totalFailed = $totalFailed;

        return $this;
    }

    /**
     * Get totalFailed
     *
     * @return integer 
     */
    public function getTotalFailed()
    {
        return $this->totalFailed;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ApiStatusLog
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
     * @return ApiStatusLog
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
     * Set isSendNotification
     *
     * @param boolean $isSendNotification
     * @return ApiStatusLog
     */
    public function setIsSendNotification($isSendNotification)
    {
        $this->isSendNotification = $isSendNotification;

        return $this;
    }

    /**
     * Get isSendNotification
     *
     * @return boolean 
     */
    public function getIsSendNotification()
    {
        return $this->isSendNotification;
    }
}
