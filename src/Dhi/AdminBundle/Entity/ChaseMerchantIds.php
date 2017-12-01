<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ChaseMerchantIds
 *
 * @ORM\Table(name="chase_merchant_ids")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\ChaseMerchantIdsRepository")
 */
class ChaseMerchantIds
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
     * @ORM\Column(name="mid_name", type="string", length=255, nullable=true)
     */
    protected $merchantName;
    
    /**
     * @ORM\Column(name="merchant_id", type="string", length=15, nullable=true)
     */
    protected $merchantId;
    
    /**
     * @ORM\Column(name="ip_address", type="string", length=50, nullable=true)
     */
    private $ipAddress;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", nullable=false)
     */
    protected $isDefault = false;
    
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
     * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\UserChaseInfo", mappedBy="merchantId")
     */
    protected $userChaseInfo;
    
    /**
     * @ORM\OneToMany(targetEntity="ServiceLocationWiseChaseMerchantId", mappedBy="chaseMerchantIds")
     */
    protected $serviceLocationWiseChaseMarchantid;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\PurchaseOrder", mappedBy="chaseMerchantId")
     */
    protected $purchaseOrders;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     */
    protected $isActive = true;
    
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
     * Set merchantId
     *
     * @param string $merchantId
     * @return ChaseMerchantIds
     */
    public function setMerchantId($merchantId)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * Get merchantId
     *
     * @return string 
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return ChaseMerchantIds
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ChaseMerchantIds
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
     * @return ChaseMerchantIds
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
     * @param \Dhi\UserBundle\Entity\User $createdBy
     * @return ChaseMerchantIds
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
     * @return ChaseMerchantIds
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
     * Set isDefault
     *
     * @param boolean $isDefault
     * @return ChaseMerchantIds
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean 
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->userChaseInfo = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add userChaseInfo
     *
     * @param \Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo
     * @return ChaseMerchantIds
     */
    public function addUserChaseInfo(\Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo)
    {
        $this->userChaseInfo[] = $userChaseInfo;

        return $this;
    }

    /**
     * Remove userChaseInfo
     *
     * @param \Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo
     */
    public function removeUserChaseInfo(\Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo)
    {
        $this->userChaseInfo->removeElement($userChaseInfo);
    }

    /**
     * Get userChaseInfo
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserChaseInfo()
    {
        return $this->userChaseInfo;
    }

    /**
     * Add serviceLocationWiseChaseMarchantid
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId $serviceLocationWiseChaseMarchantid
     * @return ChaseMerchantIds
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
     * Add purchaseOrders
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders
     * @return ChaseMerchantIds
     */
    public function addPurchaseOrder(\Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders)
    {
        $this->purchaseOrders[] = $purchaseOrders;

        return $this;
    }

    /**
     * Remove purchaseOrders
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders
     */
    public function removePurchaseOrder(\Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders)
    {
        $this->purchaseOrders->removeElement($purchaseOrders);
    }

    /**
     * Get purchaseOrders
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPurchaseOrders()
    {
        return $this->purchaseOrders;
    }

    /**
     * Set merchantName
     *
     * @param string $merchantName
     * @return ChaseMerchantIds
     */
    public function setMerchantName($merchantName)
    {
        $this->merchantName = $merchantName;

        return $this;
    }

    /**
     * Get merchantName
     *
     * @return string 
     */
    public function getMerchantName()
    {
        return $this->merchantName;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return ChaseMerchantIds
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Get isActive
     *
     * @return boolean 
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    public function getFullName()
    {
        return sprintf('%s - %s', $this->merchantName, $this->merchantId);
    }

}
