<?php
// https://rbldnsd.io/
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
$GLOBALS["TITLENAME"]="DNS daemon for DNSBLs";
$GLOBALS["OUTPUT"]=true;

if($argv[1]=="--compile"){ compile_lists();exit;}
if($argv[1]=="--install"){ install();exit;}
if($argv[1]=="--uninstall"){ uninstall();exit;}
if($argv[1]=="--start"){ start();exit;}
if($argv[1]=="--stop"){ stop();exit;}
if($argv[1]=="--reload"){ reload();exit;}
if($argv[1]=="--restart"){ restart();exit;}

function compile_lists(){
$ipClass=new IP();
$pg=new postgres_sql();
@mkdir("/home/postfix/rbldns/dsbl",0755,true);
@chown("/home/postfix/rbldns/dsbl","postfix");
@chgrp("/home/postfix/rbldns/dsbl", "postfix");
@unlink("/home/postfix/rbldns/dsbl/spammerlist");
$tmpf = @fopen("/home/postfix/rbldns/dsbl/spammerlist", "w");
$sock=new sockets();

$RBLDNSD_TEXT=$sock->GET_INFO("RBLDNSD_TEXT");
if($RBLDNSD_TEXT==null){$RBLDNSD_TEXT="Artica Reputation";}

fwrite($tmpf,":127.0.0.2:Known spammer\n");
$c=0;

build_progress_compile(10,"{compile_database} {blacklist} {addr}");

$sql="SELECT ipaddr FROM rbl_blacklists";
$results=$pg->QUERY_SQL($sql);
if(!$pg->ok){echo "$pg->mysql_error\n";}
$max=pg_num_rows($results);
$d=0;
while ($ligne = pg_fetch_assoc($results)) {
	$ipaddr=trim($ligne["ipaddr"]);
	if($ipaddr==null){continue;}
	$c++;
	$d++;
	if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
	fwrite($tmpf, "$ipaddr\t:2:$RBLDNSD_TEXT\n");
	if($d>1000){
		$d=0;
		$prc=($c/$max)*100;
		build_progress_compile(10,"{compile_database} {blacklist} {addr} ".round($prc,2)."%");
	}
}

build_progress_compile(50,"{compile_database} {blacklist} {domains}");

$sock=new sockets();
$sock->SET_INFO("RBLDNSD_BLCK_COUNT",$c);
$c=0;


$sql="SELECT domain FROM rbl_blacklists_domains";

$results=$pg->QUERY_SQL($sql);
while ($ligne = pg_fetch_assoc($results)) {
	$domain=trim(strtolower($ligne["domain"]));
	if($domain==null){continue;}
	if(preg_match("#regex:\s+#", $domain)){continue;}
	$c++;
	fwrite($tmpf, "$domain\t:2:$RBLDNSD_TEXT\n");
}




@unlink("/home/postfix/rbldns/dsbl/whitelist");
@touch("/home/postfix/rbldns/dsbl/whitelist");

build_progress_compile(80,"{compile_database} {whitelist}");
$sql="SELECT ipaddr FROM rbl_whitelists";
$results=$pg->QUERY_SQL($sql);
if(!$pg->ok){echo "$pg->mysql_error\n";}
$c=0;
$d=0;
while ($ligne = pg_fetch_assoc($results)) {
	$ipaddr=trim($ligne["ipaddr"]);
	if($ipaddr==null){continue;}
	$c++;$d++;
	if(!$ipClass->isIPAddressOrRange($ipaddr)){continue;}
	fwrite($tmpf, "$ipaddr\t:9: Whitelisted entry\n");
	
	if($d>1000){
		$d=0;
		$prc=($c/$max)*100;
		build_progress_compile(10,"{compile_database} {whitelist} {addr} ".round($prc,2)."%");
	}
	
	
}

@fclose($tmpf);

$sock->SET_INFO("RBLDNSD_WHITE_COUNT",$c);
$sock->SET_INFO("RBLDNSD_COMPILE_TIME",time());

build_progress_compile(90,"{compile_database} $c {items}");
echo "$c entries\n";
build_progress_compile(95,"{compile_database} {reloading}");
system("/etc/init.d/rbldnsd reload");
build_progress_compile(100,"{success} $c {items}");
}

