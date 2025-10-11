<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["compile-rules"])){compile_rules();exit;}
if(isset($_GET["artica-db-status"])){artica_databases_status();exit;}




foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function compile_rules(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --build >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	
}

function artica_databases_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS")){
		$cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.squidguard.php --artica-db-status >/dev/null 2>&1 &");
		shell_exec($cmd);
	}
	echo "<articadatascgi>". base64_encode(@file_get_contents("/etc/artica-postfix/ARTICA_WEBFILTER_DB_STATUS"))."</articadatascgi>";
	
	
}