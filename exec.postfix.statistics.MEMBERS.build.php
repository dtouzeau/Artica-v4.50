<?php

ini_set('memory_limit','1000M');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="vsFTPD Daemon";
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
include_once(dirname(__FILE__)."/ressources/class.influx.inc");


if($argv[1]=="--email"){BUILD_REPORT($argv[2]);exit;}


function build_progress($text,$pourc){
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/smtp.stats.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}





function BUILD_REPORT($email){
	build_progress("{building_query}",5);
	$unix=new unix();
	$q=new postgres_sql();
	$table_name=str_replace("@","",strtolower($email));
    $table_name=str_replace(".","",$table_name);
    $table_name=str_replace("-","",$table_name);

    if($q->TABLE_EXISTS($table_name)){
        echo "Removing table $table_name\n";
        $q->QUERY_SQL("DROP TABLE $table_name");
    }
    $sql="CREATE TABLE IF NOT EXISTS \"$table_name\" (zdate timestamp,frommail VARCHAR(256),refused int,sent int,hits bigint,reason varchar(128))";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        build_progress("PostGreSQL {failed}",110);
        return;
    }
    echo "Creating index in table $table_name\n";
    $q->QUERY_SQL("create index zdate on \"$table_name\"(zdate);");
    $q->QUERY_SQL("create index mailfrom on \"$table_name\"(frommail);");
    $q->QUERY_SQL("create index qr on \"$table_name\"(refused,sent);");

    $query="SELECT date_trunc('hour', zdate) as zdate,COUNT(*) as hits,frommail,refused,sent,reason FROM smtplog WHERE tomail='$email' AND (sent=1 OR refused=1) GROUP BY date_trunc('hour', zdate),frommail,refused,sent,reason";

    build_progress("{building_query}",50);
    echo "Launch query\n";
    $sql="INSERT INTO $table_name (zdate,hits,frommail,refused,sent,reason) ($query)";
    echo "\n$sql\n";
	$q->QUERY_SQL($sql);
	
	if(!$q->ok){
	echo $q->mysql_error."\n$sql\n";
			build_progress("PostGreSQL {failed}",110);
			return;
	}
	$rows=$q->COUNT_ROWS_LOW($table_name);
	if($rows==0){
        build_progress("{building_query} {failed} no rows",100);
        $q->QUERY_SQL("DROP TABLE $table_name");
        return;
    }
    build_progress("{building_query}...",90);
	$php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.PostgreSQL-failed.php --pg-size");

    build_progress("{$table_name} {success} $rows {rows}",100);
	
}




