<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="Kernel Optimization";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--reboot#",implode(" ",$argv),$re)){$GLOBALS["REBOOT"]=true;}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["REBOOT"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');



start();
die();

function build_progress($pourc,$text){
	$cachefile="/usr/share/artica-postfix/ressources/logs/web/system.memory.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}




function start(){
    $unix=new unix();
    $sync=$unix->find_program("sync");
    $echo=$unix->find_program("echo");
    $php=$unix->LOCATE_PHP5_BIN();
    $overcommit_memory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("overcommit_memory"));
    $overcommit_ratio=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("overcommit_ratio"));
    build_progress(15,"{ratio} {$overcommit_ratio}%, Commit $overcommit_memory");
    echo "Tells any files in cache on RAM to write to disk now\n";
    system($sync);
    build_progress(50,"{ratio} {$overcommit_ratio}%, Commit $overcommit_memory");
    echo "Drops all caches from RAM\n";
    system("$echo 3 > /proc/sys/vm/drop_caches");
    system("$echo $overcommit_memory >/proc/sys/vm/overcommit_memory");
    system("$echo $overcommit_ratio >/proc/sys/vm/overcommit_ratio");
    system($sync);
    system("$echo 3 > /proc/sys/vm/drop_caches");
    build_progress(90,"{reconfiguring}");
    system("/usr/share/artica-postfix/bin/articarest -sysctl");
    build_progress(100,"{done}");
}
