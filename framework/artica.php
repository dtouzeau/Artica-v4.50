<?php
// Patch License on 2 Nov 2020
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
$GLOBALS["HOSTS_PATH"]="/usr/share/artica-postfix/ressources/conf/meta/hosts";
if(isset($_GET["license-events"])){SearchInLicenseSyslog();exit;}
if(isset($_GET["webconsole-objects"])){webconsole_objects();exit;}
if(isset($_GET["move-wizard-snapshot"])){move_wizard_snapshots();exit;}

if(isset($_GET["webfiltering-events"])){webfiltering_events();exit;}
if(isset($_GET["snapshot-sql"])){snapshot_sql();exit;}
if(isset($_GET["snapshot"])){snapshot();exit;}
if(isset($_GET["snapshot-nomysql"])){snapshot_nomysql();exit;}
if(isset($_GET["snapshot-remove"])){snapshot_remove();exit;}
if(isset($_GET["snapshot-uploaded"])){snapshot_uploaded();exit;}
if(isset($_GET["snapshot-restore"])){snapshot_restore();exit;}
if(isset($_GET["restart-webconsole-wait"])){restart_webconsole_wait();exit;}

if(isset($_GET["echo"])){zecho();exit;}
if(isset($_GET["snapshot-retreive"])){snapshot_retreive();exit;}
if(isset($_GET["LkdPTEQ"])){_LkdPTEQ();exit;}

if(isset($_GET["import-acls-objects3x"])){import_acls_objects3x();exit;}
if(isset($_GET["import3x"])){import3x();exit;}

if(isset($_GET["test-nas"])){test_nas();exit;}
if(isset($_GET["uncompress"])){uncompress();exit;}
if(isset($_GET["save-client-config"])){save_client_config();exit;}
if(isset($_GET["set-backup-server"])){save_client_server();exit;}
if(isset($_GET["meta-server-dumpgz"])){artica_meta_server_dump_gz();exit;}
if(isset($_GET["snapshot-import"])){snapshot_import();exit;}
if(isset($_GET["lighttpd-reload"])){lighttpd_reload();exit;}
if(isset($_GET["webconsole-restart"])){webconsole_restart();exit;}
if(isset($_GET["prepare-download"])){prepare_download();exit;}
if(isset($_GET["snapshot-uploaded"])){snapshot_uploaded();exit;}
if(isset($_GET["SPVersion"])){SPVersion();exit;}
if(isset($_GET["PatchsBackupSize"])){PatchsBackupSize();exit;}
if(isset($_GET["PatchsBackupRemove"])){PatchsBackupRemove();exit;}
if(isset($_GET["snapshots-events"])){searchInSyslogSnapshots();exit;}
if(isset($_GET["autoeval"])){autoeval();exit;}
if(isset($_GET["upgrade-lts"])){upgrade_lts();exit;}

$f=array();
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function zecho()
{
    echo "OK\n";
}



function move_wizard_snapshots():bool{
    $filename=base64_decode($_GET["move-wizard-snapshot"]);
    $fullpath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    $tfile="/home/artica/wizard/snapshot/snapshot.tar.gz";
    if(!is_dir("/home/artica/wizard/snapshot")){
        @mkdir("/home/artica/wizard/snapshot",0755,true);
    }
    if(is_file($tfile)){@unlink($tfile);}
    @copy($fullpath,$tfile);
    @unlink($fullpath);
    return true;
}
function upgrade_lts():bool{
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $ll=PROGRESS_DIR."/lts.log";
    @touch($ll);
    @chown("www-data",$ll);
    shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.lts.php --upgrade >$ll 2>&1 &");
    return true;
}


function autoeval(){
    $unix=new unix();
    $unix->framework_execute("exec.web-community-filter.php --register-demo","artica.license.progress",
        "artica.license.progress.log");

}


function webconsole_objects() {
    $unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
    $unix->ToSyslog("exec.webconsole.objects.php",false,"WebConsole");
    shell_exec("$php5 /usr/share/artica-postfix/exec.webconsole.objects.php >/dev/null 2>&1");
}

function PatchsBackupSize(){
    $unix=new unix();
    $PATCH_BACKUPDIR    = "/home/artica/patchsBackup";
    if(!is_dir($PATCH_BACKUPDIR)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PatchsBackupSize",0);
        return true;
    }
    $size=$unix->DIRSIZE_BYTES($PATCH_BACKUPDIR);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PatchsBackupSize",$size);
}
function PatchsBackupRemove(){
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $PATCH_BACKUPDIR    = "/home/artica/patchsBackup";
    if(!is_dir($PATCH_BACKUPDIR)){return false;}
    shell_exec("$rm -rf $PATCH_BACKUPDIR/*");
    return true;
}


function uncompress(){
    $unix=new unix();
    $tar=$unix->find_program("tar");
    $filename=$_GET["uncompress"];
    
    $FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    
    if (!is_file($FilePath)) {
        echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
    }
    writelogs_framework("$tar -xhf $FilePath -C /usr/share/", __FUNCTION__, __FILE__, __LINE__);
    shell_exec("$tar -xhf $FilePath -C /usr/share/");
    $VERSION=@file_get_contents("/usr/share/artica-postfix/VERSION");
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup /usr/share/artica-postfix/exec.initslapd.php --force >/dev/null 2>&1 &");
    shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}: v.$VERSION")))."</articadatascgi>";
}

