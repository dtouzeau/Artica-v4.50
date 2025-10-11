<?php
$GLOBALS["VERBOSE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.template-admin.inc");
include_once(dirname(__FILE__)."/ressources/class.freeradius.certificate.inc");
include_once(dirname(__FILE__)."/ressources/class.samba.privileges.inc");

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--reload"){reload();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--dictionary"){dictionary();exit;}
if($argv[1]=="--radtest"){radtest();exit;}
if($argv[1]=="--nas"){ScanNasTypes();exit;}


function buildscriptFreeRadius(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $daemonbin=$unix->find_program("freeradius");
    if(!is_file($daemonbin)){return;}
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          freeradius";
    $f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$network \$time";
    $f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: radius daemon";
    $f[]="# chkconfig: 2345 11 89";
    $f[]="# description: Extensible, configurable radius daemon";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="   $php ".__FILE__." --start \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="   $php ".__FILE__." --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="   $php ".__FILE__." --restart \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="   $php ".__FILE__." --reload \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]=" force-reload)";
    $f[]="   $php ".__FILE__." --reload \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";

    $INITD_PATH="/etc/init.d/freeradius";
    _out("freeradius: [INFO] Writing $INITD_PATH with new config");
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

}
function build_progress($pourc,$text){
    $echotext=$text;
    _out("{$pourc}% $echotext");
    $cachefile=PROGRESS_DIR."/freeradius.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function build_restart_progress($pourc,$text){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"freeradius.restart.progress");
}
function build_radtest_progress($pourc,$text){
    $echotext=$text;
    _out("{$pourc}% $echotext");
    $cachefile=PROGRESS_DIR."/radtest.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function install(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreeRadius", 1);
    build_progress(10, "{installing}");
    buildscriptFreeRadius();
    build_progress(40, "{configuring}");
    build();
    build_progress(50, "{restarting}");
    stop();
    start();
    build_progress(100, "{APP_FREERADIUS}: {done}");
}

function restart(){

    build_restart_progress(15, "{configuring}");
    build();
    if(!CheckAndBackupConfig()){
        build_restart_progress(110, "{configuring} {failed}");
        return;
    }


    build_restart_progress(20,"{stopping_service}...");
    if(!stop()){
        build_restart_progress(110,"{stopping_service}...{failed}");
        return;
    }

    build_restart_progress(50,"{starting_service}...");
    if(!start()){
        build_restart_progress(110,"{starting_service}...{failed}");
        return;
    }
    build_restart_progress(100,"{starting_service} {success}...");
}

function reload():bool{
    $unix=new unix();
    $kill=$unix->find_program("kill");
    build_restart_progress(30, "{configuring}");
    build();
    CheckCertificates();
    build_restart_progress(30, "{checking}");
    if(!CheckAndBackupConfig()){
        build_restart_progress(110, "{failed}");
        return false;
    }

    $pid=freeradius_pid();
    if($unix->process_exists($pid)){
        $pidtime=$unix->PROCCESS_TIME_MIN($pid);
        _out("Reloading : Running pid $pid since {$pidtime}mn");
        system("$kill -HUP $pid");
        build_restart_progress(100, "{success}");
        return true;
    }
    _out("Reloading : Starting service");
    if(!start()){
           build_restart_progress(110, "{failed}");
            return false;
        }

    build_restart_progress(100, "{success}");
    return true;
}


