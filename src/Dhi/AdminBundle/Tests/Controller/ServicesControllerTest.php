<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServicesControllerTest extends WebTestCase
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
        $crawler = $client->request('POST', '/admin/service/service-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServicesController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Services")')->count());
    }
     
    public function testAddServices() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/service/add-service');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServicesController::newAction', $client->getRequest()->attributes->get('_controller'));
        $form = $crawler->selectButton('add')->form();
        $form['dhi_service_add[name]'] = static::$kernel->getContainer()->getParameter('test_service_name');
        $form['dhi_service_add[status]'] = static::$kernel->getContainer()->getParameter('test_service_status');
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/admin/service/service-list'));
        $crawler = $client->followRedirect();
        $this->assertEquals(1, $crawler->filter('html:contains("Service added successfully!")')->count());
    }
    
    public function testEditServices() {    
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $objService = $this->em->getRepository('DhiUserBundle:Service')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objService) != 1, 'Service not found');
        
        $crawler = $client->request('POST', '/admin/service/edit-service/'.$objService->getId());
        $this->assertEquals('Dhi\AdminBundle\Controller\ServicesController::editAction', $client->getRequest()->attributes->get('_controller'));
        $form = $crawler->selectButton('update')->form();
        $form['dhi_service_add[name]'] = static::$kernel->getContainer()->getParameter('test_service_name');
        $form['dhi_service_add[status]'] = 0;
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect('/admin/service/service-list')); // check if redirecting properly
        $crawler = $client->followRedirect();
        $this->assertEquals(1, $crawler->filter('html:contains("Service updated successfully!")')->count());
    }
    
    
    public function testDeleteServices() {
        $client = static::createClient(
                    array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $objService = $this->em->getRepository('DhiUserBundle:Service')->findOneBy(array(), array('id' => 'DESC'));
        if($objService)
        {
            $crawler = $client->request('POST', '/admin/service/delete-service', array(
                'id' => $objService->getId()
            ));
            $this->assertEquals('Dhi\AdminBundle\Controller\ServicesController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            $jsonResponse = $client->getResponse()->getContent();
            $decodeResponse = json_decode($jsonResponse);
            $this->assertEquals('Service deleted successfully!', $decodeResponse->message);
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