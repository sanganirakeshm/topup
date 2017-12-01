<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Config\Definition\Exception\Exception;


class MilstarController extends Controller
{
    protected $container;
    protected $em;
    protected $session;
    protected $securitycontext;
    
    protected $milstar_region;
    protected $milstar_wsdl;
    
    public function __construct($container) {
        
        $this->container = $container;
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->milstar_region    = ''; //$this->container->getParameter('milstar_region');
        $this->milstar_wsdl      = $this->container->getParameter('milstar_wsdl');
        $this->PaymentProcess = $container->get('PaymentProcess');
    }
    
    
    // Milstar for approval
    public function processMilstarApproval($milsatParams) {
        
        $trancationStatus = false;
        $milsatParams['region'] = $this->milstar_region;
        
        $requestId        = $milsatParams['requestId'];
        $creditCardNumber = $milsatParams['creditCardNumber'];
        $amount           = (int) $milsatParams['amount'];
        // $processingFacnbr = $milsatParams['processingFacnbr'];
        $zipCode          = $milsatParams['zipCode'];
        $cid              = $milsatParams['cid'];
        $uid              = "";
        
        
        // Construct the XML string that will be sent to MilStar	
        $msApproval = <<<XML
            <cm:Message xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xmlns:cm='http://www.aafes.com/credit' xsi:schemaLocation='http://www.aafes.com/credit file:/home/CreditMessage11.xsd' TypeCode="Request" MajorVersion="1" MinorVersion="1" FixVersion="0">
                <cm:Header>
                    <cm:IdentityUUID>$uid</cm:IdentityUUID> 
                    <cm:LocalDateTime>date("Y-m-dTH:i:s")</cm:LocalDateTime> 
                    <cm:SettleIndicator>true</cm:SettleIndicator> 
                    <cm:OrderNumber>$requestId</cm:OrderNumber>
                    <cm:CustomerID>$cid</cm:CustomerID> 
                </cm:Header>
                <cm:Request RRN="YTprYZ65v8Ij">
                    <cm:Media>MilStar</cm:Media> 
                    <cm:RequestType>Sale</cm:RequestType> 
                    <cm:    > 
                    <cm:PrimaryAccountNumber>$creditCardNumber</cm:PrimaryAccountNumber> 
                    <cm:AmountField>$amount</cm:AmountField> 
                    <cm:PlanNumbers>
                        <cm:PlanNumber>10001</cm:PlanNumber> 
                    </cm:PlanNumbers>
                    <cm:ZipCode>$zipCode</cm:ZipCode>
                    <cm:DescriptionField>SALE</cm:DescriptionField> 
                </cm:Request>
            </cm:Message>
XML;
        
        try {
            //A SoapFault exception will be thrown if the wsdl URI cannot be loaded.
    	    $client = new \SoapClient($this->milstar_wsdl, array('trace' => 1,'exceptions' => 1));				
    
    		$responseXML = $client->MSApproval(array(
    					"inXMLApproval" => html_entity_decode($msApproval)
    			));	
    		}
        catch (SoapFault $e) {
    	    
    	    $milsatParams['failCode']	 = "WC";
    		$milsatParams['failMessage'] = $e->getMessage();    		
    		
    		$this->PaymentProcess->storeMilstarResponse($milsatParams);
        }
    
    
        $responseObject = simplexml_load_string($responseXML->MSApprovalResult);
        
        /*
         *  ReturnCode values
        *  "A" - Approved,  ReturnMessage will be NULL
        *  "D" - Denied, ReturnMessage will have applicable error message
        *  "X" - Error,  ReturnMessage will have applicable error message
        *  "E" - Validation Error, ReturnMessage will have applicable error message
        */
        $milsatParams['processStatus'] = 'MSApproval';
        switch($responseObject->Response->ResponseType)	{
            case "ResponseType":
                // if($this->processMilstarSettle($responseObject,$milsatParams)){
                    $trancationStatus['authCode']   = $responseObject->Response->AuthNumber;
                    $trancationStatus['authTicket'] = ''; //$responseObject->Response->AuthTkt;
                // }
                break;
            case "Declined":
                $this->processMilstarResponse($responseObject,$milsatParams);
                break;
            case "TIMEOUT":
                $this->processMilstarResponse($responseObject,$milsatParams);
                break;
            default:
                throw new Exception("Error Invalid ReturnCode");
        }      

        return $trancationStatus;
    }

