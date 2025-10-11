<?php
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');

    $maxTime=5;
    $pgrep=$unix->find_program("pgrep");
    $procs[]=$unix->find_program("id");
    $procs[]=$unix->find_program("netstat");

    $procsex[]="sleep 1";


    foreach ($procs as $process){

        $pid=$unix->PIDOF($process);
        if(!$unix->process_exists($pid)){continue;}
        $time=$unix->PROCCESS_TIME_MIN($pid);
        echo "Detected $process pid: $pid Time: {$time}Mn\n";
        if($time>$maxTime){
            posix_kill($pid,SIGKILL);
            squid_admin_mysql(0,"Killed bad process $pid $process TTL:{$time}Mn",__FILE__,__LINE__);
        }

    }
    foreach ($procsex as $pattern){
        $results=array();
        exec("$pgrep -l -f \"$pattern\" 2>&1",$results);
        foreach ($results as $line){
            $line=trim($line);
            if($line==null){continue;}
            if(preg_match("#pgrep#",$line)){continue;}
            if(!preg_match("#^([0-9]+)\s+#",$line,$re)){continue;}
            $pid=$re[1];
            $time=$unix->PROCCESS_TIME_MIN($pid);
            if($time>$maxTime){
                posix_kill($pid,SIGKILL);
                squid_admin_mysql(0,"Killed bad process $pid $pattern TTL:{$time}Mn",__FILE__,__LINE__);
            }
        }

    }
?>