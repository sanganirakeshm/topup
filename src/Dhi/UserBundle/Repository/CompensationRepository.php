<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CompensationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CompensationRepository extends EntityRepository
{
    public function getAllCompensation() {
    
        $query = $this->createQueryBuilder('c')
                ->orderBy('c.id','DESC');
    
        return $query;
    }
    
     //Added for Gridlist
    public function getCompensationGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $isLimit = true, $adminServiceLocationPermission = '') {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
        
        $query = $this->createQueryBuilder('c')
                ->leftJoin('c.serviceLocations', 'sl')
                ->orderBy('c.id','DESC');
                 
        if($adminServiceLocationPermission != '')
        { 
            $query->andWhere('sl.id IN (:serviceLocation)');
            $query->setParameter('serviceLocation', $adminServiceLocationPermission);
            $query->orWhere('sl.id IS NULL');
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
       
        $countData = count($query->getQuery()->getArrayResult());
        
        if ($isLimit) {
            $query->setMaxResults($limit);
            $query->setFirstResult($offset);
        }
       
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
                    
                     if ($key == 'Title' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'c.title   ';
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

    public function getCompensationEmail(){
        $query = $this->createQueryBuilder('c')
            ->select("c.id","scl.id as servicelocation","u.username", "u.email", "u.isEmailVerified", "c.isEmailActive", "c.emailContent", "c.emailSubject", 'cus.id as compUserServiceId', 'c.isEmailSentToAdmin')
            ->innerJoin("c.serviceLocations", "scl")
            ->innerJoin("c.userService", "cus")
            ->innerJoin("cus.userService", "us")
            ->innerJoin("us.user", "u")
            ->where("cus.isEmailSent = :isEmailSent")
            ->andWhere("cus.status = :status")
            ->setParameter("status", 1)
            ->setParameter("isEmailSent", 0)
            ->andWhere("c.isEmailActive = :isEmailActive")
            ->setParameter("isEmailActive", 1)
            ->orderBy('c.id','DESC')
            ->setMaxResults(100)
            ->setFirstResult(0);
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }

    public function getAllIpRangesOfComp($compId) {
        $query = $this->createQueryBuilder('c')
            ->select("isz.fromIpAddressLong", "isz.toIpAddressLong")
            ->innerJoin('c.serviceLocations', 'sl')
            ->innerJoin('sl.ipAddressZones', 'isz')
            ->where("c.id = :compId")
            ->setParameter("compId", $compId);

        $ranges = $query->getQuery()->getArrayResult();

        return $ranges;
    }

    public function updateInstance($params){
        $query = $this->createQueryBuilder('c')
            ->update()
            ->set('c.isInstance', true);

            if (!empty($params['comps'])) {
                foreach ($params['comps'] as $comp) {
                    $id = $comp->getId();
                    $query
                        ->orWhere('c.id = :id'.$id)
                        ->setParameter('id'.$id, $id);
                }
            }
            $query->getQuery()->execute();
    }
}
