<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function install(){
    $unix=new unix();
    $unix->framework_execute("exec.arpd.php --install","arpd.progress","arpd.log");
}

function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.arpd.php --uninstall","arpd.progress","arpd.log");
}

