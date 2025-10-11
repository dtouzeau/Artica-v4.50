<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/mysql.status.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');


$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["BY_SOCKET_FAILED"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BY_FRAMEWORK"]=null;
$GLOBALS["BY_WIZARD"]=false;
$GLOBALS["CMDLINE"]=implode(" ",$argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--socketfailed#",implode(" ",$argv))){$GLOBALS["BY_SOCKET_FAILED"]=true;}
if(preg_match("#--framework=(.+?)$#",implode(" ",$argv),$re)){$GLOBALS["BY_FRAMEWORK"]=$re[1];}
if(preg_match("#--bywizard#",implode(" ",$argv),$re)){$GLOBALS["BY_WIZARD"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;$GLOBALS["BY_FRAMEWORK"]="Manual";$GLOBALS["PROGRESS"]=true;$GLOBALS["BY_WIZARD"]=true;}

if($argv[1]=="--start"){SERVICE_START();die(0);}
if($argv[1]=="--ttl"){SERVICE_TTL();die(0);}
if($argv[1]=="--stop"){SERVICE_STOP();die(0);}
if($argv[1]=="--restart"){SERVICE_RESTART();die(0);}
if($argv[1]=="--recovery"){restart_reco();exit();}
if($argv[1]=="--engines"){status_all_mysql_engines();exit();}
if($argv[1]=="--test-sock"){test_sockets();exit();}
if($argv[1]=="--monit"){install_monit();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--mysql-safe"){mysqld_safe_for_root();exit();}
if($argv[1]=="--mysql-safe-cmd"){mysqlsafe_cmds();exit();}




function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/mysqld/mysqld.pid");
	if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: /var/run/mysqld/mysqld.pid -> \"$pid\"\n";}
	if(!$unix->process_exists($pid)){
		$mysqlbin=$unix->LOCATE_mysqld_bin();
		$pgrep=$unix->find_program("pgrep");
		$lsof=$unix->find_program("lsof");
		if(is_file($pgrep)){
			if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1\n";}
			exec("$pgrep -l -f \"(mysqld|mariadbd).*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1",$results);
			
			foreach ($results as $num=>$line){
				if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $line\n";}
				if(preg_match("#pgrep#",$line)){continue;}
			 	if(preg_match("#^([0-9]+)\s+#", $line,$re)){
			 		@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
			 		return $re[1];
			 	}
			}
		}
		$results=array();
		exec("$lsof -Pnl +M -i TCP:3306 2>&1",$results);
		foreach ($results as $num=>$line){
			if(preg_match("#mysqld\s+([0-9]+).*?TCP.*?:3306#",$line,$re)){
				@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
				return $re[1];
			}
			
		}
	}
	return $pid;
	
}
function build_progress($pourc,$text):bool{
    $unix=new unix();
    echo "Starting......: ".date("H:i:s")." ($pourc%) $text\n";
    return $unix->framework_progress($pourc,$text,"mysql.restart.progress");
}


function restart_reco(){
	$unix=new unix();

	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "Starting......: ".date("H:i:s")." MySQL this script is already executed PID: $pid since {$time}Mn\n";
			if($time<10){if(!$GLOBALS["FORCE"]){return;}}
			unix_system_kill_force($pid);
		}
		@file_put_contents($pidfile, getmypid());	
	
    $GLOBALS["RECOVERY"]=3;
    echo "Stopping MySQL...............: RECOVERY MODE\n";
    SERVICE_STOP(true);
    echo "Starting......: ".date("H:i:s")." MySQL RECOVERY MODE\n";
    SERVICE_START(false,true);
    echo "Starting......: ".date("H:i:s")." Sleeping 10 seconds\n";
    sleep(10);
    echo "Stopping MySQL...............: RECOVERY MODE\n";
    SERVICE_STOP(true);
    $GLOBALS["RECOVERY"]=0;
    echo "Starting......: ".date("H:i:s")." MySQL Normal mode\n";
    SERVICE_START(false,true);



	
}

function SERVICE_TTL(){
	$unix=new unix();
	
	$MYSQLPid=PID_NUM();
	if(!$unix->process_exists($MYSQLPid)){
		echo "Not running\n";
		return;
	}
	
	$RunningScince=$unix->PROCCESS_TIME_MIN($MYSQLPid);
	echo "Running Since {$RunningScince}Mn\n";
}

function test_sockets(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	if($GLOBALS["VERBOSE"]){echo "$pidfile\n$pidTime\n";}
	$pid=@file_get_contents($pidfile);

	if(!$GLOBALS["FORCE"]){
		$LastExec=$unix->file_time_min($pidTime);
		if($LastExec<15){return;}
		
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($time<15){return;}
			unix_system_kill_force($pid);
		}
	}
	
	@unlink($pidfile);
	@unlink($pidTime);
	@file_put_contents($pidfile, getmypid());
	@file_put_contents($pidTime, time());
	

	$socket="/var/run/mysqld/mysqld.sock";
	if(!$unix->is_socket($socket)){
		$unix->ToSyslog("MySQL: Fatal: /var/run/mysqld/mysqld.sock no such socket");
		squid_admin_mysql(0,"Fatal: /var/run/mysqld/mysqld.sock no such socket [ {action} = {restart} ]", null,__FILE__,__LINE__);
		SERVICE_RESTART();
		return;
	}
	
	$mysql=new mysql_status();
	$mysql->MainInstance();
	
}


