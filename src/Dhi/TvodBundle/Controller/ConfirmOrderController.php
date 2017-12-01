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
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Symfony\Component\HttpFoundation\Response;
use Dhi\ServiceBundle\Model\ExpressCheckout;
use Dhi\UserBundle\Entity\User;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Form\Type\BillingAddressFormType;
use Dhi\ServiceBundle\Entity\ChaseCheckout;

class ConfirmOrderController extends Controller {

    public $paymentStep    = array('1', '2', '3');
    public $paymentOptions = array();
    public function getPatmentMethod(){
        $em         = $this->getDoctrine()->getManager();
        $methods = array('chase', 'PayPal', 'CreditCard', 'Milstar');
        $objPaymentOptions = $em->getRepository("DhiServiceBundle:PaymentMethod")->getPaymentMethodByCode($methods);
        foreach ($objPaymentOptions as $objPaymentOption) {
            $this->paymentOptions[$objPaymentOption->getId()] = strtolower($objPaymentOption->getCode());
        }
    }


    public function confirmPaymentDetailAction(Request $request, $step) {

        $jsonResponse = array();
        $jsonResponse['status']           = '';
        $jsonResponse['message']          = '';
        
        $jsonResponse['stepOneResponse']    = '';
        $jsonResponse['stepTwoResponse']    = '';
        $jsonResponse['stepThreeResponse']  = '';
        
        $jsonResponse['requestStep']        = '';
        
        if ($request->isXmlHttpRequest()) {
            $requestParams = $request->request->all();
            if(!empty($requestParams)) {

                if($step == 1) {
                     
                    $stepOneResponse = $this->paymentStepOne($request);
                    
                    $jsonResponse['stepOneResponse'] = $stepOneResponse;
                    $jsonResponse['requestStep'] = 1;
                }
                
                if($step == 2) {
                     
                    $stepOneResponse = $this->paymentStepOne($request);
                    $stepTwoResponse = $this->paymentStepTwo($request);
                    
                    if($stepOneResponse['status'] != 'success'){
                        
                        $jsonResponse['stepOneResponse'] = $stepOneResponse;
                        $jsonResponse['requestStep']     = 1;
                    }else{
                                                
                        $jsonResponse['stepTwoResponse'] = $stepTwoResponse;
                        $jsonResponse['requestStep']     = 2;
                    }
                }
                
                if($step == 3) {
                     
                    $stepOneResponse   = $this->paymentStepOne($request);
                    $stepTwoResponse   = $this->paymentStepTwo($request);
                    $stepThreeResponse = $this->paymentStepThree($request);
                    
                    if($stepOneResponse['status'] != 'success'){
                        
                        $jsonResponse['stepOneResponse'] = $stepOneResponse;
                        $jsonResponse['requestStep']     = 1;
                        
                    }/*else if($stepTwoResponse['status'] != 'success'){
                        
                        $jsonResponse['stepTwoResponse'] = $stepTwoResponse;
                        $jsonResponse['requestStep']     = 2;
                    }*/else{
                                                
                        $jsonResponse['stepThreeResponse'] = $stepThreeResponse;
                        $jsonResponse['requestStep']       = 3;
                    }
                }                 
            }
            else {

                $jsonResponse['status']  = 'failed';
                $jsonResponse['message'] = 'Invalid request.';
            }
        }else{

            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }

        echo json_encode($jsonResponse);
        exit;
    }
    
    public function paymentStepOne($request) {
    
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
        $jsonResponse['status'] = 'success';
    
        return $jsonResponse;
    }
    
    public function paymentStepTwo($request) {
    
        $em          = $this->getDoctrine()->getManager(); //Init Entity Manager
        //$user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id
    
        $requestParams = $request->request->all();
        $errorMsgArr = $this->paymentOptionAtrributesError();
        
        $jsonResponse = array();
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
    
        if($requestParams){
    
            if(isset($requestParams['Billing']) && !empty($requestParams['Billing'])){
                
                $billingData  = $requestParams['Billing'];

                foreach ($billingData as $key => $val){

                    if(array_key_exists($key,$errorMsgArr)){

                        if($val == ''){

                            $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                        }
                    }
                }

                if($jsonResponse['errorMessages']){

                    $jsonResponse['status']   = 'error';
                }else{
                    
                    $jsonResponse['status']  = 'success';
                    $this->get('session')->set('billingInfo',$billingData);
                }
            }else{
                $jsonResponse['status']  = 'failed';
                $jsonResponse['message'] = 'Please check billing information data.';
            }            
        }else{
    
            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }
    
        return $jsonResponse;
    }
    
