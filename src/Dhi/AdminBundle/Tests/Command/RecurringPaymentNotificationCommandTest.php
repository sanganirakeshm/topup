<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class RecurringPaymentNotificationCommandTest extends WebTestCase {
    protected static $application;
    protected $em;
    
    /**
    * {@inheritDoc}
    */
    public function setUp()
    {
        self::runCommand('dhi:recurring-payment-notification');
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
    
    public function testRecurringPaymentNotificationCommand(){
        $objPaypalRecurringProfile = $this->em->getRepository('DhiServiceBundle:PaypalRecurringProfile')->findBy(array('profileStatus' => 'Active'));
        $this->assertFalse($objPaypalRecurringProfile == false, 'Active Recurring Profile Not Found.');
        $command = sprintf('%s --quiet', 'dhi:recurring-payment-notification');
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


