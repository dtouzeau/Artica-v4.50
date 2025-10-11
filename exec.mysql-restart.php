<?php
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$pid=@file_get_contents($pidfile);
$unix=new unix();
if($unix->process_exists($pid,(basename(__FILE__)))){
	squid_admin_mysql(2, "Starting......: ".date("H:i:s")."Already executed PID $pid...", __FUNCTION__, __FILE__, __LINE__, "mysql");
	exit();
}
@file_put_contents($pidfile, getmypid());

$t=time();
$unix=new unix();
$unix->ToSyslog("Restarting MySQL service");
squid_admin_mysql(1,"Restarting MySQL service...", null,__FILE__,__LINE__);
exec("/etc/init.d/mysql restart --framework=".__FILE__." 2>&1",$results);
$took=$unix->distanceOfTimeInWords($t,time());
squid_admin_mysql(2, "Restarting MySQL service done {took} $took:\n".@implode("\n", $results), __FUNCTION__, __FILE__, __LINE__, "mysql");

