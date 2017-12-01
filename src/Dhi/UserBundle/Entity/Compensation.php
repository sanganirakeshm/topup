<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\Index as Index;

/**
 *
 * @ORM\Table(name="compensation",indexes={
        @Index(name="compensation_is_active_idx", columns={"is_active"}),
        @Index(name="compensation_is_instance_idx", columns={"is_instance"})
    })
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\CompensationRepository")
 */
class Compensation
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
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="admin_id", type="integer", nullable=true)
     */
    protected $admin_id;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="isp_hours", type="integer", nullable=true)
     */
    protected $ispHours;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="iptv_hours", type="integer", nullable=true)
     */
    protected $iptvDays;
    
    /**
     * @ORM\ManyToMany(targetEntity="Service", inversedBy="compensations")
     * @ORM\JoinTable(name="compensation_services",
     *      joinColumns={@ORM\JoinColumn(name="compensation_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="service_id", referencedColumnName="id")}
     * )
     */
    protected $services;
    
    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="compensations")
     * @ORM\JoinTable(name="compensation_customers",
     *      joinColumns={@ORM\JoinColumn(name="compensation_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    protected $users;
    
    /**
     * @ORM\ManyToMany(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation", inversedBy="compensations")
     * @ORM\JoinTable(name="compensation_service_locations",
     *      joinColumns={@ORM\JoinColumn(name="compensation_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="service_location_id", referencedColumnName="id")}
     * )
     */
    protected $serviceLocations;
    
    /**
     * @ORM\Column(name="type", type="string", columnDefinition="ENUM('ServiceLocation', 'Customer')", options={"default":"", "comment":"ServiceLocation, Customer"})
     */
    protected $type;
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Completed', 'Inprogress', 'Queued')", options={"default":"Queued", "comment":"Queued, Inprogress, Completed"})
     */
    protected $status = 'Queued';
    
    /**
     * @ORM\Column(name="is_active", type="boolean", nullable=false, options={"default":true})
     */
    protected $isActive = true;

    /**
     * @ORM\Column(name="is_instance", type="boolean", nullable=false, options={"default":false})
     */
    protected $isInstance = false;
    
    /**
     * @ORM\Column(name="is_email_active", type="boolean", nullable=false, options={"default":false})
     */
    protected $isEmailActive = true;
    
    /**
     * @var string
     *
     * @ORM\Column(name="email_subject", type="string", length=255, nullable=true)
     */
    protected $emailSubject;
    
    /**
     * @var text
     * @ORM\Column(name="email_content", type="text", nullable=true)
     */
    protected $emailContent;


    /**
     * @ORM\Column(name="is_email_sent_to_admin", type="boolean", nullable=false, options={"default":false})
     */
    protected $isEmailSentToAdmin = false;

    /**
     * @var text
     * @ORM\Column(name="note", type="text", nullable=true)
     */
    protected $note;

    /**
     * @ORM\OneToMany(targetEntity="CompensationUserService", mappedBy="compensation")
     */
    protected $userService;

    /**
     * @var text
     * @ORM\Column(name="reason", type="text", nullable=true)
     */
    protected $reason;
    
    /**
     * @ORM\Column(name="executed_at", type="datetime", nullable=true)
     *
     */
    protected $executedAt;
    
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;

    /**
     * @ORM\Column(name="is_started", type="boolean", nullable=false, options={"default":false})
     */
    protected $isStarted = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocations = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set title
     *
     * @param string $title
     * @return Compensation
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string 
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set admin_id
     *
     * @param integer $adminId
     * @return Compensation
     */
    public function setAdminId($adminId)
    {
        $this->admin_id = $adminId;

        return $this;
    }

    /**
     * Get admin_id
     *
     * @return integer 
     */
    public function getAdminId()
    {
        return $this->admin_id;
    }

    /**
     * Set ispHours
     *
     * @param integer $ispHours
     * @return Compensation
     */
    public function setIspHours($ispHours)
    {
        $this->ispHours = $ispHours;

        return $this;
    }

    /**
     * Get ispHours
     *
     * @return integer 
     */
    public function getIspHours()
    {
        return $this->ispHours;
    }

    /**
     * Set iptvDays
     *
     * @param integer $iptvDays
     * @return Compensation
     */
    public function setIptvDays($iptvDays)
    {
        $this->iptvDays = $iptvDays;

        return $this;
    }

    /**
     * Get iptvDays
     *
     * @return integer 
     */
    public function getIptvDays()
    {
        return $this->iptvDays;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Compensation
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string 
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Compensation
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return Compensation
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isEmailActive
     *
     * @param boolean $isEmailActive
     * @return Compensation
     */
    public function setIsEmailActive($isEmailActive)
    {
        $this->isEmailActive = $isEmailActive;

        return $this;
    }

    /**
     * Get isEmailActive
     *
     * @return boolean 
     */
    public function getIsEmailActive()
    {
        return $this->isEmailActive;
    }

    /**
     * Set emailSubject
     *
     * @param string $emailSubject
     * @return Compensation
     */
    public function setEmailSubject($emailSubject)
    {
        $this->emailSubject = $emailSubject;

        return $this;
    }

    /**
     * Get emailSubject
     *
     * @return string 
     */
    public function getEmailSubject()
    {
        return $this->emailSubject;
    }

    /**
     * Set emailContent
     *
     * @param string $emailContent
     * @return Compensation
     */
    public function setEmailContent($emailContent)
    {
        $this->emailContent = $emailContent;

        return $this;
    }

    /**
     * Get emailContent
     *
     * @return string 
     */
    public function getEmailContent()
    {
        return $this->emailContent;
    }

    /**
     * Add services
     *
     * @param \Dhi\UserBundle\Entity\Service $services
     * @return Compensation
     */
    public function addService(\Dhi\UserBundle\Entity\Service $services)
    {
        $this->services[] = $services;

        return $this;
    }

    /**
     * Remove services
     *
     * @param \Dhi\UserBundle\Entity\Service $services
     */
    public function removeService(\Dhi\UserBundle\Entity\Service $services)
    {
        $this->services->removeElement($services);
    }

    /**
     * Get services
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Add users
     *
     * @param \Dhi\UserBundle\Entity\User $users
     * @return Compensation
     */
    public function addUser(\Dhi\UserBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Dhi\UserBundle\Entity\User $users
     */
    public function removeUser(\Dhi\UserBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return Compensation
     */
    public function addServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations[] = $serviceLocations;

        return $this;
    }

    /**
     * Remove serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     */
    public function removeServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations->removeElement($serviceLocations);
    }

    /**
     * Get serviceLocations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocations()
    {
        return $this->serviceLocations;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Compensation
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
     * @return Compensation
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
     * Add userService
     *
     * @param \Dhi\UserBundle\Entity\CompensationUserService $userService
     * @return Compensation
     */
    public function addUserService(\Dhi\UserBundle\Entity\CompensationUserService $userService)
    {
        $this->userService[] = $userService;

        return $this;
    }

    /**
     * Remove userService
     *
     * @param \Dhi\UserBundle\Entity\CompensationUserService $userService
     */
    public function removeUserService(\Dhi\UserBundle\Entity\CompensationUserService $userService)
    {
        $this->userService->removeElement($userService);
    }

    /**
     * Get userService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserService()
    {
        return $this->userService;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return Compensation
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return Compensation
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set executedAt
     *
     * @param \DateTime $executedAt
     * @return Compensation
     */
    public function setExecutedAt($executedAt)
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    /**
     * Get executedAt
     *
     * @return \DateTime 
     */
    public function getExecutedAt()
    {
        return $this->executedAt;
    }

    /**
     * Set isStarted
     *
     * @param boolean $isStarted
     * @return Compensation
     */
    public function setIsStarted($isStarted)
    {
        $this->isStarted = $isStarted;

        return $this;
    }

    /**
     * Get isStarted
     *
     * @return boolean 
     */
    public function getIsStarted()
    {
        return $this->isStarted;
    }

    /**
     * Set isInstance
     *
     * @param boolean $isInstance
     * @return Compensation
     */
    public function setIsInstance($isInstance)
    {
        $this->isInstance = $isInstance;

        return $this;
    }

    /**
     * Get isInstance
     *
     * @return boolean 
     */
    public function getIsInstance()
    {
        return $this->isInstance;
    }

    /**
     * Set isEmailSentToAdmin
     *
     * @param boolean $isEmailSentToAdmin
     * @return Compensation
     */
    public function setIsEmailSentToAdmin($isEmailSentToAdmin)
    {
        $this->isEmailSentToAdmin = $isEmailSentToAdmin;

        return $this;
    }

    /**
     * Get isEmailSentToAdmin
     *
     * @return boolean 
     */
    public function getIsEmailSentToAdmin()
    {
        return $this->isEmailSentToAdmin;
    }
}
