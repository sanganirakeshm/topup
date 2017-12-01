<?php
namespace Dhi\ServiceBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ExpressCheckout
 *
 * @author ketan.dhokia
 */
class ExpressCheckout {

    protected $METHOD           = 'SetExpressCheckout';
    protected $USERNAME         = '';
    protected $PASSWORD         = '';
    protected $SIGNATURE        = '';
    protected $PAYPALMODE       = '';
    protected $RETURNURL        = '';
    protected $CANCELURL        = '';
    protected $PAYMENTACTION    = '';
    protected $CURRENCYCODE     = 'USD';
    protected $NOSHIPPING       = '0';
    protected $LOCALECODE       = 'USD';//PayPal pages to match the language on your website.
    protected $LOGOIMG          = ''; //site logo
    protected $CARTBORDERCOLOR  = 'FFFFFF'; //border color of cart
    protected $ALLOWNOTE        = '1';
    protected $VERSION          = '109.0';
    protected $TOKEN            = '';
    protected $PAYERID          = '';
    protected $AUTHORIZATIONID  = '';
    protected $COMPLETETYPE     = '';
    protected $AMOUNT           = '';
    protected $NOTE             = '';
    protected $INVNUM           = '';
    protected $TRANSACTION_ID   = '';
    protected $REFUNDTYPE       = '';
    protected $L_BILLINGTYPE0	= '';
    protected $L_BILLINGAGREEMENTDESCRIPTION0 = '';
    private $container;

    function __construct($option,$container){

        if(isset($option) && !empty($option)){
            foreach ($option as $key => $val) {
                $this->$key = $val;
            }
        }

        $this->container = $container;
        if (!empty($option['TVOD'])) {
            $summaryData = $this->container->get('DashboardSummary')->getUserServiceSummary('', $option['TVOD']['userId'], $option['TVOD']['isTvod']);
        }else{
            $summaryData = $this->container->get('DashboardSummary')->getUserServiceSummary();
        }
        $this->USERNAME     = $summaryData['paypalCredential']['paypal_username']; //Get Paypal Business Username
        $this->PASSWORD     = $summaryData['paypalCredential']['paypal_password']; //Get Paypal Business Password
        $this->SIGNATURE    = $summaryData['paypalCredential']['paypal_signature']; //Get Paypal Business Signature
        $this->PAYPALMODE   = $summaryData['paypalCredential']['paypal_mode']; //Get Paypal api mode
    }

    public function authSetting(){
        $settingArr = array();

        $settingArr[] = 'METHOD='.$this->METHOD;
        $settingArr[] = 'VERSION='.$this->VERSION;
        $settingArr[] = 'PWD='.$this->PASSWORD;
        $settingArr[] = 'USER='.$this->USERNAME;
        $settingArr[] = 'SIGNATURE='.$this->SIGNATURE;
        return implode('&', $settingArr);
    }

    public function commanSetting(){
        $settingArr = array();

        $settingArr[] = 'PAYMENTREQUEST_0_PAYMENTACTION='.urlencode($this->PAYMENTACTION);
        $settingArr[] = 'PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->CURRENCYCODE);

        $strSetting = $this->authSetting();
        $strSetting .= '&'.implode('&', $settingArr);

        return $strSetting;
    }

