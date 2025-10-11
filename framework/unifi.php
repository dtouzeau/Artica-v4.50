<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
include_once(dirname(__FILE__)."/class.hd.inc");
include_once(dirname(__FILE__)."/class.postfix.inc");


if(isset($_GET["status"])){status();exit;}
if(isset($_GET["is_installed"])){is_installed();exit;}
if(isset($_GET["is-installed"])){is_installed();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["GetUUID"])){GetUUID();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["version"])){version();exit;}


foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}
writelogs_framework("Unable to understand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);


function is_installed(){
	
	if(is_file("/usr/lib/unifi/lib/ace.jar")){
		echo "<articadatascgi>TRUE</articadatascgi>";
		
	}
	
	echo "<articadatascgi>FALSE</articadatascgi>";
}

function restart_progress(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/influxdb-restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/influxdb.restart.progress.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.influxdb.php --restart-progress >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function install(){
	$migration=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/unifi.install.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unifi.install.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(isset($_GET["migration"])){$migration=" --migration";}
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.unifi.php --install-progress{$migration} >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}
function restart(){
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/unifi.restart.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/unifi.restart.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.unifi.php --restart >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}
function GetUUID(){
	
	$f=explode("\n",@file_get_contents("/usr/lib/unifi/data/system.properties"));
	while (list ($key, $line) = each ($f) ){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^uuid=(.+)#", $line,$re)){continue;}
		echo "<articadatascgi>{$re[1]}</articadatascgi>";
		return;
	}
	
	
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --unifi >/usr/share/artica-postfix/ressources/logs/unifi.status");
}


function version(){
	if(isset($GLOBALS["influxdb_version"])){return $GLOBALS["influxdb_version"];}
	exec("/opt/influxdb/influxd version 2>&1",$results);
	foreach ($results as $key=>$value){
		if(preg_match("#InfluxDB v([0-9\-\.a-z]+)#", $value,$re)){
			$GLOBALS["influxdb_version"]=$re[1];
			if($GLOBALS["VERBOSE"]){echo "VERSION: $value...\n";}
			echo "<articadatascgi>{$GLOBALS["influxdb_version"]}</articadatascgi>";
			
			}
		}
		if($GLOBALS["VERBOSE"]){echo "VERSION: TRY 0.8?\n";}
		exec("/opt/influxdb/influxd -v 2>&1",$results2);
		while (list ($key, $value) = each ($results2) ){
			if(preg_match("#InfluxDB\s+v([0-9\-\.a-z]+)#", $value,$re)){
				$GLOBALS["influxdb_version"]=$re[1];
				if($GLOBALS["VERBOSE"]){echo "VERSION 0.8x: $value...\n";}
				echo "<articadatascgi>{$GLOBALS["influxdb_version"]}</articadatascgi>";
			}
		}
	
	}