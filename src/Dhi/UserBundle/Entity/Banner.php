<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @ORM\Entity
 * @ORM\Table(name="banner")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\BannerRepository")
 */

class Banner {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     */
    protected $country;

	
	/**
     * @ORM\Column(name="banner_images", type="text", length=65535, nullable=false)
	 *  @Assert\Image(
     *     minWidth = 1000,
     *     maxWidth = 2000,
     *     minHeight = 300,
     *     maxHeight = 600
     * )
     */
    protected $bannerImages;

	 /**
     * @ORM\Column(name="order_no",type="integer")
     */
    protected $orderNo;


	/**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = true;
   
	




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
     * Set bannerImages
     *
     * @param string $bannerImages
     * @return Banner
     */
    public function setBannerImages($bannerImages)
    {
        $this->bannerImages = $bannerImages;

        return $this;
    }

    /**
     * Get bannerImages
     *
     * @return string 
     */
    public function getBannerImages()
    {
        return $this->bannerImages;
    }

    /**
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return Banner
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
     * Set orderNo
     *
     * @param integer $orderNo
     * @return Banner
     */
    public function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;

        return $this;
    }

    /**
     * Get orderNo
     *
     * @return integer 
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Banner
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
}
