<?php
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
	
	include_once(dirname(__FILE__).'/ressources/class.squid.inc');
	include_once(dirname(__FILE__).'/ressources/class.samba.inc');			
	
	
shell_exec("/etc/init.d/artica-process1 start");
$users=new usersMenus();
$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==1){
	

	
	$main=new main_cf();
	$main->save_conf();
	$main->save_conf_to_server();
	system('/etc/init.d/postfix restart');
	
	if($users->cyrus_imapd_installed){
		system('/usr/share/artica-postfix/bin/artica-install --cyrus-checkconfig');
		system('/etc/init.d/cyrus-imapd restart &');
	}
	
}

if($users->SQUID_INSTALLED){
	$squid=new squidbee();
	$squid->SaveToLdap();
	$squid->SaveToServer();
	
}

if($users->SAMBA_INSTALLED){
	$smb=new samba();
	$smb->SaveToLdap();
	system('/usr/share/artica-postfix/bin/artica-install --samba-reconfigure');
	system('/etc/init.d/samba restart &');
}
	
	
?>