<?php

namespace Dhi\AdminBundle\Repository;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\EntityRepository;

/**
 * ServicePartnerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ServicePartnerRepository extends EntityRepository {

	public function getpartnerGridList($limit = 0, $offset = 10, $order_by = "sp.id", $sort_order = "asc", $searchData, $SearchType, $objHelper, $user = "", $ipAddressZones = "", $admin = '', $type = '') {
		$data = $this->trim_serach_data($searchData, $SearchType);

		$query = $this->createQueryBuilder('sp');
		$query->select('sp')
			// ->leftJoin('sp.batches', 'b')
			->where('sp.isDeleted = :deleteFlag')
			->setParameter('deleteFlag', false)
			->OrderBy('sp.id');
                
                if($type != 'all'){
                    $query->andWhere('sp.status = :status')
			->setParameter('status', 1);
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
					if (($key == 'name' || $key == 'partnerName') && !empty($val)) {
						$QueryStr[$i]['Field'] = 'sp.name';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'pocName' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'sp.pocName';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'pocEmail' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'sp.pocEmail';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'pocPhone' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'sp.pocPhone';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'status' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'sp.status';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = '=';
					}
					if ($key == 'serviceType' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'sp.serviceType';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					if ($key == 'createdBy' && !empty($val)) {
						$QueryStr[$i]['Field'] = 'sp.createdBy';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'like';
					}
					$i++;
				}
			}
		}
		return $QueryStr;
	}

	public function checkPartnerName($name, $id = 0){
		$query = $this->createQueryBuilder('sp');
		$query->select('sp')->where('sp.name = :condName')->setParameter("condName", $name);
                $query->andWhere('sp.isDeleted = :is_deleted')->setParameter("is_deleted", 0);
		if(!empty($id)){
			$query->andWhere('sp.id <> :id')->setParameter("id", $id);
		}
		$result = $query->getQuery()->getResult();
		return $result;
	}

	public function getServiceLocation($partnerId = 0, $name = '', $serviceLocation = '', $adminServiceLocationPermission = ''){
		$query = $this->createQueryBuilder('sp')
			->select('sl.id', 'sl.name')
			->innerJoin('sp.batches', 'b')
			->innerJoin('b.promoCodes', 'pm')
			->innerJoin('pm.serviceLocations', 'sl')
			->groupBy('sl.id');

		if (!empty($partnerId)) {
			$query->where('sp.id = :partnerId')->setParameter('partnerId', $partnerId);
		}
		if (!empty($name)) {
			$query->where('sp.name = :name')->setParameter('name', $name);
		}
		if (!empty($serviceLocation)) {
			$query->where('sl.name = :serviceLocation')->setParameter('serviceLocation', $serviceLocation);
		}
                if($adminServiceLocationPermission != '')
                { 
                    $query->andWhere('sl.id IN (:adminServiceLocation)');
                    $query->setParameter('adminServiceLocation', $adminServiceLocationPermission);
                }
		$result = $query->getQuery()->getResult();
		return $result;
	}

	public function checkUsernameExists($username){
            
            $query = $this->createQueryBuilder('sp');
                    $query->where('sp.username = :username')
                    ->setParameter('username', $username);
            $query->andWhere('sp.isDeleted = :is_deleted')->setParameter("is_deleted", 0);
            
            $result = $query->getQuery()->getResult();
            return $result;
    }

    public function getAllPartnerNames(){
    	$query = $this->createQueryBuilder('sp');
	    $query->where('sp.isDeleted = :is_deleted')->setParameter("is_deleted", 0)->orderBy("sp.name", "ASC");
	    
	    $result = $query->getQuery()->getArrayResult();
	    $returnArr = array();
	    foreach ($result as $partner) {
	    	$returnArr[] = $partner['name'];
	    }
	    return $returnArr;
    }
}