function SERVICE_RESTART():bool{
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	
	$pid=@file_get_contents($pidfile);

    if(is_file("/etc/artica-postfix/MYSQLSAFEMODE")){
        build_progress(110,"{starting_service} {failed} SAFE MODE");
        return false;
    }

	
	$LastExec=$unix->file_time_min($pidTime);
	if(!$GLOBALS["BY_WIZARD"]){
		if($LastExec<1){
			$unix->ToSyslog("Restarting MySQL service Aborted Need at least 1mn",true,basename(__FILE__));
			return false;
		}
	}
	
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time<5){
			$unix->ToSyslog("Restarting MySQL service Aborted an artica task $pid is running",true,basename(__FILE__));
			build_progress(110,"{failed} Restarting MySQL service Aborted an artica task $pid is running");
			return false;
		}
		$unix->ToSyslog("Killing `Restart task` Running too long {$time}Mn");
		unix_system_kill_force($pid);
	}
	
	
	$unix->ToSyslog("Restarting MySQL service `{$GLOBALS["CMDLINE"]}`",true,basename(__FILE__));
	
	if($GLOBALS["FORCE"]){
		squid_admin_mysql(0, "Restarting MySQL using Force mode !",__FILE__,__LINE__);
	}
	

	if($GLOBALS["BY_SOCKET_FAILED"]){
		echo "Restarting....: ".date("H:i:s")." MySQL Seems socket is failed\n";
		$unix->ToSyslog("MySQL, Seems socket is failed...",true,basename(__FILE__));
	}
	
	
	if($GLOBALS["BY_SOCKET_FAILED"]){
		if($unix->is_socket("/var/run/mysqld/mysqld.sock")){
			squid_admin_mysql(0, "Watchdog say that the socket is failed but find it..aborting",__FILE__,__LINE__);
			return false;
		}else{
			squid_admin_mysql(2, "Watchdog say that the socket is failed and did not find it...",__FILE__,__LINE__);
		}
	}

		
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		squid_admin_mysql(0, "{restarting} {APP_MYSQL} by=[{$GLOBALS["BY_FRAMEWORK"]}] Service is not running,  start it",__FILE__,__LINE__);
		build_progress(50,"{starting_service}");
		if(!SERVICE_START(false,true)){
			build_progress(110,"{starting_service} {failed}");
		}
		return false;
	}
	
	$time=$unix->PROCESS_TTL($pid);
	squid_admin_mysql(0, "{restarting} {APP_MYSQL} {running} {since} {$time}Mn by=[{$GLOBALS["BY_FRAMEWORK"]}]...",__FILE__,__LINE__);
	build_progress(30, "{stopping_service}");
	SERVICE_STOP(true);
	build_progress(50, "{starting_service}");
	if(!SERVICE_START(false,true)){build_progress(110, "{failed}");return false;}
	build_progress(100, "{starting_service} {success}");
    return true;
}

function GetStartedValues(){
	$unix=new unix();
    $array=array();
	$mysqld=$unix->find_program("mysqld");
	exec("$mysqld --help --verbose 2>&1",$results);
    foreach ($results as $key=>$valueN){
    	if(preg_match("#--([a-z\-\_\=]+)(.+)#", $valueN,$re)){
		$key=trim($re[1]);
		if(strpos($key,"=")>0){
			$keyTR=explode("=",$key);
			$key=$keyTR[0];
		}
		//$value=trim($re[2]);
		$array["--$key"]=true;
		}
	}
	
	echo "Starting......: ".date("H:i:s")." MySQL `$mysqld` ". count($array)." available option(s)\n";
	
	return $array;
}


