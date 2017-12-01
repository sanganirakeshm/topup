<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * ServicePartner
 *
 * @ORM\Entity
 * @ORM\Table(name="survey_monkey_mail_log")
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\SurveyMonkeyMailLogRepository")
 */
class SurveyMonkeyMailLog
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
     * @ORM\Column(name="user_id", type="integer", length=10, nullable=false)
     */
    protected $userId;
    
    /**
     * @ORM\Column(name="package_id", type="integer", length=10, nullable=false)
     */
    protected $packageId;
    
    /**
     * @ORM\Column(name="surevey_id", type="integer", length=10, nullable=false)
     */
    protected $surveyId;
    
    /**
     * @ORM\Column(name="send_at", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    protected $sendAt;

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
     * Set userId
     *
     * @param integer $userId
     * @return SurveyMonkeyMailLog
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set packageId
     *
     * @param integer $packageId
     * @return SurveyMonkeyMailLog
     */
    public function setPackageId($packageId)
    {
        $this->packageId = $packageId;

        return $this;
    }

    /**
     * Get packageId
     *
     * @return integer 
     */
    public function getPackageId()
    {
        return $this->packageId;
    }

    /**
     * Set sendAt
     *
     * @param \DateTime $sendAt
     * @return SurveyMonkeyMailLog
     */
    public function setSendAt($sendAt)
    {
        $this->sendAt = $sendAt;

        return $this;
    }

    /**
     * Get sendAt
     *
     * @return \DateTime 
     */
    public function getSendAt()
    {
        return $this->sendAt;
    }

    /**
     * Set surveyId
     *
     * @param integer $surveyId
     * @return SurveyMonkeyMailLog
     */
    public function setSurveyId($surveyId)
    {
        $this->surveyId = $surveyId;

        return $this;
    }

    /**
     * Get surveyId
     *
     * @return integer 
     */
    public function getSurveyId()
    {
        return $this->surveyId;
    }
}
