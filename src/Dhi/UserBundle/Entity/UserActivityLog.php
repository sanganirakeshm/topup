<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping\Index as Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="user_activity_log",indexes={@Index(name="user_log_idx", columns={"user"})}))
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserActivityLogRepository")
 */
class UserActivityLog {
   
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(name="user", type="string", length=255, nullable=true)
     */
    protected $user;
    
    /**
     * @var string
     * @ORM\Column(name="admin", type="string", length=255, nullable=true)
     */
    protected $admin;
    
    /**
     * @var string
     * @ORM\Column(name="activity", type="string", length=255)
     */
    protected $activity;
    
    /**
     * @var text
     * @ORM\Column(name="description", type="text")
     */
    protected $description;

    /**
     * @var string
     * @ORM\Column(name="visited_url", type="string", length=255)
     */
    protected $visitedUrl;
    
    /**
     * @var string
     * @ORM\Column(name="session_id", type="string", length=255, nullable=false)
     */
    protected $sessionId;
    
    /**
     * @var string
     * @ORM\Column(name="ip", type="string", length=55)
     */
    protected $ip;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", name="timestamp")
     */
    protected $timestamp;

    /**
     * @ORM\Column(name="site_id", type="integer", nullable=true)
     */
    protected $whiteLabelId;
    
    public function saveActivityLog($data, $objActivityLog, $em, $userId = null) {
        
        if (isset($data['admin']) && !empty($data['admin'])) {
            
            $objActivityLog->setAdmin($data['admin']);
        }
        
        $objActivityLog->setUser((isset($data['user']) && !empty($data['user'])) ? $data['user'] : $userId);
        $objActivityLog->setActivity($data['activity']);
        $objActivityLog->setDescription($data['description']);
        $objActivityLog->setIp($data['ip']);
        $objActivityLog->setSessionId($data['sessionId']);
        $objActivityLog->setVisitedUrl($data['url']);
        
        $em->persist($objActivityLog);
        $em->flush();
    }    

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
     * Set user
     *
     * @param string $user
     * @return UserActivityLog
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return string 
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set admin
     *
     * @param string $admin
     * @return UserActivityLog
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Get admin
     *
     * @return string 
     */
    public function getAdmin()
    {
        return $this->admin;
    }

    /**
     * Set activity
     *
     * @param string $activity
     * @return UserActivityLog
     */
    public function setActivity($activity)
    {
        $this->activity = $activity;

        return $this;
    }

    /**
     * Get activity
     *
     * @return string 
     */
    public function getActivity()
    {
        return $this->activity;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return UserActivityLog
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set visitedUrl
     *
     * @param string $visitedUrl
     * @return UserActivityLog
     */
    public function setVisitedUrl($visitedUrl)
    {
        $this->visitedUrl = $visitedUrl;

        return $this;
    }

    /**
     * Get visitedUrl
     *
     * @return string 
     */
    public function getVisitedUrl()
    {
        return $this->visitedUrl;
    }

    /**
     * Set sessionId
     *
     * @param string $sessionId
     * @return UserActivityLog
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * Get sessionId
     *
     * @return string 
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set ip
     *
     * @param string $ip
     * @return UserActivityLog
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string 
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return UserActivityLog
     */
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime 
     */
    public function getTimestamp()
    {
        return $this->timestamp;
    }

    /**
     * Set whiteLabelId
     *
     * @param integer $whiteLabelId
     * @return UserActivityLog
     */
    public function setWhiteLabelId($whiteLabelId)
    {
        $this->whiteLabelId = $whiteLabelId;

        return $this;
    }

    /**
     * Get whiteLabelId
     *
     * @return integer 
     */
    public function getWhiteLabelId()
    {
        return $this->whiteLabelId;
    }
}