function restart(){
	build_progress(10, "{stopping}");
	if(!stop(true)){
		build_progress(110, "{stopping} {failed}");
		return;
	}
	
	build_progress(50, "{starting}");
	if(!start(true)){
		build_progress(110, "{starting} {failed}");
		return;
	}
	build_progress(100, "{starting} {success}");
}
function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/rbldnsd.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_compile($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/rbldnsd.compile.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function install(){
	build_progress(10, "{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RBLDNSD_ENABLED", 1);
	build_progress(20, "{installing}");
	build_init();
	build_progress(50, "{installing}");
	build_monit();
	build_progress(80, "{starting}");
	start(true);
	build_progress(100, "{done}");
}
function uninstall(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	build_progress(10, "{removing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_RBLDNSD_ENABLED", 0);
	remove_service("/etc/init.d/rbldnsd");
	system("$rm -rf /home/postfix/rbldns");
	@unlink("/var/log/rbl-stats.log");
	@unlink("/var/log/rbl.log");
	@unlink("/etc/monit/conf.d/APP_RBLDNSD.monitrc");
	build_progress(50, "{removing}");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(100, "{removing} {done}");
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function build_monit(){

	$f[]="check process APP_RBLDNSD with pidfile /var/run/rbldnsd.pid";
	$f[]="\tstart program = \"/etc/init.d/rbldnsd start\"";
	$f[]="\tstop program = \"/etc/init.d/rbldnsd stop\"";

	
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_RBLDNSD.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_RBLDNSD.monitrc")){
		echo "/etc/monit/conf.d/APP_RBLDNSD.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	
}

function start($aspid=false){
	
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("rbldnsd");
	
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, rbldnsd not installed\n";}
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
	
	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not enough memory\n";}
		if($unix->process_exists($pid)){stop();}
		return;
	}
	
	$APP_RBLDNSD_ENABLED=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_RBLDNSD_ENABLED"));
	
	if($APP_RBLDNSD_ENABLED==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see APP_RBLDNSD_ENABLED)\n";}
		return;
	}
	
	@mkdir("/home/postfix/rbldns/dsbl",0755,true);
	@chown("/home/postfix/rbldns/dsbl","postfix");
	@chgrp("/home/postfix/rbldns/dsbl", "postfix");
	
	$RbldnsdInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdInterface");
	$RbldnsdDomainName=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RbldnsdDomainName");
	if($RbldnsdInterface==null){$RbldnsdInterface="eth0";}
	if($RbldnsdDomainName==null){$RbldnsdDomainName="rbl.articatech.net";}
	$unix=new unix();
	$IpToListen=$unix->InterfaceToIPv4($RbldnsdInterface);
	
	$files[]="forward";
	$files[]="spammerlist";
	$files[]="whitelist";
	foreach ($files as $filename){
		if(!is_file("/home/postfix/rbldns/dsbl/$filename")){@touch("/home/postfix/rbldns/dsbl/$filename");}
		@chown("/home/postfix/rbldns/dsbl/$filename","postfix");
		@chgrp("/home/postfix/rbldns/dsbl/$filename", "postfix");
	}
	
	$unix->SystemCreateUser("postfix","postfix");

	
	$f[]=$Masterbin;
	$f[]="-u postfix:postfix";
	$f[]="-l /var/log/rbl.log";
	$f[]="-s /var/log/rbl-stats.log";
	$f[]="-p/var/run/rbldnsd.pid";
	$f[]="-r/home/postfix/rbldns/dsbl";
	$f[]="-b $IpToListen";
	$f[]="$RbldnsdDomainName:ip4set:spammerlist,whitelist";
	$f[]="$RbldnsdDomainName:generic:forward";
	
	if(is_file("/var/run/rbldnsd.pid")){@unlink("/var/run/rbldnsd.pid");}

	if(!is_file("/var/log/rbl.log")){@touch("/var/log/rbl.log");@chmod("/var/log/rbl.log","postfix");}
	
	$cmd=@implode(" ", $f) ." >/tmp/rbldnsd.start 2>&1 &";
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
		@unlink("/tmp/rbldnsd.start");
		return true;
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		$f=explode("\n",@file_get_contents("/tmp/rbldnsd.start"));
		foreach ($f as $line){if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $line\n";}}
		@unlink("/tmp/rbldnsd.start");
		
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
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NUM();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return true;
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
		return true;
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
	
	if(is_file("/var/run/rbldnsd.pid")){@unlink("/var/run/rbldnsd.pid");}
	return true;
	
}

function reload(){
	$pid=PID_NUM();
	$unix=new unix();
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service stopped...\n";}
		start();
		return;
	}
	if($GLOBALS["OUTPUT"]){echo "Reloading.....: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} PID $pid...\n";}
	
	$unix->KILL_PROCESS($pid,1);
	
}


function PID_NUM(){

	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/rbldnsd.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("rbldnsd");
	return $unix->PIDOF($Masterbin);

}
function build_init(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/rbldnsd";
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          rbldnsd";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$time";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: rbldnsd";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: {$GLOBALS["TITLENAME"]}";
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
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	
	echo "{$GLOBALS["TITLENAME"]}: [INFO] Writing $INITD_PATH with new config\n";
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
