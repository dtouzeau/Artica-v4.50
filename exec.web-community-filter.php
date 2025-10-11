<?php
if (!file_exists('/etc/artica-postfix/settings/Daemons/NewLicServer')) {
    @touch("/etc/artica-postfix/settings/Daemons/NewLicServer");
}
//SP94 // SP96++ // SP 99 ++
$GLOBALS["isRegistered"]=@file_get_contents('/etc/artica-postfix/settings/Daemons/NewLicServer');
// SP1 2020 06 02
$GLOBALS["YESCGROUP"]=true;
define("LkdPTEQ", base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLkdPTEQ="));
define("Suvgh",base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9zZXR0aW5ncy9EYWVtb25zL1dpemFyZFNhdmVkU2V0dGluZ3M="));
define("isoHF",base64_decode("L2V0Yy9hcnRpY2EtcG9zdGZpeC9GUk9NX0lTTw=="));
define("htpPat",base64_decode("aHR0cDovL2FydGljYXRlY2gubmV0L3BhdHRlcm5zLnBocD94a2V5"));
define("syslogSuff",base64_decode("QVJUSUNBX0xJQ0VOU0U="));
$GLOBALS["SCRIPT_SUFFIX"]="--script=".basename(__FILE__);
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if (function_exists("posix_getuid")) {
    if (posix_getuid()<>0) {
        die("Cannot be used in web server mode\n\n");
    }
}
$GLOBALS["BYCRON"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["STAMP"]=false;
$GLOBALS["EMAIL"]=false;
$GLOBALS["MIGRATION"] = false;
$GLOBALS["FROM_WIZARD"] = false;
$GLOBALS["REMOTE_OUTPUT"]=false;

CheckRsyslogd();

if (preg_match("#--email#", implode(" ", $argv))) {
    $GLOBALS["EMAIL"]=true;
}
if (preg_match("#--force#", implode(" ", $argv))) {
    $GLOBALS["FORCE"]=true;
}
if (preg_match("#--verbose#", implode(" ", $argv))) {
    $GLOBALS["VERBOSE"]=true;
    $GLOBALS["VERBOSE"]=true;
    ini_set('html_errors', 0);
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
}
//$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.squidguard.inc');
$GLOBALS["MYPID"]=getmypid();

$unix=new unix();
if (is_file(isoHF)) {
    if ($unix->file_time_min(isoHF)<1) {
        echo "Time less than 1mn\n";
        exit();
    }
}

$unix=new unix();
$uptime=$unix->ServerRunSince();
echo "Server running since ".$uptime." minutes...\n";
if (!$GLOBALS["FORCE"]) {
    if ($uptime < 5) {
        echo "Server run since " . $uptime . " minutes ( need 5mn, restart later)\n";
        build_progress_influx("Server run since " . $uptime . " minutes ( need 5mn, restart later)", 110);
        build_progress("Server run since " . $uptime . " minutes ( need 5mn, restart later)", 110);
        exit();
    }
}

if (preg_match("#--remote-output#", implode(" ", $argv))) {
    $GLOBALS["REMOTE_OUTPUT"]=true;
}
if (preg_match("#--force#", implode(" ", $argv))) {
    $GLOBALS["FORCE"]=true;
}
if (preg_match("#--bycron#", implode(" ", $argv))) {
    $GLOBALS["BYCRON"]=true;
}
if (preg_match("#--set-stamp#", implode(" ", $argv))) {
    $GLOBALS["STAMP"]=true;
}
if (preg_match("#--migration#", implode(" ", $argv))) {
    $GLOBALS["MIGRATION"] = true;
}
if (preg_match("#--from-wizard#", implode(" ", $argv))) {
    $GLOBALS["FROM_WIZARD"] = true;
}

if(isset($argv[1])) {
    if ($argv[1] == "--register") {register();exit();}
    if ($argv[1] == "--uuid") {uuid_check();exit();}
    if ($argv[1] == "--register-lic") {register_lic();exit();}
    if ($argv[1] == "--force-updates") {$GLOBALS["FORCE"] = true;others_update();exit();}
    if ($argv[1] == "--install-key") {install_key($argv[2]);exit();}
    if ($argv[1] == "--ping-cloud") {ping_server();exit();}
    if ($argv[1] == "--get-key") {get_key($argv[2], $argv[3]);exit();}
    if ($argv[1] == "--register-demo") {register_demo();exit();}
    if ($argv[1] == "--register-kaspersky") {exit();}
    if ($argv[1] == "--uuid") {$unix = new unix();echo $unix->GetUniqueID() . "\n";exit();}
    if ($argv[1] == "--patterns") {patterns();exit();}
    if ($argv[1] == "--remove-grace-period") {remove_grace_period();exit();}
    if ($argv[1] == "--drop-categorize") {drop_categorize();exit();}
    if (!ifMustBeExecuted()) {
        events("ifMustBeExecuted() -> FALSE, DIE()", __FUNCTION__, __FILE__, __LINE__);
        exit();
    }

    if ($argv[1] == "--sitesinfos") {exit();}
    if ($argv[1] == "--groupby") {exit();}
    if ($argv[1] == "--export") {exit();}
    if ($argv[1] == "--export-deleted") {exit();}
    if ($argv[1] == "--export-weighted") {exit();}
    if ($argv[1] == "--export-not-categorized") {exit();}

}




$t=time();
$sock=new sockets();
$users=new usersMenus();

$EnableSquidRemoteMySQL=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSquidRemoteMySQL");
if (!is_numeric($EnableSquidRemoteMySQL)) {
    $EnableSquidRemoteMySQL=0;
}
if ($EnableSquidRemoteMySQL==1) {
    exit();
}


$system_is_overloaded=system_is_overloaded();
if ($system_is_overloaded) {
    $unix=new unix();
    exit();
}


$WebCommunityUpdatePool=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebCommunityUpdatePool");
if (!is_numeric($WebCommunityUpdatePool)) {
    $WebCommunityUpdatePool=120;
    $sock->SET_INFO("WebCommunityUpdatePool", 120);
}


$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
$cachetime="/etc/artica-postfix/pids/exec.web-community-filter.php.MAIN.time";
$unix=new unix();
$myFile=basename(__FILE__);
$pid=@file_get_contents($pidfile);
if ($unix->process_exists($pid, $myFile)) {
    events("Already executed PID:$pid, die()", __FUNCTION__, __FILE__, __LINE__);
    exit();
}

$filetime=file_time_min($cachetime);


@mkdir(dirname($cachetime), 0755, true);
@unlink($cachetime);
@file_put_contents($cachetime, time());
@file_put_contents($pidfile, $GLOBALS["MYPID"]);

register_lic();





function register_demo():bool{
    echo "Starting Register Demo process\n";
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/register/key?isDemo=true");
    echo $resp."\n";
    $resp=json_decode($resp,true);
    if($resp[0]["message"]=="server_not_exist_force_register_demo"){
        echo "Demo token set, force register the server";
        register(true,true);
        return true;
    }
    return true;
}
function get_key($username,$password){
    echo "Starting Register Key process\n";
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/register/key?isDemo=false&username=$username&password=$password");
    echo $resp."\n";
    $resp=json_decode($resp,true);
    if($resp[0]["message"]=="server_not_exist_force_register_demo"){
        echo "Token set, force register the server";
        register(true);
        return true;
    }
    return true;
}
function install_key($filename):bool
{
    if(strpos($filename, 'ack_') === 0) {
        install_ack($filename);
        return true;
    }
    else {
        echo "Install Key License\n";
        $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/install/key?file=$filename");
        echo $resp."\n";
        return true;
    }

}
function install_ack($filename):bool{
    echo "Install Key License (ACK)\n";
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/install/key/ack?file=$filename");
    echo $resp."\n";
    return true;
}

function build_progress_influx($text, $pourc)
{
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb.refresh.progress";
    echo "{$pourc}% $text\n";
    $cachefile=$GLOBALS["CACHEFILE"];
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
}


function others_update(){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $cachetime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    $pid=@file_get_contents($pidfile);
    if ($unix->process_exists($pid)) {
        build_progress_influx("Already executed", 110);
        events("Already executed PID:$pid, die()", __FUNCTION__, __FILE__, __LINE__);
        return;
    }

    if (!$GLOBALS["FORCE"]) {
        $TimeCache=$unix->file_time_min($cachetime);
        if ($TimeCache<30) {
            build_progress_influx("Need at least 30Mn ( current {$TimeCache}mn ) ", 110);
            return;
        }
    }
    $TimeFile=$unix->file_time_min("/etc/artica-postfix/settings/Daemons/v4softsRepo");
    if ($GLOBALS["VERBOSE"]) {
        $TimeFile=10000;
    }
    if (!$GLOBALS["FORCE"]) {
        if ($TimeFile<480) {
            ping_server(true);
            return;
        }
    }


    build_progress_influx("{check_repository} (BigData Engine)", 20);
    ping_server();
    build_progress_influx("{check_repository} {done}", 100);
}
function register($force=false,$demo=false):bool{
    echo "Starting Register Server Process\n";
    if ($force) {
        $GLOBALS["FORCE"]=true;
    }
    $Migration = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Migration"));
    if ($Migration==1){
        if(!$GLOBALS["FORCE"]){
            $GLOBALS["FORCE"]=true;
        }
    }
    if ($force) {
        $Migration = 0;
    }
    if ($GLOBALS['FROM_WIZARD']) {
        $Migration = 0;
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/register/server?force=$force&migration=$Migration&fromWizard={$GLOBALS['FROM_WIZARD']}&demo=$demo");
    echo $resp."\n";
    $resp=json_decode($resp,true);
    if($resp[0]["message"]=="server_register_successfully_force_register_license"){
        echo "Server registered successfully, initiating register license process\n";
        register_lic(true);
        return true;
    }
    return true;
}
function uuid_check()
{
    $unix=new unix();
    $uuid=$unix->GetUniqueID();
    echo $uuid."\n";
}

function remove_grace_period()
{
    echo "Remove Grace Period\n";
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/remove/grace/period");
    echo $resp."\n";
    return true;
}



function build_progress($text, $pourc){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";

    $cachefile=PROGRESS_DIR."/artica.license.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile, 0755);
}

function ping_server($noprgress=false):bool{
    echo "Starting ping process\n";
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/ping/cloud");
    echo $resp."\n";
    $resp=json_decode($resp,true);

    if (json_last_error() > JSON_ERROR_NONE) {
        return true;
    }

    if(!isset($resp[0])){
        return true;
    }

    if($resp[0]["message"]=="server_not_exist_force_register" || $resp[0]["message"]=="blacklisted"){
        echo "Server not registered, initiating register process\n";
        register(true);
        return true;
    }
    return true;
}

function register_lic_categories():bool{
    include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
    try {
        $catz = new mysql_catz();
    }catch (Exception $e){
        echo $e->getMessage()."\n";
        return false;
    }
    $catz->ufdbcat_dns_infos();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OFFICIALS_CATZ",$catz->CategoryNumbers);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("OFFICIALS_CATZT",$catz->CategoryTime);
    return true;
}

function register_lic($force=false):bool{
    echo "Starting Register License Process\n";
    $unix                   = new unix();
    if($GLOBALS["STAMP"]){
        if(system_is_overloaded(__FILE__)){return false;}
    }
    try {
        register_lic_categories();
    }catch (Exception $e) {
        echo $e->getMessage() . "\n";
    }
    if ($GLOBALS["STAMP"]) {
        @mkdir("/etc/artica-postfix/pids", 0755, true);
        $filetime = "/etc/artica-postfix/pids/register-license";
        $ztime = $unix->file_time_min($filetime);
        if ($ztime < 120) {
            echo "{$ztime}Mn, require 120Mn... die()\n";
            if($force){return false;}
            die();
        }
        @unlink($filetime);
        @file_put_contents($filetime, time());
    }
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";

    $pid = @file_get_contents($pidfile);

    if ($unix->process_exists($pid)) {
        build_progress("License information: Already executed PID:$pid, die()", 100);
        if($force){return false;}
        die();
    }
    $resp=$GLOBALS["CLASS_SOCKETS"]->REST_API("/register/license?force=$force&migration={$GLOBALS['MIGRATION']}");
    echo $resp."\n";
    $resp=json_decode($resp,true);

    if(!isset($resp[0])){
        return false;
    }

    if($resp[0]["message"]=="force_ping"){
        echo "License registered successfully, ping cloud\n";
        ping_server();
        return true;
    }
    if($resp[0]["message"]=="server_not_exist_force_register" || $resp[0]["message"]=="license_not_exist_force_register"){
        echo "License or Server not registered, initiating Register process\n";
        register(true);
        return true;
    }
    return true;
}
function drop_categorize()
{
    $q=new mysql_squid_builder();
    $q->QUERY_SQL("TRUNCATE TABLE categorize");
}
function uncompress($srcName, $dstName)
{
    $string = implode("", gzfile($srcName));
    $fp = fopen($dstName, "w");
    fwrite($fp, $string, strlen($string));
    fclose($fp);
}
function GetCategory($www)
{
    $q=new mysql_squid_builder();
    return $q->GET_CATEGORIES($www);
}

function events($text, $function, $file=null, $line=null){
    $unix=new unix();
    if($file==null){$file=basename(__FILE__);}
    $unix->ToSyslog("License info: $text ($function/$line)",false,syslogSuff);
    return $file;
}
function ifMustBeExecuted():bool
{
    $users=new usersMenus();
    $update=true;
    $EnableWebProxyStatsAppliance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWebProxyStatsAppliance"));
    if ($EnableWebProxyStatsAppliance==1) {
        return true;
    }
    $CategoriesRepositoryEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesRepositoryEnable");
    if ($CategoriesRepositoryEnable==1) {
        return true;
    }
    if (!$users->SQUID_INSTALLED) {
        $update=false;
    }
    return $update;
}
function CheckRsyslogd(){
    if(is_file("/etc/rsyslog.d/artica-license.conf")){return;}
    $h[]="if  (\$programname =='".syslogSuff."') then {";
    $h[]="\t-/var/log/license.log";
    $h[]="\t& stop";
    $h[]="}";
    $h[]="";
    @file_put_contents("/etc/rsyslog.d/artica-license.conf",@implode("\n", $h));
    $unix=new unix();$unix->RESTART_SYSLOG(true);

}
