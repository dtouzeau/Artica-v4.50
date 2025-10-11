<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.fetchmail.inc");
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__)."/ressources/class.maincf.multi.inc");
$GLOBALS["OUTPUT"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SERVICE_NAME"]="FetchMail Daemon";
$GLOBALS["SINGLE_DEBUG"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if($argv[1]=="--export-table"){export_table();exit();}
if($argv[1]=="--multi-start"){BuildRules();exit();}
if($argv[1]=="--single-debug"){SingleDebug($argv[2]);exit();}
if($argv[1]=="--monit"){build_monit();exit();}
if($argv[1]=="--import"){import($argv[2]);exit();}
if($argv[1]=="--import-file"){import_filename($argv[2]);exit();}

if($argv[1]=="--restore"){restore_table($argv[2]);exit();}


if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reloadx();exit();}




BuildRules();
//##############################################################################
function SingleDebugEvents($subject,$text,$ID){
	$q=new mysql();
	$pid=getmypid();
	$CurrentDate=date('Y-m-d H:i:s');
	if($GLOBALS["VERBOSE"]){echo "$CurrentDate $subject\n$text\n\n";}
	
	
	$text=addslashes($text);
	$subject=addslashes($subject);
	$sql="INSERT INTO fetchmail_debug_execute (subject,account_id,zDate,events,PID) 
	VALUES('$subject','$ID','$CurrentDate','$text','$pid')";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){echo $q->mysql_error."\n";}
	return;	
}
//##############################################################################
function restart($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	stop(true);
	BuildRules(true);
	start(true);
}
//##############################################################################
function reloadx($nopid=false){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
	}
	@file_put_contents($pidfile, getmypid());
	BuildRules(true);
	$PID=DEFAULT_PID();
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: [INIT]: {$GLOBALS["SERVICE_NAME"]} PID:$PID\n";}
	$kill=$unix->find_program("kill");
	unix_system_HUP($pid);
}
//##############################################################################
function DEFAULT_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/fetchmail.pid');
	if($unix->process_exists($pid)){return $pid;}
	$fetchmail=$unix->find_program("fetchmail");
	return $unix->PIDOF($fetchmail);
	
}
//##############################################################################
function FETCHMAIL_COUNT_SERVER(){
	$f=explode("\n",@file_get_contents("/etc/fetchmailrc"));
	$i=0;
	while (list ($a, $Tofile) = each ($f)){
		if(!preg_match("#^poll#i", $Tofile)){continue;}
		$i++;
	}
	return $i;
}
//##############################################################################
function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$fetchmail=$unix->find_program("fetchmail");
	if(!is_file($fetchmail)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} not installed\n";}
		return;
	}
	
	if(!is_file("/var/log/fetchmail.log")){@touch("/var/log/fetchmail.log");}

	$pid=DEFAULT_PID();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already started $pid since {$timepid}Mn...\n";}
		return;
	}


	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$EnableFetchmail=$sock->GET_INFO("EnableFetchmail");
	if(!is_numeric($EnableFetchmail)){$EnableFetchmail=0;}
	$nohup=$unix->find_program("nohup");
	$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
	if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
	
	
	if($EnableFetchmail==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} is disabled (EnableFetchmail)...\n";}
		return;
	}
	if($EnablePostfixMultiInstance==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} multi-postfix instances enabled `EnablePostfixMultiInstance`, switch to artica-cron.\n";}
		return;
	}
	
	
	$version=fetchmail_version();
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} version $version\n";}

	if(!is_file("/etc/fetchmailrc")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/fetchmailrc no such file, aborting\n";}
		return;
	}
	
	
	shell_exec("$chown root:root /etc/fetchmailrc");
	shell_exec("$chmod 600 /etc/fetchmailrc");
	
	if(FETCHMAIL_COUNT_SERVER()==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} No pool server set, aborting...\n";}
		return;
	}

	$FetchmailDaemonPool=$sock->GET_INFO("FetchmailDaemonPool");
	if(!is_numeric($FetchmailDaemonPool)){$FetchmailDaemonPool=300;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Pool: {$FetchmailDaemonPool}Mn\n";}
	
	$f[]="$nohup $fetchmail";
	$f[]="--daemon $FetchmailDaemonPool";
	$f[]="--pidfile /var/run/fetchmail.pid"; 
	$f[]="--fetchmailrc /etc/fetchmailrc";
	$f[]=">/dev/null 2>&1 &";


	$cmd=@implode(" ", $f);
	//if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmd\n";}
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	shell_exec($cmd);

	for($i=0;$i<4;$i++){
		$pid=DEFAULT_PID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/4...\n";}
		sleep(1);
	}

	$pid=DEFAULT_PID();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Success service started pid:$pid...\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	}
}
//##############################################################################
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=DEFAULT_PID();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		return;
	}
	$pid=DEFAULT_PID();
	$kill=$unix->find_program("kill");
	$fetchmail=$unix->find_program("fetchmail");


	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	shell_exec("$fetchmail -q >/dev/null 2>&1");
	for($i=0;$i<5;$i++){
		$pid=DEFAULT_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=DEFAULT_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=DEFAULT_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}


