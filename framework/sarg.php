<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["version"])){version();exit;}
if(isset($_GET["backup-test-nas"])){backup_test_nas();exit;}
if(isset($_GET["restart-web"])){restart_sarg_web();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	
function version(){
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$sarg=$unix->find_program("sarg");
	$cmd="$sarg -h 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);
	
	
	foreach ($results as $key=>$line){
		if(preg_match("#sarg-([0-9\.]+)#", $line,$re)){$version=$re[1];}
	}
	
	echo "<articadatascgi>$version</articadatascgi>";
}

function restart_sarg_web(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	if(!is_file("/etc/init.d/sarg-web")){
		$cmd="$php /usr/share/artica-postfix/exec.initslapd.php --sarg-web 2>&1";
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}
	shell_exec("$nohup /etc/init.d/sarg-web restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &");

	
}



function backup_test_nas(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.cyrus.backup.php --test-nas --verbose 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}