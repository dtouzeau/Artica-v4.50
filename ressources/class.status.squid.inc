<?php
//SP139
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}
include_once(dirname(__FILE__)."/class.squid.watchdog.inc");
function squid_increment_func($array){
        $array[] = "squid_master_status";
        $array[] = "squid_nat";
        $array[] = "squid_db";
        $array[] = "cntlm";
        $array[] = "cntlm_parent";
        $array[] = "squidguardweb";
        $array[] = "iptables_transparent";
        $array[] = "hypercache_logger";
        $array[] = "SQUID_TAIL_SIZE";
        $array[] = "SQUID_TAIL_SIZE_HELPERS";
        $array[] = "storeid_file_rewrite_checkupdates";
        $array[]="adker_watchdog";
        $array[]="ufdbguardd";
        $array[]="ufdbguard_http";
        $array[]="ufdbguard_http_light";
        $array[]="WSUS_HTTP";
        $array[]="ziproxy";
        $array[]="ntlm_monitor";
        $array[]="msktutil_watch";
        $array[]="RemoveServices";

	return $array;
}


function CheckADLicense():bool{
    if($GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){return true;}
    if(!ifAdConnected()){return true;}
    $fname="/etc/squid3/http_access_final.conf";
    $f=explode("\n",@file_get_contents($fname));

    $WRITE=false;
    foreach ($f as $index=>$line){
        if(preg_match("#^http_access.*?deny.*?AUTHENTICATED#",$line)){
            _squid_status_out("WARNING Disable Active Directory (License Error)");
            $f[$index]="# $line";
            $WRITE=true;
        }
    }
    if($WRITE){
        @file_put_contents($fname,@implode("\n",$f));
    }
    return true;
}
function _squid_status_out($text):bool{
    if(!function_exists("openlog")){return true;}
    openlog("monit", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "artica-watchdog: $text");
    closelog();
    return true;
}
function ifAdConnected():bool{
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$EnableActiveDirectoryFeature=0;}
    if($EnableActiveDirectoryFeature==0){return false;}
    $LockActiveDirectoryToKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    if($LockActiveDirectoryToKerberos==1){return true;}
    $UseNativeKerberosAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseNativeKerberosAuth"));
    if($UseNativeKerberosAuth==1){return true;}
    $EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
    if($EnableKerbAuth==1){return true;}
    return false;
}
function storeid_file_rewrite_checkupdates():bool{
    $srcfile = "/usr/share/artica-postfix/bin/storeid_file_rewrite";
    $destfile = "/lib/squid3/storeid_file_rewrite";
    $md52=null;
    $SquidCacheLevel=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCacheLevel"));
    $SquidDisableCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableCaching"));
    $SquidDisableHyperCacheDedup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableHyperCacheDedup"));
    $StoreIDUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StoreIDUrgency"));
    $EnableIntelCeleron=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableIntelCeleron"));
    $HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
    $SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
    if($HyperCacheStoreID==0){return false;}
    if($EnableIntelCeleron==1){return false;}
    if($SquidCacheLevel==0){return false;}
    if($SquidDisableCaching==1){return false;}
    if($SquidUrgency==1){return false;}
    if($SquidDisableHyperCacheDedup==1){return false;}
    if($StoreIDUrgency==1){return false;}
    if(!is_file($srcfile)){return false;}
    $md51=md5_file($srcfile);
    $md5Size=filesize($srcfile);
    $md52Size=0;
    if(is_file($destfile)){
        $md52=md5_file($destfile);
        $md52Size=filesize($destfile);
    }
    if($md51==$md52){return true;}

    $logs[]="Source: $srcfile: $md51 $md5Size bytes";
    $logs[]="Destination: $destfile $md52 $md52Size bytes";
    squid_admin_mysql(1,"Launch HyperCache binary update",@implode("\n",$logs),__FILE__,__LINE__);
    $GLOBALS["CLASS_UNIX"]->framework_exec("exec.hypercache-dedup.php");
    return true;
    }



function zipproxy_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ziproxy");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/ziproxy.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF($masterbin);
}

function LockActiveDirectoryToKerberos():bool{
    $HaClusterClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){return false;}
    if(!is_file("/etc/squid3/krb5.keytab")){
        squid_admin_mysql(0,"Active Directory: Fatal missing /etc/squid3/krb5.keytab [action=reconnect]",null,__FILE__,__LINE__);
        shell_exec2("/usr/sbin/artica-phpfpm-service -msktutils >/dev/null 2>&1");

    }

    if(!is_file("/etc/cron.d/squid-negotiateauthenticator-5mn")) {
        $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("squid-negotiateauthenticator-5mn", "*/5 * * * *", "exec.negotiateauthenticator.php");
        system("/etc/init.d/cron reload");
    }
    return true;
}

function adker_watchdog():bool{
	if(is_file("/etc/cron.d/artica-ads-watchdog")){
		@unlink("/etc/cron.d/artica-ads-watchdog");
		shell_exec("/etc/init.d/cron reload");
	}

    $LockActiveDirectoryToKerberos= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LockActiveDirectoryToKerberos"));
    $HaClusterClient= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));
    if($HaClusterClient==1){$LockActiveDirectoryToKerberos=1;}
	if($LockActiveDirectoryToKerberos==1){
        LockActiveDirectoryToKerberos();
    }
    return true;
}
function ___squid_ldap_auth_path():string{
	if(is_file('/lib/squid3/basic_ldap_auth')){return '/lib/squid3/basic_ldap_auth';}
	if(is_file('/usr/lib/squid3/squid_ldap_auth')){return '/usr/lib/squid3/squid_ldap_auth';}
	if(is_file('/usr/lib64/squid3/squid_ldap_auth')){return '/usr/lib64/squid3/squid_ldap_auth';}
	if(is_file('/lib/squid3/squid_ldap_auth')){return '/lib/squid3/squid_ldap_auth';}
	if(is_file('/lib64/squid3/squid_ldap_auth')){return '/lib64/squid3/squid_ldap_auth';}
	if(is_file('/usr/lib/squid/ldap_auth')){return '/usr/lib/squid/ldap_auth';}
	if(is_file('/usr/lib/squid/squid_ldap_auth')){return '/usr/lib/squid/squid_ldap_auth';}
	if(is_file('/usr/lib64/squid/squid_ldap_auth')){return '/usr/lib64/squid/squid_ldap_auth';}
	if(is_file('/usr/lib64/squid/ldap_auth')){return '/usr/lib64/squid/ldap_auth';}
	if(is_file('/usr/local/lib/squid/ldap_auth')){return '/usr/local/lib/squid/ldap_auth';}
	if(is_file('/usr/local/lib64/squid/ldap_auth')){return '/usr/local/lib64/squid/ldap_auth';}
	if(is_file('/opt/artica/libexec/squid_ldap_auth')){return '/opt/artica/libexec/squid_ldap_auth';}
	return "";
}

