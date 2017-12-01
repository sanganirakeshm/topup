<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * SolarWindsRequestTypeRepository $createdBy
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class SolarWindsRequestTypeRepository extends EntityRepository {
    public function getdeletedSolarWindsdata($solarwindIds){
        
        $query = $this->createQueryBuilder('swr')
                 ->select('swr.id as requesttypeid','so.id as mapId','swr.requestTypeName','u.id as userId','u.username')
                 ->leftJoin('swr.solarwindsSupportLocation', 'so')
                 ->leftJoin('so.createdBy', 'u');
        $query->where($query->expr()->notIn('swr.solarWindId', $solarwindIds));
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }
}