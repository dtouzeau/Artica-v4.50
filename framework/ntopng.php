<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["redis-restart"])){redis_restart();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function install(){
	$migration=null;


	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ntopng.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ntopng.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ntopng.install.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function uninstall(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ntopng.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ntopng.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /usr/sbin/artica-phpfpm-service -uninstall-ntopng >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function restart(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/restart-ntopng.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/restart-ntopng.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ntopng.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	writelogs_framework("DONE" ,__FUNCTION__,__FILE__,__LINE__);
}
function redis_restart(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/restart-ntopng.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/restart-ntopng.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ntopng.php --redis-restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	writelogs_framework("DONE" ,__FUNCTION__,__FILE__,__LINE__);
}


function status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php /usr/share/artica-postfix/exec.status.php --ntopng --nowachdog >/usr/share/artica-postfix/ressources/logs/web/ntopng.status 2>&1");
	
}


