<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SalesDetailsReportControllerTest extends WebTestCase {
   protected $testHttpHost;
   
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
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
    }
    
    public function testindexAction() {
         $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('GET', '/admin/sales-details-report');
        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Sales Details Report")')->count());
    }
    
    public function testsalesReportListJson() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
       $crawler = $client->request('GET', '/admin/sales-details-list-json');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::salesDetailsListJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(0, $crawler->filter('html:contains("Detail Sales report")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-details-list-json?sSearch_0=IPTV');
        
        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::salesDetailsListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Service Type")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-details-list-json?sSearch_9=BAF');

        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::salesDetailsListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Service location")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-details-list-json?sSearch_2=PayPal');

        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::salesDetailsListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Payment Method")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-details-list-json?sSearch_3=Brainvire');

        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::salesDetailsListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Admin Usser")')->count());
                
        $crawler = $client->request('GET', '/admin/sales-details-list-json?sSearch_6=ketan');

        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::salesDetailsListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Customer")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-details-list-json?sSearch_5=2016-10-01~2016-10-31');

        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::salesDetailsListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("date range")')->count());

    }
    
   
    public function testexportCsvAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/sales-details-report');
        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Sales Details Report")')->count());

        $form = $crawler->selectLink('Export CSV')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\SalesDetailsReportController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
    }
    
    /**
     *  General function for Admin login
     */
    public function doLogin($username, $password, $client) {
        
        $crawler = $client->request('POST', '/admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);
       
        $this->assertTrue($client->getResponse()->isRedirect());
        
        $client->followRedirect();
        
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
