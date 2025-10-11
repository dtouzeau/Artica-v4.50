<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.mysql.xapian.builder.inc');

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
    if ($argv[1] == "--rpz") {rpz_database();exit;}
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

    if ($argv[1] == "--admins") {
        admins_tables();
        exit;
    }
    if ($argv[1] == "--acls") {
        acls_tables();
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
    if ($argv[1] == "--dns") {
        dns_tables();
        exit;
    }
    if ($argv[1] == "--ftp") {
        proftpd_table();
        exit;
    }

    if ($argv[1] == "--ntp") {
        ntp();
        exit;
    }
    if ($argv[1] == "--dhcp") {
        dhcpd();
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
radius_db();
upgrade_smtp_tables();
rpz_database();

if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
sidentity();
if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
migrate_interfaces();

if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
imapbox_tables();
if(!$force) {if(system_is_overloaded(__FILE__)){ die(); }}
siege_db();
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
function siege_db():bool{

    $q=new lib_sqlite("/home/artica/SQLITE/siege.db");
    $sql="CREATE TABLE IF NOT EXISTS reports ( 
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT, 
     users INTEGER,
     target TEXT,
    `zdate` TEXT, 
    `zend` TEXT,                                   
    `subject` TEXT,
     report TEXT                               
    )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
        return false;
    }
    return true;

}

