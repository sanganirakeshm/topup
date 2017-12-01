<?php
namespace Dhi\AdminBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class ExtendEmployeePlanCommand extends ContainerAwareCommand {
	private $output;
	protected function configure() {
		$this->setName('dhi:extend-employee-plan')->setDescription('Extend plan for employee users.');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("\n####### Start Extend plans for employees Cron at " . date('M j H:i') . " #######\n");
		$em = $this->getContainer()->get('doctrine')->getManager();
		$aradial = $this->getContainer()->get('aradial');

		$employeePackages = $em->getRepository("DhiUserBundle:UserService")->getPlanPlans();
		$toFlush = false;	
		foreach ($employeePackages as $empPackage) {
			$userId = $empPackage->getUser()->getUsername();
			$service = $empPackage->getService()->getName();
			$output->writeln("Extending $service plan for employee $userId");
			if(strtoupper($service) == 'ISP'){
				$userExists = $aradial->getUserInfo($userId);
				if ($userExists) {
					$planExpiryDate = trim((string)$userExists['PlanExpirationDate']);
					$planActivationDate = trim((string)$userExists['PlanActivationDate']);
					if (!empty($planExpiryDate)) {
						$validity       = $empPackage->getValidity();
						$activationDate = $empPackage->getActivationDate();
						$expiryDate     = $empPackage->getExpiryDate();
						
						$expiryDate     = new \DateTime($planExpiryDate);
						$expiryDate->modify('+'.$validity.' DAYS');

						$intervalRemainDays = $activationDate->diff($expiryDate);
                    	$validityDays =  $intervalRemainDays->format('%a');

						$user = $empPackage->getUser();
						$isUserUpdated = $aradial->updateUserIsp($user, "", $expiryDate);
						if($isUserUpdated){
							$empPackage->setValidity($validityDays);
							$empPackage->setExpiryDate($expiryDate);
							$em->persist($empPackage);
							$toFlush = true;
						}
					}
				}
			}else if(strtoupper($service) == 'IPTV'){
				$validity           = $empPackage->getValidity();
				$activationDate     = $empPackage->getActivationDate();
				$planExpiryDate     = $empPackage->getExpiryDate();
				$expiryDate         = clone $planExpiryDate;
				$expiryDate->modify('+'.$validity.' DAYS');

				$intervalRemainDays = $activationDate->diff($expiryDate);
            	$validityDays =  $intervalRemainDays->format('%a');

				$empPackage->setExpiryDate($expiryDate);
				$empPackage->setValidity($validityDays);
				$em->persist($empPackage);
				$toFlush = true;
			}
		}

		if($toFlush) {
			$em->flush();
		}

		$output->writeln("\n####### End Cron #######\n");
	}
}
