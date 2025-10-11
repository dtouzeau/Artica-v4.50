<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.hosts.inc');

$GLOBALS["TITLENAME"]="Passive asset detection system";
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	
	$system_is_overloaded=system_is_overloaded(basename(__FILE__));
	
	if($system_is_overloaded){
        if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
        if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
		echo "OVERLOADEDDDDDD!!!\n";
		writelogs("System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}), aborting...","MAIN",__FILE__,__LINE__);
		exit();
	}	
	

if($argv[1]=='--build'){$GLOBALS["OUTPUT"]=true;build();exit;}
if($argv[1]=='--stats'){build_stats();exit;}
if($argv[1]=='--parse'){parse_queue();exit;}
if($argv[1]=="--dash"){build_dashboard();exit;}
if($argv[1]=="--arp"){print_r(GetMacFromIP($argv[2]));exit;}

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();die(1);}
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
        build_progress(110,"Already Artica task running PID $pid since {$time}mn");
		return;
	}
	@file_put_contents($pidfile, getmypid());
    build_progress(20,"{stopping_service}");
	stop(true);
    build_progress(80,"{starting_service}");
	sleep(1);
	start(true);
    build_progress(100,"{restarting} {success}");

}

function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/prads.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}
function prads_pid(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/prads/prads.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $binpath="/usr/bin/prads";
    return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
}

function install(){

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
	build_progress(15,"{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePrads", 1);
	install_service();
	build_progress(20,"{installing}");
    build();
	build_progress(90,"{installing}");
    build_monit();
    $unix->Popuplate_cron_make("prads-statistics","*/10 * * * *","exec.prads.php --stats");
    shell_exec("/etc/init.d/cron reload");
	build_progress(100,"{done}");
    shell_exec("$nohup /etc/init.d/prads restart >/dev/null 2>&1 &");
}

function uninstall(){
	$unix=new unix();
	build_progress(15,"{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePrads", 0);
	remove_service("/etc/init.d/prads");
	build_progress(30,"{uninstalling}");
	@unlink("/etc/monit/conf.d/APP_PRADS.monitrc");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

    $q=new postgres_sql();
    $q->QUERY_SQL("DROP TABLE prads_time");
    $q->QUERY_SQL("DROP TABLE prads_tot");

    @unlink("/etc/cron.d/prads-statistics");
    shell_exec("/etc/init.d/cron reload");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	@unlink("/var/log/prads-asset.log");
	build_progress(100,"{uninstalling} {done}");
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function build_monit(){

	$f[]="check process APP_PRADS with pidfile /var/run/prads/prads.pid";
	$f[]="\tstart program = \"/etc/init.d/prads start\"";
	$f[]="\tstop program = \"/etc/init.d/prads stop\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_PRADS.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_PRADS.monitrc")){
		echo "/etc/monit/conf.d/APP_PRADS.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	
}


