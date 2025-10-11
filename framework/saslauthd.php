<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	



function status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --saslauthd";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function restart(){
    $unix=new unix();
    $unix->framework_execute("exec.saslauthd.php --restart","saslauth.progress","saslauth.log");
}

