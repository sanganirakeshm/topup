<?php
	/**
	* Selevision Call
	* getCustomerDetails.php (Not used in Portal)
	*/
// cuLogin=mahesh002

	$response                      = array();
	$response["customerLogin"]     = (!empty($_GET['cuLogin']) ? $_GET['cuLogin'] : '');
	$response["customerName"]      = "Nironi";
	$response["customerFirstname"] = "Mahesh";
	$response["customerEmail"]     = "mahesh001@barinvire.com";
	$response["customerCredit"]    = "0";
	$response["customerStatus"]    = "active";

	echo json_encode($response);
	exit;
?>