function uninstall(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFreeRadius", 0);
    build_progress(50, "{uninstalling}");
    remove_service("/etc/init.d/freeradius");
    if(is_file("/etc/monit/conf.d/APP_FREERADIUS.monitrc")){
        @unlink("/etc/monit/conf.d/APP_FREERADIUS.monitrc");
        build_progress(80, "{uninstalling}");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    build_progress(100, "{APP_FREERADIUS}:{uninstall} {done}");
}

function build_syslog(){
    $sfile="/etc/rsyslog.d/radiusd.conf";
    $md51=null;
    if(is_file($sfile)) {
        $md51 = md5_file($sfile);
    }
    $h[]="if  (\$programname =='radiusd') then {";
    $h[]=buildlocalsyslogfile("/var/log/freeradius/server.log");
    $h[]=BuildRemoteSyslogs("radius");
    $h[]="\t& stop";
    $h[]="}";
    @file_put_contents($sfile,@implode("\n",$h));
    $md52=md5_file($sfile);
    if($md52==$md51){return true;}
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    return true;

}


function build_monit(){
    $FreeRadiusListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenPort"));
    $FreeRadiusListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenInterface"));
    if($FreeRadiusListenPort==0){$FreeRadiusListenPort=1812;}
    if($FreeRadiusListenInterface==null){$FreeRadiusListenIP="127.0.0.1";}
    $ldap=new clladp();
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $f[]="check process APP_FREERADIUS with pidfile /var/run/freeradius/freeradius.pid";
    $f[]="\tstart program = \"/etc/init.d/freeradius start\"";
    $f[]="\tstop program = \"/etc/init.d/freeradius stop\"";

    $f[]="\tif failed host $FreeRadiusListenIP port $FreeRadiusListenPort type udp protocol radius";
    $f[]="\t\tsecret \"$ldap->ldap_password\"";
    $f[]="\tthen restart";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_FREERADIUS.monitrc", @implode("\n", $f));
    if(!is_file("/etc/monit/conf.d/APP_FREERADIUS.monitrc")){
        _out("/etc/monit/conf.d/APP_FREERADIUS.monitrc failed !!!");
    }

    system("$nohup /etc/init.d/monit reload >/dev/null 2>&1 &");

}
function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function _out($text):bool{
    $date=date("H:i:s");
    $STAT="INIT";
    if(preg_match("#^\[(.+?)\]\s+(.+)#",$text,$re)){$STAT=$re[1];$text=$re[2];}
    echo "$STAT......: $date radiusd: $text\n";
    if(!function_exists("openlog")){return true;}
    openlog("radiusd", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}
function build(){
    build_monit();
    $unix=new unix();
    $checkrad=$unix->find_program("checkrad");
    @mkdir("/var/log/freeradius",0755,true);
    _out("Starting:checkrad: `$checkrad`");
    $FreeRadiusMaxClients=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusMaxClients"));
    if($FreeRadiusMaxClients==0){$FreeRadiusMaxClients=150;}
    $FreeRadiusDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusDebug"));

    $IsMySQL=IsMySQL();	$UseMySQL=false;
    if($IsMySQL>0){$UseMySQL=true;}
    $debug_level=0;
    if($FreeRadiusDebug==1){
        $debug_level=9;
    }
    $f[]="prefix = /usr";
    $f[]="exec_prefix = /usr";
    $f[]="sysconfdir = /etc";
    $f[]="localstatedir = /var";
    $f[]="sbindir = \${exec_prefix}/sbin";
    $f[]="logdir = /var/log/freeradius";
    $f[]="raddbdir = /etc/freeradius/3.0";
    $f[]="radacctdir = \${logdir}/radacct";
    $f[]="name = freeradius";
    $f[]="confdir = \${raddbdir}";
    $f[]="modconfdir = \${confdir}/mods-config";
    $f[]="certdir = \${confdir}/certs";
    $f[]="cadir   = \${confdir}/certs";
    $f[]="run_dir = \${localstatedir}/run/\${name}";
    $f[]="db_dir = \${raddbdir}";
    $f[]="libdir = /usr/lib/freeradius";
    $f[]="pidfile = \${run_dir}/\${name}.pid";
    $f[]="correct_escapes = true";
    $f[]="max_request_time = 30";
    $f[]="cleanup_delay = 5";
    $f[]="max_requests = 16384";
    $f[]="hostname_lookups = no";
    $f[]="debug_level = $debug_level";
    $f[]="";
    $f[]="log {";
    $f[]="	destination = syslog";
    $f[]="	colourise = no";
    $f[]="	file = \${logdir}/radius.log";
    $f[]="	syslog_facility = daemon";
    $f[]="	stripped_names = no";
    $f[]="	auth = yes";
    $f[]="	auth_badpass = yes";
    $f[]="	auth_goodpass = yes";
    $f[]="	msg_denied = \"You are already logged in - access denied\"";
    $f[]="}";
    $f[]="";
    $f[]="checkrad = $checkrad";
    $f[]="";
    $f[]="security {";
    $f[]="	user = freerad";
    $f[]="	group = freerad";
    $f[]="	allow_core_dumps = no";
    $f[]="	max_attributes = 200";
    $f[]="	reject_delay = 1";
    $f[]="	status_server = yes";
    $f[]="";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="proxy_requests  = yes";
    $f[]="\$INCLUDE proxy.conf";
    $f[]="\$INCLUDE clients.conf";
    $f[]="thread pool {";
    $f[]="	start_servers = 5";
    $f[]="	max_servers = 32";
    $f[]="	min_spare_servers = 3";
    $f[]="	max_spare_servers = 10";
    $f[]="	max_requests_per_server = 0";
    $f[]="	auto_limit_acct = no";
    $f[]="  max_requests      = ".$FreeRadiusMaxClients*32;
    $f[]="  max_servers       = ".$FreeRadiusMaxClients/32;
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="modules {";
    $f[]="	\$INCLUDE modules/";
    $f[]="}";
    $f[]="";
    $f[]="instantiate {";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="policy {";
    $f[]="	\$INCLUDE policy.d/";
    $f[]="}";
    $f[]="";
    $f[]="\$INCLUDE sites-enabled/";
    $f[]="";




    dictionary();

    _out("Starting:/etc/freeradius/radiusd.conf done...");
    @mkdir("/etc/freeradius",0755,true);
    @file_put_contents("/etc/freeradius/radiusd.conf", @implode("\n", $f));
    attrs_access_reject();
    eap();
    pap();
    proxy();
    ntlm_auth();
    policy_filter();
    build_sql_connections();
    module_ldap();
    build_default();
    confusers();
    clients();
    mschap();
    module_s();
    ScanNasTypes();
    CheckCertificates();

}

function module_s(){
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==0){
        $EnableKerbAuth=0;
    }


    $f=array();

    if($EnableKerbAuth==1){

        $f[]="DEFAULT   Auth-Type = ntlm_auth";
    }

    $f[]="DEFAULT	Suffix == \".ppp\", Strip-User-Name = Yes";
    $f[]="	Hint = \"PPP\",";
    $f[]="	Service-Type = Framed-User,";
    $f[]="	Framed-Protocol = PPP";
    $f[]="";
    $f[]="DEFAULT	Suffix == \".slip\", Strip-User-Name = Yes";
    $f[]="	Hint = \"SLIP\",";
    $f[]="	Service-Type = Framed-User,";
    $f[]="	Framed-Protocol = SLIP";
    $f[]="";
    $f[]="DEFAULT	Suffix == \".cslip\", Strip-User-Name = Yes";
    $f[]="	Hint = \"CSLIP\",";
    $f[]="	Service-Type = Framed-User,";
    $f[]="	Framed-Protocol = SLIP,";
    $f[]="	Framed-Compression = Van-Jacobson-TCP-IP";
    $f[]="";
    $f[]="######################################################################";
    $f[]="";

    @file_put_contents("/etc/freeradius/mods-config/preprocess/hints", @implode("\n", $f));
    _out("Starting:/etc/freeradius/mods-config/preprocess/hints done...");


    $f=array();

}

function freeradius_pid(){
    $unix=new unix();

    $pidfile="/var/run/freeradius/freeradius.pid";

    $pid=$unix->get_pid_from_file($pidfile);
    if(!$unix->process_exists($pid)){
        $freeradius=$unix->find_program("freeradius");
        $pid=$unix->PIDOF_PATTERN($freeradius);
    }
    return $pid;
}


function dictionary():bool{

    $prefix="/usr/share/freeradius";
    return true;
}


function ntlm_auth(){
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if($EnableKerbAuth==0){return;}
    $unix=new unix();
    $ntlm_auth=$unix->find_program("ntlm_auth");
    if(!is_file($ntlm_auth)){return null;}
    winbindd_privileges();
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    $workgroup=strtoupper($array["ADNETBIOSDOMAIN"]);
    $wordkgroup2=null;
    if($workgroup<>null){
        $wordkgroup2=" --domain=$workgroup";
    }

    $f[]="exec ntlm_auth {";
    $f[]="	wait = yes";
    $f[]="	program = \"$ntlm_auth --request-nt-key$wordkgroup2 --username=%{mschap:User-Name} --password=%{User-Password}\"";
    $f[]="}";
    $f[]="";

    @mkdir("/etc/freeradius/modules",0755,true);
    @file_put_contents("/etc/freeradius/modules/ntlm_auth", @implode("\n", $f));
    _out("Starting:/etc/freeradius/modules/ntlm_auth done...");
}

function policy_filter()
{

    include_once(dirname(__FILE__)."/ressources/class.freeradius.defaults.inc");
    policyd();
    freeradius_modules();

}


function mschap(){
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if($EnableKerbAuth==0){
        $f[]="mschap {";
        $f[]="}";
        $f[]="";
        @file_put_contents("/etc/freeradius/modules/mschap", @implode("\n", $f));
        @file_put_contents("/etc/freeradius/mods-enabled/mschap", @implode("\n", $f));
        _out("Starting:/etc/freeradius/modules/mschap done...");
        return null;
    }
    $unix=new unix();
    $ntlm_auth=$unix->find_program("ntlm_auth");
    if(!is_file($ntlm_auth)){
        $f[]="mschap {";
        $f[]="}";
        $f[]="";
        @file_put_contents("/etc/freeradius/modules/mschap", @implode("\n", $f));
        @file_put_contents("/etc/freeradius/mods-enabled/mschap", @implode("\n", $f));
        _out("Starting:/etc/freeradius/modules/mschap done...");
        return null;
    }

    $unix=new unix();
    $ntlm_auth=$unix->find_program("ntlm_auth");
    $array=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    $f[]="# -*- text -*-";
    $f[]="#";
    $f[]="#  \$Id: 2170df13dbb884fde5d596eba68056781ba3160c \$";
    $f[]="";
    $f[]="# Microsoft CHAP authentication";
    $f[]="mschap {";
    $f[]="\tntlm_auth = \"$ntlm_auth --request-nt-key --allow-mschapv2 --username=%{%{Stripped-User-Name}:-%{%{User-Name}:-None}} --challenge=%{%{mschap:Challenge}:-00} --nt-response=%{%{mschap:NT-Response}:-00}\"";
    $f[]="\tntlm_auth_timeout = 10";
    $f[]="}";
    $f[]="#passchange {";
    $f[]="#		ntlm_auth = \"$ntlm_auth --helper-protocol=ntlm-change-password-1\"";
    $f[]="#		ntlm_auth_username = \"username: %{mschap:User-Name}\"";
    $f[]="#		ntlm_auth_domain = \"nt-domain: %{mschap:NT-Domain}\"";
    $f[]="#		local_cpw = \"%{exec:/path/to/script %{mschap:User-Name} %{MS-CHAP-New-Cleartext-Password}}\"";
    $f[]="#		local_cpw = \"%{sql:UPDATE radcheck set value='%{MS-CHAP-New-NT-Password}' where username='%{SQL-User-Name}' and attribute='NT-Password'}\"";
    $f[]="#}";

    @file_put_contents("/etc/freeradius/modules/mschap", @implode("\n", $f));
    _out("Starting:/etc/freeradius/modules/mschap done...");

}

function pap(){
    $f[]="pap {";
    $f[]="	auto_header = yes";
    $f[]="}";
    $f[]="";

    @mkdir("/etc/freeradius/modules",0755,true);
    @file_put_contents("/etc/freeradius/modules/pap", @implode("\n", $f));
    _out("Starting:/etc/freeradius/modules/pap done...");
}


function eap(){
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $freeradiusCertificate=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("freeradiusCertificate"));


    $default_eap_type="md5";
    $timer_expire=60;
    $ignore_unknown_eap_types="no";

    if($EnableKerbAuth==1){
       $default_eap_type = "peap";
    }

    $ttls_default_eap_type="md5";
    $ttls_copy_request_to_tunnel="no";
    $ttls_use_tunneled_reply="no";

    $peap_default_eap_type="mschapv2";
    $peap_copy_request_to_tunnel="no";
    $peap_use_tunneled_reply="no";

    $f[]="	eap {";
    $f[]="			default_eap_type = $default_eap_type";
    $f[]="			timer_expire     = $timer_expire";
    $f[]="			cisco_accounting_username_bug = no";
    $f[]="			ignore_unknown_eap_types=$ignore_unknown_eap_types";
    $f[]="			max_sessions = 4096";
    $f[]="			md5 {";
    $f[]="			}";
    $f[]="";
    $f[]="		leap {";
    $f[]="		}";
    $f[]="";
    $f[]="		gtc {";
    $f[]="			auth_type = PAP";
    $f[]="		}";
    $f[]="";

    $freeradius_certificate=new freeradius_certificate($freeradiusCertificate);
    $f[]=$freeradius_certificate->GetConf();
    $f[]="";
    $f[]="		peap {";
    $f[]="			default_eap_type = $peap_default_eap_type";
    $f[]="			copy_request_to_tunnel = $peap_copy_request_to_tunnel";
    $f[]="			use_tunneled_reply = $peap_use_tunneled_reply";
    $f[]="		#	proxy_tunneled_request_as_eap = yes";
    $f[]="			virtual_server = \"inner-tunnel\"";
    $f[]="		}";
    $f[]="";
    $f[]="		mschapv2 {";
    $f[]="		}";
    $f[]="	}";
    $f[]="";

    @file_put_contents("/etc/freeradius/modules/eap.conf", @implode("\n", $f));
    _out("Starting:/etc/freeradius/eap.conf done...");

}

