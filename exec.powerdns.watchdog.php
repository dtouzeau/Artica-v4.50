<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');

if(isset($argv[1])) {
    if ($argv[1] == "--watch") {
        watchdog($argv[2]);
        exit();
    }
}




function watchdog($maxProcesses=50):bool{
	
	$unix=new unix();
	$pdns_server=$unix->find_program("pdns_server");
	$pdns_recursor=$unix->find_program("pdns_recursor");
	$pidof=$unix->find_program("pidof");

	
	echo "pdns_server = $pdns_server\n";
	echo "pdns_recursor = $pdns_recursor\n";
	
	
	exec("$pidof $pdns_server 2>&1",$results);
	$string=@implode("", $results);
	$exploded=@explode(" ", $string);

    foreach ($exploded as $val){if(!is_numeric($val)){echo "skip $val\n";continue;}$PIDS[$val]=$val;}
	echo count($PIDS)." processes <> $maxProcesses for $pdns_server\n";
	
	if(count($PIDS) > $maxProcesses){
		echo "Watchdog GO -> kill $pdns_server !\n";

        foreach ($PIDS as $num=>$int){
			echo "Killing $pdns_server pid $num\n";
			unix_system_kill_force($num);		
		}
		
	
	
		$PIDS=array();
		exec("$pidof $pdns_recursor 2>&1",$results);
		$string=@implode("", $results);
		$exploded=@explode(" ", $string);
        foreach ($exploded as $val){if(!is_numeric($val)){continue;}$PIDS[$val]=$val;}
		echo count($PIDS)." processes <> $maxProcesses for $pdns_recursor\n";
        foreach ($PIDS as $num=>$int){
			echo "Killing $pdns_recursor pid $num \n";
			unix_system_kill_force($num);		
		}		
		
	}
	
	
	echo "Finish\n";
	return true;
}
