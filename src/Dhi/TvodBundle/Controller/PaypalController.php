<?php

namespace Dhi\TvodBundle\Controller;

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

class PaypalController extends Controller
{
    
    public function dodirectProcessAction(){
        
        $em               = $this->getDoctrine()->getManager(); //Init Entity Manager
        //$user             = $this->get('security.context')->getToken()->getUser();
        $sessionId        = $this->get('session')->get('sessionId'); //Get Session Id
        $billingAddressId 		  = $this->get('session')->get('BillingAddressId');
        $cardData         		  = $this->get('session')->get('cardInfo');
        $ipAddress        		  = $this->get('request')->server->get("REMOTE_ADDR");
        $purchaseOrderId  		  = $this->get('session')->get('PurchaseOrderId');
        $orderNumber       		  = $this->get('session')->get('PurchaseOrderNumber');
        $enableRecurringPayment   = $this->get('session')->get('EnableRecurringPayment');
        $recurringStatus = 3;
        $paypalRecurringProfileId = 0;
        
        $purchaseOrder     = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        $userName          = (string)$purchaseOrder->getUser();
        $user              = $em->getRepository("DhiUserBundle:User")->findOneBy(array("username" => $userName));
        $userId            = $user->getId();
        
        $billingAddress    = $em->getRepository('DhiServiceBundle:BillingAddress')->find($billingAddressId);
        $purchaedCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
        
        if($purchaedCartItems && $billingAddress && $cardData){
            
            $cardData['IPAddress'] = $ipAddress;
            
            //Set Configuration of paypal for call setExpressCheckOut Method
            $configPaypal = array(
                'PAYMENTACTION' => 'Sale',
                'METHOD'        => 'DoDirectPayment',
                "TVOD"          => array("userId" => $user, "isTvod"   => true)
            );
        
            $express = new ExpressCheckout($configPaypal,$this->container); //Express Checkout Object
            $paypalResponse = $express->DoDirectPayment($purchaedCartItems,$billingAddress,$cardData);
            
            if(strtoupper($paypalResponse["ACK"]) == "SUCCESS" || strtoupper($paypalResponse["ACK"]) == "SUCCESSWITHWARNING")
            {
                //Status update New => Completed
                $condition = array('user' => $user, 'paymentStatus' => 'New', 'purchaseOrder' => $purchaseOrder);
                $this->get('PaymentProcess')->updateServicePurchaseData($condition,array('paymentStatus' => 'Completed'));
                
                $transactionId = urldecode($paypalResponse["TRANSACTIONID"]);
                $amount        = urldecode($paypalResponse["AMT"]);
                $creditCardNo  = $this->get('PaymentProcess')->formatCreditCard($this->get('PaymentProcess')->maskCreditCard($cardData['CardNumber']));
                $expireDate    = $cardData['ExpMonth'].'-'.$cardData['ExpYear'];
                
                //Save paypal response in PaypalExpressCheckOutCustomer table
                $paypalCheckout = new PaypalCheckout();
                $paypalCheckout->setBuyerEmailAddress($user->getEmail());
                $paypalCheckout->setBuyerCountryCode($billingAddress->getCountry());
                $paypalCheckout->setPaypalTransactionId($transactionId);
                $paypalCheckout->setUser($user);
                $paypalCheckout->setPaypalProcessStatus('Authorization');
                $paypalCheckout->setTotalAmount($amount);
                $paypalCheckout->setCreditCardNo($creditCardNo);
                $paypalCheckout->setCcExpiredDate($expireDate);
                $paypalCheckout->setCreatedAt(new \DateTime(date('Y-m-d H:i:s')));
                
                $em->persist($paypalCheckout);
                $em->flush();
                $insertIdPaypalCheckOut = $paypalCheckout->getId();
                
                if($insertIdPaypalCheckOut){

                    $purchaseOrder->setPaypalCheckout($paypalCheckout);
                    $purchaseOrder->setPaymentStatus('Completed');                    
                    $em->persist($purchaseOrder);
                    $em->flush();
                    
                    //Add Activity Log
                    $activityLog = array();
                    $activityLog['user'] 	    = $user;
                    $activityLog['activity']    = 'Purchase Order';
                    $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment has been completed';
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    //Activity Log end here
                }
                
                //Purchase credit
                //$creditPaymentRefundStatus = $this->get('packageActivation')->addCreditInUserAccount($user);

                //Activate Purchase packages
                $paymentRefundStatus = $this->get('packageActivation')->activateVodService($user,'admin');

                if($paymentRefundStatus['status']){
                    $refundDetail = $this->get('paymentProcess')->refundPayment(array(), $user, true);
                    
                    if($paymentRefundStatus['reason'] != ''){
                        $this->get('session')->set('paymentMessage', $paymentRefundStatus['reason']);
                    }else{
                        $this->get('session')->remove('paymentMessage');
                    }

                    if($refundDetail['refundType'] == 'Full') {
                        
                        $enableRecurringPayment = false;
                    }                    
                }
                
            	//Create Recurring payment profile                
                $recurringAmount = $em->getRepository('DhiServiceBundle:ServicePurchase')->getRecurringPaymentAmount($purchaseOrderId);
                
                $this->get('session')->set('sendPurchaseEmail',1);
                return $this->redirect($this->generateUrl('dhi_tvod_purchase_order_confirm',array('ord' => $orderNumber, 'userId'=> $userId)));

            }else if(strtoupper($paypalResponse["ACK"]) == "FAILURE"){
                
                $errorCode    = urldecode($paypalResponse["L_ERRORCODE0"]); //Paypal Error Code
                $errorMessage = urldecode($paypalResponse["L_LONGMESSAGE0"]); //Paypal Error Message
                
                $this->get('session')->remove('cardInfo');
                $response = array(
                    'status'    => 'error',
                    'message'   => urldecode($errorMessage)
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            }else{
                
                $this->get('session')->remove('cardInfo');
                $response = array(
                    'status'    => 'error',
                    'message'   => 'Payment server not responding. please try after some time.'
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            }                                    
        }else{
            
            $response = array(
                'status'    => 'error',
                'message'   => 'Invalid Page Request'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }          
    }
    
    /**
     * function paypalExpressCheckoutAction()
     * This function is used for paypal setExpressCheckOut Process
     */
    public function paypalExpressCheckoutAction() {

        $request    = $this->getRequest();
        $isSecure   = $request->isSecure() ? 'https://' : 'http://';
        $em         = $this->getDoctrine()->getManager(); //Init Entity Manager
        $userId     = $request->get('userId');
        if($userId){
            $user       = $em->getRepository("DhiUserBundle:User")->find($userId);
        }
        
        $sessionId  = $this->get('session')->get('sessionId'); //Get Session Id        
        $userId     = $user->getId(); // Get Current Login User Id
        $purchaseOrderId  = $this->get('session')->get('PurchaseOrderId');

        $itemArr    = array(); //Init item array
        $siteUrl    = $isSecure.$this->getRequest()->getHost().$this->getRequest()->getBaseUrl();

        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        
        //If service purchase data not found throw to 404
        if (!$purchaseOrder) {
        
            $response = array(
                'status'    => 'error',
                'message'   => 'Invalid Page Request'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }
        
        //Retrieve user's purchased items
        $purchaedCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
        
        if($purchaedCartItems){
            
            //Set Configuration of paypal for call setExpressCheckOut Method
            $configPaypal = array(
                    'RETURNURL'     => $siteUrl.$this->container->getParameter('tvod_paypal_confirm_url'), //Set Success Return Url
                    'CANCELURL'     => $siteUrl.$this->container->getParameter('tvod_paypal_cancel_url'), //Set Cancel Return Url
                    'PAYMENTACTION' => 'Sale', //Express Checkout Payment Action
                    "TVOD"          => array("userId" => $user, "isTvod"   => true)
            );
            
            if ($this->get('session')->get('EnableRecurringPayment')) {
            	
            	$configPaypal['L_BILLINGTYPE0'] = 'RecurringPayments';
            	$configPaypal['L_BILLINGAGREEMENTDESCRIPTION0'] = 'ExchangeVUE recurring bill agreement.';
            }
            
            $express = new ExpressCheckout($configPaypal,$this->container); //Paypal Express Checkout object
            $httpParsedResponseAr = $express->setExpressCheckout($purchaedCartItems); //Call setExpressCheckOut Method of Paypal
            
            if($httpParsedResponseAr){
                
                $this->get('session')->remove('cardInfo');
                $response = array(
                    'status'    => 'error',
                    'message'   => urldecode($httpParsedResponseAr["L_LONGMESSAGE0"])
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            }
        }else{
            
            $response = array(
                'status'    => 'error',
                'message'   => 'Invalid Page Request'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }       
    }

    /**
     * function paymentConfirmAction()
     * This function will call after success from paypal setExpressCheckOut
     */
    public function paymentConfirmAction()
    {
        
        $em                = $this->getDoctrine()->getManager();
        $sessionId         = $this->get('session')->get('sessionId');
        $orderNumber       = $this->get('session')->get('PurchaseOrderNumber');
        $purchaseOrderId   = $this->get('session')->get('PurchaseOrderId');
        // $enableRecurringPayment   = $this->get('session')->get('EnableRecurringPayment');
        $recurringStatus = 3;
        $paypalRecurringProfileId = 0;

        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);
        
        if($purchaseOrder){


            $user     = $purchaseOrder->getUser();
            $userId = $user->getId();

            //Complete DoExpressCheckout Process
            $response = $this->completeDoExpressCheckoutPayment();
            if($response['status'] == 'success'){
            	$recurringProfileStatus = '';
            	$recurringProfileId = '';

                //Activate Purchase packages
                $paymentRefundStatus = $this->get('packageActivation')->activateVodService($user,'admin');

                if($paymentRefundStatus['status']){
                    $refundDetail = $this->get('paymentProcess')->refundPayment(array(), $user, true);

                    if($paymentRefundStatus['reason'] != ''){
                        $this->get('session')->set('paymentMessage', $paymentRefundStatus['reason']);
                    }

                    if($refundDetail['refundType'] == 'Full') {
                    	
                    	$enableRecurringPayment = false;
                    }                    
                }

                $this->get('session')->set('sendPurchaseEmail',1);
                return $this->redirect($this->generateUrl('dhi_tvod_purchase_order_confirm',array('ord' => $orderNumber, 'userId' => $userId)));

            }else if($response['status'] == 'failed'){
                $response = array(
                    'status'    => 'error',
                    'message'   => $response['message'],
                    'type'      => 'cancel'
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));

            }else{
                
                $response = array(
                    'status'    => 'error',
                    'message'   => 'Something went worng in paypal checkout. please try again.',
                    'type'      => 'cancel'
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            }            
        }else{
            //Redirect To Recharge Failed
            $response = array(
                'status'    => 'error',
                'message'   => 'Something went worng in paypal checkout. please try again.',
                'type'      => 'cancel'
            );
            return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
        }               
    }
    
    public function completeDoExpressCheckoutPayment(){
    
        $em              = $this->getDoctrine()->getManager(); // Init Entity Manager
        $sessionId       = $this->get('session')->get('sessionId');     // Get Session Id
        $paypalExpressId = $this->get('session')->get('paypal[insertIdPaypalExpress]');
        //$user            = $this->get('security.context')->getToken()->getUser();
        //$userId          = $user->getId();          // Get Login User Id
        $token           = $this->getRequest()->get('token');  // Get Paypal TokenID
        $PayerID         = $this->getRequest()->get('PayerID');// Get Paypal PayerId
        $purchaseOrderId = $this->get('session')->get('PurchaseOrderId');
        
        $response = array();
        $response['status']   = '';
        $response['message']  = '';
        $response['returnTo'] = '';
        $response['token'] 	  = $token;
        $response['payerID']  = $PayerID;
        
        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($purchaseOrderId);
    
        //If Service Purchase data not found throw 404
        if (!$purchaseOrder) {
    
            throw $this->createNotFoundException('No recharge data found');
        }
        
        $userId = $purchaseOrder->getUser();
        $user   = $em->getRepository("DhiUserBundle:User")->find($userId);

        if($token && $PayerID){ //Check Paypal token and payerId
            
            //Update Paypal token in purchase order
            $purchaseOrder->setPaypalToken($token);
            $em->persist($purchaseOrder);
            $em->flush();
    
            $purchaedCartItems = $em->getRepository('DhiServiceBundle:ServicePurchase')->getPurchaseCartItem($user,'New',$purchaseOrder);
    
            if($purchaedCartItems){

                //Set Configuration of paypal for call setExpressCheckOut Method
                $configPaypal = array(
                        'PAYMENTACTION' => 'Sale',
                        'METHOD'        => 'DoExpressCheckoutPayment',
                        'TOKEN'         => $token,
                        'PAYERID'       => $PayerID,
                        "TVOD"          => array("userId" => $user, "isTvod"   => true)
                );

                $express = new ExpressCheckout($configPaypal,$this->container); //Express Checkout Object
                $httpParsedResponseAr = $express->DoExpressCheckoutPayment($purchaedCartItems); //Call DoExpressCheckout menthod

                if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
                {
                    if($purchaseOrder->getServicePurchases()){
                        
                        foreach ($purchaseOrder->getServicePurchases() as $servicePurchase){
                            
                            $servicePurchase->setPaymentStatus('Completed');
                            $em->persist($servicePurchase);
                            $em->flush();                                                        
                        }
                        
                        //Call Paypal GetExpressCheckOut Method
                        $express->setMethod('GetExpressCheckoutDetails');
                        $getExpressCheckOutDetail = $express->DoExpressCheckoutPayment($purchaedCartItems);
                        
                        //PayPal Responses Data
                        $transactionId  = $httpParsedResponseAr["PAYMENTINFO_0_TRANSACTIONID"];
                        $amount         = urldecode($httpParsedResponseAr["PAYMENTINFO_0_AMT"]);
                        $email          = urldecode($getExpressCheckOutDetail["EMAIL"]);
                        $payerStatus    = urldecode($getExpressCheckOutDetail["PAYERSTATUS"]);
                        $countryCode    = urldecode($getExpressCheckOutDetail["COUNTRYCODE"]);
                        $paymentACK     = urldecode($httpParsedResponseAr["PAYMENTINFO_0_ACK"]);
                        
                        //Save paypal response in PaypalExpressCheckOutCustomer table
                        $paypalCheckout = new PaypalCheckout();
                        $paypalCheckout->setBuyerEmailAddress($email);
                        $paypalCheckout->setPaypalPayerId($PayerID);
                        $paypalCheckout->setPaypalPayerStatus($payerStatus);
                        $paypalCheckout->setBuyerCountryCode($countryCode);
                        $paypalCheckout->setPaypalTransactionId($transactionId);
                        $paypalCheckout->setUser($user);
                        $paypalCheckout->setPaypalProcessStatus('DoExpressCheckOut');
                        $paypalCheckout->setTotalAmount($amount);
                        
                        $mysqldate = new \DateTime(date('Y-m-d H:i:s'));
                        $paypalCheckout->setCreatedAt($mysqldate);
                        
                        $em->persist($paypalCheckout);
                        $em->flush();
                        $insertIdPaypalExpress = $paypalCheckout->getId();

                        if($insertIdPaypalExpress){
                            
                            $purchaseOrder->setPaypalCheckout($paypalCheckout);
                            $purchaseOrder->setPaymentStatus('Completed');
                            $em->persist($purchaseOrder);
                            $em->flush();
                            
                            //Add Activity Log
                            $activityLog = array();
                            $activityLog['user'] 	    = $user;
                            $activityLog['activity']    = 'Purchase Order';
                            $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$purchaseOrder->getOrderNumber().' payment has been completed';
                            $this->get('ActivityLog')->saveActivityLog($activityLog);
                            //Activity Log end here
                            
                            $response['status'] = 'success';                            
                        }else{
                            
                            $response['status'] = 'failed';
                            $response['message'] = 'Something went worng in paypal checkout. please try again.';
                            $response['returnTo'] = 'purchaseverification';
                        }
                    }                                        
                }else{
                    
                    $response['status'] = 'failed';
                    $response['message'] = urldecode($httpParsedResponseAr["L_LONGMESSAGE0"]);
                    $response['returnTo'] = 'purchaseverification';                                        
                }
            }else{
            
                $response['status'] = 'failed';
                $response['message'] = 'Purchase service not found in cart.';
                $response['returnTo'] = 'account';
                
            }            
        }else{
            
            $response['status'] = 'failed';
            $response['message'] = 'Paypal service not available. please try another payment options';
            $response['returnTo'] = 'purchaseverification';
        }         

        return $response;
    }        
}