function proxy(){
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));


    $f[]="proxy server {";
    $f[]="default_fallback = no";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="home_server localhost {";
    $f[]="	type = auth";
    $f[]="	ipaddr = 127.0.0.1";
    $f[]="	# virtual_server = foo";
    $f[]="	port = 1812";
    $f[]="	secret = testing123";
    $f[]="#	src_ipaddr = 127.0.0.1";
    $f[]="	require_message_authenticator = yes";
    $f[]="	response_window = 20";
    $f[]="#	no_response_fail = no";
    $f[]="	zombie_period = 40";
    $f[]="	revive_interval = 120";
    $f[]="	status_check = status-server";
    $f[]="	# username = \"test_user_please_reject_me\"";
    $f[]="	# password = \"this is really secret\"";
    $f[]="	check_interval = 30";
    $f[]="	num_answers_to_alive = 3";
    $f[]="	coa {";
    $f[]="		irt = 2";
    $f[]="		mrt = 16";
    $f[]="		mrc = 5";
    $f[]="		mrd = 30";
    $f[]="	}";
    $f[]="}";
    $f[]="";
    $f[]="home_server_pool my_auth_failover {";
    $f[]="	type = fail-over";
    $f[]="	#virtual_server = pre_post_proxy_for_pool";
    $f[]="	home_server = localhost";
    $f[]="	#fallback = virtual.example.com";
    $f[]="}";
    $f[]="";
    $f[]="realm example.com {";
    $f[]="	auth_pool = my_auth_failover";
    $f[]="#	acct_pool = acct";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="realm LOCAL {";
    $f[]="}";
    $f[]="";
    $f[]="";

    @file_put_contents("/etc/freeradius/proxy.conf", @implode("\n", $f));
    _out("Starting:/etc/freeradius/proxy.conf done...");
}

function IsMySQL(){
    if(is_file("/usr/sbin/chilli")){return 1;}

    $q=new mysql();
    $c=0;


    $sql="SELECT ID FROM freeradius_db WHERE connectiontype='mysql_local' and `enabled`=1";
    $results = $q->QUERY_SQL($sql,"artica_backup");
    if(mysqli_num_rows($results)>0){$c++;}
    return $c;
}

