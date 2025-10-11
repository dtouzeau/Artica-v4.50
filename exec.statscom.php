<?php
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.hosts.inc');
$GLOBALS["TITLENAME"]="Artica Statistics";
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if(isset($argv[1])) {
    $GLOBALS["ARGVS"] = implode(" ", $argv);
    if ($argv[1] == "--stop") {
        $GLOBALS["OUTPUT"] = true;
        exit();
    }
    if ($argv[1] == "--start") {
        $GLOBALS["OUTPUT"] = true;
        die(1);
    }
    if ($argv[1] == "--restart") {
        $GLOBALS["OUTPUT"] = true;
        exit();
    }
    if ($argv[1] == "--install") {
        $GLOBALS["OUTPUT"] = true;
        install();
        exit();
    }
    if ($argv[1] == "--install-redis") {
        exit();
    }
    if ($argv[1] == "--uninstall") {
        $GLOBALS["OUTPUT"] = true;
        uninstall();
        exit();
    }
    if ($argv[1] == "--uninstall-redis") {
        $GLOBALS["OUTPUT"] = true;
        uninstall_redis();
        exit();
    }
    if ($argv[1] == "--export-db") {
        $GLOBALS["OUTPUT"] = true;
        export_database();
        exit();
    }
    if ($argv[1] == "--import-db") {
        $GLOBALS["OUTPUT"] = true;
        import_database($argv[2]);
        exit();
    }
    if($argv[1]=="--migration"){
        install_migration();
        exit;
    }
    if($argv[1]=="--service"){
        die();
    }

}

function export_database_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"exportdb.progress");
}

function import_database($filepath): bool{
    $unix       = new unix();
    $srcfile    = base64_decode($filepath);
    $ARROOTR    = ARTICA_ROOT;
    $tmpdir     = $unix->TEMP_DIR();
    $filepath   = "$ARROOTR/ressources/conf/upload/$srcfile";
    $tpath      = "$tmpdir/statscom.gz";
    $importF    = "$tmpdir/statscom.pgdump";
    $pgrestore  = "/usr/local/ArticaStats/bin/pg_restore";
    $tmplog     = "$tmpdir/pg_restore.log";

    if(!preg_match("#\.gz$#",$srcfile)){
        @unlink($filepath);
        export_database_progress(110,"{importing} {failed} - require a gz file");
        return false;
    }

    if(is_file($tpath)){@unlink($tpath);}
    if(!@copy($filepath,$tpath)){
        @unlink($filepath);
        export_database_progress(110,"{importing} {failed} - no space left");
        return false;
    }
    @unlink($filepath);
    export_database_progress(10,"{uncompressing}");
    if(!$unix->uncompress($tpath,$importF)){
        @unlink($tpath);
        if(is_file($importF)){@unlink($importF);}
        export_database_progress(110,"{uncompressing} {failed} - no space left or corrupted file");
        return false;
    }
    @unlink($tpath);
    export_database_progress(50,"{importing}");
    $nohup=$unix->find_program("nohup");
    $cmd = "$nohup $pgrestore -v --dbname=proxydb --format=custom -h /var/run/ArticaStats -U ArticaStats $importF >$tmplog 2>&1 &";
    echo $cmd."\n";
    system($cmd);

    shell_exec($cmd);
    sleep(1);
    $i=20;
    while (true){
        $i++;
        if($i>98){$i=98;}
        $PID=$unix->PIDOF($pgrestore);
        if(!$unix->process_exists($PID)){break;}
        $ftime=$unix->PROCESS_TTL_TEXT($PID);
        export_database_progress($i,"{importing} ($ftime)");
        sleep(5);
    }

    $f=explode("\n",@file_get_contents($tmplog));
    foreach ($f as $line){
        echo "$line\n";
    }


    @unlink($importF);
    export_database_progress(100,"{importing} {success}");
    return true;
}

function install_migration():bool{
    squid_admin_mysql(1,"{installing} new Statistics Communicator service");
    stop_old_php();

    if(is_file("/usr/share/artica-postfix/exec.StatsCommunicator.php")) {
        squid_admin_mysql(1,"Remove old StatsCommunicator process..");
        @unlink("/usr/share/artica-postfix/exec.StatsCommunicator.php");
    }
    return true;
}

function export_database():bool{
    $unix=new unix();
    $ARROOTR=ARTICA_ROOT;
    $pgdump="/usr/local/ArticaStats/bin/pg_dump";
    $tmpdir=$unix->TEMP_DIR();
    $TABLES["statscom_entity"]=true;
    $TABLES["statscom_proxies"]=true;
    $TABLES["statscom_websites"]=true;
    $TABLES["statscom_users"]=true;
    $TABLES["statscom_husers"]=true;
    $TABLES["statscom_hsites"]=true;
    $TABLES["statscom"]=true;
    $TABLES["statscom_days"]=true;
    $TABLES["statsblocks"]=true;

    $tfile="$tmpdir/statscom.pgdump";
    $tcompress="$ARROOTR/ressources/conf/upload/statscom.gz";

    if(is_file($tcompress)){@unlink($tcompress);}


    foreach ($TABLES as $pgtables=>$none){
        $tswitch[]="-t $pgtables";
    }
    export_database_progress(10,"{exporting}");
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $pgdump -Fc --file=$tfile ".@implode(" ",$tswitch)." -h /var/run/ArticaStats -U ArticaStats proxydb >/dev/null 2>&1 &";
    echo $cmd."\n";
    shell_exec($cmd);
    sleep(1);
    $i=20;
    while (true){
        $i++;
        if($i>79){$i=79;}
        $PID=$unix->PIDOF($pgdump);
        if(!$unix->process_exists($PID)){break;}
        $size=filesize($tfile);
        $size=$unix->FormatBytes($size/1024);
        export_database_progress($i,"{exporting} ($size)");
        sleep(1);
    }


    if(!is_file($tfile)){
        export_database_progress(110,"{exporting} {failed}");
        return false;
    }

    export_database_progress(80,"{compressing}");
    if(!$unix->compress($tfile,$tcompress)){
        export_database_progress(110,"{compressing} {failed}");
        @unlink($tfile);
        if(is_file($tcompress)){@unlink($tcompress);}
        return false;
    }

    @unlink($tfile);
    export_database_progress(100,"{success}");

    return true;
}



