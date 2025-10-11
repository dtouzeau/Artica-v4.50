<?php
exit();
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
include_once(dirname(__FILE__).'/ressources/class.amavidb.inc');

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=true;initd();exit();}
if($argv[1]=="--changemysqldir"){changemysqldir($argv[2]);exit();}
if($argv[1]=="--databasesize"){databasesize($GLOBALS["FORCE"]);exit();}
if($argv[1]=="--restorefrom"){RestoreFromBackup($argv[2]);exit();}






function start(){
	$unix=new unix();
	$sock=new sockets();
	if(!isset($GLOBALS["MYSQL_BIN_PATH"])){$GLOBALS["MYSQL_BIN_PATH"]=null;}
	if(!isset($GLOBALS["mysql_install_db"])){$GLOBALS["mysql_install_db"]=true;}
	$pidfile=$GLOBALS["MYPID"];
	
	$WORKDIR=$GLOBALS["WORKDIR"];
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$mysql_pid_file=$GLOBALS["MYSQL_PID"];
	$MYSQL_SOCKET=$GLOBALS["MYSQL_SOCKET"];
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$GLOBALS["WORKDIR"]=$ArticaDBPath;
	$GLOBALS["MYSQL_BIN_PATH"]="{$GLOBALS["WORKDIR"]}/bin/articadb";
	
	
	$OutputBinLog=$unix->FILE_TEMP();
	$mysqlserv=new mysql_services();
	$mysqlserv->WORKDIR=$GLOBALS["WORKDIR"];
	$mysqlserv->MYSQL_PID_FILE=$mysql_pid_file;
	$mysqlserv->MYSQL_SOCKET=$MYSQL_SOCKET;
	$mysqlserv->SERV_NAME=$SERV_NAME;
	$mysqlserv->TokenParams="MySQLCatzParams";
	$mysqlserv->INSTALL_DATABASE=$GLOBALS["mysql_install_db"];
	$mysqlserv->MYSQL_BIN_DAEMON_PATH=$GLOBALS["MYSQL_BIN_PATH"];
	$mysqlserv->MYSQL_ERRMSG=$GLOBALS["MYSQL_ERRMSG"];
	$mysqlserv->InnoDB=false;
	$mysqlserv->OutputBinLog=$OutputBinLog;
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	$DisableArticaProxyStatistics=$sock->GET_INFO("DisableArticaProxyStatistics");
	$SquidActHasReverse=$sock->GET_INFO("SquidActHasReverse");
	
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if(!is_numeric($SquidActHasReverse)){$SquidActHasReverse=0;}
	if($unix->isNGnx()){$SquidActHasReverse=0;}
	

	$EnableArticaDB=1;
	if(is_file('/etc/artica-postfix/WEBSTATS_APPLIANCE')){$EnableWebProxyStatsAppliance=1;}
	if(is_file('/etc/artica-postfix/SQUID_REVERSE_APPLIANCE')){$EnableArticaDB=0;}
	if($DisableArticaProxyStatistics==1){$EnableArticaDB=0;}
	if($EnableWebProxyStatsAppliance==1){$EnableArticaDB=1;}
	if($SquidActHasReverse==1){$EnableArticaDB=0;}	
	
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Starting Task Already running PID $pid since {$time}mn\n";}
		return;
	}
		
	@file_put_contents($pidfile, getmypid());
	
	$mysql_install_db=$unix->find_program("mysql_install_db");

	
	if($EnableArticaDB==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is disabled...\n";}
		stop();
		die(0);		
		
	}
		
	if($GLOBALS["MYSQL_BIN_PATH"]<>null){
		$mysqld=$GLOBALS["MYSQL_BIN_PATH"];}
	else{
		$mysqld=$unix->find_program("mysqld");
	}
	if(!is_file($mysqld)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME is not installed...\n";}
		return;
	}	
	
	if($GLOBALS["mysql_install_db"]){
		if(!is_file($mysql_install_db)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME mysql_install_db no such binary...\n";}
			return;
		}	
	}
	
	
	$pid=DBPID();
	
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL Database Engine already running pid $pid since {$time}mn\n";}
		return;
	}	
	
	if(!is_file("{$GLOBALS["WORKDIR"]}/VERSION")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME Corrupted database, launch updates...\n";}
	}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME writing init.d\n";}
	initd();
	$TMP=$unix->FILE_TEMP();
	$cmdline=$mysqlserv->BuildParams();
	
	$nohup=$unix->find_program("nohup");
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}

	

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME Starting MySQL daemon ($SERV_NAME)\n";}
	shell_exec("$nohup $cmdline >$TMP 2>&1 &");
	sleep(1);
	for($i=0;$i<5;$i++){
		$pid=DBPID();
		if($unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL daemon ($SERV_NAME) started pid .$pid..\n";}break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $SERV_NAME MySQL daemon wait $i/5\n";}
		sleep(1);
	}	
	sleep(1);
	$pid=DBPID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL daemon ($SERV_NAME) failed to start\n";}
		$f=explode("\n",@file_get_contents($TMP));
		$repair=false;
		foreach ($f as $num=>$ligne){
			if(trim($ligne)==null){continue;}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME $ligne\n";}
			
		}
		
		$mysqlserv->CheckOutputErrors($TMP);
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME MySQL daemon ($SERV_NAME) success\n";}
	
	}
	if(!$unix->process_exists($pid)){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]:$SERV_NAME $cmdline\n";}}
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize");
}


