<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

use Dhi\UserBundle\Entity\DeactivateWithOutMacUserServiceLog;


class ActivateMacUsersServiceCommand extends ContainerAwareCommand{
	
    private $output;
    protected function configure(){

        $this->setName('dhi:activate-mac-users-service')->setDescription('activate user from selevision if mac address available in account.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("\n####### Start activate user cron on ".date('M j H:i')." #######\n");

        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $deactivateServiceLogs = $em->getRepository('DhiUserBundle:DeactivateWithOutMacUserServiceLog')->findBy(array('isActivated' => 0));
        
        if($deactivateServiceLogs) {
        	
        	foreach ($deactivateServiceLogs as $deactiveServiceLog) {
        		
        		if ($deactiveServiceLog->getUser()) {
        			
        			if ( count($deactiveServiceLog->getUser()->getUserMacAddress()) > 0 ) {
        				
        				$wsParam = array();
        				$wsParam['cuLogin'] = $deactiveServiceLog->getUser()->getUserName();
        				
        				$activeCustomerResponse = $this->getContainer()->get('selevisionService')->callWSAction('reactivateCustomer', $wsParam);
        				
        				if ($activeCustomerResponse['status'] == 1) {
        					
        					//Update service status 
        					$userService = $deactiveServiceLog->getUserService();
        					$userService->setStatus(1);
        					$em->persist($userService);
        					
        					
        					$deactiveServiceLog->setIsActivated(1);
        					$em->persist($deactiveServiceLog);
        					
        					$em->flush();
        					 
        					
        					$output->writeln("\n####### UserId: ".$deactiveServiceLog->getUser()->getId()." activated successfully in selevision #######\n");
        				} else {
        					
        					$output->writeln("\n####### UserId: ".$deactiveServiceLog->getUser()->getId()." activated failed in selevision #######\n");
        				}
        			}
        		}
        	}
            
        } else {
        	
            $output->writeln("\n####### Without MAC deactivate service log data not availble #######\n");
        }

        $output->writeln("\n####### End Cron #######\n");
    }
}