function SingleDebug($ID){
	$q=new mysql();
	$q->BuildTables();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	$fetchmail=$unix->find_program("fetchmail");
	if($unix->process_exists($pid)){
		SingleDebugEvents("Task aborted","This task is aborted, it already running PID $pid, please wait before executing a new task",$ID);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	if(!$GLOBALS["VERBOSE"]){
		SingleDebugEvents("Task executed","Starting rule number $ID\nThis task is executed please wait before executing a new task",$ID);
	}
	
	$fetch=new fetchmail();
	$output=array();
	
		$fetch=new fetchmail();
		$l[]="set logfile /var/log/fetchmail-rule-$ID.log";
		$l[]="set postmaster \"$fetch->FetchmailDaemonPostmaster\"";
		$l[]="set idfile \"/var/log/fetchmail.$ID.id\"";	
		$l[]="";	
	$GLOBALS["SINGLE_DEBUG"]=true;
	BuildRules();
	$pattern=$GLOBALS["FETCHMAIL_RULES_ID"][$ID];
	$l[]=$pattern;	
	@file_put_contents("/tmp/fetchmailrc.$ID",@implode("\n", $l));
	shell_exec("/bin/chmod 600 /tmp/fetchmailrc.$ID");
	$cmd="$fetchmail -v --nodetach -f /tmp/fetchmailrc.$ID --pidfile /tmp/fetcmailrc.$ID.pid 2>&1";
	
	if($GLOBALS["VERBOSE"]){
		echo $cmd."\n";
		$cmd="$fetchmail -v --nodetach -f /tmp/fetchmailrc.$ID --pidfile /tmp/fetcmailrc.$ID.pid";
		system($cmd);
		return;
	}
	exec($cmd,$output);
	SingleDebugEvents("Task finish with ". count($output)." event(s)",@implode("\n", $output),$ID);
	
}

function BuildRules_schedule(){
	
		$unix=new unix();
		$fetchmailbin=$unix->find_program("fetchmail");
		$sql="SELECT * FROM fetchmail_rules WHERE enabled=1";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} saving configuration file FAILED\n";
			return false;
		}
		
		
		foreach (glob("/etc/cron.d/fetchmail*") as $filename) {
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} removing $filename..\n";
			@unlink($filename);
		}
		foreach (glob("/etc/fetchmail-rules/*.rc") as $filename) {
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} removing $filename..\n";
			@unlink($filename);
		}
		
		if(!is_file($fetchmailbin)){return;}
		
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} building ". mysqli_num_rows($results)." rules...\n";	
		
		$fetch=new fetchmail();
		@mkdir("/etc/fetchmail-rules",0644,true);
		
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$ID=$ligne["ID"];
			$schedule=$ligne["schedule"];
			if($schedule==null){
				echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ID $ID has no schedule, set it to each 10mn `0,10,20,30,40,50 * * * *`";
				$schedule="0,10,20,30,40,50 * * * *";
			}
			$l=array();
			$l[]="set logfile /var/log/fetchmail.log";
			$l[]="set postmaster \"$fetch->FetchmailDaemonPostmaster\"";
			$l[]="set idfile \"/var/log/fetchmail.id\"";				
			$l[]=build_line($ligne);
			@file_put_contents("/etc/fetchmail-rules/$ID.rc", @implode("\n", $l)."\n");
			@chmod("/etc/fetchmail-rules/$ID.rc", 0600);
			@chown("/etc/fetchmail-rules/$ID.rc","root");
			@chgrp("/etc/fetchmail-rules/$ID.rc","root");
			$destSchedule="/etc/cron.d/fetchmail$ID";
			$t=array();
			$t[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
			$t[]="MAILTO=\"\"";
			$t[]="$schedule  root $fetchmailbin -N -f /etc/fetchmail-rules/$ID.rc --logfile /var/log/fetchmail.log --pidfile /var/run/fetchmail-$ID.pid >/dev/null 2>&1";
			$t[]="";
			
			@file_put_contents($destSchedule, @implode("\n", $t));
			
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} ID $ID done..\n";
		}
		
			@chmod("/etc/fetchmail-rules", 0600);
			@chown("/etc/fetchmail-rules","root");
			@chgrp("/etc/fetchmail-rules","root");		
	
}
function fetchmail_version(){
	if(isset($GLOBALS["fetchmail_version"])){return $GLOBALS["fetchmail_version"];}
	$unix=new unix();
	$fetchmail=$unix->find_program("fetchmail");
	if(!is_file($fetchmail)){return "0.0.0";}
	exec("$fetchmail -V 2>&1",$results);

	foreach ($results as $md=>$line){
		if(preg_match("#release\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["fetchmail_version"]=$re[1];
			return $re[1];
		}
		if(preg_match("#version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["fetchmail_version"]=$re[1];
			return $re[1];
		}
	}

	return "0.0.0";
}

function build_line($ligne){
		$sock=new sockets();
		$unix=new unix();
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		$fetchmail_version=fetchmail_version();
		
		if(!isset($GLOBALS["FetchMailToZarafa"])){
			$GLOBALS["FetchMailToZarafa"]=$sock->GET_INFO("FetchMailToZarafa");
			if(!is_numeric($GLOBALS["FetchMailToZarafa"])){
				$GLOBALS["FetchMailToZarafa"]=1;
			}
		}
		
		if(!isset($GLOBALS["ZARAFA_D_AGENT_BIN"])){
			$GLOBALS["ZARAFA_D_AGENT_BIN"]=$unix->find_program("zarafa-dagent");
			if(!is_file($GLOBALS["ZARAFA_D_AGENT_BIN"])){$GLOBALS["FetchMailToZarafa"]=0;}
		}
		
		
		if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)#", $fetchmail_version,$re)){
			$MAJOR=$re[1];
			$MINOR=$re[2];
			$REV=$re[3];
		}
		
		
			$ID=$ligne["ID"];
			writelogs("Building fetchmail rule for ID: {$ligne["ID"]},Zarafa Dagent:`{$GLOBALS["ZARAFA_D_AGENT_BIN"]}`  user:{$ligne["uid"]}, FetchMailToZarafa:{$GLOBALS["FetchMailToZarafa"]}",__FUNCTION__,__FILE__,__LINE__);
			
			$ligne["poll"]=trim($ligne["poll"]);
			if($ligne["poll"]==null){
				echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} rule {$ligne["ID"]} as no poll, skip it..\n";
				return;
			}
			if($ligne["proto"]==null){$ligne["proto"]="auto";}
			if($ligne["uid"]==null){
				echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} rule {$ligne["ID"]} as no uid, skip it..\n";
				return;
			}
			writelogs("Building \$user->user({$ligne["uid"]})",__FUNCTION__,__FILE__,__LINE__);
			$user=new user($ligne["uid"]);
			writelogs("Building $user->mail",__FUNCTION__,__FILE__,__LINE__);
			if(trim($user->mail)==null){
				writelogs("Building fetchmail uid has no mail !!!, skip it.. user:{$ligne["uid"]}",__FUNCTION__,__FILE__,__LINE__);
				echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} uid has no mail !!!, skip it..\n";
				$unix->send_email_events("Fetchmail rule for {$ligne["uid"]}/{$ligne["poll"]} has been skipped", "cannot read email address from LDAP", "mailbox");
				return;
			}
			
			$ligne["is"]=$user->mail;
			$smtphost=null;
			$sslfingerprint=null;
			$fetchall=null;
			$timeout=null;
			$port=null;
			$aka=null;
			$folder=null;
			$tracepolls=null;
			$interval=null;
			$keep=null;
			$fetchall=null;
			$sslcertck=null;
			$limit=null;
			$dropdelivered=null;
			$smtpport=null;
			$multidrop=null;
			if($ligne["proto"]=="httpp"){$ligne["proto"]="pop3";}
			if(!isset($ligne["folder"])){$ligne["folder"]=null;}
			if(!is_numeric($ligne["UseDefaultSMTP"])){$ligne["UseDefaultSMTP"]=1;}
			if(trim($ligne["port"])>0){$port="port {$ligne["port"]}";}
			if(trim($ligne["aka"])<>null){$aka="\n\taka {$ligne["aka"]}";}
			if($ligne["ssl"]==1){$ssl="\n\tssl\n\tsslproto ''";}	
			if($ligne["timeout"]>0){$timeout="\n\ttimeout {$ligne["timeout"]}";}
			if($ligne["folder"]<>null){$folder="\n\tfolder {$ligne["folder"]}";}				
			if($ligne["tracepolls"]==1){$tracepolls="\n\ttracepolls";}
			if($ligne["interval"]>0){$interval="\n\tinterval {$ligne["interval"]}";}		
			if($ligne["keep"]==1){$keep="\n\tkeep ";}
			if($ligne["keep"]==0){$keep="\n\tnokeep";}

			if($ligne["multidrop"]==1){$ligne["is"]="*";}
			if($ligne["fetchall"]==1){$fetchall="\n\tfetchall";}
			if(strlen(trim($ligne["sslfingerprint"]))>10){$sslfingerprint="\n\tsslfingerprint '{$ligne["sslfingerprint"]}'";}
			if($ligne["sslcertck"]==1){$sslcertck="\n\tsslcertck";}		
			if($GLOBALS["FetchMailGLobalDropDelivered"]==1){$ligne["dropdelivered"]=1;}
			
			if(!is_numeric($ligne["limit"])){$ligne["limit"]=2097152;}
			if($ligne["limit"]==0){$ligne["limit"]=2097152;}
			
			if(!isset($ligne["smtp_port"])){$ligne["smtp_port"]=25;}
			if(!isset($ligne["smtp_host"])){$ligne["smtp_host"]="127.0.0.1";}
			if(!is_numeric($ligne["smtp_port"])){$ligne["smtp_port"]=25;}
			if(trim($ligne["smtp_host"])==null){$ligne["smtp_host"]="127.0.0.1";}
			if($ligne["smtp_port"]<>25){
				$smtpport="/{$ligne["smtp_port"]}";
			}			
			
			$smtp="\n\tsmtphost {$ligne["smtp_host"]}$smtpport";
			$limit="\n\tlimit {$ligne["limit"]}";
						
			
			if($ligne["dropdelivered"]==1){
				$dropdelivered="\n\tdropdelivered is {$ligne["is"]} here";
			}
			
			if($GLOBALS["FetchMailToZarafa"]==1){
				if($ligne["UseDefaultSMTP"]==1){
					$smtp="\n\tmda \"{$GLOBALS["ZARAFA_D_AGENT_BIN"]} {$ligne["uid"]}\"";
				}
			}
			
			
			$tf=array();
			$folders=unserialize(base64_decode($ligne["folders"]));
			if($GLOBALS["VERBOSE"]){echo "Folder: ". count($folders)." items\n";}
			if(is_array($folders)){
				if(count($folders)>0){
					while (list ($md, $fenc) = each ($folders) ){
						$fff=base64_decode($fenc);
						if($GLOBALS["VERBOSE"]){echo "Folder: `$fff`\n";}
						$tf[]="\"$fff\"";
					}
				}
			}
			
			if($GLOBALS["VERBOSE"]){echo "Folder: final -> ".count($folders)." items\n";}
			if(count($tf)>0){
				$folder="\n\tfolder INBOX,".@implode(",", $tf);
			}
			
			if($EnablePostfixMultiInstance==1){
				if($GLOBALS["DEBUG"]){echo "multiple instances::poll={$ligne["poll"]} smtp_host={$ligne["smtp_host"]}\n";}
				if(strlen(trim($ligne["smtp_host"]))==0){return;}
				$smtphost="\n\tsmtphost ".multi_get_smtp_ip($ligne["smtp_host"]);
			}
			
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} poll {$ligne["poll"]} - version $MAJOR.$MINOR.$REV\n";
			if($MAJOR<7){
				if($MINOR<4){
					if($REV<21){
						if(trim($ssl)==null){$ssl="\n\tsslproto ssl23\n\tno ssl";}
					}
				}
			}
			
			$pattern="poll {$ligne["poll"]}$tracepolls\n\tproto {$ligne["proto"]} $port$interval$timeout$aka\n\tuser \"{$ligne["user"]}\"\n\tpass {$ligne["pass"]}\n\tis {$ligne["is"]}$dropdelivered$folder$ssl$fetchall$keep$multidrop$sslfingerprint$sslcertck$smtphost$limit$smtp\n\n";
			if($GLOBALS["DEBUG"]){echo "$pattern\n";}

			$GLOBALS["multi_smtp"][$ligne["smtp_host"]][]=$pattern;
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} poll {$ligne["poll"]} -> {$ligne["user"]} limit ". round($ligne["limit"]/1024)/1024 ." Mo\n";	
	
			return $pattern;
	
}


