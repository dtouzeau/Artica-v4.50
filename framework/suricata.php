<?php
// Post SP10 - 13/10/2026

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["daemon-status"])){status();exit;}
if(isset($_GET["restart-tail"])){restart_tail();exit;}
if(isset($_GET["firewall-sid"])){firewall_sid();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");


function restart_tail(){
	$unix=new unix();
	$unix->go_exec("/etc/init.d/suricata-tail restart");
}

function status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --suricata --nowachdog >/usr/share/artica-postfix/ressources/logs/web/suricata.status");
	
}



function firewall_sid(){
	$unix=new unix();
	$sig=intval($_GET["sig"]);
	if($sig==0){
		writelogs_framework("$sig == 0 Aborting !!!!",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
    $unix->framework_exec("exec.suricata.php --firewall $sig");
}
