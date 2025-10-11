<?php
if(is_file("/etc/artica-postfix/FROM_ISO")){if(is_file("/etc/init.d/artica-cd")){print "Starting......: ".date("H:i:s")." artica-". basename(__FILE__)." Waiting Artica-CD to finish\n";exit();}}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["SERVICE_NAME"]="Categories-cache";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build();exit();}
if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;remove();exit();}
if($argv[1]=="--version"){$GLOBALS["OUTPUT"]=true;echo categories_cache_version();exit();}
if($argv[1]=="--remove-db"){ remove_database_progress();exit();}
if($argv[1]=="--parse"){ parse_temp_directory();exit();}


function restart($nopid=false){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(!$nopid){
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
            build_progress("{restarting_service} {failed}",110);
            return false;
        }
    }
    @file_put_contents($pidfile, getmypid());
    build_progress("{stopping_service}",50);
    if(!stop(true)){
        build_progress("{stopping_service} {failed}",110);
        return false;
    }
    build_progress("{building_parameters}",80);
    build();
    build_progress("{starting_service}",80);
    if(!start(true)){
        build_progress("{starting_service} {failed}",110);
        return false;
    }
    build_progress("{success_restarting_service}",100);
    return true;

}

function build_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"categories-cache.progress");
}

function reload($nopid=false){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(!$nopid){
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
            return;
        }
    }
    @file_put_contents($pidfile, getmypid());


    build();
    replicate_binary();

    $pid=categories_cache_pid();
    $kill=$unix->find_program("kill");
    if($unix->process_exists($pid)){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Reload........: [INIT]: {$GLOBALS["SERVICE_NAME"]} Service running since {$time}Mn...\n";}
        unix_system_HUP($pid);
        return;
    }
    start(true);
}

function replicate_binary(){
    $unix=new unix();
    $masterbin=$unix->find_program("redis-server");
    if(!is_file($masterbin)){
        $unix->DEBIAN_INSTALL_PACKAGE("redis-server");
        $masterbin=$unix->find_program("redis-server");
    }

    if(is_file("/usr/bin/categories-cache")){
        $categories_cache_md=md5_file("/usr/bin/categories-cache");
    }
    $redis_bin=md5_file($masterbin);
    if($redis_bin<>$categories_cache_md){
        @unlink("/usr/bin/categories-cache");
        @copy($masterbin,"/usr/bin/categories-cache");
    }

    @chmod("/usr/bin/categories-cache",0755);


}

