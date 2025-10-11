<?php
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
$GLOBALS["ADDLOG"]="/var/log/artica-postfix/".basename(__FILE__).".log";
$users=new usersMenus();
exit();
if(!$users->SQUID_INSTALLED){exit();}
$file="/usr/share/artica-postfix/ressources/databases/UserAgents.txt";

	$system_is_overloaded=system_is_overloaded();
	if($system_is_overloaded){
		$unix=new unix();
		$unix->send_email_events("{OVERLOADED_SYSTEM}, UserAgents maintenance table task aborted", "Artica will wait a new better time...", "proxy");
		exit();
	}


if(!is_file($file)){exit();}
$pidfile="/etc/artica-postfix/".basename(__FILE__).".pid";
$unix=new unix();
if($unix->process_exists(trim(@file_get_contents($pidfile)))){
	writelogs("Another instance ". @file_get_contents($pidfile). " Exists... abort","MAIN",__FILE__,__LINE__);
	exit();
}
$pid=getmypid();
@file_put_contents($pidfile,$pid);

$time=file_time_min("/etc/artica-postfix/UserAgents.cache");
if($time<10080){exit();}


$f=@file_get_contents($file);

$md5=md5($f);
$oldMd5=md5(trim(@file_get_contents("/etc/artica-postfix/UserAgents.cache")));
writelogs("$md5 == $oldMd5","MAIN",__FILE__,__LINE__);
if($md5==$oldMd5){
	writelogs("No changes","MAIN",__FILE__,__LINE__);
	exit();
}
@file_put_contents("/etc/artica-postfix/UserAgents.cache","$md5");

$q=new mysql();
$q->BuildTables();
$datas=explode("\n",$f);
writelogs(count($f)." Lines to parse","MAIN",__FILE__,__LINE__);

foreach ($datas as $index=>$line){
	if(trim($line)==null){continue;}
	if(strpos($line,'*')==0){
	if(preg_match("#^([A-Za-z0-9\s\.]+)#",$line,$re)){
		$key=$re[1];
		echo $key."\n";
		continue;
	}}
	
	if(preg_match("#\s+\*(.+)#",$line,$re)){
		$array[$key][md5($re[1])]=$re[1];
		continue;
	}
	
}

if(!is_array($array)){exit();}
$ct=0;
$sqlintro="INSERT INTO UserAgents(unique_key,browser,string) VALUES ";
while (list ($prodct, $newarray) = each ($array) ){
	while (list ($unique_key, $string) = each ($newarray) ){
		$fi[]="('$unique_key','$prodct','$string')";
		usleep(20000);
		$ct++;
	
		if($ct>500){
			$ct=0;
			$sql=$sqlintro.@implode(",",$fi);
			$q->QUERY_SQL($sql,"artica_backup");
			unset($fi);
			$fi=array();
		}
		
	}
	
}

if(is_array($fi)){
		writelogs(count($fi)." queries","MAIN",__FILE__,__LINE__);
		$sql=$sqlintro.@implode(",",$fi);
		$q->QUERY_SQL($sql,"artica_backup");
}





?>