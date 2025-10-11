<?php
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["SCHEDULE_ID"]=0;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}



include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__)."/ressources/class.os.system.tools.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.booster.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.watchdog.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.catz.inc");

if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;
	$GLOBALS["debug"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
}
if(isset($argv[1])){
    if($argv[1]=="--idns"){idns();exit;}
}

$unix=new unix();
$pidfile = "/etc/artica-postfix/pids/" . basename(__FILE__) . "." . __FUNCTION__ . ".pid";

$unix=new unix();
if(!$GLOBALS["FORCE"]) {
    $execTime = $unix->file_time_min($pidfile);
    if ($execTime < 3) {
        die();
    }
    $pid = $unix->get_pid_from_file($pidfile);
    if ($unix->process_exists($pid, basename(__FILE__))) {
        die();
    }
}
@unlink($pidfile);
@file_put_contents($pidfile,getmypid());

client_list();


function client_list(){
    $unix=new unix();
    $MAIN = array();
    $data = $unix->squidclient("client_list");
    if ($data == null) {
        return false;
    }
    $f = explode("\n", $data);
    $IPADDR = null;

    foreach ($f as $index => $line) {
        $line = trim($line);
        if ($line == null) {
            continue;
        }

        if (preg_match("#ICP\s+Requests#", $line, $re)) {

            continue;

        }

        if (preg_match("#Address:\s+([0-9\.]+)#", $line, $re)) {
            $IPADDR = $re[1];
            continue;

        }

        if (preg_match("#Currently established connections:\s+([0-9]+)#", $line, $re)) {
            $MAIN[$IPADDR]["CUR_CNX"] = $re[1];
            continue;
        }

        if (preg_match("#HTTP Requests\s+([0-9]+)#", $line, $re)) {
            $MAIN[$IPADDR]["RQS"] = $re[1];
            continue;
        }


        if (preg_match("#TAG_NONE\s+([0-9]+)#", $line, $re)) {
            $MAIN[$IPADDR]["TAG_NONE"] = $re[1];
            continue;
        }
        if (preg_match("#TCP_REDIRECT\s+([0-9]+)#", $line, $re)) {
            $MAIN[$IPADDR]["TCP_REDIRECT"] = $re[1];
            continue;
        }


        if (preg_match("#TCP_HIT\s+([0-9]+)#", $line, $re)) {
            $MAIN[$IPADDR]["TCP_HIT"] = $re[1];
            continue;
        }
        if (preg_match("#TCP_MISS\s+([0-9]+)#", $line, $re)) {
            $MAIN[$IPADDR]["TCP_MISS"] = $re[1];
            continue;
        }
        if (preg_match("#TCP_REFRESH_UNMODIFI\s+([0-9]+)#", $line, $re)) {
            if (!isset($MAIN[$IPADDR]["TCP_HIT"])) {
                $MAIN[$IPADDR]["TCP_HIT"] = 0;
            }
            $MAIN[$IPADDR]["TCP_HIT"] = $MAIN[$IPADDR]["TCP_HIT"] + intval($re[1]);
            continue;
        }
        if (preg_match("#TCP_REFRESH_MODIFIED\s+([0-9]+)#", $line, $re)) {
            if (!isset($MAIN[$IPADDR]["TCP_HIT"])) {
                $MAIN[$IPADDR]["TCP_HIT"] = 0;
            }
            $MAIN[$IPADDR]["TCP_HIT"] = $MAIN[$IPADDR]["TCP_HIT"] + intval($re[1]);
            continue;
        }
        if (preg_match("#TCP_SWAPFAIL_MISS\s+([0-9]+)#", $line, $re)) {
            if (!isset($MAIN[$IPADDR]["TAG_NONE"])) {
                $MAIN[$IPADDR]["TAG_NONE"] = 0;
            }
            $MAIN[$IPADDR]["TAG_NONE"] = $MAIN[$IPADDR]["TAG_NONE"] + intval($re[1]);
            continue;
        }


        if (preg_match("#TCP_TUNNEL\s+([0-9]+)#", $line, $re)) {
            $MAIN[$IPADDR]["TCP_TUNNEL"] = $re[1];
            continue;
        }

        if (preg_match("#Name:\s+(.+)#", $line, $re)) {
            echo "Uid {$re[1]}\n";
            $MAIN[$IPADDR]["uid"] = trim($re[1]);
            continue;
        }


    }

    if (count($MAIN) == 0) {
        return;
    }
    if (is_file("/home/artica/SQLITE/mgr_client_list.db")) {
        @unlink("/home/artica/SQLITE/mgr_client_list.db");
    }

    $q = new lib_sqlite("/home/artica/SQLITE/mgr_client_list.db");
    @chmod("/home/artica/SQLITE/mgr_client_list.db", 0644);
    @chown("/home/artica/SQLITE/mgr_client_list.db", "www-data");


    $sql = "CREATE TABLE IF NOT EXISTS `mgr_client_list` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zmd5` TEXT NOT NULL UNIQUE,
		`ipaddr` TEXT,
		`uid` TEXT,
		`CUR_CNX` INTEGER,
		`RQS` INTEGER,
		`TAG_NONE` INTEGER,
		`TCP_HIT` INTEGER,
		`TCP_MISS` INTEGER,
		`TCP_REDIRECT`INTEGER,
		`TCP_TUNNEL` INTEGER
		)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error\n";
        return;
    }
    $indexes = "CREATE INDEX ipaddr ON `mgr_client_list` (ipaddr);";
    $q->QUERY_SQL($indexes);
    $indexes = "CREATE INDEX uid ON `mgr_client_list` (uid);";
    $q->QUERY_SQL($indexes);
    $indexes = "CREATE INDEX RQS ON `mgr_client_list` (RQS);";
    $q->QUERY_SQL($indexes);

    $prefix = "INSERT OR IGNORE INTO `mgr_client_list` (`zmd5`,`ipaddr`,CUR_CNX,RQS,TAG_NONE,TCP_HIT,TCP_MISS,TCP_REDIRECT,TCP_TUNNEL,uid) VALUES ";
    $T = array();
    foreach ($MAIN as $ipaddr => $array) {
        $CUR_CNX=0;
        if (!isset($array["uid"])) {
            $array["uid"] = null;
        }
        if (!isset($array["TAG_NONE"])) {
            $array["TAG_NONE"] = 0;
        }
        if (!isset($array["TCP_HIT"])) {
            $array["TCP_HIT"] = 0;
        }
        if (!isset($array["TCP_REDIRECT"])) {
            $array["TCP_REDIRECT"] = 0;
        }
        if (!isset($array["TCP_MISS"])) {
            $array["TCP_MISS"] = 0;
        }
        if (!isset($array["TCP_TUNNEL"])) {
            $array["TCP_TUNNEL"] = 0;
        }
        $uid = $array["uid"];
        if(!is_null($uid)) {
            $uid = str_replace("'", "`", $uid);
        }
        $md5 = md5($ipaddr . $array["uid"]);
        if(isset($array["CUR_CNX"])) {
            $CUR_CNX = intval($array["CUR_CNX"]);
        }
        $RQS = intval($array["RQS"]);
        $TAG_NONE = intval($array["TAG_NONE"]);
        $TCP_HIT = intval($array["TCP_HIT"]);
        $TCP_MISS = intval($array["TCP_MISS"]);
        $TCP_REDIRECT = intval($array["TCP_REDIRECT"]);
        $TCP_TUNNEL = intval($array["TCP_TUNNEL"]);
        $uid = mysql_escape_string2($uid);
        if ($ipaddr == "127.0.0.1") {
            continue;
        }
        $line = "('$md5','$ipaddr','$CUR_CNX','$RQS','$TAG_NONE','$TCP_HIT','$TCP_MISS','$TCP_REDIRECT','$TCP_TUNNEL','$uid')";
        echo $line . "\n";
        $T[] = $line;
    }

    $countOfIps = count($T);

    if ($countOfIps > 0) {
        $q->QUERY_SQL($prefix . @implode(",", $T));
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PROXY_COUNT_IP_USERS", $countOfIps);

    $time = time();

    $q = new lib_sqlite("/home/artica/SQLITE/mgr_client_list_stats.db");
    $sql = "CREATE TABLE IF NOT EXISTS `clientips_mins` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`ztime` INTEGER UNIQUE,
				`zips` INTEGER)";

    $q->QUERY_SQL($sql);
    $q->QUERY_SQL("INSERT INTO clientips_mins (ztime,zips) 
	VALUES ('$time','$countOfIps')");

}