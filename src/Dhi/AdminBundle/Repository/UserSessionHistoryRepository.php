<?php

namespace Dhi\AdminBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Dhi\AdminBundle\Repository\UserSessionHistoryRepository;

/**
 * UserSessionHistory Repository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserSessionHistoryRepository extends EntityRepository {

    public function getUserSessionHistory($userSessionLists) {

        $em = $this->getEntityManager();

        if ($userSessionLists) {

            foreach ($userSessionLists as $user) {

                $userName = $user['UserID'];
                $nasName = $user['NASName'];
                $startTime = $user['InTime'];
                $stopTime = $user['TimeOnline'];
                $framedAddress = $user['FramedAddress'];
                $callerId = $user['CallerId'];
                $calledId = $user['CalledId'];


                $query = $this->createQueryBuilder('ush')
                        ->where('ush.userName = :name')
                        ->setParameter('name', $userName);

                $result = $query->getQuery()->getArrayResult();

                $countData = count($result);

                if ($countData == 0) {

                    $objUserSession = new UserSessionHistory();
                    $objUserSession->setUserName($userName);
                    $objUserSession->setNasName($nasName);
                    $objUserSession->setStartTime($startTime);
                    $objUserSession->setStopTime($stopTime);
                    $objUserSession->setCallerId($callerId);
                    $objUserSession->setCalledId($calledId);
                    $objUserSession->setFramedAddress($framedAddress);
                    $em->persist($objUserSession);
                    $em->flush();
                }
            }
        }
    }

    public function getAradialUserHistoryGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {

        $data = $this->trim_aradial_search_data($searchData, $SearchType);
        //$startDate = new \DateTime($ReqFrom);
        $query = $this->createQueryBuilder('ush')
            ->select("ush.startDateTime", "ush.stopDateTime", "ush.userName", "ush.email", "ush.nasName", "ush.callerId", "ush.calledId", "ush.framedAddress");
        $query->andWhere('ush.startTime >= :today_startdatetime');
        $query->setParameter('today_startdatetime', date('m/d/Y', strtotime('-30 days')));


        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
        }
        if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data);
        }

        if ($likeStr) {
            $query->andWhere($likeStr);
        }

        $query->orderBy($orderBy, $sortOrder);
        $countQuery = clone $query;
        $countQuery->select("count(ush.id) as totalRecords");
        $objCountData = $countQuery->getQuery()->getOneOrNullResult();
        $countData = $objCountData['totalRecords'];

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

    public function trim_aradial_search_data($searchData, $SearchType) {

        $QueryStr = array();

        if (!empty($searchData)) {

            if ($SearchType == 'ANDLIKE') {

                $i = 0;
                foreach ($searchData as $key => $val) {

                    if ($key == 'UserName' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'ush.userName';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

										if ($key == 'Email' && !empty($val)) {
											$QueryStr[$i]['Field'] = 'ush.email';
											$QueryStr[$i]['Value'] = $val;
											$QueryStr[$i]['Operator'] = 'LIKE';
										}

                    if ($key == 'NasName' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'ush.nasName';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    if ($key == 'CallerId' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'ush.callerId';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    if ($key == 'CalledId' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'ush.calledId';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
                    if ($key == 'FramedAddress' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'ush.framedAddress';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

                    $i++;
                }
            } else {

            }
        }
        return $QueryStr;
    }

    public function getAradialUserHistoryQuery() {

        $query = $this->createQueryBuilder('ush')->select("ush.startTime", "ush.stopTime", "ush.userName", "ush.email", "ush.nasName", "ush.callerId", "ush.calledId", "ush.framedAddress");
        $query->andWhere('ush.startTime >= :today_startdatetime');
        $query->setParameter('today_startdatetime', date('m/d/Y', strtotime('-30 days')));
        return $query;
    }

    public function getAradialUserHistory($slot = array()) {

        $query = $this->getAradialUserHistoryQuery();
        if (!empty($slot)) {
            $query->setMaxResults($slot['limit']);
            $query->setFirstResult($slot['offset']);
        }
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }

    public function getSearchAradialUserHistory($searchData, $slot = array()) {

        $query = $this->getAradialUserHistoryQuery();

        if (isset($searchData['UserName']) && $searchData['UserName'] != '') {
            $query->andWhere('ush.userName LIKE :UserName')
                    ->setParameter('UserName', '%' . $searchData['UserName'] . '%');
        }

        if (isset($searchData['NasName']) && $searchData['NasName'] != '') {

            $query->andWhere('ush.nasName LIKE :NasName')
                    ->setParameter('NasName', '%' . $searchData['NasName'] . '%');
        }
        if (isset($searchData['Email']) && $searchData['Email'] != '') {

            $query->andWhere('ush.email LIKE :Email')
                    ->setParameter('Email', '%' . $searchData['Email'] . '%');
        }

        if (isset($searchData['CallerId']) && $searchData['CallerId'] != '') {

            $query->andWhere('ush.callerId LIKE :CallerId');
            $query->setParameter('CallerId', '%' . $searchData['CallerId'] . '%');
        }

        if (isset($searchData['CalledId']) && $searchData['CalledId'] != '') {

            $query->andWhere('ush.calledId LIKE :CalledId');
            $query->setParameter('CalledId', '%' . $searchData['CalledId'] . '%');
        }

        if (isset($searchData['FramedAddress']) && $searchData['FramedAddress'] != '') {

            $query->andWhere('ush.framedAddress LIKE :FramedAddress');
            $query->setParameter('FramedAddress', '%' . $searchData['FramedAddress'] . '%');
        }

        if (!empty($slot)) {
            $query->setMaxResults($slot['limit']);
            $query->setFirstResult($slot['offset']);
        }

        $result = $query->getQuery()->getArrayResult();


        return $result;
    }

    public function getMaxDate(){
        $query = $this->createQueryBuilder('ush')->select("max(ush.startDateTime) as maxDate");
        $result = $query->getQuery()->getSingleScalarResult();
        return $result;
    }
}