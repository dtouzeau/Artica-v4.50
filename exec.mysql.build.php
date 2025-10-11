<?php
$GLOBALS["SCHEDULE_ID"]=0;if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-server.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql-multi.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["MULTI"]=false;
$GLOBALS["NOMONIT"]=false;
$GLOBALS["DEBUG"]=false;
$GLOBALS["VERBOSE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--multi#",implode(" ",$argv))){$GLOBALS["MULTI"]=true;}
if(preg_match("#--withoutmonit#",implode(" ",$argv))){$GLOBALS["NOMONIT"]=true;}

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$unix=new unix();
$unix->events("Executing ".@implode(" ",$argv));

if($argv[1]=='--tmpfs'){mysql_tmpfs();exit();}
if($argv[1]=='--clean-numericsqu'){CleanBadFiles();exit();}
if($argv[1]=='--mysqldisp'){mysql_display($argv[2],$argv[3]);exit();}
if($argv[1]=='--execute'){execute_sql($argv[2],$argv[3]);exit();}
if($argv[1]=='--database-exists'){execute_database_exists($argv[2]);exit();}
if($argv[1]=='--table-exists'){execute_table_exists($argv[2],$argv[3]);exit();}
if($argv[1]=='--rownum'){execute_rownum($argv[2],$argv[3]);exit();}
if($argv[1]=='--GetAsSQLText'){GetAsSQLText($argv[2]);exit();}
if($argv[1]=='--backup'){Backup($argv[2]);exit();}
if($argv[1]=='--checks'){checks();exit();}
if($argv[1]=='--maintenance'){$unix->events("Executing Maintenance");maintenance();exit();}

if($argv[1]=="--fixmysqldbug"){fixmysqldbug();exit();}
if($argv[1]=="--multi-start"){multi_start($argv[2]);exit();}
if($argv[1]=="--multi-stop"){multi_stop($argv[2]);exit();}
if($argv[1]=="--multi-start-all"){multi_start_all();exit();}
if($argv[1]=="--multi-status"){multi_status();exit();}
if($argv[1]=='--dbstats'){databases_list_fill();exit();}
if($argv[1]=='--multi-dbstats'){multi_databases_parse();exit();}
if($argv[1]=='--mysqltuner'){mysqltuner();exit();}
if($argv[1]=='--database-rescan'){databases_rescan($argv[2],$argv[3]);exit();}
if($argv[1]=='--database-dump'){database_dump($argv[2],$argv[3]);exit();}
if($argv[1]=='--mysql-upgrade'){mysql_upgrade($argv[2]);exit();}
if($argv[1]=='--repair-db'){_repair_database($argv[2]);exit();}





// 


	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid";
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".MAIN.pid.time";
	$pid=$unix->get_pid_from_file($pidfile);
		
	if($unix->process_exists($pid,basename(__FILE__))){writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);exit();}
	
if($argv[1]=='--tables'){checks();exit();}
if($argv[1]=='--imapsync'){rebuild_imapsync();exit();}
if($argv[1]=='--rebuild-zarafa'){rebuild_zarafa();exit();}
if($argv[1]=='--squid-events-purge'){squid_events_purge();exit();}
if($argv[1]=='--mysqlcheck'){mysqlcheck($argv[2],$argv[3],$argv[4]);exit();}



if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")."MySQL no understandeable parameters, build the config by default...\n";}


$sock=new sockets();
$q=new mysqlserver();
$MysqlConfigLevel=$sock->GET_INFO("MysqlConfigLevel");
if(!is_numeric($MysqlConfigLevel)){$MysqlConfigLevel=0;}
$EnableZarafaTuning=$sock->GET_INFO("EnableZarafaTuning");
if(!is_numeric($EnableZarafaTuning)){$EnableZarafaTuning=0;}
$users=new usersMenus();
if($users->ZARAFA_INSTALLED){if($EnableZarafaTuning==1){$MysqlConfigLevel=-1;}}

if($MysqlConfigLevel>0){
	if($MysqlConfigLevel==1){
		echo "Starting......: ".date("H:i:s")."MySQL my.cnf........: SWITCH TO LOWER CONFIG.\n";
		$datas=$q->Mysql_low_config();
	}
	
	if($MysqlConfigLevel==2){
		echo "Starting......: ".date("H:i:s")."MySQL my.cnf........: SWITCH TO VERY LOWER CONFIG.\n";
		$datas=$q->Mysql_verlow_config();
	}	
}


if($MysqlConfigLevel==0){
	$unix=new unix();
	$mem=$unix->TOTAL_MEMORY_MB();
	echo "\n";
	echo "Starting......: ".date("H:i:s")." MySQL my.cnf........: Total memory {$mem}MB\n";
	
	if($mem<550){
		echo "Starting......: ".date("H:i:s")." MySQL my.cnf........: SWITCH TO LOWER CONFIG.\n";
		$datas=$q->Mysql_low_config();
		if($mem<390){
			echo "Starting......: ".date("H:i:s")." MySQL my.cnf........: SWITCH TO VERY LOWER CONFIG.\n";
			$datas=$q->Mysql_verlow_config();
		}
	}else{
		$datas=$q->BuildConf();
	}
}

if($MysqlConfigLevel==-1){
	echo "Starting......: ".date("H:i:s")." MySQL my.cnf........: SWITCH TO PERSONALIZED CONFIG.\n";
	$datas=$q->BuildConf();
}

$mycnf=$argv[1];
if(!is_file($mycnf)){$mycnf=LOCATE_MY_CNF();}
if(!is_file($mycnf)){echo "Starting......: ".date("H:i:s")." Mysql my.cnf........: unable to stat {$argv[1]}\n";exit();}

@file_put_contents($mycnf,$datas);
echo "Starting......: ".date("H:i:s")." Mysql Updating \"$mycnf\" success ". strlen($datas)." bytes\n";

function checks($nodestroy=false){
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
	$q=new mysql();
	$q->BuildTables();	
	$execute=false;
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$rm=$unix->find_program("rm");	
	
$tableEngines = array("hardware"=>"InnoDB","accesslog"=>"InnoDB","bios"=>"InnoDB","memories"=>"InnoDB","slots"=>"InnoDB",
"registry"=>"InnoDB","monitors"=>"InnoDB","ports"=>"InnoDB","storages"=>"InnoDB","drives"=>"InnoDB","inputs"=>"InnoDB",
"modems"=>"InnoDB","networks"=>"InnoDB","printers"=>"InnoDB","sounds"=>"InnoDB","videos"=>"InnoDB","softwares"=>"InnoDB",
"accountinfo"=>"InnoDB","netmap"=>"InnoDB","devices"=>"InnoDB", "locks"=>"HEAP");	
	
	if(is_file("/usr/share/artica-postfix/bin/install/ocsbase_new.sql")){
		if(!$q->DATABASE_EXISTS("ocsweb")){$execute=true;}
		if(!$execute){
			while (list ($table, $ligne) = each ($tableEngines) ){
				if(!$q->TABLE_EXISTS($table,"ocsweb")){
					if($GLOBALS["VERBOSE"]){echo "$table does not exists...\n";}
					$execute=true;break;}else{
						if($GLOBALS["VERBOSE"]){echo "ocsweb/$table OK...\n";}	
					}
			}
		}
		
	}
	reset($tableEngines);
	
	if($execute){
		$results=array();

		while (list ($table, $ligne) = each ($tableEngines) ){
			if(!$q->TABLE_EXISTS($table,"ocsweb")){
				repairocsweb();
				if($GLOBALS["VERBOSE"]){echo "Unable to create OCS table (missing $table) table\n";}
				return;
			
			}
		}
	}
		
	$sql="SELECT COUNT(networks.HARDWARE_ID),networks.*,hardware.* FROM networks,hardware WHERE networks.HARDWARE_ID=hardware.ID";
	$q->QUERY_SQL($sql,"ocsweb");
	if(!$q->ok){
		if(preg_match("#Table '(.*?)' doesn't exist#", $q->mysql_error)){
			if(!$nodestroy){
				$q->DELETE_DATABASE("ocsweb");
				if(is_dir("$MYSQL_DATA_DIR/ocsweb")){echo "Starting......: ".date("H:i:s")." OCS removing $MYSQL_DATA_DIR/ocsweb\n";shell_exec("$rm -rf $MYSQL_DATA_DIR/ocsweb");}
				checks(true);
			}
		}
	}
	
	
}


