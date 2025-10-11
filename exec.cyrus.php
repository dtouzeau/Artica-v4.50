<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["RECONFIGURE"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["RESTART"]=true;}

if($argv[1]=="--kinit"){kinit_config();exit;}
if($argv[1]=="--DirectorySize"){DirectorySize();exit;}
if($argv[1]=="--cyrusadm-ad"){ExtractCyrusAdmAD();exit;}
if($argv[1]=="--imaps-failed"){cyrus_ssl_error();exit;}
if($argv[1]=="--DB_CONFIG"){DB_CONFIG();exit;}
if($argv[1]=="--listmailboxes"){listmailboxes();exit;}
if($argv[1]=="--listmailboxes-domains"){listmailboxes($argv[2]);exit;}
if($argv[1]=="--delete-mailbox"){delete_mailbox($argv[2]);exit;}




function listmailboxes(){

	$unix=new unix();
	
	$cachefile="∕etc/artica-postfix/listmailboxes.db";
	$ldap=new clladp();
	$cyruspass=$ldap->CyrusPassword();	
	if($cyruspass==null){echo "{warning} cyrus password is not set!!!\n";}
	$cmd="/usr/share/artica-postfix/bin/cyrus-admin.pl -u cyrus -p \"$cyruspass\" --list 2>&1";
	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}
	exec($cmd,$results);
	echo @implode("\n", $results);


}
function listmailboxes_domains($domain){

	$unix=new unix();
	
	$cachefile="∕etc/artica-postfix/listmailboxes.db";
	$ldap=new clladp();
	$cyruspass=$ldap->CyrusPassword();	
	$cmd="/usr/share/artica-postfix/bin/cyrus-admin.pl -u cyrus@$domain -p \"$cyruspass\" --list 2>&1";
	exec($cmd,$results);
	echo @implode("\n", $results);


}



function delete_mailbox($user){
	$sock=new sockets();
	$cyradm="cyrus";
	
	$CyrusEnableImapMurderedFrontEnd=$sock->GET_INFO("CyrusEnableImapMurderedFrontEnd");
	if($CyrusEnableImapMurderedFrontEnd==1){
		shell_exec("/usr/share/artica-postfix/bin/artica-install --delete-mailbox $user");
		return;
	}
	$ldap=new clladp();
	$cyruspass=$ldap->CyrusPassword();	
	if(preg_match("#(.+?)@(.+)#", $user,$re)){
		$cyradm="$cyradm@{$re[2]}";
		$user=$re[1];
	}


	$cmd="/usr/share/artica-postfix/bin/cyrus-admin.pl -u cyrus -p \"$cyruspass\" -m \"$user\" --delete 2>&1";
	exec($cmd,$results);

}


function ExtractCyrusAdmAD(){
	$sock=new sockets();
	$CyrusToAD=$sock->GET_INFO("CyrusToAD");
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	if($CyrusToAD==null){$CyrusToAD=0;}
	@unlink("/etc/artica-postfix/CyrusAdmPlus");
	if($CyrusToAD==0){return;}
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusToADConfig")));
    if(!is_array($array)){$array=array();}
	if($EnableSambaActiveDirectory==1){
		$newconf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
		$array["domain"]=$newconf["ADDOMAIN"];
		$array["servername"]=$newconf["ADSERVER"];
		$array["admin"]=$newconf["ADADMIN"];
		$array["password"]=$newconf["PASSWORD"];
	}

	echo "Starting......: ".date("H:i:s")." cyrus-imapd new Active Directory Administrator ({$array["admin"]})\n";
	@file_put_contents("/etc/artica-postfix/CyrusAdmPlus",$array["admin"]);
	
}


function kinit_config(){
	
	
	$sock=new sockets();
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}	
	if($EnableKerbAuth==1){echo "Enable Kerberos authentification is enabled, Aborting\n";}
	$CyrusToAD=$sock->GET_INFO("CyrusToAD");
	$EnableSambaActiveDirectory=$sock->GET_INFO("EnableSambaActiveDirectory");
	if(!is_numeric($EnableSambaActiveDirectory)){$EnableSambaActiveDirectory=0;}
	if($CyrusToAD==null){$CyrusToAD=0;}
	if($CyrusToAD==0){DisablePamd();return;}
	EnablePamd();
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusToADConfig")));
	if($EnableSambaActiveDirectory==1){
		$newconf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaAdInfos")));
		$array["domain"]=$newconf["ADDOMAIN"];
		$array["servername"]=$newconf["ADSERVER"];
		$array["admin"]=$newconf["ADADMIN"];
		$array["password"]=$newconf["PASSWORD"];
	}
	
	
	
	
	$default_realm=strtoupper($array["domain"]);
	$servername=strtolower($array["servername"]);
	
