<?php
$GLOBALS["TITLENAME"]="Artica Web error page";
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.ccurl.inc");
include_once("/usr/share/artica-postfix/ressources/class.nginx.certificate.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(!isset($argv[1])){
    echo "Wrong command line\n";
    die();
}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--reload"){reload();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--keys"){$GLOBALS["VERBOSE"]=true;ListApiKeys();exit;}
if($argv[1]=="--start-bouncer"){die();exit;}

if($argv[1]=="--metrics"){echo stats_metrics();exit;}
if($argv[1]=="--stats"){stats_ipset_dump();exit;}
if($argv[1]=="--tasks"){tasks();exit;}
if($argv[1]=="--action-trusted"){action_trusted();exit;}
if($argv[1]=="--articalogon"){build_articalogon();exit;}
if($argv[1]=="--suricata"){exit;}
if($argv[1]=="--nginx"){exit;}
if($argv[1]=="--tasks-collections"){tasks_collections_lists();exit;}
if($argv[1]=="--restart-customer-bouncer"){restart_customer_bouncer();exit;}
echo "Unable to understand $argv[1]\n";



function action_trusted():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $pid=CROWDSEC_PID();
    if(!build_whitelists()){return false;}



    if(isset($_GET["/etc/init.d/articapcap"])){
        shell_exec("/usr/sbin/articapsniffer -reload");
    }
    $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
    if($EnableSuricata==1){
        shell_exec("/usr/sbin/artica-phpfpm-service -reconfigure-ids");
    }


    if($unix->process_exists($pid)){
        _out("[INFO]: Reloading PID: $pid after building trusted network");
        $unix->KILL_PROCESS($pid,1);
        return true;
    }
    return true;
}






function action_reload():bool{
    $unix=new unix();
    $pid=CROWDSEC_PID();
    if($unix->process_exists($pid)){
        _out("[INFO]: Reloading PID: $pid");
        $unix->KILL_PROCESS($pid,1);
        build_reload(100,"{reloading} {success}");
        return true;
    }
    _out("[ERROR]: Not running...");
    return start(false);

}

function reload():bool{
    build();
    build_reload(50,"{reloading}");
    build();
    build_reload(70,"{reloading}");
    if(!action_reload()){
        return build_reload(110,"{reloading} {failed}");
    }
    return build_reload(100,"{reloading} {success}");
}







function build_whitelists():bool{
    system("/usr/sbin/artica-phpfpm-service -crowdsec-whitelist -debug");
    return true;
}






