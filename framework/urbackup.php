<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}

writelogs_framework("urbackup... Unable to understand the query",__FUNCTION__,__FILE__,__LINE__);
function status():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.satus.php --urbackup-server","urbackup.exec");
}



function restart():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.urbackup-server.php --restart","urbackup.progress","urbackup.log");
}



?>