<?php

namespace Dhi\ServiceBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * RecurringPaymentLogRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RecurringPaymentLogRepository extends EntityRepository
{
	
	public function getRecurringHistoryGrid($limit = 0, $offset = 10, $order_by = "id", $sort_order = "asc", $searchData, $SearchType, $objHelper, $recurringProfileId = "", $user = "") {
	
		$data = $this->trim_serach_data_recurring_history($searchData, $SearchType);
		
		$query = $this->createQueryBuilder('rp')
		                ->leftJoin('rp.paypalRecurringProfile', 'prp')          
		                ->leftJoin('prp.purchaseOrder', 'po')
		                ->orderBy('rp.id', 'desc');
        
        
        if ($SearchType == 'ORLIKE') {

            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {

            $likeStr = $objHelper->andLikeSearch($data);
        }
        
        if ($likeStr) {

            $query->andWhere($likeStr);
        }
        
        if ($recurringProfileId) {
        	
        	$query->andWhere('rp.paypalRecurringProfile = :paypalRecurringProfile')->setParameter('paypalRecurringProfile', $recurringProfileId);
        }
        
        if ($user) {
        
        	$query->andWhere('po.user = :userId')->setParameter('userId', $user->getId());
        }
	
		$query->orderBy($order_by, $sort_order);
	
		$countData = count($query->getQuery()->getResult());
	
		$query->setMaxResults($limit);
		$query->setFirstResult($offset);
	
		$result = $query->getQuery()->getResult();
		//        echo $query->getQuery()->getSQL();die();
	
		$dataResult = array();
		if (count($result) > 0) {
	
			$dataResult['result'] = $result;
			$dataResult['totalRecord'] = $countData;
	
			return $dataResult;
		}
	
		return false;
	}
	
	public function trim_serach_data_recurring_history($searchData, $SearchType) {
		
		$QueryStr = array();
	
		if (!empty($searchData)) {
	
			if ($SearchType == 'ANDLIKE') {
	
				$i = 0;
				foreach ($searchData as $key => $val) {
	
					if ($key == 'profileId' && !empty($val)) {
	
						$QueryStr[$i]['Field'] = 'rp.profileId';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					
					if ($key == 'profileStatus' && !empty($val)) {
					
						$QueryStr[$i]['Field'] = 'rp.profileStatus';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					
					if ($key == 'paymentReceviedDate' && !empty($val)) {
					
						$QueryStr[$i]['Field'] = 'rp.billingDate';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					
					if ($key == 'nextBillingDate' && !empty($val)) {
					
						$QueryStr[$i]['Field'] = 'rp.nextBillingDate';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
	
					if ($key == 'finalDueDate' && !empty($val)) {
							
						$QueryStr[$i]['Field'] = 'rp.finalDueDate';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					
					if ($key == 'amount' && !empty($val)) {
							
						$QueryStr[$i]['Field'] = 'rp.amount';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					
					if ($key == 'completedCycle' && !empty($val)) {
							
						$QueryStr[$i]['Field'] = 'rp.numCompletedCycle';
						$QueryStr[$i]['Value'] = $val;
						$QueryStr[$i]['Operator'] = 'LIKE';
					}
					
					if ($key == 'remainingCycle' && !empty($val)) {
							
						$QueryStr[$i]['Field'] = 'rp.numRemainingCycle';
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