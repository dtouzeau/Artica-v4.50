<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if($GLOBALS["VERBOSE"]){echo "Loading includes...\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.c-icap.squidguard.inc');
//exec.c-icap.php --reconfigure
if(isset($argv[1])) {
    if ($argv[1]=="--threats"){die();}
    if ($argv[1] == "--template") {
        gen_template(true);
        exit;
    }
    if ($argv[1] == "--db-maintenance") {
        dbMaintenance();
        exit;
    }
    if ($argv[1] == "--maint-schedule") {
        dbMaintenanceSchedule();
        exit;
    }
    if ($argv[1] == "--build") {
        $GLOBALS["OUTPUT"] = true;
        build();
        exit;
    }
    if ($argv[1] == "--reconfigure") {
        $GLOBALS["OUTPUT"] = true;
        reconfigure();
        exit;
    }
    if ($argv[1] == "--reload") {
        $GLOBALS["OUTPUT"] = true;
        reload();
        exit;
    }
    if ($argv[1] == "--stop") {
        $GLOBALS["OUTPUT"] = true;
        stop();
        exit();
    }
    if ($argv[1] == "--start") {
        $GLOBALS["OUTPUT"] = true;
        start();
        exit();
    }
    if ($argv[1] == "--restart") {
        $GLOBALS["OUTPUT"] = true;
        restart();
        exit();
    }
    if ($argv[1] == "--purge") {
        $GLOBALS["OUTPUT"] = true;
        purge();
        exit();
    }
    if ($argv[1] == "--webf") {
        $GLOBALS["OUTPUT"] = true;
        webfilter();
        exit();
    }
    if ($argv[1] == "--webdb") {
        $GLOBALS["OUTPUT"] = true;
        webdbs();
        exit();
    }
    if ($argv[1] == "--info") {
        $GLOBALS["OUTPUT"] = true;
        infos();
        exit();
    }
    if ($argv[1] == "--infos") {
        $GLOBALS["OUTPUT"] = true;
        infos();
        exit();
    }
    if ($argv[1] == "--restart-schedule") {
        $GLOBALS["OUTPUT"] = true;
        restart_schedule();
        exit();
    }
    if ($argv[1] == "--syslog") {
        build_syslog();
        exit;
    }
    if ($argv[1] == "--simulate") {
        simulate($argv[2]);
        exit;
    }

}

_out("Wrong command line used ".@implode(" ",$argv));
if($GLOBALS["VERBOSE"]){echo "????\n";}


function restart_schedule(){
    if(!is_file("/etc/init.d/c-icap")){return false;}

    $unix=new unix();
    squid_admin_mysql(1,"Restart the local ICAP service (by schedule)",null,__FILE__,__LINE__);
    $unix->CICAP_SERVICE_EVENTS("Restarting ICAP Server by schedule", __FILE__,__LINE__);
    system("/etc/init.d/c-icap restart");
    return true;
}


function c_icap_local_interface():string{
    $unix               = new unix();
    $CICAPListenInterface = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface");
    if($CICAPListenInterface==null){$CICAPListenInterface="lo";}
    $ListenAddress  = $unix->InterfaceToIPv4($CICAPListenInterface);
    if($ListenAddress==null){$ListenAddress="127.0.0.1";}
    return $ListenAddress;
}
function _out_simulate($text){
    if(!function_exists("openlog")){return false;}
    openlog("icap-simulation", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function simulate($fnameenc){
    $fname=base64_decode($fnameenc);
    $unix=new unix();
    $SimulateICAPID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAPID"));
    $SimulateICAPURL=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SimulateICAPURL"));

    $path="/usr/share/artica-postfix/ressources/conf/upload/$fname";
    _out_simulate("Scanning $path id:$SimulateICAPID");
    if(!is_file($path)){
        $MAIN["CONN_STATUS"]=false;
        $MAIN["DESCRIPTION"]="$fname {no_such_file}";
        _out_simulate("Scanning $fname No such file");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SimulateICAP",serialize($MAIN));
        return true;
    }

    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    $ligne = $q->mysqli_fetch_array("SELECT * FROM c_icap_services WHERE ID='$SimulateICAPID'");

    $remoteaddr=$ligne["ipaddr"];
    $remoteport=$ligne["listenport"];
    $service=$ligne["icap_server"];

    $MAIN["TITLE"]="icap://$remoteaddr:$remoteport/$service";

    if($SimulateICAPID==1 or $SimulateICAPID==2) {
        $remoteaddr = c_icap_local_interface();
        $remoteport = 1345;
    }

    _out_simulate("icap://$remoteaddr:$remoteport/$service");
    $fp = @fsockopen($remoteaddr, $remoteport, $errno, $errstr, 1);
    if (!$fp) {
        $MAIN["CONN_STATUS"]=false;
        $MAIN["DESCRIPTION"]=$errstr;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SimulateICAP",serialize($MAIN));
        @unlink($path);
        return true;
    }
    $path=$unix->shellEscapeChars($path);
    $icapclient=$unix->find_program("c-icap-client");
    $cmd="$icapclient -i $remoteaddr -p $remoteport -req \"$SimulateICAPURL\" -f $path -s $service -v";
    echo "$cmd\n";
    exec("$cmd 2>&1",$results);
    @file_put_contents("/home/artica/icap.sample",@implode("\n"),$results);
    @unlink($path);
    $a=false;
    foreach ($results as $line){
        $line=trim($line);
        _out("[SAMPLE]: $line");
        if(!$a) {
            if (preg_match("#^ICAP HEADERS:#", $line)) {
                $a = true;
            }
            continue;
        }
        if(preg_match("#(.+?):\s+(.+)#",$line,$re)){
            $key=trim(strtolower($re[1]));
            if(isset($MAIN["HEADERS"][$key])){continue;}
            $MAIN["HEADERS"][$key]=trim($re[2]);
            continue;
        }

        if(preg_match("#HTTP\/.*?([0-9]+)\s+#",$line,$re)){
            if(intval($re[1])==200){
                $MAIN["CONN_STATUS"]=true;
                continue;
            }
            $MAIN["CONN_STATUS"]=false;
            continue;
        }
    }

    if(count($MAIN["HEADERS"])>0){
        $MAIN["HEADERS"]["{filename}"]=$fname;
    }

    $MAIN["CONN_STATUS"]=true;
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SimulateICAP",serialize($MAIN));
    return true;
}

function infos(){
    $unix=new unix();
    $ListenAddress=c_icap_local_interface();


    $cicapClient=$unix->find_program("c-icap-client");

    $cmdline="$cicapClient -i $ListenAddress -s srv_clamav -p 1345 2>&1";
    echo "$cmdline\n";
    exec($cmdline,$results);

    foreach ($results as $line){
        $line=trim($line);
        echo "$line\n";
        if(preg_match("#404 Service not found#i",$line)){
            $MAIN["srv_clamav"]=false;
            break;
        }
        if(preg_match("#\s+200 OK#i",$line)){
            $MAIN["srv_clamav"]=true;
            break;
        }
    }



    $cmdline="$cicapClient -s \"info?view=text\" -i $ListenAddress -p 1345 -req use-any-url 2>&1";
    $results=array();
    exec($cmdline,$results);

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#Children number:\s+([0-9]+)#i",$line,$re)){
            $MAIN["CHILDS"]=$re[1];
            continue;
        }
        if(preg_match("#Free Servers:\s+([0-9]+)#i",$line,$re)){
            $MAIN["FREE_SERVERS"]=$re[1];
            continue;
        }
        if(preg_match("#Used Servers:\s+([0-9]+)#i",$line,$re)){
            $MAIN["USED_SERVERS"]=$re[1];
            continue;
        }
        if(preg_match("#^REQUESTS.*?:\s+([0-9]+)#i",$line,$re)){
            $MAIN["REQUESTS"]=$re[1];
            continue;
        }
        if(preg_match("#FAILED REQUESTS.*?:\s+([0-9]+)#i",$line,$re)){
            $MAIN["FAILED_REQUESTS"]=$re[1];
            continue;
        }
        if(preg_match("#^BYTES IN.*?:\s+([0-9\.]+).*?Kbs#i",$line,$re)){
            $MAIN["BYTES_IN"]=$re[1];
            continue;
        }
        if(preg_match("#^BYTES OUT.*?:\s+([0-9\.]+).*?Kbs#i",$line,$re)){
            $MAIN["BYTES_OUT"]=$re[1];
            continue;
        }
    }
    $MAIN["TIME"]=time();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CiCapTEXT",serialize($MAIN));



}

function reload($aspid=false){
	$unix       = new unix();
	$pidfile    = "/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid        = @file_get_contents($pidfile);

	if(!$aspid){
		if($unix->process_exists($pid)){
			echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap service ". __FUNCTION__."() already running PID:$pid\n";
			return false;
		}
		@file_put_contents($pidfile,getmypid());
	}


	$EnableClamavInCiCap=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableClamavInCiCap"));


	if(!is_running()){
	        $unix->CICAP_SERVICE_EVENTS("Reloading: ICAP server not running...",
                __FILE__,__LINE__);
			echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap service not running...\n";
			echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap Starting C-ICAP service...\n";
			start(true);
			return true;
	}
	$PID=PID_NUM();
	$PROCESS_TTL=$unix->PROCESS_TTL($PID);

	if($EnableClamavInCiCap==1) {
        if (!$unix->is_socket("/var/run/clamav/clamav.sock")) {
            echo "Reloading.....: " . date("H:i:s") . " [INIT]: c-icap Warning clamav.sock does not exists!\n";
            shell_exec("/etc/init.d/clamav-daemon start");

        }
        @chmod("/var/run/clamav/clamav.sock", 0777);
    }


	echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap service running since {$PROCESS_TTL}Mn\n";

    if(!quick_stop()){return false;}
    if(!quick_start()){return false;}
    echo "Reloading.....: ".date("H:i:s")." [INIT]: c-icap service Success\n";

	return true;
}

function quick_stop(){
    $unix       = new unix();
    $pid        = PID_NUM();

    if(!$unix->process_exists($pid)){
        echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap Service already stopped\n";
        if(is_file("/var/run/c-icap/c-icap.pid")){@unlink("/var/run/c-icap/c-icap.pid");}
        return true;
    }

    $unix->KILL_PROCESS($pid,15);
    for($i=1;$i<21;$i++){
        if(!$unix->process_exists($pid)){break;}
        echo "Stopping......: ".date("H:i:s")." [INIT]: Terminate c-icap $i/21\n";
        $unix->KILL_PROCESS($pid,15);
        sleep(1);
        $pid=PID_NUM();
    }
    $pid        = PID_NUM();
    if($unix->process_exists($pid)) {
        $unix->KILL_PROCESS($pid, 9);
        for ($i = 1; $i < 11; $i++) {
            if (!$unix->process_exists($pid)) {break;}
            echo "Stopping......: " . date("H:i:s") . " [INIT]: Killing c-icap $i/10\n";
            $unix->KILL_PROCESS($pid,9);
            sleep(1);
            $pid = PID_NUM();
        }
    }

    $pid        = PID_NUM();
    if($unix->process_exists($pid)) {
        echo "Stopping......: " . date("H:i:s") . " [INIT]: c-icap Failed\n";
        return false;
    }
    if(is_file("/var/run/c-icap/c-icap.pid")){@unlink("/var/run/c-icap/c-icap.pid");}
    echo "Stopping......: " . date("H:i:s") . " [INIT]: c-icap Success\n";
    return true;

}

function quick_start(){
    $unix       = new unix();
    $rm         = $unix->find_program("rm");
    $nohup      = $unix->find_program("nohup");
    $daemonbin  = $unix->find_program("c-icap");
    $tmpdir     = $unix->TEMP_DIR();
    $pid        = PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Service already started $pid since {$timepid}Mn...\n";
        build_progress_restart('{starting_service}',99);
        @file_put_contents("/var/run/c-icap/c-icap.pid",$pid);
        return true;
    }


    shell_exec("$rm -f /var/lib/c_icap/temporary/* >/dev/null 2>&1");
    if(is_file("/var/run/c-icap/c-icap.ctl")){@unlink("/var/run/c-icap/c-icap.ctl");}

    $cmd=$unix->sh_command("$daemonbin -f /etc/c-icap.conf >$tmpdir/c_icap_start 2>&1");
    $pid=PID_NUM();
    $unix->go_exec($cmd);

    if($unix->process_exists($pid)){
        echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Service Success...\n";
        return true;
    }

    for($i=1;$i<11;$i++){
        if($unix->process_exists($pid)){break;}
        echo "Starting......: ".date("H:i:s")." [INIT]: c-icap $i/10\n";
        sleep(1);
        $pid=PID_NUM();
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Service Success...\n";
        return true;
    }
    echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Service Failed...\n";
    return false;
}






