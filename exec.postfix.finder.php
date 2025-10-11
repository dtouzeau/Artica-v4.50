<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["ULIMITED"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--unlimit#",implode(" ",$argv))){$GLOBALS["ULIMITED"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}



if($argv[1]=="--find"){find($argv[2]);exit();exit;}
if($argv[1]=="--logrotate"){logrotate();exit();exit;}
if($argv[1]=="--transaction-find"){transaction_find($argv[2],$argv[3]);exit();exit;}
start();
function start(){
	
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
	if($unix->process_exists($pid)){
		$unix->events(basename(__FILE__).":: ".__FUNCTION__." Already process $pid running.. Aborting");
		return;
	}	
	
	$sql="SELECT * FROM postfinder WHERE finish=0 ORDER BY date_start";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		find($ligne["pattern"]);
	}
	
	
}

function find($pattern){
	$SEARCH["PATTERN"]=$pattern;
	$md5Pattern=md5($pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	
	$MONTHS=array("Jan"=>1,"Feb"=>2,"Mar"=>3,"Apr"=>4,"May"=>5,"Jun"=>6,"Jul"=>7,"Aug"=>8,"Sep"=>9,"Oct"=>10,"Nov"=>11,"Dec"=>12);	
	$POSTIDS=array();
	$unix=new unix();
	$sock=new sockets();
	$BackupMailLogPath=$sock->GET_INFO("BackupMailLogPath");
	if($BackupMailLogPath==null){$BackupMailLogPath="/home/logrotate_backup";}
	if($GLOBALS["VERBOSE"]){echo "BackupMailLogPath=$BackupMailLogPath\n";}
	$filecache="$BackupMailLogPath/$md5Pattern.search";
	
	$sql="UPDATE postfinder SET finish=-1 WHERE `md5`='$md5Pattern'";
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_events');
	if(!$q->ok){echo $q->mysql_error;}	
	
	
	$grep=$unix->find_program("grep");
	$cmd="$grep --binary-files=text -Ei \"$pattern\" $BackupMailLogPath/*.log 2>&1 >$filecache";
	if(!is_file($filecache)){
		if($GLOBALS["VERBOSE"]){echo "$filecache no such file -> $cmd \n";}
		shell_exec($cmd);
	}else{
		$timefile=file_time_min($filecache);
		if($timefile>10){shell_exec($cmd);}
	}
	$DATES=array();
	$handle = @fopen("$filecache", "r"); // Open file form read.
	if ($handle) {
		while (!feof($handle)){
			$ligne = fgets($handle, 4096);
			if(preg_match("#:([A-Za-z]+)\s+([0-9]+)\s+([0-9:]+)\s+.+?\[[0-9]+\]:\s+([0-9A-Z]+):#",$ligne,$re)){
				$month=$MONTHS[$re[1]];
				if(strpos($ligne,"KASINFO")>0){continue;}
				if(strlen($month)==1){$month="0$month";}
				$year=date('Y');
				if(strlen($re[2])){$re[2]="0$re[2]";}
				$date="$year-$month-{$re[2]} {$re[3]}";
				$time=strtotime($date);
				
				$postid=$re[4];
				if($postid=="NOQUEUE"){
					if($GLOBALS["VERBOSE"]){echo "$ligne skip\n";}
					$POSTIDS[$time][md5($ligne)][]=$ligne;
					continue;
				}
				$DATES[$time]=$postid;
				
			}
			
		}
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "$filecache unable to read\n";}
		
	}
	
	if(count($DATES)>0){
		krsort($DATES);
		while(list($time, $msg_id) = each($DATES)) {
			$results=array();
			$cmd="$grep -h \"$msg_id\" $BackupMailLogPath/*.log 2>&1";
			
			exec($cmd,$results);
			$POSTIDS[$time][$msg_id]=$results;
			
		}
		
	}
	$cc=0;
	while(list($time, $msg_id) = each($POSTIDS)) {
		$cc++;
	}
	reset($POSTIDS);
	krsort($POSTIDS);
	$finals=addslashes(serialize($POSTIDS));
	$now=date('Y-m-d H:i:s');
	$sql="UPDATE postfinder SET finish=1, date_end='$now', search_datas='$finals',msg_num=$cc WHERE `md5`='$md5Pattern'";
	$q=new mysql();
	$q->QUERY_SQL($sql,'artica_events');
	if(!$q->ok){echo $q->mysql_error;}

}


