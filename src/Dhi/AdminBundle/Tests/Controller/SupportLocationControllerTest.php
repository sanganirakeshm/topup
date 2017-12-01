<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SupportLocationControllerTest extends WebTestCase {

    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
         $this->conn = static::$kernel->getContainer()->get('database_connection');
    }
    
    /**
     *  General function for Admin login
     */
    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', '/admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect();
    }
    
    /**
     * This action allows to test add support location
     */
    public function testAddSupportLocation() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/support-location/add-location');

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportLocationController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('add')->form();
        $siteId = $this->getsiteId();
        if(empty($siteId['lastinsertId'])){
            $this->assertFalse(true, 'Enable to find site');
        }
        $form['dhi_user_support_location[supportsite]']   = $siteId['lastinsertId'];
        $form['dhi_user_support_location[name]'] = "NewOne";
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-location/location-list')); // check if redirecting properly

        $crawler = $client->followRedirect();

        $this->assertEquals(1, $crawler->filter('html:contains("Support location added successfully.")')->count());
    }

    /**
     * This action allows to test edit support location
     */
    public function testEditSupportLocation() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $objSupportLocation = $this->em->getRepository('DhiUserBundle:SupportLocation')
                ->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objSupportLocation) == 0, 'Support location not found');
        $crawler = $client->request('POST', '/admin/support-location/edit-location/' . $objSupportLocation->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportLocationController::editAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('update')->form();
        $siteId = $this->getsiteId();
        if(empty($siteId['lastinsertId'])){
            $this->assertFalse(true, 'Enable to find site');
        }
        $form['dhi_user_support_location[supportsite]']   = $siteId['lastinsertId'];
        $form['dhi_user_support_location[name]'] = "UpdatedNew";
        
        $client->submit($form);
        
        $this->assertTrue($client->getResponse()->isRedirect('/admin/support-location/location-list')); // check if redirecting properly
        
        $crawler = $client->followRedirect();
        
        $this->assertEquals(1, $crawler->filter('html:contains("Support location updated successfully.")')->count());
    }
    
    /**
     * This action allows to test edit support location
     */
    public function testDeleteSupportLocation() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $objSupportLocation = $this->em->getRepository('DhiUserBundle:SupportLocation')
                ->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objSupportLocation) == 0, 'Support location not found');
        $crawler = $client->request('POST', '/admin/support-location/delete-location?id=' . $objSupportLocation->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportLocationController::deleteAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->type != 'success', $data->message);
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
        $crawler = $client->request('POST', '/admin/support-location/sequence-number?siteId=' . $siteId['lastinsertId']. '&startlimitnum=1&oldSequenceNum=2&newSequenceNum=1');

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportLocationController::updateSequenceNumberAction', $client->getRequest()->attributes->get('_controller'));

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
        $locationName = static::$kernel->getContainer()->getParameter('test_support_location_name');
        $crawler = $client->request('POST', '/admin/support-location/check-duplicate?supportSite=' . $siteId['lastinsertId']. '&name='.$locationName);

        $this->assertEquals('Dhi\AdminBundle\Controller\SupportLocationController::checkDuplicateAction', $client->getRequest()->attributes->get('_controller'));

        $response = $client->getResponse();
        $data = $response->getContent();
        $this->assertFalse($data != 'true', 'Support lcoation is already exists.');
    }
    
    /**
     *  Testing for Admin login
     */
    public function testLogin() {
        
         $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $username = static::$kernel->getContainer()->getParameter('test_admin_username');
        $password = static::$kernel->getContainer()->getParameter('test_admin_password');
        
        $crawler = $client->request('GET', '/admin/login');

        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect('/admin/dashboard')); // check if redirecting properly

        $client->followRedirect();
    }
    
    public function  getsiteId(){
        
         $sql = "select id as lastinsertId from white_label WHERE is_active = '1' AND is_deleted = '0' ORDER BY id DESC";

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();
        
        return $lastInsertedId;
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
