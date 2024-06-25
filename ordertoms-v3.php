<? // phpinfo();


use GuzzleHttp\Client;

require_once (__DIR__.'/config.php');
//require_once (__DIR__.'/DBAccess.php');
require_once (__DIR__.'/remotelog.php');

//$orderId = "131";

$message_prefix = $storeId."-магазин.Крон.Заказ (Сайт->МС)";
$message = " старт ";
$level = "info";
$data = array(
    "application" => "ordertoms",
    "store" => $storeId,
    );

writeToLog($data,$message_prefix.$message,"info");

//include ('vendor/autoload.php');

//$loader = new \Composer\Autoload\ClassLoader();


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

if ( true ) {
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
//        'Authorization' => $bearer_public,
        'Authorization' => $bearer_secret,
//	        "Accept-Encoding" => "gzip"
            "type" =>"image",
            "mediaType" => "application/json"
    	]
    ]);

    $organizationMSId = "";

    $urlOrganization = "/api/remap/1.2/entity/organization/".$organizationMSId;
    $data = $clientMS->get($urlOrganization);
    $organizationMS = json_decode($data->getBody(), true);
//    writeToLog($organizationMS,' /organizationMS MS---========================================');

// Выгружаем заказ из Эквид  
    $urlOrder = "/api/v3/".$storeId."/orders/".$orderId;
    //&responseFields=count,items(attribute_externalCode,price,sku,name)";
    $provider = $clientEcwid->get($urlOrder);
    $orderEcwid = json_decode($provider->getBody(), true);
    //$provider = $clientEcwid->get($urlAuthEcwid);
    //writeToLog($provider,' /auth Ecwid---');
    $message = "  заказ в Эквид: номер ".$orderEcwid["orderNumber"];
    writeToLog($orderEcwid,$message_prefix.$message, $level);

// формируем заказ для МС
    $orderMS = array();
    $orderMS["organization"] = $organizationMS["rows"][0];
    $orderMS["description"] = "";
    $orderMS["code"] = (string)$orderEcwid["orderNumber"];
//    $orderMS["name"] = $orderId;

    // Проверка наличия уже такого заказа в МС
    $url_order = "/api/remap/1.2/entity/customerorder/?filter=code=~".$orderEcwid['orderNumber'];
    $response = $clientMS->get($url_order);
    $order_data = json_decode($response->getBody(), true);
    if($order_data["meta"]["size"] > 0) {
        $message = "  Не передается. Заказ уже есть в МС: номер в МС ".$order_data["name"]." номер в Эквид:".$orderEcwid['orderNumber'];
        writeToLog($order_data,$message_prefix.$message,"error");
        return;
    }

// анализ контрагента
// контрагент в МС
    $urlCounterParty = "/api/remap/1.2/entity/counterparty/?filter=email=~".$orderEcwid['email'];
    $data = $clientMS->get($urlCounterParty);
    $counterParty = json_decode($data->getBody(), true);
//    writeToLog($counterParty,' /counterParty MS---========================================');

    if(empty($counterParty["rows"][0]["id"])) {
        // добавление контрагента 
        $urlCustomer =   "/api/v3/".$storeId."/customers/".$orderEcwid['customerId'];
        $provider = $clientEcwid->get($urlCustomer);
        $customer = json_decode($provider->getBody(), true);
        $message = "  покупатель в Эквид, заказ: ".$orderEcwid["orderNumber"];
        writeToLog($customer,$message_prefix.$message, $level);
        $urlNew =   "/api/remap/1.2/entity/counterparty";
        $newCounterParty = array (
            "name" => $customer["billingPerson"]["name"],
            "description" => " создан из заказа Эквид ",
//            "сode" => $orderEcwid['customerId'],
            "email" => $orderEcwid["email"],
            "phone" => "may be phone",
        );
        if(!empty($customer["billingPerson"]["phone"])) $newCounterParty["phone"] = $customer["billingPerson"]["name"];
        foreach($customer["contacts"] as $parm)  {
          $newCounterParty[$parm['type']] = $parm['contact'];
        }
        $response = $clientMS->post($urlNew, ['json' => $newCounterParty]);
        $newCounterParty = json_decode($response->getBody(), true);
        $orderMS["agent"] =$newCounterParty; 
        $message = "  новый контрагент в МС: ".$newCounterParty["name"];
        writeToLog($newCounterParty,$message_prefix.$message, $level);
    }
    else
      $orderMS["agent"] = $counterParty["rows"][0]; 