function build_sql_connections(){
    if(!is_file("/usr/sbin/chilli")){
        if(IsMySQL()==0){return;}
    }
    $q=new mysql();
    $f[]="# -*- text -*-";
    $f[]="##";
    $f[]="## sql.conf -- SQL modules";
    $f[]="##";
    $f[]="##	\$Id\$";
    $f[]="";
    $f[]="######################################################################";
    $f[]="#";
    $f[]="#  Configuration for the SQL module";
    $f[]="#";
    $f[]="#  The database schemas and queries are located in subdirectories:";
    $f[]="#";
    $f[]="#	sql/DB/schema.sql	Schema";
    $f[]="#	sql/DB/dialup.conf	Basic dialup (including policy) queries";
    $f[]="#	sql/DB/counter.conf	counter";
    $f[]="#	sql/DB/ippool.conf	IP Pools in SQL";
    $f[]="#	sql/DB/ippool.sql	schema for IP pools.";
    $f[]="#";
    $f[]="#  Where \"DB\" is mysql, mssql, oracle, or postgresql.";
    $f[]="#";
    $f[]="";
    $f[]="sql {";
    $f[]="	#";
    $f[]="	#  Set the database to one of:";
    $f[]="	#";
    $f[]="	#	mysql, mssql, oracle, postgresql";
    $f[]="	#";
    $f[]="	database = \"mysql\"";
    $f[]="";
    $f[]="	#";
    $f[]="	#  Which FreeRADIUS driver to use.";
    $f[]="	#";
    $f[]="	driver = \"rlm_sql_\${database}\"";
    $f[]="";
    $f[]="	# Connection info:";
    $f[]="	server = \"$q->mysql_server\"";
    $f[]="	port = $q->mysql_port";
    $f[]="	login = \"$q->mysql_admin\"";
    $f[]="	password = \"$q->mysql_password\"";
    $f[]="	radius_db = \"artica_backup\"";
    $f[]="	acct_table1 = \"radacct\"";
    $f[]="	acct_table2 = \"radacct\"";
    $f[]="";
    $f[]="	# Allow for storing data after authentication";
    $f[]="	postauth_table = \"radpostauth\"";
    $f[]="";
    $f[]="	authcheck_table = \"radcheck\"";
    $f[]="	authreply_table = \"radreply\"";
    $f[]="";
    $f[]="	groupcheck_table = \"radgroupcheck\"";
    $f[]="	groupreply_table = \"radgroupreply\"";
    $f[]="";
    $f[]="	# Table to keep group info";
    $f[]="	usergroup_table = \"radusergroup\"";
    $f[]="";
    $f[]="	# If set to 'yes' (default) we read the group tables";
    $f[]="	# If set to 'no' the user MUST have Fall-Through = Yes in the radreply table";
    $f[]="	# read_groups = yes";
    $f[]="";
    $f[]="	# Remove stale session if checkrad does not see a double login";
    $f[]="	deletestalesessions = yes";
    $f[]="";
    $f[]="	# Print all SQL statements when in debug mode (-x)";
    $f[]="	sqltrace = no";
    $f[]="	sqltracefile = \${logdir}/sqltrace.sql";
    $f[]="";
    $f[]="	# number of sql connections to make to server";
    $f[]="	num_sql_socks = 5";
    $f[]="";
    $f[]="	# number of seconds to dely retrying on a failed database";
    $f[]="	# connection (per_socket)";
    $f[]="	connect_failure_retry_delay = 60";
    $f[]="";
    $f[]="	# lifetime of an SQL socket.  If you are having network issues";
    $f[]="	# such as TCP sessions expiring, you may need to set the socket";
    $f[]="	# lifetime.  If set to non-zero, any open connections will be";
    $f[]="	# closed \"lifetime\" seconds after they were first opened.";
    $f[]="	lifetime = 0";
    $f[]="";
    $f[]="	# Maximum number of queries used by an SQL socket.  If you are";
    $f[]="	# having issues with SQL sockets lasting \"too long\", you can";
    $f[]="	# limit the number of queries performed over one socket.  After";
    $f[]="	# \"max_qeuries\", the socket will be closed.  Use 0 for \"no limit\".";
    $f[]="	max_queries = 0";
    $f[]="";
    $f[]="	# Set to 'yes' to read radius clients from the database ('nas' table)";
    $f[]="	# Clients will ONLY be read on server startup.  For performance";
    $f[]="	# and security reasons, finding clients via SQL queries CANNOT";
    $f[]="	# be done \"live\" while the server is running.";
    $f[]="	# ";
    $f[]="	#readclients = yes";
    $f[]="";
    $f[]="	# Table to keep radius client info";
    $f[]="	nas_table = \"nas\"";
    $f[]="";
    $f[]="	# Read driver-specific configuration";
    $f[]="	\$INCLUDE sql/\${database}/dialup.conf";
    $f[]="}";
    $f[]="sqlcounter noresetBytecounter {
counter-name = Total-Max-Octets
check-name = Max-Octets
reply-name = ChilliSpot-Max-Total-Octets
sqlmod-inst = sql
key = User-Name
reset = never
query = \"SELECT (SUM(AcctInputOctets)+SUM(AcctOutputOctets)) FROM radacct WHERE UserName='%{%k}'\"
}\n";
    @file_put_contents("/etc/freeradius/sql.conf", @implode("\n", $f));

}


function build_ldap_connections(){
    if(isset($GLOBALS["build_ldap_connections"])){return $GLOBALS["build_ldap_connections"];}

    $FreeRadiusEnableLocalLdap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusEnableLocalLdap"));

    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));


    if($EnableActiveDirectoryFeature==1) {

        if($EnableKerbAuth==1){

        }

    }


    if($FreeRadiusEnableLocalLdap==1) {
        $f[] = "\tldap0";
        $f[] = "\tif (notfound) {";
        $f[] = "\t\tldap0";
        $f[] = "\t}";
        $f[] = "\tif (reject) {";
        $f[] = "\t\tldap0";
        $f[] = "\t}";
    }

        $q=new mysql();
        $sql = "SELECT ID FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
        $results = $q->QUERY_SQL($sql);
        while ($ligne = mysqli_fetch_assoc($results)) {
            $f[]="\t$TR[0]";
            $f[] = "\tif (notfound) {";
            $f[] = "\t\tldap{$ligne["ID"]}";
            $f[] = "\t}";
            $f[] = "\tif (reject) {";
            $f[] = "\t\tldap{$ligne["ID"]}";
            $f[] = "\t}";
        }



    if($EnableActiveDirectoryFeature==1) {
        $tpl=new template_admin();
        $CNXS=$tpl->ACTIVE_DIRECTORY_LDAP_CONNECTIONS();
        foreach ($CNXS as $index=>$ligne) {
            $TR[]="ad$index";

        }
        $f[]="\t$TR[0]";
        if(count($TR)>0){
            foreach ($TR as $num=>$ldapid){
                $f[]="\tif (notfound) {";
                $f[]="\t\t$ldapid";
                $f[]="\t}";
                $f[]="\tif (reject) {";
                $f[]="\t\t$ldapid";
                $f[]="\t}";
            }



        $sql = "SELECT ID FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
        $results = $q->QUERY_SQL($sql, "artica_backup");
        while ($ligne = mysqli_fetch_assoc($results)) {
            $f[] = "\tif (notfound) {";
            $f[] = "\t\tad{$ligne["ID"]}";
            $f[] = "\t}";
            $f[] = "\tif (reject) {";
            $f[] = "\t\tad{$ligne["ID"]}";
            $f[] = "\t}";
        }
    }

    }else{
        if($q->COUNT_ROWS("freeradius_db", "artica_backup")>0){
            $sql="SELECT ID FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
            $results = $q->QUERY_SQL($sql,"artica_backup");
            while ($ligne = mysqli_fetch_assoc($results)) {$TR[]="ldap{$ligne["ID"]}";}

            $sql="SELECT ID FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
            $results = $q->QUERY_SQL($sql,"artica_backup");
            while ($ligne = mysqli_fetch_assoc($results)) {$TR[]="ldap{$ligne["ID"]}";}

            $f[]="\t{$TR[0]}";
            if(count($TR)>0){
                foreach ($TR as $num=>$ldapid){
                    $f[]="\tif (notfound) {";
                    $f[]="\t\t$ldapid";
                    $f[]="\t}";
                    $f[]="\tif (reject) {";
                    $f[]="\t\t$ldapid";
                    $f[]="\t}";
                }

            }
        }
    }


    $GLOBALS["build_ldap_connections"]=@implode("\n", $f);
    return $GLOBALS["build_ldap_connections"];

}

