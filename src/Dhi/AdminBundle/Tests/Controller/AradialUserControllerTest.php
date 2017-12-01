<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AradialUserControllerTest extends WebTestCase {

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
    public function setUp() {
        static::$kernel          = static::createKernel();
        static::$kernel->boot();
        $this->container         = static::$kernel->getContainer();
        $this->em                = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session           = static::$kernel->getContainer()->get('session');
        $this->securitycontext   = static::$kernel->getContainer()->get('security.context');
        $this->testHttpHost      = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName     = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->frontPassword     = static::$kernel->getContainer()->getParameter('test_admin_password');
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
            array(), 
            array(
                'HTTP_HOST' => $this->testHttpHost,
            )
        );

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-hisotory-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Session History")')->count());
    }

    public function testaradialUserListJsonAction(){
        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => $this->testHttpHost,
            )
        );

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-hisotory-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Session History")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-user-history-list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::aradialUserListJsonAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testprintAction(){
        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => $this->testHttpHost,
            )
        );

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-hisotory-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Session History")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-history-list-print',array("offset" => 0));
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::printAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testexportCsvAction(){
        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => $this->testHttpHost,
            )
        );

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-hisotory-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Session History")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-history-export-csv',array("offset" => 0));
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testexportpdfAction(){
        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => $this->testHttpHost,
            )
        );

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-hisotory-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Session History")')->count());

        $crawler = $client->request('GET', '/admin/user/aradial-session-history-export-pdf',array("offset" => 0));
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserController::exportpdfAction', $client->getRequest()->attributes->get('_controller'));
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