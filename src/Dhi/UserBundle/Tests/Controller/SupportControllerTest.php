<?php
namespace Dhi\UserBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SupportCategoryControllerTest extends WebTestCase {
    
    /**
    * {@inheritDoc}
    */
     protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
       $this->conn     = static::$kernel->getContainer()->get('database_connection');
    }
    
     /**
     * This action allows to test send support to suppport team
     */
    public function testsupport() {
       
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_front_username'), static::$kernel->getContainer()->getParameter('test_front_password'), $client);
        
        $sql = 'select id as countryId from country';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $countryId = $query->fetch();
        
        if(empty($countryId)){
            $this->assertFalse(true, 'Unable to find country.');
        }
        
        $sql = 'SELECT wl.id as WhiteLabelId FROM white_label wl ORDER BY wl.id DESC ';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $siteIds = $query->fetch();
        
        if(empty($siteIds)){
            $this->assertFalse(true, 'Unable to find site.');
        } 
        
        $sql = 'SELECT sl.id as supportLocationId FROM support_location sl 
            INNER JOIN white_label wl ON wl.id = sl.white_label_id
            INNER JOIN solar_wind_service_location sw ON sw.support_location_id = sl.id
            WHERE sl.is_deleted = 0
            AND wl.id ='.$siteIds['WhiteLabelId'];
        $query = $this->conn->prepare($sql);
        $query->execute();
        $locationIds = $query->fetch();
        
        if(empty($locationIds)){
            $this->assertFalse(true, 'Unable to find location.');
        } 
        
        $sql = 'SELECT sc.id as supportCategoryId FROM support_category sc 
            INNER JOIN white_label wl ON wl.id = sc.white_label_id
            WHERE sc.is_deleted = 0
            AND sc.white_label_id ='.$siteIds['WhiteLabelId'];
        $query = $this->conn->prepare($sql);
        $query->execute();
        $supportCategoryIds = $query->fetch();
        
        if(empty($supportCategoryIds)){
            $this->assertFalse(true, 'Unable to find support category.');
        }
        
        
        $sql = 'SELECT ss.id as supportServiceId FROM support_service ss 
            WHERE ss.is_deleted = 0
            AND ss.is_active = 1';
        $query = $this->conn->prepare($sql);
        $query->execute();
        $supportServiceIds = $query->fetch();
        
        if(empty($supportServiceIds)){
            $this->assertFalse(true, 'Unable to find support service.');
        }
        
        $crawler = $client->request('POST', '/support');
        $client->enableProfiler();
        
        $this->assertEquals('Dhi\UserBundle\Controller\SupportController::supportAction', $client->getRequest()->attributes->get('_controller'));
         
       $form = $crawler->selectButton('Submit')->form();
       
       $form['dhi_user_support[country]'] = $countryId['countryId'];
       $form['dhi_user_support[firstname]'] = static::$kernel->getContainer()->getParameter('test_front_support_fname');
       $form['dhi_user_support[lastname]'] = static::$kernel->getContainer()->getParameter('test_front_support_lname');
       $form['dhi_user_support[email][first]'] = static::$kernel->getContainer()->getParameter('test_front_support_email_first');
       $form['dhi_user_support[email][second]'] = static::$kernel->getContainer()->getParameter('test_front_support_email_second');
       $form['dhi_user_support[number]'] = static::$kernel->getContainer()->getParameter('test_front_support_mobile_number');
       $form['dhi_user_support[category]'] = $supportCategoryIds['supportCategoryId'];
       $form['dhi_user_support[supportService]'] = $supportServiceIds['supportServiceId'];
       $form['dhi_user_support[building]'] = static::$kernel->getContainer()->getParameter('test_front_support_building');
       $form['dhi_user_support[roomNumber]'] = static::$kernel->getContainer()->getParameter('test_front_support_room_number');
       $form['dhi_user_support[location]'] = $locationIds['supportLocationId'];
       $form['dhi_user_support[time]'] = static::$kernel->getContainer()->getParameter('test_front_support_best_time');
       $form['dhi_user_support[message]'] = static::$kernel->getContainer()->getParameter('test_front_support_message');
        
        $crawler = $client->submit($form);
         
        $crawler = $client->getCrawler();
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        $this->assertEquals(1, $crawler->filter('html:contains("Your support request has been sent successfully")')->count());
    }

    
    /**
     *  General function for Admin login
     */
     public function doLogin($username, $password, $client) {

        $crawler = $client->request('POST', 'login');

        $form = $crawler->selectButton('Login')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
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
