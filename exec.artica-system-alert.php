<?php
$GLOBALS["OUTPUT"]=false;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');


xstart($argv[1],$argv[2]); 


function xstart($why,$why2){
	$unix=new unix();
	$EXEC_PID_FILE="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$EXEC_PID_TIME="/etc/artica-postfix/pids/".basename(__FILE__).".time";
	$pid=@file_get_contents($GLOBALS["EXEC_PID_FILE"]);
	if($unix->process_exists($pid)){exit();}
	$time=$unix->file_time_min($EXEC_PID_TIME);
	if($time<5){exit();}
	
	@file_put_contents($EXEC_PID_FILE, getmypid());
	@unlink($EXEC_PID_TIME);
	@file_put_contents($EXEC_PID_FILE, time());

	if($why=="SPACE"){
        $percent="{$why2}%";
        $df=$unix->find_program("df");
        exec("$df -h 2>&1",$results);
        squid_admin_mysql(0,"ALERT: Free Disk space exceed $percent",@implode("\n",$results),__FILE__,__LINE__);
        die();
    }



	
	$DIR="/home/artica/system/perf-queue/".time();
	@mkdir($DIR,0755,true);
	$echo=$unix->find_program("echo");
	$iotop=$unix->find_program("iotop");
	$python=$unix->find_program("python");
	$ps=$unix->find_program("ps");
	$sort=$unix->find_program("sort");
	$head=$unix->find_program("head");
	@file_put_contents("$DIR/time.txt", time());
	@file_put_contents("$DIR/why.txt", $why);
	@file_put_contents("$DIR/why2.txt", $why2);
    $nice=$unix->EXEC_NICE();
	if(is_file($iotop)){
		$pid=$unix->PIDOF_PATTERN("iotop");
		if(!$unix->process_exists($pid)){
            $CMDS[]="$nice $iotop -o -a -b -q -t -n 20  >$DIR/iotop.txt";
		}
	}

    echo "Writing in $DIR\n";
    $CMDS[]="$nice $python /usr/share/artica-postfix/bin/ps_mem.py >$DIR/psmem.txt 2>&1";
    $CMDS[]="$nice $ps --no-heading -eo user,pid,pcpu,args|$sort -grbk 3|$head -50 >$DIR/TOP50-CPU.txt 2>&1";
    $CMDS[]="$nice $ps --no-heading -eo user,pid,pmem,args|$sort -grbk 3|$head -50 >$DIR/TOP50-MEM.txt 2>&1";
    $CMDS[]="$nice $ps auxww  >$DIR/ALLPS.txt 2>&1";

    foreach ($CMDS as $cmd){
        echo "$cmd\n";
        shell_exec($cmd);
    }

}


