<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.ccurl.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

rpz();


function rpz(){
    $PowerDNSEnableRecursor=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableRecursor"));
    if($PowerDNSEnableRecursor==0){die();}
    $unix=new unix();
    $rpzpath="/etc/powerdns/rpz";
    if(!is_dir($rpzpath)){
        @mkdir($rpzpath,0755,true);
    }

    $q=new lib_sqlite("/home/artica/SQLITE/rpz.db");

    if(!$q->FIELD_EXISTS("policies","status")){
        $q->QUERY_SQL("ALTER TABLE policies ADD `status` INTEGER NOT NULL DEFAULT '0'");
    }


    $sql="SELECT * FROM policies WHERE enabled=1 AND rpztype=1 ORDER BY zOrder";
    $results=$q->QUERY_SQL($sql);
    $f=array();
    $c=0;
    foreach ($results as $index=>$ligne) {
        $ID = $ligne["ID"];
        $rpzname = $ligne["rpzname"];
        $rpzurl = $ligne["rpzurl"];
        $rpzdb = "$rpzpath/$ID.conf";
        $lastsaved = intval($ligne["lastsaved"]);
        $distance = $unix->time_min($lastsaved);
        if(!is_file($rpzdb)){$distance=10000;}
        if(!$GLOBALS["VERBOSE"]) {
            if ($distance < 120) {
                continue;
            }
        }
        $remote_time=fileTime($rpzurl);
        if($remote_time>0){
            if($remote_time<=$lastsaved){continue;}
        }
        $tmpfile=$unix->FILE_TEMP();
        $curl=new ccurl($rpzurl);
        if(!$curl->GetFile($tmpfile)){
            $q->QUERY_SQL("UPDATE policies SET status=10 WHERE ID=$ID");
            _out("$rpzname: Failed to dowload with error $curl->error");
            continue;
        }
        if(is_file($rpzdb)){@unlink($rpzdb);}
        @copy($tmpfile,$rpzdb);
        @unlink($tmpfile);
        $lastsaved=time();
        $lines=$unix->COUNT_LINES_OF_FILE($rpzdb);
        _out("RPZ: Success updating $rpzname with $lines elements");
        $q->QUERY_SQL("UPDATE policies SET lastsaved=$lastsaved, items=$lines,status=1 WHERE ID=$ID");
    }


}
function fileTime($url){
    $curl=new ccurl($url);
    $Infos=$curl->getHeaders(3);
    if($GLOBALS["VERBOSE"]){print_r($Infos);}
    $filetime=0;
    if(isset($Infos["filetime"])) {
        $filetime = $Infos["filetime"];
    }
    return $filetime;
}


function _out($text){
    $GLOBALS["TITLENAME"]="PowerDNS Recursor";
    echo "Service.......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("pdns_recursor", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}