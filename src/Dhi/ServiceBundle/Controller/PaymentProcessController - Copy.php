<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Symfony\Component\HttpFoundation\Response;
use Dhi\ServiceBundle\Model\ExpressCheckout;
use Dhi\UserBundle\Entity\User;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Form\Type\BillingAddressFormType;
use Dhi\ServiceBundle\Entity\Milstar;
use Dhi\ServiceBundle\Entity\ServiceApiErrorLog;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;
use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserCreditLog;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Dhi\UserBundle\Entity\UserCredit;

class PaymentProcessController extends Controller {

    protected $container;
    protected $em;
    protected $session;
    protected $securitycontext;

    public function __construct($container) {

        $this->container = $container;
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');

        $this->DashboardSummary   = $container->get('DashboardSummary');
        $this->GeoLocation        = $container->get('GeoLocation');
        $this->ActivityLog        = $container->get('ActivityLog');
        //$this->milstar            = $container->get('milstar');
    }

    public function getCartItemForPayment(){

        $summaryData = $this->DashboardSummary->getUserServiceSummary();

        $paymentCart = array();
        $totalCartAmount = 0;
        if($summaryData['CartAvailable'] == 1){

            $Cart = $summaryData['Cart'];
            $tempCart = array();

            if($summaryData['IsISPAvailabledInCart'] == 1){

                foreach ($Cart['ISP']['RegularPack'] as $package){

                    $tempArr['name'] = 'Internet package';
                    $tempArr['desc'] = 'Package: '.$package['packageName'];
                    $tempArr['amt']  = $package['payableAmount'];
                    $totalCartAmount +=$package['payableAmount'];
                    $paymentCart[] = $tempArr;
                }
            }
            if($summaryData['IsIPTVAvailabledInCart'] == 1){

                foreach ($Cart['IPTV']['RegularPack'] as $package){

                    $tempArr['name'] = 'ExchangeVUE package';
                    $tempArr['desc'] = 'Package: '.$package['packageName'];
                    $tempArr['amt']  = $package['payableAmount'];
                    $totalCartAmount +=$package['payableAmount'];
                    $paymentCart[] = $tempArr;
                }
            }
            if($summaryData['IsAddOnAvailabledInCart'] == 1){

                foreach ($Cart['IPTV']['AddOnPack'] as $package){

                    $tempArr['name'] = 'ExchangeVUE Addons package';
                    $tempArr['desc'] = 'Package: '.$package['packageName'];
                    $tempArr['amt']  = $package['payableAmount'];
                    $totalCartAmount +=$package['payableAmount'];
                    $paymentCart[] = $tempArr;
                }
            }

            if(isset($paymentCart) && !empty($paymentCart)){

                if((int)$totalCartAmount == (int) $summaryData['TotalCartAmount']){

                    return $paymentCart;
                }
            }
        }
        return false;
    }

    public function updateServicePurchaseData($queryCase,$updateFields,$type = 'condition'){

        //Get Service Purchase
        if($type == 'object'){

            $objServicePurchase = $queryCase;
        }else{

            $objServicePurchase = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->findBy($queryCase);
        }

        foreach ($objServicePurchase as $ServicePurchase){

            if(array_key_exists('paymentStatus',$updateFields)){

                $ServicePurchase->setPaymentStatus($updateFields['paymentStatus']);
            }
            if(array_key_exists('paypalCheckout',$updateFields)){

                $ServicePurchase->setPaypalCheckout($updateFields['paypalCheckout']);
            }

            if(array_key_exists('sessionId',$updateFields)){

                $ServicePurchase->setSessionId($updateFields['sessionId']);
            }
            if(array_key_exists('paymentMethod',$updateFields)){

                $ServicePurchase->setPaymentMethod($this->em->getRepository('DhiServiceBundle:PaymentMethod')->findOneById($updateFields['paymentMethod']));
            }

            $ServicePurchase->setUpdatedAt(new \DateTime(date('Y-m-d H:i:s')));

            $this->em->persist($ServicePurchase);
            $this->em->flush();
        }
    }

    public function storeMilstarResponse($milsatParams){

        $user            = $this->securitycontext->getToken()->getUser();
        $purchaseOrderId = $this->session->get('PurchaseOrderId');

        if(isset($milsatParams['refundBy'])){

            if($milsatParams['refundBy'] == 'admin'){

                $purchaseOrderId = $milsatParams['purchaseOrderId'];
            }
        }

        $purchaseOrder = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);

        if(!$purchaseOrder->getMilstar()){

            $objMilstar = new Milstar();
        }else{

            $objMilstar = $purchaseOrder->getMilstar();
        }

        $objMilstar->setUser($user);
        $objMilstar->setPurchaseOrder($purchaseOrder);
        $objMilstar->setRequestId($milsatParams['requestId']);
        $objMilstar->setPayableAmount($milsatParams['amount']);
        $objMilstar->setFacNbr($milsatParams['processingFacnbr']);
        $objMilstar->setRegion($milsatParams['region']);
        if(isset($milsatParams['zipCode'])){
            $objMilstar->setZipcode($milsatParams['zipCode']);
        }
        if(isset($milsatParams['cid'])){
            $objMilstar->setCid($milsatParams['cid']);
        }
        $objMilstar->setCardNo($milsatParams['creditCardNumber']);

        if(isset($milsatParams['failCode']) && !empty($milsatParams['failCode'])){

            if($milsatParams['failCode'] != 'A'){

                $objMilstar->setFailCode((string) $milsatParams['failCode']);
                $objMilstar->setFailMessage((string) $milsatParams['failMessage']);
            }
        }

        if(isset($milsatParams['authCode']) && !empty($milsatParams['authCode'])){

            $objMilstar->setAuthCode((string) $milsatParams['authCode']);
            $objMilstar->setAuthTicket((string) $milsatParams['authTicket']);
        }

        $objMilstar->setProcessStatus($milsatParams['processStatus']);
        $objMilstar->setResponseCode((string) $milsatParams['responseCode']);
        $objMilstar->setMessage($milsatParams['message']);

        $this->em->persist($objMilstar);
        $this->em->flush();
        $insertIdMilstar = $objMilstar->getId();

