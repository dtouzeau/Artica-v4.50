<?php
// Patch License on 2 Nov 2020
$GLOBALS["VERBOSE"]=false;
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
$GLOBALS["HOSTS_PATH"]="/usr/share/artica-postfix/ressources/conf/meta/hosts";
if(isset($_GET["license-events"])){SearchInLicenseSyslog();exit;}
if(isset($_GET["move-wizard-snapshot"])){move_wizard_snapshots();exit;}

if(isset($_GET["restart-webconsole-wait"])){restart_webconsole_wait();exit;}

if(isset($_GET["LkdPTEQ"])){_LkdPTEQ();exit;}

if(isset($_GET["import-acls-objects3x"])){import_acls_objects3x();exit;}
if(isset($_GET["import3x"])){import3x();exit;}

if(isset($_GET["PatchsBackupSize"])){PatchsBackupSize();exit;}
if(isset($_GET["PatchsBackupRemove"])){PatchsBackupRemove();exit;}
if(isset($_GET["snapshots-events"])){searchInSyslogSnapshots();exit;}

$f=array();
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");

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


function restart_webconsole_wait(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup /etc/init.d/artica-webconsole restart --tinypause >/tmp/reboot-console.log 2>&1 &";
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
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.importacls3x.php ".fw_shell_quote($filename)." >{$GLOBALS["LOG_FILE"]} 2>&1 &";
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
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.import3x.php ".fw_shell_quote($filename)." >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd, __FUNCTION__, __FILE__, __LINE__);
    shell_exec($cmd);
}


function _LkdPTEQ(){
    @unlink(base64_decode("L3Vzci9sb2NhbC9zaGFyZS9hcnRpY2EvLkdPTEQ="));
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
