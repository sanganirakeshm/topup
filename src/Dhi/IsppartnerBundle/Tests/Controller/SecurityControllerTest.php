<?php

namespace Dhi\IsppartnerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SecurityControllerTest extends WebTestCase
{
 
    protected $ispPartnerUsername;
    protected $ispPartnerPassword;
    protected $currentPassword;
    protected $newPassword;
    protected $confirmNewPassword;
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {
        
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        
        $this->ispPartnerUsername = static::$kernel->getContainer()->getParameter('test_isp_partner_username');
        $this->ispPartnerPassword = static::$kernel->getContainer()->getParameter('test_isp_partner_password');
        $this->currentPassword = static::$kernel->getContainer()->getParameter('test_isp_partner_current_password');
        $this->newPassword = static::$kernel->getContainer()->getParameter('test_isp_partner_new_password');
        $this->confirmNewPassword = static::$kernel->getContainer()->getParameter('test_isp_partner_confiem_new_password');
    }
    
    public function doLogin($username, $password, $client) {
        
        $crawler = $client->request('POST', 'isp-partner/login');
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));
        
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }
    
    public function testresetpasswordAction() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin($this->ispPartnerUsername, $this->ispPartnerPassword, $client);
        $crawler = $client->request('POST', '/isp-partner/resetpassword');
        
        $this->assertEquals('Dhi\IsppartnerBundle\Controller\SecurityController::resetpasswordAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Change Password")')->count());
        
        
        $form = $crawler->selectButton('Update')->form();
        $form['dhi_isppartner_user[current_password]'] = $this->currentPassword;
        $form['dhi_isppartner_user[password][first]'] = $this->newPassword;
        $form['dhi_isppartner_user[password][second]'] = $this->confirmNewPassword;
        
        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $crawler = $client->request('GET', '/isp-partner/dashboard');
        
        $this->assertEquals('Dhi\IsppartnerBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        
        $this->assertEquals(1, $crawler->filter('html:contains("Password has been changed successfully.")')->count());
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
