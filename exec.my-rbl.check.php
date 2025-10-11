<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.maincf.multi.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');

if(system_is_overloaded(__FILE__)){
	die();
}

if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=='--myip'){GetMyIp(true);exit();}
if($argv[1]=='--checks'){CheckCMDLine();exit();}
if($argv[1]=='--verif'){Checks();exit();}
if($argv[1]=='--query'){ChecksDNSBL($argv[2],true);exit();}
if($argv[1]=='--noip'){NoIp();exit();}
if($argv[1]=="--pear"){InstallPear();}


function CheckCMDLine(){
	
	$unix=new unix();
	$PID_FILE="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=$unix->get_pid_from_file($PID_FILE);
	if($unix->process_exists($pid)){return;}
	@file_put_contents($PID_FILE, getmypid());
	
	if(system_is_overloaded()){exit();}
	$sock=new sockets();
	$ips=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RBLCheckIPList")));
	
	if(count($ips)>0){
		if($GLOBALS["VERBOSE"]){echo count($ips). " elements to check\n";}
		if(is_array($ips)){
			while (list ($num, $ip) = each ($ips) ){
				if($GLOBALS["VERBOSE"]){echo "$ip element...\n";}
				ChecksDNSBL($ip,false,true);
			}
			ChecksDNSBL();
			return;
		}
		
	}
	ChecksDNSBL();
}


function PearVersion(){
	$unix=new unix();
	$pear_bin=$unix->find_program("pear");
	exec("$pear_bin -V 2>&1",$results);

	foreach ($results as $index=>$line){
		if(preg_match("#PEAR Version:\s+([0-9\.]+)#", $line,$re)){
			return $re[1];
		}
	}
	
	
}
	
function InstallPear(){
	$unix=new unix();
	$pear_bin=$unix->find_program("pear");
	if($pear_bin==null){return;}
	$PearVersion=PearVersion();
	if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)#", $PearVersion,$re)){
		$MAJOR=intval($re[1]);
		$MINOR=intval($re[2]);
		$REV=intval($re[3]);
	}
	
	$UPGRADE=TRUE;
	echo "Pear ($PearVersion) : Major:$MAJOR, minor:$MINOR, rev:$REV\n";
	if($MAJOR>0){
		if($MINOR>8){
			if($REV>1){
				echo "Pear ($PearVersion) OK, no need to upgrade\n";
				$UPGRADE=false;
			}
		}
	}
	if($UPGRADE){
		shell_exec("$pear_bin install PEAR-1.9.2");	
	}
	
		if($GLOBALS["VERBOSE"]){echo "$pear_bin install Net_DNSBL\n";}
		if(!is_dir("/usr/share/php/doc")){shell_exec("/bin/rm -f /usr/share/php/doc");}
		shell_exec("$pear_bin install Net_DNSBL");	
	
	
}

function Checks(){
	include_once("HTTP/Request.php");
	include_once('Net/DNSBL.php');
	
	if(!class_exists("Net_DNSBL")){
		InstallPear();
		
	}else{
		if($GLOBALS["VERBOSE"]){echo "Net_DNSBL OK\n";}
	}
	
	if(!class_exists("HTTP_Request")){
		InstallPear();
	}else{
		if($GLOBALS["VERBOSE"]){echo "HTTP_Request OK\n";}
	}	
}


