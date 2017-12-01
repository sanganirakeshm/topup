<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase {

     /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    public function testIndexUser() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('POST', '/admin/user/user-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Search User")')->count());
    }

    public function testuserListJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);

        $crawler = $client->request('GET', '/admin/user/user-list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::userListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/user/user-list-json?firstname=James');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::userListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("James")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/user/user-list-json?lastname=Bond');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::userListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Bond")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/user/user-list-json?sSearch_2=testEmployee');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::userListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("testEmployee")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/user/user-list-json?sSearch_3=testEmployee@brainvire.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::userListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("IPTV")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/user/user-list-json?sSearch_4=IPTV');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::userListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("IPTV")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/user/user-list-json?sSearch_5=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::userListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testAddUser() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('POST', '/admin/add-customer');
        $client->enableProfiler();

        $email = static::$kernel->getContainer()->getParameter('test_user_email');
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $password = static::$kernel->getContainer()->getParameter('test_password');
        $repeatPassword = static::$kernel->getContainer()->getParameter('test_repeat_password');
        $firstname = static::$kernel->getContainer()->getParameter('test_firstname');
        $lastname = static::$kernel->getContainer()->getParameter('test_lastname');
        $phonenumber = static::$kernel->getContainer()->getParameter('test_phonenumber');
        $address = static::$kernel->getContainer()->getParameter('test_address');
        $city = static::$kernel->getContainer()->getParameter('test_city');
        $state = static::$kernel->getContainer()->getParameter('test_state');
        $zip = static::$kernel->getContainer()->getParameter('test_zip');
        $country_iso_code = static::$kernel->getContainer()->getParameter('test_country_iso_code');
        $test_userservice_location = static::$kernel->getContainer()->getParameter('test_userservice_location');

        $this->assertFalse(strlen($username) < 6, 'The username minimum length should be 6');
        $this->assertFalse(strlen($username) > 32, 'The username maximum length should be 32');
        $this->assertFalse(strlen($password) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($password) > 18, 'The password maximum length should be 18');

        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $email);
        $this->assertRegExp('/^[A-Za-z0-9-_!#$]+$/', $username);
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $password);

        $this->assertEquals($password, $repeatPassword);
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::newAction', $client->getRequest()->attributes->get('_controller'));

        $user = $this->em->getRepository('DhiUserBundle:User')->getUserByUsernameOrEmail($username, $email);
        if (!$user) {
            $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array('isoCode' => $country_iso_code));
            if (!$objCountry) {
                $this->assertTrue(false, 'Test country not found in database.');
            }
            $objUserServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('name' => $test_userservice_location));
            if (!$objUserServiceLocation) {
                $this->assertTrue(false, "'BAF' service locatio not found in database.");
            }
            $form = $crawler->selectButton('Add')->form();
            $form['dhi_admin_registration[email]'] = $email;
            $form['dhi_admin_registration[username]'] = $username;
            $form['dhi_admin_registration[plainPassword][first]'] = $password;
            $form['dhi_admin_registration[plainPassword][second]'] = $repeatPassword;
            $form['dhi_admin_registration[firstname]'] = $firstname;
            $form['dhi_admin_registration[lastname]'] = $lastname;
            $form['dhi_admin_registration[phone]'] = '0123456789';
            $form['dhi_admin_registration[address]'] = $address;
            $form['dhi_admin_registration[city]'] = $city;
            $form['dhi_admin_registration[state]'] = $state;
            $form['dhi_admin_registration[zip]'] = $zip;
            $form['dhi_admin_registration[country]'] = $objCountry->getId();
            $form['dhi_admin_registration[userServiceLocation]'] = 1;
            $form['dhi_admin_registration[enabled]'] = 1;
            $form['dhi_admin_registration[is_email_optout]'] = 1;
            $crawler = $client->submit($form);
            $crawler = $client->followRedirect();
            $this->assertEquals(1, $crawler->filter('html:contains("Customer added successfully!")')->count());
        } else {
            $this->assertTrue(false, 'The user already exists (username or email matched). Please try another one.');
        }
    }

    public function testEditAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $username));

        $crawler = $client->request('POST', '/admin/edit-customer/' . $objUser->getId() . '?profile-note=test%20note');
        $client->enableProfiler();

        $email = static::$kernel->getContainer()->getParameter('test_user_email');
        $firstname = static::$kernel->getContainer()->getParameter('test_firstname');
        $lastname = static::$kernel->getContainer()->getParameter('test_lastname');
        $phonenumber = static::$kernel->getContainer()->getParameter('test_phonenumber');
        $address = static::$kernel->getContainer()->getParameter('test_address');
        $city = static::$kernel->getContainer()->getParameter('test_city');
        $state = static::$kernel->getContainer()->getParameter('test_state');
        $zip = static::$kernel->getContainer()->getParameter('test_zip');
        $country_iso_code = static::$kernel->getContainer()->getParameter('test_country_iso_code');

        $this->assertFalse(strlen($username) < 6, 'The username minimum length should be 6');
        $this->assertFalse(strlen($username) > 32, 'The username maximum length should be 32');
