<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["WATCHDOG"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["UFDBTAIL"]=false;
$GLOBALS["TITLENAME"]="DNS Filter Daemon";
$GLOBALS["AFTER-FATAL-ERROR"]=false;
$GLOBALS["BYSCHEDULE"]=false;
$GLOBALS["HUMAN"]=false;
$GLOBALS["WIZARD"]=false;
$GLOBALS["OUTPUT"]=true;

if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--from-schedule#",implode(" ",$argv),$re)){$GLOBALS["BYSCHEDULE"]=true;}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}
if(preg_match("#--watchdog#",implode(" ",$argv),$re)){$GLOBALS["WATCHDOG"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--ufdbtail#",implode(" ",$argv),$re)){$GLOBALS["UFDBTAIL"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--fatal-error#",implode(" ",$argv),$re)){$GLOBALS["AFTER-FATAL-ERROR"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--human#",implode(" ",$argv),$re)){$GLOBALS["HUMAN"]=true;$GLOBALS["FORCE"]=true;}
if(preg_match("#--wizard#",implode(" ",$argv),$re)){$GLOBALS["WIZARD"]=true;$GLOBALS["FORCE"]=true;}



include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.dnsfilterd.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["AS_ROOT"]=true;

include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.compile.dnsfilterd.inc');

if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--reload"){reload();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--monit"){dnsfilterd_monit();exit();}
if($argv[1]=="--rotate"){rotate();exit();}
if($argv[1]=="--mv-rotate"){move_rotates();exit();}
if($argv[1]=="--statistics"){statistics();exit();}
if($argv[1]=="--stats"){statistics_interface();exit();}
if($argv[1]=="--purge"){purge();exit();}
if($argv[1]=="--rest-disable"){disable_restapi();exit;}
if($argv[1]=="--rest-enable"){enable_restapi();exit;}





function build_progress($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/dnsfilterd.service.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);

}
function build_progress_r($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/dnsfilterd.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);

}
function build_progress_restart($text,$pourc){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/dnsfilterd.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);

}
function rotate(){
	$unix=new unix();
	$pid=PID_NUM();
	$kill=$unix->find_program("kill");
	if($unix->process_exists($pid)){system("kill -USR1 $pid");}
	system("/etc/init.d/dnsfilterd-tail restart");
	move_rotates();
}

function purge(){

    $unix=new unix();
    $mem=new lib_memcached();
    $allKeys=$mem->allKeys();
    $tot=count($allKeys);
    $c=0;
    foreach ($allKeys as $items){
        $c++;echo $items."\n";
      if(preg_match("#^(DNSFILTER|DNSFILTERD)#",$items)){
        $prc=$c/$tot;
        $prc=round($prc);
            if($prc>95){$prc=95;};
          build_progress_r("{removing} $items",$prc);
          $mem->Delkey($items);
      }

    }

    $unbound_control=$unix->find_program("unbound-control");


    $ll=explode(",","A, AAAA, MX, PTR, NS, SOA, CNAME, DNAME, SRV, NAPTR");
    foreach ($ll as $cc){
        $cc=trim($cc);
        build_progress_r("{removing} $cc",98);
        shell_exec("$unbound_control flush $cc");
    }
    build_progress_r("{reloading}",99);
    shell_exec("$unbound_control reload");
    build_progress_r("{done}",100);


}

function move_rotates(){
	$unix=new unix();
	$BackupMaxDaysDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir");
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	
	$files=$unix->DirFiles("/var/log/dnsfilterd","\.log\.[0-9]+$");
	
	foreach ($files as $filename=>$none){
		$filepath="/var/log/dnsfilterd/$filename";
		$filetime=filemtime($filepath);
		$FinalDirectory="$BackupMaxDaysDir/proxy/".date("Y",$filetime)."/".date("m",$filetime)."/".date("d",$filetime);
		$finalFile="dnsfilterd-".date("H-i-s",$filetime).".gz";
		@mkdir($FinalDirectory,0755,true);
		echo "$filepath --> $FinalDirectory/$finalFile\n";
		if(!$unix->compress($filepath, "$FinalDirectory/$finalFile")){
			@unlink("$FinalDirectory/$finalFile");
			squid_admin_mysql(0, "Unable to compress $finalFile for rotation", "Try to compress $filepath to $FinalDirectory/$finalFile\n{$GLOBALS["COMPRESSOR_ERROR"]}");
			continue;
		}
		echo "$filepath --> OK\n";
		@unlink($filepath);
		
	}
	
	
	
	
}



