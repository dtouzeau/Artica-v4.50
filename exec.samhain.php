<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="ARP Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');

include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}



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
    stop(true);
    sleep(1);
    start(true);

}
function build_progress($pourc,$text){
    $cachefile="/usr/share/artica-postfix/ressources/logs/samhain.progress";

    if(is_numeric($text)){
        $array["POURC"]=$text;
        $array["TEXT"]=$pourc;
        echo "{$pourc}% $text\n";
        @file_put_contents($cachefile, serialize($array));
        @chmod($cachefile,0755);
        return;

    }
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "{$pourc}% $text\n";
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function out($text){

    echo "Starting......: ".date("H:i:s")." [INIT]: HIDS, $text\n";

}


function uninstall(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSamhain",0);
    build_progress(10,"{removing}");

    $rm=$unix->find_program("rm");
    if(is_dir("/var/lib/samhain")) {
        shell_exec("$rm -rf /var/lib/samhain");
    }
    if(is_dir("/etc/samhain")) {
        shell_exec("$rm -rf /etc/samhain");
    }



}

function install(){
    $unix=new unix();
    $Masterbin=$unix->find_program("samhain");
    build_progress(10,"{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSamhain",1);
    if(!is_dir("/var/lib/samhain")){@mkdir("/var/lib/samhain",0755,true);}
    @chown("/var/lib/samhain","root");
    @chgrp("/var/lib/samhain","root");

    build();

    if(!is_file("/var/lib/samhain/samhain_file")){
        build_progress(10,"{installing}");
        out("Starting, initialize the database");
        build_progress(15,"{building_collection}");
        system("$Masterbin -t init >/dev/null 2>&1");
    }


}


function start($aspid=false){
    $unix=new unix();
    $sock=new sockets();
    $Masterbin=$unix->find_program("samhain");

    if(!is_file($Masterbin)){out("samhain not installed"); return false;}

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            out("Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!is_dir("/var/lib/samhain")){@mkdir("/var/lib/samhain",0755,true);}
    @chown("/var/lib/samhain","root");
    @chgrp("/var/lib/samhain","root");

    if(!is_file("/var/lib/samhain/samhain_file")){
        out("Starting, initialize the database");
        system("$Masterbin -t init");
    }





    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
        return;
    }
    $EnableArpDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArpDaemon"));
    $ArpdKernelLevel=$sock->GET_INFO("ArpdKernelLevel");

    if(!is_numeric($ArpdKernelLevel)){$ArpdKernelLevel=0;}


    if($EnableArpDaemon==0){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableArpDaemon)\n";}
        return;
    }

    $php5=$unix->LOCATE_PHP5_BIN();
    $sysctl=$unix->find_program("sysctl");
    $echo=$unix->find_program("echo");
    $nohup=$unix->find_program("nohup");

    if (intval($ArpdKernelLevel)>0){$ArpdKernelLevel_string=" -a $ArpdKernelLevel";}
    $Interfaces=$unix->NETWORK_ALL_INTERFACES();
    $nic=new system_nic();
    foreach ($Interfaces as $Interface=>$ligne){
        if($Interface=="lo"){continue;}
        if($Interface=="tun0"){continue;}
        if($ligne["IPADDR"]=="0.0.0.0"){continue;}
        $Interface=$nic->NicToOther($Interface);
        $TRA[$Interface]=$Interface;
    }
    foreach ($TRA as $Interface=>$ligne){
    $TR[]=$Interface; }
    @mkdir('/var/lib/arpd',0755,true);

    $f[]="$Masterbin -b /var/lib/arpd/arpd.db";
    $f[]=$ArpdKernelLevel_string;

    if(count($TR)>0){
        $f[]="-k ".@implode($TR," ");
    }


    $cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

    shell_exec($cmd);




    for($i=1;$i<5;$i++){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

    }else{
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
    }


}

function stop($aspid=false){
    $unix=new unix();
    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
            return;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();


    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
        return;
    }
    $pid=PID_NUM();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $kill=$unix->find_program("kill");




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
        return;
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
        return;
    }

}

function reload(){
    $unix=new unix();
    build();
    $kill=$unix->find_program("kill");
    $pid=PID_NUM();
    if($unix->process_exists($pid)) {
        out("Reloading PID $pid");
        shell_exec("$kill -HUP $pid");
        return true;
    }

    out("Reloading failed, not running");

}

function PID_NUM(){

    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/samhain/samhain.pid");
    if($unix->process_exists($pid)){return $pid;}
    $Masterbin=$unix->find_program("samhain");
    return $unix->PIDOF($Masterbin);

}


