<?php

namespace Dhi\ServiceBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PurchaseOrderRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PurchaseOrderRepository extends EntityRepository {

    public $countryDisable = 1;

	public function getPurchaseHistoryQuery($user, $ipAddressZones, $country = '',$isAradialMigrated = null, $purchaseType = '', $select = false) {

		$query = $this->createQueryBuilder('po')
                                
				->leftJoin('po.servicePurchases', 'sp')
				->leftjoin('sp.userService', 'us')
				->leftJoin('sp.service', 's')
				->leftJoin('sp.user', 'u')
				->leftJoin('po.paymentMethod', 'pm')
				->leftJoin('po.paypalCheckout', 'pp')
				->leftJoin('po.chase', 'cc')
				->leftJoin('po.milstar', 'm')
				->leftjoin('po.paypalRecurringProfile', 'prp')
				->leftJoin('po.userCreditLogs', 'uc')
                ->leftJoin('sp.whiteLabel','wl')
				->where('sp.purchaseOrder > :purchaseOrder')
				->setParameter('purchaseOrder', 0)
				->andWhere('po.paymentStatus != :pstatus')
				->setParameter('pstatus', 'InProcess')
				->andWhere('sp.paymentStatus NOT IN(:status)')
				->setParameter('status', array('New', 'NeedToRefund'))
				->groupBy('po.id')
				->orderby('po.id', 'DESC');
		if ($select) {
			$query->select('wl.domain','wl.companyName','po.id as poId', 'GROUP_CONCAT(sp.isCredit) as isCredit', 'GROUP_CONCAT(sp.isCompensation) as isCompensation', 'GROUP_CONCAT(s.name) as serviceName', 'pm.name as paymentmethodName', 'pm.code as paymentmethodCode', 'pp.paypalTransactionId', 'm.authTicket', 'po.paymentBy', 'u.username', 'u.id as uId', 'po.orderNumber', 'sp.paymentStatus', 'po.totalAmount', 'po.refundAmount', 'us.refundedAt', 'po.createdAt', 'po.ipAddress', 'prp.id as prpId', 'uc.type', 'po.compensationValidity', 'cc.chaseTransactionId');
		}

        if($isAradialMigrated == 1){
            $query->andWhere('u.isAradialMigrated = 1');
        }

        if($purchaseType){
            $query->andWhere('sp.paymentStatus IN ( :purchaseType )')->setParameter('purchaseType', $purchaseType);
        }

//        if (!empty($ipAddressZones)) {
//
//            foreach ($ipAddressZones as $key => $value) {
//
//                $query->orWhere('u.ipAddressLong >= :fromIp')
//                        ->setParameter('fromIp', $value['fromIP']);
//
//                $query->andWhere('u.ipAddressLong <= :toIp')
//                        ->setParameter('toIp', $value['toIP']);
//            }
//        }

		if ($user) {

			$query->andWhere('po.user = :userId')->setParameter('userId', $user);
		}
                if($this->countryDisable == 1){
                    if ($country != '') {
                            $query->andWhere('u.userServiceLocation IN (:country)');
                            $query->setParameter('country', $country);
                    }
                }

		return $query;
	}

	public function getPurchaseHistoryGrid($limit = 0, $offset = 10, $order_by = "id", $sort_order = "asc", $searchData, $SearchType, $objHelper, $user = "", $ipAddressZones = "", $admin = '', $country = '',$isAradialMigret = null, $purchaseType = '', $select = false, $module = '') {

		$data = $this->trim_serach_data_purchase_history($searchData, $SearchType);
		$query = $this->getPurchaseHistoryQuery($user, $ipAddressZones, $country, $isAradialMigret, $purchaseType, $select);

		if ($SearchType == 'ORLIKE') {
			$likeStr = $objHelper->orLikeSearch($data);
		}
		if ($SearchType == 'ANDLIKE') {
			$likeStr = $objHelper->andLikeSearch($data);
		}

		if (!empty($searchData)) {

			if (isset($searchData['transactionId'])) {

				$query->andWhere('pp.paypalTransactionId LIKE :transactionId OR m.authTicket LIKE :transactionId or cc.chaseTransactionId LIKE :transactionId');
				$query->setParameter('transactionId', '%' . $searchData['transactionId'] . '%');
			}
            if (isset($searchData['userName'])) {

				$query->andWhere('u.username LIKE :username OR u.email LIKE :username');
				$query->setParameter('username', '%' . $searchData['userName'] . '%');
			}
		}

		if (isset($searchData['purchaseDate'])) {

			$RequestDate = explode('~', $searchData['purchaseDate']);
			if (!empty($RequestDate[0])) {
				$ReqFrom     = trim($RequestDate[0]);
				$startDate = new \DateTime($ReqFrom);
			}
			if (!empty($RequestDate[1])) {
				$ReqTo       = trim($RequestDate[1]);
				$endDate = new \DateTime($ReqTo);
			}
		}

		if (!empty($startDate)) {
			$query->andWhere('po.createdAt >= :today_startdatetime');
			$query->setParameter('today_startdatetime', $startDate->format('Y-m-d 00:00:00'));
		}

		if (!empty($endDate)) {
			$query->andWhere('po.createdAt <= :today_enddatetime');
			$query->setParameter('today_enddatetime', $endDate->format('Y-m-d 23:59:59'));
		}

		if ($likeStr) {

			$query->andWhere($likeStr);
		}


		$query->orderBy($order_by, $sort_order);

                if($isAradialMigret == 1){
                    $query->andWhere('u.isAradialMigrated = 1');
                }

		$countData = count($query->getQuery()->getResult());

		$query->setMaxResults($limit);
		$query->setFirstResult($offset);
                
		if ($select) {
			$result = $query->getQuery()->getArrayResult();
		}else{
			$result = $query->getQuery()->getResult();
		}

                $dataResult = array();
		if (count($result) > 0) {

			$dataResult['result'] = $result;
			$dataResult['totalRecord'] = $countData;

			return $dataResult;
		}

		return false;
	}

