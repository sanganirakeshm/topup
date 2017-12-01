<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\ServiceBundle\Entity\Package;

class PackageInactiveCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('dhi:inactive-packages')
        ->setDescription('Inactive expired packages from custome account');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Package Inactive Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();

        $objUserService = $em->getRepository('DhiUserBundle:UserService')->getExpiredPackage();
        
        $todayDatetime = new \DateTime();
        
        if($objUserService){
            
            foreach ($objUserService as $activeService){
            	
            	$inActiveSuccess = false;
            	$isCheckInactiveService = true;
            	
            	if($activeService->getService()) {
            		
            		$serviceName   = $activeService->getService()->getName();
            		$userName      = $activeService->getUser()->getUserName();
            		$packageId 	   = $activeService->getPackageId();
            		$userId    	   = $activeService->getUser()->getId();
            		$packageName   = $activeService->getPackageName();
            		$purchaseOrder = $activeService->getPurchaseOrder();
            		$expiryDate    = $activeService->getExpiryDate();
            		
            		if($purchaseOrder) {
            			
            			$objPaypalRecurringProfile = $purchaseOrder->getPaypalRecurringProfile();
            			
            			if ($objPaypalRecurringProfile) {
            				
            				$dbNextBillingDate 	= $objPaypalRecurringProfile->getNextBillingDate();
            				$dbFinalDueDate 	= $objPaypalRecurringProfile->getFinalDueDate();
            				$recurringProfileId = $objPaypalRecurringProfile->getProfileId();
            				$profileStatus 		= $objPaypalRecurringProfile->getProfileStatus();
            				$numCompletedCycle 	= $objPaypalRecurringProfile->getNumCompletedCycle();
            				$numRemainingCycle 	= $objPaypalRecurringProfile->getNumRemainingCycle();
            				
            				if ($profileStatus != 'Active' && $expiryDate->format('Y-m-d') < $todayDatetime->format('Y-m-d')) {
            					
            					$isCheckInactiveService = true;
            				} else {
            					
            					$isCheckInactiveService = false;
            				}            				            				            				            				   	
            			}            			
            		}
            		
            		if ($isCheckInactiveService) {
            			
	            		//Inactive IPTV Package
	            		if(strtoupper($serviceName) == 'IPTV') {
	            			
			                $wsParam = array();
			                $wsParam['cuLogin'] = $userName;
			                $wsParam['offer']   = $packageId;
			                
			                $selevisionService = $this->getContainer()->get('selevisionService');
			                $wsResponse = $selevisionService->callWSAction('unsetCustomerOffer',$wsParam);
			                
			                if(isset($wsResponse['status']) && !empty($wsResponse['status'])){
			                
			                    if($wsResponse['status'] == 1){
			                                         
			                    	$inActiveSuccess = true;
			                    }
			                }
	            		}
            		
	            		//Inactive ISP package
	            		if(strtoupper($serviceName) == 'ISP') {
	            			
	            			$cancelUserResponse = $this->getContainer()->get('aradial')->deleteUserFromAradial($userName);
	            			
	            			if($cancelUserResponse['status'] == 1) {
	            				
	            				$inActiveSuccess = true;
	            			}
	            		}
            		
	            		if($inActiveSuccess) {
	            			
	            			$activeService->setStatus(0);
	            			$em->persist($activeService);
	            			$em->flush();
	            			 
	            			$output->writeln("\n####### UserId: ".$userId." AND PackageName: ".$packageName." has been inactive #######\n");
	            		}	
            		}	
            	}
            }
        }else{
            
            // $output->writeln("\n####### Expired package not found. #######\n");
        }
        
        $output->writeln("\n####### End Cron #######\n");                        
    }
    
    public function extendService($service,$activationDate,$expiryDate) {
    	
    	if ($service->getService()) {
    		
    		if (strtoupper($service->getService()->getName()) == 'ISP') {
    			
		    	$extraParam = array();
		    	$extraParam['db_$N$Users.Offer'] = $service->getPackageId();
		    	$extraParam['db_$D$Users.StartDate'] = $activationDate->format('m/d/Y H:i:s');
		    	$extraParam['db_$D$Users.UserExpiryDate'] = $expiryDate->format('m/d/Y H:i:s');
		    	$aradialResponse = $this->getContainer()->get('aradial')->createUser($service->getUser(),$extraParam);
		    	
		    	if($aradialResponse == 1) {
		    		
		    		return true;
		    	} 
    		}
    		
    		if(strtoupper($service->getService()->getName()) == 'IPTV') {
    			
    			return true;
    		}
    	}
    	return false;
    }
}
