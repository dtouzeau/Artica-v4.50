<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
$GLOBALS["OUTPUT"]=true;
$GLOBALS["TITLENAME"]="Legal Logs Configurator";


if($argv[1]=="--connect"){connect();exit();}
if($argv[1]=="--disconnect"){disconnect();exit();}
if($argv[1]=="--uploaded"){scan_uploaded();exit();}
if($argv[1]=="--list"){build_list();exit();}



function disconnect(){

    $LegalLogArticaServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaServer"));
    $LegalLogArticaPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaPort"));

    $uri="https://$LegalLogArticaServer:$LegalLogArticaPort/nodes.listener.php?legallogs-unregister=yes";

    build_progress(50,"{disconnecting}");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Pragma: no-cache,must-revalidate",
            "Cache-Control: no-cache,must revalidate",'Expect:')
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    curl_exec($ch);
    $Infos= curl_getinfo($ch);
    $http_code=$Infos["http_code"];
    echo "HTTP Code: $http_code\n";


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaClient",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogSyslogPort",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaUsername","");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaPassword","");
    $unix=new unix();

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $php5=$unix->LOCATE_PHP5_BIN();

    if($SQUIDEnable==1){
        shell_exec("$php5 /usr/share/artica-postfix/exec.squid.global.access.php --logging >/dev/null 2>&1");
    }


    build_progress(100,"{success}");
    return true;

}

function connect(){
	$unix=new unix();
    //$LegalLogArticaClient=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaClient"));
    $LegalLogArticaServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaServer"));
    $LegalLogArticaPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaPort"));
    $LegalLogArticaUsername=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaUsername"));
    $LegalLogArticaPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("LegalLogArticaPassword"));
    if($LegalLogArticaPort==0){$LegalLogArticaPort=9000;}
    if($LegalLogArticaUsername==null){$LegalLogArticaUsername="Manager";}

    $hostname=$unix->hostname_g();
	build_progress(20,"{connecting}");
    if(!$unix->CORP_LICENSE()){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaClient",0);
        build_progress("{license_error} on $hostname",110);
        return false;
    }

    $MAIN["CREDS"]["USER"]=$LegalLogArticaUsername;
    $MAIN["CREDS"]["PASS"]=$LegalLogArticaPassword;
    $MAIN["CREDS"]["HOST"]=$hostname;
    $fields=urlencode(base64_encode(serialize($MAIN)));

    $uri="https://$LegalLogArticaServer:$LegalLogArticaPort/nodes.listener.php?legallogs-register=$fields";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Pragma: no-cache,must-revalidate",
        "Cache-Control: no-cache,must revalidate",'Expect:')
    );
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $result=curl_exec($ch);
    $Infos= curl_getinfo($ch);
    $http_code=$Infos["http_code"];
    echo "HTTP Code: $http_code\n";

    if(curl_errno($ch)){
        $curl_error=curl_error($ch);
        build_progress("Communication failed Err.$curl_error",110);
        echo "Error:Curl error: $curl_error\n";
        return false;
    }

    if($http_code<>200){
        build_progress("Communication failed Err.$http_code",110);
       return false;
    }

    curl_close($ch);

    if(!preg_match("#<SUCCESS>([0-9]+)<\/SUCCESS>#is",$result,$re)){
        if(preg_match("#<error>(.+?)<\/error>#is",$result,$re)){
            build_progress($re[1],110);
            return false;
        }
        echo "*******************************\n".$result."\n*******************************\n";
        build_progress("Communication failed unable to get master transport port",110);
        return false;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaClient",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogSyslogPort",intval($re[1]));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaUsername","");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("LegalLogArticaPassword","");

    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    $php5=$unix->LOCATE_PHP5_BIN();

    if($SQUIDEnable==1){
        shell_exec("$php5 /usr/share/artica-postfix/exec.squid.global.access.php --logging >/dev/null 2>&1");
    }

    build_progress(100,"{success}");
    return true;
}

function build_progress($pourc,$text){
    if(is_numeric($text)){ $ToText=$pourc; $pourc=$text; $text=$ToText; }
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/legallogs.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}