function install_service(){
	    $unix=new unix();
	    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="#!/bin/sh";
    $f[]="";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          prads";
    $f[]="# Required-Start:    \$network \$local_fs \$remote_fs";
    $f[]="# Required-Stop:     \$network \$local_fs \$remote_fs";
    $f[]="# Default-Start:     S 2 3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Prads Realtime Asset Detection System";
    $f[]="# Description:       Prads is a server that listens on your network, and";
    $f[]="#                    logs the presence of any servers and clients";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="#";
    $f[]="# Author:       Stig Sandbeck Mathisen <ssm@debian.org>";
    $f[]="#";
    $f[]="";
    $f[]="DESC=\"Prads Realtime Asset Detection System\"";
    $f[]="NAME=\"prads\"";
    $f[]="";
    $f[]="USER=\"prads\"";
    $f[]="GROUP=\"prads\"";
    $f[]="CHROOT=\"/run/prads\"";
    $f[]="PIDFILE=\"prads.pid\"";
    $f[]="";
    $f[]="DAEMON_OPTS=\"-D -u \$USER -g \$GROUP -C \$CHROOT -p \$PIDFILE \${DAEMON_OPTS:-}\"";
    $f[]="";
    $f[]="create_chroot() {";
    $f[]="    install -o \$USER -g \$GROUP -d \$CHROOT";
    $f[]="}";
    $f[]="";
    $f[]="start_service() {";
    $f[]="    log_begin_msg \"Starting \$DESC...\"";
    $f[]="    [ ! -d \$CHROOT ] && create_chroot";
    $f[]="    $php ".__FILE__." --start";
    $f[]="    log_end_msg \$?";
    $f[]="}";
    $f[]="";
    $f[]="stop_service() {";
    $f[]="    log_begin_msg \"Stopping \$DESC...\"";
    $f[]="    $php ".__FILE__." --stop";
    $f[]="    log_end_msg \$?";
    $f[]="}";
    $f[]="";
    $f[]="status_service() {";
    $f[]="    status_of_proc \$DAEMON \$NAME";
    $f[]="}";
    $f[]="";
    $f[]="restart_service() {";
    $f[]="    $php ".__FILE__." --restart";
    $f[]="}";
    $f[]="";
    $f[]="fail() {";
    $f[]="    echo \"usage: \$0 <start|stop|restart|status>\"";
    $f[]="    exit 1";
    $f[]="}";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="    start)   start_service;;";
    $f[]="    stop)    stop_service;;";
    $f[]="    status)  status_service;;";
    $f[]="    restart) restart_service;;";
    $f[]="    force-reload) restart_service;;";
    $f[]="    *) fail;;";
    $f[]="esac";
    $f[]="";

	$INITD_PATH="/etc/init.d/prads";
	echo "PADS: [INFO] Writing $INITD_PATH with new config\n";
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


function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("prads");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, prads not installed\n";}
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

	if(!is_dir("/var/run/prads")){
	    @mkdir("/var/run/prads",0755,true);
    }

	$Folders[]="/var/run/prads";

	foreach ($Folders as $path){
        @chown($path,"prads");
        @chgrp($path,"prads");
    }



	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/prads/prads.pid", $pid);
		return;
	}


	$php5=$unix->LOCATE_PHP5_BIN();
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
    build();
	$CMDS[]="$Masterbin -D -c /etc/prads/prads.conf";
	$CMDS[]="-Z -u prads -g prads";
	
	$cmd="$Masterbin ".@implode(" ", $CMDS)." >/dev/null 2>&1 &";
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
	$pid=$unix->get_pid_from_file("/var/run/prads/prads.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("prads");
	return $unix->PIDOF($Masterbin);
}
	
