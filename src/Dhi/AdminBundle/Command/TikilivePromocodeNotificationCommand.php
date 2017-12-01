<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\AdminBundle\Entity\UserOnlineSession;

class TikilivePromocodeNotificationCommand extends ContainerAwareCommand {
	
	protected function configure() {
		$this->setName('dhi:tikilive-promocode-notification')
				->setDescription('Sent notification to DHI team to import new set of promocode.');
	}

	public function execute(InputInterface $input, OutputInterface $output) {

		$output->writeln("\n####### Start Tikilive Promocode Notification Cron at " . date('M j H:i') . " #######\n");
		$em = $this->getContainer()->get('doctrine')->getManager();
		$totalunusedcount = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->getunusedtikilivepromocodes();
                
                $tikilivethreshold         = $em->getRepository("DhiAdminBundle:Setting")->findOneByName('tikilive_promocode_threshold');
                $tikilivethresholdval      = trim($tikilivethreshold->getValue());
                if(!is_numeric($tikilivethresholdval) || $tikilivethresholdval < 0){
                    $output->writeln("\n####### Cron Aborted (Error): Tikilive promocode threshold shoud be integer value. #######\n");
                    exit();
                }
                if ($tikilivethreshold) {
                    
                    if($tikilivethresholdval > $totalunusedcount || $tikilivethresholdval == $totalunusedcount){
                       
                        /************Send Email to Admin/ DHI Team =*************/
                        $thresoldnotficationemail        = $em->getRepository("DhiAdminBundle:Setting")->findOneByName('tikilive_promocode_threshold_notification_to');
                        if($thresoldnotficationemail){
                            $setofemailArr = $thresoldnotficationemail->getValue();
                            $emailarr      = explode(',', $setofemailArr);
                            $body          = $this->getContainer()->get('templating')->render('DhiAdminBundle:Emails:tikilive_promocode_notification.html.twig');
                            $fromEmail     = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                            foreach($emailarr as $email){
                                $email = trim($email);
                                if (!!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    $tikilive_promocode_notification = \Swift_Message::newInstance()
                                        ->setSubject("TIkilive Promocode Threshold Exceed Notification")
                                        ->setFrom($fromEmail)
                                        ->setTo($email)
                                        ->setBody($body)
                                        ->setContentType('text/html');

                                   if($this->getContainer()->get('mailer')->send($tikilive_promocode_notification)){
                                      $output->writeln("\n####### Notification sent to ".$email."  #######\n");
                                   }
                               }
                            }
                        }
                        
                        /**************End of code************/
                    }
                }
                
		$output->writeln("\n####### End Cron #######\n");
	}
}
