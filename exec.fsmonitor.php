<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["FORCE"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--status"){status();exit();}
if($argv[1]=="--install"){install_service();exit;}
if($argv[1]=="--uninstall"){uninstall_service();exit;}
if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--schedule"){perform_schedule();exit;}
if($argv[1]=="--analyzediff"){$GLOBALS["VERBOSE"]=true;analyze_diffs($argv[2],$argv[3],null);exit;}

function perform_schedule(){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        _out("Already Artica task running PID $pid since {$time}mn");
        return false;
    }

    $ArticaFSMonSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonSchedule"));
    if($ArticaFSMonSchedule==1){
        permform_manual_scan();
    }

}
function permform_manual_scan(){
    $GLOBALS["SCANDIRS_REPORTS"];
    $bin="/usr/share/artica-postfix/bin/dirscan";
    $maindir="/home/artica/fsmonitor";
    if(is_link("/usr/bin/X11")){@unlink("/usr/bin/X11");}
    if(!is_dir($maindir)){@mkdir($maindir,0755,true);}
    @chmod($bin,0755);
    $ArticaFSMonWordpress=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonWordpress");
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $EnableWordpressManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement"));
    if($EnableWordpressManagement==0){$ArticaFSMonWordpress=0;}
    if($EnableNginx==0){$ArticaFSMonWordpress=0;}
    $mainconf="/etc/artica-postfix/artica-fsmonitor.conf";
    $f=explode("\n",@file_get_contents($mainconf));
    $ALREADY=array();
    $TOSCAN_PATHS=array();
    foreach ($f as $line) {
        if (!preg_match("#^\/home\/wordpress_sites#", $line)) {
            continue;
        }
        if (!preg_match("#^(.+?)\|(.+)#", $line, $re)) {
            continue;
        }
        $path = $re[1];
        if (isset($ALREADY[$path])) {
            continue;
        }
        $opts = explode("|", $re[2]);
        $TOSCAN_PATHS[$path]=$opts;

    }

    if($ArticaFSMonWordpress==1){
        $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $results=$q->QUERY_SQL("SELECT hostname,ID FROM wp_sites WHERE enabled=1");
        if(!$q->ok){
            _out_syslog("Wordpress monitoring: $q->mysql_error");
        }
        _out_syslog("Wordpress monitoring: ".count($results)." Websites");
        foreach ($results as $index=>$WordpressLigne){
            $hostname=$WordpressLigne["hostname"];
            $path="/home/wordpress_sites/site{$WordpressLigne["ID"]}";
            $TOSCAN_PATHS[$path]=array(0,"wordpress-$hostname-{$WordpressLigne["ID"]}",0);
        }
    }

    $ALREADY=array();
    foreach ($TOSCAN_PATHS as $path=>$opts){
        if(isset($ALREADY[$path])){continue;}
        $rulename=$opts[2];
        $md5=md5($path);
        $tfile_src="$maindir/$md5.src";
        $tfile_dst="$maindir/$md5.dst";

        if(is_file($tfile_dst)){
            if(is_file($tfile_src)){@unlink($tfile_src);}
            @copy($tfile_dst,$tfile_src);
        }
        _out_syslog("Manual scan of $path ($rulename)");
        $ALREADY[$path]=true;
        shell_exec("$bin \"$path/\" >$tfile_dst 2>&1");
        if(is_file($tfile_src)){
            analyze_diffs($tfile_src,$tfile_dst,$rulename);
        }
    }

}
function analyze_diffs($tfile_src,$tfile_dst,$rulename){
    $array=analyze_file($tfile_src);
    $array2=analyze_file($tfile_dst);
    foreach ($array2 as $tpath=>$crc32){
        if($GLOBALS["VERBOSE"]){echo "Path: $tpath ($crc32)\n";}
        if(!isset($array[$tpath])){
            _out_scan("[CREATE]:[$rulename] $tpath");
            if($GLOBALS["VERBOSE"]){echo " \$array2[$tpath] not set\n";}
            continue;}
        if($GLOBALS["VERBOSE"]){echo "{$array[$tpath]}< <> > {$array2[$tpath]}\n";}
        if($array[$tpath]<>$array2[$tpath]){
            $GLOBALS["SCANDIRS_REPORTS"][]="$tpath as been modified with crc32 {$array2[$tpath]}";
            _out_scan("[CHANGE]:[$rulename] $tpath as been modified with crc32 {$array2[$tpath]}");
        }
    }

}
function analyze_file($filepath):array{
    $MAINARRAY=array();
    if($GLOBALS["VERBOSE"]){echo "****************** $filepath ****************** \n";}
    $f=explode("\n",@file_get_contents($filepath));
    foreach ($f as $line){
        if(strpos(" $line","can't open")>0){
            if($GLOBALS["VERBOSE"]){echo "$filepath: Skipped: $line\n";}
            continue;
        }
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#CRC32: (.+?),\s+name:\s+(.+)#",$line,$re)){
            if($GLOBALS["VERBOSE"]){echo "$filepath: Skipped: $line\n";}
            continue;}
        $filepath_scanned=trim($re[2]);
        $crc32=trim($re[1]);
        if($GLOBALS["VERBOSE"]){echo "$filepath: \$MAINARRAY[$filepath_scanned]=$crc32\n";}
        $MAINARRAY[$filepath_scanned]=$crc32;
    }
    return $MAINARRAY;
}


