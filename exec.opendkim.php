<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["TITLENAME"]="OpenDKIM service";
$GLOBALS["PID_FILE"]="/var/run/opendkim/opendkim.pid";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;}
if(preg_match("#--simule#",implode(" ",$argv))){$GLOBALS["SIMULE"]=true;$GLOBALS["SIMULE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--whitelist"){WhitelistHosts();exit();}
if($argv[1]=="--networks"){WhitelistHosts();exit();}
if($argv[1]=="--buildKeyView"){buildKeyView();exit();}
if($argv[1]=="--TESTKeyView"){TESTKeyView();exit();}
if($argv[1]=="--keyTable"){keyTable();exit();}
if($argv[1]=="--perms"){SetPermissions();exit();}
if($argv[1]=="--build-domains"){BuildAllDomains();exit();}
if($argv[1]=="--sync-domains"){SyncDomains();exit();}



echo "Could not understand {$argv[1]}...\n";

function SyncDomains(){
    echo "Build all domains...\n";
    BuildAllDomains();
    echo "Build...\n";
    build();
    echo "Restarting...\n";
    restart();

}
function build_progress_restart($text,$pourc){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/opendkim.restart.progress";
    echo "{$pourc}% $text\n";
    $cachefile=$GLOBALS["CACHEFILE"];
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}



function build(){
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$EnableDKFilter=$sock->GET_INFO("EnableDKFilter");
	$conf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenDKIMConfig")));
	if($EnableDKFilter==null){$EnableDKFilter=0;}
	$DisconnectDKFilter=$sock->GET_INFO("DisconnectDKFilter");
	if(!is_numeric($DisconnectDKFilter)){$DisconnectDKFilter=0;}
	if($DisconnectDKFilter==1){return;}
	
	if($conf["On-BadSignature"]==null){$conf["On-BadSignature"]="accept";}
	if($conf["On-NoSignature"]==null){$conf["On-NoSignature"]="accept";}
	if($conf["On-DNSError"]==null){$conf["On-DNSError"]="tempfail";}
	if($conf["On-InternalError"]==null){$conf["On-InternalError"]="accept";}

	if($conf["On-Security"]==null){$conf["On-Security"]="tempfail";}
	if($conf["On-Default"]==null){$conf["On-Default"]="accept";}
	if($conf["ADSPDiscard"]==null){$conf["ADSPDiscard"]="1";}
	if($conf["ADSPNoSuchDomain"]==null){$conf["ADSPNoSuchDomain"]="1";}	
	if($conf["DomainKeysCompat"]==null){$conf["DomainKeysCompat"]="0";}
	if($conf["OpenDKIMTrustInternalNetworks"]==null){$conf["OpenDKIMTrustInternalNetworks"]="1";}
	
	
	if(!is_file("/etc/mail/dkim/TrustedHosts")){
	    @touch("/etc/mail/dkim/TrustedHosts");
	    @chown("/etc/mail/dkim/TrustedHosts","postfix");
	    @chgrp("/etc/mail/dkim/TrustedHosts","postfix");
    }
	
if($conf["DomainKeysCompat"]==1){$f[]="DomainKeysCompat		  {$conf["DomainKeysCompat"]}";}
$f[]="AutoRestart             1";
$f[]="RequireSafeKeys         No";
$f[]="AutoRestartRate         10/1h";
$f[]="Canonicalization        relaxed/relaxed";
$f[]="OversignHeaders         From";
$f[]="PeerList                refile:/etc/mail/dkim/PeerList";
$f[]="ExemptDomains			  refile:/etc/mail/dkim/ExemptDomains";
$f[]="ExternalIgnoreList      refile:/etc/mail/dkim/TrustedHosts";
$f[]="InternalHosts           refile:/etc/mail/dkim/TrustedHosts";
$f[]="KeyTable                file:/etc/mail/dkim/keyTable";
$f[]="SigningTable            refile:/etc/mail/dkim/signingTable";
$f[]="LogWhy                  Yes";
$f[]="On-Default              {$conf["On-Default"]}";
$f[]="On-BadSignature         {$conf["On-BadSignature"]}";
$f[]="On-DNSError             {$conf["On-DNSError"]}";
$f[]="On-InternalError        {$conf["On-InternalError"]}";
$f[]="On-NoSignature          {$conf["On-NoSignature"]}";
$f[]="On-Security             {$conf["On-Security"]}";
$f[]="PidFile                 {$GLOBALS["PID_FILE"]}";
$f[]="SignatureAlgorithm      rsa-sha256";
$f[]="Socket                  local:/var/run/opendkim/opendkim.sock";
$f[]="Syslog                  Yes";
$f[]="SyslogSuccess           Yes";
$f[]="TemporaryDirectory      /var/tmp";
$f[]="UMask                   022";
$f[]="UserID                  postfix:postfix";
$f[]="X-Header                Yes";	

@file_put_contents("/etc/opendkim.conf",@implode("\n",$f));
    build_progress_restart("{reconfiguring}",60);
keyTable();

    build_progress_restart("{reconfiguring}",70);
    WhitelistHosts();
    build_progress_restart("{reconfiguring}",80);
    SetPermissions();
	
}

function SetPermissions(){
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, opendkim Apply permissions...\n";}
	@mkdir("/var/run/opendkim",0755,true);
	@mkdir(dirname($GLOBALS["PID_FILE"]),0755,true);
	$unix->SystemCreateGroup("dkim");

    shell_exec("$chown -R postfix:postfix /var/run/opendkim >/dev/null 2>&1");
    shell_exec("$chmod -R 0755 /var/run/opendkim >/dev/null 2>&1");

	shell_exec("$chown -R postfix:postfix /etc/mail/dkim >/dev/null 2>&1");
	shell_exec("$chown  postfix:postfix /etc/mail/dkim/keys >/dev/null 2>&1");
    shell_exec("$chown -R postfix:dkim /etc/mail/dkim/keys >/dev/null 2>&1");

	shell_exec("$chmod 0755 /etc/mail/dkim >/dev/null 2>&1");
	shell_exec("$chmod 0755 /etc/mail/dkim/keys >/dev/null 2>&1");
	shell_exec("$chmod -R 0700 /etc/mail/dkim/keys >/dev/null 2>&1");

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, opendkim Apply permissions done...\n";}
}


function GenerateDKIMByDomain($DOMAIN){
    $unix=new unix();
    $opendkim_genkey=$unix->find_program("opendkim-genkey");
    if(!is_file($opendkim_genkey)){$opendkim_genkey=$unix->find_program("opendkim-genkey.sh");}
    if(!is_file($opendkim_genkey)){echo "Starting......: ".date("H:i:s")." opendkim \"opendkim-genkey.sh\" no such binary found !\n";return false;}

    $chown=$unix->find_program("chown");
    $file="/etc/mail/dkim/keyTable";
    @mkdir(dirname($file),null,true);
    $dir="/etc/mail/dkim/keys/$DOMAIN";
    if(!is_dir($dir)){
        echo "Starting......: ".date("H:i:s")." OpenDKIM Creating directory /etc/mail/dkim/keys/$DOMAIN\n";
        @mkdir("/etc/mail/dkim/keys/$DOMAIN",0755,true);
    }

    $q = new lib_sqlite("/home/artica/SQLITE/postfix.db");

    $ligne=$q->mysqli_fetch_array("SELECT ID,private_key,dns_entry FROM dkimkeys WHERE domain='$DOMAIN'");
    $ID=intval($ligne["ID"]);
    if($ID>0){return true;}
    $cmd="$opendkim_genkey -r -h rsa-sha256 -D $dir/ -d $DOMAIN -s default -b 2048 >/dev/null 2>&1";
    shell_exec($cmd);

    if(!is_file("$dir/default.private")){
        echo "$DOMAIN: Failed to generate $dir/default.private\n";
        return;
    }
    if(!is_file("$dir/default.txt")){
        echo "$DOMAIN: Failed to generate $dir/default.txt\n";
        return;
    }

    $private_key=base64_encode(@file_get_contents("$dir/default.private"));
    $dns_entry=base64_encode(@file_get_contents("$dir/default.txt"));

    $q->QUERY_SQL("INSERT INTO dkimkeys (domain,private_key,dns_entry,generated) 
    VALUES ('$DOMAIN','$private_key','$dns_entry',1)");

    if(!$q->ok){echo $q->mysql_error;}

    return true;


}

function BuildAllDomains(){
    $unix = new unix();
    $opendkim_genkey = $unix->find_program("opendkim-genkey");

    if (!is_file($opendkim_genkey)) {
        $opendkim_genkey = $unix->find_program("opendkim-genkey.sh");
    }

    if (!is_file($opendkim_genkey)) {
        echo "Starting......: " . date("H:i:s") . " opendkim \"opendkim-genkey.sh\" no such binary found !\n";
        return;
    }


    $chown = $unix->find_program("chown");
    $file = "/etc/mail/dkim/keyTable";
    @mkdir(dirname($file), null, true);


    $q = new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results = $q->QUERY_SQL("SELECT * FROM transport_maps ORDER BY addr");

    $sql = "CREATE TABLE IF NOT EXISTS `dkimkeys` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`domain` TEXT UNIQUE,
		`private_key` TEXT,
		`dns_entry` TEXT,
		`generated` INTEGER )";

    $q->QUERY_SQL($sql);


    foreach ($results as $num => $ligne) {
        $item = trim($ligne["addr"]);
        echo "Starting......: " . date("H:i:s") . " opendkim checking $item\n";
        GenerateDKIMByDomain($item);
        $OtherDomains = unserialize(base64_decode($ligne["OtherDomains"]));
        if (count($OtherDomains) > 0) {
            foreach ($OtherDomains as $domain => $none) {
                echo "Starting......: " . date("H:i:s") . " opendkim checking $domain\n";
                GenerateDKIMByDomain($domain);
            }
        }
    }

    shell_exec("$chown -R postfix:postfix /etc/mail/dkim");
}

function keyTable(){
    $keyTable           = array();
    $signingTable       = array();
    $EnableDKFilter     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter"));
    if($EnableDKFilter==0){return false;}

    $q = new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results = $q->QUERY_SQL("SELECT * FROM dkimkeys WHERE domain!='*' ORDER BY domain");

    foreach ($results as $index=>$ligne){
        $DOMAIN=$ligne["domain"];
		$dir="/etc/mail/dkim/keys/$DOMAIN";
        echo "Starting......: ".date("H:i:s")." opendkim \"$DOMAIN\"\n";
		if(!is_dir($dir)){@mkdir("/etc/mail/dkim/keys/$DOMAIN",0644,true);}
		$private_key=base64_decode($ligne["private_key"]);
		@file_put_contents("/etc/mail/dkim/keys/$DOMAIN/default",$private_key);
		$keyTable[]="default._domainkey.$DOMAIN	$DOMAIN:default:/etc/mail/dkim/keys/$DOMAIN/default";
		$signingTable[]="*@$DOMAIN default._domainkey.$DOMAIN";
		
	}

	
	if(@file_put_contents("/etc/mail/dkim/keyTable",@implode("\n",$keyTable))){
			echo "Starting......: ".date("H:i:s")." opendkim generating keyTable done...\n";
	}else{
		echo "Starting......: ".date("H:i:s")." opendkim FAILED generating keyTable done...\n";
	}
	
	if(@file_put_contents("/etc/mail/dkim/signingTable",@implode("\n",$signingTable))){
		echo "Starting......: ".date("H:i:s")." opendkim generating signingTable done...\n";
	}else{
		echo "Starting......: ".date("H:i:s")." opendkim FAILED generating signingTable done...\n";	
	}
	
}



function keyTableVerifyFiles($dir){
	if(!is_file("$dir/default.private")){return false;}
	if(!is_file("$dir/default.txt")){return false;}
	if(!is_file("$dir/default")){return false;}
	return true;
}

function WhitelistHosts(){


    $conf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("OpenDKIMConfig")));
    if(!isset($conf["OpenDKIMTrustInternalNetworks"])){$conf["OpenDKIMTrustInternalNetworks"]=1;}

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL("SELECT * FROM dkimkeys WHERE domain!='*' ORDER by domain");



    foreach ($results as $num=>$ligne) {
        $domain = trim(strtolower($ligne["domain"]));
        $opendkim_white[] = $domain;
    }


        $opendkim_white[]="127.0.0.1";
        $opendkim_white[]="localhost";
        if($conf["OpenDKIMTrustInternalNetworks"]==1) {
        $q = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $results = $q->QUERY_SQL("SELECT * FROM networks_infos");
        foreach ($results as $ligne){
            $opendkim_white[] = trim($ligne["ipaddr"]);
        }
    }


    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    $results=$q->QUERY_SQL("SELECT addr FROM mynetworks");
    foreach ($results as $num=>$ligne) {
        $opendkim_white[] = trim($ligne["addr"]);
    }


    $pg=new postgres_sql();
    $sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='domain'";
    $results=$pg->QUERY_SQL($sql,"artica_backup");

    $ExemptDomains=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $domain=trim(strtolower($ligne["pattern"]));
        if($domain==null){continue;}
        if(preg_match("#regex:\s+#", $domain)){continue;}
        $ExemptDomains[]=$domain;
    }

    $PeerList=array();
    $ipClass=new IP();
    $sql="SELECT id,pattern FROM miltergreylist_acls WHERE method='whitelist' AND type='addr'";
    $results=$pg->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $ipaddr=trim($ligne["pattern"]);
        if($ipaddr==null){continue;}
        if($ipaddr=="127.0.0.1/8"){continue;}
        if($ipaddr=="127.0.0.1"){continue;}
        if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
        $PeerList[]=$ipaddr;
    }


    if(!is_dir("/etc/mail/dkim")){@mkdir("/etc/mail/dkim",0755,true);}
    @file_put_contents("/etc/mail/dkim/PeerList",@implode("\n",$PeerList));
    @file_put_contents("/etc/mail/dkim/ExemptDomains",@implode("\n",$ExemptDomains));
    echo "Starting......: ".date("H:i:s")." opendkim ".count($opendkim_white)." Trusted items\n";
    @file_put_contents("/etc/mail/dkim/TrustedHosts",@implode("\n",$opendkim_white));
    @chown("/etc/mail/dkim/TrustedHosts","postfix");
    @chgrp("/etc/mail/dkim/TrustedHosts","postfix");

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
	$opendkim=$unix->find_program("opendkim-testkey");
	$dig=$unix->find_program("dig");
	$chmod=$unix->find_program("chmod");
	if(!is_file($opendkim)){return ;}
