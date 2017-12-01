<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServiceLocationWiseChaseMerchantIdControllerTest extends WebTestCase {

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
        $crawler = $client->request('GET', '/admin/service-location-chase-merchantids-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseChaseMerchantIdController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Service Location Wise Chase Merchant Id")')->count());
    }

    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/service-location-chase-merchantids-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseChaseMerchantIdController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/service-location-chase-merchantids-list-json?sSearch_0=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseChaseMerchantIdController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/service-location-chase-merchantids-list-json?sSearch_1=267889');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseChaseMerchantIdController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("267889")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
    }

    public function testnewAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $serviceLocationId = static::$kernel->getContainer()->getParameter('test_service_location_wise_mid_location_id_new');
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('POST', '/admin/service-location-chase-merchantids/new');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseChaseMerchantIdController::newAction', $client->getRequest()->attributes->get('_controller'));
        $checkServiceLocationExists = $this->em->getRepository("DhiAdminBundle:ServiceLocationWiseChaseMerchantId")->findOneBy(array('serviceLocation' => $serviceLocationId, 'isDeleted' => 0));
        if($checkServiceLocationExists){
            $this->get('session')->getFlashBag()->add('failure', "Service location is already added.");
        }
        $form = $crawler->selectButton('Add')->form();

        $form['dhi_admin_service_location_wise_chase_merchantId[serviceLocation]'] = $serviceLocationId;
        $form['dhi_admin_service_location_wise_chase_merchantId[chaseMerchantIds]'] = static::$kernel->getContainer()->getParameter('test_service_location_wise_mid_new');

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Chase merchant id assigned successfully.")')->count());
    }

    public function testEditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objServiceLocationWiseChaseMerchantId = $this->em->getRepository('DhiAdminBundle:ServiceLocationWiseChaseMerchantId')->findOneBy(array('isDeleted' => 0), array('id' => 'DESC'));
        if (!$objServiceLocationWiseChaseMerchantId) {
            $this->assertFalse(true, 'Service location wise mid does not exist.');
        }
        $crawler = $client->request('POST', '/admin/service-location-chase-merchantids/edit/' . $objServiceLocationWiseChaseMerchantId->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseChaseMerchantIdController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('Edit')->form();

        $form['dhi_admin_service_location_wise_chase_merchantId[chaseMerchantIds]'] = static::$kernel->getContainer()->getParameter('test_service_location_wise_mid_update');

        $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Service location wise chase merchant id updated successfully.")')->count());
    }

    public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objServiceLocationWiseChaseMerchantId = $this->em->getRepository('DhiAdminBundle:ServiceLocationWiseChaseMerchantId')->findOneBy(array('isDeleted' => 0), array('id' => 'DESC'));
        if (!$objServiceLocationWiseChaseMerchantId) {
            $this->assertFalse(true, 'Service location wise mid does not exist.');
        }

        $crawler = $client->request('POST', '/admin/service-location-chase-merchantids-delete?id='.$objServiceLocationWiseChaseMerchantId->getId());
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseChaseMerchantIdController::deleteAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertEquals('success', $data->type);
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