function build($aspid=false){
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__." L.".__LINE__."\n";}
	$unix=new unix();
	$ln=$unix->find_program("ln");
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if(!$aspid){
		if($unix->process_exists($pid)){
			echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service ". __FUNCTION__."() already running PID:$pid\n";
			return;
		}
		@file_put_contents($pidfile,getmypid());
	}

	build_progress_restart('{reconfigure}',10);

    $confs[]="/etc/c-icap.conf";
    $confs[]="/etc/c-icap-perso.conf";
    $confs[]="/etc/srv_url_check.conf";
    $confs[]="/etc/srv_record.conf";
    $confs[]="/etc/virus_scan.conf";
    $confs[]="/etc/clamd_mod.conf";

    foreach ($confs as $fileconf){
        $crcfiles[$fileconf]=crc32_file($fileconf);
    }


	$ln=$unix->find_program("ln");

	if(!is_file("/lib/libbz2.so.1.0")){
		if(is_file("/usr/lib/c_icap/libbz2.so.1.0.4")){
			shell_exec("$ln -s /usr/lib/c_icap/libbz2.so.1.0.4 /lib/libbz2.so.1.0");
		}
	}
	build_progress_restart('{reconfigure}',20);


	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
    $php=$unix->LOCATE_PHP5_BIN();
	$unix->SystemCreateUser("clamav","clamav");
	if(!$unix->SystemUserExists("squid")){
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service creating user `squid`\n";
		$unix->SystemCreateUser("squid","squid");
	}else{
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service user `squid` exists...\n";
	}
	build_progress_restart('{reconfigure}',30);
	gen_template();
	if(is_file($squidbin)){
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service squid binary is `$squidbin`\n";
	}

    build_progress_restart('{reconfigure}',40);
    shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build-schedules");


    if(is_file($squidbin)){
        if(is_file("/etc/init.d/squid")){
            build_progress_restart('{reconfigure}',45);
            shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
        }
    }

	build_progress_restart('{reconfigure}',50);
	@mkdir("/usr/etc",0755,true);
	CicapMagic("/usr/etc/c-icap.magic");

    $MUST_RECONFIGURE=false;
    foreach ($crcfiles as $filename=>$crc1){
        $crc2=crc32_file($filename);
        if($crc2==$crc1){continue;}
        $MUST_RECONFIGURE=true;
    }

    if(!$MUST_RECONFIGURE){
        infos();
        if(is_file($squidbin)){
            dbMaintenanceSchedule();
        }
        build_progress_restart('{reconfigure} {no_change}',100);
        return true;
    }

	build_progress_restart('{reconfigure}',90);
    $unix->CICAP_SERVICE_EVENTS("Reloading ICAP Server", __FILE__,__LINE__);
	reload();
	if(is_file($squidbin)){
		dbMaintenanceSchedule();
	}
    build_progress_restart('{reconfigure}',95);
	infos();
	build_progress_restart('{reconfigure} {done}',100);
    return true;

}
function build_progress_restart($text,$pourc){
	$unix=new unix();
    $unix->framework_progress($pourc,$text,"c-icap.restart.progress");
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service {$pourc}% $text\n";

}