function GetMyIp($aspid=false){
	$NoInternetAccess=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoInternetAccess"));
	if($NoInternetAccess==1){return true;}
	$sock=new sockets();
	$unix=new unix();
	$EnableArticaMetaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaMetaClient"));
	if($EnableArticaMetaClient==1){return;}
	
		
	if($aspid){
		$pidFile="/etc/artica-postfix/pids/GetMyIp.pid";
		$pid=$unix->get_pid_from_file($pidFile);
		if($unix->process_exists($pid)){return;}
		@file_put_contents($pidFile, getmypid());
	}
	
	if($sock->GET_INFO("DoNotResolvInternetIP")==1){
		$ip=$sock->GET_INFO("PublicIPAddress");
		if($ip<>null){return $ip;}
	}
	
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min(PROGRESS_DIR."/myIP.conf");
		if($time<60){return trim(@file_get_contents(PROGRESS_DIR."/myIP.conf"));}
	
	}
	
	
	
	
	$URIBASE=$unix->MAIN_URI();	
	@unlink(PROGRESS_DIR."/myIP.conf");
	$curl=new ccurl("$URIBASE/my-ip.php");
	$curl->NoHTTP_POST=true;
	
	if(!$curl->get()){

		if($curl->CURLINFO_HTTP_CODE==503){
			$unix->ToSyslog("HTTP error N° $curl->CURLINFO_HTTP_CODE ($curl->error)",true,"RBL-CHECK");
			return;
		}
		$unix->ToSyslog("HTTP error N° $curl->CURLINFO_HTTP_CODE ($curl->error)",true,"RBL-CHECK");
		return;
	}
	$datas=explode("\n", $curl->data);
	$myip=null;
	writelogs("http://www.articatech.net/my-ip.php -> ($curl->data)",__FUNCTION__,__FILE__,__LINE__);

	foreach ($datas as $num=>$val){
		if(preg_match("#^(.*?):#",$val,$re)){continue;}
		if(preg_match("#([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)#",$val,$re)){
			$myip="{$re[1]}.{$re[2]}.{$re[3]}.{$re[4]}";
			break;
		
		}
	}
	
	$oldPublicIPAddress=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PublicIPAddress");
	
	
	
	if($myip==null){
		$unix->ToSyslog("!!! Unable to preg_match datas....",true,"RBL-CHECK");
		return;
	}

	@file_put_contents(PROGRESS_DIR."/myIP.conf",$myip);
	@chmod(PROGRESS_DIR."/myIP.conf",775);
	if($oldPublicIPAddress<>$myip){squid_admin_mysql(2, "Public IP Address changed to: $myip",__FUNCTION__,__FILE__,__LINE__,"system");}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PublicIPAddress",$myip);
	
	
	if($aspid){NoIp();}
}

function NoIp(){
	$sock=new sockets();
	$EnableNoIpService=$sock->GET_INFO("EnableNoIpService");
	if(!is_numeric($EnableNoIpService)){$EnableNoIpService=0;}
	if($EnableNoIpService==0){return;}
	$Config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NoipConf")));
	$curl=new ccurl("http://dynupdate.no-ip.com/nic/update?hostname={$Config["NoIPHostname"]}");
	$curl->authname=$Config["NoIPUsername"];
	$curl->authpass=$Config["NoIPPassword"];
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		squid_admin_mysql(2, "Fatal $curl->error !!",__FUNCTION__,__FILE__,__LINE__,"system");
		return;
	}
	$results=explode("\n",$curl->data);
	
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		
		if(preg_match("#good\s+([0-9\.]+)#", $line,$re)){
			squid_admin_mysql(2, "OK For {$re[1]}",__FUNCTION__,__FILE__,__LINE__,"system");
			writelogs("OK for {$re[1]}",__FUNCTION__,__FILE__);
			$myip=$re[1];
			break;
		}
		if(preg_match("#nochg\s+([0-9\.]+)#", $line,$re)){
			writelogs("OK no changes {$re[1]}",__FUNCTION__,__FILE__);
			$myip=$re[1];
			break;
		}
		if(preg_match("#nochg\s+([0-9\.]+)#", $line,$re)){
			writelogs("OK no changes {$re[1]}",__FUNCTION__,__FILE__);
			$myip=$re[1];
			break;
		}				
		
		if(preg_match("#abuse\s+(.+)#", $line,$re)){
			writelogs("Fatal: Abuse!!!! {$re[1]}",__FUNCTION__,__FILE__);
			break;
		}		
		
	}
	if($myip<>null){
		@file_put_contents(PROGRESS_DIR."/myIP.conf",$myip);
		@chmod(PROGRESS_DIR."/myIP.conf",775);
		$sock->SET_INFO("PublicIPAddress",$myip);
	}	
	
}


