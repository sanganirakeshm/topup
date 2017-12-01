<?php
namespace Dhi\AdminBundle\Controller\Tests\Controller;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UnAssgnedPromoCodesControllerTest extends WebTestCase {

    protected $serviceLocation, $service, $packageId;

	protected function setUp() {
        static::$kernel         = static::createKernel();
        static::$kernel->boot();
        $this->container        = static::$kernel->getContainer();
        $this->em               = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->testHttpHost     = static::$kernel->getContainer()->getParameter('test_http_host');
        $_SERVER['REMOTE_ADDR'] = static::$kernel->getContainer()->getParameter('test_client_ip_address');
        $this->adminUserName    = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->adminPassword    = static::$kernel->getContainer()->getParameter('test_admin_password');

        $this->serviceLocation = static::$kernel->getContainer()->getParameter('test_reassign_pc_service_location');
        $this->service         = static::$kernel->getContainer()->getParameter('test_reassign_pc_service');
        $this->packageId       = static::$kernel->getContainer()->getParameter('test_reassign_pc_package_id');
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

        $client = static::createClient(array(), array(
			'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin(
        	$this->adminUserName, $this->adminPassword,
        	$client);

        $crawler = $client->request('POST', '/admin/unassigned-promo-codes');
        $this->assertEquals('Dhi\AdminBundle\Controller\UnAssignedPromoCodesController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("UnAssigned Promo Code")')->count());
    }

    public function testDeleteAction(){
        $client = static::createClient(
            array(),
            array(
                'HTTP_HOST' => $this->testHttpHost
            )
        );

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        // Customer
        $objPromoCode = $this->em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array( 'isPlanExpired' => 'Yes' ));
        if ($objPromoCode){
            $this->assertEquals(1, count($objPromoCode));
            $crawler = $client->request('POST', '/admin/unassigned-promo-codes-delete?id=customer^'.$objPromoCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\UnAssignedPromoCodesController::deleteAction', $client->getRequest()->attributes->get('_controller'));

            $response = $client->getResponse();
            $data     = json_decode($response->getContent());
            $this->assertEquals($data->type, "success");
        }

        // Partner
        $objPartnerPromoCode = $this->em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array( 'isPlanExpired' => 'Yes' ));
        if ($objPartnerPromoCode){
            $this->assertEquals(1, count($objPartnerPromoCode));
            $crawler = $client->request('POST', '/admin/unassigned-promo-codes-delete?id=partner^'.$objPartnerPromoCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\UnAssignedPromoCodesController::deleteAction', $client->getRequest()->attributes->get('_controller'));

            $response = $client->getResponse();
            $data     = json_decode($response->getContent());
            $this->assertEquals($data->type, "success");
        }

        // Business
        $objBusinessPromoCode = $this->em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findOneBy(array( 'isPlanExpired' => 'Yes' ));
        if ($objBusinessPromoCode){
            $this->assertEquals(1, count($objBusinessPromoCode));
            $crawler = $client->request('POST', '/admin/unassigned-promo-codes-delete?id=business^'.$objBusinessPromoCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\UnAssignedPromoCodesController::deleteAction', $client->getRequest()->attributes->get('_controller'));

            $response = $client->getResponse();
            $data     = json_decode($response->getContent());
            $this->assertEquals($data->type, "success");
        }
    }

    public function testReAssignAction(){
        $client = static::createClient(
            array(),
            array(
                'HTTP_HOST' => $this->testHttpHost
            )
        );

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $test_service_location = $this->serviceLocation;
        $test_service          = $this->service;
        $test_package_id       = $this->packageId;

        // Customer
        $objPromoCode = $this->em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array( 'isPlanExpired' => 'Yes' ));
        if ($objPromoCode){

            $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array( 'name' => $test_service_location ));
            if(!$objServiceLocation){
                $this->assertFalse(true, 'Service location "' . $test_service_location . '" not found.');
            }

            $objService = $this->em->getRepository('DhiUserBundle:Service')->findOneBy(array( 'name' => $test_service ));
            if(!$objService){
                $this->assertFalse(true, 'Service "' . $test_service . '" not found.');
            }

            $objPackage = $this->em->getRepository('DhiAdminBundle:Package')->findOneBy(array( 'packageId' => $test_package_id ));
            if(!$objPackage){
                $this->assertFalse(true, 'Package id "' . $test_package_id . '" not found.');
            }

            $crawler = $client->request('POST', '/admin/unassigned-promo-codes/reassign/customer/'.$objPromoCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\UnAssignedPromoCodesController::reAssignAction', $client->getRequest()->attributes->get('_controller'));

            $form                                                    = $crawler->selectButton('Reassign To Customer Promo Code')->form();
            $form['dhi_admin_customer_promo_code[serviceLocations]'] = $objServiceLocation->getId();
            $form['dhi_admin_customer_promo_code[service]']          = $objService->getId();
            $form['dhi_admin_customer_promo_code[packageId]']        = $test_package_id;
            $form['dhi_admin_customer_promo_code[note]']             = 'This is testing';
            $form['dhi_admin_customer_promo_code[expiredAt]']        = '12-31-2016';
            $form['dhi_admin_customer_promo_code[duration]']         = 24;
            $client->submit($form);
            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("Promo Code Reassign Successfully.")')->count());
        }
    }

