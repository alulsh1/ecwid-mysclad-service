<? // phpinfo();

// admin@site24   495b77c7bf

use GuzzleHttp\Client;

require_once (__DIR__.'/config.php');
require_once (__DIR__.'/DBAccess.php');
require_once (__DIR__.'/remotelog.php');

//include ('vendor/autoload.php');

//$loader = new \Composer\Autoload\ClassLoader();

//use phpseclib3\Net\SSH2;

//$demand_id = $_GET['id'];
//$demand_id = "d6676a88-b081-11ee-0a80-029a00117b55";
$message_prefix = $storeId. " Вебхук по отгрузке(МС->Сайт).";
$message = " старт из MS ";
$level = "info";
$data = array(
    "application" => "demandstockstoecwid",
    "store" => $storeId,
    "demandId" => $demand_id,
    );

writeToLog($data,$message_prefix.$message,"info");

$decodeBody = json_decode(file_get_contents("php://input"), true);
writeToLog( $decodeBody," postData - ".$message_prefix.$message,"info");

if (!empty($decodeBody["events"][0])) {
    $demand_id = substr($decodeBody["events"][0]["meta"]["href"],
                1+strrpos($decodeBody["events"][0]["meta"]["href"],"/"));
}
else {
    return;
}

if ( $demand_id ) {

    http_response_code(200);

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
//    'redirectUri'  => 'https://ms-ecwid-reh.devaprix.ru/index.php',
//    'scopes'        => [
//        'read_profile', 
//        'read_catalog', 
//        'update_catalog',
//        'create_catalog',
//        'read_orders'],
    'headers' => [
//        'Content-Type' => 'application/json',
        'accept' => 'application/json',
        'Authorization' => $bearer_secret,
//	        "Accept-Encoding" => "gzip"
            "type" =>"image",
            "mediaType" => "application/json"
    	]
    ]);

    
    // Получаем данные отгрузки из Moysklad API
    $url_demand = '/api/remap/1.2/entity/demand/' . $demand_id;
    $response = $clientMS->get($url_demand);
    $demand_data = json_decode($response->getBody(), true);
    $message = "  отгрузка в МС: номер ".$demand_data["name"];
    writeToLog($demand_data,$message_prefix.$message, $level);

    if ($demand_data["customerOrder"]) {
        $url_order = $demand_data["customerOrder"]["meta"]["href"];
        $response = $clientMS->get($url_order);
        $order = json_decode($response->getBody(), true);
//        $message_prefix = $order["organization"]["meta"]["href"].$message_prefix;
        $message = " заказ для отгрузки МС: номер ".$order["name"];
        writeToLog($order,$message_prefix.$message, $level);

        $response = $clientMS->get($url_demand .'/positions');
        $positions = json_decode($response->getBody(), true);
        $message = " список позиций в отгрузке МС: номер ".$demand_data["name"];
        writeToLog($positions,$message_prefix.$message, $level);
        foreach($positions["rows"] as $item)  {
//            writeToLog($item,' positions-ROW');
            $productId = trim(substr(strstr($item['assortment']["meta"]['href'],"duct/"),5));
//            writeToLog($productId,' productId');

// PRODUCT IN MS
            $url = "/api/remap/1.2/entity/product/".$productId;
            $response = $clientMS->get($url);
            $productMS = json_decode($response->getBody(), true);
            $message = " товар в отгрузке МС: ".$productMS["name"];
            writeToLog($productMS,$message_prefix.$message, $level);
// STOCK IN MS
            $urlStock = "/api/remap/1.2/report/stock/all/current?filter=assortmentId=".$productId;
            $stocks = $clientMS->get($urlStock);
            $datarow = json_decode($stocks->getBody(), true);
            if(!empty($datarow[0])) $stock = $datarow[0]["stock"];
            else $stock = 0;
            $message = " остаток по товару МС: ".$productMS["name"];
            writeToLog(array("data"=> $stock."  ".$productMS["name"]),$message_prefix.$message, $level);

// search in Ecwid
//            $url = "/api/v3/".$storeId."/products?attribute_externalCode=".$productMS["externalCode"];
            $url = "/api/v3/".$storeId."/products?sku=".$productMS[$fieldSync];
            $response = $clientEcwid->get($url);
            $data = json_decode($response->getBody(), true);
//            writeToLog($data["items"], 'найден продукт в Ecwid product---');
            if($data["count"] > 0) {
                $isProduct = true;
                $productIdEcwid = (string)$data["items"][0]["id"];
                if($data["count"] > 1) {
                    $message =  " найдено похожих товаров в Эквид: ".$data["count"];
                    writeToLog($data,$message_prefix.$message, "error");
                }
                continue;
                }
            else
                $isProduct = false;

            $product = array();
//                $product["name"] = $productMS["name"];
            $product["quantity"] = $stock;
            if($isProduct) { //  UPDATE
                $message = " изменение товара в Эквид: ".$productMS["name"].", id= ".$productIdEcwid;
                writeToLog($product,$message_prefix.$message, $level);
//                writeToLog(array("data" => $stock."  ".$productMS["name"]),$message_prefix.' UPDATE PRODUCT ',"info");
                $url = "/api/v3/".$storeId."/products/".$productIdEcwid;
                $response = $clientEcwid->put($url, ['json' => $product]);
            }
            }
    }
}