function build(){

    $conf[]="[Misc]";
    $conf[]="";
    $conf[]="# RedefReadOnly = (no default)";
    $conf[]="# RedefAttributes=(no default)";
    $conf[]="# RedefLogFiles=(no default)";
    $conf[]="# RedefGrowingLogFiles=(no default)";
    $conf[]="# RedefIgnoreAll=(no default)";
    $conf[]="# RedefIgnoreNone=(no default)";
    $conf[]="";
    $conf[]="# RedefUser0=(no default)";
    $conf[]="# RedefUser1=(no default)";
    $conf[]="";
    $conf[]="#";
    $conf[]="# --------- / --------------";
    $conf[]="#";
    $conf[]="";
    $conf[]="[ReadOnly]";
    $conf[]="dir = 0/";
    $conf[]="";
    $conf[]="[Attributes]";
    $conf[]="file = /tmp";
    $conf[]="file = /dev";
    $conf[]="file = /media";
    $conf[]="file = /proc";
    $conf[]="file = /sys";
    $conf[]="";
    $conf[]="#";
    $conf[]="# --------- /etc -----------";
    $conf[]="#";
    $conf[]="";
    $conf[]="[ReadOnly]";
    $conf[]="dir = 99/etc";
    $conf[]="";
    $conf[]="[Attributes]";
    $conf[]="file = /etc/mtab";
    $conf[]="file = /etc/adjtime";
    $conf[]="file = /etc/motd";
    $conf[]="file = /etc/lvm/.cache";
    $conf[]="file = /etc/cups/certs";
    $conf[]="file = /etc/cups/certs/0";
    $conf[]="file = /etc/fstab";
    $conf[]="file = /etc/sysconfig/hwconf";
    $conf[]="file = /etc";
    $conf[]="";
    $conf[]="[ReadOnly]";
    $conf[]="dir = 99/boot";
    $conf[]="";
    $conf[]="[ReadOnly]";
    $conf[]="dir = 99/bin";
    $conf[]="dir = 99/sbin";
    $conf[]="dir = 99/usr/share/artica-postfix";
    $conf[]="";
    $conf[]="[ReadOnly]";
    $conf[]="dir = 99/lib";
    $conf[]="";
    $conf[]="[Attributes]";
    $conf[]="dir = 99/dev";
    $conf[]="";
    $conf[]="[IgnoreAll]";
    $conf[]="dir = -1/dev/pts";
    $conf[]="file = /dev/ppp";
    $conf[]="";
    $conf[]="[ReadOnly]";
    $conf[]="dir = 99/usr";
    $conf[]="";
    $conf[]="[ReadOnly]";
    $conf[]="dir = 99/var";
    $conf[]="";
    $conf[]="[IgnoreAll]";
    $conf[]="dir = -1/var/cache";
    $conf[]="dir = -1/var/backups";
    $conf[]="dir = -1/var/games";
    $conf[]="dir = -1/var/gdm";
    $conf[]="dir = -1/var/lock";
    $conf[]="dir = -1/var/mail";
    $conf[]="dir = -1/var/run";
    $conf[]="dir = -1/var/spool";
    $conf[]="dir = -1/var/tmp";
    $conf[]="dir = -1/var/lib/texmf";
    $conf[]="dir = -1/var/lib/scrollkeeper";
    $conf[]="dir = -1/var/lib/ntp/proc";
    $conf[]="dir = -1/usr/share/artica-postfix/ressources/logs";
    $conf[]="dir = /etc/artica-postfix/pids";
    $conf[]="dir = -1/usr/local/share/artica";
    $conf[]="dir = -1/var/lib/clamav";
    $conf[]="dir = -1/var/lib/vnstat";
    $conf[]="dir = -1/var/lib/mysql/mysql";
    $conf[]="dir = -1/var/lib/ldap";
    $conf[]="dir = -1/var/log/squid";
    $conf[]="file= /var/log/artica-status.log";
    $conf[]="";
    $conf[]="[Attributes]";
    $conf[]="";
    $conf[]="dir = /var/lib/nfs";
    $conf[]="dir = /var/lib/pcmcia";
    $conf[]="file = /var/lib/rpm/__db.00?";
    $conf[]="file = /var/lib/acpi-support/vbestate";
    $conf[]="file = /var/lib/alsa/asound.state";
    $conf[]="file = /var/lib/apt/lists/lock";
    $conf[]="file = /var/lib/apt/lists/partial";
    $conf[]="file = /var/lib/cups/certs";
    $conf[]="file = /var/lib/cups/certs/0";
    $conf[]="file = /var/lib/dpkg/lock";
    $conf[]="file = /var/lib/gdm";
    $conf[]="file = /var/lib/gdm/.cookie";
    $conf[]="file = /var/lib/gdm/.gdmfifo";
    $conf[]="file = /var/lib/gdm/:0.Xauth";
    $conf[]="file = /var/lib/gdm/:0.Xservers";
    $conf[]="file = /var/lib/logrotate/status";
    $conf[]="file = /var/lib/mysql";
    $conf[]="file = /var/lib/mysql/ib_logfile0";
    $conf[]="file = /var/lib/mysql/ibdata1";
    $conf[]="file = /var/lib/slocate";
    $conf[]="file = /var/lib/slocate/slocate.db";
    $conf[]="file = /var/lib/slocate/slocate.db.tmp";
    $conf[]="file = /var/lib/urandom";
    $conf[]="file = /var/lib/urandom/random-seed";
    $conf[]="file = /var/lib/random-seed";
    $conf[]="file = /var/lib/xkb";
    $conf[]="";
    $conf[]="";
    $conf[]="[GrowingLogFiles]";
    $conf[]="dir = 99/var/log";
    $conf[]="";
    $conf[]="[Attributes]";
    $conf[]="file = /var/log/*.[0-9].gz";
    $conf[]="file = /var/log/*.[0-9].log";
    $conf[]="file = /var/log/*.[0-9]";
    $conf[]="file = /var/log/*.old";
    $conf[]="file = /var/log/*/*.[0-9].gz";
    $conf[]="file = /var/log/*/*.[0-9][0-9].gz";
    $conf[]="file = /var/log/*/*.log.[0-9]";
    $conf[]="";
    $conf[]="[Misc]";
    $conf[]="";
    $conf[]="IgnoreAdded = /var/log/.*\.[0-9]+\$";
    $conf[]="IgnoreAdded = /var/log/.*\.[0-9]+\.gz\$";
    $conf[]="IgnoreAdded = /var/log/.*\.[0-9]+\.log\$";
    $conf[]="IgnoreAdded = /var/log/[[:alnum:]]+/.*\.[0-9]+\$";
    $conf[]="IgnoreAdded = /var/log/[[:alnum:]]+/.*\.[0-9]+\.gz\$";
    $conf[]="IgnoreAdded = /var/log/[[:alnum:]]+/.*\.[0-9]+\.log\$";
    $conf[]="IgnoreAdded = /var/lib/slocate/slocate.db.tmp";
    $conf[]="IgnoreMissing = /var/lib/slocate/slocate.db.tmp";
    $conf[]="";
    $conf[]="[IgnoreNone]";
    $conf[]="";
    $conf[]="[Prelink]";
    $conf[]="";
    $conf[]="";
    $conf[]="[User0]";
    $conf[]="[User1]";
    $conf[]="";
    $conf[]="";
    $conf[]="";
    $conf[]="[EventSeverity]";
    $conf[]="# SeverityReadOnly=crit";
    $conf[]="# SeverityLogFiles=crit";
    $conf[]="# SeverityGrowingLogs=crit";
    $conf[]="# SeverityIgnoreNone=crit";
    $conf[]="# SeverityAttributes=crit";
    $conf[]="# SeverityUser0=crit";
    $conf[]="# SeverityUser1=crit";
    $conf[]="# SeverityIgnoreAll=crit";
    $conf[]="# SeverityFiles=crit";
    $conf[]="# SeverityDirs=crit";
    $conf[]="# SeverityNames=crit";
    $conf[]="";
    $conf[]="[Log]";
    $conf[]="## MailSeverity=*";
    $conf[]="## MailSeverity=!warn";
    $conf[]="## MailSeverity==crit";
    $conf[]="# MailSeverity=none";
    $conf[]="# PrintSeverity=info";
    $conf[]="# LogSeverity=mark";
    $conf[]="# SyslogSeverity=none";
    $conf[]="# ExportSeverity=none";
    $conf[]="# ExternalSeverity = none";
    $conf[]="# DatabaseSeverity = none";
    $conf[]="# PreludeSeverity = crit";
    $conf[]="";
    $conf[]="# [SuidCheck]";
    $conf[]="# SuidCheckActive = yes";
    $conf[]="# SuidCheckInterval = 7200";
    $conf[]="# SuidCheckSchedule = NULL";
    $conf[]="# SuidCheckExclude = NULL";
    $conf[]="# SuidCheckFps = 0";
    $conf[]="# SuidCheckYield = no";
    $conf[]="# SeveritySuidCheck = crit";
    $conf[]="# SuidCheckQuarantineFiles = yes";
    $conf[]="";
    $conf[]="## Method for Quarantining files:";
    $conf[]="#  0 - Delete or truncate the file.";
    $conf[]="#  1 - Remove SUID/SGID permissions from file.";
    $conf[]="#  2 - Move SUID/SGID file to quarantine dir.";
    $conf[]="#";
    $conf[]="# SuidCheckQuarantineMethod = 0";
    $conf[]="# SuidCheckQuarantineDelete = yes";
    $conf[]="";
    $conf[]="# [Utmp]";
    $conf[]="# LoginCheckActive = True";
    $conf[]="# SeverityLogin=info";
    $conf[]="# SeverityLoginMulti=warn";
    $conf[]="# SeverityLogout=info";
    $conf[]="# LoginCheckInterval = 300";
    $conf[]="";
    $conf[]="";
    $conf[]="# [Database]";
    $conf[]="# SetDBName = samhain";
    $conf[]="# SetDBTable = log";
    $conf[]="# SetDBUser = samhain";
    $conf[]="# SetDBPassword = (default: none)";
    $conf[]="# SetDBHost = localhost";
    $conf[]="# SetDBServerTstamp = True";
    $conf[]="# UsePersistent = True";
    $conf[]="";
    $conf[]="# [External]";
    $conf[]="# OpenCommand = (no default)";
    $conf[]="# SetType = log";
    $conf[]="# SetCommandLine = (no default)";
    $conf[]="# SetEnviron = TZ=(your timezone)";
    $conf[]="# SetChecksum = (no default)";
    $conf[]="# SetCredentials = (default: samhain process uid)";
    $conf[]="# SetFilterNot = (none)";
    $conf[]="# SetFilterAnd = (none)";
    $conf[]="# SetFilterOr = (none)";
    $conf[]="# SetDeadtime = 0";
    $conf[]="# SetDefault = no";
    $conf[]="";
    $conf[]="[Inotify]";
    $conf[]="InotifyActive = yes";
    $conf[]="InotifyWatches = 1048576";
    $conf[]="";
    $conf[]="[Misc]";
    $conf[]="Daemon = yes";
    $conf[]="ChecksumTest=check";
    $conf[]="SetNiceLevel = 15";
    $conf[]="# SetIOLimit = 0";
    $conf[]="# VersionString = NULL";
    $conf[]="SetLoopTime = 600";
    $conf[]="SetFileCheckTime = 315360000";
    $conf[]="# FileCheckScheduleOne = NULL";
    $conf[]="# FileCheckScheduleTwo = NULL";
    $conf[]="# ReportOnlyOnce = True";
    $conf[]="# ReportFullDetail = False";
    $conf[]="# UseLocalTime = No";
    $conf[]="# SetConsole = /dev/console";
    $conf[]="# MessageQueueActive = False";
    $conf[]="# SetReverseLookup = True";
    $conf[]="# SetMailTime = 86400";
    $conf[]="# SetMailNum = 10";
    $conf[]="# SetMailAddress=root@localhost";
    $conf[]="# SetMailRelay = NULL";
    $conf[]="# MailSubject = NULL";
    $conf[]="# SetPrelinkPath = /usr/sbin/prelink";
    $conf[]="# SetPrelinkChecksum = (no default)";
    $conf[]="# SamhainPath = (no default)";
    $conf[]="# SetLogServer = (default: compiled-in)";
    $conf[]="# SetTimeServer = (default: compiled-in)";
    $conf[]="# TrustedUser = (no default; this adds to the compiled-in list)";
    $conf[]="# SetDatabasePath = (default: compiled-in)";
    $conf[]="# SetLogfilePath = (default: compiled-in)";
    $conf[]="# SetLockfilePath = (default: compiled-in)";
    $conf[]="##";
    $conf[]="## %S severity";
    $conf[]="## %T timestamp";
    $conf[]="## %C class";
    $conf[]="##";
    $conf[]="## %F source file";
    $conf[]="## %L source line";
    $conf[]="#";
    $conf[]="# MessageHeader=\"%S %T \"";
    $conf[]="# HideSetup = False";
    $conf[]="SyslogFacility=LOG_LOCAL2";
    $conf[]="# MACType = HMAC-TIGER";
    $conf[]="# PreludeProfile = samhain";
    $conf[]="# PreludeMapToInfo =";
    $conf[]="# PreludeMapToLow = debug info";
    $conf[]="# PreludeMapToMedium = notice warn err";
    $conf[]="# PreludeMapToHigh = crit alert";
    $conf[]="";
    $conf[]="[EOF]";
    $conf[]="";

    if(!is_dir("/etc/samhain")){@mkdir("/etc/samhain",0755,true);}
    @file_put_contents("/etc/samhain/samhainrc",@implode("\n",$conf));
    out("/etc/samhain/samhainrc: done");


}

?>