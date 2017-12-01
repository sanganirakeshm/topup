<?php
namespace Dhi\AdminBundle\Command;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use \Doctrine\DBAL\DBALException;

class DeleteNoPlanUserCommand extends ContainerAwareCommand {

	private $output;

	protected function configure() {
		$this->setName('dhi:delete-no-plan-users')->setDescription('Delete users who have not purchased any plan.');
	}

	public function execute(InputInterface $input, OutputInterface $output) {
		$output->writeln("\n####### Delete users who have not purchased any plan Cron at: " . date('M j H:i') . " #######\n");
		$em = $this->getContainer()->get('doctrine')->getManager();
		$conn = $this->getContainer()->get('database_connection');

		$objLocation = $em->getRepository("DhiAdminBundle:Setting")->findOneBy(array("name" => 'delete_user_cron_service_location'));
		$isEmptyLocation = false;

		if($objLocation) {
			$strLocation = $objLocation->getValue();

			if(!empty($strLocation)) {
				$arrLocation = explode(',', trim($strLocation));
				$arrLocation = array_map('trim', $arrLocation);
				$isEmptyLocation = true;
			}
		}

		if($isEmptyLocation == true) {
			$users = $em->getRepository("DhiUserBundle:User")->getLocationWiseCustomers($arrLocation);
			$toFlush = false;

			if($users) {
				foreach ($users as $user) {
					try {
						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserService', 'us');
						$qb->where('us.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserCreditLog', 'ucl');
						$qb->where('ucl.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserCredit', 'uc');
						$qb->where('uc.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiAdminBundle:SetTopBox', 'stb');
						$qb->where('stb.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserAradialPurchaseHistory', 'uh');
						$qb->where('uh.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiServiceBundle:ServiceApiErrorLog', 'uh');
						$qb->where('uh.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiServiceBundle:ServiceActivationFailure', 'uaf');
						$qb->where('uaf.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:PromoCustomer', 'pc');
						$qb->where('pc.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$promoCodes = $em->getRepository("DhiUserBundle:PromoCustomer")->findBy(array('user' => $user));

						if ($promoCodes) {
							foreach ($promoCodes as $promoCode) {
								$promocodeId = $promoCode->getPromoCodeId();
								$em->remove($promoCode);

								if($promocodeId){
									$em->remove($promocodeId);
								}
							}
						}

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiServiceBundle:Milstar', 'm');
						$qb->where('m.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiAdminBundle:InformationLog', 'il');
						$qb->where('il.fromUser = :user');
						$qb->setParameter('user', $user);
						$qb->orWhere('il.toUser = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiAdminBundle:DiscountCodeCustomer', 'dcc');
						$qb->where('dcc.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:CustomerCompensationLog', 'ccl');
						$qb->where('ccl.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$compensations = $user->getCompensations();

						if($compensations) {
							foreach ($compensations as $compensation) {
								$em->remove($compensation);
							}
						}

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiServiceBundle:BillingAddress', 'ba');
						$qb->where('ba.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiServiceBundle:ServicePurchase', 'sp');
						$qb->where('sp.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiServiceBundle:PurchaseOrder', 'po');
						$qb->where('po.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiServiceBundle:PaypalCheckout', 'pc');
						$qb->where('pc.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$query = $em->createQuery("DELETE DhiUserBundle:UserActivityLog ul WHERE ul.user = '".$user->getUsername()."'");
						$query->execute();

						$query = $em->createQuery("DELETE DhiAdminBundle:UserIsp us WHERE us.UserID = '".$user->getUsername()."'");
						$query->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserMacAddress', 'uma');
						$qb->where('uma.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserLoginLog', 'ull');
						$qb->where('ull.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserServiceSettingLog', 'ull');
						$qb->where('ull.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$qb = $em->createQueryBuilder();
						$qb->delete('DhiUserBundle:UserServiceSetting', 'ull');
						$qb->where('ull.user = :user');
						$qb->setParameter('user', $user);
						$qb->getQuery()->execute();

						$em->flush();
	        	$conn->delete('dhi_user', array('id' => $user->getId()));
						$toFlush = true;
						$output->writeln("Deleted user: " . $user->getUsername());
					} catch (DBALException $e) {
						$output->writeln("Could not delete user: " . $user->getUsername());
					}
				}
			}

			if($toFlush) {
				$em->flush();
			}
		}

		$output->writeln("\n####### End Cron #######\n");
	}
}
