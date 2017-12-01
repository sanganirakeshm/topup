<?php
namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SurveyMonkeyMailControllerTest extends WebTestCase {
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
    }
    
     public function testindex(){
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);

        $crawler = $client->request('GET', '/admin/survey-monkey-mail/survey-monkey-mail-list');

        $this->assertEquals('Dhi\AdminBundle\Controller\SurveyMonkeyMailController::indexAction', $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(1, $crawler->filter('html:contains("Survey Monkey Mail")')->count());
    }
    
    
    public function testsurveyMonkeyMailListJson(){
       $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        )); 
       
       $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
       $crawler = $client->request('GET', '/admin/survey-monkey-mail/survey-monkey-mail-list-json');
       
       $this->assertEquals('Dhi\AdminBundle\Controller\SurveyMonkeyMailController::surveyMonkeyMailListJsonAction',
                $client->getRequest()->attributes->get('_controller'));
       
        $this->assertEquals(0, $crawler->filter('html:contains("Survey monkey email")')->count());
        // for country wrong  parameter
        $crawler = $client->request('GET', '/admin/survey-monkey-mail/survey-monkey-mail-list-json?sSearch_1=test');

        $this->assertEquals('Dhi\AdminBundle\Controller\SurveyMonkeyMailController::surveyMonkeyMailListJsonAction',
                $client->getRequest()->attributes->get('_controller'));

        $this->assertEquals(0, $crawler->filter('html:contains("subjet of survey monkey email")')->count());
        
    }
    
    
    public  function testeditsurveymonkeymail(){
        $em = $this->em;
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
       
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $objSurveymonkey = $this->em->getRepository('DhiUserBundle:SurveyMonkeyMail')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objSurveymonkey) == 0, 'SurveyMonkeyMail record not found');
        
        $crawler = $client->request('POST', '/admin/edit-survey-monkey-mail/edit-survey-monkey-mail-campaign/'.$objSurveymonkey->getId());
        
        $this->assertEquals('Dhi\AdminBundle\Controller\SurveyMonkeyMailController::editAction', $client->getRequest()->attributes->get('_controller'));
        
        $form = $crawler->selectButton('Save')->form();
        $form['dhi_admin_survey_monkey_mail[subject]'] = 'Phpunit test';
        $form['dhi_admin_survey_monkey_mail[message]'] = 'Phpunit Message';
        $form['dhi_admin_survey_monkey_mail[emailStatus]'] = 'Sent';
        
        $crawler = $client->submit($form);
        $client->followRedirect(true);
        $crawler = $client->getCrawler();
        
        $this->assertTrue($client->getResponse()->isSuccessful(), 'Survey Monkey Mail updated successfully.');
    }
    public function doLogin($username, $password, $client) {
            
        $crawler = $client->request('POST', 'admin/login');
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => static::$kernel->getContainer()->getParameter('test_admin_username'),
            '_password' => static::$kernel->getContainer()->getParameter('test_admin_password'),
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

