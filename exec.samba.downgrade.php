<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["DUMP"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);
ini_set('error_prepend_string',null);
ini_set('error_append_string',null);
}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#reconfigure-count=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE_COUNT"]=$re[1];}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.ext.tarcompress.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');




if(count($argv)==0){exit();}


downgrade($argv[1]);


function downgrade($file){
	
	$unix=new unix();
	
	
	$pidFile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file($pidFile);
	if($unix->process_exists($pid)){
		Events("??%: A process already exists PID $pid");
		return;
	}
	
	@file_put_contents($pidFile, getmypid());	
	
	
	$workdir="/home/samba/downgrade";
	$gzf="/home/samba/downgrade/$file";
	@mkdir("/home/samba/downgrade",0755,true);
	
	
	Events("0%: Ask to update package name $file");
	Events("1%: downloading $file");
	if(!is_dir($workdir)){
		Events("100%: Failed,  $workdir Permission denied");
		exit();
	}
	
	$unix=new unix();
	$URIBASE=$unix->MAIN_URI();
	if(is_file($gzf)){@unlink($gzf);}
	Events("5%: PLEASE WAIT,PLEASE WAIT,PLEASE WAIT.....downloading $file");
	$curl=new ccurl("$URIBASE/download/old-samba/$file");
	$curl->NoHTTP_POST=true;
	$curl->ProgressFunction="downgrade_prg";
	$curl->DebugProgress=true;
	$curl->WriteProgress=true;
	if(!$curl->GetFile($gzf)){
		Events("100%: Failed to download $curl->error");
		exit();
	}
	
	if(!is_file($gzf)){
		Events("100%: Failed to download permission denied on disk");
		exit();
	}
	$size=@filesize($gzf);
	$size=$size/1024;
	$size=$size/1024;
	Events("10%: ". basename($gzf)." ".round($size,2)." MB");
	Events("10%: Testing $gzf");
	if($GLOBALS["VERBOSE"]){echo "Open TAR...\n";}
	$tar = new tar();
	if(!$tar->openTar($gzf)){
		Events("100%: Failed archive seems corrupted..");
		exit();
	}
	Events("10%: Testing $gzf success");
	Events("15%: Start upgrade procedure...");
	Events("16%: Stopping Samba...");
	shell_exec("/etc/init.d/samba stop");
	shell_exec("/etc/init.d/winbind stop");
	$squidbin=$unix->LOCATE_SQUID_BIN();
	$rm=$unix->find_program("rm");
	$tar=$unix->find_program("tar");
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	Events("17%: Removing Samba...");
	SambaRemove();
	Events("50%: Installing...");
	shell_exec("$tar xf $gzf -C / >/dev/null");
	$smbd=$unix->find_program("smbd");
	if(!is_file($smbd)){
		Events("100%: Failed archive seems corrupted, please restart again or contact or support team...");
		exit();
	}
	
	$ver=$unix->samba_version();
	Events("60%: New Samba version $ver");
	
	
	Events("65%: Reconfiguring parameters");
	shell_exec("$php5 /usr/share/artica-postfix/exec.samba.php --build --force >/dev/null");
	Events("70%: Starting Samba");
	shell_exec("/etc/init.d/samba start");
	shell_exec("/etc/init.d/winbind start");
	Events("80%: Refresh Artica with the new version...");
	shell_exec("/etc/init.d/artica-process1 start");
	Events("90%: Restarting watchdogs...");
	system("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart  >/dev/null 2>&1 &");
	Events("100%: Done...");
	Events("-------------------------------------------------------------");
	Events("----------------     Samba FS V.$ver    ------------------");
	Events("-------------------------------------------------------------");
}


