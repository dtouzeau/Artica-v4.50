<?php
$GLOBALS["CACHE_FILE"]="/etc/artica-postfix/iptables-hostspot.conf";
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["remove-session"])){remove_session();exit;}
if(isset($_GET["restart-web"])){restart_progress();exit;}
if(isset($_GET["apply-progress"])){apply_progress();exit;}
if(isset($_GET["install-progress"])){install_progress();exit;}
if(isset($_GET["uninstall-progress"])){uninstall_progress();exit;}
if(isset($_GET["status"])){services_status();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function remove_session(){
	$path=base64_decode($_GET["remove-session"]);
	if(is_file($path)){@unlink($path);}
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	system("/usr/sbin/artica-phpfpm-service -reload-proxy");
}


function services_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --microhotspot --nowachdog >/usr/share/artica-postfix/ressources/logs/web/microhotspot.status";
	shell_exec($cmd);
	
}

function restart_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.web.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.web.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup /etc/init.d/microhotspot restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");

}

function apply_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.apply.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.apply.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php /usr/share/artica-postfix/exec.microhotspot.install.php --build --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);	
	
}

function install_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php /usr/share/artica-postfix/exec.microhotspot.install.php --install --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);	
	
}


function uninstall_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/microhotspot.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$nohup $php /usr/share/artica-postfix/exec.microhotspot.install.php --uninstall --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);	

}
function wizard_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/squid.webauth.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/squid.webauth.restart.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.wifidog.php --wizard --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);	
	
}


function restore_rules_progress(){
	$filename=$_GET["filename"];
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/webauth.rules.restore.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/webauth.rules.restore.progress.txt";


	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.wifidog.php --restore $filename >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);


}
function backup_rules_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/webauth.rules.bakckup.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/webauth.rules.bakckup.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.wifidog.php --backup >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);
	
	
}


function reconfigure_web_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.web.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.web.logs";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.hostpot-web.php --restart --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);

}

function emergency_on(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.logs";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.wifidog.php --emergency-on --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);	
	
	
}
function emergency_off(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.logs";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.wifidog.php --emergency-off --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);


}
function reconfigure_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/hostpot.reconfigure.logs";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.wifidog.php --reconfigure-progress --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	shell_exec($cmd);	
	
}

