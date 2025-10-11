<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["uninstall"])){UNINSTALL();exit;}
if(isset($_GET["install"])){INSTALL();exit;}
if(isset($_GET["status"])){STATUS();exit;}
if(isset($_GET["restart"])){RESTART();exit;}
if(isset($_GET["logs"])){search_logs();exit;}
writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);

function STATUS(){
    $unix=new unix();
    $unix->framework_exec("exec.status.php --wazhu-client");
}
function search_logs():bool{
    $unix=new unix();
    $unix->framework_search_syslog($_GET["logs"],
        "/var/ossec/logs/ossec.log",
        "wazhu.client.syslog","");

    return true;
}


function UNINSTALL(){
    $unix=new unix();
    $unix->framework_execute("exec.wazhu.client.php --uninstall","wazhu.client.progress","wazhu.client.progress.log");
}

function INSTALL(){
    $unix=new unix();
    $unix->framework_execute("exec.wazhu.client.php --install","wazhu.client.progress","wazhu.client.progress.log");
}

function RESTART(){
	$unix=new unix();
    $unix->framework_execute("exec.wazhu.client.php --restart","wazhu.client.progress","wazhu.client.progress.log");

}





?>