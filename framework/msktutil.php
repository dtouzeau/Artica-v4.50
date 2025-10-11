<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install-tgz"])){install_tgz();exit;}

if(isset($_GET["klist"])){klist_kerberos();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	




function install_tgz(){
	$migration=null;


	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/msktutil.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/msktutil.install.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.msktutil.install.php --install {$_GET["key"]} {$_GET["OS"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function klist_kerberos(){
    $unix=new unix();
    $klist=$unix->find_program("klist");
    writelogs_framework("$klist -kt >/usr/share/artica-postfix/ressources/logs/klist.out 2>&1" ,
        __FUNCTION__,__FILE__,__LINE__);
    shell_exec("$klist -kt >/usr/share/artica-postfix/ressources/logs/klist.out 2>&1");

}

