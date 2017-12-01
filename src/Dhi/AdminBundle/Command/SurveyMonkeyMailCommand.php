<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Dhi\AdminBundle\Entity\SurveyMonkeyMailLog;
use \DateTime;

class SurveyMonkeyMailCommand extends ContainerAwareCommand {

    private $output;

    protected function configure() {
        $this->setName('dhi:send-survey-monkey-email')
                ->setDescription('Survey monkey email or support email to customer.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("\n####### Start Send Survey Monkey Mail Cron at " . date('M j H:i') . " #######\n");

        // fetch records from survey_monkey_email which are under process
        $em = $this->getContainer()->get('doctrine')->getManager();

        $surveyMonkeyRecord = $em->getRepository('DhiUserBundle:SurveyMonkeyMail')->findBy(array('emailStatus' => 'Sending'));

        // new cron execute will not start until old will not be completed.
        if (!count($surveyMonkeyRecord)) {

            $objEmailCampaign = $em->getRepository('DhiUserBundle:SurveyMonkeyMail')->findBy(array('emailStatus' => 'sent'));

            if (count($objEmailCampaign)) {
                $sentUserId = array();
                $userArray = 0;
                $packageArray = 0;
                
                foreach ($objEmailCampaign as $emailCampaign) {
                    $i = 0;
                    $SurveyLogId = $emailCampaign->getId();
                    $surveysentmaillogdata = $em->getRepository('DhiAdminBundle:SurveyMonkeyMailLog')->getSurveyMonkeyLog($SurveyLogId);
                    
                    $datcount = count($surveysentmaillogdata); 
                    if($datcount>0){
                       
                        foreach($surveysentmaillogdata as $surveysentmaillog){
                            $userArray[] = $surveysentmaillog['userId'];
                            $packageArray[] = $surveysentmaillog['packageId'];
                        }
                    } 
                    
                    $servicePurchaseUser = $em->getRepository('DhiUserBundle:UserService')->getFreeTrialUsers($userArray,$packageArray);
                     
                    if ($servicePurchaseUser) {
                        
                        // update email status 'Sending'
                        $emailCampaign->setEmailStatus('Sending');
                        $em->persist($emailCampaign);
                        $em->flush();

                        foreach ($servicePurchaseUser as $purchasedService) {
                            $userId = $purchasedService->getUser()->getId();
                            $packageId = $purchasedService->getPackageId();
                            $sendMailFlag = false;

                            if (!in_array($userId, $sentUserId)) {

                                $sentUserId[] = $userId;

                                if ($purchasedService->getPackageName() == 'Free Trial') {
                                    $sendMailFlag = true;
                                }
                            }

                            if ($sendMailFlag) {  
                                
                                $surveyLogobj = new SurveyMonkeyMailLog();
                                $surveyLogobj->setUserId($userId);
                                $surveyLogobj->setPackageId($packageId);
                                $surveyLogobj->setSurveyId($SurveyLogId);
                                $em->persist($surveyLogobj);
                                //$em->flush();
                                 
                                $subject = $emailCampaign->getSubject();
                                $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                                $toEmail = $purchasedService->getUser()->getEmail();
                                $isEmailVerified =  $purchasedService->getUser()->getIsEmailVerified();
                                        
                                if($isEmailVerified){
                                    $body = $emailCampaign->getMessage();
                                    $output->writeln("\ Email sent to : " . $toEmail . " \n");

                                    $emailCampaignmail = \Swift_Message::newInstance()
                                            ->setSubject($subject)
                                            ->setFrom($fromEmail)
                                            ->setTo($toEmail)
                                            ->setBody($body)
                                            ->setContentType('text/html');

                                    if ($this->getContainer()->get('mailer')->send($emailCampaignmail)) {

                                        $output->writeln("\nSurvey Monkey email sent to : " . $toEmail . " \n");
                                    } else {

                                        $output->writeln("\nSurvey Monkey email sending failed to : " . $toEmail . " \n");
                                    }
                                }else{
                                    $output->writeln("\nEmail not verified: ".$toEmail."\n");
                                }
                            }
                            
                        }
                        
                    } else {
                       $output->writeln("\n####### Survey Monkey User not found. #######\n"); 
                    }
                    $emailCampaign->setEmailStatus('Sent');
                    $em->persist($emailCampaign);
                    $em->flush();
                } 
            } else {
                $output->writeln("\n####### Active email Survey Monkey  not found. #######\n");
            }
        } else {
            $output->writeln("\n####### New Cron execution will not start until old cron process complete #######\n");
        }

        $output->writeln("\n####### End Cron #######\n");
    }
}