<?php
// Post SP10 - 13/10/2026
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["logoff"])){logoff();exit;}
if(isset($_GET["disconnect"])){disconnect();exit;}
if(isset($_GET["connect"])){connect();exit;}
if(isset($_GET["pkgupgrade"])){pkgupgrade();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["syslog"])){tailscale_syslog();exit;}

function tailscale_syslog(){
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);ini_set('error_append_string',null);

    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $MAIN=unserialize(base64_decode($_GET["syslog"]));
    $PROTO_P=null;

    foreach ($MAIN as $val=>$key){

        writelogs_framework("$val, $key",__FUNCTION__,__FILE__,__LINE__);
        $MAIN[$key]=str_replace(".", "\.", $MAIN[$key]);
        $MAIN[$key]=str_replace("*", ".*?", $MAIN[$key]);

    }

    $max=intval($MAIN["MAX"]);if($max>1500){$max=1500;}
    $date=$MAIN["DATE"];
    $SRC=$MAIN["SRC"];
    $SRCPORT=$MAIN["SRCPORT"];
    if($MAIN["TERM"]<>null){$TERM=".*?{$MAIN["TERM"]}";}
    if($SRC<>null){$SRC_P=".*?\/$SRC.*?";}
    if($SRCPORT<>null){$SRCPORT_P=".*?:$SRCPORT.*?";}
    if($MAIN["C"]==0){$TERM_P=$TERM;}


    $mainline="{$TERM_P}{$SRC_P}{$PROTO_P}{$SRCPORT_P}";
    if($TERM<>null){
        if($MAIN["C"]>0){
            $mainline="($mainline|$TERM)";
        }
    }

    $search="$date.*?$mainline";
    $cmd="$grep -iE '$search' /var/log/tailscale.log |$tail -n $max >".PROGRESS_DIR."/tailscale.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

writelogs_framework("tailscale... Unable to understand the query",__FUNCTION__,__FILE__,__LINE__);
function status(){
    $unix=new unix();
    $unix->framework_exec("exec.satus.php --tailscale","tailscale.exec");
    $unix->framework_exec("exec.tailscale.php --infos","tailscale.exec");

}
function logoff(){
    $unix=new unix();
    $unix->framework_exec("exec.tailscale.php --loggoff","tailscale.loggoff");
}
function disconnect(){
    $unix=new unix();
    $unix->framework_exec("exec.tailscale.php --disconnect","tailscale.loggoff");
}
function pkgupgrade(){
    $unix=new unix();
    $unix->framework_execute("exec.tailscale.php --repository --force","tailscale.apt.progress","tailscale.apt.log");
}
function connect(){
    $unix=new unix();
    $unix->framework_exec("exec.tailscale.php --connect","tailscale.loggoff");
}
function install(){
	$unix=new unix();
	$unix->framework_execute("exec.tailscale.php --install","tailscale.enable.progress","tailscale.enable.log");
}
function uninstall(){
    $unix=new unix();
    $unix->framework_execute("exec.tailscale.php --uninstall","tailscale.enable.progress","tailscale.enable.log");
}
function restart(){
    $unix=new unix();
    $unix->framework_execute("exec.tailscale.php --restart","tailscale.restart.progress","tailscale.restart.log");
}


?>