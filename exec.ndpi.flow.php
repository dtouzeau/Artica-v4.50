<?php
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["YESCGROUP"]=true;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.familysites.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
//. Connection start time.
//2. The time of the last connection packet.
//3. L3 proto: 4 - IPv4, 6 - IPv6
//4. L4 protocol (1 - icmp, 6 - tcp, 17 - udp ...)
//5. Source address
//6. Source port (0 if not exist)
//7. Destination address
//8. Destination port (0 if not exist)
//9. Bytes from sourse to destination
//10. Bytes from destination to sourse
//11. Packets from sourse to destination
//12. Packets from destination to sourse
//13. Interface indexes. Format: "I=<in_ifindex>,<out_ifindex>"
//14. Connection mark. Optional. Format: "CM=<hexmark>"
//15. Source NAT. Optional, only for IPv4. Format: "SN=ipv4_address:port"
//16. Destination NAT. Optional, only for IPv4. Format: "DN=ipv4_address:port"
//17. NDPI protocol. Format: "P=xxx"
if(isset($argv)){
    if($argv[1]=="--scan"){scan_file($argv[2]);exit;}
    if($argv[1]=="--compress-day"){compressday();exit;}
    if($argv[1]=="--loop"){parse_files();exit;}
    if($argv[1]=="--stats"){ndpi_stats();exit;}
    if($argv[1]=="--month"){ndpi_month();exit;}
    if($argv[1]=="--repair"){ndpi_month_repair();exit;}
    if($argv[1]=="--tracks"){Trackers();exit;}
}

//exec.ndpi.flow.php --loop


function parse_files(){

    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";

    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){
        echo "Already running pid $pid\n";
        die();
    }

    @file_put_contents($pidfile,getmypid());
    $Groups=$unix->DirFiles("/home/artica/ndpi-temp","\.ndpi$");
    foreach ($Groups as $filename=>$none){
        $filepath="/home/artica/ndpi-temp/$filename";
        echo "$filepath ->";
        if(!scan_file($filepath)){ echo " FALSE\n";continue;}
        echo " True\n";
        @unlink($filepath);
    }


}



