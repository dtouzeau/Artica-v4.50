#!/usr/bin/php -q
<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__)."/ressources/class.users.menus.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.webconsole.params.inc");
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
$GLOBALS["TITLENAME"]="Network Latency Engine";


if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}


if(isset($argv[1])){
    if($argv[1]=="--install"){install();exit;}
    if($argv[1]=="--uninstall"){uninstall();exit;}
    if($argv[1]=="--build"){build();exit;}
    if($argv[1]=="--stop"){stop();exit;}
    if($argv[1]=="--restart"){restart();exit;}

    if($argv[1]=="--schedule"){run_schedule();exit;}
}


$unix=new unix();
foreach ($argv as $index=>$value){
    $unix->ToSyslog("Detected Notif:[$index]=$value",false,"smokeping");
}


function build_progress($prc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"smokeping.progress");
}

function restart(){
    build_progress(10,"{stopping}");
    stop();
    build_progress(40,"{stopping}");
    build();
    build_progress(50,"{starting}");
    $unix=new unix();
    $unix->go_exec("/etc/init.d/smokeping start");
    build_progress(100,"{starting} {success}");
}

function build(){
    $unix=new unix();
    $smokeping=$unix->find_program("smokeping");
    build_progress(10,"{building_configuration}");
    smokeping_config();
    build_progress(40,"{building_configuration}");
    checkdirectories();
    build_progress(45,"{building_configuration}");
    $pid=smokeping_pid();
    if($unix->process_exists($pid)){
        build_progress(50,"{reloading}");
        exec("$smokeping --reload 2>&1",$results);
        $RELOAD_AGAIN=false;
        foreach ($results as $line){
            if(preg_match("#Wrong value of step:\s+(.+?)\s+has\s+#i",$line,$re)){
                @unlink($re[1]);
                echo "[Removed]: {$re[1]}\n";
                $RELOAD_AGAIN=true;
                continue;
            }
            echo "[start]: $line\n";
        }
        if($RELOAD_AGAIN){
            $results=array();
            exec("$smokeping --reload 2>&1",$results);
            foreach ($results as $line){echo "[start]: $line\n";}
        }
        build_progress(100,"{success}");
        return true;
    }

    build_progress(80,"{starting_service}");


    $RELOAD_AGAIN=false;
    $results=array();
    exec("/etc/init.d/smokeping start 2>&1",$results);
    foreach ($results as $line){
        if(preg_match("#Wrong value of step:\s+(.+?)\s+has\s+#i",$line,$re)){
            @unlink($re[1]);
            echo "[Removed]: {$re[1]}\n";
            $RELOAD_AGAIN=true;
            continue;
        }
        echo "[start]: $line\n";
    }
    if($RELOAD_AGAIN){
        $results=array();
        exec("/etc/init.d/smokeping start 2>&1",$results);
        foreach ($results as $line){echo "[start]: $line\n";}
    }


    $pid=smokeping_pid();
    if(!$unix->process_exists($pid)){
        build_progress(110,"{failed}");
        return false;
    }
    build_progress(100,"{success}");
    return true;
}
function run_schedule(){
    $unix=new unix();
    $fdir="/var/cache/smokeping/threats";
    $handle = opendir($fdir);if(!$handle){return false;}
    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    $post=new postgres_sql();
    if(!$post->CREATE_SMOKEPING()){
        echo $q->mysql_error."\n";
    }
    $fsql=array();
    $tt=array();
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$fdir/$filename";

        if(!preg_match("#^([0-9]+)\.threat$#",$filename,$re)){continue;}
        $stime=$re[1];
        $type=null;
        $tt=explode("\n",@file_get_contents($targetFile));

        foreach ($tt as $line){
            if(preg_match("#(someloss|startloss|rttdetect|hostdown|lossdetect)#",$line,$re)){
                $type=$re[1];
                continue;
            }

            if(preg_match("#\.section_([0-9]+)#",$line,$re)){
                $ID=intval($re[1]);
                $sligne=$q->mysqli_fetch_array("SELECT title FROM smokeping WHERE ID='$ID'");
                $subject=$sligne["title"];
            }
        }

        if($type==null){
            $unix->ToSyslog("Missing TYPE for $targetFile",false,"smokeping");
            continue;
        }
        if($subject==null){
            $unix->ToSyslog("Missing Subject for $targetFile",false,"smokeping");
            continue;
        }

        $zdate=date("Y-m-d H:i:s",$stime);
        @unlink($targetFile);
        if(isset($ALREADY[$ID][$type])){continue;}
        $ALREADY[$ID][$type]=true;
        $fsql[]="('$zdate','$ID','$type')";
        $tt[]="Detected on: $zdate";
        squid_admin_mysql(0,"[$type]: $subject",@implode("\n",$tt),__FILE__,__LINE__);

    }

    if(count($tt)>0){
        $sql="INSERT INTO smokeping (zdate,ruleid,category) VALUES ".@implode(",",$fsql);
        $post->QUERY_SQL($sql);
        if(!$post->ok){
            $unix->ToSyslog("SQL Error $post->mysql_error",false,"smokeping");
            echo $post->mysql_error."\n";
            return false;
        }
        Synchronize();
        return true;
    }
    Synchronize();
    return true;
}

function Synchronize(){
    $unix           = new unix();
    $pidpath        = "/var/run/smokeping/smokeping-sync.pid";

    $CurrentPid=$unix->get_pid_from_file($pidpath);
    if($unix->process_exists($CurrentPid)){
        echo "Process $CurrentPid already exists: ".@file_get_contents("/proc/$CurrentPid/cmdline")."\n";
        return false;
    }
    $CurrentTime=$unix->file_time_min($CurrentPid);
    if($CurrentTime<30){return false;}
    @unlink($pidpath);
    @file_put_contents($pidpath,getmypid());

     $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
     if(!$q->FIELD_EXISTS("smokeping","scount")){
         $q->QUERY_SQL("ALTER TABLE smokeping ADD `scount` INTEGER NOT NULL DEFAULT 0");
     }
     $qp=new postgres_sql();
     $qp->QUERY_SQL("DELETE FROM smokeping WHERE zdate < NOW() - INTERVAL '30 days'");



     $results=$q->QUERY_SQL("SELECT * FROM smokeping");
     foreach ($results as $index=>$ligne){
         $IDSrc=$ligne["ID"];
         $sloss=$qp->mysqli_fetch_array("SELECT COUNT(*) as tcount FROM smokeping WHERE ruleid=$IDSrc");
         $tcount=$sloss["tcount"];
         $q->QUERY_SQL("UPDATE smokeping SET scount='$tcount' WHERE ID=$IDSrc");
     }

    return true;


}