//        $this->assertFalse(strlen($password) < 8, 'The password minimum length should be 8');
//        $this->assertFalse(strlen($password) > 18, 'The password maximum length should be 18');

        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $email);
        $this->assertRegExp('/^[A-Za-z0-9-_!#$]+$/', $username);
//        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $password);
//        $this->assertEquals($password, $repeatPassword);
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::editAction', $client->getRequest()->attributes->get('_controller'));

        if ($objUser) {

            $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array('isoCode' => $country_iso_code));
            if (!$objCountry) {
                $this->assertTrue(false, 'Test country not found in database.');
            }

            $form = $crawler->selectButton('Update')->form();
            $form['dhi_admin_registration[username]'] = $username;
            $form['dhi_admin_registration[email]'] = $email;
            $form['dhi_admin_registration[firstname]'] = $firstname;
            $form['dhi_admin_registration[lastname]'] = $lastname;
            $form['dhi_admin_registration[phone]'] = '0123456789';
            $form['dhi_admin_registration[address]'] = $address;
            $form['dhi_admin_registration[city]'] = $city;
            $form['dhi_admin_registration[state]'] = $state;
            $form['dhi_admin_registration[zip]'] = $zip;
            $form['dhi_admin_registration[country]'] = $objCountry->getId();
            $form['dhi_admin_registration[enabled]'] = 1;
            $form['dhi_admin_registration[is_email_optout]'] = 1;
            $form['profile-note'] = "Update customer testing note.";
            $crawler = $client->submit($form);
            $crawler = $client->followRedirect();
            $this->assertEquals(1, $crawler->filter('html:contains("Customer updated successfully!")')->count());
        } else {
            $this->assertTrue(false, 'User not exists.');
        }
    }

    public function testViewAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $username));

        $crawler = $client->request('POST', '/admin/view-customer/' . $objUser->getId());
        $client->enableProfiler();

        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::viewAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("User Detail")')->count());
        $this->assertEquals(1, $crawler->filter('html:contains("User Setting Detail")')->count());
    }

    public function testLoginLogAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('GET', '/admin/user/user-login-log');

        $client->enableProfiler();
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::loginLogAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("SEARCH USER LOG")')->count());
    }

    public function testServiceDetailAction() {
        $client = static::createClient(
                        array(), array(
                        'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                        'REMOTE_ADDR' => '127.0.0.1'
                      ));

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $username));
        $crawler = $client->request('GET', '/admin/user/service-details/' . $objUser->getId());
        $client->enableProfiler();
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::serviceDetailAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Service Purchase")')->count());
        $this->assertEquals(1, $crawler->filter('html:contains("Order Summary")')->count());
    }

    public function testActiveUserReportAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('GET', '/admin/user/active-user-report');
        $client->enableProfiler();
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Active User Report")')->count());
    }

    public function testLogAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );
        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $username));
        $crawler = $client->request('GET', '/admin/user/user-log/' . $objUser->getId());
        $client->enableProfiler();
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::logAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Log Summary")')->count());
    }

    public function testAddCompensationAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );
        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $username));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        $crawler = $client->request('GET', '/admin/user/add-compensation/'.$objUser->getId());
        $client->enableProfiler();
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::addCompensationAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Add New Compensation")')->count());
        $form = $crawler->selectButton('Add')->form();
        $form['dhi_admin_compensation[ispHours]'] = '48';
        $form['dhi_admin_compensation[reason]'] = 'Test cases compensation reason';
        $client->submit($form);
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

    public function testactiveUserReport() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/user/active-user-report');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Active User Report")')->count());
    }

    public function testactiveUserListJson() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/user/active-user-list-json');
        $this->assertEquals(0, $crawler->filter('html:contains("Active user report")')->count());
        $crawler = $client->request('GET', '/admin/user/active-user-list-json?name=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(0, $crawler->filter('html:contains("Active user report search by location")')->count());
        $crawler = $client->request('GET', '/admin/user/active-user-list-json?service=IPTV');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("IPTV")')->count());
    }

    public function testactiveUserExportExcelAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/user/active-user-report');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Active User Report")')->count());
        $form = $crawler->selectLink('Export Excel')->first()->link();
        $crawler = $client->click($form);
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserExportExcelAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testactiveUserExportCsv() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/user/active-user-report');
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Active User Report")')->count());
        $form = $crawler->selectLink('Export CSV')->first()->link();
        $crawler = $client->click($form);
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::activeUserExportCsvAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testRedeemPromoCodeAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        $crawler = $client->request('GET', '/admin/user/promocode/redeem', array(
            'user' => $objUser->getId(),
            'promoCode' => static::$kernel->getContainer()->getParameter('test_admin_redeem_promocode'),
            'promoAction' => 'redeem'
        ));

        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::redeemPromoCodeAction', $client->getRequest()->attributes->get('_controller'));
        $response = $client->getResponse();
        $content = $response->getContent();
        if ($content) {
            $decodeContent = json_decode($content);
            if ($decodeContent->status == 'success') {
                $this->assertTrue(true, "** " . $decodeContent->message);
            } else {
                $this->assertFalse(true, "** " . $decodeContent->message);
            }
        } else {
            $this->assertFalse(true, "Response not found.");
        }
    }

    public function testApplyPromoCodeAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        
        $crawler = $client->request('GET', '/admin/user/promocode/redeem', array(
            'user' => $objUser->getId(),
            'promoCode' => static::$kernel->getContainer()->getParameter('test_admin_redeem_promocode'),
            'promoAction' => 'apply'
        ));

        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::redeemPromoCodeAction', $client->getRequest()->attributes->get('_controller'));
        $response = $client->getResponse();
        $content = $response->getContent();
        if ($content) {
            $decodeContent = json_decode($content);
            if ($decodeContent->status == 'success') {
                $this->assertTrue(true, "** " . $decodeContent->message);
            } else {
                $this->assertFalse(true, "** " . $decodeContent->message);
            }
        } else {
            $this->assertFalse(true, "Response not found.");
        }
    }
    
    public function testDeleteAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host'),
                    'REMOTE_ADDR' => '127.0.0.1'
                        )
        );

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('GET', '/admin/delete-customer');

        $client->enableProfiler();
        $this->assertEquals('Dhi\AdminBundle\Controller\UserController::deleteAction', $client->getRequest()->attributes->get('_controller'));
        $username = static::$kernel->getContainer()->getParameter('test_username');
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $username));
        if ($objUser) {
            $objUser->setIsDeleted(1);
            $objUser->setExpired(1);
            $objUser->setExpiresAt(new \DateTime());
            $this->em->persist($objUser);
            $this->em->flush();
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
