<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\User;
class TestController extends Controller {

	public function selevisionWsAction() {
		$em = $this->getDoctrine()->getManager();
		//Call Selevision webservice for unset package
	$wsOfferParam = array();
	$wsOfferParam['cuLogin'] = 'niravjames';
	$wsOfferParam['offer'] = 10012;

	$selevisionService = $this->get('selevisionService');
	$wsResponse = $selevisionService->callWSAction('unsetCustomerOffer', $wsOfferParam);
		print_r($wsResponse);
		exit;

		//$data = array();

//		$Param = array();
//		$Param['Page'] = 'UserHit';
//		$Param['qdb_Users.UserID']='afns-ten-huumm';

		$aradial = $this->get('aradial');
		$wsResponse1 = $aradial->callWSAction('getUserList', $Param);

		if (!empty($wsResponse['userSession'])) {

			foreach ($wsResponse['userSession'] as $user) {
				$userName = $user['UserID'];

				$Param = array();
				$Param['Page'] = 'UserHit';
				$Param['qdb_Users.UserID']= $userName;

				$wsResponse1 = $aradial->callWSAction('getUserList', $Param);
				if ($wsResponse1['status'] == 1) {
//					foreach ($wsResponse1['userList'] as $wsResponse1) {
//						if ($wsResponse1['Users.UserID'] == $userName) {
//							$email = $wsResponse1['UserDetails.Email'];
//						}
//					}
					$email = $wsResponse1['userList'][0]['UserDetails.Email'];
				}

			}
		}
		//$data =
		if ($wsResponse1['status'] == 1) {
//					foreach ($wsResponse1['userList'] as $wsResponse1) {
//						if ($wsResponse1['Users.UserID'] == $userName) {
//							$email = $wsResponse1['UserDetails.Email'];
//						}
//					}
					$email = $wsResponse1['userList'][0]['UserDetails.Email'];
				}
		echo "<pre>";
		print_r($wsResponse1['status']);
		exit;
		foreach($wsResponse['userSession'] as $response){
			//echo $response['UserID'].'<br/>';
			
		}
		$data =	$em->getRepository('DhiUserBundle:User')->getEmailForAradialUser('kd1234568');
		echo "<pre>";
		print_r($data);exit;
		$data =	$em->getRepository('DhiServiceBundle:PurchaseOrder')->getAradialCustomerSessionHistoryQuery();
		if (!empty($data)) {
			foreach ($data as $userData) {
				echo "<pre>";
				print_r($userData);
			}
		}
		echo "<pre>";
		print_r($data);
		exit;
	}

	
	

}
