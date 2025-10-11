<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.kerb.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");



xstart();

function xstart(){

	$unix=new unix();
	$TimeExec="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$PidFile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	
	$pid=$unix->get_pid_from_file($PidFile);
	if($unix->process_exists($pid)){
		$Time=$unix->PROCESS_TIME_INT($pid);
		if($Time>30){
			squid_admin_mysql(0, "Killing all Refresh Active Directory: Task exceed 30mn, current={$Time}mn [action=kill]", null,__FILE__,__LINE__);
			$unix->KILL_PROCESS($pid,9);
		}else{
			squid_admin_mysql(1,"Refresh Active Directory: Already running pid $pid since {$Time}mn",null,__FILE__,__LINE__);
			return;
		}
		
	}
	
	$xtime=$unix->file_time_min($TimeExec);
	if($xtime<5){
		squid_admin_mysql(0, "Refresh Active Directory: Failed (need max each 5mn current is {$xtime}mn)", null,__FILE__,__LINE__);
		exit();
	}
	@file_put_contents($PidFile, getmypid());
	@unlink($TimeExec);
	@file_put_contents($TimeExec, time());
	
	$php=$unix->LOCATE_PHP5_BIN();
	$tmpfile=$unix->FILE_TEMP();
	$t=time();
	shell_exec("$php /usr/share/artica-postfix/exec.nltm.connect.php >$tmpfile 2>&1");
	$took=$unix->distanceOfTimeInWords($t,time());
	squid_admin_mysql(1,"Refresh Active Directory: Done {took} $took",@file_get_contents($tmpfile),__FILE__,__LINE__);
	
	if(is_file("/etc/init.d/winbind")){
		system("/etc/init.d/winbind restart --force");
		$tail=$unix->find_program("tail");
		exec("$tail -n 50 /var/log/samba/log.winbindd 2>&1",$results);
		squid_admin_mysql(1,"Refresh Active Directory: Restarting Winbind daemon done.",@implode("\n",$results),__FILE__,__LINE__);
	}
	
	$LOCATE_SQUID_BIN=$unix->LOCATE_SQUID_BIN();
	if(is_file($LOCATE_SQUID_BIN)){
		$results=array();
		exec("/usr/sbin/artica-phpfpm-service -reload-proxy 2>&1",$results);
		squid_admin_mysql(1,"Refresh Active Directory: {reloading_proxy_service} done.",@implode("\n",$results),__FILE__,__LINE__);
	}
	
}
