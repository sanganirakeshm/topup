<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserMacAddressControllerTest extends WebTestCase {
     /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    public function testmacAddress(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
         $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

    }
    
    
    public function testlistMacAddress(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array(), array('id' => 'DESC'));
       $this->assertFalse(count($objUser) ==  0, 'User Mac Address record not found.');
        $crawler = $client->request('GET', '/admin/mac-address-list/'.$objUser->getId());
        $this->assertEquals('Dhi\AdminBundle\Controller\UserMacAddressController::listMacAddressAction', $client->getRequest()->attributes->get('_controller'));
        $respone = $client->getResponse();
        $data = json_decode($respone->getContent());
        $this->assertFalse($data->status == 'failure', 'No record found');
    }


    public function testtransferDevice(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       $objUserMacAddress = $this->em->getRepository('DhiUserBundle:UserMacAddress')->findOneBy(array(), array('id' => 'DESC'));
       $this->assertFalse(count($objUserMacAddress) ==  0, 'User Mac Address record not found.');
       $crawler = $client->request('POST', '/admin/transfer-mac-address/'.$objUserMacAddress->getId().'/'.$objUserMacAddress->getUser()->getId());
       
       $client->enableProfiler();
       
        
        $this->assertEquals('Dhi\AdminBundle\Controller\UserMacAddressController::transferDeviceAction', $client->getRequest()->attributes->get('_controller'));
        
        $form = $crawler->selectButton('Transfer')->form();
        $form['dhi_transfer_mac_address[macAddress]'] = '48:51:B7:F2:67:82';
        $form['dhi_transfer_mac_address[user]'] = 5;
        $crawler = $client->submit($form);
               
        $this->assertTrue($client->getResponse()->isSuccessful(),'Device Transfer successfully');
    } 


    public function testDeleteMacAddress() {
        $client = static::createClient(
                    array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $userId = 3;
        $user = $this->em->getRepository('DhiUserBundle:User')->find($userId);
        if($user){
            $macAddress = $this->em->getRepository('DhiUserBundle:UserMacAddress')->findOneBy(array(), array('id' => 'DESC'));
            if($macAddress)
            {
                $crawler = $client->request('POST', '/admin/mac-address-remove/'.$macAddress->getId().'/'.$macAddress->getUser()->getId(), array(
                    'id' => $macAddress->getId()
                ));
                $this->assertEquals('Dhi\AdminBundle\Controller\UserMacAddressController::deleteMacAddressAction', $client->getRequest()->attributes->get('_controller'));
            }
        }
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

