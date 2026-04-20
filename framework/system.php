<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }

if(isset($_GET["reset-rrd"])){reset_rrd();exit;}
if(isset($_GET["force-status"])){force_status();exit;}
if(isset($_GET["seeker"])){seeker();exit;}
if(isset($_GET["make-writable"])){make_www_writable();exit;}
if(isset($_GET["EnableMilterGreylistExternalDB"])){EnableMilterGreylistExternalDB();exit;}
if(isset($_GET["optimize-celeron"])){optimize_celeron();exit;}
if(isset($_GET["sensors"])){sensors();exit;}
if(isset($_GET["NetDiscover-restart"])){NetDiscover_Restart();exit;}
if(isset($_GET["BackupLogsMaxStoragePercent-info"])){BackupLogsMaxStoragePercent_info();exit;}
if(isset($_GET["disable-ntopng"])){disable_ntopng();exit;}
if(isset($_GET["enable-ntopng"])){enable_ntopng();exit;}
if(isset($_GET["syncthing-installed"])){syncthing_installed();exit;}
if(isset($_GET["restart-all-extrn-scvcs"])){restart_all_extrn_services();exit;}
if(isset($_GET["critical-paths-locations"])){critical_paths_locations();exit;}
if(isset($_GET["change-directories-progress"])){change_directories_progress();exit;}
if(isset($_GET["qos-status"])){qos_status_eth();exit;}
if(isset($_GET["artica-status-restart"])){artica_status_restart();exit;}
if(isset($_GET["remove-logs-file"])){remove_file();exit;}
if(isset($_GET["ip-to-mac"])){ip_to_mac();exit;}
if(isset($_GET["system-text"])){system_text();exit;}
if(isset($_GET["fsarray"])){fsarray();exit;}
if(isset($_GET["start-syslog-db"])){start_syslog_db();exit;}
if(isset($_GET["move-system"])){move_system();exit;}
if(isset($_GET["artica-update"])){artica_update();exit;}
if(isset($_GET["dns-linker"])){dns_linker();exit;}
if(isset($_GET["swap-init"])){swap_init();exit;}
if(isset($_GET["all-services"])){all_services();exit;}
if(isset($_GET["generic-start"])){generic_start();exit;}
if(isset($_GET["meminfo"])){meminfo();exit;}
if(isset($_GET["uidNumber"])){uidNumber();exit;}
if(isset($_GET["INODES_MAX"])){INODES_MAX();exit;}
if(isset($_GET["HardDriveDiskSizeMB"])){HardDriveDiskSizeMB();exit;}
if(isset($_GET["archiverlogs"])){archiverlogs();exit;}
if(isset($_GET["wizard-execute"])){wizard_execute();exit;}
if(isset($_GET["syslogdb-restart"])){syslogdb_restart();exit;}
if(isset($_GET["syslogdb-status"])){syslogdb_status();exit;}
if(isset($_GET["syslogdb-query"])){syslogdb_query();exit;}
if(isset($_GET["logrotate-query"])){logrotate_query();exit;}
if(isset($_GET["syslogarchive-logs"])){syslogarchive_logs();exit;}
if(isset($_GET["rsync-debian-status"])){rsync_debian_status();exit;}
if(isset($_GET["refresh-index-ini"])){refresh_index_ini();exit;}
if(isset($_GET["backup-restore-new"])){backup_restore();exit;}
if(isset($_GET["nmap-scan-single"])){nmap_scan_single();exit;}
if(isset($_GET["ntopng-installed"])){ntopng_installed();exit;}
if(isset($_GET["ntopng-restart"])){ntopng_restart();exit;}
if(isset($_GET["ntopng-status"])){ntopng_status();exit;}
if(isset($_GET["netdata-status"])){netdata_status();exit;}
if(isset($_GET["refresh-logs-storefiles"])){refresh_logs_storefiles();exit;}
if(isset($_GET["empty-swap"])){empty_swap();exit;}
if(isset($_GET["force-databases"])){force_databases();exit;}
if(isset($_GET["roolback-sp"])){roolback_sp();exit;}
if(isset($_GET["kernel-events"])){searchlogs_kernel();exit;}
if(isset($_GET["delete-all-sps"])){delete_all_sp_js();exit;}
if(isset($_GET["roolback-global"])){roolback_global();exit;}
if(isset($_GET["TrackAdmins-install"])){trackadmin_install();exit;}
if(isset($_GET["TrackAdmins-uninstall"])){trackadmin_uninstall();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);

