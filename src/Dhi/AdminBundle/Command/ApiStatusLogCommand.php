<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

use Dhi\AdminBundle\Entity\ApiStatusLog;

class ApiStatusLogCommand extends ContainerAwareCommand{
	
    private $output;
    protected function configure(){

        $this->setName('dhi:api-status-log')->setDescription('Check api status working or not if not send mail to admin user.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("\n####### Start api status log Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        $apiService = array('Selevision','Aradial');
        
        $todayDateTime = new \DateTime();
        
        if ($apiService) {
        	
            foreach ($apiService as $api) {
            	
            	$apiStatus		= 1;
            	$totalFailed 	= 0;
            	$isCheckApiCall = false;
            	
            	$objApiStatusLog = $em->getRepository('DhiAdminBundle:ApiStatusLog')->findOneBy(array('apiName' => $api));
            	
            	if ($objApiStatusLog) {
            		
            		//Get minutes betwween current date and lastupdated date
            		$lastUpdatedDate = $objApiStatusLog->getCreatedAt(); 
            		if ($objApiStatusLog->getUpdatedAt()) {
            			
            			$lastUpdatedDate = $objApiStatusLog->getUpdatedAt();
            		}
         			
            		$interval 	= $todayDateTime->diff($lastUpdatedDate);
            		//$minutes	= $interval->format('%i');
            		
            		$minutes = $interval->days * 24 * 60;
            		$minutes += $interval->h * 60;
            		$minutes += $interval->i;
            		
            		echo $minutes;
            		if ($minutes >= 15) {
            		
            			$isCheckApiCall = true;
            		}
            		//End here            		
            	
            		$totalFailed = $objApiStatusLog->getTotalFailed();
            	} else {
            	
            		$objApiStatusLog = new ApiStatusLog();
            		$isCheckApiCall = true;
            	}
            	
            	//Call service api
            	if ($isCheckApiCall) {
            		
            		if ($api == 'Selevision') {
            			 
            			if (!$this->callSelevisionService()) {
            			
            				$apiStatus = 0;
            			}            			
            		}
            		            		 
            		if ($api == 'Aradial') {
            			 
            			if (!$this->callAradialService()) {
            				
            				$apiStatus = 0;
            			}            			
            		}
            		
            		if ($apiStatus == 1) {
            		
            			$totalFailed = 0;
            		} else {
            		
            			$totalFailed = $totalFailed + 1;
            		}
            		
            		$objApiStatusLog->setApiName($api);
            		$objApiStatusLog->setApiStatus($apiStatus);
            		$objApiStatusLog->setTotalFailed($totalFailed);
            		if ($totalFailed <= 3) {
            			 
            			$objApiStatusLog->setIsSendNotification(0);
            		}
            		 
            		$em->persist($objApiStatusLog);
            		$em->flush();
            	}
            	//End here	        	            	            	            	            	            	            	            	            	            	
            	
	        }
        }
        
        $objApiFailureEmail = $em->getRepository('DhiAdminBundle:ApiFailureEmail')->findBy(array('status' => 1));
        $apiStatusLogRecords = $em->getRepository('DhiAdminBundle:ApiStatusLog')->getApiStatusLog();        
        
        if($apiStatusLogRecords){
        	
        	foreach ($apiStatusLogRecords as $apiStatusLog) {
        		
        		foreach ($objApiFailureEmail as $apiFailureEmail) {		        
        			
        			$subject   = $apiStatusLog->getApiName().' Service Failure and Shutdown Notification';
		        	$fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
		        	$toEmail   = $apiFailureEmail->getEmail();
		        	$body      = $this->getContainer()->get('templating')->renderResponse('DhiAdminBundle:Emails:api_service_down.html.twig', array('apiStatusLog' => $apiStatusLog));
		        
		        	$apiServiceDownMail = \Swift_Message::newInstance()
									        	->setSubject($subject)
									        	->setFrom($fromEmail)
									        	->setTo($toEmail)
									        	->setBody($body->getContent())
									        	->setContentType('text/html');
		        
		        	if($this->getContainer()->get('mailer')->send($apiServiceDownMail)){
		        				        				        
		        		$output->writeln("\nAPI shutdown notification email has been sent to : ".$toEmail." \n");
		        	}else{
		        
		        		$output->writeln("\nAPI shutdown notification email sending failed to : ".$toEmail." \n");
		        	}
        		}
        		
        		$apiStatusLog->setTotalFailed(0);
        		$apiStatusLog->setIsSendNotification(1);
        		
        		$em->persist($apiStatusLog);
        		$em->flush();
        	}	
        }


        $output->writeln("\n####### End Cron #######\n");
    }
    
    function callSelevisionService() {
    	
    	$API_URL                 = $this->getContainer()->getParameter('selevision_api_url');
    	$Selevision_adm_username = $this->getContainer()->getParameter('selevision_admin_username');
    	$Selevision_adm_pass     = $this->getContainer()->getParameter('selevision_admin_pass');
    	
    	if($API_URL && $Selevision_adm_username && $Selevision_adm_pass){
    		
    		$action = 'getAllOffers';
    	
    		$postParam['adLogin'] = $Selevision_adm_username;
    		$postParam['adPwd']   = $Selevision_adm_pass;
    	
    		$postStr = http_build_query($postParam);
    	
    		// Set the curl parameters.
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $API_URL.$action.'.php?'.$postStr);
    		curl_setopt($ch, CURLOPT_VERBOSE, 1);
    	
    		// Turn off the server and peer verification (TrustManager Concept).
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	
    		// Get response from the server.
    		$httpResponse = curl_exec($ch);
    	
    		$info = curl_getinfo($ch);
    	
    	
    		if($info['http_code'] == '200'){
    			
    			return true;
    		}
    	}
    	
    	return false;
    }
    
    function callAradialService() {
    	
    	$aradialAPIUrl      = $this->getContainer()->getParameter('aradial_api_url');
    	$aradialAPIUsername = $this->getContainer()->getParameter('aradial_api_username');
    	$aradialAPIPassword = $this->getContainer()->getParameter('aradial_api_pass');
    	
    	if($aradialAPIUrl && $aradialAPIUsername && $aradialAPIPassword){
    	
    		$postParam = array();
    		$postParam['Page'] = "OfferHit";
    		
    		$postStr = http_build_query($postParam);
    	
    		$ch = curl_init();
    		curl_setopt($ch, CURLOPT_URL, $aradialAPIUrl);
    		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    		curl_setopt($ch, CURLOPT_USERPWD, $aradialAPIUsername.":".$aradialAPIPassword);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    		curl_setopt($ch, CURLOPT_POST, true);
    		curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
    		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    	
    		// Get response from the server.
    		$cUrlresponse = curl_exec($ch);
    		$info         = curl_getinfo($ch);
    	
    	
    		if($info['http_code'] == '200'){
    			
    			return true;
    		}
    	}
    	
    	return false;
    }       
}