function dbMaintenance(){
	$sock=new sockets();
	$unix=new unix();
	$users=new usersMenus();
	$verbose=$GLOBALS["VERBOSE"];
	$EnableUfdbGuard=intval($sock->EnableUfdbGuard());
	if(!$users->SQUIDGUARD_INSTALLED){
		if(!$users->APP_UFDBGUARD_INSTALLED){
			if($verbose){echo "SQUIDGUARD_INSTALLED  =  FALSE\n";}
		}
		return;
	}


	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";

	if($unix->process_exists(@file_get_contents($pidfile))){
		echo "Already instance ".@file_get_contents($pidfile)." exists\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());


	$db_recover=$unix->LOCATE_DB_RECOVER();
	$db_stat=$unix->LOCATE_DB_STAT();

	if(strlen($db_recover)<3){
		echo "db_recover no such file\n";
		return;
	}

    if($verbose){echo "db_recover:$db_recover\n";}
    if($verbose){echo "db_stat:$db_stat\n";}

    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";


	echo "Stopping c-icap\n";
    $unix->CICAP_SERVICE_EVENTS("Stopping ICAP Server", __FILE__,__LINE__);
	shell_exec("/etc/init.d/c-icap stop");

	echo "Checking databases used\n";

	$datas=explode("\n",@file_get_contents("/etc/c-icap.conf"));
    foreach ($datas as $line){
		if(preg_match("#url_check\.LoadSquidGuardDB\s+(.+?)\s+(.+)#",$line,$re)){
			$dir=trim($re[2]);

			if(substr($dir,strlen($dir)-1,1)=='/'){$dir=substr($dir,0,strlen($dir)-1);}
			$array[$dir]=$re[1];
		}


	}

	$datas=explode("\n",@file_get_contents("/etc/squid/squidGuard.conf"));
	foreach ($datas as $line){
		if(preg_match("#domainlist\s+(.+)#",$line,$re)){
			$re[1]=trim($re[1]);
			$re[1]=dirname($re[1]);
			$dir="/var/lib/squidguard/".trim($re[1]);
			if(substr($dir,strlen($dir)-1,1)=='/'){$dir=substr($dir,0,strlen($dir)-1);}
			$array[$dir]="SquidGuard DB {$re[1]}";
		}


	}

	if(!is_array($array)){
		echo "No databases, aborting\n";
		return;
	}

	while (list ( $directory,$dbname) = each ($array)){
		echo "\nChecking DB $dbname in $directory\n==============================\n";
		$cmd="$db_recover -h $directory/ -v 2>&1";
		if($verbose){echo "$cmd\n";}
		exec($cmd,$results);
		if($verbose){$LOGS[]=$cmd;}
		$LOGS[]="\nmaintenance on $dbname\n==============================\n".@implode("\n",$results);
		unset($results);
		if(is_file("$directory/urls.db")){
			$cmd="$db_stat -d $directory/urls.db 2>&1";
			if($verbose){echo "$cmd\n";}
			if($verbose){$LOGS[]=$cmd;}
			exec($cmd,$results);
			$LOGS[]="\nstatistics on $directory/urls.db\n============================================================\n".@implode("\n",$results);
			unset($results);
		}else{
			$LOGS[]="\nstatistics on $directory/urls.db no such file";
		}

		if(is_file("$directory/domains.db")){
			$cmd="$db_stat -d $directory/domains.db 2>&1";
			if($verbose){echo "$cmd\n";}
			if($verbose){$LOGS[]=$cmd;}
			exec($cmd,$results);
			$LOGS[]="\nstatistics on $directory/domains.db\n============================================================\n".@implode("\n",$results);
			unset($results);
		}else{
			$LOGS[]="\nstatistics on $directory/domains.db no such file";
		}

		if(is_file("$directory/expressions.db")){
			$cmd="$db_stat -d $directory/expressions.db 2>&1";
			if($verbose){echo "$cmd\n";}
			if($verbose){$LOGS[]=$cmd;}
			exec($cmd,$results);
			$LOGS[]="\nstatistics on $directory/expressions.db\n============================================================\n".@implode("\n",$results);
			unset($results);
		}else{

		}

	}
    $unix->CICAP_SERVICE_EVENTS("Restarting ICAP Server", __FILE__,__LINE__);
	sys_THREAD_COMMAND_SET("/etc/init.d/c-icap restart");


	send_email_events("Maintenance on Web Proxy urls Databases: ". count($array)." database(s)",@implode("\n",$LOGS)."\n","system");
	if($verbose){echo @implode("\n",$LOGS)."\n";}


}

function dbMaintenanceSchedule(){
	if(is_file("/etc/cron.d/artica-cron-squidguarddb")){@unlink("/etc/cron.d/artica-cron-squidguarddb");}
	return;
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid)){
		echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service ". __FUNCTION__."() already running PID:$pid\n";
		return;
	}
	@file_put_contents($pidfile,getmypid());

	@unlink("/etc/crond.d/artica-cron-squidguarddb");
	$users=new usersMenus();
	if(!$users->SQUIDGUARD_INSTALLED){
		if(!$users->APP_UFDBGUARD_INSTALLED){
			writelogs("SQUIDGUARD_INSTALLED -> FALSE",__FUNCTION__,__FILE__,__LINE__);
			return null;
	}}
	$sock=new sockets();
	$time=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidGuardMaintenanceTime")));
	if($time["DBH"]==null){$time["DBH"]=23;}
	if($time["DBM"]==null){$time["DBM"]=45;}

	$h[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
	$h[]="MAILTO=\"\"";
	$h[]="{$time["DBM"]} {$time["DBH"]} * * *  root ". LOCATE_PHP5_BIN2()." ".__FILE__." --db-maintenance";
	$h[]="";
	@file_put_contents("/etc/cron.d/artica-cron-squidguarddb",@implode("\n",$h));
	writelogs("/etc/crond.d/artica-cron-squidguarddb DONE",__FUNCTION__,__FILE__,__LINE__);
	@chmod("/etc/crond.d/artica-cron-squidguarddb",640);
	shell_exec("/bin/chown root:root /etc/cron.d/artica-cron-squidguarddb");
}

