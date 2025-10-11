<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["YESCGROUP"]=true;
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__).'/ressources/class.templates.inc');
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');
$GLOBALS["SINGLE_DEBUG"]=false;
$GLOBALS["NOT_FORCE_PROXY"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["BYCRON"]=false;
$GLOBALS["OUTPUT"]=false;
$GLOBALS["CHANGED"]=false;
$GLOBALS["FORCE_NIGHTLY"]=false;
$GLOBALS["MasterIndexFile"]="/usr/share/artica-postfix/ressources/index.ini";
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--force-nightly#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;$GLOBALS["FORCE"]=true;$GLOBALS["FORCE_NIGHTLY"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;

	$GLOBALS["VERBOSE"]=true;
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," Fatal..:");
	ini_set('error_append_string',"\n");
}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--bycron#",implode(" ",$argv))){$GLOBALS["BYCRON"]=true;}
if(isset($argv[1])) {
    if ($argv[1] == "--restart-services") {
        RestartDedicatedServices(true);
        exit;
    }
    if ($argv[1] == "--rsync") {
        UpdateRsync();
        exit;
    }
    if ($argv[1] == "--patch") {
        update_patchs($argv[2]);
        exit;
    }
    if ($argv[1] == "--update-now") {
        nightly();
        exit;
    }
    if($argv[1]=="--rollback"){
        main_rollback($argv[2]);
        exit;
    }
}



function UpdateRsync():bool{
	$addr=null;
	$bwlimit=null;
	$ArticaAutoUpateRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsync"));
	if($ArticaAutoUpateRsync==0){return false;}
    $TOTALBYTES=0;
	$ArticaAutoUpateRsyncServer=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServer"));
	$ArticaAutoUpateRsyncServerPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsyncServerPort"));
	$CurlBandwith=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlBandwith"));
	$CurlTimeOut=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("CurlTimeOut"));
	$WgetBindIpAddress=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("WgetBindIpAddress"));
	if($CurlTimeOut==0){$CurlTimeOut=3600;}
	if($CurlTimeOut<720){$CurlTimeOut=3600;}
	
	$unix=new unix();
	
	$rsync=$unix->find_program("rsync");
	if(!is_file($rsync)){
		squid_admin_mysql(0, "Failed to update Artica ! rsync no such binary", null,__FILE__,__LINE__);
		return false;
	}
	
	if($CurlBandwith>0){
		$bwlimit=" --bwlimit=$CurlBandwith";
	}
	if($WgetBindIpAddress<>null){
		if($unix->NETWORK_IS_LISTEN_ADDR_EXISTS($WgetBindIpAddress)){$addr=" --address=$WgetBindIpAddress";}
	}
	
	
	$temp=$unix->FILE_TEMP();
	$cmdline="$rsync$bwlimit$addr --timeout=$CurlTimeOut -avzr --stats rsync://$ArticaAutoUpateRsyncServer:$ArticaAutoUpateRsyncServerPort/artica-postfix/ /usr/share/artica-postfix/ >$temp 2>&1";
	shell_exec($cmdline);
	
	$f=explode("\n",@file_get_contents($temp));
	@unlink($temp);
	$ERRORS=array();
	$VERSION1=@file_get_contents("/usr/share/artica-postfix/VERSION");
	$files=0;
	foreach ($f as $line){
		$line=trim($line);
		if($line==null){continue;}
		if(preg_match("#^rsync: failed#", $line)){
			squid_admin_mysql(0, "Synchronize Artica Core product failed ( see content )", $line,__FILE__,__LINE__);
			return false;
		}
		
		if(preg_match("#rsync error:(.+)#", $line,$re)){$ERRORS[]=$re[1];continue;}
		if(preg_match("#\.(php|inc)#", $line)){$files++;continue;}
		if(preg_match("#Total bytes received:\s+([0-9,\.]+)#",$line,$re)){$TOTALBYTES=intval($re[1]);}
	}
	$VERSION2=@file_get_contents("/usr/share/artica-postfix/VERSION");
	
	if($VERSION1<>$VERSION2){
		$TOTALBYTEST=FormatBytes($TOTALBYTES/1024);
		squid_admin_mysql(1, "Success update Artica to $VERSION2 with ".count($ERRORS)." error(s) ($TOTALBYTEST downloaded)", @implode("\n", $f),__FILE__,__LINE__);
		RestartDedicatedServices();
		return true;
	}
	
	if($files>0){
		squid_admin_mysql(1, "Success patched Artica with $files file(s) ".count($ERRORS)." error(s) ($TOTALBYTES downloaded)", @implode("\n", $f),__FILE__,__LINE__);
		RestartDedicatedServices();
		return true;
	}
	return false;
	
}

function build_progress_roolback($text,$pourc):bool{
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    $pfile=PROGRESS_DIR."/roolback.progress";
    @file_put_contents($pfile,serialize($array));
    @chmod($pfile,0755);
    return true;
}
function main_rollback_progress( $download_size, $downloaded_size ){
    if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

    if ( $download_size == 0 ){
        $progress = 0;
    }else{
        $progress = round( $downloaded_size * 100 / $download_size );
    }

    if ( $progress > $GLOBALS["previousProgress"]){
        if($progress>20) {
            if ($progress < 95) {
                build_progress_roolback("{downloading}", $progress);
            }
        }
        $GLOBALS["previousProgress"]=$progress;

    }
}
function main_rollback($version){
    $unix=new unix();
    $uri="http://mirror.articatech.com/official4/artica-$version.tgz";
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if ($ArticaRepoSSL==1){
        $uri="https://www.articatech.com/official4/artica-$version.tgz";
    }


    $curl=new ccurl($uri);
    $curl->ProgressFunction="main_rollback_progress";
    $temp_dir=$unix->TEMP_DIR();
    build_progress_roolback("{downloading} $version",20);
    if(!$curl->GetFile("$temp_dir/$version.tgz")){
        echo "Error: $curl->error\n";
        if(isset($GLOBALS["OutPutlogs"])){
            foreach ($GLOBALS["OutPutlogs"] as $line){
                echo "$line\n";
            }
        }
        build_progress_roolback("{downloading} {failed} Error: $curl->error",110);
        return false;
    }
    $rm=$unix->find_program("rm");
    $tar=$unix->find_program("tar");
    $chown=$unix->find_program("chown");
    $f[]="#!/bin/sh";
    $f[]="$rm -rf /usr/share/artica-postfix";
    $f[]="$tar -xf $temp_dir/$version.tgz -C /usr/share/";
    $f[]="$chown www-data:www-data /usr/share/artica-postfix";
    $f[]="$rm -f /tmp/roolback.sh\n";
    @file_put_contents("/tmp/roolback.sh",@implode("\n",$f));
    @chmod("/tmp/roolback.sh", 0755);
    build_progress_roolback("{upgrading}...",100);
    shell_exec("/tmp/roolback.sh");
    return true;

}
function _squid_admin_mysql($severity,$subject,$text,$file=null,$line=0){
	if(!function_exists("squid_admin_mysql")){return;}
	squid_admin_mysql($severity,$subject,$text,$file,$line);
}

