<?php
namespace Dhi\ServiceBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PaypalControllerTest extends WebTestCase
{
    protected $container;
    protected $securityContext;
    protected $session;
    protected $em;
    protected $conn;
    protected $testHttpHost;
    protected $frontUserName;
    protected $frontPassword;

    /**
    * {@inheritDoc}
    */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session           = static::$kernel->getContainer()->get('session');
        $this->securitycontext   = static::$kernel->getContainer()->get('security.context');
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_front_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_front_password');

        $this->conn = static::$kernel->getContainer()
                ->get('database_connection');
    }




    public function doLogin($username, $password, $client) {
        $crawler = $client->request('POST', 'admin/login');
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));
        $client->submit($form);
        $this->assertEquals('Dhi\UserBundle\Controller\SecurityController::checkAction', $client->getRequest()->attributes->get('_controller'));
//        $client->followRedirect();
         $client->followRedirects(true);
    }
      public function doLoginUser($username, $password, $client) {

        $crawler = $client->request('POST', 'login');

        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }


    public function testpaymentConfirmAction(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLoginUser($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('POST', '/account');

        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome '.$this->frontUserName.'")')->count());

        $crawler = $client->request('POST', '/paypal/paymentconfirm');
        

        $this->assertEquals('Dhi\ServiceBundle\Controller\PaypalController::paymentconfirmAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testpurchaseverificationAction(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLoginUser($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('POST', '/account');

        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome '.$this->frontUserName.'")')->count());

        $crawler = $client->request('POST', '/service/purchaseverification');

        $this->assertEquals('Dhi\ServiceBundle\Controller\ServiceController::purchaseverificationAction', $client->getRequest()->attributes->get('_controller'));

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
