<?php

namespace Dhi\TvodBundle\Controller;

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
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\AdminBundle\Entity\Credit;
use Dhi\UserBundle\Entity\PromoCode;

//use Dhi\AdminBundle\Form\Type\EmailCampaignSearchFormType;
use Dhi\AdminBundle\Form\Type\PromoCodeFormType;

class ServiceController extends Controller {

    protected $failedErrNo = array('1001', '1002', '1003');

    public function ajaxAddToCartPackage(Request $request) {
        $jsonResponse = array();
        $jsonResponse['result']        = 'success';
        $jsonResponse['errMsg']        = '';
        $jsonResponse['succMsg']       = '';
        $jsonResponse['lastTriggerId'] = '';

        $em     = $this->getDoctrine()->getManager();
        $isUser = false;
        $uEmail = $request->get('login');
        $idVod  = $request->get('idVod');
        $title  = $request->get('title');
        $poster = $request->get('poster');
        $price  = $request->get('price');
        $returnUrl = $request->get('return_url');
        $cancleUrl = $request->get('cancel_url');
        $expireDate = $request->get('expire');

        if(!$title){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Please enter title';
            $isUser = false;
        }

        if(!$price){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Please enter Vod price';
            $isUser = false;
        }

        if($uEmail){
            $user = $em->getRepository("DhiUserBundle:User")->findOneBy(array('username' => $uEmail, 'enabled' => 1, 'locked'=>0, 'isDeleted'=>0));
            if($user){
                $isUser = true;
            }else{
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Login does not exist';
                $isUser = false;
            }

        }else{
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Login is empty';
            $isUser = false;
        }

        if($returnUrl){
            $this->get('session')->set('return_url', $request->get('return_url'));

        }else{
            $isUser = false;
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'return_url is empty';
        }

        if($cancleUrl){
            $this->get('session')->set('cancel_url', $request->get('cancel_url'));

        }else{
            $isUser = false;
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'cancel_url is empty';
        }

        if(!$idVod){
            $isUser = false;
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'idVod is missing';
        }


        // Expire date
        if($expireDate){
            $d = DateTime::createFromFormat('Y-m-d', $expireDate);
            if($d && $d->format('Y-m-d') === $expireDate){
                $expireDate = new \DateTime($expireDate);
                //$time->format('Y-m-d H:i:s');
                $objDays = date_diff($expireDate, new \DateTime());
                $validity    = $objDays->days;
            }else{
                $isUser = false;
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Invalid Expire date.';
            }
        }else{
            $validity = 2;
        }

        if($isUser){
            $sessionId      = $this->get('paymentProcess')->generateCartSessionId();
            $userId         = $user->getId();
            $packageType    = 'TVOD';
            $service        = 'TVOD';
            //$summaryData    = $this->get('DashboardSummary')->getUserServiceSummary('', $user, true);
        	// $isDeersAuthenticated 	= $this->get('DeersAuthentication')->checkDeersAuthenticated('', $user->getId());

        	if ($jsonResponse['result'] == 'success') {
                //$objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId'=>$idVod));
                if ($idVod != '') {
                    //$jsonResponse['lastTriggerId']  = 'iptvAddToCartBtn'.$objPackage->getId();
                    $objService = $em->getRepository('DhiUserBundle:Service')->findOneByName($service);
                    $userServiceLocation = $this->get('UserLocationWiseService')->getUserServiceLocationFromAdmin($user);
                    $userServiceLocation = array_keys($userServiceLocation);

    	    		if ($objService && in_array($service, $userServiceLocation)) {
                        

    	    			$servicePlanData = array();
    	    			$servicePlanData['IsUpgrade']	= 0;

    	    			//if ($objPackage) {

    	    				//else if ($packageType == 'AddOns' && $summaryData['IsIPTVAvailabledInCart'] != 1 && $summaryData['IsIPTVAvailabledInPurchased'] != 1){

    	    					$jsonResponse['result'] = 'error';
    	    					$jsonResponse['errMsg'] = 'You have to choose ExchangeVUE plan for additional plan.';

    		    				//Check data added into service purchase
    		    				
		    					$condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService->getId(), 'user' => $user->getId(), 'isAddon' => 0);
    			    			$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);

                                $isDiscountApplied = 0;
                                $payablePrice = $price;
    			    			if (!$objServicePurchase) {
    			    				$objServicePurchase = new ServicePurchase();
                                    $this->get('session')->remove('InAppPromoCodeId');
    			    			}else{
                                    $isDiscountApplied = $objServicePurchase->getDiscountCodeApplied();
                                    $payablePrice = $objServicePurchase->getPayableAmount();
                                    if ($price != $objServicePurchase->getActualAmount()) {
                                        $isDiscountApplied = 0;
                                    }
                                }
    			    			$servicePlanData['IsUpgrade']	= 0;
                                $servicePlanData['Service']       = $objService;
                                $servicePlanData['PackageId']     = $idVod;
                                $servicePlanData['PackageName']   = $title;
                                $servicePlanData['ActualAmount']  = $price;
                                $servicePlanData['PayableAmount'] = $payablePrice;
                                $servicePlanData['FinalCost']     = $payablePrice;
                                $servicePlanData['TermsUse']      = 1;
                                $servicePlanData['Bandwidth']     = 0;
                                $servicePlanData['Validity']      = $validity;
                                $servicePlanData['Discount']      = 0;
                                $servicePlanData['purchase_type'] = "TVOD";
                                $servicePlanData['discountCodeApplied'] = $isDiscountApplied;
                                $servicePlanData['serviceLocation']  = $user->getUserServiceLocation() ? $user->getUserServiceLocation() : null;

    			    			if (strtoupper($service) == 'TVOD') {
    			    				if (strtoupper($packageType) == 'TVOD') {
    			    					$jsonResponse = $this->get('cartProcess')->addToCartIPTVPlan($objServicePurchase, $servicePlanData, $user, 'user', false, true);
    			    				}

                                } else {
    			    				$jsonResponse['result'] = 'error';
    			    				$jsonResponse['errMsg'] = $service.' service not available.';
    			    			}

                                //Update Discount data
    			    			//$discount = $this->get('BundleDiscount')->getBundleDiscount();
    	    				

    	    		} else {

    	    			$jsonResponse['result'] = 'error';
    	    			$jsonResponse['errMsg'] = $service.' service not available.';
    	    		}
    	    	} else {

    	    		$jsonResponse['result'] = 'error';
    	    		$jsonResponse['errMsg'] = 'idVod does not exist';
    	    	}
        	}

