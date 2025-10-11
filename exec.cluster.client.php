<?php
die();
$GLOBALS["SUPER_FORCE"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["DUMP"]=false;
$GLOBALS["COMMANDLINE"]=@implode(" ", $argv);
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.backup.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.cyrus.inc');

include_once(dirname(__FILE__).'/ressources/class.user.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__)."/ressources/class.snapshots.blacklists.inc");
include_once(dirname(__FILE__)."/ressources/class.webconsole.params.inc");
include_once(dirname(__FILE__)."/ressources/externals/class.aesCrypt.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.powerdns.inc");
if(preg_match("#--verbose#",$GLOBALS["COMMANDLINE"])){$GLOBALS["VERBOSE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--dump#",$GLOBALS["COMMANDLINE"])){$GLOBALS["DUMP"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--dump#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
if(preg_match("#--force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;}
if(preg_match("#--super-force#",$GLOBALS["COMMANDLINE"])){$GLOBALS["FORCE"]=true;$GLOBALS["SUPER_FORCE"]=true;}



if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}

retreive_cluster();



function build_progress($pourc,$text){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/artica.cluster.progress";
    $cachefile2="/usr/share/artica-postfix/ressources/logs/pdns.import.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @file_put_contents($cachefile2, serialize($array));
    @chmod($cachefile,0755);
    @chmod($cachefile2,0755);
}


function install(){
    $unix=new unix();
    build_progress(10,"{enable_slave_mode}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableClusterSlave",1);
    if(is_file("/etc/cron.d/PowerDNSClusterClient")){@unlink("/etc/cron.d/PowerDNSClusterClient");}
    $PowerDNSEnableClusterSlaveSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlaveSchedule"));

    if($PowerDNSEnableClusterSlaveSchedule<5){$PowerDNSEnableClusterSlaveSchedule=15;}
    $unix->Popuplate_cron_make("ClusterClient","*/$PowerDNSEnableClusterSlaveSchedule * * * *",basename(__FILE__));
    system("/etc/init.d/cron reload");
    build_progress(100,"{enable_slave_mode} {done}");
}
function uninstall(){
    $unix=new unix();
    build_progress(10,"{uninstalling}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSEnableClusterSlave",0);
    if(is_file("/etc/cron.d/PowerDNSClusterClient")){@unlink("/etc/cron.d/PowerDNSClusterClient");}
    if(is_file("/etc/cron.d/ClusterClient")){@unlink("/etc/cron.d/ClusterClient");}
    system("/etc/init.d/cron reload");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientMD5", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientDate",0);
    $rm=$unix->find_program("rm");
    shell_exec("$rm  -rf /home/artica/PowerDNS/Cluster");
    build_progress(100,"{uninstalling} {done}");
}

function retreive_cluster(){

    $PowerDNSClusterClientStop=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientStop"));
    if($PowerDNSClusterClientStop==1){
        cluster_syslog("Client replication mode is in freeze mode.",null,__LINE__);
    }

    cluster_syslog("Starting client replication mode.",null,__LINE__);
    if(!$GLOBALS["CLASS_SOCKETS"]->CORP_LICENSE()){
        cluster_events(0,"{license_error}, {failed}",null,__LINE__);
        build_progress(110,"{license_error}");
        die();
    }

    $PowerDNSEnableClusterSlave=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSEnableClusterSlave"));
    $unix=new unix();
    if(!$GLOBALS["FORCE"]){
        $TimeFile=$unix->file_time_min("/etc/artica-postfix/settings/Daemons/PowerDNSClusterClientMD5");
        if($TimeFile<5){
            cluster_syslog("{$TimeFile}mn, too short time, require at least 5mn",null,__LINE__);
            build_progress(110,"{$TimeFile}mn, too short time, require at least 5mn...");
            exit();
        }
    }




    if($PowerDNSEnableClusterSlave==0){
        cluster_syslog("Feature disabled",null,__LINE__);
        cluster_events(1,"{feature_disabled}",null,__LINE__);
        build_progress(110,"{feature_disabled}");
        return false;
    }



    $PowerDNSClusterSlaveInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterSlaveInterface"));
    $PowerDNSClusterMasterAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterAddress"));
    $PowerDNSClusterMasterPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterMasterPort"));


    if($PowerDNSClusterMasterPort==null){
        cluster_syslog("Missing cluster server port (int)",null,__LINE__);
        build_progress(110,"Missing cluster server port (int)",null,__LINE__);
    }
    if($PowerDNSClusterMasterAddress==null){
        cluster_syslog("Missing cluster server name (string)",null,__LINE__);
        build_progress(110,"Missing cluster server name (string)",null,__LINE__);
    }

    $uri="https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort/pdns-cluster/index.txt";


    cluster_debug("Downloading $uri",__LINE__);
    cluster_syslog("Downloading \"$uri\"",null,__LINE__);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_URL, "$uri");
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    $dataIndex=curl_exec($ch);

    $Error=curl_errno($ch);
    if($Error>0) {
        cluster_syslog("HTTP Error $Error for $uri",null,__LINE__);
        cluster_events(0,"HTTP Error $Error (" .curl_error($ch).")", __LINE__);
        build_progress(110,curl_error($ch));
        curl_close($ch);
        return;
    }

    $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    curl_close($ch);
    echo "Data.......: ".strlen($dataIndex)." bytes\n";
    echo "header_size: $header_size\n";
    cluster_syslog("Downloading Success HTTP Code $CURLINFO_HTTP_CODE (".strlen($dataIndex)."Bytes)",null,__LINE__);


    if($CURLINFO_HTTP_CODE>200){
        cluster_syslog("HTTP Error $CURLINFO_HTTP_CODE for $uri",null,__LINE__);
        build_progress(110,"Failed to download index err.$CURLINFO_HTTP_CODE");
        echo "https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort\n{error} $CURLINFO_HTTP_CODE\n";
        cluster_events(0, "Cluster: Failed to download index err.$CURLINFO_HTTP_CODE", "https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort",__FILE__,__LINE__);
        return false;
    }


    if($GLOBALS["VERBOSE"]){echo "Decode \"$dataIndex\"";}
    $data2 =base64_decode($dataIndex);
    $ARRAY=unserialize($data2);

    if(!isset($ARRAY["SERIAL"])){
        build_progress(110,"Decode data failed");
        echo "Decode data failed!\n";
        cluster_events(0, "Cluster: Failed to decrypt uuencoded index file", null, __LINE__);
        return false;

    }

    cluster_syslog("Decrypting success....",null,__LINE__);

    if(!isset($ARRAY["UUID"])){$ARRAY["UUID"]=null;}
    $CurrentMD5=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientMD5"));
    $PowerDNSClusterClientDate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PowerDNSClusterClientDate"));
    $RemoteMD5=$ARRAY["MD5"];
    $RemoteUUID=$ARRAY["UUID"];
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ClusterReplicateOfficalDatabases",intval($ARRAY["ClusterReplicateOfficalDatabases"]));
    if($PowerDNSClusterClientDate==0){$CurrentMD5="none";}
    if($GLOBALS["VERBOSE"]){$CurrentMD5="none";}

    if($RemoteUUID<>null){
        $MyUUID=$unix->GetUniqueID();
        if($MyUUID==$RemoteUUID){
            cluster_events(0, "Cluster: Loop back to my self, or copied virtual machines", "Cannot have the same uuid!",__LINE__);
            build_progress(110,"Loop back to myself");
            return false;
        }
    }
    if(!$GLOBALS["SUPER_FORCE"]) {
        if ($RemoteMD5 == $CurrentMD5) {
            cluster_syslog("$RemoteMD5==$CurrentMD5 Nothing to do", null, __LINE__);
            build_progress(100, "{nothing_to_do}");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientMD5", $CurrentMD5);
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientDate", time());
            return true;
        }
    }

    $unix=new unix();


    $tmpfile=$unix->FILE_TEMP();
    $uri="https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort/pdns-cluster/powerdns.tar.gz";


    cluster_syslog("Downloading $uri",null,__LINE__);
    cluster_debug("Downloading $uri",__LINE__);

    $out = fopen($tmpfile, 'w');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_FILE, $out);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 720);
    curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Pragma: no-cache,must-revalidate", "Cache-Control: no-cache,must revalidate",'Expect:'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 360);
    curl_setopt($ch, CURLOPT_URL, "$uri");
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_SSLVERSION,'all');
    curl_setopt($ch, CURLOPT_NOPROXY,"*");
    curl_exec($ch);
    $CURLINFO_HTTP_CODE=curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $header_size = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    fclose($out);
    $Error=curl_errno($ch);
    if($Error>0) {
        cluster_syslog("HTTP Error $Error (" .curl_error($ch).")",null,__LINE__);
        cluster_events(0,"HTTP Error $Error (" .curl_error($ch).")", @implode("\n",$GLOBALS["LOGS"]),__LINE__);
        build_progress(110,curl_error($ch));
        curl_close($ch);
        return;
    }

    curl_close($ch);

    if($CURLINFO_HTTP_CODE>200){
        cluster_syslog("HTTP Error $Error (" .$CURLINFO_HTTP_CODE.")",null,__LINE__);
        build_progress(110,"Failed to download container err.$CURLINFO_HTTP_CODE");
        echo "https://$PowerDNSClusterMasterAddress:$PowerDNSClusterMasterPort\n{error} $CURLINFO_HTTP_CODE\n";
        cluster_events(0,"HTTP Error $CURLINFO_HTTP_CODE", @implode("\n",$GLOBALS["LOGS"]),__LINE__);
        return false;
    }
    $size=@filesize($tmpfile);
    $sizeKO=$size/1024;
    $sizeMB=round($sizeKO/1024,2);

    $NEXT_FILE=$unix->TEMP_DIR()."/powerdns.tar.gz";
    if(is_file($NEXT_FILE)){@unlink($NEXT_FILE);}
    @copy($tmpfile,$NEXT_FILE);

    @unlink($tmpfile);
    echo "Verify $NEXT_FILE\n";
    if(!$unix->VerifyTar($NEXT_FILE)){
        @unlink($tmpfile);
        @unlink($NEXT_FILE);
        build_progress(110,"Failed to verify compressed container");
        cluster_events(0, "Cluster: Failed to verify compressed container ({$sizeMB}MB)", "VerifyTar($NEXT_FILE) Failed".@implode("\n",$GLOBALS["LOGS"]),__LINE__);
        return false;
    }
    echo "Verify OK\n";
    if(!import_backup($NEXT_FILE)){
        @unlink($tmpfile);
        @unlink($NEXT_FILE);
        build_progress(110,"Failed to import container");
        cluster_events(0, "Cluster: Failed to import container ({$sizeMB}MB)", "import_backup($NEXT_FILE) Failed".@implode("\n",$GLOBALS["LOGS"]),__LINE__);
        return false;
    }
    @unlink($NEXT_FILE);
    $EnablePDNS=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS");

    @mkdir("/home/artica/PowerDNS/Cluster/storage",0755,true);
    if(is_file("/home/artica/PowerDNS/Cluster/storage/powerdns.aes")){@unlink("/home/artica/PowerDNS/Cluster/storage/powerdns.aes");}
    @copy($tmpfile, "/home/artica/PowerDNS/Cluster/storage/powerdns.aes");
    @file_put_contents("/home/artica/PowerDNS/Cluster/storage/index.aes", $dataIndex);
    $php=$unix->LOCATE_PHP5_BIN();
    if($EnablePDNS==1){
        shell_exec("$php /usr/share/artica-postfix/exec.pdns_server.php --reload");
    }


    @unlink("/etc/artica-postfix/settings/Daemons/PowerDNSClusterClientMD5");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientMD5", $RemoteMD5);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PowerDNSClusterClientDate", time());


    build_progress(90,"{synchronizing}");

    shell_exec("$php /usr/share/artica-postfix/exec.synchronize-settings.php 2>&1");

    build_progress(100,"Success to import container");
    cluster_events(2, "Cluster: Success to import container ({$sizeMB}MB)", "",__LINE__);
    return false;
}
function import_backup($filename){
    $unix=new unix();
    $EnablePDNS=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
    $ClusterNotReplicateWeb=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateWeb"));
    $EXTRACT_TGZ=false;
    if(!is_file($filename)){
        $filename=base64_decode($filename);
        $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    }else{
        $fullpath=$filename;
        $filename=basename($filename);
    }

    echo "Target: $fullpath\n";
    if(!is_file($fullpath)){
        echo "No such file\n";
        build_progress(110,"{failed} $filename");
        return false;
    }
    $extension=$unix->file_extension($filename);
    echo "Extension...: $extension\n";
    $mysql=$unix->find_program("mysql");
    echo "MySQL Binary: $mysql\n";
    if(preg_match("#\.tar\.gz$#", $filename)){$EXTRACT_TGZ=True;}
    if(preg_match("#\.tar\s+\([0-9]+\)\.gz$#", $filename)){$EXTRACT_TGZ=True;}
    $DIRTEMP = $unix->TEMP_DIR();
    if($EXTRACT_TGZ) {
        echo "Is a TGZ file...\n";
        build_progress(20, "{extracting} $filename to $DIRTEMP/pdns_backup");
        $tar = $unix->find_program("tar");
        $rm = $unix->find_program("rm");
        if (is_dir("$DIRTEMP/pdns_backup")) {
            shell_exec("$rm -rf $DIRTEMP/pdns_backup");
        }
        @mkdir("$DIRTEMP/pdns_backup", 0777);
        system("$tar xf " . $unix->shellEscapeChars($fullpath) . " -C $DIRTEMP/pdns_backup/");
        @unlink($fullpath);


        if ($EnablePDNS == 1) {
            if (is_file("$DIRTEMP/pdns_backup/powerdns.gz")) {
                echo "Extracting powerdns.gz\n";
                $unix->uncompress("$DIRTEMP/pdns_backup/powerdns.gz", "$DIRTEMP/pdns_backup/powerdns.sql");
            }


            if (!is_file("$DIRTEMP/pdns_backup/powerdns.sql")) {
                build_progress(110, "{importing} powerdns.sql failed no such file");
                shell_exec("$rm -rf $DIRTEMP/pdns_backup");
                return false;
            }
            $q = new mysql_pdns();
            $MYSQL_CMDLINES = $q->MYSQL_CMDLINES;
            $cmdline = "$mysql $MYSQL_CMDLINES powerdns < $DIRTEMP/pdns_backup/powerdns.sql 2>&1";
            echo $cmdline . "\n";
            build_progress(40, "{importing} powerdns.sql");
            exec($cmdline, $results);

            if ($GLOBALS["VERBOSE"]) {
                echo $cmdline . "\n" . @implode("\n", $results);
                @unlink("/root/powerdns.sql");
                @copy("$DIRTEMP/pdns_backup/powerdns.sql", "/root/powerdns.sql");
                echo "You can test using this command line\n$mysql $MYSQL_CMDLINES powerdns < /root/powerdns.sql 2>&1\n\n";
            }


            @unlink("$DIRTEMP/pdns_backup/powerdns.sql");
            if ($unix->MYSQL_BIN_PARSE_ERROR($results)) {
                echo $unix->mysql_error . "\n";
                build_progress(110, "{failed} $filename");
                shell_exec("$rm -rf $DIRTEMP/pdns_backup");
                return false;
            }

        }


        if (is_dir("$DIRTEMP/pdns_backup/var/lib/ufdbartica")) {
            echo "Replicating Web-filtering databases....\n";
            if (!is_dir("/var/lib/ufdbartica")) {
                @mkdir("/var/lib/ufdbartica", 0755, true);
            }
            $cp = $unix->find_program("cp");
            $cmd_line = "$cp -rfvd $DIRTEMP/pdns_backup/var/lib/ufdbartica/* /var/lib/ufdbartica/ 2>&1";
            echo $cmd_line . "\n";
            system($cmd_line);

        } else {
            echo "$DIRTEMP/pdns_backup/var/lib/ufdbartica no such directory\n";
        }

        $BLACKLISTS=artica_settings_blacklists(true);

        if($ClusterNotReplicateWeb==1) {
            $BLACKLISTS["SquidGuardWebWebServiceID"]=true;
            $BLACKLISTS["SquidGuardWebUseInternalUri"]=true;
            $BLACKLISTS["SquidGuardWebUseExternalUri"]=true;
            $BLACKLISTS["SquidGuardWebExternalUri2"]=true;
            $BLACKLISTS["SquidGuardWebExternalUri"]=true;
            $BLACKLISTS["SquidGuardWebExternalUri"]=true;
            $BLACKLISTS["SquidGuardWebExternalUriSSL"]=true;
        }


        $CountOfParams = 0;
        $ClusterNotReplicateWeb=0;
        $ClusterNotReplicateTasks=0;
        $ClusterNotReplicateWebParameters=0;
        $MAIN=array();

        $GLOBALS["CHANGES_ACTION"]["max_filedesc"]="exec.squid.global.access.php --cache-tweaks";
        $GLOBALS["CHANGES_ACTION"]["fs_filemax"]="exec.sysctl.php --build";
        $GLOBALS["CHANGES_ACTION"]["nf_conntrack_max"]="exec.sysctl.php --build";
        $GLOBALS["CHANGES_ACTION"]["resolvConf"]="";
        $GLOBALS["CHANGES_ACTION"]["CICAPWebErrorPage"]="exec.c-icap.php --template";
        
        $GLOBALS["CHANGES_PROXY"]["max_filedesc"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("max_filedesc"));
        $GLOBALS["CHANGES_PROXY"]["CICAPWebErrorPage"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPWebErrorPage"));


        $GLOBALS["CHANGES_SYSTEM"]["fs_filemax"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("fs_filemax"));
        $GLOBALS["CHANGES_SYSTEM"]["nf_conntrack_max"]=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("nf_conntrack_max"));
        $GLOBALS["CHANGES_SYSTEM"]["resolvConf"]=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("resolvConf"));


        if (is_file("$DIRTEMP/pdns_backup/settings.conf")) {
            $MAIN = unserialize(@file_get_contents("$DIRTEMP/pdns_backup/settings.conf"));
            if(!isset($MAIN["ClusterNotReplicateWeb"])){$MAIN["ClusterNotReplicateWeb"]=0;}
            if(!isset($MAIN["ClusterNotReplicateTasks"])){$MAIN["ClusterNotReplicateTasks"]=0;}
            if(!isset($MAIN["ClusterNotReplicateWebParameters"])){$MAIN["ClusterNotReplicateWebParameters"]=0;}
            $ClusterNotReplicateWeb=intval($MAIN["ClusterNotReplicateWeb"]);
            $ClusterNotReplicateTasks=intval($MAIN["ClusterNotReplicateTasks"]);
            $ClusterNotReplicateWebParameters=intval($MAIN["ClusterNotReplicateWebParameters"]);
        }

        foreach ($MAIN as $key => $value) {
            if(isset($BLACKLISTS[$key])){continue;}
            if($key=="APP_KEEPALIVED_ENABLE"){continue;}
            if($key=="APP_KEEPALIVED_INSTALLED"){continue;}
            if($key=="APP_KEEPALIVED_VERSION"){continue;}
            if($key=="APP_KEEPALIVED_ENABLE_SLAVE"){continue;}
            $GLOBALS["PARAMS_IMPORTED"][] = $key;
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO($key, $value);
            $CountOfParams++;
        }
        $DBCRC["nginx_db"]=crc32_file("/home/artica/SQLITE/nginx.db");

        $SQLITE = sqlitedb_list();

        if($ClusterNotReplicateTasks==1){
            unset($SQLITE["sys_schedules.db"]);
        }

        if($ClusterNotReplicateWebParameters==1){
            unset($SQLITE["webconsole.db"]);
        }
        //Remove keepalived from cluster sync
        unset($SQLITE["keepalived.db"]);

        foreach ($SQLITE as $dbfile => $none) {

            if($ClusterNotReplicateTasks==1){
                if($dbfile=="sys_schedules.db"){continue;}
            }

            if($ClusterNotReplicateWebParameters==1){
                if($dbfile=="webconsole.db"){continue;}
            }
            //Remove keepalived from cluster sync
            if($dbfile=="keepalived.db"){continue;}

            if (!is_file("$DIRTEMP/pdns_backup/$dbfile")) {
                echo "$DIRTEMP/pdns_backup/$dbfile no such file...\n";
                continue;
            }

            $oldmd5 = md5_file("/home/artica/SQLITE/$dbfile");
            $newmd5 = md5_file("$DIRTEMP/pdns_backup/$dbfile");

            if ($oldmd5 == $newmd5) {
                echo "$DIRTEMP/pdns_backup/$dbfile $oldmd5==$newmd5 SKIP!\n";
                continue;
            }
            build_progress(45, "{importing} $dbfile");
            @unlink("/home/artica/SQLITE/$dbfile");
            @copy("$DIRTEMP/pdns_backup/$dbfile", "/home/artica/SQLITE/$dbfile");
            @chmod("/home/artica/SQLITE/$dbfile", 0777);
            $GLOBALS["DB_IMPORTED"][] = $dbfile;


        }


        $HaClusterClient = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("HaClusterClient"));

        if ($HaClusterClient == 1) {

            if (is_file("/etc/squid3/krb5.keytab")) {
                @unlink("/etc/squid3/krb5.keytab");
            }

            if (is_file("$DIRTEMP/pdns_backup/krb5.keytab")) {
                @copy("$DIRTEMP/pdns_backup/krb5.keytab", "/etc/squid3/krb5.keytab");
            }
        }


        $q = new postgres_sql();
        $q->CREATE_TABLES();
        $q->SMTP_TABLES();
        foreach ($GLOBALS["PGDUMP"] as $tablename) {
            if (!is_file("$DIRTEMP/pdns_backup/$tablename.pgsql")) {
                echo "$DIRTEMP/pdns_backup/$tablename.pgsql no such file...\n";
                continue;
            }
            $q->QUERY_SQL("TRUNCATE TABLE $tablename");
            build_progress(45, "{importing} $tablename.pgsql");
            $cmd = "/usr/local/ArticaStats/bin/pg_restore -v --data-only --dbname=proxydb --format=custom --table=$tablename -h /var/run/ArticaStats -U ArticaStats $DIRTEMP/pdns_backup/$tablename.pgsql";
            system($cmd);
            $GLOBALS["DB_IMPORTED"][] = "PostgreSQL: $tablename";

        }

        echo "Scanning table for categories.pgdump\n";

        if(is_file("$DIRTEMP/pdns_backup/categories.pgdump")){
            $cmd = "/usr/local/ArticaStats/bin/pg_restore -v --dbname=proxydb --clean -h /var/run/ArticaStats -U ArticaStats $DIRTEMP/pdns_backup/categories.pgdump";
            echo "$cmd\n";
            system($cmd);

        }
        $ClusterNotReplicateWebParameters=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateWebParameters"));
        if ($ClusterNotReplicateWebParameters==1) {
            $KEYS_INTERFACE[] = "ArticaHttpsPort";
            $KEYS_INTERFACE[] = "LighttpdArticaListenInterface";
            $KEYS_INTERFACE[] = "ArticaHttpUseSSL";
            $KEYS_INTERFACE[] = "LighttpdArticaCertificateName";
            $KEYS_INTERFACE[] = "LighttpdArticaDisableSSLv2";
            $KEYS_INTERFACE[] = "SSLCipherSuite";
            $KEYS_INTERFACE[] = "EnableArticaWebLogging";
        }

        $articaP="/usr/share/artica-postfix";
        $php = $unix->LOCATE_PHP5_BIN();
        $nohup=$unix->find_program("nohup");
        $devn=" >/dev/null 2>&1 &";
        build_progress(80, "{removing} $DIRTEMP/pdns_backup");
        shell_exec("$rm -rf $DIRTEMP/pdns_backup");

        $EnablePDNS = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePDNS"));
        $SQUIDEnable = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
        $EnableStatsCommunicator=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsCommunicator");
        $ClusterNotReplicateTasks=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ClusterNotReplicateTasks"));



        if(is_file("/etc/init.d/statscom")){
            if($EnableStatsCommunicator==0){
                squid_admin_mysql(1,"Uninstalling Statistics Communicator",null,__FILE__,__LINE__);
                shell_exec("$nohup $php $articaP/exec.statscom.php --uninstall{$devn}");
            }
        }



        if ($EnablePDNS == 1) {
            build_progress(85, "{restarting_services}");
            if (is_file("/etc/init.d/pdns")) {
                system("/etc/init.d/pdns restart");
            }
            if (is_file("/etc/init.d/pdns-recursor")) {
                system("/etc/init.d/pdns-recursor restart");
            }
        }


        if (is_file("/etc/init.d/unbound")) {
            build_progress(86, "{restarting_services}");
            system("/usr/sbin/artica-phpfpm-service -restart-unbound");
        }

        if (is_file("/etc/init.d/postfix")) {
            build_progress(87, "{restarting_services}");
            shell_exec("$php /usr/share/artica-postfix/exec.postfix.maincf.php --reconfigure");
            build_progress(88, "{restarting_services}");
            shell_exec("$php /usr/share/artica-postfix/exec.postfix.transport.php");
        }


        if (is_file("/etc/init.d/squid")) {
            build_progress(89, "{restarting_services}");
            shell_exec("$php /usr/share/artica-postfix/exec.squid.templates.php --progress");
        }

        if (is_file("/etc/init.d/ufdb")){
            build_progress(90, "{restarting_services}");
            shell_exec("$php /usr/share/artica-postfix/exec.ufdb-http-build.php");
        }



    }

    $CLUSTER_COMMANDS = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLUSTER_COMMANDS")));
    $CLUSTER_DONE     = unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLUSTER_DONE")));

    $CountOfComands=0;
    if(count($CLUSTER_DONE)>500){$CLUSTER_DONE=array();}
    foreach ($CLUSTER_COMMANDS as $stime=>$command){
        $distance=$unix->time_min($stime);
        if($distance>120){
            $CLUSTER_DONE[$stime]=true;
            continue;
        }

        if(isset($CLUSTER_DONE[$stime])){continue;}
        $GLOBALS["COMMANDS_IMPORTED"][]=$command;
        $CountOfComands++;
        $GLOBALS["CLASS_SOCKETS"]->getFrameWork($command);
        $CLUSTER_DONE[$stime]=true;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CLUSTER_DONE", base64_encode(serialize($CLUSTER_DONE)));
    OperationsOnChanges("CHANGES_SYSTEM");
    $CountOfDatabases=count($GLOBALS["DB_IMPORTED"]);

    $TEXT[]="Parameters: Keys:\n".@implode("\n",$GLOBALS["PARAMS_IMPORTED"]);
    $TEXT[]="NoSQL Databases.:\n".@implode("\n",$GLOBALS["DB_IMPORTED"]);
    $TEXT[]="Commands........:\n".@implode("\n",$GLOBALS["COMMANDS_IMPORTED"]);


    checks_nginx($DBCRC);

    build_progress(100,"{importing} {done}");
    cluster_events(2,"{importing} {success}: $CountOfDatabases databases, $CountOfParams parameters, $CountOfComands commands.",@implode("\n",$TEXT));
    return true;

}

