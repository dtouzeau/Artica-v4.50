<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.acls.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
$GLOBALS["VERBOSE"]=false;$GLOBALS["BYCRON"]=false;$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

try{Zexec();}catch (Exception $e) {echo "fatal error:".  $e->getMessage()."\n";}
		

function Zexec(){
	$sock=new sockets();
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	if(!$GLOBALS["FORCE"]){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid)){
			$timeTTL=$unix->PROCCESS_TIME_MIN($pid);
			squid_admin_mysql(2, "Already running PID $pid since {$timeTTL}Mn", __FUNCTION__, __FILE__, __LINE__, "antivirus");
			return;
		}
		$rm=$unix->find_program("rm");
		$timepid=$unix->file_time_min($pidfile);
		if($timepid<120){
			squid_admin_mysql(2, "Require 120Mn minimal (current: $timepid)", __FUNCTION__, __FILE__, __LINE__, "antivirus");
			return;
		}
	}
	@unlink($pidfile);
	@file_put_contents($pidfile, getmypid());
	$curlcmdline=null;
	$puser=null;
	$ini=new Bs_IniHandler();
	$datas=$sock->GET_INFO("ArticaProxySettings");
	if(trim($datas)<>null){
		$ini->loadString($datas);
			$ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
			$ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
			$ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
			$ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
			$ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
			if($ArticaProxyServerEnabled==1){$ArticaProxyServerEnabled="yes";}if($ArticaProxyServerEnabled=="yes"){
				if($ArticaProxyServerUserPassword<>null){$puser=" --proxy-user $ArticaProxyServerUsername:$ArticaProxyServerUserPassword";
				}
				
				$curlcmdline=" --proxy $ArticaProxyServerName:$ArticaProxyServerPort$puser";
			}
		}
	


	$f[]="# This is the /etc/scamp/default file.";
	$f[]="# Created ".date('F')." ".date('d').", ".date('Y')." @ ".date('H:i:s')."";
	$f[]="";
	$f[]="SCAMP_VERSION=5.3b";
	$f[]="CLAMAV_DB=/var/lib/clamav";
	$f[]="T_DIR=/var/lib/clamav/tmp";
	$f[]="C_GROUP=clamav";
	$f[]="C_PID=/var/run/clamav/clamd.pid";
	$f[]="C_USER=clamav";
	$f[]="GET_LDB=1";
	$f[]="GET_MALWARE=1";
	$f[]="GET_MSRBL=0";
	$f[]="GET_SANE=1";
	$f[]="GET_SECURITE=4";
	$f[]="GET_WINNOW=1";
	$f[]="gpg_key_url=http://www.sanesecurity.net/publickey.gpg";
	$f[]="L_TYPE=0";
	$f[]="MK_LOG=1";
	$f[]="MSRBL=rsync://rsync.mirror.msrbl.com/msrbl/";
	$f[]="msrbl_Images=MSRBL-Images.hdb";
	$f[]="msrbl_SPAM=MSRBL-SPAM.ndb";
	$f[]="msrbl_SPAM_CR=MSRBL-SPAM-CR.ndb";
	$f[]="MSR_DIR=/var/lib/clamav/tmp/msr";
	$f[]="MW_DIR=/var/lib/clamav/tmp/malware";
	$f[]="MW_FILE=mbl.ndb";
	$f[]="MW_URL=http://www.malwarepatrol.com.br/cgi/submit?action=list_clamav_ext";
	$f[]="RELOAD=0";
	$f[]="REST=1";
	$f[]="SANE=rsync://rsync.sanesecurity.net/sanesecurity";
	$f[]="SANE_DB=/var/lib/clamav/tmp/sane";
	$f[]="SI_DIR=/var/lib/clamav/tmp/securite";
	$f[]="SYS_LOG=1";
	$f[]="WPC=3";
	$f[]="W_SUM=0";
	if(!is_dir("/etc/scamp")){@mkdir("/etc/scamp",0755,true);}
	if(!is_dir("/var/lib/clamav")){@mkdir("/var/lib/clamav",0755,true);}
	@file_put_contents("/etc/scamp/default", @implode("\n", $f));
	$t=time();
	
	$l=explode("\n",@file_get_contents("/usr/share/artica-postfix/bin/scamp.sh"));
	while (list ($num, $line) = each ($l) ){
		if(preg_match("#^CURL_PROXY_CMD#", $line)){
			$l[$num]="CURL_PROXY_CMD=\"$curlcmdline\"";
		}
		
	}
	@file_put_contents("/usr/share/artica-postfix/bin/scamp.sh", @implode("\n", $l));
	
	exec("/usr/share/artica-postfix/bin/scamp.sh -L -q -R");
	$took=$unix->distanceOfTimeInWords($t,time());
	$content=@file_get_contents("/var/log/scamp.log");
	squid_admin_mysql(2, "Update done, {took} $took\n$content", __FUNCTION__, __FILE__, __LINE__, "antivirus");
	@unlink("/var/log/scamp.log");
	if(is_file("/var/run/c-icap/c-icap.ctl")){
        $unix->CICAP_SERVICE_EVENTS("Reloading ICAP Server pattern databases", __FILE__,__LINE__);
		shell_exec("echo -n \"srv_clamav:dbreload\" > /var/run/c-icap/c-icap.ctl");
		shell_exec("echo -n \"virus_scan:dbreload\" > /var/run/c-icap/c-icap.ctl");
	}
	
}