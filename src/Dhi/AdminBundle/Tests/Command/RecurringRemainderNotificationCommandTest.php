<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class RecurringRemainderNotificationCommandTest extends WebTestCase {
    protected static $application;
    protected $em;
    
    /**
    * {@inheritDoc}
    */
    public function setUp()
    {
        self::runCommand('dhi:recurring-payment-remainder-notification');
        // you can also specify an environment:
        // self::runCommand('your:command:name --env=test');
        $this->em = static::$kernel->getContainer()
                ->get('doctrine')
                ->getManager();
    }

    protected static function runCommand($command)
    {
        $command = sprintf('%s --quiet', $command);    

        return self::getApplication()->run(new StringInput($command));
    }

    protected static function getApplication()
    {
        if (null === self::$application) {
            $client = static::createClient();

            self::$application = new Application($client->getKernel());
            self::$application->setAutoExit(false);
        }

        return self::$application;
    }
    
    public function testRecurringRemainderNotificationCommand(){
        $objPaypalRecurring = $this->em->getRepository('DhiServiceBundle:PaypalRecurringProfile')->getNextbillDataForNotification();
        $this->assertFalse($objPaypalRecurring == false, 'Recurring remainder data not found.');
        $command = sprintf('%s --quiet', 'dhi:recurring-payment-remainder-notification');
        $this->runCommand($command);
        return true;
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


