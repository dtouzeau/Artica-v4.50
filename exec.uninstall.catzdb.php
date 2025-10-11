<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["WORKDIR"]="/opt/articatech";
$GLOBALS["SERV_NAME"]="categories-db";
$GLOBALS["MYSQL_PID"]="/var/run/articadb.pid";
$GLOBALS["MYSQL_SOCKET"]="/var/run/mysqld/articadb.sock";
$GLOBALS["MYPID"]="/etc/artica-postfix/pids/{$GLOBALS["SERV_NAME"]}.pid";
$GLOBALS["MYSQL_ERRMSG"]="mysql/share/english/errmsg.sys";
$GLOBALS["mysql_install_db"]=false;
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
include_once(dirname(__FILE__).'/ressources/class.mysql.services.inc');


$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
$pid=$unix->get_pid_from_file($pidfile);
$rm=$unix->find_program("rm");
$nohup=$unix->find_program("nohup");
$php=$unix->LOCATE_PHP5_BIN();
if($unix->process_exists($pid,basename(__FILE__))){
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
	return;
}

$sock=new sockets();

if(is_file("/etc/init.d/categories-db")){
	shell_exec("/etc/init.d/categories-db stop");
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f categories-db remove >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del categories-db >/dev/null 2>&1");
	}
	@unlink("/etc/init.d/categories-db");
}

$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
if(is_dir($ArticaDBPath)){

	shell_exec("$rm -rf $ArticaDBPath");
	
}



