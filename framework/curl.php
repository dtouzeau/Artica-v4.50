<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["simulate"])){simulate();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function simulate(){

        $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/curl.progress";
        $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/curl.txt";
        @unlink($ARRAY["CACHEFILE"]);
        @unlink($ARRAY["LOG_FILE"]);
        @touch($ARRAY["CACHEFILE"]);
        @touch($ARRAY["LOG_FILE"]);
        @chmod($ARRAY["CACHEFILE"],0777);
        @chmod($ARRAY["LOG_FILE"],0777);
        $unix=new unix();
        $php5=$unix->LOCATE_PHP5_BIN();
        $nohup=$unix->find_program("nohup");
        $cmd="$nohup $php5 /usr/share/artica-postfix/exec.curl.php --simulate >{$ARRAY["LOG_FILE"]} 2>&1 &";
        writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);

}