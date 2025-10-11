<?php
$GLOBALS["CACHE_FILE"]="/etc/artica-postfix/iptables-hostspot.conf";
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
// squid.statistics.php?webfiltering-clients=yes

if(isset($_GET["webfiltering-clients"])){webfiltering_clients();exit;}
if(isset($_GET["apply-progress"])){apply_progress();exit;}
if(isset($_GET["install-progress"])){install_progress();exit;}
if(isset($_GET["uninstall-progress"])){uninstall_progress();exit;}
if(isset($_GET["status"])){services_status();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);




function services_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --microhotspot --nowachdog >/usr/share/artica-postfix/ressources/logs/web/microhotspot.status";
	shell_exec($cmd);
	
}

function webfiltering_clients(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/squid.statistics.webfiltering.members.refresh.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/squid.statistics.webfiltering.members.refresh.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	@chmod($GLOBALS["CACHEFILE"],0777);
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.squid.statistics.WEBFILTERING.members.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");

}

function apply_progress(){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/microhotspot.apply.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/microhotspot.apply.log";
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
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/microhotspot.install.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/microhotspot.install.log";
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
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/microhotspot.install.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/microhotspot.install.log";
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
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.webauth.restart.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/squid.webauth.restart.log";
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
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/webauth.rules.restore.progress.txt";


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
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/hostpot.reconfigure.web.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/hostpot.reconfigure.web.logs";
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
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/hostpot.reconfigure.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/hostpot.reconfigure.logs";
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

