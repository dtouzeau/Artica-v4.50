#!/usr/bin/php -q
<?php
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["PROGRESS"]=true;
$GLOBALS["CLI"]=false;
$GLOBALS["SIGTOOL"]=false;
$GLOBALS["TITLENAME"]="Clam AntiVirus virus database updater";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv),$re)){$GLOBALS["PROGRESS"]=true;}
if(preg_match("#--cli#",implode(" ",$argv),$re)){$GLOBALS["CLI"]=true;}
if(preg_match("#--with-sigtool#",implode(" ",$argv),$re)){$GLOBALS["SIGTOOL"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');


// /etc/clamav/freshclam.conf

$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload-database"){$GLOBALS["OUTPUT"]=true;reload_database();exit();}
if($argv[1]=="--reload-log"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--force-reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--execute"){$GLOBALS["OUTPUT"]=true;execute();exit();}
if($argv[1]=="--exec"){$GLOBALS["OUTPUT"]=false;execute();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--sigtool-ouput"){$GLOBALS["OUTPUT"]=false;sigtool_output();exit();}
if($argv[1]=="--sigtool"){$GLOBALS["OUTPUT"]=false;sigtool();exit();}
if($argv[1]=="--manu"){$GLOBALS["OUTPUT"]=false;update_manu($argv[2]);exit();}




function restart() {
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
        return;
    }
    @file_put_contents($pidfile, getmypid());
    freshclam_log("{restarting} {APP_FRESHCLAM}");
    build_progress(10, "{stopping} {APP_FRESHCLAM}");
    stop(true);
    build_progress(50, "{building_configuration}");
    syslog_config();
    build();
    sleep(1);
    build_progress(70, "{starting} {APP_FRESHCLAM}");
    if(start(true)){

        if($GLOBALS["PROGRESS"]){
            build_progress(95, "{restarting} {watchdog}");
            $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
        }

        build_progress(100, "{done} {APP_FRESHCLAM}");
    }


}

function update_manu($filename){

    $filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";

    if(strpos($filename,")")>0){
        $filename=str_replace("(","",$filename);
        $filename=str_replace(")","",$filename);
        $filename=str_replace(" ","",$filename);
        if(is_file(dirname(__FILE__)."/ressources/conf/upload/$filename")){
            @unlink(dirname(__FILE__)."/ressources/conf/upload/$filename");
        }
        @copy($filepath,dirname(__FILE__)."/ressources/conf/upload/$filename");
        $filepath=dirname(__FILE__)."/ressources/conf/upload/$filename";

    }

    echo "Source: $filepath\n";
    if(!is_file($filepath)){
        echo "$filepath no such file\n";
        build_progress("{failed}",110);
        @unlink($filepath);
        return false;
    }

    $unix=new unix();
    $unzip=$unix->find_program("unzip");
    if(!is_file($unzip)){
        build_progress("{installing} unzip",20);
        $unix->DEBIAN_INSTALL_PACKAGE("unzip");
        sleep(5);
        if(!is_file("/usr/bin/zip")) {
            squid_admin_mysql(1, "Warning, unable to found zip program", null, __FILE__, __LINE__);
            build_progress("Warning, unable to found zip program",110);
            @unlink($filepath);
            return false;
        }
    }

    build_progress("{uncompressing}",50);
    $FTEMP=$unix->TEMP_DIR()."/clamav";
    if(!is_dir($FTEMP)){@mkdir($FTEMP,0755,true);}

    shell_exec("$unzip $filepath -d $FTEMP/");
    @unlink($filepath);

    if (!$handle = opendir($FTEMP)) {
        build_progress("Warning, unable to EXTRACT PATTERNS",110);
        return false;
    }

    $prc=70;

    $TargetDir="/var/lib/clamav";
    if(!is_dir($TargetDir)) {
        @mkdir($TargetDir, 0755, true);
    }
    if(is_file($TargetDir)) {
        @unlink($TargetDir);
        @mkdir($TargetDir, 0755, true);
    }


    while (false !== ($filename = readdir($handle))) {
        $prc++;
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if(strlen($filename)<4){continue;}

        $fullpath="$FTEMP/$filename";

        if(!preg_match("#\.cvd$#",$filename)){
            build_progress("{installing} skipping $filename",$prc);
            continue;
        }

        $destination="$TargetDir/$filename";
        echo "installing $fullpath\n";
        $prc++;
        build_progress("{installing} $filename",$prc);
        if(is_file("$destination")){@unlink($destination);}
        @copy($fullpath,$destination);
    }

    $rm=$unix->find_program("rm");
    shell_exec("$rm -rf $FTEMP");


    if(is_file("/etc/init.d/clamav-daemon")){
        build_progress("{reloading}",80);
        shell_exec("/etc/init.d/clamav-daemon reload");
    }


    sleep(5);
    sigtool(80);
    build_progress("{success}",100);
    return true;

}

function reload_database($aspid=false){
    $unix=new unix();
    $sock=new sockets();
    $Masterbin=$unix->find_program("clamd");

    if(!is_file($Masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
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
    $kill=$unix->find_program("kill");
    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
        shell_exec("$kill -USR2 $pid");
        return;
    }

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}

}
function reload($aspid=false){
    $unix=new unix();
    $sock=new sockets();
    $Masterbin=$unix->find_program("clamd");

    if(!is_file($Masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
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
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service reloading PID $pid running since {$timepid}Mn...\n";}
        unix_system_HUP($pid);
        return;
    }

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not running\n";}

}



function execute(){
    $unix=new unix();
    $unix->CLAMAV_DIRECTORIES();
    fix_clamav_libraries();


    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pidTime="/var/run/clamav/scheduled.time";
    $pid=$unix->get_pid_from_file($pidfile);
    echo "Old pid: $pid\n";


    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        build_progress("Already Executed pid:$pid since {$time}mn",99);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}

        return;
    }
    syslog_config();
    $pid=$unix->PIDOF("/bin/freshexec");
    sleep(1);
    if($unix->process_exists($pid)){

        while($unix->process_exists($pid)){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            build_progress("Currently running since {$time}mn",50);
            sleep(5);
            $pid=$unix->PIDOF("/bin/freshexec");
        }
        sleep(5);
        sigtool();



        build_progress("{done}",100);
        return;


    }

    @file_put_contents($pidfile, getmypid());
    if(!$GLOBALS["FORCE"]){
        $TimEx=$unix->file_time_min($pidTime);
        if($TimEx<120){
            freshclam_log("Aborting task Only each 120mn, current is {$TimEx}mn");
            build_progress("Only each 120mn, current is {$TimEx}mn",110);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Only each 120mn, current is {$TimEx}mn\n";}
            return;
        }
    }
    @unlink($pidTime);
    @file_put_contents("$pidTime", time());
    build_progress("{udate_clamav_databases}",10);


    $Masterbin=$unix->find_program("freshclam");
    if(!is_file($Masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service freshclam not installed\n";}
        freshclam_syslog("Missing freshclam binary !!");
        build_progress("Missing freshclam",110);
        return false;
    }

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {building_settings}\n";}
    build_progress("{building_configuration}",20);
    build();

    $log=PROGRESS_DIR."/clamav.update.progress.txt";
    $verbose=" --verbose";



    $nohup=$unix->find_program("nohup");
    @chmod("/usr/share/artica-postfix/ressources/logs/web", 0777);
    if(is_file($log)) {
        @chmod($log, 0777);
    }

    if(is_file(dirname($Masterbin)."/freshexec")){@unlink(dirname($Masterbin)."/freshexec");}
    @copy($Masterbin, dirname($Masterbin)."/freshexec");
    @chmod(dirname($Masterbin)."/freshexec",0755);
    $Masterbin=dirname($Masterbin)."/freshexec";

    $curl=new ccurl("http://clamav.artica.center/dns.txt");
    $curl->NoHTTP_POST=true;
    if(!$curl->get()) {
        freshclam_syslog("clamav.artica.center HTTP Error $curl->error");
        squid_admin_mysql(0, "clamav.artica.center: {remote_http_service_unavailable}: $curl->error", null, __FILE__, __LINE__);
        return false;
    }


    @chmod("/usr/share/artica-postfix/exec.freshclam.updated.php",0755);
    @chmod("/usr/share/artica-postfix/exec.freshclam.failed.php",0755);

    $ClamUser=$unix->ClamUser();
    $tt[]="$nohup $Masterbin --config-file=/etc/clamav/freshclam.conf";
    $tt[]="--pid=/var/run/clamav/freshclam_manu.pid";
    $tt[]="--on-update-execute=/usr/share/artica-postfix/exec.freshclam.updated.php";
    $tt[]="--on-error-execute=/usr/share/artica-postfix/exec.freshclam.failed.php";
    $tt[]="--user=$ClamUser";
    $tt[]="--no-dns";
    $tt[]="--config-file=/etc/clamav/freshclam.conf";
    $tt[]="--log=$log$verbose >/dev/null 2>&1 &";


    $cmd=@implode(" ", $tt);

    if($GLOBALS["FORCE"]){
        if(is_file("/var/lib/clamav/freshclam.dat")){@unlink("/var/lib/clamav/freshclam.dat");}
    }

    $Dirs=$unix->dirdir("/var/lib/clamav");
    $rm=$unix->find_program("rm");

    foreach ($Dirs as $directory=>$MAIN){
        echo "Checking $directory\n";
        if(!preg_match("#\.tmp$#", $directory)){continue;}
        echo "Remove directory $directory";
        shell_exec("$rm -rf $directory");
    }



    build_progress("{udate_clamav_databases}",50);
    echo $cmd;
    $shfile=$unix->sh_command($cmd);

    if(is_file($log)){@unlink($log);}
    $unix->go_exec($shfile);
    $PID=fresh_clam_manu_pid();
    $WAIT=true;
    $counter=50;

    while ($WAIT) {
        if(!$unix->process_exists($PID)){
            break;
        }
        $ttl=$unix->PROCCESS_TIME_MIN($PID);
        echo "PID: Running $PID since {$ttl}mn\n";
        $counter++;
        if($counter>80){$counter=80;}
        build_progress("{udate_clamav_databases} {waiting} PID $PID {since} {$ttl}mn",$counter);
        sleep(2);
        $PID=fresh_clam_manu_pid();
    }
    if(is_file("/var/log/clamav/freshclam-update.log")){@unlink("/var/log/clamav/freshclam-update.log");}


    build_progress("{done}",90);
    @unlink("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases");


    sigtool();

    $Y=date("Y");
    $f=explode("\n",@file_get_contents($log));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#$Y\s+->\s+#",$line)){
            freshclam_log($line);
        }
    }

    build_progress("{done}",100);

}

function freshclam_log($text){
    if(!function_exists("syslog")){return false;}
    openlog("freshclam", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "[Artica]: $text");
    closelog();
    return true;
}
function freshclam_syslog($text){
    if(!function_exists("syslog")){return false;}
    openlog("freshclam", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "[Artica]: $text");
    closelog();
    return true;
}

function fresh_clam_manu_pid(){
    $unix=new unix();
    return $unix->PIDOF("/bin/freshexec");

}

function build_progress($text,$pourc){
    $echotext=$text;
    $unix=new unix();
    if(is_numeric($text)){
        $old=$pourc;
        $pourc=$text;
        $text=$old;
    }

    $echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
    $unix->framework_progress($pourc,$text,"clamav.update.progress");
    $unix->framework_progress($pourc,$text,"clamav.freshclam.progress");
    if($GLOBALS["PROGRESS"]){sleep(1);}

}

function sigtool_output(){
    sigtool();
    $bases=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/interface-cache/ClamAVBases"));

    if(count($bases)==0){
        echo "No database !!!!";
        return;
    }
    foreach ($bases as $db=>$MAIN){
        $DBS[]=$db;
        $DBS[]="-------------------------------";
        $DBS[]="date: {$MAIN["zDate"]}";
        $DBS[]="version: {$MAIN["version"]}";
        $DBS[]="signatures: {$MAIN["signatures"]}";
        $DBS[]="";
    }

    echo @implode("\\n", $DBS);


}



function start($aspid=false){
    $unix=new unix();
    $Masterbin=$unix->find_program("freshclam");

    if(!is_file($Masterbin)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, clamd not installed\n";}
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
        return false;
    }

    $EnableFreshClam=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreshClam"));
    $EnableClamavDaemon=intval($unix->EnableClamavDaemon());

    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableFreshClam...: $EnableFreshClam\n";
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableClamavDaemon: $EnableClamavDaemon\n";

    if($EnableClamavDaemon==1){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ClamAV Daemon is ON\n";
        $EnableFreshClam=1;
    }




    if($EnableFreshClam==0){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableFreshClam/EnableClamavDaemon)\n";
        build_progress(110, "{starting} {APP_FRESHCLAM} {disabled}");
        return false;
    }



    $aa_complain=$unix->find_program('aa-complain');
    if(is_file($aa_complain)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} add $Masterbin Profile to AppArmor..\n";}
        shell_exec("$aa_complain $Masterbin >/dev/null 2>&1");
    }

    $ClamUser=$unix->ClamUser();
    @chmod("/usr/share/artica-postfix/ressources/logs/web", 0777);

    @mkdir("/var/clamav",0755,true);
    @mkdir("/var/run/clamav",0755,true);
    @mkdir("/var/lib/clamav",0755,true);
    @mkdir("/var/log/clamav",0755,true);

    $folders[]=" /var/lib/clamav/tmp";
    $folders[]=" /var/lib/clamav";
    $folders[]=" /var/log/clamav";
    $folders[]=" /var/clamav";

    foreach ($folders as $directory){
        if(!is_dir($directory)){@mkdir($directory,0755,true);}
        @chmod($directory,0755);
        $unix->chown_func("$ClamUser", "$ClamUser",$directory);
    }

    if(is_file("/var/log/clamav/freshclam.log")){
        $unix->chown_func("$ClamUser", "$ClamUser","/var/log/clamav/freshclam.log");
    }
    build_progress(71, "{starting} {APP_FRESHCLAM}");

    build();
    build_progress(72, "{starting} {APP_FRESHCLAM}");
    if(is_file("/var/log/clamav/freshclam.log")){@unlink("/var/log/clamav/freshclam.log");}
    $cmd="$Masterbin --daemon  --config-file=/etc/clamav/freshclam.conf --pid=/var/run/clamav/freshclam.pid --user=$ClamUser --log=/var/log/clamav/freshclam.log --on-update-execute=/usr/share/artica-postfix/exec.freshclam.updated.php --on-error-execute=/usr/share/artica-postfix/exec.freshclam.failed.php >/dev/null 2>&1";

    freshclam_log("Starting ClamAV update pattern service");

    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
    $cmd_sh=$unix->sh_command($cmd);
    $unix->go_exec($cmd_sh);


    for($i=1;$i<5;$i++){
        build_progress(72+$i, "{starting} {APP_FRESHCLAM}");
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    build_progress(80, "{starting} {APP_FRESHCLAM}");
    $pid=PID_NUM();


    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
        return true;

    }else{
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
        build_progress(110, "{starting} {APP_FRESHCLAM} {failed}");
        return false;
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed..\n";}
        freshclam_log("{starting} {APP_FRESHCLAM} {failed}");
        build_progress(110, "{starting} {APP_FRESHCLAM} {failed}");
        return false;
    }

    return true;


}

