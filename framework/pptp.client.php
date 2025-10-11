<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["start-vpn"])){start_vpn();exit;}
if(isset($_GET["stop-vpn"])){stop_vpn();exit;}
if(isset($_GET["remove-vpn"])){remove_vpn();exit;}
if(isset($_GET["add-vpn"])){add_vpn();exit;}
if(isset($_GET["searchlogs"])){searchlogs();exit;}
if(isset($_GET["restart-vpn"])){restart_vpn();exit;}

foreach ($_GET as $key=>$val){$tt[]="$key=$val";}
writelogs_framework("!!!! unable to understand ".@implode(" ",$tt),"MAIN",__FILE__,__LINE__);

function install(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/pptp-client.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pptp-client.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.pptp-client.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function uninstall(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/pptp-client.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/pptp-client.progress.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.pptp-client.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function status(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.pptp-client.php --status";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function restart_vpn(){
    $ID=intval($_GET["restart-vpn"]);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.pptp-client.php --build";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    $pon=$unix->find_program("pon");
    $poff=$unix->find_program("poff");
    $rm=$unix->find_program("rm");
    $filename=$unix->FILE_TEMP();
    $nohup=$unix->find_program("nohup");
    $sh[]="#!/bin/sh";
    $sh[]="$poff PPTP_{$ID} >/dev/null 2>&1";
    $sh[]="$pon PPTP_{$ID} >/dev/null 2>&1";
    $sh[]="$rm -f $filename\n";
    @file_put_contents($filename,@implode("\n",$sh));
    @chmod($filename,0755);
    writelogs_framework("$nohup $filename >/dev/null 2>&1 &",__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$nohup $filename >/dev/null 2>&1 &");
}

function add_vpn(){
    $ID=$_GET["add-vpn"];
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.pptp-client.php --build";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    $pon=$unix->find_program("pon");
    $cmd="$pon PPTP_{$ID} >/dev/null 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function start_vpn(){
    $ID=$_GET["start-vpn"];
    $unix=new unix();
    $pon=$unix->find_program("pon");
    $cmd="$pon PPTP_{$ID} >/dev/null 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function stop_vpn(){
    $ID=$_GET["stop-vpn"];
    $unix=new unix();
    $pon=$unix->find_program("poff");
    $cmd="$pon PPTP_{$ID} >/dev/null 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function remove_vpn(){
    $ID=$_GET["remove-vpn"];
    $unix=new unix();
    $pon=$unix->find_program("poff");
    $cmd="$pon PPTP_{$ID} >/dev/null 2>&1";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    if(is_file("/etc/ppp/peers/PPTP_$ID")){@unlink("/etc/ppp/peers/PPTP_$ID");}


}
function searchlogs(){
    $search=trim(base64_decode($_GET["searchlogs"]));

    writelogs_framework("Search=$search",__FUNCTION__,__FILE__,__LINE__);
    $target_file="/usr/share/artica-postfix/ressources/logs/web/pptp.log";
    $source_file="/var/log/pptp.log";

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