<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WhiteLabelControllerTest extends WebTestCase {

    /**
     * {@inheritDoc}
     */
    protected function setUp() {
       static::$kernel = static::createKernel();
       static::$kernel->boot();
       $this->em       = static::$kernel->getContainer()->get('doctrine')->getManager(); 
       $this->conn     = static::$kernel->getContainer()->get('database_connection');
       $this->rand     = rand(0,1000);
       $this->email    = static::$kernel->getContainer()->getParameter('test_email');
       $this->path     = static::$kernel->getContainer()->getParameter('kernel.root_dir').'/../web/bundles/dhiadmin/images/white-label/';
    }

    public function testIndexAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $crawler = $client->request('POST', '/admin/whitelabel');
        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Manage Site")')->count());
    }

    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/whitelabel/list-json');

        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::whitelabelListJsonAction', $client->getRequest()->attributes->get('_controller'));

        $crawler = $client->request('GET', '/admin/whitelabel/list-json?sSearch_0=reliance');
        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::whitelabelListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("reliance")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/whitelabel/list-json?sSearch_1=www.reliancejio.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::whitelabelListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("www.reliancejio.com")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/whitelabel/list-json?sSearch_2=test@ats.com');
        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::whitelabelListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("test@ats.com")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
                
        $crawler = $client->request('GET', '/admin/whitelabel/list-json?sSearch_3=Active');
        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::whitelabelListJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Active")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }

    public function testnewAction() {

        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            )
        );

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/whitelabel/add');

        $form = $crawler->selectButton('Add')->form();
        

        $hl  = new UploadedFile($this->path . 'Header Logo.png', 'image/png', 123);
        $fl  = new UploadedFile($this->path . 'Footer Logo.png', 'Footer Logo.png', 'image/png', 123);
        $f   = new UploadedFile($this->path . 'FavIcon.png', 'FavIcon.png', 'image/png', 123);
        $bb  = new UploadedFile($this->path . 'Branding Banner.png', 'Branding Banner.png', 'image/png', 123);
        $bbi = new UploadedFile($this->path . 'Branding Banner Inner Page.png', 'Branding Banner Inner Page.png', 'image/png', 123);
        $bi  = new UploadedFile($this->path . 'Background Image.png', 'Background Image.png', 'image/png', 123);
        
        
        $form['dhi_admin_white_label[companyName]']             = 'Company Name Testcase - ' . $this->rand;
        $form['dhi_admin_white_label[domain]']                  = 'www.domain'. $this->rand .'.com';
        $form['dhi_admin_white_label[supportpage]']             = 'https://domain.com/support';
        $form['dhi_admin_white_label[fromEmail]']               = $this->email;
        $form['dhi_admin_white_label[supportEmail]']            = $this->email;
        $form['dhi_admin_white_label[status]']                  = "0";
        $form['dhi_admin_white_label[headerLogo]']              = $hl;
        $form['dhi_admin_white_label[footerLogo]']              = $fl;
        $form['dhi_admin_white_label[brandingBanner]']          = $bb;
        $form['dhi_admin_white_label[backgroundimage]']         = $bi;
        $form['dhi_admin_white_label[brandingBannerInnerPage]'] = $bbi;
        $form['dhi_admin_white_label[favicon]']                 = $f;
        $form['validateImg']                                    = 0;

        $client->submit($form);
        $crawler = $client->getCrawler();

        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Site added successfully.")')->count());
    }

    public function testeditAction() {

        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            )
        );

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $sql = 'select id as lastinsertId from white_label WHERE is_deleted = 0 ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $lastInsertedId = $query->fetch();
        
        $crawler = $client->request('POST', '/admin/whitelabel/edit/'.$lastInsertedId['lastinsertId']);
        $form = $crawler->selectButton('Update')->form();

        $hl  = new UploadedFile($this->path . 'Header Logo.png', 'image/png', 123);
        $fl  = new UploadedFile($this->path . 'Footer Logo.png', 'Footer Logo.png', 'image/png', 123);
        $f   = new UploadedFile($this->path . 'FavIcon.png', 'FavIcon.png', 'image/png', 123);
        $bb  = new UploadedFile($this->path . 'Branding Banner.png', 'Branding Banner.png', 'image/png', 123);
        $bbi = new UploadedFile($this->path . 'Branding Banner Inner Page.png', 'Branding Banner Inner Page.png', 'image/png', 123);
        $bi  = new UploadedFile($this->path . 'Background Image.png', 'Background Image.png', 'image/png', 123);
        
        
        $form['dhi_admin_white_label[companyName]']             = 'New Company Name Testcase - ' . $this->rand;
        $form['dhi_admin_white_label[domain]']                  = 'www.domain'. $this->rand .'.com';
        $form['dhi_admin_white_label[supportpage]']             = 'https://domain.com/support';
        $form['dhi_admin_white_label[fromEmail]']               = $this->email;
        $form['dhi_admin_white_label[supportEmail]']            = $this->email;
        $form['dhi_admin_white_label[status]']                  = "1";
        $form['dhi_admin_white_label[headerLogo]']              = $hl;
        $form['dhi_admin_white_label[footerLogo]']              = $fl;
        $form['dhi_admin_white_label[brandingBanner]']          = $bb;
        $form['dhi_admin_white_label[backgroundimage]']         = $bi;
        $form['dhi_admin_white_label[brandingBannerInnerPage]'] = $bbi;
        $form['dhi_admin_white_label[favicon]']                 = $f;
        $form['validateImg']                                    = 0;

        $client->submit($form);
        $crawler = $client->getCrawler();
        
        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("Site updated successfully.")')->count());
    }

    public function testdisableAction() {
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
 
        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());
        
        $sql = 'select id as lastinsertId from white_label WHERE is_deleted = 0 ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();
        $crawler = $client->request('GET', '/admin/disable-white-label/'.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::disableAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $this->assertEquals(1, $crawler->filter('html:contains("successfully")')->count());
    }

    public function testdeleteAction() {
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       
        $crawler = $client->request('GET', '/admin/dashboard');

        $this->assertEquals('Dhi\AdminBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to dashboard!")')->count());
        
        $sql = 'select id as lastinsertId from white_label ORDER BY id DESC';

        $query = $this->conn->prepare($sql);

        $query->execute();

        $lastInsertedId = $query->fetch();

        $crawler = $client->request('GET', '/admin/whitelabel/delete?id='.$lastInsertedId['lastinsertId']);

        $this->assertEquals('Dhi\AdminBundle\Controller\WhiteLabelController::deleteAction', $client->getRequest()->attributes->get('_controller'));
        $response = $client->getResponse();
        $data = json_decode($response->getContent());
        $this->assertFalse($data->type != 'success', $data->message);
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
