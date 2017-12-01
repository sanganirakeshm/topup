<?php
namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BusinessBatchControllerTest extends WebTestCase
{
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

    public function testindexAction(){
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
    }

    public function testlistJsonAction(){
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

        $crawler = $client->request('GET', '/admin/business-batch-list-json?businessName=business12');

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

         $getresult = $crawler->filter('html:contains("business12")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);

        $crawler = $client->request('GET', '/admin/business-batch-list-json?sSearch_0=123');

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::listJsonAction', $client->getRequest()->attributes->get('_controller'));

         $getresult = $crawler->filter('html:contains("123")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testnewAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/add-new-business-batch');

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::newAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Add Business Promo Code Batch")')->count());

        $form = $crawler->selectButton('add')->form();
         
        $objBusiness = $this->em->getRepository('DhiAdminBundle:Business')->findOneBy(array('status' => 1, 'isDeleted' => 0), array('id' => 'DESC'));
        $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('name' => 'BAF'), array('id' => 'DESC'));
        $objService = $this->em->getRepository('DhiUserBundle:service')->findOneBy(array('name' => 'ISP'), array('id' => 'DESC'));
        
        
        $this->assertFalse(count($objBusiness) == 0 || count($objServiceLocation) == 0 || count($objService) == 0, 'Business, Service or Service Location Not Found.');
        
        $serviceLocationId = $objServiceLocation->getId();
        $objPackage = $this->em->getRepository('DhiAdminBundle:Package')->findOneBy(array('serviceLocation' => $serviceLocationId, 'status' => 1, 'isExpired' => 0), array('id' => 'DESC'));        
        $this->assertFalse(count($objPackage) == 0, 'Package not found');
        
        $form['dhi_admin_business_promo_code_batch[business]'] = $objBusiness->getId();
        $form['dhi_admin_business_promo_code[serviceLocations]'] = $serviceLocationId;
        $form['dhi_admin_business_promo_code[service]'] = $objService->getId();;
        $form['dhi_admin_business_promo_code_batch[batchName]'] = 'Test Batch';
        $form['dhi_admin_business_promo_code[packageId]'] = $objPackage->getPackageId();
        $form['dhi_admin_business_promo_code_batch[noOfCodes]'] = '1';
        $form['dhi_admin_business_promo_code[businessValue]'] = '1';
        $form['dhi_admin_business_promo_code[customerValue]'] = '1';
        $form['dhi_admin_business_promo_code[expirydate]'] = '10-26-2016';
        $form['dhi_admin_business_promo_code[status]'] = 'Active';
        $form['dhi_admin_business_promo_code_batch[reason]'] = 'testreason';
        $form['dhi_admin_business_promo_code_batch[note]'] = 'test note';

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Business promo code batch added successfully.")')->count());
    }

    public function testgetPromoPackageAction(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());
        $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('name' => 'BAF'), array('id' => 'DESC'));
        $objService = $this->em->getRepository('DhiUserBundle:service')->findOneBy(array('name' => 'ISP'), array('id' => 'DESC'));
        $this->assertFalse(count($objServiceLocation) ==  0 || count($objService) == 0, 'Service or Service location not found');
        $crawler = $client->request('GET', '/admin/get-business-package?locationId='.$objServiceLocation->getId().'&serviceId='.$objService->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::getPromoPackageAction', $client->getRequest()->attributes->get('_controller'));

        $gettypevar = gettype($crawler);

        $this->assertEquals('object', $gettypevar);
    }

   public function testgetdeleteBatchAction(){

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

        $crawler = $client->request('GET', '/admin/delete-business-batch?id='.$lastInsertedId['lastinsertId'].'&reason=webtestcasereason');

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::deleteBatchAction', $client->getRequest()->attributes->get('_controller'));
        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->type != 'success', $data->message);

    }

    public function testgetexportpdfAction(){

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

        $crawler = $client->request('GET', '/admin/business-promocode-export-pdf/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::exportpdfAction', $client->getRequest()->attributes->get('_controller'));


    }

    public function testgetexportcsvAction(){

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

        $crawler = $client->request('GET', '/admin/business-promocode-export-csv/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::exportcsvAction', $client->getRequest()->attributes->get('_controller'));

    }

    public function testgetexportExcelAction(){

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

        $crawler = $client->request('GET', '/admin/business-promocode-export-excel/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::exportexcelAction', $client->getRequest()->attributes->get('_controller'));

    }

     public function testgetPromoServiceAction(){

        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());
        
        $objBusiness = $this->em->getRepository('DhiAdminBundle:Business')->findOneBy(array('status' => 1, 'isDeleted' => 0), array('id' => 'DESC'));
        $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('name' => 'BAF'), array('id' => 'DESC'));
        $this->assertFalse(count($objBusiness) == 0 || count($objServiceLocation) == 0, 'Business or Service Location Not Found.');
        
        $crawler = $client->request('GET', '/admin/get-business-promo-code-service?locationId='.$objServiceLocation->getId().'&businessId='.$objBusiness->getId());

        $this->assertEquals('Dhi\AdminBundle\Controller\BusinessBatchController::getPromoServiceAction', $client->getRequest()->attributes->get('_controller'));

        $gettypevar = gettype($crawler);

        $this->assertEquals('object', $gettypevar);
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
