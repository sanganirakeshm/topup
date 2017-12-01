<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * 
 */
class EmployeeControllerTest extends WebTestCase {

    protected $testHttpHost;
    protected $adminUserName;
    protected $adminPassword;
    protected $empUsername;
    protected $empEmail;
    protected $empPassword;
    protected $empComfPassword;
    protected $empFname;
    protected $empLname;
    protected $empPhone;
    protected $empAddress;
    protected $empCity;
    protected $empState;
    protected $empZip;
    protected $empCountry;
    protected $empServiceLoc;
    protected $empEnabled;
    
    
    protected $empUpdateEmail;
    protected $empUpdateFname;
    protected $empUpdateLname;
    protected $empUpdatePhone;
    protected $empUpdateAddress;
    protected $empUpdateCity;
    protected $empUpdateState;
    protected $empUpdateZip;
    protected $empUpdateCountry;
    protected $empUpdateEnabled;
    
    
    protected $empUpdatePassword;
    protected $empUpdateComfPassword;
    
    
    protected $empUpdateMaxMacAddress;
    protected $empUpdateMaxDailyTran;

    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->adminUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->adminPassword = static::$kernel->getContainer()->getParameter('test_admin_password');

        /* Add new emp Variables */
        $this->empUsername = static::$kernel->getContainer()->getParameter('test_emp_username');
        $this->empEmail = static::$kernel->getContainer()->getParameter('test_emp_email');
        $this->empPassword = static::$kernel->getContainer()->getParameter('test_emp_password');
        $this->empComfPassword = static::$kernel->getContainer()->getParameter('test_emp_confirm_password');
        $this->empFname = static::$kernel->getContainer()->getParameter('test_emp_fname');
        $this->empLname = static::$kernel->getContainer()->getParameter('test_emp_lname');
        $this->empPhone = static::$kernel->getContainer()->getParameter('test_emp_phone');
        $this->empAddress = static::$kernel->getContainer()->getParameter('test_emp_address');
        $this->empCity = static::$kernel->getContainer()->getParameter('test_emp_city');
        $this->empState = static::$kernel->getContainer()->getParameter('test_emp_state');
        $this->empZip = static::$kernel->getContainer()->getParameter('test_emp_zip');
        $this->empCountry = static::$kernel->getContainer()->getParameter('test_emp_country');
        $this->empServiceLoc = static::$kernel->getContainer()->getParameter('test_emp_service_location');
        $this->empEnabled = static::$kernel->getContainer()->getParameter('test_emp_service_enabled');


        /* Update Profile Variables */
        $this->empUpdateEmail = static::$kernel->getContainer()->getParameter('test_emp_update_email');
        $this->empUpdateFname = static::$kernel->getContainer()->getParameter('test_emp_update_fname');
        $this->empUpdateLname = static::$kernel->getContainer()->getParameter('test_emp_update_lname');
        $this->empUpdatePhone = static::$kernel->getContainer()->getParameter('test_emp_update_phone');
        $this->empUpdateAddress = static::$kernel->getContainer()->getParameter('test_emp_update_address');
        $this->empUpdateCity = static::$kernel->getContainer()->getParameter('test_emp_update_city');
        $this->empUpdateState = static::$kernel->getContainer()->getParameter('test_emp_update_state');
        $this->empUpdateZip = static::$kernel->getContainer()->getParameter('test_emp_update_zip');
        $this->empUpdateCountry = static::$kernel->getContainer()->getParameter('test_emp_update_country');
        $this->empUpdateEnabled = static::$kernel->getContainer()->getParameter('test_emp_update_service_enabled');

        /* Update password */
        $this->empUpdatePassword = static::$kernel->getContainer()->getParameter('test_emp_update_password');
        $this->empUpdateComfPassword = static::$kernel->getContainer()->getParameter('test_emp_update_confirm_password');

