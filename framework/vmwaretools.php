<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["status"])){status();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);



function status(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --vmtools --nowachdog >/usr/share/artica-postfix/ressources/logs/web/vmtools.status 2>&1";
	shell_exec($cmd);
	writelogs_framework($cmd);
}