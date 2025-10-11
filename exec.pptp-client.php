<?php
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if(preg_match("#--verbose#",@implode("",$argv))){$GLOBALS["VERBOSE"]=true;}

if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){install();exit();}
if($argv[1]=="--start"){watchdog();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--up"){ifup($argv);exit;}
if($argv[1]=="--down"){ifdown($argv);exit;}
if($argv[1]=="--status"){status_all();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--mods"){check_mods();exit;}

function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/web/pptp-client.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function install(){
    $unix=new unix();
    build_progress(10,"{installing}");
    if(!is_dir("/etc/ppp/peers")){
        @mkdir("/etc/ppp/peers",0644,true);
    }

    if(!is_file("/usr/bin/pon")){
        build_progress(10,"{installing} PON....");
        $unix->DEBIAN_INSTALL_PACKAGE("ppp");
    }

    if(!is_file("/usr/bin/pon")) {
        build_progress(10, "{installing} PON {failed}....");
        return false;
    }

    $unix->Popuplate_cron_make("pptp-vpn","*/3 * * * *",basename(__FILE__)." --start");
    UNIX_RESTART_CRON();

    build_progress(50,"{installing}....");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePPTPClient",1);
    create_service();
    build_progress(60,"{installing}....");
    build_syslog();
    create_tables();
    build_progress(100,"{installing} {success}....");
}

function create_tables(){
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    @chmod("/home/artica/SQLITE/pptp.db", 0644);
    @chown("/home/artica/SQLITE/pptp.db", "www-data");
    $sql="CREATE TABLE IF NOT EXISTS `connections` (
					  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
					  `servername` TEXT,
					  `username` TEXT,
					  `password` TEXT,
					  `interface` TEXT,
					  `connexion_name` TEXT,
					  `routes` TEXT,
					  `enabled` INTEGER,
					  `mppe` INTEGER NOT NULL DEFAULT 1,
					  `authentication` INTEGER NOT NULL DEFAULT 1,
					  `md5` TEXT
            ) ";

    $q->QUERY_SQL($sql);


}

function stop(){
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $results=$q->QUERY_SQL("SELECT ID,connexion_name FROM `connections` ORDER BY connexion_name");
    foreach ($results as $index=>$ligne){$connexions_name[$ligne["ID"]]=$ligne["connexion_name"];}


    status_all();
    $PROCESSES=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/pptp.status"));

    $unix=new unix();
    $poff=$unix->find_program("poff");

    foreach ($PROCESSES as $ID=>$script){
        $name="PPTP_{$ID}";
        events("Stopping: Shutdown {$connexions_name[$ID]}");
        shell_exec("$poff $name");
    }
}

function check_mods(){
    $unix=new unix();

    $timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";

    if(!$GLOBALS["VERBOSE"]) {
        if (is_file($timefile)) {
            $TimeExec = $unix->file_time_min($timefile);
            if ($TimeExec < 30) {
                return true;
            }
        }
    }

    $lsmod=$unix->find_program("lsmod");
    $modprobe=$unix->find_program("modprobe");

    exec("$lsmod 2>&1",$mods);
    foreach ($mods as $line){
        if(!preg_match("#^(.+?)\s+[0-9]+\s+[0-9]+\s+(.+)#",$line,$re)){continue;}

        $xline=$re[2];
        if(strpos($xline,",")>0){
            $ymods=explode(",",$xline);
            foreach ($ymods as $ymod){
                $ymod=trim(strtolower($ymod));
                $runned_mods[$ymod]=true;
            }
        }else{
            $ymod=trim(strtolower($xline));
            $runned_mods[$ymod]=true;
        }

        $smod=trim(strtolower($re[1]));
        $runned_mods[$smod]=true;

    }



    if($GLOBALS["VERBOSE"]){
        foreach ($runned_mods as $ymod=>$n){
            echo "$ymod,";
        }
        echo "\n";
    }

    $zmods[]="nf_nat_pptp";
    $zmods[]="nf_conntrack_pptp";
    $zmods[]="nf_conntrack_proto_gre";

    $c=0;
    foreach ($zmods as $modinst){
        if(isset($runned_mods[$modinst])){continue;}
        events("watchdog: Launching kernel module $modinst");
        $c++;
        shell_exec("$modprobe $modinst");
    }

    if($c==0){
        if(is_file($timefile)){@unlink($timefile);}
        @file_put_contents($timefile,time());
    }


}

function watchdog(){

    check_mods();
    $CURRENT=array();
    $maindir="/etc/ppp/peers";
    if(!is_dir($maindir)){@mkdir($maindir,0755,true);}
    if (!$handle = opendir($maindir)) {echo "/etc/ppp/peers failed to open, permission denied or no space left\n";return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $results=$q->QUERY_SQL("SELECT ID,connexion_name,enabled FROM `connections` ORDER BY connexion_name");
    foreach ($results as $index=>$ligne){
        $connexions_enabled[$ligne["ID"]]=intval($ligne["enabled"]);
        $connexions_name[$ligne["ID"]]=$ligne["connexion_name"];
    }

    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$maindir/$filename";
        if(is_numeric($filename)){@unlink($targetFile);continue;}
        if(is_dir($targetFile)){continue;}
        if(!preg_match("#PPTP_([0-9]+)#",$filename,$re)){continue;}
        $ID=intval($re[1]);
        if($connexions_enabled[$ID]==0){
            events("Watchodg: {$connexions_name[$ligne["ID"]]} is disabled, skip it");
            @unlink($targetFile);
            continue;
        }
        $CURRENT[$ID]=$filename;
    }

    status_all();
    $PROCESSES=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/web/pptp.status"));

    $unix=new unix();
    $pon=$unix->find_program("pon");

    foreach ($CURRENT as $ID=>$script){
        if(!isset($PROCESSES[$ID])){
            events("Watchdog: Error {$connexions_name[$ID]} is not connected, initialize connection");
            shell_exec("$pon $script");
        }
    }
}

function ifup($argts=array()){
    $interface=$argts[2];
    $remote_gateway=$argts[5];
    $local_ip=$argts[4];
    $vpnname=$argts[6];
    $unix=new unix();
    if(!preg_match("#PPTP_([0-9]+)#",$vpnname,$re)){return;}
    $ID=$re[1];
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT routes,connexion_name FROM connections WHERE ID=$ID");
    $connexion_name=$ligne["connexion_name"];
    events("Connection $connexion_name to $remote_gateway succeed with local interface $interface ($local_ip)");

    $ip=$unix->find_program("ip");

    $routes=unserialize(base64_decode($ligne["routes"]));
    foreach ($routes as $znet=>$none){
        shell_exec("$ip route add $znet dev $interface proto kernel scope link src $local_ip");
        events("Adding route for $znet for $interface ($local_ip)");
    }


}
function ifdown($argts){
    $interface=$argts[2];
    $remote_gateway=$argts[5];
    $local_ip=$argts[4];
    $vpnname=$argts[6];
    if(!preg_match("#PPTP_([0-9]+)#",$vpnname,$re)){return false;}
    $ID=$re[1];
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM connections WHERE ID=$ID");
    $connexion_name=$ligne["connexion_name"];
    events("disconnect $connexion_name from $remote_gateway succeed with local interface $interface ($local_ip)");
    $unix=new unix();
    $ip=$unix->find_program("ip");
    $routes=unserialize(base64_decode($ligne["routes"]));
    foreach ($routes as $znet=>$none){
        shell_exec("$ip route del $znet dev $interface proto kernel scope link src $local_ip");
        events("Removing route for $znet for $interface ($local_ip)");
    }

}

function build(){
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/pptp.db");
    $maindir="/etc/ppp/peers";
    if(!is_dir($maindir)){@mkdir($maindir,0755,true);}

    if (!$handle = opendir($maindir)) {
        echo "/etc/ppp/peers failed to open, permission denied or no space left\n";
        return false;
    }

    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$maindir/$filename";
        if(is_numeric($filename)){@unlink($targetFile);continue;}
        if(is_dir($targetFile)){continue;}
        if(!preg_match("#PPTP_([0-9]+)#",$filename,$re)){continue;}
        $CURRENT[$re[1]]=$targetFile;
    }

    $chap_secrets="/etc/ppp/chap-secrets";

    $sql="SELECT * FROM connections WHERE enabled=1";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}
    $php=$unix->LOCATE_PHP5_BIN();
    $ipupd=array();
    $ipupd[]="#!/bin/sh";
    $ipupd[]="IFACE=\$PPP_IFACE";
    $ipupd[]="TTY=\$PPP_TTY";
    $ipupd[]="SPEED=\$PPP_SPEED";
    $ipupd[]="LOCAL=\$PPP_LOCAL";
    $ipupd[]="REMOTE=\$PPP_REMOTE";
    $ipupd[]="IPPARAM=\$PPP_IPPARAM";
    $ipupd[]="UNIT=\$PPP_UNIT";
    $ipupd[]="$php ".__FILE__." --up \$IFACE \$TTY \$SPEED \$LOCAL \$REMOTE \$IPPARAM \$UNIT";
    $ipupd[]="";

    @file_put_contents("/etc/ppp/ip-up.d/0000articaifup",@implode("\n",$ipupd));
    $ipupd=array();
    $ipupd[]="#!/bin/sh";
    $ipupd[]="IFACE=\$PPP_IFACE";
    $ipupd[]="TTY=\$PPP_TTY";
    $ipupd[]="SPEED=\$PPP_SPEED";
    $ipupd[]="LOCAL=\$PPP_LOCAL";
    $ipupd[]="REMOTE=\$PPP_REMOTE";
    $ipupd[]="IPPARAM=\$PPP_IPPARAM";
    $ipupd[]="UNIT=\$PPP_UNIT";
    $ipupd[]="$php ".__FILE__." --down \$IFACE \$TTY \$SPEED \$LOCAL \$REMOTE \$IPPARAM \$UNIT";
    $ipupd[]="";
    @file_put_contents("/etc/ppp/ip-down.d/0000articaifup",@implode("\n",$ipupd));

    @chmod("/etc/ppp/ip-down.d/0000articaifup",0755);
    @chmod("/etc/ppp/ip-up.d/0000articaifup",0755);
    echo "/etc/ppp/ip-up.d/0000articaifup done..\n";
    echo "/etc/ppp/ip-down.d/0000articaifup done..\n";



    foreach ($results as $index=>$ligne){
        $connexion_name=$ligne["connexion_name"];
        $outgoing_interface=null;
        $ID=$ligne["ID"];
        if(isset($CURRENT[$ID])){
            echo "Connection $ID $connexion_name already exists, modify it\n";
            unset($CURRENT[$ID]);
        }
        $servername=$ligne["servername"];
        $username=$ligne["username"];
        $password=$ligne["password"];
        $MPPE=$ligne["mppe"];
        $AUTH=$ligne["authentication"];
        $interface=$ligne["interface"];
        $localbind=$unix->InterfaceToIPv4($interface);
        if($localbind<>null){
            $outgoing_interface=" --localbind $localbind";
        }
        $conf=array();
        $conf[]="# written by Artica for connection $connexion_name";
        $conf[]="pty \"/usr/sbin/pptp $servername{$outgoing_interface}  --nolaunchpppd --logstring CNX{$ID}\"";
        $conf[]="lock";
        $conf[]="noauth";
        $conf[]="nobsdcomp";
        $conf[]="nodeflate";
        $conf[]="name $username";
       // $conf[]="PPTP_$ID";

        if($AUTH==1){
            $conf[] = "refuse-pap";
            $conf[] = "refuse-eap";


            if($MPPE==1 or $MPPE==2) {
                $conf[] = "require-mppe-128";
            }

            if($MPPE==0) {
                $conf[] = "refuse-mppe-128";
            }

        }

        if($AUTH==2){
            $conf[] = "require-pap";
            $conf[] = "refuse-eap";
            $conf[] = "refuse-mppe-128";

        }

        $conf[]="remotename PPTP_$ID";
        $conf[]="ipparam PPTP_$ID";
        $conf[]="";
        $chaps[]="$username PPTP_$ID \"$password\" *";
        echo "Saving configuration for $connexion_name $maindir/PPTP_$ID\n";
        @file_put_contents("$maindir/PPTP_$ID",@implode("\n",$conf));


    }

    echo "Saving configuration $chap_secrets ".count($chaps)." credentials\n";
    @file_put_contents($chap_secrets,@implode("\n",$chaps));

    $poff=$unix->find_program("poff");

    foreach ($CURRENT as $ID=>$path){
        echo "Stopping PPTP_$ID";
        shell_exec("$poff PPTP_$ID");
        sleep(1);
        @unlink($path);

    }
}

