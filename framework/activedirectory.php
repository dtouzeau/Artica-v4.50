<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["msktutils-renew-schedule"])){msktutils_renew_schedule();exit;}

if(isset($_GET["ntlm-progress"])){ntlm_join();exit;}
if(isset($_GET["kdestroy"])){kdestroy();exit;}
if(isset($_GET["ntlm-kerberos"])){krenew_ntlm();exit;}
if(isset($_GET["winbindd-events"])){winbindd_events();exit;}
if(isset($_GET["restart-smb"])){restart_smb();exit;}

function restart_smb(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.join.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.join.progress.txt";
    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.nltm.connect.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);
function ntlm_join(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.join.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/ntlm.join.log";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.nltm.connect.php >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function kdestroy(){
    $unix=new unix();
    $kdestroy=$unix->find_program("kdestroy");
    $unix->go_exec_out($kdestroy);
}
function krenew_ntlm(){
    $unix=new unix();
    $unix->framework_execute("exec.nltm.connect.php --krb5-renew",
        "krb5.renew.progress",
        "krb5.renew.log");
}









function winbindd_events(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["winbindd-events"]));
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

    if($mainline==null){
        if($date==null){
            $cmd="$tail -n $max /var/log/samba/log.winbindd >/usr/share/artica-postfix/ressources/logs/web/winbindd-events.syslog 2>&1";
            writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
            @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/winbindd-events.syslog.pattern", "*");
            shell_exec($cmd);
            return;
        }
    }


    $search="$date.*?$mainline";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/samba/log.winbindd |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/winbindd-events.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/winbindd-events.syslog.pattern", $search);
    shell_exec($cmd);

}