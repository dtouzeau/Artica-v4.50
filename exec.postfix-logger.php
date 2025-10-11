<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["ULIMITED"]=false;
$GLOBALS["VERBOSE2"]=false;
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.postfix.builder.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.ini-frame.inc");

if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--unlimit#",implode(" ",$argv))){$GLOBALS["ULIMITED"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--verb2#",implode(" ",$argv))){$GLOBALS["VERBOSE2"]=true;}

$sock=new sockets();
$DisableMessaging=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableMessaging"));
if($DisableMessaging==1){exit();}

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
$GLOBALS["ArticaStatusUsleep"]=$sock->GET_INFO("ArticaStatusUsleep");
$GLOBALS["ArticaSMTPStatsTimeFrame"]=$sock->GET_INFO("ArticaSMTPStatsTimeFrame");
$GLOBALS["ArticaSMTPStatsMaxFiles"]=$sock->GET_INFO("ArticaSMTPStatsMaxFiles");
$GLOBALS["EnableArticaSMTPStatistics"]=$sock->GET_INFO("EnableArticaSMTPStatistics");
if(!is_numeric($GLOBALS["ArticaSMTPStatsTimeFrame"])){$GLOBALS["ArticaSMTPStatsTimeFrame"]=2;}
if(!is_numeric($GLOBALS["ArticaStatusUsleep"])){$GLOBALS["ArticaStatusUsleep"]=50000;}
if(!is_numeric($GLOBALS["ArticaSMTPStatsMaxFiles"])){$GLOBALS["ArticaSMTPStatsMaxFiles"]=2400;}
if(!is_numeric($GLOBALS["EnableArticaSMTPStatistics"])){$GLOBALS["EnableArticaSMTPStatistics"]=1;}
@mkdir("{$GLOBALS["ARTICALOGDIR"]}/MGREYSTATS",0755,true);


if($argv[1]=='--postqueue'){postqueue();exit;}

if($argv[1]=='--cnx-errors'){
	events("Starting cnx-errors ...");
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/postqueue.cnx-errors.time";
	$exTime=$unix->file_time_min($timefile);
	if($exTime<7){exit();}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	ScanPostFixConnectionsErr();
	connexion_errors_stats();
	exit();
}
if($argv[1]=='--milter'){
	events("Starting mgreylist ...");
	MiltergreyList();
	exit();
}


if($argv[1]=='--cnx-only'){
	events("Starting cnx-only ...");
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/postqueue.cnx-only.time";
	$exTime=$unix->file_time_min($timefile);
	if($exTime<8){exit();}
	@unlink($timefile);
	@file_put_contents($timefile, time());	
	ScanPostFixConnections();
	connexion_errors_stats();
	exit();
}
if($argv[1]=='--postqueue'){
	events("Starting postqueue ...");
	postqueue();
	CleanQueues();
	exit();
}

if($argv[1]=='--geo'){
	$GLOBALS["VERBOSE"]=true;
	events("Starting geoip  {$argv[2]}...");
	DebugGeo();
	GeoIP($argv[2]);
	exit();
}


if($argv[1]=='--postqueue-clean'){
	events("Starting postqueue ...");
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/postqueue.clean.time";
	$exTime=$unix->file_time_min($timefile);
	if($exTime<5){exit();}	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	CleanQueues();
	postqueue();
	exit();
}

if($argv[1]=='--postqueue-truncate'){
	events("Starting empty postqueue ...");
	$q=new mysql();
	$q->QUERY_SQL("truncate table postqueue","artica_events");
	exit();
}


if($argv[1]=='--cnx-stats'){
	events("Starting connexion_errors_stats ...");
	ScanPostFixConnections();
	ScanPostFixConnectionsErr();	
	connexion_errors_stats();
	exit();
}


$unix=new unix();
$pidpath="/etc/artica-postfix/pids.3/".basename(__FILE__)."pid";
if($unix->process_exists(@file_get_contents($pidpath))){
	writelogs(basename(__FILE__).":Already executed.. PID: ". @file_get_contents($pidpath). " aborting the process",basename(__FILE__),__FILE__,__LINE__);
	exit();
}

@file_put_contents($pidpath,getmypid());

$sock=new sockets();
$GLOBALS["ArticaStatusUsleep"]=$sock->GET_INFO("ArticaStatusUsleep");
$GLOBALS["ArticaSMTPStatsTimeFrame"]=$sock->GET_INFO("ArticaSMTPStatsTimeFrame");
$GLOBALS["ArticaSMTPStatsMaxFiles"]=$sock->GET_INFO("ArticaSMTPStatsMaxFiles");
$GLOBALS["EnableArticaSMTPStatistics"]=$sock->GET_INFO("EnableArticaSMTPStatistics");
if(!is_numeric($GLOBALS["ArticaSMTPStatsTimeFrame"])){$GLOBALS["ArticaSMTPStatsTimeFrame"]=2;}
if(!is_numeric($GLOBALS["ArticaStatusUsleep"])){$GLOBALS["ArticaStatusUsleep"]=50000;}
if(!is_numeric($GLOBALS["ArticaSMTPStatsMaxFiles"])){$GLOBALS["ArticaSMTPStatsMaxFiles"]=2400;}
if(!is_numeric($GLOBALS["EnableArticaSMTPStatistics"])){$GLOBALS["EnableArticaSMTPStatistics"]=1;}


if($GLOBALS["EnableArticaSMTPStatistics"]==0){
	events("Statistics generation is disabled");
	shell_exec("/bin/rm -rf {$GLOBALS["ARTICALOGDIR"]}/RTM/*");
	exit();
}

if($argv[1]=='--amavis-stats'){
	events("amavis stats ...");
	amavis_event_hour();
	exit();
}


@mkdir("{$GLOBALS["ARTICALOGDIR"]}/IMAP",0755,true);

if($argv[1]=='--postfix'){
	events("Starting OnlyPostfix...");
	OnlyPOstfix();
	MiltergreyList();
	exit();
}
if($argv[1]=='--cnx'){
	events("Starting ScanPostFixConnections...");
	ScanPostFixConnections();
	ScanPostFixConnectionsErr();
	MiltergreyList();
	exit();
}
if($argv[1]=='--cnx-errors'){
	events("Starting ScanPostFixConnections mysql errors...");
	ScanPostFixMysqlErr();
	exit();
}

if($argv[1]=='--users-stats'){
	events("Starting smtp_logs_day_users ...");
	smtp_logs_day_users();
	exit();
}

if($argv[1]=='--amavis'){
	events("Starting ScanPostfixID ...");
	$q=new mysql();
	ScanPostfixID($q);
	exit();
}




	$unix=new unix();
	$pid=getmypid();
	$pidefile="/etc/artica-postfix/croned.1/".basename(__FILE__).".pid";
	if(is_file($pidefile)){
		$currentpid=trim(@file_get_contents($pidefile));
		if($currentpid<>$pid){
			if($unix->process_exists($currentpid)){
			write_syslog("Already instance $currentpid executed aborting...",__FILE__);
			exit();
			}	
		}
	}
	
	@file_put_contents($pidefile,$pid);

$q=new mysql();

if($GLOBALS["VERBOSE"]){echo "-> ScanPostfixID()\n";}
ScanPostfixID($q);
if($GLOBALS["VERBOSE"]){echo "-> ScanCyrusConnections()\n";}
ScanCyrusConnections($q);
if($GLOBALS["VERBOSE"]){echo "-> ScanPostFixConnections()\n";}
ScanPostFixConnections();
if($GLOBALS["VERBOSE"]){echo "-> ScanPostFixConnectionsErr()\n";}
ScanPostFixConnectionsErr();
if($GLOBALS["VERBOSE"]){echo "-> ScanVirusQueue()\n";}
ScanVirusQueue($q);
CheckPostfixLogs();
MiltergreyList();
ScanPostFixMysqlErr();
smtp_logs_day_users();
postqueue();
CleanQueues();
amavis_event_hour();
THREAD_COMMAND_SET(LOCATE_PHP5_BIN() ." ". dirname(__FILE__)."/exec.last.100.mails.php");
optimizetable();



