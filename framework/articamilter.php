<?php
// Patch License on 2 Nov 2020
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
if(isset($_GET["artica-notifs-events"])){artica_notifs_searchInSyslog();exit;}

if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["export"])){export();exit;}
if(isset($_GET["csv"])){export_csv();exit;}


if(isset($_GET["syslog"])){artica_milter_searchInSyslog();exit;}


$f=array();
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function restart(){
    $unix=new unix();
    $unix->framework_execute("exec.smtpd.php --restart",
    "articanotifs.progress",
    "articanotifs.log");
}
function uninstall(){

    $unix=new unix();
    $unix->framework_execute("exec.artica-milter.php --uninstall",
        "articamilter.progress",
        "articamilter.log");
}
function install(){

    $unix=new unix();
    $unix->framework_execute("exec.artica-milter.php --install",
        "articamilter.progress",
        "articamilter.log");
}
function export(){
    $unix=new unix();
    $unix->framework_execute("exec.artica-milter.php --export",
        "articamilter.progress",
        "articamilter.log");
}

function export_csv(){
    $unix=new unix();
    $unix->framework_execute("exec.artica-milter.php --csv",
        "articamilter.progress",
        "articamilter.log");
}

function artica_milter_searchInSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $TERM=$_GET["syslog"];

    $file_result=PROGRESS_DIR."/articamilter.syslog";
    $max=1500;
    $search=".*?$TERM.*?";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/artica-milter.log |$tail -n $max >$file_result 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

