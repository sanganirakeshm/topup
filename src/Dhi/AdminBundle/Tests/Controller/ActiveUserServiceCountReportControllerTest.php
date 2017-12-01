<?php
namespace Dhi\UserBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActiveUserServiceCountReportControllerTest extends WebTestCase {
    
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
    
    /**
     * This action allows to list product
     */
     public function testindex(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/active-service-count-report');

        $this->assertEquals('Dhi\AdminBundle\Controller\ActiveUserServiceCountReportController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("List of Active user service count report")')->count());
     }
     
     public function testactiveUserServiceCountListJson(){
         
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/active-service-count-list-json');
        
        $this->assertEquals(0, $crawler->filter('html:contains("Active user service count report")')->count());
          
        $crawler = $client->request('GET', '/admin/active-service-count-list-json?serviceLocation=BAF');

        $this->assertEquals('Dhi\AdminBundle\Controller\ActiveUserServiceCountReportController::activeUserServiceCountListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Active user service count report search by service location")')->count());
        
       
        $crawler = $client->request('GET', '/admin/active-service-count-list-json?serviceType=IPTV');

        $this->assertEquals('Dhi\AdminBundle\Controller\ActiveUserServiceCountReportController::activeUserServiceCountListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("IPTV")')->count());
        
         $crawler = $client->request('GET', '/admin/active-service-count-list-json?toActiveDate=05/10/2016');

        $this->assertEquals('Dhi\AdminBundle\Controller\ActiveUserServiceCountReportController::activeUserServiceCountListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

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
    
    /**
     * {@inheritDoc}
     */
    protected function tearDown(){
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}