$f[]="[logging]";
$f[]="	default = FILE:/var/log/krb5libs.log";
$f[]="	kdc = FILE:/var/log/krb5kdc.log";
$f[]="	admin_server = FILE:/var/log/kadmind.log";
$f[]="[libdefaults]";
$f[]="	clockskew = 300";
$f[]="	ticket_lifetime = 24h";
$f[]="	forwardable = yes";
$f[]="	default_realm = $default_realm";
$f[]="[realms]";
$f[]="	$default_realm = {";
$f[]="		kdc = $servername";
$f[]="		default_domain = $default_realm";
$f[]="		kpasswd_server = $servername";
$f[]="}";
$f[]="";
$f[]="[domain_realm]";
$f[]="	.$default_realm = $default_realm";
$f[]="[appdefaults]";
$f[]="pam = {";
$f[]="	debug = false";
$f[]="	ticket_lifetime = 36000";
$f[]="	renew_lifetime = 36000";
$f[]="	forwardable = true";
$f[]="	krb4_convert = false";
$f[]="}";
$f[]="";
	
@file_put_contents("/etc/krb5.conf",@implode("\n",$f));	
RunKinit($array["admin"]."@".strtoupper($array["domain"]),$array["password"]);
if($GLOBALS["RELOAD"]){
	shell_exec("/etc/init.d/artica-postfix restart saslauthd");
}
	
}

function EnablePamd(){
$f[]="# PAM configuration file for Cyrus IMAP service";
$f[]="# \$Id: imap.pam 5 2005-03-12 23:19:45Z sven $";
$f[]="#";
$f[]="# If you want to use Cyrus in a setup where users don't have";
$f[]="# accounts on the local machine, you'll need to make sure";
$f[]="# you use something like pam_permit for account checking.";
$f[]="#";
$f[]="# Remember that SASL (and therefore Cyrus) accesses PAM"; 
$f[]="# modules through saslauthd, and that SASL can only deal with";
$f[]="# plaintext passwords if PAM is used.";
$f[]="#";
$f[]="auth     sufficient pam_krb5.so no_user_check validate";
$f[]="account  sufficient pam_permit.so";
@file_put_contents("/etc/pam.d/imap",@implode("\n",$f));
@file_put_contents("/etc/pam.d/smtp",@implode("\n",$f));


}

function DisablePamd(){
	
$f[]="# PAM configuration file for Cyrus IMAP service";
$f[]="# \$Id: imap.pam 5 2005-03-12 23:19:45Z sven $";
$f[]="#";
$f[]="# If you want to use Cyrus in a setup where users don't have";
$f[]="# accounts on the local machine, you'll need to make sure";
$f[]="# you use something like pam_permit for account checking.";
$f[]="#";
$f[]="# Remember that SASL (and therefore Cyrus) accesses PAM"; 
$f[]="# modules through saslauthd, and that SASL can only deal with";
$f[]="# plaintext passwords if PAM is used.";
$f[]="#";
$f[]="@include common-auth";
$f[]="@include common-account";
@file_put_contents("/etc/pam.d/imap",@implode("\n",$f));
@unlink("/etc/pam.d/smtp");
}


function RunKinit($username,$password){
$unix=new unix();
$kinit=$unix->find_program("kinit");
$klist=$unix->find_program("klist");
$echo=$unix->find_program("echo");
if(!is_file($kinit)){logskinit("Unable to stat kinit");return;}

exec("$klist 2>&1",$res);
$line=@implode("",$res);


if(strpos($line,"No credentials cache found")>0){
	unset($res);
	logskinit($line." -> initialize..");
	exec("$echo \"$password\"|$kinit {$username} 2>&1",$res);
	foreach ($res as $num=>$a){
		if(preg_match("#Password for#",$a,$re)){unset($res[$num]);}
	}	
	$line=@implode("",$res);	
	if(strlen(trim($line))>0){
		logskinit($line." -> Failed..");
		return;
	}
	unset($res);
	exec("$klist 2>&1",$res);	
}

foreach ($res as $num=>$a){	if(preg_match("#Default principal:(.+)#",$a,$re)){logskinit(trim($re[1])." -> success");break;}}
	

	
}

function logskinit($text=null){
	$file="/var/log/artica-postfix/kinit.log";
	@mkdir(dirname($file));
	$logFile=$file;
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
   	if (is_file($logFile)) { 
   		$size=filesize($logFile);
   		if($size>1000000){unlink($logFile);}
   	}
   	echo "$text\n";
   	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	$date=date("Y-m-d H:i:s");
	@fwrite($f, "$date $text\n");
	@fclose($f);
}


