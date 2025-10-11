<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");



if(isset($_GET["check"])){check();exit;}

foreach ($_GET as $num=>$ligne){$a[]="$num=$ligne";}
writelogs_framework("Unable to understand ".@implode("&",$a),__FUNCTION__,__FILE__,__LINE__);


function check(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php /usr/share/artica-postfix/exec.tftpd.php --check >/dev/null 2>&1 &";
	shell_exec($cmd);
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
}