    public function setExpressCheckout($paymentDetail){
        $paypalmode = ($this->PAYPALMODE=='Sandbox') ? '.sandbox' : '';
        $itemstring = $this->commanSetting();
        $itemstring .= '&SOLUTIONTYPE=Sole&'.$this->processPayment($paymentDetail);

        $itemstring .= ($this->L_BILLINGTYPE0)?'&L_BILLINGTYPE0='.urlencode($this->L_BILLINGTYPE0):'';
        $itemstring .= ($this->L_BILLINGAGREEMENTDESCRIPTION0)?'&L_BILLINGAGREEMENTDESCRIPTION0='.urlencode($this->L_BILLINGAGREEMENTDESCRIPTION0):'';

        $httpParsedResponseAr = $this->postToPaypal($itemstring);

        if("SUCCESS" == strtoupper($httpParsedResponseAr["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($httpParsedResponseAr["ACK"]))
        {
            //Redirect user to PayPal store with Token received.
            $paypalurl ='https://www'.$paypalmode.'.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token='.$httpParsedResponseAr["TOKEN"].'';
            header('Location: '.$paypalurl);
            exit;
        }
        return $httpParsedResponseAr;
    }

    public function processPayment($paymentDetail){
        $i=0;
        $paypalConfig = array();
        $GrandTotal = 0;

        $paypalConfig[] = 'NOSHIPPING='.$this->NOSHIPPING; //set 1 to hide buyer's shipping address, in-case products that does not require shipping
        $paypalConfig[] = 'LOCALECODE='.$this->LOCALECODE; //PayPal pages to match the language on your website.
        $paypalConfig[] = 'LOGOIMG='; //site logo
        $paypalConfig[] = 'CARTBORDERCOLOR='.$this->CARTBORDERCOLOR;//border color of cart
        $paypalConfig[] = 'ALLOWNOTE='.$this->ALLOWNOTE;
        $paypalConfig[] = 'RETURNURL='.urlencode($this->RETURNURL);
        $paypalConfig[] = 'CANCELURL='.urlencode($this->CANCELURL);

        if(isset($paymentDetail['item']) && !empty($paymentDetail['item'])){

            foreach ($paymentDetail['item'] as $val){

                if(isset($val['name']) && !empty($val['name'])){
                    $paypalConfig[] = 'L_PAYMENTREQUEST_0_NAME'.$i.'='.urlencode($val['name']);
                }

                if(isset($val['desc']) && !empty($val['desc'])){
                    $paypalConfig[] = 'L_PAYMENTREQUEST_0_DESC'.$i.'='.urlencode($val['desc']);
                }

                if(isset($val['amt']) && !empty($val['amt'])){
                    $paypalConfig[] = 'L_PAYMENTREQUEST_0_AMT'.$i.'='.urlencode($val['amt']);
                    $GrandTotal += $val['amt'];
                }

                $i++;
            }

            $paypalConfig[] = 'PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal);
        }
        //echo implode('&', $paypalConfig);exit;
        return implode('&', $paypalConfig);
    }

    public function DoExpressCheckoutPayment($paymentDetail){

        $configArr = array();
        $configArr[] = 'TOKEN='.urlencode($this->TOKEN);
        $configArr[] = 'PAYERID='.urlencode($this->PAYERID);

        if(isset($paymentDetail['item']) && !empty($paymentDetail['item'])){
            $i=0;
            $itemArr = array();
            $GrandTotal = 0;
            foreach ($paymentDetail['item'] as $val){
                if(isset($val['name']) && !empty($val['name'])){
                    $configArr[] = 'L_PAYMENTREQUEST_0_NAME'.$i.'='.urlencode($val['name']);
                }

                if(isset($val['number']) && !empty($val['number'])){
                    $configArr[] = 'L_PAYMENTREQUEST_0_NUMBER'.$i.'='.urlencode($val['number']);
                }

                if(isset($val['desc']) && !empty($val['desc'])){
                    $configArr[] = 'L_PAYMENTREQUEST_0_DESC'.$i.'='.urlencode($val['desc']);
                }

                if(isset($val['amt']) && !empty($val['amt'])){
                    $configArr[] = 'L_PAYMENTREQUEST_0_AMT'.$i.'='.urlencode($val['amt']);
                    $GrandTotal += $val['amt'];
                }

                if(isset($val['qty']) && !empty($val['qty'])){
                    $configArr[] = 'L_PAYMENTREQUEST_0_QTY'.$i.'='. urlencode($val['qty']);

                }
                $i++;
            }
            $configArr[] = 'PAYMENTREQUEST_0_AMT='.urlencode($GrandTotal);
        }


        $configArr[] = 'PAYMENTREQUEST_0_CURRENCYCODE='.urlencode($this->CURRENCYCODE);

        $postString = $this->commanSetting();
        $postString .= '&'.implode('&', $configArr);

        return $this->postToPaypal($postString);
    }

    public function DoCapture(){

        $settingArr = array();

        $settingArr[] = 'AUTHORIZATIONID='.urlencode($this->AUTHORIZATIONID);
        $settingArr[] = 'AMT='.urlencode($this->AMOUNT);
        $settingArr[] = 'COMPLETETYPE='.urlencode($this->COMPLETETYPE);
        $settingArr[] = 'INVNUM='.urlencode($this->INVNUM);

        $strSetting = $this->authSetting();
        $strSetting .= '&'.implode('&', $settingArr);

        return $this->postToPaypal($strSetting);

    }

    public function DoVoid(){
        $configArr[] = 'AUTHORIZATIONID='.urlencode($this->AUTHORIZATIONID);
        $configArr[] = 'NOTE='.urlencode($this->NOTE);

        $postString = $this->authSetting();
        $postString .= '&'.implode('&', $configArr);

        return $this->postToPaypal($postString);
    }

    public function getExpressCheckOutDetail(){

        $configArr = array();
        $configArr[] = 'TOKEN='.urlencode($this->TOKEN);

        $postString = $this->authSetting();
        $postString .= '&'.implode('&', $configArr);

        return $this->postToPaypal($postString);
    }

    public function setMethod($method){

        $this->METHOD = $method;
        return $this;
    }

    public function postToPaypal($nvpreq){
        $paypalmode = ($this->PAYPALMODE=='Sandbox') ? '.sandbox' : '';

        $API_Endpoint = "https://api-3t".$paypalmode.".paypal.com/nvp";
        $version = urlencode('109.0');

        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSLVERSION, 1);


        // Set the request as a POST FIELD for curl.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        // Get response from the server.
        $httpResponse = curl_exec($ch);

        if(!$httpResponse) {
            exit("$this->METHOD failed: ".curl_error($ch).'('.curl_errno($ch).')');
        }

        // Extract the response details.
        $httpResponseAr = explode("&", $httpResponse);

        $httpParsedResponseAr = array();
        foreach ($httpResponseAr as $i => $value) {
            $tmpAr = explode("=", $value);
            if(sizeof($tmpAr) > 1) {
                $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
            }
        }

        if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr)) {

            //exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
        }

        return $httpParsedResponseAr;
    }

