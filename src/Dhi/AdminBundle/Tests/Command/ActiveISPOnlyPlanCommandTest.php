<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class ActiveISPOnlyPlanCommandTest extends WebTestCase {
    protected static $application;

    public function setUp()
    {
        self::runCommand('dhi:activate-isp-plan');
        // you can also specify an environment:
        // self::runCommand('your:command:name --env=test');
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
    
    public function testispcommand(){
        $command = sprintf('%s --quiet', 'dhi:activate-isp-plan');
        $this->runCommand($command);
        return true;
    }
}