function build(){
    $unix=new unix();
    $sysctl=$unix->find_program("sysctl");
    shell_exec("$sysctl \"vm.overcommit_memory=1\" 2>&1");
    $RedisBindIP="127.0.0.1";
    $CategoriesCacheListenIP=null;
    categories_cache_install();
    $CategoriesCacheMaxMemory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheMaxMemory"));
    if($CategoriesCacheMaxMemory==0){$CategoriesCacheMaxMemory=500;}

    $CategoriesCacheListenNet=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheListenNet"));
    $CategoriesCacheListenAddr=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheListenAddr"));
    $CategoriesCacheListenPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CategoriesCacheListenPort"));
    if($CategoriesCacheListenPort==0){$CategoriesCacheListenPort=2214;}

    if($CategoriesCacheListenNet==1){
        $CategoriesCacheListenIP=$unix->NETWORK_IFNAME_TO_IP($CategoriesCacheListenAddr);
        if($CategoriesCacheListenIP==null){$CategoriesCacheListenNet=0;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CategoriesCacheListenIP",$CategoriesCacheListenIP);
    }

    if($CategoriesCacheListenNet==1){
        $RedisBindIP=$CategoriesCacheListenIP;
    }

    $f[]="daemonize yes";
    $f[]="pidfile /var/run/categories-cache/categories-cache.pid";
    $f[]="port $CategoriesCacheListenPort";
    $f[]="bind $RedisBindIP";
    $f[]="unixsocket /var/run/categories-cache/categories-cache.sock";
    $f[]="unixsocketperm 777";
    $f[]="timeout 0";
    $f[]="loglevel notice";
    $f[]="logfile /var/log/categories-cache/categories-cache.log";
    $f[]="syslog-enabled yes";
    $f[]="syslog-ident ksrn";
    $f[]="syslog-facility local5";
    $f[]="databases 16";
    $f[]="save 900 1";
    $f[]="save 300 10";
    $f[]="save 60 10000";
    $f[]="rdbcompression yes";
    $f[]="dbfilename dump.rdb";
    $f[]="dir /home/artica/categories-cache";
    $f[]="slave-serve-stale-data yes";
    $f[]="# maxclients 128";
    $f[]="maxmemory {$CategoriesCacheMaxMemory}mb";
    $f[]="maxmemory-policy allkeys-lru";
    $f[]="# maxmemory-samples 3";
    $f[]="appendonly no";
    $f[]="appendfsync everysec";
    $f[]="no-appendfsync-on-rewrite no";
    $f[]="auto-aof-rewrite-percentage 100";
    $f[]="auto-aof-rewrite-min-size 64mb";
    $f[]="slowlog-log-slower-than 10000";
    $f[]="slowlog-max-len 128";
    $f[]="list-max-ziplist-entries 512";
    $f[]="list-max-ziplist-value 64";
    $f[]="set-max-intset-entries 512";
    $f[]="zset-max-ziplist-entries 128";
    $f[]="zset-max-ziplist-value 64";
    $f[]="activerehashing yes";
    CheckFilesAndSecurity();

    if(!is_dir("/etc/categories-cache")){@mkdir("/etc/categories-cache",0755,true);}
    @file_put_contents("/etc/categories-cache/categories-cache.conf", @implode("\n", $f));
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} categories-cache.conf done\n";}


}

function log_syslog($text){
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $text\n";
    if(function_exists("openlog")){openlog("ksrn", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog(LOG_INFO, "[CATEGORIES_CACHE]: $text");}
    if(function_exists("closelog")){closelog();}
}

function parse_temp_directory(){

    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
        return false;
    }

    if(is_file($pidfile)){@unlink($pidfile);}
    @file_put_contents($pidfile,$pid);

    $socket="/var/run/categories-cache/categories-cache.sock";
    $cat="/usr/bin/cat";
    $redis="/usr/bin/redis-cli";
    $maindir="/home/artica/theshieldsdb-tmpcat";
    if (!$handle = opendir($maindir)) {return false;}

    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$maindir/$filename";
        if(!is_file($targetFile)){continue;}
        if(preg_match("#^([0-9])\.txt#",$filename,$re)){continue;}
        $category_id=$re[1];
        $cmd="$cat $targetFile | $redis -s $socket --pipe 2>&1";
        $results=array();
        exec($cmd,$results);
        $IMPORT=0;
        foreach ($results as $line){
            $line=trim($line);
            if($line==null){continue;}

            if(preg_match("#All data transferred#",$line)){
                echo $targetFile. " Success\n";
                $IMPORT++;
                @unlink($targetFile);
                continue;
            }

            if(preg_match("#Could not connect to Redis#",$line)){
                squid_admin_mysql(0,"Error importing $targetFile categories-cache down",
                    "did you have enough memory on your server ?",__FILE__,__LINE__);
                shell_exec("/etc/init.d/categories-cache start");
                return false;
            }

            if(preg_match("#Redis is configured to save RDB snapshots, but it is currently not able#",$line)){
                log_syslog("categories-cache is currently dump data, aborting importing category $category_id");
                return false;
            }
            echo $line."\n";
        }
    }
    if($IMPORT>0){
        squid_admin_mysql(2,"{success} {importing} $IMPORT {databases}");
    }
    return true;

}

