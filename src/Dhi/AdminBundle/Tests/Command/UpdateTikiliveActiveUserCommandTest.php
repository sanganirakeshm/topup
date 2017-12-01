<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class UpdateTikiliveActiveUserCommandTest extends WebTestCase {
    protected static $application;

    public function setUp()
    {
        self::runCommand('dhi:update-tikilive-active-user');
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
    
    public function testTikilivePromocodeNotificationCommand(){
        $command = sprintf('%s --quiet', 'dhi:update-tikilive-active-user');
        $this->runCommand($command);
        return true;
    }
}