function OnlyPOstfix(){
	$pid=getmypid();
	
	$pidefile="/etc/artica-postfix/pids/".basename(__FILE__).".onlypostfix.pid";
	if(is_file($pidefile)){
		$currentpid=trim(@file_get_contents($pidefile));
		if($currentpid<>$pid){
			if(is_dir('/proc/'.$currentpid)){
			write_syslog("Already instance executed aborting...",__FILE__);
			exit();
			}	
		}
	}
	
	
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$timesched=$unix->file_time_min($timefile);
	if($timesched<=$GLOBALS["ArticaSMTPStatsTimeFrame"]){
		if($GLOBALS["VERBOSE"]){echo "$timesched/{$GLOBALS["ArticaSMTPStatsTimeFrame"]} aborting\n";}
		return;
	}
			
	
	file_put_contents($pidefile,$pid);
	$path="{$GLOBALS["ARTICALOGDIR"]}/RTM";
	if (!$handle = opendir($path)) { @mkdir($path,0755,true);return;}
	$countDeFiles=0;
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$path/$filename";
		$countDeFiles++;
		if($countDeFiles>$GLOBALS["ArticaSMTPStatsMaxFiles"]){break;}
		events("OnlyPOstfix(): parsing $targetFile $countDeFiles/{$GLOBALS["ArticaSMTPStatsMaxFiles"]}");
		if(!preg_match("#\.msg$#",$filename)){continue;}
		if($filename=="NOQUEUE.msg"){@unlink("$targetFile");continue;}
		if(!is_file("$targetFile")){continue;}
		usleep($GLOBALS["ArticaStatusUsleep"]);
		
		if(PostfixFullProcess("$targetFile")){
			echo "OnlyPOstfix(): success $targetFile $countDeFiles/{$GLOBALS["ArticaSMTPStatsMaxFiles"]}\n";
			@unlink($targetFile);
		}
	}
	//MiltergreyList();	
	ScanPostFixConnections();
	ScanPostFixConnectionsErr();		
}

function MiltergreyList(){
	$WORKDIR="{$GLOBALS["ARTICALOGDIR"]}/MGREYSTATS";
	if (!$handle = opendir($WORKDIR)) { @mkdir($WORKDIR,0755,true);return;}
	$countDeFiles=0;
	$array=array();
	
	
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$WORKDIR/$filename";
		$countDeFiles++;
		$content=@file_get_contents($targetFile);
		if(!preg_match("#MGREYSTATS:(.+?),(.+?),(.*?),(.*?),(.*?),(.*?),(.*?),(.*?)#", $content,$re)){
			events("MiltergreyList():: bad content `$content`",__FILE__);
			@unlink($targetFile);
			continue;
		}
		
		$md5=md5($content);
		$instance=$re[1];
		$publicip=$re[2];
		$mailfrom=addslashes($re[3]);
		$rcpt=addslashes($re[4]);
		$tt=explode("@", $mailfrom);
		$domainfrom=$tt[1];
		$tt=explode("@", $rcpt);
		$domainto=$tt[1];
		$unknown1=$re[5];
		$unknown2=$re[6];
		$failed=$re[7];
		$country=$re[8];	
		$time=filemtime($targetFile);
		$date=date("Y-m-d H:i:s",$time);
		$hourtable="mgreyh_".date("YmdH",$time);
		$HOUR=date('H',$time);
		@unlink($targetFile);
		$array[$hourtable][]="('$md5','$date','$HOUR','$mailfrom','$instance','$rcpt','$domainfrom','$domainto','$publicip','$failed')";
		if(count($array[$hourtable])>800){MiltergreyList_inject($array);$array=array();}
	}
	
	MiltergreyList_inject($array);
	events("MiltergreyList():: Finish...",__FILE__);
}




function MiltergreyList_inject($array){
	if(count($array)==0){
		events("MiltergreyList_inject():: Nothing to do...",__FILE__);
		return;}
	$q=new mysql_postfix_builder();
	events("MiltergreyList_inject():: Analyze ". count($array) ." rows",__FILE__);
    foreach ($array as $tablename=>$sqls){
		events("MiltergreyList_inject():: build-sql $tablename > ". count($array) ." rows",__FILE__);
		$q->milter_BuildHourTable($tablename);
		if(!$q->TABLE_EXISTS($tablename)){events("MiltergreyList_inject():: $tablename no such table",__FILE__);}
		
		$prefix="INSERT IGNORE INTO $tablename (`zmd5`,`ztime`,`zhour`,`mailfrom`,`instancename`,`mailto`,`domainfrom`,`domainto`,`senderhost`,`failed`) VALUES ";
		$q->QUERY_SQL($prefix.@implode(",", $sqls));
		if(!$q->ok){events("MiltergreyList_inject():: $q->mysql_error",__FILE__);}
	}
	events("MiltergreyList_inject():: Finish...",__FILE__);
	
}


function ScanPostFixConnections(){
	$path="{$GLOBALS["ARTICALOGDIR"]}/smtp-connections";
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$ipcache_path="/etc/artica-postfix/ipcaches.db";
	if($unix->process_exists(@file_get_contents($pidfile))){
		events_cnx(__FUNCTION__."():: -> Already executed aborting");
		return;
	}
	
	$pid=getmypid();
	@file_put_contents($pidfile,getmypid());
	events_cnx(__FUNCTION__."():: -> starting PID:$pid");
	
	$timesched=$unix->file_time_min($timefile);
	if($timesched<=$GLOBALS["ArticaSMTPStatsTimeFrame"]){
		events_cnx(__FUNCTION__."():: -> $timesched/{$GLOBALS["ArticaSMTPStatsTimeFrame"]} aborting");
		if($GLOBALS["VERBOSE"]){echo "$timesched/{$GLOBALS["ArticaSMTPStatsTimeFrame"]} aborting\n";}
		return;
	}	
	
	$c=0;
	$q=new mysql();
	$prefix="INSERT IGNORE INTO mail_con (zmd5,zDate,hostname,ipaddr) VALUES";
	events_cnx(__FUNCTION__."():: -> starting glob in line:".__LINE__);
	if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/smtp-connections")) {
		events_cnx(__FUNCTION__."():: -> glob failed in line:".__LINE__);
		return ;
	}

	$ArticaSMTPStatsMaxFiles=$GLOBALS["ArticaSMTPStatsMaxFiles"];
	
	while (false !== ($filename = readdir($handle))) {
		@unlink($filename);
		
	}
	
	
}

function ScanPostFixMysqlErr(){
	$path="{$GLOBALS["ARTICALOGDIR"]}/smtp-connections";
	$c=0;
	$q=new mysql();
	
	events_cnx(__FUNCTION__."():: -> starting glob $path/*.sql:".__LINE__);
	
	
	
	foreach (glob("$path/*.sql") as $filename) {
		$sql=@file_get_contents($filename);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo "ScanPostFixMysqlErr():: Filename:$filename\n$q->mysql_error\n\n\n";continue;}
		@unlink($filename);
		
	}
	events_cnx(__FUNCTION__."():: -> FINISH:".__LINE__);
	
}


function ScanPostFixConnectionsErr(){
	
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($unix->process_exists(@file_get_contents($pidpath))){
		events_cnx(basename(__FILE__).":".__FUNCTION__."::Already executed.. PID: ". @file_get_contents($pidpath). " aborting the process",basename(__FILE__),__FILE__,__LINE__);
		return;
	}	
	
	$pid=getmypid();
	events_cnx(basename(__FILE__).":".__FUNCTION__.":: start pid $pid");
	
	@file_put_contents($pidpath,$pid);
	
	$path="{$GLOBALS["ARTICALOGDIR"]}/smtp-connections";
	$c=0;
	$q=new mysql();
	$prefix="INSERT IGNORE INTO mail_con_err (zmd5,zDate,hostname,ipaddr,smtp_err) VALUES";
	
	if (!$handle = opendir("{$GLOBALS["ARTICALOGDIR"]}/smtp-connections")) {
		events_cnx(__FUNCTION__."():: -> glob failed in line:".__LINE__);
		return ;
	}	
	
	$ArticaSMTPStatsMaxFiles=$GLOBALS["ArticaSMTPStatsMaxFiles"]*22;
	
	 while (false !== ($filename = readdir($handle))) {
	 	if(!preg_match("#\.err$#",basename($filename))){continue;}
	 	$filename="{$GLOBALS["ARTICALOGDIR"]}/smtp-connections/$filename";
	 	if(!is_file($filename)){events_cnx("ScanPostFixConnectionsErr():: $filename no such file");continue;}
		$array=unserialize(@file_get_contents($filename));
		if(!is_array($array)){
			events_cnx("ScanPostFixConnectionsErr():: $filename not an array");
			@unlink($filename);
			continue;
		}
		$date=date("Y-m-d H:i:s",$array["TIME"]);
		$hostname=$array["HOSTNAME"];
		$IP=$array["IP"];
		$error=$array["error"];
		
		if(($hostname==null) && ($IP==null) && ($error==null)){
			@unlink($filename);
			events_cnx(__FUNCTION__.":: $filename no datas");
			continue;
		}
		
		if($hostname=="unknown"){$hostname=null;}
		
		
		$c++;
		if(!$GLOBALS["ULIMITED"]){
			if($c>$ArticaSMTPStatsMaxFiles){events_cnx("ScanPostFixConnectionsErr():: Break after $c events unlimit:{$GLOBALS["ULIMITED"]}");break;}
		}
		
		
		if($hostname==null){
			if($GLOBALS["HOSTS"][$IP]==null){$hostname=gethostbyaddr($IP);}else{$hostname=$GLOBALS["HOSTS"][$IP];}
		}
		$GLOBALS["HOSTS"][$IP]=$hostname;
		
		//echo "$date $IP ($hostname)\n";
		$md5=md5("$date$IP$hostname");
		
		
		$sq[]="('$md5','$date','$hostname','$IP','$error')";
		@unlink($filename);
		
		if(count($sq)>500){
			events_cnx("ScanPostFixConnectionsErr():: Writing $c events ex:('$md5','$date','$hostname','$IP','$error')");
			$sql=$prefix." ".@implode(",",$sq);
			unset($sq);
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				events_cnx("ScanPostFixConnectionsErr()::$q->mysql_error");
				@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/smtp-connections/".time().".sql",$sql);
				writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
			}
		}
		
	}
	
	closedir($handle);
	
	
	if(count($sq)>0){
		events_cnx("ScanPostFixConnectionsErr():: write ". count($sq)." events");
		$sql=$prefix." ".@implode(",",$sq);
		unset($sq);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){
			@file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/smtp-connections/".time()."sql",$sql);
			writelogs($q->mysql_error,__FUNCTION__,__FILE__,__LINE__);
		}		
	}	
	
	
}

