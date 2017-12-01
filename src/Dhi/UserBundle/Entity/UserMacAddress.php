<?php

namespace Dhi\UserBundle\Entity;

use Symfony\Component\Validator\Constraint;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="user_mac_address")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserMacAddressRepository")
 */
class UserMacAddress
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userMacAddress")
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
     * @var integer
     *
     * @ORM\Column(name="sequence_number", type="integer", nullable=true)
     */
    
    protected $sequenceNumber;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;
	
    /**
     * @var \string
     *
     * @ORM\Column(name="mac_already_exist_date", type="string", nullable=true)
     * 
     */
    protected $macAlreadyExistDate;
	
	/**
     * @var string
     *
     * @ORM\Column(name="token", type="string", nullable=true)
     */
    protected $token;
	
	
    

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
     * @return UserMacAddress
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
     * Set sequenceNumber
     *
     * @param integer $sequenceNumber
     * @return UserMacAddress
     */
    public function setSequenceNumber($sequenceNumber)
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }

    /**
     * Get sequenceNumber
     *
     * @return integer 
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserMacAddress
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
     * @return UserMacAddress
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
     * @return UserMacAddress
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
     * Set token
     *
     * @param string $token
     * @return UserMacAddress
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set macAlreadyExistDate
     *
     * @param \DateTime $macAlreadyExistDate
     * @return UserMacAddress
     */
    public function setMacAlreadyExistDate($macAlreadyExistDate)
    {
        $this->macAlreadyExistDate = $macAlreadyExistDate;

        return $this;
    }

    /**
     * Get macAlreadyExistDate
     *
     * @return \DateTime 
     */
    public function getMacAlreadyExistDate()
    {
        return $this->macAlreadyExistDate;
    }
}
