<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;




/**
 * SetTopBox
 *
 * @ORM\Table(name="settopbox")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\SetTopBoxRepository")
 */
class SetTopBox
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
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
	
	/**
     * @var string
     *
     * @ORM\Column(name="mac_address", type="string")
     */
    protected $macAddress;
	
	/**
     * @ORM\Column(name="given_by", type="string", nullable=false)
     */
    protected $givenBy;
	
	/**
     * @ORM\Column(name="recieved_by", type="string", nullable=true)
     */
    protected $receivedBy;
	
	
	/**
     * @ORM\Column(name="given_at", type="datetime", nullable=true)
     * 
     */
    protected $givenAt;

    /**
     * @ORM\Column(name="received_at", type="datetime", nullable=true)
     * 
     */
    protected $receivedAt;
	
	 /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;
    
	

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
     * Set macAddress
     *
     * @param string $macAddress
     * @return SetTopBox
     */
    public function setMacAddress($macAddress)
    {
        $this->macAddress = $macAddress;

        return $this;
    }

    /**
     * Get macAddress
     *
     * @return string 
     */
    public function getMacAddress()
    {
        return $this->macAddress;
    }

    /**
     * Set givenBy
     *
     * @param string $givenBy
     * @return SetTopBox
     */
    public function setGivenBy($givenBy)
    {
        $this->givenBy = $givenBy;

        return $this;
    }

    /**
     * Get givenBy
     *
     * @return string 
     */
    public function getGivenBy()
    {
        return $this->givenBy;
    }

    /**
     * Set receivedBy
     *
     * @param string $receivedBy
     * @return SetTopBox
     */
    public function setReceivedBy($receivedBy)
    {
        $this->receivedBy = $receivedBy;

        return $this;
    }

    /**
     * Get receivedBy
     *
     * @return string 
     */
    public function getReceivedBy()
    {
        return $this->receivedBy;
    }

    /**
     * Set givenAt
     *
     * @param \DateTime $givenAt
     * @return SetTopBox
     */
    public function setGivenAt($givenAt)
    {
        $this->givenAt = $givenAt;

        return $this;
    }

    /**
     * Get givenAt
     *
     * @return \DateTime 
     */
    public function getGivenAt()
    {
        return $this->givenAt;
    }

    /**
     * Set receivedAt
     *
     * @param \DateTime $receivedAt
     * @return SetTopBox
     */
    public function setReceivedAt($receivedAt)
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * Get receivedAt
     *
     * @return \DateTime 
     */
    public function getReceivedAt()
    {
        return $this->receivedAt;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return SetTopBox
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return SetTopBox
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
}
