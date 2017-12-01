<?php
namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Dhi\AdminBundle\Command\CompensationCommand;
use Symfony\Component\Console\Tester\CommandTester;

class CompensationCommandTest extends WebTestCase {
    protected static $application;
    protected $em;
    protected $container;

    /**
    * {@inheritDoc}
    */
    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
        $this->container = static::$kernel->getContainer();
    }

    public function testExecute() {
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new CompensationCommand());
        $command = $objApplication->find('dhi:compensation');

        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $result = $objcommandtest->getDisplay();

        $setting = $this->em->getRepository("DhiAdminBundle:Setting")->findOneByName('compensation_to_email');
        $this->assertFalse(count($setting) == 0, "'compensation_to_email' record not found in settings.");

        $objRunningCompensation = $this->em->getRepository('DhiUserBundle:Compensation')->findOneBy(array('status' => 'Inprogress', 'isActive' => true));
        if ($objRunningCompensation) {
            $IsCronInrogress = strpos($result, "Cron already in progress");
            $this->assertFalse( $IsCronInrogress === false, "Another Compensation already in progress. But, Compensation executed successfully.");
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