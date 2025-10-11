<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.pdns.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.powerdns.inc');
$GLOBALS["SHOWKEYS"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(preg_match("#--showkeys#",implode(" ",$argv))){$GLOBALS["SHOWKEYS"]=true;}


if($argv[1]=="--start"){start_recursor();exit;}
if($argv[1]=="--stop"){stop_recursor();exit;}
if($argv[1]=="--restart"){restart_recursor();exit;}
if($argv[1]=="--reload"){reload_recursor();exit;}
if($argv[1]=="--lua"){powerdns_lua();exit;}
if($argv[1]=="--rpz"){powerdns_lua(true);exit;}



function start_recursor(){
	$sock=new sockets();
	$unix=new unix();
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
	$PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));

	$PowerDNSOutgoingIP=null;
	$PowerDNSRecursorIP=null;
	$PowerDNSRecursorQuerLocalAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSRecursorQuerLocalAddr"));
	$PowerDNSRecursorInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSRecursorInterface"));
	if($PowerDNSRecursorInterface==null){$PowerDNSRecursorIP=null;}

	if($PowerDNSRecursorIP==null){
		$zips[]="127.0.0.1";
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
        foreach ($NETWORK_ALL_INTERFACES as $ipaddr=>$none){
            if(preg_match("#^127\.0\.0#",$ipaddr)){continue;}
			_out("Listens $ipaddr");
			$zips[]=$ipaddr;
		}
		
		$PowerDNSRecursorIP=@implode(",", $zips);
		
	}
	
	
	if($PowerDNSRecursorQuerLocalAddr<>null){
		$PowerDNSOutgoingIP=$unix->InterfaceToIPv4($PowerDNSRecursorQuerLocalAddr);
	}
	

	$nohup=$unix->find_program("nohup");
	$recursorbin=$unix->find_program("pdns_recursor");

	if(!is_file($recursorbin)){
        _out("Not installed, aborting task");
        return false;
	}
	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
		$pidtime=$unix->PROCCESS_TIME_MIN($pid);
		if($PowerDNSEnableRecursor==0){
            _out("Service is disabled, aborting task (1)");
            stop_recursor();
            return false;
        }
		if($EnablePDNS==0){
           _out("Service is disabled, aborting task (2)");
            stop_recursor();
            return false;
        }
		_out("Starting: Already running PID $pid since {$pidtime}mn");

		return true;
	}

	if($DisablePowerDnsManagement==1){
		_out("DisablePowerDnsManagement=$DisablePowerDnsManagement, aborting task");
		return false;
	}
	if($EnablePDNS==0){_out("service is disabled, aborting task");return false;}
	$trace=null;$quiet="yes";
	
	$PowerDNSRecursorLogLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSRecursorLogLevel"));
	$PowerDNSDNSSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSDNSSEC"));
	$PowerDNSLogsQueries=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSLogsQueries"));
    if($PowerDNSRecursorLogLevel<3){$PowerDNSRecursorLogLevel=3;}
	if ($PowerDNSLogsQueries==1){$quiet='no';}

	_out("Network card to send queries $PowerDNSRecursorQuerLocalAddr");
	_out("Log level [$PowerDNSRecursorLogLevel]");


	@mkdir("/var/run/pdns",0755,true);
	pdns_recursor_lua();
	if(!is_file("/etc/powerdns/hosts")){SafeSearch();}
    powerdns_lua();
    $unix->framework_exec("exec.pdns_server.install.php --syslog");
	$cd[]="$nohup $recursorbin --daemon";
	$cd[]="--etc-hosts-file=/etc/powerdns/hosts --export-etc-hosts=yes";
	$cd[]="--socket-dir=/var/run/pdns --quiet=$quiet";
	$cd[]="--config-dir=/etc/powerdns{$trace}";
	$cd[]="--forward-zones-file=/etc/powerdns/forward-zones-file";
	$cd[]="--allow-from-file=/etc/powerdns/allow-from-file";
	$cd[]="--reuseport=yes";
	$cd[]="--max-mthreads=2035";
	if($PowerDNSOutgoingIP<>null){
		$cd[]="--query-local-address=$PowerDNSOutgoingIP";
	}
	$cd[]="--local-address=$PowerDNSRecursorIP";
	$cd[]="--local-port=53";
	$cd[]="--loglevel=$PowerDNSRecursorLogLevel";
	$cd[]="--lua-config-file=/etc/powerdns/PowerDNS.lua";
	$cd[]=">/dev/null 2>&1 &";
	
	if(!is_file("/etc/powerdns/forward-zones-file")){@touch("/etc/powerdns/forward-zones-file");}
	if(!is_file("/etc/powerdns/allow-from-file")){@touch("/etc/powerdns/allow-from-file");}

    $cmd=@implode(" ", $cd);
    $cmdline=$unix->sh_command($cmd);
    $unix->go_exec($cmdline);
	sleep(1);
	$pid=pdns_recursor_pid();
	if(!$unix->process_exists($pid)){
		for($i=0;$i<5;$i++){
			_out("waiting ".($i+1)."/5");
			$pid=pdns_recursor_pid();
			if($unix->process_exists($pid)){break;}
            sleep(1);
		}
	}

	$pid=pdns_recursor_pid();
	if(!$unix->process_exists($pid)){
		_out("Failed to start with $cmd");
		return false;
	}else{
        _out("Success to start with PID $pid");
		return true;
	}

}
function _out($text){
    $GLOBALS["TITLENAME"]="PowerDNS Recursor";
    echo "Service.......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("pdns_recursor", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function pdns_recursor_pid(){
	$unix=new unix();
	$pid=trim(@file_get_contents("/var/run/pdns/pdns_recursor.pid"));
	if($unix->process_exists($pid)){return $pid;}
	$recursorbin=$unix->find_program("pdns_recursor");
	return $unix->PIDOF($recursorbin);

}
function powerdns_lua(){

    $unix=new unix();
    $tfile="/etc/powerdns/PowerDNS.lua";
    $rpzpath="/etc/powerdns/rpz";
    $md5=@crc32_file($tfile);
    if(!is_dir($rpzpath)){
        @mkdir($rpzpath,0755,true);
    }
    @chmod($rpzpath,0755);
    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $sql="SELECT * FROM policies WHERE enabled=1 ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);
    $f=array();
    $c=0;
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $rpzname=$ligne["rpzname"];
        $rpztype=$ligne["rpztype"];
        $defpol=$ligne["defpol"];
        $rpzurl=$ligne["rpzurl"];
        $defcontent=$ligne["defcontent"];
        $c++;
        if($rpztype==1){
            $rpzdb="$rpzpath/$ID.conf";
            if(!is_file($rpzdb)){@touch($rpzdb);}
            $f[]="-- $index] ($ID) $rpzurl";
            $f[]="rpzFile(\"$rpzdb\", {defpol=$defpol, defcontent=\"$defcontent\",name=\"$rpzname\"})";
        }
        if($rpztype==2){
            $f[]="-- $index] ($ID) $rpzurl";
            $f[]="rpzPrimary(\"$rpzurl\", \"$rpzname\", defcontent=\"$defcontent\",name=\"$rpzname\"})";
        }

    }
    _out("$c RPZ Policies...");
    @file_put_contents($tfile,@implode("\n",$f));
    if($c>0){
        $unix->Popuplate_cron_make("exec.pdns.rpz.php","30 * * * *","pdns-rpz");
    }else{
        $unix->Popuplate_cron_delete("pdns-rpz");
    }
    $md52=@crc32_file($tfile);
    if($md52==$md5){return true;}
    $pdns_control=$unix->find_program("pdns_control");
    system("$pdns_control reload");
    return true;

}


