<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["smtp-notifs"])){smtp_notifs();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}
if(isset($_GET["status"])){status();exit;}



writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function service_cmds(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	
	exec("/etc/init.d/ufdb-http $cmds 2>&1",$results);
	writelogs_framework("artica-postfix $cmds squidguard-http ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}




function status(){
	exec(LOCATE_PHP5_BIN2()." /usr/share/artica-postfix/exec.status.php --squidguard-http --nowachdog",$results);
	writelogs_framework("/usr/share/artica-postfix/exec.status.php --squidguard-http ".count($results)." rows",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
}

function smtp_notifs(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.squidguard.smtp.php --smtp --force >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


?>