function start($nopid=false){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    if(!$nopid){
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already Artica task running PID $pid since {$time}mn\n";}
            return false;
        }
    }



    $pid=categories_cache_pid();
    if($unix->process_exists($pid)){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already running since {$time}Mn...\n";}
        return true;
    }

    replicate_binary();
    $masterbin="/usr/bin/categories-cache";

    CheckFilesAndSecurity();

    $pids=$unix->PIDOF_BY_PORT(2214);
    log_syslog(count($pids)." processes running 2214");
    foreach ($pids as $pid=>$none){
        log_syslog("killing $pid listen 2214");
        unix_system_kill_force($pid);
    }


    if(!is_dir("/home/artica/theshieldsdb-tmpcat")){
        @mkdir("/home/artica/theshieldsdb-tmpcat",0755,true);
    }
    $version=categories_cache_version();
    log_syslog("Starting service v$version");
    $cmd="$masterbin /etc/categories-cache/categories-cache.conf";
    if(is_file("/var/log/categories-cache/categories-cache.log")){@unlink("/var/log/categories-cache/categories-cache.log");}


    shell_exec($cmd);

    $c=1;
    for($i=0;$i<10;$i++){
        sleep(1);
        log_syslog("Starting service waiting $c");
        $pid=categories_cache_pid();
        if($unix->process_exists($pid)){
            log_syslog("Success PID $pid");
            break;
        }
        $c++;
    }

    $pid=categories_cache_pid();
    if(!$unix->process_exists($pid)){

        $f=explode("\n",@file_get_contents("/var/log/categories-cache/categories-cache.log"));
        foreach ($f as $line){
            $line=trim($line);
            if(!isset($GLOBALS["ALLFIX"])) {
                if (preg_match("#Fatal error loading the DB#", $line, $re)) {
                    log_syslog("Fixing Error ($line)");
                    @unlink("/home/artica/categories-cache/dump.rdb");
                    $GLOBALS["ALLFIX"] = true;
                    return start(true);
                }
            }
            log_syslog($line);

        }
        log_syslog("Failed");
        log_syslog("$cmd");
        return false;
    }
    squid_admin_mysql(2,"{APP_CATEGORIES_CACHE} {started}");
    return true;

}


function CheckFilesAndSecurity(){
    $unix=new unix();
    $unix->CreateUnixUser("redis","redis");
    $f[]="/var/run/categories-cache";
    $f[]="/var/log/categories-cache";
    $f[]="/home/artica/categories-cache";
    $f[]="/etc/categories-cache";
    $f[]="/home/artica/theshieldsdb-tmpcat";

    foreach ($f as $val){
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} checking \"$val\"\n";}
        if(!is_dir($val)){@mkdir($val,0755,true);}
        $unix->chown_func("redis","redis","$val/*");
    }

}

function stop(){

    $unix=new unix();
    $pid=categories_cache_pid();

    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Already stopped...\n";}
        return true;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
    $socket="/var/run/categories-cache/categories-cache.sock";
    $redis_cli=$unix->find_program("redis-cli");
    shell_exec("$redis_cli -s $socket shutdown");

    for($i=0;$i<8;$i++){
        $pid=categories_cache_pid();
        if(!$unix->process_exists($pid)){break;}
        unix_system_kill($pid);
        if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $pid=categories_cache_pid();
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
        return true;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}

    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=categories_cache_pid();
        if(!$unix->process_exists($pid)){break;}
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";
        unix_system_kill_force($pid);
        sleep(1);
    }

    $pids=$unix->PIDOF_BY_PORT(6379);
    foreach ($pids as $pid=>$none){
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} killing $pid listen 6379\n";
        unix_system_kill_force($pid);
    }

    $pid=categories_cache_pid();
    if(!$unix->process_exists($pid)) {
        if ($GLOBALS["OUTPUT"]) {
            echo "Stopping......: " . date("H:i:s") . " [INIT]: {$GLOBALS["SERVICE_NAME"]} success stopped...\n";
        }
        return true;
    }
    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
    return false;

}