function BuildRules(){
		$unix=new unix();
		$sock=new sockets();
		if(system_is_overloaded(basename(__FILE__))){
            squid_admin_mysql(1, "{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: {OVERLOADED_SYSTEM}, aborting task",null,__FILE__,__LINE__);
		    exit();
		}
		$EnableFetchmailScheduler=$sock->GET_INFO("EnableFetchmailScheduler");	
		$EnablePostfixMultiInstance=$sock->GET_INFO("EnablePostfixMultiInstance");
		if(!is_numeric($EnableFetchmailScheduler)){$EnableFetchmailScheduler=0;}
		if(!is_numeric($EnablePostfixMultiInstance)){$EnablePostfixMultiInstance=0;}
		
		$DenyFetchMailWriteConf=$sock->GET_INFO("DenyFetchMailWriteConf");
		if(!is_numeric($DenyFetchMailWriteConf)){$DenyFetchMailWriteConf=0;}
		
		if(!isset($GLOBALS["FetchMailGLobalDropDelivered"])){
			$sock=new sockets();
			$GLOBALS["FetchMailGLobalDropDelivered"]=$sock->GET_INFO("FetchMailGLobalDropDelivered");
			if(!is_numeric($GLOBALS["FetchMailGLobalDropDelivered"])){$GLOBALS["FetchMailGLobalDropDelivered"]=0;}
			
		}	

		if($DenyFetchMailWriteConf==1){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} saving configuration denied (DenyFetchMailWriteConf)\n";
			return true;}
		
		@file_put_contents("/proc/sys/net/ipv4/tcp_timestamps", "0");
		if($EnableFetchmailScheduler==1){BuildRules_schedule();return;}
		
		
		foreach (glob("/etc/cron.d/fetchmail*") as $filename) {
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} removing $filename..\n";
			@unlink($filename);
		}		
		
		
		$fetch=new fetchmail();
		$l[]="set logfile /var/log/fetchmail.log";
		$l[]="set daemon $fetch->FetchmailPoolingTime";
		$l[]="set postmaster \"$fetch->FetchmailDaemonPostmaster\"";
		$l[]="set idfile \"/var/log/fetchmail.id\"";	
		$l[]="";

		$sql="SELECT * FROM fetchmail_rules WHERE enabled=1";
		$q=new mysql();
		
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} saving configuration file FAILED\n";
			return false;
		}
		
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} building ". mysqli_num_rows($results)." rules...\n";
		
		
		$array=array();
		while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$ID=$ligne["ID"];
			$pattern=build_line($ligne);
			$l[]=$pattern;
			$GLOBALS["FETCHMAIL_RULES_ID"][$ID]=$pattern;
		}
		
		if($GLOBALS["SINGLE_DEBUG"]){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} single-debug, aborting nex step\n";
			return;
		}
		
		if($EnablePostfixMultiInstance==1){
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} postfix multiple instances enabled (".count($GLOBALS["multi_smtp"]).") hostnames\n";
			@unlink("/etc/artica-postfix/fetchmail.schedules");
			
			if(is_array($GLOBALS["multi_smtp"])){
				if($GLOBALS["DEBUG"]){print_r($GLOBALS["multi_smtp"]);}
				while (list ($hostname, $rules) = each ($GLOBALS["multi_smtp"])){
					echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $hostname save rules...\n";
					@file_put_contents("/etc/postfix-$hostname/fetchmail.rc",@implode("\n",$rules));
					@chmod("/etc/postfix-$hostname/fetchmail.rc",0600);
					$schedule[]=multi_build_schedule($hostname);
					if(!is_fetchmailset($hostname)){
						$restart=true;
					}else{
						echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $hostname already scheduled...\n";
					}
				}
				if($restart){
					@file_put_contents("/etc/artica-postfix/fetchmail.schedules",@implode("\n",$schedule));
					system("/etc/init.d/artica-postfix restart fcron");
				}
			}
		return;
		}
		
		if(is_file("/etc/fetchmail.perso")){
			$l[]="# fetchmail.perso content";
			$l[]="# Save a configuration file in /etc/fetchmail.perso";
			$l[]=@file_get_contents("/etc/fetchmail.perso");
		}
		
		if(is_array($l)){
			$conf=implode("\n",$l);
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} building /etc/fetchmailrc ". count($l)." lines\n";
		}else{
			echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} building /etc/fetchmailrc 0 lines\n";
			$conf=null;}
		@file_put_contents("/etc/fetchmailrc",$conf);
		shell_exec("/bin/chmod 600 /etc/fetchmailrc");
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} saving /etc/fetchmailrc configuration file done\n";
		build_monit();
		if($GLOBALS["RELOAD"]){if($EnablePostfixMultiInstance==0){reload();}}
			
}

