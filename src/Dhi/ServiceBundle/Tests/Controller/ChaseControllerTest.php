<?php

namespace Dhi\ServiceBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChaseControllerTest extends WebTestCase {

    protected $frontUserName;
    protected $frontUserPassword;
    protected $chase;
    protected $testHttpHost;


    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_front_username');
        $this->frontUserPassword = static::$kernel->getContainer()->getParameter('test_front_password');
        
        
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        
    }
     
    
    public function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', 'login');
        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        
        $client->followRedirect();
    }
    
    public function testgetApiGatewayUrl(){
        
        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontUserPassword, $client);
        $crawler = $client->request('POST', '/account');

        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome")')->count());    
        
        $this->chase           = static::$kernel->getContainer()->get('chase');
        $cgiUrl = $this->chase->getApiGatewayUrl();
        $this->assertFalse($cgiUrl== '', 'cgi_url is not blank');
    }
    
    public function testauthorize(){
        
        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontUserPassword, $client);
        $crawler = $client->request('POST', '/account');

        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome")')->count());    
        
        $payment = array(
            "order" => array(
                    "orderNumber"    => static::$kernel->getContainer()->getParameter('test_chase_customer_ref_no'),
                    "billingAddress" => array(
                            'postcode' => static::$kernel->getContainer()->getParameter('test_chase_postal_code')
                    )
            ),
            "custRefNo"   => static::$kernel->getContainer()->getParameter('test_chase_customer_ref_no')
        );
        $this->chase = static::$kernel->getContainer()->get('chase');
        $response = $this->chase->authorize($payment, static::$kernel->getContainer()->getParameter('test_chase_service_plan_amount'));
        $this->assertFalse($response['status'] != 'success', 'Chase process Failed');
    }
}
