<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\UserBundle\Entity\UserService;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\UserBundle\Entity\Compensation;
use Dhi\ServiceBundle\Controller\SelevisionController;
use Dhi\UserBundle\Entity\CustomerCompensationLog;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\CompensationUserService;

class CompensationEmailCommand extends ContainerAwareCommand{
    private $output;
    protected function configure(){
        $this->setName('dhi:send-compensation-email')->setDescription('Send compensation notification');
    }

    public function execute(InputInterface $input, OutputInterface $output){
        $output->writeln("\n####### Start Compensation Cron at ".date('M j H:i')." #######\n");
        $em              = $this->getContainer()->get('doctrine')->getManager();
        $objCompensation = $em->getRepository('DhiUserBundle:Compensation')->getCompensationEmail();
       if($objCompensation){
            foreach($objCompensation as $record) {
                $this->serviceLocationWiseCompensation($record,$output);
            }
        }
        $output->writeln("\n####### End Cron #######\n");
    }

    public function serviceLocationWiseCompensation($record, $output){
        $em = $this->getContainer()->get('doctrine')->getManager();
        $userService = $em->getRepository("DhiUserBundle:CompensationUserService")->find($record['compUserServiceId']);
        if($record['isEmailActive'] == 1) {
            if($this->sendCompensationMail($record)){
                $output->writeln("\n####### Compensation Email has been sent to ".$record['email']." #######\n");
            }else{
                $output->writeln("\n####### Compensation Email sending failed to ".$record['email']." #######\n");
            }
        }
        $userService->setIsEmailSent(1);
        $em->persist($userService);
        $em->flush();

        // Send email to Admin
        if (isset($record['isEmailSentToAdmin']) && $record['isEmailSentToAdmin'] == false) {
            $objComp = $em->getRepository("DhiUserBundle:Compensation")->find($record['id']);
            if ($objComp) {
                if ($objComp->getIsEmailSentToAdmin() == false) {
                    if ($this->sendSystemCompensationMail($record)) {
                        $objComp->setIsEmailSentToAdmin(true);
                        $em->persist($objComp);
                        $em->flush();
                    }
                }
            }
        }
    }

    public function sendCompensationMail($objCompensation){
        if($objCompensation && !empty($objCompensation['isEmailVerified'])){
            
            $em = $this->getContainer()->get('doctrine')->getManager();
            $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$objCompensation['servicelocation']));
            if($getdomain){
               
                $fromEmail  = $getdomain->getWhiteLabel()->getFromEmail();
                $comapnyname = $getdomain->getWhiteLabel()->getCompanyName();
                $subject    = $comapnyname.' -'. $objCompensation['emailSubject'];
            } else {
                
               $subject     = 'ExchangeVUE -'. $objCompensation['emailSubject'];
               $fromEmail   = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
               $comapnyname = 'ExchangeVUE'; 
            }
            
            
            $toEmail           = $objCompensation['email'];
            $body              = $this->getContainer()->get('templating')->renderResponse('DhiUserBundle:Emails:compensation_email.html.twig', array('username' => $objCompensation['username'], 'emailContent' => $objCompensation['emailContent'],'companyname'=>$comapnyname));
         
            $compensation_email = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setFrom($fromEmail)
                ->setTo($toEmail)
                ->setBody($body->getContent())
                ->setContentType('text/html');

            if($this->getContainer()->get('mailer')->send($compensation_email)){
                return true;
            }
        }
        return false;
    }

    public function sendSystemCompensationMail($objCompensation){
        if($objCompensation){
            if($objCompensation['isEmailActive'] == 1) {

                $em = $this->getContainer()->get('doctrine')->getManager();
                $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$objCompensation['servicelocation']));
                if($getdomain){
                    $fromEmail  = $getdomain->getWhiteLabel()->getFromEmail();
                    $comapnyname = $getdomain->getWhiteLabel()->getCompanyName();
                    $subject    = $comapnyname.' -'. $objCompensation['emailSubject'];
                } else {
                   $subject     = 'ExchangeVUE -'. $objCompensation['emailSubject'];
                   $fromEmail   = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                   $comapnyname = 'ExchangeVUE'; 
                }

                $compensationEmail = $this->getContainer()->getParameter('compensation_email');                
                $body              = $this->getContainer()->get('templating')->renderResponse('DhiUserBundle:Emails:compensation_email.html.twig', array('username' => '', 'emailContent' => $objCompensation['emailContent'],'companyname'=>$comapnyname));

                $compensation_email = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom($fromEmail)
                    ->setTo($compensationEmail)
                    ->setBody($body->getContent())
                    ->setContentType('text/html');

                if($this->getContainer()->get('mailer')->send($compensation_email)){
                    return true;
                }
            }
        }
        return false;
    }
}