<?php

namespace Dhi\UserBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo; // gedmo annotations
use Doctrine\ORM\Mapping\Index as Index;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="dhi_user",indexes={
        @Index(name="dhi_user_locked_idx", columns={"locked"}),
        @Index(name="dhi_user_is_deleted_idx", columns={"is_deleted"})
    })
 * @ORM\Entity(repositoryClass="Dhi\UserBundle\Repository\UserRepository")
 */

class User extends BaseUser {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function __construct() {
        parent::__construct();
        $this->roles = array('ROLE_USER');
        $this->groups = new ArrayCollection();
        $this->serviceLocations = new ArrayCollection();
        $this->compensations = new ArrayCollection();
    }

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $firstname;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $lastname;

    /**
     * @ORM\Column(name="ip_address", type="string", length=15, nullable=true)
     */
    protected $ipAddress;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $address;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $state;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $zip;

    /**
     * @var string
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     */
    protected $phone;

    /**
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country", referencedColumnName="id")
     */
    protected $country;

    /**
     * @ORM\Column(name="user_type", type="string", columnDefinition="enum('US Military', 'US Government', 'Civilian')", options={"default":"US Military"} )
     */
    protected $userType = 'US Military';

    /**
     * @ORM\Column(name="is_email_verified", type="boolean", nullable=true)
     */
    protected $isEmailVerified = false;

    /**
     * @ORM\Column(name="email_verification_date", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="create")
     */
    protected $emailVerificationDate;

    /**
     * @ORM\Column(name="is_email_optout", type="boolean", nullable=true)
     */
    protected $isEmailOptout = false;

    /**
     * @ORM\Column(name="is_loggedin", type="boolean", nullable=false, options={"default":false})
     */
    protected $isloggedin = false;

    /**
     * @ORM\Column(name="is_aradial_migrated", type="boolean", nullable=false, options={"default":false})
     */
    protected $isAradialMigrated = false;

    /**
     * @ORM\Column(name="is_flagged_for_isp_bal_trans", type="boolean", nullable=false, options={"default":false})
     */
    protected $isFlaggedForIspBalTrans = false;

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
     * @ORM\Column(name="is_deleted", type="boolean", nullable=true)
     */
    protected $isDeleted = false;

    /**
     * @ORM\Column(name="is_deers_authenticated", type="boolean", nullable=true)
     */
    protected $isDeersAuthenticated = false;

    /**
     * @ORM\Column(name="deers_authenticated_at", type="datetime", nullable=true)
     */
    protected $deersAuthenticatedAt;

    /**
     * @ORM\OneToMany(targetEntity="UserService", mappedBy="user")
     */
    protected $userServices;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ChaseCheckout", mappedBy="user")
     */
    protected $chasePayment;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer", mappedBy="user")
     */
    protected $employeePromoCodeCustomer;

    /**
     * @ORM\OneToMany(targetEntity="UserService", mappedBy="refundedBy")
     */
    protected $refundedByUserServices;

    /**
     * @ORM\OneToMany(targetEntity="UserService", mappedBy="expiredBy")
     */
    protected $expiredUserServices;

    /**
     * @ORM\OneToOne(targetEntity="UserSetting", mappedBy="user")
     */
    protected $userSetting;

    /**
     * @ORM\Column(name="is_iptv_disabled", type="boolean", nullable=true)
     */
    protected $isIptvDisabled = false;

    /**
     * @ORM\Column(name="new_selevision_user", type="boolean", nullable=true)
     */
    protected $newSelevisionUser = false;

    /**
     * @ORM\OneToMany(targetEntity="UserServiceSetting", mappedBy="user")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    protected $serviceSettings;

    /**
     * @ORM\Column(name="is_last_login", type="boolean", nullable=false, options={"default":false})
     */
    protected $isLastLogin = false;

    /**
     * @ORM\Column(name="is_aradial_exists", type="boolean", nullable=false, options={"default":false})
     */
    protected $isAradialExists = false;

    /**
     * @ORM\Column(name="is_employee", type="boolean", nullable=false, options={"default":false})
     */
    protected $isEmployee = false;

    /**
     * @ORM\Column(name="ip_address_long", type="bigint", nullable=true)
     */
    protected $ipAddressLong;

    /**
     * @ORM\Column(name="cid", type="bigint", nullable=true)
     */
    protected $cid;

    /**
     * @ORM\Column(name="customer_ref_num", type="bigint", nullable=true)
     */
    protected $customerRefNum;    

     /**
     * @ORM\OneToMany(targetEntity="UserMacAddress", mappedBy="user")
     */
    private $userMacAddress;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\Milstar", mappedBy="user")
     */
    private $milstar;

    /**
     * @ORM\OneToMany(targetEntity="UserCreditLog", mappedBy="user")
     */
    private $userCreditLogs;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\PaypalCheckout", mappedBy="user")
     */
    private $paypalCheckouts;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\PurchaseOrder", mappedBy="paymentByUser")
     */
    private $userPurchased;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\PurchaseOrder", mappedBy="user")
     */
    private $purchaseOrders;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\ServiceBundle\Entity\ServicePurchase", mappedBy="user")
     */
    private $servicePurchases;


