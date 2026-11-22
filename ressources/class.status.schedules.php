<?php
function load_stats(){
	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
	
	if(!isset($GLOBALS["CLASS_SOCKETS"])){$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	if(!isset($GLOBALS["CLASS_UNIX"])){$unix=new unix();}else{$unix=$GLOBALS["CLASS_UNIX"];}
	
	$time=time();
	$BASEDIR="/usr/share/artica-postfix";
	$hash_mem=array();
	
	
	
	$NtpdateAD=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NtpdateAD"));
	$SquidPerformance=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidPerformance"));
	if($NtpdateAD==1){$NTPDEnabled=1;}
	



	$time_file=$GLOBALS["CLASS_UNIX"]->file_time_min("/etc/artica-postfix/pids/exec.cleanfiles.php.time");
	if($time_file>120){
		shell_exec2("{$GLOBALS["nohup"]} {$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} $BASEDIR/exec.cleanfiles.php >/dev/null 2>&1 &");
	}
	
	// ex `exec.clean.logs.php --clean-tmp` (CleanLogs + Clean_tmp_path) :
	// articarest logrotate.CleanTmp, anti-rejeu de 240 min cote daemon.
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/logrotate/clean-tmp");




	
	
	
	// ex `exec.clean.logs.php --clean-space` : articarest logrotate.CleanSpaceTask
	// (/home > 90 %), anti-rejeu de 240 min cote daemon.
	$GLOBALS["CLASS_SOCKETS"]->REST_API("/logrotate/clean-space");


	$kill=$GLOBALS["CLASS_UNIX"]->find_program("kill");
	$pgrep=$GLOBALS["CLASS_UNIX"]->find_program("pgrep");

	// Deux formes de lancement d'une tache planifiee coexistent, et les DEUX
	// doivent rester sous plafond de duree :
	//  - l'ancienne voie PHP, encore presente dans les /etc/cron.d des
	//    appliances qui n'ont pas encore converge :
	//    "php exec.schedules.php --run[-squid] <ID>". A retirer quand le parc
	//    aura tourne ; plus rien ne l'ecrit depuis la tache 11 ;
	//  - la voie portee, ecrite par SystemTasks/builder.go (cronLine()) :
	//    "/usr/sbin/artica-phpfpm-service -run-systemtask <ID>" pour la famille
	//    systeme, "-run-proxytask <ID>" pour la famille proxy.
	// Depuis le portage, cron n'ecrit plus la premiere forme : sans les deux
	// motifs portes, ces taches n'auraient plus AUCUN plafond de duree, alors
	// que la voie qui les execute vient d'etre reparee.
	//
	// Le second motif colle l'executable A SON DRAPEAU, et ce n'est pas du
	// zele : /usr/sbin/artica-phpfpm-service est une COPIE du binaire du
	// daemon. Un motif plus large -- le seul chemin, ou le seul drapeau --
	// matcherait le daemon lui-meme, ou n'importe quel processus portant ce
	// chemin dans sa ligne de commande : c'est l'incident "ghost-match" deja
	// vecu sur ce produit (un pgrep -f qui matche une sous-chaine apparue dans
	// une cmdline sans rapport). Seule l'execution d'une tache porte cette
	// paire ; le daemon (`articarest -daemon`) et les autres invocations de
	// artica-phpfpm-service (-start-3proxy, -monit-check...) ne la portent pas.
	$patterns=array(
		"exec.schedules.php --run",
		"artica-phpfpm-service -run-systemtask",
		"artica-phpfpm-service -run-proxytask",
	);
	$mypid=getmypid();

	foreach ($patterns as $pattern){
		$results=array();
		exec("pgrep -l -f \"$pattern\" 2>&1",$results);
		foreach ($results as $line){
			if(preg_match("#pgrep#", $line)){continue;}
			if(!preg_match("#^([0-9]+)\s+#", $line,$re)){continue;}
			$pid=intval($re[1]);
			if($pid<2){continue;}
			if($pid==$mypid){continue;}
			// "pgrep -l" n'imprime que le NOM du processus (procps-ng >= 3.3,
			// verifie sur 4.0.2) et non sa ligne de commande : le filtre
			// "#pgrep#" ci-dessus ne peut donc PLUS voir le shell que exec()
			// vient de forker et qui porte le motif dans sa propre cmdline. On
			// relit la vraie cmdline dans /proc et on n'agit que si elle porte
			// bien le motif -- jamais si elle contient "pgrep". Un pid disparu
			// entre le pgrep et cette lecture sort de lui-meme.
			$cmdline=@file_get_contents("/proc/$pid/cmdline");
			if($cmdline===false){continue;}
			$cmdline=trim(str_replace("\0"," ",$cmdline));
			if($cmdline==""){continue;}
			if(strpos($cmdline,"pgrep")!==false){continue;}
			if(strpos($cmdline,$pattern)===false){continue;}
			$TTL=$GLOBALS["CLASS_UNIX"]->PROCCESS_TIME_MIN($pid);
			events("$pid $cmdline -> {$TTL}Mn",__FUNCTION__,__LINE__);
			if($TTL<420){continue;}
			ToSyslog("Killing stuck scheduled task PID $pid ($cmdline)");
			unix_system_kill_force($pid);
		}
	}



	events("************************ SCHEDULE ****************************",__FUNCTION__,__LINE__);
}