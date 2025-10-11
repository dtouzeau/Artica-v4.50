<?php
$GLOBALS["DEBUG_CATZ"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/externals/Net/DNS2.inc");
include_once(dirname(__FILE__)."/ressources/class.tcpip.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

include_once("/usr/share/artica-postfix/ressources/class.resolv.conf.inc");
include_once("/usr/share/artica-postfix/ressources/class.sqlite.inc");
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
if(isset($argv[1])){

    if($argv[1]=="--build"){build();exit();}
    if($argv[1]=="--reload"){restart();exit();}
    if($argv[1]=="--restart"){restart();exit();}
    if($argv[1]=="--start"){start();exit();}
    if($argv[1]=="--stop"){stop();exit();}
    if($argv[1]=="--syslog"){shell_exec("/usr/sbin/artica-phpfpm-service -dnsfw-syslog");exit();}
    if($argv[1]=="--check-cache"){check_dnscache();exit;}
    if($argv[1]=="--dnsdist-timeout"){exit(0);}
    if($argv[1]=="--template7"){template_7($argv[2]);exit;}
    if($argv[1]=="--template8"){template_8($argv[2]);exit;}
    if($argv[1]=="--template9"){template_9($argv[2]);exit;}
    if($argv[1]=="--template10"){template_10($argv[2]);exit;}
    if($argv[1]=="--wizard-setup"){wizard_setup();exit;}
    if($argv[1]=="--safesearch"){SafeSearch();exit;}

}



function PID_NUM(){

    $unix=new unix();
    $pid=$unix->PIDOF_PATTERN("dnsdist -C \/etc\/");
    if($pid>0){return $pid;}
    return $unix->PIDOF_PATTERN("/bin/dnsdist");

}
function _out($text){
    echo "Service.......: ".date("H:i:s")." [INIT]: DNS Firewall $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("dnsdist", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function stop(){
    shell_exec("/usr/sbin/artica-phpfpm-service -stop-dnsfw -debug");
    return true;
}

function reload(){

    build();

}
function _out_dnscache($text):bool{
    $LOG_SEV = LOG_INFO;
    _out_monit($text);
    if (!function_exists("openlog")) {return false;}
    openlog("dnscache", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function _out_monit($text):bool{
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("monit", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function targeted_server():string{
    $ranges[]="play.google.com";
    $ranges[]="www.google.com";
    $ranges[]="ogs.google.com";
    $ranges[]="www.ibm.com";
    $ranges[]="www.firefox.com";
    $ranges[]="www.microsoft.com";
    $ranges[]="www.defense.gov";
    $ranges[]="www.nyc.gov";
    $ranges[]="ec.europa.eu";
    $ranges[]="www.icj-cij.org";
    $ranges[]="safebrowsing.googleapis.com";
    $rand=rand(0,count($ranges)-1);
    return strval($ranges[$rand]);

}


function targeted_dnsdist():string{
    $ranges=array();
    $DnsDistWatchdogHosts=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsDistWatchdogHosts"));
    if($DnsDistWatchdogHosts==null){
        return targeted_server();
    }
    $second=array();
    $tb=explode(",",$DnsDistWatchdogHosts);
    foreach ($tb as $line){
        $line=trim(strtolower($line));
        if($line==null){continue;}
        $second[$line]=true;
    }
    
    if(count($second)==0){return targeted_server();}
    
    foreach ($second as $host=>$null){
        $ranges[]=$host;
    }
    $rand=rand(0,count($ranges)-1);
    return strval($ranges[$rand]);
}

function check_dnscache(){
    $unix=new unix();
    $dig=$unix->find_program("dig");
    if(!is_file($dig)){exit(0);}
    if(!is_file("/etc/init.d/dnscache")) {
        exit(0);
    }
    $host = targeted_server();
    $cmd = "$dig @127.0.0.55 $host +nocomments +noquestion +noauthority +noadditional +nostats +time=2 +tries=1 +trace 2>&1";
    exec($cmd, $results);

    if(DIGRisError($results,$host)){
        _out_dnscache("Testing 127.0.0.55 in UDP mode failed");
        exit(1);
    }

    exit(0);

}
function DIGRisError($results,$HostTested):bool{

    foreach ($results as $line) {
        if (preg_match("#connection timed out;#", $line, $re)) {
            _out_monit("[warning] $line on $HostTested");
            return true;
        }
        if (preg_match("#no servers could be reached#", $line, $re)) {
            _out_monit("[warning] $line on $HostTested");
            return true;
        }
    }
    
    return false;
}

function check_dnsdist_timeout(){
    $GLOBALS["MONIT"]=false;
    $LAST_DNSDIST_ERROR=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LAST_DNSDIST_ERROR"));
    squid_admin_mysql(1,"{restarting} DNS Firewall by the watchdog ( see report )",
        @implode("\n",$LAST_DNSDIST_ERROR),__FILE__,__LINE__);
    _out_monit("'APP_DNSDIST_TESTS' Stopping DNS Firewall service");
    stop();
    if(!start()){
        _out_monit("'APP_DNSDIST_TESTS' Restarting DNS Firewall service failed");
        exit(1);
    }
    _out_monit("'APP_DNSDIST_TESTS' Restarting DNS Firewall service success");
    exit(0);

}



function start():bool{
    $unix=new unix();
    $unix->ReplicatePHPFMPService();
    $Masterbin=$unix->find_program("dnsdist");
    $EnableDNSDist=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDNSDist"));


    if(!is_file($Masterbin)){
        echo "Starting......: ".date("H:i:s")." [INIT]: DNS Firewall, not installed\n";
        return false;
    }
    if($EnableDNSDist==0){
        echo "Starting......: ".date("H:i:s")." [INIT]: DNS Firewall, not enabled see (EnableDNSDist)\n";
        if(is_file("/etc/monit/conf.d/APP_DNSDIST.monitrc")){
            @unlink("/etc/monit/conf.d/APP_DNSDIST.monitrc");
            $unix->MONIT_RELOAD();
        }
        return false;
    }

    shell_exec("/usr/sbin/artica-phpfpm-service -start-dnsfw -debug");
    return true;

}
function build_progress($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"dnsdist.install");
    return true;
}
function build_progress_restart($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"dnsdist.restart");
    return true;
}
function restart(){
    $unix=new unix();
    $unix->ReplicatePHPFMPService();

    squid_admin_mysql(1,"{APP_DNSDIST}: {restarting_service}",null,__FILE__,__LINE__);
    build_progress_restart("{stopping} {APP_DNSDIST}",10);
    stop();
    build_progress_restart("{configuring} {APP_DNSDIST}",50);
    build();
    build_progress_restart("{starting} {APP_DNSDIST}",70);
    if(!start(true)){
        build_progress_restart("{starting} {APP_DNSDIST} {failed}",110);
        return;
    }
    build_progress_restart("{starting} {APP_DNSDIST} {success}",100);
}
function destroy_log():bool{
    return true;
}





function DNSDIST_VERSION():string{
    $unix=new unix();
    if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
    $dnsdist=$unix->find_program("dnsdist");
    exec("$dnsdist --version 2>&1",$results);
    foreach ($results as $index=>$line){
        if(preg_match("#^(dnsdist)\s+([0-9\.]+)#", trim($line),$re)){
            $GLOBALS[__FUNCTION__]=$re[2];
            if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Found '{$re[2]}'\n";}
            return $re[2];
        }
        if($GLOBALS["VERBOSE"]){echo "[".__FUNCTION__."]: Not Found '$line'\n";}
    }
    return "1.0.0";
}




function BUILD_ACLS_SAFESEARCH($ID,$safe_settings,$firstuuid,$AllSectorsRules):string{
    $unix=new unix();
    $unix->framework_exec("exec.dnscache.php --safesearchs");
    $f=array();
    $selectorSrc="selector$ID";
    $RuleID=$ID;
    $uuids[$firstuuid]=true;

    $EnableGoogleSafeSearch=intval($safe_settings["EnableGoogleSafeSearch"]);
    $EnableBraveSafeSearch=intval($safe_settings["EnableBraveSafeSearch"]);
    $EnableDuckduckgoSafeSearch=intval($safe_settings["EnableDuckduckgoSafeSearch"]);
    $EnableYandexSafeSearch=intval($safe_settings["EnableYandexSafeSearch"]);
    $EnablePixabaySafeSearch=intval($safe_settings["EnablePixabaySafeSearch"]);
    $EnableQwantSafeSearch=intval($safe_settings["EnableQwantSafeSearch"]);
    $EnableBingSafeSearch=intval($safe_settings["EnableBingSafeSearch"]);
    $EnableYoutubeSafeSearch=intval($safe_settings["EnableYoutubeSafeSearch"]);
    $EnbaleYoutubeModerate=intval($safe_settings["EnbaleYoutubeModerate"]);

    $SafeApiQwantCom = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeApiQwantCom"));
	$ForceSafeSearchGoogle = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ForceSafeSearchGoogle"));
	$SafeDuckduckgo = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeDuckduckgo"));
	$StrictBingCom = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("StrictBingCom"));
	$FamilySearchYandexCom = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FamilySearchYandexCom"));
	$SafesearchBraveCom = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafesearchBraveCom"));
	$SafesearchPixabayCom = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafesearchPixabayCom"));
	$RestrictYoutubeCom = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestrictYoutubeCom"));
	$RestrictModerateYoutubeCom = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RestrictModerateYoutubeCom"));

    if ($EnableGoogleSafeSearch == 1) {
        if($ForceSafeSearchGoogle==null){$ForceSafeSearchGoogle="216.239.38.120";}
        $NewSelector="GoogleSafe$selectorSrc";
        $f[]=_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(GoogleDom) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector, SpoofAction('$ForceSafeSearchGoogle'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";
        $firstuuid=null;
    }

    if ($EnableQwantSafeSearch == 1) {
        if($SafeApiQwantCom==null){$SafeApiQwantCom="194.187.168.114";}
        _out("SafeSearch for Qwant: $SafeApiQwantCom");
        if($firstuuid==null){$firstuuid=gettuid();}
        $uuids[$firstuuid]=true;
        $NewSelector="Qwant$selectorSrc";
        $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(QwantDom) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector, SpoofAction('$SafeApiQwantCom'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";
    }
    if ($EnableDuckduckgoSafeSearch == 1) {
        //
        if($SafeDuckduckgo==null){$SafeDuckduckgo="52.142.126.100";}
        if($firstuuid==null){$firstuuid=gettuid();}
        $uuids[$firstuuid]=true;
        $NewSelector="DuckDuck$selectorSrc";
        $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(DuckDuckDom) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector,SpoofAction('$SafeDuckduckgo'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";
    }

    if ($EnableBingSafeSearch == 1) {
            if($StrictBingCom==null){$StrictBingCom="204.79.197.220";}
            $NewSelector="BingBing$selectorSrc";
            if($firstuuid==null){$firstuuid=gettuid();}
            $uuids[$firstuuid]=true;
            $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
            $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(BingBingDom) }";
            $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
            $f[] = "addAction($NewSelector,SpoofAction('$StrictBingCom'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";


            $NewSelector="BingBing2$selectorSrc";
            if($firstuuid==null){$firstuuid=gettuid();}
            $uuids[$firstuuid]=true;
            $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
            $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(BingBingDom2) }";
            $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
            $f[] = "addAction($NewSelector,SpoofAction('127.0.0.1'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";

    }


    if($EnableYandexSafeSearch==1){
        if($FamilySearchYandexCom ==null) {$FamilySearchYandexCom = "213.180.204.242";}
        $NewSelector="Yandex$selectorSrc";
        if($firstuuid==null){$firstuuid=gettuid();}
        $uuids[$firstuuid]=true;
        $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(YandexDom) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector,SpoofAction('$FamilySearchYandexCom'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";
    }


    if($EnableBraveSafeSearch==1){
        if($SafesearchBraveCom==null) {
            $SafesearchBraveCom = "99.86.91.105";
        }


        $NewSelector="Brave$selectorSrc";
        if($firstuuid==null){$firstuuid=gettuid();}
        $uuids[$firstuuid]=true;
        $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(BraveDom) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector,SpoofAction({'$SafesearchBraveCom','99.86.91.125','99.86.91.59','99.86.91.70'}),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";

        if($firstuuid==null){$firstuuid=gettuid();}
        $NewSelector="BraveCDN$selectorSrc";
        $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(BraveCDN) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector,SpoofAction({'108.138.246.43','108.138.246.96','108.138.246.113','108.138.246.7'}),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";
    }
    if($EnablePixabaySafeSearch==1){
        if($SafesearchPixabayCom==null) {$SafesearchPixabayCom = "172.64.150.12";}
        if($firstuuid==null){$firstuuid=gettuid();}
        $uuids[$firstuuid]=true;
        $NewSelector="Pixabay$selectorSrc";
        $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(PixabayDOM) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector,SpoofAction('$SafesearchPixabayCom'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";
    }

    if ($RestrictYoutubeCom ==null){
        $RestrictYoutubeCom = "216.239.38.120";
    }
    if ($RestrictModerateYoutubeCom ==null){
        $RestrictModerateYoutubeCom = "216.239.38.119";
    }

    $ASYOUTUBE = false;
    if ($EnbaleYoutubeModerate == 1) {
        $ASYOUTUBE = true;
    }
    if ($EnableYoutubeSafeSearch == 1) {
        $ASYOUTUBE = true;
    }


    if ($ASYOUTUBE) {
        if ($EnableYoutubeSafeSearch == 1) {
            $SpoofAction=$RestrictYoutubeCom;
        }
        if ($EnbaleYoutubeModerate == 1) {
            $SpoofAction=$RestrictModerateYoutubeCom;
        }
        if($firstuuid==null){$firstuuid=gettuid();}
        $uuids[$firstuuid]=true;
        $NewSelector="YoutubeSafe$selectorSrc";
        $f[] =_dupSelectors($AllSectorsRules,$selectorSrc,$NewSelector);
        $f[] = "$NewSelector = AndRule{ $NewSelector, SuffixMatchNodeRule(YoutubeDOM) }";
        $f[] = "addAction($NewSelector, LuaAction(LogRule$ID) )";
        $f[] = "addAction($NewSelector,SpoofAction('$SpoofAction'),{name=\"rule-$ID\",uuid=\"$firstuuid\"})";

        $f[] = "";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    foreach ($uuids as $uuid=>$none){
        $zuid[]=$uuid;

    }
    $uuidm=@implode(",",$zuid);
    $q->QUERY_SQL("UPDATE dnsdist_rules set uuids='$uuidm' WHERE ID=$ID");
    if(!is_dir("/etc/dnsdist/rules.$RuleID")){
        @mkdir("/etc/dnsdist/rules.$RuleID",0755,true);
    }
    @file_put_contents("/etc/dnsdist/rules.$RuleID/SafeSearchs.conf",@implode("\n",$f));
    _out("Building SafeSearchs [/etc/dnsdist/rules.$RuleID/SafeSearchs.conf] [OK]");
    return "includeDirectory(\"/etc/dnsdist/rules.$RuleID\")";
}
function _dupSelectors($AllSectorsRules,$oldseclector,$newselector):string{
    $f=array();
    foreach ($AllSectorsRules as $line){
        $line=str_replace($oldseclector,$newselector,$line);
        $f[]="$newselector = $line";
    }
    return @implode("\n",$f);
}


function NewServerDOHParseConfig($URI):array{
    $array=array();
    $parse=parse_url($URI);

    $path="/dns-query";
    $host=$parse["host"];
    $proto=$parse["scheme"];



    $array["tls"]=null;

    if(isset($parse["port"])){$port=intval($parse["port"]);}
    if($port==0){
        if($proto=="https"){
            $array["tls"]="openssl";
            $port=443;
        }

        if($proto=="http"){
            $port=80;
        }
    }
    if(isset($parse["path"])){$path=$parse["path"];}
    if(isset($parse["query"])){$path="$path{$parse["query"]}";}
    if(preg_match("#^(.+?):([0-9]+)#",$host,$re)){
        $host=$re[1];
        $port=$re[2];
    }

    $array["address"]="$host:$port";
    $array["dohPath"]=$path;
    return $array;

}

function newServerDOH($server,$name,$OutboundInterface_text,$DNSDistQpsRule,$checks):string{
    $Hostname=null;
    $URI=null;
    if(strpos($server,"|")>0){
        $tb=explode("|",$server);
        $Hostname=$tb[1];
        $URI=$tb[0];
    }
    if($URI==null){
        $URI=$server;
    }
    $array=NewServerDOHParseConfig($URI);
    $LOG[]="-- newServerDOH($server...";
    $address=$array["address"];
    $dohPath=$array["dohPath"];
    $tls=$array["tls"];

    if($address==null){
        return "";
    }
    $CF["address"]=$address;
    if($tls<>null){
        $CF["tls"]=$tls;
    }
    if($Hostname<>null){
        $CF["subjectName"]=$Hostname;
    }
    if($dohPath<>null){
        $CF["dohPath"]=$dohPath;
    }

    $CF["validateCertificates"]="bool:true";
    $CF["useClientSubnet"]="bool:false";
    $CF["name"]=$name;
    $CF["pool"]="defaults";

    if(preg_match("#source=\"(.+)\"#",$OutboundInterface_text,$re)){
        $CF["source"]=$re[1];
    }

    
    
    $f=array();
    foreach ($CF as $key=>$val){
        if(preg_match("#^bool:(.+)#",$val,$re)){
            $f[]="$key=$re[1]";
            continue;
        }
        $f[]="$key=\"$val\"";
    }

    return @implode("\n",$LOG)."\nnewServer({".@implode(",",$f)."$DNSDistQpsRule$checks})";


}
function checksDefaults():string{

    $DNSDIST_VERSION=DNSDIST_VERSION();
    $vers=explode(".",$DNSDIST_VERSION);
    $vMajor=intval($vers[0]);
    $vMinor=intval($vers[1]);
    $LAZY=false;

    if($vMajor==1){
        if($vMinor > 7 ){ $LAZY=true;}
    }

    $DNSDistCheckName=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckName"));
    $DNSDistCheckInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistMaxCheckFailures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistMaxCheckFailures"));
    $DNSDistCheckTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckTimeout"));
    if(trim($DNSDistCheckName)==null){$DNSDistCheckName="a.root-servers.net";}
    if($DNSDistCheckInterval==0){$DNSDistCheckInterval=1;}
    if($DNSDistMaxCheckFailures==0){$DNSDistMaxCheckFailures=3;}
    if($DNSDistCheckTimeout<3){$DNSDistCheckTimeout=3;}

    if(!preg_match("#(.+?)\.$#",$DNSDistCheckName)){$DNSDistCheckName=$DNSDistCheckName.".";}
    $checks=",checkName=\"$DNSDistCheckName\",checkInterval=$DNSDistCheckInterval,maxCheckFailures=$DNSDistMaxCheckFailures,checkTimeout=$DNSDistCheckTimeout";
    if(!$LAZY){return $checks;}

    $DNSDistCheckTimeout=$DNSDistCheckTimeout*1000;

     $f[]="healthCheckMode='lazy'";
     $f[]="checkInterval=$DNSDistCheckInterval";
     $f[]="lazyHealthCheckFailedInterval=15";
     $f[]="rise=2";
     $f[]="maxCheckFailures=$DNSDistMaxCheckFailures";
     $f[]="checkTimeout=$DNSDistCheckTimeout";
     $f[]="lazyHealthCheckThreshold=30";
     $f[]="lazyHealthCheckSampleSize=100";
     $f[]="lazyHealthCheckMinSampleCount=10";
     $f[]="lazyHealthCheckMode='TimeoutOnly'";
    return ",".@implode(",",$f);


}
function checksDefaultsBackends():array{
    $BACKENDS=array();
    $resolv=new resolv_conf();
    if(!is_file("/etc/artica-postfix/AS_DOCKER_SERVICE")) {
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/system/network/resolvapply");
    }
    $EnableCloudflared=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCloudflared"));

    if($EnableCloudflared==1){
        if($resolv->MainArray["DNS1"]=="1.1.1.1"){
            $resolv->MainArray["DNS1"]="127.0.0.45";
        }
        if($resolv->MainArray["DNS2"]=="1.1.1.1"){
            $resolv->MainArray["DNS2"]="127.0.0.45";
        }
        if($resolv->MainArray["DNS3"]=="1.1.1.1"){
            $resolv->MainArray["DNS3"]="127.0.0.45";
        }
        if($resolv->MainArray["DNS4"]=="1.1.1.1"){
            $resolv->MainArray["DNS4"]="127.0.0.45";
        }
    }

    if($resolv->MainArray["DNS1"]<>null){
        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$resolv->MainArray["DNS1"])){
            $BACKENDS[$resolv->MainArray["DNS1"]]=true;

        }

    }
    if($resolv->MainArray["DNS2"]<>null){
        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$resolv->MainArray["DNS2"])){
            $BACKENDS[$resolv->MainArray["DNS2"]]=true;
        }

    }
    if($resolv->MainArray["DNS3"]<>null){
        if(preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#",$resolv->MainArray["DNS3"])) {
            $BACKENDS[$resolv->MainArray["DNS3"]] = true;
        }
    }
    if(isset($resolv->MainArray["DNS4"])){
        if($resolv->MainArray["DNS4"]<>null) {
            if (preg_match("#^[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+$#", $resolv->MainArray["DNS4"])) {
                $BACKENDS[$resolv->MainArray["DNS4"]] = true;
            }
        }
    }
    if(isset($BACKENDS["8.8.8.8"])){
        if(!isset($BACKENDS["8.8.4.4"])){
            $BACKENDS["8.8.4.4"]=true;
        }
        if(!isset($BACKENDS["https://8.8.8.8/dns-query|dns.google"])){
            $BACKENDS["https://8.8.8.8/dns-query|dns.google"]=true;
        }
    }
    if(isset($BACKENDS["1.1.1.1"])){
        if(!isset($BACKENDS["1.1.1.2"])){
            $BACKENDS["1.1.1.2"]=true;
        }
        $BACKENDS["https://104.16.249.249/dns-query|cloudflare-dns.com"]=true;
    }
    return $BACKENDS;

}

function SO_REUSEPORT():string{

    $DNSDISTReusePort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTReusePort"));
    if($DNSDISTReusePort==1){
        return "true";
    }
    return "false";
}

function build(){
    $GLOBALS["ERRORS"]=array();
    $GLOBALS["DSTDOMAINS"]=array();
    $GLOBALS["LB_CACHES"]=array();
    $unix=new unix();
    $CPUNUMBER=$unix->CPU_NUMBER();
    $rm=$unix->find_program("rm");



    $scanDirs=scandir("/etc/dnsdist");
    foreach ($scanDirs as $subdir){
        if($subdir=="."){continue;}
        if($subdir==".."){continue;}
        $tdr="/etc/dnsdist/$subdir";
        if(!preg_match("#rule\.[0-9]+#",$subdir)){continue;}
        if(!is_dir($tdr)){continue;}
        _out("Removing directory [$tdr]");
        shell_exec("$rm -rf $tdr");
    }



    $OLD=false;
    $interfaces=array();
    $GLOBALS["EDNS"]=false;
    $DNSDistQps=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistQps"));
    z_install_lua_syslog();

    $DNSDistQpsRule=null;
    if($DNSDistQps>0){
        $DNSDistQpsRule=",qps=$DNSDistQps";
    }

    $hashpassword="\$scrypt\$ln=10,p=1,r=8\$i1RTaZzCtsuG0NaCQ8UiHA==\$M5TCdd96U7QPKfH8jIJvEtDSCewXjxXNir8ZmH8RI5Y="; // artica

    $DnsdistAsBalancer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DnsdistAsBalancer"));
    if(!is_dir("/etc/dnsdist/conf.d")){@mkdir("/etc/dnsdist/conf.d",0755,true);}
    if($DnsdistAsBalancer==0) {
        $f[] = "includeDirectory(\"/etc/dnsdist/conf.d\")";
        $f[] = "require \"articaglobals\"";

    }
    shell_exec("/usr/sbin/artica-phpfpm-service -dnsfw-syslog");




    $DNSDIST_VERSION=DNSDIST_VERSION();
    $vers=explode(".",$DNSDIST_VERSION);
    $vMajor=intval($vers[0]);
    $vMinor=intval($vers[1]);
    $LAZY=false;
    $f[] = "-- Version Major:$vMajor, Minor: $vMinor";
    if($vMajor==1){
        if($vMinor < 7 ){ $OLD=true;}
        if($vMinor > 7 ){ $LAZY=true;}
    }

    $OutboundInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnboundOutGoingInterface"));
    $setServFailWhenNoServer=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("setServFailWhenNoServer"));


    $f[] = "-- Interfaces ".count($interfaces);
    $SO_REUSEPORT=SO_REUSEPORT();
    _out("Interfaces to serve: ".count($interfaces));
    $f[] = "addLocal('127.0.0.1',{reusePort=$SO_REUSEPORT})";
    if(count($interfaces)>0) {
        foreach ($interfaces as $inet => $Ipaddr) {
            if (preg_match("#^127\.0\.0#", $Ipaddr)) {
                continue;
            }
            $f[] = "addLocal('$Ipaddr',{reusePort=$SO_REUSEPORT})";
        }
    }else{
        $net=new networking();
        $ALL_IPS_GET_ARRAY=$net->ALL_IPS_GET_ARRAY();
        unset($ALL_IPS_GET_ARRAY["127.0.0.1"]);
        foreach ($ALL_IPS_GET_ARRAY as $ipaddr=>$val){
            if(preg_match("#^127\.0#",$ipaddr)){
                continue;
            }
            $f[] = "addLocal('$ipaddr', { reusePort=$SO_REUSEPORT})";
        }


    }
    if($setServFailWhenNoServer==1) {
        $f[] = "setServFailWhenNoServer(true)";
    }








    $f[] = "-- ACLS BEGIN -----------------------------------------";
    $f[]=BUILD_ACLS();
    $f[] = "-- ACLS END -----------------------------------------";
    $f[] = "";
    $f[] = "-- DEFAULT RULE -------------------------------------";
    $q = new lib_sqlite("/home/artica/SQLITE/dns.db");
    $ipclass=new IP();
    $Rules=$q->COUNT_ROWS("pdns_restricts");
    $f[] = "SRCDEFAULT = newNMG()";




    $f[] = "-- $Rules Restrictions";
    if($Rules==0){
        $ACLREST[] = "'0.0.0.0/0'";
        $ACLREST[] = "'::/0'";
    }else {
        $NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES(true);
        foreach ($NETWORK_ALL_INTERFACES as $ipaddr=>$none){
            if($ipaddr==null){continue;}
            if($ipaddr=="0.0.0.0"){continue;}
            if($ipaddr=="127.0.0.1"){continue;}
            $ACLREST[] = "'$ipaddr/32'";
        }


        $sql = "SELECT *  FROM pdns_restricts";
        $results = $q->QUERY_SQL($sql);
        $ACLREST[] = "'127.0.0.0/8'";
        foreach ($results as $index => $ligne) {
            $address=trim($ligne["address"]);
            if($address==null){continue;}

            if (!$ipclass->isIPAddressOrRange($address)) {
                continue;
            }
            if(strpos($address,"/")==0){$address="$address/32";}
            $ACLREST[] = "'". trim($address)."'";
        }
    }

    foreach ($ACLREST as $mask){
        $f[]="SRCDEFAULT:addMask($mask)";
    }
    $f[]="selectorDefault = AndRule{ selectorDefault, NetmaskGroupRule(SRCDEFAULT) }";
    $f[] = "-- DEFAULT RULE END ---------------------------------";


    if($GLOBALS["EDNS"]){
        $f[]="setECSOverride(true)";
        $f[]="setECSSourcePrefixV4(32)";
        $f[]="setECSSourcePrefixV6(128)";
    }


    $f[] = "setACL({".@implode(",",$ACLREST)."})";
    if(count($GLOBALS["ERRORS"])>0){
        $f[]="-- ------------------------------- ERRORS";
        $f[]=@implode("\n",$GLOBALS["ERRORS"]);
    }


    $OutboundInterface_text=null;
    if($OutboundInterface<>null){
        $OutboundInterface_text=", source=\"$OutboundInterface\"";
    }


    $INTERF=$unix->NETWORK_ALL_INTERFACES(true);
if($OLD) {
    $f[] = "-- OLD server for version $vMajor.$vMinor -- --";
    $f[] = "webserver(\"127.0.0.1:5600\",'','')";
}else{
    foreach ($INTERF as $ip=>$none){
        $acls[]=$ip;
    }
    $aclsWeb=$acls;
    $aclsWeb[]="127.0.0.1";
    $acl_text=@implode(",",$acls);
    $acl_webtext=@implode(",",$aclsWeb);
    $f[]="webserver(\"127.0.0.1:5600\")";
    $f[]="setKey(\"uPDd2yOtg16DT8r71fZ5BNOwTuuWAGNtrcv4g8ovsYE=\")";
    $f[]="setWebserverConfig({password=\"\",apiKey=\"$hashpassword\",acl=\"$acl_webtext\" })";
}


    $checks=checksDefaults();
    $defaultBackends=checksDefaultsBackends();

    $DNSDistBlockMalware=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistBlockMalware"));
    $f[]=DynBlockRules();
    $f[]="controlSocket('127.0.0.1:3199')";
    $f[]="setKey(\"uPDd2yOtg16DT8r71fZ5BNOwTuuWAGNtrcv4g8ovsYE=\")";
    $f[]="setConsoleACL('0.0.0.0/0')";


    $sql="SELECT * FROM pdns_fwzones";
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");

    $results=$q->QUERY_SQL($sql);
    $ALREADYZONESET=array();
    foreach ($results as $index=>$ligne){
        $hostname=$ligne["hostname"];
        if(isset($GLOBALS["DSTDOMAINS"][$hostname])){continue;}
        $port=$ligne["port"];
        $zone=$ligne["zone"];
        $id=$ligne["ID"];
        if(strlen(trim($zone))==0){
            continue;
        }
        $GLOBALS["DSTDOMAINS"][$hostname]=true;
        $zone=str_replace("%20","",$zone);
        if(isset($ALREADYZONESET[$zone])){
            continue;
        }
        $ALREADYZONESET[$zone]=true;

        if($zone=="*"){
            $f[]="newServer({address=\"$hostname:$port\", useClientSubnet=false, name=\"fwdns$id\"{$OutboundInterface_text}, pool=\"defaults\"$DNSDistQpsRule$checks}) ";
            continue;
        }

        $f[]="newServer({address=\"$hostname:$port\", useClientSubnet=false, name=\"fwdns$id\"{$OutboundInterface_text}, pool=\"zone$id\"$DNSDistQpsRule$checks})";
        $f[]="addAction({'$zone', '$zone.'}, PoolAction(\"zone$id\"));";
    }
    $dnsCC=0;
    foreach ($defaultBackends as $backend=>$none){
        if(isset($INTERF[$backend])) {continue;}
        $dnsCC++;
        if(preg_match("#^https#",$backend)){
            $f[]=newServerDOH($backend,"dns$dnsCC",$OutboundInterface_text,$DNSDistQpsRule,$checks);
            continue;
        }

        $f[] = "newServer({address=\"$backend:53\", useClientSubnet=false, name=\"dns$dnsCC\"{$OutboundInterface_text}, pool=\"defaults\"$DNSDistQpsRule$checks}) ";

    }


    $DNSDistSetServerPolicy=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistSetServerPolicy"));
    if($DNSDistSetServerPolicy==null){$DNSDistSetServerPolicy="leastOutstanding";}


    $f[]="setServerPolicy($DNSDistSetServerPolicy)";

    $resolv=new resolv_conf();
    $f[]=DEFAULT_DOMAINS($resolv);


    $UnBoundCacheSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheSize"));
    $UnBoundCacheMinTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMinTTL"));
    $UnBoundCacheMAXTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheMAXTTL"));
    $UnBoundCacheNEGTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UnBoundCacheNEGTTL"));


    if($UnBoundCacheMinTTL==0){$UnBoundCacheMinTTL=3600;}
    if($UnBoundCacheMAXTTL==0){$UnBoundCacheMAXTTL=172800;}
    if($UnBoundCacheNEGTTL==0){$UnBoundCacheNEGTTL=3600;}

    if($UnBoundCacheMinTTL==-1){$UnBoundCacheMinTTL=0;}
    if($UnBoundCacheMAXTTL==-1){$UnBoundCacheMAXTTL=0;}
    if($UnBoundCacheNEGTTL==-1){$UnBoundCacheNEGTTL=0;}

    if($UnBoundCacheSize==0){$UnBoundCacheSize=100;}

    $DnsDistCacheItem=$UnBoundCacheSize*1024;
    $DnsDistCacheItem=$DnsDistCacheItem*1024;
    $DnsDistCacheItem=round($DnsDistCacheItem/512);


    $MaxQPSIPRule=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MaxQPSIPRule");


    $f[] = "addAction(PoolAvailableRule(\"\"), PoolAction(\"defaults\"))";
    if($MaxQPSIPRule>0) {
        $f[] = "addAction(MaxQPSIPRule($MaxQPSIPRule), DropAction())";
    }
   // $f[]="addResponseAction(AllRule(), LuaResponseAction(ArticaResponse))";

    foreach ($GLOBALS["LB_CACHES"] as $lines){
        $f[]=$lines;
    }




    $f[]="pcdefaults = newPacketCache($DnsDistCacheItem, {maxTTL=$UnBoundCacheMAXTTL, minTTL=$UnBoundCacheMinTTL, temporaryFailureTTL=$UnBoundCacheNEGTTL, staleTTL=60, dontAge=false})";
    $f[]="getPool(\"defaults\"):setCache(pcdefaults)";

    $setStaleCacheEntriesTTL=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("setStaleCacheEntriesTTL"));
    if($setStaleCacheEntriesTTL==0){$setStaleCacheEntriesTTL=16600;}
    $f[]="setStaleCacheEntriesTTL($setStaleCacheEntriesTTL)";
    $f[]="setMaxUDPOutstanding(65535)";
    if($LAZY){
        $f[]="setVerboseHealthChecks(true)";
    }
    $f[]="";

    @file_put_contents("/etc/dnsdist.builded",@implode("\n",$f));
    $results=array();
    exec("/usr/bin/dnsdist -C /etc/dnsdist.builded --check-config 2>&1",$results);
    foreach ($results as $line){
        _out("Starting: Checking configuration: $line");
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^Fatal error#i",$line)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSDIST_CONFIG_FAILED",$line);
            return false;
        }
        if(preg_match("#^Fatal Lua error#i",$line)) {
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSDIST_CONFIG_FAILED", $line);
            return false;
        }

        if(preg_match("#Configuration\s+.*?\s+OK#i",$line)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSDIST_CONFIG_FAILED","");
            @file_put_contents("/etc/dnsdist.conf",@implode("\n",$f));
            return true;
        }
    }

    @file_put_contents("/etc/dnsdist.conf",@implode("\n",$f));
    return true;

}





