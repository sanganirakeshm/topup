<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;
use DateTime;

/**
 * UserLoginLogRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserLoginLogRepository extends EntityRepository {
    /**
     * Fetch all records from userLoginLog table 
     * @param type $user
     * @return type
     */
    public function getAllUserLoginLogQuery($searchParam, $slot = array(), $adminServiceLocationPermission = '') {
        
        
        $query = $this->createQueryBuilder('l')
                ->select('l.id as loginLogId, u.id as userId, u.firstname, u.lastname, l.ipAddress, sl.name as userServiceLocation, l.createdAt, c.name as countryName')
                ->innerJoin('l.user', 'u')
                ->leftJoin('u.userServiceLocation', 'sl')
                ->leftJoin('l.country', 'c')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%');
        
        if(!empty($searchParam)) {
            
            
            /*if(isset($searchParam['Name']) && $searchParam['Name'] != "")  {
                 
                $query->andWhere('u.username LIKE :username')
                      ->setParameter('username', '%' . $searchParam['Name'] . '%');
                      
            }*/
            
            if(!empty($searchParam['Name']) && isset($searchParam['Name'])){
                $query->andWhere("CONCAT(u.firstname,' ', u.lastname) LIKE :fullName")
                    ->setParameter('fullName', '%'.$searchParam['Name'].'%');
            }
            
            if(isset($searchParam['IpAddress']) && $searchParam['IpAddress'] != "")  {
                    
                $query->andWhere('l.ipAddress LIKE :ip')
                      ->setParameter('ip', '%' . $searchParam['IpAddress'] . '%');
            }
            
            if(isset($searchParam['Country']) && $searchParam['Country'] != "")  {
                    
                $query->andWhere('c.name LIKE :country')
                      ->setParameter('country', '%' . $searchParam['Country'] . '%');
            }
            
            if(isset($searchParam['ActiveServices']) && $searchParam['ActiveServices'] !="")
            {    
               $query->leftJoin('u.userServices', 'us')
                     ->leftJoin('us.service', 's')
                     ->andWhere('s.name LIKE :name')
                     ->setParameter('name', '%'.$searchParam['ActiveServices'].'%');

               $query->andWhere('us.status = :status')
                     ->setParameter('status', 1)
                     ->groupBy('l.id');

            }
            
            if(isset($searchParam['Logintime']) && $searchParam['Logintime'] != "")  {
                    
                    $dates = explode('~', $searchParam['Logintime']);
                    $startDate = new \DateTime($dates['0']);
                    $endDate = new \DateTime($dates['1']);
                    
                    if(isset($startDate) && $startDate) {

                        $query->andWhere('l.createdAt >= :startDate')
                              ->setParameter("startDate", $startDate->format('Y-m-d 00:00:00'));
                    }

                    if(isset($endDate) && $endDate) {

                        $query->andWhere('l.createdAt <= :endDate')
                              ->setParameter("endDate", $endDate->format('Y-m-d 23:59:59'));
                    }
                 
            }
            
        }
        if(!empty($adminServiceLocationPermission))
        { 
            $query->andWhere('sl.id IN (:serviceLocation)');
            $query->setParameter('serviceLocation', $adminServiceLocationPermission);
            $query->orWhere('u.userServiceLocation IS NULL');
        }
        if (!empty($slot)) {
            $query->setMaxResults($slot['limit']);
            $query->setFirstResult($slot['offset']);
        }
        return $query->getQuery()->getArrayResult();
        
    }
    
    /**
     * Search from userLoginLog
     * 
     * @param type $query
     * @param type $searchParam
     * @return type
     */
    public function getUserLoginLogSearch($query, $searchParam) {
        
        $query->andWhere('u.username LIKE :username OR u.firstname LIKE :firstname OR u.lastname LIKE :lastname OR c.name LIKE :country OR l.ipAddress LIKE :ip ')
                ->setParameter('username', '%' . $searchParam['search'] . '%')
                ->setParameter('firstname', '%' . $searchParam['search'] . '%')
                ->setParameter('lastname', '%' . $searchParam['search'] . '%')
                ->setParameter('ip', '%' . $searchParam['search'] . '%')
                ->setParameter('country', '%' . $searchParam['search'] . '%');

        
         if(isset($searchParam['startDate']) && $searchParam['startDate']) {
            
            $startDate = new \DateTime($searchParam['startDate']);

            $query->andWhere('l.createdAt >= :startDate')
                  ->setParameter("startDate", $startDate->format('Y-m-d 00:00:00'));
        }
        
        if(isset($searchParam['endDate']) && $searchParam['endDate']) {
            
            $endDate = new \DateTime($searchParam['endDate']);
            
            $query->andWhere('l.createdAt <= :endDate')
                  ->setParameter("endDate", $endDate->format('Y-m-d 23:59:59'));
        }
        
        return $query;
    }
    
     //Added for Gridlist
    public function getUserLoginLogGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper,$user,$id, $adminServiceLocationPermission = '') {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
        
        $query = $this->createQueryBuilder('l')
                //->select('u.id, u.firstname, u.lastname, c.name, sp.name') //, u.isloggedin
                //->addSelect('u.id, u.firstname, u.lastname, c.name, sp.id') //, u.isloggedin
                ->select('l.id loginid, u.id as userId, u.firstname, u.lastname, l.ipAddress, sl.name as userServiceLocation, l.createdAt, c.name as countryName')
                ->innerJoin('l.user', 'u')
                ->leftJoin('l.country', 'c')
                ->leftJoin('u.userServiceLocation', 'sl')
                //->innerJoin('u.serviceLocations', 'sl')
                //->leftJoin('DhiServiceBundle:ServicePurchase', 'sp', 'with', 'u.id=sp.user')
                ->where('u.locked = :locked')
                ->setParameter('locked', 0)
                ->andWhere('u.isDeleted = :deleted')
                ->setParameter('deleted', 0)
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%ROLE_USER%');
        
       if(@$searchData['ActiveServices']!='')
       {    
          $query->leftJoin('u.userServices', 'us')
                ->leftJoin('us.service', 's')
                ->andWhere('s.name LIKE :name')
                ->setParameter('name', '%'.$searchData['ActiveServices'].'%');
          
          $query->andWhere('us.status = :status')
                ->setParameter('status', 1);
       }
       
        
        if($id) {
           
            $query->andWhere('l.user = :user')
                    ->setParameter('user',$user);
        }
        
        if(!empty($searchData) && isset($searchData['Name'])){
            $query->andWhere("CONCAT(u.firstname,' ', u.lastname) LIKE :fullName")
                ->setParameter('fullName', '%'.$searchData['Name'].'%');
        }
            
        if($adminServiceLocationPermission != '')
        { 
            $query->andWhere('sl.id IN (:serviceLocation)');
            $query->setParameter('serviceLocation', $adminServiceLocationPermission);
            $query->orWhere('u.userServiceLocation IS NULL');
        }
       
        if ($SearchType == 'ORLIKE') {

            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {

            $likeStr = $objHelper->andLikeSearch($data);
        }
        
        if(!empty($searchData) && isset($searchData['Logintime']))
        {
               
                $RequestDate = explode('~', $searchData['Logintime']);
    		$ReqFrom = trim($RequestDate[0]);
    		$ReqTo = trim($RequestDate[1]);
                
                if($ReqFrom != "")
                {    
                    $startDate = new \DateTime($ReqFrom);
                    $query->andWhere('l.createdAt >= :today_startdatetime');
                    $query->setParameter('today_startdatetime', $startDate->format('Y-m-d 00:00:00'));
                }
                if($ReqTo != "") {
                    $endDate = new \DateTime($ReqTo);
                    $query->andWhere('l.createdAt <= :today_enddatetime');
                    $query->setParameter('today_enddatetime', $endDate->format('Y-m-d 23:59:59'));
                }
        }  

        if ($likeStr) {

            $query->andWhere($likeStr);
        }

        $query->orderBy($orderBy, $sortOrder);
       
        $countQuery = clone $query;
        $countQuery->select("count(distinct l.id) as totalRecords");
        $objCountData = $countQuery->getQuery()->getOneOrNullResult();
        $countData = $objCountData['totalRecords'];
        
        
        if(@$searchData['ActiveServices']!='')
        {
            $query->groupBy('l.id');
        }

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
                    
                     /*if ($key == 'Name' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'u.username';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }*/
                    
                    if ($key == 'IpAddress' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'l.ipAddress';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    
                    if ($key == 'Country' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'c.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    $i++;
                }
            } else {
                
            }
        }
        return $QueryStr;
    }
    
    public function getAfterNinetyLoginLog() {
    	
    	$todayDateTime 	= new \DateTime();
    	$newDate		= $todayDateTime->modify('-90 DAYS');
    	
    	$query = $this->createQueryBuilder('l')
    					->where('l.createdAt <=:createdAt')
    					->setParameter('createdAt', $newDate->format('Y-m-d H:i:s'));
    	
		$result = $query->getQuery()->getResult();
		
		if ($result) {
			
			return $result;
		}
    	
		return false;
    } 
}
