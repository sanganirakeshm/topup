<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserServiceSuspendControllerTest extends WebTestCase {
     protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
     public function testdoServiceSuspendAction(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/user/service-suspend/420');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserServiceSuspendController::doServiceSuspendAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
     }
     
     public function testdoServiceUnsuspendAction(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/user/service-unsuspend/419');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserServiceSuspendController::doServiceUnsuspendAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
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
}
