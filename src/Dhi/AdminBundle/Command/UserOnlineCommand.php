<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\AdminBundle\Entity\UserOnlineSession;

class UserOnlineCommand extends ContainerAwareCommand {

	private $output;

	protected function configure() {

		$this->setName('dhi:get-online-session')
				->setDescription('Get user online session from aradial api');
	}

	public function execute(InputInterface $input, OutputInterface $output) {

		$output->writeln("\n####### Start User Online Session Cron at " . date('M j H:i') . " #######\n");
		$em = $this->getContainer()->get('doctrine')->getManager();
		$connection = $em->getConnection();
		$queryChannel = $connection->prepare('UPDATE user_online_session SET is_offline = "y" WHERE is_offline = "n"');
		$queryChannel->execute();

		// $output->writeln("\n####### Truncate User Online Session Cron #######\n");
		$wsParam = array();
		$wsParam['Page']    = 'Sessions';
		$wsParam['OnePage'] = '1';

		$aradial = $this->getContainer()->get('aradial');
		$wsResponse = $aradial->callWSAction('getUserSession', $wsParam);

		if (isset($wsResponse['userOnline'])) {
			foreach ($wsResponse['userOnline'] as $user) {
				$userName      = (isset($user['UserID']) ? $user['UserID'] : '');
				$nasName       = (isset($user['NASName']) ? $user['NASName'] :'');
				$onlineSince   = (isset($user['StartTime']) ? $user['StartTime'] : '');
				$timeOnline    = (isset($user['SessionTime']) ? : '');
				$userIp        = (isset($user['UserIP']) ? $user['UserIP'] : '');
				$nasId         = (isset($user['NasID']) ? $user['NasID'] : '');
				$nasPort       = (isset($user['NASPort']) ? $user['NASPort'] : '');
				$acctSessionId = (isset($user['AcctSessionId']) ? $user['AcctSessionId'] :'');

				$objUserSession = new UserOnlineSession();
				$objUserSession->setUserName($userName);
				$objUserSession->setNasName($nasName);
				$objUserSession->setOnlineSince($onlineSince);
				$objUserSession->setTimeOnline($timeOnline);
				$objUserSession->setUserIp($userIp);
				$objUserSession->setNasId($nasId);
				$objUserSession->setNasPort($nasPort);
				$objUserSession->setAccountSessionId($acctSessionId);

				$em->persist($objUserSession);
				$em->flush();

				// $output->writeln("".$userName." added successfully");

			}
		} else {
			$output->writeln("\n####### No online users found! #######\n");
		}

		$output->writeln("\n####### End Cron #######\n");
	}
}
