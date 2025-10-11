<?php
$GLOBALS["FORCE"]=false;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.familysites.inc');
if(is_array($argv)){
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
}
if($GLOBALS["VERBOSE"]){
	ini_set('display_errors', 1);
	ini_set('html_errors',0);
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
    $GLOBALS["FORCE"]=true;
}
if($argv[1]=="--domains"){DomainsTable();exit;}
if($argv[1]=="--sum"){AnalyzeSums();exit;}

xrun_members();


function xrun_members(){

    $unix = new unix();
    $pidfile = "/etc/artica-postfix/pids/SquidStatsHourlyQueue.pid";
    $pidTime = "/etc/artica-postfix/pids/SquidStatsHourlyQueue.time";
    $tpl = new template_admin();
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        die("Already executed");
    }

    @file_put_contents($pidfile, getmypid());
    $timeExec = $unix->file_time_min($pidTime);

    if (!$GLOBALS["FORCE"]) {
        if ($timeExec < 15) {
            die("Only Each 15mn");
        }
    }
    @unlink($pidfile);
    @file_put_contents($pidfile, time());


    $q = new postgres_sql();
    $q->CREATE_TABLES();
    if ($GLOBALS["VERBOSE"]) {
        echo "--------------------------------------------------------[" . __LINE__ . "]\n";
    }
    if ($q->isRemote) {
        echo "Is remote == yes, aborting...\n";
        return;
    }

    // Table access users : access_users

    $redis = new Redis();
    try {
        $redis->connect('/var/run/redis/redis.sock');
    } catch (Exception $e) {
        if ($GLOBALS["VERBOSE"]) {
            echo $e->getMessage() . "\n";
        }
        squid_admin_mysql(0, "Redis connection error", $e->getMessage(), __FILE__, __LINE__);
        exit;
    }


    $CurrentKey = $tpl->time_key_10mn();

    $iterator = null;
    $SearchPattern = "WebStats:*:CurrentUser:*";
    if ($GLOBALS["VERBOSE"]) {echo "Scan keys...\n";}


    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

    $MAINKEY_TO_DELETE=array();
    $SQL_ARRAY=array();
    while ($keys = $redis->scan($iterator, $SearchPattern)) {

        foreach ($keys as $key) {
            if (preg_match("#$CurrentKey#", $key)) {
                if ($GLOBALS["VERBOSE"]) {echo "Aborting key $key\n";}
                continue;
            }
            if (!preg_match("#^WebStats:([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+):CurrentUser:(Size|RQS):(.+)#", $key, $re)) {
                echo "$key abort\n";
                continue;
            }
            $MAINKEY_TO_DELETE[] = $key;
            $ztime = strtotime("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}");
            $zDate = date("Y-m-d H:i:00", $ztime);
            $Type = $re[6];
            $User = $re[7];
            $value = $redis->get($key);
            $SQL_ARRAY[$zDate][$User][$Type] = $value;

        }
    }


    if(count($SQL_ARRAY)==0){
        echo "Members: No Data, skip\n";
        $redis->close();
        RunOtherFunctions();
        return;
    }

    $f = array();
    foreach ($SQL_ARRAY as $zDate => $array) {
        foreach ($array as $Member => $ztype) {
            $f[] = "('$zDate','$Member','{$ztype["Size"]}','{$ztype["RQS"]}')";

        }

    }


    if (count($f) > 0) {
        $q->QUERY_SQL("INSERT INTO access_users (zdate,userid,size,rqs) VALUES " . @implode(",", $f));
        if (!$q->ok) {
            squid_admin_mysql(1,"PostGreSQL error", $q->mysql_error, __FILE__, __LINE__);
            return false;
        }
    }

    if(count($MAINKEY_TO_DELETE)>0) {
        echo "Members: Delete ".count($MAINKEY_TO_DELETE)." keys\n";
        $redis->del($MAINKEY_TO_DELETE);
    }

    $redis->close();

    RunOtherFunctions();
    return true;

}

function RunOtherFunctions(){
    CleanTempCurrentUserIPKeys();
    DomainsTable();
    AnalyzeSums();
}


