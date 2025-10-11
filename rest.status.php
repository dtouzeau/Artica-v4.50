<?php
header("Pragma: no-cache");
header("Expires: 0");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-cache, must-revalidate");
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
include_once(dirname(__FILE__).'/class.tcpip.inc');

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(!inetwork()){
    $RestAPi=new RestAPi();
    $RestAPi->_content_type="text/html";
    $RestAPi->response("Access denied",500);
    die();
}


$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/status", "", $request_uri);
$f=explode("/",$request_uri);

if(!isset($f[2])){$f[2]=null;}
if(!isset($f[3])){$f[3]=null;}
if(!isset($f[4])){$f[4]=null;}
if(!isset($f[5])){$f[5]=null;}



if($f[1]=="json"){MONIT_STATUS_JSON();exit;}
if($f[1]=="xml"){MONIT_STATUS_XML();exit;}
if($f[1]=="html"){MONIT_STATUS_HTML();exit;}

MONIT_STATUS_OTHER($f[1]);
exit;

$RestAPi=new RestAPi();
$RestAPi->_content_type="text/html";
$RestAPi->response("Error understand query [{$f[1]}]",404);


function MONIT_REPLACE_HTML($fileContents){
    $fileContents=str_replace("http://mmonit.com/monit","http://articatech.com",$fileContents);
    $fileContents=str_replace("http://mmonit.com","http://articatech.com",$fileContents);
    $fileContents=str_replace("M/Monit","Artica Watchdog Service",$fileContents);
    $fileContents=str_replace("Monit instances","Monitored services",$fileContents);
    $fileContents=str_replace("Monit Service Manager","Artica Watchdog Manager",$fileContents);
    $fileContents=str_replace("Monit is","Artica Watchdog is",$fileContents);
    $fileContents=str_replace("_about","#",$fileContents);
    $fileContents=str_replace("tildeslash","articatech",$fileContents);
    $fileContents=str_replace("Monit ","Artica Watchdog ",$fileContents);
    return $fileContents;
}

function MONIT_STATUS_OTHER($cmd){

    if(isset($_POST["action"])){
        $ch = curl_init();
        $curlPost=null;
        foreach ($_POST as $key=>$val){
            $curlPost .='&'.$key.'=' . $val;
        }

        curl_setopt($ch, CURLOPT_URL,"http://127.0.0.1:2874/$cmd");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$curlPost);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $server_output = MONIT_REPLACE_HTML(curl_exec($ch));
        $CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));
        curl_close($ch);
        $RestAPi=new RestAPi();
        $RestAPi->_content_type="text/html";
        $RestAPi->response($server_output,$CURLINFO_HTTP_CODE);
        die();
    }


    $fileContents= file_get_contents("http://127.0.0.1:2874/$cmd");


    if(!$fileContents){
        $RestAPi=new RestAPi();
        $RestAPi->_content_type="text/html";
        $RestAPi->response("Error fetching content",501);
        die();
    }

    $RestAPi=new RestAPi();
    $RestAPi->_content_type="text/html";
    $RestAPi->response(MONIT_REPLACE_HTML($fileContents),200);
    die();
}
function MONIT_STATUS_XML(){
    $fileContents= file_get_contents("http://127.0.0.1:2874/_status?format=xml");
    if(!$fileContents){
        $RestAPi=new RestAPi();
        $RestAPi->_content_type="application/xml";
        $RestAPi->response("Error fetching content",501);
        die();
    }

    $RestAPi=new RestAPi();
    $RestAPi->_content_type="application/xml";
    $RestAPi->response($fileContents,200);
    die();
}

function MONIT_STATUS_HTML(){

    $fileContents= file_get_contents("http://127.0.0.1:2874/");
    if(!$fileContents){
        $RestAPi=new RestAPi();
        $RestAPi->_content_type="text/html";
        $RestAPi->response("Error fetching content",501);
        die();
    }

    $RestAPi=new RestAPi();
    $RestAPi->_content_type="text/html";
    $RestAPi->response($fileContents,200);
    die();

}

function MONIT_STATUS_JSON(){

    $fileContents= file_get_contents("http://127.0.0.1:2874/_status?format=xml");

    if(!$fileContents){
        $RestAPi=new RestAPi();
        $RestAPi->response("Error fetching content",501);
        die();
    }

    $fileContents = str_replace(array("\n", "\r", "\t"), '', $fileContents);
    $fileContents = trim(str_replace('"', "'", $fileContents));
    $simpleXml = simplexml_load_string($fileContents);

    if(!$simpleXml){
        $RestAPi=new RestAPi();
        $RestAPi->response("Error fetching xml content",501);
        die();
    }

    $json = json_encode($simpleXml);
    $array = json_decode($json,TRUE);
    $out=json_encode($array,JSON_PRETTY_PRINT);

    $RestAPi=new RestAPi();
    $RestAPi->response($out,200);
}




function logon_events($succes){
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    $logFile="/var/log/artica-webauth.log";
    $date=date('Y-m-d H:i:s');
    $f = fopen($logFile, 'a');
    fwrite($f, "$date $IPADDR $succes\n");
    fclose($f);
}


function inetwork(){
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    if(isset($_SERVER["REMOTE_ADDR"])){$IPADDR=$_SERVER["REMOTE_ADDR"];}
    if(isset($_SERVER["HTTP_X_REAL_IP"])){$IPADDR=$_SERVER["HTTP_X_REAL_IP"];}
    if(isset($_SERVER["HTTP_X_FORWARDED_FOR"])){$IPADDR=$_SERVER["HTTP_X_FORWARDED_FOR"];}
    $results=$q->QUERY_SQL("SELECT * FROM networks_infos WHERE enabled=1");

    $IPCL=new IP();
    foreach ($results as $index=>$ligne){
        $net=$ligne["ipaddr"];
        if($IPCL->isInRange($IPADDR,$net)){return true;}

    }
    return false;
}






