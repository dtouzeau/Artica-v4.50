<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["switch-status"])){switch_status();exit;}
if(isset($_GET["switch-restart"])){switch_restart();exit;}
if(isset($_GET["switch-reconfigure"])){switch_reconfigure();exit;}

if(isset($_GET["install-switch"])){switch_install();exit;}
if(isset($_GET["switch-main-status"])){switch_main_status();exit;}
if(isset($_GET["DeleteDatabasePath"])){DeleteDatabasePath();exit;}
if(isset($_GET["plug2tap-status"])){plug2tap_status();exit;}
if(isset($_GET["virtual-delete"])){virtual_delete();exit;}
if(isset($_GET["switch-network-restart"])){switch_network_restart();exit;}
if(isset($_GET["switch-remove"])){switch_remove();exit;}
if(isset($_GET["port-reconfigure"])){port_reconfigure();exit;}
if(isset($_GET["port-delete"])){port_delete();exit;}
if(isset($_GET["switch-uninstall"])){switch_uninstall();exit;}




writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);	




function switch_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$switch=$_GET["switch"];
	$switch_init="/etc/init.d/virtualswitch-$switch";
	$switch_pid="/var/run/switch-$switch.pid";
	if(!is_file($switch_init)){
		writelogs_framework("$switch_init no such file",__FUNCTION__,__FILE__,__LINE__);
		$ARRAY["INSTALLED"]=false;
		echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
		return;
	}
	$ARRAY["INSTALLED"]=true;
	$pid=$unix->get_pid_from_file($switch_pid);
	if($unix->process_exists($pid)){
		$ARRAY["RUNNING"]=true;
		$ARRAY["RUNNING_SINCE"]=$unix->PROCESS_UPTIME($pid);
	}else{
		$ARRAY["RUNNING"]=false;
	}
	
	echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
	
}

function switch_uninstall(){
	$switch=$_GET["switch-uninstall"];
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/virtualswitch.uninstall.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/virtualswitch.uninstall.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.vde.php --remove $switch >{$GLOBALS["LOGSFILES"]} >/dev/null 2>&1 &");
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function switch_install(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$switch=$_GET["switch"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.vde.php --install $switch >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function switch_remove(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$switch=$_GET["switch-remove"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.vde.php --remove $switch >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function switch_network_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$switch=$_GET["switch"];
	$cmd=trim("/etc/init.d/virtualnet-$switch restart  >/dev/null 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function switch_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$switch=$_GET["switch"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.vde.php --restart-switch $switch >/dev/null 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
}

function switch_reconfigure(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$switch=$_GET["switch"];
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.vde.php --reconfigure-switch $switch >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function switch_main_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$switch=$_GET["switch-main-status"];
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --vde-uniq $switch --nowachdog 2>&1");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	echo "<articadatascgi>".@implode("\n", $results)."</articadatascgi>";
}

function virtual_delete(){
	$unix=new unix();
	$virtname=$_GET["virtual-delete"];
	$nic=$_GET["nic"];
	$pidfile="/var/run/$virtname.pid";
	$ipbin=$unix->find_program("ip");
	$ifconfig=$unix->find_program("ifconfig");
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	
	$pid=$unix->get_pid_from_file($pidfile);
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){unix_system_kill($pid);sleep(1);}
	if($unix->process_exists($pid)){unix_system_kill_force($pid);sleep(1);}
	
	$cmd="$ipbin route flush table $virtname";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
	$cmd="/usr/share/artica-postfix/bin/rt_tables.pl --remove-name $virtname >/dev/null 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

	
	$cmd="$ifconfig $virtname down";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.vde.php --reconfigure-switch $nic >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function plug2tap_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$virtname=$_GET["plug2tap-status"];
	$pidfile="/var/run/$virtname.pid";
	$pid=$unix->get_pid_from_file($pidfile);
	
	if(!$unix->process_exists($pid)){
		$ARRAY["RUNNING"]=false;
		echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
		return;
	}
	
	$ARRAY["RUNNING"]=true;
	$ARRAY["RUNNING_SINCE"]=$unix->PROCESS_UPTIME($pid);
	echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
	
}

function localdbsize(){
	$unix=new unix();
	$cachefile="/usr/share/artica-postfix/LocalDatabases/dbsize.xp";
	if(is_file($cachefile)){$time=$unix->file_time_min($cachefile);if($time>30){@unlink($cachefile);}}
	if(!is_file($cachefile)){
		$size=$unix->DIRSIZE_KO("/usr/share/artica-postfix/LocalDatabases");
		if($size>1000){@file_put_contents($cachefile, $size);}
	}else{
		$size=@file_get_contents($cachefile);
	}
	
	echo "<articadatascgi>$size</articadatascgi>";
	
}
function DeleteDatabasePath(){
	$DeleteDatabasePath=base64_decode($_GET["DeleteDatabasePath"]);
	if($DeleteDatabasePath==null){return;}
	if(!is_dir($DeleteDatabasePath)){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$cmd=trim("$rm -rf $DeleteDatabasePath >/dev/null &");
	@unlink("/usr/share/artica-postfix/LocalDatabases/dbsize.xp");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}

function port_delete(){
	$unix=new unix();
	$ID=$_GET["port-delete"];
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.vde.php --remove-port $ID >/dev/null 2>&1 &");	
}

function port_reconfigure(){
	$unix=new unix();
	$ID=$_GET["port-reconfigure"];
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("/etc/init.d/virtualport-$ID stop");
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.vde.php --build-port $ID >/dev/null 2>&1 &");
	
}