function sshd():bool{
    @mkdir("/home/artica/SQLITE", 0755, true);
    $q=new lib_sqlite("/home/artica/SQLITE/sshd.db");
    @chmod("/home/artica/SQLITE/sshd.db", 0644);
    @chown("/home/artica/SQLITE/sshd.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);




    $sql="CREATE TABLE IF NOT EXISTS sshd_privkeys (
			`username` TEXT NOT NULL PRIMARY KEY,
			`publickey` TEXT,
			`privatekey` TEXT,
			`slength` INTEGER NOT NULL DEFAULT 0,
			`enabled` INTEGER NOT NULL DEFAULT 1,
			`passphrase` TEXT NULL
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS sshd_allowips ( `pattern` TEXT NOT NULL PRIMARY KEY )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS sshproxy ( 
    `username` TEXT NOT NULL PRIMARY KEY,
    `hostname` TEXT NOT NULL,
    `port` INTEGER NOT NULL DEFAULT 22,
    `enabled` INTEGER NOT NULL DEFAULT 1 )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "Fatal: $q->mysql_error (".__LINE__.")\n";
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS sshproxy_keys ( 
    `hostname` TEXT NOT NULL PRIMARY KEY,
    `privkey` TEXT NOT NULL )";
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




function dns_tables():bool{
    rpz_database();
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    @chmod("/home/artica/SQLITE/dns.db", 0644);
    @chown("/home/artica/SQLITE/dns.db", "www-data");




    echo "[".__LINE__."]: Create table dnsinfos\n";
    $sql="CREATE TABLE IF NOT EXISTS `dnsinfos` (domain_id INTEGER PRIMARY KEY,name text,cialdom INTEGER,renewdate INTEGER,zinfo text,explain text)";
    $q->QUERY_SQL($sql);


    echo "[".__LINE__."]: Create table DNSFilterSettings\n";
    $sql="CREATE TABLE IF NOT EXISTS `DNSFilterSettings` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`zKey` TEXT NOT NULL UNIQUE,`zvalue` TEXT)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "FATAL: *********************** $q->mysql_error ***********************\n";
    }

    echo "[".__LINE__."]: Create table webfilter_rules\n";
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_rules` (
		 			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				  	groupmode INTEGER,
				  	enabled INTEGER,
					groupname TEXT,
					BypassSecretKey TEXT,
					endofrule TEXT ,
					blockdownloads INTEGER DEFAULT '0' ,
					naughtynesslimit INTEGER DEFAULT '50' ,
					searchtermlimit INTEGER DEFAULT '30' ,
					bypass INTEGER DEFAULT '0' ,
					deepurlanalysis  INTEGER DEFAULT '0' ,
					UseExternalWebPage INTEGER DEFAULT '0' ,
					UseReferer INTEGER DEFAULT '0' ,
					ExternalWebPage TEXT ,
					freeweb TEXT ,
					sslcertcheck INTEGER DEFAULT '0' ,
					sslmitm INTEGER DEFAULT '0',
					GoogleSafeSearch INTEGER DEFAULT '0',
					`embeddedurlweight` INTEGER,
					TimeSpace TEXT,
					TemplateError TEXT,
					TemplateColor1 TEXT,
					TemplateColor2 TEXT,
					RewriteRules TEXT,
					zOrder INTEGER,
					AllSystems INTEGER,
					`http_code` INTEGER,
					UseSecurity INTEGER
				) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }

    echo "[".__LINE__."]: Create table webfilter_blks\n";
    $q=new lib_sqlite("/home/artica/SQLITE/dns.db");
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_blks` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, webfilter_id INTEGER,modeblk INTEGER,category INTEGER NOT NULL) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }


    echo "[".__LINE__."]: Create table webfilters_dtimes_rules \n";

    $sql="CREATE TABLE IF NOT EXISTS `webfilters_dtimes_rules` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`TimeName` VARCHAR( 128 ) NOT NULL ,`TimeCode` TEXT NOT NULL ,`enabled` INTEGER ,`ruleid` INT ) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }


    echo "[".__LINE__."]: Create table webfilter_blklnk\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_blklnk` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,zmd5 TEXT UNIQUE,webfilter_blkid INTEGER,webfilter_ruleid  INTEGER, blacklist INTEGER NOT NULL DEFAULT '1' )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }

    echo "[".__LINE__."]: Create table webfilter_ipsources\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_ipsources` (ipaddr TEXT,description TEXT,ruleid INTEGER)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
    }

    echo "[".__LINE__."]: Create table webfilter_certs\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_certs` (`zmd5` TEXT PRIMARY KEY,`certname` TEXT NOT NULL, `certdata` TEXT NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    fill_webfilter_certs("dns.db");

    echo "[".__LINE__."]: Create table webfilter_whitelists\n";

    $sql="CREATE TABLE IF NOT EXISTS `webfilter_whitelists` 
			(`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`pattern` TEXT,`type` INTEGER,enabled INTEGER DEFAULT 1)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Create table webfilter_blacklists\n";

    $sql="CREATE TABLE IF NOT EXISTS `webfilter_blacklists` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`pattern` TEXT,`type` INTEGER,enabled INTEGER DEFAULT 1)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }



    $Count=$q->COUNT_ROWS("webfilter_whitelists");
    if ($Count<3) {
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('artica.fr','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('articatech.net','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('msftncsi.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('google.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('googleapis.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('gstatic.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('apple.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('microsoft.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('kaspersky.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('googleusercontent.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('mozilla.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('mozilla.org','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('kaspersky-labs.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('akamaiedge.net','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('akamai.net','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('symcd.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('cloudflare.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('amazonaws.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('edgekey.net','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('akadns.net','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('nist.gov','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('tp-link.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('ntp.org','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('playstation.net','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('amazonaws.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('microsoft.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('akamaiedge.net','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('trendmicro.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('ubuntu.com','1',1)");
        $q->QUERY_SQL("INSERT INTO webfilter_whitelists (`pattern`,`type`,`enabled`) VALUES ('127.0.0.1','0',1)");
    }


    echo "[".__LINE__."]: Create table dns_pools\n";
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





function suricata():bool{
    @mkdir("/home/artica/SQLITE", 0755, true);
    $q=new lib_sqlite("/home/artica/SQLITE/suricata.db");
    @chmod("/home/artica/SQLITE/suricata.db", 0644);
    @chown("/home/artica/SQLITE/suricata.db", "www-data");

    if ($q->COUNT_ROWS("suricata_rules_packages")==0) {
        $sql="INSERT OR IGNORE INTO suricata_rules_packages (rulefile,enabled,category) VALUES
				('botcc.rules',0,'DMZ'),
				('ciarmy.rules',0,'DMZ'),
				('compromised.rules','0','DMZ'),
				('drop.rules',1,'DMZ'),
				('emerging-activex.rules',1,'WEB'),
				('emerging-attack_response.rules',1,'ALL'),
				('emerging-chat.rules',0,'WEB'),
				('emerging-current_events.rules',0,'ALL'),
				('emerging-dns.rules',0,'DMZ'),
				('emerging-dos.rules',0,'DMZ'),
				('emerging-exploit.rules',0,'DMZ'),
				('emerging-ftp.rules',0,'DMZ'),
				('emerging-games.rules',0,'ALL'),
				('emerging-icmp_info.rules',0,'ALL'),
				('emerging-icmp.rules',0,'ALL'),
				('emerging-imap.rules',0,'DMZ'),
				('emerging-inappropriate.rules',0,'WEB'),
				('emerging-malware.rules',1,'WEB'),
				('emerging-mobile_malware.rules',0,'WEB'),
				('emerging-netbios.rules',0,'ALL'),
				('emerging-p2p.rules',0,'WEB'),
				('emerging-policy.rules',1,'WEB'),
				('emerging-pop3.rules',0,'DMZ'),
				('emerging-rpc.rules',0,'ALL'),
				('emerging-scada.rules',0,'ALL'),
				('emerging-scan.rules',1,'ALL'),
				('emerging-shellcode.rules',1,'ALL'),
				('emerging-smtp.rules',0,'DMZ'),
				('emerging-snmp.rules',0,'ALL'),
				('emerging-sql.rules',0,'ALL'),
				('emerging-telnet.rules',0,'ALL'),
				('emerging-tftp.rules',0,'ALL'),
				('emerging-trojan.rules',1,'ALL'),
				('emerging-user_agents.rules',0,'ALL'),
				('emerging-voip.rules',0,'ALL'),
				('emerging-web_client.rules',1,'HTTP'),
				('emerging-web_server.rules',0,'HTTP'),
				('emerging-web_specific_apps.rules',0,'HTTP'),
				('emerging-worm.rules',1,'ALL'),
				('tor.rules',0,'ALL'),
				('decoder-events.rules',0,'ALL'),
				('stream-events.rules',0,'ALL'),
				('http-events.rules',0,'HTTP'),
				('smtp-events.rules',0,'DMZ'),
				('dns-events.rules',0,'DMZ'),
				('tls-events.rules',0,'DMZ')";
        $q->QUERY_SQL($sql);
    }
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

function sidentity():bool{
    @mkdir("/home/artica/SQLITE", 0755, true);
    $qlite=new lib_sqlite("/home/artica/SQLITE/identity.db");
    @chmod("/home/artica/SQLITE/identity.db", 0644);
    @chown("/home/artica/SQLITE/identity.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);


    echo "[".__LINE__."]: Migrate table sidentity\n";
    $sql="CREATE TABLE IF NOT EXISTS `sidentity` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT,`skey` TEXT UNIQUE NOT NULL, `svalue` TEXT NOT NULL )";
    $qlite->QUERY_SQL($sql);
    if (!$qlite->ok) {
        echo "Fatal: $qlite->mysql_error (".__LINE__.")\n";
    }
    return true;
}




function migrate_interfaces():bool
{
    @mkdir("/home/artica/SQLITE", 0755, true);
    $qlite=new lib_sqlite("/home/artica/SQLITE/interfaces.db");
    @chmod("/home/artica/SQLITE/interfaces.db", 0644);
    @chown("/home/artica/SQLITE/interfaces.db", "www-data");
    @chown("/home/artica/SQLITE", "www-data");
    @chmod("/home/artica/SQLITE", 0755);
    echo "[".__LINE__."]: Migrate table nics FROM artica_backup\n";




    $qlite=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    @chmod("/home/artica/SQLITE/proxy.db", 0644);
    @chown("/home/artica/SQLITE/proxy.db", "www-data");

    $sql="INSERT OR IGNORE INTO templates_manager (ID,TemplateName,CssContent,headContent,BodyContent)
		 VALUES(1,'Red error page','ICAgIGJvZHkgewogICAgICAgIGNvbG9yOiAgICAgICAgICAgICNGRkZGRkY7IAogICAgICAgIGJhY2tncm91bmQtY29sb3I6ICNGRkZGRkY7IAogICAgICAgIGZvbnQtZmFtaWx5OiAgICAgIENhbGlicmksIENhbmRhcmEsIFNlZ29lLCAiU2Vnb2UgVUkiLCBPcHRpbWEsIEFyaWFsLCBzYW5zLXNlcmlmOyAKICAgICAgICBmb250LXdlaWdodDogICAgICBsaWdodGVyOwogICAgICAgIGZvbnQtc2l6ZTogICAgICAgIDE0cHQ7IAogICAgICAgIAogICAgICAgIG9wYWNpdHk6ICAgICAgICAgICAgMC4wOwogICAgICAgIHRyYW5zaXRpb246ICAgICAgICAgb3BhY2l0eSAyczsKICAgICAgICAtd2Via2l0LXRyYW5zaXRpb246IG9wYWNpdHkgMnM7CiAgICAgICAgLW1vei10cmFuc2l0aW9uOiAgICBvcGFjaXR5IDJzOwogICAgICAgIC1vLXRyYW5zaXRpb246ICAgICAgb3BhY2l0eSAyczsKICAgICAgICAtbXMtdHJhbnNpdGlvbjogICAgIG9wYWNpdHkgMnM7ICAgIAogICAgfQogICAgaDEgewogICAgICAgIGZvbnQtc2l6ZTogNzJwdDsgCiAgICAgICAgbWFyZ2luLWJvdHRvbTogMDsgCiAgICAgICAgZm9udC1mYW1pbHk6IENhbGlicmksIENhbmRhcmEsIFNlZ29lLCAiU2Vnb2UgVUkiLCBPcHRpbWEsIEFyaWFsLCBzYW5zLXNlcmlmOwogICAgICAgIG1hcmdpbi10b3A6IDAgOwogICAgfSAgICAKLmJhZHsgZm9udC1zaXplOiAxMTBweDsgZmxvYXQ6bGVmdDsgbWFyZ2luLXJpZ2h0OjMwcHg7IH0KLmJhZDpiZWZvcmV7IGNvbnRlbnQ6ICJcMjYzOSI7fQogICAgaDIgewogICAgICAgIGZvbnQtc2l6ZTogMjJwdDsgCiAgICAgICAgZm9udC1mYW1pbHk6IENhbGlicmksIENhbmRhcmEsIFNlZ29lLCAiU2Vnb2UgVUkiLCBPcHRpbWEsIEFyaWFsLCBzYW5zLXNlcmlmOyAKICAgICAgICBmb250LXdlaWdodDogbGlnaHRlcjsKICAgIH0gICAKICAgIGgzIHsKICAgICAgICBmb250LXNpemU6IDE4cHQ7IAogICAgICAgIGZvbnQtZmFtaWx5OiBDYWxpYnJpLCBDYW5kYXJhLCBTZWdvZSwgIlNlZ29lIFVJIiwgT3B0aW1hLCBBcmlhbCwgc2Fucy1zZXJpZjsgCiAgICAgICAgZm9udC13ZWlnaHQ6IGxpZ2h0ZXI7CiAgICAgICAgbWFyZ2luLWJvdHRvbTogMCA7CiAgICB9ICAgCiAgICAjd3JhcHBlciB7CiAgICAgICAgd2lkdGg6IDcwMHB4IDsKICAgICAgICBtYXJnaW4tbGVmdDogYXV0byA7CiAgICAgICAgbWFyZ2luLXJpZ2h0OiBhdXRvIDsKICAgIH0gICAgCiAgICAjaW5mbyB7CiAgICAgICAgd2lkdGg6IDYwMHB4IDsKICAgICAgICBtYXJnaW4tbGVmdDogYXV0byA7CiAgICAgICAgbWFyZ2luLXJpZ2h0OiBhdXRvIDsKICAgIH0gICAgCi5pbXBvcnRhbnR7CiAgICAgICAgZm9udC1zaXplOiAxOHB0OyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiBsaWdodGVyOwogICAgICAgIG1hcmdpbi1ib3R0b206IDAgOwogICAgfSAgICAKcCB7CiAgICAgICAgZm9udC1zaXplOiAxMnB0OyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiBsaWdodGVyOwogICAgICAgIG1hcmdpbi1ib3R0b206IDAgOwogICAgfSAgICAKICAgIHRkLmluZm9fdGl0bGUgeyAgICAKICAgICAgICB0ZXh0LWFsaWduOiByaWdodDsKICAgICAgICBmb250LXNpemU6ICAxMnB0OyAgCiAgICAgICAgbWluLXdpZHRoOiAxMDBweDsKICAgIH0KICAgIHRkLmluZm9fY29udGVudCB7CiAgICAgICAgdGV4dC1hbGlnbjogbGVmdDsKICAgICAgICBwYWRkaW5nLWxlZnQ6IDEwcHQgOwogICAgICAgIGZvbnQtc2l6ZTogIDEycHQ7ICAKICAgIH0KICAgIC5icmVhay13b3JkIHsKICAgICAgICB3aWR0aDogNTAwcHg7CiAgICAgICAgd29yZC13cmFwOiBicmVhay13b3JkOwogICAgfSAgICAKICAgIGEgewogICAgICAgIHRleHQtZGVjb3JhdGlvbjogdW5kZXJsaW5lOwogICAgICAgIGNvbG9yOiAjRkZGRkZGOyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiBsaWdodGVyOwogICAgfQogICAgYTp2aXNpdGVkewogICAgICAgIHRleHQtZGVjb3JhdGlvbjogdW5kZXJsaW5lOwogICAgICAgIGNvbG9yOiAjRkZGRkZGOyAKICAgIH0KCQkKCQkKCS5CdXR0b24yMDE0LWxnIHsKCWJvcmRlci1yYWRpdXM6IDZweCA2cHggNnB4IDZweDsKCS1tb3otYm9yZGVyLXJhZGl1czogNnB4IDZweCA2cHggNnB4OwoJLWtodG1sLWJvcmRlci1yYWRpdXM6IDZweCA2cHggNnB4IDZweDsKCS13ZWJraXQtYm9yZGVyLXJhZGl1czogNnB4IDZweCA2cHggNnB4OwoJZm9udC1zaXplOiAxOHB4OwoJbGluZS1oZWlnaHQ6IDEuMzM7CglwYWRkaW5nOiAxMHB4IDE2cHg7Cn0KLkJ1dHRvbjIwMTQtc3VjY2VzcyB7CmJhY2tncm91bmQtY29sb3I6ICM2MjVGRkQ7CmJvcmRlci1jb2xvcjogIzAwMDAwMDsKY29sb3I6ICNGRkZGRkY7Cn0KLkJ1dHRvbjIwMTQgewotbW96LXVzZXItc2VsZWN0OiBub25lOwpib3JkZXI6IDFweCBzb2xpZCB0cmFuc3BhcmVudDsKYm9yZGVyLXJhZGl1czogNHB4IDRweCA0cHggNHB4OwpjdXJzb3I6IHBvaW50ZXI7CmRpc3BsYXk6IGlubGluZS1ibG9jazsKZm9udC1zaXplOiAyMnB4Owpmb250LXdlaWdodDogbm9ybWFsOwpsaW5lLWhlaWdodDogMS40Mjg1NzsKbWFyZ2luLWJvdHRvbTogMDsKcGFkZGluZzogNnB4IDIycHg7CnRleHQtYWxpZ246IGNlbnRlcjsKdmVydGljYWwtYWxpZ246IG1pZGRsZTsKd2hpdGUtc3BhY2U6IG5vd3JhcDsKZm9udC1mYW1pbHk6IENhbGlicmksIENhbmRhcmEsIFNlZ29lLCAiU2Vnb2UgVUkiLCBPcHRpbWEsIEFyaWFsLCBzYW5zLXNlcmlmOwp9Cg==',
		'PCFET0NUWVBFIEhUTUw+CjxodG1sPgo8aGVhZD4KPHRpdGxlPiVUSVRMRV9IRUFEJTwvdGl0bGU+CiVKUVVFUlklCiVDU1MlCjxzY3JpcHQgdHlwZT0idGV4dC9qYXZhc2NyaXB0Ij4KICAgIGZ1bmN0aW9uIGJsdXIoKXsgfQogICAgZnVuY3Rpb24gY2hlY2tJZlRvcE1vc3RXaW5kb3coKQogICAgewogICAgICAgIGlmICh3aW5kb3cudG9wICE9IHdpbmRvdy5zZWxmKSAKICAgICAgICB7ICAKICAgICAgICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vcGFjaXR5ICAgID0gIjAuMCI7CiAgICAgICAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUuYmFja2dyb3VuZCA9ICIjRkZGRkZGIjsKICAgICAgICB9CiAgICAgICAgZWxzZQogICAgICAgIHsKICAgICAgICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vcGFjaXR5ICAgID0gIjEuMCI7CiAgICAgICAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUuYmFja2dyb3VuZCA9ICIjOGMxOTE5IjsKICAgICAgICB9IAogICAgfQo8L3NjcmlwdD4KPC9oZWFkPgo=',
		'PGJvZHkgb25Mb2FkPSdjaGVja0lmVG9wTW9zdFdpbmRvdygpJz4KPGRpdiBpZD0id3JhcHBlciI+CjxoMSBjbGFzcz1iYWQ+PC9oMT4KJURZTkFNSUNfQ09OVEVOVCUgICAKPC9kaXY+Cgo='),
		(2,'White error page','Ym9keSB7CiAgICAgICAgY29sb3I6ICAgICAgICAgICAgIzAwMDAwMDsgCiAgICAgICAgYmFja2dyb3VuZC1jb2xvcjogI0ZGRkZGRjsgCiAgICAgICAgZm9udC1mYW1pbHk6ICAgICAgQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiAgICAgIGxpZ2h0ZXI7CiAgICAgICAgZm9udC1zaXplOiAgICAgICAgMTRwdDsgCiAgICAgICAgCiAgICAgICAgb3BhY2l0eTogICAgICAgICAgICAwLjA7CiAgICAgICAgdHJhbnNpdGlvbjogICAgICAgICBvcGFjaXR5IDJzOwogICAgICAgIC13ZWJraXQtdHJhbnNpdGlvbjogb3BhY2l0eSAyczsKICAgICAgICAtbW96LXRyYW5zaXRpb246ICAgIG9wYWNpdHkgMnM7CiAgICAgICAgLW8tdHJhbnNpdGlvbjogICAgICBvcGFjaXR5IDJzOwogICAgICAgIC1tcy10cmFuc2l0aW9uOiAgICAgb3BhY2l0eSAyczsgICAgCiAgICB9CiAgICBoMSB7CiAgICAgICAgZm9udC1zaXplOiA3MnB0OyAKICAgICAgICBtYXJnaW4tYm90dG9tOiAwOyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7CiAgICAgICAgbWFyZ2luLXRvcDogMCA7CiAgICB9ICAgIAouYmFkeyBmb250LXNpemU6IDExMHB4OyBmbG9hdDpsZWZ0OyBtYXJnaW4tcmlnaHQ6MzBweDsgfQouYmFkOmJlZm9yZXsgY29udGVudDogIlwyNjM5Ijt9CiAgICBoMiB7CiAgICAgICAgZm9udC1zaXplOiAyMnB0OyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiBsaWdodGVyOwogICAgfSAgIAogICAgaDMgewogICAgICAgIGZvbnQtc2l6ZTogMThwdDsgCiAgICAgICAgZm9udC1mYW1pbHk6IENhbGlicmksIENhbmRhcmEsIFNlZ29lLCAiU2Vnb2UgVUkiLCBPcHRpbWEsIEFyaWFsLCBzYW5zLXNlcmlmOyAKICAgICAgICBmb250LXdlaWdodDogbGlnaHRlcjsKICAgICAgICBtYXJnaW4tYm90dG9tOiAwIDsKICAgIH0gICAKICAgICN3cmFwcGVyIHsKICAgICAgICB3aWR0aDogODAlIDsKICAgICAgICBtYXJnaW4tbGVmdDogYXV0byA7CiAgICAgICAgbWFyZ2luLXJpZ2h0OiBhdXRvIDsKICAgIH0gICAgCiAgICAjaW5mbyB7CiAgICAgICAgd2lkdGg6IDYwMHB4IDsKICAgICAgICBtYXJnaW4tbGVmdDogYXV0byA7CiAgICAgICAgbWFyZ2luLXJpZ2h0OiBhdXRvIDsKICAgIH0gICAgCi5pbXBvcnRhbnR7CiAgICAgICAgZm9udC1zaXplOiAxOHB0OyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiBsaWdodGVyOwogICAgICAgIG1hcmdpbi1ib3R0b206IDAgOwogICAgfSAgICAKcCB7CiAgICAgICAgZm9udC1zaXplOiAxMnB0OyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiBsaWdodGVyOwogICAgICAgIG1hcmdpbi1ib3R0b206IDAgOwogICAgfSAgICAKICAgIHRkLmluZm9fdGl0bGUgeyAgICAKICAgICAgICB0ZXh0LWFsaWduOiByaWdodDsKICAgICAgICBmb250LXNpemU6ICAxMnB0OyAgCiAgICAgICAgbWluLXdpZHRoOiAxMDBweDsKICAgIH0KICAgIHRkLmluZm9fY29udGVudCB7CiAgICAgICAgdGV4dC1hbGlnbjogbGVmdDsKICAgICAgICBwYWRkaW5nLWxlZnQ6IDEwcHQgOwogICAgICAgIGZvbnQtc2l6ZTogIDEycHQ7ICAKICAgIH0KICAgIC5icmVhay13b3JkIHsKICAgICAgICB3aWR0aDogNTAwcHg7CiAgICAgICAgd29yZC13cmFwOiBicmVhay13b3JkOwogICAgfSAgICAKICAgIGEgewogICAgICAgIHRleHQtZGVjb3JhdGlvbjogdW5kZXJsaW5lOwogICAgICAgIGNvbG9yOiAjMDAwMDAwOyAKICAgICAgICBmb250LWZhbWlseTogQ2FsaWJyaSwgQ2FuZGFyYSwgU2Vnb2UsICJTZWdvZSBVSSIsIE9wdGltYSwgQXJpYWwsIHNhbnMtc2VyaWY7IAogICAgICAgIGZvbnQtd2VpZ2h0OiBsaWdodGVyOwogICAgfQogICAgYTp2aXNpdGVkewogICAgICAgIHRleHQtZGVjb3JhdGlvbjogdW5kZXJsaW5lOwogICAgICAgIGNvbG9yOiAjMDAwMDAwOyAKICAgIH0K',
		'PCFET0NUWVBFIEhUTUw+CjxodG1sPgo8aGVhZD4KPHRpdGxlPiVUSVRMRV9IRUFEJTwvdGl0bGU+CiVKUVVFUlklCiVDU1MlCjxzY3JpcHQgdHlwZT0idGV4dC9qYXZhc2NyaXB0Ij4KICAgIGZ1bmN0aW9uIGJsdXIoKXsgfQogICAgZnVuY3Rpb24gY2hlY2tJZlRvcE1vc3RXaW5kb3coKQogICAgewogICAgICAgIGlmICh3aW5kb3cudG9wICE9IHdpbmRvdy5zZWxmKSAKICAgICAgICB7ICAKICAgICAgICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vcGFjaXR5ICAgID0gIjAuMCI7CiAgICAgICAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUuYmFja2dyb3VuZCA9ICIjOGMxOTE5IjsKICAgICAgICB9CiAgICAgICAgZWxzZQogICAgICAgIHsKICAgICAgICAgICAgZG9jdW1lbnQuYm9keS5zdHlsZS5vcGFjaXR5ICAgID0gIjEuMCI7CiAgICAgICAgICAgIGRvY3VtZW50LmJvZHkuc3R5bGUuYmFja2dyb3VuZCA9ICIjRkZGRkZGIjsKICAgICAgICB9IAogICAgfQo8L3NjcmlwdD4KPC9oZWFkPgo=',
		'PGJvZHkgb25Mb2FkPSdjaGVja0lmVG9wTW9zdFdpbmRvdygpJz4KPGRpdiBpZD0id3JhcHBlciI+JURZTkFNSUNfQ09OVEVOVCU8L2Rpdj4KCg=='),
		(3,'Microsoft style template','Ym9keSB7CiAgICBiYWNrZ3JvdW5kLXJlcGVhdDogcmVwZWF0LXg7CiAgICBiYWNrZ3JvdW5kLWNvbG9yOiB3aGl0ZTsKICAgIGZvbnQtZmFtaWx5OiAiU2Vnb2UgVUkiLCAidmVyZGFuYSIsICJhcmlhbCI7CiAgICBtYXJnaW46IDBlbTsKICAgIGNvbG9yOiAjNTc1NzU3Owp9CkgxLEgyLEhSewoJCQkJCWRpc3BsYXk6bm9uZTsKCQkJCQkKCQkJCQl9CgoubWFpbkNvbnRlbnQgewogICAgbWFyZ2luLXRvcDogODBweDsKICAgIHdpZHRoOiA3MDBweDsKICAgIG1hcmdpbi1sZWZ0OiAxMjBweDsKICAgIG1hcmdpbi1yaWdodDogMTIwcHg7Cn0KCgoKLnRpdGxlIHsKICAgIGNvbG9yOiAjMjc3OGVjOwogICAgZm9udC1zaXplOiAzOHB0OwogICAgZm9udC13ZWlnaHQ6IDMwMDsKICAgIHZlcnRpY2FsLWFsaWduOiBib3R0b207CiAgICBtYXJnaW4tYm90dG9tOiAyMHB4OwogICAgZm9udC1mYW1pbHk6ICJTZWdvZSBVSSIsICJ2ZXJkYW5hIjsKICAgIHBvc2l0aW9uOiByZWxhdGl2ZTsKfQoKLnRhc2tTZWN0aW9uIHsKICAgIG1hcmdpbi10b3A6IDIwcHg7CiAgICBtYXJnaW4tYm90dG9tOiA0MHB4OwogICAgcG9zaXRpb246IHJlbGF0aXZlOwp9CgoudGFza3MgewogICAgY29sb3I6ICMwMDAwMDA7CiAgICBmb250LWZhbWlseTogIlNlZ29lIFVJIiwgInZlcmRhbmEiOwogICAgZm9udC13ZWlnaHQ6IDIwMDsKICAgIGZvbnQtc2l6ZTogMTJwdDsKICAgIHBhZGRpbmctdG9wOiA1cHg7Cn0KYmxvY2txdW90ZSB7CiAgICBjb2xvcjogIzAwMDAwMDsKICAgIGZvbnQtZmFtaWx5OiAiU2Vnb2UgVUkiLCAidmVyZGFuYSI7CiAgICBmb250LXdlaWdodDogMjAwOwogICAgZm9udC1zaXplOiAxMnB0OwogICAgbWFyZ2luLXRvcDogLTEwcHg7Cn0KClAscHJlIHsKICAgIGNvbG9yOiAjMDAwMDAwOwogICAgZm9udC1mYW1pbHk6ICJTZWdvZSBVSSIsICJ2ZXJkYW5hIjsKICAgIGZvbnQtd2VpZ2h0OiAyMDA7CiAgICBmb250LXNpemU6IDEycHQ7CiAgICBwYWRkaW5nLXRvcDogNXB4Owp9CiNmb290ZXIgcCB7CiAgICBjb2xvcjogI0NDQ0NDQzsKICAgIGZvbnQtZmFtaWx5OiAiU2Vnb2UgVUkiLCAidmVyZGFuYSI7CiAgICBmb250LXdlaWdodDogbm9ybWFsOwogICAgZm9udC1zaXplOiAxMHB0OwogICAgcGFkZGluZy10b3A6IDVweDsKfQpsaSB7CiAgICBtYXJnaW4tdG9wOiA4cHg7Cn0KCi5kaWFnbm9zZUJ1dHRvbiB7CiAgICBvdXRsaW5lOiBub25lOwogICAgZm9udC1zaXplOiA5cHQ7Cn0K',
		'PCFET0NUWVBFIEhUTUw+CjxIVE1MPgo8SEVBRD4KPE1FVEEgY29udGVudD0iSUU9MTEuMDAwMCIgaHR0cC1lcXVpdj0iWC1VQS1Db21wYXRpYmxlIj4KPE1FVEEgaHR0cC1lcXVpdj0iQ29udGVudC1UeXBlIiBjb250ZW50PSJ0ZXh0L2h0bWw7IGNoYXJzZXQ9VVRGLTgiPiAgICAgICAgIAo8dGl0bGU+JVRJVExFX0hFQUQlPC90aXRsZT4KJUpRVUVSWSUKJUNTUyUKPFNDUklQVCBsYW5ndWFnZT0iamF2YXNjcmlwdCIgdHlwZT0idGV4dC9qYXZhc2NyaXB0Ij4KCmZ1bmN0aW9uIGlzRXh0ZXJuYWxVcmxTYWZlRm9yTmF2aWdhdGlvbih1cmxTdHIpCnsKdmFyIHJlZ0V4ID0gbmV3IFJlZ0V4cCgiXihodHRwKHM/KXxmdHB8ZmlsZSk6Ly8iLCAiaSIpOwpyZXR1cm4gcmVnRXguZXhlYyh1cmxTdHIpOwp9CmZ1bmN0aW9uIGNsaWNrUmVmcmVzaCgpCnsKdmFyIGxvY2F0aW9uID0gd2luZG93LmxvY2F0aW9uLmhyZWY7CnZhciBwb3VuZEluZGV4ID0gbG9jYXRpb24uaW5kZXhPZignIycpOwppZiAocG91bmRJbmRleCAhPSAtMSAmJiBwb3VuZEluZGV4KzEgPCBsb2NhdGlvbi5sZW5ndGggJiYgaXNFeHRlcm5hbFVybFNhZmVGb3JOYXZpZ2F0aW9uKGxvY2F0aW9uLnN1YnN0cmluZyhwb3VuZEluZGV4KzEpKSkKewp3aW5kb3cubG9jYXRpb24ucmVwbGFjZShsb2NhdGlvbi5zdWJzdHJpbmcocG91bmRJbmRleCsxKSk7Cn0KfQpmdW5jdGlvbiBuYXZDYW5jZWxJbml0KCkKewp2YXIgbG9jYXRpb24gPSB3aW5kb3cubG9jYXRpb24uaHJlZjsKdmFyIHBvdW5kSW5kZXggPSBsb2NhdGlvbi5pbmRleE9mKCcjJyk7CmlmIChwb3VuZEluZGV4ICE9IC0xICYmIHBvdW5kSW5kZXgrMSA8IGxvY2F0aW9uLmxlbmd0aCAmJiBpc0V4dGVybmFsVXJsU2FmZUZvck5hdmlnYXRpb24obG9jYXRpb24uc3Vic3RyaW5nKHBvdW5kSW5kZXgrMSkpKQp7CnZhciBiRWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoIkEiKTsKYkVsZW1lbnQuaW5uZXJUZXh0ID0gTF9SRUZSRVNIX1RFWFQ7CmJFbGVtZW50LmhyZWYgPSAnamF2YXNjcmlwdDpjbGlja1JlZnJlc2goKSc7Cm5hdkNhbmNlbENvbnRhaW5lci5hcHBlbmRDaGlsZChiRWxlbWVudCk7Cn0KZWxzZQp7CnZhciB0ZXh0Tm9kZSA9IGRvY3VtZW50LmNyZWF0ZVRleHROb2RlKExfUkVMT0FEX1RFWFQpOwpuYXZDYW5jZWxDb250YWluZXIuYXBwZW5kQ2hpbGQodGV4dE5vZGUpOwp9Cn0KZnVuY3Rpb24gZXhwYW5kQ29sbGFwc2UoZWxlbSwgY2hhbmdlSW1hZ2UpCnsKaWYgKGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKQp7CmVjQmxvY2sgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZChlbGVtKTsKaWYgKGVjQmxvY2sgIT0gdW5kZWZpbmVkICYmIGVjQmxvY2sgIT0gbnVsbCkKewppZiAoY2hhbmdlSW1hZ2UpCnsKZWxlbUltYWdlID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoZWxlbSArICJJbWFnZSIpOwp9CmlmICghY2hhbmdlSW1hZ2UgfHwgKGVsZW1JbWFnZSAhPSB1bmRlZmluZWQgJiYgZWxlbUltYWdlICE9IG51bGwpKQp7CmlmIChlY0Jsb2NrLmN1cnJlbnRTdHlsZS5kaXNwbGF5ID09ICJub25lIiB8fCBlY0Jsb2NrLmN1cnJlbnRTdHlsZS5kaXNwbGF5ID09IG51bGwgfHwgZWNCbG9jay5jdXJyZW50U3R5bGUuZGlzcGxheSA9PSAiIikKewplY0Jsb2NrLnN0eWxlLmRpc3BsYXkgPSAiYmxvY2siOwppZiAoY2hhbmdlSW1hZ2UpCnsKZWxlbUltYWdlLnNyYyA9ICJ1cC5wbmciOwp9Cn0KZWxzZSBpZiAoZWNCbG9jay5jdXJyZW50U3R5bGUuZGlzcGxheSA9PSAiYmxvY2siKQp7CmVjQmxvY2suc3R5bGUuZGlzcGxheSA9ICJub25lIjsKaWYgKGNoYW5nZUltYWdlKQp7CmVsZW1JbWFnZS5zcmMgPSAiZG93bi5wbmciOwp9Cn0KZWxzZQp7CmVjQmxvY2suc3R5bGUuZGlzcGxheSA9ICJibG9jayI7CmlmIChjaGFuZ2VJbWFnZSkKewplbGVtSW1hZ2Uuc3JjID0gInVwLnBuZyI7Cn0KfQp9Cn0KfQp9CmZ1bmN0aW9uIGluaXRIb21lcGFnZSgpCnsKRG9jVVJMPWRvY3VtZW50LmxvY2F0aW9uLmhyZWY7CnZhciBwb3VuZEluZGV4ID0gRG9jVVJMLmluZGV4T2YoJyMnKTsKaWYgKHBvdW5kSW5kZXggIT0gLTEgJiYgcG91bmRJbmRleCsxIDwgbG9jYXRpb24ubGVuZ3RoICYmIGlzRXh0ZXJuYWxVcmxTYWZlRm9yTmF2aWdhdGlvbihsb2NhdGlvbi5zdWJzdHJpbmcocG91bmRJbmRleCsxKSkpCnsKcHJvdG9jb2xJbmRleD1Eb2NVUkwuaW5kZXhPZigiOi8vIiwgNCk7CnNlcnZlckluZGV4PURvY1VSTC5pbmRleE9mKCIvIiwgcHJvdG9jb2xJbmRleCArIDMpOwpCZWdpblVSTD1Eb2NVUkwuaW5kZXhPZigiIyIsMSkgKyAxOwp1cmxyZXN1bHQ9RG9jVVJMLnN1YnN0cmluZyhCZWdpblVSTCwgc2VydmVySW5kZXgpOwppZiAocHJvdG9jb2xJbmRleCAtIEJlZ2luVVJMID4gNykKdXJscmVzdWx0PSIiOwpkaXNwbGF5cmVzdWx0PURvY1VSTC5zdWJzdHJpbmcocHJvdG9jb2xJbmRleCArIDMsIHNlcnZlckluZGV4KTsKfQplbHNlCnsKZGlzcGxheXJlc3VsdCA9ICIiOwp1cmxyZXN1bHQgPSAiIjsKfQp2YXIgYUVsZW1lbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCJBIik7CmFFbGVtZW50LmlubmVyVGV4dCA9IGRpc3BsYXlyZXN1bHQ7CmFFbGVtZW50LmhyZWYgPSB1cmxyZXN1bHQ7CmhvbWVwYWdlQ29udGFpbmVyLmFwcGVuZENoaWxkKGFFbGVtZW50KTsKfQpmdW5jdGlvbiBpbml0Q29ubmVjdGlvblN0YXR1cygpCnsKaWYgKG5hdmlnYXRvci5vbkxpbmUpCnsKY2hlY2tDb25uZWN0aW9uLmlubmVyVGV4dCA9IExfQ09OTkVDVElPTl9PTl9URVhUOwp9CmVsc2UKewpjaGVja0Nvbm5lY3Rpb24uaW5uZXJUZXh0ID0gTF9DT05ORUNUSU9OX09GRl9URVhUOwp9Cn0KZnVuY3Rpb24gaW5pdEdvQmFjaygpCnsKaWYgKGhpc3RvcnkubGVuZ3RoIDwgMSkKewp2YXIgdGV4dE5vZGUgPSBkb2N1bWVudC5jcmVhdGVUZXh0Tm9kZShMX0dPQkFDS19URVhUKTsKZ29CYWNrQ29udGFpbmVyLmFwcGVuZENoaWxkKHRleHROb2RlKTsKfQplbHNlCnsKdmFyIGJFbGVtZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgiQSIpOwpiRWxlbWVudC5pbm5lclRleHQgPSBMX0dPQkFDS19URVhUIDsKYkVsZW1lbnQuaHJlZiA9ICJqYXZhc2NyaXB0Omhpc3RvcnkuYmFjaygpOyI7CmdvQmFja0NvbnRhaW5lci5hcHBlbmRDaGlsZChiRWxlbWVudCk7Cn0KfQpmdW5jdGlvbiBpbml0TW9yZUluZm8oaW5mb0Jsb2NrSUQpCnsKdmFyIGJFbGVtZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgiQSIpOwpiRWxlbWVudC5pbm5lclRleHQgPSBMX01PUkVJTkZPX1RFWFQ7CmJFbGVtZW50LmhyZWYgPSAiamF2YXNjcmlwdDpleHBhbmRDb2xsYXBzZShcJ2luZm9CbG9ja0lEXCcsIHRydWUpOyI7Cm1vcmVJbmZvQ29udGFpbmVyLmFwcGVuZENoaWxkKGJFbGVtZW50KTsKfQpmdW5jdGlvbiBpbml0T2ZmbGluZVVzZXIob2ZmbGluZVVzZXJJRCkKewp2YXIgYkVsZW1lbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCJBIik7CmJFbGVtZW50LmlubmVyVGV4dCA9IExfT0ZGTElORV9VU0VSU19URVhUOwpiRWxlbWVudC5ocmVmID0gImphdmFzY3JpcHQ6ZXhwYW5kQ29sbGFwc2UoJ29mZmxpbmVVc2VySUQnLCB0cnVlKTsiOwpvZmZsaW5lVXNlckNvbnRhaW5lci5hcHBlbmRDaGlsZChiRWxlbWVudCk7Cn0KZnVuY3Rpb24gaW5pdFVuZnJhbWVDb250ZW50KCkKewp2YXIgbG9jYXRpb24gPSB3aW5kb3cubG9jYXRpb24uaHJlZjsKdmFyIHBvdW5kSW5kZXggPSBsb2NhdGlvbi5pbmRleE9mKCcjJyk7CmlmIChwb3VuZEluZGV4ICE9IC0xICYmIHBvdW5kSW5kZXgrMSA8IGxvY2F0aW9uLmxlbmd0aCAmJiBpc0V4dGVybmFsVXJsU2FmZUZvck5hdmlnYXRpb24obG9jYXRpb24uc3Vic3RyaW5nKHBvdW5kSW5kZXgrMSkpKQp7CmRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCJ3aGF0VG9Eb0ludHJvIikuc3R5bGUuZGlzcGxheT0iIjsKZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoIndoYXRUb0RvQm9keSIpLnN0eWxlLmRpc3BsYXk9IiI7Cn0KfQpmdW5jdGlvbiByZW1vdmVOb1NjcmlwdEVsZW1lbnRzKCkgewp2YXIgbm9TY3JpcHRFbGVtZW50cyA9IGRvY3VtZW50LmdldEVsZW1lbnRzQnlUYWdOYW1lKCJub3NjcmlwdCIpOwpmb3IgKHZhciBpID0gbm9TY3JpcHRFbGVtZW50cy5sZW5ndGggLSAxOyBpID49IDA7IGktLSkKewp2YXIgYkVsZW1lbnQgPSBub1NjcmlwdEVsZW1lbnRzW2ldOwppZiAoYkVsZW1lbnQgIT09IHVuZGVmaW5lZCAmJiBiRWxlbWVudCAhPT0gbnVsbCkKewpiRWxlbWVudC5yZW1vdmVOb2RlKHRydWUpOwp9Cn0KfQpmdW5jdGlvbiBtYWtlTmV3V2luZG93KCkKewp2YXIgbG9jYXRpb24gPSB3aW5kb3cubG9jYXRpb24uaHJlZjsKdmFyIHBvdW5kSW5kZXggPSBsb2NhdGlvbi5pbmRleE9mKCcjJyk7CmlmIChwb3VuZEluZGV4ICE9IC0xICYmIHBvdW5kSW5kZXgrMSA8IGxvY2F0aW9uLmxlbmd0aCAmJiBpc0V4dGVybmFsVXJsU2FmZUZvck5hdmlnYXRpb24obG9jYXRpb24uc3Vic3RyaW5nKHBvdW5kSW5kZXgrMSkpKQp7CndpbmRvdy5vcGVuKGxvY2F0aW9uLnN1YnN0cmluZyhwb3VuZEluZGV4KzEpKTsKfQp9CmZ1bmN0aW9uIHNldFRhYkluZm8odGFiSW5mb0Jsb2NrSUQpCnsKdmFyIGJQcmV2RWxlbWVudCA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCJ0YWJJbmZvVGV4dElEIik7CnZhciBiUHJldkltYWdlID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoInRhYkluZm9CbG9ja0lESW1hZ2UiKTsKaWYgKGJQcmV2RWxlbWVudCAhPSBudWxsKQp7CnRhYkluZm9Db250YWluZXIucmVtb3ZlQ2hpbGQoYlByZXZFbGVtZW50KTsKfQppZiAoYlByZXZJbWFnZSAhPSBudWxsKQp7CnRhYkltYWdlQ29udGFpbmVyLnJlbW92ZUNoaWxkKGJQcmV2SW1hZ2UpOwp9CnZhciBiRWxlbWVudCA9IGRvY3VtZW50LmNyZWF0ZUVsZW1lbnQoIkEiKTsKdmFyIGJJbWFnZUVsZW1lbnQgPSBkb2N1bWVudC5jcmVhdGVFbGVtZW50KCJJTUciKTsKdmFyIGVjQmxvY2sgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCh0YWJJbmZvQmxvY2tJRCk7CmlmICgoZWNCbG9jayAhPSB1bmRlZmluZWQgJiYgZWNCbG9jayAhPSBudWxsKSAmJgooZWNCbG9jay5jdXJyZW50U3R5bGUuZGlzcGxheSA9PSAibm9uZSIgfHwgZWNCbG9jay5jdXJyZW50U3R5bGUuZGlzcGxheSA9PSBudWxsIHx8IGVjQmxvY2suY3VycmVudFN0eWxlLmRpc3BsYXkgPT0gIiIpKQp7CmJFbGVtZW50LmlubmVyVGV4dCA9IExfU0hPV19IT1RLRVlTX1RFWFQ7CmJJbWFnZUVsZW1lbnQuYWx0ID0gTF9TSE9XX0hPVEtFWVNfVEVYVDsKYkltYWdlRWxlbWVudC5zcmM9ImRvd24ucG5nIjsKfQplbHNlCnsKYkVsZW1lbnQuaW5uZXJUZXh0ID0gTF9ISURFX0hPVEtFWVNfVEVYVDsKYkltYWdlRWxlbWVudC5hbHQgPSBMX0hJREVfSE9US0VZU19URVhUOwpiSW1hZ2VFbGVtZW50LnNyYz0idXAucG5nIjsKfQpiRWxlbWVudC5pZCA9ICJ0YWJJbmZvVGV4dElEIjsKYkVsZW1lbnQuaHJlZiA9ICJqYXZhc2NyaXB0OmV4cGFuZENvbGxhcHNlKFwndGFiSW5mb0Jsb2NrSURcJywgZmFsc2UpOyBzZXRUYWJJbmZvKCd0YWJJbmZvQmxvY2tJRCcpOyI7CmJJbWFnZUVsZW1lbnQuaWQ9InRhYkluZm9CbG9ja0lESW1hZ2UiOwpiSW1hZ2VFbGVtZW50LmJvcmRlcj0iMCI7CmJJbWFnZUVsZW1lbnQuY2xhc3NOYW1lPSJhY3Rpb25JY29uIjsKdGFiSW5mb0NvbnRhaW5lci5hcHBlbmRDaGlsZChiRWxlbWVudCk7CnRhYkltYWdlQ29udGFpbmVyLmFwcGVuZENoaWxkKGJJbWFnZUVsZW1lbnQpOwp9CmZ1bmN0aW9uIGxhdW5jaEludGVybmV0T3B0aW9ucygpCnsKd2luZG93LmV4dGVybmFsLm1zTGF1bmNoSW50ZXJuZXRPcHRpb25zKCk7Cn0KZnVuY3Rpb24gZGlhZ25vc2VDb25uZWN0aW9uKCkKewp3aW5kb3cuZXh0ZXJuYWwuRGlhZ25vc2VDb25uZWN0aW9uKCk7Cn0KZnVuY3Rpb24gZGlhZ25vc2VDb25uZWN0aW9uQW5kUmVmcmVzaCgpCnsKd2luZG93LmV4dGVybmFsLkRpYWdub3NlQ29ubmVjdGlvbigpOwppZiAobmF2aWdhdG9yLm9uTGluZSkKewpjbGlja1JlZnJlc2goKTsKfQp9CmZ1bmN0aW9uIGdldEluZm8oKQp7CmNoZWNrQ29ubmVjdGlvbigpOwppZiAoZG9jdW1lbnQuYWRkRXZlbnRMaXN0ZW5lcikKewphZGRFdmVudExpc3RlbmVyKCJvZmZsaW5lIiwgcmVwb3J0Q29ubmVjdGlvbkV2ZW50LCBmYWxzZSk7Cn0KZWxzZQp7CmF0dGFjaEV2ZW50KCJvbm9mZmxpbmUiLCByZXBvcnRDb25uZWN0aW9uRXZlbnQpOwp9CmRvY3VtZW50LmJvZHkub25vbmxpbmUgPSByZXBvcnRDb25uZWN0aW9uRXZlbnQ7CmRvY3VtZW50LmJvZHkub25vZmZsaW5lID0gcmVwb3J0Q29ubmVjdGlvbkV2ZW50Owp9CmZ1bmN0aW9uIGNoZWNrQ29ubmVjdGlvbigpCnsKdmFyIG5ld0hlYWRpbmcgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgibWFpblRpdGxlIik7CnZhciBub3RDb25uZWN0ZWRUYXNrcyA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCJub3RDb25uZWN0ZWRUYXNrcyIpOwp2YXIgY2FudERpc3BsYXlUYXNrcyA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCJjYW50RGlzcGxheVRhc2tzIik7Cn0KZnVuY3Rpb24gcmVwb3J0Q29ubmVjdGlvbkV2ZW50KGUpCnsKaWYgKCFlKSBlID0gd2luZG93LmV2ZW50OwppZiAoJ29ubGluZScgPT0gZS50eXBlKQp7CnNldFRpbWVvdXQgKCAiY2xpY2tSZWZyZXNoKCkiLCAxMDAwICk7Cn0KZWxzZSBpZiAoJ29mZmxpbmUnID09IGUudHlwZSkKewpjaGVja0Nvbm5lY3Rpb24oKTsKfQplbHNlCnsKY2hlY2tDb25uZWN0aW9uKCk7Cn0KfQpmdW5jdGlvbiBhZGRVUkwoKQp7CnZhciB1cmxSZXN1bHQgPSAiIjsKdmFyIERvY1VSTCA9IGRvY3VtZW50LmxvY2F0aW9uLmhyZWY7CnZhciB1cmxQbGFjZWhvbGRlciA9IGRvY3VtZW50LmdldEVsZW1lbnRCeUlkKCJ3ZWJwYWdlIik7CnZhciBiZWdpbkluZGV4ID0gRG9jVVJMLmluZGV4T2YoJyMnKSArIDE7CmlmIChEb2NVUkwuaW5kZXhPZigiZmlsZTovLyIsIGJlZ2luSW5kZXgpID09IC0xKQp7CnZhciBwcm90b2NvbEVuZEluZGV4ID0gRG9jVVJMLmluZGV4T2YoIjovLyIsIGJlZ2luSW5kZXgpOwp2YXIgZW5kSW5kZXg9RG9jVVJMLmluZGV4T2YoIi8iLCBwcm90b2NvbEVuZEluZGV4ICsgMyk7CnVybFJlc3VsdCA9IERvY1VSTC5zdWJzdHJpbmcoYmVnaW5JbmRleCwgZW5kSW5kZXgpOwp9CnVybFBsYWNlaG9sZGVyLmlubmVyVGV4dCA9IHVybFJlc3VsdCArICIgIjsKfQpmdW5jdGlvbiBhZGREb21haW5OYW1lKCkKewp2YXIgZG9tYWluTmFtZVBsYWNlaG9sZGVyID0gZG9jdW1lbnQuZ2V0RWxlbWVudEJ5SWQoIkRvbWFpbk5hbWUiKTsKZG9tYWluTmFtZVBsYWNlaG9sZGVyLmlubmVyVGV4dCA9IGZpbmRWYWx1ZSgiRG9tYWluTmFtZT0iKSArICIgIjsKfQpmdW5jdGlvbiBhZGRQcm94eURldGFpbCgpCnsKdmFyIHByb3h5RGV0YWlsUGxhY2Vob2xkZXIgPSBkb2N1bWVudC5nZXRFbGVtZW50QnlJZCgiUHJveHlEZXRhaWwiKTsKcHJveHlEZXRhaWxQbGFjZWhvbGRlci5pbm5lclRleHQgPSBmaW5kVmFsdWUoIlByb3h5RGV0YWlsPSIpOwp9CmZ1bmN0aW9uIGZpbmRWYWx1ZShrZXkpCnsKdmFyIHZhbHVlID0gJyc7CkRvY1F1ZXJ5ID0gZG9jdW1lbnQubG9jYXRpb24uc2VhcmNoOwpCZWdpblN0cmluZyA9IERvY1F1ZXJ5LmluZGV4T2Yoa2V5KTsKaWYgKEJlZ2luU3RyaW5nID4gMCkKewpCZWdpblN0cmluZyArPSBrZXkubGVuZ3RoOwpFbmRTdHJpbmcgPSBNYXRoLm1heCgwLCBNYXRoLm1pbihEb2NRdWVyeS5pbmRleE9mKCImIiwgQmVnaW5TdHJpbmcpLCBEb2NRdWVyeS5pbmRleE9mKCIjIiwgQmVnaW5TdHJpbmcpKSk7CmlmIChFbmRTdHJpbmcgPiAwKQp7CnZhbHVlID0gRG9jUXVlcnkuc3Vic3RyaW5nKEJlZ2luU3RyaW5nLCBFbmRTdHJpbmcpOwp9CmVsc2UKewp2YWx1ZSA9IERvY1F1ZXJ5LnN1YnN0cmluZyhCZWdpblN0cmluZyk7Cn0KfQpyZXR1cm4gdmFsdWU7Cn0KZnVuY3Rpb24gaXNIVFRQUyhjYW50RGlzcGxheVRhc2tzKQp7CnZhciBEb2NVUkwgPSBkb2N1bWVudC5sb2NhdGlvbi5ocmVmOwp2YXIgcG91bmRJbmRleCA9IERvY1VSTC5pbmRleE9mKCcjJyk7CnZhciBwcm90b2NvbEluZGV4ID0gRG9jVVJMLmluZGV4T2YoImh0dHBzOi8vIiwgcG91bmRJbmRleCk7CmlmIChwcm90b2NvbEluZGV4PnBvdW5kSW5kZXgpCnsKdmFyIGJFbGVtZW50ID0gZG9jdW1lbnQuY3JlYXRlRWxlbWVudCgibGkiKTsKYkVsZW1lbnQudGV4dENvbnRlbnQgPSBMX1RMU19TU0xfVEVYVDsKY2FudERpc3BsYXlUYXNrcy5hcHBlbmRDaGlsZChiRWxlbWVudCk7Cn0KfQoKPC9TQ1JJUFQ+CiAgICAgCjxNRVRBIG5hbWU9IkdFTkVSQVRPUiIgY29udGVudD0iTVNIVE1MIDExLjAwLjk2MDAuMTc4MDEiPgo8L2hlYWQ+Cg==',
		'PEJPRFkgb25sb2FkPSJqYXZhc2NyaXB0OmdldEluZm8oKTsiPgo8RElWIGNsYXNzPSJtYWluQ29udGVudCIgaWQ9ImNvbnRlbnRDb250YWluZXIiPgoJPERJViBjbGFzcz0idGl0bGUiIGlkPSJtYWluVGl0bGUiPiVUSVRMRV9IRUFEJTwvRElWPgoJPERJViBjbGFzcz0idGFza1NlY3Rpb24iIGlkPSJ0YXNrU2VjdGlvbiI+JURZTkFNSUNfQ09OVEVOVCU8L0RJVj4KPC9ESVY+CgoK'),
		(4,'HotSpot Artica default','Ym9keSB7ICAgIGJhY2tncm91bmQtcmVwZWF0OiByZXBlYXQteDsgICAgYmFja2dyb3VuZC1jb2xvcjogd2hpdGU7ICAgIGZvbnQtZmFtaWx5OiAiU2Vnb2UgVUkiLCAidmVyZGFuYSIsICJhcmlhbCI7ICAgIG1hcmdpbjogMGVtOyAgICBjb2xvcjogI0ZGRkZGRjsgICAgb3BhY2l0eTogICAgICAgICAgICAwLjA7ICAgIHRyYW5zaXRpb246ICAgICAgICAgb3BhY2l0eSAyczsgICAgLXdlYmtpdC10cmFuc2l0aW9uOiBvcGFjaXR5IDJzOyAgICAtbW96LXRyYW5zaXRpb246ICAgIG9wYWNpdHkgMnM7ICAgIC1vLXRyYW5zaXRpb246ICAgICAgb3BhY2l0eSAyczsgICAgLW1zLXRyYW5zaXRpb246ICAgICBvcGFjaXR5IDJzO31IMSxIMixIUnsgZGlzcGxheTpub25lOyB9YSxhOnZpc2l0ZWQsYTphY3RpdmUgeyAgICBjb2xvcjogIzAwNTQ0NzsgICAgZm9udC1mYW1pbHk6ICJTZWdvZSBVSSIsICJ2ZXJkYW5hIjsJICB0ZXh0LWRlY29yYXRpb246dW5kZXJsaW5lO30ubWFpbmJvZHl7CWJhY2tncm91bmQtaW1hZ2U6dXJsKCIvaW1hZ2VzP3BpY3R1cmU9d2lmaWRvZy01MDAucG5nIik7ICBiYWNrZ3JvdW5kLXJlcGVhdDogbm8tcmVwZWF0OyAgYmFja2dyb3VuZC1wb3NpdGlvbjogMCUgMCU7ICBtaW4taGVpZ2h0OjU1MHB4OyAgcGFkZGluZy10b3A6ODBweDt9Lm1haW5Db250ZW50IHsgICAgd2lkdGg6IDcwMHB4OyAgICBtYXJnaW4tbGVmdDogMTIwcHg7ICAgIG1hcmdpbi1yaWdodDogMTIwcHg7fXAudGV4dC1lcnJvcntjb2xvcjojRTQwNTAxICFpbXBvcnRhbnQ7Cm1hcmdpbjo1cHg7CnBhZGRpbmc6M3B4Owpib3JkZXI6MXB4IHNvbGlkICNFNDA1MDE7CmJvcmRlci1yYWRpdXM6NXB4IDVweCA1cHggNXB4OwotbW96LWJvcmRlci1yYWRpdXM6NXB4Owotd2Via2l0LWJvcmRlci1yYWRpdXM6NXB4OwpiYWNrZ3JvdW5kLWNvbG9yOiAjRjdFNUQ5Owpmb250LXdlaWdodDpib2xkOwpmb250LXNpemU6IDE4cHg7Cm1hcmdpbi1ib3R0b206IDIwcHg7CnBhZGRpbmc6IDhweCAzNXB4IDhweCAxNHB4Owp0ZXh0LXNoYWRvdzogMCAxcHggMCByZ2JhKDI1NSwgMjU1LCAyNTUsIDAuNSk7Cn0udGV4dC1lcnJvciBhIHtjb2xvcjojRTQwNTAxICFpbXBvcnRhbnQ7Cm1hcmdpbjo1cHg7CnBhZGRpbmc6M3B4Owpib3JkZXI6MXB4IHNvbGlkICNFNDA1MDE7CmJvcmRlci1yYWRpdXM6NXB4IDVweCA1cHggNXB4OwotbW96LWJvcmRlci1yYWRpdXM6NXB4Owotd2Via2l0LWJvcmRlci1yYWRpdXM6NXB4OwpiYWNrZ3JvdW5kLWNvbG9yOiAjRjdFNUQ5Owpmb250LXdlaWdodDpib2xkOwpmb250LXNpemU6IDE4cHg7Cm1hcmdpbi1ib3R0b206IDIwcHg7CnBhZGRpbmc6IDhweCAzNXB4IDhweCAxNHB4Owp0ZXh0LXNoYWRvdzogMCAxcHggMCByZ2JhKDI1NSwgMjU1LCAyNTUsIDAuNSk7Cjtib3JkZXI6MHB4O21hcmdpbjowcHg7cGFkZGluZzowcHg7Ym9yZGVyLXJhZGl1czowcHg7fS50aXRsZSB7ICAgIGNvbG9yOiAjRkZGRkZGOyAgICBmb250LXNpemU6IDY0cHg7ICAgIGZvbnQtd2VpZ2h0OiAzMDA7ICAgIHZlcnRpY2FsLWFsaWduOiBib3R0b207ICAgIG1hcmdpbi1ib3R0b206IDIwcHg7ICAgIGZvbnQtZmFtaWx5OiAiU2Vnb2UgVUkiLCAidmVyZGFuYSI7ICAgIHBvc2l0aW9uOiByZWxhdGl2ZTt9LnRpdGxlMiB7ICAgIGNvbG9yOiAjRkZGRkZGOyAgICBmb250LXNpemU6IDMycHg7ICAgIGZvbnQtd2VpZ2h0OiAzMDA7ICAgIHZlcnRpY2FsLWFsaWduOiBib3R0b207ICAgIG1hcmdpbi1ib3R0b206IDIwcHg7ICAgIGZvbnQtZmFtaWx5OiAiU2Vnb2UgVUkiLCAidmVyZGFuYSI7ICAgIHBvc2l0aW9uOiByZWxhdGl2ZTt9LnRhc2tTZWN0aW9uIHsgICAgbWFyZ2luLXRvcDogMjBweDsgICAgbWFyZ2luLWJvdHRvbTogNDBweDsgICAgcG9zaXRpb246IHJlbGF0aXZlO30udGFza3MgeyAgICBjb2xvcjogIzAwMDAwMDsgICAgZm9udC1mYW1pbHk6ICJTZWdvZSBVSSIsICJ2ZXJkYW5hIjsgICAgZm9udC13ZWlnaHQ6IDIwMDsgICAgZm9udC1zaXplOiAxNnB4OyAgICBwYWRkaW5nLXRvcDogNXB4O30uUEluc2lkZSB7ICAgIGNvbG9yOiAjMDAwMDAwOyAgICBmb250LWZhbWlseTogIlNlZ29lIFVJIiwgInZlcmRhbmEiOyAgICBmb250LXdlaWdodDogMjAwOyAgICBmb250LXNpemU6IDE2cHg7ICAgIHBhZGRpbmctdG9wOiA1cHg7fWJsb2NrcXVvdGUgeyAgICBjb2xvcjogIzAwMDAwMDsgICAgZm9udC1mYW1pbHk6ICJTZWdvZSBVSSIsICJ2ZXJkYW5hIjsgICAgZm9udC13ZWlnaHQ6IDIwMDsgICAgZm9udC1zaXplOiAxNnB4OyAgICBtYXJnaW4tdG9wOiAtMTBweDt9UCxwcmUgeyAgICBjb2xvcjogI0ZGRkZGRjsgICAgZm9udC1mYW1pbHk6ICJTZWdvZSBVSSIsICJ2ZXJkYW5hIjsgICAgZm9udC13ZWlnaHQ6IDIwMDsgICAgZm9udC1zaXplOiAxNnB4OyAgICBwYWRkaW5nLXRvcDogNXB4O30jZm9vdGVyIHAgeyAgICBjb2xvcjogI0NDQ0NDQzsgICAgZm9udC1mYW1pbHk6ICJTZWdvZSBVSSIsICJ2ZXJkYW5hIjsgICAgZm9udC13ZWlnaHQ6IG5vcm1hbDsgICAgZm9udC1zaXplOiAxMHB0OyAgICBwYWRkaW5nLXRvcDogNXB4O30uc3BhY2VyLWhlaWdodCB7IGhlaWdodDoyMHB4O31pbnB1dFt0eXBlPSJ0ZXh0Il0saW5wdXRbdHlwZT0icGFzc3dvcmQiXSwgaW5wdXRbdHlwZT0iZGF0ZXRpbWUiXSwgaW5wdXRbdHlwZT0iZGF0ZXRpbWUtbG9jYWwiXSwgaW5wdXRbdHlwZT0iZGF0ZSJdLCBpbnB1dFt0eXBlPSJtb250aCJdLCBpbnB1dFt0eXBlPSJ0aW1lIl0sIGlucHV0W3R5cGU9IndlZWsiXSwgaW5wdXRbdHlwZT0ibnVtYmVyIl0sIGlucHV0W3R5cGU9ImVtYWlsIl0sIGlucHV0W3R5cGU9InVybCJdLCBpbnB1dFt0eXBlPSJzZWFyY2giXSwgaW5wdXRbdHlwZT0idGVsIl0sIGlucHV0W3R5cGU9ImNvbG9yIl0sIC51bmVkaXRhYmxlLWlucHV0IHt3aWR0aDo4MCU7YmFja2dyb3VuZC1jb2xvcjogI0ZGRkZGRjsKYm9yZGVyOiAxcHggc29saWQgI0NDQ0NDQzsKYm94LXNoYWRvdzogMCAxcHggMXB4IHJnYmEoMCwgMCwgMCwgMC4wNzUpIGluc2V0Owp0cmFuc2l0aW9uOiBib3JkZXIgMC4ycyBsaW5lYXIgMHMsIGJveC1zaGFkb3cgMC4ycyBsaW5lYXIgMHM7CnBhZGRpbmc6M3B4Owpjb2xvcjojNEM1MzVDO2ZvbnQtc2l6ZTogMTZweCAhaW1wb3J0YW50O30ubGVnZW5ke2NvbG9yOiAjNEM1MzVDOwpmb250LXdlaWdodDogbm9ybWFsOwp0ZXh0LWFsaWduOiByaWdodDsKdGV4dC10cmFuc2Zvcm06IGNhcGl0YWxpemU7fXRkLkJ1dHRvbkNlbGwgeyAKCQkJCXRleHQtYWxpZ246cmlnaHQ7CgkJCQl2ZXJ0aWNhbC1hbGlnbjpib3R0b207CgkJCQlwYWRkaW5nLXRvcDoyMHB4OwoJCQkJYm9yZGVyLXRvcDoxcHggc29saWQgI0NDQ0NDQzsKCQoJfS5CdXR0b24yMDE0LWxnIHsKCQkJYm9yZGVyLXJhZGl1czogNnB4IDZweCA2cHggNnB4OwoJCQlmb250LXNpemU6IDE4cHg7CgkJCWxpbmUtaGVpZ2h0OiAxLjMzOwoJCQlwYWRkaW5nOiAxMHB4IDE2cHg7CgkJfQoJCS5CdXR0b24yMDE0LXN1Y2Nlc3MgewoJCQliYWNrZ3JvdW5kLWNvbG9yOiAjNUNCODVDOwoJCQlib3JkZXItY29sb3I6ICM0Q0FFNEM7CgkJCWNvbG9yOiAjRkZGRkZGOwoJCX0KCQkuQnV0dG9uMjAxNCB7CgkJCS1tb3otdXNlci1zZWxlY3Q6IG5vbmU7CgkJCWJvcmRlcjogMXB4IHNvbGlkIHRyYW5zcGFyZW50OwoJCQlib3JkZXItcmFkaXVzOiA0cHggNHB4IDRweCA0cHg7CgkJCWN1cnNvcjogcG9pbnRlcjsKCQkJZGlzcGxheTogaW5saW5lLWJsb2NrOwoJCQlmb250LXNpemU6IDIycHggIWltcG9ydGFudDsKCQkJZm9udC13ZWlnaHQ6IG5vcm1hbDsKCQkJbGluZS1oZWlnaHQ6IDEuNDI4NTc7CgkJCW1hcmdpbi1ib3R0b206IDA7CgkJCXBhZGRpbmc6IDZweCAyMnB4OwoJCQl0ZXh0LWFsaWduOiBjZW50ZXI7CgkJCXZlcnRpY2FsLWFsaWduOiBtaWRkbGU7CgkJCXdoaXRlLXNwYWNlOiBub3dyYXA7CgkJfQoJCQoJCWEuQnV0dG9uMjAxNCwgYS5CdXR0b24yMDE0OmxpbmssIGEuQnV0dG9uMjAxNDp2aXNpdGVkLCBhLkJ1dHRvbjIwMTQ6aG92ZXJ7CgkJCWZvbnQtc2l6ZTogMjJweCAhaW1wb3J0YW50OwoJCQljb2xvcjogI0ZGRkZGRjsKCQkJdGV4dC1kZWNvcmF0aW9uOm5vbmU7CgkJfQoJCQoJCXRyLlRhYmxlQm91dG9uMjAxNHsKCQkJY3Vyc29yOiBwb2ludGVyOwoJCQliYWNrZ3JvdW5kLWNvbG9yOiA1Q0I4NUMgIWltcG9ydGFudDsKCQkJYm9yZGVyLWNvbG9yOiA0Q0FFNEMgIWltcG9ydGFudDsKCQkJY29sb3I6ICNGRkZGRkYgIWltcG9ydGFudDsKCQl9CgkJdHIuVGFibGVCb3V0b24yMDE0OmhvdmVyewoJCQljdXJzb3I6IHBvaW50ZXI7CgkJCWJhY2tncm91bmQtY29sb3I6ICM0N0E0NDcgIWltcG9ydGFudDsKCQkJYm9yZGVyLWNvbG9yOiAjNENBRTRDICFpbXBvcnRhbnQ7CgkJCWNvbG9yOiAjRkZGRkZGICFpbXBvcnRhbnQ7CgkJfQoJCQoJCXRkLlRhYmxlQm91dG9uMjAxNHsKCQkJYm9yZGVyLWNvbG9yOiAjNENBRTRDICFpbXBvcnRhbnQ7CgkJCWJvcmRlci1jb2xvcjogIzRDQUU0QyAhaW1wb3J0YW50OwoJCX0KCQl0ZC5UYWJsZUJvdXRvbjIwMTQ6aG92ZXJ7CgkJCWJhY2tncm91bmQtY29sb3I6IDQ3QTQ0NyAhaW1wb3J0YW50OwoJCQlib3JkZXItY29sb3I6ICM0Q0FFNEMgIWltcG9ydGFudDsKCQl9CgkJCgkJLkJ1dHRvbjIwMTQtc3VjY2VzcyB7CgkJCWJhY2tncm91bmQtY29sb3I6IDVDQjg1QyAhaW1wb3J0YW50OwoJCQlib3JkZXItY29sb3I6IDRDQUU0QyAhaW1wb3J0YW50OwoJCQljb2xvcjogI0ZGRkZGRiAhaW1wb3J0YW50OwoJCX0KCQkuQnV0dG9uMjAxNC1zdWNjZXNzOmhvdmVyLCAuQnV0dG9uMjAxNC1zdWNjZXNzOmZvY3VzLCAuQnV0dG9uMjAxNC1zdWNjZXNzOmFjdGl2ZSwgLkJ1dHRvbjIwMTQtc3VjY2Vzcy5hY3RpdmUsIC5vcGVuIC5kcm9wZG93bi10b2dnbGUuQnV0dG9uMjAxNC1zdWNjZXNzIHsKCQkJYmFja2dyb3VuZC1jb2xvcjogIzQ3QTQ0NyAhaW1wb3J0YW50OwoJCQlib3JkZXItY29sb3I6ICM0Q0FFNEMgIWltcG9ydGFudDsKCQkJY29sb3I6ICNGRkZGRkYgIWltcG9ydGFudDsKCQl9CgkJLkJ1dHRvbjIwMTQtc3VjY2VzczphY3RpdmUsIC5CdXR0b24yMDE0LXN1Y2Nlc3MuYWN0aXZlLCAub3BlbiAuZHJvcGRvd24tdG9nZ2xlLkJ1dHRvbjIwMTQtc3VjY2VzcyB7CgkJCWJhY2tncm91bmQtaW1hZ2U6IG5vbmU7CgkJfQoJCS5CdXR0b24yMDE0LXN1Y2Nlc3MuZGlzYWJsZWQsIC5CdXR0b24yMDE0LXN1Y2Nlc3NbZGlzYWJsZWRdLCBmaWVsZHNldFtkaXNhYmxlZF0gLkJ1dHRvbjIwMTQtc3VjY2VzcywgLkJ1dHRvbjIwMTQtc3VjY2Vzcy5kaXNhYmxlZDpob3ZlciwgLkJ1dHRvbjIwMTQtc3VjY2Vzc1tkaXNhYmxlZF06aG92ZXIsIGZpZWxkc2V0W2Rpc2FibGVkXSAuQnV0dG9uMjAxNC1zdWNjZXNzOmhvdmVyLCAuQnV0dG9uMjAxNC1zdWNjZXNzLmRpc2FibGVkOmZvY3VzLCAuQnV0dG9uMjAxNC1zdWNjZXNzW2Rpc2FibGVkXTpmb2N1cywgZmllbGRzZXRbZGlzYWJsZWRdIC5CdXR0b24yMDE0LXN1Y2Nlc3M6Zm9jdXMsIC5CdXR0b24yMDE0LXN1Y2Nlc3MuZGlzYWJsZWQ6YWN0aXZlLCAuQnV0dG9uMjAxNC1zdWNjZXNzW2Rpc2FibGVkXTphY3RpdmUsIGZpZWxkc2V0W2Rpc2FibGVkXSAuQnV0dG9uMjAxNC1zdWNjZXNzOmFjdGl2ZSwgLkJ1dHRvbjIwMTQtc3VjY2Vzcy5kaXNhYmxlZC5hY3RpdmUsIC5CdXR0b24yMDE0LXN1Y2Nlc3MuYWN0aXZlW2Rpc2FibGVkXSwgZmllbGRzZXRbZGlzYWJsZWRdIC5CdXR0b24yMDE0LXN1Y2Nlc3MuYWN0aXZlIHsKCQkJYmFja2dyb3VuZC1jb2xvcjogIzVDQjg1QyAhaW1wb3J0YW50OwoJCQlib3JkZXItY29sb3I6ICM0Q0FFNEMgIWltcG9ydGFudDsKCQl9Zm9ybSB7ICAgYmFja2dyb3VuZDogLW1vei1saW5lYXItZ3JhZGllbnQoY2VudGVyIHRvcCAsICNGMUYxRjEgMHB4LCAjRkZGRkZGIDQ1cHgpIHJlcGVhdCBzY3JvbGwgMCAwIHRyYW5zcGFyZW50OwpiYWNrZ3JvdW5kOiByZ2IoMjU1LDI1NSwyNTUpOyAvKiBPbGQgYnJvd3NlcnMgKi8KYmFja2dyb3VuZDogLW1vei1saW5lYXItZ3JhZGllbnQodG9wLCByZ2JhKDI1NSwyNTUsMjU1LDEpIDAlLCByZ2JhKDI0NiwyNDYsMjQ2LDEpIDQ3JSwgcmdiYSgyMzcsMjM3LDIzNywxKSAxMDAlKTsgLyogRkYzLjYrICovCmJhY2tncm91bmQ6IC13ZWJraXQtZ3JhZGllbnQobGluZWFyLCBsZWZ0IHRvcCwgbGVmdCBib3R0b20sIGNvbG9yLXN0b3AoMCUscmdiYSgyNTUsMjU1LDI1NSwxKSksIGNvbG9yLXN0b3AoNDclLHJnYmEoMjQ2LDI0NiwyNDYsMSkpLCBjb2xvci1zdG9wKDEwMCUscmdiYSgyMzcsMjM3LDIzNywxKSkpOyAvKiBDaHJvbWUsU2FmYXJpNCsgKi8KYmFja2dyb3VuZDogLXdlYmtpdC1saW5lYXItZ3JhZGllbnQodG9wLCByZ2JhKDI1NSwyNTUsMjU1LDEpIDAlLHJnYmEoMjQ2LDI0NiwyNDYsMSkgNDclLHJnYmEoMjM3LDIzNywyMzcsMSkgMTAwJSk7IC8qIENocm9tZTEwKyxTYWZhcmk1LjErICovCmJhY2tncm91bmQ6IC1vLWxpbmVhci1ncmFkaWVudCh0b3AsIHJnYmEoMjU1LDI1NSwyNTUsMSkgMCUscmdiYSgyNDYsMjQ2LDI0NiwxKSA0NyUscmdiYSgyMzcsMjM3LDIzNywxKSAxMDAlKTsgLyogT3BlcmEgMTEuMTArICovCmJhY2tncm91bmQ6IC1tcy1saW5lYXItZ3JhZGllbnQodG9wLCByZ2JhKDI1NSwyNTUsMjU1LDEpIDAlLHJnYmEoMjQ2LDI0NiwyNDYsMSkgNDclLHJnYmEoMjM3LDIzNywyMzcsMSkgMTAwJSk7IC8qIElFMTArICovCmJhY2tncm91bmQ6IGxpbmVhci1ncmFkaWVudCh0byBib3R0b20sIHJnYmEoMjU1LDI1NSwyNTUsMSkgMCUscmdiYSgyNDYsMjQ2LDI0NiwxKSA0NyUscmdiYSgyMzcsMjM3LDIzNywxKSAxMDAlKTsgLyogVzNDICovCmZpbHRlcjogcHJvZ2lkOkRYSW1hZ2VUcmFuc2Zvcm0uTWljcm9zb2Z0LmdyYWRpZW50KCBzdGFydENvbG9yc3RyPScjZmZmZmZmJywgZW5kQ29sb3JzdHI9JyNlZGVkZWQnLEdyYWRpZW50VHlwZT0wICk7IC8qIElFNi05ICovCmJvcmRlcjogMXB4IHNvbGlkICNEREREREQ7CmJvcmRlci1yYWRpdXM6IDVweCA1cHggNXB4IDVweDsKYm94LXNoYWRvdzogMnB4IDJweCA4cHggcmdiYSgwLCAwLCAwLCAwLjYpOwptYXJnaW46IDVweDsKcGFkZGluZzogMjFweDsKfWZvcm0geyAgIGNvbG9yOiMwMDAwMDB9Zm9ybSA+IHAgeyAgIGNvbG9yOiMwMDAwMDB9bGkgeyAgICBtYXJnaW4tdG9wOiA4cHg7fS5kaWFnbm9zZUJ1dHRvbiB7ICAgIG91dGxpbmU6IG5vbmU7ICAgIGZvbnQtc2l6ZTogOXB0O30K',
		'PCFET0NUWVBFIEhUTUw+CjxIVE1MPgo8SEVBRD4KPHRpdGxlPiVUSVRMRV9IRUFEJTwvdGl0bGU+CjxNRVRBIGNvbnRlbnQ9IklFPTExLjAwMDAiIGh0dHAtZXF1aXY9IlgtVUEtQ29tcGF0aWJsZSI+CjxNRVRBIGh0dHAtZXF1aXY9IkNvbnRlbnQtVHlwZSIgY29udGVudD0idGV4dC9odG1sOyBjaGFyc2V0PUlTTy04ODU5LTEiPgolSlFVRVJZJQolQ1NTJQoKCjxzY3JpcHQgdHlwZT0idGV4dC9qYXZhc2NyaXB0Ij4KCQlmdW5jdGlvbiBibHVyKCl7IH0KCQlmdW5jdGlvbiBCbHVyeigpIHsgfQoJCWZ1bmN0aW9uIGNoZWNrSWZUb3BNb3N0V2luZG93KCkKCQl7CgkJCWlmICh3aW5kb3cudG9wICE9IHdpbmRvdy5zZWxmKQoJCQl7CgkJCQlkb2N1bWVudC5ib2R5LnN0eWxlLm9wYWNpdHkgICAgPSAiMC4wIjsKCQkJCWRvY3VtZW50LmJvZHkuc3R5bGUuYmFja2dyb3VuZCA9ICIjRkZGRkZGIjsKCQkJfQoJCQllbHNlCgkJCXsKCQkJCWRvY3VtZW50LmJvZHkuc3R5bGUub3BhY2l0eSAgICA9ICIxLjAiOwoJCQkJZG9jdW1lbnQuYm9keS5zdHlsZS5iYWNrZ3JvdW5kID0gIiMwMDU0NDciOwoJCQl9CgkJfQoJCTwvc2NyaXB0PgoKPC9IRUFEPgo=',
		'PEJPRFkgb25Mb2FkPSdjaGVja0lmVG9wTW9zdFdpbmRvdygpJz4KPGRpdiBjbGFzcz1tYWluYm9keT4KCTxkaXYgY2xhc3M9InRpdGxlIiBpZD0ibWFpblRpdGxlIj4lVElUTEVfSEVBRCU8L2Rpdj4KCTxkaXYgY2xhc3M9Im1haW5Db250ZW50IiBpZD0iY29udGVudENvbnRhaW5lciI+CgkJPGRpdiBjbGFzcz0idGFza1NlY3Rpb24iIGlkPSJ0YXNrU2VjdGlvbiI+JURZTkFNSUNfQ09OVEVOVCU8L2Rpdj4KCQklRk9PVEVSJQoJPC9kaXY+CgkKPC9kaXY+Cg==')
		";
    $qlite->QUERY_SQL($sql);
    if (!$qlite->ok) {
        echo "$qlite->mysql_error (".__LINE__.")\n$sql\n";
    }

    echo "[".__LINE__."]: Migrate table templates_files\n";
    $sql="CREATE TABLE IF NOT EXISTS templates_files  (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`filename` TEXT UNIQUE,
		`contentfile` BLOB,
		`contenttype` TEXT,
		`contentsize` INTEGER )";
    $qlite->QUERY_SQL($sql);
    if (!$qlite->ok) {
        echo "$qlite->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table squidtpls\n";
    $sql="CREATE TABLE IF NOT EXISTS `squidtpls` (
			  `zmd5` text PRIMARY KEY,
			  `template_name` TEXT  NOT NULL,
			  `template_body` LONGTEXT  NOT NULL,
			  `template_header` LONGTEXT  NOT NULL,
			  `template_title` TEXT  NOT NULL,
			  `template_time` text,
			  `template_link` INTEGER,
			  `template_uri` TEXT  NOT NULL,
			  `emptytpl` INTEGER,
			  `lang` varchar(5)  NOT NULL);";
    $qlite->QUERY_SQL($sql);
    if(!$qlite->ok){echo "$qlite->mysql_error (".__LINE__.")\n$sql\n";}
    // migrate_data_squid("squidtpls","/home/artica/SQLITE/proxy.db");

    sys();
    rpz_database();
    suricata();
    proxy_tables();
    webfilter_tables();
    haproxy_tables();
    keepalived();
    adagent_tables();
    CheckNGINXTables();
    postfix_tables();
    admins_tables();
    acls_tables();
    openvpn();
    strongswan();
    schedules();
    caches();
    hypercaches_tables();
    postfix_events();
    dns_tables();
    groups_privs();
    proftpd_table();
    CheckUsersTables();
    ntp();
    ipinfo();
    sshd();
    return true;
}



function groups_privs():bool
{
    $q=new lib_sqlite("/home/artica/SQLITE/privileges.db");
    @chmod("/home/artica/SQLITE/privileges.db", 0644);
    @chown("/home/artica/SQLITE/privileges.db", "www-data");
    $sql="CREATE TABLE IF NOT EXISTS `adgroupsprivs` ( `ID` INTEGER PRIMARY KEY AUTOINCREMENT, `DN` text UNIQUE, `content` TEXT NOT NULL )";
    $q->QUERY_SQL($sql);

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

function acls_tables():bool
{
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    @chmod("/home/artica/SQLITE/acls.db", 0644);
    @chown("/home/artica/SQLITE/acls.db", "www-data");

    echo "[".__LINE__."]: Migrate table sessions_objects\n";
    $sql="CREATE TABLE IF NOT EXISTS `sessions_objects` (
	            `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	             `objectname` TEXT,
	            `ttl` INTEGER,`sleep` INTEGER,
	            `identifier` TEXT NOT NULL DEFAULT 'SRC') ";
    $q->QUERY_SQL($sql);


    echo "[".__LINE__."]: Migrate table UsersAgentsDB FROM ----\n";
    $sql = "CREATE TABLE IF NOT EXISTS `UsersAgentsDB` (
                `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`explain` TEXT,
				`editor` TEXT,
				`category` TEXT,
				`pattern` TEXT UNIQUE,
				`bypass` INTEGER NOT NULL DEFAULT 1,
				`bypassWebF` INTEGER NOT NULL DEFAULT 0,
				`bypassWebC` INTEGER NOT NULL DEFAULT 0,
				`deny` INTEGER NOT NULL DEFAULT 0,
				`enabled` INTEGER NOT NULL DEFAULT 0
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "[".__LINE__."]: $q->mysql_error\n";}

    if(!$q->INDEX_EXISTS("UsersAgentsDB","tag_explain")) {
        $q->QUERY_SQL("CREATE INDEX tag_explain ON UsersAgentsDB (explain);");
        $q->QUERY_SQL("CREATE INDEX tag_pattern ON UsersAgentsDB (pattern);");
        $q->QUERY_SQL("CREATE INDEX tag_category ON UsersAgentsDB (category);");
        $q->QUERY_SQL("CREATE INDEX tag_editor ON UsersAgentsDB (editor);");
        if (!$q->ok) {
            echo "[" . __LINE__ . "]: F $q->mysql_error\n";
        }
    }

        echo "[".__LINE__."]: Migrate table qos_sqacllinks\n";
    $sql="CREATE TABLE IF NOT EXISTS `qos_sqacllinks` (
			`zmd5` TEXT PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER,
			`direction` INTEGER,
			`gpid` INTEGER,
			`zOrder` INT)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}



    echo "[".__LINE__."]: Migrate table dnsdist_rules\n";
    $sql="CREATE TABLE IF NOT EXISTS `dnsdist_rules` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`rulename` TEXT,enabled INTEGER DEFAULT 1,dns_caches TEXT NULL,
        ruletype INTEGER DEFAULT 1,checkInterval INT,maxCheckFailures INT,checkName TEXT,caches TEXT,zOrder INTEGER DEFAULT 1)";
    $q->QUERY_SQL($sql);
    if(!$q->FIELD_EXISTS("dnsdist_rules","checkInterval")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD checkInterval INT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","maxCheckFailures")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD maxCheckFailures INT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","checkName")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD checkName TEXT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","checkTimeout")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD checkTimeout INT");
    }
    $q=new lib_sqlite("/home/artica/SQLITE/acls.db");
    if(!$q->FIELD_EXISTS("dnsdist_rules","caches")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD caches TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","uuid")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD uuid TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","simpledomains")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD `simpledomains` TEXT NULL");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","maxCheckFailures")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD maxCheckFailures INT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","checkTimeout")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD checkTimeout INT");
    }
    if(!$q->FIELD_EXISTS("dnsdist_rules","dns_caches")){
        $q->QUERY_SQL("ALTER TABLE dnsdist_rules ADD dns_caches TEXT NULL");
    }
    if (!$q->ok) {echo __FUNCTION__." (".__LINE__."):Fatal: ".$q->mysql_error."\n$sql\n";}

    $q->QUERY_SQL($sql);


    echo "[".__LINE__."]: Migrate table http_reply_access\n";
    $sql="CREATE TABLE IF NOT EXISTS `http_reply_access` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`allow` INTEGER NOT NULL DEFAULT 0 ,
		`config` TEXT ,
		`zorder`  SMALLINT( 3 ) NOT NULL
		)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    $sql="CREATE TABLE IF NOT EXISTS `http_reply_access_links` (
		`zmd5` TEXT NOT NULL PRIMARY KEY ,
		`aclid` INTEGER ,
		`negation` INTEGER ,
		`gpid` INTEGER,
		`zOrder` INTEGER
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    echo "[".__LINE__."]: Migrate table webfilters_sqaclaccess\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_sqaclaccess` (
		`zmd5` TEXT NOT NULL PRIMARY KEY ,
		`aclid` INTEGER ,
		`httpaccess` TEXT NOT NULL ,
		`httpaccess_value`  INTEGER NOT NULL,
		`httpaccess_data`  TEXT)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table webfilters_sqaclsports\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_sqaclsports` (
			`aclport` SMALLINT( 5 ) PRIMARY KEY,
			`portname` VARCHAR( 128 ) NOT NULL,
			`interface`  VARCHAR( 128 ),
			`enabled`  INTEGER )";


    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table webfilter_aclsdynamic_rights\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_aclsdynamic_rights` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`gpid` INTEGER,
			`type` INTEGER,
			`pattern` TEXT  )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table tcp_outgoing_mark\n";

    $sql="CREATE TABLE IF NOT EXISTS `tcp_outgoing_mark` (
        `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`mark` varchar( 20 ) NOT NULL ,
		`config` TEXT ,
		`zorder`  SMALLINT( 3 ) NOT NULL
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    $sql="CREATE TABLE IF NOT EXISTS `tcp_outgoing_mark_links` (
		`zmd5` TEXT NOT NULL PRIMARY KEY ,
		`aclid` INTEGER ,
		`negation` INTEGER ,
		`gpid` INTEGER,
		`zOrder` INTEGER
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    echo "[".__LINE__."]: Create table squid_auth_schemes_acls\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_auth_schemes_acls` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`ztype` varchar( 20 ) NOT NULL ,
		`aclport` INTEGER ,
		`zorder`  INTEGER NOT NULL DEFAULT 1
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    echo "[".__LINE__."]: Create table squid_auth_schemes_link\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_auth_schemes_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zorder` INTEGER
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Create table squid_http_headers_acls\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_http_headers_acls` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`ztype` varchar( 20 ) NOT NULL ,
		`headername` TEXT,
		`headervalue` TEXT,
		`aclport` INTEGER ,
		`zorder`  INTEGER NOT NULL DEFAULT 1
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    echo "[".__LINE__."]: Create table squid_http_headers_link\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_http_headers_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zorder` INTEGER
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}



    $sql="CREATE TABLE IF NOT EXISTS `squid_http_bandwidth_acls` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`limit_network` INTEGER NOT NULL DEFAULT 0,
		`network_max_size` INTEGER NOT NULL DEFAULT 0,
		`network_bandwidth` INTEGER NOT NULL DEFAULT 0,
		`limit_client` INTEGER NOT NULL DEFAULT 0,
		`client_maxsize` INTEGER NOT NULL DEFAULT 0,
		`client_bandwidth` INTEGER NOT NULL DEFAULT 0,
		`delay_pool_number` INTEGER NOT NULL DEFAULT 0,
		`zorder`  INTEGER NOT NULL DEFAULT 1
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    $sql="CREATE TABLE IF NOT EXISTS `squid_http_bandwidth_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zorder` INTEGER
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    $sql="CREATE TABLE IF NOT EXISTS `squid_icap_acls` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` TEXT NOT NULL,
        `aclname` TEXT,
		`enabled` INTEGER NOT NULL ,
		`ztype` varchar( 20 ) NOT NULL ,
		`headername` TEXT,
		`headervalue` TEXT,
		`aclport` INTEGER ,
		`zorder`  INTEGER NOT NULL DEFAULT 1
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    echo "[".__LINE__."]: Create table squid_icap_acls_link\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_icap_acls_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zorder` INTEGER
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    $sql="CREATE TABLE IF NOT EXISTS `squid_url_rewrite_acls` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` TEXT NOT NULL ,
        `aclname` TEXT,
		`enabled` INTEGER NOT NULL ,
		`ztype` varchar( 20 ) NOT NULL ,
		`headername` TEXT,
		`headervalue` TEXT,
		`aclport` INTEGER ,
		`zorder`  INTEGER NOT NULL DEFAULT 1
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    echo "[".__LINE__."]: Create table squid_url_rewrite_link\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_url_rewrite_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zorder` INTEGER
			)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table global_whitelist\n";
    $sql="CREATE TABLE IF NOT EXISTS `global_whitelist` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`pattern` TEXT NOT NULL,
				`description` VARCHAR( 128 ) NOT NULL,
				`zDate` datetime NOT NULL,
				`type` INTEGER ,
				`enabled` INTEGER NULL,
				`ruletype` INTEGER NULL,
				`groupid` BIGINT( 100 ) NOT NULL,
				`deny_cache` INTEGER NULL DEFAULT 0,
				`deny_auth` INTEGER NULL DEFAULT 0,
				`deny_ufdb` INTEGER NULL DEFAULT 0,
				`deny_icap` INTEGER NULL DEFAULT 0,
				`deny_ext` INTEGER NULL DEFAULT 0,
				`deny_global` INTEGER NULL DEFAULT 0,
				`frommeta` INTEGER NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}





    echo "[".__LINE__."]: Migrate table squid_logs_acls\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_logs_acls` (
		`aclid` INTEGER PRIMARY KEY AUTOINCREMENT,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`logtype` INTEGER NOT NULL ,
		`logconfig` TEXT ,
		`zorder`  SMALLINT( 3 ) NOT NULL
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}




    echo "[".__LINE__."]: Migrate table wpad_rules FROM wpad_sources_link\n";
    $sql="CREATE TABLE IF NOT EXISTS `wpad_sources_link` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER , `negation` INTEGER, `gpid` INTEGER, `zorder` INTEGER  )  ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n"; return false;}






    echo "[".__LINE__."]: Migrate table wpad_events\n";
    $sql="CREATE TABLE IF NOT EXISTS `wpad_events` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`zDate` DATETIME NOT NULL ,
			`ruleid` INT UNSIGNED ,
			`ipaddr` TEXT NOT NULL ,
			`hostname` TEXT NOT NULL ,
			`browser` TEXT NOT NULL ,
			`script` TEXT
			)  ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}

    echo "[".__LINE__."]: Migrate table webfilters_blkwhlts\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_blkwhlts` (
			  `pattern` TEXT NOT NULL,
			  `description` TEXT,
			  `enabled` INTEGER,
			  `PatternType` INTEGER,
			  `blockType` INTEGER,
			  `zmd5` TEXT NOT NULL,
			  PRIMARY KEY (`zmd5`) )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}





    echo "[".__LINE__."]: Migrate table outgoingaddr_sqacllinks\n";
    $sql="CREATE TABLE IF NOT EXISTS `outgoingaddr_sqacllinks` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL )  ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table privoxy_sqacllinks\n";
    $sql="CREATE TABLE IF NOT EXISTS `privoxy_sqacllinks` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL )  ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table squid_privoxy_acls\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_privoxy_acls` (
		`aclid` INTEGER PRIMARY KEY AUTOINCREMENT ,
		`rulename` VARCHAR( 128 ) NOT NULL ,
		`enabled` INTEGER NOT NULL ,
		`deny` INTEGER NOT NULL ,
		`config` TEXT ,
		`zorder`  SMALLINT( 3 ) NOT NULL
		)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table parents_sqacllinks\n";
    $sql="CREATE TABLE IF NOT EXISTS `parents_sqacllinks` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table parents_white_sqacllinks\n";
    $sql="CREATE TABLE IF NOT EXISTS `parents_white_sqacllinks` (
			`zmd5` TEXT NOT NULL PRIMARY KEY ,
			`aclid` INTEGER ,
			`negation` INTEGER ,
			`gpid` INT UNSIGNED ,
			`zOrder` INT( 10 ) NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table deny_cache_domains\n";
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `deny_cache_domains` 
			( `items` VARCHAR(256) PRIMARY KEY,ztype TEXT )");
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}



    echo "[".__LINE__."]: Migrate table ext_time_quota_acl\n";
    $sql="CREATE TABLE IF NOT EXISTS `ext_time_quota_acl` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				  `QuotaName` TEXT NOT NULL,
				  `QuotaType` TEXT NOT NULL,
				  `TTL` INTEGER NOT NULL DEFAULT 60,
				  `enabled` INTEGER NOT NULL DEFAULT 1,
				  `details` TEXT NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}



    echo "[".__LINE__."]: Migrate table ext_time_quota_acl_rules\n";
    $sql="CREATE TABLE IF NOT EXISTS `ext_time_quota_acl_rules` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				  `ruleid` INTEGER NOT NULL,
				  `rulename` TEXT NOT NULL,
				  `budget` TEXT NOT NULL,
				  `period` TEXT NOT NULL,
				  `enabled` INTEGER NOT NULL DEFAULT 1 )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table ext_time_quota_acl_link\n";
    $sql="CREATE TABLE IF NOT EXISTS `ext_time_quota_acl_link` (
				  `zmd5` VARCHAR(100) PRIMARY KEY,
				  `ruleid` INTEGER NOT NULL,
				  `groupid` INTEGER NOT NULL,
				  `enabled` INTEGER NOT NULL DEFAULT 1)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }
    // migrate_data_squid("ext_time_quota_acl_link", "/home/artica/SQLITE/acls.db");

    echo "[".__LINE__."]: Migrate table squid_pools FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_pools` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`rulename` TEXT NOT NULL ,
			`rule_class` INTEGER,
			`enable` INTEGER NOT NULL  DEFAULT 1,
			`total_net_enabled` INTEGER NOT NULL DEFAULT 0,
			`total_net_max` INTEGER NOT NULL DEFAULT 0,
			`total_net` TEXT NOT NULL ,
			`total_net_band` INTEGER NOT NULL,
			`total_computer_enabled` INTEGER NOT NULL DEFAULT 0,
			`total_computer_max` INTEGER NOT NULL DEFAULT 0,
			`total_computer_band` INTEGER NOT NULL ,
	
			`total_user_enabled` INTEGER NOT NULL DEFAULT 0,
			`total_user_max` INTEGER NOT NULL DEFAULT 0,
			`total_user_band` INTEGER NOT NULL ,
				
			`total_member_band` INTEGER NOT NULL DEFAULT 0,
			`total_member_max` INTEGER NOT NULL DEFAULT 0,
			`total_member_enabled` INTEGER NOT NULL DEFAULT 0,				
			
			`total_users` TEXT NOT NULL )";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }



    echo "[".__LINE__."]: Migrate table squid_pools_acls FROM artica_backup\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_pools_acls` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`pool_id` INTEGER NOT NULL ,
			`ACL_TYPE` TEXT NOT NULL ,
			`ACL_DATAS` TEXT NOT NULL ,
			`enabled`INTEGER NOT NULL DEFAULT '1' ) ";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table limit_bdwww\n";
    $sql="CREATE TABLE IF NOT EXISTS `limit_bdwww` ( `website` TEXT NOT NULL PRIMARY KEY )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }
    // migrate_data_squid("limit_bdwww", "/home/artica/SQLITE/acls.db");




    echo "[".__LINE__."]: Create table http_headers\n";
    $sql="CREATE TABLE IF NOT EXISTS `http_headers` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT, `HeaderName` TEXT UNIQUE)";
    $q->QUERY_SQL($sql);
    $CountOfHTTP=$q->COUNT_ROWS("http_headers");
    echo "[".__LINE__."]: http_headers: $CountOfHTTP items\n";
    if ($q->COUNT_ROWS("http_headers")==0) {
        $f[]="A-IM";
        $f[]="Accept";
        $f[]="Accept-Additions";
        $f[]="Accept-Charset";
        $f[]="Accept-Datetime";
        $f[]="Accept-Encoding";
        $f[]="Accept-Features";
        $f[]="Accept-Language";
        $f[]="Accept-Patch";
        $f[]="Accept-Post";
        $f[]="Accept-Ranges";
        $f[]="Access-Control-Allow-Origin";
        $f[]="All";
        $f[]="Age";
        $f[]="Allow";
        $f[]="ALPN";
        $f[]="Alt-Svc";
        $f[]="Alt-Used";
        $f[]="Alternates";
        $f[]="Alternate-Protocol";
        $f[]="Apply-To-Redirect-Ref";
        $f[]="Authentication-Control";
        $f[]="Authentication-Info";
        $f[]="Authorization";
        $f[]="C-Ext";
        $f[]="C-Man";
        $f[]="C-Opt";
        $f[]="C-PEP";
        $f[]="C-PEP-Info";
        $f[]="Cache-Control";
        $f[]="CalDAV-Timezones";
        $f[]="Close";
        $f[]="Connection";
        $f[]="Content-Base";
        $f[]="Content-Disposition";
        $f[]="Content-Encoding";
        $f[]="Content-ID";
        $f[]="Content-Language";
        $f[]="Content-Length";
        $f[]="Content-Location";
        $f[]="Content-MD5";
        $f[]="Content-Range";
        $f[]="Content-Script-Type";
        $f[]="Content-Style-Type";
        $f[]="Content-Type";
        $f[]="Content-Version";
        $f[]="Cookie";
        $f[]="Cookie2";
        $f[]="DASL";
        $f[]="DAV";
        $f[]="Date";
        $f[]="Default-Style";
        $f[]="Delta-Base";
        $f[]="Depth";
        $f[]="Derived-From";
        $f[]="Destination";
        $f[]="Differential-ID";
        $f[]="Digest";
        $f[]="Early-Data";
        $f[]="ETag";
        $f[]="Expect";
        $f[]="Expires";
        $f[]="Ext";
        $f[]="Forwarded";
        $f[]="From";
        $f[]="GetProfile";
        $f[]="Hobareg";
        $f[]="Host";
        $f[]="HTTP2-Settings";
        $f[]="IM";
        $f[]="If";
        $f[]="If-Match";
        $f[]="If-Modified-Since";
        $f[]="If-None-Match";
        $f[]="If-Range";
        $f[]="If-Schedule-Tag-Match";
        $f[]="If-Unmodified-Since";
        $f[]="Include-Referred-Token-Binding-ID";
        $f[]="Keep-Alive";
        $f[]="Label";
        $f[]="Last-Modified";
        $f[]="Link";
        $f[]="Location";
        $f[]="Lock-Token";
        $f[]="Man";
        $f[]="Max-Forwards";
        $f[]="Memento-Datetime";
        $f[]="Meter";
        $f[]="MIME-Version";
        $f[]="Negotiate";
        $f[]="Opt";
        $f[]="Optional-WWW-Authenticate";
        $f[]="Ordering-Type";
        $f[]="Origin";
        $f[]="Overwrite";
        $f[]="P3P";
        $f[]="PEP";
        $f[]="PICS-Label";
        $f[]="Pep-Info";
        $f[]="Position";
        $f[]="Pragma";
        $f[]="Prefer";
        $f[]="Preference-Applied";
        $f[]="ProfileObject";
        $f[]="Protocol";
        $f[]="Protocol-Info";
        $f[]="Protocol-Query";
        $f[]="Protocol-Request";
        $f[]="Proxy-Authenticate";
        $f[]="Proxy-Authentication-Info";
        $f[]="Proxy-Authorization";
        $f[]="Proxy-Features";
        $f[]="Proxy-Instruction";
        $f[]="Public";
        $f[]="Public-Key-Pins";
        $f[]="Public-Key-Pins-Report-Only";
        $f[]="Range";
        $f[]="Redirect-Ref";
        $f[]="Referer";
        $f[]="Retry-After";
        $f[]="Safe";
        $f[]="Schedule-Reply";
        $f[]="Schedule-Tag";
        $f[]="Sec-Token-Binding";
        $f[]="Sec-WebSocket-Accept";
        $f[]="Sec-WebSocket-Extensions";
        $f[]="Sec-WebSocket-Key";
        $f[]="Sec-WebSocket-Protocol";
        $f[]="Sec-WebSocket-Version";
        $f[]="Security-Scheme";
        $f[]="Server";
        $f[]="Set-Cookie";
        $f[]="Set-Cookie2";
        $f[]="SetProfile";
        $f[]="SLUG";
        $f[]="SoapAction";
        $f[]="Status-URI";
        $f[]="Strict-Transport-Security";
        $f[]="Surrogate-Capability";
        $f[]="Surrogate-Control";
        $f[]="TCN";
        $f[]="TE";
        $f[]="Timeout";
        $f[]="Topic";
        $f[]="Trailer";
        $f[]="Transfer-Encoding";
        $f[]="TTL";
        $f[]="Urgency";
        $f[]="URI";
        $f[]="Upgrade";
        $f[]="User-Agent";
        $f[]="Variant-Vary";
        $f[]="Vary";
        $f[]="Via";
        $f[]="WWW-Authenticate";
        $f[]="Want-Digest";
        $f[]="Warning";
        $f[]="X-Content-Type-Options";
        $f[]="X-Frame-Options";
        $f[]="Access-Control";
        $f[]="Access-Control-Allow-Credentials";
        $f[]="Access-Control-Allow-Headers";
        $f[]="Access-Control-Allow-Methods";
        $f[]="Access-Control-Allow-Origin";
        $f[]="Access-Control-Max-Age";
        $f[]="Access-Control-Request-Method";
        $f[]="Access-Control-Request-Headers";
        $f[]="Compliance";
        $f[]="Content-Transfer-Encoding";
        $f[]="Cost";
        $f[]="EDIINT-Features";
        $f[]="Message-ID";
        $f[]="Method-Check";
        $f[]="Method-Check-Expires";
        $f[]="Non-Compliance";
        $f[]="Optional";
        $f[]="Referer-Root";
        $f[]="Resolution-Hint";
        $f[]="Resolver-Location";
        $f[]="SubOK";
        $f[]="Subst";
        $f[]="Title";
        $f[]="UA-Color";
        $f[]="UA-Media";
        $f[]="UA-Pixels";
        $f[]="UA-Resolution";
        $f[]="UA-Windowpixels";
        $f[]="Version";
        $f[]="X-Device-Accept";
        $f[]="X-Device-Accept-Charset";
        $f[]="X-Device-Accept-Encoding";
        $f[]="X-Device-Accept-Language";
        $f[]="X-Device-User-Agent";
        $f[]="X-GoogApps-Allowed-Domains";
        $newf=array();
        foreach ($f as $field){
            $newf[]="('$field')";
        }

        $sql="INSERT OR IGNORE INTO http_headers (`HeaderName`) VALUES ".@implode(",", $newf);
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "**************************** FATAL ****************************\n$q->mysql_error\n\n\n";}
    }
    //CHEK X-GoogApps-Allowed-Domains
    $sql="SELECT COUNT(*) as tcount from http_headers where HeaderName='X-GoogApps-Allowed-Domains'";
    $res=$q->mysqli_fetch_array($sql);

    if (intval($res["tcount"]) == 0) {
        $sql="INSERT OR IGNORE INTO http_headers (`HeaderName`) VALUES ('X-GoogApps-Allowed-Domains')";
        $q->QUERY_SQL($sql);
        if(!$q->ok){echo "**************************** FATAL ****************************\n$q->mysql_error\n\n\n";}
    }
