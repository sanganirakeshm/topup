<?php

namespace Dhi\AdminBundle\Repository;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\EntityRepository;

/**
 * AddonsMasterRespository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AddonsMasterRespository extends EntityRepository {
	
    public function getAddonsGridList($limit = 0, $offset = 10, $order_by = "cm.id", $sort_order = "asc", $searchData, $SearchType, $objHelper) {
        
        $data  = $this->trim_serach_data($searchData, $SearchType);
        $query = $this->createQueryBuilder('am');
        $query->select('am.id', 'am.name', 'am.image');

        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
        }

        if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data);
        }

        if ($likeStr) {
            $query->andWhere($likeStr);
        }

        $countQuery = clone $query;
        $countQuery->select("COUNT(am.id) as totalRecords");
        $countData  = $countQuery->getQuery()->getSingleScalarResult();

        $query->orderBy($order_by, $sort_order);
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);
        $result = $query->getQuery()->getArrayResult();
        $dataResult = array();
        if ($countData > 0) {
            $dataResult['result']      = $result;
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
                    if (($key == 'name') && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'am.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'like';
                    }
                    $i++;
                }
            }
        }
        return $QueryStr;
    }

    public function getAddons(){
        $query = $this->createQueryBuilder('am');
        $query->select('am.name');
        $result = $query->getQuery()->getArrayResult();
        if (!empty($result)) {
            $allAddons = array_map(function($addons){ return $addons['name']; }, $result);
            return $allAddons;
        }else{
            return array();
        }
    }

    public function getAddonsImage(){
        $query = $this->createQueryBuilder('am')
                ->select('am.name' , 'am.image');
        $result = $query->getQuery()->getArrayResult();
        $arrAddonsImage = array();
        if(count($result) > 0){
            foreach ($result as $row){
                $arrAddonsImage[$row['name']] = $row['image'];
            }
        }
        return $arrAddonsImage;
    }
}