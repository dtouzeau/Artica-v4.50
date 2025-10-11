<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["instance-status"])){instance_status();exit;}
if(isset($_GET["instance-delete"])){instance_delete();exit;}
if(isset($_GET["instance-reconfigure"])){instance_reconfigure();exit;}
if(isset($_GET["instance-reconfigure-all"])){instance_reconfigure_all();exit;}
if(isset($_GET["instance-service"])){instance_service();exit;}
if(isset($_GET["instance-memory"])){instance_memory();exit;}
if(isset($_GET["multi-root"])){instance_root_set();exit;}
if(isset($_GET["filstats"])){filstats();exit;}
if(isset($_GET["backuptable"])){backuptable();exit;}
if(isset($_GET["mysqlreport"])){mysqlreport();exit;}
if(isset($_GET["MysqlTunerRebuild"])){MysqlTunerRebuild();exit;}
if(isset($_GET["rescan-db"])){mysql_rescan_db();exit;}
if(isset($_GET["dumpwebdb"])){mysql_dump_database();exit;}
if(isset($_GET["convert-innodb-file-persize"])){mysql_convert_innodb();exit;}
if(isset($_GET["getramtmpfs"])){getramtmpfs();exit;}
if(isset($_GET["mysql-upgrade"])){mysql_upgrade();exit;}
if(isset($_GET["restore-db"])){mysql_restore_database();exit;}
if(isset($_GET["restore-exists"])){restore_exists();exit;}
if(isset($_GET["mysql-fnfound"])){REPAIR_TABLE_FILE_NOT_FOUND();exit;}
if(isset($_GET["empty-database"])){EMPTY_DATABASE();exit;}
if(isset($_GET["database-path"])){database_path();exit; }
if(isset($_GET["movedb"])){database_move();exit;}
if(isset($_GET["clean"])){database_clean();exit;}
if(isset($_GET["repair-table"])){repair_table();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}


reset($_GET);
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function repair_table(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mysql.repair.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/mysql.repair.progres.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysqld.crash.php --crashed-table {$_GET["table"]} {$_GET["database"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mysql.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/mysql.install.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.start.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function uninstall(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mysql.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/mysql.install.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.start.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function restart_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mysql.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/mysql.restart.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.start.php --restart --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}

function restore_exists(){
	$unix=new unix();
	$database=$_GET["restore-exists"];
	$pgrep=$unix->find_program("pgrep");
	$cmd="$pgrep -l -f \"mysql.*?--max_allowed_packet.*?$database\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $num=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^[0-9]+.*?sh\s+#", $line)){continue;}
		
		if(preg_match("#^([0-9]+)\s+.*?mysql#", $line,$re)){
			$pid=$re[1];
			writelogs_framework("$pid unix->PROCESS_UPTIME($pid)",__FUNCTION__,__FILE__,__LINE__);
			$time=$unix->PROCESS_UPTIME($pid);
			writelogs_framework("$pid ($time)",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>". base64_encode($time)."</articadatascgi>";
		}
		
	}
	
}

function mysql_restore_database(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();		
	$nohup=$unix->find_program("nohup");
	$database=$_GET["restore-db"];
	$sourcefile=base64_decode($_GET["source"]);
	$instance_id=$_GET["instance-id"];
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.backup.php --restore $instance_id $database \"$sourcefile\" >/dev/null 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function instance_status(){
	$instance_id=$_GET["instance_id"];
	$pidfile="/var/run/mysqld/mysqld$instance_id.pid";
	$unix=new unix();
	$pid=multi_get_pid($instance_id);
	writelogs_framework("$pidfile -> $pid",__FUNCTION__,__FILE__,__LINE__);
	if($unix->process_exists($pid)){echo "<articadatascgi>ON</articadatascgi>";return;}
	echo "<articadatascgi>OFF</articadatascgi>";
}
function instance_memory(){
	$instance_id=$_GET["instance_id"];
	$pidfile="/var/run/mysqld/mysqld$instance_id.pid";
	$unix=new unix();
	$pid=multi_get_pid($instance_id);
	writelogs_framework("$pidfile -> $pid",__FUNCTION__,__FILE__,__LINE__);
	if($unix->process_exists($pid)){
		$rss=$unix->PROCESS_MEMORY($pid,true);
		$vm=$unix->PROCESS_CACHE_MEMORY($pid,true);
		
	}
	
	echo "<articadatascgi>". base64_encode(serialize(array($rss,$vm)))."</articadatascgi>";
}

function instance_service(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();		
	$instance_id=$_GET["instance_id"];
	$action=$_GET["action"];
	$results[]="Action: $action";
	if($action=="start"){
		$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-start $instance_id 2>&1";
	}else{
		$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-stop $instance_id 2>&1";
	}
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}
function instance_root_set(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();		
	$instance_id=$_GET["instance-id"];
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql-multi.php --rootch $instance_id 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";	
}

function multi_get_pid($ID){
	$unix=new unix();
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pidfile)){
		if($unix->process_exists($pid)){
			writelogs_framework("$pidfile ->$pid",__FUNCTION__,__FILE__,__LINE__);
			return $pid;
		}
	}
	
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$cmd="{$GLOBALS["pgrepbin"]} -l -f \"socket=/var/run/mysqld/mysqld$ID.sock\" 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $index=>$ligne){
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){
			writelogs_framework("$ligne -> {$re[1]}",__FUNCTION__,__FILE__,__LINE__);
			return $re[1];}
	}
	return null;
}

