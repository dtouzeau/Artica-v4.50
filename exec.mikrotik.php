#!/usr/bin/php
<?php
//SP119
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($argv[1])){
    if($argv[1]=="--install"){install();exit();}
    if($argv[1]=="--uninstall"){uninstall();exit();}
    if($argv[1]=="--delete"){DeleteRules();exit();}
    if($argv[1]=="--start"){start();exit();}
    if($argv[1]=="--stop"){DeleteRules();exit();}
    if($argv[1]=="--remove"){DeleteRules();exit();}
    if($argv[1]=="--verif"){verif();exit();}
    if($argv[1]=="--build"){CreateRules();exit();}
    if($argv[1]=="--routes"){checkroutes();exit();}
    if($argv[1]=="--monit"){create_monit();exit();}
    exit();
}

echo "Starting......: ".date("H:i:s")." [INIT]: Mikrotik Unknown cmd, assume CreateRules();\n";
CreateRules();
function CreateRules():bool{
    build_progress("{building_files}",20);
    $unix                   = new unix();
    $iptables               = local_find_program("iptables");
    $HTTP_PORT              = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikHTTPPort"));
    $HTTPS_PORT             = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikSSLPort"));
    $MikrotikInterface      = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikInterface");
    $suffixTables           = "-m comment --comment \"ArticaMikroTik\"";
    $SquidMikrotikMaskerade = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMikrotikMaskerade"));
    $php                    = $unix->LOCATE_PHP5_BIN();
    DeleteRules();
    build_progress("{creating_rules}",50);


    $GLOBALS["SCRIPT_START"][]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik remove iptables\"";
    $GLOBALS["SCRIPT_START"][]="/usr/bin/php ".__FILE__." --remove >/dev/null 2>&1 || true";


    if($MikrotikInterface==null){$MikrotikInterface="eth0";}
    if($HTTP_PORT==0){
        $HTTP_PORT=rand(4000,8080);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MikrotikHTTPPort",$HTTP_PORT);
    }
    if($HTTPS_PORT==0){
        $HTTPS_PORT=rand(2048,4043);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MikrotikSSLPort",$HTTPS_PORT);
    }
    $IPADDR=$unix->InterfaceToIPv4($MikrotikInterface);

    $SRC_HTTP_PORT=80;
    $SRC_HTTPS_PORT=443;
    $GLOBALS["SCRIPT_START"][]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik $SRC_HTTP_PORT -> $HTTP_PORT\"";
    $GLOBALS["SCRIPT_START"][]="$iptables -t mangle -I PREROUTING -p tcp --dport $SRC_HTTP_PORT -j TPROXY --tproxy-mark 0x1/0x1 --on-port $HTTP_PORT $suffixTables || true";
    $GLOBALS["SCRIPT_START"][]="$iptables -t nat -I PREROUTING -s $IPADDR -p tcp --dport $SRC_HTTP_PORT -j ACCEPT $suffixTables || true";
    $GLOBALS["SCRIPT_START"][]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik $SRC_HTTPS_PORT -> $HTTPS_PORT\"";
    $GLOBALS["SCRIPT_START"][]="$iptables -t mangle -I PREROUTING -p tcp --dport $SRC_HTTPS_PORT -j TPROXY --tproxy-mark 0x1/0x1 --on-port $HTTPS_PORT $suffixTables || true";
    $GLOBALS["SCRIPT_START"][]="$iptables -t nat -I PREROUTING -s $IPADDR -p tcp --dport $SRC_HTTPS_PORT -j ACCEPT $suffixTables || true";
    $GLOBALS["SCRIPT_START"][]="$iptables -t mangle -N DIVERT $suffixTables >/dev/null 2>&1 || true";
    if($SquidMikrotikMaskerade==1){
        $GLOBALS["SCRIPT_START"][]="$iptables -t nat -I POSTROUTING -j MASQUERADE $suffixTables || true";
    }
    $GLOBALS["SCRIPT_START"][]="$iptables -t mangle -I PREROUTING -p tcp -m socket -j DIVERT $suffixTables || true";
    $GLOBALS["SCRIPT_START"][]="$iptables -t mangle -I DIVERT -j ACCEPT $suffixTables || true";
    $GLOBALS["SCRIPT_START"][]="$iptables -t mangle -I DIVERT -j MARK --set-mark 1 $suffixTables || true";
    $GLOBALS["SCRIPT_START"][]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik (modprobe)\"";
    $GLOBALS["SCRIPT_START"][]="modprobe ip_tables >/dev/null 2>&1 || true";
    $GLOBALS["SCRIPT_START"][]="modprobe nf_conntrack_ipv4 >/dev/null 2>&1 || true";
    $GLOBALS["SCRIPT_START"][]="modprobe xt_tcpudp >/dev/null 2>&1 || true";
    $GLOBALS["SCRIPT_START"][]="modprobe nf_tproxy_core >/dev/null 2>&1 || true";
    $GLOBALS["SCRIPT_START"][]="modprobe xt_MARK2 >/dev/null 2>&1 || true";
    $GLOBALS["SCRIPT_START"][]="modprobe xt_TPROXY2 >/dev/null 2>&1 || true";
    $GLOBALS["SCRIPT_START"][]="modprobe xt_socket2 >/dev/null 2>&1 || true";
    $GLOBALS["SCRIPT_START"][]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik (Kernel)\"";
    $GLOBALS["SCRIPT_START"][]="/usr/sbin/artica-phpfpm-service -firewall-tune";
    $GLOBALS["SCRIPT_START"][]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik rules [DONE]\"";
    mikrotik_create();
    build_progress("{checking_rules}",80);
    checkroutes();
    system("/etc/init.d/mikrotik start");
    build_progress("{create_monit_file}",85);
    create_monit();
    if(!CheckSquidPorts()){
        build_progress("{reconfiguring} {APP_SQUID}",90);
        shell_exec("$php ". ARTICA_ROOT."/exec.squid.global.access.php --ports");
        if(!CheckSquidPorts()) {
            build_progress("{check_squid_ports_failed}", 110);
            return false;
        }
    }


    build_progress("{done}",100);
    return true;
}

