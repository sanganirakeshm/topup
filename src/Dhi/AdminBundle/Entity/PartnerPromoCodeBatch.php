<?php

namespace Dhi\AdminBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PartnerPromoBatch
 *
 * @ORM\Table(name="partner_promo_code_batch")
 * @ORM\Entity
 * @ORM\Entity(repositoryClass="Dhi\AdminBundle\Repository\PartnerPromoCodeBatchRepository")
 */
class PartnerPromoCodeBatch
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
     * @ORM\ManyToOne(targetEntity="ServicePartner", inversedBy="batches")
     * @ORM\JoinColumn(name="partner_id", referencedColumnName="id")
     */
    protected $partner;

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
     * @ORM\OneToMany(targetEntity="PartnerPromoCodes", mappedBy="batchId")
     */
    protected $promoCodes;

    /**
     * @ORM\Column(name="batch_name", type="string", nullable=false, length=10)
     */
    protected $batchName;
    
    /**
     * @ORM\Column(name="status", type="string", columnDefinition="ENUM('Active','Inactive')")
     */
    protected $status;
    
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
     * Set partner
     *
     * @param \Dhi\AdminBundle\Entity\ServicePartner $partner
     * @return PartnerPromoCodeBatch
     */
    public function setPartner(\Dhi\AdminBundle\Entity\ServicePartner $partner = null)
    {
        $this->partner = $partner;

        return $this;
    }

    /**
     * Get partner
     *
     * @return \Dhi\AdminBundle\Entity\ServicePartner 
     */
    public function getPartner()
    {
        return $this->partner;
    }

    /**
     * Set noOfCodes
     *
     * @param integer $noOfCodes
     * @return PartnerPromoCodeBatch
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
     * @return PartnerPromoCodeBatch
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
     * Constructor
     */
    public function __construct()
    {
        $this->promoCodes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add promoCodes
     *
     * @param \Dhi\AdminBundle\Entity\PartnerPromoCodes $promoCodes
     * @return PartnerPromoCodeBatch
     */
    public function addPromoCode(\Dhi\AdminBundle\Entity\PartnerPromoCodes $promoCodes)
    {
        $this->promoCodes[] = $promoCodes;

        return $this;
    }

    /**
     * Remove promoCodes
     *
     * @param \Dhi\AdminBundle\Entity\PartnerPromoCodes $promoCodes
     */
    public function removePromoCode(\Dhi\AdminBundle\Entity\PartnerPromoCodes $promoCodes)
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
     * Set batchName
     *
     * @param string $batchName
     * @return PartnerPromoCodeBatch
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
     * @return PartnerPromoCodeBatch
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
     * Set note
     *
     * @param string $note
     * @return PartnerPromoCodeBatch
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