function reload(){
	$unix=new unix();
	$kill=$unix->find_program("kill");
	$tb=explode("\n", @file_get_contents("/var/run/fetchmail.pid"));
	$isrun=false;
	while (list ($i, $pid) = each ($tb)){
		if(trim($pid)==null){continue;}
		if(!preg_match("#([0-9]+)#", $pid,$re)){continue;}
		$pid=$re[1];
		if(!$unix->process_exists($pid)){continue;}
		$isrun=true;
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} reload pid $pid\n";
		unix_system_HUP($pid);
	}
	
	if(!$isrun){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} is not running, start it\n";
		start(true);
	}
	
	
}

function is_fetchmailset($hostname){
	
	if(!is_array($GLOBALS["crontab"])){
		exec("/usr/share/artica-postfix/bin/fcrontab -c /etc/artica-cron/artica-cron.conf  -l -u root 2>&1",$results);
		$GLOBALS["crontab"]=$results;
	}
	if($GLOBALS["DEBUG"]){echo __FUNCTION__.":: $hostname ". count($GLOBALS["crontab"])." lines\n";}
	$hostname=str_replace(".","\.",$hostname);
	while (list ($i, $line) = each ($GLOBALS["crontab"])){
		if(preg_match("#bin\/fetchmail.+?fetchmailrc\s+\/etc\/postfix-$hostname#",$line)){
			return true;
		}else{
		if($GLOBALS["DEBUG"]){echo __FUNCTION__.":: $line NO MATCH #bin\/fetchmail.+?fetchmailrc \/etc\/$hostname#\n";}
		}
		
	}
	return false;
	
}


