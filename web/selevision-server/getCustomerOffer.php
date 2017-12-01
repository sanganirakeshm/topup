<?php
	/**
	* Selevision Call
	* getCustomerOffer.php
	*/
	
	require_once 'database.php';
	$response = array();

	if (empty($_GET['adLogin']) || empty($_GET['adPwd'])) {
		$response["status"] = "fail";
		$response["detail"] = "login does not exist";

	}else{

		$db = new Database($_GET);
		if ($db->isError == false && !empty($_GET['cuLogin'])) {
			$sql = "SELECT us.package_id FROM user_services us INNER JOIN dhi_user u ON us.user_id = u.id WHERE u.username = :username AND us.status = 1";
			$res = $db->execute($sql, array('username' => $_GET['cuLogin']));

			if (!empty($res)) {
				foreach ($res as $key => $record) {
					$response['data'][]['offerId']   = $record['package_id'];
				}
			}
			$response["status"] = "success";

		}else{
			$response["status"] = "fail";
			$response["detail"] = "login does not exist";
		}
	}

	echo json_encode($response);
	exit;
?>