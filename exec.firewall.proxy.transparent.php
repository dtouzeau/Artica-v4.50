<?php
$GLOBALS["NOTPROXY"]=false;
$GLOBALS["MASQUERADED"]=false;
$GLOBALS["MARKLOG"]="-m comment --comment \"ArticaSquidTransparent\"";
$GLOBALS["MANGLE"]="/usr/sbin/iptables -t mangle";
$GLOBALS["NOFW"]=false;
$GLOBALS["SINGLE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--single#",@implode(" ",$argv))){$GLOBALS["SINGLE"]=true;}
if(preg_match("#--nofw#",@implode(" ",$argv))){$GLOBALS["NOFW"]=true;}

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}





function uninstall(){
    remove_service("/etc/init.d/artica-firewall");
}

function build(){
    uninstall();
}

function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}