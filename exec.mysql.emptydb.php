<?php
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');


if($argv[1]=="--db"){empty_db($argv[2]);}


function empty_db($database){
	$GLOBALS["LOGSDB"]=$database;
	$logFile=PROGRESS_DIR."/empty-{$GLOBALS["LOGSDB"]}.txt";
	@unlink($logFile);
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
			ouputz("already running PID:$pid",__LINE__);
			return;
		}
		$t=time();
	@file_put_contents($pidfile,getmypid());
	ouputz("Instanciate library...",__LINE__);
	$q=new mysql();
	if($database=="syslogstore"){
		ouputz("Please wait, Cleaning table store...",__LINE__);
		
		$q->QUERY_SQL("TRUNCATE TABLE `store`","syslogstore");
		if(!$q->ok){
			ouputz("Error, $q->mysql_error",__LINE__);
			if(preg_match("#Can't create.+?write to file#", $q->mysql_error)){
				ouputz("Please wait, removing store table from disk...",__LINE__);
				remove_disk_table("store",$database);
			}
			return;
		}
		ouputz("Cleaning syslogstore done took ".$unix->distanceOfTimeInWords($t,time(),true),__LINE__);
		@unlink(PROGRESS_DIR."/squidlogs.stats");
		return;
		
		
	}
	
	if($database=="squidlogs"){
		emptysquidlogs();
		ouputz("Cleaning squidlogs done took ".$unix->distanceOfTimeInWords($t,time(),true),__LINE__);
		@unlink(PROGRESS_DIR."/squidlogs.stats");
		return;		
	}
	
		
	
}

function remove_disk_table_all($table,$database){
	$sock=new sockets();
	$unix=new unix();
	$MYSQL_DATA_DIR=$sock->GET_INFO("ChangeMysqlDir");
	if($MYSQL_DATA_DIR==null){$MYSQL_DATA_DIR="/var/lib/mysql";}
	ouputz("Remove $MYSQL_DATA_DIR/$database/$table.(*)",__LINE__);	
	$t=time();
	$rm=$unix->find_program("rm");	
	shell_exec("$rm -f $MYSQL_DATA_DIR/$database/$table.*");
	
}





function remove_disk_table($table,$database){
	$unix=new unix();
	$sock=new sockets();
	$MYSQL_DATA_DIR=$sock->GET_INFO("ChangeMysqlDir");
	if($MYSQL_DATA_DIR==null){$MYSQL_DATA_DIR="/var/lib/mysql";}
	ouputz("Remove $MYSQL_DATA_DIR/$database/$table.MYD(*)",__LINE__);
	
	$t=time();
	$rm=$unix->find_program("rm");
	$touch=$unix->find_program("touch");
	shell_exec("$rm -f $MYSQL_DATA_DIR/$database/$table.MYD");
	shell_exec("$rm -f $MYSQL_DATA_DIR/$database/$table.MYD*");
	ouputz("Cleaning $database/$table done took ".$unix->distanceOfTimeInWords($t,time(),true),__LINE__);
	shell_exec("$touch $MYSQL_DATA_DIR/$database/$table.MYD");
	@chmod("$MYSQL_DATA_DIR/$database/$table.MYD", 0755);
	@chown("$MYSQL_DATA_DIR/$database/$table.MYD", "mysql");
	@chgrp("$MYSQL_DATA_DIR/$database/$table.MYD", "mysql");
	@unlink(PROGRESS_DIR."/squidlogs.stats");
}

function emptysquidlogs(){
	$unix=new unix();
	$sock=new sockets();
	$q=new mysql();
	$MYSQL_DATA_DIR=$sock->GET_INFO("ChangeMysqlDir");
	if($MYSQL_DATA_DIR==null){$MYSQL_DATA_DIR="/var/lib/mysql";}
	$dir="$MYSQL_DATA_DIR/squidlogs";
	
	$TABLESDELETE["searchwords_*"]=true;
	$TABLESDELETE["UserSizeD_*"]=true;
	$TABLESDELETE["youtubeday_*"]=true;
	$TABLESDELETE["*_week"]=true;
	$TABLESDELETE["*_blocked_week"]=true;
	$TABLESDELETE["*_members"]=true;
	$TABLESDELETE["*_hour"]=true;
	$TABLESDELETE["*_visited"]=true;
	$TABLESDELETE["dansguardian_events_*"]=true;
	$TABLESDELETE["squidhour_*"]=true;
	$TABLESDELETE["visited_sites"]=true;
	$TABLESDELETE["UserAuth*"]=true;
	$TABLESDELETE["tables_day"]=true;
	$TABLESDELETE["adansguardian_events_*"]=true;
	$TABLESDELETE["*_day"]=true;
	$TABLESDELETE["youtubeweek_*"]=true;
	ouputz("Remove ".count($TABLESDELETE)." family tables in $MYSQL_DATA_DIR/squidlogs",__LINE__);	
	
	while (list ($pattern, $line) = each ($TABLESDELETE)){
	
	foreach (glob("$dir/$pattern.MYD") as $filename) {
		$table=str_replace(".MYD", "", basename($filename));
		$table=str_replace(".frm", "", $table);
		if(preg_match("#(.+?)\/(.+?)\.[A-Za-Z]+$#", $table,$re)){$table=$re[1];}
		ouputz("Removing $table....",__LINE__);
		$q->QUERY_SQL("DROP TABLE `$table`","squidlogs");
		if(!$q->ok){
			ouputz("Error, $q->mysql_error",__LINE__);
			
			if(preg_match("#Unknown table#i", $q->mysql_error)){
				remove_disk_table_all($table,"squidlogs");
				continue;
			}
			
			remove_disk_table($table,"squidlogs");
			$q->QUERY_SQL("DROP TABLE `$table`","squidlogs");
			if(!$q->ok){ouputz("Error, $q->mysql_error",__LINE__);}
		}
		
	}
	
	}
	
	foreach (glob("$dir/*.BAK") as $filename) {
		ouputz("Removing backup file ". basename($filename)."....",__LINE__);
		@unlink($filename);
	}
	
}

function ouputz($text,$line){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$pid=@getmypid();
	$date=@date("H:i:s");
	
	$logFile=PROGRESS_DIR."/empty-{$GLOBALS["LOGSDB"]}.txt";
	if($GLOBALS["VERBOSE"]){echo "$logFile\n";}
	if(is_file($logFile)){
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = fopen($logFile, 'a');
	fwrite($f, "$date [$pid][$line]: $text\n");
	fclose($f);
	chmod($logFile, 0777);
}