<?php

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.syslogs.inc");
include_once(dirname(__FILE__)."/ressources/class.fail2ban.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
include_once(dirname(__FILE__)."/ressources/class.openssh.inc");

if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--enable"){enable();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--reload"){reload();exit;}

if($argv[1]=="--notify-start-service"){notify_start_service();exit(0);}
if($argv[1]=="--notify-stop-service"){squid_admin_mysql(0, "Fail2ban service stopped", null,__FILE__,__LINE__);exit(0);}
if($argv[1]=="--notify-ban"){notify_ban($argv[2],$argv[3]);}
if($argv[1]=="--notify-unban"){notify_unban($argv[2],$argv[3]);}
function install(){
	$unix=new unix();
	$timefile="/etc/artica-postfix/pids/".__FILE__.".time";
	if($unix->file_time_min($timefile)<240){return false;}
	
	
	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$bin=$unix->find_program("fail2ban-server");
	if(!is_file($bin)){
		$unix->DEBIAN_INSTALL_PACKAGE("fail2ban");
	}
	$bin=$unix->find_program("fail2ban-server");
	
	$fail2ban=new fail2ban();
	
	
	if(is_file($bin)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFail2Ban", 1);
		$fail2ban->buildinit();
		build();
	}
}

function notify_start_service(){
    $unix=new unix();
    $timefile="/etc/artica-postfix/pids/".__FILE__.".".__FUNCTION__.".time";
    if($unix->file_time_min($timefile)<1){return;}
    squid_admin_mysql(2, "Fail2ban service started", null,__FILE__,__LINE__);
}

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/fail2ban.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_restart($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/fail2ban.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function notify_unban($ip,$service){
	
	squid_admin_mysql(1, "$service $ip was removed from the banned list",null,__FILE__,__LINE__);
}

function notify_ban($ip,$service=null){
	$unix=new unix();
	$ln=$unix->find_program("ln");
	if(!is_file("/usr/share/GeoIP/GeoIPCity.dat")){
		if(is_file("/usr/local/share/GeoIP/GeoLiteCity.dat")){
			system("$ln -s /usr/local/share/GeoIP/GeoLiteCity.dat /usr/share/GeoIP/GeoIPCity.dat");
		}
	
	}
	$country=null;
	$city=null;
	if(function_exists("geoip_record_by_name")){
		$record = geoip_record_by_name($ip);
		if ($record) {
			$country=$record["country_name"];
			$city=$record["city"];
			echo "$ip $country,$city\n";	
		}
	}else{
		echo "geoip_record_by_name not found...\n";
	}
	
	$q=new postgres_sql();
	$q->suricata_tables();

	$country=strtolower(trim(str_replace("'", "`", $country)));
	$city=strtolower(trim(str_replace("'", "`", $city)));
	$time=date("Y-m-d H:i:s");
	$hostname=gethostbyaddr($ip);
	
	$country=replace_accents($country);
	$city=replace_accents($city);
	
	squid_admin_mysql(1, "$service $ip from $country/$city banned",null,__FILE__,__LINE__); 
	
	
	$sql="INSERT INTO fail2ban_events (zdate,country,city,src_ip,hostname,xcount,service) VALUES ('$time','$country','$city','$ip','$hostname',1,'$service')";
	$q->QUERY_SQL($sql);
	
	
	if(!$q->ok){
		squid_admin_mysql(0, "Unable to create statistics for banned ip $ip", $q->mysql_error."\n$sql",__FILE__,__LINE__);
		return;
	}
	
	
	
}

function restart(){
	$unix=new unix();
	
	
	if($unix->ServerRunSince()<3){
		build_progress_restart("{failed}: Please wait (server was started)",110 );
		return;
	}
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		squid_admin_mysql(1, "Restarting Fail2Ban service", null,__FILE__,__LINE__);
		build_progress_restart("{stopping_service}",30);
		stop(true);
		
	}else{
		squid_admin_mysql(1, "Restart Fail2Ban service [does not running]", null,__FILE__,__LINE__);
	}
	build_progress_restart("{reconfiguring}",50);
	build();
	$fail2ban=new fail2ban();
	$fail2ban->buildinit();
	
	
	build_progress_restart("{starting_service}",70);
	
	if(!start(true)){
		build_progress_restart("{starting_service} {failed}",110);
		return;
	}
	sleep(1);
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		build_progress_restart("{starting_service} {success}",100);
		return;
	
	}
	
	build_progress_restart("{starting_service} {failed}",110);
	
	
}

function reload():bool{
    build_progress_restart("{reconfiguring}",50);
    $EnableFail2Ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFail2Ban"));
    if($EnableFail2Ban==0){
        build_progress_restart("{reconfiguring} {failed} {disabled}",110);
        return false;
    }

    build(true);
	$unix=new unix();
	$pid=PID_NUM();


	if(!is_file("/etc/init.d/fail2ban")){
        $fail2ban=new fail2ban();
        $fail2ban->buildinit();

    }

	if($unix->process_exists($pid)){
		build_progress_restart("{reloading}...",60);
		$unix=new unix();
		$fail2banClient=$unix->find_program("fail2ban-client");	
		system("$fail2banClient -v -p /var/run/fail2ban/fail2ban.pid -s /var/run/fail2ban/fail2ban.sock reload");
		build_progress_restart("{reloading} {success}",100);
		return false;
	}
	build_progress_restart("{starting}...",60);
	system("/etc/init.d/fail2ban start");
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		build_progress_restart("{starting_service} {success}",100);
		return true;
	
	}
	build_progress_restart("{starting_service} {failed}",110);
	return false;
}



function PID_NUM(){
	$unix=new unix();
	$pidfile="/var/run/fail2ban/fail2ban.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("fail2ban-server");

}