function CheckSquidPorts(){

    $HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikHTTPPort"));
    $HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikSSLPort"));
    $HTTP_PORT_OK=false;
    $HTTPS_PORT_OK=false;

    $f=explode("\n",@file_get_contents("/etc/squid3/listen_ports.conf"));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(strpos($line,0,1)=="#"){continue;}
        if(preg_match("#^http_port.*?$HTTP_PORT#",$line)){$HTTP_PORT_OK=true;continue;}
        if(preg_match("#^https_port.*?$HTTPS_PORT_OK#",$line)){$HTTPS_PORT_OK=true;continue;}
    }
    if($HTTPS_PORT_OK AND $HTTP_PORT_OK){return true;}

}

function verif() {
    $d=0;

    $iptables_save=local_find_program("iptables-save");
    exec("$iptables_save > /etc/artica-postfix/iptables-mikrotik.conf");

    $data=file_get_contents("/etc/artica-postfix/iptables-mikrotik.conf");
    $datas=explode("\n",$data);
    $pattern2="#.+?ArticaMikroTik#";


    foreach ($datas as $num=>$ligne){
        if($ligne==null){continue;}
        if(preg_match($pattern2,$ligne)){return;}
    }
    CreateRules();

}

function DeleteRules(){
    $d=0;

    $iptables_save=local_find_program("iptables-save");
    exec("$iptables_save > /etc/artica-postfix/iptables-mikrotik.conf");

    $data=file_get_contents("/etc/artica-postfix/iptables-mikrotik.conf");
    $datas=explode("\n",$data);
    $pattern2="#.+?ArticaMikroTik#";
    $conf=null;
    $iptables_restore=local_find_program("iptables-restore");
    foreach ($datas as $ligne){
        if($ligne==null){continue;}
        if(preg_match($pattern2,$ligne)){
            echo "Starting......: ".date("H:i:s")." [INIT]: Mikrotik: Remove \"$ligne\"\n";
            $d++;continue;}

        $conf=$conf . $ligne."\n";
    }
    file_put_contents("/etc/artica-postfix/iptables-mikrotik.new.conf",$conf);
    system("$iptables_restore < /etc/artica-postfix/iptables-mikrotik.new.conf");


}
function local_find_program($strProgram){
    global $addpaths;
    $arrPath = array('/bin', '/sbin', '/usr/bin', '/usr/sbin', '/usr/local/bin',
        '/usr/local/sbin','/usr/kerberos/bin','/usr/libexec');
    if (function_exists("is_executable")) {
        foreach($arrPath as $strPath) {$strProgrammpath = $strPath . "/" . $strProgram;if (is_executable($strProgrammpath)) {return $strProgrammpath;}}
    } else {
        return strpos($strProgram, '.exe');
    }
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

function rt_tables_check($tableid){
    if(!isset($GLOBALS["rt_tables_check"])){
        $GLOBALS["rt_tables_check"]=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
    }
    reset($GLOBALS["rt_tables_check"]);

    foreach ($GLOBALS["rt_tables_check"] as $line){
        if(preg_match("#^([0-9]+)\s+$tableid#i", $line,$re)){return $re[1];}
    }

    return 0;
}
function ip_rules_check($dev,$TableID,$tablename){
    if(!isset($GLOBALS["ip_rules_check"])){
        exec("/bin/ip -f inet rule list 2>&1",$GLOBALS["ip_rules_check"]);

    }
    reset($GLOBALS["ip_rules_check"]);

    foreach ($GLOBALS["ip_rules_check"] as $ligne){
        if(preg_match("#from all fwmark.*?$dev lookup ($TableID|$tablename)#i", $ligne)){return true;}
        if(preg_match("#from all fwmark.*?lookup ($TableID|$tablename)#i", $ligne)){return true;}
    }
}


function checkroutes(){
    $TableID=rt_tables_check("tproxy");
    if($TableID==0){
        system("/bin/echo \"100 tproxy\" >> /etc/iproute2/rt_tables");
        echo "Starting......: ".date("H:i:s")." [INIT]: Mikrotik table 100 [ADDED]\n";
        $TableID=100;
    }else{
        echo "Starting......: ".date("H:i:s")." [INIT]: Mikrotik table $TableID [OK]\n";

    }

    if(!ip_rules_check("lo",$TableID,"tproxy")){
        echo "Mikrotik inet rule lo [ADDED]\n";
        system("/bin/ip -f inet rule add fwmark 1 lookup $TableID");
        system("/bin/ip -f inet route add local default dev lo table $TableID");
        system("/bin/ip -f inet6 rule add fwmark 1 lookup $TableID");
        system("/bin/ip -f inet6 route add local default dev lo table $TableID");
    }else{
        echo "Starting......: ".date("H:i:s")." [INIT]: Mikrotik inet rule [OK]\n";
    }


    $MikrotikInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikInterface");
    if($MikrotikInterface==null){$MikrotikInterface="eth0";}
    system("/bin/ip -f inet route add local default dev $MikrotikInterface table tproxy >/dev/null 2>&1");

}
function build_progress($pourc,$text){

    if(!is_dir("/usr/share/artica-postfix/ressources/logs/web")){@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755,true);}
    $cachefile="/usr/share/artica-postfix/ressources/logs/mikrotik.progress";

    if(is_numeric($text)){
        $array["POURC"]=$text;
        $array["TEXT"]=$pourc;
        echo "{$pourc}% $text\n";
        @file_put_contents($cachefile, serialize($array));
        @chmod($cachefile,0755);
        return;

    }


    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "{$pourc}% $text\n";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function install(){

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMikrotikEnabled",1);
    build_progress("{install}",10);
    CreateRules();
    build_progress("{install}",30);
    build_progress("{starting}",50);
    shell_exec("/etc/init.d/mikrotik start");
    build_progress("{starting} {done}",100);
}

function create_monit(){
    $unix=new unix();
    $HTTP_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikHTTPPort"));
    $HTTPS_PORT=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikSSLPort"));
    $MikrotikInterface=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MikrotikInterface");
    if($MikrotikInterface==null){$MikrotikInterface="eth0";}
    $MikrotikInterfaceIP="127.0.0.1";


    if($unix->is_interface_available($MikrotikInterface)){
        $MikrotikInterfaceIP=$unix->InterfaceToIPv4($MikrotikInterface);
        if($MikrotikInterfaceIP==null){$MikrotikInterfaceIP="127.0.0.1";}
    }

    $f[]="# Interface = $MikrotikInterface";
    $f[]="check host tproxy.http with address $MikrotikInterfaceIP";
    $f[]="\tstart program = \"/usr/sbin/artica-phpfpm-service -start-proxy\" with timeout 60 seconds";
    $f[]="\tstop program  = \"/etc/init.d/squid stop --script=MONIT_MIKROTIK\" with timeout 60 seconds";
    $f[]="\trestart program  = \"/etc/init.d/squid restart --script=MONIT_MIKROTIK\" with timeout 60 seconds";
    $f[]="if failed port $HTTP_PORT with timeout 15 seconds";
    $f[]="    send \"GET /squid-internal-static/icons/silk/folder.png HTTP/1.0\\r\\n\\r\\n\"";
    $f[]="    expect \"HTTP/[0-9\.]{3} [0-9]+ .*\\r\\n\"";
    $f[]="    for 2 cycles then restart";
    $f[]="";
    $f[]="check host tproxy.https with address $MikrotikInterfaceIP";
    $f[]="\tstart program = \"/usr/sbin/artica-phpfpm-service -start-proxy\" with timeout 60 seconds";
    $f[]="\tstop program  = \"/etc/init.d/squid stop --script=MONIT_MIKROTIK\" with timeout 60 seconds";
    $f[]="\trestart program  = \"/etc/init.d/squid restart --script=MONIT_MIKROTIK\" with timeout 60 seconds";
    $f[]="if failed port $HTTPS_PORT with timeout 15 seconds";
    $f[]="    for 5 cycles then restart";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/MIKROTIK.monitrc", @implode("\n", $f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");


}

