<?php

namespace Dhi\AdminBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * GlobalDiscountRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class GlobalDiscountRepository extends EntityRepository
{
   
    public function getAllDiscountCountry($countryId=null) {
        
        $query = $this->createQueryBuilder('gd')
        ->leftJoin('gd.country', 'c');
        
        if($countryId) {
            
           $query->where('gd.country = :country')
             ->setParameter('country', $countryId)      
             ->orderBy('gd.id', 'DESC');
           
        } else {
           $query->where('gd.country IS NULL')
             ->orderBy('gd.id', 'DESC'); 
        }
       
    
        return $query->getQuery()->getResult();
    }

    public function getAllDiscountedCountry() {
        $query = $this->createQueryBuilder('gd')->select('distinct c.name')->innerJoin('gd.country', 'c');
        return $query->getQuery()->getArrayResult();
    }
    
    
    public function checkDiscount($objGlobalDiscount, $maxAmountFlag = false) {
        
        $query = $this->createQueryBuilder('gd')
        ->leftJoin('gd.country', 'c');
        
        if($objGlobalDiscount) {
            
            
                $query->where('gd.maxAmount >= :maxAmt')
                      ->setParameter('maxAmt', $objGlobalDiscount->getMaxAmount());      
                
                $query->andWhere('gd.minAmount <= :minAmt')
                      ->setParameter('minAmt', $objGlobalDiscount->getMinAmount());  
                
                $query->orWhere('gd.minAmount BETWEEN :miAmt AND :mxAmt')
                      ->setParameter('miAmt', $objGlobalDiscount->getMinAmount())      
                      ->setParameter('mxAmt', $objGlobalDiscount->getMaxAmount());      
                
                $query->orWhere('gd.maxAmount BETWEEN :midAmt AND :madAmt')
                      ->setParameter('midAmt', $objGlobalDiscount->getMinAmount())      
                      ->setParameter('madAmt', $objGlobalDiscount->getMaxAmount());  
                
                if($objGlobalDiscount->getCountry()) {
                    
                     $query->andWhere('gd.country = :cid')
                           ->setParameter('cid', $objGlobalDiscount->getCountry()->getId());      
                    
                }
                else {
                    
                    $query->andWhere('gd.country IS NULL');
               
                }
                
                if($objGlobalDiscount->getId() > 0) {
                    $query->andWhere('gd.id != :gid')
                           ->setParameter('gid', $objGlobalDiscount->getId());
                }
               
                
        }
        
        $result = $query->getQuery()->getResult();
        
        //$data =  $query->getQuery()->getArrayResult();
        
        if($result) {
            
            if($maxAmountFlag) {
                    
                    if(1 == count($result)) {
                        
                       return false;
                    }
                    
            }
            return false;
            
        }else {
            
            return true;
        }
        
       
        
    }
    
    //Added for Gridlist
    public function getGlobalGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
        
        $query = $this->createQueryBuilder('gd')
                ->leftJoin('gd.country', 'c')
                ->orderBy('gd.id', 'desc');
                 

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
                    
                     if ($key == 'Country' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'c.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    if ($key == 'MinimumAmount' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'gd.minAmount';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    
                     if ($key == 'MaximumAmount' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'gd.maxAmount';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    
                     if ($key == 'DiscountPurchase' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'gd.percentage';
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
}

