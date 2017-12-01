<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * CustomerCompensationLogRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerCompensationLogRepository extends EntityRepository
{

	public function getPendingUserServices($condition){
		$query = $this->createQueryBuilder('ccl');
		
		if (!empty($condition['status'])) {
			$query
				->where("ccl.status = :status")
				->setParameter("status", $condition['status']);
		}

		if (!empty($condition['compensation'])) {
			$query
				->andWhere("ccl.compensation = :compensation")
				->setParameter("compensation", $condition['compensation']);
		}

		$query->setMaxResults(300)->setFirstResult(0);
		return $query->getQuery()->getResult();
	}

	public function getCompServices($condition){
		$query = $this->createQueryBuilder('ccl')
			->select('COUNT(ccl.id)');

		if (!empty($condition['id'])) {
			$query
				->andWhere("ccl.id = :id")
				->setParameter("id", $condition['id']);
		}

		if (!empty($condition['compensation'])) {
			$query
				->andWhere("ccl.compensation = :compensation")
				->setParameter("compensation", $condition['compensation']);
		}

		if (!empty($condition['status'])) {
			$query
				->andWhere("ccl.status = :status")
				->setParameter("status", $condition['status']);
		}

		if (!empty($condition['activeService'])) {
			$query
				->innerJoin("ccl.user", "u")
				->innerJoin("ccl.userService", "us")
				->andWhere('u.locked = :locked')
				->setParameter('locked', 0)
				->andWhere('u.isDeleted = :deleted')
				->setParameter('deleted', 0)
				->andWhere('us.status = :usStatus')
				->setParameter('usStatus', 1)
				->andWhere('us.expiryDate > :expiryDate')
				->setParameter('expiryDate', new \DateTime('now'));
		}

		return $query->getQuery()->getSingleScalarResult();
	}
}