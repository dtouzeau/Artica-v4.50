<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["BOOT"]=false;
$GLOBALS["MONIT"]=false;
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.hosts.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

$GLOBALS["TITLENAME"]="Passive asset detection system";
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
    if(preg_match("#--boot#",implode(" ",$argv))){$GLOBALS["BOOT"]=true;}
    if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
	
	$system_is_overloaded=system_is_overloaded(basename(__FILE__));
	
	if($system_is_overloaded){
		echo "OVERLOADEDDDDDD!!!\n";
		writelogs("System is overloaded ({$GLOBALS["SYSTEM_INTERNAL_LOAD"]}), aborting...","MAIN",__FILE__,__LINE__);
		exit();
	}	
	

if($argv[1]=='--build'){$GLOBALS["OUTPUT"]=true;build();exit;}
$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop($argv[2]);exit();}
if($argv[1]=="--start"){start($argv[2]);die();}
if($argv[1]=="--start-www"){$GLOBALS["OUTPUT"]=true;start_www();die(1);}
if($argv[1]=="--stop-www"){$GLOBALS["OUTPUT"]=true;stop_www();die(1);}
if($argv[1]=="--restart-www"){$GLOBALS["OUTPUT"]=true;restart_www();die(1);}
if($argv[1]=="--restart"){restart($argv[2]);exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--loggoff"){loggoff();exit();}
if($argv[1]=="--disconnect"){disconnect();exit();}
if($argv[1]=="--connect"){connect();exit();}
if($argv[1]=="--interfaces"){interfaces();exit();}
if($argv[1]=="--reconnect"){reconnect();exit();}
if($argv[1]=="--repository"){repository();exit();}



if($argv[1]=="--infos"){$GLOBALS["OUTPUT"]=true;infos();exit();}

function restart($type=null):bool{
    if($type=="web"){return restart_www();}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
        build_progress(110,"Already Artica task running PID $pid since {$time}mn");
		return false;
	}
	@file_put_contents($pidfile, getmypid());
    build_progress(20,"{stopping_service}");
	stop(true);
    build_progress(80,"{starting_service}");
	sleep(1);
	start(true);
    build_progress(100,"{restarting} {success}");
    return true;

}
function restart_www():bool{
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        _out("Web Service: Unable to restart already Artica task running PID $pid since {$time}mn");
        build_progress(110,"Already Artica task running PID $pid since {$time}mn");
        return false;
    }
    @file_put_contents($pidfile, getmypid());
    build_progress(20,"Web Service: {stopping_service}");
    stop_www(true);
    build_progress(80,"Web Service: {starting_service}");
    sleep(1);
    start_www(true);
    build_progress(100,"Web Service: {restarting} {success}");
    return true;
}
function build_progress($pourc,$text){
	$unix=new unix();
	$unix->framework_progress($pourc,$text,"tailscale.enable.progress");
}
function install(){
    $unix=new unix();
	build_progress(15,"{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableTailScaleService", 1);
    $unix->create_service("tailscale",__FILE__);
    $unix->create_service("tailscale-web",__FILE__,"web");
	build_progress(20,"{installing}");
    $qnet=new lib_sqlite("/home/artica/SQLITE/interfaces.db");

	build_progress(90,"{installing}");
    build_monit();
    build_progress(91,"{installing}");
    build_syslog();
    build_progress(92,"{installing}");
    repository();

    if(!is_file("/etc/cron.d/tailscale-upgrade")){
        $unix->Popuplate_cron_make("tailscale-upgrade","10 15 * * SUN","exec.tailscale.php --repository");
        UNIX_RESTART_CRON();
    }


	build_progress(100,"{done}");
}
function build_syslog(){
    $RELOAD = false;
    $tfile  = "/etc/rsyslog.d/TailScale.conf";
    if(!is_file($tfile)){$RELOAD=true;}

    $f[]="if  (\$programname =='tailscale') then {";
    $f[]="\t-/var/log/tailscale.log";
    $f[]="\t& stop";
    $f[]="}\n";

    @file_put_contents($tfile,@implode("\n",$f));
    if($RELOAD){$unix=new unix();$unix->RESTART_SYSLOG(true);}

}


