<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["global-stats"])){global_statistics();exit;}
if(isset($_GET["stop-socket"])){backend_stop();exit;}
if(isset($_GET["start-socket"])){backend_start();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function status(){
    $statsfile="/usr/share/artica-postfix/ressources/logs/web/haexchange.status";
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $cmd="$php5 /usr/share/artica-postfix/exec.status.php --ha-exchange >$statsfile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function backend_stop(){
    $unix=new unix();
    $array=base64_decode($_GET["stop-socket"]);
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");
    $cmd="$echo \"disable server $array\"|$socat stdio /var/run/ha-exchange.stat 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}
function backend_start(){
    $unix=new unix();
    $array=base64_decode($_GET["start-socket"]);
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");
    $cmd="$echo \"enable server $array\"|$socat stdio /var/run/ha-exchange.stat 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function install(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";

    @unlink($config["PROGRESS_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));

    @unlink($config["LOG_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["LOG_FILE"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.haexchnage.php --install >{$config["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function uninstall(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.haexchnage.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
function reload(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/haexchnage.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["CACHEFILE"]);
    @chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));

    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.haexchnage.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function global_statistics(){
    $unix=new unix();
    $echo=$unix->find_program("echo");
    $socat=$unix->find_program("socat");

    $cmd="$echo \"show stat\"|$socat stdio unix-connect:/var/run/ha-exchange.stat >/usr/share/artica-postfix/ressources/logs/web/haexchange.stattus.dmp 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);

}