function cyrus_ssl_error(){
	$unix=new unix();
	$users=new usersMenus();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	
	if(!is_file($users->maillog_path)){return null;}
	
	exec("$tail -n 800 $users->maillog_path|$grep imaps 2>&1",$results);
	if(count($results)>1){
		$text="Artica has detected an error in cyrus when connecting to the SSL imap port\n";
		$text=$text."You should rebuild your ssl certificate or try to investigate on the events below:\n-------------------------\n";
		$text=$text.@implode("\n",$results); 
		echo $text."\n";
		$unix->send_email_events("cyrus-imap: IMAP SSL error");
		
	}
	
	
}

function DB_CONFIG(){
	$unix=new unix();
	$configdirectory=$unix->IMAPD_GET("configdirectory")."/db";
	$sock=new sockets();
	$datas=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CyrusDBConfig")));
	$EnableCyrusDBConfig=$sock->GET_INFO("EnableCyrusDBConfig");
	
	if($datas["set_cachesize"]==null){$datas["set_cachesize"]="2 524288000 1";}
	if(!is_numeric($datas["set_lg_regionmax"])){$datas["set_lg_regionmax"]="1048576";}
	if(!is_numeric($datas["set_lg_bsize"])){$datas["set_lg_bsize"]="2097152";}
	if(!is_numeric($datas["set_lg_max"])){$datas["set_lg_max"]="4194304";}
	if($EnableCyrusDBConfig<>1){
		if(is_file("$configdirectory/DB_CONFIG")){@unlink("$configdirectory/DB_CONFIG");}
		return;
	}
	
$f[]="set_cachesize {$datas["set_cachesize"]}";
$f[]="set_lg_regionmax {$datas["set_lg_regionmax"]}";
$f[]="set_lg_bsize {$datas["set_lg_bsize"]}";
$f[]="set_lg_max {$datas["set_lg_max"]}";
$f[]="set_tx_max 200";

$f[]="";

echo "Starting......: ".date("H:i:s")." cyrus-imapd define $configdirectory/DB_CONFIG\n";
@file_put_contents("$configdirectory/DB_CONFIG",@implode("\n",$f));
	
	
}

function DirectorySize(){
	$unix=new unix();
	$pid_path="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__;
	$timePath="/etc/artica-postfix/croned.1/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=@file_get_contents($pid_path);
	if(!$GLOBALS["FORCE"]){
		if($unix->process_exists($pid)){exit();}
		$childpid=posix_getpid();
		@file_put_contents($pid_path,$childpid);
	
	
		if(system_is_overloaded()){
			if($GLOBALS["VERBOSE"]){echo "{OVERLOADED_SYSTEM}.\n";}
			return;
		}
	}
	
	
	$filetim=$unix->file_time_min($timePath);
	
	if($GLOBALS["VERBOSE"]){echo "Time File: $timePath ({$filetim}Mn)\n";}
	
	if(!$GLOBALS["FORCE"]){if($filetim<240){return;}}
	$partition_default=$unix->IMAPD_GET("partition-default");
	if(is_link($partition_default)){$partition_default=readlink($partition_default);}
	@file_put_contents($timePath, time());
	
	
	
	
	if($GLOBALS["VERBOSE"]){echo "partition_default = $partition_default\n";}
	artica_mysql_events(2,"Starting calculate - $partition_default - disk size",null,__FILE__,"mailbox");
	
	if(strlen($partition_default)<3){return;}
	if(!is_dir($partition_default)){return;}
	
	$currentsize=(($unix->DIRSIZE_BYTES($partition_default)/1024)/1024);
	$PartInfo=$unix->DIRPART_INFO($partition_default);
	$totalMB=$PartInfo["TOT"];
	$totalMB=round($totalMB/1048576);
	if($GLOBALS["VERBOSE"]){echo "partition_default = {$currentsize}MB/{$totalMB}MB\n";}
	
	
	
	$sock=new sockets();
	$currentsize=round($currentsize);
	$sock->SET_INFO("CyrusImapPartitionDefaultSize",$currentsize);
	$sock->SET_INFO("CyrusImapPartitionDefaultSizeTime",time());
	$sock->SET_INFO("CyrusImapPartitionDiskSize",$totalMB);
	send_email_events("Mailboxes size on your server: $currentsize MB","Mailboxes size on your server: $currentsize MB","mailbox");
	
	if($partition_default=="/var/spool/cyrus/mail"){
		$sock->SET_INFO("CyrusImapPartitionDefaultDirSize",$currentsize);
		return;
	}
		
	$currentsize=(($unix->DIRSIZE_BYTES("/var/spool/cyrus/mail")/1024)/1024);
	$sock->SET_INFO("CyrusImapPartitionDefaultDirSize",$currentsize);
	
	
}