return true;

}


function admins_tables():bool
{
    $q=new lib_sqlite("/home/artica/SQLITE/admins.db");
    @chmod("/home/artica/SQLITE/admins.db", 0644);
    @chown("/home/artica/SQLITE/admins.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS `groups` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`groupname` TEXT,
		`privileges` TEXT,
		`enabled` INTEGER DEFAULT 1)";


    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS `users` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`username` TEXT,
		`passmd5` TEXT,
		`groupid` INTEGER,
		`enabled` INTEGER DEFAULT 1)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `lnk` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`userid` INTEGER,
		`groupid` INT)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }



    $sql="CREATE TABLE IF NOT EXISTS `APIs` (
		`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		`zmd5` TEXT NOT NULL UNIQUE,
		`userid` TEXT,
		`content` TEXT	
		)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    $sql="CREATE TABLE IF NOT EXISTS `admintracks` (
		`time` INTEGER PRIMARY KEY,
		`ipaddr` TEXT,
		`username` TEXT,
		`operation` TEXT)";
    $q->QUERY_SQL($sql);

    return true;
}




function webfilter_tables():bool
{
    $q=new lib_sqlite("/home/artica/SQLITE/webfilter.db");
    @chmod("/home/artica/SQLITE/webfilter.db", 0644);
    @chown("/home/artica/SQLITE/webfilter.db", "www-data");

    echo "[".__LINE__."]: Migrate table webfilter_assoc_groups\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_assoc_groups` (
				   `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				    webfilter_id INTEGER,
				  	group_id INTEGER,
				  	zMD5 TEXT UNIQUE)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilter_blks\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_blks` (
	 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
	  webfilter_id INTEGER,
	 modeblk INTEGER,
	 category TEXT NOT NULL
	) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilter_dnsbl\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_dnsbl` (
				  `dnsbl` TEXT UNIQUE,
				  `name` TEXT NOT NULL,
				  `uri` TEXT NOT NULL ,
				  `enabled` INTEGER DEFAULT '1'
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }



    $sql="CREATE TABLE IF NOT EXISTS `webfilter_notifications` ( `category` integer PRIMARY KEY )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilters_blkwhlts\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_blkwhlts` (
			  `pattern` TEXT NOT NULL,
			  `description` TEXT,
			  `enabled` INTEGER,
			  `PatternType` INTEGER,
			  `blockType` INTEGER,
			  `zmd5` TEXT NOT NULL,
			  PRIMARY KEY (`zmd5`) )";
    $q->QUERY_SQL($sql);



    echo "[".__LINE__."]: Migrate table webfilter_rules\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_rules` (
		 			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				  	groupmode INTEGER,
				  	enabled INTEGER,
					groupname TEXT,
					BypassSecretKey TEXT,
					endofrule TEXT ,
					blockdownloads INTEGER DEFAULT '0' ,
					naughtynesslimit INTEGER DEFAULT '50' ,
					searchtermlimit INTEGER DEFAULT '30' ,
					bypass INTEGER DEFAULT '0' ,
					deepurlanalysis  INTEGER DEFAULT '0' ,
					UseExternalWebPage INTEGER DEFAULT '0' ,
					UseReferer INTEGER DEFAULT '0' ,
					ExternalWebPage TEXT ,
					freeweb TEXT ,
					sslcertcheck INTEGER DEFAULT '0' ,
					sslmitm INTEGER DEFAULT '0',
					GoogleSafeSearch INTEGER DEFAULT '0',
					`embeddedurlweight` INTEGER,
					TimeSpace TEXT,
					TemplateError TEXT,
					TemplateColor1 TEXT,
					TemplateColor2 TEXT,
					RewriteRules TEXT,
					zOrder INTEGER,
					AllSystems INTEGER,
					`http_code` INTEGER,
					UseSecurity INTEGER
				) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilter_blklnk\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_blklnk` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				 zmd5 TEXT UNIQUE,
				 webfilter_blkid INTEGER,
				 webfilter_ruleid  INTEGER,
				 blacklist INTEGER NOT NULL DEFAULT '1'
				 )";


    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }



    echo "[".__LINE__."]: Migrate table webfilter_blkgp\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_blkgp` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				 groupname TEXT NOT NULL,
				 enabled INTEGER NOT NULL DEFAULT '1')";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilter_blkcnt\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_blkcnt` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`webfilter_blkid` INT( 10 ) NOT NULL,
				 category INTEGER NOT NULL DEFAULT 0
				)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilter_catprivs\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_catprivs` (
			`zmd5` TEXT PRIMARY KEY,
			`categorykey` TEXT NOT NULL,
			`groupdata` TEXT NOT NULL,
			`allowrecompile` INTEGER NOT NULL DEFAULT 0
			) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilter_certs\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_certs` (`zmd5` TEXT PRIMARY KEY,`certname` TEXT NOT NULL, `certdata` TEXT NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    fill_webfilter_certs();


    echo "[".__LINE__."]: Migrate table webfilter_group\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_group` (
		 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			groupname TEXT NOT NULL,
			localldap INTEGER NOT NULL DEFAULT '0' ,
			enabled INTEGER NOT NULL DEFAULT '1' ,
			gpid INTEGER NOT NULL DEFAULT '0' ,
			description TEXT NOT NULL,
			`dn` TEXT NOT NULL,
			`settings` TEXT)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    echo "[".__LINE__."]: Migrate table webfilter_members\n";

    $sql="CREATE TABLE IF NOT EXISTS `webfilter_members` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
					pattern TEXT NOT NULL,
					enabled INTEGER NOT NULL DEFAULT '1' ,
					groupid INTEGER NOT NULL DEFAULT '0' ,
					membertype INTEGER NOT NULL DEFAULT '0')";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }



    echo "[".__LINE__."]: Migrate table webfilters_dtimes_rules\n";

    $sql="CREATE TABLE IF NOT EXISTS `webfilters_dtimes_rules` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`TimeName` VARCHAR( 128 ) NOT NULL ,
			`TimeCode` TEXT NOT NULL ,
			`enabled` INTEGER ,
			`ruleid` INT 
			) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;


    }

    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilter_assoc_quota_groups\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_assoc_quota_groups` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				    webfilter_id INTEGER NOT NULL,
				  	group_id INTEGER NOT NULL,
				  	zMD5 TEXT UNIQUE
				)  ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilters_rewriterules\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_rewriterules` (
				 `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		    	  rulename TEXT NOT NULL,
				  enabled INTEGER NOT NULL DEFAULT 1,
				  ItemsCount INTEGER NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilter_termsg\n";

    $sql="CREATE TABLE IF NOT EXISTS `webfilter_termsg` (
				   `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				   groupname TEXT NOT NULL,
				   enabled INTEGER NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilter_terms\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_terms` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				   term TEXT NOT NULL,
				   enabled INTEGER NOT NULL,
				   xregex INTEGER NOT NULL)  ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilter_termsassoc\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_termsassoc` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT ,
				   term_group BIGINT( 100) NOT NULL,
				   termid BIGINT( 100)NOT NULL) ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    // ---------------------------------------------------------------------------------------------------

    echo "[".__LINE__."]: Migrate table webfilter_ufdbexpr\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_ufdbexpr` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT ,
				   rulename TEXT NOT NULL,
				   ruleid  BIGINT( 100) NOT NULL,
				   enabled INTEGER NOT NULL )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    // ---------------------------------------------------------------------------------------------------

    echo "[".__LINE__."]: Migrate table webfilter_ufdbexprassoc\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_ufdbexprassoc` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT ,
				   groupid  BIGINT( 100) NOT NULL,
				   termsgid  BIGINT( 100) NOT NULL,
				   enabled INTEGER NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    // ---------------------------------------------------------------------------------------------------

    echo "[".__LINE__."]: Migrate table webfilters_dtimes_blks\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_dtimes_blks` (
				   `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				    webfilter_id INTEGER NOT NULL,
				  	modeblk INTEGER NOT NULL,
				  	category TEXT NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilters_quotas_blks\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_quotas_blks` (
				  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				    webfilter_id INTEGER NOT NULL,
				  	modeblk INTEGER NOT NULL,
				  	category TEXT NOT NULL)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    $q->QUERY_SQL($sql);

    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilters_rewriteitems\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_rewriteitems` (
				   `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				    ruleid INTEGER NOT NULL,
				  	frompattern TEXT NOT NULL,
				  	topattern TEXT NOT NULL,
				  	enabled INTEGER NOT NULL DEFAULT 1)";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilter_bannedexts\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_bannedexts` (
				  `zmd5` TEXT  UNIQUE,
				  `ext` varchar(10) NOT NULL,
				  `description` TEXT NOT NULL,
				  `enabled` INTEGER NOT NULL DEFAULT '1',
				  `ruleid` INTEGER NOT NULL
				)  ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    // ---------------------------------------------------------------------------------------------------
    echo "[".__LINE__."]: Migrate table webfilter_bannedextsdoms\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilter_bannedextsdoms` (`zmd5` TEXT  UNIQUE,`ext` varchar(10) NOT NULL,`description` TEXT NOT NULL,`enabled` INTEGER NOT NULL DEFAULT '1',`ruleid` INTEGER NOT NULL)  ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;

    }

    $sql="CREATE TABLE IF NOT EXISTS `ufdbunlock` ( `md5` TEXT NOT NULL PRIMARY KEY ,`logintime` INTEGER , `finaltime` INTEGER , `uid` TEXT, `MAC` TEXT, `www` TEXT , `ipaddr` TEXT,details TEXT )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;

    }


    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyFinaltime ON ufdbunlock (finaltime)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS Keyuid ON ufdbunlock (uid)");
    $q->QUERY_SQL("CREATE INDEX IF NOT EXISTS KeyIpaddr ON ufdbunlock (ipaddr)");



    $sql="CREATE TABLE IF NOT EXISTS `webfilters_usersasks` ( `zmd5` TEXT NOT NULL PRIMARY KEY ,`zDate` INTEGER , `ipaddr` TEXT ,`sitename` TEXT ,`info` TEXT,`uid` TEXT  )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $sql="CREATE TABLE IF NOT EXISTS `ufdb_smtp` (
		`zmd5` TEXT NOT NULL PRIMARY KEY,
		`zDate` INTEGER,
		`Subject` TEXT,
		`content` TEXT,
		`main_array` TEXT,
		`URL` TEXT,
		`REASONGIVEN` TEXT,
		`sender` TEXT,
		`retrytime` INTEGER,
		`ticket` INTEGER,
		`SquidGuardIPWeb` TEXT)";




    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    return true;
}

