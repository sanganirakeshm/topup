<?php

namespace Dhi\UserBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class RegistrationControllerTest extends WebTestCase {

    protected $em;
    protected $testUsername;
    protected $testEmail;
    protected $testComfirmEmail;
    protected $testPassword;
    protected $testComfirmPassword;
    protected $testFname;
    protected $testLname;
    protected $testAddress;
    protected $testCity;
    protected $testState;
    protected $testZip;
    protected $testCountry;
    
    protected $testNetgateUsername;
    protected $testNetgateEmail;
    protected $testNetgateComfirmEmail;
    protected $testNetgatePassword;
    protected $testNetgateComfirmPassword;
    protected $testNetgateFname;
    protected $testNetgateLname;
    protected $testNetgateAddress;
    protected $testNetgateCity;
    protected $testNetgateState;
    protected $testNetgateZip;
    protected $testNetgateCountry;

    /**
    * {@inheritDoc}
    */
    public function setUp() {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        
        $this->testUsername = static::$kernel->getContainer()->getParameter('test_reg_username');
        $this->testEmail = static::$kernel->getContainer()->getParameter('test_reg_email');
        $this->testComfirmEmail = static::$kernel->getContainer()->getParameter('test_reg_confirm_email');
        $this->testPassword= static::$kernel->getContainer()->getParameter('test_reg_password');
        $this->testComfirmPassword = static::$kernel->getContainer()->getParameter('test_reg_confirm_password');
        $this->testFname = static::$kernel->getContainer()->getParameter('test_reg_fname');
        $this->testLname = static::$kernel->getContainer()->getParameter('test_reg_lname');
        $this->testAddress = static::$kernel->getContainer()->getParameter('test_reg_address');
        $this->testCity = static::$kernel->getContainer()->getParameter('test_reg_city');
        $this->testState = static::$kernel->getContainer()->getParameter('test_reg_state');
        $this->testZip = static::$kernel->getContainer()->getParameter('test_reg_zip');
        $this->testCountry = static::$kernel->getContainer()->getParameter('test_reg_country');
        
        $this->testNetgateUsername = static::$kernel->getContainer()->getParameter('test_netgate_reg_username');
        $this->testNetgateEmail = static::$kernel->getContainer()->getParameter('test_netgate_reg_email');
        $this->testNetgateComfirmEmail = static::$kernel->getContainer()->getParameter('test_netgate_reg_confirm_email');
        $this->testNetgatePassword= static::$kernel->getContainer()->getParameter('test_netgate_reg_password');
        $this->testNetgateComfirmPassword = static::$kernel->getContainer()->getParameter('test_netgate_reg_confirm_password');
        $this->testNetgateFname = static::$kernel->getContainer()->getParameter('test_netgate_reg_fname');
        $this->testNetgateLname = static::$kernel->getContainer()->getParameter('test_netgate_reg_lname');
        $this->testNetgateAddress = static::$kernel->getContainer()->getParameter('test_netgate_reg_address');
        $this->testNetgateCity = static::$kernel->getContainer()->getParameter('test_netgate_reg_city');
        $this->testNetgateState = static::$kernel->getContainer()->getParameter('test_netgate_reg_state');
        $this->testNetgateZip = static::$kernel->getContainer()->getParameter('test_netgate_reg_zip');
        $this->testNetgateCountry = static::$kernel->getContainer()->getParameter('test_netgate_reg_country');
        
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    public function testFindUserByEmail() {
        $user = $this->em->getRepository('DhiUserBundle:User')
                ->findOneBy(array('username' => $this->testEmail));
        $this->assertEquals(0, count($user));
    }

    /*
     * Regular user registration
     */
    public function testRegistrationAction() {
        
        $this->testFindUserByEmail();

        $client = static::createClient(
                array(), array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            ));
        $client->followRedirects(true);
        $crawler = $client->request('POST', '/register/');

        $client->enableProfiler();

        $this->assertEquals('Dhi\UserBundle\Controller\RegistrationController::registerAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertRegExp('/^[A-Za-z0-9-_!#$]+$/', $this->testUsername);
        
        $this->assertFalse(strlen($this->testUsername) < 6, 'The username minimum length should be 6');
        $this->assertFalse(strlen($this->testUsername) > 32, 'The username maximum length should be 32');
        
        
        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->testEmail);
        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->testComfirmEmail);
        $this->assertSame($this->testEmail, $this->testComfirmEmail, 'Email and confirm email do not match.');
        
        
        $this->assertFalse(strlen($this->testPassword) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($this->testPassword) > 18, 'The password maximum length should be 18');
        $this->assertFalse(strlen($this->testComfirmPassword) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($this->testComfirmPassword) > 18, 'The password maximum length should be 18');
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testPassword);
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testComfirmPassword);
        $this->assertSame($this->testPassword, $this->testComfirmPassword, 'Password does not match the confirm password.');

        
        $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->testFname);
        $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->testLname);
        
        $this->assertFalse(strlen($this->testFname) < 3, 'Your firstname must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testLname) < 3, 'Your lastname must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testAddress) < 3, 'Your address must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testCity) < 3, 'Your city must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testZip) < 5, 'Your zip must have minimum 5 characters.');

        
        $form = $crawler->selectButton('Register')->form();
        $this->assertEquals(1, $crawler->filter('html:contains("Create an account")')->count());
        
        $objUserForUserName = $user = $this->em->getRepository('DhiUserBundle:User')
                ->findOneBy(array('username' => $this->testUsername));
        $this->assertEquals(0, count($objUserForUserName));
        
        
        $objUserForEmail = $user = $this->em->getRepository('DhiUserBundle:User')
                ->findOneBy(array('email' => $this->testEmail));
        $this->assertEquals(0, count($objUserForEmail));
        
        
        if(!$objUserForUserName && !$objUserForEmail){
            $form['fos_user_registration_form[username]'] = $this->testUsername;
            $form['fos_user_registration_form[email][first]'] = $this->testEmail;
            $form['fos_user_registration_form[email][second]'] = $this->testComfirmEmail;
            $form['fos_user_registration_form[plainPassword][first]'] = $this->testPassword;
            $form['fos_user_registration_form[plainPassword][second]'] = $this->testComfirmPassword;
            $form['fos_user_registration_form[firstname]'] = $this->testFname;
            $form['fos_user_registration_form[lastname]'] = $this->testLname;
            $form['fos_user_registration_form[address]'] = $this->testAddress;
            $form['fos_user_registration_form[city]'] = $this->testCity;
            $form['fos_user_registration_form[state]'] = $this->testState;
            $form['fos_user_registration_form[zip]'] = $this->testZip;
            $form['fos_user_registration_form[country]'] = $this->testCountry;

            $crawler = $client->submit($form);
            $this->assertEquals(1, $crawler->filter('html:contains("Welcome")')->count());

        }
        
    }

    /*
     * Netgate User Regisration
     */
    
    public function testNetgateUserRegistrationAction() {
        
        $client = static::createClient(
                array(), array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            ));
        $client->followRedirects(true);
        $crawler = $client->request('POST', '/netgate/register');

        $client->enableProfiler();

        $this->assertEquals('Dhi\UserBundle\Controller\RegistrationController::registerAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertRegExp('/^[A-Za-z0-9-_!#$]+$/', $this->testNetgateUsername);
        //$this->assertRegExp('/^tji\/|^bsm\//i', $this->testUsername);
        
        $this->assertFalse(strlen($this->testNetgateUsername) < 6, 'The username minimum length should be 6');
        $this->assertFalse(strlen($this->testNetgateUsername) > 32, 'The username maximum length should be 32');
        
        
        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->testNetgateEmail);
        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $this->testNetgateComfirmEmail);
        $this->assertSame($this->testNetgateEmail, $this->testNetgateComfirmEmail, 'Email and confirm email do not match.');
        
        
        $this->assertFalse(strlen($this->testNetgatePassword) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($this->testNetgatePassword) > 18, 'The password maximum length should be 18');
        $this->assertFalse(strlen($this->testNetgateComfirmPassword) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($this->testNetgateComfirmPassword) > 18, 'The password maximum length should be 18');
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testNetgatePassword);
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $this->testNetgateComfirmPassword);
        $this->assertSame($this->testNetgatePassword, $this->testNetgateComfirmPassword, 'Password does not match the confirm password.');

        
        $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->testNetgateFname);
        $this->assertRegExp('/^[A-Za-z0-9 _-]+$/', $this->testNetgateLname);
        
        $this->assertFalse(strlen($this->testNetgateFname) < 3, 'Your firstname must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testNetgateLname) < 3, 'Your lastname must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testNetgateAddress) < 3, 'Your address must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testNetgateCity) < 3, 'Your city must have minimum 3 characters.');
        $this->assertFalse(strlen($this->testNetgateZip) < 5, 'Your zip must have minimum 5 characters.');

        
        $form = $crawler->selectButton('Register')->form();
        $this->assertEquals(1, $crawler->filter('html:contains("Create an account")')->count());
        
        $objUserForUserName = $user = $this->em->getRepository('DhiUserBundle:User')
                ->findOneBy(array('username' => $this->testNetgateUsername));
        $this->assertEquals(0, count($objUserForUserName));
        
        
        $objUserForEmail = $user = $this->em->getRepository('DhiUserBundle:User')
                ->findOneBy(array('email' => $this->testNetgateEmail));
        $this->assertEquals(0, count($objUserForEmail));
        
        
        if(!$objUserForUserName && !$objUserForEmail){
            $form['fos_user_registration_form[username]'] = $this->testNetgateUsername;
            $form['fos_user_registration_form[email][first]'] = $this->testNetgateEmail;
            $form['fos_user_registration_form[email][second]'] = $this->testNetgateComfirmEmail;
            $form['fos_user_registration_form[plainPassword][first]'] = $this->testNetgatePassword;
            $form['fos_user_registration_form[plainPassword][second]'] = $this->testNetgateComfirmPassword;
            $form['fos_user_registration_form[firstname]'] = $this->testNetgateFname;
            $form['fos_user_registration_form[lastname]'] = $this->testNetgateLname;
            $form['fos_user_registration_form[address]'] = $this->testNetgateAddress;
            $form['fos_user_registration_form[city]'] = $this->testNetgateCity;
            $form['fos_user_registration_form[state]'] = $this->testNetgateState;
            $form['fos_user_registration_form[zip]'] = $this->testNetgateZip;
            $form['fos_user_registration_form[country]'] = $this->testNetgateCountry;

            $crawler = $client->submit($form);
            $this->assertEquals(1, $crawler->filter('html:contains("Welcome")')->count());

        }
        
    }
    
    public function testRegistrationConfirm() {
        
        
        $client = static::createClient(
                array(), array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            ));
        $client->followRedirects(true);
        $user = $this->em->getRepository('DhiUserBundle:User')->findOneByUsername($this->testUsername);
        if(!$user){
            $user = $this->em->getRepository('DhiUserBundle:User')->findOneByUsername($this->testNetgateUsername);
        }
        $this->assertFalse(count($user) == 0, 'User not found');
        $crawler = $client->request('GET', '/register/confirm/' . $user->getConfirmationToken());

        $today = new \DateTime();
        $diff = $today->diff($user->getEmailVerificationDate());
        $hours = $diff->h;
        $hours = $hours + ($diff->days * 24);
        $this->assertTrue($hours < 72, 'Registration confirmation link is valid.');

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