function install(){
    build_progress(10,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSmokePing",1);
    build_progress(50,"{installing}");
    install_service();
    build_progress(60,"{installing}");
    install_fcgiwrap();
    build_progress(70,"{installing}");
    install_fcgiwrap_monit();
    build_progress(80,"{installing}");
    install_service_monit();
    build_progress(90,"{installing}");
    install_rsyslogd();
    build_progress(95,"{installing}");
    smokeping_config();
    build_progress(97,"{starting_service}");
    shell_exec("/etc/init.d/smokeping start");
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    build_progress(100,"{installing} {done}");
}

function build_database(){
    @mkdir("/home/artica/SQLITE", 0755, true);
    $qlite=new lib_sqlite("/home/artica/SQLITE/smokeping.db");
    @chmod("/home/artica/SQLITE/smokeping.db", 0644);
    @chown("/home/artica/SQLITE/smokeping.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);

    $sql="CREATE TABLE IF NOT EXISTS `smokeping` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `probe` TEXT NOT NULL DEFAULT 'FPing',
        `host` TEXT NOT NULL DEFAULT 'www.google.fr',
        `title` TEXT NOT NULL DEFAULT 'Ping host',
        `request` TEXT NOT NULL DEFAULT '',
        `step` INTEGER NOT NULL DEFAULT '120',
        `pings` INTEGER NOT NULL DEFAULT '20',
        `sourceaddress` TEXT NOT NULL DEFAULT 'eth0',
        `proxyaddress` TEXT NOT NULL DEFAULT '',
        `proxyport` INTEGER NOT NULL DEFAULT 3128,
        `scount` INTEGER NOT NULL DEFAULT 0,                                       
        `enabled` INTEGER NOT NULL DEFAULT 1
    )";

    $qlite->QUERY_SQL($sql);
    if (!$qlite->ok) {
        echo "Fatal: $qlite->mysql_error (".__LINE__.")\n";
    }

    $Number=$qlite->COUNT_ROWS("smokeping");
    $unix=new unix();
    if($Number==0){
        $f[]="('FPing','www.google.fr',1,'Google','')";
        $Nets=$unix->NETWORK_ALL_INTERFACES();
        foreach ($Nets as $interface=>$ip){

            $netz=new system_nic($interface);
            $GATEWAY=$netz->GATEWAY;
            echo "Scanning $interface ($GATEWAY)\n";
            if($GATEWAY==null){continue;}
            if($GATEWAY=="0.0.0.0"){continue;}
            if($GATEWAY=="no"){continue;}
            $f[]="('FPing','$GATEWAY',1,'Gateway $GATEWAY','')";
        }

        $resolv=new resolv_conf();
        if(isset($resolv->MainArray["DNS1"])){
            $f[]="('DNS','{$resolv->MainArray["DNS1"]}',1,'DNS 1','www.google.com')";
        }
        if(isset($resolv->MainArray["DNS2"])){
            $f[]="('DNS','{$resolv->MainArray["DNS2"]}',1,'DNS 2','www.google.com')";
        }
        if(isset($resolv->MainArray["DNS3"])){
            $f[]="('DNS','{$resolv->MainArray["DNS3"]}',1,'DNS 3','www.google.com')";
        }

        $f[]="('HTTP','http://www.msftncsi.com/ncsi.txt',1,'Microsoft HTTP Ping','')";
        $qlite->QUERY_SQL("INSERT INTO smokeping (probe,host,enabled,title,request) VALUES ".
            @implode(",",$f));
    }

}

function smokeping_pid():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/smokeping/smokeping.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF_PATTERN("/smokeping [");
}

function stop():bool{
    $unix=new unix();
    $pid=smokeping_pid();

    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";
        return true;
    }
    $pid=smokeping_pid();
    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=smokeping_pid();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    $pid=smokeping_pid();
    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";
        return true;
    }

    echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=smokeping_pid();
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
        sleep(1);
    }

    if($unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";
        return false;
    }
    return true;

}

function probes(){

    //$f["FPing"]="{ping_host}";

    $f[]="*** Probes ***";
    $f[]="+ FPing";
    $f[]="binary    = /usr/bin/fping";
    $f[]="";
    $f[]="";
    $f[]="+ DNS";
    $f[]="binary    = /usr/bin/dig";
    $f[]="forks     = 5";
    $f[]="offset    = 50%";
    $f[]="step      = 120";
    $f[]="timeout   = 15";
    $f[]="pings     = 5";
    $f[]="";
    $f[]="";
    $f[]="+Curl";
    $f[]="binary = /usr/bin/curl";
    $f[]="forks     = 5";
    $f[]="offset    = 50%";
    $f[]="step      = 120";
    $f[]="timeout   = 15";
    $f[]="pings     = 5";
    $f[]="";
    $f[]="";
    $f[]="+EchoPingHttp";
    $f[]="forks     = 5";
    $f[]="offset    = 50%";
    $f[]="step      = 120";
    $f[]="timeout   = 15";
    $f[]="pings     = 5";
    $f[]="";
    $f[]="";
    $f[]="+EchoPingHttps";
    $f[]="forks     = 5";
    $f[]="offset    = 50%";
    $f[]="step      = 120";
    $f[]="timeout   = 15";
    $f[]="pings     = 5";
    $f[]="";
    $f[]="";


    # if not, you'll have to specify the location separately for each probe
    # + EchoPing         # uses TCP or UDP echo (port 7)
    # + EchoPingDiscard  # uses TCP or UDP discard (port 9)
    # + EchoPingChargen  # uses TCP chargen (port 19)
   # $f[]="+ EchoPingSmtp";
   # $f[]="+ EchoPingHttps";
   # $f[]="+ EchoPingHttp";
   # $f[]="+ EchoPingDNS";
   # $f[]="+ EchoPingLDAP";
    @file_put_contents("/etc/smokeping/config.d/Probes",@implode("\n",$f));
    echo "/etc/smokeping/config.d/Probes [DONE]\n";
}

function General(){
    $unix=new unix();
    $fname="/etc/smokeping/config.d/General";
    $Myhostname=php_uname("n");
    $ArticaHttpsPort        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $ArticaHttpUseSSL       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));
    $LighttpdArticaListenInterface = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LighttpdArticaListenInterface"));
    if($ArticaHttpsPort==0){$ArticaHttpsPort=9000;}
    if($ArticaHttpsPort==443){$ArticaHttpUseSSL=1;}
