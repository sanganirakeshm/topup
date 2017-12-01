<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserActivityLogControllerTest extends WebTestCase {
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    public function testindex(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/audit-logs');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Search Audit Log")')->count());
    }
    
    
    public function testactivityLogListJson(){
            
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       $crawler = $client->request('GET', '/admin/audit-log-list-json/0');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(0, $crawler->filter('html:contains("User Admin Audit log")')->count());
        
        $crawler = $client->request('GET', '/admin/audit-log-list-json/0?admin=mahesh_superadmin');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with admin user")')->count());
        
        $crawler = $client->request('GET', '/admin/audit-log-list-json/0?sSearch_1=test12345');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with front user")')->count());
        
        $crawler = $client->request('GET', '/admin/audit-log-list-json/0?sSearch_2=Add+to+Cart+ISP');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with user/admin activity")')->count());
      
        $crawler = $client->request('GET', '/admin/audit-log-list-json/0?sSearch_4=192.168.10.122');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with ip address")')->count());
        
         $crawler = $client->request('GET', '/admin/audit-log-list-json/0?sSearch_5=2016-10-18~2016-10-27');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with date range")')->count());
        
        /*for histrocial search*/
        $crawler = $client->request('GET', '/admin/audit-log-list-json/1');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(0, $crawler->filter('html:contains("User Admin Audit log with historical")')->count());
        
        $crawler = $client->request('GET', '/admin/audit-log-list-json/1?admin=mahesh_superadmin');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with admin user historical")')->count());
        
        $crawler = $client->request('GET', '/admin/audit-log-list-json/1?sSearch_1=test12345');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with front user,historical")')->count());
        
        $crawler = $client->request('GET', '/admin/audit-log-list-json/1?sSearch_2=Add+to+Cart+ISP');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with user/admin activity")')->count());
      
        $crawler = $client->request('GET', '/admin/audit-log-list-json/1?sSearch_4=192.168.10.122');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with ip address historical")')->count());
        
         $crawler = $client->request('GET', '/admin/audit-log-list-json/1?sSearch_5=2016-10-18~2016-10-27');

        $this->assertEquals('Dhi\AdminBundle\Controller\UserActivityLogController::activityLogListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("search with date range historical")')->count());
    }
    
   /**
     *  General function for Admin login
     */
    public function doLogin($username, $password, $client) {
        
        $crawler = $client->request('POST', '/admin/login');

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
    protected function tearDown()
    {
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}

