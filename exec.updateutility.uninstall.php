#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["CLI"]=false;
$GLOBALS["TITLENAME"]="Kaspersky Update Utility";


$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

xuninstall();

function build_progress($text,$pourc){
	$echotext=$text;
	if(is_numeric($text)){$old=$pourc;$pourc=$text;$text=$old;}
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/UpdateUtility.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function xuninstall(){
	$unix=new unix();
	$curl=new ccurl();
	$sock=new sockets();

	
	$rm=$unix->find_program("rm");
	build_progress("{remove_software}",20);
	system("$rm -rf /etc/UpdateUtility");
	build_progress("{remove_databases}",25);
	if(is_dir("/home/kaspersky/UpdateUtility")){
		echo "Remove /home/kaspersky/UpdateUtility\n";
		system("$rm -rf /home/kaspersky/UpdateUtility");
	}
	
	
	
	
	build_progress("{remove_databases}",30);
	$UpdateUtilityStorePath=$sock->GET_INFO("UpdateUtilityStorePath");
	if($UpdateUtilityStorePath==null){$UpdateUtilityStorePath="/home/kaspersky/UpdateUtility";}
	if(is_dir($UpdateUtilityStorePath)){
		echo "Remove $UpdateUtilityStorePath\n";
		system("$rm -rf $UpdateUtilityStorePath");
		
	}
	
	build_progress("{remove_tasks}",40);
	if(is_file("/etc/cron.d/UpdateUtility")){
		@unlink("/etc/cron.d/UpdateUtility");
		system("/etc/init.d/cron reload");
	}
	
	build_progress("{remove_websites}",50);
	$q=new mysql();
	$q->QUERY_SQL("DELETE FROM freeweb WHERE groupware='UPDATEUTILITY'");
	
	$sock->SET_INFO("UpdateUtilityForceProxy", 0);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
		system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	}
	
	build_progress("{refresh}",90);
	system("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose");
	
	build_progress("{remove_software} {success}",100);
	
	
	
	
}