function ChecksDNSBL($iptocheck=null,$output=false,$increment=false){
	
	include_once("HTTP/Request.php");
	include_once('Net/DNSBL.php');
	
	if(!class_exists("Net_DNSBL")){
		InstallPear();
	}else{
		if($GLOBALS["VERBOSE"]){echo "Net_DNSBL OK\n";}
	}	
	
	
	if(trim($iptocheck=="--force")){$iptocheck=null;$output=false;}
	$textip=null;
	if($iptocheck==null){$myip=GetMyIp();}else{$myip=$iptocheck;}
	if(!preg_match("#[0-9+]\.[0-9+]\.[0-9+]\.[0-9+]#",$myip)){
		$textip=" ($myip) ";
		$myip=gethostbyname($myip);
		if($GLOBALS["VERBOSE"]){
			echo "Checking $myip...........: was$textip\n";
		}
	}
	$sock=new sockets();
	$unix=new unix();
	$RBLCheckFrequency=$sock->GET_INFO("RBLCheckFrequency");
	$RBLCheckNotification=$sock->GET_INFO("RBLCheckNotification");
	if(!is_numeric($RBLCheckFrequency)){$RBLCheckFrequency=60;}	
	if(!is_numeric($RBLCheckNotification)){$RBLCheckNotification=0;}
	
	if($GLOBALS["VERBOSE"]){
		echo "Checking $myip$textip...........: RBLCheckFrequency...: $RBLCheckFrequency\n";
		echo "Checking $myip$textip...........: RBLCheckNotification: $RBLCheckNotification\n";
	}
	
	if(!$GLOBALS["FORCE"]){
		$md=md5($myip);
		$timefile="/etc/artica-postfix/cron.1/ChecksDNSBL.$md.time";
		if(!$GLOBALS["VERBOSE"]){
			$time=file_time_min($timefile);
			if($time<$RBLCheckFrequency){
				echo @file_get_contents($timefile);
				return;
			}	
		}
		@unlink($timefile);
		@file_put_contents($timefile,time());
	}
	include_once('Net/DNSBL.php');
	$dnsbl = new Net_DNSBL();
	
	if(!isset($GLOBALS["DDNS"])){
		$sql="SELECT * FROM rbl_servers WHERE enabled=1 ORDER BY `rbl`";
		$q=new mysql();
		$results=$q->QUERY_SQL($sql,"artica_backup");
		if($q->ok){
			while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
			$GLOBALS["DDNS"][]=$ligne["rbl"];
			}
		}
	}
	
	if(count($GLOBALS["DDNS"])==0){
		$GLOBALS["DDNS"][]="b.barracudacentral.org";
		$GLOBALS["DDNS"][]="bl.deadbeef.com";
		$GLOBALS["DDNS"][]="bl.emailbasura.org";
		$GLOBALS["DDNS"][]="bl.spamcannibal.org";
		$GLOBALS["DDNS"][]="bl.spamcop.net";
		//$dnss[]="blackholes.five-ten-sg.com";
		$GLOBALS["DDNS"][]="blacklist.woody.ch";
		$GLOBALS["DDNS"][]="bogons.cymru.com";
		$GLOBALS["DDNS"][]="cbl.abuseat.org";
		$GLOBALS["DDNS"][]="cdl.anti-spam.org.cn";
		$GLOBALS["DDNS"][]="combined.abuse.ch";
		$GLOBALS["DDNS"][]="combined.rbl.msrbl.net";
		$GLOBALS["DDNS"][]="db.wpbl.info";
		$GLOBALS["DDNS"][]="dnsbl-1.uceprotect.net";
		$GLOBALS["DDNS"][]="dnsbl-2.uceprotect.net";
		$GLOBALS["DDNS"][]="dnsbl-3.uceprotect.net";
		
		$GLOBALS["DDNS"][]="dnsbl.cyberlogic.net";
		$GLOBALS["DDNS"][]="dnsbl.inps.de";
		$GLOBALS["DDNS"][]="dnsbl.njabl.org";
		$GLOBALS["DDNS"][]="dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="drone.abuse.ch";
		$GLOBALS["DDNS"][]="duinv.aupads.org";
		$GLOBALS["DDNS"][]="dul.dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="dul.ru";
		$GLOBALS["DDNS"][]="dyna.spamrats.com";
		$GLOBALS["DDNS"][]="dynip.rothen.com";
		$GLOBALS["DDNS"][]="fl.chickenboner.biz";
		$GLOBALS["DDNS"][]="http.dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="images.rbl.msrbl.net";
		$GLOBALS["DDNS"][]="ips.backscatterer.org";
		$GLOBALS["DDNS"][]="ix.dnsbl.manitu.net";
		$GLOBALS["DDNS"][]="korea.services.net";
		$GLOBALS["DDNS"][]="misc.dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="noptr.spamrats.com";
		$GLOBALS["DDNS"][]="ohps.dnsbl.net.au";
		$GLOBALS["DDNS"][]="omrs.dnsbl.net.au";
		$GLOBALS["DDNS"][]="orvedb.aupads.org";
		$GLOBALS["DDNS"][]="osps.dnsbl.net.au";
		$GLOBALS["DDNS"][]="osrs.dnsbl.net.au";
		$GLOBALS["DDNS"][]="owfs.dnsbl.net.au";
		$GLOBALS["DDNS"][]="owps.dnsbl.net.au";
		$GLOBALS["DDNS"][]="pbl.spamhaus.org";
		$GLOBALS["DDNS"][]="phishing.rbl.msrbl.net";
		$GLOBALS["DDNS"][]="probes.dnsbl.net.au";
		$GLOBALS["DDNS"][]="proxy.bl.gweep.ca";
		$GLOBALS["DDNS"][]="proxy.block.transip.nl";
		$GLOBALS["DDNS"][]="psbl.surriel.com";
		$GLOBALS["DDNS"][]="rbl.interserver.net";
		$GLOBALS["DDNS"][]="rdts.dnsbl.net.au";
		$GLOBALS["DDNS"][]="relays.bl.gweep.ca";
		$GLOBALS["DDNS"][]="relays.bl.kundenserver.de";
		$GLOBALS["DDNS"][]="relays.nether.net";
		$GLOBALS["DDNS"][]="residential.block.transip.nl";
		$GLOBALS["DDNS"][]="ricn.dnsbl.net.au";
		$GLOBALS["DDNS"][]="rmst.dnsbl.net.au";
		$GLOBALS["DDNS"][]="sbl.spamhaus.org";
		$GLOBALS["DDNS"][]="short.rbl.jp";
		$GLOBALS["DDNS"][]="smtp.dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="socks.dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="spam.abuse.ch";
		$GLOBALS["DDNS"][]="spam.dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="spam.rbl.msrbl.net";
		$GLOBALS["DDNS"][]="spam.spamrats.com";
		$GLOBALS["DDNS"][]="spamlist.or.kr";
		$GLOBALS["DDNS"][]="spamrbl.imp.ch";
		$GLOBALS["DDNS"][]="t3direct.dnsbl.net.au";
		
		$GLOBALS["DDNS"][]="tor.dnsbl.sectoor.de";
		$GLOBALS["DDNS"][]="torserver.tor.dnsbl.sectoor.de";
		$GLOBALS["DDNS"][]="ubl.lashback.com";
		$GLOBALS["DDNS"][]="ubl.unsubscore.com";
		$GLOBALS["DDNS"][]="virbl.bit.nl";
		$GLOBALS["DDNS"][]="virus.rbl.jp";
		$GLOBALS["DDNS"][]="virus.rbl.msrbl.net";
		$GLOBALS["DDNS"][]="web.dnsbl.sorbs.net";
		$GLOBALS["DDNS"][]="wormrbl.imp.ch";
		$GLOBALS["DDNS"][]="xbl.spamhaus.org";
		$GLOBALS["DDNS"][]="zen.spamhaus.org";
		$GLOBALS["DDNS"][]="zombie.dnsbl.sorbs.net";	
	}
	
	
	if($GLOBALS["VERBOSE"]){
		echo "Checking $myip$textip...........: checking............: ". count($GLOBALS["DDNS"]) ." rbls servers\n";
		echo "Checking $myip$textip...........: Output..............: $output\n";
	}	
	
	if($GLOBALS["VERBOSE"]){echo "checking ". count($GLOBALS["DDNS"]) ." rbls servers\n";}
	if($GLOBALS["VERBOSE"]){echo "Checking $myip...........: ->setBlacklists();\n";}
	reset($GLOBALS["DDNS"]);
	$dnsbl->setBlacklists($GLOBALS["DDNS"]);
	if(!$output){
		if(!$increment){
			if($GLOBALS["VERBOSE"]){echo "Delete /usr/share/artica-postfix/ressources/logs/web/blacklisted.html\n";}
			@unlink(PROGRESS_DIR."/blacklisted.html");
		}
	}

	if($output){
		if ($dnsbl->isListed($myip)) {
		$blacklist=$dnsbl->getListingBl($myip);
		$detail = $dnsbl->getDetails($myip); 
		$final="$blacklist;{$detail["txt"][0]}";
		@file_put_contents($timefile,time());
		echo $final;
		}
	
	return;
	}

	$date=date('l F H:i');
	if($GLOBALS["VERBOSE"]){echo "Checking $myip$textip...........: Output..............: $date\n";}
	if(!$increment){
		@unlink(PROGRESS_DIR."/blacklisted.html");
		@unlink(PROGRESS_DIR."/Notblacklisted.html");
	}
	
