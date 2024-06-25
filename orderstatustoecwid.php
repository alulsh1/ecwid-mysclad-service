<? // phpinfo();

// admin@site24   495b77c7bf

use GuzzleHttp\Client;

require_once (__DIR__.'/config.php');
require_once (__DIR__.'/DBAccess.php');
require_once (__DIR__.'/remotelog.php');



//include ('vendor/autoload.php');

//$loader = new \Composer\Autoload\ClassLoader();

//use phpseclib3\Net\SSH2;

//$order_id = $_GET['id'];
//$order_id = $_REQUEST["requestId"];
//$order_id = "24c01523-b689-11ee-0a80-0ff8000f6f00";
//$order_id = "f536b8d5-b67f-11ee-0a80-08f6000f40ee"; //bad
$message_prefix = $storeId." Вебхук изменение статуса заказа(МС->Сайт).";
$message = " старт ";
$level = "info";
$data = array(
    "application" => "orderStatusToEcwid",
    "store" => $storeId,
    "orderId" => $order_id,
    );

writeToLog($data,$message_prefix.$message,"info");

$decodeBody = json_decode(file_get_contents("php://input"), true);
writeToLog( $decodeBody," postData - ".$message_prefix.$message,"info");

if (!empty($decodeBody["events"][0])) {
    $order_id = substr($decodeBody["events"][0]["meta"]["href"],
                1+strrpos($decodeBody["events"][0]["meta"]["href"],"/"));
}
else {
    return;
}

if ( $isLoginAuth ) {
    $arrayClient = array(
        'base_uri' => 'https://api.moysklad.ru',
        'auth' => $authMS, 
        'headers' => [
//          'Authorization' => $bearer,
        'Content-Type' => 'application/json',
            "Accept-Encoding" => "gzip",
            "type" =>"image",
            "mediaType" => "application/json"
        ]
    );
}
else {
    $arrayClient = array(
        'base_uri' => 'https://api.moysklad.ru',
        'headers' => [
        'Authorization' => $bearer,
        'Content-Type' => 'application/json',
            "Accept-Encoding" => "gzip",
            "type" =>"image",
            "mediaType" => "application/json"
        ]
    );
}
$clientMS = new Client($arrayClient);