function uninstall(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFail2Ban", 0);
	build_progress("{remove_service}",50);
	remove_service("/etc/init.d/fail2ban");
	if(is_file("/etc/monit/conf.d/APP_FAIL2BAN.monitrc")){
		@unlink("/etc/monit/conf.d/APP_FAIL2BAN.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	}
	build_progress("{remove_service} {done}",100);
}
function enable(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFail2Ban", 1);
	build_progress("{install_service}",50);
	$fail2ban=new fail2ban();
	$fail2ban->buildinit();
	build_progress("{reconfigure}",80);
	build();
	build_monit();
	build_progress("{install_service} {done}",100);
}
function build_monit(){
	
	$f[]="check process APP_FAIL2BAN with pidfile /var/run/fail2ban/fail2ban.pid";
	$f[]="\tstart program = \"/etc/init.d/fail2ban start\"";
	$f[]="\tstop program = \"/etc/init.d/fail2ban stop\"";

	$f[]="\tif failed unixsocket /var/run/fail2ban/fail2ban.sock then restart";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_FAIL2BAN.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_FAIL2BAN.monitrc")){
		echo "/etc/monit/conf.d/APP_FAIL2BAN.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	build_progress("/etc/monit/conf.d/APP_FAIL2BAN.monitrc done",90);
}

function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function build($noreload=false){

    $f[]="/etc/fail2ban/action.d";
    $f[]="/etc/fail2ban/filter.d";
    $f[]="/var/lib/fail2ban";

    foreach ($f as $directory){
        if(!is_dir($directory)){@mkdir($directory,0755,true);}
        @chmod($directory,0755);
    }


	fail2ban_conf();
	fail2ban_common_conf();
	sshd_conf();
	sshportal_conf();
	jail_conf();
	artica_conf();
	suricata_conf();
	
	
	
if($noreload){return;}
	$unix=new unix();
	$fail2banClient=$unix->find_program("fail2ban-client");
	
	$php=$unix->LOCATE_PHP5_BIN();
	system("/usr/sbin/artica-phpfpm-service -configure-ssh");
	if($unix->process_exists(PID_NUM())){
		system("$fail2banClient -v -p /var/run/fail2ban/fail2ban.pid -s /var/run/fail2ban/fail2ban.sock reload");
	}
	
}


function fail2ban_conf(){
	$f[]="# Fail2Ban main configuration file created v4.30 patch 55 ".date("Y-m-d H:i:s");
	$f[]="[Definition]";
	$f[]="loglevel = INFO";
	$f[]="logtarget = /var/log/fail2ban.log";
	$f[]="syslogsocket = auto";
	$f[]="socket = /var/run/fail2ban/fail2ban.sock";
	$f[]="pidfile = /var/run/fail2ban/fail2ban.pid";
	$f[]="dbfile = /var/lib/fail2ban/fail2ban.sqlite3";
	$f[]="";
	$f[]="# Options: dbpurgeage";
	$f[]="# Notes.: Sets age at which bans should be purged from the database";
	$f[]="# Values: [ SECONDS ] Default: 86400 (24hours)";
	$f[]="dbpurgeage = 86400";
	$f[]="";
	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/fail2ban.conf done\n";
	@file_put_contents("/etc/fail2ban/fail2ban.conf", @implode("\n", $f));
}


function jail_conf(){
    $PROXY_PORT         = array();
    $qProxy             = new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$Fail2bantime       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2bantime"));
	$Fail2findtime      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2findtime"));
	$Fail2maxretry      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2maxretry"));
	$Fail2Purge         = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Fail2Purge"));
	$EnablePostfix      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));
	$EnableOpenSSH      = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenSSH"));
    $SquidMgrListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
	
	if($Fail2bantime==0){$Fail2bantime=600;}
	if($Fail2findtime==0){$Fail2findtime=600;}
	if($Fail2maxretry==0){$Fail2maxretry=5;}
	if($Fail2Purge==0){$Fail2Purge=7;}

	if($SquidMgrListenPort>0) {
        $PROXY_PORT[] = $SquidMgrListenPort;
    }
    $results=$qProxy->QUERY_SQL("SELECT port FROM proxy_ports WHERE enabled=1");
	foreach ($results as $index=>$ligne){
        $port=intval($ligne["port"]);
        if($port>0){$PROXY_PORT[]=$port;}

    }

	
	$f[]="# WARNING: heavily refactored in 0.9.0 release.  Please review and";
	$f[]="[INCLUDES]";
	$f[]="before = paths-debian.conf";
	$f[]="[DEFAULT]";
	$f[]="# \"ignoreip\" can be an IP address, a CIDR mask or a DNS host. Fail2ban will not";
	$f[]="# ban a host which matches an address in this list. Several addresses can be";
	$f[]="# defined using space (and/or comma) separator.";
	
	
	$MyNets[]="127.0.0.0/8";
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $results=$q->QUERY_SQL("SELECT * FROM networks_infos WHERE trusted=1");
    foreach ($results as $index=>$ligne){
        $MyNets[]=$ligne["ipaddr"];

    }

	$f[]="ignoreip = ".@implode(" ", $MyNets);
	$f[]="";
	$f[]="# External command that will take an tagged arguments to ignore, e.g. <ip>,";
	$f[]="# and return true if the IP is to be ignored. False otherwise.";
	$f[]="#";
	$f[]="# ignorecommand = /path/to/command <ip>";
	$f[]="ignorecommand =";
	$f[]="bantime  = 600";
	$f[]="findtime  = 600";
	$f[]="maxretry = 5";
	$f[]="backend = auto";
	$f[]="usedns = warn";
	$f[]="logencoding = auto";
	$f[]="enabled = false";
	$f[]="filter = %(__name__)s";
	$f[]="destemail = root@localhost";
	$f[]="sender = root@localhost";
	$f[]="mta = sendmail";
	$f[]="protocol = tcp";
	$f[]="chain = INPUT";
	$f[]="port = 0:65535";
	$f[]="fail2ban_agent = Fail2Ban/%(fail2ban_version)s";
	$f[]="banaction = iptables-multiport";
	$f[]="banaction_allports = iptables-allports";
	$f[]="action_ = %(banaction)s[name=%(__name__)s, bantime=\"%(bantime)s\", port=\"%(port)s\", protocol=\"%(protocol)s\", chain=\"%(chain)s\"]";
	$f[]="# ban & send an e-mail with whois report to the destemail.";
	$f[]="action_mw = %(banaction)s[name=%(__name__)s, bantime=\"%(bantime)s\", port=\"%(port)s\", protocol=\"%(protocol)s\", chain=\"%(chain)s\"]";
	$f[]="            %(mta)s-whois[name=%(__name__)s, sender=\"%(sender)s\", dest=\"%(destemail)s\", protocol=\"%(protocol)s\", chain=\"%(chain)s\"]";
	$f[]="action_mwl = %(banaction)s[name=%(__name__)s, bantime=\"%(bantime)s\", port=\"%(port)s\", protocol=\"%(protocol)s\", chain=\"%(chain)s\"]";
	$f[]="             %(mta)s-whois-lines[name=%(__name__)s, sender=\"%(sender)s\", dest=\"%(destemail)s\", logpath=%(logpath)s, chain=\"%(chain)s\"]";
	$f[]="action_xarf = %(banaction)s[name=%(__name__)s, bantime=\"%(bantime)s\", port=\"%(port)s\", protocol=\"%(protocol)s\", chain=\"%(chain)s\"]";
	$f[]="             xarf-login-attack[service=%(__name__)s, sender=\"%(sender)s\", logpath=%(logpath)s, port=\"%(port)s\"]";
	$f[]="";
	$f[]="action_cf_mwl = cloudflare[cfuser=\"%(cfemail)s\", cftoken=\"%(cfapikey)s\"]";
	$f[]="                %(mta)s-whois-lines[name=%(__name__)s, sender=\"%(sender)s\", dest=\"%(destemail)s\", logpath=%(logpath)s, chain=\"%(chain)s\"]";
	$f[]="";
	$f[]="# Report block via blocklist.de fail2ban reporting service API";
	$f[]="# ";
	$f[]="# See the IMPORTANT note in action.d/blocklist_de.conf for when to";
	$f[]="# use this action. Create a file jail.d/blocklist_de.local containing";
	$f[]="# [Init]";
	$f[]="# blocklist_de_apikey = {api key from registration]";
	$f[]="#";
	$f[]="action_blocklist_de  = blocklist_de[email=\"%(sender)s\", service=%(filter)s, apikey=\"%(blocklist_de_apikey)s\", agent=\"%(fail2ban_agent)s\"]";
	$f[]="";
	$f[]="# Report ban via badips.com, and use as blacklist";
	$f[]="#";
	$f[]="# See BadIPsAction docstring in config/action.d/badips.py for";
	$f[]="# documentation for this action.";
	$f[]="#";
	$f[]="# NOTE: This action relies on banaction being present on start and therefore";
	$f[]="# should be last action defined for a jail.";
	$f[]="#";
	$f[]="action_badips = badips.py[category=\"%(__name__)s\", banaction=\"%(banaction)s\", agent=\"%(fail2ban_agent)s\"]";
	$f[]="action_badips_report = badips[category=\"%(__name__)s\", agent=\"%(fail2ban_agent)s\"]";
	$f[]="action = %(action_)s";
	$f[]="";


	$f[]="[sshd]";
    if($EnableOpenSSH==1) {
        $f[] = "enabled	= true";

        $sshd = new openssh();
        if ($sshd->main_array["Port"] == 0) {
            $sshd->main_array["Port"] = 22;
        }
        $f[] = "port    	= {$sshd->main_array["Port"]}";
        $f[] = "logpath 	= /var/log/sshd.log";
        $f[] = "backend 	= auto";
        $f[] = "findtime 	= $Fail2findtime";
        $f[] = "maxretry	= $Fail2maxretry";
        $f[] = "bantime	= $Fail2bantime";
        $f[] = "action	= iptables\n\tartica";
        $f[] = "";
        $f[] = "";

    }

    if(is_file("/etc/init.d/sshportal")){
        $sshdportalPort     = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("sshdportalPort"));
        if($sshdportalPort==0){ $sshdportalPort=2222; }
        $f[]="[sshportal]";
        $f[] = "enabled	= true";
        $f[] = "port    	= $sshdportalPort";
        $f[] = "logpath 	= /var/log/sshportal.log";
        $f[] = "backend 	= auto";
        $f[] = "findtime 	= $Fail2findtime";
        $f[] = "maxretry	= $Fail2maxretry";
        $f[] = "bantime	= $Fail2bantime";
        $f[] = "action	= iptables\n\tartica";
        $f[] = "";
        $f[] = "";
    }

	
	if(!is_file("/var/log/suricata-detected.log")){@touch("/var/log/suricata-detected.log");}
	$SuricataFail2ban=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataFail2ban"));
	$ProFTPDInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ProFTPDInstalled"));
	$EnableProFTPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProFTPD"));
	if($ProFTPDInstalled==0){$EnableProFTPD=0;}
	$f[]="[suricata]";
	if($SuricataFail2ban==1){
		$f[]="enabled	= true";
	}
	$f[]="port     = 1:65535";
	$f[]="logpath  = /var/log/suricata-detected.log";
	$f[]="backend  = auto";
	$f[]="findtime 	= $Fail2findtime";
	$f[]="maxretry	= 1";
	$f[]="bantime	= $Fail2bantime";
	$f[]="action	= iptables\n\tartica-suricata";
	$f[]="";
    $f[]="";


    $f[]="[proftpd]";
	if($EnableProFTPD==1){$f[]="enabled	= true";}else{$f[]="enabled	= false";}
	$f[]="port     = ftp,ftp-data,ftps,ftps-data";
	$f[]="logpath  = /var/log/proftpd.log";
	$f[]="backend  = auto";
	$f[]="findtime 	= $Fail2findtime";
	$f[]="maxretry	= $Fail2maxretry";
	$f[]="bantime	= $Fail2bantime";
	$f[]="action	= iptables\n\tartica-proftpd";
    $f[]="";
    $f[]="";
    $ArticaHttpsPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
    artica_webauth_conf();
    rdpproxy_conf();
    proxy_geoip_conf();
    if(!is_file("/var/log/artica-webauth.log")){
        @touch("/var/log/artica-webauth.log");
        @chmod("/var/log/artica-webauth.log",0777);
    }
    $f[]="[artica-webauth]";
	$f[]="enabled    = true";
    $f[]="filter    = artica-webauth";
    $f[]="port      = http,https,$ArticaHttpsPort";
    $f[]="logpath   = /var/log/artica-webauth.log";
    $f[]="findtime 	= $Fail2findtime";
    $f[]="maxretry	= $Fail2maxretry";
    $f[]="bantime	= $Fail2bantime";
    $f[]="action	= iptables\n\tartica-webauth";
	$f[]="";

	if(is_file("/etc/init.d/rdpproxy-authhook")){
        $RDPProxyPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyPort"));
        if($RDPProxyPort==0){$RDPProxyPort=3389;}
        $f[]="[RDPPROXY]";
        $f[]="enabled    = true";
        $f[]="filter    = rdpproxy";
        $f[]="port      = $RDPProxyPort";
        $f[]="logpath   = /var/log/rdpproxy/auth.log";
        $f[]="findtime 	= $Fail2findtime";
        $f[]="maxretry	= $Fail2maxretry";
        $f[]="bantime	= $Fail2bantime";
        $f[]="action	= iptables\n\trdpproxy";
        $f[]="";

    }

    $EnableProxyGeoIP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyGeoIP"));

    if($EnableProxyGeoIP==1){
        $f[]="[proxy_geoip]";
        $f[]="enabled    = true";
        $f[]="filter    = proxy_geoip";
        $f[]="port      = ".@implode(",",$PROXY_PORT);
        $f[]="logpath   = /var/log/proxy-geoip.log";
        $f[]="findtime 	= $Fail2findtime";
        $f[]="maxretry	= 1";
        $f[]="bantime	= $Fail2bantime";
        $f[]="action	= iptables\n\tproxy_geoip";
        $f[]="";
        if(!is_file("/var/log/proxy-geoip.log")){
            @touch("/var/log/proxy-geoip.log");

        }
        @chmod("/var/log/proxy-geoip.log",0755);


    }

	$f[]="[nginx-http-auth]";
	$f[]="port    = http,https";
	$f[]="logpath = %(nginx_error_log)s";
	$f[]="";
	$f[]="# To use 'nginx-limit-req' jail you should have `ngx_http_limit_req_module` ";
	$f[]="# and define `limit_req` and `limit_req_zone` as described in nginx documentation";
	$f[]="# http://nginx.org/en/docs/http/ngx_http_limit_req_module.html";
	$f[]="# or for example see in 'config/filter.d/nginx-limit-req.conf'";
	$f[]="[nginx-limit-req]";
	$f[]="port    = http,https";
	$f[]="logpath = %(nginx_error_log)s";
	$f[]="";
	$f[]="[nginx-botsearch]";
	$f[]="port     = http,https";
	$f[]="logpath  = %(nginx_error_log)s";
	$f[]="maxretry = 2";
	$f[]="";
	$f[]="";
	$f[]="# ASSP SMTP Proxy Jail";
	$f[]="[assp]";
	$f[]="port     = smtp,465,submission";
	$f[]="logpath  = /root/path/to/assp/logs/maillog.txt";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="";

	$portsToBlock="iptables-multiport[name=postfix, port=\"smtp,submission,pop3,pop3s,imap,imaps,sieve\", protocol=tcp]";

	if($EnablePostfix==1){
		$f[]="[postfix]";
		$f[]="enabled\t= true";
		$f[]="port     = smtp,465,submission";
		$f[]="logpath  = /var/log/mail.log";
		$f[]="backend  = auto";
		$f[]="findtime 	= $Fail2findtime";
		$f[]="maxretry	= $Fail2maxretry";
		$f[]="bantime	= $Fail2bantime";
        $f[]="action	= $portsToBlock\n\tartica-smtp";
		$f[]="";
		$f[]="";
		$f[]="[postfix-rbl]";
		$f[]="enabled\t= true";
		$f[]="port     = smtp,465,submission";
		$f[]="logpath  = /var/log/mail.log";
		$f[]="backend  = auto";
		$f[]="findtime 	= $Fail2findtime";
		$f[]="maxretry	= $Fail2maxretry";
		$f[]="bantime	= $Fail2bantime";
        $f[]="action	= $portsToBlock\n\tartica-smtp";
		$f[]="";
		$f[]="";
		$f[]="[postfix-sasl]";
		$f[]="enabled\t= true";
		$f[]="port     = smtp,465,submission,imap3,imaps,pop3,pop3s";
		$f[]="findtime 	= $Fail2findtime";
		$f[]="maxretry	= $Fail2maxretry";
		$f[]="bantime	= $Fail2bantime";
        $f[]="action	= $portsToBlock\n\tartica-smtp";
		$f[]="logpath  = /var/log/mail.log";
		$f[]="backend  = auto";

        postfix_conf();
        postfix_rbl_conf();
	
	}
	
	$f[]="";
	$f[]="";
	$f[]="[sieve]";
	$f[]="port   = smtp,465,submission";
	$f[]="logpath = %(dovecot_log)s";
	$f[]="backend = auto";
	$f[]="";
	$f[]="[cyrus-imap]";
	$f[]="port   = imap3,imaps";
	$f[]="logpath = %(syslog_mail)s";
	$f[]="backend = auto";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="# To log wrong MySQL access attempts add to /etc/my.cnf in [mysqld] or";
	$f[]="# equivalent section:";
	$f[]="# log-warning = 2";
	$f[]="#";
	$f[]="# for syslog (daemon facility)";
	$f[]="# [mysqld_safe]";
	$f[]="# syslog";
	$f[]="#";
	$f[]="# for own logfile";
	$f[]="# [mysqld]";
	$f[]="# log-error=/var/log/mysqld.log";
	$f[]="[mysqld-auth]";
	$f[]="";
	$f[]="port     = 3306";
	$f[]="logpath  = %(mysql_log)s";
	$f[]="backend  = auto";
	$f[]="";
	$f[]="";
	$f[]="# Log wrong MongoDB auth (for details see filter 'filter.d/mongodb-auth.conf')";
	$f[]="[mongodb-auth]";
	$f[]="# change port when running with \"--shardsvr\" or \"--configsvr\" runtime operation";
	$f[]="port     = 27017";
	$f[]="logpath  = /var/log/mongodb/mongodb.log";
	$f[]="";
	$f[]="";
	$f[]="# Jail for more extended banning of persistent abusers";
	$f[]="# !!! WARNINGS !!!";
	$f[]="# 1. Make sure that your loglevel specified in fail2ban.conf/.local";
	$f[]="#    is not at DEBUG level -- which might then cause fail2ban to fall into";
	$f[]="#    an infinite loop constantly feeding itself with non-informative lines";
	$f[]="# 2. Increase dbpurgeage defined in fail2ban.conf to e.g. 648000 (7.5 days)";
	$f[]="#    to maintain entries for failed logins for sufficient amount of time";
	$f[]="[recidive]";
	$f[]="";
	$f[]="logpath  = /var/log/fail2ban.log";
	$f[]="banaction = %(banaction_allports)s";
	$f[]="bantime  = 604800  ; 1 week";
	$f[]="findtime = 86400   ; 1 day";
	$f[]="";
	$f[]="";
	$f[]="# Generic filter for PAM. Has to be used with action which bans all";
	$f[]="# ports such as iptables-allports, shorewall";
	$f[]="";
	$f[]="[pam-generic]";
	$f[]="# pam-generic filter can be customized to monitor specific subset of 'tty's";
	$f[]="banaction = %(banaction_allports)s";
	$f[]="logpath  = %(syslog_authpriv)s";
	$f[]="backend  = auto";
	$f[]="";
	$f[]="";
	$f[]="[xinetd-fail]";
	$f[]="";
	$f[]="banaction = iptables-multiport-log";
	$f[]="logpath   = %(syslog_daemon)s";
	$f[]="backend   = auto";
	$f[]="maxretry  = 2";
	$f[]="";
	$f[]="";
	$f[]="# stunnel - need to set port for this";
	$f[]="[stunnel]";
	$f[]="";
	$f[]="logpath = /var/log/stunnel4/stunnel.log";
	$f[]="";
	$f[]="";
	$f[]="[ejabberd-auth]";
	$f[]="";
	$f[]="port    = 5222";
	$f[]="logpath = /var/log/ejabberd/ejabberd.log";
	$f[]="";
	$f[]="";
	$f[]="[counter-strike]";
	$f[]="";
	$f[]="logpath = /opt/cstrike/logs/L[0-9]*.log";
	$f[]="# Firewall: http://www.cstrike-planet.com/faq/6";
	$f[]="tcpport = 27030,27031,27032,27033,27034,27035,27036,27037,27038,27039";
	$f[]="udpport = 1200,27000,27001,27002,27003,27004,27005,27006,27007,27008,27009,27010,27011,27012,27013,27014,27015";
	$f[]="action  = %(banaction)s[name=%(__name__)s-tcp, port=\"%(tcpport)s\", protocol=\"tcp\", chain=\"%(chain)s\", actname=%(banaction)s-tcp]";
	$f[]="           %(banaction)s[name=%(__name__)s-udp, port=\"%(udpport)s\", protocol=\"udp\", chain=\"%(chain)s\", actname=%(banaction)s-udp]";
	$f[]="";
	$f[]="# consider low maxretry and a long bantime";
	$f[]="# nobody except your own Nagios server should ever probe nrpe";
	$f[]="[nagios]";
	$f[]="";
	$f[]="logpath  = %(syslog_daemon)s     ; nrpe.cfg may define a different log_facility";
	$f[]="backend  = auto";
	$f[]="maxretry = 1";
	$f[]="";
	$f[]="";
	$f[]="[oracleims]";
	$f[]="# see \"oracleims\" filter file for configuration requirement for Oracle IMS v6 and above";
	$f[]="logpath = /opt/sun/comms/messaging64/log/mail.log_current";
	$f[]="banaction = %(banaction_allports)s";
	$f[]="";
	$f[]="[directadmin]";
	$f[]="logpath = /var/log/directadmin/login.log";
	$f[]="port = 2222";
	$f[]="";
	$f[]="[portsentry]";
	$f[]="logpath  = /var/lib/portsentry/portsentry.history";
	$f[]="maxretry = 1";
	$f[]="";
	$f[]="[pass2allow-ftp]";
	$f[]="# this pass2allow example allows FTP traffic after successful HTTP authentication";
	$f[]="port         = ftp,ftp-data,ftps,ftps-data";
	$f[]="# knocking_url variable must be overridden to some secret value in jail.local";
	$f[]="knocking_url = /knocking/";
	$f[]="filter       = apache-pass[knocking_url=\"%(knocking_url)s\"]";
	$f[]="# access log of the website with HTTP auth";
	$f[]="logpath      = %(apache_access_log)s";
	$f[]="blocktype    = RETURN";
	$f[]="returntype   = DROP";
	$f[]="bantime      = 3600";
	$f[]="maxretry     = 1";
	$f[]="findtime     = 1";
	$f[]="";
	$f[]="";
	$f[]="[murmur]";
	$f[]="# AKA mumble-server";
	$f[]="port     = 64738";
	$f[]="action   = %(banaction)s[name=%(__name__)s-tcp, port=\"%(port)s\", protocol=tcp, chain=\"%(chain)s\", actname=%(banaction)s-tcp]";
	$f[]="           %(banaction)s[name=%(__name__)s-udp, port=\"%(port)s\", protocol=udp, chain=\"%(chain)s\", actname=%(banaction)s-udp]";
	$f[]="logpath  = /var/log/mumble-server/mumble-server.log";
	$f[]="";
	$f[]="";
	$f[]="[screensharingd]";
	$f[]="# For Mac OS Screen Sharing Service (VNC)";
	$f[]="logpath  = /var/log/system.log";
	$f[]="logencoding = utf-8";
	$f[]="";
	$f[]="[haproxy-http-auth]";
	$f[]="# HAProxy by default doesn't log to file you'll need to set it up to forward";
	$f[]="# logs to a syslog server which would then write them to disk.";
	$f[]="# See \"haproxy-http-auth\" filter for a brief cautionary note when setting";
	$f[]="# maxretry and findtime.";
	$f[]="logpath  = /var/log/haproxy.log";
	$f[]="";
	$f[]="[slapd]";
	$f[]="port    = ldap,ldaps";
	$f[]="filter  = slapd";
	$f[]="logpath = /var/log/slapd.log";
	$f[]="";
	
	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/jail.conf done\n";
	@file_put_contents("/etc/fail2ban/jail.conf", @implode("\n", $f));
}