        if($insertIdMilstar){

            $purchaseOrder->setMilstar($objMilstar);
            $this->em->persist($purchaseOrder);
            $this->em->flush();
        }
    }

    public function storeActiveUserService($servicePurchase){

        $ipAddress  = $this->GeoLocation->getRealIpAddress();
        $ipToLong 	= sprintf("%u", ip2long($ipAddress));
        $isSetActualValidity = 0;

		$flagISPExtend = false;

		if($servicePurchase){
            //Add User Service
            $userService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array('servicePurchase' => $servicePurchase->getId()));

            if(!$userService){
                $userService = new UserService();
                $objUserService = $userService;
            }

            $validity = $servicePurchase->getValidity();
            if($this->session->get('ISPSetStatusunset')){
                $preActiveService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array('id' => $this->session->get('ISPSetStatusunset')));
                $oneDayUpgradeDetails = $this->DashboardSummary->getOneDayUpgradeDetails($preActiveService, $servicePurchase);
                if (!empty($oneDayUpgradeDetails)) {
                    $isSetActualValidity = 1;
                    $userService->setActualValidity($validity);
                    $activationDate = $oneDayUpgradeDetails['activationDate'];
                    $expiryDate     = $oneDayUpgradeDetails['expiryDate'];
                    $validity       = $oneDayUpgradeDetails['validity'];

                }else{
                    $activationDate = new \DateTime(date('Y-m-d H:i:s'));
                    $currentDate = new \DateTime(date('Y-m-d H:i:s'));
                    $expiryDate  = $currentDate->modify('+'.$servicePurchase->getValidity().' '.$servicePurchase->getValidityType());
                }
            } else {
                $currentDate = new \DateTime(date('Y-m-d H:i:s'));
                $expiryDate  = $currentDate->modify('+'.$servicePurchase->getValidity().' '.$servicePurchase->getValidityType());
                $activationDate = new \DateTime(date('Y-m-d H:i:s'));
            }

            $payableAmount = $servicePurchase->getPayableAmount();
			$actualAmount = $servicePurchase->getActualAmount();
			$finalCost = $servicePurchase->getFinalCost();

            if ($servicePurchase->getUser()) {
    			if($this->session->has('isExtendISP')) {
    				if($this->session->get('isExtendISP') == true && $servicePurchase->getService()->getName() == 'ISP')
    				{
                        $user = $servicePurchase->getUser();
    					$objOldUserService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array('user' => $user, 'service' => $servicePurchase->getService()->getId(), 'packageId' => $servicePurchase->getPackageId()), array('id' => 'DESC'));
    					if($objOldUserService) {
                            $expiryDate = '';
                            $activationDate = '';
                            $currentDate = '';

    						$currentDate = $objOldUserService->getExpiryDate();
    						$expiryDate =  $currentDate->modify('+'.$servicePurchase->getValidity().' '.$servicePurchase->getValidityType());

                            $activationDate = $objOldUserService->getActivationDate();

                            $objUserService->setIsExtend(1);
    						$objUserService->setIsExtendedSaparately(1);
    						$objUserService->setActualValidity($validity);
                            $isSetActualValidity = 1;
							$validity = ($objOldUserService->getValidity() + $servicePurchase->getValidity());
                            if($objOldUserService->getActualValidity()) {
    							$payableAmount = ($objOldUserService->getPayableAmount() + $payableAmount);
    							$finalCost = $payableAmount;
    						} else {
    							$payableAmount = ($payableAmount + $payableAmount);
    						}
    					}
    				}
    			}

    			if($this->session->has('isExtendIPTV')) {
				    if($this->session->get('isExtendIPTV') == true && $servicePurchase->getService()->getName() == 'IPTV'){
    					$user = $servicePurchase->getUser() ? $servicePurchase->getUser() : '';

                        $objOldUserService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array('user' => $user, 'service' => $servicePurchase->getService()->getId(), 'packageId' => $servicePurchase->getPackageId()), array('id' => 'DESC'));

    					if($objOldUserService) {
                            $expiryDate = '';
                            $activationDate = '';
                            $currentDate = '';

    						$currentDate = $objOldUserService->getExpiryDate();
    						$expiryDate =  $currentDate->modify('+'.$servicePurchase->getValidity().' '.$servicePurchase->getValidityType());
    						$activationDate = $objOldUserService->getActivationDate();

    						$objUserService->setIsExtend(1);
                            $objUserService->setIsExtendedSaparately(1);
    						$objUserService->setActualValidity($validity);
                            $isSetActualValidity = 1;
    						$validity = ($objOldUserService->getValidity() + $servicePurchase->getValidity());
                            if($objOldUserService->getActualValidity()) {
    							$payableAmount = ($objOldUserService->getPayableAmount() + $payableAmount);
    							$finalCost = $payableAmount;
                            } else {
        						$payableAmount = ($payableAmount + $payableAmount);
    						}
						}
					}
				}
			}

			if($this->session->has('isAddonExtendIPTV')) {

				if($this->session->get('isAddonExtendIPTV') == true && $servicePurchase->getService()->getName() == 'IPTV' && $servicePurchase->getIsAddon() == 1)
				{
					$user = $servicePurchase->getUser();

					$objOldUserService = $this->em->getRepository('DhiUserBundle:UserService')->findOneBy(array('user' => $user, 'service' => $servicePurchase->getService()->getId(), 'packageId' => $servicePurchase->getPackageId()), array('id' => 'DESC'));

					if($objOldUserService) {
                        $expiryDate = '';
                        $activationDate = '';
                        $currentDate = '';

						$currentDate = $objOldUserService->getExpiryDate();
						$expiryDate =  $currentDate->modify('+'.$servicePurchase->getValidity().' '.$servicePurchase->getValidityType());

						$activationDate = $objOldUserService->getActivationDate();

						$objUserService->setIsExtend(1);
                        $objUserService->setIsExtendedSaparately(1);
						$objUserService->setActualValidity($validity);
                        $isSetActualValidity = 1;
						$validity = ($objOldUserService->getValidity() + $servicePurchase->getValidity());
                        if($objOldUserService->getActualValidity()) {
							$payableAmount = ($objOldUserService->getPayableAmount() + $payableAmount);
							$finalCost = $payableAmount;
						} else {
							$payableAmount = ($payableAmount + $payableAmount);
						}
					}

				}

			}

            /*
                if(!isset($objUserService)){
                    $objUserService = $userService;
                }

                if($this->session->has('promoDaysValidity')) {
                    if($this->session->get('promoDaysValidity')) {
                    $currentDate = new \DateTime(date('Y-m-d H:i:s'));
                        $expiryDate  = $currentDate->modify('+'.$this->session->get('promoDaysValidity').' hour');
                        $activationDate = new \DateTime(date('Y-m-d H:i:s'));
                    }
                }
            */

            if($isSetActualValidity == 0){
                $objUserService->setActualValidity($validity);
            }
            $objUserService->setUser($servicePurchase->getUser());
            $objUserService->setPurchaseOrder($servicePurchase->getPurchaseOrder());
            $objUserService->setServicePurchase($servicePurchase);
            $objUserService->setService($servicePurchase->getService());
            $objUserService->setPackageId($servicePurchase->getPackageId());
            $objUserService->setPackageName($servicePurchase->getPackageName());
            $objUserService->setActualAmount($servicePurchase->getActualAmount());
            $objUserService->setTotalDiscount($servicePurchase->getTotalDiscount());
            $objUserService->setDiscountRate($servicePurchase->getDiscountRate());
            $objUserService->setFinalCost($payableAmount);
            $objUserService->setPayableAmount($payableAmount);
            $objUserService->setBandwidth($servicePurchase->getBandwidth());
            $objUserService->setValidity($validity);
            $objUserService->setActivationDate($activationDate);
            $objUserService->setExpiryDate($expiryDate);

            if($this->get("session")->has('isISPOnly') || $this->get("session")->has('isISPOnlyUpgrade')){
                if(($this->get("session")->get('isISPOnly') == true) || ($this->get("session")->get('isISPOnlyUpgrade') == true) ){
                    $objUserService->setIsPlanActive(false);
                }
            }
            $objUserService->setStatus(1);
            $objUserService->setIsAddon($servicePurchase->getIsAddon());
            $objUserService->setServiceLocationIp($ipToLong);
            $objUserService->setUnusedCredit($servicePurchase->getUnusedCredit());
            $objUserService->setUnusedDays($servicePurchase->getUnusedDays());
            $this->em->persist($objUserService);

            if (!empty($objOldUserService)) {
                $objOldUserService->setStatus(0);
                $this->em->persist($objOldUserService);
            }

			$objUserServiceSetting = $this->em->getRepository('DhiUserBundle:UserServiceSetting')->findBy(array('user' => $servicePurchase->getUser(), 'service' => $servicePurchase->getService()));
            if(!$objUserServiceSetting) {
                //Add Service in UserServiceSetting
                $objUserServiceSetting = new UserServiceSetting();
                $objUserServiceSetting->setService($servicePurchase->getService());
                $objUserServiceSetting->setUser($servicePurchase->getUser());
                $objUserServiceSetting->setServiceStatus('Enabled');
                $this->em->persist($objUserServiceSetting);

                //Add User's service log
                $objUserServiceSettingLog = new UserServiceSettingLog();
                $objUserServiceSettingLog->setService($servicePurchase->getService());
                $objUserServiceSettingLog->setUser($servicePurchase->getUser());
                $objUserServiceSettingLog->setServiceStatus('Enabled');
                $this->em->persist($objUserServiceSettingLog);
            }

            $this->em->flush();

			$expiryDate = '';
			$activationDate = '';
			$currentDate = '';


			return true;
        }

        return false;
    }

	public function extendExpiryDateCurrentServices($expiryDate, $id) {

		$flag = false;

		$objUserService = $this->em->getRepository('DhiUserBundle:UserService')->find($id);

		if($objUserService) {


			$objUserService->setExpiryDate($expiryDate);
			$this->em->persist($objUserService);
			$this->em->flush();
			$flag = true;

		}

		return $flag;

	}

    public function refundPayment($refundInfoArr = array(), $user = null, $isTvod = false){

        if(!$user){
            $user            = $this->securitycontext->getToken()->getUser();
        }

        $purchaseOrderId = $this->session->get('PurchaseOrderId');
        $purchaseOrder = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);

        $totalRefundAmount = 0;
        $totalPaybleAmount = 0;
        $refundSuccess = false;
        $refundType = '';
        $paymentStatus = '';

        if($purchaseOrder){

            $servicePurchases = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->findBy(array('purchaseOrder' => $purchaseOrder->getId()));

            if($servicePurchases){

                foreach ($servicePurchases as $order){

                    $totalPaybleAmount += $order->getPayableAmount();

                    if($order->getPaymentStatus() == 'NeedToRefund' && $order->getRechargeStatus() == 2){

                        $totalRefundAmount += $order->getPayableAmount();
                    }
                }

                $refundType = 'Partial';
                $isRefundCredit = 0;
                $creditRefundType = '';


                if($totalRefundAmount == $totalPaybleAmount){

                    $refundType = 'Full';
                }

                if($purchaseOrder->getPaymentMethod()){

                    $paymentMethod = $purchaseOrder->getPaymentMethod()->getCode();

                    if($paymentMethod == 'PayPal' || $paymentMethod == 'CreditCard'){

                        if($totalRefundAmount > 0){

                            $configPaypal = array(
                                    'METHOD'           => 'RefundTransaction',
                                    'INVNUM'           => $purchaseOrder->getOrderNumber(),
                                    'TRANSACTION_ID'   => $purchaseOrder->getPaypalCheckout()->getPaypalTransactionId(),
                                    'REFUNDTYPE'       => $refundType,
                                    'AMOUNT'           => $totalRefundAmount
                            );

                            if ($isTvod) {
                                $configPaypal['TVOD'] = array(
                                    'userId' => $user,
                                    'isTvod' => true
                                );
                            }

                            $express 		= new ExpressCheckout($configPaypal,$this->container); //Paypal Express Checkout object
                            $paypalResponse = $express->refundPayment();

                            if ($paypalResponse['ACK'] == "Success") {

                                $refundSuccess = true;
                            }
                        }
                    }

                    if($paymentMethod == 'Milstar'){

                        if($purchaseOrder->getMilstar()){

                            if($totalRefundAmount > 0){

                                //$refundInfoArr['amount'] = 1;
                                $refundInfoArr['amount'] = $totalRefundAmount;

                                $milstarRefundStatus = $this->get('milstar')->processMilstarCredit($refundInfoArr);

                                if($milstarRefundStatus){

                                    $refundSuccess = true;
                                }
                            }
                        }
                    }

                    if(strtolower($paymentMethod) == 'chase'){
                        //$refundSuccess = true;
                         if($purchaseOrder->getChase()){
                             if($totalRefundAmount > 0){
                                 $refundInfoArr['amount'] = $totalRefundAmount;
                                 $chaseRefundStatus = $this->get('chase')->refund($refundInfoArr);
                                 if($chaseRefundStatus){
                                     $refundSuccess = true;
                                 }
                             }
                         }
                    }

                    if($paymentMethod == 'Cash' || $paymentMethod == 'EagleCash'){

                        /*foreach ($servicePurchases as $servicePurchase){

                            if($servicePurchase->getPaymentStatus() == 'NeedToRefund' && $servicePurchase->getRechargeStatus() == 2){

                                $this->em->remove($servicePurchase);
                            }
                        }
                        $this->em->flush();*/
                        $refundSuccess = true;
                    }

                    if(in_array($paymentMethod, array('FreePlan', 'InAppPromoCode'))){

                        $refundSuccess = true;
                    }
                }





                if($refundSuccess){

                    $updateServicePurchase = $this->em->createQueryBuilder()->update('DhiServiceBundle:ServicePurchase', 'sp')
                    ->set('sp.paymentStatus', '\'Voided\'')
                    ->where('sp.paymentStatus =:paymentStatus')
                    ->setParameter('paymentStatus', 'NeedToRefund')
                    ->andWhere('sp.rechargeStatus =:rechargeStatus')
                    ->setParameter('rechargeStatus', 2)
                    ->andWhere('sp.purchaseOrder =:purchaseOrder')
                    ->setParameter('purchaseOrder', $purchaseOrderId)
                    ->getQuery()->execute();

                    if($refundType == 'Full'){

                        $paymentStatus = 'Voided';
                    }

                    if($refundType == 'Partial'){

                        //$paymentStatus = 'PartiallyCompleted';
                        $paymentStatus = 'Completed';
                    }

                    //Updtae payment status
                    if($paymentStatus){

                        $purchaseOrder->setPaymentStatus($paymentStatus);
                        $purchaseOrder->setRefundAmount($totalRefundAmount);
                        $this->em->persist($purchaseOrder);
                        $this->em->flush();

                        //Add Activity Log
                        $activityLog = array();
                        $activityLog['user'] 	    = $user;
                        $activityLog['activity']    = 'Purchase Order';
                        $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$purchaseOrder->getOrderNumber().' payment has been '.$paymentStatus;
                        $this->ActivityLog->saveActivityLog($activityLog);
                        //Activity Log end here
                    }
                }
            }
        }

        $response = array();
        $response['refundType'] = $refundType;
        $response['refundSuccess'] = $refundSuccess;
        $response['totalPaidAmount'] = $totalPaybleAmount - $totalRefundAmount;

        return $response;
    }

    public function paymentSuccessSummary($orderNumber){
        $purchaseOrder = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneByOrderNumber($orderNumber);

        if (!$purchaseOrder) {
            return false;
        }

        $user = $purchaseOrder->getUser();

        $TotalBundleDiscount = 0;
        $purchasedSummaryData = array();

        $purchasedSummaryData['PurchaseOrderId'] = 0;
        $purchasedSummaryData['TransactionId'] = '';
        $purchasedSummaryData['OrderNumber'] = '';
        $purchasedSummaryData['PurchasedDate'] = '';
        $purchasedSummaryData['PaymentMethod'] = '';
        $purchasedSummaryData['PaymentStatus'] = '';
        $purchasedSummaryData['PurchaseEmailSent'] = 0;
        $purchasedSummaryData['ServiceNetPaidAmount'] = 0;
        $purchasedSummaryData['ServiceNetRefundedAmount'] = 0;
        $purchasedSummaryData['PurchaseOrderNetPaidAmount'] = 0;
        $purchasedSummaryData['PurchaseOrderNetRefundedAmount'] = 0;
        $purchasedSummaryData['IPTVTotalPaybleAmount'] = 0;
        $purchasedSummaryData['IPTVTotalActualAmount'] = 0;
        $purchasedSummaryData['IPTVTotalRefundedAmount'] = 0;

        $purchasedSummaryData['TVODTotalPaybleAmount'] = 0;
        $purchasedSummaryData['TVODTotalActualAmount'] = 0;
        $purchasedSummaryData['TVODTotalRefundedAmount'] = 0;

        $purchasedSummaryData['AddOnTotalPaybleAmount'] = 0;
        $purchasedSummaryData['AddOnTotalActualAmount'] = 0;
        $purchasedSummaryData['AddOnTotalRefundedAmount'] = 0;
        $purchasedSummaryData['ISPTotalPaybleAmount'] = 0;
        $purchasedSummaryData['ISPTotalActualAmount'] = 0;
        $purchasedSummaryData['ISPTotalRefundedAmount'] = 0;
        $purchasedSummaryData['CreditTotalPaybleAmount'] = 0;
        $purchasedSummaryData['CreditTotalActualAmount'] = 0;
        $purchasedSummaryData['CreditTotalRefundedAmount'] = 0;
        $purchasedSummaryData['BundleName'] = '';
        $purchasedSummaryData['isBundleDiscountApplied'] = 0;
        $purchasedSummaryData['TotalBundleDiscount'] = 0;
        $purchasedSummaryData['NetPaidAmount'] = 0;
        $purchasedSummaryData['UserId'] = 0;
        $purchasedSummaryData['emailVerified'] = 0;
        $purchasedSummaryData['UserName'] = '';
        $purchasedSummaryData['Email'] = '';
        $purchasedSummaryData['RecurringStatus'] = '';
        $purchasedSummaryData['firstName'] = '';
        $purchasedSummaryData['lastName'] = '';
        $purchasedSummaryData['isUpgrade'] = '';
        $purchasedSummaryData['isISPPlanExtend'] = false;
        $purchasedSummaryData['isIPTVPlanExtend'] = false;
        $purchasedSummaryData['isTVODPlanExtend'] = false;
        $purchasedSummaryData['isAddOnPlanExtend'] = false;
        $purchasedSummaryData['isCreditExtend'] = false;

        $purchasedSummaryData['isDiscountCouponAvailable'] = 0;
        $purchasedSummaryData['isDiscountCouponPercentage'] = 0;
        $purchasedSummaryData['isDiscountCouponAmount'] = 0;

        // Check for employee
        $isEmployee = $user->getIsEmployee();
        $purchasedSummaryData['isEmployee'] = $isEmployee;
        $purchasedSummaryData['TotalPromotionOff'] = 0;
        $purchasedSummaryData['TotalPromotionPer'] = 0;

        if ($purchaseOrder) {
            $servicePurchases = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->findByPurchaseOrder($purchaseOrder->getId());

            if (!empty($servicePurchases)) {

                $totalDiscountPer = $totalDiscountAmo = $i = 0;
                $isPartnerPromoApplied = false;
                $isBusinessPromoApplied = false;
                foreach ($servicePurchases as $servicePurchased) {
                    if($servicePurchased->getPromoCodeApplied() == 2){
                        $isPartnerPromoApplied = true;
                    }

                    if($servicePurchased->getPromoCodeApplied() == 3){
                        $isBusinessPromoApplied = true;
                    }

                    if ($servicePurchased->getIsAddon() == 0) {
                        $purchasedSummaryData['isUpgrade'] = $servicePurchased->getIsUpgrade();
                    }

                    $tempArr = array();
                    $tempArr['isBundleDiscountAvailable'] = 0;
                    $tempArr['packageName']          = $servicePurchased->getPackageName();
                    $tempArr['validity']          = $servicePurchased->getValidity();
                    $tempArr['bandwidth']          = $servicePurchased->getBandwidth();
                    $tempArr['bundleName']          = $servicePurchased->getbundleName();
                    $tempArr['packageStatus']        = $servicePurchased->getActivationStatus();
                    $tempArr['packagePaybleAmount'] = (($isPartnerPromoApplied == false && $isBusinessPromoApplied == false) ? $servicePurchased->getPayableAmount() : 0);
                    $tempArr['packageActualAmount'] = (($isPartnerPromoApplied == false && $isBusinessPromoApplied == false) ? $servicePurchased->getActualAmount() : 0);
                    $tempArr['paymentStatus']        = $servicePurchased->getPaymentStatus();
                    $tempArr['bundleDiscount']       = $servicePurchased->getBundleDiscount();
                    $tempArr['totalDiscountCodeAmount']       = $servicePurchased->getDiscountCodeAmount();
                    $tempArr['validityType']       = $servicePurchased->getvalidityType();
                    $tempArr['totalPromotionOff']       = $servicePurchased->getPromotionDiscountAmount();
                    $tempArr['totalPromotionPer']       = $servicePurchased->getPromotionDiscountPer();

                    if($servicePurchased->getBundleId()){
                        $discountAmount = ($servicePurchased->getActualAmount() * $servicePurchased->getBundleDiscount()) / 100;
                        $tempArr['bundleDiscountAmount'] =  $discountAmount;//$servicePurchased->getActualAmount() - $servicePurchased->getPayableAmount();
                    }else{
                            $discountAmount = $tempArr['bundleDiscountAmount'] = 0;
                    }

                    $tempArr['purchaseType']         = $servicePurchased->getPurchaseType();
                    $tempArr['bundleId']             = $servicePurchased->getBundleId();

                    $tempArr['isDiscountAvailable']  = 0;
                    $tempArr['isUnusedCreditAvailable'] = 0;

                    if ($servicePurchased->getPaymentStatus() == 'Refunded') {

                        $tempArr['RefundedAmount'] = $servicePurchased->getPayableAmount();
                    } else {

                        $tempArr['RefundedAmount'] = 0;
                    }

                    if ($servicePurchased->getService()) {

                        if ($servicePurchased->getIsAddon() == 1) {

                            $serviceName = 'AddOn';
                        } else {

                            $serviceName = strtoupper($servicePurchased->getService()->getName());
                        }

                        $purchasedSummaryData['is'.$serviceName.'PlanExtend'] = ($servicePurchased->getIsExtend() == 1)?true:false;

                        $bundleId = $servicePurchased->getbundleId();
                        if(!empty($bundleId)){
                            $bundlePlan = $this->em->getRepository("DhiAdminBundle:Bundle")->findOneBy(array("bundle_id"=>$bundleId));
                            if($bundlePlan){
                                if(strtoupper($serviceName) == 'ISP') {
                                    $tempArr['packageActualAmount'] = $bundlePlan->getIspAmount();
                                }else if(strtoupper($serviceName) == 'IPTV') {
                                    $tempArr['packageActualAmount'] = $bundlePlan->getIptvAmount();
                                }
                            }
                        }


                        $purchasedSummaryData[$serviceName][] = $tempArr;

                        if($servicePurchased->getTotalDiscount() > 0){

                            $tempArrDiscount = array();
                            $tempArrDiscount['isDiscountAvailable'] = 1;
                            $tempArrDiscount['isUnusedCreditAvailable'] = 0;

                            $tempArrDiscount['TotalDiscount']      = $servicePurchased->getTotalDiscount();
                            $tempArrDiscount['Discription']        = $servicePurchased->getDiscountRate().'% Bundle discount on '.$serviceName.' Package';
                            $purchasedSummaryData[$serviceName][]  = $tempArrDiscount;
                        }

                        $tempArr['bundleDiscount']       = $servicePurchased->getBundleDiscount();
                        if($servicePurchased->getBundleId()){
                            $discountAmount = $servicePurchased->getDisplayBundleDiscount();
                        }else{
                            $discountAmount = 0;
                        }

                        if($servicePurchased->getPurchaseType() == 'BUNDLE' || $servicePurchased->getBundleDiscount() > 0){
                            $TotalBundleDiscount += $discountAmount;
                            $purchasedSummaryData['isBundleDiscountApplied'] = 1;

                            $purchasedSummaryData['BundleName'] = $servicePurchased->getbundleName();
                            $tempArrBundleDiscount = array();
                            $tempArrBundleDiscount['isDiscountAvailable']       = 0;
                            $tempArrBundleDiscount['isBundleDiscountAvailable'] = 1;
                            $tempArrBundleDiscount['isUnusedCreditAvailable'] = 0;
                            $tempArrBundleDiscount['bundleDiscount']            = $servicePurchased->getBundleDiscount();
                            $tempArrBundleDiscount['bundleDiscountAmount']      = $discountAmount;
                            $tempArrBundleDiscount['bundleName']        = $servicePurchased->getbundleName();
                            $purchasedSummaryData[$serviceName][]  = $tempArrBundleDiscount;
                        }

                        if ($servicePurchased->getPromotionDiscountPer() > 0) {
                            $purchasedSummaryData['TotalPromotionOff'] += $servicePurchased->getPromotionDiscountAmount();
                            $purchasedSummaryData['TotalPromotionPer'] = $servicePurchased->getPromotionDiscountPer();
                        }

                        if($servicePurchased->getUnusedCredit() > 0){

                            $tempArrCredit = array();
                            $tempArrCredit['isDiscountAvailable'] = 0;
                            $tempArrCredit['isBundleDiscountAvailable'] = 0;
                            $tempArrCredit['isUnusedCreditAvailable'] = 1;

                            $tempArrCredit['TotalUnusedCredit']  = $servicePurchased->getUnusedCredit();
                            $tempArrCredit['Discription']        = 'Existing '.$serviceName.' Pack Unused credit';
                            $purchasedSummaryData[$serviceName][]   = $tempArrCredit;
                        }

                        if($servicePurchased->getDiscountCodeApplied() == 1){
                            $discountPer   = $servicePurchased->getDiscountCodeRate();
                            $payableAmount = $servicePurchased->getPayableAmount();

                            if ($discountPer < 100) {
                                $discountAmo = ((($payableAmount*100)/(100 - $discountPer)) - $payableAmount);
                            } else {
                                $discountAmo = $servicePurchased->getDiscountCodeAmount();
                            }


                            // $tempArrDiscountCoupon = array();
                            // $tempArrDiscountCoupon['isDiscountAvailable'] = 0;
                            // $tempArrDiscountCoupon['isBundleDiscountAvailable'] = 0;
                            // $tempArrDiscountCoupon['isUnusedCreditAvailable'] = 0;
                            // $tempArrDiscountCoupon['isDiscountCouponAvailable'] = 1;

                            // $tempArrDiscountCoupon['discountCouponAmount']  = $discountPer;
                            // $tempArrDiscountCoupon['Discription']        = $discountPer.' % Discount';
                            // $purchasedSummaryData[$serviceName][]   = $tempArrDiscountCoupon;

                            $purchasedSummaryData['isDiscountCouponAvailable'] = 1;
                            $totalDiscountPer = $discountPer;

                            $totalDiscountAmo += $discountAmo;
                        }

                        if($servicePurchased->getDiscountCodeApplied() == 7){
                            $discountPer   = $servicePurchased->getDiscountCodeRate();
                            $payableAmount = $servicePurchased->getPayableAmount();
                            $discountAmo = $servicePurchased->getDiscountCodeAmount();

                            $purchasedSummaryData['isDiscountCouponAvailable'] = 1;
                            $totalDiscountPer = $discountPer;

                            $totalDiscountAmo += $discountAmo;
                        }

						if ($isBusinessPromoApplied == false && $isPartnerPromoApplied == false ) {
							$purchasedSummaryData[$serviceName . 'TotalPaybleAmount'] += $servicePurchased->getPayableAmount() + (isset($discountAmo) ? $discountAmo : 0) + ($servicePurchased->getPromotionDiscountAmount() > 0 ? $servicePurchased->getPromotionDiscountAmount() : 0);
							$purchasedSummaryData[$serviceName . 'TotalActualAmount'] += $servicePurchased->getActualAmount();
							$purchasedSummaryData[$serviceName . 'TotalRefundedAmount'] += $tempArr['RefundedAmount'];
						}
                    }
                    if ($servicePurchased->getIsCredit() == 1 && $servicePurchased->getCredit()) {

                        $credit = $servicePurchased->getCredit()->getCredit();

                        //$tempArr['packageName'] = 'Pay $' . $servicePurchased->getPayableAmount() . ' and get ' . $credit . ' credit in your account.';
                        $tempArr['packageName'] = $credit . ' ExchangeVUE Credits.';
                        $purchasedSummaryData['Credit'][] = $tempArr;
                        $purchasedSummaryData['CreditTotalPaybleAmount'] += $servicePurchased->getPayableAmount();
                        $purchasedSummaryData['CreditTotalActualAmount'] += $servicePurchased->getActualAmount();
                        $purchasedSummaryData['CreditTotalRefundedAmount'] += $tempArr['RefundedAmount'];
                    }
                    $i++;
                }

                $purchasedSummaryData['discountCouponPercentage'] = $totalDiscountPer;
                $purchasedSummaryData['discountCouponAmount'] = $totalDiscountAmo;

                $purchasedSummaryData['ServiceNetPaidAmount'] = $purchasedSummaryData['IPTVTotalPaybleAmount'] + $purchasedSummaryData['AddOnTotalPaybleAmount'] + $purchasedSummaryData['ISPTotalPaybleAmount'] + $purchasedSummaryData['CreditTotalPaybleAmount'];
                $purchasedSummaryData['ServiceNetRefundedAmount'] = $purchasedSummaryData['IPTVTotalRefundedAmount'] + $purchasedSummaryData['AddOnTotalRefundedAmount'] + $purchasedSummaryData['ISPTotalRefundedAmount'] + $purchasedSummaryData['CreditTotalRefundedAmount'];
            }

            $paymentMethod = '';
            $transactionId = 'N/A';
            if($purchaseOrder->getPaymentMethod()){

                $paymentMethod = $purchaseOrder->getPaymentMethod()->getName();

                if($purchaseOrder->getPaymentMethod()->getCode() == 'PayPal' || $purchaseOrder->getPaymentMethod()->getCode() == 'CreditCard'){

                    if($purchaseOrder->getPaypalCheckout()) {

                        $transactionId = $purchaseOrder->getPaypalCheckout()->getPaypalTransactionId();
                    }
                }

                if($purchaseOrder->getPaymentMethod()->getCode() == 'Milstar'){

                    if($purchaseOrder->getMilstar()) {

                        $transactionId = $purchaseOrder->getMilstar()->getAuthTicket();
                    }
                }

                if(strtolower($purchaseOrder->getPaymentMethod()->getCode()) == 'chase'){

                    if($purchaseOrder->getChase()) {

                        $transactionId = $purchaseOrder->getChase()->getChaseTransactionId();
                    }
                }
            }

            $purchasedSummaryData['PurchaseOrderId'] = $purchaseOrder->getId();
            $purchasedSummaryData['OrderNumber'] = $purchaseOrder->getOrderNumber();
            $purchasedSummaryData['PurchasedDate'] = $purchaseOrder->getCreatedAt()->format('m/d/Y h:i:s');
            $purchasedSummaryData['PaymentMethod'] = $paymentMethod;
            $purchasedSummaryData['TransactionId'] = $transactionId;
            $purchasedSummaryData['PaymentStatus'] = $purchaseOrder->getPaymentStatus();
            $purchasedSummaryData['PurchaseEmailSent'] = $purchaseOrder->getPurchaseEmailSent();
            $purchasedSummaryData['PurchaseOrderNetPaidAmount'] = $isPartnerPromoApplied == false && $isBusinessPromoApplied == false ? $purchaseOrder->getTotalAmount() : 0;
            $purchasedSummaryData['PurchaseOrderNetRefundedAmount'] = $isPartnerPromoApplied == false && $isBusinessPromoApplied == false ? $purchaseOrder->getRefundAmount() : 0;
            $purchasedSummaryData['RecurringStatus'] = $purchaseOrder->getRecurringStatus();
            $purchasedSummaryData['TotalBundleDiscount'] = $TotalBundleDiscount;

            if($purchaseOrder->getUser()){

                $purchasedSummaryData['UserId'] = $purchaseOrder->getUser()->getId();
                $purchasedSummaryData['emailVerified'] = $purchaseOrder->getUser()->getIsEmailVerified();
                $purchasedSummaryData['UserName'] = $purchaseOrder->getUser()->getUserName();
                $purchasedSummaryData['Email'] = $purchaseOrder->getUser()->getEmail();
                $purchasedSummaryData['firstName'] = $purchaseOrder->getUser()->getFirstname();
                $purchasedSummaryData['lastName'] = $purchaseOrder->getUser()->getLastname();

            }


			if(isset($purchasedSummaryData['ISP'][0]['paymentStatus']) && $purchasedSummaryData['ISP'][0]['paymentStatus'] == 'Voided') {

			$wsParam = array();
			$wsParam['Page'] = 'UserSessions';
			$wsParam['SessionsMode'] = 'UsrAllSessions';
			$wsParam['qdb_Users.UserID'] = $user->getUserName();
            $wsParam['op_$D$AcctSessionTime']  = "<>";
            $wsParam['qdb_$D$AcctSessionTime'] = " ";

			$aradial = $this->get('aradial');
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
                    $isRefunded      = 1;

					if (!empty($userName)) {
						$userData = $this->em->getRepository('DhiUserBundle:User')->getEmailForAradialUser($userName);
					}

					if (!empty($userData)) {
						$email = $userData[0]['email'];
					} else {
						$email = '';
					}

					$Param = array();
					$Param['Page'] = 'UserHit';
					$Param['qdb_Users.UserID'] = $userName;

                    if (empty($email)) {
    					$wsResponse1 = $aradial->callWSAction('getUserList', $Param);
    					if (!empty($wsResponse1['userList'])) {
    						$email = empty($wsResponse1['userList'][0]['UserDetails.Email']) ? '' : $wsResponse1['userList'][0]['UserDetails.Email'];
    					}
                    }

					if(!empty($acctSessionTime)){
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
						$this->em->persist($objUserSession);
						$this->em->flush();
					}
				}
			}
		}

            //$purchasedSummaryData['UserId'] = ($purchaseOrder->getUser())?$purchaseOrder->getUser()->getId():0;
            //echo "<pre>";print_r($purchasedSummaryData);exit;

            return $purchasedSummaryData;
        }

        return false;
    }

    public function sendPurchaseEmail($purchasedSummaryData, $isTvod = false, $tikiLive = array()) {

        $sendEmailError = false;
        $isVerifiedUser = true;
        $session = $this->container->get('session');
        $whitelabel = $session->get('brand');

        if ((empty($purchasedSummaryData['emailVerified']))) {
            $total = $this->em->getRepository("DhiServiceBundle:PurchaseOrder")->countPurchase($purchasedSummaryData['UserId']);
            if ($total == 1) {
                $isVerifiedUser = true;
            }else{
                $isVerifiedUser = false;
            }
        }

        if((isset($purchasedSummaryData['PaymentStatus']) && $purchasedSummaryData['PaymentStatus'] == 'Completed')){

            if($whitelabel){
                $subject   = $whitelabel['name'].' - Purchase Receipt';
                $fromEmail = $whitelabel['fromEmail'];
                $fromName  = $whitelabel['name'];
                $compnayname = $whitelabel['name'];
                $compnaydomain = $whitelabel['domain'];
                $supportpage   = $whitelabel['supportpage'];
            } else {
                $subject         = 'ExchangeVUE - Purchase Receipt';
                $fromEmail       = $this->container->getParameter('purchase_summary_from_email');
                $fromName        = $this->container->getParameter('purchase_summary_from_name');
                $compnayname     = 'ExchangeVUE';
                $compnaydomain   = 'exchangevue.com';
                $supportpage     = 'https://www.facebook.com/dhitelecom';
            }


            if($isTvod == true) {
                $emailBody = $this->render('DhiServiceBundle:Emails:tvodPurchaseEmail.html.twig', array('purchasedSummaryData' => $purchasedSummaryData,'companyname'=>$compnayname,'companydomain'=>$compnaydomain));
            } else {
                $tikiliveMsg = '';
                if (!empty($tikiLive['istikilivepromocode']) && !empty($tikiLive['tikiliveMsg'])) {
                    $tikiliveMsg = $tikiLive['tikiliveMsg'];
                }

                $emailBody = $this->render('DhiServiceBundle:Emails:purchaseEmail.html.twig',
                	array(
                		'purchasedSummaryData' => $purchasedSummaryData,
                		'tikiliveMsg' => $tikiliveMsg,
                		'companyname' => $compnayname,
                		'companydomain' => $compnaydomain,
                		'supportpage'=>$supportpage,
                                'httpProtocol' => ($this->getRequest()->isSecure() ? 'https://' : 'http://')
                	)
                );
            }


             if($isVerifiedUser){
                
                $toEmail         = $purchasedSummaryData['Email'];
                $body            = $emailBody->getContent();

                try {

                    $message = \Swift_Message::newInstance()
                                ->setSubject($subject)
                                ->setFrom(array($fromEmail => $fromName))
                                ->setTo($toEmail)
                                ->setBody($body)
                                ->setContentType('text/html');

                    if($this->container->get('mailer')->send($message)){

                        $purchaseOrder = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchasedSummaryData['PurchaseOrderId']);

                        if($purchaseOrder) {

                            $purchaseOrder->setPurchaseEmailSent(1);
                            $this->em->persist($purchaseOrder);
                            $this->em->flush();
                        }

                        $sendEmailError = true;
                    }
                }catch (\Exception $e) {

                    $sendEmailError = false;
                }
            } else {
                if($tikiLive['istikilivepromocode'] == true && !empty($tikiLive['tikiliveMsg'])){

                    $tikiliveMsg = $tikiLive['tikiliveMsg'];

                    if($whitelabel){
                        $subject   = $whitelabel['name'].' - Tikilive Promocode';
                        $fromEmail = $whitelabel['fromEmail'];
                        $fromName  = $whitelabel['name'];
                        $compnayname = $whitelabel['name'];
                        $compnaydomain = $whitelabel['domain'];
                        
                    } else {
                        $subject         = 'ExchangeVUE - Purchase Receipt';
                        $fromEmail       = $this->container->getParameter('purchase_summary_from_email');
                        $fromName        = $this->container->getParameter('purchase_summary_from_name');
                        $compnayname     = 'ExchangeVUE';
                        $compnaydomain   = 'exchangevue.com';
                        
                    }
                    
                    $promoemailBody = $this->render('DhiServiceBundle:Emails:purchasetikilivepromo.html.twig', array('purchasedSummaryData' => $purchasedSummaryData, 'tikiliveMsg' => $tikiliveMsg,'sitename'=>$compnayname));
                    
                    $toEmail         = $purchasedSummaryData['Email'];
                    $body            = $promoemailBody->getContent();

                    $promomessage = \Swift_Message::newInstance()
                                ->setSubject($subject)
                                ->setFrom(array($fromEmail => $fromName))
                                ->setTo($toEmail)
                                ->setBody($body)
                                ->setContentType('text/html');
                    if($this->container->get('mailer')->send($promomessage)){
                        $sendEmailError = true;
                    }
                }
            }
        }else if(isset($purchasedSummaryData['PaymentStatus']) && $purchasedSummaryData['PaymentStatus'] == 'Voided'){
            $purchaseOrder = $this->em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchasedSummaryData['PurchaseOrderId']);
            $toEmail = $this->container->getParameter('paypal_voided_recipient');

            if ($purchaseOrder && !empty($toEmail)) {
                $paymentMethod = $purchaseOrder->getPaymentMethod();
                if ($paymentMethod) {
                    $name = $paymentMethod->getCode();
                    if (strtolower($name) == 'paypal') {
                        $servicePurchases = $purchaseOrder->getServicePurchases();
                        $service          = "";
                        if ($servicePurchases) {
                            foreach ($servicePurchases as $servicePurchase) {
                                if ($servicePurchase->getPaymentStatus() == "Voided") {
                                    if ($servicePurchase->getPurchaseType() == "BUNDLE") {
                                        $service = "Bundle";
                                    }else{
                                        $service = $servicePurchase->getService()->getName();
                                    }
                                }
                            }
                        }
                        $emailBody        = $this->render('DhiServiceBundle:Emails:paypalVoidedPurchaseEmail.html.twig', array('purchasedSummaryData' => $purchasedSummaryData, "serviceName" => $service));
                        $subject         = "User account creation on $service failed";
                        $fromEmail       = $this->container->getParameter('purchase_summary_from_email');
                        $fromName        = $this->container->getParameter('purchase_summary_from_name');
                        $toEmail         = $toEmail;
                        $body            = $emailBody->getContent();

                        try {
                            $message = \Swift_Message::newInstance()
                                ->setSubject($subject)
                                ->setFrom(array($fromEmail => $fromName))
                                ->setTo($toEmail)
                                ->setBody($body)
                                ->setContentType('text/html');
                            $this->container->get('mailer')->send($message);

                        }catch (\Exception $e) {}
                    }
                }
            }
        }

        return $sendEmailError;
    }

    public function clearPaymentSession(){

        $user = $this->securitycontext->getToken()->getUser();
        $sessionId = $this->session->get('sessionId');

        $condition = array('user' => $user, 'sessionId' => $sessionId, 'paymentStatus' => 'New');
        $servicePurchase = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->findBy($condition);

        $this->session->remove('billingInfo');
        $this->session->remove('milstarInfo');
        $this->session->remove('cardInfo');
        $this->session->remove('paymentBy');

        $this->session->remove('PurchaseOrderId');
        $this->session->remove('PurchaseOrderNumber');
        $this->session->remove('BillingAddressId');

        $this->session->remove('sessionId');
        $this->session->remove('termsUse');

        $this->session->remove('IsISPAvailabledInCart');
        $this->session->remove('IsIPTVAvailabledInCart');
        $this->session->remove('EnableRecurringPayment');

        $this->session->remove('ChaseWPInfo');
        $this->session->remove('chaseUserErrorMsg');
        $this->session->remove('chasePaymentType');

        $sessionId = $this->generateCartSessionId();
        if($sessionId){

            if($servicePurchase){

                foreach ($servicePurchase as $purchase){

                    if(!$purchase->getPurchaseOrder()){

                        $purchase->setSessionId($sessionId);
                        $this->em->persist($purchase);
                        $this->em->flush();
                    }
                }
            }
        }
    }

    public function clearAdminPaymentSession(){

        $this->session->remove('eagleCashInfo');
        $this->session->remove('adminSessionId');
        $this->session->remove('eagleCashInfo');
        $this->session->remove('paymentBy');
        $this->session->remove('eagleCashInfo');
        $this->session->remove('CACCardInfo');
        $this->session->remove('posOrderNo');
        $this->session->remove('PurchaseOrderId');
        $this->session->remove('IsISPAvailabledInCart');
        $this->session->remove('IsIPTVAvailabledInCart');
    }

    public function emailVerifiedForNextPurchase($type = 'user', $user = null){
        return true;
        if($type == 'user' && $user == null){
            $user = $this->securitycontext->getToken()->getUser();
        }

        $countPurchasedService  = $this->em->getRepository('DhiUserBundle:UserService')->countUserPurchaseService($user);

        // Check Email address verify for after first purchase
        if($user->getIsEmailVerified() != 1) {
            if($user->getIsAradialExists()) {
                if($countPurchasedService > 1) {
                    return false;
                }
            } else {
                if($countPurchasedService > 0) {
                    return false;
                }
            }
        }

        return true;
    }

    public function generateCartSessionId($type = 'user'){
        $sessionId =  $this->generateUniqueString(25);

        //$sessionId = '2fRJdOlfZZejlj4AJ331wOxN2';

        if($type == 'user'){

            if(!$this->session->get('sessionId')){

                $this->session->set('sessionId',$sessionId);

                return $sessionId;
            }else{

                return $this->session->get('sessionId');
            }
        }

        if($type == 'admin'){

            if(!$this->session->get('adminSessionId')){

                $this->session->set('adminSessionId',$sessionId);

                return $sessionId;
            }else{

                return $this->session->get('adminSessionId');
            }
        }
    }

    public function checkDeersPackageExistInCart($summaryData){

        $IPTVPackageId    = $summaryData['CartIPTVPackageId'];
        $AddOnPackageId   = $summaryData['CartAddOnPackageId'];
        $ISPPackageId     = $summaryData['CartISPPackageId'];

        $packageIds = array();
        $packageIds = array_merge($IPTVPackageId,$AddOnPackageId,$ISPPackageId);

        if( count($packageIds) > 0 ) {

            $countDeersPackage = $this->em->getRepository('DhiAdminBundle:Package')->countDeersPackageFromId($packageIds);

            if( $countDeersPackage > 0 ) {

                return true;
            }
        }

        return false;
    }

    public function serviceAPIErrorLog($user,$action,$api){

        if($user && $action && $api) {

            $objServiceApiErrorLog = $this->em->getRepository('DhiServiceBundle:ServiceApiErrorLog')->findOneBy(array('apiType' => $api, 'action' => $action, 'status' => 0));

            if(!$objServiceApiErrorLog) {

                $objServiceApiErrorLog = new ServiceApiErrorLog();
            }

            $objServiceApiErrorLog->setUser($user);
            $objServiceApiErrorLog->setAction($action);
            $objServiceApiErrorLog->setApiType($api);

            $this->em->persist($objServiceApiErrorLog);
            $this->em->flush();
        }
    }

    public function generateOrderNumber(){
        $today = date("YmdHi");
        $chars = array_merge(range(0,9), range('A', 'Z'), range('a', 'z'));

        $key = '';
        for($i=0; $i < 10; $i++) {
            $key .= $chars[mt_rand(0, count($chars) - 1)];
        }
        return $today . strtoupper($key);
    }

    public function generate_random_number($length = 7) {

        $character_array = array_merge(range(1, 9));
        $number = "";
        for($i = 0; $i < $length; $i++) {
            $number .= $character_array[rand(0, (count($character_array) - 1))];
        }
        return $number;
    }

    public function generateUniqueString($length = 20) {

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }


    function MaskCreditCard($cc){
        // Get the cc Length
        $cc_length = strlen($cc);
        // Replace all characters of credit card except the last four and dashes
        for($i=0; $i<$cc_length-4; $i++){
            if($cc[$i] == '-'){
                continue;
            }
            $cc[$i] = 'X';
        }
        // Return the masked Credit Card #
        return $cc;
    }

    function FormatCreditCard($cc)
    {
        // Clean out extra data that might be in the cc
        $cc = str_replace(array('-',' '),'',$cc);
        // Get the CC Length
        $cc_length = strlen($cc);
        // Initialize the new credit card to contian the last four digits
        $newCreditCard = substr($cc,-4);
        // Walk backwards through the credit card number and add a dash after every fourth digit
        for($i=$cc_length-5;$i>=0;$i--){
            // If on the fourth character add a dash
            if((($i+1)-$cc_length)%4 == 0){
                $newCreditCard = '-'.$newCreditCard;
            }
            // Add the current character to the new credit card
            $newCreditCard = $cc[$i].$newCreditCard;
        }
        // Return the formatted credit card number
        return $newCreditCard;
    }

    public function redeemTikilivePromoCode($purchasedSummaryData){
        $isError = false;
        $expiredPlansCount  = $this->em->getRepository("DhiUserBundle:UserService")->getExpiredPlans($purchasedSummaryData['UserId']);
        $promocode          = '';
        $tikipromo          = false;
        $totalPurchaseCount = $this->em->getRepository("DhiServiceBundle:PurchaseOrder")->countTotalPurchase($purchasedSummaryData['UserId']);
        $isToRedeem         = false;
        $tuser              = $this->em->getRepository("DhiUserBundle:User")->find($purchasedSummaryData['UserId']);
        $objPurchaseOrder   = $this->em->getRepository("DhiServiceBundle:PurchaseOrder")->find($purchasedSummaryData['PurchaseOrderId']);

        if($purchasedSummaryData['isISPPlanExtend'] == 1) {
            $isError = true;
        }

        if(!$objPurchaseOrder || ($tuser && $tuser->getIsEmployee())){
            $isError = true;
        }

        $isCodeApplied = $this->em->getRepository("DhiAdminBundle:TikilivePromoCode")->findOneBy(array('purchaseId' => $purchasedSummaryData['PurchaseOrderId']));
        if ($isCodeApplied) {
            $isError = true;
        }

        if ($isError == false && $tuser && $totalPurchaseCount == 1 && empty($expiredPlansCount)) {
            $isToRedeem = true;

        }else if ($isError == false && count($expiredPlansCount) > 0) {
            // $isToRedeem = true;

            if (!empty($purchasedSummaryData['ISP']) && !empty($expiredPlansCount[0]['bandwidth'])) {

                $expirydate  = $expiredPlansCount[0]['expiryDate'];
                $currentdate = new \DateTime();
                if (($expiredPlansCount[0]['isPlanActive'] == 1 && $expirydate > $currentdate) || $expiredPlansCount[0]['isPlanActive'] == false) {

                    $bandwidth = 0;
                    foreach ($purchasedSummaryData['ISP'] as $servicePurchase) {
                        if (!empty($servicePurchase['bandwidth']) && $expiredPlansCount[0]['bandwidth'] < $servicePurchase['bandwidth']) {

                            if (!empty($expiredPlansCount[0]['purchaseOrderId'])) {
                                $prevPromoCode = $this->em->getRepository("DhiAdminBundle:tikiLivePromoCode")->findOneBy(array('purchaseId' => $expiredPlansCount[0]['purchaseOrderId']));
                                if ($prevPromoCode) {
                                    $oldPlanName = $prevPromoCode->getPlanName();
                                }
                            }

                            $isToRedeem = true;
                            continue;
                        }
                    }

                }else if ($expiredPlansCount[0]['isPlanActive'] == 1 && $expirydate < $currentdate) {
                    $isToRedeem = true;
                }

            }
        }

        if ($isToRedeem == true){

            $objServicePurchase = $this->em->getRepository("DhiServiceBundle:ServicePurchase")->findOneBy(array('purchaseOrder' => $purchasedSummaryData['PurchaseOrderId']));
            $packageID          = $objServicePurchase->getPackageId();
            $objPackage         =  $this->em->getRepository("DhiAdminBundle:Package")->findOneBy(array('packageId'=>$packageID));
            if ($objPackage) {
                $packageType        = $objPackage->getPackageType();
                $isBundle           = $objPackage->getIsBundlePlan();
                $servicelocationId  = $objPackage->getServiceLocation()->getId();

                if($packageType == 'ISP' && !$isBundle){

                    $tikiLivePromoCode = $this->em->getRepository("DhiAdminBundle:TikilivePromoCode")->getTikilivePromocode($packageID);

                    if($tikiLivePromoCode){

                        if (!empty($oldPlanName) && !empty($tikiLivePromoCode[0]['promoCode']) && $oldPlanName == $tikiLivePromoCode[0]['planName']) {
                            $isError = true;
                        }

                        $promocode = $tikiLivePromoCode[0]['promoCode'];

                        if($promocode != '' && $isError == false){

                            $objpromocode = $this->em->getRepository("DhiAdminBundle:TikilivePromoCode")->find($tikiLivePromoCode[0]['id']);
                            $displayDate  = new \DateTime();

                            $objpromocode->setIsRedeemed('Yes');
                            $objpromocode->setDisplayDate($displayDate);
                            $objpromocode->setRedeemedBy($tuser);
                            $objpromocode->setPurchaseId($purchasedSummaryData['PurchaseOrderId']);
                            $this->em->persist($objpromocode);
                            $this->em->flush();
                            $tikipromo = true;
                        }
                    }
                }
            }
        }

        return array('promoCodeStatus' => $tikipromo, 'promocode' => $promocode);
    }
}
