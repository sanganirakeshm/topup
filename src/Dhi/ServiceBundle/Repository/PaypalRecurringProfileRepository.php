<?php

namespace Dhi\ServiceBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * PaypalRecurringProfileRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PaypalRecurringProfileRepository extends EntityRepository
{
	
	public function getNextbillDataForNotification() {
		
		$expdate = new \DateTime();
		$expdate->modify('+72 HOURS');
		
		$qb = $this->createQueryBuilder('pr')
					->select('pr')
					->where('pr.isSendNotification =:isSendNotification')
					->setParameter('isSendNotification', 0)
					->andWhere('pr.nextBillingDate >=:stNextBillingDate')
					->setParameter('stNextBillingDate', $expdate->format('Y-m-d 00:00:00'))
					->andWhere('pr.nextBillingDate <=:edNextBillingDate')
					->setParameter('edNextBillingDate', $expdate->format('Y-m-d 23:59:59'));							
					
		$expiredData = $qb->getQuery()->getResult();
		
		if($expiredData) {
			
			return $expiredData;
		}
	
		return false;
	}
	
	public function getAfterOneDayRecurringData() {
	
		$expdate = new \DateTime();
		$expdate->modify('+1 DAY');
	
		$qb = $this->createQueryBuilder('pr')
		->select('pr')
		->where('pr.isSendNotification =:isSendNotification')
		->setParameter('isSendNotification', 0)
		->andWhere('pr.nextBillingDate >=:stNextBillingDate')
		->setParameter('stNextBillingDate', $expdate->format('Y-m-d 00:00:00'))
		->andWhere('pr.nextBillingDate <=:edNextBillingDate')
		->setParameter('edNextBillingDate', $expdate->format('Y-m-d 23:59:59'));
			
		$expiredData = $qb->getQuery()->getResult();
	
		if($expiredData) {
				
			return $expiredData;
		}
	
		return false;
	}
}