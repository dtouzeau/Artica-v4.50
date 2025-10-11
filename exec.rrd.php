#!/usr/bin/php -q
<?php
$GLOBALS["BASEDIR"]=basename(__FILE__)."/ressources/interface-cache";
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.monit.xml.inc");

$array_load=sys_getloadavg();
$internal_load=$array_load[0];

if(isset($argv[1])) {

    if ($argv[1] == "--nginx") {
        nginx();
        exit;
    }
    if ($argv[1] == "--load") {
        scan_load();
        exit;
    }
    if ($argv[1] == "--monit") {
        exit;
    }
    if($argv[1]=="--proxy-graphs"){
        CreateGraphSquid2($argv[2]);
        exit;
    }

    if($argv[1]=="--rrd-only"){exit;}
    if($argv[1]=="--requests"){
        if(system_is_overloaded()){exit;}
        scan_squid_requests(true);
        exit;
    }
    if($argv[1]=="--php"){
        if(system_is_overloaded()){exit;}
        scan_wordpress_phpfpm();
        exit;
    }
    if($argv[1]=="--vts"){
        if(system_is_overloaded()){exit;}
        nginx_vts();
        exit;
    }
    if($argv[1]=="--nginx-requests"){
        CreateGraphsRequests();
        exit;
    }



    if($argv[1]=="--pictures-only"){
        if(!$GLOBALS["FORCE"]) {
            if (system_is_overloaded()) {
                exit;
            }
        }
        rrd_pictures(true);
        exit;
    }
}
scan_squid();

function scan_mysql():bool{
    $unix=new unix();
    $EnableMySQL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMySQL"));
    if($EnableMySQL==0){
        if($GLOBALS["VERBOSE"]){echo "MYSQL Aborted not enabled...\n";}
        return true;
    }
    $mysqladmin=$unix->mysqladmin("status");
    if($GLOBALS["VERBOSE"]){echo "$mysqladmin\n";}
    exec("$mysqladmin status 2>&1",$rr);
    $rrdtool=$unix->find_program("rrdtool");
    if(!is_file($rrdtool)){
        return false;
    }
    if(!preg_match("#Uptime:\s+([0-9]+)\s+Threads:\s+([0-9]+)\s+Questions:\s+([0-9]+)\s+Slow queries:\s+([0-9]+)\s+Opens:\s+([0-9]+).*?\s+Open tables:\s+([0-9]+)\s+Queries per second avg:\s+([0-9\.]+)#",trim(@implode("",$rr)),$re)){
        if($GLOBALS["VERBOSE"]){echo "MYSQL NOT FOUND [".trim(@implode("",$rr))."]!\n";}
        return false;}
    $Uptime=$re[1];
    $Threads=$re[2];
    $Questions=$re[3];
    $SlowQueries=$re[4];
    $Opens=$re[5];
    $OpenTables=$re[6];
    $avg=$re[7];
    $oldQuestions=0;
    $roundedAvg = floor(abs($avg)) * ($avg >= 0 ? 1 : -1);
    if(is_file("/etc/artica-postfix/scanmysql.Questions.int")) {
        $oldQuestions = intval(@file_get_contents("/etc/artica-postfix/scanmysql.Questions.int"));
    }
    if($oldQuestions>$Questions){$oldQuestions=$Questions;}
    $DiffQest=$Questions-$oldQuestions;
    $base=ArticaRRDBase();
    $rrd_base="$base/mysql.rrd";
    if(!is_dir($base)){@mkdir($base,0755);}

    if(!is_file("$base/mysql.rrd")) {
        echo "-------> Creating $rrd_base\n";
        shell_exec("$rrdtool create $rrd_base -s 300"
            . " DS:requests:GAUGE:600:U:U"
            . " DS:opens:GAUGE:600:U:U"
            . " DS:opentables:GAUGE:600:U:U"
            . " DS:avgrq:GAUGE:600:U:U"
            . " DS:slow:GAUGE:600:U:U"
            . " RRA:AVERAGE:0.5:1:576"
            . " RRA:AVERAGE:0.5:6:672"
            . " RRA:AVERAGE:0.5:24:732"
            . " RRA:AVERAGE:0.5:144:1460");
    }

    shell_exec("$rrdtool updatev $rrd_base -t requests N:$DiffQest");
    shell_exec("$rrdtool updatev $rrd_base -t opens N:$Opens");
    shell_exec("$rrdtool updatev $rrd_base -t opentables N:$OpenTables");
    shell_exec("$rrdtool updatev $rrd_base -t avgrq N:$roundedAvg");
    shell_exec("$rrdtool updatev $rrd_base -t slow N:$SlowQueries");
    return true;
}
function ArticaRRDBase():string{
    return "/home/artica/rrd";
}


function nginx($aspid=false){
    $unix=new unix();
    $TimePID = "/etc/artica-postfix/pids/exec.rrd.php.nginx.pid";
    if($aspid) {
        $pid = $unix->get_pid_from_file($TimePID);
        if ($unix->process_exists($pid)) {
            $unix->ToSyslog("nginx(): Already Artica process running pid $pid", false, "rrd");
            return false;
        }
    }
    @file_put_contents($TimePID,getmygid());
    $TimeExec = "/etc/artica-postfix/pids/exec.rrd.php.nginx.time";
    $Stime=$unix->file_time_min($TimeExec);
    if($Stime==0){
        rrd_syslog("nginx: Current {$Stime}mn, need 2 minutes at least, aborting");
        return false;
    }
    @unlink($TimeExec);
    @file_put_contents($TimeExec,time());

    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==0){
        if(is_file("/etc/cron.d/nginx-rrd")){
            @unlink("/etc/cron.d/nginx-rrd");
            shell_exec("/etc/init.d/cron reload");
        }
        rrd_syslog("nginx: EnableNginx = 0");
        return true;
    }


    $unix->Popuplate_cron_delete("nginx-rrd");

    nginx_caches();
    include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
    scan_wordpress_phpfpm();
    return true;

}

