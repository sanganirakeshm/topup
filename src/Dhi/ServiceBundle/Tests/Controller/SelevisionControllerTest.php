<?php

namespace Dhi\AdminBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Dhi\UserBundle\Entity\UserMacAddress;

class SelevisionControllerTest extends WebTestCase {

    protected $selevisionService;
    protected $username;
    protected $password;
    protected $frontUserName;
    protected $testMacAddress;
    protected $testUpdateMacAddress;
    protected $testDeleteMacAddress;
    protected $em;

    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();
        $this->selevisionService = static::$kernel->getContainer()->get('selevisionService');
        $this->username = static::$kernel->getContainer()->getParameter('test_admin_username');
        $this->password = static::$kernel->getContainer()->getParameter('test_admin_password');
        $this->frontUserName = static::$kernel->getContainer()->getParameter('test_front_username');
        $this->testMacAddress = static::$kernel->getContainer()->getParameter('test_mac_address');
        $this->testUpdateMacAddress = static::$kernel->getContainer()->getParameter('test_update_mac_address');
        $this->testDeleteMacAddress = static::$kernel->getContainer()->getParameter('test_delete_mac_address');

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    public function testCreateNewUser() {

        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));
        $this->assertFalse(count($user) == 0, 'User not found');
        $flag = $this->selevisionService->createNewUser($user);
        $this->assertFalse($flag == false, 'Unable to create selevision user');
    }
    
    
    public function testgetAllPackageDetails() {

        $packageArr = $this->selevisionService->getAllPackageDetails();
        $type = gettype($packageArr);
        $this->assertEquals($type, 'array');
    }
    
    public function testgetActivePackageIds() {

        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));
        $this->assertFalse(count($user) == 0, 'User not found');
        $activePackageIds = $this->selevisionService->getActivePackageIds($user->getUsername());
        $type = gettype($activePackageIds);
        $this->assertEquals($type, 'array');
    }
    
    public function testuserLoginSelevision() {

        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));
        $this->assertFalse(count($user) == 0, 'User not found');
        $wsResponseUserLogin = $this->selevisionService->userLoginSelevision($user->getUsername());
        $this->assertFalse($wsResponseUserLogin['status'] == 0, 'Unable to login in selevision');
    }
    
    public function testregisterMacAddressSelevision() {

        $wsResponse = $this->selevisionService->registerMacAddressSelevision($this->testMacAddress, 1);
        $detail = isset($wsResponse['detail']) ? $wsResponse['detail'] : '';
        $this->assertFalse($wsResponse['status'] == 0, 'Unable to register mac address in selevision '. $detail);
    }
    
    public function testsetMacAddressSelevision() {

        $wsResponseSetMacAddress = $this->selevisionService->setMacAddressSelevision($this->testMacAddress, 1, '', $this->frontUserName, 1);
        $detail = isset($wsResponseSetMacAddress['detail']) ? $wsResponseSetMacAddress['detail'] : '';
        $this->assertFalse($wsResponseSetMacAddress['status'] == 0, 'Unable to set mac address in selevision '. $detail);
    }
    
    public function testgetMacAddressSequenceNumber() {

        $user = $this->em->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $this->frontUserName));
        $this->assertFalse(count($user) == 0, 'User not found');
        $sequence_number = $this->selevisionService->getMacAddressSequenceNumber($user);
        $type = gettype($sequence_number);
        $this->assertEquals($type, 'integer');
    }
    
    public function testunsetMacAddressSelevision() {

        $wsResponseUnsetMacAddress = $this->selevisionService->unsetMacAddressSelevision($this->testMacAddress, '', $this->frontUserName, 1);
        $this->assertFalse($wsResponseUnsetMacAddress['status'] == 0, 'Unable to unset mac address in selevision');
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
