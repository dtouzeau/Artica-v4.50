<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.kerb.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__)."/framework/class.settings.inc");

$GLOBALS["CHECKS"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["JUST_PING"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["WRITEPROGRESS"]=false;
$GLOBALS["WATCHDOG"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--checks#",implode(" ",$argv))){$GLOBALS["CHECKS"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv))){$GLOBALS["WATCHDOG"]=true;}
$GLOBALS["EXEC_PID_FILE"]="/etc/artica-postfix/".basename(__FILE__).".pid";

$unix=new unix();
$sock=new sockets();

$UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
$LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
$HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
if($LockActiveDirectoryToKerberos==1){
    if($argv[1]=='--ntpdate'){echo "Sync Time...\n";sync_time(true);exit;}
    die();
}
if($UseNativeKerberosAuth==1){
    if($argv[1]=='--ntpdate'){echo "Sync Time...\n";sync_time(true);exit;}
    die();
}

if(isset($argv[1])){
	$DisableWinbindd=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableWinbindd");
	if(!is_numeric($DisableWinbindd)){$DisableWinbindd=0;}
	if($DisableWinbindd==1){progress_logs(100,"{join_activedirectory_domain}","You have defined that the Winbindd daemon is disabled, this feature cannot be used");
	    echo "Exiting due to winbindd daemon disabled...\n";
	    exit();
	}
}

if($argv[1]=='--fstab'){winbindisacl();exit();}
if($argv[1]=="--klist"){ping_klist();exit();}
if($argv[1]=='--winbinddpriv'){winbind_priv_perform(true);exit();}
if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Executing with `{$argv[1]}` command...", basename(__FILE__));}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--ping"){$GLOBALS["JUST_PING"]=true;ping_kdc();exit();}
if($argv[1]=="--samba-proxy"){SAMBA_PROXY();exit();}
if($argv[1]=='--winbindfix'){winbindfix();exit();}
if($argv[1]=='--winbindacls'){winbindd_set_acls_mainpart();exit();}
if($argv[1]=='--winbinddmonit'){winbindd_monit();exit();}
if($argv[1]=='--join'){JOIN_ACTIVEDIRECTORY();exit();}
if($argv[1]=='--samba-ver'){SAMBA_VERSION_DEBUG();exit();}
if($argv[1]=='--refresh-ticket'){refresh_ticket();exit();}
if($argv[1]=='--disconnect'){disconnect();exit;}
if($argv[1]=='--ntpdate'){echo "Sync Time...\n";sync_time(true);exit;}
if($argv[1]=='--build-progress'){$GLOBALS["WRITEPROGRESS"]=true;build_progress();exit;}
if($argv[1]=='--users'){GetUsersNumber();exit;}
if($argv[1]=='--krb5conf'){krb5conf();exit;}
if($argv[1]=='--mskt'){run_msktutils();exit;}
if($argv[1]=='--keytab'){run_keytabmissing();exit;}
if($argv[1]=='--quickconnect'){quickConnect();exit;}


unset($argv[0]);
echo "Unable to understand your command...\n";
progress_logs(100,"{join_activedirectory_domain}","Unable to understand ".@implode(" ", $argv)."");


function build_progress_quickConnect($pourc,$text){
	$filename=PROGRESS_DIR."/quickconnect.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0755);
}

function quickConnect(){
	
	
	build_progress_quickConnect(10, "krb5.conf");
	krb5conf();
	build_progress_quickConnect(20, "krb5.conf");
	ping_klist();
	build_progress_quickConnect(30, "{APP_WINBINDD}");
	SAMBA_PROXY();
	build_progress_quickConnect(40, "{join_activedirectory_domain}");
	JOIN_ACTIVEDIRECTORY();
	build_progress_quickConnect(50, "{join_activedirectory_domain}");
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	
	if($WindowsActiveDirectoryKerberos==1){
		build_progress_quickConnect(100, "{success}");
		return;
	}
	winbind_priv(false,52);
	build_progress_quickConnect(60, "{join_activedirectory_domain}");
	winbindd_monit();
	build_progress_quickConnect(70, "{join_activedirectory_domain}");
	
	
	if(!is_file("/etc/init.d/winbind")){
		build_progress_quickConnect(80, "{join_activedirectory_domain}");

	}
	
	build_progress_quickConnect(90, "{join_activedirectory_domain}");
	system("/etc/init.d/winbind restart --force");
	build_progress_quickConnect(100, "{success}");
}


function refresh_ticket($nopid=false){
	include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
	$unix=new unix();
	$sock=new sockets();
	$users=new usersMenus();
	$t=time();
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeExec=intval($unix->PROCCESS_TIME_MIN($pid));
		writelogs("Process $pid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
		if($timeExec>5){
			$kill=$unix->find_program("kill");
			squid_admin_mysql(2, "killing old pid $pid (already exists since {$timeExec}Mn)",__FUNCTION__,__FILE__,__LINE__);
			unix_system_kill_force($pid);
		}else{
			return;
		}
	}
	
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
			
	if($EnableKerbAuth==0){squid_admin_mysql(2, "EnableKerbAuth is disabled, aborting",__FUNCTION__,__FILE__,__LINE__);return;}
	if(!$users->WINBINDD_INSTALLED){squid_admin_mysql(2, "Samba is not installed, aborting",__FUNCTION__,__FILE__,__LINE__);return;}
	
	$net=$unix->find_program("net");
	$msktutil=$unix->find_program("msktutil");
	$cmdline="$net rpc changetrustpw -d 3 2>&1";
	exec($cmdline,$results);
	if(!is_file($msktutil)){
		$results[]="msktutil no such binary...";
		$took=$unix->distanceOfTimeInWords($t,time(),true);
		squid_admin_mysql(2, "Update kerberos done took:$took\n".@implode("\n", $results),__FUNCTION__,__FILE__,__LINE__);
		return;
		
	}
	
	$myNetBiosName=$unix->hostname_simple();
	$msktutil_version=msktutil_version();
	if($msktutil==3){
		$cmd="$msktutil --update --verbose --computer-name $myNetBiosName";
	}
	if($msktutil==4){
		$cmd="$msktutil --auto-update --verbose --computer-name $myNetBiosName";
	}
	$results[]=$cmd;
	exec($cmd." 2>&1",$results);
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "Update kerberos done took:$took\n".@implode("\n", $results),
        __FUNCTION__,__FILE__,__LINE__);
}

function run_keytabmissing(){
	
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	
	progress_logs(10,"{apply_settings} Check kerb5","Check kerb5..in line ".__LINE__);
	if(!krb5conf(12)){
		progress_logs(110,"{apply_settings} Check kerb5 {failed}","Check kerb5..in line ".__LINE__);
		return;
	}
	progress_logs(15,"{apply_settings} Check mskt","Check msktutils in line ".__LINE__);
	if(!run_msktutils()){
		progress_logs(110,"{apply_settings} Check mskt {failed}","Check mskt..in line ".__LINE__);
		return;
	}
	if( is_file($squidbin)){ 
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
		system("/usr/sbin/artica-phpfpm-service -reload-proxy");}
}


function build_kerberos($progress=0){
	$unix=new unix();
	$sock=new sockets();
	$function=__FUNCTION__;
	
	$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeExec=intval($unix->PROCCESS_TIME_MIN($pid));
		writelogs("Process $pid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
		if($timeExec>5){
			$kill=$unix->find_program("kill");
			progress_logs($progress,"{kerberaus_authentication}","killing old pid $pid (already exists since {$timeExec}Mn)");
			unix_system_kill_force($pid);
		}else{
			return;
		}
	}
	$time=$unix->file_time_min($timefile);
	
	if($time<2){progress_logs($progress,"{kerberaus_authentication}","2mn minimal to run this script currently ({$time}Mn)");}
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);
	writelogs("Running PID $mypid",__FUNCTION__,__FILE__,__LINE__);
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));

	
	sync_time($progress);
	krb5conf($progress);
	progress_logs($progress,"{kerberaus_authentication}","run_msktutils() -> run in line ".__LINE__);
	run_msktutils($progress);
	
}

function sync_time_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"ntpd.progress");
}