    // milstar for credit
    public function processMilstarCredit($milsatParams) {
        
        $trancation = false;
        
        $requestId        = $milsatParams['requestId'];
        $creditCardNumber = $milsatParams['creditCardNumber'];
        $amount           = (int) $milsatParams['amount'];
        // $processingFacnbr = $milsatParams['processingFacnbr'];
        $authCode         = (string) $milsatParams['authCode'];
        // $authTkt       = (string) $milsatParams['authTicket'];
        $uid              = '';
        $customerID       = $milsatParams['cid'];
        $zipCode          = $milsatParams['zipCode'];
        // $milsatParams['processStatus'] = 'MSCredit';
        // $milsatParams['region']        = $this->milstar_region;

        $msCreditXml = <<<XML
            <cm:Message xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xmlns:cm='http://www.aafes.com/credit' xsi:schemaLocation='http://www.aafes.com/credit file:/home/CreditMessage11.xsd' TypeCode="Request" MajorVersion="1" MinorVersion="1" FixVersion="0">
                <cm:Header>
                    <cm:IdentityUUID>$uid</cm:IdentityUUID>
                    <cm:LocalDateTime>date("Y-m-dTH:i:s")</cm:LocalDateTime>
                    <cm:SettleIndicator>true</cm:SettleIndicator>
                    <cm:OrderNumber>$requestId</cm:OrderNumber>
                    <cm:Comment>Refund</cm:Comment>
                    <cm:CustomerID>$customerID</cm:CustomerID>
                </cm:Header>
                <cm:Request RRN="BOC+kI0Gp8wm">
                    <cm:Media>MilStar</cm:Media>
                    <cm:RequestType>Refund</cm:RequestType>
                    <cm:InputType>Keyed</cm:InputType>
                    <cm:PrimaryAccountNumber>$creditCardNumber</cm:PrimaryAccountNumber>
                    <cm:AmountField>-$amount</cm:AmountField>
                    <cm:PlanNumbers>
                        <cm:PlanNumber>10001</cm:PlanNumber>
                    </cm:PlanNumbers> 
                    <cm:OriginalOrder>$requestId</cm:OriginalOrder>
                    <cm:ZipCode>$zipCode</cm:ZipCode>
                    <cm:DescriptionField>REFUND</cm:DescriptionField>
                </cm:Request>
            </cm:Message>
XML;
            
        try {
            $client = new \SoapClient($this->milstar_wsdl, array('trace' => 1,'exceptions' => 1));
        
            $responseXML = $client->MSCredit(array(
                    "inXMLCredit" => html_entity_decode($msCreditXml)
            ));
        } catch (SoapFault $e) {
            
            $milsatParams['requestId']         = $requestID;
            $milsatParams['amount']            = $amount;
            $milsatParams['processingFacnbr']  = ''; //$processingFacnbr;
            $milsatParams['region']            = $region;
            $milsatParams['failCode']	       = "WC";
            $milsatParams['failMessage']       = $e->getMessage();
           
            
            $this->PaymentProcess->processMilstarResponse($milsatParams);
        }
        
        
        $responseObject = simplexml_load_string($responseXML->MSCreditResult);
        
        /*
		*  ReturnCode values
		*  "A" - Approved,  ReturnMessage will be NULL
		*  "D" - Denied, 	ReturnMessage will have applicable error message
		*  "X" - Error, 	ReturnMessage will have applicable error message
		*  "E" - Validation Error, ReturnMessage will have applicable error message 
		*/	
		switch($responseObject->Response->ResponseType)	{
		    
			case "Approved":			    
			    $milsatParams['failCode']    = NULL;
			    $milsatParams['failMessage'] = NULL;
			    $this->processMilstarResponse($responseObject,$milsatParams);
				$trancation = true;
				break;
			default:
				$this->processMilstarResponse($responseObject,$milsatParams);
		}
		
		return $trancation;
    }  
    
    private function processMilstarResponse($responseObject,$milsatParams)
    {        
        //data is now in the xml request part of the response
        $returnCode 	 = $responseObject->Response->ReasonCode;
        $returnMessage   = ''; //$responseObject->Response->ReturnMessage;
        
        
        $milsatParams['failCode']     = $returnCode;
        $milsatParams['failMessage']  = $returnMessage;
        $milsatParams['responseCode'] = $returnCode;
        $milsatParams['message'] = '';
        
        switch($returnCode)	{
            case "Declined":
                $milsatParams['message'] = 'Declined, Please try another payment method or contact support with the following info: ';
                break;
            case "TIMEOUT":
                $milsatParams['message'] = 'Error, Please try another payment method or contact support with the following info: ';
                break;
        }
        
        $this->PaymentProcess->storeMilstarResponse($milsatParams);
    }    
}
