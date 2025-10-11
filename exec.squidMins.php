<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
$GLOBALS["BYPASS"]=true;
$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NODHCP"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
}
ini_set('display_errors', 1);
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


if($GLOBALS["VERBOSE"]){echo "Loading...\n";}


include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');

$pidfile        = "/etc/artica-postfix/pids/exec.squidMins.php.pid";
$date           = date("YW");
$unix           = new unix();
$squidbin       = $unix->LOCATE_SQUID_BIN();
$SQUIDEnable    = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
if(!is_file($squidbin)){exit();}
if($SQUIDEnable==0){exit();}

if(is_file($pidfile)){
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){
        echo "Current process already exists with pid $pid\n";
        die();
    }
}
@file_put_contents($pidfile,getmygid());
$q              = new postgres_sql();
$mem            = new lib_memcached();
$KEYS           = $mem->allKeys();

if($GLOBALS["VERBOSE"]){echo count($KEYS)." keys to Scan....\n";}

foreach ($KEYS as $skey){

    if(preg_match("#^notcategorized\.(.+)#",$skey,$re)){
            echo "[$skey] TRAPPED\n";
            $sitename=trim(strtolower($re[1]));
            $RQS=intval($mem->getKey($skey));
            $ligne=$q->mysqli_fetch_array("SELECT familysite,rqs FROM not_categorized WHERE familysite='$sitename'");
            if(trim($ligne["familysite"])<>null) {
                $ct = intval($ligne["rqs"]);
                $ct=$ct+$RQS;
                $q->QUERY_SQL("UPDATE not_categorized SET rqs=$ct WHERE familysite='$sitename'");
                $mem->Delkey($skey);
                continue;
            }
        $zdate=date("Y-m-d H:i:s");
        $q->QUERY_SQL("INSERT INTO not_categorized (zdate,rqs,familysite) VALUES ('$zdate',$RQS,'$sitename') ON CONFLICT DO NOTHING");
        continue;
    }



    if(preg_match("#^visited\.(.+)#",$skey,$re)){
        echo "[$skey] TRAPPED\n";
        $sitename=trim(strtolower($re[1]));
        $mem->Delkey($skey);
        $q->QUERY_SQL("INSERT INTO visits (domain) VALUES('$sitename') ON CONFLICT DO NOTHING");
        continue;
    }

}






		




