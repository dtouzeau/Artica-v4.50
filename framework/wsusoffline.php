<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["uninstall"])){UNINSTALL();exit;}
if(isset($_GET["install"])){INSTALL();exit;}
if(isset($_GET["reconfigure"])){RECONFIGURE();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["execute"])){EXECUTE();exit;}
if(isset($_GET["storage"])){STORAGE();exit;}
if(isset($_GET["clean-timestamps"])){CLEAN_TIMESTAMPS();exit;}



writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function RECONFIGURE(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wsusoffline.php --build >/dev/null 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function EXECUTE(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$su=$unix->find_program("su");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.reconfigure.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.reconfigure.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wsusoffline.php --build --exec >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function STORAGE(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$su=$unix->find_program("su");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.storage.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.storage.log";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wsusoffline.php --storage >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function CLEAN_TIMESTAMPS(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$su=$unix->find_program("su");

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.storage.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.storage.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wsusoffline.php --clean-timestamps >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}


function status(){
	
	@unlink("/usr/share/artica-postfix/ressources/logs/web/wsusoffline.state");
	$unix=new unix();
	$pid=$unix->PIDOF_PATTERN("/download-updates.bash");
	if($unix->process_exists($pid)){
		$array["PID"]=$pid;
		$array["PIDTIME"]=$unix->PROCESS_UPTIME($pid);
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/wsusoffline.state", serialize($array));
	}
	$pid=$unix->PIDOF_PATTERN("wget.*?--append-output=.*?/log/download.log");
	if($unix->process_exists($pid)){
		$array["PID"]=$pid;
		$array["PROC"]="{downloading}";
		$array["PIDTIME"]=$unix->PROCESS_UPTIME($pid);
		$f=explode("\n",@file_get_contents("/usr/share/wsusoffline/log/download.log"));
		foreach ($f as $line){
			if(!preg_match("#^[0-9]+[A-Z][\.\s]+([0-9]+)%\s+[0-9\.]+[A-Z]\s+#", $line,$re)){continue;}
			$array["PROGRESS"]=$re[1];
		}
		
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/wsusoffline.state", serialize($array));
		
	}
	
	
	
	
}


function INSTALL(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.install.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wsusoffline.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}

function UNINSTALL(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/wsusoffline.install.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.wsusoffline.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}






?>