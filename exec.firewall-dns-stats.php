<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["VERBOSE"]=true;
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.familysites.inc");
if(isset($argv[1])){

    if($argv[1]=="--rrd"){firewall_rrd();exit;}
    if($argv[1]=="--graphs"){generates_graphs();exit;}

}

scan_firewall_stats();

function scan_firewall_stats(){
    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        $time = $unix->PROCCESS_TIME_MIN($pid);
        echo "Already Artica task running PID $pid since {$time}mn\n";
        return false;
    }
    @file_put_contents($pidfile, getmypid());

    $DisablePostGres = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));
    $DisableDNSFWLogRules = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisableDNSFWLogRules"));
    $unix = new unix();



    $BaseWorkDir = "/home/artica/temp-dnsfirewall";
    $handle = opendir($BaseWorkDir);
    if (!$handle) {
        echo "Unable to handle $BaseWorkDir\n";
        return false;
    }
    echo "Scanning handle $BaseWorkDir\n";
    while (false !== ($filename = readdir($handle))) {
        if ($filename == ".") {
            continue;
        }
        if ($filename == "..") {
            continue;
        }
        $srcfile = "$BaseWorkDir/$filename";
        if (preg_match("#\.array$#", $filename)) {
            @unlink($srcfile);
            continue;
        }
        if (!preg_match("#\.log$#", $filename)) {
            continue;
        }
        if ($DisablePostGres == 1) {
            @unlink($srcfile);
            continue;
        }

        $ftime=$unix->file_time_min($srcfile);
        if($ftime>420){@unlink($srcfile);continue;}
        if($ftime<11){
            echo "$filename ---> SKIP\n";
            continue;
        }

        preg_match("#([0-9]{1,4})([0-9]{1,2})([0-9]{1,2})([0-9]{1,2})([0-9]{1,2})#",$filename,$re);
        $year=$re[1];
        $month=$re[2];
        $day=$re[3];
        $hour=$re[4];
        $mins=$re[5];
        $zdate="$year-$month-$day $hour:$mins:00";
        echo "Parsing $srcfile\n";
        if(parse_file($zdate,$srcfile)){@unlink($srcfile);}
    }


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

function parse_file($zdate,$DestPath){
    $xtime=strtotime($zdate);
    $qfam=new squid_familysite();
    $handle = @fopen($DestPath, "r");
    if (!$handle) {echo "Failed to open file\n";return;}
    $c=0;
    $COMPRESS=array();
    while (!feof($handle)){
        $c++;
        $unserialized =trim(fgets($handle, 4096));
        $array=unserialize($unserialized);
        if(!isset($array["ACTION"])){continue;}
        $SRC=$array["SRC"];
        if(!preg_match("#[0-9\.]+$#",$SRC)){continue;}
        $QTYPE=$array["QTYPE"];
        $DOMAIN=$array["DOMAIN"];
        $CATEGORY=$array["CATEGORY"];
        $FAMILYSITE=$qfam->GetFamilySites($DOMAIN);
        $siteid=get_siteid($DOMAIN,$FAMILYSITE,$CATEGORY);
        $CACHE=$array["CACHE"];
        $RULE=$array["RULE"];
        $TIME=$array["TIME"];
        $SRN_ACTION=$array["ACTION"];
        $SRC=$array["SRC"];
        if($GLOBALS["VERBOSE"]){echo "$TIME: $SRN_ACTION $DOMAIN $QTYPE - $RULE - $siteid\n";}
        if(!isset($COMPRESS[$siteid][$QTYPE][$RULE][$SRN_ACTION][$SRC])){
            $COMPRESS[$siteid][$QTYPE][$RULE][$SRN_ACTION][$SRC]=1;
        }else{
            $COMPRESS[$siteid][$QTYPE][$RULE][$SRN_ACTION][$SRC]=$COMPRESS[$siteid][$QTYPE][$RULE][$SRN_ACTION][$SRC]+1;
        }

    }
    $f=array();
    foreach ($COMPRESS as $domainid=>$level1) {
        foreach ($level1 as $qtype => $level2) {
            foreach ($level2 as $ruleid => $level3) {
                foreach ($level3 as $action => $level4) {
                    foreach ($level4 as $ipaddr => $hits) {
                        if($GLOBALS["VERBOSE"]){echo "$zdate - $domainid,$qtype,$ruleid,$action,$ipaddr===>$hits\n";}
                         $f[]="('$xtime','$ipaddr','$qtype','$action','$hits','$domainid','$ruleid')";
                         if(count($f)>500){
                            if(!dnsfw_inject($f)){return false;}
                            $f=array();
                         }
                    }
                }
            }

        }
    }

    if(count($f)>0){
        if(!dnsfw_inject($f)){return false;}
    }

    firewall_rrd();
    return true;

}

