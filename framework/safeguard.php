<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["connect"])){connect();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["license"])){license();exit;}
if(isset($_GET["save-license"])){license_save();exit;}
if(isset($_GET["info-domains"])){info_domains();exit;}
if(isset($_GET["test-join"])){test_join();exit;}
if(isset($_GET["unjoin"])){unjoin();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function install(){
    $unix=new unix();
    $unix->framework_execute("exec.arpd.php --install","arpd.progress","arpd.log");
}

function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.arpd.php --uninstall","arpd.progress","arpd.log");
}

function status():bool{
    $unix=new unix();
    $unix->framework_exec("exec.status.php --vasd");
    return true;
}
function connect():bool{
    $unix=new unix();
    $Index=intval($_GET["connect"]);
    $unix->framework_execute("exec.vasd.php --connect $Index","vasd.progress","vasd.log");
    return true;
}
function test_join():bool{
    $unix=new unix();
    $unix->framework_exec("exec.vasd.php --testjoin");
    return true;
}
function unjoin():bool{
    $unix=new unix();
    $unix->framework_execute("exec.vasd.php --unjoin","vasd.progress","vasd.log");
    return true;
}
function info_domains(){
    $out=PROGRESS_DIR."/vastool-info-domains";
    $cmd="/opt/quest/bin/vastool info domains >$out 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function license():bool{
    $out=PROGRESS_DIR."/vastool-license";
    shell_exec("/opt/quest/bin/vastool license -i >$out 2>&1");
    return true;
}
function license_save():bool{
    $filepath=ARTICA_ROOT."/ressources/conf/upload/{$_GET["save-license"]}";
    if(!is_file($filepath)) {
        writelogs_framework($filepath . " no such file", __FUNCTION__, __FILE__, __LINE__);
        $filepath = dirname(__FILE__) . "/ressources/conf/upload/'{$_GET["save-license"]}'";
        if (!is_file($filepath)) {
            writelogs_framework($filepath . " no such file", __FUNCTION__, __FILE__, __LINE__);
            return false;
        }
    }


    $unix=new unix();
    $unix->framework_exec("exec.vasd.php --license ".base64_encode($filepath));

    return true;
}