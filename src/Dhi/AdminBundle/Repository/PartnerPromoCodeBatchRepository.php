<?php

namespace Dhi\AdminBundle\Repository;

use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\EntityRepository;

/**
 * PartnerPromoCodeBatchRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PartnerPromoCodeBatchRepository extends EntityRepository {

	public function getPartnerPromoCodeBatchGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $serviceLocation = '') {
        $data = $this->trim_serach_data($searchData, $SearchType);
        $query = $this->createQueryBuilder('pb')
                ->leftJoin('pb.partner', 'pat')
                ->leftJoin('DhiAdminBundle:PartnerPromoCodes','pc','with','pb.id = pc.batchId')
                ->leftJoin('pc.serviceLocations', 'sl')
                ;
        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data);
        }
        if ($likeStr) {
            $query->andWhere($likeStr);
        }

        if($serviceLocation!= '')
        { 
            $query->andWhere('pc.serviceLocations IN (:serviceLocation)');
            $query->setParameter('serviceLocation', $serviceLocation);
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
                    if ($key == 'PartnerName' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'pat.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    if ($key == 'BatchPrefix' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'pb.batchName';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'like';
                    }
                    $i++;
                }
            } else {
                
            }
        }
        return $QueryStr;
    }
}
