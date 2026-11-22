<?php
// stamp 01/11/2026
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}

if(isset($_GET["export-db"])){export_database();exit;}
if(isset($_GET["import-backup"])){import_database();exit;}
if(isset($_GET["searchlogs"])){searchlogs();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["schedules"])){schedules();exit;}
if(isset($_GET["schedule"])){schedule();exit;}
if(isset($_GET["searchdebugs"])){searchdebugs();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");


function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.statscom.php --uninstall","stats-com.progress","stats-com.log");
}
function export_database(){
    $unix=new unix();
    $unix->framework_execute("exec.statscom.php --export-db","exportdb.progress","exportdb.progress.txt");
   }
function import_database(){
    $filename=base64_encode($_GET["import-backup"]);
    $unix=new unix();
    $unix->framework_execute("exec.statscom.php --import-db $filename","exportdb.progress","exportdb.progress.txt");

}
function install(){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/stats-com.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/stats-com.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.statscom.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function schedule(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.schedule.progres";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.report.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.pdf.proxy.daily.php --schedule ".escapeshellarg($_GET["schedule"])." >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function schedules(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.report.progres";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/statscom.report.log";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.statscom-stats.php --set-schedules >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function searchdebugs()
{
    $search = $_GET["searchdebugs"];
    $target_file = PROGRESS_DIR . "/statsdebug.log";
    $source_file = "/var/log/statscom-stats.log";

    $unix = new unix();
    $tail = $unix->find_program("tail");
    $grep = $unix->find_program("grep");
    $rp = 500;
    if (is_numeric($_GET["rp"])) {
        $rp = intval($_GET["rp"]);
    }
    if($rp>1500){$rp=1500;}
    if ($search == null) {

        $cmd = "$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
        shell_exec($cmd);
        return;
    }
}

function searchlogs(){
    $search=$_GET["searchlogs"];
    $target_file="/usr/share/artica-postfix/ressources/logs/web/statsredis.log";
    $source_file="/var/log/statsredis.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){

        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    // Pentest 2026-11-04 : motif et nombre de lignes de la requete quotes (injection shell root).
    $cmd="$grep --binary-files=text -i -E ".fw_shell_quote($search)." $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");



}
