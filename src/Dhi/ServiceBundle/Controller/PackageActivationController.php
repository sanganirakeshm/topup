<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\ServiceBundle\Entity\ServiceActivationFailure;
use Dhi\UserBundle\Entity\UserCreditLog;
use Dhi\UserBundle\Entity\UserCredit;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Dhi\AdminBundle\Entity\UserIsp;
use Dhi\AdminBundle\Entity\Package;
use \DateTime;

class PackageActivationController extends Controller {

	protected $container;
	protected $em;
	protected $session;
	protected $securitycontext;
	protected $paymentProcess;
	protected $aradial;
	protected $DashboardSummary;

	public function __construct($container) {

		$this->container = $container;

		$this->em = $container->get('doctrine')->getManager();
		$this->session = $container->get('session');
		$this->securitycontext = $container->get('security.context');
		$this->paymentProcess = $container->get('paymentProcess');
		$this->DashboardSummary = $container->get('DashboardSummary');
		$this->UserLocationWiseService = $container->get('UserLocationWiseService');
		$this->aradial = $container->get('aradial');
	}

	public function activateVodService($user, $type = 'user', $userServiceLocation = '') {
		$em = $this->em; //Init Entity Manager
		$purchaseOrderId = $this->get('session')->get('PurchaseOrderId');
		
		$userServiceLocation = $this->get('UserLocationWiseService')->getUserServiceLocationFromAdmin($user);
		$userServiceLocation = array_keys($userServiceLocation);


		$isTVODForRefund = false;


		if (in_array('TVOD', $userServiceLocation)) {

			$serviceTVOD = $this->em->getRepository('DhiUserBundle:Service')->findOneByName('TVOD');
			$servicePurchases = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->getPaymentCompletedData($user, $purchaseOrderId, $serviceTVOD->getId(), 0);

			if($servicePurchases){

				foreach ($servicePurchases as $servicePurchase) {

					//Active TVOD service
					$selevisionService = $this->get('selevisionService');
                    $currentDate = new \DateTime(date('Y-m-d 23:59:59'));
                    $expiryDate = $currentDate->modify('+' . $servicePurchase->getValidity().' '.$servicePurchase->getValidityType());

					$wsOfferParam = array();
					$wsOfferParam['cuLogin']   = $servicePurchase->getUser()->getUserName();
					$wsOfferParam['idContent'] = (int)$servicePurchase->getPackageId();
                    $wsOfferParam['expire']    = $expiryDate->format('Y-m-d');
					$wsRes = $selevisionService->callWSAction('tvodPurchase', $wsOfferParam);
					$rechargeStatus = 0;
					if (!empty($wsRes['status']) && $wsRes['status'] == 'success')  {
						$rechargeStatus = 1;
					}

					if(!empty($wsRes[0]) && $wsRes[0] == 'success'){
						$rechargeStatus = 1;
					}

					if($rechargeStatus != 1){
						$message = !empty($wsRes['detail'])? $wsRes['detail'] : '';
						$isTVODForRefund = true;
						$rechargeStatus = 2;
						$servicePurchase->setPaymentStatus('NeedToRefund');
					}

					$servicePurchase->setRechargeStatus($rechargeStatus);
					$this->em->persist($servicePurchase);
					$this->em->flush();

					if ($rechargeStatus == 1) {
						$this->paymentProcess->storeActiveUserService($servicePurchase);
					}
				}
			}

			if ($isTVODForRefund){
				return array('status'=>true, 'reason' => $message);
			}
		}else{
			return array('status'=>true, 'reason' => "Service not available");
		}

		return array('status'=>false, 'reason' => '');
	}

