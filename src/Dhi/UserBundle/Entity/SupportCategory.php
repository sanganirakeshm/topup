<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="support_category")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\SupportCategoryRepository")
 */

class SupportCategory {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
     /**
     * @ORM\Column(name="name", type="string", length=255)
     *
     */
    
    protected $name;
    
    /**
     * @ORM\OneToMany(targetEntity="Support", mappedBy="category")
     */    
     protected $supports;
     
     /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $supportsite;

    /**
     * @ORM\Column(name="sequence_number", type="integer", length=10, nullable=true)
     *
     */
    protected $sequenceNumber;
    
    /**
     * @ORM\Column(name="is_deleted", type="boolean")
     *
     */
    protected $isDeleted = false;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->supports = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return SupportCategory
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
     * Add supports
     *
     * @param \Dhi\UserBundle\Entity\Support $supports
     * @return SupportCategory
     */
    public function addSupport(\Dhi\UserBundle\Entity\Support $supports)
    {
        $this->supports[] = $supports;

        return $this;
    }

    /**
     * Remove supports
     *
     * @param \Dhi\UserBundle\Entity\Support $supports
     */
    public function removeSupport(\Dhi\UserBundle\Entity\Support $supports)
    {
        $this->supports->removeElement($supports);
    }

    /**
     * Get supports
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getSupports()
    {
        return $this->supports;
    }

    /**
     * Set supportsite
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $supportsite
     * @return SupportCategory
     */
    public function setSupportsite(\Dhi\AdminBundle\Entity\WhiteLabel $supportsite = null)
    {
        $this->supportsite = $supportsite;

        return $this;
    }

    /**
     * Get supportsite
     *
     * @return \Dhi\AdminBundle\Entity\WhiteLabel 
     */
    public function getSupportsite()
    {
        return $this->supportsite;
    }
    
    /**
     * Set sequenceNumber
     *
     * @param integer $sequenceNumber
     * @return SupportCategory
     */
    public function setSequenceNumber($sequenceNumber)
    {
        $this->sequenceNumber = $sequenceNumber;

        return $this;
    }

    /**
     * Get sequenceNumber
     *
     * @return integer 
     */
    public function getSequenceNumber()
    {
        return $this->sequenceNumber;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return SupportCategory
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

    /**
     * Set createdBy
     *
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return SupportCategory
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
}
