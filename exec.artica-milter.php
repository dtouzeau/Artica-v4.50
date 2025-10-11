<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__)."/ressources/class.smtpd.notifications.inc");
include_once(dirname(__FILE__)."/ressources/class.maillogs.inc");
$GLOBALS["CLASS_SOCKETS"]       = new sockets();
$GLOBALS["GENPROGGNAME"]        = "ksrn.progress";
$GLOBALS["TITLENAME"]           = "Artica notifications service";
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
// COmpilateur 192.168.1.190


if($argv[1]=="--syslog"){build_syslog();exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--monit"){build_monit();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--export"){export_logs();exit;}
if($argv[1]=="--csv"){export_csv();exit;}
if(isset($argv[1])){
    echo "Unable to understand {$argv[1]}\n";die();
}
function stop($aspid=false){
   shell_exec("/usr/sbin/artica-phpfpm-service -stop-artica-milter");

}
function start($aspid=false){
    shell_exec("/usr/sbin/artica-phpfpm-service -start-artica-milter");
    return true;

}
function export_logs(){
    build_progress(10,"{exporting}");
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tmpfile=$unix->FILE_TEMP().".txt";
    build_progress(50,"{exporting}");
    shell_exec("$grep --binary-files=text \": id=\" /var/log/artica-milter.log >$tmpfile 2>&1");
    build_progress(80,"{compressing}");
    $destfile=PROGRESS_DIR."/artica-milter.gz";
    if(!$unix->compress($tmpfile,$destfile)){
        build_progress($GLOBALS["COMPRESSOR_ERROR"],110);
        @unlink($tmpfile);
        return false;

    }
    build_progress(100,"{success}");
    return true;
}
function export_csv(){
    build_progress(10,"{exporting}");
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tmpfile=$unix->FILE_TEMP().".txt";
    build_progress(50,"{exporting}");
    shell_exec("$grep --binary-files=text \": id=\" /var/log/artica-milter.log >$tmpfile 2>&1");
    build_progress(80,"{compressing}");
    $destfile=PROGRESS_DIR."/artica-milter.csv";

    $handle = @fopen($tmpfile, "r");
    if (!$handle) {
        build_progress(110,"{failed}");
        echo "Failed to open file $tmpfile\n";
        @unlink($tmpfile);
        return false;}



    $fp = fopen($destfile, 'w');
    $sf=array("Time","Client","From","To","Subject");
    fputcsv($fp, $sf);
    while (!feof($handle)) {
        $c++;
        $line = trim(fgets($handle, 4096));
        if(!preg_match("#^(.+?)\s+([0-9]+)\s+([0-9:]+)\s+.*?\]:(.+)#",$line,$re)){continue;}
        $month=$re[1];$day=$re[2];$time=$re[3];$line=$re[4];
        $stime=date('Y')."-$month-$day $time";
        $MAIN=array();
        if(!preg_match_all("#(.+?)=\"(.+?)\";#",$line,$re)){continue;}
        foreach ($re[1] as $index=>$line){
            $key=trim($line);
            $value=trim($re[2][$index]);
            $MAIN[$key]=$value;
        }
        $subject=clean_header($MAIN["subject"]);
        $recipients=parse_recipitents(clean_header($MAIN["to"]));
        $sf=array($stime,$MAIN["ipaddr"],$MAIN["from"],$recipients,$subject);
        fputcsv($fp, $sf);
    }
    build_progress(100,"{done}");
    fclose($fp);
    return true;
}


function restart():bool{
    build_progress(10,"{stopping} Artica Milter");
    stop(true);
    if(!start(true)){
        build_progress(40,"{starting} Artica Milter {failed}");
        return false;
    }
    build_progress(100,"{starting} Artica Milter {success}");
    return true;
}
function uninstall(){
    build_progress(15, "{uninstalling}");
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArticaMilter",0);
    shell_exec("/usr/sbin/artica-phpfpm-service -uninstall-artica-milter");

    $unix->framework_exec("exec.postfix.maincf.php --milters");
    build_progress(100, "{installing} {success}");
    return true;
}

function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"articamilter.progress");
}

function install():bool{
    $unix=new unix();
    build_progress(15, "{installing}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableArticaMilter",1);
    shell_exec("/usr/sbin/artica-phpfpm-service -install-artica-milter");
    $unix->framework_exec("exec.postfix.maincf.php --milters");
    build_progress(70, "{installing}");
    start(true);
    build_progress(100, "{installing} {success}");
    return true;
}

function _out($text):bool{
    $date=date("H:i:s");
    echo "Starting......: $date [INIT]: {$GLOBALS["TITLENAME"]}, $text\n";
    return true;
}
function _xout($text):bool{
    $date=date("H:i:s");
    echo "Stopping......: $date [INIT]: {$GLOBALS["TITLENAME"]}, $text\n";
    return true;
}
