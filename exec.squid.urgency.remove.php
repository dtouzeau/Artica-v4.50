<?php
$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.urgency.disable.progress";
$GLOBALS["ARGVS"]=implode(" ",$argv);
$GLOBALS["META"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--meta#",implode(" ",$argv))){$GLOBALS["META"]=true;}
if(preg_match("#--progress-activedirectory=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["AD_PROGRESS"]=$re[1];}


include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.http_access_defaults.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if($argv[1]=="--ssl-on"){xstart_ssl_on();exit;}
if($argv[1]=="--ssl"){xstart_ssl();exit;}
if($argv[1]=="--ufdb-on"){ufdb_on();exit;}
if($argv[1]=="--ufdb-off"){ufdb_off();exit;}
if($argv[1]=="--ufdb-off"){ufdb_off();exit;}
if($argv[1]=="--ad-on"){ad_on();exit;}
if($argv[1]=="--caches-on"){caches_on();exit;}
if($argv[1]=="--caches-off"){caches_off();exit;}
if($argv[1]=="--logs-on"){logs_on();exit;}
if($argv[1]=="--basic-on"){basic_on();exit;}
if($argv[1]=="--emergency-on"){emergency_enable();}
//
xstart();



function build_progress($text,$pourc){
	$unix=new unix();
    $unix->framework_progress($pourc,$text,"squid.urgency.disable.progress");

}
function emergency_enable(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUrgency", 1);
    $unix->framework_exec("exec.squid.php --build --force");
}

function logs_on(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LogsWarninStop", 1);
    $unix->framework_exec("exec.squid.global.access.php --logging");

}
function basic_on(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BasicAuthenticatorEmergency", 1);
    $unix->framework_exec("exec.squid.global.access.php --force");
}

function xstart_ssl_on(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSSLUrgency", 1);
    $unix->framework_exec("exec.squid.global.access.php --force");
}

function ufdb_on(){
	$unix=new unix();
	build_progress("Stamp emerency to on",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUFDBUrgency", 1);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency",0755);
	
	
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink(PROGRESS_DIR."/ufdb.rules_toolbox_left.html");
	
	build_progress("{stopping} {webfiltering}",60);
	system("/etc/init.d/ufdb stop");
	
	
	build_progress("{done} {APP_SQUID}",100);

}
function ufdb_off(){
	$unix=new unix();
	$users=new usersMenus();
	build_progress("Stamp emerency to OFF",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUFDBUrgency", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdbGuardDisabledRedirectors", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUFDBUrgency",0755);
	build_progress("{reconfiguring} {please_wait}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.php --build");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink(PROGRESS_DIR."/ufdb.rules_toolbox_left.html");
	
	if($users->CORP_LICENSE){
		build_progress("{recompiling_personal_databases} {please_wait}",40);
		system("$php /usr/share/artica-postfix/exec.squidguard.php --compile-all-categories");
	}
	
	build_progress("{starting} {webfiltering}",60);
	system("/usr/sbin/artica-phpfpm-service -start-ufdb");
	
	build_progress("{build_status}",70);
	system("$php /usr/share/artica-postfix/exec.status.php --all-squid");
	
	build_progress("{done} {APP_SQUID}",100);
	if($GLOBALS["META"]){
		shell_exec("$php /usr/share/artica-postfix/exec.artica-meta-client.php --ping --force");
	}
}
function xstart_ssl(){
	$unix=new unix();
	build_progress("Stamp emergency to off",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSSLUrgency", 0);
	@chmod("/etc/artica-postfix/settings/Daemons/SquidUrgency",0755);
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --ports");	
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink(PROGRESS_DIR."/ufdb.rules_toolbox_left.html");
	build_progress("{done} {APP_SQUID}",100);

}

function caches_on(){
	$unix=new unix();
	build_progress("Stamp emergency to ON",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUrgencyCaches", 1);
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{reconfiguring} {please_wait}",30);
	system("$php /usr/share/artica-postfix/exec.squid.verify.caches.php --emergency");
	build_progress("{done} {APP_SQUID}",100);
}
function caches_off(){
	$unix=new unix();
	build_progress("Stamp emergency to OFF",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUrgencyCaches", 0);
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{reconfiguring} {please_wait}",30);
	system("$php /usr/share/artica-postfix/exec.squid.verify.caches.php --emergency");
	build_progress("{done} {APP_SQUID}",100);
}

function ad_on(){
	$unix=new unix();
	
	$MonitConfig=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidWatchdogMonitConfig");
	$MonitConfig=unserialize(base64_decode($MonitConfig));
	if(!isset($MonitConfig["CHECK_AD"])){$MonitConfig["CHECK_AD"]=1;}
	if(!isset($MonitConfig["CHECK_AD_ACTION"])){$MonitConfig["CHECK_AD_ACTION"]="disable_ad";}
	
	if($MonitConfig["CHECK_AD"]<>1){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryEmergency", 0);
		build_progress("{emergency} Active Directory CHECK_AD {disabled} {done}",100);
		return;
	}
	
	if($MonitConfig["CHECK_AD_ACTION"]<>"disable_ad"){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryEmergency", 0);
		build_progress("{emergency} Active Directory CHECK_AD_ACTION {disabled} {done}",100);
		return;
	}	
	
	
	build_progress("Stamp emergency to ON",20);
	
	$unix=new unix();
	$ServerRunSince=$unix->ServerRunSince();
	if($ServerRunSince<10){
		build_progress("{emergency} Active Directory {failed}",110);
		echo "Uptime since {$ServerRunSince}mn < 10Mn\n";
		squid_admin_mysql(1, "[Active Directory]: Active Directory Emergency request but wait more then 10mn (current $ServerRunSince) [action=notify]", null,__FILE__,__LINE__);
		return;
	}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryEmergency", 1);
	build_progress("{reconfiguring}",30);
	$php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.squid.global.access.php --general --force");
	build_progress("{emergency} Active Directory {done}",100);
}


function xstart(){
	$unix=new unix();
	build_progress("Stamp emergencies to off",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUrgency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUFDBUrgency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidSSLUrgency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LogsWarninStop", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MacToUidUrgency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("ActiveDirectoryEmergency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("BasicAuthenticatorEmergency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SizeQuotasCheckerEmergency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidUrgencyCaches", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMimeEmergency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DynamicACLUrgency", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidHotSpotUrgency", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRNEmergency", 0);

	@chmod("/etc/artica-postfix/settings/Daemons/SquidUrgency",0755);
	@file_put_contents("/etc/artica-postfix/pids/basicauthenticator.helpers.crashing.count", 0);
	@file_put_contents("/etc/artica-postfix/pids/ntlmauthenticator.helpers.crashing.count", 0);
    @file_put_contents("/etc/squid3/non_ntlm.acl","# Disabled by Web console\n");

    $php            = $unix->LOCATE_PHP5_BIN();
    $chown          = $unix->find_program("chown");

    shell_exec("/usr/sbin/artica-phpfpm-service -sslcrtd");
    system("$chown -R squid:squid /home/squid");


    if(!is_file("/etc/squid3/http_access_final.conf")){
        build_progress("{reconfiguring}",25);
        $http_access_defaults=new http_access_defaults();
        $http_access_defaults->http_access_deny_final();
    }

	
	build_progress("{reconfiguring}",30);

	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --nocheck");
	system("$php /usr/share/artica-postfix/exec.squid.php --build --noreload");
	
	build_progress("{reloading} {APP_SQUID}",50);
	squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	if(is_file("/etc/init.d/ntlm-monitor")){system("/etc/init.d/ntlm-monitor restart");}
	
	build_progress("{starting} {webfiltering}",60);
	system("/usr/sbin/artica-phpfpm-service -start-ufdb");
	build_progress("{restarting} Status service",70);
	system("/etc/init.d/artica-status restart");
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	@unlink(PROGRESS_DIR."/ufdb.rules_toolbox_left.html");
	build_progress("{done} {APP_SQUID}",100);

}