function build(){
	$unix=new unix();

	$PRADSInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PRADSInterface");
	if($PRADSInterface==null){$PRADSInterface="eth0";}



    $f[]="daemon=1";
    $f[]="arp=1";
    $f[]="service_tcp=1";
    $f[]="client_tcp=1";
    $f[]="service_udp=1";
    $f[]="os_syn_fingerprint=1";
    $f[]="os_synack_fingerprint=1";
    $f[]="os_ack_fingerprint=1";
    $f[]="os_rst_fingerprint=1";
    $f[]="os_fin_fingerprint=1";
    $f[]="os_udp=1";
    $f[]="os_icmp=1";
    $f[]="asset_log=/var/log/prads-asset.log";
   // $f[]="chroot_dir=/var/run/prads";
    $f[]="pid_file=/var/run/prads/prads.pid";
    $f[]="sig_file_syn=/etc/prads/os.fp";
    $f[]="sig_file_synack=/etc/prads/osa.fp";
    $f[]="sig_file_serv_tcp=/etc/prads/tcp-service.sig";
    $f[]="sig_file_cli_tcp=/etc/prads/tcp-clients.sig";
    $f[]="sig_file_serv_udp=/etc/prads/udp-service.sig";
    $f[]="sig_file_cli_udp=/etc/prads/udp-clients.sig";
    $f[]="mac_file=/etc/prads/mac.sig";
    $f[]="user=prads";
    $f[]="group=prads";
    $f[]="#";
    $f[]="# interface";
    $f[]="# -------------------------";
    $f[]="# This contains the name of the interface PRADS will listen to.";
    $f[]="# PRADS will try to auto-detect the interface if none specified.";
    $f[]="#";
    $f[]="# Note! Only one interface at a time is supported currently. ";
    $f[]="interface=$PRADSInterface";
    $f[]="# interface=wlan0";
    $f[]="# interface=en0   # Mac OSX";
    $f[]="#";
    $f[]="# bpfilter";
    $f[]="# -------------------------";
    $f[]="# This value contains a libpcap filter to be applied to PRADS.";
    $f[]="# bpfilter 'src net 192.168.0.0 mask 255.255.255.0 or dst net 192.168.0.0 mask 255.255.255.0'";
    $f[]="# bpf-example for monitoring only your assets on 192.168.10.0/24:";
    $f[]="# bpfilter=src net 192.168.10.0 mask 255.255.255.0";
    $f[]="# NOTE: Be aware if you have vlan-tagged traffic...";
    $f[]="#bpfilter=src net 0.0.0.0 mask 0.0.0.0 or dst net 0.0.0.0 mask 0.0.0.0";
    $f[]="";
    $f[]="# bpf_file";
    $f[]="# -------------------------";
    $f[]="# Path to file which contains Berkley Packet Filter to load.";
    $f[]="# Default is not to load BPF from file.";
    $f[]="# Don't confuse yourself by using bpfilter= nor -b param with this config option.";
    $f[]="#bpf_file=bpf.conf";
    $f[]="";
    $f[]="#";
    $f[]="# fifo";
    $f[]="# -------------------------";
    $f[]="# PRADS FIFO file - sguil compatible fifo output for asset log";
    $f[]="# NOTE: There is no default fifo.";
    $f[]="# fifo=prads.fifo";
    $f[]="#";
    $f[]="home_nets=192.168.0.0/16,10.0.0.0/255.0.0.0,172.16.0.0/255.240.0.0";
    $f[]="";

    @file_put_contents("/etc/prads/prads.conf",@implode("\n",$f));
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/prads/prads.conf done..\n";

}

