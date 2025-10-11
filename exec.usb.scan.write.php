<?php
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["VERBOSE"]=false;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.nginx.inc');
include_once(dirname(__FILE__).'/ressources/class.freeweb.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.reverse.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.resolv.conf.inc');
include_once(dirname(__FILE__).'/ressources/class.usb-scan.inc');
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="--expand"){is_expand($argv[2]);exit;}

$unix=new unix();


$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$time=$unix->PROCCESS_TIME_MIN($pid);
	build_progress(110, "service Already Artica task running PID $pid since {$time}mn");
	if($GLOBALS["OUTPUT"]){echo "service Already Artica task running PID $pid since {$time}mn\n";}
	return;
}
@file_put_contents($pidfile, getmypid());
if($GLOBALS["VERBOSE"]){echo "Verbosed !!!\n";}

if(system_is_overloaded()){
    build_progress(110, "{OVERLOADED_SYSTEM} ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]})");
    die();
}

build_progress(50, "Scanning");

$usb=new usbscan();
$datas=$usb->disks_list();
build_progress(80, "Scanning");
@file_put_contents("/usr/share/artica-postfix/ressources/usb.scan.inc", $datas);
@unlink("/usr/share/artica-postfix/ressources/usb.scan.serialize");
@file_put_contents("/usr/share/artica-postfix/ressources/usb.scan.serialize", @serialize($usb->SERIALIZED));
@chmod("/usr/share/artica-postfix/ressources/usb.scan.inc",0755);
@chmod("/usr/share/artica-postfix/ressources/usb.scan.serialize",0755);
include_once("/usr/share/artica-postfix/ressources/usb.scan.inc");
build_progress(100, "{success}");


function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/exec.usb.scan.write.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function is_expand($mount){
    $GLOBALS["VERBOSE"]=true;
    $usb=new usbscan();
    echo $usb->isExpand($mount)."\n";

}
