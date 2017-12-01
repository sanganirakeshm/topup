<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 *
 */
class ServicePartnerControllerTest extends WebTestCase {

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

        $crawler = $client->request('GET', '/admin/service-partner-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partners")')->count());
    }

    public function testdiscountCodeListJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/service-partner-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partners")')->count());


        $crawler = $client->request('GET', '/admin/service-partner-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::adminListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/service-partner-list-json?sSearch_1=QNET');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::adminListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("QNET")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/service-partner-list-json?sSearch_2=12345');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::adminListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("12345")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/service-partner-list-json?sSearch_3=vch@gmail.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::adminListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("vch@gmail.com")')->count();
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


        $crawler = $client->request('GET', '/admin/service-partner-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partners")')->count());


        $discountCode = 'VIVA10';
        $crawler = $client->request('POST', '/admin/add-new-partner');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::newAction', $client->getRequest()->attributes->get('_controller'));

//        $objDiscountCode = $this->em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('discountCode' => $discountCode));
//
//        $this->assertFalse(count($objDiscountCode) != 0, 'Global Promo Code already exists');
//        if (!$objDiscountCode) {
        $form = $crawler->selectButton('add')->form();

        $form['dhi_admin_service_partner[name]'] = static::$kernel->getContainer()->getParameter('test_isp_service_partner_name');
        $form['dhi_admin_service_partner[description]'] = 'test partnet description';
        $form['dhi_admin_service_partner[pocName]'] = 'tp';
        $form['dhi_admin_service_partner[pocEmail]'] = 'testpartner@brainvire.com';
        $form['dhi_admin_service_partner[pocPhone]'] = '3211234332';
        $form['dhi_admin_service_partner[status]'] = 1;

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partner added successfully!")')->count());
//        }
    }

    public function testeditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/service-partner-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partners")')->count());

        $objDiscountCode = $this->em->getRepository('DhiAdminBundle:ServicePartner')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objDiscountCode) == 0, 'ServicePartner not found');
        $crawler = $client->request('POST', '/admin/edit-service-partner/' . $objDiscountCode->getId());


        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::editAction', $client->getRequest()->attributes->get('_controller'));
        $form = $crawler->selectButton('Update')->form();

        $form['dhi_admin_service_partner[name]'] = static::$kernel->getContainer()->getParameter('test_isp_service_partner_name').'Update';
        $form['dhi_admin_service_partner[description]'] = 'test partnet description';
        $form['dhi_admin_service_partner[pocName]'] = 'tp';
        $form['dhi_admin_service_partner[pocEmail]'] = 'testpartner@brainvire.com';
        $form['dhi_admin_service_partner[pocPhone]'] = '3211234332';
        $form['dhi_admin_service_partner[status]'] = 1;
        $form['reason'] = "testreason";

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partner updated successfully!")')->count());
//            }
    }

    

     public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/service-partner-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partners")')->count());

        $objServicePartner = $this->em->getRepository('DhiAdminBundle:ServicePartner')->findOneBy(array(), array('id' => 'DESC'));
        
        $this->assertFalse(count($objServicePartner) != 1, 'Unable to find service partner.');

        if ($objServicePartner) {

            $crawler = $client->request('GET', '/admin/delete-service-partner?id=' . $objServicePartner->getId().'&reason=test');

            $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            $response = $client->getResponse();
            $data = json_decode($response->getContent());
            $this->assertFalse($data->type != 'success', $data->message);
        }
    }

    public function testgetLocationAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/service-partner-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partners")')->count());

        $crawler = $client->request('GET', '/admin/delete-service-partner-location?sLocation=1');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::getLocationAction', $client->getRequest()->attributes->get('_controller'));

    }

     public function testcheckPartnerNameAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/service-partner-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("ISP Service Partners")')->count());

        $objServicePartner = $this->em->getRepository('DhiAdminBundle:ServicePartner')->findOneBy(array(), array('id' => 'DESC'));

        $this->assertFalse(count($objServicePartner) != 1, 'Unable to find service partner.');

        if ($objServicePartner) {
            $partnerName = array('dhi_admin_service_partner' => array('name' => 'Partner'));
            $crawler = $client->request('GET', '/admin/service-partner/check', $partnerName);

            $this->assertEquals('Dhi\AdminBundle\Controller\ServicePartnerController::checkPartnerNameAction', $client->getRequest()->attributes->get('_controller'));
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