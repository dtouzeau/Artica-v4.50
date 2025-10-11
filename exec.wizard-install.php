<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$ArticaLogDirSet="/etc/artica-postfix/settings/Daemons/ArticaLogDir";
if(!isset($GLOBALS["ARTICALOGDIR"])){
    $GLOBALS["ARTICALOGDIR"]=@file_get_contents($ArticaLogDirSet);
    if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix";}
}
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["NOREBOOT"]=false;

if(preg_match("#--verbose#",implode(" ",$argv))){
    $GLOBALS["VERBOSE"]=true;$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
}
if(preg_match("#noreboot#",implode(" ",$argv))){
    $GLOBALS["NOREBOOT"]=true;
}
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.resolv.conf.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.system.nics.inc");
include_once(dirname(__FILE__).'/ressources/class.identity.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.squid.inc");


if($argv[1]=="--snapshot"){restore_snapshot();exit;}
if($argv[1]=="--network"){exit;}
if($argv[1]=="--automation"){automation();exit;}
if($argv[1]=="--articaweb"){create_articaweb($argv["2"]);exit();}
if($argv[1]=="--genuid"){
    $unix=new unix();
    echo "Dynamic: ";
    echo $unix->gen_uuid()."\nCurrent: ".$unix->GetUniqueID()."\n";exit();}
if($argv[1]=="--tests-network"){testnetworks($argv["2"]);exit();}

WizardExecute();


function testnetworks(){
    $unix=new unix();
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid=@file_get_contents($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){exit();}
    @file_get_contents($pidfile,getmypid());

    shell_exec("/etc/init.d/mysql restart --force --bywizard --framework=".__FILE__);

    $users=new usersMenus();
    $q=new mysql();
    $q->BuildTables();
    $sock=new sockets();
    $savedsettings=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WizardSavedSettings")));

    if(!is_array($savedsettings)){exit();}
    if(count($savedsettings)<4){exit();}

    if($q->COUNT_ROWS("nics", "artica_backup")==0){
        WizardExecute();
        @file_put_contents("/etc/artica-postfix/TESTS_NETWORK_EXECUTED", time());
    }


}

function writeprogress($perc,$text):bool{
    $unix=new unix();
    $sourcefunction=null;
    $sourceline=null;
    $sourcefile=null;
    $unix->framework_progress($perc,$text,"wizard.progress");

    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            $sourcefile=basename($trace[1]["file"]);
            $sourcefunction=$trace[1]["function"];
            $sourceline=$trace[1]["line"];
        }

    }
    $unix->events("Progress: $perc% - $text","/var/log/artica-wizard.log",$sourcefunction,$sourceline,$sourcefile);
    return true;
}

function wizard_events($text):bool{
    $sourcefunction=null;
    $sourceline=null;
    $sourcefile=null;
    if(function_exists("debug_backtrace")){
        $trace=debug_backtrace();
        if(isset($trace[1])){
            $sourcefile=basename($trace[1]["file"]);
            $sourcefunction=$trace[1]["function"];
            $sourceline=$trace[1]["line"];
        }

    }
    $unix=new unix();
    $unix->events("$text","/var/log/artica-wizard.log",$sourcefunction,$sourceline,$sourcefile);
    return true;
}

