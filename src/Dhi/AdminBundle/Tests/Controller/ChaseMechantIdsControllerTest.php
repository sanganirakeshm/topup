<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ChaseMechantIdsControllerTest extends WebTestCase {

    /**
     * {@inheritDoc}
     */
    protected $container;
    protected $securityContext;
    protected $session;
    protected $em;
    protected $conn;
    protected $testHttpHost;
    protected $adminUserName;
    protected $adminPassword;

    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->adminUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->adminPassword = static::$kernel->getContainer()->getParameter('test_admin_password');

        $this->conn = static::$kernel->getContainer()
                ->get('database_connection');
    }

    public function doLogin($username, $password, $client) {

        $crawler = $client->request('GET', '/admin/login');

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
        $crawler = $client->request('GET', '/admin/chase-merchantids-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Chase Merchant")')->count());
    }

    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/chase-merchantids-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/chase-merchantids-list-json?sSearch_0=267889');
        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("267889")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        
        $crawler = $client->request('GET', '/admin/chase-merchantids-list-json?sSearch_6=Enable');
        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Enable")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testnewAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $merchantId = static::$kernel->getContainer()->getParameter('test_chase_merchant_id_new');
        $merchantName = static::$kernel->getContainer()->getParameter('test_chase_merchant_name');
        $crawler = $client->request('POST', '/admin/chase-merchantids/new');
        
        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::newAction', $client->getRequest()->attributes->get('_controller'));
        $checkMerchantId = $this->em->getRepository('DhiAdminBundle:ChaseMerchantIds')->checkUniqueMerchantId($merchantId, $merchantName);
        if ($checkMerchantId) {
            $this->assertFalse(true, 'This merchant name or merchant id is already exists!');
        }
        $form = $crawler->selectButton('Add')->form();

        $form['dhi_admin_chase_merchatids[merchantName]'] = $merchantName;
        $form['dhi_admin_chase_merchatids[merchantId]'] = $merchantId;
        $form['dhi_admin_chase_merchatids[isDefault]'] = 1;


        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Chase merchant id added successfully.")')->count());
    }

    public function testEditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objChaseMerchantIds = $this->em->getRepository('DhiAdminBundle:ChaseMerchantIds')->findOneBy(array(), array('id' => 'DESC'));
        if (!$objChaseMerchantIds) {
            $this->assertFalse(true, 'Chase merchant id record does not exists.');
        }
        $crawler = $client->request('POST', '/admin/chase-merchantids/edit/' . $objChaseMerchantIds->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::editAction', $client->getRequest()->attributes->get('_controller'));
        $merchantName = static::$kernel->getContainer()->getParameter('test_chase_merchant_name_update');
        $checkMerchantId = $this->em->getRepository('DhiAdminBundle:ChaseMerchantIds')->checkUniqueMerchantId($objChaseMerchantIds->getId(), $merchantName);
        if ($checkMerchantId) {
            $this->assertFalse(true, 'This merchant name or merchant id is already exists!');
        }
        $form = $crawler->selectButton('Edit')->form();
        
        $form['dhi_admin_chase_merchatids[merchantName]'] = $merchantName;
        $form['dhi_admin_chase_merchatids[merchantId]'] = static::$kernel->getContainer()->getParameter('test_chase_merchant_id_new_update');

        $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Chase merchant id updated successfully")')->count());
    }

    public function testsetDefaultMerchantIdAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objChaseMerchantIds = $this->em->getRepository('DhiAdminBundle:ChaseMerchantIds')->findOneBy(array(), array('id' => 'DESC'));
        if (!$objChaseMerchantIds) {
            $this->assertFalse(true, 'Chase merchant id record does not exists.');
        }

        $crawler = $client->request('POST', '/admin/chase-merchantids/set-default?id='.$objChaseMerchantIds->getId());
        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::setDefaultMerchantIdAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->type != 'success', $data->message);
    }
    
    public function testactiveInactiveAction(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objChaseMerchantIds = $this->em->getRepository('DhiAdminBundle:ChaseMerchantIds')->findOneBy(array(), array('id' => 'DESC'));
        if (!$objChaseMerchantIds) {
            $this->assertFalse(true, 'Chase merchant id record does not exists.');
        }
        $status = $objChaseMerchantIds->getIsActive() ? 0 : 1;
        $crawler = $client->request('POST', '/admin/chase-merchantids/active-inactive?id='.$objChaseMerchantIds->getId().'&status='.$status);
        $this->assertEquals('Dhi\AdminBundle\Controller\ChaseMechantIdsController::activeInactiveAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->type != 'success', $data->message);
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
