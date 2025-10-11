<?php
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.urgency.disable.progress";
$GLOBALS["VERBOSE"]=false;
$GLOBALS["makeQueryForce"]=false;
$GLOBALS["FORCE"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__)."/ressources/class.squid.manager.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.squid.builder.php");



if($argv[1]=="--on"){ON();exit;}
if($argv[1]=="--off"){OFF();exit;}

function build_progress($text,$pourc){
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}

function ON(){
	$cacheTime="/etc/artica-postfix/pids/".basename(__FILE__);
	$unix=new unix();
	if($unix->ServerRunSince()<3){return;}

	$TimeExec=$unix->file_time_min($cacheTime);
	if($TimeExec<2){exit();}
	@unlink($cacheTime);
	@file_put_contents($cacheTime, time());
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMimeEmergency", 1);
	
	$php=$unix->LOCATE_PHP5_BIN();
    system("/usr/sbin/artica-phpfpm-service -proxy-mimeconf");
	squid_admin_mysql(0, "Rebuilding Proxy service for MIME Emergency (ON)", null,__FILE__,__LINE__);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
}
function OFF(){
	$unix=new unix();
	build_progress("Stamp to OFF",50);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidMimeEmergency", 0);

	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("Rebuilding templates",70);
    system("/usr/sbin/artica-phpfpm-service -proxy-mimeconf");
	squid_admin_mysql(0, "Rebuilding Proxy service for MIME Emergency (OFF)", null,__FILE__,__LINE__);
	$squidbin=$unix->LOCATE_SQUID_BIN();
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
	build_progress("{done}",100);
}