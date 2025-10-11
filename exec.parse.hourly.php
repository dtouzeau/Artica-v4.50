#!/usr/bin/php -q
<?php
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
ini_set('memory_limit','1000M');
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
include_once(dirname(__FILE__)."/ressources/class.realtime-buildsql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
$GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogDir");
if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid/realtime-events";}

if(is_file("/usr/local/ArticaStats/bin/postgres")){
    $GLOBALS["LogFileDeamonLogDir"]=@file_get_contents("/etc/artica-postfix/settings/Daemons/LogFileDeamonLogPostGresDir");
    if($GLOBALS["LogFileDeamonLogDir"]==null){$GLOBALS["LogFileDeamonLogDir"]="/home/artica/squid-postgres/realtime-events";}
}


if(preg_match("#--verbose#",implode(" ",$argv))){
    echo "VERBOSED....\n";
    $GLOBALS["VERBOSE"]=true;$GLOBALS["TRACE_INFLUX"]=true;
    $GLOBALS["OUTPUT"]=true;
    $GLOBALS["debug"]=true;
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}

if(isset($argv[1])) {
    if ($argv[1] == "--failed") {
        failedZ();
        exit;
    }
    if ($argv[1] == "--rotate") {
        rotate();
        exit;
    }
    if ($argv[1] == "--file") {
        ACCESS_LOG_HOURLY_BACKUP($argv[2]);
    }
    if ($argv[1] == "--ufdb") {
        UFDB_LOG_NEW_HOURLY();
        die();
    }
}

scan();
function scan(){
    $pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    $pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidFile);
    if($unix->process_exists($pid)){
        events("A process, $pid Already exists...");
        return;
    }

    $GLOBALS["MYHOSTNAME_PROXY"]=$unix->hostname_g();

    @file_put_contents($pidFile, getmypid());
    $time=$unix->file_time_min($pidtime);
    if(!$GLOBALS["VERBOSE"]){
        if($time<5){
            events("{$time}mn, require minimal 5mn");
            return;
        }
    }

    @file_put_contents($pidtime,time());

    events("Running UFDB_LOG_NEW_HOURLY()");
    UFDB_LOG_NEW_HOURLY();
    UFDB_LOG_NEW_HOURLY_FAILED();


    events("Running ROTATE()");
    ROTATE();
    failedZ();
}





function failedZ(){
    $pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    $pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidFile);
    if($unix->process_exists($pid)){ return;}
    @file_put_contents($pidFile, getmypid());
    $time=$unix->file_time_min($pidtime);
    if($time<5){return;}

}
function events($text=null){
    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();

        if(isset($trace[0])){
            $file=basename($trace[0]["file"]);
            $function=$trace[0]["function"];
            $line=$trace[0]["line"];
        }

        if(isset($trace[1])){
            $file=basename($trace[1]["file"]);
            $function=$trace[1]["function"];
            $line=$trace[1]["line"];
        }



    }

    $array_load=sys_getloadavg();
    $internal_load=$array_load[0];

    $logFile="/var/log/artica-parse.hourly.log";
    $mem=round(((memory_get_usage()/1024)/1000),2);

    $suffix=date("Y-m-d H:i:s")." [".basename(__FILE__)."/$function/$line]:";
    if($GLOBALS["VERBOSE"]){echo "$suffix $text memory {$mem}MB (system load $internal_load)\n";}

    if (is_file($logFile)) {
        $size=filesize($logFile);
        if($size>1000000){@unlink($logFile);}
    }
    $f = @fopen($logFile, 'a');
    @fwrite($f, "$suffix $text\n");
    @fclose($f);
}




function ROTATE(){
    $unix=new unix();

    $backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/mainsize-backup";
    ROTATE_DIR($backupdir);

    $backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/access-backup";
    ROTATE_DIR($backupdir);

    $backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/webfilter-backup";
    ROTATE_DIR($backupdir);


    $backupdir="{$GLOBALS["LogFileDeamonLogDir"]}/volume-backup";
    ROTATE_DIR($backupdir);

}
function ROTATE_DIR($backupdir){
    $unix=new unix();
    $cat=$unix->find_program("cat");
    $files=$unix->DirFiles($backupdir);
    $suffix="influx";
    if(is_file("/usr/local/ArticaStats/bin/postgres")){ $suffix="postgres";}


    $today=date("Y-m-d");
    foreach ($files as $basename=>$subarray){

        if(preg_match("#^([0-9\-]+)\.gz$#", $basename,$re)){continue;}

        if(preg_match("#^([0-9\-]+)\.back$#", $basename,$re)){
            if($re[1]<>$today){
                if(!$unix->compress("$backupdir/$basename", "$backupdir/{$re[1]}.gz")){@unlink("$backupdir/{$re[1]}.gz");continue;}
                @unlink("$backupdir/$basename");
            }
            continue;
        }


        if(!preg_match("#^([0-9]+)\.$suffix\.log$#", $basename,$re)){
            echo "$basename no match...\n";
            continue;
        }
        $time=$re[1];
        $day=date("Y-m-d",$time);

        $handleOUT = @fopen("$backupdir/$basename", "r");
        $handleIN = @fopen("$backupdir/$day.back", "a");
        $c=0;
        while (!feof($handleOUT)){
            $line =trim(fgets($handleOUT, 4096));
            @fwrite($handleIN,"$line\n");
            $c++;
        }

        events("$backupdir/$basename $c line(s)");
        fclose($handleOUT);
        fclose($handleIN);
        @unlink("$backupdir/$basename");

    }

}



