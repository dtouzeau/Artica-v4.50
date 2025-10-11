<?php
$GLOBALS["FORCE"]=false;
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
$GLOBALS["TITLENAME"]="GreenSQL";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["OUTPUT"]=true;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.greensql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

if(is_array($argv)){if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}}

if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="-V"){$GLOBALS["VERBOSE"]=true;echo Greensqlversion()."\n";exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--mysql"){MySqlUsernameAndPassword();exit();}





function build_progress($text,$pourc){
	$echotext=$text;
	if(!$GLOBALS["PROGRESS"]){return;}
	$cachefile=PROGRESS_DIR."/greensql.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function restart() {
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		build_progress("{failed}",110);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	build_progress("{stopping_service}",20);
	stop(true);
	build_progress("{configuring}",50);

	build();
	sleep(1);
	build_progress("{starting_service}",55);
	if(!start(true)){
		build_progress("{starting_service} {failed}",90);
		return;
	}

	build_progress("{restarting} {success}",100);


}

function MySqlUsernameAndPassword(){
	
	
	$q=new mysql();
	$q->PRIVILEGES("greensql","greensql","greensql");
	if(!$q->ok){echo "$q->mysql_error\n";}
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/usr/sbin/greensql-fw";

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/greensql-fw.pid", $pid);
		build_progress_install(79, "{APP_GREENSQL} {started}");
		build_progress("{starting_service}",90);
		return true;
	}
	$EnableGreenSQL=$sock->GET_INFO("EnableGreenSQL");
	
	if($EnableGreenSQL==0){
		build_progress_install(79, "{APP_GREENSQL} {disabled}");
		build_progress("Service disabled",110);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableKerbAuth,EnableCNTLM)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");


	$cmd="$nohup /usr/sbin/greensql-fw -p /etc/greensql >/dev/null 2>&1 &";
	build_progress_install(60, "{APP_GREENSQL} {starting_service}");
	build_progress("{starting_service}",60);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	shell_exec($cmd);

	for($i=1;$i<6;$i++){
		build_progress_install(60+$i, "{APP_GREENSQL} {starting_service}");
		build_progress("{starting_service}",60+$i);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}
	
	build_progress_install(70, "{APP_GREENSQL} {starting_service}");
	build_progress("{starting_service}",70);
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		build_progress_install(100, "{APP_GREENSQL} {starting_service} {success}");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		@file_put_contents("/var/run/greensql-fw.pid", $pid);
		build_progress("{starting_service} {success}",100);
		return true;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		build_progress("{starting_service} {failed}",110);
		return false;
	}

}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$chmod=$unix->find_program("chmod");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function build(){
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} checking Database....\n";
	$q=new mysql();
	if(!$q->DATABASE_EXISTS("greensql")){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating database greensql\n";$q->CREATE_DATABASE("greensql");}
	checkGreenTables();
	buildconfig();
	
}
function build_pgsql_conf(){
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	
	$sql="CREATE TABLE IF NOT EXISTS `pgsql_rules` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				   zcommand text not null,
				   pattern text not null,
				   explain text not null,
				   enabled int not null default 1)  ";
	$q->QUERY_SQL($sql);
	
	$CountRows=	$q->COUNT_ROWS("pgsql_rules");
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} PostGreSQL $CountRows rules\n";
	if($CountRows==0){fill_pgsql_rules();}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='alter'");
	$f[]="[alter]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='create'");
	$f[]="[create]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='drop'");
	$f[]="[drop]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='info'");
	$f[]="[info]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='true constants'");
	$f[]="[true constants]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='true constants'");
	$f[]="[true constants]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='bruteforce functions'");
	$f[]="[bruteforce functions]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM pgsql_rules WHERE `enabled`=1 AND `zcommand`='sensitive tables'");
	$f[]="[sensitive tables]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	@file_put_contents("/etc/greensql/pgsql.conf", @implode("\n", $f));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/greensql/pgsql.conf [OK]\n";
	
}