function repairocsweb(){
	$unix=new unix();
	$q=new mysql();
	$q->CREATE_DATABASE("ocsweb");
	
	
	$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `hardware` (
				  `ID` int(11) NOT NULL AUTO_INCREMENT,
				  `DEVICEID` varchar(255) NOT NULL,
				  `NAME` varchar(255) DEFAULT NULL,
				  `WORKGROUP` varchar(255) DEFAULT NULL,
				  `USERDOMAIN` varchar(255) DEFAULT NULL,
				  `OSNAME` varchar(255) DEFAULT NULL,
				  `OSVERSION` varchar(255) DEFAULT NULL,
				  `OSCOMMENTS` varchar(255) DEFAULT NULL,
				  `PROCESSORT` varchar(255) DEFAULT NULL,
				  `PROCESSORS` int(11) DEFAULT '0',
				  `PROCESSORN` smallint(6) DEFAULT NULL,
				  `MEMORY` int(11) DEFAULT NULL,
				  `SWAP` int(11) DEFAULT NULL,
				  `IPADDR` varchar(255) DEFAULT NULL,
				  `DNS` varchar(255) DEFAULT NULL,
				  `DEFAULTGATEWAY` varchar(255) DEFAULT NULL,
				  `ETIME` datetime DEFAULT NULL,
				  `LASTDATE` datetime DEFAULT NULL,
				  `LASTCOME` datetime DEFAULT NULL,
				  `QUALITY` decimal(7,4) DEFAULT NULL,
				  `FIDELITY` bigint(20) DEFAULT '1',
				  `USERID` varchar(255) DEFAULT NULL,
				  `TYPE` int(11) DEFAULT NULL,
				  `DESCRIPTION` varchar(255) DEFAULT NULL,
				  `WINCOMPANY` varchar(255) DEFAULT NULL,
				  `WINOWNER` varchar(255) DEFAULT NULL,
				  `WINPRODID` varchar(255) DEFAULT NULL,
				  `WINPRODKEY` varchar(255) DEFAULT NULL,
				  `USERAGENT` varchar(50) DEFAULT NULL,
				  `CHECKSUM` bigint(20) unsigned DEFAULT '262143',
				  `SSTATE` int(11) DEFAULT '0',
				  `IPSRC` varchar(255) DEFAULT NULL,
				  `UUID` varchar(255) DEFAULT NULL,
				  PRIMARY KEY (`DEVICEID`,`ID`),
				  KEY `NAME` (`NAME`),
				  KEY `CHECKSUM` (`CHECKSUM`),
				  KEY `USERID` (`USERID`),
				  KEY `WORKGROUP` (`WORKGROUP`),
				  KEY `OSNAME` (`OSNAME`),
				  KEY `MEMORY` (`MEMORY`),
				  KEY `DEVICEID` (`DEVICEID`),
				  KEY `ID` (`ID`)
				) ENGINE=InnoDB DEFAULT CHARSET=UTF8;","ocsweb");
	$mysql=$unix->find_program("mysql");
	$password=$q->mysql_password;
	if(strlen($password)>0){$password=" -p$password";}
	$cmd="$mysql -u $q->mysql_admin$password --batch -h $q->mysql_server -P $q->mysql_port -D ocsweb < /usr/share/artica-postfix/bin/install/ocsbase_new.sql";
	exec($cmd,$results);
	
	foreach ($results as $a=>$b){
		if($GLOBALS["VERBOSE"]){echo "$b";}
	}	
	
}


function mysqld_version(){
	if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
	exec("$mysqld --version 2>&1",$results);
	foreach ($results as $num=>$ligne){

		if(preg_match("#mysqld.*?([0-9\.\-]+)#", $ligne,$re)){
			$GLOBALS[__FUNCTION__]=$re[1];
			return $GLOBALS[__FUNCTION__];
		}
	}
}

function multi_status(){
	
	
if(!is_file("/etc/mysql-multi.cnf")){exit();}
if(system_is_overloaded(basename(__FILE__))){writelogs("Fatal: {OVERLOADED_SYSTEM} Load:{$GLOBALS["SYSTEM_INTERNAL_LOAD"]} Memory: {$GLOBALS["SYSTEM_INTERNAL_MEMM"]}MB ,die()","MAIN",__FILE__,__LINE__);exit();}


	$ini=new iniFrameWork("/etc/mysql-multi.cnf");
	$INSTANCES=array();
	
	while (list ($key, $line) = each ($ini->_params)){
		if(preg_match("#^mysqld([0-9]+)#", $key,$re)){
			$instance_id=$re[1];
			$INSTANCES[$instance_id]=true;
		}
	}	
	if(count($INSTANCES)==0){exit();}
	$unix=new unix();
	
	$mysqlversion=mysqld_version();
	while (list ($instance_id, $line) = each ($INSTANCES)){
		$master_pid=multi_get_pid($instance_id);
		$l[]="[ARTICA_MYSQL:$instance_id]";
			$l[]="service_name=APP_MYSQL_ARTICA";
			$l[]="master_version=$mysqlversion";
			$l[]="service_cmd=mysql:$instance_id";
			$l[]="service_disabled=1";
			$l[]="watchdog_features=1";
			$l[]="family=system";
			 
			$status=$unix->PROCESS_STATUS($master_pid);
			if($GLOBALS["VERBOSE"]){echo "Mysqld status = $status\n";
			print_r($status);}
			
			 
			if(!$unix->process_exists($master_pid)){
				multi_start($instance_id);
				$l[]="running=0";
			}else{
				$l[]="running=1";
				$l[]=$unix->GetMemoriesOf($master_pid);
				$l[]="";
			}		
		
	}
	echo @implode("\n", $l);
	
}

