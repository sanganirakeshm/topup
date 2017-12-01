<?php
namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\ServiceBundle\Entity\Package;
use Dhi\AdminBundle\Entity\IpAddressZone;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * @ORM\Entity
 * @ORM\Table(name="service",indexes={
        @Index(name="service_name_idx", columns={"name"})
    })
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\ServiceRepository")
 */
class Service
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
     * @ORM\Column(name="name", type="string", length=50, nullable=false)
     */
    protected $name;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     */
    protected $status;
    
    /**
     * @ORM\OneToMany(targetEntity="CountrywiseService", mappedBy="services")
     */
    protected $countrywiseService;

    /**
     * @ORM\OneToMany(targetEntity="UserService", mappedBy="service")
     */
    protected $userServices;

    /**
     * @ORM\ManyToMany(targetEntity="Dhi\AdminBundle\Entity\IpAddressZone", mappedBy="services")
     */
    protected $ipAddressZones;

    /**
     * @ORM\ManyToMany(targetEntity="Compensation", mappedBy="services")
     *
     */
    protected $compensations;

    /**
     * @ORM\ManyToMany(targetEntity="Dhi\AdminBundle\Entity\Business", mappedBy="services")
     *
     */
    protected $businesses;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\AdminBundle\Entity\UserSuspendHistory", mappedBy="service")
     */
    protected $userSuspendHistory;
    
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
     * Constructor
     */
    public function __construct()
    {
        $this->countrywiseService = new \Doctrine\Common\Collections\ArrayCollection();
        $this->userServices = new \Doctrine\Common\Collections\ArrayCollection();
        $this->ipAddressZones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->compensations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->businesses = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Service
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
     * Set status
     *
     * @param boolean $status
     * @return Service
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
     * Add countrywiseService
     *
     * @param \Dhi\UserBundle\Entity\CountrywiseService $countrywiseService
     * @return Service
     */
    public function addCountrywiseService(\Dhi\UserBundle\Entity\CountrywiseService $countrywiseService)
    {
        $this->countrywiseService[] = $countrywiseService;

        return $this;
    }

    /**
     * Remove countrywiseService
     *
     * @param \Dhi\UserBundle\Entity\CountrywiseService $countrywiseService
     */
    public function removeCountrywiseService(\Dhi\UserBundle\Entity\CountrywiseService $countrywiseService)
    {
        $this->countrywiseService->removeElement($countrywiseService);
    }

    /**
     * Get countrywiseService
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCountrywiseService()
    {
        return $this->countrywiseService;
    }

    /**
     * Add userServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $userServices
     * @return Service
     */
    public function addUserService(\Dhi\UserBundle\Entity\UserService $userServices)
    {
        $this->userServices[] = $userServices;

        return $this;
    }

    /**
     * Remove userServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $userServices
     */
    public function removeUserService(\Dhi\UserBundle\Entity\UserService $userServices)
    {
        $this->userServices->removeElement($userServices);
    }

    /**
     * Get userServices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserServices()
    {
        return $this->userServices;
    }

    /**
     * Add ipAddressZones
     *
     * @param \Dhi\AdminBundle\Entity\IpAddressZone $ipAddressZones
     * @return Service
     */
    public function addIpAddressZone(\Dhi\AdminBundle\Entity\IpAddressZone $ipAddressZones)
    {
        $this->ipAddressZones[] = $ipAddressZones;

        return $this;
    }

    /**
     * Remove ipAddressZones
     *
     * @param \Dhi\AdminBundle\Entity\IpAddressZone $ipAddressZones
     */
    public function removeIpAddressZone(\Dhi\AdminBundle\Entity\IpAddressZone $ipAddressZones)
    {
        $this->ipAddressZones->removeElement($ipAddressZones);
    }

    /**
     * Get ipAddressZones
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getIpAddressZones()
    {
        return $this->ipAddressZones;
    }

    /**
     * Add compensations
     *
     * @param \Dhi\UserBundle\Entity\Compensation $compensations
     * @return Service
     */
    public function addCompensation(\Dhi\UserBundle\Entity\Compensation $compensations)
    {
        $this->compensations[] = $compensations;

        return $this;
    }

    /**
     * Remove compensations
     *
     * @param \Dhi\UserBundle\Entity\Compensation $compensations
     */
    public function removeCompensation(\Dhi\UserBundle\Entity\Compensation $compensations)
    {
        $this->compensations->removeElement($compensations);
    }

    /**
     * Get compensations
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCompensations()
    {
        return $this->compensations;
    }

    /**
     * Add businesses
     *
     * @param \Dhi\AdminBundle\Entity\Business $businesses
     * @return Service
     */
    public function addBusiness(\Dhi\AdminBundle\Entity\Business $businesses)
    {
        $this->businesses[] = $businesses;

        return $this;
    }

    /**
     * Remove businesses
     *
     * @param \Dhi\AdminBundle\Entity\Business $businesses
     */
    public function removeBusiness(\Dhi\AdminBundle\Entity\Business $businesses)
    {
        $this->businesses->removeElement($businesses);
    }

    /**
     * Get businesses
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getBusinesses()
    {
        return $this->businesses;
    }

    /**
     * Add userSuspendHistory
     *
     * @param \Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory
     * @return Service
     */
    public function addUserSuspendHistory(\Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory)
    {
        $this->userSuspendHistory[] = $userSuspendHistory;

        return $this;
    }

    /**
     * Remove userSuspendHistory
     *
     * @param \Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory
     */
    public function removeUserSuspendHistory(\Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory)
    {
        $this->userSuspendHistory->removeElement($userSuspendHistory);
    }

    /**
     * Get userSuspendHistory
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserSuspendHistory()
    {
        return $this->userSuspendHistory;
    }
}