function ScanCyrusConnections($q){
$path="{$GLOBALS["ARTICALOGDIR"]}/IMAP";
	$files=DirListsql($path);
	$startedAT=date("Y-m-d H:i:s");
	$count=0;
	if(!is_array($files)){events(__FUNCTION__." No files.. Aborting\n");return null;}
   events("ScanCyrusConnections():: Get sql in $path ". count($files)." files");
    foreach ($files as $num=>$file){
		$count=$count+1;
		events("running $path/$file");	
		$q->QUERY_SQL(@file_get_contents("$path/$file"),"artica_events");
		if(!$q->ok){
			events("$path/$file failed");
		}else{
			@unlink("$path/$file");
		}
	}
}

function ScanVirusQueue($q){
$path="{$GLOBALS["ARTICALOGDIR"]}/infected-queue";
	$files=DirListsql($path);
	$startedAT=date("Y-m-d H:i:s");
	$count=0;
	if(!is_array($files)){events(__FUNCTION__." No files.. Aborting\n");return null;}
   events("Get sql in $path ". count($files)." files");
    foreach ($files as $num=>$file){
		$count=$count+1;
		events("ScanVirusQueue():: running $path/$file");	
		$q->QUERY_SQL(@file_get_contents("$path/$file"),"artica_events");
		if(!$q->ok){
			events("$path/$file failed");
		}else{
			@unlink("$path/$file");
		}
	}
}


