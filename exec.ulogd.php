<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="FireWall logger service";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
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
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}



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
	stop(true);
	build();
	sleep(1);
	start(true);
	
}
function build_progress($text,$pourc){
	if(is_numeric($text)){$oldtext=$pourc;$pourc=$text;$text=$oldtext;}
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ulogd.install.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function install(){
	$unix=new unix();
	build_progress(50, "{creating_service}");
	system("/sbin/ldconfig >/dev/null 2>&1");
	install_ulogd_service();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UlogdEnabled", 1);
	build_progress(80, "{restarting}");
	if(!build()){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UlogdEnabled", 0);
		remove_service("/etc/init.d/ulogd");
		remove_tables();
		build_progress(110, "{failed}");
		return;
	}
	
	if(!start(true)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UlogdEnabled", 0);
		remove_service("/etc/init.d/ulogd");
		remove_tables();
		build_progress(110, "{failed}");
		return;
	}
	
	build_progress(90, "{reconfigure}");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.firehol.php --reconfigure-progress");
	
	build_progress(100, "{success}");
}

function remove_tables(){
	$q=new postgres_sql();
	$BUILD=False;
	
	$tables[]="ulog2";
	$tables[]="mac";
	$tables[]="hwhdr";
	$tables[]="tcp";
	$tables[]="nfacct";
	$tables[]="ulog2_ct";
	$tables[]="ip_proto";
	
	foreach ($tables as $tablename){
		if(!$q->TABLE_EXISTS($tablename)){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Remove table $tablename\n";}
		
	}
}


function uninstall(){
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("UlogdEnabled", 0);
	build_progress(50, "{uninstall}");
	remove_service("/etc/init.d/ulogd");
	build_progress(80, "{uninstall}");
	remove_tables();
	
	build_progress(90, "{reconfigure}");
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.firehol.php --reconfigure-progress");
	
	
	build_progress(100, "{success}");
}
function install_ulogd_service(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          ulogd";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: UserSpace Log Daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable UserSpace Log Daemon";
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
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/ulogd";
	echo "HYPERCACHE: [INFO] Writing $INITD_PATH with new config\n";
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

	return true;

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
function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin="/usr/local/sbin/ulogd";

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, ulogd not installed\n";}
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
		return true;
	}
	$UlogdEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UlogdEnabled"));
	$FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));
	if($FireHolEnable==0){$UlogdEnabled=0;}


	if($UlogdEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see FireHolEnable/FireholInstalled/UlogdEnabled)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	if(!is_file("/etc/ulogd.conf")){build();}
	
	
	$f[]="$Masterbin --daemon";
	$f[]="--configfile /etc/ulogd.conf --pidfile /var/run/ulogd.pid";
	
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	
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
		return false;
	}


}

