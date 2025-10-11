<?php
if(preg_match("#--verbose#",implode(" ",$argv),$re)){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=false;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

$GLOBALS["YESCGROUP"]=true;
$GLOBALS["DEBUG"]=false;
$GLOBALS["FORCE"]=false;
echo __LINE__."\n";
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.catz.inc');
include_once(dirname(__FILE__).'/ressources/class.hosts.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.acls.useragents.inc');
include_once(dirname(__FILE__).'/ressources/class.statscom-msmtp.inc');
include_once(dirname(__FILE__).'/ressources/class.html.tools.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');

if(isset($argv[1])){
    if($argv[1]=="--main"){build_maintenance();exit;}
    if($argv[1]=="--days"){die();}
    if($argv[1]=="--entities"){entities();exit;}
    if($argv[1]=="--catz"){Nocategorized();exit;}
    if($argv[1]=="--users"){build_users_days();exit;}
    if($argv[1]=="--test-smtp"){test_smtp();exit;}
    if($argv[1]=="--set-schedules"){schedules();}
    if($argv[1]=="--scan"){scanqueue();exit;}
    if($argv[1]=="--scan-sites"){scanqueue_sites();exit;}
    if($argv[1]=="--syslog"){exit;}
    if($argv[1]=="--unbound"){scanunboundfile($argv[2]);exit;}
    if($argv[1]=="--dnsdist"){scandndistfile($argv[2]);exit;}
    if($argv[1]=="--verbose"){scanqueue();exit;}
    if($argv[1]=="--force"){scanqueue();exit;}
    if(!$GLOBALS["VERBOSE"]) {
        exit;
    }
}

echo "Start:".__LINE__."\n";
scanqueue();


function build_maintenance(){
    $GLOBALS["VERBOSE"]=true;
    $q=new postgres_sql();
    if(!$q->CREATE_STATSCOM()){
        echo "error:".$q->mysql_error."\n";
    }

}

function schedules(){
    $unix=new unix();
    if (!$handle = opendir("/etc/cron.d")) {return false;}

    build_progress_reports(25,"{removing}..");

    while (false !== ($file = readdir($handle))) {
        if ($file == "." OR $file == "..") {continue;}
        if(substr($file, 0,1)=='.'){continue;}
        $fullpath="/etc/cron.d/$file";
        if(is_dir($file)){continue;}

        if(!preg_match("#^pdf-proxy-report-[0-9]+#",$file)){continue;}
        echo "Remove $file\n";
        @unlink($fullpath);
    }
    build_progress_reports(50,"{configuring}..");

    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $results=$q->QUERY_SQL("SELECT * FROM pdf_reports WHERE enabled=1");
    foreach ($results as $index=>$ligne){
        $ID=$ligne["ID"];
        $TimeText=$ligne["TimeText"];
        $unix->Popuplate_cron_make("pdf-proxy-report-$ID",$TimeText,"exec.pdf.proxy.daily.php --schedule $ID");


    }

    build_progress_reports(75,"{restarting}..");
    shell_exec("/etc/init.d/cron/restart");
    build_progress_reports(100,"{done}..");
}






function test_smtp(){


    $smtp=new statscom_msmtp();
    $recipient=$smtp->recipient;
    build_progress_smtp(50,"Send to $recipient");
    $smtp->Subject="Subject: Test notification PDF Report engine";
    $body[]="";
    $body[]="";
    $body[]="Here, the message from the robot...";
    $body[]="";
    $body[]="";
    $finalbody=@implode("\r\n", $body);
    $smtp->debug=true;

    if( !$smtp->Send($finalbody)){
        build_progress_smtp(110,"{failed} see logs");
        echo "$smtp->smtp_error\n";
        return;
    }
    build_progress_smtp(100,"Send to $recipient {success}");
}
function build_progress_smtp($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile=PROGRESS_DIR."/statscom.smtp.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}
function build_progress_reports($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $ARRAY["PROGRESS_FILE"]=PROGRESS_DIR."/statscom.report.progres";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($ARRAY["PROGRESS_FILE"], serialize($array));
    @chmod($ARRAY["PROGRESS_FILE"],0755);
}



function scanqueue_sites(){
    $BaseWorkDir="/home/artica/StatsComQueue";
    if(!is_dir($BaseWorkDir)){return;}

    $q=new postgres_sql();
    $q->CREATE_USERAGENT();
    if(!$q->ok){
        echo $q->mysql_error."\n";
        return false;
    }

    $BLOCK=array();
    $UNBOUND=array();
    $DNSDIST=array();
    if (!$handle = opendir($BaseWorkDir)) {return;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        $targetFile="$BaseWorkDir/$filename";

        if(preg_match("#\.unbound$#",$filename)){
            $UNBOUND[]=$targetFile;
            continue;
        }

        if(preg_match("#\.dnsdist$#",$filename)){
            $DNSDIST[]=$targetFile;
            continue;
        }
    }

    foreach ($UNBOUND as $unbundfile){
        scanunboundfile($unbundfile);

    }

    foreach ($DNSDIST as $dnsdistfile){
        scandndistfile($dnsdistfile);
    }

    return true;

}

function inject_unbound($f){
    $prefix="INSERT INTO statscom_dns (zdate,ipaddr,siteid,entityid,query,response,hits) VALUES ";
    if(count($f)==0){return true;}
    if(!isset($GLOBALS["COUNT_ENTRIES"])){$GLOBALS["COUNT_ENTRIES"]=0;}
    $GLOBALS["COUNT_ENTRIES"]=$GLOBALS["COUNT_ENTRIES"]+count($f);
    $q=new postgres_sql();
    $q->QUERY_SQL($prefix.@implode(",",$f));

    if(!$q->ok){
        events("statscom failed with error $q->mysql_error",__LINE__);
        squid_admin_mysql(1,"statscom failed with error $q->mysql_error",$q->mysql_error,__FILE__,__LINE__);
        return false;
    }
    return true;

}

function scandndistfile($filepath){
    if(!isset($GLOBALS["COUNT_ENTRIES"])){$GLOBALS["COUNT_ENTRIES"]=0;}
    events("Parsing $filepath",__LINE__);
    $data=@file_get_contents($filepath);
    $json=json_decode($data);
    $f=array();

    foreach ($json as $xtime=>$array_uuid) {
        $zdate = date("Y-m-d H:i:00", $xtime);
        foreach ($array_uuid as $uuid => $array_query) {
            $entityid = get_entityid($uuid);
            if ($entityid == 0) {
                events("Unable to get entity for $uuid", __LINE__);
                return false;
            }
            foreach ($array_query as $query => $array_domain) {
                foreach ($array_domain as $domain => $array_action) {
                    $siteid = get_siteid($domain);
                    foreach ($array_action as $ruleid => $array_ruleid) {
                        foreach ($array_ruleid as $rulename => $array_rulename) {
                            update_dnsrule($ruleid,$rulename);
                            foreach ($array_rulename as $ipaddr => $queries) {
                                $f[]="('$zdate','$ipaddr','$siteid','$entityid','$query','$ruleid','$queries')";
                                $GLOBALS["COUNT_ENTRIES"]=$GLOBALS["COUNT_ENTRIES"]+1;
                                if(count($f)>2000){
                                    if(!inject_dnsdist($f)){return false;}
                                    $f=array();
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    if(!inject_dnsdist($f)){return false;}
    @unlink($filepath);
    return true;
}

function inject_dnsdist($array){
    if(count($array)==0){return true;}

    $prefix="INSERT INTO statscom_dnsfw (zdate,ipaddr,siteid,entityid,query,ruleid,hits) VALUES";

    $sql=$prefix.@implode(",",$array);

    $q=initq();
    $q->QUERY_SQL($sql);
    if(!$q->ok){
        events("inject_dnsdist: $q->mysql_error",__LINE__);
        return false;
    }

    return true;

}

function initq(){
    if(!isset($GLOBALS["Q"])) {
        $GLOBALS["Q"] = new postgres_sql();
        return $GLOBALS["Q"];
    }

    if(!method_exists($GLOBALS["Q"],"mysqli_fetch_array")){
        $GLOBALS["Q"] =new postgres_sql();
        return $GLOBALS["Q"];
    }
    if(!method_exists($GLOBALS["Q"],"QUERY_SQL")){
        $GLOBALS["Q"]=new postgres_sql();
    }


    return $GLOBALS["Q"];

}

function update_dnsrule($ruleid,$rulename){
    $ruleid=intval($ruleid);
    if($ruleid==0){return true;}
    if(isset( $GLOBALS["update_dnsrule:$rulename:$ruleid"])){return true;}
    $q=initq();

    $ligne=$q->mysqli_fetch_array("SELECT id, rulename from statscom_dnsfwrules WHERE id='$ruleid'");
    $id=intval($ligne["id"]);
    if($id==0){
        $q->QUERY_SQL("INSERT INTO statscom_dnsfwrules (id,rulename) VALUES('$ruleid','$rulename')");
        $GLOBALS["update_dnsrule:$rulename:$ruleid"]=true;
        return true;
    }
    $q->QUERY_SQL("UPDATE statscom_dnsfwrules SET rulename='$rulename' WHERE id='$id'");
    $GLOBALS["update_dnsrule:$rulename:$ruleid"]=true;
    return true;
}

function scanunboundfile($filepath){
    events("Parsing $filepath",__LINE__);
    $data=@file_get_contents($filepath);
    $json=json_decode($data);
    $f=array();

    foreach ($json as $xtime=>$array_uuid){
        $zdate=date("Y-m-d H:i:00",$xtime);
        foreach ($array_uuid as $uuid=>$array_query){
            $entityid=get_entityid($uuid);
            if($entityid==0){
                events("Unable to get entity for $uuid",__LINE__);
                return false;
            }
            foreach ($array_query as $query=>$array_domain){
                foreach ($array_domain as $domain=>$array_action) {
                    $siteid=get_siteid($domain);
                    foreach ($array_action as $action=>$array_ip) {
                        foreach ($array_action as $action2=>$array_action2) {
                            foreach ($array_action2 as $ipsrc=>$requests) {
                                $line="('$zdate','$ipsrc','$siteid','$entityid','$query','$action','$requests')";
                                $f[]=$line;
                                if(count($f)>2000){
                                    if(!inject_unbound($f)){return false;}
                                    $f=array();
                                }

                            }
                        }
                    }
                }
            }
        }
    }

    if(count($f)>0){
        if(!inject_unbound($f)){
            events("$filepath Failed");
            return false;}
    }

    @unlink($filepath);
    return true;

}

function scanqueue(){
    if($GLOBALS["VERBOSE"]){echo __FUNCTION__."\n";}
    $unix=new unix();
    $ftime="/etc/artica-postfix/pids/exec.statscom-stats.php.scanqueue.time";
    $pidfile="/etc/artica-postfix/pids/exec.statscom-stats.php.scanqueue.pid";

    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){
        events("Already Process $pid is executed",__LINE__);
        return false;
    }
    $exec_time=$unix->file_time_min($ftime);
    if(!$GLOBALS["FORCE"]) {
        if ($exec_time < 10) {
            return false;
        }
    }
    @unlink($ftime);
    @file_put_contents($ftime,time());
    @file_put_contents($pidfile,getmypid());

    $q=new postgres_sql();
    if(!$q->CREATE_STATSCOM()){
        if($GLOBALS["VERBOSE"]){echo "Error creating statscom tables $q->mysql_error\n";}
        events("Error creating statscom tables",__LINE__);
        events($q->mysql_error,__LINE__);
        squid_admin_mysql(1,"Error creating statscom tables",$q->mysql_error,__FILE__,__FUNCTION__);
        return false;
    }

    $q->QUERY_SQL("ALTER TABLE statscom ALTER COLUMN username TYPE varchar(90);");
    $q->QUERY_SQL("ALTER TABLE statscom_husers ALTER COLUMN username TYPE varchar(90);");
    $q->QUERY_SQL("ALTER TABLE statscom_users ALTER COLUMN username TYPE varchar(90);");
    $q->QUERY_SQL("ALTER TABLE statsblocks ALTER COLUMN username TYPE varchar(90);");
    $q->QUERY_SQL("ALTER TABLE statscom_days ALTER COLUMN username TYPE varchar(90);");


    $BaseWorkDir="/home/artica/StatsComQueue";
    if(!is_dir($BaseWorkDir)){
        @mkdir($BaseWorkDir,0755,true);
    }

    scanqueue_sites();
    return true;
}





function import_users($USERS){
    $q=new postgres_sql();
    $fu=array();
    foreach ($USERS as $md5=>$row){
        $fu[]=$row;
        if(count($fu)>500){
            $q->QUERY_SQL("INSERT INTO statscom_users (zmd5,username,ipaddr,mac) VALUES ".@implode(",",$fu). " ON CONFLICT DO NOTHING");
            if(!$q->ok){echo $q->mysql_error."\n";}
            $fu=array();
        }

    }

    if(count($fu)>0){
        $q->QUERY_SQL("INSERT INTO statscom_users (zmd5,username,ipaddr,mac) VALUES ".@implode(",",$fu). " ON CONFLICT DO NOTHING");
        if(!$q->ok){echo $q->mysql_error."\n";}
    }
}



function get_entityid($uuid,$proxname=null){

    if(!preg_match("#^[0-9a-z]+-[0-9a-z]+-[0-9a-z]+-[0-9a-z]+-[0-9a-z]+#",$uuid)){
        events("get_entityid:: $uuid is a wrong pattern",__LINE__);
        return 0;
    }

    if(isset($GLOBALS["get_entityid"][$uuid])){
        return intval($GLOBALS["get_entityid"][$uuid]);
    }
    $q=initq();
    $ligne=$q->mysqli_fetch_array("SELECT entityid from statscom_entity WHERE entityname='$uuid'");
    $siteid=intval($ligne["entityid"]);
    if($siteid==0) {
        if($proxname<>null) {
            $q->QUERY_SQL("INSERT INTO statscom_entity(entityname,entitylabel) VALUES ('$uuid','$proxname')");
            $ligne = $q->mysqli_fetch_array("SELECT entityid from statscom_entity WHERE entityname='$uuid'");
            $siteid = intval($ligne["entityid"]);
        }

    }
    if($siteid>0) {
        $GLOBALS["get_entityid"][$uuid] = $siteid;
        return intval($GLOBALS["get_entityid"][$uuid]);
    }
    return 0;
}

function get_siteid($sitename,$familysite=null,$category=0){

    if(trim($sitename)==null){return 0;}
    if(is_numeric($sitename)){return 0;}

    if(isset($GLOBALS["get_siteid"][$sitename])){
        return intval($GLOBALS["get_siteid"][$sitename]);
    }
    $q=initq();

    $ligne=$q->mysqli_fetch_array("SELECT siteid from statscom_websites WHERE sitename='$sitename'");

    if(!$q->ok){
        echo $q->mysql_error."\n";
        $ligne=array();
    }

    if($familysite==null){
        $fam=new squid_familysite();
        $familysite=$fam->GetFamilySites($sitename);
    }
    if(!$q->FIELD_EXISTS("statscom_websites","lastseen")){
        $q->QUERY_SQL("ALTER TABLE statscom_websites ADD lastseen bigint default 0");
        $q->create_index("statscom_websites","idx_lastseen",array("lastseen"));
    }
    if(!$ligne){
        $ligne=array();
    }
    if(!isset($ligne["siteid"])){$ligne["siteid"]=0;}

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

function build_users_days(){
    $q=new postgres_sql();
    $sql="SELECT username,ipaddr,mac FROM statscom_days GROUP BY username,ipaddr,mac";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";return;}
    while ($ligne = pg_fetch_assoc($results)) {
        $username=trim($ligne["username"]);
        $username=str_replace("'","`",$username);
        $ipaddr=trim($ligne["ipaddr"]);
        $mac=trim($ligne["mac"]);
        $md5_user=md5("$username$ipaddr$mac");
        echo "('$md5_user','$username','$ipaddr','$mac')\n";
        $USERS[$md5_user]="('$md5_user','$username','$ipaddr','$mac')";
    }
    import_users($USERS);
}





function entities(){
    $q=new postgres_sql();

    $sql="SELECT entityid FROM statscom_entity";
    $results=$q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error."\n";return;}
    while ($ligne = pg_fetch_assoc($results)) {
        $entityid=$ligne["entityid"];
        echo "Update entity ID $entityid\n";
        $ligne2=$q->mysqli_fetch_array("SELECT SUM(size) as size, SUM(hits) as hits FROM statscom_days WHERE entityid='$entityid'");
        if(!$q->ok){echo $q->mysql_error."\n";return;}

        $hits=$ligne2["hits"];
        $size=$ligne2["size"];
        $q->QUERY_SQL("UPDATE statscom_entity SET hits=$hits,size=$size WHERE entityid='$entityid'");
        if(!$q->ok){echo $q->mysql_error."\n";return;}
    }
}



function Nocategorized(){
    $q=new postgres_sql();
    $unix=new unix();
    $catz=new mysql_catz();
    $catz->NoNegativeCache=true;
    $c=0;
    $unix->Popuplate_cron_make("statscom-categorize","30 23 * * *","exec.statscom-stats.php --catz");
    $memcache=new lib_memcached();

    $results=$q->QUERY_SQL("SELECT sitename,siteid FROM statscom_websites WHERE category=0");
    while ($ligne = pg_fetch_assoc($results)) {
        $siteid=intval($ligne["siteid"]);
        $sitename=$ligne["sitename"];
        $category=$catz->GET_CATEGORIES($sitename);
        if($category>0){
            $c++;
            $key_not_categorized="notcategorized.".strtolower($sitename);
            $memcache->Delkey($key_not_categorized);
            $text[]="$sitename categorized in ".$catz->CategoryIntToStr($category);
            $q->QUERY_SQL("UPDATE statscom_websites SET category=$category WHERE siteid='$siteid'");
            continue;
        }

        echo "$sitename Unknown...\n";

    }

    if($c>0){
        $smtp=new statscom_msmtp();
        $recipient=$smtp->recipient;
        if($recipient<>null){
            $smtp->Subject="$c unknown websites fixed and categorized";
            $smtp->Send(@implode("\r\n",$text));
        }

        squid_admin_mysql(1,"$c unknown websites fixed and categorized",null,__FILE__,__LINE__);
    }
}


function transfert_day($day){
    $USERS=array();
    $q=new postgres_sql();
    $CurrentDay=date("Y-m-d");
    if($day==$CurrentDay){return false;}

    if(!$q->CREATE_STATSCOM()){
        echo "error:".$q->mysql_error."\n";
        return;
    }


    $sql="SELECT date(zdate) as xdate,username,ipaddr,mac,siteid,entityid,SUM(hits) as hits,SUM(size) as size FROM statscom WHERE date(zdate)='$day' GROUP BY xdate,username,ipaddr,mac,siteid,entityid";
    $f=array();
    $results=$q->QUERY_SQL($sql);

    $prefix="INSERT INTO statscom_days (zdate,username,ipaddr,mac,siteid,entityid,size,hits) VALUES ";


    if(!$q->ok){echo $q->mysql_error."\n";return false;}
    while ($ligne = pg_fetch_assoc($results)) {
        $zdate=$ligne["xdate"];
        $username=trim($ligne["username"]);
        $username=str_replace("'","`",$username);
        $ipaddr=trim($ligne["ipaddr"]);
        $mac=trim($ligne["mac"]);
        $siteid=intval($ligne["siteid"]);
        $entityid=intval($ligne["entityid"]);
        $size=intval($ligne["size"]);
        $hits=intval($ligne["hits"]);
        $md5_user=md5("$username$ipaddr$mac");
        $USERS[$md5_user]="('$md5_user','$username','$ipaddr','$mac')";

        $line="('$zdate','$username','$ipaddr','$mac','$siteid','$entityid','$size','$hits')";
        $f[]=$line;

        if(count($f)>5000){
            $q->QUERY_SQL($prefix.@implode(",",$f));
            if(!$q->ok){
                echo $q->mysql_error;
                return false;
            }

            $f=array();
        }

    }

    if(count($f)>0){
        $q->QUERY_SQL($prefix.@implode(",",$f));
        if(!$q->ok){
            echo $q->mysql_error;
            return false;
        }
    }

    import_users($USERS);

    return true;

}


function events($text,$line=0):bool{
    $unix=new unix();
    $file=basename(__FILE__);
    return $unix->ToSyslog("$file $text in line $line","stats-communicator");
}