function sync_time($aspid=false){
	if(isset($GLOBALS[__FUNCTION__])){
	    echo "Function already running in memory\n";
	    return false;
	}
	$unix=new unix();
	$NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
	if($NtpdateAD==0){
        $unix->ToSyslog("Synchronize with Active Directory cancelled, feature not enabled",false,"ntpdate");
        sync_time_progress(110,"NtpdateAD is disabled, aborting...");
        echo "NtpdateAD is disabled, aborting...\n";
	    return true;
	}


	$function=__FUNCTION__;
	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".". __FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$timeExec=$unix->PROCCESS_TIME_MIN($pid);
			writelogs("Process $pid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
			if($timeExec>5){
				squid_admin_mysql(2, "killing old pid $pid (already exists since {$timeExec}Mn)",null,__FILE__,__LINE__);
				unix_system_kill_force($pid);
			}else{
			    if($GLOBALS["VERBOSE"]){echo "Return False;\n";}
                sync_time_progress(110,"Already process $pid/".getmypid()." Exists since {$timeExec}mn");
			    $unix->ToSyslog("Already process $pid Exists since {$timeExec}mn",false,"ntpdate");
				return false;
			}
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
    if(!isset($array["WINDOWS_DNS_SUFFIX"])){$array["WINDOWS_DNS_SUFFIX"]="";}
    if(!isset($array["ADNETIPADDR"])){$array["ADNETIPADDR"]="";}
    if(!isset($array["WINDOWS_SERVER_NETBIOSNAME"])){$array["WINDOWS_SERVER_NETBIOSNAME"]="";}
    $hostname="";

    if(!is_null($array["WINDOWS_SERVER_NETBIOSNAME"])){
	    $hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".
            strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
    }
	$ipaddr=trim($array["ADNETIPADDR"]);	
	$ntpdate=$unix->find_program("ntpdate");
	$hwclock=$unix->find_program("hwclock");
	
	
	if(!is_file($ntpdate)){
        sync_time_progress(110,"ntpdate no such binary");
	    progress_logs(20,"{sync_time_ad}","$function, ntpdate no such binary Line:".__LINE__."");
	    return false;
	}
    sync_time_progress(50,"{synchronizing}");
	if(!is_file("/etc/rsyslog.d/ntpdate.conf")){
        system("/usr/sbin/artica-phpfpm-service -install-ntp");
    }

    if(strlen($hostname)<5){
        if($ipaddr==null) {
            $unix->ToSyslog("Synchronizing unable to obtain address of the Active directory", false, "ntpdate");
            return false;
        }
    }

    $unix->ToSyslog("Synchronizing clock with host:[$hostname] and IP:[$ipaddr] Active Directory server L.".__LINE__,false,"ntpdate");
	progress_logs(20,"{sync_time_ad}","$function, sync the time with the Active Directory $hostname [$ipaddr]...");

	if($ipaddr<>null){$cmd="$ntpdate -s -4 -v -u $ipaddr";}else{$cmd="$ntpdate -s -4 -v -u $hostname";}
	if($GLOBALS["VERBOSE"]){progress_logs(20,"{sync_time_ad}","$cmd line:".__LINE__."");}

	if($GLOBALS["VERBOSE"]){echo $cmd."\n";}

    sync_time_progress(80,"{synchronizing}");
	exec($cmd." 2>&1",$results);
	foreach ($results as $a){
		$unix->ToSyslog("Active Directory synchronization: $a",false,"ntpdate");
		progress_logs(20,"{sync_time_ad}","$function, $a Line:".__LINE__."");
	}
	
	if(is_file($hwclock)){
        sync_time_progress(90,"{synchronizing}");
		progress_logs(20,"{sync_time_ad}","$function, sync the Hardware time with $hwclock");
        $unix->ToSyslog("Synchronize the hardware clock.",false,"ntpdate");
		shell_exec("$hwclock --systohc");
	}

	$GLOBALS[__FUNCTION__]=true;
    sync_time_progress(100,"{success}");
	return true;
}




function krb5conf($progress=0){
	$unix=new unix();
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	$function=__FUNCTION__;
	if(!checkParams()){progress_logs(10,"{kerberaus_authentication}","$function, misconfiguration failed");return;}
	$msktutil=check_msktutil();
	if(!is_file($msktutil)){return;}
	@chmod($msktutil,0755);
	$uname=posix_uname();
	$mydomain=$uname["domainname"];
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	$enctype=null;
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));

	if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
	if($array["WINDOWS_SERVER_TYPE"]==null){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}	
	
	progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory type `{$array["WINDOWS_SERVER_TYPE"]}`");
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	$ipaddr=trim($array["ADNETIPADDR"]);
	
	$workgroup=$array["ADNETBIOSDOMAIN"];
	if($WindowsActiveDirectoryKerberos==1){$array["WINDOWS_SERVER_TYPE"]="WIN_2008AES";}


	progress_logs($progress,"{kerberaus_authentication}","$function, Native Kerberos method `$WindowsActiveDirectoryKerberos`");
	progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory hostname `$hostname`");
	progress_logs($progress,"{kerberaus_authentication}","$function, my domain: \"$mydomain\"");
	progress_logs($progress,"{kerberaus_authentication}","$function, my hostname: \"$myFullHostname\"");
	progress_logs($progress,"{kerberaus_authentication}","$function, my netbiosname: \"$myNetBiosName\"");
	progress_logs($progress,"{kerberaus_authentication}","$function, my workgroup: \"$workgroup\"");		
	
	
	
	
	if($array["WINDOWS_SERVER_TYPE"]=="WIN_2003"){
		progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory type Windows 2003, adding default_tgs_enctypes");
		$t[]="# For Windows 2003:";
		$t[]=" default_tgs_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" default_tkt_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" permitted_enctypes = rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]="";
		
	}

	if($array["WINDOWS_SERVER_TYPE"]=="WIN_2008AES"){
		progress_logs($progress,"{kerberaus_authentication}","$function, Active Directory type Windows 2008, adding default_tgs_enctypes");
		$t[]="; for Windows 2008 with AES";
		$t[]=" default_tgs_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" default_tkt_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]=" permitted_enctypes = aes256-cts-hmac-sha1-96 rc4-hmac des-cbc-crc des-cbc-md5";
		$t[]="";
		$enctype=" --enctypes 28";
		
	}
	
	$dns_lookup_realm="yes";
	$dns_lookup_kdc="yes";
	$default_realm=$domainUp;
	$realms=$domainUp;
	$default_domain=$domainUp;
	
	

	//allow_weak_crypto = true ?? -> 
	$hostname_up=strtoupper($hostname);
	$f[]="[logging]";
	$f[]="\tdefault = FILE:/var/log/krb5libs.log";
	$f[]="\tkdc = FILE:/var/log/krb5kdc.log";
	$f[]="\tadmin_server = FILE:/var/log/kadmind.log";
	$f[]="";
	$f[]="[libdefaults]";
	$f[]="\tdefault_keytab_name = /etc/squid3/krb5.keytab";
	$f[]="\tdefault_realm = $default_realm";
	$f[]="\tdns_lookup_realm = $dns_lookup_realm";
	$f[]="\tdns_lookup_kdc = $dns_lookup_kdc";
	$f[]="\tallow_weak_crypto = true";
	$f[]="\tticket_lifetime = 24h";
	$f[]="\tforwardable = true";
	$f[]="\tproxiable = true";
	$f[]="\tfcc-mit-ticketflags = true";
	$f[]="\tccache_type = 4";
	
	
	$conf[]="\tdefault_ccache_name = FILE:/etc/kerberos/tickets/krb5cc_%{euid}";
	
	$f[]="";
	progress_logs($progress,"{kerberaus_authentication}","$function, ". count($t)." lines for default_tgs_enctypes");
	if( count($t)>0){
		$f[]=@implode("\n", $t);
	}
	
	
	$IPClass=new IP();
	
	
	$f[]="[realms]";
	$f[]="\t$realms = {";
	$f[]="\t\tkdc = $hostname:88";
	if($IPClass->isValid($ipaddr)){
		//$f[]="\t\tkdc=$ipaddr:88";
	}
	
	if(count($array["Controllers"])>0){
        foreach ($array["Controllers"] as $md5=>$array2){
			$kdc_hostname=$array2["hostname"];
			$kdc_ipaddr=$array2["ipaddr"];
			$UseIPaddr=$array2["UseIPaddr"];
			if($UseIPaddr==1){
				$f[]="\t\tkdc = $kdc_ipaddr:88";
			}else{
				$f[]="\t\tkdc = $kdc_hostname:88";
			}
		}
	}
	
	$f[]="\t\tadmin_server = $hostname:749";
	if($default_domain<>null){$f[]="\t\tdefault_domain = $domaindow";}
	$f[]="\t}";
	$f[]="";
	$f[]="[domain_realm]";
	$f[]="\t.$domaindow = $domainUp";
	$f[]="\t$domaindow = $domainUp";
	
	$f[]="";
	$f[]="[appdefaults]";
	$f[]="\tpam = {";
	$f[]="\t\tdebug = false";
	$f[]="\t\tticket_lifetime = 36000";
	$f[]="\t\trenew_lifetime = 36000";
	$f[]="\t\tforwardable = true";
	$f[]="\tkrb4_convert = false";
	$f[]="\t}";
	$f[]="";
		
	
	
	
	$conf[]="";
	@mkdir("/etc/kerberos/tickets",0755,true);
	@file_put_contents("/etc/krb.conf", @implode("\n", $f));
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/krb.conf done");
	@file_put_contents("/etc/krb5.conf", @implode("\n", $f));
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/krb5.conf done");	
	unset($f);
	$f[]="lhs=.ns";
	$f[]="rhs=.$mydomain";
	$f[]="classes=IN,HS";
	@file_put_contents("/etc/hesiod.conf", @implode("\n", $f));
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/hesiod.conf done");


	unset($f);
	$f[]="[libdefaults]";
	$f[]="\t\tdebug = true";
	$f[]="[kdcdefaults]";
	//$f[]="\tv4_mode = nopreauth";	
	$f[]="\tkdc_ports = 88,750";	
	//$f[]="\tkdc_tcp_ports = 88";	
	$f[]="[realms]";	
	$f[]="\t$domainUp = {";	
	$f[]="\t\tdatabase_name = /etc/krb5kdc/principal";
	$f[]="\t\tacl_file = /etc/kadm.acl";	
	$f[]="\t\tdict_file = /usr/share/dict/words";	
	$f[]="\t\tadmin_keytab = FILE:/etc/krb5.keytab";
	$f[]="\t\tkey_stash_file = /etc/krb5kdc/.k5.$domainUp";
	$f[]="\t\tmaster_key_type = des3-hmac-sha1";
	$f[]="\t\tsupported_enctypes = des3-hmac-sha1:normal des-cbc-crc:normal des:normal des:v4 des:norealm des:onlyrealm des:afs3";	
	$f[]="\t\tdefault_principal_flags = +preauth";
	$f[]="\t}";
	$f[]="";
	if(!is_dir("/usr/share/krb5-kdc")){@mkdir("/usr/share/krb5-kdc",644,true);}
	@file_put_contents("/usr/share/krb5-kdc/kdc.conf", @implode("\n", $f));
	@file_put_contents("/etc/kdc.conf", @implode("\n", $f));
	progress_logs($progress,"{kerberaus_authentication}","$function, /usr/share/krb5-kdc/kdc.conf done");
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/kdc.conf done Line:".__LINE__."");
	
	unset($f);

	$config="*/admin *\n";
	@file_put_contents("/etc/kadm.acl"," ");
	@file_put_contents("/usr/share/krb5-kdc/kadm.acl"," ");
	@file_put_contents("/etc/krb5kdc/kadm5.acl"," ");
	progress_logs($progress,"{kerberaus_authentication}","$function, /etc/kadm.acl done");	
	$progress=$progress+1;
	
	progress_logs($progress,"{kerberaus_authentication}","Running Linit() from line:".__LINE__);
	if(!RunKinit("{$array["WINDOWS_SERVER_ADMIN"]}@$domainUp",$array["WINDOWS_SERVER_PASS"],$progress)){
		return false;
	}
	progress_logs($progress,"{kerberaus_authentication}",__FUNCTION__."() done from line:".__LINE__);
	return true;
}

function check_msktutil(){
	$unix=new unix();
	$msktutil=$unix->find_program("msktutil");
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$function=__FUNCTION__;
	if(is_file($msktutil)){return $msktutil;}
	progress_logs(20,"{join_activedirectory_domain}","Kerberos, msktutil no such binary");
	if(is_file("/home/artica/mskutils.tar.gz.old")){
		progress_logs(20,"{join_activedirectory_domain}","$function, msktutil /home/artica/mskutils.tar.gz.old found, install it");
		shell_exec("$tar -xhf /home/artica/mskutils.tar.gz.old -C /");
	}

	$msktutil=$unix->find_program("msktutil");	
	if(is_file($msktutil)){return $msktutil;}	
	shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_MSKTUTIL >/dev/null 2>&1 &");
	progress_logs(20,"{join_activedirectory_domain}","$function, msktutil no such binary");
	
}

function msktutil_version(){
	$unix=new unix();
	$msktutil=$unix->find_program("msktutil");	
	$t=exec("$msktutil --version 2>&1");
	if(preg_match("#msktutil version\s+([0-9\.]+)#", $t,$re)){
		$tr=explode(".", $re[1]);
		return intval($tr[1]);
	}
}
function getWindowsNetBios():string{
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    if(isset($array["WINDOWS_SERVER_NETBIOSNAME"])){
        return $array["WINDOWS_SERVER_NETBIOSNAME"];
    }

    if (isset($array["fullhosname"])) {
        $fname=$array["fullhosname"];
        if(strlen($fname) < 2){
            return "";
        }
        if(strpos($fname,".")>0){
            $tb=explode(".",$fname);
            return $tb[0];
        }
    }
    return "";
}
function getWindowsFQDN():string{
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

    if(isset($array["WINDOWS_SERVER_NETBIOSNAME"])){
        return strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
    }

    if (isset($array["fullhosname"])) {
        return strtolower(trim($array["fullhosname"]));
    }
    return "";
}

function resolve_kdc(){
	$function=__FUNCTION__;
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	$ipaddr=trim($array["ADNETIPADDR"]);
    $hostname=getWindowsFQDN();

    if($ipaddr==null){
		progress_logs(20,"{join_activedirectory_domain}","$function, KDC $hostname no ip address set, aborting checking function");
		return;
	}

	$newip=gethostbyname($hostname);
	progress_logs(20,"{join_activedirectory_domain}","$function, KDC $hostname [$ipaddr] resolved to: $newip");
}



