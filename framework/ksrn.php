<?php
$_GET["verbose"]=1;
$GLOBALS["ARTPATH"]="/usr/share/artica-postfix";
ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){
    ini_set('display_errors', 1);
    ini_set('html_errors',0);
    ini_set('display_errors', 1);

    $GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["events"])){ksrn_events();exit;}
if(isset($_GET["trial"])){ksrn_trial();exit;}
if(isset($_GET["check"])){ksrn_check();exit;}
if(isset($_GET["dnsbl"])){dnsbl();exit;}
if(isset($_GET["sync-stats"])){sync_stats();exit;}
if(isset($_GET["whitelist"])){ksrn_whitelist();exit;}
if(isset($_GET["dnsbl-cache"])){dnsbl_cache();exit;}
if(isset($_GET["emergency"])){emergency();exit;}
if(isset($_GET["emergency-disable"])){emergency_off();exit;}
if(isset($_GET["restart-client"])){restart_client();exit;}
if(isset($_GET["install-kcloud"])){install_kcloud();exit;}
if(isset($_GET["uninstall-kcloud"])){uninstall_kcloud();exit;}

if(isset($_GET["log-file"])){log_file();exit;}

if(isset($_GET["install-categories-cache"])){install_categories_cache();exit;}
if(isset($_GET["uninstall-categories-cache"])){uninstall_categories_cache();exit;}
if(isset($_GET["restart-categories-cache"])){restart_categories_cache();exit;}
if(isset($_GET["status-categories-cache"])){status_categories_cache();exit;}
if(isset($_GET["remove-categories-cache"])){remove_categories_cache();exit;}
if(isset($_GET["import-categories-cache"])){import_categories_cache();exit;}
if(isset($_GET["go-squid-auth"])){go_squid_auth_status();exit;}
if(isset($_GET["uncategorized-events"])){uncategorized_events();exit;}

writelogs_framework("Unable to understand ".serialize($_GET),"MAIN",__FILE__,__LINE__);

function go_squid_auth_status():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.status.php --go-squid-auth");
}



function status():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.status.php --ksrn");
}
function status_categories_cache(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --categories-cache";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function log_file(){
    $unix=new unix();
    $unix->framework_execute("exec.ksrn.php --logfile","ksrn.progress", "ksrn.log");
}
function restart(){
   $unix=new unix();
    $unix->framework_execute("exec.ksrn.php --restart","ksrn.restart",
        "ksrn.log");
}

function install_categories_cache(){
    $unix=new unix();
    $unix->framework_execute("exec.categories-cache.php --install",
        "categories-cache.progress", "categories-cache.log");
}
function uninstall_categories_cache(){
    $unix=new unix();
    $unix->framework_execute("exec.categories-cache.php --uninstall",
        "categories-cache.progress",
        "categories-cache.log");
}
function restart_categories_cache(){
    $unix=new unix();
    $unix->framework_execute("exec.categories-cache.php --restart",
        "categories-cache.progress", "categories-cache.log");
}
function remove_categories_cache(){
    $unix=new unix();
    $unix->framework_execute("exec.categories-cache.php --remove-db",
        "categories-cache.progress",
        "categories-cache.log");
}
function import_categories_cache(){
    $unix=new unix();
    $unix->framework_execute("compile-category.py --to-redis",
        "ufdbcattoredis.progress",
        "ufdbcattoredis.log"
    );
}



function restart_client(){

    $unix=new unix();
    $unix->framework_execute("exec.ksrn.php --restart-client","ksrn.client.progress",
        "ksrn.client.log");
}


