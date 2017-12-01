<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
/**
 * IspIpn
 *
 * @ORM\Table(name="isp_pin")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\IspPinRepository")
 */
class IspPin
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
     * @ORM\ManyToOne(targetEntity="ServiceLocation", inversedBy="ispPin")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocation;
        
    /**
     * @ORM\ManyToOne(targetEntity="Package", inversedBy="ispPin")
     * @ORM\JoinColumn(name="package_id", referencedColumnName="id")
     */
    protected $package;
    
    /**
     * @ORM\Column(name="username", type="string", nullable=false)
     */
    protected $username;
    
    /**
     * @ORM\Column(name="password", type="string", nullable=false)
     */
    protected $password;

    /**
     * @ORM\Column(name="validity", type="integer", nullable=false)
     */
    protected $validity;
	
	
	/**
     * @ORM\Column(name="isp_type", type="string", columnDefinition="enum('Individual', 'Business')", options={"default":"Individual"} )
     */
    protected $ispType;
	
	
	/**
     * @ORM\Column(name="name", type="string", nullable=true)
     */
    protected $name;
	
	/**
     * @ORM\Column(name="email", type="string", nullable=true)
     */
    protected $email;
	
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
     * Set username
     *
     * @param string $username
     * @return IspPin
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
     * @return IspPin
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
     * Set validity
     *
     * @param integer $validity
     * @return IspPin
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * Get validity
     *
     * @return integer 
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return IspPin
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
     * @return IspPin
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
     * Set serviceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation
     * @return IspPin
     */
    public function setServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation = null)
    {
        $this->serviceLocation = $serviceLocation;

        return $this;
    }

    /**
     * Get serviceLocation
     *
     * @return \Dhi\AdminBundle\Entity\ServiceLocation 
     */
    public function getServiceLocation()
    {
        return $this->serviceLocation;
    }

    /**
     * Set package
     *
     * @param \Dhi\AdminBundle\Entity\Package $package
     * @return IspPin
     */
    public function setPackage(\Dhi\AdminBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Dhi\AdminBundle\Entity\Package 
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Set ispType
     *
     * @param string $ispType
     * @return IspPin
     */
    public function setIspType($ispType)
    {
        $this->ispType = $ispType;

        return $this;
    }

    /**
     * Get ispType
     *
     * @return string 
     */
    public function getIspType()
    {
        return $this->ispType;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return IspPin
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
     * Set email
     *
     * @param string $email
     * @return IspPin
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }
}
