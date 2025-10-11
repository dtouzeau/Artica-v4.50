<?php

if(preg_match("#--verbose#",implode(" ",$argv))){
	$GLOBALS["DEBUG"]=true;
	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);
	ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string',null);
	ini_set('error_append_string',null);
	echo "Verbose mode....\n";
}


include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}


$GLOBALS["WORKDIR"]="/home/artica/worktemp";
$GLOBALS["URISRC"]="http://downloads.jasig.org/cas";
if($argv[1]=="--latests"){latests();exit;}
install();

function install(){

		$unix=new unix();
		$wget=$unix->find_program("wget");
		$tar=$unix->find_program("tar");
		$rm=$unix->find_program("rm");
		$cp=$unix->find_program("cp");
		
		
		$targetfile=latests();
		
		if(!is_file("{$GLOBALS["WORKDIR"]}/$targetfile")){
			echo "Downloading source file `$targetfile` on base `{$GLOBALS["URISRC"]}`\n";
			shell_exec("$wget {$GLOBALS["URISRC"]}/$targetfile -O {$GLOBALS["WORKDIR"]}/$targetfile");
		}
		if(!is_file("{$GLOBALS["WORKDIR"]}/$targetfile")){echo "Failed downloading {$GLOBALS["URISRC"]}/$targetfile\n";return;}

		echo "Extracting $targetfile\n";
		shell_exec("$tar xf {$GLOBALS["WORKDIR"]}/$targetfile -C {$GLOBALS["WORKDIR"]}/");
		echo "Parsing directories {$GLOBALS["WORKDIR"]}\n";
		$dirs=$unix->dirdir($GLOBALS["WORKDIR"]);
		while (list ($num, $line) = each ($dirs)){
			$dirname=basename($num);
			if(preg_match("#^cas-server-#", $dirname)){
				$sourcedir=$num;
				break;
			}
		}
		echo "Source directory `$sourcedir`\n";
		if(!is_dir($sourcedir)){echo "Failed extracting $targetfile\n";return;}
		if(is_dir("/usr/share/cas-server")){
			echo "Cleaning old installation...\n";
			shell_exec("/bin/rm -rf /usr/share/cas-server");
		}
		@mkdir("/usr/share/cas-server",0755,true);
		echo "installing `$sourcedir` in /usr/share/cas-server\n";
		shell_exec("$cp -rf $sourcedir/* /usr/share/cas-server/");
		if(!is_file("/usr/share/cas-server/pom.xml")){echo "Failed...\n";return;}
		echo "Cleaning temp files...\n";
		shell_exec("/bin/rm -rf {$GLOBALS["WORKDIR"]}");
	
}


function Architecture(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	exec("$uname -m 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#i[0-9]86#", $val)){return 32;}
		if(preg_match("#x86_64#", $val)){return 64;}
	}
}


function latests(){
	$unix=new unix();
	$wget=$unix->find_program("wget");
	if(!is_dir($GLOBALS["WORKDIR"])){@mkdir($GLOBALS["WORKDIR"],0755,true);}
	if(!is_file("{$GLOBALS["WORKDIR"]}/index.html")){
		shell_exec("$wget {$GLOBALS["URISRC"]} -O {$GLOBALS["WORKDIR"]}/index.html");
	}
	$f=explode("\n",@file_get_contents("/tmp/index.html"));

	foreach ($f as $index=>$line){
		if(preg_match("#<a href=\".*?cas-server-([0-9\.]+)-release\.tar\.gz#", $line,$re)){
			
			$ve=$re[1];
			$SourceFile="cas-server-$ve-release.tar.gz";
			if(preg_match("#^([0-9]+)\.([0-9]+)$#", $ve,$ri)){
				if(strlen($ri[2])==1){$ri[2]="{$ri[2]}0";}
				$ve="{$ri[1]}.{$ri[2]}.00.00";
			
			}			
			
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)$#", $ve,$ri)){
				if(strlen($ri[2])==1){$ri[2]="{$ri[2]}0";}
				if(strlen($ri[3])==1){$ri[3]="{$ri[3]}0";}
				if(strlen($ri[4])==1){$ri[4]="{$ri[4]}0";}
				$ve="{$ri[1]}.{$ri[2]}.{$ri[3]}.{$ri[4]}";
				
			}
			if(preg_match("#^([0-9]+)\.([0-9]+)\.([0-9]+)$#", $ve,$ri)){
				if(strlen($ri[2])==1){$ri[2]="{$ri[2]}0";}
				if(strlen($ri[3])==1){$ri[3]="{$ri[3]}0";}
				$ve="{$ri[1]}.{$ri[2]}.{$ri[3]}.00";
			
			}			
			
			
			$ve=str_replace(".", "", $ve);
			$ve=str_replace("-", "", $ve);
			$versions[$ve]=$SourceFile;
			if($GLOBALS["VERBOSE"]){echo "$ve -> $SourceFile ({$ri[1]}.{$ri[2]}.{$ri[3]})\n";}
		}
	}
	
	krsort($versions);
	while (list ($num, $filename) = each ($versions)){
		$vv[]=$filename;
	}
	
	echo "Found latest file version: `{$vv[0]}` on base={$GLOBALS["URISRC"]}\n";
	return $vv[0];
}
?>