function automation(){
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
    $sock=new sockets();
    $users=new usersMenus();
    $unix=new unix();
    @chmod("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1",0755);

    if(!is_file(PROGRESS_DIR."/AutomationScript.conf")){
        echo "AutomationScript.conf no such file...\n";
        writeprogress(110,"AutomationScript.conf no such file...");
        return;
    }

    $ipClass=new IP();
    $users=new usersMenus();
    $array=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/KerbAuthInfos")));
    if(!is_array($array)){$array=array();}
    $php=$unix->LOCATE_PHP5_BIN();

    $AutomationScript=@file_get_contents(PROGRESS_DIR."/AutomationScript.conf");
    if(preg_match("#<SQUIDCONF>(.*?)</SQUIDCONF>#is", $AutomationScript,$rz)){
        $squidconf=$rz[1];
        if(strlen($squidconf)>10){
            echo "Squid.conf = ".strlen($squidconf)." bytes\n";
            $AutomationScript=str_replace("<SQUIDCONF>{$rz[1]}</SQUIDCONF>", "", $AutomationScript);
            @file_put_contents(PROGRESS_DIR."/SquidToImport.conf", $squidconf);
            $squidconf=null;
            writeprogress(5,"Importing old Squid.conf");
            system("$php /usr/share/artica-postfix/exec.squid.import.conf.php --import \"".PROGRESS_DIR."/SquidToImport.conf\" --verbose");
            @unlink(PROGRESS_DIR."/SquidToImport.conf");
        }
    }




    $data=explode("\n",$AutomationScript);
    $WizardStatsAppliance=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/WizardStatsAppliance")));
    writeprogress(5,"Analyze configuration file...");

    foreach ($data as $num=>$ligne){
        $ligne=trim($ligne);
        if($ligne==null){continue;}

        if(preg_match("#^\##", trim($ligne))){continue;}
        if(!preg_match("#(.+?)=(.+)#", $ligne,$re)){continue;}
        $key=trim($re[1]);
        $value=trim($re[2]);
        writeprogress(5,"Parsing [$key] = \"$value\"");
        if(preg_match("#BackupSquidLogs#", $key)){$sock->SET_INFO($key, $value);}
        if($key=="caches"){ $WizardSavedSettings["CACHES"][]=$value; continue; }
        $WizardSavedSettings[$key]=$value;
        $KerbAuthInfos[$key]=$value;
    }
    writeprogress(6,"Analyze configuration file...");
    $sock->SaveConfigFile(base64_encode(serialize($WizardStatsAppliance)), "WizardStatsAppliance");
    $sock->SaveConfigFile(base64_encode(serialize($KerbAuthInfos)), "KerbAuthInfos");
    $WizardSavedSettings["ARTICAVERSION"]=$users->ARTICA_VERSION;

    if(isset($WizardSavedSettings["RootPassword"])){
        writeprogress(6,"Change ROOT Password....");
        $unix->ChangeRootPassword($WizardSavedSettings["RootPassword"]);
        unset($WizardSavedSettings["RootPassword"]);
        sleep(2);

    }

    $WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");



    if(is_numeric($WizardWebFilteringLevel)){
        $WizardSavedSettings["EnableWebFiltering"]=1;
    }


    $ProxyDNSCount=0;
    if(isset($WizardSavedSettings["EnableKerbAuth"])){
        $sock->SET_INFO("EnableKerbAuth", intval($WizardSavedSettings["EnableKerbAuth"]));
        $sock->SET_INFO("UseADAsNameServer", $WizardSavedSettings["UseADAsNameServer"]);
        $sock->SET_INFO("NtpdateAD", $WizardSavedSettings["NtpdateAD"]);
        if($WizardSavedSettings["UseADAsNameServer"]==1){
            if($ipClass->isValid($WizardSavedSettings["ADNETIPADDR"])){
                $WizardSavedSettings["DNS1"]=$WizardSavedSettings["ADNETIPADDR"];
                $q=new mysql_squid_builder();
                $q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$WizardSavedSettings["ADNETIPADDR"]}','$ProxyDNSCount')");
            }
        }

    }
    writeprogress(7,"Analyze configuration file...");
    if(isset($WizardSavedSettings["ProxyDNS"])){
        $ProxyDNS=explode(",",$WizardSavedSettings["ProxyDNS"]);
        $c=1;
        foreach ($ProxyDNS as $num=>$nameserver){
            if(!$ipClass->isValid($nameserver)){continue;}
            $ProxyDNSCount++;
            $q=new mysql_squid_builder();
            $q->QUERY_SQL("INSERT INTO dns_servers (dnsserver,zOrder) VALUES ('{$WizardSavedSettings["ADNETIPADDR"]}','$ProxyDNSCount')");
        }
    }

    if(isset($WizardSavedSettings["EnableArticaMetaClient"])){$sock->SET_INFO("EnableArticaMetaClient",$WizardSavedSettings["EnableArticaMetaClient"]);}
    if(isset($WizardSavedSettings["ArticaMetaUsername"])){$sock->SET_INFO("ArticaMetaUsername",$WizardSavedSettings["ArticaMetaUsername"]);}
    if(isset($WizardSavedSettings["ArticaMetaPassword"])){$sock->SET_INFO("ArticaMetaPassword",$WizardSavedSettings["ArticaMetaPassword"]);}
    if(isset($WizardSavedSettings["ArticaMetaHost"])){$sock->SET_INFO("ArticaMetaHost",$WizardSavedSettings["ArticaMetaHost"]);}
    if(isset($WizardSavedSettings["ArticaMetaPort"])){$sock->SET_INFO("ArticaMetaPort",$WizardSavedSettings["ArticaMetaPort"]);}






    writeprogress(8,"Analyze configuration file...");
    if(isset($WizardSavedSettings["ENABLE_PING_GATEWAY"])){
        if(!isset($WizardSavedSettings["PING_GATEWAY"])){$WizardSavedSettings["PING_GATEWAY"]=null;}
        $MonitConfig=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/SquidWatchdogMonitConfig")));
        $MonitConfig["ENABLE_PING_GATEWAY"]=$WizardSavedSettings["ENABLE_PING_GATEWAY"];
        $MonitConfig["PING_GATEWAY"]=$WizardSavedSettings["PING_GATEWAY"];
        $MonitConfig["MAX_PING_GATEWAY"]=$WizardSavedSettings["MAX_PING_GATEWAY"];
        $MonitConfig["PING_FAILED_RELOAD_NET"]=$WizardSavedSettings["PING_FAILED_RELOAD_NET"];
        $MonitConfig["PING_FAILED_REBOOT"]=$WizardSavedSettings["PING_FAILED_REBOOT"];
        $MonitConfig["PING_FAILED_REPORT"]=$WizardSavedSettings["PING_FAILED_REPORT"];
        $MonitConfig["PING_FAILED_FAILOVER"]=$WizardSavedSettings["PING_FAILED_FAILOVER"];
        $sock->SaveConfigFile(base64_encode(serialize($MonitConfig)), "SquidWatchdogMonitConfig");
    }
    writeprogress(9,"Analyze configuration file...");
    $sock->SET_INFO("timezones",$WizardSavedSettings["timezones"]);
    $nic=new system_nic();

    $Encoded=base64_encode(serialize($WizardSavedSettings));
    $sock->SaveConfigFile($Encoded,"WizardSavedSettings");

    writeprogress(10,"Analyze configuration file...");
    $TuningParameters=unserialize(base64_decode(@file_get_contents("/etc/artica-postfix/settings/Daemons/MySQLSyslogParams")));
    if(isset($WizardSavedSettings["MySQLSyslogUsername"])){$TuningParameters["username"]=$WizardSavedSettings["MySQLSyslogUsername"];}
    if(isset($WizardSavedSettings["MySQLSyslogPassword"])){$TuningParameters["password"]=$WizardSavedSettings["MySQLSyslogPassword"];}
    if(isset($WizardSavedSettings["MySQLSyslogServer"])){$TuningParameters["mysqlserver"]=$WizardSavedSettings["MySQLSyslogServer"];}
    if(isset($WizardSavedSettings["MySQLSyslogServerPort"])){$TuningParameters["RemotePort"]=$WizardSavedSettings["MySQLSyslogServerPort"];}
    if(isset($WizardSavedSettings["MySQLSyslogWorkDir"])){$TuningParameters["MySQLSyslogWorkDir"]=$WizardSavedSettings["MySQLSyslogWorkDir"];}
    if(isset($WizardSavedSettings["MySQLSyslogType"])){$TuningParameters["MySQLSyslogType"]=$WizardSavedSettings["MySQLSyslogType"];}
    $sock->SaveConfigFile(base64_encode(serialize($TuningParameters)), "MySQLSyslogParams");
    $sock->SET_INFO("MySQLSyslogType", $WizardSavedSettings["MySQLSyslogType"]);
    $sock->SET_INFO("MySQLSyslogWorkDir", $WizardSavedSettings["MySQLSyslogWorkDir"]);
    $sock->SET_INFO("EnableSyslogDB", $WizardSavedSettings["EnableSyslogDB"]);

    if(!isset($WizardSavedSettings["SquidPerformance"])){$WizardSavedSettings["SquidPerformance"]=1;}

    $sock->SET_INFO("SquidPerformance", $WizardSavedSettings["SquidPerformance"]);


    if(isset($WizardSavedSettings["EnableCNTLM"])){
        $sock->SET_INFO("EnableCNTLM", $WizardSavedSettings["EnableCNTLM"]);
        $sock->SET_INFO("CnTLMPORT", $WizardSavedSettings["CnTLMPORT"]);
    }

    if(isset($WizardSavedSettings["DisableSpecialCharacters"])){
        $sock->SET_INFO("DisableSpecialCharacters", $WizardSavedSettings["DisableSpecialCharacters"]);
    }

    if(isset($WizardSavedSettings["SambaBindInterface"])){
        $sock->SET_INFO("SambaBindInterface", $WizardSavedSettings["SambaBindInterface"]);
    }

    writeprogress(11,"Analyze configuration file...");
    if(isset($WizardSavedSettings["EnableSNMPD"])){
        $sock->SET_INFO("EnableSNMPD", $WizardSavedSettings["EnableSNMPD"]);
        $sock->SET_INFO("SNMPDCommunity", $WizardSavedSettings["SNMPDCommunity"]);
        $sock->SET_INFO("SNMPDNetwork", $WizardSavedSettings["SNMPDNetwork"]);
        $GLOBALS["CLASS_SOCKETS"]->REST_API("/snmpd/restart");
    }


    writeprogress(12,"Analyze configuration file...");
    if(isset($WizardSavedSettings["DisableArticaProxyStatistics"])){$sock->SET_INFO("DisableArticaProxyStatistics", $WizardSavedSettings["DisableArticaProxyStatistics"]);}
    if(isset($WizardSavedSettings["EnableProxyLogHostnames"])){$sock->SET_INFO("EnableProxyLogHostnames", $WizardSavedSettings["EnableProxyLogHostnames"]);}
    if(isset($WizardSavedSettings["EnableSargGenerator"])){$sock->SET_INFO("EnableSargGenerator", $WizardSavedSettings["EnableSargGenerator"]);}

    if(isset($WizardSavedSettings["CACHES"])){
        if(count($WizardSavedSettings["CACHES"])>0){

            $order=1;
            foreach ($WizardSavedSettings["CACHES"] as $index=>$ligne){
                $order++;
                $CONFCACHE=explode(",",$ligne);
                $cachename=$CONFCACHE[0];
                $CPU=$CONFCACHE[1];
                $cache_directory=$CONFCACHE[2];
                $cache_type=$CONFCACHE[3];
                $size=$CONFCACHE[4];
                $cache_dir_level1=$CONFCACHE[5];
                $cache_dir_level2=$CONFCACHE[6];
                if($cache_type=="tmpfs"){ $users=new usersMenus(); $memMB=$users->MEM_TOTAL_INSTALLEE/1024; $memMB=$memMB-1500; if($size>$memMB){ $size=$memMB-100; }}
                $q=new lib_sqlite("/home/artica/SQLITE/caches.db");
                $q->QUERY_SQL("INSERT INTO squid_caches_center
						(cachename,cpu,cache_dir,cache_type,cache_size,cache_dir_level1,cache_dir_level2,enabled,percentcache,usedcache,zOrder)
						VALUES('$cachename',$CPU,'$cache_directory','$cache_type','$size','$cache_dir_level1','$cache_dir_level2',1,0,0,$order)","artica_backup");
            }
        }
    }


    $squid=new squidbee();
    writeprogress(14,"Analyze configuration file...");
    include_once(dirname(__FILE__)."/ressources/class.squid.ports.patch.inc");
    Patch_table_squid_ports();





    writeprogress(16,"Analyze configuration file...");
    if(isset($WizardSavedSettings["ManagerAccount"])){
        if($WizardSavedSettings["ManagerAccount"]<>null){
            if($WizardSavedSettings["ManagerPassword"]<>null){
                @mkdir("/etc/artica-postfix/ldap_settings",0755,true);
                @file_put_contents("/etc/artica-postfix/ldap_settings/admin", $WizardSavedSettings["ManagerAccount"]);
                @file_put_contents("/etc/artica-postfix/ldap_settings/password", $WizardSavedSettings["ManagerPassword"]);
            }
        }

    }
    writeprogress(17,"Analyze configuration file...");

    $sock->SET_INFO("EnableUfdbGuard", 0);
    $sock->SET_INFO("EnableArpDaemon", 0);
    $sock->SET_INFO("EnablePHPFPM",0);
    $sock->SET_INFO("EnableFreeWeb",0);
    $sock->SET_INFO("SlapdThreads", 4);
    $sock->SET_INFO("AsMetaServer", 0);
    $sock->SET_INFO("WizardSavedSettingsSend", 1);
    $savedsettings["ARTICAVERSION"]=$users->ARTICA_VERSION;
    $Encoded=base64_encode(serialize($WizardSavedSettings));
    @file_put_contents("/etc/artica-postfix/settings/Daemons/WizardSavedSettings", $Encoded);
    writeprogress(18,"Analyze configuration file...{finish}");
    WizardExecute(true);

}





