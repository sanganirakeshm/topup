<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class UserLogBackupCommand extends ContainerAwareCommand {

    protected function configure() {
        parent::configure();

        $this->setName('dhi:backup-user-log-history')->setDescription('This cron will take backup of user log details');
    }

    public function execute(InputInterface $input, OutputInterface $output) {

        $output->writeln("\n####### Start User Log Backup Cron at " . date('M j H:i') . " #######\n");

        $date = new DateTime();

        $date->modify('-90 day');

        // establish a connection to our database
        $defaultConnection = $this->getContainer()->get('doctrine.dbal.default_connection');

        // establish a connection to backup database
        $secondaryConnection = $this->getContainer()->get('doctrine.dbal.secondary_connection');

        // fetch all the records except last one month
        $data = $defaultConnection->fetchAll('SELECT * FROM user_activity_log WHERE timestamp <= \'' . $date->format('Y-m-d') . '\'');

        $count = 0;
        foreach ($data as $record) {

            try {
                // insert record in to backup database
                $result = $secondaryConnection->exec('INSERT INTO user_activity_log 
                (id, user, admin, activity, description, visited_url, session_id, ip, timestamp) VALUES (
                ' . $record['id'] . ',
                "' . ($record['user'] != "" ? $record['user'] : NULL) . '",
                "' . ($record['admin'] != "" ? $record['admin'] : NULL) . '",
                "' . $record['activity'] . '",
                "' . $record['description'] . '",
                "' . $record['visited_url'] . '",
                "' . $record['session_id'] . '",
                "' . $record['ip'] . '",
                "' . $record['timestamp'] . '"
                )');

                // check whether query was successful or not
                if ($result) {
                    $count++;
                    $defaultConnection->exec('DELETE FROM user_activity_log WHERE id=' . $record['id']);
                }
            } catch (\Exception $e) {

                $output->writeln('Exception occured while inserting record with id: ' . $record['id']);
            }
        }

        $output->writeln('Total ' . $count . ' records found.');
        $output->writeln("\n####### End Cron #######\n");
    }

}
