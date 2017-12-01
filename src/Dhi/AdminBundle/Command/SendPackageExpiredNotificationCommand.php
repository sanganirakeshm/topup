<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\ServiceBundle\Entity\Package;

class SendPackageExpiredNotificationCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('dhi:send-package-expired-notification')
        ->setDescription('Inactive expired packages from custome account');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Package Expired Notification Send Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();

        $objUserService = $em->getRepository('DhiUserBundle:UserService')->getExpiredPackageForNotification();
        
        if($objUserService){
            
            foreach ($objUserService as $activeService){
                
                //Get date diffreance of Active nad Expiry date
                $activationDate = $activeService->getActivationDate();
                $expiredDate    = $activeService->getExpiryDate();
                
                $interval = $activationDate->diff($expiredDate);
                $days =  $interval->format('%a');
                //End here
                
                if($days >= 7){
                    
                    $activeService->setSentExpiredNotification(1);
                    $em->persist($activeService);
                    $em->flush();
                    
                    $subject   = 'Your '.$activeService->getService()->getName().' package will be expire on '.$expiredDate->format('m/d/Y');
                    //$fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                    $toEmail   = $activeService->getUser()->getEmail();
                    $isEmailVerified =  $activeService->getUser()->getIsEmailVerified();
                                        
                    if($isEmailVerified){
                     if($activeService->getServicePurchase()->getServiceLocationId()){
                        $servicelocationID = $activeService->getServicePurchase()->getServiceLocationId()->getId(); 
                        $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$servicelocationID));
                        if($getdomain){

                            $fromEmail  = $getdomain->getWhiteLabel()->getFromEmail();
                            $comapnyname = $getdomain->getWhiteLabel()->getCompanyName();
                        }  else {
                           $fromEmail   = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                           $comapnyname = 'ExchangeVUE'; 
                        }     
                      } else {

                           $fromEmail   = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                           $comapnyname = 'ExchangeVUE'; 
                        }
                        
                        $body      = $this->getContainer()->get('templating')->renderResponse('DhiAdminBundle:Emails:package_expired_notification.html.twig', array('username' => $activeService->getUser()->getFirstname(), 'userService' => $activeService,'companyname'=>$comapnyname));
                         
                        $notificationMail = \Swift_Message::newInstance()
                                            ->setSubject($subject)
                                            ->setFrom($fromEmail)
                                            ->setTo($toEmail)
                                            ->setBody($body->getContent())
                                            ->setContentType('text/html');

                        if($this->getContainer()->get('mailer')->send($notificationMail)){

                            $output->writeln("\nNotification sent to : ".$toEmail." \n");
                        }else{

                            $output->writeln("\nNotification sending failed to : ".$toEmail." \n");
                        }
                    }else{
                        $output->writeln("\nEmail not verified: ".$toEmail."\n");
                    }
                }                                
            }
        }else{
            
            $output->writeln("\n####### Expired package not found. #######\n");
        }
        
        $output->writeln("\n####### End Cron #######\n");                        
    }
}
