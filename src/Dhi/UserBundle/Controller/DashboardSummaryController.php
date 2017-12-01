<?php

namespace Dhi\UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use FOS\UserBundle\Mailer\MailerInterface;
use Dhi\UserBundle\Form\Type\AccountFormType;
use Dhi\UserBundle\Form\Type\ChangePasswordFormType;
use Dhi\UserBundle\Form\Type\AccountSettingFormType;
use Dhi\UserBundle\Form\Type\AccountTypeFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\Service;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\AdminBundle\Entity\Credit;
use Dhi\UserBundle\Entity\UserCredit;
use Dhi\UserBundle\Entity\UserCreditLog;

class DashboardSummaryController extends Controller {

	protected $container;
	protected $em;
	protected $session;
	protected $securitycontext;
	protected $request;
	protected $UserLocationWiseService;

	public function __construct($container) {

		$this->container = $container;

		$this->UserLocationWiseService = $container->get('UserLocationWiseService');
		$this->em = $container->get('doctrine')->getManager();
		$this->session = $container->get('session');
		$this->securitycontext = $container->get('security.context');
		$this->request = $container->get('request');
		$this->ActivityLog = $container->get('ActivityLog');
	}

	public function getUserServiceSummary($type = 'user', $user = null, $isTvod = false) {

		if ($type == 'admin') {

			$sessionId = $this->session->get('adminSessionId');
		} else {

			$sessionId = $this->session->get('sessionId');
		}

		if ($isTvod) {
			$LocationService = $this->UserLocationWiseService->getUserServiceLocationFromAdmin($user);
		} else {
			if ($type == 'admin' && $user) {
				$LocationService = $this->UserLocationWiseService->getUserServiceLocationFromAdmin($user);
			} else {

				$user = $this->securitycontext->getToken()->getUser();
				$LocationService = $this->UserLocationWiseService->getUserLocationService();
			}
		}

		$accountSummary = array();

		$accountSummary['Cart'] = '';
		$accountSummary['CartAvailable'] = 0;
		$accountSummary['CartIPTVPackageId'] = array();
		$accountSummary['CartAddOnPackageId'] = array();
		$accountSummary['CartISPPackageId'] = array();
		$accountSummary['CartTVODPackageId'] = array();
		$accountSummary['CartBundlePackageId'] = array();
		$accountSummary['CartBundleServicePurchaseId'] = array();
		$accountSummary['CartIPTVServicePurchaseId'] = array();
		$accountSummary['CartAddOnServicePurchaseId'] = array();
		$accountSummary['CartISPServicePurchaseId'] = array();
		$accountSummary['CartTVODServicePurchaseId'] = array();
		$accountSummary['CartBundleAvailable'] = 0;
		$accountSummary['IsBundleAvailabledInCart'] = 0;
		$accountSummary['IsISPAvailabledInCart'] = 0;
		$accountSummary['IsAddOnAvailabledInCart'] = 0;
		$accountSummary['IsIPTVAvailabledInCart'] = 0;
		$accountSummary['IsTVODAvailabledInCart'] = 0;
		$accountSummary['ServiceAvailableInCart'] = array();
		$accountSummary['TotalCartAmount'] = 0;
		$accountSummary['TotalbundleDiscount'] = 0;
		$accountSummary['TotalPromotionOff'] = 0;
		$accountSummary['TotalPromotionPer'] = 0;
		$accountSummary['Purchased'] = '';
		$accountSummary['PurchasedAvailable'] = 0;
		$accountSummary['PurchasedIPTVPackageId'] = array();
		$accountSummary['PurchasedBUNDLEPackageId'] = array();
		$accountSummary['PurchasedAddOnPackageId'] = array();
		$accountSummary['PurchasedISPPackageId'] = array();
		$accountSummary['ServiceAvailableInPurchased'] = array();
		$accountSummary['IsISPAvailabledInPurchased'] = 0;
		$accountSummary['IsTVODAvailabledInPurchased'] = 0;
		$accountSummary['IsAddOnAvailabledInPurchased'] = 0;
		$accountSummary['IsIPTVAvailabledInPurchased'] = 0;
		$accountSummary['IsBundleAvailabledInPurchased'] = 0;
		$accountSummary['TotalPurchasedAmount'] = 0;
		$accountSummary['AvailableServicesOnLocation'] = array();
		$accountSummary['IPTVButtonEnabled'] = 1;
		$accountSummary['IsCreditAvailabledInCart'] = 0;
		$accountSummary['CartCreditId'] = array();
		$accountSummary['serviceLocationId'] = 0;
		$accountSummary['IsDeersRequiredPlanAdded'] = 0;
		$accountSummary['ISPPackageValidity'] = '';
		$accountSummary['IsShowDeactiveIPTVMessageAsPerMac'] = 0;
		$accountSummary['isServiceLocationChanged'] = 0;
		$accountSummary['isSiteChanged'] = 0;
		$accountSummary['isPartnerPromocodeApplied'] = 0;
		$accountSummary['discountCodeAmount'] = 0;
		$accountSummary['isBusinessPromocodeApplied'] = 0;
		$accountSummary['discountCodeRate'] = 0;
		$accountSummary['isDiscountCodeApplied'] = 0;
		$accountSummary['paypalCredential'] = array();
		$accountSummary['paypalCredentialKey'] = 0;
		$accountSummary['isEmployee'] = 0;
		$accountSummary['employeeDefaultValidity'] = 0;
		$accountSummary['isPromotionAvailable'] = 0;
		$accountSummary['promotion'] = array();
		$accountSummary['inAppCodeDiscount'] = 0;
		$accountSummary['ISPExtendId'] = 0;

		if (array_key_exists('IPTV', $LocationService) && array_key_exists('ISP', $LocationService) && array_key_exists('TVOD', $LocationService)) {

			$accountSummary['IPTVButtonEnabled'] = 0;
		}


		if ($LocationService) {
			foreach ($LocationService as $key => $val) {

				if ($key == 'IPTV' || $key == 'ISP' || $key == 'TVOD') {
					$accountSummary['AvailableServicesOnLocation'][] = strtoupper($val['ServiceName']);
				} else if ($key == 'BUNDLE') {
					$accountSummary['AvailableServicesOnLocation'][] = 'BUNDLE';
				}
			}
			$accountSummary['serviceLocationId'] = $LocationService['serviceLocationId'];
		}

		// Check for employee
		$isEmployee = $user->getIsEmployee();
		$accountSummary['isEmployee'] = $isEmployee;
		if ($isEmployee) {
			$objValiditySetting = $this->em->getRepository('DhiAdminBundle:Setting')->findOneByName('employee_purchase_validity');
			if ($objValiditySetting) {
				$accountSummary['employeeDefaultValidity'] = $objValiditySetting->getValue();
			}
		}

		// For multiple paypal credential based on location
		$paypalCredentials = $this->container->getParameter("paypal_credentials");
		$keyFound = false;

		if (isset($paypalCredentials)) {
			if ($accountSummary['serviceLocationId']) {
				if (!empty($user) && method_exists($user, 'getGeoLocationCountry')) {

						$paypalServiceLocation = $this->em->getRepository("DhiAdminBundle:ServiceLocation")->find($accountSummary['serviceLocationId']);

						if ($paypalServiceLocation) {
							$key = $this->em->getRepository("DhiAdminBundle:paypalCredentials")->findOneBy(array('serviceLocations' => $paypalServiceLocation));

							if ($key) {
								if (isset($paypalCredentials[$key->getPaypalId()]['paypal_username'])) {
									$accountSummary['paypalCredential'] = $paypalCredentials[$key->getPaypalId()];
									$accountSummary['paypalCredentialKey'] = $key->getPaypalId();
									$keyFound = true;
								}
							}
						}
				}
			}

			if ($keyFound == false) {
				foreach ($paypalCredentials as $accountKey => $account) {
					if ($account['is_default'] == 1) {
						$accountSummary['paypalCredential'] = $account;
						$accountSummary['paypalCredentialKey'] = $accountKey;
						continue;
					}
				}
			}
		}

		// Promotion

		if (!empty($accountSummary['serviceLocationId'])) {
			$objpromotionBanner = $this->em->getRepository('DhiAdminBundle:Promotion')->getActiveCodeData(array($accountSummary['serviceLocationId']));

			if (!empty($objpromotionBanner[0])) {
				$accountSummary['isPromotionAvailable'] = $objpromotionBanner[0]['id'];
				$accountSummary['promotion'] = $objpromotionBanner[0];
			}
		}

		//Cart Purchase data
		$cartPurchaseData = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->getCartPurchaseItem($user, $sessionId);
		if ($cartPurchaseData) {

			$accountSummary['CartAvailable'] = 1;

			foreach ($cartPurchaseData as $cartPurchase) {

				if ($cartPurchase->getService() && $cartPurchase->getIsCredit() != 1) {
					// if (in_array(strtoupper($cartPurchase->getService()->getName()), $accountSummary['AvailableServicesOnLocation'])) {

					$discountCodeAmount = $totalbundleDiscount = $totalCartAmount = 0;

					$serviceId = $cartPurchase->getService()->getId();
					$serviceName = strtoupper($cartPurchase->getService()->getName());
					$isAddOn = $cartPurchase->getIsAddon();
					$packageName = $cartPurchase->getPackageName();
					$packageId = $cartPurchase->getPackageId();
					$amount = $cartPurchase->getActualAmount();
					$payableAmount = $cartPurchase->getPayableAmount();
					$discountPercentage = $cartPurchase->getDiscountRate();
					$discountAmount = $cartPurchase->getTotalDiscount();
					$finalCost = $cartPurchase->getFinalCost();
					$bundleId = $cartPurchase->getBundleId();
					$bundleDiscount = $cartPurchase->getBundleDiscount();
					$purchaseType = $cartPurchase->getpurchaseType();
					$bundleName = $cartPurchase->getBundleName();
					$dispBundleName = $cartPurchase->getDisplayBundleName();
					$validityType = $cartPurchase->getvalidityType();
					$discountCodeRate = $cartPurchase->getDiscountCodeRate();
					$discountCodeAmount1 = $cartPurchase->getDiscountCodeAmount();
					$promotionDiscountAmount = $cartPurchase->getPromotionDiscountAmount();
					$promotionDiscountPer = $cartPurchase->getPromotionDiscountPer();
					$promotion = $cartPurchase->getPromotion();

					if (empty($validityType)) {
						$validityType = "DAYS";
					}

					$package = $this->em->getRepository("DhiAdminBundle:Package")->findOneBy(array('packageId' => $packageId));
					if ($package) {
						$serviceLocation = $package->getServiceLocation()->getId();
					} else {
						$description = '';
						$serviceLocation = 0;
					}

					$tempArr = array();
					$tempArr['serviceName'] = $serviceName;
					$tempArr['serviceId'] = $serviceId;
					$tempArr['packageName'] = $packageName;
					$tempArr['packageId'] = $packageId;
					$tempArr['amount'] = $amount;
					$tempArr['actualAmount'] = $amount;
					$tempArr['bandwidth'] = $cartPurchase->getBandwidth();
					$tempArr['finalCost'] = $finalCost;
					$tempArr['payableAmount'] = $payableAmount;
					$tempArr['discountPercentage'] = $discountPercentage;
					$tempArr['discountAmount'] = $discountAmount;
					$tempArr['servicePurchaseId'] = $cartPurchase->getId();
					$tempArr['validity'] = $cartPurchase->getValidity();
					$tempArr['bundleDiscount'] = $bundleDiscount;
					$tempArr['bundleDiscountAmount'] = 0;
					$tempArr['purchaseType'] = $purchaseType;
					$tempArr['bundleId'] = $bundleId;
					$tempArr['bundleName'] = $bundleName;
					$tempArr['displayBundleName'] = $dispBundleName;
					$tempArr['serviceLocation'] = $serviceLocation;
					$tempArr['discountCodeRate'] = $cartPurchase->getDiscountCodeRate();
					$tempArr['discountCodeAmount'] = 0;
					$tempArr['isExtend'] = $cartPurchase->getIsExtend();
					$tempArr['displayBundleDiscount'] = $cartPurchase->getDisplayBundleDiscount();
					$tempArr['validityType'] = $validityType;

					if (strtoupper($serviceName) == 'ISP') {
						$accountSummary['IPTVButtonEnabled'] = 1;
					}

					if (strtoupper($serviceName) == 'TVOD') {
						$accountSummary['TVODButtonEnabled'] = 1;
					}

					array_push($accountSummary['ServiceAvailableInCart'], strtoupper($serviceName));

					if ($isAddOn) {

						$totalCartAmount = $totalCartAmount + $payableAmount;

						if ($discountCodeRate > 0) {
							if ($discountCodeRate < 100) {
								$discountCodeAmount = ((($payableAmount * 100) / (100 - $discountCodeRate)) - $payableAmount);
							} else {
								$discountCodeAmount = (($payableAmount * 100) - $payableAmount);
							}
							$amount = $amount - $discountCodeAmount;
							$tempArr['discountCodeAmount'] = $discountCodeAmount;
							$accountSummary['isDiscountCodeApplied'] = 1;
							$accountSummary['discountCodeRate'] = $discountCodeRate;
						}

						$accountSummary['CartAddOnPackageId'][$cartPurchase->getId()] = $packageId;
						$accountSummary['CartAddOnServicePurchaseId'][$packageId] = $cartPurchase->getId();
						$accountSummary['IsAddOnAvailabledInCart'] = 1;
						$accountSummary['Cart'][strtoupper($serviceName)]['AddOnPack'][] = $tempArr;
					} else {

						if ($tempArr['purchaseType'] == "BUNDLE" && $bundleId != '') {
							$discountAmo = $tempArr['displayBundleDiscount']; // ($amount * $bundleDiscount) / 100;
							$totalbundleDiscount += $discountAmo;
							$tempArr['bundleDiscountAmount'] = $totalbundleDiscount;
							$accountSummary['CartBundlePackageId'][$cartPurchase->getId()] = $bundleId;
							$accountSummary['IsBundleAvailabledInCart'] = 1;
							$accountSummary['Cart']['Bundle']['RegularPack'] = $tempArr;
							$accountSummary['CartBundleServicePurchaseId'][$bundleId] = $cartPurchase->getId();

							$bundlePlan = $this->em->getRepository("DhiAdminBundle:Bundle")->findOneBy(array("bundle_id" => $bundleId));
							if ($bundlePlan) {
								if (strtoupper($serviceName) == 'ISP') {
									$tempArr['actualAmount'] = $bundlePlan->getIspAmount();
								} else if (strtoupper($serviceName) == 'IPTV') {
									$tempArr['actualAmount'] = $bundlePlan->getIptvAmount();
								}
							}
						} else if ($discountPercentage > 0) {
							$amount = $amount - $discountAmount;
						}

						if ($discountCodeRate > 0) {
							if ($discountCodeRate < 100) {
								$discountCodeAmount = ((($payableAmount * 100) / (100 - $discountCodeRate)) - $payableAmount);
							} else {
								$discountCodeAmount = $cartPurchase->getDiscountCodeAmount();
							}
							$amount = $amount - $discountCodeAmount;
							$tempArr['discountCodeAmount'] = $discountCodeAmount;
							$accountSummary['isDiscountCodeApplied'] = 1;
							if ($cartPurchase->getDiscountCodeApplied() == 7) {
								$accountSummary['inAppCodeDiscount'] = $cartPurchase->getDiscountCodeAmount();
							}

							$accountSummary['discountCodeRate'] = $discountCodeRate;
						} else if ($discountCodeAmount1 > 0) {
							$discountCodeAmount = $cartPurchase->getDiscountCodeAmount();
							if ($amount > $discountCodeAmount) {
								$amount = $amount - $discountCodeAmount;
							}

							$tempArr['discountCodeAmount'] = $discountCodeAmount;
							$accountSummary['isDiscountCodeApplied'] = 1;
							$accountSummary['discountCodeRate'] = $discountCodeAmount1;
						}

						if ($promotionDiscountAmount > 0) {
							if ($promotionDiscountAmount > 0) {
								$amount = $amount - $promotionDiscountAmount;
							}
						}

						$totalCartAmount = $totalCartAmount + $payableAmount;
						$accountSummary['Is' . strtoupper($serviceName) . 'AvailabledInCart'] = 1;
						$accountSummary['Cart' . strtoupper($serviceName) . 'PackageId'][$cartPurchase->getId()] = $packageId;
						$accountSummary['Cart' . strtoupper($serviceName) . 'ServicePurchaseId'][$packageId] = $cartPurchase->getId();
						$accountSummary['Cart'][strtoupper($serviceName)]['RegularPack'][] = $tempArr;

						$accountSummary['Cart'][strtoupper($serviceName)]['Current' . strtoupper($serviceName) . 'Packvalidity'] = $cartPurchase->getValidity();
						$accountSummary['Cart'][strtoupper($serviceName)]['unusedCredit'] = $cartPurchase->getUnusedCredit();
						$accountSummary['Cart'][strtoupper($serviceName)]['unusedDays'] = $cartPurchase->getUnusedDays();

						if (strtoupper($serviceName) == 'ISP') {
							$accountSummary['ISPPackageValidity'] = $cartPurchase->getValidity();
						}

						if ($purchaseType == 'BUNDLE') {
							$accountSummary['CartBundleAvailable'] = 1;
						}
					}

					$accountSummary['discountCodeAmount'] += $discountCodeAmount;
					$accountSummary['TotalCartAmount'] += $totalCartAmount;
					$accountSummary['TotalbundleDiscount'] += $totalbundleDiscount;
					if ($cartPurchase->getPromotionDiscountPer() > 0) {
						$accountSummary['TotalPromotionOff'] += $cartPurchase->getPromotionDiscountAmount();
						$accountSummary['TotalPromotionPer'] = $cartPurchase->getPromotionDiscountPer();
					}
					$accountSummary['BundlePercentage'] = $bundleDiscount;

					//Check package required deers authentication
					$deersPackage = $this->em->getRepository('DhiAdminBundle:Package')->findBy(array('packageId' => $packageId, 'isDeers' => 1));

					if (count($deersPackage) > 0 && $accountSummary['IsDeersRequiredPlanAdded'] == 0) {
						$accountSummary['IsDeersRequiredPlanAdded'] = 1;
					}
//					}
				}
				if ($cartPurchase->getIsCredit() == 1) {

					if ($cartPurchase->getCredit()) {

						$amount = $cartPurchase->getActualAmount();
						$payableAmount = $cartPurchase->getPayableAmount();

						$tempArr = array();
						$tempArr['amount'] = $amount;
						$tempArr['payableAmount'] = $payableAmount;
						$tempArr['credit'] = $cartPurchase->getCredit()->getCredit();
						$tempArr['creditId'] = $cartPurchase->getCredit()->getId();
						$tempArr['servicePurchaseId'] = $cartPurchase->getId();

						$accountSummary['Cart']['Credit'][] = $tempArr;
						$accountSummary['IsCreditAvailabledInCart'] = 1;
						$accountSummary['TotalCartAmount'] += $payableAmount;
						$accountSummary['CartCreditId'][] = $tempArr['creditId'];
					}
				}
			}
		}
		//End here

		$userPurchaseItem = $this->em->getRepository('DhiUserBundle:UserService')->getUsersPurchasedService($user, false);
		if ($userPurchaseItem) {

			$accountSummary['PurchasedAvailable'] = 1;
			$totalbundleDiscount = $totalPurchaseAmount = 0;

			foreach ($userPurchaseItem as $purchase) {

				if (in_array($purchase->getService()->getName(), $accountSummary['AvailableServicesOnLocation'])) {

					$userServiceId = $purchase->getId();
					$serviceId = $purchase->getService()->getId();
					$serviceName = $purchase->getService()->getName();
					$rechargeType = $purchase->getServicePurchase()->getPurchaseType();
					$bundleName = $purchase->getServicePurchase()->getbundleName();
					$dispBundleName = $purchase->getServicePurchase()->getDisplayBundleName();
					$bundleId = $purchase->getServicePurchase()->getbundleId();
					$bundleDiscount = $purchase->getServicePurchase()->getBundleDiscount();
					$isAddOn = $purchase->getIsAddon();
					$packageName = $purchase->getPackageName();
					$packageId = $purchase->getPackageId();
					$amount = $purchase->getActualAmount();
					$discountPercentage = $purchase->getDiscountRate();
					$discountAmount = $purchase->getTotalDiscount();
					$finalCost = $purchase->getFinalCost();
					$expiryDate = $purchase->getExpiryDate();
					$userServiceLocation = $purchase->getServicePurchase()->getServiceLocationId() ? $purchase->getServicePurchase()->getServiceLocationId()->getName() : 'N/A';
					$isPartnerPromocodeApplied = ($purchase->getServicePurchase()->getPromoCodeApplied() == 2 || $purchase->getServicePurchase()->getDiscountCodeApplied() == 2) ? true : false;
					$isBusinessPromocodeApplied = ($purchase->getServicePurchase()->getPromoCodeApplied() == 3 || $purchase->getServicePurchase()->getDiscountCodeApplied() == 3) ? true : false;
					$isPlanActive = $purchase->getIsPlanActive();
					$activationDate = $purchase->getActivationDate();
					$displayBundleDiscount = $purchase->getServicePurchase()->getDisplayBundleDiscount();
					$validityType = $purchase->getServicePurchase()->getvalidityType();

					$package = $this->em->getRepository("DhiAdminBundle:Package")->findOneBy(array('packageId' => $packageId));

					if ($package) {
						$serviceLocation = $package->getServiceLocation()->getId();

						if ($LocationService['serviceLocationId'] == $serviceLocation) {
							$isServiceLocationChanged = 0;
						} else {
							$isServiceLocationChanged = 1;
						}

						if ($this->session->has('availableServiceLocationOnSite')) {
							$description = $package->getDescription();
							$siteLocations = $this->session->get('availableServiceLocationOnSite');
							$siteLocations = array_values($siteLocations);

							if (!in_array($serviceLocation, $siteLocations)) {
								$accountSummary['isSiteChanged'] = 1;
							}
						}

					} else {
						$description = '';
						$serviceLocation = 0;
						$isServiceLocationChanged = 0;
					}

					$tempArr = array();
					$tempArr['serviceName'] = $serviceName;
					$tempArr['serviceLocation'] = $serviceLocation;
					$tempArr['serviceId'] = $serviceId;
					$tempArr['packageName'] = $packageName;
					$tempArr['packageId'] = $packageId;
					$tempArr['amount'] = $amount;
					$tempArr['finalCost'] = $finalCost;
					$tempArr['discountPercentage'] = $discountPercentage;
					$tempArr['discountAmount'] = $discountAmount;
					$tempArr['userServiceId'] = $purchase->getId();
					$tempArr['bandwidth'] = $purchase->getBandwidth();
					$tempArr['validity'] = $purchase->getValidity();
					$tempArr['userserviceId'] = $purchase->getId();
					$tempArr['rechargeType'] = $rechargeType;
					$tempArr['bundleName'] = $bundleName;
					$tempArr['displayBundleName'] = $dispBundleName;
					$tempArr['bundleId'] = $bundleId;
					$tempArr['isPlanActive'] = $isPlanActive;
					$tempArr['activationDate'] = $activationDate->format('m/d/Y H:i:s');
					$tempArr['expiryDate'] = $isEmployee == 0 ? $expiryDate->format('m/d/Y H:i:s') : null;
					$tempArr['serviceLocation'] = $userServiceLocation;
					$tempArr['isServiceLocationChanged'] = $isServiceLocationChanged;
					$tempArr['isPartnerPromocodeApplied'] = $isPartnerPromocodeApplied;
					$tempArr['isBusinessPromocodeApplied'] = $isBusinessPromocodeApplied;
					$tempArr['displayBundleDiscount'] = $displayBundleDiscount;
					$tempArr['validityType'] = $validityType;
					$tempArr['description'] = $description;
					$totalPurchaseAmount = $totalPurchaseAmount + $amount;

					//Calculate remain days and credit of current package
					$currentDate = new DateTime();
					if ($isPlanActive == true) {
						$daysInterval = $currentDate->diff($activationDate);
					} else {
						$noOfdaysUsed = 0;
					}

					if (!empty($daysInterval)) {
						$noOfdaysUsed = $daysInterval->format('%a');
					}

					$perDayPrice = 0;
					$remainigDays = 0;
					$remainigDaysforCredit = 0;
					$remainingCredits = 0;
					$promoCodeExist = 0;
					if ($purchase->getPurchaseOrder()) {
						if ($purchase->getPurchaseOrder()->getPaymentMethod() && $purchase->getIsExtend() == 0) {
							if ($purchase->getPurchaseOrder()->getPaymentMethod()->getCode() == 'promocode') {
								$promoCodeExist = 1;
							}
						}
					}
					
					$suspendedValidity = $this->em->getRepository("DhiAdminBundle:UserSuspendHistory")->getTotalSuspendedHours($purchase->getId());

					if ($isPlanActive == true && $purchase->getValidity() && $noOfdaysUsed <= $purchase->getValidity() && $purchase->getValidity() != 1) {
						
						$remainigDays = $purchase->getValidity() - $noOfdaysUsed;
						$remainigDaysforCredit = ($purchase->getValidity() - $suspendedValidity) - ($noOfdaysUsed - $suspendedValidity);

						if (!empty($daysInterval) && ($daysInterval->h > 0 || $daysInterval->i > 0 || $daysInterval->s > 0)) {
							$remainigDaysforCredit--;
						}

						if ($promoCodeExist) {
							$perDayPrice = 0;
						} else {
							if ($purchase->getIsExtend() == 1) {
								if ($rechargeType == "BUNDLE") {
									$perDayPrice = ($purchase->getActualAmount() - $purchase->getServicePurchase()->getDisplayBundleDiscount()) / $purchase->getActualValidity();
								}else{
									$perDayPrice = $purchase->getActualAmount() / $purchase->getActualValidity();
								}
							} else {
								if ($purchase->getServicePurchase()->getPromoCodeApplied() == 2) {
									// Partner promo code Redeemed
									$objPartnerPromoCodes = $purchase->getServicePurchase()->getDiscountedPartnerPromocode();
									$customerValue = 0;
									$duration = 0;
									if ($objPartnerPromoCodes) {
										$customerValue = $objPartnerPromoCodes->getCustomerValue();
										$duration = $objPartnerPromoCodes->getDuration();
										if ($duration == 0 || empty($duration)) {

											if ($validityType != 'HOURS') {
												$tmpSuspendValidity = $suspendedValidity * 24;
											} else {
												$tmpSuspendValidity = $suspendedValidity;
											}

											$duration = $purchase->getValidity() - $tmpSuspendValidity;
										} else {
											$duration = ceil($duration / 24);
										}
									}

									$perDayPrice = $customerValue / $duration;
								} elseif ($purchase->getServicePurchase()->getPromoCodeApplied() == 3) {
									// Business promo code Redeemed
									$objBusinessPromoCodes = $purchase->getServicePurchase()->getDiscountedBusinessPromocode();
									$customerValue = 0;
									$duration = 0;
									if ($objBusinessPromoCodes) {
										$customerValue = $objBusinessPromoCodes->getCustomerValue();
										$duration = $objBusinessPromoCodes->getDuration();
										if ($duration == 0 || empty($duration)) {

											if ($validityType != 'HOURS') {
												$tmpSuspendValidity = $suspendedValidity * 24;
											} else {
												$tmpSuspendValidity = $suspendedValidity;
											}

											$duration = $purchase->getValidity() - $tmpSuspendValidity;
										} else {
											$duration = ceil($duration / 24);
										}
									}
									$perDayPrice = $customerValue / $duration;
								} else {
									if ($rechargeType == "BUNDLE") {
										$perDayPrice = ($purchase->getActualAmount() - $purchase->getServicePurchase()->getDisplayBundleDiscount()) / ($purchase->getValidity() - $suspendedValidity);
									} else {
										$perDayPrice = $purchase->getActualAmount() / ($purchase->getValidity() - $suspendedValidity);
									}
								}
							}
						}
					}


					if ($isPlanActive == false) {
						$remainigDays = $purchase->getValidity() - $suspendedValidity;
						$remainigDaysforCredit = $purchase->getValidity() - $suspendedValidity;
						if ($promoCodeExist) {
							$perDayPrice = 0;
						} else {
							if ($purchase->getIsExtend() == 1) {
								$perDayPrice = $purchase->getActualAmount() / $purchase->getActualValidity();
							} else {
								$perDayPrice = $purchase->getActualAmount() / ($purchase->getValidity() - $suspendedValidity);
							}
						}
					}

					$remainingCredits = $perDayPrice * $remainigDaysforCredit;
					//End here

					if (strtoupper($serviceName) == 'ISP') {
						$accountSummary['IPTVButtonEnabled'] = 1;
					}

					array_push($accountSummary['ServiceAvailableInPurchased'], strtoupper($serviceName));

					$accountSummary['TotalPurchasedAmount'] = $totalPurchaseAmount;

					if ($isAddOn) {

						if ($accountSummary['IsIPTVAvailabledInPurchased'] == 1) {
							$addonsRemainingCredit = $perDayPrice * $accountSummary['Purchased']['IPTVRemainDays'];
							if ($addonsRemainingCredit > 5) {
								$remainingCredits = $addonsRemainingCredit;
							}
						}

						$accountSummary['PurchasedAddOnPackageId'][] = $packageId;
						$accountSummary['IsAddOnAvailabledInPurchased'] = 1;
						$accountSummary['isServiceLocationChanged'] = $isServiceLocationChanged;
						$accountSummary['isPartnerPromocodeApplied'] = $isPartnerPromocodeApplied;
						$accountSummary['isBusinessPromocodeApplied'] = $isBusinessPromocodeApplied;
						$accountSummary['Purchased'][strtoupper($serviceName)]['AddOnPack'][] = $tempArr;
						$accountSummary['Purchased'][strtoupper($serviceName)]['CurrentAddOnPackbandwidth'] = $purchase->getBandwidth();
						$accountSummary['Purchased'][strtoupper($serviceName)]['CurrentAddOnPackvalidity'] = $purchase->getValidity();
						$accountSummary['Purchased'][strtoupper($serviceName)]['CurrentAddOnPackprice'] = $purchase->getActualAmount();

						$accountSummary['Purchased']['AddOnPackPerDayPrice'] = number_format($perDayPrice, 2);
						$accountSummary['Purchased']['AddOnPackRemainDays'] = $remainigDays;
						$accountSummary['Purchased']['AddOnPackRemainCredit'] = number_format($remainingCredits, 2);
					} else {

						$deactivateServiceLogs = $this->em->getRepository('DhiUserBundle:DeactivateWithOutMacUserServiceLog')->findBy(array('isActivated' => 0, 'userService' => $userServiceId));

						if ($deactivateServiceLogs && $currentDate->format('Y-m-d') <= $expiryDate->format('Y-m-d')) {

							$accountSummary['IsShowDeactiveIPTVMessageAsPerMac'] = 1;
						}

						if ($tempArr['rechargeType'] == 'BUNDLE' && $bundleId != '') {
							$discountAmo = $displayBundleDiscount;
							$totalbundleDiscount += $discountAmo;
							$tempArr['bundleDiscountAmount'] = $totalbundleDiscount;
							$accountSummary['IsBundleAvailabledInPurchased'] = 1;
							$accountSummary['PurchasedBUNDLEPackageId'][] = $bundleId;
							$accountSummary['Purchased']["BUNDLE"]['RegularPack'][$bundleId] = $tempArr;
						}
						$packageDetails = $this->em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $purchase->getPackageId()));
						$accountSummary['Is' . strtoupper($serviceName) . 'AvailabledInPurchased'] = 1;
						$accountSummary['isServiceLocationChanged'] = $isServiceLocationChanged;
						$accountSummary['isPartnerPromocodeApplied'] = $isPartnerPromocodeApplied;
						$accountSummary['isBusinessPromocodeApplied'] = $isBusinessPromocodeApplied;
						$accountSummary['Purchased' . strtoupper($serviceName) . 'PackageId'][] = $packageId;
						$accountSummary['Purchased'][strtoupper($serviceName)]['RegularPack'][] = $tempArr;
						$accountSummary['Purchased'][strtoupper($serviceName)]['Current' . strtoupper($serviceName) . 'bandwidth'] = $purchase->getBandwidth();
						$accountSummary['Purchased'][strtoupper($serviceName)]['Current' . strtoupper($serviceName) . 'validity'] = $purchase->getValidity();
						$accountSummary['Purchased'][strtoupper($serviceName)]['Current' . strtoupper($serviceName) . 'packageValidity'] = $purchase->getActualValidity();
						$accountSummary['Purchased'][strtoupper($serviceName)]['Current' . strtoupper($serviceName) . 'price'] = $purchase->getFinalCost();
						$accountSummary['Purchased'][strtoupper($serviceName) . 'PerDayPrice'] = number_format($perDayPrice, 2);
						$accountSummary['Purchased'][strtoupper($serviceName) . 'RemainDays'] = $remainigDays;
						$accountSummary['Purchased'][strtoupper($serviceName) . 'RemainCredit'] = number_format($remainingCredits, 2);
						$accountSummary['Purchased'][strtoupper($serviceName) . 'IsPlanActive'] = $isPlanActive;
					}
				}
			}
		}

		$accountSummary['ISPExtendPlan'] = false;
		$accountSummary['BundleExtendPlan'] = false;
		$accountSummary['IPTVExtendPlan'] = false;
		$accountSummary['IPTVAddOnExtendPlan'] = false;
		$extendPlanDay = $this->container->getParameter('extend_plan_day') ? $this->container->getParameter('extend_plan_day') : 3;

		if ($accountSummary) {

			if ($accountSummary['isEmployee'] == 0 && isset($accountSummary['Purchased']) && isset($accountSummary['Purchased']['ISPRemainDays']) && $accountSummary['Purchased']['ISPIsPlanActive'] == true) {


				if ($accountSummary['IsISPAvailabledInPurchased'] == 1 && $accountSummary['Purchased']['ISPRemainDays'] < $extendPlanDay || $accountSummary['Purchased']['ISPRemainDays'] == $extendPlanDay) {

					if (isset($accountSummary['Purchased']['ISP'])) {

						//  if ($accountSummary['Purchased']['ISP']['CurrentISPvalidity'] > 5) {

						$accountSummary['ISPExtendPlan'] = true;

						$objPackage = $this->em->getRepository("DhiAdminBundle:Package")->findOneBy(array('packageId' => $accountSummary['Purchased']['ISP']['RegularPack'][0]['packageId'], 'packageName' => $accountSummary['Purchased']['ISP']['RegularPack'][0]['packageName']));

						if ($objPackage) {

							$accountSummary['ISPExtendId'] = $objPackage->getId();
						}
						//  }
					}
				}
			}

			if ($accountSummary['isEmployee'] == 0 && isset($accountSummary['Purchased']) && isset($accountSummary['Purchased']['IPTVRemainDays'])) {


				if ($accountSummary['IsIPTVAvailabledInPurchased'] == 1 && $accountSummary['Purchased']['IPTVRemainDays'] < $extendPlanDay || $accountSummary['Purchased']['IPTVRemainDays'] == $extendPlanDay) {

					if (isset($accountSummary['Purchased']['IPTV'])) {

						//    if ($accountSummary['Purchased']['IPTV']['CurrentIPTVvalidity'] > 5) {

						$accountSummary['IPTVExtendPlan'] = true;

						$objPackage = $this->em->getRepository("DhiAdminBundle:Package")->findOneBy(array('packageId' => $accountSummary['Purchased']['IPTV']['RegularPack'][0]['packageId'], 'packageName' => $accountSummary['Purchased']['IPTV']['RegularPack'][0]['packageName']));

						if ($objPackage) {

							$accountSummary['IPTVExtendId'] = $objPackage->getId();
						} else {
							$accountSummary['IPTVExtendId'] = 0;
						}
						//    }
					}
				}
			}

			// Bundle
			if ($accountSummary['isEmployee'] == 0 && $accountSummary['IsBundleAvailabledInPurchased'] == 1 && !empty($accountSummary['IPTVExtendId']) && !empty($accountSummary['ISPExtendId'])) {
				if ($accountSummary['ISPExtendPlan'] == true && $accountSummary['IPTVExtendPlan'] == true) {
					if (isset($accountSummary['Purchased']['ISP']) && isset($accountSummary['Purchased']['IPTV'])) {
						$accountSummary['BundleExtendPlan'] = true;
						$objBundle = $this->em->getRepository("DhiAdminBundle:Bundle")->findOneBy(array('bundle_id' => $accountSummary['Purchased']['ISP']['RegularPack'][0]['bundleId']));
						if ($objBundle) {
							$accountSummary['BundleExtendId'] = $accountSummary['Purchased']['ISP']['RegularPack'][0]['bundleId'];
						}
					}
				}
			}

			if ($accountSummary['isEmployee'] == 0 && isset($accountSummary['Purchased']) && isset($accountSummary['Purchased']['AddOnPackRemainDays'])) {


				if ($accountSummary['IsAddOnAvailabledInPurchased'] == 1 && $accountSummary['Purchased']['AddOnPackRemainDays'] < $extendPlanDay || $accountSummary['Purchased']['AddOnPackRemainDays'] == $extendPlanDay) {

					if (isset($accountSummary['Purchased']['IPTV'])) {

						//   if ($accountSummary['Purchased']['IPTV']['CurrentAddOnPackvalidity'] > 5) {

						$accountSummary['IPTVAddOnExtendPlan'] = true;

						$objPackage = $this->em->getRepository("DhiAdminBundle:Package")->findOneBy(array('packageId' => $accountSummary['Purchased']['IPTV']['AddOnPack'][0]['packageId'], 'packageName' => $accountSummary['Purchased']['IPTV']['AddOnPack'][0]['packageName']));

						if ($objPackage) {

							$accountSummary['AddonExtendId'] = $objPackage->getId();
						} else {
							$accountSummary['AddonExtendId'] = 0;
						}
						//    }
					}
				}
			}

			$isExtendISP = $isExtendIPTV = false;
			if ($accountSummary['isEmployee'] == 0 && isset($accountSummary['Cart'])) {

				$this->session->set('isExtendISP', false);

				if (isset($accountSummary['Cart']['ISP'])) {

					if (isset($accountSummary['Cart']['ISP']['RegularPack'])) {

						if ($accountSummary['Cart']['ISP']['RegularPack'] && $accountSummary['ISPExtendPlan'] == 1) {

							if ($accountSummary['Cart']['ISP']['RegularPack'][0]['isExtend'] == 1 && $accountSummary['Cart']['ISP']['RegularPack'][0]['packageId'] == $accountSummary['PurchasedISPPackageId'][0]) {


								if ($this->session->get('isExtendISP') == false) {
									$isExtendISP = true;
									$this->session->set('isExtendISP', true);
								}
							}
						}
					}
				}
			}


			if ($accountSummary['isEmployee'] == 0 && isset($accountSummary['Cart'])) {

				$this->session->set('isExtendIPTV', false);

				if (isset($accountSummary['Cart']['IPTV'])) {

					if (isset($accountSummary['Cart']['IPTV']['RegularPack'])) {

						if ($accountSummary['Cart']['IPTV']['RegularPack'] && $accountSummary['IPTVExtendPlan'] == 1) {

							if ($accountSummary['Cart']['IPTV']['RegularPack'][0]['isExtend'] == 1 && $accountSummary['Cart']['IPTV']['RegularPack'][0]['packageId'] == $accountSummary['PurchasedIPTVPackageId'][0]) {

								if ($this->session->get('isExtendIPTV') == false) {
									$isExtendIPTV = true;
									$this->session->set('isExtendIPTV', true);
								}
							}
						}
					}
				}
			}

			// Extend Bundle
			if ($accountSummary['isEmployee'] == 0 && $isExtendISP == true && $isExtendIPTV == true && $accountSummary['IsBundleAvailabledInPurchased'] == 1 && $accountSummary['IsBundleAvailabledInCart'] == 1) {
				$this->session->set('isExtendBundle', true);
			}

			if ($accountSummary['isEmployee'] == 0 && isset($accountSummary['Cart'])) {

				$this->session->set('isAddonExtendIPTV', false);

				if (isset($accountSummary['Cart']['IPTV'])) {

					if (isset($accountSummary['Cart']['IPTV']['AddOnPack'])) {

						if ($accountSummary['Cart']['IPTV']['AddOnPack'] && $accountSummary['IPTVExtendPlan'] == 1) {

							if (isset($accountSummary['PurchasedAddOnPackageId'][0]) && $accountSummary['Cart']['IPTV']['AddOnPack'][0]['packageId'] == $accountSummary['PurchasedAddOnPackageId'][0]) {

								if ($this->session->get('isAddonExtendIPTV') == false) {

									$this->session->set('isAddonExtendIPTV', true);
								}
							}
						}
					}
				}
			}

			$this->session->set('isISPOnly', false);
			if ($accountSummary['IsISPAvailabledInCart'] == 1 && $accountSummary['IsISPAvailabledInPurchased'] == 0 && $accountSummary['ISPExtendPlan'] == 0) {
				if ($accountSummary['IsIPTVAvailabledInCart'] == 0 and $accountSummary['IsIPTVAvailabledInPurchased'] == 0) {
					$this->session->set('isISPOnly', true);
				}
			}
			// set isplanactive false on upgrade isponly

			$this->session->set('isISPOnlyUpgrade', false);
			if ($accountSummary['IsISPAvailabledInCart'] == 1 && $accountSummary['IsISPAvailabledInPurchased'] >= 0 && $accountSummary['ISPExtendPlan'] == 0) {
				$isUpgradeIsp = true;
				if (isset($accountSummary['ISPExtendId']) && in_array($accountSummary['ISPExtendId'], $accountSummary['CartISPServicePurchaseId'])) {
					$isUpgradeIsp = false;
				}
				if ($isUpgradeIsp == true && $accountSummary['IsIPTVAvailabledInCart'] == 0 and $accountSummary['IsIPTVAvailabledInPurchased'] == 0) {
					$this->session->set('isISPOnlyUpgrade', true);
				}
			}
		}

		return $accountSummary;
	}

	public function checkISPAddedForIPTV($type, $user, $isTvod = false) {

		if ($type != 'admin' && $isTvod == false) {

			$user = NULL;
		}
		$summaryData = $this->getUserServiceSummary($type, $user, $isTvod);

		//Check ISP service added or not for IPTV
		if (in_array('IPTV', $summaryData['AvailableServicesOnLocation']) && in_array('ISP', $summaryData['AvailableServicesOnLocation'])) {

			if ($summaryData['IsIPTVAvailabledInCart'] == 0) {

				if (!in_array('ISP', $summaryData['ServiceAvailableInPurchased']) && !in_array('ISP', $summaryData['ServiceAvailableInCart'])) {

					return false;
				}
			}
		}

		return true;
	}

	public function upgradeIPTVServicePlan($user, $objServicePurchase, $servicePlanData, $type = 'user') {

		$loggedInUser = $this->securitycontext->getToken()->getUser();
		$returnFlag = false;

		if ($type == 'admin') {

			$sessionId = $this->session->get('adminSessionId');
			$summaryData = $this->getUserServiceSummary($type, $user);
		} else {

			$sessionId = $this->session->get('sessionId');
			$summaryData = $this->getUserServiceSummary($type, NULL);
		}

		$tmpPayableAmount = (!empty($servicePlanData['purchase_type']) && $servicePlanData['purchase_type'] == 'BUNDLE') ? $servicePlanData['PayableAmount'] : $servicePlanData['ActualAmount'];
		$servicePlanData['PayableAmount'] = $tmpPayableAmount - $summaryData['Purchased']['IPTVRemainCredit'];
		$servicePlanData['FinalCost'] = $tmpPayableAmount;
		$servicePlanData['User'] = $user;
		$servicePlanData['UnusedDays'] = $summaryData['Purchased']['IPTVRemainDays'];
		$servicePlanData['UnusedCredit'] = $summaryData['Purchased']['IPTVRemainCredit'];
		//Update IPTV data
		if ($this->updateServicePurchaseData($objServicePurchase, $servicePlanData, $summaryData['isPromotionAvailable'])) {

			$returnFlag = true;
		}

		//Add Activity Log
		$activityLog = array();
		$activityLog['user'] = $loggedInUser;
		$activityLog['activity'] = 'Add to Cart ExchangeVUE package';
		$activityLog['description'] = 'User ' . $loggedInUser->getUserName() . ' add to cart ExchangeVUE plan for Upgrade.';
		$this->ActivityLog->saveActivityLog($activityLog);
		//Activity Log end here

		if ($this->checkISPAddedForIPTV($type, $user)) {

			//$objService = $this->em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
			if ($summaryData['IsISPAvailabledInCart'] == 0 && $summaryData['IsISPAvailabledInPurchased'] == 1) {

				$objServicePurchase = new ServicePurchase();

				$ISPPackages = $summaryData['Purchased']['ISP']['RegularPack'];

				if ($ISPPackages) {

					foreach ($ISPPackages as $ISPPackage) {

						if ($servicePlanData['Validity'] > $summaryData['Purchased']['ISPRemainDays']) {

							$ispPurchasedService = $this->em->getRepository('DhiUserBundle:UserService')->find($ISPPackage['userserviceId']);

							$objServicePurchase = new ServicePurchase();

							$servicePlanData = array();
							$servicePlanData['User'] = $user;
							$servicePlanData['Service'] = $ispPurchasedService->getService();
							$servicePlanData['PackageId'] = $ispPurchasedService->getPackageId();
							$servicePlanData['PackageName'] = $ispPurchasedService->getPackageName();
							$servicePlanData['ActualAmount'] = $ispPurchasedService->getActualAmount();
							$servicePlanData['PayableAmount'] = $ispPurchasedService->getActualAmount() - $summaryData['Purchased']['ISPRemainCredit'];
							$servicePlanData['FinalCost'] = $ispPurchasedService->getActualAmount();
							$servicePlanData['TermsUse'] = 1;
							$servicePlanData['Bandwidth'] = $ispPurchasedService->getBandwidth();
							$servicePlanData['Validity'] = $ispPurchasedService->getValidity();
							$servicePlanData['SessionId'] = $sessionId;
							$servicePlanData['IsUpgrade'] = 1;
							$servicePlanData['UnusedDays'] = $summaryData['Purchased']['ISPRemainDays'];
							$servicePlanData['UnusedCredit'] = $summaryData['Purchased']['ISPRemainCredit'];

							$this->updateServicePurchaseData($objServicePurchase, $servicePlanData, $summaryData['isPromotionAvailable']);
						}
					}

					//Add Activity Log
					$activityLog = array();
					$activityLog['user'] = $loggedInUser;
					$activityLog['activity'] = 'Add to Cart ISP';
					$activityLog['description'] = 'ISP plan added into cart along with ExchangeVUE plan Upgrade.';
					$this->ActivityLog->saveActivityLog($activityLog);
					//Activity Log end here
				}
			}
		}

		return $returnFlag;
	}

	public function upgradeISPServicePlan($user, $objServicePurchase, $servicePlanData, $type = 'user') {

		$loggedInUser = $this->securitycontext->getToken()->getUser();
		$returnFlag = false;

		if ($type == 'admin') {

			$sessionId = $this->session->get('adminSessionId');
			$summaryData = $this->getUserServiceSummary($type, $user);
		} else {

			$sessionId = $this->session->get('sessionId');
			$summaryData = $this->getUserServiceSummary($type, NULL);
		}
		$tmpPayableAmount = (!empty($servicePlanData['purchase_type']) && $servicePlanData['purchase_type'] == 'BUNDLE') ? $servicePlanData['PayableAmount'] : $servicePlanData['ActualAmount'];
		if ($summaryData['IsISPAvailabledInPurchased'] == 1) {

			$servicePlanData['PayableAmount'] = $tmpPayableAmount - $summaryData['Purchased']['ISPRemainCredit'];
			$servicePlanData['FinalCost'] = $tmpPayableAmount;
			$servicePlanData['User'] = $user;
			$servicePlanData['UnusedDays'] = $summaryData['Purchased']['ISPRemainDays'];
			$servicePlanData['UnusedCredit'] = $summaryData['Purchased']['ISPRemainCredit'];

			if ($this->updateServicePurchaseData($objServicePurchase, $servicePlanData, $summaryData['isPromotionAvailable'])) {

				$returnFlag = true;
			}

			//Add Activity Log
			$activityLog = array();
			$activityLog['user'] = $loggedInUser;
			$activityLog['activity'] = 'Add to Cart ISP';
			$activityLog['description'] = 'User ' . $loggedInUser->getUserName() . ' add to cart ISP plan for Upgrade.';
			$this->ActivityLog->saveActivityLog($activityLog);
			//Activity Log end here
		}

		return $returnFlag;
	}

	public function addNewServicePlan($user, $objServicePurchase, $servicePlanData, $type = 'user') {


		$returnFlag = false;

		if ($type == 'admin') {
			$admin = $this->get('security.context')->getToken()->getUser();
			$loggedInUser = $user;
			$sessionId = $this->session->get('adminSessionId');
			$summaryData = $this->getUserServiceSummary($type, $user);
		} else {
			$loggedInUser = $this->securitycontext->getToken()->getUser();
			$sessionId = $this->session->get('sessionId');
			$summaryData = $this->getUserServiceSummary($type, NULL);
		}

		$tmpPayableAmount = (!empty($servicePlanData['purchase_type']) && $servicePlanData['purchase_type'] == 'BUNDLE') ? $servicePlanData['PayableAmount'] : $servicePlanData['ActualAmount'];
		$servicePlanData['PayableAmount'] = $tmpPayableAmount;
		$servicePlanData['FinalCost'] = $tmpPayableAmount;
		$servicePlanData['User'] = $user;
		$servicePlanData['UnusedDays'] = NULL;
		$servicePlanData['UnusedCredit'] = NULL;

		if ($this->updateServicePurchaseData($objServicePurchase, $servicePlanData, $summaryData['isPromotionAvailable'])) {

			//If ISP validity less than 30days then remove IPTV service from cart
			/* if($servicePlanData['Service']) {

			  if(strtoupper($servicePlanData['Service']->getName()) == 'ISP') {

			  if($servicePlanData['Validity'] < 30 || $servicePlanData['Validity'] == ''){

			  $service = $this->em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => 'IPTV'));

			  if($service) {

			  $query = $this->em->createQuery("DELETE DhiServiceBundle:ServicePurchase sp WHERE sp.service = '".$service->getId()."' AND sp.sessionId = '".$sessionId."' AND sp.paymentStatus = 'New' AND sp.rechargeStatus = '0'");
			  $query->execute();
			  }
			  }
			  }
			  } */

			$returnFlag = true;

			//Add Activity Log
			$activityLog = array();
			if ($type == 'admin') {
				$activityLog['admin'] = $admin;
				$activityLog['description'] = 'Admin has added ' . $servicePlanData['Service']->getName() . ' pack to cart for ' . $loggedInUser->getUserName() . ' user.';
			} else {
				$activityLog['description'] = 'User ' . $loggedInUser->getUserName() . ' add to cart new ' . $servicePlanData['Service']->getName() . ' plan.';
			}
			$activityLog['user'] = $loggedInUser;
			$activityLog['activity'] = 'Add to Cart ' . $servicePlanData['Service']->getName();
			$this->ActivityLog->saveActivityLog($activityLog);
			//Activity Log end here
		}

		return $returnFlag;
	}

	public function addPremiumPackage($user, $objService, $premiumPackageIds, $premiumPackageNameArr, $premiumPriceArr, $premiumPackageValidityArr, $type = 'user', $extendPlan = 0) {

		$loggedInUser = $this->securitycontext->getToken()->getUser();
		$returnFlag = false;
		$addOnDeleteIds = array();

		if ($type == 'admin') {

			$sessionId = $this->session->get('adminSessionId');
			$summaryData = $this->getUserServiceSummary($type, $user);
		} else {

			$sessionId = $this->session->get('sessionId');
			$summaryData = $this->getUserServiceSummary($type, NULL);
		}

		if ($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsIPTVAvailabledInPurchased'] == 1) {


			if ($summaryData['IsAddOnAvailabledInCart'] == 1) {

				$iptvPackages = $summaryData['Cart']['IPTV'];

				if (!empty($iptvPackages['AddOnPack'])) {

					foreach ($iptvPackages['AddOnPack'] as $addOnPackage) {

						$addOnDeleteIds[] = $addOnPackage['servicePurchaseId'];

						if ($premiumPackageIds) {

							if (!in_array($addOnPackage['packageId'], $premiumPackageIds)) {

								$objDeleteAddOnPackage = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->find($addOnPackage['servicePurchaseId']);
								$this->em->remove($objDeleteAddOnPackage);
								$this->em->flush();
							}
						}
					}
				}
			}

			if (isset($premiumPackageIds) && !empty($premiumPackageIds)) {

				foreach ($premiumPackageIds as $packageId) {

					$condition = array('packageId' => $packageId, 'sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 1);
					$objAddOnServicePurchase = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);

					if (!$objAddOnServicePurchase) {

						$objAddOnServicePurchase = new ServicePurchase();
					}

					$PayableAmount = $premiumPriceArr[$packageId];
					$PackageName = $premiumPackageNameArr[$packageId];
					$PackageValidity = $premiumPackageValidityArr[$packageId];
					$PackagePrice = $premiumPriceArr[$packageId];
					$premiumPerDayPrice = $PackagePrice / $PackageValidity;
					$UnusedDays = NULL;
					$UnusedCredit = NULL;

					if ($summaryData['IsIPTVAvailabledInCart'] == 1) {

						$PayableAmount = $premiumPerDayPrice * $summaryData['Cart']['IPTV']['CurrentIPTVPackvalidity'];
						$PackageValidity = $summaryData['Cart']['IPTV']['CurrentIPTVPackvalidity'];
					} elseif ($summaryData['IsIPTVAvailabledInPurchased'] == 1 && $extendPlan == 0) {

						$PayableAmount = $premiumPerDayPrice * $summaryData['Purchased']['IPTVRemainDays'];
						$PackageValidity = $summaryData['Purchased']['IPTVRemainDays'];
					} elseif ($summaryData['IsIPTVAvailabledInPurchased'] == 1 && $extendPlan != 0) {

						$PayableAmount = $summaryData['Purchased']['IPTV']['AddOnPack'][0]['amount'];
						$PackageValidity = $summaryData['Purchased']['IPTV']['AddOnPack'][0]['validity'];
					}

					if ($PayableAmount > 5) {

						$servicePlanData = array();
						$servicePlanData['User'] = $user;
						$servicePlanData['Service'] = $objService;
						$servicePlanData['PackageId'] = $packageId;
						$servicePlanData['PackageName'] = $premiumPackageNameArr[$packageId];
						$servicePlanData['ActualAmount'] = $premiumPriceArr[$packageId];
						$servicePlanData['PayableAmount'] = $PayableAmount;
						$servicePlanData['FinalCost'] = $premiumPriceArr[$packageId];
						$servicePlanData['TermsUse'] = 1;
						$servicePlanData['Bandwidth'] = NULL;
						$servicePlanData['Validity'] = $PackageValidity;
						$servicePlanData['SessionId'] = $sessionId;
						$servicePlanData['IsUpgrade'] = 0;
						$servicePlanData['IsAddon'] = 1;
						$servicePlanData['UnusedDays'] = $UnusedDays;
						$servicePlanData['UnusedCredit'] = $UnusedCredit;
						$servicePlanData['serviceLocation'] = $user->getUserServiceLocation() ? $user->getUserServiceLocation() : null;

						if ($this->updateServicePurchaseData($objAddOnServicePurchase, $servicePlanData, $summaryData['isPromotionAvailable'])) {

							if ($extendPlan > 0) {

								$this->session->set('isAddonExtendIPTV', true);
							}
							$returnFlag = true;

							//Add Activity Log
							$activityLog = array();
							$activityLog['user'] = $loggedInUser;
							$activityLog['activity'] = 'Add to Cart AddOns Package';
							$activityLog['description'] = 'User ' . $loggedInUser->getUserName() . ' add to cart AddOns Package.';
							$this->ActivityLog->saveActivityLog($activityLog);
							//Activity Log end here
						}
					}
				}
			} else {

				if (count($addOnDeleteIds) > 0) {

					$objDeletePurchase = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->deleteItemFromCart($sessionId, $user, $addOnDeleteIds);
					$returnFlag = true;
				}
			}
		}

		return $returnFlag;
	}

	public function updateServicePurchaseData($objServicePurchase, $servicePlanData, $isPromotionAvailable) {

		$objServicePurchase->setUser($servicePlanData['User']);
		$objServicePurchase->setService($servicePlanData['Service']);
		$objServicePurchase->setPackageId($servicePlanData['PackageId']);
		$objServicePurchase->setPackageName($servicePlanData['PackageName']);
		$objServicePurchase->setPaymentStatus('New');
		$objServicePurchase->setActualAmount($servicePlanData['ActualAmount']);
		$objServicePurchase->setPayableAmount($servicePlanData['PayableAmount']);
		$objServicePurchase->setFinalCost($servicePlanData['FinalCost']);
		$objServicePurchase->setTermsUse($servicePlanData['TermsUse']);
		$objServicePurchase->setBandwidth($servicePlanData['Bandwidth']);
		$objServicePurchase->setValidity($servicePlanData['Validity']);
		$objServicePurchase->setSessionId($servicePlanData['SessionId']);
		$objServicePurchase->setIsUpgrade($servicePlanData['IsUpgrade']);
		$objServicePurchase->setUnusedDays($servicePlanData['UnusedDays']);
		$objServicePurchase->setUnusedCredit($servicePlanData['UnusedCredit']);

		if (!empty($servicePlanData['isExtend']) && $servicePlanData['isExtend'] == 1) {
			$objServicePurchase->setIsExtend($servicePlanData['isExtend']);
		}

		if (!empty($servicePlanData['validityType']) && $servicePlanData['validityType'] == 1) {
			$objServicePurchase->setValidityType('HOURS');
		} else {
			$objServicePurchase->setValidityType('DAYS');
		}

		$objServicePurchase->setBundleId(isset($servicePlanData['bundle_id']) ? $servicePlanData['bundle_id'] : null);
		$objServicePurchase->setbundleDiscount(isset($servicePlanData['bundleDiscount']) ? $servicePlanData['bundleDiscount'] : null);
		$objServicePurchase->setDisplayBundleDiscount(isset($servicePlanData['displayBundleDiscount']) ? $servicePlanData['displayBundleDiscount'] : null);
		$objServicePurchase->setPurchaseType(isset($servicePlanData['purchase_type']) ? $servicePlanData['purchase_type'] : null);
		$objServicePurchase->setBundleName(isset($servicePlanData['bundleName']) ? $servicePlanData['bundleName'] : null);
		$objServicePurchase->setDisplayBundleName(isset($servicePlanData['displayBundleName']) ? $servicePlanData['displayBundleName'] : null);
		$objServicePurchase->setBundleApplied(isset($servicePlanData['bundleApplied']) ? $servicePlanData['bundleApplied'] : 0);
		$objServicePurchase->setDiscountCodeApplied(isset($servicePlanData['discountCodeApplied']) ? $servicePlanData['discountCodeApplied'] : 0);
		$objServicePurchase->setServiceLocationId(isset($servicePlanData['serviceLocation']) ? $servicePlanData['serviceLocation'] : null);

		if (isset($servicePlanData['IsAddon']) && !empty($servicePlanData['IsAddon'])) {

			$objServicePurchase->setIsAddon($servicePlanData['IsAddon']);
		}

		// WhiteLabel Site
		$wwSite = $this->getSiteFromPackage($servicePlanData['PackageId']);
		if ($wwSite) {
			$objServicePurchase->setWhiteLabel($wwSite);
		}

		$this->em->persist($objServicePurchase);
		$this->em->flush();

		$insertIdServicePurchase = $objServicePurchase->getId();

		if ($insertIdServicePurchase) {
			$this->setPromotionDiscount($insertIdServicePurchase, $isPromotionAvailable);
			return $insertIdServicePurchase;
		}

		return false;
	}

	public function getPdfPurchaseHistoryData($user = '', $ipAddressZones = '', $searchData = '', $slot = array()) {

		$admin = $this->get('security.context')->getToken()->getUser();

		$country = '';
		if ($admin->getGroup() != 'Super Admin' && $admin->getSingleRole() != 'ROLE_USER') {
			$country = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}

		if (isset($searchData) && $searchData != '') {

			$purchaseOrderData = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->getSearchPdfPurchaseHistory($user, $ipAddressZones, $country, $searchData, false, $slot);
		} else {
			$purchaseOrderData = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->getPdfPurchaseHistory($user, $ipAddressZones, $country, false, $slot);
		}



		$purchaseHistoryData = array();
		if ($purchaseOrderData) {

			$i = 0;
			foreach ($purchaseOrderData as $purchaseOrder) {

				$purchasedService = '';
				$isOnlyCredit = true;
				$compArr = explode(',', $purchaseOrder['isCompensation']);
				$creditArr = explode(',', $purchaseOrder['isCredit']);
				$serviceArr = explode(',', $purchaseOrder['serviceName']);

				if (!empty($serviceArr)) {
					$purchasedTypeArr = array();
					foreach ($serviceArr as $key => $serviceNm) {
						if ($creditArr[$key] == 1) {
							$purchasedTypeArr[] = "Credit";
						} else if ($compArr[$key] == 1) {
							$isOnlyCredit = false;
							$purchasedTypeArr[] = "Compensation";
						} else {
							$isOnlyCredit = false;
							$purchasedTypeArr[] = strtoupper($serviceNm);
						}
					}

					if (!empty($purchasedTypeArr)) {
						$purchasedService = implode('<br/>', array_unique($purchasedTypeArr));
					}
				}

				$paymentMethod = '';
				$transactionId = 'N/A';
				if ($purchaseOrder->getPaymentMethod()) {

					$paymentMethod = $purchaseOrder->getPaymentMethod()->getName();

					if ($purchaseOrder->getPaymentMethod()->getCode() == 'PayPal' || $purchaseOrder->getPaymentMethod()->getCode() == 'CreditCard') {

						if ($purchaseOrder->getPaypalCheckout()) {

							$transactionId = $purchaseOrder->getPaypalCheckout()->getPaypalTransactionId();
						}
					}

					if ($purchaseOrder->getPaymentMethod()->getCode() == 'Milstar') {

						if ($purchaseOrder->getMilstar()) {

							$transactionId = $purchaseOrder->getMilstar()->getAuthTicket();
						}
					}

					if (strtolower($purchaseOrder->getPaymentMethod()->getCode()) == 'chase') {

						if ($purchaseOrder->getChase()) {

							$transactionId = $purchaseOrder->getChase()->getChaseTransactionId();
						}
					}
				} else {

					if ($isOnlyCredit) {

						if ($purchaseOrder->getPaymentBy() == 'Admin') {

							if ($purchaseOrder->getUserCreditLogs()) {

								foreach ($purchaseOrder->getUserCreditLogs() as $userCreditLog) {

									$paymentMethod = $userCreditLog->getType();
									if ($userCreditLog->getType() == 'EagleCash') {
										$paymentMethod .= '<br/>(' . $userCreditLog->getEagleCashNo() . ')';
									}
								}
							}
						}
					} else {
						if ($isOnlyCredit) {
							if ($purchaseOrder['paymentBy'] == 'Admin') {
								if (!empty($purchaseOrder['type'])) {
									$paymentMethod = $purchaseOrder['type'];
								} else {
									$paymentMethod = 'Pay By Admin';
								}
							}
						}
					}
				}
				$tempData = array();
				$tempData['orderNumber'] = $purchaseOrder['orderNumber'];
				$tempData['transactionId'] = $transactionId;
				$tempData['username'] = ($purchaseOrder['username']) ? $purchaseOrder['username'] : '';
				$tempData['purchaseService'] = $purchasedService;
				$tempData['paymentMethod'] = $paymentMethod;
				$tempData['paymentStatus'] = $purchaseOrder['paymentStatus'] == "Expired" ? "Plan Expired by Customer Support" : $purchaseOrder['paymentStatus'];
				$tempData['totalAmount'] = (!empty($purchaseOrder['totalAmount'])) ? "$" . $purchaseOrder['totalAmount'] : '';
				$tempData['refundAmount'] = (!empty($purchaseOrder['refundAmount'])) ? "$" . $purchaseOrder['refundAmount'] : '';
				$tempData['purchaseDate'] = (!empty($purchaseOrder['createdAt'])) ? $purchaseOrder['createdAt']->format('M-d-Y H:i:s') : '';
				$tempData['ipAddress'] = $purchaseOrder['ipAddress'];

				$purchaseHistoryData[$i] = $tempData;
				$purchaseHistoryData[$i]['serviceData'] = '';

				$purchaseOrder = $this->em->getRepository("DhiServiceBundle:PurchaseOrder")->findOneBy(array("orderNumber" => $purchaseOrder['orderNumber']));

				if ($purchaseOrder) {
					if ($purchaseOrder->getServicePurchases()) {

						$purchaseHistoryDetail = array();

						foreach ($purchaseOrder->getServicePurchases() as $servicePurchased) {

							$serviceName = '';
							$tempArr = array();
							$tempArr['packageName'] = $servicePurchased->getPackageName();
							$tempArr['packageActualAmount'] = $servicePurchased->getActualAmount();
							$tempArr['packagePaybleAmount'] = $servicePurchased->getPayableAmount();
							$tempArr['paymentStatus'] = $servicePurchased->getPaymentStatus();
							$tempArr['activationStatus'] = $servicePurchased->getActivationStatus();
							$tempArr['serviceLocation'] = $servicePurchased->getServiceLocationId() ? $servicePurchased->getServiceLocationId()->getName() : 'N/A';
							$tempArr['activationDate'] = '';
							$tempArr['expiryDate'] = '';
							$tempArr['service'] = '';
							$tempArr['validity'] = '';

							if ($servicePurchased->getService() && $servicePurchased->getIsCompensation() != 1) {

								if ($servicePurchased->getIsAddon() == 1) {

									$serviceName = 'AddOn';
								} else {

									$serviceName = strtoupper($servicePurchased->getService()->getName());
								}

								if ($serviceName) {

									$purchaseHistoryDetail[$serviceName][$servicePurchased->getId()] = $tempArr;
								}
							}

							if ($servicePurchased->getIsCredit() == 1 && $servicePurchased->getCredit()) {

								$credit = $servicePurchased->getCredit()->getCredit();

								$tempArr['packageName'] = $credit . ' ExchangeVUE Credits';
								//$tempArr['packageName'] = 'Pay $'.$servicePurchased->getPayableAmount().' and get '.$credit.' credit in your account.';
								$purchaseHistoryDetail['Credit'][$servicePurchased->getId()] = $tempArr;
							}

							if ($servicePurchased->getIsCompensation() == 1) {

								$tempArr['service'] = strtoupper($servicePurchased->getService()->getName());
								$tempArr['validity'] = $purchaseOrder->getCompensationValidity();
								$purchaseHistoryDetail['Compensation'][$servicePurchased->getId()] = $tempArr;
							}
						}
					}

					if ($purchaseOrder->getUserService()) {

						foreach ($purchaseOrder->getUserService() as $userService) {

							if ($userService->getService()) {

								if ($userService->getIsAddon() == 1) {

									$serviceName = 'AddOn';
								} else {

									$serviceName = strtoupper($userService->getService()->getName());
								}

								if ($userService->getServicePurchase()) {

									$servicePurchaseId = $userService->getServicePurchase()->getId();

									if ($userService->getActivationDate()) {

										$activationDate = $userService->getActivationDate()->format('m/d/Y');
										$purchaseHistoryDetail[$serviceName][$servicePurchaseId]['activationDate'] = $activationDate;
									}

									if ($userService->getExpiryDate()) {

										$expiryDate = $userService->getExpiryDate()->format('m/d/Y');
										$purchaseHistoryDetail[$serviceName][$servicePurchaseId]['expiryDate'] = $expiryDate;
									}
								}
							}
						}
					}
				}

				if (isset($purchaseHistoryDetail)) {

					$purchaseHistoryData[$i]['serviceData'][] = $purchaseHistoryDetail;
				}
				$i++;
			}
		}

		return $purchaseHistoryData;
	}

	public function addUserCredit($user, $creditId, $sessionId) {

		$flag = false;
		$isSelevisionUser = $this->get('selevisionService')->createNewUser($user);

		if ($creditId == 'removeCredit') {

			$removeCredit = $this->em->createQueryBuilder()->delete('DhiServiceBundle:ServicePurchase', 'sp')
											->where('sp.isCredit =:isCredit')
											->setParameter('isCredit', 1)
											->andWhere('sp.paymentStatus =:paymentStatus')
											->setParameter('paymentStatus', 'New')
											->andWhere('sp.user =:user')
											->setParameter('user', $user)
											->andWhere('sp.sessionId =:sessionId')
											->setParameter('sessionId', $sessionId)
											->getQuery()->execute();
			$flag = true;
		} else {

			$objServicePurchase = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy(array('isCredit' => 1, 'paymentStatus' => 'New', 'user' => $user));

			if (!$objServicePurchase) {

				$objServicePurchase = new ServicePurchase();
			}

			$objCredit = $this->em->getRepository("DhiAdminBundle:Credit")->find($creditId);

			if ($objCredit) {

				$objServicePurchase->setUser($user);
				$objServicePurchase->setCredit($objCredit);
				$objServicePurchase->setActualAmount($objCredit->getAmount());
				$objServicePurchase->setPayableAmount($objCredit->getAmount());
				$objServicePurchase->setIsCredit(1);
				$objServicePurchase->setSessionId($sessionId);
				$objServicePurchase->setPackageId(0);
				$objServicePurchase->setPackageName('');
				$this->em->persist($objServicePurchase);
				$this->em->flush();

				$flag = true;
			}
		}

		return $flag;
	}

	public function getAdminValidCountry() {
		$admin = $this->get('security.context')->getToken()->getUser();

		$country = '';
		if ($admin->getGroup() != 'Super Admin' && $admin->getSingleRole() != 'ROLE_USER') {
			$country = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}
		return $country;
	}

	public function getPrintPurchaseHistoryData($user = '', $ipAddressZones = '', $searchData = '', $slot = array()) {
		$admin = $this->get('security.context')->getToken()->getUser();
		$country = '';
		if ($admin->getGroup() != 'Super Admin' && $admin->getSingleRole() != 'ROLE_USER') {
			$country = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}

		if (isset($searchData) && $searchData != '') {
			$purchaseOrderData = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->getSearchPdfPurchaseHistory($user, $ipAddressZones, $country, $searchData, true, $slot);
		} else {
			$purchaseOrderData = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->getPdfPurchaseHistory($user, $ipAddressZones, $country, true, $slot);
		}

	$purchaseHistoryData = array();
	if ($purchaseOrderData) {
		$i = 0;
		foreach ($purchaseOrderData as $purchaseOrder) {

			$purchasedService = '';
            $isOnlyCredit     = true;
            $compArr          = explode(',', $purchaseOrder['isCompensation']);
            $creditArr        = explode(',', $purchaseOrder['isCredit']);
            $serviceArr       = explode(',', $purchaseOrder['serviceName']);

            $purchasedTypeArr = array();
            foreach ($serviceArr as $key => $serviceNm) {
                if ($creditArr[$key] == 1) {
                    $purchasedTypeArr[] = "Credit";
                }else if ($compArr[$key] == 1) {
                    $isOnlyCredit = false;
                    $purchasedTypeArr[] = "Compensation";
                }else{
                    $isOnlyCredit = false;
                    $purchasedTypeArr[] = strtoupper($serviceNm);
                }
            }

            if (!empty($purchasedTypeArr)) {
                $purchasedService = implode('<br/>', array_unique($purchasedTypeArr));
            }

						$paymentMethod = '';
            $transactionId = 'N/A';
            if (!empty($purchaseOrder['paymentmethodCode'])) {
                $paymentMethod = $purchaseOrder['paymentmethodName'];
                if($purchaseOrder['paymentmethodCode'] == 'Paypal' || $purchaseOrder['paymentmethodCode'] == 'CreditCard'){
                    if(!empty($purchaseOrder['paypalTransactionId'])) {
                        $transactionId = $purchaseOrder['paypalTransactionId'];
						}
					}

					if ($purchaseOrder['paymentmethodCode'] == 'Milstar') {
						if (!empty($purchaseOrder['authTicket'])) {
							$transactionId = $purchaseOrder['authTicket'];
						}
					}

					if (strtolower($purchaseOrder['paymentmethodCode']) == 'chase') {
						if (!empty($purchaseOrder['chaseTransactionId'])) {
							$transactionId = $purchaseOrder['chaseTransactionId'];
						}
					}
				} else {
					if ($isOnlyCredit) {
						if ($purchaseOrder['paymentBy'] == 'Admin') {
							if (!empty($purchaseOrder['type'])) {
								$paymentMethod = $purchaseOrder['type'];
							} else {
								$paymentMethod = 'Pay By Admin';
							}
						}
					}
				}

				$tempData                               = array();
				$tempData['orderNumber']                = $purchaseOrder['orderNumber'];
				$tempData['transactionId']              = $transactionId;
				$tempData['username']                   = ($purchaseOrder['username']) ? $purchaseOrder['username'] : '';
				$tempData['purchaseService']            = $purchasedService;
				$tempData['paymentMethod']              = $paymentMethod;
				$tempData['paymentStatus']              = $purchaseOrder['paymentStatus'] == "Expired" ? "Plan Expired by Customer Support" : $purchaseOrder['paymentStatus'];
				$tempData['totalAmount']                = ($purchaseOrder['totalAmount']) ? "$" . $purchaseOrder['totalAmount'] : '';
				$tempData['refundAmount']               = ($purchaseOrder['refundAmount']) ? "$" . $purchaseOrder['refundAmount'] : '';
				$tempData['purchaseDate']               = ($purchaseOrder['createdAt']) ? $purchaseOrder['createdAt']->format('M-d-Y H:i:s') : '';
				$tempData['companyName']                = $purchaseOrder['companyName'];
      	$tempData['ipAddress']                  = $purchaseOrder['ipAddress'];
				$purchaseHistoryData[$i]                = $tempData;
				$purchaseHistoryData[$i]['serviceData'] = '';

			$userServiceArr = $this->em->getRepository("DhiServiceBundle:servicePurchase")->getPoUserServce($purchaseOrder['poId']);

				if ($userServiceArr) {
					$purchaseHistoryDetail = array();
					foreach ($userServiceArr as $userService) {

						$servicePurchased = $userService;
						$serviceName = '';
						$tempArr = array();
						$tempArr['packageName'] = $servicePurchased['packageName'];
						$tempArr['packageActualAmount'] = $servicePurchased['actualAmount'];
						$tempArr['packagePaybleAmount'] = $servicePurchased['payableAmount'];
						$tempArr['paymentStatus'] = $servicePurchased['paymentStatus'];
						$tempArr['activationStatus'] = ($servicePurchased['rechargeStatus'] == 1 ? "Success" : ($servicePurchased['rechargeStatus'] == 2 ? "Failed" : ""));
						$tempArr['serviceLocation'] = (!empty($servicePurchased['serviceLocationId']) ? $servicePurchased['serviceLocationId'] : 'N/A');
						$tempArr['activationDate'] = '';
						$tempArr['expiryDate'] = '';
						$tempArr['service'] = '';
						$tempArr['validity'] = '';

						if ($servicePurchased['service'] && $servicePurchased['isCompensation'] != 1) {
							if ($servicePurchased['isAddon'] == 1) {
								$serviceName = 'AddOn';
							} else {
								$serviceName = strtoupper($servicePurchased['service']);
							}
							if ($serviceName) {
								$purchaseHistoryDetail[$serviceName][$servicePurchased['id']] = $tempArr;
							}
						}

						if ($servicePurchased['isCredit'] == 1 && $servicePurchased['isCredit']) {
							$credit = $servicePurchased['credit'];
							$tempArr['packageName'] = $credit . ' ExchangeVUE Credits';
							$purchaseHistoryDetail['Credit'][$servicePurchased['id']] = $tempArr;
						}

						if ($servicePurchased['isCompensation'] == 1) {
							$tempArr['service'] = strtoupper($servicePurchased['service']);
							$tempArr['validity'] = $purchaseOrder['compensationValidity'];
							$purchaseHistoryDetail['Compensation'][$servicePurchased['id']] = $tempArr;
						}

						// User service
						if ($userService['service']) {
							if ($userService['isAddon'] == 1) {
								$serviceName = 'AddOn';
							} else {
								$serviceName = strtoupper($userService['service']);
							}
							if ($userService['servicePurchaseId']) {
								$servicePurchaseId = $userService['servicePurchaseId'];
								if (!empty($userService['activationDate'])) {
									$activationDate = $userService['activationDate']->format('m/d/Y');
									$purchaseHistoryDetail[$serviceName][$servicePurchaseId]['activationDate'] = $activationDate;
								}
								if (!empty($userService['expiryDate'])) {
									$expiryDate = $userService['expiryDate']->format('m/d/Y');
									$purchaseHistoryDetail[$serviceName][$servicePurchaseId]['expiryDate'] = $expiryDate;
								}
							}
						}
					}
				}
				if (isset($purchaseHistoryDetail)) {
					$purchaseHistoryData[$i]['serviceData'][] = $purchaseHistoryDetail;
				}
				$i++;
			}
		}
		return $purchaseHistoryData;
	}

	public function setPromotionDiscount($servicePackageId, $promotionId) {
		if (!empty($servicePackageId) && !empty($promotionId)) {
			$servicePurchase = $this->em->getRepository("DhiServiceBundle:ServicePurchase")->find($servicePackageId);
			$promotion = $this->em->getRepository("DhiAdminBundle:promotion")->find($promotionId);

			if ($servicePurchase) {
				if ($servicePurchase->getIsAddon() == 0) {
					$payableAmount = $servicePurchase->getPayableAmount();
					$finalCost = $servicePurchase->getFinalCost();
					$discountType = $promotion->getAmountType();
					$discount = $promotion->getAmount();
					$puchaseType = $servicePurchase->getPurchaseType();
					if ($discountType == 'a') {
						$discountAmo = $discount;
						if ($discountAmo < $payableAmount) {
							if ($puchaseType == "BUNDLE") {
								$objBundle = $this->em->getRepository("DhiAdminBundle:Bundle")->findOneBy(array("bundle_id" => $servicePurchase->getBundleId()));
								$perAmount = $objBundle->getAmount();
								$discountAmo = $discount / 2;
							} else {
								$perAmount = $payableAmount;
							}
							$discountPer = ($discount * 100) / $perAmount;
							$payableAmount = $payableAmount - $discountAmo;
							$finalCost = $finalCost - $discountAmo;
						} else {
							$discountAmo = $payableAmount;
							$discountPer = 100;
							$payableAmount = 0;
							$finalCost = 0;
						}
					} else {
						$discountPer = $discount;
						$discountAmo = ($payableAmount * $discount) / 100;
						if ($discountAmo < $payableAmount) {
							$payableAmount = $payableAmount - $discountAmo;
							$finalCost = $finalCost - $discountAmo;
						} else {
							$discountAmo = $payableAmount;
							$payableAmount = 0;
							$finalCost = 0;
						}
					}
					$servicePurchase->setPayableAmount($payableAmount);
					$servicePurchase->setFinalCost($finalCost);
					$servicePurchase->setPromotion($promotion);
					$servicePurchase->setPromotionDiscountAmount($discountAmo);
					$servicePurchase->setPromotionDiscountPer($discountPer);
					$this->em->persist($servicePurchase);
					$this->em->flush();
				}
			}
		}
	}
        
    public function getUserLocationWiseChaseMID($userId){
        
        $objServiceLocationWiseMID = $this->em->getRepository('DhiAdminBundle:ServiceLocationWiseChaseMerchantId')->findOneBy(array('serviceLocation' => $this->session->get('serviceLocationId'), 'isDeleted' => 0));
        
        $mid = '';
        $chaseMerchantId = '';
        $response = array();
        $response['isDefaultChaseMID'] = 0;
        $response['isProfileExist'] = 0;
        $response['customerRefNum'] = '';
        
        if(!$objServiceLocationWiseMID){
            $objChaseMerchantId = $this->em->getRepository('DhiAdminBundle:ChaseMerchantIds')->findOneBy(array('isDefault' => 1));
            if($objChaseMerchantId){
                $mid = $objChaseMerchantId->getId();
                $chaseMerchantId = $objChaseMerchantId->getMerchantId();
                $response['isDefaultChaseMID'] = 1;
                
            }
        }else{
            $mid = $objServiceLocationWiseMID->getChaseMerchantIds()->getId();
            $chaseMerchantId = $objServiceLocationWiseMID->getChaseMerchantIds()->getMerchantId();
        }
        $objUserChaseInfo = $this->em->getRepository('DhiUserBundle:UserChaseInfo')->findOneBy(array('user' => $userId, 'merchantId' => $mid));
        
        $response['merchantId'] = $chaseMerchantId;
        $response['chaseMerchantPID'] = $mid;
        
        if($objUserChaseInfo){
            $response['customerRefNum'] = $objUserChaseInfo->getCustomerRefNum();
            $response['isProfileExist'] = 1;
        }
        
        return $response;
        
    }

    public function checksiteWiseLocation()
    {
		$availableServiceLocationOnSite = $this->get('session')->get('availableServiceLocationOnSite');
		$serviceLocationId              = $this->get('session')->get('serviceLocationId');
		$returnFlag = false;
		if (!empty($serviceLocationId)) {
			if(in_array($serviceLocationId, $availableServiceLocationOnSite)){
				$returnFlag = true;
			}
		}
		return $returnFlag;
    }

    public function getSiteFromPackage($packageId)
    {
    	$package = $this->em->getRepository("DhiAdminBundle:Package")->findOneBy(array('packageId' => $packageId));
		if ($package) {
			$serviceLocation = $package->getServiceLocation();
			if ($serviceLocation) {
				$locationWiseSite = $this->em->getRepository("DhiAdminBundle:ServiceLocationWiseSite")->findOneBy(array('serviceLocation' => $serviceLocation, 'isDeleted' => 0));

				if (!empty($locationWiseSite) && is_object($locationWiseSite)) {
					$site = $locationWiseSite->getWhiteLabel();
					if ($site) {
						if ($site->getIsDeleted() == 0) {
							return $site;
						}
					}
				}
			}
		}

		return null;
    }

	public function getOneDayUpgradeDetails($objUserService, $servicePurchase){
		$response = array();
		$validity = $objUserService->getActualValidity();
		$oldServicePurchase = $objUserService->getServicePurchase();
		if($validity == 1 && $oldServicePurchase->getValidityType() == "DAYS"){
		    if ($objUserService->getIsPlanActive() == 1) {
				$response['activationDate'] = clone $objUserService->getActivationDate();
				$preExpiryDate              = clone $objUserService->getExpiryDate();
				$response['expiryDate']     = $preExpiryDate->modify('+'.$servicePurchase->getValidity().' '.$servicePurchase->getValidityType());
				$response['validity']       = $validity + $servicePurchase->getValidity();
		    }
		}
		return $response;
	}
}