	public function activateServicePack($user, $type = 'user', $userServiceLocation = '') {
		$em = $this->em; //Init Entity Manager
		$purchaseOrderId = $this->get('session')->get('PurchaseOrderId');
		$IsISPAvailabledInCart = $this->get('session')->get('IsISPAvailabledInCart');
		$IsIPTVAvailabledInCart = $this->get('session')->get('IsIPTVAvailabledInCart');

		if ($userServiceLocation == '' && $type == 'user') {

			$userServiceLocation = $this->get('UserLocationWiseService')->getUserLocationService();
			$userServiceLocation = array_keys($userServiceLocation);
		}

		$isISPForRefund = false;
		$isIPTVForRefund = false;
		$isAddOnForRefund = false;

		$checkISPForIPTV = false;
		$isISPActivated = false;
		$isIPTVActivated = false;
                $isBUNDLE  = false;


                if (in_array('BUNDLE', $userServiceLocation) && $IsISPAvailabledInCart && $IsIPTVAvailabledInCart){
                    $isBUNDLE = true;
                }
                        
		if ((in_array('IPTV', $userServiceLocation) && in_array('ISP', $userServiceLocation)) || $isBUNDLE) {

			if ($IsISPAvailabledInCart == 1 && $IsIPTVAvailabledInCart == 1) {

				$checkISPForIPTV = true;
			}
		}

		if ((in_array('ISP', $userServiceLocation)) || $isBUNDLE) {
            $serviceISP = $this->em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
            // echo "IPTV in CART:".$IsIPTVAvailabledInCart;
            $isISPForRefund = $this->activationProcess($user, $serviceISP, false, '', $IsIPTVAvailabledInCart);
		}

		if ((in_array('IPTV', $userServiceLocation)) || $isBUNDLE) {

			$serviceIPTV = $this->em->getRepository('DhiUserBundle:Service')->findOneByName('IPTV');

			//Active IPTV service
			if ($checkISPForIPTV) {


				$isISPActivated = $this->em->getRepository('DhiUserBundle:UserService')->findBy(array('user' => $user, 'service' => $serviceISP->getId(), 'status' => '1', 'isAddon' => 0));

				if ($isISPActivated && !$isISPForRefund) {

					$isIPTVForRefund = $this->activationProcess($user, $serviceIPTV);

					//If IPTV fail then also inactive ISP
					if ($isIPTVForRefund) {

						$isISPForRefund = $this->iptvFailedOnInActiveISP($user, $serviceISP);
						$this->get("session")->set('reCreateISP', true);
					}else{
						$this->deleteIspUser($user->getUserName());	
					}
				} else {

					$isIPTVForRefund = $this->activationProcess($user, $serviceIPTV, true);
					$this->deleteIspUser($user->getUserName());
				}
			} else {

				if (!array_key_exists('ISP', $userServiceLocation)) {

					$isIPTVForRefund = $this->activationProcess($user, $serviceIPTV);
					$this->deleteIspUser($user->getUserName());
				}
			}

			//Active IPTV AddOn Package
			$isIPTVActivated = $this->em->getRepository('DhiUserBundle:UserService')->findBy(array('user' => $user, 'service' => $serviceIPTV->getId(), 'status' => '1', 'isAddon' => 0));

			if ($isIPTVActivated) {

				$isAddOnForRefund = $this->activationProcess($user, $serviceIPTV, false, 'AddOn');
			} else {

				$isAddOnForRefund = $this->activationProcess($user, $serviceIPTV, true, 'AddOn');
			}
		}

		if ($isISPForRefund || $isIPTVForRefund || $isAddOnForRefund) {

			return true;
		}

		return false;
	}

