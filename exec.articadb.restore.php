<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/class.settings.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");





restore($argv[1]);

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;

	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}
	if($GLOBALS["VERBOSE"]){echo "******************** {$pourc}% $text ********************\n";}
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/squid.articadb.restore.progress";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);

}


function restore($filename){
	$unix=new unix();
	$sock=new sockets();
	$TMP=$unix->FILE_TEMP();
	$filenameBase=basename($filename);
	
	if(!is_file($filename)){
		echo "$filename no such file\n";
		build_progress("{failed}",110);
		
	}
	
	$tmpf=$unix->FILE_TEMP();
	build_progress("{uncompress} $filenameBase",10);
	if(!$unix->uncompress($filename, $tmpf)){
		@unlink($filename);
		build_progress("{uncompress} $filenameBase {failed}",110);
		return;
	}
	@unlink($filename);
	build_progress("{importing} $tmpf",50);
	
	$q=new mysql_squid_builder();

	$nice=$unix->EXEC_NICE();
	$mysql=$unix->find_program("mysql");
	$gzip=$unix->find_program("gzip");
	$nohup=$unix->find_program("nohup");
	$echo=$unix->find_program("echo");
	$rm=$unix->find_program("rm");
	$php=$unix->LOCATE_PHP5_BIN();

	$sh[]="#!/bin/sh";
	$sh[]="$echo \"$mysql -> $filenameBase\"";
	$sh[]="$nice $mysql $q->MYSQL_CMDLINES -f squidlogs < $tmpf";
	$sh[]="$rm $TMP.sh";
	$sh[]="\n";
	@file_put_contents("$TMP.sh", @implode("\n", $sh));
	@chmod("$TMP.sh",0755);
	
	
	build_progress(10,"Starting restore $filenameBase - ". basename("$TMP.sh")." ");
	system("$nohup $TMP.sh >$TMP.txt 2>&1 &");
	sleep(1);
	$PID=$unix->PIDOF_PATTERN("$TMP.sh");
	echo "Running PID $PID\n";
	while ($unix->process_exists($PID)) {
		build_progress(50,"Starting restoring $filenameBase");
		sleep(3);
		$PID=$unix->PIDOF_PATTERN("$TMP.sh");
		echo "Running PID $PID\n";
	}
	
	echo @file_get_contents("$TMP.txt")."\n";
	@unlink("$TMP.sh");
	@unlink("$TMP.txt");
	build_progress(50,"{restore} {done} $filenameBase");
	
	build_progress(50,"{restore} Analyze Hourly tables");
	system("$php /usr/share/artica-postfix/exec.squid.stats.hours.php --force --verbose");
	build_progress(60,"{restore} Repair Hourly tables");
	system("$php /usr/share/artica-postfix/exec.squid.stats.hours.php --repair --force --verbose");
	build_progress(70,"{restore} Repair Table days");
	system("$php /usr/share/artica-postfix/exec.squid.stats.repair.php --tables-day --repair --force --verbose");
	build_progress(100,"{restore} Done");
}

