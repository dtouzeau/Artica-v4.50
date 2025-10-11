<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="SNMP Daemon";
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--user"){$GLOBALS["OUTPUT"]=true;createUser();exit();}
if($argv[1]=="--dnsdist"){$GLOBALS["OUTPUT"]=true;DNSDIST_EXTEND();exit();}


if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;$GLOBALS["RECONFIGURE"]=true;build();exit();}



function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());

    build_progress_restart(10,"{stopping_service}");
	stop(true);
	sleep(1);
    build_progress_restart(50,"{reconfiguring}");
	build();

    build_progress_restart(60,"{starting_service}");
	if(!start(true)){
        build_progress_restart(110,"{starting_service} {failed}");
        return;
    }
    build_progress_restart(100,"{starting_service} {success}");
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("snmpd");

	if(!is_file($Masterbin)){
		if(!is_file("/usr/sbin/snmpd")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, not installed\n";}
			return false;
		}
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return true;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return true;
	}
	$EnableSNMPD=$sock->GET_INFO("EnableSNMPD");
	if(!is_numeric($EnableSNMPD)){$EnableSNMPD=0;}
	

	if($EnableSNMPD==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableSNMPD)\n";}
		return false;
	}
	
	$ips=$unix->NETWORK_ALL_INTERFACES(true);
	foreach ($ips as $ip=>$line){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} listen $ip\n";}

		
	}
	
	if(!is_file("/usr/bin/download-mibs")){
		$unix->DEBIAN_INSTALL_PACKAGE("snmp-mibs-downloader");
		if(is_file("/usr/bin/download-mibs")){shell_exec("/usr/bin/download-mibs");}
	}
    @mkdir("/usr/share/mibs",0755,true);
	$chown=$unix->find_program("chown");
	shell_exec("$chown -R root:root /var/lib/snmp");
	@unlink("/var/log/snmpd.log");
	@touch("/var/log/snmpd.log");

    $SNMPDagentAddress=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDagentAddress"));
    $SNMPDInterfaceAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDInterfaceAddress"));

    $listen=null;
    if($SNMPDInterfaceAddress<>null) {
        $tt=array();
        $ipaddr = $unix->InterfaceToIPv4($SNMPDInterfaceAddress);
        if($ipaddr<>null){
            $tt[]=$ipaddr;
        }
        if($SNMPDagentAddress>0){
            $tt[]=$SNMPDagentAddress;
        }
        if(count($tt)>0){
            $listen=" -x ".@implode(":",$tt);
        }
    }


	$cmd="$Masterbin -c /etc/snmp/snmpd.conf -Lf /var/log/snmpd.log -u root -g root{$listen}  -p /var/run/snmpd.pid";

	echo "$cmd\n";
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	build();
	createUser();
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
	}

    return false;
}

function stop($aspid=false){
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

function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("snmpd"));
}

function PID_PATH(){
	return "/var/run/snmpd.pid";
}

