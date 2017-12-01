<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FreeRechargeCardControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp() {
       static::$kernel = static::createKernel();
       static::$kernel->boot();
       $this->conn = static::$kernel->getContainer()->get('database_connection');
    }
    
    public function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', 'admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }
    
    public function testIndexAction() {
        $client = static::createClient(
            array(), array(
            'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/free-recharge-card');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Free Recharge Card")')->count());
    }
    
    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/free-recharge-card/list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/free-recharge-card/list-json?sSearch_0=bkpbv_test ');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("bkpbv_test")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/free-recharge-card/list-json?sSearch_1=bkpbv_test@brainvire.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("bkpbv_test@brainvire.com")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/free-recharge-card/list-json?sSearch_2=06/01/2017~06/30/2017');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Jun-01-2017")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
                
    }
    
    public function testexportCsvAction() {

        $client = static::createClient(
            array(), array(
            'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/free-recharge-card');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Free Recharge Card")')->count());
        
        $form = $crawler->selectLink('Export CSV')->first()->link();
        $crawler = $client->request('GET', '/admin/free-recharge-card/export-csv?offset=0');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
    }
    
    public function testmarkUserAction(){

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/free-recharge-card/new');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::markUserAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Check Eligibility")')->count());
        
        $sql = 'SELECT u.username as username FROM dhi_user u 
            WHERE u.is_deleted = 0
            AND u.locked = 0
            AND u.roles LIKE "%ROLE_USER%"
            ORDER BY u.id DESC
            LIMIT 1';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $user = $query->fetch();
        
        if(empty($user)){
            $this->assertFalse(true, 'Unable to find user.');
        }
        
        $form = $crawler->selectButton('Mark as Free Recharge Card')->form();
        $form['dhi_admin_free_recharge_card[userId]'] = $user['username'];

        $client->submit($form);

        $crawler = $client->getCrawler();
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        
        $this->assertEquals(1, $crawler->filter('html:contains("User marked for Free Recharge Card successfully.")')->count());
    }
    
    public function textcheckEligibilityAction(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/free-recharge-card/new');
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::markUserAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Check Eligibility")')->count());
        
        $sql = 'SELECT u.username as username FROM dhi_user u 
            WHERE u.is_deleted = 0
            AND u.locked = 0
            AND u.roles LIKE "%ROLE_USER%"
            ORDER BY u.id DESC
            LIMIT 1';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $user = $query->fetch();
        
        if(empty($user)){
            $this->assertFalse(true, 'Unable to find user.');
        }
        
        $crawler = $client->request('POST', '/admin/free-recharge-card/check-eligibility?usernameOrEmail='.$user['username']);
        $this->assertEquals('Dhi\AdminBundle\Controller\FreeRechargeCardController::checkEligibilityAction', $client->getRequest()->attributes->get('_controller'));
        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->result != 'success', $data->errMsg);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        parent::tearDown();

    }
}
