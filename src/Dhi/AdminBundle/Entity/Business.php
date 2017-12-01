<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * Business
 *
 * @ORM\Entity
 * @ORM\Table(name="business")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\BusinessRepository")
 */
class Business
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
     * @ORM\Column(name="business_name", type="string", length=100, nullable=false, unique=false)
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
     * @ORM\Column(name="reason", type="string", nullable=false)
     */
    protected $reason;
    
    /**
     * @ORM\ManyToMany(targetEntity="Dhi\UserBundle\Entity\Service", inversedBy="businesses")
     * @ORM\JoinTable(name="business_services",
     *      joinColumns={@ORM\JoinColumn(name="business_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="service_id", referencedColumnName="id")}
     * )
     */
    protected $services;

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
     * @ORM\OneToMany(targetEntity="BusinessPromoCodeBatch", mappedBy="business")
     */
    protected $batches;
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
        $this->batches = new \Doctrine\Common\Collections\ArrayCollection();
    }


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
     * @return Business
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
     * @return Business
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
     * @return Business
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
     * @return Business
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
     * @return Business
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
     * @return Business
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
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return Business
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Business
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
     * @return Business
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
     * Set createdBy
     *
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return Business
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
     * Add batches
     *
     * @param \Dhi\AdminBundle\Entity\BusinessPromoCodeBatch $batches
     * @return Business
     */
    public function addBatch(\Dhi\AdminBundle\Entity\BusinessPromoCodeBatch $batches)
    {
        $this->batches[] = $batches;

        return $this;
    }

    /**
     * Remove batches
     *
     * @param \Dhi\AdminBundle\Entity\BusinessPromoCodeBatch $batches
     */
    public function removeBatch(\Dhi\AdminBundle\Entity\BusinessPromoCodeBatch $batches)
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

    /**
     * Set reason
     *
     * @param string $reason
     * @return Business
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Add services
     *
     * @param \Dhi\UserBundle\Entity\Service $services
     * @return Business
     */
    public function addService(\Dhi\UserBundle\Entity\Service $services)
    {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param \Dhi\UserBundle\Entity\Service $services
     */
    public function removeService(\Dhi\UserBundle\Entity\Service $services)
    {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServices()
    {
        return $this->services;
    }
}