function DynBlockRules():string{

    $DNSDISTDynamicBlocks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicBlocks"));
    if($DNSDISTDynamicBlocks==0){
        return "";
    }
    $DNSDISTDynamicMaxReq=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicMaxReq"));
    $DNSDISTDynamicMaxSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicMaxSec"));
    $DNSDISTDynamicBlockSec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicBlockSec"));
    $DNSDISTDynamicWhite=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDISTDynamicWhite"));

    if($DNSDISTDynamicWhite==null){$DNSDISTDynamicWhite="127.0.0.0/8,192.168.0.0/16,10.0.0.0/16";}
    if($DNSDISTDynamicMaxSec==0){$DNSDISTDynamicMaxSec=20;}
    if($DNSDISTDynamicMaxReq==0){$DNSDISTDynamicMaxReq=50;}
    if($DNSDISTDynamicBlockSec==0){$DNSDISTDynamicBlockSec=60;}
    if($DNSDISTDynamicMaxSec<5){$DNSDISTDynamicMaxSec=10;}
    if($DNSDISTDynamicMaxSec<5){$DNSDISTDynamicMaxSec=10;}
    if($DNSDISTDynamicBlockSec<2){$DNSDISTDynamicBlockSec=2;}

    $maxreq=$DNSDISTDynamicMaxReq;
    $maxs=$DNSDISTDynamicMaxSec;
    $block=$DNSDISTDynamicBlockSec;
    $f[] = "local dbr = dynBlockRulesGroup()";
    $f[] = "dbr:setQueryRate($maxreq, $maxs, \"Exceeded query rate\", $block)";
    $f[] = "dbr:setRCodeRate(DNSRCode.NXDOMAIN, $maxreq, $maxs, \"Exceeded NXD rate\", $block)";
    $f[] = "dbr:setRCodeRate(DNSRCode.SERVFAIL, $maxreq, $maxs, \"Exceeded ServFail rate\", $block)";
    $f[] = "dbr:setQTypeRate(DNSQType.ANY, $maxreq, $maxs, \"Exceeded ANY rate\", $block)";
    $f[] = "dbr:setQTypeRate(DNSQType.TXT, $maxreq, $maxs, \"Exceeded TXT rate\", $block)";
    $f[] = "dbr:setQTypeRate(DNSQType.CNAME, $maxreq, $maxs, \"Exceeded CNAME rate\", $block)";
    $f[] = "dbr:setQTypeRate(DNSQType.MX, $maxreq, $maxs, \"Exceeded MX rate\", $block)";
    $f[] = "dbr:setQTypeRate(DNSQType.NS, $maxreq, $maxs, \"Exceeded NS rate\", $block)";
    $f[] = "dbr:setQTypeRate(DNSQType.SOA, $maxreq, $maxs, \"Exceeded SOA rate\", $block)";
    $f[] = "dbr:setQTypeRate(DNSQType.A, $maxreq, $maxs, \"Exceeded A rate\", $block)";

    $excludeRange[]="\"127.0.0.0/8\"";
    $tb=explode(",",$DNSDISTDynamicWhite);
    if(is_array($tb)) {
        foreach ($tb as $range) {
            $range = trim($range);
            if($range==null){continue;}
            if(preg_match("#^127\.0\.0#",$range)){continue;}
            if (!IP::IsACDIROrIsValid($range)){continue;}
            $excludeRange[]="\"$range\"";
        }
    }
    $f[]="dbr:excludeRange({".@implode(',',$excludeRange)."})";
    $f[]="-- check dynamic rule every second";
    $f[]="function maintenance()";
    $f[]="\tdbr:apply()";
    $f[]="end";
    return @implode("\n",$f);

}



