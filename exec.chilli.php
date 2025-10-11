#!/usr/bin/php
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["RELOAD"]=false;
$GLOBALS["TITLENAME"]="Chilli";
$GLOBALS["BYCONSOLE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--onetime#",implode(" ",$argv),$re)){$GLOBALS["ONETIME"]=true;}
if(preg_match("#--byconsole#",implode(" ",$argv),$re)){$GLOBALS["BYCONSOLE"]=true;}
$GLOBALS["DNMASQCONF"]=false;
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--start-dnsmasq"){$GLOBALS["OUTPUT"]=true;$GLOBALS["DNMASQCONF"]=true;start_dnsmasq();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RELOAD"]=true;build();exit();}
if($argv[1]=="--web"){$GLOBALS["OUTPUT"]=false;coova_web();exit();}
if($argv[1]=="--init"){$GLOBALS["OUTPUT"]=false;chilli_init_d();exit();}
if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=false;;exit();}
if($argv[1]=="--up"){$GLOBALS["OUTPUT"]=false;up_sh();}
if($argv[1]=="--iptablesx"){$GLOBALS["OUTPUT"]=false;flush_iptables();}




chilli_conup();

function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());	
	stop(true);
	start(true);
}



function start_dnsmasq($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$nohup=$unix->find_program("nohup");
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq\n";}
	if(!is_file("/etc/chilli/sbin/dnsmasq")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq not installed !!\n";}
		return;	
	}
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	
	$pid=PID_NUM_DNSMASQ();
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq Service already started $pid since {$timepid}Mn...\n";}
		return;
	}	
	
	if($GLOBALS["DNMASQCONF"]){
		dnsmasq_config();
	}
	
	$CMD[]="/etc/chilli/sbin/dnsmasq";
	$CMD[]="--pid-file=/var/run/chilli.dnsmasq.pid --no-resolv --user=root";
	$CMD[]="--conf-file=/etc/chilli/dnsmasq.conf";
	
	$cmd="$nohup ".@implode(" ", $CMD)." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq starting service\n";}
	shell_exec($cmd);
	
	for($i=1;$i<6;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM_DNSMASQ();
		if($unix->process_exists($pid)){break;}
	}
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq Success PID $pid\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq $cmd\n";}
	}
	
	
}
function PID_NUM_DNSMASQ(){
	$filename="/var/run/chilli.dnsmasq.pid";
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/etc/chilli/sbin/dnsmasq");
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$chilli=$unix->find_program("chilli");
	
	if(!is_file($chilli)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
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
		start_dnsmasq(true);
		return;
	}	
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}

	if($EnableChilli==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled\n";}
		$pid=PID_NUM();
		if($unix->process_exists($pid)){stop(true);}
		return;
	}	
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	$echo=$unix->find_program("echo");
	$mknod=$unix->find_program("mknod");
	$chilli=$unix->find_program("chilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	$nohup=$unix->find_program("nohup");
	$ifconfig=$unix->find_program("ifconfig");
	$iptables=$unix->find_program("iptables");
	$modprobe=$unix->find_program("modprobe");
	
	
	$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
	$ChilliConf=GetInterfaceArray($ChilliConf);
	$http_port=intval($ChilliConf["SQUID_HTTP_PORT"]);
	$https_port=intval($ChilliConf["SQUID_HTTPS_PORT"]);
	
	if(is_file("/home/artica/packages/chilli.tar.gz")){@unlink("/home/artica/packages/chilli.tar.gz");}
	
	$wan_ip=$ChilliConf["HS_WANIF_IP"];
	
	if(!is_numeric($ChilliConf["HS_DEBUG"])){$ChilliConf["HS_DEBUG"]=0;}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} modprobe......: `$modprobe`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} sysctl........: `$sysctl`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} echo..........: `$echo`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} mknod.........: `$mknod`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} chilli........: `$chilli`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} iptables......: `$iptables`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Debug.........: `{$ChilliConf["HS_DEBUG"]}`\n";}
	

	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Proxy.........: `$wan_ip:$http_port`\n";}

	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} tune system...\n";}

	
	shell_exec("$sysctl -w net.ipv4.ip_forward=1 2>&1");
	shell_exec("$echo 1 > /proc/sys/net/ipv4/ip_forward");

	shell_exec("$sysctl -w net.ipv4.conf.eth0.send_redirects=$KernelSendRedirects 2>&1");	
	shell_exec("$modprobe tun >/dev/null 2>&1");
	up_sh($ChilliConf);
	
	$array=$unix->alt_stat("/dev/net/tun");
	
	if(!isset($array["device"]["device"])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} building /dev/net/tun\n";}
		@mkdir("/dev/net",0755,true);
		shell_exec("$mknod tun c 10 200");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	

	if(!is_dir("/usr/local/var/run")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} creating `/usr/local/var/run`\n";}
		@mkdir("/usr/local/var/run",0755,true);
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} `/usr/local/var/run` done.\n";}
	}
	
	CheckSquid($http_port,$https_port);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Adding Proxy TCP:$http_port/ssl:$https_port iptables rules\n";}
	
	
	
	$t[]="HS_NETWORK={$ChilliConf["HS_NETWORK"]}	   # HotSpot Network (must include HS_UAMLISTEN)";
	$t[]="HS_NETMASK={$ChilliConf["HS_NETMASK"]}   # HotSpot Network Netmask";
	$t[]="HS_UAMLISTEN={$ChilliConf["HS_UAMLISTEN"]}";
	
	$Sources="-s $wan_ip ";

	$Sources=null;
	$CMDS[]="$nohup $chilli";
	if($ChilliConf["HS_DEBUG"]==1){
		$CMDS[]="--debug";
	}
	$CMDS[]="--dhcpif={$ChilliConf["HS_LANIF"]}";
	$CMDS[]="--uamanydns";
	$CMDS[]="-c /etc/chilli.conf";
	$CMDS[]="--pidfile=/var/run/chilli.pid";
	$CMDS[]=">/dev/null 2>&1 &";
	$cmd=@implode(" ", $CMDS);
	shell_exec($cmd);
	
	for($i=1;$i<11;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}
		
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		events("Hostpot service as been successfully started PID: $pid (Artica)");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		start_dnsmasq(true);
		shell_exec("/etc/init.d/freeradius restart");
		sleep(1);
		shell_exec("$iptables -I INPUT -i {$ChilliConf["HS_WANIF"]} -p tcp --dport 443 -j ACCEPT");
		shell_exec("$iptables -I INPUT -i {$ChilliConf["HS_WANIF"]} -p tcp --dport 80 -j ACCEPT");
		shell_exec("$iptables -I INPUT -i {$ChilliConf["HS_WANIF"]} -p tcp --dport 22 -j ACCEPT");
		shell_exec("$iptables -I INPUT -p tcp -m tcp --dport 389 -j ACCEPT");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} flush iptables...\n";}
		$iptables_save=$unix->find_program("iptables-save");
		$iptables_restore=$unix->find_program("iptables-restore");
		$tmpfile=$unix->FILE_TEMP();
		shell_exec("$iptables_save > $tmpfile");
		shell_exec("$iptables_restore < $tmpfile");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} done...\n";}
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		flush_iptables();
	}	
	
	
}
function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("chilli"));	
}

function PID_PATH(){
	return "/var/run/chilli.pid";
}