function ScanPostfixID($q){
	$q=new mysql();
	$unix=new unix();
	$super=0;
	$path="{$GLOBALS["ARTICALOGDIR"]}/RTM";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$timesched=$unix->file_time_min($timefile);
	if($timesched<=$GLOBALS["ArticaSMTPStatsTimeFrame"]){
		if($GLOBALS["VERBOSE"]){echo "$timesched/{$GLOBALS["ArticaSMTPStatsTimeFrame"]} aborting\n";}
		return;
	}
	
	
	
	if($GLOBALS["VERBOSE"]){echo "Scanning $path...\n";}
	
	$startedAT=date("Y-m-d H:i:s");
	$count=0;
	if (!$handle = opendir($path)) { @mkdir($path,0755,true);return;}
   	$countDeFiles=0;
   	$KILL_THIS_QUEUE=false;
   	while (false !== ($file = readdir($handle))) {
   		if($file=="."){continue;}
   		if($file==".."){continue;}
   		$targetFile="$path/$file";
   		$countDeFiles++;
   		if($countDeFiles>$GLOBALS["ArticaSMTPStatsMaxFiles"]){break;}
		$count++;
		$super++;
		if(preg_match("#\.id-message$#",$file)){$amavis[]=$file;continue;}
		events("ScanPostfixID():: ($count/{$GLOBALS["ArticaSMTPStatsMaxFiles"]})");
		events("ScanPostfixID()::  \"$path/$file\"");
		if(!preg_match("#\.msg$#",$file)){continue;}
				
		if($file=="NOQUEUE.msg"){
			events("ScanPostfixID():: Delete /$path/$file");
			@unlink("$path/$file");
			continue;
		}
		
		if($KILL_THIS_QUEUE){
			@unlink("/$path/$file");
			continue;
		}
		
		if($GLOBALS["VERBOSE"]){echo "ArticaStatusUsleep:{$GLOBALS["ArticaStatusUsleep"]}ms\n";}	
		usleep($GLOBALS["ArticaStatusUsleep"]);
		if($count>$GLOBALS["ArticaSMTPStatsMaxFiles"]){events("ScanPostfixID():: Break...");break;}
		if($GLOBALS["VERBOSE"]){echo "PostfixFullProcess -> $targetFile\n";}
		$t1=time();
		if(!PostfixFullProcess($targetFile,$q)){continue;}
		$t2=time();
		$distanceInSeconds = round(abs($t2 - $t1));
		if($GLOBALS["VERBOSE"]){echo "PostfixFullProcess -> $distanceInSeconds seconds\n";}
		if($distanceInSeconds>3){
			send_email_events("Too many time parsing {$GLOBALS["ARTICALOGDIR"]}/RTM", "Processing file take more than 3s ({$distanceInSeconds}s) the realtime monitor will be kept for this queue..", "postfix");
			$KILL_THIS_QUEUE=true;
		}
		
		SetStatus("Postfix",$GLOBALS["ArticaSMTPStatsMaxFiles"],$count,$startedAT);
		events("ScanPostfixID():: ($count/{$GLOBALS["ArticaSMTPStatsMaxFiles"]}) with a sleep of {$GLOBALS["ArticaStatusUsleep"]} microseconds line ".__LINE__);
   	}		
	

	
	
	
	
	$path="/tmp/savemail-infos";
	if (!$handle = opendir($path)) { @mkdir($path,0755,true);return;}
	$max=$GLOBALS["ArticaSMTPStatsMaxFiles"];	
	$count=0;
	while (false !== ($file = readdir($handle))) {
   		if($file=="."){continue;}
   		if($file==".."){continue;}
   		$targetFile="$path/$file";
		$super++;
		events("ScanPostfixID():amavis_logger(): parsing /tmp/savemail-infos/$file $count/$max");
		amavis_logger("/tmp/savemail-infos/$file");
	}
		
	
	
	
	
if($super++>0){
	$unix->THREAD_COMMAND_SET($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.postfix.vip.php");
	write_syslog("Success inserting $super mails events in mysql database...",__FILE__);
	}
	
}


function SetStatus($filetype,$max,$current,$startedAT){
	$ini=new Bs_IniHandler();
	$ini->set("PROGRESS","type",$filetype);
	$ini->set("PROGRESS","max",$max);
	$ini->set("PROGRESS","current",$current);
	$ini->set("PROGRESS","time",date('Y-m-d H:i:s'));
	$ini->set("PROGRESS","pid",getmypid());
	$ini->set("PROGRESS","starton",$startedAT);
	
	$ini->saveFile("/usr/share/artica-postfix/ressources/logs/postfix-logger.ini");
	@chmod("/usr/share/artica-postfix/ressources/logs/postfix-logger.ini",0777);
	
	
}

function PostfixFullProcess($file){
	$q=new mysql();
	$org_file=$file;
	if(!is_file($file)){return null;}
	$ini=new Bs_IniHandler($file);
	
	if(!isset($ini->_params["TIME"]["message-id"])){$ini->_params["TIME"]["message-id"]=null;}
	if(!isset($ini->_params["TIME"]["time_end"])){$ini->_params["TIME"]["time_end"]=null;}
	if(!isset($ini->_params["TIME"]["time_start"])){$ini->_params["TIME"]["time_start"]=null;}
	if(!isset($ini->_params["TIME"]["mailfrom"])){$ini->_params["TIME"]["mailfrom"]=null;}
	if(!isset($ini->_params["TIME"]["mailto"])){$ini->_params["TIME"]["mailto"]=null;}
	if(!isset($ini->_params["TIME"]["delivery_user"])){$ini->_params["TIME"]["delivery_user"]=null;}
	if(!isset($ini->_params["TIME"]["smtp_sender"])){$ini->_params["TIME"]["smtp_sender"]=null;}
	if(!isset($ini->_params["TIME"]["size"])){$ini->_params["TIME"]["size"]=null;}
	if(!isset($ini->_params["TIME"]["time_start"])){$ini->_params["TIME"]["time_start"]=null;}
	if(!isset($ini->_params["TIME"]["time_connect"])){$ini->_params["TIME"]["time_connect"]=null;}
	if(!isset($ini->_params["TIME"]["bounce_error"])){$ini->_params["TIME"]["bounce_error"]=null;}
	if(!isset($ini->_params["TIME"]["delivery_success"])){$ini->_params["TIME"]["delivery_success"]=null;}
	
	
	$delivery_success=$ini->_params["TIME"]["delivery_success"];
	$message_id=$ini->_params["TIME"]["message-id"];
	$time_end=$ini->_params["TIME"]["time_end"];
	$mailfrom=$ini->_params["TIME"]["mailfrom"];
	$mailto=$ini->_params["TIME"]["mailto"];	
	$delivery_success=$ini->_params["TIME"]["delivery_success"];
	$bounce_error=$ini->_params["TIME"]["bounce_error"];
	$time_connect=$ini->_params["TIME"]["time_connect"];
	$delivery_user=$ini->_params["TIME"]["delivery_user"];
	$time_start=$ini->_params["TIME"]["time_start"];
	$smtp_sender=$ini->_params["TIME"]["smtp_sender"];
	$size=$ini->_params["TIME"]["size"];
	$search_postfix_id=true;
	if($time_connect==null){if($time_start<>null){$time_connect=$time_start;}}
	

	
	$file=basename($file);
	$postfix_id=str_replace(".msg","",$file);
	if($GLOBALS["VERBOSE"]){echo "PostfixFullProcess()::[$postfix_id] file:$file\n";}
	events("PostfixFullProcess()::[$postfix_id] file:$file");
	
	if(preg_match("#delivery temporarily suspended.+?Local configuration error#",$bounce_error)){$bounce_error="Remote Server error";$delivery_success="no";}
	if(preg_match("#250.+?mail accepted for delivery#",$bounce_error)){$bounce_error="Sended";$delivery_success="yes";}
	if(preg_match("#Scanning timed out#",$bounce_error)){$bounce_error="Scanning timed out";$delivery_success="no";}
	
	if($mailto==null){if($delivery_user<>null){$mailto=$delivery_user;}}
	if($time_connect==null){if($time_end<>null){$time_connect=$time_end;}}
	if($time_end==null){if($time_connect<>null){$time_end=$time_connect;}}
	
	if(preg_match('#(.+?)@(.+)#',$mailfrom,$re)){$domain_from=$re[2];}
	if(preg_match('#(.+?)@(.+)#',$mailto,$re)){$domain_to=$re[2];}
	$mailfrom=str_replace("'",'',$mailfrom);
	$mailto=str_replace("'",'',$mailto);
	
	if($delivery_success==null){$delivery_success="no";}
	
	if($message_id==null){$message_id=md5(time().$mailfrom.$mailto);}
	
	$bounce_error_array["RBL"]=true;
	$bounce_error_array["Helo command rejected"]=true;
	$bounce_error_array["Domain not found"]=true;
	$bounce_error_array["too many recipients"]=true;
	$bounce_error_array["PostScreen RBL"]=true;
	$bounce_error_array["PostScreen"]=true;
	$bounce_error_array["Scanning timed out"]=true;
	$bounce_error_array["blacklisted"]=true;
	$bounce_error_array["timed out"]=true;
	$bounce_error_array["malformed address"]=true;
	$bounce_error_array["Connection refused"]=true;
	$bounce_error_array["malformed address (ASSP)"]=true;
	
	
	if(preg_match("#lost connection with.+?\[(.+?)\]\s+#",$bounce_error,$re)){
		$bounce_error="lost connection";$delivery_success="no";
		$smtp_sender=$re[1];
	}
	
	if($bounce_error_array[$bounce_error]){$search_postfix_id=false;}
	
	if($smtp_sender<>null){
		$t1=time();
		$array_geo=GeoIP($smtp_sender);
		$t2=time();
		if($GLOBALS["VERBOSE"]){
			$distanceInSeconds = round(abs($t2 - $t1));
			echo "GeoIP($smtp_sender) -> $distanceInSeconds seconds\n";
		}
		$Country=$array_geo[0];
		$City=$array_geo[1];
		$City=addslashes($City);
		$Country=addslashes($Country);		
	}	
	
	
	if(preg_match("#,sender_user='(.+?)'#",$mailfrom,$re)){$mailfrom=$re[1];}
	if($search_postfix_id){
		$t1=time();
		$sqlid=getid_from_postfixid($postfix_id,$q);
		$t2=time();
		if($GLOBALS["VERBOSE"]){
			$distanceInSeconds = round(abs($t2 - $t1));
			echo "getid_from_postfixid($postfix_id -> $distanceInSeconds seconds\n";
		}
	}
	
	events("PostfixFullProcess():: $time_connect:: message-id=<$message_id> from=<$mailfrom> to=<$mailto> bounce_error=<$bounce_error> old id=$sqlid");
	
	if($sqlid==null){
		$domain_to=mysql_escape_string2($domain_to);
		$mailto=mysql_escape_string2($mailto);
		$bounce_error=mysql_escape_string2($bounce_error);
		$sql="INSERT IGNORE INTO smtp_logs (delivery_id_text,msg_id_text,time_connect,time_sended,delivery_success,sender_user,sender_domain,delivery_user,delivery_domain,bounce_error,smtp_sender,Country  )
		VALUES('$postfix_id','$message_id','$time_connect','$time_end','$delivery_success','$mailfrom','$domain_from','$mailto','$domain_to','$bounce_error','$smtp_sender','$Country');
		";
		if(strlen($message_id)>255){$message_id=md5($message_id);}
		events_cnx(__FUNCTION__."() ADD:[$message_id] [$smtp_sender] from=<$mailfrom> to=<$mailto> \"$bounce_error\" line:".__LINE__);
		$t1=time();
		$q->QUERY_SQL($sql,"artica_events");
		$t2=time();
		if($GLOBALS["VERBOSE"]){
			$distanceInSeconds = round(abs($t2 - $t1));
			echo "QUERY_SQL -> ADD -> $distanceInSeconds seconds\n";
		}
		
		if($q->ok){
			events("PostfixFullProcess():: Delete $org_file line:".__LINE__);
			@unlink($org_file);
			return true;
		}else{
			events_cnx("FAILED MYSQL $org_file");
			events("PostfixFullProcess():: $q->mysql_error line:".__LINE__);
			if(preg_match("#Error.+?File .+?smtp_logs.+?not found#",$q->mysql_error)){
				$unix=new unix();
				$unix->send_email_events("artica_events/smtp_logs table is crashed","mysql claim:$q->mysql_error\nThe table has been deleted and rebuilded","system");
				$sql="DROP TABLE `smtp_logs`";
				$q->QUERY_SQL($sql,"artica_events");
				$q->BuildTables();
			}
			
			events($sql);
			return false;
			}
		
	}else{
		
		$mailfrom=str_replace(">, orig_to=","",$mailfrom);
		events_cnx("EDIT:[$sqlid] from=<$mailfrom> to=<$mailto> bounce_error=\"$bounce_error\"");
		if($mailfrom<>null){$FIELDS[]="sender_user='$mailfrom'";}
		if($delivery_success<>null){$FIELDS[]="delivery_success='$delivery_success'";}
		if($domain_from<>null){$FIELDS[]="sender_domain='$domain_from'";}
		if($domain_to<>null){$FIELDS[]="delivery_domain='$domain_to'";}
		if($bounce_error<>null){$FIELDS[]="bounce_error='$bounce_error'";}
		if($time_connect<>null){$FIELDS[]="time_connect='$time_connect'";}
		if($time_end<>null){$FIELDS[]="time_sended='$time_end'";}
		if($message_id<>null){$FIELDS[]="msg_id_text='$message_id'";}
		if($smtp_sender<>null){$FIELDS[]="smtp_sender='$smtp_sender'";}
		if($size<>null){$FIELDS[]="bytes='$size'";}
		
		if(count($FIELDS)>0){
			$Settadd=",".@implode(",", $FIELDS);
		}
									
		
		$sql="UPDATE smtp_logs SET delivery_id_text='$postfix_id'$Settadd WHERE id=$sqlid";
		$t1=time();
		$q->QUERY_SQL($sql,"artica_events");
		$t2=time();
		if($GLOBALS["VERBOSE"]){
			$distanceInSeconds = round(abs($t2 - $t1));
			echo "QUERY_SQL -> UPDATE -> $distanceInSeconds seconds\n";
		}
		
		if($q->ok){
			@unlink($org_file);
			return true;
		}
		else{
			events_cnx("FAILED MYSQL $org_file");
			events("$q->mysql_error");
			events($sql);
			return false;
			}
		
	}
}

function getid_from_postfixid($postfix_id,$q){
	$date=date('Y-m-d');
	$sqlclass=new mysql();
	$sql="SELECT id FROM smtp_logs WHERE delivery_id_text='$postfix_id' AND time_stamp>=DATE_SUB(NOW(),INTERVAL 5 DAY)";
	events("getid_from_postfixid:: $sql");
	$ligne=@mysqli_fetch_array($sqlclass->QUERY_SQL($sql,"artica_events"));
	if(!$sqlclass->ok){
		events("$sqlclass->mysql_error, $sql");	
	}
	events("getid_from_postfixid($postfix_id)={$ligne["id"]}");
	return trim($ligne["id"]);
	}
	

function deleteid_from_messageid($messageid){
	if($messageid==null){return null;}
	$q=new mysql();
	$sql="DELETE FROM smtp_logs WHERE msg_id_text='$messageid'";
	$q->QUERY_SQL($sql,"artica_events");
	
}

function DirListPostfix($path,$maxscan=0){
	$dir_handle = @opendir($path);
	if(!$dir_handle){
		write_syslog("Unable to open \"$path\"",__FILE__);
		return array();
	}
		$count=0;	
		while ($file = readdir($dir_handle)) {
		  if($file=='.'){continue;}
		  if($file=='..'){continue;}
		  //$maxscan
		  
		  if(!is_file("$path/$file")){continue;}
		   if(!preg_match("#\.msg$#",$file)){continue;}
		    $count++;
		    if($maxscan>0){if($count>$maxscan){break;}}
		  	$array[$file]=$file;
		  }
		if(!is_array($array)){return array();}
		@closedir($dir_handle);
		return $array;
}
function DirListsql($path){
	$dir_handle = @opendir($path);
	if(!$dir_handle){
		write_syslog("Unable to open \"$path\"",__FILE__);
		return array();
	}
		$count=0;	
		while ($file = readdir($dir_handle)) {
		  if($file=='.'){continue;}
		  if($file=='..'){continue;}
		  if(!is_file("$path/$file")){continue;}
		   if(!preg_match("#\.sql$#",$file)){continue;}
		  	$array[$file]=$file;
		  }
		if(!is_array($array)){return array();}
		@closedir($dir_handle);
		return $array;
}

function DirList($path){
	$dir_handle = @opendir($path);
	$array=array();
	if(!$dir_handle){
		if($GLOBALS["VERBOSE"]){write_syslog("Unable to open \"$path\"",__FILE__);}
		return array();
	}
$count=0;	
while ($file = readdir($dir_handle)) {
  if($file=='.'){continue;}
  if($file=='..'){continue;}
  if(!is_file("$path/$file")){continue;}
  	$array[$file]=$file;
  }
	if(!is_array($array)){return array();}
	@closedir($dir_handle);
	return $array;
}

function amavis_logger($fullpath){
	$q=new mysql();
	
	$ini=new iniFrameWork($fullpath);
	
	$message_id=$ini->_params["TIME"]["message-id"];
	$time_amavis=$ini->_params["TIME"]["time_amavis"];
	$smtp_sender=$ini->_params["TIME"]["server_from"];
	$mailfrom=$ini->_params["TIME"]["mailfrom"];
	$mailto=$ini->_params["TIME"]["mailto"];
	$Country=$ini->_params["TIME"]["Country"];
	$Region=$ini->_params["TIME"]["Region"];
	$City=$ini->_params["TIME"]["City"];
	$kas=$ini->_params["TIME"]["kas"];
	$banned=$ini->_params["TIME"]["banned"];
	$infected=$ini->_params["TIME"]["infected"];
	$spammy=$ini->_params["TIME"]["spammy"];
	$spam=$ini->_params["TIME"]["spam"];
	$blacklisted=$ini->_params["TIME"]["blacklisted"];
	$whitelisted=$ini->_params["TIME"]["whitelisted"];
	$size=$ini->_params["TIME"]["size"];
	$subject=trim($ini->_params["TIME"]["subject"]);
	events("amavis_logger():: ". basename($fullpath)." ($message_id) from=<$mailfrom> to=<$mailto>");
	$Region=trim(str_replace("'",'`',$Region));
	$Country=trim(str_replace("'",'`',$Country));
	$City=trim(str_replace("'",'`',$City));
	
	if($Country==null){
		$array_geo=GeoIP($smtp_sender);
		$Country=$array_geo[0];
		$City=$array_geo[1];
		$City=addslashes($City);
		$Country=addslashes($Country);		
	}
	
	
	if(preg_match('#(.+?)@(.+)#',$mailfrom,$re)){$domain_from=$re[2];}

	if(!is_numeric($whitelisted)){$whitelisted=0;}
	if(!is_numeric($blacklisted)){$blacklisted=0;}
	if(!is_numeric($kas)){$kas=0;}
	if(!is_numeric($banned)){$banned=0;}
	if(!is_numeric($infected)){$infected=0;}
	if(!is_numeric($spammy)){$spammy=0;}
	if(!is_numeric($spam)){$spam=0;}
	if(!is_numeric($size)){$size=0;}
	

	$mailto=str_replace("'","",$mailto);
	$mailfrom=str_replace("'",'',$mailfrom);
	
	$mailto=$mailto.",";
	$mailto_array=explode(",",$mailto);
	if(!is_array($mailto_array)){return null;}
	//mb_internal_encoding("UTF-8");
	//$subject = mb_decode_mimeheader($subject); 	
	
	
	events("amavis_logger():: Delete id <$message_id> in mysql");
	if($message_id<>null){deleteid_from_messageid($message_id,$q);}
	if($message_id==null){$message_id=md5($ini->toString());}
	events("amavis_logger():: Start loop for Recipients number=".count($mailto_array)." id=<$message_id>");
	
	$bounce_error=null;
	if($bounce_error==null){if($infected==1){$bounce_error="INFECTED";}}
	if($bounce_error==null){if($banned==1){$bounce_error="BANNED";}}
	if($bounce_error==null){if($spammy==1){$bounce_error="SPAM";}}
	if($bounce_error==null){if($spam==1){$bounce_error="SPAM";}}
	if($bounce_error==null){if($blacklisted==1){$bounce_error="BLACKLISTED";}}
	if($bounce_error==null){if($whitelisted==1){$bounce_error="WHITELISTED";}}
	if($bounce_error==null){if($kas>90){$bounce_error="KAS3";}}
	if($bounce_error==null){$bounce_error="PASS";}

	$prefix="INSERT INTO amavis_event (`from`,`from_domain`,`to`,`to_domain`,`subject`,`size`,`bounce_error`,`country`,`city`,`zDate`,`ipaddr`) VALUES";
	$subject=addslashes($subject);
	$inserted_number=0;
    foreach ($mailto_array as $num=>$destataire){
		$destataire=trim($destataire);
		if($message_id==null){continue;}
		if($destataire==null){continue;}
		if(preg_match('#(.+?)@(.+)#',$destataire,$re)){$domain_to=$re[2];}	
		
		
		$inserted_number++;
		events("amavis_logger():: $time_amavis $message_id rcpt=<$destataire> From=<$mailfrom> $bounce_error Geo:$Country/$City");
		$f[]="('$mailfrom','$domain_from','$destataire','$domain_to','$subject','$size','$bounce_error','$Country','$City','$time_amavis','$smtp_sender')";

	}
	$sql=$prefix." ".@implode(",",$f);
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){events("amavis_logger():: FAILED $sql");return null;}
	events("amavis_logger():: DELETE $fullpath");
	if(!@unlink("$fullpath")){events("amavis_logger():: WARNING UNABLE TO DELETE ".basename($fullpath));}
	
	
	
}
function events($text){
		$pid=getmypid();
		$date=date('H:i:s');
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.sql.debug";
		$size=filesize($logFile);
		if($size>5000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid] $text\n");
		@fclose($f);	
		}
		
		
