<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

$force = $GLOBALS["FORCE"];
$pid_file="/etc/artica-postfix/pids/ConvertToSQLite.pid";
$unix=new unix();
$pid=$unix->get_pid_from_file($pid_file);

if(isset($argv[1])) {
    if ($argv[1] == "--network") {migrate_interfaces();exit;}
    if ($argv[1] == "--analyze") {analyze();exit;}
    if ($argv[1] == "--nginx") {CheckNGINXTables();exit;}
    if ($argv[1] == "--hamrp") {hamrp();exit;}
    if ($argv[1] == "--force") {$force=true;}
}
if(isset($argv[2])) {
    if($argv[2] == "--force") {$force = true;}
}

if(!$force) {
    if ($unix->process_exists($pid, basename(__FILE__))) {
        echo "Already PID $pid running...\n";
        die();
    }
    if ($unix->process_number_me($argv) > 1) {
        echo "Already processes running...\n";
        die();
    }
    if(system_is_overloaded(__FILE__)){ die(); }
}
if (!$force) {
    $ttime = $unix->file_time_min($pid_file);
    if ($ttime < 5) {
        echo "Must be PID 5mn, current is {$ttime}mn\n";
        die();
    }
}

$myPid=getmypid();
if(is_file($pid_file)){@unlink($pid_file);}
@file_put_contents($pid_file,$myPid);

if(!is_dir("/home/artica/SQLITE")) {
    @mkdir("/home/artica/SQLITE", 0755, true);
}
@chmod("/home/artica", 0755);
@chmod("/home/artica/SQLITE", 0755);
@chown("/home/artica/SQLITE", "www-data");


if(isset($argv[1])) {
    if ($argv[1] == "--haproxy") {
        haproxy_tables();
        exit;
    }
    if ($argv[1] == "--adagent") {
        adagent_tables();
        exit;
    }

    if ($argv[1] == "--proxy") {
        proxy_tables();
        exit;
    }
    if ($argv[1] == "--sys") {
        sys();
        exit;
    }
    if ($argv[1] == "--postfix-upgrade") {
        upgrade_smtp_tables();
        exit;
    }

    if ($argv[1] == "--postfix") {
        postfix_tables();
        exit;
    }
    if ($argv[1] == "--schedules") {
        schedules();
        exit;
    }
    if ($argv[1] == "--caches") {
        caches();
        exit;
    }

    if ($argv[1] == "--ftp") {
        proftpd_table();
        exit;
    }
    if ($argv[1] == "--openvpn") {
        openvpn();
        exit;
    }
    if ($argv[1] == "--net") {
        migrate_interfaces();
        exit;
    }
    if ($argv[1] == "--ipsec") {
        strongswan();
        exit;
    }
    if ($argv[1] == "--imapbox") {
        imapbox_tables();
        exit;
    }
    if ($argv[1] == "--keepalived") {
        keepalived();
        exit;
    }
    if($argv[1]=="--backup-task"){
        migrate_backup_tasks();
        exit;
    }
}
migrate_backup_tasks();
check_hotspot_tables();
radius_db();
upgrade_smtp_tables();
if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
migrate_interfaces();
if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
imapbox_tables();
if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
analyze();
if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
$unix=new unix();
$php=$unix->LOCATE_PHP5_BIN();
$nohup=$unix->find_program("nohup");
if(!is_dir("/etc/artica-postfix")){@mkdir("/etc/artica-postfix",0755);}
if(!is_file("/etc/artica-postfix/UPGRADE_SQLITE_440")){@touch("/etc/artica-postfix/UPGRADE_SQLITE_440");}
$unix->chown_func("www-data", "www-data", "/home/artica/SQLITE/*");

