#!/usr/bin/php
<?php
$GLOBALS["DATA_PROCESSED"]=0;
$GLOBALS["STATSCOMCOUNTS"]=0;
$GLOBALS["VERBOSE_COUNT"]=false;
$BLOCKED_TYPES[1]="{webfiltering}";
$BLOCKED_TYPES[2]="NoTracks";
$BLOCKED_TYPES[403]="Permission denied";
$BLOCKED_TYPES[503]="Domain Does not exists";

set_time_limit(0);
ob_implicit_flush();
declare(ticks = 1);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.memcached.inc');
include_once(dirname(__FILE__).'/ressources/class.cache-logs.inc');

$GLOBALS["CLASS_UNIX"]=new unix();
$unix=new unix();
$GLOBALS["VERBOSE"]=false;
$pid=$unix->get_pid_from_file("/var/run/proxy-logs-monitor.pid");
if($unix->process_exists($pid,__FILE__)){
    $unix->ToSyslog("[DAEMON] Stopped, already pid $pid exists....","proxy-watchdog");
    die();
}

use React\EventLoop\Factory;
use React\Socket\Server;
use React\Socket\ConnectionInterface;


include_once(dirname(__FILE__).'/ressources/externals/vendor/autoload.php');
    $GLOBALS["DATA_PROCESSED"]=0;
    $GLOBALS["DATA_SAVED"]=0;
    $data=null;
    $loop = Factory::create();
    $server = new React\Socket\Server("127.0.0.1:19102", $loop, array());
    $server->on('connection', function (ConnectionInterface $connection) use ($data){

    $connection->on('data', function ($chunk) {
        $GLOBALS["DATA_PROCESSED"]++;
        dataprocessing($chunk);
    });

    $connection->on('error', function (Exception $e) {
        $error=$e->getMessage();
        $GLOBALS["CLASS_UNIX"]->ToSyslog($error,"proxy-watchdog");
    });


});



$server->on('error', function (Exception $e) {
    $error='Error' . $e->getMessage();
    $GLOBALS["CLASS_UNIX"]->ToSyslog("proxy-watchdog",$error);
    echo date("Y-m-d H:i:s").": proxy-watchdog $error\n";
    die();
});

$unix->framework_exec("exec.squid.disable.php --syslog");

$pid = pcntl_fork();

if ($pid == -1) {
    die("Starting......: ".date("H:i:s")." Proxy Watchdog fork() call asploded!\n");
} else if ($pid) {
    if($pid>0) {
        $GLOBALS["CLASS_UNIX"]->ToSyslog("fork()ed successfully with pid $pid", "proxy-watchdog");
        @file_put_contents("/var/run/proxy-logs-monitor.pid", $pid);
        exit();
    }
}

$loop->addSignal(SIGTERM, function () use ($data, $loop) {
    $GLOBALS["CLASS_UNIX"]->ToSyslog(sprintf('%s finished running, Processed %d requests',
        posix_getpid(), $GLOBALS["DATA_PROCESSED"]),"proxy-watchdog");

   $loop->stop();
    die();
});


$loop->run();

function dataprocessing($chunk){
    if (!isset($GLOBALS["CLASS_UNIX"])) {
        $GLOBALS["CLASS_UNIX"] = new unix();
    }
    if (!isset($GLOBALS["MEMCACHE"])) {
        $GLOBALS["MEMCACHE"] = new lib_memcached();
    }
    if(strpos($chunk,"\n")>0){
        $TBR=explode("\n",$chunk);
        foreach ($TBR as $line){
            $line=trim($line);

            if($line==null){continue;}
            if(preg_match("#^<[0-9]+>(.+)#",$line,$re)){$line=$re[1];}
            Parseline($line);
        }
        return true;
    }

    Parseline($chunk);
    return true;
}



function roundToNearestMinuteInterval(\DateTime $dateTime, $minuteInterval = 10)
{
    return $dateTime->setTime(
        $dateTime->format('H'),
        round($dateTime->format('i') / $minuteInterval) * $minuteInterval, 0);
}
function events($text){
    $GLOBALS["CLASS_UNIX"]->ToSyslog($text, "proxy-watchdog");
}

