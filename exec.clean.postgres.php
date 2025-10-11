<?php
$GLOBALS["YESCGROUP"];
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.sqlite.inc');

if(system_is_overloaded()){die();}

if($argv[1]=="--run"){clean_postgres(intval($argv[2]));exit(0);}


clean_postgres();

function clean_postgres($retention=0){

    $unix=new unix();
    $pid="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
    $myPid=$unix->get_pid_from_file($pid);
    if($unix->process_exists($myPid)){
        echo "Already running PID $myPid\n";
        build_progress_vacuumdb("{failed} Already running PID $myPid",110);
        exit(0);

    }

    $InfluxAdminRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxAdminRetentionTime"));
    $SystemEventsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SystemEventsRetentionTime"));
    if($InfluxAdminRetentionTime==0){$InfluxAdminRetentionTime=365;}
    if($SystemEventsRetentionTime==0){$SystemEventsRetentionTime=15;}
    if(!$unix->CORP_LICENSE()){
        $InfluxAdminRetentionTime=5;
        $SystemEventsRetentionTime=7;
    }


    $retention=intval($retention);
    if($retention==0){$retention=$InfluxAdminRetentionTime;}
    build_progress_vacuumdb("Cleaning $retention Day(s)...",10);
    clean_postgres_perform($retention);

    if($SystemEventsRetentionTime<7){$SystemEventsRetentionTime=7;}
    $q=new lib_sqlite("/home/artica/SQLITE/system_events.db");
    $MaxDate=strtotime("-{$SystemEventsRetentionTime} days");
    if( $q->TABLE_EXISTS('squid_admin_mysql')){
        $q->QUERY_SQL("DELETE FROM squid_admin_mysql WHERE zDate < '$MaxDate'");
    }

    if(!$q->ok){echo $q->mysql_error."\n";}

    if(is_file("/home/artica/SQLITE/schedule_events.db")){
        $q=new lib_sqlite("/home/artica/SQLITE/schedule_events.db");
        if( $q->TABLE_EXISTS('events')) {
            $q->QUERY_SQL("DELETE FROM events WHERE zDate < '$MaxDate'");
        }
    }


}


