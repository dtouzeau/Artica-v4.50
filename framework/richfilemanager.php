<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(isset($_GET["install"])){richfilemanager_install();exit;}
if(isset($_GET["uninstall"])){richfilemanager_uninstall();exit;}



foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);
function richfilemanager_install(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/RichFileManager.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/RichFileManager.install.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.richfilemanager.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);	
}
function richfilemanager_uninstall(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$hostname=$_GET["hostname"];
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/RichFileManager.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/RichFileManager.install.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);

	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.richfilemanager.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";;
	system($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}

