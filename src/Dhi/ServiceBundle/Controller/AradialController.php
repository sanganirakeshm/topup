<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AradialController extends Controller {

	protected $container;
	protected $em;
	protected $action;

	public function __construct($container) {

		$this->container = $container;
		$this->securitycontext = $container->get('security.context');
		$this->em = $container->get('doctrine')->getManager();
		$this->action = array(
				'createUser', //Create User
				'updateUser', //Update User
				'cancelUser', //Canceling a User
				'getUserList', //Get User List
				'getSingleUser', //Get Single User
				'postingBalance', //Posting a Balance Transaction to a User
				'getUserSession', //Get User Online Sessions
				'getUserSessionHistory', //Get User Session History
				'OfferPurchaseByCC', //Change a user Offer using a Credit Card
				'OfferPurchaseFromBalance', //Change User Offer using Balance
				'createAccount', //Create an Account
				'updateAccount', //Update an Account
				'deleteAccount', //Delete Account
				'getAccountList', //Get Account List
				'getOfferList', //Get Offer List
				'getInvoiceList', //Get Invoice List
				'getInvoiceCharges', //Get Invoice Charges
				'getInvoiceTaxes', //Get Invoice Taxes
				'postPaymentForInvoice', //Post Payment for an Invoice
				'createGroup', //Create a group
				'updateGroup', //Update Group
				'deleteGroup', //Delete a group
				'getGroupList', //Get Group List
				'getSingleGroup', //Get a Single Group
				'voucherSignup', //Voucher Signup
				'voucherRefill', //Voucher Refill
				'changeVoucherStatus', //Change Voucher Status
				'getVoucherDetails', //Get Voucher Details
		);
	}

	public function callWSAction($action, $param = array()) {
		$response = array();
		$response['serviceAvailable'] = '1';

		if ($param && $action && in_array($action, $this->action)) {

			$response = $this->sendWsRequest($action, $param);
		} else {

			$response['status'] = 0;
			$response['detail'] = 'Unable to find valid parameter.';
		}

		return $response;
	}

	/*
	 * Send cUrl request to aradial service
	 */

	public function sendWsRequest($action, $postParam) {

		$response = array();
		$response['serviceAvailable'] = '1';

		$aradialAPIUrl = $this->container->getParameter('aradial_api_url');
		$aradialAPIUsername = $this->container->getParameter('aradial_api_username');
		$aradialAPIPassword = $this->container->getParameter('aradial_api_pass');

		if ($aradialAPIUrl && $aradialAPIUsername && $aradialAPIPassword) {

			$postStr = http_build_query($postParam);

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $aradialAPIUrl);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $aradialAPIUsername . ":" . $aradialAPIPassword);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postStr);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

			// Get response from the server.
			$cUrlresponse = curl_exec($ch);
			$info = curl_getinfo($ch);

			if ($info['http_code'] != '404') {
				if (!$cUrlresponse) {

					$response['serviceAvailable'] = '0';
					$response['status'] = '0';
					$response['msg'] = '(' . curl_errno($ch) . ') ' . curl_error($ch);
				} else {
					//echo $cUrlresponse;exit;
					//$xml = new SimpleXMLElement($cUrlresponse);

					$xml = simplexml_load_string($cUrlresponse);
					$response = $this->parseXMLResponse($xml, $action);
					$response['serviceAvailable'] = '1';
				}
			} else {

				$response['serviceAvailable'] = '0';
				$response['status'] = 0;
				$response['detail'] = '404: Requested webservice url not found.';
			}
		} else {

			$response['serviceAvailable'] = '0';
			$response['status'] = 0;
			$response['detail'] = 'Unable to find api credential.';
		}

		return $response;
	}

	/*
	 * Used to create user in aradial
	 */
	public function createUser($user, $extraParam = array(), $isRemoveIspUser = 1, $isReCreate = false) {

		$params = array();
		$params['Page'] = "UserEdit";
		$params['Add'] = 1;
		$params['db_Users.UserID'] = $user->getUserName();
		$params['Password'] = trim(base64_decode($user->getEncryPwd()));
		$params['PasswordEncryptionType'] = 0;
		$params['db_$RS$Users.GroupName'] = "DHI";
		$params['db_$N$Users.UserService'] = 51;
		$params['db_$N$Users.ServiceType'] = 1;
		$params['db_UserDetails.FirstName'] = $user->getFirstName();
		$params['db_UserDetails.LastName'] = $user->getLastName();
		$params['db_UserDetails.Email'] = $user->getEmail();
		$params['db_$N$User.CurrencyId'] = 840;
		$params['db_$N$Users.PrepaidIndicator'] = 1;

		// Set extra params
		if (!empty($extraParam)) {
			foreach ($extraParam as $key => $val) {
				$params[$key] = $val;
			}
		}

		//Check user exits in aradial if extis first delete user
		$responseUserExits = $this->checkUserExistsInAradial($params['db_Users.UserID']);

		if ($responseUserExits['status'] == 1) {

			if($isReCreate == false){
				$this->get('packageActivation')->storeIspUser($params['db_Users.UserID']);
			}

			//Delete existing user from aradial
			$cancelUsrResponse = $this->deleteUserFromAradial($params['db_Users.UserID']);
			if($cancelUsrResponse == false){
				return false;
			}
			$logoutUsrResponse = $this->logoutUserFromAradial($user);
		}

		//Create New aradial user
		$newUsrResponse = $this->sendWsRequest('createUser', $params);
		if ($newUsrResponse['status'] == 1) {
			if($isRemoveIspUser == 0 && $responseUserExits['status'] == 1){
				$this->get('packageActivation')->deleteIspUser($user->getUserName());
			}
			return true;
		}else{
			if($responseUserExits['status'] == 1 && $isReCreate == false){
				$this->get('packageActivation')->reCreateIspUser($user->getUserName());
			}
		}


		return false;
	}

	public function createUserIsp($username, $password) {

		$flag = false;

		$params = array();
		$params['Page'] = "UserEdit";
		$params['Add'] = 1;
		$params['db_Users.UserID'] = $username;
		$params['Password'] = trim($password);
		$params['PasswordEncryptionType'] = 0;
		$params['db_$N$Users.UserService'] = 51;
		$params['db_$N$Users.ServiceType'] = 1;
		$params['db_$RS$Users.GroupName'] = "DHI";

		$newUsrResponse = $this->sendWsRequest('createUser', $params);

		if ($newUsrResponse['status'] == 1) {

			$flag = true;
		}

		return $flag;
	}

	public function updateUserIsp($user, $activationDate = '', $expiryDate) {

		$flag = false;
		$params = array();
		$params['Page']                       = "UserEdit";
		$params['Modify']                     = 1;
		$params['UserID']                     = $user->getUserName();
		$params['Password']                   = trim(base64_decode($user->getEncryPwd()));
		if(!empty($activationDate)){
			$params['db_$D$Users.StartDate']      = $activationDate->format('m/d/Y H:i:s');
		}
		$params['db_$D$Users.UserExpiryDate'] = $expiryDate->format('m/d/Y H:i:s');
		$response = $this->sendWsRequest('updateUser', $params);

		if ($response['status'] == 1) {
			$flag = true;
		}
		return $flag;
	}

	/*
	 * Check User exist in aradial
	 */

	public function checkUserExistsInAradial($userId) {

		//Get user if exist in aradial
		$singleUsrParam = array();
		$singleUsrParam['Page'] = 'UserEdit';
		$singleUsrParam['UserID'] = $userId;

		$signleUsrResponse = $this->sendWsRequest('getSingleUser', $singleUsrParam);

		return $signleUsrResponse;
	}

		public function getCurlResponse($singleUsrParam){
				$aradialAPIUrl      = $this->container->getParameter('aradial_api_url');
				$aradialAPIUsername = $this->container->getParameter('aradial_api_username');
				$aradialAPIPassword = $this->container->getParameter('aradial_api_pass');

				if($aradialAPIUrl && $aradialAPIUsername && $aradialAPIPassword){

						$postStr = http_build_query($singleUsrParam);
						$params = '';

						foreach($singleUsrParam as $key=>$value)
										$params .= $key.'='.$value.'&';

						$ch = curl_init();
						curl_setopt($ch, CURLOPT_URL, $aradialAPIUrl);
						curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
						curl_setopt($ch, CURLOPT_USERPWD, $aradialAPIUsername.":".$aradialAPIPassword);
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
						$response = curl_exec($ch);
						$info = curl_getinfo($ch);
						curl_close($ch);
						$xmlresponse = simplexml_load_string($response);
						return $xmlresponse;
				}
		}

		/*
		 * Check User exist in aradial
		 */
		public function checkUserAuthAradial($userId,$passw) {
				//Get user if exist in aradial
				$singleUsrParam = array();
				$singleUsrParam['Page']   = 'UserEdit';
				$singleUsrParam['UserID'] = $userId;
				$singleUsrParam['CheckPassword'] = $passw;

				$xml_verify = $this->getCurlResponse($singleUsrParam);

				// Checkout the response
				$checkAuth = array();
				if($xml_verify->Status['value'] == 'Success'){
						$checkAuth['result'] = true;

						//user information i.e. user status  & user offer
						$userInfo = $this->getUserInfo($userId);
						$checkAuth['status'] = $userInfo['status'];
						$checkAuth['offer'] = $userInfo['offer'];
						$checkAuth['PricePlanAssignDate'] = $userInfo['PricePlanAssignDate'];
						$checkAuth['FirstName'] = $userInfo['FirstName'];
						$checkAuth['LastName'] = $userInfo['LastName'];
				}else{
						$checkAuth['result'] = false;
				}
				return $checkAuth;
		}
		public function checkEmailAvailableAradial($email){
				$singleUsrParam = array();
				$singleUsrParam['Page']   = 'UserHit';
				$singleUsrParam['qdb_UserDetails.Email'] = $email;
				$xml_verify = $this->getCurlResponse($singleUsrParam);

				// Checkout the response
				$checkAuth = array();

				if($xml_verify->Status['value']){
						$checkAuth['result'] = false;
				}else{
						$checkAuth['result'] = true;
				}
				$checkAuth['responseArray'] = $xml_verify;
				return $checkAuth;
		}


		/*
		 * Check User exist in aradial
		 */
		public function getUserInformation($userId) {
				//Get user if exist in aradial
				$singleUsrParam = array();
				$singleUsrParam['Page']   = 'UserEdit';
				$singleUsrParam['UserID'] = $userId;

				$xml_verify = $this->getCurlResponse($singleUsrParam);

				// Checkout the response
				$checkAuth = array();
				if($xml_verify->Status['value'] == 'Success'){
						$checkAuth['result'] = true;

						//user information i.e. user status  & user offer
						$userInfo = $this->getUserInfo($userId);
			$checkAuth['status'] = $userInfo['status'];
						$checkAuth['offer'] = $userInfo['offer'];
						$checkAuth['PricePlanAssignDate'] = $userInfo['PricePlanAssignDate'];
						$checkAuth['FirstName'] = $userInfo['FirstName'];
						$checkAuth['LastName'] = $userInfo['LastName'];
						$checkAuth['PlanActivationDate'] = $userInfo['PlanActivationDate'];
						$checkAuth['PlanExpirationDate'] = $userInfo['PlanExpirationDate'];
						$checkAuth['UserExpirationDate'] = $userInfo['PlanExpirationDate'];

				}else{
						$checkAuth['result'] = false;
				}
				return $checkAuth;
		}

		public function getUserInfo($userId){
				// checkout user status
				$singleUsrParam['Page']   = 'UserHit';
				$singleUsrParam['qdb_Users.UserID'] = $userId;
				$xml_verify = $this->getCurlResponse($singleUsrParam);

				$data['PlanActivationDate'] = $xml_verify->TR->TD[14];
				$data['PlanExpirationDate'] = $xml_verify->TR->TD[15];
		$data['status'] = $xml_verify->TR->TD[16];
		$data['offer'] = $xml_verify->TR->TD[7];
				$data['PricePlanAssignDate'] = $xml_verify->TR->TD[39];
				$data['FirstName'] = $xml_verify->TR->TD[46];
				$data['LastName'] = $xml_verify->TR->TD[47];

				return $data;
		}

	/*
	 * Delete user from aradial
	 */

	public function deleteUserFromAradial($userId) {

		// Delete existing user from aradial
		$delUserParam = array();
		$delUserParam['Page'] = 'UserEdit';
		$delUserParam['UserId'] = $userId;
		$delUserParam['ConfirmDelete'] = 1;

		$cancelUserResponse = $this->sendWsRequest('cancelUser', $delUserParam);

		if ($cancelUserResponse['status'] == 1) {

			return $cancelUserResponse;
		}

		return false;
	}

	/*
	 * Logout user from aradial
	 */

	public function logoutUserFromAradial($user) {

		// Logout existing user from aradial
		$wsParam = array();
		$wsParam['Page']        = 'RadUserRequest';
		$wsParam['Rad_ReqCode'] = '40';
		$wsParam['UserID']      = $user->getUserName();

		$logoutUserResponse = $this->sendWsRequest('logoutUser', $wsParam);

		if ($logoutUserResponse['status'] == 1) {
			return $logoutUserResponse;
		}

		return false;
	}

	/*
	 * Get package from aradial
	 */
	public function getOffer() {

		// Get offer params
		$params = array();
		$params['Page'] = "OfferHit";
		$params['OnePage'] = '1';

		$offerResponse = $this->sendWsRequest('getOfferList', $params);

		if ($offerResponse['status'] == 1) {

			return $offerResponse;
		}

		return false;
	}

	/*
	 * Parse aradial XML response to array
	 */

	public function parseXMLResponse($response, $action) {

		$responseArray = array();
		$responseArray['status'] = 0;
		if ($response) {

			if ($response->getName() == 'Result') {

				foreach ($response as $key => $val) {

					if ($val->attributes()) {

						foreach ($val->attributes() as $attrKey => $attrVal) {

							$value = (string) $attrVal;
							if ($value == 'Success') {

								$value = 1;
							} else if ($value == 'Error') {

								$value = 0;
							}

							$responseArray[strtolower($key)] = $value;
						}
					}
				}
			}

			if ($response->getName() == 'User') {

				$responseArray['status'] = 1;
			}

			if ($response->getName() == 'Offers' && $action = 'getOfferList') {

				if ($response->TR) {

					$i = 0;
					$responseArray['package'] = array();
					$responseArray['status'] = 1;
					foreach ($response->TR as $tr) {

						foreach ($tr->TD as $td) {

							//echo "{$td['fieldName']} : $td<br>";
							$fieldName = (string) $td['fieldName'];
							$value = (string) $td;

							$tempArray = array();
							$responseArray['package'][$i][$fieldName] = $value;
						}
						$i++;
					}
				}
			}

			if ($response->getName() == 'UserSessions') {

				if ($response->TR) {

					$i = 0;
					$responseArray['userSession'] = array();
					$responseArray['status'] = 1;
					foreach ($response->TR as $tr) {

						foreach ($tr->TD as $td) {

							//echo "{$td['fieldName']} : $td<br>";
							$fieldName = (string) $td['fieldName'];
							$value = (string) $td;

							$tempArray = array();
							$responseArray['userSession'][$i][$fieldName] = trim($value);
						}
						$i++;
					}
				}
			}
			if ($response->getName() == 'Sessions') {

				if ($response->TR) {

					$i = 0;
					$responseArray['userOnline'] = array();
					$responseArray['status'] = 1;
					foreach ($response->TR as $tr) {

						foreach ($tr->TD as $td) {

							//echo "{$td['fieldName']} : $td<br>";
							$fieldName = (string) $td['fieldName'];
							$value = (string) $td;

							$tempArray = array();
							$responseArray['userOnline'][$i][$fieldName] = trim($value);
						}
						$i++;
					}
				}
			}
			if ($response->getName() == 'SessionDelete') {

				$responseArray['status'] = 1;
			}

			if ($response->getName() == 'Users') {

				if ($response->TR) {

					$i = 0;
					$responseArray['userList'] = array();
					$responseArray['status'] = 1;
					foreach ($response->TR as $tr) {

						foreach ($tr->TD as $td) {

							//echo "{$td['fieldName']} : $td<br>";
							$fieldName = (string) $td['fieldName'];
							$value = (string) $td;

							$tempArray = array();
							$responseArray['userList'][$i][$fieldName] = trim($value);
						}
						$i++;
					}
				}
			}
		}

		return $responseArray;
	}

	############################ XML to array convert function #######################################################
	##################################################################################################################

	function xmlstr_to_array($xmlstr) {

		$doc = new DOMDocument();
		$doc->loadXML($xmlstr);
		$root = $doc->documentElement;
		$output = domnode_to_array($root);
		$output['@root'] = $root->tagName;
		return $output;
	}

	function domnode_to_array($node) {
		$output = array();
		switch ($node->nodeType) {
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				$output = trim($node->textContent);
				break;
			case XML_ELEMENT_NODE:
				for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++) {
					$child = $node->childNodes->item($i);
					$v = domnode_to_array($child);
					if (isset($child->tagName)) {
						$t = $child->tagName;
						if (!isset($output[$t])) {
							$output[$t] = array();
						}
						$output[$t][] = $v;
					} elseif ($v || $v === '0') {
						$output = (string) $v;
					}
				}
				if ($node->attributes->length && !is_array($output)) { //Has attributes but isn't an array
					$output = array('@content' => $output); //Change output into an array.
				}
				if (is_array($output)) {
					if ($node->attributes->length) {
						$a = array();
						foreach ($node->attributes as $attrName => $attrNode) {
							$a[$attrName] = (string) $attrNode->value;
						}
						$output['@attributes'] = $a;
					}
					foreach ($output as $t => $v) {
						if (is_array($v) && count($v) == 1 && $t != '@attributes') {
							$output[$t] = $v[0];
						}
					}
				}
				break;
		}
		return $output;
	}

	public function getUserBalance($username) {
		$flag = false;
		$params = array();
		$params['Page'] = "UserEdit";
		$params['UserID'] = $username;

		$aradialAPIUrl = $this->container->getParameter('aradial_api_url');
		$aradialAPIUsername = $this->container->getParameter('aradial_api_username');
		$aradialAPIPassword = $this->container->getParameter('aradial_api_pass');
		$postStr = '';
		foreach ($params as $key => $value) {
			$postStr .= $key . '=' . $value . '&';
		}
		rtrim($postStr, '&');

		$xml_verify = $this->getCurlResponse($params);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $aradialAPIUrl . '?' . $postStr);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $aradialAPIUsername . ":" . $aradialAPIPassword);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec($ch);
		$info = curl_getinfo($ch);
		curl_close($ch);
		$xml = simplexml_load_string($response);
		if($info['http_code'] != '404' && isset($xml->TD[28])){
			return array("status"=>"success", "balance" => (string)$xml->TD[28]);
		}else{
			return array("status"=>"fail", "balance" => null);
		}
	}

	private function getCurlUrlResponse($singleUsrParam){
		$aradialAPIUrl      = $this->container->getParameter('aradial_api_url');
		$aradialAPIUsername = $this->container->getParameter('aradial_api_username');
		$aradialAPIPassword = $this->container->getParameter('aradial_api_pass');

		if($aradialAPIUrl && $aradialAPIUsername && $aradialAPIPassword){

			$postStr = http_build_query($singleUsrParam);
			$params  = '';
			foreach($singleUsrParam as $key=>$value){
				$params .= $key.'='.$value.'&';
			}

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $aradialAPIUrl.'?'.$params);
			curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($ch, CURLOPT_USERPWD, $aradialAPIUsername.":".$aradialAPIPassword);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$response    = curl_exec($ch);
			$info        = curl_getinfo($ch);
			curl_close($ch);
			if ($info['http_code'] != '404') {
				if (!empty($response)) {
					return $response;
				}
			}
			return null;
		}
		return null;
	}

	public function getUserDetails($userId){
		$singleUsrParam['Page']   = 'UserEdit';
		$singleUsrParam['UserID'] = urlencode($userId);
		$ispResponse              = $this->getCurlUrlResponse($singleUsrParam);

		if ($ispResponse) {
			$xml = simplexml_load_string($ispResponse);

			$response = array();
			$response['activationDate'] = trim((string)$xml->TD[14]);
			$response['expiryDate'] = trim((string)$xml->TD[15]);
			return $response;
		}else{
			return null;
		}
	}
}
