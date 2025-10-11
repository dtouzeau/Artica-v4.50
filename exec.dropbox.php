<?php
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.dnsmasq.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


if($argv[1]=="--uri"){DropBoxUri();exit;}


function DropBoxUri(){
	$sock=new sockets();
	$DropBoxUri=$sock->GET_INFO("DropBoxUri");
	if(strlen($DropBoxUri)>10){
		echo $DropBoxUri."\n";
		return;
	}
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	exec("$grep \"Please visit\" /var/log/dropbox.log|$tail -n 2000 2>&1",$results);
	
	while (list ($dir, $line) = each ($results) ){
		if(preg_match("#Please visit\s+(.+?)\s+to link#", $line,$re)){
			$uri=trim($re[1]);
			break;
		}
		
	}
	
	if($uri<>null){
		$sock=new sockets();
		$sock->SET_INFO("DropBoxUri", $uri);
		echo $uri."\n";
		return;
	}
	
}