        	return $jsonResponse;
        }else{
            $jsonResponse['result'] = 'error';
            return $jsonResponse;
        }
    }

    /*public function ajaxRemoveCartPackageAction(Request $request) {

    	$jsonResponse = array();
    	$jsonResponse['result'] 	= 'success';
    	$jsonResponse['errMsg'] 	= '';
    	$jsonResponse['succMsg'] 	= '';

    	$user 			= $this->get('security.context')->getToken()->getUser();
    	$em 			= $this->getDoctrine()->getManager();

    	$sessionId			= $this->get('paymentProcess')->generateCartSessionId();
    	$servicePurchaseId 	= $request->get('servicePurchaseId');
    	$service 			= $request->get('service');
    	$isAddonsPack 		= $request->get('isAddonsPack');

    	if ($servicePurchaseId && in_array($service, array('IPTV','ISP'))) {

    		$displayName = $service;
    		if (strtoupper($service) == 'IPTV') {

    			if ($isAddonsPack) {

    				$displayName = 'ExchangeVUE';
    			} else {

    				$displayName = 'Additional';
    			}
    		} else if((strtoupper($service) == 'ISP')){
					$displayName = 'ISP';
			} else{

			}

    		//Get Service object
    		$objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => strtoupper($service)));

    		//Delete cart service
    		$objDeletePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->deleteServicePackage($sessionId, $user, $objService, $servicePurchaseId, $isAddonsPack);

    		if ($objDeletePurchase) {

    			$jsonResponse['succMsg'] = $displayName.' plan has been removed successfully from cart.';
    			$jsonResponse['response'] = array('service' => $service);
    		} else {

    			$jsonResponse['result'] = 'error';
    			$jsonResponse['errMsg'] = 'Something went to wrong in delete action. please try again.';
    		}

    		$discount = $this->get('BundleDiscount')->getBundleDiscount();
    	} else {

    		$jsonResponse['result'] = 'error';
    		$jsonResponse['errMsg'] = 'Invalid package request.';
    	}

    	echo json_encode($jsonResponse);
    	exit;
    }*/

    public function purchaseverificationAction(Request $request) {
        $cartStatus = $this->ajaxAddToCartPackage($request);

        if($cartStatus['result'] == 'error'){
            $response = array(
                'status'    => 'error',
                'message'   => (isset($cartStatus['errMsg']) && $cartStatus['errMsg'] != '') ?$cartStatus['errMsg']:'Invalid requested parameters',
            );

            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }

        $uEmail                                  = $request->get('login');
        $em                                      = $this->getDoctrine()->getManager();
        $sessionId                               = $this->get('session')->get('sessionId'); //Get Session Id
        $user                                    = $em->getRepository("DhiUserBundle:User")->findOneBy(array('username' => $uEmail));
        $userServiceLocation                     = $this->get('UserLocationWiseService')->getUserServiceLocationFromAdmin($user);
        $userServiceLocation['IsMilstarEnabled'] = 0;
        $summaryData                             = $this->get('DashboardSummary')->getUserServiceSummary('', $user, true);
        $isDeersAuthenticated                    = $this->get('DeersAuthentication')->checkDeersAuthenticated('', $user->getId());
        $isDeersPackageExistInCart               = $this->get('paymentProcess')->checkDeersPackageExistInCart($summaryData);
        $orderNumber                             = $this->get('PaymentProcess')->generateOrderNumber();
        $this->get('session')->set('orderNumber', $orderNumber);

        if (!in_array('TVOD', array_keys($userServiceLocation))) {
            $response = array(
                'status'    => 'error',
                'message'   => 'Service not available in service location'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }

        if($summaryData['CartAvailable'] == 0){
            $response = array(
                'status'    => 'error',
                'message'   => 'Invalid Page Request'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }

        $form = $this->createFormBuilder(array())->getForm();

        $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findby(array('user' => $user, 'paymentStatus' => 'New', 'sessionId' => $sessionId));
        $isDearsAuthenticatedForMilstar = '';
		$isDearsAuthenticatedForMilstar = $this->get('session')->get('isDearsAuthenticatedForMilstar');
		if($isDearsAuthenticatedForMilstar){
			$isDearsAuthenticatedForMilstar = 1;
		} else {
			$isDearsAuthenticatedForMilstar = 2;
		}


        if(empty($summaryData['IsTVODAvailabledInCart'])){
            $response = array(
                'status'    => 'error',
                'message'   => 'Something went wrong on server.'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }

        $chaseArr = array(
            "secure_id"    => $this->container->getParameter("chase_hosted_secure_id"),
            "callback_url" => $this->container->getParameter("chase_callback_url"),
            "src"          => $this->container->getParameter("chase_iframe_url")
        );

        $chaseArr['src'] = $chaseArr['src']."=".$chaseArr['secure_id'];
        $chaseArr['src'] = $chaseArr['src']."&callback_url=".$chaseArr['callback_url'];

        return $this->render('DhiTvodBundle:Service:purchaseConfirm.html.twig', array(
            'objServicePurchase'             => $objServicePurchase,
            'form'                           => $form->createView(),
            'expiresMonth'                   => $this->creditCardYearMonth('month'),
            'expiresYear'                    => $this->creditCardYearMonth('year'),
            'country'                        => $em->getRepository('DhiUserBundle:Country')->getCreditCardCountry(),
            'summaryData'                    => $summaryData,
            'isISPAddedForTVOD'              => true,//$isISPAddedForIPTV,
            'userServiceLocation'            => $userServiceLocation,
            'creditBalance'                  => 0,//($user->getUserCredit()) ? $user->getUserCredit()->getTotalCredits() : 0,
            'isDeersAuthenticated'           => $isDeersAuthenticated,
            'isDearsAuthenticatedForMilstar' => $isDearsAuthenticatedForMilstar,
            'userId'                         => $user->getId(),
            'username'                       => $user->getUsername(),
            'status'                         => 'success',
            'chaseParams'                    => $chaseArr,
            'orderNumber'                    => $orderNumber,
            'username'                       => $user->getUsername()
        ));
    }

    public function orderComfirmationAction($userId) {

        //Clear Session Data
        $this->get('PaymentProcess')->clearPaymentSession();

        $view = array();

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository("DhiUserBundle:User")->find($userId);

        $orderNumber = $this->getRequest()->get('ord');

        $purchasedSummaryData = $this->get('paymentProcess')->paymentSuccessSummary($orderNumber);

        if (!$purchasedSummaryData) {
            $response = array(
                'status'    => 'error',
                'message'   => 'Invalid Page Request'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }

        if($user->getId()!= $purchasedSummaryData['UserId']) {

            $response = array(
                'status'    => 'error',
                'message'   => 'Invalid Page Request - User not found'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }

        if($this->get('session')->get('sendPurchaseEmail') == 1 && $purchasedSummaryData['PurchaseEmailSent'] != 1){
            if($this->get('paymentProcess')->sendPurchaseEmail($purchasedSummaryData, true)){
                $purchasedSummaryData['PurchaseEmailSent'] = 1;
                $this->get('session')->remove('sendPurchaseEmail');
            }
        }

        if($this->get('session')->has('InAppPromoCodeId') && $purchasedSummaryData['PaymentStatus'] == 'Completed'){
            $InAppPromoCode = $this->get('session')->get('InAppPromoCodeId');
            if (!empty($InAppPromoCode)) {
                $objPromoCode = $em->getRepository('DhiUserBundle:InAppPromoCode')->findOneBy(array('id'=>$InAppPromoCode));
                if ($objPromoCode) {
                    $objPromoCode->setCustomer($user);
                    $objPromoCode->setIsRedeemed('Yes');
                    $objPromoCode->setRedeemDate(new \DateTime(date('Y-m-d H:i:s')));
                    $em->persist($objPromoCode);
                    $em->flush();
                    $this->get('session')->remove('InAppPromoCodeId');
                }
            }
        }

        if($this->get('session')->get('paymentMessage') && $this->get('session')->get('paymentMessage') != ''){
            $view['message']    = $this->get('session')->get('paymentMessage');
            $this->get('session')->remove('paymentMessage');
        }else{
            $view['message']    = '';
        }
        $view['purchasedSummaryData'] = $purchasedSummaryData;
        $view['url'] = $this->get('session')->get('return_url');
        $view['status']     = 'success';
        $view['errNo']      = '';
        return $this->render('DhiTvodBundle:Service:purchaseSuccess.html.twig', $view);
    }

    public function paymentCancelAction() {

        //$user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $sessionId = $this->get('session')->get('sessionId'); //Get Session Id
        $response = array(
            'status'    => 'error',
            'message'   => 'Your payment has been cancelled.',
            'type'      => 'cancel'
        );
        return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
    }

    public function purchaseFailAlertAction(Request $request) {
        $this->get('PaymentProcess')->clearPaymentSession();
        $view = array();
        $em = $this->getDoctrine()->getManager();
        $errNo = $this->getRequest()->get('err');

        if($this->getRequest()->get('ord')){
            $orderNumber = $this->getRequest()->get('ord');
            $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneByOrderNumber($orderNumber);
            $purchasedSummaryData = $this->get('paymentProcess')->paymentSuccessSummary($orderNumber);
        }
        $params = $request->query->get('params');

        $view['purchasedSummaryData'] = isset($purchasedSummaryData) ? $purchasedSummaryData : array();
        $view['errNo']         = $errNo;
        $view['purchaseOrder'] = isset($purchaseOrder) ? $purchaseOrder : '';
        if(isset($params['type']) && $params['type'] == 'cancel'){
            $view['url']    = $this->get('session')->get('cancel_url');
        }else{
           $view['url']    = $this->get('session')->get('return_url');
        }

        if(empty($view['url'])){
            $this->get('session')->getFlashBag()->add('notice', 'Invalid Request. "return_url" or "cancel_url" should not be empty.');
            return $this->redirect($this->generateUrl('dhi_user_homepage'));
        }

        $view['status']        = isset($params['status']) ? $params['status'] :'';
        $view['message']       = isset($params['message']) ? $params['message'] :'';
        return $this->render('DhiTvodBundle:Service:purchaseSuccess.html.twig', $view);
    }

    public function creditCardYearMonth($type) {

        $data = array();

        if ($type == 'month') {

            for ($i = 1; $i <= 12; $i++) {

                $data[$i] = $i;
            }
        }

        if ($type == 'year') {

            $year = date('Y');
            for ($i = 0; $i <= 10; $i++) {

                $yy = $year + $i;
                $data[$yy] = $yy;
            }
        }

        return $data;
    }
    

    public function activateFreePlanAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $userId = $request->get('userId');
        if ($userId) {
            $user   = $em->getRepository("DhiUserBundle:User")->find($userId);
        }

        $sessionId            = $this->get('session')->get('sessionId'); //Get Session Id
        $summaryData          = $this->get('DashboardSummary')->getUserServiceSummary('', $user, true);
        $orderNumber          = $this->get('PaymentProcess')->generateOrderNumber();
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated('', $userId);

        if ($request->getMethod() == "POST" && $summaryData) {

            $isDeersPackageExistInCart   = $this->get('paymentProcess')->checkDeersPackageExistInCart($summaryData);

            if($isDeersPackageExistInCart && $isDeersAuthenticated == 2) {
                $response = array(
                    'status'    => 'error',
                    'message'   => 'DEERS authentication is required for purchase package.'
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            }

            $paybleAmount = $request->get('paybleAmount');

            if ($paybleAmount == 0) {

                if ($summaryData['CartAvailable'] == 1) {

                	$this->get('session')->set('IsISPAvailabledInCart',$summaryData['IsISPAvailabledInCart']);
                	$this->get('session')->set('IsIPTVAvailabledInCart',$summaryData['IsIPTVAvailabledInCart']);

                    $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);

                    if($isSelevisionUser == 0) {
                         $response = array(
                             'status'    => 'error',
                             'message'   => 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists'
                         );
                         return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
                    }

                	$cartTotalAmount = $summaryData['TotalCartAmount'];

                	
                    $isPromoCodeApplied = false;
                    if (!empty($summaryData['isDiscountCodeApplied']) && $summaryData['isDiscountCodeApplied'] == 7) {
                        $objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneByCode('InAppPromoCode');
                        if ($objPaymentMethod) {
                            $isPromoCodeApplied = true;
                        }else{
                            $response = array(
                                'status'    => 'error',
                                'message'   => 'Something wend wrong on server. Payment Method "InAppPromoCode" does not exists.'
                            );
                            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
                        }
                    }else{
                        $objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneByCode('FreePlan');
                        if (!$objPaymentMethod) {
                            $response = array(
                                'status'    => 'error',
                                'message'   => 'Something wend wrong on server. Payment Method "FreePlan" does not exists.'
                            );
                            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));   
                        }
                    }

                	$objPurchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneBy(array('sessionId' => $sessionId, 'paymentStatus' => 'InProcess'));

                	if (!$objPurchaseOrder) {
                		$objPurchaseOrder = new PurchaseOrder();
                	}
                	$objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                	$objPurchaseOrder->setSessionId($sessionId);
                	$objPurchaseOrder->setOrderNumber($orderNumber);
                	$objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentBy('User');
                    $objPurchaseOrder->setPaymentByUser($user);
                	$objPurchaseOrder->setTotalAmount($cartTotalAmount);
                	$objPurchaseOrder->setPaymentStatus('Completed');

                	$em->persist($objPurchaseOrder);
                	$em->flush();
                	$insertIdPurchaseOrder = $objPurchaseOrder->getId();

                	if ($insertIdPurchaseOrder) {

                		$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy(array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user->getId()));
                		if($objServicePurchase){
                			foreach ($objServicePurchase as $servicePurchase){
                				$servicePurchase->setPurchaseOrder($objPurchaseOrder);
                				$servicePurchase->setPaymentStatus('Completed');
                				$em->persist($servicePurchase);
                				$em->flush();
                			}
                		}
                        
                        if($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsTVODAvailabledInCart'] == 1 || $summaryData['IsAddOnAvailabledInCart']) {
                            $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                            if($isSelevisionUser == 0) {
                                $response = array(
                                    'status'    => 'error',
                                    'message'   => 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists'
                                );

                                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
                            }
                        }

                        $this->get('session')->set('PurchaseOrderId', $insertIdPurchaseOrder);
                        $this->get('session')->set('PurchaseOrderNumber', $orderNumber);

                        //Add Activity Log
                        $activityLog = array();
                        $activityLog['user']        = $user;
                        $activityLog['activity']    = 'Purchase Order';
                        $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment In process.';
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        //Activity Log end here

                		$paymentRefundStatus = $this->get('packageActivation')->activateVodService($user,'admin');
                		if($paymentRefundStatus['status']){
                            $isPaymentRefund = $this->get('paymentProcess')->refundPayment(array(), $user, true);
                            if($paymentRefundStatus['reason'] != ''){
                                $this->get('session')->set('paymentMessage', $paymentRefundStatus['reason']);
                            }else{
                                $this->get('session')->remove('paymentMessage');
                            }
                        }
                        $this->get('session')->set('sendPurchaseEmail',1);

                		return $this->redirect($this->generateUrl('dhi_tvod_purchase_order_confirm', array('ord' => $orderNumber, 'userId' => $userId)));

                	}else{
                		$response = array(
                            'status'    => 'error',
                            'message'   => 'Order could not procced, please check order amount.'
                        );
                        return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
                	}
                } else {
                    $response = array(
                        'status'    => 'error',
                        'message'   => 'Data not found in cart.'
                    );
                    return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
                }
            } else {
                $response = array(
                    'status'    => 'error',
                    'message'   => 'Order could not procced, please check order amount'
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            }
        } else {

            $response = array(
                'status'    => 'error',
                'message'   => 'Invalid Page Request'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }
    }

}