function events_cnx($text){
		$pid=getmypid();
		$date=date('H:i:s');
		$logFile="{$GLOBALS["ARTICALOGDIR"]}/postfix-logger.cnx.sql.debug";
		$size=filesize($logFile);
		if($size>5000000){unlink($logFile);}
		$f = @fopen($logFile, 'a');
		if($GLOBALS["VERBOSE"]){echo "$date [$pid] $text\n";}
		@fwrite($f, "$date [$pid] $text\n");
		@fclose($f);	
		}		
		
function optimizetable(){
	$file="/etc/artica-postfix/table.smtp.logs.optimize";
	$filetime=intval(file_time_min($file));
	events("optimizetable:: $file=$filetime mn");
	if($filetime<2880){
		events("optimizetable:: Need to wait 2880Mn");
		return null;
	}	
	@unlink($file);
	file_put_contents($file,date("y-m-d H:i:s"));
	$q=new mysql();	
	events("OPTIMIZE TABLE");
	$sql="OPTIMIZE TABLE `smtp_logs`";
	$q->QUERY_SQL($sql,"artica_events");
	$sql="OPTIMIZE TABLE `postqueue`";
	$q->QUERY_SQL($sql,"artica_events");	
	events("OPTIMIZE TABLE DONE");
	
}


function CheckPostfixLogs(){
	$log_path=LOCATE_MAILLOG_PATH();
	$unix=new unix();
	if(!is_file($log_path)){events("CheckPostfixLogs(): Cannot found log path");return null;}
	$size=filesize($log_path);
	$size=$size/1024;
	$size=$size/1000;
	
	events("CheckPostfixLogs():$log_path=$size MB");
	
	if($size==0){
		events("CheckPostfixLogs():Restarting postfix");
		$unix->RESTART_SYSLOG();
		$unix->send_email_events("Postfix will be restarted","Line: ". __LINE__."\nLog path:$log_path, Size=0\nIn order to rebuild the log file","postfix");
		shell_exec("/etc/init.d/postfix restart-single");
	}
	
	
}

