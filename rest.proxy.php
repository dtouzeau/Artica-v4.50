#!/usr/bin/php
<?php

if(preg_match("#--help#",@implode(" ",$argv))){help();exit;}

if(preg_match("#--server=(.+?)(\s|$)#",@implode(" ",$argv),$re)){$GLOBALS["SERVER"]=$re[1];}
if(preg_match("#--port=([0-9]+)#",@implode(" ",$argv),$re)){$GLOBALS["PORT"]=$re[1];}
if(preg_match("#--get=(.+?)(\s|$)#",@implode(" ",$argv),$re)){$GLOBALS["GET"]=$re[1];}
if(preg_match("#--api=(.+?)(\s|$)#",@implode(" ",$argv),$re)){$GLOBALS["API"]=trim($re[1]);}


if( (!isset($GLOBALS["SERVER"])) OR  (!isset($GLOBALS["PORT"])) OR  (!isset($GLOBALS["GET"])) OR (!isset($GLOBALS["API"])) ){help();exit;}


$ch = curl_init();
$CURLOPT_HTTPHEADER[]="Accept: application/json";
$CURLOPT_HTTPHEADER[]="Pragma: no-cache,must-revalidate";
$CURLOPT_HTTPHEADER[]="Cache-Control: no-cache,must revalidate";
$CURLOPT_HTTPHEADER[]="Expect:";
$CURLOPT_HTTPHEADER[]="ArticaKey: {$GLOBALS["API"]}";
$MAIN_URI="https://{$GLOBALS["SERVER"]}:{$GLOBALS["PORT"]}/api/rest/proxy/{$GLOBALS["GET"]}";

echo "Request.....: $MAIN_URI\n";
foreach ($CURLOPT_HTTPHEADER as $header){echo "$header\n";}

curl_setopt($ch, CURLOPT_HTTPHEADER, $CURLOPT_HTTPHEADER);
curl_setopt($ch, CURLOPT_TIMEOUT, 300);
curl_setopt($ch, CURLOPT_URL, $MAIN_URI);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);


$response = curl_exec($ch);
$errno=curl_errno($ch);
if($errno>0){
    echo "Error $errno\n".curl_error($ch)."\n$response\n";
    curl_close($ch);
    die();
}
$CURLINFO_HTTP_CODE=intval(curl_getinfo($ch,CURLINFO_HTTP_CODE));

echo "Server return code.....: $CURLINFO_HTTP_CODE\n";

if($CURLINFO_HTTP_CODE<>200){
    echo "Error $CURLINFO_HTTP_CODE\n";
    if($CURLINFO_HTTP_CODE==404){
        echo "Please, restart the web interface service of {$GLOBALS["SERVER"]}\using /etc/init.d/artica-webconsole restart\n";
    }
    if($CURLINFO_HTTP_CODE==407){
        $json=json_decode($response);
        echo "Failed $json->message\n";die();
    }
    if($CURLINFO_HTTP_CODE==503){
        $json=json_decode($response);
        echo "Failed (REST API ERROR ): $json->message\n";die();
    }
    die();
}


$json=json_decode($response);
if(!$json->status){echo "Failed $json->message\n";die();}
echo "Server return Success\n";

var_dump($json);



function help(){

echo "REST API TEST For proxy\n";
echo "****************************\n";


echo "--server=[server address]\n";
echo "--port=[server port]\n";
echo "--get=[rest api uri]\n";
echo "--api=[API KEY]\n";

echo "Example: \n";
echo basename(__FILE__)." --api=TaR9uMaMBKo10gGlvta89hcqNdYKXmP1 --server=192.168.1.1 --port=9000 --get=white/list/all/500\n\n";





}