function analyze():bool{
    $sdir="/home/artica/SQLITE";
    if (!$handle = opendir($sdir)) {return false;}
    echo "Scanning $sdir\n";
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if(!preg_match("#\.db$#",$filename)){continue;}
        $targetFile="$sdir/$filename";
        echo "Analysis, optimize $targetFile\n";
        $q=new lib_sqlite($targetFile);
        $q->QUERY_SQL("PRAGMA analysis_limit=400;");
        if(!$q->ok){echo "---------------> $q->mysql_error\n";}
        $q->QUERY_SQL("PRAGMA optimize;");

    }

    return true;
}
function upgrade_smtp_tables():bool{

    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");


    if(!$q->FIELD_EXISTS("smtp_rules","instanceid")){
        $q->QUERY_SQL("ALTER TABLE smtp_rules ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }


    if(!$q->FIELD_EXISTS("smtp_generic_maps","instanceid")){
        $q->QUERY_SQL("ALTER TABLE smtp_generic_maps ADD instanceid INTEGER NOT NULL DEFAULT 0");
    }

    $ip_reputations="CREATE TABLE ip_reputations (
                `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
                service TEXT NOT NULL,enabled INTEGER NOT NULL DEFAULT 1,
                `instanceid` INTEGER NOT NULL DEFAULT 0)";

    if(!$q->IF_TABLE_EXISTS("ip_reputations")){
        $q->QUERY_SQL($ip_reputations);
    }

    if(!$q->FIELD_EXISTS("ip_reputations","instanceid")){
        $results=$q->QUERY_SQL("SELECT * FROM relay_domains_restricted");
        $q->QUERY_SQL("DROP TABLE ip_reputations");
        $q->QUERY_SQL($ip_reputations);
        foreach ($results as $index=>$ligne){
            $service=$ligne["service"];
            echo "$index=$service\n";
            $q->QUERY_SQL("INSERT INTO ip_reputations (service) VALUES ('$service')");
        }
    }


    $relay_domains_restricted="CREATE TABLE IF NOT EXISTS `relay_domains_restricted` (
            `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
            `instanceid` INTEGER NOT NULL DEFAULT 0, 
            `domainname` text NOT NULL)";

    if(!$q->TABLE_EXISTS("relay_domains_restricted")){
        $q->QUERY_SQL($relay_domains_restricted);
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");

    }
    if(!$q->FIELD_EXISTS("relay_domains_restricted","instanceid")){
        $results=$q->QUERY_SQL("SELECT * FROM relay_domains_restricted");
        $q->QUERY_SQL("DROP TABLE relay_domains_restricted");
        $q->QUERY_SQL($relay_domains_restricted);
        foreach ($results as $index=>$ligne){
            $domainname=$ligne["domainname"];
            echo "$index = $domainname\n";
            $q->QUERY_SQL("INSERT INTO relay_domains_restricted (domainname) VALUES ('$domainname')");

        }
    }

    $mynetworks="CREATE TABLE IF NOT EXISTS mynetworks (
     `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
    addr TEXT NOT NULL,description text,
    instance_id INTEGER NOT NULL DEFAULT 0)";

    if(!$q->TABLE_EXISTS("mynetworks")){
        $q->QUERY_SQL($mynetworks);
        $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    }
    if(!$q->FIELD_EXISTS("mynetworks","ID")){
        $results=$q->QUERY_SQL("SELECT * FROM mynetworks");
        $q->QUERY_SQL("DROP TABLE mynetworks");
        $q->QUERY_SQL($mynetworks);
        foreach ($results as $index=>$ligne){
            $addr=$ligne["addr"];
            $description=$q->sqlite_escape_string2($ligne["description"]);
            $q->QUERY_SQL("INSERT INTO mynetworks (addr,description) 
                            VALUES ('$addr','$description')");

        }
    }


    @chmod("/home/artica/SQLITE/postfix.db", 0777);
    return true;
}
function migrate_backup_tasks():bool{
    $unix=new unix();
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    $results=$q->QUERY_SQL("SELECT * FROM system_schedules WHERE TaskType=75 ORDER BY ID DESC");
    if(count($results)==0){return true;}

    foreach ($results as $index=>$ligne){
        $enabled=$ligne["enabled"];
        if($enabled==0){
            continue;
        }
        $TimeText=$ligne["TimeText"];
        $unix->Popuplate_cron_make("backup-snaphosts",$TimeText,"exec.backup.artica.php --snapshot");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("BackupArticaSnaps",1);
        break;


    }
    $q->QUERY_SQL("DELETE FROM system_schedules WHERE TaskType=75");
    return true;

}
function radius_db():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/radius.db");
    $sql="CREATE TABLE IF NOT EXISTS freeradius_clients (
			`ipaddr` TEXT NOT NULL PRIMARY KEY,
			`secret` TEXT ,
			`shortname` TEXT ,
			`enabled` INTEGER NOT NULL DEFAULT 1,
			`nastype`  TEXT NOT NULL DEFAULT 'Client-ABC' )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
        return false;
    }
    return true;
}


function schedules():bool{
    @mkdir("/home/artica/SQLITE", 0755, true);
    $q=new lib_sqlite("/home/artica/SQLITE/sys_schedules.db");
    @chmod("/home/artica/SQLITE/sys_schedules.db", 0644);
    @chown("/home/artica/SQLITE/sys_schedules.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);

    $sql="CREATE TABLE IF NOT EXISTS `system_schedules` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`TimeText` VARCHAR( 128 ),
		`TimeDescription` VARCHAR( 128 ),
		`TaskType` INTEGER,
		`enabled` INTEGER )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
        return false;
    }



    $sql="CREATE TABLE IF NOT EXISTS `pdf_reports` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`TimeText` VARCHAR( 128 ),
		`TimeDescription` VARCHAR( 128 ),
		`TaskType` INTEGER,
		`recipients` TEXT,
		`subject` TEXT,
		`enabled` INTEGER )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
        return false;
    }
    return true;
}

function CheckNGINXTables($groupid=0):bool{
    $dbpath="/home/artica/SQLITE/nginx.db";
    if($groupid==0) {
        hamrp();
    }
    if($groupid>0){
        $dbpath="/home/artica/SQLITE/nginx.$groupid.db";
    }

    $q=new lib_sqlite($dbpath);

    $sql="CREATE TABLE IF NOT EXISTS `httrack_sites` ( 
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `enabled` INTEGER NOT NULL DEFAULT 0 ,
     `serviceid` INTEGER NOT NULL DEFAULT 0 , 
    `size` INTEGER DEFAULT '0', 
    `minrate` INTEGER NOT NULL DEFAULT '512', 
    `maxfilesize` INTEGER NOT NULL DEFAULT '512', 
    `maxsitesize` INTEGER NOT NULL DEFAULT '5000', 
    `maxworkingdir` INTEGER NOT NULL DEFAULT '20',
    `UserAgent` TEXT NULL )";

    $q->QUERY_SQL($sql);




    $sql="CREATE TABLE IF NOT EXISTS `caches_center` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `cachename` TEXT ,
			  `inactive` INTEGER,
			  `RemoveSize` INTEGER,
			  `cache_dir` TEXT NOT NULL,
			  `cache_type` VARCHAR( 50 ) NOT NULL,
			  `cache_size` INTEGER NOT NULL,
			  `cache_dir_level1` INT( 10 ),
			  `cache_dir_level2` INT( 10 ),
			  `min_size` INTEGER NOT NULL DEFAULT 0,
			  `max_size` INTEGER NOT NULL DEFAULT 80000,
			  `enabled` INTEGER DEFAULT 1,
			  `remove` INTEGER NOT NULL DEFAULT 0,
			  `percentcache` INTEGER,
			  `percenttext` TEXT,
			  `usedcache` INTEGER,
			  `zOrder` INTEGER
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
    }

    if($q->COUNT_ROWS("caches_center")==0) {
        if (!$q->FIELD_EXISTS("caches_center", "inactive")) {
            $q->QUERY_SQL("ALTER TABLE caches_center ADD inactive INTEGER");
        }
        $sql = "INSERT INTO caches_center (cachename,cache_dir,cache_type,cache_size,inactive)
        VALUES('Default cache','/home/nginx/BigCache','disk',1024,172800)";
        $q->QUERY_SQL($sql);
    }
    wordpress();

    return true;
}
function wordpress():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/wordpress.db");
    @chmod("/home/artica/SQLITE/wordpress.db", 0644);
    @chown("/home/artica/SQLITE/wordpress.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);

    $sql="CREATE TABLE IF NOT EXISTS `wp_backup` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`siteid` INTEGER,
		`backuptime` INTEGER,
		`hostname` TEXT,
		`filename` TEXT,
		`dbsize` INTEGER,
		`filesize` INTEGER,
		`fullpath` TEXT
		)
		";
    $q->QUERY_SQL($sql);


    $sql="CREATE TABLE IF NOT EXISTS `wp_infirewall` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`address` TEXT,
	`description` text,
	`enabled` INTEGER NOT NULL DEFAULT 1,
	`port` INTEGER NOT NULL DEFAULT 80)";
    $q->QUERY_SQL($sql);

    $sql="CREATE TABLE IF NOT EXISTS `wp_firewall` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`address` TEXT,
	`description` text,
	`enabled` INTEGER NOT NULL DEFAULT 1,
	`port` INTEGER NOT NULL DEFAULT 80)";
    $q->QUERY_SQL($sql);


    $sql="CREATE TABLE IF NOT EXISTS `wp_sites` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`WP_LANG` TEXT,
		`date_created` TEXT,
		`hostname` TEXT UNIQUE,
		`admin_user` TEXT,
		`admin_password` TEXT,
		`admin_email` TEXT,
		`database_name` TEXT,
		`database_user` TEXT,
		`database_password` TEXT,
		`database_error` TEXT,
		`aliases` TEXT,
		`wp_version` TEXT,
		`ssl` INTEGER NOT NULL DEFAULT 0,
		`letsencrypt` INTEGER NOT NULL DEFAULT 0,
		`ssl_certificate` TEXT,
		`enabled` INTEGER,
		`status` INTEGER,
		`cgicache` INTEGER,
		`readonly` INTEGER NOT NULL DEFAULT 0,
		`site_size` INTEGER NOT NULL DEFAULT 0,
		`wp_config` TEXT,
        `zmd5` TEXT
		)
		";
    $q->QUERY_SQL($sql);

    if($q->TABLE_EXISTS("wp_sites")) {

        if(!$q->FIELD_EXISTS("wp_sites","site_size")){
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD site_size INTEGER NOT NULL DEFAULT 0");
        }
        if(!$q->FIELD_EXISTS("wp_sites","version")){
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD version text NOT NULL DEFAULT '0.0'");
        }

        if (!$q->FIELD_EXISTS("wp_sites", "ssl")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD ssl INTEGER NOT NULL DEFAULT '0'");
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD letsencrypt INTEGER NOT NULL DEFAULT '0'");
        }
        if (!$q->FIELD_EXISTS("wp_sites", "pagespeed")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD pagespeed INTEGER DEFAULT 0");
        }
        if (!$q->FIELD_EXISTS("wp_sites", "cacheid")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD cacheid INTEGER DEFAULT 0");
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD proxy_cache_revalidate INTEGER DEFAULT 1");
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD proxy_cache_min_uses INTEGER DEFAULT 1");
        }
        if (!$q->FIELD_EXISTS("wp_sites", "yoast")) {
            $q->QUERY_SQL("ALTER TABLE wp_sites ADD yoast INTEGER DEFAULT 0");
        }
    }

    return true;

}
function check_hotspot_tables():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/hotspot.db");
    $sql="CREATE TABLE IF NOT EXISTS `network_rules` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`ruleid` INTEGER NOT NULL,
			`pattern` TEXT NOT NULL )  ";
    $q->QUERY_SQL($sql);
    return true;
}
function caches():bool{
    @mkdir("/home/artica/SQLITE", 0755, true);
    $q=new lib_sqlite("/home/artica/SQLITE/caches.db");
    @chmod("/home/artica/SQLITE/caches.db", 0644);
    @chown("/home/artica/SQLITE/caches.db", "www-data");



    $sql="CREATE TABLE IF NOT EXISTS `squid_caches_center` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `cachename` TEXT ,
			  `cpu` INTEGER,
			  `RemoveSize` INTEGER,
			  `cache_dir` TEXT NOT NULL,
			  `cache_type` VARCHAR( 50 ) NOT NULL,
			  `cache_size` INT( 50 ) NOT NULL,
			  `cache_dir_level1` INT( 10 ),
			  `cache_dir_level2` INT( 10 ),
			  `min_size` INTEGER,
			  `max_size` INTEGER,
			  `enabled` INTEGER DEFAULT 1,
			  `remove` INTEGER,
			  `percentcache` INTEGER,
			  `percenttext` VARCHAR(10),
			  `usedcache` INTEGER,
			  `CPUAF` INTEGER  DEFAULT 0,
			   `wizard` INTEGER,
			  `zOrder` INTEGER
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
    }
    return true;
}
function CheckUsersTables():bool{
    @mkdir("/home/artica/SQLITE", 0755, true);
    $qlite=new lib_sqlite("/home/artica/SQLITE/admins.db");
    @chmod("/home/artica/SQLITE/admins.db", 0644);
    @chown("/home/artica/SQLITE/admins.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);
    $sql="CREATE TABLE IF NOT EXISTS `CreateUserQueue` ( `zMD5` TEXT PRIMARY KEY,`content` TEXT NOT NULL ) ";
    $qlite->QUERY_SQL($sql);
    return true;
}

function migrate_interfaces():bool
{
    proxy_tables();
    haproxy_tables();
    keepalived();
    adagent_tables();
    CheckNGINXTables();
    postfix_tables();
    openvpn();
    strongswan();
    schedules();
    caches();
    hypercaches_tables();
    postfix_events();
    proftpd_table();
    CheckUsersTables();
    ipinfo();
    return true;
}

function hypercaches_tables():bool
{
    $q=new lib_sqlite("/home/artica/SQLITE/hypercache.db");
    @chmod("/home/artica/SQLITE/hypercache.db", 0644);
    @chown("/home/artica/SQLITE/hypercache.db", "www-data");
    echo "[".__LINE__."]: Migrate table hypercache_rules\n";
    $sql="CREATE TABLE IF NOT EXISTS `hypercache_rules` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`cacheid` INT ,
				`pattern` TEXT NOT NULL,
				`description` TEXT NOT NULL,
				`type` INTEGER ,
				`enabled` INTEGER DEFAULT 1,
				`block_downloads` INTEGER NULL,
			    `limit_rate_after` INTEGER NOT NULL default '500',
    			`limit_rate` INTEGER ,
				`siteslist` TEXT,
				`extlists` TEXT,
				`proxy_cache_valid` smallint(5) NOT NULL DEFAULT 15,
				`zOrder` INTEGER 
			 )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }
return true;
}
function keepalived():bool
{
    $q = new lib_sqlite("/home/artica/SQLITE/keepalived.db");
    @chmod("/home/artica/SQLITE/keepalived.db", 0644);
    @chown("/home/artica/SQLITE/keepalived.db", "www-data");
    echo "[" . __LINE__ . "]: Migrate table keepalived_primary_nodes\n";
    $sql = "CREATE TABLE IF NOT EXISTS `keepalived_primary_nodes` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`primary_node_name` varchar(128) NOT NULL,
				`interface`  VARCHAR( 128 ),
                `state` VARCHAR( 128 ),
                `virtual_router_id` INTEGER,
                `priority` INTEGER,
                `advert_int` INTEGER,
                `nopreempt` INTEGER,
                `unicast_src_ip` INTEGER,
    			`enable_peers_ttl` INTEGER,
				`min_peers_ttl` integer ,
				`max_peers_ttl` INTEGER ,
                `auth_enable` INTEGER,
                `auth_type` VARCHAR( 128 ),
                `auth_pass` VARCHAR( 128 ),
                `notifty_enable` INTEGER,
                `notifty`  TEXT,
				`interval` INTEGER,
				`fall` INTEGER,
				`rise` INTEGER,
                `timeout` INTEGER,
				`weight` INTEGER,
				`enable` INTEGER,
                `isPrimaryNode` INTEGER, 
                `primaryNodeIP` VARCHAR( 128 ),
                `primaryNodePort` INTEGER,
                `primaryNodeID` INTEGER,
                `secondaryNodeIsDisconnected` INTEGER,
                `synckey` TEXT,
                `force_action` TEXT,
                `service_state` TEXT,
                `last_sync` TEXT,
                `errortext` TEXT,
                `status` INTEGER
		) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (" . __LINE__ . ")\n$sql\n";
        return false;
    }

    echo "[" . __LINE__ . "]: Migrate table keepalived_secondary_nodes\n";
    $sql = "CREATE TABLE IF NOT EXISTS `keepalived_secondary_nodes` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`primary_node_id` INTEGER ,
                `secondary_node_ip` VARCHAR( 128 ),
                `secondary_node_port` VARCHAR( 128 ),
                `primary_node_ip` VARCHAR( 128 ),
                `secondary_node_can_overwrite_settings`  INTEGER,
				`enable` INTEGER,
                `synckey` TEXT,
                `nopreempt` INTEGER,
                `priority` INTEGER,              
                `errortext` TEXT,
                `status` INTEGER,
                `hostname` TEXT,
                `force_action` TEXT,
                `service_state` TEXT,
                `last_sync` TEXT
		) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (" . __LINE__ . ")\n$sql\n";
        return false;
    }


    echo "[" . __LINE__ . "]: Migrate table keepalived_services\n";
    $sql = "CREATE TABLE IF NOT EXISTS `keepalived_services` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
                `primary_node_id` INTEGER,
				`service` TEXT,
    			`script`  TEXT,
				`enable` INTEGER,
				`synckey` TEXT
		) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (" . __LINE__ . ")\n$sql\n";
        return false;
    }

    echo "[" . __LINE__ . "]: Migrate table keepalived_virtual_interfaces\n";
    $sql = "CREATE TABLE IF NOT EXISTS `keepalived_virtual_interfaces` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`primary_node_id` INTEGER ,
                `virtual_interface` VARCHAR( 128 ),
                `virtual_interface_id` INTEGER ,
                `virtual_ip`  VARCHAR( 128 ),   
                `netmask` VARCHAR( 128 ),
                `dev` VARCHAR( 128 ),
                `label`   VARCHAR( 128 ),
				`enable` INTEGER,
                `synckey` TEXT
		) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (" . __LINE__ . ")\n$sql\n";
        return false;
    }

    echo "[" . __LINE__ . "]: Migrate table keepalived_track_interfaces\n";
    $sql = "CREATE TABLE IF NOT EXISTS `keepalived_track_interfaces` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`primary_node_id` INTEGER NOT NULL,
				`interface`  VARCHAR( 128 ),
                `weight` INTEGER,
				`enable` INTEGER,
                `synckey` TEXT
		) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (" . __LINE__ . ")\n$sql\n";
        return false;
    }
return true;

}
function strongswan():bool
{
    $q=new lib_sqlite("/home/artica/SQLITE/strongswan.db");
    @chmod("/home/artica/SQLITE/strongswan.db", 0644);
    @chown("/home/artica/SQLITE/strongswan.db", "www-data");
    echo "[".__LINE__."]: Migrate table strongswan_conns\n";
    $sql="CREATE TABLE IF NOT EXISTS `strongswan_conns` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`conn_name` varchar(128) NOT NULL,
				`params` LONGTEXT,
				`order` INTEGER,
				`enable` INTEGER
		) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table strongswan_auth\n";
    $sql="CREATE TABLE IF NOT EXISTS `strongswan_auth` (
					  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
					  `conn_id` INTEGER,
					  `selector` LONGTEXT,
					  `type` INTEGER,
					  `cert` LONGTEXT,
					  `secret` LONGTEXT,
					  `order` INTEGER,
					  `enable` INTEGER
) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table strongswan_certs\n";
    //$sql="DROP TABLE `strongswan_certs`";
    $sql="CREATE TABLE IF NOT EXISTS `strongswan_certs` (
					  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
					  `name` TEXT,
					  `cn` TEXT,
					  `ca_key` TEXT,
					  `ca_cert` TEXT,
					  `ca_cert_content` LONGTEXT,
					  `server_key` TEXT,
					  `server_cert` TEXT,
					  `order` INTEGER,
					  `enable` INTEGER					  
) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table strongswan_cnx\n";
    $sql="CREATE TABLE IF NOT EXISTS `strongswan_cnx` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		zdate INTEGER,
		action varchar(60),
		ipaddr_local TEXT,
		ipaddr_vip TEXT,
		member varchar(128),
		ztime INTEGER
) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }
return true;
}
function openvpn():bool
{
    $q=new lib_sqlite("/home/artica/SQLITE/openvpn.db");
    @chmod("/home/artica/SQLITE/openvpn.db", 0644);
    @chown("/home/artica/SQLITE/openvpn.db", "www-data");
    echo "[".__LINE__."]: Migrate table vpn_auth\n";
    $sql="CREATE TABLE IF NOT EXISTS `vpn_auth` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`type` smallint NOT NULL DEFAULT 1,
				`hostname` varchar(128) NOT NULL,
				`params` TEXT
		) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table vpn_events\n";
    $sql="CREATE TABLE IF NOT EXISTS `vpn_events` (
				`stime` BIGINT UNSIGNED ,
				`subject` VARCHAR( 255 ) NOT NULL ,
				`text` LONGTEXT NOT NULL ,
				`IPPARAM` VARCHAR( 255 ) NOT NULL 
) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table vpnclient\n";
    $sql="CREATE TABLE IF NOT EXISTS `vpnclient` (
					  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
					  `servername` varchar(180),
					  `serverport` int(3),
					  `admin` varchar(50),
					  `password` varchar(128),
					  `connexion_name` varchar(128),
					  `sitename` TEXT,
					  `IP_START` varchar(25),
					  `netmask` varchar(25),
					  `ethlisten` varchar(10),
					  `keypassword` varchar(255),
					  `connexion_type` smallint(1),
					  `ca_bin` longblob,
					  `key_bin` longblob,
					  `cert_bin` longblob,
					  `ovpn` longblob,
					  `routes` text,
					  `enabled` smallint(1),
					  `routes_additionnal` TEXT,
					  `use_proxy` int(3),
					  `wakeupip` varchar(50),
					  `wakeupok` varchar(50)
) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }

    echo "[".__LINE__."]: Migrate table vpn_global_fw_rules_allow\n";
    $sql="CREATE TABLE IF NOT EXISTS `vpn_global_fw_rules_allow` (
					  `id` INTEGER PRIMARY KEY  AUTOINCREMENT,
					  `ports` TEXT
) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table vpn_global_fw_rules_deny\n";
    $sql="CREATE TABLE IF NOT EXISTS `vpn_global_fw_rules_deny` (
					  `id` INTEGER PRIMARY KEY  AUTOINCREMENT,
					  `ports` TEXT
) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }
    return true;

}





function proxy_tables():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    @chmod("/home/artica/SQLITE/proxy.db", 0644);
    @chown("/home/artica/SQLITE/proxy.db", "www-data");


    echo "[".__LINE__."]: Migrate table StoreID\n";
    if (is_file(dirname(__FILE__)."/ressources/class.storeid.defaults.inc")) {
        include_once(dirname(__FILE__)."/ressources/class.storeid.defaults.inc");
        if ($q->COUNT_ROWS("StoreID")==0) {
            echo "[".__LINE__."]: Migrate table StoreID ->FillStoreIDDefaults \n";
            if (function_exists("FillStoreIDDefaults")) {
                $q->QUERY_SQL(FillStoreIDDefaults());
            }
        }
        if (function_exists("FillStoreIDUpdates")) {
            echo "[".__LINE__."]: Migrate table StoreID ->FillStoreIDUpdates \n";
            $q->QUERY_SQL(FillStoreIDUpdates());
        }
    }

    $sql="SELECT * FROM proxy_ports WHERE transparent=1 OR TProxy=1";

    $results=$q->QUERY_SQL($sql);
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
    $SquidTransparentInterfaceIN=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceIN"));
    $SquidTransparentInterfaceOUT=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentInterfaceOUT"));
    $SquidTransparentSSLCert=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidTransparentSSLCert"));

    foreach ($results as $index=>$ligne) {
        $ID=$ligne["ID"];
        $PortName=$ligne["PortName"];
        $nic=$SquidTransparentInterfaceIN;
        $outgoing_addr=$SquidTransparentInterfaceOUT;
        $sslcertificate=$ligne["sslcertificate"];
        $enabled=$ligne["enabled"];
        $NoCache=$ligne["NoCache"];
        $NoFilter=$ligne["NoFilter"];
        $port=$ligne["port"];
        $TProxy=$ligne["TProxy"];
        $UseSSL=intval($ligne["UseSSL"]);
        $localport=80;
        if ($UseSSL==1) {
            $localport=443;
            $sslcertificate=$SquidTransparentSSLCert;
        }

        $sql="INSERT INTO transparent_ports (PortName,nic,outgoing_nic,sslcertificate,enabled,NoCache,NoFilter,TProxy,localport,port) VALUES ('$PortName','$nic','$outgoing_addr','$sslcertificate','$enabled',$NoCache,$NoFilter,$TProxy,$port,$localport)";

        $q->QUERY_SQL($sql);
        if (!$q->ok) {
            echo $q->mysql_error."\n$index: $sql\n";
            return false;
        }
        $q->QUERY_SQL("DELETE FROM proxy_ports WHERE ID=$ID");
    }

    echo "[".__LINE__."]: Table Proxy Port as ". $q->COUNT_ROWS("proxy_ports"). " elements...\n";
    $results = $q->QUERY_SQL("SELECT ID,port,ipaddr FROM proxy_ports WHERE enabled=1");
    if (!$q->ok) {
        echo "[".__LINE__."]: $q->mysql_error\n";
    }
    foreach ($results as $index=>$lignePorts) {
        echo "[".__LINE__."]:$index: {$lignePorts["ID"]}=TCP {$lignePorts["ipaddr"]}:{$lignePorts["port"]}\n";
    }



    if (!$q->FIELD_EXISTS("webfilters_schedules", "Params")) {
        $q->QUERY_SQL("ALTER TABLE webfilters_schedules ADD `Params` TEXT");
        if (!$q->ok) {
            echo "****************\n$q->mysql_error\n**********************\n";
        }
    }

    return true;
}
function adagent_tables():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/adagent.db");
    @chmod("/home/artica/SQLITE/adagent.db", 0644);
    @chown("/home/artica/SQLITE/adagent.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS `adagent_service` (
				  `servicename` TEXT UNIQUE,
				  `crtype` INTEGER,
				  `ipaddrport` text UNIQUE,
				  `listen_ip` TEXT ,
				  `listen_port` INTEGER NOT NULL,
				  `dispatch_mode` TEXT ,
				  `client_timout` INTEGER NOT NULL,
				  `checkup_interval` INTEGER NOT NULL,
				  `wakeup_interval` INTEGER NOT NULL,
				  `loadbalancetype` INTEGER,
				  `tunnel_mode` INTEGER,
				  `enabled` INTEGER DEFAULT '1',
				  `servicetype` INTEGER,
				  `transparent` INTEGER,
				  `transparentsrcport` INTEGER NOT NULL,
				  `MainConfig` TEXT
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS `adagent_backends` (
		    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`servicename` TEXT ,
				`backendname` TEXT NOT NULL,
				`backendntoken` TEXT NOT NULL,
				`sitename` TEXT ,
				`listen_ip` TEXT ,
				`listen_port` INTEGER NOT NULL,
				`bweight` INTEGER NOT NULL DEFAULT '1',
				`enabled` INTEGER DEFAULT '1',
				`MainConfig` TEXT,
				`localInterface` TEXT)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS `adagent_backends_link` (
		    	 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`gpid` INTEGER NOT NULL,
				`backendid` INTEGER NOT NULL
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    $sql="CREATE TABLE IF NOT EXISTS `adagent_backends_groups` (
			    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`groupname` TEXT ,
				`servicename` TEXT ,
				`enabled` INTEGER DEFAULT '1',
				`default` INTEGER,
				`MainConfig` TEXT
				 )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `adagent_acls_rules` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`servicename` TEXT ,
			`rulename` TEXT ,
			`rule_action` INTEGER  default 0,
			`rule_action_data` TEXT NULL,
			`zorder` INTEGER  DEFAULT '0')";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `adagent_acls_link` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`ruleid` INTEGER NOT NULL,
			`groupid` INTEGER NOT NULL,
			`operator` INTEGER,
			`revert` INTEGER,
			`zorder` INTEGER)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `adagent_acls_groups` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`groupname` TEXT NOT NULL,
			`grouptype` TEXT NOT NULL,
			`enabled` INTEGER default 1,
			`zorder` smallint(5) NOT NULL default 1 )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `adagent_acls_items` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`groupid` INTEGER NOT NULL,
			`pattern` TEXT )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    return true;
}
function haproxy_tables():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/haproxy.db");
    @chmod("/home/artica/SQLITE/haproxy.db", 0644);
    @chown("/home/artica/SQLITE/haproxy.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS `haproxy` (
				  `servicename` TEXT UNIQUE,
				  `crtype` INTEGER,
				  `ipaddrport` text UNIQUE,
				  `listen_ip` TEXT ,
				  `listen_port` INTEGER NOT NULL,
				  `dispatch_mode` TEXT ,
				  `client_timout` INTEGER NOT NULL,
				  `checkup_interval` INTEGER NOT NULL,
				  `wakeup_interval` INTEGER NOT NULL,
				  `loadbalancetype` INTEGER,
				  `tunnel_mode` INTEGER,
				  `enabled` INTEGER DEFAULT '1',
				  `servicetype` INTEGER,
				  `transparent` INTEGER,
				  `transparentsrcport` INTEGER NOT NULL,
				  `MainConfig` TEXT
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `hacluster_backends` (
		        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`backendname` TEXT NOT NULL,
				`realname` TEXT NULL,
				`listen_ip` TEXT ,
				`artica_port` INTEGER NOT NULL,
				`listen_port` INTEGER NOT NULL,
				`bweight` INTEGER NOT NULL DEFAULT '1',
				`enabled` INTEGER NOT NULL DEFAULT '1',
                `status` INTEGER NOT NULL DEFAULT '0',
                `microproxy` INTEGER NOT NULL DEFAULT '0',
                `updated` INTEGER NOT NULL DEFAULT '0',
				`options` TEXT)";

    $q->QUERY_SQL($sql);
    if(!$q->FIELD_EXISTS("hacluster_backends","realname")){
        $q->QUERY_SQL("ALTER TABLE hacluster_backends ADD `realname` TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("hacluster_backends","microproxy")){
        $q->QUERY_SQL("ALTER TABLE hacluster_backends ADD `microproxy` INTEGER NOT NULL DEFAULT 0");
    }
    if(!$q->FIELD_EXISTS("hacluster_backends","updated")){
        $q->QUERY_SQL("ALTER TABLE hacluster_backends ADD `updated` INTEGER NOT NULL DEFAULT 0");
    }


    $sql="CREATE TABLE IF NOT EXISTS `haproxy_backends` (
		    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`servicename` TEXT ,
				`backendname` TEXT NOT NULL,
				`sitename` TEXT ,
				`listen_ip` TEXT ,
				`listen_port` INTEGER NOT NULL,
				`bweight` INTEGER NOT NULL DEFAULT '1',
				`enabled` INTEGER DEFAULT '1',
				`MainConfig` TEXT,
				`localInterface` TEXT)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }



    $sql="CREATE TABLE IF NOT EXISTS `haproxy_backends_link` (
		    	 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`gpid` INTEGER NOT NULL,
				`backendid` INTEGER NOT NULL
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `haproxy_backends_groups` (
			    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`groupname` TEXT ,
				`servicename` TEXT ,
				`enabled` INTEGER DEFAULT '1',
				`default` INTEGER,
				`MainConfig` TEXT
				 )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `haproxy_acls_rules` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`servicename` TEXT ,
			`rulename` TEXT ,
			`rule_action` INTEGER  default 0,
			`rule_action_data` TEXT NULL,
			`zorder` INTEGER  DEFAULT '0')";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `haproxy_acls_link` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`ruleid` INTEGER NOT NULL,
			`groupid` INTEGER NOT NULL,
			`operator` INTEGER,
			`revert` INTEGER,
			`zorder` INTEGER)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `haproxy_acls_groups` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`groupname` TEXT NOT NULL,
			`grouptype` TEXT NOT NULL,
			`enabled` INTEGER default 1,
			`zorder` smallint(5) NOT NULL default 1 )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `haproxy_acls_items` (
			 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`groupid` INTEGER NOT NULL,
			`pattern` TEXT )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    return true;
}
function postfix_events():bool{
    echo "[".__LINE__."]: Create table postfix_search FROM nothing\n";
    $q=new lib_sqlite("/home/artica/SQLITE/postfix_events.db");
    @chmod("/home/artica/SQLITE/postfix_events.db", 0644);
    @chown("/home/artica/SQLITE/postfix_events.db", "www-data");
    chgrp("/home/artica/SQLITE/postfix_events.db", "www-data");

    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `postfix_search` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`status` integer,
			`maxlines` integer,
			time integer,
			therms text,
			fsize INTEGER,
			fpath text ) ");



    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n\n";
        return false;
    }
    return true;
}
function spammassassin_tables():bool{
    echo "[".__LINE__."]: CREATE table meta_rules\n";
    $q=new lib_sqlite("/home/artica/SQLITE/spamassassin.db");
    if (is_file("/home/artica/SQLITE/spamassassin.db")) {
        @chmod("/home/artica/SQLITE/spamassassin.db", 0644);
        @chown("/home/artica/SQLITE/spamassassin.db", "www-data");
        chgrp("/home/artica/SQLITE/spamassassin.db", "www-data");
    }

    $sql="CREATE TABLE IF NOT EXISTS `meta_rules` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		 `rulename` TEXT ,
		 `describe` TEXT,
		 `enabled` INTEGER NOT NULL DEFAULT 1,
		 `finalscore` INTEGER NOT NULL,
		 `calculation` INTEGER )";

    $q->QUERY_SQL($sql);

    echo "[".__LINE__."]: CREATE table sub_rules\n";
    $sql="CREATE TABLE IF NOT EXISTS `sub_rules` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		 `meta_id` INTEGER NOT NULL,
		 `ruletype` TEXT ,
		 `enabled` INTEGER NOT NULL DEFAULT 1,
		 `header` TEXT,
		 `pattern` TEXT )";

    $q->QUERY_SQL($sql);


    echo "[".__LINE__."]: CREATE table whitelists\n";
    $sql="CREATE TABLE IF NOT EXISTS `whitelists` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		 `pattern` TEXT )";

    $q->QUERY_SQL($sql);


    echo "[".__LINE__."]: Migrate table spamasssin_baddomains FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `spamasssin_baddomains` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT,`pattern` TEXT UNIQUE, zdate TEXT )";
    $q->QUERY_SQL($sql);

    echo "[".__LINE__."]: Migrate table spamasssin_escrap FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `spamasssin_escrap` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `pattern` TEXT UNIQUE, zdate TEXT)";
    $q->QUERY_SQL($sql);


    echo "[".__LINE__."]: Migrate table spamasssin_subjects FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `spamasssin_subjects` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`pattern` TEXT UNIQUE,zdate TEXT)";
    $q->QUERY_SQL($sql);

    echo "[".__LINE__."]: Migrate table spamasssin_raw FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `spamasssin_raw` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`pattern` TEXT UNIQUE,zdate TEXT)";
    $q->QUERY_SQL($sql);



    echo "[".__LINE__."]: Migrate table mimedefang_antivirus FROM artica_backup\n";
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `mimedefang_antivirus` ( `zmd5` TEXT PRIMARY KEY NOT NULL,  `mailfrom` TEXT NOT NULL, `mailto` TEXT NOT NULL,`type` INTEGER )");


    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS mailfrom ON mimedefang_antivirus (mailfrom)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS mailto ON mimedefang_antivirus (mailto)");


    echo "[".__LINE__."]: Create table mimedefang_backup\n";
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `mimedefang_backup` (
			`zmd5` TEXT PRIMARY KEY NOT NULL,  
			`mailfrom` TEXT NOT NULL, 
			`mailto` TEXT NOT NULL,
			`retentiontime` INTEGER 
			 )");




    echo "[".__LINE__."]: Create table mimedefang_spamassassin\n";
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `mimedefang_spamassassin` (
			`zmd5` TEXT PRIMARY KEY NOT NULL,  
			 `mailfrom` TEXT NOT NULL, 
			 `mailto` TEXT NOT NULL,
			 `XSpamStatusHeaderScore` INTEGER,
			 `SpamAssBlockWithRequiredScore` INTEGER,
			 `SpamAssassinRequiredScore` INTEGER,
			 `MimeDefangQuarteMail` INTEGER,
			 `MimeDefangMaxQuartime` INTEGER,
			 `MimeDefangQuartDest` TEXT)");

return true;

}
function postfix_tables():bool{
    echo "[".__LINE__."]: Migrate table relay_domains_restricted FROM artica_backup\n";

    $q=new lib_sqlite("/home/artica/SQLITE/postqueue.db");
    $sql="CREATE TABLE IF NOT EXISTS `postqueue` (
			  `msgid` TEXT PRIMARY KEY NOT NULL,
			  `instance` TEXT NOT NULL,
			  `zDate` TEXT NOT NULL,
			  `from` TEXT NOT NULL,
			  `recipients` TEXT NOT NULL,
			  `context` TEXT NOT NULL,
			  `event` TEXT NOT NULL,
			  `removed` INTEGER NOT NULL DEFAULT '0',
			  `from_domain` TEXT NOT NULL,
			  `size` INTEGER NOT NULL)";
    $q->QUERY_SQL($sql);
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_context ON postqueue (context,removed)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_from_domain ON postqueue (from_domain,`from`)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_zDate ON postqueue (zDate)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_instance ON postqueue (instance)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_size ON postqueue (size)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS idx_recipients ON postqueue (recipients)");


    $q=new lib_sqlite("/home/artica/SQLITE/postfix.db");
    if (is_file("/home/artica/SQLITE/postfix.db")) {
        @chmod("/home/artica/SQLITE/postfix.db", 0644);
        @chown("/home/artica/SQLITE/postfix.db", "www-data");
        chgrp("/home/artica/SQLITE/postfix.db", "www-data");
    }

    $sql="CREATE TABLE IF NOT EXISTS `relay_domains_restricted` (`domainname` text PRIMARY KEY)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS `smtp_rules` (
        id INTEGER PRIMARY KEY AUTOINCREMENT , 
        rulename TEXT NOT NULL,ruletype TEXT NOT NULL,
        instanceid INTEGER NOT NULL DEFAULT 1,
        action TEXT NOT NULL,action_value TEXT,
        items TEXT,zorder INTEGER, 
        zdate DATETIME, 
        enabled INTEGER
    )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `postfix_transport_mailbox` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT , 
				  `uid` TEXT NOT NULL,
				  `xType` INTEGER NOT NULL,
				  `lmtp_address` TEXT NOT NULL,
				  `hostname` TEXT NOT NULL )";
    $q->QUERY_SQL($sql);



    echo "[".__LINE__."]: Migrate table transport_maps FROM NONE\n";
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `transport_maps` (addr varchar(256) PRIMARY KEY,
			direction INTEGER,
			service TEXT,
			enabled INTEGER,
			tls_enabled INTEGER,
			tls_mode text,
			nexthope TEXT,
			nextport INTEGER,
			OtherDomains TEXT,
			auth INTEGER DEFAULT 0,
			username TEXT,
			password TEXT
			
			) ");
    $q->QUERY_SQL($sql);
    if (!$q->FIELD_EXISTS("transport_maps", "auth")) {
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `auth` INTEGER DEFAULT 0");
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `username` TEXT");
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `password` TEXT");
    }
    if (!$q->FIELD_EXISTS("transport_maps", "OtherDomains")) {
        $q->QUERY_SQL("ALTER TABLE transport_maps ADD `OtherDomains` TEXT");
    }

    echo "[".__LINE__."]: Migrate table smtp_tls_policy_maps FROM NONE\n";
    $sql="CREATE TABLE IF NOT EXISTS `smtp_tls_policy_maps` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		 `hostname` TEXT ,
		 `servername` TEXT ,
		 `port` INTEGER NOT NULL,
		 `MX_lookups` INTEGER,
		 `tls_option` TEXT NULL,
		 `protocols` TEXT NULL,
		 `ciphers` TEXT NULL,
		 `tls_match` TEXT NULL,
		 `fingerprint` TEXT )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    echo "[".__LINE__."]: Migrate table postfix_params FROM NONE\n";
    $sql="CREATE TABLE IF NOT EXISTS `postfix_params` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				  `key` TEXT ,
				  `value` TEXT,
				  `ou` TEXT,
				  `ValueTEXT` TEXT,
				  `uuid` TEXT ,
				  `ip_address` TEXT )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    echo "[".__LINE__."]: Create table smtpd_milter_maps FROM NONE\n";
    $sql="CREATE TABLE IF NOT EXISTS `smtpd_milter_maps` (
	`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	`pattern` TEXT UNIQUE NOT NULL,
	`instanceid` INTEGER NOT NULL DEFAULT 0,
	`enabled` INTEGER NOT NULL DEFAULT 1)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Create table postfix_diffusion FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `postfix_diffusion` (
	 `recipient` TEXT PRIMARY KEY NOT NULL,
	 `enabled` INTEGER NOT NULL DEFAULT '1',
	 `hostname` varchar(255) NOT NULL )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    echo "[".__LINE__."]: Create table postfix_diffusion_list FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `postfix_diffusion_list` (
		  `recipient` varchar(255) NOT NULL,
		  `mainlist` varchar(255) NOT NULL,
		  `enabled` INTEGER NOT NULL DEFAULT '1')";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    echo "[".__LINE__."]: Create table smtp_sasl_password_maps\n";
    $sql="CREATE TABLE IF NOT EXISTS `smtp_sasl_password_maps` (
		hostname TEXT PRIMARY KEY,
		username TEXT,
		password TEXT)";

    $q->QUERY_SQL($sql);

    echo "[".__LINE__."]: Create table milterregex_acls\n";

    $sql="CREATE TABLE IF NOT EXISTS milterregex_acls (
			  `zmd5` TEXT  PRIMARY KEY,
			  `instance` TEXT  NOT NULL,
			  `method` TEXT  NOT NULL,
			  `type` TEXT  NOT NULL,
			  `enabled` INTEGER  NOT NULL,
			  `pattern` TEXT  NOT NULL,
			  `description` TEXT  NOT NULL,
			  `reverse` INTEGER NOT NULL,
			  `extended` INTEGER NOT NULL,
			  `zDate` TEXT)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error."\n$sql\n";
    }


    echo "[".__LINE__."]: Create table smtp_generic_maps\n";

    $sql="CREATE TABLE IF NOT EXISTS `smtp_generic_maps` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`generic_from` TEXT NOT NULL ,
			`generic_to` TEXT NOT NULL ,
			instanceid INTEGER NOT NULL DEFAULT 0,
			recipient_canonical_maps INTEGER NOT NULL DEFAULT 0,
			sender_canonical_maps INTEGER NOT NULL DEFAULT 0,
			smtp_generic_maps INTEGER NOT NULL DEFAULT 1,
			`zmd5` TEXT NOT NULL UNIQUE
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo $q->mysql_error."\n$sql\n";
    }
    $sql="CREATE TABLE IF NOT EXISTS `postfix_multi` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `key` TEXT NOT NULL,
        `value` TEXT NOT NULL,
        `ou` TEXT NULL,
        `instanceid` INTEGER NOT NULL,
        `ValueTEXT` TEXT NOT NULL,
        `uuid` TEXT NOT NULL );";
    $q->QUERY_SQL($sql);

    $sql="CREATE TABLE IF NOT EXISTS `postfix_instances` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
        `instancename` TEXT NOT NULL,
         interface TEXT NOT NULL,
        `enabled` INTEGER NOT NULL DEFAULT 1);";
    $q->QUERY_SQL($sql);



    return spammassassin_tables();
}
function imapbox_tables():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/imapbox.db");

    $sql="CREATE TABLE IF NOT EXISTS `accounts` (
				`id` INTEGER PRIMARY KEY AUTOINCREMENT,
				`userid` TEXT UNIQUE,
				`passwd` TEXT NOT NULL DEFAULT '',
				`database_size` INTEGER NOT NULL DEFAULT '0',
				`enabled` INTEGER NOT NULL DEFAULT '1'
		)";


    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }else{
        echo "ImapBox: Accounts OK\n";
    }

    $sql="CREATE TABLE IF NOT EXISTS `mailboxes` (
				`id` INTEGER PRIMARY KEY AUTOINCREMENT,
				`account_id` INTEGER NOT NULL,
				`username` TEXT NOT NULL DEFAULT '',
				`password` TEXT NOT NULL DEFAULT '',
				`hostname` TEXT NOT NULL,
				`remote_folder` TEXT NOT NULL DEFAULT 'INBOX',
				`remote_port` INTEGER NOT NULL DEFAULT '143',
				`database_size` INTEGER NOT NULL DEFAULT '0',
				`messages` INTEGER NOT NULL DEFAULT '0',
				`scanned` INTEGER NOT NULL DEFAULT '0',
				`enabled` INTEGER NOT NULL DEFAULT '1'
		)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }else{
        echo "ImapBox: mailboxes OK\n";
    }
    return true;
}
function hamrp():bool{
    $q = new lib_sqlite("/home/artica/SQLITE/hamrp.db");
    
    if($q->TABLE_EXISTS("hamrp")){
         if(!$q->FIELD_EXISTS("hamrp","ssl")){
            $q->QUERY_SQL("DROP TABLE hamrp");
        }
    }
    
    $sql="CREATE TABLE IF NOT EXISTS `hamrp` (
				`uuid` TEXT PRIMARY KEY,
				`nodename` TEXT NOT NULL DEFAULT 'New node',
				`nodetype` INTEGER NOT NULL DEFAULT '0',
				`groupid` INTEGER NOT NULL DEFAULT '0',
				`ipaddr` TEXT NOT NULL DEFAULT '0.0.0.0',
				`port` INTEGER NOT NULL DEFAULT '9503',
				`enabled` INTEGER NOT NULL DEFAULT '1',
				`ssl` INTEGER NOT NULL DEFAULT '0',
				`hostname` TEXT NOT NULL DEFAULT 'localhost.localdomain',
				`status` INTEGER NOT NULL DEFAULT '0',
				`lastsaved` INTEGER NOT NULL DEFAULT '0',
				`zOrder` INTEGER NOT NULL DEFAULT '1',
				`NginxRun` INTEGER NOT NULL DEFAULT '0',
				`HaProxyRun` INTEGER NOT NULL DEFAULT '0',
                `cpu` INTEGER NOT NULL DEFAULT '1',
                `mem` INTEGER NOT NULL DEFAULT '100',
                `version` TEXT NOT NULL DEFAULT '0.0.0',
                `CpuPourc` TEXT NOT NULL DEFAULT '0',
                `MemRow` TEXT NOT NULL DEFAULT '0.0,0,0',
                 kernel TEXT NOT NULL DEFAULT '0',
                 NginxVersion TEXT NOT NULL DEFAULT '',
                 HaProxyVersion TEXT NOT NULL DEFAULT '',
                 DistributionName TEXT NOT NULL DEFAULT ''
                                   
                 
    )";


    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    if(!$q->FIELD_EXISTS("hamrp","enabled")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD enabled INTEGER NOT NULL DEFAULT '1'");
    }
    if(!$q->FIELD_EXISTS("hamrp","DistributionName")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD DistributionName TEXT NOT NULL DEFAULT ''");
    }



    if(!$q->FIELD_EXISTS("hamrp","NginxRun")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD NginxRun INTEGER NOT NULL DEFAULT '0'");
        $q->QUERY_SQL("ALTER TABLE hamrp ADD HaProxyRun INTEGER NOT NULL DEFAULT '0'");
    }

    if(!$q->FIELD_EXISTS("hamrp","CpuPourc")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD CpuPourc TEXT NOT NULL DEFAULT '0'");
        $q->QUERY_SQL("ALTER TABLE hamrp ADD MemRow TEXT NOT NULL DEFAULT '0.0,0,0'");
    }
    if(!$q->FIELD_EXISTS("hamrp","NginxVersion")){
        $q->QUERY_SQL("ALTER TABLE hamrp ADD NginxVersion TEXT NOT NULL DEFAULT ''");
        if(!$q->ok){
            echo $q->mysql_error."\n";
        }
        $q->QUERY_SQL("ALTER TABLE hamrp ADD HaProxyVersion TEXT NOT NULL DEFAULT ''");
        if(!$q->ok){
            echo $q->mysql_error."\n";
        }
    }

    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS groupid ON hamrp (groupid)");

    $sql="CREATE TABLE IF NOT EXISTS `groups` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`groupname` TEXT NOT NULL DEFAULT 'New group',
				`comment` TEXT NOT NULL,
				`EnableRedis` INTEGER NOT NULL DEFAULT '0',
				`enabled` INTEGER NOT NULL DEFAULT '1'
		)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";

    }
    if(!$q->FIELD_EXISTS("groups","EnableRedis")){
        $q->QUERY_SQL("ALTER TABLE groups ADD EnableRedis INTEGER NOT NULL DEFAULT 0");
    }


    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS privs (`ID` INTEGER PRIMARY KEY AUTOINCREMENT, groupid INT,dngroup TEXT )");

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }

    $results=$q->QUERY_SQL("SELECT ID FROM groups");

    foreach ($results as $index=>$ligne){
        $ID=intval($ligne["ID"]);
        if($ID==0){continue;}
        CheckNGINXTables($ID);

    }
    return true;
}

