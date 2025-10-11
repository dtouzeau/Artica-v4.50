<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["VERBOSE"]=true;
}

xstart();


function xstart(){
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/exec.pdns_control.php.pid";
	$pidtime="/etc/artica-postfix/pids/exec.pdns_control.php.time";
	@mkdir(dirname($pidtime),0755,true);
	
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid)){
		if($GLOBALS["VERBOSE"]){echo "Already running pid $pid\n";}
		exit();
	}
	
	$Time=$unix->file_time_min($pidtime);
	if($Time<5){
		if($GLOBALS["VERBOSE"]){echo "{$Time}mn, need 5mn\n";}
		exit();
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$pdns_control=$unix->find_program('pdns_control');
	$rec_control=$unix->find_program('rec_control');
	
	
	shell_exec("$pdns_control --config-dir=/etc/powerdns remotes >/etc/artica-postfix/settings/Daemons/PowerDNSRemotes");
	
	
}