    public function testBulkReAssignAction(){
        $client = static::createClient(
            array(),
            array(
                'HTTP_HOST' => $this->testHttpHost
            )
        );

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        $test_service_location = $this->serviceLocation;
        $test_service          = $this->service;
        $test_package_id       = $this->packageId;

        $arrPromoCodes = array();
        $objPromoCode = $this->em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array('isPlanExpired' => 'Yes'));
        if ($objPromoCode) {
            $arrPromoCodes[] = 'customer~'.$objPromoCode->getId();
        }

        $objBusinessPromoCode = $this->em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findOneBy(array('isPlanExpired' => 'Yes'));
        if ($objBusinessPromoCode) {
            $arrPromoCodes[] = 'business~'.$objBusinessPromoCode->getId();
        }

        $objPartnerPromoCode = $this->em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('isPlanExpired' => 'Yes'));
        if ($objPartnerPromoCode) {
            $arrPromoCodes[] = 'partner~'.$objPartnerPromoCode->getId();
        }

        if (!empty($arrPromoCodes)){

            $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array( 'name' => $test_service_location ));
            if(!$objServiceLocation){
                $this->assertFalse(true, 'Service location "' . $test_service_location . '" not found.');
            }

            $objService = $this->em->getRepository('DhiUserBundle:Service')->findOneBy(array( 'name' => $test_service ));
            if(!$objService){
                $this->assertFalse(true, 'Service "' . $test_service . '" not found.');
            }

            $objPackage = $this->em->getRepository('DhiAdminBundle:Package')->findOneBy(array( 'packageId' => $test_package_id ));
            if(!$objPackage){
                $this->assertFalse(true, 'Package id "' . $test_package_id . '" not found.');
            }
            
            $date = new \DateTime();
            $date->modify('+20 DAYS');

            $crawler = $client->request('POST', '/admin/unassigned-promo-codes/bulk/reassign/', array('promoIds' => $arrPromoCodes));
            $this->assertEquals('Dhi\AdminBundle\Controller\UnAssignedPromoCodesController::bulkReAssignAction', $client->getRequest()->attributes->get('_controller'));

            $form                                                    = $crawler->selectButton('Reassign To Customer Promo Code')->form();
            $form['dhi_admin_customer_promo_code[serviceLocations]'] = $objServiceLocation->getId();
            $form['dhi_admin_customer_promo_code[service]']          = $objService->getId();
            $form['dhi_admin_customer_promo_code[packageId]']        = $test_package_id;
            $form['dhi_admin_customer_promo_code[note]']             = 'PHPUnit Testing';
            $form['dhi_admin_customer_promo_code[expiredAt]']        = $date->format('m-d-Y');
            $form['dhi_admin_customer_promo_code[duration]']         = 24;
            $client->submit($form);
            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("Promo Code(s) Reassign Successfully.")')->count());
        }
    }

    protected function tearDown(){
        parent::tearDown();

        $this->em->close();
        $this->em = null;
    }
}