<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["refresh"])){refresh();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["syslog"])){SearchInSyslog();exit;}

$tt=array();
foreach ($_GET as $key=>$value){
    $tt[]=$key;
}

writelogs_framework("unable to understand query [".@implode($tt,", ")."]...",__FUNCTION__,__FILE__,__LINE__);

function reload(){
    $unix=new unix();
    $unix->framework_exec("exec.quagga.php --reload &","quagga.reload");
}

function SearchInSyslog(){
    $unix=new unix();
    $unix->framework_search_syslog($_GET["syslog"],"/var/log/ospfd.log",
        "ospfd.syslog","ospfd.pattern");
}

function restart(){
    $unix=new unix();
    $unix->framework_execute("exec.quagga.php --restart","ospfd.progress","ospfd.log");
}

function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.quagga.php --uninstall","ospfd.progress","ospfd.log");
}
function install(){
    $unix=new unix();
    $unix->framework_execute("exec.quagga.php --install","ospfd.progress","ospfd.log");
}
function refresh(){
    $unix=new unix();
    $unix->framework_exec("exec.quagga.php --status","ospfd.refresh");
}
function status(){
    $unix=new unix();
    $unix->framework_exec("exec.status.php --quagga","quagga.status");
}