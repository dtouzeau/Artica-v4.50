<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["exec"])){execute();exit;}
if(isset($_GET["run-backup"])){execute_backup();exit;}
if(isset($_GET["run-backup-exec"])){run_backup_exec();exit;}

writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);


function execute(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$md5=$_GET["md5"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.mailbox.migration.php --member $md5 >/dev/null &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	
}
function execute_backup(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$nohup=$unix->find_program("nohup");
	$md5=$_GET["md5"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.offlineimap.php --backup-md5 $md5 >/dev/null &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
	
}
function run_backup_exec(){
	$unix=new unix();
	$md5=$_GET["md5"];	
	$pidfile="/var/run/offlineimap-$md5.pid";
	if(!is_file($pidfile)){
		writelogs_framework("$pidfile -> no such file",__FUNCTION__,__FILE__,__LINE__);	
		return;
	}
	$pid=@file_get_contents($pidfile);	
	writelogs_framework("$pidfile -> $pid",__FUNCTION__,__FILE__,__LINE__);	
	
	if($unix->process_exists($pid)){
		$timemin=$unix->PROCCESS_TIME_MIN($pid);
		echo "<articadatascgi>$timemin</articadatascgi>";
		return;
	}	
	writelogs_framework("$pidfile -> FALSE",__FUNCTION__,__FILE__,__LINE__);	
	
	
}