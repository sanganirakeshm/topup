<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Dhi\ServiceBundle\Model\PaymentDetails;
use Dhi\ServiceBundle\Model\ExpressCheckout;
use Dhi\ServiceBundle\Entity\PaypalCheckout;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;

class MilstarPaymentController extends Controller
{
    
    public function milstarProcessAction(){
        
        $em                  = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user                = $this->get('security.context')->getToken()->getUser();
        $sessionId           = $this->get('session')->get('sessionId'); //Get Session Id
        $milstarInfo         = $this->get('session')->get('milstarInfo');
        $userServiceLocation = $this->get('UserLocationWiseService')->getUserLocationService();
        $purchaseOrderId     = $this->get('session')->get('PurchaseOrderId');
        $orderNumber         = $this->get('session')->get('PurchaseOrderNumber');

        
        if($milstarInfo && $userServiceLocation['IsMilstarEnabled'] == 1){
            
            $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);

            if($purchaseOrder){
                
                $milsatParams['requestId']        = $orderNumber;
                $milsatParams['creditCardNumber'] = $milstarInfo['MCardNumber'];
                $milsatParams['amount']           = $purchaseOrder->getTotalAmount();
                //$milsatParams['amount']           = 1;
                //$milsatParams['processingFacnbr'] = $userServiceLocation['FacNumber'];
                // $milsatParams['processingFacnbr'] = $this->container->getParameter('processingFacnbr');
                $milsatParams['zipCode']          = $milstarInfo['MZipcode'];
                $milsatParams['cid']              = $user->getCid();
                
                $response = $this->get('milstar')->processMilstarApproval($milsatParams);
                
                if ($response){
                    
                    //Updtae payment status
                    $purchaseOrder->setPaymentStatus('Completed');
                    $em->persist($purchaseOrder);
                    $em->flush();
                    
                    //Add Activity Log
                    $activityLog = array();
                    $activityLog['user'] 	    = $user;
                    $activityLog['activity']    = 'Purchase Order';
                    $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment has been completed.';
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    //Activity Log end here
                    
                    //Status update New => Completed
                    $condition = array('user' => $user, 'paymentStatus' => 'New', 'sessionId' => $sessionId);
                    $this->get('PaymentProcess')->updateServicePurchaseData($condition,array('paymentStatus' => 'Completed'));
                    
                    $milsatParams['authCode']     = $response['authCode'];
                    $milsatParams['authTicket']   = $response['authTicket'];                    
                    
                    //Purchase credit
                    $creditPaymentRefundStatus = $this->get('packageActivation')->addCreditInUserAccount($user);
                    
                    //Activate Purchase packages
                    $paymentRefundStatus = $this->get('packageActivation')->activateServicePack($user);
                    
                    if($paymentRefundStatus || $creditPaymentRefundStatus){
                        
                        $isPaymentRefund = $this->get('paymentProcess')->refundPayment($milsatParams);
                    }
                    $this->get('session')->set('sendPurchaseEmail',1);
                    return $this->redirect($this->generateUrl('dhi_service_purchase_order_confirm',array('ord' => $orderNumber)));
                    
                }else{
                    
                    $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);
                    
                    $errorMsg = 'Milstar Payment is unavailable at the moment please try again later.';
                    
                    if($purchaseOrder){
                        
                        if($purchaseOrder->getPaymentMethod()){
                            
                            if($purchaseOrder->getPaymentMethod()->getCode() == 'Milstar'){

                                if($purchaseOrder->getMilstar()){
                                    if($purchaseOrder->getMilstar()->getFailCode() != 'A' && $purchaseOrder->getMilstar()->getFailMessage()){
                                        
                                        //$errorMsg = $purchaseOrder->getMilstar()->getFailMessage().' please try with another payment option.';
                                    }                                     
                                }
                            }
                        }
                    }
                    
                    $this->get('session')->getFlashBag()->add('failure', $errorMsg);
                    return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                } 
            }else{
                
                throw $this->createNotFoundException('Invalid Page Request');
            }           
        }else{
            
            throw $this->createNotFoundException('Invalid Page Request');
        }        
    }
}
