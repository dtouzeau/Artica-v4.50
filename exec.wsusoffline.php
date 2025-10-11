<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["EXEC"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--exec#",implode(" ",$argv))){$GLOBALS["EXEC"]=true;}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(isset($argv[1])){
if($argv[1]=="--reload"){reload_progress();exit();}
if($argv[1]=="--uninstall"){uninstall_service();exit();}
if($argv[1]=="--install"){install_service();exit();}
if($argv[1]=="--count"){Autocount();exit();}
if($argv[1]=="--davfs"){davfs();exit();}
if($argv[1]=="--default"){autofs_default();exit();}
if($argv[1]=="--checks"){Checks();exit();}
if($argv[1]=="--dirsize"){ dirsize();exit;}
if($argv[1]=="--storage"){ dirsize(true);exit;}
if($argv[1]=="--build"){build();exit();}
if($argv[1]=="--dirdebug"){diresizedebug();exit;}
if($argv[1]=="--miss"){checksmissingfiles();exit;}
if($argv[1]=="--clean-timestamps"){clean_timestamps();exit;}
}
function build_progress_storage($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/wsusoffline.storage.prg";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}

function build_progress_rs($text,$pourc){
	if(!$GLOBALS["PROGRESS"]){return;}
	$echotext=$text;
	$echotext=str_replace("{reconfigure}", "Reconfigure", $echotext);
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/autofs.restart.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);

}
function build_progress_install($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/wsusoffline.install.prg";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}
function build_progress_reconfigure($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/wsusoffline.reconfigure.prg";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}



function install_service(){
	build_progress_install("{enable_feature}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWsusOffline", 1);
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install("{install_service}",30);
	system("$php /usr/share/artica-postfix/exec.samba-service.php --install");
	build_progress_install("{install_service}",40);
	build();
	if(is_file("/etc/init.d/rsync")){system("/etc/init.d/rsync restart");}
	build_progress_install("{install_service}",50);
	build_progress_install("{success}",100);
}

function  uninstall_service(){
	build_progress_install("{disable_feature}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableWsusOffline", 0);
	if(is_file("/etc/cron.d/wsusoffline")){@unlink("/etc/cron.d/wsusoffline");system("/etc/init.d/cron reload");}
	if(is_file("/etc/init.d/rsync")){system("/etc/init.d/rsync restart");}
	$unix=new unix();
	$rm=$unix->find_program("rm");
	build_progress_install("{disable_feature}",25);
	shell_exec("$rm -rf /usr/share/wsusoffline/client/*");
	
	build_progress_install("{uninstall_service}",30);
	build_progress_install("{success}",100);
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function move_directories($wsusofflineStorageDir){
	
	$unix=new unix();
	$wsusofflineStorageDir = rtrim($wsusofflineStorageDir,"/");
	if(preg_match("#(.*?)\/client$#", $wsusofflineStorageDir,$re)){$wsusofflineStorageDir=$re[1];}
	
	
	
	$RealPath=dirname(@readlink("/usr/share/wsusoffline/client"));
	if($RealPath==null){$RealPath="/usr/share/wsusoffline/client";}
	echo "Real path....................: $RealPath\n";
	
	
	
	echo "New path.....................: $wsusofflineStorageDir/client";
	@mkdir($wsusofflineStorageDir."/client",0755,true);
	$cp=$unix->find_program("cp");
	$rm=$unix->find_program("rm");
	$ln=$unix->find_program("ln");
	echo "$cp -rfvd $RealPath/* $wsusofflineStorageDir/client/\n";
	system("$cp -rfvd $RealPath/* $wsusofflineStorageDir/client/");
	echo "$rm -rf $RealPath\n";
	system("$rm -rf $RealPath");
	echo "$ln -sf $wsusofflineStorageDir/client /usr/share/wsusoffline/client\n";
	system("$ln -sf $wsusofflineStorageDir/client /usr/share/wsusoffline/client");
	
	if(is_file("/etc/init.d/rsync")){system("/etc/init.d/rsync restart");}
	if(is_file("/etc/init.d/samba")){system("/etc/init.d/samba reload");}
	
	
	
}

function build(){
	
	//client/wsus
	//client/cpp
	//client/msse
	//client/wddefs
	//client/dotnet
	$unix=new unix();
	
	$schedules[1]="0 0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23 * * *";
	$schedules[2]="0 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
	$schedules[4]="0 0,4,8,12,16,20 * * *";
	$schedules[8]="0 0,8,16 * * *";
	$schedules[24]="0 1 * * *";
	$schedules[25]="0 2 * * *";
	$schedules[26]="0 3 * * *";
	$schedules[27]="0 4 * * *";
	$SCRIPTS=array();
	build_progress_reconfigure("{reconfigure}",10);
	$wsusofflineStorageDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineStorageDir"));
	if($wsusofflineStorageDir==null){$wsusofflineStorageDir="/usr/share/wsusoffline";}
	$wsusofflineSched=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineSched"));
	$wsusofflineLimitRate=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineLimitRate"));
	$wsusofflineUseLocalProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineUseLocalProxy"));
	$SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
	
	if($wsusofflineLimitRate==0){$wsusofflineLimitRate=850;}
	if($wsusofflineSched==0){$wsusofflineSched=2;}
	
	if($wsusofflineLimitRate>0){
		$unix->wgetrc("limit-rate",$wsusofflineLimitRate*1000);
	}else{
		$unix->wgetrc("limit-rate",null);
	}
	$proxy=null;
	if($SQUIDEnable==1){
		if($wsusofflineUseLocalProxy==1){
			$port=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
			$proxy="127.0.0.1:$port";
		}
	}
	$unix->wgetrc("http_proxy",$proxy);
	$unix->wgetrc("https_proxy",$proxy);
	
	build_progress_reconfigure("{reconfigure}",20);
	$unix->Popuplate_cron_make("wsusoffline",$schedules[$wsusofflineSched],"/bin/wsus.sh");
	shell_exec("/etc/init.d/cron reload");
	
	build_progress_reconfigure("{reconfigure}",30);
	$RealPath=dirname(@readlink("/usr/share/wsusoffline/client"));
	if($RealPath==null){$RealPath="/usr/share/wsusoffline/client";}
	echo "Real path....................: $RealPath\n";
	echo "Defined path.................: $wsusofflineStorageDir\n";
	if($wsusofflineStorageDir<>"/usr/share/wsusoffline"){
		
		if($RealPath<>"$wsusofflineStorageDir"){
			echo "$RealPath<>$wsusofflineStorageDir\n";
			echo "move_directories --> $wsusofflineStorageDir\n";
			move_directories($wsusofflineStorageDir);
		}
	}
	
	build_progress_reconfigure("{reconfigure}",40);
	$TempDir=$wsusofflineStorageDir."/wsusoffline_temp";
	@mkdir($TempDir,0755,true);
	$php=$unix->LOCATE_PHP5_BIN();
	$md5deep=$unix->find_program("md5deep");
	$awk=$unix->find_program("awk");
	$md5sum=$unix->find_program("md5sum");
	$sort=$unix->find_program("sort");
	chdir("/root");
	$SCRIPTS[]="#!/bin/sh";
	$SCRIPTS[]="USER=root";
	$SCRIPTS[]="HOME=/root";
	$SCRIPTS[]="TERM=xterm";
	$SCRIPTS[]="SHELL=/bin/bash";
	$SCRIPTS[]="SHLVL=1";
	$SCRIPTS[]="LOGNAME=root";
	$SCRIPTS[]="COLUMNS=80";
	$SCRIPTS[]="LINES=24";
	$SCRIPTS[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin";
	$SCRIPTS[]=". /lib/lsb/init-functions";
	$SCRIPTS[]="export COLUMNS";
	$SCRIPTS[]="export LINES";
	$SCRIPTS[]="echo \"Running...\" >>/var/log/wsusoffline.log";
	$SCRIPTS[]="mkdir -p $TempDir";
	$SCRIPTS[]="chmod 0755 $TempDir";
	$SCRIPTS[]="rm -f /var/log/wsusoffline.log || true";
	$SCRIPTS[]="rm -f /etc/artica-postfix/wsusstamp || true";
	$SCRIPTS[]="touch /etc/artica-postfix/wsusstamp";
	$SCRIPTS[]="env >>/var/log/wsusoffline.log 2>&1";
	$SCRIPTS[]="echo \"PRC:75 Scanning client directory...\" >>/var/log/wsusoffline.log";
	$SCRIPTS[]="$md5deep -r /usr/share/wsusoffline/client | $awk {'print $1'} | $sort | $md5sum | $awk {'print $1'} >/etc/artica-postfix/wsusstamp_md5.a || true";
	$SCRIPTS[]="echo \"PRC:80 Scanning client directory done...\" >>/var/log/wsusoffline.log";
	$f=explode("\n",@file_get_contents("/usr/share/wsusoffline/sh/download-updates.bash"));
	checksmissingfiles();
	
	while (list($index,$line)=each($f)){
		if(preg_match("#readonly temp_dir#", $line)){
			echo "TempDir......................: ($index) $TempDir\n";
			$f[$index]="readonly temp_dir=\"$TempDir\"";
			continue;
		}
		
		if(preg_match("#COLUMNS=.*?tput cols#", $line)){
			echo "COLUMNS......................: ($index) 80\n";
			continue;
			$f[$index]="\tCOLUMNS=80";
		}
		if(preg_match("#LINES=.*?tput lines#", $line)){
			echo "LINES=......................: ($index) 24\n";
			$f[$index]="\tLINES=24";
			continue;
		}
	}
	
	@file_put_contents("/usr/share/wsusoffline/sh/download-updates.bash", @implode("\n", $f));
	@chmod("/usr/share/wsusoffline/sh/download-updates.bash",0755);
	$SCRIPTS[]="cd /usr/share/wsusoffline/sh";
	
	$wsusoffline=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusoffline"));
	
	$zlangs=array();
	while (list($lang,$none)=each($wsusoffline["LANGS"])){
		echo "Language.....: $lang\n";
		$zlangs[]=$lang;
	}
	build_progress_reconfigure("{reconfigure}",50);
	if(count($zlangs)==0){
		echo "No language as been defined\n";
		@file_put_contents("/bin/wsus.sh", @implode("\n", $SCRIPTS));
		@chmod("/bin/wsus.sh",0755);
		build_progress_reconfigure("{reconfigure}",110);
		return;
	}
	
	$languages=@implode(",", $zlangs);
	$opts=array();
	$options=null;
	while (list($opt,$none)=each($wsusoffline["OPTIONS"])){
		$opts[]="-{$opt}";
		
	}
	
	build_progress_reconfigure("{reconfigure}",60);
	if(count($opts)>0){$options=" ".@implode(" ", $opts);}
	

	
	
	
	
	while (list($prod,$none)=each($wsusoffline["PRODUCTS"])){
		$SCRIPTS[]="echo \"PRC:100 Running $prod\" >>/var/log/wsusoffline.log";
		$SCRIPTS[]="./download-updates.bash $prod $languages{$options} >>/var/log/wsusoffline.log 2>&1";
	}
	
	$SCRIPTS[]="echo \"Scanning directory (final)\" >>/var/log/wsusoffline.log";
	$SCRIPTS[]="$md5deep -r /usr/share/wsusoffline/client | $awk {'print $1'} | $sort | $md5sum | $awk {'print $1'} >/etc/artica-postfix/wsusstamp_md5.b || true";
	$SCRIPTS[]="$php ".__FILE__." --dirsize ||true";
	$SCRIPTS[]="exit 0\n";
	
	@file_put_contents("/bin/wsus.sh", @implode("\n", $SCRIPTS));
	@chmod("/bin/wsus.sh",0755);
	
	build_progress_reconfigure("{reconfigure}",70);
	if(is_file("/etc/init.d/rsync")){system("/etc/init.d/rsync restart");}
	
	if(!$GLOBALS["EXEC"]){
		build_progress_reconfigure("{reconfigure} {success}",100);
		return;}
	
	build_progress_reconfigure("{running} {updates}",70);
	system("/bin/wsus.sh >/tmp/start.log 2>&1 &");
	$prc=0;
	for($i=0;$i<30;$i++){
		$tt=explode("\n",@file_get_contents("/var/log/wsusoffline.log"));
		foreach($tt as $line){
			if(preg_match("#PRC:([0-9]+)\s+(.+)#", $line,$re)){$progress_text=$re[2];$prc=$re[1];}
		}
		
		if($prc>0){build_progress_reconfigure($progress_text,$prc);}
		if($prc==100){
			build_progress_reconfigure("{success}",100);
			return;}
		sleep(1);
		
	}
	
	build_progress_reconfigure("{success}",100);
	
}
function diresizedebug(){
	$unix=new unix();
	$dirsize_bytes=$unix->DIRSIZE_BYTES_NOCACHE("/usr/share/wsusoffline/client");
	echo "$dirsize_bytes\n";
	print_r(unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineSizes")));
}

function checksmissingfiles(){
	
	$ins[]="[Installation]";
	$ins[]="skipieinst=Disabled";
	$ins[]="updatecpp=Enabled";
	$ins[]="instmssl=Disabled";
	$ins[]="instdotnet35=Disabled";
	$ins[]="instdotnet4=Disabled";
	$ins[]="instpsh=Disabled";
	$ins[]="instwmf=Disabled";
	$ins[]="instmsse=Disabled";
	$ins[]="skipdefs=Disabled";
	$ins[]="updatetsc=Disabled";
	$ins[]="instofv=Disabled";
	$ins[]="all=Disabled";
	$ins[]="seconly=Disabled";
	$ins[]="excludestatics=Disabled";
	$ins[]="skipdynamic=Disabled";
	$ins[]="[Control]";
	$ins[]="verify=Enabled";
	$ins[]="autoreboot=Disabled";
	$ins[]="shutdown=Disabled";
	$ins[]="[Messaging]";
	$ins[]="showlog=Disabled";
	$ins[]="showieinfo=Enabled";
	$ins[]="[MSI]";
	
	if(!is_file("/usr/share/wsusoffline/client/UpdateInstaller.ini")){
		echo "Creating /usr/share/wsusoffline/client/UpdateInstaller.ini\n";
		@file_put_contents("/usr/share/wsusoffline/client/UpdateInstaller.ini", @implode("\n", $ins));
		
	}else{
		echo "/usr/share/wsusoffline/client/UpdateInstaller.ini OK\n";
	}
	
}

function clean_timestamps(){
	build_progress_storage("{clean_timestamps}",10);
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$nohup=$unix->find_program("nohup");
	system("$rm /usr/share/wsusoffline/timestamps/*");
	build();
	dirsize(true);
	system("$nohup /bin/wsus.sh >/dev/null 2>&1 &");
	
}

function dirsize($force=false){
	$unix=new unix();
	
	build_progress_storage("{scanning}",20);
	$dirsize_bytes=$unix->DIRSIZE_BYTES_NOCACHE("/usr/share/wsusoffline/client");
	$partition=$unix->DIRPART_INFO("/usr/share/wsusoffline/client");
	$partsize=$partition["TOT"];
	$md5b=trim(@file_get_contents("/etc/artica-postfix/wsusstamp_md5.b"));
	$md5a=trim(@file_get_contents("/etc/artica-postfix/wsusstamp_md5.a"));
	$xtime=filemtime("/etc/artica-postfix/wsusstamp");
	$took=distanceOfTimeInWords($xtime,time());
	
	if(!$force){
		if($md5b<>$md5a){
			$dirsize_k=FormatBytes($dirsize_bytes/1024,true);
			squid_admin_mysql(1, "WSUS new updates downloaded ($dirsize_k) {took}  $took", @file_get_contents("/var/log/wsusoffline.log"),__FILE__,__LINE__);
		}
	}
	
	build_progress_storage("{scanning}",30);

	$array["CUR"]=$dirsize_bytes;
	$array["PART"]=$partsize;
	
	echo "CUR.......: $dirsize_bytes";
	echo "PARTITION.: $partsize";
	
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("wsusofflineSizes", serialize($array));
	$q=new mysql();
	
	build_progress_storage("{scanning}",40);
	$sql="CREATE TABLE IF NOT EXISTS `artica_backup`.`wsusoffline_dirs` (
				`path` VARCHAR( 128 ) NOT NULL PRIMARY KEY,
				`filename` VARCHAR( 128 ) NOT NULL  DEFAULT 'filename',
				`sizebytes` INT(10) UNSIGNED NOT NULL DEFAULT 0,
				`filemtime`  INT(10) UNSIGNED NOT NULL DEFAULT 0,
				 KEY `path`(`path`),
				 KEY `filename`(`filename`)
				) ENGINE=MYISAM;";
	$q->QUERY_SQL($sql,'artica_backup');
	$q->QUERY_SQL("TRUNCATE TABLE wsusoffline_dirs","artica_backup");
	$h=array();
	$find=$unix->find_program("find");
	$grep=$unix->find_program("grep");
	$tmp=$unix->FILE_TEMP();
	$cmdline="$find /usr/share/wsusoffline/client/ -type f |grep -E \"\.(cab|exe)$\" >$tmp 2>&1";
	echo $cmdline."\n";
	shell_exec($cmdline);
	$f=explode("\n",@file_get_contents($tmp));
	@unlink($tmp);
	
	$c=40;
	foreach ($f as $path){
		$path=trim($path);
		if($path==null){continue;}
		if(!is_file($path)){continue;}
		$size=@filesize($path);
		$filename=basename($path);
		$filemtime=filemtime($path);
		$h[]="('$path','$filename','$size','$filemtime')";
		if($c>95){$c=95;}
		build_progress_storage("{scanning} $filename",$c);
	}
	
	
	if(count($h)>0){
		echo count($h)." Line(s)\n";
		$q->QUERY_SQL("INSERT IGNORE INTO wsusoffline_dirs (path,filename,sizebytes,filemtime) VALUES ".@implode(",", $h),"artica_backup");
		if(!$q->ok){
			echo $q->mysql_error."\n";
			build_progress_storage("{scanning} {failed} MySQL",110);
			return;
		}
		
		
	}
	build_progress_storage("{scanning} {success}",100);
	
}



?>