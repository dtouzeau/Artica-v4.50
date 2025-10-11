<?php
// SP 127
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.strongswan.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');


if($argv[1]=="--monit"){strongswan_monit();exit;}
if($argv[1]=="--init"){strongswan_server();exit;}

xrun();

function xrun(){

    $unix=new unix();
    build_progress("{enable_service}",15);
    $php=$unix->LOCATE_PHP5_BIN();
    if(!is_file("/usr/local/lib/python2.7/dist-packages/vici/__init__.py")){
        echo "Installing python vici....\n";
        $pip=$unix->find_program("pip");
        system("$pip install vici");
    }
    $strongswan=new strongswan();
    $strongSwanWizard=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("strongSwanWizard")));
    $strongswan->main_array["GLOBAL"]["ENABLE_SERVER"]=1;
    $strongswan->main_array["GLOBAL"]["strongSwanCharonstart"]=$strongSwanWizard["strongSwanCharonstart"];
    $strongswan->main_array["GLOBAL"]["strongSwanCachecrls"]=$strongSwanWizard["strongSwanCachecrls"];
    $strongswan->main_array["GLOBAL"]["strongSwanCharondebug"]=$strongSwanWizard["strongSwanCharondebug"];
    $strongswan->main_array["GLOBAL"]["strongSwanStrictcrlpolicy"]=$strongSwanWizard["strongSwanStrictcrlpolicy"];
    $strongswan->main_array["GLOBAL"]["strongSwanUniqueids"]=$strongSwanWizard["strongSwanUniqueids"];
    $strongswan->main_array["GLOBAL"]["StrongswanListenInterface"]=$strongSwanWizard["StrongswanListenInterface"];
    $strongswan->main_array["GLOBAL"]["StrongswanEnableDNSWINS"]=$strongSwanWizard["StrongswanEnableDNSWINS"];
    $strongswan->main_array["GLOBAL"]["VPN_DNS_1"]=$strongSwanWizard["VPN_DNS_1"];
    $strongswan->main_array["GLOBAL"]["VPN_DNS_2"]=$strongSwanWizard["VPN_DNS_2"];
    $strongswan->main_array["GLOBAL"]["VPN_WINS_1"]=$strongSwanWizard["VPN_WINS_1"];
    $strongswan->main_array["GLOBAL"]["VPN_WINS_2"]=$strongSwanWizard["VPN_WINS_2"];
    $strongswan->main_array["GLOBAL"]["strongSwanEnableDHCP"]=$strongSwanWizard["strongSwanEnableDHCP"];
    $strongswan->main_array["GLOBAL"]["strongSwanDHCPForceServerAddress"]=$strongSwanWizard["strongSwanDHCPForceServerAddress"];
    $strongswan->main_array["GLOBAL"]["strongSwanDHCPIdentityLease"]=$strongSwanWizard["strongSwanDHCPIdentityLease"];
    $strongswan->main_array["GLOBAL"]["StrongswanDHCPListenInterface"]=$strongSwanWizard["StrongswanDHCPListenInterface"];
    $strongswan->main_array["GLOBAL"]["strongSwanDHCPServer"]=$strongSwanWizard["strongSwanDHCPServer"];

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableStrongswanServer",1);

    $strongswan->Save(true);
    build_progress("{building_configuration}",50);
    strongswan_server();
    strongswan_monit();

    build_progress("{restart_service}",60);
    system("$php /usr/share/artica-postfix/exec.strongswan.php --restart");


    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state start APP_STRONGSWAN");
    }

    $pid=PID_NUM();
    for($i=0;$i<6;$i++){
        if($unix->process_exists($pid)){
            echo "Strongswan service successfully started\n";
            break;
        }
        echo "Strongswan service Waiting PID $pid....\n";
        sleep(1);
        $pid=PID_NUM();

    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        echo "Strongswan service not started....\n";
        exec("ps -aux | grep -i ipsec 2>&1",$cmdlineresults);
        foreach($cmdlineresults as $line){
            echo "Found process: $line\n";


        }
        build_progress("{failed}",110);
        return false;
    }

    $unix->Popuplate_cron_make("artica-strongswan-sess2mn","0,2,4,6,8,10,12,14,16,18,20,22,24,26,28,30,32,34,36,38,40,42,44,46,48,50,52,54,56,58 * * * *","exec.strongswan.sessions.php");
    $unix->Popuplate_cron_make("strongswan-status-mn","* * * * *","exec.strongswan.sessions.php");

    shell_exec("/etc/init.d/cron reload");

    //system("$php /usr/share/artica-postfix/exec.strongswan.php --reconfigure \"$uid\"");
    build_progress("{done}",100);
    return true;
}


function PID_NUM(){

    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/strongswan/strongswan-server.pid");
    if($unix->process_exists($pid)){return $pid;}
    $Masterbin=$unix->find_program("starter");
    // return $unix->PIDOF_PATTERN("$Masterbin --start");
    return $unix->PIDOF("/usr/lib/ipsec/charon");

}


