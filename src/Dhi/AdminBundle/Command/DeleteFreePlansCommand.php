<?php
namespace Dhi\AdminBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class DeleteFreePlansCommand extends ContainerAwareCommand {

	private $output;

	protected function configure() {
		$this->setName('dhi:delete-free-plans')->setDescription('Delete purchase details of free plans.');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("\n####### Delete purchase details of free plans Cron at: " . date('M j H:i') . " #######\n");

		$em = $this->getContainer()->get('doctrine')->getManager();

		$condition = array("10000", "10001");

		$service = $em->getRepository("DhiUserBundle:Service")->findOneBy(array("name" => 'IPTV'));
		$freeUserPlans = $em->getRepository("DhiUserBundle:UserService")->getPlansByPackageId($condition, $service, true);
		$toFlush = false;
		$selevisionService = $this->getContainer()->get('selevisionService');

		if($freeUserPlans) {
			foreach ($freeUserPlans as $key => $plan) {
				$user = $plan->getUser();
				$output->writeln('Removing plan for user: ' . $user->getUserName());

				// Remove Free plan from Selevision
				$wsOfferParam = array();
				$wsOfferParam['cuLogin'] = $user->getUserName();
				$wsOfferParam['offer'] = $plan->getPackageId();
				$wsResponse = $selevisionService->callWSAction('unsetCustomerOffer', $wsOfferParam);

				if($wsResponse['status'] == 1) {
					$servicePurchase = $plan->getServicePurchase();
					$purchaseOrder   = $plan->getPurchaseOrder();

					$em->remove($plan);
					$em->remove($servicePurchase);
					$em->remove($purchaseOrder);

					$toFlush = true;
				}
			}
		}

		if($toFlush) {
			$em->flush();
		}

		$output->writeln("\n####### End Cron #######\n");
	}
}
