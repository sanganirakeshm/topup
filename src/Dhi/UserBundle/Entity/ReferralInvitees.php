<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ReferralInvitees
 *
 * @ORM\Table(name="referral_invitees")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\ReferralInviteesRepository")
 */
class ReferralInvitees
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="referralInvitees")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $userId;
    
    /**
     * @ORM\Column(name="email_id", type="string", length=255, nullable=false)
     */
    protected $emailId;    
    
    /**
     * @ORM\Column(name="is_purchased", type="boolean")
     */
    protected $isPurchased;    
    
    /**
     * @ORM\Column(name="token", type="string", length=255, nullable=false)
     */
    protected $token;    

    /**
     * @ORM\Column(name="is_register", type="boolean")
     */
    protected $isRegister;    
    
    /**
     * @ORM\Column(name="promo_code_email_sent", type="boolean", nullable=false)
     */
    protected $promoCodeEmailSent = false;    
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel", inversedBy="referralInvitees")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $whiteLabel;
    
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
     * Set emailId
     *
     * @param string $emailId
     * @return ReferralInvitees
     */
    public function setEmailId($emailId)
    {
        $this->emailId = $emailId;

        return $this;
    }

    /**
     * Get emailId
     *
     * @return string 
     */
    public function getEmailId()
    {
        return $this->emailId;
    }

    /**
     * Set isPurchased
     *
     * @param boolean $isPurchased
     * @return ReferralInvitees
     */
    public function setIsPurchased($isPurchased)
    {
        $this->isPurchased = $isPurchased;

        return $this;
    }

    /**
     * Get isPurchased
     *
     * @return boolean 
     */
    public function getIsPurchased()
    {
        return $this->isPurchased;
    }

    /**
     * Set token
     *
     * @param string $token
     * @return ReferralInvitees
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get token
     *
     * @return string 
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set isRegister
     *
     * @param boolean $isRegister
     * @return ReferralInvitees
     */
    public function setIsRegister($isRegister)
    {
        $this->isRegister = $isRegister;

        return $this;
    }

    /**
     * Get isRegister
     *
     * @return boolean 
     */
    public function getIsRegister()
    {
        return $this->isRegister;
    }

    /**
     * Set promoCodeEmailSent
     *
     * @param boolean $promoCodeEmailSent
     * @return ReferralInvitees
     */
    public function setPromoCodeEmailSent($promoCodeEmailSent)
    {
        $this->promoCodeEmailSent = $promoCodeEmailSent;

        return $this;
    }

    /**
     * Get promoCodeEmailSent
     *
     * @return boolean 
     */
    public function getPromoCodeEmailSent()
    {
        return $this->promoCodeEmailSent;
    }

    /**
     * Set userId
     *
     * @param \Dhi\UserBundle\Entity\User $userId
     * @return ReferralInvitees
     */
    public function setUserId(\Dhi\UserBundle\Entity\User $userId = null)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set whiteLabel
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel
     * @return ReferralInvitees
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
}