function SPVersion()
{
    $VERSION=trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
    if (!is_dir("/usr/share/artica-postfix/SP")) {
        @mkdir("/usr/share/artica-postfix/SP");
        if ($GLOBALS["VERBOSE"]) {
            echo "Creating directory SP<br>\n";
        }
        echo "<articadatascgi>0</articadatascgi>";
        return;
    }
    if (!is_file("/usr/share/artica-postfix/SP/$VERSION")) {
        if ($GLOBALS["VERBOSE"]) {
            echo "/usr/share/artica-postfix/SP/$VERSION no such file<br>\n";
        }
        echo "<articadatascgi>0</articadatascgi>";
        return;
    }
    $SP=intval(@file_get_contents("/usr/share/artica-postfix/SP/$VERSION"));
    echo "<articadatascgi>$SP</articadatascgi>";
}






function restart_webconsole_wait(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup /etc/init.d/artica-webconsole restart --tinypause >/tmp/reboot-console.log 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}





function snapshot_remove()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    
    $zmd5=$_GET["snapshot-remove"];
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-remove \"$zmd5\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function snapshot_restore()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    
    $zmd5=$_GET["snapshot-restore"];
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-file \"$zmd5\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function snapshot_uploaded()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.upload.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.upload.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    
    $zmd5=$_GET["snapshot-uploaded"];
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-uploaded \"$zmd5\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function snapshot_nomysql()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot --nomysql >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function prepare_download()
{
    $filename=$_GET["prepare-download"];
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --prepare-download \"$filename\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function snapshot(){
    $unix=new unix();
    $unix->framework_execute("exec.backup.artica.php --snapshot",
        "backup.artica.progress","backup.artica.progress.txt");
}
function test_nas(){
    $unix=new unix();
    $unix->framework_execute("exec.backup.artica.php --test-nas",
        "backup.test.progress","backup.test.progress.txt");
}
function snapshot_retreive()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $nodelete=null;
    if (isset($_GET["nodelete"])) {
        $nodelete=" --nodelete";
    }
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-retreive{$nodelete} >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}



function artica_meta_server_dump_gz()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.proxy.acls.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/artica-meta.proxy.acls.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.artica-meta-server.php --export-tables >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}
function import_acls_objects3x()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/import3x.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/import3x.txt";
    $filename=$_GET["import-acls-objects3x"];
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.importacls3x.php $filename >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function import3x()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/import3x.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/import3x.txt";
    $filename=$_GET["import3x"];
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $filename=$unix->shellEscapeChars($filename);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.import3x.php $filename >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}


function snapshot_import()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/snapshot.upload.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/snapshot.upload.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-import >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}


function snapshot_sql()
{
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/backup.artica.progress.txt";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0777);
    @chmod($GLOBALS["LOG_FILE"], 0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --snapshot-id {$_GET["ID"]} >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}

function save_client_config()
{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --comps >/dev/null 2>&1 &");
    writelogs_framework("$cmd", __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}
function save_client_server()
{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.amanda.php --backup-server >/dev/null 2>&1 &");
    writelogs_framework("$cmd", __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}
function _LkdPTEQ(){
    @unlink(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLkdPTEQ="));
}

function webfiltering_events()
{
    @copy("/var/log/artica-ufdb.log", "/usr/share/artica-postfix/ressources/logs/web/artica-ufdb.log");
    @chmod(0755, "/usr/share/artica-postfix/ressources/logs/web/artica-ufdb.log");
}

function lighttpd_reload()
{
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.lighttpd.php --reload >/dev/null 2>&1 &");
}

function webconsole_restart()
{
    $unix=new unix();

    $nohup=$unix->find_program("nohup");

    $f[]="#!/bin/sh";
    $f[]="/etc/init.d/artica-webconsole restart || true";
    $f[]="/etc/init.d/artica-webconsole stop || true";
    $f[]="/etc/init.d/artica-webconsole stop || true";
    $f[]="/etc/init.d/artica-webconsole start || true";
    $f[]="";

    @file_put_contents("/tmp/web-restart.sh",@implode("\n",$f));
    @chmod("/tmp/web-restart.sh", 0755);
    shell_exec("$nohup /tmp/web-restart.sh >/dev/null 2>&1 &");
}
function searchInSyslogSnapshots(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["snapshots-events"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $PROTO=$MAIN["PROTO"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $SRCPORT=$MAIN["SRCPORT"];
    $DSTPORT=$MAIN["DSTPORT"];
    $IN=$MAIN["IN"];
    $OUT=$MAIN["OUT"];
    $MAC=$MAIN["MAC"];
    $PID=$MAIN["PID"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}

    if($PID<>null){$PID_P=".*?sshd\[$PID\].*?";}
    if($IN<>null){$IN_P="(from|to)\s+.*?$IN.*?";}
    if($SRC<>null){$IN_P="(from|to)\s+.*?$SRC.*?";}
    if($DST<>null){$IN_P="(from|to)\s+.*?$DST.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$PID_P}{$TERM_P}{$IN_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }



    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/snapshots-backup.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/snapshots-backup.log 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/websnapshots-backup.log.pattern", $search);
    shell_exec($cmd);

}
function SearchInLicenseSyslog(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog"]));
    $targetfile="/var/log/license.log";
    $RFile="/usr/share/artica-postfix/ressources/logs/web/license.syslog";

    foreach ($MAIN as $val=>$key){
        $MAIN[$val]=str_replace(".", "\.", $MAIN[$val]);
        $MAIN[$val]=str_replace("*", ".*?", $MAIN[$val]);
        $MAIN[$val]=str_replace("/", "\/", $MAIN[$val]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    if($max==0){$max=100;}
    $date=$MAIN["DATE"];
    $search=$MAIN["TERM"];
    $search="$date.*?$search";
    $search=str_replace(".*?.*?",".*?",$search);


    $cmd="$grep --binary-files=text -i -E '$search' $targetfile |$tail -n $max >$RFile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/license.syslog.pattern", $search);
    shell_exec($cmd);

}
