<?php


namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\ActivateMacUsersServiceCommand;

class ActivateMacUsersServiceCommandTest extends WebTestCase {
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
        
        $deactivateServiceLogs = $this->em->getRepository('DhiUserBundle:DeactivateWithOutMacUserServiceLog')->findBy(array('isActivated' => 1));
        $this->assertFalse(count($deactivateServiceLogs) == 0 , 'Without MAC deactivate service log data not availble');
        
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new ActivateMacUsersServiceCommand());
        $command = $objApplication->find('dhi:activate-mac-users-service');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/.../', $objcommandtest->getDisplay());

        if ($deactivateServiceLogs) {

            foreach ($deactivateServiceLogs as $deactiveServiceLog) {

                if ($deactiveServiceLog->getUser()) {

                    if (count($deactiveServiceLog->getUser()->getUserMacAddress()) > 0) {

                        $wsParam = array();
                        $wsParam['cuLogin'] = $deactiveServiceLog->getUser()->getUserName();

                        $activeCustomerResponse =  $this->container->get('selevisionService')->callWSAction('reactivateCustomer', $wsParam);

                        if ($activeCustomerResponse['status'] == 1) {
                            $userService = $deactiveServiceLog->getUserService();
                            $userServiceStatus = $userService->getStatus();
                            $this->assertEquals(1, $userServiceStatus);
                            $deactiveServiceLogStatus = $deactiveServiceLog->getIsActivated();
                            $this->assertEquals(1, $userServiceStatus);
                        }
                    }
                }
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
