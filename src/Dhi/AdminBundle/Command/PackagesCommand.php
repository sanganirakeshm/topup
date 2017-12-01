<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Dhi\AdminBundle\Entity\Package;
use Dhi\AdminBundle\Entity\Bundle;
use Dhi\AdminBundle\Entity\Channel;
use Dhi\AdminBundle\Entity\ChannelMaster;
use Doctrine\ORM\Query\ResultSetMapping;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Entity\AddonsMaster;

class PackagesCommand extends ContainerAwareCommand {

	private $output;

	protected function configure() {
		$this->setName('dhi:get-packages')->setDescription('Get packages from selevision and aradial api');
	}

	public function execute(InputInterface $input, OutputInterface $output) {

		$bundlePackages = $ispBundlePackages = $iptvBundlePackages = array();
		$output->writeln("\n####### Start Service Package Cron at " . date('M j H:i') . " #######\n");

		$em = $this->getContainer()->get('doctrine')->getManager();
		$connection = $em->getConnection();

		$queryChannel = $em->createQuery('DELETE DhiAdminBundle:Bundle');
		$queryChannel->execute();

		$queryChannel = $em->createQuery('DELETE DhiAdminBundle:Channel');
		$queryChannel->execute();

		$arrOldPackages = $this->getOldPackages($em);

		$querPackage = $em->createQuery('DELETE DhiAdminBundle:Package');
		$querPackage->execute();

		$sqlChannel = 'ALTER TABLE channel AUTO_INCREMENT = 1';
		$statementChannel = $connection->prepare($sqlChannel);
		$statementChannel->execute();

		// $output->writeln("\n####### Truncate Channel #######\n");

		$sqlPackage = 'ALTER TABLE package AUTO_INCREMENT = 1';
		$statementPackage = $connection->prepare($sqlPackage);
		$statementPackage->execute();

		$sqlBundle = 'ALTER TABLE bundle AUTO_INCREMENT = 1';
		$statementBundle = $connection->prepare($sqlBundle);
		$statementBundle->execute();

		// $output->writeln("\n####### Truncate Package #######\n");

		#################### Store IPTV Package ######################################
		$selevisionService = $this->getContainer()->get('selevisionService');
		$packageArr = $selevisionService->getAllPackageDetails();

		$ispPackageCount = 0;
		$iptvPackageCount = 0;
		$premiumPackageCount = 0;

		if (!empty($packageArr)) {
			$arrChannel = $em->getRepository('DhiAdminBundle:ChannelMaster')->getChannels();
			$arrAddons = $em->getRepository('DhiAdminBundle:AddonsMaster')->getAddons();

			foreach ($packageArr as $key => $package) {
				if ($key != "") {
					$key = trim($key);
					$packageKeyArr = explode('-', $key);

					if (!empty($packageKeyArr) && $packageKeyArr && count($packageKeyArr) > 4) {
						$isDeers = 0;

						if (count($packageKeyArr) >= 5 && trim($packageKeyArr[4]) == 'D') {
							$isDeers = 1;
						}

						$packageType = '';

						if ($packageKeyArr[2] == 'ADDON') {
							$packageType = 'PREMIUM';
						} else {
							$packageType = 'IPTV';
						}

						//$output->writeln("\n####### Add Package #######\n");

						$objLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getServiceLocation($packageKeyArr);

						if ($objLocation) {
							// save package
							$objPackage = new Package();
							$packageName = str_replace(":", " ", $packageKeyArr[3]);
							if (!empty($packageKeyArr[5]) && ($packageKeyArr[5] == 'BDL' || $packageKeyArr[5] == 'ABDL') && $packageKeyArr[2] != 'ADDON') {
								$iptvBundlePackages[$key] = $package;
								$objPackage->setIsBundlePlan(true);
								$packageName = str_replace("Bundle", "", $packageName);
							}

							if ($packageType == 'IPTV') {
								$iptvPackageCount++;
							} else {
								$premiumPackageCount++;
							}

							$isPartner = (
								(!empty($packageKeyArr[4]) && strtoupper($packageKeyArr[4]) == 'PAR') ||
								(!empty($packageKeyArr[5]) && strtoupper($packageKeyArr[5]) == 'PAR') ||
								(!empty($packageKeyArr[6]) && strtoupper($packageKeyArr[6]) == 'PAR') ? true : false);

							$isExpired = (
								(!empty($packageKeyArr[4]) && $packageKeyArr[4] == 'EXP') || 
								(!empty($packageKeyArr[5]) && $packageKeyArr[5] == 'EXP') || 
								(!empty($packageKeyArr[6]) && $packageKeyArr[6] == 'EXP') || 
								(!empty($packageKeyArr[7]) && $packageKeyArr[7] == 'EXP')) ? true : false;

							$isEmployee = false;
							if (
								(!empty($packageKeyArr[4]) && $packageKeyArr[4] == 'EMP') || 
								(!empty($packageKeyArr[5]) && $packageKeyArr[5] == 'EMP') ||
								(!empty($packageKeyArr[6]) && $packageKeyArr[6] == 'EMP') ||
								(!empty($packageKeyArr[7]) && $packageKeyArr[7] == 'EMP') ||
								(!empty($packageKeyArr[8]) && $packageKeyArr[8] == 'EMP')
							) {
								$isEmployee = true;
							}

							$isPromotion = false;
							if (
								(!empty($packageKeyArr[4]) && $packageKeyArr[4] == 'PROMO') ||
								(!empty($packageKeyArr[5]) && $packageKeyArr[5] == 'PROMO') ||
								(!empty($packageKeyArr[6]) && $packageKeyArr[6] == 'PROMO') ||
								(!empty($packageKeyArr[7]) && $packageKeyArr[7] == 'PROMO') ||
								(!empty($packageKeyArr[8]) && $packageKeyArr[8] == 'PROMO') ||
								(!empty($packageKeyArr[9]) && $packageKeyArr[9] == 'PROMO')
							) {
								$isPromotion = true;
							}

							$objPackage->setPackageId($package['packageId']);
							$objPackage->setPackageName($packageName);
							$objPackage->setAmount($package['packagePrice'] ? $package['packagePrice'] : '0');
							$objPackage->setPackageType($packageType);
							$objPackage->setStatus(1);
							$objPackage->setBandwidth($package['bandwidth'] ? $package['bandwidth'] : 10);
							$objPackage->setValidity($package['validity'] ? $package['validity'] : 30);
							$objPackage->setTotalChannels($package['packageChannelCount']);
							$objPackage->setServiceLocation($objLocation);
							$objPackage->setIsDeers($isDeers);
							$objPackage->setIsForPartner($isPartner);
							$objPackage->setIsExpired($isExpired);
							$objPackage->setIsEmployee($isEmployee);
							$objPackage->setIsPromotionalPlan($isPromotion);

							$em->persist($objPackage);
							$em->flush();

							if ($objPackage->getId()) {

								if (!empty($package['packageChannels'])) {
									// $output->writeln("\n####### Add Channel #######\n");

									foreach ($package['packageChannels'] as $channel) {
										if ($channel != "") {
											$objChannel = new Channel();
											$objChannel->setPackage($objPackage);
											$objChannel->setName($channel);
											$objChannel->setStatus(1);
											$em->persist($objChannel);

											if (!in_array($channel, $arrChannel)) {
												$objChannelMaster = new ChannelMaster();
												$objChannelMaster->setName($channel);
												$em->persist($objChannelMaster);
												$arrChannel[] = $channel;
											}
											$em->flush();
										}
									}
								}
                                                                
                                                                ############## Addons Master start here #########################################
                                                                if (!in_array($packageName, $arrAddons)) {
                                                                    $objAddonsMaster = new AddonsMaster();
                                                                    $objAddonsMaster->setName($packageName);
                                                                    $em->persist($objAddonsMaster);
                                                                    $em->flush();
                                                                    $arrAddons[] = $packageName;
                                                                }
                                                                ############## Addons Master end here #########################################
							}

							$bundlePackages['iptvKeys'][] = $key;
							$bundlePackages['iptv'][$key] = $objPackage->getId();
							// }
						}
					}
				}
			}

			if ($iptvPackageCount > 0 || $premiumPackageCount > 0) {
				$output->writeln("\n####### IPTV Packages added successfully #######\n");
			} else {
				$output->writeln("\n####### IPTV Packages not found #######\n");
			}
		} else {
			$output->writeln("\n####### IPTV Packages not found #######\n");
		}
		############## IPTV Package end here #########################################

		#################### Store ISP Package #########################################
		$offers = $this->getContainer()->get('aradial')->getOffer();
		$currentDate = new DateTime();
                
              	if ($offers['status'] == 1) {
                    
			if (isset($offers['package'])) {
				foreach ($offers['package'] as $val) {
					$offerId = $val['OfferId'];
					$name = $val['Name'];
					$ispPackageKeyArr = explode('-', $name);
					$description = $val['Description'];
					$expirationTime = $val['ExpirationTime'];
					$saleExpirationDate = trim($val['SaleExpirationDate']);
					$price = $val['Price'];
					$isAddOn = 0;
					$isDeers = 0;
					$isExpired = false;

					// Check whether plan in expired or not
					if (!empty($saleExpirationDate)) {
						$saleExpireDateObj = new DateTime($saleExpirationDate);
						if ($saleExpireDateObj < $currentDate) {
							$isExpired = true;
							continue;
						}
					}

					// Get bandwidth
					$bandwidth = 0;
					preg_match('!\d+k+!', $description, $matches);

					if (count($matches) > 0) {
						$bandwidth = str_replace('k', '', $matches[0]);
					}
					// End here

					$pos = strpos($name, "Add On");
					if ($pos !== false) {
						$isAddOn = 1;
					}

					if ($isAddOn == 0 && $isExpired == false) {
						// Explode ISP package Name
						$serviceLocation = '';
						$packageName = '';
                                               
						$nameExplode = explode('-', $name);

						if (isset($nameExplode[1])) {
							$serviceLocation = $nameExplode[1];
						}

						if (isset($nameExplode[2])) {
							$packageName = $nameExplode[2];
						}

						$objLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getPackageServiceLocation($serviceLocation);

						if ($objLocation) {
							// save package
							$objPackage = new Package();

							if (!empty($ispPackageKeyArr['4']) && ($ispPackageKeyArr['4'] == 'BDL' || $ispPackageKeyArr['4'] == 'ABDL')) {
								$ispBundlePackages[trim($name)] = $val;
								$objPackage->setIsBundlePlan(true);
							} //else {

							$isHourlyPlan = false;
							$hourlyValidity = 0;
							if(!empty($ispPackageKeyArr['3'])){
								$isHourly = strpos($ispPackageKeyArr['3'], 'H');
								if ($isHourly !== false) {
									$hourlyValidity = str_replace("H", "", $ispPackageKeyArr['3']);
									if (is_numeric($hourlyValidity)) {
										$isHourlyPlan = true;
									}
								}

							}

							$isPromotion = false;
							if (
								(!empty($ispPackageKeyArr['3']) && $ispPackageKeyArr['3'] == 'PROMO') || 
								(!empty($ispPackageKeyArr['4']) && $ispPackageKeyArr['4'] == 'PROMO') || 
								(!empty($ispPackageKeyArr['5']) && $ispPackageKeyArr['5'] == 'PROMO') || 
								(!empty($ispPackageKeyArr['6']) && $ispPackageKeyArr['6'] == 'PROMO')
								) {
								$isPromotion = true;
							}

							$isEmployee = false;
							if (
								(!empty($ispPackageKeyArr['3']) && $ispPackageKeyArr['3'] == 'EMP') || 
								(!empty($ispPackageKeyArr['4']) && $ispPackageKeyArr['4'] == 'EMP') || 
								(!empty($ispPackageKeyArr['5']) && $ispPackageKeyArr['5'] == 'EMP') 
								) {
								$isEmployee = true;
							}

							$ispPackageCount++;

							$objPackage->setPackageId($offerId);
							$objPackage->setPackageName($packageName);
							$objPackage->setAmount($price);
							$objPackage->setPackageType('ISP');
							$objPackage->setStatus(1);
							$objPackage->setBandwidth($bandwidth);
							$objPackage->setValidity( ($isHourlyPlan == true ? $hourlyValidity : $expirationTime));
							$objPackage->setTotalChannels(0);
							$objPackage->setServiceLocation($objLocation);
							$objPackage->setIsDeers($isDeers);
							$objPackage->setDescription($description);
                            $objPackage->setPackageNamespace($name);
							$objPackage->setIsAddons($isAddOn);
							$objPackage->setIsHourlyPlan($isHourlyPlan);
							$objPackage->setIsEmployee($isEmployee);

							$objPackage->setIsPromotionalPlan($isPromotion);
							$em->persist($objPackage);
							$em->flush();

							if (!empty($arrOldPackages[$offerId])) {
								$validity = ($isHourlyPlan == true ? $hourlyValidity : $expirationTime);

								if(
									$arrOldPackages[$offerId]['bandwidth'] == $bandwidth &&
									$arrOldPackages[$offerId]['validity'] == $validity &&
									$arrOldPackages[$offerId]['amount'] == $price
								){
									unset($arrOldPackages[$offerId]);
								}
							}

							$bundlePackages['ispKeys'][] = trim($name);
							$bundlePackages['isp'][trim($name)] = $objPackage->getId();
							// }
						}
					}
				}

				if ($ispPackageCount > 0) {
					$output->writeln("\n####### ISP Packages added successfully #######\n");
				} else {
					$output->writeln("\n####### ISP Packages not found #######\n");
				}
			} else {
				$output->writeln("\n####### ISP Packages not found #######\n");
			}
		}

		if (!empty($arrOldPackages)) {
			$sql = '';
			foreach ($arrOldPackages as $key => $pacakge) {
				$sql .= "pt.packageId = '" . $key . "' OR ";
			}

			$sql = rtrim($sql, 'OR ');
		}

		$deleteQuery = "DELETE DhiAdminBundle:PackageWiseTikiLivePlan pt WHERE pt.packageId NOT IN (SELECT p.packageId FROM DhiAdminBundle:Package p)".(!empty($sql) ? " OR ($sql)" : '');
		$query = $em->createQuery($deleteQuery);
		$query->execute();

		// send package notification email
		/*
			if ($ispPackageCount == 0 || $iptvPackageCount == 0) {
				$this->sendMailPackageNotify($ispPackageCount, $iptvPackageCount, $premiumPackageCount);
			}
        */
		#################### ISP Package End Here #########################################

		#################### Bundles Start Here ##################################
		$bundleCount = 0;

		if (isset($iptvBundlePackages)) {
			foreach ($iptvBundlePackages as $iptvKey => $iptv) {
				$iptvKeyArr = explode('-', $iptvKey);
				$isIptvAutoBundle = ($iptvKeyArr[5] == "ABDL") ? true : false;
				
				// For actual plan
				if (in_array($iptvKeyArr[5], array('BDL', 'ABDL'))) {
					unset($iptvKeyArr[5]);
				}

				if (isset($iptvKeyArr[6])) {
					unset($iptvKeyArr[6]);
				}

				$iptvBundleName = str_replace(':', ' ', $iptvKeyArr[3]);
				$iptvKeyArr[3] = str_replace(':Bundle', '', $iptvKeyArr[3]);
				$actualIPTVKey = implode($iptvKeyArr, '-');
				// End for actual plan

				$key = $iptvKey; //implode($iptvKeyArr, '-');

				if (in_array($key, $bundlePackages['iptvKeys'])) {
					if (!empty($bundlePackages['iptv'][$actualIPTVKey]) && !empty($bundlePackages['iptv'][$key])) {

						$iptvId = $bundlePackages['iptv'][$key];
						$iptvPlan = $em->getRepository("DhiAdminBundle:Package")->find($iptvId);
						$actualIPTVPlan = $em->getRepository("DhiAdminBundle:Package")->find($bundlePackages['iptv'][$actualIPTVKey]);

						foreach ($ispBundlePackages as $ispKey => $isp) {
							$ispKeyArr = explode('-', $ispKey);
							$isIspAutoBundle = ($ispKeyArr[4] == "ABDL") ? true : false;

							// For Actual plan
							if (in_array($ispKeyArr[4], array('BDL', 'ABDL'))) {
								unset($ispKeyArr[4]);

								if (isset($ispKeyArr[6])) {
									unset($ispKeyArr[6]);
								}
							}

							$actualISPKey = implode($ispKeyArr, '-');
							// For Actual plan

							$ispPackagekey = $ispKey; //implode($ispKeyArr, '-');

							if (in_array($ispPackagekey, $bundlePackages['ispKeys']) && in_array($actualISPKey, $bundlePackages['ispKeys'])) {
								$ispId = $bundlePackages['isp'][$ispPackagekey];
								$ispPlan = $em->getRepository("DhiAdminBundle:Package")->find($ispId);
								$actualISPPlan = $em->getRepository("DhiAdminBundle:Package")->find($bundlePackages['isp'][$actualISPKey]);

								if ($iptvPlan && $actualIPTVPlan && $ispPlan && $actualISPPlan) {
									$order = !empty($iptvKeyArr[6]) ? $iptvKeyArr[6] : 0;
									$totalAmount = $actualIPTVPlan->getAmount() + $actualISPPlan->getAmount();
									$bundleAmount = $iptv['packagePrice'] + $isp['Price'];
									$discountAmount = $totalAmount - $bundleAmount;
									$discountPer = ($discountAmount * 100) / $totalAmount;
									$description = $isp['Description'];
									$packageName = '';
									$nameExplode = explode('-', $isp['Name']);

									if (isset($nameExplode[1])) {
										$serviceLocation = $nameExplode[1];
									}

									if (isset($nameExplode[2])) {
										$packageName = $nameExplode[2];
									}

									$actualBandwidth = $actualISPPlan->getBandwidth();
									$bandwidth = $ispPlan->getBandwidth();

									if (empty($bandwidth)) {
										$ispPlan->setBandwidth($actualBandwidth);
										$em->persist($ispPlan);
										$em->flush();
									}
									$isEmployee = (($iptvPlan->getIsEmployee() == 1 && $ispPlan->getIsEmployee() == 1) ? 1 : 0);
									$isAutoBundle = ($isIspAutoBundle == true && $isIptvAutoBundle == true) ? true : false;

									$objBundle = new Bundle();
									$objBundle->setIptv($iptvPlan);
									$objBundle->setIsp($ispPlan);
									$objBundle->setOrderId($order);
									$objBundle->setTotalPackageAmount($totalAmount);
									$objBundle->setAmount($bundleAmount);
									//$objBundle->setPackageType($packageType);
									$objBundle->setBundleId($iptv['packageId'] . $isp['OfferId']);
									$objBundle->setIptvAmount($actualIPTVPlan->getAmount());
									$objBundle->setIspAmount($actualISPPlan->getAmount());
									$objBundle->setRegularIptv($actualIPTVPlan);
									$objBundle->setRegularIsp($actualISPPlan);
									$objBundle->setStatus(1);
									$objBundle->setBundleName("Bundle savings");
									$objBundle->setDisplayBundleName($description);
									$objBundle->setDescription("Bundle for " . $actualIPTVPlan->getPackageName() . " IPTV & " . $ispPlan->getValidity() . " " . (($ispPlan->getValidity() == 1) ? "day" : 'days') . " " . $ispPlan->getPackageName() . " internet (up to " . $ispPlan->getBandwidth() . " kbps)");
									$objBundle->setDiscount($discountPer);
									$objBundle->setIsEmployee($isEmployee);
                                                                        $objBundle->setIsAutoBundle($isAutoBundle);

									$em->persist($actualIPTVPlan);
									$em->persist($actualISPPlan);
									$em->persist($objBundle);
									$em->flush();

									$bundleCount++;
								}
							}
						}
					}
				}
			}

			if ($bundleCount > 0) {
				$output->writeln("\n####### Bundles added successfully #######\n");
			} else {
				$output->writeln("\n####### Bundles not found #######\n");
			}
		} else {
			$output->writeln("\n####### Bundles not found #######\n");
		}
		#################### Bundles End Here ##################################

		$output->writeln("\n####### End Cron #######\n");
	}