function build_progress_restart($pourc,$text){
    $date=date("Y-m-d H:i:s");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/statscom.progress";
    echo "$date: [$pourc%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
}
function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]=PROGRESS_DIR."/stats-com.progress";
	echo "$date: [$pourc%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}



function install(){
    $unix=new unix();
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $php=$unix->LOCATE_PHP5_BIN();
	build_progress(15,"{installing}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableStatsCommunicator", 1);
    build_progress(20,"{installing}");
    build_progress(30,"{installing}");

    if($SQUIDEnable==1) {
        build_progress(50, "{installing}");
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logs");
        build_progress(60, "{installing}");
        shell_exec("/etc/init.d/squid restart");
        build_progress(70, "{installing}");
    }
	build_progress(90,"{installing}");
    build_progress(95,"{installing}");
    $unix->Popuplate_cron_make("statscom-statistics","*/12 * * * *","exec.statscom-stats.php");
    $unix->Popuplate_cron_make("statscom-categorize","30 23 * * *","exec.statscom-stats.php --catz");
    shell_exec("/etc/init.d/cron reload");
    $sock=new sockets();
    $sock->REST_API("/statscom/install");
	build_progress(100,"{done}");

}




function uninstall_redis(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    if(is_file("/etc/init.d/statsredis")) {
        $unix->remove_service("/etc/init.d/statsredis");
    }
    if(is_dir("/home/artica/statscom_db")) {
        shell_exec("$rm -rf /home/artica/statscom_db");
    }

    if(is_file("/etc/monit/conf.d/APP_STATS_REDIS.monitrc")) {
        @unlink("/etc/monit/conf.d/APP_STATS_REDIS.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
    }
    if(is_file("/var/log/statsredis.log")){
        @unlink("/var/log/statsredis.log");
    }
    if(is_file("/etc/statsredis.conf")){
        @unlink("/etc/statsredis.conf");
    }
}

function uninstall(){
    $unix=new unix();
    $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress(15,"{uninstalling}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableStatsCommunicator", 0);
	$unix->remove_service("/etc/init.d/statscom");

	build_progress(30,"{uninstalling}");
	@unlink("/etc/monit/conf.d/APP_STATS_COMMUNICATOR.monitrc");
    uninstall_redis();
    $rm=$unix->find_program("rm");
    build_progress(35,"{uninstalling} /home/artica/StatsComQueue");
    if(is_dir("/home/artica/StatsComQueue")){
        shell_exec("$rm -rf /home/artica/StatsComQueue");
    }

    if(is_file("/etc/rsyslog.d/statscom-logs")){
        @unlink("/etc/rsyslog.d/statscom-logs");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

    build_progress(40,"{remove_database}");
    $q=new postgres_sql();
    $q->QUERY_SQL("DROP TABLE statscom");
    $q->QUERY_SQL("DROP TABLE statscom_months");
    $q->QUERY_SQL("DROP TABLE statscom_entity");
    $q->QUERY_SQL("DROP TABLE statscom_websites");

    $f[]="statscom-statistics";
    $f[]="statscom-days";
    $f[]="statscom-categorize";

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("STATSCOM_COUNT_ENTRIES",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("STATSCOM_COUNT_BYTES",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("STATSCOM_COUNT_REQS",0);

    foreach ($f as $file){
        if(is_file("/etc/cron.d/$file")){@unlink("/etc/cron.d/$file");}
    }
    shell_exec("/etc/init.d/cron reload");
    if(is_file("/etc/rsyslog.d/artica-statscom.conf")){
        @unlink("/etc/rsyslog.d/artica-statscom.conf");
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }

    if($SQUIDEnable==1) {
        build_progress(80, "{installing}");
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logs");
    }
    $sock=new sockets();
    $sock->REST_API("/statscom/uninstall");
	build_progress(100,"{uninstalling} {done}");
	
}
function _out($text):bool{
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("stats-communicator", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}

function stop_old_php():bool{
    $unix=new unix();
    $pid=PID_NUM_PHP();
    if(!$unix->process_exists($pid)){
        _out("Old StatsCom service already stopped");
        return true;
    }


    _out("Service Shutdown pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM_PHP();
        if(!$unix->process_exists($pid)){break;}
        _out("Service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid=PID_NUM_PHP();
    if(!$unix->process_exists($pid)){
        _out("Service success...");
        return true;
    }

    _out("service shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM_PHP();
        if(!$unix->process_exists($pid)){break;}
        _out("Service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if($unix->process_exists($pid)){
        _out("service failed...");
        return false;
    }

    return true;

}



function PID_NUM_PHP(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/artica-communicator.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("exec.StatsCommunicator.php");
}