function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		_out("Already Artica task running PID $pid since {$time}mn");
        build_progress("{restarting} {failed}",110);
		return false;
	}
	@file_put_contents($pidfile, getmypid());
    build_progress("{restarting}",50);
	stop(true);
    build_progress("{restarting}",80);
	sleep(1);
	if(start(true)){
        build_progress("{restarting} {success}",100);
        return true;
    }
    build_progress("{restarting} {failed}",110);
    return false;

	
}
function build(){
    build_progress("{building}",50);
    buildconf();
    build_progress("{building} {success}",100);
}


function status(){
    $unix=new unix();

    $pid=PID_NUM();
    echo "PID:................ $pid\n";
    if(!$unix->process_exists($pid)){
        echo "Running:............ NO\n";
    }else{
        echo "Running:............ Yes\n";
    }
}

function uninstall_service(){
    build_progress("{uninstalling}",30);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMon",0);
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $INITD_PATH=INIT_PATH();
    build_progress("{uninstalling}",50);
    $unix->remove_service($INITD_PATH);
    $contfile       = "/etc/cron.d/articafs-monitor";
    $monitfile      = "/etc/monit/conf.d/APP_ARTICAFSMON.monitrc";
    $maindir        ="/home/artica/fsmonitor";
    if(is_file($monitfile)) {
       @unlink($monitfile);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    }
    if(is_file($contfile)){
        @unlink($contfile);
        shell_exec("/etc/init.d/cron reload");
    }
    build_progress("{uninstalling} {remove} $maindir",80);
    if(is_dir($maindir)){shell_exec("$rm -rf $maindir");}
    build_progress("{uninstalling} {success}",100);

}
function build_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"fsm.progress");

}

function install_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/artica-fsmonitor";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ArticaFSMon",1);
    build_progress("{installing}",30);
    $me=__FILE__;
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          artica-fsmonitor";
    $f[]="# Required-Start:    \$local_fs \$remote_fs \$syslog \$time";
    $f[]="# Required-Stop:     \$local_fs \$remote_fs \$syslog ";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: artica-fsmonitor daemon";
    $f[]="# chkconfig: 2345 11 89";
    $f[]="# description: artica-fsmonitor logger";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="   $php $me --start \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="   $php $me --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="   $php $me --restart \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="   $php $me --restart \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


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

    build_progress("{installing}",50);
    update_version();
    build_progress("{installing}",60);
    build_syslog();
    build_progress("{installing}",70);
    build_monit();
    build_progress("{installing}",80);
    $unix->go_exec("$INITD_PATH start");
    build_progress("{installing} {success}",100);
    return true;
}
function wordpress_monitoring($spath){
    $wp=array();
    $f[]="wp-admin";
    $f[]="wp-admin/includes";
    $f[]="wp-content";
    $f[]="wp-content/uploads";
    $f[]="wp-content/uploads/wp-file-manager-pro";
    $f[]="wp-includes";
    $f[]="wp-includes/Requests/Cookie";
    $f[]="wp-includes/customize";

    foreach ($f as $subdir){
        if(!is_dir("$spath/$subdir")){continue;}
        $mains["$spath/$subdir"]="$spath/$subdir";
    }
    foreach ($mains as $dir=>$none){
        $wp[]=$dir;
    }
    return $wp;
}

