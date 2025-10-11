<?php
$GLOBALS["VERBOSE"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.auth.tail.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.tail.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}


if($argv[1]=="--squid"){var_log_squid();exit();}




function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/varlog.squid.progress";
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	sleep(1);

}


function var_log_squid(){
	$GLOBALS["TITLENAME"]="Squid-cache logs location";
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
		build_progress("{failed}",110);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	
	
	$OrgPath="/var/log/squid";
	$OrgDir=$OrgPath;
	$VarLogSquidLocation=$sock->GET_INFO("VarLogSquidLocation");
	if(trim($VarLogSquidLocation)==null){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} No destination defined\n";
		build_progress("{checking}",110);
		return;
	}
	
	if(is_link($OrgDir)){$OrgDir=readlink($OrgDir);}
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Original directory `$OrgDir`\n";
	
	build_progress("{checking}",10);
	
	if($OrgDir==$VarLogSquidLocation){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already defined...\n";
		build_progress("{done}",110);
		return;
	}
	
	build_progress("{creating_directory}",110);
	
	@mkdir($VarLogSquidLocation,0755,true);
	
	
	if(!is_dir($VarLogSquidLocation)){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $VarLogSquidLocation permission denied...\n";
		build_progress("{failed}",110);
		return;
	}
	
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	$chown=$unix->find_program("chown");
	$t=time();
	@touch("$VarLogSquidLocation/$t");
	if(!is_file("$VarLogSquidLocation/$t")){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $VarLogSquidLocation permission denied...\n";
		build_progress("{failed}",110);
		return;
	}
	@unlink("$VarLogSquidLocation/$t");
	
	build_progress("{apply_permissions}",15);
	@chmod($VarLogSquidLocation,0755);
	@chown($VarLogSquidLocation, "squid");
	@chgrp($VarLogSquidLocation, "squid");
	build_progress("Copy sources files",20);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Copy $OrgDir to $VarLogSquidLocation\n";
	system("$cp -rp $OrgDir/* $VarLogSquidLocation/");
	build_progress("Remove sources files",30);
	system("$rm -rf $OrgDir");
	build_progress("{linking} $OrgPath",40);
	system("$ln -sf $VarLogSquidLocation $OrgPath");
	
	build_progress("{checking} $OrgPath",45);
	if(!is_link($OrgPath)){
		build_progress("{linking} $OrgPath {failed}",110);
		@mkdir($OrgPath,0755,true);
		@chmod($OrgPath,0755);
		@chown($OrgPath, "squid");
		@chgrp($OrgPath, "squid");
		return;
	}
	
	$NewLink=@readlink($OrgPath);
	echo "New Link $NewLink\n";
	if($NewLink<>$VarLogSquidLocation){
		echo "No match $VarLogSquidLocation\n";
		build_progress("{linking} $OrgPath {failed}",110);
		
	}
	
	
	system("$chown -h squid:squid $OrgPath");
	build_progress("Restarting service",80);
	system("/etc/init.d/squid restart");
	build_progress("{done}",100);
}