    public function DoDirectPayment($itemList,$billingAddress,$cardDetail){

        $ccDetailArr[] = 'PAYMENTACTION='.urlencode($this->PAYMENTACTION);
        $ccDetailArr[] = 'IPADDRESS='.urlencode($cardDetail['IPAddress']);
        $ccDetailArr[] = 'CREDITCARDTYPE='.urlencode($cardDetail['CardType']);
        $ccDetailArr[] = 'ACCT='.urlencode($cardDetail['CardNumber']);
        $ccDetailArr[] = 'EXPDATE='.urlencode($cardDetail['ExpMonth'].''.$cardDetail['ExpYear']);
        $ccDetailArr[] = 'CVV2='.urlencode($cardDetail['Cvv']);
        //$ccDetailArr[] = 'PAYERSTATUS='.urlencode($ccDetail['PayerStatus']);
        $ccDetailArr[] = 'FIRSTNAME='.urlencode($billingAddress->getFirstname());
        $ccDetailArr[] = 'LASTNAME='.urlencode($billingAddress->getLastname());
        $ccDetailArr[] = 'STREET='.urlencode($billingAddress->getAddress());
        $ccDetailArr[] = 'CITY='.urlencode($billingAddress->getCity());
        $ccDetailArr[] = 'STATE='.urlencode($billingAddress->getState());
        $ccDetailArr[] = 'ZIP='.urlencode($billingAddress->getZip());
        $ccDetailArr[] = 'COUNTRYCODE='.urlencode($billingAddress->getCountry());


        $i=0;
        $itemArr = array();
        $GrandTotal = 0;
        if(isset($itemList['item']) && !empty($itemList['item'])){
            foreach ($itemList['item'] as $val){
                if(isset($val['name']) && !empty($val['name'])){
                    $itemArr[] = 'L_NAME'.$i.'='.urlencode($val['name']);
                }

                if(isset($val['number']) && !empty($val['number'])){
                    $itemArr[] = 'L_NUMBER'.$i.'='.urlencode($val['number']);
                }

                if(isset($val['desc']) && !empty($val['desc'])){
                    $itemArr[] = 'L_DESC'.$i.'='.urlencode($val['desc']);
                }

                if(isset($val['amt']) && !empty($val['amt'])){
                    $itemArr[] = 'L_AMT'.$i.'='.urlencode($val['amt']);
                    $GrandTotal += $val['amt'];
                }

                if(isset($val['qty']) && !empty($val['qty'])){
                    $itemArr[] = 'L_QTY'.$i.'='. urlencode($val['qty']);

                }
                $i++;
            }
        }

        $ccDetailArr[] = 'AMT='.urlencode($GrandTotal);

        $postString = $this->authSetting();
        $postString .= '&'.implode('&', $ccDetailArr);
        $postString .= '&'.implode('&', $itemArr);

        return $this->postToPaypal($postString);
    }

