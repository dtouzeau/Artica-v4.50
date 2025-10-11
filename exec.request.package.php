<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["NOPID"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');



build($argv[1]);


function build($uri){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){exit();}
	@file_put_contents($pidfile, getmypid());
	$echo=$unix->find_program("echo");
	$curl=$unix->find_program("curl");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$curl=$unix->find_program("curl");
	@unlink("/var/log/squid/request.debug");
	$DirFinal="/usr/share/artica-postfix/ressources/support/".time();
	@mkdir($DirFinal,0755,true);
	$SquidMgrListenPort=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
	shell_exec("$echo \"Proxy address 127.0.0.1:$SquidMgrListenPort\" > /var/log/squid/request.debug 2>&1");
	shell_exec("$echo \"Url to test: $uri \" >> /var/log/squid/request.debug 2>&1");
	
	progress("{rotate_logs_files}",30);
	shell_exec("$echo \"Rotate & turn to debug... \" >> /var/log/squid/request.debug 2>&1");
	@copy("/var/log/squid/access.log", "/var/log/squid/access.log.".time());
	shell_exec("$squidbin -k rotate >> /var/log/squid/request.debug 2>&1");
	progress("{turn_to_debug}",35);
	shell_exec("$squidbin -k debug >/dev/null 2>&1");
	sleep(4);
	progress("{send_query}",40);
	$cmd="$curl --head --verbose --trace-time --proxy http://127.0.0.1:$SquidMgrListenPort --url $uri >> /var/log/squid/request.debug 2>&1";
	shell_exec("$echo \"$cmd\" >> /var/log/squid/request.debug 2>&1");
	shell_exec($cmd);
	sleep(4);
	progress("{return_back_to_normal}",40);
	LogsThisDebug("************************************************************");
	squid_admin_mysql(1, "Reconfiguring proxy service",null,__FILE__,__LINE__);
	shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__)."  >> /var/log/squid/request.debug 2>&1");
	LogsThisDebug("************************************************************");
	sleep(1);
	@copy("/var/log/squid/cache.log", "$DirFinal/cache.log");
		
	progress("{compressing_package}",90);
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$filename="request.tar.gz";
	
	@unlink("/usr/share/artica-postfix/ressources/support/$filename");
	@copy("/var/log/squid/request.debug", "$DirFinal/request.debug");
	@unlink("/var/log/squid/request.debug");
	chdir($DirFinal);
	$cmd="$tar -cvzf /usr/share/artica-postfix/ressources/support/$filename * 2>&1";
	exec($cmd,$results);
	
	@chmod("/usr/share/artica-postfix/ressources/support/$filename", 0755);
	shell_exec("$rm -rf $DirFinal");
	LogsThisDebug("*********************************************************");
	
	$c=0;
	progress("{success}",100);
	
}

function LogsThisDebug($text){
	$date=date("H:i:s");
	$f = @fopen("/var/log/squid/request.debug", 'a');
	@fwrite($f,"$date $text\n");
	@fclose($f);
}

function progress($title,$perc){
	$array=array($title,$perc);
	@file_put_contents("/usr/share/artica-postfix/ressources/support/request.progress",serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/support/request.progress", 0755);
}
