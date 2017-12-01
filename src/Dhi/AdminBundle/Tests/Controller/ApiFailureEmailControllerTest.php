<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiFailureEmailControllerTest extends WebTestCase {

    protected $testHttpHost;
    protected $adminUserName;
    protected $adminPassword;
    protected $apiFailureEmail;
    protected $apiFailureStatus;
    protected $apiFailureUpdateEmail;
    protected $apiFailureUpdateStatus;
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
        $this->adminUserName = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->adminPassword = static::$kernel->getContainer()->getParameter('test_admin_password');

        $this->apiFailureEmail = static::$kernel->getContainer()->getParameter('test_api_failure_email');
        $this->apiFailureStatus = static::$kernel->getContainer()->getParameter('test_api_failure_status');

        $this->apiFailureUpdateEmail = static::$kernel->getContainer()->getParameter('test_api_failure_update_email');
        $this->apiFailureUpdateStatus = static::$kernel->getContainer()->getParameter('test_api_failure_update_status');
        

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
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

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/api-failure-email-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\ApiFailureEmailController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("API Failure Email")')->count());
    }

    public function testapiFailureEmailJsonAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $crawler = $client->request('GET', '/admin/api-failure-email-list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\ApiFailureEmailController::apiFailureEmailJsonAction', $client->getRequest()->attributes->get('_controller'));


        $crawler = $client->request('GET', '/admin/api-failure-email-list-json?sSearch_0=test@brainvire.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\ApiFailureEmailController::apiFailureEmailJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("test@brainvire.com")')->count();
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

        $crawler = $client->request('GET', '/admin/add-failure-email');
        $this->assertEquals('Dhi\AdminBundle\Controller\ApiFailureEmailController::newAction', $client->getRequest()->attributes->get('_controller'));

        $form = $crawler->selectButton('add')->form();

        $form['dhi_api_failure_email_add[email]'] = $this->apiFailureEmail;
        $form['dhi_api_failure_email_add[status]'] = $this->apiFailureStatus;

        $client->submit($form);

        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Email added successfully!")')->count());
    }

    public function testeditAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);

        $objApiFailure = $this->em->getRepository('DhiAdminBundle:ApiFailureEmail')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objApiFailure) != 1, 'API failure email list does not exist.');

        if ($objApiFailure) {

            $crawler = $client->request('GET', '/admin/edit-api-failure-email/' . $objApiFailure->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\ApiFailureEmailController::editAction', $client->getRequest()->attributes->get('_controller'));

            $form = $crawler->selectButton('Update')->form();

            $form['dhi_api_failure_email_add[email]'] = $this->apiFailureUpdateEmail;
            $form['dhi_api_failure_email_add[status]'] = $this->apiFailureUpdateStatus;

            $client->submit($form);

            $client->followRedirect(true);
            $crawler = $client->getCrawler();
            $this->assertEquals(1, $crawler->filter('html:contains("Api failure email updated successfully!")')->count());
        }
    }

    public function testdeleteAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));

        $this->doLogin($this->adminUserName, $this->adminPassword, $client);
        
        
        $objApiFailure = $this->em->getRepository('DhiAdminBundle:ApiFailureEmail')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objApiFailure) != 1, 'API failure email list does not exist.');

        if ($objApiFailure) {

            $crawler = $client->request('POST', '/admin/delete-api-failure-email?id=' . $objApiFailure->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\ApiFailureEmailController::deleteAction', $client->getRequest()->attributes->get('_controller'));
            $responce = $client->getResponse();
            $data = json_decode($responce->getContent());

            $this->assertEquals($data->message, "Email deleted successfully!");
        }
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
