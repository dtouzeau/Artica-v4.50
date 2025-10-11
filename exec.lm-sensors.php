<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Network traffic probe";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--test"){$GLOBALS["OUTPUT"]=true;test_sensors();exit();}
if($argv[1]=="--enable"){$GLOBALS["OUTPUT"]=true;xenable();exit();}
if($argv[1]=="--disable"){$GLOBALS["OUTPUT"]=true;disable();exit();}

$GLOBALS["OUTPUT"]=true;
xstart();

function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/system.sensors.progress";
	echo "[{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}


function xstart(){
	
	build_progress("Change settings...",10);
	$sock=new sockets();
	$unix=new unix();
	$LMSensorsEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LMSensorsEnable"));
	build_progress("Enabled: $LMSensorsEnable",15);
	if($LMSensorsEnable==1){ xenable(); }
	build_progress("{done}",100);
	
}

function xenable(){
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LMSensorsEnable", 1);
	$echo=$unix->find_program("echo");
	$sensors_detect=$unix->find_program("sensors-detect");
	echo "echo: $echo\nsensors_detect: $sensors_detect\n";
	build_progress("Detect sensors....",20);
	$cmd="$echo \"YES\"|$sensors_detect";
	system($cmd);
	if(is_file("/usr/share/munin/plugins/sensors_")){
		$ln=$unix->find_program("ln");
		shell_exec("$ln -s /usr/share/munin/plugins/sensors_ /usr/share/munin/plugins/sensors_temp");
	}
	
	if(!is_file("/etc/cron.d/lm-sensors")){
		$nice=$unix->EXEC_NICE();
		$php5=$unix->LOCATE_PHP5_BIN();
		$me=__FILE__;
		$cmdline=trim("$nice $php5 $me --test --cron");
		$f[]="MAILTO=\"\"";
		$f[]="0,15,30,45 * * * *  root $cmdline >/dev/null 2>&1";
		$f[]="";
		@file_put_contents("/etc/cron.d/lm-sensors", @implode("\n", $f));
		UNIX_RESTART_CRON();
	
	}	
	
	
}

function disable(){
	$unix=new unix();
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("LMSensorsEnable", 0);
	@unlink("/usr/share/munin/plugins/sensors_temp");
	@unlink("/etc/cron.d/lm-sensors");
	build_progress("{removing}....",20);
	UNIX_RESTART_CRON();
	build_progress("{removing} {done}....",100);
}

function test_sensors(){
	$unix=new unix();
	$q=new mysql();
	$sock=new sockets();
	$cachefile=PROGRESS_DIR."/sensors.array";
	$pidtime="/etc/artica-postfix/pids/exec.lm-sensors.php.time";
	
	$LMSensorsEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LMSensorsEnable"));
	if($LMSensorsEnable==0){
		if(is_file("/etc/cron.d/lm-sensors")){@unlink("/etc/cron.d/lm-sensors");}
		@unlink($cachefile);
		return;
	}
	

	
	if(!$GLOBALS["FORCE"]){
		$time=$unix->file_time_min($pidtime);
		if($time<15){
			events("Current {$time}Mn, require 15Mn...",__FUNCTION__,__LINE__);
			return;
		}
	}
	
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	
	$sensors=$unix->find_program("sensors");
	events("Running sensors...",__FUNCTION__,__LINE__);
	exec("$sensors 2>&1",$results);

	$rows=array();
	while (list ($path, $val) = each ($results) ){
		$HIGH=null;
		if(preg_match("#Adapter:(.*)#i", $val,$re)){
			$adaptater=trim($re[1]);
		}
		
		if(preg_match("#(.*?):\s+\+([0-9\.]+).*?\((.*?)\)#", $val,$re)){
			$KEY=$re[1];
			$TEMP=$re[2];
			$POSZ=$re[3];
			if(preg_match("#high.*?=\s+\+([0-9\.]+)#", $POSZ,$re)){$HIGH=$re[1];}
			if(preg_match("#crit.*?=\s+\+([0-9\.]+)#", $POSZ,$re)){$CRIT=$re[1];}
			if($HIGH==null){$HIGH=$CRIT;}
			$xtime=date("Y-m-d H:i:s");
			$ARRAY[$adaptater][$KEY]["TEMP"]=$TEMP;
			$ARRAY[$adaptater][$KEY]["HIGH"]=$HIGH;
			$ARRAY[$adaptater][$KEY]["CRIT"]=$CRIT;
			$percent=$TEMP/$CRIT;
			$percent=$percent*100;
			$ARRAY[$adaptater][$KEY]["PERC"]=round($percent,2);
			
			if($ARRAY[$adaptater][$KEY]["PERC"]>90){
				squid_admin_mysql(0, "{warning} {$ARRAY[$adaptater][$KEY]["PERC"]}% of temperature reached!", 
				"Adaptater:$adaptater\nType:$KEY\nTemperature: {$TEMP}°C\nCritic:{$CRIT}°C",__FILE__,__LINE__
				
				);
				
				
			}
			
		}
		
	}

events("Saving /usr/share/artica-postfix/ressources/logs/web/sensors.array",__FUNCTION__,__LINE__);	
@file_put_contents(PROGRESS_DIR."/sensors.array", serialize($ARRAY));
@chmod(PROGRESS_DIR."/sensors.array", 0755);
	
	
}
function events($text,$function=null,$line=0){
	if($GLOBALS["VERBOSE"]){
		echo "$function:: $text (L.$line)\n";
		return;
	}
	$filename=basename(__FILE__);
	$classunix=dirname(__FILE__)."/framework/class.unix.inc";
		if(!isset($GLOBALS["CLASS_UNIX"])){
		if(!is_file($classunix)){$classunix="/opt/artica-agent/usr/share/artica-agent/ressources/class.unix.inc";}
				include_once($classunix);
				$GLOBALS["CLASS_UNIX"]=new unix();
	}

	$GLOBALS["CLASS_UNIX"]->events("$filename $function:: $text (L.$line)","/var/log/artica-status.log");
	}
