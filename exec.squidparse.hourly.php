#!/usr/bin/php -q
<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
ini_set('memory_limit','1000M');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
$GLOBALS["LogFileDeamonLogDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}

if(is_file("/usr/local/ArticaStats/bin/postgres")){
	$GLOBALS["LogFileDeamonLogDir"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogFileDeamonLogPostGresDir");
	if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
}


if(preg_match("#--verbose#",implode(" ",$argv))){
		echo "VERBOSED....\n";
		$GLOBALS["VERBOSE"]=true;$GLOBALS["TRACE_INFLUX"]=true;
		$GLOBALS["OUTPUT"]=true;
		$GLOBALS["debug"]=true;
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

if($argv[1]=="--failed"){failedZ();exit;}
if($argv[1]=="--rotate"){rotate();exit;}
if($argv[1]=="--file"){ACCESS_LOG_HOURLY_BACKUP($argv[2]);}
if($argv[1]=="--ufdb"){UFDB_LOG_NEW_HOURLY();exit();}


scan();
function scan(){}
function failedZ(){}
function events($text=null){}
function ACCESS_LOG_HOURLY_FAILED(){}
function FAILED_INJECT($faildir,$backupdir){}
function MAIN_SIZE($workfile){}
function ROTATE(){}
function ROTATE_DIR($backupdir){}
function CACHED($workfile){}
function ACCESS_LOG_HOURLY_BACKUP($sourcefile=null){}
function TimeToInflux($time,$Nomilliseconds=false){}
function MSSQL_DUMP_FAMSITE($MSSQLFAM){}
function MSSQL_DUMP_FAMSITE_USER($MSSQLFAM){}
function MSSQL_DUMP_USERCOUNT($COUNTOFUSERS){}
function MSSQL_DUMP_USER($MEM){}
function MSSQL_DUMP($MEM){}
function REQUESTS_DUMP($MEM){}
function WEBSITES_DUMP($MEM){}
function ACCESS_LOG_HOURLY_DUMP($MEM){}
function UFDB_LOG_NEW_HOURLY_FAILED(){}
function UFDB_LOG_NEW_HOURLY(){}
function UFDB_LOG_HOURLY_DUMP($MEM){}
function UFDB_LOG_HOURLY_MYSQL_DUMP($MEM){}
function MINTOTEN($MIN){}
?>