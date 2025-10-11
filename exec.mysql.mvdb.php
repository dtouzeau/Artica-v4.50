<?php
$GLOBALS["FORCE"]=false;$GLOBALS["REINSTALL"]=false;
$GLOBALS["NO_HTTPD_CONF"]=false;
$GLOBALS["NO_HTTPD_RELOAD"]=false;
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["posix_getuid"]=0;
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');


if($argv[1]=="--db"){mvdb($argv[2],$argv[3]);}


function mvdb($database,$encpath){
	
	$GLOBALS["LOGSDB"]=$database;
	$logFile=PROGRESS_DIR."/empty-{$GLOBALS["LOGSDB"]}.txt";
	@unlink($logFile);
	if($database==null){
		ouputz("No database set",__LINE__);
		return;		
	}
	
	
	$unix=new unix();
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
			ouputz("already running PID:$pid",__LINE__);
			return;
		}
		$t=time();
	@file_put_contents($pidfile,getmypid());
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	$path=base64_decode($encpath);
	
	if(!is_link("$MYSQL_DATA_DIR/$database")){
		$original_path="$MYSQL_DATA_DIR/$database";
	}else{
		$original_path=readlink("$MYSQL_DATA_DIR/$database");
	}

	if($original_path==null){
		ouputz("original_path is null, aborting ",__LINE__);
		return;
	}
	
	if($path=="$MYSQL_DATA_DIR/$database"){
		ouputz("$path cannot be the original path `$MYSQL_DATA_DIR/$database`",__LINE__);
		return;
	}
	
	if($path=="$original_path"){
		ouputz("$path cannot be the same path `$original_path`",__LINE__);
		return;		
	}
	$chattr=$unix->find_program("chattr");
	$cp=$unix->find_program("cp");
	$mv=$unix->find_program("mv");
	$ln=$unix->find_program("ln");
	$rm=$unix->find_program("rm");
	$chmod=$unix->find_program("chmod");
	$chown=$unix->find_program("chown");
	@mkdir($path,0755,true);
	ouputz("[0%] Mark $MYSQL_DATA_DIR/$database/* as read only",__LINE__);
	exec2("$chattr -R +i $MYSQL_DATA_DIR/$database");
	ouputz("[5%] copy $MYSQL_DATA_DIR/$database/* to $path/",__LINE__);
	exec2("$cp -rf \"$MYSQL_DATA_DIR/$database/\"* \"$path/\"");
	
	ouputz("[15%] Mark $path/* as write",__LINE__);
	exec2("$chattr -R -i \"$path\"");
	
	if(is_dir("$MYSQL_DATA_DIR/$database-bak")){
		exec2("$chattr -R -i $MYSQL_DATA_DIR/$database-bak");
		exec2("$rm -rf \"$MYSQL_DATA_DIR/$database-bak\"");
	}
	
	ouputz("[35%] move $MYSQL_DATA_DIR/$database to $MYSQL_DATA_DIR/$database-bak",__LINE__);
	exec2("$chattr -R -i \"$MYSQL_DATA_DIR/$database\"");
	exec2("$mv \"$MYSQL_DATA_DIR/$database\" \"$MYSQL_DATA_DIR/$database-bak\"");
	
	if(!is_dir("$MYSQL_DATA_DIR/$database-bak")){
		ouputz("move $MYSQL_DATA_DIR/$database to $MYSQL_DATA_DIR/$database-bak failed, roolback",__LINE__);
		exec2("$chattr -R -i \"$MYSQL_DATA_DIR/$database\"");
		return;
	}
	ouputz("[55%] link $path to $MYSQL_DATA_DIR/$database");
	exec2("$ln -s \"$path\" \"$MYSQL_DATA_DIR/$database\"");
	
	ouputz("[75%] remove $MYSQL_DATA_DIR/$database-bak ",__LINE__);
	exec2("$chattr -R -i \"$MYSQL_DATA_DIR/$database-bak\"");
	exec2("$rm -rf \"$MYSQL_DATA_DIR/$database-bak\"");
	if($original_path<>"$MYSQL_DATA_DIR/$database"){
		exec2("$rm -rf \"$original_path\"");
	}
	ouputz("[85%] Apply permissions on $path",__LINE__);
	exec2("$chmod -R 0755 $path");
	exec2("$chown -R mysql:mysql $path");
	
	
	@unlink(PROGRESS_DIR."/squidlogs.stats");
	ouputz("[100%] Task finish took ".$unix->distanceOfTimeInWords($t,time(),true),__LINE__);
}

function exec2($cmd){
	$t=time();
	$unix=new unix();
	ouputz("Please wait... Executing a new task...",__LINE__);
	ouputz($cmd,__LINE__);
	exec($cmd." 2>&1",$results);
	while (list ($pattern, $line) = each ($results)){
		ouputz($line,__LINE__);
	}
	ouputz("Success took ".$unix->distanceOfTimeInWords($t,time(),true),__LINE__);
}











function ouputz($text,$line){
	if(!isset($GLOBALS["CLASS_UNIX"])){$GLOBALS["CLASS_UNIX"]=new unix();}
	//if($GLOBALS["VERBOSE"]){echo "$text\n";}
	$pid=@getmypid();
	$date=@date("H:i:s");
	
	$logFile=PROGRESS_DIR."/empty-{$GLOBALS["LOGSDB"]}.txt";
	if($GLOBALS["VERBOSE"]){echo "$logFile\n";}
	if(is_file($logFile)){
		$size=@filesize($logFile);
		if($size>1000000){@unlink($logFile);}
	}
	$f = fopen($logFile, 'a');
	fwrite($f, "$date [$pid][$line]: $text\n");
	fclose($f);
	chmod($logFile, 0777);
}