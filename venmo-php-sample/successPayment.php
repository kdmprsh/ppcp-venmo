<?php
include('config.php');
$paymentID = $_GET['id'];
$access_token = getAccessToken();
$res = getPaymentDetails($access_token, $paymentID);

// authorization id is needed to capture the payment
$authId = "";
if (isset($res['json']['purchase_units'][0]['payments']['authorizations'][0]['id'])) {
	$authId = $res['json']['purchase_units'][0]['payments']['authorizations'][0]['id'];
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"/><title>Venmo authorization</title></head>
<body>
<h3>Venmo payment authorized</h3>
<p>Authorization id: <b><?php echo htmlspecialchars($authId); ?></b></p>
<?php if ($authId != "") { ?>
<!-- In a real flow capture happens on your server after the ticket is issued -->
<form method="post" action="capturePayment.php">
	<input type="hidden" name="authId" value="<?php echo htmlspecialchars($authId); ?>"/>
	<button type="submit">Capture now</button>
</form>
<?php } ?>
<?php
echo"<pre>";var_dump($res);echo"</pre>";
?>
</body>
</html>
