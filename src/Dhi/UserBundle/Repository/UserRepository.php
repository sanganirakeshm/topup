<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Serializer\Exception\UnsupportedException;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository implements UserProviderInterface {


     public function getAdminGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper,$admin) {

       $data = $this->trim_serach_data($searchData, $SearchType);

       $query = $this->createQueryBuilder('u')
               ->select('u.id, u.username, grp.name as groupName, u.email, u.enabled, u.lastLogin, u.isloggedin')
               ->leftJoin('u.groups', 'grp')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_ADMIN%');

        if($admin->getGroup() != 'Super Admin') {
            $query->andWhere('u.id = :id')
            ->setParameter('id', $admin->getId());
        }

        if ($SearchType == 'ORLIKE') {

            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {

            $likeStr = $objHelper->andLikeSearch($data);
        }

        if ($likeStr) {

            $query->andWhere($likeStr);
        }

        $query->orderBy($orderBy, $sortOrder);

        $countQuery = clone $query;
        $countQuery->select("count(u.id) as totalRecords");
        $objCountData = $countQuery->getQuery()->getOneOrNullResult();
        $countData = $objCountData['totalRecords'];
        
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        $result = $query->getQuery()->getArrayResult();

        $dataResult = array();

        if ($countData > 0) {

            $dataResult['result'] = $result;
            $dataResult['totalRecord'] = $countData;

            return $dataResult;
        }
        return false;
    }

     public function trim_serach_data($searchData, $SearchType) {

        $QueryStr = array();

        if (!empty($searchData)) {

            if ($SearchType == 'ANDLIKE') {

                $i = 0;
                foreach ($searchData as $key => $val) {

                     if ($key == 'Username' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'u.username';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    if ($key == 'Email' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'u.email';
                        $QueryStr[$i]['Value'] = $val ;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    $i++;
                }
            } else {

            }
        }
        return $QueryStr;
    }

    public function getPackageServiceList($serviceLocation, $packageType = array()) {
        $query = $this->createQueryBuilder('u')
                ->select('s.name as sName','u.id','us.packageName', 'p.packageType', 'sp.bundle_id', 'p.description', 'us.packageId', 's.name', 'count(u.id)')
                ->innerJoin('u.userServiceLocation', 'sl')
                ->innerJoin('u.userServices', 'us')
                ->innerJoin('us.servicePurchase', 'sp')
                ->innerJoin('us.service', 's')
                ->leftJoin('DhiAdminBundle:Package', 'p', 'with', 'us.packageId = p.packageId')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%')
                ->andWhere('us.status = :status')->setParameter('status', 1)
                ->andWhere('us.isAddon = :addon')->setParameter('addon', 0)
                ->groupBy('us.packageId')
                ->andWhere('sl.name IN(:serviceLocation)')
                ->setParameter('serviceLocation', $serviceLocation)
                ->andWhere('u.isSuspended = :suspended')
                ->setParameter('suspended', 0);

        $query->andWhere("us.expiryDate > :ex")->setParameter("ex", new \DateTime());
        $query->andWhere("sp.purchase_type IS NULL");

        $bQuery = $this->createQueryBuilder('u')
                ->select('sp.purchase_type as sName','sp.id','b.displayBundleName as packageName', 'b.description', 'count(u.id)')
                ->innerJoin('u.userServiceLocation', 'sl')
                ->innerJoin('u.userServices', 'us')
                ->innerJoin('us.servicePurchase', 'sp')
                ->innerJoin('us.service', 's')
                ->leftJoin('DhiAdminBundle:Bundle', 'b', 'with', 'sp.bundle_id = b.bundle_id')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%')
                ->andWhere('us.status = :status')->setParameter('status', 1)
                ->andWhere('us.isAddon = :addon')->setParameter('addon', 0)
                ->groupBy('sp.bundle_id')
                ->andWhere('sl.name IN(:serviceLocation)')
                ->setParameter('serviceLocation', $serviceLocation);

        $bQuery->andWhere("us.expiryDate > :ex")->setParameter("ex", new \DateTime());
        $bQuery->andWhere("sp.purchase_type =  :bundle")->setParameter("bundle", "BUNDLE");

        $bQuery->andWhere('s.name IN (:bsIname)')->setParameter('bsIname', "ISP");
        if (!empty($packageType)) {
            if (!in_array('ISP', $packageType) && !in_array('IPTV', $packageType)) {
                $bQuery->andWhere('s.name IN (:bsName)')->setParameter('bsName', "BUNDLE");
            }
            $query->andWhere('p.packageType IN (:packageType)')->setParameter('packageType', $packageType);
            $query->andWhere('s.name IN (:sName)')->setParameter('sName', $packageType);
        }

        $bQesult     = $bQuery->getQuery()->getArrayResult();
        $result     = $query->getQuery()->getArrayResult();
        $mainResult = array_merge($result,$bQesult);
        $countData  = count($mainResult);
        $dataResult = array();
        if ($countData > 0) {
            $dataResult['result'] = $mainResult;
            $dataResult['totalRecord'] = $countData;
            return $dataResult;
        }
        return false;
    }

    public function getUserGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper,$admin,$country='',$firstname,$lastname ,$isEmployee = 0) {

        $data = $this->user_serach_data($searchData, $SearchType);
        
        $query = $this->createQueryBuilder('u')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%');

        /*if(@$searchData['Username']!='' || @$searchData['Email']!=''){
           $country = '';
        }*/
        
        if($firstname!='') {
            $query->andWhere("u.firstname LIKE '%$firstname%'");
        }
                
        if($lastname!='') {
            $query->andWhere("u.lastname LIKE '%$lastname%'");
        }

        if($country!=''){
             $query->andWhere('u.userServiceLocation IN (:country)');
             $query->setParameter('country', $country);
        }
        
        if(!empty($ipAddressZones)) {
           foreach($ipAddressZones as $key => $value) {
                $query->orWhere('u.ipAddressLong >= :fromIp')
                      ->setParameter('fromIp', $value['fromIP']);
                $query->andWhere('u.ipAddressLong <= :toIp')
                      ->setParameter('toIp', $value['toIP']);
           }
        }

        if (!array_key_exists("isSuspended",$searchData)){
               $query->andWhere('u.isSuspended = :suspended')
                    ->setParameter('suspended', 0);
        }

        $countQuery = clone $query;

        if(@$searchData['ActiveServices']!=''){
            $countQuery->innerJoin('u.userServices', 'us')
                ->innerJoin('us.service', 's')
                ->innerJoin('DhiAdminBundle:Package', 'p', 'with', 'us.packageId = p.packageId')
                ->innerJoin('us.servicePurchase', 'sp')
                ->andWhere('us.status = :status')
                ->setParameter('status', 1)
                ->andWhere('us.expiryDate > :currentDate')
                ->setParameter('currentDate', new \DateTime());

            $query->innerJoin('u.userServices', 'us')
                ->innerJoin('us.service', 's')
                ->innerJoin('DhiAdminBundle:Package', 'p', 'with', 'us.packageId = p.packageId');

            $query->andWhere('us.status = :status')
                ->setParameter('status', 1)
                ->andWhere('us.expiryDate > :currentDate')
                ->setParameter('currentDate', new \DateTime())
                ->groupBy('u.id');
            
            $query->innerJoin('us.servicePurchase', 'sp');
            if(in_array($searchData['ActiveServices'], array('IPTV', 'ISP'))){
                
                $query->andWhere('sp.purchase_type IS NULL')
                        ->andWhere('s.name IN (:serviceName)')
                        ->setParameter('serviceName', $searchData['ActiveServices']);;
                        
                $countQuery->andWhere('sp.purchase_type IS NULL')
                        ->andWhere('s.name IN (:serviceName)')
                        ->setParameter('serviceName', $searchData['ActiveServices']);
                
            }else if($searchData['ActiveServices'] == 'IPTV and ISP'){
                $query->andWhere('sp.purchase_type = :purchaseType')
                        ->setParameter('purchaseType', 'BUNDLE');
                
                $countQuery->andWhere('sp.purchase_type = :purchaseType')
                        ->setParameter('purchaseType', 'BUNDLE');
            }
       }


        if(@$searchData['ServiceLocation']!='')
        {
            if(@$searchData['ActiveServices']==''){
                
                $countQuery->innerJoin('u.userServices', 'us')
                        ->innerJoin('us.servicePurchase', 'sp');
                $query->innerJoin('u.userServices', 'us')
                      ->innerJoin('us.servicePurchase', 'sp');
                
            }
            
                $countQuery->innerJoin('sp.service_location_id', 'sl')
                ->andWhere('sl.name IN (:location)')
                ->setParameter('location', $searchData['ServiceLocation']);

                $query->innerJoin('sp.service_location_id', 'sl')
                ->andWhere('sl.name IN (:location)')
                ->setParameter('location', $searchData['ServiceLocation'])
                ->groupBy('u.id');
        }


        $query->andWhere('u.isEmployee = :isEmployee')->setParameter('isEmployee', $isEmployee);
        $countQuery->andWhere('u.isEmployee = :isEmployee')->setParameter('isEmployee', $isEmployee);

        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {

            $likeStr = $objHelper->andLikeSearch($data);
        }

        if ($likeStr) {
            $countQuery->andWhere($likeStr);
            $query->andWhere($likeStr);
        }

        $query->orderBy($orderBy, $sortOrder);

        $countQuery->select("count(distinct u.id) as totalUsers");
        $objCountData = $countQuery->getQuery()->getOneOrNullResult();
        $countData = $objCountData['totalUsers'];
        
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);

        $result = $query->getQuery()->getResult();

        $dataResult = array();

        if ($countData > 0) {

            $dataResult['result'] = $result;
            $dataResult['totalRecord'] = $countData;

            return $dataResult;
        }
        return false;
    }

     public function user_serach_data($searchData, $SearchType) {

        $QueryStr = array();

        if (!empty($searchData)) {

            if ($SearchType == 'ANDLIKE') {

                $i = 0;
                foreach ($searchData as $key => $val) {

                     if ($key == 'Username' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'u.username';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    if ($key == 'Email' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'u.email';
                        $QueryStr[$i]['Value'] = $val ;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    if ($key == 'isSuspended' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'u.isSuspended';
                        $QueryStr[$i]['Value'] = $val ;
                        $QueryStr[$i]['Operator'] = '=';
                    }
                    $i++;
                }
            } else {

            }
        }
        return $QueryStr;
    }

    public function getUserByUsernameOrEmail($username, $email) {

        $query = $this->createQueryBuilder('u')
                ->select('u.email')
                ->where('u.username = :username')
                ->setParameter('username', $username)
                ->orWhere('u.email = :email')
                ->setParameter('email', $email);

        return $result = $query->getQuery()->getOneOrNullResult();
    }


    public function getAllCustomer() {

        $query = $this->createQueryBuilder('u')
        ->where('u.locked = :locked')
        ->setParameter('locked', 0)
        ->andWhere('u.isDeleted = :deleted')
        ->setParameter('deleted', 0)
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_USER%');

        return $query;
    }

    public function getAllEmployee() {

        $query = $this->createQueryBuilder('u')
        ->where('u.locked = :locked')
        ->setParameter('locked', 0)
        ->andWhere('u.isDeleted = :deleted')
        ->setParameter('deleted', 0)
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_USER%')
        ->andWhere('u.isEmployee LIKE :employee')
        ->setParameter('employee', 1)
        ->orderBy("u.username", "ASC");

        $result = $query->getQuery()->getArrayResult();
		$package = array();
		foreach($result as $key => $value){
			$package[$value['username']] = $value['firstname'].' '.$value['lastname'];
		}
		return $package;
    }
    public function getAllEmployeeUsername() {

        $query = $this->createQueryBuilder('u')
        ->where('u.locked = :locked')
        ->setParameter('locked', 0)
        ->andWhere('u.isDeleted = :deleted')
        ->setParameter('deleted', 0)
        ->andWhere('u.roles LIKE :role')
        ->setParameter('role', '%ROLE_USER%')
        ->andWhere('u.isEmployee LIKE :employee')
        ->setParameter('employee', 1)
        ->orderBy("u.username", "ASC");

        $result = $query->getQuery()->getArrayResult();
		$package = array();
		foreach($result as $key => $value){
			$package[] = $value['username'];
		}
		return $package;
    }

    public function getAllAdmin() {
        $query = $this->createQueryBuilder('u')
        ->where('u.roles NOT LIKE :role')
        ->setParameter('role', '%ROLE_USER%')
        ->orderBy('u.username','ASC');

        return $query->getQuery()->getArrayResult();
    }

    public function getAllActiveAdmin() {
        $query = $this->createQueryBuilder('u')
        ->where('u.roles NOT LIKE :role')
        ->setParameter('role', '%ROLE_USER%')
        ->orderBy('u.firstname','ASC')
        ->andWhere('u.isDeleted = :isDeleted')
        ->setParameter('isDeleted', 0);

        return $query->getQuery()->getResult();
    }

    public function getAllCustomerIpAddressZoneWise($fromIpAddress, $toIpAddress) {

        $query = $this->createQueryBuilder('u')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%')
                ->andWhere('u.ipAddressLong >= :fromIp')
                ->setParameter('fromIp', $fromIpAddress)
                ->andWhere('u.ipAddressLong <= :toIp')
                ->setParameter('toIp', $toIpAddress);


        return $query->getQuery()->getResult();
    }

    public function getSearchUser($tag,$serviceIds){

        $query = $this->createQueryBuilder('u')
                        ->leftjoin('u.userServices', 'us')
                        ->leftJoin('us.service', 'sr')
                        ->where('u.locked = :locked')
                        ->setParameter('locked', 0)
                        ->andWhere('u.isDeleted = :deleted')
                        ->setParameter('deleted', 0)
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_USER%')
                        ->andWhere('sr.id IN(:id)')
                        ->setParameter('id', $serviceIds)
                        ->andWhere('us.status =:status')
                        ->setParameter('status', 1);

        $query->andWhere('u.username LIKE :username OR u.email LIKE :email OR u.firstname LIKE :firstname OR u.lastname LIKE :lastname')
              ->setParameter('username', '%' . $tag . '%')
              ->setParameter('email', '%' . $tag . '%')
              ->setParameter('firstname', '%' . $tag . '%')
              ->setParameter('lastname', '%' . $tag . '%');

        return $query->getQuery()->getResult();
    }

    public function getUserByActiveService($serviceIds,$selectedUserIds){

        $query = $this->createQueryBuilder('u')
                        ->leftjoin('u.userServices', 'us')
                        ->leftJoin('us.service', 'sr')
                        ->where('u.locked = :locked')
                        ->setParameter('locked', 0)
                        ->andWhere('u.isDeleted = :deleted')
                        ->setParameter('deleted', 0)
                        ->andWhere('u.roles LIKE :role')
                        ->setParameter('role', '%ROLE_USER%')
                        ->andWhere('u.id IN(:uid)')
                        ->setParameter('uid', $selectedUserIds)
                        ->andWhere('sr.id IN(:id)')
                        ->setParameter('id', $serviceIds)
                        ->andWhere('us.status =:status')
                        ->setParameter('status', 1);

        return $query->getQuery()->getResult();
    }


	public function getByCustomerName($searchedData) {
		$searchedData1 = "$searchedData";

        $query = $this->createQueryBuilder('u')
				//->select('u.username')
				->where('u.locked = :locked')
				->setParameter('locked', 0)
				->andWhere('u.isDeleted = :deleted')
				->setParameter('deleted', 0)
				->andWhere('u.roles LIKE :role')
				->setParameter('role', '%ROLE_USER%');

		 $query->andWhere('u.username LIKE :username OR u.email LIKE :email');
		 $query->setParameter('username', '%'.$searchedData1.'%');
		 $query->setParameter('email', '%'.$searchedData1.'%');
				//->setParameter('firstname', '%'.$searchedData.'%');
			//	->setParameter('firstname', '%' . $searchedData . '%')
			//	->setParameter('lastname', '%' . $searchedData . '%');

        return $query->getQuery()->getArrayResult();
    }

	public function getEmailForAradialUser($username) {

		$query = $this->createQueryBuilder('u')
				->Where('u.username = :username')
				->setParameter('username', $username);


		return $query->getQuery()->getArrayResult();
	}

    public function getFlaggedForIspBalanceTransferUsers() {

        $query = $this->createQueryBuilder('u')
            ->where('u.locked = :locked')
            ->setParameter('locked', 0)
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('deleted', 0)
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->andWhere('u.isFlaggedForIspBalTrans =:flag')
            ->setParameter('flag', true);

        return $query->getQuery()->getResult();
    }

    public function getLocationWiseCustomers($serviceLocations) {
        $query = $this->createQueryBuilder('u', 'sl')
            ->innerJoin('u.userServiceLocation', 'sl')
            ->where('u.locked = :locked')
            ->setParameter('locked', 0)
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('deleted', 0)
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%')
            ->andWhere('sl.name IN (:locations)')
            ->setParameter('locations', $serviceLocations)
            ->orderBy('u.id', 'asc');

        $result = $query->getQuery()->getResult();
        if($result){
            return $result;
        }else{
            return null;
        }
    }

    public function getActiveUsers($serviceLocation){
        $query = $this->createQueryBuilder('u')
            ->select("COUNT(u.id) as total")
            ->where('u.userServiceLocation = :userServiceLocation')
            ->setParameter('userServiceLocation', $serviceLocation)
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('deleted', 0);
        $result = $query->getQuery()->getSingleScalarResult();
        return $result;
    }
    
    public function getsuperadminuser() {
        $query = $this->createQueryBuilder('u')
                ->select('u.username')
            ->where('u.locked = :locked')
            ->setParameter('locked', 0)
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('deleted', 0)
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->orderBy('u.id', 'asc');

        $query->setMaxResults(1);
        $query->setFirstResult(0);
        $result = $query->getQuery()->getArrayResult();
        if($result){
            return $result;
        }else{
            return null;
        }
    }
    
    
    public function loadUserByUsername($username)
    {
      
       $user = $this->createQueryBuilder('u')
            ->where('u.username = :username OR u.email = :email')
            ->setParameter('username', $username)
            ->setParameter('email', $username)
            ->andWhere('u.enabled = 1')
            ->getQuery();
        
        $userResult = $user->getResult();
       
        
        if(count($userResult) > 1){
            throw new UsernameNotFoundException('Invalid username/email address or password.');
        }else{
            $user = $user->getOneOrNullResult();
            //echo "<pre>"; print_r($user->getRoles()); exit;
        }
        if (!$user) {
            throw new UsernameNotFoundException(sprintf('No user with name "%s" was found.', $username));
        }

        return $user;
    }
    
    public function refreshUser(UserInterface $user) {
        $class = get_class($user);
        if (!$this->supportsClass($class)) {
            throw new UnsupportedUserException(
            sprintf(
                    'Instances of "%s" are not supported.', $class
            )
            );
        }

        return $this->find($user->getId());
    }

    public function supportsClass($class) {
        return $this->getEntityName() === $class || is_subclass_of($class, $this->getEntityName());
    }

    public function checkUserNameEmailExists($username = '', $id = '', $email = '') {

        $qb = $this->createQueryBuilder('u');

        if (!empty($username)) {
            $qb->where('u.username = :username')
                    ->setParameter('username', $username);
        }

        if (!empty($email)) {
            $qb->andWhere('u.email = :email')
                    ->setParameter('email', $email);
        }

        if (!empty($id)) {
            $qb->andWhere('u.id != :id')
                    ->setParameter('id', $id);
        }

        $userName_result = $qb->getQuery()->getResult();

        return $userName_result;
    }
    
    public function checkEmail($email, $userId = '') {
        
        $query = $this->createQueryBuilder('u')
                ->select('u.email')
                //->where('u.email = :email')
                ->where('u.username = :email OR u.email = :email')
                ->setParameter('email', $email);
        
        if ($userId != '') {
            
            $query->andWhere('u.id != :userId')
                  ->setParameter('userId', $userId);
        }

        return $query->getQuery()->getOneOrNullResult();
    }
    
   public function checkUsernameEmail($username, $userId = '') {
        
        $query = $this->createQueryBuilder('u')
                ->select('u.email')
                ->where('u.email = :username')
                //->where('u.username = :email OR u.email = :email')
                ->setParameter('username', $username);
        
        if ($userId != '') {
            
            $query->andWhere('u.id != :userId')
                  ->setParameter('userId', $userId);
        }

        return $query->getQuery()->getOneOrNullResult();
    }
    
     public function  getAbandonedPurchase($abandedthresholdval){
         
         $query = $this->createQueryBuilder('u')
                    ->select('u.id','u.username','u.usernameCanonical','u.email','u.emailCanonical','u.lastLogin','MAX(po.createdAt) as lastpurchasedate','DATE_DIFF(MAX(po.createdAt),u.lastLogin) as datediffof')
                    ->leftjoin('u.purchaseOrders','po')
                    ->where('u.enabled = 1')
                    ->andWhere('u.isDeleted = :deleted')
                    ->setParameter('deleted', 0)
                    ->andWhere('u.roles LIKE :role')
                    ->setParameter('role', '%ROLE_USER%') 
                    ->andWhere('po.compensationValidity is NULL')
                    ->andWhere('u.lastLogin is NOT NULL');
                
                  $query->groupby('po.user');
                  $query->having('(datediffof > 0 AND datediffof >= :thresholval) OR (datediffof < 0 AND datediffof <= -:thresholval)')
                        ->setParameter('thresholval', $abandedthresholdval);
                  
             $result =  $query->getQuery()->getArrayResult();
             return $result;
     }
     
     public function getObjectUserByUsernameOrEmail($usernameOrEmail = ''){
         
        $query = $this->createQueryBuilder('u')
            ->where('u.locked = :locked')
            ->setParameter('locked', 0)
            ->andWhere('u.username = :usernameOrEmail OR u.email = :usernameOrEmail')
            ->setParameter('usernameOrEmail', $usernameOrEmail)
            ->andWhere('u.locked = :locked')
            ->setParameter('locked', 0)
            ->andWhere('u.isDeleted = :deleted')
            ->setParameter('deleted', 0)
            ->andWhere('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_USER%');
        $result = $query->getQuery()->getOneOrNullResult();
        return $result;
     }
}