<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.users.menus.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.mount.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

xdownload();

function xdownload(){
	
	
	$unix=new unix();
	build_progress("{downloading} HTML to PDF",1);
	
	$tmpfile=$unix->FILE_TEMP().".tar.gz";
	$curl=new ccurl("http://articatech.net/download/wkhtmltox-0.12.3.tar.gz");
	$curl->ProgressFunction="download_progress";
	if(!$curl->GetFile($tmpfile)){
		build_progress("{downloading} HTML to PDF {failed}",110);
		@unlink($tmpfile);
		return;
	}
	build_progress("{uncompress} HTML to PDF",96);
	$tar=$unix->find_program("tar");
	system("$tar xvf $tmpfile -C /");
	@unlink($tmpfile);
	if(!is_file("/bin/wkhtmltopdf")){
		build_progress("{uncompress} HTML to PDF {failed}",110);
		return;
	}
	build_progress("{installing} HTML to PDF {success}",100);
	
}

function build_progress($text,$pourc){
	$cachefile="/usr/share/artica-postfix/ressources/logs/wkhtmltox.install.progress";
	if(is_numeric($text)){
		$array["POURC"]=$text;
		$array["TEXT"]=$pourc;
	}else{
		$array["POURC"]=$pourc;
		$array["TEXT"]=$text;
	}
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
		echo "{$progress}% - {$downloaded_size}/$download_size\n";
		if($progress>1){
		if($progress<95){
			build_progress("{downloading} {$downloaded_size}/$download_size",$progress);
		}
		}
		$GLOBALS["previousProgress"]=$progress;
			
	}
}
