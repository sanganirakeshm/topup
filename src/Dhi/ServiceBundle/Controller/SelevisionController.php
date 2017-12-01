<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SelevisionController extends Controller
{
    protected $container;
    protected $em;


    public function __construct($container) {
    
        $this->container = $container;
        $this->securitycontext   = $container->get('security.context');
        $this->em                = $container->get('doctrine')->getManager();
        
    }
    
    public function callWSAction($action,$param = array())
    {
        $response = array();
        $response['serviceAvailable'] = '1';

        if($action){

            switch ($action) {

                //Create a new operator
                case 'createOperator':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Update an existing operator
                case 'updateOperator':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Update operator password
                case 'updateOperatorPwd':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Give credit to customer's wallet
                case 'giveOperatorCredit':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get customer history purchase
                case 'getCustomerPurchases':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Create a customer
                case 'createCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Change a customer's password
                case 'changeCustomerPwd':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Disable a customer
                case 'deactivateCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Reactivate a customer (after doing Disable a customer)
                case 'reactivateCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get customer password
                case 'getCustomerPwd':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Update a customer
                case 'updateCustomer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Give channel package to customer
                case 'setCustomerOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Remove channel package from a customer
                case 'unsetCustomerOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get channel packages from a customer
                case 'getCustomerOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                //Get all channel packages
                case 'getAllOffers':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                    
                case 'getChannelsFromOffer':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'registerMac':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'giveCustomerBonusTime':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'customerMac':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'giveCustomerCredit':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                case 'getCustomerDetails':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                case 'getCustomerSessionLogs':
                    $response = $this->sendWsRequest($action,$param);
                    break;

                case 'tvodPurchase':
                    $response = $this->sendWsRequest($action,$param);
                    break;
                
                default:
                    $response['status'] = 0;
                    $response['detail'] = 'Selevision action mismatch';
            }

        }else{

            $response['status'] = 0;
            $response['detail'] = 'Unable to find valid parameter.';
        }
        
        return $response;
    }

    public function sendWsRequest($action,$postParam){

        $response = array();
        $response['serviceAvailable'] = '1';

        $API_URL                 = $this->container->getParameter('selevision_api_url');
        $Selevision_adm_username = $this->container->getParameter('selevision_admin_username');
        $Selevision_adm_pass     = $this->container->getParameter('selevision_admin_pass');

        if($API_URL && $Selevision_adm_username && $Selevision_adm_pass){

            $postParam['adLogin'] = $Selevision_adm_username;
            $postParam['adPwd']   = $Selevision_adm_pass;
            
            $postStr = http_build_query($postParam);
            
            // Set the curl parameters.
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $API_URL.$action.'.php?'.$postStr);
            // curl_setopt($ch, CURLOPT_VERBOSE, 1);

            // Turn off the server and peer verification (TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // Get response from the server.
            $httpResponse = curl_exec($ch);
            
            $info = curl_getinfo($ch);
            
            
            if($info['http_code'] != '404'){
                    
                if(!$httpResponse) {
                    
                    $response['serviceAvailable'] = '0';
                    $response['status'] = '0';
                    $response['msg']    = '('.curl_errno($ch).') '.curl_error($ch);                
                }else{
                    
                    $httpResponse = json_decode($httpResponse, true);
                    
                    if(count($httpResponse) > 0){
                        
                        if(isset($httpResponse['status']) && !empty($httpResponse['status'])){
                            
                            $httpResponse['status'] = ($httpResponse['status'] == 'fail')?0:1;
                        }
                        
                        if($action == 'getAllOffers' || $action == 'getChannelsFromOffer' || $action == 'getCustomerOffer'){
                            
                            if(count($httpResponse) == 2 && array_key_exists('status', $httpResponse)){
                                
                                $response = $httpResponse;
                                $response['serviceAvailable'] = '1';
                            }else{
                                
                                $response['data'] = $httpResponse;
                                $response['status'] = 1;
                            }                            
                            
                        }else{
                            
                            $response = $httpResponse;
                            $response['serviceAvailable'] = '1';
                        }                
                    }else{
                        
                        $response['status'] = 0;
                        $response['detail'] = 'Data not found';
                    }
                    
                }            
            }else{
                
                $response['serviceAvailable'] = '0';
                $response['status'] = 0;
                $response['detail']    = '404: Requested webservice url not found.';
            }
        }else{
            
            $response['serviceAvailable'] = '0';
            $response['status'] = 0;
            $response['detail']    = 'Unable to find api credential.';
        }

        return $response;
    }
    
    public function getAllPackageDetails() {
        
        $packages = $this->callWSAction('getAllOffers', array());       
        $packageArr = array();
        
        if(!empty($packages['data'])){
            
            foreach($packages['data'] as $package) {
    
                $packageArr[$package['offerName']]['packageId'] = $package['offerId'];
                $packageArr[$package['offerName']]['packageName'] = $package['offerName'];
                $packageArr[$package['offerName']]['packagePrice'] = $package['offerPrice'];
                $packageArr[$package['offerName']]['bandwidth'] = 10;
                $packageArr[$package['offerName']]['validity'] = 30;
                
                $wsParam = array();
                $wsParam['offer'] = $package['offerId'];
                $wsRespose = $this->callWSAction('getChannelsFromOffer', $wsParam);
                
                $channelList = array();

                if (!empty($wsRespose)) {

                    if (array_key_exists('data', $wsRespose)) {
                        $channelList = $wsRespose['data'];
                    }
                }
                
                $packageArr[$package['offerName']]['packageChannels'] = $channelList; 
                $packageArr[$package['offerName']]['packageChannelCount'] = count($channelList);
                
            }
           
        }
       
        return $packageArr;
    }
    
    public function checkUserExistInSelevision($user) {
        
        // check selevision api to check whether customer exist in system
        if (is_object($user) && $user->getUsername()) {
            $wsParam            = array();
            $wsParam['cuLogin'] = $user->getUsername();

            $selevisionService = $this->get('selevisionService');
            $wsResponse = $selevisionService->callWSAction('getCustomerPwd', $wsParam);
            return $wsResponse;
        }else{
            return array('status' => 0, 'serviceAvailable' => 0);
        }
    }
    
    public function createNewUser($user) {
        
        $flag = false;
        
        $responseUserExits = $this->checkUserExistInSelevision($user);
        
        if (!$responseUserExits['status']) {
        
            $wsAddCustParam = array();
            $wsAddCustParam['cuFirstName']  = $user->getFirstName();
            $wsAddCustParam['cuLastName']   = $user->getLastName();
            $wsAddCustParam['cuEmail']      = $user->getEmail();
            $wsAddCustParam['cuLogin']      = $user->getUserName();
            $wsAddCustParam['cuPwd']        = base64_decode($user->getEncryPwd());
        
            $selevisionService = $this->get('selevisionService');
            $wsRes = $selevisionService->callWSAction('createCustomer',$wsAddCustParam);
            
            if($wsRes['status'] == 1){

                //Update New user flag
                $user->setNewSelevisionUser(1);
                $this->em->persist($user);
                $this->em->flush();
                
                $flag = true;
            }else{
                
                $flag = false;
            }
        }else{
            
            $flag = true;
        }
        //$flag = true;
        return $flag;        
    }
    
    public function getActivePackageIds($username){
    	
    	//Call Selevision webservice for unset package
    	$wsParam = array();
    	$wsParam['cuLogin'] = $username;
    	
    	$wsRes = $this->callWSAction('getCustomerOffer',$wsParam);
    	//echo "<pre>";print_r($wsRes);exit;
    	$activePackageIds = array();
    	if($wsRes['status']){
    		
    		if(count($wsRes['data']) > 0){
    			
    			foreach ($wsRes['data'] as $activePackage){
    				
    				$activePackageIds[] = $activePackage['offerId'];
    			}    			    			
    		}
    	}    	
    	return $activePackageIds;    	
    }
    
   public function userLoginSelevision($username) {
       
        $selevisionService = $this->get('selevisionService');
                            
        $wsGetUserLoginParam['action']  = 'get';
        $wsGetUserLoginParam['cuLogin'] = $username;

        $wsResponseUserLogin= $selevisionService->callWSAction('customerMac', $wsGetUserLoginParam);

        return $wsResponseUserLogin;
      
   }
   
   
   public function registerMacAddressSelevision($macAddress,$serialNumber) {
       
        $selevisionService = $this->get('selevisionService');
                            
        $wsParam['mac'] = $macAddress;
        $wsParam['serial'] = $serialNumber;

        $wsResponse = $selevisionService->callWSAction('registerMac', $wsParam);
        
        return $wsResponse;
      
   }
   
    public function checkSelevisionResponse($response = array() ) {
        
        if(!empty($response)) {
            
            if(isset($response['status'])){
                
                if($response['status'] != '1') {

                    if(isset($response['detail'])) {
                        
                        if($response['detail'] == "unable to query") {
                            
                            $response['detail'] = 'Something went wrong with register mac address. Please contact support if the issue persists.';
                        }                        
                    }else{
                        
                        $response['detail'] = 'Something went wrong with register mac address. Please contact support if the issue persists.';
                    }
                    
                    $response['status'] = 'failure';                    
                    echo json_encode($response);
                    exit;
                }
            }
        }        
    }
    
    public function setMacAddressSelevision($macAddress,$serialNumber, $action, $username, $mac_address_seqno) {
        
        $selevisionService = $this->get('selevisionService');
        
        $wsSetMacAddressParam['mac']     = $macAddress;
        $wsSetMacAddressParam['serial']  = $serialNumber;
        $wsSetMacAddressParam['action']  = 'set';
        $wsSetMacAddressParam['cuLogin'] = $username;
        $wsSetMacAddressParam['cuDevice']= $mac_address_seqno == 0 ? "mac" : "extra".$mac_address_seqno; 
        
        $wsResponseSetMacAddress = $selevisionService->callWSAction('customerMac', $wsSetMacAddressParam);
        
        return $wsResponseSetMacAddress;
        
    }
    
    
     //get mac-address sequence number of user
    public function getMacAddressSequenceNumber($user) {
        
        $sequence_number = 0;
        
        $objMacAddress   = $this->em->getRepository('DhiUserBundle:UserMacAddress')->findBy(array('user' => $user), array('id' => 'asc'));
        
        if( $objMacAddress ) {
            $sequence_number =  count($objMacAddress);
        }
        return $sequence_number;
    }
    
    
    public function unsetMacAddressSelevision($macAddress, $action, $username, $mac_address_seqno) {
        
        $selevisionService = $this->get('selevisionService');
        
        $wsUnsetMacAddressParam['mac'] = $macAddress;
        $wsUnsetMacAddressParam['action'] = 'unset';
        $wsUnsetMacAddressParam['cuLogin'] = $username;
        $wsUnsetMacAddressParam['cuDevice']= $mac_address_seqno == 0 ? "mac" : "extra".$mac_address_seqno; 
        
        $wsResponseUnsetMacAddress = $selevisionService->callWSAction('customerMac', $wsUnsetMacAddressParam);

        return $wsResponseUnsetMacAddress;
        
        
    }
   
}
