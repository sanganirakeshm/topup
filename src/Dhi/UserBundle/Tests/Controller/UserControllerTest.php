<?php

namespace Dhi\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase {

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
        $this->assertFalse(count($user) == 0, 'User not found');
        $pid = 1;
        $packageId = 10010;
        $packageType = 'IPTV';
        $service = 'IPTV';
        $isAddonsPack = 0;
        $ispValidity = 0;
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


        /*
         * Apply Global Promo code
         */
        $userId = $user->getId();
        $codeType = 'discount';
        $userDiscountCode = static::$kernel->getContainer()->getParameter('test_discount_code_apply_front');
        $amount = 10.00;
        
        $requestGlobalCodeParams = "userDiscountCode=" . $userDiscountCode . '&codeType=' . $codeType . '&userId=' . $userId . '&amount=' . $amount;
        $crawler = $client->request('POST', '/check-discount-code-validation?' . $requestGlobalCodeParams);
        $this->assertEquals('Dhi\UserBundle\Controller\UserController::checkDiscountCodeAction', $client->getRequest()->attributes->get('_controller'));
        
        /* End Here */

        $isStepOneCompleted = "0";
        $discountCode = '';
        $paybleAmount = "10.00";
        $_token = "8680bb4f6066073aab5131820d8fc747d01f36c2";

        $tempap = array(
            'isStepOneCompleted' => "0",
            'discountCode' => '',
            'paybleAmount' => "10.00",
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
            "paybleAmount" => "10.00",
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

            /* Update Profile Tab -2 Change Password */
            $crawler = $client->request('POST', '/profile/2');
            $this->assertEquals('Dhi\UserBundle\Controller\AccountController::accountUpdateAction', $client->getRequest()->attributes->get('_controller'));

            $form['dhi_user_changepassword[current_password]'] = $this->testCurrentPassword;
            $form['dhi_user_changepassword[plainPassword][first]'] = $this->testPassword;
            $form['dhi_user_changepassword[plainPassword][second]'] = $this->testComfirmPassword;

            $this->assertFalse(strlen($this->testPassword) < 8, 'The password minimum length should be 8');
            $this->assertFalse(strlen($this->testPassword) > 18, 'The password maximum length should be 18');
            $this->assertFalse(strlen($this->testComfirmPassword) < 8, 'The password minimum length should be 8');
            $this->assertFalse(strlen($this->testComfirmPassword) > 18, 'The password maximum length should be 18');
            $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testPassword);
            $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testComfirmPassword);
            $this->assertSame($this->testPassword, $this->testComfirmPassword, 'Password does not match the confirm password.');

            $form = $crawler->selectButton('Update Password')->form();
            $this->assertEquals(1, $crawler->filter('html:contains(" Password updated successfully!")')->count());
        }
    }

    public function testinviteFriendAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        
       $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        
        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));

        $this->assertEquals(1, count($user));

        if ($user) {
            $email = 'test2@gmail';
            $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $email);
                
            $checkUserFlag = $em->getRepository("DhiUserBundle:User")->findBy(array('email' => $email));
            $checkReferralInviteesFlag = $this->em->getRepository("DhiUserBundle:ReferralInvitees")->findBy(array('emailId' => $email,'userId'=>$user));
            
            if(count($checkUserFlag) || count($checkReferralInviteesFlag) > 0){
                 $this->assertFalse($email, 'Email Address already used.');
            }
            
             $crawler = $client->request('POST', '/refer-friend',array('txtEmailid_1'=>'test@gmail.com'));
             $this->assertEquals('Dhi\UserBundle\Controller\UserController::inviteFriendAction', $client->getRequest()->attributes->get('_controller'));
             $this->assertEquals(0, $crawler->filter('html:contains(" Invtation sent successfully!")')->count());
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
