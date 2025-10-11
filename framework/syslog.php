<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["legal-logs-install"])){logal_logs_install();exit;}
if(isset($_GET["rotate-logs"])){RotateLogs();exit;}
if(isset($_GET["syslog-events"])){searchlogs_syslog();exit;}
if(isset($_GET["decrypt-backup"])){decrypt_backups();exit;}
if(isset($_GET["rebuild-all"])){rebuild_all();exit;}
if(isset($_GET["logs-sink"])){logs_sink_searcher();exit;}
if(isset($_GET["logs-sink-rtt"])){logs_sink_rtt();exit;}
if(isset($_GET["enable-ssl"])){enable_ssl();exit;}
if(isset($_GET["disable-ssl"])){disable_ssl();exit;}
if(isset($_GET["create-client-ssl"])){create_client_ssl();exit;}
if(isset($_GET["apply-client-ssl"])){apply_client_ssl();exit;}
if(isset($_GET["remove-host"])){remove_host();exit;}
if(isset($_GET["log-sink-params"])){logsink_params();exit;}
if(isset($_GET["log-sink-backup"])){logsink_backup();exit;}
writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);


function logs_sink_rtt():bool{
    $unix=new unix();
    $unix->framework_search_syslog($_GET["logs-sink-rtt"],
        "/var/log/logsink-rtime.log",
        "logs-sink.rtt.syslog","");
    
    return true;
}
function status():bool{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --rsyslog >/usr/share/artica-postfix/ressources/logs/syslog.status 2>&1");

    return true;
}

function logsink_params():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.syslog-engine.php --log-sink-params");
}
function logsink_backup():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.backup.logsink.php --force");
}

function enable_ssl():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.syslog-engine.php --enable-ssl",
        "syslog.ssl.progress","syslog.ssl.log");
}

function create_client_ssl():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.syslog-engine.php --client-certificate",
        "syslog.ssl.progress","syslog.ssl.log");

}
function  apply_client_ssl():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.syslog-engine.php --client-template",
        "syslog.ssl.progress","syslog.ssl.log");

}
function remove_host():bool{
    $host=$_GET["remove-host"];
    $unix=new unix();
    return $unix->framework_exec("exec.syslog-engine.php --remove-host \"$host\"");

}


function disable_ssl():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.syslog-engine.php --disable-ssl",
        "syslog.ssl.progress","syslog.ssl.log");

}


function searchlogs_syslog():bool{
    $search=trim(base64_decode($_GET["syslog-events"]));
    $target_file=PROGRESS_DIR."/syslog-events.log";
    $source_file="/var/log/rsyslogd.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){
        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return true;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");
    return true;
}
function decrypt_backups(){
    $unix=new unix();
    $unix->framework_exec("/usr/share/artica-postfix/bin/artica-rotate -decrypt-logs");
}



function rebuild_all():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.syslog-engine.php --all-daemons","syslog.daemons.progress","syslog.daemons.progress.progress.log");
}

function logal_logs_install(){
    $unix=new unix();
    $unix->framework_execute("exec.syslog-engine.php --install --legal-logs","syslog.install.progress","syslog.install.progress.log");

}

function uninstall(){
	$unix=new unix();
    $unix->framework_execute("exec.syslog-engine.php --uninstall","syslog.install.progress","syslog.install.progress.log");

}
function zgrep_pid(){
    $unix=new unix();
    $fname=$_GET["fname"];
    $find=$unix->find_program("find");
    $zgrep=$unix->find_program("zgrep");
}
function logs_sink_searcher(){
    $date=intval($_GET["date"]);
    $host=trim($_GET["host"]);
    $search=base64_decode($_GET["search"]);
    $fname=$_GET["fname"];
    $rows=intval($_GET["rows"]);
    $LogSinkWorkDir=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LogSinkWorkDir");
    if($LogSinkWorkDir==null){$LogSinkWorkDir="/home/syslog/logs_sink";}
    $path=$LogSinkWorkDir;


    if($host==null){$host="*";}
    if($host=="all"){$host="*";}
    writelogs_framework("date = $date",__FUNCTION__,__FILE__,__LINE__);

    if($host<>null){
        $path="/home/syslog/logs_sink/$host";
    }
    if($date>5){
        $spf=date("Y-m-d",$date)."_";
    }

    $spf=$spf."*.gz";
    $commut[]="-E";
    $commut[]="-i";
    $commut[]="-e";
    if($rows<200){$rows=200;}
    $unix=new unix();
    $find=$unix->find_program("find");
    $zgrep=$unix->find_program("zgrep");
    $tail=$unix->find_program("tail");
    $rm=$unix->find_program("rm");
    $nohup=$unix->find_program("nohup");
    $tmpfile=PROGRESS_DIR."/$fname.log";
    $zgrepcmd="$zgrep ".@implode(" ",$commut)." \"$search\" \$I 2>&1";
    $shname=PROGRESS_DIR."/$fname.sh";
    $sh[]="#!/bin/sh";
    $cmdline="for I in $($find $path -name \"$spf\"); do $zgrepcmd; done 2>&1";
    writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
    $sh[]="$cmdline";
    $sh[]="$rm $shname\n";
    @file_put_contents($shname,@implode("\n",$sh));
    @chmod($shname,0755);
    if(is_file($tmpfile)){@unlink($tmpfile);}
    $cmd2="$nohup $shname|$tail -n $rows >$tmpfile 2>&1 &";
    writelogs_framework($cmd2,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd2);
}
function is_regex($pattern):bool{
    $f[]="{";
    $f[]="[";
    $f[]="+";
    $f[]="\\";
    $f[]="?";
    $f[]="$";
    $f[]=".*";
    foreach ($f as $key=>$val){
        if(strpos(" $pattern", $val)>0){return true;}
    }

    return false;
}

function RotateLogs(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["rotate-logs"]));
    $filename="/usr/share/artica-postfix/ressources/logs/web/rotate.syslog";
    $srcfile="/var/log/legal-rotate.log";

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline=$TERM_P;
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' $srcfile |$tail -n $max >$filename 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("$filename.pattern", $search);
    shell_exec($cmd);

}