function build_default(){
    $sock=new sockets();
    $q=new mysql();
    $unix=new unix();
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    $FreeRadiusListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenPort"));
    if($FreeRadiusListenPort==0){$FreeRadiusListenPort=1812;}
    $FreeRadiusListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenInterface"));

    //$isLDAP=isLDAP();

    $FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
    if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
    $IsMySQL=IsMySQL();	$UseMySQL=false;
    if($IsMySQL>0){$UseMySQL=true;}
    $authenticate_ntlm_auth=null;
    if($EnableKerbAuth==1) {
        $authenticate_ntlm_auth = "ntlm_auth";
    }
    if($FreeRadiusListenInterface==null){
        $FreeRadiusListenInterface="eth0";
    }

    $ipaddr=$unix->InterfaceToIPv4($FreeRadiusListenInterface);

    $f[]="server default {";
    $f[]="\tlisten {";
    $f[]="\t\ttype = auth";
    $f[]="\t\tipaddr = $ipaddr";
    $f[]="\t\tport = $FreeRadiusListenPort";
    $f[]="";
    $f[]="\t\tlimit {";
    $f[]="\t\t\tmax_connections = 16";
    $f[]="	      lifetime = 0";
    $f[]="	      idle_timeout = 30";
    $f[]="	}";
    $f[]="}";
    $f[]="";
    $f[]="\tlisten {";
    $f[]="\t\tipaddr = $ipaddr";
    $f[]="\t\tport = 0";
    $f[]="\t\ttype = acct";
    $f[]="\t\tlimit {";
    $f[]="";
    $f[]="\t\t}";
    $f[]="\t}";

    $f[]="";
    $f[]="";
    $f[]="authorize {";
    $f[]="	filter_username";
    $f[]="	preprocess";
    $f[]="	chap";
    $f[]="	mschap";
    $f[]="	digest";
    $f[]="	suffix";
    $f[]="";
    $f[]="	eap {";
    $f[]="		ok = return";
    $f[]="	}";
    $f[]="	files";
    $f[]="	-sql";
    $f[]="	-ldap";
    $f[]="	expiration";
    $f[]="	logintime";
    $f[]="	pap";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="";
    $f[]="authenticate {";
    $f[]="	Auth-Type PAP {";
    $f[]="		pap";
    $f[]="	}";
    $f[]="";
    $f[]="	Auth-Type CHAP {";
    $f[]="		chap";
    $f[]="	}";
    $f[]="";
    $f[]="	Auth-Type MS-CHAP {";
    $f[]="		mschap";
    $f[]="	}";
    $f[]="\t$authenticate_ntlm_auth";
    $f[]="\tmschap";
    $f[]="\tdigest";
    $f[]="\teap";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="preacct {";
    $f[]="	preprocess";
    $f[]="	acct_unique";
    $f[]="	suffix";
    $f[]="	files";
    $f[]="}";
    $f[]="";
    $f[]="accounting {";
    $f[]="	detail";
    $f[]="	unix";
    $f[]="	-sql";
    $f[]="	exec";
    $f[]="	attr_filter.accounting_response";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="session {";
    $f[]="";
    $f[]="}";

    $f[]="post-auth {";
    $f[]="	update {";
    $f[]="		&reply: += &session-state:";
    $f[]="	}";
    $f[]="	-sql";
    $f[]="	exec";
    $f[]="	remove_reply_message_if_eap";
    $f[]="";
    $f[]="	Post-Auth-Type REJECT {";
    $f[]="		-sql";
    $f[]="		attr_filter.access_reject";
    $f[]="		eap";
    $f[]="		remove_reply_message_if_eap";
    $f[]="	}";
    $f[]="";
    $f[]="	Post-Auth-Type Challenge {";
    $f[]="	}";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="pre-proxy {";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="post-proxy {";
    $f[]="	eap";
    $f[]="";
    $f[]="}";
    $f[]="}\n";
    if(is_file("/etc/freeradius/sites-enabled/inner-tunnel")){
        @unlink("/etc/freeradius/sites-enabled/inner-tunnel");
    }
    if(!is_dir("/etc/freeradius/sites-enabled")) {
        @mkdir("/etc/freeradius/sites-enabled", 0755, true);
    }
    @file_put_contents("/etc/freeradius/sites-enabled/default", @implode("\n", $f));
    _out("Starting:/etc/freeradius/sites-enabled/default done...");

    $f=array();

    $f[]="server inner-tunnel {";
    $f[]="\tlisten {";
    $f[]="\t\tipaddr = 127.0.0.1";
    $f[]="\t\tport = 18120";
    $f[]="\t\ttype = auth";
    $f[]="}";
    $f[]="\tauthorize {";
    $f[]="\t\tfilter_username";
    $f[]="\t\tchap";
    $f[]="\t\tmschap";
    $f[]="\t\tsuffix";
    $f[]="\t\tupdate control {";
    $f[]="\t\t\t&Proxy-To-Realm := LOCAL";
    $f[]="\t\t}";
    $f[]="\t\teap {";
    $f[]="\t\t\tok = return";
    $f[]="\t\t}";
    $f[]="\t\tfiles";
    $f[]="\t\t-sql";
    $f[]="\t\t-ldap";
    $f[]="\t\texpiration";
    $f[]="\t\tlogintime";
    $f[]="\t\tpap";
    $f[]="\t}";
    $f[]="\tauthenticate {";
    $f[]="\t\tAuth-Type PAP {";
    $f[]="\t\t\tpap";
    $f[]="\t\t}";
    $f[]="\t\tAuth-Type CHAP {";
    $f[]="\t\t\tchap";
    $f[]="\t\t}";
    $f[]="\tAuth-Type MS-CHAP {";
    $f[]="\t\tmschap";
    $f[]="\t}";
    $f[] ="\t$authenticate_ntlm_auth";
    $f[]="\tmschap";
    $f[]="\teap";
    $f[]="\t}";
    $f[]="\tsession {";
    $f[]="\t\tradutmp";
    $f[]="\t}";
    $f[]="\tpost-auth {";
    $f[]="\t\t-sql";
    $f[]="\t\tif (0) {";
    $f[]="\t\t\tupdate reply {";
    $f[]="\t\t\tUser-Name !* ANY";
    $f[]="\t\t\tMessage-Authenticator !* ANY";
    $f[]="\t\t\tEAP-Message !* ANY";
    $f[]="\t\t\tProxy-State !* ANY";
    $f[]="\t\t\tMS-MPPE-Encryption-Types !* ANY";
    $f[]="\t\t\tMS-MPPE-Encryption-Policy !* ANY";
    $f[]="\t\t\tMS-MPPE-Send-Key !* ANY";
    $f[]="\t\t\tMS-MPPE-Recv-Key !* ANY";
    $f[]="\t\t}";
    $f[]="\t\tupdate {";
    $f[]="\t\t\t&outer.session-state: += &reply:";
    $f[]="\t\t}";
    $f[]="\t}";
    $f[]="\tPost-Auth-Type REJECT {";
    $f[]="\t\t-sql";
    $f[]="\t\tattr_filter.access_reject";
    $f[]="\t\tupdate outer.session-state {";
    $f[]="\t\t&Module-Failure-Message := &request:Module-Failure-Message";
    $f[]="\t\t}";
    $f[]="\t}";
    $f[]="}";
    $f[]="pre-proxy {";
    $f[]="}";
    $f[]="post-proxy {";
    $f[]="	eap";
    $f[]="}";
    $f[]="}";

    @file_put_contents("/etc/freeradius/sites-enabled/inner-tunnel", @implode("\n", $f));
    _out("Starting:/etc/freeradius/sites-enabled/inner-tunnel done...");
}

