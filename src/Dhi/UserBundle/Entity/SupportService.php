<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * SupportService
 *
 * @ORM\Table(name="support_service")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\SupportServiceRepository")
 */
class SupportService
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
     * @ORM\Column(name="service_name", type="string", length=255)
     *
     */
    protected $serviceName;
    
    /**
     * @ORM\Column(name="is_active", type="boolean")
     *
     */
    protected $isActive = true;
    
    /**
     * @ORM\Column(name="is_deleted", type="boolean")
     *
     */
    protected $isDeleted = false;

    /**
     * @ORM\Column(name="ip_address", type="string", length=15, nullable=true)
     */
    protected $ipAddress;
    
    /**
     * @ORM\Column(name="created_by", type="integer", nullable=true)
     */
    protected $createdBy;
    
    /**
     * @ORM\Column(name="updated_by", type="integer", nullable=true)
     */
    protected $updatedBy;
    
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
     *
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\Support", mappedBy="supportService")
     */
    protected $support;

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
     * Set serviceName
     *
     * @param string $serviceName
     * @return SupportService
     */
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;

        return $this;
    }

    /**
     * Get serviceName
     *
     * @return string 
     */
    public function getServiceName()
    {
        return $this->serviceName;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return SupportService
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return SupportService
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return SupportService
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set createdBy
     *
     * @param integer $createdBy
     * @return SupportService
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedBy
     *
     * @param integer $updatedBy
     * @return SupportService
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return integer 
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return SupportService
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
     * @return SupportService
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
     * Constructor
     */
    public function __construct()
    {
        $this->support = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add support
     *
     * @param \Dhi\UserBundle\Entity\Support $support
     * @return SupportService
     */
    public function addSupport(\Dhi\UserBundle\Entity\Support $support)
    {
        $this->support[] = $support;

        return $this;
    }

    /**
     * Remove support
     *
     * @param \Dhi\UserBundle\Entity\Support $support
     */
    public function removeSupport(\Dhi\UserBundle\Entity\Support $support)
    {
        $this->support->removeElement($support);
    }

    /**
     * Get support
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSupport()
    {
        return $this->support;
    }
}