function RemoveServices():bool{
	if(!is_file("/etc/artica-postfix/settings/Daemons/SQUIDEnable")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUIDEnable", 1);}
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==1){return true;}
	
	$servicesINIT[]="squid";
	$servicesINIT[]="squidguard-http";
	$servicesINIT[]="squid-nat";
	$servicesINIT[]="squid-tail";
	$servicesINIT[]="iptables-transparent";
	$servicesINIT[]="hypercache-web";
	$servicesINIT[]="zipproxy";
	$servicesINIT[]="ntlm-monitor";
	$servicesINIT[]="cache-scheduler";
	$servicesINIT[]="cache-httpd";
	$servicesINIT[]="ufdb-client";
	$servicesINIT[]="microhotspot";
	$servicesINIT[]="hotspot-web";
	$servicesINIT[]="SquidRouterMode";
	$servicesINIT[]="proxy-wccp";
	$servicesINIT[]="privoxy";
	$servicesINIT[]="cntlm";
    $servicesINIT[]="squid-dns";


	foreach ($servicesINIT as $filename) {
		if(!is_file("/etc/init.d/$filename")){continue;}
		squid_admin_mysql(0, "Removing service $filename ( Proxy is disabled )", "Remove /etc/init.d/$filename",__FILE__,__LINE__);
		remove_service("/etc/init.d/$filename");
	}
		
	return true;
}

function HypercacheStatus():bool{
    $HyperCacheStoreID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoreID"));
    $tfile=PROGRESS_DIR."/HyperCacheStoreID.status";
    $l[]="[HYPERCACHE_STOREID]";
    $l[]="service_name=HYPERCACHE_STOREID";
    $l[]="service_cmd=/etc/init.d";
    $l[]="master_version=1.0";
    $l[]="service_disabled=$HyperCacheStoreID";
    $l[]="family=network";
    $l[]="watchdog_features=1";
    $l[]="installed=1";

    $pids=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL("storeid_file_rewrite");
    if(count($pids)==0){
        $l[]="running=0";
        @file_put_contents($tfile,@implode("\n",$l));
        return true;
    }
    $master_pid=0;
    $rss=0;$vm=0;$count=0;$uptime=null;
    foreach ($pids as $num=>$pid){
        if(!is_numeric($pid)){continue;}
        $newrss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid,true);
        $rss=$rss+$newrss;
        $vm=$vm+$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($pid,true);
        $uptime=$GLOBALS["CLASS_UNIX"]->PROCESS_UPTIME($pid);
        $master_pid=$pid;
        $count++;
    }
    $l[]="master_pid=$master_pid";
    $l[]="running=1";
    $l[]="master_memory=$rss";
    $l[]="master_cached_memory=$vm";
    $l[]="processes_number=$count";
    $l[]=$uptime;
    $l[]="";
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() Return ".count($l)." Lines\n";}
    @file_put_contents($tfile,@implode("\n",$l));
    return true;
}





function squid_compilation_params(){
    $squidbin=$GLOBALS["CLASS_UNIX"]->LOCATE_SQUID_BIN();
    $COMPILE_SQUID_TOKENS=array();
    exec("$squidbin -v 2>&1",$results);
    $text=@implode("\n", $results);
    if(preg_match("#configure options:\s+(.+)#is", $text,$re)){$text=$re[1];}
    if(preg_match_all("#'(.+?)'#is", $text, $re)){
        foreach ($re[1] as $index=>$line){
            if(preg_match("#(.+?)=(.*)#", $line,$ri)){
                $key=$ri[1];
                $value=$ri[2];
                $key=str_replace("--", "", $key);
                if(trim($value)==null){$value=true;}
                $COMPILE_SQUID_TOKENS[$key]=$value;
                continue;
            }
            $key=$line;
            $value=1;
            $key=str_replace("--", "", $key);
            if($GLOBALS["VERBOSE"]){echo "squid -v [$key] = `$value`\n";}
            $COMPILE_SQUID_TOKENS[$key]=$value;
        }

    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("COMPILE_SQUID_TOKENS",serialize($COMPILE_SQUID_TOKENS));
    return true;
}

function SquidRouterMode(){
	$SquidInRouterMode=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidInRouterMode"));
	if($SquidInRouterMode==0){return;}
	
	$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
	$SquidCachesProxyEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidCachesProxyEnabled"));
	$EnableUfdbGuard=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"));
	$EnableUfdbSimplePage=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbSimplePage"));

	$CicapEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"));
	$EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
	$EnableSquidLogger=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidLogger"));
	
	if($SquidCachesProxyEnabled==1){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.global.access.php --disable-cache");
	}
	if($EnableProxyCompressor==1){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.zipproxy.php --uninstall >/dev/null 2>&1");
	}
	if($EnableUfdbGuard==1){
		shell_exec2("/usr/sbin/artica-phpfpm-service -uninstall-ufdb >/dev/null 2>&1");
	}	
	if($EnableUfdbSimplePage==1){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdb-lighthttp.php --uninstall-web >/dev/null 2>&1");
	}	

	if($CicapEnabled==1){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.c-icap.install.php --uninstall >/dev/null 2>&1");
	}

	if($EnableLocalUfdbCatService==1){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdbcat.php --remove-progress >/dev/null 2>&1");
	}	
	if($EnableSquidLogger==1){
		shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid-logger.php --install >/dev/null 2>&1");
	}
	
	
}

function ziproxy():string{
	
	$ZIPROXY_INSTALLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZIPROXY_INSTALLED"));
	
	if($ZIPROXY_INSTALLED==0){return "";}
	if(!is_file("/etc/init.d/zipproxy")){return "";}
	$EnableProxyCompressor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyCompressor"));
	$l[]="";
	$l[]="[APP_ZIPROXY]";
	$l[]="service_name=APP_ZIPROXY";
	$l[]="service_cmd=/etc/init.d/zipproxy";
	$l[]="master_version=".trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ZIPROXY_VERSION"));
	$l[]="service_disabled=$EnableProxyCompressor";
	$l[]="family=network";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==0){
        if(is_file("/etc/init.d/zipproxy")) {
            shell_exec2("{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.zipproxy.php --uninstall >/dev/null 2>&1 &");
        }
		$l[]="running=0";
		return implode("\n",$l);
	}
	
	if($EnableProxyCompressor==0){return implode("\n",$l);}
	$master_pid=zipproxy_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){squid_admin_mysql(0, "HTTP Proxy compressor nor running [{action}={start}]", null,__FILE__,__LINE__);}
		}
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$nohup=$GLOBALS["CLASS_UNIX"]->find_program("nohup");
			shell_exec2("$nohup {$GLOBALS["NICE"]} /etc/init.d/zipproxy start >/dev/null 2>&1 &");
		
		}
		
		$l[]="running=0";
		return implode("\n",$l);
	}
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_ZIPROXY");
	$l[]="";
	return implode("\n",$l);
	
}