function pdns_recursor_lua(){
	$lua=array();
	$lua[]="function preresolve(dq)";
	$lua[]="\tif dq.qname:equal(\"www.google.com\") then";
	$lua[]="\t\tdq:addAnswer(pdns.CNAME, \"forcesafesearch.google.com.\")";
	$lua[]="\t\tdq.rcode = 0";
	$lua[]="\t\tdq.followupFunction=\"followCNAMERecords\"";
	$lua[]="\t\treturn true;";
	$lua[]="\tend";
	$lua[]="\treturn false;";
	$lua[]="end\n";
	@file_put_contents("/etc/powerdns/recursor.lua.GoogleSafeSearch.lua", @implode("\n", $lua));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} recursor.lua.GoogleSafeSearch.lua done...\n";}
}


function stop_recursor():bool{
	$sock=new sockets();
	$unix=new unix();
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");

	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	$recursorbin=$unix->find_program("pdns_recursor");
	if($DisablePowerDnsManagement==1){
		_out("Stopping: DisablePowerDnsManagement=$DisablePowerDnsManagement, aborting task");
		return false;
	}

	if(!is_file($recursorbin)){
        _out("Stopping: Not installed, aborting task");
	}

	$pid=pdns_recursor_pid();
	if(!$unix->process_exists($pid)){
        _out("Stopping: Already stopped");
		return true;
	}

	$pidtime=$unix->PROCCESS_TIME_MIN($pid);
    _out("Stopping: pid $pid running since {$pidtime}mn");
	$rec_control=$unix->find_program("rec_control");
	
	system("$rec_control --socket-dir=/var/run/pdns quit-nicely");
	sleep(1);
	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
		for($i=0;$i<3;$i++){
            _out("Stopping: Waiting pid $pid top stop ".($i+1)."/3");
			system("$rec_control --socket-dir=/var/run/pdns quit-nicely");
			$pid=pdns_recursor_pid();
			if(!$unix->process_exists($pid)){break;}
			sleep(1);
		}
	}
	unix_system_kill($pid);
	sleep(1);
	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
		for($i=0;$i<5;$i++){
            _out("Stopping: Waiting pid $pid top stop ".($i+1)."/5");
			unix_system_kill($pid);
			$pid=pdns_recursor_pid();
			if(!$unix->process_exists($pid)){break;}
			sleep(1);
		}
	}

	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)){
        _out("Stopping: Force killing pid $pid");
		unix_system_kill_force($pid);
		if($unix->process_exists($pid)){
			for($i=0;$i<5;$i++){
                _out("Stopping: Waiting pid $pid top stop ".($i+1)."/5");
				unix_system_kill_force($pid);
				$pid=pdns_recursor_pid();
				if(!$unix->process_exists($pid)){break;}
				sleep(1);
			}
		}
	}

	$pid=pdns_recursor_pid();
	if($unix->process_exists($pid)) {
        _out("Stopping: Failed to stop");
        return false;
    }
    _out("Stopping: Success");
	return true;
}
function build_progress_recursor($pourc,$text){
	$echotext=$text;
	echo "Starting......: Recursor: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/recusor.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function reload_recursor(){
	$unix=new unix();
	build_progress_recursor(70, "{configuring}");
	SafeSearch();
	build_progress_recursor(71, "{configuring}");
	forward_zones();
	build_progress_recursor(72, "{reloading_service}");
	$rec_control=$unix->find_program("rec_control");
	
	system("$rec_control --socket-dir=/var/run/pdns reload-acls");
	system("$rec_control --socket-dir=/var/run/pdns reload-zones");
	build_progress_recursor(100, "{reloading_service} {done}");
	 
	
}