function mysql_upgrade($instanceid){
	if(!is_numeric($instanceid)){$instanceid=0;}
	$unix=new unix();
	$mysql_upgrade=$unix->find_program("mysql_upgrade");
	if(!is_file($mysql_upgrade)){echo "mysql_upgrade no such bin...\n";return;}
	$myisamchk=$unix->find_program("myisamchk");
	$q=new mysql();
	if($q->mysql_server=="127.0.0.1"){$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";}else{$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";}
	if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
	$cmdline="$mysql_upgrade --user=$q->mysql_admin$password $servcmd 2>&1";	
	$cmdchk="$myisamchk -c -r -f mysql/*";
	if($instanceid>0){
		$q=new mysql_multi($instanceid);
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysql_upgrade --user=$q->mysql_admin$password --socket=$q->SocketPath 2>&1";
		$cmdchk="$myisamchk -c -r -f mysql/* --defaults-file=/etc/mysql-multi.cnf";
	}
	
	//mysqlcheck -c -f --auto-repair --user=root --password=WinAccra96   --socket=/var/run/mysqld/mysqld.sock --databases mysql zarafa1 zarafa zarafa2
	
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n$cmdchk\n";}
	shell_exec($cmdline);
	shell_exec($cmdchk); 
	maintenance(true);
	
}



function database_dump($database,$instanceid){
	$q=new mysql();
	
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	if(!is_file($mysqldump)){return;}
	$options="--add-drop-table --no-create-info --no-create-db --skip-comments";
	echo "Dump $database with instance $instanceid ($mysqldump)\n";
	
	if($instanceid>0){
		$q=new mysql_multi($instanceid);
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqldump --user=$q->mysql_admin$password --socket=$q->SocketPath $options --databases $database >/tmp/$database.sql 2>&1";
		
	}else{
		$q=new mysql();
		if($q->mysql_server=="127.0.0.1"){
			$servcmd=" --socket=/var/run/mysqld/mysqld.sock ";
		}else{
			$servcmd=" --host=$q->mysql_server --port=$q->mysql_port ";
		}
		if($q->mysql_password<>null){$password=" --password=$q->mysql_password ";}
		$cmdline="$mysqldump --user=$q->mysql_admin$password $servcmd $options --databases $database >/tmp/$database.sql 2>&1";
	}
	$results[]=$cmdline;
	exec($cmdline,$results);
	echo @implode("\n", $results);
	compress("/tmp/$database.sql",PROGRESS_DIR."/$database.gz");
	@unlink("/tmp/$database.sql");
	@chmod(PROGRESS_DIR."/$database.gz", 0777);
	
	
}
function compress($source,$dest){
		if(!function_exists("gzopen")){
			$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
			writelogs("Fatal!! gzopen no such function ! $called in ".__FUNCTION__." line ".__LINE__, basename(__FILE__));
			return false;
		}
	    $mode='wb9';
	    $error=false;
	    if(is_file($dest)){@unlink($dest);}
	    $fp_out=gzopen($dest,$mode);
	    if(!$fp_out){return;}
	    $fp_in=fopen($source,'rb');
	    if(!$fp_in){return;}
	    while(!feof($fp_in)){gzwrite($fp_out,fread($fp_in,1024*512));}
	    fclose($fp_in);
	    gzclose($fp_out);
		return true;
	}

function databases_rescan($instanceid=0,$database=null):bool{
    $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
    if($EnableMySQL==0){return false;}
    if(is_numeric($database)){
        return false;
    }
	if($instanceid>0){
		multi_databases_list_tables($instanceid,$database);
		return true;
	}
	databases_list_tables($database);
    return true;
}


function multi_databases_parse(){
	$unix=new unix();
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("{OVERLOADED_SYSTEM}, aborting task",__FUNCTION__,__FILE__,__LINE__);
		return;
	}	
	
	$sql="SELECT ID FROM mysqlmulti WHERE enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		multi_databases_list_fill($ligne["ID"]);
		
	}	
	
	
}

function multi_databases_list_fill($instance_id){
	
	$prefix="INSERT IGNORE INTO mysqldbsmulti (instance_id,databasename,TableCount,dbsize) VALUES ";
	$q=new mysql_multi($instance_id);
	$q2=new mysql();
	$databases=$q->DATABASE_LIST_SIMPLE();
	if($GLOBALS["VERBOSE"]){echo "Found ". count($databases)." databases\n";}	
	while (list ($database, $ligne) = each ($databases) ){
		
		$rr=multi_databases_list_tables($instance_id,$database);
		$TableCount=$rr[0];
		$Size=$rr[1];
		if($GLOBALS["VERBOSE"]){echo "Found database `$database` $TableCount tables ($Size)\n";}
		$f[]="($instance_id,'$database','$TableCount','$Size')";
		
	}
	
	
	if(count($f)>0){
		$q2->QUERY_SQL("DELETE FROM mysqldbsmulti WHERE instance_id='$instance_id'","artica_backup");
		$q2->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		
	}		
}
function multi_databases_list_tables($instance_id,$database){
	$sql="show TABLE STATUS";
	$q=new mysql_multi($instance_id);
	$prefix="INSERT IGNORE INTO mysqldbtablesmulti (instance_id,tablename,databasename,tablesize,tableRows) VALUES ";
	$dbsize=0;
	$count=0;
	$f=array();
	$results=$q->QUERY_SQL($sql,$database);
	$q2=new mysql();
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$tablesize=$ligne['Data_length'] + $ligne['Index_length'];
			$dbsize += $tablesize; 
			$Rows=$ligne["Rows"];
			$count=$count+1;
			$tablename=$ligne["Name"];
			eventsDB("Found table `$database/$tablename $tablesize $Rows rows`",__LINE__);
			$f[]="($instance_id,'$tablename','$database','$tablesize','$Rows')";
	}
	
	if(count($f)>0){
		$q2->QUERY_SQL("DELETE FROM mysqldbtablesmulti WHERE databasename='$database' AND instance_id='$instance_id'","artica_backup");
		$q2->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		
	}
	
	
	return array($count,$dbsize);
	}


	function eventsDB($text,$line){
		if($GLOBALS["VERBOSE"]){echo "[$line]: $text\n";}
		$pid=getmypid();
		$date=date('Y-m-d H:i:s');
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/databases-stats.log";
		$size=@filesize($logFile);
		if($size>5000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid]:Line:$line  $text\n");
		@fclose($f);
	}