	public function trim_serach_data_purchase_history($searchData, $SearchType) {

                $QueryStr = array();

		if (!empty($searchData)) {
//			if ($SearchType == 'ANDLIKE') {

				$i = 0;
				foreach ($searchData as $key => $val) {
                                    $this->countryDisable = 2 ;

					if ($key == 'orderNumber' && !empty($val)) {

						$QueryStr[$i]['Field'] = 'po.orderNumber';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					if ($key == 'totalAmount' && !empty($val)) {

						$QueryStr[$i]['Field'] = 'po.totalAmount';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					if ($key == 'refundAmount' && !empty($val)) {

						$QueryStr[$i]['Field'] = 'po.refundAmount';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					if ($key == 'paymentMethod' && !empty($val)) {

						$QueryStr[$i]['Field'] = 'pm.name';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = '=';
					}
					if ($key == 'purcasedService' && !empty($val)) {

						$QueryStr[$i]['Field'] = 's.name';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					if ($key == 'paymentStatus' && !empty($val)) {

						if ($val == "Plan Expired by Customer Support") {
							$val = "Expired";
						}
						$QueryStr[$i]['Field'] = 'sp.paymentStatus';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
                                        if ($key == 'isAradialMigrated' && !empty($val)) {

						$QueryStr[$i]['Field'] = 'u.isAradialMigrated';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
                                        
                                        if ($key == 'whitelabel' && !empty($val)) {

						$QueryStr[$i]['Field'] = 'sp.whiteLabel';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = '=';
					}
					$i++;
//				}

			}
		}
		return $QueryStr;
	}

	public function getExpandedPurchaseDetail($purchaseOrderId) {

		$query = $this->getPurchaseHistoryQuery(null, null);

		$query->andWhere('po.id = :purchaseOrderId')->setParameter('purchaseOrderId', $purchaseOrderId);

		$result = $query->getQuery()->getOneOrNullResult();

		return $result;
	}

	public function getPdfPurchaseHistory($user, $ipAddressZones, $country = '', $select = false, $slot = array()) {
		$query = $this->getPurchaseHistoryQuery($user, $ipAddressZones, $country, null, '', $select);
		if (!empty($slot)) {
			$query->setMaxResults($slot['limit']);
       		$query->setFirstResult($slot['offset']);
		}

		$result = $query->getQuery()->getResult();
		return $result;
	}

	public function getSearchPdfPurchaseHistory($user, $ipAddressZones, $country = '', $searchData, $select = false, $slot = array()) {

        if (!empty($searchData)) {
            $this->countryDisable = 2;
        }
		$query = $this->getPurchaseHistoryQuery($user, $ipAddressZones, $country, null, '', $select);

		if (isset($searchData['userName']) && $searchData['userName'] != '') {
			$query->andWhere('u.username LIKE :username')
					->setParameter('username', '%' . $searchData['userName'] . '%');
		}

		if (isset($searchData['orderNumber']) && $searchData['orderNumber'] != '') {
			$query->andWhere('po.orderNumber LIKE :orderNumber')
					->setParameter('orderNumber', '%' . $searchData['orderNumber'] . '%');
		}

		if (isset($searchData['paymentMethod']) && $searchData['paymentMethod'] != '') {

			$query->andWhere('pm.name = :paymentmethod')
					->setParameter('paymentmethod', $searchData['paymentMethod']);
		}

		if (isset($searchData['transactionId']) && $searchData['transactionId'] != '') {

			$query->andWhere('pp.paypalTransactionId LIKE :transactionId OR m.authTicket LIKE :transactionId OR cc.chaseTransactionId LIKE :transactionId');
			$query->setParameter('transactionId', '%' . $searchData['transactionId'] . '%');
		}

		if (isset($searchData['paymentStatus']) && $searchData['paymentStatus'] != '') {

			$query->andWhere('sp.paymentStatus = :paymentStatus')
					->setParameter('paymentStatus', $searchData['paymentStatus']);
		}
                if (isset($searchData['isAradialMigrated']) && $searchData['isAradialMigrated'] != '') {

			$query->andWhere('u.isAradialMigrated = :isAradialMigrated')
					->setParameter('isAradialMigrated', $searchData['isAradialMigrated']);
		}

		if (!empty($searchData) && isset($searchData['purchaseDate'])) {

			$RequestDate = explode('~', $searchData['purchaseDate']);
			$ReqFrom = trim($RequestDate[0]);
			$ReqTo = trim($RequestDate[1]);

			if ($ReqFrom != "") {
				$startDate = new \DateTime($ReqFrom);
				$query->andWhere('po.createdAt >= :today_startdatetime');
				$query->setParameter('today_startdatetime', $startDate->format('Y-m-d 00:00:00'));
			}
			if ($ReqTo != "") {
				$endDate = new \DateTime($ReqTo);
				$query->andWhere('po.createdAt <= :today_enddatetime');
				$query->setParameter('today_enddatetime', $endDate->format('Y-m-d 23:59:59'));
			}
		}
                
                if (isset($searchData['whitelabel']) && $searchData['whitelabel'] != '') {

			$query->andWhere('sp.whiteLabel = :whitelabelsite')
					->setParameter('whitelabelsite', $searchData['whitelabel']);
		}

		if (!empty($slot)) {
			$query->setMaxResults($slot['limit']);
       		$query->setFirstResult($slot['offset']);
		}

		$result = $query->getQuery()->getResult();
		return $result;
	}

	public function getSalesQuery($user, $ipAddressZones, $country, $searchData, $chart = false, $flagPackage = false) {

		// payment methods
		$paymentMethod = array('Paypal', 'Credit Card', 'Milstar', 'Cash', 'Eagle Cash');
		//$serviceLocaton = array('BAF', 'KAF', 'DAF');

		$query = $this->createQueryBuilder('po');

		if ($flagPackage) {

			$query->select('us.packageId', 'us.actualAmount', 'SUM(us.payableAmount) as totalActualAmount', 'us.packageName', 'po.id as purchaseOrderId', 'us.payableAmount as totalAmount', 'pm.name as paymentMethod', 'sp.id as servicePurchaseId', 's.name as serviceName', 'u.username', 'po.createdAt', 'us.createdAt as createDate', 'u.ipAddressLong as userServiceLocationIp', 'us.serviceLocationIp');
		} else {

			$query->select('po.id as purchaseOrderId', 'us.payableAmount as totalAmount', 'pm.name as paymentMethod', 'sp.id as servicePurchaseId', 's.name as serviceName', 'u.username', 'po.createdAt', 'us.createdAt as createDate', 'u.ipAddressLong as userServiceLocationIp', 'us.serviceLocationIp');
		}

		$query->leftJoin('po.servicePurchases', 'sp')
				->leftJoin('po.paymentMethod', 'pm')
				->leftJoin('sp.userService', 'us')
				->leftJoin('sp.service', 's')
				->leftJoin('sp.user', 'u')
				->leftJoin('u.userServiceLocation', 'usl');


		$query->where('sp.purchaseOrder > :purchaseOrder')
				->setParameter('purchaseOrder', 0);

		$query->andWhere('sp.paymentStatus = :ppaystatus')
				->setParameter('ppaystatus', 'Completed');


		$query->andWhere('po.paymentStatus = :postatus')
				->setParameter('postatus', 'Completed');

		$query->andWhere('pm.name IN (:pmName)')
				->setParameter('pmName', $paymentMethod);

//		$query->andWhere('usl.name IN (:uslName)')
//				->setParameter('uslName', $serviceLocaton);

		if ($user) {

			$query->andWhere('po.user = :userId')->setParameter('userId', $user);
		}

		if ($country != '') {

			$query->andWhere('u.userServiceLocation IN (:country)');
			$query->setParameter('country', $country);
		}


		if (!empty($searchData)) {

			if (isset($searchData['totalSales'])) {

				$RequestDate = explode('~', $searchData['totalSales']);
				$ReqFrom = trim($RequestDate[0]);
				$ReqTo = trim($RequestDate[1]);

				if ($ReqFrom != "") {
					$startDate = new \DateTime($ReqFrom);
					$query->andWhere('po.createdAt >= :today_startdatetime');
					$query->setParameter('today_startdatetime', $startDate->format('Y-m-d 00:00:00'));
				}
				if ($ReqTo != "") {
					$endDate = new \DateTime($ReqTo);
					$query->andWhere('po.createdAt <= :today_enddatetime');
					$query->setParameter('today_enddatetime', $endDate->format('Y-m-d 23:59:59'));
				}
			}
			if(isset($searchData['serviceLocation']) && $searchData['serviceLocation'] != ''){
								//$query->leftJoin('u.serviceLocations', 'sl');
//									->leftJoin('sl.ipAddressZones', 'ip')
//									->where('ip.fromIpAddressLong <= :fromIp')
//									->setParameter('fromIp', 'us.serviceLocationIp')
//									->andWhere('ip.toIpAddressLong >= :toIp')
//									->setParameter('toIp', 'us.serviceLocationIp');
				//us.serviceLocationIp
				$query->andWhere('usl.name LIKE :locationName')
						->setParameter('locationName', '%'.$searchData['serviceLocation'].'%');


			}



			if (isset($searchData['serviceType']) && $searchData['serviceType'] != '') {
				$query->andWhere('s.name = :servicename')
						->setParameter('servicename', $searchData['serviceType']);
			}

			if (isset($searchData['paymentMethod']) && $searchData['paymentMethod'] != '') {
				$query->andWhere('pm.name LIKE :paymentmethod')
						->setParameter('paymentmethod', '%' . $searchData['paymentMethod'] . '%');
			}


			if (!$chart && empty($searchData['totalSales'])) {

//				$firstDatePreviousMonth = new \DateTime();
//				$firstDatePreviousMonth = $firstDatePreviousMonth->modify('first day of previous month');
//
//				$lastDatePreviousMonth = new \DateTime();
//				$lastDatePreviousMonth = $lastDatePreviousMonth->modify('last day of previous month');
//
//				$query->andWhere('po.createdAt >= :today_startdatetime');
//				$query->setParameter('today_startdatetime', $firstDatePreviousMonth->format('Y-m-d 00:00:00'));
//
//				$query->andWhere('po.createdAt <= :today_enddatetime');
//				$query->setParameter('today_enddatetime', $lastDatePreviousMonth->format('Y-m-d 23:59:59'));
			}
		} else {

			if (!$chart) {

//				$firstDatePreviousMonth = new \DateTime();
//				$firstDatePreviousMonth = $firstDatePreviousMonth->modify('first day of previous month');
//
//				$lastDatePreviousMonth = new \DateTime();
//				$lastDatePreviousMonth = $lastDatePreviousMonth->modify('last day of previous month');

				/*$query->andWhere('po.createdAt >= :today_startdatetime');
				$query->setParameter('today_startdatetime', $firstDatePreviousMonth->format('Y-m-d 00:00:00'));

				$query->andWhere('po.createdAt <= :today_enddatetime');
				$query->setParameter('today_enddatetime', $lastDatePreviousMonth->format('Y-m-d 23:59:59'));*/
			}
		}

		$query->orderby('po.id', 'DESC');

		if ($flagPackage) {

			$query->groupBy('us.packageId');
			$query->addGroupBy('us.service');
			$query->addGroupBy('us.actualAmount');
		}
		//$query->addGroupBy('po.id');
		return $query;
	}

	public function getSalesReportGrid($limit = 0, $offset = 10, $order_by = "id", $sort_order = "asc", $searchData, $SearchType, $objHelper, $user = "", $ipAddressZones = "", $admin = '', $country = '') {

		$query = $this->getSalesQuery($user, $ipAddressZones, $country, $searchData);

		$query->orderBy($order_by, $sort_order);

		//$query->setMaxResults($limit);
       // $query->setFirstResult($offset);

//		$resultData = $query->getQuery()->getSQL();
		//print_r($resultData);exit;
		$resultData = $query->getQuery()->getArrayResult();
		//print_r($resultData);exit;

		return $this->getSalesData($resultData);
	}

	public function getSalesReportData($searchData, $ipAddressZones, $admin, $country) {

		$query = $this->getSalesQuery(null, $ipAddressZones, $country, $searchData);

		$resultData = $query->getQuery()->getArrayResult();

		return $this->getSalesData($resultData);
	}


	public function getSalesData($resultData, $chart = false) {

		$em = $this->getEntityManager();
		$serviceLocations = $this->getEntityManager()->getRepository("DhiAdminBundle:ServiceLocation")->findAll();
		$services = $this->getEntityManager()->getRepository("DhiUserBundle:Service")->findAll();
		$paymentMethods = $this->getEntityManager()->getRepository("DhiServiceBundle:PaymentMethod")->findAll();


		if ($resultData) {

			foreach ($resultData as $key => $record) {

				$locationIp = $record['serviceLocationIp'] ? $record['serviceLocationIp'] : $record['userServiceLocationIp'];

				$serviceLocationName = $em->getRepository("DhiAdminBundle:ServiceLocation")
								->createQueryBuilder('sl')
								->select('sl.name')
								->leftJoin('sl.ipAddressZones', 'ip')
								->where('ip.fromIpAddressLong <= :fromIp')
								->setParameter('fromIp', $locationIp)
								->andWhere('ip.toIpAddressLong >= :toIp')
								->setParameter('toIp', $locationIp)
								->getQuery()->getOneOrNullResult();

				$resultData[$key]['serviceLocationName'] = $serviceLocationName ? $serviceLocationName['name'] : '';
			}
		}

		$finalArray = array();
		$grandTotal = 0;
                $ServicegrandTotal = array();
                $IPTVgrandTotal = 0;
				$ISPgrandTotal = 0;
                $currentmonthtotal = 0;
		$finalArrayChart = array();

		foreach ($serviceLocations as $serviceLocationRecord) {

			foreach ($services as $serviceRecord) {

				foreach ($paymentMethods as $method) {

					foreach ($resultData as $key => $record) {

						if ($record['createDate']) {

							$date = explode('-', $record['createDate']->format('Y-m-d'));
						}

						if ($record['serviceLocationName'] != "" && $record['serviceLocationName'] == $serviceLocationRecord->getName()) {


							if ($record['serviceName'] && $record['serviceName'] == $serviceRecord->getName()) {

								if ($record['paymentMethod'] == $method->getName()) {

									if ($record['createDate'] && $record['serviceLocationName'] && $record['serviceName'] && $record['paymentMethod']) {

										$grandTotal += $record['totalAmount'];

//														if($record['serviceName'] == $serviceRecord->getName()){
//														   if(isset($ServicegrandTotal[$record['serviceName']])){
//															  $IPTVgrandTotal+= $record['totalAmount'];
//														   } else {
//															  $IPTVgrandTotal = $record['totalAmount'];
//														   }
//														}
														if($record['serviceName'] == 'IPTV'){
															$IPTVgrandTotal+= $record['totalAmount'];
														} else if($record['serviceName'] == 'ISP') {
															$ISPgrandTotal+= $record['totalAmount'];
														} else{
															//$ISPgrandTotal = 0;
														}
//

														$ServicegrandTotal['IPTV']= $IPTVgrandTotal;
														$ServicegrandTotal['ISP']= $ISPgrandTotal;
														//echo "<pre>";
														//print_r($record['serviceName']);

										if (isset($finalArray[$record['serviceLocationName']][$record['serviceName']][$method->getName()]['totalAmountPaymentMethod']))
											$finalArray[$record['serviceLocationName']][$record['serviceName']][$method->getName()]['totalAmountPaymentMethod'] += $record['totalAmount'];
										else
											$finalArray[$record['serviceLocationName']][$record['serviceName']][$method->getName()]['totalAmountPaymentMethod'] = $record['totalAmount'];

										if (isset($finalArrayChart[$date[0]][$date[1]][$record['serviceLocationName']]))
											$finalArrayChart[$date[0]][$date[1]][$record['serviceLocationName']] += $record['totalAmount'];
										else
											$finalArrayChart[$date[0]][$date[1]][$record['serviceLocationName']] = $record['totalAmount'];
										//$finalArrayChart[$date[0]][$record['serviceLocationName']]  += $record['totalAmount'];
									}
                                                                }

							}
						}
					}
				}
			}
		}

		$dataResult = array();
		if (count($finalArray) > 0) {
			//echo count($finalArray);exit;
			//print_r($finalArray);exit;
			$dataResult['result'] = $finalArray;
			$dataResult['totalRecord'] = 13;
			$dataResult['grandTotal'] = $grandTotal;

                        $dataResult['servicegrandtotal'] = $ServicegrandTotal;
						//print_r( $dataResult['servicegrandtotal']);exit;
                        //$dataResult['grandISPserviceTotal'] = $ISPgrandTotal;
                        $dataResult['currentmonthsale'] = $grandTotal;
			$dataResult['chartServiceLocation'] = $finalArrayChart;

			return $dataResult;
		}

		return false;
	}

	public function getSalesReportChartData($flag, $flagPackage = false) {


		$finalArr = '';

		if ($flag == 'year' && $flagPackage == false) {
			$query = $this->getSalesQuery(null, null, null, null, true, $flagPackage);

			$result = $this->getSalesData($query->getQuery()->getArrayResult(), false);

			$finalArr = $this->chartData($result);
		} else if ($flag == 'year' && $flagPackage == true) {
			$query = $this->getSalesQuery(null, null, null, null, true, true);

			$finalArr = $this->chartDataPackageAndService($query->getQuery()->getArrayResult());
		} else {

			$query = $this->getSalesQuery(null, null, null, null, false, false);
			//print_r($query->getQuery()->getArrayResult()); exit;
			$result = $this->getSalesData($query->getQuery()->getArrayResult(), false);

			$finalArr = $this->chartData($result);
		}


		return $finalArr;
	}

	public function chartData($salesReportData) {

		$finalArr = array();
		$arrChart = array();
		$serviceLocation = array();
		$paymentMethod = array();
		$serviceLocationYears = array();
		$serviceLocationKey = array();

		if ($salesReportData) {

			foreach ($salesReportData['result'] as $keyLocation => $services) {

				foreach ($services as $keyService => $paymentMethods) {

					foreach ($paymentMethods as $key => $value) {

						if (isset($arrChart['ServiceLocation'][$keyLocation]))
							$arrChart['ServiceLocation'][$keyLocation] += $value['totalAmountPaymentMethod'];
						else
							$arrChart['ServiceLocation'][$keyLocation] = $value['totalAmountPaymentMethod'];

						if (isset($arrChart['PaymentMethod'][$key]))
							$arrChart['PaymentMethod'][$key] += $value['totalAmountPaymentMethod'];
						else
							$arrChart['PaymentMethod'][$key] = $value['totalAmountPaymentMethod'];
					}
				}
			}



			$tempServiceLocation = array();
			//$serviceLocation = array();

			foreach ($arrChart['ServiceLocation'] as $key => $value) {

				$tempServiceLocation['label'] = $key;
				$tempServiceLocation['data'] = $value;

				$serviceLocation [] = $tempServiceLocation;
			}


			$tempPaymentMethod = array();
			//$paymentMethod = array();

			foreach ($arrChart['PaymentMethod'] as $key => $value) {

				$tempPaymentMethod['label'] = $key;
				$tempPaymentMethod['data'] = $value;

				$paymentMethod [] = $tempPaymentMethod;
			}

			//$serviceLocationKey = array();
			$tempYearServiceLocation = array();


			foreach ($salesReportData['chartServiceLocation'] as $keyYear => $years) {

				foreach ($years as $keyMonth => $year) {

					foreach ($year as $keyLocation => $month) {

						$tempYearServiceLocation[$keyYear . '-' . $keyMonth] = $year;

						if (!in_array($keyLocation, $serviceLocationKey, true)) {

							$serviceLocationKey[] = $keyLocation;
						}
					}
				}
			}

			$arrServiceLocation = array();

			foreach ($tempYearServiceLocation as $keyYear => $records) {

				foreach ($records as $keyLocation => $record) {

					$arrServiceLocation['month'] = $keyYear;

					foreach ($serviceLocationKey as $location => $value) {

						if ($keyLocation == $value) {

							$arrServiceLocation[$value] = $record;
						} else {

							$arrServiceLocation[$value] = 0;
						}
					}

					$serviceLocationYears[] = $arrServiceLocation;
				}
			}
		}

		$finalArr['serviceLocation'] = json_encode($serviceLocation ? $serviceLocation : 0);
		$finalArr['paymentMethod'] = json_encode($paymentMethod ? $paymentMethod : 0);
		$finalArr['arrServiceLocation'] = json_encode($serviceLocationYears ? $serviceLocationYears : 0);
		$finalArr['serviceLocationKey'] = json_encode($serviceLocationKey ? $serviceLocationKey : 0);


//		$finalArr['serviceLocation'] = json_encode($serviceLocation);
//		$finalArr['paymentMethod'] = json_encode($paymentMethod);
//		$finalArr['arrServiceLocation'] = json_encode($serviceLocationYears);
//		$finalArr['serviceLocationKey'] = json_encode($serviceLocationKey);


		return $finalArr;
	}

	public function chartDataPackageAndService($salesReportData) {

		$finalArr = array();
		$arrChart = array();

		if ($salesReportData) {

			foreach ($salesReportData as $key => $records) {

				$arrChart[$records['serviceName']][$records['packageName']][$records['actualAmount']] = $records['totalActualAmount'];
			}


			$tempServicesPackage = array();
			$tempPackage = '';
			$service = '';

			foreach ($arrChart as $key => $recordPackage) {

				if ($key) {

					$service = $key;
					foreach ($recordPackage as $keyPackage => $record) {


						foreach ($record as $value) {

							if ($value > 0) {

								$tempPackage['label'] = $keyPackage;
								$tempPackage['data'] = $value;

								$tempServicesPackage[$key][] = $tempPackage;
							}
						}
					}

					if(isset($finalArr[$service])) {

						$finalArr[$service] = json_encode($tempServicesPackage[$key]);
					}
				}
			}
		}

		return $finalArr;
	}

	public function getAradialCustomerSessionHistoryQuery() {

		$query = $this->createQueryBuilder('po')
				->leftJoin('po.servicePurchases', 'sp')
				->Where('po.paymentStatus != :pstatus')
				->setParameter('pstatus', 'Completed')
				->andWhere('po.paymentStatus != :status')
				->setParameter('status', 'InProcess')
				->andWhere('po.paymentStatus != :sstatus')
				->setParameter('sstatus', 'PartiallyCompleted')
				
				->orderby('po.id', 'DESC');

		$result = $query->getQuery()->getResult();

		return $result;
	}
        
        
    public function checkPosOrderNoExist($orderNo){
        
        $query = $this->createQueryBuilder('po')
                ->Where('po.orderNumber = :orderNo')
                ->setParameter('orderNo',$orderNo);

		$result = $query->getQuery()->getArrayResult();
        return $result;
    }

    public function countPurchase($user_id){
    	$query = $this->createQueryBuilder('po')
    			->select('u.id', 'count(po.orderNumber) as totalPurchase')
    			->innerJoin('po.user', 'u')
                ->Where('u.id = :user')
                ->setParameter('user',$user_id)
                ->andWhere('po.paymentStatus = :status')
                ->setParameter('status', 'Completed')
                ->groupBy('u.id');

			$result = $query->getQuery()->getOneOrNullResult();
			if (!empty($result['totalPurchase'])) {
				return $result['totalPurchase'];
			}else{
				return 0;
			}
    }

    public function checkFirstPurchase($userId = ''){

        $query = $this->createQueryBuilder('po')
                ->select('po','sp.validity')
                ->leftJoin('po.servicePurchases', 'sp')
                ->where('po.user = :userId')
                ->andWhere('po.paymentStatus = :payStatus')
                ->setParameter('payStatus', 'Completed')
                ->setParameter('userId', $userId)
                ->andWhere('sp.isAddon = 0');
        
        $result = $query->getQuery()->getResult();
        if(count($result) > 0){
            return $result;
        }
        return false;
    }

    public function getSalesDetailsReportGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {

        $query = $this->getSalesDetailsReportQuery($searchData, $SearchType, $objHelper);
        
        $queryArr = $query->getQuery()->getArrayResult();
        $grandPayableAmountTotal = array_sum(array_map(function($item) { return $item['payableAmount']; }, $queryArr));
        
        $countData = count($queryArr);
        $query->setMaxResults($limit);
        $query->setFirstResult($offset);
        $query->orderBy($orderBy, $sortOrder);

        $result = $query->getQuery()->getArrayResult();
        $dataResult = array();

        if ($countData > 0) {

            $dataResult['result'] = $result;
            $dataResult['totalRecord'] = $countData;
            $dataResult['payableAmountGrandTotal'] = $grandPayableAmountTotal;
            
            return $dataResult;
        }
        return false;
    }

    public function getExportDataOfSalesDetails($objHelper, $searchData, $SearchType = 'ANDLIKE') {
        $query = $this->getSalesDetailsReportQuery($searchData, $SearchType, $objHelper);
        $result = $query->getQuery()->getArrayResult();
        if ($result) {
            return $result;
        }
        return false;
    }

    public function trim_sales_details_serach_data($searchData, $SearchType) {
        $QueryStr = array();
        if (!empty($searchData)) {
            if ($SearchType == 'ANDLIKE') {
                $i = 0;
                foreach ($searchData as $key => $val) {
                    if ($key == 'paymentMethod' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'pm.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';
                    }else if ($key == 'adminUser' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'pbu.username';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';

                    }else if ($key == 'bandwidth' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'sp.bandwidth';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';

                    }else if ($key == 'validity' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'sp.validity';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';

                    }else if ($key == 'customer' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'u.username';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    else if ($key == 'serviceLocation' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'sl.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';
                    }
                    else if ($key == 'purchasedFrom' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'wl.id';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';
                    }
                    $i++;
                }
            }
        }
        return $QueryStr;
    }

    public function getSalesDetailsReportQuery($searchData, $SearchType, $objHelper) {
        // Remove "NeedToRefund"
        $paymentStatus = array('Completed', 'Refunded', 'Expired', 'NeedToRefund');
        $data = $this->trim_sales_details_serach_data($searchData, $SearchType);
        $query = $this->createQueryBuilder('po')
            ->select('sl.name', 'po.createdAt', 'po.paymentBy', 'po.orderNumber', 'pm.name as paymentMethod', 'pbu.username as paymentByUser', "GROUP_CONCAT(s.name) as service", 'u.id as user_id', 'u.username', 'GROUP_CONCAT(sp.packageName) as packageName', 'GROUP_CONCAT(sp.bandwidth) as bandwidth', 'GROUP_CONCAT(sp.validity) as validity', 'GROUP_CONCAT(sp.validityType) as validityType', 'GROUP_CONCAT(sp.rechargeStatus) as rechargeStatus',  'SUM(sp.actualAmount) as actualAmount', 'sum(sp.payableAmount) as payableAmount','GROUP_CONCAT(distinct sp.purchase_type) as purchaseType', 'GROUP_CONCAT(distinct sp.display_bundle_name) as displayBundleName','wl.companyName')
            ->innerJoin('po.servicePurchases', 'sp')
            ->innerJoin('sp.service_location_id', 'sl')
            ->innerJoin('sp.service', 's')
            ->innerJoin('po.paymentMethod', 'pm')
            ->innerJoin('po.user', 'u')
            ->leftJoin('sp.whiteLabel', 'wl')
            ->leftJoin('po.paymentByUser', 'pbu')
            ->where('sp.paymentStatus IN (:paymentStatus)')
            ->setParameter('paymentStatus', $paymentStatus)
            ->andWhere('pm.showInSalesReport = :showInSalesReport')
            ->setParameter('showInSalesReport', 1)
            ->andWhere('po.paymentStatus IN (:postatus)')
            ->setParameter('postatus', $paymentStatus)
            ->groupBy('po.id', 'sp.isAddon');

        $slQuery = '';

        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data);
        }

        if ($likeStr) {
            $query->andWhere($likeStr);
        }

        if (!empty($searchData) && isset($searchData['createdDate'])) {
            $RequestDate = explode('~', $searchData['createdDate']);
            $ReqFrom = trim($RequestDate[0]);
            $ReqTo = trim($RequestDate[1]);

            if (isset($RequestDate) && !empty($RequestDate)) {
                if ($ReqFrom) {
                    $startDate = new \DateTime($ReqFrom);
                    $query->andWhere('po.createdAt >= :startdatetime');
                    $query->setParameter('startdatetime', $startDate->format('Y-m-d 00:00:00'));
                }
                if ($ReqTo) {
                    $startDate = new \DateTime($ReqTo);
                    $query->andWhere('po.createdAt <= :enddatetime');
                    $query->setParameter('enddatetime', $startDate->format('Y-m-d 23:59:59'));
                }
            }
        }

        if(!empty($searchData) && isset($searchData['serviceType'])){
            $service = $searchData['serviceType'];
            if (strtoupper($service) == "TVOD") {
                $query->andWhere('s.name = :service')->setParameter('service',$service);
                $query->andWhere('sp.purchase_type = :isTvod')->setParameter('isTvod' , "TVOD");
            }else if ($service == "BUNDLE") {
                $query->andWhere('sp.purchase_type = :isBundle')->setParameter('isBundle' , "BUNDLE");
            }else{
                $query->andWhere('s.name = :service')->setParameter('service',$service);
                $query->andWhere('sp.purchase_type IS NULL');
            }
        }

        return $query;
    }

    public function getActiveByServiceLocationnew($locationID,$service,$paymentMethodName,$packageName,$searchData,$purchasedFrom){
        // Remove "NeedToRefund"       
        $paymentStatus = array('Completed', 'Refunded', 'Expired', 'NeedToRefund');	

		$query = $this->createQueryBuilder('po')
				->select('pm.name as paymentMethod', 'pm.code as paymentMethodCode', 'sp.paypalCredential', 's.name as serviceName', 'sp.purchase_type','sl.name as locationName', 'SUM(sp.payableAmount) as totalAmount')
                // ->addSelect('SUM(CASE WHEN us.isExtendedSaparately <> 1 AND us.isExtend = 1 THEN us.payableAmount ELSE sp.payableAmount END) as totalAmount')
				// ->leftJoin('po.purchaseOrder', 'po')
				->innerJoin('po.servicePurchases', 'sp')
				->innerJoin('sp.service', 's')
                                ->innerJoin('sp.service_location_id', 'sl')
				->innerJoin('po.paymentMethod', 'pm')
                                ->leftJoin('sp.whiteLabel', 'wl')
				->andWhere('pm.showInSalesReport = :showInSalesReport')
				->setParameter('showInSalesReport', 1)
                ->andWhere('sp.paymentStatus IN (:ppaystatus)')
				->setParameter('ppaystatus', $paymentStatus)
				->andWhere('po.paymentStatus IN (:postatus)')
				->setParameter('postatus', $paymentStatus)
                ->andWhere('sl.id = :locationId')
				->setParameter('locationId', $locationID)
				->groupBy('s.id')
				->addGroupBy('pm.id')->addGroupBy('sp.purchase_type')->addGroupBy('sp.paypalCredential');

		if(!empty($service)){
			if (strtoupper($service) == "TVOD") {
                $query->andWhere('s.name = :service')->setParameter('service',$service);
                $query->andWhere('sp.purchase_type = :isTvod')->setParameter('isTvod' , "TVOD");
            }else if ($service == "BUNDLE") {
                $query->andWhere('sp.purchase_type = :isBundle')->setParameter('isBundle' , "BUNDLE");
            }else{
                $query->andWhere('s.name = :service')->setParameter('service',$service);
                $query->andWhere('sp.purchase_type IS NULL');
            }
		}

		if(!empty($paymentMethodName)){
			$query->andWhere('pm.name = :payment')
					->setParameter('payment',$paymentMethodName);
		}

		if(!empty($packageName)){
			$query->andWhere('sp.packageId = :package OR sp.bundle_id = :bundle')->setParameter('package',$packageName)->setParameter('bundle',$packageName);
		}
                
                if(!empty($purchasedFrom)){
                    $query->andWhere('wl.id = :whiteLabelid')->setParameter('whiteLabelid',$purchasedFrom);
                }

		if(!empty($searchData['serviceType'])){

			$RequestDate = explode('~', $searchData['serviceType']);
				$ReqFrom = trim($RequestDate[0]);
				$ReqTo = trim($RequestDate[1]);

				if ($ReqFrom != "") {
					$startDate = new \DateTime($ReqFrom);
					$query->andWhere('po.createdAt >= :today_startdatetime');
					$query->setParameter('today_startdatetime', $startDate->format('Y-m-d 00:00:00'));
				}
				if ($ReqTo != "") {
					$endDate = new \DateTime($ReqTo);
					$query->andWhere('po.createdAt <= :today_enddatetime');
					$query->setParameter('today_enddatetime', $endDate->format('Y-m-d 23:59:59'));

			}
		}

		$result = $query->getQuery()->getArrayResult();

		return $result;

	}

    public function countTotalPurchase($userId){
    	$query = $this->createQueryBuilder('po')
    			->select('count(po.orderNumber) as totalPurchase')
    			->innerJoin('po.user', 'u')
                ->Where('u.id = :user')
                ->setParameter('user',$userId)
                ->andWhere('po.paymentStatus IN (:status)')
                ->setParameter('status', array('Completed', 'Refunded', 'Expired'))
                ->groupBy('u.id');

			$result = $query->getQuery()->getOneOrNullResult();
			if (!empty($result['totalPurchase'])) {
				return $result['totalPurchase'];
			}else{
				return 0;
			}
    }
}