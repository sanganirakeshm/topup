<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;

/**
 * ServiceLocation
 *
 * @ORM\Table(name="service_location")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ServiceLocationRepository")
 */
class ServiceLocation
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @ORM\Column(name="description", type="text", nullable=false)
     */
    protected $description;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\Country", inversedBy="serviceLocations")
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     */
    protected $country;

    /**
     * @ORM\OneToMany(targetEntity="IpAddressZone", mappedBy="serviceLocation", cascade={"persist", "remove"})
     */
    protected $ipAddressZones;

    /**
     * @ORM\OneToMany(targetEntity="Package", mappedBy="serviceLocation", cascade={"persist", "remove"})
     */
    protected $package;

    /**
     * @ORM\OneToMany(targetEntity="DiscountCodeServiceLocation", mappedBy="serviceLocation")
     */
    protected $discountCodeServiceLocation;

    /**
     * @ORM\OneToMany(targetEntity="ServiceLocationDiscount", mappedBy="serviceLocation", cascade={"persist", "remove"})
     */
    protected $serviceLocationDiscounts;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase", mappedBy="service_location_id")
     */
    protected $serviceLocaton;

    /**
     * @ORM\ManyToMany(targetEntity="Dhi\UserBundle\Entity\User", mappedBy="serviceLocations")
     *
     */
    protected $admins;

    /**
     * @ORM\ManyToMany(targetEntity="Dhi\UserBundle\Entity\Compensation", mappedBy="serviceLocations")
     *
     */
    protected $compensations;

	  /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\PromoCode", mappedBy="serviceLocations")
     */
    protected $Promocode;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\InAppPromoCode", mappedBy="serviceLocations")
     *
     */
    protected $InAppPromocode;
    /**
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\User", mappedBy="userServiceLocation")
	 *
	 */
    protected $users;

    /**
     * @ORM\OneToMany(targetEntity="PaypalCredentials", mappedBy="serviceLocations", cascade={"persist", "remove"})
     */
    protected $paypalCredentialLocation;

    /**
     * @ORM\OneToMany(targetEntity="ServiceLocationWiseChaseMerchantId", mappedBy="serviceLocation")
     */
    protected $serviceLocationWiseChaseMarchantid;

    /**
     * @ORM\OneToMany(targetEntity="ServiceLocationWiseSite", mappedBy="serviceLocation")
     */
    protected $serviceLocationWiseSite;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->ipAddressZones = new \Doctrine\Common\Collections\ArrayCollection();
        $this->package = new \Doctrine\Common\Collections\ArrayCollection();
        $this->discountCodeServiceLocation = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocationDiscounts = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocaton = new \Doctrine\Common\Collections\ArrayCollection();
        $this->admins = new \Doctrine\Common\Collections\ArrayCollection();
        $this->compensations = new \Doctrine\Common\Collections\ArrayCollection();
        $this->Promocode = new \Doctrine\Common\Collections\ArrayCollection();
        $this->InAppPromocode = new \Doctrine\Common\Collections\ArrayCollection();
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
        $this->paypalCredentialLocation = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocationWiseChaseMarchantid = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocationWiseSite = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return ServiceLocation
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
     * Set description
     *
     * @param string $description
     * @return ServiceLocation
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
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return ServiceLocation
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
     * Add ipAddressZones
     *
     * @param \Dhi\AdminBundle\Entity\IpAddressZone $ipAddressZones
     * @return ServiceLocation
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
     * Add package
     *
     * @param \Dhi\AdminBundle\Entity\Package $package
     * @return ServiceLocation
     */
    public function addPackage(\Dhi\AdminBundle\Entity\Package $package)
    {
        $this->package[] = $package;

        return $this;
    }

    /**
     * Remove package
     *
     * @param \Dhi\AdminBundle\Entity\Package $package
     */
    public function removePackage(\Dhi\AdminBundle\Entity\Package $package)
    {
        $this->package->removeElement($package);
    }

    /**
     * Get package
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPackage()
    {
        return $this->package;
    }

    /**
     * Add discountCodeServiceLocation
     *
     * @param \Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation
     * @return ServiceLocation
     */
    public function addDiscountCodeServiceLocation(\Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation)
    {
        $this->discountCodeServiceLocation[] = $discountCodeServiceLocation;

        return $this;
    }

    /**
     * Remove discountCodeServiceLocation
     *
     * @param \Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation
     */
    public function removeDiscountCodeServiceLocation(\Dhi\AdminBundle\Entity\DiscountCodeServiceLocation $discountCodeServiceLocation)
    {
        $this->discountCodeServiceLocation->removeElement($discountCodeServiceLocation);
    }

    /**
     * Get discountCodeServiceLocation
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getDiscountCodeServiceLocation()
    {
        return $this->discountCodeServiceLocation;
    }

    /**
     * Add serviceLocationDiscounts
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts
     * @return ServiceLocation
     */
    public function addServiceLocationDiscount(\Dhi\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts)
    {
        $this->serviceLocationDiscounts[] = $serviceLocationDiscounts;

        return $this;
    }

    /**
     * Remove serviceLocationDiscounts
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts
     */
    public function removeServiceLocationDiscount(\Dhi\AdminBundle\Entity\ServiceLocationDiscount $serviceLocationDiscounts)
    {
        $this->serviceLocationDiscounts->removeElement($serviceLocationDiscounts);
    }

    /**
     * Get serviceLocationDiscounts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocationDiscounts()
    {
        return $this->serviceLocationDiscounts;
    }

    /**
     * Add serviceLocaton
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $serviceLocaton
     * @return ServiceLocation
     */
    public function addServiceLocaton(\Dhi\ServiceBundle\Entity\ServicePurchase $serviceLocaton)
    {
        $this->serviceLocaton[] = $serviceLocaton;

        return $this;
    }

    /**
     * Remove serviceLocaton
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $serviceLocaton
     */
    public function removeServiceLocaton(\Dhi\ServiceBundle\Entity\ServicePurchase $serviceLocaton)
    {
        $this->serviceLocaton->removeElement($serviceLocaton);
    }

    /**
     * Get serviceLocaton
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocaton()
    {
        return $this->serviceLocaton;
    }

    /**
     * Add admins
     *
     * @param \Dhi\UserBundle\Entity\User $admins
     * @return ServiceLocation
     */
    public function addAdmin(\Dhi\UserBundle\Entity\User $admins)
    {
        $this->admins[] = $admins;

        return $this;
    }

    /**
     * Remove admins
     *
     * @param \Dhi\UserBundle\Entity\User $admins
     */
    public function removeAdmin(\Dhi\UserBundle\Entity\User $admins)
    {
        $this->admins->removeElement($admins);
    }

    /**
     * Get admins
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getAdmins()
    {
        return $this->admins;
    }

    /**
     * Add compensations
     *
     * @param \Dhi\UserBundle\Entity\Compensation $compensations
     * @return ServiceLocation
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
     * Add Promocode
     *
     * @param \Dhi\UserBundle\Entity\PromoCode $promocode
     * @return ServiceLocation
     */
    public function addPromocode(\Dhi\UserBundle\Entity\PromoCode $promocode)
    {
        $this->Promocode[] = $promocode;

        return $this;
    }

    /**
     * Remove Promocode
     *
     * @param \Dhi\UserBundle\Entity\PromoCode $promocode
     */
    public function removePromocode(\Dhi\UserBundle\Entity\PromoCode $promocode)
    {
        $this->Promocode->removeElement($promocode);
    }

    /**
     * Get Promocode
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPromocode()
    {
        return $this->Promocode;
    }

    /**
     * Add InAppPromocode
     *
     * @param \Dhi\UserBundle\Entity\InAppPromoCode $inAppPromocode
     * @return ServiceLocation
     */
    public function addInAppPromocode(\Dhi\UserBundle\Entity\InAppPromoCode $inAppPromocode)
    {
        $this->InAppPromocode[] = $inAppPromocode;

        return $this;
    }

    /**
     * Remove InAppPromocode
     *
     * @param \Dhi\UserBundle\Entity\InAppPromoCode $inAppPromocode
     */
    public function removeInAppPromocode(\Dhi\UserBundle\Entity\InAppPromoCode $inAppPromocode)
    {
        $this->InAppPromocode->removeElement($inAppPromocode);
    }

    /**
     * Get InAppPromocode
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInAppPromocode()
    {
        return $this->InAppPromocode;
    }

    /**
     * Add users
     *
     * @param \Dhi\UserBundle\Entity\User $users
     * @return ServiceLocation
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
     * Add paypalCredentialLocation
     *
     * @param \Dhi\AdminBundle\Entity\PaypalCredentials $paypalCredentialLocation
     * @return ServiceLocation
     */
    public function addPaypalCredentialLocation(\Dhi\AdminBundle\Entity\PaypalCredentials $paypalCredentialLocation)
    {
        $this->paypalCredentialLocation[] = $paypalCredentialLocation;

        return $this;
    }

    /**
     * Remove paypalCredentialLocation
     *
     * @param \Dhi\AdminBundle\Entity\PaypalCredentials $paypalCredentialLocation
     */
    public function removePaypalCredentialLocation(\Dhi\AdminBundle\Entity\PaypalCredentials $paypalCredentialLocation)
    {
        $this->paypalCredentialLocation->removeElement($paypalCredentialLocation);
    }

    /**
     * Get paypalCredentialLocation
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPaypalCredentialLocation()
    {
        return $this->paypalCredentialLocation;
    }

    /**
     * Add serviceLocationWiseChaseMarchantid
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId $serviceLocationWiseChaseMarchantid
     * @return ServiceLocation
     */
    public function addServiceLocationWiseChaseMarchantid(\Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId $serviceLocationWiseChaseMarchantid)
    {
        $this->serviceLocationWiseChaseMarchantid[] = $serviceLocationWiseChaseMarchantid;

        return $this;
    }

    /**
     * Remove serviceLocationWiseChaseMarchantid
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId $serviceLocationWiseChaseMarchantid
     */
    public function removeServiceLocationWiseChaseMarchantid(\Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId $serviceLocationWiseChaseMarchantid)
    {
        $this->serviceLocationWiseChaseMarchantid->removeElement($serviceLocationWiseChaseMarchantid);
    }

    /**
     * Get serviceLocationWiseChaseMarchantid
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocationWiseChaseMarchantid()
    {
        return $this->serviceLocationWiseChaseMarchantid;
    }

    /**
     * Add serviceLocationWiseSite
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite
     * @return ServiceLocation
     */
    public function addServiceLocationWiseSite(\Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite)
    {
        $this->serviceLocationWiseSite[] = $serviceLocationWiseSite;

        return $this;
    }

    /**
     * Remove serviceLocationWiseSite
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite
     */
    public function removeServiceLocationWiseSite(\Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite)
    {
        $this->serviceLocationWiseSite->removeElement($serviceLocationWiseSite);
    }

    /**
     * Get serviceLocationWiseSite
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocationWiseSite()
    {
        return $this->serviceLocationWiseSite;
    }
}
