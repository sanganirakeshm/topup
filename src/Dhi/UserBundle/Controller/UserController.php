<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\UserMacAddress;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dhi\UserBundle\Form\Type\UserMacAddressFormType;
use Dhi\UserBundle\Entity\ReferralInvitees;

/**
 */
class UserController extends Controller {

	/**
	 * This is the action that authenticates a customer against DEERS
	 */
	public function deersAuthAction() {

		$request = $this->getRequest ();
		$type = $request->get('type');
		$query_params = array();
		$param = '';
		if($type == 'milstarpayment' ){
			$param['type'] = $type;
		}

		$user = $this->get ( 'security.context' )->getToken ()->getUser ();
		$ipAddress = $this->get ( 'session' )->get ( 'ipAddress' );

		$apiurl = $this->container->getParameter ( 'deers_api_url' );
		//echo ($param)?'?'.http_build_query($param):'';exit;
		$url = $this->container->getParameter ( 'deers_current_site_url' ).(($param)?'?'.http_build_query(array('type='=>$param['type'])):'');

		if ($request->query->has ( 'AAFES_auth' ) && $request->query->get ( 'AAFES_auth' ) != null) {

			$apiurl = $apiurl . '?action=deers-authenticate&AAFES_auth=' . urlencode ( $request->query->get ( 'AAFES_auth' ) ) . '&auth_type=AAFES&username=' . $user->getUserName () . '&ip_address=' . $ipAddress;

			$ch = curl_init ();
			curl_setopt ( $ch, CURLOPT_URL, $apiurl );
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, 0 );
			curl_setopt ( $ch, CURLOPT_SSL_VERIFYHOST, false );
			curl_setopt ( $ch, CURLOPT_HTTPGET, 1 );
			curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
			$response = curl_exec ( $ch );
			curl_close ( $ch );

			$result = json_decode ( $response );

			if ($result == null or $result == '') {
				$result ['error'] = 'Due to internal issue, we can not process your request yet. Please try again later OR contact support.';
				exit ();
			}

			// Create object of ActivityLog
			$objActivityLog = new UserActivityLog ();

			// Create entity manager
			$em = $this->getDoctrine ()->getManager ();

			$user = $this->get ( 'security.context' )->getToken ()->getUser ();

			/* START: add user audit log for deers authentication */
			$activityLog = array (
					'user' => $user,
					'activity' => 'Deers authentication'
			);
			/* END: add user audit log for deers authentication */

			try {
				if ($result) {
					if ($result->result == 1) {
						$user->setIsDeersAuthenticated ( 1 );
						$user->setIsLastLogin ( 1 );
						$user->setDeersAuthenticatedAt ( new DateTime () );
						$user->setCid ( $result->cid );
						$em->persist ( $user );
						// $em->flush();

						$this->get ( 'session' )->getFlashBag ()->add ( 'success', "Deers authentication done successfully!" );

						// description for audit log activity
						$activityLog ['description'] = 'User ' . $user->getUsername () . ' has completed successfully Deers authentication';
					} else {
						$this->get ( 'session' )->getFlashBag ()->add ( 'failure', "You are either not Exchange customer or the authorization token has expired." );

						// description for audit log activity
						$activityLog ['description'] = 'User ' . $user->getUsername () . ' has failed Deers authentication';
					}
				} else {
					$this->get ( 'session' )->getFlashBag ()->add ( 'failure', "D.E.E.R.S authentication was unsuccessful . Please try again later or contact support." );
					$activityLog ['description'] = "Some error occured. might be API server down or something went wrong.";
				}
			} catch ( \Exception $e ) {
				$this->get ( 'session' )->getFlashBag ()->add ( 'failure', "D.E.E.R.S authentication was unsuccessful . Please try again later or contact support." );
				$activityLog ['description'] = $e;
			}

			$this->get ( 'ActivityLog' )->saveActivityLog ( $activityLog );
			$query_str = parse_url($request->getUri(), PHP_URL_QUERY);
			parse_str($query_str, $query_params);
			$em->flush ();

			if (!empty($query_params['type'])) {

				if($query_params['type'] == 'milstarpayment'){

					$this->get('session')->set('isDearsAuthenticatedForMilstar',1);
					return $this->redirect ( $this->generateUrl ( 'dhi_service_purchaseverification' ) );
				} else {

					return $this->redirect ( $this->generateUrl ( 'dhi_user_account' ) );
				}
			}else {

				return $this->redirect ( $this->generateUrl ( 'dhi_user_account' ) );
			}

		} else {

			return new RedirectResponse ( 'http://www.shopmyexchange.com/signin-redirect?loc=' . $url );
		}
		exit ();
	}



    public function checkDiscountCodeAction(Request $request){

        $userId           = $request->get('userId');
        $codeType         = $request->get('codeType');
        $amount           = $request->get('amount');
        $em               = $this->getDoctrine()->getManager();
        $user             = $this->get('security.context')->getToken()->getUser();
        $userDiscountCode = $request->get('userDiscountCode');
        $summaryData      = $this->get('DashboardSummary')->getUserServiceSummary();

        if($userDiscountCode){
            // get discount code detaisl
            $objPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('discountCode' => $userDiscountCode));
            $result = array();

            // Check for code exists in Database
            if($objPromoCode){
                $result['codeid'] = $objPromoCode->getId();

                //check for code is not expire
                $paymentDate   = date('M-d-Y');
                $codeDateBegin =  $objPromoCode->getStartDate()->format('M-d-Y');
                $codeDateEnd   =  $objPromoCode->getEndDate()->format('M-d-Y');
                if ((($paymentDate > $codeDateBegin) && ($paymentDate < $codeDateEnd) || ($paymentDate == $codeDateEnd) || ($paymentDate == $codeDateBegin)) && ($objPromoCode->getStatus() == 1)){
                    $result['dateValid'] = true;
                    $result['error'] = "codeIsActive";

                } else {
                    $result['dateValid'] = false;
                    $result['startdata'] = $paymentDate;
                    $result['bdate']     = $codeDateBegin;
                    $result['edate']     = $codeDateEnd;

                    if($paymentDate < $codeDateBegin){
                        $result['error'] = "codeInActive";
                    }
                    if($paymentDate >= $codeDateEnd){
                        $result['error'] = "codeExpire";
                    }
                }

                if($result['dateValid']){
                    // checkout for the user userd only once
                    $objDiscountCodeCustomerDetails = $em->getRepository('DhiAdminBundle:DiscountCodeCustomer')->findOneBy(array('DiscountCodeId' => $objPromoCode,'user' => $user));
                    if($objDiscountCodeCustomerDetails){
                        // if user alreary user discount code once
                        $result['locationuser'] = $objDiscountCodeCustomerDetails->getId();
                        $result['msg'] = "useralready user code";
                        $result['error'] = "alreayUsed";
                    }else{
                        $objDiscountCodeLocationDetails = $em->getRepository('DhiAdminBundle:DiscountCodeServiceLocation')->findBy(array('discountCodeId' => $objPromoCode));
                        $serviceLocationExist = 0;
                        foreach ($objDiscountCodeLocationDetails as $DiscServiceLocation) {
                            $discountSLId = $DiscServiceLocation->getServiceLocation()->getId();
                            $userSLId = $user->getUserServiceLocation()->getId();
                            if($discountSLId == $userSLId){
                                $serviceLocationExist = 1;
                            }
                        }
                        if($serviceLocationExist == 1){
                            
                            // if user never used discount code
                            if($objPromoCode->getAmountType() == 'amount'){
                                $discountAmount = $objPromoCode->getAmount();
                                if($amount > $discountAmount ){
                                    $AmountAfterdiscount = $amount - $discountAmount;
                                    $discountPercentage = ($discountAmount * 100) / $amount;
                                }else{
                                    // $discountAmount = $amount;
                                    // $AmountAfterdiscount = 00.00;
                                }
                            }else if ($objPromoCode->getAmountType() == 'percentage'){
                                $discountPercentage = $objPromoCode->getAmount();
                                $discountAmount = ($discountPercentage * $amount) / 100;
                                $AmountAfterdiscount = $amount - $discountAmount;
                            }

                            if($discountAmount > $amount){
                                $discountAmount = $amount;
                                $discountPercentage = 100;
                                $AmountAfterdiscount = 00.00;
                            }
                            
                            $result['msg']                = "Freash user code";
                            $result['discountAmount']     = $discountAmount;
                            if ($objPromoCode->getAmountType() == 'amount') {
                                $result['percentageType'] = 'amount';
                                $result['percentage']         = $discountAmount;
                                $result['discountPercentage'] = $discountPercentage; // $discountAmount;
                            } else if ($objPromoCode->getAmountType() == 'percentage') {
                                $result['percentageType']       = 'percentage';
                                $result['percentage']       = $discountPercentage;
                                $result['discountPercentage'] = $discountPercentage;
                            }
                            
                            $result['beformdisc']         = $amount;
                            $result['finalAmount']        = number_format($AmountAfterdiscount, 2, '.', '');
                            
                            $result['success']            = "codeIsValid";
                            $result['userid']             = $user->getId();

                            // update service purchase
                            $sessionId = $this->get('session')->get('sessionId');
                            $userCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId,'New');

                            $grossDiscount = $grossPayable = $grossFinal = 0;
                            foreach ($userCartItems as $item) {
                                $payableAmount = 0;
                                $payableAmount = $item->getPayableAmount();
                                if ($objPromoCode->getAmountType() == 'percentage') {
                                    $discountAmount = ($payableAmount * $discountPercentage) / 100;
                                } else if ($objPromoCode->getAmountType() == 'amount') {
                                    $discountPercentage = ($discountAmount * 100) / $payableAmount;
                                }
                                $finalPayableAmount = $payableAmount - $discountAmount;
                                $grossPayable = $grossPayable + $finalPayableAmount;
                                $finalcost = $item->getFinalCost() - $discountAmount ;
                                $grossDiscount = $grossDiscount + $discountAmount;
                                $grossFinal = $grossFinal + $finalcost;

                                if($item->getDiscountCodeApplied() == 0){
                                    $item->setPayableAmount($finalPayableAmount);
                                    $item->setFinalCost($finalcost);
                                    if ($objPromoCode->getAmountType() == 'percentage') {
                                        $item->setDiscountCodeRate($discountPercentage);
                                        $item->setDiscountCodeAmount($discountAmount);

                                    } else if ($objPromoCode->getAmountType() == 'amount') {
                                         $item->setDiscountCodeRate($discountPercentage);
                                         $item->setDiscountCodeAmount($discountAmount);
                                    }
                                    
                                    $item->setDiscountCodeApplied(1);
                                    $em->persist($item);
                                    $em->flush();
                                }    
                            }
                            //save activity log 
                            $activityLog['user'] = $user->getUsername();
                            $activityLog['activity'] = ' Global Prome Code used by user  ';
                            $activityLog['description'] = "User ".$user->getUsername()." has used Global promo code ".$objPromoCode->getDiscountCode();
                            $this->get('ActivityLog')->saveActivityLog($activityLog);
                            
                            $result["discountAmount"] = number_format($grossDiscount, 2, '.', '');
                            $result["payAmount"] = number_format($grossPayable,2, '.', '');
                            $result["finalcost"] = number_format($grossFinal,2, '.', '');

                            $this->get('session')->set('usedDiscountCodeId',$objPromoCode->getId());
                            $this->get('session')->set('usedDiscountCodeuserId',$user->getId());
                            $this->get('session')->set('finalDiscountedAmount',$AmountAfterdiscount);
                        }else{
                            $result['error'] = "codeNotExists";
                        }
                    }
                }else{
                    $result['msg'] = "code is not live";
                    $result['error'] = "codeNotExists";
                }
            }else{
                 $result['error'] = "codeNotExists";
            }

            $isCheckPartnerPromo = true;
            if($summaryData['IsIPTVAvailabledInCart'] == 0 and $summaryData['IsAddOnAvailabledInCart'] == 1){
                $isCheckPartnerPromo = false;
            }else{
                $isCheckPartnerPromo = true;
            }
            if($isCheckPartnerPromo == true && !empty($result['error']) && $result['error'] != 'codeIsActive'){
                $result = array();
                $objPackagePromoCode  = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('code' => $userDiscountCode));
                if($objPackagePromoCode){
                    $result['codeid'] = $objPackagePromoCode->getId();
                    $paymentDate   = date('M-d-Y');
                    $codeDateEnd   =  $objPackagePromoCode->getExpirydate() ? $objPackagePromoCode->getExpirydate()->format('M-d-Y 23:59:59') : null;

                    $isServiceLocationAvail = true;
                    if ($objPackagePromoCode->getServiceLocations()) {
                        if ($objPackagePromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                            $isServiceLocationAvail = false;
                        }
                    }

                    if($isServiceLocationAvail == false){
                        $result['error'] = 'codeNotExists';
                    }else if(!is_null($codeDateEnd) && $paymentDate > $codeDateEnd){
                        $result['error'] = "codeExpire";
                    }else if($objPackagePromoCode->getStatus() == 'Inactive' || $objPackagePromoCode->getIsPlanExpired() == 'Yes'){
                        $result['error'] = "codeInActive";
                    }else if($objPackagePromoCode->getIsRedeemed() == 'Yes'){
                        $result['msg']   = "useralready user code";
                        $result['error'] = "alreayUsed";
                    }else if($objPackagePromoCode->getCustomerValue() < 1){
                        $result['msg']   = "Invalid Promocode";
                        $result['error'] = "codeNotExists";
                    }else{
                        $discountAmount = $objPackagePromoCode->getCustomerValue();
                        $amountAfterdiscount = $amount - $discountAmount;
                        $discountPercentage = ($discountAmount * 100) / $amount;
                        if($discountPercentage > 100){
                            $discountPercentage = 100;
                            $discountAmount = $amount;
                            $amountAfterdiscount = 0;
                        }
                        
                        $result['error']              = "codeIsActive";
                        $result['msg']                = "Partner fresh user code";
                        $result['percentageType']     = "percentage";
                        $result['discountAmount']     = $discountAmount;
                        $result['percentage']         = number_format($discountPercentage, 2, '.', '');
                        $result['discountPercentage'] = number_format($discountPercentage, 2, '.', '');
                        $result['beformdisc']         = $amount;
                        $result['finalAmount']        = number_format($amountAfterdiscount, 2, '.', '');
                        $result['success']            = "codeIsValid";
                        $result['userid']             = $user->getId();
                        $result['percentageType']     = 'amount';
                        $sessionId = $this->get('session')->get('sessionId');
                        $userCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId,'New');
                        $grossDiscount = $grossPayable = $grossFinal = 0;
                        foreach($userCartItems as $item) {
                            $payableAmount = 0;
                            $payableAmount = $item->getPayableAmount();
                            $discountAmount = ($payableAmount * $discountPercentage) / 100;
                            $finalPayableAmount = $payableAmount - $discountAmount;
                            $grossPayable = $grossPayable + $finalPayableAmount;
                            $finalcost = $item->getFinalCost() - $discountAmount ;
                            $grossDiscount = $grossDiscount + $discountAmount;
                            $grossFinal = $grossFinal + $finalcost;

                            if($item->getDiscountCodeApplied() == 0){
                                $item->setPayableAmount($finalPayableAmount);
                                $item->setFinalCost($finalcost);
                                $item->setDiscountCodeRate($discountPercentage);
                                $item->setDiscountCodeAmount($discountAmount);
                                $item->setDiscountCodeApplied(2);
                                $item->setDiscountedPartnerPromocode($objPackagePromoCode);
                                $em->persist($item);
                                $em->flush();
                            }    
                        }
                        $this->get('session')->set('usedPDiscountCodeId',$objPackagePromoCode->getId());
                    }
                }else{
                    $result['error'] = "codeNotExists";
                    $isCheckPartnerPromo = false;
                }
            }

            // Business Promocode
            $checkBusinessPromo = true;
            if($summaryData['IsIPTVAvailabledInCart'] == 0 and $summaryData['IsAddOnAvailabledInCart'] == 1){
                $checkBusinessPromo = false;
            }else{
                $checkBusinessPromo = true;
            }
            if($checkBusinessPromo == true && !empty($result['error']) && $result['error'] != 'codeIsActive'){
                $result = array();
                $objPackagePromoCode  = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findOneBy(array('code' => $userDiscountCode));
                if($objPackagePromoCode){
                    $result['codeid'] = $objPackagePromoCode->getId();
                    $paymentDate      = date('M-d-Y');
                    $codeDateEnd      =  $objPackagePromoCode->getExpirydate() ? $objPackagePromoCode->getExpirydate()->format('M-d-Y 23:59:59') : null;

                    $isServiceLocationAvail = true;
                    if ($objPackagePromoCode->getServiceLocations()) {
                        if ($objPackagePromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                            $isServiceLocationAvail = false;
                        }
                    }

                    if($isServiceLocationAvail == false){
                        $result['error'] = 'codeNotExists';
                    }else if(!is_null($codeDateEnd) && $paymentDate > $codeDateEnd){
                        $result['error'] = "codeExpire";
                    }else if($objPackagePromoCode->getStatus() == 'Inactive' || $objPackagePromoCode->getIsPlanExpired() == 'Yes'){
                        $result['error'] = "codeInActive";
                    }else if($objPackagePromoCode->getIsRedeemed() == 'Yes'){
                        $result['msg']   = "useralready user code";
                        $result['error'] = "alreayUsed";
		
                    }else if($objPackagePromoCode->getCustomerValue() < 1){
			            $result['msg']   = "Invalid Promocode";
                        $result['error'] = "codeNotExists";

                    }else{
                        $discountAmount      = $objPackagePromoCode->getCustomerValue();
                        $amountAfterdiscount = $amount - $discountAmount;
                        $discountPercentage  = ($discountAmount * 100) / $amount;
                        if($discountPercentage > 100){
                            $discountPercentage  = 100;
                            $discountAmount      = $amount;
                            $amountAfterdiscount = 0;
                        }
                        
                        $result['error']              = "codeIsActive";
                        $result['msg']                = "Business code";
                        $result['discountAmount']     = $discountAmount;
                        $result['percentage']         = number_format($discountPercentage, 2, '.', '');
                        $result['discountPercentage'] = number_format($discountPercentage, 2, '.', '');
                        $result['beformdisc']         = $amount;
                        $result['finalAmount']        = number_format($amountAfterdiscount, 2, '.', '');
                        $result['success']            = "codeIsValid";
                        $result['userid']             = $user->getId();
                        $result['percentageType']     = "percentage";
                        $sessionId = $this->get('session')->get('sessionId');
                        $userCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId,'New');
                        $grossDiscount = $grossPayable = $grossFinal = 0;
                        foreach($userCartItems as $item) {
                            $payableAmount      = 0;
                            $payableAmount      = $item->getPayableAmount();
                            $discountAmount     = ($payableAmount * $discountPercentage) / 100;
                            $finalPayableAmount = $payableAmount - $discountAmount;
                            $grossPayable       = $grossPayable + $finalPayableAmount;
                            $finalcost          = $item->getFinalCost() - $discountAmount ;
                            $grossDiscount      = $grossDiscount + $discountAmount;
                            $grossFinal         = $grossFinal + $finalcost;

                            if($item->getDiscountCodeApplied() == 0){
                                $item->setPayableAmount($finalPayableAmount);
                                $item->setFinalCost($finalcost);
                                $item->setDiscountCodeRate($discountPercentage);
                                $item->setDiscountCodeAmount($discountAmount);
                                $item->setDiscountCodeApplied(3);
                                $item->setDiscountedBusinessPromocode($objPackagePromoCode);
                                $em->persist($item);
                                $em->flush();
                            }    
                        }
                        $this->get('session')->set('businessDiscountCodeId',$objPackagePromoCode->getId());
                        $isCheckPartnerPromo = true;
                    }
                }else{
                    $result['error'] = "codeNotExists";
                    $isCheckPartnerPromo = false;
                }
            }

            // for the employee promo code
            if ($isCheckPartnerPromo == false && !empty($result['error']) && $result['error'] != 'codeIsActive') {
                // get employee code detais
                $objEmployeePromoCode = $em->getRepository('DhiAdminBundle:EmployeePromoCode')->findOneBy(array('employeePromoCode' => $userDiscountCode));
                $result = array();

                // Check for code exists in Database
                if ($objEmployeePromoCode) {
                    $result['codeid'] = $objEmployeePromoCode->getId();

                    //check for code is not expire
                    $paymentDate = date('M-d-Y');
                    $codeDateBegin = $objEmployeePromoCode->getCreatedAt()->format('M-d-Y');
                    if (($paymentDate >= $codeDateBegin) && ($objEmployeePromoCode->getStatus() == 1)) {
                        // check for purchase history exists for user
                        $isPurchaseHistoryExists = $em->getRepository('DhiUserBundle:UserService')->findOneBy(array('user' => $user));
                        if (!$isPurchaseHistoryExists) {
                            $result['dateValid'] = true;
                            $result['error'] = "codeIsActive";
                        } else {
                            $result['dateValid'] = false;
                            $result['error'] = "codeIsActive";
                        }
                    } else {
                        $result['dateValid'] = false;
                        $result['startdata'] = $paymentDate;
                        $result['error'] = "codeInActive";
                    }
                }
                if (isset($result['dateValid']) && $result['dateValid'] == true ) {
                    // checkout for the user userd only once
                    $objEmployeePromoCodeCustomerDetails = $em->getRepository('DhiAdminBundle:EmployeePromoCodeCustomer')->findOneBy(array('EmployeePromoCodeId' => $objEmployeePromoCode, 'user' => $user));
                    if ($objEmployeePromoCodeCustomerDetails) {
                        // if user alreary user discount code once
                        $result['locationuser'] = $objEmployeePromoCodeCustomerDetails->getId();
                        $result['msg'] = "useralready user code";
                        $result['error'] = "alreayUsed";
                    } else {

                        // if user never used discount code
                        if ($objEmployeePromoCode->getAmountType() == 'amount') {
                            $discountAmount = $objEmployeePromoCode->getAmount();
                            if ($amount > $discountAmount) {
                                $AmountAfterdiscount = $amount - $discountAmount;
                            } else {
                                $AmountAfterdiscount = 00.00;
                            }
                        } else if ($objEmployeePromoCode->getAmountType() == 'percentage') {
                            $discountPercentage = $objEmployeePromoCode->getAmount();
                            $discountAmount = ($discountPercentage * $amount) / 100;
                            $AmountAfterdiscount = $amount - $discountAmount;
                        }

                        $result['msg'] = "Freash user code";
                        $result['discountAmount'] = $discountAmount;
                        if ($objEmployeePromoCode->getAmountType() == 'amount') {
                            $result['percentageType'] = 'amount';
                            $result['percentage'] = $discountAmount;
                            $result['discountPercentage'] = $discountAmount;
                        } else if ($objEmployeePromoCode->getAmountType() == 'percentage') {
                            $result['percentageType'] = 'percentage';
                            $result['percentage'] = $discountPercentage;
                            $result['discountPercentage'] = $discountPercentage;
                        }

                        $result['beformdisc'] = $amount;
                        $result['finalAmount'] = number_format($AmountAfterdiscount, 2, '.', '');

                        $result['success'] = "codeIsValid";
                        $result['userid'] = $user->getId();

                        // update service purchase
                        $sessionId = $this->get('session')->get('sessionId');
                        $userCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user, $sessionId, 'New');

                        $grossDiscount = $grossPayable = $grossFinal = 0;
                        $numberOfItemInCart = count($userCartItems);
                        foreach ($userCartItems as $item) {
                            if ($item->getIsAddon() == 1) {
                                $numberOfItemInCart = $numberOfItemInCart - 1;
                            }
                        }
                        foreach ($userCartItems as $item) {

                            if ($item->getIsAddon() == 0) {

//                                if($item->getBundleId() != null){
//                                    if($item->getService()->getName() == 'IPTV'){
//                                        $payableAmount = $summaryData['Cart']['IPTV']['RegularPack'][0]['actualAmount'];
//                                    }
//                                    if($item->getService()->getName() == 'ISP'){
//                                        $payableAmount = $summaryData['Cart']['ISP']['RegularPack'][0]['actualAmount'];
//                                    }
//                                   // $payableAmount = 0;
//                                   // $payableAmount = $item->getPayableAmount();
//                                    if ($objEmployeePromoCode->getAmountType() == 'percentage') {
//                                        $discountAmount = ($payableAmount * $discountPercentage) / 100;
//                                    } elseif ($objEmployeePromoCode->getAmountType() == 'amount') {
//                                        $discountAmount = $objEmployeePromoCode->getAmount() / $numberOfItemInCart;
//                                    }
//                                    $finalPayableAmount = $payableAmount - $discountAmount;
//                                    $grossPayable = $grossPayable + $finalPayableAmount;
//                                    $finalcost = $payableAmount - $discountAmount ;
//                                        $grossDiscount = $grossDiscount + $discountAmount;
//                                    $grossFinal = $grossFinal + $finalcost;
//                                }else{
                                $payableAmount = 0;
                                $payableAmount = $item->getPayableAmount();

                                if ($objEmployeePromoCode->getAmountType() == 'percentage') {
                                    $discountAmount = ($payableAmount * $discountPercentage) / 100;
                                } elseif ($objEmployeePromoCode->getAmountType() == 'amount') {
                                    $discountAmount = $objEmployeePromoCode->getAmount() / $numberOfItemInCart;
                                }

                                if ($payableAmount > $discountAmount) {
                                    $finalPayableAmount = $payableAmount - $discountAmount;
                                } else {
                                    $finalPayableAmount = 00.00;
                                    $discountAmount = $payableAmount;
                                }

                                $grossPayable = $grossPayable + $finalPayableAmount;
                                if ($item->getFinalCost() > $discountAmount) {
                                    $finalcost = $item->getFinalCost() - $discountAmount;
                                } else {
                                    $finalcost = 00.00;
                                }
                                $grossDiscount = $grossDiscount + $discountAmount;
                                $grossFinal = $grossFinal + $finalcost;
//                                }


                                if ($item->getDiscountCodeApplied() == 0) {
                                    $item->setPayableAmount($finalPayableAmount);
                                    $item->setFinalCost($finalcost);
                                    if ($objEmployeePromoCode->getAmountType() == 'percentage') {
                                        $item->setDiscountCodeRate($discountPercentage);
                                        $item->setDiscountCodeAmount($discountAmount);
                                    }
                                    if ($objEmployeePromoCode->getAmountType() == 'amount') {
                                        $item->setDiscountCodeAmount($discountAmount);
                                    }
                                    $item->setDiscountedEmployeePromocode($objEmployeePromoCode);
                                    $item->setDiscountCodeApplied(5);
                                    $em->persist($item);
                                    $em->flush();
                                }
                            }
                        }
                        //save activity log
                        $activityLog['user'] = $user->getUsername();
                        $activityLog['activity'] = ' Employee Prome Code used by user  ';
                        $activityLog['description'] = "User " . $user->getUsername() . " has used Employee promo code " . $objEmployeePromoCode->getEmployeePromoCode();
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $result["discountAmount"] = number_format($grossDiscount, 2, '.', '');
                        $result["payAmount"] = number_format($grossPayable, 2, '.', '');
                        $result["finalcost"] = number_format($grossFinal, 2, '.', '');

                        $this->get('session')->set('usedEmployeePromoCodeId', $objEmployeePromoCode->getId());
                        $this->get('session')->set('usedEmployeePromoCodeuserId', $user->getId());
                        $this->get('session')->set('finalEmployeePromoCodeDiscountedAmount', $grossFinal);
                    }
                } else {
                    $result['msg'] = "code is not live";
                    $result['error'] = "codeNotExists";
                }
            }
            
            // Check referral Promo Code
            if(!empty($result['error']) && $result['error'] != 'codeIsActive'){
                $result = array();
                $objReferralPromoCode  = $em->getRepository('DhiUserBundle:ReferralPromoCode')->findOneBy(array('promocode' => $userDiscountCode));
                if($objReferralPromoCode){
                    $result['codeid'] = $objReferralPromoCode->getId();
                    $paymentDate   = date('M-d-Y');
                    
                    if($objReferralPromoCode->getIsRedeemed() == 1){
                        $result['msg']   = "useralready user code";
                        $result['error'] = "alreayUsed";
                    }else{
                        $objSetting = $em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'friend_referral_reward_amount'));
                        $discountAmount = 0;
                        if($objSetting){
                            $discountAmount = $objSetting->getValue();
                        }
                        
                        $amountAfterdiscount = $amount - $discountAmount;
                        $discountPercentage = ($discountAmount * 100) / $amount;
                        if($discountPercentage > 100){
                            $discountPercentage = 100;
                            $discountAmount = $amount;
                            $amountAfterdiscount = 0;
                        }
                        
                        $result['error']              = "codeIsActive";
                        $result['msg']                = "Referral freash user code";
                        $result['discountAmount']     = $discountAmount;
                        $result['percentage']         = number_format($discountPercentage, 2, '.', '');
                        $result['discountPercentage'] = number_format($discountPercentage, 2, '.', '');
                        $result['beformdisc']         = $amount;
                        $result['finalAmount']        = number_format($amountAfterdiscount, 2, '.', '');
                        $result['success']            = "codeIsValid";
                        $result['userid']             = $user->getId();
                        $result['percentageType']     = 'amount';
                        $sessionId = $this->get('session')->get('sessionId');
                        $userCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId,'New');
                        $grossDiscount = $grossPayable = $grossFinal = 0;
                        foreach($userCartItems as $item) {
                            $payableAmount = 0;
                            $payableAmount = $item->getPayableAmount();
                            $discountAmount = ($payableAmount * $discountPercentage) / 100;
                            $finalPayableAmount = $payableAmount - $discountAmount;
                            $grossPayable = $grossPayable + $finalPayableAmount;
                            $finalcost = $item->getFinalCost() - $discountAmount ;
                            $grossDiscount = $grossDiscount + $discountAmount;
                            $grossFinal = $grossFinal + $finalcost;

                            if($item->getDiscountCodeApplied() == 0){
                                $item->setPayableAmount($finalPayableAmount);
                                $item->setFinalCost($finalcost);
                                $item->setDiscountCodeRate($discountPercentage);
                                $item->setDiscountCodeAmount($discountAmount);
                                $item->setDiscountCodeApplied(4);
                                $item->setDiscountedReferralPromoCode($objReferralPromoCode);
                                $em->persist($item);
                                $em->flush();
                            }    
                        }
                        $this->get('session')->set('usedReferralDiscountCodeId',$objReferralPromoCode->getId());
                    }
                }else{
                    $result['error'] = "codeNotExists";       
                }
            }
        }else{
            $result['error'] = "emptyCode";
        }
        $response = new Response(json_encode($result));
	    $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function inviteFriendAction(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        if($this->get('session')->has('isActiveReferModule') && $this->get('session')->get('isActiveReferModule') == 0){
            $this->get('session')->getFlashBag()->add('failure', 'Something wrong in request.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }
        
        $allowInvite = 5;
        $user= $this->get('security.context')->getToken()->getUser();
        if($user == 'anon.'){
            return $this->redirect($this->generateUrl('fos_user_security_login'));
        }
        $objReferralInvitees = $em->getRepository('DhiUserBundle:ReferralInvitees')->findBy(array('userId' => $user));
        $remainingInvite = $allowInvite - count($objReferralInvitees);
        if($request->getMethod() == "POST"){
         
            if($this->get('session')->has('affiliate')){
                
               $affiliateValue =  $this->get('session')->get('affiliate');
               
               if($affiliateValue == 'bv'){
                   $url = 'dhi_signup';
               }else if($affiliateValue == 'netgate'){
                   $url = 'dhi_signup_netgate';
               }
            }else{
                $url = 'fos_user_registration_register';
            }
            
            $whitelabel = $this->get('session')->get('brand');
            if($whitelabel){
                $fromEmail = $whitelabel['fromEmail'];
                $compnayname = $whitelabel['name'];
                $compnaydomain = $whitelabel['domain'];
            } else {
                $fromEmail       = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                $compnayname     = 'ExchangeVUE';
                $compnaydomain   = 'exchangevue.com';
            }
            
            $objWhiteLabel = null;
            if(!empty($whitelabel['id'])){
                $objWhiteLabel = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whitelabel['id']);
            }
            
            $isSendAnyInvitation = false;
            $userFnameLname = ($user->getFirstname() || $user->getLastname()) ? $user->getFirstname() . ' '. $user->getLastname() : $user->getUsername();

            
            $referFriendEmail = $request->get('txtEmailid_1');
            $isFormValid = true;
            $referEmailId = '';
            if(isset($referFriendEmail)){
                $referEmailId = $referFriendEmail;
            }
            if(empty($referEmailId)){

                $this->get('session')->getFlashBag()->add('failure', 'Please enter Email Id.');
                $isFormValid = false;
            }else if( !empty($referEmailId) && !(filter_var($referEmailId, FILTER_VALIDATE_EMAIL))) {

                $this->get('session')->getFlashBag()->add('failure', 'Please enter valid Email Id.');
                $isFormValid = false;
            }else if(!empty($referEmailId)){

                $checkReferralInviteesFlag = $em->getRepository("DhiUserBundle:ReferralInvitees")->findBy(array('emailId' => $referEmailId));
                if(count($checkReferralInviteesFlag) > 0 ){

                    $this->get('session')->getFlashBag()->add('failure', 'Invitation is already sent to this email id.');
                    $isFormValid = false;
                }
            }
            if(!$isFormValid){
                return $this->render('DhiUserBundle:ReferralFriend:index.html.twig', array(
                    'remainingInvitee' => $remainingInvite,
                ));
            }
            
            
            for($i=1 ; $i <=5 ; $i++){
                
                if($request->get('txtEmailid_'.$i) != ''){
                    
                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                    $token = '';
                    $random_token_length = 10;
                    for ($len = 0; $len < $random_token_length; $len++) {
                        $token .= $characters[rand(0, strlen($characters) - 1)];
                    }
                    
                    $emailId = $request->get('txtEmailid_'.$i);
                    
                    $encodeEmail = base64_encode($emailId);
                    $encodeToken = base64_encode($token);
                    
                    $objReferralInvitee = new ReferralInvitees();
                    $objReferralInvitee->setUserId($user);
                    $objReferralInvitee->setEmailId($emailId);
                    $objReferralInvitee->setIsPurchased(0);
                    $objReferralInvitee->setToken($token);
                    $objReferralInvitee->setIsRegister(0);
                    $objReferralInvitee->setWhiteLabel($objWhiteLabel);
                    $em->persist($objReferralInvitee);
                    $em->flush();
                    
                    $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:referral_invitees_email.html.twig', array('url' => $url, 'email' => $encodeEmail, 'token' => $encodeToken, 'companyname' => $compnayname, 'userFnameLname' => $userFnameLname));
                    
                    $send_email_refer = \Swift_Message::newInstance()
                        ->setSubject($compnayname. " - You are invited to register")
                        ->setFrom($fromEmail)
                        ->setTo($emailId)
                        ->setBody($body->getContent())
                        ->setContentType('text/html');

                    $this->container->get('mailer')->send($send_email_refer);
                    $isSendAnyInvitation = true;
                }
            }
            if($isSendAnyInvitation){
                $this->get('session')->getFlashBag()->add('success', "Invitation has been sent successfully.");
            }
            return $this->redirect($this->generateUrl('dhi_user_refer_friends'));
        } 
        return $this->render('DhiUserBundle:ReferralFriend:index.html.twig', array(
            'remainingInvitee' => $remainingInvite
        ));
    }
    
    public function checkUniqueInvitationAction(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        $fieldName= $request->get('fieldName');
        $fieldValue = $request->get($fieldName);
        
        
        if(!empty($fieldValue)){
            
            $checkUserFlag = $em->getRepository("DhiUserBundle:User")->findBy(array('email' => $fieldValue));
            $checkReferralInviteesFlag = $em->getRepository("DhiUserBundle:ReferralInvitees")->findBy(array('emailId' => $fieldValue));
            
            if(count($checkUserFlag) > 0 || count($checkReferralInviteesFlag) > 0){
                $status = "false";
            }else{
                $status = "true";
            }
        }else{
            $status = "true";
        }
        $response = new Response($status);
        return $response;
    }
}
