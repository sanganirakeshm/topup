<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;


class RecurringRemainderNotificationCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('dhi:recurring-payment-remainder-notification')
        ->setDescription('Send email notification to user for recurring payment');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();

        $objPaypalRecurring = $em->getRepository('DhiServiceBundle:PaypalRecurringProfile')->getNextbillDataForNotification();
        
        if($objPaypalRecurring){
            
            foreach ($objPaypalRecurring as $paypalRecurring){
                
                $purchaseOrder = $paypalRecurring->getPurchaseOrder();
                
                if($purchaseOrder){
                	
                	if ($purchaseOrder->getRecurringStatus() == 1) {
                	
	                	$recurringProfileDetail = $this->getContainer()->get('recurringProcess')->getRecurringProfileDetail($paypalRecurring->getProfileId());
	                	
	                	if ($recurringProfileDetail['STATUS'] == 'Active') {
	                		
	                		$finalPaymentDueDate = new \DateTime($recurringProfileDetail['FINALPAYMENTDUEDATE']);
	                		$profileStartDate = new \DateTime($recurringProfileDetail['PROFILESTARTDATE']);
	                		$nextDueDate = new \DateTime($recurringProfileDetail['NEXTBILLINGDATE']);
	                			                	
		                	$objUserService = $purchaseOrder->getUserService();
		                	 
		                	$nextBillingDate = $paypalRecurring->getNextBillingDate()->format('m/d/Y');
		                	$paypalOrderNo = '';
		                	if($purchaseOrder->getPaypalCheckout()) {
		                	
		                		$paypalOrderNo = $purchaseOrder->getPaypalCheckout()->getPaypalTransactionId();
		                	}
	                	
	                            $serviceocationID  = $purchaseOrder->getUser()->getUserServiceLocation()->getId();
                                      
                                    $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$serviceocationID));
                                    if($getdomain){
                                        $fromEmail  = $getdomain->getWhiteLabel()->getFromEmail();
                                        $comapnyname = $getdomain->getWhiteLabel()->getCompanyName();
                                        $subject   = $comapnyname.': Remainder for next billing due on '.$nextBillingDate;
                                    } else {
                                       $subject   = 'ExchangeVUE: Remainder for next billing due on '.$nextBillingDate;
                                       $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                                       $comapnyname = 'ExchangeVUE'; 
                                    }
                                        
		                    //$subject   = 'ExchangeVUE: Remainder for next billing due on '.$nextBillingDate;
		                    //$fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
		                    $toEmail   = $purchaseOrder->getUser()->getEmail();
                                    $isEmailVerified =  $objPurchaseOrder->getUser()->getIsEmailVerified();
                                        
                                    if($isEmailVerified){
                                        $bodyArr = array(
                                                            'username' 		=> $purchaseOrder->getUser()->getFirstname(), 
                                                            'userServices' 	=> $objUserService,
                                                            'paypalOrderNo' => $paypalOrderNo,
                                                            'nextBillingDate' => $nextBillingDate,
                                                             'companyname'    =>$comapnyname
                                        );
                                        $body      = $this->getContainer()->get('templating')->renderResponse('DhiAdminBundle:Emails:recurring_payment_remainder.html.twig', $bodyArr);

                                        $notificationMail = \Swift_Message::newInstance()
                                                            ->setSubject($subject)
                                                            ->setFrom($fromEmail)
                                                            ->setTo($toEmail)
                                                            ->setBody($body->getContent())
                                                            ->setContentType('text/html');

                                        if($this->getContainer()->get('mailer')->send($notificationMail)){

                                            $paypalRecurring->setProfileId($recurringProfileDetail['PROFILEID']);
                                            $paypalRecurring->setProfileStatus($recurringProfileDetail['STATUS']);
                                            $paypalRecurring->setProfileStartDate($profileStartDate);
                                            $paypalRecurring->setNextBillingDate($nextDueDate);
                                            $paypalRecurring->setFinalDueDate($finalPaymentDueDate);
                                            $paypalRecurring->setNumCompletedCycle($recurringProfileDetail['NUMCYCLESCOMPLETED']);
                                            $paypalRecurring->setNumRemainingCycle($recurringProfileDetail['NUMCYCLESREMAINING']);
                                            $paypalRecurring->setAck($recurringProfileDetail['ACK']);
                                            $paypalRecurring->setIsSendNotification(0);

                                            $em->persist($paypalRecurring);
                                            $em->flush();		                    

                                            $output->writeln("\nRecurring Remainder Notification sent to : ".$toEmail." \n");
                                        }else{

                                            $output->writeln("\nRecurring Remainder Notification sending failed to : ".$toEmail." \n");
                                        }
                                    }else{
                                        $output->writeln("\nEmail not verified: ".$toEmail."\n");
                                    }
	                	} 
                	}	                   
                }                                
            }
        }else{
            
            $output->writeln("\n####### Recurring remainder data not found. #######\n");
        }
        
        $output->writeln("\n####### End Cron #######\n");                        
    }
}
