<?php
$GLOBALS["AS_ROOT"]=true;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__).'/ressources/class.nginx.params.inc');
include_once(dirname(__FILE__)."/ressources/class.webcopy.inc");

if($argv[1]=="--simple"){runsingle($argv[2]);exit;}
if($argv[1]=="--single"){runsingle($argv[2]);exit;}
if($argv[1]=="--delete"){deletesingle($argv[2]);exit;}
if($argv[1]=="--synchronize"){synchronize();exit;}
if($argv[1]=="--sync"){synchronize();exit;}
if($argv[1]=="--sync-all"){synchronize();exit;}
if($argv[1]=="--erase"){erase($argv[2]);}
if($argv[1]=="--sym"){PrepareSymblinkLink($argv[2]);}


function proxy_options():string{
    $unix=new unix();
    $ini=new Bs_IniHandler();
    $datas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxySettings");
    if(trim($datas)<>null){
        $ini->loadString($datas);
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerEnabled"])){$ini->_params["PROXY"]["ArticaProxyServerEnabled"]=0;}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerName"])){$ini->_params["PROXY"]["ArticaProxyServerName"]="";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerPort"])){$ini->_params["PROXY"]["ArticaProxyServerPort"]="3128";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerUsername"])){$ini->_params["PROXY"]["ArticaProxyServerUsername"]="";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerUserPassword"])){$ini->_params["PROXY"]["ArticaProxyServerUserPassword"]="";}


        $ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
        $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
        $ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
        $ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
        $ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];

    }

    $userPP=null;
    if($ArticaProxyServerEnabled==1){
        if($ArticaProxyServerUsername<>null){
            $userPP="$ArticaProxyServerUsername:$ArticaProxyServerUserPassword@";
        }
       return "--proxy $userPP@$ArticaProxyServerName:$ArticaProxyServerPort";
    }
    $squidbin=$unix->LOCATE_SQUID_BIN();
    if(!is_file($squidbin)){return "";}
    if(!is_file("/etc/init.d/squid")){return "";}
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0) {return "";}
    $SquidMgrListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
    return "--proxy 127.0.0.1:$SquidMgrListenPort";

}

function WebCopyDir():string{
    $WebCopyDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebCopyDir"));
    if(strlen($WebCopyDir)<4){$WebCopyDir="/home/artica/WebCopy";}
    return $WebCopyDir;
}

function synchronize_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"webcopy.synchronize.progress");
}

function synchronize():bool{

    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
        synchronize_progress(110,"{already_running}");
        return false;
    }

    if(is_file($pidfile)){@unlink($pidfile);}
    @file_put_contents($pidfile,$pid);
    patch_rsyslog_webcopy();

    $WebCopyDir=WebCopyDir();
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    patch_table();
    $sql="SELECT ID,enabled,actiondel,enforceuri FROM httrack_sites";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){
        xsyslog("[service:0]: SQL Error L.".__LINE__." $q->mysql_error");
    }
    $c=50;
    synchronize_progress($c,"{scanning}");

    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $enforceuri=$ligne["enforceuri"];
        $c++;
        if($c>95){$c=95;}
        synchronize_progress($c,"{scanning} $enforceuri");
        $enabled=intval($ligne["enabled"]);
        $AIVABLE[$ID]=true;
        $actiondel=$ligne["actiondel"];
        if($enabled==0) {continue;}
        if($actiondel==1){deletesingle($ID);continue;}
        $workingdir="$WebCopyDir/$ID";
        if(!is_dir($workingdir)){continue;}
        $dirsize=$unix->DIRSIZE_BYTES_NOCACHE($workingdir);
        if($dirsize>10) {
            $q->QUERY_SQL("UPDATE httrack_sites SET size='$dirsize' WHERE ID=$ID");
        }

    }
    if (!$handle = opendir($WebCopyDir)) {return true;}

    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if(!is_numeric($filename)){continue;}
        $filename=intval($filename);
        $c++;
        if($c>95){$c=95;}
        $targetDir="$WebCopyDir/$filename";
        if(!is_dir($targetDir)){continue;}
        synchronize_progress($c,"{scanning} $filename");
        if(!isset($AIVABLE[$filename])){
            xsyslog("[service:$filename]: Sync $filename ($targetDir) is not in list, remove it...");
            httrack_remove($filename);
        }
    }
    synchronize_progress(100,"{scanning} {done}");
    return true;
}