function databases_list_fill(){
	$unix=new unix();
	if(system_is_overloaded(basename(__FILE__))){
		writelogs("{OVERLOADED_SYSTEM}, aborting task",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	if(!$GLOBALS["FORCE"]){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$pid=$unix->get_pid_from_file($pidfile);
		
		if($unix->process_exists($pid,basename(__FILE__))){
			writelogs("Already process $pid exists",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
		
		$time=$unix->file_time_min($pidfileTime);
		if($time<20){
			if($GLOBALS["VERBOSE"]){echo "Minimal time = 20Mn (current is {$time}Mn)\n";}
			return;
		}
		@unlink($pidfileTime);
		@file_put_contents($pidfileTime, time());
		@file_put_contents($pidfile, getmypid());
	}
	
	if($GLOBALS["VERBOSE"]){echo "databases_list_fill() executed\n";}
	$prefix="INSERT IGNORE INTO mysqldbs (databasename,TableCount,dbsize) VALUES ";
	$q=new mysql();
	if(!$q->TABLE_EXISTS('mysqldbs','artica_backup')){
	if($GLOBALS["VERBOSE"]){echo "check_storage_table()\n";}$q->check_storage_table(true);}	
	
	
	eventsDB("DATABASE_LIST_SIMPLE()",__LINE__);
	$databases=$q->DATABASE_LIST_SIMPLE();
	eventsDB("DATABASE_LIST_SIMPLE() fone",__LINE__);
	eventsDB("Found ". count($databases)." databases -> dROP mysqldbtables",__LINE__);
	
	$q->QUERY_SQL("DROP TABLE mysqldbtables","artica_backup");
	
	eventsDB("BuildTables()...",__LINE__);
	if(!class_exists("mysql_builder")){include_once(dirname(__FILE__)."/ressources/class.mysql.builder.inc");}
	$t=new mysql_builder();
	$t->check_mysql_dbtables();
	
	
	
	while (list ($database, $ligne) = each ($databases) ){
		eventsDB("-> databases_list_tables($database)...",__LINE__);
		$rr=databases_list_tables($database);
		$TableCount=$rr[0];
		$Size=$rr[1];
		eventsDB("Found database `$database` $TableCount tables ($Size)",__LINE__);
		$f[]="('$database','$TableCount','$Size')";
		
	}
	
	
	if(count($f)>0){
		eventsDB("Inbjecting ".count($f)." elements...",__LINE__);
		$q->QUERY_SQL("TRUNCATE TABLE mysqldbs","artica_backup");
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		
	}	
	
	eventsDB("multi_databases_parse()",__LINE__);
	multi_databases_parse();
	eventsDB("multi_databases_parse() done...",__LINE__);
	@file_put_contents($pidfileTime, time());
}

	
function databases_list_tables($database){
	$sql="show TABLE STATUS";
	$q=new mysql();
	$prefix="INSERT INTO mysqldbtables (zKey,tablename,databasename,tablesize,tableRows) VALUES ";
	$dbsize=0;
	$count=0;
	$f=array();
	$results=$q->QUERY_SQL($sql,$database);
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$tablesize=$ligne['Data_length'] + $ligne['Index_length'];
			$dbsize += $tablesize; 
			$Rows=$ligne["Rows"];
			$count=$count+1;
			$tablename=$ligne["Name"];
			$zKey=md5("$tablename$database");
			if($GLOBALS["VERBOSE"]){echo "Found table `$database/$tablename $tablesize $Rows rows`\n";}	
			$f[]="('$zKey','$tablename','$database','$tablesize','$Rows')";
	}
	
	if(count($f)>0){
		$q->QUERY_SQL("DELETE FROM mysqldbtables WHERE databasename='$database'","artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_backup");
		if(!$q->ok){echo $q->mysql_error."\n";}
		
	}
	
	echo "Filling DB for $database : ".count($f)." items..\n";
	return array($count,$dbsize);
	}


function multi_start_all(){
	$q=new mysqlserver();
	$GLOBALS["MULTI"]=true;
	$q->mysql_multi();
	$sql="SELECT ID  FROM `mysqlmulti` WHERE enabled=1 ORDER BY ID DESC";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){return;}
	while ($ligne = mysqli_fetch_assoc($results)) {multi_start($ligne["ID"]);}
	
}

function multi_create_cache(){
	if(isset($GLOBALS["CACHECREATED"])){return;}
	$sql="SELECT ID,servername  FROM `mysqlmulti` WHERE enabled=1 ORDER BY ID DESC";
	$q=new mysql();
	$results = $q->QUERY_SQL($sql,'artica_backup');
	if(!$q->ok){return;}
	while ($ligne = mysqli_fetch_assoc($results)) {
		$ARR[$ligne["ID"]]=$ligne["servername"];
	}
	
	@file_put_contents("/etc/artica-postfix/mysql_multi_names.cache", serialize($ARR));
	$GLOBALS["CACHECREATED"]=true;
}

function multi_get_pid($ID){
	$unix=new unix();
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pid)){
		if($unix->process_exists($pid)){return $pid;}
	}
	
	if(!isset($GLOBALS["pgrepbin"])){$GLOBALS["pgrepbin"]=$unix->find_program("pgrep");}
	$cmd="{$GLOBALS["pgrepbin"]} -l -f \"socket=/var/run/mysqld/mysqld$ID.sock\" 2>&1";
	exec($cmd,$results);
	foreach ($results as $index=>$ligne){
		if(preg_match("#pgrep -l#", $ligne)){continue;}
		if(preg_match("#^([0-9]+)\s+#", $ligne,$re)){return $re[1];}
	}
	return null;
}

function multi_stop($ID){
	if(!is_numeric($ID)){echo "Stopping......: ".date("H:i:s")."Mysql instance no id specified\n";return;}
	$PID=multi_get_pid($ID);
	echo "Stopping......: ".date("H:i:s")."Mysql instance id:$ID PID:$PID..\n";
	$unix=new unix();
	if(!$unix->process_exists($PID)){
		echo "Stopping......: ".date("H:i:s")."Mysql instance id:$ID already stopped..\n";
		return;
	}
	$mysqld_multi=$unix->find_program("mysqld_multi");
	$kill=$unix->find_program("kill");
	$cmd="$mysqld_multi --defaults-file=/etc/mysql-multi.cnf start $ID 2>&1";
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	foreach ($results as $index=>$ligne){echo "Stopping......: ".date("H:i:s")."Mysql instance id:$ID $ligne\n";}
	sleep(1);
	
	for($i=0;$i<10;$i++){
		$PID=multi_get_pid($ID);
		if(!$unix->process_exists($PID)){break;}
		if(is_numeric($PID)){
			$cmd="$kill -9 $PID";
			echo "Stopping......: ".date("H:i:s")."Mysql instance id:$ID killing PID: $PID\n";
			shell_exec($cmd);
			sleep(1);
		}
	}
	$PID=multi_get_pid($ID);
	if(!$unix->process_exists($PID)){
		echo "Stopping......: ".date("H:i:s")."Mysql instance id:$ID success..\n";
		return;
	}	
	echo "Stopping......: ".date("H:i:s")."Mysql instance id:$ID failed..\n";
}

