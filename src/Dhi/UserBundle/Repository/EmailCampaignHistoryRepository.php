<?php

namespace Dhi\UserBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * EmailCampaignHistoryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EmailCampaignHistoryRepository extends EntityRepository
{
    
    public function getEmailCampaignHistoryGridList($limit = 0, $offset = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $campaignId) {
        
        $data = $this->trim_serach_data($searchData, $SearchType);
        
        $query = $this->createQueryBuilder('eh')
                ->select('eh')
                ->leftJoin('eh.user', 'u')
                ->leftJoin('eh.emailCampaign', 'e')
                ->leftJoin('u.userServiceLocation','sl')
                ->where('eh.emailCampaign = :campaignId')
                ->setParameter('campaignId', $campaignId);
               
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
       
        $countData = count($query->getQuery()->getArrayResult());
           
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

                    if ($key == 'SentAt' && !empty($val)) {
                        $date = explode('~', $val);
                        $fromDate = trim($date[0]);
                        $toDate = trim($date[1]);

                        if ($fromDate != "") {
                            $startDate = new \DateTime($fromDate);
                            $QueryStr[$i]['Field'] = 'eh.createdAt';
                            $QueryStr[$i]['Value'] = $startDate->format('Y-m-d 00:00:00');
                            $QueryStr[$i]['Operator'] = '>=';
                         }
                         if ($toDate != "") {
                            $i++;
                            $endDate = new \DateTime($toDate);
                            $QueryStr[$i]['Field'] = 'eh.createdAt';
                            $QueryStr[$i]['Value'] = $endDate->format('Y-m-d 23:59:59');
                            $QueryStr[$i]['Operator'] = '<=';
                         }
                    }
                    $i++;
                }
            } else {
                
            }
        }
        return $QueryStr;
    }
}