function checks_nginx($DBCRC){
    $unix=new unix();
    $EnableNginx=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableNginx");
    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_INSTALLED"))==0){return true;}
    if($EnableNginx==0){return true;}

    $nginx_db=crc32_file("/home/artica/SQLITE/nginx.db");
    if($DBCRC["nginx_db"]==$nginx_db){
        cluster_events(2,"Reverse-Proxy settings - no changes","{$DBCRC["nginx_db"]} = $nginx_db",__LINE__);
        return true;
    }

    $CLUSTER_NGINX_ARRAY=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CLUSTER_NGINX_ARRAY")));

    if(count($CLUSTER_NGINX_ARRAY)==0){return true;}
    $ARROOT=ARTICA_ROOT;
    $q=new lib_sqlite("/home/artica/SQLITE/nginx.db");
    $php=$unix->LOCATE_PHP5_BIN();
    foreach ($CLUSTER_NGINX_ARRAY as $service_id=>$none){
        $ligne=$q->mysqli_fetch_array("SELECT `servicename` FROM nginx_services WHERE ID=$service_id");
        $servicename=$ligne["servicename"];
        if($servicename==null){continue;}
        cluster_events(2,"Reverse-Proxy settings - Compiling site $servicename",null,__LINE__);
        shell_exec("$php $ARROOT/exec.nginx.single.php $service_id >/dev/null 2>&1");
    }

    return true;
}