    public function refundPayment(){

        $refundConfig		= array();
        $refundConfig[]		= 'INVOICEID='.urlencode($this->INVNUM);
        $refundConfig[] 	= 'TRANSACTIONID='.urlencode($this->TRANSACTION_ID);
        $refundConfig[] 	= 'REFUNDTYPE='.urlencode($this->REFUNDTYPE);
        $refundConfig[] 	= 'CURRENCYCODE='.urlencode($this->CURRENCYCODE);

        if($this->REFUNDTYPE == 'Partial'){

            $refundConfig[] 	= 'AMT='.urlencode($this->AMOUNT);
        }

        $refundStr			= implode('&',$refundConfig);

        $nvpStr 				= $this->authSetting().'&'.$refundStr;
        $httpParsedResponseAr 	= $this->postToPaypal($nvpStr);

        return $httpParsedResponseAr;
    }

    public function createRecurringPaymentProfile($payby,$recurringProfileDetail = array(),$otherDetail=array()) {

    	if(count($recurringProfileDetail) > 0) {

    		$paypalPostParam = array();

    		foreach ($recurringProfileDetail as $key => $val) {

    			$paypalPostParam[] = $key.'='.urlencode($val);
    		}

	    	if ($payby == 'CreditCard') {

	    		if (array_key_exists('cardDetail', $otherDetail)) {

	    			$cardDetail = $otherDetail['cardDetail'];

	    			if (count($cardDetail) > 0) {

		    			foreach ($cardDetail as $key => $val) {

		    				$paypalPostParam[] = $key.'='.urlencode($val);
		    			}
	    			}
	    		}

	    		if (array_key_exists('billingDetail', $otherDetail)) {

	    			$billingDetail = $otherDetail['billingDetail'];

	    			if (count($billingDetail) > 0) {

	    				foreach ($billingDetail as $key => $val) {

	    					$paypalPostParam[] = $key.'='.urlencode($val);
	    				}
	    			}
	    		}


	    	}

	    	if ($payby == 'ExpressCheckout') {

	    		if (count($otherDetail) > 0) {

	    			foreach ($otherDetail as $key => $val) {

	    				$paypalPostParam[] = $key.'='.urlencode($val);
	    			}
	    		}
	    	}

	    	$refundConfig[] 	= 'CURRENCYCODE='.urlencode($this->CURRENCYCODE);

	    	$nvpStr = $this->authSetting();
	    	$nvpStr .= '&'.implode('&',$paypalPostParam);

	    	$httpParsedResponseAr 	= $this->postToPaypal($nvpStr);

	    	return $httpParsedResponseAr;
    	}

    	return false;
    }

    public function getRecurringProfileData($profileId) {

    	$nvpStr = $this->authSetting();
    	$nvpStr .= '&PROFILEID='.$profileId;

    	$httpParsedResponseAr 	= $this->postToPaypal($nvpStr);

    	if($httpParsedResponseAr) {

    		foreach ($httpParsedResponseAr as $key => $val) {

    			$httpParsedResponseAr[$key] = urldecode($val);
    		}
    	}

    	return $httpParsedResponseAr;
    }
}