function module_ldap(){
    $ldap=new clladp();
    $q=new mysql();
    $sock=new sockets();
    $FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
    if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
    $EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));


    if($FreeRadiusEnableLocalLdap==1){
        if($EnableOpenLDAP==1) {
            $f[] = "ldap ldap0 {";
            $f[] = "        server = \"$ldap->ldap_host\"";
            $f[] = "        basedn = \"dc=organizations,$ldap->suffix\"";
            $f[] = "        filter = \"(uid=%{%{Stripped-User-Name}:-%{User-Name}})\"";
            $f[] = "        ldap_connections_number = 5";
            $f[] = "        timeout = 4";
            $f[] = "        timelimit = 3";
            $f[] = "        net_timeout = 1";
            $f[] = "        tls {";
            $f[] = "                start_tls = no";
            $f[] = "        }";
            $f[] = "        dictionary_mapping = \${confdir}/ldap.attrmap";
            $f[] = "        password_attribute = userPassword";
            $f[] = "        edir_account_policy_check = no";
            $f[] = "        access_attr_used_for_allow = no";
            $f[] = "}\n";
        }

    }

    $sql="SELECT ID,params FROM freeradius_db WHERE connectiontype='ldap' and `enabled`=1";
    $results = $q->QUERY_SQL($sql,"artica_backup");
    while ($ligne = mysqli_fetch_assoc($results)) {
        $array=unserialize(base64_decode($ligne["params"]));
        if($array["LDAP_FILTER"]==null){$array["LDAP_FILTER"]="(uid=%{%{Stripped-User-Name}:-%{User-Name}})";}
        if($array["PASSWORD_ATTRIBUTE"]==null){$array["PASSWORD_ATTRIBUTE"]="userPassword";}
        if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
        $LDAP_SERVER=$array["LDAP_SERVER"];
        $LDAP_PORT=$array["LDAP_PORT"];
        $LDAP_SUFFIX=$array["LDAP_SUFFIX"];
        $LDAP_FILTER=$array["LDAP_FILTER"];
        $LDAP_DN=$array["LDAP_DN"];
        $LDAP_PASSWORD=$array["LDAP_PASSWORD"];
        $PASSWORD_ATTRIBUTE=$array["PASSWORD_ATTRIBUTE"];
        $ACCESS_ATTRIBUTE=$array["ACCESS_ATTRIBUTE"];

        $f[]="ldap ldap{$ligne["ID"]} {";
        $f[]="        server = \"$LDAP_SERVER\"";
        $f[]="        port = \"$LDAP_PORT\"";
        $f[]="        basedn = \"$LDAP_SUFFIX\"";
        $f[]="        filter = \"$LDAP_FILTER\"";
        $f[]="        identity    = \"$LDAP_DN\"";
        $f[]="        password = \"$LDAP_PASSWORD\"";
        $f[]="        ldap_connections_number = 5";
        $f[]="        timeout = 4";
        $f[]="        timelimit = 3";
        $f[]="        net_timeout = 1";
        $f[]="        tls {";
        $f[]="                start_tls = no";
        $f[]="        }";
        $f[]="        dictionary_mapping = \${confdir}/ldap.attrmap";
        $f[]="        password_attribute = $PASSWORD_ATTRIBUTE";
        if($ACCESS_ATTRIBUTE<>null){
            $f[]="        access_attr = \"$ACCESS_ATTRIBUTE\"";
            $f[]="        access_attr_used_for_allow = yes";
        }
        $f[]="        edir_account_policy_check = no";
        $f[]="}\n";


    }
    $sql="SELECT ID,params FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
    $results = $q->QUERY_SQL($sql,"artica_backup");
    while ($ligne = mysqli_fetch_assoc($results)) {
        $array=unserialize(base64_decode($ligne["params"]));
        $array["LDAP_FILTER"]="(&(sAMAccountname=%{Stripped-User-Name:-%{User-Name}})(objectClass=person))";
        $ADGROUP=trim($array["ADGROUP"]);
        if(!is_numeric($array["LDAP_PORT"])){$array["LDAP_PORT"]=389;}
        $LDAP_SERVER=$array["LDAP_SERVER"];
        $LDAP_PORT=$array["LDAP_PORT"];
        $LDAP_SUFFIX=$array["LDAP_SUFFIX"];
        $LDAP_FILTER=$array["LDAP_FILTER"];
        $LDAP_DN=$array["LDAP_DN"];
        $LDAP_PASSWORD=$array["LDAP_PASSWORD"];
        $PASSWORD_ATTRIBUTE=$array["PASSWORD_ATTRIBUTE"];
        $ACCESS_ATTRIBUTE=$array["ACCESS_ATTRIBUTE"];

        $f[]="ldap ldap{$ligne["ID"]} {";
        $f[]="        server = \"$LDAP_SERVER\"";
        $f[]="        port = \"$LDAP_PORT\"";
        $f[]="        basedn = \"$LDAP_SUFFIX\"";
        $f[]="        filter = \"$LDAP_FILTER\"";
        $f[]="        identity    = \"$LDAP_DN\"";
        $f[]="        password = \"$LDAP_PASSWORD\"";
        $f[]="        groupname_attribute = cn";
        $f[]="        groupmembership_filter = \"(|(&(objectClass=group)(member=%Ldap-UserDn}))(&(objectClass=top)(uniquemember=%{Ldap-UserDn})))\"";
        $f[]="        groupmembership_attribute = memberOf";
        $f[]="        ldap_connections_number = 5";
        $f[]="        chase_referrals = yes";
        $f[]="        rebind = yes";
        $f[]="        timeout = 4";
        $f[]="        timelimit = 3";
        $f[]="        net_timeout = 1";
        $f[]="        tls {";
        $f[]="                start_tls = no";
        $f[]="        }";
        $f[]="        dictionary_mapping = \${confdir}/ldap.attrmap";
        if($ACCESS_ATTRIBUTE<>null){
            $f[]="        access_attr = \"$ACCESS_ATTRIBUTE\"";
            $f[]="        access_attr_used_for_allow = yes";
        }
        $f[]="        edir_account_policy_check = no";
        $f[]="}\n";


    }






    @mkdir("/etc/freeradius/modules",0755,true);
    @file_put_contents("/etc/freeradius/modules/ldap", @implode("\n", $f));
    _out("Starting:/etc/freeradius/modules/ldap done...");


}

function ScanNasTypes(){
    $unix=new unix();
    if(!is_file("/usr/share/freeradius/dct2fr")){
        $tar=$unix->find_program("tar");
        @mkdir("/usr/share/freeradius",0755,true);
        shell_exec("$tar -xf /usr/share/artica-postfix/bin/install/freeradius/FreeradiusInShare.tar.gz -C /usr/share/freeradius/");

    }
    $MAIN=array();
    $files=$unix->DirFiles("/usr/share/freeradius");
    if($GLOBALS["VERBOSE"]){_out("Count..........:" .count($files)."");}

    $MAIN[null]="{other}";

    foreach ($files as $filename=>$none){

        if($GLOBALS["VERBOSE"]){_out("File...........: $filename");}
        if(!preg_match("#dictionary\.(.+)#", $filename,$re)){continue;}
        $type=trim($re[1]);
        $f=explode("\n",@file_get_contents("/usr/share/freeradius/$filename"));
        foreach ($f as $line){
            if(preg_match("#BEGIN-VENDOR\s+(.+)#", $line,$re)){
                $MAIN[$type]=trim($re[1]);
                break;
            }
        }

    }
    $MAIN["Standard"]="Standard";
    $MAIN["RADIUS Standard"]="RADIUS Standard";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("FreeRadiusNasTypes", serialize($MAIN));

}




