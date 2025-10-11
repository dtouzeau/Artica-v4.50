<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

if($argv[1]=="--key"){getKey();exit;}

//https://www.badips.com/get/key


function getKey(){
	
	$BadIpsKey=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BadIpsKey");
	if($BadIpsKey<>null){return;}
	
	$curl=new ccurl("https://www.badips.com/get/key");
	$curl->NoHTTP_POST=true;
	
	if(!$curl->get()){
		squid_admin_mysql(1, "Unable to access to www.badips.com", $curl->error,__FILE__,__LINE__);
		echo "Unable to access to www.badips.com $curl->error\n";
		return;
	}
	
	$json=json_decode($curl->data);
	$key=$json->key;
	$err=$json->err;
	
	if($key==null){
		squid_admin_mysql(1, "Unable to obtain key from www.badips.com", $err,__FILE__,__LINE__);
		echo "Unable to obtain key from www.badips.com\n$err\n";
		return;
	}
	
	echo "Success Key: $key\n";
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("BadIpsKey","$key");
	squid_admin_mysql(2, "Success to obtain key from www.badips.com", null,__FILE__,__LINE__);
	

}