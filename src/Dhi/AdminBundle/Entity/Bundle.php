<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\Service;
/**
 * Bundle
 *
 * @ORM\Table(name="bundle")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\BundleRepository")
 */
class Bundle
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
     * @ORM\Column(name="package_name", type="string", nullable=false)
     */
    protected $bundleName;

    /**
     * @ORM\Column(name="display_bundle_name", type="text", nullable=true)
     */
    protected $displayBundleName;

    /**
     * @ORM\Column(name="bundle_id", type="integer", nullable=false)
     */
    protected $bundle_id;

    /**
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;    

    /**
     *
     * @ORM\ManyToOne(targetEntity="Package", inversedBy="iptvBundle")
     * @ORM\JoinColumn(name="iptv_id", referencedColumnName="id")
     */
    protected $iptv;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Package", inversedBy="ispBundle")
     * @ORM\JoinColumn(name="isp_id", referencedColumnName="id")
     */
    protected $isp;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Package", inversedBy="iptvRegularBundle")
     * @ORM\JoinColumn(name="regular_iptv_id", referencedColumnName="id")
     */
    protected $regularIptv;
    
    /**
     *
     * @ORM\ManyToOne(targetEntity="Package", inversedBy="ispRegularBundle")
     * @ORM\JoinColumn(name="regular_isp_id", referencedColumnName="id")
     */
    protected $regularIsp;
    
    /**
     * @ORM\Column(name="iptv_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $iptvAmount;
    
    /**
     * @ORM\Column(name="isp_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $ispAmount;
    
    /**
     * @ORM\Column(name="amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $amount;

    /**
     * @ORM\Column(name="total_package_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $totalPackageAmount;

    /**
     * @ORM\Column(name="discount", type="decimal", precision= 10, scale= 2, nullable=true, options={"comment":"%"})
     */
    protected $discount;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="status", type="boolean", nullable=false)
     */
    protected $status = false;
    
    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;
    
    /**
     * @ORM\Column(name="order_id", type="integer", nullable=true)
     */
    protected $order_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_employee", type="boolean", nullable=false)
     */
    protected $isEmployee = false;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_auto_bundle", type="boolean", nullable=false)
     */
    protected $isAutoBundle = false;

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
     * Set iptv
     *
     * @param string $iptv
     * @return Bundle
     */
    public function setIptv($iptv)
    {
        $this->iptv = $iptv;

        return $this;
    }

    /**
     * Get iptv
     *
     * @return string 
     */
    public function getIptv()
    {
        return $this->iptv;
    }

    /**
     * Set isp
     *
     * @param string $isp
     * @return Bundle
     */
    public function setIsp($isp)
    {
        $this->isp = $isp;

        return $this;
    }

    /**
     * Get isp
     *
     * @return string 
     */
    public function getIsp()
    {
        return $this->isp;
    }

    /**
     * Set price
     *
     * @param string $price
     * @return Bundle
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return string 
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     * @return Bundle
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return integer 
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Bundle
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
     * Set amount
     *
     * @param string $amount
     * @return Bundle
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string 
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return Bundle
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
     * Set description
     *
     * @param string $description
     * @return Bundle
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
     * Set bundleName
     *
     * @param string $bundleName
     * @return Bundle
     */
    public function setBundleName($bundleName)
    {
        $this->bundleName = $bundleName;

        return $this;
    }

    /**
     * Get bundleName
     *
     * @return string 
     */
    public function getBundleName()
    {
        return $this->bundleName;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        
    }

    /**
     * Set bundle_id
     *
     * @param integer $bundleId
     * @return Bundle
     */
    public function setBundleId($bundleId)
    {
        $this->bundle_id = $bundleId;

        return $this;
    }

    /**
     * Get bundle_id
     *
     * @return integer 
     */
    public function getBundleId()
    {
        return $this->bundle_id;
    }

    /**
     * Set order_id
     *
     * @param integer $orderId
     * @return Bundle
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get order_id
     *
     * @return integer 
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set totalPackageAmount
     *
     * @param string $totalPackageAmount
     * @return Bundle
     */
    public function setTotalPackageAmount($totalPackageAmount)
    {
        $this->totalPackageAmount = $totalPackageAmount;

        return $this;
    }

    /**
     * Get totalPackageAmount
     *
     * @return string 
     */
    public function getTotalPackageAmount()
    {
        return $this->totalPackageAmount;
    }

    /**
     * Set displayBundleName
     *
     * @param string $displayBundleName
     * @return Bundle
     */
    public function setDisplayBundleName($displayBundleName)
    {
        $this->displayBundleName = $displayBundleName;

        return $this;
    }

    /**
     * Get displayBundleName
     *
     * @return string 
     */
    public function getDisplayBundleName()
    {
        return $this->displayBundleName;
    }

    /**
     * Set iptvAmount
     *
     * @param string $iptvAmount
     * @return Bundle
     */
    public function setIptvAmount($iptvAmount)
    {
        $this->iptvAmount = $iptvAmount;

        return $this;
    }

    /**
     * Get iptvAmount
     *
     * @return string 
     */
    public function getIptvAmount()
    {
        return $this->iptvAmount;
    }

    /**
     * Set ispAmount
     *
     * @param string $ispAmount
     * @return Bundle
     */
    public function setIspAmount($ispAmount)
    {
        $this->ispAmount = $ispAmount;

        return $this;
    }

    /**
     * Get ispAmount
     *
     * @return string 
     */
    public function getIspAmount()
    {
        return $this->ispAmount;
    }

    /**
     * Set isEmployee
     *
     * @param boolean $isEmployee
     * @return Bundle
     */
    public function setIsEmployee($isEmployee)
    {
        $this->isEmployee = $isEmployee;

        return $this;
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
     * Set regularIptv
     *
     * @param \Dhi\AdminBundle\Entity\Package $regularIptv
     * @return Bundle
     */
    public function setRegularIptv(\Dhi\AdminBundle\Entity\Package $regularIptv = null)
    {
        $this->regularIptv = $regularIptv;

        return $this;
    }

    /**
     * Get regularIptv
     *
     * @return \Dhi\AdminBundle\Entity\Package 
     */
    public function getRegularIptv()
    {
        return $this->regularIptv;
    }

    /**
     * Set regularIsp
     *
     * @param \Dhi\AdminBundle\Entity\Package $regularIsp
     * @return Bundle
     */
    public function setRegularIsp(\Dhi\AdminBundle\Entity\Package $regularIsp = null)
    {
        $this->regularIsp = $regularIsp;

        return $this;
    }

    /**
     * Get regularIsp
     *
     * @return \Dhi\AdminBundle\Entity\Package 
     */
    public function getRegularIsp()
    {
        return $this->regularIsp;
    }

    /**
     * Set isAutoBundle
     *
     * @param boolean $isAutoBundle
     * @return Bundle
     */
    public function setIsAutoBundle($isAutoBundle)
    {
        $this->isAutoBundle = $isAutoBundle;

        return $this;
    }

    /**
     * Get isAutoBundle
     *
     * @return boolean 
     */
    public function getIsAutoBundle()
    {
        return $this->isAutoBundle;
    }
}