function hypercache_logger(){
	if(is_file("/usr/share/artica-postfix/exec.init-tail-hypercache.php")){@unlink("/usr/share/artica-postfix/exec.init-tail-hypercache.php"); }
	if(is_file("/usr/share/artica-postfix/exec.hypercache-tail.php")){ @unlink("/usr/share/artica-postfix/exec.hypercache-tail.php"); }
}








function ufdbguard_http_light_pid(){

	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/UfdbWebf.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return $pid;
	}

	return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/sbin/ufdbweb");

}

function ufdbguard_http_light(){}

function ufdbguard_http(){
	
	
	$ARTICA_VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$EnableSquidGuardHTTPService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidGuardHTTPService"));
	
	if(!is_file("/etc/init.d/ufdb-http")){
		if($EnableSquidGuardHTTPService==0){return null;}
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdb-http.php --install-web >/dev/null 2>&1 &");
		squid_admin_mysql(0, "Install the Web-Filtering Web service ( enabled but was not installed)", null,__FILE__,__LINE__);
		return null;
		
	}
	$l[]="";
	$l[]="[APP_UFDB_HTTP]";
	$l[]="service_name=APP_UFDB_HTTP";
	$l[]="master_version=$ARTICA_VERSION";
	$l[]="service_cmd=/etc/init.d/ufdb-http";
	$l[]="service_disabled=$EnableSquidGuardHTTPService";
	$l[]="family=squid";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	
	if($EnableSquidGuardHTTPService==0){return implode("\n",$l);}
	$master_pid=ufdbguard_http_pid();
	$l[]="pid_path=/var/run/webfilter-http.pid";
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb-http start >/dev/null 2>&1 &");
		}
		$l[]="";return implode("\n",$l);
	}
	
	$l[]=GetMemoriesOf($master_pid,"APP_UFDB_HTTP");
	$l[]="";
	
	return implode("\n",$l);
}
function ufdbguard_http_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/webfilter-http.pid");
	if($unix->process_exists($pid)){
		return $pid;
	}
	return $unix->PIDOF_PATTERN("/usr/share/artica-postfix/webfilter-http.py");

}


function squidguardweb(){
	if(!is_file("/etc/init.d/squidguard-http")){return null;}
	squid_admin_mysql(0, "Web error page service will be upgraded to 3.x generation", null,__FILE__,__LINE__);
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdb-http.php --install-web >/dev/null 2>&1");
}



function iptables_transparent():string{
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	if($SQUIDEnable==0){return "";}
	if(!$GLOBALS["CLASS_UNIX"]->SQUID_INSTALLED()){
		$l[]="";
		$l[]="[APP_TRANSPARENT_ROUTING]";
		$l[]="service_name=APP_TRANSPARENT_ROUTING";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);
	}
	
	if(!is_file("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT")){
		return "";
	}
	
	$l[]="\n[APP_TRANSPARENT_ROUTING]";
	$l[]="service_name=APP_TRANSPARENT_ROUTING";
	$ports=intval(@file_get_contents("/etc/artica-postfix/MYSQL_AS_PROXY_TRANSPARENT"));


	if($ports==0){
		$l[]="installed=1";
		$l[]="service_disabled=0";
		$l[]="";
		return @implode("\n", $l);
	}

	$l[]="installed=1";
	$l[]="service_disabled=1";
	$l[]="master_version=".iptables_version();
	$l[]="service_cmd=/etc/init.d/iptables-transparent";
	
	
	
	$iptables_save=$GLOBALS["CLASS_UNIX"]->find_program("iptables-save");
	exec("$iptables_save 2>&1",$results);
	$OK1=false;
	foreach ($results as $ligne){
		if(preg_match("#-A PREROUTING.*?-p tcp -m tcp --dport [0-9]+#", $ligne)){
			$OK1=true;
			break;
	
		}
	
		if(preg_match("#PREROUTING .*?--dport\s+[0-9]+.*?REDIRECT\s+--to-ports\s+[0-9]+#",$ligne)){
			$OK1=true;
			break;
			
		}
	
		if(preg_match("#-A PREROUTING -p tcp -m tcp --dport [0-9]+ -m comment.*?--to-ports\s+[0-9]+#", $ligne)){
			$OK1=true;
			break;
			
		}
	
		if(preg_match("#-A\s+out_trproxy\.[0-9]+.*?-p tcp -j REDIRECT\s+--to-ports\s+[0-9]+#",$ligne)){
			$OK1=true;
			break;
			
		}
	
	}
	
	
	
	if(!$OK1){
		squid_admin_mysql(1, "Transparent method inactive [action=notify]", @implode("\n", $results),__FILE__,__LINE__);
		$depmod=$GLOBALS["CLASS_UNIX"]->find_program("depmod");
		shell_exec("$depmod -a");
		$l[]="running=0\ninstalled=1";
		$l[]="";
		return implode("\n",$l);
	}
		
	$l[]="installed=1";
	$l[]="running=1";
	$l[]="master_pid=0";
	$l[]="master_time=0";
	$l[]="master_memory=0";
	$l[]="processes_number=1";
	$l[]="uptime=none";
	$l[]="";
	return @implode("\n", $l);
	
}

function WSUS_HTTP_PID(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/wsus-http/http-server.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return $pid;
	}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/share/artica-postfix/wsus-http.py");
}

function WSUS_HTTP(){

	
	if(!is_file("/etc/init.d/cache-httpd")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WindowsUpdateCaching", 0);
		return;
	}
	
	squid_admin_mysql(0, "Windows Update Caching will be disabled ( feature replaced by HyperCache Proxy Service)", "The HyperCache Proxy service is able to cache more updates sites than the Windows Updates Feature..");
	shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squid.windowsupdate.enable.php --disable >/dev/null 2>&1 &");
	
	
	
}
function WSUS_SCHEDULER_PID(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/wsus-http/scheduler.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return $pid;
	}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/usr/share/artica-postfix/wsus-scheduler.py");
}



