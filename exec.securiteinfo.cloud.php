<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


$unix=new unix();

$filenames[]="javascript.ndb";
$filenames[]="securiteinfo.hdb";
$filenames[]="securiteinfo.ign2";
$filenames[]="securiteinfoandroid.hdb";
$filenames[]="securiteinfoascii.hdb";
$filenames[]="securiteinfohtml.hdb";
$filenames[]="spam_marketing.ndb";

$TargetDir="/home/www.artica.fr/webfilters-databases";
$MAIN=unserialize(base64_decode(@file_get_contents("$TargetDir/securiteinfo.txt")));


while (list ($index,$filename) = each ($filenames) ){
	
	$TargetFile="$TargetDir/$filename.gz";
	$SourceFilename="/home/securiteinfo/$filename";
	$filemimetime=filemtime($SourceFilename);
	echo "$filename: GZ      = $TargetFile\n";
	echo "$filename: TIME    = $filemimetime\n";
	echo "$filename: OLDTIME = {$MAIN["$filename.gz"]["TIME"]}\n";
	
	if(is_file($TargetFile)){
		if(isset($MAIN["$filename.gz"]["TIME"])){
			$OLDTIME=$MAIN["$filename.gz"]["TIME"];
			if($OLDTIME==$filemimetime){
				echo "$filename: $filemimetime (not changed )\n";
				@chown($TargetFile,"www-data");
				@chgrp($TargetFile, "www-data");
				@chmod($TargetFile,0755);
				$MAIN["$filename.gz"]["INDEXTIME"]=time();
				$MAIN["$filename.gz"]["MD5"]=md5_file($TargetFile);
				$MAIN["$filename.gz"]["SIZEGZ"]=@filesize($TargetFile);
				continue;
			}
		
		}
	}
	
	
	$filesize=filesize($SourceFilename);
	echo "$filename: Size = $filesize Bytes\n";
	
	@unlink($TargetFile);
	
	$unix->compress($SourceFilename, $TargetFile);
	if(!is_file($TargetFile)){continue;}
	$filemd5=md5_file($TargetFile);
	echo "$filename: MD5 = $filemd5\n";
	@chown($TargetFile,"www-data");
	@chgrp($TargetFile, "www-data");
	@chmod($TargetFile,0755);
	
	$MAIN["$filename.gz"]["INDEXTIME"]=time();
	$MAIN["$filename.gz"]["TARGETFILE"]=$filename;
	$MAIN["$filename.gz"]["TIME"]=$filemimetime;
	$MAIN["$filename.gz"]["MD5"]=$filemd5;
	$MAIN["$filename.gz"]["SIZEORG"]=$filesize;
	$MAIN["$filename.gz"]["SIZEGZ"]=@filesize($TargetFile);
	
}

@file_put_contents("$TargetDir/securiteinfo.txt", base64_encode(serialize($MAIN)));
@chown("$TargetDir/securiteinfo.txt","www-data");
@chgrp("$TargetDir/securiteinfo.txt", "www-data");
@chmod("$TargetDir/securiteinfo.txt",0755);