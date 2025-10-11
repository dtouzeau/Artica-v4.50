<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

if(isset($argv[1])){
    if($argv[1]=="--remove"){clean_video($argv[2]);exit;}
}

scan();

function build_progress($pourc,$text){
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy-videos.progress", serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/squid.rdpproxy-videos.progress",0755);
}
function MoveBranch(){
    $unix=new unix();
    $RDPProxyVideoPathConf=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPath");
    $cp=$unix->find_program("cp");
    $rm=$unix->find_program("rm");
    $ln=$unix->find_program("ln");
    if(!is_dir($RDPProxyVideoPathConf)){@mkdir($RDPProxyVideoPathConf,0755,true);}
    if(!is_dir($RDPProxyVideoPathConf)){return false;}
    shell_exec("$cp -rfd /home/artica/rds/videos/* $RDPProxyVideoPathConf/");
    shell_exec("$rm -rf /home/artica/rds/videos");
    shell_exec("$ln -sf $RDPProxyVideoPathConf /home/artica/rds/videos");
    $link=readlink("/home/artica/rds/videos");
    if($link==$RDPProxyVideoPathConf){return true;}
    return false;

}

function scan(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $zip=$unix->find_program("zip");

    $RDPProxyVideoPathConf=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPath");
    if($RDPProxyVideoPathConf==null){$RDPProxyVideoPathConf="/home/artica/rds/videos";}
    $RDPProxyVideoPath="/home/artica/rds/videos";
    if(is_link($RDPProxyVideoPath)){$RDPProxyVideoPath=@readlink($RDPProxyVideoPath);}

    if($RDPProxyVideoPathConf<>$RDPProxyVideoPath){
        if(!MoveBranch()){
            squid_admin_mysql(1,"Warning, unable to move files from $RDPProxyVideoPath to $RDPProxyVideoPathConf",
                null,__FILE__,__LINE__);
            build_progress(110,"Warning, unable to move files");
            return;
        }
    }

    if(!is_file($zip)){
        build_progress(20,"{installing} ZIP");
        $unix->DEBIAN_INSTALL_PACKAGE("zip");
        sleep(5);
        if(!is_file("/usr/bin/zip")) {
            squid_admin_mysql(1, "Warning, unable to found zip program", null, __FILE__, __LINE__);
            build_progress(110, "Warning, unable to found zip program");
            return;
        }
    }

    if(!create_table()){
        echo "Create infrastructure tables failed\n";
        build_progress(110,"Create infrastructure tables failed");
        return false;
    }
    build_progress(20,"{cleaning}");
    clean_videos();

    $Workdir="/var/rdpproxy/recorded/rdp";
    build_progress(50,"{scanning}");
    if(!is_dir($Workdir)){@mkdir($Workdir,0755,true);}

    if (!$handle = opendir($Workdir)) {return false;}
    while (false !== ($file = readdir($handle))) {
        if($file=="."){continue;}
        if($file==".."){continue;}
        if(is_file($file)){
            @unlink("$Workdir/$file");
            continue;
        }
        $subdir="$Workdir/$file";
        if(!is_dir($subdir)){continue;}
        if(!preg_match("#^[0-9]+$#",$file)){continue;}
        echo "Scanning $subdir\n";
        if(!subscan($subdir)){continue;}
        shell_exec("$rm -rf $subdir");

    }

    build_progress(100,"{scanning} {success}");

}

function clean_video($ID){
    $RDPProxyVideoPathConf=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPath");
    if($RDPProxyVideoPathConf==null){$RDPProxyVideoPathConf="/home/artica/rds/videos";}
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM videos WHERE ID=$ID");
    $path = $ligne["path"];
    $fileDel="$RDPProxyVideoPathConf/$path";
    if(is_file($fileDel)){
        @unlink($fileDel);
    }
    $q->QUERY_SQL("DELETE FROM videos WHERE ID=$ID");
}

function clean_videos(){
    $RDPProxyVideoPathConf=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPath");
    if($RDPProxyVideoPathConf==null){$RDPProxyVideoPathConf="/home/artica/rds/videos";}
    $RDPProxyVideoRententionDays = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoRententionDays"));
    if($RDPProxyVideoRententionDays==0){$RDPProxyVideoRententionDays=365;}
    $dateToDelete = strtotime("-{$RDPProxyVideoRententionDays} day");
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $results=$q->QUERY_SQL("SELECT * FROM videos WHERE connection_date<$dateToDelete ORDER BY connection_date DESC");
    foreach ($results as $index=>$ligne) {
        $path = $ligne["path"];
        $ID = $ligne["ID"];
        $fileDel="$RDPProxyVideoPathConf/$path";
        @unlink($fileDel);
        $q->QUERY_SQL("DELETE FROM videos WHERE ID=$ID");
    }
    $minsDel=$RDPProxyVideoRententionDays*1440;
    if (!$handle = opendir($RDPProxyVideoPathConf)) {return false;}
    while (false !== ($file = readdir($handle))) {
        if($file=="."){continue;}
        if($file==".."){continue;}
        $target="$RDPProxyVideoPathConf/$file";
        if(is_dir($target)){
            echo "$target is a directory, skip it\n";
            continue;
        }
        $diff=$unix->file_time_min($target);

        if($diff>$minsDel){
            echo "Remove $target ($diff remove: {$RDPProxyVideoRententionDays} days)\n";
            $q->QUERY_SQL("DELETE FROM videos WHERE path='$file'");
            @unlink($target);
        }else{
            echo "$target: SKIP\n";
        }
    }
}

