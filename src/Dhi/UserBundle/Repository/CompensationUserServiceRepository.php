<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CompensationUserServiceRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CompensationUserServiceRepository extends EntityRepository
{

	public function getNumberOfCompedPlans($compensationId) {
		$query = $this->createQueryBuilder('cus')
            ->select("COUNT(cus.id)")
            ->innerJoin("cus.compensation", "c")
            ->where("c.id = :compensation_id")
            ->setParameter("compensation_id", $compensationId);
		
		$total = $query->getQuery()->getSingleScalarResult();        
		return $total;
	}

	public function getExistingCompedPurchaseOrder($compensationId, $userId){
		$query = $this->createQueryBuilder('cus')
            ->select("po.orderNumber")
            ->innerJoin("cus.compensation", "c")
            ->innerJoin("cus.purchaseOrder", "po")
            ->innerJoin("po.user", "u")
            ->where("c.id = :compensation_id")
            ->setParameter("compensation_id", $compensationId)
            ->andWhere("u.id = :user_id")
            ->setParameter("user_id", $userId);
		
		$result = $query->getQuery()->getOneOrNullResult();
		if (!empty($result['orderNumber'])) {
			return $result['orderNumber'];
		}
		return null;
	}
}