function fill_webfilter_certs($db="webfilter.db"):bool
{
    if ($db==null) {
        $db="webfilter.db";
    }
    $q=new lib_sqlite("/home/artica/SQLITE/$db");
    $file=dirname(__FILE__)."/databases/ufdbcerts";
    if (!is_file($file)) {
        return false;
    }
    $text=unserialize(base64_decode(@file_get_contents($file)));
    $prefix="INSERT OR IGNORE INTO webfilter_certs (`zmd5`,`certname`,`certdata`) VALUES ";
    $f=array();
    foreach ($text as $md5=>$array) {
        $data=mysql_escape_string2($array["DATA"]);
        $name=$array["NAME"];
        if (strtolower($name)<>"default") {
            $name=mysql_escape_string2(base64_decode($array["NAME"]));
        }
        $f[]="('$md5','$name','$data')";
    }

    $q->QUERY_SQL($prefix.@implode(",", $f));

    return true;
}

function proxy_search():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/proxy_search.db");
    @chmod("/home/artica/SQLITE/proxy_search.db", 0644);
    @chown("/home/artica/SQLITE/proxy_search.db", "www-data");

    echo "[".__LINE__."]: Migrate table proxy_search\n";
    $sql="CREATE TABLE IF NOT EXISTS `proxy_search` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`uuid` TEXT NULL,
			`datefrom` TEXT NOT NULL ,
			`timefrom` TEXT NOT NULL ,
			`dateto` TEXT NOT NULL ,
			`timeto` TEXT NOT NULL ,
			`username` TEXT NULL,
		    `ipsrc` TEXT NULL,
		    `ipdest` TEXT NULL,
			`category` TEXT NULL,
			`sitename` TEXT NULL,
			`maxlines` INTEGER NOT NULL DEFAULT 500,
			`squidcode` INTEGER NOT NULL DEFAULT 0,
			`logspath` TEXT NULL,
			`lines` INTEGER NOT NULL DEFAULT 0,
			`size` INTEGER NOT NULL DEFAULT 0,
			`executed` INTEGER NOT NULL DEFAULT 0,
			`percentage` INTEGER NOT NULL DEFAULT 0,
			`enabled` INTEGER NOT NULL DEFAULT '1')";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}
    return true;
}