function build_progress_manu($text,$pourc):bool{

    $unix=new unix();
    $unix->framework_progress($pourc,$text,"artica.updatemanu.progress");
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
            if(isset($trace[1]["line"])){$sourcefile=basename($trace[1]["file"]);}
            if(isset($trace[1]["function"])){$sourcefunction=$trace[1]["function"];}
            if(isset($trace[1]["line"])){$sourceline=$trace[1]["line"];}
		}

	}

	$unix->events("{$pourc}) $text","/var/log/artica.updater.log",false,$sourcefunction,$sourceline,$sourcefile);
	return true;
}


function build_progress($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"refresh.index.progress");
    return true;
}

function updater_events($text,$sourcefunction=null,$sourceline=0){
    $sourcefile=basename(__FILE__);
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			if($sourcefunction==null){$sourcefunction=$trace[1]["function"];}
			if($sourceline==0){$sourceline=$trace[1]["line"];}
		}
			
	}
	$unix=new unix();
	$unix->events("$text","/var/log/artica.updater.log",false,$sourcefunction,$sourceline,$sourcefile);
	
}

function build_progress_index($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"refresh.index.progress");
    build_progress_manu(12,"Index: $pourc% $text");
    return true;

}

function build_progress_patch($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"artica.install.progress");
    return true;

}

function CheckUserCount(){
	$unix=new unix();
	$cachefile="/etc/artica-postfix/UsersNumber";
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$nice=EXEC_NICE();
	if(!is_file($cachefile)){
			shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.samba.php --users >/dev/null 2>&1 &");
			return 0;
	}
	
	$usersN=@file_get_contents($cachefile);
	if($unix->file_time_min($cachefile)>3600){
		@unlink($cachefile);
		shell_exec("$nohup $nice $php /usr/share/artica-postfix/exec.samba.php --users >/dev/null 2>&1 &");
		return $usersN;		
	}
	return $usersN;
}
//#############################################################################

