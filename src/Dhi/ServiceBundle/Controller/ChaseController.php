<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ChaseController extends Controller
{
    const REQUEST_TYPE_AUTH_CAPTURE = 'AC';
    const REQUEST_TYPE_AUTH_ONLY    = 'A';
    const REQUEST_TYPE_CAPTURE_ONLY = 'C';
    const REQUEST_TYPE_CREDIT       = 'R';
    
    const RESPONSE_CODE_APPROVED = 1;
    const RESPONSE_CODE_DECLINED = 2;
    const RESPONSE_CODE_ERROR    = 3;
    const RESPONSE_CODE_HELD     = 4;
    
    const RESPONSE_PROC_STATUS_APPROVED = 0;
    const STATUS_APPROVED               = 'Approved';
    
    protected $container;
    protected $session;
    protected $_code                   = 'paymenttechchase';
    protected $_isGateway              = true;
    protected $_canAuthorize           = false;
    protected $_canCapture             = true;
    protected $_canCapturePartial      = true;
    protected $_canRefund              = true;
    protected $_canVoid                = false;
    protected $_canUseInternal         = true;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc              = false;
    protected $config                  = array();
    
    public function __construct($container)
    {
        $this->container = $container;
        $this->config = $container->getParameter('chase');
        $this->session = $container->get('session');
    }
    
    public function getApiGatewayUrl()
    {
        $value = $this->config['cgi_url'];
        return $value;
    }

    public function getApiGatewaySecondaryUrl()
    {
        $value = $this->config['cgi_secondary_url'];
        return $value;
    }
    
    private function getTransactionId()
    {
        return $this->transactionId;
    }
    
    private function setTransactionParams($response)
    {
        $this->transactionId = $response->txRefNum;
    }
    
    public function authorize($payment, $amount, $chaseMerchantData = array())
    {
        if(isset($chaseMerchantData['merchantId'])){
            $this->config['merchantId'] = $chaseMerchantData['merchantId'];
        }
        
        if (!extension_loaded('soap')){
            return $this->getResponse('error', 'Error No: #1005, Something went wrong with your purchase. Please contact support if the issue persists.');
        }

        if (empty($this->config['username']) || empty($this->config['password']) || empty($this->config['bin_no']) || empty($this->config['merchantId']) || empty($this->config['terminalId']) || empty($this->config['cgi_secondary_url']) || empty($this->config['cgi_url'])) {
            return $this->getResponse('error', 'Error No: #1004, Something went wrong with your purchase. Please contact support if the issue persists.');
        }

        if ($amount <= 0) {
            return $this->getResponse('error', 'Invalid amount');
        }
        
        $payment['chaseTransType'] = self::REQUEST_TYPE_AUTH_CAPTURE;
        $payment['amount']         = $amount;
        
        $request  = $this->_buildRequest($payment);
        $response = $this->_postRequest($request);
        
        if (!empty($response['status']) && $response['status'] != 'error') {
            $payment['status']      = self::STATUS_APPROVED;
            $payment['LastTransId'] = $this->getTransactionId();
        }

        if (!empty($response['data']) && $response['status'] == 'error') {
            $this->errorData = $response['data'];
        }

        $response = $this->getResponse($response['status'], $response['message'], $this);
        return $response;
    }
    
    protected function _buildRequest($payment)
    {
        $order   = $payment['order'];
        $request = new \stdClass();

        if ($order && $order['orderNumber']) {
            $request->orderID = $order['orderNumber'];
            if (!empty($order['billingAddress'])) {
                $billing                 = $order['billingAddress'];
                $request->avsZip         = $billing['postcode'];
                $request->avsAddress1    = $billing['avsAddress1'];
                $request->avsAddress2    = $billing['avsAddress2'];
                $request->avsCity        = $billing['avsCity'];
                $request->avsState       = $billing['avsState'];
                $request->avsCountryCode = $billing['avsCountryCode'];
            }
        }
        
        $request->orbitalConnectionUsername = $this->config['username'];
        $request->orbitalConnectionPassword = $this->config['password'];
        $request->bin                       = $this->config['bin_no'];
        $request->merchantID                = $this->config['merchantId'];
        $request->terminalID                = $this->config['terminalId'];
        $request->transType                 = $payment['chaseTransType'];
        
        if (empty($payment['custRefNo'])) {
            
            if (!empty($payment['CcExpMonth'])) {
                if(strlen($payment['CcExpMonth'])==1){
                  $exp = $payment['CcExpYear']."0".$payment['CcExpMonth'];
                }else{
                  $exp = $payment['CcExpYear'].$payment['CcExpMonth'];
                }
                $request->ccExp = $exp;
            }

            if(!empty($payment['CcCid'])){
                $request->ccCardVerifyNum = $payment['CcCid'];
            }

            if (!empty($payment['CcNumber'])) {
                $request->ccAccountNum = $payment['CcNumber'];
            }

            if(!empty($payment['CcType']) && ($payment['CcType'] == 'VI' || $payment['CcType'] == 'DI') &&  $payment['CcCid'] != ''){
                $request->ccCardVerifyPresenceInd = 1;
            }

            if (!empty($payment['CcOwner'])) {
                $request->avsName = $payment['CcOwner'];
            }

            $request->addProfileFromOrder = ((!empty($payment['chasePaymentType']) && $payment['chasePaymentType'] == 'new-profile') ? 'A' : '')  ;

            $request->profileOrderOverideInd = 'NO';
        } else {
            $custRefNo                  = $payment['custRefNo'];
            $request->customerRefNum    = $custRefNo;
            $request->useCustomerRefNum = $custRefNo;
        }
        $request->comments     = "Po No: ".$order['orderNumber'];
        $request->orderID      = $order['orderNumber'];
        $request->industryType = "EC";

        if (!empty($payment['lastTransId']) && $payment['lastTransId'] && $this->amount > 0) {
            $transId = $payment['lastTransId'];
        }

        if(!empty($payment['amount'])){
            $request->amount = str_replace('.','',$payment['amount']);
        }
        switch ($payment['chaseTransType'] && !empty($transId)) {
            case self::REQUEST_TYPE_CREDIT:
                $request->txRefNum = $transId;
                break;
        }
        return $request;
    }

    protected function _postRequest($request)
    {
        $url                  = $this->getApiGatewayUrl() . '/PaymentechGateway.wsdl';
        $secondaryUrl         = $this->getApiGatewaySecondaryUrl() . '/PaymentechGateway.wsdl';
        $no                   = new \stdClass();
        $no->newOrderRequest  = $request;
        $soapError            = "";
        $soapIsToRunSecondary = false;

        try {
            $client = @new \SoapClient(
                $url,
                array(
                  //'ssl_method'     => SOAP_SSL_METHOD_TLS,
                  'cache_wsdl'     => WSDL_CACHE_NONE,
                  'trace' => 1,
                  'stream_context' => stream_context_create(
                    array(
                      'ssl'=> array(
                        'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                        'ciphers' => 'SHA256',
                        'verify_peer'=>false,
                        'verify_peer_name'=>false, 
                        'allow_self_signed' => true //can fiddle with this one.
                      )
                    )
                           
                  )
                )
            );
            $ret                    = $client->newOrder($no);
            $response               = $ret->return;
            $response->endPointType = 'Primary';
            $response->soapError    = (!empty($soapError) ? $soapError : '');
            $response->endPoint     = $url;
            $this->authResponse     = $response;

        }catch (\SoapFault $e) {
            $soapError = $e->getMessage();
            $soapIsToRunSecondary = true;
        }

            
        if ($soapIsToRunSecondary == true) {
            try {
                $client = @new \SoapClient(
                    $secondaryUrl,
                    array(
                      //'ssl_method'     => SOAP_SSL_METHOD_TLS,
                      'cache_wsdl'     => WSDL_CACHE_NONE,
                      'trace' => 1,
                      'stream_context' => stream_context_create(
                        array(
                          'ssl'=> array(
                            'crypto_method' => STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
                            'ciphers' => 'SHA256',
                            'verify_peer'=>false,
                            'verify_peer_name'=>false, 
                            'allow_self_signed' => true //can fiddle with this one.
                          )
                        )
                          
                      )
                    )
                );
                $ret                    = $client->newOrder($no);
                $response               = $ret->return;
                $response->endPointType = 'Secondary';
                $response->soapError    = (!empty($soapError) ? $soapError : '');
                $response->endPoint     = $secondaryUrl;
                $this->authResponse     = $response;
            }catch (\SoapFault $e) {
                $data = array(
                    'soapError'    => (!empty($soapError) ? $soapError : ''),
                    'endPointType' => 'Secondary',
                    'endPoint'     => $secondaryUrl
                );
                return $this->getResponse('error', $e->getMessage(), $data);
            }
        }

        if ($response->procStatus == self::RESPONSE_PROC_STATUS_APPROVED) {
            
            if ($response->approvalStatus == self::RESPONSE_CODE_APPROVED) {
                // $this->unsError();
                
            } else {

                if ($response->cvvRespCode != 'M') {
                    $message       = 'Cardholder Verification : ' . (!empty($response->respMsg) ? $response->respMsg : '');
                    $message       = str_replace(' ', '', $message);
                    $errorResponse = $this->getResponse('error', $message . ' ' . $response->procStatusMessage);
                    
                } else if ($response->avsRespCode != 'Z' || $response->avsRespCode != '9' || $response->avsRespCode != 'H') {
                    $message       = 'Address Verfication : ' . (isset($response->respMsg) ? $response->respMsg : '');
                    $message       = str_replace(' ', '', $message);
                    $errorResponse = $this->getResponse('error', $message . ' ' . $response->procStatusMessage);
                    
                } else if ($response->approvalStatus == 0 || $response->approvalStatus == 2) {
                    $errorResponse = $this->getResponse('error', 'Card is in Decline State. ' . $response->procStatusMessage);
                    
                }
                return $errorResponse;
            }
        } else {
            if ($response->procStatus != self::RESPONSE_PROC_STATUS_APPROVED) {
                $response = $this->getResponse('error', 'System Error : ' . $response->procStatusMessage);
                
            } else if ($response->cvvRespCode != 'M') {
                $response = $this->getResponse('error', 'Cardholder Verification : ' . $response->procStatusMessage);
                
            } else if ($response->avsRespCode != 'Z' || $response->avsRespCode != '9' || $response->avsRespCode != 'H') {
                $response = $this->getResponse('error', 'Address Verfication :' . $response->procStatusMessage);
                
            } else if ($response->approvalStatus == 0 || $response->approvalStatus == 2) {
                $response = $this->getResponse('error', 'Card is in Decline State' . $response->procStatusMessage);
            }
            return $response;
        }

        $this->setTransactionParams($response);
        return $this->getResponse('success', '');
    }
    
    public function parseErrMessage($message)
    {
        $errMessage    = @explode(' ', trim($message));
        $allerrMessage = '';
        if (count($errMessage) > 0) {
            for ($i = 1; $i < count($errMessage); $i++) {
                $allerrMessage .= $errMessage[$i] . " ";
            }
        }        
        return $allerrMessage;
    }
    
    private function getResponse($status, $message, $response = array())
    {
        $userError = '';

        if ($status == 'error') {

            if (is_object($response) && isset($response->procStatus) && !empty($response->procStatus)) {
                $userError = $this->getErrors('procStatus', $response->procStatus);
                $responseCode = $response->procStatus;
            }

            if (empty($userError) && is_object($response) && isset($response->respCode) && !empty($response->respCode)) {
                $userError = $this->getErrors('respCode', $response->respCode);
                $responseCode = $response->respCode;
            }

            if (empty($userError) && is_object($response) && isset($response->avsRespCode) && !empty($response->avsRespCode)) {
                $userError = $this->getErrors('avsRespCode', $response->avsRespCode);
                $responseCode = $response->avsRespCode;
            }

            if (empty($userError) && is_object($response) && isset($response->cvvRespCode) && !empty($response->cvvRespCode)) {
                $userError = $this->getErrors('cvvRespCode', $response->cvvRespCode);
                $responseCode = $response->cvvRespCode;
            }

            if (empty($userError) && !empty($message) && strpos($message, " Error ")) {
                $originalMsg = trim($message);
                $msgArr      = explode(' Error ', $originalMsg);
                if (!empty($msgArr[0])) {
                    $userError = $this->getErrors('other', $msgArr[0]);
                    $responseCode = $msgArr[0];
                }
            }

            if (empty($userError) && !empty($message) && strpos($message, " Error. ")) {
                $originalMsg = trim($message);
                $msgArr      = explode(' Error. ', $originalMsg);
                if (!empty($msgArr[0])) {
                    $userError = $this->getErrors('other', $msgArr[0]);
                    $responseCode = $msgArr[0];
                }
            }
            
            if (empty($userError) && !empty($message) && strpos(strtolower($message), "failed to load")) {
                $responseCode = 'INER1';
            }
        }

        $responseObj = array(
            'status'  => $status,
            'message' => $message,
            'data'    => $response
        );
        // if (empty($userError) && $status == 'error') {
        $responseObj['titleErrorMsg'] = '['.((!empty($responseCode)) ? $responseCode.': ' : '')."Your purchase could not be completed, Please review all of your information including card number, expiration date, security code and billing address. If you continue to have problems, please contact customer service.]";
        // }
        
        if(!$this->session->has('chaseUserErrorMsg')){
            $this->session->set('chaseUserErrorMsg', $userError);
        }
        $responseObj['userErrorMsg'] = $userError;
        return $responseObj;
    }

    public function refund($payment)
    {
        if ($payment['LastTransId'] && $payment['amount'] > 0) {
            $payment['chaseTransType'] = self::REQUEST_TYPE_CREDIT;
            $request                   = $this->_buildRequest($payment);
            $request->txRefNum         = $payment['LastTransId'];
            $response                  = $this->_postRequest($request);
            if ($response) {
              $payment['status']  = self::STATUS_APPROVED;
              $payment['LastTransId']  = $this->getTransactionId();
              $response = $this->getResponse($response['status'], $response['message'], $this);

            } else {
              $response = $this->getResponse('error', 'There has been an error processing your payment.');
          }
        }else{
          $response = $this->getResponse('error', 'Transaction Id not found.');
        }
        return $response;
    }

    private function getErrors($errorType, $errorCode){
        $errors = array();
        $errors['respCode'] = array(
            '64' => array(
                'control' => 'ChaseWpCvv',
                'message' => 'Please enter a valid CVV2 number'
            ),
            '68' => array(
                'control' => 'ChaseWpCardNumber',
                'message' => 'Please enter a valid credit card number'
            ),
            '14' => array(
                'control' => 'ChaseWpCardNumber',
                'message' => 'Please enter a valid credit card number'
            )
        );

        /*
            $errors['cvvRespCode'] = array(
                'N' => 'N CVV No match',
                'P' => 'P Not processed'
            );

            $errors['avsRespCode'] = array(
                '1' => '1 No address supplied'
            );

            $errors['procStatus'] = array(
                '20400' => 'Invalid Request. Please Try Again Later',
                '20403' => 'Forbidden: SSL Connection Required. Please Try Again Later',
                '20408' => 'Internal Server Error. Please Try Again Later',
                '20500' => 'Internal Server Error. Please Try Again Later',
                '20502' => 'Connection Error. Please Try Again Later',
                '20503' => 'Server Unavailable: Please Try Again Later',
                '20412' => 'Internal Server Error. Please Try Again Later'
            );
        */

        $errors['other'] = array(
            '818'   => array(
                'control' => 'ChaseWpCvv',
                'message' => 'Please enter a valid CVV2 number'
            ),
            '841'   => array(
                'control' => 'ChaseWpCardNumber',
                'message' => 'Please enter a valid credit card number'
            ),
            '839'   => array(
                'control' => 'ChaseWpCardNumber',
                'message' => 'Please enter a valid credit card number'
            )
        );

        if (!empty($errors[$errorType][$errorCode])) {
            return $errors[$errorType][$errorCode];
        } else {
            return;
        }
    }
}