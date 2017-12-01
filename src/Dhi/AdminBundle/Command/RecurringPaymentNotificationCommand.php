<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\ServiceBundle\Entity\RecurringPaymentLog;

class RecurringPaymentNotificationCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('dhi:recurring-payment-notification')->setDescription('Send email notification to user for success recurring payment');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();

        $todayDateTime = new \DateTime();
        $objPaypalRecurringProfile = $em->getRepository('DhiServiceBundle:PaypalRecurringProfile')->findBy(array('profileStatus' => 'Active'));
        
        if($objPaypalRecurringProfile){
            
            foreach ($objPaypalRecurringProfile as $paypalRecurringProfile){
            	
            	$isUpdatePaypalRecurringProfileData = false;
            	$isUpdateRecurringProfileLogData 	= false;
            	$sendNotificationMail = false;
            	$isExtendServiceValidity	= false;
            	
            	//GetRecurringProfileDetail from paypal
            	$recurringProfileDetail = $this->getContainer()->get('recurringProcess')->getRecurringProfileDetail($paypalRecurringProfile->getProfileId());
            	//echo '<pre>';print_r($recurringProfileDetail);exit;
            	$recurringProfileDetail['NEXTBILLINGDATE'] = '2016-03-28T10:00:00Z';
            	
            	//PurchaseOrder object
            	$objPurchaseOrder = $paypalRecurringProfile->getPurchaseOrder();
            	
            	//Paypal GetRecurringProfileDetail data inital
            	$apiProfileStatus		= $recurringProfileDetail['STATUS'];
            	$apiProfileStartDate 	= new \DateTime($recurringProfileDetail['PROFILESTARTDATE']);
            	$apiFinalPaymentDueDate = new \DateTime($recurringProfileDetail['FINALPAYMENTDUEDATE']);
            	$apiProfileId			= $recurringProfileDetail['PROFILEID'];
            	$apiNumCycleCompleted 	= $recurringProfileDetail['NUMCYCLESCOMPLETED'];
            	$apiNumCycleRemaing 	= $recurringProfileDetail['NUMCYCLESREMAINING'];
            	$apiAck 				= $recurringProfileDetail['ACK'];
            	$apiAmount				= $recurringProfileDetail['AMT'];
            	$apiNextDueDate			= NULL;
            	
            	$dbNextDueDate 		= $paypalRecurringProfile->getNextBillingDate();
            	$dbProfileStatus 	= $paypalRecurringProfile->getProfileStatus();
            	
            	if ($dbProfileStatus == 'Active') {
            		
            		if ($apiProfileStatus == 'Active') {
            			
            			$apiNextDueDate = new \DateTime($recurringProfileDetail['NEXTBILLINGDATE']);
            			
            			if ($apiNextDueDate->format('Y-m-d') > $dbNextDueDate->format('Y-m-d')) {
            			
            				$isUpdatePaypalRecurringProfileData = true;
            				$isUpdateRecurringProfileLogData 	= true;
            				$sendNotificationMail = true;
            				$isExtendServiceValidity = true;
            			}
            		} else if ($apiProfileStatus == 'Expired') {
            			
            			if ($apiFinalPaymentDueDate->format('Y-m-d') == $dbNextDueDate->format('Y-m-d')) {
            				
            				$isUpdatePaypalRecurringProfileData = true;
            				$isUpdateRecurringProfileLogData 	= true;
            				$sendNotificationMail = true;
            				$isExtendServiceValidity = true;
            			}
            		} else {
            			
            			$isUpdatePaypalRecurringProfileData = true;            				
            		}
            	}
            	
            	/* var_dump($isUpdatePaypalRecurringProfileData);
            	var_dump($isUpdateRecurringProfileLogData);
            	var_dump($sendNotificationMail);
            	var_dump($isExtendServiceValidity);
            	exit; */
            	//Update RecurringProfileLog data, extend current service and send success recurring payment notification
            	if ($isUpdateRecurringProfileLogData) {
            		
            		$objUserService = $objPurchaseOrder->getUserService();
            		
            		$paypalOrderNo = '';
            		if($objPurchaseOrder->getPaypalCheckout()) {
            			 
            			$paypalOrderNo = $objPurchaseOrder->getPaypalCheckout()->getPaypalTransactionId();
            		}
            		
            	
	            	//Update RecurringPaymentLog data
	            	$objRecurringPaymentLog = new RecurringPaymentLog();
	            	
	            	$objRecurringPaymentLog->setPaypalRecurringProfile($paypalRecurringProfile);
	            	$objRecurringPaymentLog->setProfileId($apiProfileId);
	            	$objRecurringPaymentLog->setProfileStatus($apiProfileStatus);
	            	$objRecurringPaymentLog->setBillingDate($dbNextDueDate);
	            	if ($apiNextDueDate) {
	            	$objRecurringPaymentLog->setNextBillingDate($apiNextDueDate);
	            	}
	            	$objRecurringPaymentLog->setFinalDueDate($apiFinalPaymentDueDate);
	            	$objRecurringPaymentLog->setNumCompletedCycle($apiNumCycleCompleted);
	            	$objRecurringPaymentLog->setNumRemainingCycle($apiNumCycleRemaing);
	            	$objRecurringPaymentLog->setAck($apiAck);
	            	$objRecurringPaymentLog->setAmount($apiAmount);
	            	
	            	$em->persist($objRecurringPaymentLog);
	            	$em->flush();
	            	//End here
	            	
	            	if ($objRecurringPaymentLog) {
	            	
	            		//Extend current active service validity
		            	if ($objUserService && $isExtendServiceValidity) {
		            		 
		            		foreach ($objUserService as $userService) {
		            	
		            			$expiryDate = $userService->getExpiryDate();
		            	
		            			if ($expiryDate->format('Y-m-d') <= $todayDateTime->format('Y-m-d')) {
		            	
		            				$newStartDate 	= $expiryDate->modify('+1 DAYS');
		            	
		            				$temExpDate = new \DateTime($newStartDate->format('Y-m-d H:i:s'));
		            	
		            				$newExpiryDate 	= $temExpDate->modify('+'.$userService->getValidity().' DAYS');
		            	
		            				$userService->setActivationDate($newStartDate);
		            				$userService->setExpiryDate($newExpiryDate);
		            				$userService->setStatus(1);
		            				$em->persist($userService);
		            				 
		            				$this->extendService($userService,$newStartDate,$newExpiryDate);
		            				 
		            				$output->writeln("\n####### Package: ".$userService->getPackageName()." has been extended successfully of username ".$userService->getUser()->getUserName()." #######\n");
		            			}
		            		}
		            	}
		            	//End here
	            	
		            	//Send Recurring payment success notification mail to user 
		            	if ($sendNotificationMail) {
		            			            		
	            			$serviceocationID  = $objPurchaseOrder->getUser()->getUserServiceLocation()->getId();
                                      
                                        $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$serviceocationID));
                                        if($getdomain){
                                            $fromEmail  = $getdomain->getWhiteLabel()->getFromEmail();
                                            $comapnyname = $getdomain->getWhiteLabel()->getCompanyName();
                                            $subject   = $comapnyname.': Your recurring payment has been successfully completed';
                                        } else {
                                           $subject   = 'ExchangeVUE: Your recurring payment has been successfully completed';
                                           $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                                           $comapnyname = 'ExchangeVUE'; 
                                        }
	            			$toEmail   = ($objPurchaseOrder->getUser())?$objPurchaseOrder->getUser()->getEmail():'';
	            			$isEmailVerified =  $objPurchaseOrder->getUser()->getIsEmailVerified();
                                        
                                        if($isEmailVerified){
                                            $bodyArr = array(
                                                            'username' 			=> ($objPurchaseOrder->getUser())?$objPurchaseOrder->getUser()->getFirstname():'',
                                                            'userServices' 		=> $objUserService,
                                                            'paypalOrderNo' 	=> $paypalOrderNo,
                                                            'billingDate' 	=> ($dbNextDueDate)?$dbNextDueDate->format('m/d/Y'):'',
                                                            'companyname'      =>$comapnyname
                                            );
                                            $body      = $this->getContainer()->get('templating')->renderResponse('DhiAdminBundle:Emails:recurring_payment_success.html.twig', $bodyArr);

                                            $notificationMail = \Swift_Message::newInstance()
                                                                                    ->setSubject($subject)
                                                                                    ->setFrom($fromEmail)
                                                                                    ->setTo($toEmail)
                                                                                    ->setBody($body->getContent())
                                                                                    ->setContentType('text/html');

                                            if($this->getContainer()->get('mailer')->send($notificationMail)){

                                                    $objRecurringPaymentLog->setIsPurchaseNotificationSend(1);
                                                    $em->persist($objRecurringPaymentLog);

                                                    $output->writeln("\nRecurring Purchase Notification sent to : ".$toEmail." \n");
                                            } else {

                                                    $output->writeln("\nRecurring Purchase Notification sending failed to : ".$toEmail." \n");
                                            }
                                        }else{
                                            $output->writeln("\nEmail not verified: ".$toEmail."\n");
                                        }
		            	}
		            	//End here
	            	}	
            	}
            	
            	//Update PaypalRecurringProfile Data
            	if ($isUpdatePaypalRecurringProfileData) {
            		
            		$paypalRecurringProfile->setProfileId($apiProfileId);
            		$paypalRecurringProfile->setProfileStatus($apiProfileStatus);
            		$paypalRecurringProfile->setProfileStartDate($apiProfileStartDate);
            		if ($apiNextDueDate) {
            		$paypalRecurringProfile->setNextBillingDate($apiNextDueDate);
            		}
            		$paypalRecurringProfile->setFinalDueDate($apiFinalPaymentDueDate);
            		$paypalRecurringProfile->setNumCompletedCycle($apiNumCycleCompleted);
            		$paypalRecurringProfile->setNumRemainingCycle($apiNumCycleRemaing);
            		$paypalRecurringProfile->setAck($apiAck);
            		 
            		$em->persist($paypalRecurringProfile);
            		$em->flush();            		
            	}
            	//End here
            }
        } else {
        	
        	$output->writeln("\n Active Recurring Profile Not Found. \n");
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
