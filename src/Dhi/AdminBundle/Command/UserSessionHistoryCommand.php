<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\AdminBundle\Entity\UserSessionHistory;

class UserSessionHistoryCommand extends ContainerAwareCommand {

	private $output;

	protected function configure() {
		$this->setName('dhi:get-session-history')
				->setDescription('Get user session history from aradial api');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("\n####### Start User Session History Cron at " . date('Y M j H:i') . " #######\n");
		$em = $this->getContainer()->get('doctrine')->getManager();

		$aradial                           = $this->getContainer()->get('aradial');
		$maxDateTime                       = $em->getRepository("DhiAdminBundle:UserSessionHistory")->getMaxDate();
		$wsParam                           = array();
		$wsParam['Page']                   = 'UserSessions';
		$wsParam['SessionsMode']           = 'UsrAllSessions';
		// $wsParam['OnePage']                = 1;
		$maxDate                           = new \DateTime($maxDateTime);
		$wsParam['op_$D$AcctSessionTime'] = "<>";
		$wsParam['qdb_$D$AcctSessionTime'] = " ";


		if (!empty($maxDateTime)) {
			$wsParam['op_$D$AcctDate']  = '>';
			$wsParam['qdb_$D$AcctDate'] = $maxDate->format("m/d/Y");
		}

		$wsResponse = $aradial->callWSAction('getUserSessionHistory', $wsParam);

		if (!empty($wsResponse['userSession'])) {
			foreach ($wsResponse['userSession'] as $user) {
				$userName        = $user['UserID'];
				$nasName         = $user['NASName'];
				$startTime       = $user['InTime'];
				$stopTime        = $user['TimeOnline'];
				$framedAddress   = $user['FramedAddress'];
				$callerId        = $user['CallerId'];
				$calledId        = $user['CalledId'];
				$acctSessionTime = $user['AcctSessionTime'];
				$isRefunded      = 0;

				if (!empty($userName)) {
					$userData = $em->getRepository('DhiUserBundle:User')->getEmailForAradialUser($userName);
				}

				if ($userData) {
					$email = $userData[0]['email'];
				} else {
					$email = '';
				}

				$Param                     = array();
				$Param['Page']             = 'UserHit';
				$Param['qdb_Users.UserID'] = $userName;

				if (empty($email)) {
					$wsResponse1 = $aradial->callWSAction('getUserList', $Param);
					if (!empty($wsResponse1['userList'])) {
						$email = empty($wsResponse1['userList'][0]['UserDetails.Email']) ? '' : $wsResponse1['userList'][0]['UserDetails.Email'];
					}
				}

				if(!empty($acctSessionTime)){

					$condition = array(
						"userName"      => $userName,
						"email"         => $email,
						"nasName"       => $nasName,
						"startTime"     => $startTime,
						"stopTime"      => $stopTime,
						"framedAddress" => $framedAddress
					);

					$existingUerSession = $em->getRepository("DhiAdminBundle:UserSessionHistory")->findBy($condition);
					if ($existingUerSession) {
						// $output->writeln($userName . " already exists.");

					}else{

						$objUserSession = new UserSessionHistory();
						$objUserSession->setUserName($userName);
						$objUserSession->setEmail($email);
						$objUserSession->setNasName($nasName);
						$objUserSession->setStartTime($startTime);
						$objUserSession->setStopTime($stopTime);
						$objUserSession->setCallerId($callerId);
						$objUserSession->setCalledId($calledId);
						$objUserSession->setFramedAddress($framedAddress);
						$objUserSession->setIsRefunded($isRefunded);
                        if (!empty($startTime) && !empty($stopTime)) {
							$objUserSession->setStartDateTime(new \DateTime($startTime));
						}
						if (!empty($stopTime) && !empty($startTime)) {
                            list($hours, $minutes, $seconds) = sscanf($stopTime, '%d:%d:%d');
                            $sTime = new \DateTime($startTime);
                            $objStopTime = clone $sTime;
                            $seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
                            $objStopTime->modify('+'.$seconds.' seconds');
							$objUserSession->setStopDateTime($objStopTime);
						}
						$em->persist($objUserSession);
						$em->flush();

						// $output->writeln($userName . " added successfully");
					}
				}
			}
		}
		$output->writeln("\n####### End Cron #######\n");
	}
}