<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;

/**
 * IpAddressZone
 *
 * @ORM\Table(name="ip_address_zone")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\IpAddressZoneRepository")
 * @ORM\HasLifecycleCallbacks
 * 
 */

class IpAddressZone
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
     * @ORM\Column(name="from_ip_address", type="string", length=15, nullable=false)
     */
    protected $fromIpAddress;
    
    /**
     * @ORM\Column(name="to_ip_address", type="string", length=15, nullable=false)
     */
    protected $toIpAddress;
    
    /**
     * @ORM\Column(name="from_ip_address_long", type="bigint", nullable=true)
     */
    protected $fromIpAddressLong;
    
    /**
     * @ORM\Column(name="to_ip_address_long", type="bigint", nullable=true)
     */
    protected $toIpAddressLong;
    
    /**
     * @ORM\Column(name="is_milstar_enabled", type="boolean", nullable=true, options={"default" = 0})
     */
    protected $isMilstarEnabled = false;
    
    /**
     * @ORM\Column(name="milstar_fac_number", type="string", length=8, nullable=true)
     */
    protected $milstarFacNumber;
    
    /**
    *
    * @ORM\ManyToMany(targetEntity="Dhi\UserBundle\Entity\Service", inversedBy="ipAddressZones")
    * @ORM\JoinTable(name="ip_address_zone_services")
    */
    protected $services;
    
    /**
     * @ORM\ManyToOne(targetEntity="ServiceLocation", inversedBy="ipAddressZones")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocation;
        
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->services = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set fromIpAddress
     *
     * @param string $fromIpAddress
     * @return IpAddressZone
     */
    public function setFromIpAddress($fromIpAddress)
    {
        $this->fromIpAddress = $fromIpAddress;
        
        return $this;
    }

    /**
     * Get fromIpAddress
     *
     * @return string 
     */
    public function getFromIpAddress()
    {
        return $this->fromIpAddress;
    }

    /**
     * Set toIpAddress
     *
     * @param string $toIpAddress
     * @return IpAddressZone
     */
    public function setToIpAddress($toIpAddress)
    {
        $this->toIpAddress = $toIpAddress;

        return $this;
    }

    /**
     * Get toIpAddress
     *
     * @return string 
     */
    public function getToIpAddress()
    {
        return $this->toIpAddress;
    }

    /**
     * Set serviceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation
     * @return IpAddressZone
     */
    public function setServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation = null)
    {
        $this->serviceLocation = $serviceLocation;

        return $this;
    }

    /**
     * Get serviceLocation
     *
     * @return \Dhi\AdminBundle\Entity\ServiceLocation 
     */
    public function getServiceLocation()
    {
        return $this->serviceLocation;
    }

    /**
     * Set isMilstarEnabled
     *
     * @param boolean $isMilstarEnabled
     * @return IpAddressZone
     */
    public function setIsMilstarEnabled($isMilstarEnabled)
    {
        $this->isMilstarEnabled = $isMilstarEnabled;

        return $this;
    }

    /**
     * Get isMilstarEnabled
     *
     * @return boolean 
     */
    public function getIsMilstarEnabled()
    {
        return $this->isMilstarEnabled;
    }

    /**
     * Set milstarFacNumber
     *
     * @param string $milstarFacNumber
     * @return IpAddressZone
     */
    public function setMilstarFacNumber($milstarFacNumber)
    {
        $this->milstarFacNumber = $milstarFacNumber;

        return $this;
    }

    /**
     * Get milstarFacNumber
     *
     * @return string 
     */
    public function getMilstarFacNumber()
    {
        return $this->milstarFacNumber;
    }

    /**
     * Set iptvDiscount
     *
     * @param integer $iptvDiscount
     * @return IpAddressZone
     */
    public function setIptvDiscount($iptvDiscount)
    {
        $this->iptvDiscount = $iptvDiscount;

        return $this;
    }

    /**
     * Get iptvDiscount
     *
     * @return integer 
     */
    public function getIptvDiscount()
    {
        return $this->iptvDiscount;
    }

    /**
     * Set ispDiscount
     *
     * @param integer $ispDiscount
     * @return IpAddressZone
     */
    public function setIspDiscount($ispDiscount)
    {
        $this->ispDiscount = $ispDiscount;

        return $this;
    }

    /**
     * Get ispDiscount
     *
     * @return integer 
     */
    public function getIspDiscount()
    {
        return $this->ispDiscount;
    }

    /**
     * Set fromIpAddressLong
     * 
     * @param integer $fromIpAddressLong
     * @return IpAddressZone
     */
    public function setFromIpAddressLong($fromIpAddressLong)
    {
        $this->fromIpAddressLong = ip2long($this->fromIpAddress);

        return $this;
    }

    /**
     * Get fromIpAddressLong
     *
     * @return integer 
     */
    public function getFromIpAddressLong()
    {
        return $this->fromIpAddressLong;
    }

    /**
     * Set toIpAddressLong
     * 
     * @param integer $toIpAddressLong
     * @return IpAddressZone
     */
    public function setToIpAddressLong($toIpAddressLong)
    {
        $this->toIpAddressLong = ip2long($this->toIpAddress);

        return $this;
    }

    /**
     * Get toIpAddressLong
     *
     * @return integer 
     */
    public function getToIpAddressLong()
    {
        return $this->toIpAddressLong;
    }
    
    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpdate(){
        $this->fromIpAddressLong = ip2long($this->fromIpAddress);
        $this->toIpAddressLong = ip2long($this->toIpAddress);
    }

    /**
     * Add services
     *
     * @param \Dhi\UserBundle\Entity\Service $services
     * @return IpAddressZone
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
}
