<?php
namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\ActivateMacUsersServiceCommand;
use Dhi\AdminBundle\Command\SendPackageExpiredNotificationCommand;

class SendPackageExpiredNotificationCommandTest extends WebTestCase {
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
        $objUserService = $em->getRepository('DhiUserBundle:UserService')->getExpiredPackageForNotification();
        $this->assertFalse($objUserService == false, 'Expired package not found.');
        
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new SendPackageExpiredNotificationCommand());
        $command = $objApplication->find('dhi:send-package-expired-notification');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/../', $objcommandtest->getDisplay());
       
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


