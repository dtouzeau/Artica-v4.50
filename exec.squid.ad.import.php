<?php
exit();
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.active.directory.inc');

if(system_is_overloaded(basename(__FILE__))){echo "Overloaded, die()";exit();}

$GLOBALS["FORCE"]=false;
$GLOBALS["AUTHCMD"]=null;
$GLOBALS["EXECUTOR"]="(none)";
$GLOBALS["PARAMS"]=implode(" ",$argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(count($argv)>1){if(preg_match("#--by=(.+)#",implode(" ",$argv,$re))){$GLOBALS["EXECUTOR"]=$re[1];}}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
importActivedirectoryusers();
	

function importActivedirectoryusers(){
	$sock=new sockets();
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==0){return ;}



	
	$unix=new unix();	
	$user=new settings_inc();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=$unix->get_pid_from_file($pidfile);

	if($unix->process_exists($pid,basename(__FILE__))){WriteMyLogs("Process $pid already exists",__FUNCTION__,__FILE__,__LINE__);return;}
	if(system_is_overloaded(basename(__FILE__))){WriteMyLogs("{OVERLOADED_SYSTEM}, aborting",__FUNCTION__,__FILE__,__LINE__);return;}	

	@file_put_contents($pidfile, getmypid());
	$TImeStamp=$unix->file_time_min($pidTime);
	if(!$GLOBALS["FORCE"]){if($TImeStamp<20){
		WriteMyLogs("Need 20mn, current={$TImeStamp}Mn executed by:{$GLOBALS["EXECUTOR"]} Params:{$GLOBALS["PARAMS"]}",__FUNCTION__,__FILE__,__LINE__);return;}}
	@unlink($pidTime);
	@file_put_contents($pidTime, time());	
	
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	$usermod=$unix->find_program("usermod");
	$chmod=$unix->find_program("chmod");		
	if(!is_file($netbin)){WriteMyLogs("net no such binary, aborting", __FUNCTION__, __FILE__, __LINE__);return ;}
	
	
	
	
	$array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];	
	$GLOBALS["AUTHCMD"]=" -U $adminname%$adminpassword";	
	
	
	getNetInfos();
	if(!isset($GLOBALS["LDAP_HOST"])){WriteMyLogs("Unable to get ldap infos, aborting", __FUNCTION__, __FILE__, __LINE__);return ;}
	GetUsersArray();

}

