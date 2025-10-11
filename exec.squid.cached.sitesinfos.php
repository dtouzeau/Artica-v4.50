<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["NOCHECK"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--reload#",implode(" ",$argv),$re)){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--nochek#",implode(" ",$argv))){$GLOBALS["NOCHECK"]=true;}



include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.squid.remote-stats-appliance.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.checks.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.refresh_patterns.inc');
start();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/squid.cached.sitesinfos.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);


}

function start(){
	$unix=new unix();

	
	
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$php=$unix->LOCATE_PHP5_BIN();
	$squid_refresh_pattern=new squid_refresh_pattern();
	$tempsquid=$unix->TEMP_DIR()."/squid.conf";
	build_progress("{caches_rules}: {apply}",15);

	build_progress("{caches_rules}: {apply}",30);
	$squid_refresh_pattern->build();
	
		
	if(!$GLOBALS["NOCHECK"]){
		if(!build_IsInSquid()){
			build_progress("{reconfiguring}",30);
			system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
			if(!build_IsInSquid()){
				build_progress("{failed}",110);
				return;
			}
			build_progress("{done}",100);
			return;
		}
	}
	
	
	build_progress("{reconfiguring}",50);
	build_progress("{reconfiguring}",90);

	
	@unlink($tempsquid);
	@copy("/etc/squid3/squid.conf",$tempsquid);
	$squid_checks=new squid_checks($tempsquid);
	if(!$squid_checks->squid_parse()){
		build_progress("{checking}: {failed}",110);
		return;
	}
	
	if($GLOBALS["RELOAD"]){if( is_file($squidbin)){ 
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }
    }

	build_progress("{success}",100);
	
}

function squidprivs($path){
	@chmod($path, 0755);
	@chown($path,"squid");
	@chgrp($path, "squid");	
	
}

function build_IsInSquid(){

	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	foreach ($f as $num=>$val){
		if(preg_match("#include.*?refresh_pattern_artica\.conf#i", $val)){return true;}

	}
}
