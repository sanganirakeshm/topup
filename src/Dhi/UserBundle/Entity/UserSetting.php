<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="user_setting")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserSettingRepository")
 */
class UserSetting
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
     * @ORM\OneToOne(targetEntity="User", inversedBy="userSetting")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var integer
     *
     * @ORM\Column(name="mac_address", type="integer", nullable=true)
     */
    protected $macAddress;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="max_daily_transaction", type="integer", nullable=true)
     */
    protected $maxDailyTransaction;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="admin_id", type="integer")
     */
    protected $adminId;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
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
     * Set macAddress
     *
     * @param integer $macAddress
     * @return UserSetting
     */
    public function setMacAddress($macAddress)
    {
        $this->macAddress = $macAddress;

        return $this;
    }

    /**
     * Get macAddress
     *
     * @return integer 
     */
    public function getMacAddress()
    {
        return $this->macAddress;
    }

    /**
     * Set adminId
     *
     * @param integer $adminId
     * @return UserSetting
     */
    public function setAdminId($adminId)
    {
        $this->adminId = $adminId;

        return $this;
    }

    /**
     * Get adminId
     *
     * @return integer 
     */
    public function getAdminId()
    {
        return $this->adminId;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserSetting
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
     * @return UserSetting
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return UserSetting
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

    /**
     * Set maxDailyTransaction
     *
     * @param integer $maxDailyTransaction
     * @return UserSetting
     */
    public function setMaxDailyTransaction($maxDailyTransaction)
    {
        $this->maxDailyTransaction = $maxDailyTransaction;

        return $this;
    }

    /**
     * Get maxDailyTransaction
     *
     * @return integer 
     */
    public function getMaxDailyTransaction()
    {
        return $this->maxDailyTransaction;
    }
}
