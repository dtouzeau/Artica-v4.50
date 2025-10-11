<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["RELOAD"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;}
if(preg_match("#--simule#",implode(" ",$argv))){$GLOBALS["SIMULE"]=true;$GLOBALS["SIMULE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["FORCE"]=true;}


if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--whitelist"){WhitelistHosts();exit();}
if($argv[1]=="--networks"){MyNetworks();exit();}
if($argv[1]=="--buildKeyView"){buildKeyView();exit();}
if($argv[1]=="--TESTKeyView"){TESTKeyView();exit();}
if($argv[1]=="--keyTable"){keyTable();exit();}




function build(){
	$EnableDKFilter     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDkimMilter"));
	$DisconnectDKFilter = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisconnectDKFilter");
    $conf               = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DkimMilterConfig")));
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}
	if($DisconnectDKFilter==1){return;}

	if($EnableDKFilter==0){return false;}
	
	if($conf["On-BadSignature"]==null){$conf["On-BadSignature"]="accept";}
	if($conf["On-NoSignature"]==null){$conf["On-NoSignature"]="accept";}
	if($conf["On-DNSError"]==null){$conf["On-DNSError"]="tempfail";}
	if($conf["On-InternalError"]==null){$conf["On-InternalError"]="accept";}

	if($conf["On-Security"]==null){$conf["On-Security"]="tempfail";}
	if($conf["On-Default"]==null){$conf["On-Default"]="accept";}
	if($conf["ADSPDiscard"]==null){$conf["ADSPDiscard"]="1";}
	if($conf["ADSPNoSuchDomain"]==null){$conf["ADSPNoSuchDomain"]="1";}
	if(trim($conf["SignOutgoing"])==null){$conf["SignOutgoing"]="1";}
	if(!is_numeric($conf["X-Header"])){$conf["X-Header"]=1;}		
	
	while (list ($key, $value) = each ($conf) ){
		if($value=="1"){$conf[$key]="yes";}
		if($value=="0"){$conf[$key]="no";}
		
	}

	
	if($conf["SignOutgoing"]=="yes"){
		$mode="s";
		echo "Starting......: ".date("H:i:s")." milter-dkim sign outgoing mails\n"; 
	}
	if($conf["VerifyIncoming"]=="yes"){
		$mode=$mode."v";
		echo "Starting......: ".date("H:i:s")." milter-dkim verify incoming mails\n";
	}

$ldap=new clladp();
$domains=$ldap->hash_get_all_domains();
while (list ($key, $value) = each ($domains) ){
	$dd[]=$key;
}
echo "Starting......: ".date("H:i:s")." milter-dkim ".count($dd)." local domains\n";
$Domain=@implode(",", $dd);
$f[]="ADSPDiscard			{$conf["ADSPDiscard"]}";
$f[]="ADSPNoSuchDomain		{$conf["ADSPNoSuchDomain"]}";
$f[]="AllowSHA1Only			no";
$f[]="AlwaysAddARHeader		no";
$f[]="AutoRestart			yes";
$f[]="AutoRestartCount		2";
$f[]="AutoRestartRate		10/1h";
$f[]="Background			Yes";
$f[]="BaseDirectory			/var/run/dkim-filter";
$f[]="BodyLengths			No";
$f[]="Canonicalization		simple/simple";
$f[]="ClockDrift			300 ";
$f[]="Diagnostics			yes";
$f[]="DNSTimeout			10";
$f[]="Domain				$Domain";
$f[]="EnableCoredumps		no";
$f[]="ExternalIgnoreList	/etc/mail/dkim/trusted-hosts";
$f[]="FixCRLF 				no";
$f[]="InternalHosts			/etc/mail/dkim/internal-hosts";
$f[]="KeepTemporaryFiles	no";
$f[]="KeyList 				/etc/mail/dkim/keylist";
$f[]="LogWhy				yes";
$f[]="MilterDebug			0";
$f[]="Mode					$mode";
$f[]="On-Default         	{$conf["On-Default"]}";
$f[]="On-BadSignature    	{$conf["On-BadSignature"]}";
$f[]="On-DNSError        	{$conf["On-DNSError"]}";
$f[]="On-InternalError   	{$conf["On-InternalError"]}";
$f[]="On-NoSignature     	{$conf["On-NoSignature"]}";
$f[]="On-Security        	{$conf["On-Security"]}";
$f[]="PeerList				/etc/mail/dkim/trusted-hosts";
$f[]="PidFile				/var/run/dkim-milter/dkim-milter.pid";
$f[]="Quarantine			No";
$f[]="#QueryCache			yes";
$f[]="RemoveARAll			No";
$f[]="RemoveOldSignatures	No";
$f[]="ReportAddress			postmaster@example.com";
$f[]="RequiredHeaders		No";
$f[]="Selector				default";
$f[]="SendADSPReports		No";
$f[]="SendReports			No";
$f[]="SignatureAlgorithm	rsa-sha256";
$f[]="SignatureTTL			0";
$f[]="Socket				local:/var/run/dkim-milter/dkim-milter.sock";
$f[]="StrictTestMode		no";
$f[]="Syslog				yes";
$f[]="SyslogFacility		mail";
$f[]="SyslogSuccess			yes";
$f[]="TemporaryDirectory	/var/tmp";
$f[]="UMask					022";
$f[]="UserID				postfix";
$f[]="X-Header				{$conf["X-Header"]}";
$f[]="";
/*
 *        KeyList (string)
Gives the location of  a  file  listing  rules  for signing with mul‐tiple   keys.    If  present, overrides any KeyFile
setting in the conifguration file.  The  file  named  here  should contain a set of lines of  the form
sender‐pattern:signing‐domain:keypath where sender‐pattern is  a  pattern  to   match   against message  senders
(with   the  special  character  "*" interpreted as "zero or more characters"), signing‐domain is the domain to   announce  as  
the signing   domain   when  generating signatures, and keypath is the path to the PEM‐formatted private key to  be   used for  signing messages  which  match the sender‐pattern.
 The selector used in the signature  will  be  the  filename  portion  of keypath.   
If  the file  referenced  by  keypath cannot be opened, the
filter will try again by appending ".pem" and then  ".private"  before giving  up
 */
//http://www.howtoforge.com/set-up-dkim-for-multiple-domains-on-postfix-with-dkim-milter-2.8.x-centos-5.3
@mkdir("/etc/dkim-milter",null,true);
@file_put_contents("/etc/dkim-milter/dkim-milter.conf",@implode("\n",$f));

keyTable();
WhitelistDomains();
WhitelistHosts();
MyNetworks();
echo "Starting......: ".date("H:i:s")." milter-dkim Apply permissions...\n";
shell_exec("/bin/chmod 755 /etc/mail/dkim >/dev/null 2>&1");
shell_exec("/bin/chmod 0770 /etc/mail/dkim/keys >/dev/null 2>&1");
shell_exec("/bin/chmod 0770 /etc/mail/dkim/keys/* >/dev/null 2>&1");
shell_exec("/bin/chmod 0770 /etc/mail/dkim/keys/*/* >/dev/null 2>&1");
shell_exec("/bin/chown -R postfix:postfix /etc/mail/dkim >/dev/null 2>&1");
echo "Starting......: ".date("H:i:s")." milter-dkim Apply permissions done...\n";
if($GLOBALS["RELOAD"]){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/dkim-milter/dkim-milter.pid");
	echo "Starting......: ".date("H:i:s")." milter-dkim reloading current PID=$pid\n";
	if($unix->process_exists($pid)){
		$kill=$unix->find_program("kill");
		echo "Starting......: ".date("H:i:s")." milter-dkim reloading PID $pid\n";
		unix_system_HUP($pid);
		}
	}

	
}
function keyTable(){
$unix=new unix();
$genkey=$unix->find_program("dkim-genkey");
$chown=$unix->find_program("chown");

if(!is_file($genkey)){
	echo "Starting......: ".date("H:i:s")." milter-dkim \"dkim-genkey\" no such binary found !\n";
	return;
}	

$file="/etc/mail/dkim/keylist";
@mkdir(dirname($file),null,true);

$ldap=new clladp();
$domainsH=$ldap->AllDomains();
if(is_array($domainsH)){
	while (list ($num, $DOMAIN) = each ($domainsH) ){
		$dir="/etc/mail/dkim/keys/$DOMAIN";
		if(!is_dir($dir)){
			echo "Starting......: ".date("H:i:s")." milter-dkim creating directory /etc/mail/dkim/keys/$DOMAIN\n";
			@mkdir("/etc/mail/dkim/keys/$DOMAIN",0755,true);
		}	
		if(!keyTableVerifyFiles($dir)){
			echo "Starting......: ".date("H:i:s")." milter-dkim generating TXT and private for $DOMAIN\n";
			$cmd="$genkey -D $dir/ -d $DOMAIN -s default";
			system($cmd);
			shell_exec("/bin/cp $dir/default.private $dir/default");
		}else{
			echo "Starting......: ".date("H:i:s")." milter-dkim TXT and private for $DOMAIN OK\n";
		}
		
		shell_exec("$chown -R postfix:postfix $dir >/dev/null 2>&1");
		$keyTable[]="*@$DOMAIN:$DOMAIN:/etc/mail/dkim/keys/$DOMAIN/default";
	
	}
}else{
	echo "Starting......: ".date("H:i:s")." milter-dkim generating No domains set\n";
}
	
	if(@file_put_contents("/etc/mail/dkim/keylist",@implode("\n",$keyTable))){
			echo "Starting......: ".date("H:i:s")." milter-dkim generating keylist done...\n";
	}else{
		echo "Starting......: ".date("H:i:s")." milter-dkim FAILED generating keylist done...\n";
	}
	
	
	
}

function WhitelistDomains(){
	
	$sql="SELECT * FROM spamassassin_dkim_wl ORDER BY ID DESC";
	$q=new mysql();
	$results=$q->QUERY_SQL($sql,"artica_backup");


	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){	
	$f[]=$ligne["domain"];
	}
	
	@file_put_contents("/etc/mail/dkim/trusted-domains",@implode("\n",$f));
	echo "Starting......: ".date("H:i:s")." milter-dkim generating trusted domains ". count($f)." entries done...\n";
}

