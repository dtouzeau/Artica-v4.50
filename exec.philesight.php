<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["META_PING"]=false;
$GLOBALS["MECMDS"]=@implode(" ", $argv);
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--meta-ping#",implode(" ",$argv))){$GLOBALS["META_PING"]=true;}



if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}

if($argv[1]=='--run'){echo run()."\n";exit();}

function run(){

	if($GLOBALS["VERBOSE"]){$GLOBALS["FORCE"]=true;}
	if($GLOBALS["FORCE"]){
		ini_set('html_errors',0);
		ini_set('display_errors', 1);
		ini_set('error_reporting', E_ALL);
	}
	$unix=new unix();
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$TimeFile="/etc/artica-postfix/pids/exec.philesight.php.scan_directories.time";
	
	$pid=$unix->PIDOF("/usr/share/artica-postfix/bin/duc");
	if($unix->process_exists($pid)){
        build_progress("{failed} process $pid already exists",110);
	    return;
	}
	$START_TIME=time();
	
	if(!$GLOBALS["FORCE"]){
		if(system_is_overloaded(__FILE__)){
            build_progress("{failed} {OVERLOADED_SYSTEM}",110);
		    return;

		}
	}
    build_progress("{please_wait}, {cleaning_data}...",10);
	$php=$unix->LOCATE_PHP5_BIN();
    system("$php /usr/share/artica-postfix/exec.clean.logs.php --clean-logs");

	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	@mkdir("/usr/share/artica-postfix/img/philesight",0755,true);
	@mkdir("/home/artica/philesight",0755,true);
	$NICE=$unix->EXEC_NICE();
	
	build_progress("{please_wait}, {scaning_directory}...",30);

	if(!is_file("/usr/share/artica-postfix/bin/duc")) {
        build_progress("{please_wait}, {failed} /bin/duc no such binary", 110);
        die();
	}

    if(is_file("/home/artica/philesight/system.db")){@unlink("/home/artica/philesight/system.db");}
	chmod("/usr/share/artica-postfix/bin/duc",0755);

	$cmd="$NICE /usr/share/artica-postfix/bin/duc index --database /home/artica/philesight/system.db --one-file-system \"/\" 2>&1";

	if($GLOBALS["FORCE"]){echo "$cmd\n";}
    build_progress("{please_wait}, {analyze} disk", 50);
	system($cmd);

	if(!is_file("/home/artica/philesight/system.db")){
	    echo "/home/artica/philesight/system.db no such file.\n";
        build_progress("{please_wait}, {failed} system.db no such database", 110);
        return;
	}



    $cmd="$NICE /usr/share/artica-postfix/bin/duc graph --database /home/artica/philesight/system.db --gradient --ring-gap=1 --format=png --palette=classic --size=256 --levels=2 --output=/usr/share/artica-postfix/img/philesight/system-256.png /  \"/\"";
    if($GLOBALS["FORCE"]){echo "$cmd\n";}
    build_progress("{please_wait}, {analyze} disk", 60);
    system($cmd);

	$cmd="$NICE /usr/share/artica-postfix/bin/duc graph --database /home/artica/philesight/system.db --format=png --size=1024 --output=/usr/share/artica-postfix/img/philesight/system.png  \"/\"";
	if($GLOBALS["FORCE"]){echo "$cmd\n";}
    build_progress("{please_wait}, {analyze} disk", 70);
	system($cmd);


	build_progress("{success}",100);
	$Took=$unix->distanceOfTimeInWords($START_TIME,time());
	squid_admin_mysql(2, "directories size scanned ( {took} $Took) ", null,__FILE__,__LINE__);


}

function meta_events($text,$function,$line=0){
	$file=basename(__FILE__);
	$pid=@getmypid();
	$date=@date("H:i:s");
	$logFile="/var/log/artica-meta-agent.log";
	$size=@filesize($logFile);
	if($size>1000000){@unlink($logFile);}
	$f = @fopen($logFile, 'a');
	$text="[$file][$pid] $date $function:: $text (L.$line)\n";
	if($GLOBALS["VERBOSE"]){echo $text;}
	@fwrite($f, $text);
	@fclose($f);
}

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system.dirmon.progress";
	echo "{$pourc}% $text\n";
	$cachefile=$GLOBALS["CACHEFILE"];
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function diskof($path){
	$unix=new unix();
	echo $path ." = ".$unix->DIRDISK_OF($path)."\n";
	
}

function sql_time_min($time){
	$data1 = $time;
	$data2 = time();
	$difference = ($data2 - $data1);
	$results=intval(round($difference/60));
	if($results<0){$results=1;}
	return $results;
}



?>