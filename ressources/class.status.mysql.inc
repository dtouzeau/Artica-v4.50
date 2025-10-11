<?php
function mysql_server():string{

	$mysqlversion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_MYSQL_VERSION");
    $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));


	$l[]="[APP_MYSQL_ARTICA]";
	$l[]="service_name=APP_MYSQL_ARTICA";
	$l[]="master_version=$mysqlversion";
	$l[]="service_cmd=/etc/init.d/mysql";
	$l[]="service_disabled=$EnableMySQL";
	$l[]="watchdog_features=1";
	$l[]="family=system";

    $mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
    if(is_null($mysqld)){
        $EnableMySQL=0;
    }else{
        if(!is_file($mysqld)){
            $EnableMySQL=0;
        }
    }

    if($EnableMySQL==0){
        if(is_file("/etc/init.d/mysql")){
            squid_admin_mysql(0, "{uninstall} {APP_MYSQL_ARTICA} ({disabled}");
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.mysql.start.php --uninstall");
            return @implode("\n",$l);
        }
        return @implode("\n",$l);
    }

    if($GLOBALS["VERBOSE"]){ echo "MySQL Enabled: $EnableMySQL\n"; }

    $master_pid=mysql_server_pid();
	$GLOBALS["CLASS_UNIX"]->chown_func("mysql","mysql", "/var/run/mysqld");
	$GLOBALS["CLASS_UNIX"]->chown_func("mysql","mysql", "/var/log/mysql");
	@chmod("/var/run/mysqld", 0777);

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){

        if($GLOBALS["VERBOSE"]){
            echo "MySQL pid: $master_pid\n";
        }

		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
			if($ServerRunSince<3){
                $GLOBALS["CLASS_UNIX"]->go_exec("/etc/init.d/mysql start");

			}
			if($ServerRunSince>3){
                $GLOBALS["MYSQL_WATCHOG_EVENTS"][]="Server running since {$ServerRunSince}Mn";
				exec("{$GLOBALS["TAILBIN"]} -n 120 /var/lib/mysql/mysqld.err 2>&1",$tailR);
				if($GLOBALS["SQUID_INSTALLED"]){squid_admin_mysql(0,
				"MySQL not running, starting MySQL service and repair ( Watchdog service )",
				@implode("\n", $GLOBALS["MYSQL_WATCHOG_EVENTS"])."\n".@implode("\n", $tailR),__FILE__,__LINE__);}
                $GLOBALS["CLASS_UNIX"]->framework_exec("exec.initd-mysql.php");
                $GLOBALS["CLASS_UNIX"]->go_exec("/etc/init.d/mysql start");
                $GLOBALS["CLASS_UNIX"]->framework_exec("exec.exec.mysqld.crash.php --crashed --force");
			}
		}
		$l[]="";
		return implode("\n",$l);
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_MYSQL");
	$l[]="";


	$AS_CGROUP=false;
	if($GLOBALS["CLASS_SOCKETS"]->EnableIntelCeleron==1){$AS_CGROUP=true;}
	if($AS_CGROUP){
		events("mysqld cgroup must be enabled ....",__FUNCTION__,__LINE__);
		$cgroups=new status_cgroups();
		if($cgroups->GetLimit($master_pid)=="unlimited"){
			$cgroups->set_limit("mysql", $master_pid);
		}

	}

    $ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
    $GLOBALS["CLASS_UNIX"]->framework_exec("exec.mysql.start.php --engines --framework=".__FILE__);

	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if(!$GLOBALS["CLASS_UNIX"]->is_socket("/var/run/mysqld/mysqld.sock")){
			if($ServerRunSince>3){
				$xxx=array();
				exec("/usr/bin/stat /var/run/mysqld/mysqld.sock 2>&1",$xxx);
				squid_admin_mysql(0, "/var/run/mysqld/mysqld.sock no such socket [ {action} = {restart} ]", @implode("\n", $xxx));
                $GLOBALS["CLASS_UNIX"]->framework_exec("exec.mysql.start.php --restart --socketfailed --framework=".__FILE__);
			}
		}
	}

	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysqld.crash.php.check_crashed.time");
	events("exec.mysqld.crash.php.check_crashed.time = $CacheSchedules/240Min",__FUNCTION__,__LINE__);
	if($CacheSchedules>240){
		if(!system_is_overloaded()){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.mysqld.crash.php --crashed");
		}
	}

	$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.mysql.start.php.test_sockets.time");
	events("exec.mysql.start.php.test_sockets.time = $CacheSchedules/15Min",__FUNCTION__,__LINE__);
	if($CacheSchedules>15){
		if($ServerRunSince>3){
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.mysql.start.php --test-sock");
		}
	}

	return implode("\n",$l);

}