function SQUID_TAIL_SIZE_PID(){
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/squid-size-tail.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("/squid-size-tail.py");
	
}

function SQUID_TAIL_SIZE(){
	$EnableQuotaSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQuotaSize"));
	if(!is_file("/etc/init.d/squid-tail-size")){return;}
	$l[]="";
	$l[]="[SQUID_TAIL_SIZE]";
	$l[]="service_name=SQUID_TAIL_SIZE";
	$l[]="master_version=".@file_get_contents("/usr/share/artica-postfix/VERSION");
	$l[]="service_cmd=/etc/init.d/squid-tail-size";
	$l[]="service_disabled=$EnableQuotaSize";
	$l[]="watchdog_features=1";
	$l[]="pid_path=/var/run/squid-size-tail.pid";
	$l[]="installed=1";
	if($EnableQuotaSize==0){return @implode("\n", $l)."\n";}
	
	
	$master_pid=SQUID_TAIL_SIZE_PID();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(1, "Size Quotas checker service is {stopped} [{action}={start}]",null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-tail-size start >/dev/null 2>&1 &");
		}
	
		$l[]="running=0";
		return @implode("\n", $l)."\n";
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"SQUID_TAIL_SIZE");
	$l[]="";
	return @implode("\n", $l)."\n";
}

function SQUID_TAIL_SIZE_HELPERS_PIDS(){

	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	$pids=array();

	exec("$pgrep -l -f squid-size-helper.py",$f);
	foreach ($f as $index=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		if(is_numeric(trim($pid))){$pids[trim($pid)]=trim($pid);continue;}
		if(preg_match("#([0-9]+)#", $pid,$re)){$pids[$re[1]]=true;}
	}
	return $pids;
}

function SQUID_TAIL_SIZE_HELPERS(){ 
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableQuotaSize")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableQuotaSize", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/SizeQuotasCheckerEmergency")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SizeQuotasCheckerEmergency", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidDisableAllFilters")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDisableAllFilters", 0);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/SquidUrgency")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUrgency", 0);}
	
	
	$EnableQuotaSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableQuotaSize"));
	$SizeQuotasCheckerEmergency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SizeQuotasCheckerEmergency"));
	$SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
	$SquidDisableAllFilters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidDisableAllFilters"));
	if($SizeQuotasCheckerEmergency==1){$EnableQuotaSize=0;}
	if($SquidUrgency==1){$EnableQuotaSize=0;}
	if($SquidDisableAllFilters==1){$EnableQuotaSize=0;}
	
	if(!is_file("/etc/init.d/squid-tail-size")){return;}
	
	
	$l[]="";
	$l[]="[SQUIDTAILSIZE_HELPERS]";
	$l[]="service_name=SQUIDTAILSIZE_HELPERS";
	$l[]="installed=1";
	
	if($EnableQuotaSize==0){
		$l[]="running=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
		
	}
	
	$pids=SQUID_TAIL_SIZE_HELPERS_PIDS();
	
	$count=count($pids);
	events("Clients = $count",__FUNCTION__,__LINE__);
	
	
	if($count==0){
		$l[]="running=0";
		$l[]="";
	
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			squid_admin_mysql(0, "Size Helpers clients are not running [action=notify]", null,__FILE__,__LINE__);
		}
	
		return implode("\n",$l);
	}
	
	
	$rss=0;
	$vm=0;
    $master_pid=0;$uptime="";
	if($GLOBALS["VERBOSE"]){echo "-> SQUID_TAIL_SIZE_HELPERS_PIDS() -> PIDs:$count\n";}
	foreach ($pids as $num=>$pid){
		if(!is_numeric($pid)){continue;}
		$newrss=$GLOBALS["CLASS_UNIX"]->PROCESS_MEMORY($pid,true);
		if($GLOBALS["VERBOSE"]){echo "-> SQUID_TAIL_SIZE_HELPERS_PIDS() -> PID:$num-$pid RSS: $newrss\n";}
		$rss=$rss+$newrss;
		$vm=$vm+$GLOBALS["CLASS_UNIX"]->PROCESS_CACHE_MEMORY($pid,true);
		$uptime=$GLOBALS["CLASS_UNIX"]->PROCESS_UPTIME($pid);
		$master_pid=$pid;
	}
	$l[]="master_pid=$master_pid";
	$l[]="running=1";
	$l[]="master_memory=$rss";
	$l[]="master_cached_memory=$vm";
	$l[]="processes_number=$count";
	$l[]=$uptime;
	$l[]="";
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."() Return ".count($l)." Lines\n";}
	return implode("\n",$l);
	
	
	
}


