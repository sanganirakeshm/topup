<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ServicePartner
 *
 * @ORM\Entity
 * @ORM\Table(name="service_partners")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ServicePartnerRepository")
 */
class ServicePartner
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
     * @ORM\Column(name="partner_name", type="string", length=100, nullable=false, unique=true)
     */
    protected $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(name="poc_name", type="string", length=50, nullable=true)
     */
    protected $pocName;

    /**
     * @ORM\Column(name="poc_email", type="string", length=50, nullable=true)
     */
    protected $pocEmail;

    /**
     * @ORM\Column(name="poc_phone", type="string", length=50, nullable=true)
     */
    protected $pocPhone;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    protected $isDeleted = false;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\UserService", mappedBy="deActivatedBy")
     */
    protected $deactivatedByUserServices;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $service;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
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
     * @ORM\OneToMany(targetEntity="PartnerPromoCodeBatch", mappedBy="partner")
     */
    protected $batches;

    /**
     * @ORM\Column(name="username", type="string", length=255, nullable=true)
     */
    protected $username;
    
    /**
     * @ORM\Column(name="password", type="string", length=255, nullable=true)
     */
    protected $password;
    
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
     * Set name
     *
     * @param string $name
     * @return ServicePartner
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return ServicePartner
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
     * Set pocName
     *
     * @param string $pocName
     * @return ServicePartner
     */
    public function setPocName($pocName)
    {
        $this->pocName = $pocName;

        return $this;
    }

    /**
     * Get pocName
     *
     * @return string 
     */
    public function getPocName()
    {
        return $this->pocName;
    }

    /**
     * Set pocEmail
     *
     * @param string $pocEmail
     * @return ServicePartner
     */
    public function setPocEmail($pocEmail)
    {
        $this->pocEmail = $pocEmail;

        return $this;
    }

    /**
     * Get pocEmail
     *
     * @return string 
     */
    public function getPocEmail()
    {
        return $this->pocEmail;
    }

    /**
     * Set pocPhone
     *
     * @param string $pocPhone
     * @return ServicePartner
     */
    public function setPocPhone($pocPhone)
    {
        $this->pocPhone = $pocPhone;

        return $this;
    }

    /**
     * Get pocPhone
     *
     * @return string 
     */
    public function getPocPhone()
    {
        return $this->pocPhone;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return ServicePartner
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
     * @return ServicePartner
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
     * @return ServicePartner
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
     * Set service
     *
     * @param \Dhi\UserBundle\Entity\Service $service
     * @return ServicePartner
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
     * Set createdBy
     *
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return ServicePartner
     */
    public function setCreatedBy(\Dhi\UserBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->batches = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return ServicePartner
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
     * Set username
     *
     * @param string $username
     * @return ServicePartner
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string 
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return ServicePartner
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string 
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Add deactivatedByUserServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $deactivatedByUserServices
     * @return ServicePartner
     */
    public function addDeactivatedByUserService(\Dhi\UserBundle\Entity\UserService $deactivatedByUserServices)
    {
        $this->deactivatedByUserServices[] = $deactivatedByUserServices;

        return $this;
    }

    /**
     * Remove deactivatedByUserServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $deactivatedByUserServices
     */
    public function removeDeactivatedByUserService(\Dhi\UserBundle\Entity\UserService $deactivatedByUserServices)
    {
        $this->deactivatedByUserServices->removeElement($deactivatedByUserServices);
    }

    /**
     * Get deactivatedByUserServices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDeactivatedByUserServices()
    {
        return $this->deactivatedByUserServices;
    }

    /**
     * Add batches
     *
     * @param \Dhi\AdminBundle\Entity\PartnerPromoCodeBatch $batches
     * @return ServicePartner
     */
    public function addBatch(\Dhi\AdminBundle\Entity\PartnerPromoCodeBatch $batches)
    {
        $this->batches[] = $batches;

        return $this;
    }

    /**
     * Remove batches
     *
     * @param \Dhi\AdminBundle\Entity\PartnerPromoCodeBatch $batches
     */
    public function removeBatch(\Dhi\AdminBundle\Entity\PartnerPromoCodeBatch $batches)
    {
        $this->batches->removeElement($batches);
    }

    /**
     * Get batches
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBatches()
    {
        return $this->batches;
    }
}
