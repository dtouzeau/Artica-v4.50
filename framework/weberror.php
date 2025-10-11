<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["uninstall"])){UNINSTALL();exit;}
if(isset($_GET["install"])){INSTALL();exit;}
if(isset($_GET["status"])){STATUS();exit;}
if(isset($_GET["restart"])){RESTART();exit;}
if(isset($_GET["events"])){SearchServiceEventsInSyslog();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function STATUS(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$php /usr/share/artica-postfix/exec.status.php --web-error-page --nowachdog";
    shell_exec($cmd);
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
}


function SearchServiceEventsInSyslog(){
    $unix=new unix();
    $unix->framework_search_syslog($_GET["events"],
        "/var/log/web-error-page.log",
        "weberror.events.syslog","
        ");
}
?>