<?php
namespace Dhi\AdminBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReAssignPromoCodesCommand extends ContainerAwareCommand {

	private $output;
	private $em;
	protected function configure() {
		$this->setName('dhi:reassign-promo-codes')->setDescription('Reassign Promo Codes (Business, Partner and Customer Promo Codes).');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("\n####### Start Reassign Promo Codes Cron at " . date('M j H:i') . " #######\n");
		$this->em = $this->getContainer()->get('doctrine')->getManager();

		// Get Active Packages
		$activePlans = $this->getPackages();

		// Partner Promo Codes
		if (!empty($activePlans['package'])) {
			$updatedCodes = $this->em->getRepository("DhiAdminBundle:PartnerPromoCodes")->reAssignPlanCodes($activePlans['package']);
			$output->writeln("\n-- Updating $updatedCodes Partner Promo Codes: Completed");
		}

		if (!empty($activePlans)) {
			// Business Promo Codes
			$updatedBusinessCodes = $this->em->getRepository("DhiAdminBundle:BusinessPromoCodes")->reAssignPlanCodes($activePlans);
			$output->writeln("\n-- Updating $updatedBusinessCodes Business Promo Codes: Completed");

			// Customer Promo Codes
			$codes = $this->em->getRepository("DhiUserBundle:PromoCode")->reAssignPlanCodes($activePlans);
			$output->writeln("\n-- Updating $codes Customer Promo Codes: Completed");
		}

		$output->writeln("\n\n####### End Cron #######\n");
	}

	private function getPackages(){
		$allPackages = array();

		// Packages
		$packages = $this->em->getRepository("DhiAdminBundle:Package")->getPromoPackages();
		$allPackages['package'] = array_keys($packages);

		// Bundles
		$bundles = $this->em->getRepository("DhiAdminBundle:Bundle")->getBundlePlan();
		$allPackages['bundle'] = array_keys($bundles);
		return $allPackages;
	}
}