function CicapMagic($path){
$f[]="# In this file defined the types of files and the groups of file types. ";
$f[]="# The predefined data types, which are not included in this file, ";
$f[]="# are ASCII, ISO-8859, EXT-ASCII, UTF (not implemented yet), HTML ";
$f[]="# which are belongs to TEXT predefined group and BINARY which ";
$f[]="# belongs to DATA predefined group.";
$f[]="#";
$f[]="# The line format of magic file is:";
$f[]="#";
$f[]="# offset:Magic:Type:Short Description:Group1[:Group2[:Group3]...]";
$f[]="#";
$f[]="# CURRENT GROUPS are :TEXT DATA EXECUTABLE ARCHIVE GRAPHICS STREAM DOCUMENT";
$f[]="";
$f[]="0:MZ:MSEXE:DOS/W32 executable/library/driver:EXECUTABLE";
$f[]="0:LZ:DOSEXE:MS-DOS executable:EXECUTABLE";
$f[]="0:\177ELF:ELF:ELF unix executable:EXECUTABLE";
$f[]="0:\312\376\272\276:JavaClass:Compiled Java class:EXECUTABLE";
$f[]="";
$f[]="#Archives";
$f[]="0:Rar!:RAR:Rar archive:ARCHIVE";
$f[]="0:PK\003\004:ZIP:Zip archive:ARCHIVE";
$f[]="0:PK00PK\003\004:ZIP:Zip archive:ARCHIVE";
$f[]="0:\037\213:GZip:Gzip compressed file:ARCHIVE";
$f[]="0:BZh:BZip:BZip compressed file:ARCHIVE";
$f[]="0:SZDD:Compress.exe:MS Copmress.exe'd compressed data:ARCHIVE";
$f[]="0:\037\235:Compress:UNIX compress:ARCHIVE";
$f[]="0:MSCF:MSCAB:Microsoft cabinet file:ARCHIVE";
$f[]="257:ustar:TAR:Tar archive file:ARCHIVE";
$f[]="0:\355\253\356\333:RPM:Linux RPM file:ARCHIVE";
$f[]="#Other type of Archives";
$f[]="0:ITSF:MSCHM:MS Windows Html Help:ARCHIVE";
$f[]="0:!<arch>\012debian:debian:Debian package:ARCHIVE";
$f[]="";
$f[]="# Graphics";
$f[]="0:GIF8:GIF:GIF image data:GRAPHICS";
$f[]="0:BM:BMP:BMP image data:GRAPHICS";
$f[]="0:\377\330:JPEG:JPEG image data:GRAPHICS";
$f[]="0:\211PNG:PNG:PNG image data:GRAPHICS";
$f[]="0:\000\000\001\000:ICO:MS Windows icon resource:GRAPHICS";
$f[]="0:FWS:SWF:Shockwave Flash data:GRAPHICS";
$f[]="0:CWS:SWF:Shockwave Flash data:GRAPHICS";
$f[]="";
$f[]="#STREAM";
$f[]="0:\000\000\001\263:MPEG:MPEG video stream:STREAM";
$f[]="0:\000\000\001\272:MPEG::STREAM";
$f[]="0:RIFF:RIFF:RIFF video/audio stream:STREAM";
$f[]="0:OggS:OGG:Ogg Stream:STREAM";
$f[]="0:ID3:MP3:MP3 audio stream:STREAM";
$f[]="0:\377\373:MP3:MP3 audio stream:STREAM";
$f[]="0:\377\372:MP3:MP3 audio stream:STREAM";
$f[]="0:\060\046\262\165\216\146\317:ASF:WMA/WMV/ASF:STREAM";
$f[]="0:.RMF:RMF:Real Media File:STREAM";
$f[]="";
$f[]="#Responce from stream server :-)";
$f[]="0:ICY 200 OK:ShouthCast:Shouthcast audio stream:STREAM";
$f[]="";
$f[]="#Documents";
$f[]="0:\320\317\021\340\241\261\032\341:MSOFFICE:MS Office Document:DOCUMENT";
$f[]="0:\208\207\017\224\161\177\026\225\000:MSOFFICE::DOCUMENT";
$f[]="4:Standard Jet DB:MSOFFICE:MS Access Database:DOCUMENT";
$f[]="0:%PDF-:PDF:PDF document:DOCUMENT";
$f[]="0:%!:PS:PostScript document:DOCUMENT";
$f[]="";
$f[]="";
echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service $path done...\n";
@file_put_contents($path,@implode("\n",$f));
}

