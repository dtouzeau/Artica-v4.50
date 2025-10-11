<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}


function uninstall(){
	$time=time();
	$unix=new unix();
	$INSTALL_DIR="/usr/share/phpldapadmin";
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
	}
	
	$cacheFile=PROGRESS_DIR."/phpldapadmin.status";
	@unlink($cacheFile);
	$rm=$unix->find_program("rm");
	progress("{uninstalling}","Remove files and folder",30);
	progress("{uninstalling}","Cleaning filesystem",30);
	shell_exec("$rm -rf $INSTALL_DIR");
	progress("{uninstalling}","Cleaning filesystem",40);	
	progress("{uninstalling}","Learning Artica to the removed software",80);
	shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose --".time()." >/dev/null 2>&1");
	shell_exec("/etc/init.d/artica-status restart --force >/dev/null 2>&1");
	progress("{uninstalling} {done}","Done...",100);
	
}

function install(){
	
	$filename="phpldapadmin-1.2.3.tgz";
	$version_down="1.2.3";
	$uri="http://articatech.net/download/phpldapadmin-1.2.3.tgz";
	$INSTALL_DIR="/usr/share/phpldapadmin";
	$time=time();
	$unix=new unix();
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		if($unix->PROCCESS_TIME_MIN($pid,10)<2){return;}
	}
	
	$cacheFile=PROGRESS_DIR."/phpldapadmin.status";
	@unlink($cacheFile);
	$rm=$unix->find_program("rm");
	$cp=$unix->find_program("cp");
	$TMPDIR=$unix->TEMP_DIR()."/$time";
	$FINAL_TARGET_DIR=null;
	$TARGET_PATH="$TMPDIR/$filename";
	progress("{downloading} 1.2.3 version","Downloading $filename",30);
	progress("{downloading} 1.2.3 version","Temporary directory = $TMPDIR",30);
	$curl=new ccurl("$uri");
	$php=$unix->LOCATE_PHP5_BIN();
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	@mkdir($TMPDIR);
	if(!$curl->GetFile($TARGET_PATH)){
		progress("{failed}",$curl->error,100);
		shell_exec("$rm -rf $TMPDIR");
		return;
	}
	
	@mkdir("/usr/share/phpldapadmin",0755,true);
	progress("{downloading} $version_down version {success}","success saving $TARGET_PATH ",50);
	progress("{extracting} $version_down version","Extracting $TARGET_PATH ",55);
	$tar=$unix->find_program("tar");
	exec("$tar xvf $TARGET_PATH -C /$TMPDIR/ 2>&1",$results);
	
	foreach ($results as $index=>$line){
		progress("{extracting} $version_down version",$line,60);
	}
	
	$dir=$unix->dirdir($TMPDIR);
	while (list ($index, $line) = each ($dir) ){
		progress("{search} {directory}",$line,65);
		if(preg_match("#phpldapadmin#", $line)){
			progress("{search} {directory}","Found directory $line",65);
			$FINAL_TARGET_DIR=$line;
			break;
		}
	}
	
	if($FINAL_TARGET_DIR==null){
		progress("{extracting} $version_down version {failed}","Unable to find a suitable directory",100);
		shell_exec("$rm -rf $TMPDIR");
		return;
	}
	
	progress("{installing} $version_down version","Copy the content of $FINAL_TARGET_DIR",70);
	exec("$cp -rfdv  $FINAL_TARGET_DIR/* $INSTALL_DIR/ 2>&1",$resultsA);
	while (list ($index, $line) = each ($resultsA) ){ progress("{installing} 5.5.88 version",$line,70); }
	progress("{installing} $version_down version","Removing the $TMPDIR directory",71);
	shell_exec("$rm -rf $TMPDIR");
	
	if(!is_file("$INSTALL_DIR/index.php")){
		progress("{installing} $version_down version {failed}","install from $FINAL_TARGET_DIR failed ",100);
		return;
	}
	
	$ln=$unix->find_program("ln");
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	
	shell_exec("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose --".time()." >/dev/null 2>&1");
	shell_exec("/etc/init.d/artica-status restart --force >/dev/null 2>&1");
	
	progress("{installed}","Done",100);
	shell_exec("$php /usr/share/artica-postfix/exec.phpldapadmin.php --build");
	
	sleep(2);
	shell_exec("/etc/init.d/artica-webconsole restart --force");
	
	
	
}

function progress($title,$text,$pourc){
	if(trim($text)==null){return;}
	if($GLOBALS["VERBOSE"]){echo "{$pourc}% ".getmypid()." ".date("Y-m-d H:i:s")."] $text\n";}
	$cacheFile=PROGRESS_DIR."/phpldapadmin.status";
	$data=unserialize(@file_get_contents($cacheFile));
	echo "{$pourc}% - $title\n";
	echo "$text\n";
	$data["TITLE"]=$title;
	$data["POURC"]=$pourc;
	$data["LOGS"][]=getmypid()." [".date("Y-m-d H:i:s")."] $text";
	@file_put_contents($cacheFile, serialize($data));
	@chmod($cacheFile,0755);
	
	
}