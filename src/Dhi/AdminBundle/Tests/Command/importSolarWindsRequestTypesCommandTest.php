<?php
namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Dhi\AdminBundle\Command\importSolarWindsRequestTypesCommand;

class importSolarWindsRequestTypesCommandTest extends WebTestCase {
    protected $container;
    protected $em;

    protected function setUp() {

        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->container = static::$kernel->getContainer();
    }
    
    /**
     * This action allows to execute import Solar winds data
     */
    public function testExecute() {
       
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new importSolarWindsRequestTypesCommand());
        $command = $objApplication->find('dhi:import-solar-winds-request-types');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/../', $objcommandtest->getDisplay());
       
    }
}