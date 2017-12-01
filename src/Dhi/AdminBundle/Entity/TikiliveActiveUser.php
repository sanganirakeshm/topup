<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping\Index as Index;

/**
 * TikiliveActiveUser
 *
 * @ORM\Table(name="tikilive_active_user",indexes={@Index(name="tikilive_active_user_promo_code_idx", columns={"promo_code"})}))
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\TikiliveActiveUserRepository")
 */
class TikiliveActiveUser
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
     * @ORM\Column(name="tikilive_user_id", type="integer", nullable=true)
     *
     */
    protected $tikiliveUserId;

    /**
     * @ORM\Column(name="tikilive_user_country_code", type="string", length=15, nullable=true)
     *
     */
    protected $tikiliveUserCountryCode;

    /**
     * @ORM\Column(name="tikilive_user_name", type="string", length=50, nullable=true)
     *
     */
    protected $tikiliveUserName;

    /**
     *
     * @ORM\ManyToOne(targetEntity="ServiceLocation")
     * @ORM\JoinColumn(name="service_location", referencedColumnName="id", nullable=true)
     */
    protected $serviceLocation;

    /**
     * @ORM\Column(name="promo_code", type="string", length=25)
     */
    protected $promoCode;

    /**
     * @ORM\Column(name="actual_country", type="string", length=25, nullable=true)
     */
    protected $actualCountry;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="tikilive_last_login", type="datetime", nullable=true)
     */
    protected $tikiliveLastLogin;

    /**
     * @ORM\Column(name="tikilive_last_ip", type="string", length=15, nullable=true)
     */
    protected $tikiliveLastIp;
    
    /**
     * @ORM\Column(name="tikilive_last_ip_long", type="bigint", nullable=true)
     */
    protected $tikiliveLastIpLong;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     */
    protected $isActive = true;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="promo_code_expiry_date", type="datetime", nullable=true)
     */
    protected $expiryDate;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_promo_code_expired", type="boolean", nullable=false)
     */
    protected $isPromoCodeExpired = false;

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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set tikiliveUserId
     *
     * @param integer $tikiliveUserId
     * @return TikiliveActiveUser
     */
    public function setTikiliveUserId($tikiliveUserId)
    {
        $this->tikiliveUserId = $tikiliveUserId;

        return $this;
    }

    /**
     * Get tikiliveUserId
     *
     * @return integer 
     */
    public function getTikiliveUserId()
    {
        return $this->tikiliveUserId;
    }

    /**
     * Set tikiliveLastLogin
     *
     * @param \DateTime $tikiliveLastLogin
     * @return TikiliveActiveUser
     */
    public function setTikiliveLastLogin($tikiliveLastLogin)
    {
        $this->tikiliveLastLogin = $tikiliveLastLogin;

        return $this;
    }

    /**
     * Get tikiliveLastLogin
     *
     * @return \DateTime 
     */
    public function getTikiliveLastLogin()
    {
        return $this->tikiliveLastLogin;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return TikiliveActiveUser
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

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return TikiliveActiveUser
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
     * @return TikiliveActiveUser
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
     * Set tikiliveLastIp
     *
     * @param string $tikiliveLastIp
     * @return TikiliveActiveUser
     */
    public function setTikiliveLastIp($tikiliveLastIp)
    {
        $this->tikiliveLastIp = $tikiliveLastIp;

        return $this;
    }

    /**
     * Get tikiliveLastIp
     *
     * @return string 
     */
    public function getTikiliveLastIp()
    {
        return $this->tikiliveLastIp;
    }

    /**
     * Set tikiliveLastIpLong
     *
     * @param integer $tikiliveLastIpLong
     * @return TikiliveActiveUser
     */
    public function setTikiliveLastIpLong($tikiliveLastIpLong)
    {
        $this->tikiliveLastIpLong = $tikiliveLastIpLong;

        return $this;
    }

    /**
     * Get tikiliveLastIpLong
     *
     * @return integer 
     */
    public function getTikiliveLastIpLong()
    {
        return $this->tikiliveLastIpLong;
    }

    /**
     * Set expiryDate
     *
     * @param \DateTime $expiryDate
     * @return TikiliveActiveUser
     */
    public function setExpiryDate($expiryDate)
    {
        $this->expiryDate = $expiryDate;

        return $this;
    }

    /**
     * Get expiryDate
     *
     * @return \DateTime 
     */
    public function getExpiryDate()
    {
        return $this->expiryDate;
    }

    /**
     * Set isPromoCodeExpired
     *
     * @param boolean $isPromoCodeExpired
     * @return TikiliveActiveUser
     */
    public function setIsPromoCodeExpired($isPromoCodeExpired)
    {
        $this->isPromoCodeExpired = $isPromoCodeExpired;

        return $this;
    }

    /**
     * Get isPromoCodeExpired
     *
     * @return boolean 
     */
    public function getIsPromoCodeExpired()
    {
        return $this->isPromoCodeExpired;
    }

    /**
     * Set serviceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocation
     * @return TikiliveActiveUser
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
     * Set tikiliveUserCountryCode
     *
     * @param string $tikiliveUserCountryCode
     * @return TikiliveActiveUser
     */
    public function setTikiliveUserCountryCode($tikiliveUserCountryCode)
    {
        $this->tikiliveUserCountryCode = $tikiliveUserCountryCode;

        return $this;
    }

    /**
     * Get tikiliveUserCountryCode
     *
     * @return string 
     */
    public function getTikiliveUserCountryCode()
    {
        return $this->tikiliveUserCountryCode;
    }

    /**
     * Set tikiliveUserName
     *
     * @param string $tikiliveUserName
     * @return TikiliveActiveUser
     */
    public function setTikiliveUserName($tikiliveUserName)
    {
        $this->tikiliveUserName = $tikiliveUserName;

        return $this;
    }

    /**
     * Get tikiliveUserName
     *
     * @return string 
     */
    public function getTikiliveUserName()
    {
        return $this->tikiliveUserName;
    }

    /**
     * Set promoCode
     *
     * @param string $promoCode
     * @return TikiliveActiveUser
     */
    public function setPromoCode($promoCode)
    {
        $this->promoCode = $promoCode;

        return $this;
    }

    /**
     * Get promoCode
     *
     * @return string 
     */
    public function getPromoCode()
    {
        return $this->promoCode;
    }

    /**
     * Set actualCountry
     *
     * @param string $actualCountry
     * @return TikiliveActiveUser
     */
    public function setActualCountry($actualCountry)
    {
        $this->actualCountry = $actualCountry;

        return $this;
    }

    /**
     * Get actualCountry
     *
     * @return string 
     */
    public function getActualCountry()
    {
        return $this->actualCountry;
    }
}
