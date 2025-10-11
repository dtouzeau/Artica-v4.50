<?php
$GLOBALS["SCHEDULE_ID"]=0;
$GLOBALS["FORCE"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');


if(is_array($argv)){
    if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
    if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
    if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
    if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
    if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}


runProc();




function uninstall(){

    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service --uninstall-speedtest");

}

function install(){
    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -install-speedtest");
}

function runProc(){
    $unix=new unix();
    shell_exec("/usr/sbin/artica-phpfpm-service -run-speedtest");

}

