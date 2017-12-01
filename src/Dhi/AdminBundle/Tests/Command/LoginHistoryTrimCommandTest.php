<?php


namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\LoginHistoryTrimCommand;

class LoginHistoryTrimCommandTest extends WebTestCase {

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
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new LoginHistoryTrimCommand());
        $command = $objApplication->find('dhi:login-history-trim');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/.../', $objcommandtest->getDisplay());

        // check
        $objUserLoginLog = $this->em->getRepository('DhiUserBundle:UserLoginLog')->getAfterNinetyLoginLog();
        $this->assertFalse($objUserLoginLog == false, 'Login log not found.');
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
