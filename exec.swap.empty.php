<?php
$GLOBALS["FULL"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.main_cf.inc');

empty_swap();



function empty_swap(){
	
	
	$unix=new unix();
	$swapoff=$unix->find_program("swapoff");
	$swapon=$unix->find_program("swapon");
	$nohup=$unix->find_program("nohup");
	$CurrentSwap=CurrentSwap();
	
	build_progress(10, "SWAP: {$CurrentSwap}%");
	
	echo "Current swap use: {$CurrentSwap}%\n";
	$ORSWAP=$CurrentSwap;
	if($CurrentSwap==0){
		build_progress(100, "{success}");
		exit();
	}
	build_progress(15, "{empty_swap} {$CurrentSwap}%");
	shell_exec("$nohup $swapoff -a >/dev/null 2>&1 &");
	sleep(1);
	$pexists=true;
	
	$mmcount=0;
	$ORSWAP2=$ORSWAP;
	while ($pexists){
		$mmcount++;
		$pid=$unix->PIDOF($swapoff);
		if(!$unix->process_exists($pid)){break;}
		$CurrentSwap=CurrentSwap();
		if($CurrentSwap<>$ORSWAP2){
			$ORSWAP2=$CurrentSwap;
			$mmcount=0;
		}
		echo "Counter: $mmcount/20\n";
		$prc=100-$CurrentSwap;
		if($prc>95){$prc=95;}
		if($prc<15){$prc=15;}
		build_progress($prc, "{empty_swap} {$CurrentSwap}%");
		sleep(2);
		if($mmcount>20){
			echo "Timed Out!!!\n";
			$unix->KILL_PROCESS($pid,9);
			shell_exec("$swapon -a");
			build_progress(110, "{empty_swap} {failed2}");
			return ;
		}
	}
	
	shell_exec("$swapon -a");
	$CurrentSwap=CurrentSwap();
	if($CurrentSwap>$ORSWAP){
		build_progress(110, "{empty_swap} {failed2}");
		return ;
	}
	if($CurrentSwap==$ORSWAP){
		build_progress(110, "{empty_swap} {failed2}");
		return ;
	}	
	
	$ORSWAP2=$ORSWAP-10;
	if($ORSWAP2<1){$ORSWAP2=5;}
	if($CurrentSwap>$ORSWAP2){
		build_progress(110, "{empty_swap} {failed2}");
		return ;
	}	
	build_progress(100, "{empty_swap} {success}");
	
}

function CurrentSwap(){
	$pourc=0;
	$sys=new systeminfos();
	if($sys->swap_total>0){
		$pourc=round(($sys->swap_used/$sys->swap_total)*100);
	}
	return $pourc;
}
function build_progress($pourc,$text){
if(!is_dir('/usr/share/artica-postfix/ressources/logs/web')){@mkdir('/usr/share/artica-postfix/ressources/logs/web',0755,true);}
	$cachefile=PROGRESS_DIR."/system.memory.emptyswap";

	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
		echo "{$pourc}% $text\n";
		@file_put_contents($cachefile, serialize($array));
		@chmod($cachefile,0755);
		return;

	}


	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "{$pourc}% $text\n";
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
