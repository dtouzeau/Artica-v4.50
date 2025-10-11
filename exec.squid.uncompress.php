<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.computers.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");

if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}

if($argv[1]=="--key"){download_install($argv[2]);exit;}


install($argv[2]);exit;



function download_install($key){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.install.progress";
	$GLOBALS["LOG_FILE"]=PROGRESS_DIR."/squid.install.progress.txt";
	$sock=new sockets();
	$ArticaTechNetSquidRepo=unserialize(base64_decode($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaTechNetSquidRepo")));
	$array=$ArticaTechNetSquidRepo[$key];
	
	
	
	$URL=$array["URL"];
	$VERSION=$array["VERSION"];
	$FILESIZE=$array["FILESIZE"];
	$FILENAME=$array["FILENAME"];
	$MD5=$array["MD5"];
	$tarballs_file="/usr/share/artica-postfix/ressources/conf/upload/$FILENAME";
	
	echo "Url......................: $URL\n";
	echo "Version..................: $VERSION\n";
	echo "File size................: $FILESIZE\n";
	echo "Filename.................: $FILENAME\n";
	echo "MD5......................: $MD5\n";
	
	if($URL==null){
		build_progress("{downloading} $FILENAME {failed}...",110);
		exit();
	}
	build_progress("{downloading} $FILENAME {please_wait}...",5);
	$curl=new ccurl($URL);
	$curl->WriteProgress=true;
	$curl->ProgressFunction="download_progress";
	if(!$curl->GetFile($tarballs_file)){
		build_progress("{downloading} $FILENAME {failed}...",110);
		@unlink($tarballs_file);
		echo $curl->error;
		exit();
	}
	build_progress("{checking} $FILENAME {please_wait}...",9);
	
	$filesize=@filesize($tarballs_file);
	
	$md5file=md5_file($tarballs_file);
	
	echo "File size................: $filesize\n";
	echo "MD5......................: $md5file\n";
	
	if($filesize<50){
		print_r($curl->CURL_ALL_INFOS);
		echo @file_get_contents($tarballs_file);
		
	}
	
	
	if($md5file<>$MD5){
		@unlink($tarballs_file);
		echo "Md5 failed, corrupted file...\n";
		build_progress("{checking} $FILENAME {failed}...",110);
		exit();
		
	}
	install($FILENAME);
	
	
}

function download_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}
	 
	if ( $progress > $GLOBALS["previousProgress"]){
		echo "Downloading: ". $progress."%, please wait...\n";
		$GLOBALS["previousProgress"]=$progress;
		if($progress<95){
			build_progress("{downloading}  $progress%",5);
		}
	}
}


function install($filename){
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/squid.install.progress";
	$GLOBALS["LOG_FILE"]=PROGRESS_DIR."/squid.install.progress.txt";
	
	$unix=new unix();
	$LINUX_CODE_NAME=$unix->LINUX_CODE_NAME();
	$LINUX_DISTRIBUTION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("LinuxDistributionFullName");
	$LINUX_VERS=$unix->LINUX_VERS();
	$LINUX_ARCHITECTURE=$unix->LINUX_ARCHITECTURE();
	$APACHEUSER=$unix->APACHE_SRC_ACCOUNT();
	$DebianVer="debian{$LINUX_VERS[0]}";
	$TMP_DIR=$unix->TEMP_DIR();
	$ORGV=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$PATCH_VER=null;
	$tarballs_file="/usr/share/artica-postfix/ressources/conf/upload/$filename";
	echo "Package $tarballs_file\n";
	$size=filesize($tarballs_file);
	

	
	echo "Size....................: ".FormatBytes($size/1024)."\n";
		
	build_progress("Analyze...",10);
		
	echo "Current system..........: $LINUX_CODE_NAME $LINUX_DISTRIBUTION {$LINUX_VERS[0]}/{$LINUX_VERS[1]} $LINUX_ARCHITECTURE\n";
	echo "Package.................: $filename\n";
	echo "Temp dir................: $TMP_DIR\n";
	
	
	
	if(!is_file($tarballs_file)){
		echo "$tarballs_file no such file...\n";
		build_progress("No such file...",110);
		return;
	}
	echo "Uncompressing $tarballs_file...\n";
	build_progress("{extracting} $filename...",20);
	
	
	$chown=$unix->find_program("chown");
	$chmod=$unix->find_program("chmod");
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$squid=$unix->LOCATE_SQUID_BIN();
	build_progress("{extracting} $filename...",50);
	
	system("$tar xf $tarballs_file -C /");
	echo "Removing $tarballs_file...\n";
	@unlink($tarballs_file);
	shell_exec("$rm -rf /usr/share/artica-postfix/ressources/conf/upload/*");
	@unlink(dirname(__FILE__)."/ressources/logs/squid.compilation.params");
	
	
	build_progress("depmod/ldconfig...",55);
	$depmod=$unix->find_program("depmod");
	$ldconfig=$unix->find_program("ldconfig");
	system("$depmod -a");
	system("$ldconfig");

	build_progress("{restarting} Squid-cache...",60);
	system("/etc/init.d/squid restart --force");
	
	build_progress("{reconfiguring} Squid-cache...",65);
	system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
	
	build_progress("{reconfiguring} {APP_UFDBGUARD}...",70);
	system("$php /usr/share/artica-postfix/exec.squidguard.php --build --force");
	
	build_progress("{restarting} {APP_C_ICAP}...",80);
    $unix->CICAP_SERVICE_EVENTS("Restarting ICAP service",__FILE__,__LINE__);
	system("/etc/init.d/c-icap restart");
	
	
	build_progress("Refresh local versions...",90);
	system("$php /usr/share/artica-postfix/exec.status.php --squid --nowachdog");
	system("$php /usr/share/artica-postfix/exec.status.php --process1 --nowachdog");
	$squid_version=x_squid_version();
	build_progress("{success} v.$squid_version...",100);
	echo "Starting......: ".date("H:i:s")." Done you can close the screen....\n";
		
	
	
	
}
function x_squid_version(){
	$unix=new unix();
	$squidbin=$unix->LOCATE_SQUID_BIN();
	exec("$squidbin -v 2>&1",$results);
	foreach ($results as $num=>$val){
		if(preg_match("#Squid Cache: Version.*?([0-9\.\-a-z]+)#", $val,$re)){
			return trim($re[1]);
		}
	}

}

function build_progress($text,$pourc){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);

}
?>