<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InAppPromoCodeControllerTest extends WebTestCase {

    protected $testHttpHost;
    protected $adminUserName;
    protected $adminPassword;
    
    /**
    * {@inheritDoc}
    */
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

        $crawler = $client->request('GET', '/admin/in-app-promo-code-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("In App Promo Code")')->count());
    }
    
     public function testpromoCodeListJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/in-app-promo-code-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));


        $crawler = $client->request('GET', '/admin/in-app-promo-code-list-json?sSearch_0=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);

        $crawler = $client->request('GET', '/admin/in-app-promo-code-list-json?createdBy=admin');
        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("admin")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/in-app-promo-code-list-json?sSearch_8=10/24/2016~10/25/2016');
        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Oct-24-2016")')->count();
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

        $crawler = $client->request('POST', '/admin/add-new-in-app-promo-code');

        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::newAction', $client->getRequest()->attributes->get('_controller'));

        $test_service_location = 'BAF';
                
        $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array( 'name' => $test_service_location ));
        if(!$objServiceLocation){
            $this->assertFalse(true, 'Service location "' . $test_service_location . '" not found.');
        }
        
        $form = $crawler->selectButton('add')->form();

        $form['dhi_admin_in_app_promo_code[serviceLocations]'] = $objServiceLocation->getId();
        $form['dhi_admin_in_app_promo_code[note]'] = 'This is testing';
        $form['dhi_admin_in_app_promo_code[expiredAt]'] = '02-28-2017';
        $form['dhi_admin_in_app_promo_code[status]'] = 'Active';
        $form['dhi_admin_in_app_promo_code[amount]'] = 5;

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Promo Code added successfully.")')->count());
    }

    public function testeditinapppromocode() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $objPromoCode = $this->em->getRepository('DhiUserBundle:InAppPromoCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertEquals(1, count($objPromoCode));

        if ($objPromoCode) {

            $crawler = $client->request('POST', '/admin/edit-in-app-promo-code/'.$objPromoCode->getId());


            $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::editInAppPromocodeAction', $client->getRequest()->attributes->get('_controller'));

            $form = $crawler->selectButton('Update')->form();

            $form['dhi_admin_in_app_promo_code[serviceLocations]'] = 1;
            $form['dhi_admin_in_app_promo_code[note]'] = 'This is testing for Update';
            $form['dhi_admin_in_app_promo_code[expiredAt]'] = '02-28-2017';
            $form['dhi_admin_in_app_promo_code[status]'] = 'Active';
            $form['dhi_admin_in_app_promo_code[amount]'] = 10;

            $client->submit($form);

            $client->followRedirect(true);
            $crawler = $client->getCrawler();

            $this->assertEquals(0, $crawler->filter('html:contains("In App Promo Code updated successfully.")')->count());
        }
    }
    
      public function testdisableAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objPromoCode = $this->em->getRepository('DhiUserBundle:InAppPromoCode')->findOneBy(array(), array('id' => 'DESC'));

        $this->assertEquals(1, count($objPromoCode));
        $this->assertFalse(count($objPromoCode) != 1, 'Unable to find promo code.');
        if ($objPromoCode) {

            $this->assertFalse($objPromoCode->getIsRedeemed() == 'Yes', "This Promo Code already reedemded so can't delete");
            $crawler = $client->request('POST', '/admin/disable-in-app-promo-code/'.$objPromoCode->getId());

            $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::disableAction', $client->getRequest()->attributes->get('_controller'));
            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("successfully")')->count());
        }
    }
    
    public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objPromoCode = $this->em->getRepository('DhiUserBundle:InAppPromoCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertEquals(1, count($objPromoCode));

        if ($objPromoCode) {

            $this->assertFalse($objPromoCode->getIsRedeemed() == 'Yes', "This Promo Code already reedemded so can't delete");
            $crawler = $client->request('POST', '/admin/delete-in-app-promo-code?id=6');
            
            $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            $responce = $client->getResponse();
            $data  = json_decode($responce->getContent());
            $this->assertFalse($data->type != 'success', $data->message);
        }
    }
    
     public function testexportCsvAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/in-app-promo-code-export-csv',array("offset" => 0));

        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
    }
    
      public function testexportpdfAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/in-app-promo-code-export-pdf',array("offset" => 0));

        $this->assertEquals('Dhi\AdminBundle\Controller\InAppPromoCodeController::exportpdfAction', $client->getRequest()->attributes->get('_controller'));
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

