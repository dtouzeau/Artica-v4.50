<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="DNS server DNSMasq";
$GLOBALS["NOCHECK"]=false;
$GLOBALS["COMMANDLINE"]=@implode($argv, " ");
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--nocheck#",implode(" ",$argv),$re)){$GLOBALS["NOCHECK"]=true;}
$GLOBALS["AS_ROOT"]=true;
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");



build();


function build(){
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$unix=new unix();
	
	if($unix->file_time_min($pidtime)<1){
		build_progress_computaccess("Please retry at least 1mn",110);
		exit();
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		build_progress_computaccess("Please retry at a process is executed",110);
		exit();
	}
	
	@file_put_contents($pidfile, getmypid());
	
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM computers_time WHERE `enabled`=1";
	
	
	build_progress_computaccess("Starting query",10);
	$results = $q->QUERY_SQL($sql);
	if(!$q->ok){
		build_progress_computaccess("MySQL error",110);
		echo $q->mysql_error;
		exit();
	}
	$ARRAYF["MONDAY"]=1;
	$ARRAYF["TUESDAY"]=2;
	$ARRAYF["WEDNESDAY"]=3;
	$ARRAYF["THURSDAY"]=4;
	$ARRAYF["FRIDAY"]=5;
	$ARRAYF["SATURDAY"]=6;
	$ARRAYF["SUNDAY"]=0;
	$c=0;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$MAC=$ligne["MAC"];
		$c++;
		reset($ARRAYF);
		build_progress_computaccess("Settings for $MAC",15);
		
		while (list ($DAY, $indexDay) = each ($ARRAYF)){
			$AM=explode(";",$ligne["{$DAY}_AM"]);
			$PM=explode(";",$ligne["{$DAY}_PM"]);
			$array[$MAC][$indexDay]["AM"][0]=$AM[0];
			$array[$MAC][$indexDay]["AM"][1]=$AM[1];
			$array[$MAC][$indexDay]["PM"][0]=$PM[0];
			$array[$MAC][$indexDay]["PM"][1]=$PM[1];
			
		}
	}
	
	
	@unlink("/etc/squid3/MacRestrictAccess.db");
	if($c>0){
		build_progress_computaccess("Building configuration",50);
		@file_put_contents("/etc/squid3/MacRestrictAccess.db", serialize($array));
		if(!$GLOBALS["nocheck"]){
			build_progress_computaccess("Checking configuration",55);
			if(!CheckConf()){return;}
		}
	}else{
		build_progress_computaccess("{no_activated_rule}",110);
		return;
	}
	build_progress_computaccess("{done}",100);
	
}

function CheckConf(){
	$unix=new unix();
	build_progress_computaccess("Check Proxy service",60);
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	if(!IsInConf()){
		build_progress_computaccess("Rebuild Proxy service",70);
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		if(!IsInConf()){build_progress_computaccess("{failed}",110);return false;}
		build_progress_computaccess("{done}",100);
		return true;
	}	
	
	build_progress_computaccess("{reloading_proxy_service}",70);
	system("$nohup $php /usr/share/artica-postfix/exec.squid.watchdog.php --reload --script=".basename(__FILE__)." >/dev/null 2>&1 &");
	return true;
	
}

function build_progress_computaccess($text,$pourc){



	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/exec.squid.computer.access.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function IsInConf(){

	$f=explode("\n",@file_get_contents("/etc/squid3/external_acls.conf"));
	foreach ($f as $index=>$line){
		if(preg_match("#external_acl_type ArticaRestrictAccess#i", $line)){return true;}
	}
	
}
