<?php
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
	include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
	include_once(dirname(__FILE__).'/framework/frame.class.inc');
	include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
	
	
	
start();

function build_progress($text,$pourc){
	echo $text."\n";
	$cachefile=PROGRESS_DIR."/squid.debug.website.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}

function start(){
	$sock=new sockets();
	$array=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WebSiteAnalysis")));
	if(!isset($array["website-analysis"])){$array["website-analysis"]="http://www.articatech.com";}
	
	build_progress("{website_analysis}:", 20);
	
	echo "Website: {$array["website-analysis"]}\n";
	
	if(!is_numeric($array["website-analysis-timeout"])){$array["website-analysis-timeout"]=2;}
	
	if($array["website-analysis-timeout"]==0){$array["website-analysis-timeout"]=2;}
	if($array["website-analysis-timeout"]>30){$array["website-analysis-timeout"]=15;}
	
	$unix=new unix();
	$curl=$unix->find_program("curl");
	
	
	$CMDS[]=$curl;
	$CMDS[]="--show-error --trace-time --trace-ascii /usr/share/artica-postfix/ressources/logs/web/curl.trace";
	$CMDS[]="--connect-timeout {$array["website-analysis-timeout"]}";
	$urls=parse_url($array["website-analysis"]);
	
	$scheme=$urls["scheme"];
	if($scheme=="https"){
		
		
	}
	
	if($array["website-analysis-address"]<>null){
		$CMDS[]="--interface {$array["website-analysis-address"]}";
	}
	
	if($array["website-analysis-proxy"]<>null){
		$CMDS[]="--proxy {$array["website-analysis-proxy"]}";
	}
	
	$CMDS[]="{$array["website-analysis"]}";
	
	$cmd=@implode(" ", $CMDS);
	build_progress("{website_analysis}: {connecting}", 50);
	system($cmd);
	build_progress("{website_analysis}: {done}", 100);
	@chmod(PROGRESS_DIR."/curl.trace",0755);
	
}
