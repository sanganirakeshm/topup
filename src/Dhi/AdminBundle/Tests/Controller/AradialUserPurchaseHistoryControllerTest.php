<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AradialUserPurchaseHistoryControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() {
       static::$kernel = static::createKernel();
       static::$kernel->boot();
       
       $this->em       = static::$kernel->getContainer()->get('doctrine')->getManager(); 
       $this->conn     = static::$kernel->getContainer()->get('database_connection');
    }
    
    public function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', 'admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }
    
    public function testIndexAction() {
        $client = static::createClient(
            array(), array(
            'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/user/aradial-user-purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Aradial User Purchase History")')->count());
    }
    
    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-list-json?sSearch_3=mahesh23');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("mahesh23")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-list-json?sSearch_2=65193578NK4883747');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("65193578NK4883747")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-list-json?sSearch_5=Aradial Migrate');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Aradial Migrate")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-list-json?sSearch_6=Completed');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Completed")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-list-json?sSearch_9=2016-08-01~2016-08-31');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Aug-03-2016")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }
    
    public function testexportpdfAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/user/aradial-user-purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aradial User Purchase History")')->count());
        
        $form = $crawler->selectLink('Export PDF')->first()->link();
        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-export-pdf?offset=0');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::exportpdfAction', $client->getRequest()->attributes->get('_controller'));
    }
    
    public function testexportCsvAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/user/aradial-user-purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aradial User Purchase History")')->count());
        
        $form = $crawler->selectLink('Export CSV')->first()->link();
        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-export-csv?offset=0');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
    }
    
     public function testprintAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/user/aradial-user-purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aradial User Purchase History")')->count());
        
        $form = $crawler->selectLink('Print')->first()->link();
        $crawler = $client->request('GET', '/admin/user/aradial-user-purchase-history-print?offset=0');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserPurchaseHistoryController::printAction', $client->getRequest()->attributes->get('_controller'));

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
