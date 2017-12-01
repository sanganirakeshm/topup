<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations


/**
 * @ORM\Entity
 * @ORM\Table(name="milstar")
 * @ORM\Entity(repositoryClass="Dhi\ServiceBundle\Repository\MilstarRepository")
 */

class Milstar {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="milstar")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    
    /**
     * @ORM\OneToOne(targetEntity="PurchaseOrder", mappedBy="milstar")
     */
    protected $purchaseOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="request_id", type="string", length=50)
     */
    protected $requestId;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="total_amount", type="decimal", precision= 10, scale= 2, nullable=false)
     */
    protected $payableAmount;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="fac_nbr", type="integer")
     */
    protected $facNbr;
    
    /**
    * @var string
    *
    * @ORM\Column(name="region", type="string", length=2)
    */
    protected $region;
    
    /**
     * @var string
     *
     * @ORM\Column(name="zipcode", type="string", length= 255, nullable=true)
     */
    protected $zipcode;
            
    /**
     * @var string
     *
     * @ORM\Column(name="cid", type="string", length=15)
     */
    protected $cid;
    
    /**
     * @var string
     *
     * @ORM\Column(name="card_no", type="string", length=16)
     */
    protected $cardNo;
    
    /**
     * @var string
     *
     * @ORM\Column(name="fail_code", type="string", length=6, nullable=true)
     */
    protected $failCode;
    
    /**
    * @var string
    *
    * @ORM\Column(name="fail_message", type="string", length=255, nullable=true)
    */
    protected $failMessage;
    
    /**
     * @var string
     *
     * @ORM\Column(name="auth_code", type="string", length=6, nullable=true)
     */
    protected $authCode;
    
    /**
     * @var string
     *
     * @ORM\Column(name="auth_ticket", type="string", length=15, nullable=true)
     */
    protected $authTicket;
    
    /**
     * @var string
     *
     * @ORM\Column(name="response_code", type="string", length=2, nullable=true)
     */
    protected $responseCode;
    
    /**
     * @var string
     *
     * @ORM\Column(name="message", type="string", length=255, nullable=true)
     */
    protected $message;
    
    /**
     * @ORM\Column(name="process_status", type="string", columnDefinition="ENUM('MSApproval', 'MSSettlement', 'MSCredit')", options={"default":"New", "comment":"MSApproval, MSSettlement, MSCredit"})
     */
    protected $processStatus;
    
    /**
     * @var string
     *
     * @ORM\Column(name="ip_address", type="string", length=16, nullable=true)
     */
    protected $ip_address;
    
    /**
    * @ORM\Column(name="created_at", type="datetime", nullable=true)
    * @Gedmo\Timestampable(on="create")
    */
    protected $createdAt;
    
    /**
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
     * Set requestId
     *
     * @param string $requestId
     * @return Milstar
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Get requestId
     *
     * @return string 
     */
    public function getRequestId()
    {
        return $this->requestId;
    }

    /**
     * Set payableAmount
     *
     * @param string $payableAmount
     * @return Milstar
     */
    public function setPayableAmount($payableAmount)
    {
        $this->payableAmount = $payableAmount;

        return $this;
    }

    /**
     * Get payableAmount
     *
     * @return string 
     */
    public function getPayableAmount()
    {
        return $this->payableAmount;
    }

    /**
     * Set facNbr
     *
     * @param integer $facNbr
     * @return Milstar
     */
    public function setFacNbr($facNbr)
    {
        $this->facNbr = $facNbr;

        return $this;
    }

    /**
     * Get facNbr
     *
     * @return integer 
     */
    public function getFacNbr()
    {
        return $this->facNbr;
    }

    /**
     * Set region
     *
     * @param string $region
     * @return Milstar
     */
    public function setRegion($region)
    {
        $this->region = $region;

        return $this;
    }

    /**
     * Get region
     *
     * @return string 
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * Set zipcode
     *
     * @param string $zipcode
     * @return Milstar
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;

        return $this;
    }

    /**
     * Get zipcode
     *
     * @return string 
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * Set cid
     *
     * @param string $cid
     * @return Milstar
     */
    public function setCid($cid)
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * Get cid
     *
     * @return string 
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Set cardNo
     *
     * @param string $cardNo
     * @return Milstar
     */
    public function setCardNo($cardNo)
    {
        $this->cardNo = $cardNo;

        return $this;
    }

    /**
     * Get cardNo
     *
     * @return string 
     */
    public function getCardNo()
    {
        return $this->cardNo;
    }

    /**
     * Set failCode
     *
     * @param string $failCode
     * @return Milstar
     */
    public function setFailCode($failCode)
    {
        $this->failCode = $failCode;

        return $this;
    }

    /**
     * Get failCode
     *
     * @return string 
     */
    public function getFailCode()
    {
        return $this->failCode;
    }

    /**
     * Set failMessage
     *
     * @param string $failMessage
     * @return Milstar
     */
    public function setFailMessage($failMessage)
    {
        $this->failMessage = $failMessage;

        return $this;
    }

    /**
     * Get failMessage
     *
     * @return string 
     */
    public function getFailMessage()
    {
        return $this->failMessage;
    }

    /**
     * Set authCode
     *
     * @param string $authCode
     * @return Milstar
     */
    public function setAuthCode($authCode)
    {
        $this->authCode = $authCode;

        return $this;
    }

    /**
     * Get authCode
     *
     * @return string 
     */
    public function getAuthCode()
    {
        return $this->authCode;
    }

    /**
     * Set authTicket
     *
     * @param string $authTicket
     * @return Milstar
     */
    public function setAuthTicket($authTicket)
    {
        $this->authTicket = $authTicket;

        return $this;
    }

    /**
     * Get authTicket
     *
     * @return string 
     */
    public function getAuthTicket()
    {
        return $this->authTicket;
    }

    /**
     * Set responseCode
     *
     * @param string $responseCode
     * @return Milstar
     */
    public function setResponseCode($responseCode)
    {
        $this->responseCode = $responseCode;

        return $this;
    }

    /**
     * Get responseCode
     *
     * @return string 
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return Milstar
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string 
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set processStatus
     *
     * @param string $processStatus
     * @return Milstar
     */
    public function setProcessStatus($processStatus)
    {
        $this->processStatus = $processStatus;

        return $this;
    }

    /**
     * Get processStatus
     *
     * @return string 
     */
    public function getProcessStatus()
    {
        return $this->processStatus;
    }

    /**
     * Set ip_address
     *
     * @param string $ipAddress
     * @return Milstar
     */
    public function setIpAddress($ipAddress)
    {
        $this->ip_address = $ipAddress;

        return $this;
    }

    /**
     * Get ip_address
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Milstar
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
     * @return Milstar
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
     * Set purchaseOrder
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return Milstar
     */
    public function setPurchaseOrder(\Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder = null)
    {
        $this->purchaseOrder = $purchaseOrder;

        return $this;
    }

    /**
     * Get purchaseOrder
     *
     * @return \Dhi\ServiceBundle\Entity\PurchaseOrder 
     */
    public function getPurchaseOrder()
    {
        return $this->purchaseOrder;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return Milstar
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
