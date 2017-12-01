<?php
// src/Acme/UserBundle/Entity/User.php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="user_login_log")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserLoginLogRepository")
 */
class UserLoginLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="ip_address", type="string", length=15, nullable=true)
     */
    protected $ipAddress;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     */
    protected $country;
    
//     /**
//      * @ORM\ManyToOne(targetEntity="ServiceLocation")
//      * @ORM\JoinColumn(name="serviceLocation", referencedColumnName="id")
//      */
//     protected $serviceLocation;
    
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;    
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $whiteLabel;
    
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
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return UserLoginLog
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserLoginLog
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
     * @return UserLoginLog
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
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return UserLoginLog
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

    /**
     * Set whiteLabel
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel
     * @return UserLoginLog
     */
    public function setWhiteLabel(\Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel = null)
    {
        $this->whiteLabel = $whiteLabel;

        return $this;
    }

    /**
     * Get whiteLabel
     *
     * @return \Dhi\AdminBundle\Entity\WhiteLabel 
     */
    public function getWhiteLabel()
    {
        return $this->whiteLabel;
    }
}