function DEFAULT_DOMAINS($resolv){
    if($resolv->MainArray["DOMAINS1"]<>null){$tt[]=$resolv->MainArray["DOMAINS1"];}
    if($resolv->MainArray["DOMAINS2"]<>null){$tt[]=$resolv->MainArray["DOMAINS2"];}
    if($resolv->MainArray["DOMAINS3"]<>null){$tt[]=$resolv->MainArray["DOMAINS3"];}
    $tt=array();
    $f[]="-- DEFAULT DOMAINS DEFINED IN DNS GLOBAL PARAMETERS";

    $uuid=gettuid();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSDIST_DEFAULT_UUID",$uuid);
    $ActionsNets[]="addAction(selectorDefault, LuaAction(LogRule0) )";
    $ActionsNets[]="addAction(selectorDefault, PoolAction(\"defaults\"),{name=\"rule_default\",uuid=\"$uuid\"})";

    $x=array();
    $DSTDOMDEFAULT_COUNT=0;
    foreach ($tt as $domain){
        if(isset($GLOBALS["DSTDOMAINS"][$domain])){
            $x[]="-- [ $domain ] is already defined in ACLs or other";
            continue;}
        $DSTDOMDEFAULT_COUNT++;
        $x[]="DSTDOMDEFAULT:add(newDNSName(\"$domain.\"))";

    }
    if($DSTDOMDEFAULT_COUNT>0) {
        $f[] = "DSTDOMDEFAULT = newSuffixMatchNode()";
        $f[] = "selectorDefault = OrRule{ selectorDefault, SuffixMatchNodeRule(DSTDOMDEFAULT) }";
    }
    $hostname="dnsfw";
    $f[]="rl = newRemoteLogger(\"127.0.0.1:4897\")";
    $f[]="addAction(AllRule(),RemoteLogAction(rl, nil, {serverID=\"$hostname\"}))";
    $f[]="addResponseAction(AllRule(),RemoteLogResponseAction(rl, nil, true, {serverID=\"$hostname\"}))";
    $f[]="addCacheHitResponseAction(AllRule(), RemoteLogResponseAction(rl, nil, true, {serverID=\"$hostname\"}))";
    $f[]="";

    $f[]="mylogaction=LogAction(\"/var/log/dnsdist-service.log\",false, true, false, false, true)";

    $f[] = "addAction(AllRule(), mylogaction)";
    $f[] ="mylogaction:reload()";
    $f[]=@implode("\n",$ActionsNets);
    return @implode("\n",$f);


}

function BUILD_ACLS_QTYPE($ID){
    $GLOBALS["BUILD_ACLS_QTYPE"]=array();

    $qrt=qtr_array();

    foreach ($qrt as $qt){
        $AVAIL[$qt]=true;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='dnsquerytype'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";

    $results=$q->QUERY_SQL($sql);

    if(count($results)==0){
        $GLOBALS["BUILD_ACLS_QTYPE"][]="-- no dnsquerytype objects for rule $ID";
        return false;
    }

    $fcatz=array();
    foreach ($results as $index=>$ligne){
        $gpid=$ligne["gpid"];
        $results2=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid");
        if(count($results2)==0){
            $GLOBALS["BUILD_ACLS_QTYPE"][]="-- dnsquerytype no item for group id:$gpid - webfilters_sqitems";
            continue;
        }
        foreach ($results2 as $index2=>$ligne2){
            $pattern=trim($ligne2["pattern"]);
            if($pattern==null){continue;}
            $pattern=strtoupper($pattern);
            if(!isset($AVAIL[$pattern])){
                $GLOBALS["BUILD_ACLS_QTYPE"][]="-- $pattern not supported";
                continue;
            }
            $fcatz[]=$pattern;
        }
    }

    if(count($fcatz)==0){
        $GLOBALS["BUILD_ACLS_QTYPE"][]="-- dnsquerytype no supported item for group id:$gpid";
        return false;
    }

    $DNSDistDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDebug"));
    $f[]="function Qtypefilter$ID(dq)";
    $f[]="\tlocal qname = dq.qname:toString():lower()";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";

    foreach ($fcatz as $DNSQType){
        $f[]="\tif(dq.qtype==DNSQType.$DNSQType) then";
        if($DNSDistDebug==1){
            $f[]="\t\tWriteventQtype$ID(qname,remote_addr,\"$DNSQType matches!\")";
        }
        $f[]="\t\treturn true";
        $f[]="\tend";
    }
    if($DNSDistDebug==1){
        $f[]="\tWriteventQtype$ID(qname,remote_addr,dq.qtype ..\"Nothing matches\")";
        }
    $f[]="\treturn false";
    $f[]="end\n\n";
    $f[]="function WriteventQtype$ID(domain,ipsrc,text)";
    $f[]="\tlocal msg = string.format(\"rule:$ID:Qtype [%s][%s] %s\", ipsrc, domain,text)";
    $f[]="\tinfolog(msg)";
    $f[]="end\n\n";

    @file_put_contents("/etc/dnsdist/conf.d/artica-qtype-$ID.conf",@implode("\n",$f));
    $GLOBALS["BUILD_ACLS_QTYPE"][]="-- dnsquerytype Success";
    return true;

}

function BUILD_ACLS_WEBFILTER($ID){
    $GLOBALS["BUILD_ACLS_WEBFILTER"]=array();
    if(!$GLOBALS["CLASS_SOCKETS"]->DNSDIST_WEBFILTER_ENABLED()){
        $GLOBALS["BUILD_ACLS_WEBFILTER"][]="-- No The Shields service defined";
        return false;
    }
    $TheShieldsServiceEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsServiceEnabled"));
    $DNSDistDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDebug"));


    if($TheShieldsServiceEnabled==1){
        $TheShieldsPORT = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsPORT"));
        if($TheShieldsPORT==0){$TheShieldsPORT=2004;}
        $TheShieldaddr="127.0.0.1";
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='webfilter'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);


    if(count($results)==0){
        $GLOBALS["BUILD_ACLS_WEBFILTER"][]="-- no webfilter objects for rule $ID";
        return false;
    }
    $f[]="MemWebfilter$ID = {}";
    $f[]="function Webfilter$ID(dq)";
    $f[]="\tlocal qname = dq.qname:toString():lower()";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tlocal memitem=remote_addr .. qname";
    $f[]="\tif qname ==\".\" then return false end";
    $f[]="\tif remote_addr ==\"127.0.0.1\" then return false end";
    $f[]="\tif string.find(qname, \"http:\") then return false end";

    $f[]="\tif MemWebfilter{$ID}[memitem] then";
    $f[]="\t\tif MemWebfilter{$ID}[memitem]==\"TRUE\" then return true end";
    $f[]="\t\tif MemWebfilter{$ID}[memitem]==\"FALSE\" then return false end";
    $f[]="\tend";

    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));
    $domains[]="filter.artica.center";
    $domains[]="cguardprotect.net";
    if($RemoteCategoriesServicesRemote==1) {
        if (strlen($RemoteCategoriesServicesDomain) > 3) {
            $domains[] = $RemoteCategoriesServicesDomain;
        }
    }

    foreach ($domains as $domain){
        $f[]="\tif string.find(qname, \"$domain\") then";
        $f[]="\t\t\treturn false";
        $f[]="\tend";
    }



    $f[]="\tlocal http = require(\"socket.http\")";
    $f[]="\tlocal uri = \"http://$TheShieldaddr:$TheShieldsPORT/filtering/\" .. qname ..\"/\" .. remote_addr";
    $f[]="\tlocal body, code, headers, status = http.request(uri)";
    if($DNSDistDebug==1){
        $f[]="\t\tWriteventWebFilter$ID(qname,remote_addr,uri .. \" report http code \" .. code)";
    }
    $f[]="\tif code~=200 then ";
    $f[]="\t\tWriteventWebFilter$ID(qname,remote_addr,\"http://$TheShieldaddr:$TheShieldsPORT/filtering/\" .. qname ..\"/ failed code:\" .. code)";
    $f[]="\t\treturn false";
    $f[]="\tend";
    $f[]="\tbody=all_trim$ID(body)";
    $f[]="\tMemWebfilter{$ID}[memitem]=body";
    $f[]="\tif body == \"TRUE\" then";
    $f[]="\t\treturn true";
    $f[]="\tend";
    $f[]="\treturn false";
    $f[]="end";
    $f[]="";
    $f[]="";
    $f[]="function WriteventWebFilter$ID(domain,ipsrc,text)";
    $f[]="\tlocal msg = string.format(\"rule:$ID:web-filtering [%s][%s] %s\", ipsrc, domain,text)";
    $f[]="\tinfolog(msg)";
    $f[]="end";
    $f[]="";
    $f[]="function all_trim$ID(s)";
    $f[]="\treturn s:match( \"^%s*(.-)%s*$\" )";
    $f[]="end\n";


    @file_put_contents("/etc/dnsdist/conf.d/artica-webfilter-$ID.conf",@implode("\n",$f));
    $GLOBALS["BUILD_ACLS_WEBFILTER"][]="-- webfilter Success";
    return true;


}