function Config_dustbin(){
	//  $f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_inppkt_ULOG.so\"";
	//  $f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_inppkt_UNIXSOCK.so\"";
	//	$f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_output_MYSQL.so\"";
	//	$f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_output_DBI.so\"";
	//	$f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_output_SQLITE3.so\"";
	//  $f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_filter_IP2HBIN.so\"";
	
	
	//$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_filter_PRINTFLOW.so\"";
	//$f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_filter_MARK.so\"";
	
	//$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_output_SYSLOG.so\"";
	//$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_output_XML.so\"";
	
	//$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_output_GPRINT.so\"";
	//$f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_output_NACCT.so\"";
	//$f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_output_PCAP.so\"";
	
	
	//$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_raw2packet_BASE.so\"";
	//$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_inpflow_NFACCT.so\"";
	//$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_output_GRAPHITE.so\"";
	//$f[]="#plugin=\"/usr/local/lib/ulogd/ulogd_output_JSON.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_filter_IP2BIN.so\"";
	//$f[]="# this is a stack for logging packet send by system via LOGEMU";
	//$f[]="#stack=log1:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,print1:PRINTPKT,emu1:LOGEMU";
	
	$f[]="# this is a stack for logging packet to JSON formatted file after a collect via NFLOG";
	$f[]="#stack=log2:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,mac2str1:HWHDR,json1:JSON";
	$f[]="";
	$f[]="# this is a stack for logging packets to syslog after a collect via NFLOG";
	$f[]="#stack=log3:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,print1:PRINTPKT,sys1:SYSLOG";
	$f[]="";
	$f[]="# this is a stack for logging packets to syslog after a collect via NuFW";
	$f[]="#stack=nuauth1:UNIXSOCK,base1:BASE,ip2str1:IP2STR,print1:PRINTPKT,sys1:SYSLOG";
	$f[]="";
	$f[]="# this is a stack for flow-based logging to MySQL";
	$f[]="#stack=ct1:NFCT,ip2bin1:IP2BIN,mysql2:MYSQL";
	
	$f[]="# this is a stack for packet-based logging via LOGEMU";
	$f[]="#stack=log2:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,print1:PRINTPKT,emu1:LOGEMU";
	$f[]="";
	$f[]="# this is a stack for ULOG packet-based logging via LOGEMU";
	$f[]="#stack=ulog1:ULOG,base1:BASE,ip2str1:IP2STR,print1:PRINTPKT,emu1:LOGEMU";
	$f[]="";
	$f[]="# this is a stack for packet-based logging via LOGEMU with filtering on MARK";
	$f[]="#stack=log2:NFLOG,mark1:MARK,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,print1:PRINTPKT,emu1:LOGEMU";
	$f[]="";
	$f[]="# this is a stack for packet-based logging via GPRINT";
	$f[]="#stack=log1:NFLOG,gp1:GPRINT";
	$f[]="";
	$f[]="# this is a stack for flow-based logging via LOGEMU";
	$f[]="#stack=ct1:NFCT,ip2str1:IP2STR,print1:PRINTFLOW,emu1:LOGEMU";
	$f[]="";
	$f[]="# this is a stack for flow-based logging via GPRINT";
	$f[]="#stack=ct1:NFCT,gp1:GPRINT";
	$f[]="";
	$f[]="# this is a stack for flow-based logging via XML";
	$f[]="#stack=ct1:NFCT,xml1:XML";
	$f[]="";
	$f[]="# this is a stack for logging in XML";
	$f[]="#stack=log1:NFLOG,xml1:XML";
	$f[]="";
	$f[]="# this is a stack for accounting-based logging via XML";
	$f[]="#stack=acct1:NFACCT,xml1:XML";
	$f[]="";
	$f[]="# this is a stack for accounting-based logging to a Graphite server";
	$f[]="#stack=acct1:NFACCT,graphite1:GRAPHITE";
	$f[]="";
	$f[]="# this is a stack for NFLOG packet-based logging to PCAP";
	$f[]="#stack=log2:NFLOG,base1:BASE,pcap1:PCAP";
	$f[]="";
	$f[]="# this is a stack for logging packet to MySQL";
	$f[]="#stack=log2:NFLOG,base1:BASE,ifi1:IFINDEX,ip2bin1:IP2BIN,mac2str1:HWHDR,mysql1:MYSQL";
	
	$f[]="# this is a stack for flow-based logging to SQLITE3";
	$f[]="#stack=ct1:NFCT,sqlite3_ct:SQLITE3";
	$f[]="";
	$f[]="# this is a stack for logging packet to SQLITE3";
	$f[]="#stack=log1:NFLOG,sqlite3_pkt:SQLITE3";
	$f[]="";
	$f[]="# this is a stack for flow-based logging in NACCT compatible format";
	$f[]="#stack=ct1:NFCT,ip2str1:IP2STR,nacct1:NACCT";
	$f[]="";
	$f[]="# this is a stack for accounting-based logging via GPRINT";
	$f[]="#stack=acct1:NFACCT,gp1:GPRINT";
	
	$f[]="# this is a stack for logging packet to PGsql after a collect via NFLOG";
	$f[]="#stack=log2:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,mac2str1:HWHDR,pgsql1:PGSQL";
	
	
	
	
	$f[]="";
	$f[]="[ct1]";
	$f[]="#netlink_socket_buffer_size=217088";
	$f[]="#netlink_socket_buffer_maxsize=1085440";
	$f[]="#netlink_resync_timeout=60 # seconds to wait to perform resynchronization";
	$f[]="#pollinterval=10 # use poll-based logging instead of event-driven";
	$f[]="# If pollinterval is not set, NFCT plugin will work in event mode";
	$f[]="# In this case, you can use the following filters on events:";
	$f[]="#accept_src_filter=192.168.1.0/24,1:2::/64 # source ip of connection must belong to these networks";
	$f[]="#accept_dst_filter=192.168.1.0/24 # destination ip of connection must belong to these networks";
	$f[]="#accept_proto_filter=tcp,sctp # layer 4 proto of connections";
	$f[]="";
	$f[]="[ct2]";
	$f[]="#netlink_socket_buffer_size=217088";
	$f[]="#netlink_socket_buffer_maxsize=1085440";
	$f[]="#reliable=1 # enable reliable flow-based logging (may drop packets)";
	$f[]="hash_enable=0";
	$f[]="";
	$f[]="# Logging of system packet through NFLOG";
	$f[]="[log1]";
	$f[]="# netlink multicast group (the same as the iptables --nflog-group param)";
	$f[]="# Group O is used by the kernel to log connection tracking invalid message";
	$f[]="group=0";
	$f[]="#netlink_socket_buffer_size=217088";
	$f[]="#netlink_socket_buffer_maxsize=1085440";
	$f[]="# set number of packet to queue inside kernel";
	$f[]="#netlink_qthreshold=1";
	$f[]="# set the delay before flushing packet in the queue inside kernel (in 10ms)";
	$f[]="#netlink_qtimeout=100";
	$f[]="";
	$f[]="# packet logging through NFLOG for group 1";
	$f[]="[log2]";
	$f[]="# netlink multicast group (the same as the iptables --nflog-group param)";
	$f[]="group=1 # Group has to be different from the one use in log1";
	$f[]="#netlink_socket_buffer_size=217088";
	$f[]="#netlink_socket_buffer_maxsize=1085440";
	$f[]="# If your kernel is older than 2.6.29 and if a NFLOG input plugin with";
	$f[]="# group 0 is not used by any stack, you need to have at least one NFLOG";
	$f[]="# input plugin with bind set to 1. If you don't do that you may not";
	$f[]="# receive any message from the kernel.";
	$f[]="#bind=1";
	$f[]="";
	$f[]="# packet logging through NFLOG for group 2, numeric_label is";
	$f[]="# set to 1";
	$f[]="[log3]";
	$f[]="# netlink multicast group (the same as the iptables --nflog-group param)";
	$f[]="group=2 # Group has to be different from the one use in log1/log2";
	$f[]="numeric_label=1 # you can label the log info based on the packet verdict";
	$f[]="#netlink_socket_buffer_size=217088";
	$f[]="#netlink_socket_buffer_maxsize=1085440";
	$f[]="#bind=1";
	$f[]="";
	$f[]="[ulog1]";
	$f[]="# netlink multicast group (the same as the iptables --ulog-nlgroup param)";
	$f[]="nlgroup=1";
	$f[]="#numeric_label=0 # optional argument";
	$f[]="";
	$f[]="[nuauth1]";
	$f[]="socket_path=\"/tmp/nuauth_ulogd2.sock\"";
	$f[]="";
	$f[]="[emu1]";
	$f[]="file=\"/var/log/ulogd_syslogemu.log\"";
	$f[]="sync=1";
	$f[]="";
	$f[]="[op1]";
	$f[]="file=\"/var/log/ulogd_oprint.log\"";
	$f[]="sync=1";
	$f[]="";
	$f[]="[gp1]";
	$f[]="file=\"/var/log/ulogd_gprint.log\"";
	$f[]="sync=1";
	$f[]="timestamp=1";
	$f[]="";
	$f[]="[xml1]";
	$f[]="directory=\"/var/log/\"";
	$f[]="sync=1";
	$f[]="";
	$f[]="[json1]";
	$f[]="sync=1";
	$f[]="#file=\"/var/log/ulogd.json\"";
	$f[]="#timestamp=0";
	$f[]="# device name to be used in JSON message";
	$f[]="#device=\"My awesome Netfilter firewall\"";
	$f[]="# If boolean_label is set to 1 then the numeric_label put on packet";
	$f[]="# by the input plugin is coding the action on packet: if 0, then";
	$f[]="# packet has been blocked and if non null it has been accepted.";
	$f[]="#boolean_label=1";
	$f[]="# Uncomment the following line to use JSON v1 event format that";
	$f[]="# can provide better compatility with some JSON file reader.";
	$f[]="#eventv1=1";
	$f[]="";
	$f[]="[pcap1]";
	$f[]="#default file is /var/log/ulogd.pcap";
	$f[]="#file=\"/var/log/ulogd.pcap\"";
	$f[]="sync=1";
	$f[]="";
}