function DomainsTable(){
    $tpl = new template_admin();
    $CurrentKey = $tpl->time_key_10mn();
    $SearchPattern = "WebStats:*:Domains:*";
    $QFam = new squid_familysite();
    $qprox = new mysql_squid_builder();

    $redis = new Redis();
    $redis->connect('/var/run/redis/redis.sock');

    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

    $MAINKEY_TO_DELETE = array();
    $SQL_ARRAY = array();
    while ($keys = $redis->scan($iterator, $SearchPattern)) {

        foreach ($keys as $key) {
            $Size = 0;
            if (preg_match("#$CurrentKey#", $key)) {
                continue;
            }
            if (!preg_match("#^WebStats:([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+):Domains:(.+?):(Size|Hits|Users)#", $key, $re)) {
                echo "$key abort\n";
                continue;
            }

            $MAINKEY_TO_DELETE[] = $key;
            $ztime = strtotime("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}");
            $zDate = date("Y-m-d H:i:00", $ztime);
            $Domain = $QFam->GetFamilySites($re[6]);
            $domainid = $qprox->DomainToInt($Domain);
            $Type = $re[7];


            if ($Type == "Size") {
                $value = intval($redis->get($key));
                if ($value == 0) {
                    continue;
                }
                if (isset($SQL_ARRAY[$zDate][$domainid]["SIZE"])) {
                    $SQL_ARRAY[$zDate][$domainid]["SIZE"] = intval($SQL_ARRAY[$zDate][$domainid]["SIZE"]) + $value;
                } else {
                    $SQL_ARRAY[$zDate][$domainid]["SIZE"] = $value;
                }
            }
            if ($Type == "Hits") {
                $value = intval($redis->get($key));
                if ($value == 0) {
                    continue;
                }
                if (isset($SQL_ARRAY[$zDate][$domainid]["HITS"])) {
                    $SQL_ARRAY[$zDate][$domainid]["HITS"] = intval($SQL_ARRAY[$zDate][$domainid]["HITS"]) + $value;
                } else {
                    $SQL_ARRAY[$zDate][$domainid]["HITS"] = $value;
                }
            }

            if ($Type == "Users") {
                $value = count($redis->sMembers($key));
                if (isset($SQL_ARRAY[$zDate][$domainid]["MEMBERS"])) {
                    if ($value > $SQL_ARRAY[$zDate][$domainid]["MEMBERS"]) {
                        $SQL_ARRAY[$zDate][$domainid]["MEMBERS"] = $value;
                    }
                } else {
                    $SQL_ARRAY[$zDate][$domainid]["MEMBERS"] = $value;
                }
            }


        }

    }


    $f = array();
    foreach ($SQL_ARRAY as $zDate => $array) {
        foreach ($array as $domainid => $main) {
            if (!isset($main["SIZE"])) {
                continue;
            }
            $f[] = "('$zDate','$domainid','{$main["HITS"]}','{$main["SIZE"]}','{$main["MEMBERS"]}')";
        }

    }


    if (count($f) > 0) {
        $q = new postgres_sql();
        $q->QUERY_SQL("INSERT INTO domains_access (zdate,domainid,hits,size,users) VALUES " . @implode(",", $f));
        if (!$q->ok) {
            squid_admin_mysql(1,"PostGreSQL error", $q->mysql_error, __FILE__, __LINE__);
            return false;
        }
    }

    if (count($MAINKEY_TO_DELETE) > 0) {
        echo "Websites: Delete " . count($MAINKEY_TO_DELETE) . " keys\n";
        $redis->del($MAINKEY_TO_DELETE);
    }

    $redis->close();
    return true;
}