function connexion_errors_stats(){
	
	
	
	
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if($unix->process_exists(@file_get_contents($pidpath))){
		events_cnx(basename(__FILE__).":".__FUNCTION__."::Already executed.. PID: ". @file_get_contents($pidpath). " aborting the process",basename(__FILE__),__FILE__,__LINE__);
		exit();
	}	
	
	
	
	$timefile="/etc/artica-postfix/pids/connexion_errors_stats.time";
	
	if($unix->file_time_min($timefile)>60){
		@unlink($timefile);
		@file_put_contents($timefile,"#");
	}else{
		events_cnx(basename(__FILE__).":".__FUNCTION__.":: need to wait 60mn");
		return;
	}
	
	$sql="SELECT COUNT( zmd5 ) AS tcount, WEEK( zDate ) AS tweek, DAY( zDate ) AS tday, HOUR( zDate ) AS thour, hostname, ipaddr, smtp_err, 
	DATE_FORMAT( zDate, '%Y-%m-%d' ) AS tdate
	FROM mail_con_err
	WHERE zDate <= DATE_SUB( NOW( ) , INTERVAL 1 HOUR )
	GROUP BY hostname, ipaddr, smtp_err";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	$count=mysqli_num_rows($results);
	
	
	if(!$q->ok){echo $q->mysql_error;return;}
	$count=mysqli_num_rows($results);
	echo $count." rows\n";
	
	if($count==0){return;}	
	
	
	$prefix="INSERT INTO mail_con_err_stats (zmd5,conx,zweek,zday,zhour,zDate,hostname,ipaddr,smtp_err) VALUES ";
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$md5=md5("'{$ligne["tcount"]}','{$ligne["tweek"]}','{$ligne["tday"]}','{$ligne["thour"]}','{$ligne["tdate"]}','{$ligne["hostname"]}','{$ligne["ipaddr"]}','{$ligne["smtp_err"]}'");
		$f[]="('$md5','{$ligne["tcount"]}','{$ligne["tweek"]}','{$ligne["tday"]}','{$ligne["thour"]}','{$ligne["tdate"]}','{$ligne["hostname"]}','{$ligne["ipaddr"]}','{$ligne["smtp_err"]}')";
		if(count($f)>500){
			$sql=$prefix." ".@implode(",",$f);
			$q->QUERY_SQL($sql,"artica_events");
			echo "-> 500\n";
			if(!$q->ok){echo $q->mysql_error;return;}
			unset($f);
		}
		
	}	
	
	if(count($f)>0){
		echo "-> ".count($f)."\n";
		$sql=$prefix." ".@implode(",",$f);
		$q->QUERY_SQL($sql,"artica_events");
		unset($f);	
	}
	
	$sql="DELETE FROM mail_con_err WHERE zDate <= DATE_SUB( NOW( ) , INTERVAL 1 HOUR )";
	$q->QUERY_SQL($sql,"artica_events");
	$sql="OPTIMIZE TABLE mail_con_err_stats";
	$q->QUERY_SQL($sql,"artica_events");
	$sql="OPTIMIZE TABLE mail_con_err";
	$q->QUERY_SQL($sql,"artica_events");	
	
}


function amavis_event_hour(){
	
	$q=new mysql();
	$sql="SELECT zDate,`hour` FROM amavis_event_hours GROUP BY zDate,`hour` ORDER BY zDate,`hour` DESC LIMIT 0,1";
	
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));	
	$lastDate=trim($ligne["zDate"]);
	$lasthour=trim($ligne["hour"]);
	echo "Last Day: $lastDate - {$lasthour}H\n";
	if($lastDate<>null){
		echo "Last Day: $lastDate - {$lasthour}H\n";
		if(strlen($lasthour)==1){$lasthour="0$lasthour";}
		$lastDate_sql=" zDate >'$lastDate $lasthour:00:00' AND ";
	}	
	

$sql="SELECT COUNT( ID ) AS tcount, 
SUM( size ) AS tsize, bounce_error, HOUR( zDate ) AS thour, DATE_FORMAT( zDate, '%Y-%m-%d' ) AS tdate, from_domain, to_domain, country, ipaddr
FROM amavis_event
WHERE $lastDate_sql zDate <= DATE_SUB( NOW( ) , INTERVAL 1 HOUR )
GROUP BY bounce_error, thour, tdate, from_domain, to_domain, country, ipaddr
ORDER BY tdate, thour";

	$prefix="INSERT IGNORE INTO amavis_event_hours (zmd5,messages,size,bounce_error,domain_from,domain_to,zDate,`hour`,ipaddr,country) VALUES ";
	echo "$sql\n";
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}
	$count=mysqli_num_rows($results);
	echo $count." rows\n";
	if($count==0){return;}	
	$inserted_number=0;
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$md=null;
        foreach ($ligne as $a=>$b){$md=$md.$b;}
		$ligne["country"]=addslashes($ligne["country"]);
		$md5=md5(serialize($md));
		$line="('$md5','{$ligne["tcount"]}','{$ligne["tsize"]}','{$ligne["bounce_error"]}','{$ligne["from_domain"]}',
		'{$ligne["to_domain"]}','{$ligne["tdate"]}','{$ligne["thour"]}','{$ligne["ipaddr"]}','{$ligne["country"]}')";
		if($GLOBALS["VERBOSE"]){echo $line."\n";}
		$f[]=$line;
		$inserted_number++;
		
	if(count($f)>500){
			$sql=$prefix." ".@implode(",",$f);
			$q->QUERY_SQL($sql,"artica_events");
			echo "amavis_event_hour() -> 500\n";
			if(!$q->ok){echo $q->mysql_error;return;}
			unset($f);
		}
		
	}	
		
	if(count($f)>0){
		echo "amavis_event_hour() -> ".count($f)."\n";
		$sql=$prefix." ".@implode(",",$f);
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error;return;}
		unset($f);	
	}
	

	
	$sql="OPTIMIZE TABLE amavis_event_hours";
	$q->QUERY_SQL($sql,"artica_events");	
	$sql="OPTIMIZE TABLE amavis_event";
	$q->QUERY_SQL($sql,"artica_events");
}


function smtp_logs_day_users(){
	
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/smtp_logs_day_users.time";
	
	if($unix->file_time_min($timefile)>420){
		@unlink($timefile);
		@file_put_contents($timefile,"#");
	}else{
		echo "Wait 420Mn\n";
		return;
	}	

	$q=new mysql();
	$f=array();
	
	$sql="SELECT zDay FROM smtp_logs_day_users GROUP BY zDay ORDER BY zDay DESC LIMIT 0,1";
	$ligne=@mysqli_fetch_array($q->QUERY_SQL($sql,'artica_events'));	
	$lastDate=trim($ligne["zDay"]);
	
	if($lastDate<>null){
		echo "Last Day: $lastDate\n";
		$lastDate_sql=" DATE_FORMAT( time_connect, '%Y-%m-%d' ) >'$lastDate' AND ";
	}
	
	
	
	$sql="SELECT COUNT( ID ) AS hits, SUM( bytes ) AS size, sender_user, sender_domain, delivery_user, delivery_domain, smtp_sender, DATE_FORMAT( time_connect, '%Y-%m-%d' ) AS tday
		FROM smtp_logs
		WHERE $lastDate_sql DATE_FORMAT( time_connect, '%Y-%m-%d' ) < DATE_FORMAT( NOW( ) , '%Y-%m-%d' )
		GROUP BY sender_user, sender_domain, delivery_user, delivery_domain, smtp_sender, tday
		ORDER BY tday DESC";

	$prefix="INSERT IGNORE INTO smtp_logs_day_users (zmd5,hits,size,sender_user,sender_domain,recipient_user,recipient_domain,zDay,ipaddr) VALUES ";
	
	$results=$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error;return;}
	$count=mysqli_num_rows($results);
	echo $count." rows\n";
	if($count==0){return;}	
		
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
	$md5=md5("'{$ligne["hits"]}','{$ligne["size"]}','{$ligne["sender_user"]}','{$ligne["sender_domain"]}','{$ligne["delivery_user"]}','{$ligne["delivery_domain"]}','{$ligne["tday"]}','{$ligne["smtp_sender"]}'");		
	$f[]="('$md5','{$ligne["hits"]}','{$ligne["size"]}','{$ligne["sender_user"]}','{$ligne["sender_domain"]}','{$ligne["delivery_user"]}','{$ligne["delivery_domain"]}','{$ligne["tday"]}','{$ligne["smtp_sender"]}')";
	
	if(count($f)>500){
			$sql=$prefix." ".@implode(",",$f);
			$q->QUERY_SQL($sql,"artica_events");
			echo "-> 500\n";
			if(!$q->ok){echo $q->mysql_error;return;}
			unset($f);
		}
		
	}	
		
	if(count($f)>0){
		echo "-> ".count($f)."\n";
		$sql=$prefix." ".@implode(",",$f);
		$q->QUERY_SQL($sql,"artica_events");
		unset($f);	
	}
	
	$sql="OPTIMIZE TABLE smtp_logs";
	$q->QUERY_SQL($sql,"artica_events");	
	$sql="OPTIMIZE TABLE smtp_logs_day_users";
	$q->QUERY_SQL($sql,"artica_events");	
}


