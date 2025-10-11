<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
if($DisablePostGres==1){die();}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.manager.inc');


if(isset($argv[1])) {
    if ($argv[1] == "--clean") {
        CleanFailed();
        exit;
    }
    if ($argv[1] == "--count-files") {
        GetCountFiles();
        exit;
    }
    if ($argv[1] == "--pg-size") {
        tables_size();
        exit;
    }
}
// exec.PostgreSQL-failed.php --pg-size
parse();


function GetCountFiles(){
    if(system_is_overloaded(basename(__FILE__))){return false;}
	$pidfile="/etc/artica-postfix/pids/exec.PostgreSQL-failed.php.GetCountFiles.pid";
	$pidTime="/etc/artica-postfix/pids/exec.PostgreSQL-failed.php.GetCountFiles.time";
	$NotifTime="/etc/artica-postfix/pids/exec.PostgreSQL-failed.php.GetCountFiles.notif.time";
	$unix=new unix();
	$ExeTime=$unix->file_time_min($pidTime);
	$ExecNotif=$unix->file_time_min($NotifTime);
	if($ExeTime<29){return false;}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	$filesnumber=$unix->COUNT_FILES("/home/squid/PostgreSQL-failed");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PostgreSQLFailedCount", $filesnumber);
	
	if($filesnumber>10000 and $filesnumber<100000){
		if($ExecNotif>30){
			@unlink($NotifTime);
			@file_put_contents($NotifTime, time());
			squid_admin_mysql(0, "Fatal PostgreSQL Failed reach 10,000 entries ($filesnumber) remove all after 100,000", "Fatal PostgreSQL Failed reach 10,000 entries ($filesnumber) remove all after 100,000",__FILE__,__LINE__);
		}
	}
	
	if($filesnumber>100000){
		squid_admin_mysql(0, "Fatal PostgreSQL Failed reach 10,000 entries ($filesnumber) remove all after 100,000", "Fatal PostgreSQL Failed reach 100,000 entries ($filesnumber) remove all files!",__FILE__,__LINE__);
		CleanFailed();
	}
	

}


function CleanFailed(){
	
	$unix=new unix();
	$filesnumber=$unix->COUNT_FILES("/home/squid/PostgreSQL-failed");
	
	
	build_progress(10, "$filesnumber {files}");
	if($filesnumber==0){
		build_progress(100, "{success}");
		exit();
	}
	$c=0;
	if (!$handle = opendir("/home/squid/PostgreSQL-failed")) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/home/squid/PostgreSQL-failed/$filename";
		$c++;
		$prc=$c/$filesnumber;
		$prc=$prc*100;
		$prc=round($prc);
		if($prc>10){
			if($prc<95){
				build_progress($prc, "{remove} $c files/$filesnumber");
			}
		}
		echo "Remove $targetFile\n";
		@unlink($targetFile);
	}

	exit();
	
	
}
function tables_size():bool{
    $unix=new unix();

    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.convert-to-sqlite.php");

    if(!$GLOBALS["FORCE"]) {
        $tmpfile = "/etc/artica-postfix/cron.1/" . basename(__FILE__) . "." . __FUNCTION__ . ".time";
        if (is_file($tmpfile)) {
            if ($unix->file_time_min($tmpfile) < 30) {
                return false;
            }
        }
    }

    @unlink($tmpfile);
    @file_put_contents($tmpfile,time());

	$q=new postgres_sql();
    if(!is_dir("/home/artica/SQLITE_TEMP")){@mkdir("/home/artica/SQLITE_TEMP",0755,true);}
	@chown("/home/artica/SQLITE_TEMP", "www-data");
	@chmod("/home/artica/SQLITE_TEMP", 0755);



	$table="(SELECT *, pg_size_pretty(total_bytes) AS total
    , pg_size_pretty(index_bytes) AS INDEX
    , pg_size_pretty(toast_bytes) AS toast
    , pg_size_pretty(table_bytes) AS TABLE
  FROM (
  SELECT *, total_bytes-index_bytes-COALESCE(toast_bytes,0) AS table_bytes FROM (
      SELECT c.oid,nspname AS table_schema, relname AS TABLE_NAME
              , c.reltuples AS row_estimate
              , pg_total_relation_size(c.oid) AS total_bytes
              , pg_indexes_size(c.oid) AS index_bytes
              , pg_total_relation_size(reltoastrelid) AS toast_bytes
          FROM pg_class c
          LEFT JOIN pg_namespace n ON n.oid = c.relnamespace
          WHERE relkind = 'r'
  ) a
) a) b";


	$sql="SELECT * FROM $table ORDER BY total_bytes DESC";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){

        if(preg_match("#could not open shared memory segment#",$q->mysql_error)){
            $results[]="This means your server did not have enough memory for defined settings ( less than 4GB)";
            $results[]="Please, read this article to change parameters of the PostgreSQL service..";
            $results[]="https://wiki.articatech.com/system/postgresql/could-not-open-shared-memory-segment";
            squid_admin_mysql(0,"PostgreSQL: {memory_warning} {action}=WIKI!",$q->mysql_error."\n".@implode("\n",$results),__FILE__,__LINE__);
            return false;
        }

        if(preg_match("#Could not Connect to database#",$q->mysql_error)){
            exec("/etc/init.d/artica-postgres restart 2>&1",$results);
            squid_admin_mysql(0,"PostgreSQL: {failed_to_connect} {action}={restart}",$q->mysql_error."\n".@implode("\n",$results),__FILE__,__LINE__);
            return false;
        }



        echo "<$q->mysql_error>\n";
		squid_admin_mysql(1,"PostgreSQL Failed while calculating table size..." , $q->mysql_error."\n".$sql,__FILE__,__LINE__);
		return false;
	}

	$qLite=new lib_sqlite("/home/artica/SQLITE_TEMP/pg_tables.db");
	@chmod("/home/artica/SQLITE_TEMP/pg_tables.db", 0644);
	@chown("/home/artica/SQLITE_TEMP/pg_tables.db", "www-data");

	$sql="CREATE TABLE IF NOT EXISTS `ztables` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`tablename` TEXT,
		`zrows` integer,
		`zdate` INTEGER,
		`zbytes` INTEGER )";


	$qLite->QUERY_SQL($sql);
	$qLite->QUERY_SQL("DELETE FROM ztables");



	$f=array();
	while($ligne=@pg_fetch_assoc($results)){
		if(preg_match("#^(pg|sql)_#", $ligne["table_name"])){continue;}
		$tablename=$ligne["table_name"];
		if(isset($GLOBALS["ALREADY"][$tablename])){continue;}
		$total_bytes=intval($ligne["total_bytes"]);
		$ligne2=$q->mysqli_fetch_array("SELECT COUNT(*) as tcount from $tablename");
		$rows=intval($ligne2["tcount"]);
		$time=time();
        $qLite->QUERY_SQL("INSERT INTO ztables (tablename,zdate,zrows,zbytes) VALUES ('$tablename',$time,$rows,$total_bytes)");
        $GLOBALS["ALREADY"][$tablename]=true;

	}

    return true;

}



