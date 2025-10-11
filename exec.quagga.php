<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');

if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--start"){ospf_start();exit;}
if($argv[1]=="--stop"){ospf_stop();exit;}
if($argv[1]=="--restart"){ospf_restart();exit;}
if($argv[1]=="--status"){status();exit;}
if($argv[1]=="--reload"){reload();exit;}



function build_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"ospfd.progress");
}

function reload():bool{
    $unix       = new unix();
    $kill       = $unix->find_program("kill");
    etc_default();
    $md51=md5_file(OSPF_CONF);
    if(!ospfd_conf()){return false;}
    $md52=md5_file(OSPF_CONF);
    if ($md51 == $md52) {return true;}
    $pid = OSPF_PID();
    if (!$unix->process_exists($pid)) {
        _out("Starting ospf daemon...");
        start();
        return true;
    }
    _out("Reloading ospf daemon...");
    shell_exec("$kill -HUP $pid");
    return true;
}

function build($nocheck=false):bool{
    $unix       = new unix();
    $kill       = $unix->find_program("kill");
    etc_default();
    @file_put_contents("/etc/quagga/zebra.conf","\n");
    if(is_file(OSPF_CONF)) {
        $md51 = md5_file(OSPF_CONF);
    }

    if(!ospfd_conf()){return false;}

    $md52=md5_file(OSPF_CONF);

    if(!$nocheck) {
        if ($md51 <> $md52) {
            $pid = OSPF_PID();
            if (!$unix->process_exists($pid)) {
                _out("Starting ospf daemon...");
                start();
            }

            if ($unix->process_exists($pid)) {
                _out("Reloading ospf daemon...");
                shell_exec("$kill -HUP $pid");
                return true;
            }

        }
    }
    return true;
}

function ospf_restart():bool{

    if(!build(true)){
        build_progress("{building} {failed}",110);
        return false;
    }

    build_progress("{stopping}",10);
    ospf_stop();
    build_progress("{building}",20);

    checkdirs();
    build_progress("{starting}",30);
    if(!ospf_start()){
        build_progress("{starting} {failed}",110);
        return false;
    }
    build_progress("{starting} {success}",100);
    return true;
}

function ZEBRA_PID():int{
    $pidfile=ZEBRA_PID_PATH;
    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){return intval($pid);}
    $ospfd=$unix->find_program("zebra");
    $pid=$unix->PIDOF($ospfd);
    return intval($pid);
}

function zebra_start():bool{
    $unix=new unix();
    $pid=ZEBRA_PID();
    if($unix->process_exists($pid)){return true;}
    shell_exec("/etc/init.d/zebra start");

}

function install(){
    $unix=new unix();
    build_progress("{installing}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOSPFD",1);
    build_progress("{installing}",15);
    checkdirs();
    build_progress("{installing}",20);
    create_service();
    $unix->create_service("ospfd",__FILE__);
    build_progress("{installing}",50);
    $unix->create_monit("APP_ZEBRA",ZEBRA_PID_PATH,"zebra");
    $unix->create_monit("APP_OSPF",OSPF_PID_PATH,"ospfd");
    $unix->create_syslog_pname("ospfd","/var/log/ospfd.log");
    build_progress("{starting}",80);
    ospf_start();
    build_progress("{success}",100);
}
function worksdirs(){
    $dirs[]="/var/run/quagga";
    $dirs[]="/run/quagga";
    $dirs[]="/var/lock/quagga";
    $dirs[]="/etc/quagga";
    return $dirs;
}

function uninstall(){
    $unix=new unix();
    build_progress("{uninstalling}",10);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableOSPFD",0);
    $unix->remove_monit("APP_ZEBRA");
    $unix->remove_syslog("ospfd");
    build_progress("{uninstalling}",20);
    $unix->remove_monit("APP_OSPF");
    build_progress("{uninstalling}",30);
    $unix->remove_service("zebra");
    build_progress("{uninstalling}",40);
    $unix->remove_service("ospfd");
    build_progress("{uninstalling}",50);
    $files[]="/var/log/ospfd.log";
    $files[]="/etc/default/quagga";
    $files[]=OSPF_CONF;
    $files[]=OSPF_PID_PATH;
    $files[]=ZEBRA_PID_PATH;
    $files[]="/run/quagga/ospfd.pid";
    $files[]="/run/quagga/ospfd.vty";
    $files[]="/run/quagga/zebra.pid";
    $files[]="/run/quagga/zebra.vty";
    $files[]="/run/quagga/zserv.api";
    $files[]="/var/run/quagga/spfd.pid";
    $files[]="/var/run/quagga/ospfd.vty";
    $files[]="/var/run/quagga/zebra.pid";
    $files[]="/var/run/quagga/zebra.vty";
    $files[]="/var/run/quagga/zserv.api";
    $files[]="/etc/quagga/zebra.conf";
    $files[]="/root/.history_quagga";

    foreach ($files as $tpath){
        if(is_file($tpath)){@unlink($tpath);}
    }

    $dirs=worksdirs();
    foreach ($dirs as $tpath){
        if(!is_dir($tpath)){continue;}
        @rmdir($tpath);
    }

    build_progress("{uninstalling} {done}",100);

}