function fail2ban_common_conf(){
	$f[]="# Common";
	$f[]="#";
	$f[]="";
	$f[]="[INCLUDES]";
	$f[]="";
	$f[]="after  = paths-overrides.local";
	$f[]="";
	$f[]="[DEFAULT]";
	$f[]="";
	$f[]="default_backend = auto";
	$f[]="";
	$f[]="sshd_log = /var/log/sshd.log";
	$f[]="sshd_backend = %(default_backend)s";
	$f[]="";
	$f[]="dropbear_log = %(syslog_authpriv)s";
	$f[]="dropbear_backend = %(default_backend)s";
	$f[]="";
	$f[]="# There is no sensible generic defaults for syslog log targets, thus";
	$f[]="# leaving them empty here so that no errors while parsing/interpolating configs";
	$f[]="syslog_daemon =";
	$f[]="syslog_ftp =";
	$f[]="syslog_local0 =";
	$f[]="syslog_mail_warn =";
	$f[]="syslog_user =";
	$f[]="# Set the default syslog backend target to default_backend";
	$f[]="syslog_backend = auto";
	$f[]="";
	$f[]="# from /etc/audit/auditd.conf";
	$f[]="auditd_log = /var/log/audit/audit.log";
	$f[]="exim_main_log = /var/log/exim/mainlog";
	$f[]="nginx_error_log = /var/log/nginx/*error.log";
	$f[]="nginx_access_log = /var/log/nginx/*access.log";
	$f[]="lighttpd_error_log = /var/log/lighttpd/error.log";
	$f[]="# http://www.hardened-php.net/suhosin/configuration.html#suhosin.log.syslog.facility";
	$f[]="# syslog_user is the default. Lighttpd also hooks errors into its log.";
	$f[]="";
	$f[]="suhosin_log = %(syslog_user)s";
	$f[]="              %(lighttpd_error_log)s";
	$f[]="";
	$f[]="proftpd_log = %(syslog_ftp)s";
	$f[]="proftpd_backend = auto";
	$f[]="pureftpd_log = %(syslog_ftp)s";
	$f[]="pureftpd_backend = auto";
	$f[]="wuftpd_log = %(syslog_ftp)s";
	$f[]="wuftpd_backend = auto";
	$f[]="vsftpd_log = /var/log/vsftpd.log";
	$f[]="postfix_log = %(syslog_mail_warn)s";
	$f[]="postfix_backend = auto";
	$f[]="dovecot_log = %(syslog_mail_warn)s";
	$f[]="dovecot_backend = auto";
	$f[]="solidpop3d_log = %(syslog_local0)s";
	$f[]="mysql_log = %(syslog_daemon)s";
	$f[]="mysql_backend = auto";
	$f[]="roundcube_errors_log = /var/log/roundcube/errors";
	$f[]="";
	$f[]="# Directory with ignorecommand scripts";
	$f[]="ignorecommands_dir = /etc/fail2ban/filter.d/ignorecommands";
	$f[]="";
	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/paths-common.conf done\n";
	@file_put_contents("/etc/fail2ban/paths-common.conf", @implode("\n", $f));

	$f=array();
    $f[]="# Generic configuration items (to be used as interpolations) in other";
    $f[]="# filters  or actions configurations";
    $f[]="#";
    $f[]="";
    $f[]="[INCLUDES]";
    $f[]="";
    $f[]="# Load customizations if any available";
    $f[]="after = common.local";
    $f[]="";
    $f[]="";
    $f[]="[DEFAULT]";
    $f[]="";
    $f[]="# Type of log-file resp. log-format (file, short, journal, rfc542):";
    $f[]="logtype = file";
    $f[]="";
    $f[]="# Daemon definition is to be specialized (if needed) in .conf file";
    $f[]="_daemon = \S*";
    $f[]="";
    $f[]="#";
    $f[]="# Shortcuts for easier comprehension of the failregex";
    $f[]="#";
    $f[]="# PID.";
    $f[]="# EXAMPLES: [123]";
    $f[]="__pid_re = (?:\[\d+\])";
    $f[]="";
    $f[]="# Daemon name (with optional source_file:line or whatever)";
    $f[]="# EXAMPLES: pam_rhosts_auth, [sshd], pop(pam_unix)";
    $f[]="__daemon_re = [\[\(]?%(_daemon)s(?:\(\S+\))?[\]\)]?:?";
    $f[]="";
    $f[]="# extra daemon info";
    $f[]="# EXAMPLE: [ID 800047 auth.info]";
    $f[]="__daemon_extra_re = \[ID \d+ \S+\]";
    $f[]="";
    $f[]="# Combinations of daemon name and PID";
    $f[]="# EXAMPLES: sshd[31607], pop(pam_unix)[4920]";
    $f[]="__daemon_combs_re = (?:%(__pid_re)s?:\s+%(__daemon_re)s|%(__daemon_re)s%(__pid_re)s?:?)";
    $f[]="";
    $f[]="# Some messages have a kernel prefix with a timestamp";
    $f[]="# EXAMPLES: kernel: [769570.846956]";
    $f[]="__kernel_prefix = kernel:\s?\[ *\d+\.\d+\]:?";
    $f[]="";
    $f[]="__hostname = \S+";
    $f[]="";
    $f[]="# A MD5 hex";
    $f[]="# EXAMPLES: 07:06:27:55:b0:e3:0c:3c:5a:28:2d:7c:7e:4c:77:5f";
    $f[]="__md5hex = (?:[\da-f]{2}:){15}[\da-f]{2}";
    $f[]="";
    $f[]="# bsdverbose is where syslogd is started with -v or -vv and results in <4.3> or";
    $f[]="# <auth.info> appearing before the host as per testcases/files/logs/bsd/*.";
    $f[]="__bsd_syslog_verbose = <[^.]+\.[^.]+>";
    $f[]="";
    $f[]="__vserver = @vserver_\S+";
    $f[]="";
    $f[]="__date_ambit = (?:\[\])";
    $f[]="";
    $f[]="# Common line prefixes (beginnings) which could be used in filters";
    $f[]="#";
    $f[]="#      [bsdverbose]? [hostname] [vserver tag] daemon_id spaces";
    $f[]="#";
    $f[]="# This can be optional (for instance if we match named native log files)";
    $f[]="__prefix_line = <lt_<logtype>/__prefix_line>";
    $f[]="";
    $f[]="# PAM authentication mechanism check for failures, e.g.: pam_unix, pam_sss,";
    $f[]="# pam_ldap";
    $f[]="__pam_auth = pam_unix";
    $f[]="";
    $f[]="# standardly all formats using prefix have line-begin anchored date:";
    $f[]="datepattern = <lt_<logtype>/datepattern>";
    $f[]="";
    $f[]="[lt_file]";
    $f[]="# Common line prefixes for logtype \"file\":";
    $f[]="__prefix_line = %(__date_ambit)s?\s*(?:%(__bsd_syslog_verbose)s\s+)?(?:%(__hostname)s\s+)?(?:%(__kernel_prefix)s\s+)?(?:%(__vserver)s\s+)?(?:%(__daemon_combs_re)s\s+)?(?:%(__daemon_extra_re)s\s+)?";
    $f[]="datepattern = {^LN-BEG}";
    $f[]="";
    $f[]="[lt_short]";
    $f[]="# Common (short) line prefix for logtype \"journal\" (corresponds output of formatJournalEntry):";
    $f[]="__prefix_line = \s*(?:%(__hostname)s\s+)?(?:%(_daemon)s%(__pid_re)s?:?\s+)?(?:%(__kernel_prefix)s\s+)?";
    $f[]="datepattern = %(lt_file/datepattern)s";
    $f[]="[lt_journal]";
    $f[]="__prefix_line = %(lt_short/__prefix_line)s";
    $f[]="datepattern = %(lt_short/datepattern)s";
    $f[]="";
    $f[]="[lt_rfc5424]";
    $f[]="# RFC 5424 log-format, see gh-2309:";
    $f[]="#__prefix_line = \s*<__hostname> <__daemon_re> \d+ \S+ \S+\s+";
    $f[]="__prefix_line = \s*<__hostname> <__daemon_re> \d+ \S+ (?:[^\[\]\s]+|(?:\[(?:[^\]\"]*|\"[^\"]*\")*\])+)\s+";
    $f[]="datepattern = ^<\d+>\d+\s+{DATE}";
    $f[]="";

    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/common.conf done\n";
    @file_put_contents("/etc/fail2ban/filter.d/common.conf", @implode("\n", $f));
    $f=array();
    $f[]="# Fail2Ban configuration file";
    $f[]="[INCLUDES]";
    $f[]="before = iptables-common.conf";
    $f[]="";
    $f[]="[Definition]";
    $f[]="# Option:  actionstart";
    $f[]="# Notes.:  command executed on demand at the first ban (or at the start of Fail2Ban if actionstart_on_demand is set to false).";
    $f[]="# Values:  CMD";
    $f[]="#";
    $f[]="actionstart = <iptables> -N f2b-<name>";
    $f[]="              <iptables> -A f2b-<name> -j <returntype>";
    $f[]="              <iptables> -I <chain> -p <protocol> --dport <port> -j f2b-<name>";
    $f[]="";
    $f[]="actionstop = <iptables> -D <chain> -p <protocol> --dport <port> -j f2b-<name>";
    $f[]="             <actionflush>";
    $f[]="             <iptables> -X f2b-<name>";
    $f[]="";
    $f[]="actioncheck = <iptables> -n -L <chain> | grep -q 'f2b-<name>[ \t]'";
    $f[]="";
    $f[]="actionban = <iptables> -I f2b-<name> 1 -s <ip> -j <blocktype>";
    $f[]="";
    $f[]="actionunban = <iptables> -D f2b-<name> -s <ip> -j <blocktype>";
    $f[]="";
    $f[]="[Init]";
    $f[]="";
    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/iptables.conf done\n";
    @file_put_contents("/etc/fail2ban/action.d/iptables.conf", @implode("\n", $f));
    $f=array();
    $f[]="# Fail2Ban configuration file";

    $f[]="[INCLUDES]";
    $f[]="";
    $f[]="after = iptables-blocktype.local";
    $f[]="        iptables-common.local";
    $f[]="# iptables-blocktype.local is obsolete";
    $f[]="";
    $f[]="[Definition]";
    $f[]="";
    $f[]="actionflush = <iptables> -F f2b-<name>";
    $f[]="";
    $f[]="";
    $f[]="[Init]";
    $f[]="";
    $unix=new unix();
    $iptables=$unix->find_program("iptables");
    $ip6tables=$unix->find_program("ip6tables");
    $f[]="chain = INPUT";
    $f[]="name = default";
    $f[]="port = ssh";
    $f[]="protocol = tcp";
    $f[]="blocktype = REJECT --reject-with icmp-port-unreachable";
    $f[]="returntype = RETURN";
    $f[]="lockingopt = -w";
    $f[]="iptables = $iptables <lockingopt>";
    $f[]="";
    $f[]="";
    $f[]="[Init?family=inet6]";
    $f[]="";
    $f[]="blocktype = REJECT --reject-with icmp6-port-unreachable";
    $f[]="iptables = $ip6tables <lockingopt>\n";

    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/iptables-common.conf done\n";
    @file_put_contents("/etc/fail2ban/action.d/iptables-common.conf", @implode("\n", $f));



}

