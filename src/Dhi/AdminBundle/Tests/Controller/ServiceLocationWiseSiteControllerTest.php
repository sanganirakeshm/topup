<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServiceLocationWiseSiteControllerTest extends WebTestCase {

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
        $crawler = $client->request('GET', '/admin/service-location-to-site/list');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Service Location Wise Site")')->count());
    }

    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/service-location-to-site/list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/service-location-to-site/list-json?sSearch_0=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/service-location-to-site/list-json?sSearch_1=portal');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("portal")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/service-location-to-site/list-json?sSearch_2=192');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("192")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testnewAction() {

        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => $this->testHttpHost
            )
        );

        $sql   = 'SELECT id AS lastinsertId FROM white_label WHERE is_deleted = 0 ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $resWS = $query->fetch();

        $sql   = 'SELECT id AS lastinsertId FROM service_location ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $resSl = $query->fetch();


        $serviceLocationId = $resSl['lastinsertId'];
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('POST', '/admin/service-location-to-site/new');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::newAction', $client->getRequest()->attributes->get('_controller'));
        $checkServiceLocationExists = $this->em->getRepository("DhiAdminBundle:ServiceLocationWiseSite")->findOneBy(array('serviceLocation' => $serviceLocationId));
        if ($checkServiceLocationExists) {
            $this->assertFalse(true, 'This service location is already assigned to other site.');
        }
        $form = $crawler->selectButton('Add')->form();

        $form['dhi_admin_service_location_wise_site[serviceLocation]'] = $serviceLocationId;
        $form['dhi_admin_service_location_wise_site[whiteLabel]'] = $resWS['lastinsertId'];

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Service location wise site assigned successfully.")')->count());
    }

    public function testEditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $sql   = 'SELECT id AS lastinsertId FROM white_label WHERE is_deleted = 0 ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $resWS = $query->fetch();

        $objServiceLocationWiseSite = $this->em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findOneBy(array(), array('id' => 'DESC'));
        if (!$objServiceLocationWiseSite) {
            $this->assertFalse(true, 'Unable to find record.');
        }
        $crawler = $client->request('POST', '/admin/service-location-to-site/edit/' . $objServiceLocationWiseSite->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('Update')->form();

        $form['dhi_admin_service_location_wise_site[whiteLabel]'] = $resWS['lastinsertId'];

        $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Service location wise site updated successfully.")')->count());
    }

    public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objServiceLocationWiseSite = $this->em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findOneBy(array('isDeleted' => 0), array('id' => 'DESC'));
        if (!$objServiceLocationWiseSite) {
            $this->assertFalse(true, 'Service location wise site does not exist.');
        }

        $crawler = $client->request('POST', '/admin/service-location-to-site/delete?id=' . $objServiceLocationWiseSite->getId());
        $this->assertEquals('Dhi\AdminBundle\Controller\ServiceLocationWiseSiteController::deleteAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertEquals('success', $data->type);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown() {
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }

}