function run_msktutils(){
	$unix=new unix();
	$sock=new sockets();
	
	if(is_file("/usr/sbin/msktutil")){@chmod("/usr/sbin/msktutil",0755);}
	$msktutil=$unix->find_program("msktutil");
	$function=__FUNCTION__;
	$klist=$unix->find_program("klist");
	
	if(!is_file($msktutil)){
		if(is_file("/home/artica/mskutils.tar.gz.old")){
			progress_logs(20,"{join_activedirectory_domain}","$function, uncompress /home/artica/mskutils.tar.gz.old");
			shell_exec("tar xf /home/artica/mskutils.tar.gz.old -C /");
		}
	}
	
	$msktutil=$unix->find_program("msktutil");
	if(!is_file($msktutil)){	
		progress_logs(20,"{join_activedirectory_domain}","$function, msktutil not installed, you should use it..");
		return;
	}
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	$ActiveDirectorySquidHTTPHostname=$sock->GET_INFO("ActiveDirectorySquidHTTPHostname");
	$ipaddr=trim($array["ADNETIPADDR"]);
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	if(!isset($array["WINDOWS_SERVER_TYPE"])){$array["WINDOWS_SERVER_TYPE"]="WIN_2003";}
	progress_logs(20,"{join_activedirectory_domain}","$function, computers branch `{$array["COMPUTER_BRANCH"]}`");
	progress_logs(20,"{join_activedirectory_domain}","$function, my full hostname `$myFullHostname`");
	progress_logs(20,"{join_activedirectory_domain}","$function, my netbios name `$myNetBiosName`");
	progress_logs(20,"{join_activedirectory_domain}","$function, Active Directory hostname `$hostname` ($ipaddr)");
	$kdestroy=$unix->find_program("kdestroy");
	
	$domain_controller=$hostname;
	
	
	$enctypes=null;
	if( $array["WINDOWS_SERVER_TYPE"]=="WIN_2008AES"){
		$enctypes=" --enctypes 28";
	}
	$msktutil_version=msktutil_version();
	progress_logs(20,"{join_activedirectory_domain}","$function, msktutil version 0.$msktutil_version");
	
// msktutil -c -b "CN=COMPUTERS" 
//-s HTTP/squid.demo.local 
//-k /etc/squid3/krb5.keytab 
//--computer-name squid-http --upn HTTP/squid.demo.local --server dc2008demo.demo.local --verbose --enctypes 28	
	$myNetBiosName=strtolower($myNetBiosName);
	$myFullHostname=strtolower($myFullHostname);
	if($ActiveDirectorySquidHTTPHostname<>null){$myFullHostname=strtolower($ActiveDirectorySquidHTTPHostname);}
	if($WindowsActiveDirectoryKerberos==0){$myNetBiosName="$myNetBiosName-k";}
	
	$net=$unix->find_program("net");
	echo "###################################################################\n";
	echo "######################### ALTERNATIVE KEYTAB ######################\n";
	echo "###################################################################\n";
	$f=array();
	$f[]="#!/bin/sh";
	$f[]="PATH=/bin:/usr/bin:/sbin:/usr/sbin";
	$f[]="export KRB5_KTNAME=FILE:/etc/squid3/krb5.keytab";
	$f[]="KRB5RCACHETYPE=none export KRB5RCACHETYPE";
	$f[]="KRB5_KTNAME=/etc/squid3/krb5.keytab export KRB5_KTNAME";
	$f[]="#$net ads keytab CREATE";
	$f[]="#$net ads keytab ADD HTTP";
	@file_put_contents("/tmp/netads.sh", @implode("\n", $f));
	$f=array();
	
	
	echo "build_service_environ().... Line ".__LINE__."\n";
	build_service_environ();

	
	
	echo "Running /tmp/netads.sh.... Line ".__LINE__."\n";
	@chmod("/tmp/netads.sh", 0755);
	system("/tmp/netads.sh");
	@unlink("/tmp/netads.sh");
	
	echo "ping_klist().... Line ".__LINE__."\n";
	ping_klist();
	
	$f[]="$msktutil -c -b \"{$array["COMPUTER_BRANCH"]}\"";
	$f[]="-s HTTP/$myFullHostname";
	$f[]="-k /etc/squid3/krb5.keytab";
	$f[]="--computer-name $myNetBiosName";
	$f[]="--upn HTTP/$myFullHostname";
	$f[]="--server $domain_controller";
	$f[]="--verbose";
	$f[]="$enctypes";
	
	
	$IpClass=new IP();
	echo "$domain_controller as IP address $ipaddr\n";
	if($IpClass->isValid($ipaddr)){
		echo "$domain_controller as IP address $ipaddr -> /etc/hosts\n";
		$unix->create_EtcHosts($domain_controller, $ipaddr);
	}
	
	$MSKTUTIL_SUCCESS=true;
	
	echo "WindowsActiveDirectoryKerberos == $WindowsActiveDirectoryKerberos\n";
	
	
	$cmdline=@implode(" ", $f);
	progress_logs(20,"{join_activedirectory_domain}","$function,`$cmdline`");
	exec("$cmdline 2>&1",$results);
    foreach ($results as $a){
		if(trim($a)==null){continue;}
		progress_logs(20,"{join_activedirectory_domain}","$function, $a Line:".__LINE__."");
		if(preg_match("#Is your kerberos ticket expired#i", $a)){
			progress_logs(20,"{join_activedirectory_domain} kerberos failed (ticket expired)","$function,`$cmdline`");
			echo "$cmdline\n###################################################################\n";
			echo "######################### MKTUTILS FAILED #########################\n";
			echo "###################################################################\n";
			echo "######################## CONTINUE ANYWAY ##########################\n";
			echo "###################################################################\n";
			
		}
	
	}
	
	if($WindowsActiveDirectoryKerberos==1){
		if(!is_file("/etc/squid3/krb5.keytab")){
			echo "/etc/squid3/krb5.keytab - No Such file!!\n$cmdline\n";
			echo "###################################################################\n";
			echo "######################### MKTUTILS FAILED #########################\n";
			echo "###################################################################\n";
			return false;
		}
	}

	if(is_file("/etc/squid3/krb5.keytab")){
		echo "SUCCESS\n";
		echo "###################################################################\n";
		
		exec("$klist -k /etc/squid3/krb5.keytab -t 2>&1",$klist_results);
		@chmod("/etc/squid3/krb5.keytab", 0755);
		@chown("/etc/squid3/krb5.keytab","squid");
		@chgrp("/etc/squid3/krb5.keytab","squid");
		
		
		$SUCCESS=false;
        foreach ($klist_results as $a){
			if(preg_match("#$myNetBiosName#", $a)){
				echo "$a [SUCCESS]\n";
				$SUCCESS=true;
			}
			
		}
	
	}
	echo "buildong cron for updates...\n";
	$cmdline="$msktutil --auto-update --verbose --computer-name $myNetBiosName --server $domain_controller";
	$CRON[]="#!/bin/sh";
	$CRON[]="exec $cmdline";
	$CRON[]="";
	@file_put_contents("/etc/cron.daily/msktutil", @implode("\n", $CRON));
	chmod("/etc/cron.daily/msktutil",0755);
	chown("/etc/cron.daily/msktutil","root");
	
	if($SUCCESS){
		if($msktutil_version==4){
			exec("$cmdline 2>&1",$results);
            foreach ($results as $a){if(trim($a)==null){continue;}
			progress_logs(20,"{join_activedirectory_domain}","$function, $a Line:".__LINE__."");}
		}
	}
	
return true;
	
	
	
}

function build_progress_join($pourc,$text){
	$filename=PROGRESS_DIR."/winbindd.join.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0755);
}

function progress_logs($percent,$title,$log=null,$line=0){
	build_progress_join($percent,$title);
	$date=date("H:i:s");
	if(!isset($GLOBALS["LAST_PROGRESS"])){$GLOBALS["LAST_PROGRESS"]=0;}
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		$function="MAIN";
		$line=0;
		if(isset($trace[1]["file"])){
			$filename=basename($trace[1]["file"]);
		}
		if(isset($trace[1]["function"])){
			$function="{$trace[1]["function"]}()";
		}
		if(isset($trace[1]["line"])){
			
			if($line==0){
				$function=$function." line {$trace[1]["line"]}";
			}
		}
	}
	$log="{$percent}%  $date : $log $function";
	echo "$log\n";
	if(!$GLOBALS["WRITEPROGRESS"]){return;}
	$cachefile=PROGRESS_DIR."/squid.ad.progress";
	
	$array["POURC"]=$percent;
	$array["TEXT"]=$title;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function build_progress(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timeExec=intval($unix->PROCCESS_TIME_MIN($pid));
		if($GLOBALS["OUTPUT"]){progress_logs(20,"{join_activedirectory_domain}","Process $pid already exists since {$timeExec}Mn");}
		writelogs("Process $pid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
		if($timeExec<5){return;}
		$kill=$unix->find_program("kill");
		progress_logs(20,"{join_activedirectory_domain}","build_progress, killing old pid $pid (already exists since {$timeExec}Mn)");
		unix_system_kill_force($pid);
	}
		
	
	if(!build(5)){return;}
	progress_logs(66,"NssSwitch","Building nsswitch.... on line ".__LINE__);
	exec("/usr/sbin/artica-phpfpm-service -nsswitch",$results2);
    foreach ($results2 as $line){
		progress_logs(67,"NssSwitch",$line);
	}
	
	$php=$unix->LOCATE_PHP5_BIN();
	if(is_file($unix->LOCATE_SQUID_BIN())){
		progress_logs(68,"{reconfiguring_proxy_service}","...");
		$results2=array();
		exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --force --progress-activedirectory=68 2>&1",$results2);
        foreach ($results2 as $line){
			progress_logs(68,"Reconfiguring {APP_SQUID}",$line);
		}
		$results2=array();
		$squidbin=$unix->LOCATE_SQUID_BIN();
		if(is_file($squidbin)){
			progress_logs(69,"{reloading_proxy_service}","...");
            system("/usr/sbin/artica-phpfpm-service -reload-proxy");
		}
		
		
	}
	$nohup=$unix->find_program("nohup");
	progress_logs(70,"{please_wait_restarting_artica_status}","...");
	shell_exec("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");
	progress_logs(100,"{completed}","...");
}


