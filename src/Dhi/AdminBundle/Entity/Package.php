<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;
use Doctrine\ORM\Mapping\Index as Index;

/**
 * Package
 *
 * @ORM\Table(name="package",indexes={@Index(name="package_package_id_idx", columns={"package_id"})}))
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\PackageRepository")
 */
class Package
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
     * @ORM\Column(name="package_id", type="integer", nullable=false)
     */
    protected $packageId;

    /**
     * @ORM\Column(name="package_name", type="string", nullable=false)
     */
    protected $packageName;

    /**
     * @ORM\Column(name="amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $amount;

    /**
     * @ORM\Column(name="total_channels", type="integer", nullable=false)
     */
    protected $totalChannels;

    /**
     * @ORM\Column(name="package_type", type="string", columnDefinition="ENUM('IPTV','ISP','PREMIUM')", options={"comment":"IPTV, ISP ,PREMIUM"})
     */

    protected $packageType;

    /**
     * @ORM\OneToMany(targetEntity="Channel", mappedBy="package")
     */
    protected $channels;

    /**
     * @ORM\OneToMany(targetEntity="Bundle", mappedBy="iptv")
     */
    protected $iptvBundle;

    /**
     * @ORM\OneToMany(targetEntity="Bundle", mappedBy="isp")
     */
    protected $ispBundle;

    /**
     * @ORM\OneToMany(targetEntity="Bundle", mappedBy="regularIptv")
     */
    protected $iptvRegularBundle;

    /**
     * @ORM\OneToMany(targetEntity="Bundle", mappedBy="regularIsp")
     */
    protected $ispRegularBundle;


    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;


    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deers", type="boolean", nullable=false)
     */
    protected $isDeers = false;

    /**
     * @var string
     *
     * @ORM\Column(name="bandwidth", type="string", length=50, nullable=true)
     */
    private $bandwidth = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="validity", type="string", length=50, nullable=true)
     */
    private $validity;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_addons", type="boolean", nullable=false)
     */
    protected $isAddons = false;

    /**
     * @ORM\ManyToOne(targetEntity="ServiceLocation", inversedBy="package")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */

    protected $serviceLocation;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_bundle_plan", type="boolean", nullable=false)
     */
    protected $isBundlePlan = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_hourly_plan", type="boolean", nullable=false)
     */
    protected $isHourlyPlan = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_for_partner", type="boolean", nullable=false)
     */
    protected $isForPartner = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_expired", type="boolean", nullable=false)
     */
    protected $isExpired = false;

    /**
     * @ORM\Column(name="package_namespace", type="string", nullable=true)
     */
    protected $packageNamespace;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_employee", type="boolean", nullable=false)
     */
    protected $isEmployee = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_promotional_plan", type="boolean", nullable=false)
     */
    protected $isPromotionalPlan = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="free_recharge_card", type="boolean", nullable=false)
     */
    protected $freeRechargeCard = false;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->channels = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set packageId
     *
     * @param integer $packageId
     * @return Package
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return integer
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set packageName
     *
     * @param string $packageName
     * @return Package
     */
    public function setPackageName($packageName)
    {
        $this->packageName = $packageName;

        return $this;
    }

    /**
     * Get packageName
     *
     * @return string
     */
    public function getPackageName()
    {
        return $this->packageName;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     * @return Package
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set totalChannels
     *
     * @param integer $totalChannels
     * @return Package
     */
    public function setTotalChannels($totalChannels)
    {
        $this->totalChannels = $totalChannels;

        return $this;
    }

    /**
     * Get totalChannels
     *
     * @return integer
     */
    public function getTotalChannels()
    {
        return $this->totalChannels;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Package
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
     * Add channels
     *
     * @param \Dhi\AdminBundle\Entity\Channel $channels
     * @return Package
     */
    public function addChannel(\Dhi\AdminBundle\Entity\Channel $channels)
    {
        $this->channels[] = $channels;

        return $this;
    }

    /**
     * Remove channels
     *
     * @param \Dhi\AdminBundle\Entity\Channel $channels
     */
    public function removeChannel(\Dhi\AdminBundle\Entity\Channel $channels)
    {
        $this->channels->removeElement($channels);
    }

    /**
     * Get channels
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChannels()
    {
        return $this->channels;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Package
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
     * Set packageType
     *
     * @param string $packageType
     * @return Package
     */
    public function setPackageType($packageType)
    {
        $this->packageType = $packageType;

        return $this;
    }

    /**
     * Get packageType
     *
     * @return string
     */
    public function getPackageType()
    {
        return $this->packageType;
    }

    /**
     * Set serviceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation
     * @return Package
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
     * Set bandwidth
     *
     * @param string $bandwidth
     * @return Package
     */
    public function setBandwidth($bandwidth)
    {
        $this->bandwidth = $bandwidth;

        return $this;
    }

    /**
     * Get bandwidth
     *
     * @return string
     */
    public function getBandwidth()
    {
        return $this->bandwidth;
    }

    /**
     * Set validity
     *
     * @param string $validity
     * @return Package
     */
    public function setValidity($validity)
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * Get validity
     *
     * @return string
     */
    public function getValidity()
    {
        return $this->validity;
    }

    /**
     * Set isDeers
     *
     * @param boolean $isDeers
     * @return Package
     */
    public function setIsDeers($isDeers)
    {
        $this->isDeers = $isDeers;

        return $this;
    }

    /**
     * Get isDeers
     *
     * @return boolean
     */
    public function getIsDeers()
    {
        return $this->isDeers;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Package
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
     * Set isAddons
     *
     * @param boolean $isAddons
     * @return Package
     */
    public function setIsAddons($isAddons)
    {
        $this->isAddons = $isAddons;

        return $this;
    }

    /**
     * Get isAddons
     *
     * @return boolean
     */
    public function getIsAddons()
    {
        return $this->isAddons;
    }

		public function getPackageAmount()
    {
        return $this->packageName.' - $'. $this->amount .'';
    }


    /**
     * Add iptvBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $iptvBundle
     * @return Package
     */
    public function addIptvBundle(\Dhi\AdminBundle\Entity\Bundle $iptvBundle)
    {
        $this->iptvBundle[] = $iptvBundle;

        return $this;
    }

    /**
     * Remove iptvBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $iptvBundle
     */
    public function removeIptvBundle(\Dhi\AdminBundle\Entity\Bundle $iptvBundle)
    {
        $this->iptvBundle->removeElement($iptvBundle);
    }

    /**
     * Get iptvBundle
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIptvBundle()
    {
        return $this->iptvBundle;
    }

    /**
     * Add ispBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $ispBundle
     * @return Package
     */
    public function addIspBundle(\Dhi\AdminBundle\Entity\Bundle $ispBundle)
    {
        $this->ispBundle[] = $ispBundle;

        return $this;
    }

    /**
     * Remove ispBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $ispBundle
     */
    public function removeIspBundle(\Dhi\AdminBundle\Entity\Bundle $ispBundle)
    {
        $this->ispBundle->removeElement($ispBundle);
    }

    /**
     * Get ispBundle
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIspBundle()
    {
        return $this->ispBundle;
    }

    /**
     * Set isBundlePlan
     *
     * @param boolean $isBundlePlan
     * @return Package
     */
    public function setIsBundlePlan($isBundlePlan)
    {
        $this->isBundlePlan = $isBundlePlan;

        return $this;
    }

    /**
     * Get isBundlePlan
     *
     * @return boolean
     */
    public function getIsBundlePlan()
    {
        return $this->isBundlePlan;
    }

    /**
     * Set isForPartner
     *
     * @param boolean $isForPartner
     * @return Package
     */
    public function setIsForPartner($isForPartner)
    {
        $this->isForPartner = $isForPartner;

        return $this;
    }

    /**
     * Get isForPartner
     *
     * @return boolean
     */
    public function getIsForPartner()
    {
        return $this->isForPartner;
    }

    /**
     * Set isExpired
     *
     * @param boolean $isExpired
     * @return Package
     */
    public function setIsExpired($isExpired)
    {
        $this->isExpired = $isExpired;

        return $this;
    }

    /**
     * Get isExpired
     *
     * @return boolean
     */
    public function getIsExpired()
    {
        return $this->isExpired;
    }

    /**
     * Set isHourlyPlan
     *
     * @param boolean $isHourlyPlan
     * @return Package
     */
    public function setIsHourlyPlan($isHourlyPlan)
    {
        $this->isHourlyPlan = $isHourlyPlan;

        return $this;
    }

    /**
     * Get isHourlyPlan
     *
     * @return boolean
     */
    public function getIsHourlyPlan()
    {
        return $this->isHourlyPlan;
    }

    /**
     * Set packageNamespace
     *
     * @param string $packageNamespace
     * @return Package
     */
    public function setPackageNamespace($packageNamespace)
    {
        $this->packageNamespace = $packageNamespace;

        return $this;
    }

		/**
     * Set isEmployee
     *
     * @param boolean $isEmployee
     * @return Package
     */
    public function setIsEmployee($isEmployee)
    {
        $this->isEmployee = $isEmployee;

        return $this;
    }

    /**
     * Get packageNamespace
     *
     * @return string
     */
    public function getPackageNamespace()
    {
        return $this->packageNamespace;
		}

		/**
     * Get isEmployee
     *
     * @return boolean
     */
    public function getIsEmployee()
    {
        return $this->isEmployee;
    }

    /**
     * Add iptvRegularBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $iptvRegularBundle
     * @return Package
     */
    public function addIptvRegularBundle(\Dhi\AdminBundle\Entity\Bundle $iptvRegularBundle)
    {
        $this->iptvRegularBundle[] = $iptvRegularBundle;

        return $this;
    }

    /**
     * Remove iptvRegularBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $iptvRegularBundle
     */
    public function removeIptvRegularBundle(\Dhi\AdminBundle\Entity\Bundle $iptvRegularBundle)
    {
        $this->iptvRegularBundle->removeElement($iptvRegularBundle);
    }

    /**
     * Get iptvRegularBundle
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIptvRegularBundle()
    {
        return $this->iptvRegularBundle;
    }

    /**
     * Add ispRegularBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $ispRegularBundle
     * @return Package
     */
    public function addIspRegularBundle(\Dhi\AdminBundle\Entity\Bundle $ispRegularBundle)
    {
        $this->ispRegularBundle[] = $ispRegularBundle;

        return $this;
    }

    /**
     * Remove ispRegularBundle
     *
     * @param \Dhi\AdminBundle\Entity\Bundle $ispRegularBundle
     */
    public function removeIspRegularBundle(\Dhi\AdminBundle\Entity\Bundle $ispRegularBundle)
    {
        $this->ispRegularBundle->removeElement($ispRegularBundle);
    }

    /**
     * Get ispRegularBundle
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getIspRegularBundle()
    {
        return $this->ispRegularBundle;
    }

    /**
     * Set isPromotionalPlan
     *
     * @param boolean $isPromotionalPlan
     * @return Package
     */
    public function setIsPromotionalPlan($isPromotionalPlan)
    {
        $this->isPromotionalPlan = $isPromotionalPlan;

        return $this;
    }

    /**
     * Get isPromotionalPlan
     *
     * @return boolean
     */
    public function getIsPromotionalPlan()
    {
        return $this->isPromotionalPlan;
    }

    /**
     * Set freeRechargeCard
     *
     * @param boolean $freeRechargeCard
     * @return Package
     */
    public function setFreeRechargeCard($freeRechargeCard)
    {
        $this->freeRechargeCard = $freeRechargeCard;

        return $this;
    }

    /**
     * Get freeRechargeCard
     *
     * @return boolean 
     */
    public function getFreeRechargeCard()
    {
        return $this->freeRechargeCard;
    }
}
