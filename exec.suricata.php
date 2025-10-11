<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
$GLOBALS["SERVICE_NAME"]="IDS service";
$GLOBALS["DEBUG"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}

	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
	if($pid<100){$pid=null;}
	$unix=new unix();
	if($unix->process_exists($pid,basename(__FILE__))){events("PID: $pid Already exists....");exit();}

	
if($argv[1]=="--build"){suricata_config();exit();}
if($argv[1]=="--classifications"){build_classification();exit();}	
if($argv[1]=="--cd"){installapt();exit();}
if($argv[1]=="--package"){make_package();exit();}
if($argv[1]=="--path"){@unlink($GLOBALS["LOGFILE"]);installapt($argv[2]);exit();}
if($argv[1]=="--install"){@unlink($GLOBALS["LOGFILE"]);installapt($argv[2]);exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--version"){$GLOBALS["OUTPUT"]=true;echo suricata_version();exit();}
if($argv[1]=="--reconfigure-progress"){$GLOBALS["OUTPUT"]=true;echo reconfigure_progress();exit();}
if($argv[1]=="--dashboard"){$GLOBALS["OUTPUT"]=true;echo suricata_dashboard();exit();}
if($argv[1]=="--parse-rules"){$GLOBALS["OUTPUT"]=true;parse_rulesToPostGres();exit();}
if($argv[1]=="--reload-progress"){$GLOBALS["OUTPUT"]=true;reload_progress();exit();}
if($argv[1]=="--firewall"){$GLOBALS["OUTPUT"]=true;firewall($argv[2]);exit();}
if($argv[1]=="--install-service"){install_service();exit();}
if($argv[1]=="--remove-service"){remove_suricata();exit();}
if($argv[1]=="--syslog"){build_syslog();exit();}


function build_progress($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"suricata.progress");

}
function build_progress_reconfigure($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"suricata.progress");
}
function build_progress_restart($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"suricata.progress");
}
function _out($text):bool{
    echo date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("suricata", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}
function build_syslog():bool{
    $unix=new unix();
    $syslog_conf="/etc/rsyslog.d/00_suricata.conf";
    $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
    if($EnableSuricata==0){
        if(is_file($syslog_conf)){
            @unlink($syslog_conf);
            $unix->RESTART_SYSLOG(true);
        }
        return true;
    }
    $md5_start=null;
    if(is_file($syslog_conf)){$md5_start=md5_file($syslog_conf);}


    $remote= BuildRemoteSyslogs("suricata","local-suricata");

    $h[]="if  (\$programname =='suricata') then {";
    $h[]=buildlocalsyslogfile("/var/log/suricata/suricata-service.log");
    $h[]=$remote;
    $h[]="& stop";
    $h[]="}";
    $h[]="";

    @file_put_contents($syslog_conf,@implode("\n", $h));
    $md5_end=md5_file($syslog_conf);
    if($md5_end<>$md5_start) {
        _out("Starting: Updating Syslog configuration...");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
        return true;
    }
    _out("Starting: Syslog configuration NO CHANGE...");
    return true;
}
function reload():bool{
	$unix=new unix();
	suricata_config();
	_out("Reloading service");
	$pid=suricata_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		build_progress_reconfigure("{reloading} Suricata",20);
		suricata_config();
		build_progress_reconfigure("{reloading} Suricata",80);
		_out("Running since {$time}Mn...");
		$unix->KILL_PROCESS($pid,12);
		$nohup=$unix->find_program("nohup");
		build_progress_reconfigure("{reloading} Suricata",90);
		shell_exec("$nohup /etc/init.d/suricata-tail restart >/dev/null 2>&1 &");
	}else{
		_out("Not running, start it");
		return start(true);
	}
    return true;
}
function install_service():bool{
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSuricata", 1);
	build_progress_reconfigure("{enable} Suricata",10);
	install_service_suricata();
	install_service_suricata_tail();
	install_monit();
	build_progress_reconfigure("{reconfiguring} Suricata",50);
	suricata_config();
	$nohup=$unix->find_program("nohup");
	build_progress_reconfigure("{starting_service} Suricata",70);
	@mkdir("/home/artica/suricata-tail",0755,true);
    if(!is_dir("/var/log/suricata")) {
        @mkdir("/var/log/suricata", 0755, true);
    }

	build_progress_reconfigure("{starting_service} Suricata FireWall",80);

    $unix->framework_exec("exec.crowdsec.php --suricata");

	$cmd="$php /usr/share/artica-postfix/exec.suricata-fw.php --build --force";
	echo $cmd."\n";
	system($cmd);
	
	build_progress_reconfigure("{starting_service} Suricata {success}",100);
	shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
    return true;
}
function install_monit():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $me=__FILE__;
    $monit_file="/etc/monit/conf.d/APP_SURICATA.monitrc";
    $md51=null;
    if(is_file($monit_file)){
        $md51=md5_file($monit_file);
    }

	$f[]="check process IDS with pidfile /var/run/suricata/suricata.pid";
	$f[]="\tstart program = \"$php $me --start\"";
	$f[]="\tstop program = \"$php $me --stop\"";

	$f[]="";
	@file_put_contents($monit_file, @implode("\n", $f));
    $md52=md5_file($monit_file);
    if($md52==$md51){return true;}
    _out("CONF: Reconfigure daemon monitor");
    $unix->MONIT_RELOAD();
    return true;
}
function remove_suricata(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	build_progress_reconfigure("{disable_feature} Suricata",10);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSuricata", 0);
    $monit_file="/etc/monit/conf.d/APP_SURICATA.monitrc";
    $syslog_conf="/etc/rsyslog.d/01-suricata.conf";
	
	$q=new postgres_sql();
	if(!$q->isRemote){
		build_progress_reconfigure("{disable_feature} {remove_statistics}",20);
		$q->QUERY_SQL("DROP TABLE suricata_events");
	}
	
	if(is_dir("/home/artica/suricata-tail")){system("$rm -rf /home/artica/suricata-tail");}
	if(is_dir("/var/log/suricata")){system("$rm -rf /var/log/suricata");}
	
	build_progress_reconfigure("{disable_feature} Suricata FireWall",50);
	shell_exec("$php /usr/share/artica-postfix/exec.suricata-fw.php --build");
	build_progress_reconfigure("{disable_feature} Suricata Tail",60);
	remove_service("/etc/init.d/suricata-tail");
	build_progress_reconfigure("{disable_feature} Suricata",90);
	remove_service("/etc/init.d/suricata");
	if(is_file($monit_file)){@unlink($monit_file);}
    if(is_file($syslog_conf)){@unlink($syslog_conf);}
    if(is_dir("/var/log/suricata")){
        $rm=$unix->find_program("rm");
        shell_exec("$rm -rf /var/log/suricata");
    }
	
	build_progress_reconfigure("{disable_feature} Suricata",91);
	
	if(is_file("/etc/modprobe.d/pfring.conf")){
		@unlink("/etc/modprobe.d/pfring.conf");
		$rmmod=$unix->find_program("rmmod");
		system("$rmmod pf_ring");
	}

    $unix->framework_exec("exec.crowdsec.php --suricata");
	build_progress_reconfigure("{disable_feature} Suricata",100);
    $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function reconfigure_progress():bool{
    $EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
	
	if($EnableSuricata==1){
		build_progress_reconfigure("{restarting} Suricata",20);
		echo "Restarting service....\n";
		restart(true);
		disablesid();
		build_progress_reconfigure("{reconfigure} Dashboard",50);
		suricata_dashboard();
		if(!installapt()){
			build_progress_reconfigure("{reconfigure} {failed}",110);
			return false;
		}
		
		build_progress_reconfigure("{reconfigure} {done}",100);
        return true;
		
	}else{
		build_progress_reconfigure("{stopping} Suricata",20);
		stop(true);
		build_progress_reconfigure("{stopping} barnyard",30);
		system("/etc/init.d/barnyard stop");
		
		build_progress_reconfigure("{stopping} tail",40);
		system("/etc/init.d/suricata-tail stop");
		
		build_progress_reconfigure("{stopping} {done}",100);
	}
    return true;
	
}
function installapt(){
	return true;
	
}
function restart($nopid=false):bool{
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Already Artica task running PID $pid since {$time}mn");
			build_progress_restart("{failed}",110);
			return false;
		}
	}
	@file_put_contents($pidfile, getmypid());
	_out("Stopping service");
	build_progress_restart("{stopping_service}",50);
	stop(true);
	_out("Building configuration");
	
	$ServerRunSince=$unix->ServerRunSince();
	if($ServerRunSince>3){
		build_progress_restart("{reconfiguring}",60);
		squid_admin_mysql(1, "Reconfigure IDS service (server {running} {since} {$ServerRunSince}mn)", null,__FILE__,__LINE__);
		suricata_config();
	}
	_out("Starting service");
	
	build_progress_restart("{restarting}...",90);
	if(start(true)){
		return build_progress_restart("{restarting} {success}",100);
	}
	build_progress_restart("{restarting} {failed}",100);
	return false;

}
function start($nopid=false):bool{
	$unix=new unix();
	$sock=new sockets();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	
	if(!$nopid){
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Starting: Already Artica task running PID $pid since {$time}mn");
			return false;
		}
	}

	$pid=suricata_pid();
	if($unix->process_exists($pid)){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		_out("Already running since {$time}Mn...");
		return true;
	}

	$EnableSuricata=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableSuricata"));
	if($EnableSuricata==0){
		_out("Disabled ( see EnableSuricata )...");
		return false;
	}


	$masterbin=$unix->find_program("suricata");
	if(!is_file($masterbin)){
		_out("Not installed...");
		return false;
	}
    build_syslog();
	$ldconfig=$unix->find_program("ldconfig");
	$modprobe=$unix->find_program("modprobe");
	
	suricata_config();
	@mkdir("/var/run/suricata",0755,true);
	@mkdir("/var/log/barnyard2",0755,true);
	@mkdir("/var/log/suricata",0755,true);
	@chmod("/usr/share/artica-postfix/bin/sidrule",0755);
	$DebianVersion=DebianVersion();

    _out("Debian version '$DebianVersion'...");
    if($DebianVersion<10) {
        if (!is_file("/usr/lib/libhs.so.4")) {
            _out("Starting installing libhyperscan4");
            $unix->DEBIAN_INSTALL_PACKAGE("libhyperscan4");
        }

        if (!is_file("/usr/lib/libhs.so.4")) {
            _out("Starting /usr/lib/libhs.so.4 no such file!");
            return false;
        }
    }
	
	if(is_file("/var/log/suricata.log")){@unlink("/var/log/suricata.log");}
	
	$SuricataDepmod=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataDepmod"));
	$SuricataPfRing=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SuricataPfRing"));
	
	if($SuricataDepmod==0){
		$depmod=$unix->find_program("depmod");
		_out("Running depmod..");
		system("$depmod -a");
		$sock->SET_INFO("SuricataDepmod", 1);
	}

	if($SuricataPfRing==1) {
        if (!is_file("/etc/modprobe.d/pfring.conf")) {
            _out("Starting running ldconfig..");
            system($ldconfig);
            _out("Starting install module pf_ring");
            @file_put_contents("/etc/modprobe.d/pfring.conf", "options pf_ring transparent_mode=0 min_num_slots=32768 enable_tx_capture=0\n");
        }
    }
    if($SuricataPfRing==0) {
        if($unix->isModulesLoaded("pf_ring")){
            _out("unload pfring support");
            $rmmod=$unix->find_program("rmmod");
            shell_exec("$rmmod pf_ring");
        }
    }


    if($SuricataPfRing==1) {
            _out("Starting Loading pf_ring...");
            $results=array();
            exec("$modprobe pf_ring transparent_mode=0 min_num_slots=32768 2>&1",$results);
            foreach ($results as $line){_out("modprobe: $line");}

            if (!$unix->isModulesLoaded("pf_ring")) {
                for ($i = 0; $i < 5; $i++) {
                    if ($unix->isModulesLoaded("pf_ring")) {
                        break;
                    }
                    $results=array();
                    exec("$modprobe pf_ring transparent_mode=0 min_num_slots=32768 2>&1",$results);
                    foreach ($results as $line){
                        _out("modprobe: $line");
                    }
                    if ($GLOBALS["OUTPUT"]) {
                        _out("Starting Waiting pf_ring to be loaded $i/5..");
                    }
                    sleep(1);
                }

                if (!$unix->isModulesLoaded("pf_ring")) {
                        _out("Starting Failed, pf_ring not loaded");

                return false;
            }
        }
    }
	
	
	$SuricataInterface=$sock->GET_INFO("SuricataInterface");
	if($SuricataInterface==null){$SuricataInterface="eth0";}
	
	if ($handle = opendir("/var/log/suricata")) {
		while (false !== ($fileZ = readdir($handle))) {
			if($fileZ=="."){continue;}
			if($fileZ==".."){continue;}
			$path="/var/log/suricata/$fileZ";;
	
			if(preg_match("#unified2\.alert\.#", $fileZ)){
				if($unix->file_time_min($path)>10){
					_out("remove $path");
					@unlink($path);
                }

            }
	
        }
	}
	$ethtool=$unix->find_program("ethtool");

	if(is_file($ethtool)){
		shell_exec("$ethtool -K $SuricataInterface gro off >/dev/null 2>&1");
		shell_exec("$ethtool -K $SuricataInterface lro off >/dev/null 2>&1");
	}
	
	
	$suricata_version=suricata_version();
	@mkdir("/var/run/suricata",0755,true);
	_out("Starting service v$suricata_version");
	$cm[]="$masterbin";
	$cm[]="--pidfile /var/run/suricata/suricata.pid";
    if($SuricataPfRing==1) {
        _out("Starting service using PFRING");
        $cm[] = "--pfring";
        $cm[] = "--pfring-cluster-id=99";
        $cm[] = "--pfring-cluster-type=cluster_flow";
    }else{
        _out("Starting service using AF PACKET");
        $cm[] = "--af-packet";
    }
	$cm[]="-D";
	@unlink("/var/run/suricata/suricata.pid");
	
	if(!installapt()){
		_out("Failed to check required packages");
	}
    $cm[]=" >/var/log/suricata.start 2>&1";
	$cmdline=implode(" ", $cm);
    $sh=$unix->sh_command($cmdline);
    $unix->go_exec($sh);

	$c=1;
	for($i=0;$i<10;$i++){
		sleep(1);
		_out("Starting service waiting $c/10");
		$pid=suricata_pid();
		if($unix->process_exists($pid)){
			_out("Success PID $pid");
			break;
		}
		$c++;
	}
    if(is_file("/var/log/suricata.start")){
        $tt=explode("\n",@file_get_contents("/var/log/suricata.start"));
        foreach ($tt as $line){
            if(is_null($line)){continue;}
            _out("Start $line");
        }
    }

	$pid=suricata_pid();
	if(!$unix->process_exists($pid)){
		_out("Starting Failed");
		_out("$cmdline");
		return false;
	}else{
		$nohup=$unix->find_program("nohup");
		_out("Restarting IDS logger");
		shell_exec("$nohup /etc/init.d/suricata-tail restart >/dev/null 2>&1 &");
		
		
		if(is_file("/bin/suricata-fw.sh")){
			_out("Running IDS firewall rules..");
			shell_exec("/bin/suricata-fw.sh");
		}
		return true;
	}

}
function stop():bool{
	$unix=new unix();
	$sock=new sockets();
	$masterbin=$unix->find_program("suricata");

	$pid=suricata_pid();
	if(!is_file($masterbin)){
		_out("Stopping Not installed");
		return true;

	}

	if(!$unix->process_exists($pid)){
		_out("Stopping Already stopped");
		return true;
	}


	$php5=$unix->LOCATE_PHP5_BIN();

	
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=suricata_pid();
		if(!$unix->process_exists($pid)){break;}
		_out("waiting pid:$pid $i/5...");
		unix_system_kill($pid);
		sleep(1);
	}
	

	_out("Stopping Shutdown pid $pid...");
	$pid=suricata_pid();
	
	if(!$unix->process_exists($pid)){
		_out("Stopping success...");
		@unlink("/var/run/suricata/suricata.pid");
		shell_exec("$php5 /usr/share/artica-postfix/exec.suricata-fw.php --delete >/dev/null 2>&1 &");
		unload_pfring();		
		return true;
	}

	_out("Stopping shutdown - force - pid $pid...");

	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=suricata_pid();
		if(!$unix->process_exists($pid)){break;}
		_out("waiting pid:$pid $i/5...");
		unix_system_kill_force($pid);
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		_out("Stopping success stopped...");
		@unlink("/var/run/suricata/suricata.pid");
		shell_exec("$php5 /usr/share/artica-postfix/exec.suricata-fw.php --delete >/dev/null 2>&1 &");
		unload_pfring();
		return true;
	}
    _out("Stopping failed...");
    return false;
}
function unload_pfring():bool{
	$unix=new unix();
	$rmmod=$unix->find_program("rmmod");

	
	if(!$unix->isModulesLoaded("pf_ring")){
		_out("Stopping pf_ring already unloaded");
        return true;
	}
	
	
	_out("Stopping Unloading pf_ring module...");
	system("$rmmod pf_ring >/dev/null 2>&1");
	
	for($y=0;$y<5;$y++){
		if(!$unix->isModulesLoaded("pf_ring")){break;}
		_out("Stopping waiting unloading pf_ring $y/5");
		sleep(1);
		system("$rmmod pf_ring >/dev/null 2>&1");
			
	}
	
	_out("Stopping Unloading pf_ring done..");
    return true;
}





