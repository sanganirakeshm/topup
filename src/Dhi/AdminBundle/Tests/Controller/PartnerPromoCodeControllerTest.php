<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PartnerPromoCodeControllerTest extends WebTestCase
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
    
    public function testIndexAction() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/partner-promo-batch/list');
        $this->assertEquals('Dhi\AdminBundle\Controller\PartnerPromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("ISP Partner Promo Codes")')->count());
        
    }
    
    public function testNewAction() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $test_partner = static::$kernel->getContainer()->getParameter('test_partner');
        $test_service_location = static::$kernel->getContainer()->getParameter('test_service_location');
        $test_batch_prefix = static::$kernel->getContainer()->getParameter('test_batch_prefix');
        $test_batch_rand_char = static::$kernel->getContainer()->getParameter('test_partner_promocode_batch_random_char');
        
        $crawler = $client->request('POST', '/admin/partner_promo-code/add');
        $this->assertEquals('Dhi\AdminBundle\Controller\PartnerPromoCodeController::newAction', $client->getRequest()->attributes->get('_controller'));
        
        $this->assertEquals(4, strlen($test_batch_prefix), 'Batch prefix should be minimum 4 character.');
        $this->assertEquals(3, strlen($test_batch_rand_char), 'Partner promocode randon character must be 3 alphanumaric character.');
        
        // Set parameters
        $objPartner = $this->em->getRepository('DhiAdminBundle:ServicePartner')->findOneBy(array('name' => $test_partner));
        if(!$objPartner)
        {
            $this->assertFalse(true, "Service partner not found");
        }
        $partnerId = $objPartner->getId();
        $serviceName = $objPartner->getService()->getName();
        $objServiceLocation = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('name' => $test_service_location));
        if(!$objServiceLocation)
        {
            $this->assertFalse(true, "Service location not found");
        }
        $serviceLocationId = $objServiceLocation->getId();
        $serviceLocationName = $objServiceLocation->getName();
        
        $packages = $this->em->getRepository('DhiAdminBundle:Package')->getPackageTypeByService($serviceName, $serviceLocationId, true, true);
        
        $packagesId = array();
        if($packages)
        {
            foreach($packages as $package)
            {
                $packagesId[] = $package['packageId'];
            }
        }
        else
        {
            $this->assertFalse(true, 'Packages not found.');
        }
        
        $pkgId = reset($packagesId);
        
        $form = $crawler->selectButton('Add')->form();
        $form['dhi_admin_partner_promo_code_batch[partner]'] = $partnerId;
        $form['dhi_admin_partner_promo_code[serviceLocations]'] = $serviceLocationId;
        $form['hdnServicePrefix'] = $serviceLocationName;
        $form['txtRandomChar'] = $test_batch_rand_char;
        $form['dhi_admin_partner_promo_code_batch[batchName]'] = $test_batch_prefix;
        $form['dhi_admin_partner_promo_code[packageId]'] = $pkgId;
        $form['dhi_admin_partner_promo_code[duration]'] = 10; // Hours
        $form['dhi_admin_partner_promo_code[expirydate]'] = NULL;
        $form['chkNeverExpire'] = true;   
        $form['dhi_admin_partner_promo_code_batch[noOfCodes]'] = 2;
        $form['dhi_admin_partner_promo_code[partnerValue]'] = 8.00;
        $form['dhi_admin_partner_promo_code[customerValue]'] = 10.00;
        $form['dhi_admin_partner_promo_code[status]'] = 'Inactive';
        $form['dhi_admin_partner_promo_code_batch[reason]'] = 'This is reason made by test cases.';
        $form['dhi_admin_partner_promo_code_batch[note]'] = 'This is reason made by test notes.';
        $client->submit($form);
        $this->batchPromoCode = $serviceLocationName . $test_batch_rand_char . $test_batch_prefix;
        $this->assertTrue($client->getResponse()->isRedirect()); // check if redirecting properly
        $crawler = $client->followRedirect();
        $this->assertEquals(1, $crawler->filter('html:contains("ISP Partner Promo Code batch generated successfully!")')->count());
    }
    
    public function testEditCodeAction() {
    
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $objPartnerPromoCode = $this->em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array(), array('id' => 'DESC'));
        if($objPartnerPromoCode)
        {
            $objPartnerPromoCodeBatch = $objPartnerPromoCode->getBatchId();
            $this->assertNotNull($objPartnerPromoCodeBatch, 'Partner promocode batch id not found.');
            $crawler = $client->request('POST', '/admin/partner-promo-code/edit/'.$objPartnerPromoCodeBatch->getId().'/'.$objPartnerPromoCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\PartnerPromoCodeController::editCodeAction', $client->getRequest()->attributes->get('_controller'));
            $form = $crawler->selectButton('Update')->form();
            $form['dhi_admin_partner_promo_code_batch[reason]'] = 'Dummy reason while running edit test cases';
            $form['dhi_admin_partner_promo_code[note]'] = 'Dummy note while running edit test cases';
            $client->submit($form);
            $this->assertTrue($client->getResponse()->isRedirect()); // check if redirecting properly
            $crawler = $client->followRedirect();
            $this->assertEquals(1, $crawler->filter('html:contains("ISP Partner Promo Code has been updated successfully.")')->count());
        }
        else
        {
            $this->assertFalse(true, '"' . $partnerPromoCode . '" partner promocode not found.');
        }
        
    }
    
    public function testChangeCodeStatus() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $objPartnerPromoCode = $this->em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array(), array('id' => 'DESC'));
        if($objPartnerPromoCode)
        {
            $objPartnerPromoCodeBatch = $objPartnerPromoCode->getBatchId();
            $this->assertNotNull($objPartnerPromoCodeBatch, 'Partner promocode batch id not found.');
            $crawler = $client->request('POST', '/admin/partner-promo-code/code-status/'.$objPartnerPromoCodeBatch->getId().'/'.$objPartnerPromoCode->getId());
            $this->assertEquals('Dhi\AdminBundle\Controller\PartnerPromoCodeController::changeCodeStatusAction', $client->getRequest()->attributes->get('_controller'));
            $this->assertTrue($client->getResponse()->isRedirect()); // check if redirecting properly
            $crawler = $client->followRedirect();
        }
        else
        {
            $this->assertFalse(true, '"' . $partnerPromoCode . '" partner promocode not found.');
        }
        
    }
    
    public function testCodeListAction() {
        
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/partner-promo-batch/list');
        $this->assertEquals('Dhi\AdminBundle\Controller\PartnerPromoCodeController::indexAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("ISP Partner Promo Codes")')->count());
        
    }
    
    public function testDeleteBatchAction()
    {
        $client = static::createClient(
                        array(), array(
                    'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        
        $objBatchPromoCode = $this->em->getRepository('DhiAdminBundle:PartnerPromoCodeBatch')->findOneBy(array(), array('id' => 'DESC'));
        
        if($objBatchPromoCode)
        {
            $batchPromoCodeId = $objBatchPromoCode->getId();
            
            $crawler = $client->request('GET', '/admin/partner-batch/delete', array(
                    'id' => $batchPromoCodeId,
                    'reason' => 'Reason automatically added while running test cases'
            ));
            
            $this->assertEquals('Dhi\AdminBundle\Controller\PartnerPromoCodeController::deleteBatchAction', $client->getRequest()->attributes->get('_controller'));
            $jsonResponse = $client->getResponse()->getContent();
            $decodeResponse = json_decode($jsonResponse);
            $this->assertFalse($decodeResponse->type != 'success', $decodeResponse->message);
        }
        
    }
    
    public function testDeActivatedReportAction() {
        $client = static::createClient(
                array(), array(
            'HTTP_HOST' => static::$kernel->getContainer()->getParameter('test_http_host')
        ));
        $this->doLogin(static::$kernel->getContainer()->getParameter('test_admin_username'), static::$kernel->getContainer()->getParameter('test_admin_password'), $client);
        $crawler = $client->request('POST', '/admin/partner-promo-code/deactivated-list');
        $this->assertEquals('Dhi\AdminBundle\Controller\PartnerPromoCodeController::deActivatedReportAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals(1, $crawler->filter('html:contains("Partner PromoCode Deactivated Report")')->count());
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