	public function activationProcess($user, $service, $defaultRefund = false, $type = '', $IsIPTVAvailabledInCart = 0) {

		$purchaseOrderId = $this->get('session')->get('PurchaseOrderId');
		$paymentRefundStatus = false;
		$isAddonPackage = 0;

		if ($type == 'AddOn') {

			$isAddonPackage = 1;
		}

		if ($service) {

			//Get Service Activation Data
			$servicePurchases = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->getPaymentCompletedData($user, $purchaseOrderId, $service->getId(), $isAddonPackage);

			if ($servicePurchases) {

				foreach ($servicePurchases as $servicePurchase) {

					if ($servicePurchase->getIsCredit() == 0) {

						$activationStatus = false;
						$serviceName = strtoupper($servicePurchase->getService()->getName());

						if (!$defaultRefund) {

							//IPTV package activation process
							if ($serviceName == 'IPTV') {

								$activationStatus = $this->iptvProcess($user, $servicePurchase);

								if ($activationStatus) {

									$this->unsetActiveIPTVPackage($user, $isAddonPackage, $servicePurchase);
								}
							}

							//ISP package activation process
							if ($serviceName == 'ISP') {
                // add  user session  history before update
                $aradial = $this->get('aradial');
                $userStatusInAradial = $aradial->checkUserExistsInAradial($user->getUsername());

                if($userStatusInAradial['status'] == 1) {

					$wsParam                           = array();
					$wsParam['Page']                   = 'UserSessions';
					$wsParam['SessionsMode']           = 'UsrAllSessions';
					$wsParam['qdb_Users.UserID']       = $user->getUserName();
					$wsParam['op_$D$AcctSessionTime']  = "<>";
					$wsParam['qdb_$D$AcctSessionTime'] = " ";

                    $wsResponse = $aradial->callWSAction('getUserSessionHistory', $wsParam);

                    if (isset($wsResponse['userSession']) && $wsResponse['userSession'] != null) {
                        foreach ($wsResponse['userSession'] as $userDetails) {
                            $userName = $userDetails['UserID'];
                            $nasName = $userDetails['NASName'];
                            $startTime = $userDetails['InTime'];
                            $stopTime = $userDetails['TimeOnline'];
                            $framedAddress = $userDetails['FramedAddress'];
                            $callerId = $userDetails['CallerId'];
                            $calledId = $userDetails['CalledId'];
                            $acctSessionTime = $userDetails['AcctSessionTime'];

                            $isRefunded = 1;
                            $email = '';

                            if(!empty($acctSessionTime)) {

	                            $em = $this->getDoctrine()->getManager();
	                            if (!empty($userName)) {
	                                $userData = $em->getRepository('DhiUserBundle:User')->getEmailForAradialUser($userName);
	                            }

	                            if (!empty($userData)) {
	                                $email = $userData[0]['email'];
	                            } else {
	                                $email = '';
	                            }


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
                                if (!empty($startTime)) {
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
                            }
                        }
                    }
                 }
                //stop
                                $activationStatus = 0;
                                if ($this->session->has('isExtendISP')) {
                                         if($this->session->get('isExtendISP') == true){
                                         		$objUserService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array('user' => $user, 'service' => $servicePurchase->getService()->getId(), 'packageId' => $servicePurchase->getPackageId(), 'status' => 1), array('id' => 'DESC'));

												$extendCurrentExpDate = $objUserService->getExpiryDate();
												$extendActivationDate = $objUserService->getActivationDate();
												$extendExpiryDate =  clone $extendCurrentExpDate;
												$extendExpiryDate->modify('+'.$servicePurchase->getValidity().' '.$servicePurchase->getValidityType());

												$this->aradial->updateUserIsp($user, $extendActivationDate, $extendExpiryDate);
                                                $activationStatus = 1;
                                         }
                                }

                                if($activationStatus == 0){
                            		$planDates = array();
                            		$objUserService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array('user' => $user, 'service' => $servicePurchase->getService(), 'validity' => 1, 'status' => 1), array('id' => 'DESC'));
                            		if ($objUserService) {
                            			$oneDayUpgradeDetails = $this->DashboardSummary->getOneDayUpgradeDetails($objUserService, $servicePurchase);
                            			if (!empty($oneDayUpgradeDetails)) {
                            				$planDates['activationDate'] = $oneDayUpgradeDetails['activationDate'];
                    						$planDates['expiryDate']     = $oneDayUpgradeDetails['expiryDate'];
                            			}
                            		}
                            		$activationStatus = $this->ispProcess($user, $servicePurchase, $IsIPTVAvailabledInCart, $planDates);
                                    if ($activationStatus) {
                                      $this->unsetActiveISPPackage($user);
                              		}
                                }
                        }
                }

						if ($activationStatus) {

							$rechargeStatus = 1;
						} else {

                                                 	$rechargeStatus = 2;
							$servicePurchase->setPaymentStatus('NeedToRefund');
							$paymentRefundStatus = true;
                        			}

						//Update Service Purchase
						$servicePurchase->setRechargeStatus($rechargeStatus);

						$this->em->persist($servicePurchase);
						$this->em->flush();

						if ($rechargeStatus == 1) {

							$this->paymentProcess->storeActiveUserService($servicePurchase);
						}
					}
				}

				return $paymentRefundStatus;
			}
		}

