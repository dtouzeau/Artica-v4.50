<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["export"])){export();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);


function export(){
    $table=$_GET["table"];
    $file=$_GET["file"];
    $GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/$file.$table.progress";
    $GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/$file.$table.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOG_FILE"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOG_FILE"]);
    @chmod($GLOBALS["PROGRESS_FILE"],0777);
    @chmod($GLOBALS["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.export.sqlite.php \"$file\" \"$table\" >{$GLOBALS["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}