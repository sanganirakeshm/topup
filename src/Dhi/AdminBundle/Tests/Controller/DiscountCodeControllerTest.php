<?php

namespace Dhi\AdminBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * 
 */
class DiscountCodeControllerTest extends WebTestCase {

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
        $crawler = $client->request('GET', '/admin/login');
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

        $crawler = $client->request('GET', '/admin/discount-code-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Global Promo Code")')->count());
    }

    public function testdiscountCodeListJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/discount-code-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::discountCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));


        $crawler = $client->request('GET', '/admin/discount-code-list-json?sSearch_1=BAF');
        $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::discountCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BAF")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/discount-code-list-json?sSearch_2=grab5');
        $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::discountCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("grab5")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);


        $crawler = $client->request('GET', '/admin/discount-code-list-json?sSearch_6=admin');
        $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::discountCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("admin")')->count();
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

        $discountCode = static::$kernel->getContainer()->getParameter('test_discount_code_new');
        $crawler = $client->request('POST', '/admin/add-new-discount-code');

        $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::newAction', $client->getRequest()->attributes->get('_controller'));

        $objDiscountCode = $this->em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('discountCode' => $discountCode));

        $this->assertFalse(count($objDiscountCode) != 0, 'Global Promo Code already exists');
        if (!$objDiscountCode) {
            $form = $crawler->selectButton('add')->form();

            $form['dhi_admin_discount_code[serviceLocations]'] = 1;
            $form['dhi_admin_discount_code[discountCode]'] = $discountCode;
            $form['dhi_admin_discount_code[amountType]'] = 'percentage';
            $form['dhi_admin_discount_code[amount]'] = 10;
            $form['dhi_admin_discount_code[startdate]'] = '10-24-2016';
            $form['dhi_admin_discount_code[enddate]'] = '10-31-2016';
            $form['dhi_admin_discount_code[status]'] = 1;
            $form['dhi_admin_discount_code[note]'] = 'This is a testing';

            $client->submit($form);

            $client->followRedirect(true);
            $crawler = $client->getCrawler();

            $this->assertEquals(1, $crawler->filter('html:contains("Global Promo Code added successfully.")')->count());
        }
    }

    public function testeditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $discountCode = static::$kernel->getContainer()->getParameter('test_discount_code_new').'Update';
        $objDiscountCode = $this->em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objDiscountCode) == 0, 'DiscountCode not found');

        if ($objDiscountCode) {

            $crawler = $client->request('POST', '/admin/edit-discount-code/' . $objDiscountCode->getId());


            $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::editAction', $client->getRequest()->attributes->get('_controller'));
            $objDiscountCode = $this->em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('discountCode' => $discountCode));

            $this->assertFalse(count($objDiscountCode) != 0, 'Global Promo Code already exists');
            if (!$objDiscountCode) {

                $form = $crawler->selectButton('Update')->form();

                $form['dhi_admin_discount_code[serviceLocations]'] = 1;
                $form['dhi_admin_discount_code[discountCode]'] = $discountCode;
                $form['dhi_admin_discount_code[amountType]'] = 'percentage';
                $form['dhi_admin_discount_code[amount]'] = 10;
                $form['dhi_admin_discount_code[startdate]'] = '10-24-2016';
                $form['dhi_admin_discount_code[enddate]'] = '10-31-2016';
                $form['dhi_admin_discount_code[status]'] = 1;
                $form['dhi_admin_discount_code[note]'] = 'This is a testing for Update';

                $client->submit($form);

                $client->followRedirect(true);
                $crawler = $client->getCrawler();

                $this->assertEquals(1, $crawler->filter('html:contains("Global Promo Code updated successfully.")')->count());
            }
        }
    }

    public function testdisableAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objDiscountCode = $this->em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objDiscountCode) != 1, 'Unable to find global promo code.');

        if ($objDiscountCode) {

            $crawler = $client->request('POST', '/admin/disable-discount-code/' . $objDiscountCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::disableAction', $client->getRequest()->attributes->get('_controller'));

            $crawler = $client->request('GET', '/admin/discount-code-list');
            $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("successfully.")')->count());
        }
    }

    public function testviewCustomerAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objDiscountCode = $this->em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objDiscountCode) != 1, 'Unable to find global promo code.');
        if ($objDiscountCode) {

            $crawler = $client->request('GET', '/admin/discount-code-customer/' . $objDiscountCode->getId());

            $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::viewcustomerAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("Global Promo Code Customer")')->count());
        }
    }

    public function testdiscountCodeCustomerListJsonAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objDiscountCode = $this->em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objDiscountCode) != 1, 'Unable to find global promo code.');

        if ($objDiscountCode) {

            $crawler = $client->request('GET', '/admin/discount-code-customer-list-json/' . $objDiscountCode->getId());

            $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::discountCodeCustomerListJsonAction', $client->getRequest()->attributes->get('_controller'));


            $crawler = $client->request('GET', '/admin/discount-code-customer-list-json/' . $objDiscountCode->getId() . '?sSearch_1=mahesh');
            $this->assertEquals('Dhi\AdminBundle\Controller\DiscountCodeController::discountCodeCustomerListJsonAction', $client->getRequest()->attributes->get('_controller'));
            $getresult = $crawler->filter('html:contains("mahesh")')->count();
            if ($getresult == 0) {
                $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
            }
            $this->assertEquals(1, $getresult);
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