$ldap=new clladp();
$domainsH=$ldap->AllDomains();
if(is_array($domainsH)){
	while (list ($num, $DOMAIN) = each ($domainsH) ){
		unset($results);
		
		shell_exec("$chmod -R 0600 /etc/mail/dkim/keys/$DOMAIN");
		$results[]="\n\n$dig TXT +short default._domainkey.$DOMAIN :\n-------------------------------\n";
		exec("$dig TXT +short default._domainkey.$DOMAIN 2>&1",$results);
		$results[]="\n\n";
		exec("$opendkim -d $DOMAIN -s default -k /etc/mail/dkim/keys/$DOMAIN/default 2>&1",$results);
		$array[$DOMAIN]=@implode("\n",$results);
	}
}

@file_put_contents("/etc/mail/dkim.domains.tests.key",base64_encode(serialize($array)));


}


function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/opendkim/opendkim.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("opendkim");
	return $unix->PIDOF($Masterbin);
}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
        build_progress_restart("{failed}",110);
		return;
	}
	@file_put_contents($pidfile, getmypid());

    build_progress_restart("{stopping_service}",10);
	if(!stop(true)){
        build_progress_restart("{stopping_service} {failed}",110);
        return;
    }
    build_progress_restart("{reconfiguring}",55);
	build();
	sleep(1);
    build_progress_restart("{starting_service}",75);
	if(!start(true)){
        build_progress_restart("{starting_service} {failed}",110);
        return;
    }
    build_progress_restart("{starting_service}",90);
	SetPermissions();
    build_progress_restart("{starting_service} {success}",100);

}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("opendkim");
    $prc=76;
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, opendkim not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return true;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		
		return true;
	}
	
	$EnableDKFilter=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDKFilter"));



	if($EnableDKFilter==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableDKFilter)\n";}
		stop();
		return true;
	}

	$chown=$unix->find_program("chown");
    if(!is_dir("/var/run/opendkim")){@mkdir("/var/run/opendkim",0755,true);}
    if(!is_dir("/etc/mail/dkim")){@mkdir("/etc/mail/dkim",0755,true);}
    if(!is_file("/etc/mail/dkim/TrustedHosts")){
        @touch("/etc/mail/dkim/TrustedHosts");
        @chown("/etc/mail/dkim/TrustedHosts","postfix");
        @chgrp("/etc/mail/dkim/TrustedHosts","postfix");
    }



    @chown("/var/run/opendkim","postfix");
    @chgrp("/var/run/opendkim","postfix");
	
	@unlink("/var/run/opendkim/opendkim.pid");
	$f[]=$Masterbin;
	$f[]="-p /var/run/opendkim/opendkim.sock";
	$f[]="-x /etc/opendkim.conf";
	$f[]="-u postfix";
	$f[]="-P {$GLOBALS["PID_FILE"]}";

	if(is_file("/var/run/opendkim/opendkim.sock")) {
        @unlink("/var/run/opendkim/opendkim.sock");
    }


	

	$cmd=@implode(" ", $f);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);
    build_progress_restart("{starting_service}",$prc++);
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(2);
        build_progress_restart("{starting_service}",$prc++);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
    build_progress_restart("{starting_service}",$prc++);
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		$unix->chown_func("postfix", "postfix","/var/run/opendkim/opendkim.sock");
		shell_exec("$chown -R postfix:postfix /etc/mail/dkim >/dev/null 2>&1");
		shell_exec("$chown -R postfix:postfix /etc/mail/dkim/keys >/dev/null 2>&1");
		shell_exec("$chown -R postfix:postfix /var/run/opendkim >/dev/null 2>&1");
        build_progress_restart("{starting_service}",$prc++);
        return true;

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		return false;
	}


}
function stop($aspid=false){
	if($GLOBALS["MONIT"]){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} runned by Monit, abort\n";}
		return;}
		$unix=new unix();
		if(!$aspid){
			$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
			$pid=$unix->get_pid_from_file($pidfile);
			if($unix->process_exists($pid,basename(__FILE__))){
				$time=$unix->PROCCESS_TIME_MIN($pid);
				if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Artica script already running PID $pid since {$time}mn\n";}
				return;
			}
			@file_put_contents($pidfile, getmypid());
		}

		$pid=PID_NUM();


		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
            build_progress_restart("{stopping_service}",50);
			return true;
		}
		$pid=PID_NUM();
		$nohup=$unix->find_program("nohup");
		$php5=$unix->LOCATE_PHP5_BIN();
		$kill=$unix->find_program("kill");


		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
		unix_system_kill($pid);
		for($i=0;$i<5;$i++){
            build_progress_restart("{stopping_service}",10+$i);
			$pid=PID_NUM();
			if(!$unix->process_exists($pid)){break;}
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
			sleep(1);
		}

		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
            build_progress_restart("{stopping_service}",50);
			return true;
		}

		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
		unix_system_kill_force($pid);
		for($i=0;$i<5;$i++){
            build_progress_restart("{stopping_service}",20+$i);
			$pid=PID_NUM();
			if(!$unix->process_exists($pid)){break;}
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
			sleep(1);
		}

		if($unix->process_exists($pid)){
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
			return;
		}

        build_progress_restart("{stopping_service}",50);
		@unlink("/var/run/opendkim/opendkim.sock");
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		
} 

?>