function parse_queue(){
    $wordkdir="/home/artica/prads";
    if(!is_dir($wordkdir)){return;}
    $unix=new unix();
    $PRADSInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PRADSInterface");
    if($PRADSInterface==null){$PRADSInterface="eth0";}

    if(!is_file("/usr/sbin/arp-scan")){
        $unix->DEBIAN_INSTALL_PACKAGE("arp-scan");
    }

    $q=new postgres_sql();


    if(!$q->create_prads_table()){
        echo "Statistics table failed\n";
        events("Statistics creation table failed $q->mysql_error");
        return false;
    }

    if (!$handle = opendir($wordkdir)) {
        return;
    }


    $UNLINK=array();
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}
        $TargetFile="$wordkdir/$file";
        if(is_dir($TargetFile)){continue;}
        if(!is_numeric($file)){continue;}
        if(!analyze_file($TargetFile)){continue;}
         $UNLINK[]=$TargetFile;
    }


    foreach ($GLOBALS["PRADS"] as $ip=>$array){
        if($ip==null){continue;}
        if(!isset($array["MAC"])){
            echo $ip." No MAC\n";
            $res=GetMacFromIP($ip,$PRADSInterface);

            $GLOBALS["PRADS"][$ip]["MAC"]=$res[0];
            if(!isset($GLOBALS["PRADS"][$ip]["OS"])) {
                if (strtolower($res[1]) <> "Unknown") {
                    $GLOBALS["PRADS"][$ip]["OS"] = $res[1];
                }
            }
        }
    }

    $prefix="INSERT INTO prads_time (discovered,mac,ipaddr,vlan,
            ports,portstext,protos,syn,ack,client,server,udp,fin,rst,arp,icmp) VALUES";

    $HOST=array();

    foreach ($GLOBALS["PRADS"] as $ip=>$array){
        $TableHost=true;
        if(!isset($array["MAC"])){$array["MAC"]="00:00:00:00:00:00";$TableHost=false;}
        $discovered=$array["TIME"];
        $mac=$array["MAC"];
        $vlan=$array["VLAN"];
        $ports=count($array["PORTS"]);
        $portstext=serialize($array["PORTS"]);
        $protos=count($array["PROTO"]);
        if(!isset($array["ICMP"])){$array["ICMP"]=0;}
        if(!isset($array["RST"])){$array["RST"]=0;}
        if(!isset($array["CLIENT"])){$array["CLIENT"]=0;}
        if(!isset($array["SERVER"])){$array["SERVER"]=0;}
        if(!isset($array["ACK"])){$array["ACK"]=0;}
        if(!isset($array["FIN"])){$array["FIN"]=0;}
        if(!isset($array["SYN"])){$array["SYN"]=0;}
        if(!isset($array["UDP"])){$array["UDP"]=0;}
        if(!isset($array["ARP"])){$array["ARP"]=0;}
        $icmp=intval($array["ICMP"]);
        $rst=intval($array["RST"]);
        $client=intval($array["CLIENT"]);
        $server=intval($array["SERVER"]);
        $ack=intval($array["ACK"]);
        $fin=intval($array["FIN"]);
        $udp=intval($array["UDP"]);
        $arp=intval($array["ARP"]);
        $syn=intval($array["SYN"]);
        $mac=strtolower($mac);

        if($TableHost){
            $HOST[$mac]["IP"]=$ip;
            $HOST[$mac]["OS"]=$array["OS"];
        }
        $sdate=date("Y-m-d H:i:s",$discovered);
        if(!preg_match("#^[0-9\.]+$#",$ip)){continue;}

        $f[]="('$sdate','$mac','$ip','$vlan','$ports','$portstext',
        '$protos','$syn','$ack','$client','$server','$udp','$fin','$rst','$arp','$icmp')";

        if(count($f)>500){
            $sql="$prefix ".@implode(",",$f);
            $q->QUERY_SQL($sql);
            if(!$q->ok){
                events("insterting into table failed $q->mysql_error in line ".__LINE__);
                die();}
            $f=array();
        }
    }

    if(count($f)>0){
        $sql="$prefix ".@implode(",",$f);
        $q->QUERY_SQL($sql);
        if(!$q->ok){
            events("insterting into table failed $q->mysql_error in line ".__LINE__);
            die();
        }

    }

    foreach ($UNLINK as $path){
        events("Removing $path");
        @unlink($path);
    }

    if(count($HOST)>0){
        foreach ($HOST as $mac=>$array){
            $cmp=new hosts($mac);
            $cmp->ipaddr=$array["IP"];
            events("Table hosts is updated with $mac / {$array["IP"]} {$array["OS"]}".__LINE__);
            if($array["OS"]<>null){$cmp->ComputerOS=$array["OS"];}
            $cmp->Save(true);
        }
    }
}

