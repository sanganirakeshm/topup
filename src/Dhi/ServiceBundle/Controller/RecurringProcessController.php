<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Dhi\ServiceBundle\Model\ExpressCheckout;
use Dhi\ServiceBundle\Entity\PaypalRecurringProfile;

class RecurringProcessController extends Controller
{
    protected $container;
    protected $em;


    public function __construct($container) {
    
        $this->container = $container;
        $this->securitycontext   = $container->get('security.context');
        $this->em                = $container->get('doctrine')->getManager();
        
    }
    
    public function updateRecurringProfileData($profileId) {
    	 
    	$recurringProfileDetail = $this->getRecurringProfileDetail($profileId); //Call to get recurring profile detail
    
    	if ($recurringProfileDetail) {
    
    		if(strtoupper($recurringProfileDetail["ACK"]) == "SUCCESS" || strtoupper($recurringProfileDetail["ACK"]) == "SUCCESSWITHWARNING") {
    
    			$objPaypalRecurringProfile = $this->em->getRepository('DhiServiceBundle:PaypalRecurringProfile')->findOneBy(array('profileId' => $profileId));
    			 
    			if(!$objPaypalRecurringProfile) {
    				 
    				$objPaypalRecurringProfile = new PaypalRecurringProfile();
    			}
    			 
    			$nextBillingDate = new \DateTime($recurringProfileDetail['NEXTBILLINGDATE']);
    			$profileStartDate = new \DateTime($recurringProfileDetail['PROFILESTARTDATE']);
    			$lastDueDate = new \DateTime($recurringProfileDetail['FINALPAYMENTDUEDATE']);
    			 
    			 
    			$objPaypalRecurringProfile->setProfileId($recurringProfileDetail['PROFILEID']);
    			$objPaypalRecurringProfile->setProfileStatus($recurringProfileDetail['STATUS']);
    			$objPaypalRecurringProfile->setProfileStartDate($profileStartDate);
    			$objPaypalRecurringProfile->setNextBillingDate($nextBillingDate);
    			$objPaypalRecurringProfile->setFinalDueDate($lastDueDate);
    			$objPaypalRecurringProfile->setNumCompletedCycle($recurringProfileDetail['NUMCYCLESCOMPLETED']);
    			$objPaypalRecurringProfile->setNumRemainingCycle($recurringProfileDetail['NUMCYCLESREMAINING']);
    			$objPaypalRecurringProfile->setAck($recurringProfileDetail['ACK']);
    			 
    			$this->em->persist($objPaypalRecurringProfile);
    			$this->em->flush();
    			 
    			return $objPaypalRecurringProfile;
    		}
    	}
    	 
    	return false;
    }
    
    public function getRecurringProfileDetail($profileId) {
    	
    	$recurringProfileDetail = array();
    
    	$configPaypal = array(
    
    			'METHOD'=> 'GetRecurringPaymentsProfileDetails',
    	);
    
    	$express = new ExpressCheckout($configPaypal,$this->container); //Paypal Express Checkout object
    	$recurringProfileDetail = $express->getRecurringProfileData($profileId); //Call to get recurring profile detail
    
    	return $recurringProfileDetail;
    }
    
    
}
