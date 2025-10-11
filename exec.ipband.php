<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FLUSH"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--flush#",implode(" ",$argv))){$GLOBALS["FLUSH"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


if($argv[1]=='--restart'){restart();exit;}
if($argv[1]=='--start'){start();exit;}
if($argv[1]=='--stop'){stop();exit;}
if($argv[1]=='--parse'){print_r(parseReport($argv[2]));exit;}
if($argv[1]=='--tomysql'){parsereports();exit;}
if($argv[1]=='--status'){status();exit;}
if($argv[1]=='--parseresolv'){ParseResolvConf();exit;}
if($argv[1]=='--sum'){sumof();exit;}


function status(){
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ipband=$GLOBALS["CLASS_UNIX"]->find_program("ipband");
	if(strlen($ipband)<5){return;}
	$sock=new sockets();
	$ipBandEnabled=$sock->GET_INFO("ipBandEnabled");
	if(!is_numeric($ipBandEnabled)){$ipBandEnabled=0;}
	if($ipBandEnabled==0){return;}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$me=basename(__FILE__);
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid,$me)){
		if($GLOBALS["VERBOSE"]){echo " --> Already executed.. $pid aborting the process\n";}
		squid_admin_mysql(2, "Already executed.. $pid aborting the process",__FUNCTION__,__FILE__,__LINE__,"ipband");
		return;
	}	
	@file_put_contents($pidfile, getmypid());
	
	
	$pidfileTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$unix=new unix();
	$time=$unix->file_time_min($pidfileTime);
	
	if($time>4){
		WriteMyLogs("-> parsereports()",__FUNCTION__,__FILE__,__LINE__,"ipband");
		if(!system_is_overloaded()){
			@unlink($pidfileTime);
			@file_put_contents($pidfileTime, time());
			parsereports();
			WriteMyLogs("-> start()",__FUNCTION__,__FILE__,__LINE__,"ipband");
			start();
		}else{
			squid_admin_mysql(2, "-> OVERLOADED {$GLOBALS["SYSTEM_INTERNAL_LOAD"]} -> Die()",__FUNCTION__,__FILE__,__LINE__,"ipband");
		}
	}
	
}

function sumof(){
	$q=new mysql();
	$rows=$q->COUNT_ROWS("ipband", "artica_events");	
	$sock=new sockets();
	$ipBandEnabled=$sock->GET_INFO("ipBandEnabled");
	if(!is_numeric($ipBandEnabled)){$ipBandEnabled=0;}
	if($ipBandEnabled==0){
		if($rows>1){
			squid_admin_mysql(2, "$rows in table, too much rows, remove the table and re-create it",__FUNCTION__,__FILE__,__LINE__,"ipband");
			$q->QUERY_SQL("DROP TABLE `ipband`","artica_events");
			$q->BuildTables();			
		}
	}
	

	if($rows>1000000){
		squid_admin_mysql(2, "$rows in table, too much rows, remove the table and re-create it",__FUNCTION__,__FILE__,__LINE__,"ipband");
		$q->QUERY_SQL("DROP TABLE `ipband`","artica_events");
		$q->BuildTables();
		
	}
	
}


function restart(){
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ipband=$GLOBALS["CLASS_UNIX"]->find_program("ipband");
	if(strlen($ipband)<5){return;}
	
	$sock=new sockets();
	$ipBandEnabled=$sock->GET_INFO("ipBandEnabled");
	if(!is_numeric($ipBandEnabled)){$ipBandEnabled=0;}	
	if($ipBandEnabled==0){stopall();return;}
	stopall();
	stop();
	start();
}

function stopall(){
	$GLOBALS["CLASS_UNIX"]=new unix();
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$pidof=$GLOBALS["CLASS_UNIX"]->find_program("pidof");
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ipband=$GLOBALS["CLASS_UNIX"]->find_program("ipband");
	if(strlen($ipband)<5){return;}
	shell_exec("$kill -9 `$pidof $ipband 2>&1`");
	
}