function sshportal_conf(){
    $f[]="# Fail2Ban filter for Reverse Proxy SSH";
    $f[]="#";
    $f[]="# If you want to protect OpenSSH from being bruteforced by password";
    $f[]="# authentication then get public key authentication working before disabling";
    $f[]="# PasswordAuthentication in sshd_config.";
    $f[]="#";
    $f[]="#";
    $f[]="# \"Connection from <HOST> port \d+\" requires LogLevel VERBOSE in sshd_config";
    $f[]="#";
    $f[]="";
    $f[]="[INCLUDES]";
    $f[]="";
    $f[]="# Read common prefixes. If any customizations available -- read them from";
    $f[]="# common.local";
    $f[]="before = common.conf";
    $f[]="";
    $f[]="[Definition]";
    $f[]="";
    $f[]="_daemon = sshd";
    $f[]="";
    $f[]="failregex = ^%(__prefix_line)s(?:error: PAM: )?[aA]uthentication (?:failure|error|failed) for .* from <HOST>( via \S+)?\s*$";
    $f[]="            ^%(__prefix_line)s(?:error: PAM: )?User not known to the underlying authentication module for .* from <HOST>\s*$";
    $f[]="            ^%(__prefix_line)sFailed \S+ for (?P<cond_inv>invalid user )?(?P<user>(?P<cond_user>\S+)|(?(cond_inv)(?:(?! from ).)*?|[^:]+)) from <HOST>(?: port \d+)?(?: ssh\d*)?(?(cond_user):|(?:(?:(?! from ).)*)$)";
    $f[]="            ^%(__prefix_line)sROOT LOGIN REFUSED.* FROM <HOST>\s*$";
    $f[]="            ^%(__prefix_line)s[iI](?:llegal|nvalid) user .*? from <HOST>(?: port \d+)?\s*$";
    $f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because not listed in AllowUsers\s*$";
    $f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because listed in DenyUsers\s*$";
    $f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because not in any group\s*$";
    $f[]="            ^%(__prefix_line)srefused connect from \S+ \(<HOST>\)\s*$";
    $f[]="            ^%(__prefix_line)s(?:error: )?Received disconnect from <HOST>: 3: .*: Auth fail(?: \[preauth\])?$";
    $f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because a group is listed in DenyGroups\s*$";
    $f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because none of user's groups are listed in AllowGroups\s*$";
    $f[]="            ^(?P<__prefix>%(__prefix_line)s)User .+ not allowed because account is locked<SKIPLINES>(?P=__prefix)(?:error: )?Received disconnect from <HOST>: 11: .+ \[preauth\]$";
    $f[]="            ^(?P<__prefix>%(__prefix_line)s)Disconnecting: Too many authentication failures for .+? \[preauth\]<SKIPLINES>(?P=__prefix)(?:error: )?Connection closed by <HOST> \[preauth\]$";
    $f[]="            ^(?P<__prefix>%(__prefix_line)s)Connection from <HOST> port \d+(?: on \S+ port \d+)?<SKIPLINES>(?P=__prefix)Disconnecting: Too many authentication failures for .+? \[preauth\]$";
    $f[]="            ^%(__prefix_line)s(error: )?maximum authentication attempts exceeded for .* from <HOST>(?: port \d*)?(?: ssh\d*)? \[preauth\]$";
    $f[]="            ^%(__prefix_line)spam_unix\(sshd:auth\):\s+authentication failure;\s*logname=\S*\s*uid=\d*\s*euid=\d*\s*tty=\S*\s*ruser=\S*\s*rhost=<HOST>\s.*$";
    $f[]="";
    $f[]="ignoreregex = ";
    $f[]="";
    $f[]="[Init]";
    $f[]="";
    $f[]="# \"maxlines\" is number of log lines to buffer for multi-line regex searches";
    $f[]="maxlines = 10";
    $f[]="";
    $f[]="journalmatch = _SYSTEMD_UNIT=sshd.service + _COMM=sshd";
    $f[]="";

    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/sshportal.conf\n";
    @file_put_contents("/etc/fail2ban/filter.d/sshportal.conf", @implode("\n", $f));

}

