<?php
namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BusinessPromoCodeControllerTest extends WebTestCase
{
    protected $container;
    protected $session;
    protected $em;
    protected $conn;
    protected $testHttpHost;
    protected $frontUserName;
    protected $frontPassword;
    
    /**
    * {@inheritDoc}
    */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_admin_password');
        $this->conn = static::$kernel->getContainer()->get('database_connection');
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

    public function testcodeListAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

         $sql = 'select id as lastinsertId from business_promo_code_batch  ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/business-promo-code/list/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessPromoCodeController::codeListAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Business Promo Code")')->count());
    }

    public function testcodeListJsonAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

         $crawler = $client->request('GET', '/admin/business-batch-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Business Promo Code")')->count());


         $sql = 'select id as lastinsertId from business_promo_code_batch  ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/business-promo-code/list-json?batchId='.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessPromoCodeController::codeListJsonAction', $client->getRequest()->attributes->get('_controller'));


         $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/business-promo-code/list-json?batchId='.$lastInsertedId['lastinsertId'].'&sSearch_0=345');

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessPromoCodeController::codeListJsonAction', $client->getRequest()->attributes->get('_controller'));

                 $getresult = $crawler->filter('html:contains("345")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testeditAction(){
        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

         $crawler = $client->request('GET', '/admin/business-batch-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Business Promo Code")')->count());

        $sql = 'select id as lastinsertId from business_promo_code_batch  ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $sql = 'select id as codeId from  business_promo_codes  where batch_id='.$lastInsertedId['lastinsertId'].' GROUP BY batch_id ';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $codeId = $query->fetch();

        $crawler = $client->request('GET', '/admin/business-promo-code/edit/'.$lastInsertedId['lastinsertId'].'/'.$codeId['codeId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessPromoCodeController::editCodeAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Edit Business Promo Code")')->count());

        $form = $crawler->selectButton('update')->form();

        $form['dhi_admin_business_promo_code[expirydate]'] = '10-26-2016';
        $form['dhi_admin_business_promo_code[status]'] = 'Active';
        $form['dhi_admin_business_promo_code_batch[reason]'] = 'testreason';
        $form['dhi_admin_business_promo_code[note]'] = 'test note';

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Business promo code has been updated successfully.")')->count());
    }
    
    /**
    * {@inheritDoc}
    */
    protected function tearDown(){
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}
