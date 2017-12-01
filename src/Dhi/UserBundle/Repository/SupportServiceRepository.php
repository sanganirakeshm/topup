<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SupportServiceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SupportServiceRepository extends EntityRepository {
    
    public function getSupportServiceGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
        
        $query = $this->createQueryBuilder('ss')
          ->select('ss.id, ss.serviceName, ss.isActive, u.username')
          ->leftJoin('DhiUserBundle:User', 'u', 'with', 'ss.createdBy = u.id')
          ->where('ss.isDeleted = :isdeleted')
          ->setParameter('isdeleted', 0);

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
       
        $countQuery   = clone $query;
        $countQuery->select("count(ss.id) as totalRecords");
        $objCountData = $countQuery->getQuery()->getOneOrNullResult();
        $countData    = $objCountData['totalRecords'];
           
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
                    
                    if ($key == 'ServiceName' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'ss.serviceName';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    if ($key == 'CreatedBy' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'u.username';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';
                    }
                    if ($key == 'Status' && !empty($val)) {
                        
                        $arrVal = array('Active' => 1, 'InActive' => 0);
                        $QueryStr[$i]['Field'] = 'ss.isActive';
                        $QueryStr[$i]['Value'] = $arrVal[$val];
                        $QueryStr[$i]['Operator'] = '=';

                    }
                    
                    $i++;
                }
            } else {
                
            }
        }
        return $QueryStr;
    }
}