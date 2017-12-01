<?php

namespace Dhi\TvodBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;
use Dhi\ServiceBundle\Entity\ChaseCheckout;

class ChasePaymentController extends Controller
{
    public function doDirectProfilePaymentAction(Request $request, $userId){
		$em              = $this->getDoctrine()->getManager();
		$user            = $em->getRepository("DhiUserBundle:User")->find($userId);

		if ($user) {
			$sessionId         = $this->get('session')->get('sessionId');
			$orderNumber       = $this->get('session')->get('PurchaseOrderNumber');
			$purchaseOrderId   = $this->get('session')->get('PurchaseOrderId');
			$ipAddress         = $this->get('request')->server->get("REMOTE_ADDR");
			$purchaseOrder     = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);
			$purchaedCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
			
			
			$customerRefNum     = $user->getCustomerRefNum();

			if(!empty($purchaedCartItems)){
				if (!empty($customerRefNum)) {

					// chase
					$amount = $purchaseOrder->getTotalAmount();
					$chase = $this->get('chase');
					$request = array(
						"chaseTransType" => "",
						// "amount" => "",
						"order" => array(
							"orderNumber"    => $orderNumber,
							"billingAddress" => array(
								'postcode' => $user->getZip()
							)
						),
						"CcExpMonth"  => "",
						"CcExpYear"   => "",
						"lastTransId" => "",
						"status"      => "",
						"custRefNo"   => $customerRefNum,
						"CcCid"       => "",
						"CcNumber"    => "",
						"CcType"      => "",
						"CcOwner"     => ""
					);
					$response = $chase->authorize($request, $amount);
					if (!empty($response)) {
						$data = $response['data'];

						// Storing chase response
						$chasePaymentResponse = new ChaseCheckout();
						$chasePaymentResponse->setResponse(json_encode($response));
						$chasePaymentResponse->setUser($user);
						$chasePaymentResponse->setCustomerRefNo($customerRefNum);
                                                $chasePaymentResponse->setTransType('with-profile');

						if (!empty($data->transactionId) && $response['status'] == 'success') {

							$chasePaymentResponse->setChaseTransactionId($data->transactionId);
							$chasePaymentResponse->setChaseProcessStatus("Completed");
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

							//Activate Purchase packages
			                $paymentRefundStatus = $this->get('packageActivation')->activateVodService($user,'admin');

			                if(!empty($paymentRefundStatus['status'])){
								$refundInfoArr                = array();
								$refundInfoArr['LastTransId'] = $data->transactionId;
								$refundInfoArr["order"]       = array(
		                            'orderNumber' => $orderNumber
		                        );
			                    $refundDetail = $this->get('paymentProcess')->refundPayment($refundInfoArr, $user, true);
			                }

							$this->get('session')->set('sendPurchaseEmail',1);
							return $this->redirect($this->generateUrl('dhi_tvod_purchase_order_confirm',array('ord' => $orderNumber, 'userId'=> $userId)));

						} else {

							$chasePaymentResponse->setChaseProcessStatus("Failed");
							$em->persist($chasePaymentResponse);

							if ($purchaseOrder) {
								$purchaseOrder->setChase($chasePaymentResponse);
								$purchaseOrder->setPaymentStatus('Failed');
			                    $em->persist($purchaseOrder);
							}
							$em->flush();

							if (!empty($response['message'])) {
								$msg = $response['message'];
							}else{
								$msg = 'Internal server error. please try again.';
							}

							$response = array(
			                    'status'    => 'error',
			                    'message'   => urldecode($msg)
			                );
			                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
						}
					}else{
						$response = array(
		                    'status'    => 'error',
		                    'message'   => 'Internal server error. please try after some time.'
		                );
		                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
					}
				}else{
					$response = array(
	                    'status'    => 'error',
	                    'message'   => 'Chase profile does not exists. Please try again.'
	                );
	                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
				}

			}else{
	            $response = array(
					'status'  => 'error',
					'message' => 'Invalid Page Request'
	            );
	            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
	        }
	    }else{
	    	$response = array(
				'status'  => 'error',
				'message' => 'Invalid Page Request'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
	    }
    }
}