function instance_reconfigure(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-start {$_GET["instance-id"]} >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function filstats(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --dbstats --force >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function instance_reconfigure_all(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --multi-start-all >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function MysqlTunerRebuild(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --mysqltuner --force >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
}

function instance_delete(){
	$instance_id=$_GET["instance_id"];
	$pidfile="/var/run/mysqld/mysqld$instance_id.pid";
	$ini=new iniFrameWork("/etc/mysql-multi.cnf");
	$database_path=$ini->get("mysqld$instance_id","datadir");
	$unix=new unix();
	if(is_file("/usr/sbin/mysqlmulti-start{$instance_id}")){@unlink("/usr/sbin/mysqlmulti-start{$instance_id}");}
	if(is_file("/usr/sbin/mysqlmulti-stop{$instance_id}")){@unlink("/usr/sbin/mysqlmulti-stop{$instance_id}");}
	if(is_file("/etc/monit/conf.d/mysqlmulti$instance_id.monitrc")){@unlink("/etc/monit/conf.d/mysqlmulti$instance_id.monitrc");}
	$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");
	
	
	$rm=$unix->find_program("rm");
	$kill=$unix->find_program("kill");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$pid=$unix->get_pid_from_file($pidfile);
	writelogs_framework("$pidfile -> $pid",__FUNCTION__,__FILE__,__LINE__);
	if($unix->process_exists($pid)){
		$cmd="$kill -9 $pid";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}
	writelogs_framework("database path -> '$database_path'",__FUNCTION__,__FILE__,__LINE__);
	if(is_dir($database_path)){
		$cmd="$rm -rf \"$database_path\" 2>&1";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}
	
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --multi";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function backuptable(){
	$PARAMS=unserialize(base64_decode($_GET["backuptable"]));
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	if(!is_file($mysqldump)){
		echo "<articadatascgi>". base64_encode("ERROR: mysqldump no such binary")."</articadatascgi>";
		return;
	}
	
	$t=time();
	$tfile="{$PARAMS["PATH"]}/{$PARAMS["DB"]}.{$PARAMS["TABLE"]}.$t.sql";
	
	if(!is_numeric($PARAMS["PORT"])){$PARAMS["PORT"]=3306;}
	$PARAMS["PASS"]=$unix->shellEscapeChars($PARAMS["PASS"]);
	@mkdir($PARAMS["PATH"],0755,true);
	$cmd="$mysqldump --user={$PARAMS["ROOT"]} --password={$PARAMS["PASS"]} --port={$PARAMS["PORT"]} --host={$PARAMS["HOST"]} {$PARAMS["DB"]} {$PARAMS["TABLE"]} > $tfile 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	if(!is_file($tfile)){
		echo "<articadatascgi>". base64_encode("ERROR: mysqldump $tfile no such file")."</articadatascgi>";
		return;		
	}
	
	$filesize=$unix->file_size($tfile);
	$filesize=round($filesize/1024);
	echo "<articadatascgi>".base64_encode("$tfile ($filesize K) done\n".@implode("\n", $results))."</articadatascgi>";
	
	
	
}
function mysqlreport(){
	$user=base64_decode($_GET["user"]);
	$password=base64_decode($_GET["password"]);
	$socket=base64_decode($_GET["socket"]);
	$hostname=base64_decode($_GET["hostname"]);
	$port=base64_decode($_GET["port"]);
	
	$instanceid=$_GET["instance-id"];
	if(!is_numeric($instanceid)){$instanceid=0;}
	if($instanceid==0){
		$user=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_admin"));
		$password=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/database_password"));
		$mysql_server=trim(@file_get_contents("/etc/artica-postfix/settings/Mysql/mysql_server"));
		if($user==null){$user="root";}
		if($mysql_server==null){$mysql_server="127.0.0.1";}
		if($mysql_server=="localhost"){$mysql_server="127.0.0.1";}
		if($mysql_server=="127.0.0.1"){$socket="/var/run/mysqld/mysqld.sock";}
		
	}
	
	writelogs("password: ".strlen($password),__FUNCTION__,__FILE__,__LINE__);
	
	if($socket<>null){
		if(!is_file($socket)){
			$socket=" --socket $socket";
		}
	}
	
	if($socket==null){
		if($hostname<>null){
			$socket=" --host $hostname --port $port";
		}
	}
	
	if($user<>null){
		$user=" --user $user";
		if($password<>null){
			$user=" --user $user --password \"$password\"";
		}
	}
	
	$unix=new unix();
	$mysqlreport=$unix->find_program("mysqlreport");
	if(strlen($mysqlreport)<4){
		$mysqlreport="/usr/share/artica-postfix/bin/mysqlreport";
		@chmod($mysqlreport, 0755);
	}
	
	$cmd="$mysqlreport$socket$user 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $key=>$value){
	if(preg_match("#Access denied for user#", $value)){
		$results=array();
		$cmd="$mysqlreport$socket --user=root 2>&1";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);
		break;
		}
	}
	
	
	reset($results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}
function mysql_rescan_db(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --database-rescan {$_GET["instance-id"]} {$_GET["database"]} --verbose 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
	
}
function mysql_dump_database(){
	$database=$_GET["database"];
	$instance=$_GET["instance"];
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$cmd="$php5 /usr/share/artica-postfix/exec.mysql.build.php --database-dump {$_GET["database"]} {$_GET["instance-id"]} --verbose 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
}
function mysql_convert_innodb(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/mysqldefrag.php --innodbfpt >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);		
	
}

