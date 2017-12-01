<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ReferralPromoCode
 *
 * @ORM\Table(name="referral_promo_code")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\ReferralPromoCodeRepository")
 */
class ReferralPromoCode
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
     * @ORM\Column(name="promo_code", type="string", length=6, nullable=false)
     */
    protected $promocode;
    
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="referrerUser")
     * @ORM\JoinColumn(name=" referrer_user_id", referencedColumnName="id")
     */
    protected $referrerUserId;
    
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="refereeUser")
     * @ORM\JoinColumn(name=" referee_user_id", referencedColumnName="id")
     */
    protected $refereeUserId;

    /**
     * @ORM\Column(name="is_redeemed", type="boolean" )
     */
    protected $isRedeemed;    
    
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
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase", mappedBy="discountedReferralPromoCode")
     */
    protected $referralCode;
    
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
     * Set promocode
     *
     * @param string $promocode
     * @return ReferralPromoCode
     */
    public function setPromocode($promocode)
    {
        $this->promocode = $promocode;

        return $this;
    }

    /**
     * Get promocode
     *
     * @return string 
     */
    public function getPromocode()
    {
        return $this->promocode;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return ReferralPromoCode
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
     * @return ReferralPromoCode
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
     * Set referrerUserId
     *
     * @param \Dhi\UserBundle\Entity\User $referrerUserId
     * @return ReferralPromoCode
     */
    public function setReferrerUserId(\Dhi\UserBundle\Entity\User $referrerUserId = null)
    {
        $this->referrerUserId = $referrerUserId;

        return $this;
    }

    /**
     * Get referrerUserId
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getReferrerUserId()
    {
        return $this->referrerUserId;
    }

    /**
     * Set refereeUserId
     *
     * @param \Dhi\UserBundle\Entity\User $refereeUserId
     * @return ReferralPromoCode
     */
    public function setRefereeUserId(\Dhi\UserBundle\Entity\User $refereeUserId = null)
    {
        $this->refereeUserId = $refereeUserId;

        return $this;
    }

    /**
     * Get refereeUserId
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getRefereeUserId()
    {
        return $this->refereeUserId;
    }

    /**
     * Set isRedeemed
     *
     * @param boolean $isRedeemed
     * @return ReferralPromoCode
     */
    public function setIsRedeemed($isRedeemed)
    {
        $this->isRedeemed = $isRedeemed;

        return $this;
    }

    /**
     * Get isRedeemed
     *
     * @return boolean 
     */
    public function getIsRedeemed()
    {
        return $this->isRedeemed;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->referralCode = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add referralCode
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $referralCode
     * @return ReferralPromocode
     */
    public function addReferralCode(\Dhi\ServiceBundle\Entity\ServicePurchase $referralCode)
    {
        $this->referralCode[] = $referralCode;

        return $this;
    }

    /**
     * Remove referralCode
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $referralCode
     */
    public function removeReferralCode(\Dhi\ServiceBundle\Entity\ServicePurchase $referralCode)
    {
        $this->referralCode->removeElement($referralCode);
    }

    /**
     * Get referralCode
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getReferralCode()
    {
        return $this->referralCode;
    }
}