function multi_start($ID){
	$q=new mysqlserver();
	$GLOBALS["MULTI"]=true;
	$GLOBALS["SHOWLOGONLYFOR"]=$ID;
	multi_monit($ID);
	multi_create_cache();
	$q->mysql_multi();
	echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID..\n";
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID PID:$pidfile..\n";
	$unix=new unix();
	if($unix->process_exists($unix->get_pid_from_file($pidfile))){echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID already running...\n";return;}
	$chmod=$unix->find_program("chmod");
	$ini=new iniFrameWork("/etc/mysql-multi.cnf");
	$database_path=$ini->get("mysqld$ID","datadir");
	if(is_file("$database_path/error.log")){@unlink("$database_path/error.log");}
	echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID database=$database_path\n";
	
	$cmd="$chmod 755 $database_path";
	exec($cmd,$results);
	$mysqld_multi=$unix->find_program("mysqld_multi");
	$cmd="$mysqld_multi --defaults-file=/etc/mysql-multi.cnf start $ID --verbose --no-log 2>&1";
	if(is_file("$database_path/maria_log_control")){@unlink("$database_path/maria_log_control");}
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	exec($cmd,$results);
	foreach ($results as $index=>$ligne){echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID $ligne\n";}
	
	for($i=0;$i<4;$i++){
		sleep(1);
		if($unix->process_exists(multi_get_pid($ID))){sleep(1);break;}
	}
	
	if(!$unix->process_exists(multi_get_pid($ID))){
		echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID failed..\n";
	}else{
		$q=new mysql_multi($ID);
		$q->QUERY_SQL_NO_BASE("create user 'mysqld_multi'@'127.0.0.1' identified by 'mysqld_multi'");
		$q->QUERY_SQL_NO_BASE("create user 'mysqld_multi'@'localhost' identified by 'mysqld_multi'");
		$q->QUERY_SQL_NO_BASE("create user 'grant shutdown on *.* to mysqld_multi'");
		$q=new mysqlserver_multi($ID);
		$q->setssl();
		
	}
		if(is_file("$database_path/error.log")){
			echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID $database_path/error.log\n";
			$f=explode("\n",@file_get_contents("$database_path/error.log"));
			foreach ($f as $index=>$ligne){
				if(trim($ligne)==null){continue;}
				if(preg_match("#^[0-9]+\s+[0-9\:]+\s+(.+)#", $ligne,$re)){$ligne=$re[1];}
				echo "Starting......: ".date("H:i:s")." $ligne\n";
			}
		}else{
			echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID $database_path/error.log no such file\n";
		}
}

function multi_monit($ID){
	if($GLOBALS["NOMONIT"]){return;}
	$unix=new unix();
	$monit=$unix->find_program("monit");
	$chmod=$unix->find_program("chmod");
	if(!is_file($monit)){return;}
	$q=new mysql_multi($ID);
	$reloadmonit=false;
	$monit_file="/etc/monit/conf.d/mysqlmulti$ID.monitrc";
	$pidfile="/var/run/mysqld/mysqld$ID.pid";
	
	if($q->watchdog==0){
		echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID monit is not enabled ($q->watchdog)\n";
		if(is_file($monit_file)){
			@unlink($monit_file);
			@unlink("/usr/sbin/mysqlmulti-start{$ID}");
			@unlink("/usr/sbin/mysqlmulti-stop{$ID}");
			$reloadmonit=true;}
	}
	
	if($q->watchdog==1){
		echo "Starting......: ".date("H:i:s")." Mysql instance id:$ID monit is enabled\n";
		$reloadmonit=true;
		$f[]="check process mysqlmulti{$ID}";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"/usr/sbin/mysqlmulti-start{$ID}\"";
   		$f[]="stop program =  \"/usr/sbin/mysqlmulti-stop{$ID}\"";
   		if($q->watchdogMEM>0){
  			$f[]="if totalmem > $q->watchdogMEM MB for 5 cycles then alert";
   		}
   		if($q->watchdogCPU>0){
   			$f[]="if cpu > $q->watchdogCPU% for 5 cycles then alert";
   		}
	   $f[]="if 5 restarts within 5 cycles then timeout";
	   
	   @file_put_contents($monit_file, @implode("\n", $f));
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --multi-start $ID --withoutmonit";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/mysqlmulti-start{$ID}", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/mysqlmulti-start{$ID}");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]=$unix->LOCATE_PHP5_BIN()." ".__FILE__." --multi-stop $ID --withoutmonit";
	   $f[]="exit 0\n";
 	   @file_put_contents("/usr/sbin/mysqlmulti-stop{$ID}", @implode("\n", $f));
 	   shell_exec("$chmod 777 /usr/sbin/mysqlmulti-stop{$ID}");	   
	}
	
	if($reloadmonit){
		$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");
	}
	
}


function rebuild_imapsync(){
	$q=new mysql();
	writelogs("DELETE imapsync table...",__FUNCTION__,__FILE__,__LINE__);
	$sql="DROP TABLE `imapsync`";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$sql:: $q->mysql_error\n";}
	writelogs("Rebuild tables",__FUNCTION__,__FILE__,__LINE__);
	$q->BuildTables();
	}
	
function rebuild_zarafa(){
	$q=new mysql();
	$q->DELETE_DATABASE("zarafa");
	shell_exec("/etc/init.d/artica-postfix restart zarafa");
	}
	
function execute_sql($filename,$database){
	$q=new mysql();
	$q->QUERY_SQL(@file_get_contents($filename),$database);
	if(!$q->ok){echo "ERROR: $q->mysql_error";}
	
}
function execute_database_exists($database){
	$q=new mysql();
	if(!$q->DATABASE_EXISTS($database)){echo "FALSE\n";exit();}
	echo "TRUE\n";
	
}
function execute_table_exists($database,$table){
	$q=new mysql();
	if(!$q->TABLE_EXISTS($table,$database)){echo "FALSE\n";exit();}
	echo "TRUE\n";
	
}
function execute_create_database($database,$table){
	$q=new mysql();
	if(!$q->TABLE_EXISTS($table,$database)){echo "FALSE\n";exit();}
	echo "TRUE\n";
	
}
function execute_rownum($database,$table){
	$q=new mysql();
	$table=trim($table);
	$sql="SELECT count(*) as tcount FROM $table";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,$database));
	if($ligne["tcount"]==null){echo "0\n";return;}
	echo "{$ligne["tcount"]}\n";
}
function GetAsSQLText($filename){
	$datas=@file_get_contents($filename);
	$datas=addslashes($datas);
	@file_put_contents($filename,$datas);
}

