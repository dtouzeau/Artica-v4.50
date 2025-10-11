<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();

$tpl=new template_admin();
header('Content-Type: application/json');
$id=urlencode($_GET["id"]);
if($_GET["id"]=="#" or $_GET["id"]=="/" or $_GET["id"]==""){
    if(isset($_GET["basePath"])){
        if($tpl->IsBase64($_GET["basePath"])){
            $basePath=base64_decode($_GET["basePath"]);
        }else{
            VERBOSE("{$_GET["basePath"]} is not a valid base64 path");
            $basePath=$_GET["basePath"];
        }
        if($basePath<>"/"){
            $id=urlencode($basePath);
        }
    }
}
if($id=="/"){
    $id=urlencode("/");
}
if(strlen($id)<=0){
    $id=urlencode("/");
}
VERBOSE("/system/tree/$id",__LINE__);
$json=$GLOBALS["CLASS_SOCKETS"]->REST_API("/system/tree/$id");
echo $json;