function CheckServices(){
    $sock=new sockets();
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    if($sock->EnableUfdbGuard()==1){
        echo "EnableUfdbGuard == 1\n";
        if(!is_file("/etc/init.d/ufdb-tail")){

            system("$php /usr/share/artica-postfix/exec.squidguard.php --install-services");
        }
        if(!is_file("/etc/init.d/ufdb")){

            system("$php /usr/share/artica-postfix/exec.squidguard.php --install-services");
        }


    }else{
        echo "EnableUfdbGuard == 0\n";
        if(is_file("/etc/init.d/ufdb")){
            system("$php /usr/share/artica-postfix/exec.ufdbguard.uninstall.php");
        }
    }

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenSSH"))==1){
        echo "EnableOpenSSH == 1\n";
        if(!is_file("/etc/init.d/ssh")){
            system("/usr/sbin/artica-phpfpm-service -install-ssh");
        }
    }else{
        echo "EnableOpenSSH == 0\n";
        if(is_file("/etc/init.d/ssh")){
            system("/usr/sbin/artica-phpfpm-service -uninstall-ssh");
        }
    }

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapEnabled"))==1){
        if(!is_file("/etc/init.d/c-icap")){system("/usr/sbin/artica-phpfpm-service -install-cicap");}
        $unix->CICAP_SERVICE_EVENTS("Reconfiguring ICAP service",__FILE__,__LINE__);
        system("$php /usr/share/artica-postfix/exec.c-icap.php --reconfigure");

        if(is_file("/etc/artica-postfix/settings/Daemons/CicapClusterConnect")){
            $CicapClusterConnect=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CicapClusterConnect"));
            if($CicapClusterConnect==1){system("$php /usr/share/artica-postfix/exec.c-icap.install.php --connect");}
            if($CicapClusterConnect==0){system("$php /usr/share/artica-postfix/exec.c-icap.install.php --disconnect");}
        }
    }else{
        if(is_file("/etc/init.d/c-icap")){system("/usr/sbin/artica-phpfpm-service -uninstall-cicap");}
    }


    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableLocalUfdbCatService"))==1){
        if(!is_file("/etc/init.d/dnscatz")){system("/usr/sbin/artica-phpfpm-service -install-dncatz");}
    }else{
        if(is_file("/etc/init.d/dnscatz")){system("/usr/sbin/artica-phpfpm-service -uninstall-dncatz");}
    }

    if(intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableUfdbGuard"))==1){
        if(!is_file("/etc/init.d/ufdb")){system("$php /usr/share/artica-postfix/exec.ufdb.enable.php");}
    }else{
        if(is_file("/etc/init.d/ufdbcat")){system("$php /usr/share/artica-postfix/exec.ufdbguard.uninstall.php");}
    }


}