function WizardExecute($aspid=false){

    writeprogress(100,"{done} {please_refresh_again_this_pannel}");
    FINAL___();
    return true;
}
function restore_snapshot():bool{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $SnapShotsStorageDirectory = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SnapShotsStorageDirectory"));
    writeprogress(98,"{restore_a_snapshot}...");
    $source_file="/home/artica/wizard/snapshot/snapshot.tar.gz";
    if(!is_file($source_file)){
        writeprogress(98,"{restore_a_snapshot}...$source_file no such file");
    }
    if(is_file($source_file)) {
        if ($SnapShotsStorageDirectory == null) {
            $SnapShotsStorageDirectory = "/home/artica/snapshots";
        }
        if (!is_dir($SnapShotsStorageDirectory)) {
            @mkdir($SnapShotsStorageDirectory, 0755, true);
        }
        $target_file = "$SnapShotsStorageDirectory/snapshot.tar.gz";
        if (is_file($target_file)) {
            @unlink($target_file);
        }
        writeprogress(98, "Copy $source_file to $target_file");
        @copy($source_file, $target_file);
        @unlink($source_file);
        if(!is_file($target_file)){
            writeprogress(98, "Copy Failed!");
        }
        $nohup=$unix->find_program("nohup");
        writeprogress(98, "{restore_a_snapshot}...$target_file");
        system("$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-file snapshot.tar.gz --wizard=99 >/dev/null 2>&1 &");
    }

    return true;
}

