<?php

namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\ActivateMacUsersServiceCommand;
use Dhi\AdminBundle\Command\UserOnlineCommand;

class UserOnlineCommandTest extends WebTestCase {
    protected $container;
    protected $em;
    
    /**
    * {@inheritDoc}
    */
    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        $this->container = static::$kernel->getContainer();
    }
    
    /**
     * This action allows to list product
     */
    public function testExecute() {
        $em = $this->em;
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new UserOnlineCommand());
        $command = $objApplication->find('dhi:get-online-session');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/../', $objcommandtest->getDisplay());
        
        $aradial = $this->container->get('aradial');
        $wsParam = array();
        $wsParam['Page']    = 'Sessions';
        $wsParam['OnePage'] = '1';
        $wsResponse = $aradial->callWSAction('getUserSession', $wsParam);

        if (isset($wsResponse['userOnline'])) {
                foreach ($wsResponse['userOnline'] as $user) {
                        $userName      = (isset($user['UserID']) ? $user['UserID'] : '');
                        $nasName       = (isset($user['NASName']) ? $user['NASName'] :'');
                        $onlineSince   = (isset($user['StartTime']) ? $user['StartTime'] : '');
                        $timeOnline    = (isset($user['SessionTime']) ? : '');
                        $userIp        = (isset($user['UserIP']) ? $user['UserIP'] : '');
                        $nasId         = (isset($user['NasID']) ? $user['NasID'] : '');
                        $nasPort       = (isset($user['NASPort']) ? $user['NASPort'] : '');
                        $acctSessionId = (isset($user['AcctSessionId']) ? $user['AcctSessionId'] :'');

                        $objUserSession = new UserOnlineSession();
                        $objUserSession->setUserName($userName);
                        $objUserSession->setNasName($nasName);
                        $objUserSession->setOnlineSince($onlineSince);
                        $objUserSession->setTimeOnline($timeOnline);
                        $objUserSession->setUserIp($userIp);
                        $objUserSession->setNasId($nasId);
                        $objUserSession->setNasPort($nasPort);
                        $objUserSession->setAccountSessionId($acctSessionId);

                        $em->persist($objUserSession);
                        $em->flush();
                       
                        $this->assertFalse(true, "".$userName." added successfully");

                }
        }
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


