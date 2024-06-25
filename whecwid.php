<?php

require_once (__DIR__.'/remotelog.php');
require_once (__DIR__.'/config.php');

/*
приложение - https://my.ecwid.com/store/16208263#develop-apps:name=custom-app-16208263-1
вебхук   - https://ms-ecwid-reh.devaprix.ru/whecwid.php
событие  - order.created

*/

$message_prefix = $storeId. " заказы Эквид->МС: ";
$message = " старт-вехук ";
$level = "info";
$data = array(
    "application" => "whecwid",
    );

// Get contents of webhook request
$requestBody = file_get_contents('php://input');

// Parse webhook data
$decodedBody = json_decode($requestBody,true);


writeToLog($_REQUEST,$message_prefix.$message."REQUEST","info");

$message = " вебхук передал данные decodedBody";
writeToLog($decodedBody,$message_prefix.$message,"info");

//$eventId = $decodedBody['eventId'];
//$eventCreated = $decodedBody['eventCreated'];
//$storeId = $decodedBody['storeId'];
$entityId =  $decodedBody['entityId'];
$eventType = $decodedBody['eventType'];
$data = $decodedBody['data'];
$signatureHeaderPresent = false;

// Reply with 200OK to Ecwid
http_response_code(200);

// Filter out the events we're not interested in
if ($eventType == 'order.created') {
    $orderId = $entityId;
    require_once (__DIR__.'/ordertoms.php');
//    exit;
}


/*
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Continue if eventType is order.updated
// Verify the webhook (check that it is sent by Ecwid)
foreach (getallheaders() as $name => $value) {
    if ($name == "X-Ecwid-Webhook-Signature") {
        $headerSignature = "$value";
      	$signatureHeaderPresent = true;
        
        $hmac_result = hash_hmac("sha256", "$eventCreated.$eventId", $client_secret, true);
        $generatedSignature = base64_encode($hmac_result);
        
        if ($generatedSignature !== $headerSignature) {
            echo 'Signature verification failed';
            exit;
        }
    }
}

if (!$signatureHeaderPresent) {
	echo 'Signature verification failed';
	exit;
}

// Handle the event
// 
// Update events can be sent about one entity (product/order) multiple times. 
//
// Make sure the changed entity meets your requirements first, before processing further. 
// 
// ...
*/

?>