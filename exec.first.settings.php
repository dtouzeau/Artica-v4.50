<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.artica.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');
include_once(dirname(__FILE__).'/ressources/class.monit.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



if($argv[1]=="--monit"){monit();exit();}

$sock=new sockets();
$ArticaFirstWizard=$sock->GET_INFO('ArticaFirstWizard');
if($ArticaFirstWizard==1){exit();}




$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==1){
    $ldap=new clladp();
	$ldap->AddDomainEntity($domainname,"$domainname");
	$main=new main_cf();
	$main->save_conf();
	$main->save_conf_to_server();
}

if($users->cyrus_imapd_installed){
	$cyr=new cyrus();
	$cyr->CreateMailbox("postmaster");
}

$sock->SET_INFO("ArticaFirstWizard",1);
exit();



function events($text){
		$logFile="/var/log/artica-postfix/artica-status.debug";
		$pid=getmypid();
		$date=date('Y-m-d H:i:s');
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		$page=CurrentPageName();
		@fwrite($f, "$date [$pid] $page $text\n");
		@fclose($f);	
		}

function monit(){
	$monit=new monit();
	$monit->save();
}

?>