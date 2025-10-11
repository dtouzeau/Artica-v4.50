<?php
if(preg_match("#--ouput#",implode(" ",$argv),$re)){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
$GLOBALS["GETPARAMS"]=@implode(" Params:",$argv);
$GLOBALS["CMDLINEXEC"]=@implode("\nParams:",$argv);

include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.groups.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.dansguardian.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squidguard.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.compile.ufdbguard.inc");

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/ressources/class.ufdbguard-tools.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}


compile();

function build_progress($pourc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"dansguardian2.mainrules.progress");
}
function compile(){
	$sock=new sockets();
	$unix=new unix();
	build_progress(15, "{build_parameters}");
	$ufdbguardd_path=$unix->find_program("ufdbguardd");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	$kill=$unix->find_program("kill");
	$ufdb=new compile_ufdbguard();
	$datas=$ufdb->buildConfig();
	if($sock->EnableUfdbGuard()==0){
		build_progress(110, "{disabled} !!!");
		exit();
	}
	
	build_progress(50, "{build_parameters}");
	@file_put_contents("/etc/ufdbguard/ufdbGuard.conf",$datas);
	@file_put_contents("/etc/squid3/ufdbGuard.conf",$datas);
	build_progress(60, "{webfiltering_parameters_was_saved}");
	
	sleep(1);
	build_progress(70, "{apply_permissions}");
	
	shell_exec("$chmod 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/squid3/ufdbGuard.conf");
	shell_exec("$chmod -R 755 /etc/ufdbguard");
	
	shell_exec("$chown -R squid:squid /etc/ufdbguard");
	shell_exec("$chown -R squid:squid /var/log/squid");
	shell_exec("$chown -R squid:squid /etc/squid3");
	shell_exec("$chown -R squid:squid /var/lib/ufdbartica");
	
	sleep(1);
	build_progress(75, "{please_wait_reloading_service}");
	$pid=ufdbguard_pid();
	if(!$unix->process_exists($pid)){
		system("/usr/sbin/artica-phpfpm-service -start-ufdb");
		$pid=ufdbguard_pid();
		if(!$unix->process_exists($pid)){
			build_progress(110, "{starting_service} {failed2} !!!");
		}else{
			build_progress(100, "{starting_service} {success} !!!");
		}
		
		
		return;
	}
	
	
	
	build_progress(80, "{reloading} PID $pid");
	shell_exec("$kill -HUP $pid");
	build_progress(100, "{success}");
	
	
}



function ufdbguard_pid(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/tmp/ufdbguardd.pid");
	if($unix->process_exists($pid)){
		$cmdline=trim(@file_get_contents("/proc/$pid/cmdline"));
		if(!preg_match("#ufdbcatdd#", $cmdline)){return $pid;}
	}
	$ufdbguardd=$unix->find_program("ufdbguardd");
	return $unix->PIDOF($ufdbguardd);
}