<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PromoCodeSalesReportControllerTest extends WebTestCase
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
        $crawler = $client->request('POST', '/admin/promo-code-sales/list');
        $this->assertEquals('Dhi\AdminBundle\Controller\PromoCodeSalesReportController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Partner PromoCode Sales Report")')->count());
        
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
    protected function tearDown(){
        parent::tearDown();

        $this->em->close();
        $this->em = null; // avoid memory leaks
    }
}