function TimeToInflux($time,$Nomilliseconds=false){
    $time=QueryToUTC($time);
    $milli=null;
    $microtime=microtime();
    preg_match("#^[0-9]+\.([0-9]+)\s+#", $microtime,$re);
    $ms=intval($re[1]);
    if(!$Nomilliseconds){$milli=".{$ms}";}
    return date("Y-m-d",$time)."T".date("H:i:s",$time)."{$milli}Z";
}

function UFDB_LOG_NEW_HOURLY_FAILED(){
    $directory_failed="/home/artica/squid/webfilter/events-failed";
    $q=new postgres_sql();
    $unix=new unix();
    if(!is_dir($directory_failed)){return;}
    if (!$handle = opendir($directory_failed)) {return;}

    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$directory_failed/$filename";
        if(is_dir($targetFile)){continue;}
        $xtime=$unix->file_time_min($targetFile);
        if($xtime>2880){@unlink($targetFile);continue;}
        $q->QUERY_SQL(@file_get_contents($targetFile));
        if(!$q->ok){continue;}
        @unlink($targetFile);
    }


}

function UFDB_LOG_NEW_HOURLY(){
    $directory="/home/artica/squid/webfilter/events";
    $q=new postgres_sql();
    $unix=new unix();
    if(!is_dir($directory)){return;}
    if (!$handle = opendir($directory)) {return;}
    $prefix="INSERT INTO webfilter (zDate,website,category,rulename,public_ip,blocktype,why,hostname,client,proxyname,rqs)";
    $f=array();
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$directory/$filename";
        if(is_dir($targetFile)){continue;}
        if($GLOBALS["VERBOSE"]){echo "$targetFile\n";}
        if($unix->file_time_min($targetFile)>180){@unlink($targetFile);continue;}
        $sql_suffix=@file_get_contents($targetFile);
        $sql="$prefix VALUES $sql_suffix";
        $q->QUERY_SQL($sql);
        if(!$q->ok){continue;}
        @unlink($targetFile);

    }

}
function MINTOTEN($MIN){
    $MA["00"]="00";
    $MA["01"]="00";
    $MA["02"]="00";
    $MA["03"]="00";
    $MA["04"]="00";
    $MA["05"]="00";
    $MA["06"]="00";
    $MA["07"]="00";
    $MA["08"]="00";
    $MA["09"]="00";
    $MA[10]=10;
    $MA[11]=10;
    $MA[12]=10;
    $MA[13]=10;
    $MA[14]=10;
    $MA[15]=10;
    $MA[16]=10;
    $MA[17]=10;
    $MA[18]=10;
    $MA[19]=10;
    $MA[20]=20;
    $MA[21]=20;
    $MA[22]=20;
    $MA[23]=20;
    $MA[24]=20;
    $MA[25]=20;
    $MA[26]=20;
    $MA[27]=20;
    $MA[28]=20;
    $MA[29]=20;
    $MA[30]=30;
    $MA[31]=30;
    $MA[32]=30;
    $MA[33]=30;
    $MA[34]=30;
    $MA[35]=30;
    $MA[36]=30;
    $MA[37]=30;
    $MA[38]=30;
    $MA[39]=30;
    $MA[40]=40;
    $MA[41]=40;
    $MA[42]=40;
    $MA[43]=40;
    $MA[44]=40;
    $MA[45]=40;
    $MA[46]=40;
    $MA[47]=40;
    $MA[48]=40;
    $MA[49]=40;
    $MA[50]=50;
    $MA[51]=50;
    $MA[52]=50;
    $MA[53]=50;
    $MA[54]=50;
    $MA[55]=50;
    $MA[56]=50;
    $MA[57]=50;
    $MA[58]=50;
    $MA[59]=50;
    return $MA[$MIN];


}
?>