<?php

namespace Dhi\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="permission_category")
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\PermissionCategoryRepository")
 */
class PermissionCategory {
	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	protected $id;
	
	/**
	 * Get id
	 *
	 * @return integer
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * @var string
	 *
	 * @ORM\Column(name="name", type="string", length=100, nullable=false)
	 */
	protected $name;
	
	/**
     *
     * @ORM\OneToMany(targetEntity="Permission", mappedBy="category")
     */
    protected $permissions;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }



    /**
     * Set name
     *
     * @param string $name
     * @return PermissionCategory
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add permissions
     *
     * @param \Dhi\UserBundle\Entity\Permission $permissions
     * @return PermissionCategory
     */
    public function addPermission(\Dhi\UserBundle\Entity\Permission $permissions)
    {
        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Dhi\UserBundle\Entity\Permission $permissions
     */
    public function removePermission(\Dhi\UserBundle\Entity\Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPermissions()
    {
        return $this->permissions;
    }
}
