<?php

namespace Dhi\AdminBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * ServiceLocationWiseSiteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class ServiceLocationWiseSiteRepository extends EntityRepository {

    public function getLocationWiseSiteGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {
        $data = $this->trim_serach_data($searchData, $SearchType);
        $query = $this->createQueryBuilder('ls')
                ->select('ls.id, sl.name AS serviceLocationName, wl.companyName, wl.domain, ls.createdAt, ls.updatedAt')
                ->leftJoin('ls.serviceLocation', 'sl')
                ->leftJoin('ls.whiteLabel', 'wl')
                ->where('ls.isDeleted = :isdeleted')
                ->setParameter('isdeleted', 0);


        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
        } else if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data);
        }

        if ($likeStr) {
            $query->andWhere($likeStr);
        }

        $query->orderBy($orderBy, $sortOrder);

        $countQuery   = clone $query;
        $countQuery->select("count(wl.id) as totalRecords");
        $objCountData = $countQuery->getQuery()->getOneOrNullResult();
        $countData    = $objCountData['totalRecords'];

        $query->setMaxResults($limit);
        $query->setFirstResult($offset);
        $result = $query->getQuery()->getArrayResult();

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
                    if ($key == 'ServiceLocation' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'sl.name';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = '=';
                    }
                    if ($key == 'CompanyName' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'wl.companyName';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    if ($key == 'CompanyDomainName' && !empty($val)) {
                        $QueryStr[$i]['Field'] = 'wl.domain';
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
