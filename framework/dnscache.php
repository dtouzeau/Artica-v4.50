<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["uninstall"])){UNINSTALL();exit;}
if(isset($_GET["install"])){INSTALL();exit;}
if(isset($_GET["status"])){STATUS();exit;}
if(isset($_GET["restart"])){RESTART();exit;}
if(isset($_GET["watchdog"])){watchdog();exit;}
writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);

function STATUS(){
    $unix=new unix();
    $unix->framework_exec("exec.status.php --dnscache");

}
function watchdog(){
    $unix=new unix();
    $unix->framework_exec("exec.dnscache.php --watchdog");
}


function UNINSTALL(){
    $unix=new unix();
    $unix->framework_execute("exec.dnscache.php --uninstall","dnscache.progress","dnscache.log");

}

function INSTALL():bool{
    $unix=new unix();
    writelogs_framework("INSTALLING DNS CACHE",__FUNCTION__,__FILE__,__LINE__);
    if(is_file("/etc/artica-postfix/DoNotUseLocalDNSCache")){
        @unlink("/etc/artica-postfix/DoNotUseLocalDNSCache");
    }

   return $unix->framework_execute("exec.dnscache.php --install","dnscache.progress","dnscache.log");
}

function RESTART():bool{
	$unix=new unix();
	return $unix->framework_execute("exec.dnscache.php --restart","dnscache.progress","dnscache.log");

}





?>