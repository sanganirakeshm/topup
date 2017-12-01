<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * PackageWiseTikiLivePlan
 *
 * @ORM\Table(name="package_wise_tikilive_plan")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\PackageWiseTikiLivePlanRepository")
 */
class PackageWiseTikiLivePlan
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
     * @ORM\Column(name="package_id", type="integer")
     *
     */
    protected $packageId;
    
    /**
     * @var string
     * @ORM\Column(name="tikilive_plan_name", type="string", length=255, nullable=false)
     */
    protected $tikiLivePlanName;

    /**
     * @var \DateTime
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updatedAt;
    
    /**
     * @ORM\Column(name="created_by", type="integer", nullable=true)
     *
     */
    protected $createdBy;
    
    /**
     * @ORM\Column(name="updated_by", type="integer", nullable=true )
     *
     */
    protected $updatedBy;
    
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
     * @return PackageWiseTikiLivePlan
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
     * Set tikiLivePlanName
     *
     * @param string $tikiLivePlanName
     * @return PackageWiseTikiLivePlan
     */
    public function setTikiLivePlanName($tikiLivePlanName)
    {
        $this->tikiLivePlanName = $tikiLivePlanName;

        return $this;
    }

    /**
     * Get tikiLivePlanName
     *
     * @return string 
     */
    public function getTikiLivePlanName()
    {
        return $this->tikiLivePlanName;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return PackageWiseTikiLivePlan
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
     * @return PackageWiseTikiLivePlan
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
     * Set createdBy
     *
     * @param integer $createdBy
     * @return PackageWiseTikiLivePlan
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Get createdBy
     *
     * @return integer 
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set updatedBy
     *
     * @param integer $updatedBy
     * @return PackageWiseTikiLivePlan
     */
    public function setUpdatedBy($updatedBy)
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Get updatedBy
     *
     * @return integer 
     */
    public function getUpdatedBy()
    {
        return $this->updatedBy;
    }
}
