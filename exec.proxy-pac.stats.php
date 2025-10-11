<?php
$GLOBALS["YESCGROUP"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.acls.useragents.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();


start();


function start(){

    $q=new postgres_sql();
    $date = new DateTime(date("Y-m-d H:i:s"));
    $Newdate=roundDownToMinuteInterval($date);
    $NotBeforeDate=$Newdate->format("Y-m-d H:i:s");
    $NotBeforeTime=strtotime($NotBeforeDate);
    echo "Not before $NotBeforeDate\n";
    $useragents=new useragents(false);
    if(!is_dir("/var/log/proxy-pac")){
        return true;
    }
    if (!$handle = opendir("/var/log/proxy-pac")) {return true;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if(!preg_match("#history_([0-9]+)\.log$#",$filename,$re)){continue;}
        $TimeSpace=$re[1];
        if(preg_match("#^([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})([0-9]{0,2})#",$TimeSpace,$re)){
            $newDate=date("Y")."-{$re[1]}-{$re[2]} {$re[3]}:{$re[4]}:00";
            $date = new DateTime($newDate);
            $zDateClass=roundUpToMinuteInterval($date);
            $TenMinutesDate=$zDateClass->format("Y-m-d H:i:s");
            $TenMinuteTime=strtotime($TenMinutesDate);
            if($GLOBALS["VERBOSE"]){echo "$TenMinutesDate Not before $NotBeforeDate\n";}
            if($TenMinuteTime>$NotBeforeTime){continue;}
            if(ScanFile($filename)){
                @unlink("/var/log/proxy-pac/$filename");
            }


        }


    }
    if(isset($GLOBALS["PACTIME"])) {
        foreach ($GLOBALS["PACTIME"] as $zDate => $array1) {
            if ($zDate == "USERAGENTS") {continue;}
            $CountOfClients = count($array1["CLIENTS"]);
            $Requests = $array1["RQS"];
            echo "$zDate $CountOfClients ($Requests)\n";
            $q->QUERY_SQL("INSERT INTO proxypac_stats (zdate,requests,clients) VALUES ('$zDate','$Requests','$CountOfClients')");
            if (!$q->ok) {
                echo $q->mysql_error . "\n";
                die();
            }
        }
    }

    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    $sql="CREATE TABLE IF NOT EXISTS UserAgents ( source TEXT PRIMARY KEY, regex TEXT)";
    $q->QUERY_SQL($sql);
    if(!isset($GLOBALS["PACTIME"])){$GLOBALS["PACTIME"]["USERAGENTS"]=array();}


    foreach ($GLOBALS["PACTIME"]["USERAGENTS"] as $USERAGENT=>$NONE){
        $regex=$useragents->PatternToRegex($USERAGENT);
        $USERAGENT=$q->sqlite_escape_string2($USERAGENT);
        $regex=$q->sqlite_escape_string2($regex);
        $sql="INSERT OR IGNORE INTO UserAgents(source,regex) VALUES ('$USERAGENT','$regex')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo $q->mysql_error."\n";return;}
    }

    return true;


}

function ScanFile($filename){

    $fp = @fopen("/var/log/proxy-pac/$filename", "r");
    if(!$fp){
        echo "$filename BAD FD\n";
        return true;
    }

    while(!feof($fp)) {
        $line = trim(fgets($fp));
        $line = trim($line);
        if($line==null){continue;}
        $tt=explode("|||",$line);

        $zDate=TimeTo10Minutes($tt[0]);
        $UserAgent=$tt[1];
        $ipAddr=$tt[2];

        if(!isset($GLOBALS[$zDate]["RQS"])){$GLOBALS[$zDate]["RQS"]=0;}

        $GLOBALS["PACTIME"][$zDate]["RQS"]++;
        $GLOBALS["PACTIME"][$zDate]["CLIENTS"][$ipAddr]=true;
        $GLOBALS["PACTIME"]["USERAGENTS"][$UserAgent]=true;


    }

    return true;


}

function TimeTo10Minutes($xtime){
    $date = new DateTime(date("Y-m-d H:i:s",$xtime));
    $Newdate=roundUpToMinuteInterval($date);
    return $Newdate->format("Y-m-d H:i:s");
}


function roundUpToMinuteInterval(\DateTime $dateTime, $minuteInterval = 10){
    return $dateTime->setTime($dateTime->format('H'), ceil($dateTime->format('i') / $minuteInterval) * $minuteInterval, 0);
}
function roundDownToMinuteInterval(\DateTime $dateTime, $minuteInterval = 10){
    return $dateTime->setTime($dateTime->format('H'), floor($dateTime->format('i') / $minuteInterval) * $minuteInterval, 0);
}