function SERVICE_STOP($aspid=false){
	$unix=new unix();
	$socket="/var/run/mysqld/mysqld.sock";
	$mysqlbin=$unix->LOCATE_mysqld_bin();
	$mysqladmin=$unix->find_program("mysqladmin");

    if(is_file("/etc/artica-postfix/MYSQLSAFEMODE")){
        return false;
    }

    $PID=MYSQLDSAFE_PID();
    if($unix->process_exists($PID)){
        echo "Stopping MySQL...............:  mysql safe is executed, aborting..\n";
        return true;
    }

	$prc=30;
	build_progress($prc++, "{stopping_service}");
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);

	if(!$aspid){
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "Stopping MySQL...............: This script is already executed PID: $pid since {$time}Mn\n";
			if($time<5){if(!$GLOBALS["FORCE"]){return;}}
			unix_system_kill_force($pid);
		}
		
		
		@file_put_contents($pidfile, getmypid());		
	}
	
	$pid=PID_NUM();  
	if($GLOBALS["VERBOSE"]){echo "DEBUG:: PID RETURNED $pid\n";}
	

	$unix->ToSyslog("MySQL: Stopping MySQL server");
	if(!$unix->process_exists($pid,$mysqlbin)){
		build_progress($prc++, "{stopping_service}");
		echo "Stopping MySQL...............: Already stopped\n";
        return MARIADB_STOP();
    }
	
	

	build_progress($prc++, "{stopping_service}");
	
	if(is_file($mysqladmin)){
		if(is_file($socket)){

			$cmd=$unix->mysqladmin("shutdown")." >/dev/null 2>&1 &";
			echo "Stopping MySQL...............: Stopping smoothly mysqld pid:$pid\n";
			if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $cmd\n";}
			for($i=0;$i<10;$i++){
				build_progress($prc++, "{stopping_service}");
				sleep(1);
				$pid=PID_NUM();  
				if(!$unix->process_exists($pid,$mysqlbin)){break;}
				echo "Stopping MySQL...............: Stopping, please wait $i/10\n";
			}
		}
	}

    $pid=$unix->PIDOF("/usr/sbin/mariadbd");
    if($unix->process_exists($pid)){
        echo "Stopping MySQL...............: /usr/sbin/mariadbd ($pid)\n";
        $unix->KILL_PROCESS($pid,9);
    }
	
	$pid=PID_NUM();  	
	if(!$unix->process_exists($pid,$mysqlbin)){
		echo "Stopping MySQL...............: Stopped\n";
		squid_admin_mysql(2, "Success to STOP MySQL server", __FUNCTION__, __FILE__, __LINE__);
		return MARIADB_STOP();
	}
	build_progress($prc++, "{stopping_service}");
	squid_admin_mysql(0,"Stopping MySQL service PID $pid", null,__FILE__,__LINE__);
	echo "Stopping MySQL...............: killing smoothly PID $pid\n";
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress($prc++, "{stopping_service}");
		sleep(1);
		$pid=PID_NUM();  
		if(!$unix->process_exists($pid,$mysqlbin)){break;}
	}	
	
	build_progress($prc++, "{stopping_service}");
	if(!$unix->process_exists($pid,$mysqlbin)){
		echo "Stopping MySQL...............: Stopped\n";
		squid_admin_mysql(2, "Success to STOP MySQL server", __FUNCTION__, __FILE__, __LINE__);
        return MARIADB_STOP();
	}
	
	build_progress($prc++, "{stopping_service}");
	echo "Stopping MySQL...............: Force killing PID $pid\n";
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		sleep(1);
		build_progress($prc++, "{stopping_service}");
		$pid=PID_NUM();  
		if(!$unix->process_exists($pid,$mysqlbin)){break;}
	}	

	build_progress($prc++, "{stopping_service}");
	if(!$unix->process_exists($pid,$mysqlbin)){
		echo "Stopping MySQL...............: Stopped\n";
		squid_admin_mysql(2, "Success to STOP MySQL server", __FUNCTION__, __FILE__, __LINE__);
        return MARIADB_STOP();
	}	
	

	echo "Stopping MySQL...............: failed\n";
    return false;
}
function MARIADB_PID():int{
    $unix=new unix();
    return $unix->PIDOF("/usr/sbin/mariadbd");
}
function MYSQLDSAFE_PID():int{
    $unix=new unix();

    $pid=$unix->get_pid_from_file("/var/run/mysqld/mysqld-safe.pid");
    if($unix->process_exists($pid)){return $pid;}
    $binary=$unix->LOCATE_mysqld_bin();
    $basename=basename($binary);
    return $unix->PIDOF_PATTERN("$basename.*?skip-grant-tables");
}
function MARIADB_STOP():bool{
    $unix=new unix();



    $pid=MARIADB_PID();
    if(!$unix->process_exists($pid)) {
        echo "Stopping MySQL...............: /usr/sbin/mariadbd Stopped\n";
        return true;
    }

    $unix->KILL_PROCESS($pid,9);
    for($i=0;$i<6;$i++){
        $pid=MARIADB_PID();
        if(!$unix->process_exists($pid)) {
            echo "Stopping MySQL...............: /usr/sbin/mariadbd Stopped\n";
            return true;
        }
        sleep(1);
        $unix->KILL_PROCESS($pid,9);
    }

    $pid=MARIADB_PID();
    if(!$unix->process_exists($pid)) {
        echo "Stopping MySQL...............: /usr/sbin/mariadbd Stopped\n";
        return true;
    }
    return false;
}

