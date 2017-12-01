<?php

namespace Dhi\IsppartnerBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DashboardControllerTest extends WebTestCase
{
 
    protected $ispPartnerUsername;
    protected $ispPartnerPassword;
    protected $ispPartnerPromocode;
    protected $ispPartnerPromocodeStatus;
    protected $ispPartnerPromocodeReason;
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {
        
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        
        $this->ispPartnerUsername = static::$kernel->getContainer()->getParameter('test_isp_partner_username');
        $this->ispPartnerPassword = static::$kernel->getContainer()->getParameter('test_isp_partner_password');
        $this->ispPartnerPromocode = static::$kernel->getContainer()->getParameter('test_isp_partner_promo_code');
        $this->ispPartnerPromocodeStatus = static::$kernel->getContainer()->getParameter('test_isp_partner_promo_code_status');
        $this->ispPartnerPromocodeReason = static::$kernel->getContainer()->getParameter('test_isp_partner_promo_code_reason');
    }
    
    public function doLogin($username, $password, $client) {
        
        $crawler = $client->request('POST', 'isp-partner/login');
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));
        
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }
    
    public function testIndexAction() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin($this->ispPartnerUsername, $this->ispPartnerPassword, $client);
        $crawler = $client->request('POST', '/isp-partner/dashboard');
        $this->assertEquals('Dhi\IsppartnerBundle\Controller\DashboardController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Welcome to ISP Partner Dashboard!")')->count());
        
    }
    
    public function testsearchAction(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin($this->ispPartnerUsername, $this->ispPartnerPassword, $client);
        $crawler = $client->request('POST', '/isp-partner/promo-code/search/?promocode='.$this->ispPartnerPromocode);
        $this->assertEquals('Dhi\IsppartnerBundle\Controller\DashboardController::searchAction', $client->getRequest()->attributes->get('_controller'));
        
        $data = $client->getResponse();
        $response = json_decode($data->getContent());
        $flag = $response->flag;
        
        if($flag){
            $this->assertEquals($this->ispPartnerPromocode, $response->name);
        }else{ 
            $this->assertFalse(TRUE, 'Promo code not found.');
        }
    }
    
    public function testeditCodeAction(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin($this->ispPartnerUsername, $this->ispPartnerPassword, $client);
        
        $objPartnerPromoCode = $this->em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array( 'code' => $this->ispPartnerPromocode ));
        
        if(!$objPartnerPromoCode){
            $this->assertFalse(TRUE, 'Promo code not found.');
        }
        
        $batchId = $objPartnerPromoCode->getBatchId()->getId();
        $codeId = $objPartnerPromoCode->getId();
        
        if ($objPartnerPromoCode->getIsRedeemed() == 'Yes') {
            $this->assertFalse(TRUE, 'Access denied! Promo code is Redeemded.');
        }else{
        
            $crawler = $client->request('POST', '/isp-partner/promo-code/edit/'.$batchId.'/'.$codeId);
            $this->assertEquals('Dhi\IsppartnerBundle\Controller\DashboardController::editCodeAction', $client->getRequest()->attributes->get('_controller'));

            $form = $crawler->selectButton('Update')->form();

            $form['dhi_admin_partner_promo_code[status]'] = $this->ispPartnerPromocodeStatus;
            $form['dhi_admin_partner_promo_code_batch[reason]'] = $this->ispPartnerPromocodeReason;

            $client->submit($form);

            $client->followRedirect(true);
            $crawler = $client->getCrawler();

            $this->assertEquals(1, $crawler->filter('html:contains("Promo code '.$this->ispPartnerPromocode.' updated successfully.")')->count());
        }
    }
    
    public function testdeactivateAction(){
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin($this->ispPartnerUsername, $this->ispPartnerPassword, $client);
        
        $objPartnerPromoCode = $this->em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array( 'code' => $this->ispPartnerPromocode ));
        
        if(!$objPartnerPromoCode){
            $this->assertFalse(TRUE, 'Promo code not found.');
        }
        
        $promocodeid = $objPartnerPromoCode->getId();
        $isRedeemedValue = $objPartnerPromoCode->getIsRedeemed();
        $candelete = 'yes';
        $discodeapl = '';
        $procodeapl = '';
        
        $objServicePurchase = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy(array('discountedPartnerPromocode' => $promocodeid));
        
        if (count($objServicePurchase) > 0) {
            $discodeapl = $objServicePurchase->getDiscountCodeApplied();
            $procodeapl = $objServicePurchase->getPromoCodeApplied();
        }
        
        $reqParams = 'promocodeid='.$promocodeid.'&candelete='.$candelete.'&isRedeemedValue='.$isRedeemedValue.'&discodeapl='.$discodeapl.'&procodeapl='.$procodeapl;
        
        $crawler = $client->request('POST', '/isp-partner/promo-code/deactivate?'.$reqParams);
        $this->assertEquals('Dhi\IsppartnerBundle\Controller\DashboardController::deactivateAction', $client->getRequest()->attributes->get('_controller'));
        
        $data = $client->getResponse();
        $response = json_decode($data->getContent());
        
        $this->assertEquals($response->type, 'success');
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
