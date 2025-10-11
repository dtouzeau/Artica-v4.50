<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
$GLOBALS["GENPROGGNAME"]        = "ksrn-stats.progress";
$GLOBALS["CLASS_SOCKETS"]       = new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

if(isset($argv[1])) {
    if ($argv[1] == "--white") {
        ksrn_white();
        exit;
    }
    if ($argv[1] == "--line") {
        stats_line();
        exit;
    }
    if ($argv[1] == "--patterns") {
        ksrn_patterns();
        exit;
    }
}

xstart();

function xstart(){
    $unix        = new unix();
    $fam         = new squid_familysite();
    $q           = new postgres_sql();
    $BaseWorkDir ="/var/log/squid";



    if (!is_file("/etc/cron.d/ksrn-patterns")) {
        $GLOBALS["CLASS_SOCKETS"]->build_progress(46, "{installing}");
        $unix->Popuplate_cron_make("ksrn-patterns", "*/2 * * * *", "exec.ksrn.statistics.php --patterns");
        UNIX_RESTART_CRON();
    }

    if (!is_file("/etc/cron.d/ksrn-stats")) {
        $GLOBALS["CLASS_SOCKETS"]->build_progress(47, "{installing}");
        $unix->Popuplate_cron_make("ksrn-stats", "*/10 * * * *", "exec.ksrn.statistics.php");
        UNIX_RESTART_CRON();
    }
    if (!is_file("/etc/cron.d/ksrn-status")) {
        $GLOBALS["CLASS_SOCKETS"]->build_progress(48, "{installing}");
        $unix->Popuplate_cron_make("ksrn-status", "* * * * *", "exec.ksrn.php --status");
        UNIX_RESTART_CRON();
    }

    if(!$q->CREATE_KSRN()){
        $unix->_syslog("[ERROR]: FATAL: STAS: $q->mysql_error","ksrn");
        echo "$q->mysql_error\n";
        $GLOBALS["CLASS_SOCKETS"]->build_progress(110, "{synchronize} {failed}");
        return false;
    }
    ksrn_patterns();
    stats_line();

    $prefix="INSERT INTO ksrn (zdate,username,ipaddr,mac,siteid,category,provider,duration ) ";
    $handle = opendir($BaseWorkDir);
    if($handle){
        while (false !== ($filename = readdir($handle))) {
            if($filename=="."){continue;}
            if($filename==".."){continue;}
            $ftime          = 0;
            $targetFile     = "$BaseWorkDir/$filename";
            if(is_dir($targetFile)){continue;}
            if(!preg_match("#\.ksrn$#", $filename)){continue;}


            if(!$GLOBALS["FORCE"]) {
                $ftime=$unix->file_time_min($targetFile);
                echo "$targetFile {$ftime}mn\n";
                if ($ftime < 10) {
                    continue;
                }
            }

            $IpClass=new IP();
            $GLOBALS["CLASS_SOCKETS"]->build_progress(50, "{scanning} $filename");
            $f=explode("\n",@file_get_contents($targetFile));
            $sq=array();
            foreach ($f as $line){
                $zValues=explode("|",$line);
                if(count($zValues)<4){continue;}
                $zdate      = $zValues[0];
                $username   = $zValues[1];
                $ipaddr     = $zValues[2];
                $mac        = $zValues[3];
                $category   = $zValues[4];
                $family     = $fam->GetFamilySites($zValues[5]);
                $siteid     = get_siteid($zValues[5],$family,$category);
                $Provider   = strtoupper($zValues[6]);
                $ScanTime   = round($zValues[7]);
                if($siteid==0){continue;}
                if(!$IpClass->isValid($ipaddr) OR $ipaddr==null){$ipaddr="0.0.0.0";}
                if(!$IpClass->IsvalidMAC($mac) OR $mac==null){$mac="00:00:00:00:00:00";}

                if(strlen($username)>90){$username=substr($username,0,89);}
                if(!is_numeric($category)){
                    $unix->_syslog("[ERROR]: FATAL: $filename STAS: category is not a numeric","ksrn");
                    @unlink($targetFile);
                    continue;
                }
                $sq[]="('$zdate','$username','$ipaddr','$mac','$siteid','$category','$Provider','$ScanTime')";
            }
            if(count($sq)>0){
                $sql="$prefix VALUES ".@implode(",",$sq);
                $q->QUERY_SQL($sql);
                $q->mysql_error=str_replace("\n","#012", $q->mysql_error);
                if(!$q->ok){$unix->_syslog("[ERROR]: FATAL: $filename STAS: $q->mysql_error","ksrn");continue;}
                $unix->_syslog("[INFO]: STAS: $filename Injecting ".count($sq)." threats","ksrn");
                @unlink($targetFile);
            }
        }
    }

    $GLOBALS["CLASS_SOCKETS"]->build_progress(100, "{scanning} {success}");
    return true;

}
function ksrn_patterns(){
    $compile_root = "/home/artica/theshieldsdb";
    if(!is_dir($compile_root)){@mkdir($compile_root,0755,true);}
    $TheShieldsUseLocalCats=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TheShieldsUseLocalCats"));
    if (!$handle = opendir($compile_root)) {return false;}
    $patterns=0;
    $patterns_size=0;
    while (false !== ($file = readdir($handle))) {
        if ($file == ".") {continue;}
        if ($file == "..") {continue;}

        if(!preg_match("#^[0-9]+\.dbm$#",$file)){
            echo "Scanning $file no match\n";
            continue;}
        $ffile="$compile_root/$file";
        $size=@filesize($ffile);
        $patterns++;
        $patterns_size=$patterns_size+$size;
        if($TheShieldsUseLocalCats==0){@unlink($ffile);}

    }

    $FINAL=array($patterns,$patterns_size);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KSRN_PATTERNS",serialize($FINAL));
    return true;
}


