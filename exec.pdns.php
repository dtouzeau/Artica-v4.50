<?php
$GLOBALS["FILESDB"][]="ntp.db";
$GLOBALS["FILESDB"][]="dns.db";
$GLOBALS["FILESDB"][]="postfix.db";
$GLOBALS["FILESDB"][]="spamassassin.db";
$GLOBALS["FILESDB"][]="certificates.db";
$GLOBALS["PGDUMP"][]="miltergreylist_acls";
$GLOBALS["PGDUMP"][]="autowhite";
$GLOBALS["SETTINGS"][]="EnableMilterRegex";
$GLOBALS["SETTINGS"][]="MilterGreyListEnabled";
$GLOBALS["SETTINGS"][]="MimeDefangEnabled";
$GLOBALS["SETTINGS"][]="NTPDEnabled";
$GLOBALS["SETTINGS"][]="NTPDUseSpecifiedServers";
$GLOBALS["SETTINGS"][]="NTPClientDefaultServerList";
$GLOBALS["SETTINGS"][]="CurlUserAgent";
$GLOBALS["SETTINGS"][]="ArticaProxySettings";


if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.ldap.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/ressources/class.pdns.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
include_once(dirname(__FILE__)."/ressources/class.postgres.inc");
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/externals/class.aesCrypt.inc');
$GLOBALS["FORCE"]=false;
$GLOBALS["SHOWKEYS"]=false;
$GLOBALS["OUTPUT"]=True;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}}
if(preg_match("#--showkeys#",implode(" ",$argv))){$GLOBALS["SHOWKEYS"]=true;}
if(preg_match("#--scheduled#",implode(" ",$argv))){$GLOBALS["scheduled"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($argv[1]=="--tldextract"){tldextract();exit;}

if($argv[1]=="--mysql"){checkMysql();exit;}
if($argv[1]=="--dnsseck"){dnsseck();exit;}
if($argv[1]=="--dnssec"){dnsseck();exit;}
if($argv[1]=="--reload"){reload_service();exit;}
if($argv[1]=="--rebuild-database"){rebuild_database();exit;}
if($argv[1]=="--replic-artica"){replic_artica_servers();exit;}
if($argv[1]=="--reconstruct-db"){reconstruct_database();exit;}



if($argv[1]=="--listen-ips"){listen_ips();exit;}
if($argv[1]=="--wizard-on"){wizard_on();exit;}
if($argv[1]=="--reconfigure-all"){reconfigure_all();exit;}
if($argv[1]=="--import"){import_backup($argv[2]);exit;}
if($argv[1]=="--export"){export_backup();exit;}
if($argv[1]=="--export-cluster"){export_cluster();exit;}
if($argv[1]=="--checksum-cluster"){checksum_cluster();exit;}
if($argv[1]=="--verify-zones"){verify_zones();exit;}
if($argv[1]=="--rectify-zone"){rectify_zone($argv[2]);exit;}
if($argv[1]=="--add-record"){add_record();exit;}
if($argv[1]=="--zone-info"){zone_info($argv[2]);exit;}
if($argv[1]=="--cleandb") {clean_database();exit;}
if($argv[1]=="--repair-db"){repair_database();exit;}

echo "Cannot understand {$argv[1]}\n";



function reconfigure_all(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_reconfigure(10,"{checking} MySQL");
	checkMysql();
	build_progress_reconfigure(30,"{checking} {APP_PDNS_RECURSOR}");
	restart_recursor();
	build_progress_reconfigure(50,"{checking} {APP_PDNS}");
	system("$php /usr/share/artica-postfix/exec.pdns_server.php --restart");
	build_progress_reconfigure(100,"{DNS_SERVER} {reconfigure_service} {done}");
}



function rebuild_database($nollop=false){
	$unix=new unix();
	$MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
	echo "Starting......: ".date("H:i:s")." PowerDNS destroy database and recreate it\n";
	$q=new mysql();
	$q->DELETE_DATABASE("powerdns");
	$rm=$unix->find_program("rm");
	if(is_dir("$MYSQL_DATA_DIR/powerdns")){
		echo "Starting......: ".date("H:i:s")." PowerDNS removing $MYSQL_DATA_DIR/powerdns\n";
		shell_exec("$rm -rf $MYSQL_DATA_DIR/powerdns");
	}
	checkMysql($nollop);
	shell_exec("/etc/init.d/pdns restart");
}

function checksum_cluster(){
	
	$unix=new unix();
	$TimeFile="/etc/artica-postfix/pids/pdns.checksum_cluster.time";
	
	
	$PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
	if($PowerDNSEnableClusterMaster==0){return;}
	$PowerDNSEnableClusterMasterMD5=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterMD5"));
	$PowerDNSEnableClusterMasterTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMasterTime"));
	if($PowerDNSEnableClusterMasterTime>0){
		if($unix->file_time_min($TimeFile)<5){return;}
	}
	@unlink($TimeFile);
	@file_put_contents($TimeFile, time());
	$EnablePDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS");
	
	if($EnablePDNS==1){
		$q=new mysql_pdns();
		$f["cryptokeys"]=true;
		$f["domainmetadata"]=true;
		$f["domains"]=true;
		$f["comments"]=true;
		$f["perm_items"]=true;
		$f["perm_templ"]=true;
		$f["perm_templ_items"]=true;
		$f["records"]=true;
		$f["supermasters"]=true;
		$f["tsigkeys"]=true;
		$f["users"]=true;
		$f["zones"]=true;
		$f["zone_templ"]=true;
		$f["zone_templ_records"]=true;
		$f["migrations"]=true;
		
		$c=0;
		foreach ($f as $tablename=>$none){
			$ligne=mysqli_fetch_array($q->QUERY_SQL("CHECKSUM TABLE $tablename"));
			$Checksum=intval($ligne["Checksum"]);
			$c=$c."$Checksum";
		}
	}
		
	
	$q=new postgres_sql();
	foreach ($GLOBALS["PGDUMP"] as $table){
		$ligne=$q->mysqli_fetch_array("SELECT md5(array_agg(md5((t.*)::varchar))::varchar) FROM (SELECT * FROM $table ORDER BY 1) AS t");
		$c=$c.$ligne["md5"];
	}
	
	
	foreach ($GLOBALS["FILESDB"] as $db){
		$Checksum=md5_file("/home/artica/SQLITE/$db");
		$c=$c.$Checksum;
	}
	
	foreach ($GLOBALS["SETTINGS"] as $KeyFileName){
		$MAIN[$KeyFileName]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($KeyFileName);
		
	}
	$c=$c.md5(serialize($MAIN));
	
	
	$md5=md5($c);
	if($PowerDNSEnableClusterMasterTime==0){$PowerDNSEnableClusterMasterMD5="none";}
	if($PowerDNSEnableClusterMasterMD5==$md5){return;}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableClusterMasterMD5", $md5);
	squid_admin_mysql(2, "Database Checksum changes, building cluster package", null,__FILE__,__LINE__);
	
	export_cluster();
	
}

function repair_database_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"pdns.repair.progress");
}

function repair_database(){

    repair_database_progress(10,"{mysql_repair}");
    $q=new mysql();
    $mysql[]="db";
    $mysql[]="user";
    $mysql[]="host";
    $i=1;
    foreach ($mysql as $table){
        $i=$i+10;
        repair_database_progress($i,"{mysql_repair} $table");
        $q->REPAIR_TABLE("mysql",$table);
    }
    $TABLES=$q->LIST_TABLES_POWERDNS();
    foreach ($TABLES as $table=>$none){
        $i=$i+10;
        if($i>90){$i=90;}
        repair_database_progress($i,"{mysql_repair} $none");
        $q->REPAIR_TABLE("powerdns",$table);
    }

    //ALTER TABLE domains CONVERT TO CHARACTER SET latin1;

    repair_database_progress(95,"{mysql_repair}");
    if(!checkMysql(true)){
        repair_database_progress(100,"{mysql_repair} {failed}");
        return false;
    }
    repair_database_progress(100,"{mysql_repair} {success}");
    return true;
}

function clean_database(){

    $q=new mysql_pdns();
    $sql="SELECT * FROM records";
    $results = $q->QUERY_SQL($sql);
    $logs=array();
    $rows=mysqli_num_rows($results);
    echo "$rows item(s)\n";
    while ($ligne = mysqli_fetch_assoc($results)) {
        $id=$ligne["id"];
        $type=trim($ligne["type"]);
        $name=$ligne["name"];
        $content=$ligne["content"];
        if($type==null){
            $logs[]="$id $name $content (type is null)";
            $q->QUERY_SQL("DELETE FROM records WHERE id=$id");
        }

    }

    if(count($logs)>0){
        squid_admin_mysql("{APP_PDNS}: ".count($logs)." deleted records",@implode("\n",$logs),__FILE__,__LINE__);
    }

}