function build_routes():array{
    $f          = array();
    $q          = new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $sql        ="CREATE TABLE IF NOT EXISTS `ospf_networks` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `network` TEXT NOT NULL,
        `realm` TEXT NOT NULL DEFAULT '0.0.0.0',
        `enabled` INTEGER NOT NULL DEFAULT 1
    )";

    $q->QUERY_SQL($sql);

    if($q->COUNT_ROWS("ospf_networks")==0){
        $rfc[]="10.0.0.0/8";
        $rfc[]="172.16.0.0/12";
        $rfc[]="192.168.0.0/16";
        foreach ($rfc as $nets) {
            $q->QUERY_SQL("INSERT INTO ospf_networks (network) VALUES ('$nets')");
        }
    }

    $results=$q->QUERY_SQL("SELECT network,realm FROM ospf_networks WHERE enabled=1");
    foreach ($results as $index=>$ligne){
        $network=$ligne["network"];
        $area=$ligne["realm"];
        if($area==null){$area="0.0.0.0";}
        $f[]="\tnetwork $network area $area";
    }

    return $f;

}

function ospfd_conf():bool{
    $unix       = new unix();
    $ospfd      = $unix->find_program("ospfd");
    $passive    = array();
    $GLOBALS["OSPF_CONF_BUILDED"]=true;

    $hostname           = $unix->hostname_g();
    $OSPFRouterIdent    =  $GLOBALS["CLASS_SOCKETS"]->GET_INFO("OSPFRouterIdent");
    $OSPFRouterID       = null;
    if($OSPFRouterIdent<>null){
        $OSPFRouterID=$unix->InterfaceToIPv4($OSPFRouterIdent);
    }

    if($OSPFRouterID==null){
        $OSPFRouterIdent="eth0";
        $OSPFRouterID=$unix->InterfaceToIPv4($OSPFRouterIdent);
        _out("Checking identification of $OSPFRouterIdent -> $OSPFRouterID");

        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OSPFRouterIdent",$OSPFRouterIdent);
        if($OSPFRouterID==null){
            $OSPFRouterIdent="eth1";
            $OSPFRouterID=$unix->InterfaceToIPv4($OSPFRouterIdent);
            _out("Checking identification of $OSPFRouterIdent -> $OSPFRouterID");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OSPFRouterIdent",$OSPFRouterIdent);
        }
        if($OSPFRouterID==null){
            $OSPFRouterIdent="eth2";
            $OSPFRouterID=$unix->InterfaceToIPv4($OSPFRouterIdent);
            _out("Checking identification of $OSPFRouterIdent -> $OSPFRouterID");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OSPFRouterIdent",$OSPFRouterIdent);
        }
    }

    if($OSPFRouterID==null){
        _out("Unable to find the router id information");
        return false;
    }
    _out("$hostname Router-id: $OSPFRouterID ");
    $f[]="hostname $hostname";
    $f[]="interface lo";

    $datas=explode("\n",@file_get_contents("/proc/net/dev"));

    foreach ($datas as $ligne){
        if(!preg_match("#^(.+?):#",$ligne,$re)){continue;}
        $Interface=trim($re[1]);
        $nic    = new system_nic($Interface);
        $IPADDR = $nic->IPADDR;
        $f[]="";
        $f[]="! Configuration for $Interface (".$nic->NICNAME . " $IPADDR)";
        if($nic->ospf_enable==0){
            $f[]="interface $Interface";
            $f[]="! Disabled for $Interface";
            $passive[$Interface]=$Interface;
            continue;
        }
        if($IPADDR=="0.0.0.0"){
            $f[]="interface $Interface";
            $passive[$Interface]=$Interface;
            continue;
        }
        $f[]="interface $Interface";
        $f[]="\tip ospf hello-interval 10";
        $f[]="\tip ospf network broadcast";
  	    $f[]="\tip ospf cost 10";
        $f[]="\tip ospf priority 1";
        $f[]="";

    }

    $f[]="router ospf";
    foreach ($passive as $interface=>$none){
        $f[]="\tpassive-interface $interface";
    }
    $f[]="\tospf router-id $OSPFRouterID";
    $f[]="\tredistribute kernel";
    $f[]="\tredistribute static";
    $routes=build_routes();
    foreach ($routes as $zrt) {
        $f[] = $zrt;
    }
    $f[]="";
    $f[]="log syslog";
    $f[]="";

    if(is_file("/tmp/ospfd.conf")){@unlink("/tmp/ospfd.conf");}
    @file_put_contents("/tmp/ospfd.conf",@implode("\n",$f));
    _out("Checking validity");
    exec("$ospfd --config_file /tmp/ospfd.conf -C 2>&1",$tests_results);
    $conf=true;
    foreach ($tests_results as $line) {
        $line=trim($line);
        if(preg_match("#Error#i",$line)){$conf=false;break;}

    }

    if(!$conf){
        _out("Error configuration on /tmp/ospfd.conf");
        foreach ($tests_results as $line) {
            _out($line);
        }
        return false;
    }

    @file_put_contents(OSPF_CONF,@implode("\n",$f));
    return true;
}