function xsyslog($text):bool{
    echo $text."\n";
    if(function_exists("openlog")){openlog("WebCopy", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog(LOG_INFO, $text);}
    if(function_exists("closelog")){closelog();}
    return true;
}
function patch_rsyslog_webcopy(){
    if(is_file("/etc/rsyslog.d/webcopy.conf")){
        @unlink("/etc/rsyslog.d/webcopy.conf");
    }
    $syslogfile="/etc/rsyslog.d/01_webcopy.conf";
    $syslogfile_md5=@crc32_file($syslogfile);
    $action_file=buildlocalsyslogfile("/var/log/nginx/webcopy.log");

    $f[]="if  (\$programname =='WebCopy') then {";

    $SendAllConf=BuildRemoteSyslogs("artica-status");
    if($SendAllConf<>null){
        $f[]=$SendAllConf;
    }
    $SendConf=BuildRemoteSyslogs("webcopy");
    if($SendConf<>null){
        $f[]=$SendConf;
    }

    $f[]=$action_file;
    $f[]="\t&stop";
    $f[]="}\n";

    @file_put_contents($syslogfile,@implode("\n",$f));
    $syslogfile_md52=@crc32_file($syslogfile);

    if($syslogfile_md52<>$syslogfile_md5) {
        echo "Creating rsyslod configuration file\n";
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }
}

function httrack_install($ID):bool{
   $cron_file="/etc/cron.d/webcopy-$ID";
    $md51=md5_file($cron_file);
    $f1=basename(__FILE__);
    $q      = new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $ligne=$q->mysqli_fetch_array("SELECT schedule FROM httrack_sites WHERE ID=$ID");
    $httrack_schedule=intval($ligne["httrack_schedule"]);
    $unix=new unix();
    if($httrack_schedule==0){$httrack_schedule=6;}
    $unix->Popuplate_cron_make("webcopy-$ID","15 */$httrack_schedule * * *","$f1 --single $ID");
    $md52=md5_file($cron_file);
    if($md51<>$md52){
        UNIX_RESTART_CRON();
    }
    return true;
}
function httrack_remove($ID):bool{
    $ID=intval($ID);
    if($ID==0){return true;}
    xsyslog("[service:$ID]: Ask to remove $ID...");
    $cron_file="/etc/cron.d/webcopy-$ID";
    if(!is_file($cron_file)){
        return httrack_remove_path($ID);
    }
    xsyslog("[service:$ID]: Remove $cron_file");
    @unlink($cron_file);
    UNIX_RESTART_CRON();
    return httrack_remove_path($ID);
}
function httrack_remove_path($ID):bool{
    $ID=intval($ID);
    if($ID==0){return true;}
    $WebCopyDir=WebCopyDir();
    $workingdir="$WebCopyDir/$ID";
    if(!is_dir($workingdir)){return true;}
    $unix=new unix();
    $rm=$unix->find_program("rm");
    xsyslog("[service:$ID]: Remove $workingdir");
    shell_exec("$rm -rf $workingdir");
    xsyslog("[service:$ID]: Removed directory $workingdir");
    return true;
}
function erase($ID){
    xsyslog("[service:$ID]: Remove WebCopy content ID:$ID...");
    progress_runsingle(10,"{removing}...",$ID);
    httrack_remove($ID);
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $q->QUERY_SQL("UPDATE httrack_sites SET size='0', lasttime='0',notfound='',downloaded_status='' WHERE ID=$ID");
    progress_runsingle(100,"{success}...",$ID);
}
function deletesingle($ID):bool{
    xsyslog("[service:$ID]: Remove WebCopy feature ID:$ID...");
    httrack_remove($ID);
    $sql="DELETE FROM httrack_sites WHERE ID=$ID";
    xsyslog("[service:$ID]: Remove $ID from database");
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        xsyslog("[service:$ID]: SQL Error $q->mysql_error");
    }
    return true;
}
function progress_runsingle($prc,$text,$ID){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"webcopy-$ID.progress","webcopy-$ID.log");
}
function patch_table(){
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");

    if(!$q->FIELD_EXISTS("httrack_sites","UserAgent")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD UserAgent TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","HostHeader")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD HostHeader TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","AddHeader")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD AddHeader TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","exclude")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD exclude TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","enforceuri")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD enforceuri TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","lasttime")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD lasttime INTEGER NOT NULL DEFAULT '0'");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","useproxy")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD useproxy INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","maxextern")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD maxextern INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","actiondel")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD actiondel INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","notfound")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD notfound TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("httrack_sites","downloaded_status")){
        $q->QUERY_SQL("ALTER TABLE httrack_sites ADD downloaded_status TEXT NULL");
    }
}
function runsingle($ID):bool{
    $unix=new unix();
    $opts_proxy=null;
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".$ID.pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";
        progress_runsingle(110,"{already_process_exists_try_later}",$ID);
        return false;
    }
    $pidtime=$unix->file_time_min($pidfile);
    if(!$GLOBALS["FORCE"]) {
        if ($pidtime < 2) {
            echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} Running only each 2mn\n";
            progress_runsingle(110,"{already_process_exists_try_later}",$ID);
            return false;
        }
    }

    progress_runsingle(10,"{starting}",$ID);
    patch_rsyslog_webcopy();
    $cron_file="/etc/cron.d/webcopy-sync";
    if(!is_file($cron_file)){
        $unix->Popuplate_cron_make("webcopy-sync","30 */2 * * *","exec.httptrack.php --sync");
        UNIX_RESTART_CRON();
    }
    $php=$unix->LOCATE_PHP5_BIN();
    $syslogfile="/etc/rsyslog.d/webcopy.conf";
    if(!is_file($syslogfile)){ shell_exec("$php /usr/share/artica-postfix/exec.nginx.php --syslog"); }


    if(is_file($pidfile)){@unlink($pidfile);}
    @file_put_contents($pidfile, getmypid());

    xsyslog("[service:$ID]: Running WebCopy feature...");
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    patch_table();


    $WebCopyDir=WebCopyDir();
    $workingdir="$WebCopyDir/$ID";
    if(!is_dir($workingdir)){@mkdir($workingdir,0755,true);}
    $httrack=$unix->find_program("httrack");
    $dirsizeG=0;
    $t=time();

    $APACHE_USERNAME=$unix->APACHE_SRC_ACCOUNT();
    $APACHE_SRC_GROUP=$unix->APACHE_SRC_GROUP();

    $WebCopy=$q->mysqli_fetch_array("SELECT * FROM httrack_sites WHERE ID=$ID");
    $enabled=$WebCopy["enabled"];
    $actiondel=intval($WebCopy["actiondel"]);
    if($actiondel==1){
        progress_runsingle(110,"{removed}",$ID);
        deletesingle($ID);
        xsyslog("[service:$ID]: WebCopy stamped to be removed, Uninstalling");
        return false;
    }
    if($enabled==0){
        progress_runsingle(110,"{disabled}",$ID);
        xsyslog("[service:$ID]: WebCopy Disabled");
        return false;
    }
    httrack_install($ID);

    $useproxy=intval($WebCopy["useproxy"]);
    if($useproxy==1) {
        $opts_proxy = proxy_options();
    }
    $HostHeader=$WebCopy["HostHeader"];
    $UserAgent=$WebCopy["UserAgent"];
    if($UserAgent==null){$UserAgent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.45 Safari/537.36";}
    if(strlen(trim($WebCopy["HostHeader"]))>3) {
        $HostHeader = trim($WebCopy["HostHeader"]);
    }

    $AddHeader=trim($WebCopy["AddHeader"]);
    $minrate=$WebCopy["minrate"];
    $maxfilesize=$WebCopy["maxfilesize"];
    $maxsitesize=$WebCopy["maxsitesize"];
    $enforceuri=trim($WebCopy["enforceuri"]);
    $exclude=trim($WebCopy["exclude"]);
    if($exclude==null){$exclude="*.gz,*.zip,*.exe,*.iso,*.nrg,*.pdf";}
    $size=$WebCopy["size"];
    $maxworkingdir=intval($WebCopy["maxworkingdir"]);
    $maxextern=intval($WebCopy["maxextern"]);

    $excludes=explode(",",$exclude);
    foreach ($excludes as $pattern){
        $exc[]="-/$pattern";
    }

    $sizeKB=$size/1024;

    $sizeMB=round($sizeKB/1024,2);

    if($maxworkingdir==0){$maxworkingdir=20;}
    $maxsitesizeMB=$maxsitesize/1000;

    if($maxsitesizeMB>$maxworkingdir){
        $maxsitesize=$maxworkingdir*1000;
    }

    if($maxsitesizeMB>$maxworkingdir){
        $maxsitesize=$maxworkingdir*1000;
    }

    if($sizeMB>$maxworkingdir){
        xsyslog("[service:$ID]: Skip downloading content Directory: {$sizeMB}MB reach limit of {$maxworkingdir}MB");
        return false;
    }

    $ResteMB=$maxworkingdir-$sizeMB;
    $ResteKB=$ResteMB*1000;


    if($maxsitesize>$ResteKB){
        $maxsitesize=$ResteKB;
    }
    $address=trim($WebCopy["enforceuri"]);
    if(strlen($address)<8){xsyslog("[service:$ID]: No target address [$address], aborting");return false;}

    if($enforceuri<>null){
        if(preg_match("#^(.+?)\[(.+?)\]#",$enforceuri,$re)){
            $address=$re[1];
            $HostHeader=$re[2];
        }else{
            $address=$enforceuri;
            $HostHeader=null;
        }
    }

    $opts[]="$httrack $address";
    $opts[]=@implode(" ",$exc);
    $opts[]="--quiet --keep-alive --robots=0";
    echo "Dir: Max Downloads:$maxsitesize KB\n";

    $maxfilesize=$maxfilesize*1000;
    $maxsitesize=$maxsitesize*1000;
    $minrate=$minrate*1000;
    $resultsCMD=array();
    echo "Dir: Max Downloads:$maxsitesize Bytes\n";
    echo "Checking $workingdir/hts-cache/doit.log";
    if(is_file("$workingdir/hts-cache/doit.log")){
        $opts[]="--update";
        echo "- OK FOUND\n";
    }else{
        echo "- NOT FOUND\n";
    }


    $opts[]="-F \"$UserAgent\"";


    if($HostHeader<>null) {
        $opts[] = "--headers \"Host: $HostHeader\"";
    }
    if($AddHeader<>null){
        $AddHeader=str_replace('"',"'",$AddHeader);
        $opts[]="--headers \"$AddHeader\"";
    }


    if($opts_proxy<>null){
        $opts[]=$opts_proxy;
        xsyslog("[service:$ID]: Using a remote proxy...");
    }

    if($maxextern>0){
        $opts[]="--ext-depth=$maxextern";
    }
    $tempfile=$unix->FILE_TEMP();
    $opts[]="--max-files=$maxfilesize --max-size=$maxsitesize --max-rate=$minrate";
    $opts[]="-o0 --path $workingdir";
    $opts[]=" >$tempfile 2>&1";
    $cmdline=@implode(" ",$opts);
    xsyslog("[service:$ID]: Starting downloading content of $address/$minrate/".$unix->FormatBytes($maxsitesize/1000));

    $tail=$unix->find_program("tail");
    $t1=time();
    $logfile="$workingdir/hts-log.txt";
    if($GLOBALS["VERBOSE"]){echo"$cmdline\n";}
    $c=20;
    progress_runsingle($c,"{running}",$ID);

    $sh=$unix->sh_command($cmdline);
    $unix->go_exec($sh);
    sleep(1);
    $httrack_pid=httrack_pid($ID);

    while (true) {
        if(!$unix->process_exists($httrack_pid)){
            break;
        }
        $minutes=$unix->PROCESS_TTL_TEXT($httrack_pid);
        $c++;
        if($c>95){$c=95;}
        sleep(3);
        progress_runsingle($c,$minutes,$ID);
        $httrack_pid=httrack_pid($ID);
    }


    $resultsCMD=@explode("\n",@file_get_contents($tempfile));
    foreach ($resultsCMD as $line){
        if($GLOBALS["VERBOSE"]){xsyslog("[service:$ID]: CMD $line");}
    }
    $CHANGE_NGINX=true;
    xsyslog("[service:$ID]: $address done took ". $unix->distanceOfTimeInWords_text($t1,time()));
    $NOTFOUND=array();
    if(is_file($logfile)){
        $tp=explode("\n",@file_get_contents($logfile));
        foreach ($tp as $line){
            if(preg_match("#No data seems to have been transferred during this session#")){
                xsyslog("[service:$ID]: Success: No change");
                $CHANGE_NGINX=false;
                continue;
            }

            if(preg_match("#Error:\s+.*?\"Not Found.*?link\s+(.+?)\s+\(#",$line,$re)){
                $NOTFOUND[trim($re[1])]=true;
            }

            if(preg_match("#^[0-9:]+\s+(Warning|Error):\s+(.+)#",$line,$re)){
                xsyslog("[service:$ID]: Event {$re[1]}: {$re[2]}");
                continue;
            }

            if(preg_match("#mirror complete in\s+(.+)#i",$line,$re)){
                xsyslog("[service:$ID]: Success mirror complete in {$re[1]}");
                continue;
            }

        }
    }
    $dirsize=$unix->DIRSIZE_BYTES_NOCACHE($workingdir);
    $took=$unix->distanceOfTimeInWords($t,time());
    $dirsizeText=$unix->FormatBytes($dirsize/1024);
    $notfound=base64_encode(serialize($NOTFOUND));

    $downloaded_size=0;
    $downloaded_files=0;
    $new_file="$workingdir/hts-cache/new.txt";
    if(is_file($new_file)){
        $tr=explode("\n",@file_get_contents($new_file));
        foreach ($tr as $line){
            if(!preg_match("#^[0-9:]+\s+([0-9]+)\/[0-9]+\s+.*?\s+200#",$line,$re)){continue;}
            $downloaded_files++;
            $downloaded_size=$downloaded_size+intval($re[1]);

        }
    }


    $downloaded_status="{last_scan} $downloaded_files {files} ".$unix->FormatBytes($downloaded_size/1024);
    xsyslog("[service:$ID]: Dir: Current size:$dirsize Bytes");
    xsyslog("[service:$ID]: New size....:{$dirsizeText}");
    xsyslog("[service:$ID]: $address($ID) scrapped took $took size=$dirsizeText");
    $lasttime=time();

    $q->QUERY_SQL("UPDATE httrack_sites SET size='$dirsize', 
                         lasttime='$lasttime',notfound='$notfound',
                         downloaded_status='$downloaded_status'
                     WHERE ID=$ID");

    PrepareSymblinkLink($ID);
    if(!$q->ok){xsyslog($q->mysql_error);}
    @chmod($workingdir,0755);
    @chmod(dirname($workingdir),0755);
    $chown=$unix->find_program("chown");
    shell_exec("$chown -R $APACHE_USERNAME:$APACHE_SRC_GROUP $workingdir");
    @file_put_contents("$workingdir/EXEC",time());
    progress_runsingle(100,"{success} $downloaded_status",$ID);
    return true;

}
function PrepareSymblinkLink($ID){
    $unix=new unix();
    $ln=$unix->find_program("ln");
    $webcpy=new webcopy($ID);
    $workingdir=$webcpy->WebCopySrcDir();
    $DestDir=$webcpy->WebCopyDir();
    $filehost=$webcpy->WebCopyOriginalHostName();
    echo "Scanning $workingdir ( $filehost )\n";
    if(!is_dir($workingdir)){return false;}
    if (!$handle = opendir($workingdir)) {return false;}
    while (false !== ($file = readdir($handle))) {
        if ($file == "." OR $file == "..") {continue;}
        if(substr($file, 0,1)=='.'){continue;}
        if($file=="hts-cache"){continue;}
        $fullpath="$workingdir/$file";
        if(!is_dir($fullpath)){continue;}
        if($filehost==$file){continue;}
        $Destination="$DestDir/$file";
        if(is_link($Destination)){
            $LinkedDestination=readlink($Destination);
            if($LinkedDestination==$fullpath){continue;}
        }
        xsyslog("[service:$ID]: Symlink $fullpath to $Destination");
        shell_exec("$ln -sf $fullpath $Destination");
    }
    return true;

}


function httrack_pid($ID):int{
    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    exec("$pgrep -f -l \"in/httrack.*?--max-files.*?--path.*?/$ID\" 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^[0-9]+\s+.*?pgrep#",$line)){continue;}
        if(preg_match("#^([0-9]+)\s+.*?httrack#",$line,$re)){return intval($re[1]);}
    }
    return 0;
}

function is_regex($pattern):bool{
    $f[]="{";
    $f[]="[";
    $f[]="+";
    $f[]="\\";
    $f[]="?";
    $f[]="$";
    $f[]=".*";

    foreach ($f as $val){
        if(strpos(" $pattern", $val)>0){return true;}
    }
    return false;
}