<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChangesSalesReportControllerTest extends WebTestCase {
    
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
    
   public function testsalesReport() {
         $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('GET', '/admin/sales-reports');
        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Sales Report")')->count());
    }
    
    public function testsalesReportListJson() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
       $crawler = $client->request('GET', '/admin/sales-reports-list-json');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportListJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(0, $crawler->filter('html:contains("Admin sales report")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-reports-list-json?sSearch_1=2016-10-01~2016-10-31');

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Date range")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-reports-list-json?sSearch_0=BAF');

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Service location")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-reports-list-json/IPTV/PayPal/0?Search_1=2016-10-01~2016-10-31');

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Service type")')->count());
                
        $crawler = $client->request('GET', '/admin/sales-reports-list-json/0/PayPal/0?sSearch_1=2016-10-01~2016-10-31');

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("payment method")')->count());
        
        $crawler = $client->request('GET', '/admin/sales-reports-list-json/0/0/10000?sSearch_1=2016-10-01~2016-10-31');

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("service plan")')->count());
    }
    
    public function testexportpdfAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/sales-reports');
        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Sales Report")')->count());

        $form = $crawler->selectLink('Export PDF')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::exportpdfAction', $client->getRequest()->attributes->get('_controller'));

    }
    
    public function testexportExcelAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/sales-reports');
        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Sales Report")')->count());

        $form = $crawler->selectLink('Export Excel')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::exportExcelAction', $client->getRequest()->attributes->get('_controller'));

    }
    
    public function testexportCsvAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/sales-reports');
        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::salesReportAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Sales Report")')->count());

        $form = $crawler->selectLink('Export CSV')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\ChangesSalesReportController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));

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