function gen_template($reconfigure=false):bool{
    if(!is_file("/etc/init.d/c-icap")){return true;}
	$templateDestination=base64_decode("L3Vzci9zaGFyZS9jX2ljYXAvdGVtcGxhdGVzL3ZpcnVzX3NjYW4vZW4vVklSVVNfRk9VTkQ=");
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	if(!is_dir("/usr/share/squid-langpack/templates")){@mkdir("/usr/share/squid-langpack/templates",0755,true);}

	@mkdir(dirname($templateDestination),0755,true);

	///usr/share/c_icap/templates//virus_scan/en/VIR_MODE_HEAD
	///usr/share/c_icap/templates/srv_url_check/en/DENY
    $VIR_MODE_HEAD=dirname($templateDestination)."/VIR_MODE_HEAD";
    $VIR_MODE_TAIL=dirname($templateDestination)."/VIR_MODE_TAIL";
    $VIRUS_FOUND=dirname($templateDestination)."/VIRUS_FOUND";

    $crc[$VIR_MODE_HEAD]=crc32_file($VIR_MODE_HEAD);
    $crc[$VIR_MODE_TAIL]=crc32_file($VIR_MODE_TAIL);
    $crc[$VIRUS_FOUND]=crc32_file($VIRUS_FOUND);

	@mkdir("/usr/share/c_icap/templates/srv_url_check/en",0755,true);
    $CICAPWebErrorPage=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPWebErrorPage"));
    if(strlen($CICAPWebErrorPage)<20){
        $CICAPWebErrorPage=@file_get_contents(dirname(__FILE__)."/VIRUS.html");

    }
    @file_put_contents($templateDestination,$CICAPWebErrorPage);
	@chown($templateDestination,"squid");
	@chgrp($templateDestination,"squid");

	@chown("/usr/share/c_icap","squid");
	@chgrp("/usr/share/c_icap","squid");
	@chown("/usr/share/c_icap/templates","squid");
	@chgrp("/usr/share/c_icap/templates","squid");
	@chown("/usr/share/c_icap/templates/srv_url_check","squid");
	@chgrp("/usr/share/c_icap/templates/srv_url_check","squid");
	@chown("/usr/share/c_icap/templates/srv_url_check/en","squid");
	@chgrp("/usr/share/c_icap/templates/srv_url_check/en","squid");
	@chown("/usr/share/c_icap/templates/srv_url_check/en/DENY","squid");
	@chgrp("/usr/share/c_icap/templates/srv_url_check/en/DENY","squid");



	$data="<html>
	<head>
	<!--C-ICAP virus_scan module -->
	</head>
	<body>
	<div style='font-size:20px;font-weight:bold'> Web security download page for %VFR </div>";
	@file_put_contents($VIR_MODE_HEAD, $data);

	$data="<div style='font-size:18px;font-weight:bold'>
	<strong>Found something %VVN in <span style='text-decoration:underline'>%VU</span></strong><hr>
	file (size:%Ib Bytes) from (%VHS) %VFR</a> <br>
	
	</body>
	</html>";
	@file_put_contents($VIR_MODE_TAIL, $data);

    $xRECONFIGURE=false;
    foreach ($crc as $filename=>$crc1){
        $crc2=crc32_file($filename);
        if($crc1==$crc2){continue;}
        $xRECONFIGURE=true;
    }
    if(!$reconfigure){return true;}
    if(!$xRECONFIGURE){return true;}
    if(!is_running()) {
        shell_exec("/etc/init.d/c-icap start");
        return true;
    }
    $echo=$unix->find_program("echo");
    $unix->CICAP_SERVICE_EVENTS("Reconfiguring ICAP Server by CTL socket", __FILE__,__LINE__);
    shell_exec("$echo -n \"reconfigure\" > /var/run/c-icap/c-icap.ctl");
    return true;

}