function SERVICE_START($nochecks=false,$nopid=false){
	$unix=new unix();
	$sock=new sockets();
	$prc=50;

    if(is_file("/etc/artica-postfix/MYSQLSAFEMODE")){
        build_progress($prc++, "MySQL locked in Safe mode");
        return false;
    }
	
	if(!$nopid){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "Starting......: ".date("H:i:s")." MySQL this script is already executed PID: $pid since {$time}Mn\n";
			if($time<5){if(!$GLOBALS["FORCE"]){return false;}}
			unix_system_kill_force($pid);
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$EnableMySQL=intval($sock->GET_INFO("EnableMySQL"));


	
	if(is_file("/etc/artica-postfix/mysql.stop")){
	    echo "Starting......: ".date("H:i:s")." MySQL locked, exiting\n";
	    build_progress($prc++, "MySQL locked");
	    return false;
    }
    $PID=MYSQLDSAFE_PID();
    if($unix->process_exists($PID)){
        echo "Starting......: ".date("H:i:s")." MySQL locked in SAFE MODE, exiting\n";
        build_progress($prc++, "MySQL locked in Safe mode");
        return false;
    }

	
	$PID_NUM=PID_NUM();
	if($unix->process_exists($PID_NUM)){
		$timemin=$unix->PROCCESS_TIME_MIN($PID_NUM);
		echo "Starting......: ".date("H:i:s")." MySQL already running PID \"$PID_NUM\" since {$timemin}Mn\n";
		build_progress($prc++, "{uninstalling}");
		if($EnableMySQL==0){
            uninstall();
        }
		return false;
	}

    if(!is_file("/usr/lib/x86_64-linux-gnu/libpmem.so.1")){
        echo "Starting......: ".date("H:i:s")." MySQL Installing libpmem1 package...\n";
        $unix->DEBIAN_INSTALL_PACKAGE("libpmem1");
    }
    if(!is_file("/usr/lib/x86_64-linux-gnu/libpmem.so.1")){
        echo "Starting......: ".date("H:i:s")." MySQL Installing libpmem1 package [failed]...\n";
        echo "Starting......: ".date("H:i:s")." Starting anyway...\n";
    }

    if(!$unix->CreateUnixUser("mysql","mysql")){
        echo "Starting......: ".date("H:i:s")." MySQL Creating user MySQL [failed]...\n";
        return false;
    }
	
	$mysql_install_db=$unix->find_program('mysql_install_db');
	$mysqlbin=$unix->LOCATE_mysqld_bin();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");	
	if(!is_file($mysqlbin)){echo "Starting......: ".date("H:i:s")." MySQL is not installed, abort\n";return false;}

    $MysqlBinAllAdresses=$sock->GET_INFO('MysqlBinAllAdresses');
	$MySQLTMPMEMSIZE=$sock->GET_INFO('MySQLTMPMEMSIZE');
	$MysqlTooManyConnections=$sock->GET_INFO("MysqlTooManyConnections");
	$MysqlRemoveidbLogs=$sock->GET_INFO("MysqlRemoveidbLogs");
	$innodb_force_recovery=$sock->GET_INFO("innodb_force_recovery");
	if(!is_numeric($innodb_force_recovery)){$innodb_force_recovery=0;}
	
	if(!is_numeric($MysqlRemoveidbLogs)){$MysqlRemoveidbLogs=0;}
	if(!is_numeric($MysqlBinAllAdresses)){$MysqlBinAllAdresses=0;}
	if(!is_numeric($MySQLTMPMEMSIZE)){$MySQLTMPMEMSIZE=0;}
	if(!is_numeric($MysqlTooManyConnections)){$MysqlTooManyConnections=0;}

	$MySqlTmpDir=$sock->GET_INFO('MySQLTMPDIR');
	$MySQLLOgErrorPath=$sock->GET_INFO('MySQLLOgErrorPath');
	$datadir=$unix->MYSQL_DATA_DIR();
	$EnableMysqlLog=$sock->GET_INFO("EnableMysqlLog");
	if(!is_numeric($EnableMysqlLog)){$EnableMysqlLog=0;}
	if($datadir==null){$datadir='/var/lib/mysql';}
	if($MySqlTmpDir=='/tmp'){$MySqlTmpDir=null;}
	if($MySQLLOgErrorPath==null){$MySQLLOgErrorPath=$datadir.'/mysqld.err';}

    $dirs[]="/var/log/mysql";
    $dirs[]="/var/run/mysqld";
    $dirs[]="/var/lib/mysql";
    $dirs[]=$datadir;
    foreach ($dirs as $directory){
        if(!is_dir($directory)){@mkdir($directory,0755,true);}
        @chown($directory,"mysql");
        @chgrp($directory,"mysql");
        @chmod($directory,0755);

    }

	if($MysqlTooManyConnections==1){
        echo "Starting......: ".date("H:i:s")." MySQL MysqlTooManyConnections=1, abort\n";
        return false;
    }
	if(isset($GLOBALS["RECOVERY"])){$innodb_force_recovery=$GLOBALS["RECOVERY"];}

if(strlen($MySqlTmpDir)>3){
        echo "Starting......: ".date("H:i:s")." MySQL tempdir : $MySqlTmpDir\n";
       shell_exec("$php5 /usr/share/artica-postfix/exec.mysql.build.php --tmpfs");
       $MySqlTmpDir=str_replace("//", "/", $MySqlTmpDir);
       if(!is_dir($MySqlTmpDir)){
          @mkdir($MySqlTmpDir,0755,true);
          $unix->chown_func("mysql","mysql", $MySqlTmpDir);
       }
       $MySqlTmpDirCMD=" --tmpdir=$MySqlTmpDir";
}

	if(!is_file("/etc/monit/conf.d/APP_MYSQLD.monitrc")){install_monit();}


	build_progress($prc++, "{starting_service}");
	$pid_file="/var/run/mysqld/mysqld.pid";
	$socket="/var/run/mysqld/mysqld.sock";
	$mysql_user="mysql";


    if(function_exists("posix_getgrnam")) {
        $dirs=$unix->dirdir("/var/lib/mysql");
        foreach ($dirs as $num => $directory) {
            echo "Starting......: " . date("H:i:s") . " MySQL, apply permissions on " . basename($directory) . "\n";
            $unix->chown_func("mysql", "mysql", "$directory/*");

        }
    }
	
	
	build_progress($prc++, "{starting_service}");
	$bind_address=' --bind-address=127.0.0.1';
	$bind_address2="127.0.0.1";
	  if($MysqlBinAllAdresses==1){
	      $bind_address2='All (0.0.0.0)';
	      $bind_address=' --bind-address=0.0.0.0';
	  }

   echo "Starting......: ".date("H:i:s")." MySQL Pid path.......:$pid_file\n";
   echo "Starting......: ".date("H:i:s")." datadir..............:$datadir\n";
   echo "Starting......: ".date("H:i:s")." Log error............:$MySQLLOgErrorPath\n";
   echo "Starting......: ".date("H:i:s")." socket...............:$socket\n";
   echo "Starting......: ".date("H:i:s")." user.................:$mysql_user\n";
   echo "Starting......: ".date("H:i:s")." LOGS ENABLED.........:$EnableMysqlLog\n";
   echo "Starting......: ".date("H:i:s")." Daemon...............:$mysqlbin\n";
   echo "Starting......: ".date("H:i:s")." Bind address.........:$bind_address2\n";
   echo "Starting......: ".date("H:i:s")." Temp Dir.............:$MySqlTmpDir\n";
   echo "Starting......: ".date("H:i:s")." innodb_force_recovery:$innodb_force_recovery\n";
   
   
   if(!is_file($pid_file)){@touch($pid_file);}
   @chown($pid_file, $mysql_user);
   @chgrp($pid_file, $mysql_user);
   
   build_progress($prc++, "{starting_service}");
   squid_admin_mysql(1,"Starting MySQL service...", null,__FILE__,__LINE__);
   echo "Starting......: ".date("H:i:s")." Settings permissions..\n";

   if($unix->is_socket("/var/run/mysqld/mysqld.sock")){@unlink("/var/run/mysqld/mysqld.sock");}
   if(is_file('/var/run/mysqld/mysqld.err')){@unlink('/var/run/mysqld/mysqld.err');}

   if(function_exists("posix_getpwnam")){
       if(is_file("/var/run/mysqld/mysqld.pid")){
           $unix->chown_func($mysql_user,$mysql_user, "/var/run/mysqld/mysqld.pid");
       }
   }else{
       if(is_file("/var/run/mysqld/mysqld.pid")){
           system("chown $mysql_user:$mysql_user /var/run/mysqld/mysqld.pid");
       }
   }

   @chmod("/var/run/mysqld", 0777);

   
   if($MysqlRemoveidbLogs==1){
        shell_exec('/bin/mv /var/lib/mysql/ib_logfile* /tmp/');
       $sock->SET_INFO('MysqlRemoveidbLogs','0');
   }
   
   
   $logpathstring=" --log-error=$MySQLLOgErrorPath";
   if($EnableMysqlLog==1){$logpathstring=" --log=/var/log/mysql.log --log-slow-queries=/var/log/mysql-slow-queries.log --log-error=$MySQLLOgErrorPath --log-warnings";}
   
   $toTouch[]="/var/log/mysql-slow-queries.log";
   $toTouch[]="/var/log/mysql.error";
   $toTouch[]="/var/log/mysql.log";
   $toTouch[]="/var/log/mysql.warn";
   $toTouch[]="/var/lib/mysql/mysqld.err";

   foreach ($toTouch as $filename){
   		if(!is_file($filename)){@file_put_contents($filename, "#\n");}

       if(function_exists("posix_getpwnam")) {
           $unix->chown_func($mysql_user, $mysql_user, $filename);
       }else{
           system("chown $mysql_user:$mysql_user $filename");
       }
   }

	
	if(!is_file("/usr/share/mysql/mysql_performance_tables.sql")){
		@mkdir("/usr/share/mysql",0755,true);
		@copy("/usr/share/artica-postfix/bin/install/mysql_performance_tables.sql", "/usr/share/mysql/mysql_performance_tables.sql");
	}

    echo "Starting......: ".date("H:i:s")." MySQL mysql_install_db=$mysql_install_db\n";
   build_progress($prc++, "{starting_service}");
   echo "Starting......: ".date("H:i:s")." MySQL Checking : $datadir/mysql/plugin.frm\n";
   if(!is_file("$datadir/mysql/host.frm")){
	    if(is_file($mysql_install_db)){
	        echo "Starting......: ".date("H:i:s")." MySQL Installing default databases\n";
	        echo "Starting......: ".date("H:i:s")." $mysql_install_db --datadir=\"$datadir\"\n";
	        shell_exec("$mysql_install_db --datadir=\"$datadir\"");
	    	}
	}else{
		echo "Starting......: ".date("H:i:s")." MySQL Checking : $datadir/mysql/host.frm OK\n";
	}
	$cmd2=array();
	$MEMORY=$unix->MEM_TOTAL_INSTALLEE();
	
	
	
	build_progress($prc++, "{starting_service} Memory: {$MEMORY}KB");
	$GetStartedValues=GetStartedValues();
	$MySQLSkipNameResolve=intval($sock->GET_INFO("MySQLSkipNameResolve"));
	$MySQLSkipExternalLocking=intval($sock->GET_INFO("MySQLSkipExternalLocking"));
	$MySQLSkipCharacterSetClientHandshake=intval($sock->GET_INFO("MySQLSkipCharacterSetClientHandshake"));
	$MySQLKeyBufferSize=intval($sock->GET_INFO("MySQLKeyBufferSize"));
	$MySQLMyisamSortBufferSize=intval($sock->GET_INFO("MySQLMyisamSortBufferSize"));
	$MySQLSortBufferSize=intval($sock->GET_INFO("MySQLSortBufferSize"));
	$MySQLQueryCacheSize=intval($sock->GET_INFO("MySQLQueryCacheSize"));
	$MySQLJoinBufferSize=intval($sock->GET_INFO("MySQLJoinBufferSize"));
	$MySQLReadBufferSize=intval($sock->GET_INFO("MySQLReadBufferSize"));
	$MySQLReadRndBufferSize=intval($sock->GET_INFO("MySQLReadRndBufferSize"));
	$MySQLMaxAllowedPackets=intval($sock->GET_INFO("MySQLMaxAllowedPackets"));
	$MySQLMaxConnections=intval($sock->GET_INFO("MySQLMaxConnections"));
	$MySQLThreadCacheSize=intval($sock->GET_INFO("MySQLThreadCacheSize"));
	$MySQLWaitTimeOut=intval($sock->GET_INFO("MySQLWaitTimeOut"));
	$MySQLOpenFilesLimit=intval($sock->GET_INFO("MySQLOpenFilesLimit"));
	
	if($MySQLThreadCacheSize>$MySQLMaxConnections){$MySQLThreadCacheSize=$MySQLMaxConnections;}
	
	if($MySQLSkipNameResolve==1){
		if($GetStartedValues["--skip-name-resolve"]){$cmd2[]="--skip-name-resolve";}
	}
	if($MySQLSkipExternalLocking==1){
		if($GetStartedValues["--skip-external-locking"]){$cmd2[]="--skip-external-locking";}
	}
	
	if($MySQLSkipCharacterSetClientHandshake==1){
		if($GetStartedValues["--skip-character-set-client-handshake"]){$cmd2[]="--skip-character-set-client-handshake";}
	}
	if($MySQLKeyBufferSize>0){
		if($GetStartedValues["--key-buffer-size"]){ $cmd2[]="--key-buffer-size={$MySQLKeyBufferSize}M";}
	}
	if($MySQLSortBufferSize>0){
		if($GetStartedValues["--sort-buffer-size"]){ $cmd2[]="--sort-buffer-size={$MySQLSortBufferSize}M";}
	}
	if($MySQLQueryCacheSize>0){
		if($GetStartedValues["--query-cache-size"]){ $cmd2[]="--query-cache-size={$MySQLQueryCacheSize}M";}
	}	
	if($MySQLMyisamSortBufferSize>0){
		if($GetStartedValues["--myisam-sort-buffer-size"]){ $cmd2[]="--myisam-sort-buffer-size={$MySQLMyisamSortBufferSize}M";}
	}	
	if($MySQLJoinBufferSize>0){
		if($GetStartedValues["--join-buffer-size"]){ $cmd2[]="--join-buffer-size={$MySQLJoinBufferSize}M";}
	}	
	if($MySQLReadBufferSize>0){
		if($GetStartedValues["--read-buffer-size"]){ $cmd2[]="--read-buffer-size={$MySQLReadBufferSize}M";}
	}
	if($MySQLReadRndBufferSize>0){
		if($GetStartedValues["--read-rnd-buffer-size"]){ $cmd2[]="--read-rnd-buffer-size={$MySQLReadRndBufferSize}M";}
	}	
	if($MySQLMaxAllowedPackets>0){
		if($GetStartedValues["--max-allowed-packet"]){ $cmd2[]="--max-allowed-packet={$MySQLMaxAllowedPackets}M";}
	}	
	if($MySQLMaxConnections>0){
		if($GetStartedValues["--max-connections"]){ $cmd2[]="--max-connections={$MySQLMaxConnections}";}
	}
	if($MySQLThreadCacheSize>0){
		if($GetStartedValues["--thread-cache-size"]){ $cmd2[]="--thread-cache-size={$MySQLThreadCacheSize}";}
	}	
	if($MySQLWaitTimeOut>0){
		if($GetStartedValues["--wait-timeout"]){ $cmd2[]="--wait-timeout={$MySQLWaitTimeOut}";}
	}	
	if($MySQLOpenFilesLimit>0){
		if($GetStartedValues["--open-files-limit"]){ $cmd2[]="--open-files-limit={$MySQLOpenFilesLimit}";}
	}




   if(is_file($MySQLLOgErrorPath)){@unlink($MySQLLOgErrorPath);}
	$cmds[]=$mysqlbin;
	if($MEMORY<624288){$cmds[]="--no-defaults --user=mysql";}


	
	$cmds[]="--pid-file=/var/run/mysqld/mysqld.pid";
	$cmds[]=trim($logpathstring);
	$cmds[]=trim($MySqlTmpDirCMD);
	$cmds[]=$bind_address;
	$cmds[]="--socket=$socket";
	$cmds[]="--datadir=\"$datadir\"";
    //$cmds[]="--init-file=/var/lib/mysql/mysql-init";
    if($GetStartedValues["--plugin-load"]){
       // $cmds[]="--plugin-load=unix_socket,auth_socket";
       // $cmds[]="--plugin-load=unix_socket,auth_socket";
    }

	if(count($cmd2)==0){
		if($innodb_force_recovery>0){
		$cmds[]="--innodb-force-recovery=$innodb_force_recovery";
		}
	}
	if(count($cmd2)>0){$cmds[]=@implode(" ", $cmd2);}
	$cmds[]=">/dev/null 2>&1 &";
	if(is_file('/usr/sbin/aa-complain')){
        echo "Starting......: ".date("H:i:s")." Mysql Adding mysql in apparamor complain mode...\n";
        shell_exec("/usr/sbin/aa-complain $mysqlbin >/dev/null 2>&1");
	}
	
	$cmd=@implode(" ", $cmds);
	foreach ($cmds as $ligne){
		$ligne=trim($ligne);
		if($ligne==null){continue;}
		echo "Starting......: ".date("H:i:s")." MySQL Option: $ligne\n";
	}
	build_progress($prc++, "{starting_service}....");
	echo "Starting......: ".date("H:i:s")." MySQL Starting daemon, please wait\n";
	writelogs("Starting MySQL $cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$count=0;
    sleep(2);
    
    
    
   for($i=0;$i<6;$i++){
   		$pid=PID_NUM();
   		if($unix->process_exists($pid,$mysqlbin)){
   			echo "Starting......: ".date("H:i:s")." MySQL Checks daemon running...\n";
   			break;
   		}
   		build_progress($prc++, "{starting_service}....($i/6)");
   		echo "Starting......: ".date("H:i:s")." MySQL Checks daemon, please wait ($i/6)\n";
   		sleep(1);
   }

   $pid=PID_NUM();
   if(!$unix->process_exists($pid)){
   	echo "Starting......: ".date("H:i:s")." MySQL failed\n";
   	echo "Starting......: ".date("H:i:s")." $cmd\n";
   	squid_admin_mysql(2, "Failed to start MySQL server", __FUNCTION__, __FILE__, __LINE__);
   	$php5=$unix->LOCATE_PHP5_BIN();
   	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php >/dev/null 2>&1 &");
   	return false;
   	
   }

   	squid_admin_mysql(1,"Success to start MySQL Server with new pid $pid", null,__FILE__,__LINE__);
   	echo "Starting......: ".date("H:i:s")." MySQL Success pid $pid\n";
    $q=new mysql();
    $q->QUERY_SQL("SHOW GRANTS FOR root@localhost;","mysql");
    if(!$q->ok){
        echo "*********** $q->mysql_error *****************\n";
        if(!isset($GLOBALS["NOSAFE"])){
            if(preg_match("#Access denied for user.*?root.*?localhost#i",$q->mysql_error)) {
                mysqld_safe_for_root();
            }
        }
    }


    if(is_file("/usr/bin/mariadb-upgrade")){
        shell_exec("/usr/bin/mariadb-upgrade --version-check");
    }
   	
   	build_progress($prc++, "{success}");
   	return true;
   

   
}
function mysql_safe_bin():string{
    $unix=new unix();

    if(is_file("/usr/bin/mariadbd-safe")){
        @chmod("/usr/bin/mariadbd-safe",0755);
        return "/usr/bin/mariadbd-safe";
    }

    $safebin=$unix->find_program("mysqld_safe");
    if(strlen($safebin)>1){return $safebin;}
    return $unix->find_program("mariadb-safe");
}


function mysqld_safe_for_root():bool{
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    echo "Starting......: ".date("H:i:s")." Running in Safe Mode\n";
    SERVICE_STOP();
    @touch("/etc/artica-postfix/MYSQLSAFEMODE");
    $tempfile="/var/lib/mysql/mysqld-safe.err";


    $mysql=$unix->find_program("mysql");

    $SH[]="FLUSH PRIVILEGES;";
    $SH[]="CREATE USER IF NOT EXISTS 'root'@'localhost' IDENTIFIED BY '';";
    $SH[]="GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' WITH GRANT OPTION;";
    $SH[]="FLUSH PRIVILEGES;";

    @file_put_contents("/var/lib/mysql/mysql-init" ,@implode("\n",$SH));
    chown("/var/lib/mysql/mysql-init","mysql");



    $datadir=$unix->MYSQL_DATA_DIR();
    if($datadir==null){$datadir="/var/lib/mysql";}
    $binary=$unix->LOCATE_mysqld_bin();
    $f[]="$nohup";
    $f[]="$binary";
    $f[]="--basedir=/usr";
    $f[]="--datadir=$datadir";
    $f[]="--plugin-dir=/usr/lib/mysql/plugin";
    $f[]="--user=mysql";
    $f[]="--skip-grant-tables";
    $f[]="--skip-networking";
    $f[]="--verbose --debug";
    $f[]="--log-error=/var/lib/mysql/mysqld-safe.err";
    $f[]="--pid-file=/var/run/mysqld/mysqld-safe.pid";
    $f[]="--socket=/var/run/mysqld/mysqld.sock";
    //$f[]="--init-file=/var/lib/mysql/mysql-init";
    $f[]="--port=3306";
    $f[]=">/dev/null 2>&1 &";


    if(is_file("/var/lib/mysql/mysqld-safe.err")){
        @unlink("/var/lib/mysql/mysqld-safe.err");
    }
    $cmd=@implode(" ",$f);
    echo "$cmd\n";
    shell_exec($cmd);

    for ($i=0;$i<5;$i++){
        $PID=MYSQLDSAFE_PID();
        if($unix->process_exists($PID)){
            break;
        }
        echo "Starting......: ".date("H:i:s")." Running in Safe Mode $i/5\n";
        sleep(1);
    }

    $PID=MYSQLDSAFE_PID();
    if(!$unix->process_exists($PID)){
        echo "Starting......: ".date("H:i:s")." Running in Safe Mode FAILED\n";
        $f=explode("\n",@file_get_contents($tempfile));
        @unlink($tempfile);
        foreach ($f as $line){
            echo "$line\n";
        }
    }
    for($i=0;$i<6;$i++){
        echo "Starting......: ".date("H:i:s")." Waiting...5s \n";
        sleep(1);
    }
    echo "Starting......: ".date("H:i:s")." Flushing privileges\n";
    exec("$mysql -S /var/run/mysqld/mysqld.sock < /var/lib/mysql/mysql-init 2>&1",$results);
    foreach ($results as $line){
        echo "Starting......: ".date("H:i:s")."mysql-init: $line\n";
    }

    $PID=MYSQLDSAFE_PID();
    if($unix->process_exists($PID)){
        echo "Starting......: ".date("H:i:s")." Running in Safe Mode SUCCESS [$PID]\n";
    }else{
        echo "Starting......: ".date("H:i:s")." Running in Safe Mode FAILED [CRASHED]\n";
        $f=explode("\n",@file_get_contents($tempfile));
        @unlink($tempfile);
        foreach ($f as $line){
            echo "$line\n";
        }
        @unlink("/etc/artica-postfix/MYSQLSAFEMODE");
        return false;
    }



    $PID=MYSQLDSAFE_PID();
    $unix->KILL_PROCESS($PID,15);

    if($unix->process_exists($PID)){
        for ($i=0;$i<6;$i++) {
            $PID=MYSQLDSAFE_PID();
            if($unix->process_exists($PID)){
                $unix->KILL_PROCESS($PID,15);
            }else{
                break;
            }
            echo "Starting......: " . date("H:i:s") . " Stopping Safe Mode pid:$PID $i/5\n";
            sleep(1);
        }
    }
    $PID=MYSQLDSAFE_PID();
    if($unix->process_exists($PID)) {
        $unix->KILL_PROCESS($PID, 9);
        echo "Starting......: " . date("H:i:s") . " Killing Safe Mode pid:$PID $i/5\n";
        for ($i = 0; $i < 6; $i++) {
            sleep(1);
            $PID = MYSQLDSAFE_PID();
            if (!$unix->process_exists($PID)) {
                echo "Starting......: " . date("H:i:s") . " Killing Safe Mode KILLED\n";
                break;
            }
            $unix->KILL_PROCESS($PID, 9);
        }
    }

    @unlink("/etc/artica-postfix/MYSQLSAFEMODE");

    $GLOBALS["NOSAFE"]=true;
    SERVICE_START(true,true);
    return true;
}


function status_all_mysql_engines(){
	$unix=new unix();
	if(systemMaxOverloaded()){return;}
	
	$cachefile=PROGRESS_DIR."/MYSQLDB_STATUS";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if(!$GLOBALS["VERBOSE"]){
		$time=$unix->file_time_min($cachefile);
		if($time<60){return;}
	}
	
	
	
	$sock=new sockets();
	$datadir=$unix->MYSQL_DATA_DIR();
	$ArticaDBPath=$sock->GET_INFO("ArticaDBPath");
	if($ArticaDBPath==null){$ArticaDBPath="/opt/articatech";}
	$SquidStatsDatabasePath=$sock->GET_INFO("SquidStatsDatabasePath");
	if($SquidStatsDatabasePath==null){$SquidStatsDatabasePath="/opt/squidsql";}
	
	$array["APP_MYSQL_ARTICA"]["size"]=$unix->DIRSIZE_BYTES($datadir);
	$array["APP_MYSQL_ARTICA"]["INFO"]=$unix->DIRPART_INFO($datadir);
	
	if(is_dir("$ArticaDBPath/mysql")){
		$array["APP_ARTICADB"]["size"]=$unix->DIRSIZE_BYTES("$ArticaDBPath");
		$array["APP_ARTICADB"]["INFO"]=$unix->DIRPART_INFO("$ArticaDBPath");
		
	}
	
	if(is_dir("$SquidStatsDatabasePath/data")){
		$array["APP_SQUID_DB"]["size"]=$unix->DIRSIZE_BYTES("$SquidStatsDatabasePath");
		$array["APP_SQUID_DB"]["INFO"]=$unix->DIRPART_INFO("$SquidStatsDatabasePath");
		
	}
	
	$MySQLSyslogWorkDir=$sock->GET_INFO("MySQLSyslogWorkDir");
	if($MySQLSyslogWorkDir==null){$MySQLSyslogWorkDir="/home/syslogsdb";}	
	
	if(is_dir($MySQLSyslogWorkDir)){
		$array["MYSQL_SYSLOG"]["size"]=$unix->DIRSIZE_BYTES($MySQLSyslogWorkDir);
		$array["MYSQL_SYSLOG"]["INFO"]=$unix->DIRPART_INFO($MySQLSyslogWorkDir);		
	}
	if($GLOBALS["VERBOSE"]){print_r($array);}
	@unlink($cachefile);
	@file_put_contents($cachefile, base64_encode(serialize($array)));
	@chmod($cachefile, 0777);
	
}


function install_monit():bool{
	@unlink("/etc/monit/conf.d/APP_MYSQLD.monitrc");
    $fname="/etc/monit/conf.d/APP_MYSQL_ARTICA.monitrc";
    $md51=null;
	if(is_file($fname)){
        $md51=md5_file($fname);

    }
	
	
	$f[]="check process APP_MYSQL_ARTICA with pidfile /var/run/mysqld/mysqld.pid";
	$f[]="\tstart program = \"/etc/init.d/mysql start\"";
	$f[]="\tstop program = \"/etc/init.d/mysql stop\"";
    $f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_MYSQL_ARTICA.monitrc", @implode("\n", $f));
    $md52=md5_file($fname);
    if($md52==$md51){
        return true;
    }
    echo "Starting......: ".date("H:i:s")." /etc/monit/conf.d/APP_MYSQL_ARTICA.monitrc success\n";
    $unix=new unix();
    $unix->MONIT_RELOAD();
    return true;
}

function build_progress_install($text,$pourc){
	$filename=PROGRESS_DIR."/mysql.install.progress";
	
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
		@file_put_contents($filename, serialize($array));
		@chmod($filename,0755);
		return;
	}
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}

function uninstall(){
	$unix=new unix();
	build_progress_install(10, "{APP_MYSQL} {uninstalling}");
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock->SET_INFO("EnableMySQL", 0);
	$EnableGreenSQL=intval($sock->GET_INFO("EnableGreenSQL"));
	
	if($EnableGreenSQL==1){
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.greensql.php --uninstall");
	}
	build_progress_install(20, "{APP_MYSQL} {uninstalling}");
	remove_service("/etc/init.d/mysql");
	if(is_file("/etc/monit/conf.d/APP_MYSQL_ARTICA.monitrc")){
		@unlink("/etc/monit/conf.d/APP_MYSQL_ARTICA.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	
	build_progress_install(30, "{APP_MYSQL} {uninstalling}");
	system("/etc/init.d/artica-status restart --force");
	
	$EnableMunin=intval($sock->GET_INFO("EnableMunin"));
	if($EnableMunin==1){
		system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");
		system("/etc/init.d/munin-node restart");
	}
	
	build_progress_install(100, "{APP_MYSQL} {uninstalling} {done}");
	
}

function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function build_progress_wp($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/wordpress.install.progress", serialize($array));
    @chmod($GLOBALS["PROGRESS_FILE"],0755);

}

function install(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install(10, "{APP_MYSQL} {installing}");
    build_progress_wp(30,"{APP_MYSQL} {installing}");
	$sock=new sockets();
	$sock->SET_INFO("EnableMySQL", 1);
	build_progress_install(20, "{APP_MYSQL} {installing}");
    build_progress_wp(31,"{APP_MYSQL} {installing}");
	mysqlInit();
	build_progress_install(30, "{APP_MYSQL} {installing}");
    build_progress_wp(32,"{APP_MYSQL} {installing}");
	install_monit();
	build_progress_install(80, "{APP_MYSQL} {installing}");
    build_progress_wp(33,"{APP_MYSQL} {installing}");
	system("/etc/init.d/artica-status restart --force");
	
	$EnableMunin=intval($sock->GET_INFO("EnableMunin"));
	if($EnableMunin==1){
		system("$php /usr/share/artica-postfix/exec.munin.php --reconfigure");
		system("/etc/init.d/munin-node restart");
	}
    build_progress_wp(40,"{APP_MYSQL} {installing} {done}");
	build_progress_install(100, "{APP_MYSQL} {installing} {done}");
	
}

function mysqlInit(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/mysql";
	$php5script="exec.mysql.start.php";
	$daemonbinLog="MySQL For Artica";
	$daemon_path=$unix->find_program("mysqld");


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         mysql";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";

	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}
}


?>