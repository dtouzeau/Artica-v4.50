<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["BYSCRIPT"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--script#",implode(" ",$argv),$re)){$GLOBALS["BYSCRIPT"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');


	$GLOBALS["ARGVS"]=implode(" ",$argv);
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
	if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;status();exit();}
	if($argv[1]=="--pars"){print_r(ParseParams());exit();}
	if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;buildConfig(true);reload();exit();}
		
	
		
function FrmToSyslog($text){

	$LOG_SEV=LOG_INFO;
	if(function_exists("openlog")){openlog("PHP-FPM-INIT", LOG_PID , LOG_SYSLOG);}
	if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
	if(function_exists("closelog")){closelog();}
}

function restart(){
	$scriptlog=null;if($GLOBALS["BYSCRIPT"]){$scriptlog=" by init.d script";}
	if($GLOBALS["VERBOSE"]){echo "restart -> start...\n";}
	$unix=new unix();
	
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
	}
	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	@file_put_contents($pidfile, getmypid());
	FrmToSyslog("Restarting PHP5-FPM daemon$scriptlog...");
	stop(true);

	
	buildConfig(true);
	start(true);	
	
	
}	

function reload(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}	
	$pid=FPM_PID();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Service Stopped...\n";}
		start(true);
		return;
	}	
	
	$kill=$unix->find_program("kill");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Reloading PID $pid...\n";}
	unix_system_HUP($pid);
	
}
	
function stop($aspid=false){
	$scriptlog=null;if($GLOBALS["BYSCRIPT"]){$scriptlog=" by init.d script";}
	$unix=new unix();
	
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
	}
	
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	if(!is_file($daemon_path)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: not installed\n";}
		return;
	}	
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=FPM_PID();
	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Service already stopped...\n";}
		return;
	}	
	$pid=FPM_PID();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$lighttpd_bin=$unix->find_program("lighttpd");
	$kill=$unix->find_program("kill");
	
	
	FrmToSyslog("Stopping PHP-FPM daemon$scriptlog");
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<15;$i++){
		$pid=FPM_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Service waiting pid:$pid $i/5...\n";}
		unix_system_kill($pid);
		sleep(1);
	}	
	
	$pid=FPM_PID();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<15;$i++){
		$pid=FPM_PID();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}	
	
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Service success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: PHP-FPM: Service failed...\n";}
	}	
}



function status(){
	$unix=new unix();
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();	
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
		return;
	}
	
	if(!$GLOBALS["VERBOSE"]){
		$timeExec=$unix->file_time_min($pidtime);
		if($timeExec<15){return;}
	}
	@unlink($pidtime);
	@file_put_contents($pidtime, time());
	@file_put_contents($pidfile, getmypid());	
	
	$pid=LIGHTTPD_PID();
	$unix=new unix();
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Framework service running $pid since {$timepid}Mn...\n";}
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Framework service stopped...\n";}
		start();
		return;
	}
	$MAIN_PID=$pid;
	$phpcgi=$unix->LIGHTTPD_PHP5_CGI_BIN_PATH();
	$kill=$unix->find_program("kill");
	$array=$unix->PIDOF_PATTERN_ALL($phpcgi);
	if(count($array)==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: no php-cgi processes...\n";}
		return;
	}
	foreach ($array as $pid=>$line){
		$username=$unix->PROCESS_GET_USER($pid);
		if($username==null){continue;}
		if($username<>"root"){continue;}
		$time=$unix->PROCCESS_TIME_MIN($pid);
		$arrayPIDS[$pid]=$time;
		$ppid=$unix->PPID_OF($pid);
		if($time>20){
			if($ppid<>$MAIN_PID){
				if($GLOBALS["VERBOSE"]){echo "killing $pid {$time}mn ppid:$ppid/$MAIN_PID\n";}
				unix_system_kill_force($pid);
			}
		}
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: ".count($arrayPIDS)." php-cgi processes...\n";}
	
}


function ParseParams(){
	$unix=new unix();
	if(isset($GLOBALS["PHP-PARAMS"])){return $GLOBALS["PHP-PARAMS"];}
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	exec("$daemon_path -h 2>&1",$array);

	
	foreach ($array as $index=>$line){
		
		if(preg_match("#allow-to-run-as-root#",$line,$re)){
			$GLOBALS["PHP-PARAMS"]["allow-to-run-as-root"]=true;
			$GLOBALS["PHP-PARAMS"]["-R"]=true;
			continue;
		}
		
		if(preg_match("#-([a-zA-Z]),\s+--(.+?)\s+#", $line,$re)){
			$GLOBALS["PHP-PARAMS"][$re[1]]=true;
			$GLOBALS["PHP-PARAMS"][$re[2]]=true;
			continue;
		}
		
		if(preg_match("#-([a-zA-Z]),\s+--(.+?)$#", $line,$re)){
			$GLOBALS["PHP-PARAMS"][$re[1]]=true;
			$GLOBALS["PHP-PARAMS"][$re[2]]=true;
			continue;
		}
				
		if(preg_match("#-([a-zA-Z])\s+#", $line,$re)){
			$GLOBALS["PHP-PARAMS"][$re[1]]=true;
		}
		
	}
	
	return $GLOBALS["PHP-PARAMS"];
	
}

