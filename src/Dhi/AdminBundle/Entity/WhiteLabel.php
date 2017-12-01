<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * WhiteLabel
 *
 * @ORM\Table(name="white_label")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\WhiteLabelRepository")
 */
class WhiteLabel
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
     * @var string
     *
     * @ORM\Column(name="company_name", type="string", length=50, nullable=false)
     */
    protected $companyName;
    
    /**
     * @var string
     *
     * @ORM\Column(name="domain", type="string", length=200, nullable=false)
     */
    protected $domain;
    
     /**
     * @ORM\Column(name="from_email", type="string", length=50, nullable=false)
     */
    protected $fromEmail;
    
    /**
     * @ORM\Column(name="support_email", type="string", length=300, nullable=false)
     */
    protected $supportEmail;
    
    /**
     * @var string
     *
     * @ORM\Column(name="supportpage", type="string", length=200, nullable=false)
     */
    protected $supportpage;
    
    /**
     * @var string
     *
     * @ORM\Column(name="header_logo", type="string", length=255, nullable=true)
     * @Assert\Image(
     *     minWidth = 311,
     *     maxWidth = 311,
     *     minHeight = 114,
     *     maxHeight = 114)
     */
     protected $headerLogo;
     
     /**
     * @var string
     *
     * @ORM\Column(name="footer_logo", type="string", length=255, nullable=true)
     * @Assert\Image(
     *     minWidth = 270,
     *     maxWidth = 270,
     *     minHeight = 98,
     *     maxHeight = 98)
     */
     protected $footerLogo;
     
     /**
     * @var string
     *
     * @ORM\Column(name="branding_banner", type="string", length=255, nullable=true)
     * @Assert\Image(
     *     minWidth = 1133,
     *     maxWidth = 1133,
     *     minHeight = 411,
     *     maxHeight = 411)
     */
     protected $brandingBanner;
     
     
     /**
     * @var string
     *
     * @ORM\Column(name="branding_banner_inner_page", type="string", length=255, nullable=true)
     * @Assert\Image(
     *     minWidth = 674,
     *     maxWidth = 674,
     *     minHeight = 241,
     *     maxHeight = 241)
     */
     protected $brandingBannerInnerPage;
     
     /**
     * @var string
     *
     * @ORM\Column(name="favicon", type="string", length=255, nullable=true)
     * @Assert\Image(
     *     minWidth = 20,
     *     maxWidth = 20,
     *     minHeight = 20,
     *     maxHeight = 20)
     */
     protected $favicon;
     
     /**
     * @var string
     *
     * @ORM\Column(name="backgroundimage", type="string", length=255, nullable=true)
     * @Assert\Image(
     *     minWidth = 521,
     *     maxWidth = 521,
     *     minHeight = 760,
     *     maxHeight = 760)
     */
     protected $backgroundimage;
    
       
    /**
     * @var boolean
     *
     * @ORM\Column(name="is_active", type="boolean", nullable=false)
     */
    protected $status = true;
    
     /**
     * @var boolean
     *
     * @ORM\Column(name="is_deleted", type="boolean", nullable=false)
     */
    protected $isDeleted = false;
    
    /**
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
     *
     * @ORM\Column(name="created_by", type="integer")
     */
    protected $createdBy;
    
     /**
     *
     * @ORM\Column(name="updated_by", type="integer", nullable=true)
     */
    protected $updatedBy;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase", mappedBy="whiteLabel")
     */
    private $servicePurchases;
    
    /**
    * @ORM\OneToMany(targetEntity="ServiceLocationWiseSite", mappedBy="whiteLabel")
    */
    protected $serviceLocationWiseSite;
    
    /**
    * @ORM\OneToMany(targetEntity="Dhi\UserBundle\Entity\ReferralInvitees", mappedBy="whiteLabel")
    */
    protected $referralInvitees;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->servicePurchases = new \Doctrine\Common\Collections\ArrayCollection();
        $this->serviceLocationWiseSite = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set companyName
     *
     * @param string $companyName
     * @return WhiteLabel
     */
    public function setCompanyName($companyName)
    {
        $this->companyName = $companyName;

        return $this;
    }

    /**
     * Get companyName
     *
     * @return string 
     */
    public function getCompanyName()
    {
        return $this->companyName;
    }

    /**
     * Set domain
     *
     * @param string $domain
     * @return WhiteLabel
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Get domain
     *
     * @return string 
     */
    public function getDomain()
    {
        return $this->domain;
    }

    /**
     * Set fromEmail
     *
     * @param string $fromEmail
     * @return WhiteLabel
     */
    public function setFromEmail($fromEmail)
    {
        $this->fromEmail = $fromEmail;

        return $this;
    }

    /**
     * Get fromEmail
     *
     * @return string 
     */
    public function getFromEmail()
    {
        return $this->fromEmail;
    }

    /**
     * Set supportEmail
     *
     * @param string $supportEmail
     * @return WhiteLabel
     */
    public function setSupportEmail($supportEmail)
    {
        $this->supportEmail = $supportEmail;

        return $this;
    }

    /**
     * Get supportEmail
     *
     * @return string 
     */
    public function getSupportEmail()
    {
        return $this->supportEmail;
    }

    /**
     * Set supportpage
     *
     * @param string $supportpage
     * @return WhiteLabel
     */
    public function setSupportpage($supportpage)
    {
        $this->supportpage = $supportpage;

        return $this;
    }

    /**
     * Get supportpage
     *
     * @return string 
     */
    public function getSupportpage()
    {
        return $this->supportpage;
    }

    /**
     * Set headerLogo
     *
     * @param string $headerLogo
     * @return WhiteLabel
     */
    public function setHeaderLogo($headerLogo)
    {
        $this->headerLogo = $headerLogo;

        return $this;
    }

    /**
     * Get headerLogo
     *
     * @return string 
     */
    public function getHeaderLogo()
    {
        return $this->headerLogo;
    }

    /**
     * Set footerLogo
     *
     * @param string $footerLogo
     * @return WhiteLabel
     */
    public function setFooterLogo($footerLogo)
    {
        $this->footerLogo = $footerLogo;

        return $this;
    }

    /**
     * Get footerLogo
     *
     * @return string 
     */
    public function getFooterLogo()
    {
        return $this->footerLogo;
    }

    /**
     * Set brandingBanner
     *
     * @param string $brandingBanner
     * @return WhiteLabel
     */
    public function setBrandingBanner($brandingBanner)
    {
        $this->brandingBanner = $brandingBanner;

        return $this;
    }

    /**
     * Get brandingBanner
     *
     * @return string 
     */
    public function getBrandingBanner()
    {
        return $this->brandingBanner;
    }

    /**
     * Set brandingBannerInnerPage
     *
     * @param string $brandingBannerInnerPage
     * @return WhiteLabel
     */
    public function setBrandingBannerInnerPage($brandingBannerInnerPage)
    {
        $this->brandingBannerInnerPage = $brandingBannerInnerPage;

        return $this;
    }

    /**
     * Get brandingBannerInnerPage
     *
     * @return string 
     */
    public function getBrandingBannerInnerPage()
    {
        return $this->brandingBannerInnerPage;
    }

    /**
     * Set favicon
     *
     * @param string $favicon
     * @return WhiteLabel
     */
    public function setFavicon($favicon)
    {
        $this->favicon = $favicon;

        return $this;
    }

    /**
     * Get favicon
     *
     * @return string 
     */
    public function getFavicon()
    {
        return $this->favicon;
    }

    /**
     * Set backgroundimage
     *
     * @param string $backgroundimage
     * @return WhiteLabel
     */
    public function setBackgroundimage($backgroundimage)
    {
        $this->backgroundimage = $backgroundimage;

        return $this;
    }

    /**
     * Get backgroundimage
     *
     * @return string 
     */
    public function getBackgroundimage()
    {
        return $this->backgroundimage;
    }

    /**
     * Set status
     *
     * @param boolean $status
     * @return WhiteLabel
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
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return WhiteLabel
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return WhiteLabel
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
     * @return WhiteLabel
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
     * @return WhiteLabel
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
     * @return WhiteLabel
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

    /**
     * Add servicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases
     * @return WhiteLabel
     */
    public function addServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases[] = $servicePurchases;

        return $this;
    }

    /**
     * Remove servicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases
     */
    public function removeServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases->removeElement($servicePurchases);
    }

    /**
     * Get servicePurchases
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServicePurchases()
    {
        return $this->servicePurchases;
    }

    /**
     * Add serviceLocationWiseSite
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite
     * @return WhiteLabel
     */
    public function addServiceLocationWiseSite(\Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite)
    {
        $this->serviceLocationWiseSite[] = $serviceLocationWiseSite;

        return $this;
    }

    /**
     * Remove serviceLocationWiseSite
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite
     */
    public function removeServiceLocationWiseSite(\Dhi\AdminBundle\Entity\ServiceLocationWiseSite $serviceLocationWiseSite)
    {
        $this->serviceLocationWiseSite->removeElement($serviceLocationWiseSite);
    }

    /**
     * Get serviceLocationWiseSite
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServiceLocationWiseSite()
    {
        return $this->serviceLocationWiseSite;
    }

    public function getFullName()
    {
        return sprintf('%s - %s', $this->companyName, $this->domain);
    }

    /**
     * Add referralInvitees
     *
     * @param \Dhi\UserBundle\Entity\ReferralInvitees $referralInvitees
     * @return WhiteLabel
     */
    public function addReferralInvitee(\Dhi\UserBundle\Entity\ReferralInvitees $referralInvitees)
    {
        $this->referralInvitees[] = $referralInvitees;

        return $this;
    }

    /**
     * Remove referralInvitees
     *
     * @param \Dhi\UserBundle\Entity\ReferralInvitees $referralInvitees
     */
    public function removeReferralInvitee(\Dhi\UserBundle\Entity\ReferralInvitees $referralInvitees)
    {
        $this->referralInvitees->removeElement($referralInvitees);
    }

    /**
     * Get referralInvitees
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getReferralInvitees()
    {
        return $this->referralInvitees;
    }
}