function buildconf($norestart=false){
    $mainconf="/etc/artica-postfix/artica-fsmonitor.conf";
    $ArticaFSMonWordpress=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonWordpress");
    $EnableNginx=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx"));
    $EnableWordpressManagement=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWordpressManagement"));
    if($EnableWordpressManagement==0){$ArticaFSMonWordpress=0;}
    if($EnableNginx==0){$ArticaFSMonWordpress=0;}

    $md51=null;
    $unix=new unix();
    $unix->Popuplate_cron_make("articafs-monitor","15 */2 * * *",basename(__FILE__)." --schedule");
    if(is_file($mainconf)) {
        $md51 = md5_file($mainconf);
    }
    $conf=array();
    $Directories=array();
    $defaults[]="/bin";
    $defaults[]="/usr/sbin";
    $defaults[]="/sbin";
    $defaults[]="/var/spool/cron/crontabs";

    foreach ($defaults as $line){
        if(!is_dir($line)){continue;}
        $Directories[$line]="0|0|default";
    }
    _out("Wordpress monitoring: $ArticaFSMonWordpress");
    if($ArticaFSMonWordpress==1){
        $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
        $results=$q->QUERY_SQL("SELECT hostname,ID FROM wp_sites WHERE enabled=1");
        if(!$q->ok){
            _out("Wordpress monitoring: $q->mysql_error");
        }
        _out("Wordpress monitoring: ".count($results)." Websites");
        foreach ($results as $index=>$WordpressLigne){
            $hostname=$WordpressLigne["hostname"];
            $path="/home/wordpress_sites/site{$WordpressLigne["ID"]}";
            _out("Wordpress monitoring: Monitor $path");
            $sdirs=wordpress_monitoring($path);
            foreach ($sdirs as $index=>$zdir){
                $Directories[$zdir]="0|0|wordpress-$hostname-{$WordpressLigne["ID"]}";
            }

        }
    }

    $RULES=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaFSMonParams")));
    foreach ($RULES as $ID=>$PARMS) {
        if ($ID == 0) {
            continue;
        }
        $DIRECTORY=$PARMS["DIRECTORY"];
        $ENABLED= $PARMS["ENABLED"];
        $RULENAME=$PARMS["RULENAME"];
        if($ENABLED==0){continue;}
        $Directories[$DIRECTORY]="0|0|$RULENAME";
    }



    $c=0;
    foreach ($Directories as $dir=>$zconf){
        $c++;
        $conf[]="$dir|$zconf";
    }
    _out("Writing $mainconf configuration file with: ($c) directories to monitor");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ARTICA_FSMON_DIRS",$c);
    @file_put_contents($mainconf,@implode("\n",$conf));
    $md52 = md5_file($mainconf);
    if($norestart){return true;}
    if($md51==$md52){return true;}
    restart();
    return true;
}


