<?php
include('config.php');
$access_token = getAccessToken();

/********** Create order Pay Load (intent AUTHORIZE, Venmo as payment source) ****/
$postData = '{
  "intent": "AUTHORIZE",
  "payment_source": {
    "venmo": {
      "experience_context": {
        "brand_name": "Venmo",
        "shipping_preference": "NO_SHIPPING",
        "user_action": "CONTINUE"
      }
    }
  },
  "purchase_units": [
    {
      "reference_id": "<YOUR BOOKING REFERENCE>",
      "description": "payment for <MENTION SERVICE DETAILS>",
      "custom_id": "<PASS ANY CUSTOM DETAILS THAT YOU WANT IN PAYPAL SETTLEMENT REPORT>",
      "invoice_id": "<UNIQUE INVOICE ID>",
      "amount": {
        "currency_code": "USD",
        "value": "1.00",
        "breakdown": {
          "item_total": {
            "currency_code": "USD",
            "value": "1.00"
          }
        }
      },
      "items": [
        {
          "name": "payment for <MENTION SERVICE DETAILS>",
          "unit_amount": {
            "currency_code": "USD",
            "value": "1.00"
          },
          "quantity": "1",
          "description": "payment for <MENTION SERVICE DETAILS>",
          "category": "DIGITAL_GOODS"
        }
      ]
    }
  ]
}';

//Create Order
$res = getApprovalURL($access_token, $postData);
header('Content-Type: application/json');
print json_encode($res);
?>