function ksrn_white(){
    $unix       = new unix();
    $squid      = $unix->LOCATE_SQUID_BIN();
    $final      = array();
    $tempwhite  = "/etc/squid3/acl_dstdomain_tempwhite.conf";


    $first_md5=md5_file($tempwhite);

    $libmem         = new lib_memcached();
    $squidfam       = new squid_familysite();
    $q              = new postgres_sql();
    $keys           = $libmem->allKeys();
    $ip             = new IP();
    $sites          = array();
    $cleanedsites   = array();
    $ALREADY        = array();

    $MAINDOMAINS["googleapis.com"]=true;
    $MAINDOMAINS["googletagmanager.com"]=true;
    $MAINDOMAINS["doubleclick.net"]=true;
    $MAINDOMAINS["firefox.com"]=true;
    $MAINDOMAINS["lepetitvapoteur.com"]=true;
    $MAINDOMAINS["canal-u.tv"]=true;
    $MAINDOMAINS["linkedin.com"]=true;
    $MAINDOMAINS["dailymotion.com"]=true;
    $MAINDOMAINS["force.com"]=true;
    $MAINDOMAINS["salesforceliveagent.com"]=true;
    $MAINDOMAINS["salesforce.com"]=true;
    $MAINDOMAINS["sfdcstatic.com"]=true;
    $MAINDOMAINS["reseau-canope.fr"]=true;

    $sql="SELECT * FROM ksrn_white";
    $results=$q->QUERY_SQL($sql);
    while ($ligne = pg_fetch_assoc($results)) {
        $sitename=strtolower($ligne["sitename"]);
        if($ip->isValid($sitename)){continue;}
        if(preg_match("#^www\.(.+)#",$sitename,$re)){$sitename=$re[1];}
        if(isset($ALREADY[$sitename])){continue;}
        $famsite=strtolower($squidfam->GetFamilySites($sitename));
        if($famsite==$sitename){
            $MAINDOMAINS[$sitename]=true;
            continue;
        }
        $ALREADY[$sitename]=true;
        $sites[]=$sitename;

    }

    foreach ($keys as $key){
        if(preg_match("#^WHITEDOM:(.+)#",$key,$re)){
            $sitename=strtolower($re[1]);
            if(preg_match("#^www\.(.+)#",$sitename,$re)){$sitename=$re[1];}
            if(isset($ALREADY[$sitename])){continue;}
            $famsite=strtolower($squidfam->GetFamilySites($sitename));
            if($famsite==$sitename){
                $MAINDOMAINS[$sitename]=true;
                continue;
            }
            $sites[]=$sitename;

        }
    }

    foreach ($sites as $sitename){
        $famsite=$squidfam->GetFamilySites($sitename);
        if(isset($MAINDOMAINS[$famsite])){continue;}
        $cleanedsites[]=$sitename;
    }
    foreach ($MAINDOMAINS as $sitename=>$none){
        $final[]="$sitename";
    }
    foreach ($cleanedsites as $sitename){
        $final[]="$sitename";
    }

    @file_put_contents($tempwhite,@implode("\n",$final));
    @chown($tempwhite,"squid");
    @chgrp($tempwhite,"squid");
    $last_md5=md5_file($tempwhite);
    if($last_md5==$first_md5){return true;}
    $unix->ToSyslog("{reloading_proxy_service} in order to update The Shields whitelist acl","ksrn");
    system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    return true;
}

