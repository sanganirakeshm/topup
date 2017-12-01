<?php

namespace Dhi\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Entity\PaypalCheckout;

class AccountControllerTest extends WebTestCase {

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

    /* Update Profile variables */
    protected $testEmail;
    protected $testComfirmEmail;
    protected $testCurrentPassword;
    protected $testPassword;
    protected $testComfirmPassword;
    protected $testFname;
    protected $testLname;
    protected $testAddress;
    protected $testCity;
    protected $testState;
    protected $testZip;
    protected $testCountry;
    
    protected $customerPromoCode;
    
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
        $this->aradial = static::$kernel->getContainer()->get('aradial');
        $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');

        /* Set Update profile variales */
        $this->testEmail = static::$kernel->getContainer()->getParameter('test_update_email');
        $this->testComfirmEmail = static::$kernel->getContainer()->getParameter('test_update_confirm_email');
        $this->testCurrentPassword = static::$kernel->getContainer()->getParameter('test_current_password');
        $this->testPassword = static::$kernel->getContainer()->getParameter('test_update_password');
        $this->testComfirmPassword = static::$kernel->getContainer()->getParameter('test_update_confirm_password');
        $this->testFname = static::$kernel->getContainer()->getParameter('test_update_fname');
        $this->testLname = static::$kernel->getContainer()->getParameter('test_update_lname');
        $this->testAddress = static::$kernel->getContainer()->getParameter('test_update_address');
        $this->testCity = static::$kernel->getContainer()->getParameter('test_update_city');
        $this->testState = static::$kernel->getContainer()->getParameter('test_update_state');
        $this->testZip = static::$kernel->getContainer()->getParameter('test_update_zip');
        $this->testCountry = static::$kernel->getContainer()->getParameter('test_update_country');
        /* End Here */

        $this->customerPromoCode = static::$kernel->getContainer()->getParameter('test_customer_promo_code');
        
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

