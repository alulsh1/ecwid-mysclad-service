<?

//include('Net/SSH2.php');

require_once (__DIR__.'/config.php');

global $ssh;

/*
SSH_HOST=213.189.219.130
SSH_USERNAME=web
SSH_PASSWORD=9weX18hN7WMZwkk
$ssh = new \phpseclib3\Net\SSH2(env('SSH_HOST'));
        $ssh->login(env('SSH_USERNAME'),env('SSH_PASSWORD'));
        $commandOutput = $ssh->exec('sudo /root/yandex-cloud/bin/yc logging write \
         --group-name=b24-oooaprix-addingtotasks-devaprix-ru\
         --message="'.$domain.' '.addslashes($record->message).'" \
         --level='.$record->level->name.' \
         --json-payload="'.addslashes(json_encode($jsonpayload)).'"
         ');
*/
//set_include_path($path=__DIR__ . '/phpseclib/phpseclib');
include ('vendor/autoload.php');

//$loader = new \Composer\Autoload\ClassLoader();

use phpseclib3\Net\SSH2;

// чистка логов

$dir    = 'log';
$files = scandir($dir);
$minTime = (int) date("ymd",time() - ($logLifeTime * 24 * 60 * 60));
       echo $minTime." minTime\n";
foreach ($files as $file) {
        if (fnmatch("[0123456789][0123456789][0123456789][0123456789][0123456789][0123456789]*", $file)) {
                $fileTime = (int) substr($file,0,6);
                if ($fileTime < $minTime) { 
                        echo $fileTime." fileTime\n";
                        echo $file." file for del\n\n";
                        $r = unlink("log/".$file);
                }
        }
}




if ($isLoggingYandex) {
        $ssh = new SSH2($Ya_ip);
        $ssh->login($Ya_login,$Ya_pass);
//        $commandOutput = $ssh->exec('sudo /root/yandex-cloud/bin/yc logging write --group-name=ms-ecwid-reh --message="test start" --level=info');
}
/*
//$ssh = new \phpseclib3\Net\SSH2('213.189.219.130');
$ssh = new SSH2('46.254.21.241');
$ssh->login('web','3S3nf39848fhfj2');
$group = "ms-ecwid-reh";
*/        
/*        $commandOutput = $ssh->exec('sudo /root/yandex-cloud/bin/yc logging write \
         --group-name=ms-ecwid-reh \
         --message="авторизация Yandex ms-ecwid-reh" \
         --level=info \
          ');
*/
//        echo $commandOutput;
//        echo ' авторизация ms-ecwid-reh <br>';

/**
 * Write data to log file.
 *
 * @param mixed $data
 * @param string $title
 *
 * @return bool
 */
function writeToLog($data,$message, $level) {
    require (__DIR__.'/config.php');
 $log = "\n".date("Y-m-d H:i:s").": ".$level.": ";
 $log .= (strlen($message) > 0 ? $message : 'DEBUG') . "  "; //"\n";
 $logShort = $log;
 if (!$isLogFileShort) $log .= print_r($data, 1);
 //$log .= "\n------------------------\n";
 if ($isLogFile) {
        if (!$isLogFileShort) file_put_contents(getcwd() . '/log/'.date("ymd").'-logDetail'.'.txt', $log, FILE_APPEND);
        file_put_contents(getcwd() . '/log/'.date("ymd").'-logShort'.'.txt', $logShort, FILE_APPEND);
 }
 //writeToYandex($data,$message,$level);
 writeToYandex(array(),$message,$level);
 return true;
}

/**
 * Write data to log file.
 *
 * @param mixed $data
 * @param string $title
 *
 * @return bool
 */
function writeToYandex($data,$message,$level) {
    require (__DIR__.'/config.php');
global $ssh;
if (!$isLoggingYandex) return true;
if(!empty($data)) {
        $requestYa = 'sudo /root/yandex-cloud/bin/yc logging write --group-name='.$group.
        ' --message="'.$message.'" --level='.$level.' --json-payload="'.addslashes(json_encode($data)).'"'  ;      
        $commandOutput = $ssh->exec($requestYa);
}
/*        $commandOutput = $ssh->exec('sudo /root/yandex-cloud/bin/yc logging write \
         --group-name='.$group.' \
         --message="'.$message.'" \
         --level='.$level.' \
         --json-payload="'.addslashes(json_encode($data)).'"'
          );*/
else {
        $requestYa = 'sudo /root/yandex-cloud/bin/yc logging write --group-name='.$group.
        ' --message="'.$message.'" --level='.$level;      
        $commandOutput = $ssh->exec($requestYa);
}
/*        $commandOutput = $ssh->exec('sudo /root/yandex-cloud/bin/yc logging write \
         --group-name='.$group.' \
         --message="'.$message.'" \
         --level='.$level.' \
          '); */
 return true;
}
        
        /*
         
        $commandOutput = $ssh->exec('sudo /root/yandex-cloud/bin/yc logging write \
         --group-name=b24-oooaprix-addingtotasks-devaprix-ru \
         --message="test231107 - Info" \
         --level=INFO \
         --json-payload="'.addslashes(json_encode($jsonpayload)).'"'
         );
         
*/