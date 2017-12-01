<?php
namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GroupControllerTest extends WebTestCase
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

//    public function doLogin($username, $password, $client) {
//        $crawler = $client->request('POST', 'admin/login');
//        $form = $crawler->selectButton('Sign In')->form(array(
//            '_username' => $username,
//            '_password' => $password,
//        ));
//        $client->submit($form);
//        $this->assertEquals('Dhi\UserBundle\Controller\SecurityController::checkAction', $client->getRequest()->attributes->get('_controller'));
//         $client->followRedirects(true);
//    }

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

        $crawler = $client->request('GET', '/admin/group/group-list');

        
        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Add Group")')->count());
    }

    public function testGroupListJsonAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/group/group-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

        

        $crawler = $client->request('GET', '/admin/group/group-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::groupListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Records")')->count());
    }

    public function testNewAction(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/group/group-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Add Group")')->count());

        $crawler = $client->request('POST', '/admin/group/add-group');

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('add')->form();

        $form['dhi_admin_group[name]'] = 'WebTestCaseGroup';

        $client->submit($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::newAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->followRedirect();

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Group added successfully!")')->count());
    }

    public function testEditAction(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/group/group-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $sql = 'select id as lastinsertId from dhi_group ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('POST', '/admin/group/edit-group/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('update')->form();

        $form['dhi_admin_group[name]'] = 'WebTestCaseGroup_new';

        $client->submit($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::editAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->followRedirect();

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

     }

    public function testDeleteAction(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/group/group-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $sql = 'select id as lastinsertId from dhi_group ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/group/delete-group?id='.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::deleteAction', $client->getRequest()->attributes->get('_controller'));
     }

    public function testPermissionAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/group/group-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $sql = 'select id as lastinsertId,name as group_name from dhi_group ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/group/permissions/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\GroupController::permissionAction', $client->getRequest()->attributes->get('_controller'));
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
