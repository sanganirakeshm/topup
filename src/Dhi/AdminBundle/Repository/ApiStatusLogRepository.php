<?php

namespace Dhi\AdminBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ApiStatusLogRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ApiStatusLogRepository extends EntityRepository
{
	
	public function getApiStatusLog(){
		
		$qb = $this->createQueryBuilder('asl')
					->where('asl.apiStatus =:apiStatus')
					->setParameter('apiStatus', 0)
					->andWhere('asl.totalFailed >=:totalFailed')
					->setParameter('totalFailed', 4);
	
		$apiStatusLog = $qb->getQuery()->getResult();
	
		if($apiStatusLog){
	
			return $apiStatusLog;
		}
	
		return false;
	}
}