function build_progress($text,$pourc){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/strongswan.enable.progress";
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
    if($GLOBALS["OUTPUT"]){sleep(1);}
}

function strongswan_monit(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $sh[]="#!/bin/sh";
    $sh[]="$php  /usr/share/artica-postfix/exec.strongswan.php --start --monit >/dev/null";
    $sh[]="";

    @file_put_contents("/usr/sbin/strongswan-start.sh", @implode("\n", $sh));
    $sh=array();
    $sh[]="#!/bin/sh";
    $sh[]="$php  /usr/share/artica-postfix/exec.strongswan.php --stop --monit >/dev/null";
    $sh[]="";
    @file_put_contents("/usr/sbin/strongswan-stop.sh", @implode("\n", $sh));


    @chmod("/usr/sbin/strongswan-start.sh",0755);
    @chmod("/usr/sbin/strongswan-stop.sh",0755);

    @unlink("/etc/monit/conf.d/APP_STRONGSWAN.monitrc");
    $f=array();
    $f[]="check process APP_STRONGSWAN with pidfile /var/run/strongswan/strongswan-server.pid";
    $f[]="\tstart program = \"/etc/init.d/ipsec start\"";
    $f[]="\tstop program = \"/etc/init.d/ipsec stop\"";
    $f[]="\tif 5 restarts within 5 cycles then timeout";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_STRONGSWAN.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    //IPSEC VICI
    @unlink("/etc/monit/conf.d/APP_STRONGSWAN_VICI.monitrc");
    $f=array();
    $f[]="check process APP_STRONGSWAN_VICI with pidfile /var/run/strongswan-stats.pid";
    $f[]="\tstart program = \"/etc/init.d/ipsec-stats start\"";
    $f[]="\tstop program = \"/etc/init.d/ipsec-stats stop\"";
    $f[]="\tif 5 restarts within 5 cycles then timeout";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_STRONGSWAN_VICI.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    //IPSEC VICI STATS PARSER
    @unlink("/etc/monit/conf.d/APP_STRONGSWAN_VICI_STATS.monitrc");
    $f=array();
    $f[]="check process AAPP_STRONGSWAN_VICI_STATS with pidfile /var/run/strongswan-vici-stats.pid";
    $f[]="\tstart program = \"/etc/init.d/ipsec-stats start\"";
    $f[]="\tstop program = \"/etc/init.d/ipsec-stats stop\"";
    $f[]="\tif 5 restarts within 5 cycles then timeout";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_STRONGSWAN_VICI_STATS.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");


}

function strongswan_server(){
    if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/ipsec";
    $php5script="exec.strongswan.php";
    $daemonbinLog="Strongswan server";


    $f=array();
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         strongswan-server";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

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
    $f[]="    $php /usr/share/artica-postfix/$php5script --reconfigure \$2 \$3";
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


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
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
    @chmod("/usr/share/artica-postfix/bin/_updown",0755);
    $custon_updown=@file_get_contents("/usr/share/artica-postfix/bin/_updown");
    @file_put_contents("/usr/lib/ipsec/_updown",$custon_updown);
    @chmod("/usr/lib/ipsec/_updown",0755);
    // $g[]="#! /bin/sh";
    // $g[]="case \"\$PLUTO_VERB:$1\" in";
    // $g[]="up-client:)";
    // $g[]="iptables -t mangle -A INPUT -i \$PLUTO_INTERFACE -s \$PLUTO_PEER_CLIENT -p tcp -m tcp --tcp-flags SYN,RST SYN -m tcpmss --mss 1361:1536 -j TCPMSS --set-mss 1360";
    // $g[]="iptables -t mangle -A OUTPUT -o \$PLUTO_INTERFACE -d \$PLUTO_PEER_CLIENT -p tcp -m tcp --tcp-flags SYN,RST SYN -m tcpmss --mss 1361:1536 -j TCPMSS --set-mss 1360";
    // $g[]=";;";
    // $g[]="down-client:)";
    // $g[]="iptables -t mangle -D INPUT -i \$PLUTO_INTERFACE -s \$PLUTO_PEER_CLIENT -p tcp -m tcp --tcp-flags SYN,RST SYN -m tcpmss --mss 1361:1536 -j TCPMSS --set-mss 1360";
    // $g[]="iptables -t mangle -D OUTPUT -o \$PLUTO_INTERFACE -d \$PLUTO_PEER_CLIENT -p tcp -m tcp --tcp-flags SYN,RST SYN -m tcpmss --mss 1361:1536 -j TCPMSS --set-mss 1360";
    // $g[]=";;";
    // $g[]="esac";
    // @file_put_contents("/etc/strongswan.d/updown.sh", @implode("\n", $g));
    // @chmod("/etc/strongswan.d/updown.sh",0755);
    //STATS PARSER
    $daemonbinLog="Strongswan stats parser";
    $php5script="exec.strongswan-stats-parser.php";
    $INITD_PATH="/etc/init.d/ipsec-stats";
    $f=array();
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         strongswan-server";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

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
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";
    $f[]="";

    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
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



?>