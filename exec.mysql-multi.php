#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');



if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--rootch"){ChangeRoot($argv[2]);}




function ChangeRoot($instance_id){
	echo "Loading instance $instance_id\n";
	$mysqld=new mysqlserver_multi($instance_id);
	
	$username=$mysqld->mysql_admin;
	$password=$mysqld->mysql_password;
	if($password==null){echo "Password is null, aborting\n";return;}
	$unix=new unix();
	$mysqld_safe=$unix->find_program("mysqld_safe");
	$nohup=$unix->find_program("nohup");
	$killbin=$unix->find_program("kill");
	echo "mysqld_safe:$mysqld_safe\n";
	$ini=new Bs_IniHandler();
	$ini->loadFile("/etc/mysql-multi.cnf");
	$array=$ini->_params["mysqld$instance_id"];
	$datadir=$ini->get("mysqld$instance_id","datadir");
	foreach ($array as $key=>$value){$tt[]="$key = $value";}
	
	$newconfig="[mysqld]\n".@implode("\n", $tt)."\n";
	@file_put_contents("/etc/mysql-temp-$instance_id.cf", $newconfig);
	
	for($i=0;$i<5;$i++){
		$pid=multi_get_pid($instance_id);
		if($pid>0){
			echo "Stopping mysqld safe pid:$pid\n";
			unix_system_kill_force($pid);
			sleep(1);
		}else{break;}
	}	
	
	echo "Stopping instance $instance_id\n";
	//shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.mysql.build.php --multi-stop $instance_id >/dev/null 2>&1");
	
	
	for($i=0;$i<5;$i++){
		$pid=multi_get_pidNormal($instance_id);
		if($pid>0){
			echo "Stopping mysqld pid:$pid\n";
			unix_system_kill_force($pid);
			sleep(1);
		}else{break;}
	}	
	
	
	
	$pid=@file_get_contents("/var/run/mysqld/mysqld$instance_id.pid");
	if($unix->process_exists($pid)){
		echo "Stopping instance $instance_id failed...\n";
		return;
	}
	
	echo "Running instance $instance_id in safe mode...\n";
	if(file_exists("$datadir/error.log")){@unlink("$datadir/error.log");}
	
	$cmd="$nohup $mysqld_safe --defaults-file=/etc/mysql-temp-$instance_id.cf --datadir=\"$datadir\" --skip-grant-tables --skip-networking --skip-external-locking --log-error=\"$datadir/error.log\" --pid-file=/var/run/mysqld/mysqld$instance_id.pid >/dev/null 2>&1 &";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);
	
	
	for($i=0;$i<4;$i++){
		sleep(1);
		$pid=@file_get_contents("/var/run/mysqld/mysqld$instance_id.pid");
		if($unix->process_exists($pid)){sleep(1);break;}
	}	
	
	$pid=@file_get_contents("/var/run/mysqld/mysqld$instance_id.pid");
	if(!$unix->process_exists($pid)){
		echo "Failed to run mysqld safe\n";
		if(file_exists("$datadir/error.log")){echo @file_get_contents("$datadir/error.log");}
		shell_exec($nohup." ".$unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.mysql.build.php --multi-start $instance_id >/dev/null 2>&1 &");
		return;
	}

	$q=new mysql_multi($instance_id);
	
	$ligne=mysqli_fetch_array($q->QUERY_SQL("SELECT User from user WHERE User='$username' LIMIT 0,1","mysql"));
	if(!$q->ok){echo $q->mysql_error."\n";}
	echo "User:$username = {$ligne["User"]}\n";
	if($ligne["User"]<>null){
		echo "User: Already exists, update password\n";
		$sqlstring="UPDATE user SET password=PASSWORD(\"$password\") WHERE user=\"$username\"";
		$q->QUERY_SQL($sqlstring,"mysql");
		if(!$q->ok){echo $q->mysql_error."\n";}
   		$q->QUERY_SQL_NO_BASE("FLUSH PRIVILEGES");
		if(!$q->ok){echo $q->mysql_error."\n";}
	}else{
		echo "Root: did not exists, Create a new one...\n";
		$q->QUERY_SQL_NO_BASE("create user root@localhost");
		$q->QUERY_SQL_NO_BASE("GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost' with grant option");
		$q->QUERY_SQL_NO_BASE("FLUSH PRIVILEGES");
		
	}
	
	
	echo "Stopping mysqld safe....\n";
	for($i=0;$i<5;$i++){
		$pid=multi_get_pid($instance_id);
		if($pid>0){
			echo "Stopping mysqld safe pid:$pid\n";
			unix_system_kill_force($pid);
			sleep(1);
		}else{break;}
	}
	
	exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.mysql.build.php --multi-stop $instance_id 2>&1",$results);
	foreach ($results as $key=>$value){echo "$value\n";}
	$results=array();
	echo "Start mysqld in normal mode\n";
	exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.mysql.build.php --multi-start $instance_id 2>&1",$results);
	foreach ($results as $key=>$value){echo "$value\n";}
}

function multi_get_pid($ID){
	$unix=new unix();
	$mysqld_safe=$unix->find_program("mysqld_safe");
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$cmd="{$GLOBALS["pgrepbin"]} -l -f \"$mysqld_safe.*?--pid-file=/var/run/mysqld/mysqld$ID.pid\" 2>&1";
	exec($cmd,$results);
	foreach ($results as $index=>$ligne){
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){return $re[1];}
	}
	return null;
}
function multi_get_pidNormal($ID){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/mysqld/mysqld$ID.pid");
	echo "multi_get_pidNormal::$ID:: found $pid in /var/run/mysqld/mysqld$ID.pid\n";
	if($unix->process_exists($pid)){return $pid;}
	$mysqld=$unix->find_program("mysqld");
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$mysqld=str_replace("/", "\/", $mysqld);
	$cmd="{$GLOBALS["pgrepbin"]} -l -f '$mysqld.*?--pid-file=\/var\/run\/mysqld\/mysqld$ID\.pid' 2>&1 >/tmp/$ID.TMP";
	
	pcntl_exec($cmd);
	$results=explode("\n", @file_get_contents("/tmp/$ID.TMP"));
	if($GLOBALS["VERBOSE"]){echo "multi_get_pidNormal::$ID:: $cmd -> ". count($results)."\n";}
	foreach ($results as $index=>$ligne){
		if(preg_match("#pgrep -l#", $ligne)){
			if($GLOBALS["VERBOSE"]){echo "multi_get_pidNormal::$ID:: skip:$ligne\n";}continue;}
			if($GLOBALS["VERBOSE"]){echo "multi_get_pidNormal::$ID:: Found line:$ligne\n";}
			if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){return $re[1];}
			if($GLOBALS["VERBOSE"]){echo "multi_get_pidNormal::$ID:: no match line:$ligne\n";}
	}
	return null;
}