//======================================================================================================================
function mysql_replica():string{
	
	$program=$GLOBALS["CLASS_UNIX"]->find_program("ndbd");
	if($program==null){return "";}
	$EnableMysqlClusterReplicat=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMysqlClusterReplicat");
	if(!is_numeric($EnableMysqlClusterReplicat)){$EnableMysqlClusterReplicat=0;}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program);
	$l[]="[MYSQL_CLUSTER_REPLICA]";
	$l[]="service_name=APP_MYSQL_CLUSTER_REPLICA";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=mysql-cluster";
	$l[]="service_disabled=$EnableMysqlClusterReplicat";
	$l[]="family=system";
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid);
	$l[]="";

	return implode("\n",$l);

}
//============================================================================================================================================
function mysql_mgmt():string{
	
	$program=$GLOBALS["CLASS_UNIX"]->find_program("ndb_mgmd");
	if($program==null){return "";}
	$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($program);
	$EnableMysqlClusterManager=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMysqlClusterManager"));
    $l[]="[MYSQL_CLUSTER_MGMT]";
	$l[]="service_name=APP_MYSQL_CLUSTER_MGMT";
	$l[]="master_version=".$GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
	$l[]="service_cmd=mysql-cluster";
	$l[]="service_disabled=$EnableMysqlClusterManager";
	$l[]="family=system";

	if($EnableMysqlClusterManager==0){
		return implode("\n",$l);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){$l[]="";return implode("\n",$l);}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"MYSQL_CLUSTER_MGMT");
	$l[]="";

	return implode("\n",$l);

}

function mysql_server_pid(){

	$GLOBALS["MYSQL_WATCHOG_EVENTS"]=array();
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/mysqld/mysqld.pid");
	if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: /var/run/mysqld/mysqld.pid -> \"$pid\"\n";}

	$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="/var/run/mysqld/mysqld.pid -> \"$pid\"";

	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}

	if(is_file("/proc/$pid/cmdline")){
		$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="/proc/$pid/cmdline = ".@file_get_contents("/proc/$pid/cmdline");
	}
		
    $mysqlbin=$GLOBALS["CLASS_UNIX"]->LOCATE_mysqld_bin();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$lsof=$GLOBALS["CLASS_UNIX"]->find_program("lsof");
	if(is_file($pgrep)){
		if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1\n";}
			$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\"";
		    exec("$pgrep -l -f \"$mysqlbin.*?--pid-file=/var/run/mysqld/mysqld.pid\" 2>&1",$results);

		foreach ($results as $line){
			$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$line";
			if($GLOBALS["VERBOSE"]){echo "[VERBOSE]: $line\n";}
			if(preg_match("#pgrep#",$line)){continue;}
			if(preg_match("#^([0-9]+)\s+#", $line,$re)){
				@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
				$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="Return ".$re[1];
				return $re[1];
			}
		}
	}


	$results=array();
	exec("$lsof -Pnl +M -i TCP:3306 2>&1",$results);
	$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$lsof -Pnl +M -i TCP:3306 2>&1";
	foreach ($results as $line){
		$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="$line";
		if(preg_match("#mysqld\s+([0-9]+).*?TCP.*?:3306#",$line,$re)){
			@file_put_contents("/var/run/mysqld/mysqld.pid", $re[1]);
			$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="Return ".$re[1];
			return $re[1];
		}

	}


	$GLOBALS["MYSQL_WATCHOG_EVENTS"][]="Return $pid";
	return $pid;
}