<?php
namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Dhi\AdminBundle\Command\AbandonedPurchasesUserCommand;

class AbandonedPurchasesUserCommandTest extends WebTestCase {
    protected $container;
    protected $em;

    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->container = static::$kernel->getContainer();
    }
    
    /**
     * This action allows to execute Abandoned Purchase User command
     */
    public function testExecute() {
        $em = $this->em;
        $userpurchase = $em->getRepository("DhiUserBundle:User")->getAbandedPurchase();
        $this->assertFalse($userpurchase == false, 'No User Found.');
        
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new AbandonedPurchasesUserCommand());
        $command = $objApplication->find('dhi:abandoned-purchase-user');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/../', $objcommandtest->getDisplay());
       
    }
}