function restart_recursor(){
	$unix=new unix();
	$forcesafesearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoogleSafeSearchAddress"));
	_out("ForceSafesearch:$forcesafesearch");
	if($forcesafesearch==null){
		$forcesafesearch=gethostbyname("forcesafesearch.google.com");
		if($forcesafesearch==null){$forcesafesearch="216.239.38.120";}
		if($forcesafesearch<>null){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("GoogleSafeSearchAddress", $forcesafesearch);}
	}

	build_progress_recursor(20, "{stopping_service}");
	if(!stop_recursor()){
		build_progress_recursor(110, "{stopping_service} {failed}");
		return;
	}
	build_progress_recursor(70, "{configuring}");
	SafeSearch();
	build_progress_recursor(71, "{configuring}");
	forward_zones();
	build_progress_recursor(72, "{starting_service}");
	if(!start_recursor()){
		build_progress_recursor(110, "{starting_service} {failed}");
		return;
	}
	build_progress_recursor(100, "{starting_service} {success}");

	$pdns_control=$unix->find_program("pdns_control");
	system("$pdns_control purge");
}
function SafeSearch()
{

    $unix = new unix();
    $f = explode("\n", @file_get_contents("/etc/hosts"));

    foreach ($f as $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }
        if (strpos($line, "google.") > 0) {
            continue;
        }
        if (strpos($line, "youtube.") > 0) {
            continue;
        }
        if (strpos($line, "qwant.") > 0) {
            continue;
        }
        if (strpos($line, "bing.") > 0) {
            continue;
        }
        $HOTS[] = $line;

    }

    if (!$unix->CORP_LICENSE()){
        @file_put_contents("/etc/powerdns/hosts", @implode("\n", $HOTS) . "\n");
        return;
    }


	$ipclass=new IP();
	$EnableGoogleSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGoogleSafeSearch"));
	$forcesafesearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("GoogleSafeSearchAddress"));

	$EnableQwantSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQwantSafeSearch"));
	$QwantSafeSearchAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("QwantSafeSearchAddress"));

	$EnableBingSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableBingSafeSearch"));
	$BingSafeSearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BingSafeSearch"));

	$EnableYoutubeSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableYoutubeSafeSearch"));
	$YoutubeSafeSearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("YoutubeSafeSearch"));

	$EnbaleYoutubeModerate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnbaleYoutubeModerate"));
	$YoutubeModerate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("YoutubeModerate"));

    $EnableDuckduckgoSafeSearch=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDuckduckgoSafeSearch"));
    $DuckduckgoSafeSearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DuckduckgoSafeSearch"));


	if($EnableGoogleSafeSearch==1){
		if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}
		if($forcesafesearch==null){$forcesafesearch=gethostbyname("forcesafesearch.google.com");}
		if(!$ipclass->isValid($forcesafesearch)){$forcesafesearch=null;}
		if($forcesafesearch==null){$forcesafesearch="216.239.38.120";}
		$googlef[]="www.google.com";
		$googlef[]="www.google.fr";
		$googlef[]="www.google.ad";
		$googlef[]="www.google.ae";
		$googlef[]="www.google.com.af";
		$googlef[]="www.google.com.ag";
		$googlef[]="www.google.com.ai";
		$googlef[]="www.google.al";
		$googlef[]="www.google.am";
		$googlef[]="www.google.co.ao";
		$googlef[]="www.google.com.ar";
		$googlef[]="www.google.as";
		$googlef[]="www.google.at";
		$googlef[]="www.google.com.au";
		$googlef[]="www.google.az";
		$googlef[]="www.google.ba";
		$googlef[]="www.google.com.bd";
		$googlef[]="www.google.be";
		$googlef[]="www.google.bf";
		$googlef[]="www.google.bg";
		$googlef[]="www.google.com.bh";
		$googlef[]="www.google.bi";
		$googlef[]="www.google.bj";
		$googlef[]="www.google.com.bn";
		$googlef[]="www.google.com.bo";
		$googlef[]="www.google.com.br";
		$googlef[]="www.google.bs";
		$googlef[]="www.google.bt";
		$googlef[]="www.google.co.bw";
		$googlef[]="www.google.by";
		$googlef[]="www.google.com.bz";
		$googlef[]="www.google.ca";
		$googlef[]="www.google.cd";
		$googlef[]="www.google.cf";
		$googlef[]="www.google.cg";
		$googlef[]="www.google.ch";
		$googlef[]="www.google.ci";
		$googlef[]="www.google.co.ck";
		$googlef[]="www.google.cl";
		$googlef[]="www.google.cm";
		$googlef[]="www.google.cn";
		$googlef[]="www.google.com.co";
		$googlef[]="www.google.co.cr";
		$googlef[]="www.google.com.cu";
		$googlef[]="www.google.cv";
		$googlef[]="www.google.com.cy";
		$googlef[]="www.google.cz";
		$googlef[]="www.google.de";
		$googlef[]="www.google.dj";
		$googlef[]="www.google.dk";
		$googlef[]="www.google.dm";
		$googlef[]="www.google.com.do";
		$googlef[]="www.google.dz";
		$googlef[]="www.google.com.ec";
		$googlef[]="www.google.ee";
		$googlef[]="www.google.com.eg";
		$googlef[]="www.google.es";
		$googlef[]="www.google.com.et";
		$googlef[]="www.google.fi";
		$googlef[]="www.google.com.fj";
		$googlef[]="www.google.fm";
		$googlef[]="www.google.fr";
		$googlef[]="www.google.ga";
		$googlef[]="www.google.ge";
		$googlef[]="www.google.gg";
		$googlef[]="www.google.com.gh";
		$googlef[]="www.google.com.gi";
		$googlef[]="www.google.gl";
		$googlef[]="www.google.gm";
		$googlef[]="www.google.gp";
		$googlef[]="www.google.gr";
		$googlef[]="www.google.com.gt";
		$googlef[]="www.google.gy";
		$googlef[]="www.google.com.hk";
		$googlef[]="www.google.hn";
		$googlef[]="www.google.hr";
		$googlef[]="www.google.ht";
		$googlef[]="www.google.hu";
		$googlef[]="www.google.co.id";
		$googlef[]="www.google.ie";
		$googlef[]="www.google.co.il";
		$googlef[]="www.google.im";
		$googlef[]="www.google.co.in";
		$googlef[]="www.google.iq";
		$googlef[]="www.google.is";
		$googlef[]="www.google.it";
		$googlef[]="www.google.je";
		$googlef[]="www.google.com.jm";
		$googlef[]="www.google.jo";
		$googlef[]="www.google.co.jp";
		$googlef[]="www.google.co.ke";
		$googlef[]="www.google.com.kh";
		$googlef[]="www.google.ki";
		$googlef[]="www.google.kg";
		$googlef[]="www.google.co.kr";
		$googlef[]="www.google.com.kw";
		$googlef[]="www.google.kz";
		$googlef[]="www.google.la";
		$googlef[]="www.google.com.lb";
		$googlef[]="www.google.li";
		$googlef[]="www.google.lk";
		$googlef[]="www.google.co.ls";
		$googlef[]="www.google.lt";
		$googlef[]="www.google.lu";
		$googlef[]="www.google.lv";
		$googlef[]="www.google.com.ly";
		$googlef[]="www.google.co.ma";
		$googlef[]="www.google.md";
		$googlef[]="www.google.me";
		$googlef[]="www.google.mg";
		$googlef[]="www.google.mk";
		$googlef[]="www.google.ml";
		$googlef[]="www.google.com.mm";
		$googlef[]="www.google.mn";
		$googlef[]="www.google.ms";
		$googlef[]="www.google.com.mt";
		$googlef[]="www.google.mu";
		$googlef[]="www.google.mv";
		$googlef[]="www.google.mw";
		$googlef[]="www.google.com.mx";
		$googlef[]="www.google.com.my";
		$googlef[]="www.google.co.mz";
		$googlef[]="www.google.com.na";
		$googlef[]="www.google.com.nf";
		$googlef[]="www.google.com.ng";
		$googlef[]="www.google.com.ni";
		$googlef[]="www.google.ne";
		$googlef[]="www.google.nl";
		$googlef[]="www.google.no";
		$googlef[]="www.google.com.np";
		$googlef[]="www.google.nr";
		$googlef[]="www.google.nu";
		$googlef[]="www.google.co.nz";
		$googlef[]="www.google.com.om";
		$googlef[]="www.google.com.pa";
		$googlef[]="www.google.com.pe";
		$googlef[]="www.google.com.pg";
		$googlef[]="www.google.com.ph";
		$googlef[]="www.google.com.pk";
		$googlef[]="www.google.pl";
		$googlef[]="www.google.pn";
		$googlef[]="www.google.com.pr";
		$googlef[]="www.google.ps";
		$googlef[]="www.google.pt";
		$googlef[]="www.google.com.py";
		$googlef[]="www.google.com.qa";
		$googlef[]="www.google.ro";
		$googlef[]="www.google.ru";
		$googlef[]="www.google.rw";
		$googlef[]="www.google.com.sa";
		$googlef[]="www.google.com.sb";
		$googlef[]="www.google.sc";
		$googlef[]="www.google.se";
		$googlef[]="www.google.com.sg";
		$googlef[]="www.google.sh";
		$googlef[]="www.google.si";
		$googlef[]="www.google.sk";
		$googlef[]="www.google.com.sl";
		$googlef[]="www.google.sn";
		$googlef[]="www.google.so";
		$googlef[]="www.google.sm";
		$googlef[]="www.google.sr";
		$googlef[]="www.google.st";
		$googlef[]="www.google.com.sv";
		$googlef[]="www.google.td";
		$googlef[]="www.google.tg";
		$googlef[]="www.google.co.th";
		$googlef[]="www.google.com.tj";
		$googlef[]="www.google.tk";
		$googlef[]="www.google.tl";
		$googlef[]="www.google.tm";
		$googlef[]="www.google.tn";
		$googlef[]="www.google.to";
		$googlef[]="www.google.com.tr";
		$googlef[]="www.google.tt";
		$googlef[]="www.google.com.tw";
		$googlef[]="www.google.co.tz";
		$googlef[]="www.google.com.ua";
		$googlef[]="www.google.co.ug";
		$googlef[]="www.google.co.uk";
		$googlef[]="www.google.com.uy";
		$googlef[]="www.google.co.uz";
		$googlef[]="www.google.com.vc";
		$googlef[]="www.google.co.ve";
		$googlef[]="www.google.vg";
		$googlef[]="www.google.co.vi";
		$googlef[]="www.google.com.vn";
		$googlef[]="www.google.vu";
		$googlef[]="www.google.ws";
		$googlef[]="www.google.rs";
		$googlef[]="www.google.co.za";
		$googlef[]="www.google.co.zm";
		$googlef[]="www.google.co.zw";
		$googlef[]="www.google.cat";


		$HOTS[]="$forcesafesearch\t".@implode(" ", $googlef);

	}



	$ASYOUTUBE=false;
	if($EnbaleYoutubeModerate==1){$ASYOUTUBE=true;}
	if($EnableYoutubeSafeSearch==1){$ASYOUTUBE=true;}



	if($ASYOUTUBE){
		if(!$ipclass->isValid($YoutubeSafeSearch)){$YoutubeSafeSearch=null;}
		if($YoutubeSafeSearch==null){$YoutubeSafeSearch=gethostbyname("restrict.youtube.com");}
		if(!$ipclass->isValid($YoutubeSafeSearch)){$YoutubeSafeSearch=null;}
		if($YoutubeSafeSearch==null){$YoutubeSafeSearch="216.239.38.120";}
		if(!$ipclass->isValid($YoutubeModerate)){$YoutubeModerate=null;}
		if($YoutubeModerate==null){$YoutubeModerate=gethostbyname("restrictmoderate.youtube.com");}
		if(!$ipclass->isValid($YoutubeModerate)){$YoutubeModerate=null;}
		if($YoutubeModerate==null){$YoutubeModerate="216.239.38.119";}

		if($EnableYoutubeSafeSearch==1){$YoutubeIP=$YoutubeSafeSearch;}
		if($EnbaleYoutubeModerate==1){$YoutubeIP=$YoutubeModerate;}
		$HOTS[]="$YoutubeIP www.youtube.com m.youtube.com youtubei.googleapis.com youtube.googleapis.com www.youtube-nocookie.com";
			
	}


	if($EnableQwantSafeSearch==1){
		if(!$ipclass->isValid($QwantSafeSearchAddress)){$QwantSafeSearchAddress=null;}
		if($QwantSafeSearchAddress==null){$QwantSafeSearchAddress=gethostbyname("safeapi.qwant.com");}
		if(!$ipclass->isValid($QwantSafeSearchAddress)){$QwantSafeSearchAddress=null;}
		if($QwantSafeSearchAddress==null){$QwantSafeSearchAddress="194.187.168.114";}
		$HOTS[]="$QwantSafeSearchAddress\tapi.qwant.com";

	}

	if($EnableBingSafeSearch==1){
		if(!$ipclass->isValid($BingSafeSearch)){$BingSafeSearch=null;}
		if($BingSafeSearch==null){$BingSafeSearch=gethostbyname("strict.bing.com");}
		if(!$ipclass->isValid($BingSafeSearch)){$BingSafeSearch=null;}
		if($BingSafeSearch==null){$BingSafeSearch="204.79.197.220";}
		$HOTS[]="$BingSafeSearch\twww.bing.com bing.com";
		$HOTS[]="127.0.0.1\texplicit.bing.net";
	}

    if($EnableDuckduckgoSafeSearch==1){
        $DuckduckgoSafeSearch=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DuckduckgoSafeSearch"));
        if(!$ipclass->isValid($DuckduckgoSafeSearch)){$DuckduckgoSafeSearch=null;}
        if($DuckduckgoSafeSearch==null){$DuckduckgoSafeSearch=gethostbyname("safe.duckduckgo.com");}
        if(!$ipclass->isValid($DuckduckgoSafeSearch)){$DuckduckgoSafeSearch=null;}
        if($DuckduckgoSafeSearch==null){$DuckduckgoSafeSearch="54.229.105.151";}
        $HOTS[]="$DuckduckgoSafeSearch\tduckduckgo.com www.duckduckgo.com";
    }


	@file_put_contents("/etc/powerdns/hosts", @implode("\n", $HOTS)."\n");

}

