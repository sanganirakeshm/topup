<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PromotionControllerTest extends WebTestCase {
    
    protected $testHttpHost;
    protected $adminUserName;
    protected $adminPassword;

    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->adminUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->adminPassword = static::$kernel->getContainer()->getParameter('test_admin_password');

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

        $crawler = $client->request('GET', '/admin/promotion-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\PromotionController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Global Promotion ")')->count());
    }
    
    public function testlistJsonAction() {
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/promotion-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\PromotionController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        
        $crawler = $client->request('GET', '/admin/promotion-list-json?sSearch_3=Active');
        $this->assertEquals('Dhi\AdminBundle\Controller\PromotionController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Status")')->count();
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
        
        $crawler = $client->request('POST', '/admin/add-new-promotion');

        $this->assertEquals('Dhi\AdminBundle\Controller\PromotionController::newAction', $client->getRequest()->attributes->get('_controller'));
       
        $startDate = '12-24-2016'; 
        $endDate = '01-31-2017';
        
        $sDate = new DateTime();
        $sDate = $sDate->createFromFormat('m-d-Y', $startDate);
        
        $eDate = new DateTime();
        $eDate = $eDate->createFromFormat('m-d-Y', $endDate);
        
        $promotion = $this->em->getRepository("DhiAdminBundle:Promotion")->checkDateRange($sDate, $eDate, 0);
        
        if ($promotion) {
           $this->assertFalse($promotion, 'Promotion already exits for given date Range.');
        }
        
        $form = $crawler->selectButton('add')->form();

        $form['dhi_admin_promotion[serviceLocations]'] = 1;
        $form['dhi_admin_promotion[startDate]'] = '12-24-2016';
        $form['dhi_admin_promotion[endDate]'] = '01-31-2017';
        $form['dhi_admin_promotion[amountType]'] = 'p';
        $form['dhi_admin_promotion[amount]'] = 10;
        $form['dhi_admin_promotion[isActive]'] = 1;
        $form['dhi_admin_promotion[bannerImage]'] = '1.jpg';
            
        $client->submit($form);
        
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Global Promotion added successfully.")')->count());
        
    }
    
    public function testeditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        
        $crawler = $client->request('POST', '/admin/edit-promotion/11');

        $this->assertEquals('Dhi\AdminBundle\Controller\PromotionController::editAction', $client->getRequest()->attributes->get('_controller'));
       
        $startDate = '12-24-2016'; 
        $endDate = '01-31-2017';
        
        $sDate = new DateTime();
        $sDate = $sDate->createFromFormat('m-d-Y', $startDate);
        
        $eDate = new DateTime();
        $eDate = $eDate->createFromFormat('m-d-Y', $endDate);
        
        $promotion = $this->em->getRepository("DhiAdminBundle:Promotion")->checkDateRange($sDate, $eDate, 11);
        
        if ($promotion) {
           $this->assertFalse($promotion, 'Promotion already exits for given date Range.');
        }
        
        $form = $crawler->selectButton('Update')->form();

        $form['dhi_admin_promotion[serviceLocations]'] = 1;
        $form['dhi_admin_promotion[startDate]'] = '12-24-2016';
        $form['dhi_admin_promotion[endDate]'] = '01-31-2017';
        $form['dhi_admin_promotion[amountType]'] = 'p';
        $form['dhi_admin_promotion[amount]'] = 10;
        $form['dhi_admin_promotion[isActive]'] = 1;
        $form['dhi_admin_promotion[bannerImage]'] = '1.jpg';
            
        $client->submit($form);
        
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Global Promotion updated successfully.")')->count());
        
    }
    
    public function testchangeStatusAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $promotion = $this->em->getRepository("DhiAdminBundle:Promotion")->find(11);
        $this->assertEquals(1, count($objDiscountCode));
        $this->assertFalse(count($promotion) != 1, 'Unable to find global promotion.');
        
        if ($promotion) {

            $crawler = $client->request('POST', '/admin/change-promotion-status/' . $objDiscountCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\PromotionController::changeStatusAction', $client->getRequest()->attributes->get('_controller'));

            $crawler = $client->request('GET', '/admin/promotion-list');
            $this->assertEquals('Dhi\AdminBundle\Controller\PromotionController::indexAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("successfully.")')->count());
        }
    }
}    