function get_siteid($sitename,$familysite=null,$category=0){

    if(trim($sitename)==null){return 0;}
    if(is_numeric($sitename)){return 0;}

    if(isset($GLOBALS["get_siteid"][$sitename])){
        return intval($GLOBALS["get_siteid"][$sitename]);
    }

    $q=new postgres_sql();
    $ligne=$q->mysqli_fetch_array("SELECT siteid from statscom_websites WHERE sitename='$sitename'");
    if(!$q->ok){echo $q->mysql_error."\n";}



    $siteid=intval($ligne["siteid"]);
    if($GLOBALS["DEBUG"]){echo "get_siteid(): statscom_websites $sitename == {$ligne["siteid"]}\n";}
    if($siteid==0) {
        if($familysite==null){return 0;}
        $time=time();
        $q->QUERY_SQL("INSERT INTO statscom_websites(sitename,familysite,category,lastseen) 
                                VALUES ('$sitename','$familysite','$category','$time')");
        if($GLOBALS["DEBUG"]){echo "statscom_websites $sitename,$familysite,$category ==> INSERT\n";}
        if(!$q->ok){echo $q->mysql_error."\n";}
        $ligne=$q->mysqli_fetch_array("SELECT siteid from statscom_websites WHERE sitename='$sitename'");
        $siteid=intval($ligne["siteid"]);

    }

    $GLOBALS["get_siteid"][$sitename]=intval($siteid);
    return intval($GLOBALS["get_siteid"][$sitename]);

}

function stats_line(){
    $unix=new unix();
    $lib=new lib_memcached();
    $q=new postgres_sql();
    if($GLOBALS["VERBOSE"]){echo "Parse All keys...\n";}
    $allkets=$lib->allKeys();
    if(!$lib->ok){
        if($lib->mysql_error=="NEED RESTART"){
            //squid_admin_mysql(1,"Restart memcache service for sync configuration",null,__FILE__,__LINE__);
            //shell_exec("/usr/sbin/artica-phpfpm-service -restart-memcached");

        }
        return false;
    }

    if($GLOBALS["VERBOSE"]){echo "Parse All keys done...\n";}
    foreach ($allkets as $key){
        if(!preg_match("#SRNSTATSLINE:(.+)#",$key,$re)){continue;}
        if($GLOBALS["VERBOSE"]){echo "Found Key = $key\n";}
        if(!preg_match("#^([0-9]{4})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})$#",$re[1],$ri)){
            if($GLOBALS["VERBOSE"]){echo "Key = $re[1] no matches dates\n";}
            $lib->Delkey($key);
            continue;
        }
        $year=$ri[1];
        $month=$ri[2];
        $day=$ri[3];
        $hour=$ri[4];
        $minutes=$ri[5];
        $stime=strtotime("$year-$month-$day $hour:$minutes:00");
        $distance=$unix->time_min($stime);
        if($distance<10){continue;}

        $data=intval($lib->getKey($key));
        if($GLOBALS["VERBOSE"]){echo "Found Key = $key Data = $data\n";}
        if($data==0){
            $lib->Delkey($key);
            continue;
        }
        $q->QUERY_SQL("INSERT INTO ksrn_lines (zdate,requests) VALUES('$year-$month-$day $hour:$minutes:00','$data')");
        if(!$q->ok){
            echo $q->mysql_error."\n";
            continue;
        }
        $lib->Delkey($key);

    }

}