function keyTableVerifyFiles($dir){
	if(!is_file("$dir/default.private")){return false;}
	if(!is_file("$dir/default.txt")){return false;}
	if(!is_file("$dir/default")){return false;}
	return true;
}

function WhitelistHosts(){  
	$sock=new sockets();
	$q=new mysql();

	$ip=new networking();
	
	
	$f[]="127.0.0.1";
	$already["127.0.0.1"]=true;
	$sql="SELECT * FROM postfix_whitelist_con";
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(isset($already[$ligne["ipaddr"]])){continue;}
		if(isset($already[$ligne["hostname"]])){continue;}
		
		echo "Starting......: ".date("H:i:s")." milter-dkim Trusted Host \"{$ligne["ipaddr"]}\" FROM postfix_whitelist_con\n";
		echo "Starting......: ".date("H:i:s")." milter-dkim Trusted Host \"{$ligne["hostname"]}\" FROM postfix_whitelist_con\n";
		$f[]=$ligne["ipaddr"];
		if(strpos($ligne["hostname"], "*")==0){$f[]=$ligne["hostname"];}
		
		$already[$ligne["hostname"]]=true;
		$already[$ligne["ipaddr"]]=true;
		
	}
	
	$allips=$ip->ALL_IPS_GET_ARRAY();
	
	while (list ($ip, $line) = each ($allips) ){
		if(isset($already[$ip])){continue;}
		$already[$ip]=true;
		echo "Starting......: ".date("H:i:s")." milter-dkim Trusted Host \"$ip\" FROM ALL_IPS_GET_ARRAY\n";
		$f[]=$ip;
		$hostname=gethostbyaddr($ip);
		if(isset($already[$hostname])){continue;}
		$already[$hostname]=true;
		echo "Starting......: ".date("H:i:s")." milter-dkim Trusted Host \"$hostname\" FROM ALL_IPS_GET_ARRAY\n";
		$f[]=$hostname;
	}
	
	@mkdir("/etc/mail/dkim",0755,true);
 	@file_put_contents("/etc/mail/dkim/trusted-hosts",@implode("\n",$f));
 	echo "Starting......: ".date("H:i:s")." milter-dkim generating trusted hosts ". count($f)." entries done...\n";
}