function AnalyzeSums()
{


    $tpl = new template_admin();
    $CurrentKey = $tpl->time_key_10mn();
    $SearchPattern = "WebStats:*:CurrentDomains";


    $redis = new Redis();
    $redis->connect('/var/run/redis/redis.sock');
    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
    $iterator = null;
    $MAINKEY_TO_DELETE = array();
    $SQL_ARRAY = array();
    while ($keys = $redis->scan($iterator, $SearchPattern)) {

        foreach ($keys as $key) {
            if (preg_match("#$CurrentKey#", $key)) {
                continue;
            }
            if (!preg_match("#^WebStats:([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+):CurrentDomains#", $key, $re)) {
                echo "$key abort (not matches)\n";
                continue;
            }
            $MAINKEY_TO_DELETE[] = $key;
            $ztime = strtotime("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}");
            $zDate = date("Y-m-d H:i:00", $ztime);
            $DomainsCount = count($redis->sMembers($key));
            $SQL_ARRAY[$zDate]["DOMAINS"] = $DomainsCount;
            echo "$zDate $DomainsCount domains\n";
        }
    }
    $redis->close();
    AnalyzeSums_HITS($SQL_ARRAY,$MAINKEY_TO_DELETE,$CurrentKey);
}
function AnalyzeSums_HITS($SQL_ARRAY,$MAINKEY_TO_DELETE,$CurrentKey){
    $keys=array();
    $redis = new Redis();
    $redis->connect('/var/run/redis/redis.sock');
    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
    $iterator=null;

    $SearchPattern = "WebStats:*:TotalHits";
    while ($keys = $redis->scan($iterator, $SearchPattern)) {
        foreach ($keys as $key) {
            if (preg_match("#$CurrentKey#", $key)) {
                continue;
            }
            if (!preg_match("#^WebStats:([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+):TotalHits#", $key, $re)) {
                echo "$key abort ($SearchPattern)\n";
                continue;
            }

            $MAINKEY_TO_DELETE[] = $key;
            $ztime = strtotime("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}");
            $zDate = date("Y-m-d H:i:00", $ztime);
            $HITS = $redis->get($key);
            $SQL_ARRAY[$zDate]["HITS"] = $HITS;
        }

    }

    $keys=array();
    $iterator=null;
    $SearchPattern = "WebStats:*:TotalSize";
    while ($keys = $redis->scan($iterator, $SearchPattern)) {
        foreach ($keys as $key) {
            if (preg_match("#$CurrentKey#", $key)) {
                continue;
            }
            if (!preg_match("#^WebStats:([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+)-([0-9]+):TotalSize#", $key, $re)) {
                echo "$key abort ($SearchPattern)\n";
                continue;
            }

            $MAINKEY_TO_DELETE[] = $key;
            $ztime = strtotime("{$re[1]}-{$re[2]}-{$re[3]} {$re[4]}:{$re[5]}");
            $zDate = date("Y-m-d H:i:00", $ztime);
            $SIZE = $redis->get($key);
            $SQL_ARRAY[$zDate]["SIZE"] = $SIZE;
        }
    }

    foreach ($SQL_ARRAY as $zdate=>$MAIN){

        $DOMAINS=$MAIN["DOMAINS"];
        if(!isset($MAIN["HITS"])){continue;}
        if(!isset($MAIN["SIZE"])){continue;}
        $HITS=$MAIN["HITS"];
        $SIZE=$MAIN["SIZE"];
        $f[]="('$zdate',$DOMAINS,$HITS,$SIZE)";



    }

    if(count($f)>0) {
        $q = new postgres_sql();
        if (!$q->TABLE_EXISTS("bandwidth_table")) {
            $q->CREATE_TABLES();
        }
        $sql = "INSERT INTO bandwidth_table( zdate ,sites,hits,size) VALUES " . @implode(",", $f)." ON CONFLICT DO NOTHING";
        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error;
            squid_admin_mysql(0, "PostgreSQL Error: $q->mysql_error", null, __FILE__, __LINE__);
            return false;
        }
    }

    if(count($MAINKEY_TO_DELETE)>0) {
        echo "Websites: Delete ".count($MAINKEY_TO_DELETE)." keys\n";
        $redis->del($MAINKEY_TO_DELETE);
    }

    $redis->close();
    return true;


}

function CleanTempCurrentUserIPKeys(){

    $tpl = new template_admin();
    $CurrentKey = $tpl->time_key_10mn();

    $redis=new Redis();
    $redis->connect('/var/run/redis/redis.sock');
    $redis->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);

    $iterator=null;
    $SearchPattern="WebStats:*:CurrentUserIP:*";
    while ($keys = $redis->scan($iterator, $SearchPattern)) {
        foreach ($keys as $key) {
            if (preg_match("#$CurrentKey#", $key)) {
                continue;
            }
            if($GLOBALS["VERBOSE"]){echo "Removing $key\n";}
            $redis->del($key);
        }
    }

}



