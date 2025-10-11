<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$GLOBALS["UFDBTAIL"]=false;
$GLOBALS["TITLENAME"]="DNS Load-balancing Daemon";
$GLOBALS["OUTPUT"]=true;
include_once("/usr/share/artica-postfix/ressources/class.resolv.conf.inc");
include_once("/usr/share/artica-postfix/ressources/class.squid.inc");
include_once("/usr/share/artica-postfix/ressources/class.sqlite.inc");
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--reload"){reload();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--destroy-log"){destroy_log($argv[2]);}

function PID_NUM(){
    $unix=new unix();
    return $unix->PIDOF_PATTERN("sbin\/squid-dns -C \/etc\/squid3");
}
function stop($aspid=false):bool{
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        return true;
    }
    $pid=PID_NUM();

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    $unix->KILL_PROCESS($pid,9);
    for($i=0;$i<10;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        $unix->KILL_PROCESS($pid,9);
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";
        exec("/usr/sbin/ip addr del 127.0.0.253 dev lo");
        @unlink("/var/run/dnsdist-squid.pid");
        return true;
    }

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    $unix->KILL_PROCESS($pid,9);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        $unix->KILL_PROCESS($pid,9);
        sleep(1);
    }

    if($unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";
        return false;
    }
    exec("/usr/sbin/ip addr del 127.0.0.253 dev lo");
    @unlink("/var/run/dnsdist-squid.pid");
    return true;

}

function build_monit(){
    $tfile="/etc/monit/conf.d/APP_DNSDIST_SQUID.monitrc";
    if(!is_file($tfile)){
    return;
    }
    @unlink($tfile);
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");




}
function upgrade(){
    $unix=new unix();
    $SourceBin=$unix->find_program("dnsdist");
    $DestBin="/usr/sbin/squid-dns";
    $md51=md5_file($SourceBin);
    $md52=md5_file($DestBin);
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Source $md51 / Destination $md52\n";
    if($md51==$md52){return true;}
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Updating $DestBin software\n";
    @copy($SourceBin,$DestBin);
    @chmod($DestBin,0755);
    return true;
}

function update(){
       return true;
}

function start($aspid=false){
    uninstall();
    return false;

}

function restart(){
    uninstall();
}


function build(){
}

function reload(){
    Uninstall();
    return true;
}

function uninstall(){
    $tfile="/etc/monit/conf.d/APP_DNSDIST_SQUID.monitrc";
    build_progress(10,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidDNSUseLB",0);
    remove_service("/etc/init.d/squid-dns");
    build_progress(20,"{uninstalling}");
    if(is_file($tfile)){
        @unlink($tfile);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    }
    build_progress(50,"{uninstalling}");
    build_progress(100,"{uninstalling} {success}");

}
function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"proxydns.progress");
}
function build_progress_restart($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"proxydns.restart.progress");
}
function etc_hosts(){
    $f[]="-- check whether a file exists and can be opened for reading";
    $f[]="local function file_exists(name)";
    $f[]="  local f = io.open(name, \"r\")";
    $f[]="  return f ~= nil and io.close(f)";
    $f[]="end";
    $f[]="";
    $f[]="-- read all lines from given file & invoke callback with (ip, hostname) as argument;";
    $f[]="-- nonexisting files are ignored.";
    $f[]="local function forEachHost(file, callback)";
    $f[]="  if not file_exists(file) then return end";
    $f[]="  for line in io.lines(file) do";
    $f[]="    -- ignore empty lines and comments";
    $f[]="    if string.len(line) ~= 0 and string.find(line, \"^#\") == nil then";
    $f[]="      -- ignore any localhost entries";
    $f[]="      if not string.match(line, \"localhost\") then";
    $f[]="        -- separate IP and all names";
    $f[]="        for ip, space, host in string.gmatch(line, \"(%g+)(%s+)(%g.+)\") do";
    $f[]="          -- create entries for every name";
    $f[]="          for name in string.gmatch(host, \"(%g+)\") do";
    $f[]="            callback(ip, name)";
    $f[]="          end";
    $f[]="        end";
    $f[]="      end";
    $f[]="    end";
    $f[]="  end";
    $f[]="end";
    $f[]="";
    $f[]="-- read all lines from given file & invoke callback with (domain) as argument;";
    $f[]="-- nonexisting files are ignored.";
    $f[]="local function forEachDomain(file, callback)";
    $f[]="  if not file_exists(file) then return end";
    $f[]="  for line in io.lines(file) do";
    $f[]="    -- ignore empty lines and comments";
    $f[]="    if string.len(line) ~= 0 and string.find(line, \"^#\") == nil then";
    $f[]="      -- create entries for every name";
    $f[]="      for domain in string.gmatch(line, \"(%g+)\") do";
    $f[]="        callback(domain)";
    $f[]="      end";
    $f[]="    end";
    $f[]="  end";
    $f[]="end";
    $f[]="";
    $f[]="-- create spoof rules for all entries in the given hosts file";
    $f[]="function addHosts(file)";
    $f[]="  forEachHost(file, function(ip, hostname) addAction(hostname, SpoofAction({ip}, {ttl=3600}), {name=hostname}) end)";
    $f[]="end";
    $f[]="";
    $f[]="-- create nxdomain rules for all entries in the given file";
    $f[]="function blockDomains(file)";
    $f[]="  forEachDomain(file, function(domain) addAction(domain, RCodeAction(DNSRCode.NXDOMAIN), {name=domain}) end)";
    $f[]="end";

    $tmpf[]="/usr/local/share/lua/5.3/hosts.lua";
    $tmpf[]="/usr/local/share/lua/5.3/hosts/init.lua";
    $tmpf[]="/usr/local/lib/lua/5.3/hosts.lua";
    $tmpf[]="/usr/local/lib/lua/5.3/hosts/init.lua";
    $tmpf[]="/usr/share/lua/5.3/hosts.lua";
    $tmpf[]="/usr/share/lua/5.3/hosts/init.lua";
    foreach ($tmpf as $fname){
        $drname=dirname($fname);
        if(!is_dir($drname)){@mkdir($drname,0755,true);}
        @file_put_contents($fname,@implode("\n",$f));
    }



}