function MyNetworks($trust=1){

		$ldap=new clladp();
		$nets=$ldap->load_mynetworks();
		$q=new mysql();
		$nets[]="127.0.0.0/8";
		while (list ($num, $network) = each ($nets) ){$cleaned[$network]=$network;}
		unset($nets);
		while (list ($network, $network2) = each ($cleaned) ){$nets[]=$network;}	
		
	$t=explode("\n", @file_get_contents("/etc/hosts"));
	while (list ($num, $line) = each ($t) ){
		if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+\s+(.+?)\s+#", $line,$re)){
			echo "Starting......: ".date("H:i:s")." milter-dkim Internal Host \"{$re[1]}\"\n";
			$nets[]=$re[1];
			$already[$re[1]]=true;
		}else{
			if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." milter-dkim DEBUG HOSTS: $line, No match\n";}
		}
	}
	
	$sql="SELECT *  FROM `postfix_multi` WHERE `key` = 'myhostname'";	
	$results=$q->QUERY_SQL($sql,"artica_backup");
	if(!$q->ok){echo "$q->mysql_error\n";}
	while($ligne=mysqli_fetch_array($results,MYSQLI_ASSOC)){
		if(isset($already[$ligne["ip_address"]])){continue;}
		if(isset($already[$ligne["value"]])){continue;}
		echo "Starting......: ".date("H:i:s")." milter-dkim Internal Host \"{$ligne["ip_address"]}\"\n";
		echo "Starting......: ".date("H:i:s")." milter-dkim Internal Host \"{$ligne["value"]}\"\n";		
		$nets[]=$ligne["ip_address"];
		$nets[]=$ligne["value"];
		$already[$ligne["ip_address"]]=true;
		$already[$ligne["value"]]=true;
	}		
		
		echo "Starting......: ".date("H:i:s")." milter-dkim generating internal hosts ". count($nets)." entries done...\n";
		@file_put_contents("/etc/mail/dkim/internal-hosts",@implode("\n",$nets));
}

function buildKeyView(){
$ldap=new clladp();
$domainsH=$ldap->AllDomains();
if(is_array($domainsH)){
	while (list ($num, $DOMAIN) = each ($domainsH) ){
		$file="/etc/mail/dkim/keys/$DOMAIN/default.txt";
		if(is_file($file)){
			$array[$DOMAIN]=@file_get_contents($file);	
		}
	
}
}

@file_put_contents("/etc/mail/dkim.domains.key",base64_encode(serialize($array)));


}
function TESTKeyView(){
	$unix=new unix();
	$opendkim=$unix->find_program("dkim-testkey");
	if(!is_file($opendkim)){return ;}
$ldap=new clladp();
$domainsH=$ldap->AllDomains();
if(is_array($domainsH)){
	while (list ($num, $DOMAIN) = each ($domainsH) ){
		unset($results);
		exec("$opendkim -d $DOMAIN -s default 2>&1",$results);
		$array[$DOMAIN]=@implode("\n",$results);
	}
}

@file_put_contents("/etc/mail/dkim.domains.tests.key",base64_encode(serialize($array)));


}

 

?>