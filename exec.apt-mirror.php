<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["RESTART"]=true;}

if($argv[1]=="--mirror-list"){BuildMirrorConf();exit();}
if($argv[1]=="--perform"){perform();exit();}
if($argv[1]=="--schedules"){schedules();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--build"){BuildMirrorConf();exit;}
if($argv[1]=="--is"){is_size();exit();}
if($argv[1]=="--stop2"){stop2();exit();}
if($argv[1]=="--rmdir"){remove_aptdir();exit();}
if($argv[1]=="--move-folder"){move_folders();exit;}
if($argv[1]=="--syslog"){build_syslog();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop-service"){stop_service();exit;}
if($argv[1]=="--restart"){restart_service();exit;}
if($argv[1]=="--postmirror"){postmirror_report();exit;}

function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"debian-mirror.progress");
}

function is_size(){
    $unix=new unix();
    $config=BuildMirrorConfDefaults();
    $SrcDir=$config["webserverpath"];
    if(!is_dir($SrcDir)){@mkdir($SrcDir);}
    $DIRPART_INFO=$unix->DIRPART_INFO($SrcDir);
    $AIV=$DIRPART_INFO["AIV"];
    if($AIV==0){return false;}
    $CurrSize=$unix->DIRSIZE_BYTES_NOCACHE($SrcDir);
    if($GLOBALS["VERBOSE"]){echo "\n**********************\n[$SrcDir]: Current size: $CurrSize - ".$unix->FormatBytes($CurrSize/1024)."\n";}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AptMirrorRepoSize",$unix->FormatBytes($CurrSize/1024));
    $require=112742891520;
    $TotalSize=$AIV+$CurrSize;
    $requireCalc=$TotalSize-$require;
    _out("Requirement: ".$unix->FormatBytes($require/1024).
        " Available size: ".$unix->FormatBytes($AIV/1024).
        " Used size: ". $unix->FormatBytes($CurrSize/1024).
        " Container size: ".$unix->FormatBytes($TotalSize/1024).
        " Container Compatibility: ".$unix->FormatBytes($requireCalc/1024));

    if($requireCalc<1024){
        _out("No Enough size for operation, require ".$unix->FormatBytes($require/1024)." ".$unix->FormatBytes($requireCalc/1024). " Current ".$unix->FormatBytes($AIV/1024));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APT_MIRROR_NO_SIZE",serialize(array($require,$AIV)));
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APT_MIRROR_NO_SIZE",null);
    return true;
}

function update_version(){
    $unix = new unix();
    $ARROOT = ARTICA_ROOT;
    $daemon_src = "$ARROOT/bin/debian-mirror";
    $daemon_dst= "/usr/sbin/debian-mirror";
    $cpbin = $unix->find_program("cp");
    $daemon_dst_md5=null;
    $daemon_src_md5 = md5_file($daemon_src);

    if(is_file($daemon_dst)) { $daemon_dst_md5 = md5_file($daemon_dst); }
    if ($daemon_src_md5 == $daemon_dst_md5) { return false; }
    if (is_file($daemon_dst)) { @unlink($daemon_dst); }
    echo "[OK]: $daemon_src_md5 != $daemon_dst_md5 - Updating, please wait\n";
    shell_exec("$cpbin -f $daemon_src $daemon_dst");
    @chmod($daemon_dst,0755);
    return true;
}
function PID_NUM(){

    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/debian-mirror-http.pid");
    if($unix->process_exists($pid)){
        return $pid;
    }

    return $unix->PIDOF("/usr/sbin/apt-mirror");

}
function _out($text):bool{
    echo date("H:i:s")." [INIT]: debian-mirror: $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("debian-mirror", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}
function restart_service():bool{
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        _out("Service Already Artica task running PID $pid since {$time}mn");
        return false;
    }
    @file_put_contents($pidfile, getmypid());
    _out("Stopping APT-Mirror service");
    stop_service();
    if (!start(true)) {
        return false;
    }

    return true;
}

function stop_service($aspid=false){
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("service Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        _out("Service already stopped...");
        return true;
    }
    $pid=PID_NUM();
    _out("Shutdown pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        _out("Waiting to stop pid:$pid $i/5...");
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        _out("Stopping success...");
        return true;
    }

    _out("Shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        _out("Waiting to stop pid:$pid $i/5...");
        sleep(1);
    }

    if(!$unix->process_exists($pid)){
        _out("Service success to be stopped");
        return true;
    }

    _out("Stopping service failed...");
    return false;

}

function start($aspid=false){
    $unix=new unix();
    $Masterbin="/usr/sbin/debian-mirror";

    if(!is_file($Masterbin)) {
        update_version();

    }
    if(!is_file($Masterbin)){
        _out("$Masterbin not installed");
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            _out("Already Artica task running PID $pid since {$time}mn");
            return true;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        _out("Service already started $pid since {$timepid}Mn...");
        @file_put_contents("/var/run/debian-mirror-http.pid",$pid);
        return true;
    }
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    if($EnableNginx==1){
        _out("Listen 127.0.0.1:16324");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DebianMirrorListenPort",16324);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DebianMirrorListenAddr","127.0.0.1");
    }else{
        _out("Listen *:80");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DebianMirrorListenPort",80);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DebianMirrorListenAddr","");
    }

    build_monit();
    build_syslog();
    update_version();
    $unix->go_exec($Masterbin);

    for($i=1;$i<5;$i++){
        _out("Waiting $i/5");
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)) {
        _out("Success PID $pid");
        return true;
    }

    _out("Failed");
    _out("$Masterbin");
    return false;

}



