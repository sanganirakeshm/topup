<?php

namespace Dhi\AdminBundle\Controller\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Console\Command;
use Dhi\AdminBundle\Command\ActivateMacUsersServiceCommand;
use Dhi\AdminBundle\Command\DeleteFreePlansCommand;

class DeleteFreePlansCommandTest extends WebTestCase {

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
        $objApplication->add(new DeleteFreePlansCommand());
        $command = $objApplication->find('dhi:delete-free-plans');
        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $this->assertRegExp('/.../', $objcommandtest->getDisplay());
        $condition = array("10000", "10001");
        $service = $this->em->getRepository("DhiUserBundle:Service")->findOneBy(array("name" => 'IPTV'));

        $freeUserPlans = $this->em->getRepository("DhiUserBundle:UserService")->getPlansByPackageId($condition, $service, true);

        $toFlush = false;
        $selevisionService = $this->container->get('selevisionService');

        if ($freeUserPlans) {
           foreach ($freeUserPlans as $key => $plan) {
                $user = $plan->getUser();
                // test Free plan from Selevision remove
//                    $wsOfferParam = array();
//                    $wsOfferParam['cuLogin'] = $user->getUserName();
//                    $wsOfferParam['offer'] = $plan->getPackageId();
//                    $wsResponse = $selevisionService->callWSAction('getCustomerOffer', $wsOfferParam);
//                    $this->assertEquals(1, $wsResponse['status']);
                    $servicePurchase = $this->em->getRepository("DhiServiceBundle:ServicePurchase")->findById($plan->getServicePurchase()->getId());
                    $this->assertEquals(0,count($servicePurchase));
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