function multi_get_smtp_ip($hostname){
	if($GLOBALS["SMTP_HOSTS_IP_FETCHMAIL"][$hostname]<>null){return $GLOBALS["SMTP_HOSTS_IP_FETCHMAIL"][$hostname];}
	$main=new maincf_multi($hostname);
	$GLOBALS["SMTP_HOSTS_IP_FETCHMAIL"][$hostname]=$main->ip_addr;
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $hostname ($main->ip_addr)\n";
	return $main->ip_addr;
	
}

function multi_build_schedule($hostname){
	$unix=new unix();
	$fetchmail=$unix->find_program("fetchmail");
	if($fetchmail==null){return null;}	
	$main=new maincf_multi($hostname);
	$array=unserialize(base64_decode($main->GET_BIGDATA("PostfixMultiFetchMail")));	
	if($array[$hostname]["enabled"]<>1){return null;}
	if($array[$hostname]["schedule"]==null){return null;}
	if($array[$hostname]["schedule"]<2){return null;}
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $hostname scheduling each {$array[$hostname]["schedule"]}mn\n";
	return "{$array[$hostname]["schedule"]} $fetchmail --nodetach --fetchmailrc /etc/postfix-$hostname/fetchmail.rc >>/var/log/fetchmail.log";
	
	
}
function build_monit(){
	$settings=new settings_inc();
	$sock=new sockets();
	$monit_file="/etc/monit/conf.d/fetchmail.monitrc";
	$start_file="/usr/sbin/fetchmail-monit-start";
	$stop_file="/usr/sbin/fetchmail-monit-stop";
	$processMonitName="fetchmail";
	$reloadmonit=false;
	
	$FilesToCheck[]=$monit_file;
	$FilesToCheck[]=$start_file;
	$FilesToCheck[]=$stop_file;
	
	
	if(!$settings->MONIT_INSTALLED){
		echo "Starting......: ".date("H:i:s")." $processMonitName Monit is not installed\n";
		return;
	}
	
	$unix=new unix();
	$pidfile="/var/run/fetchmail.pid";
	$chmod=$unix->find_program("chmod");
	
	
	echo "Starting......: ".date("H:i:s")." $processMonitName PidFile = `$pidfile`\n";
	if($pidfile==null){
		echo "Starting......: ".date("H:i:s")." $processMonitName PidFile unable to locate\n";
		return ;
	}
	

	$MonitConfig=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FetchMailMonitConfig")));
	if(!is_numeric($MonitConfig["watchdog"])){$MonitConfig["watchdog"]=1;}
	if(!is_numeric($MonitConfig["watchdogCPU"])){$MonitConfig["watchdogCPU"]=95;}
	if(!is_numeric($MonitConfig["watchdogMEM"])){$MonitConfig["watchdogMEM"]=1500;}	
	$EnableDaemon=$sock->GET_INFO("EnableFetchmail");
	$EnableFetchmailScheduler=$sock->GET_INFO("EnableFetchmailScheduler");
	
	if(!is_numeric($EnableDaemon)){$EnableDaemon=0;}
	if(!is_numeric($EnableFetchmailScheduler)){$EnableFetchmailScheduler=0;}
	if($EnableDaemon==0){$MonitConfig["watchdog"]=0;}
	if($EnableFetchmailScheduler==1){$MonitConfig["watchdog"]=0;}
	
	if($MonitConfig["watchdog"]==0){
		echo "Starting......: ".date("H:i:s")." $processMonitName Monit is not enabled (watchdog)\n";

		foreach ($FilesToCheck as $Tofile){
			if(is_file($Tofile)){
				@unlink($Tofile);
				$reloadmonit=true;
			}
		}
	}
	
	if($MonitConfig["watchdog"]==1){
		
		while (list ($i, $Tofile) = each ($FilesToCheck)){if(!is_file($Tofile)){$reloadmonit=true;break;}echo "Starting......: ".date("H:i:s")." $processMonitName `$Tofile` Monit file done\n";}
		if(!$reloadmonit){	echo "Starting......: ".date("H:i:s")." $processMonitName Monit is already set check pid `$pidfile`\n";return;}
			
		echo "Starting......: ".date("H:i:s")." $processMonitName Monit is enabled check pid `$pidfile`\n";
		$reloadmonit=true;
		$f[]="check process $processMonitName";
   		$f[]="with pidfile $pidfile";
   		$f[]="start program = \"$start_file\"";
   		$f[]="stop program =  \"$stop_file\"";
   		if($MonitConfig["watchdogMEM"]){
  			$f[]="if totalmem > {$MonitConfig["watchdogMEM"]} MB for 5 cycles then alert";
   		}
   		if($MonitConfig["watchdogCPU"]>0){
   			$f[]="if cpu > {$MonitConfig["watchdogCPU"]}% for 5 cycles then alert";
   		}
	   $f[]="if 5 restarts within 5 cycles then timeout";
	    echo "Starting......: ".date("H:i:s")." $processMonitName $monit_file done\n";
	   @file_put_contents($monit_file, @implode("\n", $f));
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]="/etc/init.d/fetchmail start";
	   $f[]="exit 0\n";
 	   @file_put_contents($start_file, @implode("\n", $f));
 	   echo "Starting......: ".date("H:i:s")." $processMonitName $start_file done\n"; 
 	   shell_exec("$chmod 777 $start_file");
	   $f=array();
	   $f[]="#!/bin/sh";
	   $f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin";
	   $f[]="/etc/init.d/fetchmail stop";
	   $f[]="exit 0\n";
 	   @file_put_contents($stop_file, @implode("\n", $f));
 	   echo "Starting......: ".date("H:i:s")." $processMonitName $stop_file done\n";
 	   shell_exec("$chmod 777 $stop_file");	   
	}
	
	if($reloadmonit){
		$unix->THREAD_COMMAND_SET("/usr/share/artica-postfix/bin/artica-install --monit-check");
	}	
}