function proxy_tables():bool{


    $q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
    @chmod("/home/artica/SQLITE/proxy.db", 0644);
    @chown("/home/artica/SQLITE/proxy.db", "www-data");
    proxy_search();

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

    echo "[".__LINE__."]: Migrate table squid_balancers\n";
    $sql="CREATE TABLE IF NOT EXISTS `squid_balancers` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`ipsrc` TEXT NOT NULL ,
			`enabled` INT( 1 ) NOT NULL DEFAULT '1')";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {echo "$q->mysql_error (".__LINE__.")\n$sql\n";}


    echo "[".__LINE__."]: Migrate table webfilters_schedules\n";
    $sql="CREATE TABLE IF NOT EXISTS `webfilters_schedules` (
			`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			`TimeText` VARCHAR( 128 ) NOT NULL ,
			`TimeDescription` VARCHAR( 128 ) ,
			`TaskType` INTEGER ,
			`Params` TEXT,
			`enabled` INTEGER)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
    }
    if (!$q->FIELD_EXISTS("webfilters_schedules", "Params")) {
        $q->QUERY_SQL("ALTER TABLE webfilters_schedules ADD `Params` TEXT");
        if (!$q->ok) {
            echo "****************\n$q->mysql_error\n**********************\n";
        }
    }

    echo "[".__LINE__."]: Migrate table publiccerts\n";
    $sql="CREATE TABLE IF NOT EXISTS `publiccerts` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`zmd5` TEXT UNIQUE,
				`issuer` TEXT,
				`zDate` text NOT NULL,
				`subject`  TEXT NOT NULL,
				`enabled` INTEGER NULL DEFAULT 1,
				`content` TEXT
			 )  ";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo "$q->mysql_error (".__LINE__.")\n$sql\n";
        return false;
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


    echo "[".__LINE__."]: Create table amavisd_tests\n";
    $q->QUERY_SQL("CREATE TABLE IF NOT EXISTS `amavisd_tests` (
			  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `sender` TEXT,
			  `recipients` TEXT,
			  `message` TEXT,
			  `amavisd_results` TEXT,
			  `spamassassin_results` TEXT,
			  `spamassassin_results_header` TEXT,
			  `spamassassin_score` TEXT,
			  `sanlearn` INTEGER,
			  `finish` INTEGER NOT NULL DEFAULT '0',
			  `saved_date` TEXT NOT NULL,
			  `subject` TEXT NOT NULL)");
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

function rpz_database():bool{
    $q = new lib_sqlite("/home/artica/SQLITE/rpz.db");
    $sql="CREATE TABLE IF NOT EXISTS `policies` (
				`ID` INTEGER PRIMARY KEY AUTOINCREMENT,
				`rpzname` TEXT NOT NULL DEFAULT 'policy.rpz',
				`rpztype` INTEGER NOT NULL DEFAULT '0',
				`defpol` TEXT NOT NULL DEFAULT 'Policy.Custom',
				`defcontent` TEXT NOT NULL DEFAULT 'localhost.localdomain',
				`enabled` INTEGER NOT NULL DEFAULT '1',
				`items` INTEGER NOT NULL DEFAULT '0',
				`status` INTEGER NOT NULL DEFAULT '0',
				`lastsaved` INTEGER NOT NULL DEFAULT '0',
				`zOrder` INTEGER NOT NULL DEFAULT '1',
				`rpzurl` TEXT NULL
		)";

    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }

    if(!$q->FIELD_EXISTS("policies","status")){
        $q->QUERY_SQL("ALTER TABLE policies ADD `status` INTEGER NOT NULL DEFAULT '0'");
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

function ntp():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/ntp.db");
    @chmod("/home/artica/SQLITE/ntp.db", 0644);
    @chown("/home/artica/SQLITE/ntp.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS `ntpd_servers` (
          `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
		  `ntp_servers` varchar(255) NOT NULL UNIQUE,
		  `order` INTEGER
		  )";
    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }
    return true;
}

