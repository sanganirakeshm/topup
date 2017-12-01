<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TikiliveActiveUserControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() {
       static::$kernel = static::createKernel();
       static::$kernel->boot();
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
        
        $crawler = $client->request('POST', '/admin/tikilive-active-user');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Tikilive Active User")')->count());
    }
    
    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/tikilive-active-user-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/tikilive-active-user-list-json?sSearch_0=bkpbv_stevenprzy ');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("bkpbv_stevenprzy ")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-active-user-list-json?sSearch_0=bkpbv_stevenprzy');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("www.reliancejio.com")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-active-user-list-json?sSearch_1=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
                
        $crawler = $client->request('GET', '/admin/tikilive-active-user-list-json?sSearch_3=82.77.232.192');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("82.77.232.192")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-active-user-list-json?sSearch_3=05/01/2017~05/31/2017');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("May-19-2017")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }
    
    public function testexportCsvAction() {

        $client = static::createClient(
            array(), array(
            'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/tikilive-active-user');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Tikilive Active User")')->count());
        
        $form = $crawler->selectLink('Export CSV')->first()->link();
        $crawler = $client->request('GET', '/admin/tikilive-active-user/export-csv?offset=0');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikiliveActiveUserController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
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