function restart(){
	$unix=new unix();
	build_progress_restart("{stopping_service}",20);
	if(!stop(true,20)){
		build_progress_restart("{stopping_service} {failed}",110);
		return;
	}
	build_progress_restart("{reconfiguring}",50);
	build();
	build_progress_restart("{reconfigure} {done}",55);
	build_progress_restart("{starting_service}",60);
	if(!start(true,60)){
		build_progress_restart("{starting_service} {failed}",110);
		return;
	}
	$unbound_control=$unix->find_program("unbound-control");
	shell_exec("$unbound_control reload");
	build_progress_restart("{starting_service} {success}",100);
}


function stop($aspid=false,$prc=0){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return true;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");
	$chmod=$unix->find_program("chmod");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress_restart("{stopping_service}",$prc++);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		build_progress_restart("{stopping_service}",$prc++);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}
	
	return true;

}


function reload(){
	$unix=new unix();
	build_progress_r("{reconfigure}",20);
	build();
	build_progress_r("{reconfigure} {done}",30);
	if(!is_file("/var/log/dnsfilterd/ufdbguardd.log")){@touch("/var/log/dnsfilterd/ufdbguardd.log");}
	@chown("/var/log/dnsfilterd/ufdbguardd.log", "unbound");
	
	if(!is_dir("/home/artica/SQLITE_DNSFILTER")){@mkdir("/home/artica/SQLITE_DNSFILTER",0755,true);}
	@chown("/home/artica/SQLITE_DNSFILTER", "unbound");
	@chgrp("/home/artica/SQLITE_DNSFILTER", "unbound");
	
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		build_progress_r("{starting_service}",40);
		if(!start(true,40)){
			build_progress_r("{starting_service} {failed}",110);
			return;
		}
		build_progress_r("{starting_service} {success}",100);
		
	}
	
	$kill=$unix->find_program("kill");
	build_progress_r("{reloading} PID $pid",90);
	shell_exec("$kill -HUP $pid");
	
	
	$unbound_control=$unix->find_program("unbound-control");
	shell_exec("$unbound_control reload");
	build_progress_r("{reloading} PID $pid {success}",100);
	
	
}

function build(){
	
	$compile_dnsfilterd=new compile_dnsfilterd();
	$conf=$compile_dnsfilterd->buildConfig();
	if(!is_dir("/etc/dnsfilterd")){@mkdir("/etc/dnsfilterd",0755,true);}
	@file_put_contents("/etc/dnsfilterd/dnsfilterd.conf", $conf);
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/dnsfilterd/dnsfilterd.conf [OK]\n";
	if(!is_dir("/home/artica/SQLITE_DNSFILTER")){@mkdir("/home/artica/SQLITE_DNSFILTER",0755,true);}
	@chown("/home/artica/SQLITE_DNSFILTER", "unbound");
	@chgrp("/home/artica/SQLITE_DNSFILTER", "unbound");
	
	
	
	
	$f=explode("\n",@file_get_contents("/etc/dnsfilterd/dnsfilterd.conf"));
	$CountOfPattern=0;
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#domainlist\s+\"#", $line)){$CountOfPattern++;continue;}
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSFILTERD_NUM_DATABASES",$CountOfPattern);
	
	
	
	$whitelist_src=array();
	$whitelist_dst=array();
	$sql="SELECT * FROM webfilter_whitelists WHERE enabled=1 ORDER BY pattern ";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$results=$q->QUERY_SQL($sql);
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";
	$ipClass=new IP();
	foreach ($results as $index=>$ligne){
		$type=intval($ligne["type"]);
		$pattern=$ligne["pattern"];
		if($type==0){
			if(!$ipClass->isIPAddressOrRange($pattern)){continue;}
			$whitelist_src[]=$pattern;
			continue;
		}
		
		if($type==1){
			$whitelist_dst[]=$pattern;
			continue;
		}
		
	}
	
	@file_put_contents("/etc/unbound/whitelist_src.db",@implode("\n", $whitelist_src));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/unbound/whitelist_src.db [OK]\n";
	
	@file_put_contents("/etc/unbound/whitelist_dst.db",@implode("\n", $whitelist_dst));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/unbound/whitelist_dst.db [OK]\n";
	
	
	
	$blacklist_src=array();
	$blacklist_dst=array();
	$sql="SELECT * FROM webfilter_blacklists WHERE enabled=1 ORDER BY pattern ";
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$results=$q->QUERY_SQL($sql);
	$ztype[0]="{client_source_ip_address}";
	$ztype[1]="{destination_domain}";
	$ipClass=new IP();
	foreach ($results as $index=>$ligne){
		$type=intval($ligne["type"]);
		$pattern=$ligne["pattern"];
		if($type==0){
			if(!$ipClass->isIPAddressOrRange($pattern)){continue;}
			$blacklist_src[]=$pattern;
			continue;
		}
	
		if($type==1){
			$blacklist_dst[]=$pattern;
			continue;
		}
	
	}
	
	@file_put_contents("/etc/unbound/blacklist_src.db",@implode("\n", $blacklist_src));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/unbound/blacklist_src.db [OK]\n";
	
	@file_put_contents("/etc/unbound/blacklist_dst.db",@implode("\n", $blacklist_dst));
	echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/unbound/blacklist_dst.db [OK]\n";

	
	
}