function update_find_latest_nightly():int{

    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
	$MAIN=$array["NIGHT"];
	$keyMain=0;
	foreach ($MAIN as $key=>$ligne){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;
}

function update_find_latest():int{

    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
	$MAIN=$array["OFF"];
	$keyMain=0;
	foreach ($MAIN as $key=>$ligne){
		$key=intval($key);
		if($key==0){continue;}
		if($key>$keyMain){$keyMain=$key;}
	}
	return $keyMain;		
}


function update_release(){
	$unix=new unix();
	$tmpdir=$unix->TEMP_DIR();
    $MyCurrentVersion=GetCurrentVersion();

	echo "Starting......: ".date("H:i:s")." Retreve Index file from cloud...\n";
    build_progress_manu("{downloading} .. (Line:".__LINE__.")",13);
    $key=update_find_latest();
	$MyNextVersion=$key;
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
	$OFFICIALS=$array["OFF"];
	
	$Lastest=$OFFICIALS[$key]["VERSION"];
	$MAIN_URI=$OFFICIALS[$key]["URL"];
	$MAIN_MD5=$OFFICIALS[$key]["MD5"];
	$MAIN_FILENAME=$OFFICIALS[$key]["FILENAME"];
	$uri=$MAIN_URI;
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if ($ArticaRepoSSL==1){
        $uri=str_replace("http://mirror.articatech.com", "https://www.articatech.com",$uri);
        $uri=str_replace("http://articatech.net", "https://www.articatech.com",$uri);
    }
	
	echo "Starting......: ".date("H:i:s")." Last Official release: \"$Lastest\"\n";
	echo "Starting......: ".date("H:i:s")." Last Official release: \"$MAIN_URI\"\n";
	echo "Starting......: ".date("H:i:s")." Last Official release: \"$MAIN_MD5\"\n";

	
	echo "Starting......: ".date("H:i:s")." Official release Cur:$MyCurrentVersion, Next:$MyNextVersion\n";
	if($MyNextVersion==$MyCurrentVersion){
		echo "Starting......: ".date("H:i:s")." Official release $MyCurrentVersion/$MyNextVersion \"{version-up-to-date}\"\n";
		updater_events("Official release $MyCurrentVersion/$MyNextVersion {version-up-to-date}");
		return true;
	}
	if($MyCurrentVersion>$MyNextVersion){
		echo "Starting......: ".date("H:i:s")." Official release $MyCurrentVersion/$MyNextVersion \"{version-up-to-date}\"\n";
		updater_events("Official release $MyCurrentVersion/$MyNextVersion {version-up-to-date}");
		return true;
	}

	

	_squid_admin_mysql(1,"New official release available version $Lastest",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." Official release Downloading new version $Lastest, please wait\n";
	updater_events("Downloading new version $Lastest");
	build_progress_manu("{downloading} v$Lastest (Line:".__LINE__.")",100);
	
	$ArticaFileTemp="$tmpdir/$Lastest/$MAIN_FILENAME";
	@mkdir("$tmpdir/$Lastest",0755,true);
	$curl=new ccurl($uri);
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="nightly_progress";
	$GLOBALS["DWNFILENAME"]="v$Lastest";
	$t=time();
	echo "Starting......: ".date("H:i:s")." Official release Downloading $uri\n";

    $GLOBALS["PROGRESS_TEXT"]="{downloading}";
    $GLOBALS["PROGRESS_PRC"]=20;
	
	if(!$curl->GetFile($ArticaFileTemp)){
		_squid_admin_mysql(0,"Error: Official release Unable to download latest build with error $curl->error",null,__FILE__,__LINE__);
		updater_events("Unable to download latest build with error $curl->error");
		squid_admin_mysql(2, "Unable to download latest build with error $curl->error", null, __FILE__, __LINE__);
		@unlink($ArticaFileTemp);
		return false;
	}
	
	$size=@filesize($ArticaFileTemp)/1024;
	$md5_file=md5_file($ArticaFileTemp);
	if($md5_file<>$MAIN_MD5){
		events("Corrupted file $md5_file <> $MAIN_MD5");
		build_progress_manu("{Corrupted} {failed}",110);
		exit();
	}
	
	
	echo "Starting......: ".date("H:i:s")." Official release size:{$size}KB\n";
	
	$took=$unix->distanceOfTimeInWords($t,time());
	_squid_admin_mysql(2,"$MAIN_FILENAME downloaded, {took} $took",null,__FILE__,__LINE__);
	squid_admin_mysql(2, "$MAIN_FILENAME downloaded, {took} $took", null, __FILE__, __LINE__);
	events("$MAIN_FILENAME downloaded, took $took");


	echo "Starting......: ".date("H:i:s")." Official release took $took\n";
	build_progress_manu("{installing}",100);
	if(install_package($ArticaFileTemp,$Lastest)){return true;}
	events("New Artica update v.$Lastest");
    update_patchs();
	return true;
	
}


function master_index(){
	
	@unlink($GLOBALS["MasterIndexFile"]);
	
	$ini=new iniFrameWork();
	$ini->loadFile('/etc/artica-postfix/artica-update.conf');
    if(!isset($ini->_params["AUTOUPDATE"]["uri"])){
        $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
        if ($ArticaRepoSSL==1){
            $ini->_params["AUTOUPDATE"]["uri"]="https://www.articatech.com/auto.update.php";
        } else{
            $ini->_params["AUTOUPDATE"]["uri"]="http://articatech.net/auto.update.php";
        }

    }
	$uri=$ini->_params["AUTOUPDATE"]["uri"];
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if ($ArticaRepoSSL==1){
        $uri=str_replace("http://mirror.articatech.com", "https://www.articatech.com",$uri);
        $uri=str_replace("http://articatech.net", "https://www.articatech.com",$uri);
    }
	$arrayURI=parse_url($uri);
	echo "Starting......: ".date("H:i:s")." Refreshing index file...\n";
	
	$curl=new ccurl("$uri?time=".time());
	if(!$curl->GetFile($GLOBALS["MasterIndexFile"])){
	_squid_admin_mysql(0,"Error $curl->error",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." Error $curl->error_num;$curl->error, Try direct\n";
	
	if(!$GLOBALS["NOT_FORCE_PROXY"]){
		echo "Starting......: ".date("H:i:s")." FATAL: Unable to download index file, try in direct mode\n";
		$GLOBALS["NOT_FORCE_PROXY"]=true;
		return master_index();
	}
	
	
	if($curl->error=="{CURLE_COULDNT_RESOLVE_HOST}"){
			if($arrayURI["host"]=="www.artica.fr"){
				if(!$GLOBALS["CHANGED"]){
                    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
                    if ($ArticaRepoSSL==1){
                        echo "Starting......: ".date("H:i:s")." trying https://www.articatech.com\n";
                        $ini->_params["AUTOUPDATE"]["uri"]="https://www.articatech.com/auto.update.php";
                    } else{
                        echo "Starting......: ".date("H:i:s")." trying www.articatech.net\n";
                        $ini->_params["AUTOUPDATE"]["uri"]="http://articatech.net/auto.update.php";
                    }

					$ini->saveFile("/etc/artica-postfix/artica-update.conf");
					$GLOBALS["CHANGED"]=true;
					return master_index();
				}
			}
		}
		return false;
	}
	
	
	if(!is_file($GLOBALS["MasterIndexFile"])){
		echo "Starting......: ".date("H:i:s")." {$GLOBALS["MasterIndexFile"]} no such file...\n";
		return false;
	
	}
	
	return true;
}

function update_patchs($manualfile=null):bool{
    $unix               = new unix();
    $CURVER             = GetCurrentVersionString();
    $ARTICA_BASE        = "/usr/share/artica-postfix";
    $CURPATCH           = intval(@file_get_contents("$ARTICA_BASE/SP/$CURVER"));
    $PATCH_BACKUPDIR    = "/home/artica/patchsBackup/$CURVER/$CURPATCH";
    $php                = $unix->LOCATE_PHP5_BIN();

    $unix->ToSyslog("Execute Update Patch with \"$manualfile\"",false,"artica-update");
    echo "Current Version: $CURVER\n";

    if($manualfile==null){
        $ArticaDisablePatchs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));
        if($ArticaDisablePatchs==1){
            build_progress_patch("{feature_disabled}",110);
            return false;
        }

        build_progress_patch("{downloading} Index file",10);
        $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
        if ($ArticaRepoSSL==1){
            $curl=new ccurl("https://www.articatech.com/servicepack2.php?t=".time());
        } else{
            $curl=new ccurl("http://articatech.net/servicepack2.php?t=".time());
        }

        if(!$curl->get()){
            build_progress_patch("{downloading} Index file {failed}",110);
            return false;
        }


        $array=unserialize(base64_decode($curl->data));
        if(!is_array($array)){
            build_progress_patch("{corrupted} Index file",110);
            echo "Patch: \n$curl->data\nNot an array...";
            return false;
        }



        if(!isset($array[$CURVER])){
            build_progress_patch("{corrupted} Index file",110);
            echo "Nothing to do\n";
            return true;
        }
        $MD5=$array[$CURVER]["MD5"];
        $URI=$array[$CURVER]["URI"];
        $TIME=$array[$CURVER]["TIME"];
        $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
        if ($ArticaRepoSSL==1){
            $URI=str_replace("http://mirror.articatech.com", "https://www.articatech.com",$URI);
            $URI=str_replace("http://articatech.net", "https://www.articatech.com",$URI);
        }
        $PVER=intval($array[$CURVER]["VERSION"]);

        echo "$MD5 v$PVER $URI generated on ".date("Y-m-d H:i:s",$TIME)."\n";

        if($PVER==$CURPATCH){
            build_progress_patch("{nothing_to_change}",100);
            echo "Nothing to do\n";
            return true;
        }
        if($CURPATCH>$PVER){
            build_progress_patch("{nothing_to_change} Current v$CURPATCH is higuer than v$PVER",100);
            echo "Nothing to do\n";
            return true;
        }

        build_progress_patch("{downloading} $CURPATCH {to} $PVER ",13);
        echo "Downloading $URI\n";
        build_progress_patch("{downloading}",100);
        $TEMPFILE=$unix->FILE_TEMP().".tgz";
        $curl=new ccurl($URI);
        if(!$curl->GetFile($TEMPFILE)){
            build_progress_patch("{downloading} {failed}",110);
            echo "Failed to download\n";
            return false;
        }
        $mdNew=md5_file($TEMPFILE);
        if($mdNew<>$MD5){
            build_progress_patch("{downloading} {corrupted}",110);
            echo "Download corrupted...\n";
            return false;
        }

    }else{
        $TEMPFILE=base64_decode($manualfile);
        $base=basename($TEMPFILE);
        if(!is_file($TEMPFILE)){
            echo "$TEMPFILE no such file\n";
            build_progress_patch("$base no such file",110);
            return false;
        }

        if(!preg_match("#ArticaP([0-9]+)\.tgz#i",$base,$re)){
            @unlink($TEMPFILE);
            build_progress_patch("$base {corrupted}",110);
            return false;
        }
        $PVER=$re[1];
        if($PVER==$CURPATCH){
            build_progress_patch("{nothing_to_change}",100);
            echo "Nothing to do\n";
            return true;
        }

    }

    $TMPPATH=$unix->TEMP_DIR()."/PATCH$PVER";
    $tar=$unix->find_program("tar");
    build_progress_patch("{uncompressing} $CURPATCH {to} $PVER ",14);
    build_progress_patch("{uncompressing}",25);
    echo "Uncompressing in $TMPPATH...\n";
    @mkdir($TMPPATH,0755,true);
    shell_exec("$tar -xf $TEMPFILE -C $TMPPATH/");
    @unlink($TEMPFILE);
    $rm=$unix->find_program("rm");
    $rmfinal="$rm -rf $TMPPATH";
    build_progress_patch("{installing} $CURPATCH {to} $PVER ",15);
    echo "get Index file\n";
    if(!is_file("$TMPPATH/artica-postfix/SP/index.pf")){
        build_progress_patch("index.pf no such file",110);
        echo "$TMPPATH/artica-postfix/SP/index.pf no such file";
        shell_exec($rmfinal);
        return false;
    }

    if(!is_file("$TMPPATH/artica-postfix/SP/current.pf")){
        build_progress_patch("Verification file not found",110);
        echo "Verification file not found...\n";
        shell_exec($rmfinal);
        return false;
    }

    $PATCHMAINVER=trim(@file_get_contents("$TMPPATH/artica-postfix/SP/current.pf"));
    if($PATCHMAINVER <> $CURVER){
        build_progress_patch("{incompatible}",110);
        echo "This patch supports only this version $PATCHMAINVER Aborting\n";
        shell_exec($rmfinal);
        return false;
    }

    $MAIN=unserialize(@file_get_contents("$TMPPATH/artica-postfix/SP/index.pf"));
    if(!is_array($MAIN)){
        build_progress_patch("{corrupted}",110);
        echo "$TMPPATH/artica-postfix/SP/index.pf Corrupted patch...\n";
        shell_exec($rmfinal);
        return false;
    }

    $DOROLLBACK=false;
    $patched=0;

    $Max=count($MAIN);
    $c=0;
    build_progress_patch("{installing} $CURPATCH {to} $PVER ",16);
    foreach ($MAIN as $destination=>$md5source){
        $c++;
        $prc=$c/$Max;
        $prc=round($prc*100);
        if($prc>25){
            if($prc<98){
                build_progress_patch("{installing}",$prc);
            }
        }

        if(preg_match("#(index|current)\.pf$#",$destination)){
            echo $destination." --> SKIP\n";
            continue;
        }
        if(preg_match("#\/SP\/[0-9\.]+#",$destination)){
            echo $destination." --> SKIP\n";
            continue;
        }


        $SourceFile=str_replace("/usr/share",$TMPPATH,$destination);
        echo "Scanning $SourceFile\n";
        if(!is_file($SourceFile)){
            echo "$SourceFile no such file!!!\n";
            shell_exec($rmfinal);
            return false;
        }

        $md5SourceFile=md5_file($SourceFile);
        if($md5SourceFile<>$md5source){
            echo "$SourceFile corrupted $md5SourceFile<>$md5source!!!\n";
            shell_exec($rmfinal);
            return false;
        }
        $md5DestFile=null;
        if(is_file($destination)) {$md5DestFile = md5_file($destination);}
        if($md5DestFile==$md5source){
            echo "$destination SKIP\n";
            $pback="$PATCH_BACKUPDIR$destination";
            $backdir=dirname($pback);
            @mkdir($backdir,0755,true);
            @copy($destination,$pback);
            @chmod($destination,0755);
            continue;
        }
        echo "$destination PATCHING\n";
        $pback="$PATCH_BACKUPDIR$destination";
        $backdir=dirname($pback);
        @mkdir($backdir,0755,true);
        @copy($destination,$pback);
        @chmod($pback,0755);
        @unlink($destination);

        $destination_dir=dirname($destination);
        if(!is_dir($destination_dir)){
            echo "Creating sirectory $destination_dir\n";
            @mkdir($destination_dir,0755,true);
        }
        if(!@copy($SourceFile,$destination)){
            echo "Copy $SourceFile -> $destination failed\n";
            $DOROLLBACK=true;
            break;
        }
        @chmod($destination,0755);
        if(is_file($destination)) {$md5DestFile = md5_file($destination);}
        if($md5DestFile==$md5source){
            $patched++;
            echo "$destination [OK]\n";
            continue;
        }

        $DOROLLBACK=true;
        break;
    }
    build_progress_patch("{installing} $CURPATCH {to} $PVER ",17);
    system("cd $PATCH_BACKUPDIR");
    @chdir($PATCH_BACKUPDIR);
    $tar=$unix->find_program("tar");
    @mkdir("$PATCH_BACKUPDIR/usr/share/artica-postfix/SP",0755,true);
    if(!is_dir("$ARTICA_BASE/SP")){@mkdir("$ARTICA_BASE/SP");}
    @file_put_contents("$ARTICA_BASE/SP/$CURVER",$CURPATCH);
    shell_exec("$tar -czf $PATCH_BACKUPDIR/package.tgz *");
    shell_exec("$rm -rf $PATCH_BACKUPDIR/usr");

    if($DOROLLBACK){
        echo "Restore old backup....\n";
        squid_admin_mysql(0,"Failed to update $CURVER Service Pack $PVER");
        shell_exec("$tar xf $PATCH_BACKUPDIR/package.tgz -C /");
        echo "Patching [FAILED]\n";
        build_progress_patch("{installing} {failed}",110);
        return false;
    }
    build_progress_patch("{installing} $CURPATCH {to} $PVER ",18);
    echo "Patching success\n";
    build_progress_patch("{installing} {success}",100);
    @file_put_contents("$ARTICA_BASE/SP/$CURVER",$PVER);
    shell_exec($rmfinal);

    $chmod=$unix->find_program("chmod");
    shell_exec("$chmod 0755 $ARTICA_BASE/*.php $ARTICA_BASE/*.sh $ARTICA_BASE/*.py");

    build_progress_patch("{installing} $CURPATCH {to} $PVER ",100);
    if($patched>0){

        shell_exec("/usr/bin/php $ARTICA_BASE/exec.framework.php --migration");
        build_progress_patch("{installing} $CURPATCH {to} $PVER ",100);
        shell_exec("/etc/init.d/artica-status restart --force");
        build_progress_patch("{installing} $CURPATCH {to} $PVER ",100);
        shell_exec("/etc/init.d/artica-syslog restart");
        build_progress_patch("{installing} $CURPATCH {to} $PVER ",100);
        if(is_file("/etc/init.d/cache-tail")){
            shell_exec("/etc/init.d/cache-tail restart");
        }

        build_progress_patch("{installing} $CURPATCH {to} $PVER ",100);
        shell_exec("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    }
    build_progress_patch("{installing} $CURPATCH {to} $PVER ",100);
    squid_admin_mysql(2, "New Artica update $CURVER Service Pack $PVER", "$patched patched sources files", __FILE__, __LINE__);
    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $sql="CREATE TABLE IF NOT EXISTS `history` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`version` text UNIQUE, `updated` INTEGER,`asseen` integer ,`xline` integer)";
    $q->QUERY_SQL($sql);
    $time=time();
    $line=__LINE__;
    $q->QUERY_SQL("INSERT OR IGNORE INTO history (version,updated,asseen,xline) VALUES ('$CURVER Service Pack $PVER','$time',0,$line)");
    build_progress_patch("{installing} $CURPATCH {to} $PVER ",25);
    shell_exec("$php $ARTICA_BASE/aptget.php --grubpc >/dev/null 2>&1");
    return true;
}


function nightly(){
	@mkdir("/var/log/artica-postfix",0755,true);
	$GLOBALS["MasterIndexFile"]="/usr/share/artica-postfix/ressources/index.ini";
	$unix=new unix();
	$sock=new sockets();
	$timefile="/etc/artica-postfix/croned.1/nightly";
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".pid";
	$pid=@file_get_contents($pidfile);
    $tmpdir=$unix->TEMP_DIR();

	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		echo "Starting......: ".date("H:i:s")." nightly build already executed PID: $pid since {$time}Mn\n";
		updater_events("Already executed PID: $pid since {$time}Mn");
		if($time<120){if(!$GLOBALS["FORCE"]){exit();}}
		unix_system_kill_force($pid);
	}

    $timefilExec=$unix->file_time_min($timefile);

	if($timefilExec<5){
        if(!$GLOBALS["FORCE"]) {
            updater_events("Last execution ({$timefilExec}mn) Too short time to perform update - require 5 minutes");
            build_progress_manu("{failed} too short time, require 5min period", 110);
            die();
        }
    }


	// Create pointers....
	$mypid=getmypid();
	@file_put_contents($pidfile, $mypid);
	@unlink($timefile);
	@file_put_contents($timefile,time());


    updater_events("Running PID $mypid");
	$ArticaAutoUpateOfficial=$sock->GET_INFO("ArticaAutoUpateOfficial");
	$ArticaAutoUpateNightly=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateNightly"));
    $ArticaDisablePatchs=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaDisablePatchs"));
	if(!is_numeric($ArticaAutoUpateOfficial)){$ArticaAutoUpateOfficial=1;}


	if($GLOBALS["FORCE"]){
		_squid_admin_mysql(1,"Update task pid $mypid is forced by an human.",
            null,__FILE__,__LINE__);
	}
	

	if($ArticaAutoUpateOfficial==0 AND $ArticaAutoUpateNightly=0 AND $ArticaDisablePatchs==1 ){
		updater_events("Artica Update feature is disabled");
		echo "Starting......: ".date("H:i:s")." Artica Update feature is disabled (enabled = $ArticaAutoUpateOfficial} )\n";
		build_progress_manu("{failed} {disabled}",110);
		exit();
	}

// ----------------------- LANCEMENT
	$ArticaAutoUpateRsync=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaAutoUpateRsync"));

	if($ArticaAutoUpateRsync==1){
		echo "Starting......: ".date("H:i:s")." Nightly builds using Rsync\n";
        build_progress_manu("{downloading} (Line:".__LINE__.")",10);
		UpdateRsync();
        build_progress_manu("{downloading} {success} (Line:".__LINE__.")",100);
		exit();
		
	}

	
	echo "Starting......: ".date("H:i:s")." Nightly builds checking an official release first\n";
	
	build_progress_manu("{downloading} (Line:".__LINE__.")",10);
	
	$GLOBALS["PROGRESS_TEXT"]="{downloading}";
	$GLOBALS["PROGRESS_PRC"]=10;

	if($ArticaAutoUpateOfficial==1) {
        if (update_release()) {
            updater_events("update_release() return true, finish");
            update_patchs();
            build_progress_manu("{success}", 100);
            return false;
        }
    }
	
	if($ArticaAutoUpateNightly==0){
		echo "Starting......: ".date("H:i:s")." Nightly builds feature is disabled\n";
		updater_events("Update to Nightly builds feature is disabled");
		build_progress_manu("{success}",100);
        update_patchs();
		return false;
		
	}
    $array=$GLOBALS["CLASS_SOCKETS"]->unserializeb64($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaUpdateRepos"));
	$OFFICIALS=$array["NIGHT"];
	$key=update_find_latest_nightly();
	$MyNextVersion=$key;
	$Lastest=$OFFICIALS[$key]["VERSION"];
	$MAIN_URI=$OFFICIALS[$key]["URL"];
	$MAIN_MD5=$OFFICIALS[$key]["MD5"];
	$MAIN_FILENAME=$OFFICIALS[$key]["FILENAME"];
	$uri=$MAIN_URI;
    $ArticaRepoSSL = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaRepoSSL"));
    if ($ArticaRepoSSL==1){
        $uri=str_replace("http://mirror.articatech.com", "https://www.articatech.com",$uri);
        $uri=str_replace("http://articatech.net", "https://www.articatech.com",$uri);
    }
	$Lastest=trim(strtolower($Lastest));
		
	$MyCurrentVersion=GetCurrentVersion();
	echo "Starting......: ".date("H:i:s")." Current version: $MyCurrentVersion\n";
	echo "Starting......: ".date("H:i:s")." Nightly builds version \"$Lastest\" on repository\n";
	echo "Starting......: ".date("H:i:s")." nightly builds Cur:$MyCurrentVersion, Next:$MyNextVersion\n";
	if($MyNextVersion==$MyCurrentVersion){
		echo "Starting......: ".date("H:i:s")." nightly builds $MyCurrentVersion/$MyNextVersion \"{version-up-to-date} - {same_version}\"\n";
        update_patchs();
		build_progress_manu("{version-up-to-date} - {same_version}",100);
		return true;
	}
	if($MyCurrentVersion>$MyNextVersion){
		echo "Starting......: ".date("H:i:s")." nightly builds $MyCurrentVersion/$MyNextVersion \"{version-up-to-date} - Most updated\"\n";
        update_patchs();
		build_progress_manu("{version-up-to-date} - {same_version}",100);
		return true ;
	}

    build_progress_manu("{downloading} $Lastest (Line:".__LINE__.")",29);
	_squid_admin_mysql(2,"nightly builds Downloading new version $Lastest",null,__FILE__,__LINE__);
	echo "Starting......: ".date("H:i:s")." nightly builds Downloading new version $Lastest, please wait\n";
	events("Downloading new version $Lastest");
	
	
	$ArticaFileTemp="$tmpdir/$Lastest/artica-$Lastest.tgz";    
	@mkdir("$tmpdir/$Lastest",0755,true);
	$curl=new ccurl($uri);
	$curl->Timeout=2400;
	$curl->WriteProgress=true;
	$curl->ProgressFunction="nightly_progress";
	$GLOBALS["DWNFILENAME"]="v$Lastest";
	$t=time();
	$GLOBALS["PROGRESS_TEXT"]="{downloading}";
	$GLOBALS["PROGRESS_PRC"]=30;
	build_progress_manu("{downloading} (Line:".__LINE__.")",30);
	
	
	if(!$curl->GetFile($ArticaFileTemp)){
		build_progress_manu("{downloading} {failed}",110);
		_squid_admin_mysql(0,"nightly builds Unable to download latest nightly build $Lastest with error $curl->error",null,__FILE__,__LINE__);
		events("Unable to download latest nightly build with error $curl->error");
		squid_admin_mysql(2, "Unable to download latest nightly build with error $curl->error", null, __FILE__, __LINE__);
		@unlink($ArticaFileTemp);
		return false;
	}
	build_progress_manu("{downloading} {success}",31);
	$took=$unix->distanceOfTimeInWords($t,time());
	_squid_admin_mysql(2,"$MAIN_FILENAME download, {took} $took",null,__FILE__,__LINE__);
	
	$md5_file=md5_file($ArticaFileTemp);
	if($md5_file<>$MAIN_MD5){
		echo "$md5_file <> $MAIN_MD5\n";
		_squid_admin_mysql(0,"nightly builds $MAIN_FILENAME: corrupted package",null,__FILE__,__LINE__);
		events("nightly builds $MAIN_FILENAME: corrupted package");
		squid_admin_mysql(2, "nightly builds $MAIN_FILENAME: corrupted package", null, __FILE__, __LINE__);
		@unlink($ArticaFileTemp);
		return false;
		
	}
	
	
	squid_admin_mysql(2, "$MAIN_FILENAME download, {took} $took", null, __FILE__, __LINE__);
	events("artica-$Lastest.tgz download, took $took");
	echo "Starting......: ".date("H:i:s")." nightly builds took $took\n";
	events("Now, installing the newest version in $ArticaFileTemp package...");

	if(!install_package($ArticaFileTemp,$Lastest)){
		events("Install package Failed...");
		return false;
	}
	 events("New Artica update v.$Lastest");
	squid_admin_mysql(2, "New Artica update v.$Lastest", null, __FILE__, __LINE__);
    update_patchs();
	$q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
	$sql="CREATE TABLE IF NOT EXISTS `history` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`version` text UNIQUE, `updated` INTEGER,`asseen` integer ,`xline` integer)";
    $q->QUERY_SQL($sql);

    $time=time();
    $line=__LINE__;
    events("INSERT OR IGNORE INTO history (version,updated,asseen,xline) VALUES ('$Lastest','$time',0,$line)");
    $q->QUERY_SQL("INSERT OR IGNORE INTO history (version,updated,asseen,xline) VALUES ('$Lastest','$time',0,$line)");

    if(!$q->ok){
        squid_admin_mysql(0,"SQL Error while inserting new version in history",$q->mysql_error,__FILE__,__LINE__);
    }

    return true;
	

}

function install_package($filename,$expected=null){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	events("Starting......: ".date("H:i:s")." install_package() Extracting package $filename, please wait... ");
	echo "Starting......: ".date("H:i:s")." install_package() Extracting package $filename, please wait... \n";
	
	
	
	$tarbin=$unix->find_program("tar");
	$killall=$unix->find_program("killall");
	echo "Starting......: ".date("H:i:s")." tar: $tarbin\n";
	echo "Starting......: ".date("H:i:s")." killall: $killall\n";
	
	build_progress_manu("{resource_testing}",50);
	
	events("Starting......: ".date("H:i:s")." install_package() Testing Package");
	echo "Starting......: ".date("H:i:s")." Testing Package ".basename($filename)."\n";
	
	ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
	ini_set('error_prepend_string'," WARNING!!:");
	ini_set('error_append_string',"\n");
	echo "Starting......: ".date("H:i:s")." Testing Package Please wait....\n";

	
	
	if(!$unix->TARGZ_TEST_CONTAINER($filename,false,true)){
		echo "Starting......: ".date("H:i:s")." Testing Package ".basename($filename)." failed\n";
		_squid_admin_mysql(0,"Compressed package ".basename($filename)." seems corrupted",null,__FILE__,__LINE__);
		events("Fatal, Compressed package seems corrupted");
		events($GLOBALS["TARGZ_TEST_CONTAINER_ERROR"]);
		@unlink($filename);
		build_progress_manu("{Corrupted}",110);
		exit;
	}
	echo "Starting......: ".date("H:i:s")." Purge directories\n";
	events("Starting......: ".date("H:i:s")." Purge directories...");
	build_progress_manu("{remove_temp_files}",51);
	

	if(is_dir("/usr/share/artica-postfix/ressources/conf/upload")){
		system("$rm -f /usr/share/artica-postfix/ressources/conf/upload/*");
	}
	
	if(is_dir("/usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded")){
		system("$rm -f /usr/share/artica-postfix/ressources/conf/meta/hosts/uploaded/*");
	}
    build_progress_manu("{extracting}...{please_wait}",54);
	events("Starting......: ".date("H:i:s")." Extracting...");
	exec("$tarbin xpf $filename -C /usr/share/ 2>&1",$results);
    @unlink($filename);
    build_progress_manu("{check_folders_permissions}...{please_wait}",55);
    shell_exec("$nohup /usr/sbin/artica-phpfpm-service -permission-watch >/dev/null 2>&1 &");

		
	$MyCurrentVersion=GetCurrentVersionString();
	build_progress_manu("{current_version} $MyCurrentVersion",56);
	if($expected<>null){
		if($MyCurrentVersion<>$expected){
			_squid_admin_mysql(1,"install_package(): Expected version:$expected does not match $MyCurrentVersion",$results,__FILE__,__LINE__);
			 build_progress_manu("Expected version:$expected does not match $MyCurrentVersion {failed}",110);
			return false;
		}
	}

    _squid_admin_mysql(2, "New Artica update v.$MyCurrentVersion", $results, __FILE__, __LINE__);

    $q=new lib_sqlite("/home/artica/SQLITE/nightly.db");
    $sql="CREATE TABLE IF NOT EXISTS `history` (`ID` INTEGER PRIMARY KEY AUTOINCREMENT,`version` text UNIQUE, `updated` INTEGER,`asseen` integer ,`xline` integer)";
    $q->QUERY_SQL($sql);

    $time=time();
    $line=__LINE__;

    events("INSERT INTO history (version,updated,asseen,xline) VALUES ('$MyCurrentVersion','$time',0,$line)");
    $q->QUERY_SQL("INSERT INTO history (version,updated,asseen,xline) VALUES ('$MyCurrentVersion','$time',0,$line)");

    if(!$q->ok){
        _squid_admin_mysql(0,"SQL Error while inserting new version in history",$q->mysql_error,__FILE__,__LINE__);
    }
	

	_squid_admin_mysql(2,"install_package(): restart dedicated services...",null,__FILE__,__LINE__);
	squid_admin_mysql(2, "Warning: Restart Artica dedicated services after an upgrade...", null, __FILE__, __LINE__);
	system("$php ". __FILE__." --restart-services");
	_squid_admin_mysql(2,"install_package(): finish",null,__FILE__,__LINE__);
	build_progress_manu("{done}",100);
	return true;
	
	
}

function RestartDedicatedServices($aspid=false){
	$unix       = new unix();
	$Aroot      = ARTICA_ROOT;

    $articaver=GetCurrentVersionString();
    build_progress_manu("{success} v$articaver",100);


	if($aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=@file_get_contents($pidfile);
		
		
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			echo "Starting......: ".date("H:i:s")." RestartDedicatedServices already executed PID: $pid since {$time}Mn\n";
			if($time<120){if(!$GLOBALS["FORCE"]){exit();}}
			unix_system_kill_force($pid);
		}
		
		@file_put_contents($pidfile, getmypid());
	}
	
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->REST_API("/register/server");
	events("Starting artica");
	echo "Starting......: ".date("H:i:s")." nightly builds starting artica...\n";
	build_progress_manu("{starting} depmod (Line:".__LINE__.")",100);
	
	$depmod=$unix->find_program("depmod");
	$ldconfig=$unix->find_program("ldconfig");
    $chmod=$unix->find_program("chmod");
	system("$depmod -a");


    shell_exec("$chmod 0755 $Aroot/*.php /usr/share/artica-postfix/*.sh /usr/share/artica-postfix/*.py");

    build_progress_manu("{starting} ldconfig... (Line:".__LINE__.")",100);
	system("$ldconfig");
	build_progress_manu("{starting} (Line:".__LINE__.")",100);
	build_progress_manu("{starting} (Line:".__LINE__.")",100);
	build_progress_manu("{starting} (Line:".__LINE__.")",100);
	build_progress_manu("{starting} (Line:".__LINE__.")",100);

	$python_delete[]="ressources/activedirectoryclass.py";
    $python_delete[]="ldap-tests.py";
    $python_delete[]="bin/cloudflare_query.py";
    $python_delete[]="bin/external_acl_krsn.py";
    $python_delete[]="bin/external_acl_itchart.py";
    $python_delete[]="bin/srnquery.py";
    $python_delete[]="googlesafebrowsing.py";
    $python_delete[]="bin/goldlic.py";
    $python_delete[]="bin/nginx-stats.py";
    $python_delete[]="bin/sbserver-daemon";
    $python_delete[]="ubound-srn.py";
    $python_delete[]="srn-smtp.py";
    $python_delete[]="exec.rpz-master.php";
    $python_delete[]="exec.atomi.php";

    foreach ($python_delete as $pythonrem){
        if(is_file("/usr/share/artica-postfix/$pythonrem")){
            @unlink("/usr/share/artica-postfix/$pythonrem");
        }
    }

	if(is_file("/etc/init.d/ufdb-tail")){
		shell_exec("$nohup $php /etc/init.d/ufdb-tail restart");
	}
	if(is_file("/etc/init.d/postfix-logger")){
		shell_exec("$nohup $php /etc/init.d/postfix-logger restart");
	}	

	if(is_file("/etc/init.d/artica-syslog")) {
        shell_exec("$nohup /etc/init.d/artica-syslog restart >/dev/null 2>&1 &");
    }
	build_progress_manu("{starting} (Line:".__LINE__.")",100);
    shell_exec("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");

	build_progress_manu("{starting} (Line:".__LINE__.")",100);
	shell_exec("$nohup /etc/init.d/auth-tail restart >/dev/null 2>&1 &");

	

	if(is_file("/etc/init.d/mimedefang")){
		build_progress_manu("{starting} - Building mimedefang",100);
		shell_exec("$nohup $php $Aroot/exec.mimedefang.php --parse >/dev/null 2>&1 &");
	}

	if(is_file("/etc/init.d/firehol")){
        build_progress_manu("{starting} (Line:".__LINE__.")",100);
        shell_exec("$nohup /etc/init.d/firehol restart 2>&1 &");
    }

    if(is_file("/etc/init.d/theshields")){
        build_progress_manu("{starting} (Line:".__LINE__.")",100);
        shell_exec("$nohup/etc/init.d/theshields restart 2>&1 &");
    }

    shell_exec("/usr/sbin/artica-phpfpm-service -reconfigure-syslog");
    $unix->RESTART_SYSLOG(true);
    shell_exec("$php $Aroot/exec.virtuals-ip.php --watchdog");
    shell_exec("$php $Aroot/exec.clean.logs.php --patchs-backup");
    build_progress_manu("{starting} (Line:".__LINE__.")",100);

    if(is_file("/etc/init.d/squid")){
        shell_exec("$php $Aroot/exec.squid.php --service-pack");
        shell_exec("$php $Aroot/exec.squid.disable.php --squid-service");
        shell_exec("$php $Aroot/exec.squid.disable.php --syslog");
        shell_exec("$php $Aroot/exec.squid.disable.php --monit");
        shell_exec("$php $Aroot/exec.ksrn.php --check-updates");
        shell_exec("$php $Aroot/exec.ksrn.php --restart");
        shell_exec("$php $Aroot/exec.squid.global.access.php --logging");
        squid_admin_mysql(1,"{reloading_proxy_service} after Artica update",null,__FILE__,__LINE__);
        system("/usr/sbin/artica-phpfpm-service -reload-proxy");
    }
    $GoExec=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Exec_Enable"));
    if ($GoExec==0){
        system("$php /usr/share/artica-postfix/exec.go.exec.php");
    }

	
	if(is_file("/etc/init.d/c-icap-access")){
		build_progress_manu("{starting} - Restarting c-icap tail",100);
		shell_exec("$nohup /etc/init.d/c-icap-access restart >/dev/null 2>&1 &");
	}

    if(is_file("/etc/init.d/postfix")) {
        shell_exec("$php $Aroot/exec.postfix.vacuum.php >/dev/null 2>&1 &");
    }
    if(is_file("/etc/init.d/unbound")) {
        shell_exec("$php $Aroot/exec.unbound.php --restart >/dev/null 2>&1 &");
    }
    shell_exec("$php $Aroot/exec.go.exec.php --update >/dev/null 2>&1 &");
    shell_exec("$php $Aroot/exec.squid.global.access.php --auth >/dev/null 2>&1 &");
    if(is_file("/etc/init.d/nginx")){
        $NGINX_SP663=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("NGINX_SP663"));
        if($NGINX_SP663==0){
            shell_exec("$php $Aroot/exec.nginx.php --restart-build  >/dev/null 2>&1 &");
        }
    }

    $articaver=GetCurrentVersionString();
    build_progress_manu("{success} v$articaver",100);

    shell_exec("/usr/share/artica-postfix/bin/articarest -phpini");

    build_progress_manu("{starting} (Line:".__LINE__.")",100);

    $unix->ToSyslog("Running $nohup /usr/bin/php $Aroot/exec.apt-get.php --grubpc","SYSTEM-UPGRADE");
    shell_exec("$nohup /usr/bin/php $Aroot/exec.apt-get.php --grubpc >/dev/null 2>&1 &");
    build_progress_manu("{starting} (Line:".__LINE__.")",100);

    shell_exec("$nohup /usr/bin/php $Aroot/exec.status.php --process1 --force >/dev/null 2>&1 &");
	build_progress_manu("{starting} (Line:".__LINE__.")",100);

    build_progress_manu("{starting} (Line:".__LINE__.")",100);
	shell_exec("$nohup $php /usr/share/artica-postfix/exec.verif.packages.php >/dev/null 2>&1 &");

    if(is_file("/etc/init.d/go-shield-server")){
        shell_exec("$nohup /etc/init.d/go-shield-server restart >/dev/null 2>&1 &");
    }

	events("done");

	echo "Starting......: ".date("H:i:s")." Done you can close the screen....\n";	
	_squid_admin_mysql(2,"RestartDedicatedServices(): finish",null,__FILE__,__LINE__);
}


function nightly_progress( $client, $download_size, $downloaded, $upload_size, $uploaded) {
	if ($download_size === 0) {return;}
	$GLOBALS["UPDATED_SIZE"]=$GLOBALS["UPDATED_SIZE"]+$downloaded;
	$percent = floor($downloaded * 100 / $download_size);
	if(!isset($GLOBALS["PERCENT"][$download_size])){$GLOBALS["PERCENT"][$download_size]=0;}
	if($GLOBALS["PERCENT"][$download_size]==$percent){return;}
	$GLOBALS["PERCENT"][$download_size]=$percent;
	build_progress($GLOBALS["PROGRESS_TEXT"]."  {$percent}%",$GLOBALS["PROGRESS_PRC"]);
	build_progress_manu("{downloading}... {$GLOBALS["DWNFILENAME"]} {$percent}% (Line:".__LINE__.")",12);
}

function GetCurrentVersionString(){
	
	return trim(@file_get_contents("/usr/share/artica-postfix/VERSION"));
	
}

function GetCurrentVersion(){
   $tmpstr=GetCurrentVersionString();
   $tmpstr=str_replace(".", "", $tmpstr);
   return intval($tmpstr);
}

function events($text=null){
    $sourceline=0;$sourcefunction="";
	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}
			
	}
	
	updater_events($text,$sourcefunction,$sourceline);
}
