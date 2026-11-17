<?php
include('config.php');
?>
<!DOCTYPE html>

<head>
  <meta charset="utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    body { font-family: helvetica, tahoma, calibri, sans-serif; color:#3a3a3a; }
    .paypal-button-container { border-radius: 5px; background-color: #FFFFFF; padding: 20px; max-width: 760px; width: 100%; margin: 0 auto; }
    .summary { max-width: 760px; margin: 0 auto 10px auto; font-size:17px; }
    #not-eligible { display:none; color:#a00; text-align:center; }
    #terms-msg { display:none; color:#a00; text-align:center; font-size:14px; }
  </style>
</head>

<body>
  <div class="summary">
    <h3>Pay with Venmo</h3>
    <p>Total to be paid: <b>USD 1.00</b></p>
    <label><input type="checkbox" id="terms"> I have read and agree to the terms and conditions.</label>
  </div>

  <div class="paypal-button-container">
    <div id="venmo-button-container"></div>
    <p id="terms-msg">Please accept the terms and conditions to continue.</p>
    <p id="not-eligible">Venmo is not available on this device or for this buyer. Please choose another payment method.</p>
  </div>

  <!-- Include the PayPal JavaScript SDK (v5). enable-funding=venmo shows Venmo, intent must match the order intent.
       buyer-country=US is only for sandbox testing. -->
  <script src="https://www.paypal.com/sdk/js?client-id=<?php echo clientId(); ?>&currency=USD&intent=authorize&enable-funding=venmo<?php echo (SANDBOX_FLAG ? '&buyer-country=US' : ''); ?>"></script>

  <script>
    var venmoButton = paypal.Buttons({
      fundingSource: paypal.FUNDING.VENMO,
      style: {
        layout: 'vertical',
        shape:  'rect',
        label:  'pay'
      },

      // Start the Venmo flow only when the terms are accepted
      onClick: function(data, actions) {
        if (document.getElementById('terms').checked) {
          document.getElementById('terms-msg').style.display = 'none';
          return actions.resolve();
        }
        document.getElementById('terms-msg').style.display = 'block';
        return actions.reject();
      },

      // Creates the order on your server (intent AUTHORIZE)
      createOrder: function(data, actions) {
        return fetch('createPayment.php', {
          method: 'post',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ source: data.paymentSource }) // "venmo"
        }).then(function(res) {
          return res.json();
        }).then(function(order) {
          console.log(order);
          return order.id;
        });
      },

      onCancel: function(data) {
        // Show a cancel page, or return to the payment page with other payment methods
        window.location.href = "cancel.php";
      },

      // Buyer approved in Venmo: authorize the order on your server
      onApprove: function(data, actions) {
        console.log("within onApprove");
        console.log(data);
        return fetch('executePayment.php', {
          method: 'post',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data) // contains orderID
        }).then(function(res) {
          return res.json();
        }).then(function(details) {
          console.log(details);
          var auth = details.purchase_units && details.purchase_units[0].payments &&
                     details.purchase_units[0].payments.authorizations &&
                     details.purchase_units[0].payments.authorizations[0];
          if (auth && auth.status == "CREATED") {
            window.location.href = "successPayment.php?id=" + encodeURIComponent(details.id);
          } else {
            console.log("error", details);
            alert("Venmo payment could not be authorized. Please try another payment method.");
          }
        });
      },

      onError: function(err) {
        console.log(err);
        alert("Venmo payment error. Please try again or choose another payment method.");
      }
    });

    // Show the Venmo button only if Venmo is eligible (US buyer, USD, supported browser/device)
    if (venmoButton.isEligible()) {
      venmoButton.render('#venmo-button-container');
    } else {
      document.getElementById('not-eligible').style.display = 'block';
    }
  </script>

</body>
</html>