function reset_rrd(){
    $unix=new unix();
    $base="/home/artica/rrd";
    $rm=$unix->find_program("rm");
    shell_exec("$rm -rf $base/*");
    shell_exec("$rm -rf /usr/share/artica-postfix/img/squid/*");
    if(is_file("/etc/artica-postfix/pids/exec.rrd.php.scan_squid.pid")){
        @unlink("/etc/artica-postfix/pids/exec.rrd.php.scan_squid.pid");
    }
}


function force_databases():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.convert-to-sqlite.php --force");
}





function roolback_global():bool{
    $unix=new unix();
    $version=escapeshellarg($_GET["roolback-global"]);
    $unix->framework_execute("exec.nightly.php --rollback $version","roolback.progress","roolback.progress.txt");
    return true;
}





function searchlogs_kernel(){
    $search=trim(base64_decode($_GET["kernel-events"]));
    $target_file=PROGRESS_DIR."/kernel-events.log";
    $source_file="/var/log/kern.log";

    $unix=new unix();
    $tail=$unix->find_program("tail");
    $grep=$unix->find_program("grep");
    $rp=500;
    if(is_numeric($_GET["rp"])){$rp=intval($_GET["rp"]);}

    if($search==null){
        $cmd="$tail -n $rp $source_file >$target_file 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        return;
    }

    $search=$unix->StringToGrep($search);
    $cmd="$grep --binary-files=text -i -E ".escapeshellarg($search)." $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}



function trackadmin_uninstall(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/TrackAdmins.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/TrackAdmins.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.trackadmin.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}

function trackadmin_install(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    $GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/TrackAdmins.progress";
    $GLOBALS["LOGSFILES"]=PROGRESS_DIR."/TrackAdmins.log";
    @unlink($GLOBALS["PROGRESS_FILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["PROGRESS_FILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["PROGRESS_FILE"], 0755);
    @chmod($GLOBALS["LOGSFILES"], 0755);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.trackadmin.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}






function ArchStruct(){
	$unix=new unix();
	$uname=$unix->find_program("uname");
	$line=exec("$uname -m 2>&1");
	if(preg_match("#i[0-9]86#", $line)){return 32;}
	if(preg_match("#x86_64#", $line)){return 64;}
}

function system_text(){
	if(!is_file("/etc/debian_version")){return;}
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	$Major=$re[1];
	$debian_version=$Major;
	$ArchStruct=ArchStruct();
	echo "<articadatascgi>Debian $debian_version {$ArchStruct} bits</articadatascgi>";
	
}




function swap_init(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$rm=$unix->find_program("rm");
	$php=$unix->LOCATE_PHP5_BIN();
	if(!is_file("/etc/init.d/artica-swap")){
		$cmd=trim("$php /usr/share/artica-postfix/exec.initd-swap.php >/dev/null 2>&1");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
				
	}
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.initd-swap.php --start >/dev/null 2>&1 &");
	shell_exec($cmd);
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);	
}






function all_services(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();	
	$cmd=trim("$php /usr/share/artica-postfix/exec.status.php --all --nowachdog 2>&1");
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}


function artica_status_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}



function generic_start(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");	
	$key=$_GET["key"];
	$action=$_GET["action"];
	$token=$_GET["cmd"];
	$file=PROGRESS_DIR."/$key.log";
	if(is_file("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS")){@unlink("/usr/share/artica-postfix/ressources/databases/ALL_SQUID_STATUS");}
	if(is_file("/usr/share/artica-postfix/ressources/logs/global.status.ini")){@unlink("/usr/share/artica-postfix/ressources/logs/global.status.ini");}
	
	
	@unlink($file);
	
	
	
	writelogs_framework("token $token -> $action",__FUNCTION__,__FILE__,__LINE__);
	

	
	$binary="/etc/init.d/artica-postfix";
	if(strpos("$token", "init.d")>0){
		$binary=$token;
		writelogs_framework("change binary to $token",__FUNCTION__,__FILE__,__LINE__);
		$token=null;
	}else{
		$token=" $token";
	}
	
	if(preg_match("#squid-cache#",$token)){
		$binary="/etc/init.d/squid";
	}
		
	
	@file_put_contents($file, "{$action} Please wait....\n$binary $action$token\n");
	@chmod($file, 0777);
	$cmd="$nohup $binary $action$token >> $file 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	$cmd="$nohup /etc/init.d/artica-status restart --force >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}