function start($aspid=false,$prc=0){
	$unix=new unix();
	$Masterbin="/opt/dnsfilterd/bin/dnsfilterd";

	if(!is_file($Masterbin)){
		build_progress("{starting_service} not installed",$prc++);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, dnsfilterd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/dnsfilterd/dnsfilterd.pid", $pid);
		@file_put_contents("/var/run/dnsfilterd/ufdbguardd.pid", $pid);
		return true;
	}
	

	$dnsfiltersocks=new dnsfiltersocks();
	$UfdbListenPort=intval($dnsfiltersocks->GET_INFO("UfdbListenPort"));
	$Threads=intval($dnsfiltersocks->GET_INFO("UfdbGuardThreads"));
	if($UfdbListenPort==0){$UfdbListenPort=3979;}
	
	if($Threads==0){$Threads=64;}
	if($Threads>140){$Threads=140;}
	
	if(!is_dir("/var/log/dnsfilterd")){@mkdir("/var/log/dnsfilterd",0755,true);}
	if(!is_dir("/etc/dnsfilterd")){@mkdir("/etc/dnsfilterd",0755,true);}
	if(!is_dir("/var/run/dnsfilterd")){@mkdir("/var/run/dnsfilterd",0755,true);}

	@chmod("/var/run/dnsfilterd",0755,true);

	if($GLOBALS["MONIT"]){

	    if(!is_file("/var/log/dnsfilterd/ufdbguardd.log")){
	        $CONTENT="/var/log/dnsfilterd/ufdbguardd.log No such file!!!\n";
        }else{
	        $CONTENT=@file_get_contents("/var/log/dnsfilterd/ufdbguardd.log");
        }

	    squid_admin_mysql(0,"DNS Filter - last events before start",
        "It seems the DNS Filter is started with the watchdog..\nHere events before starting again the service\n$CONTENT\n",
        __FILE__,__LINE__);
    }

    if(is_file("/var/log/dnsfilterd/ufdbguardd.log")){
        $unix->compress("/var/log/dnsfilterd/ufdbguardd.log","/var/log/dnsfilterd/ufdbguardd.log.".time().".gz");
        @unlink("/var/log/dnsfilterd/ufdbguardd.log");
    }
	if(!is_file("/var/log/dnsfilterd/ufdbguardd.log")){@touch("/var/log/dnsfilterd/ufdbguardd.log");}
	@chown("/var/log/dnsfilterd/ufdbguardd.log", "unbound");
    @chmod("/var/log/dnsfilterd/ufdbguardd.log", 0755);
	
	$f[]="$Masterbin -c /etc/dnsfilterd/dnsfilterd.conf";
	
	if(!is_file("/etc/dnsfilterd/dnsfilterd.conf")){
		$compile_dnsfilterd=new compile_dnsfilterd();
		$conf=$compile_dnsfilterd->buildConfig();
		@file_put_contents("/etc/dnsfilterd/dnsfilterd.conf", $conf);
		echo "/etc/dnsfilterd/dnsfilterd.conf [OK]\n";
	}
	$f[]="-w $Threads";
	$f[]="-p $UfdbListenPort";
	$f[]="-U root";
	$f[]="-N";
	
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	build_progress_r("{starting_service}",$prc++);
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		build_progress_r("{starting_service} $i/5",$prc++);
		if($unix->process_exists($pid)){break;}
	}

	build_progress_r("{starting_service}",$prc++);
	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		shell_exec("/etc/init.d/dnsfilterd-tail restart >/dev/null 2>&1 &");
        squid_admin_mysql(1,"Success to start DNS Filter Daemon...",@file_get_contents("/var/log/dnsfilterd/ufdbguardd.log"),__FILE__,__LINE__);
		return true;
	}else{
        squid_admin_mysql(0,"Failed to start DNS Filter Daemon...",@file_get_contents("/var/log/dnsfilterd/ufdbguardd.log"),__FILE__,__LINE__);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
		return false;
	}


}

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/dnsfilterd/dnsfilterd.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF("/opt/dnsfilterd/bin/dnsfilterd");
}


