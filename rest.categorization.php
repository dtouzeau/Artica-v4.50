<?php
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$data=file_get_contents("php://input");
echo $data."\n";
if(!isStringIsJson($data)){
	writelogs("isStringIsJson:No json posted",__FUNCTION__,__FILE__,__LINE__);
	json_response("No json posted",500);
	return;
}
$ARRY=json_decode($data);
build($ARRY);





function build($array){
	writelogs("Instance of?",__FUNCTION__,__FILE__,__LINE__);
	if(!($array instanceof stdClass)){
		writelogs("Wrong information posted not a class",__FUNCTION__,__FILE__,__LINE__);
		json_response("Wrong information posted",500);
		return;
	}

	$fields["uuid"]=true;
	$fields["zdate"]=true;
	$fields["sitename"]=true;
	$fields["category"]=true;
	$fields["email"]=true;
	
	foreach ($fields as $key=>$none){

		if(!array_key_exists($key,$array)) {
			writelogs("Wrong information posted ($key) NOT EXISTS",__FUNCTION__,__FILE__,__LINE__);
			json_response("Wrong information posted",500);
			return;
		}
	
		if($array->$key==null){
			writelogs("Wrong information posted ($key) NULL",__FUNCTION__,__FILE__,__LINE__);
			json_response("Wrong information posted",500);
			return;
		}

        writelogs("INFO $key:{$array->$key}",__FUNCTION__,__FILE__,__LINE__);
	
	}
	
	$uuid=$array->uuid;
	$zdate=$array->zdate;
	$sitename=$array->sitename;
	$category=$array->category;
	$company=$array->company;
	$email=$array->email;
	$qz=new mysql_catz();
	writelogs("Get categories ($sitename) NULL",__FUNCTION__,__FILE__,__LINE__);
	$detectedas=$qz->GET_CATEGORIES($sitename);
	
	$q=new postgres_sql();
	if(!$q->TABLE_EXISTS("categories_requests")){$q->CREATE_TABLES();}
	
	$sql="INSERT INTO categories_requests (zDate,uuid,sitename,category,company,email,detectedas) VALUES
			('$zdate','$uuid','$sitename','$category','$company','$email',$detectedas)";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		json_response("MySQL Error",500);
		return;
	}
	json_response("Success",200);
	
}


function json_response($message = null, $code = 200)
{
	header_remove();
	http_response_code($code);
	header("Cache-Control: no-transform,public,max-age=300,s-maxage=900");
	header('Content-Type: application/json');

	$status = array(
			200 => '200 OK',
			400 => '400 Bad Request',
			422 => 'Unprocessable Entity',
			500 => '500 Internal Server Error'
	);
	header('Status: '.$status[$code]);
	return json_encode(array(
			'status' => $code < 300, // success or not?
			'message' => $message
	));
}

function isStringIsJson($json){
$result = json_decode($json);
if (json_last_error() === JSON_ERROR_NONE) {return true;}
if (json_last_error() === 0) {return true;}
}