function stop(){
	$SERV_NAME=$GLOBALS["SERV_NAME"];
	$MYSQL_SOCKET=$GLOBALS["MYSQL_SOCKET"];
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]:$SERV_NAME Already task running PID $pid since {$time}mn\n";}
		return;
	}
	
	$sock=new sockets();
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$GLOBALS["WORKDIR"]=$ArticaDBPath;
	$GLOBALS["MYSQL_BIN_PATH"]="{$GLOBALS["WORKDIR"]}/bin/articadb";

	$pid=DBPID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) already stopped...\n";}
		return;
	}	
	
	
	$time=$unix->PROCCESS_TIME_MIN($pid);
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping MySQL Daemon ($SERV_NAME) with a ttl of {$time}mn\n";}
	$mysqladmin=$unix->find_program("mysqladmin");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Stopping MySQL Daemon ($SERV_NAME) smoothly...\n";}
	$cmd="$mysqladmin --socket=$MYSQL_SOCKET  --protocol=socket --user=root shutdown >/dev/null";
	shell_exec($cmd);

	$pid=DBPID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		return;
	}	
	
	$kill=$unix->find_program("kill");
	for($i=0;$i<10;$i++){
		$pid=DBPID();
		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) kill pid $pid..\n";}
			unix_system_kill_force($pid);
		}else{
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) wait $i/10\n";}
		sleep(1);
	}	
	$pid=DBPID();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) success...\n";}
		@unlink($MYSQL_SOCKET);
		return;
	}	
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: MySQL daemon ($SERV_NAME) Failed...\n";}
}

function DBPID(){
	$unix=new unix();
	
	
	
	$pid=$unix->get_pid_from_file($GLOBALS["MYSQL_PID"]);
	
	if($GLOBALS["VERBOSE"]){echo "Pid: {$GLOBALS["MYSQL_PID"]} -> $pid\n";}
	
	if($unix->process_exists($pid)){return $pid;}

	if(!isset($GLOBALS["MYSQL_BIN_PATH"])){
		if($GLOBALS["VERBOSE"]){echo "MYSQL_BIN_PATH Not set\n";}
		return;}
	if($GLOBALS["MYSQL_BIN_PATH"]==null){return;}
	$pgrep=$unix->find_program("pgrep");
	
	if($GLOBALS["VERBOSE"]){echo "$pgrep -l -f \"{$GLOBALS["MYSQL_BIN_PATH"]}\" 2>&1\n";}
	
	exec("$pgrep -l -f \"{$GLOBALS["MYSQL_BIN_PATH"]}\" 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#pgrep#", $ligne)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $ligne,$re)){continue;}
		return $re[1];
	}
	
}




