<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


xtsart();


function xtsart(){
	
	system("clear");
	echo "This operation will reset all parameters.\n";
	echo "Your server will restored to default settings\n";
	echo "All data will be removed\n";
	echo "Type Y key if your are agree or Q to exit\n";
	
	$answer=trim(strtolower(fgets(STDIN)));
	if($answer=="q"){die(0);}
	if($answer=="y"){xreset();return;}
	xtsart();
	
	
	
}

function xreset(){
	system("clear");
	echo "{warning} all data will be erased..\n";
	echo "Type Y key if your are agree or Q to exit\n";
	$answer=trim(strtolower(fgets(STDIN)));
	if($answer=="q"){die(0);}
	if($answer=="y"){reset2();return;}
	xtsart();
	
}

function reset2(){
	system("clear");
	echo "Remove databases\n";
	
	$q=new mysql();
	echo "Remove database settings\n";
	$q->DELETE_DATABASE("artica_backup");
	echo "Remove database events\n";
	$q->DELETE_DATABASE("artica_events");
	echo "Remove database Proxy\n";
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("DROP DATABASE `squidlogs`");
	echo "Remove Artica settings Proxy\n";
	
	$unix=new unix();
	$files=$unix->DirFiles("/etc/artica-postfix/settings/Daemons");
	while (list ($filename, $value) = each ($files) ){
		$fulename="/etc/artica-postfix/settings/Daemons/$filename";
		echo "Removing $filename\n";
		@unlink($fulename);
	}
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/ProxyUseArticaDB",0);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/StatsPerfsSquidAnswered",1);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/CacheManagement2",1);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/EnablePHPFPM",0);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/EnableArticaFrontEndToNGninx",0);
	
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/EnableArticaFrontEndToApache",1);
	@file_put_contents("/root/build/etc/artica-postfix/settings/Daemons/EnableNginx",0);
	echo "Restarting Web Console...\n";
	system('/etc/init.d/artica-webconsole restart');
	
	system("clear");
	echo "All data has been erased..\n";
	echo "Type Enter key to exit\n";
	$answer=trim(strtolower(fgets(STDIN)));
	exit();
	
}
