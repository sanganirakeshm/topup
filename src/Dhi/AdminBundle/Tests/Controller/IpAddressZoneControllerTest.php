<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IpAddressZoneControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        
    }
    
    public function testIndexAction()
    {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/service-location-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\IpAddressZoneController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Service Location")')->count());
    }
    
    public function testAddIpAddressZone() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/add-service-location');
        $this->assertEquals('Dhi\AdminBundle\Controller\IpAddressZoneController::newAction', $client->getRequest()->attributes->get('_controller'));
        $objServices = $this->em->getRepository('DhiUserBundle:Service')->findAll();
        
        $serviceArr = array();
        if($objServices)
        {
            foreach($objServices as $objService)
            {
                $serviceArr[] = $objService->getId();
            }
            
            $from_ip = static::$kernel->getContainer()->getParameter('test_from_ip');
            $to_ip = static::$kernel->getContainer()->getParameter('test_to_ip');
            
            $testCountryIsoCode = static::$kernel->getContainer()->getParameter('test_country_iso_code');
            $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array('isoCode' => $testCountryIsoCode));
            if($objCountry)
            {
                $this->assertFalse(!filter_var($from_ip, FILTER_VALIDATE_IP));
                $this->assertFalse(!filter_var($to_ip, FILTER_VALIDATE_IP));
                $form = $crawler->selectButton('add')->form();
                $form['dhi_service_location[country]'] = $objCountry->getId();
                $form['dhi_service_location[name]'] = "Test Name";
                $form['dhi_service_location[description]'] = "Service Location Test Description";
                $form['dhi_service_location[ipAddressZones][0][fromIpAddress]'] = $from_ip;
                $form['dhi_service_location[ipAddressZones][0][toIpAddress]'] = $to_ip;
                $form['dhi_service_location[ipAddressZones][0][services]'] = $serviceArr;
                $form['dhi_service_location[ipAddressZones][0][isMilstarEnabled]'] = 1;
                $client->submit($form);
                $this->assertTrue($client->getResponse()->isRedirect()); // check if redirecting properly
                $crawler = $client->followRedirect();
                $this->assertEquals(1, $crawler->filter('html:contains("Service location added successfully.")')->count());
            }
        }
        
    }
    
    public function testEditIpAddressZone() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $objServices = $this->em->getRepository('DhiUserBundle:Service')->findAll();
        
        if($objServices)
        {
            $serviceArr = array();
            foreach($objServices as $objService)
            {
                $serviceArr[] = $objService->getId();
            }
            
            $from_ip = static::$kernel->getContainer()->getParameter('test_from_ip');
            $to_ip = static::$kernel->getContainer()->getParameter('test_to_ip');
            
            $testCountryIsoCode = static::$kernel->getContainer()->getParameter('test_country_iso_code');
            $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array('isoCode' => $testCountryIsoCode));
            if($objCountry)
            {
                $objIpAddressZone = $this->em->getRepository('DhiAdminBundle:IpAddressZone')->findOneBy(array( 'fromIpAddress' => $from_ip, 'toIpAddress' => $to_ip ));
                
                if($objIpAddressZone)
                {
                    $serviceLocationId = $objIpAddressZone->getServiceLocation()->getId();
                    $crawler = $client->request('GET', '/admin/edit-service-location/'.$serviceLocationId);
                    $this->assertEquals('Dhi\AdminBundle\Controller\IpAddressZoneController::editAction', $client->getRequest()->attributes->get('_controller'));

                    $this->assertFalse(!filter_var($from_ip, FILTER_VALIDATE_IP));
                    $this->assertFalse(!filter_var($to_ip, FILTER_VALIDATE_IP));
                    $form = $crawler->selectButton('Update')->form();
                    $form['dhi_service_location[country]'] = $objCountry->getId();
                    $form['dhi_service_location[name]'] = "Test Name - Updated";
                    $form['dhi_service_location[description]'] = "Service Location Test Description - Updated";
                    $form['dhi_service_location[ipAddressZones][0][fromIpAddress]'] = $from_ip;
                    $form['dhi_service_location[ipAddressZones][0][toIpAddress]'] = $to_ip;
                    $form['dhi_service_location[ipAddressZones][0][services]'] = $serviceArr;
                    $form['dhi_service_location[ipAddressZones][0][isMilstarEnabled]'] = 1;
                    $client->submit($form);
                    $this->assertTrue($client->getResponse()->isRedirect()); // check if redirecting properly
                    $crawler = $client->followRedirect();
                    $this->assertEquals(1, $crawler->filter('html:contains("Service location updated successfully.")')->count());
                }
            }
        }
        
    }
    
    public function testDeleteIpAddressZone() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $from_ip = static::$kernel->getContainer()->getParameter('test_from_ip');
        $to_ip = static::$kernel->getContainer()->getParameter('test_to_ip');
        $objIpAddressZone = $this->em->getRepository('DhiAdminBundle:IpAddressZone')->findOneBy(array( 'fromIpAddress' => $from_ip, 'toIpAddress' => $to_ip ));
        
        if($objIpAddressZone)
        {
            $serviceLocationId = $objIpAddressZone->getServiceLocation()->getId();
            $crawler = $client->request('GET', '/admin/delete-service-location', array(
                'id' => $serviceLocationId
            ));
            $this->assertEquals('Dhi\AdminBundle\Controller\IpAddressZoneController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            $jsonResponse = $client->getResponse()->getContent();
            $decodeResponse = json_decode($jsonResponse);
            $this->assertEquals('Service location deleted successfully.', $decodeResponse->message);
        }    
        
    }
    
    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', 'admin/login');
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => static::$kernel->getContainer()->getParameter('test_admin_username'),
            '_password' => static::$kernel->getContainer()->getParameter('test_admin_password'),
        ));
        
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
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
