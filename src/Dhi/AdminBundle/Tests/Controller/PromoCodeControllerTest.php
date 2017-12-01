<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * 
 */
class PromoCodeControllerTest extends WebTestCase {

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

        $crawler = $client->request('GET', '/admin/promo-code-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Customer Promo Code")')->count());
    }

    public function testpromoCodeListJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/promo-code-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));


        $crawler = $client->request('GET', '/admin/promo-code-list-json?sSearch_0=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/promo-code-list-json?sSearch_1=IPTV');
        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("IPTV")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/promo-code-list-json?sSearch_3=vqxk6');
        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("vqxk6")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/promo-code-list-json?createdBy=admin');
        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("admin")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/promo-code-list-json?sSearch_9=10/24/2016~10/25/2016');
        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::promoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
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

        $crawler = $client->request('POST', '/admin/add-new-promo-code');

        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::newAction', $client->getRequest()->attributes->get('_controller'));

        $test_service_location = 'BAF';
        $test_service = 'IPTV';
        
        $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array( 'name' => $test_service_location ));
        if(!$objServiceLocation){
            $this->assertFalse(true, 'Service location "' . $test_service_location . '" not found.');
        }
        $objService = $this->em->getRepository('DhiUserBundle:Service')->findOneBy(array( 'name' => $test_service ));
        if(!$objService){
            $this->assertFalse(true, 'Service "' . $test_service . '" not found.');
        }
        $objPackage = $this->em->getRepository('DhiAdminBundle:Package')->findOneBy(array('serviceLocation' =>  $objServiceLocation->getId(), 'status' => 1, 'isExpired' => 0));
        if(!$objPackage){
            $this->assertFalse(true, 'Package not found.');
        }
        
        $form = $crawler->selectButton('add')->form();

        $form['dhi_admin_promo_code[serviceLocations]'] = $objServiceLocation->getId();
        $form['dhi_admin_promo_code[service]'] = $objService->getId();
        $form['dhi_admin_promo_code[packageId]'] = $objPackage->getPackageId();
        $form['dhi_admin_promo_code[note]'] = 'This is testing';
        $form['dhi_admin_promo_code[expiredAt]'] = '10-31-2016';
        $form['dhi_admin_promo_code[noOfCodes]'] = 1;
        $form['dhi_admin_promo_code[duration]'] = 24;

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Promo Code added successfully.")')->count());
    }

    public function testeditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $objPromoCode = $this->em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertEquals(1, count($objPromoCode));

        if ($objPromoCode) {

            $this->assertFalse($objPromoCode->getNoOfRedemption() == 1, "This Promo Code already reedemded so can't edit");
            $crawler = $client->request('POST', '/admin/edit-promo-code/'.$objPromoCode->getId());


            $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::editAction', $client->getRequest()->attributes->get('_controller'));

            $form = $crawler->selectButton('Update')->form();

            $form['dhi_admin_promo_code[serviceLocations]'] = 1;
            $form['dhi_admin_promo_code[service]'] = 1;
            $form['dhi_admin_promo_code[packageId]'] = 10001;
            $form['dhi_admin_promo_code[note]'] = 'This is testing for Update';
            $form['dhi_admin_promo_code[expiredAt]'] = '10-31-2016';
            $form['dhi_admin_promo_code[duration]'] = 50;

            $client->submit($form);

            $client->followRedirect(true);
            $crawler = $client->getCrawler();

            $this->assertEquals(1, $crawler->filter('html:contains("Promo Code updated successfully.")')->count());
        }
    }

    public function testdisableAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objPromoCode = $this->em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array(), array('id' => 'DESC'));

        $this->assertEquals(1, count($objPromoCode));
        $this->assertFalse(count($objPromoCode) != 1, 'Unable to find promo code.');
        if ($objPromoCode) {

            $this->assertFalse($objPromoCode->getNoOfRedemption() == 1, "This Promo Code already reedemded so can't delete");
            $crawler = $client->request('POST', '/admin/disable-promo-code/'.$objPromoCode->getId());

            $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::disableAction', $client->getRequest()->attributes->get('_controller'));
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

        $objPromoCode = $this->em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertEquals(1, count($objPromoCode));

        if ($objPromoCode) {

            $this->assertFalse($objPromoCode->getNoOfRedemption() == 1, "This Promo Code already reedemded so can't delete");
            $crawler = $client->request('POST', '/admin/delete-promo-code?id=52');
            
            $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            $responce = $client->getResponse();
            $data  = json_decode($responce->getContent());
            $this->assertFalse($data->type != 'success', $data->message);
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