function subscan($directory){
    $logfile=null;
    $starttime=basename($directory);
    $mp4s=array();
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $zip=$unix->find_program("zip");
    $RDPProxyVideoPathConf=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("RDPProxyVideoPath");
    if($RDPProxyVideoPathConf==null){$RDPProxyVideoPathConf="/home/artica/rds/videos";}
    $RDPProxyVideoPath="/home/artica/rds/videos";
    if($RDPProxyVideoPathConf<>$RDPProxyVideoPath)

    if(is_link($RDPProxyVideoPath)){$RDPProxyVideoPath=@readlink($RDPProxyVideoPath);}
    if(!is_dir($RDPProxyVideoPath)){@mkdir($RDPProxyVideoPath,0755,true);}

    if (!$handle = opendir($directory)) {return false;}
    while (false !== ($file = readdir($handle))) {
        if($file=="."){continue;}
        if($file==".."){continue;}
        $target="$directory/$file";
        if(is_dir($target)){continue;}
        $files[]=$target;
        if(preg_match("#\.mp4$#",$file)){
            $mp4s[]=$target;
            continue;
        }
        if(preg_match("#\.log$#",$file)){
            $logfile="$directory/$file";
            continue;
        }
    }

    if($logfile==null){return false;}
    $duration=subscan_terminated($logfile);
    if(count($mp4s)==0){
        echo "Unable to found mp4 files...\n";
        return false;
    }

    if($duration==null){return false;}
    echo "Started: ".date("Y-m-d H:i:s",$starttime)."\n";
    echo "Duration: $duration\n";

    $parse=trim(str_replace(".log","",basename($logfile)));
    echo "Parsed: $parse\n";
    $TB=explode("-",$parse);
    $ruleid=$TB[0];
    $targetid=$TB[1];
    $userid=$TB[2];
    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $ligne=$q->mysqli_fetch_array("SELECT username FROM members WHERE ID='$userid'");
    $username=$ligne["username"];
    $ligne=$q->mysqli_fetch_array("SELECT alias,target_host,target_device FROM targets WHERE ID='$targetid'");

    if($ligne["target_host"]<>null){$tname[]=$ligne["target_host"];}
    if($ligne["target_device"]<>null){$tname[]=$ligne["target_device"];}
    if($ligne["alias"]<>null){$tname[]="({$ligne["alias"]})";}
    $hostname=@implode(" ",$tname);
    $ligne=$q->mysqli_fetch_array("SELECT groupname FROM groups WHERE ID='$ruleid'");
    $rule_name=$ligne["groupname"];

    echo "Rule: ($ruleid) $rule_name \n";
    echo "User: ($userid) $username \n";
    echo "Comp: ($targetid) $hostname \n";

    $ZipFilename=md5("$rule_name$username$hostname$starttime").".zip";

    echo "Compressing ".@implode(" ",$mp4s)."\n";
    shell_exec("$zip -9 -j $RDPProxyVideoPath/$ZipFilename ".@implode(" ",$mp4s));

    exec("$zip -T $RDPProxyVideoPath/$ZipFilename 2>&1",$zipresults);
    $ZIPINTEGRITY=false;
    foreach ($zipresults as $line){
        $line=trim($line);
        if(preg_match("#\s+OK$#",$line)){
            $ZIPINTEGRITY=true;
        }

    }

    if(!$ZIPINTEGRITY){
        echo "Compressing integrity failed\n";
        @unlink("$RDPProxyVideoPath/$ZipFilename");
        squid_admin_mysql(1,"Failed to compress video files ZIP itegrity return false",@implode("\n",$zipresults),__FILE__,__LINE__);
        return false;
    }
    $psize=@filesize("$RDPProxyVideoPath/$ZipFilename");
    $rule_name=$q->sqlite_escape_string2($rule_name);
    $sql="INSERT INTO `videos`(connection_date,rule_name,rule_id,username,userid,hostname,hostid,duration,psize,path) VALUES 
    ('$starttime','$rule_name','$ruleid','$username','$userid','$hostname','$targetid','$duration','$psize','$ZipFilename')";
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        @unlink("$RDPProxyVideoPath/$ZipFilename");
        echo $q->mysql_error."\n";
        squid_admin_mysql(0,"SQLite Error while inserting videos",$q->mysql_error,__FILE__,__LINE__);
        return false;
    }

    return true;


}
function subscan_terminated($lofile){
    if(!is_file($lofile)){return null;}

    $f=explode("\n",@file_get_contents($lofile));
    foreach ($f as $line){

        if(preg_match("#type=.*?SESSION_DISCONNECTION.*?duration=\"(.*?)\"#i",$line,$re)){
            return $re[1];
        }

    }

    return null;


}


function create_table(){
    $unix=new unix();
    if(!is_file("/etc/cron.d/rdpproxy-videos")){
        $EnableRDPProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableRDPProxy"));
        if($EnableRDPProxy==1){
            $unix->Popuplate_cron_make("rdpproxy-videos","*/5 * * * *","exec.rdpproxy.videos.php");
            shell_exec("/etc/init.d/cron reload");
        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/rdpproxy.db");
    $sql="CREATE TABLE IF NOT EXISTS `videos` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
             `connection_date` INTEGER,
             `rule_name` TEXT,
              `rule_id` INTEGER,
              `username` TEXT,
              `userid` INTEGER,
              `hostname` TEXT,
              `hostid` INTEGER,
              `duration` TEXT,
              `psize` INTEGER,
              `path` TEXT)";

    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";return false;}
    return true;

}