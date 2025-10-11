<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
	include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
	include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	
	if(system_is_overloaded(basename(__FILE__))){writelogs("{OVERLOADED_SYSTEM}, aborting task","MAIN",__FILE__,__LINE__);exit();}
	
	
if($argv[1]=="--users"){quota_users();exit();}
if($argv[1]=="--user"){quota_users();exit();}
if($argv[1]=="--quota-check"){quotaCheck();exit();}
if($argv[1]=="--quota-sql"){quotas_mysql();exit();}





function quota_users(){
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,__FILE__)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($time<10){	
			writelogs("Warning: Already running pid $pid since {$time}mn",__FUNCTION__,__FILE__,__LINE__);
			return;
		}else{
			unix_system_kill_force($pid);
		}
	}		
	@file_put_contents($pidfile, getmypid());	
	
	
	
	$setquota=$unix->find_program("setquota");
	if($GLOBALS["VERBOSE"]){echo "setquota:$setquota\n";}
	if(strlen($setquota)==0){return;}
	$sql="SELECT * FROM quotaroot WHERE `enabled`=1";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(system_is_overloaded(basename(__FILE__))){echo "Overloaded, die()";exit();}
	if(!$q->ok){echo $q->mysql_error."\n";}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$block_hardlimit=$ligne["block-hardlimit"]*1024;
		$block_softlimit=$ligne["block-softlimit"]*1024;
		$inode_hardlimit=$ligne["inode-hardlimit"];
		$inode_softlimit=$ligne["inode-softlimit"];
		$GraceTime=$ligne["GraceTime"];
		if(!is_numeric($GraceTime)){$GraceTime=1;}
		$GraceTime=$GraceTime*60;
		$uid=$ligne["uid"];
		$switch="u";
		if(preg_match("#@([0-9]+)#", $uid,$re)){$switch="g";$uid=$re[1];}
		
		
		$f[]="$setquota -$switch $uid $block_softlimit $block_hardlimit $inode_softlimit $inode_hardlimit -a";
		//$f[]="$setquota -u $uid -t $GraceTime -a";
		
		
	}
	
	$sql="SELECT * FROM quotaroot WHERE `enabled`=0";
	if($GLOBALS["VERBOSE"]){echo $sql."\n";}
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";}
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$uid=trim($ligne["uid"]);
		if($uid==null){continue;}
		$uid=$ligne["uid"];
		$switch="u";
		if(preg_match("#@([0-9]+)#", $uid,$re)){$switch="g";$uid=$re[1];}		
		$f[]="$setquota -$switch $uid 0 0 0 0 -a";
		//$f[]="$setquota -u $uid -t $GraceTime -a";
	}	
	
	foreach ($f as $num=>$ligne){
		if($GLOBALS["VERBOSE"]){echo $ligne."\n";}
		shell_exec($ligne);
	}
	quotas_mysql();
	
}

function quotas_mysql($nochek=false){
	$unix=new unix();
	$repquota=$unix->find_program("repquota");
	if(!$nochek){
		$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){return;}
		@file_put_contents($pidfile, getmypid());
	}

$prefix="INSERT IGNORE INTO repquota (uid,blockused,filesusers,blockLimit,Fileslimit) VALUES ";	
	
	exec("$repquota -a 2>&1",$results);
	
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#^(.+?)\s+--\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$re[1]=trim($re[1]);
			$blockLimit=$re[4];
			$blockused=$re[2];
			$filesusers=$re[5];
			$Fileslimit=$re[7];
			$sq[]="('{$re[1]}','$blockused','$filesusers','$blockLimit','$Fileslimit')";
			
		}
		
	}
	$results=array();
	
	exec("$repquota -g -a 2>&1",$results);
	
	
	foreach ($results as $num=>$ligne){
		if(preg_match("#^(.+?)\s+--\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$re[1]=trim($re[1]);
			$blockLimit=$re[4];
			$blockused=$re[2];
			$filesusers=$re[5];
			$Fileslimit=$re[7];
			$sq[]="('@{$re[1]}','$blockused','$filesusers','$blockLimit','$Fileslimit')";
			
		}
		
	}	
	
	
	$q=new mysql();
	if($GLOBALS["VERBOSE"]){$q->BuildTables();}
	if(!$q->TABLE_EXISTS("repquota", "artica_events")){$q->BuildTables();}
	$q->QUERY_SQL("TRUNCATE TABLE repquota",'artica_events');
	if(count($sq)>0){
		$q->QUERY_SQL($prefix.@implode(",", $sq),"artica_events");
	}
	
	
}


function quotaCheck(){
	$unix=new unix();
	$quotaoff=$unix->find_program("quotaoff");
	$quotacheck=$unix->find_program("quotacheck");
	$quotaon=$unix->find_program("quotaon");
	
	if(!is_file($quotaoff)){return;}
	if(!is_file($quotacheck)){return;}
	if(!is_file($quotaon)){return;}
	
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){return;}
	@file_put_contents($pidfile, getmypid());
	
	if($unix->file_time_min($filetime)<300){exit();}
	@unlink($filetime);
	@file_put_contents($filetime, time());
	shell_exec("$quotaoff -a");
	shell_exec("$quotacheck -avugm");
	shell_exec("$quotaon -a");
	
	
}
	
	
	