function THE_SHIELDS_SERVER_ADDR(){

    $GoShieldServerIP = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Addr"));;
    $GoShieldServerPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Shield_Server_Port"));
    if ($GoShieldServerIP == null) {$GoShieldServerIP = "127.0.0.1";}
    if ($GoShieldServerPort == 0) {$GoShieldServerPort = 3333;}
    return "http://$GoShieldServerIP:$GoShieldServerPort";

}
function BUILD_ACLS_REPUTATION($ID):bool{
    $GLOBALS["BUILD_ACLS_REPUTATION"]=array();
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='reputation'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);
    if(count($results)==0){
        $GLOBALS["BUILD_ACLS_REPUTATION"][]="-- no object reputation for rule $ID";
        return false;
    }

    $Whites=array();
    $Blacks=array();
    $c=0;
    foreach ($results as $index=>$ligne) {
          $repblack = intval($ligne["repblack"]);
        $repwhite = intval($ligne["repwhite"]);
        if ($repblack>0){
            $Blacks[$repblack]=$repblack;
            $c++;
        }
        if ($repwhite>0){
            $Whites[$repwhite]=$repwhite;
            $c++;
        }
    }
    $fBlk[]=0;
    $fWhk[]=0;

    if($c==0) {
        $GLOBALS["BUILD_ACLS_REPUTATION"][] = "-- no reputation rule";
        return false;
    }

    foreach ($Blacks as $ruleid=>$none){
        $fBlk[]=$ruleid;
    }
    foreach ($Whites as $ruleid=>$none){
        $fWhk[]=$ruleid;
    }

    $blackrules=@implode("-",$fBlk);
    $whiterules=@implode("-",$fWhk);
    $f[]="";
    $f[]="function GetSrcRep$ID(dq)";
    $f[]="\tlocal qname = dq.qname:toString():lower()";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tif qname ==\".\" then return false end";
    $f[]="\tif remote_addr ==\"127.0.0.1\" then return false end";
    $ActiveDirectoryRestPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPort"));
    if($ActiveDirectoryRestPort==0){
        $ActiveDirectoryRestPort=9503;
    }

    $f[]="";
    $f[]="";
    $AddrPtrn="http://127.0.0.1:$ActiveDirectoryRestPort";
    $f[]="\tlocal http = require(\"socket.http\")";
    $f[]="\tlocal uri = \"$AddrPtrn/reputation/dns/\" .. remote_addr ..\"/\" .. qname ..\"/$ID/$blackrules/$whiterules\"";
    $f[]="\tlocal body, code, headers, status = http.request(uri)";
    $f[]="\tif code==450 then ";
    $f[]="\t\treturn true";
    $f[]="\tend";
    $f[]="\treturn false";
    $f[]="end";
    $f[]="";
    $f[]="";
    $f[]="";
    $f[]="function WriteventSrcReput$ID(domain,ipsrc,text)";
    $f[]="\tlocal msg = string.format(\"rule:$ID:ipreput [%s][%s] %s\", ipsrc, domain,text)";
    $f[]="\tinfolog(msg)";
    $f[]="end";
    $f[]="";
    $f[]="function all_trim$ID(s)";
    $f[]="\treturn s:match( \"^%s*(.-)%s*$\" )";
    $f[]="end\n";

    @file_put_contents("/etc/dnsdist/conf.d/artica-ipreput-$ID.conf",@implode("\n",$f));
    $GLOBALS["BUILD_ACLS_REPUTATION"][]="-- Success";
    return true;

}
function BUILD_ACLS_GEOIP_SRC($ID):bool{
    $GLOBALS["BUILD_ACLS_GEOIP_SRC"]=array();
    $EnableGeoipUpdate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGeoipUpdate"));

    if($EnableGeoipUpdate==0){
        $GLOBALS["BUILD_ACLS_GEOIP_SRC"][]="-- geoipsrc not active";
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='geoipsrc'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);

    if(count($results)==0){
        $GLOBALS["BUILD_ACLS_GEOIP_SRC"][]="-- no object geoipsrc for rule $ID";
        return false;
    }
    $fcatz=array();
    foreach ($results as $index=>$ligne){
        $gpid=$ligne["gpid"];
        $results2=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid");
        if(count($results2)==0){
            $GLOBALS["BUILD_ACLS_GEOIP_SRC"][]="-- no items for group id:$gpid - webfilters_sqitems";
            continue;
        }
        foreach ($results2 as $index2=>$ligne2){
            $pattern=trim(strtoupper($ligne2["pattern"]));
            if(strlen($pattern)<2){continue;}
            $fcatz[]=$pattern;

        }

    }
    if(count($fcatz)==0) {
        $GLOBALS["BUILD_ACLS_GEOIP_SRC"][] = "-- no countries for group id:$gpid";
        return false;
    }

    $catquery=@implode("-",$fcatz);
    $f[]="MemSrcGeo$ID = {}";
    $f[]="";
    $f[]="";
    $f[]="function GetSrcGeo$ID(dq)";
    $f[]="\tlocal qname = dq.qname:toString():lower()";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tif qname ==\".\" then return false end";
    $f[]="\tif remote_addr ==\"127.0.0.1\" then return false end";
    $ActiveDirectoryRestPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPort"));
    if($ActiveDirectoryRestPort==0){
        $ActiveDirectoryRestPort=9503;
    }

    $f[]="";
    $f[]="";
    $AddrPtrn="http://127.0.0.1:$ActiveDirectoryRestPort";
    $f[]="\tlocal http = require(\"socket.http\")";

    //r.GET("/geoip/dns/{ipaddr}/{domain}/{ruleid}/{countries}", RestDnsFwGeoip)

    $f[]="\tlocal uri = \"$AddrPtrn/geoip/dns/\" .. remote_addr ..\"/\" .. qname ..\"/$ID/$catquery\"";
    $f[]="\tlocal body, code, headers, status = http.request(uri)";
    $f[]="\tif code==450 then ";
    $f[]="\t\treturn true";
    $f[]="\tend";
    $f[]="\treturn false";
    $f[]="end";
    $f[]="";
    $f[]="";
    $f[]="";
    $f[]="function WriteventSrcGeo$ID(domain,ipsrc,text)";
    $f[]="\tlocal msg = string.format(\"rule:$ID:srcgeo [%s][%s] %s\", ipsrc, domain,text)";
    $f[]="\tinfolog(msg)";
    $f[]="end";
    $f[]="";
    $f[]="function all_trim$ID(s)";
    $f[]="\treturn s:match( \"^%s*(.-)%s*$\" )";
    $f[]="end\n";

    @file_put_contents("/etc/dnsdist/conf.d/artica-geoipsrc-$ID.conf",@implode("\n",$f));
    $GLOBALS["BUILD_ACLS_GEOIP_SRC"][]="-- Success";
    return true;

}

function BUILD_ACLS_NETBIOSNAME($ID):bool{

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='netbiosname'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);

    if(count($results)==0){
        return false;
    }

    return true;
}

function BUILD_ACLS_THESHIELDS($ID){
    $DEBUG=false;
    $GLOBALS["BUILD_ACLS_THESHIELDS"]=array();
    if(!$GLOBALS["CLASS_SOCKETS"]->DNSDIST_WEBFILTER_ENABLED()){
        $GLOBALS["BUILD_ACLS_THESHIELDS"][]="-- No The Shields service defined";
        return false;
    }
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='the_shields'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);


    if(count($results)==0){
        $GLOBALS["BUILD_ACLS_THESHIELDS"][]="-- no object the_shields for rule $ID";
        return false;
    }
    $DEBUG=false;
    $DNSDistDebug=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDebug"));
    if($DNSDistDebug==1){$DEBUG=true;}

    $f[]="MemTheShields$ID = {}";
    $f[]="function GetTheShields$ID(dq)";
    $f[]="\tlocal qname = dq.qname:toString():lower()";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tif qname ==\".\" then return false end";
    $f[]="\tif remote_addr ==\"127.0.0.1\" then return false end";

    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));
    $domains[]="filter.artica.center";
    $domains[]="cguardprotect.net";
    if($RemoteCategoriesServicesRemote==1) {
        if (strlen($RemoteCategoriesServicesDomain) > 3) {
            $domains[] = $RemoteCategoriesServicesDomain;
        }
    }

    foreach ($domains as $domain){
        $f[]="\tif string.find(qname, \"$domain\") then";
        $f[]="\t\t\treturn false";
        $f[]="\tend";
    }





    $f[]="\tif MemTheShields{$ID}[qname] then";
    $f[]="\t\tif MemTheShields{$ID}[qname]==\"TRUE\" then";
    $f[]="\t\t\treturn true";
    $f[]="\t\tend";
    $f[]="\t\tif MemTheShields{$ID}[qname]==\"FALSE\" then";
    $f[]="\t\t\treturn false";
    $f[]="\t\tend";
    $f[]="\tend";

    $THE_SHIELDS_SERVER_ADDR=THE_SHIELDS_SERVER_ADDR();
    $f[]="\tlocal http = require(\"socket.http\")";
    $f[]="\tlocal uri = \"$THE_SHIELDS_SERVER_ADDR/theshields/\" .. qname";
    $f[]="\tlocal body, code, headers, status = http.request(uri)";
    $f[]="\tif code~=200 then ";
    $f[]="\t\tWriteventTheShields$ID(qname,remote_addr,\"http $THE_SHIELDS_SERVER_ADDR failed code:\" .. code)";
    $f[]="\tend";
    if($DEBUG) {
        $f[] = "\tWriteventTheShields$ID(qname,remote_addr,\"880 \" .. uri .. \" report [\" .. body ..\"]\")";
    }

    $f[]="\tif body == \"TRUE\" then";
    $f[]="\t\tMemTheShields{$ID}[qname]=\"TRUE\"";
    $f[]="\t\treturn true";
    $f[]="\tend";
    $f[]="\t\tMemTheShields{$ID}[qname]=\"FALSE\"";
    $f[]="\treturn false";
    $f[]="end";
    $f[]="";
    $f[]="";
    $f[]="";
    $f[]="function WriteventTheShields$ID(domain,ipsrc,text)";
    $f[]="\tlocal msg = string.format(\"rule:$ID:theshields [%s][%s] %s\", ipsrc, domain,text)";
    $f[]="\tinfolog(msg)";
    $f[]="end";
    $f[]="";
    $f[]="function all_trim$ID(s)";
    $f[]="\treturn s:match( \"^%s*(.-)%s*$\" )";
    $f[]="end\n";

    @file_put_contents("/etc/dnsdist/conf.d/artica-category-$ID.conf",@implode("\n",$f));
    $GLOBALS["BUILD_ACLS_THESHIELDS"][]="-- Success";
    return true;


}


function SpoofNoDomain($ID,$CnameDomain):bool{
    $rulename="default";
    $f[]="function SpoofNoDomain$ID(dq)";
    $f[]="\tlocal qnamesrc = dq.qname:toString():lower()";
    $f[]="\tqname = string.gsub(qnamesrc, \".localdomain.local\", \"\")";
    $f[]="\tqname = string.gsub(qnamesrc, \".localdomain\", \"\")";

    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tlocal delimiter = \".\"";
    $f[]="\tlocal array = {}";
    $f[]="\tfor value in string.gmatch(qname, \"([^\" .. delimiter .. \"]+)\") do";
   // $f[]="\tinfolog(\"value:\" .. value)";
    $f[]="\t\ttable.insert(array, value)";
    $f[]="\tend";
    $f[]="\tlocal host = array[1]";
    $f[]="\tlocal domain = array[2]";
    $f[]="\tlocal QTypeOK=0";
   // $f[]="\tlocal debug = string.format(\"%s -> host:%s,  Domain:[%s] -> qname:%s\", qnamesrc,host, domain,qname)";
   // $f[]="\tinfolog(debug)";

    $f[]="\tlocal stype=''";
    $f[]="\tif domain ~= nil then";
    $f[]="\t\treturn DNSAction.None";
    $f[]="\tend";

    if($ID>0) {
        $q = new lib_sqlite("/home/artica/SQLITE/acls.db");
        $ligne = $q->mysqli_fetch_array("SELECT rulename FROM dnsdist_rules WHERE ID='$ID'");
        $rulename=$ligne["rulename"];
    }
    $f[]="\tif qnamesrc ==\".\" then";
    $f[]="\t\treturn DNSAction.None";
    $f[]="\tend";

    $f[]="\tif dq.qtype==DNSQType.A then";
    $f[]="\t\tQTypeOK=1";
    $f[]="\tend";

    $f[]="\tif dq.qtype==DNSQType.AAAA then";
    $f[]="\t\tQTypeOK=1";
    $f[]="\tend";

    $f[]="\tif QTypeOK == 0 then";
    $f[]="\t\treturn DNSAction.None";
    $f[]="\tend";

    $qrt=qtr_array();

    foreach ($qrt as $qtype){
        $f[]="\tif dq.qtype==DNSQType.$qtype then stype='$qtype' end";

    }

    $f[]="";
    $f[]="\tlocal finaldom = string.format(\"%s%s\", qname, '$CnameDomain')";
    $f[]="\tlocal text = string.format(\"Spoofed %s to %s [$rulename]:$ID\",qnamesrc,finaldom)";
    $f[]="\tlocal msg = string.format(\"%s %s %s %s\", remote_addr, finaldom,stype,text)";
    $f[]="\tinfolog(msg)";
    $f[]="\treturn DNSAction.Spoof,finaldom";
    $f[]="end\n";
    _out("Build: artica-spoof-$ID.conf [OK]");
    @file_put_contents("/etc/dnsdist/conf.d/artica-spoof-$ID.conf",@implode("\n",$f));
    return true;
}


function LogRuleCategoriesService():bool{
    $rulename="categories-service";

    $f[]="function LogRuleCategoriesService(dq)";
    $f[]="\tlocal qname = dq.qname:toString():lower()";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tlocal stype=''";
    $f[]=la_white_log(0,$rulename);

    $qrt=qtr_array();

    foreach ($qrt as $qtype){
        $f[]="if dq.qtype==DNSQType.$qtype then stype='$qtype' end";

    }



    $f[]="\tlocal text = '[$rulename]:0'";
    $f[]="\tlocal msg = string.format(\"%s %s %s %s\", remote_addr, qname,stype,text)";
    $f[]="\tinfolog(msg)";
    $f[]="\treturn DNSAction.None";
    $f[]="end";
    @file_put_contents("/etc/dnsdist/conf.d/artica-log-$rulename.conf",@implode("\n",$f));
    return true;
}