function checkMysql($nollop=false){
	$unix=new unix();
	
	if(!$GLOBALS["FORCE"]){
		$timefile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".time";
		if($unix->file_time_min($timefile)<1){
			echo "Starting......: ".date("H:i:s")." PowerDNS need at least 1mn, aborting\n";
			build_progress_repair(110,"{failed}");
			return false;
		}
	}

	@unlink($timefile);
	@file_put_contents($timefile, time());
	
	$passwdcmdline=null;
	$password=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSQLPassword");
	if($password==null){$password="powerdns";}
	
	$q=new mysql();
	$q->QUERY_SQL("SET GLOBAL innodb_default_row_format=DYNAMIC;");
	$q->SET_VARIABLES("innodb_default_row_format", "DYNAMIC");
	
	$q->CREATE_DATABASE("powerdns");
	$q->PRIVILEGES("powerdns", $password, "powerdns");
	
	
	$q=new mysql_pdns();
	
	if(!$q->TestingConnection(true)){
		build_progress_repair(110,"{failed}");
		echo "Starting......: ".date("H:i:s")." checkMysql:: $q->mysql_error\n";
		echo "Starting......: ".date("H:i:s")." checkMysql:: PowerDNS creating, MySQL seems not ready..\n";
		return false;
	}
	
	build_progress_repair(30,"Build database");
	if(!$q->DATABASE_EXISTS("powerdns")){
		echo "Starting......: ".date("H:i:s")." checkMysql:: PowerDNS creating 'powerdns' database\n";
		if(!$q->CREATE_DATABASE("powerdns")){
			build_progress_repair(110,"{failed}");
			echo "Starting......: ".date("H:i:s")." checkMysql:: PowerDNS creating 'powerdns' database failed\n"; 
			return false;
		}
	}

	echo "Starting......: ".date("H:i:s")." checkMysql::  PowerDNS 'powerdns' database OK\n";

$f["cryptokeys"]=true;
$f["domainmetadata"]=true;
$f["domains"]=true;
$f["comments"]=true;
$f["perm_items"]=true;
$f["perm_templ"]=true;
$f["perm_templ_items"]=true;
$f["records"]=true;
$f["supermasters"]=true;
$f["tsigkeys"]=true;
$f["users"]=true;
$f["zones"]=true;
$f["zone_templ"]=true;
$f["zone_templ_records"]=true;
$f["migrations"]=true;



$resultTables=true;

foreach ($f as $tablename=>$none){
	if(!$q->TABLE_EXISTS($tablename, "powerdns")){echo "Starting......: ".date("H:i:s")." PowerDNS Table `$tablename` failed...\n";$resultTables=false;continue;}
	echo "Starting......: ".date("H:i:s")." checkMysql:: PowerDNS Table `$tablename` OK...\n";
}























    $resultTables=true;
    foreach ($TablesList  as $tablename=>$none){
        build_progress_repair(50,"$tablename");
        if(!$q->TABLE_EXISTS($tablename, "powerdns")){
            echo "Starting......: ".date("H:i:s")." PowerDNS Table `$tablename` failed...\n";
            $resultTables=false;
        }

    }




    if($resultTables){
        echo "Starting......: ".date("H:i:s")." PowerDNS Success...\n";
        build_progress_repair(100,"{success}");
        return true;
    }
    build_progress_repair(110,"{failed}");
    echo "Starting......: ".date("H:i:s")." PowerDNS Mysql done...\n";

    return false;
}

function build_progress_recursor($pourc,$text){
	$echotext=$text;
	echo "Starting......: Recursor: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/recusor.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_repair($pourc,$text){
	$echotext=$text;
	echo "Starting......: Repair: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/pdns.repair.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_reconfigure($pourc,$text){
	$echotext=$text;
	echo "Starting......: Reconfigure: ".date("H:i:s")." {$pourc}% $echotext\n";
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	$cachefile="/usr/share/artica-postfix/ressources/logs/pdns.reconfigure.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	if($GLOBALS["VERBOSE"]){echo "Save $cachefile {$pourc}%\n";}
	file_put_contents($cachefile, serialize($array));
	chmod($cachefile,0755);
}
function build_progress_import($pourc,$text){
    cluster_events("$text ({$pourc}%)",0);
	$echotext=$text;
	echo "Starting......: Reconfigure: ".date("H:i:s")." {$pourc}% $echotext\n";
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	$cachefile="/usr/share/artica-postfix/ressources/logs/pdns.import.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	if($GLOBALS["VERBOSE"]){echo "Save $cachefile {$pourc}%\n";}
	file_put_contents($cachefile, serialize($array));
	chmod($cachefile,0755);
}
function build_progress_dnssec($pourc,$text){
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	$cachefile="/usr/share/artica-postfix/ressources/logs/pdns.dnssec.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	file_put_contents($cachefile, serialize($array));
	chmod($cachefile,0755);
}
function export_cluster(){
	$unix=new unix();
	$PowerDNSEnableClusterMaster=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterMaster"));
	if($PowerDNSEnableClusterMaster==0){export_cluster_remove_files();return;}
	
	if(!is_dir("/home/artica/PowerDNS/Cluster/storage")){@mkdir("/home/artica/PowerDNS/Cluster/storage",0755,true);}
	@file_put_contents("/home/artica/PowerDNS/Cluster/storage/index.php","<?php header(\"location: index.html\"); ?>");
	@file_put_contents("/home/artica/PowerDNS/Cluster/storage/index.html", "<html><head></head><body><H1>This is the storage section for PowerCluster system, keep out !</H1></body></html>");
	@chmod("/home/artica/PowerDNS/Cluster/storage/index.html",0644);
	@chmod("/home/artica/PowerDNS/Cluster/storage/index.php",0755);
	
	$EnablePDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS");

	
	if($EnablePDNS==1){
		$mysqldump=$unix->find_program("mysqldump");
	
		if(!is_file($mysqldump)){
			squid_admin_mysql(0, "Cannot export data MySQLDump missing", null,__FILE__,__LINE__);
			return;
		}
		
		$IGNORES[]="perm_templ";
		$IGNORES[]="comments";
		$IGNORES[]="users";
		$IGNORES[]="perm_templ_items";
		$IGNORES[]="zones";
		$IGNORES[]="zone_templ";
		$IGNORES[]="zone_templ_records";
		$IGNORES[]="records_zone_templ";
		$IGNORES[]="migrations";
		
		foreach ($IGNORES as $table_ignore){
			$tblign[]="--ignore-table=powerdns.$table_ignore";
			
		}
		
		$q=new mysql_pdns();
		$MYSQL_CMDLINES=$q->MYSQL_CMDLINES;
		$cmdline="$mysqldump ".@implode(" ", $tblign) ." --add-drop-table --skip-comments --insert-ignore $MYSQL_CMDLINES powerdns > /home/artica/PowerDNS/Cluster/storage/powerdns.sql 2>&1";
		exec($cmdline,$results);
		if($unix->MYSQL_BIN_PARSE_ERROR($results)){
			squid_admin_mysql(0, "Cluster: Error while exporting powerdns database", @implode("\n", $results),__FILE__,__LINE__);
			echo $unix->mysql_error."\n";
			@unlink("/home/artica/PowerDNS/Cluster/storage/powerdns.sql");
			return;
		}
		if($unix->MYSQL_BIN_PARSE_FILE("/home/artica/PowerDNS/Cluster/storage/powerdns.sql")){
			squid_admin_mysql(0, "Cluster: Error while exporting powerdns database", $unix->mysql_error,__FILE__,__LINE__);
			echo $unix->mysql_error."\n";
			@unlink("/home/artica/PowerDNS/Cluster/storage/powerdns.sql");
			return;
		}
	
		$unix->compress("/home/artica/PowerDNS/Cluster/storage/powerdns.sql", "/home/artica/PowerDNS/Cluster/storage/powerdns.gz");
		@unlink("/home/artica/PowerDNS/Cluster/storage/powerdns.sql");
	}
	
	foreach ($GLOBALS["FILESDB"] as $db){
		if(is_file("/home/artica/PowerDNS/Cluster/storage/$db")){
			@unlink("/home/artica/PowerDNS/Cluster/storage/$db");
		}
		@copy("/home/artica/SQLITE/$db", "/home/artica/PowerDNS/Cluster/storage/$db");
	}
	
	
	foreach ($GLOBALS["PGDUMP"] as $pgtables){
		$cmd="/usr/local/ArticaStats/bin/pg_dump --file=/home/artica/PowerDNS/Cluster/storage/$pgtables.pgsql --inserts --data-only --format=custom --table=$pgtables -h /var/run/ArticaStats -U ArticaStats proxydb";
		shell_exec($cmd);
	}
	
	foreach ($GLOBALS["SETTINGS"] as $KeyFileName){
		$MAIN[$KeyFileName]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO($KeyFileName);
	
	}
	
	@file_put_contents("/home/artica/PowerDNS/Cluster/storage/settings.conf", serialize($MAIN));
	
	
	$tar=$unix->find_program("tar");
	chdir("/home/artica/PowerDNS/Cluster/storage");
	system("cd /home/artica/PowerDNS/Cluster/storage");
	system("$tar czf /home/artica/PowerDNS/Cluster/storage/powerdns.tar.gz *");
	export_cluster_remove_files();
	
	$PowerDNSClusterPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterPassword"));
	$crypt = new AESCrypt($PowerDNSClusterPassword);
	$date = date("Y-m-d");
	$time = date("H:i:s");
	$crypt->setExtText(array( $crypt::CREATED_DATE=>$date, $crypt::CREATED_TIME=>$time ) );
	$data=file_get_contents("/home/artica/PowerDNS/Cluster/storage/powerdns.tar.gz");
	@unlink("/home/artica/PowerDNS/Cluster/storage/powerdns.tar.gz");
	file_put_contents("/home/artica/PowerDNS/Cluster/storage/powerdns.aes", $crypt->encrypt( $data) );
	
	$md5=md5_file("/home/artica/PowerDNS/Cluster/storage/powerdns.aes");
	$ARRAY["MD5"]=$md5;
	$ARRAY["SERIAL"]=time();
	$crypt = new AESCrypt($PowerDNSClusterPassword);
	$date = date("Y-m-d");
	$time = date("H:i:s");
	$crypt->setExtText(array( $crypt::CREATED_DATE=>$date, $crypt::CREATED_TIME=>$time ) );
	file_put_contents("/home/artica/PowerDNS/Cluster/storage/index.aes", $crypt->encrypt( serialize($ARRAY)) );
	squid_admin_mysql(2, "Cluster: Success creating cluster package",__FILE__,__LINE__);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableClusterMasterTime",time());
		
}

