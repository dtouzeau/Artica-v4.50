<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["scandb"])){scandb();exit;}


function uninstall(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.install.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.install.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function restart(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.restart.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php --restart-single >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function scandb(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.scan.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/milter-greylist.scan.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.php --database >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
?>