function squid_events_purge(){
	$q=new mysql();
	$t1=time();
	$sock=new sockets();
	$nice=EXEC_NICE();
	$squidMaxTableDays=$sock->GET_INFO("squidMaxTableDays");
	$squidMaxTableDaysBackup=$sock->GET_INFO("squidMaxTableDaysBackup");
	$squidMaxTableDaysBackupPath=$sock->GET_INFO("squidMaxTableDaysBackupPath");
	if($squidMaxTableDays==null){$squidMaxTableDays=730;}
	if($squidMaxTableDaysBackup==null){$squidMaxTableDaysBackup=1;}
	if($squidMaxTableDaysBackupPath==null){$squidMaxTableDaysBackupPath="/home/squid-mysql-bck";}
	

	$sql="SELECT COUNT( ID ) as tcount FROM `dansguardian_events` WHERE `zDate` < DATE_SUB( NOW( ) , INTERVAL $squidMaxTableDays DAY )";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));
	$events_number=$ligne["tcount"];
	if($events_number==0){return;}
	if($events_number<0){return;}
	if(!is_numeric($events_number)){return;}
	
	$unix=new unix();
	$mysqldump=$unix->find_program("mysqldump");
	$gzip_bin=$unix->find_program("gzip");
	$stat_bin=$unix->find_program("stat");
	
	if($squidMaxTableDaysBackup==1){
			
			if(!is_file($mysqldump)){
				send_email_events("PURGE: unable to stat mysqldump the backup cannot be performed",
				"task aborted, uncheck the backup feature if you want to purge without backup",
				"proxy");
				return;
			}
			
			if(strlen($squidMaxTableDaysBackupPath)==0){
				send_email_events("PURGE: backup path was not set",
				"task aborted, uncheck the backup feature if you want to purge without backup",
				"proxy");
				return;		
			}
			@mkdir($squidMaxTableDaysBackupPath,600,true);
			$targeted_path="$squidMaxTableDaysBackupPath/".date("Y-m-d").".".time().".sql";
			$dumpcmd="$nice$mysqldump -u $q->mysql_admin -p$q->mysql_password -h $q->mysql_server artica_events dansguardian_events";
			$dumpcmd=$dumpcmd." -w \"zDate < DATE_SUB( NOW( ) , INTERVAL $squidMaxTableDays DAY )\" >$targeted_path";
			
			exec($dumpcmd,$results);
			$text_results=@implode("\n",$results);
			if(!is_file("$targeted_path")){
				send_email_events("PURGE: failed dump table",
				"task aborted,$targeted_path no such file\n$text_results\n uncheck the backup feature if you want to purge without backup\n$dumpcmd",
				"proxy");
				return;
			}
			
			if(is_file($gzip_bin)){
				$targeted_path_gz=$targeted_path.".gz";
				shell_exec("$nice$gzip_bin $targeted_path -c >$targeted_path_gz 2>&1");
				if(is_file($targeted_path_gz)){
					@unlink($targeted_path);
					$targeted_path=$targeted_path_gz;
				}
			}
			
			unset($results);
			exec("$stat_bin -c %s $targeted_path",$results);
			$filesize=trim(@implode("",$results));
			$filesize=$filesize/1024;
			$filesize=FormatBytes($filesize);
			$filesize=str_replace("&nbsp;"," ",$filesize);
	}
	
	$sql="DELETE FROM `dansguardian_events` WHERE `zDate` < DATE_SUB( NOW( ) , INTERVAL $squidMaxTableDays DAY )";
	$q=new mysql();
	$q->QUERY_SQL($sql,"artica_events");
	
	if(!$q->ok){
		send_email_events("PURGE: failed removing $events_number elements",
		"task aborted,unable to delete $events_number elements,\nError:$q->mysql_error\n$sql",
		"proxy");
		return;	
	}
			
	$t2=time();
	
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);
	
	if($squidMaxTableDaysBackup==1){
		$backuptext="\nRemoved elements are backuped on your specified folder:$squidMaxTableDaysBackupPath\nBackuped datas file:$targeted_path ($filesize)";
	}
	
	send_email_events("PURGE: success removing $events_number elements",
	"task successfully executed.\nExecution time:$distanceOfTimeInWords\nBackuped datas:$targeted_path",
	"proxy");	
	
	
}

function Backup($table){
	$q=new mysql();
	$q->BackupTable($table,"artica_backup");
	
}

function mysql_display($table,$database){
	if($database==null){$database="artica_backup";}
	$q=new mysql();
	$sql="SELECT * FROM $table LIMIT 0,1";
	$results=$q->QUERY_SQL($sql,$database);
	$len = mysql_num_fields($results);
	
	for ($i = 0; $i < $len; $i++) {
		$name = mysql_field_name($results, $i);
		$lines[]=$name;
		
		
		$fields[$name]=true;
			
	} 	
	echo @implode(" | ", $lines)."\n";
	
	
	$sql="SELECT * FROM $table";
	$results=$q->QUERY_SQL($sql,$database);	
	while ($ligne = mysqli_fetch_assoc($results)) {
		reset($fields);
		unset($f);
		while (list ($a, $b) = each ($fields) ){
			$f[]=$ligne[$a];
			
		}
		echo @implode(" | ", $f)."\n";
		
	}
	
	
	
}

