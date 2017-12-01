<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SupportServiceControllerTest extends WebTestCase
{
    protected $serviceNameNew;
    protected $statusNew;
    protected $serviceNameUpdate;
    protected $statusUpdate;
    /**
     * {@inheritDoc}
     */
    protected function setUp() {
       static::$kernel = static::createKernel();
       static::$kernel->boot();
       
       $this->em       = static::$kernel->getContainer()->get('doctrine')->getManager(); 
       $this->conn     = static::$kernel->getContainer()->get('database_connection');
       
       $this->serviceNameNew = static::$kernel->getContainer()->getParameter('test_support_service_name_new');
       $this->statusNew      = static::$kernel->getContainer()->getParameter('test_support_service_status_new');
       
       $this->serviceNameUpdate = static::$kernel->getContainer()->getParameter('test_support_service_name_update');
       $this->statusUpdate      = static::$kernel->getContainer()->getParameter('test_support_service_status_update');
    }
    
    public function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', 'admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }
    
    public function testIndexAction() {
        $client = static::createClient(
            array(), array(
            'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/support-service-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Support Service")')->count());
    }
    
    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/support-service/list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/support-service/list-json?sSearch_0=ISP');
        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("ISP")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/support-service/list-json?sSearch_2=admin');
        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("admin")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/support-service/list-json?sSearch_1=Active');
        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Active")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }
    
    public function testnewAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/support-service/new');

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('Add')->form();
        $form['dhi_user_support_service[serviceName]'] = $this->serviceNameNew;
        $form['dhi_user_support_service[isActive]']    = $this->statusNew;

        $client->submit($form);

        $crawler = $client->getCrawler();
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Support service added successfully.")')->count());
    }
    
    public function testeditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $sql = 'select id as lastinsertId from support_service WHERE is_deleted = 0 ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $lastInsertedId = $query->fetch();
        
        if(empty($lastInsertedId)){
            $this->assertFalse(true, 'Unable to find support service.');
        }
        
        $crawler = $client->request('POST', '/admin/support-service/edit-service/'. $lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('Update')->form();
        $form['dhi_user_support_service[serviceName]'] = $this->serviceNameUpdate;
        $form['dhi_user_support_service[isActive]']    = $this->statusUpdate;

        $client->submit($form);

        $crawler = $client->getCrawler();
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Support service updated successfully.")')->count());
    }
    
    public function testdeleteAction() {
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('POST', '/admin/support-service-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Support Service")')->count());
        
        $sql = 'select id as lastinsertId from support_service WHERE is_deleted = 0 ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/support-service/delete-service?id='. $lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportServiceController::deleteAction', $client->getRequest()->attributes->get('_controller'));
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
