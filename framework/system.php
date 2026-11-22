<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }

if(isset($_GET["artica-update"])){artica_update();exit;}
if(isset($_GET["nmap-scan-single"])){nmap_scan_single();exit;}
if(isset($_GET["empty-swap"])){empty_swap();exit;}
if(isset($_GET["roolback-sp"])){roolback_sp();exit;}
if(isset($_GET["roolback-global"])){roolback_global();exit;}
if(isset($_GET["TrackAdmins-install"])){trackadmin_install();exit;}
if(isset($_GET["TrackAdmins-uninstall"])){trackadmin_uninstall();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");

function roolback_global():bool{
    $unix=new unix();
    $version=escapeshellarg($_GET["roolback-global"]);
    $unix->framework_execute("exec.nightly.php --rollback $version","roolback.progress","roolback.progress.txt");
    return true;
}





// searchlogs_kernel() porte dans articarest kernlog/ (GET /system/kernel/events, 2026-11-11).

function trackadmin_uninstall(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/TrackAdmins.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/TrackAdmins.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.trackadmin.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function trackadmin_install(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/TrackAdmins.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/TrackAdmins.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.trackadmin.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function artica_update():bool{
	$unix=new unix();
    $unix->framework_execute(
        "exec.nightly.php --update-now --force --output",
        "artica.updatemanu.progress","artica.updatemanu.log");
    return true;
	
}

function empty_swap(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.emptyswap";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.emptyswap.php.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.swap.empty.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function nmap_scan_single(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/nmap.single.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nmap_single_progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	if($_GET["MAC"]==null){$_GET["MAC"]="00:00:00:00:00:00";}
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nmapscan.php --scan-single ".fw_shell_quote($_GET["MAC"])." ".fw_shell_quote($_GET["ipaddr"])." >>{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function roolback_sp(){

    $sp=intval($_GET["roolback-sp"]);
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/roolback.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/roolback.progress.txt";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"], 0755);
    @chmod($ARRAY["LOG_FILE"], 0755);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.rollback.php $sp >>{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}