		return false;
	}

	public function iptvProcess($user, $service) {
		$em = $this->em; //Init Entity Manager
		$newUser = 0;

		// check selevision api to check whether customer exist in system
		$wsParam = array();
		$wsParam['cuLogin'] = $user->getUsername();

		$selevisionService = $this->get('selevisionService');
		$userExist = $selevisionService->checkUserExistInSelevision($user);

		$flagExtendPlan = false;

		if ($userExist['status']) {
			if ($this->session->has('isExtendIPTV')) {
				if ($this->session->get('isExtendIPTV') == true) {
					$flagExtendPlan = true;
				}
			}

			if ($this->session->has('isAddonExtendIPTV')) {
				if ($this->session->get('isAddonExtendIPTV') == true) {
					$flagExtendPlan = true;
				}
			}

			if ($flagExtendPlan) {
				return true;

			} else {
				$selevisionService = $this->get('selevisionService');

				// Check if plan is already activated or not
				$selevisionIPTVActivePackageIds = $selevisionService->getActivePackageIds($user->getUserName());
				if(in_array($service->getPackageId(), $selevisionIPTVActivePackageIds)){
					return true;
				}

				//Call Selevision webservice for set package
				$wsOfferParam = array();
				$wsOfferParam['cuLogin'] = $user->getUserName();
				$wsOfferParam['offer'] = $service->getPackageId();
				$wsRes = $selevisionService->callWSAction('setCustomerOffer', $wsOfferParam);

				if ($wsRes['status'] == 1) {

					return true;
				} else {

					$error = (isset($wsRes['detail'])) ? $wsRes['detail'] : $service->getPackageName() . ' faild to activate.';
					$this->packageActivationFailure($user, $error, $service);
				}
			}
		}

		return false;
	}

	public function unsetActiveIPTVPackage($user, $isAddonPackage, $servicePurchase) {

		$em = $this->em; //Init Entity Manager
		$service = $em->getRepository('DhiUserBundle:Service')->findOneByName('IPTV');

		$condition = array('user' => $user, 'status' => 1, 'service' => $service, 'isAddon' => $isAddonPackage);
		$objUserService = $em->getRepository('DhiUserBundle:UserService')->findBy($condition);

		if ($objUserService) {

			foreach ($objUserService as $activeService) {

				if ($activeService->getIsAddon() == 0) {
					$flagExtendPlan = false;
					if ($this->session->has('isExtendIPTV')) {
						if ($this->session->get('isExtendIPTV') == true) {
							$flagExtendPlan = true;
						}
					}

					if ($this->session->has('isAddonExtendIPTV')) {
						if ($this->session->get('isAddonExtendIPTV') == true) {
							$flagExtendPlan = true;
						}
					}
					if($flagExtendPlan){
						$wsResponse = array('status' => 1);

					}else{
						$packageId = $activeService->getPackageId();

						if($servicePurchase->getPackageId() != $packageId){
						
							//Call Selevision webservice for unset package
							$wsOfferParam = array();
							$wsOfferParam['cuLogin'] = $user->getUserName();
							$wsOfferParam['offer'] = $packageId;

							$selevisionService = $this->get('selevisionService');
							$wsResponse = $selevisionService->callWSAction('unsetCustomerOffer', $wsOfferParam);

						}else{
							$wsResponse = array('status' => 1);
						}
					}

					if ($wsResponse['status'] == 1) {

						$activeService->setStatus(0);
						$em->persist($activeService);
						$em->flush();
					}
				}
			}
		}
	}

	public function ispProcess($user, $service, $IsIPTVAvailabledInCart = 0, $planDates = array()) {
		######## Update Offer in Aradial ####################
		if (!empty($planDates)) {
			$activationDate = $planDates['activationDate'];
			$expiryDate = $planDates['expiryDate'];

		}else{
			$currentDate = new \DateTime(date('Y-m-d 23:59:59'));
			$expiryDate = $currentDate->modify('+' . $service->getValidity().' '.$service->getValidityType());
			$activationDate = new \DateTime(date('Y-m-d H:i:s'));
		}
		$aradialResponse = 0;
		$isEmployee = $user->getIsEmployee();

		// Service call removed
		if ($this->session->has('isExtendISP')) {
			if ($this->session->get('isExtendISP') == true) {
				$aradialResponse = 1;
			}
		}
		else if(($this->session->has('expiredPackage'))) {
			if($this->session->get('expiredPackage') == 1){
				$aradialResponse = 1;
			}
		}

		if($aradialResponse == 0){
            $isispOnlyPurchase = 0;
            if($this->get("session")->has('isISPOnly')){
                    if($this->get("session")->get('isISPOnly') == true){
                            $isispOnlyPurchase = 1;
                    }
            }
            
            if($this->get("session")->has('isISPOnlyUpgrade')){
                    if($this->get("session")->get('isISPOnlyUpgrade') == true){
                            $isispOnlyPurchase = 1;
                    }
            }

            $extraParam = array();
            $extraParam['db_$N$Users.Offer'] = $service->getPackageId();
            $extraParam['charge'] = $service->getActualAmount();
            $extraParam['db_$D$Users.StartDate'] = $activationDate->format('m/d/Y H:i:s');
        	$extraParam['db_$D$Users.UserExpiryDate'] = $expiryDate->format('m/d/Y H:i:s');
            $aradialResponse = $this->aradial->createUser($user, $extraParam, $IsIPTVAvailabledInCart);
		}

		if ($aradialResponse == 1) {
			$checkUserBalance = $this->aradial->getUserBalance($user->getUsername());
			if($checkUserBalance['status'] == 'fail' || $checkUserBalance['balance'] != 0){
				$user->setIsFlaggedForIspBalTrans(true);
				$this->em->persist($user);
				$this->em->flush();
			}
                                        return true;
		} else {
                                        $error = (isset($wsRes['reason'])) ? $wsRes['reason'] : $service->getPackageName() . ' faild to activate.';
                                        $this->packageActivationFailure($user, $error, $service);
		}
		############ END Here ##################################

		return false;
	}

	public function unsetActiveISPPackage($user) {
		$aradialResponse = 0;
		if ($this->session->has('isExtendISP')) {
			if ($this->session->get('isExtendISP') == true) {
				$aradialResponse = 1;
			}
		}else if(($this->session->has('expiredPackage'))) {
			if($this->session->get('expiredPackage') == 1){
				$aradialResponse = 1;
			}
		}

		if($aradialResponse == 0){
			$em = $this->em; //Init Entity Manager
			$service = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');

			$condition = array('user' => $user, 'status' => 1, 'service' => $service);
			$objUserService = $em->getRepository('DhiUserBundle:UserService')->findBy($condition);

			if ($objUserService) {

				foreach ($objUserService as $activeService) {

					$packageId = $activeService->getPackageId();
	                $this->session->set('ISPSetStatusunset', $activeService->getId());
					$activeService->setStatus(0);
					$em->persist($activeService);
					$em->flush();
				}
			}
		}
	}

	public function packageActivationFailure($user, $error, $servicePurchase) {

		$em = $this->em; //Init Entity Manager
		//insert error to failure table
		$packageActivationFailure = new ServiceActivationFailure();

		$packageActivationFailure->setUser($user);
		$packageActivationFailure->setServices($servicePurchase->getService());
		$packageActivationFailure->setServicePurchases($servicePurchase);
		$packageActivationFailure->setPackageId($servicePurchase->getPackageId());
		$packageActivationFailure->setPackageName($servicePurchase->getPackageName());
		$packageActivationFailure->setFailureDescription($error);

		$em->persist($packageActivationFailure);
		$em->flush();
                
               $serviceName = $servicePurchase->getService()->getName();
                $setting         = $this->em->getRepository("DhiAdminBundle:Setting")->findOneByName('service_failed_notification_email');
                                                       
                if($setting->getValue()){
                    $numberofnotifcationeemail = explode(',', $setting->getValue());
                    $servicetype = $serviceName;
                    $type        = 'Activate';
                    
                    
                    //$this->sendNotification($servicetype,$type,$user,$setting->getValue());
                    if(count($numberofnotifcationeemail)>0){
                        foreach ($numberofnotifcationeemail as $email){
                            $this->sendNotification($servicetype,$type,$user,$email);
                        }
                    }
                }

		$insertIdFailure = $packageActivationFailure->getId();
	}

	public function addCreditInUserAccount($user) {

		$em = $this->em; //Init Entity Manager
		$purchaseOrderId = $this->get('session')->get('PurchaseOrderId');

		//Get Service Activation Data
		$servicePurchases = $em->getRepository('DhiServiceBundle:ServicePurchase')->getPaymentCompletedData($user, $purchaseOrderId);

		$isRefundPayment = false;
		$rechargeStatus = 0;
		if ($servicePurchases) {

			foreach ($servicePurchases as $servicePurchase) {

				if ($servicePurchase->getIsCredit() == 1) {

					$credit = $servicePurchase->getCredit()->getCredit();
					$userCreditPurchase = $credit;
					$amount = $servicePurchase->getPayableAmount();

					$objUserCreditLog = new UserCreditLog();

					$objUserCreditLog->setUser($user);
					$objUserCreditLog->setPurchaseOrder($servicePurchase->getPurchaseOrder());
					$objUserCreditLog->setCredit($credit);
					$objUserCreditLog->setAmount($amount);
					$objUserCreditLog->setTransactionType('Credit');

					$em->persist($objUserCreditLog);
					$em->flush();

					if ($objUserCreditLog->getId()) {

						$objUserCredit = $user->getUserCredit();

						if (!$objUserCredit) {

							$objUserCredit = new UserCredit();
						} else {
							$credit = $credit + $objUserCredit->getTotalCredits();
						}

						$objUserCredit->setUser($user);
						$objUserCredit->setTotalCredits($credit);
						$em->persist($objUserCredit);
						$em->flush();

						if ($objUserCredit->getId()) {

							$rechargeStatus = 1;

							// add user credit
							$wsParam = array();
							$wsParam['cuLogin'] = $user->getUsername();
							$wsParam['credit'] = $userCreditPurchase;

							$selevisionService = $this->get('selevisionService');
							$wsRespose = $selevisionService->callWSAction('giveCustomerCredit', $wsParam);
						} else {

							$isRefundPayment = true;
							$servicePurchase->setPaymentStatus('NeedToRefund');
						}
					}

					$servicePurchase->setRechargeStatus($rechargeStatus);
					$em->persist($servicePurchase);
					$em->flush();
				}
			}
		}

		return $isRefundPayment;
	}

	public function iptvFailedOnInActiveISP($user, $service) {

		$purchaseOrderId = $this->get('session')->get('PurchaseOrderId');
		$paymentRefundStatus = false;

		if ($service) {

			//Get Service Activation Data
			$servicePurchases = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->getPaymentCompletedData($user, $purchaseOrderId, $service->getId());

			if ($servicePurchases) {

				foreach ($servicePurchases as $servicePurchase) {

					if ($servicePurchase->getIsCredit() == 0) {

						$servicePurchaseId = $servicePurchase->getId();

						$activationStatus = false;
						$serviceName = strtoupper($servicePurchase->getService()->getName());

						// $this->unsetActiveISPPackage($user);

						// Delete user from aradial
						// $cancelUsrResponse = $this->get('aradial')->deleteUserFromAradial($user->getUserName());
						// $logoutUsrResponse = $this->get('aradial')->logoutUserFromAradial($user);

						$paymentRefundStatus = true;

						//Update Service Purchase
						$servicePurchase->setPaymentStatus('NeedToRefund');
						$servicePurchase->setRechargeStatus(2);

						$this->em->persist($servicePurchase);
						$this->em->flush();

						$query = $this->em->createQuery("DELETE DhiUserBundle:UserService ur WHERE ur.servicePurchase = '" . $servicePurchaseId . "'");
						$query->execute();
					}
				}
			}
		}

		return $paymentRefundStatus;
	}

	public function storeIspUser($userName){
		$aradial = $this->get("aradial");
		$wsParam = array();
		$wsParam['Page']             = 'UserSessions';
		$wsParam['SessionsMode']     = 'UsrAllSessions';
		$wsParam['qdb_Users.UserID'] = $userName;
		$wsResponse                  = $aradial->checkUserExistsInAradial($userName);

        if ($wsResponse['status'] == 1) {
        	$removeISPUser = $this->deleteIspUser($userName);
    		$user = $this->em->getRepository("DhiUserBundle:User")->findOneBy(array('username'=>$userName));
    		$userIsp = new UserIsp();
			$userIsp->setUserID($user->getUsername());
			$userIsp->setFirstName($user->getFirstname());
			$userIsp->setLastName($user->getLastname());
			$userIsp->setEmail($user->getEmail());

			$service = $this->em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
			$condition = array('user' => $user, 'status' => 1, 'service' => $service, 'isAddon' => 0);
			$objUserService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy($condition);
            if($objUserService){
            	$userServiceId = $objUserService->getId();
            	if(!empty($userServiceId)){
					$userIsp->setOffer($objUserService->getId());
				}
	        }
			$this->em->persist($userIsp);
			$this->em->flush();
			return true;
        }
        return false;
	}

	public function deleteIspUser($userName){
		$userIsp = $this->em->getRepository("DhiAdminBundle:UserIsp")->findOneBy(array('UserID'=>$userName));
		if($userIsp){
			$this->em->remove($userIsp);
			$this->em->flush();
			return true;
		}
		return false;
	}

	public function reCreateIspUser($userName)
	{
		$ispUser = $this->em->getRepository("DhiAdminBundle:UserIsp")->findOneBy(array('UserID'=>$userName));
		$user = $this->em->getRepository("DhiUserBundle:User")->findOneBy(array('username'=>$userName));
		$isPurchase = false;
		if($ispUser && $user){
			$extraParam = array();
            $service = $this->em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
			//$condition = array('user' => $user, 'status' => 1, 'service' => $service, 'isAddon' => 0);
			$userUserviceId = $ispUser->getOffer();
			if(!empty($userUserviceId)){
				$objUserService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array("id"=>$userUserviceId));
	            if($objUserService){
					$extraParam['db_$N$Users.Offer'] = $objUserService->getPackageId();
					$extraParam['db_$D$Users.StartDate'] = $objUserService->getActivationDate()->format('m/d/Y H:i:s');
		        	$extraParam['db_$D$Users.UserExpiryDate'] = $objUserService->getExpiryDate()->format('m/d/Y H:i:s');
                    $extraParam['charge'] = $objUserService->getActualAmount();
		        	$isPurchase = true;
		        }
	    	}
	    	$cancelUsrResponse = $this->aradial->deleteUserFromAradial($userName);
                
                /* 
                 * Send notfication when ISP deactivation failed.
                 */
                $setting         = $this->em->getRepository("DhiAdminBundle:Setting")->findOneByName('service_failed_notification_email');
                if(!$cancelUsrResponse && $setting->getValue()){
                    $numberofnotifcationeemail = explode(',', $setting->getValue());
                    $servicetype = 'ISP';
                    $type        = 'deactivate';
                    //$this->sendNotification($servicetype,$type,$user,$setting->getValue());
                    if(count($numberofnotifcationeemail)>0){
                        foreach ($numberofnotifcationeemail as $email){
                            $this->sendNotification($servicetype,$type,$user,$email);
                        }
                    }
                }
	    $logoutUsrResponse = $this->aradial->logoutUserFromAradial($user);
            $aradialResponse = $this->aradial->createUser($user, $extraParam, 0, true);
            if($aradialResponse == true){
            	if($isPurchase == true && isset($objUserService)){
            		$objUserService->setStatus(1);
            		$this->em->persist($objUserService);
            		$this->em->flush();
            	}
            	$this->deleteIspUser($userName);
            	return true;
            }else{
            	return false;
            }
		}
        return false;
	}
        
        /*
         * Send notification to user, When IPTv or ISP failed at Active Or DeActivation time.
         * Auther: Chetan Raiyani
         * Date: 18-01-2016
         */
        
        public function sendNotification($servicetype, $type,$user,$toEmail) {
            
            $notificationData = array();
            $notificationData['servicetype'] = $servicetype;
            $notificationData['type']        = $type;
            $notificationData['firstname']   = $user->getFirstname(); 
            $notificationData['lastname']   = $user->getLastname(); 
            $notificationData['userName']   = $user->getUsername(); 
            
            $session = $this->container->get('session');
            $whitelabel = $session->get('brand');
            if($whitelabel){
                $subject   = $whitelabel['name'].' - Failed Service';
                $fromEmail = $whitelabel['fromEmail'];
                $fromName  = $whitelabel['name'];
                $compnayname = $whitelabel['name'];
                $compnaydomain = $whitelabel['domain'];
                $supportpage   = $whitelabel['supportpage'];
            } else {
                $subject         = 'ExchangeVUE - Failed Service';
                $fromEmail       = $this->container->getParameter('purchase_summary_from_email');
                $fromName        = $this->container->getParameter('purchase_summary_from_name'); 
                $compnayname     = 'ExchangeVUE';
                $compnaydomain   = 'exchangevue.com';
                $supportpage     = 'https://www.facebook.com/dhitelecom';
            }
            
             
            
            $emailBody = $this->render('DhiServiceBundle:Emails:notificationEmail.html.twig', array('notificationData' => $notificationData,'companyname'=>$compnayname,'companydomain'=>$compnaydomain,'supportpage'=>$supportpage));
            $toEmail         = $toEmail;
            $body            = $emailBody->getContent();
            
            try {
                
                $message = \Swift_Message::newInstance()
                            ->setSubject($subject)
                            ->setFrom(array($fromEmail => $fromName))
                            ->setTo($toEmail)
                            ->setBody($body)
                            ->setContentType('text/html');
                if($this->container->get('mailer')->send($message)){
                   $sendEmailError = true; 
                }
                
            } catch (Exception $ex) {
               $sendEmailError = false;
            }
            return $sendEmailError;
        }

    public function syncPackages() {

        $response = array();
        $response['successMsg'] = '';
        $response['errorMsg'] = '';
        $bundlePackages = $ispBundlePackages = $iptvBundlePackages = array();

        $em = $this->em;
        $connection = $em->getConnection();

        $arrOldPackages = $this->getOldPackages($em);

        $querPackage = $em->createQuery('DELETE DhiAdminBundle:Package p WHERE p.packageType = :pType')->setParameter("pType", "ISP");
        $querPackage->execute();

        #################### Store ISP Package #########################################
	$ispPackageCount = 0;
        $offers = $this->container->get('aradial')->getOffer();
        $currentDate = new \DateTime();

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
                    $response['successMsg'][] = "ISP Packages added successfully";
                } else {
                    $response['errorMsg'][] = "ISP Packages not found";
                }
            } else {
                $response['errorMsg'][] = "ISP Packages not found";
            }
        } else {
            $response['errorMsg'][] = "Something went wrong in aradial api request.";
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

        return $response;
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