function install(){
	
	$unix=new unix();
	$Masterbin=$unix->find_program("snmpd");
	if(!is_file($Masterbin)){
		build_progress(110,"{failed}: SNMPD not found");
		return;
	}
	
	if(!is_file("/usr/bin/download-mibs")){
		build_progress(10,"{installing}");
		$unix->DEBIAN_INSTALL_PACKAGE("snmp-mibs-downloader");
		if(is_file("/usr/bin/download-mibs")){
			shell_exec("/usr/bin/download-mibs");
		}
	}

	install_service();
	build_progress(40,"{configure}");
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(is_file($squidbin)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableProxyInSNMPD", 1);
		$php=$unix->LOCATE_PHP5_BIN();
		system("$php /usr/share/artica-postfix/exec.squid.global.access.php --snmp --noexternal-scripts");
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSNMPD", 1);
	build();
	build_progress(50,"{starting_service}");
	start(true);
	build_progress(100,"{success}");
}

function uninstall(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSNMPD", 0);
	build_progress(50,"{remove_service}");
	remove_service("/etc/init.d/snmpd");
	build_progress(100,"{success}");
	
}

function build_progress($pourc,$text){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/snmpd.service.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	
}
function build_progress_restart($pourc,$text){

    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/restart-snmpd.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
}


function build_progress_install($text,$prc){build_progress($prc,$text);}


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

function install_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$daemonbin=$unix->find_program("snmpd");
	$INITD_PATH="/etc/init.d/snmpd";
	
		if(!is_file($daemonbin)){return;}
		$f[]="#!/bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          snmpd";
		$f[]="# Required-Start:    \$local_fs \$syslog";
		$f[]="# Required-Stop:     \$local_fs";
		$f[]="# Should-Start:";
		$f[]="# Should-Stop:";
		$f[]="# Default-Start:     3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: SNMPD daemon";
		$f[]="# chkconfig: 2345 11 89";
		$f[]="# description: Extensible, configurable SNMP daemon";
		$f[]="### END INIT INFO";
            $f[]="export MIBS";
		$f[]="export MIBS=/usr/share/snmp/mibs";
		$f[]="case \"\$1\" in";
		$f[]=" start)";
		$f[]="   $php /usr/share/artica-postfix/exec.snmpd.php --start \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]="  stop)";
		$f[]="   $php /usr/share/artica-postfix/exec.snmpd.php --stop \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]=" restart)";
		$f[]="   $php /usr/share/artica-postfix/exec.snmpd.php --restart \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]=" reload)";
		$f[]="   $php /usr/share/artica-postfix/exec.snmpd.php --restart \$2 \$3";
		$f[]="	 exit 0";
		$f[]="    ;;";
		$f[]="";
		$f[]="  *)";
		$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
		$f[]="    exit 1";
		$f[]="    ;;";
		$f[]="esac";
		$f[]="exit 0\n";
	
	
		echo "SNMPD: [INFO] Writing $INITD_PATH with new config\n";
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

	

function createUser(){
	
	$SNMPDUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDUsername"));
	$SNMPDPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDPassword"));
    $SNMPDPassphrase=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDPassphrase"));
	if($SNMPDUsername==null){return;}
	
	$f=explode("\n",@file_get_contents("/var/lib/snmp/snmpd.conf"));
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^(.+?)SHA\s+\".*?AES#", $line)){continue;}
		$TT[]=$line;
		
		
	}
	
	
	if($SNMPDUsername==null){
		@file_put_contents("/var/lib/snmp/snmpd.conf", @implode("\n", $TT));
		return;
	}
	
	$TT[]="createUser $SNMPDUsername SHA \"$SNMPDPassword\" AES \"$SNMPDPassphrase\"";
	@file_put_contents("/var/lib/snmp/snmpd.conf", @implode("\n", $TT));
}

function build(){
	$unix=new unix();
	$sock=new sockets();
    SNMPv2_PDU();
    DHCPD_EXTEND();
    UNBOUND_EXTEND();
    UNBOUND_EXTEND2();
    NTPD_EXTEND();
	$SNMPDNetwork=$sock->GET_INFO("SNMPDNetwork");
	if($SNMPDNetwork==null){$SNMPDNetwork="default";}
	$SNMPDCommunity=$sock->GET_INFO("SNMPDCommunity");
	if($SNMPDCommunity==null){$SNMPDCommunity="public";}
	$EnableProxyInSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableProxyInSNMPD"));
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	$WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
	$SNMPDOrganization=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDOrganization"));
	$SNMPDContact=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDContact"));
	$SNMPDUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDUsername"));
	if($SNMPDOrganization==null){$SNMPDOrganization=$WizardSavedSettings["organization"];}
	if($SNMPDContact==null){$SNMPDContact=$WizardSavedSettings["mail"];}
    $SNMPDDisablev2=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SNMPDDisablev2"));
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));