function LUaFW($ruleid,$fwobject){
    $ActiveDirectoryRestPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryRestPort"));
    if($ActiveDirectoryRestPort==0){
        $ActiveDirectoryRestPort=9503;
    }
    $f[]="function LUaFW{$ruleid}(dq)";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tlocal http = require(\"socket.http\")";
    $f[]="\tlocal ltn12 = require(\"ltn12\")";
    $f[]="\tlocal uri = \"http://127.0.0.1:$ActiveDirectoryRestPort/firewall/item/add/$fwobject/\" .. remote_addr";
    $f[]="\tlocal response_body = {}";
    $f[]="\tlocal body, code, headers, status = http.request{ url = uri, headers = headers, sink = ltn12.sink.table(response_body)}";
    $f[]="\tif code~=200 then ";
    $f[]="\t\tlocal msg=string.format(\"%s failed with code %s\",uri,code)";
    $f[]="\t\tSendToSyslog(9999999,hostname,\"0.0.0.0\",\"firewall\",0,msg)";
    $f[]="\tend";
    $f[]="\treturn DNSAction.None";
    $f[]="end";
    @file_put_contents("/etc/dnsdist/conf.d/artica-fw-$ruleid.conf",@implode("\n",$f));
}








function BUILD_ACLS_CATEGORIES($ID){
    $GLOBALS["BUILD_ACLS_CATEGORIES"]=array();
    if(!$GLOBALS["CLASS_SOCKETS"]->DNSDIST_WEBFILTER_ENABLED()){
        $GLOBALS["BUILD_ACLS_CATEGORIES"][]="-- categories, No The Shields service defined";
        return false;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='categories'
		AND dnsdist_sqacllinks.aclid=$ID
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);


    if(count($results)==0){
        $GLOBALS["BUILD_ACLS_CATEGORIES"][]="-- categories, no objects for rule $ID";
        return false;
    }
    $fcatz=array();
    foreach ($results as $index=>$ligne){
        $gpid=$ligne["gpid"];
        $results2=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid");
        if(count($results2)==0){
            $GLOBALS["BUILD_ACLS_CATEGORIES"][]="-- categories, no items for group id:$gpid - webfilters_sqitems";
            continue;
        }
        foreach ($results2 as $index2=>$ligne2){
            $pattern=intval($ligne2["pattern"]);
            if($pattern==0){continue;}
            $fcatz[]=$pattern;

        }

    }
    if(count($fcatz)==0) {
        $GLOBALS["BUILD_ACLS_CATEGORIES"][] = "-- no categories for group id:$gpid";
        return false;
    }

    $catquery=@implode("-",$fcatz);
    $f[]="MemCategory$ID = {}";
    $f[]="";
    $f[]="";

    $DNSDistDisableCategories=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistDisableCategories"));
    $f[]="function GetCategories$ID(dq)";
    $f[]="\tlocal qname = dq.qname:toString():lower()";
    $f[]="\tlocal remote_addr = dq.remoteaddr:toString()";
    $f[]="\tlocal DNSDistDisableCategories = $DNSDistDisableCategories";
    $f[]="\tif DNSDistDisableCategories==1 then return false end";
    $f[]="\tif qname ==\".\" then return false end";
    $f[]="\tif remote_addr==\"127.0.0.1\" then return false end";
    $f[]="";





    $RemoteCategoriesServicesRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesRemote"));
    $RemoteCategoriesServicesDomain = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RemoteCategoriesServicesDomain"));
    $domains[]="filter.artica.center";
    $domains[]="cguardprotect.net";
    if($RemoteCategoriesServicesRemote==1) {
        if (strlen($RemoteCategoriesServicesDomain) > 3) {
            $domains[] = $RemoteCategoriesServicesDomain;
        }
    }

    foreach ($domains as $domain){
        $f[]="\tif string.find(qname, \"$domain\") then";
        $f[]="\t\t\treturn false";
        $f[]="\tend";
    }


    $f[]="";
    $f[]="\tif MemCategory{$ID}[qname] then";
    $f[]="\t\tif MemCategory{$ID}[qname]==\"TRUE\" then";
    $f[]="\t\t\treturn true";
    $f[]="\t\tend";
    $f[]="\t\tif MemCategory{$ID}[qname]==\"FALSE\" then";
    $f[]="\t\t\treturn false";
    $f[]="\t\tend";
    $f[]="\tend";
    $f[]="";
    $f[]="";

    $THE_SHIELDS_SERVER_ADDR=THE_SHIELDS_SERVER_ADDR();
    $f[]="\tlocal http = require(\"socket.http\")";
    $f[]="\tlocal ltn12 = require(\"ltn12\")";
    $f[]="\tlocal uri = \"$THE_SHIELDS_SERVER_ADDR/category/\" .. qname ..\"/$catquery\"";
    $f[]="\tlocal response_body = {}";
    $f[]="\tlocal headers = {";
    $f[]="\t\t[\"source_ip\"] = remote_addr";
    $f[]="\t}";

    $f[]="\tlocal body, code, headers, status = http.request{ url = uri, headers = headers, sink = ltn12.sink.table(response_body)}";
    $f[]="\tif code~=200 then ";
    $f[]="\t\tWriteventCategories$ID(qname,remote_addr,\"http $THE_SHIELDS_SERVER_ADDR failed code:\" .. code)";
    $f[]="\t\treturn false";
    $f[]="\tend";
    $f[]="";
    $f[]="";
    $f[]="\tif table.concat(response_body) == \"TRUE\" then";
    $f[]="\t\tMemCategory{$ID}[qname]=\"TRUE\"";
    $f[]="\t\treturn true";
    $f[]="\tend";
    $f[]="";
    $f[]="";
    $f[]="\tMemCategory{$ID}[qname]=\"FALSE\"";
    $f[]="\treturn false";
    $f[]="end";
    $f[]="";
    $f[]="";
    $f[]="function WriteventCategories$ID(domain,ipsrc,text)";
    $f[]="\tlocal msg = string.format(\"rule:$ID:categories [%s][%s] %s\", ipsrc, domain,text)";
    $f[]="\tinfolog(msg)";
    $f[]="end";
    $f[]="";
    $f[]="function all_trim$ID(s)";
    $f[]="\treturn s:match( \"^%s*(.-)%s*$\" )";
    $f[]="end\n";

    $f[]="function TableLength$ID(T)";
    $f[]="\tlocal count = 0";
    $f[]="\tfor _ in pairs(T) do count = count + 1 end";
    $f[]="\treturn count";
    $f[]="end\n";


    @file_put_contents("/etc/dnsdist/conf.d/artica-category-$ID.conf",@implode("\n",$f));
    $GLOBALS["BUILD_ACLS_CATEGORIES"][]="-- Success";
    return true;


}


function BUILD_ACLS():string{

    $EnableLocalUfdbCatService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"));
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $FireHolEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("FireHolEnable"));






    $results=$q->QUERY_SQL("SELECT ID,ruletype,rulevalue,rulename FROM dnsdist_rules WHERE enabled=1 ORDER BY zOrder");

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $q->QUERY_SQL("UPDATE dnsdist_rules set uuids='' WHERE ID=$ID");
        $ruletype=$ligne["ruletype"];
        $rulevalue=trim($ligne["rulevalue"]);
        $rulename=utf8_encode($ligne["rulename"]);
        $NODest=false;
        if($ruletype==9){$NODest=true;}
        $AllSectorsRules=array();
        $AddAction=array();
        $SELECTOR=0;
        $f[] = "-- *****************************************************************";
        $f[] = "-- Rule [ $rulename ] Type: $ruletype";
        $fsrc=array();



        if(BUILD_ACLS_NETBIOSNAME($ID)){
            $fsrc[] = "-- Selector for no domain suffix (netbiosname acl)";
            $SELECTOR++;
            SpoofNoDomain($ID,"articatech.int");
            $AddAction[]="addAction(AllRule(), LuaAction(SpoofNoDomain$ID))";
            $AllSectorsRules[]="OrRule{ selector$ID, RegexRule(\"^[0-9a-zA-Z\\\-_\\\~]+$\") }";
            $fsrc[]="selector$ID = OrRule{ selector$ID, RegexRule(\"^[0-9a-zA-Z\\\-_\\\~]+$\") }";
        }

        if(BUILD_ACLS_SRC($ID)){
            $fsrc[] = @implode("\n",$GLOBALS["BUILD_ACLS_LB_SERVERS"]);
            $fsrc[] = "-- Selector, src IP addresses";
            $fsrc[] = "SRCIP$ID = newNMG()";
            $fsrc[] = @implode("\n",$GLOBALS["LB_IP"]);
            $SELECTOR++;
            $AllSectorsRules[]="AndRule{ selector$ID, NetmaskGroupRule(SRCIP{$ID}) }";
            $fsrc[]="selector$ID = AndRule{ selector$ID, NetmaskGroupRule(SRCIP{$ID}) }";
        }else{
            $fsrc[] = @implode("\n",$GLOBALS["BUILD_ACLS_LB_SERVERS"]);

        }

        if(BUILD_ACLS_ALL($ID)) {
            $SELECTOR++;
            $AllSectorsRules[]="AndRule{ selector$ID, makeRule(\"0.0.0.0/0\") }";
            $fsrc[] = "selector$ID = AndRule{ selector$ID, makeRule(\"0.0.0.0/0\") }";
        }

        if(BUILD_ACLS_REPUTATION($ID)){
            $fsrc[] = @implode("\n",$GLOBALS["BUILD_ACLS_REPUTATION"]);
            $SELECTOR++;
            $AllSectorsRules[]="AndRule{ selector$ID,  LuaRule(GetSrcRep$ID) }";
            $fsrc[] = "selector$ID = AndRule{ selector$ID,  LuaRule(GetSrcRep$ID) }";
        }

        if(BUILD_ACLS_GEOIP_SRC($ID)){
            $fsrc[] = @implode("\n",$GLOBALS["BUILD_ACLS_GEOIP_SRC"]);
            $SELECTOR++;
            $AllSectorsRules[]="AndRule{ selector$ID,  LuaRule(GetSrcGeo$ID) }";
            $fsrc[] = "selector$ID = AndRule{ selector$ID,  LuaRule(GetSrcGeo$ID) }";
        }

       if(BUILD_ACLS_QTYPE($ID)){
            $fsrc[] = @implode("\n",$GLOBALS["BUILD_ACLS_QTYPE"]);
            $SELECTOR++;
            $AllSectorsRules[]="AndRule{ selector$ID,  LuaRule(Qtypefilter$ID) }";
            $fsrc[] = "selector$ID = AndRule{ selector$ID,  LuaRule(Qtypefilter$ID) }";
        }

        if(BUILD_ACLS_DSTDOMREGEX($ID)){
            if(!$NODest) {
                $fsrc[] = @implode("\n", $GLOBALS["BUILD_ACLS_LB_SERVERS"]);
                $SELECTOR++;
                $AllSectorsRules[] = "AndRule{ selector$ID, {$GLOBALS["REGEX_DOM"]} }";
                $fsrc[] = "selector$ID = AndRule{ selector$ID, {$GLOBALS["REGEX_DOM"]} }";
            }
        }


        if(BUILD_ACLS_DSTDOM($ID)){
            if(!$NODest) {
                $NewFile=array();
                $NewFile[] = @implode("\n", $GLOBALS["BUILD_ACLS_LB_SERVERS"]);
                $NewFile[] = "DSTDOM$ID = newSuffixMatchNode()";
                $NewFile[] = @implode("\n", $GLOBALS["LB_DOM"]);
                if(!is_dir("/etc/dnsdist/rules.$ID")) {
                    @mkdir("/etc/dnsdist/rules.$ID", 0755, true);
                }
                @file_put_contents("/etc/dnsdist/rules.$ID/SuffixMatchNode.conf",@implode("\n",$NewFile));
                $fsrc[]="includeDirectory(\"/etc/dnsdist/rules.$ID\")";

                $SELECTOR++;
                $AllSectorsRules[] = "AndRule{ selector$ID, SuffixMatchNodeRule(DSTDOM$ID) }";
                $fsrc[] = "selector$ID = AndRule{ selector$ID, SuffixMatchNodeRule(DSTDOM$ID) }";
            }
        }

        if(BUILD_ACLS_CATEGORIES($ID)){
            if(!$NODest) {
                $fsrc[] = @implode("\n", $GLOBALS["BUILD_ACLS_CATEGORIES"]);
                $SELECTOR++;
                $AllSectorsRules[] = "AndRule{ selector$ID, LuaRule(GetCategories$ID) }";
                $fsrc[] = "selector$ID = AndRule{ selector$ID, LuaRule(GetCategories$ID) }";
            }
        }

        if(BUILD_ACLS_THESHIELDS($ID)){
            if(!$NODest) {
                $fsrc[] = @implode("\n", $GLOBALS["BUILD_ACLS_THESHIELDS"]);
                $SELECTOR++;
                $AllSectorsRules[] = "AndRule{ selector$ID, LuaRule(GetTheShields$ID) }";
                $fsrc[] = "selector$ID = AndRule{ selector$ID, LuaRule(GetTheShields$ID) }";
            }
        }

        if(BUILD_ACLS_WEBFILTER($ID)){
            if(!$NODest) {
                $fsrc[] = @implode("\n", $GLOBALS["BUILD_ACLS_WEBFILTER"]);
                $SELECTOR++;
                $AllSectorsRules[] = "AndRule{ selector$ID, LuaRule(Webfilter$ID) }";
                $fsrc[] = "selector$ID = AndRule{ selector$ID, LuaRule(Webfilter$ID) }";
            }
            $f[]=@implode("\n",$GLOBALS["BUILD_ACLS_GEOIP_SRC"]);
        }


        if($ruletype==1){
            $GLOBALS["BUILD_ACLS_LB_SERVERS"]=array();
            $GLOBALS["LB_SERVERS"]=array();
            $GLOBALS["LB_SERVERS_DEFAULTS"]=array();
            $GLOBALS["newServer_options"]=array();
            $f[] = "-- RULE TYPE 1";
            $f[] = @implode("\n", $GLOBALS["newServer_options"]);
            if(!BUILD_ACLS_LB_SERVERS($ID)){
                $f[] = "-- Seems not server to balance";
                continue;
            }
            if($SELECTOR==0){
                $f[] = "-- Seems no selector to balance, using default instead";
                $f[] = @implode("\n", $GLOBALS["LB_SERVERS_DEFAULTS"]);
                continue;
            }

            $uuid=gettuid();
            $f[] = @implode("\n",$fsrc);
            $f[] = @implode("\n",$GLOBALS["BUILD_ACLS_LB_SERVERS"]);
            $f[] = @implode("\n",$GLOBALS["LB_SERVERS"]);
            LogRuleName($ID);
            $f[] = "-- Additional actions=".count($AddAction);
            if(count($AddAction)>0){
                $f[] = @implode("\n",$AddAction);
            }
            $f[] = "addAction(selector$ID, LuaAction(LogRule$ID) )";
            $f[] = "addAction(selector$ID,  PoolAction(\"Pool$ID\"),{name=\"rule-$ID\",uuid=\"$uuid\"})";
            $q->QUERY_SQL("UPDATE dnsdist_rules set uuid='$uuid' WHERE ID=$ID");
            continue;
        }

        if($ruletype==2){
            if($SELECTOR==0) {
                $f[] = "-- Seems no selector for this rule, aborting";
                continue;
            }

            $uuid=gettuid();

            $CountOfSelector=0;
            foreach ($fsrc as $tline){
                $tline=trim($tline);
                if($tline==null){continue;}
                $CountOfSelector++;
                $f[] = $tline;
            }
            LogRuleName($ID);
            if($CountOfSelector>0) {
                $f[] = "addAction(selector$ID, LuaAction(LogRule$ID) )";
                if($FireHolEnable==1){
                    if (strlen($ligne["fwobject"]) > 2) {
                        LUaFW($ID,$ligne["fwobject"]);
                        $f[] = "addAction(selector$ID, LuaAction(LUaFW$ID) )";
                    }
                }
                $f[] = "addAction(selector$ID,  RCodeAction(DNSRCode.REFUSED),{name=\"rule-$ID\",uuid=\"$uuid\"} )";
            }
            $q->QUERY_SQL("UPDATE dnsdist_rules set uuid='$uuid' WHERE ID=$ID");
            continue;
        }
        if($ruletype==3){
            if($rulevalue==null){
                $f[] =@implode("\n",$GLOBALS["BUILD_ACLS_LB_SERVERS"]);
                $f[] = "-- No CNAME defined for the answer";
                continue;
            }
            $CountOfSelector=0;
            foreach ($fsrc as $tline){
                $tline=trim($tline);
                if($tline==null){continue;}
                $CountOfSelector++;
                $f[] = $tline;
            }

            $uuid=gettuid();
            LogRuleName($ID);
            if($CountOfSelector>0) {

                $f[] = "addAction(selector$ID, LuaAction(LogRule$ID) )";
                $f[] = "addAction(selector$ID,  SpoofCNAMEAction('$rulevalue'),{name=\"rule-$ID\",uuid=\"$uuid\"} )";
            }
            $q->QUERY_SQL("UPDATE dnsdist_rules set uuid='$uuid' WHERE ID=$ID");
            continue;
        }
        if($ruletype==4){
            $GLOBALS["ERRORS"][]="-- $rulevalue -->";
            $zlistValues=SpoofAction_value($rulevalue);
            $GLOBALS["ERRORS"][]="-- $rulevalue < --";
            $listValues=GET_ACLS_DST_SERVERS($ID,true);
            if(count($listValues)>0){
                foreach ($listValues as $zvalue){$zlistValues[]=$zvalue;}
            }

            $final_value=@implode(",",$zlistValues);
            $rulevalue="{{$final_value}}";
            if(strlen($rulevalue)<4) {
                $f[] = @implode("\n", $GLOBALS["BUILD_ACLS_LB_SERVERS"]);
                $f[] = "-- $rulevalue No IP defined for the answer";
                continue;
            }

            $CountOfSelector=0;
            foreach ($fsrc as $tline){
                $tline=trim($tline);
                if($tline==null){continue;}
                $CountOfSelector++;
                $f[] = $tline;
            }
            $uuid=gettuid();
            LogRuleName($ID);
            $f[] = "-- Rule.$ID Type: $ruletype Selectors: $CountOfSelector";
            if($CountOfSelector>0) {

                $f[] = "addAction(selector$ID, LuaAction(LogRule$ID) )";
                $f[] = "addAction(selector$ID,  SpoofAction($rulevalue),{name=\"rule-$ID\",uuid=\"$uuid\"} )";
            }
            $q->QUERY_SQL("UPDATE dnsdist_rules set uuid='$uuid' WHERE ID=$ID");
            continue;
        }
        if($ruletype==5){
            $uuid=gettuid();
            $CountOfSelector=0;
            foreach ($fsrc as $tline){
                $tline=trim($tline);
                if($tline==null){continue;}
                $CountOfSelector++;
                $f[] = $tline;
            }
            if($CountOfSelector>0) {

                $f[] = "addAction(selector$ID,  SkipCacheAction() ,{name=\"rule-$ID\",uuid=\"$uuid\"})";
            }
            $q->QUERY_SQL("UPDATE dnsdist_rules set uuid='$uuid' WHERE ID=$ID");
            continue;
        }
        if($ruletype==6){
            $GLOBALS["BUILD_ACLS_LB_SERVERS"]=array();
            $GLOBALS["LB_SERVERS"]=array();
            $GLOBALS["LB_SERVERS_DEFAULTS"]=array();
            $GLOBALS["newServer_options"]=array();
            BUILD_ACLS_LB_GOOGLE_DOH($ID);
            $uuid=gettuid();
            $f[] = @implode("\n", $GLOBALS["newServer_options"]);
            $f[] = @implode("\n",$fsrc);
            $f[] = @implode("\n",$GLOBALS["BUILD_ACLS_LB_SERVERS"]);
            $f[] = @implode("\n",$GLOBALS["LB_SERVERS"]);
            LogRuleName($ID);

            $f[] = "addAction(selector$ID, LuaAction(LogRule$ID) )";
            $f[] = "addAction(selector$ID,  PoolAction(\"Google\"),{name=\"rule-$ID\",uuid=\"$uuid\"})";
            $q->QUERY_SQL("UPDATE dnsdist_rules set uuid='$uuid' WHERE ID=$ID");

        }
        if($ruletype==9){
            LogRuleName($ID);
            $f[] = @implode("\n",$fsrc);
            $GLOBALS["BUILD_ACLS_LB_SERVERS"]=array();
            $GLOBALS["LB_SERVERS"]=array();
            $GLOBALS["LB_SERVERS_DEFAULTS"]=array();
            $GLOBALS["newServer_options"]=array();
            $uuid=gettuid();
            $safe_settings=unserialize(base64_decode($ligne["dns_caches"]));
            $q->QUERY_SQL("UPDATE dnsdist_rules set uuid='$uuid' WHERE ID=$ID");
            $f[]=BUILD_ACLS_SAFESEARCH($ID,$safe_settings,$uuid,$AllSectorsRules);
        }
    }

    return @implode("\n",$f);

}