function status_all(){
$unix=new unix();
exec("/usr/share/artica-postfix/bin/pptp-pid.py 2>&1",$results);
$ID=0;
foreach ($results as $line){
        if(preg_match("#IFNAME=(.+)#",$line,$re)){$IFNAME=$re[1];continue;}
        if(preg_match("#PPPD_PID=([0-9]+)#",$line,$re)){
            $PPPD_PID=$re[1];
            $cmdline=var_export(@file_get_contents("/proc/$PPPD_PID/cmdline"),true);
            if(preg_match("#PPTP_([0-9]+)#",$cmdline,$re)){$ID=$re[1];}
            $PPD[$ID]["IFNAME"]=$IFNAME;
            $PPD[$ID]["PID"]=$PPPD_PID;
            $PPD[$ID]["TTL"]=$unix->PROCESS_TTL($PPPD_PID);
            continue;
        }
        if(preg_match("#IPREMOTE=([0-9\.]+)#",$line,$re)){
            if($ID > 0) {
                $PPD[$ID]["IPREMOTE"] = $re[1];
            }
            continue;
        }
    }

    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/pptp.status",serialize($PPD));
}

function events($text){
    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("pptp", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}


function build_syslog(){
    $RELOAD=false;
    $Filename="/etc/rsyslog.d/pptp-client.conf";
    if(!is_file($Filename)){$RELOAD=true;}

    $f[]="if  (\$programname =='pptp') then {";
    $f[]="\t-/var/log/pptp.log";
    $f[]="\t& stop";
    $f[]="}\n";
    $f[]="";
    $f[]="if  (\$programname =='pppd') then {";
    $f[]="\t-/var/log/pptp.log";
    $f[]="\t& stop";
    $f[]="}\n";



    @file_put_contents($Filename,@implode("\n",$f));

    if($RELOAD){
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

}

function create_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/pptp-client";
    $php5script=basename(__FILE__);
    $daemonbinLog="PPTP VPN Client";
    $Provides=basename($INITD_PATH);


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:        $Provides";
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
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]=" build)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    ;;";
    $f[]=" force-reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]=" reload-database)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]=" reload-log)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";

    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: $INITD_PATH {start|stop|restart|force-reload|reload-log|reload-database|status} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


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

function uninstall(){
    build_progress(10,"{uninstall}");
    $unix=new unix();
    $rm =$unix->find_program("rm");
    if(is_dir("/etc/ppp/peers")){shell_exec("$rm -rf /etc/ppp/peers");}
    @unlink("/etc/ppp/ip-up.d/0000articaifup");
    @unlink("/etc/ppp/ip-down.d/0000articaifup");

    if(is_file("/etc/rsyslog.d/pptp-client.conf")){
        @unlink("/etc/rsyslog.d/pptp-client.conf");
    }

    if(is_file("/etc/cron.d/pptp-vpn")){
        @unlink("/etc/cron.d/pptp-vpn");
        UNIX_RESTART_CRON();
    }

    if(is_file("/var/log/pptp.log")){@unlink("/var/log/pptp.log");}
    build_progress(50,"{uninstall}");
    remove_service("/etc/init.d/pptp-client");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnablePPTPClient",0);


}
