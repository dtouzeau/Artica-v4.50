<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.settings.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}


if($argv[1]=="--app"){remove(trim($argv[2]));exit();}



function remove($APP){
	
	if($APP=="APP_POSTFIX"){remove_postfix();return;}
	if($APP=="APP_SCANNED_ONLY"){APP_SCANNED_ONLY_REMOVE();return;}
	if($APP=="APP_DNSMASQ"){APP_DNSMASQ_REMOVE();return;}
	if($APP=="APP_SAMBA"){APP_SAMBA_REMOVE();return;}
	if($APP=="APP_SAMBA35"){APP_SAMBA_REMOVE();return;}
	if($APP=="APP_SAMBA36"){APP_SAMBA_REMOVE();return;}
	
}

function APP_POSTFIX_REMOVE(){
	$unix=new unix();
	$apt=$unix->find_program("apt-get");
	
	if(is_file($apt)){
		shell_exec("/etc/init.d/postfix stop");
		shell_exec("apt-get remove postfix* --purge -y");
		shell_exec("apt-get remove spamassassin --purge -y");
		shell_exec("apt-get remove amavisd-new --purge -y");
		return;
		
	}
	
	
	

	
	
	
}

function APP_DNSMASQ_REMOVE(){
	$unix=new unix();
	$apt=$unix->find_program("apt-get");
	$dnsmasq=$unix->find_program("dnsmasq");
	shell_exec("/etc/init.d/artica-postfix stop dnsmasq");
	if(is_file($apt)){
		shell_exec("/etc/init.d/artica-postfix stop dnsmasq");
		shell_exec("apt-get remove dnsmasq --purge -y");
		@unlink($dnsmasq);
		return;
		
	}	
	
}

function APP_SAMBA_REMOVE(){
	$unix=new unix();
	$apt=$unix->find_program("apt-get");
	
	shell_exec("/etc/init.d/artica-postfix stop samba");
	if(is_file($apt)){
		shell_exec("apt-get remove samba --purge -y");
		shell_exec("apt-get remove winbind --purge -y");
	}	
	
	shell_exec("/usr/share/artica-postfix/bin/artica-make --remove-samba");
}


function APP_SCANNED_ONLY_REMOVE(){
	shell_exec("/etc/init.d/artica-postfix stop samba");
	@unlink("/usr/sbin/scannedonlyd_clamav");
	shell_exec("/etc/init.d/artica-postfix start samba");	
	
}