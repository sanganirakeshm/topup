<?php
namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TikilivePromoCodeControllerTest extends WebTestCase
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
        $this->path     = static::$kernel->getContainer()->getParameter('kernel.root_dir').'/../app/Resources/';
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
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Tikilive Promo Code")')->count());
    }
    
    public function testlistJsonAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));
        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
       
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_1=8LNtiop05042017');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("BatchName")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_2=jaLAKL345147');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("PromoCode")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_3=Basic');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("IPTVPlan Name")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(0, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_4=Yes');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Is Displayed?")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(0, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_5=Free Trial');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Internet Plan Name")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_7=2017-05-01~2017-05-31');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("date range")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_8=testuser');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Redeemed By")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_9=Enabled');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Status")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
        
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list-json?sSearch_11=admin');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::listJsonAction', $client->getRequest()->attributes->get('_controller'));
        $getresult = $crawler->filter('html:contains("Imported By")')->count();
        if ($getresult == 0) {
            $getresult = $crawler->filter('html:contains("No Record Found!")')->count();
        }
        $this->assertEquals(1, $getresult);
    }
    
    public function testimportAction() {

        $client = static::createClient(
            array(), 
            array(
                'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
            )
        );

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('POST', '/admin/tikilive-promo-code/import');

        $form = $crawler->selectButton('Import')->form();

        $csvfile  = new UploadedFile($this->path . 'Tikilive-Promo-Codes-Sample.csv', 'file/csv', 123);
        
        $form['dhi_admin_tikilive_promo_code[batchName]']  = 'ec04';
        $form['dhi_admin_tikilive_promo_code[csvFile]']    = $csvfile;
        $form['txtdateprefix']                             = '1DD';
        $form['txtRandomChar']                             = '05312017';
        
        $client->submit($form);
        $crawler = $client->getCrawler();

        $this->assertTrue($client->getResponse()->isRedirect());

        $client->followRedirect(true);
        $crawler = $client->getCrawler();

        $this->assertEquals(0, $crawler->filter('html:contains("Promocode imported successfully.")')->count());
    }
    
    public function testChangeStatusAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        
        $sql = 'select * from tikilive_promo_code ORDER BY id DESC';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $promoCodeData = $query->fetch();
        if(count($promoCodeData) > 0)
        {
            $promoCodeId = $promoCodeData['id'];
            $status = $promoCodeData['status'];
            $statusText = 'Enabled';
            $changeStatus = 'Disabled';
            if($status == 0)
            {
                $statusText = 'Disabled';
                $changeStatus = 'Enabled';
            }
            $crawler = $client->request('POST', '/admin/tikilive-promo-code/change-status/'.$promoCodeId.'/'.$statusText);
            $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::changeStatusAction', $client->getRequest()->attributes->get('_controller'));
            
            $crawler = $client->request('GET', '/admin/tikilive-promo-code/list');
            $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(1, $crawler->filter('html:contains("Tikilive Promo Code ' . $changeStatus . ' successfully.")')->count());
        }
        else
        {
            $this->assertFalse(true, 'No tikilive promo code found');
        }
    }
    
    public function testChangeStatusallAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin($this->frontUserName, $this->frontPassword, $client);
        
        $promoCodeObjs = $this->em->getRepository('DhiAdminBundle:TikilivePromoCode')->findBy(array('isRedeemed' => 'No'),array('id'=>'DESC'));
        
        if(count($promoCodeObjs) > 0)
        {
            $promoCodeIds = array();
            if(count($promoCodeObjs) > 2)
            {
                for($i=0; $i<2; $i++)
                {
                    $promoCodeIds[] = $promoCodeObjs[$i]->getId();
                }
            }
            else
            {
                foreach($promoCodeObjs as $promoCodeObj)
                {
                    $promoCodeIds[] = $promoCodeObj->getId();
                }
            }
            $statusType = 'disable';
            $crawler = $client->request('POST', '/admin/tikilive-promo-code/change-statusall', array('promocodeids' => $promoCodeIds, 'statustype' => $statusType));
            $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::changeStatusallAction', $client->getRequest()->attributes->get('_controller'));
            
            if($statusType=='disable'){
                $changeStatus = 'Disabled';
            }
            else
            {
                $changeStatus = 'Enabled';
            }
            
            $crawler = $client->request('GET', '/admin/tikilive-promo-code/list');
            $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertEquals(0, $crawler->filter('html:contains("Tikilive Promo Codes ' . $changeStatus . ' successfully.")')->count());
        }
        else
        {
            $this->assertFalse(true, 'No tikilive promo code found');
        }
    }

      
     public function testexportCsvAction() {

        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => $this->testHttpHost
        ));

        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/tikilive-promo-code/list');
        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("Tikilive promocode")')->count());

        $form = $crawler->selectLink('Export CSV')->first()->link();

        $crawler = $client->click($form);

        $this->assertEquals('Dhi\AdminBundle\Controller\TikilivePromoCodeController::exportCsvAction', $client->getRequest()->attributes->get('_controller'));
    }
    
    protected function tearDown(){
        parent::tearDown();
        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}
