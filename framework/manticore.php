<?php
// Patch License on 2 Nov 2020
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}

function install():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.manticore.php --install",
        "manticore.install.progress","manticore.install.progress.log");
}
function uninstall():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.manticore.php --uninstall",
        "manticore.install.progress","manticore.install.progress.log");
}
function restart():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.manticore.php --uninstall",
        "manticore.install.progress","manticore.install.progress.log");
}
function status():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.status.php --manticore");
}