function clients():bool{
    $ldap=new clladp();
    $unix=new unix();
    $f[]="client localhost {";
    $f[]="	ipaddr = 127.0.0.1";
    $f[]="	secret		= $ldap->ldap_password";
    $f[]=" 	shortname	= localhost";
    $f[]="	nastype     = other	# localhost isn't usually a NAS...";
    $f[]="#	login       = !root";
    $f[]="}";

    $FreeRadiusListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenInterface"));

    if($FreeRadiusListenInterface==null){
        $FreeRadiusListenInterface="eth0";
    }
    $ipaddr=$unix->InterfaceToIPv4($FreeRadiusListenInterface);
    $f[]="client local$FreeRadiusListenInterface {";
    $f[]="	ipaddr = $ipaddr";
    $f[]="	secret		= $ldap->ldap_password";
    $f[]=" 	shortname	= local$FreeRadiusListenInterface";
    $f[]="	nastype     = other	# localhost isn't usually a NAS...";
    $f[]="#	login       = !root";
    $f[]="}";





    $f[]="";
    $f[]="#client 192.168.0.0/24 {";
    $f[]="#	secret		= testing123-1";
    $f[]="#	shortname	= private-network-1";
    $f[]="#}";
    $f[]="#";
    $f[]="#client 192.168.0.0/16 {";
    $f[]="#	secret		= testing123-2";
    $f[]="#	shortname	= private-network-2";
    $f[]="#}";
    $f[]="";
    $f[]="";
    $f[]="#client 10.10.10.10 {";
    $f[]="#	# secret and password are mapped through the \"secrets\" file.";
    $f[]="#	secret      = testing123";
    $f[]="#	shortname   = liv1";
    $f[]="#       # the following three fields are optional, but may be used by";
    $f[]="#       # checkrad.pl for simultaneous usage checks";
    $f[]="#	nastype     = livingston";
    $f[]="#	login       = !root";
    $f[]="#	password    = someadminpas";
    $f[]="#}";
    $f[]="";

    $q=new lib_sqlite("/home/artica/SQLITE/radius.db");
    $sql="SELECT * FROM freeradius_clients WHERE `enabled`=1";
    $results = $q->QUERY_SQL($sql);
    foreach ($results as $index=>$ligne){
        if($ligne["nastype"]==null){$ligne["nastype"]="other";}
        if($ligne["nastype"]=="RADIUS Standard"){$ligne["nastype"]="other";}
        if($ligne["secret"]==null){$ligne["secret"]=$ldap->ldap_password;}
        $f[]="client {$ligne["shortname"]} {";
        $f[]="\tipv4addr    = {$ligne["ipaddr"]}";
        $f[]="\tsecret      = {$ligne["secret"]}";
        $f[]="\tshortname   = \"{$ligne["shortname"]}\"";
        $f[]="\tnastype     = {$ligne["nastype"]}";
        $f[]="}\n";
    }

    @mkdir("/etc/freeradius/modules",0755,true);
    @file_put_contents("/etc/freeradius/clients.conf", @implode("\n", $f));
    _out("Starting:/etc/freeradius/clients.conf done...");
    return true;
}


function confusers(){
    $sock=new sockets();
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
    $isLDAP=isLDAP();
    @file_put_contents("/etc/freeradius/users", "\n");
    return;
    $f[]="";
    if($isLDAP==1){	$f[]="DEFAULT Auth-Type = LDAP\n\t\tFall-Through = 0";}
    if($EnableKerbAuth==1){	$f[]="DEFAULT Auth-Type = ntlm_auth\n\t\tFall-Through = 1";}
    $sql="SELECT ID,params FROM freeradius_db WHERE connectiontype='ad' and `enabled`=1";
    $results = $q->QUERY_SQL($sql,"artica_backup");
    while ($ligne = mysqli_fetch_assoc($results)) {
        $array=unserialize(base64_decode($ligne["params"]));
        $ADGROUP=trim($array["ADGROUP"]);
        if($ADGROUP<>null){
            $ADGROUP=str_replace(",", ";", $ADGROUP);
            if(strpos(" $ADGROUP", ";")>0){
                $ADGROUPTR=explode(";", $ADGROUP);
                foreach ($ADGROUPTR as $num=>$gpname){
                if($gpname==null){continue;}$f[]="DEFAULT Ldap-Group == \"$gpname\"\n\tFall-Through = yes";}
            }else{
                $f[]="DEFAULT Ldap-Group == \"$ADGROUP\"\n\tFall-Through = yes";
            }
        }
    }

    $f[]="DEFAULT Auth-Type = Reject";
    $f[]="\tFall-Through = 1\n";
    @mkdir("/etc/freeradius/",0755,true);
    @file_put_contents("/etc/freeradius/users", @implode("\n", $f));
    _out("Starting:/etc/freeradius/users done...");
}

function isLDAP(){
    $sock=new sockets();
    $FreeRadiusEnableLocalLdap=$sock->GET_INFO("FreeRadiusEnableLocalLdap");
    if(!is_numeric($FreeRadiusEnableLocalLdap)){$FreeRadiusEnableLocalLdap=1;}
    if($FreeRadiusEnableLocalLdap==1){return true;}
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if($EnableActiveDirectoryFeature==1){return true;}
    $sql="SELECT COUNT(ID) as tcount FROM freeradius_db WHERE connectiontype='ldap' AND `enabled`=1";
    $q=new mysql();
    $ligne=mysqli_fetch_array(
        $q->QUERY_SQL($sql,"artica_backup")
    );
    if($ligne["tcount"]>0){return true;}

    $sql="SELECT COUNT(ID) as tcount FROM freeradius_db WHERE connectiontype='ad' AND `enabled`=1";
    $q=new mysql();
    $ligne=mysqli_fetch_array(
        $q->QUERY_SQL($sql,"artica_backup")
    );
    if($ligne["tcount"]>0){return true;}

    return false;
}




function CheckAndBackupConfig(){
    $unix=new unix();
    $freeradius=$unix->find_program("freeradius");
    $tar=$unix->find_program("tar");
    $cp=$unix->find_program("cp");
    $cd=$unix->find_program("cd");
    $rm=$unix->find_program("rm");
    _out("[CHECK]: Checking configuration using [freeradius -d /etc/freeradius -D /usr/share/freeradius -C -X]");
    exec("$freeradius -d /etc/freeradius -D /usr/share/freeradius -C -X 2>&1",$results);
    $STATUS=FALSE;
    foreach ($results as $line){
        if($GLOBALS["VERBOSE"]){_out("$line");}

        if(preg_match("#(Syntax error|Errors reading)#", $line)){
            _out("[ERROR]: Error f$line");}


        if(preg_match("#Configuration appears to be OK#i", $line)){
            _out("[SUCCESS]: Starting:Configuration OK");
            $STATUS=true;
            break;
        }


    }

    if(!$STATUS){
        _out("[ERROR]: checking Configuration FAILED");
        if(is_file("/root/radius.backup.tar.gz")){
            _out("[ALERT]:Revert to backup");
            //system("$tar xf /root/radius.backup.tar.gz -C /");
            return false;
        }
        _out("Starting:Return failed");
        return false;
    }

    if(is_file("/root/radius.backup.tar.gz")){@unlink("/root/radius.backup.tar.gz");}
    _out("Starting:Backup settings...");
    @mkdir("/root/freeradius/etc/freeradius",0755,true);
    @mkdir("/root/freeradius/usr/share/freeradius",0755,true);
    shell_exec("$cp -rfd /etc/freeradius/* /root/freeradius/etc/freeradius/");
    shell_exec("$cp -rfd /usr/share/freeradius/* /root/freeradius/usr/share/freeradius/");
    @chdir("/root/freeradius");
    system("cd /root/freeradius");
    system("$tar czf /root/radius.backup.tar.gz *");
    @chdir("/root");
    system("cd /root");
    system("$rm -rf /root/freeradius");
    return true;

}

function CheckCertificates(){
    _out("Verify Certificates....");
    $certDir="/etc/ssl/certs/freeradius/default";
    if(!is_dir($certDir)){
        @mkdir($certDir,0755,true);

    }

    if(!is_file("$certDir/privkey.pem")){
        _out("Duplicating certificate to $certDir/privkey.pem");
        @copy("/etc/nginx/certificates/default/privkey.pem","$certDir/privkey.pem");
    }else{
        _out("$certDir/privkey.pem [OK]");
    }
    if(!is_file("$certDir/cacert.pem")){
        _out("Duplicating certificate to $certDir/cacert.pem");
        @copy("/etc/nginx/certificates/default/cacert.pem","$certDir/cacert.pem");
    }else {
        _out("$certDir/cacert.pem [OK]");
    }
}

