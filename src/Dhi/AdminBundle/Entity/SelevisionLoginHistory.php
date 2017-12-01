<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ApiFailureEmail
 *
 * @ORM\Table(name="selevision_login_history")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\SelevisionLoginHistoryRepository")
 */
class SelevisionLoginHistory
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
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @ORM\Column(name="start_date", type="datetime", nullable=true)
     */
    protected $startDate;

    /**
     * @ORM\Column(name="refreshed_date", type="datetime", nullable=true)
     */
    protected $refreshedDate;

    /**
     * @ORM\Column(name="end_date", type="datetime", nullable=true)
     */
    protected $endDate;




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
     * Set startDate
     *
     * @param \DateTime $startDate
     * @return SelevisionLoginHistory
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;

        return $this;
    }

    /**
     * Get startDate
     *
     * @return \DateTime 
     */
    public function getStartDate()
    {
        return $this->startDate;
    }

    /**
     * Set refreshedDate
     *
     * @param \DateTime $refreshedDate
     * @return SelevisionLoginHistory
     */
    public function setRefreshedDate($refreshedDate)
    {
        $this->refreshedDate = $refreshedDate;

        return $this;
    }

    /**
     * Get refreshedDate
     *
     * @return \DateTime 
     */
    public function getRefreshedDate()
    {
        return $this->refreshedDate;
    }

    /**
     * Set endDate
     *
     * @param \DateTime $endDate
     * @return SelevisionLoginHistory
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;

        return $this;
    }

    /**
     * Get endDate
     *
     * @return \DateTime 
     */
    public function getEndDate()
    {
        return $this->endDate;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return SelevisionLoginHistory
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