function getramtmpfs(){
	$dir=base64_decode($_GET["dir"]);
	if($dir==null){return;}
	$unix=new unix();
	$df=$unix->find_program("df");
	$cmd="$df -h \"$dir\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$df -h \"$dir\" 2>&1",$results);
	foreach ($results as $key=>$value){
		if(!preg_match("#tmpfs\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.]+)%#", $value,$re)){
			writelogs_framework("$value no match",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		writelogs_framework("{$re[2]}:{$array["PURC"]}%",__FUNCTION__,__FILE__,__LINE__);
			$array["SIZE"]=$re[1];
			$array["PURC"]=$re[4];
			echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
			return;
		
	}
		
}
function mysql_upgrade(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	if(!is_numeric($_GET["instance-id"])){$_GET["instance-id"]=0;}
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.build.php --mysql-upgrade {$_GET["instance-id"]}  >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}	

function REPAIR_TABLE_FILE_NOT_FOUND(){
	$unix=new unix();
	$file=base64_decode($_GET["mysql-fnfound"]);
	$table=$_GET["table"];
	echo "<articadatascgi>".$unix->MYSQL_REPAIR_TABLE_FILE_NOT_FOUND($file,$table)."</articadatascgi>";
	return true;	
}
function EMPTY_DATABASE(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$db=$_GET["empty-database"];
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.emptydb.php --db $db >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function database_path(){
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	
	$database=base64_decode($_GET["database-path"]);
	if(!is_link("$MYSQL_DATA_DIR/$database")){
		echo "<articadatascgi>". base64_encode("$MYSQL_DATA_DIR/$database")."</articadatascgi>";
		return;
	}

	echo  "<articadatascgi>". base64_encode(readlink("$MYSQL_DATA_DIR/$database"))."</articadatascgi>";

}
function database_move(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$db=$_GET["movedb"];
	$path=$_GET["path"];
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mysql.mvdb.php --db $db $path >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --mysql >/usr/share/artica-postfix/ressources/logs/web/mysql.status 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function searchInSyslog(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["syslog"]));
	$PROTO_P=null;

	foreach ($MAIN as $val=>$key){
		$MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
		$MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

	}

	$max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
	$date=$MAIN["DATE"];
	$PROTO=$MAIN["PROTO"];
	$SRC=$MAIN["SRC"];
	$DST=$MAIN["DST"];
	$SRCPORT=$MAIN["SRCPORT"];
	$DSTPORT=$MAIN["DSTPORT"];
	$IN=$MAIN["IN"];
	$OUT=$MAIN["OUT"];
	$MAC=$MAIN["MAC"];
	$PID=$MAIN["PID"];
	if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

	if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
	if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
	if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
	if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
	if($MAIN["C"]==0){$TERM_P=$TERM;}


	$mainline="{$PID_P}{$TERM_P}{$IN_P}";
	if($TERM<>null){
		if($MAIN["C"]>0){
			$mainline="($mainline|$TERM)";
		}
	}



	$search="$date.*?$mainline";
	$search=str_replace(".*?.*?",".*?",$search);
	$cmd="$grep --binary-files=text -i -E '$search' /var/lib/mysql/mysqld.err |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/mysql.syslog 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/mysql.syslog.pattern", $search);
	shell_exec($cmd);

}