function GetUsersArray(){
	$unix=new unix();	
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	exec("$netbin ads search '(objectClass=user)' {$GLOBALS["AUTHCMD"]} 2>&1",$results);
	$array=array();
	foreach ($results as $index=>$line){
		if(preg_match("#distinguishedName: (.+)#", $line,$re)){$array[trim($re[1])]=true;continue;}
	}
	
	$groups=array();
	while (list ($dn, $line) = each ($array) ){
		$results=array();
		exec("$netbin ads search '(&(objectclass=user)(distinguishedName=$dn))' {$GLOBALS["AUTHCMD"]} 2>&1",$results);
		$givenname=null;
		$displayname=null;
		$samaccountname=null;
		$userprincipalname=null;
		$telephoneNumber=null;
		$mobile=null;
		$title=null;
		$sn=null;
		$ou=null;
		$mail=null;
		
		foreach ($results as $index=>$ligne){
			if(preg_match("#givenName: (.+)#",$ligne,$re)){$givenname=trim($re[1]);continue;}
			if(preg_match("#displayName: (.+)#",$ligne,$re)){$displayname=trim($re[1]);continue;}
			if(preg_match("#sAMAccountName: (.+)#",$ligne,$re)){$samaccountname=trim($re[1]);continue;}
			if(preg_match("#userPrincipalName: (.+)#",$ligne,$re)){$userprincipalname=trim($re[1]);continue;}
			if(preg_match("#telephoneNumber: (.+)#",$ligne,$re)){$telephoneNumber=trim($re[1]);continue;}
			if(preg_match("#mobile: (.+)#",$ligne,$re)){$mobile=trim($re[1]);continue;}
			if(preg_match("#title: (.+)#",$ligne,$re)){$title=trim($re[1]);continue;}
			if(preg_match("#sn: (.+)#",$ligne,$re)){$sn=trim($re[1]);continue;}
			if(preg_match("#mail: (.+)#",$ligne,$re)){$mail=trim($re[1]);continue;}
			if(preg_match("#memberOf: (.+)#",$ligne,$re)){$groups[$dn][]=trim($re[1]);}
		}
		
		if(strpos($samaccountname, "$")>0){continue;}
		$givenname=addslashes($givenname);
		$displayname=addslashes($displayname);
		$samaccountname=addslashes($samaccountname);
		$userprincipalname=addslashes($userprincipalname);
		$telephoneNumber=addslashes($telephoneNumber);
		$mobile=addslashes($mobile);
		$title=addslashes($title);
		$sn=addslashes($sn);
		
		if($GLOBALS["VERBOSE"]){echo $dn." `$samaccountname`\n";}
		$sql[]="('$dn','$samaccountname','$mail','$userprincipalname','$displayname','$ou','$telephoneNumber','$mobile','$givenname','$title','$sn')";	
		
	}
	
	
	if(count($sql)==0){return;}
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE activedirectory_users","artica_backup");
	$q->QUERY_SQL("TRUNCATE TABLE activedirectory_groups","artica_backup");
	$prefix="INSERT IGNORE INTO activedirectory_users (dn,samaccountname,mail,userprincipalname,displayname,ou,telephonenumber,mobile,givenname,title,sn) VALUES";
	$sqlfinal=$prefix." ".@implode(",", $sql);
	if($GLOBALS["VERBOSE"]){echo $sqlfinal."\n";}
	$q->QUERY_SQL($sqlfinal,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	$sql=array();
	
	while (list ($userdn, $groupdnArray) = each ($groups) ){
		while (list ($a, $groupdn) = each ($groupdnArray) ){
			if($GLOBALS["VERBOSE"]){echo "link $userdn -> $groupdn\n";}
			LinkGroups($groupdn,$userdn);
		}
	}
	checksGroups();
	
}

function checksGroups(){
	
	$sql="SELECT groupdn FROM activedirectory_groups GROUP BY groupdn";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");
	echo mysqli_num_rows($results)." groups to parse\n";
	$prefix="INSERT IGNORE INTO activedirectory_groupsNames (dn,groupname,UsersCount,description) VALUES";
	$unix=new unix();	
	$netbin=$unix->LOCATE_NET_BIN_PATH();
		
	
	
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$groupdn=utf8_decode($ligne["groupdn"]);
		
		$resultscmds=array();
		
		$cmd="$netbin ads search \"(&(objectclass=group)(distinguishedName=$groupdn))\" {$GLOBALS["AUTHCMD"]}";
		if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
		exec("$cmd 2>&1",$resultscmds);	
		$dn=addslashes($groupdn);
		$UsersCount=0;
		$description=null;
		$cn=null;
		while (list ($index, $line) = each ($resultscmds) ){
			if(preg_match("#cn: (.+)#",$line,$re)){$cn=addslashes(trim($re[1]));continue;}
			if(preg_match("#member: (.+)#",$line,$re)){$UsersCount++;continue;}
			if(preg_match("#description: (.+)#",$line,$re)){$description=addslashes(trim($re[1]));continue;}
			
		}
		$sqlA[]="('$dn','$cn',$UsersCount,'$description')";	
		
		
	}
	if(count($sqlA)==0){return;}

	
	$sqlfinal=$prefix." ".@implode(",", $sqlA);
	$q->QUERY_SQL($sqlfinal,"artica_backup");
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	$sql=array();	
	
}






function getNetInfos(){
	$unix=new unix();	
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	exec("$netbin ads info {$GLOBALS["AUTHCMD"]} 2>&1",$results);
	
	foreach ($results as $index=>$line){	
		if(preg_match("#LDAP server:(.+)#", $line,$re)){$GLOBALS["LDAP_HOST"]=trim($re[1]);continue;}
		if(preg_match("#Bind Path:(.+)#", $line,$re)){$GLOBALS["LDAP_SUFFIX"]=trim($re[1]);continue;}
		if(preg_match("#LDAP port.+?([0-9]+)#", $line,$re)){$GLOBALS["LDAP_PORT"]=trim($re[1]);continue;}
		
	}
}

function LinkGroups($groupdn,$userdn){
	$q=new mysql();
	$groupdn=utf8_encode($groupdn);
	$userdn=utf8_encode($userdn);
	$mdkey=md5("$groupdn$userdn");
	$groupdn=addslashes($groupdn);
	$userdn=addslashes($userdn);
	$sql="INSERT IGNORE INTO activedirectory_groups(groupdn,userdn,mdkey) VALUES ('$groupdn','$userdn','$mdkey')";
	$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo $q->mysql_error;}
	
	
}

function WriteMyLogs($text,$function,$file,$line){
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$mem=round(((memory_get_usage()/1024)/1000),2);
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
	@fwrite($f, "$date [{$GLOBALS["MYPID"]}][{$mem}MB]: [$function::$line] $text\n");
	@fclose($f);
}