function meminfo(){
	
	$f=explode("\n",@file_get_contents("/proc/meminfo"));
	foreach ($f as $num=>$ligne){
		if(!preg_match("#(.*?):\s+([0-9]+)\s+#", $ligne,$re)){continue;}
		$TotalKbytes=$re[2];
		$TotalBytes=$TotalKbytes*1024;
		$key=strtoupper($re[1]);
		$array[$key]=$TotalBytes;
	}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}




function uidNumber(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.uidMember.php >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}



function INODES_MAX(){
	$unix=new unix();
	$dev=escapeshellarg(base64_decode($_GET["dev"]));
	$INODES_MAX=escapeshellarg($_GET["INODES_MAX"]);
	$INODE_SIZE=escapeshellarg($_GET["INODE_SIZE"]);
	$mount=$unix->find_program("mount");
	$umount=$unix->find_program("umount");
	$mke2fs=$unix->find_program("mke2fs");
	exec("$umount -l $dev",$results);
	exec("$mke2fs -I $INODE_SIZE -N $INODES_MAX $dev 2>&1",$results);
	exec("$mount $dev 2>&1",$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function HardDriveDiskSizeMB(){
	$unix=new unix();
	$path=$unix->shellEscapeChars(base64_decode($_GET["HardDriveDiskSizeMB"]));
	$df=$unix->find_program("df");
	$cmd="$df -B 1000000 $path 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmd",$results);
	foreach ($results as $num=>$line){
		$line=trim($line);
		if($line==null){continue;}
		if(!preg_match("#^(.*?)([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)\s+([0-9\.]+)%\s+(.+)#",$line,$re)){
			writelogs_framework("No match `$line`",__FUNCTION__,__FILE__,__LINE__);
			continue;}
		$array["DEV"]=trim($re[1]);
		$array["SIZE"]=trim($re[2]);
		$array["USED"]=trim($re[3]);
		$array["AVAILABLE"]=trim($re[4]);
		$array["POURC"]=trim($re[5]);
		echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
		return;
	}
		
}

function archiverlogs(){
	$filelog="{$GLOBALS["ARTICALOGDIR"]}/artica-mailarchive.debug";
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["search"]));
	$prefix=null;
	$max=500;
	if(isset($_GET["rp"])){$max=intval($_GET["rp"]);}	
	
	if($search<>null){
		$prefix="$grep --binary-files=text -i -E '$search' $filelog| ";
		
	}
	
	if($search<>null){
		$search=$unix->StringToGrep($search);
		$cmd="$grep --binary-files=text -i -E '$search' $filelog| $tail -n $max 2>&1";
	}else{
		$cmd="$tail -n $max $filelog 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		
}
function logrotate_query(){
	$filelog="{$GLOBALS["ARTICALOGDIR"]}/logrotate.debug";
	
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["search"]));
	$prefix=null;
	$max=500;
	if(isset($_GET["rp"])){$max=intval($_GET["rp"]);}
	
	if($search<>null){
		$prefix="$grep --binary-files=text -i -E '$search' $filelog| ";
	
	}
	
	if($search<>null){
		$search=$unix->StringToGrep($search);
		$cmd="$grep --binary-files=text -i -E '$search' $filelog| $tail -n $max 2>&1";
	}else{
		$cmd="$tail -n $max $filelog 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		
	
}

function syslogdb_query(){
	$filelog=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("MySQLSyslogWorkDir");
	if($filelog==null){$filelog="/home/syslogsdb";}	
	$filelog="$filelog/error.log";
	$unix=new unix();
	$grep=$unix->find_program("grep");
	$tail=$unix->find_program("tail");
	$search=trim(base64_decode($_GET["search"]));
	$prefix=null;
	$max=500;
	if(isset($_GET["rp"])){$max=intval($_GET["rp"]);}
	
	if($search<>null){
		$prefix="$grep --binary-files=text -i -E '$search' $filelog| ";
	
	}
	
	if($search<>null){
		$search=$unix->StringToGrep($search);
		$cmd="$grep --binary-files=text -i -E '$search' $filelog| $tail -n $max 2>&1";
	}else{
		$cmd="$tail -n $max $filelog 2>&1";
	}
	
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
		
	
}


function force_status(){

	@touch("/etc/artica-postfix/force-status");
	
	
}