function allow_from(){
	
	$q=new mysql();
	@unlink("/etc/powerdns/allow_recursion.txt");
	
	$ip=new IP();
	
	
	$sql="SELECT * FROM pdns_restricts";
	$results = $q->QUERY_SQL($sql,"artica_backup");
	while ($ligne = mysqli_fetch_assoc($results)) {
		$addr=trim($ligne["address"]);
		if($addr==null){continue;}
		if(!$ip->isIPAddressOrRange($addr)){continue;}
		$f[]=$addr;
	}
	
	if(count($f)>0){
        $f[]="127.0.0.0/8";
		@file_put_contents("/etc/powerdns/allow-from-file", @implode("\n", $f));
	}else{
        $f[]="127.0.0.0/8";
		$f[]="192.168.0.0/16";
		$f[]="10.0.0.0/8";
		$f[]="172.16.0.0/12";
		@file_put_contents("/etc/powerdns/allow-from-file", @implode("\n", $f));
	}
	
	
}


function forward_zones(){
	allow_from();
	$unix=new unix();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT * FROM pdns_fwzones";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){return;}
	$ZONES=array();
	$ZONES_RECUSRIVE=array();
	@unlink("/etc/powerdns/forward-zones-file");
	

	foreach ($results as $index=>$ligne){
		$hostname=$ligne["hostname"].":".$ligne["port"];
		$zone=$ligne["zone"];
		if($zone=="*"){$zone=".";}
		$recursive=$ligne["recursive"];
		if($recursive==1){$zone="+$zone";}
		$ZONES[$zone][$hostname]=true;

	}


	if(count($ZONES)>0){
		$t=array();
		while (list ($zone, $array) = each ($ZONES) ){
			if(count($array)==0){continue;}
			$z=array();
			while (list ($hostname, $none) = each ($array) ){if(trim($hostname)==null){continue;}$z[]=$hostname;}
			_out("Forward zone $zone -> ".@implode(",",$z));
			$t[]="$zone=".@implode(",",$z);
		}

	}

	$q=new mysql_pdns();
	$sql="SELECT * FROM domains ORDER BY name";
	$results=$q->QUERY_SQL($sql);
	while ($ligne2 = mysqli_fetch_assoc($results)) {
		$id=$ligne2["id"];
		if(intval($id)==0){continue;}
		$name=$ligne2["name"];
		if($name==null){continue;}
		$t[]="+$name=127.0.0.154:53";
	}
	
	
	if(count($t)>0){
		@file_put_contents("/etc/powerdns/forward-zones-file", @implode("\n", $t));
	}
	
	

}