function stop(){
	$GLOBALS["CLASS_UNIX"]=new unix();
	$q=new mysql();
	$sql="SELECT network FROM ipban";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(mysqli_num_rows($results)==0){return;}
	
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$network=$ligne["network"];
		stopnet($network);
	}
}

function parsereports(){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	foreach (glob("/var/log/ipband.*") as $filename) {
		WriteMyLogs("Parsing $filename",__FUNCTION__,__FILE__,__LINE__,"ipband");
		if(preg_match("#\.html$#", $filename)){
			WriteMyLogs("$filename -> SKIP",__FUNCTION__,__FILE__,__LINE__,"ipband");
			continue;
		}
		
		$array=parseReport($filename);
		if(sendtomysql($array)){
			WriteMyLogs("Unlink $filename",__FUNCTION__,__FILE__,__LINE__,"ipband");
			@unlink($filename);
		}else{
			squid_admin_mysql(2, "Fatal while injecting $filename",__FUNCTION__,__FILE__,__LINE__,"ipband");
		}
	}

	
}

function parseReport($filename){
	$c=0;
	$sock=new sockets();
	$ipBandEnabled=$sock->GET_INFO("ipBandEnabled");
	if(!is_numeric($ipBandEnabled)){$ipBandEnabled=0;}
	if($ipBandEnabled==0){return;}	
	$filesize=$GLOBALS["CLASS_UNIX"]->file_size($filename);
	$filesize=round($filesize/1024)/1000;
	WriteMyLogs("$filename -> $filesize M",__FUNCTION__,__FILE__,__LINE__,"ipband");
	if($filesize>50){
		WriteMyLogs("{$filesize}M -> KILL IT",__FUNCTION__,__FILE__,__LINE__,"ipband");
		@unlink($filename);
		return;
	}
	
	
	$f=file($filename);
	foreach ($f as $index=>$line){
		
		if(preg_match("#Date:\s+(.+)#", $line,$re)){
			$zdate=$re[1];
			if(preg_match("#([a-zA-Z]+)\s+([a-zA-Z]+)\s+([0-9]+)\s+([0-9\:]+)\s+([0-9]+)#", $zdate,$re)){
				$zdate="{$re[1]} {$re[2]} {$re[3]} {$re[5]} {$re[4]}";
			}
			 
			$timestr=trim($zdate);
			$time=strtotime($timestr);
			echo "Date: ".date('Y-m-d H:i:s',$time)."\n";
			$ttime=date('Y-m-d H:i:s',$time);
			continue;
		}
		
		if(preg_match("#^(.+?)\s+<.*?([0-9]+).*?>\s+.*?([0-9\.]+)\s+<.*?([0-9]+).*?>\s+([a-z]+)\s+([0-9\.]+)\s+(.+)#", $line,$re)){
			$FROM=trim($re[1]);
			$FROM_PORT=trim($re[2]);
			$TO=trim($re[3]);
			$TO_PORT=trim($re[4]);
			$proto=trim($re[5]);
			$sizebytes=round($re[6]*1024);
			$ARRAY[$ttime][]=array("FROM"=>$FROM,"FROM_PORT"=>$FROM_PORT,"TO"=>$TO,"TO_PORT"=>$TO_PORT,"PROTO"=>$proto,"SIZE"=>$sizebytes);
			$c++;
			continue;
		}
	}
	WriteMyLogs("$c lines detected in arrayof(". count($ARRAY).") items",__FUNCTION__,__FILE__,__LINE__,"ipband");
	if($GLOBALS["VERBOSE"]){echo "$c lines detected in arrayof(". count($ARRAY).") items\n";}
	return $ARRAY;
	
	
	
}

