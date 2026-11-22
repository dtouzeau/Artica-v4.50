<?php
// stamp 01/11/2026
// Post SP10 - 13/10/2026
if(isset($_GET["VERBOSE"])){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string','');ini_set('error_append_string','');}
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["allstatus"])){allstatus();exit;}
if(isset($_GET["monit-status"])){status();exit;}
if(isset($_GET["monitor"])){monitor();exit;}
if(isset($_GET["restart-app"])){restart_app();exit;}
if(isset($_GET["htop"])){htop();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die(error_log("DIE ".__FILE__." Line: ".__LINE__)?"DIE":"DIE");

function allstatus(){
    $unix           = new unix();
    $monit          = $unix->find_program("monit");
    $cache_file     = PROGRESS_DIR."/monit.all.status";
    shell_exec("$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state status >$cache_file 2>&1");
}

function monitor(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$monit=$unix->find_program("monit");
	// Pentest 2026-11-04 : valeurs de requete quotees (injection shell root).
	$cmd="$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state monitor ".fw_shell_quote($_GET["monitor"])."";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function restart_app(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$monit=$unix->find_program("monit");
	// Pentest 2026-11-04 : valeurs de requete quotees (injection shell root).
	$cmd="$monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid -s /var/run/monit/monit.state restart ".fw_shell_quote($_GET["restart-app"])."";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}

function htop(){
    $tfile=PROGRESS_DIR."/htop.status";
    $opts="--black --line-fix";
    $cmd="/usr/bin/echo q | /usr/bin/htop -C | /usr/bin/aha  >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
}




function status(){
	$unix=new unix();
	$cache_file=PROGRESS_DIR."/monit.status.all";
    if(is_file($cache_file)) {
        $array = unserialize(@file_get_contents($cache_file));
        echo "<articadatascgi>" . base64_encode(serialize($array)) . "</articadatascgi>";
    }
}
