<?php
if(isset($_GET["verbose"])){
	ini_set('display_errors', 1);	
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	$GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["redsocks-progress"])){redsocks_progress();exit;}
if(isset($_GET["ss5events"])){ss5events();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);
//-----------------------------------------------------------------------------------------------------------------------------------
function uninstall(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ss5.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/ss5.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ss5.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
//-----------------------------------------------------------------------------------------------------------------------------------
function install(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ss5.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/ss5.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ss5.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

//-----------------------------------------------------------------------------------------------------------------------------------
function restart_progress(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/ss5.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/ss5.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ss5.php --restart --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function service_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --ss5 --nowachdog >/usr/share/artica-postfix/ressources/logs/APP_SS5.status 2>&1");
}
function ss5events(){
	$search=trim(base64_decode($_GET["ss5events"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

	if($search==null){

		$cmd="$tail -n $rp /var/log/ss5/ss5.log 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		exec("$tail -n $rp /var/log/ss5/ss5.log 2>&1",$results);
		@file_put_contents(PROGRESS_DIR."/ss5-events", serialize($results));
		
		return;
	}

	$search=$unix->StringToGrep($search);


	$cmd="$grep --binary-files=text -i -E '$search' /var/log/ss5/ss5.log 2>&1|$tail -n $rp 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);

	@file_put_contents(PROGRESS_DIR."/ss5-events", serialize($results));

}