function clean_postgres_perform($InfluxAdminRetentionTime=0){
    $unix=new unix();
    $FirsTime=time();
    if($InfluxAdminRetentionTime==0){
        build_progress_vacuumdb("No retention time defined",90);
        return false;
    }
    $prc=10;
    $qPostgres=new postgres_sql();
    $LastMonthTime=strtotime("first day of last month");
    $LastMonth=date("Y-m-d 00:00:00",$LastMonthTime);
    $IpAuditRetention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("IpAuditRetention"));
    if($IpAuditRetention==0){$IpAuditRetention=7;}

    if($qPostgres->TABLE_EXISTS("ipaudit_days")){
        build_progress_vacuumdb("Cleaning ipaudit_days...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM ipaudit_days 
        WHERE constartdate < NOW() - INTERVAL '$InfluxAdminRetentionTime days'");
        if(!$qPostgres->ok){echo "Fatal $qPostgres->mysql_error\n";}
    }

    if($qPostgres->TABLE_EXISTS("ipaudit")) {
        build_progress_vacuumdb("Cleaning ipaudit...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM ipaudit WHERE constartdate < NOW() - INTERVAL '$InfluxAdminRetentionTime days'");
    }

    if($InfluxAdminRetentionTime>$IpAuditRetention) {
        build_progress_vacuumdb("Cleaning ipaudit...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM ipaudit WHERE constartdate < NOW() - INTERVAL '$IpAuditRetention days'");
        if (!$qPostgres->ok) {
            echo "Fatal $qPostgres->mysql_error\n";
        }
    }

    $LIST_TABLES=$qPostgres->LIST_TABLES("public");
    foreach ($LIST_TABLES as $tablename=>$none){
        if(preg_match("#^icap_[0-9]+#",$tablename)){
            $qPostgres->QUERY_SQL("DROP TABLE $tablename");
        }
    }



    if($qPostgres->TABLE_EXISTS("proxypac")) {
        build_progress_vacuumdb("Cleaning proxypac...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM proxypac WHERE zdate < NOW() - INTERVAL '$InfluxAdminRetentionTime days'");
    }

    if($qPostgres->TABLE_EXISTS("proxypac_stats")) {
        build_progress_vacuumdb("Cleaning proxypac_stats...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM proxypac_stats WHERE zdate < '$LastMonth'");
    }

    if($qPostgres->TABLE_EXISTS("ndpi_main")) {
        build_progress_vacuumdb("Cleaning ndpi_main...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM ndpi_main WHERE zdate < '$LastMonth'");
    }



    if($qPostgres->TABLE_EXISTS("strongswan_stats")) {
        build_progress_vacuumdb("Cleaning strongswan_stats...",$prc++);
        $Strongswanretention=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO["Strongswanretention"]);
        if($Strongswanretention==0){$Strongswanretention=7;}
        $qPostgres->QUERY_SQL("DELETE FROM strongswan_stats WHERE zdate < NOW() - INTERVAL '$Strongswanretention days'");
    }

    $Current=date("Y-m-d H:i:s");
    $TimeRTTStart=strtotime("$Current - $InfluxAdminRetentionTime days");


    $zday=intval(date("d",$TimeRTTStart));
    $zmonth=intval(date("m",$TimeRTTStart));
    $zyear=intval(date("Y",$TimeRTTStart));

    if($qPostgres->TABLE_EXISTS("rttable_users")) {
        build_progress_vacuumdb("Cleaning rttable_users...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM rttable_users 
        WHERE zday <= $zday AND zmonth<=$zmonth AND zyear<=$zyear");
        if (!$qPostgres->ok) {
            echo "Fatal $qPostgres->mysql_error\n";
        }
    }





    if($qPostgres->TABLE_EXISTS("rttable_domains")) {
        build_progress_vacuumdb("Cleaning rttable_domains...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM rttable_domains 
        WHERE zday <= $zday AND zmonth<=$zmonth AND zyear<=$zyear");
        if (!$qPostgres->ok) {
            echo "Fatal $qPostgres->mysql_error\n";
        }
    }

    $array_zdate[]="ndpi_main";
    $array_zdate[]="ndpi_month";
    $array_zdate[]="domains_access";
    $array_zdate[]="bandwidth_table";
    $array_zdate[]="not_categorized";

    $array_zdate[]="system";
    $array_zdate[]="openvpn_stats";
    $array_zdate[]="openvpn_cnx";
    $array_zdate[]="access_users";

    $array_zdate[]="access_log";
    $array_zdate[]="access_year";
	$array_zdate[]="access_month";
    $array_zdate[]="access_big";
    $array_zdate[]="dns_access";
    $array_zdate[]="dns_access_days";

    $array_zdate[]="main_size";
    $array_zdate[]="access_month";
    $array_zdate[]="icap_access";
    $array_zdate[]="hypercache_access";
    $array_zdate[]="ntlmauthenticator";
    $array_zdate[]="agents_access";
    $array_zdate[]="access_users";
    $array_zdate[]="dns_rqcounts";

    $array_zdate[]="statscom";
    $array_zdate[]="statscom_days";
    $array_zdate[]="statsblocks";
    $array_zdate[]="ksrn";


    foreach ($array_zdate as $tablename){
        if(!$qPostgres->TABLE_EXISTS($tablename)) {continue;}
        $ROWS=$qPostgres->COUNT_ROWS_LOW($tablename);
        if($ROWS>0) {
            build_progress_vacuumdb("Cleaning $tablename Current:$ROWS rows...", $prc++);
            $qPostgres->QUERY_SQL("DELETE FROM $tablename WHERE zdate < NOW() - INTERVAL '$InfluxAdminRetentionTime days'");

            if(!$qPostgres->ok){
                build_progress_vacuumdb("Error $tablename $qPostgres->mysql_error", $prc++);

            }
            $ROWS2 = $qPostgres->COUNT_ROWS_LOW($tablename);
            $RESTE = $ROWS - $ROWS2;
            if ($RESTE > 0) {
                build_progress_vacuumdb("$RESTE rows removed in $tablename...", $prc++);
            }
        }
    }
    $PDNSStatsRetentionTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSStatsRetentionTime"));
    if($PDNSStatsRetentionTime==0){$PDNSStatsRetentionTime=5;}

    if($InfluxAdminRetentionTime>$PDNSStatsRetentionTime){
        build_progress_vacuumdb("Cleaning dns_access_days...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM dns_access_days WHERE zdate < NOW() - INTERVAL '$PDNSStatsRetentionTime days'");

        build_progress_vacuumdb("Cleaning dns_access...",$prc++);
        $qPostgres->QUERY_SQL("DELETE FROM dns_access WHERE zdate < NOW() - INTERVAL '$PDNSStatsRetentionTime days'");
    }

    $t=time();
    build_progress_vacuumdb("vacuum full...",80);
    $qPostgres->QUERY_SQL("vacuum full");
    build_progress_vacuumdb("vacuum full done took: ".$unix->distanceOfTimeInWords_text($t,time()),82);
    build_progress_vacuumdb("{index_database_text}",90);
    $t=time();
    $qPostgres->QUERY_SQL("Reindex database proxydb");
    build_progress_vacuumdb("Reindex database done took: ".$unix->distanceOfTimeInWords_text($t,time()),82);
    build_progress_vacuumdb("{success} took: ". $unix->distanceOfTimeInWords_text($FirsTime,time()),100);


}
function build_progress_vacuumdb($text,$pourc){
    $unix=new unix();
    $text=str_replace("\n"," ",$text);
    $unix->ToSyslog("$text",false,"clean-db");
    if($pourc<>110){if($pourc>100){$pourc=99;}}
    echo "Starting......: ".date("H:i:s")." [INIT]: {$pourc}% $text\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/squid.statistics.purge.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}