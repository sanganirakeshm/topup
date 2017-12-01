<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Country
 *
 * @ORM\Table(name="country")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\CountryRepository")
 */
class Country
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
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;
    
    /**
     * @var string
     *
     * @ORM\Column(name="iso_code", type="string", length=255)
     */
    protected $isoCode;
    
    /**
     *
     * @ORM\OneToMany(targetEntity="CountrywiseService", mappedBy="country")
     */
    protected $countrywiseService;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\AdminBundle\Entity\PaypalCredentials", mappedBy="country")
     */
    protected $payPalCredentials;

    /**
     *
     * @ORM\OneToMany(targetEntity="Support", mappedBy="country")
     */
    protected $support;

    /**
     *
     * @ORM\OneToMany(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation", mappedBy="country")
     */
    protected $serviceLocations;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->countrywiseService = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Country
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
     * Set isoCode
     *
     * @param string $isoCode
     * @return Country
     */
    public function setIsoCode($isoCode)
    {
        $this->isoCode = $isoCode;

        return $this;
    }

    /**
     * Get isoCode
     *
     * @return string 
     */
    public function getIsoCode()
    {
        return $this->isoCode;
    }

    /**
     * Add countrywiseService
     *
     * @param \Dhi\UserBundle\Entity\CountrywiseService $countrywiseService
     * @return Country
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
     * Add payPalCredentials
     *
     * @param \Dhi\AdminBundle\Entity\PayPalCredentials $payPalCredentials
     * @return Country
     */
    public function addPayPalCredential(\Dhi\AdminBundle\Entity\PayPalCredentials $payPalCredentials)
    {
        $this->payPalCredentials[] = $payPalCredentials;

        return $this;
    }

    /**
     * Remove payPalCredentials
     *
     * @param \Dhi\AdminBundle\Entity\PayPalCredentials $payPalCredentials
     */
    public function removePayPalCredential(\Dhi\AdminBundle\Entity\PayPalCredentials $payPalCredentials)
    {
        $this->payPalCredentials->removeElement($payPalCredentials);
    }

    /**
     * Get payPalCredentials
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPayPalCredentials()
    {
        return $this->payPalCredentials;
    }

    /**
     * Add support
     *
     * @param \Dhi\UserBundle\Entity\Support $support
     * @return Country
     */
    public function addSupport(\Dhi\UserBundle\Entity\Support $support)
    {
        $this->support[] = $support;

        return $this;
    }

    /**
     * Remove support
     *
     * @param \Dhi\UserBundle\Entity\Support $support
     */
    public function removeSupport(\Dhi\UserBundle\Entity\Support $support)
    {
        $this->support->removeElement($support);
    }

    /**
     * Get support
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSupport()
    {
        return $this->support;
    }

    /**
     * Add serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return Country
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
}
