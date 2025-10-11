<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["FORCE"]=false;
$GLOBALS["EXECUTED_AS_ROOT"]=true;
$GLOBALS["RUN_AS_DAEMON"]=false;
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["DISABLE_WATCHDOG"]=false;
$GLOBALS["COMMANDLINE"]=implode(" ",$argv);
if(strpos($GLOBALS["COMMANDLINE"],"--verbose")>0){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;$GLOBALS["DEBUG"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--nowachdog#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DISABLE_WATCHDOG"]=true;}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
$GLOBALS["BYPASS"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/whois/whois.main.php');
include_once(dirname(__FILE__)."/ressources/class.main_cf.inc");


SwapBoot();

function SwapBoot(){
	$reboot=false;
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]=new sockets();
	$GLOBALS["CLASS_UNIX"]=new unix();
	$ps=$unix->find_program("ps");
	$SwapOffOn=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SwapOffOn")));
	$filecache="/etc/artica-postfix/cron.1/SwapOffOn.time";
	if(!is_numeric($SwapOffOn["SwapEnabled"])){$SwapOffOn["SwapEnabled"]=1;}
	if(!is_numeric($SwapOffOn["SwapMaxPourc"])){$SwapOffOn["SwapMaxPourc"]=20;}
	if(!is_numeric($SwapOffOn["SwapMaxMB"])){$SwapOffOn["SwapMaxMB"]=0;}
	if(!is_numeric($SwapOffOn["SwapTimeOut"])){$SwapOffOn["SwapTimeOut"]=60;}
	if($SwapOffOn["SwapEnabled"]==0){squid_admin_mysql(2, "SwapEnabled is disabled, operation aborted", __FUNCTION__, __FILE__, __LINE__, "system");return;}
	
	
	$sys=new systeminfos();
	
	$pourc=round(($sys->swap_used/$sys->swap_total)*100);
	squid_admin_mysql(2, "$sys->swap_used/$sys->swap_total {$sys->swap_used}MB used ($pourc%)", __FUNCTION__, __FILE__, __LINE__, "system");
	if($SwapOffOn["SwapMaxMB"]>0){
		if($sys->swap_used>$SwapOffOn["SwapMaxMB"]){
			$execeed_text=$SwapOffOn["SwapMaxMB"]."MB";
			$reboot=true;
		}
	}
	if(!$reboot){
		if($pourc>1){
			if($SwapOffOn["SwapMaxPourc"]>1){
				if($pourc>$SwapOffOn["SwapMaxPourc"]){
					$execeed_text=$SwapOffOn["SwapMaxPourc"]."%";
					$reboot=true;
				}
			}
		}
	}

	if(!$reboot){return;}

	squid_admin_mysql(2, "Swap exceed rules: $execeed_text reboot operation will be executed in 30s\n",ps_report(), __FILE__, __LINE__);
	sleep(30);
    shell_exec("/usr/sbin/artica-phpfpm-service -reboot");
}