    public function paymentStepThree(Request $request) {
    
        $em          = $this->getDoctrine()->getManager(); //Init Entity Manager
        //$user        = $this->get('security.context')->getToken()->getUser();
        $this->getPatmentMethod();
        $userId     = $request->get('userId');
        if($userId){
            $user       = $em->getRepository("DhiUserBundle:User")->find($userId);
        }

        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id
    
        $requestParams = $request->request->all();
        $errorMsgArr = $this->paymentOptionAtrributesError();
        
        $creditCardData = '';
        $milstarData = '';
        $recurringPayment = '';
        
        $jsonResponse = array();
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
    
        if($requestParams && $user){
    
            $processStep     = $requestParams['processStep'];
            $recurringPayment = (!empty($requestParams['recurringPayment']))?$requestParams['recurringPayment']:0;
            
            if ($requestParams['paymentBy'] == 'cc') {
                $condPaymentBy = 'CreditCard';
            }else{
                $condPaymentBy = $requestParams['paymentBy'];
            }

            if(in_array(strtolower($condPaymentBy),$this->paymentOptions)){

                if($requestParams['paymentBy'] == 'cc'){

                    if(isset($requestParams['cc']) && !empty($requestParams['cc'])){
                        
                        $creditCardData  = $requestParams['cc'];
                        
                        foreach ($creditCardData as $key => $val){
    
                            if(array_key_exists($key,$errorMsgArr)){
    
                                if($val == ''){
    
                                    $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                                }
                            }
                        }
                    }

                    if(isset($requestParams['Billing']) && !empty($requestParams['Billing'])){
                        
                        $billingData  = $requestParams['Billing'];
                        
                        foreach ($billingData as $key => $val){
                        
                            if(array_key_exists($key,$errorMsgArr)){
                        
                                if($val == ''){
                        
                                    $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                                }
                            }
                        }
                    }
                }

                if($requestParams['paymentBy'] == 'milstar'){
                    
                    if(isset($requestParams['milstar']) && !empty($requestParams['milstar'])){
                        
                        $milstarData     = $requestParams['milstar'];
                        foreach ($milstarData as $key => $val){
    
                            if(array_key_exists($key,$errorMsgArr)){
    
                                if($val == ''){
    
                                    $jsonResponse['errorMessages'][$key] = $errorMsgArr[$key];
                                }
                            }
                        }
                    }                        
                }

                if($jsonResponse['errorMessages']){

                    $jsonResponse['status']   = 'error';
                }else{

                    $this->get('session')->set('milstarInfo',$milstarData);
                    $this->get('session')->set('cardInfo',$creditCardData);
                    $this->get('session')->set('paymentBy',$requestParams['paymentBy']);
                    if ($recurringPayment == 1) {
                    	
                    	$this->get('session')->set('EnableRecurringPayment',$recurringPayment);
                    } else {
                    	
                    	$this->get('session')->remove('EnableRecurringPayment');
                    }
                    
                    $jsonResponse['paymentBy'] = $requestParams['paymentBy'];
                    $jsonResponse['status']  = 'success';
                }
            }else{

                $jsonResponse['status']  = 'failed';
                $jsonResponse['message'] = 'Please select valid payment request.';
            }
            
        }else{
            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }
    
        return $jsonResponse;
    }
    