function start(){
    $unix=new unix();
    $sock=new sockets();
    $EnableFreeRadius=$sock->GET_INFO("EnableFreeRadius");
    $pid=freeradius_pid();

    if($unix->process_exists($pid)){
        $pidtime=$unix->PROCCESS_TIME_MIN($pid);
        _out("Starting:Already running pid $pid since {$pidtime}mn");
        build_restart_progress(95,"{starting_service} {success}");
        @file_put_contents("/var/run/freeradius/freeradius.pid", $pid);
        return true;
    }
    $freeradius=$unix->find_program("freeradius");
    if(!is_file($freeradius)){
        _out("Starting:failed, freeradius, no such binary...");
        build_restart_progress(95,"{starting_service} {failed}");
        return false;
    }


    $freeradius_version=freeradius_version();
    _out("Starting:daemon version $freeradius_version");
    CheckCertificates();
    build_syslog();
    $f[]="-d /etc/freeradius -D /usr/share/freeradius -n radiusd -f";
    $FreeRadiusDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusDebug"));
    if($FreeRadiusDebug==1){
        $f[]="-X";
    }

    $unix->folderSec("/var/run/freeradius",0755,"radiusd");
    if(!is_file("/var/run/freeradius/freeradius.pid")){@unlink("/var/run/freeradius/freeradius.pid");}
    $cmdline="$freeradius ".@implode(" ", $f);
    $nohup=$unix->find_program("nohup");
    $sh=$unix->sh_command("$nohup $cmdline >/dev/null 2>&1 &");
    $unix->go_exec($sh);
    sleep(1);

    build_restart_progress(60,"{starting_service}...");
    $c=60;
    for($i=1;$i<6;$i++){
        build_restart_progress($c++,"{starting_service}... waiting $i/5");
        _out("Starting:waiting $i/5");

        $pid=freeradius_pid();
        if($unix->process_exists($pid)){
            _out("Starting:Success PID $pid");
            return true;
        }
        sleep(1);
    }
    $pid=freeradius_pid();
    if($unix->process_exists($pid)){
        build_restart_progress(95,"{starting_service}...");
        _out("Starting:Success PID $pid");
        return true;
    }
    _out("Starting:Failed");
    _out("Starting:$cmdline");
    return false;
}

function freeradius_version():string{
    $unix=new unix();
    $freeradius=$unix->find_program("freeradius");
    exec("$freeradius -v 2>&1",$results);
    foreach ($results as $val){
        if(!preg_match("#Version ([0-9\.]+)#", $val,$re)){continue;}
        return $re[1];
    }
    return "";
}

function stop(){
    $unix=new unix();
    _out("Stopping : find binaries daemons");

    $pid=freeradius_pid();
    if(!$unix->process_exists($pid)){
        _out("Stopping : Already stopped");
        build_restart_progress(50,"{stopping_service}...");
        return true;
    }

    $pidtime=$unix->PROCCESS_TIME_MIN($pid);
    _out("Stopping : PID $pid since {$pidtime}mn");
    build_restart_progress(20,"{stopping_service}...");
    unix_system_kill($pid);
    $c=20;
    for($i=1;$i<11;$i++){
        build_restart_progress($c++,"{stopping_service}...");
        _out("Stopping : waiting PID: $pid $i/10");
        sleep(1);
        unix_system_kill($pid);
        $pid=freeradius_pid();
        if(!$unix->process_exists($pid)){
            _out("Stopping : Stopped");
            return true;
        }
    }

    $pid=freeradius_pid();
    if(!$unix->process_exists($pid)){
        _out("Stopping : Stopped");
        build_restart_progress(50,"{stopping_service}...{success}");
        return true;
    }
    _out("Stopping : Failed");
    return false;

}

function radtest():bool{
    $unix=new unix();
    $radtest=$unix->find_program("radtest");
    $ldap=new clladp();
    $FreeRadiusRadTest=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusRadTest")));
    $FreeRadiusListenInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenInterface"));
    if($FreeRadiusListenInterface==null){$FreeRadiusListenInterface="eth0";}
    $ListenIP=$unix->InterfaceToIPv4($FreeRadiusListenInterface);
    $FreeRadiusListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FreeRadiusListenPort"));
    if($FreeRadiusListenPort==0){$FreeRadiusListenPort=1812;}
    if(isset($FreeRadiusRadTest["secret"])){if($FreeRadiusRadTest["secret"]==null){unset($FreeRadiusRadTest["secret"]);}}

    build_radtest_progress(10,"Type......: {$FreeRadiusRadTest["authtype"]}");
    build_radtest_progress(10,"Radius....: {$ListenIP}:$FreeRadiusListenPort (udp)");
    build_radtest_progress(10,"Simulate..: {$FreeRadiusRadTest["nas-name"]}");

    if(isset($FreeRadiusRadTest["secret"])){
        $password_client=$unix->shellEscapeChars($FreeRadiusRadTest["secret"]);
    }else{
        $password_client=$unix->shellEscapeChars($ldap->ldap_password);
    }

    $password_user=$unix->shellEscapeChars($FreeRadiusRadTest["password"]);


    $f[]="$radtest";
    $f[]="-d /usr/share/freeradius";
    if($FreeRadiusRadTest["authtype"]<>null) {
        $f[] = "-t {$FreeRadiusRadTest["authtype"]}";
    }
    $f[]="-x";
    $f[]="-4";
    $f[]=$unix->shellEscapeChars($FreeRadiusRadTest["username"]);
    $f[]=$password_user;
    $f[]=$ListenIP;
    $f[]=0;
    $f[]=$password_client;
    $f[]=0;
    if($FreeRadiusRadTest["nas-name"]<>null){
        $f[]=$FreeRadiusRadTest["nas-name"];
    }
    $title=null;
    $results=array();
    build_radtest_progress(50,"{query_service}");
    $cmdline=@implode(" ", $f);
    $cmdlineText=str_replace($password_client, "[password]", $cmdline);
    $cmdlineText=str_replace($password_user, "[password-client]", $cmdlineText);
    if($GLOBALS["VERBOSE"]){_out("******\n$cmdline\n********");}
    $results[]=$cmdlineText;
    exec($cmdline." 2>&1",$results);
    $RESULTSA=false;
    foreach($results as $line){
        if(preg_match("#Cleartext-Password#", $line)){$line="	Cleartext-Password = \"[password]\"";}
        if(preg_match("#User-Password#", $line)){$line="	User-Password = \"[password]\"";}
        if(preg_match("#Access-Accept#", $line)){$RESULTSA=true;}
        if(preg_match("#Received Access-Reject#", $line)){$RESULTSA=false;$title="{reject}";}
        echo $line."\n";

    }

    if($RESULTSA){
        build_radtest_progress(100,"{success}");
    }else{
        build_radtest_progress(110,"{failed} $title");
    }
return true;
}


function attrs_access_reject(){

    $filename="/etc/freeradius/attrs.access_reject";
    $f[]="#";
    $f[]="#	Configuration file for the rlm_attr_filter module.";
    $f[]="#	Please see rlm_attr_filter(5) manpage for more information.";
    $f[]="#";
    $f[]="#	\$Id$";
    $f[]="#";
    $f[]="#	This configuration file is used to remove almost all of the attributes";
    $f[]="#	From an Access-Reject message.  The RFC's say that an Access-Reject";
    $f[]="#	packet can contain only a few attributes.  We enforce that here.";
    $f[]="#";
    $f[]="DEFAULT";
    $f[]="EAP-Message =*,";
    $f[]="State =*,";
    $f[]="Message-Authenticator =*,";
    $f[]="Reply-Message =*,";
    $f[]="Proxy-State =*";
    $f[]="";
    @file_put_contents($filename, @implode("\n", $f));
    _out("Starting:$filename done");
}