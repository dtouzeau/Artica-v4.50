<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.sqlite.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){
	$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); 
	if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } 
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["CLASS_UNIX"]=new unix();
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_FAMILY"]=new squid_familysite();

$pidfile="/var/run/dnsfilterd-tail.pid";
$pid=getmypid();
$pid=@file_get_contents($pidfile);
events("Found old PID $pid");
if($pid<>$pid){
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid,basename(__FILE__))){events("Already executed PID: $pid.. aborting the process");exit();}
}

file_put_contents($pidfile,$pid);
events("ufdbtail starting PID $pid...");
$GLOBALS["ufdbGenTable"]=$GLOBALS["CLASS_UNIX"]->find_program("ufdbGenTable");
$GLOBALS["chown"]=$GLOBALS["CLASS_UNIX"]->find_program("chown");
$GLOBALS["nohup"]=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
$GLOBALS["PHP5_BIN"]=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
$GLOBALS["SBIN_ARP"]=$GLOBALS["CLASS_UNIX"]->find_program("arp");
$GLOBALS["SBIN_ARPING"]=$GLOBALS["CLASS_UNIX"]->find_program("arping");
$GLOBALS["SBIN_RM"]=$GLOBALS["CLASS_UNIX"]->find_program("rm");

$GLOBALS["RELOADCMD"]="{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --reload";
if($argv[1]=='--date'){echo date("Y-m-d H:i:s")."\n";}
ToSyslog("DNS Filter Service Watchdog started pid $pid");
events("ufdbGenTable = {$GLOBALS["ufdbGenTable"]}");




$pipe = fopen("php://stdin", "r");
while(!feof($pipe)){
	$buffer .= fgets($pipe, 4096);
	try {Parseline($buffer);}
	catch(Exception $e){squid_admin_mysql(0, "Fatal error on $buffer: ".$e->getMessage(),"MAIN",__FILE__,__LINE__,"ufdbguard-service");}
	$buffer=null;
}

fclose($pipe);

events_ufdb_exec("Artica ufdb-tail shutdown");
events("Shutdown...");
exit();



