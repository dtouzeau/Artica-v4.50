<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.activedirectory.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	$GLOBALS["AS_ROOT"]=true;
	
	
	$unix=new unix();
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["output"]=true;}
	
	if(system_is_overloaded(basename(__FILE__))){
		$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__);
		exit();
	}
	
	
	if($argv[1]=="--status"){status($argv[2]);exit();}
	if($argv[1]=="--dist"){distri($argv[2]);exit();}
	
	$ou=$argv[1];
	
	if($ou==null){
		echo "Please define the local organization..\n";
		exit();
	}
	
	$ad=new wad($ou);
	$ad->Perform_import();
	$sock=new sockets();
	$sock->getFrameWork("cmd.php?postfix-hash-tables=yes");
	
	
function distri($ou){
	$ad=new wad($ou);
	$ad->ImportDistriList();
	
}


function status($ou){
	$ad=new wad($ou);
	$ad->analyze();
	
}


?>