function build_service_environ(){
	
	$INITD_PATH="/etc/init.d/adsenv";
	$daemonbinLog="Active Directory environment";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        adsenv";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="    KRB5RCACHETYPE=none export KRB5RCACHETYPE";
	$f[]="    KRB5_KTNAME=/etc/squid3/krb5.keytab export KRB5_KTNAME";
	$f[]="    mkdir -p /var/cache/samba/lck || true";
	$f[]="    chmod 0755 /var/cache/samba/lck || true";
	$f[]="    mkdir -p /var/cache/samba/msg || true";
	$f[]="    chmod 0700 /var/cache/samba/msg || true";	
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    ;;";
	$f[]=" reload)";
	
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	
	
	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
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


function build($nopid=false){
	if(isset($GLOBALS["BUILD_EXECUTED"])){
		progress_logs(20,"{continue}","Already executed");
		return;
	}
	$GLOBALS["BUILD_EXECUTED"]=true;
	$unix=new unix();
	$sock=new sockets();
	$function=__FUNCTION__;
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));

	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}

	if($EnableKerbAuth==0){
		progress_logs(110,"{authentication_via_activedirectory_is_disabled}","{authentication_via_activedirectory_is_disabled}");
		return;
	}
	
	if(!$nopid){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".time";
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$timeExec=intval($unix->PROCCESS_TIME_MIN($pid));
			if($GLOBALS["OUTPUT"]){progress_logs(20,"{join_activedirectory_domain}","Process $pid already exists since {$timeExec}Mn");}
			writelogs("Process $pid already exists since {$timeExec}Mn",__FUNCTION__,__FILE__,__LINE__);
			if($timeExec>5){
				$kill=$unix->find_program("kill");
				progress_logs(20,"{join_activedirectory_domain}","killing old pid $pid (already exists since {$timeExec}Mn)");
				unix_system_kill_force($pid);
			}else{
				return;
			}
		}
		
		
		$time=$unix->file_time_min($timefile);
		if($time<2){
			if($GLOBALS["OUTPUT"]){progress_logs(20,"{join_activedirectory_domain}","2mn minimal to run this script currently ({$time}Mn)");}
			writelogs("2mn minimal to run this script currently ({$time}Mn)",__FUNCTION__,__FILE__,__LINE__);
			return;
		}
	
	}
	
	
	pinglic(true);
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);
	progress_logs(20,"{join_activedirectory_domain} Running PID $mypid","Running PID $mypid",__LINE__);
	writelogs("Running PID $mypid",__FUNCTION__,__FILE__,__LINE__);
	
	$wbinfo=$unix->find_program("wbinfo");
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$ntpdate=$unix->find_program("ntpdate");
	$php=$unix->LOCATE_PHP5_BIN();

	
	$php5=$unix->LOCATE_PHP5_BIN();
	if($WindowsActiveDirectoryKerberos==0){
		if(!is_file($wbinfo)){
            system("/usr/sbin/artica-phpfpm-service -sources-list");
			shell_exec("$nohup /usr/share/artica-postfix/bin/setup-ubuntu --check-samba >/dev/null 2>&1 &");
			$wbinfo=$unix->find_program("wbinfo");
			
		}
		if(!is_file($wbinfo)){
				progress_logs(110,"{finish}","Auth Winbindd, samba is not installed");
				return;
		}
		
		system("/etc/init.d/winbind start");
	}
	
	if(!checkParams()){
		progress_logs(110,"{finish}","Auth Winbindd, misconfiguration failed");
		return;
	}
	$unix=new unix();
	$chmod=$unix->find_program("chmod");
	$msktutil=check_msktutil();
	$kdb5_util=$unix->find_program("kdb5_util");
	$kadmin_bin=$unix->find_program("kadmin");
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	if(!is_file($msktutil)){return;}
	@mkdir("/var/log/samba",0755,true);
	@mkdir("/var/run/samba",0755,true);
	$uname=posix_uname();
	$mydomain=$uname["domainname"];
	$myFullHostname=$unix->hostname_g();
	$myNetBiosName=$unix->hostname_simple();
	$enctype=null;
	$sock=new sockets();
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domaindow=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	
	$ipaddr=trim($array["ADNETIPADDR"]);

    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");
	
	
	if($ipaddr<>null){
		$ipaddrZ=explode(".",$ipaddr);
		foreach ($ipaddrZ as $num=>$a){
			$ipaddrZ[$num]=intval($a);
		}
		$ipaddr=@implode(".", $ipaddrZ);
	}
	
	progress_logs(9,"{apply_settings} Synchronize time","Synchronize time"." in line ".__LINE__);
	sync_time();

	progress_logs(10,"{apply_settings} Check kerb5","Check kerb5..in line ".__LINE__);
	if(!krb5conf(12)){
		progress_logs(110,"{apply_settings} Check kerb5 {failed}","Check kerb5..in line ".__LINE__);
		return;
	}
	progress_logs(15,"{apply_settings} Check mskt","Check msktutils in line ".__LINE__);
	if(!run_msktutils()){
		progress_logs(110,"{apply_settings} Check mskt {failed}","Check mskt..in line ".__LINE__);
		return;
	}
	
	progress_logs(16,"{remove_service} OpenLDAP server","Check msktutils in line ".__LINE__);
	system("/usr/sbin/artica-phpfpm-service -uninstall-ldap");
	
	progress_logs(19,"{apply_settings} [kadmin_bin]",$kadmin_bin);
	progress_logs(19,"{apply_settings} [netbin]", $netbin);
	
	if($WindowsActiveDirectoryKerberos==1){return true;}
	progress_logs(15,"{apply_settings} netbin","netbin -> $netbin in line ".__LINE__);
	if(is_file($netbin)){
		try {
			progress_logs(15,"{apply_settings} netbin","netbin -> SAMBA_PROXY()  in line ".__LINE__);
			SAMBA_PROXY();
		} catch (Exception $e) {
			progress_logs(15,"{failed}","Exception Error: Message: " .$e->getMessage());
		}
	}
		
	if(is_file("$netbin")){
		progress_logs(20,"{join_activedirectory_domain}","netbin -> JOIN_ACTIVEDIRECTORY() ");
		JOIN_ACTIVEDIRECTORY(); // 29%
	}
	progress_logs(51,"{restarting_winbind} 1","winbind_priv();");
	winbind_priv(false,52);
	progress_logs(60,"{restarting_winbind} 2","winbind_priv();");
	winbindd_monit();
	progress_logs(65,"{restarting_winbind} 3","winbind_priv();");
	
	
	if(!is_file("/etc/init.d/winbind")){
		progress_logs(65,"{creating_service}","winbind_priv();");

	}
	
	progress_logs(65,"{restarting_winbind}","winbind_priv();");
	system("/etc/init.d/winbind restart --force");
	
	return true;


}


function winbindd_version(){
	$unix=new unix();
	$winbindd=$unix->find_program("winbindd");
	if(!is_file($winbindd)){return;}
	exec("$winbindd -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#", @implode("", $results),$re)){
		return $re[1];
	}
}



function LdapToSlash($branch){
	$tt=explode(",",trim($branch));

    foreach ($tt as $field){
		if(preg_match("#^(.*?)=(.+)#", trim($field),$re)){
			$TT2[]=trim($re[2]);
		}
	}
	
	krsort($TT2);
	return @implode("/", $TT2);
}