function PID_NUM():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/clamav/freshclam.pid");
    if($unix->process_exists($pid)){return intval($pid);}
    $Masterbin=$unix->find_program("freshclam");
    return intval($unix->PIDOF_PATTERN("$Masterbin.*?--on-update-execute="));

}
function stop($aspid=false):bool{
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
        return false;
    }
    $pid=PID_NUM();

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
        return false;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
        return false;
    }
return true;
}






function build_progress_sigtool($text,$pourc):bool{
    $echotext=$text;

    if(is_numeric($text)){
        $old=$pourc;
        $pourc=$text;
        $text=$old;
    }
    $echotext=str_replace("{status}", "Status", $echotext);
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $echotext\n";
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"clamav.status.db.progress");
    return true;
}






function sigtool($prc=0){
    $unix=new unix();
    $sigtool=$unix->find_program("sigtool");
    if(strlen($sigtool)<5){
        if($prc>0){return;}
        build_progress_sigtool("{analyze} {failed} no sigtool",110);
        return;
    }
    $patterns=array();
    $baseDir="/var/lib/clamav";
    $patnz=$unix->DirFiles($baseDir,"\.(cvd|cld|hdb|ign2|ndb)$");
    foreach ($patnz as $path=>$none){$patterns[basename($path)]=true;}
    $q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");
    $results=$q->QUERY_SQL("SELECT * FROM pattern_status");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $dbname=$ligne["dbname"];
        echo "$dbname:$ID";
        if(!isset($patterns[$dbname])){
            echo " -- $index- $dbname:$ID no such db\n";
            $q->QUERY_SQL("DELETE FROM pattern_status WHERE ID=$ID");
            continue;
        }
        echo " -- OK\n";
    }


    $mXCount=count($patterns);
    $c=1;
    $q=new lib_sqlite("/home/artica/SQLITE/antivirus.db");

    foreach ($patterns as $pattern=>$none){
        if(!is_file("$baseDir/$pattern")){continue;}
        $STAT=@stat("$baseDir/$pattern");
        $c++;
        $results=array();
        $prc=$c/$mXCount;
        $prc=round($prc*100);
        if($prc<30){$prc=30;}
        if($prc>95){$prc=95;}
        $time=0;
        build_progress_sigtool("{analyze} $pattern",$prc);

        $patternmd5=md5_file("$baseDir/$pattern");
        echo "$baseDir/$pattern MD5: $patternmd5";
        $ligne=$q->mysqli_fetch_array("SELECT * FROM `pattern_status` WHERE dbname='$pattern'");

        if(!isset($ligne["md5file"])){
            $ligne["md5file"]="";
        }
        if(!isset($ligne["version"])){
            $ligne["version"]="";
        }
        if(!isset($ligne["dbname"])){
            $ligne["dbname"]="";
        }
        if(!isset($ligne["patterndate"])){
            $ligne["patterndate"]="";
        }

        $basemd5=$ligne["md5file"];
        $version=$ligne["version"];
        $dbname=$ligne["dbname"];
        $patterndate=$ligne["patterndate"];
        if($basemd5==$patternmd5){
            echo " --- SAME MD5: SKIP version='$version' dbname=$dbname patterndate=$patterndate\n";
            continue;
        }
        $ID=$ligne["ID"];
        if($ID==0){
            $q->QUERY_SQL("INSERT INTO `pattern_status` (`dbname`,`md5file`) VALUES ('$pattern','$patternmd5')");
            $ID=$q->last_id;
            if($ID==0){echo "Failed\n";return;}
        }

        echo "$sigtool --info=$baseDir/$pattern\n";
        exec("$sigtool --info=$baseDir/$pattern 2>&1",$results);
        $VERSION=false;
        $SIGNATURES=false;
        foreach ($results as $index=>$line){
            echo $line."\n";
            if(preg_match("#Build time:\s+(.+)#", $line,$re)){$time=strtotime($re[1]);$zdate=date("Y-m-d H:i:s",$time);$q->QUERY_SQL("UPDATE `pattern_status` SET `patterndate`='$zdate' WHERE ID='$ID'");continue;}
            if(preg_match("#Version:\s+([0-9]+)#",$line,$re)){$VERSION=true;$q->QUERY_SQL("UPDATE `pattern_status` SET `version`='$re[1]' WHERE ID='$ID'");continue;}
            if(preg_match("#Signatures:\s+([0-9]+)#",$line,$re)){$SIGNATURES=true;$q->QUERY_SQL("UPDATE `pattern_status` SET `signatures`='{$re[1]}' WHERE ID='$ID'");}

        }

        if($time==0){$zdate=null;$time=$STAT["mtime"];$zdate=date("Y-m-d H:i:s",$time);$q->QUERY_SQL("UPDATE `pattern_status` SET `patterndate`='$zdate' WHERE ID='$ID'");}
        if(!$VERSION){$q->QUERY_SQL("UPDATE `pattern_status` SET `version`='".date("YmdHi",$time)."' WHERE ID='$ID'");}
        if(!$SIGNATURES){$q->QUERY_SQL("UPDATE `pattern_status` SET `signatures`='".$unix->COUNT_LINES_OF_FILE("$baseDir/$pattern")."' WHERE ID='$ID'");}
        $q->QUERY_SQL("UPDATE `pattern_status` SET `md5file`='$patternmd5' WHERE ID='$ID'");

    }


    if($c==1){
        build_progress_sigtool("{analyze} {failed} No db",110);
        return;
    }

    build_progress_sigtool("{analyze} {success}",100);
}
