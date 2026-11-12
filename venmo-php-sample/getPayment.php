<?php
include('config.php');
$access_token = getAccessToken();

$PAYMENT_ID = $_REQUEST['orderId'];

$res = getPaymentDetails($access_token, $PAYMENT_ID);

header('Content-Type: application/json');
print json_encode($res);
?>