function ufdbguardd(){
    $l=array();
	$ufdbguardd_path=$GLOBALS["CLASS_UNIX"]->find_program("ufdbguardd");
	if(is_file($ufdbguardd_path)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_UFDBGUARD_INSTALLED", 1);
	}else{
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_UFDBGUARD_INSTALLED", 0);
		$l[]="";
		$l[]="[APP_UFDBGUARD]";
		$l[]="service_name=APP_UFDBGUARD";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	
	$ufdbgclient_path=$GLOBALS["CLASS_UNIX"]->find_program("ufdbgclient");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ufdbgclient_path", $ufdbgclient_path);
	if(!is_file("/etc/init.d/ufdb")){return;}
	
	$SquidUrgency=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidUrgency"));
	if($SquidUrgency==1){return;}
	$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();
	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	

	$master_pid=ufdbguard_pid();
	$EnableUfdbGuard=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard();
	$UseRemoteUfdbguardService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UseRemoteUfdbguardService"));
	if($UseRemoteUfdbguardService==1){$EnableUfdbGuard=0;}

	if($EnableUfdbGuard==0){
		if(is_file("/etc/init.d/ufdb")){
			squid_admin_mysql(0, "Removing Web-Filtering startup script (feature disabled)", null,__FILE__,__LINE__);
			remove_service("/etc/init.d/ufdb");
            return implode("\n",$l);
		}
	}
	
	if(!is_file("/etc/artica-postfix/settings/Daemons/WebFilteringRansomware")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("WebFilteringRansomware", 1);}
	if(!is_file("/etc/artica-postfix/settings/Daemons/EnableGoogleSafeSearch")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGoogleSafeSearch", 1);}
	

	$WindowsUpdateCaching=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsUpdateCaching"));
	$WebFilteringRansomware=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebFilteringRansomware"));
	
	
	if($ServerRunSince>5){
		if(is_file("/home/ufdb/smtp-events/ACCESS_LOG")){
			if($GLOBALS["CLASS_UNIX"]->file_time_min("/home/ufdb/smtp-events/ACCESS_LOG.time")>5){
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdb.smtp.release.php >/dev/null 2>&1 &");
				@unlink("/home/ufdb/smtp-events/ACCESS_LOG.time");
				@file_put_contents("/home/ufdb/smtp-events/ACCESS_LOG.time", time());
			}
			
		}

	}
		
	
	
	$ufdbguardd_version=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UFDBDaemonVersion"));
	
	$l[]="";
	$l[]="[APP_UFDBGUARD]";
	$l[]="service_name=APP_UFDBGUARD";
	$l[]="master_version=$ufdbguardd_version";
	$l[]="service_cmd=/etc/init.d/ufdb";
	$l[]="service_disabled=$EnableUfdbGuard";
	$l[]="watchdog_features=1";
	$l[]="pid_path=$pid_path";
	
	if(!$GLOBALS["DISABLE_WATCHDOG"]){
		if($EnableUfdbGuard==0){
			if($GLOBALS["VERBOSE"]){echo "EnableUfdbGuard =  0 SHUTDOWN\n";}
			if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
				
				$LOGS=$GLOBALS["CLASS_SOCKETS"]->EnableUfdbGuard(true);
				if(function_exists("squid_admin_mysql")){squid_admin_mysql(1, "Stopping Ufdbguard service it is disabled\n".@implode("\n", $LOGS),__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");}
				
				$l[]="running=1";
				$l[]=GetMemoriesOf($master_pid,"APP_UFDBGUARD");
				$l[]="";
				ToSyslog(basename(__FILE__)." Stopping Ufdbguard !!!! PID: $master_pid in line ".__LINE__);

				foreach ($LOGS as $line){ToSyslog(basename(__FILE__)." Stopping Ufdbguard why: $line");}
				
				
				if(is_file("/proc/$master_pid/cmdline")){
					ToSyslog(basename(__FILE__)." $master_pid is `".trim(@file_get_contents("/proc/$master_pid/cmdline"))."`");
				}
				
					
				
				
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb stop >/dev/null 2>&1 &");
			}
			
			
			if(is_file("/etc/cron.d/ufdb-db-checkv")){
				@unlink("/etc/cron.d/ufdb-db-checkv");
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/cron reload >/dev/null 2>&1 &");
			}
			return implode("\n",$l);
			
		}
	}
	
	$TOTAL_MEMORY_MB=$GLOBALS["CLASS_UNIX"]->TOTAL_MEMORY_MB();
	
	
	if($TOTAL_MEMORY_MB>50){
		if($TOTAL_MEMORY_MB<2500){
			squid_admin_mysql(0, "Web-Filtering: FATAL Disable service Not enough memory {$TOTAL_MEMORY_MB}MB, Require at least 2500MB", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /usr/sbin/artica-phpfpm-service -uninstall-ufdb >/dev/null 2>&1 &");
            return implode("\n",$l);
		}
	}

    $CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/settings/Daemons/UfdbCatCountDatabases");

    if($CacheSchedules>10){
        $cmd=trim("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdb.used.databases.php >/dev/null 2>&1 &");
        shell_exec2($cmd);
    }
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		$master_pid=$GLOBALS["CLASS_UNIX"]->PIDOF($ufdbguardd_path);
		@file_put_contents($pid_path,$master_pid);
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			
			if(function_exists("WriteToSyslogMail")){WriteToSyslogMail("UfdGuard master Daemon service is not running -> start it...", basename(__FILE__));}
			$timestamp="/etc/artica-postfix/pids/ufdbguardd.status.notif";
			
			if($GLOBALS["CLASS_UNIX"]->file_time_min($timestamp)>10){
				@unlink($timestamp);
				@file_put_contents($timestamp, time());
				$ps=$GLOBALS["CLASS_UNIX"]->find_program("ps");
				$grep=$GLOBALS["CLASS_UNIX"]->find_program("grep");
				exec("$ps aux|$grep ufdbguardd 2>&1",$ps_aux);
				squid_admin_mysql(1, "UfdGuard master Daemon service is not running -> start it...",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
				squid_admin_mysql(1, "Web filtering Engine is down [{action}={start}]",@implode("\n", $ps_aux),__FUNCTION__,__FILE__,__LINE__);
			}
			
	
			shell_exec2("{$GLOBALS["nohup"]} /usr/sbin/artica-phpfpm-service -start-ufdb >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";return implode("\n",$l);
        return implode("\n",$l);
	}	

	if(!$GLOBALS["DISABLE_WATCHDOG"]){

		if(is_file("/var/log/squid/UFDB_SOCKET_ERROR")){
			@unlink("/var/log/squid/UFDB_SOCKET_ERROR");
			squid_admin_mysql(1, "Sockets error encountered, restart Web filtering service",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
			squid_admin_mysql(0, "Sockets error encountered, restart Web filtering service",
			"/var/log/squid/UFDB_SOCKET_ERROR exists, see previous errors",__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart --force --watchdog >/dev/null 2>&1 &");
		}

		if(!is_dir("/var/log/ufdbguard")){@mkdir("/var/log/ufdbguard",0755,true);}
		if(!is_file("/var/tmp/ufdbguardd.pid")){@file_put_contents("/var/tmp/ufdbguardd.pid", $master_pid);}

		$MaxThreads=140;
		$CurrentThreads=$GLOBALS["CLASS_UNIX"]->PROCESS_SOCKETS_NUM($master_pid);
		if($CurrentThreads>=$MaxThreads){
			squid_admin_mysql(1, "Web filtering, Max threads limit reached - $CurrentThreads - restarting",__FUNCTION__,__FILE__,__LINE__,"ufdbguard-service");
			squid_admin_mysql(0, "Web filtering, Max threads limit reached - $CurrentThreads - restarting", "The Web filtering service threads are freeze, the dameon will be restarted",__FILE__,__LINE__);
			shell_exec("{$GLOBALS["nohup"]} /etc/init.d/ufdb restart --watchdog >/dev/null 2>&1 &");
		}
	
		if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.ufdb.queue.release.php.time")>5){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ufdb.queue.release.php --check >/dev/null 2>&1 &");
		}
	
		if($GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.squidguard.smtp.php.ufdb_all_smtp.time")>15){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.squidguard.smtp.php --smtp >/dev/null 2>&1 &");
		}
		

		
		if($ServerRunSince>5){
		if($WindowsUpdateCaching==1){

					
			$time=$GLOBALS["CLASS_UNIX"]->file_time_min("/usr/share/artica-postfix/ressources/logs/web/WindowsUpdate.state");
			if($time>120){
				Windows_update_events("INFO: Calculating partitions",__LINE__);
				shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.windowsupdate-partials.php --partition  >/dev/null 2>&1 &");
			}
			
		}}
		
		
	}
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_UFDBGUARD");
	$l[]="";
    if($GLOBALS["VERBOSE"]){echo "---------- FINISH ".__FUNCTION__." ---------- \n";}
    if($GLOBALS["VERBOSE"]){print_r($l);}
	$results=implode("\n",$l);
    if($GLOBALS["VERBOSE"]){echo $results;}
    return $results;

}

function Windows_update_events($text,$line=0){
	$date=@date("H:i:s");
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){return false;}
	if(!isset($GLOBALS["MYPID"])){$GLOBALS["MYPID"]=getmypid();}
	$logFile="/var/log/squid/windowsupdate.debug";
	$size=@filesize($logFile);
	if($size>9000000){@unlink($logFile);@touch($logFile);@chown($logFile,"squid");@chgrp($logFile, "squid"); }
	$line="$date:[Scheduler/$line]:[{$GLOBALS["MYPID"]}]: $text";
	if($GLOBALS["VERBOSE"]){echo "$line\n";}
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$line\n");
	@fclose($f);


}
function msktutil_watch():bool{
	
	if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MSKTUTIL_INSTALLED"))==0){return true;}
	$msktutil=$GLOBALS["CLASS_UNIX"]->find_program("msktutil");
	if($msktutil==null){return true;}
	$pids=$GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN_ALL($msktutil);

    foreach ($pids as $pid=>$none){
		$processtime=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
		if($processtime>5){
			squid_admin_mysql(0, "Killed $msktutil, running more than 5mn ({$processtime}Mn)", null,__FUNCTION__,__FILE__,__LINE__);
			$GLOBALS["CLASS_UNIX"]->KILL_PROCESS($pid);}
	}
    return true;
}







//========================================================================================================================================================

function cntlm_pid(){
	$filename="/var/run/cntlm.pid";
	if(is_file($filename)){
		$pid=trim(@file_get_contents($filename));
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	}
	$cntlm=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$cntlm.*?cntlm\.pid");
}
function cntlm_parent_pid(){
	$filename="/var/run/cntlm-parent.pid";
	if(is_file($filename)){
		$pid=trim(@file_get_contents($filename));
		if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	}
	
	
	$cntlm=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$cntlm.*?cntlm-parent");
}


function cntlm_parent(){
	
	if($GLOBALS["VERBOSE"]){echo "\n\n ***** CNTLM PARENT ******* \n\n";}
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	if(!is_file($bin)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CNTLMInstalled", 0);
		$l[]="";
		$l[]="[APP_CNTLM_PARENT]";
		$l[]="service_name=APP_CNTLM_PARENT";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		return implode("\n",$l);
	}
	
	$master_pid=cntlm_parent_pid();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CNTLMInstalled", 1);
	$EnableCNTLM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidEnableParentNTLM"));
	
	$l[]="";
	$l[]="[APP_CNTLM_PARENT]";
	$l[]="service_name=APP_CNTLM_PARENT";
	$l[]="service_cmd=/etc/init.d/cntlm-parent";
	$l[]="master_version=".cntlm_version();
	$l[]="service_disabled=$EnableCNTLM";
	$l[]="pid_path=/var/run/cntlm-parent.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";
	
	
	
	if($EnableCNTLM==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.cntlm-parent.php --stop >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
	}
	
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			$cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.cntlm-parent.php --start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="";
		return implode("\n",$l);
	}
	
	$l[]=GetMemoriesOf($master_pid,"APP_CNTLM_PARENT");
	$l[]="";
	
	return implode("\n",$l);
	
}


function cntlm(){
	$bin=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	if(!is_file($bin)){
		$l[]="";
		$l[]="[APP_CNTLM]";
		$l[]="service_name=APP_CNTLM";
		$l[]="installed=0";
		$l[]="service_disabled=0";
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CNTLMInstalled", 0);
		return implode("\n",$l);
	}
	
	if($GLOBALS["VERBOSE"]){echo "\n\n ***** CNTLM ******* \n\n";}

	$master_pid=cntlm_pid();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CNTLMInstalled", 1);
	$EnableCNTLM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCNTLM");
	$EnableKerbAuth=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth");
	if(!is_numeric($EnableCNTLM)){$EnableCNTLM=0;}
	if(!is_numeric($EnableKerbAuth)){$EnableKerbAuth=0;}
	if($EnableKerbAuth==0){$EnableCNTLM=0;}
	$l[]="";
	$l[]="[APP_CNTLM]";
	$l[]="service_name=APP_CNTLM";
	$l[]="service_cmd=/etc/init.d/cntlm";
	$l[]="master_version=".cntlm_version();
	$l[]="service_disabled=$EnableCNTLM";
	$l[]="pid_path=/var/run/cntlm.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	

	if($EnableCNTLM==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			if(is_file("/etc/init.d/cntlm")){
				squid_admin_mysql(1, "NTLM Proxy service running (disabled) [action=stop]", null,__FILE__,__LINE__);
				$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/cntlm stop >/dev/null 2>&1 &");
                shell_exec2($cmd);
			}

		}
		if($GLOBALS["VERBOSE"]){echo "CNTLM :Disbaled\n";}
		$l[]="";
		return implode("\n",$l);
	}


	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if(is_file("/etc/init.d/cntlm")){
				squid_admin_mysql(1, "NTLM Proxy service down [{action}={start}]", null,__FILE__,__LINE__);
				$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/cntlm start >/dev/null 2>&1 &");
				shell_exec2($cmd);
			}
		}
		$l[]="";
		return implode("\n",$l);
		}
	$l[]=GetMemoriesOf($master_pid,"APP_CNTLM");
	$l[]="";

	return implode("\n",$l);
}