// анализ списка товаров
    $isListGood = true; 
    foreach($orderEcwid["items"] as $item)  {
        $isProductInMS = false;
        $isBadProduct = false;
        if(!empty($item["sku"])
            && empty($item["combinationId"]) && empty($item["combinations"])) {
            $url = "/api/remap/1.2/entity/product?filter=".$fieldSync."=".$item["sku"];
            $response = $clientMS->get($url); // from MS
            $product = json_decode($response->getBody(), true);
//                writeToLog($product["meta"], 'в MS /product---');
//                writeToLog($product, 'в MS /product---');
            if($product["meta"]["size"] > 0) {
                $isProductInMS = true;
                if($product["meta"]["size"] > 1) {
                    $message =  " найдено похожих товаров в MS: ".$product["meta"]["size"];
                    writeToLog($data,$message_prefix.$message, "error");
                }
            }
        }
        else
            $isBadProduct = true;

        $position = array(
          "quantity" => $item["quantity"],
          "price" => $item["price"] * 100,
//            "discount" => $item[""],
//            "vat" => $item[""],
        );

//        повторная проверка наличия товара в МС
        if(!$isProductInMS
          && !$isBadProduct) {
            $url = "/api/remap/1.2/entity/product?filter=".$fieldSync."=".$item["sku"];
            $response = $clientMS->get($url); // from MS
            $product = json_decode($response->getBody(), true);
    //                writeToLog($product["meta"], 'в MS /product---');
    //                writeToLog($product, 'в MS /product---');
            if($product["meta"]["size"] > 0) {
                $isProductInMS = true;
                if($product["meta"]["size"] > 1) {
                    $message =  " найдено похожих товаров в MS: ".$product["meta"]["size"];
                    writeToLog($data,$message_prefix.$message, "error");
                }
            }
        }

        if(!$isProductInMS
              && !$isBadProduct) {
            // добавление товара в MC при отсутствии 
          $addElement = array(
            "name" => $item["name"],
          );

          $orderMS["description"] = $orderMS["description"]." -!- ERROR товар - ".$item["name"]." отсутствовал в МС. Добавлен, требует коррекции.";
          $isListGood = false; 
          $urlCreate =    "/api/remap/1.2/entity/product";
          $response = $clientMS->post($urlCreate, ['json' => $addElement]);
          $product = json_decode($response->getBody(), true);
          $message = " добавлен товар в МС: ". $item["name"];
          writeToLog($product,$message_prefix.$message, $level);
          $position["assortment"]["meta"] = $product["meta"];
        }
        elseif($isBadProduct) {
          $orderMS["description"] = $orderMS["description"]."  -!-  ERROR недопустимый для обмена товар - ".$item["name"]
                ." -- сумма заказа ".$orderEcwid["total"]." руб. Обработка заказа прекращена ";
          $isListGood = false; 
          break;  
        }
        else {
        // использование соответствующего кода товара МС
          $orderMS["description"] = $orderMS["description"]." товар - ".$item["name"]." ОК ";
          $position["assortment"]["meta"] = $product["rows"][0]["meta"];
        }
        $orderMS["positions"][] = $position;
                
    }
    
// Загружаем заказ в МойСклад
    if($isListGood)  $orderMS["description"] = "";
    writeToLog($orderMS,$message_prefix.' order for POST to MS ',"info");
    $urlCreateOrder = "/api/remap/1.2/entity/customerorder";
    $response = $clientMS->post($urlCreateOrder, ['json' => $orderMS]);
    $newOrder = json_decode($response->getBody(), true);
    $message = " добавлен новый заказ в МС: номер ". $newOrder["name"];
    writeToLog($newOrder,$message_prefix.$message, $level);
// ставим заказу МС внешний код - в id заказа в эквид 

}

/*
2) выгружаем статус заказа из Эквид в МойСклад <br/>
3) связь заказов по внешнему коду в Моемскладе и id заказа в эквид <br/>
4) скидки и пр. будут передаваться в виде измененной цены составляющих заказа <br/>
5) Заказ выгружается по событию в Эквид в течении 1-5 минут <br/>
<br/>
Контрагенты <br/>
1) Ищем контрагента по E-mail, если не находим. то создаем <br/>
2) в контрагенте передаем Название контрагента, тип (физ, юрлицо, ип). ФИО, телефон, почту, ИНН, КПП, Название компании <br/>
3) контрагент передается только вместе с заказом <br/>
<br/>
*/