function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	
	if(!is_file("/usr/lib/python2.7/dist-packages/unboundmodule.py")){
		build_progress("{creating_service} {failed} python-unbound {not_installed}",110);
		return;
	}
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFilterd",1);
	build_progress("{creating_service}",52);
	dnsfilterd_service();
	build_progress("{creating_service}",53);
	dnsfilterd_tail_service();
	build_progress("{starting_service}",54);
	dnsfilterd_monit();
	build_progress("{starting_service}",55);
	system("/etc/init.d/dnsfilterd start");
	build_progress("{starting_service}",56);
	system("/etc/init.d/dnsfilterd-tail");
	$unix->Popuplate_cron_make("dnsfilter-schedule","0 0 * * *",basename(__FILE__)." --rotate");
	$unix->Popuplate_cron_make("dnsfilter-stats","5 * * * *",basename(__FILE__)." --statistics");
	system("/etc/init.d/cron reload");
	build_progress("{restarting_service}",70);
	system("/usr/sbin/artica-phpfpm-service -restart-unbound");
	build_progress("{installing} {succes}",100);


}

function uninstall(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFilterd",0);
	build_progress("{uninstalling}",52);
	remove_service("/etc/init.d/dnsfilterd");
	remove_service("/etc/init.d/dnsfilterd-tail");
	
	build_progress("{uninstalling}",55);
	@unlink("/etc/cron.d/dnsfilter-schedule");
	@unlink("/etc/cron.d/dnsfilter-stats");
	system("/etc/init.d/cron reload");
	
	build_progress("{uninstalling}",60);
	if(is_file("/etc/monit/conf.d/APP_DNSFILTERD.monitrc")){
		@unlink("/etc/monit/conf.d/APP_DNSFILTERD.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	build_progress("{restarting_service}",70);
	system("/usr/sbin/artica-phpfpm-service -restart-unbound");
	build_progress("{uninstalling} {done}",100);
}


function dnsfilterd_monit(){
	
	$f[]="check process APP_DNSFILTERD";
	$f[]="with pidfile /var/run/dnsfilterd/dnsfilterd.pid";
	$f[]="start program = \"/etc/init.d/dnsfilterd start --monit\"";
	$f[]="stop program =  \"/etc/init.d/dnsfilterd stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	$f[]="";
	
	$f[]="check process APP_DNSFILTERD_TAIL";
	$f[]="with pidfile /var/run/dnsfilterd-tail.pid";
	$f[]="start program = \"/etc/init.d/dnsfilterd-tail start --monit\"";
	$f[]="stop program =  \"/etc/init.d/dnsfilterd-tail stop --monit\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	$f[]="";
	
	
	@file_put_contents("/etc/monit/conf.d/APP_DNSFILTERD.monitrc", @implode("\n", $f));
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	
	
	
	
}



function dnsfilterd_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/dnsfilterd";
	$php5script=basename(__FILE__);
	$daemonbinLog="DNS filter Service";
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	$Threads=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UfdbGuardThreads"));
	if($Threads==0){$Threads=64;}
	if($Threads>140){$Threads=140;}

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         dnsfilterd";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]=". /lib/lsb/init-functions";
	$f[]="DAEMON=\"/opt/dnsfilterd/bin/dnsfilterd\"";
	$f[]="DAEMON_ARGS=\"-c /etc/dnsfilterd/dnsfilterd.conf -U root -w $Threads\"";
	$f[]="PIDFILE=\"/var/run/dnsfilterd/dnsfilterd.pid\"";

	$f[]="case \"\$1\" in";
	$f[]=" start)";

	$PossibleDirs[]="/var/lib/ftpunivtlse1fr";
	$PossibleDirs[]="/var/lib/ufdbartica";
	$PossibleDirs[]="/var/lib/squidguard";
	$PossibleDirs[]="/var/lib/squidguard/security";
	$PossibleDirs[]="/var/run/dnsfilterd";


	foreach ($PossibleDirs as $Directory){
		$f[]="\tmkdir -p $Directory || true";
		$f[]="\tchmod 0755 $Directory || true";
		$f[]="\tchown squid:squid $Directory || true";
	}
	$f[]="\tchmod 0755 /etc/dnsfilterd/dnsfilterd.conf || true";
	$f[]="\tchown squid:squid /var/lib/squidguard/security/cacerts || true";
	$f[]="\t$php /usr/share/artica-postfix/$php5script --start \$2 \$3";

	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" rotatelog)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --rotatelog \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|rotatelog} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));


	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");

	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}
function dnsfilterd_tail_service(){
	$unix=new unix();
	$sock=new sockets();
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/dnsfilterd-tail";
	$php5script="exec.dnsfilterdtail.php";
	$daemonbinLog="DNS filter Watchdog Service";
	

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         dnsfilter-tail";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: $daemonbinLog";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: $daemonbinLog";
	$f[]="### END INIT INFO";
	$f[]=". /lib/lsb/init-functions";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="\t$php /usr/share/artica-postfix/$php5script --start \$2 \$3";

	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";

	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|rotatelog} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));


	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");

	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}


}