function is_running(){
	$unix=new unix();
	$binpath=$unix->find_program("c-icap");
	$master_pid=trim(@file_get_contents("/var/run/c-icap/c-icap.pid"));
	if($master_pid==null){$master_pid=$unix->PIDOF($binpath);}
	if(!$unix->process_exists($master_pid)){$master_pid=$unix->PIDOF($binpath);}

	if(!$unix->process_exists($master_pid)){return false;}
	return true;
}

function reconfigure(){
	$unix=new unix();
	$echo=$unix->find_program("echo");
	gen_template();
	build();

	if(!is_running()){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service not running...\n";return;}
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service reconfigure service...\n";
    $unix->CICAP_SERVICE_EVENTS("Reconfiguring ICAP Server by CTL socket", __FILE__,__LINE__);
	shell_exec("$echo -n \"reconfigure\" > /var/run/c-icap/c-icap.ctl");

}
function PID_NUM(){
	$filename=PID_PATH();
	$pid=trim(@file_get_contents($filename));
	$unix=new unix();
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($unix->find_program("c-icap"));
}
//##############################################################################
function PID_PATH(){
	return '/var/run/c-icap/c-icap.pid';
}
//##############################################################################
function restart(){
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
		build_progress_restart('Already running',110);
		return;
	}
	@file_put_contents($pidfile, getmypid());
	build_progress_restart('{stopping_service}',10);


    $unix->CICAP_SERVICE_EVENTS("Restarting ICAP Server (init.d)", __FILE__,__LINE__);
	stop(true);
	if(!start(true)){
		build_progress_restart('{starting_service} {failed}',110);
		return;
	}
	build_progress_restart('{starting_service} {success}',100);

}
//##############################################################################

function dev_shm(){
    $BaseWorkDir="/dev/shm";
    $handle = opendir($BaseWorkDir);
    if($handle) {
        while (false !== ($filename = readdir($handle))) {
            if($filename=="."){continue;}
            if($filename==".."){continue;}
            $targetFile="$BaseWorkDir/$filename";
            if(!is_file($targetFile)){continue;}
            if(!preg_match("#^c-icap-shared#",$filename)){continue;}
            echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service remove $targetFile\n";
            @unlink($targetFile);
        }
    }
}

