<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["NOPID"]=false;
$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
$GLOBALS["debug"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){
    $GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir");
    if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; }
}

if(isset($argv[1])){
    if($argv[1]=="--step1"){support_step1();exit();}
    if($argv[1]=="--step2"){support_step2();exit();}
    if($argv[1]=="--step3"){support_step3();exit();}
}

build();


function build(){
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $unix=new unix();
    $sock=new sockets();
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){exit();}
    $php=$unix->LOCATE_PHP5_BIN();
    @file_put_contents($pidfile, getmypid());
    $i=10;
    progress("{get_system_informations}",$i++);
    $i=support_step1($i);
    progress("{APP_UFDBGUARD}",$i++);
    $EnableUfdbGuard=intval($sock->EnableUfdbGuard());

    if($EnableUfdbGuard==1){
        $ufdbguardd=$unix->find_program("ufdbguardd");
        if(is_file($ufdbguardd)){
            shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --build --force --verbose >/usr/share/artica-postfix/ressources/support/build-ufdbguard.log 2>&1");
        }
    }

    $i=30;
    progress("{get_all_logs}",$i++);
    $i=support_step2($i);
    progress("{get_all_logs}",$i++);
    progress("{compressing_package}",$i++);
    support_step3();
    @file_put_contents("/etc/artica-postfix/support-tool-prc", $i);
    progress("{compressing_package} {success}",100);

}

function progress($title,$perc):bool{
    if($perc>100){$perc=100;}
    echo "$title,$perc\n";
    echo "Starting......: ".date("H:i:s")." {$perc}% $title\n";
    $cachefile=PROGRESS_DIR."/squid.debug.support-tool.progress";
    $array["POURC"]=$perc;
    $array["TEXT"]=$title;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
    return true;
}

function support_step1($i=0):int{
    $unix=new unix();
    $ps=$unix->find_program("ps");
    $df=$unix->find_program("df");
    $du=$unix->find_program("du");
    $lsof=$unix->find_program("lsof");
    $free=$unix->find_program("free");
    $files[]="/etc/hostname";
    $files[]="/etc/resolv.conf";
    $files[]="/usr/share/artica-postfix/ressources/settings.inc";
    $files[]="/usr/share/artica-postfix/ressources/logs/global.status.ini";
    $files[]="/usr/share/artica-postfix/ressources/logs/global.versions.conf";
    $files[]="/var/log/lighttpd/squidguard-lighttpd-error.log";
    $files[]="/var/log/lighttpd/squidguard-lighttpd.start";
    $files[]="/var/log/artica-parse.hourly.log";
    $files[]="/var/log/license.log";
    $files[]="/etc/init.d/tproxy";
    $files[]="/etc/init.d/artica-ifup";
    $files[]="/var/log/squid/artica.watchdog.log";
    $files[]="/etc/dnsfilterd/dnsfilterd.conf";
    $files[]="/etc/unbound/unbound.conf";
    $files[]="/var/log/activedirectory.log";
    $files[]="/var/log/automount.log";
    $files[]="/home/artica/philesight/system.db";
    $files[]="/var/log/lighttpd/hotspot-service.log";
    $files[]="/var/log/suricata/suricata.log";
    $files[]="/var/log/suricata.start";
    $files[]="/var/log/phpfpm.framework.access.log";
    progress("{remove}",$i++);
    if(is_dir("/usr/share/artica-postfix/ressources/support")){
        shell_exec("/bin/rm -rf /usr/share/artica-postfix/ressources/support");
    }

    $WORKDIR="/usr/share/artica-postfix/ressources/support";
    @mkdir($WORKDIR,0755,true);
    foreach ($files as $b){
        $destfile=basename($b);
        $dirname=dirname($b);
        $dirname_final=str_replace("/","_",$dirname);
        $DESTDIR="$WORKDIR/$dirname_final";
        if(!is_dir($DESTDIR)){@mkdir($DESTDIR,0755);}
        progress("$destfile",$i++);
        if(preg_match("#\.log$#",$destfile)){
            $unix->compress($b,"$DESTDIR/$destfile.gz");
            continue;
        }
        @copy($b, "$DESTDIR/$destfile");
    }

    $iptables=$unix->find_program("iptables-save");
    @chmod("/usr/share/artica-postfix/bin/smemstat",0755);

    progress("{get_system_informations}",$i++);
    shell_exec("$ps auxww >/usr/share/artica-postfix/ressources/support/ps.txt 2>&1");
    shell_exec("/usr/share/artica-postfix/bin/smemstat -k >/usr/share/artica-postfix/ressources/support/smemstat.txt 2>&1");
    
    
    progress("{get_system_informations}",$i++);
    shell_exec("$df -h >/usr/share/artica-postfix/ressources/support/dfh.txt 2>&1");
    progress("{get_system_informations}",$i++);
    shell_exec("$df -i >/usr/share/artica-postfix/ressources/support/dfi.txt 2>&1");

    progress("$lsof",$i++);
    shell_exec("$lsof >/usr/share/artica-postfix/ressources/support/lsof.txt 2>&1");

    progress("$iptables",$i++);
    shell_exec("$iptables >/usr/share/artica-postfix/ressources/support/iptables.txt 2>&1");


    progress("ps_mem",$i++);
    shell_exec("/usr/share/artica-postfix/bin/ps_mem.py  >/usr/share/artica-postfix/ressources/support/ps_mem.txt 2>&1");

    progress("$free -m",$i++);
    shell_exec("$free -m  >/usr/share/artica-postfix/ressources/support/free.txt 2>&1");

    if(is_file("/etc/init.d/isc-dhcp-server")){
        $dhcpd=$unix->find_program("dhcpd");
        progress("Testing DHCPD ",$i++);
        shell_exec("$dhcpd -t -cf /etc/dhcp3/dhcpd.conf >/usr/share/artica-postfix/ressources/support/dhcpd.txt 2>&1 &");
    }

    progress("{scanning} /var/log {partition}",$i++);
    shell_exec("$du -h /var/log --max-dep=1 >/usr/share/artica-postfix/ressources/support/var-log-sizes.txt 2>&1");
    progress("{scanning} {network}",$i++);
    $report=$unix->NETWORK_REPORT();
    @file_put_contents("/usr/share/artica-postfix/ressources/support/NETWORK_REPORT.txt", $report);
    return $i;
}