function postqueue(){
	
		$unix=new unix();
		if(!$GLOBALS["FORCE"]){
			$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
			$pid=@file_get_contents($pidfile);
			if($unix->process_exists($pid)){echo __FUNCTION__." already executed pid $pid\n";return;}
			@file_put_contents($pidfile,getmypid());
		}

		if(system_is_overloaded()){return;}
		
		
		$DirPath="{$GLOBALS["ARTICALOGDIR"]}/postqueue";
		if (!$handle = opendir($DirPath)) {if($GLOBALS["VERBOSE"]){echo "$DirPath ERROR\n";}return;}
		$c=0;
		while (false !== ($file = readdir($handle))) {
			if ($file == "."){continue;}
			if ($file == ".."){continue;}
			if(is_dir("$DirPath/$file")){if($GLOBALS["VERBOSE"]){echo "$DirPath/$file -> DIR\n";} continue;}
			$filename="$DirPath/$file";
			$time=$unix->file_time_min($filename);
			if($time>180){@unlink($filename);continue;}
			$c++;
		}
		if($c==0){return;}

		$q=new mysql();
		$q->QUERY_SQL("truncate table postqueue","artica_events");
				
		if($GLOBALS["VERBOSE"]){echo "Scanning $DirPath\n";}
		
		if (!$handle = opendir($DirPath)) {if($GLOBALS["VERBOSE"]){echo "$DirPath ERROR\n";}return;}
		$c=0;
		while (false !== ($file = readdir($handle))) {
			if ($file == "."){continue;}
			if ($file == ".."){continue;}
			if(is_dir("$DirPath/$file")){if($GLOBALS["VERBOSE"]){echo "$DirPath/$file -> DIR\n";} continue;}
			$filename="$DirPath/$file";
			if($GLOBALS["VERBOSE"]){echo "Scanning $filename\n";}
			if(postqueue_parse($filename)){
				events("postqueue():: Success parsing ".basename($filename));
				@unlink($filename);
			}
		}
		
	shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.watchdog.postfix.queue.php >/dev/null 2>&1 &");		
		
}
function postqueue_parse($filename){
	$array=unserialize(@file_get_contents($filename));
	if(!is_array($array)){
		echo $filename." Not an array() !\n";
		@unlink($filename);
		return;
	}
	
	
	$q=new mysql();
	if(!is_array($array["LIST"])){return;}
	
	
	foreach ($array["LIST"] as $instance=>$array_content){
        foreach ($array_content as $msgid=>$content){
			if(trim($msgid)==null){continue;}
			if(trim($msgid)=="FROM_DOMAIN"){continue;}
			if(trim($msgid)=="STATUS"){continue;}
			
			
			$from_domain=null;
			if(preg_match("#(.+?)@(.+)#",$content["FROM"],$re)){
				$from_domain=$re[2];
			}
			$context=null;
			if(isset($content["STATUS"])){
				if(preg_match("#Connection timed out#",$content["STATUS"])){$context="Timed out";}
				if(preg_match("#Host not found#",$content["STATUS"])){$context="Host not found";}
				if(preg_match("#Access denied#",$content["STATUS"])){$context="Access denied";}
				if(preg_match("#Domain not found#",$content["STATUS"])){$context="Domain not found";}
				if(preg_match("#unable to verify recipient#",$content["STATUS"])){$context="unable to verify recipient";}
				if(preg_match("#connect to 127\.0\.0\.1.+?:10024.+?Connection refused#",$content["STATUS"])){$context="Connection refused (amavis)";}
				if(preg_match("#Connection refused#",$content["STATUS"])){$context="Connection refused";}
				if(preg_match("#Greylisting#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#said: 451 Try again later#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#lost connection#",$content["STATUS"])){$context="lost connection";}
				if(preg_match("#timeout exceeded#",$content["STATUS"])){$context="Timed out";}
				if(preg_match("#timed out while#",$content["STATUS"])){$context="Timed out";}
				if(preg_match("#451 Timeout#",$content["STATUS"])){$context="Timed out";}
				if(preg_match("#service timed out#",$content["STATUS"])){$context="Timed out";}
				if(preg_match("#Temporary lookup failure#",$content["STATUS"])){$context="lookup failure";}
				if(preg_match("#local error#",$content["STATUS"])){$context="local error";}
				if(preg_match("#We have limits for how many messages can be sent per hour and per day#",$content["STATUS"])){$context="limit exceed";}
				if(preg_match("#Temporary recipient validation error#",$content["STATUS"])){$context="recipient validation error";}
				if(preg_match("#451 Try again later#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#451 Temporary local problem#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#refused to talk to me.+?Service Unavailable#",$content["STATUS"])){$context="refused to talk";}
				if(preg_match("#refused to talk to me.+?concurrent connections has exceeded a limit#",$content["STATUS"])){$context="Too Much connections";}
				if(preg_match("#refused to talk to me#",$content["STATUS"])){$context="refused to talk";}
				if(preg_match("#Already reached per#",$content["STATUS"])){$context="Too Much connections";}
				if(preg_match("#Network is unreachable#",$content["STATUS"])){$context="Bad Network";}
				if(preg_match("#Greylisted#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#temporary blocked#",$content["STATUS"])){$context="temporary blocked";}
				if(preg_match("#temporary problem#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#mailbox is full#",$content["STATUS"])){$context="Mailbox full";}
				if(preg_match("#127\.0\.0\.1.+?said.+?Service shutting down#",$content["STATUS"])){$context="Filter restarting";}
				if(preg_match("#Error in processing, id=.+?virus_scan FAILED#",$content["STATUS"])){$context="Antivirus failed";}
				if(preg_match("#exceed mailbox quota#",$content["STATUS"])){$context="Mailbox full";}
				if(preg_match("#Out of memory#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#Domain not active#",$content["STATUS"])){$context="Domain not active";}
				if(preg_match("#MailBox quota excedeed#",$content["STATUS"])){$context="Mailbox full";}
				if(preg_match("#Policy Rejection.+?Quota Exceeded#",$content["STATUS"])){$context="Quota Exceeded";}
				if(preg_match("#forwarding FAILED: Error writing to socket#",$content["STATUS"])){$context="Socket Error";}
				if(preg_match("#said.+?mailbox temporarily disabled#",$content["STATUS"])){$context="Mailbox inactive";}
				if(preg_match("#\(RLY\:NW\)\s+http#",$content["STATUS"])){$context="limit exceed";}
				if(preg_match("#said: 450 MI:IPB http#",$content["STATUS"])){$context="refused to talk";}
				if(preg_match("#temporarily deferred due to user complaints#",$content["STATUS"])){$context="user complaints";}
				if(preg_match("#unsolicited mail originating from your IP address#",$content["STATUS"])){$context="user complaints";}
				if(preg_match("#unverified address: unknown user#",$content["STATUS"])){$context="unable to verify recipient";}
				if(preg_match("#421-ts01\.html#",$content["STATUS"])){$context="user complaints";}
				if(preg_match("#Bad Network#",$content["STATUS"])){$context="Bad Network";}
				if(preg_match("#No route to host#",$content["STATUS"])){$context="No route to host";}
				if(preg_match("#Access denied by DCC#",$content["STATUS"])){$context="You are a spammer";}
				if(preg_match("#Trend Micro Network Reputation Service#",$content["STATUS"])){$context="You are a spammer";}
				if(preg_match("#Trend Micro Email Reputation Service#",$content["STATUS"])){$context="You are a spammer";}
				if(preg_match("#Trend Micro Email Reputation database#",$content["STATUS"])){$context="You are a spammer";}
				if(preg_match("#barracudanetworks#",$content["STATUS"])){$context="You are a spammer";}
				if(preg_match("#host.*?451.*?can.*?connect.*?psmtp#i", $content["STATUS"])){$context="Remote PSMTP error";}
				
				if(preg_match("#451 qqt failure#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#qq read error#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#Temporary_Failure#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#Insufficient system#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#unable to read controls#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#temporary local problem#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#user complaints#",$content["STATUS"])){$context="user complaints";}
				if(preg_match("#out of quota#",$content["STATUS"])){$context="Quota Exceeded";}
				if(preg_match("#No space left on device#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#Can't create temporary mail file#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#human verification before permitting delivery#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#User unknown in local recipient table#",$content["STATUS"])){$context="recipient validation error";}
				if(preg_match("#lowest numbered MX record points to local host#",$content["STATUS"])){$context="Cannot resolve your domain";}
				if(preg_match("#is receiving mail at a rate that 450#",$content["STATUS"])){$context="limit exceed";}
				if(preg_match("#mailbox unavailable#",$content["STATUS"])){$context="recipient validation error";}
				if(preg_match("#Please try again later#",$content["STATUS"])){$context="Greylisting";}
				if(strpos($content["STATUS"], "(DYN:T1)")>0){$context="You are a spammer";}
				if(preg_match("#permanently rejected this message as Spam#",$content["STATUS"])){$context="You are a spammer";}
				if(preg_match("#exceed quota for#",$content["STATUS"])){$context="Quota Exceeded";}
				if(preg_match("#User unknown#",$content["STATUS"])){$context="recipient validation error";}
				if(preg_match("#Recipient address rejected#",$content["STATUS"])){$context="recipient validation error";}
				if(preg_match("#Domain of sender .+? does not resolve#",$content["STATUS"])){$context="Cannot resolve your domain";}
				if(preg_match("#Try again later#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#Authentication required#",$content["STATUS"])){$context="Authentication required";}
				if(preg_match("#Requested action not taken.+?try again#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#authorized.+?Please try later#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#greylisted for#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#please try again [0-9]+#",$content["STATUS"])){$context="Greylisting";}
				if(preg_match("#Too many recipients received#",$content["STATUS"])){$context="limit exceed";}
				if(preg_match("#delivery temporarily suspended.*?No route to host#",$content["STATUS"])){$context="Remote problem";}
				if(preg_match("#delivery temporarily suspended.*?10024: Connection refused#",$content["STATUS"])){$context="Amavis out-of-service";}
				if(preg_match("#Your subject had too many subsequent spaces#",$content["STATUS"])){$context="Header regex rule";}
				if(preg_match("#Too much Spam from your domain \!#",$content["STATUS"])){$context="Header regex rule";}
				if(preg_match("#Message content rejected - No spam please#",$content["STATUS"])){$context="Header regex rule";}
				if(preg_match("#Connection timed out#i", $content["STATUS"])){$context="Timed out";}
				if(preg_match("#451.*?Server configuration problem#i", $content["STATUS"])){$context="Remote problem";}
				
				
			if($context==null){
				if($GLOBALS["VERBOSE"]){echo "\n\nnot filtered {$content["STATUS"]}\n";}
				$context=addslashes($content["STATUS"]);}
				$content["STATUS"]=addslashes($content["STATUS"]);				
			}else{
				if($content["ACTIVE"]=="YES"){$context="Active queue";}
			}
			$content["FROM"]=addslashes($content["FROM"]);
			$to=addslashes(@implode(",",$content["TO"]));
			$prefix="INSERT IGNORE INTO postqueue (`msgid`,`zDate` ,`from`,`recipients`,`context`,`event`,`from_domain`,`size`,`instance`) VALUES ";
			$instance=trim($instance);
			if($GLOBALS["VERBOSE"]){
				echo "[$msgid] from=<{$content["FROM"]}> to=<". @implode(",",$content["TO"])."> status={$content["STATUS"]}\n";
			}
			if(substr($instance, 0,1)=="-"){$instance=substr($instance, 1,strlen($instance));}
			$suffix[]="('$msgid','{$content["timestamp"]}','{$content["FROM"]}','$to','$context','{$content["STATUS"]}','$from_domain','{$content["msgsize"]}','$instance')";

			if(count($suffix)>100){
				$sql=$prefix.@implode(",",$suffix);
				$q->QUERY_SQL($sql,"artica_events");
				if(!$q->ok){echo $q->mysql_error."\n\n\n\n";return false;}
				$suffix=array();
			}
			

			
		}
		
	}
	
	if(count($suffix)>0){
		$sql=$prefix.@implode(",",$suffix);
		if($GLOBALS["VERBOSE"]){echo $sql."\n";}
		$q->QUERY_SQL($sql,"artica_events");
		if(!$q->ok){echo $q->mysql_error."\n\n\n\n";return false;}
		$suffix=array();
	}	
	
	return true;
	}