function install(){
   return Uninstall();

}


function reconfigure_squid(){
    return true;
}

function LOG_FILE(){
    return "/var/log/squid/dns.log";
}

function destroy_log(){
    $unix=new unix();
    $LOG_FILE=LOG_FILE();
    $filelog_size=@filesize($LOG_FILE);
    $filelog_text=$unix->FormatBytes($filelog_size/1024);
    squid_admin_mysql(0,"{APP_DNSDIST_SQUID} {logfile_exceed_rule} ($filelog_text), remove it");
    @unlink($LOG_FILE);
    @touch($LOG_FILE);
    restart();


}


function dnsdist_squid_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/squid-dns";
    $php5script=basename(__FILE__);
    $daemonbinLog="DNS Load-balancing Service";


    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         squid-dns";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]=". /lib/lsb/init-functions";

    $f[]="case \"\$1\" in";
    $f[]=" start)";

    $f[]="\t$php /usr/share/artica-postfix/$php5script --start \$2 \$3";

    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" rotatelog)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --rotatelog \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|rotatelog} (+ '--verbose' for more infos)\"";
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

function google_extensions():array{
    $googlef[]="com";
    $googlef[]="fr";
    $googlef[]="ad";
    $googlef[]="ae";
    $googlef[]="com.af";
    $googlef[]="com.ag";
    $googlef[]="com.ai";
    $googlef[]="al";
    $googlef[]="am";
    $googlef[]="co.ao";
    $googlef[]="com.ar";
    $googlef[]="as";
    $googlef[]="at";
    $googlef[]="com.au";
    $googlef[]="az";
    $googlef[]="ba";
    $googlef[]="com.bd";
    $googlef[]="be";
    $googlef[]="bf";
    $googlef[]="bg";
    $googlef[]="com.bh";
    $googlef[]="bi";
    $googlef[]="bj";
    $googlef[]="com.bn";
    $googlef[]="com.bo";
    $googlef[]="com.br";
    $googlef[]="bs";
    $googlef[]="bt";
    $googlef[]="co.bw";
    $googlef[]="by";
    $googlef[]="com.bz";
    $googlef[]="ca";
    $googlef[]="cd";
    $googlef[]="cf";
    $googlef[]="cg";
    $googlef[]="ch";
    $googlef[]="ci";
    $googlef[]="co.ck";
    $googlef[]="cl";
    $googlef[]="cm";
    $googlef[]="cn";
    $googlef[]="com.co";
    $googlef[]="co.cr";
    $googlef[]="com.cu";
    $googlef[]="cv";
    $googlef[]="com.cy";
    $googlef[]="cz";
    $googlef[]="de";
    $googlef[]="dj";
    $googlef[]="dk";
    $googlef[]="dm";
    $googlef[]="com.do";
    $googlef[]="dz";
    $googlef[]="com.ec";
    $googlef[]="ee";
    $googlef[]="com.eg";
    $googlef[]="es";
    $googlef[]="com.et";
    $googlef[]="fi";
    $googlef[]="com.fj";
    $googlef[]="fm";
    $googlef[]="fr";
    $googlef[]="ga";
    $googlef[]="ge";
    $googlef[]="gg";
    $googlef[]="com.gh";
    $googlef[]="com.gi";
    $googlef[]="gl";
    $googlef[]="gm";
    $googlef[]="gp";
    $googlef[]="gr";
    $googlef[]="com.gt";
    $googlef[]="gy";
    $googlef[]="com.hk";
    $googlef[]="hn";
    $googlef[]="hr";
    $googlef[]="ht";
    $googlef[]="hu";
    $googlef[]="co.id";
    $googlef[]="ie";
    $googlef[]="co.il";
    $googlef[]="im";
    $googlef[]="co.in";
    $googlef[]="iq";
    $googlef[]="is";
    $googlef[]="it";
    $googlef[]="je";
    $googlef[]="com.jm";
    $googlef[]="jo";
    $googlef[]="co.jp";
    $googlef[]="co.ke";
    $googlef[]="com.kh";
    $googlef[]="ki";
    $googlef[]="kg";
    $googlef[]="co.kr";
    $googlef[]="com.kw";
    $googlef[]="kz";
    $googlef[]="la";
    $googlef[]="com.lb";
    $googlef[]="li";
    $googlef[]="lk";
    $googlef[]="co.ls";
    $googlef[]="lt";
    $googlef[]="lu";
    $googlef[]="lv";
    $googlef[]="com.ly";
    $googlef[]="co.ma";
    $googlef[]="md";
    $googlef[]="me";
    $googlef[]="mg";
    $googlef[]="mk";
    $googlef[]="ml";
    $googlef[]="com.mm";
    $googlef[]="mn";
    $googlef[]="ms";
    $googlef[]="com.mt";
    $googlef[]="mu";
    $googlef[]="mv";
    $googlef[]="mw";
    $googlef[]="com.mx";
    $googlef[]="com.my";
    $googlef[]="co.mz";
    $googlef[]="com.na";
    $googlef[]="com.nf";
    $googlef[]="com.ng";
    $googlef[]="com.ni";
    $googlef[]="ne";
    $googlef[]="nl";
    $googlef[]="no";
    $googlef[]="com.np";
    $googlef[]="nr";
    $googlef[]="nu";
    $googlef[]="co.nz";
    $googlef[]="com.om";
    $googlef[]="com.pa";
    $googlef[]="com.pe";
    $googlef[]="com.pg";
    $googlef[]="com.ph";
    $googlef[]="com.pk";
    $googlef[]="pl";
    $googlef[]="pn";
    $googlef[]="com.pr";
    $googlef[]="ps";
    $googlef[]="pt";
    $googlef[]="com.py";
    $googlef[]="com.qa";
    $googlef[]="ro";
    $googlef[]="ru";
    $googlef[]="rw";
    $googlef[]="com.sa";
    $googlef[]="com.sb";
    $googlef[]="sc";
    $googlef[]="se";
    $googlef[]="com.sg";
    $googlef[]="sh";
    $googlef[]="si";
    $googlef[]="sk";
    $googlef[]="com.sl";
    $googlef[]="sn";
    $googlef[]="so";
    $googlef[]="sm";
    $googlef[]="sr";
    $googlef[]="st";
    $googlef[]="com.sv";
    $googlef[]="td";
    $googlef[]="tg";
    $googlef[]="co.th";
    $googlef[]="com.tj";
    $googlef[]="tk";
    $googlef[]="tl";
    $googlef[]="tm";
    $googlef[]="tn";
    $googlef[]="to";
    $googlef[]="com.tr";
    $googlef[]="tt";
    $googlef[]="com.tw";
    $googlef[]="co.tz";
    $googlef[]="com.ua";
    $googlef[]="co.ug";
    $googlef[]="co.uk";
    $googlef[]="com.uy";
    $googlef[]="co.uz";
    $googlef[]="com.vc";
    $googlef[]="co.ve";
    $googlef[]="vg";
    $googlef[]="co.vi";
    $googlef[]="com.vn";
    $googlef[]="vu";
    $googlef[]="ws";
    $googlef[]="rs";
    $googlef[]="co.za";
    $googlef[]="co.zm";
    $googlef[]="co.zw";
    $googlef[]="cat";
    return $googlef;
}