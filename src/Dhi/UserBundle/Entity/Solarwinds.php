<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations

/**
 * @ORM\Entity
 * @ORM\Table(name="solar_winds_api_log")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\SolarwindsRepository")
 */

class Solarwinds {

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(name="ticket_id", type="string", length=150)
     *
     */
    protected $ticketId;

    /**
     * @ORM\Column(name="endpoint", type="string", length=150)
     *
     */
    protected $endpoint;

    /**
     * @ORM\Column(name="http_code", type="string", length=150)
     *
     */
    protected $httpCode;

    /**
     * @ORM\Column(name="action", type="string", length=50)
     *
     */
    protected $action;
    
    /**
     * @ORM\Column(name="url_parameters", type="text", nullable=true, options={"comment":"GET Request Parameter"})
     *
     */
    protected $urlParameters;

    /**
     * @ORM\Column(name="request", type="text", nullable=true, options={"comment":"POST Request Parameter"})
     *
     */
    protected $request;
    
    /**
     * @ORM\Column(name="response", type="text", nullable=true)
     *
     */
    protected $response;
    
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     * @Gedmo\Timestampable(on="create")
     */
    protected $createdAt;

    /**
     * @ORM\Column(name="ip_address", type="string", length=15, nullable=true)
     */
    protected $ipAddress;

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
     * Set ticketId
     *
     * @param string $ticketId
     * @return Solarwinds
     */
    public function setTicketId($ticketId)
    {
        $this->ticketId = $ticketId;

        return $this;
    }

    /**
     * Get ticketId
     *
     * @return string 
     */
    public function getTicketId()
    {
        return $this->ticketId;
    }

    /**
     * Set request
     *
     * @param string $request
     * @return Solarwinds
     */
    public function setRequest($request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Get request
     *
     * @return string 
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Set response
     *
     * @param string $response
     * @return Solarwinds
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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return Solarwinds
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
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return Solarwinds
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string 
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set action
     *
     * @param string $action
     * @return Solarwinds
     */
    public function setAction($action)
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action
     *
     * @return string 
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set endpoint
     *
     * @param string $endpoint
     * @return Solarwinds
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;

        return $this;
    }

    /**
     * Get endpoint
     *
     * @return string 
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Set httpCode
     *
     * @param string $httpCode
     * @return Solarwinds
     */
    public function setHttpCode($httpCode)
    {
        $this->httpCode = $httpCode;

        return $this;
    }

    /**
     * Get httpCode
     *
     * @return string 
     */
    public function getHttpCode()
    {
        return $this->httpCode;
    }

    /**
     * Set urlParameters
     *
     * @param string $urlParameters
     * @return Solarwinds
     */
    public function setUrlParameters($urlParameters)
    {
        $this->urlParameters = $urlParameters;

        return $this;
    }

    /**
     * Get urlParameters
     *
     * @return string 
     */
    public function getUrlParameters()
    {
        return $this->urlParameters;
    }
}
