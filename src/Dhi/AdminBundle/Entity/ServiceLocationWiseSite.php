<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ServiceLocationWiseSite
 *
 * @ORM\Table(name="service_location_wise_site")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ServiceLocationWiseSiteRepository")
 */
class ServiceLocationWiseSite
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
     * @ORM\ManyToOne(targetEntity="WhiteLabel", inversedBy="serviceLocationWiseSite")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $whiteLabel;
    
    /**
     * @ORM\ManyToOne(targetEntity="ServiceLocation", inversedBy="serviceLocationWiseSite")
     * @ORM\JoinColumn(name="service_location_id", referencedColumnName="id")
     */
    protected $serviceLocation;
    
    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="updated_by", referencedColumnName="id")
     */
    protected $updatedBy;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    protected $isDeleted = false;
    
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ServiceLocationWiseSite
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
     * @return ServiceLocationWiseSite
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
     * Set whiteLabel
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel
     * @return ServiceLocationWiseSite
     */
    public function setWhiteLabel(\Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel = null)
    {
        $this->whiteLabel = $whiteLabel;

        return $this;
    }

    /**
     * Get whiteLabel
     *
     * @return \Dhi\AdminBundle\Entity\WhiteLabel 
     */
    public function getWhiteLabel()
    {
        return $this->whiteLabel;
    }

    /**
     * Set serviceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation
     * @return ServiceLocationWiseSite
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
     * Set createdBy
     *
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return ServiceLocationWiseSite
     */
    public function setCreatedBy(\Dhi\UserBundle\Entity\User $createdBy = null)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedBy
     *
     * @param \Dhi\UserBundle\Entity\User $updatedBy
     * @return ServiceLocationWiseSite
     */
    public function setUpdatedBy(\Dhi\UserBundle\Entity\User $updatedBy = null)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return ServiceLocationWiseSite
     */
    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean 
     */
    public function getIsDeleted()
    {
        return $this->isDeleted;
    }
}