        /* Update Settings */
        $this->empUpdateMaxMacAddress = static::$kernel->getContainer()->getParameter('test_emp_update_max_mac_address');
        $this->empUpdateMaxDailyTran = static::$kernel->getContainer()->getParameter('test_emp_update_max_daily_transcation');

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', 'admin/login');
        $form = $crawler->selectButton('Sign In')->form(array(
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
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/employee/employee-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Search Employee")')->count());
    }

    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/employee/employee-list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/employee/employee-list-json?firstname=James');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("James")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);

        
        $crawler = $client->request('GET', '/admin/employee/employee-list-json?lastname=Bond');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Bond")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        
        $crawler = $client->request('GET', '/admin/employee/employee-list-json?sSearch_2=testEmployee');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("testEmployee")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/employee/employee-list-json?sSearch_3=testEmployee@brainvire.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("IPTV")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/employee/employee-list-json?sSearch_4=IPTV');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("IPTV")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/employee/employee-list-json?sSearch_5=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testnewAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('POST', '/admin/add-employee');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::newAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertFalse(strlen($this->empUsername) < 6, 'The username minimum length should be 6');
        $this->assertFalse(strlen($this->empUsername) > 32, 'The username maximum length should be 32');
        $this->assertFalse(strlen($this->empPassword) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($this->empPassword) > 18, 'The password maximum length should be 18');

        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->empEmail);
        $this->assertRegExp('/^[A-Za-z0-9-_!#$]+$/', $this->empUsername);
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->empPassword);

        $this->assertEquals($this->empPassword, $this->empComfPassword);

        $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->empFname);
        $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->empLname);

        $user = $this->em->getRepository('DhiUserBundle:User')->getUserByUsernameOrEmail($this->empUsername, $this->empEmail);

        if (!$user) {

            $form = $crawler->selectButton('add')->form();

            $form['dhi_admin_registration[username]'] = $this->empUsername;
            $form['dhi_admin_registration[email]'] = $this->empEmail;
            $form['dhi_admin_registration[plainPassword][first]'] = $this->empPassword;
            $form['dhi_admin_registration[plainPassword][second]'] = $this->empComfPassword;
            $form['dhi_admin_registration[firstname]'] = $this->empFname;
            $form['dhi_admin_registration[lastname]'] = $this->empLname;
            $form['dhi_admin_registration[phone]'] = $this->empPhone;
            $form['dhi_admin_registration[address]'] = $this->empAddress;
            $form['dhi_admin_registration[city]'] = $this->empCity;
            $form['dhi_admin_registration[state]'] = $this->empState;
            $form['dhi_admin_registration[zip]'] = $this->empZip;
            $form['dhi_admin_registration[country]'] = $this->empCountry;
            $form['dhi_admin_registration[userServiceLocation]'] = $this->empServiceLoc;
            $form['dhi_admin_registration[enabled]'] = $this->empEnabled;

            $client->submit($form);

            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("Employee added successfully!")')->count());
        } else {
            $this->assertTrue(false, 'The user already exists (username or email matched). Please try another one.');
        }
    }

    public function testLogAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
                        )
        );

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->empUsername));
        $this->assertFalse(count($objUser) != 1, 'Employee does not exist.');

        if ($objUser) {

            $crawler = $client->request('GET', '/admin/employee/employee-log/' . $objUser->getId());
            $client->enableProfiler();
            $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::logAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("Log Summary")')->count());
        }
    }

    public function testserviceDetailAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
                        )
        );

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->empUsername));
        $this->assertFalse(count($objUser) != 1, 'Employee does not exist.');

        if ($objUser) {

            $requestParams = array(
                'userId' => $objUser->getId(),
                'service' => 'IPTV',
                'packageId' => 10001,
                'packageName' => array(
                    '10001' => 'Free Trial'
                ),
                'validity' => array(
                    '10001' => 30
                ),
                'price' => array(
                    '10001' => 10.00
                )
            );

            $crawler = $client->request('POST', '/admin/add-to-cart-plan/0/{}', $requestParams, array(), array(
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
            ));

            $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseProcessController::addToCartPlanAction', $client->getRequest()->attributes->get('_controller'));

            $crawler = $client->request('POST', '/admin/confirm-purchase/' . $objUser->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseProcessController::confirmPurchaseAction', $client->getRequest()->attributes->get('_controller'));

            $crawler = $client->request('POST', '/admin/proceed-free-activation/' . $objUser->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\PurchaseProcessController::activateFreePlanAction', $client->getRequest()->attributes->get('_controller'));
        }
    }

    public function testviewAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
                        )
        );

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->empUsername));
        $this->assertFalse(count($objUser) != 1, 'Employee does not exist.');

        if ($objUser) {

            $crawler = $client->request('POST', '/admin/view-employee/' . $objUser->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::viewAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("Employee Detail")')->count());
        }
    }

    public function testeditProfileAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->empUsername));
        $this->assertFalse(count($objUser) != 1, 'Employee does not exist.');

        if ($objUser) {

            $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->empUpdateEmail);
            $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->empUpdateFname);
            $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->empUpdateLname);

            $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('email' => $this->empUpdateEmail));

            if (!$user) {

                $crawler = $client->request('POST', '/admin/edit-employee/' . $objUser->getId());
                $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::editAction', $client->getRequest()->attributes->get('_controller'));

                //Update Emp Information
                $form = $crawler->selectButton('Update')->form();
                $form['dhi_admin_registration[email]'] = $this->empUpdateEmail;
                $form['dhi_admin_registration[firstname]'] = $this->empUpdateFname;
                $form['dhi_admin_registration[lastname]'] = $this->empUpdateLname;
                $form['dhi_admin_registration[phone]'] = $this->empUpdatePhone;
                $form['dhi_admin_registration[address]'] = $this->empUpdateAddress;
                $form['dhi_admin_registration[city]'] = $this->empUpdateCity;
                $form['dhi_admin_registration[state]'] = $this->empUpdateState;
                $form['dhi_admin_registration[zip]'] = $this->empUpdateZip;
                $form['dhi_admin_registration[country]'] = $this->empUpdateCountry;
                $form['dhi_admin_registration[enabled]'] = $this->empUpdateEnabled;
                $form['profile-note'] = 'test for update';

                $client->submit($form);
                $client->followRedirect(true);
                $crawler = $client->getCrawler();
                $this->assertEquals(1, $crawler->filter('html:contains("Employee updated successfully!")')->count());
            } else {
                $this->assertTrue(false, 'The email already exists. Please try another one.');
            }
        }
    }

    public function testeditPasswordAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->empUsername));
        $this->assertFalse(count($objUser) != 1, 'Employee does not exist.');

        if ($objUser) {
            $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->empPassword);
            $this->assertFalse(strlen($this->empUpdatePassword) < 8, 'The password minimum length should be 8');
            $this->assertFalse(strlen($this->empUpdatePassword) > 18, 'The password maximum length should be 18');
            $this->assertEquals($this->empPassword, $this->empComfPassword);

            $crawler = $client->request('POST', '/admin/edit-employee/' . $objUser->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::editAction', $client->getRequest()->attributes->get('_controller'));

            $changePasswordForm = $crawler->selectButton('Update Password')->form();

            $changePasswordForm['dhi_admin_changepassword[plainPassword][first]'] = $this->empUpdatePassword;
            $changePasswordForm['dhi_admin_changepassword[plainPassword][second]'] = $this->empUpdateComfPassword;
            $changePasswordForm['change-password-note'] = 'test for update password';

            $client->submit($changePasswordForm);
            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("Password updated successfully!")')->count());
        }
    }

    public function testeditSettingAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->empUsername));
        $this->assertFalse(count($objUser) != 1, 'Employee does not exist.');

        if ($objUser) {
            /* Update Setting */
            $crawler = $client->request('POST', '/admin/edit-employee/' . $objUser->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::editAction', $client->getRequest()->attributes->get('_controller'));

            $userSettingForm = $crawler->selectButton('Update Setting')->form();

            $userSettingForm['dhi_admin_user_setting[mac_address]'] = $this->empUpdateMaxMacAddress;
            $userSettingForm['dhi_admin_user_setting[max_daily_transaction]'] = $this->empUpdateMaxDailyTran;
            $userSettingForm['user-setting-note'] = 'test for update setting';

            $client->submit($userSettingForm);
            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("Settings saved successfully!")')->count());
        }
    }

    public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->empUsername, 'isDeleted' => 0));
        $this->assertFalse(count($objUser) != 1, 'Employee does not exist.');

        if ($objUser) {

            $crawler = $client->request('POST', '/admin/delete-employee?id=' . $objUser->getId());

            $this->assertEquals('Dhi\AdminBundle\Controller\EmployeeController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            
            $responce = $client->getResponse();
            $data  = json_decode($responce->getContent());
        
            $this->assertEquals($data->message, "Employee deleted successfully!");
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