function ListApiKeys():array{
    $array=array();
    $unix=new unix();
    $TEMPFILE=$unix->FILE_TEMP();
    shell_exec("/usr/local/sbin/cscli bouncers list -o json >$TEMPFILE 2>&1");
    $data=@file_get_contents($TEMPFILE);
    $json=json_decode($data);
    if(!$json){return $array;}
    //var_dump($json);

    foreach ($json as $index=>$json2){
        $name=$json2->name;
        $api_key=$json2->api_key;
        $array[$name]=$api_key;
    }
    if($GLOBALS["VERBOSE"]){
        print_r($array);
    }
    return $array;
}
function build_custom_bouncer_update_binary():bool{
    $srcfile="/usr/share/artica-postfix/bin/articapsniffer";
    $dstfile="/usr/sbin/articapsniffer";

    if(!is_file($srcfile)){
        _out("$srcfile no such file!");
        return false;

    }
    $md52=null;
    $md51=md5_file($srcfile);
    if(is_file($dstfile)){
        $md52=md5_file($dstfile);
    }
    if($md51==$md52){
        _out("$dstfile up-to-date..");
        @chmod($dstfile,0755);
        return true;
    }
    _out("$dstfile Updating $dstfile ...");
    if(is_file($dstfile)){@unlink($dstfile);}
    @copy($srcfile,$dstfile);
    @chmod($dstfile,0755);
    return true;

}
function build_custom_bouncer():bool{
    $GetServerPort=GetServerPort();
    $ListApiKeys=ListApiKeys();
    $CROWDSEC_BOUNCER_KEY=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CROWDSEC_BOUNCER_KEY");
    $unix=new unix();

    if(strpos($CROWDSEC_BOUNCER_KEY,"level=fatal")>0){
        _out("[ERROR]: $CROWDSEC_BOUNCER_KEY");
        $CROWDSEC_BOUNCER_KEY=null;
    }

    if($CROWDSEC_BOUNCER_KEY==null){
        if(isset($ListApiKeys["custom-bouncer"])){
            _out("Remove old Customer Bouncer API KEY");
            shell_exec("/usr/local/sbin/cscli bouncers delete \"custom-bouncer\"");
        }

        $FILE_TEMP=$unix->FILE_TEMP();
        _out("Create Custom Bouncer API KEY");
        shell_exec("/usr/local/sbin/cscli bouncers add \"custom-bouncer\" -o raw >$FILE_TEMP 2>&1");
        $CROWDSEC_BOUNCER_KEY=trim(@file_get_contents($FILE_TEMP));
        @unlink($FILE_TEMP);
        $ListApiKeys=ListApiKeys();

        if(strpos($CROWDSEC_BOUNCER_KEY,"level=fatal")>0){
            _out("[ERROR]: $CROWDSEC_BOUNCER_KEY");
            $CROWDSEC_BOUNCER_KEY=null;
        }

        if(!isset($ListApiKeys["customer-bouncer"])){
            _out("[ERROR]: Failed to Create firewall-bouncer API KEY");
        }

        _out("[INFO]: firewall-bouncer API KEY \"$CROWDSEC_BOUNCER_KEY\"");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSEC_BOUNCER_KEY",$CROWDSEC_BOUNCER_KEY);

    }
    _out("Custom Bouncer API KEY: $CROWDSEC_BOUNCER_KEY");
    build_custom_bouncer_update_binary();
    $f[]="bin_path: /usr/sbin/articapsniffer";
    $f[]="bin_args: []";
    $f[]="feed_via_stdin: false";
    $f[]="total_retries: 0";
    $f[]="scenarios_containing: []";
    $f[]="scenarios_not_containing: []";
    $f[]="origins: []";
    $f[]="piddir: /var/run/";
    $f[]="update_frequency: 10s";
    $f[]="cache_retention_duration: 10s";
    $f[]="daemonize: true";
    $f[]="log_mode: file";
    $f[]="log_dir: /var/log/";
    $f[]="log_level: info";
    $f[]="log_compression: true";
    $f[]="log_max_size: 100";
    $f[]="log_max_backups: 3";
    $f[]="log_max_age: 30";
    $f[]="api_url: http://127.0.0.1:$GetServerPort/";
    $f[]="api_key: $CROWDSEC_BOUNCER_KEY";
    $f[]="";
    $f[]="prometheus:";
    $f[]="  enabled: true";
    $f[]="  listen_addr: 127.0.0.1";
    $f[]="  listen_port: 60602";
    $f[]="";
    @file_put_contents("/etc/crowdsec/bouncers/crowdsec-custom-bouncer.yaml",@implode("\n",$f));
    _out("/etc/crowdsec/bouncers/crowdsec-custom-bouncer.yaml OK");
    return true;
}





function create_ipset_objects():bool{
    $unix=new unix();
    $ipset=$unix->find_program("ipset");
    $ipsetlist=ipset_list();

    if(!isset($ipsetlist["crowdsec-blacklists"])){
        _out("Create Firewall group crowdsec-blacklists");
        shell_exec("$ipset create crowdsec-blacklists hash:ip timeout 0 maxelem 150000");
    }
    if(!isset($ipsetlist["crowdsec6-blacklists"])){
        _out("Create Firewall group crowdsec-blacklists for ipv6");
        shell_exec("$ipset create crowdsec6-blacklists hash:ip timeout 0 family inet6 maxelem 150000");
    }
    return create_firewall_chains();
}
function create_firewall_chains():bool{
    $unix=new unix();
    $iptables_save=$unix->find_program("iptables-save");
    exec("$iptables_save 2>&1",$results);
    $ActiveDirectoryRestPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPort"));
    if($ActiveDirectoryRestPort==0){ $ActiveDirectoryRestPort=9503; }
    $ArticaRestAllIps=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRestAllIps"));

    foreach ($results as $line){
        if(preg_match("#RULE\.CROWDSEC#",$line)){
            _out("Firewall rules for crowdsec - success");
            return true;
        }
    }
    $iptables=$unix->find_program("iptables");
    $CONF[]="$iptables -t filter -N in_crowdsec || true";

    $CONF[]="$iptables -t filter -I INPUT -m set --match-set crowdsec6-blacklists src -j in_crowdsec -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -t filter -I INPUT -m set --match-set crowdsec-blacklists src -j in_crowdsec -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I in_crowdsec -j DROP -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I in_crowdsec -m limit --limit 1/sec -j LOG --log-prefix \"FIREHOL: CROWDSEC: \" -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -m set --match-set crowdsec6-blacklists src -j in_crowdsec -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -s 127.0.0.1 -p tcp --dport $ActiveDirectoryRestPort -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -s 127.0.0.1 -p tcp --dport 80 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -s 127.0.0.1 -p tcp --dport 3334 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -s 127.0.0.1 -p tcp --dport 443 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -s 127.0.0.1 -p udp --dport 53 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -s 127.0.0.1 -p udp --dport 5516 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
    $CONF[]="$iptables -I INPUT -i lo -s 127.0.0.1 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";



    if(strlen($ArticaRestAllIps)>2){
        $tb=explode(",",$ArticaRestAllIps);
        foreach ($tb as $ipaddr){
            $CONF[]="$iptables -I INPUT -s $ipaddr -p tcp --dport $ActiveDirectoryRestPort -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
            $CONF[]="$iptables -I INPUT -s $ipaddr -p tcp --dport 80 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
            $CONF[]="$iptables -I INPUT -s $ipaddr -p tcp --dport 443 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
            $CONF[]="$iptables -I INPUT -s $ipaddr -p tcp --dport 3334 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
            $CONF[]="$iptables -I INPUT -s $ipaddr -p udp --dport 5516 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
            $CONF[]="$iptables -I INPUT -s $ipaddr -p udp --dport 53 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";
        }
    }
    $CONF[]="$iptables -I INPUT -d 127.0.0.0/8 -j ACCEPT -m comment --comment \"RULE.CROWDSEC\"";

    foreach ($CONF as $cmdline){
        shell_exec($cmdline);
    }

    return true;

}



