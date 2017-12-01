<?php

namespace Dhi\TvodBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use \DateTime;
use Dhi\UserBundle\Entity\InAppPromoCode;

class InAppPromoCodeController extends Controller {

	public function applyPromoCodeAction($userId, Request $request){
		$em               = $this->getDoctrine()->getManager();
		$response = array('status' => 'fail', 'msg' => 'Code does not exists', 'code' => '', 'result' => array());
		$amount           = $request->get('amount');
		$user = $em->getRepository("DhiUserBundle:User")->findOneBy(array('id' => $userId, 'enabled' => 1, 'locked'=>0, 'isDeleted'=>0));
		$sessionId     = $this->get('session')->get('sessionId');
		$code = $request->get('code');
		if ($user) {
			if (!empty($code)) {
				$objPromoCode = $em->getRepository('DhiUserBundle:InAppPromoCode')->findOneBy(array('promoCode' => $code, 'status' => "Active"));
				$currentDate  = new DateTime();
				if ($objPromoCode) {
					$response['code'] = $code;
					$expireDate       = $objPromoCode->getExpiredAt();
					$isExpired        = $this->checkDate($currentDate, $expireDate);
					if ($isExpired) {
						$isRedeemed = $objPromoCode->getIsRedeemed();
						if ($isRedeemed == 'Yes') {
							$response['msg'] = 'Sorry! The code you entered is already used. Please enter another Promo Code.';

						}else{
							$serviceLocation = $this->checkServiceLocation($objPromoCode->getServiceLocations(), $user->getUserServiceLocation());
							if ($serviceLocation) {
								
                                $userCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId, 'New');
								if ($userCartItems) {
									$discountAmount   = $objPromoCode->getAmount();
									$discountedAmount = 0;
									if($amount > $discountAmount ){
	                                    $discountedAmount = $amount - $discountAmount;
	                                }

	                                // $grossDiscount = $grossPayable = 0;
	                                foreach ($userCartItems as $key => $item) {
										$payableAmount      = $item->getPayableAmount();
										$discountPercentage = ($discountAmount * 100) / $payableAmount;
										if ($discountAmount >= $payableAmount && $item->getDiscountCodeApplied() == 0) {
											$discountPercentage = 100;
											$discountAmount     = $payableAmount;
											$finalPayableAmount = $payableAmount - $discountAmount;
											$finalcost          = $item->getFinalCost() - $discountAmount;
	                                		
	                                		$item->setPayableAmount($finalPayableAmount);
	                                		$item->setFinalCost($finalcost);
	                                		$item->setDiscountCodeRate($discountPercentage);
	                                		$item->setDiscountCodeAmount($discountAmount);
	                                		$item->setDiscountCodeApplied(7);
	                                		$em->persist($item);
                                    		$em->flush();

		                                	$response['result']['amount']           = $amount;
											$response['msg']                        = '';
											$response['result']['discountAmount']   = $discountAmount;
											$response['result']['percentage']       = number_format($discountPercentage, 2, '.', '');
											$response['result']['discountedAmount'] = number_format($discountedAmount, 2, '.', '');
											$response['status']                     = 'success';

											//save activity log 
											$activityLog['user']        = $user->getUsername();
											$activityLog['activity']    = 'In-App Prome Code used by user';
											$activityLog['description'] = "User ".$user->getUsername()." has used In-App promo code ".$code;
											$this->get('ActivityLog')->saveActivityLog($activityLog);
											$this->get('session')->set('InAppPromoCodeId',$objPromoCode->getId());

	                                	}else{
	                                		$response['msg'] = 'Invalid Promo Code. Please enter another Promo Code.';
	                                	}
	                                }
								}else{
									$response['msg'] = 'Cart is empty.';
								}
							}
						}
					}else{
						$response['msg'] = 'Sorry! The code you entered is already expired. Please enter another Promo Code.';
					}

				}

			}else{
				$response['msg'] = 'Please Enter Promo Code';
			}
		}else{
			$response['msg'] = 'Invalid Request';
		}

		$finalResponse = new Response(json_encode($response));
	    $finalResponse->headers->set('Content-Type', 'application/json');
        return $finalResponse;
	}

	public function removePromoCodeAction($userId, Request $request){
        $em               = $this->getDoctrine()->getManager();
        $user = $em->getRepository("DhiUserBundle:User")->findOneBy(array('id' => $userId, 'enabled' => 1, 'locked'=>0, 'isDeleted'=>0));
        $response = array();

        if ($user) {
			$sessionId   = $this->get('paymentProcess')->generateCartSessionId();
			$summaryData = $this->get('DashboardSummary')->getUserServiceSummary('', $user, true);
        	$condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user);
        	if($summaryData['isDiscountCodeApplied'] == 1){
            	$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy($condition);
            	foreach ($objServicePurchase as $purchase) {
                	$isApplied = $purchase->getDiscountCodeApplied();
	                if($isApplied == 7){
						$discountPercentage = $purchase->getDiscountCodeRate();
						$payableAmount      = $purchase->getPayableAmount();
						$discountPercentage = $purchase->getDiscountCodeRate();
						$discountCodeAmount =  $purchase->getDiscountCodeAmount();
						
						$finalPayableAmount = $payableAmount + $discountCodeAmount;
						$finalAmount        = $purchase->getFinalCost() + $discountCodeAmount;

                    	$purchase->setPayableAmount($finalPayableAmount);
	                    $purchase->setFinalCost($finalAmount);
	                    $purchase->setDiscountCodeRate(0);
	                    $purchase->setDiscountCodeAmount(0);
	                    $purchase->setDiscountCodeApplied(0);
	                    $em->persist($purchase);
	                    $em->flush();
	                    $response['status'] = "success";

	                }
            	}

            	if($response['status'] == 'success'){
	                $response['msg']    = "In-App Discount code has been successfully removed.";
	                $this->get('session')->getFlashBag()->add('success', 'In-App Discount code has been successfully removed.');
	            }else{
	                $response['status'] = "fail";
	                $response['msg']    = "In-App Discount code does not exists.";
	            }
        	}else{
	            $response['status'] = "fail";
	            $response['msg']    = "Discount code does not exists.";
	        }

		}else{
			$response['status'] = "fail";
            $response['msg']    = "Invalid Request.";
		}

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
		
    }

	private function checkServiceLocation($serviceLocation, $currentServiceLocation){
		if ($serviceLocation) {
			if ($serviceLocation->getId() == $currentServiceLocation->getId()) {
				return true;
			}
		}else{
			return false;
		}
	}

	private function checkDate($currentDate, $expireDate){
		if ($expireDate && $expireDate > $currentDate) {
			return true;
		}else{
			return false;
		}
	}

}