function dnsfw_inject($array):bool{
    $prefix="INSERT INTO dnsfw_access (zdate,ipaddr,qtype,action,hits,domainid,ruleid)";
    $ssline=@implode(",",$array);
    $q=new postgres_sql();
    $q->QUERY_SQL($prefix." VALUES ".$ssline);
    if(!$q->ok){
        echo $q->mysql_error."\n";
        return false;}
    return true;

}

function dashboard_stats(){

    $sql="SELECT dnsfw_access.domainid, count(dnsfw_access.hits) as hits 
    statscom_websites.category,statscom_websites.siteid
    FROM dnsfw_access,statscom_websites
    WHERE dnsfw_access.domainid=statscom_websites.siteid ORDER BY hits DESC
    ";

}

function next10Minutes($time)  {
    $time = $time + 600;

    $nextMinutes = floor(date("i", $time) / 10) * 10;

    if ($nextMinutes < 10) {
        $nextMinutes = "00";
    }

    return strtotime(date("d-m-Y H:{$nextMinutes}:00", $time));
}

function firewall_rrd(){
    $unix=new unix();
    $pidtime="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
    $xtime=$unix->file_time_min($pidtime);
    if($xtime<12){return false;}
    @unlink($pidtime);
    @file_put_contents($pidtime,time());
    $DNSFireWallStatsRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DNSFireWallStatsRetention"));
    if($DNSFireWallStatsRetention==0){$DNSFireWallStatsRetention=5;}
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){$DNSFireWallStatsRetention=5;}

    $base="/home/artica/rrd";
    $start              =" --start " .strtotime('-2 day');
    if(!is_dir($base)){@mkdir($base,0755,true);}
    $rrdtool=$unix->find_program("rrdtool");

    $ARRAY=array();
    $CLIENTS=array();
    $q=new postgres_sql();
    if(!$q->FIELD_EXISTS("dnsfw_access","stats")){
        $q->QUERY_SQL("ALTER TABLE dnsfw_access ADD stats smallint NOT NULL DEFAULT 0");
        if(!$q->ok){echo $q->mysql_error."\n";}
    }

    $now = strtotime("-10 minutes");
    $last2days=strtotime("-{$DNSFireWallStatsRetention} day");
    $results=$q->QUERY_SQL("SELECT zdate,ipaddr,hits FROM dnsfw_access WHERE zdate < $now AND stats=0 ORDER BY zdate");
    if(!$q->ok){echo $q->mysql_error."\n";}
    while ($ligne = pg_fetch_assoc($results)) {
        $xtime=$ligne["zdate"];
        $ipaddr=$ligne["ipaddr"];
        $hits=$ligne["hits"];
        $x10=next10Minutes($xtime);
        if(!isset($CLIENTS[$x10][$ipaddr])){
            $CLIENTS[$x10][$ipaddr]=$hits;
        }else{
            $CLIENTS[$x10][$ipaddr]=$CLIENTS[$x10][$ipaddr]+$hits;
        }
        if(!isset($ARRAY[$x10])){$ARRAY[$x10]=$hits;continue;}
        $ARRAY[$x10]=$ARRAY[$x10]+$hits;

    }
   $NEWCLS=array();
   foreach ($CLIENTS as $time=>$cc){
       $c=0;
       foreach ($cc as $key=>$number){$c++;}
       $NEWCLS[$time]=$c;
   }

    if(!is_file("$base/dnsfw_cnx.rrd")) {
        echo "-------> Creating $base/dnsfw_cnx.rrd\n";
        shell_exec("$rrdtool create $base/dnsfw_cnx.rrd -s 300$start"
            . " DS:cnx:GAUGE:800:0:U"
            . " RRA:AVERAGE:0.5:1:576"
            . " RRA:AVERAGE:0.5:6:672"
            . " RRA:AVERAGE:0.5:24:732"
            . " RRA:AVERAGE:0.5:144:1460");
    }
    if(!is_file("$base/dnsfw_users.rrd")) {
        echo "-------> Creating $base/dnsfw_users.rrd\n";
        shell_exec("$rrdtool create $base/dnsfw_users.rrd -s 300$start"
            . " DS:users:GAUGE:800:0:U"
            . " RRA:AVERAGE:0.5:1:576"
            . " RRA:AVERAGE:0.5:6:672"
            . " RRA:AVERAGE:0.5:24:732"
            . " RRA:AVERAGE:0.5:144:1460");
    }
    ksort($ARRAY);
    ksort($NEWCLS);
    foreach ($ARRAY as $time=>$value) {
        $res = array();
        $cmdline = array();
        $cmdline[] = "$rrdtool updatev $base/dnsfw_cnx.rrd";
        $cmdline[] = "-t cnx";
        $cmdline[] = "$time:$value";
        if ($GLOBALS["VERBOSE"]) {
            echo @implode(" ", $cmdline) . "\n";
        }
        exec(@implode(" ", $cmdline), $res);
        foreach ($res as $out) {
            echo "[OUT] dnsfw_cnx.rrd $out\n";
        }
    }
    foreach ($NEWCLS as $time=>$value) {
        $res = array();
        $cmdline = array();
        $cmdline[] = "$rrdtool updatev $base/dnsfw_users.rrd";
        $cmdline[] = "-t users";
        $cmdline[] = "$time:$value";
        if ($GLOBALS["VERBOSE"]) {
            echo @implode(" ", $cmdline) . "\n";
        }
        exec(@implode(" ", $cmdline), $res);
        foreach ($res as $out) {
            echo "[OUT] dnsfw_users.rrd $out\n";
        }
    }
    generates_graphs();
    $q->QUERY_SQL("UPDATE dnsfw_access SET stats=1 WHERE zdate < $now AND stats=0");
    $q->QUERY_SQL("DELETE FROM dnsfw_access WHERE zdate < $last2days");
    $q->QUERY_SQL("OPTIMIZE TABLE dnsfw_access");

}
function generates_graphs(){
    $periods[]="hourly";
    $periods[]="yesterday";
    $periods[]="day";
    $periods[]="week";
    $periods[]="month";
    $periods[]="year";



    $dbnames["dnsfw_users"]="Number of Clients";
    $dblegend["dnsfw_users"]="ipaddr";
    $dbtables["dnsfw_users"]="users";

    $dbnames["dnsfw_cnx"]="Number of queries";
    $dblegend["dnsfw_cnx"]="Queries";
    $dbtables["dnsfw_cnx"]="cnx";

    foreach ($dbnames as $dbname=>$title){

        foreach ($periods as $period) {
            $legend=$dblegend[$dbname];
            $table=$dbtables[$dbname];
            generate_image($period, $dbname,$title,$legend,$table);
        }

    }
}
function generate_image($period,$dbname,$title,$legend,$tablename){
    $unix=new unix();
    $rrdtool=$unix->find_program("rrdtool");
    $img="/usr/share/artica-postfix/img/squid";
    if(!is_dir($img)){@mkdir($img,0755,true);}
    $rrd="/home/artica/rrd";

    $tperiod="-s \"-1$period\"";

    if($period=="yesterday"){
        $tperiod="--end 00:00";
    }
    if($period=="hourly"){
        $tperiod="-s end-1h";
    }

    $img_with=950;
    $img_heigth=500;
    $Gridcolor="#ebebeb";
    $linecolor="1db496";
    $bgcolor="-c BACK#FFFFFF -c CANVAS#FFFFFF -c SHADEA#FFFFFF -c SHADEB#FFFFFF -c GRID$Gridcolor -c MGRID$Gridcolor -c ARROW#FFFFFF  --slope-mode --watermark \"$(date -R)\" --no-gridfit --font TITLE:13:Arial --font AXIS:8:'Arial' --font LEGEND:8:'Courier' --font UNIT:8:'Arial' --font WATERMARK:6:'Arial'";

    system("$rrdtool graph $img/$dbname-$period.png"
        ." $tperiod"
        ." -t \"$title over the last $period\""
        ." --lazy"
        ." -h $img_heigth -w $img_with"
        ." -l 0"
        ." -a PNG"
        ." -v \"$legend\""
        ." $bgcolor"
        ." DEF:filed=$rrd/$dbname.rrd:$tablename:AVERAGE"
        ." CDEF:base=filed"
        ." GPRINT:base:MAX:\" Max\\: %5.1lf %s\""
        ." GPRINT:base:AVERAGE:\" Avg\\: %5.1lf %S\""
        ." GPRINT:base:LAST:\" Current\\: %5.1lf %S\\n\""
        ." LINE3:base#$linecolor:$legend"
    );

}