function sendtomysql($array){
	$q=new mysql();
	sumof();
	$prefix="INSERT INTO ipband (`zDate`,`IP_FROM`,`PORT_FROM`,`IP_TO`,`PORT_TO`,`size`,`proto`) VALUES ";
	if(!$q->TABLE_EXISTS("ipband", "artica_events")){$q->BuildTables();}
	if(!$q->TABLE_EXISTS("ipband", "artica_events")){
		WriteMyLogs("ipband no such table",__FUNCTION__,__FILE__,__LINE__,"ipband");
		return false;
	}
	if(!is_array($array)){
		WriteMyLogs("Not an array",__FUNCTION__,__FILE__,__LINE__,"ipband");
		return false;
	}
	if(count($array)==0){
		WriteMyLogs("No item",__FUNCTION__,__FILE__,__LINE__,"ipband");
		return false;
	}
	while (list ($zdate, $zarray) = each ($array)){
		$Countzarray=count($zarray);
		$DF=0;
		while (list ($index, $Yarray) = each ($zarray)){
			$DF++;
			if($GLOBALS["VERBOSE"]){echo "$zdate {$Yarray["FROM"]} {$Yarray["TO"]}\n";}
			$f[]="('$zdate','{$Yarray["FROM"]}','{$Yarray["FROM_PORT"]}','{$Yarray["TO"]}','{$Yarray["TO_PORT"]}','{$Yarray["SIZE"]}','{$Yarray["PROTO"]}')";
			WriteMyLogs("$zdate {$Yarray["FROM"]} => {$Yarray["TO"]}",__FUNCTION__,__FILE__,__LINE__,"ipband");
			if(count($f)>3000){
				$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
				if(!$q->ok){
					squid_admin_mysql(2, "$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"ipband");
					return false;
				}
				$f=array();
			}
		}
		
	}
	
	system_admin_events(count($f)." item(s)",__FUNCTION__,__FILE__,__LINE__,"ipband");
	if(count($f)>0){
		$q->QUERY_SQL($prefix.@implode(",", $f),"artica_events");
		if(!$q->ok){
			WriteMyLogs("$q->mysql_error",__FUNCTION__,__FILE__,__LINE__,"ipband");
			return false;
		}
	}
	

	
	return true;
}

function gethostbyaddrCache($ip,$vv=null){
	if(isset($GLOBALS[$ip])){return $GLOBALS[$ip];}
	$GLOBALS[$ip]=gethostbyaddr($ip);
	if($GLOBALS["VERBOSE"]){echo "Resolv $ip -> {$GLOBALS[$ip]} ($vv)\n";}
	return $GLOBALS[$ip];
	
}

function ParseResolvConf(){
	
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ipband=$GLOBALS["CLASS_UNIX"]->find_program("ipband");
	if(strlen($ipband)<5){return;}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$me=basename(__FILE__);
	$pid=@file_get_contents($pidfile);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid,$me)){if($GLOBALS["VERBOSE"]){echo " --> Already executed.. $pid aborting the process\n";}squid_admin_mysql(2, "Already executed.. $pid aborting the process",__FUNCTION__,__FILE__,__LINE__,"ipband");return;}	
	@file_put_contents($pidfile, getmypid());	
	$t=time();
	$c=0;
	sumof();
	$q=new mysql();
	$sql="SELECT IP_TO_HOST,IP_TO FROM ipband GROUP BY IP_TO_HOST,IP_TO HAVING IP_TO_HOST IS NULL";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ipto=$ligne["IP_TO"];
		$hostname=gethostbyaddrCache($ipto);
		$c++;
		$q->QUERY_SQL("UPDATE ipband SET IP_TO_HOST='$hostname' WHERE IP_TO='$ipto'","artica_events");
		if(!$q->ok){squid_admin_mysql(2, "$q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"ipband");}
		
	}
	
	$sql="SELECT IP_FROM,IP_FROM_HOST FROM ipband GROUP BY IP_FROM,IP_FROM_HOST HAVING IP_FROM_HOST IS NULL";
	$results=$q->QUERY_SQL($sql,"artica_events");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$ipto=$ligne["IP_FROM"];
		$hostname=gethostbyaddrCache($ipto);
		$c++;
		$q->QUERY_SQL("UPDATE ipband SET IP_FROM_HOST='$hostname' WHERE IP_FROM='$ipto'","artica_events");
		if(!$q->ok){squid_admin_mysql(2, "$q->mysql_error\n",__FUNCTION__,__FILE__,__LINE__,"ipband");}
		
	}	
	
	if($c>0){
		$took=$GLOBALS["CLASS_UNIX"]->distanceOfTimeInWords($t,time(),true);
		squid_admin_mysql(2, "$c Ip addresses resolved in $took",__FUNCTION__,__FILE__,__LINE__,"ipband");
	}
	
	
}


