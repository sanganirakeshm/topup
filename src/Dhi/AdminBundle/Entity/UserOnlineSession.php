<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;


/**
 * UserSessionHistory
 *
 * @ORM\Table(name="user_online_session")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\UserOnlineSessionRepository")
 */
class UserOnlineSession
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
     * @ORM\Column(name="username", type="string", nullable=true)
     */
    protected $userName;

    /**
     * @ORM\Column(name="nasname", type="string", nullable=true)
     */
    protected $nasName;

    /**
     * @ORM\Column(name="online_since", type="string", nullable=true)
     */
    protected $onlineSince;
    /**
     * @ORM\Column(name="time_online", type="string", nullable=true)
     */
    protected $timeOnline;

    /**
     * @ORM\Column(name="user_ip", type="string", nullable=true)
     */
    protected $userIp;
    /**
     * @ORM\Column(name="nas_id", type="string", nullable=true)
     */
    protected $nasId;
    /**
     * @ORM\Column(name="nas_port", type="string", nullable=true)
     */
    protected $nasPort;
    /**
     * @ORM\Column(name="account_session_id", type="string", nullable=true)
     */
    protected $accountSessionId;
    /**
     * @ORM\Column(name="is_offline", type="string", columnDefinition="ENUM('y','n')")
     */
    protected $isOffline = "n";

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
     * Set userName
     *
     * @param string $userName
     * @return UserOnlineSession
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string 
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * Set nasName
     *
     * @param string $nasName
     * @return UserOnlineSession
     */
    public function setNasName($nasName)
    {
        $this->nasName = $nasName;

        return $this;
    }

    /**
     * Get nasName
     *
     * @return string 
     */
    public function getNasName()
    {
        return $this->nasName;
    }

    /**
     * Set onlineSince
     *
     * @param string $onlineSince
     * @return UserOnlineSession
     */
    public function setOnlineSince($onlineSince)
    {
        $this->onlineSince = $onlineSince;

        return $this;
    }

    /**
     * Get onlineSince
     *
     * @return string 
     */
    public function getOnlineSince()
    {
        return $this->onlineSince;
    }

    /**
     * Set timeOnline
     *
     * @param string $timeOnline
     * @return UserOnlineSession
     */
    public function setTimeOnline($timeOnline)
    {
        $this->timeOnline = $timeOnline;

        return $this;
    }

    /**
     * Get timeOnline
     *
     * @return string 
     */
    public function getTimeOnline()
    {
        return $this->timeOnline;
    }

    /**
     * Set userIp
     *
     * @param string $userIp
     * @return UserOnlineSession
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;

        return $this;
    }

    /**
     * Get userIp
     *
     * @return string 
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * Set nasId
     *
     * @param string $nasId
     * @return UserOnlineSession
     */
    public function setNasId($nasId)
    {
        $this->nasId = $nasId;

        return $this;
    }

    /**
     * Get nasId
     *
     * @return string 
     */
    public function getNasId()
    {
        return $this->nasId;
    }

    /**
     * Set nasPort
     *
     * @param string $nasPort
     * @return UserOnlineSession
     */
    public function setNasPort($nasPort)
    {
        $this->nasPort = $nasPort;

        return $this;
    }

    /**
     * Get nasPort
     *
     * @return string 
     */
    public function getNasPort()
    {
        return $this->nasPort;
    }

    /**
     * Set accountSessionId
     *
     * @param string $accountSessionId
     * @return UserOnlineSession
     */
    public function setAccountSessionId($accountSessionId)
    {
        $this->accountSessionId = $accountSessionId;

        return $this;
    }

    /**
     * Get accountSessionId
     *
     * @return string 
     */
    public function getAccountSessionId()
    {
        return $this->accountSessionId;
    }

    

    /**
     * Set isOffline
     *
     * @param string $isOffline
     * @return UserOnlineSession
     */
    public function setIsOffline($isOffline)
    {
        $this->isOffline = $isOffline;

        return $this;
    }

    /**
     * Get isOffline
     *
     * @return string 
     */
    public function getIsOffline()
    {
        return $this->isOffline;
    }
}