function SambaRemove(){
	$unix=new unix();
	$rm=$unix->find_program("rm");
	$f[]="/usr/sbin/smbd";
	$f[]="/usr/sbin/nmbd";
	$f[]="/usr/sbin/swat";
	$f[]="/usr/sbin/winbindd";
	$f[]="/usr/sbin/msktutil";
	$f[]="/usr/bin/wbinfo";
	$f[]="/usr/bin/smbclient";
	$f[]="/usr/bin/net";
	$f[]="/usr/bin/smbspool";
	$f[]="/usr/bin/testparm";
	$f[]="/usr/bin/smbstatus";
	$f[]="/usr/bin/smbget";
	$f[]="/usr/bin/smbta-util";
	$f[]="/usr/bin/smbcontrol";
	$f[]="/usr/bin/smbtree";
	$f[]="/usr/bin/tdbbackup";
	$f[]="/usr/bin/nmblookup";
	$f[]="/usr/bin/pdbedit";
	$f[]="/usr/bin/tdbdump";
	$f[]="/usr/bin/tdbrestore";
	$f[]="/usr/bin/tdbtool";
	$f[]="/usr/bin/smbpasswd";
	$f[]="/usr/bin/rpcclient";
	$f[]="/usr/bin/smbcacls";
	$f[]="/usr/bin/profiles";
	$f[]="/usr/bin/ntlm_auth";
	$f[]="/usr/bin/sharesec";
	$f[]="/usr/bin/smbcquotas";
	$f[]="/usr/bin/eventlogadm";
	$f[]="/usr/lib/samba/lowcase.dat";
	$f[]="/usr/lib/samba/upcase.dat";
	$f[]="/usr/lib/samba/valid.dat";
	$f[]="/usr/lib/samba/vfs/recycle.so";
	$f[]="/usr/lib/samba/vfs/audit.so";
	$f[]="/usr/lib/samba/vfs/extd_audit.so";
	$f[]="/usr/lib/samba/vfs/full_audit.so";
	$f[]="/usr/lib/samba/vfs/netatalk.so";
	$f[]="/usr/lib/samba/vfs/fake_perms.so";
	$f[]="/usr/lib/samba/vfs/default_quota.so";
	$f[]="/usr/lib/samba/vfs/readonly.so";
	$f[]="/usr/lib/samba/vfs/cap.so";
	$f[]="/usr/lib/samba/vfs/expand_msdfs.so";
	$f[]="/usr/lib/samba/vfs/shadow_copy.so";
	$f[]="/usr/lib/samba/vfs/shadow_copy2.so";
	$f[]="/usr/lib/samba/vfs/xattr_tdb.so";
	$f[]="/usr/lib/samba/vfs/catia.so";
	$f[]="/usr/lib/samba/vfs/streams_xattr.so";
	$f[]="/usr/lib/samba/vfs/streams_depot.so";
	$f[]="/usr/lib/samba/vfs/readahead.so";
	$f[]="/usr/lib/samba/vfs/fileid.so";
	$f[]="/usr/lib/samba/vfs/preopen.so";
	$f[]="/usr/lib/samba/vfs/syncops.so";
	$f[]="/usr/lib/samba/vfs/acl_xattr.so";
	$f[]="/usr/lib/samba/vfs/acl_tdb.so";
	$f[]="/usr/lib/samba/vfs/smb_traffic_analyzer.so";
	$f[]="/usr/lib/samba/vfs/dirsort.so";
	$f[]="/usr/lib/samba/vfs/scannedonly.so";
	$f[]="/usr/lib/samba/vfs/crossrename.so";
	$f[]="/usr/lib/samba/vfs/linux_xfs_sgid.so";
	$f[]="/usr/lib/samba/vfs/time_audit.so";
	$f[]="/usr/lib/samba/idmap/rid.so";
	$f[]="/usr/lib/samba/idmap/autorid.so";
	$f[]="/usr/lib/samba/idmap/ad.so";
	$f[]="/usr/lib/samba/charset/CP850.so";
	$f[]="/usr/lib/samba/charset/CP437.so";
	$f[]="/usr/lib/samba/auth/script.so";
	$f[]="/usr/lib/samba/de.msg";
	$f[]="/usr/lib/samba/en.msg";
	$f[]="/usr/lib/samba/fi.msg";
	$f[]="/usr/lib/samba/fr.msg";
	$f[]="/usr/lib/samba/it.msg";
	$f[]="/usr/lib/samba/ja.msg";
	$f[]="/usr/lib/samba/nl.msg";
	$f[]="/usr/lib/samba/pl.msg";
	$f[]="/usr/lib/samba/ru.msg";
	$f[]="/usr/lib/samba/tr.msg";
	$f[]="/lib/security/pam_smbpass.so";
	$f[]="/lib/security/pam_winbind.so";
	$f[]="/usr/lib/libtalloc.so.2.0.5";
	$f[]="/usr/lib/libtalloc.a";
	$f[]="/usr/include/talloc.h";
	$f[]="/usr/lib/libtdb.so.1.2.9";
	$f[]="/usr/lib/libtdb.a";
	$f[]="/usr/include/tdb.h";
	$f[]="/usr/lib/libwbclient.so.0";
	$f[]="/usr/lib/libwbclient.a";
	$f[]="/usr/include/wbclient.h";
	$f[]="/usr/lib/libnetapi.so.0";
	$f[]="/usr/lib/libnetapi.a";
	$f[]="/usr/include/netapi.h";
	$f[]="/usr/lib/libsmbclient.so.0";
	$f[]="/usr/lib/libsmbclient.a";
	$f[]="/usr/include/libsmbclient.h";
	$f[]="/usr/lib/libsmbsharemodes.so.0";
	$f[]="/usr/lib/libsmbsharemodes.a";
	$f[]="/usr/include/smb_share_modes.h";
	$f[]="/usr/share/locale/de/LC_MESSAGES/net.mo";
	$f[]="/usr/share/locale/ar/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/cs/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/da/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/de/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/es/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/fi/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/fr/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/hu/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/it/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ja/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ko/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/nb/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/nl/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/pl/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/pt_BR/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/ru/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/sv/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/share/locale/zh_TW/LC_MESSAGES/pam_winbind.mo";
	$f[]="/usr/lib/libnetapi.a";
	$f[]="/usr/lib/libnetapi.so.0";
	$f[]="/usr/lib/libsmbclient.a";
	$f[]="/usr/lib/libsmbclient.so.0";
	$f[]="/usr/lib/libsmbsharemodes.a";
	$f[]="/usr/lib/libsmbsharemodes.so.0";
	$f[]="/usr/lib/libtalloc.a";
	$f[]="/usr/lib/libtalloc.so.2.0.5";
	$f[]="/usr/lib/libtalloc.so.2";
	$f[]="/usr/lib/libtdb.a";
	$f[]="/usr/lib/libtdb.so.1.2.9";
	$f[]="/usr/lib/libtdb.so.1";
	$f[]="/usr/lib/libcups.so.2";
	$f[]="/usr/lib/libavahi-client.so.3";
	$f[]="/usr/lib/libavahi-client.so.3.2.7";
	$f[]="/usr/lib/libwbclient.so.0";
	$f[]="/lib/libnss_winbind.so";
	$f[]="/lib/libnss_wins.so";
	$f[]="/usr/bin/ctdb";
	$f[]="/usr/bin/smnotify";
	$f[]="/usr/bin/ping_pong";
	$f[]="/usr/bin/ctdb_diagnostics";
	$f[]="/usr/bin/onnode";
	$f[]="/usr/include/ctdb.h";
	$f[]="/usr/include/ctdb_private.h";
	$f[]="/usr/sbin/ctdbd";
	$f[]="/usr/share/locale/cs/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/da/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/de/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/eo/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/es/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/fi/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/fr/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ga/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/gl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/hu/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/id/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/is/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/it/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ja/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ko/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/lv/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/nb/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/nl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/pl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/pt/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ro/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/ru/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sk/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sl/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/sv/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/th/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/tr/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/uk/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/vi/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/wa/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/zh_TW/LC_MESSAGES/popt.mo";
	$f[]="/usr/share/locale/zh_CN/LC_MESSAGES/popt.mo";

	foreach ($f as $index=>$filename){
		if(!is_file($filename)){continue;}
		Events("Removing file $filename ");
		@unlink($filename);
	
	}
	
	if(is_dir("/usr/lib/samba")){
		Events("Removing directory /usr/lib/samba");
		shell_exec("$rm -rf /usr/lib/samba");
	}
	
}



function Events($text){

	if(function_exists("debug_backtrace")){
		$trace=debug_backtrace();
		if(isset($trace[1])){
			$sourcefile=basename($trace[1]["file"]);
			$sourcefunction=$trace[1]["function"];
			$sourceline=$trace[1]["line"];
		}

	}
	
	$unix=new unix();
	$unix->events($text,dirname(__FILE__)."/ressources/logs/web/samba.downgrade.html",false,$sourcefunction,$sourceline);
	@chmod(dirname(__FILE__)."/ressources/logs/web/samba.downgrade.html",0755);
}

function downgrade_prg( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}

	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}
	 
	if ( $progress > $GLOBALS["previousProgress"]){
		Events("Downloading: ". $progress."%, please wait...");
		$GLOBALS["previousProgress"]=$progress;
	}
}