function build(){
	$unix=new unix();
	$sock=new sockets();
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$EnableChilli=$sock->GET_INFO("EnableChilli");
	if(!is_numeric($EnableChilli)){$EnableChilli=0;}
	$KernelSendRedirects=$sock->GET_INFO("KernelSendRedirects");
	if(!is_numeric($KernelSendRedirects)){$KernelSendRedirects=1;}
	$save=false;
	$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
	$ChilliConf=GetInterfaceArray($ChilliConf);
	$php=$unix->LOCATE_PHP5_BIN();

	if(!isset($ChilliConf["HS_UAMFREEWEB"])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} FreeWeb Login page is not set...\n";}
	}

	
	
	if(!is_file("/var/www/c2/index.php")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Installing CakePHP\n";}
		shell_exec("/usr/share/artica-postfix/bin/artica-make APP_CAKEPHP >/dev/null 2>&1");
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CakePHP done\n";}
	}
	
	if(!is_file("/var/www/c2/yfi_cake/setup/coova_json/login.php")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} `/var/www/c2/yfi_cake/setup/coova_json/login.php no such file Installing YFI CakePHP\n";}
		shell_exec("/usr/share/artica-postfix/bin/artica-make APP_CAKEPHP >/dev/null 2>&1");
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} YFI CakePHP done\n";}
	}
	if(!is_dir("/usr/share/coova_json")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} `/usr/share/coova_json` no such directory Installing Coova JSon\n";}
		shell_exec("/usr/share/artica-postfix/bin/artica-make APP_CAKEPHP >/dev/null 2>&1");
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Coova JSon done\n";}
	}	
	
	
	
	$unix->SystemCreateUser("chilli","chilli");
	
	
	$f[]="include /etc/chilli/main.conf";
	$f[]="include /etc/chilli/hs.conf";
	$f[]="include /etc/chilli/local.conf";
	$f[]="ipup=/etc/chilli/up.sh";
	$f[]="ipdown=/etc/chilli/down.sh";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Chilli: `/etc/chilli.conf` done\n";}
	file_put_contents("/etc/chilli.conf",@implode("\n", $f));
	
	if(!is_numeric($ChilliConf["EnableSSLRedirection"])){$ChilliConf["EnableSSLRedirection"]=0;}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building main configuration: {$ChilliConf["HS_LANIF"]} -> {$ChilliConf["HS_WANIF"]}\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Listen.....: {$ChilliConf["HS_UAMLISTEN"]}\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DHCP.......: {$ChilliConf["HS_DYNIP"]}/{$ChilliConf["HS_DYNIP_MASK"]} ({$ChilliConf["HS_NETWORK"]})\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Proxy Port.: {$ChilliConf["SQUID_HTTP_PORT"]}\n";}
	
	
	
	
	
	$ldap=new clladp();
	if(!is_numeric($ChilliConf["ENABLE_DHCP_RELAY"])){$ChilliConf["ENABLE_DHCP_RELAY"]=0;}
	
	$t[]="# -*- mode: shell-script; -*-";
	$t[]="#";
	$t[]="#   Coova-Chilli Default Configurations. ";
	$t[]="#   To customize, copy this file to /etc/chilli/config";
	$t[]="#   and edit to your liking. This is included in shell scripts";
	$t[]="#   that configure chilli and related programs before file 'config'. ";
	$t[]="";
	$t[]="";
	$t[]="###";
	$t[]="#   Local Network Configurations";
	$t[]="# ";
	$t[]="";
	if($ChilliConf["HS_WANIF"]<>null){$t[]="HS_WANIF={$ChilliConf["HS_WANIF"]}            # WAN Interface toward the Internet";}
	$t[]="HS_LANIF={$ChilliConf["HS_LANIF"]}		   # Subscriber Interface for client devices";
	$t[]="HS_NETWORK={$ChilliConf["HS_NETWORK"]}	   # HotSpot Network (must include HS_UAMLISTEN)";
	$t[]="HS_NETMASK={$ChilliConf["HS_NETMASK"]}   # HotSpot Network Netmask";
	$t[]="HS_UAMLISTEN={$ChilliConf["HS_UAMLISTEN"]}   # HotSpot IP Address (on subscriber network)";
	$t[]="HS_UAMPORT=3990            # HotSpot UAM Port (on subscriber network)";
	$t[]="HS_UAMUIPORT=4990          # HotSpot UAM 'UI' Port (on subscriber network, for embedded portal)";
	$t[]="HS_NATANYIP=off";
	//$t[]="HS_STATIP=off";
	//$t[]="HS_STATIP_MASK=";
	
	
	$t[]="";
	if($ChilliConf["HS_DYNIP"]<>null){$t[]="HS_DYNIP={$ChilliConf["HS_DYNIP"]}";}
	if($ChilliConf["HS_DYNIP_MASK"]<>null){$t[]="HS_DYNIP_MASK={$ChilliConf["HS_DYNIP_MASK"]}";}
	if($ChilliConf["HS_DNS_DOMAIN"]<>null){$t[]="HS_DNS_DOMAIN={$ChilliConf["HS_DNS_DOMAIN"]}";}
	

	
	//$t[]="HS_STATIP={$ChilliConf["HS_STATIP"]}";
	//$t[]="HS_STATIP_MASK={$ChilliConf["HS_STATIP_MASK"]}";
	$t[]="# DNS Servers";
	$t[]="HS_DNS1={$ChilliConf["HS_UAMLISTEN"]}";
	$t[]="HS_DNS2={$ChilliConf["HS_UAMLISTEN"]}";
	
	
	
	DefaultSplash($ChilliConf);
	if(!isset($ChilliConf["SQUID_HTTP_PORT"])){$ChilliConf["SQUID_HTTP_PORT"]=rand(45000,65400);$save=true;}
	if(!is_numeric($ChilliConf["SQUID_HTTP_PORT"])){$ChilliConf["SQUID_HTTP_PORT"]=rand(45000,65400);$save=true;}

	if(!isset($ChilliConf["SQUID_HTTPS_PORT"])){$ChilliConf["SQUID_HTTPS_PORT"]=rand(45000,65400);$save=true;}
	if(!is_numeric($ChilliConf["SQUID_HTTPS_PORT"])){$ChilliConf["SQUID_HTTPS_PORT"]=rand(45000,65400);$save=true;}	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Building DNSMasq settings\n";}
	dnsmasq_config();
	
	if($ChilliConf["EnableSSLRedirection"]==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SSL redirection is Active\n";}
		$t[]="HS_UAMUISSL=on";
		$t[]="HS_REDIRSSL=on";
		include_once(dirname(__FILE__)."/ressources/class.squid.inc");
		$squid=new squidbee();
		$t[]=$squid->SaveCertificate($ChilliConf["certificate_center"],false,false,true);
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} SSL redirection is inactive\n";}
	}
	$ChilliConf["uamallowed"][$ChilliConf["HS_UAMFREEWEB"]]=true;
	$ChilliConf["uamallowed"]["127.0.0.1"]=true;
	$ChilliConf["uamallowed"][$ChilliConf["HS_WANIF_IP"]]=true;
	
	$ip=new IP();
	if($ip->isIPAddress($ChilliConf["HS_DNS1"])){
		$ChilliConf["uamallowed"][$ChilliConf["HS_DNS1"]]=true;
	}
	if($ip->isIPAddress($ChilliConf["HS_DNS2"])){
		$ChilliConf["uamallowed"][$ChilliConf["HS_DNS2"]]=true;
	}	
	
	if($ChilliConf["AD_SERVER"]<>null){
		$ChilliConf["uamallowed"][$ChilliConf["AD_SERVER"]]=true;
	}
	
	while (list ($num, $ligne) = each ($ChilliConf["uamallowed"]) ){
		if(trim($num)==null){continue;}
		if(is_numeric($num)){continue;}
		$HS_UAMALLOW[]=$num;
	}
	
	if($save){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Saving new configuration file...\n";}
		$NewArray=base64_encode(serialize($ChilliConf));
		$sock->SaveConfigFile($NewArray, "ChilliConf");
	}
	
	
	$RADIUS_IP="127.0.0.1";
	if($ChilliConf["RADIUS_IP"]<>null){
		$RADIUS_IP=$ChilliConf["RADIUS_IP"];
	}
	
	if(!is_numeric($ChilliConf["HS_LAN_ACCESS"])){$ChilliConf["HS_LAN_ACCESS"]=1;}

	$t[]="HS_NASID=nas01";
	$t[]="HS_RADIUS=$RADIUS_IP";
	//$t[]="HS_RADIUS2=$RADIUS_IP";
	$t[]="HS_UAMALLOW=".@implode(",", $HS_UAMALLOW);
	//$t[]="HS_ACCTUPDATE";
	$t[]="HS_RADSECRET=$ldap->ldap_password";
	$t[]="HS_UAMSECRET=$ldap->ldap_password";
	$t[]="HS_UAMALIASNAME=chilli";
	$t[]="HS_NASIP=$RADIUS_IP";
	if($ChilliConf["HS_LAN_ACCESS"]==1){
		$t[]="HS_LAN_ACCESS=on";
	}else{
		$t[]="HS_LAN_ACCESS=off";
	}
	
	if($ChilliConf["ENABLE_DHCP_RELAY"]==1){
		if($ChilliConf["HS_DHCPRELAYAGENT"]<>null){
			if($ChilliConf["HS_DHCPGATEWAY"]<>null){
				$t[]="HS_DHCPRELAYAGENT={$ChilliConf["HS_DHCPRELAYAGENT"]}";
				$t[]="HS_DHCPGATEWAY={$ChilliConf["HS_DHCPGATEWAY"]}";
			}
		}
		
	}
	
	if(is_numeric($ChilliConf["HS_UAMFREEWEB"])){$ChilliConf["HS_UAMFREEWEB"]=null;}
	
	$t[]="";
	//$t[]="HS_LAYER3=on";
	$t[]="";
	$t[]="# Put entire domains in the walled-garden with DNS inspection";
	$t[]="# HS_UAMDOMAINS=\".paypal.com,.paypalobjects.com\"";
	$t[]="HS_UAMSERVER={$ChilliConf["HS_UAMFREEWEB"]}";
	$t[]="# HS_UAMSERVICE=";

	$t[]="HS_UAMFORMAT=\"http://\$HS_UAMSERVER/hs_land.php\"";	
	$t[]="HS_UAMHOMEPAGE=\"http://{$ChilliConf["HS_UAMFREEWEB"]}/splash.php\"";
	$t[]="HS_CONUP=\"".__FILE__."\"";
	$t[]="HS_CONDOWN=\"".dirname(__FILE__)."/exec.chilli.condown.php\"";
	$t[]="";
	$t[]="";
	$t[]="###";
	$t[]="#   Features not activated per-default (default to off)";
	$t[]="# HS_RADCONF=off	   # Get some configurations from RADIUS or a URL ('on' and 'url' respectively)";
	$t[]="HS_ANYIP=on		   # Allow any IP address on subscriber LAN";
	$t[]="HS_MACAUTH=on		   # To turn on MAC Authentication";
	$t[]="# HS_MACAUTHDENY=on	   # Put client in 'drop' state on MAC Auth Access-Reject";
	$t[]="# HS_MACAUTHMODE=local	   # To allow MAC Authentication based on macallowed, not RADIUS";
	$t[]="# HS_MACALLOW=\"...\"      # List of MAC addresses to authenticate (comma seperated)";
	$t[]="# HS_USELOCALUSERS=on      # To use the /etc/chilli/localusers file";
	$t[]="# HS_OPENIDAUTH=on	   # To inform the RADIUS server to allow OpenID Auth";
	$t[]="# HS_WPAGUESTS=on	   # To inform the RADIUS server to allow WPA Guests";
	$t[]="# HS_DNSPARANOIA=on	   # To drop DNS packets containing something other";
	$t[]="# HS_OPENIDAUTH=on	   # To inform the RADIUS server to allow OpenID Auth";
	$t[]="# HS_USE_MAP=on		   # Short hand for allowing the required google";

	$t[]="###";
	$t[]="#   Other feature settings and their defaults";
	$t[]="# HS_DEFSESSIONTIMEOUT=0   # Default session-timeout if not defined by RADIUS (0 for unlimited)";
	$t[]="# HS_DEFIDLETIMEOUT=0	   # Default idle-timeout if not defined by RADIUS (0 for unlimited)";
	$t[]="# HS_DEFBANDWIDTHMAXDOWN=0   # Default WISPr-Bandwidth-Max-Down if not defined by RADIUS (0 for unlimited)";
	$t[]="# HS_DEFBANDWIDTHMAXUP=0	   # Default WISPr-Bandwidth-Max-Up if not defined by RADIUS (0 for unlimited)";
	$t[]="";
	$t[]="# HS_RADCONF=on		   # gather the ChilliSpot-Config attributes in";
	$t[]="#			   # Administrative-User login";
	$t[]="# HS_RADCONF_SERVER=rad01.coova.org		 # RADIUS Server";
	$t[]="# HS_RADCONF_SECRET=coova-anonymous		 # RADIUS Shared Secret ";
	$t[]="# HS_RADCONF_AUTHPORT=1812			 # Auth port";
	$t[]="# HS_RADCONF_USER=chillispot			 # Username";
	$t[]="# HS_RADCONF_PWD=chillispot			 # Password";

	$ALLOWPORTS["80"]=true;
	$ALLOWPORTS["443"]=true;
	$ALLOWPORTS["22"]=true;
	$ALLOWPORTS["2812"]=true;
	$ALLOWPORTS["53"]=true;
	$ALLOWPORTS["3990"]=true;
	$ALLOWPORTS["22"]=true;
	$ALLOWPORTS["9000"]=true;
	$ALLOWPORTS["389"]=true;
	$ALLOWPORTS["53"]=true;
	$ALLOWPORTS["1553"]=true;
	$ALLOWPORTS["137"]=true;
	$ALLOWPORTS["138"]=true;
	$ALLOWPORTS["139"]=true;
	$ALLOWPORTS["445"]=true;
	$ALLOWPORTS["80"]=true;
	$ALLOWPORTS["443"]=true;
	$ALLOWPORTS["1812"]=true;
	$ALLOWPORTS["3306"]=true;
	$ALLOWPORTS["47980"]=true;
	
	while (list ($index, $line) = each ($ALLOWPORTS)){
		$PPORT[]=$index;
		
	}
	
	
	$t[]="HS_TCP_PORTS=\"". @implode(" ", $PPORT)."\"";
	$t[]="";
	$t[]="###";
	$t[]="#   Standard configurations";
	$t[]="#";
	$t[]="HS_MODE=hotspot";
	$t[]="HS_TYPE=chillispot";
	$t[]="# HS_RADAUTH=1812";
	$t[]="# HS_RADACCT=1813";
	$t[]="# HS_ADMUSR=chillispot";
	$t[]="# HS_ADMPWD=chillispot";
	$t[]="";
	$t[]="";
	


	if($ChilliConf["HS_PROVIDER"]==null){$ChilliConf["HS_PROVIDER"]="Artica";}
	if($ChilliConf["HS_PROVIDER_LINK"]==null){$ChilliConf["HS_PROVIDER_LINK"]="http://www.articatech.net";}
	if($ChilliConf["HS_LOC_NAME"]==null){$ChilliConf["HS_LOC_NAME"]="Artica HotSpot";}
	if($ChilliConf["HS_LOC_NETWORK"]==null){$ChilliConf["HS_LOC_NETWORK"]="HotSpot Network";}
	
	
	$t[]="HS_PROVIDER={$ChilliConf["HS_PROVIDER"]}";
	$t[]="HS_PROVIDER_LINK={$ChilliConf["HS_PROVIDER_LINK"]}/";
	//$t[]="HS_LOC_NAME=\"{$ChilliConf["HS_LOC_NAME"]}\"	   # WISPr Location Name and used in portal";
	//$t[]="HS_LOC_NETWORK=\"{$ChilliConf["HS_LOC_NETWORK"]}\"	   # Network name";
	$t[]="# HS_LOC_AC=408			   # Phone area code";
	$t[]="# HS_LOC_CC=1			   # Phone country code";
	$t[]="# HS_LOC_ISOCC=US		   # ISO Country code";
	$t[]="";
	$t[]="# Embedded miniportal";
	$t[]="# HS_REG_MODE=\"tos\" # or self, other";
	$t[]="# HS_RAD_PROTO=\"pap\" # or mschapv2, chap";
	$t[]="# HS_USE_MAP=on\n";	
	echo "Starting......: ".date("H:i:s")." [INIT]: Chilli: `/etc/chilli/config` done\n";
	echo "Starting......: ".date("H:i:s")." [INIT]: Chilli: flush /etc/init.d..\n";
	chilli_init_d();
	file_put_contents("/etc/chilli/config", @implode("\n", $t));
	coova_web();
	shell_exec("$php5 /usr/share/artica-postfix/exec.freeradius.php --build");
	if($GLOBALS["RELOAD"]){
		$kill=$unix->find_program("kill");
		shell_exec("/etc/init.d/chilli reconfigure");
		$pid=PID_NUM();
		if($unix->process_exists($pid)){
			shell_exec("$kill -HUP $pid 2>&1");
		}else{
			start();
		}
		
	}
	
}

function dnsmasq_config($ChilliConf=array()){
	
	if(count($ChilliConf)<5){
		$sock=new sockets();
		$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
		$ChilliConf=GetInterfaceArray($ChilliConf);
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Chilli DnsMasq DNS Domain: `{$ChilliConf["HS_DNS_DOMAIN"]}`\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Chilli DnsMasq UAM Listen: `{$ChilliConf["HS_UAMLISTEN"]}`\n";}
	$t[]="address=/{$ChilliConf["HS_DNS_DOMAIN"]}/{$ChilliConf["HS_UAMLISTEN"]}";
	$t[]="no-resolv";
	$t[]="strict-order";
	
	$t[]="expand-hosts";
	$t[]="domain={$ChilliConf["HS_DNS_DOMAIN"]}";
	
	if($ChilliConf["HS_DNS1"]==null){$ChilliConf["HS_DNS1"]="8.8.8.8";}
	if($ChilliConf["HS_DNS2"]==null){$ChilliConf["HS_DNS2"]="8.8.4.4";}
	
	if($ChilliConf["HS_DNS1"]<>null){$t[]="server={$ChilliConf["HS_DNS1"]}";}
	if($ChilliConf["HS_DNS2"]<>null){$t[]="server={$ChilliConf["HS_DNS2"]}";}	
	$t[]="bogus-nxdomain=67.215.65.132";	
	@file_put_contents("/etc/chilli/dnsmasq.conf", @implode("\n", $t));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Chilli DnsMasq settings OK\n";}
}

function CheckSquid($http_port=0,$https_port=0){
	if(!is_file("/etc/squid3/squid.conf")){return;}
	$f=explode("\n",@file_get_contents("/etc/squid3/squid.conf"));
	if(  ($http_port==0) &&  ($https_port==0)  ){return;}
	$OK=false;
	$OKSSL=false;
	foreach ($f as $index=>$line){
		
		if($http_port>0){
			if(preg_match("#http_port.*?$http_port#", $line)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Chilli Squid listen to port $http_port\n";}
				$OK=true;
				continue;
				
			}
		}else{
			$OK=true;
		}
		
		if($https_port>0){
			if(preg_match("#https_port.*?$https_port#", $line)){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Chilli Squid listen to SSL port $https_port\n";}
				$OKSSL=true;
				continue;
				
			}
		}else{
			$OKSSL=true;
		}		
		
	}
	
	if($OK){
		if($OKSSL){
			return ;
		}
	}
	
	

		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Chilli Cannot find `$http_port/$https_port` Reconfigure squid-cache\n";}
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force");
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Chilli restarting squid-cache\n";}
		shell_exec("/etc/init.d/squid restart --script=".basename(__FILE__));

	
}


function coova_web(){
	$ldap=new clladp();
	$sock=new sockets();
	$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
	
	
	if(!isset($ChilliConf["HS_UAMFREEWEB"])){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: FreeWeb Login page is not set...\n";}}
	
	
	$f[]="<?";
	$f[]="\t\$msecret  = '$ldap->ldap_password';            //Change this to be the same as your chilli's configuration";
	$f[]="\t\$username   = \$_POST['username'];";
	$f[]="\t\$password   = \$_POST['password'];";
	$f[]="\t\$challenge  = \$_POST['challenge'];";
	$f[]="\t\$redir	    = \$_POST['userurl'];";
	$f[]="\t\$server_ip  = \$_POST['uamip'];";
	$f[]="\t\$port       = \$_POST['uamport'];";
	$f[]="";
	$f[]="    //--Add a remember me cookie---";
	$f[]="    if( array_key_exists('remember',\$_POST)){";
	$f[]="        \$Year = (2592000*12) + time();";
	$f[]="        setcookie(\"hs[username]\",   \$username, \$Year);";
	$f[]="        setcookie('hs[password]',        \$password, \$Year);";
	$f[]="    }";
	$f[]="";
	$f[]="    //--There is a bug that keeps the logout in a loop if userurl is http%3a%2f%2f1.0.0.0 ---/";
	$f[]="    //--We need to remove this and replace it with something we want";
	$f[]="    if (preg_match(\"/1\.0\.0\.0/i\", \$redir)) {";
	$f[]="";
	$f[]="        \$default_site = 'google.com';";
	$f[]="        \$pattern = \"/1\.0\.0\.0/i\";";
	$f[]="        \$redir = preg_replace(\$pattern, \$default_site, \$redir);";
	$f[]="    }";
	$f[]="";
	$f[]="	\$enc_pwd    = return_new_pwd(\$password,\$challenge,\$uamsecret);";
	$f[]="	//\$dir		= '/json/logon';";
	$f[]="	\$dir		= '/logon';";
	$f[]="    \$target     = \"http://\$server_ip\".':'.\$port.\$dir.\"?username=\$username&password=\$enc_pwd&userurl=\$redir\";";
	$f[]="   // print(\$target);";
	$f[]="";
	$f[]="	header(\"Location: \$target\");";
	$f[]="";
	$f[]="	//Function to do the encryption thing of the password";
	$f[]="	function return_new_pwd(\$pwd,\$challenge,\$uamsecret){";
	$f[]="	        \$hex_chal   = pack('H32', \$challenge);                  //Hex the challenge";
	$f[]="	        \$newchal    = pack('H*', md5(\$hex_chal.\$uamsecret));    //Add it to with \$uamsecret (shared between chilli an this script)";
	$f[]="	        \$response   = md5(\"\0\" . \$pwd . \$newchal);              //md5 the lot";
	$f[]="	        \$newpwd     = pack('a32', \$pwd);                //pack again";
	$f[]="	        \$password   = implode ('', unpack('H32', (\$newpwd ^ \$newchal))); //unpack again";
	$f[]="	        return \$password;";
	$f[]="    	}";
	$f[]="";
	$f[]="?>";	
	
	$unix=new unix();
	$cp=$unix->find_program("cp");
	@mkdir("/var/www/coova_json",0755,true);
	if(!is_file("/var/www/coova_json/login.php")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Installing Coova JSON...\n";
		shell_exec("$cp -rf /var/www/c2/yfi_cake/setup/coova_json/* /var/www/coova_json/");
	}
	
	
	@file_put_contents("/var/www/coova_json/login.php", @implode("\n", $f));
	@chmod("var/www/coova_json/login.php", 0755);
	$unix->chown_func($unix->APACHE_SRC_ACCOUNT(),$unix->APACHE_SRC_GROUP(), "/var/www/coova_json/*");
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} `coova_json/login.php` done\n";
	
	$f=explode("\n", @file_get_contents("/var/www/coova_json/js/custom.js"));
	
	while (list ($key, $line) = each ($f) ){
		if(preg_match("#\s+p_url_use:.*?'#", $line)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Chilli: L.$key FreeWeb: {$ChilliConf["HS_UAMFREEWEB"]}\n";
			$f[$key]="\tp_url_use:  'http://{$ChilliConf["HS_UAMFREEWEB"]}/c2/yfi_cake/third_parties/json_usage_check?key=12345&username=',  //This is the YFi Web service which will show the user's usage";
			continue;
		}
		if(preg_match("#\s+p_url_uam:.*?'#", $line)){	
			echo "Starting......: ".date("H:i:s")." [INIT]: Chilli: L.$key FreeWeb: {$ChilliConf["HS_UAMFREEWEB"]}\n";
			$f[$key]="\tp_url_uam:  'http://{$ChilliConf["HS_UAMFREEWEB"]}/mobile/uam.php?challenge=',    //This us the web service which will return a uam encrypted hash using the challenge, password and UAM shared secret";
			continue;
		}
		
		if(preg_match("#\s+p_url_voucher_name:.*?'#", $line)){
			echo "Starting......: ".date("H:i:s")." [INIT]: Chilli: L.$key FreeWeb: {$ChilliConf["HS_UAMFREEWEB"]}\n";
			$f[$key]="\tp_url_voucher_name: 'http://{$ChilliConf["HS_UAMFREEWEB"]}/c2/yfi_cake/third_parties/json_voucher_name?key=12345&password=',";
			continue;
		}
	}
	
	@file_put_contents("/var/www/coova_json/js/custom.js", @implode("\n", $f));
	echo "Starting......: ".date("H:i:s")." [INIT]: Chilli: `/custom.js` done\n";
	$f=explode("\n", @file_get_contents("/var/www/coova_json/uam.php"));
	
	while (list ($key, $line) = each ($f) ){
		if(preg_match("#uamsecret.*?=#", $line)){
			$f[$key]="\t\$uamsecret = '$ldap->ldap_password';";
			continue;
		}
	}
	
	@file_put_contents("/var/www/coova_json/uam.php", @implode("\n", $f));	
	echo "Starting......: ".date("H:i:s")." [INIT]: Chilli: `/uam.php` done\n";
	DefaultSplash($ChilliConf);
}


function GetInterfaceArray($ChilliConf){
		$unix=new unix();
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: LANIF:{$ChilliConf["HS_LANIF"]}\n";}
		
		if(!is_numeric($ChilliConf["HS_DYNIP"])){$ChilliConf["HS_DYNIP"]=50;}
		
		$array=$unix->InterfaceToIP($ChilliConf["HS_LANIF"]);
		if($GLOBALS["VERBOSE"]){
			foreach ($array as $num=>$ligne){
				echo "Starting......: ".date("H:i:s")." [DEBUG]: LANIF:{$ChilliConf["HS_LANIF"]} [{$num}] = `$ligne`\n";
			}
		}
		
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: LANIF: {$array["IPADDR"]} -> EXPLODE\n";}
		if($ChilliConf["HS_UAMLISTEN"]==null){$ChilliConf["HS_UAMLISTEN"]=$array["IP"];}
		if($ChilliConf["HS_NETMASK"]==null){$ChilliConf["HS_NETMASK"]=$array["NETMASK"];}		
		
// **** LAN ************************************************

		
		$PR=explode(".",$ChilliConf["HS_UAMLISTEN"]);
		$ChilliConf["HS_DYNIP_MASK"]=$ChilliConf["HS_NETMASK"];
		$ChilliConf["HS_DYNIP"]="{$PR[0]}.{$PR[1]}.{$PR[2]}.{$ChilliConf["HS_DYNIP"]}";
		$ChilliConf["HS_NETWORK"]="{$PR[0]}.{$PR[1]}.{$PR[2]}.0";
		if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." [DEBUG]: LANIF:{$ChilliConf["HS_LANIF"]} HS_DYNIP {$ChilliConf["HS_DYNIP"]}\n";}
		

// **** WAN ************************************************		
		$array=$unix->InterfaceToIP($ChilliConf["HS_WANIF"]);
		$ChilliConf["HS_WANIF_IP"]=$array["IP"];
		$ChilliConf["HS_STATIP"]=$array["NETWORK"];
		$ChilliConf["HS_STATIP_MASK"]=$array["NETMASK"];

		
		$array=$unix->InterfaceToIP($ChilliConf["DHCP_IF"]);
		$ChilliConf["HS_DHCPRELAYAGENT"]=$array["IP"];
		
		$array=$unix->InterfaceToIP($ChilliConf["RADIUS_IF"]);
		$ChilliConf["RADIUS_IP"]=$array["IP"];
		
		return $ChilliConf;
				
}


function CssContent(){
	$f[]="        <style type=\"text/css\">";
	$f[]="";
	$f[]="			body{";
	$f[]="				font: 10pt Arial, Helvetica, sans-serif;";
	$f[]="				background: #fffff;";
	$f[]="			}";
	$f[]="			#sum{";
	$f[]="				width: 485px;";
	$f[]="				height: 221px;";
	$f[]="				margin: 50px auto;";
	$f[]="			}";
	$f[]="			h1{";
	$f[]="				width: 401px;";
	$f[]="				height: 127px;";
	$f[]="				background: transparent url('<? echo(\$DEFAULT_LOGO) ?>') no-repeat;";
	$f[]="				margin: 0 27px 21px;";
	$f[]="			}";
	$f[]="	";
	$f[]="			h1 span{";
	$f[]="				display: none;";
	$f[]="			}";
	$f[]="			#content{";
	$f[]="				width: 485px;";
	$f[]="				height: 221px;";
	$f[]="				background: url('<? echo(\$IMAGE_HEADERS) ?>/form.png') no-repeat;	";
	$f[]="			}";
	$f[]="			.f{";
	$f[]="				padding: 45px 50px 45px 38px;	";
	$f[]="				overflow: hidden;";
	$f[]="			}";
	$f[]="			.field{";
	$f[]="				clear:both;";
	$f[]="				text-align: right;";
	$f[]="				margin-bottom: 15px;";
	$f[]="			}";
	$f[]="			.field label{";
	$f[]="				float:left;";
	$f[]="				font-weight: bold;";
	$f[]="				line-height: 42px;";
	$f[]="			}";
	$f[]="			.field input{";
	$f[]="				background: #fff url('<? echo(\$IMAGE_HEADERS) ?>/input.png') no-repeat;";
	$f[]="				outline: none;";
	$f[]="				border: none;";
	$f[]="				font-size: 10pt;";
	$f[]="				padding: 7px 9px 8px;";
	$f[]="				width: 279px;";
	$f[]="				height: 25px;";
	$f[]="				font-size: 18px;";
	$f[]="				font-weight:bolder;";
	$f[]="				color:#444444;";
	$f[]="			}";
	$f[]="			.field input.active{";
	$f[]="				background: url('<? echo(\$IMAGE_HEADERS) ?>/input_act.png') no-repeat;";
	$f[]="			}";
	$f[]="			.button{";
	$f[]="				width: 297px;";
	$f[]="				float: right;";
	$f[]="			}";
	$f[]="			.button input{";
	$f[]="				width: 69px;";
	$f[]="				background: url('<? echo(\$IMAGE_HEADERS) ?>/btn_bg.png') no-repeat;";
	$f[]="				border: 0;";
	$f[]="				font-weight: bold;";
	$f[]="				height: 27px;";
	$f[]="				float: left;";
	$f[]="				padding: 0;";
	$f[]="			}";
	$f[]="        ";
	$f[]="		</style>";

	return @implode("\n", $f);
}

function DefaultSplash($ChilliConf){


	$newArray["HS_PROVIDER"]=$ChilliConf["HS_PROVIDER"];
	$newArray["HS_PROVIDER_LINK"]=$ChilliConf["HS_PROVIDER_LINK"];
	$newArray["HS_LOC_NAME"]=$ChilliConf["HS_LOC_NAME"];
	$newArray["HS_LOC_NETWORK"]=$ChilliConf["HS_LOC_NETWORK"];
	
	@mkdir("/var/www/coova_json",0755,true);
	@mkdir("/var/www/coova_json/img",0755,true);
	@file_put_contents("/var/www/coova_json/default.cfg", serialize($newArray));
	@copy("/usr/share/artica-postfix/img/hotspot-logo.png","/var/www/coova_json/img/default-logo.png");
	@copy("/usr/share/artica-postfix/img/error-128.png","/var/www/coova_json/img/error-128.png");
	@copy("/usr/share/artica-postfix/img/wait_verybig_mini_red.gif","/var/www/coova_json/img/wait.gif");

	
	@chmod("/var/www/coova_json/login.artica.php",0755);
	@chmod("/var/www/coova_json/default.cfg",0755);
	@chmod("/var/www/coova_json/img/default-logo.png",0755);
	
	$f[]="<?";
	$f[]="	\$challenge = \$_REQUEST['challenge'];";
	$f[]="	\$userurl   = \$_REQUEST['userurl'];";
	$f[]="	\$res	   = \$_REQUEST['res'];";
	$f[]="	\$qs        = \$_SERVER[\"QUERY_STRING\"];";
	$f[]="";
	$f[]="    \$uamip     = \$_REQUEST['uamip'];";
	$f[]="    \$uamport   = \$_REQUEST['uamport'];";
	$f[]="    \$mac   = \$_REQUEST['mac'];";
	$f[]="    \$ip   = \$_REQUEST['ip'];";
	$f[]="";
	$f[]="";
	$f[]="    //--There is a bug that keeps the logout in a loop if userurl is http%3a%2f%2f1.0.0.0 ---/";
	$f[]="    //--We need to remove this and replace it with something we want";
	$f[]="    if (preg_match(\"/1\.0\.0\.0/i\", \$userurl)) {";
	$f[]="        \$default_site = 'google.com';";
	$f[]="        \$pattern = \"/1\.0\.0\.0/i\";";
	$f[]="        \$userurl = preg_replace(\$pattern, \$default_site, \$userurl);";
	$f[]="    }";
	$f[]="    //---------------------------------------------------------";
	$f[]="";
	$f[]="	if(\$res == 'success'){";
	$f[]="";
	$f[]="		header(\"Location: \$userurl\");";
	$f[]="		print(\"\n</html>\");";
	$f[]="	}";
	$f[]="";
	$f[]="	if(\$res == 'failed'){";
	$f[]="";
	$f[]="		header(\"Location: fail.php?\".\$qs);";
	$f[]="		print(\"\n</html>\");";
	$f[]="";
	$f[]="	}";
	$f[]="";
	$f[]="    //-- cookie add on -------------------------------";
	$f[]="    if(\$res == 'notyet'){";
	$f[]="";
	$f[]="        if(isset(\$_COOKIE['hs'])){";
	$f[]="";
	$f[]="                \$uamsecret  = 'greatsecret';";
	$f[]="                \$dir        = '/logon';";
	$f[]="                \$userurl    = \$_REQUEST['userurl'];";
	$f[]="                \$redir      = urlencode(\$userurl);";
	$f[]="";
	$f[]="                \$username   = \$_COOKIE['hs']['username'];";
	$f[]="                \$password   = \$_COOKIE['hs']['password'];";
	$f[]="                \$enc_pwd    = return_new_pwd(\$password,\$challenge,\$uamsecret);";
	$f[]="                \$target     = \"http://\$uamip\".':'.\$uamport.\$dir.\"?username=\$username&password=\$enc_pwd&userurl=\$redir\";";
	$f[]="                header(\"Location: \$target\");";
	$f[]="                print(\"\n</html>\");";
	$f[]="        }";
	$f[]="    }";
	$f[]="    //Function to do the encryption thing of the password";
	$f[]="    function return_new_pwd(\$pwd,\$challenge,\$uamsecret){";
	$f[]="            \$hex_chal   = pack('H32', \$challenge);                  //Hex the challenge";
	$f[]="            \$newchal    = pack('H*', md5(\$hex_chal.\$uamsecret));    //Add it to with \$uamsecret (shared between chilli an this script)";
	$f[]="            \$response   = md5(\"\0\" . \$pwd . \$newchal);              //md5 the lot";
	$f[]="            \$newpwd     = pack('a32', \$pwd);                //pack again";
	$f[]="            \$password   = implode ('', unpack('H32', (\$newpwd ^ \$newchal))); //unpack again";
	$f[]="            return \$password;";
	$f[]="    }";
	$f[]="";
	$f[]="    ";
	$f[]="    \$IMAGE_HEADERS=\$_SERVER[\"SERVER_NAME\"].\"/img\";";
	$f[]="    ";
	$f[]="    \$DEFAULT_SPLASH=unserialize(@file_get_contents(\"default.cfg\"));";
	$f[]="    \$TITLE_HTML=\"{\$DEFAULT_SPLASH[\"HS_LOC_NAME\"]}\";";
	$f[]="    \$HS_PROVIDER_LINK=\"{\$DEFAULT_SPLASH[\"HS_PROVIDER_LINK\"]}\";";	
	$f[]="    \$LINK_HTML=\"<a href=\\\"\$HS_PROVIDER_LINK\\\">{\$DEFAULT_SPLASH[\"HS_PROVIDER\"]}</a>\";";
	$f[]="    \$DEFAULT_LOGO=\"img/default-logo.png\";";
	$f[]="    ";
	$f[]="    //-- End Cookie add on ------------";
	$f[]="";
	$f[]="?>";
	$f[]="<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"";
	$f[]="   \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
	$f[]="<html>";
	$f[]="    <head>";
	$f[]="        <title><? echo(\$TITLE_HTML) ?></title>";
	$f[]="        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\" />";
	$f[]="        <meta http-equiv=\"pragma\" content=\"no-cache\" />";
	$f[]="        <meta http-equiv=\"expires\" content=\"-1\" />";
	$f[]="        <script type=\"text/javascript\" src=\"js/dojo/dojo.js\" djConfig=\"parseOnLoad: true\"></script>";
	$f[]="       ";
	$f[]=CssContent();
	$f[]="";
	$f[]="</head>";
	$f[]="";
	$f[]="<body>";
	$f[]="<div style=\"postition:absolute;top:0px;left:80%;width:100%\">";
	$f[]="<table style='width:100%;padding:0px;margin:0px'>";
	$f[]="<tbody><tr>";
	$f[]="<td width=100%>&nbsp;<td>";
	$f[]="<td width=1% nowrap><div id=\"user_info\" style='text-align:right;width:90px'>";
	$f[]=" <div id=\"langs\" style=\"text-align:right;\">";
	$f[]="";
	$f[]=" </div>";
	$f[]="</div>";
	$f[]="</td>";
	$f[]="</tr>";
	$f[]="</body>";
	$f[]="</table>";
	$f[]="</div>";
	$f[]="";
	$f[]="  <div id=\"sum\">";
	$f[]="    <div id=\"header\">";
	$f[]="      <h1><span>{TEMPLATE_TITLE_HEAD}</span></h1>";
	$f[]="    </div>";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="    <div id=\"content\">";
	$f[]="";
	$f[]="			 <form name=\"login\" action=\"login.artica.php\" method=\"post\">";
	$f[]="			 <input type=\"hidden\" name=\"uamip\" value=\"<? echo(\$uamip) ?>\" />";
	$f[]="			 <input type=\"hidden\" name=\"mac\" value=\"<? echo(\$mac) ?>\" />";
	$f[]="			 <input type=\"hidden\" name=\"ip\" value=\"<? echo(\$ip) ?>\" />";
	$f[]="           <input type=\"hidden\" name=\"uamport\" value=\"<? echo(\$uamport) ?>\" />";
	$f[]="           <input type=\"hidden\" name=\"challenge\" value=\"<? echo(\$challenge) ?>\" />";
	$f[]="           <input type=\"hidden\" name=\"userurl\" value=\"<? echo(urlencode(\$userurl)) ?>\" />";
	$f[]="           <input type=\"hidden\" name=\"lang\" id='sel_lang' value=\"en\" />";
	$f[]="			 ";
	$f[]="				<div class=\"f\">";
	$f[]="					<div class=\"field\">";
	$f[]="						<label for=\"username\">User name:</label> <input type=\"text\" name=\"username\" id=\"l_username\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon(event)\">";
	$f[]="		";
	$f[]="					</div>";
	$f[]="					<div class=\"field\">";
	$f[]="						<label for=\"fpassword\">Password:</label> <input type=\"password\" name=\"password\" id=\"l_password\" onfocus=\"this.setAttribute('class','active')\" onblur=\"this.removeAttribute('class');\" OnKeyPress=\"javascript:SendLogon(event)\">";
	$f[]="						<div id='lostpassworddiv'></div>";
	$f[]="					</div>";
	$f[]="					<div class=\"field button\">";
	$f[]="						<input type=\"submit\" value=\"submit\"/>";
	$f[]="					</div>";
	$f[]="				</div>";
	$f[]="		";
	$f[]="			</form>			";
	$f[]="    </div><!-- /#content -->";
	$f[]="";
	$f[]="    <div class=\"footer\">";
	$f[]="    	<center style='font-size:13px;font-weight:bold;color:black'><? echo(\$LINK_HTML) ?><br>Copyright <? echo(date(\"Y\")) ?></center>";
	$f[]="    </div><!-- /#footer -->";
	$f[]="  </div>";
	$f[]="  ";
	$f[]="</body>";
	$f[]="<script type=\"text/javascript\">";
	$f[]="  document.login.username.focus();";
	$f[]="</script>";
	$f[]="</html>";
	$f[]="";
	@file_put_contents("/var/www/coova_json/hs_land.php", @implode("\n", $f));
	@chmod("/var/www/coova_json/hs_land.php",0755);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /hs_land.php done\n";}
	$f=array();
	
	$f[]="<?";
	$f[]="	\$msecret  = 'secret';            //Change this to be the same as your chilli's configuration";
	$f[]="	\$username   = \$_POST['username'];";
	$f[]="	\$password   = \$_POST['password'];";
	$f[]="	\$challenge  = \$_POST['challenge'];";
	$f[]="	\$redir	    = \$_POST['userurl'];";
	$f[]="	\$server_ip  = \$_POST['uamip'];";
	$f[]="	\$port       = \$_POST['uamport'];";
	$f[]="";
	$f[]="    //--Add a remember me cookie---";
	$f[]="    if( array_key_exists('remember',\$_POST)){";
	$f[]="        \$Year = (2592000*12) + time();";
	$f[]="        setcookie(\"hs[username]\",   \$username, \$Year);";
	$f[]="        setcookie('hs[password]',        \$password, \$Year);";
	$f[]="    }";
	$f[]="";
	$f[]="    //--There is a bug that keeps the logout in a loop if userurl is http%3a%2f%2f1.0.0.0 ---/";
	$f[]="    //--We need to remove this and replace it with something we want";
	$f[]="    if (preg_match(\"/1\.0\.0\.0/i\", \$redir)) {";
	$f[]="";
	$f[]="        \$default_site = 'google.com';";
	$f[]="        \$pattern = \"/1\.0\.0\.0/i\";";
	$f[]="        \$redir = preg_replace(\$pattern, \$default_site, \$redir);";
	$f[]="    }";
	$f[]="";
	$f[]="	\$enc_pwd    = return_new_pwd(\$password,\$challenge,\$uamsecret);";
	$f[]="	//\$dir		= '/json/logon';";
	$f[]="	\$dir		= '/logon';";
	$f[]="    \$target     = \"http://\$server_ip\".':'.\$port.\$dir.\"?username=\$username&password=\$enc_pwd&userurl=\$redir\";";
	$f[]="   // print(\$target);";
	$f[]="";
	$f[]="	header(\"Location: \$target\");";
	$f[]="";
	$f[]="	//Function to do the encryption thing of the password";
	$f[]="	function return_new_pwd(\$pwd,\$challenge,\$uamsecret){";
	$f[]="	        \$hex_chal   = pack('H32', \$challenge);                  //Hex the challenge";
	$f[]="	        \$newchal    = pack('H*', md5(\$hex_chal.\$uamsecret));    //Add it to with \$uamsecret (shared between chilli an this script)";
	$f[]="	        \$response   = md5(\"\" . \$pwd . \$newchal);              //md5 the lot";
	$f[]="	        \$newpwd     = pack('a32', \$pwd);                //pack again";
	$f[]="	        \$password   = implode ('', unpack('H32', (\$newpwd ^ \$newchal))); //unpack again";
	$f[]="	        return \$password;";
	$f[]="    	}";
	$f[]="";
	$f[]="?>";	
	
	if($ChilliConf["HS_PROVIDER"]==null){$ChilliConf["HS_PROVIDER"]="Artica";}
	if($ChilliConf["HS_PROVIDER_LINK"]==null){$ChilliConf["HS_PROVIDER_LINK"]="http://www.articatech.net";}
	if($ChilliConf["HS_LOC_NAME"]==null){$ChilliConf["HS_LOC_NAME"]="Artica HotSpot";}
	if($ChilliConf["HS_LOC_NETWORK"]==null){$ChilliConf["HS_LOC_NETWORK"]="HotSpot Network";}
	$f=array();
	$f[]="<?
\$userurl   = \$_REQUEST['userurl'];
\$reason    = \$_REQUEST['reply'];
	
//If it failed here we need to wipe the cookie
setcookie(\"hs[username]\",   \"\", time()-3600);
setcookie('hs[password]',   \"\", time()-3600);
?>
<html>
	<head>
		<title>{$ChilliConf["HS_LOC_NAME"]}</title>
		".CssContent()."
	</head>
<body>
	<div id=\"content\">
		<div id=\"trans-border\">
			<center style='margin:50px'>
			<h3 style='font-size:22px'>Authentication Failure</h3>
			<center><img src='img/error-128.png' style='margin:10px'></center>
			<center style='margin:10px;style='font-size:18px'><strong>Reason: </b><? echo(\$reason); ?></strong></center>
			<center style='margin:10px;style='font-size:22px'><a href=\"<? echo(\$userurl); ?>\">Try Again</a></center>
			</center>
			</div>
		</div>
		<script>
			function ReturnBacl(){
				document.location.href='<? echo(\$userurl); ?>';
			
			}
		
			setTimeout('ReturnBacl()',1500);
		
		</script>
</body>
</html>	";
	@file_put_contents("/var/www/coova_json/fail.php", @implode("\n", $f));
	@chmod("/var/www/coova_json/fail.php",0755);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /fail.php done\n";}	
$f=array();


$f[]="
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">
<html>
    <head>
    <title>{$ChilliConf["HS_LOC_NAME"]}</title>
    
    <?
        \$login_url = urldecode(\$_SERVER[\"QUERY_STRING\"]);
        \$redir_url = preg_replace('/^loginurl=/','',\$login_url);
    ?>
    <meta http-equiv=\"refresh\" content=\"2; URL=<? print \$redir_url; ?>\"> 
    <style type=\"text/css\">
      	".CssContent()."
    </style>
    </head>
    <body style=\"margin: 0pt auto; height:100%; background:white;\">
        <div style=\"width:100%;height:80%;position:fixed;display:table;\">
            	<p style=\"display: table-cell; line-height: 2.5em; vertical-align:middle;text-align:center;color:grey;\">

               <center style='margin:20px'>
 <div id=\"sum\">
		<div id=\"header\">
		<h1><span>{TEMPLATE_TITLE_HEAD}</span></h1>
	 </div>
</div>     				
      				<img src='img/wait.gif'>
      			</center>
        </div>
    </body>
</html>
";
@file_put_contents("/var/www/coova_json/splash.php", @implode("\n", $f));
@chmod("/var/www/coova_json/splash.php",0755);
if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /splash.php done\n";}		
}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$iptables=$unix->find_program("iptables");
	
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		if($GLOBALS["BYCONSOLE"]){
			@unlink("/etc/artica-postfix/MEM_INTERFACES");
			shell_exec("$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --build");
			$unix->THREAD_COMMAND_SET("/etc/init.d/artica-status reload");
		}
		return;
	}
	$pid=PID_NUM();




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		flush_iptables();
		stop_dnsmasq(true);
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success, cleaning firewall...\n";}
	flush_iptables();
	stop_dnsmasq(true);
	$unix->THREAD_COMMAND_SET("/etc/init.d/artica-status reload");

}
function stop_dnsmasq($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM_DNSMASQ();

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$iptables=$unix->find_program("iptables");



	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service already stopped...\n";}
		return;
	}
	$pid=PID_NUM_DNSMASQ();




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM_DNSMASQ();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM_DNSMASQ();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM_DNSMASQ();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service failed...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} DNSMasq service success, cleaning firewall...\n";}
	


}

function flush_iptables(){
	$unix=new unix();
	$iptables=$unix->find_program("iptables");
	
	shell_exec("$iptables -F INPUT");
	shell_exec("$iptables -P INPUT ACCEPT");
	shell_exec("$iptables -F OUTPUT");
	shell_exec("$iptables -P OUTPUT ACCEPT");
	shell_exec("$iptables -F FORWARD");
	shell_exec("$iptables -P FORWARD ACCEPT");
	shell_exec("$iptables -t nat -F PREROUTING");
	shell_exec("$iptables -t nat -F");
	shell_exec("$iptables -t mangle -F");
	shell_exec("$iptables -F");
	shell_exec("$iptables -X");	
	
}

function chilli_init_d(){
		$unix=new unix();
		$sock=new sockets();
		$chilli=$unix->find_program("chilli");
		$php5=$unix->LOCATE_PHP5_BIN();
		$ifconfig=$unix->find_program("ifconfig");
		$EnableChilli=$sock->GET_INFO("EnableChilli");
		if(!is_numeric($EnableChilli)){$EnableChilli=0;}		
		$iptables=$unix->find_program("iptables");
		$f[]="#!/bin/sh";
		$f[]="#";
		$f[]="# chilli CoovaChilli init";
		$f[]="#";
		$f[]="# chkconfig: 2345 65 35";
		$f[]="# description: CoovaChilli";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:       chilli";
		$f[]="# Required-Start: network ";
		$f[]="# Should-Start: ";
		$f[]="# Required-Stop:  network";
		$f[]="# Should-Stop: ";
		$f[]="# Default-Start:  2 3 5";
		$f[]="# Default-Stop:";
		$f[]="# Description:    CoovaChilli access controller";
		$f[]="### END INIT INFO";
		$f[]="";
		$f[]="[ -f /usr/sbin/chilli ] || exit 0";
		$f[]="ENABLED=$EnableChilli";
		$f[]="	if [ \$ENABLED -eq 0 ]";
		$f[]="	then";
		$f[]="		echo \"Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}  is disabled\"";
		$f[]="		exit 0";
		$f[]="	fi";		
		
		$f[]="";
		$f[]=". /etc/chilli/functions";
		$f[]="";
		$f[]="CONFIG=/etc/chilli.conf";
		$f[]="pidfile=/var/run/chilli.pid";
		$f[]="";
		$f[]="[ -f \$CONFIG ] || {";
		$f[]="    echo \"\$CONFIG Not found\"";
		$f[]="    exit 0";
		$f[]="}";
		$f[]="";
		$f[]="check_required";
		$f[]="";
		$f[]="RETVAL=0";
		$f[]="prog=\"chilli\"";
		$f[]="";
		$f[]="case \$1 in";
		$f[]="start)";
		$f[]="";
		$f[]="\t$php5 ". __FILE__." --build";
		$f[]="\twriteconfig";
		$f[]="\tradiusconfig";
		$f[]="";
		$f[]="\ttest \${HS_ADMINTERVAL:-0} -gt 0 && {	";
		$f[]="\t\t(crontab -l 2>&- | grep -v \$0";
		$f[]="\t\techo \"*/\$HS_ADMINTERVAL * * * * \$0 radconfig\"";
		$f[]="\t\t) | crontab - 2>&-";
		$f[]="\t}";
		$f[]="";
		$f[]="\t$ifconfig \$HS_LANIF 0.0.0.0";
		$f[]="\t$php5 ". __FILE__." --start";
		$f[]="\t/etc/init.d/freeradius start";
		$f[]="";
		$f[]=";;";
		$f[]="    ";
		$f[]="radconfig)";
		$f[]="\t[ -e \$MAIN_CONF ] || writeconfig";
		$f[]="\tradiusconfig";
		$f[]=";;";
		$f[]="";
		$f[]="reconfigure)";
		$f[]="\twriteconfig";
		$f[]="\tradiusconfig";
		$f[]=";;";
		$f[]="";				
		$f[]="reload)";
		$f[]="\twriteconfig";
		$f[]="\tradiusconfig";		
		$f[]="\tkillall -HUP chilli";
		$f[]=";;";
		$f[]="";
		$f[]="restart)";
		$f[]="\t\$0 stop";
		$f[]="\tsleep 1";
		$f[]="\t\$0 start";
		$f[]="\tRETVAL=\$?";
		$f[]=";;";
		$f[]="    ";
		$f[]="stop)";
		$f[]="";
		$f[]="\techo \"Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}\"";
		$f[]="\t$php5 ". __FILE__." --stop";		
		$f[]="\tcrontab -l 2>&- | grep -v \$0 | crontab -";
		$f[]=";;";
		$f[]="    ";
		$f[]="*)";
		$f[]="\techo \"Usage: \$0 {start|stop|restart|reload|radconfig|reconfigure}\"";
		$f[]="\texit 1";
		$f[]="esac";
		$f[]="";
		$f[]="exit \$?";
		$f[]="";
		
		$INITD_PATH="/etc/init.d/chilli";
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Writing $INITD_PATH with new config\n";
		@file_put_contents($INITD_PATH, @implode("\n", $f));
		
		@chmod($INITD_PATH,0755);
		
		if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
		}
		
		if(is_file('/sbin/chkconfig')){
				shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 2345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}		
		
		
	}

function chilli_conup($argvA){
	
	
	$sqlCR="CREATE TABLE IF NOT EXISTS `hotspot_ident` (
				`ipaddr` VARCHAR( 90 ) NOT NULL PRIMARY KEY ,
				 `username` VARCHAR(128) NOT NULL,
				  `MAC` VARCHAR(128) NOT NULL,
				  zDate datetime NOT NULL,
				  KEY `username` (`username`),
				  UNIQUE KEY `MAC` (`MAC`),
				  KEY `zDate` (`zDate`)
				)  ENGINE = MYISAM;";
	
	$USER_NAME=mysql_escape_string2($_ENV["USER_NAME"]);
	$FRAMED_IP_ADDRESS=$_ENV["FRAMED_IP_ADDRESS"];
	$MAC=$_ENV["CALLING_STATION_ID"];
	$MAC=strtolower($MAC);
	$MAC=str_replace("-", ":", $MAC);
	
	$zDate=date("Y-m-d H:i:s");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL($sqlCR);
	if(!$q->ok){events($q->mysql_error);}
	$q->QUERY_SQL("DELETE FROM hotspot_ident WHERE `ipaddr`='$FRAMED_IP_ADDRESS'");
	$q->QUERY_SQL("DELETE FROM hotspot_ident WHERE `MAC`='$MAC'");
	$q->QUERY_SQL("INSERT IGNORE INTO `hotspot_ident` (ipaddr,username,MAC,zDate) VALUES ('$FRAMED_IP_ADDRESS','$USER_NAME','$MAC','$zDate')");
	if(!$q->ok){events($q->mysql_error);}
	
	
	
	
}


function up_sh($ChilliConf=array()){
	if(count($ChilliConf)<5){
		$sock=new sockets();
		$ChilliConf=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ChilliConf")));
		$ChilliConf=GetInterfaceArray($ChilliConf);
	}
	$squidport=intval($ChilliConf["SQUID_HTTP_PORT"]);
	
$f[]="#!/bin/sh";
$f[]="";
$f[]="TUNTAP=\$(basename \$DEV)";
$f[]="UNDO_FILE=/var/run/chilli.\$TUNTAP.sh";
$f[]="";
$f[]=". /etc/chilli/functions";
$f[]="";
$f[]="[ -e \"\$UNDO_FILE\" ] && sh \$UNDO_FILE 2>/dev/null";
$f[]="rm -f \$UNDO_FILE 2>/dev/null";
$f[]="";
$f[]="ipt() {";
$f[]="    opt=\$1; shift";
$f[]="    echo \"iptables -D \$*\" >> \$UNDO_FILE";
$f[]="    iptables \$opt \$*";
$f[]="}";
$f[]="";
$f[]="ipt_in() {";
$f[]="    ipt -I INPUT -i \$TUNTAP \$*";
$f[]="}";
$f[]="";
$f[]="if [ -n \"\$TUNTAP\" ]";
$f[]="then";
$f[]="    # ifconfig \$TUNTAP mtu \$MTU";
$f[]="    if [ \"\$KNAME\" != \"\" ]";
$f[]="    then";
$f[]="	ipt -I FORWARD -i \$DHCPIF -m coova --name \$KNAME -j ACCEPT ";
$f[]="	ipt -I FORWARD -o \$DHCPIF -m coova --name \$KNAME --dest -j ACCEPT";
$f[]="	ipt -I FORWARD -i \$TUNTAP -j ACCEPT";
$f[]="	ipt -I FORWARD -o \$TUNTAP -j ACCEPT";
$f[]="	[ -n \"\$DHCPLISTEN\" ] && ifconfig \$DHCPIF \$DHCPLISTEN";
$f[]="    else";
$f[]="	if [ \"\$LAYER3\" != \"1\" ]";
$f[]="	then";
$f[]="	    [ -n \"\$UAMPORT\" -a \"\$UAMPORT\" != \"0\" ] && \\";
$f[]="		ipt_in -p tcp -m tcp --dport \$UAMPORT --dst \$ADDR -j ACCEPT";
$f[]="	    ";
$f[]="	    [ -n \"\$UAMUIPORT\" -a \"\$UAMUIPORT\" != \"0\" ] && \\";
$f[]="		ipt_in -p tcp -m tcp --dport \$UAMUIPORT --dst \$ADDR -j ACCEPT";
$f[]="	    ";
$f[]="	    [ -n \"\$HS_TCP_PORTS\" ] && {";
$f[]="		for port in \$HS_TCP_PORTS; do";
$f[]="		    ipt_in -p tcp -m tcp --dport \$port --dst \$ADDR -j ACCEPT";
$f[]="		done";
$f[]="	    }";
$f[]="	    ";
$f[]="	    ipt_in -p udp -d 255.255.255.255 --destination-port 67:68 -j ACCEPT";
$f[]="	    ipt_in -p udp -d \$ADDR --destination-port 67:68 -j ACCEPT";
$f[]="	    ipt_in -p udp --dst \$ADDR --dport 53 -j ACCEPT";
$f[]="	    ipt_in -p icmp --dst \$ADDR -j ACCEPT";
$f[]="	    ";
$f[]="	    ipt -A INPUT -i \$TUNTAP --dst \$ADDR -j DROP";
$f[]="	    ";
$f[]="	    ipt -I INPUT   -i \$DHCPIF -j DROP";
$f[]="	fi";
$f[]="	";
$f[]="	ipt -I FORWARD -i \$DHCPIF -j DROP";
$f[]="	ipt -I FORWARD -o \$DHCPIF -j DROP";
$f[]="	";
$f[]="	ipt -I FORWARD -i \$TUNTAP -j ACCEPT";
$f[]="	ipt -I FORWARD -o \$TUNTAP -j ACCEPT";
$f[]="	";
$f[]="        # Help out conntrack to not get confused";
$f[]="        # (stops masquerading from working)";
$f[]="        #ipt -I PREROUTING -t raw -j NOTRACK -i \$DHCPIF";
$f[]="        #ipt -I OUTPUT -t raw -j NOTRACK -o \$DHCPIF";
$f[]="	";
$f[]="        # Help out MTU issues with PPPoE or Mesh";
$f[]="	ipt -I FORWARD -p tcp -m tcp --tcp-flags SYN,RST SYN -j TCPMSS --clamp-mss-to-pmtu";
$f[]="	ipt -I FORWARD -t mangle -p tcp -m tcp --tcp-flags SYN,RST SYN -j TCPMSS --clamp-mss-to-pmtu";
$f[]="	";
$f[]="	[ \"\$HS_LAN_ACCESS\" != \"on\" -a \"\$HS_LAN_ACCESS\" != \"allow\" ] && \\";
$f[]="	    ipt -I FORWARD -i \$TUNTAP \\! -o \$HS_WANIF -j DROP";
$f[]="	";
$f[]="	[ \"\$HS_LOCAL_DNS\" = \"on\" ] && \\";
$f[]="	    ipt -I PREROUTING -t nat -i \$TUNTAP -p udp --dport 53 -j DNAT --to-destination \$ADDR";
$f[]="    fi";
$f[]="fi";
$f[]="";
$f[]="# site specific stuff optional";
$f[]="[ -e /etc/chilli/ipup.sh ] && . /etc/chilli/ipup.sh";
$f[]="";
@file_put_contents("/etc/chilli/up.sh", @implode("\n", $f));
@chmod("/etc/chilli/up.sh",0755);


$f=array();
$f[]="#!/bin/sh";
$f[]="# Custom rules for Hotspot";
$f[]="# TRANS PROXY";
$f[]="#    ipt -I PREROUTING -t nat -p tcp -s 10.1.0.0/24 -d 10.1.0.1 --dport $squidport -j DROP";
$f[]="#    ipt -I PREROUTING -t nat -i \$IF -p tcp -s 10.1.0.0/24 -d ! 10.1.0.1 --dport 80 -j REDIRECT --to $squidport";
$f[]="";
$f[]="    # Redirect to Squid proxy (drop direct attempts to proxy)";
$f[]="    ipt -I PREROUTING -t mangle -p tcp -s \$NET/\$MASK -d \$ADDR --dport $squidport -j DROP";
$f[]="    ipt -I PREROUTING -t nat -i {$ChilliConf["HS_LANIF"]} -p tcp -s \$NET/\$MASK ! -d \$ADDR --dport 80 -j REDIRECT --to $squidport";
$f[]="    # Look at using this rule?";
$f[]="    # ipt -I PREROUTING  -t nat -i {$ChilliConf["HS_LANIF"]} -p tcp -s \$NET/\$MASK ! -d  \$ADDR --dport 80 -j DNAT --to 192.168.8.22:3128";
$f[]="    ";
$f[]="    # Redirect DNS to local server # Coova Chilli seems to take care of this";
$f[]="#    ipt -I PREROUTING -t nat -i {$ChilliConf["HS_LANIF"]} -p tcp -s \$NET/\$MASK ! -d  \$ADDR --dport 53 -j REDIRECT --to 53";
$f[]="#    ipt -I PREROUTING -t nat -i {$ChilliConf["HS_LANIF"]} -p udp -s \$NET/\$MASK ! -d \$ADDR --dport 53 -j REDIRECT --to 53    ";
$f[]="# MASQUERADE";
$f[]="    ipt -I POSTROUTING -t nat -o {$ChilliConf["HS_LANIF"]} -j MASQUERADE";
$f[]="";
@file_put_contents("/etc/chilli/ipup.sh", @implode("\n", $f));
@chmod("/etc/chilli/ipup.sh",0755);

}

function events($text){
	
	$LOG_SEV=LOG_INFO;
	openlog("coova-chilli", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
	
}
	
?>