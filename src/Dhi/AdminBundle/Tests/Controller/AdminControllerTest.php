<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * 
 */
class AdminControllerTest extends WebTestCase {
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    /**
     * This action allows to test the updating account page for a user
     */
    public function testAccountData() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->hasParameter('test_admin_username') ? static::$kernel->getContainer()->getParameter('test_admin_username') : '', static::$kernel->getContainer()->hasParameter('test_admin_password') ? static::$kernel->getContainer()->getParameter('test_admin_password') : '', $client);
        $crawler = $client->request('POST', '/admin/add-new-admin');
        $client->enableProfiler();


        $email = static::$kernel->getContainer()->getParameter('test_new_admin_email');
        $username = static::$kernel->getContainer()->getParameter('test_new_admin_username');
        $password = static::$kernel->getContainer()->getParameter('test_new_admin_password');
        $repeatPassword = static::$kernel->getContainer()->getParameter('test_new_admin_repeat_password');
        $role = static::$kernel->getContainer()->getParameter('test_new_admin_role');
        $active = static::$kernel->getContainer()->getParameter('test_new_addmin_status');

        $this->assertFalse(strlen($username) < 6, 'The username minimum length should be 6');
        $this->assertFalse(strlen($username) > 32, 'The username maximum length should be 32');
        $this->assertFalse(strlen($password) < 8, 'The password minimum length should be 8');
        $this->assertFalse(strlen($password) > 18, 'The password maximum length should be 18');

        $this->assertRegExp('/^[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+\.[a-zA-Z]{2,4}/', $email);
        $this->assertRegExp('/^[A-Za-z0-9-_!#$]+$/', $username);
        $this->assertRegExp('/^[A-Za-z0-9!@#$_]+$/', $password);

        $this->assertEquals($password, $repeatPassword);
        $this->assertEquals('Dhi\AdminBundle\Controller\AdminController::newAction', $client->getRequest()->attributes->get('_controller'));

        $user = $this->em->getRepository('DhiUserBundle:User')->getUserByUsernameOrEmail($username, $email);
        if ($user) {
            $this->assertEquals($user['email'], $email);
        }
        $form = $crawler->selectButton('Add')->form();
        $form['dhi_admin_registration[email]'] = $email;
        $form['dhi_admin_registration[username]'] = $username;
        $form['dhi_admin_registration[plainPassword][first]'] = $password;
        $form['dhi_admin_registration[plainPassword][second]'] = $repeatPassword;
        $form['dhi_admin_registration[enabled]'] = $active;

        $crawler = $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Admin added successfully!")')->count());
    }

    private function getMockPasswordEncoder() {
        return $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
    }

    private function getUser() {
        return $this->getMockBuilder('FOS\UserBundle\Model\User')
                        ->getMockForAbstractClass();
    }

    private function getMockCanonicalizer() {
        return $this->getMock('FOS\UserBundle\Util\CanonicalizerInterface');
    }

    private function getMockEncoderFactory() {
        return $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');
    }

    private function getUserManager(array $args) {
        return $this->getMockBuilder('FOS\UserBundle\Model\UserManager')
                        ->setConstructorArgs($args)
                        ->getMockForAbstractClass();
    }

    public function doLogin($username, $password, $client) {
        $crawler = $client->request('POST', 'admin/login');
        //echo $client->getResponse()->getContent();exit;
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }
    
    /**
    * {@inheritDoc}
    */
    protected function tearDown(){
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }

}
