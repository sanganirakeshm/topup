<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * EmailCampaignHistory
 *
 * @ORM\Table(name="email_campaign_hostory")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\EmailCampaignHistoryRepository")
 */
class EmailCampaignHistory
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
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\EmailCampaign")
     * @ORM\JoinColumn(name="email_campaign_id", referencedColumnName="id")
     */
    protected $emailCampaign;
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @ORM\Column(name="blast_email", type="string", nullable=true, length=50)
     */
    protected $blastEmail;
    
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
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set blastEmail
     *
     * @param string $blastEmail
     * @return EmailCampaignHistory
     */
    public function setBlastEmail($blastEmail)
    {
        $this->blastEmail = $blastEmail;

        return $this;
    }

    /**
     * Get blastEmail
     *
     * @return string 
     */
    public function getBlastEmail()
    {
        return $this->blastEmail;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return EmailCampaignHistory
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
     * @return EmailCampaignHistory
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
     * Set emailCampaign
     *
     * @param \Dhi\UserBundle\Entity\EmailCampaign $emailCampaign
     * @return EmailCampaignHistory
     */
    public function setEmailCampaign(\Dhi\UserBundle\Entity\EmailCampaign $emailCampaign = null)
    {
        $this->emailCampaign = $emailCampaign;

        return $this;
    }

    /**
     * Get emailCampaign
     *
     * @return \Dhi\UserBundle\Entity\EmailCampaign 
     */
    public function getEmailCampaign()
    {
        return $this->emailCampaign;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return EmailCampaignHistory
     */
    public function setUser(\Dhi\UserBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Dhi\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }
}
