<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["status"])){status();exit;}
if(isset($_GET["sync-freewebs"])){sync_freewebs();exit;}
if(isset($_GET["access-events"])){access_events();exit;}
if(isset($_GET["freshclam-status"])){freshclam_status();exit;}
if(isset($_GET["yararules"])){yararules();exit;}
if(isset($_GET["clamd-reconfigure"])){clamd_reconfigure();exit;}
if(isset($_GET["freshclam-events"])){freshclam_events();exit;}
if(isset($_GET["clamd-events"])){clamd_events();exit;}
if(isset($_GET["manual-update"])){manual_update();exit;}
if(isset($_GET["remove-all"])){remove_all();exit;}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
clamav_syslog("Unable to understand query !!!!!!!!!!!");
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);





$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/clamav.updates.progress";
$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/clamav.updates.progress.txt";

function clamd_reconfigure():bool{
	$unix=new unix();
    return $unix->framework_execute("exec.clamd.php --reconfigure","clamd.progress","clamd.progress.logs");
}

function remove_all(){
    $unix=new unix();
    $unix->framework_execute("exec.clamd.php --remove","clamd.progress","clamd.log");
}






function yararules() {
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/clamav.yararules.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/clamav.yararules.progress.logs";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.yararules.update.php --force --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function manual_update(){
    $filename=$_GET["manual-update"];
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/clamav.update.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/clamav.update.progress.txt";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.freshclam.php --manu $filename >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}
function freshclam_syslog($text){
    if(!function_exists("syslog")){return false;}
    openlog("freshclam", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "[Artica]: $text");
    closelog();
    return true;
}

function clamav_syslog($text){
    if(!function_exists("syslog")){return false;}
    openlog("clamav-daemon", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}





function status(){
	writelogs_framework("Starting" ,__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();

    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --clamav-patterns";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --clamav >/usr/share/artica-postfix/ressources/logs/web/clamav.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function freshclam_status(){
	
	writelogs_framework("Starting" ,__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --freshclam >/usr/share/artica-postfix/ressources/logs/web/freshclam.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}



function freshclam_events(){
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$MAIN=unserialize(base64_decode($_GET["freshclam-events"]));
	$PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("/", "\/", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
    }

	$max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}


	$SRC=$MAIN["SRC"];
	$DST=$MAIN["DST"];

	$IN=$MAIN["IN"];
	$PID=$MAIN["PID"];
	if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

	if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
	if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
	if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
	if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
	if($MAIN["C"]==0){$TERM_P=$TERM;}


	$mainline="{$PID_P}{$TERM_P}{$IN_P}";
	if($TERM<>null){
		if($MAIN["C"]>0){
			$mainline="($mainline|$TERM)";
		}
	}


    $tfile=PROGRESS_DIR."/freshclam.syslog";
	$search="$mainline";
	$search=trim(str_replace(".*?.*?",".*?",$search));
    if($search==null){
        $cmd="$tail -n $max /var/log/freshclam.log >$tfile 2>&1";
    }else {
        $cmd = "$grep --binary-files=text -i -E '$search' /var/log/freshclam.log |$tail -n $max >$tfile 2>&1";
    }
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/freshclam.syslog.pattern", $search);
	$sh=$unix->sh_command($cmd);
    $unix->go_exec_out($sh);

}




function access_events(){

}
