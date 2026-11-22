<?php
// Post SP10 - 13/10/2026
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string','');
ini_set('error_append_string','');
ini_set("log_errors", 1);
ini_set("error_log", "/var/log/php.log");
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["ufdbcat-service-events"])){ufdbcat_service_events();exit;}
if(isset($_GET["services-status"])){ufdbguard_status();exit;}
if(isset($_GET["ufdbweb-events"])){ufdbweb_events();exit;}
if(isset($_GET["unlock-rules"])){unlock_rules();exit;}

foreach ($_GET as $num=>$line){$f[]="`$num=$line`";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");


function ufdbguard_status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --ufdbguardd >/usr/share/artica-postfix/ressources/databases/ALL_UFDB_STATUS 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}
function unlock_rules(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ufdb-http.build.progress.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

}







function ufdbcat_service_events(){
    $search=trim(base64_decode($_GET["search"]));
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    $target_file="/usr/share/artica-postfix/ressources/logs/web/ufdbcat.log";
    if(is_numeric($_GET["rp"])){$rp=$_GET["rp"]+50;}

    if($search==null){

        $cmd="$grep --binary-files=text -i -E '[0-9\s\-\:]+\s+\[[0-9]+\]\s+' /var/log/ufdbcat/ufdbguardd.log | $tail -n $rp >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }



    $search=$unix->StringToGrep($search);


    $cmd="$grep --binary-files=text -i -E '[0-9\s\-\:]+\s+\[[0-9]+\]\s+' /var/log/ufdbcat/ufdbguardd.log|$grep --binary-files=text -i -E '$search' |$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");



}

function ufdbweb_events(){
    $unix=new unix();
    $tail=$unix->find_program("tail");
    $targetfile="/usr/share/artica-postfix/ressources/logs/ufdbweb.log.tmp";
    $sourceLog="/var/log/ufdb-http.log";
    $grep=$unix->find_program("grep");
    $LinesZ=".*";
    $rp=intval($_GET["rp"]);
    $query=$_GET["query"];
    $cmd="$grep --binary-files=text -Ei \"$LinesZ\" $sourceLog|$tail -n $rp >$targetfile 2>&1";

    if($query<>null){
        if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
            $pattern=str_replace(".", "\.", $query);
            $pattern=str_replace("*", ".*?", $pattern);
            $pattern=str_replace("/", "\/", $pattern);
        }
    }
    if($pattern<>null){

        $cmd="$grep --binary-files=text -Ei \"$LinesZ\" $sourceLog|$grep --binary-files=text -Ei \"$pattern\" | $tail -n $rp  >$targetfile 2>&1";
    }
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/ufdbweb.log.cmd", $cmd);
    shell_exec($cmd);
    @chmod("$targetfile",0755);


}