<? // phpinfo();


use GuzzleHttp\Client;

require_once (__DIR__.'/config.php');
//require_once (__DIR__.'/DBAccess.php');
require_once (__DIR__.'/remotelog.php');

//$orderId = "131";

$message_prefix = $storeId." Инстолляция. MS создание вебхуков ";
$message = " старт ";
$level = "info";
$data = array(
    "application" => "mshookcreate",
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

// список существующих вебхуков
/*
$urlSearch = "/api/remap/1.2/entity/webhook";
$data = $clientMS->get($urlSearch);
$webhooksMS = json_decode($data->getBody(), true);
$message = "  вебхуки МС до постановки: ";
writeToLog($webhooksMS["rows"],$message_prefix.$message, $level);
*/

// установка вебхуков
/*
$hook = array(
  "url" => "https://webhook.site/18761bb1-fa76-4929-9578-1a4d0ed138c7",
  "action" => "UPDATE",
  "entityType" => "customerorder",
);
$urlCreate = "/api/remap/1.2/entity/webhook";
$data = $clientMS->post($urlCreate, ['json' => $hook]);
$webhooksMS = json_decode($data->getBody(), true);
$message = "  установлен вебхук МС: ";
writeToLog($webhooksMS,$message_prefix.$message, $level);
*/

$hook = array(
  "url" => $urlApp."/demandstockstoecwid.php",
  "action" => "CREATE",
  "entityType" => "demand",
);
$urlCreate = "/api/remap/1.2/entity/webhook";
$data = $clientMS->post($urlCreate, ['json' => $hook]);
$webhooksMS = json_decode($data->getBody(), true);
$message = "  установлен вебхук МС: ";
writeToLog($webhooksMS,$message_prefix.$message, $level);

$hook = array(
  "url" => $urlApp."/orderstatustoecwid.php",
  "action" => "UPDATE",
  "entityType" => "customerorder",
);
$urlCreate = "/api/remap/1.2/entity/webhook";
$data = $clientMS->post($urlCreate, ['json' => $hook]);
$webhooksMS = json_decode($data->getBody(), true);
$message = "  установлен вебхук МС: ";
writeToLog($webhooksMS,$message_prefix.$message, $level);


// удаление вебхуков
/*
$idHook = "33";
$urlDelete = "/api/remap/1.2/entity/webhook".$idHook;
$data = $clientMS->delete($urlDelete);
$webhooksMS = json_decode($data->getBody(), true);
$message = "  удален вебхук МС: ";
writeToLog($webhooksMS,$message_prefix.$message, $level);
*/
// список существующих вебхуков
$urlSearch = "/api/remap/1.2/entity/webhook";
$data = $clientMS->get($urlSearch);
$webhooksMS = json_decode($data->getBody(), true);
$message = "  вебхуки МС итоговый список: ";
writeToLog($webhooksMS["rows"],$message_prefix.$message, $level);