   /**
     * @ORM\ManyToMany(targetEntity="Group", inversedBy="users")
     * @ORM\JoinTable(name="dhi_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    protected $groups;

     /**
	 * @ORM\ManyToMany(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation", inversedBy="admins")
	 * @ORM\JoinTable(name="admin_service_location",
	 *      joinColumns={@ORM\JoinColumn(name="admin_id", referencedColumnName="id")},
	 *      inverseJoinColumns={@ORM\JoinColumn(name="service_location_id", referencedColumnName="id")}
	 * )
	 */
    protected $serviceLocations;

    /**
     * @ORM\ManyToMany(targetEntity="Compensation", mappedBy="users")
     *
     */
    protected $compensations;

	/**
     * @ORM\OneToOne(targetEntity="UserCredit" , mappedBy="user")
     */
    protected $userCredit;

    /**
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="geo_location_country_id", referencedColumnName="id")
     */
    protected $geoLocationCountry;


     /**
     * @var string
     * @ORM\Column(name="encry_pwd", type="string", length=255, nullable=true)
     */
    protected $encryPwd;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\ServiceLocation", inversedBy="users")
     * @ORM\JoinColumn(name="user_service_location_id", referencedColumnName="id")
     */
    protected $userServiceLocation;
    
    /**
     * @ORM\OneToMany(targetEntity="ReferralPromoCode", mappedBy="refereeUserId")
     */
    protected $refereeUser;

    /**
     * @ORM\OneToMany(targetEntity="ReferralPromoCode", mappedBy="referrerUserId")
     */
    protected $referrerUser;

    /**
     * @ORM\OneToMany(targetEntity="ReferralInvitees", mappedBy="userId")
     */
    protected $referralInvitees;

    /**
     * @ORM\Column(name="is_suspended", type="boolean", nullable=false, options={"default":false})
     */
    protected $isSuspended = false;

    /**
     * @ORM\OneToMany(targetEntity="UserChaseInfo", mappedBy="user")
     */
    protected $userChaseInfo;

    /**
     * @ORM\OneToMany(targetEntity="InAppPromoCode", mappedBy="customer")
     */
    protected $inAppPromoCodeCustomer;

    /**
     * @ORM\OneToMany(targetEntity="Dhi\AdminBundle\Entity\TikilivePromoCode", mappedBy="redeemedBy")
     */
    protected $TikilivePromoCodeCustomer;

    /**
     * @ORM\ManyToOne(targetEntity="Dhi\AdminBundle\Entity\WhiteLabel")
     * @ORM\JoinColumn(name="white_label_id", referencedColumnName="id")
     */
    protected $whiteLabel;
    
    /**
     * @ORM\Column(name="is_abandoned", type="boolean", nullable=true, options={"default":false})
     */
    protected $isAbandoned = false;
    
    /**
     * @ORM\OneToMany(targetEntity="Dhi\AdminBundle\Entity\UserSuspendHistory", mappedBy="user")
     */
    protected $userSuspendHistory;

    public function getActiveServices() {
        $activeServices = array();
        foreach ($this->getUserServices() as $record) {
            if ($record->getStatus() == 1 && $record->getExpiryDate() > new \DateTime()) {
                $activeServices[$record->getService()->getId()]['name'] = $record->getService()->getName();
            }
        }
        return $activeServices;
    }

    public function getActivePackages() {

        $activePackages = array();
        $activePackages['IPTV']   = '';
        $activePackages['ISP']    = '';
        $activePackages['AddOn']  = '';
        foreach ($this->getUserServices() as $record) {

            if ($record->getStatus() == 1 && $record->getExpiryDate() > new \DateTime()) {

            	if($record->getIsAddon() == 1){

            		$serviceName = 'AddOn';
            	}else{
            		$serviceName = $record->getService()->getName();
            	}


                $temp = array();
                $temp['packageId'] 	 = $record->getPackageId();
                $temp['packageName'] = $record->getPackageName();
                $temp['amount'] 	 = $record->getActualAmount();
                $temp['bandwidth'] 	 = $record->getBandwidth();
                $temp['validity']    = $record->getValidity();
                $temp['actualValidity']    = $record->getActualValidity();

                if($record->getServicePurchase()){
                    $temp['bundle'] = $record->getServicePurchase()->getBundleId();
                }

                $activePackages[$serviceName] = $temp;
                $activePackages[$serviceName.'Ids'][] = $temp['packageId'];
            }
        }
        return $activePackages;
    }