function CleanQueues(){
    $EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
    if($EnablePostfix==0){die();}

	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
		
		if(!$GLOBALS["FORCE"]){
			$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
			$pid=@file_get_contents($pidfile);
			if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
				echo "CleanQueues() already executed pid $pid\n";
				return;
			}
		
			@file_put_contents($pidfile,getmypid());
		}


        $q=new lib_sqlite("/home/artica/SQLITE/postqueue.db");
        if(!$q->TABLE_EXISTS("postqueue")){
            $sql="CREATE TABLE IF NOT EXISTS `postqueue` (
			  `msgid` TEXT PRIMARY KEY NOT NULL,
			  `instance` TEXT NOT NULL,
			  `zDate` TEXT NOT NULL,
			  `from` TEXT NOT NULL,
			  `recipients` TEXT NOT NULL,
			  `context` TEXT NOT NULL,
			  `event` TEXT NOT NULL,
			  `removed` INTEGER NOT NULL DEFAULT '0',
			  `from_domain` TEXT NOT NULL,
			  `size` INTEGER NOT NULL)";
            $q->QUERY_SQL($sql);
            $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_context ON postqueue (context,removed)");
            $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_from_domain ON postqueue (from_domain,`from`)");
            $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_zDate ON postqueue (zDate)");
            $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_instance ON postqueue (instance)");
            $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_size ON postqueue (size)");
            $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_recipients ON postqueue (recipients)");

        }
	
	
		$sql="SELECT * FROM postqueue ORDER BY zDate";

		$results=$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";return;}
		$count=count($results);
		if($count==0){
			if($GLOBALS["VERBOSE"]){echo "No line\n";}
			return;
		}	
		$postcat=$GLOBALS["CLASS_UNIX"]->find_program("postcat");
		$c='';
	foreach($results as $index=>$ligne){
		$hostname=$ligne["instance"];
		if($hostname<>"master"){$c=" -c /etc/postfix-$hostname";}
		$msgid=$ligne["msgid"];
		$results2=array();
		if($GLOBALS["VERBOSE"]){echo "Check \"$msgid\"\n";}
		
		exec("$postcat -qh $msgid $c 2>&1",$results2);
		
		if(preg_match("#No such file#",$results2[0])){
			if($GLOBALS["VERBOSE"]){echo "$msgid DIE\n";}
			$sql="DELETE FROM postqueue WHERE msgid='$msgid'";
			$q->QUERY_SQL($sql,"artica_events");
			if(!$q->ok){
				if($GLOBALS["VERBOSE"]){echo "Error \"$q->mysql_error\"\n";}
			}
			continue;	
		}
		
		if($GLOBALS["VERBOSE"]){echo "$msgid LIVE\n";}
	}
	
	$GLOBALS["CLASS_UNIX"]->THREAD_COMMAND_SET(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.postfix.vip.php --queue");
	
}

function GeoIP($site_IP){
	if(!function_exists("geoip_record_by_name")){
		if($GLOBALS["VERBOSE"]){echo "geoip_record_by_name no such function\n";}
		return array();
	}
	
	if($site_IP==null){
		events("GeoIP():: $site_IP is Null");
		return array();}
	if(!preg_match("#[0-9]+\.[0-9]+\.[0-9]+#",$site_IP)){
		events("GeoIP():: $site_IP ->gethostbyname()");
		$site_IP=gethostbyname($site_IP);
		events("GeoIP():: $site_IP");
	}
	
	
	if(isset($GLOBALS["COUNTRIES"][$site_IP])){
		events("GeoIP():: $site_IP {$GLOBALS["COUNTRIES"][$site_IP]}/{$GLOBALS["CITIES"][$site_IP]}");
		if($GLOBALS["VERBOSE"]){echo "$site_IP:: MEM={$GLOBALS["COUNTRIES"][$site_IP]}\n";}
		return array($GLOBALS["COUNTRIES"][$site_IP],$GLOBALS["CITIES"][$site_IP]);
	}
	
	$record = geoip_record_by_name($site_IP);
	if ($record) {
		$Country=$record["country_name"];
		$city=$record["city"];
		$GLOBALS["COUNTRIES"][$site_IP]=$Country;
		$GLOBALS["CITIES"][$site_IP]=$city;
		events("GeoIP():: $site_IP $Country/$city");
		return array($GLOBALS["COUNTRIES"][$site_IP],$GLOBALS["CITIES"][$site_IP]);
	}else{
		events("GeoIP():: $site_IP No record");
		if($GLOBALS["VERBOSE"]){echo "$site_IP:: No record\n";}
		return array();
	}
		
	return array();
}

function DebugGeo():bool{
    $conf["geoip_continent_code_by_name"]="Get the two letter continent code";
    $conf["geoip_country_code_by_name"]="Get the two letter country code";
    $conf["geoip_country_code3_by_name"]="Get the three letter country code";
    $conf["geoip_country_name_by_name"]="Get the full country name";
    $conf["geoip_database_info"]="Get GeoIP Database information";
    $conf["geoip_db_avail"]="Determine if GeoIP Database is available";
    $conf["geoip_db_filename"]="Returns the filename of the corresponding GeoIP Database";
    $conf["geoip_db_get_all_info"]="Returns detailed information about all GeoIP database types";
    $conf["geoip_id_by_name"]="Get the Internet connection type";
    $conf["geoip_isp_by_name"]="Get the Internet Service Provider (ISP) name";
    $conf["geoip_org_by_name"]="Get the organization name";
    $conf["geoip_record_by_name"]="Returns the detailed City information found in the GeoIP Database";
    $conf["geoip_region_by_name"]="Get the country code and region";
    $conf["geoip_region_name_by_code"]="Returns the region name for some country and region code combo";
    $conf["geoip_time_zone_by_country_and_region"]="Returns the time zone for some country and region code combo";
        foreach ($conf as $num=>$ligne){
            if(!function_exists($num)){
                echo "Failed: $num \"$ligne\"\n";
            }else{
                echo "Success: $num \"$ligne\"\n";
            }
        }
    return true;

}


?>