function proftpd_table():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/ftpusers.db");
    $sql="CREATE TABLE IF NOT EXISTS `ftpuser` (
				`id` INTEGER PRIMARY KEY AUTOINCREMENT,
				`userid` TEXT NOT NULL DEFAULT '',
				`passwd` TEXT NOT NULL DEFAULT '',
				`uid` INTEGER NOT NULL DEFAULT '5500',
				`gid` INTEGER NOT NULL DEFAULT '5500',
				`homedir` TEXT NOT NULL DEFAULT '',
				`shell` TEXT NOT NULL DEFAULT '/bin/false',
				`count` INTEGER NOT NULL DEFAULT '0',
				`accessed` text,
				`modified` text,
				`LoginAllowed` text NOT NULL DEFAULT 'true'
		)";


    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyUserid ON ftpuser (userid,passwd)");


    $sql="CREATE TABLE IF NOT EXISTS `ftpgroup` (
		`groupname` TEXT NOT NULL DEFAULT '',
		`gid` INTEGER NOT NULL DEFAULT '5500',
		`members` TEXT NOT NULL DEFAULT ''
		)";
    $q->QUERY_SQL($sql);

    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS Keygpid ON ftpgroup (gid)");

    // `quota_type` enum('user','group','class','all')
    // `par_session` enum('false','true')
    //`limit_type` enum('soft','hard') NOT NULL DEFAULT 'soft',

    $sql="CREATE TABLE IF NOT EXISTS `ftpquotalimits` (
				`name` TEXT DEFAULT NULL,
				`quota_type` text NOT NULL DEFAULT 'user',
				`par_session` text NOT NULL DEFAULT 'false',
				`limit_type` text NOT NULL DEFAULT 'soft',
				`bytes_up_limit` FLOAT NOT NULL DEFAULT '0',
				`bytes_down_limit` FLOAT NOT NULL DEFAULT '0',
				`bytes_transfer_limit` FLOAT NOT NULL DEFAULT '0',
				`files_up_limit` INTEGER NOT NULL DEFAULT '0',
				`files_down_limit` INTEGER NOT NULL DEFAULT '0',
				`files_transfer_limit` INTEGER NOT NULL DEFAULT '0'
		);
		";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    //`quota_type` enum('user','group','class','all') NOT NULL DEFAULT 'user',
    $sql="CREATE TABLE IF NOT EXISTS `ftpquotatotal` (
				`name` TEXT NOT NULL DEFAULT '',
				`quota_type`text NOT NULL DEFAULT 'user',
				`bytes_up_total` FLOAT NOT NULL DEFAULT '0',
				`bytes_down_total` FLOAT NOT NULL DEFAULT '0',
				`bytes_transfer_total` FLOAT NOT NULL DEFAULT '0',
				`files_up_total` INTEGER NOT NULL DEFAULT '0',
				`files_down_total` INTEGER NOT NULL DEFAULT '0',
				`files_transfer_total` INTEGER NOT NULL DEFAULT '0')";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    return true;
}
function ipinfo():bool{

    $q=new lib_sqlite("/home/artica/SQLITE/ipinfo.db");
    @chmod("/home/artica/SQLITE/ipinfo.db", 0644);
    @chown("/home/artica/SQLITE/ipinfo.db", "www-data");
    $sql="CREATE TABLE IF NOT EXISTS `ipinfo` (
          `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		  `ipaddr` TEXT NOT NULL UNIQUE,
		  `content` TEXT
		  )";
    $q->QUERY_SQL($sql);
    return true;
}


