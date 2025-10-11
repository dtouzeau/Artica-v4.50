<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["validator"])){validator();exit;}
if(isset($_GET["init"])){init();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["reload-fetchmail"])){reload();exit;}
if(isset($_GET["import-compiled"])){import_fetchmail_compiled_rules();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function init(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$su=$unix->find_program("su");
	$cmd=trim("$php /usr/share/artica-postfix/exec.initslapd.php --ftp-proxy >/dev/null 2>&1");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function validator(){
   $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/ftp.validator.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/ftp.validator.logs";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.ftp.validator.php >{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function restart(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$su=$unix->find_program("su");
	$cmd=trim("$php /usr/share/artica-postfix/exec.initslapd.php --ftp-proxy --force >/dev/null 2>&1");
	shell_exec($cmd);	
	$cmd=trim("$nohup /etc/init.d/ftp-proxy restart >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	$cmd=trim("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --ftp-proxy --nowachdog 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";


}