function scan_file($filepath){

    $fam    = new squid_familysite();
    $handle = @fopen($filepath, "r");
    $f      = array();
    $MAIN   = array();
    if (!$handle) {echo "Failed to open file $filepath\n";return;}

    $c=0;
    while (!feof($handle)) {
        $pattern = trim(fgets($handle));
        if ($pattern == null) {
            continue;
        }
        $familysite=null;

        if(preg_match("#\s+([C|H])=(.+?)$#",$pattern,$re)){
            $hostname=$re[2];
            if(preg_match("#^(_none_|www)\.(.+)#",$hostname,$re)){$hostname=$re[2];}
            $hostname=str_replace("*.","",$hostname);
            $familysite=$fam->GetFamilySites($hostname);
        }

        $Array=pattern1($pattern);
        if(!$Array){
            $Array=pattern2($pattern);
        }

        if(!is_array($Array)){
            continue;
        }

        $Connection_start_time=$Array["Connection_start_time"];
        $Connection_last_time=$Array["Connection_last_time"];
        $L3_proto=$Array["L3_proto"];
        $L4_proto=$Array["L4_proto"];
        $source_address=$Array["source_address"];
        $source_port=$Array["source_port"];
        $destination_addr=$Array["destination_addr"];
        $destination_port=$Array["destination_port"];
        $UploadBytes=$Array["U"];
        $DownloadBytes=$Array["D"];
        $Application=$Array["APP"];
        if($familysite==null){$familysite=$Application;}
        $c++;

        if(($source_address=="127.0.0.1") AND ($destination_addr=="127.0.0.1")){continue;}
        if($source_address=="0.0.0.0"){continue;}

        if($source_address==null){
            echo "Source address null ici:$pattern\n";
            continue;
        }

        $date2=date("Y-m-d H:i:s",roundToClosestMinute($Connection_start_time,10));


        if(!isset($MAIN[$date2][$source_address][$destination_addr][$L4_proto][$Application][$familysite])){
            $MAIN[$date2][$source_address][$destination_addr][$L4_proto][$Application][$familysite]["U"]=intval($UploadBytes);
            $MAIN[$date2][$source_address][$destination_addr][$L4_proto][$Application][$familysite]["D"]=intval($DownloadBytes);
            continue;
        }else{
            if($DownloadBytes>0) {
                $D = intval($MAIN[$date2][$source_address][$destination_addr][$L4_proto][$Application][$familysite]["D"]);
                $D = $D + $DownloadBytes;
                $MAIN[$date2][$source_address][$destination_addr][$L4_proto][$Application][$familysite]["D"] = $D;
            }

            if($UploadBytes>0){
                $U=intval($MAIN[$date2][$source_address][$destination_addr][$L4_proto][$Application][$familysite]["U"]);
                $U=$U+$UploadBytes;
                $MAIN[$date2][$source_address][$destination_addr][$L4_proto][$Application][$familysite]["U"]=$U;
            }
        }
    }

    echo "$c paquets\n";


    $q=new postgres_sql();
    $ToCatgorize=array();
    $prefix="INSERT INTO ndpi_main (zdate,src,dst,l4proto,category,categoryint,familysite,download,upload) VALUES ";

    foreach ($MAIN as $date=>$MAIN2){

        foreach ($MAIN2 as $source_address=>$MAIN3){

            foreach ($MAIN3 as $destination_addr=>$MAIN4){

                foreach ($MAIN4 as $L4_proto=>$MAIN5){

                    foreach ($MAIN5 as $Application=>$MAIN6){

                        foreach ($MAIN6 as $familysite=>$MAIN7) {
                            $categoryint=0;

                            if(!isset($TOCATZE_FAM[$familysite])) {
                                if(strpos($familysite,".")>0) {
                                    $ToCatgorize[] = $familysite;
                                    $TOCATZE_FAM[$familysite] = true;
                                }
                            }
                            $DOWN = $MAIN7["D"];
                            $UPL = $MAIN7["U"];
                            $f[] = "('$date','$source_address','$destination_addr',
                            '$L4_proto','$Application','$categoryint','$familysite','$DOWN','$UPL')";
                            if (count($f) > 8000) {
                                $q->QUERY_SQL($prefix . @implode(",", $f));
                                if (!$q->ok) {
                                    echo $q->mysql_error."\nin Line ".__LINE__."\n";
                                    return false;
                                }
                                $f = array();
                            }
                        }
                    }

                }

            }


        }
    }

    if(count($f)>0){
        echo "Save ".count($f)." entries\n";
        $q->QUERY_SQL($prefix.@implode(",",$f));
        if(!$q->ok){
            echo $q->mysql_error."\n";
            return false;
        }

    }

    if(count($ToCatgorize)>0){
        $catz=new mysql_catz();
        $zArray=array();
        $array=$catz->ufdbcat_bulk($ToCatgorize);
        if(!isset($array["RESPONSE"])){
            $array["RESPONSE"]=null;
        }
        if(!is_null($array["RESPONSE"])) {
            $zArray = unserialize($array["RESPONSE"]);
        }
        if(isset($zArray["sitenames"])){
            foreach ($zArray["sitenames"] as $sitename=>$category){
                if($category==0){continue;}
                $q->QUERY_SQL("UPDATE ndpi_main SET categoryint=$category WHERE familysite='$sitename'");
            }
        }
    }


    return true;
}