function GetVersion(){
	$unix=new unix();
	if(isset($GLOBALS["GetVersion"])){return $GLOBALS["GetVersion"];}
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	exec("$daemon_path -v 2>&1",$array);
	foreach ($array as $index=>$line){
		if(preg_match("#PHP\s+([0-9\.]+)#", $line,$re)){$GLOBALS["GetVersion"]=$re[1];}
		return $GLOBALS["GetVersion"];
			
			
		}
	
	
}



function start($aspid=false){
	
	$scriptlog=null;if($GLOBALS["BYSCRIPT"]){$scriptlog=" by init.d script";}
	$sock=new sockets();

	
	$unix=new unix();
	
	if(is_file("/etc/artica-postfix/FROM_ISO")){
		if($unix->file_time_min("/etc/artica-postfix/FROM_ISO")<1){return;}
	}
	
	
	$daemon_path=$unix->APACHE_LOCATE_PHP_FPM();
	if(!is_file($daemon_path)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: not installed\n";}
		return;
	}
	
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}	
	
	$pid=FPM_PID();
	$EnablePHPFPM=$sock->GET_INFO("EnablePHPFPM");
	if(!is_numeric($EnablePHPFPM)){$EnablePHPFPM=0;}
	$ZarafaApachePHPFPMEnable=$sock->GET_INFO("ZarafaApachePHPFPMEnable");
	if(!is_numeric($ZarafaApachePHPFPMEnable)){$ZarafaApachePHPFPMEnable=0;}
	
	$EnableArticaApachePHPFPM=$sock->GET_INFO("EnableArticaApachePHPFPM");
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	
	$EnablePHPFPMFreeWeb=$sock->GET_INFO("EnablePHPFPMFreeWeb");
	if(!is_numeric($EnablePHPFPMFreeWeb)){$EnablePHPFPMFreeWeb=0;}
	
	$EnablePHPFPMFrameWork=$sock->GET_INFO("EnablePHPFPMFrameWork");
	if(!is_numeric($EnablePHPFPMFrameWork)){$EnablePHPFPMFrameWork=0;}
	
	
	if($EnableArticaApachePHPFPM==1){$EnablePHPFPM=1;}
	if($ZarafaApachePHPFPMEnable==1){$EnablePHPFPM=1;}
	if($EnablePHPFPMFreeWeb==1){$EnablePHPFPM=1;}
	if($EnablePHPFPMFrameWork==1){$EnablePHPFPM=1;}
	if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){$EnablePHPFPMFreeWeb=1;$EnablePHPFPM=1;}
	
	if($unix->process_exists($pid)){
		if($EnablePHPFPM==0){stop(true);}
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: EnablePHPFPMFrameWork    = $EnablePHPFPMFrameWork\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: EnablePHPFPMFreeWeb      = $EnablePHPFPMFreeWeb\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: ZarafaApachePHPFPMEnable = $ZarafaApachePHPFPMEnable\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: EnableArticaApachePHPFPM = $EnableArticaApachePHPFPM\n";}
	
	if($EnablePHPFPM==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Disabled ( see EnablePHPFPM )\n";}
		@unlink("/etc/monit/conf.d/phpfpm.monitrc");
		return;
	}
	
	$end=null;
	$nohup=$unix->find_program("nohup");
	$ParseParams=ParseParams();
	$parms[]="$daemon_path";
	if(isset($ParseParams["pid"])){
		$parms[]="--pid /var/run/php5-fpm.pid";
	}
	
	
	$parms[]="--fpm-config /etc/php5/fpm/php-fpm.conf";
	
	if(isset($ParseParams["daemonize"])){
		$parms[]="--daemonize";
		$end="&";
	}
	
	//PHP 5.3.10-1ubuntu3.6
	//PHP 5.3.24-1~dotdeb.0
	
	
	if(isset($ParseParams["allow-to-run-as-root"])){
		$parms[]="--allow-to-run-as-root";
	}
	
	if($end<>null){
		$parms[]=$end;
	}
	
	$cmd=@implode(" ", $parms);
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: version:".GetVersion()."\n";}
	FrmToSyslog("Starting PHP-FPM daemon$scriptlog");
	shell_exec($cmd);
	
	
	for($i=0;$i<6;$i++){
		$pid=FPM_PID();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: waiting $i/6...\n";}
		sleep(1);
	}
	
	$pid=FPM_PID();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Success service started pid:$pid...\n";}
		$monit=new monit_unix();
		$monit->MONITOR("PHPFPM");
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Service failed...\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}
	}
	
	
}





