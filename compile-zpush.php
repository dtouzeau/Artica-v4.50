<?php
ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.ccurl.inc');

if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["DEBUG_MEM"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
}
if($GLOBALS["VERBOSE"]){ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

start();

function start(){

	$unix=new unix();
	$tmp_path=$unix->TEMP_DIR();
	_progress("Check repository",10);
	echo "Downloading index file...\n";
	$curl=new ccurl("http://www.artica.fr/auto.update.php");
	$curl->NoHTTP_POST=true;
	if(!$curl->get()){
		_progress("Check repository - FAILED",100);
		echo "$curl->error\n";
		return;
	}
	
	$ini=new Bs_IniHandler();
	$ini->loadString($curl->data);
	$couldversion=$ini->_params["NEXT"]["z-push"];
	echo "Available version = $couldversion\n";
	if($couldversion==null){
		_progress("Check repository - FAILED",100);
		echo "Corrupted index file\n";
		return;
	}

	$rm=$unix->find_program("rm");
	$SourceFile="z-push-$couldversion.tar.gz";
	$SourcePath="$tmp_path/$SourceFile";
	$SourceTemp="$tmp_path/".time();
	$InstallDir=$SourceTemp;
	
	echo "Downloading http://www.artica.fr/download/z-push-$couldversion.tar.gz\n";
	
	_progress("Downloading v.$couldversion",15);
	$curl=new ccurl("http://www.artica.fr/download/z-push-$couldversion.tar.gz");
	@unlink($SourcePath);
	if(!$curl->GetFile($SourcePath)){
		@unlink($SourcePath);
		_progress("Download $SourceFile - FAILED",100);
		echo $curl->error."\n";
		return;
	}
	
	_progress("Uncompress $SourceFile",20);
	echo "Create temp dir: $SourceTemp\n";
	echo "Uncompress $SourcePath\n";
	@mkdir("/usr/share/z-push",0755,true);
	@mkdir($SourceTemp,0755,true);
	$tar=$unix->find_program("tar");
	$cp=$unix->find_program("cp");
	$php=$unix->LOCATE_PHP5_BIN();
	shell_exec("$tar xf $SourcePath -C $SourceTemp/");
	@unlink($SourcePath);
	
	if(!is_file("$InstallDir/version.php")){
		echo "Finding directory\n";
		$DIRS=$unix->dirdir($SourceTemp);
		while (list ($num, $ligne) = each ($DIRS) ){
			if(is_file("$num/version.php")){ 
				echo "Found Directory $num\n";
				$InstallDir=$num; break; }
		}
		
	}
	
	if(!is_file("$InstallDir/version.php")){
		_progress("Failed Corrupted compressed file",100);
		shell_exec("$rm -rf $SourceTemp");
		return;
	}
	
	_progress("Installing z-Push $couldversion",50);
	shell_exec("$cp -rfd $InstallDir/* /usr/share/z-push/");
	shell_exec("$rm -rf $SourceTemp");
	_progress("Reconfiguring FreeWebs ",80);

	_progress("Success",100);
	
}


function _progress($text,$prc){
	$file="/usr/share/artica-postfix/ressources/zpush_progress.progress";
	$ARRAY["TEXT"]=$text;
	$ARRAY["POURC"]=$prc;
	@file_put_contents($file, serialize($ARRAY));
	@chmod($file,0755);

}