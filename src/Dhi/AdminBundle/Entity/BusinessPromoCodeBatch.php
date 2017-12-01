<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * BusinessPromoBatch
 *
 * @ORM\Table(name="business_promo_code_batch")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\BusinessPromoCodeBatchRepository")
 */
class BusinessPromoCodeBatch
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
     * @ORM\ManyToOne(targetEntity="Business", inversedBy="batches")
     * @ORM\JoinColumn(name="business_id", referencedColumnName="id")
     */
    protected $business;

    /**
     * @ORM\Column(name="no_of_codes", type="integer", length=5, nullable=false)
     */
    protected $noOfCodes;
    
    /**
     * @ORM\Column(name="reason", type="string", nullable=false)
     */
    protected $reason;

     /**
     * @ORM\Column(name="note", type="string", nullable=true)
     */
    protected $note;

    /**
     * @ORM\Column(name="batch_name", type="string", nullable=false, length=10)
     */
    protected $batchName;
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Active','Inactive')")
     */
    protected $status;

    /**
     * @ORM\OneToMany(targetEntity="BusinessPromoCodes", mappedBy="batchId")
     */
    protected $promoCodes;


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
     * Set noOfCodes
     *
     * @param integer $noOfCodes
     * @return BusinessPromoCodeBatch
     */
    public function setNoOfCodes($noOfCodes)
    {
        $this->noOfCodes = $noOfCodes;

        return $this;
    }

    /**
     * Get noOfCodes
     *
     * @return integer 
     */
    public function getNoOfCodes()
    {
        return $this->noOfCodes;
    }

    /**
     * Set reason
     *
     * @param string $reason
     * @return BusinessPromoCodeBatch
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string 
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set batchName
     *
     * @param string $batchName
     * @return BusinessPromoCodeBatch
     */
    public function setBatchName($batchName)
    {
        $this->batchName = $batchName;

        return $this;
    }

    /**
     * Get batchName
     *
     * @return string 
     */
    public function getBatchName()
    {
        return $this->batchName;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return BusinessPromoCodeBatch
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set business
     *
     * @param \Dhi\AdminBundle\Entity\Business $business
     * @return BusinessPromoCodeBatch
     */
    public function setBusiness(\Dhi\AdminBundle\Entity\Business $business = null)
    {
        $this->business = $business;

        return $this;
    }

    /**
     * Get business
     *
     * @return \Dhi\AdminBundle\Entity\Business 
     */
    public function getBusiness()
    {
        return $this->business;
    }
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->promoCodes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add promoCodes
     *
     * @param \Dhi\AdminBundle\Entity\BusinessPromoCodes $promoCodes
     * @return BusinessPromoCodeBatch
     */
    public function addPromoCode(\Dhi\AdminBundle\Entity\BusinessPromoCodes $promoCodes)
    {
        $this->promoCodes[] = $promoCodes;

        return $this;
    }

    /**
     * Remove promoCodes
     *
     * @param \Dhi\AdminBundle\Entity\BusinessPromoCodes $promoCodes
     */
    public function removePromoCode(\Dhi\AdminBundle\Entity\BusinessPromoCodes $promoCodes)
    {
        $this->promoCodes->removeElement($promoCodes);
    }

    /**
     * Get promoCodes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPromoCodes()
    {
        return $this->promoCodes;
    }

    /**
     * Set note
     *
     * @param string $note
     * @return BusinessPromoCodeBatch
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return string 
     */
    public function getNote()
    {
        return $this->note;
    }
}