function wizard_execute(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.wizard-install.php >/usr/share/artica-postfix/ressources/logs/web/wizard.log 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}






function rsync_debian_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --rsync-debian-mirror --nowachdog";
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";	
	
}



function syslogdb_restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.logs-db.php --init";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	if(is_file("/etc/init.d/syslog-db")){
		$cmd=trim("$nohup /etc/init.d/syslog-db restart >/dev/null 2>&1 &");
		writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);	
		$unix->THREAD_COMMAND_SET("$php /usr/share/artica-postfix/exec.logs-db.php --restart");
		return;
	}
	$cmd="$nohup $php /usr/share/artica-postfix/exec.logs-db.php --restart >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}



function syslogdb_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --syslog-db --nowachdog";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";

}


function syslogarchive_logs(){
	$unix=new unix();
	$tail=$unix->find_program("tail");
	if(!isset($_GET["rp"])){$rp=250;}else{$rp=intval($_GET["rp"]);}
	$cmdline="$tail -n $rp {$GLOBALS["ARTICALOGDIR"]}/logrotate.debug";
	if($_GET["search"]<>null){
		$grep=$unix->find_program("grep");
		$_GET["search"]=base64_decode($_GET["search"]);
		$cmdline="$grep --binary-files=text -i -E '{$_GET["search"]}' {$GLOBALS["ARTICALOGDIR"]}/logrotate.debug|$tail -n $rp";
	}

	writelogs_framework("$cmdline",__FUNCTION__,__FILE__,__LINE__);
	exec("$cmdline 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}







function ToSyslog($text){
	if(!function_exists("syslog")){return;}
	$file=basename(__FILE__);
	$LOG_SEV=LOG_INFO;
	openlog("framework", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}


function artica_update():bool{
	$unix=new unix();
    $unix->framework_execute(
        "exec.nightly.php --update-now --force --output",
        "artica.updatemanu.progress","artica.updatemanu.log");
    return true;
	
}
function refresh_index_ini():bool{
	$unix=new unix();
    $unix->framework_execute("exec.nightly.php --refresh --force","refresh.index.progress","refresh.index.txt");
	return true;
}
function move_system(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.system-move.php --move >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function start_syslog_db(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.logs-db.php --start >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.logs-db.php --init >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}


function fsarray(){
	$unix=new unix();
	if($unix->find_program("fsck.ext4")){$array["ext4"]="ext4";}
	if(!isset($array["ext4"])){
		if($unix->find_program("fsck.ext3")){$array["ext3"]="ext3";}
	}
	
	if($unix->find_program("fsck.btrfs")){$array["btrfs"]="btrfs";}
	if($unix->find_program("fsck.xfs")){$array["xfs"]="xfs";}
	if($unix->find_program("fsck.reiserfs")){$array["reiserfs"]="reiserfs";}
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function ip_to_mac(){
	$ipaddr=$_GET["ip-to-mac"];
	$unix=new unix();

	
	
	
	if(!is_file("/usr/bin/arping")){
		writelogs_framework("/usr/bin/arping -> not found",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	
	$MacResolvInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacResolvInterface"));
	$time=$unix->file_time_min("/etc/artica-postfix/settings/Daemons/MacResolvFrfomIP");
	if($time>240){@unlink("/etc/artica-postfix/settings/Daemons/MacResolvFrfomIP");}
	
	$MacResolvFrfomIP=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("MacResolvFrfomIP"));
	if($MacResolvInterface<>null){
		if($MacResolvFrfomIP==null){
			$MacResolvFrfomIP=ethToIp($MacResolvInterface);
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("MacResolvFrfomIP", $MacResolvFrfomIP);
		}
	}
	
	if($MacResolvFrfomIP<>null){$s="-s $MacResolvFrfomIP ";}
	
	$cmd="/usr/bin/arping -f -c 1 $s$ipaddr 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $num=>$line){
		if(!preg_match("#reply from.*?\[(.+?)\]#", $line,$re)){ 
			writelogs_framework("$line -> not found",__FUNCTION__,__FILE__,__LINE__);
			continue; 
		}
		$re[1]=trim(strtolower($re[1]));
		echo "<articadatascgi>{$re[1]}</articadatascgi>";
		return;
		
	
	}
}
function ethToIp($MacResolvInterface){
	$cmd="/sbin/ip addr show $MacResolvInterface 2>&1";
	exec($cmd,$results);
	if($GLOBALS["VERBOSE"]){events("ethToIp():: $cmd ".count($results)." lines");}
	foreach ($results as $num=>$line){

		if(preg_match("#inet\s+([0-9\.]+)\/#", $line,$re)){
			return $re[1];
		}
		
	}
}
function empty_swap(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.emptyswap";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.emptyswap.php.log";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.swap.empty.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}






function backup_restore(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.backup.artica.php --restore >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function nmap_scan_single(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/nmap.single.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/nmap_single_progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	if($_GET["MAC"]==null){$_GET["MAC"]="00:00:00:00:00:00";}
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.nmapscan.php --scan-single \"{$_GET["MAC"]}\" \"{$_GET["ipaddr"]}\" >>{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function ntopng_installed(){
	$unix=new unix();
	if(is_file("/usr/local/bin/ntopng")){@chmod("/usr/local/bin/ntopng", 0755);}
	$masterbin=$unix->find_program("ntopng");
	
	if(!is_file($masterbin)){
		writelogs_framework("ntopng -> $masterbin -> FALSE",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>FALSE</articadatascgi>";
		return;
	}
	
	
	if(is_file($masterbin)){echo "<articadatascgi>TRUE</articadatascgi>";}
	
}
function ntopng_restart(){
	$unix=new unix();
	if(is_file("/usr/local/bin/ntopng")){@chmod("/usr/local/bin/ntopng", 0755);}
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.ntopng.php --restart >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &");
}

function ntopng_status(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	if(!is_file("/etc/init.d/ntopng")){return;}
	exec("$php5 /usr/share/artica-postfix/exec.status.php --ntopng --nowachdog 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";
	
}
function netdata_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$php5 /usr/share/artica-postfix/exec.status.php --netdata --nowachdog >/usr/share/artica-postfix/ressources/logs/web/netdata.status 2>&1");
}

function roolback_sp(){

    $sp=intval($_GET["roolback-sp"]);
    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/roolback.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/roolback.progress.txt";
    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"], 0755);
    @chmod($ARRAY["LOG_FILE"], 0755);

    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.rollback.php $sp >>{$ARRAY["LOG_FILE"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function  remove_file(){
	$filename=$_GET["remove-logs-file"];
	if(!is_file($filename)){return;}
	if(is_dir($filename)){return;}
	@unlink($filename);
	
}

function refresh_logs_storefiles(){

	
}



function sensors(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.sensors.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.sensors.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.lm-sensors.php >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}




function seeker(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/seeker.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/seeker.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.seeker.php --force --verbose  >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

}



function optimize_celeron(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.optimize.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.optimize.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.intel.celeron.php  >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function disable_ntopng(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/disable-ntopng.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/disable-ntopng.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup /usr/sbin/artica-phpfpm-service -uninstall-ntopng >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}



function enable_ntopng(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/disable-ntopng.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/disable-ntopng.log";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.ntopng.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function qos_status_eth(){
	@mkdir("/usr/share/artica-postfix/ressources/logs/web",0755);
	$unix=new unix();
	$tc=$unix->find_program("tc");
	$eth=$_GET["eth"];
	$cmd="$tc -s class show dev $eth >/usr/share/artica-postfix/ressources/logs/web/qos-$eth.status 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	@chmod("/usr/share/artica-postfix/ressources/logs/web/qos-$eth.status", 0755);
	
}

function critical_paths_locations():bool{
	$unix=new unix();
	
	$f["/var/log/squid"]=true;
	$f["/home/logs-backup"]=true;
	$f["/home/c-icap/blacklists"]=true;
	$f["/var/log/artica-postfix"]=true;

    foreach ($f as $path=>$none){
		$newpath=$path;
		if(is_link($newpath)){
			$newpath=readlink($newpath);
		}
		$size=$unix->DIRSIZE_KO($newpath);
		$ARRAY[$path]["SIZE"]=$size;
		$ARRAY[$path]["PATH"]=$newpath;
		
	}
	
	echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
    return true;
}

function change_directories_progress(){
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/change.directories.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/change.directories.txt";
	
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	
	@touch($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	
	
	@chmod($GLOBALS["LOGSFILES"], 0777);
	@chmod($GLOBALS["CACHEFILE"], 0777);
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.change.directories.progress.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");

}

function restart_all_extrn_services(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$cmd="$nohup /etc/init.d/ntopng restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	$cmd="$nohup /usr/share/artica-postfix/exec.bwm-ng.php --restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	if(is_file("/etc/init.d/apache2")){
		$cmd="$nohup /etc/init.d/apache2 restart >/dev/null 2>&1 &";
		writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
		shell_exec($cmd);
	}
	
	$cmd="$nohup /etc/init.d/artica-status restart >/dev/null 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}


















function syncthing_installed(){
	$unix=new unix();
	$bin=$unix->find_program("syncthing");
	if(!is_file($bin)){echo "<articadatascgi>".base64_encode("FALSE")."</articadatascgi>";return;}
	@chmod($bin,0755);
	echo "<articadatascgi>".base64_encode("TRUE")."</articadatascgi>";
	
}

function BackupLogsMaxStoragePercent_info(){
	$BackupMaxDaysDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("BackupMaxDaysDir"));;
	if($BackupMaxDaysDir==null){$BackupMaxDaysDir="/home/logrotate_backup";}
	if(!is_dir("$BackupMaxDaysDir")){@mkdir("$BackupMaxDaysDir",0755,true);}
	$unix=new unix();
	$DIRPART_INFO=$unix->DIRPART_INFO($BackupMaxDaysDir);
	
	$DIRSIZE=$unix->DIRSIZE_BYTES($BackupMaxDaysDir);
	$DIRPART_INFO["CURSIZE"]=$DIRSIZE;
	echo "<articadatascgi>".base64_encode(serialize($DIRPART_INFO))."</articadatascgi>";
}







function  NetDiscover_Restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("/etc/init.d/netdiscover restart");
	
}



function make_www_writable(){
	$dir=$_GET["make-writable"];
	if(!is_dir($dir)){return;}
	@chmod($dir, 0777);
	
}







function EnableMilterGreylistExternalDB(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$EnableMilterGreylistExternalDB=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
	$BandwithCalculationSchedule=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMilterGreylistExternalDB"));
	$EnableArticaTechSpamAssassin=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableArticaTechSpamAssassin"));
	$EnableMalwarePatrolBody=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMalwarePatrolBody"));
	
	if($EnableMilterGreylistExternalDB==0){
		writelogs_framework("/etc/cron.d/artica-miltergreylist -> 0" ,__FUNCTION__,__FILE__,__LINE__);
		if(is_file("/etc/cron.d/artica-miltergreylist")){
			@unlink("/etc/cron.d/artica-miltergreylist");
			shell_exec("/etc/init.d/cron reload");
			
		}
	}
	
	if($EnableMilterGreylistExternalDB==1){
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.update.php >/dev/null 2>&1 &");
	}
	
	
	
	

	$schedules[1]="0 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22 * * *";
	$schedules[2]="0 0,2,4,6,8,10,12,14,16,18,20,22 * * *";
	$schedules[4]="0 0,4,8,12,16,20 * * *";
	$schedules[8]="0 0,8,16 * * *";
	$schedules[24]="0 1 * * *";
	$schedule=$schedules[$BandwithCalculationSchedule];

	
	if($EnableMilterGreylistExternalDB==1){
		writelogs_framework("/etc/cron.d/artica-miltergreylist -> $schedule" ,__FUNCTION__,__FILE__,__LINE__);
		$unix->Popuplate_cron_make("artica-miltergreylist",$schedule,"exec.milter-greylist.update.php");
		shell_exec("/etc/init.d/cron reload");
	}
	
	if($EnableMalwarePatrolBody==1){
		shell_exec("$nohup $php5 /usr/share/artica-postfix/exec.milter-greylist.update.php --malware-patrol >/dev/null 2>&1 &");
		$unix->Popuplate_cron_make("postfix-malware-patrol",$schedule,"exec.milter-greylist.update.php --malware-patrol");
		shell_exec("/etc/init.d/cron reload");
	}
	
	if($EnableArticaTechSpamAssassin==1){
		writelogs_framework("/etc/cron.d/artica-spamassupd -> $schedule" ,__FUNCTION__,__FILE__,__LINE__);
		$unix->Popuplate_cron_make("artica-spamassupd",$schedule,"exec.milter-greylist.update.php");
		shell_exec("/etc/init.d/cron reload");
	}else{
		if(is_file("/etc/cron.d/artica-spamassupd")){
			@unlink("/etc/cron.d/artica-spamassupd");
			shell_exec("/etc/init.d/cron reload");
			
		}
		
	}	
	
	
}

