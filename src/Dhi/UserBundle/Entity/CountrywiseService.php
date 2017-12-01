<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * CountrywiseService
 *
 * @ORM\Table(name="countrywise_service")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\CountrywiseServiceRepository")
 */
class CountrywiseService {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Country", inversedBy="countrywiseService")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     */
    protected $country;

    /**
     * @ORM\ManyToOne(targetEntity="Service", inversedBy="countrywiseService")
     * @ORM\JoinColumn(name="service_id", referencedColumnName="id")
     */
    protected $services;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean")
     */
    protected $status;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_show_on_landing", type="boolean")
     */
    protected $isShowOnLanding;

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
     * Set status
     *
     * @param boolean $status
     * @return CountrywiseService
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
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return CountrywiseService
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
     * Set services
     *
     * @param \Dhi\UserBundle\Entity\Service $services
     * @return CountrywiseService
     */
    public function setServices(\Dhi\UserBundle\Entity\Service $services = null)
    {
        $this->services = $services;

        return $this;
    }

    /**
     * Get services
     *
     * @return \Dhi\UserBundle\Entity\Service 
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Set isShowOnLanding
     *
     * @param boolean $isShowOnLanding
     * @return CountrywiseService
     */
    public function setIsShowOnLanding($isShowOnLanding)
    {
        $this->isShowOnLanding = $isShowOnLanding;

        return $this;
    }

    /**
     * Get isShowOnLanding
     *
     * @return boolean 
     */
    public function getIsShowOnLanding()
    {
        return $this->isShowOnLanding;
    }
}