function changemysqldir($dir){
	
	$sock=new sockets();
	$unix=new unix();
	$pidfile=$GLOBALS["MYPID"];
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already task running PID $pid since {$time}mn\n";}
		return;
	}
	
	@file_put_contents($pidfile, getmypid());	

	if(substr($dir, strlen($dir)-1,1)=="/"){$dir=substr($dir,0,strlen($dir)-1);}
	
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$dirCMD=$unix->shellEscapeChars($dir);
	if($dir==$ArticaDBPath){return;}
	@mkdir($dir,0755,true);
	
	$sock->SET_INFO("ArticaDBPath", $dir);
	$Size=$unix->DIRSIZE_BYTES($ArticaDBPath);
	echo "Moving......: [INIT]: Copy $ArticaDBPath content to next dir size=`$Size`\nPlease wait...\n";
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	system("$cp -rfv $ArticaDBPath/* $dirCMD/");
	$Size2=$unix->DIRSIZE_BYTES($dir);
	if($Size2<$Size){echo "Moving......: [INIT]: Copy error $Size2 is less than original size ($Size)\n";}
	
	echo "Moving......: [INIT]: Stamp DB to $dir\n";
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaDBPath", $dir);
	echo "Moving......: [INIT]: Restarting MySQL database engine...\n";
	system("/etc/init.d/categories-db restart");
	echo "Moving......: [INIT]: Removing old data\n";
	shell_exec("$nohup $rm -rf $ArticaDBPath/* >/dev/null 2>&1 &");
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." ".__FILE__." --databasesize --force");
    $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
	echo "Moving......: [INIT]: DONE\n";
}
function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 ".__FILE__." --stop");
	shell_exec("$php5 ".__FILE__." --start");
	
}


function initd(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          {$GLOBALS["SERV_NAME"]}";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Artica Categories MySQL Engine database";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Artica Categories MySQL Engine database";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php ". __FILE__." --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php ". __FILE__." --stop --byinitd --force \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	
	$f[]="    $php ". __FILE__." --stop --byinitd --force \$2 \$3";
	$f[]="    $php ". __FILE__." --start --byinitd \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} {ldap|} (+ 'debug' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	@file_put_contents("/etc/init.d/{$GLOBALS["SERV_NAME"]}", @implode("\n", $f));
	@chmod("/etc/init.d/{$GLOBALS["SERV_NAME"]}",0755);
	echo "Starting......: ".date("H:i:s")." [INIT]:{$GLOBALS["SERV_NAME"]} /etc/init.d/{$GLOBALS["SERV_NAME"]} done..\n";
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f {$GLOBALS["SERV_NAME"]} defaults >/dev/null 2>&1");
		
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add {$GLOBALS["SERV_NAME"]} >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 {$GLOBALS["SERV_NAME"]} on >/dev/null 2>&1");
	}
}

function databasesize($force=false){
	
	
	$unix=new unix();
	$sock=new sockets();

	$arrayfile=PROGRESS_DIR."/{$GLOBALS["SERV_NAME"]}.size.db";
	$pidfile="/etc/artica-postfix/pids/{$GLOBALS["SERV_NAME"]}-databasesize.pid";
	
	if(!$force){
		
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			return;
		}
	
		@file_put_contents($pidfile, getmypid());
		$time=$unix->file_time_min($arrayfile);
		if($time<20){return;}
	}
	
	$ArticaDBPath="/home/artica/categories_databases";
	
	$GLOBALS["WORKDIR"]=$ArticaDBPath;	
	
	$dir=$GLOBALS["WORKDIR"];
	if(is_link($dir)){$dir=readlink($dir);}
	$unix=new unix();
	$sizbytes=$unix->DIRSIZE_BYTES($dir);
	$dir=$unix->shellEscapeChars($dir);
	$df=$unix->find_program("df");
	$array["DBSIZE"]=$sizbytes/1024;
	exec("$df -B K $dir 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#^.*?\s+([0-9A-Z\.]+)K\s+([0-9A-Z\.]+)K\s+([0-9A-Z\.]+)K\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
			$array["SIZE"]=$re[1];
			$array["USED"]=$re[2];
			$array["AIVA"]=$re[3];
			$array["POURC"]=$re[4];
			$array["MOUNTED"]=$re[5];
			break;
		}
	}
	$results=array();
	exec("$df -i $dir 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#^.*?\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9A-Z\.]+)\s+([0-9\.]+)%\s+(.+)#", $ligne,$re)){
			$array["ISIZE"]=$re[1];
			$array["IUSED"]=$re[2];
			$array["IAIVA"]=$re[3];
			$array["IPOURC"]=$re[4];
			break;
		}
	}	

	if($GLOBALS["VERBOSE"]) {print_r($array);}
	
	@unlink($arrayfile);
	@file_put_contents($arrayfile, serialize($array));
	if($GLOBALS["VERBOSE"]) {echo "Saving $arrayfile...\n";}
	@chmod($arrayfile, 0755);
	
}
?>