function _statussquid($text=null){
	if(!isset($GLOBALS["MIPIDSQUID"])){$GLOBALS["MIPIDSQUID"]=getmypid();}
	
	$TIME=date("M d H:i:s");
	if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$logFile="/var/log/artica-status-squid.log";
	if(!is_dir(dirname($logFile))){mkdir(dirname($logFile));}
	if (is_file($logFile)) {
		$size=filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$logFile=str_replace("//","/",$logFile);
	$f = @fopen($logFile, 'a');
	@fwrite($f, "$TIME [{$GLOBALS["MIPIDSQUID"]}] $text\n");
	@fclose($f);
}





function squid_nat(){
	if(!is_file("/etc/init.d/squid-nat")){return;}
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	if(!is_file($masterbin)){return;}
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransparent27"));
	$l[]="[APP_SQUID_NAT]";
	$l[]="service_name=APP_SQUID_NAT";
	$l[]="master_version=".squid_27_version();
	$l[]="service_cmd=/etc/init.d/squid-nat";
	$l[]="service_disabled=$enabled";
	$l[]="family=proxy";
	$l[]="watchdog_features=1";

	if($enabled==0){return implode("\n",$l);}
	$master_pid=squid_nat_pid();

	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
			if(!$GLOBALS["DISABLE_WATCHDOG"]){
				shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/squid-nat reload >/dev/null 2>&1 &");
				squid_admin_mysql(0,"Squid-Cache NAT front-end not running, start it...",null,__FILE__,__LINE__);
			}
		}
		$l[]="running=0\ninstalled=1";$l[]="";
		return implode("\n",$l);
	}

	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_SQUID_NAT");
	$l[]="";

	return implode("\n",$l);
}

