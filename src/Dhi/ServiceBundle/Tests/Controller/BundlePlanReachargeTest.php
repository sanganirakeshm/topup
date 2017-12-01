<?php
namespace Dhi\ServiceBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Entity\PaypalCheckout;

class BundlePlanReachargeTest extends WebTestCase
{
    protected $container;
    protected $securityContext;
    protected $session;
    protected $em;
    protected $conn;
    protected $testHttpHost;
    protected $frontUserName;
    protected $frontPassword;
    protected $aradial;
      protected $selevisionService;
      
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
         $this->aradial = static::$kernel->getContainer()->get('aradial');
         $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');

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


    public function testRecharge(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLoginUser($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('POST', '/account');

        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::indexAction', $client->getRequest()->attributes->get('_controller'));


        $this->assertEquals(1, $crawler->filter('html:contains("Welcome")')->count());

        /*
         * Service controller / ajaxAddToCartBundleAction
         */

        $pid = 10011306;
        $packageType = 'BUNDLE';
        $service = 'BUNDLE';
        $isAddonsPack = 0;

        // add into bundle
        $requestParams = "pid=" . $pid . '&pid=' . $pid . '&packageType=' . $packageType . '&service=' . $service . '&isAddonsPack=' . $isAddonsPack  ;
        $crawler = $client->request('POST', '/add-to-cart-bundle/false?'.$requestParams);

        $this->assertEquals('Dhi\ServiceBundle\Controller\ServiceController::ajaxAddToCartBundleAction', $client->getRequest()->attributes->get('_controller'));

        /*
         * Service controller / purchaseverification
         */

        $crawler = $client->request('POST', '/service/purchaseverification');
        $this->assertEquals(1, $crawler->filter('html:contains("Order Summary")')->count());
        $this->assertEquals('Dhi\ServiceBundle\Controller\ServiceController::purchaseverificationAction', $client->getRequest()->attributes->get('_controller'));

        $isStepOneCompleted = "0";
        $discountCode = '';
        $paybleAmount = "35.99";
        $_token =   "8680bb4f6066073aab5131820d8fc747d01f36c2";

        $tempap = array(
        'isStepOneCompleted' => "0",
        'discountCode' => '',
        'paybleAmount' => "35.99",
        'form' => array(
        '_token' =>   "8680bb4f6066073aab5131820d8fc747d01f36c2" ));

        // add into bundle
        $requestParams = "isStepOneCompleted=" . $isStepOneCompleted . '&discountCode=' . $discountCode . '&paybleAmount=' . $paybleAmount . '&_token=' . $_token  ;

        $crawler = $client->request('POST', '/confirm-payment-detail/1',$tempap,  array(), array(
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $this->assertEquals('Dhi\ServiceBundle\Controller\ConfirmOrderController::confirmPaymentDetailAction', $client->getRequest()->attributes->get('_controller'));


        $formdatastep2 =  array (
            "isStepOneCompleted"=> "0",
            "discountCode"=> "",
            "paybleAmount"=> "35.99",
            "form"=> array ( "_token" => "8680bb4f6066073aab5131820d8fc747d01f36c2" ),
            "processStep" => "paymentOptions",
            "isStepThreeCompleted" => "0",
            "paymentBy" => "paypal",
            "Billing"=>
                array(
                    "Firstname" => "", "Lastname" => "", "Address" =>  "", "City" => "", "State" => "", "Zipcode" =>  "", "Country" => "",
                ),
            "cc"=>
                array ( "CardType" => "", "CardNumber" => "", "ExpMonth" => "", "ExpYear" => "", "Cvv" => "", ),
            "milstar"=>
                array ( "MCardNumber" => "", "MZipcode" => "", )
        );

        $crawler = $client->request('POST', '/confirm-payment-detail/3',$formdatastep2,  array(), array(
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
        $this->assertEquals('Dhi\ServiceBundle\Controller\ConfirmOrderController::confirmPaymentDetailAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('POST', '/do-payment-process');

        $this->assertEquals('Dhi\ServiceBundle\Controller\ConfirmOrderController::doPaymentProcessAction', $client->getRequest()->attributes->get('_controller'));

        $tokentest = 'EC-0826967786633381E';
        $PayerIDtest = 'B6JLM86TBLA36';
        $requestParams = "token=" . $tokentest . '&PayerID=' . $PayerIDtest;
        $crawler = $client->request('POST', '/paypal/paymentconfirm?'.$requestParams);

        $this->assertEquals('Dhi\ServiceBundle\Controller\PaypalController::paymentconfirmAction', $client->getRequest()->attributes->get('_controller'));


        $crawler = $client->request('POST', '/service/purchaseverification');

        $this->assertEquals('Dhi\ServiceBundle\Controller\ServiceController::purchaseverificationAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Order Summary")')->count());

        $this->assertEquals(1, $crawler->filter('html:contains("success")')->count());

        $this->assertEquals(0, $crawler->filter('html:contains("fail")')->count());

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