    public function getActivePackageIds() {
        $activePackageIds = array();
        foreach ($this->getUserServices() as $record) {

            if ($record->getStatus() == 1 && $record->getExpiryDate() > new \DateTime()) {
                $activePackageIds[] = $record->getPackageId();
            }
        }
        return $activePackageIds;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set firstname
     *
     * @param string $firstname
     * @return User
     */
    public function setFirstname($firstname) {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * Get firstname
     *
     * @return string
     */
    public function getFirstname() {
        return $this->firstname;
    }

    /**
     * Set lastname
     *
     * @param string $lastname
     * @return User
     */
    public function setLastname($lastname) {
        $this->lastname = $lastname;

        return $this;
    }

    /**
     * Get lastname
     *
     * @return string
     */
    public function getLastname() {
        return $this->lastname;
    }

    /**
     * Set ipAddress
     *
     * @param string $ipAddress
     * @return User
     */
    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress
     *
     * @return string
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * Set address
     *
     * @param string $address
     * @return User
     */
    public function setAddress($address) {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }

    /**
     * Set city
     *
     * @param string $city
     * @return User
     */
    public function setCity($city) {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * Set state
     *
     * @param string $state
     * @return User
     */
    public function setState($state) {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState() {
        return $this->state;
    }

    /**
     * Set zip
     *
     * @param string $zip
     * @return User
     */
    public function setZip($zip) {
        $this->zip = $zip;

        return $this;
    }

    /**
     * Get zip
     *
     * @return string
     */
    public function getZip() {
        return $this->zip;
    }

    /**
     * Set phone
     *
     * @param string $phone
     * @return User
     */
    public function setPhone($phone) {
        $this->phone = $phone;

        return $this;
    }

    /**
     * Get phone
     *
     * @return string
     */
    public function getPhone() {
        return $this->phone;
    }

    /**
     * Set userType
     *
     * @param string $userType
     * @return User
     */
    public function setUserType($userType) {
        $this->userType = $userType;

        return $this;
    }

    /**
     * Get userType
     *
     * @return string
     */
    public function getUserType() {
        return $this->userType;
    }

    /**
     * Set isEmailVerified
     *
     * @param boolean $isEmailVerified
     * @return User
     */
    public function setIsEmailVerified($isEmailVerified) {
        $this->isEmailVerified = $isEmailVerified;

        return $this;
    }

    /**
     * Get isEmailVerified
     *
     * @return boolean
     */
    public function getIsEmailVerified() {
        return $this->isEmailVerified;
    }

    /**
     * Set emailVerificationDate
     *
     * @param \DateTime $emailVerificationDate
     * @return User
     */
    public function setEmailVerificationDate($emailVerificationDate) {
        $this->emailVerificationDate = $emailVerificationDate;

        return $this;
    }

    /**
     * Get emailVerificationDate
     *
     * @return \DateTime
     */
    public function getEmailVerificationDate() {
        return $this->emailVerificationDate;
    }

    /**
     * Set isEmailOptout
     *
     * @param boolean $isEmailOptout
     * @return User
     */
    public function setIsEmailOptout($isEmailOptout) {
        $this->isEmailOptout = $isEmailOptout;

        return $this;
    }

    /**
     * Get isEmailOptout
     *
     * @return boolean
     */
    public function getIsEmailOptout() {
        return $this->isEmailOptout;
    }

    /**
     * Set isloggedin
     *
     * @param boolean $isloggedin
     * @return User
     */
    public function setIsloggedin($isloggedin) {
        $this->isloggedin = $isloggedin;

        return $this;
    }

    /**
     * Get isloggedin
     *
     * @return boolean
     */
    public function getIsloggedin() {
        return $this->isloggedin;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     * @return User
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     * @return User
     */
    public function setUpdatedAt($updatedAt) {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt() {
        return $this->updatedAt;
    }

    /**
     * Set isDeleted
     *
     * @param boolean $isDeleted
     * @return User
     */
    public function setIsDeleted($isDeleted) {
        $this->isDeleted = $isDeleted;

        return $this;
    }

    /**
     * Get isDeleted
     *
     * @return boolean
     */
    public function getIsDeleted() {
        return $this->isDeleted;
    }

    /**
     * Set isDeersAuthenticated
     *
     * @param boolean $isDeersAuthenticated
     * @return User
     */
    public function setIsDeersAuthenticated($isDeersAuthenticated) {
        $this->isDeersAuthenticated = $isDeersAuthenticated;

        return $this;
    }

    /**
     * Get isDeersAuthenticated
     *
     * @return boolean
     */
    public function getIsDeersAuthenticated() {
        return $this->isDeersAuthenticated;
    }

    /**
     * Set deersAuthenticatedAt
     *
     * @param \DateTime $deersAuthenticatedAt
     * @return User
     */
    public function setDeersAuthenticatedAt($deersAuthenticatedAt) {
        $this->deersAuthenticatedAt = $deersAuthenticatedAt;

        return $this;
    }

    /**
     * Get deersAuthenticatedAt
     *
     * @return \DateTime
     */
    public function getDeersAuthenticatedAt() {
        return $this->deersAuthenticatedAt;
    }

    /**
     * Set country
     *
     * @param \Dhi\UserBundle\Entity\Country $country
     * @return User
     */
    public function setCountry(\Dhi\UserBundle\Entity\Country $country = null) {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return \Dhi\UserBundle\Entity\Country
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Add userServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $userServices
     * @return User
     */
    public function addUserService(\Dhi\UserBundle\Entity\UserService $userServices) {
        $this->userServices[] = $userServices;

        return $this;
    }

    /**
     * Remove userServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $userServices
     */
    public function removeUserService(\Dhi\UserBundle\Entity\UserService $userServices) {
        $this->userServices->removeElement($userServices);
    }

    /**
     * Get userServices
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserServices() {
        return $this->userServices;
    }

    /**
     * Set isIptvDisabled
     *
     * @param boolean $isIptvDisabled
     * @return User
     */
    public function setIsIptvDisabled($isIptvDisabled) {
        $this->isIptvDisabled = $isIptvDisabled;

        return $this;
    }

    /**
     * Get isIptvDisabled
     *
     * @return boolean
     */
    public function getIsIptvDisabled() {
        return $this->isIptvDisabled;
    }

    /**
     * Set newSelevisionUser
     *
     * @param boolean $newSelevisionUser
     * @return User
     */
    public function setNewSelevisionUser($newSelevisionUser) {
        $this->newSelevisionUser = $newSelevisionUser;

        return $this;
    }

    /**
     * Get newSelevisionUser
     *
     * @return boolean
     */
    public function getNewSelevisionUser() {
        return $this->newSelevisionUser;
    }

    /**
     * Add serviceSettings
     *
     * @param \Dhi\UserBundle\Entity\UserServiceSetting $serviceSettings
     * @return User
     */
    public function addServiceSetting(\Dhi\UserBundle\Entity\UserServiceSetting $serviceSettings) {
        $this->serviceSettings[] = $serviceSettings;

        return $this;
    }

    /**
     * Remove serviceSettings
     *
     * @param \Dhi\UserBundle\Entity\UserServiceSetting $serviceSettings
     */
    public function removeServiceSetting(\Dhi\UserBundle\Entity\UserServiceSetting $serviceSettings) {
        $this->serviceSettings->removeElement($serviceSettings);
    }

    /**
     * Get serviceSettings
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getServiceSettings() {
        return $this->serviceSettings;
    }

    public function getName() {
        $name = '';
        if ($this->firstname) {
            $name .= $this->firstname;
            if ($this->lastname) {
                $name .= ' ';
            }
        } if ($this->lastname) {
            $name .= $this->lastname;
        } if ($name == '') {
            return $this->username;
        } return $name;
    }

    /*
     * function to get user role (single)
     */

    public function getSingleRole() {
        return $this->roles[0];
    }

    /**
     * Add groups
     *
     * @param \Dhi\UserBundle\Entity\Group $groups
     * @return User
     */
    public function addGroup(\FOS\UserBundle\Model\GroupInterface $groups)
    {
        $this->groups[] = $groups;

        return $this;
    }

    /**
     * Remove groups
     *
     * @param \Dhi\UserBundle\Entity\Group $groups
     */
    public function removeGroup(\FOS\UserBundle\Model\GroupInterface $groups)
    {
        $this->groups->removeElement($groups);
    }

    /**
     * Get groups
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGroups()
    {
        return $this->groups;
    }

    public function getGroupObject() {

        // return first group object, there will be always single group.
        return $this->groups[0];
    }

    public function getGroup() {
        // return first group name, there will be always single group.
    	if(count($this->groups))
    	   return $this->groups[0]->getName();
    	else
    	    return null;
    }

    public function getGroupId() {

        // return first group name, there will be always single group.
        return $this->groups[0]->getId();
    }


    /**
     * Add userSetting
     *
     * @param \Dhi\UserBundle\Entity\UserSetting $userSetting
     * @return User
     */
    public function addUserSetting(\Dhi\UserBundle\Entity\UserSetting $userSetting)
    {
        $this->userSetting[] = $userSetting;

        return $this;
    }

    /**
     * Remove userSetting
     *
     * @param \Dhi\UserBundle\Entity\UserSetting $userSetting
     */
    public function removeUserSetting(\Dhi\UserBundle\Entity\UserSetting $userSetting)
    {
        $this->userSetting->removeElement($userSetting);
    }

    /**
     * Get userSetting
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserSetting()
    {
        return $this->userSetting;
    }

    /**
     * Set userSetting
     *
     * @param \Dhi\UserBundle\Entity\UserSetting $userSetting
     * @return User
     */
    public function setUserSetting(\Dhi\UserBundle\Entity\UserSetting $userSetting = null)
    {
        $this->userSetting[] = $userSetting;

        return $this;
    }

    /**
     * Set isLastLogin
     *
     * @param boolean $isLastLogin
     * @return User
     */
    public function setIsLastLogin($isLastLogin)
    {
        $this->isLastLogin = $isLastLogin;

        return $this;
    }

    /**
     * Get isLastLogin
     *
     * @return boolean
     */
    public function getIsLastLogin()
    {
        return $this->isLastLogin;
    }

    /**
     * Add adminServiceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $adminServiceLocation
     * @return User
     */
    public function addAdminServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $adminServiceLocation)
    {
        $this->adminServiceLocation[] = $adminServiceLocation;

        return $this;
    }

    /**
     * Remove adminServiceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $adminServiceLocation
     */
    public function removeAdminServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $adminServiceLocation)
    {
        $this->adminServiceLocation->removeElement($adminServiceLocation);
    }

    /**
     * Get adminServiceLocation
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAdminServiceLocation()
    {
        return $this->adminServiceLocation;
    }

    /**
     * Add serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     * @return User
     */
    public function addServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations[] = $serviceLocations;

        return $this;
    }

    /**
     * Remove serviceLocations
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations
     */
    public function removeServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $serviceLocations)
    {
        $this->serviceLocations->removeElement($serviceLocations);
    }

    /**
     * Get serviceLocations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getServiceLocations()
    {
        return $this->serviceLocations;
    }


    /**
     * Set ipAddressLong
     *
     * @param integer $ipAddressLong
     * @return User
     */
    public function setIpAddressLong($ipAddressLong)
    {
        $this->ipAddressLong = $ipAddressLong;

        return $this;
    }

    /**
     * Get ipAddressLong
     *
     * @return integer
     */
    public function getIpAddressLong()
    {
        return $this->ipAddressLong;
    }

    /**
     * Add userMacAddress
     *
     * @param \Dhi\UserBundle\Entity\UserMacAddress $userMacAddress
     * @return User
     */
    public function addUserMacAddress(\Dhi\UserBundle\Entity\UserMacAddress $userMacAddress)
    {
        $this->userMacAddress[] = $userMacAddress;

        return $this;
    }

    /**
     * Remove userMacAddress
     *
     * @param \Dhi\UserBundle\Entity\UserMacAddress $userMacAddress
     */
    public function removeUserMacAddress(\Dhi\UserBundle\Entity\UserMacAddress $userMacAddress)
    {
        $this->userMacAddress->removeElement($userMacAddress);
    }

    /**
     * Get userMacAddress
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserMacAddress()
    {
        return $this->userMacAddress;
    }

    /**
     * Set cid
     *
     * @param integer $cid
     * @return User
     */
    public function setCid($cid)
    {
        $this->cid = $cid;

        return $this;
    }

    /**
     * Get cid
     *
     * @return integer
     */
    public function getCid()
    {
        return $this->cid;
    }

    /**
     * Add compensations
     *
     * @param \Dhi\UserBundle\Entity\Compensation $compensations
     * @return User
     */
    public function addCompensation(\Dhi\UserBundle\Entity\Compensation $compensations)
    {
        $this->compensations[] = $compensations;

        return $this;
    }

    /**
     * Remove compensations
     *
     * @param \Dhi\UserBundle\Entity\Compensation $compensations
     */
    public function removeCompensation(\Dhi\UserBundle\Entity\Compensation $compensations)
    {
        $this->compensations->removeElement($compensations);
    }

    /**
     * Get compensations
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getCompensations()
    {
        return $this->compensations;
    }

    /**
     * Set userCredit
     *
     * @param \Dhi\UserBundle\Entity\UserCredit $userCredit
     * @return User
     */
    public function setUserCredit(\Dhi\UserBundle\Entity\UserCredit $userCredit = null)
    {
        $this->userCredit = $userCredit;

        return $this;
    }

    /**
     * Get userCredit
     *
     * @return \Dhi\UserBundle\Entity\UserCredit
     */
    public function getUserCredit()
    {
        return $this->userCredit;
    }



    /**
     * Set geoLocationCountry
     *
     * @param \Dhi\UserBundle\Entity\Country $geoLocationCountry
     * @return User
     */
    public function setGeoLocationCountry(\Dhi\UserBundle\Entity\Country $geoLocationCountry = null)
    {
        $this->geoLocationCountry = $geoLocationCountry;

        return $this;
    }

    /**
     * Get geoLocationCountry
     *
     * @return \Dhi\UserBundle\Entity\Country
     */
    public function getGeoLocationCountry()
    {
        return $this->geoLocationCountry;
    }


    /**
     * Set encryPwd
     *
     * @param string $encryPwd
     * @return User
     */
    public function setEncryPwd($encryPwd)
    {
        $this->encryPwd = $encryPwd;

        return $this;
    }

    /**
     * Get encryPwd
     *
     * @return string
     */
    public function getEncryPwd()
    {
        return $this->encryPwd;
    }

    /**
     * Set userServiceLocation
     *
     * @param \Dhi\AdminBundle\Entity\ServiceLocation $userServiceLocation
     * @return User
     */
    public function setUserServiceLocation(\Dhi\AdminBundle\Entity\ServiceLocation $userServiceLocation = null)
    {
        $this->userServiceLocation = $userServiceLocation;

        return $this;
    }

    /**
     * Get userServiceLocation
     *
     * @return \Dhi\AdminBundle\Entity\ServiceLocation
     */
    public function getUserServiceLocation()
    {
        return $this->userServiceLocation;
    }

    /**
     * Set isAradialExists
     *
     * @param boolean $isAradialExists
     * @return User
     */
    public function setIsAradialExists($isAradialExists)
    {
        $this->isAradialExists = $isAradialExists;

        return $this;
    }

    /**
     * Get isAradialExists
     *
     * @return boolean
     */
    public function getIsAradialExists()
    {
        return $this->isAradialExists;
    }

    /**
     * Set isAradialMigrated
     *
     * @param boolean $isAradialMigrated
     * @return User
     */
    public function setIsAradialMigrated($isAradialMigrated)
    {
        $this->isAradialMigrated = $isAradialMigrated;

        return $this;
    }

    /**
     * Get isAradialMigrated
     *
     * @return boolean
     */
    public function getIsAradialMigrated()
    {
        return $this->isAradialMigrated;
    }

    /**
     * Set isFlaggedForIspBalTrans
     *
     * @param boolean $isFlaggedForIspBalTrans
     * @return User
     */
    public function setIsFlaggedForIspBalTrans($isFlaggedForIspBalTrans)
    {
        $this->isFlaggedForIspBalTrans = $isFlaggedForIspBalTrans;

        return $this;
    }

    /**
     * Get isFlaggedForIspBalTrans
     *
     * @return boolean
     */
    public function getIsFlaggedForIspBalTrans()
    {
        return $this->isFlaggedForIspBalTrans;
    }

    /**
     * Set isEmployee
     *
     * @param boolean $isEmployee
     * @return User
     */
    public function setIsEmployee($isEmployee)
    {
        $this->isEmployee = $isEmployee;

        return $this;
    }

    /**
     * Get isEmployee
     *
     * @return boolean 
     */
    public function getIsEmployee()
    {
        return $this->isEmployee;
    }

    /**
     * Add userPurchased
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $userPurchased
     * @return User
     */
    public function addUserPurchased(\Dhi\ServiceBundle\Entity\PurchaseOrder $userPurchased)
    {
        $this->userPurchased[] = $userPurchased;

        return $this;
    }

    /**
     * Remove userPurchased
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $userPurchased
     */
    public function removeUserPurchased(\Dhi\ServiceBundle\Entity\PurchaseOrder $userPurchased)
    {
        $this->userPurchased->removeElement($userPurchased);
    }

    /**
     * Get userPurchased
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserPurchased()
    {
        return $this->userPurchased;
    }

    /**
     * Add expiredUserServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $expiredUserServices
     * @return User
     */
    public function addExpiredUserService(\Dhi\UserBundle\Entity\UserService $expiredUserServices)
    {
        $this->expiredUserServices[] = $expiredUserServices;

        return $this;
    }

    /**
     * Remove expiredUserServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $expiredUserServices
     */
    public function removeExpiredUserService(\Dhi\UserBundle\Entity\UserService $expiredUserServices)
    {
        $this->expiredUserServices->removeElement($expiredUserServices);
    }

    /**
     * Get expiredUserServices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getExpiredUserServices()
    {
        return $this->expiredUserServices;
    }


    /**
     * Add refundedByUserServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $refundedByUserServices
     * @return User
     */
    public function addRefundedByUserService(\Dhi\UserBundle\Entity\UserService $refundedByUserServices)
    {
        $this->refundedByUserServices[] = $refundedByUserServices;

        return $this;
    }

    /**
     * Remove refundedByUserServices
     *
     * @param \Dhi\UserBundle\Entity\UserService $refundedByUserServices
     */
    public function removeRefundedByUserService(\Dhi\UserBundle\Entity\UserService $refundedByUserServices)
    {
        $this->refundedByUserServices->removeElement($refundedByUserServices);
    }

    /**
     * Get refundedByUserServices
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRefundedByUserServices()
    {
        return $this->refundedByUserServices;
    }

    /**
     * Add milstar
     *
     * @param \Dhi\ServiceBundle\Entity\Milstar $milstar
     * @return User
     */
    public function addMilstar(\Dhi\ServiceBundle\Entity\Milstar $milstar)
    {
        $this->milstar[] = $milstar;

        return $this;
    }

    /**
     * Remove milstar
     *
     * @param \Dhi\ServiceBundle\Entity\Milstar $milstar
     */
    public function removeMilstar(\Dhi\ServiceBundle\Entity\Milstar $milstar)
    {
        $this->milstar->removeElement($milstar);
    }

    /**
     * Get milstar
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getMilstar()
    {
        return $this->milstar;
    }

    /**
     * Add paypalCheckouts
     *
     * @param \Dhi\ServiceBundle\Entity\PaypalCheckout $paypalCheckouts
     * @return User
     */
    public function addPaypalCheckout(\Dhi\ServiceBundle\Entity\PaypalCheckout $paypalCheckouts)
    {
        $this->paypalCheckouts[] = $paypalCheckouts;

        return $this;
    }

    /**
     * Remove paypalCheckouts
     *
     * @param \Dhi\ServiceBundle\Entity\PaypalCheckout $paypalCheckouts
     */
    public function removePaypalCheckout(\Dhi\ServiceBundle\Entity\PaypalCheckout $paypalCheckouts)
    {
        $this->paypalCheckouts->removeElement($paypalCheckouts);
    }

    /**
     * Get paypalCheckouts
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPaypalCheckouts()
    {
        return $this->paypalCheckouts;
    }

    /**
     * Add userCreditLogs
     *
     * @param \Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs
     * @return User
     */
    public function addUserCreditLog(\Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs)
    {
        $this->userCreditLogs[] = $userCreditLogs;

        return $this;
    }

    /**
     * Remove userCreditLogs
     *
     * @param \Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs
     */
    public function removeUserCreditLog(\Dhi\UserBundle\Entity\UserCreditLog $userCreditLogs)
    {
        $this->userCreditLogs->removeElement($userCreditLogs);
    }

    /**
     * Get userCreditLogs
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserCreditLogs()
    {
        return $this->userCreditLogs;
    }

    /**
     * Add purchaseOrders
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders
     * @return User
     */
    public function addPurchaseOrder(\Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders)
    {
        $this->purchaseOrders[] = $purchaseOrders;

        return $this;
    }

    /**
     * Remove purchaseOrders
     *
     * @param \Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders
     */
    public function removePurchaseOrder(\Dhi\ServiceBundle\Entity\PurchaseOrder $purchaseOrders)
    {
        $this->purchaseOrders->removeElement($purchaseOrders);
    }

    /**
     * Get purchaseOrders
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getPurchaseOrders()
    {
        return $this->purchaseOrders;
    }

    /**
     * Add servicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases
     * @return User
     */
    public function addServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases[] = $servicePurchases;

        return $this;
    }

    /**
     * Remove servicePurchases
     *
     * @param \Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases
     */
    public function removeServicePurchase(\Dhi\ServiceBundle\Entity\ServicePurchase $servicePurchases)
    {
        $this->servicePurchases->removeElement($servicePurchases);
    }

    /**
     * Get servicePurchases
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getServicePurchases()
    {
        return $this->servicePurchases;
    }

    /**
     * Add employeePromoCodeCustomer
     *
     * @param \Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $employeePromoCodeCustomer
     * @return User
     */
    public function addEmployeePromoCodeCustomer(\Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $employeePromoCodeCustomer)
    {
        $this->employeePromoCodeCustomer[] = $employeePromoCodeCustomer;

        return $this;
    }

    /**
     * Remove employeePromoCodeCustomer
     *
     * @param \Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $employeePromoCodeCustomer
     */
    public function removeEmployeePromoCodeCustomer(\Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer $employeePromoCodeCustomer)
    {
        $this->employeePromoCodeCustomer->removeElement($employeePromoCodeCustomer);
    }

    /**
     * Get employeePromoCodeCustomer
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getEmployeePromoCodeCustomer()
    {
        return $this->employeePromoCodeCustomer;
    }

    /**
     * Set customerRefNum
     *
     * @param integer $customerRefNum
     * @return User
     */
    public function setCustomerRefNum($customerRefNum)
    {
        $this->customerRefNum = $customerRefNum;

        return $this;
    }

    /**
     * Get customerRefNum
     *
     * @return integer 
     */
    public function getCustomerRefNum()
    {
        return $this->customerRefNum;
    }

    /**
     * Add chasePayment
     *
     * @param \Dhi\ServiceBundle\Entity\ChaseCheckout $chasePayment
     * @return User
     */
    public function addChasePayment(\Dhi\ServiceBundle\Entity\ChaseCheckout $chasePayment)
    {
        $this->chasePayment[] = $chasePayment;

        return $this;
    }

    /**
     * Remove chasePayment
     *
     * @param \Dhi\ServiceBundle\Entity\ChaseCheckout $chasePayment
     */
    public function removeChasePayment(\Dhi\ServiceBundle\Entity\ChaseCheckout $chasePayment)
    {
        $this->chasePayment->removeElement($chasePayment);
    }

    /**
     * Get chasePayment
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChasePayment()
    {
        return $this->chasePayment;
    }

    /**
     * Set isSuspended
     *
     * @param boolean $isSuspended
     * @return User
     */
    public function setIsSuspended($isSuspended)
    {
        $this->isSuspended = $isSuspended;

        return $this;
    }

    /**
     * Get isSuspended
     *
     * @return boolean 
     */
    public function getIsSuspended()
    {
        return $this->isSuspended;
    }

    /**
     * Add refereeUser
     *
     * @param \Dhi\UserBundle\Entity\ReferralPromoCode $refereeUser
     * @return User
     */
    public function addRefereeUser(\Dhi\UserBundle\Entity\ReferralPromoCode $refereeUser)
    {
        $this->refereeUser[] = $refereeUser;

        return $this;
    }

    /**
     * Remove refereeUser
     *
     * @param \Dhi\UserBundle\Entity\ReferralPromoCode $refereeUser
     */
    public function removeRefereeUser(\Dhi\UserBundle\Entity\ReferralPromoCode $refereeUser)
    {
        $this->refereeUser->removeElement($refereeUser);
    }

    /**
     * Get refereeUser
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getRefereeUser()
    {
        return $this->refereeUser;
    }

    /**
     * Add referralInvitees
     *
     * @param \Dhi\UserBundle\Entity\ReferralPromoCode $referralInvitees
     * @return User
     */
    public function addReferralInvitee(\Dhi\UserBundle\Entity\ReferralPromoCode $referralInvitees)
    {
        $this->referralInvitees[] = $referralInvitees;

        return $this;
    }

    /**
     * Remove referralInvitees
     *
     * @param \Dhi\UserBundle\Entity\ReferralPromoCode $referralInvitees
     */
    public function removeReferralInvitee(\Dhi\UserBundle\Entity\ReferralPromoCode $referralInvitees)
    {
        $this->referralInvitees->removeElement($referralInvitees);
    }

    /**
     * Get referralInvitees
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getReferralInvitees()
    {
        return $this->referralInvitees;
    }

    /**
     * Add referrerUser
     *
     * @param \Dhi\UserBundle\Entity\ReferralPromoCode $referrerUser
     * @return User
     */
    public function addReferrerUser(\Dhi\UserBundle\Entity\ReferralPromoCode $referrerUser)
    {
        $this->referrerUser[] = $referrerUser;

        return $this;
    }

    /**
     * Remove referrerUser
     *
     * @param \Dhi\UserBundle\Entity\ReferralPromoCode $referrerUser
     */
    public function removeReferrerUser(\Dhi\UserBundle\Entity\ReferralPromoCode $referrerUser)
    {
        $this->referrerUser->removeElement($referrerUser);
    }

    /**
     * Get referrerUser
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getReferrerUser()
    {
        return $this->referrerUser;
    }

    /**
     * Add inAppPromoCodeCustomer
     *
     * @param \Dhi\UserBundle\Entity\InAppPromoCode $inAppPromoCodeCustomer
     * @return User
     */
    public function addInAppPromoCodeCustomer(\Dhi\UserBundle\Entity\InAppPromoCode $inAppPromoCodeCustomer)
    {
        $this->inAppPromoCodeCustomer[] = $inAppPromoCodeCustomer;

        return $this;
    }

    /**
     * Remove inAppPromoCodeCustomer
     *
     * @param \Dhi\UserBundle\Entity\InAppPromoCode $inAppPromoCodeCustomer
     */
    public function removeInAppPromoCodeCustomer(\Dhi\UserBundle\Entity\InAppPromoCode $inAppPromoCodeCustomer)
    {
        $this->inAppPromoCodeCustomer->removeElement($inAppPromoCodeCustomer);
    }

    /**
     * Get inAppPromoCodeCustomer
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getInAppPromoCodeCustomer()
    {
        return $this->inAppPromoCodeCustomer;
    }

    /**
     * Add userChaseInfo
     *
     * @param \Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo
     * @return User
     */
    public function addUserChaseInfo(\Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo)
    {
        $this->userChaseInfo[] = $userChaseInfo;

        return $this;
    }

    /**
     * Remove userChaseInfo
     *
     * @param \Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo
     */
    public function removeUserChaseInfo(\Dhi\UserBundle\Entity\UserChaseInfo $userChaseInfo)
    {
        $this->userChaseInfo->removeElement($userChaseInfo);
    }

    /**
     * Get userChaseInfo
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserChaseInfo()
    {
        return $this->userChaseInfo;
    }

    /**
     * Add TikilivePromoCodeCustomer
     *
     * @param \Dhi\AdminBundle\Entity\TikilivePromoCode $tikilivePromoCodeCustomer
     * @return User
     */
    public function addTikilivePromoCodeCustomer(\Dhi\AdminBundle\Entity\TikilivePromoCode $tikilivePromoCodeCustomer)
    {
        $this->TikilivePromoCodeCustomer[] = $tikilivePromoCodeCustomer;

        return $this;
    }

    /**
     * Remove TikilivePromoCodeCustomer
     *
     * @param \Dhi\AdminBundle\Entity\TikilivePromoCode $tikilivePromoCodeCustomer
     */
    public function removeTikilivePromoCodeCustomer(\Dhi\AdminBundle\Entity\TikilivePromoCode $tikilivePromoCodeCustomer)
    {
        $this->TikilivePromoCodeCustomer->removeElement($tikilivePromoCodeCustomer);
    }

    /**
     * Get TikilivePromoCodeCustomer
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getTikilivePromoCodeCustomer()
    {
        return $this->TikilivePromoCodeCustomer;
    }

    /**
     * Set whiteLabel
     *
     * @param \Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel
     * @return User
     */
    public function setWhiteLabel(\Dhi\AdminBundle\Entity\WhiteLabel $whiteLabel = null)
    {
        $this->whiteLabel = $whiteLabel;

        return $this;
    }

    /**
     * Get whiteLabel
     *
     * @return \Dhi\AdminBundle\Entity\WhiteLabel 
     */
    public function getWhiteLabel()
    {
        return $this->whiteLabel;
    }

    /**
     * Set isAbandoned
     *
     * @param boolean $isAbandoned
     * @return User
     */
    public function setIsAbandoned($isAbandoned)
    {
        $this->isAbandoned = $isAbandoned;

        return $this;
    }

    /**
     * Get isAbandoned
     *
     * @return boolean 
     */
    public function getIsAbandoned()
    {
        return $this->isAbandoned;
    }

    /**
     * Add userSuspendHistory
     *
     * @param \Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory
     * @return User
     */
    public function addUserSuspendHistory(\Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory)
    {
        $this->userSuspendHistory[] = $userSuspendHistory;

        return $this;
    }

    /**
     * Remove userSuspendHistory
     *
     * @param \Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory
     */
    public function removeUserSuspendHistory(\Dhi\AdminBundle\Entity\UserSuspendHistory $userSuspendHistory)
    {
        $this->userSuspendHistory->removeElement($userSuspendHistory);
    }

    /**
     * Get userSuspendHistory
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUserSuspendHistory()
    {
        return $this->userSuspendHistory;
    }
}
