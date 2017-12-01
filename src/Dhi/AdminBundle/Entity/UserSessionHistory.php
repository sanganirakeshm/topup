<?php

namespace Dhi\AdminBundle\Entity;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Index as Index;
/**
 * UserSessionHistory
 *
 * @ORM\Table(name="user_session_history",indexes={@Index(name="user_session_history_start_time_idx", columns={"start_time"})}))
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\UserSessionHistoryRepository")
 */
class UserSessionHistory {

	/**
	 * @var integer
	 *
	 * @ORM\Column(name="id", type="integer")
	 * @ORM\Id
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;

	/**
	 * @ORM\Column(name="username", type="string", nullable=true)
	 */
	protected $userName;

	/**
	 * @ORM\Column(name="email", type="string", nullable=true)
	 */
	protected $email;

	/**
	 * @ORM\Column(name="nasname", type="string", nullable=true)
	 */
	protected $nasName;

	/**
	 * @ORM\Column(name="start_time", type="string", nullable=true)
	 */
	protected $startTime;

	/**
	 * @ORM\Column(name="stop_time", type="string", nullable=true)
	 */
	protected $stopTime;

	/**
	 * @ORM\Column(name="caller_id", type="string", nullable=true)
	 */
	protected $callerId;

	/**
	 * @ORM\Column(name="called_id", type="string", nullable=true)
	 */
	protected $calledId;

	/**
	 * @ORM\Column(name="framed_address", type="string", nullable=true)
	 */
	protected $framedAddress;

	/**
	 * @ORM\Column(name="is_refunded", type="string", nullable=true)
	 */
	protected $isRefunded;

	/**
     * @ORM\Column(name="start_date_time", type="datetime", nullable=true)     
     */
    protected $startDateTime;

    /**
     * @ORM\Column(name="stop_date_time", type="datetime", nullable=true)     
     */
    protected $stopDateTime;

	/**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Set userName
	 *
	 * @param string $userName
	 * @return UserSessionHistory
	 */
	public function setUserName($userName) {
		$this->userName = $userName;

		return $this;
	}

	/**
	 * Get userName
	 *
	 * @return string
	 */
	public function getUserName() {
		return $this->userName;
	}

	/**
	 * Set nasName
	 *
	 * @param string $nasName
	 * @return UserSessionHistory
	 */
	public function setNasName($nasName) {
		$this->nasName = $nasName;

		return $this;
	}

	/**
	 * Get nasName
	 *
	 * @return string
	 */
	public function getNasName() {
		return $this->nasName;
	}

	/**
	 * Set startTime
	 *
	 * @param string $startTime
	 * @return UserSessionHistory
	 */
	public function setStartTime($startTime) {
		$this->startTime = $startTime;

		return $this;
	}

	/**
	 * Get startTime
	 *
	 * @return string
	 */
	public function getStartTime() {
		return $this->startTime;
	}

	/**
	 * Set stopTime
	 *
	 * @param string $stopTime
	 * @return UserSessionHistory
	 */
	public function setStopTime($stopTime) {
		$this->stopTime = $stopTime;

		return $this;
	}

	/**
	 * Get stopTime
	 *
	 * @return string
	 */
	public function getStopTime() {
		return $this->stopTime;
	}

	/**
	 * Set callerId
	 *
	 * @param string $callerId
	 * @return UserSessionHistory
	 */
	public function setCallerId($callerId) {
		$this->callerId = $callerId;

		return $this;
	}

	/**
	 * Get callerId
	 *
	 * @return string
	 */
	public function getCallerId() {
		return $this->callerId;
	}

	/**
	 * Set calledId
	 *
	 * @param string $calledId
	 * @return UserSessionHistory
	 */
	public function setCalledId($calledId) {
		$this->calledId = $calledId;

		return $this;
	}

	/**
	 * Get calledId
	 *
	 * @return string
	 */
	public function getCalledId() {
		return $this->calledId;
	}

	/**
	 * Set framedAddress
	 *
	 * @param string $framedAddress
	 * @return UserSessionHistory
	 */
	public function setFramedAddress($framedAddress) {
		$this->framedAddress = $framedAddress;

		return $this;
	}

	/**
	 * Get framedAddress
	 *
	 * @return string
	 */
	public function getFramedAddress() {
		return $this->framedAddress;
	}

	/**
	 * Set isRefunded
	 *
	 * @param string $isRefunded
	 * @return UserSessionHistory
	 */
	public function setIsRefunded($isRefunded) {
		$this->isRefunded = $isRefunded;

		return $this;
	}

	/**
	 * Get isRefunded
	 *
	 * @return string
	 */
	public function getIsRefunded() {
		return $this->isRefunded;
	}

	/**
	 * Set email
	 *
	 * @param string $email
	 * @return UserSessionHistory
	 */
	public function setEmail($email) {
		$this->email = $email;

		return $this;
	}

	/**
	 * Get email
	 *
	 * @return string
	 */
	public function getEmail() {
		return $this->email;
	}


    /**
     * Set startDateTime
     *
     * @param \DateTime $startDateTime
     * @return UserSessionHistory
     */
    public function setStartDateTime($startDateTime)
    {
        $this->startDateTime = $startDateTime;

        return $this;
    }


    /**
     * Get startDateTime
     *
     * @return \DateTime 
     */
    public function getStartDateTime()
    {
        return $this->startDateTime;
    }

    /**
     * Set stopDateTime
     *
     * @param \DateTime $stopDateTime
     * @return UserSessionHistory
     */
    public function setStopDateTime($stopDateTime)
    {
        $this->stopDateTime = $stopDateTime;

        return $this;
    }

    /**
     * Get stopDateTime
     *
     * @return \DateTime 
     */
    public function getStopDateTime()
    {
        return $this->stopDateTime;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserSessionHistory
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
