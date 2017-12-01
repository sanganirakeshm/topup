<?php

namespace Dhi\AdminBundle\Repository;

use Doctrine\ORM\EntityRepository;


/**
 * UserSessionHistory Repository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserSuspendHistoryRepository extends EntityRepository {

    public function getSuspendedServices($userId) {
        $services = array();

        $qb = $this->createQueryBuilder('ush')
        ->where('ush.user = :userId')
        ->setParameter('userId', $userId)
        ->andWhere('ush.status = :status')
        ->setParameter('status', 0);

        $result = $qb->getQuery()->getResult();
        return $result;
     }

     public function getTotalSuspendedHours($userServiceId){
        $qb = $this->createQueryBuilder('ush')
            ->select("SUM(ush.suspendValidity)")
            ->where('ush.userService = :userServiceId')
            ->setParameter("userServiceId", $userServiceId);

        $validity = $qb->getQuery()->getSingleScalarResult();
        if (empty($validity)) {
            $validity = 0;
        }
        return $validity;
     }
}