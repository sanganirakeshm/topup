<?php
namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SetTopBoxControllerTest extends WebTestCase
{
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
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session           = static::$kernel->getContainer()->get('session');
        $this->securitycontext   = static::$kernel->getContainer()->get('security.context');
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_admin_password');
        $this->aradial = static::$kernel->getContainer()->get('aradial');
        $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');
        $this->conn = static::$kernel->getContainer()->get('database_connection');
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

    public function testindexAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/set-top-box-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Set Top Box")')->count());
    }

    public function testsetTopBoxListJsonAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

         $crawler = $client->request('GET', '/admin/set-top-box-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Set Top Box")')->count());

        $crawler = $client->request('GET', '/admin/set-top-box-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::setTopBoxListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $jsontype = gettype($crawler);
        
        $this->assertEquals('object', $jsontype);
    }

    public function testnewAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/add-new-set-top-box');

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::newAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Add Set Top Box")')->count());

         $form = $crawler->selectButton('add')->form();

        $form['dhi_set_top_box[macAddress]'] = 'ac-sd-w1-d3-23-34';
        $form['dhi_set_top_box[givenAt]'] = '10-27-2016';
        $form['dhi_set_top_box[user]'] = '4';
        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Set Top Box added successfully.")')->count());
    }

    public function testeditAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $sql = 'select id as lastInsertedId from settopbox  ORDER BY id DESC' ;

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/edit-set-top-box/'.$lastInsertedId['lastInsertedId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::editAction', $client->getRequest()->attributes->get('_controller'));

//        $permission = true;
//         $getresult = $crawler->filter('html:contains("Edit Set Top Box")')->count();
//        if ($getresult == 0) {
//            $permission = false;
//            $getresult = $crawler->filter('html:contains("You are not allowed to update set-top-box.")')->count();
//        }
//        $this->assertEquals(1, $getresult);
//
//        if($permission == true){
//            $form = $crawler->selectButton('add')->form();
//            $form['dhi_set_top_box[macAddress]'] = 'ac-sd-w1-d3-23-34';
//            $form['dhi_set_top_box[givenAt]'] = '10-27-2016';
//            $form['dhi_set_top_box[user]'] = '4';
//            $client->submit($form);
//            $client->followRedirect(true);
//            $crawler = $client->getCrawler();
//            $this->assertEquals(1, $crawler->filter('html:contains("Set-top-box updated successfully.")')->count());
//        }
    }

    public function testreturnAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $sql = 'select id as lastInsertedId from settopbox  ORDER BY id DESC' ;

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/retun-set-top-box/'.$lastInsertedId['lastInsertedId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::returnAction', $client->getRequest()->attributes->get('_controller'));

        $permission = true;
         $getresult = $crawler->filter('html:contains("Return Set Top Box")')->count();
        if ($getresult == 0) {
            $permission = false;
            $getresult = $crawler->filter('html:contains("You are not allowed to return set-top-box.")')->count();
        }
        $this->assertEquals(1, $getresult);

        if($permission == true){
            $form = $crawler->selectButton('Return')->form();
            $form['dhi_return_set_top_box[macAddress]'] = 'ac-sd-w1-d3-23-34';
            $form['dhi_return_set_top_box[givenAt]'] = '10-27-2016';
            $client->submit($form);
            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("Set Top Box Returned successfully.")')->count());
        }
    }

    public function testdeleteAction(){
        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $sql = 'select id as lastInsertedId from settopbox  ORDER BY id DESC' ;

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/delete-set-top-box?id='.$lastInsertedId['lastInsertedId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::deleteAction', $client->getRequest()->attributes->get('_controller'));
        
    }
    public function teststbMacAddressAction(){
                $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $sql = 'select id as lastInsertedId from settopbox  ORDER BY id DESC' ;

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/stb-mac-address?id='.$lastInsertedId['lastInsertedId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\SetTopBoxController::stbMacAddressAction', $client->getRequest()->attributes->get('_controller'));

        $type = gettype($crawler);

        $this->assertEquals('object',$type);

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