function build_mysql_conf(){
	
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	
	$sql="CREATE TABLE IF NOT EXISTS `mysql_rules` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				   zcommand text not null,
				   pattern text not null,
				   explain text not null,
				   enabled int not null default 1)  ";
		$q->QUERY_SQL($sql);
	
		
	$CountRows=	$q->COUNT_ROWS("mysql_rules");
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} MySQL $CountRows rules\n";
	if($CountRows==0){fill_mysql_rules(); }
	
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='alter'");
	$f[]="[alter]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='create'");
	$f[]="[create]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}	
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='drop'");
	$f[]="[drop]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}	
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='info'");
	$f[]="[info]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='true constants'");
	$f[]="[true constants]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='true constants'");
	$f[]="[true constants]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='bruteforce functions'");
	$f[]="[bruteforce functions]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	$results=$q->QUERY_SQL("SELECT * FROM mysql_rules WHERE `enabled`=1 AND `zcommand`='sensitive tables'");
	$f[]="[sensitive tables]";
	foreach ($results as $index=>$ligne) {$f[]=$ligne["pattern"]." #{$ligne["explain"]}";}
	
	@file_put_contents("/etc/greensql/mysql.conf", @implode("\n", $f));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/greensql/mysql.conf [OK]\n";
	
}

function fill_pgsql_rules(){
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('alter','alter table','ALTER section lists commands used to change table structure');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('alter','alter database','ALTER section lists commands used to change table structure');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('alter','alter group','ALTER section lists commands used to change table structure');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('alter','alter trigger','ALTER section lists commands used to change table structure');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('alter','alter user','ALTER section lists commands used to change table structure');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create aggregate','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create cast','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create constraint trigger','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create conversion','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create database','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create domain','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create function','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create group','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create index','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create language','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create operator','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create rule','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create schema','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create sequence','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create table','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create trigger','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create type','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create user','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('create','create view','CREATE section lists commands used to create tables/indices/etc..');";
	
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop aggregate','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop cast','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop conversion','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop database','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop domain','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop function','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop group','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop index','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop lanquage','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop operator','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop rule','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop schema','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop sequence','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop table','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop trigger','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop type','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop user','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','drop view','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('drop','truncate','DROP section lists commands used to drop tables');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.table_constraints','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.constraint_column_usage','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.triggers','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.routines','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.views','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.columns','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.key_column_usage','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.schemata','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.tables','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.columns','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.statistics','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.user_privileges','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.schema_privileges','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.table_privileges','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.column_privileges','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.character_sets','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.collations','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.collations_character_set_applicability','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','information_schema.profiling','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_database','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_class','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_attribute','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_statistic','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_settings','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_description','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_proc','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_shadow','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_stats','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_tables','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_user','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_views','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','pg_roles','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('info','^show','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^set','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^set constraints','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^set session authorization','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^set transaction','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^grant','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^revoke','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^reset','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','create user','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','drop user','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','create database','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','drop database','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','create schema','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','drop schema','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^load','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('block','^lock','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('true constants','current_user','list of internal mysql constants tha can always return true value. it is used to detect SQL tautologies.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('true constants','current_date','list of internal mysql constants tha can always return true value. it is used to detect SQL tautologies.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('true constants','current_time','list of internal mysql constants tha can always return true value. it is used to detect SQL tautologies.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('true constants','current_timestamp','list of internal mysql constants tha can always return true value. it is used to detect SQL tautologies.');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','substring','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','trim','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','bit_length','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','char_length','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','character_l','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','overlay','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','octet_length','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','position','list of helpful functions during database contents bruteforce');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','customer','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','member','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','order','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','admin','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','user','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','permission','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into pgsql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','session','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	
	foreach ($f as $sql){
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
}

function fill_mysql_rules(){
	$q=new lib_sqlite("/home/artica/SQLITE/greensql.db");
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('alter','alter view','ALTER section lists commands used to change table structure');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('alter','alter table','ALTER section lists commands used to change table structure');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('alter','rename table','ALTER section lists commands used to change table structure');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('create','create table','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('create','create index','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('create','create database','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('create','create procedure','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('create','create view','CREATE section lists commands used to create tables/indices/etc..');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('drop','drop table','DROP section lists commands used to drop tables');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('drop','drop index','DROP section lists commands used to drop tables');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('drop','drop database','DROP section lists commands used to drop tables');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('drop','drop view','DROP section lists commands used to drop tables');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('drop','truncate','DROP section lists commands used to drop tables');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','^desc','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','^status','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','describe','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show databases','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show schemas','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show create table','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show create database','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show create procedure','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show columns','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show fields','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show processlist','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show procedure','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show table','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show grants','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show index','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show keys','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show engine','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show function','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show procedure','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show privileges','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show open','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show bdb','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show innodb','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show logs','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show triggers','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show global','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show session','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show variables','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show warnings','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show count','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show status','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show profiles','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','show full','INFO section lists commands used to retrive information about database structure and other sensitive information.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','information_schema','mysql5.0 internal db used to store tables info');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','current_user','select current_user() will print db user');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','user\s*\(\s*\)','current system user');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','database\s*\(\s*\)','current database');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','version\s*\(\s*\)','mysql version number');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','mysql.user','mysql table of users');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('info','mysql.db','mysql table of databases');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','^set password','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','^grant','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','^kill','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','^handler','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','^revoke','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','flush privileges','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','create user','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','drop user','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','rename user','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','backup table','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','restore table','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','load file','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','load data','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','into outfile','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','into dumpfile','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','benchmark\s*\(','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('block','load_file','BLOCK section lists general SQL commands that will be blocked.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('true constants','current_user','list of internal mysql constants tha can always return true value. it is used to detect SQL tautologies.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('true constants','current_date','list of internal mysql constants tha can always return true value. it is used to detect SQL tautologies.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('true constants','version','list of internal mysql constants tha can always return true value. it is used to detect SQL tautologies.');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','mid','list of helpful functions during database contents bruteforce');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','substring','list of helpful functions during database contents bruteforce');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('bruteforce functions','substr','list of helpful functions during database contents bruteforce');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','customer','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','member','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','order','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','admin','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','user','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','permission','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	$f[]="insert into mysql_rules (zcommand,pattern,explain) VALUES ('sensitive tables','session','SENSITIVE TABLES section lists tables used by the SQL risk engine.Access to these tables will raise the risk of SQL injection.Make this list as short as possible');";
	
	
	foreach ($f as $sql){
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	
}



function build_progress_install($text,$pourc){
	$filename=PROGRESS_DIR."/greensql.install.progress";
	if(!$GLOBALS["PROGRESS"]){return;}
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
		@file_put_contents($filename, serialize($array));
		@chmod($filename,0777);
		return;
	}
	
	
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}

function uninstall(){
	$sock=new sockets();
	$sock->SET_INFO("EnableGreenSQL", 0);
	$unix=new unix();	
	build_progress_install(10, "{APP_GREENSQL} {uninstalling}");
	remove_service("/etc/init.d/greensql-fw");
	if(is_file("/etc/monit/conf.d/APP_GREENSQL.monitrc")){@unlink("/etc/monit/conf.d/APP_GREENSQL.monitrc");shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");}
	build_progress_install(100, "{APP_GREENSQL} {uninstalling} {done}");
}


function install(){
	
	$sock=new sockets();
	$sock->SET_INFO("EnableGreenSQL", 1);
	$unix=new unix();
	
	
	build_progress_install(10, "{APP_GREENSQL} {configuring}");
	buildconfig();
	
	build_progress_install(20, "{APP_GREENSQL} {configuring}");
	MySqlUsernameAndPassword();
	if(!checkGreenTables()){
		build_progress_install(110, "{APP_GREENSQL} {mysql_error} {failed}");
		$sock->SET_INFO("EnableGreenSQL", 0);
		return false;
	}
	build_progress_install(30, "{APP_GREENSQL} {creating_service}");
	create_service();
	build_progress_install(80, "{APP_GREENSQL} {APP_MONIT}");
	build_monit();
	build_progress_install(100, "{APP_GREENSQL} {success}");
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/greensql-fw.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/usr/sbin/greensql-fw");
	
}


function build_monit(){
	$f[]="check process APP_GREENSQL with pidfile /var/run/greensql-fw.pid";
	$f[]="\tstart program = \"/etc/init.d/greensql-fw start\"";
	$f[]="\tstop program = \"/etc/init.d/greensql-fw stop\"";

	
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_GREENSQL.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_GREENSQL.monitrc")){
		echo "/etc/monit/conf.d/APP_GREENSQL.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}


function create_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/greensql-fw";
	$php5script=__FILE__;
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         greensql-fw";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: GreenSQL-fw";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: GreenSQL-fw";
	$f[]="### END INIT INFO";
	$f[]="";
		$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    $php ".__FILE__." --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php ".__FILE__." --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php ".__FILE__." --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php ".__FILE__." --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	$f[]="";
	
	
	echo "GreenSQL: [INFO] Writing $INITD_PATH with new config\n";
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




function checkGreenTables(){
	
	$q=new mysql();
	if(!$q->TABLE_EXISTS("query", "greensql")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating query table\n";
		$sql="CREATE table query(
		queryid int unsigned NOT NULL auto_increment primary key,
		proxyid        int unsigned NOT NULL default '0',
		perm           smallint unsigned NOT NULL default 1,
		db_name        char(50) NOT NULL,
		query          text NOT NULL,
		INDEX(proxyid,db_name)
		) DEFAULT CHARSET=utf8;
		";
		$q->QUERY_SQL($sql,"greensql");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed $q->mysql_error\n";return false;}
		
	}


	if(!$q->TABLE_EXISTS("proxy", "greensql")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating proxy table\n";
		$sql="
			CREATE table proxy
			(
			proxyid        int unsigned NOT NULL auto_increment primary key,
			proxyname      char(50) NOT NULL default '',
			frontend_ip    char(20) NOT NULL default '',
			frontend_port  smallint unsigned NOT NULL default 0,
			backend_server char(50) NOT NULL default '',
			backend_ip     char(20) NOT NULL default '',
			backend_port   smallint unsigned NOT NULL default 0,
			dbtype         char(20) NOT NULL default 'mysql',
			status         smallint unsigned NOT NULL default '1'
			) DEFAULT CHARSET=utf8;";
		$q->QUERY_SQL($sql,"greensql");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed $q->mysql_error\n";return false;}
		//$q->QUERY_SQL("insert into proxy values (1,'Default MySQL Proxy','127.0.0.1',3305,'localhost','127.0.0.1',3306,'mysql',1);","greensql");
		//$q->QUERY_SQL("insert into proxy values (2,'Default PgSQL Proxy','127.0.0.1',5431,'localhost','127.0.0.1',5432,'pgsql',1);","greensql");
	}


	if(!$q->TABLE_EXISTS("db_perm", "greensql")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating db_perm table\n";
		$sql="CREATE table db_perm (
			dbpid          int unsigned NOT NULL auto_increment primary key,
			proxyid        int unsigned NOT NULL default '0',
			db_name        char(50) NOT NULL,
			perms          bigint unsigned NOT NULL default '0',
			perms2         bigint unsigned NOT NULL default '0',
			status         smallint unsigned NOT NULL default '0',
			sysdbtype      char(20) NOT NULL default 'user_db',
			status_changed datetime NOT NULL default '00-00-0000 00:00:00',
			INDEX (proxyid, db_name)
			) DEFAULT CHARSET=utf8;";
		
		$q->QUERY_SQL($sql,"greensql");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed $q->mysql_error\n";return false;}
		$q->QUERY_SQL("insert into db_perm (dbpid, proxyid, db_name, sysdbtype) values (1,0,'default mysql db', 'default_mysql');","greensql");
		$q->QUERY_SQL("insert into db_perm (dbpid, proxyid, db_name, sysdbtype) values (2,0,'no-name mysql db', 'empty_mysql');","greensql");
		$q->QUERY_SQL("insert into db_perm (dbpid, proxyid, db_name, sysdbtype) values (3,0,'default pgsql db', 'default_pgsql');","greensql");
	}
	
	
	if(!$q->TABLE_EXISTS("admin", "greensql")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating admin table\n";
		$sql= "CREATE table admin(
			adminid         int unsigned NOT NULL auto_increment primary key,
			name           char(50) NOT NULL default '',
			pwd            char(50) NOT NULL default '',
			email          char(50) NOT NULL default ''
			) DEFAULT CHARSET=utf8;";
		
		$q->QUERY_SQL($sql,"greensql");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed $q->mysql_error\n";return false;}
		$q->QUERY_SQL("insert into admin values(1,'admin',sha1('pwd'),'');","greensql");

	}

	if(!$q->TABLE_EXISTS("alert", "greensql")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating alert table\n";
		$sql= "CREATE table alert (
			alertid             int unsigned NOT NULL auto_increment primary key,
			agroupid            int unsigned NOT NULL default '0',
			event_time          datetime NOT NULL default '00-00-0000 00:00:00',
			risk                smallint unsigned NOT NULL default '0',
			block               smallint unsigned NOT NULL default '0',
			dbuser              varchar(50) NOT NULL default '',
			userip              varchar(50) NOT NULL default '',
			query               text NOT NULL,
			reason              text NOT NULL,
			INDEX (agroupid)
			) DEFAULT CHARSET=utf8;";
		$q->QUERY_SQL($sql,"greensql");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed $q->mysql_error\n";return false;}
	}



	if(!$q->TABLE_EXISTS("alert_group", "greensql")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating alert_group table\n";
		$sql= "CREATE table alert_group(
			agroupid            int unsigned NOT NULL auto_increment primary key,
			proxyid             int unsigned NOT NULL default '1',
			db_name             char(50) NOT NULL default '',
			update_time         datetime NOT NULL default '00-00-0000 00:00:00',
			status              smallint NOT NULL default 0,
			pattern             text NOT NULL,
			INDEX(update_time)
			)";	
			$q->QUERY_SQL($sql,"greensql");
		if(!$q->ok){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} failed $q->mysql_error\n";return false;}
	}	
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} check tables done...\n";
	return true;
}

