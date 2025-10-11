<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="iRedAPD Policy Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}




function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	sleep(1);
	start(true);
	
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$postconf=$unix->find_program("postconf");
	$Masterbin="/opt/iRedAPD/iredapd.py";

	if(!is_file($postconf)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, postfix not installed\n";}
		return;
	}
	
	if(!is_file($Masterbin)){
		$php=$unix->LOCATE_PHP5_BIN();
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		shell_exec("$php /usr/share/artica-postfix/exec.iredmail.install.php");
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	
	$pid=PID_NUM();
	if(!is_file("/var/log/iredapd.log")){@touch("/var/log/iredapd.log");}
	@chown("/var/log/iredapd.log", "postfix");
	@chgrp("/var/log/iredapd.log", "postfix");
	
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	

	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$python=$unix->find_program("python");
	$f[]="$python $Masterbin";
	buildConfig();
	
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);
	
	
	

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function buildConfig(){
	
	$q=new mysql();
	$ldap=new clladp();
	$f[]="# Listen address and port.";
	$f[]="listen_address = '127.0.0.1'";
	$f[]="listen_port = 7777";
	$f[]="";
	$f[]="# Run as a low privileged user.";
	$f[]="# If you don't want to create one, you can try 'nobody'.";
	$f[]="run_as_user = 'postfix'";
	$f[]="";
	$f[]="# Path to pid file.";
	$f[]="pid_file = '/var/run/iredapd.pid'";
	$f[]="";
	$f[]="# Path to log file.";
	$f[]="# Set 'log_file = /dev/null' if you don't want to keep the log.";
	$f[]="log_file = '/var/log/iredapd.log'";
	$f[]="";
	$f[]="# Log level: info, debug.";
	$f[]="log_level = 'info'";
	$f[]="";
	$f[]="# Backend: ldap, mysql, pgsql.";
	$f[]="backend = 'ldap'";
	$f[]="";
	$f[]="# Enabled plugins.";
	$f[]="# - Plugin name is file name which placed under 'plugins/' directory,";
	$f[]="#   without file extension '.py'.";
	$f[]="# - Plugin names MUST be seperated by comma.";
	$f[]="#plugins = ['sql_log_smtp_session_info','ldap_maillist_access_policy', 'ldap_amavisd_block_blacklisted_senders', 'ldap_recipient_restrictions']";
	$f[]="plugins = ['ldap_amavisd_block_blacklisted_senders','sql_log_smtp_session_info']";
	$f[]="####################";
	$f[]="# For ldap backend.";
	$f[]="#";
	$f[]="# LDAP server setting.";
	$f[]="# Uri must starts with ldap:// or ldaps:// (TLS/SSL).";
	$f[]="#";
	$f[]="# Tip: You can get binddn, bindpw from /etc/postfix/ldap/*.cf.";
	$f[]="#";
	$f[]="ldap_uri = 'ldap://$ldap->ldap_host:$ldap->ldap_port'";
	$f[]="ldap_basedn = 'dc=organizations,$ldap->suffix'";
	$f[]="ldap_binddn = 'cn=$ldap->ldap_admin,$ldap->suffix'";
	$f[]="ldap_bindpw = '$ldap->ldap_password'";
	$f[]="";
	$f[]="#";
	$f[]="# For MySQL and PostgreSQL backends.";
	$f[]="#";
	$f[]="sql_server = '$q->mysql_server'";
	$f[]="sql_port = '$q->mysql_port'";
	$f[]="sql_db = 'vmail'";
	$f[]="sql_user = '$q->mysql_admin'";
	$f[]="sql_password = '$q->mysql_password'";	
	@file_put_contents("/opt/iRedAPD/settings.py", @implode("\n", $f));
	
	if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /opt/iRedAPD/settings.py done\n";}



	$data=explode("\n",@file_get_contents("/opt/iRedAPD/plugins/sql_log_smtp_session_info.py"));
	
	if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ldap_amavisd_block_blacklisted_senders ". count($data)." lines\n";}
	
	$SENDER_SEARCH_ATTRLIST=false;
	$RECIPIENT_SEARCH_ATTRLIST=false;
	$REQUIRE_LOCAL_SENDER=false;
	$REQUIRE_LOCAL_RECIPIENT=false;
	foreach ($data as $index=>$line){
	
		if(preg_match("#SENDER_SEARCH_ATTRLIST.*?\[#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SENDER_SEARCH_ATTRLIST OK\n";}
			$SENDER_SEARCH_ATTRLIST=true;
			continue;

		}
		
		if(preg_match("#RECIPIENT_SEARCH_ATTRLIST.*?\[#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} RECIPIENT_SEARCH_ATTRLIST OK\n";}
			$RECIPIENT_SEARCH_ATTRLIST=true;
			continue;
		
		}

		if(preg_match("#REQUIRE_LOCAL_SENDER#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} REQUIRE_LOCAL_SENDER OK\n";}
			$REQUIRE_LOCAL_SENDER=true;
			continue;			
		}
		if(preg_match("#REQUIRE_LOCAL_RECIPIENT#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} REQUIRE_LOCAL_RECIPIENT OK\n";}
			$REQUIRE_LOCAL_RECIPIENT=true;
			continue;
		}	
	}	
	
	$filedata=array();
	
	if(!$SENDER_SEARCH_ATTRLIST){
		$filedata[]="SENDER_SEARCH_ATTRLIST = []";
		@file_put_contents("/opt/iRedAPD/plugins/sql_log_smtp_session_info.py", $filedata);
	}
	if(!$RECIPIENT_SEARCH_ATTRLIST){
		$filedata[]="RECIPIENT_SEARCH_ATTRLIST = []";
		@file_put_contents("/opt/iRedAPD/plugins/sql_log_smtp_session_info.py", $filedata);
	}
	if(!$REQUIRE_LOCAL_SENDER){
		$filedata[]="REQUIRE_LOCAL_SENDER = False";
		@file_put_contents("/opt/iRedAPD/plugins/sql_log_smtp_session_info.py", $filedata);
	}	
	if(!$REQUIRE_LOCAL_RECIPIENT){
		$filedata[]="REQUIRE_LOCAL_RECIPIENT = False";
		
	}	
	if(count($filedata)>0){
		@file_put_contents("/opt/iRedAPD/plugins/sql_log_smtp_session_info.py", @implode("\n",$filedata)."\n".@implode("\n", $data));
	}
	
	
	$data=explode("\n",@file_get_contents("/opt/iRedAPD/plugins/ldap_amavisd_block_blacklisted_senders.py"));
	foreach ($data as $index=>$line){
	
		if(preg_match("#^REQUIRE_LOCAL_RECIPIENT#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CHANGE REQUIRE_LOCAL_RECIPIENT = True\n";}
			$data[$index]="REQUIRE_LOCAL_RECIPIENT = True";
			continue;
	
		}
		
	}
	@file_put_contents("/opt/iRedAPD/plugins/ldap_amavisd_block_blacklisted_senders.py", @implode("\n", $data));
	
	$data=explode("\n",@file_get_contents("/opt/iRedAPD/libs/ldaplib/conn_utils.py"));
	$save=false;
	foreach ($data as $index=>$line){
		if(strpos($line, "mailUser")>0){
			$save=true;
			$data[$index]=str_replace("mailUser", "userAccount", $data[$index]);
		}
		
		if(strpos($line, "shadowAddress")>0){
			$save=true;
			$data[$index]=str_replace("shadowAddress", "mailAlias", $data[$index]);
		}
		
	}
	if($save){
		if($GLOBALS["OUTPUT"]){echo "Reconfiguring.: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} sql_log_smtp_session_info OK\n";}
		@file_put_contents("/opt/iRedAPD/libs/ldaplib/conn_utils.py", @implode("\n", $data));
	}
	
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function PID_NUM(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/iredapd.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("python.*?/opt/iRedAPD/iredapd");
	
}
?>