function nginxvtsIS(){

    $f=explode("\n",@file_get_contents("/etc/nginx/conf.d/nginx_status.conf"));
    foreach ($f as $line){
        if(preg_match("#internal-artica-status#",$line)){return true;}
    }
    return false;
}



function cron_tasks():bool{
    $unix=new unix();
    $rrdtool=$unix->find_program("rrdtool");
    exec("/usr/bin/pgrep -l -f \"/(cron|CRON)\" 2>&1",$results);
    $pids=array();
    foreach ($results as $line){
        $line=trim($line);
        if(!preg_match("#^([0-9]+)\s+.*?cron#i",$line,$re)){
            rrd_syslog("Cron: pgrep: $line [NO MATCHES]");
            continue;
        }
        $pids[$re[1]]=true;

    }
    $base=ArticaRRDBase();
    if(!is_dir($base)){@mkdir($base,0755);}
    if(!is_file("$base/cron.rrd")) {
        echo "-------> Creating $base/cron.rrd\n";
        shell_exec("$rrdtool create $base/cron.rrd -s 300"
            . " DS:tasks:GAUGE:600:U:U"
            . " RRA:AVERAGE:0.5:1:576"
            . " RRA:AVERAGE:0.5:6:672"
            . " RRA:AVERAGE:0.5:24:732"
            . " RRA:AVERAGE:0.5:144:1460");
    }
    $Total=count($pids);
    rrd_syslog("Cron: Tasks: $Total");
    shell_exec("$rrdtool update $base/cron.rrd -t tasks N:$Total");
    return true;
}



function rrd_pictures(){
    $unix       = new unix();
    if(!$unix->IsProductionTime()){return false;}
    $TimePID    = "/etc/artica-postfix/pids/exec.rrd.php.rrd_pictures.pid";
    $pid        = $unix->get_pid_from_file($TimePID);

    if ($unix->process_exists($pid)) {
        $unix->ToSyslog("rrd_pictures(): Already Artica process running pid $pid", false, "rrd");
        return false;
    }
    @unlink($TimePID);
    @file_put_contents($TimePID,getmypid());


    $TimeExec = "/etc/artica-postfix/pids/exec.rrd.php.rrd_pictures.time";
    $Stime=$unix->file_time_min($TimeExec);
    if(!$GLOBALS["FORCE"]) {
        if ($Stime < 10) {
            return false;
        }
    }
    @unlink($TimeExec);
    @file_put_contents($TimeExec,time());


    $periods[]="hourly";
    $periods[]="yesterday";
    $periods[]="day";
    $periods[]="week";
    $periods[]="month";
    $periods[]="year";

    foreach ($periods as $period) {
        CreateGraphSquid($period);
        CreateGraphSquid($period,true);
        CreateGraphNginx($period);
        CreateGraphNginxHosts($period);
        CreateGraphNginxHosts($period,true);
        CreateGraphNginx($period,true);
        CreateGraphCron($period);
        CreateGraphCron($period,true);
    }
    return true;
}

function scan_squid_requests(){
    $BaseWorkDir="/home/artica/squid/squidclient/stats";
    if(!is_dir($BaseWorkDir)){@mkdir($BaseWorkDir,0755,true);}
    $active_requests_files=array();
    $negotiateauthenticator=array();
    if (!$handle = opendir($BaseWorkDir)) {return false;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$BaseWorkDir/$filename";
        if(preg_match("#^active_requests\.([0-9]+)$#",$filename,$re)){
            $active_requests_files[$re[1]]=$targetFile;
            continue;
        }
        if(preg_match("#^negotiateauthenticator\.([0-9]+)$#",$filename,$re)){
            $negotiateauthenticator[$re[1]]=$targetFile;
        }
    }

    ksort($active_requests_files,SORT_NUMERIC);
    ksort($negotiateauthenticator,SORT_NUMERIC);

    foreach ($active_requests_files as $time=>$file){
        $array=scan_squid_requests_parsefile($time,$file);
        if(!isset($array["TIME"])){
            @unlink($file);
            continue;
        }
        syslog_squid("Parsing $file");
        scan_squid_request_torrd($array);
        @unlink($file);
    }

    foreach ($negotiateauthenticator as $time=>$file){
        $array=scan_squid_negociator_parsefile($time,$file);
        if(!isset($array["TIME"])){
            @unlink($file);
            continue;
        }
        syslog_squid("Parsing $file");
        scan_squid_negotiateauthenticator_torrd($array);
        @unlink($file);
    }


return true;
}

function syslog_squid($text){
    if(function_exists("syslog")){
        openlog("SquidClient", LOG_PID , LOG_SYSLOG);
        syslog(LOG_INFO, "RRD: $text");
        closelog();
    }

}