function ipset_list():array{
    $ipsetlist=array();

    $unix=new unix();
    $ipset=$unix->find_program("ipset");
    exec("$ipset list -n 2>&1",$results);
    foreach ($results as $db){
        $ipsetlist[strtolower($db)]=true;
    }
    return $ipsetlist;

}







function PIDFILE_PATH():string{
    return "/var/run/crowdsec/crowdsec.pid";
}

function tasks(){
    stats_ipset_dump();
    tasks_collections_lists();
    //cscli hub update
}

function tasks_collections_lists():bool{
    exec("/usr/local/sbin/cscli collections list -o json 2>&1",$results);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSEC_COLLECTIONS",base64_encode(@implode("\n",$results)));
    return true;
}

function update():bool{
    shell_exec("/usr/local/sbin/cscli hub update >/dev/null 2>&1");
    return true;
}
function build_progress($prc,$txt):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$txt,"crowdsec.progress");

}
function build_reload($prc,$txt):bool{
    $unix=new unix();
    $unix->framework_progress($prc,$txt,"crowdsec.reconfigure.progress");
    return true;
}

function restart_customer_bouncer():bool{

    stop_custom_bouncer();
    start_custom_bouncer();
    return true;
}

function restart():bool{
    build_progress(25, "{stopping_service}");
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("Already Artica task running PID $pid since {$time}mn");
        build_progress(110, "{stopping_service} {failed}");
        return false;
    }
    if($GLOBALS["MONIT"]) {
        squid_admin_mysql(0,"Ask to restart Web error page service by the watchdog",null,__FILE__,__LINE__);
    }
    @file_put_contents($pidfile, getmypid());
    stop(true);

    build_progress(50, "{starting_service}");
    build();
    if(start(true)) {
        build_progress(100, "{starting_service} {success}");
        return true;
    }
    build_progress(110, "{starting_service} {failed}");
    return false;
}
function GetServerPort():int{
    $ServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CrowdSecServerPort"));
    if($ServerPort==0){return 8808;}
    return $ServerPort;
}