function etc_default(){

    $f[]="# Default: Bind all daemon vtys to the loopback(s) only";
    $f[]="#";
    $f[]="BABELD_OPTS=\"-A 127.0.0.1\"";
    $f[]="BGPD_OPTS=\"-A 127.0.0.1\"";
    $f[]="ISISD_OPTS=\"-A ::1\"";
    $f[]="OSPF6D_OPTS=\"-A ::1\"";
    $f[]="OSPFD_OPTS=\"-A 127.0.0.1\"";
    $f[]="RIPD_OPTS=\"-A 127.0.0.1\"";
    $f[]="RIPNGD_OPTS=\"-A ::1\"";
    $f[]="ZEBRA_OPTS=\"-A 127.0.0.1\"";
    $f[]="PIMD_OPTS=\"-A 127.0.0.1\"";
    $f[]="";
    $f[]="# Default: The compiled in default user and group(s), useful to";
    $f[]="# system startup to chmod config files.";
    $f[]="QUAGGA_USER=quagga";
    $f[]="QUAGGA_GROUP=quagga";
    $f[]="VTY_GROUP=quaggavty";
    $f[]="";
    $f[]="# Watchquagga configuration for LSB initscripts";
    $f[]="#";
    $f[]="# (Not needed with systemd: the service files are configured to automatically";
    $f[]="# restart any daemon on failure. If zebra fails, all running daemons will be";
    $f[]="# stopped; zebra will be started again; and then the previously running daemons";
    $f[]="# will be started again.)";
    $f[]="#";
    $f[]="# Uncomment and edit this line to reflect the daemons you are actually using:";
    $f[]="WATCH_DAEMONS=\"zebra ospfd\"";
    $f[]="#";
    $f[]="# Timer values can be adjusting by editing this line:";
    $f[]="WATCH_OPTS=\"-Az -b_ -r/sbin/service_%s_restart -s/sbin/service_%s_start -k/sbin/service_%s_stop\"";
    $f[]="";
    @file_put_contents("/etc/default/quagga",@implode("\n",$f));
}


