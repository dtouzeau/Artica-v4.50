<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["build"])){build();exit;}
if(isset($_GET["threats"])){SearchEventsInSyslog();exit;}
if(isset($_GET["events"])){SearchServiceEventsInSyslog();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);

function build(){
    $unix=new unix();
    $unix->framework_execute("exec.fsmonitor.php --build","fsm.progress",
        "fsm.log");
}

function install(){
    $unix=new unix();
    $unix->framework_execute("exec.fsmonitor.php --install","fsm.progress",
        "fsm.log");
}
function restart(){
    $unix=new unix();
    $unix->framework_execute("exec.fsmonitor.php --restart","fsm.progress",
        "fsm.log");
}
function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.fsmonitor.php --uninstall","fsm.progress",
        "fsm.log");

}
function status(){
    $unix=new unix();
    $unix->framework_exec("exec.status.php --fsm");

}

function SearchEventsInSyslog(){
    $unix=new unix();
    $unix->framework_search_syslog($_GET["threats"],
        "/var/log/monitorfs-scan.log",
        "fsm.threats.syslog","fsm.threats.pattern");
}
function SearchServiceEventsInSyslog(){
    $unix=new unix();
    $unix->framework_search_syslog($_GET["events"],
        "/var/log/artica-monitorfs.debug",
        "fsm.events.syslog","fsm.events.pattern");
}