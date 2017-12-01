<?php


namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\DeleteNoPlanUserCommand;

class DeleteNoPlanUserCommandTest extends WebTestCase {

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
        $objApplication->add(new DeleteNoPlanUserCommand());
        $command = $objApplication->find('dhi:delete-no-plan-users');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/.../', $objcommandtest->getDisplay());

        // check
        $objLocation = $this->em->getRepository("DhiAdminBundle:Setting")->findOneBy(array("name" => 'delete_user_cron_service_location'));
        $this->assertFalse(count($objLocation) == 0, "'delete_user_cron_service_location' record not found in settings.");
		$isEmptyLocation = false;

		if($objLocation) {
			$strLocation = $objLocation->getValue();
			if(!empty($strLocation)) {
				$arrLocation = explode(',', trim($strLocation));
				$arrLocation = array_map('trim', $arrLocation);
				$isEmptyLocation = true;
			}
		}

		if($isEmptyLocation == true) {
			$users = $this->em->getRepository("DhiUserBundle:User")->getLocationWiseCustomers($arrLocation);
            $this->assertEquals(0,count($users));
        }else{
            echo "delete_user_cron_service_location value is empty in setting table";
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