function FINAL___(){
    $unix=new unix();
    $SQUIDEnable=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/SQUIDEnable"));
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.schedules.php --output >/dev/null 2>&1 &");
    shell_exec($cmd);
    if($SQUIDEnable==1){
        $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squid.php --build-schedules >/dev/null 2>&1 &");
        shell_exec($cmd);
    }



    $files=$unix->DirFiles("/usr/share/artica-postfix/bin");
    foreach ($files as $filename=>$line){
        @chmod("/usr/share/artica-postfix/bin/$filename",0755);
        @chown("/usr/share/artica-postfix/bin/$filename","root");
    }


    system("/etc/init.d/slapd restart --force --framework=". basename(__FILE__)."-".__LINE__);

}






function create_articaweb($websitename){}
function rebuild_vhost($servername){}
function restart_artica_status(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");

}


function EnableWebFiltering(){
    return;
    $WizardNoWebFiltering=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/WizardNoWebFiltering"));
    $AsHapProxyAppliance=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsHapProxyAppliance"));
    $AsDNSDCHPServer=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/AsDNSDCHPServer"));
    if($AsHapProxyAppliance==1){return;}
    if($AsDNSDCHPServer==1){return;}
    if($WizardNoWebFiltering==1){return;}


    $q=new mysql_squid_builder();
    $q->CheckTables();
    $unix=new unix();
    $sock=new sockets();
    writeprogress(82,"{activate_webfiltering_service} {check_tables}");
    $q->CheckTables();
    @file_put_contents("/etc/artica-postfix/settings/Daemons/EnableUfdbGuard", 1);
    $php=$unix->LOCATE_PHP5_BIN();
    $WizardWebFilteringLevel=$sock->GET_INFO("WizardWebFilteringLevel");


    $ARRAYF[0]="{block_sexual_websites}";
    $ARRAYF[1]="{block_susp_websites}";
    $ARRAYF[2]="{block_multi_websites}";
    writeprogress(82,$ARRAYF[2]);
    sleep(2);

    $array["malware"]=true;
    $array["warez"]=true;
    $array["hacking"]=true;
    $array["phishing"]=true;
    $array["spyware"]=true;

    $array["weapons"]=true;
    $array["violence"]=true;
    $array["suspicious"]=true;
    $array["paytosurf"]=true;
    $array["sect"]=true;
    $array["proxy"]=true;
    $array["gamble"]=true;
    $array["redirector"]=true;
    $array["tracker"]=true;
    $array["publicite"]=true;

    if($WizardWebFilteringLevel==0){
        $array["porn"]=true;
        $array["agressive"]=true;
        $array["dynamic"]=true;

        $array["alcohol"]=true;
        $array["astrology"]=true;
        $array["dangerous_material"]=true;
        $array["drugs"]=true;
        $array["hacking"]=true;
        $array["tattooing"]=true;
        $array["terrorism"]=true;

        $array["dating"]=true;
        $array["mixed_adult"]=true;
        $array["sex/lingerie"]=true;


        $array["marketingware"]=true;
        $array["mailing"]=true;
        $array["downloads"]=true;
        $array["gamble"]=true;
    }


    if($WizardWebFilteringLevel==1){
        $array["porn"]=true;
        $array["dating"]=true;
        $array["mixed_adult"]=true;
        $array["sex/lingerie"]=true;
    }
    if($WizardWebFilteringLevel==2){
        $array["publicite"]=true;
        $array["tracker"]=true;
        $array["marketingware"]=true;
        $array["mailing"]=true;
    }
    if($WizardWebFilteringLevel==3){
        $array["facebook"]=true;
        $array["youtube"]=true;
        $array["audio-video"]=true;
        $array["webtv"]=true;
        $array["music"]=true;
        $array["movies"]=true;
        $array["games"]=true;
        $array["gamble"]=true;
        $array["socialnet"]=true;
        $array["webradio"]=true;
        $array["chat"]=true;
        $array["webphone"]=true;
        $array["downloads"]=true;
    }
    $ruleid=0;

    writeprogress(82,"{activate_webfiltering_service}: {creating_rules}");

    foreach ($array as $key=>$val){
        $q=new mysql_squid_builder();
        $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='$key' AND modeblk=0 AND webfilter_id='$ruleid'");
        $q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ruleid','$key','0')");
        if(!$q->ok){echo $q->mysql_error_html();return;}
    }
    $q->QUERY_SQL("DELETE FROM webfilter_blks WHERE category='liste_bu' AND modeblk=1 AND webfilter_id='$ruleid'");
    $q->QUERY_SQL("INSERT IGNORE INTO webfilter_blks (webfilter_id,category,modeblk) VALUES ('$ruleid','liste_bu','1')");



    @file_put_contents("/etc/artica-postfix/settings/Daemons/SquidUrgency", 0);
    @chmod("/etc/artica-postfix/settings/Daemons/SquidUrgency",0755);

    writeprogress(82,"{activate_webfiltering_service}: {building_settings}");
    shell_exec("$php /usr/share/artica-postfix/exec.ufdb-lighthttp.php --install-web --nobuild >/dev/null 2>&1");
    shell_exec("$php /usr/share/artica-postfix/exec.ufdb.enable.php --wizard >/dev/null 2>&1");
    if(is_file("/etc/init.d/ufdb")){
        shell_exec("$php /usr/share/artica-postfix/exec.squidguard.php --build --force >/dev/null 2>&1");
    }


    writeprogress(82,"{activate_webfiltering_service}: {reconfiguring_proxy_service}");
    $unix->events("/usr/share/artica-postfix/exec.squid.php --build --force","/var/log/artica-wizard.log",__FUNCTION__,__LINE__,basename(__FILE__));
    shell_exec("$php /usr/share/artica-postfix/exec.squid.php --build --force >/dev/null 2>&1");
    $unix->events("DONE...","/var/log/artica-wizard.log",__FUNCTION__,__LINE__,basename(__FILE__));


    writeprogress(82,"{activate_webfiltering_service} {restarting_proxy_service}");
    shell_exec("/etc/init.d/squid restart --wizard");
    if(is_file("/etc/init.d/ufdb")){
        writeprogress(82,"{activate_webfiltering_service} {restarting_webfiltering_service}");
        shell_exec("/etc/init.d/ufdb restart --force");
        writeprogress(82,"{activate_webfiltering_service} {done}");
    }

    writeprogress(83,"{activate_webfiltering_service} {done}");
}