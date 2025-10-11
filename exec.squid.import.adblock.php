<?php
$GLOBALS["RELOAD"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NO_USE_BIN"]=false;
$GLOBALS["REBUILD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_INCLUDES"]=false;
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.templates.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.ini.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::class.squid.inc\n";}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::framework/class.unix.inc\n";}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
if($GLOBALS["DEBUG_INCLUDES"]){echo basename(__FILE__)."::frame.class.inc\n";}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if($argv[1]=="--import"){Import();}
if($argv[1]=="--tophp"){ToPhp();}



function ToPhp(){
	
	$domains=Import();
	while (list ($indx, $dom) = each ($domains)){
		$count=count(explode('.',$dom));
		$dom=str_replace(".", "\.", $dom);
		if($count>2){$dom="$dom\$";}else{$dom="\.$dom\$";}
		echo "if(preg_match(\"#$dom#\", \$www)){return \"tracker\";}\n";
		
	}

	
	
}



function Import(){

	$curl=new ccurl("http://www.privacyonline.org.uk/downloads/privacyonline-btl.tpl");
	$curl->NoHTTP_POST=true;
	$curl->GetFile("/tmp/privacyonline-btl.tpl");
	$f=file("/tmp/privacyonline-btl.tpl");
	@unlink("/tmp/privacyonline-btl.tpl");
	while (list ($indx, $line) = each ($f)){
		$line=trim($line);
		$line=str_replace("\n", "", $line);
		$line=str_replace("\r", "", $line);
		if(!preg_match("#^\-d\s+(.+)#", $line,$re)){if($GLOBALS["VERBOSE"]){echo "SKIP \"$line\"\n";}continue;}
		$line=$re[1];
		if(strpos($line, " ")>0){if($GLOBALS["VERBOSE"]){echo "SKIP \"$line\"\n";}continue;}
		
		if(strpos($line, "/")>0){if($GLOBALS["VERBOSE"]){echo "SKIP \"$line\"\n";}continue;}
		$domain[$line]=$line;
		
	}
	
	return $domain;

}