function start($aspid=false,$nobuild=false):bool{
	$unix=new unix();
	$sock=new sockets();
	$GLOBALS["CLASS_SOCKETS"]=$sock;

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$daemonbin=$unix->find_program("c-icap");
	if(!is_file($daemonbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service, not installed\n";}
		build_progress_restart('{starting_service} {failed}',60);
		return false;
	}



	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Service already started $pid since {$timepid}Mn...\n";}
		build_progress_restart('{starting_service}',99);
		@file_put_contents("/var/run/c-icap/c-icap.pid",$pid);
		return true;
	}


	$CicapEnabled=intval($sock->GET_INFO("CicapEnabled"));
	$SQUIDEnable=$sock->GET_INFO("SQUIDEnable");
	$EnableClamavInCiCap=intval($sock->GET_INFO("EnableClamavInCiCap"));

	if(is_file("/usr/lib/c_icap/memcached_cache.so")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("C_ICAP_MEMCACHED", 1);
    }


	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Proxy service enabled:$SQUIDEnable\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service C-ICAP service enabled:$CicapEnabled\n";}


	if($CicapEnabled==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service disabled ( see CicapEnabled )\n";}
		build_progress_restart('{starting_service}',99);
		return true;
	}


	$nohup=$unix->find_program("nohup");
	$tmpdir=$unix->TEMP_DIR();

	if(!$nobuild) {
        echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Building configuration..\n";
        echo "Starting......: ".date("H:i:s")." [INIT]: *************************** RECONFIGURE\n";
        build(true);
    }
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Generate template..\n";
	gen_template();
    build_syslog();
	@chmod("/var/run",0777);
	@mkdir("/var/run/c-icap",0755,true);
	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap Apply permissions..\n";
	$unix->chown_func("squid", "squid","/var/run/c-icap");
	$ldconfig=$unix->find_program("ldconfig");
	system("$ldconfig -n /usr/lib/c_icap/");

	build_progress_restart('{starting_service}',55);
	libicapapi();
	$rm=$unix->find_program("rm");

	if($EnableClamavInCiCap==1) {
        shell_exec("/etc/init.d/clamav-daemon start");
        if (!$unix->is_socket("/var/run/clamav/clamav.sock")) {
            echo "Reloading.....: " . date("H:i:s") . " [INIT]: c-icap Warning clamav.sock does not exists!\n";
            build_progress_restart('{starting_service}', 60);
            system("/etc/init.d/clamav-daemon start");
        }
        @chmod("/var/run/clamav/clamav.sock", 0777);
    }



	build_progress_restart('{starting_service}',65);
	shell_exec("$rm -f /var/lib/c_icap/temporary/* >/dev/null 2>&1");
	$debug=" -d 10";
	$debug=null;
    dev_shm();
	sleep(1);

    $cmd="$daemonbin -f /etc/c-icap.conf $debug >$tmpdir/c_icap_start 2>&1";
	$cmdexec=$unix->sh_command($cmd);


    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        build_progress_restart('{starting_service}',99);
        return true;
    }


	echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service run daemon\n";
    $unix->CICAP_SERVICE_EVENTS("Starting ICAP Server binary", __FILE__,__LINE__);
	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
	build_progress_restart('{starting_service}',70);
    if(is_file("/var/run/c-icap/c-icap.ctl")){@unlink("/var/run/c-icap/c-icap.ctl");}
	$unix->go_exec($cmdexec);

	for($i=0;$i<6;$i++){
		build_progress_restart('{starting_service}',75);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting $i/6...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Success service started pid:$pid...\n";}
		@unlink("$tmpdir/c_icap_start");
        $unix->CICAP_SERVICE_EVENTS("Starting ICAP Server [success]", __FILE__,__LINE__);
		build_progress_restart('{starting_service}',99);
		return true;
	}

    $unix->CICAP_SERVICE_EVENTS("Starting ICAP Server [failed]", __FILE__,__LINE__);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service failed analyze...\n";}
	$f=explode("\n",@file_get_contents("$tmpdir/c_icap_start"));
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service $line\n";}
		if(preg_match("#error while loading shared libraries#", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." **************************************************\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service please re-install package...\n";}
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." **************************************************\n";}
			$sock->SET_INFO("CicapEnabled",0);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service restarting watchdogs\n";}
			shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
            $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");

		}
	}


	build_progress_restart('{starting_service}',90);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $cmd\n";}


	if($GLOBALS["VERBOSE"]){echo "$cmd\n";}
    return false;
}
//##############################################################################
function purge($aspid=false){

	$unix=new unix();
	$sock=new sockets();

	$workdir="/var/lib/c_icap/temporary";
	if(is_link($workdir)){$workdir=readlink($workdir);}
	if(!is_dir($workdir)){return;}


	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pidTime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
	$rm=$unix->find_program("rm");
	if($GLOBALS["VERBOSE"]){
		echo "pidfile: $pidfile\n";
		echo "pidTime: $pidTime\n";
	}

	if(!$aspid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}

	}
if(!$GLOBALS["FORCE"]){
	if(isset($GLOBALS["SCHEDULE"])){
		$Time=$unix->file_time_min($pidTime);
		if($GLOBALS["VERBOSE"]){echo "Time:{$Time}Mn\n";}
		if($Time<20){
			if($GLOBALS["VERBOSE"]){echo "Time:{$Time}Mn < 20 -> EXIT\n";}
			return;}

	}
}