function export_cluster_remove_files(){
	
	$f[]="/home/artica/PowerDNS/Cluster/storage/artica_backup.sql";
	$f[]="/home/artica/PowerDNS/Cluster/storage/artica_backup.gz";
	$f[]="/home/artica/PowerDNS/Cluster/storage/powerdns.sql";
	$f[]="/home/artica/PowerDNS/Cluster/storage/powerdns.gz";
	$f[]="/home/artica/PowerDNS/Cluster/storage/powerdns.aes";
	
	foreach ($GLOBALS["FILESDB"] as $db){$f[]="/home/artica/PowerDNS/Cluster/storage/$db";}
	foreach ($GLOBALS["PGDUMP"] as $pgtables){$f[]="/home/artica/PowerDNS/Cluster/storage/$pgtables.pgsql";}
	
	
	
	foreach ($f as $path){
		if(is_file($path)){@unlink($path);}
	}
}

function reconstruct_database():bool{
    $unix=new unix();
    $MYSQL_DATA_DIR=$unix->MYSQL_DATA_DIR();
    $mysqldump=$unix->find_program("mysqldump");
    build_progress_import(10,"{exporting}");
    if(!is_file($mysqldump)){
        build_progress_import(110,"{exporting} {failed} no mysqldump");
        return false;
    }
    $rm=$unix->find_program("rm");
    $q=new mysql_pdns();
    $MYSQL_CMDLINES=$q->MYSQL_CMDLINES;
    build_progress_import(20,"{exporting} {database}");
    $DIRTEMP=$unix->TEMP_DIR();
    $backup_file="$DIRTEMP/pdns_backup_rep/powerdns.sql";
    if(is_dir("$DIRTEMP/pdns_backup_rep")){shell_exec("$rm -rf $DIRTEMP/pdns_backup_rep");}
    @mkdir("$DIRTEMP/pdns_backup_rep",0777);

    $cmdline="$mysqldump --no-create-info --no-tablespaces --no-create-db $MYSQL_CMDLINES powerdns >$backup_file 2>&1";
    exec($cmdline,$results);

    if($unix->MYSQL_BIN_PARSE_FILE($backup_file)){
        echo $unix->mysql_error."\n****\n$cmdline\n****\n";
        build_progress_import(110,"{failed}");
        shell_exec("$rm -rf $DIRTEMP/pdns_backup_rep");
        return false;
    }
    if($unix->MYSQL_BIN_PARSE_ERROR($results)){
        echo $unix->mysql_error."\n";
        build_progress_import(110,"{failed}");
        shell_exec("$rm -rf $DIRTEMP/pdns_backup");
        return false;
    }

    build_progress_import(50,"{removing_databases}");
    $q=new mysql();
    $q->DELETE_DATABASE("powerdns");
    $rm=$unix->find_program("rm");
    if(is_dir("$MYSQL_DATA_DIR/powerdns")){
        echo "Starting......: ".date("H:i:s")." PowerDNS removing $MYSQL_DATA_DIR/powerdns\n";
        shell_exec("$rm -rf $MYSQL_DATA_DIR/powerdns");
    }
    if(!checkMysql(true)){
        build_progress_import(110,"{configuring} {database} {failed}");
        return false;
    }

    $mysql=$unix->find_program("mysql");
    build_progress_import(70,"{restoring}...");
    $cmdline="$mysql --force $MYSQL_CMDLINES powerdns < $backup_file 2>&1";
    exec($cmdline,$results);

    shell_exec("$rm -rf $DIRTEMP/pdns_backup_rep");
    if($unix->MYSQL_BIN_PARSE_ERROR($results)){
        echo $unix->mysql_error."\n";
        import_backup_progress(110,"{failed}");
        return false;
    }
    import_backup_progress(110,"{success}");
    return true;
}


function export_backup(){
	$unix=new unix();
	$GLOBALS["OUTPUT"]=true;
	$rm=$unix->find_program("rm");
	$mysqldump=$unix->find_program("mysqldump");
	build_progress_import(10,"{exporting}");
	if(!is_file($mysqldump)){
		build_progress_import(110,"{exporting} {failed} no mysqldump");
		return;
	}
	@unlink(PROGRESS_DIR."/dns-backup.tar.gz");
	$DIRTEMP=$unix->TEMP_DIR();
	if(is_dir("$DIRTEMP/pdns_backup")){shell_exec("$rm -rf $DIRTEMP/pdns_backup");}
	@mkdir("$DIRTEMP/pdns_backup",0777);
	$q=new mysql_pdns();
	$MYSQL_CMDLINES=$q->MYSQL_CMDLINES;
	build_progress_import(20,"{exporting} {database}");

	$cmdline="$mysqldump $MYSQL_CMDLINES powerdns > $DIRTEMP/pdns_backup/powerdns.sql 2>&1";
    if($GLOBALS["VERBOSE"]){echo $cmdline."\n";}
	exec($cmdline,$results);
	
	if($unix->MYSQL_BIN_PARSE_FILE("$DIRTEMP/pdns_backup/powerdns.sql")){
		echo $unix->mysql_error."\n****\n$cmdline\n****\n";
		build_progress_import(110,"{failed}");
		shell_exec("$rm -rf $DIRTEMP/pdns_backup");
		return;
	}
	
	if($unix->MYSQL_BIN_PARSE_ERROR($results)){
			echo $unix->mysql_error."\n";
			build_progress_import(110,"{failed}");
			shell_exec("$rm -rf $DIRTEMP/pdns_backup");
			return;
	}
	build_progress_import(80,"{compressing} {database}");
	@copy("/home/artica/SQLITE/dns.db", "$DIRTEMP/pdns_backup/dns.db");
	
	
	chdir("$DIRTEMP/pdns_backup");
	system("cd $DIRTEMP/pdns_backup");
	$tar=$unix->find_program("tar");
	system("$tar -czf /usr/share/artica-postfix/ressources/logs/web/dns-backup.tar.gz *");
	@chmod(PROGRESS_DIR."/dns-backup.tar.gz", 0755);
	shell_exec("$rm -rf $DIRTEMP/pdns_backup");
	build_progress_import(100,"{compressing} {done}");
	if($GLOBALS["scheduled"]){
		$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));;
		if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
		$year=date("Y");
		$month=date("m");
		$day=date("d");
		@mkdir("$BackupMaxDaysDir/proxy/$year/$month/$day",0755,true);
		@copy(PROGRESS_DIR."/dns-backup.tar.gz", "$BackupMaxDaysDir/proxy/$year/$month/$day/dns-backup.tar.gz");
		@unlink(PROGRESS_DIR."/dns-backup.tar.gz");
	}
}





