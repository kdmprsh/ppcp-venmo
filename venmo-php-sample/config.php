<?php
    /*
        * Config for PayPal specific values - Venmo sample (JS SDK v5, Orders v2, AUTHORIZE then CAPTURE)
    */

    //Whether Sandbox environment is being used, Keep it true for testing
    define("SANDBOX_FLAG", filter_var(getenv("SANDBOX_FLAG") !== false ? getenv("SANDBOX_FLAG") : "true", FILTER_VALIDATE_BOOLEAN));

    //PayPal REST API endpoints
    define("SANDBOX_ENDPOINT", "https://api-m.sandbox.paypal.com");
    define("LIVE_ENDPOINT", "https://api-m.paypal.com");

    //PayPal REST App (US business account) Client Id and Client Secret - replace with your own
    define("SANDBOX_CLIENT_ID", getenv("SANDBOX_CLIENT_ID") ?: "");
    define("SANDBOX_CLIENT_SECRET", getenv("SANDBOX_CLIENT_SECRET") ?: "");
    define("LIVE_CLIENT_ID", getenv("LIVE_CLIENT_ID") ?: "");
    define("LIVE_CLIENT_SECRET", getenv("LIVE_CLIENT_SECRET") ?: "");

    //ButtonSource Tracker Code
    define("SBN_CODE","PP-ECJSv4Sample");

    //Optional: risk / device data id sent as PayPal-Client-Metadata-Id (leave empty if not used)
    define("CLIENT_METADATA_ID", "");

    /*
    * Purpose: 	Unique id for the PayPal-Request-Id header (idempotency), new value for every API call
    */
    function newRequestId() {
        return bin2hex(random_bytes(16));
    }

    /*
    * Purpose: 	To make a cURL call to REST API
    * Inputs:
    *		curlServiceUrl    : the service URL for the REST api
    *       curlHeader        : the header parameters specific to the REST api call
    *       curlPostData      : the post parameters encoded in the form required by the api (json_encode or http_build_query)
    * Returns:
    *		array["http_code"]   : the http status code
    *		array["json"]        : the decoded response body
    */
    function curlCall($curlServiceUrl, $curlHeader, $curlPostData=NULL) {

        // response container
        $resp = array(
            "http_code" => 0,
            "json"      => ""
        );

        //set the cURL parameters
        $ch = curl_init($curlServiceUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeader);

        if(!is_null($curlPostData)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPostData);
        } else {
            curl_setopt($ch, CURLOPT_HTTPGET, true);
        }

        curl_setopt($ch, CURLOPT_HEADER, true); // capture headers (PayPal-Debug-Id is useful for support)
        $response = curl_exec($ch);

        // some kind of an error happened
        if ($response === false) {
            $resp["error"] = curl_error($ch);
            curl_close($ch);
            return $resp;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerRaw  = substr($response, 0, $headerSize);
        $body       = substr($response, $headerSize);
        $resp["http_code"]   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $resp["headers_raw"] = $headerRaw;
        $resp["headers"]     = parseHeaders($headerRaw);
        $resp["json"]        = json_decode($body, true);
        curl_close($ch);

        return $resp;
    }

    function parseHeaders($headerRaw) {
        $headers = [];
        $lines = explode("\r\n", $headerRaw);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }

    function baseEndpoint() {
        return (SANDBOX_FLAG ? SANDBOX_ENDPOINT : LIVE_ENDPOINT);
    }

    function clientId() {
        return (SANDBOX_FLAG ? SANDBOX_CLIENT_ID : LIVE_CLIENT_ID);
    }

    /************** Access Token ****************/
    function getAccessToken(){
        $curlServiceUrl = baseEndpoint() . "/v1/oauth2/token";
        $clientSecret = (SANDBOX_FLAG ? SANDBOX_CLIENT_SECRET : LIVE_CLIENT_SECRET);
        $curlHeader = array(
            "Content-Type: application/x-www-form-urlencoded",
            "Authorization: Basic ". base64_encode(clientId() .":". $clientSecret)
        );
        $curlPostData = http_build_query(array("grant_type" => "client_credentials"));
        $curlResponse = curlCall($curlServiceUrl, $curlHeader, $curlPostData);
        return $curlResponse['json']['access_token'];
    }

    /************** Create Order (intent AUTHORIZE) *********/
    function getApprovalURL($access_token, $postData){
        $curlServiceUrl = baseEndpoint() . "/v2/checkout/orders";
        $curlHeader = array("Content-Type: application/json", "Authorization: Bearer ".$access_token, "PayPal-Request-Id: ".newRequestId());
        if (CLIENT_METADATA_ID != "") {
            $curlHeader[] = "PayPal-Client-Metadata-Id: ".CLIENT_METADATA_ID;
        }
        $curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
        return $curlResponse['json'];
    }

    /**************** Authorize Order (after buyer approves in Venmo) ***********/
    function executePayment($access_token, $postData, $PAYMENT_ID){
        $curlServiceUrl = baseEndpoint() . "/v2/checkout/orders/".$PAYMENT_ID."/authorize";
        $curlHeader = array("Content-Type: application/json", "Authorization: Bearer ".$access_token, "PayPal-Request-Id: ".newRequestId());
        if (CLIENT_METADATA_ID != "") {
            $curlHeader[] = "PayPal-Client-Metadata-Id: ".CLIENT_METADATA_ID;
        }
        $curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
        return $curlResponse['json'];
    }

    /**************** Capture the Authorization (for example after the ticket is issued) ***********/
    function capturePayment($access_token, $postData, $AUTHORIZATION_ID){
        $curlServiceUrl = baseEndpoint() . "/v2/payments/authorizations/".$AUTHORIZATION_ID."/capture";
        $curlHeader = array("Content-Type: application/json", "Authorization: Bearer ".$access_token, "PayPal-Request-Id: ".newRequestId());
        $curlResponse = curlCall($curlServiceUrl, $curlHeader, $postData);
        return $curlResponse['json'];
    }

    /*************** Show Payment Details ***********/
    function getPaymentDetails($access_token, $PAYMENT_ID){
        $curlServiceUrl = baseEndpoint() . "/v2/checkout/orders/".$PAYMENT_ID;
        $curlHeader = array("Content-Type: application/json", "Authorization: Bearer ".$access_token, "PayPal-Partner-Attribution-Id: ".SBN_CODE);
        return curlCall($curlServiceUrl, $curlHeader);
    }
?>