function scan_squid_negotiateauthenticator_torrd($data=array()):bool{
    $unix=new unix();
    $base=ArticaRRDBase();
    $filebase="$base/proxy_nego.rrd";
    if(!isset($GLOBALS["RRDTOOL"])) {
        $GLOBALS["RRDTOOL"] = $unix->find_program("rrdtool");
    }
    $time=$data["TIME"];
    $CNXS=$data["CNXS"];
    $proc=$data["PR"];
    if(!is_file($filebase)){
        $dbtime=$time-60;
        $cmd="{$GLOBALS["RRDTOOL"]} create $filebase -s 60 --start $dbtime"
            . " DS:cons:DERIVE:600:0:100000"
            . " DS:procs:DERIVE:600:0:100000"
            . " RRA:AVERAGE:0.5:1:576"
            . " RRA:AVERAGE:0.5:6:672"
            . " RRA:AVERAGE:0.5:24:732"
            . " RRA:AVERAGE:0.5:144:1460";

        echo $cmd."\n";
        system($cmd);
    }

    $cmdline=array();
    $cmdline[]="{$GLOBALS["RRDTOOL"]} update $filebase";
    $cmdline[]="-t cons:procs";
    $cmdline[]="$time:$CNXS:$proc";
    exec(@implode(" ",$cmdline)." 2>&1",$results);
    foreach ($results as $line){
        echo "RES: $line\n";
    }

    return true;
}


function scan_squid_request_torrd($data=array()):bool{
    $unix=new unix();
    $base=ArticaRRDBase();
    $dbname="proxy_cnxs";
    $filebase="$base/$dbname.rrd";
    if(!isset($GLOBALS["RRDTOOL"])) {
        $GLOBALS["RRDTOOL"] = $unix->find_program("rrdtool");
    }

    $time=$data["TIME"];
    $sdate=date("Y-m-d H:i:s");
    $CNXS=$data["CNXS"];
    $USERS=$data["USERS"];
    $CLIENTS=$data["CLIENTS"];
    $SITES=$data["SITES"];

    syslog_squid("$sdate Connections: $CNXS Clients: $CLIENTS");


    if(!is_file($filebase)){
        $dbtime=$time-60;
        $cmd="{$GLOBALS["RRDTOOL"]} create $filebase -s 60 --start $dbtime"
                . " DS:cons:DERIVE:600:0:100000"
                . " DS:users:DERIVE:600:0:100000"
                . " DS:clients:DERIVE:600:0:100000"
                . " DS:sites:DERIVE:600:0:100000"
                . " RRA:AVERAGE:0.5:1:576"
                . " RRA:AVERAGE:0.5:6:672"
                . " RRA:AVERAGE:0.5:24:732"
                . " RRA:AVERAGE:0.5:144:1460";

        echo $cmd."\n";
        system($cmd);
        }

    $cmdline=array();
    $cmdline[]="{$GLOBALS["RRDTOOL"]} update $filebase";
    $cmdline[]="-t cons:users:clients:sites";
    $cmdline[]="$time:$CNXS:$USERS:$CLIENTS:$SITES";
    exec(@implode(" ",$cmdline)." 2>&1",$results);
    foreach ($results as $line){
        echo "RES: $line\n";
    }

    return true;
}

function scan_squid_negociator_parsefile($time,$filename):array{
    $Process=0;
    $requests_sent=0;
    $ds=explode("\n",@file_get_contents($filename));
    foreach ($ds as $line){
        $line=trim($line);
        if($line==null){continue;}

        if(preg_match("#requests sent:\s+([0-9]+)#i",$line,$re)){
            $rqs=intval($re[1]);
            $requests_sent=$requests_sent+$rqs;
            continue;
        }

        if(preg_match("#^[0-9]+\s+[0-9]+\s+[0-9]+\s+([0-9]+)\s+[0-9]+\s+#",$line,$re)){
            $rqs=intval($re[1]);
            if($rqs==0){continue;}
            $Process++;
        }

    }

    //$sdate=date("Y-m-d H:i:s",$time);
    $CACHE_FILE="/home/artica/squid/squidclient/negotiateauthenticator_daemons";
    $CACHED=intval(@file_get_contents($CACHE_FILE));
    if($CACHED==0){$CACHED=$requests_sent;}
    if($CACHED>$requests_sent){$CACHED=$requests_sent;}
    $diff=$requests_sent-$CACHED;
    @file_put_contents($CACHE_FILE,$requests_sent);

    //echo "$sdate $diff Connections : processes = $Process\n";

    return array("TIME"=>$time,
        "CNXS"=>$diff,
        "PR"=>$Process,
    );



}

function scan_squid_requests_parsefile($time,$filename):array{
    $CONNECTION_NUMBER=0;
    $CONNECTION_REMOTE=array();
    $CONNECTION_USER=array();
    $CONNECTION_WWW=array();
    $ds=explode("\n",@file_get_contents($filename));
    foreach ($ds as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#Connection:\s+0x#i",$line)){
            $CONNECTION_NUMBER++;
            continue;
        }
        if(preg_match("#remote:\s+(.+)#i",$line,$re)){
            $host=trim($re[1]);
            if(preg_match("#^(.+?):[0-9]+#",$host,$re)){$host=$re[1];}
            $CONNECTION_REMOTE[$host]=true;
            continue;
        }
        if(preg_match("#username\s+(.+)#i",$line,$re)){
            $usr=trim($re[1]);
            if($usr=="-"){continue;}
            $CONNECTION_USER[strtolower($usr)]=true;
            continue;
        }
        if(preg_match("#uri\s+(.+)#",$line,$re)){
            $uri=trim($re[1]);
            $host=null;
            if(!preg_match("#^([a-z]+):\/\/#",$uri)){$uri="https://{$re[1]}";}
            $url_p = parse_url($uri);
            if (isset ($url_p["host"])) { $host = $url_p["host"]; }
            if($host==null){continue;}
            if(preg_match("#^(.+?):[0-9]+#",$host,$re)){
                $host=$re[1];
            }
            $CONNECTION_WWW[$host]=true;

        }
    }
    if($CONNECTION_NUMBER==0){return array();}
    $nbusers=count($CONNECTION_USER);
    $nbwww=count($CONNECTION_WWW);
    $nbclients=count($CONNECTION_REMOTE);

    return array("TIME"=>$time,
        "CNXS"=>$CONNECTION_NUMBER,
        "USERS"=>$nbusers,
        "CLIENTS"=>$nbclients,
        "SITES"=>$nbwww

    );




}




