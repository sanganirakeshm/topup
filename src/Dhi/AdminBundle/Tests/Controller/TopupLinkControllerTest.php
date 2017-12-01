<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TopupLinkControllerTest extends WebTestCase
{
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        
    }
    
    public function testIndexAction()
    {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/topup-link-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\TopupLinkController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Topup Link")')->count());
    }
    
    public function testeditTopupLinkAction() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $objTopupLink = $this->em->getRepository('DhiAdminBundle:TopupLink')->findOneBy(array(), array('id' => 'DESC'));
        
        if($objTopupLink)
        {
            
            $serviceLocationArr = array();
            $serviceLocationObjs = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findAll();
            if($serviceLocationObjs)
            {
                for($i=0; $i < count($serviceLocationObjs); $i++)
                {
                    if($i < 5)
                    {
                        $serviceLocationArr[] = $serviceLocationObjs[$i]->getId();
                    }
                    else
                    {
                        $i = count($serviceLocationObjs);
                    }
                }
            }
            
            if(!$serviceLocationArr)
            {
                $this->assertFalse(true, "No service location found. Please add it.");
            }
            else
            {
                $crawler = $client->request('GET', '/admin/edit-topup-link/'.$objTopupLink->getId());
                $this->assertEquals('Dhi\AdminBundle\Controller\TopupLinkController::editTopupLinkAction', $client->getRequest()->attributes->get('_controller'));

                $form = $crawler->selectButton('Update')->form();
                $form['dhi_topup_link[serviceLocations]'] = $serviceLocationArr;
                $form['dhi_topup_link[linkName]'] = "Testlink";
                $form['dhi_topup_link[url]'] = "http://www.example-update.com";
                $form['dhi_topup_link[status]'] = 0;
                $client->submit($form);
                $this->assertTrue($client->getResponse()->isRedirect()); // check if redirecting properly
                $crawler = $client->followRedirect();
                $this->assertEquals(1, $crawler->filter('html:contains("Topup link updated successfully!")')->count());
            }
            
        }
        
    }
    
    public function testSearchServiceLocationAction() {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('GET', '/admin/topup-link-search-service_location', array(
            'tag' => 'k'
        ));
        $this->assertEquals('Dhi\AdminBundle\Controller\TopupLinkController::searchServiceLocationAction', $client->getRequest()->attributes->get('_controller'));
        $response = $client->getResponse();
        $dataArr = json_decode($response->getContent());
    }
    
    public function doLogin($username, $password, $client) {
        $crawler = $client->request('GET', 'admin/login');
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
