<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 

if(isset($_GET["check"])){check();exit;}
if(isset($_GET["restart"])){restart();exit;}




function check(){
	@unlink("/usr/share/artica-postfix/ressources/logs/shorewall-output");
	@touch("/usr/share/artica-postfix/ressources/logs/shorewall-output");
	@chmod("/usr/share/artica-postfix/ressources/logs/shorewall-output",0755);
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.shorewall.php --build >/usr/share/artica-postfix/ressources/logs/shorewall-output 2>&1 &");
	}
	
function restart(){
		@unlink("/usr/share/artica-postfix/ressources/logs/shorewall-output");
		@touch("/usr/share/artica-postfix/ressources/logs/shorewall-output");
		@chmod("/usr/share/artica-postfix/ressources/logs/shorewall-output",0755);
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		shell_exec("$nohup $php /usr/share/artica-postfix/exec.shorewall.php --restart >/usr/share/artica-postfix/ressources/logs/shorewall-output 2>&1 &");
	}

?>