<?php
define("terminals","cpu,cpuacct,cpuset,memory,blkio");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["RELOAD_STATUS"]=false;
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/framework/class.status.hardware.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reload-status#",implode(" ",$argv))){$GLOBALS["RELOAD_STATUS"]=true;}

$GLOBALS["CLASS_UNIX"]=new unix();

if($argv[1]=='--build'){start();exit();}
if($argv[1]=='--start'){start();exit();}
if($argv[1]=='--restart'){restart();exit();}
if($argv[1]=='--stop'){stop();exit();}
if($argv[1]=='--reload'){reload();exit();}
if($argv[1]=="--ismounted"){ismounted();exit;}
if($argv[1]=="--stats"){buildstats();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}




function restart(){stop();start();}


function mounted_list(){
    $mounted=array();
    $f = explode("\n", @file_get_contents("/proc/mounts"));
    foreach ($f as $line) {
        $line = trim($line);
        if (!preg_match("#^cgroup\s+(.+?)\s+cgroup#", $line, $re)) {
            continue;
        }
        $mounted[] = $re[1];

    }
    return $mounted;
}

function stop(){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cgroups: DEBUG:: ". __FUNCTION__. " START\n";}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){echo "Starting......: ".date("H:i:s")." cgroups: Already pid $pid is running, aborting\n";return false;}
	@file_put_contents($pidfile, getmypid());

	echo "Starting......: ".date("H:i:s")." cgroups: Stopping hierarchy ".terminals."\n";

	$umount=$unix->find_program("umount");
	$rm=$unix->find_program("rm");

	$terms=explode(",",terminals);
	foreach ($terms as $terminal){

	    if(!is_terminal_mounted($terminal)){
            echo "Starting......: ".date("H:i:s")." cgroups: Stopping $terminal (already stopped)\n";
            continue;
        }
        echo "Starting......: ".date("H:i:s")." cgroups: Stopping $terminal...\n";
        shell_exec("$umount /cgroup/$terminal");
        if(is_terminal_mounted($terminal)){
            echo "Starting......: ".date("H:i:s")." cgroups: Stopping $terminal failed\n";
            continue;
        }


    }
    foreach ($terms as $terminal){
        if(!is_terminal_mounted($terminal)){continue;}
        echo "Starting......: ".date("H:i:s")." cgroups: Stopping $terminal (force mode)...\n";
        shell_exec("$umount -l /cgroup/$terminal");
        if(is_terminal_mounted($terminal)){
            echo "Starting......: ".date("H:i:s")." cgroups: Stopping $terminal failed\n";
            continue;
        }


    }

    shell_exec("$rm -rf /cgroup/*");
    if(is_first_herarchy_mounted()){
        echo "Starting......: ".date("H:i:s")." cgroups: Disconnect main hierarchy\n";
        shell_exec("$umount -l /cgroup");
    }else{
        echo "Starting......: ".date("H:i:s")." cgroups: main hierarchy already disconnected\n";
    }




    return true;
}

function reload(){
    start();
}



