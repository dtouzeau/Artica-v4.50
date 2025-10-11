<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__).'/ressources/class.rest.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(!isset($_SERVER["HTTP_UUID"])){die();}

$request_uri=$_SERVER["REQUEST_URI"];
$request_uri=str_replace("/api/rest/categories/", "", $request_uri);
$f=explode("/",$request_uri);

if($f[0]=="query"){GET_CATEGORIES($f[1]);exit;}


$GLOBALS["VERBOSE"]=true;

$array["status"]=false;
$array["message"]="Unable to understand query <{$f[0]}> <{$f[1]}> in $request_uri";
$array["category"]=0;

$RestAPi=new RestAPi();
$RestAPi->response(json_encode($array),404);

function GET_CATEGORIES($sitename){
	$catz=new mysql_catz();
	$catz->OnlyQuery();
	$catz->LogTosyslog();
	$catz->OnlyLocal();
	$catz->SaveNotCategorizedToDatabase();
	$categories=$catz->GET_CATEGORIES($sitename);
	
	if($categories>0){
		$array["status"]=true;
		$array["message"]="";
		$array["category"]=$categories;
        $array["categoryname"]=$catz->CategoryIntToStr($categories);
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),200);
		return;
		
	}

	if(!$catz->ok){
		$array["status"]=false;
		$array["message"]=$catz->mysql_error;
		$array["category"]=0;
        $array["categoryname"]="unknown";
		$RestAPi=new RestAPi();
		$RestAPi->response(json_encode($array),501);
		return;
	}
	
	
	$array["status"]=true;
	$array["message"]="";
	$array["category"]=0;
    $array["categoryname"]="unknown";
	$RestAPi=new RestAPi();
	$RestAPi->response(json_encode($array),200);
	return;
}



