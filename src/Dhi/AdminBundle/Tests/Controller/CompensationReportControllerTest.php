<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CompensationReportControllerTest extends WebTestCase {
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
    
    public function testindexAction() {
         $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('GET', '/admin/compensation-report-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationReportController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Compensation Report")')->count());
    }
    
    public function testsalesReportListJson() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
       $crawler = $client->request('GET', '/admin/compensation-report-list-json');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\CompensationReportController::listJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(0, $crawler->filter('html:contains("Compensation report")')->count());
        
        $crawler = $client->request('GET', '/admin/compensation-report-list-json?sSearch_0=Test compensation');
        
        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationReportController::listJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Test compensation")')->count());

    }
    
   
    public function testexportCsvAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/compensation-report-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationReportController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Compensation Report")')->count());

        $form = $crawler->selectLink('Export to CSV')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\CompensationReportController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));

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
    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}
