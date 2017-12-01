<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="paypal_credentials")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\PaypalCredentialsRepository")
 */
class PaypalCredentials {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="paypal_id", type="string", length=50, nullable=true)
     */
    private $PaypalId;

    /**
     *
     * @ORM\ManyToOne(targetEntity="ServiceLocation", inversedBy="paypalCredentialLocation")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocations;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Country", inversedBy="payPalCredentials")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     */
    protected $country;

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
     * @ORM\Column(name="ip_address", type="string", length=50, nullable=true)
     */
    private $ipAddress;


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
     * Set PaypalId
     *
     * @param string $paypalId
     * @return PaypalCredentials
     */
    public function setPaypalId($paypalId)
    {
        $this->PaypalId = $paypalId;

        return $this;
    }

    /**
     * Get PaypalId
     *
     * @return string 
     */
    public function getPaypalId()
    {
        return $this->PaypalId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PaypalCredentials
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
     * @return PaypalCredentials
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
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return PaypalCredentials
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
     * Set serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return PaypalCredentials
     */
    public function setServiceLocations(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations = null)
    {
        $this->serviceLocations = $serviceLocations;

        return $this;
    }

    /**
     * Get serviceLocations
     *
     * @return \Dhi\AdminBundle\Entity\ServiceLocation 
     */
    public function getServiceLocations()
    {
        return $this->serviceLocations;
    }

    /**
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return PaypalCredentials
     */
    public function setCountry(\Dhi\UserBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Dhi\UserBundle\Entity\Country 
     */
    public function getCountry()
    {
        return $this->country;
    }
}
