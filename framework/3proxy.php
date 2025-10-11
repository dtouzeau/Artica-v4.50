<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(posix_getuid()<>0){echo "<H1>???</H1>";die();}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["restart"])){restart_progress();exit;}
if(isset($_GET["systemusers"])){systemusers();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["chowndirs"])){chowndirs();exit;}
if(isset($_GET["ufdb-real"])){searchInSyslog();exit;}



foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --3proxy >/usr/share/artica-postfix/ressources/logs/web/3proxy.status 2>&1";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}



function install(){
	$migration=null;
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/3proxy.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/3proxy.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.3proxy.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function uninstall(){
	$migration=null;
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/3proxy.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/3proxy.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.3proxy.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function restart_progress(){
	
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/3proxy.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/3proxy.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.3proxy.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
		
	
}
function reload(){

	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/3proxy.progress";
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/3proxy.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["CACHEFILE"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.3proxy.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}
function systemusers(){
	
	
	$f=explode("\n",@file_get_contents("/etc/passwd"));
	foreach ($f as $num=>$line){
		if(!preg_match("#(.+?):x:([0-9]+):([0-9]+):#", $line,$re)){continue;}
		$ARRAYU["{$re[2]}:{$re[3]}"]=$re[1];
		
		
	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SystemUsers", serialize($ARRAYU));
	@chmod("/etc/artica-postfix/settings/Daemons/SystemUsers",0755);
	
}
function searchInSyslog(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	
	$year=date("Y");
	$month=date("m");
	$day=date("d");
	
	$curfile="log.$year.$month.$day";
	
	$targetfile="/usr/share/artica-postfix/ressources/logs/3proxy.log.tmp";
	$sourceLog="/var/log/3proxy/$curfile";
	$grep=$unix->find_program("grep");
	
	$rp=intval($_GET["rp"]);
	$query=$_GET["query"];
	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog| $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/3proxy.log.cmd", $cmd);
	shell_exec($cmd);
	@chmod("$targetfile",0755);

}