function reconnect():bool{
    $unix       = new unix();
    $pid        = PID_NUM();
    $addon      = null;
    if($GLOBALS["BOOT"]){$addon=" ( executed by Artica Network )";}
    if(!$unix->process_exists($pid)){
        _out("Reconnect: Service is stopped$addon, start it before...");
        start();
        _out("Connecting..");
        connect();
        return true;
    }
    _out("Disconnecting..");
    disconnect();
    sleep(1);
    _out("Connecting..");
    connect();
    return true;
}

function uninstall(){
	$unix=new unix();
	build_progress(15,"{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableTailScaleService", 0);
    build_progress(50,"{uninstalling}");

    $srclist="/etc/apt/sources.list.d/tailscale.list";
    if(is_file($srclist)){
        $dpkg=$unix->find_program("dpkg");
        shell_exec("$dpkg --remove --force-remove-reinstreq tailscale");
    }
    if(is_file("/etc/cron.d/tailscale-upgrade")){
        @unlink("/etc/cron.d/tailscale-upgrade");
        UNIX_RESTART_CRON();
    }

	$unix->remove_service("tailscale");
	$unix->remove_service("tailscale-web");
    if(is_file("/etc/monit/conf.d/APP_TAILSCALE.monitrc")){
        @unlink("/etc/monit/conf.d/APP_TAILSCALE.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    }

    $srclist="/etc/apt/sources.list.d/tailscale.list";
    if(is_file($srclist)){@unlink($srclist);}
    $q=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    $q->QUERY_SQL("DELETE FROM nics WHERE Interface='tailscale0'");
    $q->QUERY_SQL("DELETE FROM routing_rules_src WHERE nic='tailscale0'");
    $q->QUERY_SQL("DELETE FROM routing_rules WHERE nic='tailscale0'");
    $q->QUERY_SQL("DELETE FROM routing_rules_dest WHERE nic='tailscale0'");
    build_progress(100,"{uninstalling} {done}");
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function build_monit(){
	$f[]="check process APP_TAILSCALE with pidfile /var/run/tailscale/tailscale.pid";
	$f[]="\tstart program = \"/etc/init.d/tailscale start --monit --force\"";
	$f[]="\tstop program = \"/etc/init.d/tailscale stop --monit\"";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_TAILSCALE.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_TAILSCALE.monitrc")){
		echo "/etc/monit/conf.d/APP_TAILSCALE.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

    $f[]="check process APP_TAILSCALE_WEB with pidfile /var/run/tailscale/tailscalwww.pid";
    $f[]="\tstart program = \"/etc/init.d/tailscale start-www --monit\"";
    $f[]="\tstop program = \"/etc/init.d/tailscale stop-www --monit\"";
    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_TAILSCALE.monitrc", @implode("\n", $f));
    if(!is_file("/etc/monit/conf.d/APP_TAILSCALE.monitrc")){
        echo "/etc/monit/conf.d/APP_TAILSCALE.monitrc failed !!!\n";
    }
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}



function _out($text):bool{
    $unix=new unix();
    $unix->ToSyslog("$text",false,"tailscale");
    echo "TailScale service: $text\n";
    return true;
}

function start($type=null){
    $timenet            ="/run/network/artica-ifup.time";
    $unix               = new unix();
    $monit_text         = null;

    if($GLOBALS["MONIT"]){
        $monit_text=" ( by monit )";
        _out("Monitor Daemon try to start the TailScale service...");
    }

    if($type=="web"){return start_www();}

    $TailScaleIncomingPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleIncomingPort"));
	if($TailScaleIncomingPort==0){$TailScaleIncomingPort=41641;}
    $CMDS[]="/usr/sbin/tailscaled";
    $CMDS[]="--state=/var/lib/tailscale/tailscaled.state";
    $CMDS[]="--socket=/run/tailscale/tailscaled.sock --port $TailScaleIncomingPort";
    if(!is_dir("/var/run/tailscale")){@mkdir("/var/run/tailscale",0755,true);}
    if(!is_dir("/var/lib/tailscale")){@mkdir("/var/lib/tailscale",0755,true);}

    build_syslog();
	$pid=PID_NUM();


	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		_out("Service already started $pid since {$timepid}Mn");
		@file_put_contents("/var/run/tailscale/tailscale.pid", $pid);
		return true;
	}
    if(!$GLOBALS["MONIT"]) {
        if (!$GLOBALS["FORCE"]) {
            _out("To start TailScaile service, use --force in command-line$monit_text");
            return false;
        }
    }

    if(is_file($timenet)){
        $nettime=intval(@file_get_contents($timenet));
        $distance=$unix->time_min($nettime);
        if($distance==0){
            _out("Start: Waiting 20 seconds for the network to boot$monit_text");
            $unix->THREAD_COMMAND_SET("/etc/init.d/tailscale start");
            die();
        }
    }



    _out("Cleanup data...$monit_text");
	$unix->shell_command("/usr/sbin/tailscaled --cleanup >/var/log/tailscaled.cleanup");

    $results=explode("\n",@file_get_contents("/var/log/tailscaled.cleanup"));
    @unlink("/var/log/tailscaled.cleanup");
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out($line);
    }



	$cmd=@implode(" ", $CMDS)." >/var/log/tailscaled.start 2>&1";
    $unix->shell_command($cmd,true);
    if(is_file("/var/log/tailscaled.seek")){@unlink("/var/log/tailscaled.seek");}

	for($i=1;$i<5;$i++){
		_out("Starting, waiting $i/5$monit_text");
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		_out("Success PID $pid");
        @file_put_contents("/var/run/tailscale/tailscale.pid", $pid);
        start_www();
		return true;
	}



	_out("Starting Failed$monit_text");
    _out("failed with command line $cmd$monit_text");
    return false;
}

function parse_tailscale_start(){
    $seek       = "/var/log/tailscaled.seek";
    $seek_line  = 0;
    $seek_time  = 0;
    if(is_file($seek)) {
        $data = unserialize(@file_get_contents($seek));
        if(isset($data["seek_line"])){$seek_line=intval($data["seek_line"]);}
        if(isset($data["seek_time"])){$seek_time=intval($data["seek_time"]);}
    }

    if($seek_time>0){
        $time=filemtime($seek);
        if($time==$seek_time){return true;}
        if($time>$seek_time){return true;}
    }

    $results=explode("\n",@file_get_contents("/var/log/tailscaled.start"));

    $c=0;
    foreach ($results as $line){
        if($seek_line>0){
            if($c<$seek_line){continue;}
            if($c==$seek_line){continue;}
        }

        $line=trim($line);
        if($line==null){continue;}
        _out($line);
        $c++;

    }
    $data["seek_line"]=$c;
    $data["seek_time"]=time();
    @file_put_contents($seek,serialize($data));
    return true;

}

function stop($type=null):bool{
    $monit_text = null;
    if($type=="web"){return stop_www();}
	$unix=new unix();

    if($GLOBALS["MONIT"]){
        $monit_text=" ( by monit )";
        _out("Monitor Daemon try to stop the TailScale service...");
    }

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		_out("Stopping: service already stopped...$monit_text");
		return true;
	}
	$pid=PID_NUM();

	_out("Shutdown pid $pid...$monit_text");
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		_out("Stopping service waiting pid:$pid $i/5...$monit_text");
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		_out("Stopping service success...");
        _out("Cleanup data...");
        $unix->shell_command("/usr/sbin/tailscaled --cleanup >/var/log/tailscaled.cleanup");
		return true;
	}

	_out("Shutdown - force - pid $pid...");
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		_out("Waiting pid:$pid $i/5...");
		sleep(1);
	}

	if($unix->process_exists($pid)){
		_out("Stopping service failed...");
		return false;
	}
    _out("Cleanup data...");
    $unix->shell_command("/usr/sbin/tailscaled --cleanup >/var/log/tailscaled.cleanup");
    return true;
}

