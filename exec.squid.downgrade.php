<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["DUMP"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#reconfigure-count=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE_COUNT"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ext.tarcompress.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');




if(count($argv)==0){exit();}


downgrade($argv[1]);


function downgrade($file){
	
	$unix=new unix();
	
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		Events("??%: A process already exists PID $pid");
		return;
	}
	
	@file_put_contents($pidFile, getmypid());	
	
	
	$workdir="/home/squid/downgrade";
	$gzf="/home/squid/downgrade/$file";
	@mkdir("/home/squid/downgrade",0755,true);
	
	
	Events("0%: Ask to update package name $file");
	Events("1%: downloading $file");
	if(!is_dir($workdir)){
		Events("100%: Failed,  $workdir Permission denied");
		exit();
	}
	if(is_file($gzf)){@unlink($gzf);}
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	Events("5%: PLEASE WAIT,PLEASE WAIT,PLEASE WAIT.....downloading $file");
	$curl=new ccurl("$URIBASE/download/old-squid/$file");
	$curl->NoHTTP_POST=true;
	$curl->ProgressFunction="downgrade_prg";
	$curl->WriteProgress=true;
	if(!$curl->GetFile($gzf)){
		Events("100%: Failed to download $curl->error");
		exit();
	}
	
	if(!is_file($gzf)){
		Events("100%: Failed to download permission denied on disk");
		exit();
	}
	$size=@filesize($gzf);
	$size=$size/1024;
	$size=$size/1024;
	Events("10%: ". basename($gzf)." ".round($size,2)." MB");
	Events("10%: Testing $gzf");
	if($GLOBALS["VERBOSE"]){echo "Open TAR...\n";}
	$tar = new tar();
	if(!$tar->openTar($gzf)){
		Events("100%: Failed archive seems corrupted..");
		exit();
	}
	Events("10%: Testing $gzf success");
	Events("15%: Start upgrade procedure...");
	Events("16%: Stopping Squid-Cache...");
	shell_exec("/etc/init.d/squid stop");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$rm=$unix->find_program("rm");
	$tar=$unix->find_program("tar");
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	Events("17%: Removing $squidbin...");
	@unlink($squidbin);
	$f[]="/lib/squid3";
	$f[]="/usr/share/squid-langpack";
	$f[]="/usr/share/squid3";
	foreach ($f as $num=>$dir){
		Events("20%: Removing $dir directory...");
		shell_exec("$rm -rf $dir >/dev/null 2>&1");
	}
	Events("50%: Installing...");
	
	shell_exec("$tar xf $gzf -C / >/dev/null");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	if(!is_file($squidbin)){
		Events("100%: Failed archive seems corrupted, please restart again or contact or support team...");
		exit();
	}
	
	$ver=$unix->squid_version();
	Events("60%: New Squid-cache version $ver");
	
	
	Events("65%: Reconfiguring parameters");
	shell_exec("$php5 /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null");
	Events("70%: Starting Squid-Cache");
	shell_exec("/usr/sbin/artica-phpfpm-service -start-proxy");
	Events("80%: Refresh Artica with the new version...");
	shell_exec("/etc/init.d/artica-process1 start");
	Events("90%: Restarting watchdogs...");
	system("/etc/init.d/cache-tail restart");
	system("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	Events("100%: Done...");
	Events("-------------------------------------------------------------");
	Events("----------------     Squid Cache V.$ver    ------------------");
	Events("-------------------------------------------------------------");
}



function Events($text){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}
	
	$unix=new unix();
	$unix->events($text,dirname(__FILE__)."/ressources/logs/web/squid.downgrade.html",false,$sourcefunction,$sourceline);
	@chmod(dirname(__FILE__)."/ressources/logs/web/squid.downgrade.html",0755);
}

function downgrade_prg( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}
	 
	if ( $progress > $GLOBALS["previousProgress"]){
		Events("Downloading: ". $progress."%, please wait...");
		$GLOBALS["previousProgress"]=$progress;
	}
}