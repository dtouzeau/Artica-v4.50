<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Connection Track Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}




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
	sleep(1);
	start(true);
	
}

function build(){
	
	$f[]="General {";
	$f[]="	Nice -1";
	$f[]="	HashSize 8192";
	$f[]="";
	$f[]="	#";
	$f[]="	# Maximum number of conntracks: ";
	$f[]="	# it must be >= $ cat /proc/sys/net/ipv4/netfilter/ip_conntrack_max";
	$f[]="	#";
	$f[]="	HashLimit 65535";
	$f[]="";
	$f[]="	#";
	$f[]="	# Logfile: on (/var/log/conntrackd.log), off, or a filename";
	$f[]="	# Default: off";
	$f[]="	#";
	$f[]="	#LogFile on";
	$f[]="";
	$f[]="	Syslog on";
	$f[]="	LockFile /var/lock/conntrack.lock";
	$f[]="	UNIX {";
	$f[]="		Path /var/run/conntrackd.ctl";
	$f[]="		Backlog 20";
	$f[]="	}";
	$f[]="";
	$f[]="	NetlinkBufferSize 262142";
	$f[]="	NetlinkBufferSizeMaxGrowth 655355";
	$f[]="	PollSecs 15";
	$f[]="";
	$f[]="	Filter {";
	$f[]="";
	$f[]="		Protocol Accept {";
	$f[]="			TCP";
	$f[]="			# UDP";
	$f[]="		}";
	$f[]="";
	$f[]="		Address Ignore {";
	$f[]="			IPv4_address 127.0.0.1 # loopback";
	$f[]="			IPv6_address ::1";
	$f[]="		}";
	$f[]="";
	$f[]="		#";
	$f[]="		# Uncomment this line below if you want to filter by flow state.";
	$f[]="		# The existing TCP states are: SYN_SENT, SYN_RECV, ESTABLISHED,";
	$f[]="		# FIN_WAIT, CLOSE_WAIT, LAST_ACK, TIME_WAIT, CLOSED, LISTEN.";
	$f[]="		#";
	$f[]="		# State Accept {";
	$f[]="		#	ESTABLISHED CLOSED TIME_WAIT CLOSE_WAIT for TCP";
	$f[]="		# }";
	$f[]="	}";
	$f[]="}";
	$f[]="";
	$f[]="Stats {";
	$f[]="	#";
	$f[]="	# If you enable this option, the daemon writes the information about";
	$f[]="	# destroyed connections to a logfile. Default is off.";
	$f[]="	# Logfile: on, off, or a filename";
	$f[]="	# Default file: (/var/log/conntrackd-stats.log)";
	$f[]="	#";
	$f[]="	LogFile off";
	$f[]="";
	$f[]="	# If you want reliable event reporting over Netlink, set on this";
	$f[]="	# option. If you set on this clause, it is a good idea to set off";
	$f[]="	# NetlinkOverrunResync. This option is off by default and you need";
	$f[]="	# a Linux kernel >= 2.6.31.";
	$f[]="	#";
	$f[]="	# NetlinkEventsReliable Off";
	$f[]="";
	$f[]="	Syslog on";
	$f[]="}";

	@mkdir("/etc/conntrackd",0755,true);
	@file_put_contents("/etc/conntrackd/conntrackd.conf", @implode("\n", $f));
}


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("conntrackd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, conntrackd not installed\n";}
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
		return;
	}
	$EnableConntrackd=$sock->GET_INFO("EnableConntrackd");
	
	if(!is_numeric($EnableConntrackd)){$EnableConntrackd=0;}
	

	if($EnableConntrackd==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableConntrackd)\n";}
		return;
	}

	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	build();
	
	if(is_file("/var/lock/conntrack.lock")){@unlink("/var/lock/conntrack.lock");}
	
	shell_exec("$echo 1 > /proc/sys/net/ipv4/netfilter/ip_conntrack_tcp_be_liberal");
	
	$cmd="$Masterbin -d -C /etc/conntrackd/conntrackd.conf >/dev/null 2>&1 &";
	
	
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
		
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


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
	$Masterbin=$unix->find_program("conntrackd");
	if(!is_file($Masterbin)){return;}


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
	shell_exec("$Masterbin -k -C /etc/conntrackd/conntrackd.conf >/dev/null 2>&1");
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
	$Masterbin=$unix->find_program("conntrackd");
	if(!is_file($Masterbin)){return 0;}
	return $unix->PIDOF($Masterbin);
}
?>