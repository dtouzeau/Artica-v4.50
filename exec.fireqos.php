#!/usr/bin/php
<?php
$GLOBALS["SERVICE_NAME"]="Local firewall";
$GLOBALS["PERIOD"]=null;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["AS_ROOT"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--period=([0-9]+)([a-z])#", implode(" ",$argv),$re)){$GLOBALS["PERIOD"]=$re[1].$re[2];}
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.templates.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.tcpip-parser.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.nics.inc');
include_once(dirname(__FILE__) . '/ressources/class.os.system.inc');
include_once(dirname(__FILE__) . '/ressources/class.squid.inc');
include_once(dirname(__FILE__) . '/ressources/class.fireqos.inc');

if($argv[1]=="--reconfigure-progress"){$GLOBALS["PROGRESS"]=true;reconfigure_progress();exit;}
if($argv[1]=="--disable-progress"){$GLOBALS["PROGRESS"]=true;disable_progress();exit;}




if($argv[1]=="--reconfigure"){$GLOBALS["PROGRESS"]=true;reconfigure();exit;}
if($argv[1]=="--build"){$GLOBALS["PROGRESS"]=true;reconfigure();exit;}
if($argv[1]=="--scan"){$GLOBALS["PROGRESS"]=true;scanservices();exit;}
if($argv[1]=="--stop"){$GLOBALS["PROGRESS"]=true;stop_service();exit;}
if($argv[1]=="--start"){$GLOBALS["PROGRESS"]=true;start_service();exit;}
if($argv[1]=="--uninstall"){$GLOBALS["PROGRESS"]=true;remove();exit;}


function remove(){
	build_progress("{uninstalling}",20);
	remove_service("/etc/init.d/fireqos");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFireQOS", 0);
	
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE qos_containers","artica_backup");
	$q=new mysql_squid_builder();
	$q->QUERY_SQL("TRUNCATE TABLE qos_sqacllinks");
	
	build_progress("{uninstalling} {success}",100);
}


function install(){
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableFireQOS", 1);
	build_progress("{building_init_script}",20);
	build_init(true);
	scanservices();
	reconfigure_progress();
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


function build_init($noprogress=false){
	
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["echobin"]=$unix->find_program("echo");
	$php=$unix->LOCATE_PHP5_BIN();
	$KERNEL_VERSION=$unix->KERNEL_VERSION();
	$modprobe=$unix->find_program("modprobe");
	$fireqos=$unix->find_program("fireqos");
	$sh[]="#!/bin/sh -e";
	$sh[]="### BEGIN INIT INFO";
	$sh[]="# Builded on ". date("Y-m-d H:i:s");
	$sh[]="# Provides:          fireqos";
	$sh[]="# Required-Start:    \$local_fs";
	$sh[]="# Required-Stop:     \$local_fs";
	$sh[]="# Should-Start:		";
	$sh[]="# Should-Stop:		";
	$sh[]="# Default-Start:     3 4 5";
	$sh[]="# Default-Stop:      0 6";
	$sh[]="# Short-Description: start and stop the FireQOS";
	$sh[]="# Description:       QOS";
	$sh[]="### END INIT INFO";
	$sh[]="FireQOSEnable=1";
	$sh[]="if [ ! -f \"/etc/firehol/fireqos.conf\" ]; then";
	$sh[]="\tFireQOSEnable=0";
	$sh[]="fi";
	$sh[]="";
	$sh[]="";
	$sh[]="case \"\$1\" in";
	$sh[]="start)";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: FireQOS is '\$FireQOSEnable'\"";
	
	$sh[]="\tif [ \$FireQOSEnable -eq 0 ]; then";
	$sh[]="\t\t{$GLOBALS["echobin"]} \"FireQOS: disabled.\"";
	$sh[]="\t\texit 0";
	$sh[]="\tfi";
	$sh[]="";
	
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Starting\"";
	$sh[]="\tif [ -f \"$fireqos\" ]; then";
	$sh[]="\t\t$fireqos start";
	$sh[]="\tfi";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Started\"";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Done\"";
	$sh[]=";;";
	$sh[]="  stop)";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Stopping\"";
	$sh[]="if [ -f \"$fireqos\" ]; then";
	$sh[]="\t$fireqos clear_all_qos";
	$sh[]="fi";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Stopped\"";
	$sh[]=";;";
	$sh[]="  reconfigure)";
	$sh[]="if [ \$FireQOSEnable -eq 1 ]; then";
		$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Reconfiguring\"";
		$sh[]="\t$php ".__FILE__." --reconfigure >/dev/null 2>&1";
		$sh[]=" $fireqos clear_all_qos";
		$sh[]=" $fireqos start";
	$sh[]="fi";
	$sh[]=";;";
	
	$sh[]="  restart)";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Reconfiguring\"";
	$sh[]="\t$php ".__FILE__." --reconfigure >/dev/null 2>&1";
	$sh[]="\t{$GLOBALS["echobin"]} \"FireQOS: Restarting\"";
	$sh[]="\t$fireqos clear_all_qos || true";
	$sh[]="\t$fireqos start || true";
	$sh[]=";;";
	
	
	$sh[]="*)";
	$sh[]=" echo \"Usage: $0 {start ,restart,configure or stop only}\"";
	$sh[]="exit 1";
	$sh[]=";;";
	$sh[]="esac";
	$sh[]="exit 0\n";
	
		
	@file_put_contents("/etc/init.d/fireqos", @implode("\n", $sh));
	@chmod("/etc/init.d/fireqos",0755);
	if(!$noprogress){build_progress("{installing_default_script}...",90);}

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f fireqos defaults >/dev/null 2>&1");
	}
	
	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add fireqos >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 1234 fireqos on >/dev/null 2>&1");
	}	
	
	
	if(!$noprogress){build_progress("{default_script}...{done}",98);}
	
	
}