function OperationsOnChanges($MAINKEY){
    $unix=new unix();
    $ALREADY_OPERATED=array();
    foreach ($GLOBALS[$MAINKEY] as $key=>$OldValue){
        $action=$GLOBALS["CHANGES_ACTION"][$key];
        if(is_numeric($OldValue)){
            $newValue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
        }else{
            $newValue=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO($key));
        }

        if($OldValue==$newValue){continue;}
        if(isset($ALREADY_OPERATED[$action])){continue;}
        cluster_syslog("Execute operation on token $key changes..");
        $unix->framework_exec($action);
        $ALREADY_OPERATED[$action]=true;

    }
}

function MakeAction($tablename){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    if(preg_match("#^category_#", $tablename)){
        $GLOBALS["XACTIONS"]["$php /usr/share/artica-postfix/exec.squidguard.php --build-ufdb-smoothly"]=true;
        return;
    }
    if(preg_match("#^categoryuris_#", $tablename)){
        $GLOBALS["XACTIONS"]["$php /usr/share/artica-postfix/exec.squidguard.php --build-ufdb-smoothly"]=true;
        return;
    }

    if(!isset($GLOBALS["XTABLES"][$tablename])){return;}

    $GLOBALS["XACTIONS"][$GLOBALS["XTABLES"][$tablename]]=true;

}