function _out($text):bool{
    echo "Service.......: ".date("H:i:s")." [SERVICE]: $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("crowdsec", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}


function CROWDSEC_PID():int{
    $unix = new unix();
    $pid=$unix->get_pid_from_file(PIDFILE_PATH());
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/local/bin/crowdsec");
}
function CUSTOM_BOUNCER_PID_FILE():string{
    return "/var/run/crowdsec/crowdsec-custom-bouncer.pid";
}

function CUSTOM_BOUNCER_PID():int{
    $unix = new unix();
    $pid=$unix->get_pid_from_file(CUSTOM_BOUNCER_PID_FILE());
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/local/bin/crowdsec-custom-bouncer");
}
function start_custom_bouncer():bool{
    $pid        = CUSTOM_BOUNCER_PID();
    $pidfile    = CUSTOM_BOUNCER_PID_FILE();
    $unix       = new unix();

    if ($unix->process_exists($pid)) {
        $timepid = $unix->PROCCESS_TIME_MIN($pid);
        _out("custom-bouncer: Service already started $pid since {$timepid}Mn...");
        @file_put_contents($pidfile,$pid);
        return true;
    }


    build_custom_bouncer();


    $cmdline="/usr/local/bin/crowdsec-custom-bouncer -c /etc/crowdsec/bouncers/crowdsec-custom-bouncer.yaml";
    $nohup = $unix->find_program("nohup");
    $cmdFallback = "$nohup $cmdline > /dev/null 2>&1 &";
    $cmd = "$cmdline";
    _out("custom-bouncer: Starting service");


    $f = $unix->go_exec($cmd);
    if (!$f){
        _out("[WARNING]: Starting......(Fallback Method) !");
        shell_exec($cmdFallback);
    }

    for ($i = 1; $i < 5; $i++) {
        _out("custom-bouncer: Starting, waiting $i/5");
        sleep(1);
        $pid = CUSTOM_BOUNCER_PID();
        if ($unix->process_exists($pid)) {
            break;
        }
    }

    $pid = CUSTOM_BOUNCER_PID();

    if ($unix->process_exists($pid)) {
        _out("custom-bouncer: Starting Success PID $pid");
        @file_put_contents($pidfile,$pid);
        stats_ipset_dump();
        return true;
    }

    _out("[ERROR]: custom-bouncer: Starting failed $cmd");
    return false;
}

function start():bool{
  system("/usr/sbin/artica-phpfpm-service -start-crowdsec");
  return true;
}
function stop():bool{
    system("/usr/sbin/artica-phpfpm-service -stop-crowdsec");
    return true;
}
function stop_custom_bouncer():bool{
    $unix = new unix();
    $pid = CUSTOM_BOUNCER_PID();

    if (!$unix->process_exists($pid)) {
        _out("custom-bouncer: Stopping service already stopped...");
        return true;
    }
    $pid = CUSTOM_BOUNCER_PID();
    _out("custom-bouncer: Stopping service Shutdown pid $pid...");

    unix_system_kill($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = CUSTOM_BOUNCER_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("custom-bouncer: Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid = CUSTOM_BOUNCER_PID();
    if (!$unix->process_exists($pid)) {
        _out("custom-bouncer: Stopping service success...");
        return true;
    }

    _out("custom-bouncer: Stopping service shutdown - force - pid $pid...");

    unix_system_kill_force($pid);
    for ($i = 0; $i < 5; $i++) {
        $pid = CUSTOM_BOUNCER_PID();
        if (!$unix->process_exists($pid)) {
            break;
        }
        _out("custom-bouncer: Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if ($unix->process_exists($pid)) {
        _out("custom-bouncer: service failed...");
        return false;
    }

    return true;
}

function stats_ipset_dump():bool{
    $unix=new unix();
    $ipset=$unix->find_program("ipset");
    $tempfile=$unix->FILE_TEMP();
    shell_exec("$ipset list crowdsec-blacklists >$tempfile");

    $handle = @fopen($tempfile, "r");
    if ($handle) {
        while (!feof($handle)) {
            $www = trim(fgets($handle, 4096));
            if (preg_match("#Number of entries:\s+([0-9]+)#", $www, $re)) {
                if($GLOBALS["VERBOSE"]){
                    echo "Entries: $re[1]\n";
                }
                $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSET_BLACKLISTS", $re[1]);
                break;
            }
        }
    }
    @unlink($tempfile);
    $tb=explode("\n",stats_metrics());
    $MAIN=array();
    foreach ($tb as $line) {
        $line=trim($line);
        if(preg_match('#^cs_node_hits_ok_total.*?source="(.+?)".*?\}\s+([0-9]+)#',$line,$re)){
            $src=$re[1];
            $num=$re[2];
            if(!isset($MAIN[$src])){
                $MAIN[$src]=intval($num);
            }
        }
    }
    $tott=0;
    foreach ($MAIN as $log=>$num){
        $tott=$tott+$num;
    }
    if($GLOBALS["VERBOSE"]){
        echo "CROWDSET_PARSED: $tott\n";
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CROWDSET_PARSED",$tott);

    return true;

    //
}
function stats_metrics():string{
    $curl = curl_init("http://127.0.0.1:6060/metrics");

    if($GLOBALS["VERBOSE"]){
        echo "stats_metrics..\n";
    }
    $t=time();
    curl_setopt($curl,  CURLOPT_HEADER, true);
    curl_setopt($curl,  CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl,  CURLOPT_FAILONERROR, true);
    curl_setopt($curl,  CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl,  CURLOPT_SSLVERSION,'all');
    curl_setopt($curl,  CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($curl,  CURLOPT_POST, 0);
    curl_setopt($curl,  CURLOPT_USERAGENT, "Mozilla");
    curl_setopt($curl,  CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl,  CURLOPT_TIMEOUT, 30);
    curl_setopt($curl,  CURLOPT_CONNECTTIMEOUT, 30);
    curl_setopt($curl,  CURLOPT_FOLLOWLOCATION, TRUE);

    $data = curl_exec($curl);
    $len=strlen($data);
    if($GLOBALS["VERBOSE"]){
        echo "Length: $len\n";
    }
    $CURLINFO_HTTP_CODE=curl_getinfo($curl,CURLINFO_HTTP_CODE);
    curl_close($curl);
    if($CURLINFO_HTTP_CODE<>200){
        echo "Curl Error $CURLINFO_HTTP_CODE\n";
        return "";
    }

    return $data;
}