<?php
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

if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["events"])){ksrn_events();exit;}
if(isset($_GET["reconfigure"])){reconfigure();exit;}
if(isset($_GET["check"])){ksrn_check();exit;}

function status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --dwagent";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function restart(){
    $ARTICAP="/usr/share/artica-postfix";
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/dwagent.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dwagent.log";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.dwagent.php --restart >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function install(){
    $ARTICAP="/usr/share/artica-postfix";
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/dwagent.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dwagent.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.dwagent.php --install >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function uninstall(){
    $ARTICAP="/usr/share/artica-postfix";
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/dwagent.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dwagent.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.dwagent.php --uninstall >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function reconfigure(){
    $ARTICAP="/usr/share/artica-postfix";
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/dwagent.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/dwagent.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 $ARTICAP/exec.dwagent.php --reconfigure >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}



function ksrn_events(){
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["events"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $IN=$MAIN["IN"];
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
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/dwagent.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/ksrn.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ksrn.syslog.pattern", $search);
    shell_exec($cmd);

}