function FPM_PID(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file('/var/run/php5-fpm.pid');
	if($unix->process_exists($pid)){return $pid;}
	$bin=$unix->APACHE_LOCATE_PHP_FPM();
	return $unix->PIDOF($bin);
}

function buildConfig($aspid=false){
	$unix=new unix();
	
	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	
	$sock=new sockets();
	$phpfpm=$unix->APACHE_LOCATE_PHP_FPM();
	if(!is_file($phpfpm)){return;}
	$APACHE_USER=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_GROUP=$unix->APACHE_SRC_GROUP();
	$VERSION=GetVersion();
	$AsRoot=false;
	$tr=explode(".", $VERSION);
	$MAJOR=$tr[0];
	$MINOR=$tr[1];
	$REV=$tr[2];
	$process_priority=false;
	$syslog_facility=true;
	$process_max=true;
	if($MAJOR>4){
		if($MINOR>2){
			if($REV>20){
				$process_priority=true;
				
			}
		}
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Parse Parameters\n";}
	$ParseParams=ParseParams();
	$AsRoot=true;
	if(isset($ParseParams["allow-to-run-as-root"])){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Allow run as root TRUE\n";}
		$AsRoot=true;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Allow run as root is disabled\n";}
	}
	$PHPFPMNoSyslog=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHPFPMNoSyslog"));
	$PHPFPMNoProcessMax=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PHPFPMNoProcessMax"));
	
	if(!is_numeric($PHPFPMNoSyslog)){$PHPFPMNoSyslog=0;}
	if(!is_numeric($PHPFPMNoProcessMax)){$PHPFPMNoProcessMax=0;}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: PHPFPMNoSyslog:$PHPFPMNoSyslog\n";}
	
	if($PHPFPMNoSyslog==1){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Disabling process.priority token\n";}
		$syslog_facility=false;
	}
	if($PHPFPMNoProcessMax==1){
		$process_max=false;
	}
	
	$ProcessNice=$unix->GET_PERFS('ProcessNice');
	if(!is_numeric($ProcessNice)){$ProcessNice=19;}
	if($ProcessNice>19){$ProcessNice=19;}
	if($ProcessNice<1){$ProcessNice=19;}
	
	$EnableArticaApachePHPFPM=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaApachePHPFPM"));
	$EnablePHPFPMFreeWeb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePHPFPMFreeWeb"));
	$EnablePHPFPMFrameWork=$sock->GET_INFO("EnablePHPFPMFrameWork");
	$EnableFreeWeb=$sock->GET_INFO("EnableFreeWeb");
	if(!is_numeric($EnablePHPFPMFrameWork)){$EnablePHPFPMFrameWork=0;}
	if(!is_numeric($EnableArticaApachePHPFPM)){$EnableArticaApachePHPFPM=0;}
	if(!is_numeric($EnablePHPFPMFreeWeb)){$EnablePHPFPMFreeWeb=0;}
	if(!is_numeric($EnableFreeWeb)){$EnableFreeWeb=0;}
	if($EnableFreeWeb==0){$EnablePHPFPMFreeWeb=0;}
	if(is_file("/etc/artica-postfix/WORDPRESS_APPLIANCE")){$EnablePHPFPMFreeWeb=1;}
	$EnableWordpressManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement"));
	
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: will run as $APACHE_USER:$APACHE_GROUP\n";}
	$f[]=";Writing by Artica,". date("Y-m-d H:i:s")." file will be erased, change the ".__FILE__." code instead...";
	
	@unlink("/etc/php5/fpm/pool.d/www.conf");
	@unlink("/etc/php5/fpm/pool.d/apache2.conf");
	@unlink("/etc/php5/fpm/pool.d/zarafa.conf");
	@unlink("/etc/php5/fpm/pool.d/framework.conf");
	@unlink("/etc/php5/fpm/pool.d/nginx-authenticator.conf");
	
	if($EnableArticaApachePHPFPM==1){
		$f[]="[www]";
		$f[]="user = $APACHE_USER";
		$f[]="group = $APACHE_GROUP";
		$f[]="listen = /var/run/php-fpm.sock";
		$f[]="listen.mode = 0777";
		$f[]=";listen.allowed_clients = 127.0.0.1";
		if($process_priority){$f[]="process.priority = $ProcessNice";}
		$f[]="pm = dynamic";
		//$f[]="log_level = debug";
		$f[]="pm.max_children = 20";
		$f[]="pm.start_servers = 2";
		$f[]="pm.min_spare_servers = 1";
		$f[]="pm.max_spare_servers = 5";
		$f[]=";pm.process_idle_timeout = 10s;";
		$f[]="pm.max_requests = 80";
		$f[]="pm.status_path = /fpm.status.php";
		$f[]="ping.path = /fpm.ping";
		$f[]=";ping.response = pong";
		$f[]="chdir = /";
		$f[]="";
		@mkdir("/etc/php5/fpm/pool.d",0755,true);
		@file_put_contents("/etc/php5/fpm/pool.d/www.conf", @implode("\n", $f));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: /etc/php5/fpm/pool.d/www.conf done\n";}
	}

	if($EnableWordpressManagement==1){
        $f[]="";
        $f[]="[wordpress]";
        $f[]="user = $APACHE_USER";
        $f[]="group = $APACHE_GROUP";
        $f[]="listen = /var/run/php-wordpress.sock";
        $f[]="listen.mode = 0777";
        $f[]="pm = ondemand";
        $f[]="pm.max_children = 5";
        $f[]="pm.start_servers = 2";
        $f[]="pm.min_spare_servers = 1";
        $f[]="pm.max_spare_servers = 5";
        $f[]=";pm.process_idle_timeout = 10s;";
        $f[]="pm.max_requests = 200";
        $f[]="pm.status_path = /fpm.status.php";
        $f[]="ping.path = /fpm.ping";
        $f[]=";ping.response = pong";
        $f[]="chdir = /";
        $f[]="security.limit_extensions =";
        $f[]="";
    }





	$f=array();
	if($EnablePHPFPMFreeWeb==1){
		$f[]="[apache2]";
		$f[]="user = $APACHE_USER";
		$f[]="group = $APACHE_GROUP";
		$f[]="listen = /var/run/php-fpm-apache2.sock";
		$f[]="listen.mode = 0777";
		$f[]=";listen.allowed_clients = 127.0.0.1";
		if($process_priority){$f[]="process.priority = $ProcessNice";}
		$f[]="pm = dynamic";
		$f[]="pm.max_children = 50";
		$f[]="pm.start_servers = 2";
		$f[]="pm.min_spare_servers = 1";
		$f[]="pm.max_spare_servers = 5";
		$f[]=";pm.process_idle_timeout = 10s;";
		$f[]="pm.max_requests = 60";
		$f[]="pm.status_path = /fpm.status.php";
		$f[]="request_terminate_timeout = 605";
		$f[]="ping.path = /php-fpm-ping";
		$f[]=";ping.response = pong";
		$f[]="chdir = /";
		$f[]="";	
		@file_put_contents("/etc/php5/fpm/pool.d/apache2.conf", @implode("\n", $f));
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: /etc/php5/fpm/pool.d/apache2.conf done\n";}	
	}
	

	$f=array();

		
	$f=array();
	$f[]=";Writing by Artica,". date("Y-m-d H:i:s")." file will be erased, change the ".__FILE__." code instead...";
	$f[]="[global]";
	$f[]="pid = /var/run/php5-fpm.pid";
	$f[]="error_log = /var/log/php.log";
	if($syslog_facility){$f[]="syslog.facility = daemon";}
	if($syslog_facility){$f[]="syslog.ident = php-fpm";}
	$f[]="log_level = ERROR";
	$f[]=";emergency_restart_threshold = 0";
	$f[]=";emergency_restart_interval = 0";
	$f[]=";process_control_timeout = 0";
	if($process_max){$f[]="process.max = 128";}
	if($process_priority){$f[]="process.priority = $ProcessNice";}
	$f[]="daemonize = yes";
	$f[]=";rlimit_files = 1024";
	$f[]=";rlimit_core = 0";
	$f[]="include=/etc/php5/fpm/pool.d/*.conf\n";	
	
	@file_put_contents("/etc/php5/fpm/php-fpm.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: /etc/php5/fpm/php-fpm.conf done\n";}	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: Check settings\n";}
	
	$sock=new sockets();
	exec("$phpfpm -t -y /etc/php5/fpm/php-fpm.conf 2>&1",$results);
	foreach ($results as $index=>$line){
		if(trim($line)==null){continue;}
		if(strpos($line,"unknown entry 'syslog.facility'")>0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: syslog not supported..\n";}
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHPFPMNoSyslog", 1);
			buildConfig();
			return;
		}
		
		if(strpos($line,"unknown entry 'process.max'")>0){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: process.max not supported..\n";}
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PHPFPMNoProcessMax", 1);
			buildConfig();
			return;			
		}
		
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: PHP-FPM: $line\n";}
	}

	
}
?>