function suricata_pid(){
	$unix=new unix();
	$masterbin=$unix->find_program("suricata");
	$pid=$unix->get_pid_from_file('/var/run/suricata/suricata.pid');
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($masterbin);
}
function suricata_version(){
	$unix=new unix();
	if(isset($GLOBALS["suricata_version"])){return $GLOBALS["suricata_version"];}
	$squidbin=$unix->find_program("suricata");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -V 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#Suricata\s+version\s+([0-9\.]+)#i", $val,$re)){
			$GLOBALS["suricata_version"]=trim($re[1]);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SURICATA_VERSION",trim($re[1]));
			return $GLOBALS["suricata_version"];
		}
	}
}
function disablesid(){
	
	
	$q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
	
	if(!$q->TABLE_EXISTS("suricata_disablesid")){
	    $sql="CREATE TABLE IF NOT EXISTS `suricata_disablesid` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT  , `explain` TEXT  )";
	    $q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
        $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyExplain ON suricata_disablesid (explain)");
		
	}
	
	if($q->COUNT_ROWS("suricata_disablesid")==0){
		$sql="INSERT OR IGNORE INTO suricata_disablesid (ID,`explain`) VALUES 	
	('2200029','ICMPv6 unknown type'),('2200038','SURICATA UDP packet too small'),('2200070','SURICATA FRAG IPv4 Fragmentation overlap'),('2200072','SURICATA FRAG IPv6 Fragmentation overlap'),('2200073','SURICATA IPv4 invalid checksum'),
				('2200075','SURICATA UDPv4 invalid checksum'),
				('2200078','SURICATA UDPv6 invalid checksum'),
				('2200076','SURICATA ICMPv4 invalid checksum'),
				('2200079','SURICATA ICMPv6 invalid checksum'),('2200080','SURICATA IPv6 useless Fragment extension header'),('2240001','SURICATA DNS Unsollicited response'),('2240002','SURICATA DNS malformed request data'),('2240003','SURICATA DNS malformed response data'),('2221000','SURICATA HTTP unknown error'),('2221021','SURICATA HTTP response header invalid'),('2230002','SURICATA TLS invalid record type'),('2230003','SURICATA TLS invalid handshake message'),('2012811','ET DNS DNS Query to a .tk domain - Likely Hostile'),('2018438','ET DNS DNS Query for vpnoverdns - indicates DNS tunnelling'),('2014703','ET DNS Non-DNS or Non-Compliant DNS traffic on DNS port Reserved Bit Set - Likely Kazy'),('2014701','ET DNS Non-DNS or Non-Compliant DNS traffic on DNS port Opcode 6 or 7 set - Likely Kazy'),('2003068','ET SCAN Potential SSH Scan OUTBOUND'),('2013479','ET SCAN Behavioral Unusually fast Terminal Server Traffic, Potential Scan or Infection (Outbound)'),('2012086','ET SHELLCODE Possible Call with No Offset TCP Shellcode'),('2012088','ET SHELLCODE Possible Call with No Offset TCP Shellcode'),('2012252','ET SHELLCODE Common 0a0a0a0a Heap Spray String'),('2013319','ET SHELLCODE Unicode UTF-8 Heap Spray Attempt'),('2013222','ET SHELLCODE Excessive Use of HeapLib Objects Likely Malicious Heap Spray Attempt'),('2011507','ET WEB_CLIENT PDF With Embedded File'),('2010514','ET WEB_CLIENT Possible HTTP 401 XSS Attempt (External Source)'),('2010516','ET WEB_CLIENT Possible HTTP 403 XSS Attempt (External Source)'),('2010518','ET WEB_CLIENT Possible HTTP 404 XSS Attempt (External Source)'),('2010520','ET WEB_CLIENT Possible HTTP 405 XSS Attempt (External Source)'),('2010522','ET WEB_CLIENT Possible HTTP 406 XSS Attempt (External Source)'),('2010525','ET WEB_CLIENT Possible HTTP 500 XSS Attempt (External Source)'),('2010527','ET WEB_CLIENT Possible HTTP 503 XSS Attempt (External Source)'),('2012266','ET WEB_CLIENT Hex Obfuscation of unescape % Encoding'),('2012272','ET WEB_CLIENT Hex Obfuscation of eval % Encoding'),('2012398','ET WEB_CLIENT Hex Obfuscation of replace Javascript Function % Encoding'),('2101201','GPL WEB_SERVER 403 Forbidden'),('2101852','GPL WEB_SERVER robots.txt access'),('2016672','ET WEB_SERVER SQL Errors in HTTP 200 Response (error in your SQL syntax)')";
		$q->QUERY_SQL($sql);
		if(!$q->ok){echo $q->mysql_error."\n";}
	}
	
	$f=array();
	$results=$q->QUERY_SQL("SELECT * FROM suricata_disablesid ORDER BY ID");
	if(!$q->ok){echo $q->mysql_error."\n";}
	foreach($results as $index=>$ligne) {
		$f[]="1:{$ligne["ID"]}";
		
	}
	
	echo "/etc/pulledpork/disablesid.conf done with ". count($f)." rules...\n";
	@file_put_contents("/etc/pulledpork/disablesid.conf", @implode("\n", $f));
}

