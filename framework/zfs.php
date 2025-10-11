<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["ifZfsInstalled"])){ifZfsInstalled();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["set-backup-server"])){save_client_server();exit;}
if(isset($_GET["runingplist"])){ProcessExploreCgroups();exit;}
if(isset($_GET["ApplyCgroupConf"])){ApplyCgroupConf();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["kill-proc"])){kill_proc();exit;}
if(isset($_GET["mv-def-proc"])){mv_def_proc();exit;}
if(isset($_GET["mv-all-def-proc"])){mv_all_def_proc();exit;}
if(isset($_GET["kill-all-procs"])){kill_proc_all();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["is-cpu-rt"])){is_cpu_rt();exit;}




foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function ifZfsInstalled(){

  if(!is_file("/etc/apt/sources.list.d/zfsonlinux.list")){
  	echo "<articadatascgi>FALSE</articadatascgi>";
  	return;
  	
  }
  if(!is_file("/sbin/zpool")){
  	echo "<articadatascgi>FALSE</articadatascgi>";
  	return;
  }
  
  echo "<articadatascgi>TRUE</articadatascgi>";


}

function install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/zfs.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/zfs.install.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.zfs.php --install-progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);


}