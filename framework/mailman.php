<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");


if(isset($_GET["add-list"])){addlist();exit;}
if(isset($_GET["list-info"])){listinfo();exit;}
if(isset($_GET["checks-add"])){checksadded();exit;}
if(isset($_GET["checks-created"])){checksCreated();exit;}
if(isset($_GET["lists-to-delete"])){checksForDeletion();exit;}
if(isset($_GET["check-single"])){checksSingle();exit;}
if(isset($_GET["service-status"])){service_status();exit;}
if(isset($_GET["service-cmds"])){service_cmds();exit;}

if(isset($_GET["service-start"])){service_start();exit;}
if(isset($_GET["service-stop"])){service_stop();exit;}
if(isset($_GET["chpasswd"])){chpasswd();exit;}



writelogs_framework("Unable to understand the query ".@implode(" ",$_GET),__FUNCTION__,__FILE__,__LINE__);


function addlist(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$POST=unserialize(base64_decode($_GET["content"]));
	$listname=strtolower($POST["listname_add"]);
	$domain=$POST["domain"];
	$adminmail=$POST["adminmail"];
	$urlhost=$POST["urlhost"];
	$emailhost=$POST["emailhost"];
	$password=$POST["password"];
	$cmd=trim("$nohup /usr/lib/mailman/bin/newlist --urlhost=\"$urlhost\" --emailhost=\"$emailhost\" \"$listname\" \"$adminmail\" \"$password\" 2>&1");	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	exec($cmd,$results);	
	echo "<articadatascgi>".base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function checksadded(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mailman.php --checks-added >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}

function checksSingle(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$list=$_GET["list"];
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mailman.php --checks-single $list >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
}
function checksCreated(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mailman.php --checks-created >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
}
function checksForDeletion(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mailman.php --checks-deletion >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);	
}
function service_status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php /usr/share/artica-postfix/exec.status.php --mailman --nowachdog 2>&1");
	exec($cmd,$results);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	echo "<articadatascgi>". base64_encode(@implode("\n", $results))."</articadatascgi>";	
	
}
function service_start(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/artica-postfix start mailman >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	$cmd=trim("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);			
	
}
function service_stop(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd=trim("$nohup /etc/init.d/artica-postfix stop mailman >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);
	$cmd=trim("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
}

function service_cmds(){
	$cmds=$_GET["service-cmds"];
	$results[]="Postition: $cmds";
	exec("/etc/init.d/artica-postfix $cmds mailman --verbose 2>&1",$results);
	echo "<articadatascgi>".base64_encode(serialize($results))."</articadatascgi>";
}
function chpasswd(){
	$mail=$_GET["mail"];
	$pwd=base64_decode($_GET["pwd"]);
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$array["EMAIL"]=$mail;
	$array["PWD"]=$pwd;
	$data=base64_encode(serialize($array));
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.mailman.php --chpassword \"$data\" >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
	shell_exec($cmd);		
	
	
	
}
