<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(posix_getuid()<>0){echo "<H1>???</H1>";die();}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["events"])){rustdesk_events();exit;}
if(isset($_GET["change-key-perform"])){rustdesk_chkey();}

function restart():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.rustdesk.php --restart","rustdesk.restart.progress","rustdesk.restart.progress.log");
}
function install():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.rustdesk.php --install","rustdesk.install.progress","rustdesk.install.progress.log");
}
function uninstall():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.rustdesk.php --uninstall","rustdesk.install.progress","rustdesk.install.progress.log");
}
function rustdesk_chkey():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.rustdesk.php --chkey","rustdesk.install.progress","rustdesk.install.progress.log");
}
function status():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.status.php --rustdesk");
}
function rustdesk_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");

    $MAIN=unserialize(base64_decode($_GET["events"]));
    $ROOT=PROGRESS_DIR;


    list($date,$TERM,$max)=$unix->syslog_pattern($MAIN);


    $search="$date.*?$TERM";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/rustdesk/rustdesk.log |$tail -n $max >$ROOT/rustdesk.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents(PROGRESS_DIR."/rustdesk.syslog.pattern", $search);
    shell_exec($cmd);
    return true;
}