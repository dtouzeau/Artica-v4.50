<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["artica-uninstall"])){artica_uninstall();exit;}
if(isset($_GET["artica-install"])){artica_install();exit;}
if(isset($_GET["nodes-wizard"])){nodes_wizard();exit;}
if(isset($_GET["indices"])){indices();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["remove-all"])){remove_all();exit;}
foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --elasticsearch >/usr/share/artica-postfix/ressources/logs/elasticsearch.status 2>&1";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function remove_all(){
    $unix=new unix();
    $ftmp=$unix->FILE_TEMP().".sh";
    $nohup=$unix->find_program("nohup");
    $f[]="#!/bin/sh";
    $f[]="/usr/bin/rm -rf /usr/share/elasticsearch || true";
    $f[]="/usr/bin/rm -rf /usr/share/kibana || true";
    $f[]="/usr/bin/rm -f $ftmp || true";
    $f[]="";
    @file_put_contents($ftmp,@implode("\n",$f));
    @chmod($ftmp,0755);
    shell_exec("$nohup $ftmp >/dev/null 2>&1 &");
}

function indices(){
    $unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.elastic.status.php --indices";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}


function nodes_wizard(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.nodes.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.nodes.progress.txt";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.elastic.search.php --join >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}


function reload(){
	$migration=null;


    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.elastic.search.php --reload >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}



function restart(){
	$migration=null;

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ElasticSearch.restart.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.elastic.search.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function uninstall(){
	$migration=null;


	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ElasticSearch.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ElasticSearch.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.elastic.search.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}
function install(){
	$migration=null;


	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/ElasticSearch.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/ElasticSearch.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.elastic.search.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function artica_install(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/AsElasticClient.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/AsElasticClient.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.elastic.search.php --artica-install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function artica_uninstall(){
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/AsElasticClient.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/AsElasticClient.log";

    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.elastic.search.php --artica-uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}