function stop_www($aspid=false):bool{
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("Web Service: service Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=www_pid();
    if(!$unix->process_exists($pid)){
        _out("Web Service: Service already stopped...");
        return true;
    }
    $pid=www_pid();

    _out("Web Service: Shutdown Web service pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=www_pid();
        if(!$unix->process_exists($pid)){break;}
        _out("Web Service: Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid=www_pid();
    if(!$unix->process_exists($pid)){
        _out("Web Service: Stopping web service success...");
        return true;
    }

    _out("Web Service: Shutdown Web service - force - pid $pid...");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        _out("Web Service: Waiting Web service pid:$pid $i/5...");
        sleep(1);
    }

    if($unix->process_exists($pid)){
        _out("Web Service: Stopping service failed...");
        return false;
    }
    return true;
}

function www_pid(){
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/tailscale/tailscalwww.pid");
    if($unix->process_exists($pid)){return intval($pid);}
    return intval($unix->PIDOF_PATTERN("tailscale web --listen"));
}

function start_www(){
    $unix=new unix();
    $cmd="/usr/sbin/tailscale web --listen 127.0.0.1:9877";
    $pid=www_pid();



    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        _out("Web Service: Service already started $pid since {$timepid}Mn");
        @file_put_contents("/var/run/tailscale/tailscalwww.pid", $pid);
        return true;
    }

    $unix->shell_command($cmd,true);
    $results=explode("\n",@file_get_contents("/var/log/tailscaled.start"));
    @unlink("/var/log/tailscaled.start");

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out($line);
    }

    for($i=1;$i<5;$i++){
        _out("Web Service: Starting, waiting $i/5");
        sleep(1);
        $pid=www_pid();
        if($unix->process_exists($pid)){break;}
    }

    $pid=www_pid();
    if($unix->process_exists($pid)){
        _out("Web Service: Success PID $pid");
        @file_put_contents("/var/run/tailscale/tailscalwww.pid", $pid);
        return true;
    }

    _out("Web Service: Starting web service Failed");
    _out("Web Service: Failed with command line $cmd");
    return false;
}