	public function sendMailPackageNotify($ispPackageCount, $iptvPackageCount, $premiumPackageCount) {
		$fromEmail = $this->getContainer()->getParameter('fos_user.registration.confirmation.from_email');
		$servicePackageNotificationEmail = $this->getContainer()->getParameter('service_package_notification');

		$body = $this->getContainer()->get('templating')->renderResponse('DhiUserBundle:Emails:service_package_notify.html.twig', array('iptvPackageCount' => $iptvPackageCount, 'ispPackageCount' => $ispPackageCount, 'premiumPackageCount' => $premiumPackageCount));

		$service_package_email = \Swift_Message::newInstance()
			->setSubject('ExchangeVUE - Service Package Notification')
			->setFrom($fromEmail)
			->setTo($servicePackageNotificationEmail)
			->setBody($body->getContent())
			->setContentType('text/html');

		if ($this->getContainer()->get('mailer')->send($service_package_email)) {
			return true;
		}
	}

	private function getOldPackages($em){
		$objOldPackages = $em->getRepository("DhiAdminBundle:Package")->findAll();
		$arrOldPackages = array();
		if ($objOldPackages) {
			foreach ($objOldPackages as $objPackage) {
				$arrOldPackages[$objPackage->getPackageId()] = array(
					'bandwidth' => $objPackage->getBandwidth(),
					'validity'  => $objPackage->getValidity(),
					'amount'    => $objPackage->getAmount()
				);
			}
		}

		return $arrOldPackages;
	}
}