function start(){
	
	$sock=new sockets();
	$ipBandEnabled=$sock->GET_INFO("ipBandEnabled");
	if(!is_numeric($ipBandEnabled)){$ipBandEnabled=0;}
	if($ipBandEnabled==0){return;}	
	
	$GLOBALS["CLASS_UNIX"]=new unix();
	$q=new mysql();
	$sql="SELECT network FROM ipban";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(mysqli_num_rows($results)==0){return;}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$network=$ligne["network"];
		startnet($network);
	}
}

function stopnet($network){
	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$pid=pidnet($network);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		print("Stopping......: ".date("H:i:s")."ipband $network already stopped\n");
		return;
	}
	print("Stopping......: ".date("H:i:s")."ipband $network PID $pid\n");
	for($i=0;$i<6;$i++){
		if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){break;}
		$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid,9);
		$pid=pidnet($network);
	}

	$pid=pidnet($network);
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		print("Stopping......: ".date("H:i:s")."ipband $network success\n");
		return;
	}	
	print("Stopping......: ".date("H:i:s")."ipband $network failed\n");
	
}

function startnet($network){
	
	$pid=pidnet($network);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		print("Starting......: ".date("H:i:s")." ipband $network already exists PID $pid\n");
		return;
	}
	$netfile=netfile($network);
	$ipband=$GLOBALS["CLASS_UNIX"]->find_program("ipband");
	print("Starting......: ".date("H:i:s")." ipband $network ....\n");	
	$cmdline="$ipband eth0 -F -f \"net $network\" -a 300 -r 1800 -d 2 -o /var/log/ipband.$netfile -w /var/log/ipband.$netfile.html >/dev/null";
	shell_exec($cmdline);
	sleep(1);
	$pid=pidnet($network);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		print("Starting......: ".date("H:i:s")." ipband $network success\n");	
		return;
	}else{
		print("Starting......: ".date("H:i:s")." ipband $network failed\n");	
	}
	
}

function netfile($network){
	$netfile=str_replace(".", "", $network);
	$netfile=str_replace("/", "-", $netfile);	
	return $netfile;
}
function WriteMyLogs($text,$function,$file,$line){
	$mem=round(((memory_get_usage()/1024)/1000),2);
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	writelogs($text,$function,__FILE__,$line);
	$logFile="/var/log/artica-postfix/".basename(__FILE__).".log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>9000000){unlink($logFile);}
   	}
   	$date=date('m-d H:i:s');
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	if($GLOBALS["VERBOSE"]){echo "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n";}
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB][Task:{$GLOBALS["SCHEDULE_ID"]}]: [$function::$line] $text\n");
	@fclose($f);
}

function pidnet($network){
	$netfile=netfile($network);
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$pattern="ipband.+?$netfile";
	$cmd="$pgrep -l -f \"$pattern\" 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	
	exec("$pgrep -l -f \"$pattern\" 2>&1",$results);
	foreach ($results as $num=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(preg_match("#^([0-9]+)\s+#", $line,$re)){return $re[1];}
		if($GLOBALS["VERBOSE"]){echo "$line -> NO MATCH\n";}
	}
}