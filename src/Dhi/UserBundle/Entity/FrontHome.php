<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="front_home")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\FrontHomeRepository")
 * @UniqueEntity("country")
 */

class FrontHome {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id",unique=true)
     */
    protected $country;

	 /**
     * @ORM\Column(name="column1", type="text", nullable=false)
     */
    protected $column1;

	 /**
     * @ORM\Column(name="message1", type="text", nullable=false)
     */
    protected $message1;
	 /**
     * @ORM\Column(name="column2", type="text", nullable=true)
     */
    protected $column2;

	 /**
     * @ORM\Column(name="message2", type="text", nullable=true)
     */
    protected $message2;
	 /**
     * @ORM\Column(name="column3", type="text", nullable=true)
     */
    protected $column3;

	 /**
     * @ORM\Column(name="message3", type="text", nullable=true)
     */
    protected $message3;
	 /**
     * @ORM\Column(name="column4", type="text", nullable=true)
     */
    protected $column4;

	 /**
     * @ORM\Column(name="message4", type="text", nullable=true)
     */
    protected $message4;

	



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
     * Set column1
     *
     * @param string $column1
     * @return FrontHome
     */
    public function setColumn1($column1)
    {
        $this->column1 = $column1;


        return $this;
    }

    /**
     * Get column1
     *
     * @return string 
     */
    public function getColumn1()
    {
        return $this->column1;
    }

    /**
     * Set message1
     *
     * @param string $message1
     * @return FrontHome
     */
    public function setMessage1($message1)
    {
        $this->message1 = $message1;

        return $this;
    }

    /**
     * Get message1
     *
     * @return string 
     */
    public function getMessage1()
    {
        return $this->message1;
    }

    /**
     * Set column2
     *
     * @param string $column2
     * @return FrontHome
     */
    public function setColumn2($column2)
    {
        $this->column2 = $column2;

        return $this;
    }

    /**
     * Get column2
     *
     * @return string 
     */
    public function getColumn2()
    {
        return $this->column2;
    }

    /**
     * Set message2
     *
     * @param string $message2
     * @return FrontHome
     */
    public function setMessage2($message2)
    {
        $this->message2 = $message2;

        return $this;
    }

    /**
     * Get message2
     *
     * @return string 
     */
    public function getMessage2()
    {
        return $this->message2;
    }

    /**
     * Set column3
     *
     * @param string $column3
     * @return FrontHome
     */
    public function setColumn3($column3)
    {
        $this->column3 = $column3;

        return $this;
    }

    /**
     * Get column3
     *
     * @return string 
     */
    public function getColumn3()
    {
        return $this->column3;
    }

    /**
     * Set message3
     *
     * @param string $message3
     * @return FrontHome
     */
    public function setMessage3($message3)
    {
        $this->message3 = $message3;

        return $this;
    }

    /**
     * Get message3
     *
     * @return string 
     */
    public function getMessage3()
    {
        return $this->message3;
    }

    /**
     * Set column4
     *
     * @param string $column4
     * @return FrontHome
     */
    public function setColumn4($column4)
    {
        $this->column4 = $column4;

        return $this;
    }

    /**
     * Get column4
     *
     * @return string 
     */
    public function getColumn4()
    {
        return $this->column4;
    }

    /**
     * Set message4
     *
     * @param string $message4
     * @return FrontHome
     */
    public function setMessage4($message4)
    {
        $this->message4 = $message4;

        return $this;
    }

    /**
     * Get message4
     *
     * @return string 
     */
    public function getMessage4()
    {
        return $this->message4;
    }

    /**
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return FrontHome
     */
    public function setCountry(\Dhi\UserBundle\Entity\Country $country = null)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Dhi\UserBundle\Entity\Country 
     */
    public function getCountry()
    {
        return $this->country;
    }


}