if($GLOBALS["FORCE"]){
	$Time=$unix->file_time_min($pidTime);
	if($Time<1){
		if($GLOBALS["VERBOSE"]){echo "Time:{$Time}Mn < 1 -> EXIT\n";}
		return;
	}
}

	@unlink($pidTime);
	@file_put_contents($pidTime, time());
	$MaxCICAPWorkTimeMin=$sock->GET_INFO("MaxCICAPWorkTimeMin");
	$MaxCICAPWorkSize=$sock->GET_INFO("MaxCICAPWorkSize");
	if(!is_numeric($MaxCICAPWorkTimeMin)){$MaxCICAPWorkTimeMin=1440;}
	if(!is_numeric($MaxCICAPWorkSize)){$MaxCICAPWorkSize=5000;}
	$size=round($unix->DIRSIZE_KO($workdir)/1024,2);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service `$workdir` {$size}MB/$MaxCICAPWorkSize\n";}


	@file_put_contents($pidfile, getmypid());
	$sync=$unix->find_program("sync");

	if($size>$MaxCICAPWorkSize){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service {$size}MB exceed size!\n";}
		squid_admin_mysql(0, "C-ICAP: `$workdir` {$size}MB exceed size!","Artica will remove all files..\n",__FILE__,__LINE__);
		shell_exec("$rm $workdir/*");
		shell_exec($sync);
		stop(true);
		start(true);
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
		return;
	}

	if($GLOBALS["ALL"]){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service {$size}MB exceed size!\n";}
		squid_admin_mysql(0, "C-ICAP: `$workdir` {$size}MB exceed size!","Artica will remove all files..\n",__FILE__,__LINE__);
		shell_exec("$rm $workdir/*");
		shell_exec($sync);
        $unix->CICAP_SERVICE_EVENTS("Restarting ICAP Server", __FILE__,__LINE__);
		stop(true);
		start(true);
		squid_admin_mysql(2, "{reloading_proxy_service} (".__FUNCTION__.")", null,__FILE__,__LINE__);
		shell_exec("/etc/init.d/squid reload --script=".basename(__FILE__));
		return;
	}


	if (!$handle = opendir($workdir)) {return;}
	while (false !== ($file = readdir($handle))) {
		if ($file == "."){continue;}
		if ($file == ".."){continue;}
		if(is_dir($workdir)){continue;}
		$path="$workdir/$file";
		$size=@filesize($path);
		$size=$size/1024;
		$size=$size/1024;
		if($unix->is_socket($path)){continue;}
		$time=$unix->file_time_min($path);
		if($GLOBALS["ALL"]){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service removing `$path` ( {$size}M )\n";}
			@unlink($path);
			continue;
		}

		if($time>$MaxCICAPWorkTimeMin){
			squid_admin_mysql(1, "C-ICAP: Removing temporary file $path ( {$time}Mn/{$size}M )", "It exceed rule of {$MaxCICAPWorkTimeMin}Mn ( {$time}Mn )",__FILE__,__LINE__);
			@unlink($path);
			continue;
		}
	}

	$REMOVED=false;
	$workdir="/var/clamav/tmp";
	if(is_dir($workdir)){
		if (!$handle = opendir($workdir)) {return;}
		while (false !== ($file = readdir($handle))) {
			if ($file == "."){continue;}
			if ($file == ".."){continue;}
			if(is_dir($workdir)){continue;}
			$path="$workdir/$file";
			$size=@filesize($path);
			$size=$size/1024;
			$size=$size/1024;
			if($unix->is_socket($path)){continue;}
			$time=$unix->file_time_min($path);
			if($GLOBALS["ALL"]){
				if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service removing `$path` ( {$size}M )\n";}
				$REMOVED=true;
				@unlink($path);
				continue;
			}

			if($time>$MaxCICAPWorkTimeMin){
				squid_admin_mysql(1, "C-ICAP: Removing temporary file $path ( {$time}Mn/{$size}M )", "It exceed rule of {$MaxCICAPWorkTimeMin}Mn ( {$time}Mn )",__FILE__,__LINE__);
				$REMOVED=true;
				@unlink($path);
				continue;
			}
		}
	}

	if($REMOVED){shell_exec($sync);}
}


function libicapapi(){




	if(is_file("/usr/lib/libicapapi.so.3.0.1")){
		if(!is_file("/usr/lib/libicapapi.so.3")){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap linking libicapapi.so.3.0.1\n";}
			shell_exec("ln -s /usr/lib/libicapapi.so.3.0.1 /usr/lib/libicapapi.so.3");
		}
	}
	$f[]="/usr/lib/libicapapi.so.2";
}





//##############################################################################
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service Already Artica task running PID $pid since {$time}mn\n";}
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service already stopped...\n";}
		build_progress_restart('{stopping_service}',50);
		return true;
	}

	$echo=$unix->find_program("echo");
	build_progress_restart('{stopping_service}',15);
    $unix->CICAP_SERVICE_EVENTS("Operation: Stop ICAP Server PID $pid", __FILE__,__LINE__);
	shell_exec("$echo -n \"stop\"  > /var/run/c-icap/c-icap.ctl");


	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		build_progress_restart('{stopping_service}',20);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/3...\n";}
		sleep(1);
	}

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        build_progress_restart('{stopping_service}',50);
        return true;
    }

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service Shutdown pid $pid...\n";}

	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress_restart('{stopping_service}',30);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}


	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		build_progress_restart('{stopping_service}',40);
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service success...\n";}
		build_progress_restart('{stopping_service}',50);
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		build_progress_restart('{stopping_service}',45);
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: c-icap service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service success...\n";}
		$GLOBALS["ALL"]=true;
		build_progress_restart('{stopping_service}',50);
		purge(true);
		return true;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: c-icap service failed...\n";}
	build_progress_restart('{stopping_service}',50);
	return false;
}




function tmpfs_mounted_size(){
	$unix=new unix();
	$mount=$unix->find_program("mount");
	exec("$mount 2>&1",$results);
	foreach ($results as $num=>$ligne){
		if(preg_match("#^tmpfs on.*?c_icap\/temporary.*?tmpfs\s+\(.*?size=([0-9]+)M#", $ligne,$re)){return $re[1];}}
	return null;
}
function _out($text){
    $prefix="Starting......: ".date("H:i:s")."c-icap ";
    echo "$prefix $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("c-icap", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}



function webfilter(){


}

function webdbs(){

}






?>