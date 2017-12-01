<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

use Dhi\UserBundle\Entity\DeactivateWithOutMacUserServiceLog;


class DeactivateWithOutMacUserCommand extends ContainerAwareCommand{
    private $output;
    protected function configure(){

        $this->setName('dhi:deactivate-without-mac-user')->setDescription('deactivate user from selevision if mac address not available in account.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("\n####### Start deactivate user cron on ".date('M j H:i')." #######\n");

        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $userServices = $em->getRepository('DhiUserBundle:UserService')->getWithoutMacActiveIPTVService();
        
        // new cron execute will not start until old will not be completed.
        if($userServices) {
        	
        	foreach ($userServices as $userService) {
        		
        		$isCheckedForInactive = false;
        		
        		$todayDateTime 	= new \DateTime();
        		$activationDate = $userService->getActivationDate();
        		
        		$interval = $activationDate->diff($todayDateTime);
        		$days =  $interval->format('%a');
        		
        		if ($days >= 3) {
        			
	        		if ($userService->getUser()) {
	        			
	        			if ( count($userService->getUser()->getUserMacAddress()) <= 0 ) {
	        				
	        				$wsParam = array();
	        				$wsParam['cuLogin'] = $userService->getUser()->getUserName();
	        				
	        				$deactiveCustomerResponse = $this->getContainer()->get('selevisionService')->callWSAction('deactivateCustomer', $wsParam);
	        				
	        				if ($deactiveCustomerResponse['status'] == 1) {
	        					
	        					// stored log
	        					$objDeactivateWithOutMacUserServiceLog = new DeactivateWithOutMacUserServiceLog();
	        					
	        					$objDeactivateWithOutMacUserServiceLog->setUser($userService->getUser());
	        					$objDeactivateWithOutMacUserServiceLog->setUserService($userService);
	        					$objDeactivateWithOutMacUserServiceLog->setServicePurchase($userService->getServicePurchase());
	        					$objDeactivateWithOutMacUserServiceLog->setService($userService->getService());
	        					$objDeactivateWithOutMacUserServiceLog->setPackageId($userService->getPackageId());
	        					$objDeactivateWithOutMacUserServiceLog->setPackageName($userService->getPackageName());
	        					
	        					$em->persist($objDeactivateWithOutMacUserServiceLog);
	        					
	        					//Update service status 
	        					//$userService->setStatus(0);
	        					$em->persist($userService);
	        					
	        					$em->flush();
	        					 
	        					
	        					$output->writeln("\n####### UserId: ".$userService->getUser()->getId()." deactivate successfully in selevision #######\n");
	        				} else {
	        					
	        					$output->writeln("\n####### UserId: ".$userService->getUser()->getId()." deactivate failed in selevision #######\n");
	        				}
	        			}
	        		}
        		}	
        	}
            
        } else {
        	
            $output->writeln("\n####### Without MAC IPTV service data not availble #######\n");
        }

        $output->writeln("\n####### End Cron #######\n");
    }
}