function squid_db_version(){
	return $GLOBALS["CLASS_UNIX"]->MYSQL_VERSION();
}



function task_squid_license():bool{
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	if(is_file("/etc/cron.d/squid-analyze-license")){@unlink("/etc/cron.d/squid-analyze-license");}
	
	if($SQUIDEnable==1) {
        CheckADLicense();
        if (!is_file("/etc/cron.d/proxy-count-users")) {
            Popuplate_cron_make("proxy-count-users", "0,10,20,30,40,50 * * * *", "exec.squid.licenses-users.php");
            UNIX_RESTART_CRON();
        }
        return true;
    }

	if(is_file("/etc/cron.d/proxy-count-users")){
        @unlink("/etc/cron.d/proxy-count-users");
		UNIX_RESTART_CRON();
    }
	return true;
}


function task_squid_stored_objects(){
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	$EnableSquidPurgeStoredObjects=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjects"));
	$EnableSquidPurgeStoredObjectsTime=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidPurgeStoredObjectsTime"));
	$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
	
	if($SQUIDEnable==0){$EnableSquidPurgeStoredObjects=0;}
	if($EnableIntelCeleron==1){$EnableSquidPurgeStoredObjects=0;}
	if(!preg_match("#([0-9]+):([0-9]+)#", $EnableSquidPurgeStoredObjectsTime,$re)){
		$HOUR=3;
		$MIN=40;
	}else{
		$HOUR=$re[1];
		$MIN=$re[2];
	}
	
	if($EnableSquidPurgeStoredObjects==0){
		if(is_file("/etc/cron.d/squid-analyze-cache")){
			@unlink("/etc/cron.d/squid-analyze-cache");
			shell_exec("/etc/init.d/cron reload");
		}
		return;
	}
	
	if(!is_file("/etc/cron.d/squid-analyze-cache")){
		Popuplate_cron_make("squid-analyze-cache","$MIN $HOUR * * *","exec.purge.php");
		shell_exec("/etc/init.d/cron reload");
	}

}

//#############################################################################
function squid_master_status($buildcache=false):string{
    $xCacheTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$CacheSchedules=$GLOBALS["CLASS_UNIX"]->file_time_sec($xCacheTime);
	if($CacheSchedules<20){return @file_get_contents($xCacheTime);}
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0){
        return "";
    }
    task_squid_stored_objects();
    task_squid_license();
	$squid_ldap_auth_path=___squid_ldap_auth_path();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SQUID_LDAP_AUTH",$squid_ldap_auth_path);

	if(!is_file("/etc/artica-postfix/settings/Daemons/UnlockWebStats")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UnlockWebStats", 0);}

    squid_compilation_params();
	//$ServerRunSince=$GLOBALS["CLASS_UNIX"]->ServerRunSince();

    $isrtime_logs=isrtime_logs();
    if($isrtime_logs) {
        if (!is_file("/etc/cron.d/postgres-hourly")) {
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("postgres-hourly", "0,10,20,30,40,50 * * * *", "exec.postgres.squid.hourly.php");
            system("/etc/init.d/cron reload");
        }
    }else{
        if (is_file("/etc/cron.d/postgres-hourly")) {
            @unlink("/etc/cron.d/postgres-hourly");
            system("/etc/init.d/cron reload");
        }

    }

    squid_tasks();
	return "";
}
// ======================================================================================
function check_logging_conf():bool{
    $f=explode("\n",@file_get_contents("/etc/squid3/logging.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^coredump_dir#",$line)){return true;}
    }
    return false;
}
function cntlm_version():string{
	$cacheFile="/etc/artica-postfix/cntlm_version";
	if($GLOBALS["CLASS_UNIX"]->file_time_min($cacheFile)<800){return @file_get_contents("/etc/artica-postfix/cntlm_version");}

	$bin=$GLOBALS["CLASS_UNIX"]->find_program("cntlm");
	if(!is_file($bin)){if($GLOBALS['VERBOSE']){echo "cntlm -> no such file\n";}return "";}
	exec("$bin -h 2>&1",$array);
	foreach ($array as $pid=>$line){
		if(preg_match("#Proxy version\s+([0-9\.\-a-z]+)#i", $line,$re)){
			@unlink($cacheFile);
			@file_put_contents($cacheFile, $re[1]);
			return $re[1];}
			if($GLOBALS['VERBOSE']){echo "cntlm_version(),  \"$line\", not found \n";}
	}
    return "";
}
//====================================================================================
function squid_tasks():bool{
    $php=$GLOBALS["CLASS_UNIX"]->LOCATE_PHP5_BIN();
    $ionice=$GLOBALS["CLASS_UNIX"]->EXEC_NICE();
    $PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
    $RESTART_CRON=false;
    $ArticaP="/usr/share/artica-postfix";

	if(!is_file("/etc/cron.d/squid-client-list")){
		$f[]="MAILTO=\"\"";
		$f[]="10,20,30,40,50,0 * * * * root\t$ionice $php $ArticaP/exec.squidclient.mgr.clientlist.php >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/squid-client-list", @implode("\n", $f));
		@chmod("/etc/cron.d/squid-client-list",0644);
        $RESTART_CRON=true;
	}
	
	
	if(!is_file("/etc/cron.d/squid-run-c")){
        $f=array();
        $f[]="PATH=$PATH";
        $f[]="MAILTO=\"\"";
        $f[]="*/5 * * * * root\t$ionice $php $ArticaP/exec.squid.run.schedules.php >/dev/null 2>&1";
        $f[]="";
        @file_put_contents("/etc/cron.d/squid-run-c", @implode("\n", $f));
        @chmod("/etc/cron.d/squid-run-c",0644);
        $RESTART_CRON=true;
        unset($f);
	}





    if($RESTART_CRON) {
        UNIX_RESTART_CRON();
    }
    return true;

}
function squid_tasks_events($text,$function=null,$line=0){
	$filename=basename(__FILE__);
	$function=__CLASS__."/".$function;
	$GLOBALS["CLASS_UNIX"]->events("$text","/var/log/artica-stats-executor.log",false,$function,$line,$filename);
}

