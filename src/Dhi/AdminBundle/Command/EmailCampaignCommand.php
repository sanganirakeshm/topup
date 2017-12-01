<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\UserBundle\Entity\EmailCampaignHistory;

class EmailCampaignCommand extends ContainerAwareCommand {

    private $output;

    protected function configure() {
        $this->setName('dhi:send-email-campaign')
                ->setDescription('Blast marketing or support email to customer.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("\n####### Start Send Email Campaign Cron at " . date('M j H:i') . " #######\n");

        // fetch records from email_campaign which are under process
        $em = $this->getContainer()->get('doctrine')->getManager();

        $emailCampaignRecord = $em->getRepository('DhiUserBundle:EmailCampaign')->findBy(array('emailStatus' => 'Sending'));
        $blastEmailSent = 0;
        $blastEmailCampaignRecord = $em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'blast_email_campaign'));
        // new cron execute will not start until old will not be completed.
        if (!count($emailCampaignRecord)) {

            $objEmailCampaign = $em->getRepository('DhiUserBundle:EmailCampaign')->findBy(array('emailStatus' => 'In Progress'));

            if (count($objEmailCampaign)) {

                foreach ($objEmailCampaign as $emailCampaign) {
                    $serviceIds = array();
                    foreach ($emailCampaign->getServices() as $service) {
                        $serviceIds[] = $service->getId();
                    }

                    $serviceLocationIp = array();
                    $sentUserId = array();
                    $i = 0;
                    if ($emailCampaign->getServiceLocations()) {

                        foreach ($emailCampaign->getServiceLocations() as $serviceLocation) {

                            if ($emailCampaign->getEmailType() == 'M') {
                                if ($serviceLocation->getIpAddressZones()) {
                                    foreach ($serviceLocation->getIpAddressZones() as $ipAddress) {
                                        $fromIP = ip2long($ipAddress->getFromIpAddress());
                                        $toIP = ip2long($ipAddress->getToIpAddress());

                                        $servicePurchaseUser = $em->getRepository('DhiUserBundle:UserService')->getServicePurchasedUsers($serviceIds, $fromIP, $toIP);
                                        
                                        if ($servicePurchaseUser) {
                                            // update email status 'Sending'
                                            $emailCampaign->setEmailStatus('Sending');
                                            $em->persist($emailCampaign);
                                            $em->flush();

                                            foreach ($servicePurchaseUser as $purchasedService) {
                                                $userId = $purchasedService['id'];
                                                $sendMailFlag = false;
                                                if (!in_array($userId, $sentUserId)) {
                                                    $sentUserId[] = $userId;
                                                    $sendMailFlag = true;                                   
                                                }
                                                if ($sendMailFlag) {
                                                    $blastEmailSent = $this->sendEmail($emailCampaign, $purchasedService, $blastEmailCampaignRecord, $blastEmailSent, $output,$serviceLocation->getId());
                                                }
                                            }
                                        }
                                    }
                                }
                            }else if($emailCampaign->getEmailType() == 'S'){
                                $users = $em->getRepository('DhiAdminBundle:ServiceLocation')->getUserByServiceLocation($serviceLocation->getId());
                                
                                foreach ($users as $user) {
                                    $blastEmailSent = $this->sendEmail($emailCampaign, $user, $blastEmailCampaignRecord, $blastEmailSent, $output,$serviceLocation->getId());
                                }
                            }
                        }
                        $todayDateTime = new \DateTime();
                        $emailCampaign->setEmailStatus('Sent');
                        $emailCampaign->setSentAt($todayDateTime);
                        $em->persist($emailCampaign);
                        $em->flush();
                    } else {
                        $output->writeln("\n####### Service not available location not found. #######\n");
                    }
                }
            } else {
                $output->writeln("\n####### Active email campaign not found. #######\n");
            }
        } else {
            $output->writeln("\n####### New Cron execution will not start until old cron process complete #######\n");
        }

        $output->writeln("\n####### End Cron #######\n");
    }

    private function sendEmail($emailCampaign, $purchasedService, $blastEmailCampaignRecord, $blastEmailSent, $output,$locationId){
        
        $em = $this->getContainer()->get('doctrine')->getManager();
        $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$locationId));
        if($getdomain){
            $fromEmail  = $getdomain->getWhiteLabel()->getFromEmail();
            $comapnyname = $getdomain->getWhiteLabel()->getCompanyName();
        } else {
           $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
           $comapnyname = 'ExchangeVUE'; 
        }
        
        $subject   = $emailCampaign->getSubject();
        $toEmail   = $purchasedService['email'];
        $body      = $this->getContainer()->get('templating')->renderResponse('DhiAdminBundle:Emails:email_campaign.html.twig', array('username' => $purchasedService['firstname'], 'emailCampaign' => $emailCampaign,'companyname'=>$comapnyname));
        if (count($blastEmailCampaignRecord) > 0 && $blastEmailSent == 0) {
            $blastEmailSentId = $blastEmailCampaignRecord->getValue();
            $output->writeln("- Email sending to : " . $blastEmailSentId . " \n");
            $emailCampaignmail = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom($fromEmail)
                    ->setTo($blastEmailSentId)
                    ->setBody($body->getContent())
                    ->setContentType('text/html');

            if ($this->getContainer()->get('mailer')->send($emailCampaignmail)) {
                $blastEmailSent = 1;
                $output->writeln("- Campaign Email sent to : " . $blastEmailSentId . " \n");
                
                $objEmailCampaignHistory = new EmailCampaignHistory();
                $objEmailCampaignHistory->setEmailCampaign($emailCampaign);
                $objEmailCampaignHistory->setBlastEmail($blastEmailSentId);
                $em->persist($objEmailCampaignHistory);
                $em->flush();
                
            } else {
                $output->writeln("- Campaign Email sending failed to : " . $blastEmailSentId . " \n");
            }
        }

        if (!empty($purchasedService['isEmailVerified']) && $purchasedService['isEmailVerified'] == 1) {
            $emailCampaignmail = \Swift_Message::newInstance()
                    ->setSubject($subject)
                    ->setFrom($fromEmail)
                    ->setTo($toEmail)
                    ->setBody($body->getContent())
                    ->setContentType('text/html');
            $output->writeln("Email sending to : $toEmail\n");
            
            if ($this->getContainer()->get('mailer')->send($emailCampaignmail)) {

                $output->writeln("- Campaign Email sent to : " . $toEmail . " \n");
                
                $objUser = $em->getRepository('DhiUserBundle:User')->find($purchasedService['id']);
                
                $objEmailCampaignHistory = new EmailCampaignHistory();
                $objEmailCampaignHistory->setEmailCampaign($emailCampaign);
                $objEmailCampaignHistory->setUser($objUser);
                $em->persist($objEmailCampaignHistory);
                $em->flush();
                
            } else {

                $output->writeln("- Campaign Email sending failed to : " . $toEmail . " \n");
            }
        }
        return $blastEmailSent;
    }
}