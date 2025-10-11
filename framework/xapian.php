<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["exec-mysql"])){execute_mysql();exit;}
if(isset($_GET["scanid"])){scanid();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["xapian-db-size"])){localdbsize();exit;}
if(isset($_GET["DeleteDatabasePath"])){DeleteDatabasePath();exit;}
if(isset($_GET["scan"])){scan();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["delete-all"])){delete_all();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["delete-db"])){delete_database();exit;}
if(isset($_GET["smbmount"])){smbmount();exit;}
if(isset($_GET["smbunmount"])){smbunmount();exit;}
writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);	




function execute_mysql(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.xapian.index.php --mysql-dirs >/dev/null &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}
function delete_all(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	shell_exec("$rm -rf /home/omindex-databases");
	
}
function smbmount(){
	
	include_once("/usr/share/artica-postfix/ressources/class.mount.inc");
	$array=unserialize(base64_decode($_GET["smbmount"]));
	$mountpoint=$array["m"];
	$hostname=$array["h"];
	$username=$array["u"];
	$password=$array["p"];
	$sfolder=$array["s"];
	@mkdir($mountpoint,0755,true);
	$mount=new mount();
	if(!$mount->smb_mount($mountpoint, $hostname, $username, $password, $sfolder)){
		@rmdir($mountpoint);
	}
	
}

function smbunmount(){
	$path=$_GET["smbunmount"];
	include_once("/usr/share/artica-postfix/ressources/class.mount.inc");
	$mount=new mount();
	if($mount->ismounted($path)){$mount->umount($path);}
	@rmdir($path);
}


function delete_database(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$ID=$_GET["delete-db"];
	$nohup=$unix->find_program("nohup");
	if(is_dir("/home/omindex-databases/$ID")){
		shell_exec("$nohup $rm -rf /home/omindex-databases/$ID >/dev/null 2>&1 &");
	}
	
}

function restart(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/xapian.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/xapian.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.xapian.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function scan(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/omindex.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/omindex.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.xapian.index.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function scanid(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/omindex.single.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/omindex.single.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.xapian.index.php --single {$_GET["scanid"]} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}


function status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.status.php --xapian >/usr/share/artica-postfix/ressources/logs/web/xapian.status 2>&1");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	writelogs_framework("Done",__FUNCTION__,__FILE__,__LINE__);	
}

function localdbsize(){
	$unix=new unix();
	$cachefile="/usr/share/artica-postfix/LocalDatabases/dbsize.xp";
	if(is_file($cachefile)){$time=$unix->file_time_min($cachefile);if($time>30){@unlink($cachefile);}}
	if(!is_file($cachefile)){
		$size=$unix->DIRSIZE_KO("/usr/share/artica-postfix/LocalDatabases");
		if($size>1000){@file_put_contents($cachefile, $size);}
	}else{
		$size=@file_get_contents($cachefile);
	}
	
	echo "<articadatascgi>$size</articadatascgi>";
	
}

function install(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/xapian.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/xapian.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.xapian.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}

function uninstall(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/xapian.install.prg";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/xapian.install.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.xapian.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function httptrack_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/freewebs.HTTrack.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/freewebs.HTTrack.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);$array["POURC"]=2;$array["TEXT"]="{please_wait}";@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.httptrack.php --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


function DeleteDatabasePath(){
	$DeleteDatabasePath=base64_decode($_GET["DeleteDatabasePath"]);
	if($DeleteDatabasePath==null){return;}
	if(!is_dir($DeleteDatabasePath)){return;}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$cmd=trim("$rm -rf $DeleteDatabasePath >/dev/null &");
	@unlink("/usr/share/artica-postfix/LocalDatabases/dbsize.xp");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}

function mailboxes_scan_ou(){
	
	
}