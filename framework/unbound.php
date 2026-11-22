<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["fw-requests"])){firewall_events();exit;}
if(isset($_GET["status"])){status();exit;}

if(isset($_GET["cache-clear"])){cache_clear();exit;}
if(isset($_GET["dnsfw-checker"])){firewall_dns_checker();exit;}

$tt=array();
foreach ($_GET as $key=>$val){
    $tt[]="$key=>$val";
}
writelogs_framework("Unable to understand the query ".@implode(" ",$tt),__FUNCTION__,__FILE__,__LINE__);


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unbound.status";
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --unbound >{$GLOBALS["LOGSFILES"]} 2>&1";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function cache_clear(){
	$host=$_GET["cache-clear"];
	$unix=new unix();
	$unbound_control=$unix->find_program("unbound-control");
	$cmd="$unbound_control flush_zone $host";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function firewall_dns_checker(){
    $unix=new unix();
    $python=$unix->find_program("python");
    $binary="/usr/share/artica-postfix/bin/dnsfw-debug.py";
    $ARRAY=unserialize(base64_decode($_GET["dnsfw-checker"]));
    if(!isset($ARRAY["RULE_CHECKER_DOMAIN"])){$ARRAY["RULE_CHECKER_DOMAIN"]="www.ibm.com";}
    if(!isset($ARRAY["RULE_CHECKER_SRC"])){$ARRAY["RULE_CHECKER_SRC"]="192.168.1.100";}
    if(!isset($ARRAY["RULE_CHECKER_QTYPE"])){$ARRAY["RULE_CHECKER_QTYPE"]="A";}
    $t=$ARRAY["t"];
    $tfile=PROGRESS_DIR."/$t.html";
    $SourceIPAddr=$ARRAY["RULE_CHECKER_SRC"];
    $domainname=$ARRAY["RULE_CHECKER_DOMAIN"];
    $QueryType=$ARRAY["RULE_CHECKER_QTYPE"];
    $cmd="$python $binary \"$SourceIPAddr\" \"$domainname\" \"$QueryType\" >$tfile 2>&1";
    writelogs_framework("\"$cmd\"" ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function firewall_events(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/unbound.log.tmp";
    $sourceLog="/var/log/dns-firewall.log";
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];

    $cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        $cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog|$tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/unbound.log.cmd", $cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);
}

