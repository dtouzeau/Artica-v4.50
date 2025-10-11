<?php
if(!isset($GLOBALS["BASE_ROOT"])){$GLOBALS["BASE_ROOT"]="/usr/share/artica-postfix";}

function videocache_increment_func($array){return $array;}
function videocache_is_enabled(){return 0;}
function videocache(){return null;}
function videocache_scheduler(){return null;}
function videocache_events($text,$function,$line){return null;}
function videocache_clients(){return null;}
function video_cache_initd(){return null;}

function streamsquidcache_pid(){
	$masterbin=$GLOBALS["CLASS_UNIX"]->find_program("streamsquidcache");
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/squid-stream.pid');
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	return $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN($masterbin." -f /etc/streamsquidcache/squid.conf");
}
function videocache_pid(){
	
	$pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/squid/videocache.pid');
	if($GLOBALS["VERBOSE"]){echo "APP_VIDEOCACHE_SCHEDULER: /var/run/squid/videocache.pid = $pid\n"; }
	
	if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
	$pid = $GLOBALS["CLASS_UNIX"]->PIDOF_PATTERN("python.*?vc-scheduler");
	if($GLOBALS["VERBOSE"]){echo "APP_VIDEOCACHE_SCHEDULER: PIDOF_PATTERN(python.*?vc-scheduler) = $pid\n"; }
	return $pid;
}
function videocache_clients_pids(){
	$unix=new unix();
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");
	
	$pids=array();

	exec("$pgrep -l -f videocache.py 2>&1",$f);
	foreach ($f as $index=>$line){
		if(preg_match("#pgrep#", $line)){continue;}
		if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
		$pid=$re[1];
		if($GLOBALS["VERBOSE"]){echo "-> videocache_clients_pids() -> PID:$pid\n";}
		if(is_numeric(trim($pid))){$pids[trim($pid)]=trim($pid);continue;}
		if(preg_match("#([0-9]+)#", $pid,$re)){$pids[$re[1]]=true;}
	}
	return $pids;
}
function streamsquidcache_version(){
	if(isset($GLOBALS["streamsquidcache_version"])){return $GLOBALS["streamsquidcache_version"];}
	$squidbin=$GLOBALS["CLASS_UNIX"]->find_program("streamsquidcache");
	if(!is_file($squidbin)){return "0.0.0";}
	exec("$squidbin -v 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#Squid Cache: Version\s+(.+)#", $val,$re)){
			$GLOBALS["streamsquidcache_version"]=trim($re[1]);
			return $GLOBALS["streamsquidcache_version"];
		}
	}
}