function GetMacFromIP($ip,$interface="eth1"){
    $unix=new unix();
    $mem=new lib_memcached();
    $ARPSCAN=unserialize($mem->getKey("ARPSCAN"));

    if(!$GLOBALS["FORCE"]) {
        if (isset($ARPSCAN["TIME"])) {
            if ($unix->time_min($ARPSCAN["TIME"]) < 59) {
                if (isset($ARPSCAN[$ip])) {
                    events("$ip HIT already ARP scanned {$ARPSCAN[$ip]}");
                    return unserialize($ARPSCAN[$ip]);
                }
            }
        }
    }


    if(!isset($GLOBALS["ARPSCAN"])){
        $unix=new unix();
        $GLOBALS["ARPSCAN"]=$unix->find_program("arp-scan");
    }
    if($GLOBALS["ARPSCAN"]==null){return null;}
    events("$ip MISS ARP Scanning...");
    exec("{$GLOBALS["ARPSCAN"]} -I $interface $ip 2>&1",$results);

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(strpos($line,"Starting arp-scan")>0){continue;}
        if(strpos($line,"Ending arp-scan")>0){continue;}
        if(strpos($line,"packets dropped by kernel")>0){continue;}

         if(!preg_match("#^[0-9\.]+\s+([A-ZA-z:0-9]+)\s+\((.*?)\)#",$line,$re)) {
            if(!preg_match("#^[0-9\.]+\s+([0-9:a-zA-Z]+)\s+(.+)#",$line,$re)){
                events("ARP SCAN regex Not found in {{$line}}");
                continue;
            }
        }
        $ARPSCAN[$ip]=serialize(array($re[1],trim($re[2])));
        $ARPSCAN["TIME"]=time();
        $mem->saveKey("ARPSCAN",serialize($ARPSCAN),3600);
        return array($re[1],trim($re[2]));

    }
    events("$ip ARP Scanning [NONE]...");
    $ARPSCAN[$ip]=null;
    $ARPSCAN["TIME"]=time();
    $mem->saveKey("ARPSCAN",serialize($ARPSCAN),3600);

}

function events($text){

    $lineToSave=date('Y-m-d H:i:s')." $text";
    $f = @fopen("/var/log/prads.log", 'a');
    if($GLOBALS["VERBOSE"]){echo $lineToSave."\n";}

    @fwrite($f, "$lineToSave\n");
    @fclose($f);

}

function analyze_file($path){

    $handle = @fopen($path, "r");
    if (!$handle) {
        events("Failed to open file $path");
        echo "Failed to open file $path\n";return false;}
    $c=0;
    while (!feof($handle)){
        $line =trim(fgets($handle));
        if($line==null){continue;}
        if(strpos($line,",")==0){continue;}
        $c++;

        if(preg_match("#\[(.+?)\]#",$line,$re)){
            $content=$re[1];
            $content=str_replace(",",";",$content);
            $line=str_replace($re[1],$content,$line);
        }

        $explode=explode(",",$line);

        $asset=$explode[0];
        $vlan=$explode[1];
        $port=intval($explode[2]);
        $proto=$explode[3];
        $service=$explode[4];
        $service_info=$explode[5];
        $distance=$explode[6];
        $discovered=$explode[7];
        $GLOBALS["PRADS"][$asset]["VLAN"]=$vlan;

        if(!is_numeric($discovered)){
            events("FATAL \"$discovered\" is not numeric : $line");
            foreach ($explode as $index=>$value){
                events("\$explode[$index]=$value;");
            }
            continue;
        }

        if(isset( $GLOBALS["PRADS"][$asset]["TIME"])){
            if($discovered> $GLOBALS["PRADS"][$asset]["TIME"]){
                $GLOBALS["PRADS"][$asset]["TIME"]=$discovered;
            }
        }else{
            $GLOBALS["PRADS"][$asset]["TIME"]=$discovered;
        }

        $GLOBALS["PRADS"][$asset]["TIME"]=$discovered;

        if(isset($GLOBALS["PRADS"][$asset]["PROTO"][$proto])){
            $GLOBALS["PRADS"][$asset]["PROTO"][$proto]=$GLOBALS["PRADS"][$asset]["PROTO"][$proto]+1;
        }else{
            $GLOBALS["PRADS"][$asset]["PROTO"][$proto]=1;
        }
        if(isset($GLOBALS["PRADS"][$asset]["SERVICE"][$service])){
            $GLOBALS["PRADS"][$asset]["SERVICE"][$service]=$GLOBALS["PRADS"][$asset]["SERVICE"][$service]+1;
        }else{
            $GLOBALS["PRADS"][$asset]["SERVICE"][$service]=1;
        }

        if($port>0){
            if(isset($GLOBALS["PRADS"][$asset]["PORTS"][$port])){
                $GLOBALS["PRADS"][$asset]["PORTS"][$port]++;
            }else{
                $GLOBALS["PRADS"][$asset]["PORTS"][$port]=1;
            }
        }

        if($service=="ARP"){
            if(preg_match("#([0-9a-fA-F]{2}:){5}[0-9a-fA-F]{2}#",$service_info,$re)){
                $GLOBALS["PRADS"][$asset]["MAC"]=$re[0];
            }
        }
        if($service=="ACK" OR $service=="RST"){
            if(preg_match("#(:|K)A:(.+?)\]#",$service_info,$re)){
                $GLOBALS["PRADS"][$asset]["OS"]=$re[2];
            }
        }
    }
    events("$path Success analyze $c lines with ".count($GLOBALS["PRADS"])." nodes discovered");
    return true;

}