function is_first_herarchy_mounted(){

    $f=explode("\n",@file_get_contents("/proc/mounts"));
    foreach ($f as $line){
        $line=trim($line);
        if(!preg_match("#tmpfs \/cgroup tmpfs#",$line)){continue;}
        return true;
    }
    return false;
}
function is_terminal_mounted($terminal){

    $f=explode("\n",@file_get_contents("/proc/mounts"));
    foreach ($f as $line){
        $line=trim($line);
        if(!preg_match("#^$terminal \/cgroup\/$terminal cgroup#",$line)){continue;}
        return true;
    }
    return false;
}
function start(){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." cgroups: DEBUG:: ". __FUNCTION__. " START\n";}
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=@file_get_contents($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){echo "Starting......: ".date("H:i:s")." cgroups: Already pid $pid is running, aborting\n";return;}
	@file_put_contents($pidfile, getmypid());

    $cgroupsPHPCpuChoose=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPCpuChoose"));
    $cgroupsPHPDiskBandwidth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPDiskBandwidth"));
    $cgroupsPHPCpuShares=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPCpuShares"));
    $cgroupsPHPDiskIO=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsPHPDiskIO"));
    if($cgroupsPHPDiskBandwidth==0){$cgroupsPHPDiskBandwidth=10;}
    if($cgroupsPHPCpuShares==0){$cgroupsPHPCpuShares=256;}
    if($cgroupsPHPDiskIO==0){$cgroupsPHPDiskIO=450;}



    $umount=$GLOBALS["CLASS_UNIX"]->find_program("umount");
    $mount=$GLOBALS["CLASS_UNIX"]->find_program("mount");
    $echobin=$GLOBALS["CLASS_UNIX"]->find_program("echo");

    $mounted=mounted_list();
    if(count($mounted)>0) {
        foreach ($mounted as $path) {
            echo "Starting......: " . date("H:i:s") . " cgroups: disconnect $path\n";
            shell_exec("$umount -l $path");
        }

    }
    if(!is_first_herarchy_mounted()) {
        echo "Starting......: " . date("H:i:s") . " cgroups: Mount first hierarchy...\n";
        shell_exec("$mount -t tmpfs tmpfs /cgroup -o nosuid,nodev,noexec,relatime,mode=755");
    }

    $opts=explode(",",terminals);


    foreach ($opts as $terminal){
        $path="/cgroup/$terminal";
        if(is_terminal_mounted($terminal)){
            echo "Starting......: ".date("H:i:s")." cgroups: $terminal [OK]\n";
            continue;
        }
        echo "Starting......: ".date("H:i:s")." cgroups: $terminal [mount]\n";
        @mkdir($path,0755,true);
        shell_exec("$mount -t cgroup $terminal -o$terminal $path");
        if(!is_terminal_mounted($terminal)){
            echo "Starting......: ".date("H:i:s")." cgroups: $terminal [FAILED]\n";
        }
    }

    echo "Starting......: ".date("H:i:s")." cgroups: checking group php subsystem\n";
    foreach ($opts as $terminal){
        $path="/cgroup/$terminal/php";
        if(!is_dir($path)){
            echo "Starting......: ".date("H:i:s")." cgroups: Creating php under $terminal\n";
            @mkdir($path,0755);
        }

    }
    echo "Starting......: ".date("H:i:s")." cgroups: configuring group php subsystem\n";


    $blkio=$cgroupsPHPDiskBandwidth*1024;
    $blkio=$blkio*1024;

    shell_exec("$echobin \"$cgroupsPHPCpuShares\" >/cgroup/cpu/php/cpu.shares");
    shell_exec("$echobin \"10000\" >/cgroup/cpu/php/cpu.cfs_quota_us");
    shell_exec("$echobin \"100000\" >/cgroup/cpu/php/cpu.cfs_period_us");
    shell_exec("$echobin \"$cgroupsPHPCpuChoose\" >/cgroup/cpuset/php/cpuset.cpus");
    shell_exec("$echobin \"0\" >/cgroup/cpuset/php/cpuset.mems");
    shell_exec("$echobin \"$cgroupsPHPDiskIO\" >/cgroup/blkio/php/blkio.weight");
    shell_exec("$echobin \"8:0 $blkio\" >/cgroup/blkio/php/blkio.throttle.write_bps_device");
    shell_exec("$echobin \"8:0 $blkio\" >/cgroup/blkio/php/blkio.throttle.read_bps_device");
    echo "Starting......: ".date("H:i:s")." cgroups: configuring group php subsystem OK\n";
}

function build_progress_install($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"cgroups.install.progress");
}

function install():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $cgroupsEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cgroupsEnabled"));
    if($cgroupsEnabled==1){
        echo "Already installed...\n";
        build_progress_install("{installing} {success} (Already installed)",100);
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("cgroupsEnabled",1);
    $unix->remove_service("/etc/init.d/cgred");
    build_progress_install("{installing}",10);
    $unix->create_service("cgconfig",basename(__FILE__));
    $unix->create_monit("APP_CGROUPS","dir:/cgroup/blkio/php","cgconfig");

    build_progress_install("{installing}",20);
    start();
    if(is_file("/etc/init.d/munin-node")){shell_exec("$php /usr/share/artica-postfix/exec.munin.php --cron");}
    return build_progress_install("{installing} {success}",100);

}

function uninstall():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("cgroupsEnabled",0);
    stop();
    build_progress_install("{uninstall}",10);
    $unix->remove_service("/etc/init.d/cgconfig");
    build_progress_install("{uninstall}",20);
    $unix->remove_service("/etc/init.d/cgred");
    $unix->remove_monit("APP_CGROUPS");
    build_progress_install("{uninstall}",50);
    if(is_file("/etc/init.d/munin-node")){shell_exec("$php /usr/share/artica-postfix/exec.munin.php --cron");}
    @rmdir("/cgroup");
    return build_progress_install("{uninstall} {success}",100);
}