function statistics(){
	$unix=new unix();
	$Directory="/home/artica/SQLITE_DNSFILTER";
	$Files=$unix->DirFiles($Directory,"\.db$");
	$currentfile=date("Y-m-d-H").".db";
	
	$pg=new postgres_sql();
	if(!$pg->DNS_FILTER_TABLES()){echo "Failed to create PostGreSQL tables\n";return;}
	
	
	foreach ($Files as $filename=>$none){
		
		if($filename==$currentfile){
			echo "$filename ( SKIP CURRENT )\n";continue;
		}
		$q=new lib_sqlite("/home/artica/SQLITE_DNSFILTER/$filename");
		if(!$q->TABLE_EXISTS("statistics")){
			echo "$filename no statistics table\n";
			@unlink("/home/artica/SQLITE_DNSFILTER/$filename");
			continue;
		}
		
		if(!preg_match("#^([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)\.db$#", $filename,$re)){
			echo "$filename no match!!!\n";
			continue;
		}
		$zdate="{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:00:00";
		echo "$filename --> $zdate\n";
	
		if(!statistics_compress($filename,$zdate)){continue;}
		echo "$filename statistics [OK]\n";
		@unlink("/home/artica/SQLITE_DNSFILTER/$filename");
		
	}
	
	statistics_interface();
}

