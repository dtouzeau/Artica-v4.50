<?php
// Post SP10 - 13/10/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");


function install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-regex.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}
function uninstall(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-regex.reconfigure.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-regex.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}
?>