function build_dashboard(){

    $q=new postgres_sql();
    $q->create_prads_table();
    $c=0;
    $results=$q->QUERY_SQL("SELECT ipaddr FROM prads_time group by ipaddr");
    while ($ligne = pg_fetch_assoc($results)) {
        if($ligne["ipaddr"]==null){continue;}
        $c++;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PRADS_NODES",$c);
    events("Discover $c nodes in total...");

    $q->QUERY_SQL("TRUNCATE TABLE prads_tot");

    $first="SELECT mac,ipaddr,SUM(protos) as protos,
        SUM(ports) as ports,SUM(syn) as syn,SUM(ack) as ack,
        SUM(client) as client,SUM(server) as server,
        SUM(udp) as udp,SUM(fin) as fin,SUM(rst) as rst,SUM(arp) as arp,SUM(icmp) as icmp
        FROM prads_time GROUP BY mac,ipaddr";

    $sql="INSERT INTO prads_tot (mac,ipaddr,protos,ports,syn,ack,client,server,udp,fin,rst,arp,icmp) $first";

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n$sql\n";}





}


function build_stats(){
    $unix = new unix();

    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    $mypid=getmypid();

    $pid=$unix->get_pid_from_file($pidfile);
    if($pid<>$mypid) {
        if ($unix->process_exists($pid, basename(__FILE__))) {
            $TimeExe = $unix->PROCCESS_TIME_MIN($pid);
            if ($TimeExe < 180) {
                $cmdline=@file_get_contents("/proc/$pid/cmdline");
                events("FATAL: Already process exists PID $pid since {$TimeExe}mn $cmdline");
                return false;
            }
            $unix->KILL_PROCESS($pid, 9);
        }
    }

    @file_put_contents($pidfile,getmypid());

    $Time=$unix->file_time_min($pidTime);
    if($Time<5){
        events("Require 5 minutes minimal, current is {$Time}");
        return false;
    }
    @unlink($pidTime);
    @file_put_contents($pidTime,time());

    $prads_pid=PID_NUM();
    if($unix->process_exists($prads_pid)){
        $TimeExe=$unix->PROCCESS_TIME_MIN($prads_pid);
        events("PRADS service is running since {$TimeExe}mn");
    }else{
        events("FATAL PRADS service is not running !");
    }


    $wordkdir="/home/artica/prads";
    if(!is_dir($wordkdir)){@mkdir($wordkdir,0755,true);}
    $nextfile=$wordkdir."/".time();
    @copy("/var/log/prads-asset.log",$nextfile);
    $echo=$unix->find_program("echo");
    shell_exec("$echo \"\"> /var/log/prads-asset.log");
    parse_queue();
    remove_stats();
    build_dashboard();

}

function remove_stats(){
    $unix=new unix();
    $pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    $TimeExec=$unix->file_time_min($pidTime);
    if($TimeExec<480){return true;}
    @unlink($pidTime);
    @file_put_contents($pidTime,time());
    $PRADSRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PRADSRetention"));
    if($PRADSRetention==0){$PRADSRetention=7;}
    $q=new postgres_sql();
    events("Cleaning the database for {$PRADSRetention} retention days");
    $q->QUERY_SQL("DELETE FROM prads_time WHERE discovered < NOW() - INTERVAL '{$PRADSRetention} days'");

    return true;
}
?>