function Architecture():int{
    $unix = new unix();
    $uname = $unix->find_program("uname");
    exec("$uname -m 2>&1", $results);
    foreach ($results as $num => $val) {
        if (preg_match("#i[0-9]86#", $val)) {
            return 32;
        }
        if (preg_match("#x86_64#", $val)) {
            return 64;
        }
    }
    return 0;
}

function DebianVersion():int{
    $ver = trim(@file_get_contents("/etc/debian_version"));
    preg_match("#^([0-9]+)\.#", $ver, $re);
    if (preg_match("#squeeze\/sid#", $ver)) {
        return 6;
    }
    return intval($re[1]);

}
function suricata_dashboard():bool{
    $unix = new unix();
    $TimeFile = "{$GLOBALS["BASEDIR"]}/suricata.dashboard";

    $InfluxUseRemote = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
    if ($InfluxUseRemote == 1) {
        @unlink($TimeFile);
        return false;
    }

    if (!$GLOBALS["FORCE"]) {
        $TimeEx = $unix->file_time_min($TimeFile);
        if ($TimeEx < 15) {
            return false;
        }
    }

    $q = new postgres_sql();
    if (!$q->TABLE_EXISTS("suricata_events")) {
        return false;
    }

    $results = $q->QUERY_SQL("SELECT SUM(xcount) as tcount, severity FROM suricata_events GROUP BY severity");
    if (!$q->ok) {
        return false;
    }

    while ($ligne = pg_fetch_assoc($results)) {
        $severity = $ligne["severity"];
        $tcount = $ligne["tcount"];
        if ($tcount == 0) {
            continue;
        }
        $ARRAY["SEVERITIES"][$severity] = $tcount;

    }

    @unlink($TimeFile);
    @file_put_contents($TimeFile, serialize($ARRAY));
    @chmod($TimeFile, 0755);
    return true;
}
function parse_rulesToPostGres(){
    if (!is_file("/etc/suricata/rules/sid-msg.map")) {
        return;
    }
    $prefix = "INSERT INTO suricata_sig (signature,description,enabled) VALUES ";
    $f = explode("\n", @file_get_contents("/etc/suricata/rules/sid-msg.map"));
    $I = array();
    foreach ($f as $val) {
        $tr = explode("||", $val);
        $sig = intval(trim($tr[0]));
        if ($sig == 0) {
            echo "SIG  === 0 / $val\n";
            continue;
        }
        $explain = trim(pg_escape_string2($tr[1]));
        if ($explain == null) {
            continue;
        }
        if (strlen($explain) > 128) {
            $explain = substr($explain, 0, 128);
        }
        $I[] = "('$sig',E'$explain',1)";

    }

    if (count($I) == 0) {
        return;
    }

    $sql = $prefix . @implode(",", $I) . " ON CONFLICT DO NOTHING";

    $postgres = new postgres_sql();
    $postgres->QUERY_SQL($sql);
    if (!$postgres->ok) {
        echo $postgres->mysql_error . "\n";
    }
}
function firewall($signature):bool{
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";


    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("Starting Already Artica task running PID $pid since {$time}mn");
        return false;
    }

    @file_put_contents($pidfile, getmypid());


    $proxyname = $unix->hostname_g();
    $suffixTables = "-m comment --comment \"ArticaSuricata\"";
    $prefix = "INSERT INTO suricata_firewall (zdate,uduniq,signature,src_ip,dst_port,proto,proxyname) VALUES ";

    $zdate = date("Y-m-d H:i:s");
    $iptables = $unix->find_program("iptables");


    $ARRAY = array();
    $q = new postgres_sql();
    $sql = "SELECT * FROM suricata_events WHERE signature='$signature'";
    $results = $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error . "\n";
    }
    while ($ligne = @pg_fetch_assoc($results)) {
        $src_ip = $ligne["src_ip"];
        $zdate = $ligne["zdate"];
        $dst_ip = $ligne["dst_ip"];
        $dst_port = $ligne["dst_port"];
        $proto = $ligne["proto"];
        $proto = strtolower($proto);
        $uduniq = md5("$signature,$src_ip,$dst_port,$proto");
        $content = "('$zdate','$uduniq','$signature','$src_ip','$dst_port','$proto','$proxyname')";
        $sql_line = "$prefix $content ON CONFLICT DO NOTHING";
        $cmdline = "$iptables -I INPUT -p $proto -m $proto -s $src_ip --dport $dst_port -j DROP $suffixTables >>/var/log/suricata/tail.debug 2>&1";
        $ARRAY[$uduniq]["SQL"] = $sql_line;
        $ARRAY[$uduniq]["FW"] = $cmdline;
    }

    if (count($ARRAY) == 0) {
        return true;
    }

    foreach ($ARRAY as $num => $main) {
        $sql = $main["SQL"];
        if ($GLOBALS["VERBOSE"]) {
            echo $sql . "\n";
        }
        $q->QUERY_SQL($sql);
        shell_exec($main["FW"]);
        if ($GLOBALS["VERBOSE"]) {
            echo $main["FW"] . "\n";
        }
    }
return true;
}


