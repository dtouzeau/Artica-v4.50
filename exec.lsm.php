#!/usr/bin/php
<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');

$GLOBALS["TITLENAME"]="LSM daemon";
$GLOBALS["MONIT"]=false;
$GLOBALS["FORCE"]=false;
if(preg_match("#--monit#", @implode(" ", $argv))){$GLOBALS["MONIT"]=true;}
if(preg_match("#--force#", @implode(" ", $argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--net-report=([0-9]+)", @implode(" ", $argv),$re)){net_report($re[1]);exit;}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit;}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit;}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit;}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit;}
if($argv[1]=="--progress"){$GLOBALS["OUTPUT"]=true;progress();exit;}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_lsm();exit;}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall_lsm();exit;}
if($argv[1]=="--parse"){$GLOBALS["OUTPUT"]=true;parselogs();exit;}


function build_progress($text,$pourc){
	$filename=basename(__FILE__);

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[0])){$file=basename($trace[0]["file"]);$function=$trace[0]["function"];$line=$trace[0]["line"];}
		if(isset($trace[1])){$file=basename($trace[1]["file"]);$function=$trace[1]["function"];$line=$trace[1]["line"];}
	}


	echo "[{$pourc}%] $filename $text ( $function Line $line)\n";
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/lsm.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}


function install_lsm(){
	$unix=new unix();
	build_progress("{stopping_service} Watchdog",15);
	system("/etc/init.d/artica-status stop");
	$monitbin=$unix->find_program("monit");
	build_progress("{install_service} {LinkStatusMonitor}",30);
	install_service();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LinkStatusMonitorEnabled", 1);
	
	
	build_progress("{configuring} {LinkStatusMonitor}",50);
	
	build();

	build_progress("{starting_service} {LinkStatusMonitor}",50);
	
	
	if(!start(true)){
		if($GLOBALS["OUTPUT"]){echo "Failed......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service..\n";}
		build_progress("{restarting_service} {LinkStatusMonitor} {failed}",110);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LinkStatusMonitorEnabled", 0);
		remove_service("/etc/init.d/foolsm");
		system("/etc/init.d/artica-status start");
		return;
	}
	
	$f=array();
	build_progress("{reconfiguring}",28);
	$f[]="check process LinkStatusMonitor with pidfile /var/run/foolsm.pid";
	$f[]="\tstart program = \"/etc/init.d/foolsm start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/foolsm --monit\"";

	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring LinkStatusMonitor...\n";}
	@file_put_contents("/etc/monit/conf.d/LinkStatusMonitor.monitrc", @implode("\n", $f));
	//********************************************************************************************************************
	shell_exec("$monitbin -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	
	build_progress("{restarting_service} {artica_status}",90);
	system("/etc/init.d/artica-status restart --force");
	build_progress("{install_service} {success}",100);
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function uninstall_lsm(){
	$unix=new unix();
	$monitbin=$unix->find_program("monit");
	build_progress("{uninstall_service} {LinkStatusMonitor}",15);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LinkStatusMonitorEnabled", 0);
	$GLOBALS["FORCE"]=true;
	stop();
	remove_service("/etc/init.d/foolsm");
	@unlink("/etc/monit/conf.d/LinkStatusMonitor.monitrc");
	shell_exec("$monitbin -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	build_progress("{uninstall_service} {LinkStatusMonitor} {success}",100);
}


function install_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$php5script=basename(__FILE__);
	$f[]="#! /bin/sh";
	$f[]="";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:		foolsm";
	$f[]="# Required-Start:	\$remote_fs \$syslog";
	$f[]="# Required-Stop:	\$remote_fs \$syslog";
	$f[]="# Default-Start:	2 3 4 5";
	$f[]="# Default-Stop:		";
	$f[]="# Short-Description:	Link Status Monitor";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="set -e";
	$f[]="";
	
	$f[]="case \"\$1\" in";
	$f[]=" start)";

	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";
	$f[]="";
	

	echo "LSM: [INFO] Writing /etc/init.d/foolsm with new config\n";
	@unlink("/etc/init.d/foolsm");
	@file_put_contents("/etc/init.d/foolsm", @implode("\n", $f));
	
	
	@chmod("/etc/init.d/foolsm",0755);
	
	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename("/etc/init.d/foolsm")." defaults >/dev/null 2>&1");
	
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename("/etc/init.d/foolsm")." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename("/etc/init.d/foolsm")." on >/dev/null 2>&1");
	}
}

