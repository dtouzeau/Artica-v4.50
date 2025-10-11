<?php
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["EBTABLES"]=false;
$GLOBALS["OUTPUT"]=true;
$GLOBALS["PROGRESS"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');


if($argv[1]=="--table-proxy"){GetTableProxy();exit;}
if($argv[1]=="--iptables-delete"){iptables_delete_all();exit;}
if($argv[1]=="--iptables"){
		script_startfile();
		system("/etc/init.d/tproxy start");
		exit;
}

script_startfile();

function script_startfile(){
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["echobin"]=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$sh=array();

	$SquidWCCPEnabled=$sock->GET_INFO("SquidWCCPEnabled");
	$hasProxyTransparent=$sock->GET_INFO("hasProxyTransparent");

	if(!is_numeric($SquidWCCPEnabled)){$SquidWCCPEnabled=0;}
	if(!is_numeric($hasProxyTransparent)){$hasProxyTransparent=0;}




	$sh[]="#!/bin/sh -e";
	$sh[]="### BEGIN INIT INFO";
	$sh[]="# Provides:          tproxy";
	$sh[]="# Required-Start:    \$local_fs";
	$sh[]="# Required-Stop:     \$local_fs";
	$sh[]="# Should-Start:		";
	$sh[]="# Should-Stop:		";
	$sh[]="# Default-Start:     3 4 5";
	$sh[]="# Default-Stop:      0 6";
	$sh[]="# Short-Description: start and stop the tproxy";
	$sh[]="# Description:       Artica tproxy service Raise transparent proxy";
	$sh[]="### END INIT INFO";
	$sh[]="case \"\$1\" in";
	$sh[]="start)";
	$sh[]="{$GLOBALS["echobin"]} \"TProxy: Removing Iptables rules\"";
	$sh[]="{$GLOBALS["echobin"]} \"TProxy: hasProxyTransparent key ($hasProxyTransparent)...\"";
	$sh[]="{$GLOBALS["echobin"]} \"TProxy: SquidWCCPEnabled key ($SquidWCCPEnabled)...\"";
	$sh[]=script_tproxy();
	$sh[]=script_endfile();
	@file_put_contents("/etc/init.d/tproxy", @implode("\n", $sh));
	@chmod("/etc/init.d/tproxy",0755);
	build_progress("{installing_default_script}...",40);
	script_install();
	build_progress("{installing_default_script}...{done}",50);
	
}


function GetTableProxy(){
	$FOUND=false;
	$f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
	foreach ( $f as $index=>$line ){
		if(!preg_match("#([0-9]+)\s+proxy#", $line)){continue;}
		
	}
	
	if(!$FOUND){
		$f[]="299\tproxy\n";
		@file_put_contents("/etc/iproute2/rt_tables", @implode("\n", $f));
	}
	
}


function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.transparent.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

}

function script_install(){


	@chmod("/etc/init.d/tproxy",0755);
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f tproxy defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add tproxy >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 tproxy on >/dev/null 2>&1");
	}

}

// bridge mode: http://www.toyaseta.com/squid-tproxy-bridge-centos-6-3.html

function script_tproxy(){
	$unix=new unix();
	$ip=$unix->find_program("ip");
	$sock=new sockets();
	$squid=new squidbee();
	$SSL_BUMP=$squid->SSL_BUMP;
	$ssl_port=$squid->get_ssl_port();
	$php=$unix->LOCATE_PHP5_BIN();
	$SquidTProxyInterface=$sock->GET_INFO("SquidTProxyInterface");
	$MARKLOG="-m comment --comment \"ArticaSquidTransparent\"";
	$echo=$unix->find_program("echo");
	$iptables=$unix->find_program("iptables");
	$modprobe=$unix->find_program("modprobe");
	$sh[]="$modprobe xt_TPROXY || true";
	$sh[]="$modprobe xt_socket || true";
	$sh[]="$modprobe xt_mark || true";
	$sh[]="$modprobe nf_nat || true";
	$sh[]="$modprobe nf_conntrack_ipv4 || true";
	$sh[]="$modprobe nf_conntrack || true";
	$sh[]="$modprobe nf_defrag_ipv4 || true";
	$sh[]="$modprobe ipt_REDIRECT || true";
	$sh[]="$modprobe iptable_nat || true";
	$sh[]="$echo \"Squid TProxy mode: Check routing table 'Proxy'\"";
	$sh[]="$php ".__FILE__." --table-proxy || true";
	$sh[]="$ip route del 127.0.0.1 dev lo  || true";
	$sh[]="$ip route del local 127.0.0.0/24 dev lo  table local || true";
	$sh[]="$ip route del local 127.0.0.0/8 del lo table local || true";
	$sh[]="$ip -f inet rule add fwmark 1 lookup proxy || true";
	$sh[]="$ip -f inet route add local default dev lo table proxy || true";
	$sh[]="/usr/sbin/artica-phpfpm-service -firewall-tune";
	$sh[]="$iptables -t mangle -N DIVERT $MARKLOG || true";
	$sh[]="$iptables -t mangle -A DIVERT -j MARK --set-mark 1 $MARKLOG || true";
	$sh[]="$iptables -t mangle -A DIVERT -j ACCEPT $MARKLOG || true";
	$sh[]="$iptables  -t mangle -A PREROUTING -p tcp -m socket -j DIVERT $MARKLOG || true";
	$sh[]="$echo \"Squid TProxy mode: enabled in transparent mode in $squid->listen_port Port (SSL_BUMP=$SSL_BUMP) SSL PORT:$ssl_port\"";
	$sh[]="$iptables  -t mangle -A PREROUTING -p tcp --dport 80 -j TPROXY --tproxy-mark 0x1/0x1 --on-port $squid->listen_port $MARKLOG || true";
	if($SSL_BUMP==1){
		$sh[]="$iptables  -t mangle -A PREROUTING -p tcp --dport 443 -j TPROXY --tproxy-mark 0x1/0x1 --on-port $ssl_port $MARKLOG || true";
	}
	return @implode("\n", $sh);
	
}