function create_service(){

    $INITD_PATH = "/etc/init.d/zebra";
    $updaterc   = "/usr/sbin/update-rc.d";
    $chconf     = "/sbin/chkconfig";

    $f[]="#!/bin/bash";
    $f[]="# chkconfig: - 15 85";
    $f[]="# config: /etc/quagga/zebra.conf";
    $f[]="";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides: zebra";
    $f[]="# Required-Start:    \$remote_fs \$syslog \$network";
    $f[]="# Required-Stop:     \$remote_fs \$syslog \$network";
    $f[]="# Default-Start:     2 3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: GNU Zebra routing manager";
    $f[]="# Description: GNU Zebra routing manager";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="# source function library";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="# quagga command line options";
    $f[]=". /etc/default/quagga";
    $f[]="";
    $f[]="RETVAL=0";
    $f[]="PROG=\"zebra\"";
    $f[]="cmd=/usr/sbin/zebra";
    $f[]="LOCK_FILE=/var/lock/quagga/zebra";
    $f[]="CONF_FILE=/etc/quagga/zebra.conf";
    $f[]="";
    $f[]="[ -d /var/lock/quagga ] || mkdir /var/lock/quagga";
    $f[]="[ -d /run/quagga ] || ( mkdir /run/quagga && chown quagga:quagga /run/quagga )";
    $f[]="";
    $f[]="# if the config file doesn't exist, exit immediately";
    $f[]="[ -f \"\$CONF_FILE\" ] || exit 0";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";
    $f[]="	if [ `id -u` -ne 0 ]; then";
    $f[]="		echo \$\"Insufficient privilege\" 1>&2";
    $f[]="		exit 4";
    $f[]="	fi";
    $f[]="";
    $f[]="	log_daemon_msg \"Starting \$PROG\" \"\$PROG\"";
    $f[]="	/sbin/ip route flush proto zebra";
    $f[]="	start_daemon \$cmd -d \$ZEBRA_OPTS -f \$CONF_FILE";
    $f[]="	RETVAL=\$?";
    $f[]="	[ \$RETVAL -eq 0 ] && touch \$LOCK_FILE";
    $f[]="	log_end_msg \$RETVAL";
    $f[]="	;;";
    $f[]="  stop)";
    $f[]="	log_daemon_msg \"Shutting down \$PROG\" \"\$PROG\"";
    $f[]="	killproc -p /run/quagga/\$PROG.pid \$cmd";
    $f[]="	RETVAL=\$?";
    $f[]="	[ \$RETVAL -eq 0 ] && rm -f \$LOCK_FILE";
    $f[]="	log_end_msg \$RETVAL";
    $f[]="	;;";
    $f[]="  restart|reload|force-reload)";
    $f[]="	\$0 stop";
    $f[]="	\$0 start";
    $f[]="	RETVAL=\$?";
    $f[]="	;;";
    $f[]="  condrestart|try-restart)";
    $f[]="	if [ -f \$LOCK_FILE ]; then";
    $f[]="		\$0 stop";
    $f[]="		\$0 start";
    $f[]="	fi";
    $f[]="	RETVAL=\$?";
    $f[]="	;;";
    $f[]="  status)";
    $f[]="	status_of_proc -p /run/quagga/\$PROG.pid \$cmd";
    $f[]="	RETVAL=\$?";
    $f[]="	;;";
    $f[]="  *)";
    $f[]="	echo \$\"Usage: \$0 {start|stop|restart|reload|force-reload|condrestart|try-restart|status}\"";
    $f[]="	exit 2";
    $f[]="esac";
    $f[]="";
    $f[]="exit \$RETVAL";
    $f[]="";

    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file($updaterc)){
        shell_exec("$updaterc -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file($chconf)){
        shell_exec("$chconf --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("$chconf --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

    return true;

}

function checkdirs(){

    $dirs=worksdirs();
    _out("Checking directories and permissions");
    foreach ($dirs as $directory){
        if(!is_dir($directory)){
            _out("Creating directory $directory");
            @mkdir($directory,0755,true);
        }
        @chown($directory,"quagga");
        @chgrp($directory,"quagga");
    }
}

function _out($text):bool{
    $unix=new unix();
    $unix->ToSyslog("[START] $text",false,"ospfd");
    $date=date("H:i:s");
    echo "Starting......: $date [INIT]: Routing Service: $text\n";
    return true;
}

function OSPF_PID():int{
    $unix=new unix();
    $pidfile=OSPF_PID_PATH;
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){return intval($pid);}
    $ospfd=$unix->find_program("ospfd");
    $pid=$unix->PIDOF($ospfd);
    return intval($pid);
}

function ospf_start():bool{
    $unix       = new unix();
    $pid        = OSPF_PID();

    checkdirs();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        _out("Service OSPFD $pid already started $pid since {$timepid}Mn");
        @file_put_contents(OSPF_PID_PATH,$pid);
        return true;
    }

    if(is_file(OSPF_PID_PATH)){@unlink(OSPF_PID_PATH);}
    $ospfd=$unix->find_program("ospfd");
    if(!is_file($ospfd)){
        _out("Unable to locate OSPF Daemon, aborting!");
        return false;
    }

    if(!is_file(OSPF_CONF)){
        if(!isset($GLOBALS["OSPF_CONF_BUILDED"])){
            _out("Building configuration...");
            ospfd_conf();
        }

    }

    $cf=OSPF_CONF;
    $ci=OSPF_PID_PATH;
    $cmdline="$ospfd --daemon --config_file $cf --pid_file $ci";
    zebra_start();

    shell_exec($cmdline);

    for($i=1;$i<5;$i++){
        _out("Waiting $i/5");
        sleep(1);
        $pid=OSPF_PID();
        if($unix->process_exists($pid)){break;}
    }

    $pid=OSPF_PID();
    if($unix->process_exists($pid)) {
        _out("Success PID $pid");
        return true;
    }

    _out("Failed $cmdline");
    return false;
}