        $crawler = $client->request('GET', '/get-service-plan?type=IPTV');
        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::ajaxGetServicePlanAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Basic")')->count();

        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("Package not available.")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testajaxAddToCartPackageAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));
        $objPackage = $this->em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageType' => 'IPTV', 'status' => 1, 'isExpired' => 0), array('id' => 'DESC'));
        $this->assertFalse(count($objPackage) == 0, 'Package not available');
        
        $pid = 1;
        $packageId = $objPackage->getPackageId();
        $packageType = $objPackage->getPackageType();
        $service = $objPackage->getPackageType();
        $isAddonsPack = $objPackage->getIsAddons();
        $ispValidity = $objPackage->getValidity();
        $iSExtendISP = 0;

        $insertIdPaypalCheckOut = '';
        $insertIdBillingAddress = '';
        $requestParams = "pid=" . $pid . '&packageId=' . $packageId . '&packageType=' . $packageType . '&service=' . $service . '&isAddonsPack=' . $isAddonsPack . '&ispValidity=' . $ispValidity . '&iSExtendISP=' . $iSExtendISP;
        $crawler = $client->request('GET', '/add-to-cart-package?' . $requestParams);
        $this->assertEquals('Dhi\ServiceBundle\Controller\ServiceController::ajaxAddToCartPackageAction', $client->getRequest()->attributes->get('_controller'));

        /*
         * Service controller / purchaseverification
         */

        $crawler = $client->request('POST', '/service/purchaseverification');
        $this->assertEquals(1, $crawler->filter('html:contains("Order Summary")')->count());
        $this->assertEquals('Dhi\ServiceBundle\Controller\ServiceController::purchaseverificationAction', $client->getRequest()->attributes->get('_controller'));

        $isStepOneCompleted = "0";
        $discountCode = '';
        $paybleAmount = $objPackage->getAmount();
        $_token = "8680bb4f6066073aab5131820d8fc747d01f36c2";

        $tempap = array(
            'isStepOneCompleted' => "0",
            'discountCode' => '',
            'paybleAmount' => $paybleAmount,
            'form' => array(
                '_token' => "8680bb4f6066073aab5131820d8fc747d01f36c2"));

        // add into bundle
        $requestParams = "isStepOneCompleted=" . $isStepOneCompleted . '&discountCode=' . $discountCode . '&paybleAmount=' . $paybleAmount . '&_token=' . $_token;

        $crawler = $client->request('POST', '/confirm-payment-detail/1', $tempap, array(), array(
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));

        $this->assertEquals('Dhi\ServiceBundle\Controller\ConfirmOrderController::confirmPaymentDetailAction', $client->getRequest()->attributes->get('_controller'));


        $formdatastep2 = array(
            "isStepOneCompleted" => "0",
            "discountCode" => "",
            "paybleAmount" => $paybleAmount,
            "form" => array("_token" => "8680bb4f6066073aab5131820d8fc747d01f36c2"),
            "processStep" => "paymentOptions",
            "isStepThreeCompleted" => "0",
            "paymentBy" => "paypal",
            "Billing" =>
            array(
                "Firstname" => "", "Lastname" => "", "Address" => "", "City" => "", "State" => "", "Zipcode" => "", "Country" => "",
            ),
            "cc" =>
            array("CardType" => "", "CardNumber" => "", "ExpMonth" => "", "ExpYear" => "", "Cvv" => "",),
            "milstar" =>
            array("MCardNumber" => "", "MZipcode" => "",)
        );

        $crawler = $client->request('POST', '/confirm-payment-detail/3', $formdatastep2, array(), array(
            'HTTP_X-Requested-With' => 'XMLHttpRequest',
        ));
        $this->assertEquals('Dhi\ServiceBundle\Controller\ConfirmOrderController::confirmPaymentDetailAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('POST', '/do-payment-process');

        $this->assertEquals('Dhi\ServiceBundle\Controller\ConfirmOrderController::doPaymentProcessAction', $client->getRequest()->attributes->get('_controller'));

        $tokentest = 'EC-0826967786633381E';
        $PayerIDtest = 'B6JLM86TBLA36';
        $requestParams = "token=" . $tokentest . '&PayerID=' . $PayerIDtest;
        $crawler = $client->request('POST', '/paypal/paymentconfirm?' . $requestParams);

        $this->assertEquals('Dhi\ServiceBundle\Controller\PaypalController::paymentconfirmAction', $client->getRequest()->attributes->get('_controller'));


        $crawler = $client->request('POST', '/service/purchaseverification');

        $this->assertEquals('Dhi\ServiceBundle\Controller\ServiceController::purchaseverificationAction', $client->getRequest()->attributes->get('_controller'));
    }

    /*
     * Reedem Customer Promo Code
     */
    public function testajaxRedeemPromoCodeAction(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));

        $crawler = $client->request('GET', '/redeem-promo-code?promocode=' . $this->customerPromoCode);
        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::ajaxRedeemPromoCodeAction', $client->getRequest()->attributes->get('_controller'));
        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->result != 'success', $data->errMsg);
        
        $crawler = $client->request('GET', '/apply-promo-code?promocode=' . $this->customerPromoCode);
        $this->assertEquals('Dhi\UserBundle\Controller\AccountController::ajaxApplyPromoCodeAction', $client->getRequest()->attributes->get('_controller'));
        $applyResponse = $client->getResponse();
        $applyData = json_decode($applyResponse->getContent());
        $this->assertFalse($applyData->result != 'success', $applyData->errMsg);
    }

        public function testaccountUpdateAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));

        $this->assertEquals(1, count($user));

        if ($user) {


            $crawler = $client->request('POST', '/profile/1');
            $this->assertEquals('Dhi\UserBundle\Controller\AccountController::accountUpdateAction', $client->getRequest()->attributes->get('_controller'));

            $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->testEmail);
            $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->testComfirmEmail);
            $this->assertSame($this->testEmail, $this->testComfirmEmail, 'Email and confirm email do not match.');

            $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->testFname);
            $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->testLname);

            $this->assertFalse(strlen($this->testFname) < 3, 'Your firstname must have minimum 3 characters.');
            $this->assertFalse(strlen($this->testLname) < 3, 'Your lastname must have minimum 3 characters.');
            $this->assertFalse(strlen($this->testAddress) < 3, 'Your address must have minimum 3 characters.');
            $this->assertFalse(strlen($this->testCity) < 3, 'Your city must have minimum 3 characters.');
            $this->assertFalse(strlen($this->testZip) < 5, 'Your zip must have minimum 5 characters.');

            $form = $crawler->selectButton('Update Account')->form();
            $this->assertEquals(1, $crawler->filter('html:contains("Profile")')->count());
            $isFormValid = true;

            if ($user->getEmail() != $this->testEmail) {
                $objUserForEmail = $user = $this->em->getRepository('DhiUserBundle:User')
                        ->findOneBy(array('email' => $this->testEmail));
                $this->assertEquals(0, count($objUserForEmail));
                $isFormValid = count($objUserForEmail) > 0 ? false : true;
            }

            if ($isFormValid) {
                $form['dhi_user_account_update[email][first]'] = $this->testEmail;
                $form['dhi_user_account_update[email][second]'] = $this->testComfirmEmail;
                $form['dhi_user_account_update[firstname]'] = $this->testFname;
                $form['dhi_user_account_update[lastname]'] = $this->testLname;
                $form['dhi_user_account_update[address]'] = $this->testAddress;
                $form['dhi_user_account_update[city]'] = $this->testCity;
                $form['dhi_user_account_update[state]'] = $this->testState;
                $form['dhi_user_account_update[zip]'] = $this->testZip;
                $form['dhi_user_account_update[country]'] = $this->testCountry;

                $crawler = $client->submit($form);
                $this->assertEquals(1, $crawler->filter('html:contains(" Account information updated successfully!")')->count());
            }
            
            /* Update Profile Tab -2 Change Password*/
            $crawler = $client->request('POST', '/profile/2');
            $this->assertEquals('Dhi\UserBundle\Controller\AccountController::accountUpdateAction', $client->getRequest()->attributes->get('_controller'));
            $form = $crawler->selectButton('Update Password')->form();
            $this->assertFalse(strlen($this->testPassword) < 8, 'The password minimum length should be 8');
            $this->assertFalse(strlen($this->testPassword) > 18, 'The password maximum length should be 18');
            $this->assertFalse(strlen($this->testComfirmPassword) < 8, 'The password minimum length should be 8');
            $this->assertFalse(strlen($this->testComfirmPassword) > 18, 'The password maximum length should be 18');
            $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testPassword);
            $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testComfirmPassword);
            $this->assertSame($this->testPassword, $this->testComfirmPassword, 'Password does not match the confirm password.');
            
            $form['dhi_user_changepassword[current_password]'] =  $this->testCurrentPassword;
            $form['dhi_user_changepassword[plainPassword][first]'] =  $this->testPassword;
            $form['dhi_user_changepassword[plainPassword][second]'] =  $this->testComfirmPassword;
            
            $crawler = $client->submit($form);
            $this->assertEquals(1, $crawler->filter('html:contains("Password updated successfully!")')->count());
        }
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