function cluster_syslog($subject,$content=null,$line=0):bool{

    $file="cluster-client";
    if(!function_exists('syslog')){return false;}
    openlog($file, LOG_PID | LOG_PERROR, LOG_LOCAL0);
    if(intval($line)>0){$line=" ($line)";}
    syslog(LOG_INFO, "$subject{$line}");
    closelog();
    return true;
}


function cluster_events($prio,$subject,$content,$line=0){


    cluster_syslog($subject,$content,$line);

    $q=new lib_sqlite("/home/artica/SQLITE/clusters_events.db");
    $sql="CREATE TABLE IF NOT EXISTS `events` (
    `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
    `prio` INTEGER NOT NULL DEFAULT 2,
	`zdate` INTEGER,
	`sent` INTEGER NOT NULL DEFAULT 0,
	`subject` TEXT,
	`content` TEXT,
	`info` TEXT ) ";

    $q->QUERY_SQL($sql);
    $time=time();
    $info="Line ".$line ." file:".basename(__FILE__);
    $sql="INSERT INTO events (zdate,prio,sent,subject,content,info) VALUES('$time',$prio,0,'$subject','$content','$info');";
    $q->QUERY_SQL($sql);

}
function cluster_debug($text,$line){

    $GLOBALS["LOGS"][]=date("H:i:s")." $text [$line]";
}