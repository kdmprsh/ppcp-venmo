<?php
/*
* Called from onApprove in indexVenmo.php.
* AUTHORIZES the approved order (intent AUTHORIZE). Funds are captured later by capturePayment.php.
*/
include('config.php');
$access_token = getAccessToken();
$request_body = file_get_contents('php://input');

if(isset($_GET['orderId'])){
	$PAYMENT_ID = $_GET['orderId'];
}else{
	$data = json_decode($request_body);
	$PAYMENT_ID = $data->orderID; // JS SDK v5 onApprove data
}

header('Content-Type: application/json');
if (empty($PAYMENT_ID) || !preg_match('/^[A-Za-z0-9]+$/', $PAYMENT_ID)) {
	http_response_code(400);
	print json_encode(array("error" => "Invalid orderID"));
	exit;
}

$postData = '{

}';
$res = executePayment($access_token, $postData, $PAYMENT_ID);
print json_encode($res);
?>
