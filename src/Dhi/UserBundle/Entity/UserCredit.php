<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Dhi\UserBundle\Entity\User;
/**
 * ServiceLocation
 *
 * @ORM\Table(name="user_credit")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserCreditRepository")
 */
class UserCredit
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
     * @ORM\OneToOne(targetEntity="User", inversedBy="userCredit")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    
    /**
     * @ORM\Column(name="total_credits", type="integer", nullable=false)
     */
    protected $totalCredits;    
    
    /**
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;    
    
            

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
     * Set totalCredits
     *
     * @param integer $totalCredits
     * @return UserCredit
     */
    public function setTotalCredits($totalCredits)
    {
        $this->totalCredits = $totalCredits;

        return $this;
    }

    /**
     * Get totalCredits
     *
     * @return integer 
     */
    public function getTotalCredits()
    {
        return $this->totalCredits;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return UserCredit
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
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return UserCredit
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