function PID_NUM():int{
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/tailscale/tailscale.pid");
	if($unix->process_exists($pid)){return intval($pid);}
	$Masterbin="/usr/sbin/tailscaled";
	return intval($unix->PIDOF($Masterbin));
}
	
function build(){
	$unix=new unix();
}

function repository_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"tailscale.apt.progress");
}
function _APP_TAILSCALE_VERSION():string{
    if(isset($GLOBALS[__FUNCTION__])){return $GLOBALS[__FUNCTION__];}
    $bin="/usr/sbin/tailscaled";
    exec("$bin -version 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#^([0-9\.]+)$#i",$line,$re)){continue;}
        $GLOBALS[__FUNCTION__]=$re[1];
        return strval($GLOBALS[__FUNCTION__]);
    }
    return "";
}

function repository(){
    $srclist="/etc/apt/sources.list.d/tailscale.list";
    if(!$GLOBALS["FORCE"]){
        if(is_file($srclist)){return true;}
    }

    $unix=new unix();
    $apt_key=$unix->find_program("apt-key");
    $temp=$unix->FILE_TEMP();
    $v1=_APP_TAILSCALE_VERSION();
    repository_progress(20,"Update repository");

    $unix->ToSyslog("Get the pgp key from pkgs.tailscale.com",false,"tailscale");
    $curl=new ccurl("https://pkgs.tailscale.com/stable/debian/buster.gpg");
    if(!$curl->GetFile($temp)){
        repository_progress(110,"Failed to Get the pgp key from pkgs.tailscale.com");
        $unix->ToSyslog("Failed to Get the pgp key from pkgs.tailscale.com $curl->error",false,"tailscale");
        squid_admin_mysql(0,"TailScale Fatal, unable to download gpg key");
        return false;
    }

   repository_progress(30,"Update repository");
   shell_exec("$apt_key add $temp");
   @unlink($temp);

   $f[]="# Tailscale packages for debian buster";
   $f[]="deb https://pkgs.tailscale.com/stable/debian buster main\n";
   @file_put_contents($srclist,@implode("\n",$f));
   $unix->ToSyslog("Running tailscale installation from debian repository",false,"tailscale");
   repository_progress(50,"Update repository");
   shell_exec("/usr/bin/apt-get -qy update --allow-releaseinfo-change");


   repository_progress(60,"{installing}");
   $unix->DEBIAN_INSTALL_PACKAGE("tailscale");


   $v2=_APP_TAILSCALE_VERSION();
   if($v1<>$v2) {
       $unix->create_service("tailscale", __FILE__);
       $unix->create_service("tailscale-web", __FILE__, "web");
       repository_progress(80, "{installing}");
       $unix->ToSyslog("Restarting tailscale...", false, "tailscale");
       shell_exec("/etc/init.d/tailscale restart");
       shell_exec("/etc/init.d/artica-status restart --force");
   }

   repository_progress(100,"{success} {from} $v1 {to} $v2");
   return true;

}

