<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Entity\PaypalCheckout;

class AradialPaymentHistoryControllerTest extends WebTestCase {

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
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_admin_password');
        $this->aradial = static::$kernel->getContainer()->get('aradial');
        $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');

        $this->conn = static::$kernel->getContainer()
                ->get('database_connection');
    }

     public function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', '/admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testindexAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/aradial-purchase-history');

        $this->assertEquals('Dhi\AdminBundle\Controller\AradialPaymentHistoryController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Purchase History")')->count());
    }

//
    public function testHistoryJsonAction() {

          $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/aradial-purchase-history');

        $this->assertEquals('Dhi\AdminBundle\Controller\AradialPaymentHistoryController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Purchase History")')->count());

        $crawler = $client->request('GET', '/admin/aradial-purchase-history-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\AradialPaymentHistoryController::historyListJsonAction', $client->getRequest()->attributes->get('_controller'));

    }

    public function testimoprtAction(){
              $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/aradial-purchase-history');

        $this->assertEquals('Dhi\AdminBundle\Controller\AradialPaymentHistoryController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Purchase History")')->count());


        $form = $crawler->selectLink('Import History')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\AradialPaymentHistoryController::importAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Import Aradial Purchase History")')->count());

        $form = $crawler->selectButton('Import')->form();
        
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialPaymentHistoryController::importAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Import Aradial Purchase History")')->count());

        $this->assertEquals(1, $crawler->filter('html:contains("Please select file.")')->count());
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
