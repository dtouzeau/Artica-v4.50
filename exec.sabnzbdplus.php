<?php
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["debug"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;$GLOBALS["RESTART"]=true;}

if($argv[1]=="--patch"){patchbin();exit();}




function patchbin(){
	$unix=new unix();
	$sabnzbdplus=$unix->find_program("sabnzbdplus");
	if(strlen($sabnzbdplus)<5){
		echo "Starting......: ".date("H:i:s")." sabnzbdplus sabnzbdplus no such file\n";
		return;
	}
	echo "Starting......: ".date("H:i:s")." sabnzbdplus $sabnzbdplus\n";
	$f=explode("\n",@file_get_contents($sabnzbdplus));
	
	foreach ( $f as $index=>$line ){
		if(preg_match("#^import sys#",$line)){
			$nextline=$f[$index+1];
			echo "Starting......: ".date("H:i:s")." sabnzbdplus line $index\n";
			if(preg_match("#sys\.path.insert\(0#",$nextline)){
				echo "Starting......: ".date("H:i:s")." sabnzbdplus Patched OK\n";
				return;
			}else{
				echo "Starting......: ".date("H:i:s")." sabnzbdplus patching line $index\n";
				$f[$index]="import sys\nsys.path.insert(0,'/usr/share/sabnzbdplus')";
				@file_put_contents($sabnzbdplus,@implode("\n",$f));
				return;
			}	
		}		
	}

}