function sshd_conf(){
	$f[]="# Fail2Ban filter for openssh";
	$f[]="#";
	$f[]="# If you want to protect OpenSSH from being bruteforced by password";
	$f[]="# authentication then get public key authentication working before disabling";
	$f[]="# PasswordAuthentication in sshd_config.";
	$f[]="#";
	$f[]="#";
	$f[]="# \"Connection from <HOST> port \d+\" requires LogLevel VERBOSE in sshd_config";
	$f[]="#";
	$f[]="";
	$f[]="[INCLUDES]";
	$f[]="";
	$f[]="# Read common prefixes. If any customizations available -- read them from";
	$f[]="# common.local";
	$f[]="before = common.conf";
	$f[]="";
	$f[]="[Definition]";
	$f[]="";
	$f[]="_daemon = sshd";
	$f[]="";
	$f[]="failregex = ^%(__prefix_line)s(?:error: PAM: )?[aA]uthentication (?:failure|error|failed) for .* from <HOST>( via \S+)?\s*$";
	$f[]="            ^%(__prefix_line)s(?:error: PAM: )?User not known to the underlying authentication module for .* from <HOST>\s*$";
	$f[]="            ^%(__prefix_line)sFailed \S+ for (?P<cond_inv>invalid user )?(?P<user>(?P<cond_user>\S+)|(?(cond_inv)(?:(?! from ).)*?|[^:]+)) from <HOST>(?: port \d+)?(?: ssh\d*)?(?(cond_user):|(?:(?:(?! from ).)*)$)";
	$f[]="            ^%(__prefix_line)sROOT LOGIN REFUSED.* FROM <HOST>\s*$";
	$f[]="            ^%(__prefix_line)s[iI](?:llegal|nvalid) user .*? from <HOST>(?: port \d+)?\s*$";
	$f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because not listed in AllowUsers\s*$";
	$f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because listed in DenyUsers\s*$";
	$f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because not in any group\s*$";
	$f[]="            ^%(__prefix_line)srefused connect from \S+ \(<HOST>\)\s*$";
	$f[]="            ^%(__prefix_line)s(?:error: )?Received disconnect from <HOST>: 3: .*: Auth fail(?: \[preauth\])?$";
	$f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because a group is listed in DenyGroups\s*$";
	$f[]="            ^%(__prefix_line)sUser .+ from <HOST> not allowed because none of user's groups are listed in AllowGroups\s*$";
	$f[]="            ^(?P<__prefix>%(__prefix_line)s)User .+ not allowed because account is locked<SKIPLINES>(?P=__prefix)(?:error: )?Received disconnect from <HOST>: 11: .+ \[preauth\]$";
	$f[]="            ^(?P<__prefix>%(__prefix_line)s)Disconnecting: Too many authentication failures for .+? \[preauth\]<SKIPLINES>(?P=__prefix)(?:error: )?Connection closed by <HOST> \[preauth\]$";
	$f[]="            ^(?P<__prefix>%(__prefix_line)s)Connection from <HOST> port \d+(?: on \S+ port \d+)?<SKIPLINES>(?P=__prefix)Disconnecting: Too many authentication failures for .+? \[preauth\]$";
	$f[]="            ^%(__prefix_line)s(error: )?maximum authentication attempts exceeded for .* from <HOST>(?: port \d*)?(?: ssh\d*)? \[preauth\]$";
	$f[]="            ^%(__prefix_line)spam_unix\(sshd:auth\):\s+authentication failure;\s*logname=\S*\s*uid=\d*\s*euid=\d*\s*tty=\S*\s*ruser=\S*\s*rhost=<HOST>\s.*$";
	$f[]="";
	$f[]="ignoreregex = ";
	$f[]="";
	$f[]="[Init]";
	$f[]="";
	$f[]="# \"maxlines\" is number of log lines to buffer for multi-line regex searches";
	$f[]="maxlines = 10";
	$f[]="";
	$f[]="journalmatch = _SYSTEMD_UNIT=sshd.service + _COMM=sshd";
	$f[]="";
	
	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/sshd.conf\n";
	@file_put_contents("/etc/fail2ban/filter.d/sshd.conf", @implode("\n", $f));
	
	
}

