<?php
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
$GLOBALS["UPDATE"]=false;
$GLOBALS["FORCE"]=false;

ReloadMacHelpers();


function ReloadMacHelpers($output=false){
	$unix=new unix();
	
	$timeFile=$unix->file_time_min("/etc/artica-postfix/pids/ReloadMacHelpers.time");
	if($timeFile<240){
		squid_admin_mysql(2, "Want to refresh helpers but least of 240mn ( {$timeFile}mn )", null,__FILE__,__LINE__);
		exit();
	}
	
	
	@unlink("/etc/artica-postfix/pids/ReloadMacHelpers.time");
	@file_put_contents("/etc/artica-postfix/pids/ReloadMacHelpers.time", time());
	
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if( is_file($squidbin)){ 
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
		system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	}
		
}
