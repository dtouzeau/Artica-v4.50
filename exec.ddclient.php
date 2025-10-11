<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["RESTART"]=true;}

if($argv[1]=="--build"){build();exit();}



function build(){
	
	$sock=new sockets();
	$DDClientConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DDClientConfig")));
	$DDClientArray=$DDClientConfig["OPENDNS"];
	
	$conf[]="daemon=300";
	$conf[]="ssl=yes";
	$conf[]="cache=/var/cache/ddclient/ddclient.cache";
	$conf[]="pid=/var/run/ddclient.pid";
	$conf[]="syslog=yes";
	
	
	if(is_array($DDClientConfig["OPENDNS"])){
		$conf[]="use=web, web=www.articatech.net/my-ip.php";
		$conf[]="server=updates.opendns.com";
		$conf[]="protocol=dyndns2";         
		$conf[]="login={$DDClientArray["dd_client_username"]}";    
		$conf[]="password={$DDClientArray["dd_client_password"]}"; 
		$conf[]="{$DDClientArray["opendns_network_label"]}";
	}

	@mkdir("/etc/ddclient",666,true);
	@file_put_contents("/etc/ddclient/ddclient.conf",@implode("\n",$conf));
	echo "Starting......: ".date("H:i:s")." DDClient /etc/ddclient/ddclient.conf done\n";
	
	
}

