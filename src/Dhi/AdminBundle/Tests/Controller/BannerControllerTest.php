<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Entity\PaypalCheckout;

class BannerControllerTest extends WebTestCase {

    
    protected $container;
    protected $securityContext;
    protected $session;
    protected $em;
    protected $conn;
    protected $testHttpHost;
    protected $frontUserName;
    protected $frontPassword;
    protected $aradial;
    protected $selevisionService;

    /**
    * {@inheritDoc}
    */
    protected function setUp() {
        
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session = static::$kernel->getContainer()->get('session');
        $this->securitycontext = static::$kernel->getContainer()->get('security.context');
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_admin_password');
        $this->aradial = static::$kernel->getContainer()->get('aradial');
        $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');

        $this->conn = static::$kernel->getContainer()
                ->get('database_connection');
    }

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

    public function testindexAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());
        $crawler = $client->request('GET', '/admin/banner');
        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Banners")')->count());
    }

    /*
    public function testbannerListJsonAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/banner');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Banners")')->count());

        $crawler = $client->request('GET', '/admin/banner-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::bannerListJsonAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testNewAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/banner');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Banners")')->count());

        $crawler = $client->request('POST', '/admin/banner-add');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::newAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Add Banner")')->count());

        $form = $crawler->selectButton('add')->form();

        $county_iso_code = static::$kernel->getContainer()->getParameter('test_country_iso_code');
        $objCountry = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array( 'isoCode' => $county_iso_code ));
        
        if($objCountry)
        {
            $country_id = $objCountry->getId();
            $form['dhi_admin_banner[country]'] = $country_id;
            $form['dhi_admin_banner[orderNo]'] = '2';
            $form['dhi_admin_banner[status]'] = '1';
            $form['dhi_admin_banner[bannerImages]'] = '';
            $form['dhi_admin_banner[orderNo]'] = '';

            $client->submit($form);
            $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::newAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("success")')->count());
        }
        else
        {
            $this->assertFalse(true, 'Country not found by iso code.');
        }

    }*/

    public function testcheckOrderNoAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/banner');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Banners")')->count());

        $sql = 'select id as lastinsertId,country_id as countryId from banner where status = 1 ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/check-exist-orderno?orderno=' . $lastInsertedId['lastinsertId'] . '&country=' . $lastInsertedId['countryId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::checkOrderNoAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testgetOrderNoAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/banner');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Banners")')->count());

        $sql = 'select id as lastinsertId,country_id as countryId from banner where status = 1 ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/get-orderno?id=' . $lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::getOrderNoAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testdisableAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/banner');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Banners")')->count());

        $sql = 'select id as lastinsertId,country_id as countryId from banner ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/disable-banner/' . $lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::disableAction', $client->getRequest()->attributes->get('_controller'));
    }

    public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/banner');

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Banners")')->count());

        $sql = 'select id as lastinsertId from banner where status = 1 ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/banner-delete?id=' . $lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BannerController::deleteAction', $client->getRequest()->attributes->get('_controller'));
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