function build_list():bool{
    $BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));
    if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
    $zsql=array();
    $unix=new unix();
    $timefile="/etc/artica-postfix/croned.1/".basename(__FILE__).".time";
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";

    $pid=$unix->get_pid_from_file($pidfile);
    if(!$GLOBALS["VERBOSE"]) {
        if ($unix->process_exists($pid)) {
            echo "Already running process pid $pid;\n";
            return false;
        }

        $timeExec=$unix->file_time_min($timefile);
        if($timeExec<30){
            echo "Needing 30Mn, current is {$timeExec}mn;\n";
            return false;
        }

        @file_put_contents($pidfile,getmypid());
        @unlink($timefile);
        @file_put_contents($timefile,time());

    }



    $unix=new unix();
    $find=$unix->find_program("find");
    $q=new lib_sqlite("/home/artica/SQLITE/legallogs.db");

    $sql="CREATE TABLE IF NOT EXISTS `store_files` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`filepath` TEXT UNIQUE,
			`hostname` string,
			`ztype` string,
			`filesize` INTEGER ,
			`filedate` INTEGER )";
    $q->QUERY_SQL($sql);

    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }

    $results=$q->QUERY_SQL("SELECT * FROM store_files");
    foreach ($results as $index=>$ligne){
        $hostname=$ligne["hostname"];
        $DBS[$ligne["filepath"]]="$hostname";
    }


    $results=array();
    $cmd="$find $BackupMaxDaysDir -type f -name '*.gz' 2>&1";
    exec($cmd,$results);

    foreach ($results as $spath){
        $spath=trim($spath);
        if($spath==null){continue;}
        if(!is_file($spath)){continue;}
        if(isset($DBS[$spath])){
            if($GLOBALS["VERBOSE"]){
                echo "$spath already exists in database... {$DBS[$ligne["filepath"]]} SKIP\n";            }
            continue;
        }

        $fname=basename($spath);
        $size=@filesize($spath);
        $hostname="unknown";
        $stype="none";
        $sdate=0;
        $year=0;
        $month=0;
        $day=0;

        if(preg_match("#\/(proxy|mail)\/([0-9]+)\/([0-9]+)\/([0-9]+)#",$spath,$re)){
            $stype=$re[1];
            $year=$re[2];
            $month=$re[3];
            $day=$re[4];
        }

        if(preg_match("#^(.+?)\.([0-9\-]+)_([0-9\-]+)--#",$fname,$re)){
            $hostname=$re[1];
            $stringdate=$re[2]." ".str_replace("-",":",$re[3]);
            $sdate=strtotime($stringdate);
            echo "$fname ---> $stringdate --> $sdate\n";
            $year=date("Y",$sdate);
            $month=date("m",$sdate);
            $day=date("d",$sdate);
        }

        if(preg_match("#^[0-9]+\.(.+?)\.([0-9\-]+)_([0-9\-]+)--#",$fname,$re)){
            $hostname=$re[1];

            $stringdate=$re[2]." ".str_replace("-",":",$re[3]);
            $sdate=strtotime($stringdate);

            $year=date("Y",$sdate);
            $month=date("m",$sdate);
            $day=date("d",$sdate);
            echo "$fname ---> $stringdate --> $sdate --> $year-$month-$day (2)\n";
        }




        if(preg_match("#^(.+?)\.access$#",$hostname,$re)){$hostname=$re[1];}
        if(preg_match("#^cache-(.+?)$#",$hostname,$re)){
            $hostname=$re[1];
            $stype="Proxy service";
        }

        if(preg_match("#^[0-9]+-[0-9]+-[0-9]+$#",$hostname)){
            echo "Corrupted name $hostname [$spath] date:$sdate $year-$month-$day\n";
            continue;
        }

        if(strpos($stype,"/")>0){
            echo "Corrupted stype $stype [$spath]\n";
            continue;
        }

        if($sdate==0){
            echo "Date Not found...\n";
            if($year>0) {
                $sdate =strtotime("$year-$month-$day 00:00:01");
            }
        }

        $zsql[]="('$spath','$hostname','$stype','$size','$sdate')";

    }

    if(count($zsql)>0){
        $q->QUERY_SQL("INSERT INTO 
        `store_files` (filepath,hostname,ztype,filesize,filedate) VALUES 
        ".@implode(",",$zsql));
        if(!$q->ok){echo $q->mysql_error."\n";}
    }

    foreach ($DBS as $path=>$none){
        if(!is_file($path)){
            $q->QUERY_SQL("DELETE * FROM store_files WHERE filepath='$path'");
        }
    }

    $FolderSize=$unix->DIRSIZE_BYTES_NOCACHE($BackupMaxDaysDir);
    $PARTPERCENT=$unix->DIRECTORY_USEPERCENT($BackupMaxDaysDir);
    echo "$BackupMaxDaysDir = {$FolderSize}Bytes {$PARTPERCENT}%\n";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupMaxDaysDirSize",$FolderSize);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupMaxDaysDirPercent",$PARTPERCENT);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupMaxDaysDirScanTime",time());
    return true;

}

function scan_uploaded(){
    build_list();
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . ".pid";
    $timefile = "/etc/artica-postfix/pids/" . basename(__FILE__) . ".time";
    $tarballs_dir = "/usr/share/artica-postfix/ressources/conf/upload/LEGALS";
    if (!is_dir($tarballs_dir)) {
        return false;
    }

    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, __FILE__)) {
        echo "Already PID running $pid (" . basename(__FILE__) . ")\n";
        exit();
    }
    if (!$GLOBALS["VERBOSE"]) {
        $timeExec = $unix->file_time_min($timefile);
        if ($timeExec < 5) {
            echo "Need 5mn max, current is {$timeExec}Mn\n";
        }
    }

    if (!$handle = opendir($tarballs_dir)) {
        return false;
    }
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {
            continue;
        }
        if ($file == "..") {
            continue;
        }

        if (!preg_match("#\.gz$#",$file)) {
            continue;
        }
        echo "Scanning $file\n";
        $tarball_file = "$tarballs_dir/$file";
        if (is_dir($tarball_file)) {
            continue;
        }

        $tarball_file_text = "$tarball_file.txt";
        if (!is_file($tarball_file_text)) {
            continue;
        }
        $tarball_dest = trim(@file_get_contents($tarball_file_text));
        if (strlen($tarball_dest) < 10) {
            continue;
        }
        $tarball_dest_dir = dirname($tarball_dest);
        if (!is_dir($tarball_dest_dir)) {
            @mkdir($tarball_dest_dir, 0755, true);
        }

        $time = time();
        if (is_file($tarball_dest)) {
            $basename = basename($tarball_dest);
            $tarball_dest = "$tarball_dest_dir/$time.$basename";
        }

        if (!@copy($tarball_file, $tarball_dest)) {
            $unix->ToSyslog("[Legal Logs]: Unable to move $tarball_file to $tarball_dest",
                false, basename(__FILE__));
            squid_admin_mysql(0, "[Legal Logs]: Unable to move $tarball_file to $tarball_dest");
        }

        @unlink($tarball_file_text);
        @unlink($tarball_file);


    }

    return true;
}