function categories_cache_version(){
    $unix=new unix();
    if(isset($GLOBALS["categories_cache_version"])){return $GLOBALS["categories_cache_version"];}
    $masterbin=$unix->find_program("categories-cache");
    if(!is_file($masterbin)){return "0.0.0";}
    exec("$masterbin --version 2>&1",$results);
    foreach ($results as $val){
        if($GLOBALS["VERBOSE"]){echo "Checks $val\n";}
        if(preg_match("#Redis server version\s+(.+)#", $val,$re)){
            $GLOBALS["categories_cache_version"]=trim($re[1]);
            return $GLOBALS["categories_cache_version"];
        }

        if(preg_match("#Redis server v=([0-9\.]+)\s+#",$val,$re)){
            $GLOBALS["categories_cache_version"]=trim($re[1]);
            return $GLOBALS["categories_cache_version"];
        }
    }
}

function categories_cache_pid(){
    $unix=new unix();
    $pid=$unix->get_pid_from_file('/var/run/categories-cache/categories-cache.pid');
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/bin/categories-cache");
}

function remove_database_progress(){
    build_progress("{REMOVE_DATABASE}",10);
    remove_database();
    for($i=20;$i<25;$i++){
        build_progress("{REMOVE_DATABASE}",$i);
        sleep(1);
    }
    $BaseWorkDir="/home/artica/categories-cache";
    $handle = opendir($BaseWorkDir);
    if(!$handle) {
        build_progress(110,"$BaseWorkDir {no_such_dir} - handle");
        return false;
    }
    while (false !== ($filename = readdir($handle))) {
        if ($filename == ".") {continue;}
        if ($filename == "..") {continue;}
        $targetFile = "$BaseWorkDir/$filename";
        if (is_dir($targetFile)) {continue;}
        @unlink($targetFile);
        echo "Remove $targetFile\n";
        build_progress("{cleaning_data}",30);


    }
    build_progress("{restarting_service}",40);
    stop();
    build_progress("{restarting_service}",50);
    start();
    build_progress("{REMOVE_DATABASE} {done}",100);
    return true;
}

function remove_database(){
    $unix=new unix();
    $socket="/var/run/categories-cache/categories-cache.sock";
    $redis_cli=$unix->find_program("redis-cli");
    echo "$redis_cli -s $socket FLUSHALL\n";
    shell_exec("$redis_cli -s $socket FLUSHALL");

}

function install(){
    $unix=new unix();

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableCategoriesCache",1);

    build_progress("{installing}",20);
    categories_cache_install();

    build_progress("{starting_service}",50);
    if(!start(true)){
        build_progress("{installing} {failed}",110);
        return false;
    }

    $unix->Popuplate_cron_make("categories-cache","*/3 * * * *",basename(__FILE__)." --parse");
    UNIX_RESTART_CRON();

    build_progress("{installing}",60);
    categories_cache_monit();
    build_progress("{installing}",65);
    build_progress("{restarting} {APP_ARTICA_STATUS}",80);
    system("/etc/init.d/artica-status restart --force");
    build_progress("{restarting} {APP_CATEGORIES_CACHE}",90);
    system("/etc/init.d/categories-cache restart");
    if(is_file("/etc/init.d/squid")){
        build_progress("{reloading_proxy_service}",90);
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }
    build_progress("{success}",100);
    return true;
}
function remove(){
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableCategoriesCache",0);
    build_progress("{removing}",50);
    @unlink("/etc/monit/conf.d/APP_CATEGORIES_CACHE.monitrc");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    build_progress("{removing}",60);
    remove_database();
    if(is_file("/etc/cron.d/categories-cache")){
        @unlink("/etc/cron.d/categories-cache");
        build_progress("{removing}",70);
        UNIX_RESTART_CRON();
    }
    remove_service("/etc/init.d/categories-cache");
    $rm=$unix->find_program("rm");
    build_progress("{removing}",80);
    system("$rm -rf /home/artica/categories-cache/*");

    build_progress("{removing} {done}",100);
}



function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