function suricata_conf(){
	$f[]="# Fail2Ban filter for Suricata";
	$f[]="[INCLUDES]";
	$f[]="[Definition]";
	$f[]="";
	
	$f[]="";
	$f[]="failregex = [0-9]+\s+<HOST>\s+[0-9]+";
	$f[]="";
	$f[]="ignoreregex = ";
	$f[]="";
	$f[]="[Init]";
	$f[]="maxlines = 10";
	$f[]="journalmatch =";
	$f[]="";

	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/suricata.conf\n";
	@file_put_contents("/etc/fail2ban/filter.d/suricata.conf", @implode("\n", $f));
}

function artica_webauth_conf(){
    $f[]="# Fail2Ban filter for Artica Web console";
    $f[]="[INCLUDES]";
    $f[]="[Definition]";
    $f[]="failregex = .*\s+<HOST>\s+FAILED.*";
    $f[]="";
    $f[]="ignoreregex = ";
    $f[]="";
    $f[]="[Init]";
   // $f[]="datepattern = %%Y-%%b-%%d %%H:%%M:%%S";
    $f[]="journalmatch =";
    $f[]="";

    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/artica-webauth.conf\n";
    @file_put_contents("/etc/fail2ban/filter.d/artica-webauth.conf", @implode("\n", $f));

}