function Greensqlversion(){
	$f=explode("\n", @file_get_contents("/usr/share/greensql-console/config.php"));
	foreach ($f as $num=>$ligne){
		if(preg_match("#version.+?([0-9\.]+)#", $ligne,$re)){
			
			return $re[1];
		}else{
			if($GLOBALS["VERBOSE"]){echo "\"$ligne\" ->NO MATCH\n";}
		}
	}
	
}

function buildconfig(){
$version=Greensqlversion();
$q=new green_sql();
$GreenSQLBlockLevel=intval($q->GET_INFO("GreenSQLBlockLevel"));
if($GreenSQLBlockLevel==0){$GreenSQLBlockLevel=30;}
$GreenSQLWarnLevel=intval($q->GET_INFO("GreenSQLWarnLevel"));
if($GreenSQLWarnLevel==0){$GreenSQLWarnLevel=20;}

$GreenSQLRiskSQLComments=intval($q->GET_INFO("GreenSQLRiskSQLComments"));
if($GreenSQLRiskSQLComments==0){$GreenSQLRiskSQLComments=30;}

$GreenSQLRiskSenstiviteTables=intval($q->GET_INFO("GreenSQLRiskSenstiviteTables"));
if($GreenSQLRiskSenstiviteTables==0){$GreenSQLRiskSenstiviteTables=10;}

$GreenSQLRiskOrToken=intval($q->GET_INFO("GreenSQLRiskOrToken"));
if($GreenSQLRiskOrToken==0){$GreenSQLRiskOrToken=5;}

$GreenSQLRiskUnionToken=intval($q->GET_INFO("GreenSQLRiskUnionToken"));
if($GreenSQLRiskUnionToken==0){$GreenSQLRiskUnionToken=10;}

$GreenSQLRiskVarCmpVar=intval($q->GET_INFO("GreenSQLRiskVarCmpVar"));
if($GreenSQLRiskVarCmpVar==0){$GreenSQLRiskVarCmpVar=30;}

$GreenSQLRiskAlwaysTrue=intval($q->GET_INFO("GreenSQLRiskAlwaysTrue"));
if($GreenSQLRiskAlwaysTrue==0){$GreenSQLRiskAlwaysTrue=30;}

$GreenSQLRiskEmptyPassword=intval($q->GET_INFO("GreenSQLRiskEmptyPassword"));
if($GreenSQLRiskEmptyPassword==0){$GreenSQLRiskEmptyPassword=30;}

$GreenSQLRiskMultipleQueries=intval($q->GET_INFO("GreenSQLRiskMultipleQueries"));
if($GreenSQLRiskMultipleQueries==0){$GreenSQLRiskMultipleQueries=15;}

$GreenSQLVerbose=intval($q->GET_INFO("GreenSQLVerbose"));
if($GreenSQLVerbose==0){$GreenSQLVerbose=3;}


$f[]="#database settings";
$q=new mysql();
$f[]="[database]";
$f[]="dbhost=127.0.0.1";
$f[]="dbname=greensql";
$f[]="dbuser=greensql";
$f[]="dbpass=greensql";
$f[]="dbtype=mysql";
$f[]="";
$f[]="[logging]";
$f[]="# logfile - this parameter specifies location of the log file.";
$f[]="# By default this will point to /var/log/greensql.log file in linux.";
$f[]="logfile = /var/log/greensql.log";
$f[]="# loglevel - this parameter specifies level of logs to produce.";
$f[]="# Bigger value yelds more debugging information.";
$f[]="loglevel = $GreenSQLVerbose";
$f[]="";
$f[]="[risk engine]";
$f[]="# If query risk is bigger then specified value, query will be blocked";
$f[]="block_level = $GreenSQLBlockLevel";
$f[]="# Level of risk used to generate warnings. It is recomended to run application";
$f[]="# in low warning level and then to acknowledge all valid queries and";
$f[]="# then to lower the block_level";
$f[]="warn_level=$GreenSQLWarnLevel";
$f[]="# Risk factor associated with SQL comments";
$f[]="risk_sql_comments=$GreenSQLRiskSQLComments";
$f[]="# Risk factor associated with access to sensitive tables";
$f[]="risk_senstivite_tables=$GreenSQLRiskSenstiviteTables";
$f[]="# Risk factor associated with 'OR' SQL token";
$f[]="risk_or_token=$GreenSQLRiskOrToken";
$f[]="# Risk factor associated with 'UNION' SQL statement";
$f[]="risk_union_token=$GreenSQLRiskUnionToken";
$f[]="# Risk factor associated with variable comparison. For example: 1 = 1";
$f[]="risk_var_cmp_var=$GreenSQLRiskVarCmpVar";
$f[]="# Risk factor associated with variable ony operation which is always true.";
$f[]="# For example: SELECT XXX from X1 WHERE 1";
$f[]="risk_always_true=$GreenSQLRiskAlwaysTrue";
$f[]="# Risk factor associated with an empty password SQL operation.";
$f[]="# For example : SELECT * from users where password = \"\"";
$f[]="# It works with the following fields: pass/pwd/passwd/password";
$f[]="risk_empty_password=$GreenSQLRiskEmptyPassword";
$f[]="# Risk factor associated with miltiple queires which are separated by ";"";
$f[]="risk_multiple_queries=$GreenSQLRiskMultipleQueries";
$f[]="# Risk of SQL commands that can used to bruteforce database content.";
$f[]="risk_bruteforce=15";
@mkdir("/etc/greensql",644,true);
@file_put_contents("/etc/greensql/greensql.conf", @implode("\n", $f));
echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} check greensql.conf done...\n";
unset($f);
echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} v$version\n";
$f[]="<?php";
$f[]="\$version = \"$version\";";
$f[]="\$db_type = \"mysql\";";
$f[]="\$db_host = \"$q->mysql_server\";";
$f[]="\$db_port = $q->mysql_port;";
$f[]="\$db_name = \"greensql\";";
$f[]="\$db_user = \"$q->mysql_admin\";";
$f[]="\$db_pass = \"$q->mysql_password\";";
$f[]="\$log_file = \"/var/log/greensql.log\";";
$f[]="\$num_log_lines = 200;";
$f[]="\$limit_per_page = 10;";
$f[]="\$cache_dir = \"templates_c\";";
$f[]="\$smarty_dir = \"/usr/share/php/smarty\";";
$f[]="";
$f[]="?>";
@file_put_contents("/usr/share/greensql-console/config.php", @implode("\n", $f));
shell_exec("/bin/chmod 0777 /usr/share/greensql-console/templates_c");
echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} check config.php done...\n";
build_mysql_conf();
build_pgsql_conf();

}



