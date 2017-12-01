<?php

namespace Dhi\ServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="chase_checkout")
 */

class ChaseCheckout {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     *
     * @ORM\ManyToOne(targetEntity="Dhi\UserBundle\Entity\User", inversedBy="chasePayment")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;
    
    /**
     * @var string
     *
     * @ORM\Column(name="customer_ref_no", type="string", length=255, nullable=true)
     */
    protected $customerRefNo;

    /**
     * @var string
     *
     * @ORM\Column(name="chase_transaction_id",type="string", length=255, nullable=true)
     */
    protected $chaseTransactionId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", name="order_number", length=255)
     */
    protected $orderNumber;

    /**
    * @var string
    *
    * @ORM\Column(name="chase_process_status", type="string", length=30, nullable=false, options={"comment":"Completed, Failed"})
    */
    protected $chaseProcessStatus;


    /**
    * @var string
    *
    * @ORM\Column(name="trans_type", type="string", length=30, nullable=false, options={"comment":"new-profile, with-profile, without-profile"})
    */
    protected $transType;

    /**
     * @var text
     * @ORM\Column(name="response",type="text", nullable=true)
     */
    protected $response;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $created_at;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="updated_at", type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated_at;

    /**
     * @ORM\OneToOne(targetEntity="PurchaseOrder", mappedBy="chase")
     */
    protected $purchaseOrder;


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
     * Set customerRefNo
     *
     * @param string $customerRefNo
     * @return Chase
     */
    public function setCustomerRefNo($customerRefNo)
    {
        $this->customerRefNo = $customerRefNo;

        return $this;
    }

    /**
     * Get customerRefNo
     *
     * @return string 
     */
    public function getCustomerRefNo()
    {
        return $this->customerRefNo;
    }

    /**
     * Set chaseTransactionId
     *
     * @param string $chaseTransactionId
     * @return Chase
     */
    public function setChaseTransactionId($chaseTransactionId)
    {
        $this->chaseTransactionId = $chaseTransactionId;

        return $this;
    }

    /**
     * Get chaseTransactionId
     *
     * @return string 
     */
    public function getChaseTransactionId()
    {
        return $this->chaseTransactionId;
    }

    /**
     * Set chaseProcessStatus
     *
     * @param string $chaseProcessStatus
     * @return Chase
     */
    public function setChaseProcessStatus($chaseProcessStatus)
    {
        $this->chaseProcessStatus = $chaseProcessStatus;

        return $this;
    }

    /**
     * Get chaseProcessStatus
     *
     * @return string 
     */
    public function getChaseProcessStatus()
    {
        return $this->chaseProcessStatus;
    }

    /**
     * Set response
     *
     * @param string $response
     * @return Chase
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get response
     *
     * @return string 
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     * @return Chase
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime 
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     * @return Chase
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime 
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set user
     *
     * @param \Dhi\UserBundle\Entity\User $user
     * @return Chase
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
     * Set purchaseOrder
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrder
     * @return ChaseCheckout
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
     * Set transType
     *
     * @param string $transType
     * @return ChaseCheckout
     */
    public function setTransType($transType)
    {
        $this->transType = $transType;

        return $this;
    }

    /**
     * Get transType
     *
     * @return string 
     */
    public function getTransType()
    {
        return $this->transType;
    }

    /**
     * Set orderNumber
     *
     * @param string $orderNumber
     * @return ChaseCheckout
     */
    public function setOrderNumber($orderNumber)
    {
        $this->orderNumber = $orderNumber;

        return $this;
    }

    /**
     * Get orderNumber
     *
     * @return string 
     */
    public function getOrderNumber()
    {
        return $this->orderNumber;
    }
}
