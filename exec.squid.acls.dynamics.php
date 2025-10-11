<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["NOCHECK"]=false;
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["AD_PROGRESS"]=0;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_FIREHOL"]=false;
$GLOBALS["CLUSTER"]=false;
$GLOBALS["NO_EXTERNAL_SCRIPTS"]=false;
$GLOBALS["NO_VERIF_ACLS"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--cluster#",implode(" ",$argv))){$GLOBALS["CLUSTER"]=true;}
if(preg_match("#--noexternal-scripts#",implode(" ",$argv))){$GLOBALS["NO_EXTERNAL_SCRIPTS"]=true;}
if(preg_match("#--noverifacls#",implode(" ",$argv))){$GLOBALS["NO_VERIF_ACLS"]=true;}

include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.ntlm.inc');
include_once(dirname(__FILE__).'/ressources/class.http_access_defaults.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.useragents.inc');
include_once(dirname(__FILE__).'/ressources/class.tcp_outgoing_interface.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.ftp.inc");




xbuild();

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext (exec.squid.acls.dynamics.php)\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/squid.acls.dynamic.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}


function xbuild(){
	
	$q=new mysql_squid_builder();
	build_progress("{building_rules}",10);
	
	$sql="SELECT ID,GroupName FROM webfilters_sqgroups WHERE GroupType = 'dynamic_acls' AND enabled=1";
	$results=$q->QUERY_SQL($sql);
	
	if(!$q->ok){
		echo $q->mysql_error."\n";
		build_progress("MySQL Error",110);
		return;
	}
	$TOTAL=mysqli_num_rows($results);
	echo "$TOTAL objects...\n";
	if($TOTAL==0){
		build_progress("{no_rule}",110);
		return;
	}
	
	$c=0;
	while ($ligne = mysqli_fetch_assoc($results)) {
		$GroupName=$ligne["GroupName"];
		echo "Building rule for object ID {$ligne["ID"]} ( $GroupName )\n";
		$c++;
		$prc=($c/$TOTAL)*100;
		if($prc>10){ if($prc<95){ build_progress("{building_rules} $GroupName",$prc); } }
		
		xbuild_objects($ligne["ID"]);
		
	}
	
	build_progress("{done} $c {rules}",100);
	
}

function xbuild_objects($gpid){
	$q=new mysql_squid_builder();
	$sql="SELECT * FROM webfilter_aclsdynamic WHERE enabled=1";
	$results=$q->QUERY_SQL($sql);
	
	@mkdir("/etc/squid3/acls/acls_dynamics",0755,true);
	@chown("/etc/squid3/acls/acls_dynamics", "squid");
	@chgrp("/etc/squid3/acls/acls_dynamics","squid");
	$filename="/etc/squid3/acls/acls_dynamics/Group{$gpid}";
	$L=array();
	while ($ligne = mysqli_fetch_assoc($results)) {
		$f=array();
		if(intval($ligne["maxtime"])>0){ if(time()>$ligne["maxtime"]){echo "Rule.{$ligne["ID"]}, expired, skip it...\n"; continue;} }
		$f[]=$ligne["ID"];
		$f[]=$ligne["type"];
		$f[]=$ligne["value"];
		$f[]=$ligne["maxtime"];
		$f[]=$ligne["duration"];
		echo "Rule.{$ligne["ID"]}, Type={$ligne["type"]}, {$ligne["value"]}\n";
		$L[]=@implode("</data>", $f);
	}

	@file_put_contents($filename, @implode("\n", $L));
	echo "$filename ".count($L)." objects...\n";
	
}


