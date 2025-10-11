<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["status"])){status();exit;}
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){disable();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function disable(){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ipaudit.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ipaudit.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ipaudit.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

	shell_exec("$nohup $php5 /etc/init.d/artica-status restart >/dev/null 2>&1 &");
}


function install(){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/ipaudit.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/ipaudit.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ipaudit.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

	
}

function template(){

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/privoxy.template.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/privoxy.template.progress.txt";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.privoxy.php --template >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}




function status(){
	
	writelogs_framework("Starting" ,__FUNCTION__,__FILE__,__LINE__);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --ipaudit --nowachdog >/usr/share/artica-postfix/ressources/logs/web/ipaudit.status";
	writelogs_framework($cmd ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}


function events(){
	$search=trim(base64_decode($_GET["ss5events"]));
	$unix=new unix();
	$tail=$unix->find_program("tail");
	$grep=$unix->find_program("grep");
	$rp=500;
	if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}
	
		if($search==null){
	
			$cmd="$grep --binary-files=text -i -E 'Crunch:' /var/log/privoxy/privoxy.log|$tail -n $rp 2>&1";
			writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
			exec($cmd,$results);
			@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/privoxy-events", serialize($results));
	
			return;
		}
	
		$search=$unix->StringToGrep($search);
	
	
		$cmd="$grep --binary-files=text -i -E 'Crunch:' /var/log/privoxy/privoxy.log|$grep --binary-files=text -i -E '$search'|$tail -n $rp 2>&1";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		exec("$cmd",$results);
	
		@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/privoxy-events", serialize($results));
	
	
}
