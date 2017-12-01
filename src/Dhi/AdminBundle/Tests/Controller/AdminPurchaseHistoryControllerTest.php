<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Entity\PaypalCheckout;

class AdminPurchaseHistoryControllerTest extends WebTestCase {

    protected $container;
    protected $securityContext;
    protected $session;
    protected $em;
    protected $conn;
    protected $testHttpHost;
    protected $frontUserName;
    protected $frontPassword;
    protected $aradial;
    protected $selevisionService;

    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session = static::$kernel->getContainer()->get('session');
        $this->securitycontext = static::$kernel->getContainer()->get('security.context');
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_front_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_front_password');
        $this->aradial = static::$kernel->getContainer()->get('aradial');
        $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');

        $this->conn = static::$kernel->getContainer()
                ->get('database_connection');
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

    public function testpurchaseHistoryAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);

        $crawler = $client->request('GET', '/admin/user/purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase History")')->count());
    }

    public function testpurchaseHistoryJsonAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);


        $crawler = $client->request('GET', '/admin/user/purchase-history-list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));

        
        $crawler = $client->request('GET', '/admin/user/purchase-history-list-json?sSearch_3=mahesh');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("mahesh")')->count());
        
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-list-json?sSearch_2=3X843449AH800213C');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("3X843449AH800213C")')->count());
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-list-json?sSearch_5=Paypal');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Paypal")')->count());
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-list-json?sSearch_6=Completed');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Completed")')->count());
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-list-json?sSearch_1=201608048003');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("201608048003")')->count());
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-list-json?sSearch_10=2016-08-01~2017-01-30');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aug-04-2016")')->count());
        
    }

   public function testexpandedPurchaseHistoryAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);


        $crawler = $client->request('GET', '/admin/user/purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase History")')->count());

        $sql = 'select id from purchase_order ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/expand-purchase-history?poId='.$lastInsertedId['id']);

        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::expandedPurchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Package Amount")')->count());
    }

    public function testexportpdfAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/user/purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase History")')->count());
        
        $form = $crawler->selectLink('Export PDF')->first()->link();
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-export-pdf?offset=0');

        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::exportpdfAction', $client->getRequest()->attributes->get('_controller'));

    }

    public function testprintAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/user/purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase History")')->count());
        
        $form = $crawler->selectLink('Print')->first()->link();
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-print?offset=0');

        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::printAction', $client->getRequest()->attributes->get('_controller'));

    }
    
    public function testexportCsvAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/user/purchase-history');
        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase History")')->count());
        
        $form = $crawler->selectLink('Export CSV')->first()->link();
        
        $crawler = $client->request('GET', '/admin/user/purchase-history-export-csv?offset=0');

        $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseHistoryController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
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
