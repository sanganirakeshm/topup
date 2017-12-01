<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ServiceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ServiceRepository extends EntityRepository {
 
    public function getDisableServices() {
        $services = array();
        
        $query = $this->createQueryBuilder('s')
                ->select('s.name')
                ->where('s.status = :status')
                ->setParameter('status', 0);
        
        $result = $query->getQuery()->getArrayResult();
        
        foreach($result as $record) {
            $services[$record['name']] = $record['name'];
        }
        
        return $services;
    }
    
     //Added for Gridlist
    public function getServiceGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
        
        $query = $this->createQueryBuilder('s');
              
                 

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
       
        $countData = count($query->getQuery()->getArrayResult());
           
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
    
     public function trim_serach_data($searchData, $SearchType) {

        $QueryStr = array();

        if (!empty($searchData)) {

            if ($SearchType == 'ANDLIKE') {

                $i = 0;
                foreach ($searchData as $key => $val) {
                    
                     if ($key == 'Name' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 's.name';
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
    
     public function getAllActiveService() {
         
        $query = $this->createQueryBuilder('s')
                      ->select('s.name, s.id')
                      ->where('s.status = :status')
                      ->setParameter('status', 1);
                      
        return $query->getQuery()->getArrayResult();
    }
     public function getAllService() {
         
        $query = $this->createQueryBuilder('s');
                      
                      
        return $query->getQuery()->getArrayResult();
    }

	 public function getServiceName($param) {

		$query = $this->createQueryBuilder('s')
					->andwhere('s.id = :sid')
					->setParameter('sid', $param);

		$result = $query->getQuery()->getArrayResult();


		foreach($result as $serviceName){
			 $serviceName = $serviceName['name'];
		}

		return $serviceName;
	  }

	public function  getIspIptv($param) {

        $query = $this->createQueryBuilder('s')
                    ->andwhere('s.id != :sid')
                    ->setParameter('sid', $param)
                    ->andwhere('s.name !=:tvod')
                    ->setParameter('tvod','TVOD');
        $result = $query->getQuery()->getResult();
        return $result;
    }

    public function getSpecificServices($param) {

        $query = $this->createQueryBuilder('s');
        if (!empty($param)) {
            $query->andwhere('s.name NOT IN (:services)')->setParameter('services', $param);
        }
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }

}
