<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\Service;


/**
 * @ORM\Entity
 * @ORM\Table(name="service_activation_failure")
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\ServiceActivationFailureRepository")
 */

class ServiceActivationFailure {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="servicePurchases")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Service")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $services;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="ServicePurchase")
     * @ORM\JoinColumn(name="service_purchase_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $servicePurchases;

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
     * @var string
     *
     * @ORM\Column(type="string", name="failure_description", length=255)
     */
    protected $failureDescription;

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
     * @return ServiceActivationFailure
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
     * @return ServiceActivationFailure
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
     * Set failureDescription
     *
     * @param string $failureDescription
     * @return ServiceActivationFailure
     */
    public function setFailureDescription($failureDescription)
    {
        $this->failureDescription = $failureDescription;

        return $this;
    }

    /**
     * Get failureDescription
     *
     * @return string 
     */
    public function getFailureDescription()
    {
        return $this->failureDescription;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ServiceActivationFailure
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
     * @return ServiceActivationFailure
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
     * @return ServiceActivationFailure
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
     * @return ServiceActivationFailure
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
     * Set servicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases
     * @return ServiceActivationFailure
     */
    public function setServicePurchases(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases = null)
    {
        $this->servicePurchases = $servicePurchases;

        return $this;
    }

    /**
     * Get servicePurchases
     *
     * @return \Dhi\ServiceBundle\Entity\ServicePurchase 
     */
    public function getServicePurchases()
    {
        return $this->servicePurchases;
    }
}