function export_tables($i=0):int{
    $q=new mysql();
    $unix=new unix();
    $f=array();
    $tmppath=$unix->TEMP_DIR();
    $sql="SELECT *  FROM `squid_admin_mysql` ORDER BY zDate DESC";
    $results = $q->QUERY_SQL($sql,"artica_events");
    while ($ligne = mysqli_fetch_assoc($results)) {
        $f[]="{$ligne["zDate"]}:{$ligne["filename"]} {function}:{$ligne["function"]}, {line}:{$ligne["line"]}";
        $f[]="{$ligne["subject"]}";
        $f[]="{$ligne["content"]}";
        $f[]="************************************************************************************************************";
        $f[]="";
    }
    progress("{get_all_logs}",$i++);
    @file_put_contents("$tmppath/squid_admin_mysql.log", @implode("\n", $f));
    $unix->compress("$tmppath/squid_admin_mysql.log", "/usr/share/artica-postfix/ressources/support/squid_admin_mysql.log.gz");
    @unlink("$tmppath/squid_admin_mysql.log");
    progress("{get_all_logs}",$i++);
    progress("{get_all_logs}",$i++);
    return $i;


}


function support_step2($i=0):int{

    $files[]="/var/log/dns-firewall.log";
    $files[]="/var/log/ITCharter.log";
    $files[]="/var/log/itcharter-server.log";
    $files[]="/var/log/unbound.log";
    $files[]="/var/log/syslog";
    $files[]="/var/log/slapd.log";
    $files[]="/var/log/messages";
    $files[]="/var/log/auth.log";
    $files[]="/var/log/squid/access.log";
    $files[]="/var/log/squid/external-acl.log";
    $files[]="/var/log/squid/logfile_daemon.debug";
    $files[]="/var/log/squid.watchdog.log";
    $files[]="/var/log/squid/ufdbguardd.log";
    $files[]="/var/log/squid/cache.log";
    $files[]="/var/log/squid/acl_categories.log";
    $files[]="/var/log/squid/artica.watchdog.log";
    $files[]="/var/log/squid/external_acl.log";
    $files[]="/var/log/squid/squidGuard.log";
    $files[]="/var/log/squid/ufdbgclient.debug";
    $files[]="/var/log/tailon.log";
    $files[]="/var/log/ntpdate.log";
    $files[]="/var/log/ntml.status.log";
    $files[]="/var/log/theshields-daemon.log";
    $files[]="/var/log/stats-communicator.debug";
    $files[]="/var/log/webfiltering.log";
    $files[]="/var/log/dhcpd.log";
    $files[]="/var/log/php.log";
    $files[]="/var/log/mail.log";
    $files[]="/var/log/smokeping.log";
    $files[]="/var/log/admintracks.log";
    $files[]="/var/log/memcached.log";
    $files[]="/var/log/artica-status.log";
    $files[]="/var/log/memcached.log";
    $files[]="/var/log/HaClusterClient.log";
    $files[]="/var/log/squid/proxy-watchdog.log";
    $files[]="/var/log/freshclam.log";

    $files[]="/var/log/fw-nginx.log";
    $files[]="/var/log/artica-auth-service.log";
    $files[]="/var/log/artica-milter.log";
    $files[]="/var/log/apt-mirror.log";
    $files[]="/var/log/letsencrypt.log";
    $files[]="/var/log/internetwatch.log";
    $files[]="/var/log/articarest.log";

    $files[]="/var/log/samba/log.winbindd";
    $files[]="/etc/samba/smb.conf";
    $files[]="/var/log/samba/log.nmbd";
    $files[]="/var/log/samba/log.smbd";
    $files[]="/var/run/mysqld/mysqld.err";
    $files[]="/etc/init.d/artica-ifup";
    $files[]="/var/log/net-start.log";
    $files[]="/var/log/artica-ufdb.log";
    $files[]="/var/log/artica-meta.log";
    $files[]="/var/log/rsyslogd.log";
    $files[]="/var/log/webfiltering-update.log";
    $files[]="/var/log/hotspot.debug";
    $files[]="/var/log/ufdb-http.log";
    $files[]="/var/log/monit.log";
    $files[]="/var/log/hacluster.log";
    $files[]="/var/log/firewall.log";
    $files[]="/var/log/tailscale.log";
    $files[]="/var/log/hacluster-client.log";
    $files[]="/var/log/hacluster-connections.log";
    $files[]="/var/log/dns-firewall.log";
    $files[]="/var/log/k5start.log";
    $files[]="/proc/cpuinfo";
    $files[]="/etc/hosts";
    $files[]="/home/ArticaStatsDB/PG_VERSION";
    $files[]="{$GLOBALS["ARTICALOGDIR"]}/ufdbguard-tail.debug";
    $files[]=dhcp3Config();
    $files[]="/etc/dhcp3/dhcp3.builded";
    shell_exec("grep -i fatal /var/log/php.log >/var/log/php.error.log");
    $files[]="/var/log/php.error.log";
    $files[]="/etc/dhcp3/dhcp3.builded";
    $files[]="/var/log/dhcpd.log";
    $files[]="/var/log/artica-dhcpd.log";
    $files[]="/var/lib/dhcp3/dhcpd.leases";
    $files[]="/etc/dhcp3/dhcpd.conf";
    $files[]="/etc/dhcpd-reservations.conf";
    $files[]="/var/lib/dhcp3/dhcpd.other";
    $files[]="/var/log/artica-smtp-daemon.log";
    $files[]="/var/log/ksrn.log";
    $files[]="/etc/dnsdist.conf";
    $files[]="/etc/dnsdist.builded";
    $files[]="/var/log/postfix.log";


    // PostgreSQL.
    $files[]="/home/ArticaStatsDB/postgresql.conf";
    $files[]="/home/ArticaStatsDB/pg_hba.conf";
    $files[]="/var/log/postgres.log";

    $unix=new unix();
    $dmesg=$unix->find_program("dmesg");
    @mkdir("/usr/share/artica-postfix/ressources/support",0755,true);
    shell_exec("$dmesg >/usr/share/artica-postfix/ressources/support/dmesg.txt");

    $f[]="/etc/squid3";
    $f[]="/etc/hypercache";
    $f[]="/etc/postfix";
    $f[]="/etc/artica-postfix/settings/Daemons";
    $f[]="/home/artica/SQLITE";
    $f[]="/var/log/dnsfilterd";
    $f[]="/etc/rsyslog.d";
    $f[]="/etc/monit/conf.d";
    $f[]="/home/ArticaStatsDB/log";
    $f[]="/var/log/nginx";
    $f[]="/usr/share/artica-postfix/img/squid";
    $f[]="/var/log/go-shield";

    $scans=scandir("/etc");
    foreach ($scans as $dirname){
        if(!preg_match("#postfix-#",$dirname)){continue;}
        $f[]="/etc/$dirname";
    }

    progress("{get_all_logs}",$i++);
    $WORKDIR="/usr/share/artica-postfix/ressources/support";

    foreach($f as $dir){
        if(is_link($dir)){$dir=@readlink($dir);}
        if(!is_dir($dir)){continue;}
        progress("{get_all_logs} $dir",$i++);
        $dirname=str_replace("/", "_", $dir);
        @mkdir("$WORKDIR/$dirname",0755,true);
        $cmd="/bin/cp -rf $dir/* $WORKDIR/$dirname/";
        shell_exec("$cmd");
    }

    $squidbin=$unix->LOCATE_SQUID_BIN();
    $tempsquid=$unix->TEMP_DIR()."/squid.conf";
    progress("{get_all_logs}",$i++);
    if(is_file($tempsquid)){
        if(is_file($squidbin)){
            shell_exec("$squidbin -f $tempsquid -k parse >/etc-squid3/tmp.squid.conf.log 2>&1");
        }
        @copy($tempsquid, "/usr/share/artica-postfix/ressources/support/etc-squid3/tmp.squid.conf");
    }


    progress("{get_all_logs}",$i++);
    foreach ($files as $b){
        if(is_file($b)){
            $size=@filesize($b);
            $sizeKo=$size/1024;
            $sizeMo=$sizeKo/1024;
            if($sizeMo>650){
                progress("{get_all_logs}: Skipping $b",$i++);
                continue;
            }

            progress("{get_all_logs}:".basename($b),$i++);
            $destfile=basename("$b.gz");
            $dirname=dirname($b);
            $dirname_finale=str_replace("/","_",$dirname);
            if(!is_dir("$WORKDIR/$dirname_finale")){@mkdir("$WORKDIR/$dirname_finale",0755,true);}
            $unix->compress($b, "$WORKDIR/$dirname_finale/$destfile");
        }
    }

    progress("{get_all_logs} lshw",$i++);
    $lshw=$unix->find_program("lshw");
    exec("$lshw -class network 2>&1",$results);

    progress("{get_all_logs} ifconfig",$i++);
    $ifconfig=$unix->find_program("ifconfig");
    exec("$ifconfig -a 2>&1",$results);
    $results[]="\n\t***************\n";
    progress("{get_all_logs} IP",$i++);
    $ip=$unix->find_program("ip");
    exec("$ip link show 2>&1",$results);
    $results[]="\n\t***************\n";
    progress("{get_all_logs} Route",$i++);
    exec("$ip route 2>&1",$results);
    $results[]="\n\t***************\n";

    $f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
    foreach ($f as $line){
        if(!preg_match("#^([0-9]+)\s+(.+)#", $line,$re)){continue;}
        $table_num=$re[1];
        $tablename=$re[2];
        if($table_num==0){continue;}
        if($table_num>252){continue;}
        $results[]="\n\t***** Table route $table_num named $tablename *****\n";
        exec("$ip route show table $table_num 2>&1",$results);
        $results[]="\n\t***************\n";
    }

    progress("{get_all_logs} uname",$i++);
    $unix=new unix();
    $uname=$unix->find_program("uname");
    $results[]="$uname -a:";
    exec("$uname -a 2>&1",$results);
    $results[]="\n";
    $results[]="/bin/bash --version:";
    exec("/bin/bash --version 2>&1",$results);

    $results[]="\n";

    progress("{get_all_logs} gdb",$i++);
    $gdb=$unix->find_program("gdb");
    if(is_file($gdb)){
        $results[]="$gdb --version:";
        exec("$gdb --version 2>&1",$results);
    }else{
        $results[]="gdb no such binary....";
    }
    $results[]="\n";
    $smbd=$unix->find_program("smbd");
    if(is_file($smbd)){
        $results[]="$smbd -V:";
        exec("$smbd -V 2>&1",$results);
    }else{
        $results[]="smbd no such binary....";
    }

    $results[]="\n";

    progress("{get_all_logs} $squidbin",$i++);
    if(is_file($squidbin)){
        $results[]="$squidbin -v:";
        exec("$squidbin -v 2>&1",$results);
        squid_watchdog_events("Reconfiguring Proxy parameters...");
        exec("/etc/init.d/squid reload --script=".basename(__FILE__)." 2>&1",$results);
        squid_admin_mysql(2, "Framework executed to reconfigure squid-cache", @implode("\n", $results));
    }else{
        $results[]="squid no such binary....";
    }
    $results[]="\n";
    $tempsquid=$unix->TEMP_DIR()."/squid.conf";
    progress("{get_all_logs}",$i++);
    if(is_file($squidbin)){
        $results[]="$squidbin -v:";
        exec("$squidbin -v 2>&1",$results);
        squid_watchdog_events("Reconfiguring Proxy parameters...");
        squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
        exec("/etc/init.d/squid reload --script=".basename(__FILE__)." 2>&1",$results);
        squid_admin_mysql(2, "Framework executed to reconfigure squid-cache", @implode("\n", $results));

        shell_exec("$squidbin -f /etc/squid3/squid.conf -k check -X 2>&1 >/usr/share/artica-postfix/ressources/support/squid-conf-check.txt");
        if(is_file($tempsquid)){
            shell_exec("$squidbin -f $tempsquid -k check -X 2>&1 >/usr/share/artica-postfix/ressources/support/squid-temp-check.txt");
        }


    }else{
        $results[]="squid3 no such binary....";
    }

    progress("{get_all_logs} DF",$i++);
    $results[]="\n";
    $df=$unix->find_program("df");
    if(is_file($df)){
        $results[]="$df -h:";
        exec("$df -h 2>&1",$results);
    }else{
        $results[]="$df no such binary....";
    }
    progress("{get_all_logs} sysctl",$i++);
    $results[]="\n\n--------------------------------------------";
    $sysctl=$unix->find_program("sysctl");
    exec("$sysctl -a 2>&1",$results);

    progress("{get_all_logs}",$i++);
    @copy("/proc/cpuinfo","/usr/share/artica-postfix/ressources/support/cpuinfos.txt");
    @file_put_contents("/usr/share/artica-postfix/ressources/support/generated.versions.txt", @implode("\n", $results));
    return $i;
}