function SpoofAction_value($value):array{

    $value=trim($value);
    $IP=new IP();
    $values=array();
    if(strpos(" $value",",")>0){$values=explode(",",$value);}
    if(count($values)==0){
        if(strpos(" $value",";")>0){$values=explode(";",$value);}
    }
    if(count($values)==0){
        if(strpos(" $value"," ")>0){$values=explode(" ",$value);}
    }
    if(count($values)==0){
        if(!$IP->isValid($value)){
            $GLOBALS["ERRORS"][]="-- Error: SpoofAction_value: $value is not a valid IP address";
            return array();}
        return array("\"$value\"");
    }
    $f=array();
    foreach ($values as $ipaddr){
        if(!$IP->isValid($ipaddr)){
            $GLOBALS["ERRORS"][]="-- Error: SpoofAction_value: $ipaddr is not a valid IP address";
            continue;
        }
        $f[]="\"$ipaddr\"";
    }
    if(count($f)==0){return array();}
    return $f;
}

function gettuid():string{
    $uuid = bin2hex( openssl_random_pseudo_bytes(16) );
            for($cnt = 8; $cnt <=23; $cnt+=5) {
                $uuid = substr($uuid, 0, $cnt) . "-" . substr($uuid, $cnt);
            }
    return $uuid;
}

function BUILD_ACLS_SRC($ruleid):bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='src'
		AND dnsdist_sqacllinks.aclid=$ruleid
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);
    $GLOBALS["LB_IP"]=array();
    $GLOBALS["BUILD_ACLS_LB_SERVERS"]=array();
    foreach ($results as $index=>$ligne){
        $gpid=$ligne["gpid"];
        $GroupName=utf8_encode($ligne["GroupName"]);
        $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- Group $GroupName ($gpid)";
        $results2=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid");
        if(count($results2)==0){
            $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- no items for this group";
            continue;
        }

        foreach ($results2 as $index2=>$ligne2){
            $pattern=$ligne2["pattern"];
            if(!preg_match("#^([0-9]+)\/([0-9]+)#",$pattern)){
                $pattern=$pattern."/32";
            }


            $GLOBALS["LB_IP"][]="SRCIP$ruleid:addMask('$pattern')";

        }

    }

    if(count($GLOBALS["LB_IP"])==0){return false;}
    return true;
}

function BUILD_ACLS_ALL($ruleid):bool{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");


    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='all'
		AND dnsdist_sqacllinks.aclid=$ruleid
		ORDER BY dnsdist_sqacllinks.zOrder";
        $results=$q->QUERY_SQL($sql);
        if(count($results)>0){return true;}
        return false;

}

function BUILD_ACLS_DSTDOM($ruleid){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT ruletype,simpledomains FROM dnsdist_rules WHERE ID='{$ruleid}'");
    $GLOBALS["LB_DOM"]=array();
    $ruletype=$ligne["ruletype"];
    $GLOBALS["BUILD_ACLS_LB_SERVERS"]=array();

    $simpledomains=trim($ligne["simpledomains"]);
    $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- simpledomains=[$simpledomains]";
    if($ruletype==6){
        $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- Adding google domains.";
        $simpledomains="googleapis.com,googletagmanager.com,googleads.com,google.com,googlehosted.com,googleusercontent.com,googleadservices.com,withgoogle.com,googleoptimize.com,google-analytics.com,crashlytics.com,android.com,googlesource.com,gstatic.com,googlesyndication.com,goo.gl,youtube.com,doubleclick.net,doubleclick.com,googlefiber.net,firebaseio.com,googlecode.com,googleusercontent.co,appspot.com,admob.com,advertisercommunity.com,gvt2.com,googleblog.com,ggpht.com,gvt1.com,google,1e100.net,googlepages.com,ytimg.com,youtubegaming.com,youtubee.com,youtubee.life,youtubeeconomics.com,youtubeeducation.com,youtubeenhancer.fr,youtubeevideo.com,youtube-nocookie.com,youtube-noscript.com,youtube.be,youtube.co.uk,youtube.de,youtube.es,youtube.fr,youtube.nl,youtube.pl,youtubeeducation.com,youtubegaming.com,youtube-nocookie.com,youtube-noscript.com,youtube.be,youtube.co.uk,youtube.com,youtube.de,youtube.es,youtube.fr,youtube.nl,youtube.pl,youtubeeducation.com,youtubegaming.com,fitbit.com,nest.com,chrome.com,waze-app.com,uservoice.com,waze.com,chromium.org,tiltbrush.com,youtu.be,,g.co,goo.gl,goog.le,youtu.be,yt.be,gmail.co,gmail.de,gmail.dk,gmail.fr,gmail.ie,gmail.kz,gmail.name,gmailbuch.de,gmailbuzz.cl,gmailbuzz.dk,gmailer.de,gmails.de,gmailtechnicalsupport.net,gmailtechsupport.org,googlemail.com,admob.com,advertisercommunity.com,agoogleaday.com,ampproject.org,appspot.com,autodraw.com,blogspot.com,blogspot.ru,capitalg.com,chromeexperiments.com,chromestatus.com,deepmind.com,dialogflow.com,dmtry.com,flutter.io,forms.gle,getmdl.io,google-docs.org,googlee.com,googlesciencefair.com,googlew.com,googlezip.net,gv.com,gvt.com,gvt1.com,gvt2.com,gvt3.com,gv4.com,gvt5.com,analytics.com,google.cx,google.eu,google.nf,google.us,googleadservices.co.uk,googleadservices.org,googleadservices.ru,googleadsserving.cn,googlesciencefair.com,googlesource.com,googlesucks.com,googletagservice.com,googletraveladservices.com,googleusercontent.com,googlevideo.com,googlew.com,googlezip.net,dartsearch.net,app-measurement.com,domains4google.com,foofle.com,forms.gle,froogle.com,g.cn,ggoogle.com,ggpht.com,gmail.com,gmodules.com,gogle.com,gogole.com,googel.com,googil.com,googl.com,google.ac,google.ad,google.ae,google.al,google.am,google.as,google.at,google.az,google.ba,google.be,google.bf,google.bg,google.bh,google.bi,google.bj,google.bs,google.bt,google.by,google.ca,google.cat,google.cc,google.cd,google.cf,google.cg,google.ch,google.ci,google.cl,google.cm,google.cn,google.co.ao,google.co.bw,google.co.ck,google.co.cr,google.co.id,google.co.il,google.co.in,google.co.jp,google.co.ke,google.co.kr,google.co.ls,google.co.ma,google.co.mz,google.co.nz,google.co.th,google.co.tz,google.co.ug,google.co.uk,google.co.uz,google.co.ve,google.co.vi,google.co.za,google.co.zm,google.co.zw,google.com,google.com.af,google.com.ag,google.com.ai,google.com.ar,google.com.au,google.com.bd,google.com.bh,google.com.bn,google.com.bo,google.com.br,google.com.bz,google.com.co,google.com.cu,google.com.cy,google.com.do,google.com.ec,google.com.eg,google.com.et,google.com.fj,google.com.gh,google.com.gi,google.com.gt,google.com.hk,google.com.jm,google.com.kh,google.com.kw,google.com.lb,google.com.lc,google.com.ly,google.com.mm,google.com.mt,google.com.mx,google.com.my,google.com.na,google.com.nf,google.com.ng,google.com.ni,google.com.np,google.com.om,google.com.pa,google.com.pe,google.com.pg,google.com.ph,google.com.pk,google.com.pr,google.com.py,google.com.qa,google.com.sa,google.com.sb,google.com.sg,google.com.sl,google.com.sv,google.com.tj,google.com.tr,google.com.tw,google.com.ua,google.com.uy,google.com.vc,google.com.vn,google.cv,google.cz,google.de,google.dj,google.dk,google.dm,google.dz,google.ee,google.es,google.fi,google.fm,google.fr,google.ga,google.ge,google.gf,google.gg,google.gl,google.gm,google.gp,google.gr,google.gy,google.hn,google.hr,google.ht,google.hu,google.ie,google.im,google.io,google.iq,google.is,google.it,google.je,google.jo,google.kg,google.ki,google.kz,google.la,google.li,google.lk,google.lt,google.lu,google.lv,google.md,google.me,google.mg,google.mk,google.ml,google.mn,google.ms,google.mu,google.mv,google.mw,google.ne,google.net,google.ng,google.nl,google.no,google.nr,google.nu,google.org,google.pl,google.pn,google.ps,google.pt,google.ro,google.rs,google.ru,google.rw,google.sc,google.se,google.sh,google.si,google.sk,google.sm,google.sn,google.so,google.sr,google.st,google.td,google.tg,google.tk,google.tl,google.tm,google.tn,google.to,google.tt,google.vg,google.vu,google.ws,googleadservices.com,googleanalytics.com,googleapis.co,googleapps.com,googlearth.com,googleblog.com,googlebot.com,googlecdn.org,googlecloudpresscorner.com,googlecode.com,googlecommerce.com,googledrive.com,googlee.com,googleearth.com,googlegroups.com,googlehosts.org,googlecom,googlemerchandisestore.com,googlepagecreator.com,googlegooglesyndication-cn.com,googlesyndication.com,googletranslate.com,googleweblight.com,googlr.com,goolge.com,gooogle.com,gstatic.org,google.ac,google.ae,google.at,google.au,google.br,google.ch,google.co.jp,google.co.kr,google.de,google.dk,google.es,google.eu,google.gr,google.ie,google.il,google.in,google.it,google.jp,google.pk,google.ru,google.se,igoogle.com,m.2mdn.net,google.ac,google.ad,google.ae,google.al,google.am,google.as,google.at,google.az,google.ba,google.be,google.bf,google.bg,google.bj,google.bs,google.bt,google.by,google.ca,google.cat,google.cc,google.cd,google.cf,google.cg,google.ch,google.ci,google.cl,google.cm,google.cn,google.co.ao,google.co.bw,google.co.ck,google.co.cr,google.co.id,google.co.il,google.co.in,google.co.jp,google.co.ke,google.co.kr,google.co.ma,google.co.mz,google.co.nz,google.co.tz,google.co.uk,google.co.uz,google.co.ve,google.co.vi,google.co.za,google.co.zm,google.co.zw,google.com.af,google.com.ag,google.com.ai,google.com.ar,google.com.au,google.com.bd,google.com.bh,google.com.bn,google.com.bo,google.com.bz,google.com.co,google.com.cu,google.com.cy,google.com.do,google.com.ec,google.com.et,google.com.fj,google.com.gh,google.com.gi,google.com.gt,google.com.hk,google.com.jm,google.com.kh,google.com.kw,google.com.lb,google.com.lc,google.com.ls,google.com.ly,google.com.mm,google.com.mt,google.com.mx,google.com.my,google.com.na,google.com.nf,google.com.ng,google.com.ni,google.com.np,google.com.om,google.com.pa,google.com.pe,google.com.pg,google.com.ph,google.com.pk,google.com.pr,google.com.py,google.com.qa,google.com.sa,google.com.sb,google.com.sg,google.com.sl,google.com.sv,google.com.th,google.com.tj,google.com.tn,google.com.tr,google.com.tw,google.com.ua,google.com.uy,google.com.vc,google.com.vn,google.cv,google.cz,google.de,google.dj,google.dk,google.dm,google.dz,google.ee,google.fi,google.fm,google.fr,google.ga,google.ge,google.gf,google.gg,google.gl,google.gm,google.gp,google.gr,google.gy,google.hn,google.hr,google.ht,google.hu,google.ie,google.im,google.io,google.iq,google.ir,google.is,google.it,google.je,google.jo,google.kg,google.ki,google.kz,google.la,google.li,google.lk,google.lt,google.lu,google.lv,google.md,google.me,google.mg,google.mk,google.ml,google.mn,google.ms,google.mu,google.mv,google.mw,google.ne,google.net,google.nl,google.no,google.nr,google.nu,google.org,google.pl,google.pn,google.ps,google.pt,google.ro,google.rs,google.ru,google.rw,google.sc,google.se,google.sh,google.si,google.sk,google.sm,google.sn,google.so,google.st,google.td,google.tg,google.tk,google.tl,google.tm,google.to,google.tt,google.us,google.vg,google.vu,google.ws,app-measurement.com,2mdn.net,googlesyndicytion.com,goog,blogger.com,google.ac,google.ad,google.ae,google.al,google.am,google.as,google.at,google.az,google.ba,google.be,google.bf,google.bg,google.bh,google.bi,google.bj,google.bs,google.bt,google.by,google.ca,google.cat,google.cc,google.cd,google.cf,google.cg,google.ch,google.ci,google.cl,google.cm,google.cn,google.co.ao,google.co.bw,google.co.ck,google.co.cr,google.co.id,google.co.il,google.co.in,google.co.jp,google.co.ke,google.co.kr,google.co.ls,google.co.ma,google.co.mz,google.co.nz,google.co.th,google.co.tz,google.co.ug,google.co.uk,google.co.uz,google.co.ve,google.co.vi,google.co.za,google.co.zm,google.co.zw,google.com.af,google.com.ag,google.com.ai,google.com.ar,google.com.au,google.com.bd,google.com.bh,google.com.bn,google.com.bo,google.com.br,google.com.bz,google.com.co,google.com.cu,google.com.cy,google.com.do,google.com.ec,google.com.eg,google.com.et,google.com.fj,google.com.gh,google.com.gi,google.com.gt,google.com.hk,google.com.jm,google.com.kh,google.com.kw,google.com.lb,google.com.lc,google.com.ls,google.com.ly,google.com.mm,google.com.mt,google.com.mx,google.com.my,google.com.na,google.com.nf,google.com.ng,google.com.ni,google.com.np,google.com.om,google.com.pa,google.com.pe,google.com.pg,google.com.ph,google.com.pk,google.com.pr,google.com.py,google.com.qa,google.com.sa,google.com.sb,google.com.sg,google.com.sl,google.com.sv,google.com.th,google.com.tj,google.com.tn,google.com.tr,google.com.tw,google.com.ua,google.com.uy,google.com.vc,google.com.vn,google.cv,google.cz,google.de,google.dj,google.dk,google.dm,google.dz,google.ee,google.es,google.fi,google.fm,google.fr,google.ga,google.ge,google.gf,google.gg,google.gl,google.gm,google.gp,google.gr,google.gy,google.hn,google.hr,google.ht,google.hu,google.ie,google.im,google.io,google.iq,google.ir,google.is,google.it,google.it.ao,google.je,google.jo,google.kg,google.ki,google.kz,google.la,google.li,google.lk,google.lt,google.lu,google.lv,google.md,google.me,google.mg,google.mk,google.ml,google.mn,google.ms,google.mu,google.mv,google.mw,google.ne,google.net,google.nl,google.no,google.nr,google.nu,google.org,google.pl,google.pn,google.ps,google.pt,google.ro,google.rs,google.ru,google.rw,google.sc,google.se,google.sh,google.si,google.sk,google.sm,google.sn,google.so,google.sr,google.st,google.td,google.tg,google.tk,google.tl,google.tm,google.tn,google.to,google.tt,google.us,google.vg,google.vu,google.ws,googlecom,doublecklick.net,speedera.net,doublecklick.net,doubleclick-net.com,doubleclick.com,doubleclick.de,doubleclick.ne.jp,doubleclick.net,doubleclickbygoogle.com,doubleclick.netnod,doubleclick.com,doubleclick.ne.jp,doubleclick.net,doubleclickbygoogle.com,admob.biz,admob.co.kr,admob.co.nz,admob.co.uk,admob.com,admob.de,admob.dk,admob.es,admob.fi,admob.fr,admob.gr,admob.it,admob.jp,admob.kr,admob.mobi,admob.no,admob.pt,admob.sg,admob.tk,admob.tw,admob.vn,admobclick.com,admobsphere.com,ads.cc,ads.cc-dt.com,adsense.com,google.ac,google.ae,google.al,google.at,google.au,google.aw,google.ax,google.az,google.be,google.bg,google.br,google.bs,google.by,google.ca,google.ch,google.cl,google.co.bw,google.co.id,google.co.il,google.co.in,google.co.jp,google.co.ke,google.co.kr,google.co.nz,google.co.th,google.co.tz,google.co.uk,google.co.uz,google.co.ve,google.co.vi,google.co.za,google.co.zm,google.com.au,google.com.br,google.com.bz,google.com.co,google.com.cy,google.com.eg,google.com.gh,google.com.hk,google.com.kw,google.com.lb,google.com.mt,google.com.mx,google.com.my,google.com.ng,google.com.pa,google.com.qa,google.com.sa,google.com.sg,google.com.sl,google.com.tj,google.com.tr,google.com.tw,google.com.ua,google.com.vn,google.cz,google.dk,google.dz,google.ee,google.eu,google.fi,google.fr,google.ge,google.gr,google.hn,google.hr,google.hu,google.ie,google.il,google.in,google.iq,google.is,google.it,google.jo,google.jp,google.kg,google.kz,google.la,google.li,google.lk,google.lt,google.lu,google.lv,google.ma,google.md,google.me,google.mk,google.ml,google.mn,google.mv,google.ne,google.nl,google.no,google.pk,google.pl,google.pt,google.ro,google.rs,google.ru,google.sc,google.se,google.sh,google.si,google.sk,google.sm,google.sr,google.td,google.tm,google.tn,google.to,google.tr,google.tw,google.ua,google.ws,adups.com";
        $tb=explode(",",$simpledomains);
        foreach ($tb as $dom){$dom=strtolower(trim($dom));if($dom==null){continue;}$TRIMED[$dom]=true;}
        $tb=array();
        foreach ($TRIMED as $dom=>$none){$tb[]=$dom;}
        $simpledomains=@implode(",",$tb);
    }

    if(strpos($simpledomains,",")>0){
        $tb=explode(",",$simpledomains);
        foreach ($tb as $dom){
            $dom=trim($dom);
            if($dom==null){continue;}
            $GLOBALS["DSTDOMAINS"][$dom]=true;
            $GLOBALS["LB_DOM"][]="DSTDOM{$ruleid}:add(newDNSName(\"{$dom}\"))";
            $GLOBALS["LB_DOM"][]="DSTDOM{$ruleid}:add(newDNSName(\"{$dom}.\"))";
        }
    }else{
        if(strlen($simpledomains)>3){
            $GLOBALS["DSTDOMAINS"][$simpledomains]=true;
            $GLOBALS["LB_DOM"][]="DSTDOM{$ruleid}:add(newDNSName(\"{$simpledomains}\"))";
            $GLOBALS["LB_DOM"][]="DSTDOM{$ruleid}:add(newDNSName(\"{$simpledomains}.\"))";
        }
    }



    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='dstdomain'
		AND dnsdist_sqacllinks.aclid=$ruleid
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);


    foreach ($results as $index=>$ligne){
        $gpid=$ligne["gpid"];
        $GroupName=utf8_encode($ligne["GroupName"]);
        $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- Group $GroupName ($gpid)";
        $results2=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid");
        if(count($results2)==0){
            $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- no items for this group";
            continue;
        }

        foreach ($results2 as $index2=>$ligne2){
            $pattern=$ligne2["pattern"];
            if(substr($pattern,0,1)=="^"){$pattern=substr($pattern,1,strlen($pattern));}
            $GLOBALS["DSTDOMAINS"][$pattern]=true;
            $GLOBALS["LB_DOM"][]="DSTDOM{$ruleid}:add(newDNSName(\"{$pattern}\"))";
            $GLOBALS["LB_DOM"][]="DSTDOM{$ruleid}:add(newDNSName(\"{$pattern}.\"))";

        }

    }
    $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- No domain for target rule $ruleid";
    if(count($GLOBALS["LB_DOM"])==0){return false;}
    return true;

}
function BUILD_ACLS_DSTDOMREGEX($ruleid){

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='dstdom_regex'
		AND dnsdist_sqacllinks.aclid=$ruleid
		ORDER BY dnsdist_sqacllinks.zOrder";
    $results=$q->QUERY_SQL($sql);
    $GLOBALS["LB_DOM"]=array();
    $GLOBALS["REGEX_DOM"]=null;
    $GLOBALS["BUILD_ACLS_LB_SERVERS"]=array();
    $regx=array();
    foreach ($results as $index=>$ligne){
        $gpid=$ligne["gpid"];
        $GroupName=utf8_encode($ligne["GroupName"]);
        $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- Group $GroupName ($gpid)";
        $results2=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid");
        if(count($results2)==0){
            $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- no items for this group";
            continue;
        }

        foreach ($results2 as $index2=>$ligne2){
            $pattern=$ligne2["pattern"];
            $regx[]="RegexRule('$pattern')";



        }

    }

    $GLOBALS["REGEX_DOM"]="OrRule{ ".@implode(", ",$regx)." }";
    if(count($regx)==0){return false;}
    return true;

}