function logrotate(){
	$unix=new unix();
	$sock=new sockets();
	$BackupMailLogPath=$sock->GET_INFO("BackupMailLogPath");
	$BackupMailLogMaxTimeCompressed=$sock->GET_INFO("BackupMailLogMaxTimeCompressed");
	
	if(!is_numeric($BackupMailLogMaxTimeCompressed)){$BackupMailLogMaxTimeCompressed=10080;}
	if($BackupMailLogPath==null){$BackupMailLogPath="/home/logrotate_backup";}
	$du=$unix->find_program("du");
	$gzip=$unix->find_program("gzip");
	

	
	@mkdir("$BackupMailLogPath",660,true);
	if(!is_dir("$BackupMailLogPath")){
		$unix->send_email_events("PostFinder:Error while creating $BackupMailLogPath");
		return;
	}
	
	$nice=$unix->EXEC_NICE();
	$timestart=time();
	$log=array();
	foreach (glob("$BackupMailLogPath/*.log") as $filename) {
		$timefile=$unix->file_time_min($filename);
		$basename=basename($filename);
		if($GLOBALS["VERBOSE"]){echo "$basename: $timefile minutes (need $BackupMailLogMaxTimeCompressed minutes)\n";}
		if($timefile>$BackupMailLogMaxTimeCompressed){
			$targetgzip="$BackupMailLogPath/$basename.gz";
			$cmd=trim("$nice$gzip -c $filename >$targetgzip");
			if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
			$time=time();
			shell_exec($cmd);
			if(is_file($targetgzip)){
				$ev="$basename -> $targetgzip " . $unix->distanceOfTimeInWords($time,time());
				if($GLOBALS["VERBOSE"]){echo "$ev\n";}
				$log[]=$ev;
				@unlink($filename);
			}
		}
	}
	
	$strings=null;
	$NICE=$unix->EXEC_NICE();
	exec("$NICE$du -h -s $BackupMailLogPath",$results);
	$strings=@implode("",$results);
	if(preg_match("#^(.+?)\s+#",$strings,$re)){$final_size=$re[1];}

	@file_put_contents("/usr/share/artica-postfix/ressources/logs/postfinder.dirsize.txt",$final_size);
	@chmod("/usr/share/artica-postfix/ressources/logs/postfinder.dirsize.txt",777);
	if(count($log)>0){
		$unix->send_email_events("Postfinder: Directory size: $strings - ". count($log)." maillog compressed (". $unix->distanceOfTimeInWords($timestart,time()).")","Directory: $BackupMailLogPath\n".@implode("\n",$log),"postfix");
		
	}
	
	
}

function transaction_find($msgid,$id){
	$unix=new unix();
	$results=array();
	$users=new usersMenus();
	$maillog=$users->maillog_path;
	$grep=$unix->find_program("grep");
	if(!is_file($maillog)){$results[]="$maillog no such file";}
	exec("$grep $msgid $maillog 2>&1",$results);
	$q=new mysql();
	if(count($results)==0){
		$sql="SELECT time_stamp	FROM smtp_logs WHERE id=$id";
		$ligne=mysqli_fetch_array($q->QUERY_SQL($sql,"artica_events"));
		$time=strtotime($ligne["time_stamp"]);
		$pp=date("H:i",$time);
		$d=date("d",$time);
		
		$results[]="Find '$d\s+$pp.*?postfix'";
		exec("$grep --binary-files=text -Ei '$d\s+$pp.*?postfix' $maillog 2>&1",$results);
	}
		
	if(count($results)==0){	
		$results[]="$msgid no such transaction";
	}
	
	
	$sql="UPDATE smtp_logs SET `transaction`='".base64_encode(@implode("\n", $results))."' WHERE id='$id'";
	$q->QUERY_SQL($sql,"artica_events");
	if(!$q->ok){squid_admin_mysql(2, "Fatal, search transaction $msgid failed $q->mysql_error", __FUNCTION__, __FILE__, __LINE__, "postfix");}
}