function support_step3(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $tar=$unix->find_program("tar");
    $filename="support.tar.gz";
    $VERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
    $SP=intval(@file_get_contents("/usr/share/artica-postfix/SP/$VERSION"));
    @file_put_contents("/usr/share/artica-postfix/ressources/support/Artica-v$VERSION.SP.$SP.txt",time());


    chdir("/usr/share/artica-postfix/ressources/support");
    system("cd /usr/share/artica-postfix/ressources/support");
    $cmd="$tar -cvzf /usr/share/artica-postfix/ressources/support/$filename *";
    echo "***************\n$cmd\n***************\n\n";
    system($cmd);
    @chmod("/usr/share/artica-postfix/ressources/support/$filename", 0755);



    if(!is_file("/usr/share/artica-postfix/ressources/support/$filename")){
        shell_exec("$rm -rf /usr/share/artica-postfix/ressources/support");
        progress("{compressing_package} {failed}",110);
        exit();}



}

function dhcp3Config():string{

    $f[]="/etc/dhcp3/dhcpd.conf";
    $f[]="/etc/dhcpd.conf";
    $f[]="/etc/dhcpd/dhcpd.conf";
    foreach ($f as $filename){
        if(is_file($filename)){return $filename;}
    }
    return "/etc/dhcp3/dhcpd.conf";

}

function squid_watchdog_events($text):bool{
    $unix=new unix();
    $sourceline=0;
    $sourcefunction=null;
    if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$sourcefunction=$trace[1]["function"];$sourceline=$trace[1]["line"];}}
    $unix->events($text,"/var/log/squid.watchdog.log",false,$sourcefunction,$sourceline);
    return true;
}