$proto="http";
    if($ArticaHttpUseSSL==1){$proto="https";}

    if($LighttpdArticaListenInterface<>null) {
        $ipaddr = $unix->InterfaceToIPv4($LighttpdArticaListenInterface);
        if ($ipaddr <> null) {
            $Myhostname = $ipaddr;

        }
    }

    $f[]="*** General ***";
    $f[]="";
    $f[]="owner    = Peter Random";
    $f[]="contact  = some@address.nowhere";
    $f[]="mailhost = my.mail.host";
    $f[]="# NOTE: do not put the Image Cache below cgi-bin";
    $f[]="# since all files under cgi-bin will be executed ... this is not";
    $f[]="# good for images.";
    $f[]="cgiurl   = $proto://$Myhostname:$ArticaHttpsPort/smokeping/smokeping.cgi";
    $f[]="# specify this to get syslog logging";
    $f[]="syslogfacility = local0";
    $f[]="# each probe is now run in its own process";
    $f[]="# disable this to revert to the old behaviour";
    $f[]="# concurrentprobes = no";
    $f[]="";
    $f[]="@include /etc/smokeping/config.d/pathnames\n";
    @file_put_contents($fname,@implode("\n",$f));
    echo "$fname [DONE]\n";

}

function Alertes(){
    $script[]="#!/bin/sh";
    $script[]="timestamp=$(date +%s)";
    $script[]="fname=\"/var/cache/smokeping/threats/\$timestamp.threat\"";
    $script[]="\techo \"\$timestamp\" >\$fname | true";
    $script[]="for var in \"$@\"";
    $script[]="\tdo";
    $script[]="\techo \"\$var\"  >>\$fname | true";
    $script[]="done";
    $script[]="";

    @file_put_contents("/usr/sbin/smokeping-notif.sh",@implode("\n",$script));
    @chmod("/usr/sbin/smokeping-notif.sh",0755);
    $fname="/etc/smokeping/config.d/Alerts";
    $f[]="*** Alerts ***";
    $f[]="to = |/usr/sbin/smokeping-notif.sh";
    $f[]="from = joe@somehost";

    $f[]="+bigloss";
    $f[]="type = loss";
    $f[]="# In percent";
    $f[]="pattern = ==0%,==0%,==0%,==0%,>0%,>0%,>0%";
    $f[]="comment = suddenly there is packet loss";
    $f[]="";
    $f[]="+someloss";
    $f[]="type = loss";
    $f[]="# In percent";
    $f[]="pattern = >0%,*12*,>0%,*12*,>0%";
    $f[]="comment = loss 3 times in a row";
    $f[]="";
    $f[]="+startloss";
    $f[]="type = loss";
    $f[]="# In percent";
    $f[]="pattern = ==S,>0%,>0%,>0%";
    $f[]="comment = loss at startup";
    $f[]="";
    $f[]="+rttdetect";
    $f[]="type = rtt";
    $f[]="# In milli seconds";
    $f[]="pattern = <10,<10,<10,<10,<10,<100,>100,>100,>100";
    $f[]="comment = routing messed up again?";
    $f[]="";
    $f[]="+hostdown";
    $f[]="type = loss";
    $f[]="# In percent";
    $f[]="pattern =  ==0%,==0%,==0%, ==U";
    $f[]="comment = no reply";
    $f[]="";
    $f[]="+lossdetect";
    $f[]="type = loss";
    $f[]="# In percent";
    $f[]="pattern = ==0%,==0%,==0%,==0%,>20%,>20%,>20%";
    $f[]="comment = suddenly there is packet loss";
    $f[]="";
    @file_put_contents($fname,@implode("\n",$f));
    echo "$fname [DONE]\n";

}

function basepage(){
    $f[]="<!DOCTYPE html>";
    $f[]="<html>";
    $f[]="<head>";
    $f[]="    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">";
    $f[]="    <META http-equiv=\"Refresh\" content=\"<##step##>\">";
    $f[]="    <title>Latency Service for <##title##></title>";
    $f[]="    <link rel=\"stylesheet\" type=\"text/css\" href=\"css/smokeping-print.css\" media=\"print\">";
    $f[]="    <link rel=\"stylesheet\" type=\"text/css\" href=\"css/smokeping-screen.css\" media=\"screen\">";
    $f[]="<style>";
    $f[]="body{";
    $f[]="font-family: Arial, \"MS UI Gothic\", \"MS P Gothic\", sans-serif;";
    $f[]="}";
    $f[]=".sidebar {";
    $f[]="background: #2f4050;";
    $f[]="position: fixed;";
    $f[]="top: 0;";
    $f[]="left: 0;";
    $f[]="width: 250px;";
    $f[]="height: 100%;";
    $f[]="color: #ccc;";
    $f[]="}";
    $f[]=".sidebar-menu{";
    $f[]="top: 0px;";
    $f[]="}";
    $f[]=".sidebar ul.menu li ul {";
    $f[]="background: #293846;";
    $f[]="padding: 5px;";
    $f[]="}";
    $f[]=".sidebar ul.menu li.menuopen, .sidebar ul.menu li:hover, .sidebar ul.menu li.menuactive {";
    $f[]="border: 0px;";
    $f[]="background: #293846;";
    $f[]="color: #fff;";
    $f[]="}";
    $f[]=".sidebar ul.menu li a.menulinkactive {";
    $f[]="color: #FFF;";
    $f[]="font-size: large;";
    $f[]="border: 0px;";
    $f[]="}";
    $f[]=".sidebar ul.menu li a {";
    $f[]="color: #ccc;";
    $f[]="line-height: 2.0em;";
    $f[]="display: block;";
    $f[]="padding-left: 5px;";
    $f[]="text-decoration: none;";
    $f[]="font-size: 12pt;";
    $f[]="font-weight: 600;";
    $f[]="}";
    $f[]=".main .panel-heading h2 {";
    $f[]="margin: 0;";
    $f[]="padding-left: 10px;";
    $f[]="line-height: 30px;";
    $f[]="font-size: 17px;";
    $f[]="font-weight: 500;";
    $f[]="}";
    $f[]="</style>";
    $f[]="</head>";
    $f[]="<body id=\"body\">";
    $f[]="<div class=\"sidebar\" id=\"sidebar\">";
    $f[]="    <div class=\"sidebar-menu\">";
    $f[]="        <##menu##>";
    $f[]="    </div>";
    $f[]="</div>";
    $f[]="";
    $f[]="<div class=\"navbar\">";
    $f[]="    <div class=\"navbar-menu\"><a id=\"menu-button\" href=\"#\">Toggle Menu</a></div>";
    $f[]="</div>";
    $f[]="";
    $f[]="<div class=\"main\">";
    $f[]="    <h1><##title##></h1>";
    $f[]="    <h2><##remark##></h2>";
    $f[]="";
    $f[]="    <div class=\"overview\">";
    $f[]="        <##overview##>";
    $f[]="    </div>";
    $f[]="";
    $f[]="    <div class=\"details\">";
    $f[]="        <##body##>";
    $f[]="    </div>";
    $f[]="</div>";
    $f[]="";
    $f[]="<script src=\"js/prototype/prototype.js\" type=\"text/javascript\"></script>";
    $f[]="<script src=\"js/scriptaculous/scriptaculous.js?load=builder,effects,dragdrop\" type=\"text/javascript\"></script>";
    $f[]="<script src=\"js/cropper/cropper.js\" type=\"text/javascript\"></script>";
    $f[]="<script src=\"js/smokeping.js\" type=\"text/javascript\"></script>";
    $f[]="";
    $f[]="</body>";
    $f[]="</html>";

    @file_put_contents("/etc/smokeping/basepage.html",@implode("\n",$f));
}

