<?php

namespace Dhi\UserBundle\Repository;
use Doctrine\ORM\EntityRepository;

/**
 * UserMacAddressRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserMacAddressRepository extends EntityRepository
{
	
	public function checkCustomerMacAddress($userId) {
        
		//$another = $searchedData
		
		
		
        $query = $this->createQueryBuilder('um')
				->leftJoin('um.user', 'u')
				
				//->select('u.username')
				->where('u.id = :uid')
				->setParameter('uid', $userId);
		
		 
		
		 
        return count($query->getQuery()->getArrayResult());
    }
	
	public function getCustomerNameByMacAddress($userMacAddress) {
        
        $query = $this->createQueryBuilder('um')
				->leftJoin('um.user', 'u')
				//->select('u.username')
				->where('um.macAddress = :macAddress')
				->setParameter('macAddress', $userMacAddress);
		
		 
        return $query->getQuery()->getResult();
    }
	
}