if($SNMPDDisablev2==0) {
    $f[] = "rocommunity $SNMPDCommunity  127.0.0.1";
    $ips = $unix->NETWORK_ALL_INTERFACES(true);
    foreach ($ips as $ip => $line) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Allow $ip\n";
        }
        $f[] = "rocommunity $SNMPDCommunity  $ip";
    }

    if(strpos($SNMPDNetwork,",")>0){
        $zSNMPDNetwork=explode(",",$SNMPDNetwork);
        foreach ($zSNMPDNetwork as $ipaddr){
            $ipClass=new IP();
            if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
            $f[] = "rwcommunity $SNMPDCommunity  $ipaddr";
        }
    }else{
        $f[] = "rwcommunity $SNMPDCommunity  $SNMPDNetwork";
    }


}
if($SNMPDUsername<>null){
	$f[]="rouser   $SNMPDUsername";
    $f[]="iquerySecName   $SNMPDUsername";
   // $f[]="defaultMonitors          yes";
}
$f[]="sysLocation    $SNMPDOrganization";
$f[]="sysContact     $SNMPDContact";
$f[]="sysServices    72";


$squid=$unix->LOCATE_SQUID_BIN();
if(is_file($squid)){
	if($EnableProxyInSNMPD==1) {
        if ($SQUIDEnable == 1) {
            $SquidSNMPPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidSNMPPort"));
            if ($SquidSNMPPort == 0) {
                $SquidSNMPPort = 3401;
            }
            if (is_file("/usr/share/squid3/mib.txt")) {
                $moib = " -m /usr/share/squid3/mib.txt";
            }
            $f[] = "proxy$moib -v 2c -c $SNMPDCommunity 127.0.0.1:$SquidSNMPPort .1.3.6.1.4.1.3495.1";
            //$f[]="proxy$moib -v 1 -c $SNMPDCommunity 127.0.0.1:$SquidSNMPPort .1.3.6.1.4.1.3495.1";
        }
    }
}

$EnableDHCPServer   =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDHCPServer"));
$EnableDNSDist      =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));
$UnboundEnabled     =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
$NTPDEnabled        =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NTPDEnabled"));

if($EnableDHCPServer==1){
    $f[]="extend dhcpstats /etc/snmp/dhcp-status.sh";
}

if($EnableDNSDist == 1){
    $f[]="extend powerdns-dnsdist /usr/sbin/artica-phpfpm-service -dnsfw-snmp";
}

if($UnboundEnabled == 1){
    if($EnableUnBoundSNMPD==0) {
        $f[] = "extend unbound /etc/snmp/unbound-status.sh";
    }else{
        $f[]="extend  .1.3.6.1.3.1983.1.1 cache_hits /bin/cat /etc/unbound/statistics/cache_hits";
        $f[]="extend  .1.3.6.1.3.1983.1.2 memory_usage /bin/cat /etc/unbound/statistics/memory_usage";
        $f[]="extend  .1.3.6.1.3.1983.1.3 queues_by_type /bin/cat /etc/unbound/statistics/queues_by_type";
        $f[]="extend  .1.3.6.1.3.1983.1.4 answers_to_queries /bin/cat /etc/unbound/statistics/answers_to_queries";
        $f[]="extend  .1.3.6.1.3.1983.1.5 histogram /bin/cat /etc/unbound/statistics/histogram";
        $f[]="extend  .1.3.6.1.3.1983.1.6 queues_by_flags /bin/cat /etc/unbound/statistics/queues_by_flags";
        $f[]="extend  .1.3.6.1.3.1983.1.7 requestlist /bin/cat /etc/unbound/statistics/requestlist";
        $f[]="extend  .1.3.6.1.3.1983.1.8 dns_service /bin/cat /etc/unbound/statistics/unbound_status";
        $f[]="extend  .1.3.6.1.3.1983.1.9 filter_service /bin/cat /etc/unbound/statistics/dnsfilterd_status";
    }
}
if($NTPDEnabled == 1){
    $f[]="extend ntp-server /etc/snmp/ntp-server.sh";
}



