<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}


writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	



function install(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/cisco.reporter.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/cisco.reporter.progress.txt";

	@unlink($config["PROGRESS_FILE"]);
	@unlink($config["LOG_FILE"]);
	@touch($config["PROGRESS_FILE"]);
	@touch($config["LOG_FILE"]);
	@chmod($config["PROGRESS_FILE"],0777);
	@chmod($config["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.cisco.php --install >{$config["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function uninstall(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/cisco.reporter.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/cisco.reporter.progress.txt";

    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.cisco.php --uninstall >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

