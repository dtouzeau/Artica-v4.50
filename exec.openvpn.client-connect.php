<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.postgres.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

xrun();

function xrun(){
	
	

	$remove=false;
	$unix=new unix();
	$pidTime="/etc/artica-postfix/pids/exec.openvpn.client-connect.php.xrun.time";
	$pidFile="/etc/artica-postfix/pids/exec.openvpn.client-connect.php.xrun.pid";
	
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){return;}
	@file_put_contents($pidFile, getmypid());
	
	
	$xTime=$unix->file_time_min($pidTime);
	if(!$GLOBALS["VERBOSE"]){if($xTime<5){return;}}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	
	
	
	
	$filesnumber=$unix->COUNT_FILES("/home/openvpn-cnx");
	
	if($GLOBALS["VERBOSE"]){echo "$filesnumber items\n";}
	if($filesnumber==0){return;}
	if($filesnumber>1000){$remove=true;}
	
	$c=0;
	if (!$handle = opendir("/home/openvpn-cnx")) {return;}
	while (false !== ($filename = readdir($handle))) {
		if($filename=="."){continue;}
		if($filename==".."){continue;}
		if($GLOBALS["VERBOSE"]){echo "OPEN /home/openvpn-cnx/$filename item\n";}
		$targetFile="/home/openvpn-cnx/$filename";
		if(!preg_match("#\.sql$#", $filename)){@unlink($targetFile);continue;}
		if($remove){@unlink($targetFile);continue;}
		$c++;
		$f[]=$targetFile;
		$sq[]=trim(@file_get_contents("/home/openvpn-cnx/$filename"));
		if(count($sq)>500){break;}
	}

	if(count($sq)==0){return;}
	
	$q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
	//$q->VPN_TABLES();
	$sql="INSERT INTO openvpn_cnx (zdate,action,ipaddr,uid,ztime) VALUES ".@implode(",", $sq);
	$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo $q->mysql_error;
		return;
	}
	
	foreach ($f as $filepath){
		if($GLOBALS["VERBOSE"]){echo "remove $filepath item\n";}
		@unlink($filepath);
		
	}
	
}

