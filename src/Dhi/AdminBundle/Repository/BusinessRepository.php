<?php

namespace Dhi\AdminBundle\Repository;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\EntityRepository;

/**
 * BusinessRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BusinessRepository extends EntityRepository {

	public function getBusinessGridList($limit = 0, $offset = 10, $order_by = "sp.id", $sort_order = "asc", $searchData, $SearchType, $objHelper, $user = "", $ipAddressZones = "", $admin = '') {
		$data = $this->trim_serach_data($searchData, $SearchType);

		$query = $this->createQueryBuilder('b');
		$query->select('b')
			->innerJoin('b.services', 's')
			->where('b.isDeleted = :deleteFlag')
			->setParameter('deleteFlag', false)
			->OrderBy('b.id');
		if ($SearchType == 'ORLIKE') {
			$likeStr = $objHelper->orLikeSearch($data);
		}
		if ($SearchType == 'ANDLIKE') {
			$likeStr = $objHelper->andLikeSearch($data);
		}
		if ($likeStr) {
			$query->andWhere($likeStr);
		}
		$query->orderBy($order_by, $sort_order);
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
					if (($key == 'name' || $key == 'BusinessName') && !empty($val)) {
						$QueryStr[$i]['Field'] = 'b.name';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'pocName' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'b.pocName';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'pocEmail' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'b.pocEmail';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'pocPhone' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'b.pocPhone';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'status' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'b.status';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = '=';
					}
					if ($key == 'service' && !empty($val)) {
						$QueryStr[$i]['Field'] = 's.name';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = '=';
					}
					if ($key == 'createdBy' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'b.createdBy';
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

	public function getAllBusinessNames(){
		$query = $this->createQueryBuilder('b');
	    $query->where('b.isDeleted = :is_deleted')->setParameter("is_deleted", 0);
	    
	    $result = $query->getQuery()->getArrayResult();
	    $returnArr = array();
	    foreach ($result as $business) {
	    	$returnArr[] = $business['name'];
	    }
	    return $returnArr;
	}
}