function GET_ACLS_DST_SERVERS($ruleid,$return_array=false){
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID AND webfilters_sqgroups.GroupType='dst'
		AND dnsdist_sqacllinks.aclid=$ruleid
		ORDER BY dnsdist_sqacllinks.zOrder";
    $gps=array();
    $results=$q->QUERY_SQL($sql);
    $PP=array();
    $IPClass=new IP();
    foreach ($results as $index=>$ligne1){
        $gpid=$ligne1["gpid"];
        $sql="SELECT pattern FROM webfilters_sqitems WHERE gpid=$gpid ORDER BY pattern";
        $results2 = $q->QUERY_SQL($sql);
        foreach ($results2 as $index=>$ligne3){
            $pattern=$ligne3["pattern"];
            if(!$IPClass->isIPAddressOrRange($pattern)){continue;}
            if(strpos($pattern,"/")>0){
                $tt=explode("/",$pattern);
                if($tt[1]<32){continue;}
                $pattern=$tt[0];
            }
            $PP[]="\"$pattern\"";
        }
    }

    if($return_array){
        if(count($PP)==0){return array();}
        return $PP;
    }

    if(count($PP)==0){return "";}
    return @implode(", ",$PP);


}
function BUILD_ACLS_LB_SERVERS_PacketCache($ruleid,$cache_settings=array()):string{
    if(intval($cache_settings["cache_enable"])==0){return "";}
    $MaxRecords=intval($cache_settings["MaxRecords"]);
    $maxTTL=intval($cache_settings["MaxRecords"]);
    $minTTL=intval($cache_settings["minTTL"]);
    $staleTTL=intval($cache_settings["staleTTL"]);



    if($minTTL==0){$minTTL=3600;}
    if($maxTTL==0){$maxTTL=172800;}
    if($staleTTL==0){$staleTTL=3600;}


    if($MaxRecords==0){$MaxRecords=100;}
    return "PacketCache{$ruleid} = newPacketCache($MaxRecords, {maxTTL=$maxTTL, minTTL=$minTTL, temporaryFailureTTL=$staleTTL, staleTTL=$staleTTL, dontAge=false})";

}
function BUILD_ACLS_LB_SERVERS_getPool($ruleid,$cache_settings=array(),$enforcepool=null):string{
    if(intval($cache_settings["cache_enable"])==0){return "";}

    if($enforcepool<>null){
        return "getPool(\"$enforcepool\"):setCache(PacketCache{$ruleid})";
    }

    return "getPool(\"Pool{$ruleid}\"):setCache(PacketCache{$ruleid})";
}

function ChecksIntervalDefaults($ligne):array{
    $DNSDistCheckName=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistCheckInterval=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckInterval"));
    $DNSDistMaxCheckFailures=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistMaxCheckFailures"));
    $DNSDistCheckTimeout=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSDistCheckTimeout"));

    if(trim($DNSDistCheckName)==null){$DNSDistCheckName="a.root-servers.net";}
    if(intval($DNSDistCheckInterval)==0){$DNSDistCheckInterval=2;}
    if(intval($DNSDistMaxCheckFailures)==0){$DNSDistMaxCheckFailures=3;}
    if(intval($DNSDistCheckTimeout)==0){$DNSDistCheckTimeout=3;}


    if(trim($ligne["checkName"])==null){$ligne["checkName"]=$DNSDistCheckName;}
    if(intval($ligne["checkInterval"])==0){$ligne["checkInterval"]=$DNSDistCheckInterval;}
    if(intval($ligne["maxCheckFailures"])==0){$ligne["maxCheckFailures"]=$DNSDistMaxCheckFailures;}
    if(intval($ligne["checkTimeout"])==0){$ligne["checkTimeout"]=$DNSDistCheckTimeout;}

    if($ligne["checkInterval"]<2){$ligne["checkInterval"]=2;}
    if($ligne["checkTimeout"]<3){$ligne["checkTimeout"]=3;}

    if(!isset($ligne["dns_caches"])){$ligne["dns_caches"]=base64_encode(serialize(array("cache_enable"=>0)));}
    $cache_settings=unserialize(base64_decode($ligne["dns_caches"]));

    if(!isset( $cache_settings["cache_enable"])){ $cache_settings["cache_enable"]=0;}
    if(!isset( $cache_settings["MaxRecords"])){ $cache_settings["MaxRecords"]=10000;}
    if(!isset( $cache_settings["maxTTL"])){ $cache_settings["maxTTL"]=86400;}
    if(!isset( $cache_settings["minTTL"])){ $cache_settings["minTTL"]=0;}
    if(!isset( $cache_settings["staleTTL"])){ $cache_settings["staleTTL"]=60;}
    $ligne["dns_caches"]=base64_encode(serialize($cache_settings));
    return $ligne;
}

function checksDefaultsAcls($ligne,$opts):array{

    if(!isset($ligne["maxCheckFailures"])){
        echo "!!!!!! \n";
        print_r($ligne);
    }

    $checkName=$ligne["checkName"];

    if(!preg_match("#(.+?)\.$#",$checkName)){$checkName=$checkName.".";}
    $checkInterval=$ligne["checkInterval"];
    $maxCheckFailures=$ligne["maxCheckFailures"];
    $checkTimeout=$ligne["checkTimeout"];



    if($checkInterval<2){$checkInterval=2;}
    if($checkTimeout<3){$checkTimeout=3;}
    $DNSDIST_VERSION=DNSDIST_VERSION();
    $vers=explode(".",$DNSDIST_VERSION);
    $vMajor=intval($vers[0]);
    $vMinor=intval($vers[1]);
    $LAZY=false;

    if($vMajor==1){
        if($vMinor > 7 ){ $LAZY=true;}
    }

    $opts["useClientSubnet"]="false";
    $opts["checkName"]=$checkName;
    $opts["checkInterval"]=$checkInterval;
    $opts["maxCheckFailures"]=$maxCheckFailures;
    $opts["checkTimeout"]=$checkTimeout*1000;


    if(!$LAZY){return $opts;}

    $opts["healthCheckMode"]="'lazy'";
    $opts["lazyHealthCheckFailedInterval"]="15";
    $opts["rise"]="2";
    $opts["lazyHealthCheckThreshold"]="30";
    $opts["lazyHealthCheckSampleSize"]="100";
    $opts["lazyHealthCheckMinSampleCount"]="10";
    $opts["lazyHealthCheckMode"]="'TimeoutOnly'";
    return $opts;


}

function BUILD_ACLS_LB_GOOGLE_DOH($ruleid):bool{
    $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- BUILD_ACLS_LB_GOOGLE_DOH";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE ID='$ruleid'");
    if(trim($ligne["checkName"])==null){$ligne["checkName"]="redirector.googlevideo.com";}
    $ligne=ChecksIntervalDefaults($ligne);


    $cache_settings=unserialize(base64_decode($ligne["dns_caches"]));

    $opts["address"]="https://dns.google/dns-query";
    $opts=checksDefaultsAcls($ligne,$opts);
    $opts["pool"]="Google";
    $opts["name"]="DNS0-$ruleid";
    $options=newServer_options($opts);
    $GLOBALS["LB_SERVERS"][] = "newServer($options)";
    $s_PacketCache=BUILD_ACLS_LB_SERVERS_PacketCache($ruleid,$cache_settings);
    if(strlen($s_PacketCache)>2){
        $GLOBALS["LB_CACHES"][]=$s_PacketCache;
        $GLOBALS["LB_CACHES"][]=BUILD_ACLS_LB_SERVERS_getPool($ruleid,$cache_settings,"Google");
    }

    return true;
}