function sys():bool{
    echo "[".__LINE__."]: Migrate table last_boot FROM artica_events\n";
    $q=new lib_sqlite("/home/artica/SQLITE/sys.db");
    @chmod("/home/artica/SQLITE/sys.db", 0644);
    @chown("/home/artica/SQLITE/sys.db", "www-data");

    $sql="CREATE TABLE IF NOT EXISTS `last_boot` (
	`zmd5` TEXT PRIMARY KEY,
	`zDate` text,
	`subject` TEXT,
	`ztime` INTEGER,
	`ztime2` INTEGER ) ";


    $q->QUERY_SQL($sql);
    if (!$q->ok) {
        echo __FUNCTION__." [".__LINE__."]:Fatal: ".$q->mysql_error."\n$sql\n";
        return false;
    }


    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $sql="CREATE TABLE IF NOT EXISTS `history` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`version` text UNIQUE, `updated` INTEGER,`asseen` integer ,`xline` integer)";
    $q->QUERY_SQL($sql);


    $q=new lib_sqlite("/home/artica/SQLITE/link_balancer.db");

    $sql="CREATE TABLE IF NOT EXISTS `events` (
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
    `prio` INTEGER NOT NULL DEFAULT 2,
	`zdate` INTEGER,
	`sent` INTEGER NOT NULL DEFAULT 0,
	`subject` TEXT,
	`content` TEXT,
	`info` TEXT ) ";

    $q->QUERY_SQL($sql);

    $q=new lib_sqlite("/home/artica/SQLITE/clusters_events.db");
    $q->QUERY_SQL($sql);
    return true;
}
