<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * UserChaseInfo
 *
 * @ORM\Table(name="user_chase_info")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserChaseInfoRepository")
 */
class UserChaseInfo
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userChaseInfo")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ChaseMerchantIds", inversedBy="userChaseInfo")
     * @ORM\JoinColumn(name="merchant_id", referencedColumnName="id")
     */
    protected $merchantId;
    
    /**
     * @ORM\Column(name="customer_ref_num", type="bigint", nullable=true)
     */
    protected $customerRefNum;
    
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
     * Set customerRefNum
     *
     * @param integer $customerRefNum
     * @return UserChaseInfo
     */
    public function setCustomerRefNum($customerRefNum)
    {
        $this->customerRefNum = $customerRefNum;

        return $this;
    }

    /**
     * Get customerRefNum
     *
     * @return integer 
     */
    public function getCustomerRefNum()
    {
        return $this->customerRefNum;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserChaseInfo
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
     * @return UserChaseInfo
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
     * @return UserChaseInfo
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
     * Set merchantId
     *
     * @param \Dhi\AdminBundle\Entity\ChaseMerchantIds $merchantId
     * @return UserChaseInfo
     */
    public function setMerchantId(\Dhi\AdminBundle\Entity\ChaseMerchantIds $merchantId = null)
    {
        $this->merchantId = $merchantId;

        return $this;
    }

    /**
     * Get merchantId
     *
     * @return \Dhi\AdminBundle\Entity\ChaseMerchantIds 
     */
    public function getMerchantId()
    {
        return $this->merchantId;
    }
}