function mysqlcheck_squidlogs($table){
	$time1=time();
	$unix=new unix();
	$mysqlcheck=$unix->find_program("mysqlcheck");
	$q=new mysql_squid_builder();
	$pgrep=$unix->find_program("pgrep");
	$myisamchk=$unix->find_program("myisamchk");
	$touch=$unix->find_program("touch");
	$time1=time();
	$MYSQL_CMDLINES=$q->MYSQL_CMDLINES;
	$cmd="$mysqlcheck $MYSQL_CMDLINES -c -f --auto-repair squidlogs $table";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	$time_duration=distanceOfTimeInWords($time1,time());
	
	$q->QUERY_SQL("OPTIMIZE TABLE `$table`");
	if(!$q->ok){
		$OPT="\nOptimize:$q->mysql_error\n";
	}else{
		$OPT="\nOptimize: Success...\n";
	}
	

	
	
	exec("$pgrep -l -f \"$myisamchk.*?$table\"",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^[0-9]+\s+#", $line)){
			writelogs("$line already executed",@implode("\r\n", $results),__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}
	
	
	if(!is_file("/opt/squidsql/data/squidlogs/$table.MYI")){return;}
	exec("$myisamchk --safe-recover /opt/squidsql/data/squidlogs/$table.MYI 2>&1",$results);
	foreach ($results as $index=>$line){
		echo $line."\n";
	}
	
}


function mysqlcheck($db,$table,$instance_id){
	if($GLOBALS["VERBOSE"]){echo "START:: ".__FUNCTION__."\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	$unix=new unix();
	if($unix->process_exists($pid)){
		echo "Process already exists pid $pid\n";
		return;
	}
	
	if($db=="squidlogs"){mysqlcheck_squidlogs($table);return;}
	
	if(!is_numeric($instance_id)){$instance_id=0;}
	
	$time1=time();
	$mysqlcheck=$unix->find_program("mysqlcheck"); 
	$q=new mysql();
	$pass=null;
	if($q->mysql_password<>null){$pass="-p$q->mysql_password";}
	
	$cmd="$mysqlcheck -r $db $table -u $q->mysql_admin $pass 2>&1";
	
	if($instance_id>0){
		$q=new mysql_multi($instance_id);
		$cmd="$mysqlcheck -r $db $table -u $q->mysql_admin $pass --socket=\"$q->SocketPath\" 2>&1";
	}
	
	exec($cmd,$results);
	$time_duration=distanceOfTimeInWords($time1,time());

	$q->QUERY_SQL("OPTIMIZE TABLE `$table`",$db);
	if(!$q->ok){
		$OPT="\nOptimize:$q->mysql_error\n";
	}else{
		$OPT="\nOptimize: Success...\n";
	}
	
	$unix->send_email_events("mysqlcheck results on instance $instance_id $db/$table","$time_duration\n".@implode("\n",$results).$OPT,"system");
}


function maintenance($force=false){
	$unix=new unix();
	$time=$unix->file_time_min("/etc/artica-postfix/mysql.optimize.time");
	$time1=time();
	$myisamchk=$unix->find_program("myisamchk");
	$mysqlcheck=$unix->find_program("mysqlcheck"); 
	
	
	$myisamchk=$unix->find_program("myisamchk");
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"$myisamchk\"",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^[0-9]+\s+#", $line)){
			writelogs("$line already executed",@implode("\r\n", $results),__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}	
	
	if(!$force){
	if(!$GLOBALS["VERBOSE"]){
		if($time<1440){
		$unix->events("Maintenance on aborting {$time}Mn wait 1440Mn minimal");
		squid_admin_mysql(2, "Maintenance on aborting {$time}Mn wait 1440Mn minimal",__FUNCTION__,__FILE__,__LINE__,"mysql");
		
		return;
		}
	}
	}
	

	@unlink("/etc/artica-postfix/mysql.optimize.time");
	@file_put_contents("/etc/artica-postfix/mysql.optimize.time","#");
	
	
	if(is_file($mysqlcheck)){
		exec("$mysqlcheck -A -1 2>&1",$mysqlcheck_array);
		$mysqlcheck_logs=$mysqlcheck_logs."\n".@implode("\n",$mysqlcheck_array);
		unset($mysqlcheck_array);
	}
	$q=new mysql();
	$DATAS=$q->DATABASE_LIST();
	if($GLOBALS["VERBOSE"]){echo "Maintenance on ". count($DATAS)." databases starting...\n";}
	while (list ($db, $ligne) = each ($DATAS) ){
		_repair_database($db);
	
	}
	
	

	$t2=time();
	$time_duration=distanceOfTimeInWords($time1,$t2);	
	squid_admin_mysql(2, "Maintenance on ". count($DATAS)." databases done tool:$time_duration\nMysql Check events:$mysqlcheck_logs",__FUNCTION__,__FILE__,__LINE__,"mysql");
}

function _repair_database($database){
	$q=new mysql();
	$sql="SHOW TABLES";
	$results=$q->QUERY_SQL($sql,"squidlogs");	
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$time1=time();
	$myisamchk=$unix->find_program("myisamchk");
	$mysqlcheck=$unix->find_program("mysqlcheck"); 	
	
	
	$myisamchk=$unix->find_program("myisamchk");
	$pgrep=$unix->find_program("pgrep");
	exec("$pgrep -l -f \"$myisamchk\"",$results);
	foreach ($results as $index=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^[0-9]+\s+#", $line)){
			writelogs("$line already executed",@implode("\r\n", $results),__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	}	
	
	$mysqlcheck_logs=null;
	$sql="SHOW TABLES";
	$results=$q->QUERY_SQL($sql,$database);
	if(!$q->ok){
		squid_admin_mysql(2, "Maintenance on database $database failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"mysql-error");	
		return;
	}
	if(mysqli_num_rows($results)==0){
		squid_admin_mysql(2, "Maintenance on database $database aborting, no table stored",__FUNCTION__,__FILE__,__LINE__,"mysql-error");	
		return;
	}
	
	$user=$q->mysql_admin;
	$ty[]=" --user=$user";
	if($q->mysql_password<>null){
		$ty[]="--password=$q->mysql_password";
	}
	
	
	$BLACKS["events_waits_current"]=true;
	$BLACKS["events_waits_history"]=true;
	$BLACKS["events_waits_history_long"]=true;
	$BLACKS["cond_instances"]=true;
	$BLACKS["events_waits_summary_by_instance"]=true;
	$BLACKS["events_waits_summary_by_thread_by_event_name"]=true;
	$BLACKS["events_waits_summary_global_by_event_name"]=true;
	$BLACKS["file_instances"]=true;
	$BLACKS["file_summary_by_event_name"]=true;
	$BLACKS["file_summary_by_instance"]=true;
	$BLACKS["mutex_instances"]=true;
	$BLACKS["performance_timers"]=true;
	$BLACKS["rwlock_instances"]=true;
	$BLACKS["setup_consumers"]=true;
	$BLACKS["setup_instruments"]=true;
	$BLACKS["setup_timers"]=true;
	$BLACKS["threads"]=true;
	$BLACKS["schema"]=true;
	$credentials=@implode(" ", $ty);
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$table=$ligne["Tables_in_$database"];
		
		if(isset($BLACKS[$table])){continue;}
		
		$tt=time();
		if(is_file($mysqlcheck)){
			exec("$mysqlcheck$credentials -r $database $table 2>&1",$mysqlcheck_array);
			$mysqlcheck_logs=$mysqlcheck_logs."\n$mysqlcheck on $table:\n".@implode("\n",$mysqlcheck_array);
			unset($mysqlcheck_array);
		}		
			
		
		echo $table."\n";
		if(is_file($myisamchk)){
			shell_exec("$myisamchk --safe-recover --force $MYSQL_DATA_DIR/$database/$table");
		}else{
			$q->REPAIR_TABLE($database,$table);
		}
		
		$q->QUERY_SQL("OPTIMIZE table $table","$database");
		$time_duration=distanceOfTimeInWords($tt,time());	
		$p[]="$database/$table $time_duration";
		
	}
	$t2=time();
	$time_duration=distanceOfTimeInWords($time1,$t2);	
	squid_admin_mysql(2, "Maintenance on database $database done: {took} $time_duration\nOperations has be proceed on \n".@implode("\n",$p)."\nmysqlchecks results:\n$mysqlcheck_logs",__FUNCTION__,__FILE__,__LINE__,"mysql");
	
}



function fixmysqldbug(){
	if(!is_file("/usr/bin/mysqld_safe")){echo "fixmysqldbug:: /usr/bin/mysqld_safe no such file...\n";return;}
	$f=@explode("\n", @file_get_contents("/usr/bin/mysqld_safe"));
	$replace=false;
	foreach ($f as $index=>$ligne){
		if(strpos($ligne, "/usr//usr/bin//")>0){
			echo "Fix line $index\n";
			$f[$index]=str_replace("/usr//usr/bin//", "/usr/bin/", $ligne);
			$replace=true;
		}
		
		if(strpos($ligne, "/usr//usr/sbin/")>0){
			echo "Fix line $index\n";
			$f[$index]=str_replace("/usr//usr/sbin/", "/usr/sbin/", $ligne);
			$replace=true;
		}		
		
	}
	
	if($replace){
		@file_put_contents("/usr/bin/mysqld_safe", @implode("\n", $f));
	}
	
	
}
function LOCATE_MY_CNF(){
	 if(is_file('/etc/mysql/my.cnf')){return '/etc/mysql/my.cnf';}
  	 if(is_file('/etc/my.cnf')){return '/etc/my.cnf';}
 	return '/etc/mysql/my.cnf';
}

function GetMemmB(){
	$unix=new unix();
	$free=$unix->find_program("free");
	exec("$free -m 2>&1",$results);
	foreach ($results as $index=>$ligne){
		if(preg_match("#^Mem:\s+([0-9]+)\s+#", $ligne,$re)){
			$mem=$re[1];
			continue;
		}
	if(preg_match("#^Swap:\s+([0-9]+)\s+#", $ligne,$re)){
			$Swap=$re[1];
			break;
		}
		
	}
	return array($mem,$Swap);
}

function mysqltuner(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/". basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=trim(@file_get_contents($pidfile));
	if(is_numeric($pid)){
		if($unix->process_exists($pid,basename(__FILE__))){
			if($GLOBALS["VERBOSE"]){echo "Already running PID $pid\n";}
		}
	}	
	
	if(system_is_overloaded(basename(__FILE__))){
		squid_admin_mysql(2, "{OVERLOADED_SYSTEM}, aborting", __FUNCTION__, __FILE__, __LINE__, "mysql");
		return;
	}
	
	@file_put_contents($pidfile, getmypid());
	if($GLOBALS["VERBOSE"]){echo "Running for Instance 0\n";}
	$targetfile="/usr/share/artica-postfix/ressources/mysqltuner/instance-0.db";
	@mkdir("/usr/share/artica-postfix/ressources/mysqltuner",0755);
	
	$mem=GetMemmB();
	$Memory=$mem[0];
	$Swap=$mem[1];
	squid_admin_mysql(2, "Memory: {$Memory}M Swap: {$Swap}M",__FUNCTION__,__FILE__,__LINE__,"mysql");
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($targetfile);
		if($GLOBALS["VERBOSE"]){echo "$targetfile Time:{$time}Mn need 119\n";}
		if($time>119){@unlink($targetfile);}
	}else{
		@unlink($targetfile);
	}
	
	if(!is_file($targetfile)){
		$q=new mysql();
		$t=time();
		$resultsCMDLINES=array();
		$mysql_admin=$q->mysql_admin;
		$password=$q->mysql_password;
		if($mysql_admin==null){$mysql_admin="root";}
		if($password<>null){$password=" --pass \"$password\"";}
		$cmdline="/usr/share/artica-postfix/bin/mysqltuner.pl";
		$socket=" --socket \"/var/run/mysqld/mysqld.sock\"";
		$cmdline=$cmdline." --nocolor --user=$mysql_admin$password --forcemem $Memory ";
		$cmdline=$cmdline."--forceswap $Swap $socket 2>&1";
		if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
		$resultsCMDLINES[]=" >>  Generated on ". date("Y-m-d H:i:s")." %%REBUILD";
		exec($cmdline,$resultsCMDLINES);
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		squid_admin_mysql(2, "Generating report for instance number `0` tool: $took...\n".@implode("\n", $resultsCMDLINES),__FUNCTION__,__FILE__,__LINE__,"mysql");
		@file_put_contents($targetfile, @implode("\n", $resultsCMDLINES));
		@chmod($targetfile, 0755);
	}else{
		@unlink($targetfile);
	}
	
	$sql="SELECT ID FROM mysqlmulti WHERE enabled=1";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if($GLOBALS["VERBOSE"]){echo "Generating report for instance number `{$ligne["ID"]}`...\n";}
		$id=$ligne["ID"];
		$targetfile="/usr/share/artica-postfix/ressources/mysqltuner/instance-$id.db";
		
		
		if(!$GLOBALS["FORCE"]){
			
			$time=$unix->file_time_min($targetfile);
			if($GLOBALS["VERBOSE"]){echo "$targetfile Time:{$time}Mn need 119\n";}
			if($time>119){@unlink($targetfile);}
		}else{
			@unlink($targetfile);
		}
		
		
		if(!is_file($targetfile)){
			$resultsCMDLINES=array();
			$t=time();
			$q=new mysql_multi($id);
			$mysql_admin=$q->mysql_admin;
			$password=$q->mysql_password;
			if($mysql_admin==null){$mysql_admin="root";}
			if($password<>null){$password=" --pass \"$password\"";}
			$cmdline=null;
			$cmdline="/usr/share/artica-postfix/bin/mysqltuner.pl";
			$cmdline=$cmdline." --nocolor --user=$mysql_admin$password --forcemem $Memory ";
			$cmdline=$cmdline."--forceswap $Swap --socket $q->SocketPath 2>&1";
			if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
			$resultsCMDLINES[]=" >>  Generated on ". date("Y-m-d H:i:s")." %%REBUILD";
			exec($cmdline,$resultsCMDLINES);	
			squid_admin_mysql(2, "Generating report for instance number `$id` tool: $took...\n".@implode("\n", $resultsCMDLINES),__FUNCTION__,__FILE__,__LINE__,"mysql");
			@file_put_contents($targetfile, @implode("\n", $resultsCMDLINES));
			@chmod($targetfile, 0755);
		}else{
			if($GLOBALS["VERBOSE"]){echo "$targetfile exists... skip it\n";}
		}		
		
		
	}

CleanBadFiles();



}

function CleanBadFiles(){
	foreach (glob("/usr/share/artica-postfix/*") as $filename) {
		$filebase=basename($filename);
		if(is_numeric($filebase)){@unlink($filename);}
		
	}
}

function mysql_tmpfs(){
	$sock=new sockets();
	$unix=new unix();
	$MySQLTMPDIR=trim($sock->GET_INFO("MySQLTMPDIR"));
	if($MySQLTMPDIR=="/tmp"){$MySQLTMPDIR=null;}
	$MySQLTMPMEMSIZE=trim($sock->GET_INFO("MySQLTMPMEMSIZE"));
	if($MySQLTMPDIR==null){echo "Starting......: ".date("H:i:s")." MySQL tmpdir not set...\n";return;}
	if(!is_numeric($MySQLTMPMEMSIZE)){$MySQLTMPMEMSIZE=0;}
	if($MySQLTMPMEMSIZE<1){echo "Starting......: ".date("H:i:s")." MySQL tmpfs not set...\n";return;}
	
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");
	
	if(strlen($idbin)<3){echo "Starting......: ".date("H:i:s")." MySQL tmpfs `id` no such binary\n";return;}
	if(strlen($mount)<3){echo "Starting......: ".date("H:i:s")." MySQL tmpfs `mount` no such binary\n";return;}
	exec("$idbin mysql 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")."MySQL mysql no such user...\n";return;}
	$uid=$re[1];
	$gid=$re[2];
	echo "Starting......: ".date("H:i:s")." MySQL tmpfs uid/gid =$uid:$gid\n";
	mysql_tmpfs_umount($uid);
	if(is_dir($MySQLTMPDIR)){shell_exec("$rm -rf $MySQLTMPDIR/* >/dev/null 2>&1");}
	@mkdir($MySQLTMPDIR,0755,true);
	$cmd="$mount -t tmpfs -o rw,uid=$uid,gid=$gid,size={$MySQLTMPMEMSIZE}M,nr_inodes=10k,mode=0700 tmpfs \"$MySQLTMPDIR\"";
	shell_exec($cmd);
	$mounted=mysql_tmpfs_ismounted($uid);
	if(strlen($mounted)>3){
		echo "Starting......: ".date("H:i:s")." MySQL $MySQLTMPDIR(tmpfs) for {$MySQLTMPMEMSIZE}M success\n";	
		
	}else{
		echo "Starting......: ".date("H:i:s")." MySQL tmpfs for {$MySQLTMPMEMSIZE}M failed, it will return back to disk\n";
	}
}
function mysql_tmpfs_umount($uid){
	
	$unix=new unix();
	$idbin=$unix->find_program("id");
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");	
	exec("$idbin mysql 2>&1",$results);
	if(!preg_match("#uid=([0-9]+).*?gid=([0-9]+)#", @implode("", $results),$re)){echo "Starting......: ".date("H:i:s")."MySQL mysql no such user...\n";return;}	
	$uid=$re[1];
	$gid=$re[2];
	
	if(!is_numeric($uid)){
		echo "Starting......: ".date("H:i:s")." MySQL tmpfs uid is not a numeric, aborting umounting task\n";
		return;
	}	
	
	$mounted=mysql_tmpfs_ismounted($uid);
	$c=0;
	while (strlen($mounted)>3) {
		if(strlen($mounted)>3){
		echo "Starting......: ".date("H:i:s")."MySQL umount($uid) $mounted\n";
		shell_exec("$umount -l \"$mounted\"");
		$c++;
		}
		if($c>20){break;}
		$mounted=mysql_tmpfs_ismounted($uid);		
	}	

}



function mysql_tmpfs_ismounted($uid){
	
	$f=file("/proc/mounts");
	foreach ($f as $index=>$ligne){
		if(!preg_match("#tmpfs\s+(.+?)\s+tmpfs.*?uid=$uid#", $ligne,$re)){continue;}
		return trim($re[1]);
	}
}
	


?>