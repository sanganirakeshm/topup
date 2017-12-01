<?php

namespace Dhi\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Entity\PaypalCheckout;

class DeersAuthenticationControllerTest extends WebTestCase {

    protected $container;
    protected $securityContext;
    protected $session;
    protected $em;
    protected $conn;
    protected $testHttpHost;
    protected $frontUserName;
    protected $frontPassword;
    protected $DeersAuthentication;

    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session = static::$kernel->getContainer()->get('session');
        $this->securitycontext = static::$kernel->getContainer()->get('security.context');
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_front_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_front_password');
        $this->DeersAuthentication = static::$kernel->getContainer()->get('DeersAuthentication');

        $this->conn = static::$kernel->getContainer()
                ->get('database_connection');
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

    public function testindexAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/account');
        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Select Your Plan or Upgrade Your Current Plan")')->count());
    }

    public function testcheckDeersAuthenticated(){
         $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        $isDeersAuthenticated = $this->DeersAuthentication->checkDeersAuthenticated('', $objUser->getId());

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
