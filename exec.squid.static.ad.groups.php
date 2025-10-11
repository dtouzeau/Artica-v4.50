<?php
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");

if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){
		ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
		$GLOBALS["VERBOSE"]=true;
	}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
start_parse();

function start_parse(){
	if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
	$unix=new unix();
	if($GLOBALS["VERBOSE"]){"echo Loading done...\n";}
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pidfile);
	if(!$GLOBALS["FORCE"]){
		if($pid<100){$pid=null;}
		$unix=new unix();
		if($unix->process_exists($pid,basename(__FILE__))){if($GLOBALS["VERBOSE"]){echo "Already executed pid $pid\n";}return;}
		$timeexec=$unix->file_time_min($timefile);
		if($timeexec<10){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$ldap=new clladp();
	if(!$ldap->IsKerbAuth()){return;}


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
	$sql="SELECT ID,GroupName FROM webfilters_sqgroups WHERE `enabled`=1 AND `GroupType`='proxy_auth_statad'";
	$results = $q->QUERY_SQL($sql);
	$REFRESH=false;
	$Count=count($results);
	$UPDATED=array();
	foreach ($results as $index=>$ligne){
		if(parse_object($ligne["ID"],$ligne["GroupName"])){
			$UPDATED[]=$ligne["GroupName"];
			$REFRESH=true;
		}
		
		
	}
	
	
	
	if($REFRESH){
		squid_admin_mysql(2, "{reloading_proxy_service} after updating ". count($UPDATED)." Active Directory group(s)", @implode("\n", $UPDATED),__FILE__,__LINE__);
		$squid=$unix->LOCATE_SQUID_BIN();
		
		system("/etc/init.d/squid reload --force --script=exec.squid.static.ad.groups.php/".__LINE__);
		$sock=new sockets();
		$EnableTransparent27=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
		if($EnableTransparent27==1){
			if(is_file("/etc/init.d/squid-nat")){
				system("/etc/init.d/squid-nat reload --script=".basename(__FILE__));
			}
		}
	}
	
	
}


function parse_object($ID,$GroupName){
	
	$filename="/etc/squid3/acls/container_$ID.txt";
	$md5Source=md5_file($filename);
	$ad=new external_ad_search();
	$members=$ad->MembersFromGroupName($GroupName);
	$MembersCount=count($members);
	
	if($GLOBALS["VERBOSE"]){print_r($members);}
	
	if($MembersCount==0){
		squid_admin_mysql(1, "Group $GroupName return no member skiping task", null,__FILE__,__LINE__);
		return false;
	}
	squid_admin_mysql(2, "Group $GroupName have $MembersCount member(s)", null,__FILE__,__LINE__);
	
	@file_put_contents($filename, @implode("\n", $members)."\n");
	$md5Dest=md5_file($filename);
	
	if($GLOBALS["VERBOSE"]){echo "$filename: From \"$md5Source\" to \"$md5Dest\"\n";}
	
	if($md5Dest<>$md5Source){
		squid_admin_mysql(2, "Group $GroupName container have changed", null,__FILE__,__LINE__);
		return true;}
		
	return false;
}