    public function doPaymentProcessAction(Request $request){
        $em                    = $this->getDoctrine()->getManager(); //Init Entity Manager

        $userId      = $request->get('userId');
        $opPaymentBy = $request->get('paymentBy');
        if($userId){
            $user       = $em->getRepository("DhiUserBundle:User")->find($userId);
        }
        $sessionId             = $this->get('session')->get('sessionId'); //Get Session Id        
        $orderNumber           = $this->get('session')->get('orderNumber');
        $summaryData           = $this->get('DashboardSummary')->getUserServiceSummary('', $user, true);
        $billingData           = $this->get('session')->get('billingInfo');
        $isDeersAuthenticated  = $this->get('DeersAuthentication')->checkDeersAuthenticated('', $user->getId());
        $isDeersPackageExistInCart   = $this->get('paymentProcess')->checkDeersPackageExistInCart($summaryData);
        $ipAddress             = $this->get('session')->get('ipAddress');
        $this->getPatmentMethod();
        $insertIdBillingAddress = '';
        $paymentBy = $this->get('session')->get('paymentBy');
        if (empty($paymentBy)) {
            $paymentBy = $opPaymentBy;
        }

        $isSuccess = false;
        $paymentUrl = ''; 

        if ($paymentBy == 'cc') {
            $condPaymentBy = 'CreditCard';
        }else{
            $condPaymentBy = $paymentBy;
        }

        if(in_array(strtolower($condPaymentBy),$this->paymentOptions)){

            if($paymentBy == 'paypal'){

                $paymentUrl = $this->redirect($this->generateUrl('dhi_tvod_paymentby_expresscheckout', array('userId'=>$user->getId())));
            }elseif($paymentBy == 'cc'){

                if(!$billingData){
                    $response = array(
                        'status'    => 'error',
                        'message'   => 'Billing address information not found.'
                    );
                    return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
                }
                $paymentUrl = $this->redirect($this->generateUrl('dhi_tvod_paymentby_dodirect'));

            }elseif($paymentBy == 'milstar'){

				if($user->getCid() && $this->get('session')->get('isDearsAuthenticatedForMilstar') == 1)	{
            		$this->get('session')->remove('isDearsAuthenticatedForMilstar');
            		$paymentUrl = $this->redirect($this->generateUrl('dhi_paymentby_milstar'));
            	}	else	{
                    $response = array(
                        'status'    => 'error',
                        'message'   => 'Milstar customer Id not found.'
                    );
                    return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            	}

            }elseif(strtolower($paymentBy) == 'chase'){
                $chaseReq    = $request->get('chaseReq');
                if ($isDeersPackageExistInCart && $isDeersAuthenticated == 2) {
                    $response = array(
                        'status'    => 'error',
                        'message'   => 'DEERS authentication is required for purchase package.'
                    );
                    return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
                }
                if (!empty($chaseReq['name'])) {
                    $paymentUrl = $this->redirect($this->generateUrl('dhi_tvod_purchase_order_confirm',array('ord' => $orderNumber, 'userId' => $userId)));
                }else{
                    $paymentUrl = $this->redirect($this->generateUrl('dhi_tvod_chase_order_confirm',array('userId' => $user->getId())));
                }
            }else{
                $response = array(
                    'status'    => 'error',
                    'message'   => 'Invalid Page Request'
                );
                return $this->redirect($this->generateUrl('dhi_tvod_return_url', array('params' => $response)));
            }

            if($summaryData['CartAvailable']){
                
                $objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->find(array_search(strtolower($condPaymentBy), $this->paymentOptions));
                
                //Save paypal response in PaypalExpressCheckOutCustomer table
                $objPurchaseOrder = new PurchaseOrder();
                $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                $objPurchaseOrder->setSessionId($sessionId);
                $objPurchaseOrder->setOrderNumber($orderNumber);
                $objPurchaseOrder->setUser($user);
                $objPurchaseOrder->setPaymentBy('User');
                $objPurchaseOrder->setPaymentByUser($user);
                $objPurchaseOrder->setTotalAmount($summaryData['TotalCartAmount']);
                $objPurchaseOrder->setIpAddress($ipAddress);
                
                $em->persist($objPurchaseOrder);
                $em->flush();
                $insertIdPurchaseOrder = $objPurchaseOrder->getId();
                
                if($insertIdPurchaseOrder){
                    
                    if($objPaymentMethod->getCode() == 'CreditCard'){
                        
                        //Store BillingAddress
                        $objBillingAddress = new BillingAddress();
                        
                        $objBillingAddress->setPurchaseOrder($objPurchaseOrder);
                        $objBillingAddress->setFirstname($billingData['Firstname']);
                        $objBillingAddress->setLastname($billingData['Lastname']);
                        $objBillingAddress->setAddress($billingData['Address']);
                        $objBillingAddress->setCity($billingData['City']);
                        $objBillingAddress->setState($billingData['State']);
                        $objBillingAddress->setZip($billingData['Zipcode']);
                        $objBillingAddress->setCountry($billingData['Country']);
                        $objBillingAddress->setUser($user);
                        
                        $em->persist($objBillingAddress);
                        $em->flush();
                        $insertIdBillingAddress = $objBillingAddress->getId();
                    }

                    if($objPaymentMethod->getCode() == 'Chase'){
                        $chaseReq    = $request->get('chaseReq');

                        if (!empty($chaseReq['name'])) {

                            $name     = trim(urldecode($chaseReq['name']));
                            $fullname = explode(' ', $name);

                            $objBillingAddress = new BillingAddress();
                            $objBillingAddress->setPurchaseOrder($objPurchaseOrder);
                            $objBillingAddress->setFirstname(!empty($fullname[0])?$fullname[0]:'');
                            $objBillingAddress->setLastname(!empty($fullname[1]) ? $fullname[1] : '');

                            $address = urldecode($chaseReq['address1']) . "," . urldecode($chaseReq['address2']);
                            $objBillingAddress->setAddress($address);
                            $objBillingAddress->setCity($chaseReq['city']);
                            $objBillingAddress->setState($chaseReq['province']);
                            $objBillingAddress->setZip($chaseReq['postal_code']);
                            $objBillingAddress->setCountry($chaseReq['country']);
                            $objBillingAddress->setUser($user);
                            $em->persist($objBillingAddress);
                            $em->flush();
                            $insertIdBillingAddress = $objBillingAddress->getId();

                            $objChasePaymentResponse = new ChaseCheckout();
                            $objChasePaymentResponse->setResponse(json_encode($chaseReq));
                            $objChasePaymentResponse->setUser($user);
                            $objChasePaymentResponse->setChaseTransactionId($chaseReq['transId']);
                            $objChasePaymentResponse->setChaseProcessStatus("Completed");
                            $em->persist($objChasePaymentResponse);
                            $em->flush();
                            $chasePaymentResponseId = $objChasePaymentResponse->getId();

                            if (!empty($chaseReq['customerRefNum'])) {
                                $user->setCustomerRefNum($chaseReq['customerRefNum']);
                                $em->persist($user);
                            }

                            $objPurchaseOrder->setChase($objChasePaymentResponse);
                            $em->persist($objPurchaseOrder);
                            $em->flush();
                        }
                    }
                    
                    $updateServicePurchase = $em->createQueryBuilder()->update('DhiServiceBundle:ServicePurchase', 'sp')
                        ->set('sp.purchaseOrder', $insertIdPurchaseOrder)
                        ->where('sp.sessionId =:sessionId')
                        ->setParameter('sessionId', $sessionId)
                        ->andWhere('sp.paymentStatus =:paymentStatus')
                        ->setParameter('paymentStatus', 'New')
                        ->andWhere('sp.user =:user')
                        ->setParameter('user', $user)
                        ->getQuery()->execute();
                    
                    if($updateServicePurchase){
                        $isSuccess = true;
                    }
                    
                }
            }
            
            if($isSuccess){
                
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

                $this->get('session')->set('PurchaseOrderId',$insertIdPurchaseOrder);
                $this->get('session')->set('PurchaseOrderNumber',$orderNumber);
                $this->get('session')->set('BillingAddressId',$insertIdBillingAddress);
                $this->get('session')->set('IsISPAvailabledInCart',$summaryData['IsISPAvailabledInCart']);
                $this->get('session')->set('IsIPTVAvailabledInCart',$summaryData['IsIPTVAvailabledInCart']);
                
                //Add Activity Log
                $activityLog = array();
                $activityLog['user'] 	    = $user;
                $activityLog['activity']    = 'Purchase Order';
                $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment In process.';
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                //Activity Log end here

                if($objPaymentMethod->getCode() == 'Chase' && !empty($chaseReq['name'])) {
                    $condition = array('user' => $user, 'paymentStatus' => 'New', 'purchaseOrder' => $objPurchaseOrder);
                    $this->get('PaymentProcess')->updateServicePurchaseData($condition,array('paymentStatus' => 'Completed'));

                    $creditPaymentRefundStatus = $this->get('packageActivation')->addCreditInUserAccount($user);
                    $paymentRefundStatus = $this->get('packageActivation')->activateVodService($user,'admin');
                    if($paymentRefundStatus['status']){
                        $isPaymentRefund = $this->get('paymentProcess')->refundPayment(array(), $user, true);
                        if($paymentRefundStatus['reason'] != ''){
                            $this->get('session')->set('paymentMessage', $paymentRefundStatus['reason']);
                        }else{
                            $this->get('session')->remove('paymentMessage');
                        }
                    }
                }

                //Add Activity Log
                $activityLog                = array();
                $activityLog['user']        = $user;
                $activityLog['activity']    = 'Purchase Order';
                $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment has been completed';
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                //Activity Log end here



                return $paymentUrl;
            }else{
                $response = array(
                    'status'    => 'error',
                    'message'   => 'Selected payment option not available. please try another payment option.'
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
        

    public function paymentOptionAtrributesError(){

        $errorMsg = array(
                'Firstname'   => 'Please enter first name.',
                'Lastname'    => 'Please enter last name.',
                'Address'     => 'Please enter address.',
                'City'        => 'Please enter city.',
                'State'       => 'Please enter state.',
                'Zipcode'     => 'Please enter zip code.',
                'Country'     => 'Please select country.',
                'CardType'    => 'Please select card type.',
                'CardNumber'  => 'Please enter card number.',
                'ExpMonth'    => 'Please select month.',
                'ExpYear'     => 'Please select year.',
                'Cvv'         => 'Please enter cvv.',
                'MCardNumber' => 'Please enter card number',
                'MZipcode'    => 'Please enter zip code'
        );

        return $errorMsg;
    }
}
