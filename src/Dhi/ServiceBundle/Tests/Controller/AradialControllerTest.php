<?php

namespace Dhi\ServiceBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AradialControllerTest extends WebTestCase
{
    protected $container;
    protected $securityContext;
    protected $session;
    protected $em;
    protected $aradialusername;
    protected $aradialPassword;
    
    /**
    * {@inheritDoc}
    */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->container = static::$kernel->getContainer();
        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->session           = static::$kernel->getContainer()->get('session');
        $this->securitycontext   = static::$kernel->getContainer()->get('security.context');
        $this->aradialusername   = static::$kernel->getContainer()->getParameter('test_check_User_Auth_Aradial_username');
        $this->aradialPassword   = static::$kernel->getContainer()->getParameter('test_check_User_Auth_Aradial_password');
    }

    public function doLogin($username, $password, $client) {
        $crawler = $client->request('POST', 'admin/login');
        //echo $client->getResponse()->getContent();exit;
        $form = $crawler->selectButton('Sign In')->form(array(
            '_username' => $username,
            '_password' => $password,
        ));
        $client->submit($form);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
    }

    public function testcallWS(){
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        $aradial = $this->container->get('aradial');
        $wsParam = array();
        $wsParam['Page'] = 'UserSessions';
        $wsParam['SessionsMode'] = 'UsrAllSessions';
        $wsParam['qdb_Users.UserID'] = $objUser->getUserName();
        $wsResponse =  null;
        try{
            $wsResponse = $aradial->callWSAction('getUserSessionHistory', $wsParam);
        } catch (Exception $ex) {
            $this->assertFalse( 0 > 2, 'Please check callws function ');
        }


        $this->assertFalse(count($wsResponse) < 2, 'Please check callws function ');
    }

    public function testsendWsRequest(){
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        $aradial = $this->container->get('aradial');
        $wsParam = array();
        $wsParam['Page'] = 'UserSessions';
        $wsParam['SessionsMode'] = 'UsrAllSessions';
        $wsParam['qdb_Users.UserID'] = $objUser->getUserName();
        $action  = 'getUserSessionHistory';
        $wsResponse = null;

        try{
            $wsResponse = $aradial->callWSAction('getUserSessionHistory', $wsParam);
        } catch (Exception $ex) {
            $this->assertFalse( 0 > 2, 'Please check sendWs function ');
        }

        $this->assertFalse(count($wsResponse) < 2, 'Please check sendWs function ');
    }

    public function testcreateUser(){
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        $aradial = $this->container->get('aradial');
        $wsResponse = $aradial->createUser($objUser,null,1,true);
        $type = gettype($wsResponse);
        $this->assertEquals($type, 'boolean');
    }

    public function testcreateUserIsp(){
        $aradial = $this->container->get('aradial');
        $wsResponse = $aradial->createUserIsp($this->aradialusername, $this->aradialPassword);
        $type = gettype($wsResponse);
        $this->assertEquals($type, 'boolean');
    } 

    public function testupdateUserIsp(){
        $aradial = $this->container->get('aradial');
        $objUser = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array(), array('id' => 'DESC'));
        $this->assertFalse(count($objUser) == 0, 'User not found');
        $time = new \DateTime();
        $wsResponse = $aradial->updateUserIsp($objUser,'',$time);
        $type = gettype($wsResponse);
        $this->assertEquals($type, 'boolean');
    } 

    public function testcheckUserExistsInAradial(){
        $aradial = $this->container->get('aradial');
        $wsResponse = $aradial->checkUserExistsInAradial($this->aradialusername);
        $this->assertFalse(count($wsResponse) < 2, 'Please check sendWs function ');
    }

    public function testgetCurlResponse(){
        $singleUsrParam = array();
        $singleUsrParam['Page'] = 'UserEdit';
        $singleUsrParam['UserID'] = $this->aradialusername;
        $singleUsrParam['CheckPassword'] = $this->aradialPassword;
         $aradial = $this->container->get('aradial');
        
        $xml_verify = $aradial->getCurlResponse($singleUsrParam);
        $gettypeofresponse =  gettype($xml_verify);
        $this->assertEquals($gettypeofresponse, 'object');
    }

    public function testcheckUserAuthAradial() {
        $aradial = $this->container->get('aradial');
        $xml_verify = $aradial->checkUserAuthAradial($this->aradialusername, $this->aradialPassword);
        $type = gettype($xml_verify);
        $this->assertEquals($type, 'array');
    }
    public function testcheckEmailAvailableAradial(){
        $aradial = $this->container->get('aradial');
        $xml_verify = $aradial->checkEmailAvailableAradial(static::$kernel->getContainer()->getParameter('test_check_User_Auth_Aradial_email'));
        $type = gettype($xml_verify);
        $this->assertEquals($type, 'array');
    }
    public function testgetUserInformation(){
        $aradial = $this->container->get('aradial');
        $xml_verify = $aradial->getUserInformation($this->aradialusername);
        $type = gettype($xml_verify);
        $this->assertEquals($type, 'array');
    }
    
    public function testgetOffer(){
        $aradial = $this->container->get('aradial');
        $wsResponse = $aradial->getOffer();
        $type = gettype($wsResponse);
        $this->assertEquals($type, 'array');
    }
    
    public function testgetUserBalance(){
        $aradial = $this->container->get('aradial');
        $xml_verify = $aradial->getUserBalance($this->aradialusername);
        $type = gettype($xml_verify);
        $this->assertEquals($type, 'array');
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
