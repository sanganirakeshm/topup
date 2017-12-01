<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TopupLink
 *
 * @ORM\Table(name="topup_link")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\TopupLinkRepository")
 */
class TopupLink
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation")
     * @ORM\JoinTable(name="topup_link_service_locations",
     *      joinColumns={@ORM\JoinColumn(name="topup_link_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="service_location_id", referencedColumnName="id")}
     * )
     */
    protected $serviceLocations;
    
    /**
     * @var string
     *
     * @ORM\Column(name="link_name", type="string", length=50, nullable=false)
     */
    protected $linkName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="url", type="string", length=255, nullable=false)
     */
    protected $url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;
    
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
        $this->serviceLocations = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set linkName
     *
     * @param string $linkName
     * @return TopupLink
     */
    public function setLinkName($linkName)
    {
        $this->linkName = $linkName;

        return $this;
    }

    /**
     * Get linkName
     *
     * @return string 
     */
    public function getLinkName()
    {
        return $this->linkName;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return TopupLink
     */
    public function setUrl($url)
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return TopupLink
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
     * Add serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return TopupLink
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