function pattern1($pattern){

    if(!preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+I=([0-9,]+).*?P=(.+?)(\s+[A-Z]=|$)#",$pattern,$re)){return false;}
    $Connection_start_time=$re[1];
    $Connection_last_time=$re[2];
    $L3_proto=$re[3];
    $L4_proto=$re[4];
    $source_address=$re[5];
    $source_port=$re[6];
    $destination_addr=$re[7];
    $destination_port=$re[8];
    $UploadBytes=$re[9];
    $DownloadBytes=$re[10];
    $Application=$re[14];


    if(preg_match("#^(.+?)\s+C=#",$Application,$re)){$Application=$re[1];}

    $array["Connection_start_time"]=$Connection_start_time;
    $array["Connection_last_time"]=$Connection_last_time;
    $array["L3_proto"]=$L3_proto;
    $array["L4_proto"]=$L4_proto;
    $array["source_address"]=$source_address;
    $array["source_port"]=$source_port;
    $array["destination_addr"]=$destination_addr;
    $array["destination_port"]=$destination_port;
    $array["U"]=$UploadBytes;
    $array["D"]=$DownloadBytes;
    $array["APP"]=$Application;

    return $array;


}

function pattern2($pattern){
    echo "Not Found \"$pattern\"\n";
    if(!preg_match("#^([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9\.]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+I=([0-9,]+)\s+P=(.+?)\s+H=#",$pattern,$re)){return;}
    
    
    
}

function roundToClosestMinute($input = 0, $round_to_minutes = 5, $type = 'auto'){
    $now = !$input ? time() : (int)$input;

    $seconds = $round_to_minutes * 60;
    $floored = $seconds * floor($now / $seconds);
    $ceiled = $seconds * ceil($now / $seconds);

    switch ($type) {
        default:
            $rounded = ($now - $floored < $ceiled - $now) ? $floored : $ceiled;
            break;

        case 'ceil':
            $rounded = $ceiled;
            break;

        case 'floor':
            $rounded = $floored;
            break;
    }

    return $rounded ? $rounded : $input;
}


function compressday(){

    $q=new postgres_sql();

    echo "ndpi_main = ".$q->COUNT_ROWS_LOW("ndpi_main")." entries\n";

    $sql="SELECT date_trunc('day', zdate) AS tday, SUM(download) as down, SUM(upload) as up, category FROM ndpi_main
    GROUP BY tday,category";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}

    while ($ligne = pg_fetch_assoc($results)) {

        echo "{$ligne["tday"]} {$ligne["category"]} {$ligne["down"]} {$ligne["up"]}\n";
    }


}


function ndpi_stats(){

    if(!is_file("/etc/cron.d/ndpi-month")){
        $unix=new unix();
        $unix->Popuplate_cron_make("ndpi-month","30 1 * * *","exec.ndpi.flow.php --month");
        system("/etc/init.d/cron reload");
    }
    $currenthour=date("Y-m-d H:00:00");
    $currentDay=date("Y-m-d 00:00:00");
    $currentWeek=date("Y-m-d 00:00:00", strtotime('monday this week'));
    $q=new postgres_sql();

    $ligne=$q->mysqli_fetch_array("SELECT SUM(download) as download, SUM(upload) as upload FROM ndpi_main WHERE zdate>'$currenthour'");

    $hourly_download=$ligne["download"];
    $hourly_upload=$ligne["upload"];


    $ligne=$q->mysqli_fetch_array("SELECT SUM(download) as download, SUM(upload) as upload FROM ndpi_main WHERE zdate>'$currentDay'");

    $daily_download=$ligne["download"];
    $daily_upload=$ligne["upload"];


    $ligne=$q->mysqli_fetch_array("SELECT SUM(download) as download, SUM(upload) as upload FROM ndpi_main WHERE zdate>'$currentWeek'");

    $week_download=$ligne["download"];
    $week_upload=$ligne["upload"];

    $MAIN["TOTAL"]["WEEKLY"]["DOWN"]=$week_download;
    $MAIN["TOTAL"]["WEEKLY"]["UP"]=$week_upload;



    $thisMonth=date("Y-m-01 00:00:00");
    $ligne=$q->mysqli_fetch_array("SELECT  SUM(download) as download, SUM(upload) as upload FROM ndpi_month WHERE date_trunc('month',zdate)='$thisMonth'");


    $MAIN["TOTAL"]["MONTHLY"]["DOWN"]=$ligne["download"];
    $MAIN["TOTAL"]["MONTHLY"]["UP"]=$ligne["upload"];



    echo "Hourly: $hourly_download/$hourly_upload\n";
    echo "Daily: $daily_download/$daily_upload\n";
    echo "Weekly: $week_download/$week_upload\n";

    $MAIN["TOTAL"]["HOURLY"]["DOWN"]=$hourly_download;
    $MAIN["TOTAL"]["HOURLY"]["UP"]=$hourly_upload;

    $MAIN["TOTAL"]["DAILY"]["UP"]=$daily_upload;
    $MAIN["TOTAL"]["DAILY"]["DOWN"]=$daily_download;


    $array=array();



    $results=$q->QUERY_SQL("SELECT date_trunc('day', zdate) AS tday FROM ndpi_main WHERE zdate>'$currentWeek' GROUP BY tday ORDER BY tday");

    if(!$q->ok){echo $q->mysql_error."\n";return;}



    while ($ligne = pg_fetch_assoc($results)) {
        $sday=$ligne["tday"];
        $xtime=strtotime($sday);
        $nextday=str_replace("00:00:00","23:59:59",$sday);
        echo "Day: $sday - $nextday\n";

        $results2=$q->QUERY_SQL("SELECT zdate,SUM(download) as download, 
        SUM(upload) as upload FROM ndpi_main WHERE zdate>'$sday' AND zdate<'$nextday'
        GROUP BY zdate ORDER BY zdate");

        while ($ligne2 = pg_fetch_assoc($results2)) {
            $download=intval($ligne2["download"]);
            $upload=intval($ligne2["upload"]);
            $zdate=$ligne2["zdate"];
            echo "Hourly: {$zdate}: $download,$upload\n";
            $MAIN["RQ"]["WEEKLY"][$xtime][$zdate]=array("DOW"=>$download,"UP"=>$upload);
        }

        //categoryint INT,
        //		familysite VARCHAR(512),

        $results2=$q->QUERY_SQL("SELECT category,SUM(download) as download, SUM(upload) as upload FROM 
        ndpi_main WHERE zdate>'$sday' AND zdate<'$nextday' GROUP BY category ORDER BY download DESC LIMIT 10");

        while ($ligne2 = pg_fetch_assoc($results2)) {
            $category=$ligne2["category"];
            $download=intval($ligne2["download"]);
            $upload=intval($ligne2["upload"]);
            echo "$sday - $nextday $category: $download,$upload\n";
            $array[$category]=array("DOW"=>$download,"UP"=>$upload);
            $MAIN["RQ"]["CATEGORY"][$xtime][$category]=array("DOW"=>$download,"UP"=>$upload);
        }

        $results2=$q->QUERY_SQL("SELECT src,SUM(download) as download, SUM(upload) as upload FROM ndpi_main 
        WHERE zdate>'$sday' AND zdate<'$nextday'  GROUP BY src ORDER BY download DESC LIMIT 10");

        while ($ligne2 = pg_fetch_assoc($results2)) {
            $category=$ligne2["src"];
            $download=intval($ligne2["download"]);
            $upload=intval($ligne2["upload"]);
            echo "$sday - $nextday $category: $download,$upload\n";
            $array[$category]=array("DOW"=>$download,"UP"=>$upload);
            $MAIN["RQ"]["SRC"][$xtime][$category]=array("DOW"=>$download,"UP"=>$upload);
        }

        $results2=$q->QUERY_SQL("SELECT familysite,SUM(download) as download, SUM(upload) as upload FROM ndpi_main 
        WHERE zdate>'$sday' AND zdate<'$nextday'  GROUP BY familysite ORDER BY download DESC LIMIT 10");

        while ($ligne2 = pg_fetch_assoc($results2)) {
            $category=$ligne2["familysite"];
            $download=intval($ligne2["download"]);
            $upload=intval($ligne2["upload"]);
            echo "$sday - $nextday $category: $download,$upload\n";
            $array[$category]=array("DOW"=>$download,"UP"=>$upload);
            $MAIN["RQ"]["FAM"][$xtime][$category]=array("DOW"=>$download,"UP"=>$upload);
        }

        $results2=$q->QUERY_SQL("SELECT categoryint,SUM(download) as download, SUM(upload) as upload FROM ndpi_main 
        WHERE zdate>'$sday' AND zdate<'$nextday'  GROUP BY categoryint,familysite ORDER BY download DESC LIMIT 10");

        if($q->ok) {
            while ($ligne2 = pg_fetch_assoc($results2)) {
                $category = $ligne2["categoryint"];
                $download = intval($ligne2["download"]);
                $upload = intval($ligne2["upload"]);
                echo "$sday - $nextday $category: $download,$upload\n";
                $array[$category] = array("DOW" => $download, "UP" => $upload);
                $MAIN["RQ"]["CATZ"][$xtime][$category] = array("DOW" => $download, "UP" => $upload);
            }
        }else{
            squid_admin_mysql(1,"nDPI SQL Error $q->mysql_error",null,__FILE__,__LINE__);
        }


    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("NDPI_DASHBOARD",serialize($MAIN));
//familysite,categoryint

}

function ndpi_month($myDate=null){

    if($myDate==null) {
        $yesterday1 = date("Y-m-d 00:00:00", strtotime("yesterday 00:00:00"));
        $yesterday2 = date("Y-m-d 23:59:59", strtotime("yesterday 00:00:00"));
    }else{
        $xtime=strtotime($myDate);
        $yesterday1 = date("Y-m-d 00:00:00", $xtime);
        $yesterday2 = date("Y-m-d 23:59:59", $xtime);

    }



    $q=new postgres_sql();

    echo "Parsing $yesterday1\n";

    $sql="SELECT date_trunc('day', zdate) AS tday, SUM(download) as down, SUM(upload) as up, category,src,dst,familysite,categoryint FROM ndpi_main
    WHERE zdate>='$yesterday1' and zdate<='$yesterday2'
    GROUP BY tday,category,src,dst,familysite,categoryint";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}


    $prefix="INSERT INTO ndpi_month (zdate,src,dst,category,download,upload,familysite,categoryint) VALUES ";
    $f=array();
    while ($ligne = pg_fetch_assoc($results)) {
        $date=$ligne["tday"];
        $down=$ligne["down"];
        $src=$ligne["src"];
        $up=$ligne["up"];
        $dst=$ligne["dst"];
        $category=$ligne["category"];
        $familysite=$ligne["familysite"];
        $categoryint=$ligne["categoryint"];
        $f[]="('$date','$src','$dst','$category','$down','$up','$familysite','$categoryint')";
        if(count($f)>2000){
            $q->QUERY_SQL($prefix.@implode(",",$f));
            $f=array();
        }
    }


    if(count($f)>0){
        $q->QUERY_SQL($prefix.@implode(",",$f));
        $f=array();
    }

    ndpi_month_repair();
}

function ndpi_month_repair(){

    $q=new postgres_sql();
    $sql="SELECT zdate, SUM(download) as down, SUM(upload) as up FROM ndpi_month GROUP by zdate order by zdate";

    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}


    echo "In month, ".pg_num_rows($results)." items\n";

    while ($ligne = pg_fetch_assoc($results)) {
        echo "In month: {$ligne["zdate"]}\n";

        $IN_MONTH[$ligne["zdate"]]=$ligne["down"];


    }

    $Today=date("Y-m-d 00:00:00");
    $sql="SELECT date_trunc('day', zdate) AS tday,SUM(download) as down FROM ndpi_main WHERE zdate<'$Today' GROUP BY tday ORDER BY tday";


    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";}


    echo "In Days, ".pg_num_rows($results)." items\n";

    while ($ligne = pg_fetch_assoc($results)) {
        $zDate=$ligne["tday"];
        echo "In Days: $zDate\n";

        if(!isset($IN_MONTH[$zDate])){
            squid_admin_mysql(1,"Corrupted table month no date $zDate",null,__FILE__,__LINE__);
            echo "Corrupted table month no date $zDate\"\n";
            $q->QUERY_SQL("DELETE FROM ndpi_month WHERE zdate='$zDate'");
            ndpi_month($zDate);

        }

        if(isset($IN_MONTH[$zDate])){

            $DownMonth=$IN_MONTH[$zDate];
            $Down=$ligne["down"];
            $result=$Down-$DownMonth;
            $result=$result/1024;$result=$result/1024;
            echo "=$Down-$DownMonth --> $result MB\n";
            if($result>10){
                squid_admin_mysql(0,"Corrupted table month for {$result}MB");
                echo "Corrupted table month for {$result}MB\n";
                $q->QUERY_SQL("DELETE FROM ndpi_month WHERE zdate='$zDate'");
                ndpi_month($zDate);
            }

        }

    }
}

function Ultimate_Hosts_Blacklist(){

    $Ultimate_Hosts_Blacklist_size=0;
    $uri="https://raw.githubusercontent.com/Ultimate-Hosts-Blacklist/lightswitch05_hosts_ads-and-tracking-extended/master/domains.list";

    if(is_file("/home/artica/ndpi/AdsAndTracks/Ultimate-Hosts-Blacklist.size")){
        $Ultimate_Hosts_Blacklist_size=@file_get_contents("/home/artica/ndpi/AdsAndTracks/Ultimate-Hosts-Blacklist.size");
    }


   $curl=new ccurl($uri);
   $heads=$curl->getHeaders();
   $Content_Length=$heads["Content-Length"];

   echo "$Ultimate_Hosts_Blacklist_size <> $Content_Length\n";

   if($Content_Length==$Ultimate_Hosts_Blacklist_size){
       echo "Ultimate-Hosts-Blacklist: Already updated...\n";
       return;}

    $curl=new ccurl($uri);
    if(!$curl->GetFile("/home/artica/ndpi/AdsAndTracks/Ultimate-Hosts-Blacklist.txt")){
        @unlink("/home/artica/ndpi/AdsAndTracks/Ultimate-Hosts-Blacklist.txt");
        echo "Failed to download $curl->error\n";
        return;
    }

@file_put_contents("/home/artica/ndpi/AdsAndTracks/Ultimate-Hosts-Blacklist.size",$Content_Length);

}

function Trackers(){

    $unix=new unix();

    $filesSource[]="/home/artica/ndpi/AdsAndTracks/Ultimate-Hosts-Blacklist.txt";

    $echo=$unix->find_program("echo");
    $Ultimate_Hosts_Blacklist_md5=null;

    if(!is_dir("/home/artica/ndpi/AdsAndTracks")){
        @mkdir("/home/artica/ndpi/AdsAndTracks",0755,true);
    }
    Ultimate_Hosts_Blacklist();


    shell_exec("$echo \"add_custom AdsAndTracks\" >/proc/net/xt_ndpi/proto");


    foreach ($filesSource as $filepath){
        if(!is_file($filepath)){continue;}
        $tt=explode("\n",@file_get_contents($filepath));
        foreach ($tt as $dstdomain) {
            $dstdomain = trim(strtolower($dstdomain));
            if ($dstdomain == null) {continue;}
            $f[$dstdomain] = true;
        }
    }


    $f["0.gravatar.com"]=true;
    $f["023hysj.com"]=true;
    $f["1.gravatar.com"]=true;
    $f["13122.engine.mobileapptracking.com"]=true;
    $f["189358.engine.mobileapptracking.com"]=true;
    $f["192351.engine.mobileapptracking.com"]=true;
    $f["1bb821ddb967b3e010b63f2a2f3308e3.api.appsee.com"]=true;
    $f["1emn.com"]=true;
    $f["1mp.mobi"]=true;
    $f["247realmedia.com"]=true;
    $f["2705.api.swrve.com"]=true;
    $f["2705.content.swrve.com"]=true;
    $f["2o7.net"]=true;
    $f["31.14.252.148"]=true;
    $f["360yield.com"]=true;
    $f["37613-5b825.api.pushwoosh.com"]=true;
    $f["3aeeenmpdb.com"]=true;
    $f["3vwp.org"]=true;
    $f["4b6994dfa47cee4.com"]=true;
    $f["4seeresults.com"]=true;
    $f["54.222.186.106"]=true;
    $f["5d56a.v.fwmrm.net"]=true;
    $f["615b68cc9c8528e.com"]=true;
    $f["7015.xg4ken.com"]=true;
    $f["746fyw2v.com"]=true;
    $f["821.tm.zedo.com"]=true;
    $f["883.engine.mobileapptracking.com"]=true;
    $f["88-f.net"]=true;
    $f["91-cdn.com"]=true;
    $f["95.213.231.34"]=true;
    $f["a.applvn.com"]=true;
    $f["a.bitmango.com"]=true;
    $f["a.fiksu.com"]=true;
    $f["a.mstrlytcs.com"]=true;
    $f["a.optmstr.com"]=true;
    $f["a.optnmstr.com"]=true;
    $f["a.pub.network"]=true;
    $f["a.zdbb.net"]=true;
    $f["a2dfp.net"]=true;
    $f["a2pub.com"]=true;
    $f["a35e6f8ef7a43f24c49.com"]=true;
    $f["aax-us.amazon-adsystem.com"]=true;
    $f["abbott.vo.llnwd.net"]=true;
    $f["abnad.net"]=true;
    $f["abroad-ad.kingsoft-office-service.com"]=true;
    $f["abtest.mistat.xiaomi.com"]=true;
    $f["abtest.swrve.com"]=true;
    $f["accesspoint-b-65wm.ap.spotify.com"]=true;
    $f["acdn.newshuntads.com"]=true;
    $f["aclickoooo.host"]=true;
    $f["ad*.nexage.com"]=true;
    $f["ad.allconnected.in"]=true;
    $f["ad.api.kaffnet.com"]=true;
    $f["ad.apps.fm"]=true;
    $f["ad.apsalar.com"]=true;
    $f["ad.cauly.co.kr"]=true;
    $f["ad.daum.net"]=true;
    $f["ad.doubleclick.net"]=true;
    $f["ad.duapps.com"]=true;
    $f["ad.flipboard.com"]=true;
    $f["ad.jorte.com"]=true;
    $f["ad.kixer.com"]=true;
    $f["ad.leadbolt.net"]=true;
    $f["ad.leadboltapps.net"]=true;
    $f["ad.madvertise.de"]=true;
    $f["ad.mail.ru"]=true;
    $f["ad.myinstashot.com"]=true;
    $f["ad.ohmyad.co"]=true;
    $f["ad.period-calendar.com"]=true;
    $f["ad.prismamediadigital.com"]=true;
    $f["ad.smaad.jp"]=true;
    $f["ad.vrvm.com"]=true;
    $f["ad.weplayer.cc"]=true;
    $f["ad1.adfarm1.adition.com"]=true;
    $f["ad120m.com"]=true;
    $f["ad127m.com"]=true;
    $f["ad132m.com"]=true;
    $f["ad2play.ftv-publicite.fr"]=true;
    $f["ad3.adfarm1.adition.com"]=true;
    $f["ad6media.fr"]=true;
    $f["adadvisor.net"]=true;
    $f["ad-analytics-bootstrap.metaps.com"]=true;
    $f["adback.tango.me"]=true;
    $f["adbetnet.advertserve.com"]=true;
    $f["adblade.com"]=true;
    $f["adbrau.com"]=true;
    $f["ad-brix.com"]=true;
    $f["adbsc.krmobi.com"]=true;
    $f["adbuddiz.com"]=true;
    $f["adcamp.ru"]=true;
    $f["adcash.com"]=true;
    $f["adcel.vrvm.com"]=true;
    $f["adcolony.com"]=true;
    $f["ad-creatives-public.commondatastorage.googleapis.com"]=true;
    $f["addapptr.com"]=true;
    $f["addng.com"]=true;
    $f["ade.clmbtech.com"]=true;
    $f["ade.googlesyndication.com"]=true;
    $f["adeventtracker.spotify.com"]=true;
    $f["adexchangedirect.com"]=true;
    $f["adexchangegate.com"]=true;
    $f["adextent.com"]=true;
    $f["adflake.com"]=true;
    $f["adforgames.com"]=true;
    $f["adform.com"]=true;
    $f["adfox.ru"]=true;
    $f["adfuture.cn"]=true;
    $f["adgear.com"]=true;
    $f["adgebra.co.in"]=true;
    $f["adgrx.com"]=true;
    $f["adhitzads.com"]=true;
    $f["adhood.com"]=true;
    $f["adinfo.tango.me"]=true;
    $f["adinfuse.com"]=true;
    $f["aditic.net"]=true;
    $f["adition.com"]=true;
    $f["adj.st"]=true;
    $f["adkmob.com"]=true;
    $f["adleads.com"]=true;
    $f["adlibr.com"]=true;
    $f["admail.am"]=true;
    $f["adman.gr"]=true;
    $f["admantx.com"]=true;
    $f["admarketing.yahoo.net"]=true;
    $f["admarvel.com"]=true;
    $f["admarvel.s3.amazonaws.com"]=true;
    $f["ad-maven.com"]=true;
    $f["admedia.com"]=true;
    $f["admicro1.vcmedia.vn"]=true;
    $f["admicro2.vcmedia.vn"]=true;
    $f["admin.appnext.com"]=true;
    $f["admitad.com"]=true;
    $f["admixer.co.kr"]=true;
    $f["admixer.net"]=true;
    $f["admob.com"]=true;
    $f["admulti.com"]=true;
    $f["adn.insight.ucweb.com"]=true;
    $f["adnxs.com"]=true;
    $f["adocean.pl"]=true;
    $f["adonly.com"]=true;
    $f["adops.cricbuzz.com"]=true;
    $f["adotsolution.com"]=true;
    $f["adotube.com"]=true;
    $f["adoxen.com"]=true;
    $f["adplatform.vrtcal.com"]=true;
    $f["adplay.vm5apis.com"]=true;
    $f["adprotected.com"]=true;
    $f["adpublisher.s3.amazonaws.com"]=true;
    $f["adpush.goforandroid.com"]=true;
    $f["adpushup.com"]=true;
    $f["adpxl.co"]=true;
    $f["adquota.com"]=true;
    $f["ads.ad2iction.com"]=true;
    $f["ads.addng.com"]=true;
    $f["ads.admarvel.com"]=true;
    $f["ads.admoda.com"]=true;
    $f["ads.adorca.com"]=true;
    $f["ads.aerserv.com"]=true;
    $f["ads.aitype.net"]=true;
    $f["ads.avocarrot.com"]=true;
    $f["ads.blbrd.co"]=true;
    $f["ads.cricbuzz.com"]=true;
    $f["ads.fotoable.com"]=true;
    $f["ads.glispa.com"]=true;
    $f["ads.jianchiapp.com"]=true;
    $f["ads.krmobi.com"]=true;
    $f["ads.marvel.com"]=true;
    $f["ads.matomymobile.com"]=true;
    $f["ads.mdotm.com"]=true;
    $f["ads.mobilefuse.net"]=true;
    $f["ads.mobilityware.com"]=true;
    $f["ads.mobvertising.net"]=true;
    $f["ads.mopub.com"]=true;
    $f["ads.ndtv1.com"]=true;
    $f["ads.n-ws.org"]=true;
    $f["ads.ookla.com"]=true;
    $f["ads.pdbarea.com"]=true;
    $f["ads.pinger.com"]=true;
    $f["ads.pubmatic.com"]=true;
    $f["ads.reward.rakuten.jp"]=true;
    $f["ads.taptapnetworks.com"]=true;
    $f["ads.tremorhub.com"]=true;
    $f["ads.xlxtra.com"]=true;
    $f["ads.yahoo.com"]=true;
    $f["ads.yemonisoni.com"]=true;
    $f["ads.youtube.com"]=true;
    $f["ads5.truecaller.com"]=true;
    $f["adsafeprotected.com"]=true;
    $f["adsame.com"]=true;
    $f["ads-api.ft.com"]=true;
    $f["adsby.bidtheatre.com"]=true;
    $f["adscale.de"]=true;
    $f["ads-chunks.prod.ihrhls.com"]=true;
    $f["adsdk.adfarm1.adition.com"]=true;
    $f["adsdk.vrvm.com"]=true;
    $f["adsee.jp"]=true;
    $f["adserver.*.yahoodns.net"]=true;
    $f["adserver.goforandroid.com"]=true;
    $f["adserver.kimia.es"]=true;
    $f["adserver.mobillex.com"]=true;
    $f["adserver.pandora.com"]=true;
    $f["adserver.shadow.snapads.com"]=true;
    $f["adserver.snapads.com"]=true;
    $f["adserver.ubiyoo.com"]=true;
    $f["adserver.unityads.unity3d.com"]=true;
    $f["adservice.google.co.in"]=true;
    $f["adservice.google.com"]=true;
    $f["adservice.google.de"]=true;
    $f["adservice.google.se"]=true;
    $f["adshost2.com"]=true;
    $f["adslot.uc.cn"]=true;
    $f["adsmo.ru"]=true;
    $f["adsmogo.mobi"]=true;
    $f["adsmogo.net"]=true;
    $f["adsmogo.org"]=true;
    $f["adsmoloco.com"]=true;
    $f["adsniper.ru"]=true;
    $f["adspirit.de"]=true;
    $f["adspruce.com"]=true;
    $f["adspynet.com"]=true;
    $f["adsrvmedia.net"]=true;
    $f["adsrvr.org"]=true;
    $f["ad-stir.com"]=true;
    $f["adsunflower.com"]=true;
    $f["adsx.greystripe.com"]=true;
    $f["adsymptotic.com"]=true;
    $f["adtag.sphdigital.com"]=true;
    $f["adtaily.pl"]=true;
    $f["adtech.de"]=true;
    $f["adtheorent.com"]=true;
    $f["adtilt.com"]=true;
    $f["adtrack.king.com"]=true;
    $f["adultadworld.com"]=true;
    $f["adups.com"]=true;
    $f["adv.mxmcdn.net"]=true;
    $f["adv.sec.miui.com"]=true;
    $f["advantech.vo.llnwd.net"]=true;
    $f["adversal.com"]=true;
    $f["adverticum.net"]=true;
    $f["advertise.com"]=true;
    $f["advertiser.fyber.com"]=true;
    $f["advertising.amazon.in"]=true;
    $f["advertising.com"]=true;
    $f["advertur.ru"]=true;
    $f["advmob.cn"]=true;
    $f["advombat.ru"]=true;
    $f["advonline.goforandroid.com"]=true;
    $f["advs2sonline.goforandroid.com"]=true;
    $f["adwhirl.com"]=true;
    $f["adwired.mobi"]=true;
    $f["adwods.com"]=true;
    $f["ad-x.co.uk"]=true;
    $f["adz.mobi"]=true;
    $f["adzerk.net"]=true;
    $f["adzmedia.com"]=true;
    $f["adzmobi.com"]=true;
    $f["adzworld.in"]=true;
    $f["aeon.co"]=true;
    $f["af201768865.com"]=true;
    $f["affinity.com"]=true;
    $f["affiz.net"]=true;
    $f["aff-policy.lbesecapi.com"]=true;
    $f["aff-report.lbesecapi.com"]=true;
    $f["afreeca-tag.ad-mapps.com"]=true;
    $f["aftonbladetnya.sc.omtrdc.net"]=true;
    $f["agent.tamedia.com.tw"]=true;
    $f["airpush.com"]=true;
    $f["aka-cdn.adtech.de"]=true;
    $f["allaboutberlin.com"]=true;
    $f["alog.umeng.co"]=true;
    $f["alog.umeng.com"]=true;
    $f["alog.umengcloud.com"]=true;
    $f["alogs.umeng.com"]=true;
    $f["alogs.umengcloud.com"]=true;
    $f["altitude-arena.com"]=true;
    $f["am15.net"]=true;
    $f["amadagasca.com"]=true;
    $f["amazon-adsystem.com"]=true;
    $f["amdc.m.taobao.com"]=true;
    $f["amoad.com"]=true;
    $f["amobee.com"]=true;
    $f["amp.permutive.com"]=true;
    $f["amp-error-reporting.appspot.com"]=true;
    $f["amptrack-dailymail-co-uk.cdn.ampproject.org"]=true;
    $f["an.batmobi.net"]=true;
    $f["an.facebook.com"]=true;
    $f["analy.qq.com"]=True;

    echo "Injecting ". count($f)." items.\n";
    $fam=new familysite();
    foreach ($f as $sitename=>$none){
        $familysite=$fam->GetFamilySites($sitename);
        if($familysite==$sitename){
            $cmdline="$echo \"AdsAndTracks:.$sitename\" >/proc/net/xt_ndpi/host_proto";
            if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
            shell_exec("$cmdline");
            continue;
        }

        $cmdline="$echo \"AdsAndTracks:$sitename\" >/proc/net/xt_ndpi/host_proto";
        if($GLOBALS["VERBOSE"]){echo "$cmdline\n";}
        shell_exec($cmdline);
    }


}