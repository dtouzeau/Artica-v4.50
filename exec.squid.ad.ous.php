<?php
$GLOBALS["FORCE"]=false;
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
   if($argv[1]=="--search"){searchOus($argv[2]);}
}

start();
function start(){
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
		if($timeexec<120){return;}
		$mypid=getmypid();
		@file_put_contents($pidfile,$mypid);
	}

	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	@unlink("/etc/artica-postfix/activedirectory-ou.db");
	$ldap=new clladp();
	if(!$ldap->IsKerbAuth()){return;}
	
$f=new external_ad_search();
$ALPHABET=array('a','b','c','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','1','2','3','4','5','6','7','8','9','0');


while (list ($num, $letter) = each ($ALPHABET) ){
	
	$hash=$f->find_DN("$letter*",0);


	for($i=0;$i<$hash["count"];$i++){
		if(isset($hash[$i]["samaccountname"][0])){$uid=$hash[$i]["samaccountname"][0];}
		if(isset($hash[$i]["uid"][0])){$uid=$hash[$i]["uid"][0];}
		if(isset($hash[$i]["dn"])){$dn=$hash[$i]["dn"];}
		if(strpos($uid, "$")>0){continue;}
		$tr=explode(",",$dn);
		$OUS=array();
		while (list ($num, $a) = each ($tr) ){
			if(!preg_match("#ou=(.+)$#i", $a,$re)){continue;}
			$OUS[]=$re[1];
			
		}
		if(count($OUS)==0){continue;}
		$USEROU=$OUS[0];
		if($USEROU==null){continue;}
		$USERS[$uid]=$USEROU;
	}
}
	@file_put_contents("/etc/artica-postfix/activedirectory-ou.db",serialize($USERS));
}


function searchOus($ouname){
    echo "Search $ouname\n";
    $ad=new external_ad_search();
    $ad->ListOus($ouname);

}
?>