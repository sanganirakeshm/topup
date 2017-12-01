<?php
namespace Dhi\AdminBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Dhi\AdminBundle\Entity\SelevisionLoginHistory;
use \DateTime;

class SelevisionLoginHistoryCommand extends ContainerAwareCommand {

	private $output;
	protected function configure() {
		$this->setName('dhi:get-iptv-login-history')->setDescription('Get Login History From Selevision.');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("\n####### Start Selevision Login History Cron at " . date('M j H:i') . " #######\n");
		$em           = $this->getContainer()->get('doctrine')->getManager();
		$selevision   = $this->getContainer()->get('selevisionService');
		$iptvUsers    = $em->getRepository("DhiUserBundle:UserService")->getServiceUsers(array("IPTV"));
		$toFlush      = false;
		$startingDate = new DateTime("-30 DAYS");

		foreach ($iptvUsers as $userService) {
			$params = array(
				'cuLogin' => $userService->getUser()->getUsername()
			);
			$historyData = $selevision->callWSAction('getCustomerSessionLogs', $params);
			if (!empty($historyData)) {
				
				foreach ($historyData as $record) {
					if (!empty($record['created']) && $record['created'] != "0000-00-00 00:00:00") {
						$startDate = new DateTime($record['created']);
						if ($startDate < $startingDate) {
							continue;
						}
						$arr = array(
							'user'           => $userService->getUser(),
							'start_date'     => !empty($record['created']) && $record['created'] != "0000-00-00 00:00:00" ? $record['created'] : null,
							'end_date'       => !empty($record['ended']) && $record['ended'] != "0000-00-00 00:00:00" ? $record['ended'] : null,
							'refreshed_date' => !empty($record['refreshed']) && $record['refreshed'] != "0000-00-00 00:00:00" ? $record['refreshed'] : null
						);

						$objId  = $em->getRepository("DhiAdminBundle:SelevisionLoginHistory")->findLoginHistory($arr);
						if (!empty($objId[0]['id'])) {
							$selevisionLoginHistory = $em->getRepository("DhiAdminBundle:SelevisionLoginHistory")->find($objId[0]['id']);
						}else{
							$selevisionLoginHistory = new SelevisionLoginHistory();
							$selevisionLoginHistory->setUser($userService->getUser());
							$selevisionLoginHistory->setStartDate($startDate);
						}

						if (!empty($record['refreshed']) && $record['refreshed'] != "0000-00-00 00:00:00") {
							$refreshedDate = new DateTime($record['refreshed']);
							$selevisionLoginHistory->setRefreshedDate($refreshedDate);
						}
						if (!empty($record['ended']) && $record['ended'] != "0000-00-00 00:00:00") {
							$endDate = new DateTime($record['ended']);
							$selevisionLoginHistory->setEndDate($endDate);
						}
						$em->persist($selevisionLoginHistory);
						$toFlush = true;
					}
				}
			}
		}
		if($toFlush) {
			$em->flush();
		}
		$output->writeln("\n####### End Cron #######\n");
	}
}
