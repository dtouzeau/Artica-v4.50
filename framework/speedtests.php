<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["execute"])){speed_execute();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["restart-recusor"])){recursor_restart();exit;}

writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);



function speed_execute(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/speedtests.execute.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/speedtests.log";
    @unlink($config["PROGRESS_FILE"]);
    @unlink($config["LOG_FILE"]);
    @touch($config["PROGRESS_FILE"]);
    @touch($config["LOG_FILE"]);
    @chmod($config["PROGRESS_FILE"],0777);
    @chmod($config["LOG_FILE"],0777);
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.testspeed.php --force >{$config["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function install(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/speedtests.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/speedtests.log";
	@unlink($config["PROGRESS_FILE"]);
	@unlink($config["LOG_FILE"]);
	@touch($config["PROGRESS_FILE"]);
	@touch($config["LOG_FILE"]);
	@chmod($config["PROGRESS_FILE"],0777);
	@chmod($config["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.testspeed.php --install >{$config["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function uninstall(){
    $config["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/speedtests.progress";
    $config["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/speedtests.log";
	@unlink($config["PROGRESS_FILE"]);
	@unlink($config["LOG_FILE"]);
	@touch($config["PROGRESS_FILE"]);
	@touch($config["LOG_FILE"]);
	@chmod($config["PROGRESS_FILE"],0777);
	@chmod($config["LOG_FILE"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.testspeed.php --uninstall >{$config["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function replic_artica_servers(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.pdns.php --replic-artica 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
	
}
function digg(){
	$unix=new unix();
	$digg=$unix->find_program("dig");
	if(!is_file($digg)){
		echo "<articadatascgi>".base64_encode(serialize(array("dig, nos such binary")))."</articadatascgi>";
		return;
	}
	
	$hostname=$_GET["hostname"];
	$interface=$_GET["interface"];
	if($interface==null){$interface="127.0.0.1";}
	if($hostname==null){$hostname="www.google.com";}
	$cmd="$digg @$interface $hostname 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
	
}
function events_query(){
	$preprend=$_GET["prepend"];
	
	$pattern=trim(base64_decode($_GET["events-query"]));
	if($pattern=="yes"){$pattern=null;}
	$pattern=str_replace("  "," ",$pattern);
	$pattern=str_replace(" ","\s+",$pattern);
	$pattern=str_replace(".","\.",$pattern);
	$pattern=str_replace("*",".+?",$pattern);
	$pattern=str_replace("/","\/",$pattern);
	$syslogpath="/var/log/unbound.log";
	$maxrows=0;

	$unix=new unix();
	$grepbin=$unix->find_program("grep");
	$tail = $unix->find_program("tail");
	if($tail==null){return;}
	if(isset($_GET["prefix"])){
		if(trim($_GET["prefix"])<>null){
			if(strpos($_GET["prefix"], ",")>0){$_GET["prefix"]="(".str_replace(",", "|", $_GET["prefix"]).")";}
			$_GET["prefix"]=str_replace("*",".*?",$_GET["prefix"]);
			$pattern="{$_GET["prefix"]}.*?\[[0-9]+\].*?$pattern";
		}
	}
	
	if($preprend<>null){
		$grep="$grepbin '$preprend'";
		if(strpos($preprend, ",")>0){$grep="$grepbin -E '(".str_replace(",", "|", $preprend).")'";}
	}
	
	writelogs_framework("Pattern \"$pattern\"" ,__FUNCTION__,__FILE__,__LINE__);
	if(isset($_GET["rp"])){$maxrows=$_GET["rp"];}
	if($maxrows==0){$maxrows=500;}
	
	
	if(strlen($pattern)>1){
		if(($preprend<>null) && (strlen($preprend)>3)){
			$preprend="'".$preprend."'";
			if(strpos($preprend, ",")>0){$preprend=" -E '(".str_replace(",", "|", $preprend).")'";}
			$grep="$grepbin $preprend|$grepbin -i -E '$pattern'";}
			else{
				$grep="$grepbin -i -E '$pattern'";
			}
	}
	
	unset($results);
	$l=$unix->FILE_TEMP();
	
	if($grep<>null){
		$cmd="$tail -n 5000 $syslogpath|$grep|$tail -n $maxrows 2>&1";
	}else{
		$cmd="$tail -n $maxrows $syslogpath 2>&1";
	}
	
	
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	if(count($results)<3){
		$maxrows=$maxrows+2000;
		if($grep<>null){
			$cmd="$tail -n 5000 $syslogpath|$grep |$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);	
	}
	
	if(count($results)<3){
		$maxrows=$maxrows+5000;
		if($grep<>null){
			$cmd="$grep $syslogpath|$tail -n $maxrows 2>&1";
		}else{
			$cmd="$tail -n $maxrows $syslogpath 2>&1";
		}
		writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
		exec($cmd,$results);	
	}	
	
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/syslog.query", @implode("\n", $results));
	@chmod("/usr/share/artica-postfix/ressources/logs/web/syslog.query", 0755);
	
}