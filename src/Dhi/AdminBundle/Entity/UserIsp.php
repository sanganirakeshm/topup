<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;
/**
 * Bundle
 *
 * @ORM\Table(name="user_isp")
 * @ORM\Entity
 */
class UserIsp
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
     * @ORM\Column(name="user_id", type="string", nullable=false)
     */
    protected $UserID;


    /**
     * @ORM\Column(name="first_name", type="string", nullable=true)
     */
    protected $FirstName;
    
    /**
     * @ORM\Column(name="last_name", type="string", nullable=true)
     */
    protected $LastName;

    /**
     * @ORM\Column(name="email", type="string", nullable=true)
     */
    protected $Email;

    /**
     * @ORM\Column(name="offer", type="string", nullable=true)
     */
    protected $Offer;
    
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;


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
     * Set UserID
     *
     * @param string $userID
     * @return UserIsp
     */
    public function setUserID($userID)
    {
        $this->UserID = $userID;

        return $this;
    }

    /**
     * Get UserID
     *
     * @return string 
     */
    public function getUserID()
    {
        return $this->UserID;
    }

    /**
     * Set FirstName
     *
     * @param string $firstName
     * @return UserIsp
     */
    public function setFirstName($firstName)
    {
        $this->FirstName = $firstName;

        return $this;
    }

    /**
     * Get FirstName
     *
     * @return string 
     */
    public function getFirstName()
    {
        return $this->FirstName;
    }

    /**
     * Set LastName
     *
     * @param string $lastName
     * @return UserIsp
     */
    public function setLastName($lastName)
    {
        $this->LastName = $lastName;

        return $this;
    }

    /**
     * Get LastName
     *
     * @return string 
     */
    public function getLastName()
    {
        return $this->LastName;
    }

    /**
     * Set Email
     *
     * @param string $email
     * @return UserIsp
     */
    public function setEmail($email)
    {
        $this->Email = $email;

        return $this;
    }

    /**
     * Get Email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->Email;
    }

    /**
     * Set Offer
     *
     * @param string $offer
     * @return UserIsp
     */
    public function setOffer($offer)
    {
        $this->Offer = $offer;

        return $this;
    }

    /**
     * Get Offer
     *
     * @return string 
     */
    public function getOffer()
    {
        return $this->Offer;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserIsp
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
}
