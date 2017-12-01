<?php
namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EmployeePromoCodeControllerTest extends WebTestCase
{
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
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session           = static::$kernel->getContainer()->get('session');
        $this->securitycontext   = static::$kernel->getContainer()->get('security.context');
        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->frontPassword = static::$kernel->getContainer()->getParameter('test_admin_password');
        $this->aradial = static::$kernel->getContainer()->get('aradial');
        $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');
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

        $crawler = $client->request('GET', '/admin/employee-promo-code-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code")')->count());
    }

    public function testemployeePromoCodeListJsonAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

         $crawler = $client->request('GET', '/admin/employee-promo-code-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code")')->count());

        $crawler = $client->request('GET', '/admin/employee-promo-code-list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::employeePromoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/employee-promo-code-list-json?sSearch_0=EMP1');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::employeePromoCodeListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $getresult = $crawler->filter('html:contains("EMP1")')->count();

        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testemployeePromoCodeReportAction(){
        
        $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));
        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());
        $crawler = $client->request('GET', '/admin/employee-promo-code-report-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::employeePromoCodeReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code Report")')->count());

    }

    /*
    public function testemployeePromoCodeReportJsonAction(){
        
        $client = static::createClient(
            array(), array(
                'HTTP_HOST' => $this->testHttpHost,
        ));
        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        $crawler = $client->request('GET', '/admin/dashboard');
        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());
        $crawler = $client->request('GET', '/admin/employee-promo-code-report-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::employeePromoCodeReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code Report")')->count());
        $crawler = $client->request('GET', '/admin/employee-promo-code-report-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::employeePromoCodeReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code Report")')->count());

    } */

    
    public function testviewCustomerAction(){
         $client = static::createClient(
                        array(), array(
                            'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());


          $sql = 'select id as lastinsertId from employee_promo_code ORDER BY id DESC';
//
        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

         $crawler = $client->request('GET', '/admin/employee-promo-code-customer/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::viewcustomerAction', $client->getRequest()->attributes->get('_controller'));



    }

    public function testemployeePromoCodeCustomerListJsonAction(){
        $client = static::createClient(
            array(), array(
                'HTTP_HOST' => $this->testHttpHost,
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $sql = 'select id as lastinsertId from employee_promo_code ORDER BY id DESC';
//
        $query = $this->conn->prepare($sql);

        $query->execute();////

        $lastInsertedId = $query->fetch();


        $crawler = $client->request('GET', '/admin/employee-promo-code-customer/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::viewcustomerAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code Customer")')->count());

        $crawler = $client->request('GET', '/admin/employee-promo-code-customer-list-json/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::employeePromoCodeCustomerListJsonAction', $client->getRequest()->attributes->get('_controller'));

//        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code Customer")')->count());

    }

    public function testnewAction(){
        $client = static::createClient(
           array(), array( 'HTTP_HOST' => $this->testHttpHost, ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $crawler = $client->request('GET', '/admin/add-new-employee-promo-code');

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::newAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Add Employee Promo Code")')->count());

        $sql = 'select username as lastinsertId from dhi_user where is_deleted = 0 and is_employee = 1 group by is_employee';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

         $form = $crawler->selectButton('add')->form();

        $form['dhi_admin_employee_promo_code[employeeName]'] = $lastInsertedId['lastinsertId'];
        $form['dhi_admin_employee_promo_code[note]'] = 'testnot';
        $form['dhi_admin_employee_promo_code[amountType]'] = 'percentage';
        $form['dhi_admin_employee_promo_code[amount]'] = '10.22';
        $form['dhi_admin_employee_promo_code[noOfCodes]'] = '2';
        $form['dhi_admin_employee_promo_code[status]'] = '1';

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code added successfully.")')->count());
    }


    public function testeditAction(){
        $client = static::createClient(
           array(), array( 'HTTP_HOST' => $this->testHttpHost, ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        

        $sql = 'select id as lastinsertId from employee_promo_code order by id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        
        $crawler = $client->request('GET', '/admin/edit-employee-promo-code/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::editAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Edit Employee Promo Code")')->count());
        
         $form = $crawler->selectButton('edit')->form();


        $sql = 'select username as lastinsertId from dhi_user where is_deleted = 0 and is_employee = 1 group by is_employee';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();


        $form['dhi_admin_employee_promo_code[employeeName]'] = $lastInsertedId['lastinsertId'];
        $form['dhi_admin_employee_promo_code[note]'] = 'testnot1';
        $form['dhi_admin_employee_promo_code[amountType]'] = 'percentage';
        $form['dhi_admin_employee_promo_code[amount]'] = '10.22';
        $form['dhi_admin_employee_promo_code[status]'] = '1';

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Employee Promo Code updated successfully.")')->count());
    }

    public function testdisableAction(){

        $client = static::createClient(
           array(), array( 'HTTP_HOST' => $this->testHttpHost, ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);

        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());

        $sql = 'select id as lastinsertId from employee_promo_code order by id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();


        $crawler = $client->request('GET', '/admin/disable-employee-promo-code/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\EmployeePromoCodeController::disableAction', $client->getRequest()->attributes->get('_controller'));
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $getresult = $crawler->filter('html:contains("Promo Code Disabled successfully.")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("Promo Code Enabled successfully.")')->count();
        }
        $this->assertEquals(1, $getresult);

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
