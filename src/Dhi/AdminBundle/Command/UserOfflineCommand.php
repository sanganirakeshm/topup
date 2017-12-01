<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\AdminBundle\Entity\UserOnlineSession;

class UserOfflineCommand extends ContainerAwareCommand {

	private $output;
	protected function configure() {
		$this->setName('dhi:set-offline-session')
				->setDescription('Set user offline from aradial api');
	}

	public function execute(InputInterface $input, OutputInterface $output) {

		$output->writeln("\n####### Start User Offline Session Cron at " . date('M j H:i') . " #######\n");
		$em = $this->getContainer()->get('doctrine')->getManager();
		$connection = $em->getConnection();
		$queryChannel = $connection->prepare('DELETE FROM user_online_session WHERE is_offline = "y"');
		$queryChannel->execute();
		$output->writeln("\n####### End Cron #######\n");
	}
}