function import_filename($filename){
	if(!is_file($filename)){echo "$filename (no such file)\n";}
	$f=new Fetchmail_settings();
	$array=$f->parse_config(@file_get_contents($filename));
	echo "Importing ". count($array)." lines/rules form \"$filename\"\n";
	$fetch=new Fetchmail_settings();
	foreach ($array as $num=>$ligne){
		if($fetch->EditRule($ligne, 0,true) );
	}
	
}


function import($path){
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		echo "This task is aborted, it already running PID $pid, please wait before executing a new task\n";
		return;
	}
	$t=time();
	//Mailbox server;Protocol;username;password;local account;SSL Protocol;Use SSL 0/1
	$array=explode("\n",@file_get_contents($path));

	$c=0;
	$FD=0;
	echo "Importing ". count($array)." lines/rules form \"$path\"\n";
	foreach ($array as $num=>$ligne){
		$ligne=str_replace("\r", "", $ligne);
		$ligne=str_replace("\n", "", $ligne);
		$ligne=str_replace('"', "", $ligne);
		if(trim($ligne)==null){continue;}
		if(strpos($ligne, ";")==0){continue;}
		$POSTED_ARRAY=array();
		$tb=explode(";", $ligne);
		if(count($tb)<7){echo "Error line: $num..\n";$FD++;continue;}
		$POSTED_ARRAY["poll"]=$tb[0];
		$POSTED_ARRAY["proto"]=$tb[1];
		$POSTED_ARRAY["user"]=$tb[2];
		$POSTED_ARRAY["pass"]=$tb[3];
		$POSTED_ARRAY["uid"]=$tb[4];
		$POSTED_ARRAY["sslproto"]=$tb[5];
		$POSTED_ARRAY["ssl"]=intval($tb[6]);
		$ct=new user($POSTED_ARRAY["uid"]);
		if($ct->mail==null){echo "Error line:$num {$POSTED_ARRAY["uid"]} no such member\n";$FD++;continue;}
		$POSTED_ARRAY["is"]=$ct->mail;
		$fetchmail=new Fetchmail_settings();
		if(!$fetchmail->AddRule($POSTED_ARRAY)){echo "Error adding rule line $num\n";continue;}
		echo "Success adding rule line $num\n";	
		$c++;
		}
		
		
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		echo "Import task finish took:$took $FD failed, $c success\n"; 
}