function schedules(){
    $unix=new unix();
    $reboot=false;
    if(!is_file("/etc/cron.d/smokeping")){
        $unix->Popuplate_cron_make("smokeping","* * * * *",basename(__FILE__)." --schedule");
        $reboot=true;
    }

    if($reboot){
        UNIX_RESTART_CRON();
    }

}

function smokeping_config(){
    build_database();probes();Alertes();basepage();General();schedules();
    @chmod(__FILE__,0755);
    $md51=md5_file("/etc/smokeping/config.d/Targets");
    $f[]="*** Targets ***";
    $q=new lib_sqlite("/home/artica/SQLITE/smokeping.db");

    $RULES["FPing"]="Ping {host}";
    $RULES["DNS"]="{dns_query}";
    $RULES["HTTP"]="{http_query}";


    $results=$q->QUERY_SQL("SELECT * FROM smokeping WHERE probe='FPing' AND enabled=1");
    if(count($results)>0) {
        $f[] = "probe = FPing";
        $f[] = "menu = Top";
        $f[] = "title = Network Latency";
        $f[] = "alerts = someloss,hostdown";
        $f[]="\t+ Ping";
        $f[] = "menu = Ping Latency";
        $f[] = "title = Ping Latency";
        foreach ($results as $index => $ligne) {
            $ID=$ligne["ID"];

            $title=$ligne["title"];
            $host=$ligne["host"];
            if(trim($host)==null){continue;}
            if(trim($host)=="no"){continue;}
            $f[] ="\t++section_$ID";
            $f[] = "host = $host";
            $f[] = "title = $title";
            $f[] = "alerts = someloss,hostdown";
        }
    }
    $results=$q->QUERY_SQL("SELECT * FROM smokeping WHERE probe='DNS' AND enabled=1");
    if(count($results)>0) {
        $f[]="";
        $f[]="";
        $f[]="\t+ DNS";
        $f[] = "menu = DNS Latency";
        $f[] = "title   = DNS Latency";
        foreach ($results as $index => $ligne) {
            $ID=$ligne["ID"];
            $title=$ligne["title"];
            $host=$ligne["host"];

            $request=trim($ligne["request"]);
            if(trim($host)==null){continue;}
            if(trim($request)==null){continue;}
            $f[]="\t++section_$ID";
            $f[] = "probe   = DNS";
            $f[] = "title   = $title";
            $f[] = "menu    = $title";
            $f[] = "host    = $host";
            $f[] = "lookup  = $request";
            $f[] = "alerts = someloss,hostdown";
            $f[] ="";
        }
    }
    $results=$q->QUERY_SQL("SELECT * FROM smokeping WHERE probe='HTTP' AND enabled=1");
    if(count($results)>0) {
        $curl=new ccurl("http://none");
        $f[]="";
        $f[]="";
        $f[]="\t+HTTP";
        $f[] = "menu = HTTP Latency";
        $f[] = "title   = HTTP Latency";
        foreach ($results as $index => $ligne) {
            $ID=$ligne["ID"];
            $title=$ligne["title"];
            $host=$ligne["host"];
            $port=0;
             $f[] = "# host <$host>";
            $url_format=array();
            if(trim($host)==null){continue;}
            $urls=parse_url($host);
            foreach ($urls as $skey=>$sval){
                $f[] = "# $skey = <$sval>";
            }

            if(!isset($urls["scheme"])){$urls["scheme"]="http";}
            if(!isset($urls["host"])){continue;}
            if(!isset($urls["port"])){$port=0;}
            $host=$urls["host"];

            if(preg_match("#^(.+?):([0-9]+)#",$host,$re)) {
                $port = intval($re[2]);
                $host = $re[1];
            }

            $url_full[]=$urls["scheme"]."://";
            $url_full[]=$host;
            if($port>0){
                if($port<>80 OR $port<>443) {
                    $url_full[] = ":$port";
                }
            }
            if(isset($urls["path"])) {
                $url_format[] = $urls["path"];
                $url_full[]=$urls["path"];
            }else {
                $url_format[] = "/";
                $url_full[]="/";
            }
            if(isset($urls["query"])){
                $url_format[] = $urls["query"];
                $url_full[]= $urls["query"];
            }

            $final_url=@implode("",$url_format);
            $FULL_URI=@implode("",$url_full);

            if($curl->ArticaProxyServerEnabled=="yes") {
               $host    =   $curl->ArticaProxyServerName;
               $port    =   $curl->ArticaProxyServerPort;
               $final_url = $FULL_URI;
            }

            if($ligne["proxyaddress"]<>null AND strlen($ligne["proxyaddress"])>2 ){
                if(intval($ligne["proxyport"])>0){
                    $host    =   $ligne["proxyaddress"];
                    $port    =   $ligne["proxyport"];
                    $final_url = $FULL_URI;
                }
            }


            $f[]="\t++section_$ID";
            if($urls["scheme"]=="http"){
                if($port==0){$port=80;}
                $f[] = "probe   = EchoPingHttp";
            }else{
                $f[] = "probe   = EchoPingHttps";
                if($port==0){$port=443;}
            }
            $f[] = "title   = $title";
            $f[] = "menu    = $title";
            $f[] = "host    = $host";
            $f[] = "port    = $port";
            $f[] = "url     = $final_url";
            //$f[] = "agent   = User-Agent: $curl->UserAgent";
            $f[] = "ignore_cache = yes";
            $f[] = "ipversion = 4";
            $f[] = "alerts = someloss,hostdown";
            $f[] ="";
            $f[] ="";
        }

    }



    $f[]="";
    @file_put_contents("/etc/smokeping/config.d/Targets",@implode("\n",$f));
    $md52=md5_file("/etc/smokeping/config.d/Targets");
    if($md51<>$md52){
        shell_exec("/etc/init.d/smokeping restart");
    }

    echo "/etc/smokeping/config.d/Targets [DONE]\n";
}

