<?php
$GLOBALS["TITLENAME"]="Zabbix Agent";
$GLOBALS["OUTPUT"]=true;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--restart"){restart();exit();}

function restart(){
    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -restart-zabbix");
}
function install(){
    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -install-zabbix");
}
function uninstall(){
    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -uninstall-zabbix");
}

function start(){
    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -start-zabbix");
}
function stop(){
    $unix=new unix();
    $unix->go_exec("/usr/sbin/artica-phpfpm-service -stop-zabbix");
}

?>