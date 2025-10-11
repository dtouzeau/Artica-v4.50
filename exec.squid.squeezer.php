<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["BYPASS"]=true;$GLOBALS["REBUILD"]=false;$GLOBALS["OLD"]=false;$GLOBALS["FORCE"]=false;$GLOBALS["ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.dump.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--visited"){days_visited();exit;}
if($argv[1]=="--week"){week_visited();exit;}
if($argv[1]=="--month"){month_visited();exit;}



start();


function start(){
	if($GLOBALS["VERBOSE"]){echo "Starting....\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/exec.squid.squeezer.php.start.time";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	
	
	
	@file_put_contents($pidfile, getmypid());
	$TimeExe=$unix->file_time_min($pidTime);
	
	if(system_is_overloaded(basename(__FILE__))){
		$php=$unix->LOCATE_PHP5_BIN();
		$unix->THREAD_COMMAND_SET("$php ".__FILE__);
		return;
	}
	
	
	if(!$GLOBALS["FORCE"]){
		if($TimeExe<240){return;}
	}
	
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	$q=new mysql_squid_builder();
	
	$sql="CREATE TABLE IF NOT EXISTS `squidlogs`.`squeezer` (
			`filemd5` VARCHAR( 90 ) NOT NULL ,
			`datefrom` DATETIME NOT NULL,
			`dateto` DATETIME NOT NULL,
			`sended` smallint(1) NOT NULL DEFAULT 0,
			`report` TEXT,
			
			PRIMARY KEY ( `filemd5` ) ,
			KEY `datefrom` (`datefrom`),
			KEY `sended` (`sended`),
			KEY `dateto` (`dateto`)
			)  ENGINE = MYISAM;";
	
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error;return;}
	$sock=new sockets();
	$LogRotatePath=$sock->GET_INFO("LogRotatePath");
	if($LogRotatePath==null){$LogRotatePath="/home/logrotate";}
	if(!is_dir($LogRotatePath)){@mkdir($LogRotatePath,0755);}
	$LogRotatePathWork="$LogRotatePath/merged";
	$BigMerged="$LogRotatePathWork/access.merged.log";
	if(!is_file($BigMerged)){$BigMerged="/var/log/squid/access.log";}
	
	$filmd5=md5_file($BigMerged);
	$sql="SELECT filemd5 FROM squeezer WHERE filemd5='$filmd5'";
	$ligne=mysqli_fetch_array($q->QUERY_SQL($sql));
	if($ligne["filemd5"]<>null){
		if($GLOBALS["VERBOSE"]){echo "$filmd5 already done...\n";}
		return;
	}
	
	
	$cmdline="/usr/share/artica-postfix/bin/squeezer.pl < $BigMerged >$LogRotatePathWork/squeezer.html";
	if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	shell_exec($cmdline);
	
	$tr=explode("\n",@file_get_contents("$LogRotatePathWork/squeezer.html"));
	
	$FromDate=null;
	$Todate=null;
	
	while (list ($num, $val) = each ($tr) ){
		if(!preg_match("#Statistics from\s+(.+?)\s+to\s+(.+?)<#i", $val,$re)){continue;}
		$FromDate=$re[1];
		$Todate=$re[2];
		break;
	}
	
	$Timdate1=strtotime($FromDate);
	$Timdate2=strtotime($Todate);
	$TimdateSQL1=date("Y-m-d H:i:s",$Timdate1);
	$TimdateSQL2=date("Y-m-d H:i:s",$Timdate2);
	if($GLOBALS["VERBOSE"]){echo "Report from ".date("Y-m-d H:i:s",$Timdate1)." ".date("Y-m-d H:i:s",$Timdate2)."\n";}
	$text=mysql_escape_string2(@implode("\n", $tr));
	
	$q->QUERY_SQL("INSERT IGNORE INTO `squeezer` (filemd5,datefrom,dateto,sended,report) 
			VALUES ('$filmd5','$TimdateSQL1','$TimdateSQL2','0','$text')");
	
	if(!$q->ok){echo $q->mysql_error;}
	
	
	
	
	
}
