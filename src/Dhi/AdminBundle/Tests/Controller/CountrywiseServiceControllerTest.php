<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CountrywiseServiceControllerTest extends WebTestCase
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
    
    public function testIndexAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/service/countrywise-service-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\CountrywiseServiceController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Countrywise Services")')->count());
    }
     
    public function testAddCountrywiseServices() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/service/add-countrywise-service');
        
        $this->assertEquals('Dhi\AdminBundle\Controller\CountrywiseServiceController::newAction', $client->getRequest()->attributes->get('_controller'));
        $test_country_iso = static::$kernel->getContainer()->getParameter('test_country_iso_code');
        
        $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array( 'isoCode' => $test_country_iso ));
        $objServices = $this->em->getRepository('DhiUserBundle:Service')->findAll();
        
        $serviceArr = array();
        if($objServices) {
            foreach($objServices as $objService) {
                $serviceArr[] = $objService->getId();
            }
        }
        
        $form = $crawler->selectButton('add')->form();
        $form['dhi_countrywise_service_add[country]'] = $objCountry->getId();
        $form['dhi_countrywise_service_add[services]'] = $serviceArr;
        $form['dhi_countrywise_service_add[status]'] = 1;
        $form['dhi_countrywise_service_add[isShowOnLanding]'] = 1;        
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $crawler = $client->followRedirect();
        $this->assertEquals(1, $crawler->filter('html:contains("Service(s) added successfully!")')->count());
    }
    
    
    public function testEditServices() {    
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $test_country_iso = static::$kernel->getContainer()->getParameter('test_country_iso_code');
        $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array( 'isoCode' => $test_country_iso ));
        $objCountryWiseServices = $this->em->getRepository('DhiUserBundle:CountrywiseService')->findBy(array('country' => $objCountry));
        if($objCountryWiseServices) {
            foreach ($objCountryWiseServices as $objCountryWiseService)
            {
                $crawler = $client->request('GET', '/admin/service/edit-countrywise-service/'.$objCountryWiseService->getId());
                $this->assertEquals('Dhi\AdminBundle\Controller\CountrywiseServiceController::editAction', $client->getRequest()->attributes->get('_controller'));
                $form = $crawler->selectButton('Update')->form();
                $form['dhi_countrywise_service_add[status]'] = 1;
                $client->submit($form);
                $this->assertTrue($client->getResponse()->isRedirect());
                $crawler = $client->followRedirect();
                $this->assertEquals(1, $crawler->filter('html:contains("Countrywise Service updated successfully!")')->count());
            }   
        }
    }
    
    public function testChangeShowonLandingAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $test_country_iso = static::$kernel->getContainer()->getParameter('test_country_iso_code');
        $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array( 'isoCode' => $test_country_iso ));
        if($objCountry) {
            $crawler = $client->request('GET', '/admin/service/show-on-landing/'.$objCountry->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\CountrywiseServiceController::changeShowonLangingAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertTrue($client->getResponse()->isRedirect());
            $crawler = $client->followRedirect();
            $this->assertEquals(1, $crawler->filter('html:contains("Countrywise Service updated successfully!")')->count());
        }
    }
    
    public function testDeleteServices() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $test_country_iso = static::$kernel->getContainer()->getParameter('test_country_iso_code');
        $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array( 'isoCode' => $test_country_iso ));
        $objCountryWiseServices = $this->em->getRepository('DhiUserBundle:CountrywiseService')->findBy(array('country' => $objCountry));
        if($objCountryWiseServices) {
            foreach ($objCountryWiseServices as $objCountryWiseService)
            {
                $crawler = $client->request('GET', '/admin/service/delete-countrywise-service', array(
                    'id' => $objCountryWiseService->getId()
                ));
                $this->assertEquals('Dhi\AdminBundle\Controller\CountrywiseServiceController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            }   
        }
    }
    
    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', '/admin/login');
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