if ($dnsbl->isListed($myip)) {
   $blacklist=$dnsbl->getListingBl($myip);
   if($RBLCheckNotification==1){
   	$unix->send_email_events("Your server ($myip$textip) is blacklisted from $blacklist","This is the result of checking your server from " .count($GLOBALS["DDNS"])." black list servers.
   It seems your server (ip:$myip$textip) is blacklisted from $blacklist
   If you trying to send mails from this server, it should be rejected from many SMTP servers that use \"$blacklist\" for check senders IP addresses.
   ","postfix");
   }
   echo "$myip: blacklisted from $blacklist write ".PROGRESS_DIR."/blacklisted.html\"\n";
   $p=Paragraphe('danger64.png',"{WARN_BLACKLISTED}","$myip$textip {IS_BLACKLISTED_FROM} $blacklist ($date)","javascript:Loadjs('system.rbl.check.php')","$myip {IS_BLACKLISTED_FROM} $blacklist",300,80);
   if($increment){$p=@file_get_contents(PROGRESS_DIR."/blacklisted.html").$p;}
   @file_put_contents(PROGRESS_DIR."/blacklisted.html",$p);
   shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/web/blacklisted.html >/dev/null 2>&1");
   return;
}else{
	if($GLOBALS["VERBOSE"]){echo "checking ". count($GLOBALS["DDNS"]) ." rbls servers success\n";}
}

$dnsbl = new Net_DNSBL();
reset($GLOBALS["DDNS"]);
$dnsbl->setBlacklists($GLOBALS["DDNS"]);
if ($dnsbl->isListed($myip)) {
   $blacklist=$dnsbl->getListingBl($myip);
   if($RBLCheckNotification==1){
	   send_email_events("Your server ($myip$textip) is blacklisted from $blacklist","This is the result of checking your server from " .count($GLOBALS["DDNS"])." black list servers.
	   It seems your server (ip:$myip$textip) is blacklisted from $blacklist
	   If you trying to send mails from this server, it should be rejected from many SMTP servers that use \"$blacklist\" for check senders IP addresses.
	   ","postfix");
   }

   echo "$myip$textip: blacklisted from $blacklist write ".PROGRESS_DIR."/blacklisted.html\"\n";
   $p=Paragraphe('danger64.png',"{WARN_BLACKLISTED}","$myip$textip {IS_BLACKLISTED_FROM} $blacklist ($date)","javascript:Loadjs('system.rbl.check.php')","$myip$textip {IS_BLACKLISTED_FROM} $blacklist",300,80);
   if($increment){$p=@file_get_contents(PROGRESS_DIR."/blacklisted.html").$p;}
   @file_put_contents(PROGRESS_DIR."/blacklisted.html",$p);
   shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/web/blacklisted.html >/dev/null 2>&1");
   return;
}else{
	if($GLOBALS["VERBOSE"]){echo "checking ". count($GLOBALS["DDNS"]) ." rbls servers success\n";}
}
$p=Paragraphe('ok64.png',"{NOT_BLACKLISTED}","$myip$textip {IS_NOT_BLACKLISTED } ($date)","javascript:Loadjs('system.rbl.check.php')",null,300,80);
if($increment){$p=@file_get_contents(PROGRESS_DIR."/Notblacklisted.html").$p;}
@file_put_contents(PROGRESS_DIR."/Notblacklisted.html",$p);
shell_exec("/bin/chmod 777 /usr/share/artica-postfix/ressources/logs/web/Notblacklisted.html >/dev/null 2>&1");



}
?>