function Parseline($buffer){
$buffer=trim($buffer);
if(trim($buffer)==null){return;}
if($buffer==null){return null;}
$mdbuff=md5($buffer);
if(isset($GLOBALS['MDBUFF'][$mdbuff])){return;}
$GLOBALS['MDBUFF'][$mdbuff]=true;
if(count($GLOBALS['MDBUFF'])>1000){$GLOBALS['MDBUFF']=array();}

if(strpos($buffer,"using rwlock for")>0){return ;}
if(strpos($buffer,"] PASS ")>0){return ;}
if(strpos($buffer,"UFDBinitHTTPSchecker")>0){return ;}
if(strpos($buffer,"IP socket port")>0){return ;}
if(strpos($buffer,"listening on interface")>0){return ;}
if(strpos($buffer,"yielding")>0){return ;}
if(strpos($buffer,"system:")>0){return ;}
if(strpos($buffer,"URL verification threads and")>0){return ;}
if(strpos($buffer,"worker threads")>0){return ;}
if(strpos($buffer,"license status")>0){return ;}
if(strpos($buffer,"redirect-fatal-error")>0){return ;}
if(strpos($buffer,"using OpenSSL library")>0){return ;}
if(strpos($buffer,"CA certificates are")>0){return ;}
if(strpos($buffer,"Failure to load the CA database")>0){return ;}
if(strpos($buffer,"CA file is")>0){return ;}
if(strpos($buffer,"ufdbHandleAlarmForTimeEvents")>0){return ;}
if(strpos($buffer,"Changing daemon status")>0){return ;}
if(strpos($buffer,"UFDBchangeStatus")>0){return ;}
if(strpos($buffer,"url-lookup-delay-during-database-reload")>0){return ;}
if(strpos($buffer,"url-lookup-result-during-database-reload")>0){return ;}
if(strpos($buffer,"url-lookup-result-when-fatal-error")>0){return ;}
if(strpos($buffer,"no http-server")>0){return ;}
if(strpos($buffer,"upload-stats")>0){return ;}
if(strpos($buffer,"analyse-uncategorised-urls")>0){return ;}
if(strpos($buffer,"redirect-loading-database")>0){return ;}
if(strpos($buffer,"ufdb-expression-debug")>0){return ;}
if(strpos($buffer,"ufdb-debug-filter")>0){return ;}
if(strpos($buffer,"database status: up to date")>0){return ;}
if(strpos($buffer,"ufdbGenTable should be called with the")>0){return ;}
if(strpos($buffer,"is deprecated and ignored")>0){return ;}
if(strpos($buffer,"init domainlist")>0){return ;}
if(strpos($buffer,"is empty !")>0){return ;}
if(strpos($buffer,"init expressionlist")>0){return ;}
if(strpos($buffer,"is optimised to one expression")>0){return ;}
if(strpos($buffer,"be analysed since there is no proper database")>0){return ;}
if(strpos($buffer,"REDIRECT 302")>0){return ;}
if(strpos($buffer,"close fd")>0){return ;}
if(strpos($buffer,": open fd ")>0){return ;}
if(strpos($buffer,"acl {")>0){return ;}
if(strpos($buffer,"URL verifications")>0){return ;}
if(strpos($buffer,"must be part of the security")>0){return ;}
if(strpos($buffer,"}")>0){return ;}
if(strpos($buffer,"finished retrieving")>0){return ;}

if(strpos($buffer,"loading URL table from")>0){return ;}
if(strpos($buffer,"]    option")>0){return ;}
if(strpos($buffer,"{")>0){return ;}
if(strpos($buffer,"] category \"")>0){return ;}
if(strpos($buffer,"]    domainlist     \"")>0){return ;}
if(strpos($buffer,"]       pass ")>0){return ;}
if(strpos($buffer,"] safe-search")>0){return ;}
if(strpos($buffer,"configuration file")>0){return ;}
if(strpos($buffer,"refreshdomainlist")>0){return ;}
if(strpos($buffer,"software suite is free and Open Source Software")>0){return ;}
if(strpos($buffer,"by URLfilterDB")>0){return ;}
if(strpos($buffer,"] configuration status")>0){return ;}
if(strpos($buffer,'expressionlist "')>0){return ;}
if(strpos($buffer,'is newer than')>0){return ;}
if(strpos($buffer,'source "')>0){return ;}
if(strpos($buffer,'youtube-edufilter-id')>0){return ;}
if(strpos($buffer,'max-logfile-size')>0){return ;}
if(strpos($buffer,'check-proxy-tunnels')>0){return ;}
if(strpos($buffer,'seconds to allow worker')>0){return ;}
if(strpos($buffer,'] loading URL category')>0){return ;}
if(preg_match("#\] REDIR\s+#", $buffer)){return;}
if(strpos($buffer,'execdomainlist for')>0){return ;}
if(strpos($buffer,'dynamic_domainlist_updater_main')>0){return ;}
if(strpos($buffer,']    redirect       "http')>0){return ;}
if(strpos($buffer,'] ufdb-debug-')>0){return ;}

if(preg_match("#BLOCK\s+(.*?)\s+(.+?)\s+(.*?)\s+(.+?)\s+(.+?)\s+[A-Z]+#", $buffer,$re)){
	$category=0;
	$ip=$re[2];
	$rule=$re[3];
	$categoryT=$re[4];
	$url=$re[5];
	if(preg_match("#P([0-9]+)#", $categoryT,$ri)){$category=$ri[1];}
	$fam=new squid_familysite();
	$arrayURI=parse_url($url);
	$hostname=$arrayURI["host"];
	if(preg_match("#^(.+?):[0-9]+#", $hostname,$rz)){$hostname=$rz[1];}
	$familysite=$fam->GetFamilySites($hostname);
	if(!is_dir("/home/artica/SQLITE_DNSFILTER")){@mkdir("/home/artica/SQLITE_DNSFILTER",0755,true);}
	$q=new lib_sqlite("/home/artica/SQLITE_DNSFILTER/".date("Y-m-d-H").".db");
	$sql="CREATE TABLE IF NOT EXISTS `statistics` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`sitename` TEXT,`familysite` TEXT,`rulename` TEXT,`category` INTEGER,`ipaddr` TEXT)";
	$q->QUERY_SQL($sql);
	$sql="INSERT INTO `statistics` (`sitename`,`familysite`,`rulename`,`category`,`ipaddr`) VALUES ('$hostname','$familysite','$rule','$category','$ip')";
	$q->QUERY_SQL($sql);
	if(!$q->ok){squid_admin_mysql(0, "SQlite Error $q->mysql_error", $sql,__FILE__,__LINE__);}
	return;
}

if(preg_match("#ufdbCatchBadSignal.*?signal\s+([0-9]+)#", $buffer,$re)){
	squid_admin_mysql(0, "DNS Filter Service as crashed with ufdbCatchBadSignal N.{$re[1]} [ {action} = {restart} ]",$buffer,__FILE__,__LINE__);
	xsyslog("{restart} ufdbcat service...");
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart --force >/dev/null 2>&1 &");
	return;
}

if(preg_match("#FATAL ERROR: connection queue is full#",$buffer)){
	$TimeFile="/etc/artica-postfix/pids/webfiltering-connection.queue.full";
	if(!IfFileTime($TimeFile,5)){return;}
	$Threads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbCatThreads"));
	$ThreadNew=$Threads+5;
	if($ThreadNew>1285){$ThreadNew=1285;}
	if($ThreadNew==1285){
		squid_admin_mysql(0, "DNS Filter Service connection queue is full but max Threads ($ThreadNew) is reached [action=reload]", $buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/dnsfilterd reload --force >/dev/null 2>&1 &");
		return;
	}
	squid_admin_mysql(0, "DNS Filter Service connection queue is full increase Threads from $Threads to $ThreadNew [ {action} = {restart} ]", $buffer,__FILE__,__LINE__);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbCatThreads", $ThreadNew);
	shell_exec("{$GLOBALS["nohup"]} /etc/init.d/dnsfilterd restart --force >/dev/null 2>&1 &");
	return;
}


if(preg_match("#FATAL ERROR: cannot bind daemon socket: Cannot assign requested address#",$buffer)){
	$TimeFile="/etc/artica-postfix/pids/webfiltering-cannot-bind-daemon-socket-Cannot-assign-requested";
	if(!IfFileTime($TimeFile,5)){return;}
	squid_admin_mysql(0, "Webfiltering configuration issue on listen address [action=reconfigure]", $buffer,__FILE__,__LINE__);
	xsyslog("{reconfigure} DNS Filter Service...");
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart >/dev/null 2>&1 &");
	return;
}

if(stripos(" $buffer","HUP signal received to reload the configuration")>0){
	squid_admin_mysql(2, "DNS Filter Service was reloaded - reloading databases [action=notify]", $buffer,__FILE__,__LINE__);
	events_ufdb_exec("DNS Filter Service was reloaded, wait 15 seconds");
	return;
}

if(stripos(" $buffer","ufdbGuard daemon stopped")>0){
	squid_admin_mysql(1, "DNS Filter Service was stopped [action=notify]", $buffer,__FILE__,__LINE__);
	events_ufdb_exec("DNS Filter Service was stopped, wait 15 seconds");
	return;
}

if(stripos(" $buffer",'Changing daemon status to "started"')>0){
	squid_admin_mysql(2, "DNS Filter Service was started [action=notify]", $buffer,__FILE__,__LINE__);
	events_ufdb_exec("DNS Filter Service was started, wait 15 seconds");
	return;
}


if(preg_match("#thread socket-handler caught signal 11#",$buffer,$re)){
	squid_admin_mysql(0, "DNS Filter Service crash [ {action} = {restart} ]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart --force >/dev/null 2>&1 &");
	return;
}


if(preg_match("#Changing daemon status to \"error\"#",$buffer,$re)){
	squid_admin_mysql(0, "Restarting DNS Filter Service :$buffer", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart --force >/dev/null 2>&1 &");
	return;
}


if(preg_match("#FATAL ERROR: cannot open configuration file\s+\/ufdbGuard\.conf#i",$buffer,$re)){
	squid_admin_mysql(0, "DNS Filter Service error, Open Configuration File failed [action=restart service]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart --force --ufdbtail --fatal-error >/dev/null 2>&1 &");
	return;
}

if(preg_match("#FATAL.*?read failed on \"(.+?)\".*?Bad address#i",$buffer,$re)){
	squid_admin_mysql(0, "DNS Filter Service error on database: {$re[1]}  [action=Remove-database]", $buffer,__FILE__,__LINE__);
	@unlink("{$re[1]}.ufdb");
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart >/dev/null 2>&1 &");
	return;
}

if(preg_match("#FATAL ERROR: read failed on \"(.+?)\" n=[0-9]+ st_size=[0-9]+#",$buffer,$re)){
	squid_admin_mysql(0, "DNS Filter Service error on database: {$re[1]}  [action=Remove-database]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart >/dev/null 2>&1 &");
	return;
}


if(preg_match("#FATAL ERROR: cannot read from.*?No such file or directory#", $buffer,$re)){
	squid_admin_mysql(0, "DNS Filter Service error: a database is missing [ {action} = {restart} ]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart >/dev/null 2>&1 &");
	return;
}

if(preg_match("#pid [0-9]+ caught bad signal [0-9]+#", $buffer,$re)){
	squid_admin_mysql(0, "DNS Filter Service error: bad signal [ {action} = {restart} ]", $buffer,__FILE__,__LINE__);
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart >/dev/null 2>&1 &");
	return;
}

if(preg_match("#There are no sources and there is no default ACL#i", $buffer)){
	events("Seems not to be defined -> build compilation.");
	xsyslog("{reconfigure} DNS Filter Service...");
	shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart >/dev/null 2>&1 &");
	return;
}
	
	if(preg_match("#ERROR: cannot write to PID file\s+(.+)#i", $buffer,$re)){
		xsyslog("Apply permissions on {$re[1]}");
		$pidfile=$re[1];
		$pidpath=dirname($pidfile);
		@mkdir($pidpath,0755,true);
		@chown($pidpath,"squid");
		@chmod($pidpath,0755);
		return;
	}
	
	
	if(preg_match("#\] Changing daemon status to.*?error#",$buffer,$re)){
		squid_admin_mysql(0, "Fatal! DNS Filter Service is turned to error", $buffer,__FILE__,__LINE__);
		return;
		
	}
	
	if(preg_match("#\] Changing daemon status to.*?terminated#",$buffer,$re)){
		squid_admin_mysql(1, "DNS Filter Service is turned to OFF", $buffer,__FILE__,__LINE__);
		return;
	
	}	
	
	
	if(preg_match('#FATAL ERROR: table "(.+?)"\s+could not be parsed.*?error code = [0-9]+#',$buffer,$re)){
		$direname=dirname($re[1]);
		squid_admin_mysql(0, "Database $direname corrupted", $buffer."\nReconfigure DNS Filter Service after removing $direname...",__FILE__,__LINE__);
		events("Categories engine error on $direname");
		if(!is_dir($direname)){return;}
		shell_exec("{$GLOBALS["SBIN_RM"]} -rf $direname >/dev/null 2>&1");
		xsyslog("{reconfigure} DNS Filter Service after removing $direname...");
		shell_exec("{$GLOBALS["nohup"]} {$GLOBALS["PHP5_BIN"]} /usr/share/artica-postfix/exec.dnsfilterd.php --restart >/dev/null 2>&1 &");
		return;
	}

	if(preg_match("#BLOCK-FATAL\s+#",$buffer,$re)){
		$TimeFile="/etc/artica-postfix/pids/UFDBCAT_BLOCK_FATAL";
		if(!IfFileTime($TimeFile,10)){return;}
		events("Webfiltering engine error, reload service");
		events_ufdb_exec("service was restarted, $buffer");
		squid_admin_mysql(0, "Fatal, DNS Filter Service engine error", $buffer."\nThe service will be reloaded",__FILE__,__LINE__);
		xsyslog("Reloading DNS Filter Service...");
		squid_admin_mysql(1, "Reloading DNS Filter Service", null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/dnsfilterd reload >/dev/null 2>&1 &");
		return;
	}
	
	if(preg_match("#FATAL ERROR: connection queue is full#",$buffer,$re)){
		$TimeFile="/etc/artica-postfix/pids/UFDBCAT_QUEUE_IS_FULL";
		if(!IfFileTime($TimeFile,2)){return;}
		squid_admin_mysql(0, "Fatal, DNS Filter Service connection queue is full", $buffer."\nThe service will be restarted and threads are increased to $Threads",__FILE__,__LINE__);
		xsyslog("Restarting DNS Filter Service after connection queue is full...");
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/dnsfilterd restart >/dev/null 2>&1 &");
		return;
	}
	

	if(preg_match('#FATAL\*\s+table\s+"(.+?)"\s+could not be parsed.+?14#',$buffer,$re)){
		events("Table on {$re[1]} crashed");
		squid_admin_mysql(0, "Database {$re[1]} corrupted", $buffer,__FILE__,__LINE__);
		squid_admin_mysql(1, "Table on {$re[1]} crashed\n$buffer",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		return;		
	}
	
	if(preg_match("#FATAL ERROR: cannot bind daemon socket: Address already in use#", $buffer)){
		events_ufdb_exec("ERROR DETECTED : $buffer `cannot bind daemon socket`");
		squid_admin_mysql(1, "Fatal ERROR: cannot bind daemon socket: Address already in use [ {action} = {restart} ]", $buffer,__FILE__,__LINE__);
		squid_admin_mysql(1, "Fatal ERROR: cannot bind daemon socket: Address already in use",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
		xsyslog("Restarting DNS Filter Service...");
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/dnsfilterd restart >/dev/null 2>&1 &");
		return;
	}
	

	
	if(preg_match('#\] FATAL ERROR: cannot read from "(.+?)".*?No such file or directory#', $buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer,__FILE__,__LINE__);
		events("cannot read '{$re[1]}' -> \"$buffer\"");
		squid_admin_mysql(2,"DNS Filter Service issue on {$re[1]}","Launch recover_a_database()",__FILE__,__LINE__);
		//recover_a_database($re[1]);
		return;
	}
	
	if(preg_match('#\*FATAL.+? cannot read from "(.+?)".+?: No such file or directory#', $buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer,__FILE__,__LINE__);
		events("cannot read '{$re[1]}' -> \"$buffer\"");
		squid_admin_mysql(2,"DNS Filter Service issue on {$re[1]}",$buffer,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["nohup"]} /etc/init.d/dnsfilterd restart >/dev/null 2>&1 &");
		return;
		
	}
	
	
	if(preg_match('#\*FATAL\*\s+cannot read from\s+"(.+?)"#',$buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer,__FILE__,__LINE__);
		events("Problem on {$re[1]}");
		squid_admin_mysql(2,"DNS Filter Service issue on {$re[1]} !!!",$buffer,__FILE__,__LINE__);
		return;		
	}
	
	if(preg_match("#\*FATAL\*\s+cannot read from\s+\"(.+?)\.ufdb\".+?No such file or directory#",$buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} missing", $buffer."\n Problem on {$re[1]}\n\nYou need to compile your databases",__FILE__,__LINE__);
		events("DNS Filter Service Database missing : Problem on {$re[1]}");
		
		if(!is_file($re[1])){
			@mkdir(dirname($re[1]),666,true);
			shell_exec("/bin/touch {$re[1]}");
		}
		
		
		return;		
	}
	
	
	if(preg_match("#thread worker-[0-1]+.+?caught signal\s+[0-1]+#",$buffer,$re)){
		squid_admin_mysql(0, "DNS Filter Service Daemon as crashed [ {action} = {restart} ]", $buffer,__FILE__,__LINE__);
		shell_exec("/etc/init.d/dnsfilterd restart &");
	}
	
	
	
	if(preg_match("#\*FATAL\*\s+expression list\s+(.+?): Permission denied#",$buffer,$re)){
		squid_admin_mysql(0, "Database {$re[1]} permission denied", $buffer."\nProblem on '{$re[1]}' -> chown squid:squid",__FILE__,__LINE__);
		return;
	}
	
	if(preg_match("#\*FATAL.+?expression list\s+(.+?):\s+No such file or directory#", $buffer,$re)){
		squid_admin_mysql(0, "DNS Filter Service: Database {$re[1]} missing", $buffer."\nProblem on '{$re[1]}' -> Try to repair",__FILE__,__LINE__);
		
		events("Expression list: Problem on {$re[1]} -> \"$buffer\"");
		events("Creating directory ".dirname($re[1]));
		@mkdir(dirname($re[1]),0755,true);
		events("Creating empty file '".$re[1]."'");
		@file_put_contents($re[1], "\n");
		events("DNS Filter Service: Service will be reloaded");
		
		squid_admin_mysql(1, "Reloading DNS Filter Service", null,__FILE__,__LINE__);
		shell_exec("{$GLOBALS["RELOADCMD"]} --function==".__FUNCTION__ ." --line=".__LINE__." ". "--filename=".basename(__FILE__)." >/dev/null 2>&1 &");
		return;
	}
	
	if(preg_match("#database table \/var\/lib\/squidguard\/(.+?)\/domains\s+is empty#",$buffer,$re)){
		return;
	}
	


	if(preg_match("#the new configuration and database are loaded for ufdbguardd ([0-9\.]+)#",$buffer,$re)){
		squid_admin_mysql(2, "DNS Filter Service engine v{$re[1]} has reloaded new configuration and databases","",__FILE__,__LINE__);
		return;
	}
	
	if(preg_match("#statistics:(.+)#",$buffer,$re)){return;}
	


	
	
	
	events("Not filtered: $buffer");

}

function HostnameToIp($hostname){
	if(isset($GLOBALS["IPNAMES2"][$hostname])){return $GLOBALS["IPNAMES2"][$hostname];}
	$GLOBALS["IPNAMES2"][$hostname]=gethostbyname($hostname);
	return $GLOBALS["IPNAMES2"][$hostname];
}






function IfFileTime($file,$min=10){
	if(file_time_min($file)>$min){
		@unlink($file);
		@file_put_contents($file, time());
		return true;}
	return false;
}
function WriteFileCache($file){
	@unlink("$file");
	@unlink($file);
	@file_put_contents($file,"#");	
}
function events($text){
		$pid=@getmypid();
		$date=@date("H:i:s");
		$logFile="/var/log/dnsfilterd/watchdog.log";
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
		$f = @fopen($logFile, 'a');
		@fwrite($f, "$date [$pid]:: ".basename(__FILE__)." $text\n");
		@fclose($f);	
		}
		
function events_tail($text){events($text);}		
function events_ufdb_exec($text){events($text);}		
	
function xsyslog($text){
	echo $text."\n";
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail($text, basename(__FILE__));}
	
	
}
function recover_a_database($filename){
	
}
function ToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("dnsfilterd-tail", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}


?>