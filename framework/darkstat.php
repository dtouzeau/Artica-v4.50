<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if(isset($_GET["reconfigure-silent"])){reconfigure_silent();exit;}
if(isset($_GET["reload-tenir"])){reload_tenir();exit;}
if(isset($_GET["checks"])){checks();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["postfix-milter"])){postfix_milter();exit;}
if(isset($_GET["getramtmpfs"])){getramtmpfs();exit;}
if(isset($_GET["enable"])){enable();exit;}
if(isset($_GET["disable"])){disable();exit;}
writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	


function checks(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$EnableDarkStat=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDarkStat"));
	if($EnableDarkStat==1){
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.darkstat.php --install >/dev/null 2>&1 &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
		
	}else{
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.darkstat.php --uninstall >/dev/null 2>&1 &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);		
		
	}
	
}
function enable(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/darkstat.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/darkstat.progress.txt";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.darkstat.php --install >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}
function disable(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/darkstat.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/darkstat.progress.txt";

	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"],0777);
	@chmod($GLOBALS["LOG_FILE"],0777);

	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.darkstat.php --uninstall >{$GLOBALS["LOG_FILE"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}


function reconfigure_silent(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
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
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --mimedefang --nowachdog",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
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
