<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PromoCodeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PromoCodeRepository extends EntityRepository
{
	
	 //Added for Gridlist
    public function getPromoCodeGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $createdBy = '', $serviceLocation = '') {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
		
		
        $query = $this->createQueryBuilder('pc')
                ->select('pc','p.packageName','p.amount','b.displayBundleName', 'b.description','b.amount as bundleAmount', 'p.validity', 'p.bandwidth', 'p.isHourlyPlan')
				->leftJoin('pc.service', 's')
				->leftJoin('pc.serviceLocations', 'sl')
				->leftJoin('DhiAdminBundle:Package','p','with','pc.packageId = p.packageId')
				->leftJoin('DhiAdminBundle:Bundle','b','with','b.bundle_id = pc.packageId')
                ->orderBy('pc.id', 'desc')
		        ->groupBy('pc.id');
        
        if(!empty($createdBy) && $createdBy != ''){
            $query->where('pc.createdBy = :createdBy')
                    ->setParameter('createdBy', $createdBy);
        }

        if ($SearchType == 'ORLIKE') {

            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {

            $likeStr = $objHelper->andLikeSearch($data);
        }
        $query->andWhere("pc.isPlanExpired <> :isPlanExpired  OR pc.isPlanExpired IS NULL")->setParameter('isPlanExpired', "Yes");
        if ($likeStr) {

            $query->andWhere($likeStr);
        }


        if($serviceLocation!= '')
        { 
            $query->andWhere('sl.id IN (:serviceLocation)');
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
                    
                     if ($key == 'Services' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 's.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';
                    }

                    if ($key == 'PromoCode' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'pc.promoCode';
                        $QueryStr[$i]['Value'] = $val ;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    
					if ($key == 'ServiceLocation' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'sl.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';
                    }                                   
					if ($key == 'CreatedBy' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'pc.createdBy';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    if ($key == 'Action' && !empty($val)) {

                    	$RequestDate = explode('~', $val);
						$ReqFrom = trim($RequestDate[0]);
						$ReqTo = trim($RequestDate[1]);

						if ($ReqFrom != "") {
							$startDate = new \DateTime($ReqFrom);

							$QueryStr[$i]['Field'] = 'pc.createdAt';
	                        $QueryStr[$i]['Value'] = $startDate->format('Y-m-d 00:00:00');
	                        $QueryStr[$i]['Operator'] = '>=';

						}
						if ($ReqTo != "") {
							if ($ReqFrom != "") {
								$i++;
							}
							$endDate = new \DateTime($ReqTo);
							$QueryStr[$i]['Field'] = 'pc.createdAt';
	                        $QueryStr[$i]['Value'] = $endDate->format('Y-m-d 23:59:59');
	                        $QueryStr[$i]['Operator'] = '<=';
						}
                    }
                    $i++;
                }
            } else {
                
            }
        }
        return $QueryStr;
    }
	
	 public function checkPromoCodeExist() {
        
       // $data = $this->trim_serach_data($searchData, $SearchType);
		 
		 $query = $this->createQueryBuilder('pc')
                ->select('pc')
				->where('pc.status = :status')
				->setParameter('status', 1)
				->leftJoin('pc.service', 's')
				->leftJoin('pc.serviceLocations', 'sl')
                ->orderBy('pc.id', 'desc');
				 
 				
			
		$result = $query->getQuery()->getArrayResult();
		
		
		return $result ;
	
	 }
	 
	 public function getPdfPromoData($searchData, $adminServiceLocationPermission = ''){
		
		  $query = $this->createQueryBuilder('pc')
                ->select('pc','p.packageName','p.amount','b.displayBundleName', 'b.description','b.amount as bundleAmount', 'p.validity', 'p.bandwidth', 'p.packageType','pc.note')
				->leftJoin('pc.service', 's')
				->leftJoin('pc.serviceLocations', 'sl')
				->leftJoin('DhiAdminBundle:Package','p','with','pc.packageId = p.packageId')
				->leftJoin('DhiAdminBundle:Bundle','b','with','b.bundle_id = pc.packageId')
                ->orderBy('pc.id', 'desc')
				->groupBy('pc.id'); 
		  
			$query->andWhere("pc.isPlanExpired <> :isPlanExpired")->setParameter('isPlanExpired', "Yes");
		  if(!empty($searchData['Services'])){
			  $query ->andwhere('s.name LIKE :sname')
				->setParameter('sname','%'.$searchData['Services'].'%');
		  }
		  if(!empty($searchData['CreatedBy'])){
			  $query ->andwhere('pc.createdBy LIKE :uname')
				->setParameter('uname', '%'.$searchData['CreatedBy'].'%');
		  }
		  if(!empty($searchData['ServiceLocation'])){
			  $query ->andwhere('sl.name = :slname')
				->setParameter('slname', $searchData['ServiceLocation']);
		  }
		  if(!empty($searchData['PromoCode'])){
			  $query ->andwhere('pc.promoCode = :pmname')
				->setParameter('pmname', $searchData['PromoCode']);
		  }
		  if(!empty($searchData['Action'])){
		  		$RequestDate = explode('~', $searchData['Action']);
				$ReqFrom = trim($RequestDate[0]);
				$ReqTo = trim($RequestDate[1]);

				if ($ReqFrom != "") {
					$startDate = new \DateTime($ReqFrom);
                    $query->andwhere('pc.createdAt >= :createdStartAt')->setParameter('createdStartAt', $startDate->format('Y-m-d 00:00:00'));
				}
				if ($ReqTo != "") {
					$endDate = new \DateTime($ReqTo);
                    $query->andwhere('pc.createdAt <= :createdEndAt')->setParameter('createdEndAt', $endDate->format('Y-m-d 23:59:59'));
				}
		  }
		
                if(!empty($adminServiceLocationPermission)){ 
                    $query->andWhere('sl.id IN (:adminServiceLocationPermission)');
                    $query->setParameter('adminServiceLocationPermission', $adminServiceLocationPermission);
                }
		$result = $query->getQuery()->getResult();
		
		return $result ;
		 
	 }
	 
	  public function getServicePromoData($promocode){
		  
		$query = $this->createQueryBuilder('pc')
                ->select('pc')
				->leftJoin('pc.service', 's')
				->leftJoin('pc.users', 'u')
				->leftJoin('pc.serviceLocations', 'sl')
                ->orderBy('pc.id', 'desc')
				->groupBy('pc.id');   
		
		if(!empty($promocode)){
			$query ->andwhere('pc.promoCode = :pmname')
				->setParameter('pmname', $promocode);
			
		}
		
		$result = $query->getQuery()->getResult();
		
		return $result ;
		  
	  }


	public function getPackagePromoData($promoCode){

		  $query = $this->createQueryBuilder('pc')
                ->select('pc','p.packageId', 'p.packageName','p.isDeers','p.description','p.amount','p.validity','p.bandwidth','bisp.isDeers as ispDeers','biptv.isDeers as iptvDeers','b.description as bundleDesc','b.displayBundleName as displayBundleName','b.discount as bundleDiscount','b.bundleName as bundleName', 'b.bundle_id', 'p.isExpired', 'p.isHourlyPlan')
				->leftJoin('pc.service', 's')
				->leftJoin('DhiAdminBundle:Package','p','with','pc.packageId = p.packageId')
				->leftJoin('DhiAdminBundle:Bundle','b','with','pc.packageId = b.bundle_id')
				->leftJoin('b.isp','bisp')
				->leftJoin('b.iptv','biptv')
				->leftJoin('pc.serviceLocations', 'sl')
                ->orderBy('pc.id', 'desc')
				->Where('pc.status = :status')
				->setParameter('status', 1)
				->groupBy('pc.id');

		  if($promoCode){
			  $query ->andwhere('pc.promoCode = :promocode')
				->setParameter('promocode',$promoCode);
		  }

		$result = $query->getQuery()->getOneOrNullResult();

		return $result ;

	}

	public function reAssignPlanCodes($packages){
		$flag = "'Yes'";
		$query = $this->createQueryBuilder("pc")->update()
			->set("pc.isPlanExpired", $flag)
			->where("pc.noOfRedemption IS NULL")
			->andWhere('(pc.packageId NOT IN (:packages) AND pc.isBundle = :isPackageBundle) OR (pc.packageId NOT IN (:bundles) AND pc.isBundle = :isBundle)')
			->setParameter('packages', $packages['package'])
            ->setParameter('bundles', $packages['bundle'])
            ->setParameter('isPackageBundle', 0)
            ->setParameter('isBundle', 1);
		return $query->getQuery()->execute();
	}

	public function getUnAssignedPromoCodesGrid($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $batchId = 0) {
        $data = $this->trim_serach_data_unassigned($searchData, $SearchType, $batchId);
        $query = $this->createQueryBuilder('pc')
            ->select("pc.id as pid", "pc.promoCode as code", "'Customer' as type", "pc.expiredAt as expiryDate", "pc.duration", "pc.note", "sl.name as serviceLocation", "pc.id")
            ->leftJoin('pc.serviceLocations', 'sl')
            ->Where('pc.isPlanExpired = :isPlanExpired')
            ->setParameter('isPlanExpired', "Yes");

        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data);
        }
        if ($likeStr) {
            $query->andWhere($likeStr);
        }

        $result     = $query->getQuery()->getArrayResult();
        $dataResult = array();
        $countData  = count($result);
        if ($countData > 0) {
            $dataResult['result'] = $result;
            $dataResult['totalRecord'] = $countData;
            return $dataResult;
        }
        return false;
    }

    public function trim_serach_data_unassigned($searchData, $SearchType, $batchId) {
        $QueryStr = array();
        if (!empty($searchData)) {
            if ($SearchType == 'ANDLIKE') {
                $i = 0;
                foreach ($searchData as $key => $val) {
                    if ($key == 'code' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'pc.promoCode';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    $i++;
                }
            }
        }
        return $QueryStr;
    }
}