function cluster_events($text=null,$line=0){
    if($GLOBALS["VERBOSE"]){echo "$text\n";}
    $logFile="/var/log/artica-cluster.log";

   if (is_file($logFile)) {
        $size=filesize($logFile);
        if($size>1000000){@unlink($logFile);}
    }
    $logFile=str_replace("//","/",$logFile);
    $f = @fopen($logFile, 'a');
    $date=date("Y-m-d H:i:s");
    @fwrite($f, "[$date]: $text\n");
    @fclose($f);
}




function dnsseck(){
	
	$unix=new unix();
	
	$password=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PDNSQLPassword");
	if($password==null){$password="powerdns";}
	
	$q=new mysql();
	build_progress_dnssec(1,"Check MySQL password...");
	$q->PRIVILEGES("powerdns", $password, "powerdns");
	
	
	$pdnsutil=$unix->find_program("pdnsutil");
	if(!is_file($pdnsutil)){build_progress_dnssec(110,"pdnsutil no such file");return;}
	
	$PowerDNSDNSSEC=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSDNSSEC"));
	$MAIN=MyLocalDomains();
	
	$CountOfDomains=count($MAIN);
	build_progress_dnssec(2,"$CountOfDomains domains");
	
	
	$i=0;
	foreach ($MAIN as $domain=>$domain_id){
		echo "Checking domain $domain ($domain_id)\n";
		$i++;
		$prc=round(($i/$CountOfDomains)*100);
		if($prc<3){$prc=3;}
		if($prc>80){$prc=80;}
		build_progress_dnssec($prc,"$domain");
		system("$pdnsutil rectify-zone $domain");
		
		if($PowerDNSDNSSEC==1){
			shell_exec("$pdnsutil secure-zone $domain");
		}else{
			shell_exec("$pdnsutil disable-dnssec $domain");
		}
	}
	
	
	if($PowerDNSDNSSEC==1){shell_exec("$pdnsutil rectify-all-zones");}
	build_progress_dnssec(80,"Verify zones....");
	verify_zones();
	build_progress_dnssec(100,"{done}");

}
function shell_exec2($cmd){
	if($GLOBALS["VERBOSE"]){echo "Starting......: ".date("H:i:s")." PowerDNS Execute `$cmd`\n";}
	shell_exec($cmd);
	
}






