<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="support")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\SupportRepository")
 */

class Support {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="firstname", type="string", length=150)
     *
     */
    protected $firstname;
    
    /**
     * @ORM\Column(name="lastname", type="string", length=150)
     *
     */
    protected $lastname;
    
    /**
     * @ORM\Column(name="email", type="string", length=255)
     *
     */
    protected $email;
    
    /**
     * @ORM\Column(name="number", type="string", length=50)
     *
     */
    protected $number;
        
    /**
     *
     * @ORM\ManyToOne(targetEntity="SupportCategory", inversedBy="supports")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id")
     */
    protected $category;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="SupportLocation", inversedBy="support")
     * @ORM\JoinColumn(name="support_location_id", referencedColumnName="id")
     */
    protected $location;

    /**
     * @ORM\Column(name="time", type="string", length=255)
     *
     */
    protected $time;

    /**
     * @ORM\Column(name="room_number", type="string", length=20, nullable=true)
     *
     */
    protected $roomNumber;

    /**
     * @ORM\Column(name="building", type="string", length=20, nullable=true)
     *
     */
    protected $building;
    
    /**
     * @ORM\Column(name="message", type="text")
     */
    protected $message;
    
    
    /**
     * @ORM\Column(name="is_sent", type="boolean")
     */
    protected $isSent = false;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $whiteLabel;

    /**
     * @ORM\ManyToOne(targetEntity="Country", inversedBy="support")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     */
    protected $country;

    /**
     * @var integer
     *
     * @ORM\Column(name="ticket_id", type="integer", nullable=true)
     */
    protected $ticketId;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="SupportService", inversedBy="support")
     * @ORM\JoinColumn(name="support_service_id", referencedColumnName="id")
     */
    protected $supportService;

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
     * Set firstname
     *
     * @param string $firstname
     * @return Support
     */
    public function setFirstname($firstname)
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string 
     */
    public function getFirstname()
    {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return Support
     */
    public function setLastname($lastname)
    {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string 
     */
    public function getLastname()
    {
        return $this->lastname;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Support
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

    /**
     * Set number
     *
     * @param string $number
     * @return Support
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return string 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set time
     *
     * @param string $time
     * @return Support
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * Get time
     *
     * @return string 
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set roomNumber
     *
     * @param string $roomNumber
     * @return Support
     */
    public function setRoomNumber($roomNumber)
    {
        $this->roomNumber = $roomNumber;

        return $this;
    }

    /**
     * Get roomNumber
     *
     * @return string 
     */
    public function getRoomNumber()
    {
        return $this->roomNumber;
    }

    /**
     * Set building
     *
     * @param string $building
     * @return Support
     */
    public function setBuilding($building)
    {
        $this->building = $building;

        return $this;
    }

    /**
     * Get building
     *
     * @return string 
     */
    public function getBuilding()
    {
        return $this->building;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Support
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set isSent
     *
     * @param boolean $isSent
     * @return Support
     */
    public function setIsSent($isSent)
    {
        $this->isSent = $isSent;

        return $this;
    }

    /**
     * Get isSent
     *
     * @return boolean 
     */
    public function getIsSent()
    {
        return $this->isSent;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Support
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
     * Set category
     *
     * @param \Dhi\UserBundle\Entity\SupportCategory $category
     * @return Support
     */
    public function setCategory(\Dhi\UserBundle\Entity\SupportCategory $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Dhi\UserBundle\Entity\SupportCategory 
     */
    public function getCategory()
    {
        return $this->category;
    }


    /**
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return Support
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
     * @return Support
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

    /**
     * Set ticketId
     *
     * @param integer $ticketId
     * @return Support
     */
    public function setTicketId($ticketId)
    {
        $this->ticketId = $ticketId;

        return $this;
    }

    /**
     * Get ticketId
     *
     * @return integer 
     */
    public function getTicketId()
    {
        return $this->ticketId;
    }


    /**
     * Set supportService
     *
     * @param \Dhi\UserBundle\Entity\SupportService $supportService
     * @return Support
     */
    public function setSupportService(\Dhi\UserBundle\Entity\SupportService $supportService = null)
    {
        $this->supportService = $supportService;

        return $this;
    }

    /**
     * Get supportService
     *
     * @return \Dhi\UserBundle\Entity\SupportService 
     */
    public function getSupportService()
    {
        return $this->supportService;
    }

    /**
     * Set location
     *
     * @param \Dhi\UserBundle\Entity\SupportLocation $location
     * @return Support
     */
    public function setLocation(\Dhi\UserBundle\Entity\SupportLocation $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \Dhi\UserBundle\Entity\SupportLocation 
     */
    public function getLocation()
    {
        return $this->location;
    }
}
