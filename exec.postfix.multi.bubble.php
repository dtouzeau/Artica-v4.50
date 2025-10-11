<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.iptables.exec.rules.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.main_cf.inc');
include_once(dirname(__FILE__) . '/ressources/class.assp-multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');


if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
$unix=new unix();
$sock=new sockets();
$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
if($EnablePostfixMultiInstance==0){echo "Starting......: ".date("H:i:s")." Postfix Bubble multiple instance is disabled !\n";iptables_delete_rules();return;}

$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
if($unix->process_exists(@file_get_contents($pidfile),basename(__FILE__))){echo "Starting......: ".date("H:i:s")." Postfix Bubble multiple already executed PID ". @file_get_contents($pidfile)."\n";exit();}
$pid=getmypid();
echo "Starting......: ".date("H:i:s")." Postfix Bubble multiple running $pid\n";
file_put_contents($pidfile,$pid);



$GLOBALS["iptables"]=$unix->find_program("iptables");


StartBubble();



function StartBubble(){
		$q=new mysql();
		if(!$q->test_mysqli_connection()){echo "Starting......: ".date("H:i:s")." Postfix Bubble Mysql is not ready aborting...\n";return;}		
		$ip=new iptables_exec();
		$ip->buildrules();		
}