function progress(){
	$unix=new unix();
	build_progress("Writing configuration...",10);
	$ssh=new openssh();
	$data=$ssh->save(true);
	
	$LOCATE_SSHD_CONFIG_PATH=$unix->LOCATE_SSHD_CONFIG_PATH();
	echo "Config file: $LOCATE_SSHD_CONFIG_PATH\n";
	@file_put_contents($LOCATE_SSHD_CONFIG_PATH, $data);
	build_progress("{stopping_service}...",50);
	stop(true);
	build_progress("{starting_service}...",70);
	start(true);
	build_progress("{done}...",100);
}


function reload(){
	$unix=new unix();
	$sshd=$unix->find_program("sshd");
	if(!is_file($sshd)){return;}
	
	if(is_file("/etc/init.d/ssh")){
		system("/etc/init.d/ssh restart");
		return;
	}

	if(is_file("/etc/init.d/sshd")){
		system("/etc/init.d/sshd restart");
		return;
	}
	
	$pid=$unix->PIDOF($sshd);
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){
		unix_system_HUP($pid);
	}
}

function build(){
	$unix=new unix();
	$q=new mysql();
	
	if(!$q->TABLE_EXISTS("lsm_rules","artica_backup")){
			$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`lsm_rules` (
			`id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
			`interface` VARCHAR( 20 ) NOT NULL,
			`enabled` smallint( 1 ) NOT NULL DEFAULT 1,
			`check_arp` smallint( 1 ) NOT NULL DEFAULT 0,
			`checkip` varchar(40) ,
			`max_packet_loss` smallint( 2 ) NOT NULL DEFAULT '15',
			`max_successive_pkts_lost` smallint( 2 ) NOT NULL DEFAULT '7',
			`min_packet_loss` smallint( 2 ) NOT NULL DEFAULT '5',
			`min_successive_pkts_rcvd` smallint( 2 ) NOT NULL DEFAULT '10',
			`interval_ms` INT( 10 ) NOT NULL DEFAULT '1000',
			`timeout_ms` INT( 10 ) NOT NULL DEFAULT '1000',
			`ttl`  smallint( 2 ) NOT NULL DEFAULT '64',
			`action`  smallint( 2 ) NOT NULL DEFAULT '1',
			`actionconfig` TEXT,
			 PRIMARY KEY ( `id` ),
			 KEY `enabled`(`enabled`),
			 KEY `action`(`action`),
			 KEY `checkip`(`checkip`)
			) ENGINE=MYISAM;";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo "!!!!!!!! $q->mysql_error !!!!!!!!!!! \n\n";}
		
	}
	
	if($q->COUNT_ROWS("lsm_rules","artica_backup")==0){
		$q->QUERY_SQL("INSERT IGNORE INTO lsm_rules (interface,checkip,enabled) VALUES ('eth0','8.8.8.8',1)","artica_backup");
	}
	$results = $q->QUERY_SQL("SELECT *  FROM `lsm_rules` WHERE enabled=1","artica_backup");
	if(!$q->ok){echo "!!!!!!!! $q->mysql_error !!!!!!!!!!! LINE ". __LINE__."\n\n";}
	$net=new networking();
	$f=array();
	
	$f[]="#";
	$f[]="# (C) 2009 Mika Ilmaranta <ilmis@nullnet.fi>";
	$f[]="#";
	$f[]="# License: GPLv2";
	$f[]="#";
	$f[]="";
	$f[]="#";
	$f[]="# Debug level: 0 .. 8 are normal, 9 gives lots of stuff and 100 doesn't";
	$f[]="# bother to detach";
	$f[]="#";
	$f[]="#debug=10";
	$f[]="#debug=9";
	$f[]="debug=5";
	$f[]="";
	$f[]="#";
	$f[]="#";
	$f[]="";	
	
	$php=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
	$ipclass=new IP();
	if($GLOBALS["OUTPUT"]){echo "Configure......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ".mysqli_num_rows($results)." rules\n";}
	
	while ($ligne = mysqli_fetch_assoc($results)) {
		$error=null;
		$id=$ligne["id"];
		$RuleName="rule{$ligne["id"]}";	
		$checkip=$ligne["checkip"];
		$max_packet_loss=$ligne["max_packet_loss"];
		$max_successive_pkts_lost=$ligne["max_successive_pkts_lost"];
		$min_packet_loss=$ligne["min_packet_loss"];
		$min_successive_pkts_rcvd=$ligne["min_successive_pkts_rcvd"];
		$interval_ms=$ligne["interval_ms"];
		$timeout_ms=$ligne["timeout_ms"];
		$check_arp=$ligne["check_arp"];
		$device=$ligne["interface"];
		$srcip=$NETWORK_ALL_INTERFACES[$device]["IPADDR"];
		$action=$ligne["action"];
		if($srcip=="0.0.0.0"){$srcip=null;}
		if($srcip=="127.0.0.1"){$srcip=null;}
		$actionconfig=unserialize(base64_decode($ligne["actionconfig"]));
		if(!isset($actionconfig["forward_interface"])){$actionconfig["forward_interface"]=null;}
		$forward_interface=trim($actionconfig["forward_interface"]);
		if($GLOBALS["OUTPUT"]){echo "Configure......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $RuleName $device -> $checkip\n";}
		
		
		if(!$unix->is_interface_available($device)){
			$f[]="# $device, Hardware error!";
			continue;
		}
		
		
		
		if(!$ipclass->isValid($srcip)){
			$f[]="# $srcip/$device, Hardware error!";
			continue;
		}
		
		$ttl=$ligne["ttl"];
		$f[]=" connection {";
		$f[]="   name=$RuleName";
		$f[]="   checkip=$checkip";
		$f[]="   eventscript=/usr/libexec/foolsm/script{$id}.sh";
		$f[]="   max_packet_loss=$max_packet_loss";
		$f[]="   max_successive_pkts_lost=$max_successive_pkts_lost";
		$f[]="   min_packet_loss=$min_packet_loss";
		$f[]="   min_successive_pkts_rcvd=$min_successive_pkts_rcvd";
		$f[]="   interval_ms=$interval_ms";
		$f[]="   timeout_ms=$timeout_ms";
		$f[]="   warn_email=root1@some.tld";
		$f[]="   check_arp=$check_arp";
		$f[]="   sourceip=$srcip";
		$f[]="   device=$device";
		$f[]="   ttl=$ttl";
		$f[]=" }\n\n";	
		

		$FILE="/var/log/artica-postfix/lsm";
		
		if($action==2){
			if(!$unix->is_interface_available($forward_interface)){
				$error="No ip associated to $forward_interface";
				$action=1;
			}
		}
		if($action==2){
			$forward_interface_ip=$NETWORK_ALL_INTERFACES[$forward_interface]["IPADDR"];
			if(!$ipclass->isValid($forward_interface_ip)){
				$f[]="# $forward_interface_ip/$forward_interface, Hardware error!";
				$error="$forward_interface_ip/$forward_interface Hardware error!";
				continue;
			}
		}
		
		
		
		if(!is_dir($FILE)){@mkdir($FILE,0755,true);}
		$script=array();
		$script[]="#!/bin/sh";
		$script[]="#";
		$script[]="# Copyright (C) 2009-2015 Mika Ilmaranta <ilmis@nullnet.fi>";
		$script[]="# Copyright (C) 2015 Tuomo Soini <tis@foobar.fi>";
		$script[]="#";
		$script[]="# License: GPLv2";
		$script[]="#";
		$script[]="";
		$script[]="#";
		$script[]="# default event handling script";
		$script[]="#";
		$script[]="";
		$script[]="STATE=\${1}";
		$script[]="NAME=\${2}";
		$script[]="CHECKIP=\${3}";
		$script[]="DEVICE=\${4}";
		$script[]="WARN_EMAIL=\${5}";
		$script[]="REPLIED=\${6}";
		$script[]="WAITING=\${7}";
		$script[]="TIMEOUT=\${8}";
		$script[]="REPLY_LATE=\${9}";
		$script[]="CONS_RCVD=\${10}";
		$script[]="CONS_WAIT=\${11}";
		$script[]="CONS_MISS=\${12}";
		$script[]="AVG_RTT=\${13}";
		$script[]="SRCIP=\${14}";
		$script[]="PREVSTATE=\${15}";
		$script[]="TIMESTAMP=\${16}";
		$script[]="";
		$script[]="";
		$script[]="DATE=\$(date --date=@\${TIMESTAMP})";
		$script[]="";
		$script[]="cat <<EOM >$FILE/\${TIMESTAMP}.ev";
		$script[]="<subject>Your connection \${NAME} [\${CHECKIP}] has changed its state to \${STATE} at \${DATE}.</subject>";
		$script[]="<newstate>\${STATE}</newstate>";
		$script[]="<TXT>Following parameters were passed:";
		if($error<>null){
			$script[]="* * * * * * * * * ERROR * * * * ";
			$script[]="Error: $error";
		}
		$script[]="Old State    = \${PREVSTATE}";
		$script[]="State        = \${STATE}";
		$script[]="Connection   = \${NAME}";
		$script[]="Tested addr. = \${CHECKIP}";
		$script[]="From addr.   = \${SRCIP}";
		$script[]="Time Stamp   = \${TIMESTAMP}";
		$script[]="";
		$script[]="Packet statuses:";
		$script[]="-------------------------";
		if($action==2){
			$script[]="Forward to	=  $forward_interface_ip if State ==> down";
		}
		
		if($action==3){
			$script[]="Free access to all user if State ==> down (/etc/squid3/non_ntlm.access)";
		}
		
		$script[]="replied      = \${REPLIED} packets replied";
		$script[]="waiting      = \${WAITING} packets waiting for reply";
		$script[]="timeout      = \${TIMEOUT} packets that have timed out (= packet loss)";
		$script[]="reply_late   = \${REPLY_LATE} packets that received a reply after timeout";
		$script[]="cons_rcvd    = \${CONS_RCVD} consecutively received replies in sequence";
		$script[]="cons_wait    = \${CONS_WAIT} consecutive packets waiting for reply";
		$script[]="cons_miss    = \${CONS_MISS} consecutive packets that have timed out";
		$script[]="avg_rtt      = \${AVG_RTT} average rtt [usec], calculated from received packets";
		$script[]="********************************************************************************";
		$script[]="</TXT>";
		$script[]="";
		$script[]="EOM";
		
		if($action==2){
			
			$script[]="if [ \${STATE}==\"up\" ]; then";
			$script[]="\t$echo \"\" >/etc/artica-postfix/settings/Daemons/SquidNetworkSwitch";
			$script[]="\t$php /usr/share/artica-postfix/exec.squid.global.access.php --outgoingaddr";
			$script[]="fi";
			$script[]="";
			$script[]="";
			$script[]="if [ \${STATE}==\"down\" ]; then";
			$script[]="\t$echo \"$forward_interface_ip\" >/etc/artica-postfix/settings/Daemons/SquidNetworkSwitch";
			$script[]="\t$php /usr/share/artica-postfix/exec.squid.global.access.php --outgoingaddr";
			$script[]="fi";			
			
		}
		
		
		if($action==3){
				
			$script[]="if [ \${STATE}==\"up\" ]; then";
			$script[]="\t$php /usr/share/artica-postfix/exec.kerbauth.watchdog.php --emergency-ad-off";
			$script[]="fi";
			$script[]="";
			$script[]="";
			$script[]="if [ \${STATE}==\"down\" ]; then";
			$script[]="\t$php /usr/share/artica-postfix/exec.kerbauth.watchdog.php --emergency-ad-on";
			$script[]="fi";
				
		}

		if($action==4){
			$script[]="if [ \${STATE}==\"up\" ]; then";
			$script[]="\t$php /usr/share/artica-postfix/exec.squid.ReloadState.php";
			$script[]="fi";
			$script[]="";

		}		
		
		
		$script[]="$php ".__FILE__." --net-report=\${TIMESTAMP}";
		$script[]="$php ".__FILE__." --parse";
		$script[]="exit 0";
		$script[]="#";
		$script[]="";
		if(!is_dir("/usr/libexec/foolsm")){@mkdir("/usr/libexec/foolsm",0755,true);}
		@file_put_contents("/usr/libexec/foolsm/script{$id}.sh", @implode("\n", $script));
		@chmod("/usr/libexec/foolsm/script{$id}.sh",0755);
	}
	
	@file_put_contents("/etc/foolsm.conf", @implode("\n", $f));
}

function net_report($timestamp){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$ifconfig=$unix->find_program("ifconfig");
	$ip=$unix->find_program("ip");
	exec("$ifconfig -a 2>&1",$results);
	$script[]="********************************************************************************";
	exec("$ip route show 2>&1",$results);
	@file_put_contents("/var/log/artica-postfix/lsm/$timestamp.rep", @implode("\n", $results));
	system("$php ".__FILE__." --parse");
}

function LSDMSyslog($text){
	if(!function_exists("syslog")){return;}
	$LOG_SEV=LOG_INFO;
	openlog("foolsm", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}

function parselogs(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	$directory="/var/log/artica-postfix/lsm";
	if(!is_dir($directory)){
		LSDMSyslog("$directory: no such directory");
		return;}
	
	if (!$handle = opendir($directory)) {return;}
	while (false !== ($fileZ = readdir($handle))) {
		if($fileZ=="."){continue;}
		if($fileZ==".."){continue;}
		$path="$directory/$fileZ";
		$xtime=$unix->file_time_min($path);
		if($xtime>240){
			LSDMSyslog("$fileZ bad file (more than 140mn, {$xtime}mn)");
			echo "Removing $path\n";@unlink($path);continue;}
			
		if(!preg_match("#(.+?)\.ev$#", $fileZ,$re)){continue;}
		$timestamp=$re[1];
		
		$data=@file_get_contents($path);
		if(!preg_match("#<subject>(.*?)</subject>.*?<newstate>(.*?)</newstate>.*?<TXT>(.*?)</TXT>#is", $data,$re)){
			LSDMSyslog("$fileZ bad data file");
			@unlink($path);
			continue;
		}
		
		$content=$re[3]."\n".@file_get_contents("/var/log/artica-postfix/lsm/$timestamp.rep");
		@unlink("/var/log/artica-postfix/lsm/$timestamp.rep");
		$LEVEL["down"]=0;
		$LEVEL["up"]=0;
		LSDMSyslog("Send notification {$re[1]}: Level info: {$LEVEL[$re[2]]}");
		squid_admin_mysql($LEVEL[$re[2]], $re[1], $re[3],__FILE__,__LINE__);
		@unlink($path);
	}
	
	
}


function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}

	build_progress("{stopping_service}",10);
	stop(true);
	build_progress("{reconfiguring}",50);
	build();
	sleep(1);
	build_progress("{starting_service}",80);
	if(!start(true)){
		build_progress("{starting_service} {failed}",110);
		return;
	}
	build_progress("{restarting_service} {success}",100);
}


function start($aspid=false){
	
	$unix=new unix();
	$sock=new sockets();

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


	if(!is_file("/usr/share/artica-postfix/bin/foolsm")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /usr/share/artica-postfix/bin/foolsm no such binary\n";}
		return;
	}
	
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		$time=$unix->PROCESS_TTL($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} already running $pid since $time\n";}
		return true;
	}
	
	
	$LinkStatusMonitorEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinkStatusMonitorEnabled"));


	if($LinkStatusMonitorEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see LinkStatusMonitorEnabled)\n";}
		return;
	}
	
	

	if(!is_dir("/var/run")){@mkdir("/var/run",0755,true);}
	if(!is_dir("/var/lib/foolsm")){@mkdir("/var/lib/foolsm",0755,true);}
	if(!is_file("/var/lib/foolsm/config.rtt")){@touch("/var/lib/foolsm/config.rtt");}
	@chmod("/usr/share/artica-postfix/bin/foolsm", 0755);
	default_script();
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	$LSM_VERSION=LSM_VERSION();
	
	$cmd="$nohup /usr/share/artica-postfix/bin/foolsm -c /etc/foolsm.conf -p /var/run/foolsm.pid >/dev/null 2>&1 &";
	system($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} v{$LSM_VERSION} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} v{$LSM_VERSION} Success PID $pid\n";}
		return true;

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

	if($GLOBALS["MONIT"]){
		$pid=PID_NUM();
		if($unix->process_exists($pid)){
			@file_put_contents("/var/run/foolsm.pid", $pid);
			return;
		}
	}


	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	if($GLOBALS["FORCE"]){unix_system_kill_force($pid);}
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
function LSM_VERSION(){
	exec("/usr/share/artica-postfix/bin/foolsm --version 2>&1",$results);
	foreach ($results as $line){
		if(preg_match("#version ([0-9\.]+)#", $line,$re)){return $re[1];}
		
	}
	
}
function default_script(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	$FILE="/var/log/artica-postfix/lsm";
	$f[]="#!/bin/sh";
	//$f[]="# /usr/local/libexec/foolsm/default_script";
	$f[]="# Copyright (C) 2009-2015 Mika Ilmaranta <ilmis@nullnet.fi>";
	$f[]="# Copyright (C) 2015 Tuomo Soini <tis@foobar.fi>";
	$f[]="#";
	$f[]="# License: GPLv2";
	$f[]="#";
	$f[]="";
	$f[]="#";
	$f[]="# default event handling script";
	$f[]="#";
	$f[]="";
	$f[]="STATE=\${1}";
	$f[]="NAME=\${2}";
	$f[]="CHECKIP=\${3}";
	$f[]="DEVICE=\${4}";
	$f[]="WARN_EMAIL=\${5}";
	$f[]="REPLIED=\${6}";
	$f[]="WAITING=\${7}";
	$f[]="TIMEOUT=\${8}";
	$f[]="REPLY_LATE=\${9}";
	$f[]="CONS_RCVD=\${10}";
	$f[]="CONS_WAIT=\${11}";
	$f[]="CONS_MISS=\${12}";
	$f[]="AVG_RTT=\${13}";
	$f[]="SRCIP=\${14}";
	$f[]="PREVSTATE=\${15}";
	$f[]="TIMESTAMP=\${16}";
	$f[]="";
	$f[]="if [ -z \"\${WARN_EMAIL}\" ] ; then";
	$f[]="    exit 0";
	$f[]="fi";
	$f[]="";
	$f[]="DATE=\$(date --date=@\${TIMESTAMP})";
	$f[]="";
	$f[]="cat <<EOM >$FILE/\${TIMESTAMP}.ev";
	$f[]="";
	$f[]="Hi,";
	$f[]="";
	$f[]="Your connection \${NAME} has changed its state to \${STATE} at \${DATE}.";
	$f[]="";
	$f[]="Following parameters were passed:";
	$f[]="prevstate    = \${PREVSTATE}";
	$f[]="newstate     = \${STATE}";
	$f[]="name         = \${NAME}";
	$f[]="checkip      = \${CHECKIP}";
	$f[]="device       = \${DEVICE}";
	$f[]="sourceip     = \${SRCIP}";
	$f[]="warn_email   = \${WARN_EMAIL}";
	$f[]="";
	$f[]="Packet statuses:";
	$f[]="replied      = \${REPLIED} packets replied";
	$f[]="waiting      = \${WAITING} packets waiting for reply";
	$f[]="timeout      = \${TIMEOUT} packets that have timed out (= packet loss)";
	$f[]="reply_late   = \${REPLY_LATE} packets that received a reply after timeout";
	$f[]="cons_rcvd    = \${CONS_RCVD} consecutively received replies in sequence";
	$f[]="cons_wait    = \${CONS_WAIT} consecutive packets waiting for reply";
	$f[]="cons_miss    = \${CONS_MISS} consecutive packets that have timed out";
	$f[]="avg_rtt      = \${AVG_RTT} average rtt [usec], calculated from received packets";
	$f[]="";
	$f[]="BR,";
	$f[]="Your Foolsm installation";
	$f[]="";
	$f[]="EOM";
	$f[]="$php ".__FILE__." --net-report=\${TIMESTAMP}";
	$f[]="$php ".__FILE__." --parse";
	$f[]="";
	$f[]="exit 0";
	$f[]="#";
	$f[]="";
	
	@mkdir("/usr/local/libexec/foolsm",0755,true);
	@file_put_contents("/usr/local/libexec/foolsm/default_script", @implode("\n", $f));
	@chmod("/usr/local/libexec/foolsm/default_script",0755);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} default_script done\n";}
}

function PID_NUM(){
	$unix=new unix();
	$pidfile="/var/run/foolsm.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/usr/share/artica-postfix/bin/foolsm");

}