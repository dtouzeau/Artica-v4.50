<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");

if(isset($_GET["import-container"])){IMPORT_CONTAINER();exit;}
if(isset($_GET["backup-now"])){BACKUP_NOW();exit;}
if(isset($_GET["change-suffix"])){CHANGE_SUFFIX();exit;}
if(isset($_GET["rest-enable"])){REST_ENABLE();exit;}
if(isset($_GET["rest-disable"])){REST_DISABLE();exit;}
if(isset($_GET["import-members"])){IMPORT_MEMBERS();exit;}
if(isset($_GET["export-members"])){EXPORT_MEMBERS();exit;}
if(isset($_GET["count-members"])){COUNT_MEMBERS();exit;}
if(isset($_GET["remove-backup"])){BACKUP_REMOVE();exit;}
if(isset($_GET["searchlogs"])){searchlogs();exit;}
if(isset($_GET["join"])){join_master();exit;}
writelogs_framework("Unable to understand the query",__FUNCTION__,__FILE__,__LINE__);

function COUNT_MEMBERS(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ldap.import.members.php --count 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function join_master(){
    $unix = new unix();
    $unix->framework_execute("exec.openldap.join.php --join", "openldap.join.prog", "openldap.join.log");
}

function BACKUP_REMOVE(){
    $filename=$_GET["remove-backup"];
    $target_directory="/home/artica/ldap_backup";
    $tfile="$target_directory/$filename";
    @unlink($tfile);

}

function IMPORT_CONTAINER(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.backup.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.backup.progress.txt";
    $filename=$_GET["import-container"];
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.ldap.php --import \"$filename\" >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function REST_ENABLE(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ulogd.install.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ulogd.install.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php --rest-on 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function searchlogs(){
    $search=trim(base64_decode($_GET["searchlogs"]));

    writelogs_framework("Search=$search",__FUNCTION__,__FILE__,__LINE__);
    $target_file="/usr/share/artica-postfix/ressources/logs/web/slapd.log";
    $source_file="/var/log/slapd.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){
        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");



}

function BACKUP_NOW(){

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.backup.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.backup.progress.txt";

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.ldap.php --backup >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function REST_DISABLE(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ulogd.install.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ulogd.install.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.initslapd.php --rest-off 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function CHANGE_SUFFIX(){
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.chsuffix.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/openldap.chsuffix.progress.txt";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ldap.php --change-suffix >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}

function openvpn_syslog(){
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
    $cmd="$grep -iE '$search' /var/log/openvpn/openvpn.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/openvpn.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function RestartClients(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.openvpn.php --client-restart >/dev/null 2>&1 &");
    shell_exec($cmd);
}
function auth_rules(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.openvpn.php --auth-rules >/dev/null 2>&1 &");
    shell_exec($cmd);
}

function cdd_rules(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup ".LOCATE_PHP5_BIN2() ." /usr/share/artica-postfix/exec.openvpn.php --cdd >/dev/null 2>&1 &");
    shell_exec($cmd);
}

function RestartClientsTenir(){
    exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.openvpn.php --client-restart",$results);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}
function enable_service(){
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.enable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function rebuild_certificate() {
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.enable.php --certificate >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function disable_service(){
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.enable.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.disable.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function vpn_client_running(){
    if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;}
    $id=$_GET["is-client-running"];
    $pid=trim(@file_get_contents("/etc/artica-postfix/openvpn/clients/$id/pid"));
    $unix=new unix();
    writelogs_framework("/etc/artica-postfix/openvpn/clients/$id/pid -> $pid",__FUNCTION__,__FILE__,__LINE__);

    if($unix->process_exists($pid)){
        echo "<articadatascgi>TRUE</articadatascgi>";
        return;
    }
    writelogs_framework("$id: pid $pid",__FUNCTION__,__FILE__,__LINE__);

    exec($unix->find_program("pgrep") ." -l -f \"openvpn.+?clients\/2\/settings.ovpn\" 1>&1",$results);
    foreach ($results as $num=>$ligne){
        if(preg_match("#^([0-9]+)\s+.*openvpn#",$ligne,$re)){
            writelogs_framework("pid= preg_match= {$re[1]}",__FUNCTION__,__FILE__,__LINE__);
            echo "<articadatascgi>TRUE</articadatascgi>";
            return;
        }
    }
    writelogs_framework("$pid NOT RUNNING",__FUNCTION__,__FILE__,__LINE__);
}


function BuildWindowsClient(){
    $uid=$_GET["build-vpn-user"];
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.client.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/openvpn.client.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.openvpn.build-client.php \"$uid\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function ChangeCommonName($commonname){

    if(!is_file("/etc/artica-postfix/openvpn/openssl.cnf")){
        echo "<articadatascgi>ERROR: Unable to stat /etc/artica-postfix/openvpn/openssl.cnf</articadatascgi>";
        return false;
    }

    $tbl=explode("\n",@file_get_contents("/etc/artica-postfix/openvpn/openssl.cnf"));
    foreach ($tbl as $num=>$ligne){
        if(preg_match("#^commonName_default#",$ligne)){
            $tbl[$num]="commonName_default=\t$commonname";
        }
    }

    @file_put_contents("/etc/artica-postfix/openvpn/openssl.cnf",implode("\n",$tbl));
    return true;
}

function vpn_client_events(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $tail=$unix->find_program("tail");
    $cmd=trim("$tail -n 300 /etc/artica-postfix/openvpn/clients/{$_GET["ID"]}/log 2>&1 ");

    exec($cmd,$results);
    writelogs_framework($cmd ." ". count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
    echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function vpn_client_hup(){
    $pid=@file_get_contents("/etc/artica-postfix/openvpn/clients/{$_GET["ID"]}/pid");
    $unix=new unix();
    $kill=$unix->find_program("kill");
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.openvpn.php --client-configure-start {$_GET["ID"]} 2>&1 &");
    if($unix->process_exists($pid)){unix_system_kill_force($pid);}
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}

function vpn_client_reconfigure(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup $php /usr/share/artica-postfix/exec.openvpn.php --client-conf 2>&1 &");
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}

function IMPORT_MEMBERS(){

    $ou=$_GET["ou"];
    $gpid=$_GET["gpid"];
    $filename=$_GET["filename"];
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ldap.import.members";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ldap.import.members.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ldap.import.members.php \"$ou\" \"$gpid\" \"$filename\" >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function EXPORT_MEMBERS(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ldap.import.members";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ldap.import.members.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ldap.import.members.php --export >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}


?>