function ksrn_whitelist(){
    $unix=new unix();
    $ARTICAP="/usr/share/artica-postfix";
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.ksrn.statistics.php --white >/dev/null 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function sync_stats(){
    $ARTICAP="/usr/share/artica-postfix";
    $ProgressFile=PROGRESS_DIR."/ksrn-stats.progress";
    $LogFile="/usr/share/artica-postfix/ressources/logs/ksrn-stats.log";
    @unlink($ProgressFile);
    @unlink($LogFile);
    @touch($ProgressFile);
    @touch($LogFile);
    @chmod($ProgressFile,0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ProgressFile, serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.ksrn.statistics.php --force >{$LogFile} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function dnsbl_cache(){
    $ARTICAP="/usr/share/artica-postfix";
    $ProgressFile=PROGRESS_DIR."/ksrn.progress";
    $LogFile=PROGRESS_DIR."/ksrn.log";
    @unlink($ProgressFile);
    @unlink($LogFile);
    @touch($ProgressFile);
    @touch($LogFile);
    @chmod($ProgressFile,0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ProgressFile, serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.ksrn.php --dnsbl-cache >{$LogFile} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function uninstall_kcloud(){
    $unix=new unix();
    $unix->framework_execute("exec.ksrn.php --uninstall-kcloud","ksrn.progress",
        "ksrn.log");

}
function install_kcloud(){
    $unix=new unix();
    $unix->framework_execute("exec.ksrn.php --install-kcloud","ksrn.progress",
        "ksrn.log");

}

function dnsbl(){
    $ARTICAP="/usr/share/artica-postfix";
    $ProgressFile=PROGRESS_DIR."/ksrn.progress";
    $LogFile=PROGRESS_DIR."/ksrn.log";
    @unlink($ProgressFile);
    @unlink($LogFile);
    @touch($ProgressFile);
    @touch($LogFile);
    @chmod($ProgressFile,0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ProgressFile, serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.ksrn.php --dnsbl >{$LogFile} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function uninstall(){
    $ARTICAP="/usr/share/artica-postfix";
    $ProgressFile=PROGRESS_DIR."/ksrn.progress";
    $LogFile=PROGRESS_DIR."/ksrn.log";
    @unlink($ProgressFile);
    @unlink($LogFile);
    @touch($ProgressFile);
    @touch($LogFile);
    @chmod($ProgressFile,0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($ProgressFile, serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.ksrn.php --uninstall >{$LogFile} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function ksrn_trial(){
    $ARTICAP="/usr/share/artica-postfix";
    $ProgressFile=PROGRESS_DIR."/artica.k.progress";
    $LogFile=PROGRESS_DIR."/artica.k.log";
    @unlink($ProgressFile);
    @unlink($LogFile);
    @touch($ProgressFile);
    @touch($LogFile);
    @chmod($ProgressFile,0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";
    @file_put_contents($ProgressFile, serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.kcloud.php --ktrial >{$LogFile} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function ksrn_check(){
    writelogs_framework("Checking KSRN License" ,__FUNCTION__,__FILE__,__LINE__);
    $ARTICAP=$GLOBALS["ARTPATH"];
    $ProgressFile=PROGRESS_DIR."/artica.k.progress";
    $LogFile=PROGRESS_DIR."/artica.k.log";
    @unlink($ProgressFile);
    @unlink($LogFile);
    @touch($ProgressFile);
    @touch($LogFile);
    @chmod($ProgressFile,0777);
    $array["POURC"]=2;
    $array["TEXT"]="{please_wait}";
    @file_put_contents($ProgressFile, serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.kcloud.php --kinfo >{$LogFile} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function uncategorized_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $TERM=null;
    $MAIN=unserialize(base64_decode($_GET["uncategorized-events"]));
    $sourcefile="/var/log/uncategorized.log";
    $target_file=PROGRESS_DIR."/uncategoryzed.syslog";
    if(!is_file("/var/log/uncategorized.log")){
        $hostname=uname("-n");
        @file_put_contents($sourcefile,date("M d H:i:s")." $hostname uncategorized[0000]: No log here..\n");
        return false;}

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    $search="$date.*?$TERM";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' $sourcefile |$tail -n $max >$target_file 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;

}
function ksrn_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $TERM=null;
    $MAIN=unserialize(base64_decode($_GET["events"]));

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    $search="$date.*?$TERM";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/go-shield/server.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/ksrn.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents(PROGRESS_DIR."/ksrn.syslog.pattern", $search);
    shell_exec($cmd);
    return true;
}

function emergency(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 {$GLOBALS["ARTPATH"]}/exec.ksrn.php --emergency >/dev/null 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function emergency_off(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 {$GLOBALS["ARTPATH"]}/exec.ksrn.php --emergency-off >/dev/null 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}





