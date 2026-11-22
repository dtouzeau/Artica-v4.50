<?php
// stamp 01/11/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["syslog"])){searchInSyslog();exit;}
if(isset($_GET["sync"])){synchronize_remote();exit;}
if(isset($_GET["synchronize-remote"])){synchronize_remote();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
exit;

function synchronize_remote(){

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/categories.services.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/categories.services.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);


    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.categories.synchronize.php >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function searchInSyslog():bool{
    $unix=new unix();
    $unix->framework_search_syslog($_GET["search-in-logs"],
        "/var/log/dnscats-categories.log",
        "webfiltering-categories.log","webfiltering-categories.pattern");

    return true;

}