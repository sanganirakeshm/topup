<?php
namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\ActivateMacUsersServiceCommand;
use Dhi\AdminBundle\Command\SurveyMonkeyMailCommand;

class SurveyMonkeyMailCommandTest extends WebTestCase {
   protected $container;
    protected $em;
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        $this->container = static::$kernel->getContainer();
    }
    
    /**
     * This action allows to list product
     */
    public function testExecute() {
        $em = $this->em;
        $surveyMonkeyRecord = $em->getRepository('DhiUserBundle:SurveyMonkeyMail')->findBy(array('emailStatus' => 'Sending'));
        $this->assertFalse(count($surveyMonkeyRecord) > 0 , 'New Cron execution will not start until old cron process complete.');
        
        $objEmailCampaign = $em->getRepository('DhiUserBundle:SurveyMonkeyMail')->findBy(array('emailStatus' => 'sent'));
        $this->assertFalse(count($objEmailCampaign) == 0 , 'Active email Survey Monkey  not found.');
        
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new SurveyMonkeyMailCommand());
        $command = $objApplication->find('dhi:send-survey-monkey-email');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/../', $objcommandtest->getDisplay());
        
        /*if (!count($surveyMonkeyRecord)) {

            $objEmailCampaign = $em->getRepository('DhiUserBundle:SurveyMonkeyMail')->findBy(array('emailStatus' => 'sent'));

            if (count($objEmailCampaign)) {
                $sentUserId = array();
                foreach ($objEmailCampaign as $emailCampaign) {
                    $i = 0;
                    $servicePurchaseUser = $em->getRepository('DhiUserBundle:UserService')->getFreeTrialUsers();
                    
                    if ($servicePurchaseUser) {
                        // update email status 'Sending'
                        $emailCampaign->setEmailStatus('Sending');
                        $em->persist($emailCampaign);
                        $em->flush();

                        foreach ($servicePurchaseUser as $purchasedService) {
                            $userId = $purchasedService->getUser()->getId();

                            $sendMailFlag = false;

                            if (!in_array($userId, $sentUserId)) {

                                $sentUserId[] = $userId;

                                if ($purchasedService->getPackageName() == 'Free Trial') {
                                    $sendMailFlag = true;
                                }
                            }

                            if ($sendMailFlag) {                               
                                $subject = $emailCampaign->getSubject();
                                $fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
                                $toEmail = $purchasedService->getUser()->getEmail();
                                $isEmailVerified =  $objPurchaseOrder->getUser()->getIsEmailVerified();
                                        
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

                                        $this->assertTrue(0,'mail sent');
                                    } else {

                                        $this->assertFalse(1,'Mail not sending');
                                    }
                                }else{
                                   $this->assertFalse(1,'email Mail not verified');
                                }
                            }
                        }
                        $this->assertEquals(0,count($servicePurchase));
                    }
                    $emailCampaign->setEmailStatus('Sent');
                    $em->persist($emailCampaign);
                    $em->flush();
                }
                $this->assertEquals(0,count($objEmailCampaign));
            } else {
                $this->assertFalse(1,'campaing not found');
            }
        }*/

    }
    
    /**
     * {@inheritDoc}
     */
    protected function tearDown(){
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}


