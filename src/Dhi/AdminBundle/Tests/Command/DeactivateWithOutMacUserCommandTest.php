<?php


namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\ActivateMacUsersServiceCommand;

class DeactivateWithOutMacUserCommandTest extends WebTestCase {

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
        
        $userServices = $this->em->getRepository('DhiUserBundle:UserService')->getWithoutMacActiveIPTVService();
        $this->assertFalse($userServices == false, 'Without MAC IPTV service data not availble');
        
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new \Dhi\AdminBundle\Command\DeactivateWithOutMacUserCommand());
        $command = $objApplication->find('dhi:deactivate-without-mac-user');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/.../', $objcommandtest->getDisplay());
        
        // new cron execute will not start until old will not be completed.
        if ($userServices) {

            foreach ($userServices as $userService) {

                $isCheckedForInactive = false;

                $todayDateTime = new \DateTime();
                $activationDate = $userService->getActivationDate();

                $interval = $activationDate->diff($todayDateTime);
                $days = $interval->format('%a');

                if ($days >= 3) {

                    if ($userService->getUser()) {

                        if (count($userService->getUser()->getUserMacAddress()) <= 0) {
                             $deactivateServiceLogs = $this->em->getRepository('DhiUserBundle:DeactivateWithOutMacUserServiceLog')->findBy(array('user' => $userService->getUser()));
                        $wsParam = array();
                        $wsParam['cuLogin'] = $userService->getUser()->getUserName();
                        $activeCustomerResponse =  $this->container->get('selevisionService')->callWSAction('reactivateCustomer', $wsParam);

                        if ($activeCustomerResponse['status'] == 1) {
                             $this->assertEquals(1, count($deactivateServiceLogs));
                        }else{
                            $this->assertEquals(0, count($deactivateServiceLogs));
                        }
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




