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
// Service (install, start, stop, configuration, monit, syslog) : articarest
// fsmonitor. Ne restent ici que le scan planifie (cron articafs-monitor) et
// l'outil de diagnostic --analyzediff.
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