function uninstall(){
    build_progress(10,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSmokePing",0);
    $INITD_PATH="/etc/init.d/smokeping";
    $unix=new unix();
    $unix->remove_service($INITD_PATH);

    $INITD_PATH="/etc/init.d/fcgiwrap";
    build_progress(20,"{uninstalling}");
    $unix->remove_service($INITD_PATH);

    build_progress(30,"{uninstalling}");
    if(is_file("/etc/monit/conf.d/APP_FCGIWRAP.monitrc")){
        @unlink("/etc/monit/conf.d/APP_FCGIWRAP.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    build_progress(40,"{uninstalling}");

    if(is_file("/etc/monit/conf.d/APP_SMOKEPING.monitrc")){
        @unlink("/etc/monit/conf.d/APP_SMOKEPING.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    build_progress(50,"{uninstalling}");
    if(is_file("/etc/cron.d/smokeping")){
        @unlink("/etc/cron.d/smokeping");
        UNIX_RESTART_CRON();
    }

    $dirs[]="/var/lib/smokeping";
    $dirs[]="/var/cache/smokeping";
    build_progress(60,"{uninstalling}");
    foreach ($dirs as $directory){
        if(!is_dir($directory)){continue;}
        $rm=$unix->find_program("rm");
        shell_exec("$rm -rf $dirs/*");
    }

    $files[]="/var/log/smokeping.log";
    $files[]="/home/artica/SQLITE/smokeping.db";

    build_progress(70,"{uninstalling}");
    foreach ($files as $fname){
        if(!is_file($fname)){continue;}
        @unlink($fname);
    }

    build_progress(80,"{uninstalling}");
    $q=new postgres_sql();
    $q->QUERY_SQL("DROP TABLE smokeping");

    return build_progress(100,"{uninstalling} {done}");
}
function install_rsyslogd():bool{
    $fname="/etc/rsyslog.d/smokeping.conf";
    $md551=md5_file($fname);
    $h[]="if  (\$programname =='smokeping') then {";
    $h[]="\t-/var/log/smokeping.log";
    $h[]="\t& stop";
    $h[]="}";
    $h[]="";
    @file_put_contents($fname,@implode("\n", $h));
    echo "Starting......: ".date("H:i:s")." [INIT]: $fname created\n";
    $md552=md5_file($fname);
    if($md551==$md552){return true;}
    $unix=new unix();$unix->RESTART_SYSLOG(true);
    return true;
}

function install_fcgiwrap_monit(){
    $INITD_PATH="/etc/init.d/fcgiwrap";
    $f[]="check process APP_FCGIWRAP with pidfile /var/run/fcgiwrap.pid";
    $f[]="\tstart program = \"$INITD_PATH start\"";
    $f[]="\tstop program = \"$INITD_PATH stop\"";
    $f[]="\trestart program = \"$INITD_PATH restart\"";
    $f[]="if failed unixsocket /var/run/fcgiwrap.socket then restart";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_FCGIWRAP.monitrc",@implode("\n",$f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}
function checkdirectories(){
    $unix=new unix();
    $chown=$unix->find_program("chown");
    $dirs[]="/var/lib/smokeping";
    $dirs[]="/var/cache/smokeping";
    $dirs[]="/usr/share/smokeping";
    $dirs[]="/var/run/smokeping";
    $dirs[]="/var/cache/smokeping/threats";
    if(!is_dir("/var/cache/smokeping/threats")){
        @mkdir("/var/cache/smokeping/threats",0755,true);
    }

    foreach ($dirs as $directory){
        if(!is_dir($directory)){continue;}
        echo "Check permissions on directory \"$directory\"\n";
        shell_exec("$chown -R www-data:www-data $directory");
        @chmod($directory,0755);
    }

}

function install_fcgiwrap(){
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          fcgiwrap";
    $f[]="# Required-Start:    \$remote_fs";
    $f[]="# Required-Stop:     \$remote_fs";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     2 3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: FastCGI wrapper";
    $f[]="# Description:       Simple server for running CGI applications over FastCGI";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
    $f[]="";
    $f[]="SPAWN_FCGI=\"/usr/bin/spawn-fcgi\"";
    $f[]="DAEMON=\"/usr/sbin/fcgiwrap\"";
    $f[]="NAME=\"fcgiwrap\"";
    $f[]="DESC=\"FastCGI wrapper\"";
    $f[]="";
    $f[]="PIDFILE=\"/var/run/\$NAME.pid\"";
    $f[]="";
    $f[]="test -x \$SPAWN_FCGI || exit 0";
    $f[]="test -x \$DAEMON || exit 0";
    $f[]="";
    $f[]="# FCGI_APP Variables";
    $f[]="FCGI_CHILDREN=\"1\"";
    $f[]="FCGI_SOCKET=\"/var/run/\$NAME.socket\"";
    $f[]="FCGI_USER=\"www-data\"";
    $f[]="FCGI_GROUP=\"www-data\"";
    $f[]="# Socket owner/group (will default to FCGI_USER/FCGI_GROUP if not defined)";
    $f[]="FCGI_SOCKET_OWNER=\"www-data\"";
    $f[]="FCGI_SOCKET_GROUP=\"www-data\"";
    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="# Default options, these can be overriden by the information";
    $f[]="# at /etc/default/\$NAME";
    $f[]="DAEMON_OPTS=\"-f\"        # By default we redirect STDERR output from executed";
    $f[]="                        # CGI through FastCGI, to disable this behaviour set";
    $f[]="                        # DAEMON_OPTS to an empty value in the default's file";
    $f[]="";
    $f[]="ENV_VARS=\"PATH='\$PATH'\" # We reset the environ for spawn-fcgi, but we use the";
    $f[]="                        # contents of this variable as a prefix when calling it";
    $f[]="                        # to export some variables (currently just the PATH)";
    $f[]="DIETIME=10              # Time to wait for the server to die, in seconds";
    $f[]="                        # If this value is set too low you might not";
    $f[]="                        # let some servers to die gracefully and";
    $f[]="                        # 'restart' will not work";
    $f[]="QDIETIME=0.5            # The same as DIETIME, but a lot shorter for the";
    $f[]="                        # stop case.";
    $f[]="";
    $f[]="#STARTTIME=2            # Time to wait for the server to start, in seconds";
    $f[]="                        # If this value is set each time the server is";
    $f[]="                        # started (on start or restart) the script will";
    $f[]="                        # stall to try to determine if it is running";
    $f[]="                        # If it is not set and the server takes time";
    $f[]="                        # to setup a pid file the log message might";
    $f[]="                        # be a false positive (says it did not start";
    $f[]="                        # when it actually did)";
    $f[]="";
    $f[]="# Include defaults if available";
    $f[]="if [ -f /etc/default/\$NAME ] ; then";
    $f[]="    . /etc/default/\$NAME";
    $f[]="fi";
    $f[]="";
    $f[]="set -e";
    $f[]="";
    $f[]="running_pid() {";
    $f[]="# Check if a given process pid's cmdline matches a given name";
    $f[]="    pid=\$1";
    $f[]="    name=\$2";
    $f[]="    [ -z \"\$pid\" ] && return 1";
    $f[]="    [ ! -d /proc/\$pid ] &&  return 1";
    $f[]="    cmd=\"\$(cat /proc/\$pid/cmdline | tr \"\000\" \"\n\"|head -n 1 |cut -d : -f 1)\"";
    $f[]="    # Is this the expected server";
    $f[]="    [ \"\$cmd\" != \"\$name\" ] && return 1";
    $f[]="    return 0";
    $f[]="}";
    $f[]="";
    $f[]="running() {";
    $f[]="# Check if the process is running looking at /proc";
    $f[]="# (works for all users)";
    $f[]="    # No pidfile, probably no daemon present";
    $f[]="    [ ! -f \"\$PIDFILE\" ] && return 1";
    $f[]="    PIDS=\"\$(cat \"\$PIDFILE\")\"";
    $f[]="    for pid in \$PIDS; do";
    $f[]="      if [ -n \"\$pid\" ]; then";
    $f[]="        running_pid \$pid \$DAEMON && return 0 || true";
    $f[]="      fi";
    $f[]="    done";
    $f[]="    return 1";
    $f[]="}";
    $f[]="";
    $f[]="start_server() {";
    $f[]="    ARGS=\"-P \$PIDFILE\"";
    $f[]="    # Adjust NUMBER of processes";
    $f[]="    if [ -n \"\$FCGI_CHILDREN\" ]; then";
    $f[]="       ARGS=\"\$ARGS -F '\$FCGI_CHILDREN'\"";
    $f[]="    fi";
    $f[]="    # Adjust SOCKET or PORT and ADDR";
    $f[]="    if [ -n \"\$FCGI_SOCKET\" ]; then";
    $f[]="      ARGS=\"\$ARGS -s '\$FCGI_SOCKET'\"";
    $f[]="    elif [ -n \"\$FCGI_PORT\" ]; then";
    $f[]="      if [ -n \"\$FCGI_ADDR\" ]; then";
    $f[]="        ARGS=\"\$ARGS -a '\$FCGI_ADDR'\"";
    $f[]="      fi";
    $f[]="      ARGS=\"\$ARGS -p '\$FCGI_PORT'\"";
    $f[]="    fi";
    $f[]="    # Adjust user";
    $f[]="    if [ -n \"\$FCGI_USER\" ]; then";
    $f[]="      ARGS=\"\$ARGS -u '\$FCGI_USER'\"";
    $f[]="      if [ -n \"\$FCGI_SOCKET\" ]; then";
    $f[]="        if [ -n \"\$FCGI_SOCKET_OWNER\" ]; then";
    $f[]="          ARGS=\"\$ARGS -U '\$FCGI_SOCKET_OWNER'\"";
    $f[]="        else";
    $f[]="          ARGS=\"\$ARGS -U '\$FCGI_USER'\"";
    $f[]="        fi";
    $f[]="      fi";
    $f[]="    fi";
    $f[]="    # Adjust group";
    $f[]="    if [ -n \"\$FCGI_GROUP\" ]; then";
    $f[]="      ARGS=\"\$ARGS -g '\$FCGI_GROUP'\"";
    $f[]="      if [ -n \"\$FCGI_SOCKET\" ]; then";
    $f[]="        if [ -n \"\$FCGI_SOCKET_GROUP\" ]; then";
    $f[]="          ARGS=\"\$ARGS -G '\$FCGI_SOCKET_GROUP'\"";
    $f[]="        else";
    $f[]="          ARGS=\"\$ARGS -G '\$FCGI_GROUP'\"";
    $f[]="        fi";
    $f[]="      fi";
    $f[]="    fi";
    $f[]="    eval \$(echo env -i \$ENV_VARS \$SPAWN_FCGI \$ARGS -- \$DAEMON \$DAEMON_OPTS) > /dev/null";
    $f[]="    errcode=\"\$?\"";
    $f[]="    return \$errcode";
    $f[]="}";
    $f[]="";
    $f[]="stop_server() {";
    $f[]="    # Force the process to die killing it manually";
    $f[]="    [ ! -e \"\$PIDFILE\" ] && return";
    $f[]="    PIDS=\"\$(cat \"\$PIDFILE\")\"";
    $f[]="    for pid in \$PIDS; do";
    $f[]="      if running_pid \$pid \$DAEMON; then";
    $f[]="        kill -15 \$pid";
    $f[]="        # Is it really dead?";
    $f[]="        sleep \"\$QDIETIME\"s";
    $f[]="        if running_pid \$pid \$DAEMON; then";
    $f[]="          kill -9 \$pid";
    $f[]="          sleep \"\$QDIETIME\"s";
    $f[]="          if running_pid \$pid \$DAEMON; then";
    $f[]="              echo \"Cannot kill \$NAME (pid=\$pid)!\"";
    $f[]="              exit 1";
    $f[]="          fi";
    $f[]="        fi";
    $f[]="      fi";
    $f[]="    done";
    $f[]="    rm -f \"\$PIDFILE\"";
    $f[]="    if [ -n \"\$FCGI_SOCKET\" ]; then";
    $f[]="      rm -f \"\$FCGI_SOCKET\"";
    $f[]="    fi";
    $f[]="}";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";
    $f[]="        log_daemon_msg \"Starting \$DESC\" \"\$NAME\"";
    $f[]="        # Check if it's running first";
    $f[]="        if running ;  then";
    $f[]="            log_progress_msg \"apparently already running\"";
    $f[]="            log_end_msg 0";
    $f[]="            exit 0";
    $f[]="        fi";
    $f[]="        if start_server ; then";
    $f[]="            # NOTE: Some servers might die some time after they start,";
    $f[]="            # this code will detect this issue if STARTTIME is set";
    $f[]="            # to a reasonable value";
    $f[]="            [ -n \"\$STARTTIME\" ] && sleep \$STARTTIME # Wait some time ";
    $f[]="            if  running ;  then";
    $f[]="                # It's ok, the server started and is running";
    $f[]="                log_end_msg 0";
    $f[]="            else";
    $f[]="                # It is not running after we did start";
    $f[]="                log_end_msg 1";
    $f[]="            fi";
    $f[]="        else";
    $f[]="            # Either we could not start it";
    $f[]="            log_end_msg 1";
    $f[]="        fi";
    $f[]="        ;;";
    $f[]="  stop|force-stop)";
    $f[]="        log_daemon_msg \"Stopping \$DESC\" \"\$NAME\"";
    $f[]="        if running ; then";
    $f[]="            # Only stop the server if we see it running";
    $f[]="            errcode=0";
    $f[]="            stop_server || errcode=\$?";
    $f[]="            log_end_msg \$errcode";
    $f[]="        else";
    $f[]="            # If it's not running don't do anything";
    $f[]="            log_progress_msg \"apparently not running\"";
    $f[]="            log_end_msg 0";
    $f[]="            exit 0";
    $f[]="        fi";
    $f[]="        ;;";
    $f[]="  restart|force-reload)";
    $f[]="        log_daemon_msg \"Restarting \$DESC\" \"\$NAME\"";
    $f[]="        errcode=0";
    $f[]="        stop_server || errcode=\$?";
    $f[]="        # Wait some sensible amount, some server need this";
    $f[]="        [ -n \"\$DIETIME\" ] && sleep \$DIETIME";
    $f[]="        start_server || errcode=\$?";
    $f[]="        [ -n \"\$STARTTIME\" ] && sleep \$STARTTIME";
    $f[]="        running || errcode=\$?";
    $f[]="        log_end_msg \$errcode";
    $f[]="        ;;";
    $f[]="  status)";
    $f[]="";
    $f[]="        log_daemon_msg \"Checking status of \$DESC\" \"\$NAME\"";
    $f[]="        if running ;  then";
    $f[]="            log_progress_msg \"running\"";
    $f[]="            log_end_msg 0";
    $f[]="        else";
    $f[]="            log_progress_msg \"apparently not running\"";
    $f[]="            log_end_msg 1";
    $f[]="            exit 1";
    $f[]="        fi";
    $f[]="        ;;";
    $f[]="  # Use this if the daemon cannot reload";
    $f[]="  reload)";
    $f[]="        log_warning_msg \"Reloading \$NAME daemon: not implemented, as the daemon\"";
    $f[]="        log_warning_msg \"cannot re-read the config file (use restart).\"";
    $f[]="        ;;";
    $f[]="  *)";
    $f[]="        N=/etc/init.d/\$NAME";
    $f[]="        echo \"Usage: \$N {start|stop|force-stop|restart|force-reload|status}\" >&2";
    $f[]="        exit 1";
    $f[]="        ;;";
    $f[]="esac";
    $f[]="";
    $f[]="exit 0";
    $f[]="";

    $INITD_PATH="/etc/init.d/fcgiwrap";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);
    $updaterc   = "/usr/sbin/update-rc.d";
    $chconf     = "/sbin/chkconfig";
    if(is_file($updaterc)){
        shell_exec("$updaterc -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file($chconf)){
        shell_exec("$chconf --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("$chconf --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

    return true;

}
function install_service_monit(){
    $INITD_PATH="/etc/init.d/smokeping";
    $f[]="check process APP_SMOKEPING with pidfile /var/run/smokeping/smokeping.pid";
    $f[]="\tstart program = \"$INITD_PATH start\"";
    $f[]="\tstop program = \"$INITD_PATH stop\"";
    $f[]="\trestart program = \"$INITD_PATH restart\"";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_SMOKEPING.monitrc",@implode("\n",$f));
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}

function install_service(){
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        $f[]="### BEGIN INIT INFO";
        $f[]="# Provides:          smokeping";
        $f[]="# Required-Start:    \$syslog \$network \$remote_fs";
        $f[]="# Should-Start:      sshd apache";
        $f[]="# Required-Stop:     \$syslog \$network \$remote_fs";
        $f[]="# Default-Start:     2 3 4 5";
        $f[]="# Default-Stop:      0 1 6";
        $f[]="# Short-Description: Start or stop the smokeping latency logging system daemon";
        $f[]="# Description:       SmokePing is a latency logging and graphing system";
        $f[]="#                    that consists of a daemon process which organizes";
        $f[]="#                    the latency measurements and a CGI which presents";
        $f[]="#                    the graphs. This script is used to start or stop";
        $f[]="#                    the daemon.";
        $f[]="### END INIT INFO";
        $f[]="#";
        $f[]="";
        $f[]="set -e";
        $f[]="";
        $f[]="# Source LSB init functions";
        $f[]=". /lib/lsb/init-functions";
        $f[]="";
        $f[]="DAEMON=/usr/sbin/smokeping";
        $f[]="NAME=smokeping";
        $f[]="DESC=\"latency logger daemon\"";
        $f[]="CONFIG=/etc/smokeping/config";
        $f[]="PIDFILE=/var/run/smokeping/\$NAME.pid";
        $f[]="DAEMON_USER=\"www-data\"";
        $f[]="DEFAULTS=/etc/default/smokeping";
        $f[]="MODE=master";
        $f[]="DAEMON_ARGS=\"--config=\$CONFIG\"";
        $f[]="";
        $f[]="# LC_ALL prevents resetting LC_NUMERIC which in turn interferes";
        $f[]="# with Smokeping internal regexps matching floating point numbers";
        $f[]="unset LC_ALL";
        $f[]="";
        $f[]="# Check whether the binary is still present:";
        $f[]="test -f \"\$DAEMON\" || exit 0";
        $f[]="";
        $f[]="# source defaults for master vs. slave mode";
        $f[]="if [ -f \"\$DEFAULTS\" ]";
        $f[]="then";
        $f[]="    . \"\$DEFAULTS\"";
        $f[]="fi";
        $f[]="";
        $f[]="check_slave() {";
        $f[]="    if [ \"\$MODE\" != \"slave\" ]";
        $f[]="    then";
        $f[]="        return";
        $f[]="    fi";
        $f[]="    if [ -z \"\$SHARED_SECRET\" ]";
        $f[]="    then";
        $f[]="        log_progress_msg \"(missing \$SHARED_SECRET setting)\"";
        $f[]="        log_end_msg 6 # program is not configured";
        $f[]="        exit 6";
        $f[]="    fi";
        $f[]="    if [ ! -r \"\$SHARED_SECRET\" ]";
        $f[]="    then";
        $f[]="        log_progress_msg \"(invalid \$SHARED_SECRET setting)\"";
        $f[]="        log_end_msg 2 # invalid or excess argument(s)";
        $f[]="        exit 2";
        $f[]="    fi";
        $f[]="    if [ -z \"\$MASTER_URL\" ]";
        $f[]="    then";
        $f[]="        log_progress_msg \"(missing \$MASTER_URL setting)\"";
        $f[]="        log_end_msg 6 # program is not configured";
        $f[]="        exit 6";
        $f[]="    fi";
        $f[]="    DAEMON_ARGS=\"\$DAEMON_ARGS --master-url \$MASTER_URL --shared-secret \$SHARED_SECRET\"";
        $f[]="    if [ -n \"\$SLAVE_NAME\" ]";
        $f[]="    then";
        $f[]="        DAEMON_ARGS=\"\$DAEMON_ARGS --slave-name \$SLAVE_NAME\"";
        $f[]="    fi";
        $f[]="    DAEMON_ARGS=\"\$DAEMON_ARGS --cache-dir /var/lib/smokeping\"";
        $f[]="    DAEMON_ARGS=\"\$DAEMON_ARGS --pid-dir /var/run/smokeping\"";
        $f[]="}";
        $f[]="";
        $f[]="check_config () {";
        $f[]="    echo \"Checking smokeping configuration file syntax...\"";
        $f[]="    # Check whether the configuration file is available";
        $f[]="    if [ ! -r \"\$CONFIG\" ] && [ \"\$MODE\" = \"master\" ]";
        $f[]="    then";
        $f[]="        log_progress_msg \"(\$CONFIG does not exist)\"";
        $f[]="        log_end_msg 6 # program is not configured";
        $f[]="        exit 6";
        $f[]="    fi";
        $f[]="    if [ ! -d /var/run/smokeping ]; then";
        $f[]="        mkdir /var/run/smokeping";
        $f[]="        chown \${DAEMON_USER}.root /var/run/smokeping";
        $f[]="        chmod 0755 /var/run/smokeping";
        $f[]="    fi";
        $f[]="    \${DAEMON} --config=\${CONFIG} --check || exit 6";
        $f[]="}";
        $f[]="";
        $f[]="case \"\$1\" in";
        $f[]="    start)";
        $f[]="        check_config";
        $f[]="        log_daemon_msg \"Starting \$DESC\" \$NAME";
        $f[]="        check_slave";
        $f[]="        set +e";
        $f[]="        pidofproc -p \"\$PIDFILE\" \"\$DAEMON\" > /dev/null";
        $f[]="        STATUS=\$?";
        $f[]="        set -e";
        $f[]="        if [ \"\$STATUS\" = 0 ]";
        $f[]="        then";
        $f[]="            log_progress_msg \"already running\"";
        $f[]="            log_end_msg \$STATUS";
        $f[]="            exit \$STATUS";
        $f[]="        fi";
        $f[]="";
        $f[]="        set +e";
        $f[]="        start-stop-daemon --start --quiet --exec \$DAEMON --oknodo --chuid \$DAEMON_USER --pidfile \$PIDFILE -- \$DAEMON_ARGS | logger -p daemon.notice -t \$NAME";
        $f[]="        STATUS=\$?";
        $f[]="        set -e";
        $f[]="";
        $f[]="        log_end_msg \$STATUS";
        $f[]="        exit \$STATUS";
        $f[]="        ;;";
        $f[]="";
        $f[]="";
        $f[]="    stop)";
        $f[]="        log_daemon_msg \"Shutting down \$DESC\" \$NAME";
        $f[]="        $php ". __FILE__." --stop";
        $f[]="        ;;";
        $f[]="";
        $f[]="";
        $f[]="    restart)";
        $f[]="        # Restart service (if running) or start service";
        $f[]="        \$0 stop";
        $f[]="        \$0 start";
        $f[]="        ;;";
        $f[]="";
        $f[]="";
        $f[]="    reload|force-reload)";
        $f[]="        check_config";
        $f[]="        log_action_begin_msg \"Reloading \$DESC configuration\"";
        $f[]="        set +e";
        $f[]="        \$DAEMON --reload \$DAEMON_ARGS | logger -p daemon.notice -t smokeping";
        $f[]="        STATUS=\$?";
        $f[]="        set -e";
        $f[]="";
        $f[]="        if [ \"\$STATUS\" = 0 ]";
        $f[]="        then";
        $f[]="            log_action_end_msg 0 \"If the CGI has problems reloading, see README.Debian.\"";
        $f[]="        else";
        $f[]="            log_action_end_msg \$STATUS";
        $f[]="        fi";
        $f[]="        exit \$STATUS";
        $f[]="        ;;";
        $f[]="";
        $f[]="    check)";
        $f[]="	check_config";
        $f[]="	;;";
        $f[]="";
        $f[]="    status)";
        $f[]="        log_daemon_msg \"Checking \$DESC status\" \$NAME";
        $f[]="        # Use pidofproc to check the status of the service,";
        $f[]="        # pidofproc returns the exit status code of 0 when it the process is";
        $f[]="        # running.";
        $f[]="";
        $f[]="        # LSB defined exit status codes for status:";
        $f[]="        # 0    program is running or service is OK";
        $f[]="        # 1    program is dead and /var/run pid file exists";
        $f[]="        # 2    program is dead and /var/lock lock file exists";
        $f[]="        # 3    program is not running";
        $f[]="        # 4    program or service status is unknown";
        $f[]="        # 5-199    reserved (5-99 LSB, 100-149 distribution, 150-199 applications)";
        $f[]="";
        $f[]="        set +e";
        $f[]="        pidofproc -p \"\$PIDFILE\" \"\$DAEMON\" > /dev/null";
        $f[]="        STATUS=\$?";
        $f[]="        log_progress_msg \"(status \$STATUS)\"";
        $f[]="        log_end_msg 0";
        $f[]="        set -e";
        $f[]="        exit \$STATUS";
        $f[]="        ;;";
        $f[]="";
        $f[]="";
        $f[]="    *)";
        $f[]="        echo \"Usage: \$0 {start|stop|status|restart|force-reload|reload}\"";
        $f[]="        exit 1";
        $f[]="        ;;";
        $f[]="esac";
        $f[]="";

        $INITD_PATH="/etc/init.d/smokeping";
        @unlink($INITD_PATH);
        @file_put_contents($INITD_PATH, @implode("\n", $f));
        @chmod($INITD_PATH,0755);
        $updaterc   = "/usr/sbin/update-rc.d";
        $chconf     = "/sbin/chkconfig";
        if(is_file($updaterc)){
            shell_exec("$updaterc -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
        }

        if(is_file($chconf)){
            shell_exec("$chconf --add " .basename($INITD_PATH)." >/dev/null 2>&1");
            shell_exec("$chconf --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
        }

        return true;

}





?>