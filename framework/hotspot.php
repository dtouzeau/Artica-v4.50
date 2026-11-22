<?php
// Post SP10 - 13/10/2026
$GLOBALS["CACHE_FILE"]="/etc/artica-postfix/iptables-hostspot.conf";
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["events"])){searchInSyslog();exit;}
if(isset($_GET["download-events"])){download_events();exit;}
if(isset($_GET["empty-events"])){empty_events();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");


function empty_events():bool{
    $unix=new unix();
    $echo=$unix->find_program("echo");
    shell_exec("$echo \"\" > /var/log/squid/hotspot.log");
    return true;
}
function download_events():bool{
    $unix = new unix();
    return $unix->compress("/var/log/squid/hotspot.log", PROGRESS_DIR . "/hotspot.log.gz");
}



function searchInSyslog(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/hotspot.log.tmp";
    $sourceLog="/var/log/squid/hotspot.log";
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];
    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";
    $pattern=null;
    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){
           $cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog| $tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod("$targetfile",0755);

}

