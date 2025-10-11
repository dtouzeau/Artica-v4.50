<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');


$unix=new unix();
$users=new usersMenus();
$POSTFIX_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("POSTFIX_INSTALLED"));
if($POSTFIX_INSTALLED==0){exit();}


$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){
	squid_admin_mysql(2, "Already instance executed, aborting\n".@implode("\n", $results), "MAIN", __FILE__,"postfix");
	exit();}
$pid=getmypid();
file_put_contents($pidfile,$pid);




$php5=$unix->LOCATE_PHP5_BIN();

$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}


if($EnablePostfixMultiInstance==0){
	$t=time();
	exec("$php5 /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "{reconfigure} postfix done {took} $took\n".@implode("\n", $results), "MAIN", __FILE__,
	 __LINE__, "postfix");
}

