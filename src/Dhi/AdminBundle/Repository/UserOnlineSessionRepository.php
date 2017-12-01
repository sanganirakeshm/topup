<?php

namespace Dhi\AdminBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Dhi\AdminBundle\Repository\UserOnlineSessionHistoryRepository;
/**
 * UserSessionHistory Repository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserOnlineSessionRepository extends EntityRepository
{
	public function getUserOnlineSession($userOnlineSessionLists){

		$em = $this->getEntityManager();

		if ($userSessionLists) {

			foreach ($userSessionLists as $user) {

				$userName	= $user['UserID'];
				$nasName	= $user['NASName'];
				$startTime	    = $user['InTime'];
				$stopTime = $user['TimeOnline'];
				$framedAddress 	= $user['FramedAddress'];
				$callerId 		= $user['CallerId'];
				$calledId 	= $user['CalledId'];


				$query = $this->createQueryBuilder('ush')
						 ->where('ush.userName = :name')
						->setParameter('name', $userName);

				$result = $query->getQuery()->getArrayResult();

				$countData = count($result);

				if($countData == 0){

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


	 public function getAradialOnlineUserGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper) {

        $data = $this->trim_aradial_search_data($searchData, $SearchType);
		//$startDate = new \DateTime($ReqFrom);
        $query = $this->createQueryBuilder('uos')
            ->select('uos.userName', 'uos.nasName', 'uos.onlineSince', 'uos.timeOnline', 'uos.userIp', 'uos.nasId', 'uos.nasPort', 'uos.accountSessionId')
            ->where("uos.isOffline = :isOffline")
            ->setParameter("isOffline", "n");

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
        $countQuery->select("count(uos.id) as totalRecords");
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

                        $QueryStr[$i]['Field'] = 'uos.userName';
                        $QueryStr[$i]['Value'] = $val;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }

					  if ($key == 'NasName' && !empty($val)) {

                        $QueryStr[$i]['Field'] = 'uos.nasName';
                        $QueryStr[$i]['Value'] = $val ;
                        $QueryStr[$i]['Operator'] = 'LIKE';
                    }
					 
                    $i++;
                }
            } else {

            }
        }
        return $QueryStr;
    }

}