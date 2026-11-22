<?php
// Post SP10 - 13/10/2026
ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	
	$GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["simulate"])){cicap_simulate();exit;}
if(isset($_GET["icap-events"])){searchlogs_icap();exit;}
if(isset($_GET["status"])){status();exit;}

if(isset($_GET["sandbox-file"])){sandbox_file();exit;}
if(isset($_GET["sandbox-events"])){searchlogs_sandbox();exit;}
if(isset($_GET["clients-scan"])){clients_scan();exit;}

foreach ($_GET as $a=>$b){
    $c[]="$a -> $b";
}
writelogs_framework("unable to understand ".@implode(", ",$c)." query !!!!! ",__FUNCTION__,__FILE__,__LINE__);

function clients_scan(){
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/proxy/icap/watchdog");
}



function cicap_simulate(){
    $unix=new unix();
    $fname=base64_encode($_GET["simulate"]);
    $php5=$unix->LOCATE_PHP5_BIN();
    writelogs_framework("exec.c-icap.php --simulate $fname" ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$php5 /usr/share/artica-postfix/exec.c-icap.php --simulate $fname");


}


function sandbox_file(){
    $file=base64_encode($_GET["sandbox-file"]);
    $unix=new unix();
    $unix->framework_exec("exec.c-icap.sandbox.php --upload $file &");
}

function searchlogs_sandbox(){
    $search=trim(base64_decode($_GET["sandbox-events"]));
    $target_file=PROGRESS_DIR."/sandbox.log";
    $source_file="/var/log/squid/icap-sandbox.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){
        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}
function searchlogs_icap(){
    $search=trim(base64_decode($_GET["icap-events"]));
    $target_file=PROGRESS_DIR."/icap.log";
    $source_file="/var/log/squid/icap.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=intval($_GET["rp"]);
    if($rp==0){ $rp=500;}
    if($rp>1500){ $rp=1500;}

    if($search==null){
        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}

function status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --c-icap --nowachdog >/usr/share/artica-postfix/ressources/logs/web/cicap.status 2>&1";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}