function statistics_compress($filename,$zdate){
	

	
	$filepath="/home/artica/SQLITE_DNSFILTER/$filename";
	$q=new lib_sqlite("/home/artica/SQLITE_DNSFILTER/$filename");
	$pg=new postgres_sql();
	
	$prefix="INSERT INTO dnsfilter (hits,zdate,sitename,familysite,rulename,category,ipaddr) VALUES ";
	
	$sql="SELECT COUNT(*) as `hits`,`sitename`,`familysite`,`rulename`,`category`,`ipaddr` FROM statistics GROUP BY `sitename`,`familysite`,`rulename`,`category`,`ipaddr`";
	$results=$q->QUERY_SQL($sql);
	$f=array();
	foreach ($results as $index=>$ligne){
		$hits=$ligne["hits"];
		$sitename=$ligne["sitename"];
		$familysite=$ligne["familysite"];
		$rulename=trim($ligne["rulename"]);
		if($rulename==null){$rulename="blacklist";}
		$category=intval($ligne["category"]);
		$ipaddr=trim($ligne["ipaddr"]);
		if($ipaddr==null){$ipaddr="0.0.0.0";}
		$f[]="('$hits','$zdate','$sitename','$familysite','$rulename','$category','$ipaddr')";
		
		if(count($f)>500){
			echo "$filename: Inject ".count($f)." items\n";
			$pg->QUERY_SQL($prefix.@implode(",", $f));
			if(!$pg->ok){return false;}
			$f=array();
			continue;
		}
		
	}
	
	
	if(count($f)>0){
		echo "$filename: Inject ".count($f)." items\n";
		$pg->QUERY_SQL($prefix.@implode(",", $f));
		if(!$pg->ok){return false;}
	}

	return true;
	
	
}