function reload_service(){
	$sock=new sockets();
	$DisablePowerDnsManagement=$sock->GET_INFO("DisablePowerDnsManagement");
	$EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
	if(!is_numeric($DisablePowerDnsManagement)){$DisablePowerDnsManagement=0;}
	if(!is_numeric($EnablePDNS)){$EnablePDNS=0;}

	$unix=new unix();
	$kill=$unix->find_program("kill");
	$pdns_server_bin=$unix->find_program("pdns_server");
	$pdns_recursor_bin=$unix->find_program("pdns_recursor");
	if($DisablePowerDnsManagement==1){echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: management by artica is disabled\n";return;}
	if($EnablePDNS==0){echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: is disabled EnablePDNS=$EnablePDNS\n";return;}
	if(!is_file($pdns_server_bin)){echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: reloading pdns_server no such binary\n";return;}
		
	if(is_file($pdns_recursor_bin)){
		$recursor_pid=$unix->PIDOF($pdns_recursor_bin);
		if($unix->process_exists("$recursor_pid")){
			echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: recursor pid $recursor_pid\n";
			shell_exec("$kill -HUP $recursor_pid");	
		}else{
			echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: recursor not running failed\n";
		}
	}
	
	$pdns_server_pid=$unix->PIDOF($pdns_server_bin);
	if($unix->process_exists("$pdns_server_pid")){
		echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: reloading pdns_server pid $pdns_server_pid\n";
		shell_exec("$kill -HUP $pdns_server_pid");	
	}else{
			echo "Starting......: ".date("H:i:s")." PowerDNS [reload]: pdns_server not running failed\n";
		}	
}


function create_rrd(){
$f[]="rrdtool create pdns_recursor.rrd -s 60";
$f[]="DS:questions:COUNTER:600:0:100000";
$f[]="DS:tcp-questions:COUNTER:600:0:100000";
$f[]="DS:cache-entries:GAUGE:600:0:U";
$f[]="DS:packetcache-entries:GAUGE:600:0:U";
$f[]="DS:throttle-entries:GAUGE:600:0:U";
$f[]="DS:concurrent-queries:GAUGE:600:0:50000";
$f[]="DS:noerror-answers:COUNTER:600:0:100000";
$f[]="DS:nxdomain-answers:COUNTER:600:0:100000";
$f[]="DS:servfail-answers:COUNTER:600:0:100000";
$f[]="DS:tcp-outqueries:COUNTER:600:0:100000";
$f[]="DS:outgoing-timeouts:COUNTER:600:0:100000";
$f[]="DS:throttled-out:COUNTER:600:0:100000";
$f[]="DS:nsspeeds-entries:GAUGE:600:0:U";
$f[]="DS:negcache-entries:GAUGE:600:0:U";
$f[]="DS:all-outqueries:COUNTER:600:0:100000";
$f[]="DS:cache-hits:COUNTER:600:0:100000";
$f[]="DS:cache-misses:COUNTER:600:0:100000";
$f[]="DS:packetcache-hits:COUNTER:600:0:100000";
$f[]="DS:packetcache-misses:COUNTER:600:0:100000";
$f[]="DS:answers0-1:COUNTER:600:0:100000";
$f[]="DS:answers1-10:COUNTER:600:0:100000";
$f[]="DS:answers10-100:COUNTER:600:0:100000";
$f[]="DS:answers100-1000:COUNTER:600:0:100000";
$f[]="DS:answers-slow:COUNTER:600:0:100000";
$f[]="DS:udp-overruns:COUNTER:600:0:100000";
$f[]="DS:qa-latency:GAUGE:600:0:10000000";
$f[]="DS:user-msec:COUNTER:600:0:64000";
$f[]="DS:uptime:GAUGE:600:0:U";
$f[]="DS:unexpected-packets:COUNTER:600:0:1000000";
$f[]="DS:resource-limits:COUNTER:600:0:1000000";
$f[]="DS:over-capacity-drops:COUNTER:600:0:1000000";
$f[]="DS:client-parse-errors:COUNTER:600:0:1000000";
$f[]="DS:server-parse-errors:COUNTER:600:0:1000000";
$f[]="DS:unauthorized-udp:COUNTER:600:0:1000000";
$f[]="DS:unauthorized-tcp:COUNTER:600:0:1000000";
$f[]="DS:sys-msec:COUNTER:600:0:6400";
$f[]="RRA:AVERAGE:0.5:1:9600 ";
$f[]="RRA:AVERAGE:0.5:4:9600";
$f[]="RRA:AVERAGE:0.5:24:6000 ";
$f[]="RRA:MAX:0.5:1:9600 ";
$f[]="RRA:MAX:0.5:4:9600";
$f[]="RRA:MAX:0.5:24:6000";
	
$f=array();
$f[]="#!/usr/bin/env bash";
$f[]="SOCKETDIR=/var/run/";
$f[]="TSTAMP=\$(date +%s)";
$f[]="";
$f[]="OS=`uname`";
$f[]="if [ \"\$OS\" == \"Linux\" ]";
$f[]="then";
$f[]="#    echo \"Using Linux netstat directive\"";
$f[]="    NETSTAT_GREP=\"packet receive error\"";
$f[]="elif [ \"\$OS\" == \"FreeBSD\" ]";
$f[]="then";
$f[]="#    echo \"Using FreeBSD netstat directive\"";
$f[]="    NETSTAT_GREP=\"dropped due to full socket buffers\"";
$f[]="else";
$f[]="    echo \"Unsupported OS found, please report to the PowerDNS team.\"";
$f[]="    exit 1";
$f[]="fi";
$f[]="";
$f[]="";
$f[]="VARIABLES=\"questions                    \ ";
$f[]="           tcp-questions                \ ";
$f[]="           cache-entries                \ ";
$f[]="           packetcache-entries          \ ";
$f[]="           concurrent-queries           \ ";
$f[]="	   nxdomain-answers             \ ";
$f[]="           noerror-answers              \ ";
$f[]="	   servfail-answers             \ ";
$f[]="           tcp-outqueries               \ ";
$f[]="	   outgoing-timeouts            \ ";
$f[]="           nsspeeds-entries             \ ";
$f[]="           negcache-entries             \ ";
$f[]="           all-outqueries               \ ";
$f[]="           throttled-out                \ ";
$f[]="	   packetcache-hits             \ ";
$f[]="           packetcache-misses           \ ";
$f[]="	   cache-hits                   \ ";
$f[]="           cache-misses                 \ ";
$f[]="           answers0-1                   \ ";
$f[]="           answers1-10                  \ ";
$f[]="           answers10-100                \ ";
$f[]="           answers100-1000              \ ";
$f[]="           answers-slow                 \ ";
$f[]=" 	   qa-latency                   \ ";
$f[]="           throttle-entries             \ ";
$f[]="           sys-msec user-msec           \ ";
$f[]="           unauthorized-udp             \ ";
$f[]="           unauthorized-tcp             \ ";
$f[]="           client-parse-errors          \ ";
$f[]="	   server-parse-errors          \ ";
$f[]="           uptime unexpected-packets    \ ";
$f[]="           resource-limits              \ ";
$f[]="           over-capacity-drops\"";
$f[]="";
$f[]="UVARIABLES=\$(echo \$VARIABLES | tr '[a-z]' '[A-Z]' | tr - _ )";
$f[]="";
$f[]="rec_control --socket-dir=\$SOCKETDIR  GET \$VARIABLES |";
$f[]="(";
$f[]="  for a in \$UVARIABLES";
$f[]="  do";
$f[]="	  read \$a";
$f[]="  done";
$f[]="  rrdtool update pdns_recursor.rrd  \ ";
$f[]="	-t \"udp-overruns:\"\$(for a in \$VARIABLES ";
$f[]="	do";
$f[]="		echo -n \$a:";
$f[]="	done | sed 's/:\$//' ) \ ";
$f[]="\$TSTAMP\$(";
$f[]="	echo -n : ";
$f[]="	netstat -s | grep \"\$NETSTAT_GREP\" | awk '{printf \$1}' ";
$f[]="	for a in \$UVARIABLES";
$f[]="	do";
$f[]="		echo -n :\${!a}";
$f[]="	done";
$f[]="	)";
$f[]=")";
$f[]="";	
$f=array();
$f[]="#!/bin/bash";
$f[]="WWWPREFIX=. ";
$f[]="WSIZE=800";
$f[]="HSIZE=250";
$f[]="";
$f[]="# only recent rrds offer slope-mode:";
$f[]="GRAPHOPTS=--slope-mode";
$f[]="";
$f[]="function makeGraphs()";
$f[]="{";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/questions-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Questions and answers per second\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:questions=pdns_recursor.rrd:questions:AVERAGE  \ ";
$f[]="        DEF:nxdomainanswers=pdns_recursor.rrd:nxdomain-answers:AVERAGE \ ";
$f[]="        DEF:noerroranswers=pdns_recursor.rrd:noerror-answers:AVERAGE \ ";
$f[]="        DEF:servfailanswers=pdns_recursor.rrd:servfail-answers:AVERAGE \ ";
$f[]="        LINE1:questions#0000ff:\"questions/s\"\ ";
$f[]="        AREA:noerroranswers#00ff00:\"noerror answers/s\"  \ ";
$f[]="        STACK:nxdomainanswers#ffa500:\"nxdomain answers/s\"\ ";
$f[]="        STACK:servfailanswers#ff0000:\"servfail answers/s\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/tcp-questions-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"TCP questions and answers per second, unauthorized packets/s\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:tcpquestions=pdns_recursor.rrd:tcp-questions:AVERAGE  \ ";
$f[]="	DEF:unauthudp=pdns_recursor.rrd:unauthorized-udp:AVERAGE  \  ";
$f[]="	DEF:unauthtcp=pdns_recursor.rrd:unauthorized-tcp:AVERAGE  \ ";
$f[]="        LINE1:tcpquestions#0000ff:\"tcp questions/s\" \ ";
$f[]="	LINE1:unauthudp#ff0000:\"udp unauth/s\"  \ ";
$f[]="        LINE1:unauthtcp#00ff00:\"tcp unauth/s\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/packet-errors-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Packet errors per second\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:clientparseerrors=pdns_recursor.rrd:client-parse-errors:AVERAGE  \  ";
$f[]="	DEF:serverparseerrors=pdns_recursor.rrd:server-parse-errors:AVERAGE  \ ";
$f[]="	DEF:unexpected=pdns_recursor.rrd:unexpected-packets:AVERAGE  \ ";
$f[]="	DEF:udpoverruns=pdns_recursor.rrd:udp-overruns:AVERAGE  \ ";
$f[]="        LINE1:clientparseerrors#0000ff:\"bad packets from clients\" \ ";
$f[]="        LINE1:serverparseerrors#00ff00:\"bad packets from servers\" \ ";
$f[]="        LINE1:unexpected#ff0000:\"unexpected packets from servers\" \ ";
$f[]="        LINE1:udpoverruns#ff00ff:\"udp overruns from remotes\"       ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/limits-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Limitations per second\" \ ";
$f[]="	-v \"events\" \ ";
$f[]="	DEF:resourcelimits=pdns_recursor.rrd:resource-limits:AVERAGE  \ ";
$f[]="	DEF:overcapacities=pdns_recursor.rrd:over-capacity-drops:AVERAGE  \ ";
$f[]="        LINE1:resourcelimits#ff0000:\"outqueries dropped because of resource limits\" \ ";
$f[]="        LINE1:overcapacities#0000ff:\"questions dropped because of mthread limit\"      ";
$f[]="";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/latencies-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Questions answered within latency\" \ ";
$f[]="	-v \"questions\" \ ";
$f[]="	DEF:questions=pdns_recursor.rrd:questions:AVERAGE  \ ";
$f[]="        DEF:answers00=pdns_recursor.rrd:packetcache-hits:AVERAGE \ ";
$f[]="        DEF:answers01=pdns_recursor.rrd:answers0-1:AVERAGE \ ";
$f[]="        DEF:answers110=pdns_recursor.rrd:answers1-10:AVERAGE \ ";
$f[]="        DEF:answers10100=pdns_recursor.rrd:answers10-100:AVERAGE \ ";
$f[]="        DEF:answers1001000=pdns_recursor.rrd:answers100-1000:AVERAGE \ ";
$f[]="        DEF:answersslow=pdns_recursor.rrd:answers-slow:AVERAGE \ ";
$f[]="        LINE1:questions#0000ff:\"questions/s\" \ ";
$f[]="        AREA:answers00#00ff00:\"<<1 ms\" \ ";
$f[]="        STACK:answers01#00fff0:\"<1 ms\" \ ";
$f[]="        STACK:answers110#0000ff:\"<10 ms\" \ ";
$f[]="        STACK:answers10100#ff9900:\"<100 ms\" \ ";
$f[]="        STACK:answers1001000#ffff00:\"<1000 ms\" \ ";
$f[]="        STACK:answersslow#ff0000:\">1000 ms\"       ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/qoutq-\$2.png -w \$WSIZE -h \$HSIZE -l 0 \ ";
$f[]="	-t \"Questions/outqueries per second\" \ ";
$f[]="	-v \"packets\" \ ";
$f[]="	DEF:questions=pdns_recursor.rrd:questions:AVERAGE  \ ";
$f[]="        DEF:alloutqueries=pdns_recursor.rrd:all-outqueries:AVERAGE \ ";
$f[]="        LINE1:questions#ff0000:\"questions/s\"\ ";
$f[]="        LINE1:alloutqueries#00ff00:\"outqueries/s\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/qa-latency-\$2.png -w \$WSIZE -h \$HSIZE -l 0 \ ";
$f[]="	-t \"Questions/answer latency in milliseconds\" \ ";
$f[]="	-v \"msec\" \ ";
$f[]="	DEF:qalatency=pdns_recursor.rrd:qa-latency:AVERAGE  \ ";
$f[]="	CDEF:mqalatency=qalatency,1000,/ \ ";
$f[]="        LINE1:mqalatency#ff0000:\"questions/s\" ";
$f[]="";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/timeouts-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Outqueries/timeouts per second\" \ ";
$f[]="	-v \"events\" \ ";
$f[]="	DEF:alloutqueries=pdns_recursor.rrd:all-outqueries:AVERAGE  \ ";
$f[]="        DEF:outgoingtimeouts=pdns_recursor.rrd:outgoing-timeouts:AVERAGE \ ";
$f[]="        DEF:throttledout=pdns_recursor.rrd:throttled-out:AVERAGE \ ";
$f[]="        LINE1:alloutqueries#ff0000:\"outqueries/s\"\ ";
$f[]="        LINE1:outgoingtimeouts#00ff00:\"outgoing timeouts/s\"\ ";
$f[]="        LINE1:throttledout#0000ff:\"throttled outqueries/s\" ";
$f[]="	";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/caches-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Cache sizes\" \ ";
$f[]="	-v \"entries\" \  ";
$f[]="	DEF:cacheentries=pdns_recursor.rrd:cache-entries:AVERAGE  \ ";
$f[]="	DEF:packetcacheentries=pdns_recursor.rrd:packetcache-entries:AVERAGE  \ ";
$f[]="	DEF:negcacheentries=pdns_recursor.rrd:negcache-entries:AVERAGE  \ ";
$f[]="	DEF:nsspeedsentries=pdns_recursor.rrd:nsspeeds-entries:AVERAGE  \ ";
$f[]="	DEF:throttleentries=pdns_recursor.rrd:throttle-entries:AVERAGE  \ ";
$f[]="        LINE1:cacheentries#ff0000:\"cache entries\" \ ";
$f[]="        LINE1:packetcacheentries#ffff00:\"packet cache entries\" \ ";
$f[]="        LINE1:negcacheentries#0000ff:\"negative cache entries\" \ ";
$f[]="        LINE1:nsspeedsentries#00ff00:\"NS speeds entries\" \ ";
$f[]="        LINE1:throttleentries#00fff0:\"throttle map entries\" ";
$f[]="        ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/caches2-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-t \"Cache sizes\" \ ";
$f[]="	-v \"entries\" \ ";
$f[]="	DEF:negcacheentries=pdns_recursor.rrd:negcache-entries:AVERAGE  \ ";
$f[]="	DEF:nsspeedsentries=pdns_recursor.rrd:nsspeeds-entries:AVERAGE  \ ";
$f[]="	DEF:throttleentries=pdns_recursor.rrd:throttle-entries:AVERAGE  \ ";
$f[]="        LINE1:negcacheentries#0000ff:\"negative cache entries\" \ ";
$f[]="        LINE1:nsspeedsentries#00ff00:\"NS speeds entries\" \ ";
$f[]="        LINE1:throttleentries#ffa000:\"throttle map entries\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/load-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-v \"MThreads\" \ ";
$f[]="	-t \"Concurrent queries\" \ ";
$f[]="	DEF:concurrentqueries=pdns_recursor.rrd:concurrent-queries:AVERAGE  \ ";
$f[]="        LINE1:concurrentqueries#0000ff:\"concurrent queries\" ";
$f[]="        ";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/hitrate-\$2.png -w \$WSIZE -h \$HSIZE -l 0\ ";
$f[]="	-v \"percentage\" \ ";
$f[]="	-t \"cache hits\" \ ";
$f[]="	DEF:cachehits=pdns_recursor.rrd:cache-hits:AVERAGE  \ ";
$f[]="	DEF:cachemisses=pdns_recursor.rrd:cache-misses:AVERAGE  \ ";
$f[]="	DEF:packetcachehits=pdns_recursor.rrd:packetcache-hits:AVERAGE  \ ";
$f[]="	DEF:packetcachemisses=pdns_recursor.rrd:packetcache-misses:AVERAGE  \ ";
$f[]="	CDEF:perc=cachehits,100,*,cachehits,cachemisses,+,/ \ ";
$f[]="	CDEF:packetperc=packetcachehits,100,*,packetcachehits,packetcachemisses,+,/ \ ";
$f[]="        LINE1:perc#0000ff:\"percentage cache hits\"  \  ";
$f[]="        LINE1:packetperc#ff00ff:\"percentage packetcache hits\"  \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"Cache hits \" \ ";
$f[]="        GPRINT:perc:AVERAGE:\"avg %-3.1lf%%\t\" \  ";
$f[]="        GPRINT:perc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:perc:MAX:\"max %-3.1lf%%\" \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"Pkt hits   \" \ ";
$f[]="        GPRINT:packetperc:AVERAGE:\"avg %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:packetperc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:packetperc:MAX:\"max %-3.1lf%%\" \ ";
$f[]="        COMMENT:\"\l\" ";
$f[]="";
$f[]="  rrdtool graph \$GRAPHOPTS --start -\$1 \$WWWPREFIX/cpuload-\$2.png -w \$WSIZE -h \$HSIZE -l 0\  ";
$f[]="	-v \"percentage\" \ ";
$f[]="	-t \"cpu load\" \ ";
$f[]="	DEF:usermsec=pdns_recursor.rrd:user-msec:AVERAGE \ ";
$f[]="	DEF:sysmsec=pdns_recursor.rrd:sys-msec:AVERAGE \ ";
$f[]="	DEF:musermsec=pdns_recursor.rrd:user-msec:MAX \ ";
$f[]="	DEF:msysmsec=pdns_recursor.rrd:sys-msec:MAX \ ";
$f[]="	CDEF:userperc=usermsec,10,/ \ ";
$f[]="	CDEF:sysperc=sysmsec,10,/ \ ";
$f[]="	CDEF:totmperc=usermsec,sysmsec,+,10,/ \  ";
$f[]="        LINE1:totmperc#ffff00:\"max cpu use\" \ ";
$f[]="        AREA:userperc#ff0000:\"user cpu percentage\" \ ";
$f[]="        STACK:sysperc#00ff00:\"system cpu percentage\" \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"System cpu \" \ ";
$f[]="        GPRINT:sysperc:AVERAGE:\"avg %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:sysperc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:sysperc:MAX:\"max %-3.1lf%%\t\" \ ";
$f[]="        COMMENT:\"\l\" \ ";
$f[]="        COMMENT:\"User cpu   \" \ ";
$f[]="        GPRINT:userperc:AVERAGE:\"avg %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:userperc:LAST:\"last %-3.1lf%%\t\" \ ";
$f[]="        GPRINT:userperc:MAX:\"max %-3.1lf%%\" \ ";
$f[]="        COMMENT:\"\l\"        ";
$f[]="";
$f[]="";
$f[]="}";
$f[]="	";
$f[]="makeGraphs 6h 6h";
$f[]="makeGraphs 24h day";
$f[]="#makeGraphs 7d week";
$f[]="#makeGraphs 1m month";
$f[]="#makeGraphs 1y year";
}

function replic_artica_servers(){
	
	$me=basename(__FILE__);
	$unix=new unix();
	$pidpath="/etc/artica-postfix/pids/$me.pid";
	$pid=$unix->get_pid_from_file($pidpath);
	if($unix->process_exists($pid,$me)){
		squid_admin_mysql(2, "Task $pid already executed...", __FUNCTION__, __FILE__, __LINE__);
		exit();
	}
	
	@file_put_contents($pidpath, getmypid());	
	
		$q=new mysql();
		$sql="SELECT * FROM pdns_replic";
		$results = $q->QUERY_SQL($sql,"artica_backup");
		if(!$q->ok){
			system_admin_events($q->mysql_error, __FUNCTION__, __FILE__, __LINE__);
			return;
		}

	while ($ligne = mysqli_fetch_assoc($results)) {	
		$hostname=$ligne["hostname"];
		$port=$ligne["host_port"];
		$datas=unserialize(base64_decode($ligne["host_cred"]));
		$username=$datas["username"];
		$password=$datas["password"];
		replic_artica_servers_perform("$hostname:$port",$username,$password);
	}
	
}
function replic_artica_servers_perform($host,$username,$password){
	$unix=new unix();
	$curl=new ccurl("https://$host/exec.gluster.php");
	$curl->parms["PDNS_REPLIC"]=base64_encode(serialize(array("username"=>$username,"password"=>md5($password))));
	if(!$curl->get()){
		squid_admin_mysql(2, "Error while fetching $host with $curl->error", __FUNCTION__, __FILE__, __LINE__);
		return;
	}
	
	if(preg_match("#<ERROR>(.*?)</ERROR>#is", $curl->data,$re)){
		squid_admin_mysql(2, "Connection error while fetching $host {$re[1]}", __FUNCTION__, __FILE__, __LINE__);
	}
	
	if(!preg_match("#<REPLIC>(.*?)</REPLIC>#is",$curl->data,$re)){
		squid_admin_mysql(2, "Protocol error while fetching $host", __FUNCTION__, __FILE__, __LINE__);
		return;		
	}
	
	$datas=unserialize(base64_decode($re[1]));
	squid_admin_mysql(2, "Received ". count($datas) . " from $host", __FUNCTION__, __FILE__, __LINE__);
	
	$sql="DELETE FROM records WHERE articasrv='$host'";
	$q=new mysql();
	$q->QUERY_SQL($sql,"powerdns");
	if(!$q->ok){
		system_admin_events($q->mysql_error." For Host $host", __FUNCTION__, __FILE__, __LINE__);
		return;
	}
	$t=time();
	$pdns=new pdns();
	$pdns->articasrv=$host;
	while (list ($ip, $hostname) = each ($datas) ){
		if(strpos($hostname, ".")>0){
			$tr=explode(".", $hostname);
			$hostname=strtolower($tr[0]);
			unset($tr[0]);
			$pdns->domainname=strtolower(@implode(".", $tr));
		}
		
		$pdns->EditIPName($hostname, $ip, "A");
	}
	$took=$unix->distanceOfTimeInWords($t,time(),true);
	squid_admin_mysql(2, "Success update ". count($datas) . " records from $host took:$took", __FUNCTION__, __FILE__, __LINE__);
	

}











function listen_ips(){

	$unix=new unix();
	$PowerDNSListenAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSListenAddr");
	$t=array();
	$ipA=explode("\n", $PowerDNSListenAddr);
	foreach ($ipA as $line2=>$ip){
		if(trim($ip)==null){continue;}
		if(!$unix->isIPAddress($ip)){continue;}
		$t[$ip]=$ip;
	}
	
	if(count($t)==0){
		$ips=new networking();
		$ipz=$ips->ALL_IPS_GET_ARRAY();
		while (list ($ip, $line2) = each ($ipz) ){
			$t[$ip]=$ip;
		
		}
		
	}
	
	
	while (list ($a,$b) = each ($t) ){
		$f[]=$a;
		
		
	}
	
	@file_put_contents("/etc/powerdns/iplist", @implode(",", $f));
	
}

function wizard_on(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$q=new lib_sqlite("/home/artica/SQLITE/dns.db");
	$sql="SELECT * FROM pdns_fwzones";
	$results=$q->QUERY_SQL($sql);
	if(!$q->ok){
		echo "MySQL error $q->mysql_error\n";
	}

	
	foreach ($results as $index=>$ligne){
		if(!is_numeric($ligne["port"])){$ligne["port"]=53;}
		if($ligne["port"]==0){$ligne["port"]=53;}
		$hostname=$ligne["hostname"].":".$ligne["port"];
		$zone=$ligne["zone"];
		$ID=$ligne["ID"];
		echo "Zone $zone -> $hostname\n";
		
		
	}
	
	echo "[A]................: Add a new ISP DNS server\n";
	echo "[B]................: Save and Exit\n";
	echo "[Q]................: Exit\n";
	
	$line = strtoupper(trim(fgets(STDIN)));
	
	if($line=="A"){
		echo "Give the address of your DNS server:\n";
		$server=trim(fgets(STDIN));
		if($server<>null){
			$sql="INSERT INTO pdns_fwzones (zone,hostname,port) VALUES ('*','$server',53)";
			$q->QUERY_SQL($sql,"artica_backup");
			if(!$q->ok){echo $q->mysql_error."\nEnter key to exit\n";
				$line = strtoupper(trim(fgets(STDIN)));
			}
		}
		wizard_on();
		return;
	}
	
	
	if($line=="B"){
		$sock=new sockets();
		echo "Enable the PowerDNS system...\n";
		$sock->SET_INFO("EnablePDNS", 1);
		echo "Apply settings...\n";
		
		shell_exec("$php5 /usr/share/artica-postfix/exec.pdns_server.php --restart");
		shell_exec("/etc/init.d/pdns-recursor restart");
		exit();
	}
	
	if($line=="Q"){exit();}
	
	wizard_on();
	return;
}
function MyLocalDomains(){
	
	if(isset($GLOBALS["MyLocalDomains"])){return $GLOBALS["MyLocalDomains"];}
	$q=new mysql_pdns();
	
	$sql="SELECT name,domain_id FROM (SELECT * FROM records WHERE `type`='SOA' ORDER BY name) as t ";
	
	$results = $q->QUERY_SQL($sql);
	while ($ligne = mysqli_fetch_assoc($results)) {
		$domain=$ligne["name"];
		$domain_id=$ligne["domain_id"];
		if(!isset($GLOBALS["MyLocalDomains"][$domain])){$GLOBALS["MyLocalDomains"][$domain]=$domain_id;}
	}
	return $GLOBALS["MyLocalDomains"];
}

function rectify_zone_progress($pourc,$text){
	$echotext=$text;
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	$cachefile=PROGRESS_DIR."/dns.rectify-zone.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	file_put_contents($cachefile, serialize($array));
	chmod($cachefile,0755);
}


function add_record(){

    //https://tailpuff.net/powerdns-script-add-new-zones-using-fw.brid/
    $sock=new sockets();
    $unix=new unix();
    $pdnsutil=$unix->find_program("pdnsutil");
    $MAIN=unserialize(base64_decode($sock->GET_INFO("PDNSAddRecord")));
    $domain_id=intval($MAIN["domain_id"]);
    $name=$MAIN["name"];
    $TYPE=$MAIN["type"];
    $CONTENT=$MAIN["content"];
    $TTL= $MAIN["ttl"];
    $PRIO=intval($MAIN["prio"]);
    if($domain_id==0){
        $sock->SET_INFO("PDNSAddRecordResults","$name/$TYPE Wrong domain ID! (before execute the command)");
        return;
    }

    $name=trim(strtolower($name));


    $q=new mysql_pdns();
    if(!$q->FIELD_EXISTS("domains","options","powerdns")) {
        writelogs("---- pdnsutil Patching domains table with options field",__FUNCTION__,__FILE__,__LINE__);
        $q->QUERY_SQL("ALTER TABLE domains ADD options VARCHAR(64000) DEFAULT NULL;");
        if(!$q->ok){
            writelogs("pdnsutil Patching domains table with options field (1) failed $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
            if(preg_match("#olumn length too big for column#",$q->mysql_error)){
                writelogs("pdnsutil Patching domains table with options 16383",__FUNCTION__,__FILE__,__LINE__);
                $q->QUERY_SQL("ALTER TABLE domains ADD options VARCHAR(16383) DEFAULT NULL;");
                if(!$q->ok){
                    writelogs("ADD options VARCHAR(16383 -> $q->mysql_error",__FUNCTION__,__FILE__,__LINE__);
                }
            }

        }
    }
    if(!$q->FIELD_EXISTS("domains","catalog","powerdns")) {
        $q->QUERY_SQL("ALTER TABLE domains ADD catalog VARCHAR(255) DEFAULT NULL;");
        $q->QUERY_SQL("CREATE INDEX catalog_idx ON domains(catalog);");
    }


    $domain=trim(strtolower($q->GetDomainName($domain_id)));
    if($domain==null){
        $sock->SET_INFO("PDNSAddRecordResults","Domain ID $domain_id did not exists.");
        return;
    }

    if(($TYPE=="TXT") OR ($TYPE=="SPF") ){
        if(strpos("  $CONTENT",'"')==0){$CONTENT='"'.$CONTENT.'"';}
        $CONTENT="'".$CONTENT."'";
        if($name==null){$name="@";}
        $TTL=null;
    }

    if($TYPE=="MX"){
        $name="@";
        $TTL=null;
        if($PRIO==0){$PRIO=5;}
        $CONTENT="\"$PRIO $CONTENT\"";
    }
    if($TYPE=="NS"){
        $name="@";
    }

    if( ($TYPE=="A") OR ($TYPE=="AAAA")  OR ($TYPE=="CNAME") OR ($TYPE=="ALIAS")) {
        $name=str_replace(".$domain","",$name);
        if($name=="*"){$name="@";}
        if($name==$domain){$name="@";}
    }

    // MX -->    pdnsutil add-record $DOMAIN @ MX "10 smtp.$DOMAIN"

    $cmd="$pdnsutil add-record $domain $name $TYPE $TTL $CONTENT 2>&1";
    writelogs($cmd,__FUNCTION__,__FILE__,__LINE__);
    exec("$pdnsutil add-record $domain $name $TYPE $TTL $CONTENT 2>&1",$results);
    foreach ($results as $sline){
        writelogs("pdnsutil add-record [$sline]",__FUNCTION__,__FILE__,__LINE__);
    }

    $sock->SET_INFO("PDNSAddRecordResults","$domain $name $TYPE $TTL $CONTENT<br>".@implode("<br>",$results));
    clean_database();

}

function rectify_zone($domain_id){
	$unix=new unix();
	echo "Zone ID: $domain_id\n";
	$domain_id=intval($domain_id);
	if($domain_id==0){rectify_zone_progress(110,"Wrong domain id...");return false;}
    $pdns_control=$unix->find_program("pdns_control");

    exec("$pdns_control --config-dir=/etc/powerdns reload",$results);

    foreach ($results as $line){
        if(preg_match("#OK#i",$line)){break;}
        if(preg_match("#Unable to connect to remote#i",$line)){
            squid_admin_mysql(1,"[DNS] Controle socket failed [ {action} = {restart} ]",null,__FILE__,__LINE__);
           system("/etc/init.d/pdns restart");
           break;
        }


    }


	$pdnsutil=$unix->find_program("pdnsutil");
	$q=new mysql_pdns();
    if(!$q->FIELD_EXISTS("cryptokeys","published")){ $q->QUERY_SQL("ALTER TABLE cryptokeys ADD published BOOL DEFAULT 1"); }
	$domain=$q->GetDomainName($domain_id);
	rectify_zone_progress(10,"{domain} $domain");
	$results=array();
	$cmd="$pdnsutil rectify-zone \"$domain\" 2>&1";
	echo "$cmd\n";
	exec($cmd,$results);
	rectify_zone_progress(50,"{domain} $domain {info}");

    if(!zone_info($domain_id,$domain)){
        rectify_zone_progress(110,"{domain} $domain {saving_data} {failed}");
        return false;
    }
    rectify_zone_progress(100,"{domain} $domain {saving_data} ".count($results)." lines {success}");
	
}

function zone_info($domain_id,$domain=null):bool{
    $unix           = new unix();
    $pdnsutil       = $unix->find_program("pdnsutil");
    $qsql           = new mysql_pdns();
    $q              = new lib_sqlite("/home/artica/SQLITE/dns.db");
    $final          = array();
    $results        = array();

    if($domain==null){
        $domain=$qsql->GetDomainName($domain_id);
    }
    $cmd="$pdnsutil show-zone \"$domain\" 2>&1";
    echo "$cmd\n";
    exec($cmd,$results);

    $cmd="$pdnsutil check-zone \"$domain\" 2>&1";
    echo "$cmd\n";
    exec($cmd,$results);


    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#Backend launched with banner#i", $line)){
            if($GLOBALS["VERBOSE"]){echo "# $line [SKIP]\n";}
            continue;}
        if(preg_match("#UeberBackend destructor#i", $line)){
            if($GLOBALS["VERBOSE"]){echo "# $line [SKIP]\n";}
            continue;}


        if($GLOBALS["VERBOSE"]){echo "# $line [ADD]\n";}
        $final[]=$line;
    }

    $data=base64_encode(serialize($final));

    $sql="UPDATE `dnsinfos` SET `zinfo`='$data' WHERE domain_id='$domain_id'";
    $ligne=$q->mysqli_fetch_array("SELECT name FROM dnsinfos WHERE domain_id=$domain_id");



    if($ligne["name"]==null){
        echo "Insert New element\n";
        $sql="INSERT INTO `dnsinfos` (domain_id,name,`zinfo`) VALUES ('$domain_id','$domain','$data')";
    }
    if($GLOBALS["VERBOSE"]){echo $sql."\n";}
    $q->QUERY_SQL($sql);
    if(!$q->ok){echo $q->mysql_error;return false;}
    return true;
}

function verify_zones(){
	$unix           = new unix();
	$pdnsutil       = $unix->find_program("pdnsutil");
	$q              = new mysql_pdns();

	$error_type["Error"]=1;
	$error_type["Info"]=0;

    if(!$q->FIELD_EXISTS("cryptokeys","published")){ $q->QUERY_SQL("ALTER TABLE cryptokeys ADD published BOOL DEFAULT 1"); }
	
	if($q->TABLE_EXISTS("pdnsutil_dnssec")){$q->QUERY_SQL("DROP TABLE pdnsutil_dnssec");}
	$q->QUERY_SQL("CREATE TABLE pdnsutil_dnssec (id INTEGER NOT NULL AUTO_INCREMENT,domain_id INTEGER NOT NULL,content TEXT,PRIMARY KEY (id) ) ENGINE=MyISAM;");
	
	if($q->TABLE_EXISTS("pdnsutil_chkzones")){$q->QUERY_SQL("DROP TABLE pdnsutil_chkzones");}
	$q->QUERY_SQL("CREATE TABLE pdnsutil_chkzones (id INTEGER NOT NULL AUTO_INCREMENT,domain_id INTEGER NOT NULL,content TEXT,error_type smallint(1) NOT NULL, PRIMARY KEY (id), KEY error_type (`error_type`) ) ENGINE=MyISAM;");
	
	
	$MAIN=MyLocalDomains();
	$CountOfDomains=count($MAIN);
	$f=array();
	$i=0;
	foreach ($MAIN as $domain=>$domain_id){
		echo "Checking domain $domain ($domain_id)\n";
		$i++;
		$prc=round(($i/100)*100,1);
		build_progress_dnssec(80,"{$prc}% -- Checking domain $domain");
		
		$RES=array();
		exec("$pdnsutil check-zone $domain 2>&1",$RES);
		foreach ($RES as $line){
            if(preg_match("#Checked [0-9]+ records of#i",$line)){continue;}
		    if(preg_match("#[0-9]+\s+Error\s+(.+)#",$line,$re)){
                $f[]="('$domain_id',1,'{$re[1]}')";
                continue;
            }

			if(!preg_match("#\[([A-Za-z]+)\]\s+(.+)#",$line,$re)){continue;}
			$errnum=$error_type[$re[1]];
			$re[2]=str_replace("'", '"', $re[2]);
			$f[]="('$domain_id','{$re[2]}','$errnum')";	
		}
		$RES=array();
		exec("$pdnsutil show-zone $domain 2>&1",$RES);
		$nssec=array();
		foreach ($RES as $line){
			$line=trim($line);
			if($line==null){continue;}
			if(preg_match("#(No keys for zone|Zone is not actively secured)#", $line)){$nssec=array();break;}
			if(preg_match("#^([A-Z\s]+)\s+=\s+#", $line)){
				$line=str_replace("'", '"', $line);
				$nssec[]=mysql_escape_string2($line);}
			
		}
		echo "-------------------------------------------- NSSEC ". count($nssec)."--------------------------------------------\n";
		if(count($nssec)>0){
			$ctnt=@implode("\n", $nssec);
			$q->QUERY_SQL("INSERT INTO pdnsutil_dnssec (domain_id,`content`) VALUES ('$domain_id','$ctnt')");
		}
		
		
		
	}
	
	
	echo "-------------------------------------------- FINAL --------------------------------------------\n";
	if(count($f)>0){
		$q->QUERY_SQL("INSERT INTO pdnsutil_chkzones (domain_id,`content`,error_type) VALUES ".@implode(",", $f));
		if(!$q->ok){echo "FAILED $q->mysql_error!! ! ! ! ! \n";}
	}
	
	
}

function import_backup_progress($prc,$txt){
    $unix=new unix();
    $unix->framework_progress($prc,$txt,"pdns.import.progress");

}

function import_backup($fname){
    $filename=base64_decode($fname);
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $tar=$unix->find_program("tar");
    $basefile="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    if(!is_file($basefile)){
        echo $basefile." no such file\n";
        import_backup_progress(110,"{failed}");
        return false;
    }
    $tmpfile=$unix->FILE_TEMP();
    @copy($basefile,$tmpfile);
    @unlink($basefile);

    $tempdir=$unix->TEMP_DIR()."/pdns-export";
    $rmall="$rm -rf $tempdir";
    if(!is_dir($tempdir)){
        @mkdir($tempdir,0755,true);

    }

    import_backup_progress(30,"{extracting}...");
    shell_exec("$tar xf $tmpfile -C $tempdir/");
    @unlink($tmpfile);
    if(!is_file("$tempdir/dns.db")){
        shell_exec($rmall);
        echo "$tempdir/dns.db, no such file";
        import_backup_progress(110,"{failed}");
        return false;
    }
    if(!is_file("$tempdir/powerdns.sql")){
        shell_exec($rmall);
        echo "$tempdir/powerdns.sql, no such file";
        import_backup_progress(110,"{failed}");
        return false;
    }
    import_backup_progress(50,"{restoring}...");
    @unlink("/home/artica/SQLITE/dns.db");
    @copy("$tempdir/dns.db","/home/artica/SQLITE/dns.db");
    @chmod("/home/artica/SQLITE/dns.db", 0644);
    @chown("/home/artica/SQLITE/dns.db", "www-data");

    $q=new mysql_pdns();
    $MYSQL_CMDLINES=$q->MYSQL_CMDLINES;
    $mysql=$unix->find_program("mysql");
    import_backup_progress(70,"{restoring}...");
    $cmdline="$mysql $MYSQL_CMDLINES powerdns < $tempdir/powerdns.sql 2>&1";
    exec($cmdline,$results);


    if($unix->MYSQL_BIN_PARSE_ERROR($results)){
        echo $unix->mysql_error."\n";
        import_backup_progress(110,"{failed}");
        shell_exec($rmall);
        return false;
    }
    import_backup_progress(100,"{restoring} {success}...");
    return true;



}




?>