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
        $frontMethods = array('chase', 'PayPal', 'CreditCard', 'Milstar');
        $objPaymentOptions = $em->getRepository("DhiServiceBundle:PaymentMethod")->getPaymentMethodByCode($frontMethods);
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
        $user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id

        $requestParams = $request->request->all();
        $errorMsgArr = $this->paymentOptionAtrributesError();

        $jsonResponse = array();
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
        $this->getPatmentMethod();

        if($requestParams && !empty($requestParams['paymentBy'])){

            if ($requestParams['paymentBy'] == 'cc') {
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
            }else if ($requestParams['paymentBy'] == 'chase') {
                
                if ($this->get('session')->has('ChaseWPInfo')) {
                    $this->get('session')->remove('ChaseWPInfo');
                }
                if ($this->get('session')->has('chaseUserErrorMsg')) {
                    $this->get('session')->remove('chaseUserErrorMsg');
                }

                if(isset($requestParams['ChaseWP']) && !empty($requestParams['ChaseWP'])){

                    $ChaseWPData  = $requestParams['ChaseWP'];

                    foreach ($ChaseWPData as $key => $val){
                        if(array_key_exists($key,$errorMsgArr)){
                            if($val == '' && $key != 'Address2'){
                                $jsonResponse['errorMessages']['ChaseWP'.$key] = $errorMsgArr[$key];
                            }
                        }
                    }

                    if(!empty($jsonResponse['errorMessages'])){
                        $jsonResponse['status']   = 'error';
                    }else{
                        $jsonResponse['status']  = 'success';
                        $this->get('session')->set('ChaseWPInfo',$ChaseWPData);
                    }
        }else{
                    $jsonResponse['status']  = 'failed';
                    $jsonResponse['message'] = 'Please check Chase information data.';
                }
            }else{
                $jsonResponse['status']  = 'success';
            }

        }else{

            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }

        return $jsonResponse;
    }

    public function paymentStepThree(Request $request) {

        $em          = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id

        $requestParams = $request->request->all();
        $errorMsgArr = $this->paymentOptionAtrributesError();
        $this->getPatmentMethod();
        $creditCardData = '';
        $milstarData = '';
        $recurringPayment = '';

        $jsonResponse = array();
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';

        if($requestParams && !empty($requestParams['paymentBy'])){

            $processStep     = $requestParams['processStep'];
            $recurringPayment = (!empty($requestParams['recurringPayment']))?$requestParams['recurringPayment']:0;

            if ($requestParams['paymentBy'] == 'cc') {
                $paymentBy = 'creditcard';
            }else{
                $paymentBy = $requestParams['paymentBy'];
            }

            if(in_array(strtolower($paymentBy),$this->paymentOptions)){

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

                if($requestParams['paymentBy'] == 'chase' &&  in_array($requestParams['chasePaymentType'], array('new-profile', 'without-profile'))){

                    if(isset($requestParams['chc']) && !empty($requestParams['chc'])){

                        $creditCardData  = $requestParams['chc'];

                        foreach ($creditCardData as $key => $val){

                            if(array_key_exists($key,$errorMsgArr)){

                                if($val == '' && $key != 'Address2'){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key];
                                }
                                if($val != '' && $key == 'Cvv' && strlen($val) > 4){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Length'];
                                }
                            }
                        }
                    }

                    if(isset($requestParams['ChaseWP']) && !empty($requestParams['ChaseWP'])){

                        $billingData  = $requestParams['ChaseWP'];

                        foreach ($billingData as $key => $val){

                            if(array_key_exists($key,$errorMsgArr)){

                                if($val == '' && $key != 'Address2'){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key];
                                }
                                if($val != '' && $key == 'Address' && strlen($val) > 30){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Length'];
                                }
                                if($val != '' && $key == 'Address2' && strlen($val) > 30){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Length'];
                                }
                                if($val != '' && $key == 'City' && strlen($val) > 20){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Length'];
                                }
                                if($val != '' && $key == 'CardNumber' && strlen($val) > 19){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Length'];
                                }
                                if($val != '' && $key == 'Zipcode' && strlen($val) > 10){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Length'];
                                }
                                if($val != '' && $key == 'Address' && !preg_match('/^[^%]*$/', $val)){
                                
                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Regex'];
                                }
                                if($val != '' && $key == 'Address2' && !preg_match('/^[^%]*$/', $val)){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Regex'];
                                }
                                if($val != '' && $key == 'City' && !preg_match('/^[^%]*$/', $val)){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Regex'];
                                }
                                if($val != '' && $key == 'Firstname' && !preg_match('/^[A-Za-z0-9 ]+$/', $val)){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Regex'];
                                }
                                if($val != '' && $key == 'Lastname' && !preg_match('/^[A-Za-z0-9 ]+$/', $val)){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Regex'];
                                }
                                if($val != '' && $key == 'CardNumber' && !preg_match('/^[A-Za-z0-9]+$/', $val)){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr[$key.'Regex'];
                                }
                                
                                $nameLenght = strlen($billingData['Firstname']) + strlen($billingData['Lastname']) ;
                                if(($key == 'Firstname' || $key == 'Lastname') && $nameLenght > 29){

                                    $jsonResponse['errorMessages']['ChaseWp'.$key] = $errorMsgArr['NameLength'];
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
                    if ($requestParams['paymentBy'] == 'chase' && !empty($requestParams['chasePaymentType'])) {
                        $this->get('session')->set('chasePaymentType',$requestParams['chasePaymentType']);
                    }

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

    public function doPaymentProcessAction(){
        $em                    = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user                  = $this->get('security.context')->getToken()->getUser();
        $sessionId             = $this->get('session')->get('sessionId'); //Get Session Id
        $orderNumber           = $this->get('PaymentProcess')->generateOrderNumber();
        $summaryData           = $this->get('DashboardSummary')->getUserServiceSummary();
        $billingData           = $this->get('session')->get('billingInfo');
        $chaseWPInfo           = $this->get('session')->get('ChaseWPInfo');
        $isDeersAuthenticated  = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $isDeersPackageExistInCart   = $this->get('paymentProcess')->checkDeersPackageExistInCart($summaryData);
        $ipAddress             = $this->get('session')->get('ipAddress');
        $this->getPatmentMethod();

        if($isDeersPackageExistInCart && $isDeersAuthenticated == 2) {

            $this->get('session')->getFlashBag()->add('failure', 'DEERS authentication is required for purchase package.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }

        $insertIdBillingAddress = '';

        $paymentBy = $this->get('session')->get('paymentBy');

        $isSuccess = false;
        $paymentUrl = '';

        if ($paymentBy == 'cc') {
            $condPaymentBy = 'creditcard';
        }else{
            $condPaymentBy = $paymentBy;
        }

        if(in_array(strtolower($condPaymentBy),$this->paymentOptions)){

            if(strtolower($paymentBy) == 'paypal'){

                $paymentUrl = $this->redirect($this->generateUrl('dhi_paymentby_expresscheckout'));
            }elseif(strtolower($paymentBy) == 'cc'){

                if(!$billingData){

                    $this->get('session')->getFlashBag()->add('failure', 'Billing address information not found.');
                    return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                }

                $paymentUrl = $this->redirect($this->generateUrl('dhi_paymentby_dodirect'));
            }elseif(strtolower($paymentBy) == 'chase'){


                $paymentUrl = $this->redirect($this->generateUrl('dhi_paymentby_chase'));
            }elseif(strtolower($paymentBy) == 'milstar'){

					if($user->getCid() && $this->get('session')->get('isDearsAuthenticatedForMilstar') == 1)	{
                		$this->get('session')->remove('isDearsAuthenticatedForMilstar');
                		$paymentUrl = $this->redirect($this->generateUrl('dhi_paymentby_milstar'));
                	}	else	{
                		$this->get('session')->getFlashBag()->add('failure', 'Milstar customer Id not found.');
                		return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                	}
//                if($this->get('DeersAuthentication')->checkDeersAuthenticated('Milstar') == 1){
//
//                	if($user->getCid()){
//
//                		$paymentUrl = $this->redirect($this->generateUrl('dhi_paymentby_milstar'));
//                	}else{
//
//                		$this->get('session')->getFlashBag()->add('failure', 'Milstar customer Id not found.');
//                		return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
//                	}
//                }else{
//
//                    $this->get('session')->getFlashBag()->add('failure', 'DEERS authentication is required for Milstar payment. You can <a href="'.$this->generateUrl('dhi_user_deers_auth').'">click this link</a> to login and clear DEERS authentication');
//                    return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
//                }
            }else{

                throw $this->createNotFoundException('Invalid Page Request');
            }


            if($summaryData['CartAvailable']){

                $objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->find(array_search(strtolower($condPaymentBy), $this->paymentOptions));

                //Save paypal response in PaypalExpressCheckOutCustomer table
                $objPurchaseOrder = new PurchaseOrder();
                $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                $objPurchaseOrder->setSessionId($sessionId);
                $objPurchaseOrder->setOrderNumber($orderNumber);
                $objPurchaseOrder->setUser($user);
                $objPurchaseOrder->setPaymentBy("User");
                $objPurchaseOrder->setPaymentByUser($user);
                $objPurchaseOrder->setTotalAmount($summaryData['TotalCartAmount']);
                $objPurchaseOrder->setIpAddress($ipAddress);

                $em->persist($objPurchaseOrder);
                $em->flush();
                $insertIdPurchaseOrder = $objPurchaseOrder->getId();

                if($insertIdPurchaseOrder){

                    if(strtolower($objPaymentMethod->getCode()) == 'creditcard'){

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

                    }else if(strtolower($objPaymentMethod->getCode()) == 'chase' && !empty($chaseWPInfo)){

                        $objBillingAddress = new BillingAddress();
                        $objBillingAddress->setPurchaseOrder($objPurchaseOrder);
                        $objBillingAddress->setFirstname($chaseWPInfo['Firstname']);
                        $objBillingAddress->setLastname($chaseWPInfo['Lastname']);
                        $objBillingAddress->setAddress($chaseWPInfo['Address']);
                        $objBillingAddress->setAddress2((!empty($chaseWPInfo['Address2']) ? $chaseWPInfo['Address2'] : ''));
                        $objBillingAddress->setCity($chaseWPInfo['City']);
                        $objBillingAddress->setState($chaseWPInfo['State']);
                        $objBillingAddress->setZip($chaseWPInfo['Zipcode']);
                        $objBillingAddress->setCountry($chaseWPInfo['Country']);
                        $objBillingAddress->setUser($user);

                        $em->persist($objBillingAddress);
                        $em->flush();
                        $insertIdBillingAddress = $objBillingAddress->getId();
                    }
                    if(strtolower($objPaymentMethod->getCode()) == 'chase'){
                        
                        $chaseMerchantData    = $this->get('DashboardSummary')->getUserLocationWiseChaseMID($user);
                        if(isset($chaseMerchantData['chaseMerchantPID']) && !empty($chaseMerchantData['chaseMerchantPID'])){
                            $objChaseMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->find($chaseMerchantData['chaseMerchantPID']);
                            $objPurchaseOrder->setChaseMerchantId($objChaseMerchantId);
                        }
                        
                        $objPurchaseOrder->setIsDefaultChaseMid($chaseMerchantData['isDefaultChaseMID']);
                        $em->persist($objPurchaseOrder);
                        $em->flush();
                        
                    }
                    $updateServicePurchase = $em->createQueryBuilder()->update('DhiServiceBundle:ServicePurchase', 'sp')
                        ->set('sp.purchaseOrder', $insertIdPurchaseOrder);
                    /*if($paymentBy == 'chase'){
                        $updateServicePurchase->set('sp.isChaseDeers', $summaryData['IsDeersRequiredPlanAdded']);
                    }*/
                    $updateServicePurchase->where('sp.sessionId =:sessionId')
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

				if($summaryData['IsISPAvailabledInCart'] == 1) {

                    if(!empty($summaryData['Cart']['IPTV']) && $summaryData['IsBundleAvailabledInCart'] == 0){
                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                    }

                    if(!empty($summaryData['Purchased']['IPTV']) && $summaryData['IsBundleAvailabledInCart'] == 0){
                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                    }

                    $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getId());

					if(!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {

						$this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));

					}

                }

				if($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsAddOnAvailabledInCart']) {

                    if(!empty($summaryData['Cart']['ISP']) && $summaryData['IsBundleAvailabledInCart'] == 0){
                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                    }

                    if(!empty($summaryData['Purchased']['ISP']) && $summaryData['IsBundleAvailabledInCart'] == 0  && $summaryData['IsBundleAvailabledInPurchased'] == 0){
                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                    }

                    $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);

                    if($isSelevisionUser == 0) {

                        $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                    }
                }

                if($summaryData['IsISPAvailabledInCart'] == 1) {

                    $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());

                    if(!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {

                        $this->get('session')->getFlashBag()->add('failure', 'Something went wrong with your purchase. Please contact support if the issue persists.');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
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
                $activityLog['activity']    = ' Purchase Order';
                $activityLog['description'] = 'User '.$user->getUserName().' OrderNo# '.$orderNumber.' payment In process.';
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                //Activity Log end here

                return $paymentUrl;
            }else{

                $this->get('session')->getFlashBag()->add('failure', 'Selected payment option not available. please try another payment option.');
                return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
            }
        }else{

            throw $this->createNotFoundException('Invalid Page Request');
        }
    }


    public function paymentOptionAtrributesError(){

        $errorMsg = array(
                'Firstname'   => 'Please enter first name.',
                'Lastname'    => 'Please enter last name.',
                'Address'     => 'Please enter address.',
                'Address2'    => 'Please enter address.',
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
                'MZipcode'    => 'Please enter zip code',
                'AddressLength'    => 'Maximum 30 characters allowed.',
                'Address2Length'   => 'Maximum 30 characters allowed.',
                'CityLength'       => 'Maximum 20 characters allowed.',
                'CvvLength'        => 'Maximum 4 digits allowed.',
                'CardNumberLength' => 'Maximum 19 characters allowed.',
                'ZipcodeLength'    => 'Maximum 10 characters allowed.',
                'NameLength'       => 'Maximum 30 characters allowed in Fullname.',
                'AddressRegex'     => "Please enter valid Address. Character % is not allowed.",
                'Address2Regex'    => "Please enter valid Address 2. Character % is not allowed.",
                'CityRegex'        => "Please enter valid City. Character % is not allowed.",
                'FirstnameRegex'   => "First name contains only alphanumeric and space.",
                'LastnameRegex'    => "Last name contains only alphanumeric and space.",
                'CardNumberRegex'  => "Card number contains only alphanumeric."
                
        );

        return $errorMsg;
    }
}
