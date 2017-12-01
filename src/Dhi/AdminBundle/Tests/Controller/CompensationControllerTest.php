<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\UserBundle\Entity\Compensation;
class CompensationControllerTest extends WebTestCase {
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

        $crawler = $client->request('GET', '/admin/compensation-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Compensation")')->count());
    }
    
    
    public function testcompensationListJsonAction(){
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       $crawler = $client->request('GET', '/admin/compensation-list-json');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\CompensationController::compensationListJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(1, $crawler->filter('html:contains("Compensation")')->count());
        // for country wrong  parameter
        $crawler = $client->request('GET', '/admin/compensation-list-json?sSearch_1=Title');

        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationController::compensationListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Title")')->count());
        
    } 
    
    public function testAddCompensation() {
        
        $em = $this->em;
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
       
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('POST', '/admin/add-new-compensation');
         
        $client->enableProfiler();
       
        
        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationController::newAction', $client->getRequest()->attributes->get('_controller'));
        
        $form = $crawler->selectButton('Add')->form();
        $form['dhi_admin_compensation[title]'] = 'Test compensation';
        $form['dhi_admin_compensation[isEmailActive]'] = 1;
        $form['dhi_admin_compensation[emailSubject]'] = 'Vodafone1245';
        $form['dhi_admin_compensation[emailContent]'] = 'test content';
        $form['dhi_admin_compensation[note]'] = 'test note';
        $form['dhi_admin_compensation[isActive]'] = 1;
        $crawler = $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertTrue($client->getResponse()->isSuccessful(),'Compesation updated successfully');
    }
    
    /**
     * This action allows to test add provider
     */
    public function testEditCompensation() {
        
        $em = $this->em;
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
       
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('POST', '/admin/add-new-compensation');
         
        $client->enableProfiler();
       
        
        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationController::editAction', $client->getRequest()->attributes->get('_controller'));
        
        $form = $crawler->selectButton('Add')->form();
        $form['dhi_admin_compensation[title]'] = 'Test compensation';
        $form['dhi_admin_compensation[isEmailActive]'] = 1;
        $form['dhi_admin_compensation[emailSubject]'] = 'Vodafone1245';
        $form['dhi_admin_compensation[emailContent]'] = 'test content';
        $form['dhi_admin_compensation[note]'] = 'test note';
        $form['dhi_admin_compensation[isActive]'] = 1;
        $crawler = $client->submit($form);
        /*$Objcompensation = $em->getRepository('DhiUserBundle:Compensation')->find(138);
        $Objcompensation->setTitle('Test Compensation');
        $Objcompensation->setNote('test note');
        $Objcompensation->setIsActive(1);
        $Objcompensation->setIsEmailActive(1);
        $Objcompensation->setEmailSubject('Test Subject');
        $Objcompensation->setEmailContent('Test email content');
        $Objcompensation->setAdminId(1);
        $em->persist($Objcompensation);
        $em->flush();*/
        
        $this->assertTrue($client->getResponse()->isSuccessful(),'Compesation added successfully');
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

