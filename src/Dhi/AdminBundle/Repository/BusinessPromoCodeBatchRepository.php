<?php

namespace Dhi\AdminBundle\Repository;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\EntityRepository;

/**
 * BusinessPromoCodeBatchRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessPromoCodeBatchRepository extends EntityRepository {

    public function getBusinessPromoCodeBatchGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $businessName = '') {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
        $query = $this->createQueryBuilder('bb')
                ->innerJoin('bb.business', 'bus')
                ->where('bus.isDeleted =  :isDeleted ')
                ->setParameter('isDeleted', false)
                ->andWhere('bus.status = :status ')
                ->setParameter('status', 1);

        if(!empty($businessName) && $businessName != ''){
            $query->andWhere('bus.name LIKE  :businessName ')
                  ->setParameter('businessName', '%'.$businessName.'%');
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
                    if ($key == 'BusinessName' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'bus.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    if ($key == 'BatchPrefix' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'bb.batchName';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'like';
                    }
                    $i++;
                }
            }
        }
        return $QueryStr;
    }

    public function checkBusinessName($name, $id = 0){
		$query = $this->createQueryBuilder('b');
		$query->select('b')->where('b.name = :condName')->setParameter("condName", $name);
		$query->andWhere('b.isDeleted = :is_deleted')->setParameter("is_deleted", 0);
		if(!empty($id)){
			$query->andWhere('b.id <> :id')->setParameter("id", $id);
		}
		$result = $query->getQuery()->getResult();
		return $result;
	}

	public function getServiceLocation($businessId = 0, $name = '', $serviceLocation = ''){
		$query = $this->createQueryBuilder('bu')
			->select('sl.id', 'sl.name')
			->innerJoin('bu.batches', 'b')
			->innerJoin('b.promoCodes', 'pm')
			->innerJoin('pm.serviceLocations', 'sl')
			->groupBy('sl.id');

		if (!empty($businessId)) {
			$query->where('bu.id = :businessId')->setParameter('businessId', $businessId);
		}
		if (!empty($name)) {
			$query->where('bu.name = :name')->setParameter('name', $name);
		}
		if (!empty($serviceLocation)) {
			$query->where('sl.name = :serviceLocation')->setParameter('serviceLocation', $serviceLocation);
		}
		$result = $query->getQuery()->getResult();
		return $result;
	}

	public function getRedeemedPromocodes($id){
		$query = $this->createQueryBuilder('bu')
			->innerJoin('bu.batches', 'b')
			->innerJoin('b.promoCodes', 'p')
			->where('p.isRedeemed = :isRedeemed')->setParameter("isRedeemed", 'Yes')
			->andWhere('bu.id = :id')->setParameter("id", $id);

		$result = $query->getQuery()->getArrayResult();
		return $result;
	}
}