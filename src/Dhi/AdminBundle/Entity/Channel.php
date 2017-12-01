<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;

/**
 * Channel
 *
 * @ORM\Table(name="channel")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ChannelRepository")
 */

class Channel
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
     *
     * @ORM\ManyToOne(targetEntity="Package", inversedBy="channels")
     * @ORM\JoinColumn(name="package_id", referencedColumnName="id")
     */
    protected $package;
    
    /**
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;
    
    
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
     * Set name
     *
     * @param string $name
     * @return Channel
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
     * @return Channel
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
     * Set package
     *
     * @param \Dhi\AdminBundle\Entity\Package $package
     * @return Channel
     */
    public function setPackage(\Dhi\AdminBundle\Entity\Package $package = null)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * Get package
     *
     * @return \Dhi\AdminBundle\Entity\Package 
     */
    public function getPackage()
    {
        return $this->package;
    }
}