function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/postgres.cleanfailed.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function parse(){
	
	if(!is_dir("/home/squid/PostgreSQL-failed")){exit();}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=$unix->get_pid_from_file($pidfile);
	
	if($unix->process_exists($pid)){exit();}
	$timexec=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){
		if($timexec<5){exit();}
	}
	
	@unlink($pidfile);
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	@file_put_contents($pidfile, getmypid());
	
	$q=new postgres_sql();
	$failed=0;
	$c=0;
	if (!$handle = opendir("/home/squid/PostgreSQL-failed")) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="/home/squid/PostgreSQL-failed/$filename";
		
		$TimeFile=$unix->file_time_min($targetFile);
		$prefix="$filename ({$TimeFile}mn)";
		
		if($TimeFile>400){
			if($GLOBALS["VERBOSE"]){echo "$prefix Timed-out, remove\n";}
			@unlink($targetFile);
			continue;
		}
		
		$sql=@file_get_contents($targetFile);
		if(preg_match("#^.*?CREATE TABLE(.+?)#", $sql,$re)){$sql="CREATE TABLE {$re[1]}";}
		$q->QUERY_SQL($sql);
		
		if(!$q->ok){
			if(preg_match("#relation \"access_([0-9]+)\"#", $q->mysql_error,$re)){
				if($GLOBALS["VERBOSE"]){echo "$prefix Create table access_{$re[1]}\n";}
				$q->QUERY_SQL("CREATE TABLE IF NOT EXISTS \"access_{$re[1]}\" (zdate timestamp,mac macaddr,ipaddr INET,proxyname VARCHAR(128) NOT NULL,category VARCHAR(64) NULL,sitename VARCHAR(255) NULL,FAMILYSITE VARCHAR(128) NULL,USERID VARCHAR(64) NULL,SIZE BIGINT,RQS BIGINT)");
				$q->QUERY_SQL($sql);
			}
			if(preg_match("#duplicate key value violates unique constraint#",$q->mysql_error)){
				if($GLOBALS["VERBOSE"]){echo "$prefix DONE\n";}
				@unlink($targetFile);
				continue;
			}
			
			
		}
		
		if($failed>50){
			if($GLOBALS["VERBOSE"]){echo "Too much errors, aborting...\n";}
			break;
		}
		
		if($c>500){
			if($GLOBALS["VERBOSE"]){echo "Too much files, aborting...\n";}
			break;
		}
		
		if(!$q->ok){
			if($GLOBALS["VERBOSE"]){echo "$prefix ERROR $q->mysql_error\n";}
			$failed++;
			$errors[]=$targetFile." {$TimeFile}Mn TTL";
			$errors[]=$q->mysql_error;
			$errors[]=$sql;
			$errors[]="---------------------------------------------------------------";
			if($TimeFile>240){@unlink($targetFile);}
			continue;
		}
		if($GLOBALS["VERBOSE"]){echo "$prefix DONE\n";}
		@unlink($targetFile);
		$c++;
	}

	
	
}

