<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["build"])){build_conf();exit();}
if(isset($_GET["configuration-file"])){get_conf();exit;}


function build_conf(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim($nohup." ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.ejabberd.php >/dev/null 2>&1 &");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
}
function get_conf(){
	
	echo "<articadatascgi>". base64_encode(serialize(file("/etc/ejabberd/ejabberd.cfg")))."</articadatascgi>";
	
	
}