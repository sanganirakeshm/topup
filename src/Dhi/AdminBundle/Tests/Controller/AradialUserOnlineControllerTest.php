<?php

namespace Dhi\UserBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AradialUserOnlineControllerTest extends WebTestCase {

    
    protected $testHttpHost;
    protected $adminUserName;
    protected $adminPassword;
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->adminUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->adminPassword = static::$kernel->getContainer()->getParameter('test_admin_password');

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', 'admin/login');
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }

    public function testindexAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/user/aradial-online-user-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserOnlineController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Aradial Online User")')->count());
    }

    public function testaradialUserOnlineListJsonAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        
        $crawler = $client->request('GET', '/admin/user/aradial-online-user-list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserOnlineController::aradialUserOnlineListJsonAction', $client->getRequest()->attributes->get('_controller'));
        
        $crawler = $client->request('GET', '/admin/user/aradial-online-user-list-json?sSearch_0=testUsername');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserOnlineController::aradialUserOnlineListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("testUsername")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);

        
        $crawler = $client->request('GET', '/admin/user/aradial-online-user-list-json?sSearch_1=testNasName');
        $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserOnlineController::aradialUserOnlineListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("testNasName")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        
    }

    public function testdisconnectOnlineUserSessionAction(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $objUserOnlineSession = $this->em->getRepository('DhiAdminBundle:UserOnlineSession')->findOneBy(array(), array('id' => 'DESC'));
        if($objUserOnlineSession){
            $crawler = $client->request('GET', '/admin/user/aradial-disconnect-user?id=test');
            $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserOnlineController::disconnectOnlineUserSessionAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("Aradial Online User")')->count());

            $crawler = $client->request('GET', '/admin/user/aradial-online-user-list');
            $this->assertEquals('Dhi\AdminBundle\Controller\AradialUserOnlineController::indexAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("User disconnected successfully.")')->count());
        }
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
