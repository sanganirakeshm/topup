<?php

namespace Dhi\TvodBundle\Controller\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InAppPromoCodeControllerTest extends WebTestCase {
    
    protected $testHttpHost;
   
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->testHttpHost = static::$kernel->getContainer()->getParameter('test_http_host');
       
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }
    
    public function testapplyPromoCodeAction() {
         $client = static::createClient(
            array(), array(
                'HTTP_HOST' => $this->testHttpHost,
        ));
         
         $session = $client->getContainer()->get('session');
         $session->set('sessionId', 'aYuW08A8R3OxNdyrLJguLTPX9');
         $session->save();
         
         $requestParams  = 'amount=10&code=wv1tp';
         $crawler = $client->request('GET', '/tvod/apply-promo-code/11?'.$requestParams);
         
         $this->assertEquals('Dhi\TvodBundle\Controller\InAppPromoCodeController::applyPromoCodeAction', $client->getRequest()->attributes->get('_controller'));
        
    }
    
    
    public function testremovePromoCodeAction() {
        $client = static::createClient(
            array(), array(
                'HTTP_HOST' => $this->testHttpHost,
        ));
         
         $session = $client->getContainer()->get('session');
         $session->set('sessionId', 'aYuW08A8R3OxNdyrLJguLTPX9');
         $session->save();
         
         $crawler = $client->request('GET', '/tvod/remove-promo-code/11');
         
         $this->assertEquals('Dhi\TvodBundle\Controller\InAppPromoCodeController::removePromoCodeAction', $client->getRequest()->attributes->get('_controller'));
        
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