function export_table(){
	$unix=new unix();
	$target_file="/usr/share/artica-postfix/ressources/logs/fetchmail-export.gz";
	
	$mysqldump=$unix->find_program("mysqldump");
	$bzip2=$unix->find_program("bzip2");
	$time=time();
	if(!is_dir(dirname($target_file))){@mkdir(dirname($target_file),0755,true);}
	@unlink($target_file);
	$bzip2_cmd="| $bzip2 ";
	
	$q=new mysql();
	$mysql_admin=$q->mysql_admin;
	$mysql_password=$q->mysql_password;
	$mysql_server=$q->mysql_server;
	if( ($mysql_server=="localhost") OR ($mysql_server=="127.0.0.1")){
		if($q->SocketName==null){$q->SocketName="/var/run/mysqld/mysqld.sock";}
		if($mysql_password<>null){$mysql_password=" -p$mysql_password";}
		$Socket=" --protocol=socket -S $q->SocketName -u $mysql_admin$mysql_password";
		
	}else{
		if($mysql_password<>null){$mysql_password=" -p$mysql_password";}
		$Socket="--port=$q->mysql_port --host=$mysql_server -u $mysql_admin$mysql_password";
	}
	
	
	
	
	$cmd="$mysqldump$Socket --single-transaction --skip-add-drop-table --no-create-db --insert-ignore --skip-add-locks --skip-lock-tables artica_backup  fetchmail_rules $bzip2_cmd> $target_file 2>&1";
	
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	shell_exec($cmd);	
	
	
}
function restore_table($filepath){
	$unix=new unix();
	$bzip2=$unix->find_program("bzip2");
	$tStart=time();
	$mysql=$unix->find_program("mysql");
	
	$q=new mysql();
	$mysql_admin=$q->mysql_admin;
	$mysql_password=$q->mysql_password;
	$mysql_server=$q->mysql_server;
	if( ($mysql_server=="localhost") OR ($mysql_server=="127.0.0.1")){
		if($q->SocketName==null){$q->SocketName="/var/run/mysqld/mysqld.sock";}
		if($mysql_password<>null){$mysql_password=" -p$mysql_password";}
		$Socket=" --protocol=socket -S $q->SocketName -u $mysql_admin$mysql_password";
	
	}else{
		if($mysql_password<>null){$mysql_password=" -p$mysql_password";}
		$Socket="--port=$q->mysql_port --host=$mysql_server -u $mysql_admin$mysql_password";
	}
	
	
	echo "Action: Restoring From $filepath with uncompress..\n";
	$cmd="$bzip2 -d -c $filepath |$mysql --show-warnings $Socket --batch --debug-info --database=artica_backup 2>&1";
	echo $cmd."\n";
	exec($cmd,$results);
	echo @implode("\n", $results);
	
	
	
	
}


?>