function BUILD_ACLS_LB_SERVERS($ruleid):bool{
    $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- BUILD_ACLS_LB_SERVERS";
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");


    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE ID='$ruleid'");
    $ligne=ChecksIntervalDefaults($ligne);
    $ligneRule=$ligne;

    $useClientSubnet=intval($ligne["useClientSubnet"]);
    $cache_settings=unserialize(base64_decode($ligne["dns_caches"]));

    $sql="SELECT dnsdist_sqacllinks.gpid,dnsdist_sqacllinks.negation,dnsdist_sqacllinks.zOrder,webfilters_sqgroups.* 
		FROM dnsdist_sqacllinks,webfilters_sqgroups 
		WHERE dnsdist_sqacllinks.gpid=webfilters_sqgroups.ID 
		AND (webfilters_sqgroups.GroupType='dst' 
		         OR webfilters_sqgroups.GroupType='doh'
		         OR webfilters_sqgroups.GroupType='opendns'
		         OR webfilters_sqgroups.GroupType='opendnsf'
		    )
		AND dnsdist_sqacllinks.aclid=$ruleid
		ORDER BY dnsdist_sqacllinks.zOrder";
    $GLOBALS["LB_SERVERS"]=array();
    $results=$q->QUERY_SQL($sql);
    $PacketCache=array();
    $getPool=array();
    foreach ($results as $index=>$ligne){
        $gpid=$ligne["gpid"];
        $GroupType=$ligne["GroupType"];
        $GroupName=utf8_encode($ligne["GroupName"]);

        $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- [RULE:$ruleid] Group $GroupName/$GroupType ($gpid) eDNS=$useClientSubnet";
        if($GLOBALS["VERBOSE"]){echo "* * * * * [RULE:$ruleid] Group $GroupName/$GroupType ($gpid)\n * * * *\n";}
        if($GroupType=="opendns" OR $GroupType=="opendnsf" ){
            if($GroupType=="opendns"){
                $pattern="https://doh.opendns.com/dns-query";
            }
            if($GroupType=="opendnsf"){
                $pattern="https://doh.familyshield.opendns.com/dns-query";
            }



            if($GLOBALS["VERBOSE"]){echo "* * * * * [RULE:$ruleid] Open DNS OK\n";}
            $opts["address"]=$pattern;
            $opts["useClientSubnet"]="false";
            $opts=checksDefaultsAcls($ligneRule,$opts);
            $opts["pool"]="Pool$ruleid";
            $opts["name"]="DNS$gpid-$ruleid";
            $options=newServer_options($opts);
            if(strlen($options)<3) { continue;}
            $GLOBALS["LB_SERVERS"][] = "newServer($options)";

            $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- Cache enabled = {$cache_settings["cache_enable"]}";
            $s_PacketCache=BUILD_ACLS_LB_SERVERS_PacketCache($ruleid,$cache_settings);
            if(strlen($s_PacketCache)>2){
                $PacketCache[]=$s_PacketCache;
                $getPool[]=BUILD_ACLS_LB_SERVERS_getPool($ruleid,$cache_settings);
            }

            continue;
        }

        $results2=$q->QUERY_SQL("SELECT pattern FROM webfilters_sqitems WHERE enabled=1 AND gpid=$gpid");
        if(count($results2)==0){
            $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- no items for this group";
            continue;
        }

        $GLOBALS["BUILD_ACLS_LB_SERVERS"][]="-- Cache enabled = {$cache_settings["cache_enable"]}";
        $s_PacketCache=BUILD_ACLS_LB_SERVERS_PacketCache($ruleid,$cache_settings);
        if(strlen($s_PacketCache)>2){
            $PacketCache[]=$s_PacketCache;
            $getPool[]=BUILD_ACLS_LB_SERVERS_getPool($ruleid,$cache_settings);
        }



        foreach ($results2 as $index2=>$ligne2){
            $pattern=$ligne2["pattern"];
            $opts=array();
            $opts["address"]=$pattern;
            if($useClientSubnet==1){$GLOBALS["EDNS"]=true;}
            $opts["useClientSubnet"]=newServer_options_boolToStr($useClientSubnet);
            $opts=checksDefaultsAcls($ligneRule,$opts);
            $opts["pool"]="Pool{$ruleid}";
            $opts["name"]="DNS{$gpid}-$ruleid";
            $options=newServer_options($opts);
            if(strlen($options)<3) { continue;}
            $GLOBALS["LB_SERVERS"][] = "newServer($options)";

            $opts["pool"]="defaults";
            $options=newServer_options($opts);
            $GLOBALS["LB_SERVERS_DEFAULTS"][] = "newServer($options)";


        }

    }

    if(count($PacketCache)>0){
        foreach ($PacketCache as $pp){
            $GLOBALS["LB_CACHES"][]=$pp;
        }
    }
    if(count($getPool)>0){
        foreach ($getPool as $pp){
            $GLOBALS["LB_CACHES"][]=$pp;
        }
    }
    if(count($GLOBALS["LB_SERVERS"])==0){return false;}
    return true;

}

function newServer_options($array):string{
    $IPClass=new IP();
    $src_addr=$array["address"];
    if(preg_match("#(ĥttp|ftp|https|ftps):\/\/#",strtolower(trim($array["address"])),$re)){
        $proto=$re[1];
        $port=0;
        $http_address=strtolower(trim($array["address"]));
        $ipaddr=null;
        if(preg_match("#^(.+?)\[([0-9\.]+)\]#",$array["address"],$re)){
            $http_address=$re[1];
            $ipaddr=$re[2];
        }

        $parse=parse_url($http_address);
        $path="/dns-query";
        $host=$parse["host"];
        if(isset($parse["port"])){$port=intval($parse["port"]);}
        if($port==0){
            if($proto=="https"){$port=443;}
            if($proto=="http"){$port=80;}
        }
        if(isset($parse["path"])){$path=$parse["path"];}
        if(isset($parse["query"])){$path="$path{$parse["query"]}";}
        if(preg_match("#^(.+?):([0-9]+)#",$host,$re)){
            $host=$re[1];
            $port=$re[2];
        }

        if(preg_match("#^([0-9\.]+)\/[0-9]+#",$host)){
            $host=$re[1];
        }
        if($GLOBALS["VERBOSE"]){echo "newServer_options: DOH: $http_address == $host\n";}
        if(!$IPClass->isValid($host)){
            if($ipaddr==null) {
                $ipaddr = gethostbyname($host);
            }
            if(!$IPClass->isValid($ipaddr)){
                if($host=="doh.opendns.com"){
                    $ipaddr="146.112.41.2";
                }
            }
        }

        if(!$IPClass->isValid($ipaddr)){
            $GLOBALS["newServer_options"][]="-- $src_addr/$ipaddr No ip address can be resolved";
            return "";
        }

        if(strlen($ipaddr)<4){
            $GLOBALS["newServer_options"][]="-- $src_addr/$ipaddr No ip address can be resolved";
            return "";
        }

        $array["address"]="$ipaddr:$port";
        if($proto=="https") {
            $array["tls"] = "openssl";
        }

        $array["subjectName"]=$host;
        $array["dohPath"]=$path;
    }

    if(!preg_match("#^(.+?):([0-9]+)#",$array["address"])){
        $array["address"]=$array["address"].":53";
    }
    if($array["checkName"]=="0."){$array["checkName"]="a.root-servers.net.";}
    if($array["checkName"]=="."){$array["checkName"]="a.root-servers.net.";}
    $array["checkName"]="\"{$array["checkName"]}\"";


    foreach ($array as $key=>$val){
        if($val=="true" or $val=="false"){
            $opts[]="$key=$val";
            continue;
        }
        if(strpos(" $val",'"')>0){
            $opts[]="$key=$val";
            continue;
        }
        if(strpos(" $val","'")>0){
            $opts[]="$key=$val";
            continue;
        }
        if(!is_numeric($val)){$val="\"$val\"";}
        $opts[]="$key=$val";
    }




    return "{".@implode(", ",$opts)."}";

}
function newServer_options_boolToStr($bool){
    return ($bool) ? 'true' : 'false';
}
function template_7($uuid_temp):bool{
    include_once(dirname(__FILE__)."/ressources/class.acls.wizards.inc");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM dnsdist_rules WHERE uuid='$uuid_temp'");
    $ID=intval($ligne["ID"]);
    _out("Template 7: $uuid_temp as $ID ID");
    if($ID==0){
        _out("Template 7: Failed to find ID...");
        return false;
    }
    _out("Template 7: Create a new object Artica categories");

    $wiz=new acls_wizards("dnsdist_sqacllinks",$ID);
    if(!$wiz->add_group_threats()){
        _out("Template 7: failed $wiz->mysql_error");
        return false;
    }

    _out("Template 7: Success.. creating web threats wizard 7");
    return true;
}
function template_8($uuid_temp):bool{
    include_once(dirname(__FILE__)."/ressources/class.acls.wizards.inc");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM dnsdist_rules WHERE uuid='$uuid_temp'");
    $ID=intval($ligne["ID"]);
    _out("Template 8: $uuid_temp as $ID ID");
    if($ID==0){
        _out("Template 8: Failed to find ID...");
        return false;
    }
    _out("Template 8: Create a new object Artica categories");

    $wiz=new acls_wizards("dnsdist_sqacllinks",$ID);
    if(!$wiz->add_group_adv()){
        _out("Template 8: failed $wiz->mysql_error");
        return false;
    }

    _out("Template 8: Success.. creating web ads wizard 8");
    return true;
}
function template_9($uuid_temp):bool{
    include_once(dirname(__FILE__)."/ressources/class.acls.wizards.inc");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT ID FROM dnsdist_rules WHERE uuid='$uuid_temp'");
    $ID=intval($ligne["ID"]);
    _out("Template 9: $uuid_temp as $ID ID");
    if($ID==0){
        _out("Template 9: Failed to find ID...");
        return false;
    }
    _out("Template 9: Create or check a new Everyone group.");

    $wiz=new acls_wizards("dnsdist_sqacllinks",$ID);
    if(!$wiz->add_group_all()){
        _out("Template 9: failed $wiz->mysql_error");
        return false;
    }

    _out("Template 9: Success.. creating web ads wizard 9");
    return true;

}

function template_10($ID):bool{
    include_once(dirname(__FILE__)."/ressources/class.acls.wizards.inc");
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM dnsdist_rules WHERE ID='$ID'");
    $dns_caches=$ligne["dns_caches"];
    $data=unserialize(base64_decode($dns_caches));
    $addomain=$data["addomain"];
    $adaddr=$data["adaddr"];
    _out("Template 10: Active Directory $addomain [$adaddr]");
    $wiz=new acls_wizards("dnsdist_sqacllinks",$ID);

    _out("Template 10: Creating a new group for destination domain $addomain");
    $items=array();
    $items[]=$addomain;
    if(!$wiz->add_group_dstdomain("AD:$addomain",$items)){
        _out("Template 10: Failed to created ACL...");
        $q->QUERY_SQL("DELETE FROM dnsdist_rules WHERE ID='$ID'");
        return false;
    }
    _out("Template 10: Creating a new group for destination domain $addomain");
    $items=array();
    $items[]=$adaddr;
    if(!$wiz->add_group_dst("Active Directory server",$items)){
        _out("Template 10: Failed to created ACL...");
        $q->QUERY_SQL("DELETE FROM dnsdist_rules WHERE ID='$ID'");
        return false;
    }

    $items=array();
    $items[]="10.0.0.0/8";
    $items[]="172.16.0.0/12";
    $items[]="192.168.0.0/16";

    if(!$wiz->add_group_ptr("Local network rfc 1918",$items)){
        _out("Template 10: Failed to created ACL group PTR...");
    }

    if(!$wiz->add_group_netbiosname("Hosts without domain",$addomain)){
        _out("Template 10: Failed to created ACL group netbiosname...");
    }
    $cache_settings=array();
    if(!isset( $cache_settings["cache_enable"])){ $cache_settings["cache_enable"]=1;}
    if(!isset( $cache_settings["MaxRecords"])){ $cache_settings["MaxRecords"]=10000;}
    if(!isset( $cache_settings["maxTTL"])){ $cache_settings["maxTTL"]=86400;}
    if(!isset( $cache_settings["minTTL"])){ $cache_settings["minTTL"]=0;}
    if(!isset( $cache_settings["staleTTL"])){ $cache_settings["staleTTL"]=90;}
    $cache_settings=base64_encode(serialize($cache_settings));
    $q->QUERY_SQL("UPDATE dnsdist_rules SET ruletype=1,  `dns_caches`='$cache_settings' WHERE ID='$ID'");
    return true;
}


function wizard_setup(){


    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");

    if(!$q->TABLE_EXISTS("dnsdist_rules")){
        $unix=new unix();
        $php=$unix->LOCATE_PHP5_BIN();
        shell_exec("$php ".dirname(__FILE__)."/exec.convert-to-sqlite.php --force");
    }

    $MAIN=array();
    $MAIN["checkName"]="redirector.googlevideo.com";
    $MAIN["ruletype"]=2;
    $MAIN["rulename"] = "Web browsing cleaning";
    $MAIN["uuid"]=time();
    $wizard_cleaning=$MAIN["uuid"];
    $add_fields=array();
    $add_values=array();
    foreach ($MAIN as $key=>$val){
        $add_fields[]="`$key`";
        $add_values[]="'$val'";
    }
    $sql="INSERT INTO dnsdist_rules (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
    }
    template_7($wizard_cleaning);

    $MAIN=array();
    $MAIN["checkName"]="redirector.googlevideo.com";
    $MAIN["ruletype"]=9;
    $MAIN["rulename"] = "SafeSearchs";
    $MAIN["uuid"]=microtime();
    $safe_settings["EnableGoogleSafeSearch"]=1;
    $safe_settings["EnableBraveSafeSearch"]=1;
    $safe_settings["EnableDuckduckgoSafeSearch"]=1;
    $safe_settings["EnableYandexSafeSearch"]=0;
    $safe_settings["EnablePixabaySafeSearch"]=0;
    $safe_settings["EnableQwantSafeSearch"]=1;
    $safe_settings["EnableBingSafeSearch"]=1;
    $safe_settings["EnableYoutubeSafeSearch"]=0;
    $safe_settings["EnbaleYoutubeModerate"]=0;
    $cache_settings=base64_encode(serialize($safe_settings));
    $MAIN["dns_caches"]=$cache_settings;

    $add_fields=array();
    $add_values=array();
    foreach ($MAIN as $key=>$val){
        $add_fields[]="`$key`";
        $add_values[]="'$val'";
    }
    $sql="INSERT INTO dnsdist_rules (".@implode(",", $add_fields).") VALUES (".@implode(",", $add_values).")";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        echo $q->mysql_error."\n";
    }

    template_9($MAIN["uuid"]);

}


function z_install_lua_syslog():bool{
    $ROOT=ARTICA_ROOT."/bin/install/lua";
    $directories[]="/usr/local/lib/lua/5.3";
    $directories[]="/usr/local/share/lua/5.3/logging";

    foreach ($directories as $dir){
        if(!is_dir($dir)){@mkdir($dir,0755,true);}

    }

    $INSTALL["$ROOT/lsyslog.so"]="/usr/local/lib/lua/5.3/lsyslog.so";
    $INSTALL["$ROOT/syslog.lua"]="/usr/local/share/lua/5.3/logging/syslog.lua";
    $INSTALL["$ROOT/logging.lua"]="/usr/local/share/lua/5.3/logging.lua";

    foreach ($INSTALL as $sourcefile=>$destinationfile){

        if(!is_file($sourcefile)){
            _out("ALERT! Unable to find $sourcefile");
            continue;
        }


        $md1=md5_file($sourcefile);
        $md2=null;
        if(is_file($destinationfile)){
            $md2=md5_file($destinationfile);
        }
        if($md1 == $md2){
            _out(basename($sourcefile)." [UPDATED]");
            continue;
        }
        _out(basename($sourcefile)." [INSTALL]");
        @copy($sourcefile,$destinationfile);


    }
    return true;

}




function build_server_pools(){
    // dnsdist -c -C /etc/dnsdist.conf -e "showPools()"
    // dnsdist -c -C /etc/dnsdist.conf -e "getPool('Pool1'):getCache():dump('/tmp/toto.txt')"

}


function remove_limits():bool{
    $unix=new unix();
    $unix->SystemSecurityLimitsConf();
    return true;
}

