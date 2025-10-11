<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["NOPROGRESS"]=false;

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





run_func();



function build_progress($text,$pourc){
	if($GLOBALS["NOPROGRESS"]){return;}
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents(PROGRESS_DIR."/squid.network.switch.progress", serialize($array));
	@chmod(PROGRESS_DIR."/squid.network.switch.progress",0755);
	sleep(1);

}


function run_remove(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{reconfiguring_proxy_service}",80);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	build_progress("{reloading_proxy_service}",90);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	shell_exec("/etc/init.d/squid reload --force --script=".basename(__FILE__));
	build_progress("{done}",100);
	
}


function run_func(){
	$unix=new unix();
	
	$sock=new sockets();
	$SquidNetworkSwitch=$sock->GET_INFO("SquidNetworkSwitch");
	if($SquidNetworkSwitch==null){run_remove();return;}
	$unix=new unix();
	if(!$unix->NETWORK_INTERFACE_OK($SquidNetworkSwitch)){
		echo "$SquidNetworkSwitch unavailable\n";
		build_progress("$SquidNetworkSwitch {failed}",110);
		return;
		
	}
	 
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$ipaddr=$NETWORK_ALL_INTERFACES[$SquidNetworkSwitch]["IPADDR"];
	if(!$unix->NETWORK_IS_LISTEN_ADDR_EXISTS($ipaddr)){
		echo "$SquidNetworkSwitch / $ipaddr unavailable\n";
		build_progress("$SquidNetworkSwitch / $ipaddr {failed}",110);
		return;
		
	}

	build_progress("{reconfiguring_proxy_service}",80);
	
	$f=explode("\n", @file_get_contents("/etc/squid3/squid.conf"));
	foreach ($f as $num=>$ligne){
		if(preg_match("#tcp_outgoing_address#", $ligne)){
			echo "Remove line $ligne\n";
			continue;
		}
		
		$newF[]=$ligne;
		
	}
	
	echo "$SquidNetworkSwitch -> $ipaddr\n";
	$newF[]="#  Quick Network switch Interface: [$SquidNetworkSwitch] [".date("Y-m-d H:i:s")."]";
	$newF[]="tcp_outgoing_address $ipaddr all";
	$newF[]="";
	@file_put_contents("/etc/squid3/squid.conf", @implode("\n", $newF));
	
	build_progress("{reloading_proxy_service}",90);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	system("/etc/init.d/squid reload --force --script=".basename(__FILE__));
	build_progress("{done}",100);
}
