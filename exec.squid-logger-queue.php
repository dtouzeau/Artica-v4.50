<?php

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

$GLOBALS["OUTPUT"]=true;

$unix=new unix();
$pids=$unix->PIDOF_PATTERN_ALL(__FILE__);
$mypid=getmypid();
echo "[$mypid]:". count($pids)." processe(s) found...\n";
if(count($pids)>1){
	foreach ($pids as $i=>$y){echo "[$mypid]: Found process $i -> Die()\n";}
	exit();
}

scan_logger();
scan_blocker();
scan_ua();

function scan_logger(){
	// /home/artica/squid-blocked-queue
	$BaseWorkDir="/home/artica/squid-logger-queue";
	if (!$handle = opendir($BaseWorkDir)) {return;}
	$family=new squid_familysite();
	$q=new postgres_sql();
	if(!$q->create_v4_proxy_tables()){
		squid_admin_mysql(0, "Failed to create v4 tables", null,__FILE__,__LINE__);
		exit();
	}
	
	$unix=new unix();
	$GetUniqueID=$unix->GetUniqueID();
	$prefix="INSERT INTO proxy_traffic (zdate,member,category,sitename,familysite,proxyname,size,rqs) VALUES";
	$s=array();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		
		if($unix->file_time_min($targetFile)>2880){@unlink($targetFile);continue;}
		
		if(!preg_match("#^([0-9]+)\.time$#", $filename,$re)){echo "Scanning:False: $filename\n";continue;}
		$timestamp=$re[1];
		$sDate=date("Y-m-d H:i:00",$timestamp);
		
		echo "Scanning: $targetFile ($sDate)\n";
		
		$data=unserialize(@file_get_contents($targetFile));
		if(count($data)==0){echo "$targetFile -> no data\n";@unlink($targetFile);continue;}
		
		foreach ($data as $md5=>$zline){
			$TB=explode("|",$zline);
			$sitename=$TB[0];
			$username=trim($TB[1]);
			$sizebytes=intval($TB[2]);
			$rqs=intval($TB[3]);
			$category=intval($TB[4]);
			$uuid=trim($TB[5]);
			if($uuid==null){$uuid=$GetUniqueID;}
			$familysite=$family->GetFamilySites($sitename);
			if(strlen($username)<3){continue;}
			$s[]="('$sDate','$username','$category','$sitename','$familysite','$uuid','$sizebytes','$rqs')";
		}
		
		
		@unlink($targetFile);
		if(count($s)>500){
			$q->QUERY_SQL($prefix." ".@implode(",", $s));
			if(!$q->ok){echo $q->mysql_error;exit();}
			$s=array();
		}
		
	}
		

	if(count($s)>0){
		$q->QUERY_SQL($prefix." ".@implode(",", $s));
		if(!$q->ok){echo $q->mysql_error;exit();}
		$s=array();
	}	
	
}
function scan_blocker(){
// 
	$BaseWorkDir="/home/artica/squid-blocked-queue";
	if (!$handle = opendir($BaseWorkDir)) {return;}
	$family=new squid_familysite();
	$q=new postgres_sql();
	
	$unix=new unix();
	$GetUniqueID=$unix->GetUniqueID();
	$prefix="INSERT INTO proxy_blocked (zdate,member,category,sitename,familysite,proxyname,rule,rqs) VALUES";
	$s=array();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
		
		if($unix->file_time_min($targetFile)>2880){@unlink($targetFile);continue;}
		
		if(!preg_match("#^([0-9]+)\.time$#", $filename,$re)){echo "Scanning:False: $filename\n";continue;}
		$timestamp=$re[1];
		$sDate=date("Y-m-d H:i:00",$timestamp);
		
		echo "Scanning: $targetFile ($sDate)\n";
		
		$data=unserialize(@file_get_contents($targetFile));
		if(count($data)==0){echo "$targetFile -> no data\n";@unlink($targetFile);continue;}
		
		foreach ($data as $md5=>$zline){
			$TB=explode("|",$zline);
			$sitename=$TB[0];
			$username=trim($TB[1]);
			$rule=intval($TB[2]);
			$rqs=intval($TB[3]);
			$category=intval($TB[4]);
			$uuid=trim($TB[5]);
			if($uuid==null){$uuid=$GetUniqueID;}
			$familysite=$family->GetFamilySites($sitename);
			if(strlen($username)<3){continue;}
			//sitename+"|"+KeyUser+"|"+str(rule)+"|"+str(rqs)+"|"+str(category)+"|"+proxyname
			//zdate,member,category,sitename,rule,proxyname,rqs
			$s[]="('$sDate','$username','$category','$sitename','$familysite','$uuid','$rule','$rqs')";
		}
		
		
		@unlink($targetFile);
		if(count($s)>500){
			$q->QUERY_SQL($prefix." ".@implode(",", $s));
			if(!$q->ok){echo $q->mysql_error;exit();}
			$s=array();
		}
		
	}
		

	if(count($s)>0){
		$q->QUERY_SQL($prefix." ".@implode(",", $s));
		if(!$q->ok){echo $q->mysql_error;exit();}
		$s=array();
	}	
	
}

function scan_ua(){
	//
	$BaseWorkDir="/home/artica/squid-ua-queue";
	if(!is_dir($BaseWorkDir)){@mkdir($BaseWorkDir,0755,true);}
	if (!$handle = opendir($BaseWorkDir)) {return;}
	
	$q=new postgres_sql();

	$unix=new unix();
	$GetUniqueID=$unix->GetUniqueID();
	$prefix="INSERT INTO proxy_ua (zdate,ua,rqs,size,proxyname) VALUES";
	$s=array();
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		$targetFile="$BaseWorkDir/$filename";
		if(is_dir($targetFile)){continue;}
	
		if($unix->file_time_min($targetFile)>2880){@unlink($targetFile);continue;}
	
		if(!preg_match("#^([0-9]+)\.time$#", $filename,$re)){echo "Scanning:False: $filename\n";continue;}
		$timestamp=$re[1];
		$sDate=date("Y-m-d H:i:00",$timestamp);
	
		echo "Scanning: $targetFile ($sDate)\n";
	
		$data=unserialize(@file_get_contents($targetFile));
		if(count($data)==0){
			echo "$targetFile -> no data NEXT...\n";
			@unlink($targetFile);
			continue;
		}
	
		foreach ($data as $ua=>$zline){
			$TB=explode("|",$zline);
			$rqs=$TB[0];
			$size=trim($TB[1]);
			$uuid=trim($TB[2]);
			if($uuid==null){$uuid=$GetUniqueID;}
			if(strlen($ua)<3){
				if($GLOBALS["VERBOSE"]){echo "'$ua' < 3 -> continue\n";}
				continue;}
			$ua=urldecode($ua);
			if(strlen($ua)>127){$ua=substr($ua,0,127);}
			$ua=mysql_escape_string2($ua);
			if($GLOBALS["VERBOSE"]){echo "'$sDate','$ua','$rqs','$size','$uuid'\n";}
			$s[]="('$sDate','$ua','$rqs','$size','$uuid')";
		}


		@unlink($targetFile);
		if(count($s)>500){
			$q->QUERY_SQL($prefix." ".@implode(",", $s));
			if(!$q->ok){echo $q->mysql_error;exit();}$s=array();
		}
	
	}


	if(count($s)>0){
	$q->QUERY_SQL($prefix." ".@implode(",", $s));
	if(!$q->ok){echo $q->mysql_error;exit();}
			$s=array();
	}

	}