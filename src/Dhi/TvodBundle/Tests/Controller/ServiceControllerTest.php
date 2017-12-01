<?php

namespace Dhi\TvodBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ServiceControllerTest extends WebTestCase {

    protected $testHttpHost;
    protected $tvodUserName;
    protected $tvodIdVod;
    protected $tvodTitle;
    protected $tvodPoster;
    protected $tvodPrice;
    protected $tvodReturnUrl;
    protected $tvodCancelUrl;
    protected $tvodExpire;
    protected $tvodUserId;

    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->tvodUserName  = static::$kernel->getContainer()->getParameter('test_tvod_username');
        $this->tvodIdVod     = static::$kernel->getContainer()->getParameter('test_tvod_idVod');
        $this->tvodTitle     = static::$kernel->getContainer()->getParameter('test_tvod_title');
        $this->tvodPoster    = static::$kernel->getContainer()->getParameter('test_tvod_poster');
        $this->tvodPrice     = static::$kernel->getContainer()->getParameter('test_tvod_price');
        $this->tvodReturnUrl = static::$kernel->getContainer()->getParameter('test_tvod_return_url');
        $this->tvodCancelUrl = static::$kernel->getContainer()->getParameter('test_tvod_cancel_url');
        $this->tvodExpire    = static::$kernel->getContainer()->getParameter('test_tvod_expire');
        $this->tvodUserId    = static::$kernel->getContainer()->getParameter('test_tvod_userId');

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    public function testindexAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        
        $requestParams  = 'login='.$this->tvodUserName.'&idVod='.$this->tvodIdVod.'&title='.$this->tvodTitle.'&poster='.$this->tvodPoster;
        $requestParams .= '&price='.$this->tvodPrice.'&return_url='.$this->tvodReturnUrl.'&cancel_url='.$this->tvodCancelUrl.'&expire='.$this->tvodExpire;
        
        $crawler = $client->request('GET', '/tvod/purchaseverification?'.$requestParams);
        $this->assertEquals('Dhi\TvodBundle\Controller\ServiceController::purchaseverificationAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Order Summary")')->count());
        
        
        
        $isStepOneCompleted = "0";
        $discountCode = '';
        $paybleAmount = $this->tvodPrice;
        $_token = "8680bb4f6066073aab5131820d8fc747d01f36c2";

        $tempap = array(
            'isStepOneCompleted' => "0",
            'discountCode' => '',
            'paybleAmount' => $this->tvodPrice,
            'form' => array(
                '_token' => "8680bb4f6066073aab5131820d8fc747d01f36c2"));

        // add into bundle
        $requestParams = "isStepOneCompleted=" . $isStepOneCompleted . '&discountCode=' . $discountCode . '&paybleAmount=' . $paybleAmount . '&_token=' . $_token;

        $crawler = $client->request('POST', '/tvod/tvod-confirm-payment-detail/1', $tempap, array(), array(
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $this->assertEquals('Dhi\TvodBundle\Controller\ConfirmOrderController::confirmPaymentDetailAction', $client->getRequest()->attributes->get('_controller'));
        
        $formdatastep2 = array(
            "isStepOneCompleted" => "0",
            "discountCode" => "",
            "paybleAmount" => $this->tvodPrice,
            "form" => array("_token" => "8680bb4f6066073aab5131820d8fc747d01f36c2"),
            "processStep" => "paymentOptions",
            "isStepThreeCompleted" => "0",
            "paymentBy" => "paypal",
            "userId" => $this->tvodUserId,
            "Billing" =>
            array(
                "Firstname" => "", "Lastname" => "", "Address" => "", "City" => "", "State" => "", "Zipcode" => "", "Country" => "",
            ),
            "cc" =>
            array("CardType" => "", "CardNumber" => "", "ExpMonth" => "", "ExpYear" => "", "Cvv" => "",),
            "milstar" =>
            array("MCardNumber" => "", "MZipcode" => "",)
        );

        
        $crawler = $client->request('POST', '/tvod/tvod-confirm-payment-detail/3', $formdatastep2, array(), array(
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
        $this->assertEquals('Dhi\TvodBundle\Controller\ConfirmOrderController::confirmPaymentDetailAction', $client->getRequest()->attributes->get('_controller'));
        
        
        $crawler = $client->request('POST', '/tvod/tvod-do-payment-process?userId='.$this->tvodUserId);
        $this->assertEquals('Dhi\TvodBundle\Controller\ConfirmOrderController::doPaymentProcessAction', $client->getRequest()->attributes->get('_controller'));

        
        $tokentest = 'EC-2HP49445W83047110';
        $PayerIDtest = 'B6JLM86TBLA36'; 
        $requestParams = "token=" . $tokentest . '&PayerID=' . $PayerIDtest;
        $crawler = $client->request('POST', '/tvod/tvod-paypal/tvod-paymentconfirm?' . $requestParams);
        $this->assertEquals('Dhi\TvodBundle\Controller\PaypalController::paymentconfirmAction', $client->getRequest()->attributes->get('_controller'));


        $crawler = $client->request('POST', '/tvod/purchaseverification');
        $this->assertEquals('Dhi\TvodBundle\Controller\ServiceController::purchaseverificationAction', $client->getRequest()->attributes->get('_controller'));
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
