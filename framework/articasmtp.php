<?php
// Patch License on 2 Nov 2020
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
if(isset($_GET["artica-notifs-events"])){artica_notifs_searchInSyslog();exit;}
if(isset($_GET["test-notification"])){artica_notifs_tests();exit;}

if(isset($_GET["restart"])){restart();exit;}


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
function artica_notifs_tests(){
    $unix=new unix();
    $unix->framework_execute("exec.smtpd.php --test-notifs",
        "articasmtp.test.progress","articasmtp.test.log");
}

function artica_notifs_searchInSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["artica-notifs-events"]));
    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("/", "\/", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    $search=".*?$TERM.*?";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/artica-postfix/artica-smtpd.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/smtpd.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/smtpd.syslog.pattern", $search);
    shell_exec($cmd);

}

