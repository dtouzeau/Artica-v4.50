<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["listdirs"])){listdirs();exit;}
if(isset($_GET["img"])){genimg();exit;}
if(isset($_GET["run"])){run();exit;}
if(isset($_GET["reload"])){reload();exit;}
if(isset($_GET["restart"])){restart_progress();exit;}
if(isset($_GET["systemusers"])){systemusers();exit;}
if(isset($_GET["restart-progress"])){restart_progress();exit;}
if(isset($_GET["install-tgz"])){install_tgz();exit;}
if(isset($_GET["chowndirs"])){chowndirs();exit;}
if(isset($_GET["ufdb-real"])){searchInSyslog();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);


function listdirs(){

    $dir=base64_decode($_GET["listdirs"]);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="/usr/share/artica-postfix/bin/duc ls --dirs-only --full-path --database /home/artica/philesight/system.db \"$dir\" >/usr/share/artica-postfix/ressources/logs/web/philesight.dirs 2>&1";
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}
function genimg(){
    $dir=base64_decode($_GET["img"]);
    $imagem=md5($_GET["img"]);
    $cmd="/usr/share/artica-postfix/bin/duc graph --database /home/artica/philesight/system.db --format=png --size=1024 --output=/usr/share/artica-postfix/img/philesight/$imagem.png  \"$dir\"";
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}



function run(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.dirmon.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.dirmon.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.philesight.php --run --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
   shell_exec($cmd);

}


function systemusers(){
	
	
	$f=explode("\n",@file_get_contents("/etc/passwd"));
	foreach ($f as $num=>$line){
		if(!preg_match("#(.+?):x:([0-9]+):([0-9]+):#", $line,$re)){continue;}
		$ARRAYU["{$re[2]}:{$re[3]}"]=$re[1];
		
		
	}
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SystemUsers", serialize($ARRAYU));
	@chmod("/etc/artica-postfix/settings/Daemons/SystemUsers",0755);
	
}
function searchInSyslog(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	
	$year=date("Y");
	$month=date("m");
	$day=date("d");
	
	$curfile="log.$year.$month.$day";
	
	$targetfile="/usr/share/artica-postfix/ressources/logs/3proxy.log.tmp";
	$sourceLog="/var/log/3proxy/$curfile";
	$grep=$unix->find_program("grep");
	
	$rp=intval($_GET["rp"]);
	$query=$_GET["query"];
	$cmd="$tail -n $rp $sourceLog >$targetfile 2>&1";

	if($query<>null){
		if(preg_match("#regex:(.*)#", $query,$re)){$pattern=$re[1];}else{
			$pattern=str_replace(".", "\.", $query);
			$pattern=str_replace("*", ".*?", $pattern);
			$pattern=str_replace("/", "\/", $pattern);
		}
	}
	if($pattern<>null){

		$cmd="$grep --binary-files=text -Ei \"$pattern\" $sourceLog| $tail -n $rp  >$targetfile 2>&1";
	}
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/3proxy.log.cmd", $cmd);
	shell_exec($cmd);
	@chmod("$targetfile",0755);

}
