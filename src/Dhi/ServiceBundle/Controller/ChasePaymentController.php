<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;
use Dhi\ServiceBundle\Entity\ChaseCheckout;
use Dhi\UserBundle\Entity\UserChaseInfo;

class ChasePaymentController extends Controller
{
    public function doDirectProfilePaymentAction(Request $request){
		$em              = $this->getDoctrine()->getManager();
		$user            = $this->get('security.context')->getToken()->getUser();
		$sessionId       = $this->get('session')->get('sessionId');
		$orderNumber     = $this->get('session')->get('PurchaseOrderNumber');
		$purchaseOrderId = $this->get('session')->get('PurchaseOrderId');
		$ipAddress       = $this->get('request')->server->get("REMOTE_ADDR");
		$cardData        = $this->get('session')->get('cardInfo');
		$chaseWPInfo     = $this->get('session')->get('ChaseWPInfo');
		$purchaseOrder     = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);
		$purchaedCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
		$chasePaymentType = $this->get('session')->get('chasePaymentType');

		if($purchaedCartItems && !empty($chasePaymentType) && ((!empty($chaseWPInfo) && !empty($cardData) && $chasePaymentType != "with-profile") || ($chasePaymentType == "with-profile")) ){
                    $chaseMerchantData    = $this->get('DashboardSummary')->getUserLocationWiseChaseMID($user);
			if ($chasePaymentType == "with-profile") {
				$customerRefNum     = $chaseMerchantData['customerRefNum'];
				if (!empty($customerRefNum)) {
					$request = array(
						"order" => array(
							"orderNumber"    => $orderNumber,
							"billingAddress" => array(
								'avsAddress1' => "",
								'avsAddress2' => "",
								'avsCity' => "",
								'avsState' => "",
								'avsCountryCode' => "",
								'postcode' => ""
							)
						),
						"CcExpMonth"  => "",
						"CcExpYear"   => "",
						"custRefNo"   => $customerRefNum,
						"CcCid"       => "",
						"CcNumber"    => "",
						"CcType"      => "",
						"CcOwner"     => ""
					);
				}else{
					$this->get('session')->getFlashBag()->add('notice', 'Chase profile does not exists. Please try again.');
	            	return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));	
				}
			}else if(in_array($chasePaymentType, array("new-profile", "without-profile"))) {
				$request     = array(
					"order"          => array(
						"orderNumber"    => $orderNumber,
						"billingAddress" => array(
							'avsAddress1' => $chaseWPInfo['Address'],
							'avsAddress2' => (!empty($chaseWPInfo['Address2']) ? $chaseWPInfo['Address2'] : ''), 
							'avsCity' => $chaseWPInfo['City'],
							'avsState' => $chaseWPInfo['State'],
							'avsCountryCode' => (in_array($chaseWPInfo['Country'], array('US','CA', 'GB', 'UK')) ? $chaseWPInfo['Country'] : ''),
							'postcode' => $chaseWPInfo['Zipcode']
						)
					),
					"CcExpMonth"       => $cardData['ExpMonth'],
					"CcExpYear"        => $cardData['ExpYear'],
					"custRefNo"        => null,
					"CcCid"            => $cardData['Cvv'],
					"CcNumber"         => $cardData['CardNumber'],
					"CcType"           => $cardData['CardType'],
					"CcOwner"          => $chaseWPInfo['Firstname'].' '.$chaseWPInfo['Lastname'],
					'chasePaymentType' => $chasePaymentType
				);
			}

			$amount   = $purchaseOrder->getTotalAmount();
			$chase    = $this->get('chase');
			$response = $chase->authorize($request, $amount, $chaseMerchantData);

			if (!empty($response)) {
				$data = $response['data'];

				// Storing chase response
				$chasePaymentResponse = new ChaseCheckout();
				$chasePaymentResponse->setResponse(json_encode($response));
				$chasePaymentResponse->setUser($user);
				$chasePaymentResponse->setTransType($chasePaymentType);
				
				$chasePaymentResponse->setOrderNumber($orderNumber);

				$transaCode = '';
				if (!empty($data->transactionId)) {
					$transaCode = $data->transactionId;
				}else if(isset($data->authResponse->txRefNum) && !empty($data->authResponse->txRefNum)){
					$transaCode = $data->authResponse->txRefNum;
				}

				$chasePaymentResponse->setChaseTransactionId($transaCode);
				
				if (!empty($data->transactionId) && $response['status'] == 'success') {
					$cusRefNo = $this->getProfile($chasePaymentType, $data->authResponse);
					
					$chasePaymentResponse->setChaseProcessStatus("Completed");
					$chasePaymentResponse->setCustomerRefNo($cusRefNo);
					
	                if(empty($cusRefNo) && $chasePaymentType == 'with-profile'){
	                    $chasePaymentResponse->setCustomerRefNo($chaseMerchantData['customerRefNum']);
	                }

					if (!empty($cusRefNo)) {
                        if(isset($chaseMerchantData['isProfileExist']) && $chaseMerchantData['isProfileExist'] == 0){
                            $objChaseMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->find($chaseMerchantData['chaseMerchantPID']);
                            $objUserChaseInfo = new UserChaseInfo();
                            $objUserChaseInfo->setUser($user);
                            $objUserChaseInfo->setMerchantId($objChaseMerchantId);
                            $objUserChaseInfo->setCustomerRefNum($cusRefNo);
                            $em->persist($objUserChaseInfo);
                            $em->flush();
                        }else{
                            $objUserChaseInfo = $em->getRepository('DhiUserBundle:UserChaseInfo')->findOneBy(array('user' => $user, 'merchantId' => $chaseMerchantData['chaseMerchantPID']));
                            if($objUserChaseInfo){
                                $objUserChaseInfo->setCustomerRefNum($cusRefNo);
                                $em->persist($objUserChaseInfo);
                                $em->flush();
                            }
                        }
					}
					$em->persist($chasePaymentResponse);

					$em->flush();
					$chaseCheckoutId = $chasePaymentResponse->getId();

					$purchaseOrder->setChase($chasePaymentResponse);
					$purchaseOrder->setPaymentStatus('Completed');
                    $em->persist($purchaseOrder);
					$em->flush();

                    if($purchaseOrder->getServicePurchases()){
                    	foreach ($purchaseOrder->getServicePurchases() as $servicePurchase){
                            $servicePurchase->setPaymentStatus('Completed');
                            $em->persist($servicePurchase);
                            $em->flush();
                        }
                    }

					$activityLog                = array();
					$activityLog['user']        = $user;
					$activityLog['activity']    = 'Purchase Order';
					$activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment has been completed';
					$this->get('ActivityLog')->saveActivityLog($activityLog);

					//Purchase credit
	                $creditPaymentRefundStatus = $this->get('packageActivation')->addCreditInUserAccount($user);

	                //Activate Purchase packages
	                $paymentRefundStatus = $this->get('packageActivation')->activateServicePack($user);

	                if($paymentRefundStatus || $creditPaymentRefundStatus){
						$refundInfoArr                = array();
						$refundInfoArr['LastTransId'] = $data->transactionId;
						$refundInfoArr["order"]       = array(
                            'orderNumber' => $orderNumber
                        );
	                    $refundDetail = $this->get('paymentProcess')->refundPayment($refundInfoArr);
	                }

					$this->get('session')->set('sendPurchaseEmail',1);
                	return $this->redirect($this->generateUrl('dhi_service_purchase_order_confirm',array('ord' => $orderNumber)));	

				} else {

					$chasePaymentResponse->setChaseProcessStatus("Failed");
					$em->persist($chasePaymentResponse);

					if ($purchaseOrder) {
						$purchaseOrder->setChase($chasePaymentResponse);
						$purchaseOrder->setPaymentStatus('Failed');
	                    $em->persist($purchaseOrder);
					}
					$em->flush();

					if (!empty($response['titleErrorMsg'])) {
						$message = $response['titleErrorMsg'];
					}

					$this->get('session')->getFlashBag()->add('notice', $message);
            		return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));	
				}
			}else{
				$this->get('session')->getFlashBag()->add('notice', 'Internal server error. please try after some time.');
            	return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));	
			}
			
		}else{
            throw $this->createNotFoundException('Invalid Page Request');
        }
    }

    private function getProfile($chasePaymentType, $data){
		if($chasePaymentType == "new-profile" && isset($data->profileProcStatus)) {
			$profileProcStatus = $data->profileProcStatus;
			$customerRefNum    = $data->customerRefNum;
			if ($profileProcStatus == 0 && !empty($customerRefNum)) {
				return $customerRefNum;
			}
		}
		return null;
	}
}