function scan_squid(){
    if($GLOBALS["VERBOSE"]){$GLOBALS["makeQueryForce"]=true;}
	$unix=new unix();
    $TimePID="/etc/artica-postfix/pids/exec.rrd.php.scan_squid.pid";
    $pid=$unix->get_pid_from_file($TimePID);
    if($unix->process_exists($pid,basename(__FILE__))){
        $unix->ToSyslog("scan_squid(): Already Artica process running pid $pid",false,"rrd");
        return false;
    }
    $SQUIDEnable            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $base=ArticaRRDBase();
    if(!is_dir($base)){@mkdir($base,0755,true);}


    @file_get_contents($TimePID,getmypid());
    if($SQUIDEnable==1) {
        scan_squid_requests();

    }

    $unix->ToSyslog("scan_squid(): Running process",false,"rrd");
    $rrdtool=$unix->find_program("rrdtool");


    $dbname1 = 'squid_cache';
    $dbname2 = 'squid_response';
    $reqtot = 0;
    $reqhit = 0;
    $bytetot = 0;
    $bytehit = 0;
    $bytesrv = 0;
    $disku   = 0;
    $allmedsvc= 0;
    $dnsmedsvc=0;
    $diskmax=0;
    if($GLOBALS["VERBOSE"]){echo " ****************************** MySQL ******************************\n";}
    scan_mysql();
    cron_tasks();


    if($SQUIDEnable==1) {
        $counter_cache = "/etc/artica-postfix/squid.counters.txt";
        if (is_file($counter_cache)) {
            $timefile = $unix->file_time_min($counter_cache);
            if ($timefile < 2) {
                $data = explode("\n", @file_get_contents($counter_cache));
            } else {
                echo "makeQuery:: Counters...\n";
                $data = explode("\n", $unix->squidclient("counters", true));
            }
        }
        if (!is_array($data)) {
            return false;
        }
        if (count($data) < 10) {
            echo "Data array less than 10 (" . count($data) . ")\n";
            return false;
        }
        if (is_file($counter_cache)) {
            @unlink($counter_cache);
        }
        @file_put_contents($counter_cache, @implode("\n", $data));
        foreach ($data as $line) {
            if (preg_match("#client_http\.requests.*?=.*?([0-9]+)#", $line, $re)) {
                $reqtot = $re[1];
                continue;
            }
            if (preg_match("#client_http\.hits.*?=.*?([0-9]+)#", $line, $re)) {
                $reqhit = $re[1];
                continue;
            }
            if (preg_match("#client_http\.kbytes_out.*?=.*?([0-9]+)#", $line, $re)) {
                $bytetot = $re[1];
                continue;
            }
            if (preg_match("#client_http\.hit_kbytes_out.*?=.*?([0-9]+)#", $line, $re)) {
                $bytehit = $re[1];
                continue;
            }
            if (preg_match("#server\.all\.kbytes_in.*?=.*?([0-9]+)#", $line, $re)) {
                $bytesrv = $re[1];
            }
        }

        if (!is_file("$base/$dbname1.rrd")) {
            echo "-------> Creating $base/$dbname1.rrd\n";
            shell_exec("$rrdtool create $base/$dbname1.rrd -s 300"
                . " DS:reqtot:DERIVE:600:0:100000"
                . " DS:bytetot:DERIVE:600:0:100000"
                . " DS:reqhit:DERIVE:600:0:100000"
                . " DS:bytehit:DERIVE:600:0:100000"
                . " DS:bytesrv:DERIVE:600:0:100000"
                . " DS:vdisku:GAUGE:600:0:100"
                . " RRA:AVERAGE:0.5:1:576"
                . " RRA:AVERAGE:0.5:6:672"
                . " RRA:AVERAGE:0.5:24:732"
                . " RRA:AVERAGE:0.5:144:1460");
        }
        echo "makeQuery:: storedir...\n";
        $data = explode("\n", $unix->squidclient("storedir", true));
        foreach ($data as $line) {
            if (preg_match("#Current Capacity.*?=.*?([0-9\.,]+)#", $line, $re)) {
                $disku = $re[1];
                continue;
            }
            if (preg_match("#Maximum Swap Size.*?=.*?([0-9\.,]+)#", $line, $re)) {
                $diskmax = $re[1];
            }

        }
        echo "Requests total = $reqtot\n";
        echo "Request from cache = $reqhit\n";
        echo "kBytes total = $bytetot\n";
        echo "kBytes from cache = $bytehit\n";
        echo "kBytes from server = $bytesrv\n";
        $diskmax = $diskmax / 1024;
        echo "Disk Cache use percent = $disku\n";
        echo "Disk Cache size = $diskmax\n";
        @file_put_contents("/etc/artica-postfix/proxy.disk.max.rrd", $diskmax);
        $cmdline = array();
        $cmdline[] = "$rrdtool update $base/$dbname1.rrd";
        $cmdline[] = "-t reqtot:reqhit:bytetot:bytehit:bytesrv:vdisku";
        $cmdline[] = "N:$reqtot:$reqhit:$bytetot:$bytehit:$bytesrv:$disku";
        shell_exec(@implode(" ", $cmdline));

        $data = explode("\n", $unix->squidclient("5min", true));

        foreach ($data as $line) {
            if (preg_match("#client_http\.all_median_svc_time.*?=.*?([0-9\.,]+)#", $line, $re)) {
                $allmedsvc = $re[1];
                continue;
            }
            if (preg_match("#dns\.median_svc_time.*?=.*?([0-9\.,]+)#", $line, $re)) {
                $dnsmedsvc = $re[1];
            }

        }
        echo "All median service time = $allmedsvc\n";
        echo "DNS median service time = $dnsmedsvc\n";

        if (!is_file("$base/$dbname2.rrd")) {
            echo "-------> Creating $base/$dbname2.rrd\n";
            shell_exec("$rrdtool create $base/$dbname2.rrd -s 300"
                . " DS:allmedsvc:GAUGE:600:0:10"
                . " DS:dnsmedsvc:GAUGE:600:0:10"
                . " RRA:AVERAGE:0.5:1:576"
                . " RRA:AVERAGE:0.5:6:672"
                . " RRA:AVERAGE:0.5:24:732"
                . " RRA:AVERAGE:0.5:144:1460");
        }
        shell_exec("$rrdtool update $base/$dbname2.rrd -t allmedsvc:dnsmedsvc N:$allmedsvc:$dnsmedsvc");
    }

    $periods[]="hourly";
    $periods[]="yesterday";
    $periods[]="day";
    $periods[]="week";
    $periods[]="month";
    $periods[]="year";

    foreach ($periods as $period) {
        if($SQUIDEnable==1){CreateGraphSquid($period);}
        if($SQUIDEnable==1){CreateGraphSquid($period,true);}
        CreateGraphNginx($period);
        CreateGraphNginx($period,true);
        CreateGraphCron($period);
        CreateGraphCron($period,true);
    }

    $unix->ToSyslog("scan_squid(): Stopping process",false,"rrd");
    return true;
	
}
function  getColors(){
    $Gridcolor="#ebebeb";
    $linecolor="1db496";
    $bgcolor="-c BACK#FFFFFF -c CANVAS#FFFFFF -c SHADEA#FFFFFF -c SHADEB#FFFFFF -c GRID$Gridcolor -c MGRID$Gridcolor -c ARROW#FFFFFF  --slope-mode --watermark \"$(date -R)\" --no-gridfit --font TITLE:13:Arial --font AXIS:8:'Arial' --font LEGEND:8:'Courier' --font UNIT:8:'Arial' --font WATERMARK:6:'Arial'";


    return array("Gridcolor"=>$Gridcolor,
        "linecolor"=>$linecolor,
        "bgcolor"=>$bgcolor,
        "width"=>950,"heigth"=>500,"flat_width"=>1200,"flat_heigth"=>150,
        "img_path"=>"/usr/share/artica-postfix/img/squid"
        );

}
function CreateGraphCron($period,$flat=false):bool{
    $base=ArticaRRDBase();
    $cron_processes="$base/cron.rrd";

    if(!is_file($cron_processes)) {return true;}
        simple_graph($period,$flat,"$cron_processes","cron","tasks",
            "Number of processes over the last $period","Tasks");

    return true;
}
function rrd_syslog($text){
    if(!function_exists("syslog")){return false;}
    openlog("rrdtool", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
}
function CreateGraphsRequests(){
    $base=ArticaRRDBase();
    $nginx_requests="$base/nginx_requests.rrd";
    $period="hourly";
    $flat=false;
    echo "Simplegraph...\n";
    simple_graph($period,$flat,$nginx_requests,"nginx_requests","requests",
        "Number of requests over the last $period","Requests");
}
function CreateGraphNginxHosts($period,$flat=false):bool{
    $unix=new unix();
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxVTSModule"))==0){
        return false;
    }

    $old_requests=unserialize(@file_get_contents("/etc/postfix/nginx-vts.old.requests"));
    if(!is_array($old_requests)){return false;}
    $base=ArticaRRDBase();
    foreach ($old_requests as $hostname=>$none){
        $rrd_base="$base/nginx_host_$hostname.rrd";
        if(!is_file("$rrd_base")) {continue;}

        simple_graph($period,$flat,$rrd_base,"nginx_requests_$hostname","requests",
            "Number of requests over the last $period","Requests");

        simple_graph($period,$flat,$rrd_base,"nginx_bandwidth_$hostname","bandwidth",
            "Use bandwidth over the last $period","KB");
    }
    return true;

}
function CreateGraphNginx($period,$flat=false):bool{
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==0){return false;}
    $base=ArticaRRDBase();
    $nginx_requests="$base/nginx_requests.rrd";
    $nginx_cnxs="$base/nginx_cnx.rrd";
    if(is_file($nginx_requests)) {
        simple_graph($period,$flat,$nginx_requests,"nginx_requests","requests",
            "Number of requests over the last $period","Requests");

    }
    if(is_file($nginx_cnxs)) {
        simple_graph($period,$flat,"$nginx_cnxs","nginx-cnxs","connections",
            "Number of active connections over the last $period","Connections");

    }


    if(is_file("$base/fpm-wordpress.rrd")){
        simple_graph($period,$flat,"$base/fpm-wordpress.rrd","fpm-wordpress","requests",
            "Number of PHP Engine requests over the last $period","Requests");
    }

    return true;
}
function scan_wordpress_phpfpm(){
    if(isset($GLOBALS["scan_wordpress_phpfpm"])){return true;}
    $GLOBALS["scan_wordpress_phpfpm"]=true;
    if(!is_file("/etc/init.d/wordpress-fpm")){return true;}
    $unix=new unix();
    $rrdtool=$unix->find_program("rrdtool");
    $stampfile="/etc/postfix/fpm-wordpress.old.requests";
    include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
    $curl=new ccurl("http://127.0.0.1:1842/fpm-wordpress-status?json",true,"127.0.0.1");
    $curl->NoHTTP_POST=true;
    $curl->interface="127.0.0.1";
    if(!$curl->get()){return false;}
    $json = $curl->data;
    $decoded=json_decode($json);
    $current_requests=intval($decoded->{'accepted conn'});
    //$active_processes=$decoded->{'active_processes'};
    $old_requests=intval(@file_get_contents($stampfile));
    $base=ArticaRRDBase();
    if($old_requests>$current_requests){
        $old_requests=$current_requests;
    }
    $rest=$current_requests-$old_requests;
    @file_put_contents($stampfile,$current_requests);

    if(!is_file($rrdtool)){return true;}
    if(!is_dir($base)){@mkdir($base,0755);}

    if(!is_file("$base/fpm-wordpress.rrd")) {
        echo "-------> Creating $base/fpm-wordpress.rrd\n";
        shell_exec("$rrdtool create $base/fpm-wordpress.rrd -s 300"
            . " DS:requests:GAUGE:600:0:90000"
            . " RRA:AVERAGE:0.5:1:576"
            . " RRA:AVERAGE:0.5:6:672"
            . " RRA:AVERAGE:0.5:24:732"
            . " RRA:AVERAGE:0.5:144:1460");
    }
    rrd_syslog("Wordpress-FPM: requests: $rest");
    shell_exec("$rrdtool update $base/fpm-wordpress.rrd -t requests N:$rest");
    return true;

}
function CreateGraphSquid2($period,$flat=false):bool{
    if(!is_file("/etc/init.d/squid")){return true;}
    $base=ArticaRRDBase();
    $filebase_connexions="$base/proxy_cnxs.rrd";
    $filebase_negotatiors="$base/proxy_nego.rrd";



    if(is_file($filebase_connexions)) {

        echo " **************** Persistent connections ***************************\n";
        simple_graph($period,$flat,"$filebase_connexions","pcon","cons",
            "Number of connections over the last $period","Connections");

        simple_graph($period,$flat,$filebase_connexions,"pusers","users",
                    "Number of Members over the last $period","Members");

        simple_graph($period,$flat,$filebase_connexions,"pclients","clients",
                    "Number of Clients/IPs over the last $period","IP");

        simple_graph($period,$flat,$filebase_connexions,"psites","sites",
                    "Number Connected Websites over the last $period","websites");

    }
    if(is_file($filebase_negotatiors)){
        simple_graph($period,$flat,$filebase_negotatiors,"negcon","cons",
            "Number of Authentications over the last $period","Authentications");

        simple_graph($period,$flat,$filebase_negotatiors,"negprocs","procs",
            "Number of Processes over the last $period","Processes");

    }

    return true;


}
function simple_graph($period,$flat,$database,$picture,$table,$title,$labelx){

    $getColors=getColors();
    $linecolor=$getColors["linecolor"];
    $bgcolor=$getColors["bgcolor"];
    $tperiod=getPeriod($period);
    $img_path=$getColors["img_path"];
    $flatName=null;
    $img_with=$getColors["width"];
    $img_heigth=$getColors["heigth"];

    if($flat){
        $img_with=$getColors["flat_width"];
        $img_heigth=$getColors["flat_heigth"];
        $flatName=".flat";
    }

    if(!is_dir($img_path)){@mkdir($img_path,0755,true);}

    if(!isset($GLOBALS["RRDTOOL"])) {
        $unix=new unix();
        $GLOBALS["RRDTOOL"] = $unix->find_program("rrdtool");
    }

    $picture_path="$img_path/$picture-$period$flatName.png";

    $cmd = "{$GLOBALS["RRDTOOL"]} graph $picture_path"
        . " $tperiod"
        . " -t \"$title\""
        . " --lazy -h $img_heigth -w $img_with -l 0 --border 0 --tabwidth 400"
        . " -a PNG"
        . " -v \"$labelx\""
        . " $bgcolor"
        . " DEF:filed=$database:$table:AVERAGE"
        . " CDEF:base=filed"
        . " GPRINT:base:MAX:\" Max\\: %5.1lf %s\""
        . " GPRINT:base:AVERAGE:\" Avg\\: %5.1lf %S\""
        . " GPRINT:base:LAST:\" Current\\: %5.1lf %S\\n\""
        . " LINE3:base#$linecolor:$labelx";

    echo $cmd . "\n\n\n";
    shell_exec($cmd);
}
function getPeriod($period){
    $tperiod="-s \"-1$period\"";
    if($period=="hourly"){
        return "-s end-1h";
    }
    if($period=="yesterday"){
        return "--end 00:00";
    }

    return $tperiod;
}
function CreateGraphSquid($period,$flat=false){
    $unix=new unix();
    $rrdtool=$unix->find_program("rrdtool");
    $img="/usr/share/artica-postfix/img/squid";
    if(!is_dir($img)){@mkdir($img,0755,true);}
    $rrd="/home/artica/rrd";
    $flatName="";
    $dbname1 = 'squid_cache';
    $dbname2 = 'squid_response';
    $dbname3 = 'squid_memory';
    $imgname11 = 'squid_cache';
    $imgname12 = 'squid_cache2';
    $imgname13 = 'squid_cache3';
    $imgname21 = 'squid_response';
    $imgname31 = 'squid_memory';


    $getColors=getColors();
    $linecolor=$getColors["linecolor"];
    $bgcolor=$getColors["bgcolor"];
    $tperiod=getPeriod($period);

    $img_with=950;
    $img_heigth=500;


    if($flat){
        $img_with=1200;
        $img_heigth=150;
        $flatName=".flat";
    }

    if(is_file("/etc/init.d/squid")){
        CreateGraphSquid2($period,$flat);
        $diskmax=@file_get_contents("/etc/artica-postfix/proxy.disk.max.rrd");
        shell_exec("$rrdtool graph $img/$imgname11-$period$flatName.png"
                    ." $tperiod"
                    ." -t \"Proxy activity over the last $period\""
                    ." --lazy"
                    ." -h $img_heigth -w $img_with"
                    ." -l 0 -u 100 -r --units-exponent 0"
                    ." -a PNG"
                    ." -v \"percent\""
                    ." $bgcolor"
                    ." DEF:vreqtot=$rrd/$dbname1.rrd:reqtot:AVERAGE"
                    ." DEF:vbytetot=$rrd/$dbname1.rrd:bytetot:AVERAGE"
                    ." DEF:vreqhit=$rrd/$dbname1.rrd:reqhit:AVERAGE"
                    ." DEF:vbytehit=$rrd/$dbname1.rrd:bytehit:AVERAGE"
                    ." DEF:vbytesrv=$rrd/$dbname1.rrd:bytesrv:AVERAGE"
                    ." DEF:vdisku=$rrd/$dbname1.rrd:vdisku:AVERAGE"
                    ." CDEF:reqhit=vreqhit,vreqtot,/,100,*"
                    ." CDEF:byterat=vbytehit,vbytehit,vbytesrv,+,/,100,*"
                    ." CDEF:bytegra=vbytehit,vbytesrv,+,"
                  ."vbytehit,vbytehit,vbytesrv,+,/,"
                          ."0,IF,100,*"
                    ." LINE2:vdisku#5447A2:\"% Disk cache in use  \""
                    ." GPRINT:vdisku:MAX:\" Max\\: %5.1lf\""
                    ." GPRINT:vdisku:AVERAGE:\" Avg\\: %5.1lf\""
                    ." GPRINT:vdisku:LAST:\" Current\\: %5.1lf %% of $diskmax"
                            ."MB\\n\""
                    ." LINE2:reqhit#FFCC66:\"% Requests from cache\""
                    ." GPRINT:reqhit:MAX:\" Max\\: %5.1lf\""
                    ." GPRINT:reqhit:AVERAGE:\" Avg\\: %5.1lf\""
                    ." GPRINT:reqhit:LAST:\" Current\\: %5.1lf %%\\n\""
                    ." LINE2:bytegra#FF9900:\"% Bytes from cache   \""
                    ." GPRINT:byterat:MAX:\" Max\\: %5.1lf\""
                    ." GPRINT:byterat:AVERAGE:\" Avg\\: %5.1lf\""
                    ." GPRINT:byterat:LAST:\" Current\\: %5.1lf %%\""
                    );
        shell_exec("$rrdtool graph $img/$imgname12-$period$flatName.png"
            ." $tperiod"
            ." -t \"Proxy requests served over the last $period\""
            ." --lazy"
            ." -h $img_heigth -w $img_with"
            ." -l 0"
            ." -a PNG"
            ." -v \"requests/sec\""
            ." DEF:vreqtot=$rrd/$dbname1.rrd:reqtot:AVERAGE"
            ." DEF:vreqhit=$rrd/$dbname1.rrd:reqhit:AVERAGE"
            ." CDEF:reqtot=vreqtot"
            ." CDEF:reqhit=vreqhit"
            ." LINE2:reqtot#FF9900:\"Requests total     \""
            ." GPRINT:reqtot:MAX:\" Max\\: %6.1lf\""
            ." GPRINT:reqtot:AVERAGE:\" Avg\\: %6.1lf\""
            ." GPRINT:reqtot:LAST:\" Current\\: %6.1lf /sec\\n\""
            ." LINE2:reqhit#FFCC66:\"Requests from cache\""
            ." GPRINT:reqhit:MAX:\" Max\\: %6.1lf\""
            ." GPRINT:reqhit:AVERAGE:\" Avg\\: %6.1lf\""
            ." GPRINT:reqhit:LAST:\" Current\\: %6.1lf /sec\""
        );
        shell_exec("$rrdtool graph $img/$imgname13-$period$flatName.png"
            ." $tperiod"
            ." -t \"Proxy bytes served over the last $period\""
            ." --lazy"
            ." -h $img_heigth -w $img_with"
            ." -l 0"
            ." -a PNG"
            ." -v \"bytes/sec\""
            ." $bgcolor"
            ." DEF:vbytetot=$rrd/$dbname1.rrd:bytetot:AVERAGE"
            ." DEF:vbytehit=$rrd/$dbname1.rrd:bytehit:AVERAGE"
            ." DEF:vbytesrv=$rrd/$dbname1.rrd:bytesrv:AVERAGE"
            ." CDEF:bytetot=vbytetot,1024,*"
            ." CDEF:bytehit=vbytehit,1024,*"
            ." CDEF:bytesrv=vbytesrv,1024,*"
            ." LINE2:bytetot#006600:\"bytes to clients \""
            ." GPRINT:bytetot:MAX:\" Max\\: %5.1lf %s\""
            ." GPRINT:bytetot:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:bytetot:LAST:\" Current\\: %5.1lf %Sbytes/sec\\n\""
            ." LINE2:bytesrv#FF9900:\"bytes from red   \""
            ." GPRINT:bytesrv:MAX:\" Max\\: %5.1lf %s\""
            ." GPRINT:bytesrv:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:bytesrv:LAST:\" Current\\: %5.1lf %Sbytes/sec\\n\""
            ." LINE2:bytehit#FFCC66:\"bytes from cache \""
            ." GPRINT:bytehit:MAX:\" Max\\: %5.1lf %s\""
            ." GPRINT:bytehit:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:bytehit:LAST:\" Current\\: %5.1lf %Sbytes/sec\""
        );
        shell_exec("$rrdtool graph $img/$imgname21-$period$flatName.png"
            ." $tperiod"
            ." -t \"Proxy response over the last $period\""
            ." --lazy"
            ." -h $img_heigth -w $img_with"
            ." -l 0"
            ." -a PNG"
            ." -v \"seconds\""
            ." $bgcolor"
            ." DEF:all=$rrd/$dbname2.rrd:allmedsvc:AVERAGE"
            ." DEF:dns=$rrd/$dbname2.rrd:dnsmedsvc:AVERAGE"
            //." AREA:all#FFCC66:\"All median service time\""
            ." GPRINT:all:MAX:\" Max\\: %5.1lf %s\""
            ." GPRINT:all:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:all:LAST:\" Current\\: %5.1lf %Ssec\\n\""
            //." AREA:dns#FF9900:\"DNS median service time\""
            ." GPRINT:dns:MAX:\" Max\\: %5.1lf %s\""
            ." GPRINT:dns:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:dns:LAST:\" Current\\: %5.1lf %Ssec\""
            ." LINE1:all#CC9966"
            ." LINE1:dns#$linecolor"
        );
        system("$rrdtool graph $img/$imgname31-$period$flatName.png"
            ." $tperiod"
            ." -t \"Proxy memory over the last $period\""
            ." --lazy"
            ." -h $img_heigth -w $img_with"
            ." -l 0"
            ." -a PNG"
            ." -v \"Megabytes\""
            ." $bgcolor"
            ." DEF:acc=$rrd/$dbname3.rrd:memacc:AVERAGE"
            ." DEF:tot=$rrd/$dbname3.rrd:memtot:AVERAGE"
            ." CDEF:base=tot,acc,-"
            ." AREA:base#FF9900:\"Overhead \""
            ." GPRINT:base:MAX:\" Max\\: %5.1lf %s\""
            ." GPRINT:base:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:base:LAST:\" Current\\: %5.1lf %SMB\\n\""
            ." STACK:acc#FFCC66:\"Accounted\""
            ." GPRINT:acc:MAX:\" Max\\: %5.1lf %s\""
            ." GPRINT:acc:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:acc:LAST:\" Current\\: %5.1lf %SMB\\n\""
            ." GPRINT:tot:MAX:\"  Total    "
            ."   Max\\: %5.1lf %s\""
            ." GPRINT:tot:AVERAGE:\" Avg\\: %5.1lf %S\""
            ." GPRINT:tot:LAST:\" Current\\: %5.1lf %SMB\""
            ." LINE1:tot#CC9966"
            ." LINE3:base#$linecolor"
        );

    }


}



function nginx_caches():bool{
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==0){return true;}
    $nginxCachesDir = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nginxCachesDir"));
    if($nginxCachesDir==0){return true;}
    $NGINX_CACHES_DIR=array();
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $results=$q->QUERY_SQL("SELECT * FROM caches_center");
    foreach ($results as $index=>$ligne){
        $cache_dir=trim($ligne["cache_dir"]);
        $ID=$ligne["ID"];
        if(!is_dir($cache_dir)){
            @mkdir($cache_dir,0755,true);
            $unix->chown_func("www-data","www-data", $cache_dir);
        }
        @chmod($cache_dir,0755);
        $NGINX_CACHES_DIR[$ID]=$unix->DIRSIZE_BYTES($cache_dir);
    }

    $NginxProxyStorePath=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NginxProxyStorePath"));
    $phpcache="$NginxProxyStorePath/php";
    $fastcgi_cache_path=$unix->DIRSIZE_BYTES($phpcache);
    $NGINX_CACHES_DIR["PHPCACHE"]=$fastcgi_cache_path;

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NGINX_CACHES_DIR",serialize($NGINX_CACHES_DIR));
    return true;

}