// ======================================================================================
function squid_nat_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/squid-nat.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($masterbin." -f /etc/squid27/squid.conf");
}
function squid_27_version():bool{
	if(isset($GLOBALS["squid_27_version"])){return $GLOBALS["squid_27_version"];}
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("squid27");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -v 2>&1",$results);
	foreach ($results as $val){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			$GLOBALS["squid_27_version"]=trim($re[1]);
			return $GLOBALS["squid_27_version"];
		}
	}
    return "";
}
function squid_db_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/squid-db.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$mysqld=$GLOBALS["CLASS_UNIX"]->find_program("mysqld");
    return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("$mysqld.*?squid-db.sock");

}
function ufdbguard_pid(){

	$pid_path="/var/tmp/ufdbguardd.pid";
	if(!is_dir("/var/tmp")){@mkdir("/var/tmp",0775,true);}
	if(!is_file($pid_path)){$pid_path="/var/run/ufdbguard/ufdbguardd.pid";}
	
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pid_path);
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
	}
	
	$ufdbguardd=$GLOBALS["CLASS_UNIX"]->find_program("ufdbguardd");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($ufdbguardd);
}

function ss5_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/ss5/ss5.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("ss5");
	return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
	
}
function ss5_version(){

	if(isset($GLOBALS["ss5_version"])){return $GLOBALS["ss5_version"];}
	exec("/usr/sbin/ss5 -v 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#SS5 Version\s+([0-9\.]+)#", $line,$re)){
			$GLOBALS["ss5_version"]= $re[1];
			break;
		}
		
	}
	return $GLOBALS["ss5_version"];
}
function ss5(){
	$l[]="[APP_SS5]";
	$l[]="service_name=APP_SS5";

	if(!is_file("/usr/sbin/ss5")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 0);
		$l[]="installed=0";
		$l[]="";
		return @implode("\n", $l);
		
	}
	if(!is_file("/var/lib/ss5/ss5/mod_socks4.so")){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 0);
		$l[]="installed=0";
		$l[]="";
		return @implode("\n", $l);
	}
	
	if(!is_file("/etc/init.d/ss5")){return "";}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SS5Installed", 1);
	$enabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSS5"));
	
	$l[]="master_version=".ss5_version();
	$l[]="service_cmd=/etc/init.d/ss5";
	$l[]="service_disabled=$enabled";
	$l[]="family=squid";
	if($enabled==0){
		if(is_file("/etc/init.d/ss5")){
			shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ss5.php --uninstall");
		}
	}
	
	$master_pid=ss5_pid();
	
	if($GLOBALS["VERBOSE"]){echo "Enabled = $enabled PID: $master_pid\n";}
	if($enabled==0){
		if($GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
			squid_admin_mysql(1, "Socks Proxy server MUST not running [action=stop]", null,__FILE__,__LINE__);
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ss5 stop >/dev/null 2>&1 &");
			$l[]="running=1";
			$l[]=GetMemoriesOf($master_pid,"APP_SS5");
			$l[]="";
			return implode("\n",$l);
		}
	
		return implode("\n",$l)."\n";
	}
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "Socks Proxy server is {stopped} [{action}={start}]", null,__FILE__,__LINE__);}
			shell_exec2("{$GLOBALS["nohup"]} /etc/init.d/ss5 start >/dev/null 2>&1 &");
		}
		$l[]="running=0\ninstalled=1";$l[]="";
	
		return implode("\n",$l);
	}
	
	
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_SS5");
	$l[]="";
	return implode("\n",$l);
}



function ntlm_monitor_pid(){
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/artica-ntlm.pid");
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){
		return $pid;
	}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("ntlm-monitor.py");
}
function ntlm_monitor():string{
	if(!is_file("/etc/init.d/ntlm-monitor")){return "";}
	$l[]="";
	
	$WindowsActiveDirectoryKerberos=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WindowsActiveDirectoryKerberos"));
	if($WindowsActiveDirectoryKerberos==1){return "";}
	$l[]="[APP_NTLM_MONITOR]";
	$l[]="service_name=APP_NTLM_MONITOR";
	$l[]="service_cmd=/etc/init.d/ntlm-monitor";
	$l[]="master_version=".@file_get_contents("/usr/share/artica-postfix/VERSION");
	$l[]="service_disabled=1";
	$l[]="pid_path=/var/run/proxy-pac/http-server.pid";
	$l[]="watchdog_features=1";
	$l[]="installed=1";
	$l[]="family=network";
	$master_pid=ntlm_monitor_pid();
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){squid_admin_mysql(0, "NTLM Monitor {stopped} [{action}={start}]", null,__FILE__,__LINE__);}
			$cmd=trim("{$GLOBALS["NICE"]} /etc/init.d/ntlm-monitor start >/dev/null 2>&1 &");
			shell_exec2($cmd);
		}
		$l[]="running=0";
		$l[]="";
		return implode("\n",$l);

	}
	$l[]="running=1";
	$l[]=GetMemoriesOf($master_pid,"APP_NTLM_MONITOR");
	$l[]="";
	return implode("\n",$l);

}
// ====================================================================================
function isrtime_logs():bool{
    $EnableSquidLogger=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidLogger"));
    $EnableRedisServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRedisServer"));
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    if($EnableSquidLogger==0){return false;}
    if($EnableRedisServer==0){return false;}
    if($SQUIDEnable==0){return false;}
    return true;
}