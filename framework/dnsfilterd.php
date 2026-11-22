<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["ufdb-real"])){ufdb_real();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["purge"])){purge();exit;}
if(isset($_GET["rest-enable"])){REST_ENABLE();exit;}
if(isset($_GET["rest-disable"])){REST_DISABLE();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --dnsfilterd >/usr/share/artica-postfix/ressources/logs/dnsfilterd.status 2>&1";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function purge(){
    $migration=null;

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsfilterd.php --purge >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}



function ufdb_real(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$targetfile="/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.tmp";
	$sourceLog="/var/log/dnsfilterd/ufdbguardd.log";
	$grep=$unix->find_program("grep");
	$LinesZ="\]\s+(PASS|BLOCK|REDIR|BLOCK-LD|BLOCK-FATAL|RED)";
	$rp=intval($_GET["rp"]);
	$query=$_GET["query"];
	$cmd="$grep --binary-files=text -Ei \"$LinesZ\" $sourceLog|$tail -n $rp >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep --binary-files=text -Ei \"$LinesZ\" $sourceLog|$grep --binary-files=text -Ei \"$pattern\" | $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/dnsfilterd.log.cmd", $cmd);
	shell_exec($cmd);
	@chmod("$targetfile",0755);
}

function restart(){
	$migration=null;


	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/dnsfilterd.restart.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/dnsfilterd.restart.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsfilterd.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function REST_ENABLE(){
    $migration=null;
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/dnsfilterd.service.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/dnsfilterd.service.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsfilterd.php --rest-enable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function REST_DISABLE(){
    $migration=null;
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/dnsfilterd.service.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/dnsfilterd.service.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsfilterd.php --rest-disable >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function uninstall(){
	$migration=null;
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/dnsfilterd.service.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/dnsfilterd.service.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsfilterd.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function install(){
	$migration=null;

	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/dnsfilterd.service.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/dnsfilterd.service.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsfilterd.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