function start($aspid=false):bool{
	$unix=new unix();
    $daemon_pidfile=PID_PATH();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Already Artica task running PID $pid since {$time}mn");
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NUM();

	if($unix->process_exists($pid)){
        $monitor=null;
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["MONIT"]) {
            $monitor = "(Background monitor) ";
        }
         _out("{$monitor}Updating PID $daemon_pidfile with $pid");
         @file_put_contents($daemon_pidfile, $pid);

        _out("{$monitor}Service already started $pid since {$timepid}Mn..");
        _out("{$monitor}Service pid file: [$daemon_pidfile]..");
		return true;
	}


	if(is_file($pidfile)){@unlink($pidfile);}
    $maindir="/home/artica/fsmonitor/realtime";
    if(!is_dir($maindir)){@mkdir($maindir,0755,true);}
    $maindir="/home/artica/fsmonitor/notify";
    if(!is_dir($maindir)){@mkdir($maindir,0755,true);}
	$cmd=BIN_PATH();
    update_version();
    build_syslog();
    build_syslog2();
    build_monit();
    buildconf(true);
    $unix->go_exec($cmd);

	for($i=1;$i<5;$i++){
		_out("Waiting $i/5");
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		_out("Success PID $pid");
		return true;
	}
    _out("Failed");
	_out("$cmd");
    return false;


}
function _out_syslog($text){
    $date=date("Y/m/d H:i:s");
    $f = @fopen("/var/log/artica-monitorfs.debug", 'a');
    @fwrite($f, "$date [SCRIPT]: $text\n");
    @fclose($f);
    return true;
}
function _out_scan($text){
    if(!function_exists("openlog")){return false;}
    openlog("monitorfs-scan", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}
function _out($text):bool{
    echo date("H:i:s")." [INIT]: artica FS Monitor: $text\n";
    _out_syslog($text);
    return true;
}

function PID_PATH():string{
    return "/var/run/artica-monitorfs.pid";
}
function INIT_PATH():string{
    return "/etc/init.d/artica-fsmonitor";
}
function BIN_PATH():string{
    return "/usr/sbin/artica-fsmonitor";
}

function build_monit():bool{
    $monitfile      = "/etc/monit/conf.d/APP_ARTICAFSMON.monitrc";
    $oldmd      = null;
    if(is_file($monitfile)) {
        $oldmd = md5_file($monitfile);
    }
    $f[]="check process APP_ARTICAFSMON with pidfile ".PID_PATH();
    $f[]="\tstart program = \"".INIT_PATH()." start --monit\"";
    $f[]="";
    @file_put_contents($monitfile, @implode("\n", $f));
    $md52=md5_file($monitfile);
    if($oldmd==$md52){
        return true;
    }
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    return true;
}
function build_syslog2(){
    $unix=new unix();
    $tfile      = "/etc/rsyslog.d/monitorfs-scan.conf";
    $oldmd      = null;
    if(is_file($tfile)) {
        $oldmd = md5_file($tfile);
    }
    _out("$tfile [$oldmd]");
    $add_rules = BuildRemoteSyslogs("monitorfs-scan");

    $h=array();
    $h[]="";
    $h[]="if  (\$programname =='monitorfs-scan') then {";
    if(strlen($add_rules)>3) {$h[] = $add_rules;}
    $h[] ="\t-/var/log/monitorfs-scan.log";
    $h[] ="\t&stop";
    $h[]="\t}";
    $h[]="";
    @file_put_contents($tfile,@implode("\n",$h));
    $newmd      = md5_file($tfile);
    _out("$tfile [OK]");
    if($oldmd<>$newmd){
        $unix->RESTART_SYSLOG();
    }


}

function build_syslog(){
    $unix=new unix();
    $tfile      = "/etc/rsyslog.d/artica-monitorfs.conf";
    $oldmd      = null;
    if(is_file($tfile)) {
        $oldmd = md5_file($tfile);
    }
    _out("$tfile [$oldmd]");
    $add_rules = BuildRemoteSyslogs("artica-monitorfs");

    $h=array();
    $h[]="";
    $h[]="if  (\$programname =='artica-monitorfs') then {";
    if(strlen($add_rules)>3) {$h[] = $add_rules;}
    $h[] ="\t-/var/log/artica-monitorfs.log";
    $h[] ="\t&stop";
    $h[]="\t}";
    $h[]="";
    @file_put_contents($tfile,@implode("\n",$h));
    $newmd      = md5_file($tfile);
    _out("$tfile [OK]");
    if($oldmd<>$newmd){
        $unix->RESTART_SYSLOG();
    }

}

function stop($aspid=false):bool{
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
            _out("Stopping Service Already Artica task running PID $pid since {$time}mn");
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
        _out("Stopping service already stopped...");
		return true;
	}
	$pid=PID_NUM();

    _out("Stopping service Shutdown pid $pid...");
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
        _out("Stopping service waiting pid:$pid $i/5...");
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
        _out("Stopping service success...");
		return true;
	}

    _out("Stopping service shutdown - force - pid $pid...");
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
        _out("Stopping service waiting pid:$pid $i/5...");
		sleep(1);
	}

	if($unix->process_exists($pid)){
		_out("Stopping Service failed...");
		return true;
	}
return false;
}
function update_version():bool{
    $unix = new unix();
    $ARROOT = ARTICA_ROOT;
    $daemon_src = "$ARROOT/bin/" .basename(BIN_PATH());
    $daemon_dst= BIN_PATH();
    $cpbin = $unix->find_program("cp");
    $daemon_dst_md5=null;
    $daemon_src_md5 = md5_file($daemon_src);
    if(!is_file($daemon_dst)){
        _out(BIN_PATH()." Not installed - Updating, please wait");
        shell_exec("$cpbin -f $daemon_src $daemon_dst");
        @chmod($daemon_dst,0755);
        return true;
    }

    if(is_file($daemon_dst)) { $daemon_dst_md5 = md5_file($daemon_dst); }
    if ($daemon_src_md5 == $daemon_dst_md5) { return false; }
    if (is_file($daemon_dst)) { @unlink($daemon_dst); }
    _out("$daemon_src_md5 != $daemon_dst_md5 - Updating, please wait");
    shell_exec("$cpbin -f $daemon_src $daemon_dst");
    @chmod($daemon_dst,0755);
    return true;
}

function PID_NUM(){
	$unix=new unix();
    $pid=$unix->get_pid_from_file(PID_PATH());
    if($unix->process_exists($pid)){
        return $pid;
    }

    $pid=$unix->PIDOF(BIN_PATH());
    if($unix->process_exists($pid)){
        return $pid;
    }
    return 0;
}