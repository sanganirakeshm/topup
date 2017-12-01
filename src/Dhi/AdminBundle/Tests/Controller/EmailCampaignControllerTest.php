<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\UserBundle\Entity\EmailCampaign;



/**
 * 
 */
class EmailCampaignControllerTest extends WebTestCase {

     /**
     * {@inheritDoc}
     */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    public function testindex(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/email-campaign/email-campaign-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Email campaign")')->count());
    }
    
      public function testemailCampaignListJson(){
           $client = static::createClient(
                            array(), array(
                        'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            )); 
           $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
           $crawler = $client->request('GET', '/admin/email-campaign/email-campaign-list-json');

           $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::emailCampaignListJsonAction',
                    $client->getRequest()->attributes->get('_controller'));
           
           $crawler = $client->request('GET', '/admin/email-campaign/email-campaign-list-json?sSearch_0=php');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::emailCampaignListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $getresult = $crawler->filter('html:contains("php")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
         $crawler = $client->request('GET', '/admin/email-campaign/email-campaign-list-json?sSearch_1=Marketing');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::emailCampaignListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $getresult = $crawler->filter('html:contains("Marketing")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/email-campaign/email-campaign-list-json?sSearch_3=In+Progress');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::emailCampaignListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $getresult = $crawler->filter('html:contains("Progress")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/email-campaign/email-campaign-list-json?sSearch_4=08%01%2016~10%2F28%2F2016');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::emailCampaignListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $getresult = $crawler->filter('html:contains("2016")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
    }

    /**
     * This action allows to test the updating account page for a user
     */
    public function testAddEmailCampaign() {
        $em = $this->em;
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
       
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('POST', '/admin/email-campaign/add-email-campaign');
         
        $client->enableProfiler();
        
        $subject = 'PHP unit test';
        $message = 'Marketing messsage';
        $service = 'IPTV';
        $servvicelocation = 'BAF';
        $emailType = 'M';
        
        $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('Add')->form();
        $form['dhi_admin_email_campaign[subject]'] = $subject;
        $form['dhi_admin_email_campaign[message]'] = $message;
        $form['dhi_admin_email_campaign[emailType]'] = $emailType;
        $form['dhi_admin_email_campaign[emailStatus]'] = 'In Progress';
        
        $crawler = $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Email campaign added successfully.")')->count());
    }
    
    /**
     * This action allows to test edit provider
     */
    public function testEditEmailCampaign() {
        $em = $this->em;
         $client = static::createClient(
                            array(), array(
                        'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $objEmailCampaign = $this->em->getRepository('DhiUserBundle:EmailCampaign')->findOneBy(array(), array('id' => 'DESC'));
               
        $crawler = $client->request('POST', '/admin/email-campaign/edit-email-campaign/' . $objEmailCampaign->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::editAction', $client->getRequest()->attributes->get('_controller'));

        $subject = 'PHP unit test';
        $message = 'Marketing messsage';
        $service = 'IPTV';
        $servvicelocation = 'BAF';
        $emailType = 'S';
        
        $form = $crawler->selectButton('edit')->form();
        $form['dhi_admin_email_campaign[subject]'] = $subject;
        $form['dhi_admin_email_campaign[message]'] = $message;
        $form['dhi_admin_email_campaign[emailType]'] = $emailType;
        $form['dhi_admin_email_campaign[emailStatus]'] = 'In Progress';
        
        $crawler = $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Email campaign updated successfully.")')->count());
   }

    public function testdelete() {
               
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $objEmailCampaign = $this->em->getRepository('DhiUserBundle:EmailCampaign')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objEmailCampaign) == 0, 'EmailCampaign not found');
                
         $crawler = $client->request('DELETE', "/admin/email-campaign/delete-email-campaign?id=".$objEmailCampaign->getId());
                  
         $this->assertEquals('Dhi\AdminBundle\Controller\EmailCampaignController::deleteAction', $client->getRequest()->attributes->get('_controller'));
         
         $response = $client->getResponse();
         $data = json_decode($response->getContent());
         $this->assertFalse($data->type != 'success', $data->message);
        
    }
   
   public function doLogin($username, $password, $client) {
        $crawler = $client->request('POST', '/admin/login');
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => static::$kernel->getContainer()->getParameter('test_admin_username'),
            '_password' => static::$kernel->getContainer()->getParameter('test_admin_password'),
        ));
        
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}
