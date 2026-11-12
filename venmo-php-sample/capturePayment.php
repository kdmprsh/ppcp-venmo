<?php
/*
* Captures an authorization, for example after the ticket is issued.
* Input (POST): authId = authorization id returned by executePayment.php
*/
include('config.php');
$access_token = getAccessToken();

$AUTHORIZATION_ID = isset($_POST['authId']) ? $_POST['authId'] : '';
if (empty($AUTHORIZATION_ID) || !preg_match('/^[A-Za-z0-9]+$/', $AUTHORIZATION_ID)) {
	http_response_code(400);
	echo "Invalid authId";
	exit;
}

// Capture the full authorized amount. To capture a lower amount add an "amount" object.
$postData = '{
  "final_capture": true
}';
$res = capturePayment($access_token, $postData, $AUTHORIZATION_ID);

echo"<pre>";var_dump($res);echo"</pre>";
?>