function proxy_geoip_conf(){
    $f[]="# Fail2Ban filter for Proxy when using GeoIP";
    $f[]="[INCLUDES]";
    $f[]="[Definition]";
    $f[]="failregex = .*\[DENY\]: \[<HOST>\]";
    $f[]="";
    $f[]="ignoreregex = ";
    $f[]="";
    $f[]="[Init]";
    $f[]="journalmatch =";
    $f[]="";

    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/proxy_geoip.conf\n";
    @file_put_contents("/etc/fail2ban/filter.d/proxy_geoip.conf", @implode("\n", $f));

}

function rdpproxy_conf(){
    $f[]="# Fail2Ban filter for RDPPROXY";
    $f[]="[INCLUDES]";
    $f[]="[Definition]";
    $f[]="failregex = .*\[ERROR\].*?\[AUTH\].*?\[<HOST>\]";
    $f[]="";
    $f[]="ignoreregex = ";
    $f[]="";
    $f[]="[Init]";
    // $f[]="datepattern = %%Y-%%b-%%d %%H:%%M:%%S";
    $f[]="journalmatch =";
    $f[]="";

    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/rdpproxy.conf\n";
    @file_put_contents("/etc/fail2ban/filter.d/rdpproxy.conf", @implode("\n", $f));

}

