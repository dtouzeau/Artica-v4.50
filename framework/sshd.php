<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install-portal"])){sshportal_install();exit;}
if(isset($_GET["uninstall-portal"])){sshportal_uninstall();exit;}
if(isset($_GET["sshportal-status"])){sshportal_status();exit;}
if(isset($_GET["sshportal-restart"])){sshportal_restart();exit;}
if(isset($_GET["sshportal-chown"])){chown_sshportal();exit;}
if(isset($_GET["sshportal-countries"])){countries_sshportal();exit;}
if(isset($_GET["sshportal-syslog"])){syslog_sshportal();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);

function chown_sshportal(){

    @chown("/home/artica/SQLITE/sshdportal.db","www-data");
    @chgrp("/home/artica/SQLITE/sshdportal.db","www-data");
}
function sshportal_status(){
    shell_exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --sshportal --nowachdog >/usr/share/artica-postfix/ressources/logs/web/sshportal.status 2>&1");
}

//
function sshportal_restart(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/sshportal.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function sshportal_uninstall(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function sshportal_install(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/sshportal.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function countries_sshportal(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.sshportal.php --countries >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function syslog_sshportal(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["sshportal-syslog"]));
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
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/sshportal.log |tail -n $max >/usr/share/artica-postfix/ressources/logs/web/sshportal.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/sshportal.syslog.pattern", $search);
    shell_exec($cmd);

}