function infos(){
    if(!is_dir("/etc/tailscale")){@mkdir("/etc/tailscale",0755);}
    $tailscale_ip   = null;
    $unix=new unix();
    $nohup  = $unix->find_program("nohup");
    $tailscale="/usr/sbin/tailscale";
    exec("$tailscale ip --4 2>&1",$results);
    if(preg_match("#^([0-9\.]+)#",$results[0],$re)){
        $tailscale_ip=$re[1];
    }

    exec("$tailscale status --json 2>&1",$json);

    $sjson=json_decode(@implode("\n",$json));

    if(property_exists($sjson,"BackendState")){
        if(strtolower($sjson->BackendState)==strtolower("NeedsLogin")) {
            if(!is_file("/etc/tailscale/AuthURL.src")){
                _out("Execute for the first time the login on TailScale VPN service");
                shell_exec("$nohup $tailscale up >/etc/tailscale/AuthURL.src 2>&1 &");
                sleep(1);

            }
        }
        if(strtolower($sjson->BackendState)==strtolower("stopped")) {
            _out("Force Tailscale service to establish communication (Backend State = Stopped)");
            shell_exec("$nohup $tailscale up >/dev/null 2>&1 &");
            sleep(1);
        }

    }

    $pid=$unix->PIDOF_PATTERN("tailscale up");
    if($unix->process_exists($pid)) {
        _out("Destroy process $pid");
        $unix->KILL_PROCESS($pid,9);
    }
    $STATUS["PEERS"]=array();
    if(property_exists($sjson,"Peer")){
        foreach ($sjson->Peer as $index=>$Peer){
            echo "Discover peer = $index\n";
            $STATUS["PEERS"][]=$index;
        }
    }

    exec("/usr/sbin/ip -json route show table 52 2>&1",$table52);

    $STATUS["CURRENT_IP"]=$tailscale_ip;
    $STATUS["JSON"]=@implode("\n",$json);
    $STATUS["ROUTES"]=@implode("\n",$table52);
    @file_put_contents(PROGRESS_DIR."/tailscale.infos",serialize($STATUS));


}
function loggoff(){
    $tailscale="/usr/sbin/tailscale";
    system("$tailscale logout");
    if(is_file("/etc/tailscale/AuthURL.src")){@unlink("/etc/tailscale/AuthURL.src");}
    infos();
}
function disconnect(){
    $tailscale="/usr/sbin/tailscale";
    system("$tailscale down");
}
function interfaces(){
    $GLOBALS["VERBOSE"]=true;
    $unix=new unix();
   print_r($unix->NETWORK_ALL_INTERFACES());
}
function connect(){
    $unix=new unix();
    $tailscale="/usr/sbin/tailscale";
    $TailScaleAuthorizationKey=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleAuthorizationKey"));
    $LicenseInfos=unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LicenseInfos"));
    $WizardSavedSettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));
    if($LicenseInfos["COMPANY"]==null){$LicenseInfos["COMPANY"]=$WizardSavedSettings["company_name"];}
    if($LicenseInfos["EMAIL"]==null){$LicenseInfos["EMAIL"]=$WizardSavedSettings["mail"];}
    $uuid=$unix->GetUniqueID();
    $TailScaleHostname = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleHostname");
    $TailScaleInComCnx = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleInComCnx"));
    $TailScanUseSbunets = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScanUseSbunets"));
    $TailScanUseDNS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScanUseDNS"));
    $EnableArticaAsGateway = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaAsGateway"));
    $TailScaleAsGateway= intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TailScaleAsGateway"));
    if($EnableArticaAsGateway==0){$TailScaleAsGateway=0;}

    $LicenseInfos["COMPANY"]=str_replace(" ",'',$LicenseInfos["COMPANY"]);
    $LicenseInfos["COMPANY"]=str_replace("_",'',$LicenseInfos["COMPANY"]);

    $LicenseInfos["EMAIL"]=str_replace(" ",'',$LicenseInfos["EMAIL"]);
    $LicenseInfos["EMAIL"]=str_replace("_",'-',$LicenseInfos["EMAIL"]);
    $LicenseInfos["EMAIL"]=str_replace(".",'-',$LicenseInfos["EMAIL"]);
    $LicenseInfos["EMAIL"]=str_replace("@",'-',$LicenseInfos["EMAIL"]);


    $tags[]="tag:uuid-$uuid";

    if($LicenseInfos["COMPANY"]<>null){
        $tags[]="tag:{$LicenseInfos["COMPANY"]}";
    }
    if($LicenseInfos["EMAIL"]<>null){
        $tags[]="tag:{$LicenseInfos["EMAIL"]}";
    }
    $cmd[]="up";
    $cmd[]="--advertise-tags ".@implode(",",$tags);
    if($TailScaleHostname<>null){
        $cmd[]="--hostname $TailScaleHostname";
    }
    if($TailScaleInComCnx==1){
        $cmd[]="--shields-up=false";
    }else{
        $cmd[]="--shields-up";
    }
    if($TailScanUseSbunets==1){
        $cmd[]="--host-routes";
    }else{
        $cmd[]="--host-routes=false";
    }

    if($TailScanUseDNS==1){
        $cmd[]="--accept-dns";
    }else{
        $cmd[]="--accept-dns=false";
    }

    if($TailScaleAsGateway==1){
        $advertise=array();
        $qnet=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
        $qnet_results=$qnet->QUERY_SQL("SELECT pattern FROM networks_infos WHERE enabled=1 AND vpn=1");

        foreach ($qnet_results as $index=>$ligne){
            $ipaddr=$ligne["ipaddr"];
            if($ipaddr=="0.0.0.0/0.0.0.0"){continue;}
            $advertise[]=$ipaddr;
        }
        if(count($advertise)>0){$cmd[]="--advertise-routes=".@implode(",",$advertise);}
    }



    if($TailScaleAuthorizationKey<>null){
        $cmd[]="--authkey $TailScaleAuthorizationKey";
    }
    system("$tailscale ".@implode(" ",$cmd));

}

?>