function categories_cache_monit(){
    $f[]="check process APP_CATEGORIES_CACHE with pidfile /var/run/categories-cache/categories-cache.pid";
    $f[]="\tstart program = \"/etc/init.d/categories-cache start\"";
    $f[]="\tstop program = \"/etc/init.d/categories-cache stop\"";

    $f[]="";
    @file_put_contents("/etc/monit/conf.d/APP_CATEGORIES_CACHE.monitrc", @implode("\n", $f));
    if(!is_file("/etc/monit/conf.d/APP_CATEGORIES_CACHE.monitrc")){
        echo "/etc/monit/conf.d/APP_CATEGORIES_CACHE.monitrc failed !!!\n";
    }
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");

}

function categories_cache_install(){
    $f[]="#! /bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:		categories-cache";
    $f[]="# Required-Start:	\$syslog \$remote_fs";
    $f[]="# Required-Stop:	\$syslog \$remote_fs";
    $f[]="# Should-Start:		\$local_fs";
    $f[]="# Should-Stop:		\$local_fs";
    $f[]="# Default-Start:	2 3 4 5";
    $f[]="# Default-Stop:		0 1 6";
    $f[]="# Short-Description:	categories-cache - Persistent key-value db";
    $f[]="# Description:		categories-cache - Persistent key-value db";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="";
    $f[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
    $f[]="DAEMON=/usr/bin/categories-cache";
    $f[]="DAEMON_ARGS=/etc/categories-cache/categories-cache.conf";
    $f[]="NAME=categories-cache";
    $f[]="DESC=categories-cache";
    $f[]="";
    $f[]="RUNDIR=/var/run/categories-cache";
    $f[]="PIDFILE=\$RUNDIR/categories-cache.pid";
    $f[]="";
    $f[]="test -x \$DAEMON || exit 0";
    $f[]="";
    $f[]="if [ -r /etc/default/\$NAME ]";
    $f[]="then";
    $f[]="	. /etc/default/\$NAME";
    $f[]="fi";
    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="set -e";
    $f[]="";
    $f[]="if [ \"\$(id -u)\" != \"0\" ]";
    $f[]="then";
    $f[]="	log_failure_msg \"Must be run as root.\"";
    $f[]="	exit 1";
    $f[]="fi";
    $f[]="";
    $f[]="Run_parts () {";
    $f[]="	if [ -d /etc/redis/\${NAME}.\${1}.d ]";
    $f[]="	then";
    $f[]="		su redis -s /bin/sh -c \"run-parts --exit-on-error /etc/redis/\${NAME}.\${1}.d\"";
    $f[]="	fi";
    $f[]="}";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";
    $f[]="	echo never > /sys/kernel/mm/transparent_hugepage/enabled || true";
    $f[]="	echo -n \"Starting \$DESC: \"";
    $f[]="	mkdir -p \$RUNDIR";
    $f[]="	touch \$PIDFILE";
    $f[]="	chown redis:redis \$RUNDIR \$PIDFILE";
    $f[]="	chown -R redis:redis /var/log/categories-cache";
    $f[]="	chmod 755 \$RUNDIR";
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="  $php ".__FILE__." --build || true";
    $f[]="";
    $f[]="  $php ".__FILE__." --start || true";
    $f[]="	;;";
    $f[]="  stop)";
    $f[]="  $php ".__FILE__." --stop || true";
    $f[]="	sleep 1";
    $f[]="	;;";
    $f[]="";
    $f[]="  restart|force-reload)";
    $f[]="	\${0} stop";
    $f[]="	\${0} start";
    $f[]="	;;";
    $f[]="";
    $f[]="  status)";
    $f[]="	status_of_proc -p \${PIDFILE} \${DAEMON} \${NAME}";
    $f[]="	;;";
    $f[]="";
    $f[]="  *)";
    $f[]="	echo \"Usage: /etc/init.d/\$NAME {start|stop|restart|force-reload|status}\" >&2";
    $f[]="	exit 1";
    $f[]="	;;";
    $f[]="esac";
    $f[]="";
    $f[]="exit 0\n";

    $INITD_PATH="/etc/init.d/categories-cache";
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