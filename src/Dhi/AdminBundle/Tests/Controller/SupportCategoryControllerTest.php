<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SupportCategoryControllerTest extends WebTestCase {

    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->conn = static::$kernel->getContainer()->get('database_connection');
    }
     public function testindex(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/support-category/category-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Support Category")')->count());
    }
    
    
    public function testsupportCategoryListJson(){
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       $crawler = $client->request('GET', '/admin/support-category-list-json');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::supportCategoryListJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(0, $crawler->filter('html:contains("Support Category")')->count());
        
        $crawler = $client->request('GET', '/admin/support-category-list-json?sSearch_1=Other');

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::supportCategoryListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Name of support category")')->count());
        
        $crawler = $client->request('GET', '/admin/support-category-list-json?supportsite=2');

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::supportCategoryListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Support SIte")')->count());
        
    }
    
        
    /**
     * This action allows to test add support category
     */
    public function testAddSupportCategory() {
       
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
         
        $crawler = $client->request('POST', '/admin/support-category/add-category');
        $client->enableProfiler();
        
        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::newAction', $client->getRequest()->attributes->get('_controller'));
         
       $form = $crawler->selectButton('add')->form();
       $siteId = $this->getsiteId();
        if(empty($siteId['lastinsertId'])){
            $this->assertFalse(true, 'Enable to find site');
        }
       $form['dhi_user_support_category[supportsite]'] = $siteId['lastinsertId'];
       $form['dhi_user_support_category[name]'] = "UnitTesting";
        
        $client->submit($form);
         
        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Support category added successfully.")')->count());
    }

    /**
     * This action allows to test edit support category
     */
    public function testEditSupportCategory() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $objSetting = $this->em->getRepository('DhiUserBundle:SupportCategory')
                ->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objSetting) == 0, 'SupportCategory not found');
        
        $crawler = $client->request('POST', '/admin/support-category/edit-category/' . $objSetting->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::editAction', $client->getRequest()->attributes->get('_controller'));
        $siteId = $this->getsiteId();
        if(empty($siteId['lastinsertId'])){
            $this->assertFalse(true, 'Enable to find site');
        }
        $form = $crawler->selectButton('update')->form();
        $form['dhi_user_support_category[supportsite]'] = $siteId['lastinsertId'];
        $form['dhi_user_support_category[name]'] = "UnitTesting1";
        
        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Support category updated successfully")')->count());
    }
    
    /**
     * This action allows to test edit support category
     */
    public function testDeleteSupportCategory() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $objSetting = $this->em->getRepository('DhiUserBundle:SupportCategory')
                ->findOneBy(array(), array('id' => 'DESC'));
        
        $crawler = $client->request('GET', '/admin/support-category/delete-category?id='. $objSetting->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::deleteAction', $client->getRequest()->attributes->get('_controller'));
        
        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->type != 'success', $data->message);
    }
    
        
    /**
     *  General function for Admin login
     */
    public function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', '/admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
    }
    
    
    public function  getsiteId(){
        
        $sql = "select id as lastinsertId from white_label WHERE is_active = '1' AND is_deleted = '0' ORDER BY id DESC";

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();
        
        return $lastInsertedId;
    }
    
    public function testupdateSequenceNumberAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $siteId = $this->getsiteId();
        if(empty($siteId['lastinsertId'])){
            $this->assertFalse(true, 'Enable to find site');
        }
        $crawler = $client->request('POST', '/admin/support-category/sequence-number?siteId=' . $siteId['lastinsertId']. '&startlimitnum=1&oldSequenceNum=2&newSequenceNum=1');

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::updateSequenceNumberAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->type != 'success', $data->message);
    }
    
    public function testcheckDuplicateAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $siteId = $this->getsiteId();
        if(empty($siteId['lastinsertId'])){
            $this->assertFalse(true, 'Enable to find site');
        }
        $categoryName = static::$kernel->getContainer()->getParameter('test_support_category_name');
        $crawler = $client->request('POST', '/admin/support-category/check-duplicate?supportSite=' . $siteId['lastinsertId']. '&name='.$categoryName);

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportCategoryController::checkDuplicateAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = $response->getContent();
        $this->assertFalse($data != 'true', 'Support category is already exists.');
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