function build(){

	
	$f[]="[global]";
	$f[]="######################################################################";
	$f[]="# GLOBAL OPTIONS";
	$f[]="######################################################################";
	$f[]="";
	$f[]="";
	$f[]="# logfile for status messages";
	$f[]="logfile=\"/var/log/ulogd.log\"";
	$f[]="";
	$f[]="# loglevel: debug(1), info(3), notice(5), error(7) or fatal(8) (default 5)";
	$f[]="# loglevel=1";
	$f[]="";
	$f[]="######################################################################";
	$f[]="# PLUGIN OPTIONS";
	$f[]="######################################################################";
	$f[]="";
	$f[]="# We have to configure and load all the plugins we want to use";
	$f[]="";
	$f[]="# general rules:";
	$f[]="# 1. load the plugins _first_ from the global section";
	$f[]="# 2. options for each plugin in seperate section below";
	$f[]="";
	$f[]="";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_inppkt_NFLOG.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_inpflow_NFCT.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_output_PGSQL.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_filter_IFINDEX.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_filter_IP2STR.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_filter_HWHDR.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_output_LOGEMU.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_raw2packet_BASE.so\"";
	$f[]="plugin=\"/usr/local/lib/ulogd/ulogd_filter_PRINTPKT.so\"";

	$f[]="";
	$f[]="";
	$f[]="";
	$f[]="stack=logdrop:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,mac2str1:HWHDR,pgsql1:PGSQL";
	$f[]="stack=logaccept:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,mac2str1:HWHDR,pgsql1:PGSQL";
	$f[]="stack=log1:NFLOG,base1:BASE,ifi1:IFINDEX,ip2str1:IP2STR,print1:PRINTPKT,emu1:LOGEMU";
	$f[]="";
	$f[]="";
	$f[]="[log1]";
	$f[]="group=0";
	$f[]="";
	$f[]="[emu1]";
	$f[]="file=\"/var/log/ulogd_syslogemu.log\"";
	$f[]="sync=1";
	
	
	
	$f[]="# accounting see https://home.regit.org/2012/07/flow-accounting-with-netfilter-and-ulogd2/";

	$f[]="";
	$f[]="# this is a stack for flow-based logging to PGSQL";
	$f[]="#stack=ct1:NFCT,ip2str1:IP2STR,pgsql2:PGSQL";
	$f[]="";
	$f[]="# this is a stack for flow-based logging to PGSQL without local hash";
	$f[]="stack=ct1:NFCT,ip2str1:IP2STR,mac2str1:HWHDR,pgsql2:PGSQL";
	$f[]="";
	


	$f[]="[logdrop]";
	$f[]="group=1";
	$f[]="numeric_label=0";
	$f[]="";
	$f[]="[logaccept]";
	$f[]="group=1";
	$f[]="numeric_label=1";
	$f[]="";


	$f[]="[pgsql1]";
	$f[]="procedure=\"INSERT_PACKET_FULL\"";
	$f[]="# See http://www.postgresql.org/docs/9.2/static/libpq-connect.html#LIBPQ-CONNSTRING";
	$f[]="connstring=\"host='/var/run/ArticaStats' port='/var/run/ArticaStats' dbname=squidlogs user=ArticaStats password=\"";
	$f[]="backlog_memcap=1000000";
	$f[]="backlog_oneshot_requests=10";
	$f[]="ring_buffer_size=1000";
	$f[]="";
	$f[]="[pgsql2]";
	$f[]="procedure=\"INSERT_OR_REPLACE_CT\"";
	$f[]="connstring=\"host='/var/run/ArticaStats' port='/var/run/ArticaStats' dbname=squidlogs user=ArticaStats password=\"";
	$f[]="backlog_memcap=1000000";
	$f[]="backlog_oneshot_requests=10";
	$f[]="ring_buffer_size=1000";
	
	$f[]="";
	$f[]="[pgsql4]";
	$f[]="db=\"nulog\"";
	$f[]="host=\"localhost\"";
	$f[]="user=\"nupik\"";
	$f[]="table=\"nfacct\"";
	$f[]="#schema=\"public\"";
	$f[]="pass=\"changeme\"";
	$f[]="procedure=\"INSERT_NFACCT\"";
	$f[]="";
	$f[]="[sys2]";
	$f[]="facility=LOG_LOCAL2";
	$f[]="";
	$f[]="[nacct1]";
	$f[]="sync = 1";
	$f[]="#file = /var/log/ulogd_nacct.log";
	$f[]="";
	$f[]="[mark1]";
	$f[]="mark = 1";
	$f[]="";
	$f[]="[acct1]";
	$f[]="pollinterval = 2";
	$f[]="# If set to 0, we don't reset the counters for each polling (default is 1).";
	$f[]="#zerocounter = 0";
	$f[]="# Set timestamp (default is 0, which means not set). This timestamp can be";
	$f[]="# interpreted by the output plugin.";
	$f[]="#timestamp = 1";
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/ulogd.conf success\n";}
	@file_put_contents("/etc/ulogd.conf", @implode("\n", $f));
	
	
	$q=new postgres_sql();
	$BUILD=False;
	
	$tables[]="ulog2";
	$tables[]="mac";
	$tables[]="hwhdr";
	$tables[]="tcp";
	$tables[]="nfacct";
	$tables[]="ulog2_ct";
	$tables[]="ip_proto";
	
	foreach ($tables as $tablename){
		if(!$q->TABLE_EXISTS($tablename)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $tablename doesn't exists\n";}
			$BUILD=true;
			break;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $tablename [OK]\n";}
	}
	
	
	if($BUILD){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} importing tables schemas...\n";}
		system("/usr/local/ArticaStats/bin/psql -h /var/run/ArticaStats -U ArticaStats proxydb -f /usr/share/artica-postfix/bin/install/pgsql-ulogd2.sql");
		reset($tables);
		if(!$q->TABLE_EXISTS($tablename)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $tablename doesn't exists\n";}
			return false;
		}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $tablename [OK]\n";}
	}
	
	
	return true;
	
	
	
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
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	



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
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/ulogd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin="/usr/local/sbin/ulogd";
	return $unix->PIDOF($Masterbin);
}
?>