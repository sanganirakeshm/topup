<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SolarWindsRequestTypeControllerTest extends WebTestCase {
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
    
    public function testindexAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
       
        $crawler = $client->request('GET', '/admin/solar-winds-location/list');
        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Assign Solar Winds Request Types")')->count());
    }
    
    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/solar-winds-location/list-json');
        
        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::solarwindsLocationListJsonAction', $client->getRequest()->attributes->get('_controller'));
    
        $crawler = $client->request('GET', '/admin/solar-winds-location/list-json?sSearch_0=dhiportal.dev.dhitelecom.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::solarwindsLocationListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("dhiportal.dev.dhitelecom.com")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);

        $crawler = $client->request('GET', '/admin/solar-winds-location/list-json?sSearch_1=ExchangeVUE');
        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::solarwindsLocationListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("ExchangeVUE")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        

        $crawler = $client->request('GET', '/admin/solar-winds-location/list-json?sSearch_2=AFG - DAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::solarwindsLocationListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("AFG - DAF")')->count();
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

        $sql   = 'SELECT id AS lastinsertId FROM solar_winds_request_type ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $resWS = $query->fetch();
        
        $sql1   = 'SELECT id AS lastinsertId,white_label_id FROM support_location ORDER BY id DESC';
        $query = $this->conn->prepare($sql1);
        $query->execute();
        $resSl = $query->fetch();


        $supportLocationId = $resSl['lastinsertId'];
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('POST', '/admin/solar-winds-location/add');

        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::newAction', $client->getRequest()->attributes->get('_controller'));
        $checkSupportLocationExists = $this->em->getRepository("DhiUserBundle:SolarWindsSupportLocation")->findOneBy(array('supportLocation' => $supportLocationId));
        if ($checkSupportLocationExists) {
            $this->assertFalse(true, 'This support location is already assigned to other solar winds request type.');
        }
        $form = $crawler->selectButton('Add')->form();

        $form['dhi_admin_solar_winds_location[supportsite]'] = $resSl['white_label_id'];
        $form['dhi_admin_solar_winds_location[supportLocation]'] = $supportLocationId;
        $form['dhi_admin_solar_winds_location[solarWindsRequestType]'] = $resWS['lastinsertId'];

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Solar winds request type assigned to support location successfully.")')->count());
    }
    
     public function testEditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $sql   = 'SELECT id AS lastinsertId FROM solar_winds_request_type WHERE is_deleted = 0 ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $resWS = $query->fetch();

        $objSolarwindLocation = $this->em->getRepository('DhiUserBundle:SolarWindsSupportLocation')->findOneBy(array('isDeleted' => 0), array('id' => 'DESC'));
        if (!$objSolarwindLocation || empty($resWS)) {
            $this->assertFalse(true, 'Unable to find record.');
        }
        $dataArr = array('dhi_admin_solar_winds_location' => array('solarWindsRequestType' => $resWS['lastinsertId']));
        $crawler = $client->request('POST', '/admin/solar-winds-location/edit/'.$objSolarwindLocation->getId(), $dataArr);

        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('Edit')->form();

        $form['dhi_admin_solar_winds_location[solarWindsRequestType]'] = $resWS['lastinsertId'];

        $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Solar Winds request type updated successfully.")')->count());
    }

    public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objSolarwindLocation = $this->em->getRepository('DhiUserBundle:SolarWindsSupportLocation')->findOneBy(array('isDeleted' => 0), array('id' => 'DESC'));
        if (!$objSolarwindLocation) {
            $this->assertFalse(true, 'Solar wind location not exist.');
        }

        $crawler = $client->request('POST', '/admin/solar-winds-location/delete?id=' . $objSolarwindLocation->getId());
        $this->assertEquals('Dhi\AdminBundle\Controller\SolarWindsRequestTypeController::deleteAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertEquals('success', $data->type);
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
    
    /**
     * {@inheritDoc}
     */
    protected function tearDown() {
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}