function iptables_delete_all(){
	$unix=new unix();
	$sock=new sockets();
	$iptables_save=$unix->find_program("iptables-save");
	$iptables_restore=$unix->find_program("iptables-restore");
	system("$iptables_save > /etc/artica-postfix/iptables.conf");
	$ip=$unix->find_program("ip");
	$data=file_get_contents("/etc/artica-postfix/iptables.conf");
	$datas=explode("\n",$data);
	$pattern="#.+?ArticaSquidTransparent#";
	$SquidTProxyInterface=$sock->GET_INFO("SquidTProxyInterface");
	$d=0;
	foreach ($datas as $num=>$ligne){
		if($ligne==null){continue;}
		if(preg_match($pattern,$ligne)){$d++;continue;}
		$conf=$conf . $ligne."\n";
	}
	file_put_contents("/etc/artica-postfix/iptables.new.conf",$conf);
	system("$iptables_restore < /etc/artica-postfix/iptables.new.conf");
	
	shell_exec("$ip -f inet rule del fwmark 1 lookup 100 >/dev/null 2>&1");
	shell_exec("$ip -f inet route del local default dev lo table 100  >/dev/null 2>&1");
	
	
	echo "Starting......: ".date("H:i:s")." Squid Check Transparent mode: removing $d iptables rule(s) done...\n";
}


function script_endfile(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$MikrotikTransparent=intval($sock->GET_INFO('MikrotikTransparent'));
	$echo=$unix->find_program("echo");
	$SquidTProxyInterface=$sock->GET_INFO("SquidTProxyInterface");
	$ip=$unix->find_program("ip");
	
	$sh[]="$echo \"Transparent proxy method TProxy done.\"";
	$sh[]=";;";
	$sh[]="  stop)";
	$sh[]="$echo \"Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables-delete >/dev/null 2>&1";
	$sh[]="$echo \"Removing routing rules on $SquidTProxyInterface\"";
	$sh[]="$ip -f inet rule del fwmark 1 lookup 100 || true";
	$sh[]="$ip -f inet route del local default dev $SquidTProxyInterface table 100 || true";
	$sh[]=";;";
	$sh[]="  reconfigure)";
	$sh[]="$echo \"TProxy: Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables-delete >/dev/null 2>&1";
	$sh[]="$echo \"TProxy: Building Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables >/dev/null 2>&1";
	$sh[]="$echo \"TProxy: Starting builded script\"";
	$sh[]="/etc/init.d/tproxy start";
	$sh[]=";;";

	$sh[]="  restart)";
	$sh[]="$echo \"TProxy: Removing Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables-delete >/dev/null 2>&1";
	$sh[]="$echo \"TProxy: Building Iptables rules\"";
	$sh[]=$php." ".__FILE__." --iptables >/dev/null 2>&1";
	$sh[]="$echo \"TProxy: Starting builded script\"";
	$sh[]="/etc/init.d/tproxy start";
	$sh[]="$echo \"TProxy: Restarting Iptables rules success\"";
	$sh[]=";;";


	$sh[]="*)";
	$sh[]=" echo \"Usage: $0 {start ,restart,configure or stop only}\"";
	$sh[]="exit 1";
	$sh[]=";;";
	$sh[]="esac";
	$sh[]="exit 0\n";
	return @implode("\n", $sh);


}