<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
		$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
//$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');


//

start();

function start(){
    $NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
    if($NoInternetAccess==1){return;}
	$unix=new unix();
	$sock=new sockets();
	$EnableArticaMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMirror"));
	if($EnableArticaMirror==0){exit();}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	if($GLOBALS["VERBOSE"]){echo "Time: $cachetime\n";}
	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){WriteMyLogs("Already executed PID:$pid, die()",__FUNCTION__,__FILE__,__LINE__);exit();}
	@file_put_contents($pidfile,getmypid());
	
	$cachetimeEx=$unix->file_time_min($cachetime);
	if(!$GLOBALS["FORCE"]){
		if($cachetimeEx<25){return;}
	}
	
	@unlink($cachetime);
	@file_put_contents($cachetime, time());
	
	
	$APACHE_USER=$unix->APACHE_SRC_ACCOUNT();
	$MyRepo="/home/www.artica.fr/web/tmpf/auto.update.ini";
	$MIRROR="http://articatech.net";
	$REMOTE_URI="$MIRROR/auto.update.php";
	$OFFICIAL_DEST="/home/www.artica.fr/web/download";
	$NIGHTLY_DEST="/home/www.artica.fr/web/nightbuilds";
	$TEMP_DIR=$unix->TEMP_DIR();
	
	$MyRepo="/home/www.artica.fr/web/tmpf/auto.update.ini";
	
	if(!is_file($MyRepo)){
		if($GLOBALS["VERBOSE"]){echo "$MyRepo no such file\n";}
		
	}
	$ini=new Bs_IniHandler($MyRepo);
	
	$LOCAL_OFFICIAL=$ini->_params["NEXT"]["artica"];
	$LOCAL_NIGHTLY=$ini->_params["NEXT"]["artica-nightly"];
	
	
	
	
	$tmpfile=$unix->FILE_TEMP();
	
	$curl=new ccurl($REMOTE_URI);
	if(!$curl->GetFile($tmpfile)){return;}
	$ini=new Bs_IniHandler($tmpfile);
	$REMOTE_OFFICIAL=$ini->_params["NEXT"]["artica"];
	$REMOTE_NIGHTLY=$ini->_params["NEXT"]["artica-nightly"];
	@unlink($tmpfile);
	
	echo "Official $LOCAL_OFFICIAL/$REMOTE_OFFICIAL\n";
	echo "Nightly $LOCAL_NIGHTLY/$REMOTE_NIGHTLY\n";
	
	if($LOCAL_OFFICIAL<>$REMOTE_OFFICIAL){
		$uri="$MIRROR/download/artica-$REMOTE_OFFICIAL.tgz";
		$ArticaFileTemp="$TEMP_DIR/$REMOTE_OFFICIAL.tgz";
		echo "Downloading $uri\n";
		$curl1=new ccurl($uri);
		if(!$curl1->GetFile($ArticaFileTemp)){echo "Failed\n";return;}
		@copy($ArticaFileTemp, "$OFFICIAL_DEST/artica-$REMOTE_OFFICIAL.tgz");
		@chown("$OFFICIAL_DEST/artica-$REMOTE_OFFICIAL.tgz","$APACHE_USER");
		@unlink($ArticaFileTemp);
		@unlink($MyRepo);
		
	}
	
	if($LOCAL_NIGHTLY<>$REMOTE_NIGHTLY){
		$uri="$MIRROR/nightbuilds/artica-$REMOTE_NIGHTLY.tgz";
		$ArticaFileTemp="$TEMP_DIR/$REMOTE_NIGHTLY.tgz";
		echo "*******************************************\n";
		echo "Downloading $uri to $ArticaFileTemp\n";
		echo "Local: $NIGHTLY_DEST/$REMOTE_NIGHTLY.tgz\n";
		echo "*******************************************\n\n";
		
		$curl2=new ccurl($uri);
		if(!$curl2->GetFile($ArticaFileTemp)){echo "Failed\n";return;}
		@copy($ArticaFileTemp, "$NIGHTLY_DEST/artica-$REMOTE_NIGHTLY.tgz");
		@chown("$NIGHTLY_DEST/artica-$REMOTE_NIGHTLY.tgz","$APACHE_USER");
		@unlink($ArticaFileTemp);
		@unlink($MyRepo);
	
	}	
	
	
	
}