function stop2(){
 $unix=new unix();
    $config=BuildMirrorConfDefaults();
    $SrcDir=$config["webserverpath"];
    $pids=$unix->PIDOF_PATTERN_ALL("wget.*?$SrcDir");
    foreach ($pids as $pid){
        if(!$unix->process_exists($pid)){continue;}
        $unix->KILL_PROCESS($pid,9);
    }

}
function stop(){
    $unix=new unix();
    $pid=$unix->PIDOF_PATTERN("perl /bin/apt-mirror");
    if(!$unix->process_exists($pid)){return true;}
    $unix->KILL_PROCESS($pid,9);
}

function remove_aptdir(){
    $unix=new unix();
    stop();
    stop2();
    $config=BuildMirrorConfDefaults();
    $find=$unix->find_program("find");
    $rm=$unix->find_program("rm");
    $filetemp=$unix->FILE_TEMP();
    build_progress(5,"{removing}...{$config["webserverpath"]}");
    shell_exec("$find {$config["webserverpath"]} -mindepth 2 -maxdepth 3 -type d >$filetemp 2>&1");
    $h=explode("\n",@file_get_contents($filetemp));
    @unlink($filetemp);
    $pr=10;
    foreach ($h as $directory) {
        if(!is_dir($directory)){continue;}
        $pr=$pr+10;
        if($pr>90){$pr=90;}
        build_progress($pr,"{removing} $directory");
        shell_exec("$rm -rf $directory >/dev/null 2>&1");
    }
    if(is_dir($config["webserverpath"])){shell_exec("$rm -rf {$config["webserverpath"]}");}
    is_size();
    build_progress(5,"{removing}...{$config["webserverpath"]} {success}");
}