function ospf_stop():bool{
    $unix   = new unix();
    $pid    = OSPF_PID();


    if(!$unix->process_exists($pid)){
        _out("service already stopped...");
        return true;
    }
    $pid=OSPF_PID();
    _out("Shutdown pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=OSPF_PID();
        if(!$unix->process_exists($pid)){break;}
        _out("Service waiting pid:$pid $i/5");
        sleep(1);
    }

    $pid=OSPF_PID();
    if(!$unix->process_exists($pid)){
        _out("Success...");
        return true;
    }

    _out("Shutdown - force - pid $pid");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=OSPF_PID();
        if(!$unix->process_exists($pid)){break;}
        _out("Service waiting (force) pid:$pid $i/5");
        sleep(1);
    }

    if(!$unix->process_exists($pid)){
        _out("Service successfully stopped");
        return true;
    }
    _out("Service failed to be stopped");
    return false;
}

function status(){
    $unix       = new unix();
    $vtysh      = $unix->find_program("vtysh");
    $patternr   = "^([0-9\.]+)\s+([0-9]+)\s+([A-Za-z\/]+)\s+([0-9\.]+)s\s+([0-9\.]+)\s+(.+?):([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)";
    $patterns   ="^O.*?\s+([0-9\.\/]+)\s+\[([0-9\/]+)\]+\s+(.+?),(.*?),\s+([0-9:]+)";

    $cmd="$vtysh -c \"show ip ospf neighbor\" 2>&1";
    if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
    exec($cmd,$results);
    foreach ($results as $line){
        $line=trim($line);
        if(!preg_match("#$patternr#",$line,$re)){
            if($GLOBALS["VERBOSE"]){
                echo "NO MATCHES: $line\n";
            }
            continue;
        }
        if($GLOBALS["VERBOSE"]){echo "** MATCHES: $line\n";}
        $routerid=$re[1];
        $prio=$re[2];
        $stats=$re[3];
        $expire=$re[4];
        $router_ip=$re[5];
        $interface=$re[6];
        $localip=$re[7];
        $RXmtL=$re[8];
        $RqstL=$re[9];
        $DBsmL=$re[10];
        $MAIN["ROUTERS"][$routerid]["PRIO"]=$prio;
        $MAIN["ROUTERS"][$routerid]["STATUS"]=$stats;
        $MAIN["ROUTERS"][$routerid]["EXPIRE"]=$expire;
        $MAIN["ROUTERS"][$routerid]["routerip"]=$router_ip;
        $MAIN["ROUTERS"][$routerid]["localiface"]=$interface;
        $MAIN["ROUTERS"][$routerid]["localip"]=$localip;
        $MAIN["ROUTERS"][$routerid]["RXmtL"]=$RXmtL;
        $MAIN["ROUTERS"][$routerid]["RqstL"]=$RqstL;
        $MAIN["ROUTERS"][$routerid]["DBsmL"]=$DBsmL;

    }
    $cmd="$vtysh -c \"show ip route\" 2>&1";
    if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
    exec($cmd,$results2);
    foreach ($results2 as $line) {
        $line = trim($line);
        if (!preg_match("#$patterns#", $line, $re)) {
            if($GLOBALS["VERBOSE"]){
                echo "NO MATCHES: $line\n";
            }
            continue;
        }
        $MAIN["ROUTES"][$re[1]]["XX"] = $re[2];
        $MAIN["ROUTES"][$re[1]]["text"] = $re[3];
        $MAIN["ROUTES"][$re[1]]["INTERFACE"] = trim($re[4]);
        $MAIN["ROUTES"][$re[1]]["TIME"] = trim($re[5]);
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OSPFInfo",serialize($MAIN));

}