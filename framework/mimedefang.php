<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["reload-tenir"])){reload_tenir();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["postfix-milter"])){postfix_milter();exit;}
if(isset($_GET["getramtmpfs"])){getramtmpfs();exit;}
if(isset($_GET["progress"])){restart_progress();exit;}
if(isset($_GET["resend-backup"])){resend_backup();exit;}
if(isset($_GET["resend-quarantine"])){resend_quarantine();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["TrustedNetworks"])){TrustedNetworks();exit;}
if(isset($_GET["spamass-test"])){spamassassin_tests();exit;}
if(isset($_GET["urlscan"])){urlscan();exit;}
if(isset($_GET["urls-resend"])){urlsresend();exit;}
if(isset($_GET["schedules"])){schedules();exit;}



writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function service_cmds(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mimedefang.php 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/mimedefang $cmds 2>&1",$results);
	
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}
function reload(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function spamassassin_tests(){
	if(!is_numeric($_GET["spamass-test"])){$_GET["spamass-test"]=null;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/spamassassin.analyze.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/spamassassin.analyze.logs";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.spamassassin.php --spam-tests {$_GET["spamass-test"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function schedules(){

    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.php --schedules >/dev/null 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function uninstall(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/mimedefang.reconfigure.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}


function TrustedNetworks(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/spamassassin.TrustedNetworks.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/spamassassin.TrustedNetworks.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.spamassassin.php --trusted >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function restart_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.php --compile-progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}



function resend_backup(){
	$id=$_GET["resend-backup"];
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.resend.progress.$id";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.resend.progress.$id.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.backup.resend.php $id >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function urlscan(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.urls.php --xstart >/dev/null 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function urlsresend(){
    $id = $_GET["urls-resend"];
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.urls.php --resend $id >/dev/null 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function resend_quarantine(){
	$id=$_GET["resend-quarantine"];
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.resend.progress.$id";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/mimedefang.resend.progress.$id.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.mimedefang.quarantine.resend.php $id >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function reload_tenir(){
	writelogs_framework("Reloading mimedefang...",__FUNCTION__,__FILE__,__LINE__);	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	exec("$php5 /usr/share/artica-postfix/exec.mimedefang.php 2>&1",$results);
	exec("/etc/init.d/mimedefang reload 2>&1",$results);
	writelogs_framework("Reloading mimedefang done",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
	
}

function restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mimedefang.php 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/mimedefang restart 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";	
	
}
function postfix_milter(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.postfix.maincf.php --milters 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}



function status(){
	shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --mimedefang --nowachdog >/usr/share/artica-postfix/ressources/logs/web/mimedefang.status 2>&1");
}

function getramtmpfs(){
	$dir="/var/spool/MIMEDefang";
	if($dir==null){return;}
	$unix=new unix();
	$df=$unix->find_program("df");
	$cmd="$df -h \"$dir\" 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$df -h \"$dir\" 2>&1",$results);
	foreach ($results as $key=>$value){
		
		if(!preg_match("#tmpfs\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.A-Z]+)\s+([0-9\.]+)%\s+.*?MIMEDefang#", $value,$re)){
			writelogs_framework("$value no match",__FUNCTION__,__FILE__,__LINE__);
			continue;
		}
		
		writelogs_framework("{$re[2]}:{$array["PURC"]}%",__FUNCTION__,__FILE__,__LINE__);
			$array["SIZE"]=$re[1];
			$array["PURC"]=$re[4];
			echo "<articadatascgi>".base64_encode(serialize($array))."</articadatascgi>";
			return;
		
	}
		
}