function uninstall(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMikrotikEnabled",0);
    build_progress("{uninstall}",10);
    remove_service("/etc/init.d/mikrotik");

    build_progress("{uninstall}",50);
    @unlink("/etc/monit/conf.d/MIKROTIK.monitrc");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

    build_progress("{uninstall} {done}",100);
}

function mikrotik_create(){

    $INITD_PATH="/etc/init.d/mikrotik";
    $php="/usr/bin/php";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          mikrotik";
    $f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$network \$time";
    $f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog \$network";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Mikrotik rules";
    $f[]="# chkconfig: 2345 11 89";
    $f[]="# description: Mikrotik rules";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik TProxy rules [START]\"";
    $f[]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik Routes....\"";
    $f[]="$php ".__FILE__." --delete || true";
    $f[]=@implode("\n",$GLOBALS["SCRIPT_START"]);
    $f[]="/bin/echo \"Starting......: 00:00:00 [INIT]: Mikrotik END....\"";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="$php ".__FILE__." --stop || true";
    $f[]="    ;;";

    $f[]="  verif)";
    $f[]="$php ".__FILE__." --verif || true";
    $f[]="    ;;";

    $f[]="";
    $f[]=" restart)";
    $f[]="$php ".__FILE__." --stop || true";
    $f[]="$php ".__FILE__." --build || true";
    $f[]="/etc/init.d/mikrotik start || true";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|verif}\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "Mikrotik: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);@file_put_contents($INITD_PATH, @implode("\n", $f));

    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
}