function install(){
    $unix=new unix();
    build_progress(10,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableAptMirror",1);
    _out("Set feature to Enabled...");
    BuildMirrorConf();
    build_syslog();
    build_service();
    build_monit();
    start();
    $unix->Popuplate_cron_make("apt-mirror-size","*/10 * * * *",basename(__FILE__)." --is");
    shell_exec("/etc/init.d/cron reload");
    build_progress(100,"{success}");
}
function build_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/apt-mirror";
    $php5script=basename(__FILE__);
    $daemonbinLog="APT-Mirror Web service";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         apt-mirror";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop-service \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
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
function build_monit(){
    $unix=new unix();
    $tpath="/etc/monit/conf.d/APP_APT_MIRROR.monitrc";
    $md5=null;
    if(is_file($tpath)){$md5=md5_file($tpath);}
    $f[]="";
    $f[]="check process APP_APT_MIRROR with pidfile /var/run/debian-mirror-http.pid";
    $f[]="\tstart program = \"/etc/init.d/apt-mirror start --monit\"";
    $f[]="\tstop program = \"/etc/init.d/apt-mirror stop --monit\"";
    $f[]="\trestart program = \"/etc/init.d/apt-mirror restart --monit\"";
    $f[]="";

    @file_put_contents($tpath, @implode("\n", $f));
    if(!is_file($tpath)){echo "$tpath failed !!!\n";}
    $md52=md5_file($tpath);
    if($md52==$md5){return true;}
    $unix->go_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    _out("Writing $tpath with new config");
    return true;
}
function build_syslog(){
    $tfile      = "/etc/rsyslog.d/apt-mirror.conf";
    $oldmd      = md5_file($tfile);
    echo "$tfile [$oldmd]\n";
    $add_rules = BuildRemoteSyslogs("apt-mirror","apt-mirror");

    $h=array();
    $h[]="";
    $h[]="if  (\$programname =='apt-mirror') then {";
    if(strlen($add_rules)>3) {$h[] = $add_rules;}

    $h[] ="\t-/var/log/apt-mirror.log";
    $h[] ="\t&stop";
    $h[]="\t}";
    $h[]="";
    $h[]="";
    $h[]="if  (\$programname =='debian-mirror') then {";

    if(strlen($add_rules)>3) {$h[] = $add_rules;}

    $h[] ="\t-/var/log/apt-mirror.log";
    $h[] ="\t&stop";
    $h[]="\t}";
    $h[]="";
    @file_put_contents($tfile,@implode("\n",$h));
    $newmd      = md5_file($tfile);
    echo "$tfile [OK]\n";
    if($oldmd<>$newmd){
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

}


function uninstall(){
    build_progress(5,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableAptMirror",0);
    $config=BuildMirrorConfDefaults();
    $unix=new unix();
    $find=$unix->find_program("find");
    $rm=$unix->find_program("rm");
    $filetemp=$unix->FILE_TEMP();

    if(!preg_match("#^\/home#",$config["webserverpath"])) {
        build_progress(110,"{failed} hacked path {$config["webserverpath"]}");
        return false;
    }
    stop();
    build_progress(8,"{uninstalling}");
    stop2();


    shell_exec("$find {$config["webserverpath"]} -mindepth 2 -maxdepth 3 -type d >$filetemp 2>&1");
    $h=explode("\n",@file_get_contents($filetemp));
    @unlink($filetemp);
    $pr=10;
    foreach ($h as $directory) {
        if(!is_dir($directory)){continue;}
        $pr=$pr+10;
        if($pr>90){$pr=90;}
        build_progress($pr,"{removing} $directory");
        shell_exec("$rm -rf $directory >/dev/null 2>&1");
    }

    $cron[]="apt-mirror-size";
    foreach ($cron as $fname){
        if(is_file("/etc/cron.d/$fname")){@unlink("/etc/cron.d/$fname");}
    }

    shell_exec("$rm -f /etc/cron.d/apt-mirror-* >/dev/null 2>&1");
    shell_exec("/etc/init.d/cron reload");
    build_progress(100,"{success}");
    return true;
}

function schedules(){
	@unlink("/etc/cron.d/apt-mirror");
	$php=LOCATE_PHP5_BIN2();
	$file=__FILE__;

	shell_exec("/bin/rm -f /etc/cron.d/apt-mirror-* >/dev/null 2>&1");
	
	$config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AptMirrorConfigSchedule")));
	if(!is_array($config)){return;}
		$count=0;
		while (list ($uid, $schedule) = each ($config) ){
			if(trim($schedule)==null){continue;}
			$f[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
			$f[]="MAILTO=\"\"";
			$f[]="$schedule  root $php $file --perform >/dev/null 2>&1";
			$f[]="";
			@file_put_contents("/etc/cron.d/apt-mirror-$count",@implode("\n",$f));
			$count++;
			unset($f);
		}	
		shell_exec("/etc/init.d/cron reload");
	}

function postmirror(){
    $unix=new unix();
    $config=BuildMirrorConfDefaults();
    $MainDir=$config["webserverpath"]."/var";
    $postmirror="$MainDir/postmirror.sh";
    if(!is_dir($MainDir)){@mkdir($MainDir,0755,true);}
    $h[]="#!/bin/sh -e";
    $php=$unix->LOCATE_PHP5_BIN();
    $h[]="$php ". __FILE__." --postmirror";
    $h[]="";
    @file_put_contents($postmirror,@implode("\n",$h));
    @chmod($postmirror,0755);

}

function perform(){
	$sock=new sockets();
	$EnableAptMirror=$sock->GET_INFO("EnableAptMirror");
	
	if($EnableAptMirror<>1){
		echo " Debian mirror feature is disabled\n";
		_out("Debian mirror feature is disabled");
		exit();
	}	
	
	$unix=new unix();
	$pidpath="/etc/artica-postfix/cron.2/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidpath);
    build_syslog();
	if($unix->process_exists($pid)){
			_out("Debian mirror already executed PID $pid");
			exit();
	}
	$getmypid=getmypid();
    $config=BuildMirrorConfDefaults();
    if(!is_size()){return false;}
    postmirror();
	@file_put_contents($pidpath,$getmypid);
    _out("INFO: Starting PID $getmypid");
	_out("Pid path: $pidpath");

	BuildMirrorConf();
	$t1=time();
	$apt_mirror_bin=$unix->find_program("apt-mirror");
    if(!is_file($apt_mirror_bin)){exit();}
	exec("$apt_mirror_bin 2>&1",$results);
	$t2=time();
	$distanceOfTimeInWords=distanceOfTimeInWords($t1,$t2);

	_out("$distanceOfTimeInWords ($t1,$t2)");
    foreach ($results as $line) {
        _out($line);
    }

    _out("INFO: Starting calculate directory size...");
	$repos_size=mirror_size();
    _out("INFO: Starting calculate {$config["webserverpath"]} directory size=$repos_size");
    return true;
}
function postmirror_report(){
    $unix=new unix();
    $config=BuildMirrorConfDefaults();
    $clean="{$config["webserverpath"]}/var/clean.sh";
    if(is_file($clean)){
        @chmod($clean,0755);
        _out("Running Clean script.");

        exec("$clean 2>&1",$clean_results);
        foreach ($clean_results as $line){
            $line=trim($line);
            if($line==null){continue;}
            _out("[CLEAN] $line");
        }
    }

    $size=mirror_size();
    squid_admin_mysql(2,"Debian Mirroring performed storage:$size");
    _out("Debian Mirroring performed OK storage:$size");


    return true;

}

function mirror_size(){
    $unix=new unix();
    if(system_is_overloaded(basename(__FILE__))){return false;}
    $config=BuildMirrorConfDefaults();
    $repos_size=null;
    $du_bin=$unix->find_program("du");
    $NICE=$unix->EXEC_NICE();

    if(!is_dir($config["webserverpath"])){return false;}
    exec("$NICE$du_bin -h -s {$config["webserverpath"]}",$results2);
    foreach ($results2 as $line){
        if(!preg_match("#^([0-9\.,]+)([A-Za-z]+)\s+#",$line,$ri)){continue;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AptMirrorRepoSize","{$ri[1]}{$ri[2]}");
        $repos_size="{$ri[1]}{$ri[2]}";
        break;
    }

    return $repos_size;

}





function BuildProxy():string{
    $ArticaProxyServerEnabled=0;
    $ini=new Bs_IniHandler("/etc/artica-postfix/settings/Daemons/ArticaProxySettings");

    if(!isset($ini->_params["PROXY"])){
        $ini->_params["PROXY"]["ArticaProxyServerEnabled"]=0;
        $ini->_params["PROXY"]["ArticaProxyServerName"]=null;
    }
    if(!isset($ini->_params["PROXY"]["ArticaProxyServerEnabled"])){$ini->_params["PROXY"]["ArticaProxyServerEnabled"]=0;}else{
        $ArticaProxyServerEnabledVal=trim($ini->_params["PROXY"]["ArticaProxyServerEnabled"]);
        if($ArticaProxyServerEnabledVal==null){$ArticaProxyServerEnabled=0;}
        if($ArticaProxyServerEnabledVal=="yes"){$ArticaProxyServerEnabled=1;}
        if($ArticaProxyServerEnabledVal=="no"){$ArticaProxyServerEnabled=0;}
        if(is_numeric($ArticaProxyServerEnabledVal)){$ArticaProxyServerEnabled=intval($ArticaProxyServerEnabledVal);}

    }
    if(!isset($ini->_params["PROXY"]["ArticaProxyServerName"])){$ini->_params["PROXY"]["ArticaProxyServerName"]=null;}
    $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
    if($ArticaProxyServerName==null){$ArticaProxyServerEnabled=0;}

    if($ArticaProxyServerEnabled==0){
            return "set use_proxy\toff";
    }

    $ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
    $ArticaProxyServerUsername=$ini->_params["PROXY"]["ArticaProxyServerUsername"];
    $ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];
    if(trim($ArticaProxyServerPort==null)){$ArticaProxyServerPort=80;}
    if(!is_numeric($ArticaProxyServerPort)){$ArticaProxyServerPort=80;}

    $f[]="set use_proxy\ton";
    $f[]="set http_proxy\thttp://$ArticaProxyServerName:$ArticaProxyServerPort/";

    if($ArticaProxyServerUsername<>null){
        $f[]="set proxy_user\t$ArticaProxyServerUsername";
        $f[]="set proxy_password\t$ArticaProxyServerUserPassword";
    }
    return @implode("\n",$f);

}

function BuildMirrorConfDefaults(){
    $config=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AptMirrorConfig")));
    if(!isset($config["debian_mirror"])){$config["debian_mirror"]="http://ftp.de.debian.org/";}
    if(!isset($config["DebianEnabled"])){$config["DebianEnabled"]=0;}
    if(!isset($config["UbuntuCountryCode"])){$config["UbuntuCountryCode"]="us";}
    if(!isset($config["nthreads"])){$config["nthreads"]=2;}
    if(!isset($config["webserverpath"])){$config["webserverpath"]="/home/artica/apt-mirror";}
    if($config["UbuntuCountryCode"]==null){$config["UbuntuCountryCode"]="us";}
    if($config["nthreads"]==null){$config["nthreads"]=2;}
    if(!isset($config["bullseye"])){$config["bullseye"]=0;}
    if($config["webserverpath"]==null){$config["webserverpath"]="/home/artica/apt-mirror";}
    if(!is_numeric($config["nthreads"])){$config["nthreads"]=2;}
    if(!isset($config["webserverpath_migr"])){$config["webserverpath_migr"]=null;}
    if(!isset($config["kbs"])){$config["kbs"]=0;}


    return $config;
}


function move_folders(){
    $config=BuildMirrorConfDefaults();
    $unix=new unix();
    if(!is_dir($config["webserverpath"])){return true;}
    if($config["webserverpath_migr"]==null){return true; }
    $rsync=$unix->find_program("rsync");
    $find=$unix->find_program("find");
    if(!is_dir($config["webserverpath_migr"])){
        @mkdir($config["webserverpath_migr"],0666,true);
    }
    if(!is_dir($config["webserverpath_migr"])){return false;}
    _out("Moving old data from {$config["webserverpath"]} to {$config["webserverpath_migr"]}");
    exec("$rsync -arctuxzv --remove-source-files {$config["webserverpath"]}/ {$config["webserverpath_migr"]}/ 2>&1",$results);
    foreach ($results as $line){
        _out("Moving log:$line");
    }

    $isAprt=$unix->is_a_partition($config["webserverpath"]);
    if($isAprt){
        _out("{$config["webserverpath"]} is a mounted path...");
        shell_exec("$find {$config["webserverpath"]}/ -depth -type d -empty -delete");
    }else{
        shell_exec("$find {$config["webserverpath"]} -depth -type d -empty -delete");
    }


    if(!$isAprt) {
        @rmdir($config["webserverpath"]);
        if (!is_dir($config["webserverpath"])) {
            _out("New path is now {$config["webserverpath_migr"]}...");
            $config["webserverpath"] = $config["webserverpath_migr"];
            $config["webserverpath_migr"] = null;
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AptMirrorConfig", base64_encode(serialize($config)));
            is_size();
            return true;
        }
    }else{
        _out("New path is now {$config["webserverpath_migr"]}...");
        $config["webserverpath"] = $config["webserverpath_migr"];
        $config["webserverpath_migr"] = null;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("AptMirrorConfig", base64_encode(serialize($config)));
        is_size();
        return true;
    }
    _out("Moving old data failed {$config["webserverpath"]} still exists");
    return false;
}

function BuildMirrorConf(){
    $EnableAptMirror=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableAptMirror"));
	if($EnableAptMirror==0){echo " Debian mirror feature is disabled\n";return false;}
    $config=BuildMirrorConfDefaults();
    if(!isset($config["Schedule"])){$config["Schedule"]=180;}
    $ctcode=$config["UbuntuCountryCode"];
    if(!is_dir($config["webserverpath"])) {
        @mkdir($config["webserverpath"], 0666, true);
    }
    if($config["webserverpath_migr"]<>null){
        if(!move_folders()){
            _out("Error: Aborting due to move error..");
            die();
        }

        _out("New accepted path [{$config["webserverpath_migr"]}]");
        $config=BuildMirrorConfDefaults();

    }

    $paths[]="mirror";
    $paths[]="skel";
    $paths[]="var";



    foreach ($paths as $subpath){
        if(!is_dir("{$config["webserverpath"]}/$subpath")) {
            _out("Creating new path [{$config["webserverpath"]}/$subpath]");
            @mkdir("{$config["webserverpath"]}/$subpath", 0666, true);
        }
    }


    $f[]="set base_path    {$config["webserverpath"]}";
    $f[]="set mirror_path  \$base_path/mirror";
    $f[]="set skel_path    \$base_path/skel";
    $f[]="set var_path     \$base_path/var";
    $f[]="set cleanscript \$var_path/clean.sh";
    $f[]="set defaultarch  amd64";
    $f[]="set nthreads     {$config["nthreads"]}";
    if($config["kbs"]>0){
        $f[]="set limit_rate {$config["kbs"]}k";
    }
    $f[]="set _tilde 0";
    $f[]=BuildProxy();




    $f[]="############## end config ##############";
    $f[]="deb http://ftp.$ctcode.debian.org/debian/ buster main contrib non-free";
    $f[]="deb http://ftp.$ctcode.debian.org/debian/ buster-updates main contrib non-free";
    $f[]="deb http://ftp.$ctcode.debian.org/debian/ buster-proposed-updates main contrib non-free";
    $f[]="deb http://ftp.$ctcode.debian.org/debian/ buster-backports main contrib non-free";


    $f[]="deb http://security.debian.org/debian-security buster/updates main contrib non-free";
    if($config["bullseye"]==1){
        $f[]="deb http://ftp.$ctcode.debian.org/debian/ bullseye main contrib non-free";
        $f[]="deb http://ftp.$ctcode.debian.org/debian/ bullseye-updates main contrib non-free";
        $f[]="deb http://ftp.$ctcode.debian.org/debian/ bullseye-proposed-updates main contrib non-free";
        $f[]="deb http://ftp.$ctcode.debian.org/debian/ bullseye-backports main contrib non-free";
        $f[]="deb http://security.debian.org/debian-security bullseye/updates main contrib non-free";
    }

    $f[]="clean http://ftp.$ctcode.debian.org";
	$f[]="";

    $Timez[5]="*/5 * * * *";
    $Timez[10]="*/10 * * * *";
    $Timez[15]="*/15 * * * *";
    $Timez[30]="*/30 * * * *";
    $Timez[60]="17 * * * *";
    $Timez[120]="17 */2 * * *";
    $Timez[180]="17 */3 * * *";
    $Timez[360]="17 */6 * * *";
    $Timez[720]="17 */12 * * *";
    $Timez[1440]="17 0 * * *";
    $Timez[2880]="17 0 */2 * *";

    $unix=new unix();
    $schedule=$Timez[$config["Schedule"]];
    $unix->Popuplate_cron_make("apt-mirror",$schedule,basename(__FILE__)." --perform");
    @file_put_contents("/etc/apt/mirror.list",@implode("\n",$f));
    return true;

}