function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/fireqos.reconfigure.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
	if($GLOBALS["PROGRESS"]){sleep(1);}

}



function reconfigure(){
	
	
	$fire=new FireQOS();
	$fire->build();
}


function reconfigure_progress(){
	$sock=new sockets();
	$unix=new unix();
	$fireqos=$unix->find_program("fireqos");
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress("{building_rules}",50);
	$fire=new FireQOS();
	$fire->build();
	build_init(true);
	build_progress("{stopping_qos}",50);
	shell_exec("$fireqos stop");
	build_progress("{starting_qos}",70);
	shell_exec("$fireqos start");
	build_progress("{done}",100);
}

function stop_service(){
	$unix=new unix();
	$fireqos=$unix->find_program("fireqos");
	build_progress("{stopping_qos}",10);
	if(!is_file("$fireqos")){
		echo "Not installed...\n";
		build_progress("{stopping_qos} {failed}",110);
		return;
	}
	
	system("$fireqos stop");
	build_progress("{stopping_qos} {success}",100);
	
}
function start_service(){
	$unix=new unix();
	$fireqos=$unix->find_program("fireqos");
	build_progress("{starting_qos}",10);
	if(!is_file("$fireqos")){
		echo "Not installed...\n";
		build_progress("{starting_qos} {failed}",110);
		return;
	}
	system("$fireqos start");
	build_progress("{starting_qos} {success}",100);

}

function scanservices(){
	$unix=new unix();
	$firehol=$unix->find_program("firehol");
	$f=explode("\n", @file_get_contents($firehol));
	
	while (list ($ip, $line) = each ($f) ){
		if(preg_match('#server_(.+?)_ports="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["server"]["ports"]=$re[2];
			continue;
		}
		if(preg_match('#client_(.+?)_ports="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["client"]["ports"]=$re[2];
			continue;
		}
		if(preg_match('#helper_(.+)="(.+?)"#', $line,$re)){
			if(preg_match("#CAT_CMD#", $re[2])){continue;}
			$array[$re[1]]["helper"]=$re[2];
			continue;
		}		
		
		
	}
	@file_put_contents("/usr/share/artica-postfix/ressources/databases/firehol.services.db", base64_encode(serialize($array)));
	
}