function JOIN_ACTIVEDIRECTORY(){
	$function=__FUNCTION__;
	if(isset($GLOBALS["JOIN_ACTIVEDIRECTORY"])){
		progress_logs(40,"{join_activedirectory_domain}"," [$function::".__LINE__."], Already executed");
		return;
	}
	$GLOBALS["JOIN_ACTIVEDIRECTORY"]=true;
	
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	include_once("/usr/share/artica-postfix/ressources/class.samba.privileges.inc");
    winbindd_privileges();
	
	
	$unix=new unix();	
	$user=new usersMenus();
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$function=__FUNCTION__;
	if(!is_file($netbin)){progress_logs(29,"{join_activedirectory_domain}","net, no such binary");return;}
	if(!$user->WINBINDD_INSTALLED){progress_logs(29,"{join_activedirectory_domain}"," Winbindd, no such software");return;}
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	if($WindowsActiveDirectoryKerberos==1){
		progress_logs(59,"{join_activedirectory_domain}","Use only Kerberos method...");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KerbAuthWatchEv", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryEmergency", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryEmergencyReboot", 0);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryEmergencyNone", 0);
		return true;
		
	}
	progress_logs(29,"{remove} /var/lib/samba/*.tdb");
	shell_exec("$rm /var/lib/samba/*.tdb");
	
	
	$winbindd_version=winbindd_version();
	progress_logs(29,"{join_activedirectory_domain}"," Version $winbindd_version");
	if(preg_match("#^([0-9]+)\.([0-9]+)#", $winbindd_version,$re)){
		progress_logs(29,"{join_activedirectory_domain}"," Major:{$re[1]}, minor:{$re[2]}");
		$MAJOR=$re[1];
		$MINOR=$re[2];
	}
	
	
	$RECONFIGURE_PROXY=false;
	$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
	$KDC_SERVER=$NetADSINFOS["KDC server"];

	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$GLOBALS["ADMIN_PASS_FOR_LOGS"]=$adminpassword;
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];
	$workgroup=$array["ADNETBIOSDOMAIN"];
	$ipaddr=trim($array["ADNETIPADDR"]);
    if(!isset($array["COMPUTER_BRANCH"])){$array["COMPUTER_BRANCH"]="CN=Computers";}
	$COMPUTER_BRANCH=$array["COMPUTER_BRANCH"];
	$COMPUTER_SLASH=LdapToSlash($COMPUTER_BRANCH);
	$NETADS_BRANCH=null;

	if($COMPUTER_BRANCH<>null){
		if($COMPUTER_BRANCH<>"CN=Computers"){
			if($COMPUTER_SLASH<>null){
				$NETADS_BRANCH=" createcomputer=\"$COMPUTER_SLASH\"";
			}
		}
	}
	
	
	$ActiveDirectoryEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryEmergency"));
	$myNetbiosname=$unix->hostname_simple();
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Trying to relink this server with Active Directory $ad_server.$domain_lower server", basename(__FILE__));}
	$A2=array();
	$JOINEDRES=false;
	@unlink("/etc/squid3/NET_ADS_INFOS");
	
	$buffer="$adminname@$domain_lower\nWorkgroup: $workgroup\nAD: $ad_server ($ipaddr)";
	
	$LOG_LEVEL=1;
	if($GLOBALS["WATCHDOG"]){$LOG_LEVEL=0;}
	
	squid_admin_mysql($LOG_LEVEL, "NTLM: Trying to relink this server with Active Directory $ad_server.$domain_lower server", $buffer,__FILE__,__LINE__);
	
	
	if(!is_file("/etc/artica-postfix/PyAuthenNTLM2_launched")){
		shell_exec("$nohup /usr/share/artica-postfix/bin/artica-make APP_PYAUTHENNTLM >/dev/null 2>&1 &");
		@file_put_contents("/etc/artica-postfix/PyAuthenNTLM2_launched", time());
	}
	
	progress_logs(29,"{join_activedirectory_domain}"," Computer Branch.: `$COMPUTER_BRANCH -> $COMPUTER_SLASH -> $NETADS_BRANCH`");
	progress_logs(29,"{join_activedirectory_domain}"," User used.......: `$adminname`");
	progress_logs(29,"{join_activedirectory_domain}"," Workgroup.......: `$workgroup`");
	progress_logs(29,"{join_activedirectory_domain}"," Active Directory: `$ad_server` ($ipaddr)");	
	progress_logs(29,"{join_activedirectory_domain}"," [$adminname 0]: join as net ads.. (without IP addr and without domain)");
    progress_logs(29,"{join_activedirectory_domain}"," [$adminname 0]: $netbin ads join -U $adminname%*******{$NETADS_BRANCH}");
    $cmd="$netbin ads join -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
	exec($cmd,$A2);
	if($GLOBALS["VERBOSE"]){progress_logs(30,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd");}
	foreach ($A2 as $line){
		if(preg_match("#Joined#i", $line)) {
            progress_logs(31, "{join_activedirectory_domain}", " $function, [$adminname]: join for $workgroup (without IP addr and without domain) success");
            $JOINEDRES = true;
            $NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
			$KDC_SERVER=$NetADSINFOS["KDC server"];
			if($KDC_SERVER==null){progress_logs(33,"{join_activedirectory_domain}"," $function, [$adminname]: unable to join the domain $domain_lower (KDC server is null)");}
			break;
		}
		if(preg_match("#Unable to find a suitable server for domain#i", $line)){
			squid_admin_mysql(0,"Active directory Unable to find a suitable server for domain [action: None]",@implode("\n", $A2),__FILE__,__LINE__);
			progress_logs(33,"{join_activedirectory_domain}"," [$adminname 0]: **** FATAL ****");
			progress_logs(34,"{join_activedirectory_domain}"," [$adminname 0]: $line");
			progress_logs(35,"{join_activedirectory_domain}"," [$adminname 0]: ***************");
			continue;
		}
		
		progress_logs(36,"{join_activedirectory_domain}"," [$adminname 0]: $line");
			
	}
	
	$ipcmdline=null;
	
	if(!$JOINEDRES){
		progress_logs(37,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: Try to connect in ads mode with $ad_server.$domain_lower");
		if($ipaddr<>null){
			progress_logs(37,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: Try to connect in ads mode with ip address $ipaddr too");
			$ipcmdline=" -I $ipaddr ";
		}
		$cmd="$netbin ads join -S $ad_server.$domain_lower{$ipcmdline} -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
		$cmdOutput=$cmd;
		progress_logs(38,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmdOutput");
		$results=array();
		exec("$cmd",$results);
		foreach ($results as $index=>$line){
			if(preg_match("#Joined#", $line)){
				squid_admin_mysql(2,"Active directory Joined [action: None]",@implode("\n", $results),__FILE__,__LINE__);
				progress_logs(39,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: join for $workgroup in ads mode with $ad_server.$domain_lower success");
				$cmd="$netbin rpc join -S $ad_server.$domain_lower{$ipcmdline} -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
				$cmdOutput=$cmd;
				progress_logs(40,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmdOutput");
				exec("$cmd",$results2);
                foreach ($results2 as $line){
					if(SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line,32)){
						progress_logs(110,"{join_activedirectory_domain} {failed}",null);
						exit;
					}
				}
				$JOINEDRES=true;
				break;
			}
			
			SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line,40);
		}
	}	
	
	
	
	

if(!$JOINEDRES){
		progress_logs(42,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: Kdc server ads = `$KDC_SERVER`");
		if($KDC_SERVER==null){
			$cmd="$netbin ads join -W $ad_server.$domain_lower -S $ad_server -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
			if($GLOBALS["VERBOSE"]){progress_logs(42,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd");}
			exec("$cmd",$results);
			foreach ($results as $index=>$line){SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line);}	
			$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();
			$KDC_SERVER=$NetADSINFOS["KDC server"];
		}
		
		if($KDC_SERVER==null){progress_logs(43,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: unable to join the domain $domain_lower (KDC server is null)");}
	
		
	
		
	progress_logs(44,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: setauthuser..");
	$cmd="$netbin setauthuser -U $adminname%$adminpassword 2>&1";	
	if($GLOBALS["VERBOSE"]){progress_logs(44,"{join_activedirectory_domain}"," $function, $cmd");}
	$results=array();
	exec("$cmd",$results);
	foreach ($results as $index=>$line){progress_logs(44,"{join_activedirectory_domain}","$line");}
		
	if($ipaddr==null){
		$JOINEDRES=false;
		progress_logs(45,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 0]: join for $workgroup (without IP addr)");	
		if($GLOBALS["VERBOSE"]){progress_logs(46,"{join_activedirectory_domain}"," $function, $cmd");}
		$cmd="$netbin join -U $adminname%$adminpassword{$NETADS_BRANCH} $workgroup 2>&1";
		exec($cmd,$A1);
        foreach ($A1 as $line){
			if(preg_match("#Joined#", $line)){
				squid_admin_mysql(2,"Active directory Joined [action: None]",@implode("\n", $A1),__FILE__,__LINE__);
				progress_logs(46,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: join for $workgroup (without IP addr) success");
				$JOINEDRES=true;
				break;
			}
			SAMBA_OUTPUT_ERRORS("[$function::".__LINE__."]",$line,46);
		}
		
		if(!$JOINEDRES){
			progress_logs(47,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 0]: join as netrpc.. (without IP addr)");	
			$cmd="$netbin rpc join -U $adminname%$adminpassword{$NETADS_BRANCH} $workgroup 2>&1";
			exec($cmd,$A2);
			if($GLOBALS["VERBOSE"]){progress_logs(47,"{join_activedirectory_domain}"," $function, $cmd");}
            foreach ($A2 as $line){
				if(preg_match("#Joined#", $line)){
					squid_admin_mysql(2,"Active directory Joined [action: None]",@implode("\n", $A2),__FILE__,__LINE__);
					progress_logs(47,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname]: join for $workgroup (without IP addr) success");
					$JOINEDRES=true;
					break;
				}
				progress_logs(47,"{join_activedirectory_domain}",$line);
			}
		}
		
	}
	
	if($ipaddr<>null){
		progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 1]: ads '$netbin ads join -I $ipaddr -U $adminname%**** $workgroup'");
		//$cmd="$netbin ads join -S $ad_server.$domain_lower -I $ipaddr -U $adminname%$adminpassword 2>&1";
		$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword{$NETADS_BRANCH} $workgroup 2>&1";
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}"," Samba, $cmd");}
		exec($cmd,$BIGRES2);
        foreach ($BIGRES2 as $line){
			if(preg_match("#Failed to join#i", $line)){
				progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 1]: ads join failed ($line), using pure IP");
				progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], [$adminname 1]: '$netbin ads join -I $ipaddr -U $adminname%*** $workgroup'");
				$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword{$NETADS_BRANCH} $workgroup 2>&1";
				if($GLOBALS["VERBOSE"]){progress_logs(48,"{join_activedirectory_domain}"," $function, $cmd line:". __LINE__."");}
				$BIGRESS=array();
				exec($cmd,$BIGRESS);
				
				if(!is_array($BIGRESS)){$BIGRESS=array();}
				if(count($BIGRESS)==0){
					$cmd="$netbin ads join -I $ipaddr -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
					progress_logs(48,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd");
					exec($cmd,$BIGRESS);
				}
				
				if(count($BIGRESS)>0){
                    foreach ($BIGRESS as $line){
						progress_logs(48,"{join_activedirectory_domain}",$line);
						if(preg_match("#(Failed to|Unable to|Error in)#i", $line)){$BIGRESS=array();}
					}
				}
				 
				
				
				
				if($GLOBALS["VERBOSE"]){progress_logs(49,"{join_activedirectory_domain}"," [$function::".__LINE__."], ".count($BIGRESS)." lines line:".__LINE__."");}
				if(!is_array($BIGRESS)){$BIGRESS=array();}
				if(count($BIGRESS)==0){
					$cmd="$netbin rpc join -I $ipaddr -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
					if($GLOBALS["VERBOSE"]){progress_logs(49,"{join_activedirectory_domain}"," [$function::".__LINE__."], $cmd Line:".__LINE__."");}
					exec($cmd,$BIGRESS);
				}
				
				if($GLOBALS["VERBOSE"]){progress_logs(49,"{join_activedirectory_domain}"," [$function::".__LINE__."], ".count($BIGRESS)." lines line:".__LINE__."");}
				if(count($BIGRESS)>0){
                    foreach ($BIGRESS as $line){
						progress_logs(49,"{join_activedirectory_domain}",$line);
						if(preg_match("#(Failed to|Unable to|Error in)#i", $line)){$BIGRESS=array();}
					}
				}			
				
				
				if(!is_array($BIGRESS)){$BIGRESS=array();}
				if(count($BIGRESS)==0){
					$cmd="$netbin rpc join -S $ad_server -n $myNetbiosname -U $adminname%$adminpassword{$NETADS_BRANCH} 2>&1";
					progress_logs(50,"{join_activedirectory_domain}"," $cmd Line:".__LINE__."");
					exec($cmd,$BIGRESS);
				}			
				
				reset($BIGRESS);
                foreach ($BIGRESS as $line){
					progress_logs(49,"{join_activedirectory_domain}",$line);
				}
				
				break;
			}
			progress_logs(49,"{join_activedirectory_domain}","[$adminname 1] $line");
			
		}
		
		
		/*progress_logs(20,"{join_activedirectory_domain}"," Samba, [$adminname]: join with  IP Adrr:$ipaddr..");	
		$cmd="$netbin join -U $adminname%$adminpassword -I $ipaddr";
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}"," Samba, $cmd");}
		shell_exec($cmd);*/
	
	}
}
	if($KDC_SERVER==null){$NetADSINFOS=$unix->SAMBA_GetNetAdsInfos();$KDC_SERVER=$NetADSINFOS["KDC server"];}
	if($KDC_SERVER==null){
		squid_admin_mysql(0, "NTLM: unable to join the domain $domain_lower", $buffer,__FILE__,__LINE__);
		progress_logs(50,"{join_activedirectory_domain}"," Samba, [$adminname]: unable to join the domain $domain_lower");
	}else{
		squid_admin_mysql(2, "NTLM: Success to join the domain $domain_lower", "KDC = $KDC_SERVER",__FILE__,__LINE__);
		if($ActiveDirectoryEmergency==1){
			squid_admin_mysql(2, "NTLM: [Success] Active Directory emergency behavior was disabled", "KDC = $KDC_SERVER",__FILE__,__LINE__);
			$RECONFIGURE_PROXY=true;
		}
		
	}	

	progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: Kdc server ads : $KDC_SERVER Create keytap...");
	
	unset($results);
	$cmd="$netbin ads keytab create -P -U $adminname%$adminpassword 2>&1";
	if($GLOBALS["VERBOSE"]){progress_logs(50,"{join_activedirectory_domain}"," $function, $cmd");}
	exec("$cmd",$results);
	foreach ($results as $index=>$line){progress_logs(50,"{join_activedirectory_domain}"," $function, keytab: [$adminname]: $line");}

	$nohup=$unix->find_program("nohup");
	$smbcontrol=$unix->find_program("smbcontrol");
	
	progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: restarting Winbind");
	$results=array();
	exec("/etc/init.d/winbind restart 2>&1",$results);
	foreach ($results as $index=>$line){progress_logs(50,"{join_activedirectory_domain}","$line");}
	
	
	progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: Reload Artica-status");
	$results=array();
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	$restart_winbind=false;
	if(is_file($smbcontrol)){
		progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: Reloading winbindd");
		exec("$smbcontrol winbindd reload-config 2>&1",$results);
		exec("$smbcontrol winbindd online 2>&1",$results);
		foreach ($results as $index=>$line){
			progress_logs(50,"{join_activedirectory_domain}"," [$adminname]: $line");
			if(preg_match("#Cannot open the tdb#", $line)){$restart_winbind=true;break;}
		}
	}
	$results=array();
	
	
	if($restart_winbind){
		progress_logs(50,"{restarting_service}"," [$adminname] Winbindd");
		if(!is_file("/etc/init.d/winbind")){
			progress_logs(65,"{creating_service}","[$adminname] Winbindd");
			install_winbind_service();
		}
		system("/etc/init.d/winbind restart --force");
	}
	
	
	progress_logs(66,"{join_activedirectory_domain}","[$adminname]: checks nsswitch");
	exec("/usr/sbin/artica-phpfpm-service -nsswitch",$results);
	foreach ($results as $index=>$line){progress_logs(50,"{join_activedirectory_domain}","[$adminname]: $line");}
	$sock=new sockets();
	$sock->SET_INFO("KerbAuthWatchEv", 0);
	$sock->SET_INFO("ActiveDirectoryEmergency", 0);
	$sock->SET_INFO("ActiveDirectoryEmergencyReboot", 0);
	$sock->SET_INFO("ActiveDirectoryEmergencyNone", 0);
	if($RECONFIGURE_PROXY){
		squid_admin_mysql(2, "NTLM: Reconfigure the proxy service", "KDC = $KDC_SERVER",__FILE__,__LINE__);
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	build_progress_join(100,"{success}");

}

function SAMBA_VERSION(){
	
	$unix=new unix();
	$winbind=$unix->find_program("winbindd");
	exec("$winbind -V 2>&1",$results);
	if(preg_match("#Version\s+([0-9\.]+)#i", @implode("", $results),$re)){
		return $re[1];
	}
	
	
}

function SAMBA_VERSION_DEBUG(){
	$SAMBA_VERSION=SAMBA_VERSION();
	progress_logs(20,"{join_activedirectory_domain}","Samba Version (Winbind): $SAMBA_VERSION");
	if(preg_match("#^3\.6\.#", $SAMBA_VERSION)){
		echo"Samba 3.6 OK\n";
	}
}


function SAMBA_PROXY(){
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	
	if($WindowsActiveDirectoryKerberos==1){
		progress_logs(19,"{creating_service}"," Only kerberos method is set, aborting Winbind server");
		remove_service("/etc/init.d/winbind");
		remove_service("/etc/init.d/ntlm-monitor");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
		return true;
	}
	
	
	
	
	
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("{reconfigure} Samba for proxy commpliance", basename(__FILE__));}
	progress_logs(15,"SAMBA_SPECIFIC_PROXY() start... ");
	$IsAppliance=false;
	progress_logs(15,"users=new usersMenus(); ");
	$user=new settings_inc();
	$unix=new unix();
	$sock=new sockets();
	
	
	if(!$user->WINBINDD_INSTALLED){progress_logs(16,"{APP_SAMBA}"," Samba, no such software");return;}
	if($user->SQUID_APPLIANCE){$IsAppliance=true;}
	if($user->KASPERSKY_WEB_APPLIANCE){$IsAppliance=true;}
	$EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
	if(!is_numeric($EnableWebProxyStatsAppliance)){$EnableWebProxyStatsAppliance=0;}
	if($user->WEBSTATS_APPLIANCE){$EnableWebProxyStatsAppliance=1;}		
	if($EnableWebProxyStatsAppliance==1){$IsAppliance=true;}
	
	if(!$IsAppliance){progress_logs(16,"{APP_SAMBA}"," Samba,This is not a Proxy appliance, i leave untouched smb.conf");return;}
	progress_logs(16,"{APP_SAMBA}"," Samba, it is an appliance...");

	

	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	if(!isset($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
	if(!is_numeric($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}	
	
	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];
	$KerbAuthDisableGroupListing=$sock->GET_INFO("KerbAuthDisableGroupListing");
	$KerbAuthDisableNormalizeName=$sock->GET_INFO("KerbAuthDisableNormalizeName");
	$KerbAuthMapUntrustedDomain=$sock->GET_INFO("KerbAuthMapUntrustedDomain");
	$KerbAuthTrusted=$sock->GET_INFO("KerbAuthTrusted");
	if(!is_numeric($KerbAuthDisableGroupListing)){$KerbAuthDisableGroupListing=0;}
	if(!is_numeric($KerbAuthDisableNormalizeName)){$KerbAuthDisableNormalizeName=1;}
	if(!is_numeric($KerbAuthMapUntrustedDomain)){$KerbAuthMapUntrustedDomain=1;}
	if(!is_numeric($KerbAuthTrusted)){$KerbAuthTrusted=1;}
	
	
	
	$workgroup=$array["ADNETBIOSDOMAIN"];
	$realm=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$ipaddr=trim($array["ADNETIPADDR"]);
	progress_logs(16,"{APP_SAMBA}"," Samba, [$adminname]: Kdc server ads : $ad_server workgroup `$workgroup` ipaddr:$ipaddr");	
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	$password_server=$hostname;
	//if($ipaddr<>null){$password_server=$ipaddr;}
	if(strpos($password_server, ".")>0){$aa=explode(".", $password_server);$password_server=$aa[0];}
	$SAMBA_VERSION=SAMBA_VERSION();
	$ipaddr=trim($array["ADNETIPADDR"]);
	if($ipaddr<>null){$password_server=$ipaddr;}
	
	$AS36=false;
	if(preg_match("#^3\.6\.#", $SAMBA_VERSION)){$AS36=true;}
	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#", $SAMBA_VERSION,$re)){
		$MAJOR=intval($re[1]);
		$MINOR=intval($re[2]);
		$REV=intval($re[3]);
		progress_logs(17,"{APP_SAMBA}"," Samba, V$MAJOR $MINOR $REV");
		
	}
	
	
	$f[]="[global]";
	$smbkerb=new samba_kerb();
	$f[]=$smbkerb->buildPart();
	
	
	
	@file_put_contents("/etc/samba/smb.conf", @implode("\n", $f));
	progress_logs(18,"{APP_SAMBA}"," Samba, [$adminname]: SMB.CONF DONE, restarting services");
	$net=$unix->find_program("net");
	shell_exec("$net cache flush");
	shell_exec("$net cache stabilize");
	system("/usr/sbin/artica-phpfpm-service -nsswitch");
	$smbcontrol=$unix->find_program("smbcontrol");
	if(!is_file($smbcontrol)){
		progress_logs(19,"{APP_SAMBA}"," Samba, [$adminname]: Restarting Samba...");
		shell_exec("/etc/init.d/samba restart");
	}else{
		progress_logs(19,"{APP_SAMBA}"," Samba, [$adminname]: Reloading Samba...");
		shell_exec("$smbcontrol smbd reload-config");
	}
	
	progress_logs(19,"{APP_SAMBA}"," Samba, [$adminname]: Restarting Winbind...");
	
	if(!is_file("/etc/init.d/winbind")){
		progress_logs(19,"{creating_service}"," Samba, [$adminname]: Restarting Winbind...");
		install_winbind_service();
	}
	
	shell_exec("/etc/init.d/winbind stop");
	shell_exec("/etc/init.d/winbind start");
	
	shell_exec($unix->LOCATE_PHP5_BIN()." /usr/share/artica-postfix/exec.squid.ad.import.php --by=". basename(__FILE__)." &");
	
}

function ping_klist($progress=0){
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){return;}
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}

    if(!isset($array["WINDOWS_SERVER_ADMIN"])){
        return;
    }

	RunKinit($array["WINDOWS_SERVER_ADMIN"],$array["WINDOWS_SERVER_PASS"],$progress);
	
}

function RunKinit($username,$password,$progress=1){
	$unix=new unix();
	$kinit=$unix->find_program("kinit");
	$klist=$unix->find_program("klist");
	$echo=$unix->find_program("echo");
	$function=__FUNCTION__;
	if(!is_file($kinit)){echo2("Unable to stat kinit");return;}
	resolve_kdc();
	sync_time();
	exec("$klist 2>&1",$res);
	$line=@implode("",$res);


	if(strpos($line,"No credentials cache found")>0){
		unset($res);
		echo2($line." -> initialize..");
		$password=$unix->shellEscapeChars($password);
		$cmd="$echo $password|$kinit {$username} 2>&1";
		progress_logs($progress,"{kerberaus_authentication}","$cmd");
		progress_logs($progress,"{kerberaus_authentication}","$function, kinit `$username`");
		exec("$echo $password|$kinit {$username} 2>&1",$res);
		foreach ($res as $num=>$a){
			if(preg_match("#Password for#",$a,$re)){unset($res[$num]);}
			progress_logs($progress,"{kerberaus_authentication}","$a");
			
			if(preg_match("#Clock skew too great while#", $a)){
				echo "           * * * * * * * * * * * * * * * * * * *\n";
				echo "           * *                               * *\n";
				echo "           * * Please check the system clock ! *\n";
				echo "           * *   Time differ with the AD     * *\n";
				echo "           * *                               * *\n";
				echo "           * * * * * * * * * * * * * * * * * * *\n";
				progress_logs($progress,"{kerberaus_authentication}",$line." -> Failed..");
				$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth",0);
				return;
			}
			
		}	
		$line=@implode("",$res);	
		if(strlen(trim($line))>0){
			progress_logs($progress,"{kerberaus_authentication}",$line." -> Failed..");
			return;
		}
		unset($res);
		progress_logs($progress,"{kerberaus_authentication}",$klist);
		exec("$klist 2>&1",$res);
	}
	
	

    foreach ($res as $num=>$a){
		progress_logs($progress,"{kerberaus_authentication}","$a");
		if(preg_match("#Default principal:(.+)#",$a,$re)){
				progress_logs($progress,"{kerberaus_authentication}","$a SUCCESS");
				break;
			}
		}	
	

		progress_logs($progress,"{kerberaus_authentication}","DONE LINE: ".__LINE__);	
  return true;
}

function echo2($content){
	progress_logs(10,"{kerberaus_authentication}","$content");
	
}

function ping_kdc(){
	
	$sock=new sockets();
	$unix=new unix();
	$users=new settings_inc();
	$chmod=$unix->find_program("chmod");
	$filetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	if(!is_numeric("$EnableKerbAuth")){$EnableKerbAuth=0;}
	
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$ttime=$unix->PROCCESS_TIME_MIN($pid);
		progress_logs(20,"{join_activedirectory_domain}","[PING]: Already executed pid $pid since {$ttime}Mn");
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	if($EnableKerbAuth==0){progress_logs(20,"{ping_kdc}","[PING]: Kerberos, disabled");return;}
	if(!checkParams()){progress_logs(20,"{ping_kdc}","[PING]: Kerberos, misconfiguration failed");return;}
	$array["RESULTS"]=false;
	
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	$time=$unix->file_time_min($filetime);
	if(!$GLOBALS["FORCE"]){
		if($time<10){
			if(!$GLOBALS["VERBOSE"]){return;}
			progress_logs(20,"{ping_kdc}","$filetime ({$time}Mn)");
		}
	}
	$kinit=$unix->find_program("kinit");
	$echo=$unix->find_program("echo");
	$net=$unix->LOCATE_NET_BIN_PATH();
	$wbinfo=$unix->find_program("wbinfo");
	$chmod=$unix->find_program("chmod");
	$nohup=$unix->find_program("nohup");
	$domain=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$ad_server=strtolower($array["WINDOWS_SERVER_NETBIOSNAME"]);
	$kinitpassword=$array["WINDOWS_SERVER_PASS"];
	$kinitpassword=$unix->shellEscapeChars($kinitpassword);
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$clock_explain="The clock on you system (Linux/UNIX) is too far off from the correct time.\nYour machine needs to be within 5 minutes of the Kerberos servers in order to get any tickets.\nYou will need to run ntp, or a similar service to keep your clock within the five minute window";
	
	
	$cmd="$echo $kinitpassword|$kinit {$array["WINDOWS_SERVER_ADMIN"]}@$domain -V 2>&1";
	progress_logs(20,"{ping_kdc}","$cmd");
	exec("$cmd",$kinit_results);
    foreach ($kinit_results as $ligne){
		if(preg_match("#Clock skew too great while getting initial credentials#", $ligne)){
			if($GLOBALS["VERBOSE"]){progress_logs(20,"{ping_kdc}","Clock skew too great while");}
			$array["RESULTS"]=false;
			$array["INFO"]=$ligne;
			$unix->send_email_events("Active Directory connection clock issue", 
			"kinit program claim\n$ligne\n$clock_explain", "system");
		}
		if(preg_match("#Client not found in Kerberos database while getting initial credentials#", $ligne)){
			$array["RESULTS"]=false;
			$array["INFO"]=$ligne;
			$unix->send_email_events("Active Directory authentification issue", "kinit program claim\n$ligne\n", "system");}
		if(preg_match("#Authenticated to Kerberos#", $ligne)){
			$array["RESULTS"]=true;
			$array["INFO"]=$ligne;
			progress_logs(20,"{join_activedirectory_domain}","[PING]: Kerberos, Success");
		}
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{ping_kdc}","kinit: $ligne");}
	}


	$TestJoin=true;
	
	if($array["RESULTS"]==true){
		exec("$net ads testjoin 2>&1",$results);
		foreach ($results as $num=>$ligne){
			
			if(preg_match("#Unable to find#", $ligne)){
				$array["RESULTS"]=false;
				$array["INFO"]=$array["INFO"]."<div><i style='font-size:11px;color:#B3B3B3'>$ligne</i></div>";
				$TestJoin=false;
				continue;
			}
			if(preg_match("#is not valid:#", $ligne)){
				$array["RESULTS"]=false;
				$array["INFO"]=$array["INFO"]."<div><i style='font-size:11px;color:#B3B3B3'>$ligne</i></div>";
				$TestJoin=false;
				continue;
			}
		}
		
		if(preg_match("#OK#", $ligne)){
			$array["INFO"]=$array["INFO"]."<div><i style='font-size:11px;color:#B3B3B3'>$ligne</i></div>";
			$array["RESULTS"]=true;
		}
		
	}
	
	@unlink($filetime);
	@file_put_contents($filetime, time());
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/kinit.array", serialize($array));
	@chmod("/usr/share/artica-postfix/ressources/logs/kinit.array",0777);
	
	if($GLOBALS["JUST_PING"]){return;}
	
	if(!$TestJoin){
		shell_exec("$nohup $php5 ". __FILE__." --join >/dev/null 2>&1 &");
	}
	
	
	
	
	

	if($users->SQUID_INSTALLED){
		winbind_priv();


	}
	
	
}

function SAMBA_OUTPUT_ERRORS($prefix,$line,$prc=0){
	if($prc==0){$prc=20;}
	
	if(preg_match("#Clock skew too great#i", $line)){
		echo "\n";
		echo "******************************\n";
		echo "** ERROR Clock skew too great\n";
		echo "** Please check time between this machine and the AD\n";
		echo "******************************\n";
		echo "\n";
		return true;
		
	}
	
	if(preg_match("#NT_STATUS_INVALID_LOGON_HOURS#", $line)){
		echo "\n";
		echo "******************************\n";
		echo"ERROR NT_STATUS_INVALID_LOGON_HOURS\n";
		echo "** account not allowed to logon during\n";
		echo "** these logon hours\n";
		echo "******************************\n";
		echo "\n";
		return true;
	}
	
	if(preg_match("#NT_STATUS_LOGON_FAILURE#", $line)){
		echo "\n";
		echo "******************************\n";
		progress_logs($prc,"OUTPUT","** ERROR NT_STATUS_LOGON_FAILURE");
		progress_logs($prc,"OUTPUT","** inactive account with bad password specified"); 
		echo "******************************\n";
		echo "\n";
		return true;
	}	
	if(preg_match("#NT_STATUS_ACCOUNT_EXPIRED#", $line)){
		echo "\n";
		echo "******************************\n";
		progress_logs($prc,"OUTPUT","** ERROR NT_STATUS_LOGON_FAILURE");
		progress_logs($prc,"OUTPUT","** account expired"); 
		echo "******************************\n";
		echo "\n";
		return true;
	}	
	if(preg_match("#NT_STATUS_ACCESS_DENIED#", $line)){
		echo "\n";
		echo "******************************\n";
		echo "ERROR NT_STATUS_ACCESS_DENIED\n";
		echo "Wrong credentials (username or password)\n"; 
		echo "******************************\n";
		echo "\n";
		return true;
	}	
	
	
	$line=str_replace($GLOBALS["ADMIN_PASS_FOR_LOGS"],"****",$line);
	echo "{$prc}% ---> $line\n";
}


function winbind_priv($reloadservs=false,$progress=0){
	if(isset($GLOBALS["winbind_priv"])){
		progress_logs($progress,"WINBINDD {privileges}","Already executed");
		return;
	}
	$GLOBALS["winbind_priv"]=true;
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	progress_logs($progress,"WINBINDD {privileges}","winbindd_priv...");
	if(!winbind_priv_is_group()){
		xsyslog("winbindd_priv group did not exists...create it");
		$groupadd=$unix->find_program("groupadd");
		$cmd="$groupadd winbindd_priv >/dev/null 2>&1";
	}
	

	
	$unix=new unix();
	$gpass=$unix->find_program('gpass');
	$usermod=$unix->find_program("usermod");
	if(is_file($gpass)){
		progress_logs($progress,"{join_activedirectory_domain}","winbindd_priv group exists, add squid a member of winbindd_priv");
		$cmdline="$gpass -a squid winbindd_priv";
		if($GLOBALS["VERBOSE"]){progress_logs($progress,"WINBINDD {privileges}","$cmdline");}
		exec("$cmdline",$kinit_results);

        foreach ($kinit_results as $ligne){progress_logs(52,"WINBINDD {privileges}","winbindd_priv: $ligne");}
	}else{
		if(is_file($usermod)){
			$cmdline="$usermod -a -G winbindd_priv squid";
			if($GLOBALS["VERBOSE"]){progress_logs($progress,"WINBINDD {privileges}","$cmdline");}
			exec("$cmdline",$kinit_results);
            foreach ($kinit_results as $ligne){progress_logs($progress,"WINBINDD {privileges}","winbindd_priv: $ligne");}
		}

	}
	
	
	
	winbind_priv_perform(false,$progress);
	
	$pid_path=$unix->LOCATE_WINBINDD_PID();
	$pid=$unix->get_pid_from_file($pid_path);
	$res=array();
	progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind Daemon pid: $pid ($pid_path)...");
	if(!$unix->process_exists($pid)){
		progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind configuring winbind init...");
		exec("$php5 /usr/share/artica-postfix/exec.winbindd.php 2>&1",$res);

        foreach ($res as $ligne){progress_logs(54,"WINBINDD {privileges}","winbindd $ligne");}
		progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind Daemon stopped, start it...");
		start_winbind();
		
	}else{
		progress_logs($progress,"WINBINDD {privileges}","winbindd_priv checks Samba Winbind configuring winbind init...");
		shell_exec("$php5 /usr/share/artica-postfix/exec.winbindd.php 2>&1",$res);
        foreach ($res as $ligne){progress_logs(55,"WINBINDD {privileges}","winbindd $ligne");}
		progress_logs($progress,"WINBINDD {privileges}","winbindd already running pid $pid.. reload it");
		$res=array();
		exec("$php5 /usr/share/artica-postfix/exec.winbindd.php --restart 2>&1",$res);
        foreach ($res as $ligne){progress_logs(56,"WINBINDD {privileges}","winbindd $ligne");}
		
	}
	
	progress_logs($progress,"WINBINDD {privileges}","winbindd_monit()");
	winbindd_monit();
	
	
}
function winbind_priv_is_group():bool{
	$f=file("/etc/group");
	foreach ($f as $ligne){if(preg_match("#^winbindd_priv#", $ligne)){return true;}}
	return false;
}

function winbind_priv_perform($withpid=false,$progress=0):bool{
	if(isset($GLOBALS[__FUNCTION__."EXECUTED"])){return true;}
	$unix=new unix();
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	$CacheFile="/etc/artica-postfix/pids/winbind_priv_perform.time";
	if($EnableKerbAuth==0){return true;}
	if($unix->file_time_min($CacheFile)<2){echo "Need at least 2mn, aborting";return true;}
	$pids=$unix->PIDOF_PATTERN_ALL(basename(__FILE__));
	if(count($pids)>4){echo "No more 4 processes at the same time\n";return true;}
	
	@unlink($CacheFile);
	@file_put_contents($CacheFile, time());
		
	$possibleDirs[]="/var/run/samba/winbindd_privileged";
	$possibleDirs[]="/var/lib/samba/winbindd_privileged";

	$setfacl=$unix->find_program("setfacl");
	
	if($withpid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			xsyslog("(". __FUNCTION__.") Already executed PID:$pid");
			progress_logs($progress,"{kerberaus_authentication}","Already executed PID:$pid");
		}
		
	}
	
	winbindd_set_acls_is_xattr_var($progress);
	if(strlen($setfacl)>5){winbindd_set_acls_mainpart();}


    foreach ($possibleDirs as $Directory){
			if(is_dir($Directory)){
				if(strlen($setfacl)>5){shell_exec("$setfacl -m u:squid:rwx $Directory");}
			}
			
			if(file_exists("$Directory/pipe")){
				if(strlen($setfacl)>5){
					echo "$setfacl -m u:squid:rwx $Directory/pipe\n";
					shell_exec("$setfacl -m u:squid:rwx $Directory/pipe");}
				chgrp("$Directory/pipe", "winbindd_priv");
				
			}
			
						
		}
	if(!$withpid){
		$squidbin=$unix->find_program("squid");

		if(!is_file($squidbin)){$squidbin=$unix->find_program("squid3");}
		if(is_file($squidbin)){
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Reloading $squidbin",basename(__FILE__),false);}
			squid_admin_mysql(1, "Reconfiguring proxy service",null,__FILE__,__LINE__);
			progress_logs($progress,"{kerberaus_authentication}","{reconfigure} {APP_SQUID}...");
			$cmd="/etc/init.d/squid reload --script=".basename(__FILE__)." >/dev/null 2>&1 &";
			shell_exec($cmd);
		}
	}

	$GLOBALS[__FUNCTION__."EXECUTED"]=true;
    return true;
}

function stop_winbind(){
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Stopping winbindd", basename(__FILE__));}
	system("/etc/init.d/artica-postfix stop winbindd");
}
function start_winbind(){
	
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("Starting winbindd", basename(__FILE__));}
	
	if(!is_file("/etc/init.d/winbind")){
		progress_logs(20,"{creating_service}","winbindd");
		install_winbind_service();
	}
	
	exec("/etc/init.d/winbind start 2>&1",$res);

    foreach ($res as $ligne){
		progress_logs(20,"{join_activedirectory_domain}","winbindd $ligne");
	}
}

function winbindisacl(){
	if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}","winbindisacl() -> winbindd_set_acls_mainpart()");}
	winbindd_set_acls_mainpart();
}


function winbindfix(){
	winbind_priv(true);
	winbind_priv_perform();
	
}

function pinglic($aspid=false){
    return true;
}

function winbindd_set_acls_is_xattr(){
	$f=file("/proc/mounts");
	foreach ($f as $num=>$ligne){
	if(preg_match("#^(.*)\s+\/\s+(.*?)\s+.*?,acl.*?\s+([0-9]+)#",$ligne,$re)){
		progress_logs(20,"{join_activedirectory_domain}","winbindd_priv main partition is already mounted with extended acls");
		return true;
		}
	}
	
	return false;
}
function winbindd_set_acls_is_xattr_var($progress=0){
	if(isset($GLOBALS["winbindd_set_acls_is_xattr_var_exectued"])){return;}
	$GLOBALS["winbindd_set_acls_is_xattr_var_exectued"]=true;
	$unix=new unix();
	$mount=$unix->find_program("mount");	
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	$mustchange=false;
	$remountSlash=false;
	if($GLOBALS["VERBOSE"]){progress_logs($progress,"{kerberaus_authentication}","/etc/fstab -> ".count($f)." rows");}
	foreach ($f as $num=>$ligne){
		$options=array();
		$optionsR=array();
		
		if(preg_match("#^(.*?)\/var\s+(.+)\s+(.+?)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
			progress_logs($progress,"{kerberaus_authentication}","/var partition exists with options `{$re[3]}`...");
			$options=explode(",",$re[3]);
            foreach ($options as $b){
				if(is_numeric($b)){continue;}
				if(trim($b)==null){continue;}
				$optionsR[trim($b)]=true;
				
			}
			if(!isset($optionsR["acl"])){
				$mustchange=true;
				$options[]="acl";
				$options[]="user_xattr";
				$re[3]=@implode(",", $options);
				progress_logs($progress,"{kerberaus_authentication}","ACLS found {$re[1]} main partition `/var` was not extended attribute, fix it: `".@implode(",", $options)."`...");
				$f[$num]=trim($re[1])."\t/var\t".$re[2]."\t".$re[3]."\t".$re[4]."\t".$re[5];
			}
		}
		
		
		if(preg_match("#^(.*?)\/\s+(.+)\s+(.+?)\s+([0-9]+)\s+([0-9]+)#",$ligne,$re)){
			progress_logs($progress,"{kerberaus_authentication}","/ partition exists with options `{$re[3]}`...");
			$options=explode(",",$re[3]);
            foreach ($options as $b){
				if(is_numeric($b)){continue;}
				if(trim($b)==null){continue;}
				$optionsR[trim($b)]=true;
			
			}
			if(!isset($optionsR["acl"])){
				$mustchange=true;
				$options[]="acl";
				$options[]="user_xattr";
				$re[3]=@implode(",", $options);
				progress_logs($progress,"{kerberaus_authentication}","ACLS found {$re[1]} main partition `/` was not extended attribute, fix it: `".@implode(",", $options)."`...");
				$f[$num]=trim($re[1])."\t/\t".$re[2]."\t".$re[3]."\t".$re[4]."\t".$re[5];
				$remountSlash=true;
			}			
			
		}
	}
	
	if($mustchange){
		@file_put_contents("/etc/fstab", @implode("\n", $f)."\n");
		shell_exec("$mount -o remount /var");
		if($remountSlash){
			shell_exec("$mount -o remount /");
		}
	}
	
	progress_logs($progress,"{kerberaus_authentication}",__FUNCTION__." done line ".__LINE__);
	
	
}



function winbindd_set_acls_mainpart(){
	if(winbindd_set_acls_is_xattr()){
		if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}","winbindd_set_acls_is_xattr() -> winbindd_set_acls_is_xattr_var()");}
		winbindd_set_acls_is_xattr_var();
		return;
	}
	$unix=new unix();
	$setfacl=$unix->find_program("setfacl");
	$mount=$unix->find_program("mount");
	if(!is_file($setfacl)){
		xsyslog("winbindd_priv setfacl no such binary");
		return;
	}
	
	
	if(!is_file($mount)){
		xsyslog("winbindd_priv mount no such binary");
		return;
	}
	
	
	$mustchange=false;
	$f=explode("\n",@file_get_contents("/etc/fstab"));
	foreach ($f as $num=>$ligne){
		if(preg_match("#^(.*?)\s+\/\s+(.*?)\s+(.*?)\s+([0-9]+)\s+([0-9]+)#", $ligne,$re)){
			$options=explode(",",$re[3]);
            foreach ($options as $b){
				$b=trim(strtolower($b));
				if($b==null){continue;}
				progress_logs(20,"{join_activedirectory_domain}","winbindd_priv found main partition {$re[1]} with option `$b`");
				$MAINOPTIONS[trim($b)]=true;
			}
			if(!isset($MAINOPTIONS["acl"])){$mustchange=true;$options[]="acl";$options[]="user_xattr";}
			if(!$mustchange){
				progress_logs(20,"{join_activedirectory_domain}","winbindd_priv found main partition {$re[1]} ACL user_xattr,acl");
			}else{
				progress_logs(20,"{join_activedirectory_domain}","winbindd_priv found main partition {$re[1]} Add ACL user_xattr options was ". @implode(";", $options)."");
				$f[$num]="$re[1]\t/\t$re[2]\t".@implode(",", $options)."\t$re[4]\t$re[5]";
				reset($f);

                foreach ($f as $c=>$d){if(trim($d)==null){continue;}$cc[]=$d;}
				if(count($cc)>1){
					@file_put_contents("/etc/fstab", @implode("\n", $cc)."\n");
					xsyslog("winbindd_priv remount system partition...");
					shell_exec("$mount -o remount /");
				}
			}
		}
	}
	if($GLOBALS["VERBOSE"]){progress_logs(20,"{join_activedirectory_domain}","winbindd_set_acls_is_xattr() -> winbindd_set_acls_is_xattr_var()");}
	winbindd_set_acls_is_xattr_var();
}


function winbindd_monit(){
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}

function build_progress_disconnect($text,$pourc):bool{
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.ad.disconnect.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
    return true;
}


function disconnect(){
	$unix=new unix();	
	$user=new settings_inc();
	$netbin=$unix->LOCATE_NET_BIN_PATH();
	$kdestroy=$unix->find_program("kdestroy");
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));
    if(!is_array($array)){$array=array();}
	if(!isset($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
	if(!is_numeric($array["USE_AUTORID"])){$array["USE_AUTORID"]=1;}
    $sock->SET_INFO("EnableKerbNTLM", 0);
	
	$domainUp=strtoupper($array["WINDOWS_DNS_SUFFIX"]);
	$domain_lower=strtolower($array["WINDOWS_DNS_SUFFIX"]);
	$adminpassword=$array["WINDOWS_SERVER_PASS"];
	$adminpassword=$unix->shellEscapeChars($adminpassword);
	$adminpassword=str_replace("'", "", $adminpassword);
	$adminname=$array["WINDOWS_SERVER_ADMIN"];
	$ad_server=$array["WINDOWS_SERVER_NETBIOSNAME"];	
	$kdb5_util=$unix->find_program("kdb5_util");
	
	$function=__FUNCTION__;
	
	
	
	if(!is_file($netbin)){progress_logs(100,"{join_activedirectory_domain}"," net, no such binary");return;}
	if(!$user->WINBINDD_INSTALLED){progress_logs(100,"{join_activedirectory_domain}"," Samba, no such software");return;}	
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbAuth", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableKerbMonitor", 0);
	
	
	build_progress_disconnect("Flush Keytab...",5);
	echo "$netbin ads keytab flush $adminname%$adminpassword\n";
	exec("$netbin ads keytab flush 2>&1",$results);
	build_progress_disconnect("Leave Active Directory...",10);
	echo "$netbin ads leave -U $adminname%****\n";
	exec("$netbin ads leave -U $adminname%$adminpassword 2>&1",$results);
	build_progress_disconnect("Destroy Kerberos ticket",10);
	exec("$kdestroy 2>&1",$results);
	build_progress_disconnect("Destroy Kerberos ticket",15);
	system("$kdb5_util -r $domainUp  -P $adminpassword destroy -f");
	build_progress_disconnect("Destroy Kerberos ticket",20);
	@unlink("/etc/squid3/krb5.keytab");
	
	squid_admin_mysql(0, "Active directory disconnected", "An order as been sent to disconnect Active Directory",__FILE__,__LINE__);
	build_progress_disconnect("Stamp to not use Active Directory",50);
	
	@unlink("/etc/cron.d/artica-ads-watchdog");
	@unlink("/etc/cron.daily/msktutil");
	
	
	build_progress_disconnect("Remove the system from Active Directory",70);
	exec("/usr/sbin/artica-phpfpm-service -nsswitch",$results);

	
	
	foreach ($results as $num=>$ligne){
		echo "Leave......: $ligne\n";
		progress_logs(90,"{join_activedirectory_domain}","Leave......: $ligne");
	}	
	
	remove_service("/etc/init.d/winbind");
	remove_service("/etc/init.d/ntlm-monitor");
	remove_service("/etc/init.d/adsenv");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	
	if(is_file("/etc/init.d/squid")){
		build_progress_disconnect("{reconfiguring_proxy_service}",80);
		$php5=$unix->LOCATE_PHP5_BIN();
		system("$php5 /usr/share/artica-postfix/exec.squid.php --build --force");
	}
	
	build_progress_disconnect("{done}",100);
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


function checkParams(){
	
	progress_logs(5,"{apply_settings}","Checks settings..");
	
	$sock=new sockets();
	$array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KerbAuthInfos"));

	if($array["WINDOWS_DNS_SUFFIX"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_DNS_SUFFIX = NULL");
		return false;
	}
	if($array["WINDOWS_SERVER_NETBIOSNAME"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_NETBIOSNAME = NULL");
		return false;}
	if($array["WINDOWS_SERVER_TYPE"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_TYPE = NULL");
		return false;}
	if($array["WINDOWS_SERVER_ADMIN"]==null){
		progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_ADMIN = NULL");return false;}
	if($array["WINDOWS_SERVER_PASS"]==null){progress_logs(20,"{apply_settings}","Auth Winbindd, misconfiguration failed WINDOWS_SERVER_PASS = NULL");return false;}
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");
	$hostname=strtolower(trim($array["WINDOWS_SERVER_NETBIOSNAME"])).".".strtolower(trim($array["WINDOWS_DNS_SUFFIX"]));
	
	progress_logs(20,"{apply_settings}","Trying to resolve host $hostname ....". __LINE__);
	$ip=gethostbyname($hostname);
	progress_logs(20,"{apply_settings}","host $hostname = $ip....". __LINE__);
	
	if($ip==$hostname){
		progress_logs(7,"{apply_settings}","!!!!! $ip gethostbyname($hostname) failed !!!!!");
		return false;
		
	}
	
	progress_logs(7,"{apply_settings}","Checks settings success..");
	return true;
}

function xsyslog($text){
	
	if(function_exists("WriteToSyslogMail")){WriteToSyslogMail($text, basename(__FILE__));}

}
function GetUsersNumber(){
	include_once(dirname(__FILE__)."/ressources/class.external.ad.inc");
	$ad=new external_ad_search();
	$users=$ad->NumUsers();
	echo "Users: $users\n";
}