<?php

namespace Dhi\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Entity\PaypalCheckout;

class PurchaseHistoryControllerTest extends WebTestCase {

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

        $crawler = $client->request('POST', 'login');

        $form = $crawler->selectButton('Login')->form(array(
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

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/account');
        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Select Your Plan or Upgrade Your Current Plan")')->count());

        $crawler = $client->request('GET', '/purchase-history');
        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase Details")')->count());
    }

    public function testpurchaseHistoryJsonAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/account');
        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Select Your Plan or Upgrade Your Current Plan")')->count());

        $crawler = $client->request('GET', '/purchase-history');
        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase Details")')->count());

        $crawler = $client->request('GET', '/purchase-history-list-json');
        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::purchaseHistoryJsonAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testexpandedPurchaseHistoryAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/account');
        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Select Your Plan or Upgrade Your Current Plan")')->count());

        $crawler = $client->request('GET', '/purchase-history');
        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Purchase Details")')->count());

        $sql = 'select id as orderNumber from purchase_order ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/expand-purchase-history?poId='.$lastInsertedId['orderNumber']);

        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::expandedPurchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));

        $getresult = $crawler->filter('html:contains("Package Amount")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found")')->count();
        }
        $this->assertEquals(1, $getresult);

    }

    public function testexportpdfAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/account');

        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Select Your Plan or Upgrade Your Current Plan")')->count());

        $this->assertEquals(1, $crawler->filter('html:contains("Purchase History")')->count());

        $form = $crawler->selectLink('Purchase History')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Purchase Details")')->count());

        $form = $crawler->selectLink('Export PDF')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::exportpdfAction', $client->getRequest()->attributes->get('_controller'));

    }

    public function testprintAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/account');

        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Select Your Plan or Upgrade Your Current Plan")')->count());

        $this->assertEquals(1, $crawler->filter('html:contains("Purchase History")')->count());

        $form = $crawler->selectLink('Purchase History')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::purchaseHistoryAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Purchase Details")')->count());

        $form = $crawler->selectLink('Print')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\UserBundle\Controller\PurchaseHistoryController::printAction', $client->getRequest()->attributes->get('_controller'));

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
