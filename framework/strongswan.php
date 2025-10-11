<?php
//SP119
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

//STRONGSWAN
if(isset($_GET["enable"])){enable_service();exit;}
if(isset($_GET["disable"])){disable_service();exit;}
if(isset($_GET["build-tunnels"])){build_tunnels();exit;}
if(isset($_GET["build-auth"])){build_auth();exit;}
if(isset($_GET["unlink-auth-file"])){auth_file_remove();exit;}
if(isset($_GET["unlink-conf-file"])){conf_file_remove();exit;}
if(isset($_GET["build-cert"])){build_cert();exit;}
if(isset($_GET["unlink-cert-file"])){cert_file_remove();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["reconfigure"])){reconfigure();exit;}
if(isset($_GET["syslog"])){strongswan_syslog();exit;}
if(isset($_GET["refresh-sessions"])){refresh_sessions();exit;}
if(isset($_GET["tunnel-status"])){tunnel_status();exit;}
if(isset($_GET["tunnel-statusall"])){tunnel_statuall();exit;}
if(isset($_GET["restart-parser"])){restart_parser();exit;}
if(isset($_GET["status-vici"])){status_vici();exit;}
if(isset($_GET["status-vici-parser"])){status_vici_parser();exit;}
writelogs_framework("Unable to understand the query",__FUNCTION__,__FILE__,__LINE__);

function reconfigure(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/strongswan.build.php";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.build.php.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /usr/sbin/artica-phpfpm-service -reconfigure-syslog 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --reconfigure >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function restart(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/strongswan.install.php";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.install.php.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.status";
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --strongswan >{$GLOBALS["LOGSFILES"]} 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function restart_parser(){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/strongswan.install.php";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.install.php.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan-stats-parser.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function status_vici(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.status";
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --strongswan-vici >{$GLOBALS["LOGSFILES"]} 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function status_vici_parser(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.status";
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --strongswan-vici-parser >{$GLOBALS["LOGSFILES"]} 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function strongswan_syslog(){
    ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

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
    $PROTO=$MAIN["PROTO"];
    $SRC=$MAIN["SRC"];
    $DST=$MAIN["DST"];
    $SRCPORT=$MAIN["SRCPORT"];
    $DSTPORT=$MAIN["DSTPORT"];
    $IN=$MAIN["IN"];
    $OUT=$MAIN["OUT"];
    $MAC=$MAIN["MAC"];
    $DAEMON=$MAIN["DAEMON"];
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
    $cmd="$grep -iE '$search' /var/log/charon.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/charon.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function refresh_sessions(){
    $unix=new unix();
    $cmd=trim(LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.strongswan.sessions.php --force >/dev/null 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function tunnel_statuall(){
    $unix=new unix();
    exec("ipsec statusall 2>&1 |grep uptime", $output, $return_var);
    file_put_contents("/etc/artica-postfix/settings/Daemons/ipsec_statusall",$output);
}

function tunnel_status(){
    $unix=new unix();
    $tname= $_GET['tunnel'];
    exec("ipsec status $tname 2>&1  | grep up", $output, $return_var);
    file_put_contents("/etc/artica-postfix/settings/Daemons/ipsec_status_$tname",$output);
}

function build_tunnels(){
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.build.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.build.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --build-tunnels  >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function build_auth(){
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.build.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.build.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --build-auth  >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function auth_file_remove(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --unlink-auth-file {$_GET["fid"]} >/dev/null 2>&1 &");
    shell_exec($cmd);
}

function cert_file_remove(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --unlink-cert-file {$_GET["name"]} >/dev/null 2>&1 &");
    shell_exec($cmd);
}

function build_cert(){

    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.build.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.build.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --build-cert {$_GET["cert-name"]} {$_GET["cn"]} {$_GET["id"]}  >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function conf_file_remove(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.strongswan.php --unlink-conf-file {$_GET["fid"]} >/dev/null 2>&1 &");
    shell_exec($cmd);
}

function enable_service(){
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.enable.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.enable.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan.enable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function disable_service(){
    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/strongswan.enable.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/strongswan.enable.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.strongswan.disable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}