function statistics_interface(){
	$unix=new unix();
	$pg=new postgres_sql();
	$today=date("Y-m-d")." 00:00:00";
	$sock=new sockets();
	$tpl=new template_admin();
	$PieData=array();
	$sql="SELECT SUM(hits) as hits, familysite FROM dnsfilter WHERE zdate>'$today' GROUP BY familysite ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["familysite"]]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_FAMILYSITE_TODAY");
	
	
	$PieData=array();
	$sql="SELECT SUM(hits) as hits, category FROM dnsfilter WHERE zdate>'$today' GROUP BY category ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$tpl->CategoryidToName($ligne["category"])]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_CATEGORY_TODAY");	
	
	$PieData=array();
	$sql="SELECT SUM(hits) as hits, rulename FROM dnsfilter WHERE zdate>'$today' GROUP BY rulename ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["rulename"]]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_RULENAME_TODAY");	
	
	
	$PieData=array();
	$sql="SELECT SUM(hits) as hits, ipaddr FROM dnsfilter WHERE zdate>'$today' GROUP BY ipaddr ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["ipaddr"]]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_IPADDR_TODAY");	
	
	$PieData=array();
	$sql="SELECT  SUM(hits) as hits ,EXTRACT(HOUR FROM  zdate) as tdate FROM dnsfilter WHERE zdate > '$today' GROUP BY tdate ORDER BY tdate";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){
		$PieData["xdata"][]=$ligne["tdate"];
		$PieData["ydata"][]=$ligne["hits"];
		
	}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_COURBE_TODAY");

	$FirstDateOfMonth=date('Y-m-01');
	$LastDateOfMonth=date('Y-m-t');
	
	$day = date('w');
	$FirstDateOfWeek = date("Y-m-d", strtotime('monday this week'))." 00:00:00";
	
	
	
	$PieData=array();
	$sql="SELECT  SUM(hits) as hits ,EXTRACT(DAY FROM  zdate) as tdate FROM dnsfilter 
	WHERE zdate > '$FirstDateOfWeek' GROUP BY tdate ORDER BY tdate";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){
		if($GLOBALS["VERBOSE"]){echo "WEEK:$FirstDateOfWeek:{$ligne["tdate"]} {$ligne["hits"]}\n";}
		$PieData["xdata"][]=$ligne["tdate"];
		$PieData["ydata"][]=$ligne["hits"];
	
	}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_COURBE_WEEK");
	

	$PieData=array();
	$sql="SELECT SUM(hits) as hits, ipaddr FROM dnsfilter WHERE zdate>'$FirstDateOfWeek' GROUP BY ipaddr ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["ipaddr"]]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_IPADDR_WEEK");
	
	$PieData=array();
	$sql="SELECT SUM(hits) as hits, rulename FROM dnsfilter WHERE zdate>'$FirstDateOfWeek' GROUP BY rulename ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["rulename"]]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_RULENAME_WEEK");
	
	$PieData=array();
	$sql="SELECT SUM(hits) as hits, category FROM dnsfilter WHERE zdate>'$FirstDateOfWeek' GROUP BY category ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$tpl->CategoryidToName($ligne["category"])]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_CATEGORY_WEEK");
	
	
	$PieData=array();
	$sql="SELECT SUM(hits) as hits, familysite FROM dnsfilter WHERE zdate>'$FirstDateOfWeek' GROUP BY familysite ORDER BY hits DESC LIMIT 20";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["familysite"]]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_FAMILYSITE_WEEK");
	
	$PieData=array();
	$sql="SELECT  SUM(hits) as hits ,EXTRACT(DAY FROM  zdate) as tdate FROM dnsfilter
	WHERE zdate > '$FirstDateOfMonth' GROUP BY tdate ORDER BY tdate";
	$results=$pg->QUERY_SQL($sql);
	while($ligne=@pg_fetch_assoc($results)){
	if($GLOBALS["VERBOSE"]){echo "WEEK:$FirstDateOfMonth:{$ligne["tdate"]} {$ligne["hits"]}\n";}
	$PieData["xdata"][]=$ligne["tdate"];
		$PieData["ydata"][]=$ligne["hits"];
	
	}
		$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_COURBE_MONTH");
	
	
	$PieData=array();
		$sql="SELECT SUM(hits) as hits, ipaddr FROM dnsfilter WHERE zdate>'$FirstDateOfMonth' GROUP BY ipaddr ORDER BY hits DESC LIMIT 20";
		$results=$pg->QUERY_SQL($sql);
		while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["ipaddr"]]=$ligne["hits"];}
	$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_IPADDR_MONTH");
	
	$PieData=array();
		$sql="SELECT SUM(hits) as hits, rulename FROM dnsfilter WHERE zdate>'$FirstDateOfMonth' GROUP BY rulename ORDER BY hits DESC LIMIT 20";
		$results=$pg->QUERY_SQL($sql);
		while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["rulename"]]=$ligne["hits"];}
		$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_RULENAME_MONTH");
	
	$PieData=array();
		$sql="SELECT SUM(hits) as hits, category FROM dnsfilter WHERE zdate>'$FirstDateOfMonth' GROUP BY category ORDER BY hits DESC LIMIT 20";
		$results=$pg->QUERY_SQL($sql);
		while($ligne=@pg_fetch_assoc($results)){$PieData[$tpl->CategoryidToName($ligne["category"])]=$ligne["hits"];}
				$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_CATEGORY_MONTH");
	
	
	$PieData=array();
		$sql="SELECT SUM(hits) as hits, familysite FROM dnsfilter WHERE zdate>'$FirstDateOfMonth' GROUP BY familysite ORDER BY hits DESC LIMIT 20";
		$results=$pg->QUERY_SQL($sql);
		while($ligne=@pg_fetch_assoc($results)){$PieData[$ligne["familysite"]]=$ligne["hits"];}
		$sock->SaveConfigFile(serialize($PieData),"DNSFILTERD_STATS_FAMILYSITE_MONTH");
	
}


function enable_restful_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'){
    $pieces = array();
    $max = mb_strlen($keyspace, '8bit') - 1;
    for ($i = 0; $i < $length; ++$i) {
        $pieces []= $keyspace[random_int(0, $max)];
    }
    return implode('', $pieces);
}


function enable_restapi(){
    $DNSFilterRESTFulAPIKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFilterRESTFulAPIKey"));
    if($DNSFilterRESTFulAPIKey==null){
        $DNSFilterRESTFulAPIKey=enable_restful_str(10);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DNSFilterRESTFulAPIKey",$DNSFilterRESTFulAPIKey);
    }
    build_progress("{enable}",50);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFilterdRest",1);
    build_progress("{enable} {success}",100);
    sleep(5);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("/usr/sbin/artica-phpfpm-service -reload-webconsole -debug");
}



function disable_restapi(){
    build_progress("{disable}",50);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDNSFilterdRest",0);
    build_progress("{disable} {success}",100);
}