if ( $order_id ) {

    http_response_code(200);

 /*
    $clientMS = new Client([
        'base_uri' => 'https://api.moysklad.ru',
        'headers' => [
            'Authorization' => $bearer,
            'Content-Type' => 'application/json',
	        "Accept-Encoding" => "gzip",
            "type" =>"image",
            "mediaType" => "application/json"
    	]
    ]);
*/
    $clientEcwid = new Client([
        'base_uri' => 'https://app.ecwid.com',
    'headers' => [
//        'Content-Type' => 'application/json',
        'accept' => 'application/json',
        'Authorization' => $bearer_secret,
//	        "Accept-Encoding" => "gzip"
            "type" =>"image",
            "mediaType" => "application/json"
    	]
    ]);

    
    // Получаем данные заказа из Moysklad API
    $url_order = '/api/remap/1.2/entity/customerorder/' . $order_id;
    $response = $clientMS->get($url_order);
    $order_data = json_decode($response->getBody(), true);
    $message = "  заказ в МС: номер ".$order_data["name"];
    writeToLog($order_data,$message_prefix.$message, $level);

    $url_state = $order_data["state"]["meta"]["href"];
    $response = $clientMS->get($url_state);
    $state = json_decode($response->getBody(), true);
//        $message_prefix = $order["organization"]["meta"]["href"].$message_prefix;
    $message = " статус заказа в МС: номер ".$order_data["name"];
    writeToLog($state,$message_prefix.$message, $level);


/*

"name""Новый"
"stateType""Regular"
curl --request GET \
     --url https://app.ecwid.com/api/v3/storeId/orders/orderId \
     --header 'Authorization: Bearer e***s0' \
     --header 'accept: application/json'

        $response = $clientMS->get($url_order .'/positions');
        $positions = json_decode($response->getBody(), true);
        $message = " список позиций в заказе МС: номер ".$order_data["name"];
        writeToLog($positions,$message_prefix.$message, $level);
        foreach($positions["rows"] as $item)  {
//            writeToLog($item,' positions-ROW');
            $productId = trim(substr(strstr($item['assortment']["meta"]['href'],"duct/"),5));
//            writeToLog($productId,' productId');
*/
// search in Ecwid
    $id = (int)$order_data["code"];
//    $url = "/api/v3/".$storeId."/orders/".$id;
    $url = "/api/v3/".$storeId."/orders?responseFields=count,items(id)&ids=".$id; // search
    $response = $clientEcwid->get($url);
    $data = json_decode($response->getBody(), true);
    if($data["count"] > 0) {
        $message = " найден заказ в Эквиде: номер ".$order_data["name"];
        writeToLog($data,$message_prefix.$message, $level);
    }
    else {
        $message = " не найден заказ в Эквиде: номер ".$order_data["name"];
        writeToLog($data,$message_prefix.$message, "error");
        return;
    }

// формируем статус заказа из МС в Эквид
/*
МС
    Новый
    Подтвержден
    Собран
    Отгружен
    Доставлен
    Возврат
    Отменен

fulfillmentStatus
    AWAITING_PROCESSING,
    PROCESSING, SHIPPED,
    DELIVERED, 
    WILL_NOT_DELIVER, 
    RETURNED, 
    READY_FOR_PICKUP, 
    OUT_FOR_DELIVERY, 
    CUSTOM_FULFILLMENT_STATUS_1, 
    CUSTOM_FULFILLMENT_STATUS_2, 
    CUSTOM_FULFILLMENT_STATUS_3
*/
  
    $orderEcwid["id"] = $id;
    if ($state["name"] == "Новый")
        $orderEcwid["fulfillmentStatus"] = "AWAITING_PROCESSING";
    if ($state["name"] == "Подтвержден")
        $orderEcwid["fulfillmentStatus"] = "PROCESSING";
    if ($state["name"] == "Собран")
        $orderEcwid["fulfillmentStatus"] = "READY_FOR_PICKUP";
    if ($state["name"] == "Отгружен")
        $orderEcwid["fulfillmentStatus"] = "SHIPPED";
    if ($state["name"] == "Доставлен")
        $orderEcwid["fulfillmentStatus"] = "DELIVERED";
    if ($state["name"] == "Возврат")
        $orderEcwid["fulfillmentStatus"] = "WILL_NOT_DELIVER";
    if ($state["name"] == "Отменен")
        $orderEcwid["fulfillmentStatus"] = "RETURNED";


    $message = " заказ для записи в Эквиде: номер ".$orderEcwid["id"];
    writeToLog($orderEcwid,$message_prefix.$message,$level);


    $url = "/api/v3/".$storeId."/orders/".$id;
    $response = $clientEcwid->put($url, ['json' => $orderEcwid ]);
    $modified = json_decode($response->getBody(), true);
    $message = " изменение заказ в Эквиде: номер ".$orderEcwid["id"];
    writeToLog($modified,$message_prefix.$message,$level);

/*
            $product = array();
//                $product["name"] = $productMS["name"];
            $product["quantity"] = $stock;
    //        $order['description'] = "Конфликт 4... ".$order['description'];
    //        writeToLog($order['description'],'description Новое значение');
    //        $response = $clientEcwid->put($url_order, ['json' => $order]);
            if($isProduct) { //  UPDATE
                $message = " изменение товара в Эквид: ".$productMS["name"].", id= ".$productIdEcwid;
                writeToLog($product,$message_prefix.$message, $level);
//                writeToLog(array("data" => $stock."  ".$productMS["name"]),$message_prefix.' UPDATE PRODUCT ',"info");
                $url = "/api/v3/".$storeId."/products/".$productIdEcwid;
                $response = $clientEcwid->put($url, ['json' => $product]);
            }
            }
            
            */
}