$f[]="disk       /     10000";
$f[]="disk       /var  5%";
$f[]="load   12 10 5";
$f[]="master          agentx";
$f[]="#agentXSocket    tcp:localhost:705";

    if(!is_dir("/etc/snmp")) {
        @mkdir("/etc/snmp", 0755, true);
    }
	@file_put_contents("/etc/snmp/snmpd.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/snmp/snmpd.conf done\n";}
	
}

function SNMPv2_PDU(){

if(!is_dir("/var/lib/snmp/mibs/ietf")){@mkdir("/var/lib/snmp/mibs/ietf",0755,true);}

    $f[]="SNMPv2-PDU DEFINITIONS ::= BEGIN";
    $f[]="";
    $f[]="ObjectName ::= OBJECT IDENTIFIER";
    $f[]="";
    $f[]="ObjectSyntax ::= CHOICE {";
    $f[]="      simple           SimpleSyntax,";
    $f[]="      application-wide ApplicationSyntax }";
    $f[]="";
    $f[]="SimpleSyntax ::= CHOICE {";
    $f[]="      integer-value   INTEGER (-2147483648..2147483647),";
    $f[]="      string-value    OCTET STRING (SIZE (0..65535)),";
    $f[]="      objectID-value  OBJECT IDENTIFIER }";
    $f[]="";
    $f[]="ApplicationSyntax ::= CHOICE {";
    $f[]="      ipAddress-value        IpAddress,";
    $f[]="      counter-value          Counter32,";
    $f[]="      timeticks-value        TimeTicks,";
    $f[]="      arbitrary-value        Opaque,";
    $f[]="      big-counter-value      Counter64,";
    $f[]="      unsigned-integer-value Unsigned32 }";
    $f[]="";
    $f[]="IpAddress ::= [APPLICATION 0] IMPLICIT OCTET STRING (SIZE (4))";
    $f[]="";
    $f[]="Counter32 ::= [APPLICATION 1] IMPLICIT INTEGER (0..4294967295)";
    $f[]="";
    $f[]="Unsigned32 ::= [APPLICATION 2] IMPLICIT INTEGER (0..4294967295)";
    $f[]="";
    $f[]="Gauge32 ::= Unsigned32";
    $f[]="";
    $f[]="TimeTicks ::= [APPLICATION 3] IMPLICIT INTEGER (0..4294967295)";
    $f[]="";
    $f[]="Opaque ::= [APPLICATION 4] IMPLICIT OCTET STRING";
    $f[]="";
    $f[]="Counter64 ::= [APPLICATION 6]";
    $f[]="              IMPLICIT INTEGER (0..18446744073709551615)";
    $f[]="";
    $f[]="-- protocol data units";
    $f[]="";
    $f[]="PDUs ::= CHOICE {";
    $f[]="     get-request      GetRequest-PDU,";
    $f[]="     get-next-request GetNextRequest-PDU,";
    $f[]="     get-bulk-request GetBulkRequest-PDU,";
    $f[]="     response         Response-PDU,";
    $f[]="     set-request      SetRequest-PDU,";
    $f[]="     inform-request   InformRequest-PDU,";
    $f[]="     snmpV2-trap      SNMPv2-Trap-PDU,";
    $f[]="     report           Report-PDU }";
    $f[]="";
    $f[]="-- PDUs";
    $f[]="";
    $f[]="GetRequest-PDU ::= [0] IMPLICIT PDU";
    $f[]="";
    $f[]="GetNextRequest-PDU ::= [1] IMPLICIT PDU";
    $f[]="";
    $f[]="Response-PDU ::= [2] IMPLICIT PDU";
    $f[]="";
    $f[]="SetRequest-PDU ::= [3] IMPLICIT PDU";
    $f[]="";
    $f[]="-- [4] is obsolete";
    $f[]="";
    $f[]="GetBulkRequest-PDU ::= [5] IMPLICIT BulkPDU";
    $f[]="";
    $f[]="InformRequest-PDU ::= [6] IMPLICIT PDU";
    $f[]="";
    $f[]="SNMPv2-Trap-PDU ::= [7] IMPLICIT PDU";
    $f[]="";
    $f[]="--   Usage and precise semantics of Report-PDU are not defined";
    $f[]="--   in this document.  Any SNMP administrative framework making";
    $f[]="--   use of this PDU must define its usage and semantics.";
    $f[]="";
    $f[]="Report-PDU ::= [8] IMPLICIT PDU";
    $f[]="";
    $f[]="-- max-bindings INTEGER ::= 2147483647";
    $f[]="";
    $f[]="PDU ::= SEQUENCE {";
    $f[]="        request-id INTEGER (-214783648..214783647),";
    $f[]="        error-status                -- sometimes ignored";
    $f[]="            INTEGER {";
    $f[]="                noError(0),";
    $f[]="                tooBig(1),";
    $f[]="                noSuchName(2),      -- for proxy compatibility";
    $f[]="                badValue(3),        -- for proxy compatibility";
    $f[]="                readOnly(4),        -- for proxy compatibility";
    $f[]="                genErr(5),";
    $f[]="                noAccess(6),";
    $f[]="                wrongType(7),";
    $f[]="                wrongLength(8),";
    $f[]="                wrongEncoding(9),";
    $f[]="                wrongValue(10),";
    $f[]="                noCreation(11),";
    $f[]="                inconsistentValue(12),";
    $f[]="                resourceUnavailable(13),";
    $f[]="                commitFailed(14),";
    $f[]="                undoFailed(15),";
    $f[]="                authorizationError(16),";
    $f[]="                notWritable(17),";
    $f[]="                inconsistentName(18)";
    $f[]="            },";
    $f[]="        error-index                 -- sometimes ignored";
    $f[]="            INTEGER (0..2147483647),";
    $f[]="        variable-bindings           -- values are sometimes ignored";
    $f[]="            VarBindList";
    $f[]="    }";
    $f[]="";
    $f[]="BulkPDU ::=                         -- must be identical in";
    $f[]="    SEQUENCE {                      -- structure to PDU";
    $f[]="        request-id      INTEGER (-214783648..214783647),";
    $f[]="        non-repeaters   INTEGER (0..2147483647),";
    $f[]="        max-repetitions INTEGER (0..2147483647),";
    $f[]="        variable-bindings           -- values are ignored";
    $f[]="            -- VarBindList";
    $f[]="	    SEQUENCE (SIZE (0..2147483647)) OF VarBind";
    $f[]="    }";
    $f[]="";
    $f[]="-- variable binding";
    $f[]="";
    $f[]="VarBind ::= SEQUENCE {";
    $f[]="        name ObjectName,";
    $f[]="        CHOICE {";
    $f[]="            value          ObjectSyntax,";
    $f[]="            unSpecified    NULL,    -- in retrieval requests";
    $f[]="";
    $f[]="                                    -- exceptions in responses";
    $f[]="            noSuchObject   [0] IMPLICIT NULL,";
    $f[]="            noSuchInstance [1] IMPLICIT NULL,";
    $f[]="            endOfMibView   [2] IMPLICIT NULL";
    $f[]="        }";
    $f[]="    }";
    $f[]="";
    $f[]="-- variable-binding list";
    $f[]="";
    $f[]="-- VarBindList ::= SEQUENCE (SIZE (0..2147483647)) OF VarBind";
    $f[]="";
    $f[]="END";

    @file_put_contents("/var/lib/snmp/mibs/ietf/SNMPv2-PDU",@implode("\n",$f));
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /var/lib/snmp/mibs/ietf/SNMPv2-PDU done\n";
}
function DHCPD_EXTEND(){

    $unix=new unix();
    $cat=$unix->find_program("cat");
    $grep=$unix->find_program("grep");
    $tr=$unix->find_program("tr");
    $sed=$unix->find_program("sed");
    $sort=$unix->find_program("sort");
    $wc=$unix->find_program("wc");
    $chmod=$unix->find_program("chmod");
    $f[]="#!/bin/bash";
    $f[]="################################################################";
    $f[]="# copy this script to somewhere like /opt and make chmod +x it #";
    $f[]="# edit your snmpd.conf add the below line and restart snmpd    #";
    $f[]="# extend dhcpstats /opt/dhcp-status.sh                         #";
    $f[]="################################################################ ";
    $f[]="FILE_DHCP='/var/lib/dhcp3/dhcpd.leases'";
    $f[]="BIN_CAT='$cat'";
    $f[]="BIN_GREP='$grep'";
    $f[]="BIN_TR='$tr'";
    $f[]="BIN_SED='$sed'";
    $f[]="BIN_SORT='$sort'";
    $f[]="BIN_WC='$wc'";
    $f[]="";
    $f[]="CONFIGFILE=/etc/snmp/dhcp-status.conf";
    $f[]="if [ -f \$CONFIGFILE ] ; then";
    $f[]="    . \$CONFIGFILE";
    $f[]="fi";
    $f[]="";
    $f[]="DHCP_LEASES='^lease'";
    $f[]="DHCP_ACTIVE='^lease|binding state active'";
    $f[]="DHCP_EXPIRED='^lease|binding state expired'";
    $f[]="DHCP_RELEASED='^lease|binding state released'";
    $f[]="DHCP_ABANDONED='^lease|binding state abandoned'";
    $f[]="DHCP_RESET='^lease|binding state reset'";
    $f[]="DHCP_BOOTP='^lease|binding state bootp'";
    $f[]="DHCP_BACKUP='^lease|binding state backup'";
    $f[]="DHCP_FREE='^lease|binding state free'";
    $f[]="NO_ERROR='[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3} binding'";
    $f[]="";
    $f[]="\$BIN_CAT \$FILE_DHCP | \$BIN_GREP \$DHCP_LEASES | \$BIN_SORT -u | \$BIN_WC -l";
    $f[]="";
    $f[]="for state in \"\$DHCP_ACTIVE\" \"\$DHCP_EXPIRED\" \"\$DHCP_RELEASED\" \"\$DHCP_ABANDONED\" \"\$DHCP_RESET\" \"\$DHCP_BOOTP\" \"\$DHCP_BACKUP\" \"\$DHCP_FREE\"";
    $f[]="do";
    $f[]="        \$BIN_GREP -E \"\$state\"  \$FILE_DHCP | \$BIN_TR '\n' '|' | \$BIN_SED 's/ {| //g' | \$BIN_TR '|' '\n' | \$BIN_GREP -E \"\$NO_ERROR\" | \$BIN_SORT -u | \$BIN_WC -l";
    $f[]="done";
    $f[]="";

    @file_put_contents("/etc/snmp/dhcp-status.sh",@implode("\n",$f));
    shell_exec("$chmod +x /etc/snmp/dhcp-status.sh");
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/snmp/dhcp-status.sh done\n";


}
function NTPD_EXTEND(){
    $unix=new unix();
    $ntpq=$unix->find_program("ntpq");
    $ntpd=$unix->find_program("ntpd");
    $ntpdc=$unix->find_program("ntpdc");
    $grep=$unix->find_program("grep");
    $tr=$unix->find_program("tr");
    $cut=$unix->find_program("cut");
    $sed=$unix->find_program("sed");
    $awk=$unix->find_program("awk");
    $f[]="#!/bin/sh";
    $f[]="# Please make sure the paths below are correct.";
    $f[]="# Alternatively you can put them in \$0.conf, meaning if you've named";
    $f[]="# this script ntp-client.sh then it must go in ntp-client.sh.conf .";
    $f[]="#";
    $f[]="# NTPQV output version of \"ntpq -c rv\" ";
    $f[]="# p1 DD-WRT and some other outdated linux distros";
    $f[]="# p11 FreeBSD 11 and any linux distro that is up to date";
    $f[]="#";
    $f[]="# If you are unsure, which to set, run this script and make sure that";
    $f[]="# the JSON output variables match that in \"ntpq -c rv\".";
    $f[]="#";
    $f[]="BIN_NTPD='$ntpd'";
    $f[]="BIN_NTPQ='$ntpq'";
    $f[]="BIN_NTPDC='$ntpdc'";
    $f[]="BIN_GREP='$grep'";
    $f[]="BIN_TR='$tr'";
    $f[]="BIN_CUT='$cut'";
    $f[]="BIN_SED=\"$sed\"";
    $f[]="BIN_AWK='$awk'";
    $f[]="NTPQV=\"p11\"";
    $f[]="################################################################";
    $f[]="# Don't change anything unless you know what are you doing     #";
    $f[]="################################################################";
    $f[]="CONFIG=\$0\".conf\"";
    $f[]="if [ -f \$CONFIG ]; then";
    $f[]="    . \$CONFIG";
    $f[]="fi";
    $f[]="VERSION=1";
    $f[]="";
    $f[]="STRATUM=`\$BIN_NTPQ -c rv | \$BIN_GREP -Eow \"stratum=[0-9]+\" | \$BIN_CUT -d \"=\" -f 2`";
    $f[]="";
    $f[]="# parse the ntpq info that requires version specific info";
    $f[]="NTPQ_RAW=`\$BIN_NTPQ -c rv | \$BIN_GREP jitter | \$BIN_SED 's/[[:alpha:]=,_]/ /g'`";
    $f[]="if [ \$NTPQV = \"p11\" ]; then";
    $f[]="	OFFSET=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$3}'`";
    $f[]="	FREQUENCY=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$4}'`";
    $f[]="	SYS_JITTER=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$5}'`";
    $f[]="	CLK_JITTER=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$6}'`";
    $f[]="	CLK_WANDER=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$7}'`";
    $f[]="fi";
    $f[]="if [ \$NTPQV = \"p1\" ]; then";
    $f[]="	OFFSET=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$2}'`";
    $f[]="	FREQUENCY=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$3}'`";
    $f[]="	SYS_JITTER=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$4}'`";
    $f[]="	CLK_JITTER=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$5}'`";
    $f[]="	CLK_WANDER=`echo \$NTPQ_RAW | \$BIN_AWK -F ' ' '{print \$6}'`";
    $f[]="fi";
    $f[]="";
    $f[]="VER=`\$BIN_NTPD --version`";
    $f[]="if [ \"\$VER\" = '4.2.6p5' ]; then";
    $f[]="  USECMD=`echo \$BIN_NTPDC -c iostats`";
    $f[]="else";
    $f[]="  USECMD=`echo \$BIN_NTPQ -c iostats localhost`";
    $f[]="fi";
    $f[]="CMD2=`\$USECMD | \$BIN_TR -d ' ' | \$BIN_CUT -d : -f 2 | \$BIN_TR '\n' ' '`";
    $f[]="";
    $f[]="TIMESINCERESET=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$1}'`";
    $f[]="RECEIVEDBUFFERS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$2}'`";
    $f[]="FREERECEIVEBUFFERS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$3}'`";
    $f[]="USEDRECEIVEBUFFERS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$4}'`";
    $f[]="LOWWATERREFILLS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$5}'`";
    $f[]="DROPPEDPACKETS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$6}'`";
    $f[]="IGNOREDPACKETS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$7}'`";
    $f[]="RECEIVEDPACKETS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$8}'`";
    $f[]="PACKETSSENT=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$9}'`";
    $f[]="PACKETSENDFAILURES=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$10}'`";
    $f[]="INPUTWAKEUPS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$11}'`";
    $f[]="USEFULINPUTWAKEUPS=`echo \$CMD2 | \$BIN_AWK -F ' ' '{print \$12}'`";
    $f[]="";
    $f[]="echo '{\"data\":{\"offset\":\"'\$OFFSET\ ";
    $f[]="'\",\"frequency\":\"'\$FREQUENCY\ ";
    $f[]="'\",\"sys_jitter\":\"'\$SYS_JITTER\ ";
    $f[]="'\",\"clk_jitter\":\"'\$CLK_JITTER\ ";
    $f[]="'\",\"clk_wander\":\"'\$CLK_WANDER\ ";
    $f[]="'\",\"stratum\":\"'\$STRATUM\ ";
    $f[]="'\",\"time_since_reset\":\"'\$TIMESINCERESET\ ";
    $f[]="'\",\"receive_buffers\":\"'\$RECEIVEDBUFFERS\ ";
    $f[]="'\",\"free_receive_buffers\":\"'\$FREERECEIVEBUFFERS\ ";
    $f[]="'\",\"used_receive_buffers\":\"'\$USEDRECEIVEBUFFERS\ ";
    $f[]="'\",\"low_water_refills\":\"'\$LOWWATERREFILLS\ ";
    $f[]="'\",\"dropped_packets\":\"'\$DROPPEDPACKETS\ ";
    $f[]="'\",\"ignored_packets\":\"'\$IGNOREDPACKETS\ ";
    $f[]="'\",\"received_packets\":\"'\$RECEIVEDPACKETS\ ";
    $f[]="'\",\"packets_sent\":\"'\$PACKETSSENT\ ";
    $f[]="'\",\"packet_send_failures\":\"'\$PACKETSENDFAILURES\ ";
    $f[]="'\",\"input_wakeups\":\"'\$PACKETSENDFAILURES\ ";
    $f[]="'\",\"useful_input_wakeups\":\"'\$USEFULINPUTWAKEUPS\ ";
    $f[]="'\"},\"error\":\"0\",\"errorString\":\"\",\"version\":\"'\$VERSION'\"}'";
    $f[]="";

    @file_put_contents("/etc/snmp/ntp-server.sh",@implode("\n",$f));
    @chmod("/etc/snmp/ntp-server.sh",0755);
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/snmp/ntp-server.sh done\n";
}
function UNBOUND_EXTEND(){

    $unix=new unix();
    $unbountctl=$unix->find_program("unbound-control");

    $f[]="#!/bin/sh";
    $f[]="$unbountctl -c /etc/unbound/unbound.conf stats\n";

    @file_put_contents("/etc/snmp/unbound-status.sh",@implode("\n",$f));
    @chmod("/etc/snmp/unbound-status.sh",0755);
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/snmp/unbound-status.sh done\n";

}
function UNBOUND_EXTEND2(){
    $EnableUnBoundSNMPD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUnBoundSNMPD"));
    $UnboundEnabled     =   intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundEnabled"));
    if($UnboundEnabled==0){$EnableUnBoundSNMPD=0;}
    $CRON_FILE="/etc/cron.d/unbound_cacti";

    if($EnableUnBoundSNMPD==0){
        if(is_file($CRON_FILE)){
            @unlink($CRON_FILE);
            shell_exec("/etc/init.d/cron reload");
        }
        return;
    }

    if(!is_dir("/etc/unbound/statistics")){@mkdir("/etc/unbound/statistics",0755,true);}

    $unbound_cacti="/usr/share/artica-postfix/bin/install/unbound_cacti";
    @chmod($unbound_cacti,0755);

    if(!is_file($CRON_FILE)){
        $f[]="PATH=PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
        $f[]="MAILTO=\"\"";
        $f[]="*/5 * * * *	root	/usr/bin/ionice -c2 -n7 /usr/bin/nice --adjustment=19  $unbound_cacti >/dev/null 2>&1\n";
        @file_put_contents($CRON_FILE,@implode("\n",$f));
        chmod($CRON_FILE,0640);
        chown($CRON_FILE,"root");
        shell_exec("/etc/init.d/cron reload");
    }

}

?>