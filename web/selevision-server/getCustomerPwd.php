<?php
	/**
	* Selevision Call
	* getCustomerPwd.php
	*/

	require_once 'database.php';
	$response = array();

	if (empty($_GET['adLogin']) || empty($_GET['adPwd'])) {
		$response["status"] = "fail";
		$response["detail"] = "login does not exist";

	}else{
		$db = new Database($_GET);

		if ($db->isError == false) {

			if (!empty($_GET['cuLogin'])) {
			   	$sql = "SELECT u.encry_pwd from dhi_user u WHERE u.username = :username";
			   	$res = $db->execute($sql, array('username' => $_GET['cuLogin']));

			   	if (!empty($res[0]['encry_pwd'])) {
					$response['password'] = base64_decode($res[0]['encry_pwd']);
					$response['status']   = 'success';
				}else{
					$response["status"] = "fail";
					$response["detail"] = "login does not exist";
				}
			}else{
				$response["status"] = "fail";
				$response["detail"] = "login does not exist";
			}
		}else{
			$response["status"] = "fail";
			$response["detail"] = "login does not exist";
		}
	}

	echo json_encode($response);
	exit;
?>