function artica_conf(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$f[]="[Definition]";
	$f[]="actionstart 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-start-service";
	$f[]="actionstop 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-stop-service";
	$f[]="actioncheck 	= ";
	$f[]="actionban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-ban <ip> ssh";
	$f[]="actionunban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-unban <ip> ssh";
	$f[]="[Init]";
	$f[]="init = 123";
	$f[]="";
	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/artica.conf\n";
	@file_put_contents("/etc/fail2ban/action.d/artica.conf", @implode("\n", $f));
	$f=array();
	$f[]="[Definition]";
	$f[]="actionstart 	= ";
	$f[]="actionstop 	= ";
	$f[]="actioncheck 	= ";
	$f[]="actionban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-ban <ip> ids";
	$f[]="actionunban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-unban <ip> ids";
	$f[]="[Init]";
	$f[]="init = 123";
	$f[]="";
	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/artica-suricata.conf\n";
	@file_put_contents("/etc/fail2ban/action.d/artica-suricata.conf", @implode("\n", $f));
	$f=array();
	$f[]="[Definition]";
	$f[]="actionstart 	= ";
	$f[]="actionstop 	= ";
	$f[]="actioncheck 	= ";
	$f[]="actionban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-ban <ip> ftp";
	$f[]="actionunban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-unban <ip> ftp";
	$f[]="[Init]";
	$f[]="init = 123";
	$f[]="";
	echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/artica-proftpd.conf\n";
	@file_put_contents("/etc/fail2ban/action.d/artica-proftpd.conf", @implode("\n", $f));
    $f=array();
    $f[]="[Definition]";
    $f[]="actionstart 	= ";
    $f[]="actionstop 	= ";
    $f[]="actioncheck 	= ";
    $f[]="actionban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-ban <ip> smtp";
    $f[]="actionunban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-unban <ip> smtp";
    $f[]="[Init]";
    $f[]="init = 123";
    $f[]="";
    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/artica-smtp.conf\n";
    @file_put_contents("/etc/fail2ban/action.d/artica-smtp.conf", @implode("\n", $f));

    $f=array();
    $f[]="[Definition]";
    $f[]="actionstart 	= ";
    $f[]="actionstop 	= ";
    $f[]="actioncheck 	= ";
    $f[]="actionban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-ban <ip> articaweb";
    $f[]="actionunban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-unban <ip> articaweb";
    $f[]="[Init]";
    $f[]="init = 123";
    $f[]="";
    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/artica-webauth.conf\n";
    @file_put_contents("/etc/fail2ban/action.d/artica-webauth.conf", @implode("\n", $f));

    $f=array();
    $f[]="[Definition]";
    $f[]="actionstart 	= ";
    $f[]="actionstop 	= ";
    $f[]="actioncheck 	= ";
    $f[]="actionban 	= ";
    $f[]="actionunban 	= ";
    $f[]="[Init]";
    $f[]="init = 123";
    $f[]="";
    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/proxy_geoip.conf\n";
    @file_put_contents("/etc/fail2ban/action.d/proxy_geoip.conf", @implode("\n", $f));



    $f=array();
    $f[]="[Definition]";
    $f[]="actionstart 	= ";
    $f[]="actionstop 	= ";
    $f[]="actioncheck 	= ";
    $f[]="actionban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-ban <ip> rdp";
    $f[]="actionunban 	= $php /usr/share/artica-postfix/exec.fail2ban.php --notify-unban <ip> rdp";
    $f[]="[Init]";
    $f[]="init = 123";
    $f[]="";
    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/action.d/rdpproxy.conf\n";
    @file_put_contents("/etc/fail2ban/action.d/rdpproxy.conf", @implode("\n", $f));
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("fail2ban-server");
	$GLOBALS["TITLENAME"]="Fail2Ban server";
	$GLOBALS["OUTPUT"]=true;
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, arpd not installed\n";}
		return;
	}
	
	if($unix->ServerRunSince()<3){
		echo "Please wait (server was started)\n";
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/fail2ban/fail2ban.pid", $pid);
		return true;
	}



    squid_admin_mysql(1,"Starting Fail2ban service",null,__FILE__,__LINE__);
	@mkdir("/var/run/fail2ban",0755,true);
	$cmd="$Masterbin -s /var/run/fail2ban/fail2ban.sock -x -b >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		return true;

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		return false;
	}


}
function stop($aspid=false){
	$GLOBALS["TITLENAME"]="Fail2Ban server";
	$GLOBALS["OUTPUT"]=true;
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}
function postfix_rbl_conf(){

$f[]="# Fail2Ban filter for Postfix's RBL based Blocked hosts";
$f[]="#";
$f[]="#";
$f[]="";
$f[]="[INCLUDES]";
$f[]="";
$f[]="# Read common prefixes. If any customizations available -- read them from";
$f[]="# common.local";
$f[]="before = common.conf";
$f[]="";
$f[]="[Definition]";
$f[]="_daemon = postfix(-\w+)?/smtpd";
$f[]="failregex = NOQUEUE: reject:.*?from.*?\[<HOST>\].*?blocked using.*?from=<(\S*)>\s+to=<(\S+)>.*?helo=<(\S*)>";
$f[]="ignoreregex =";

    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/postfix-rbl.conf done\n";
@file_put_contents("/etc/fail2ban/filter.d/postfix-rbl.conf",@implode("\n",$f));

}

function postfix_conf(){
    $f[]="# Fail2Ban filter for selected Postfix SMTP rejections";
    $f[]="#";
    $f[]="#";
    $f[]="";
    $f[]="[INCLUDES]";
    $f[]="";
    $f[]="# Read common prefixes. If any customizations available -- read them from";
    $f[]="# common.local";
    $f[]="before = common.conf";
    $f[]="";
    $f[]="[Definition]";
    $f[]="";
    $f[]="_daemon = postfix(-\w+)?/(?:submission/|smtps/)?smtp[ds]";
    $f[]="";
    $f[]="failregex = NOQUEUE: reject: RCPT from \S+\[<HOST>\]: 554 5\.7\.1 .*$";
    $f[]="  NOQUEUE: reject: RCPT from \S+\[<HOST>\]: 450 4\.7\.1 Client host rejected: cannot find your hostname, (\[\S*\]); from=<\S*> to=<\S+> .*?proto=ESMTP helo=<\S*>$";
    $f[]="  NOQUEUE: reject: RCPT from \S+\[<HOST>\]: 450 4\.7\.1 : Helo command rejected: Host not found; from=<> to=<> proto=ESMTP helo= ";
    $f[]="  NOQUEUE: reject: EHLO from \S+\[<HOST>\]: 504 5\.5\.2 <\S+>: Helo command rejected: need fully-qualified hostname;";
    $f[]="  NOQUEUE: reject: VRFY from \S+\[<HOST>\]: 550 5\.1\.1 .*$";
    $f[]="  NOQUEUE: reject: RCPT from \S+\[<HOST>\]: 450 4\.1\.8 <\S*>: Sender address rejected: Domain not found; from=<\S*> to=<\S+>.*?helo=<\S*>";
    $f[]="  improper command pipelining after \S+ from [^[]*\[<HOST>\]:?";
    $f[]="  reject:.*?from.*?\[<HOST>\].*?cannot find your hostname,.*?from=<\S*> to=<\S*>.*?helo=<\S*>";
    $f[]="  reject: RCPT from .*?\[<HOST>\]:.*?Client host rejected: cannot find your hostname";
    $f[]="  lost connection after AUTH from .*?\[<HOST>\]";
    $f[]="";
    $f[]="";
    $f[]="ignoreregex = ";
    $f[]="";
    $f[]="[Init]";
    $f[]="";
    $f[]="journalmatch = _SYSTEMD_UNIT=postfix.service";
    $f[]="";
    echo "Starting......: ".date("H:i:s")." /etc/fail2ban/filter.d/postfix.conf done\n";
    @file_put_contents("/etc/fail2ban/filter.d/postfix.conf",@implode("\n",$f));

}

