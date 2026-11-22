<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
//SP119
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["build"])){build();exit;}

$f=array();
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");

function build(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress.log";

    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.mikrotik.php --build >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function uninstall(){
    $migration=null;


    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress.log";

    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.mikrotik.php --uninstall >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function install(){
    $migration=null;


    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/mikrotik.progress.log";

    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.mikrotik.php --install >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

