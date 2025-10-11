<?php

include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }

if(isset($_GET["group-deluser"])){group_deluser();exit;}
if(isset($_GET["group-adduser"])){group_adduser();exit;}
if(isset($_GET["group-add"])){group_add();exit;}
if(isset($_GET["system-user-pass"])){system_user_chpasswd();exit;}
if(isset($_GET["system-user-add"])){system_user_add();exit;}
if(isset($_GET["reset-all"])){reset_all();exit;}
if(isset($_GET["reset-rrd"])){reset_rrd();exit;}
if(isset($_GET["artica-notifs-restart"])){artica_notifs_restart();exit;}
if(isset($_GET["dhtest"])){dhtest();exit;}
if(isset($_GET["create-directory"])){create_directory();exit;}
if(isset($_GET["force-status"])){force_status();exit;}
if(isset($_GET["dnsperf-progress"])){dnsperf_progress();exit;}
if(isset($_GET["sysctl-reconfigure"])){sysctl_progress();exit;}
if(isset($_GET["seeker"])){seeker();exit;}

if(isset($_GET["DirectoriesMonitorSchedules"])){DirectoriesMonitorSchedules();exit;}
if(isset($_GET["make-writable"])){make_www_writable();exit;}
if(isset($_GET["phpldapadmin_installed"])){phpldapadmin_installed();exit;}
if(isset($_GET["php-snmp-progress"])){php_snmp_progress();exit;}
if(isset($_GET["EnableMilterGreylistExternalDB"])){EnableMilterGreylistExternalDB();exit;}
if(isset($_GET["dashboard-refresh"])){dashboard_refresh();exit;}
if(isset($_GET["upgradev10"])){upgradev10();exit;}
if(isset($_GET["ChangePerformance"])){ChangePerformance();exit;}
if(isset($_GET["modinfo"])){modinfo();exit;}
if(isset($_GET["optimize-celeron"])){optimize_celeron();exit;}
if(isset($_GET["sensors"])){sensors();exit;}
if(isset($_GET["NetDiscover-restart"])){NetDiscover_Restart();exit;}
if(isset($_GET["phpmyadpmin-version"])){phpmyadmin_version();exit;}
if(isset($_GET["phpmyadpmin-install"])){phpmyadmin_install();exit;}
if(isset($_GET["phpmyadmin-installed"])){phpmyadmin_installed();exit;}
if(isset($_GET["BackupLogsMaxStoragePercent-info"])){BackupLogsMaxStoragePercent_info();exit;}
if(isset($_GET["disable-ntopng"])){disable_ntopng();exit;}
if(isset($_GET["enable-ntopng"])){enable_ntopng();exit;}
if(isset($_GET["syncthing-installed"])){syncthing_installed();exit;}
if(isset($_GET["disk-parent-of"])){disks_parent_of();exit;}
if(isset($_GET["folders-monitors-progress"])){dirs_monitors_execute();exit;}
if(isset($_GET["change-new-uuid"])){change_new_uuid();exit;}
if(isset($_GET["restart-all-extrn-scvcs"])){restart_all_extrn_services();exit;}
if(isset($_GET["critical-paths-locations"])){critical_paths_locations();exit;}
if(isset($_GET["change-directories-progress"])){change_directories_progress();exit;}
if(isset($_GET["qos-status"])){qos_status_eth();exit;}
if(isset($_GET["artica-status-restart"])){artica_status_restart();exit;}
if(isset($_GET["remove-logs-file"])){remove_file();exit;}
if(isset($_GET["install-artica-key"])){install_artica_key();exit;}
if(isset($_GET["install-artica-tgz"])){install_artica_tgz();exit;}
if(isset($_GET["mii-tool-save"])){MII_TOOLS_SAVE();exit;}
if(isset($_GET["mii-tools"])){MII_TOOLS();exit;}
if(isset($_GET["create-new-uuid"])){CREATE_NEW_UUID();exit;}
if(isset($_GET["MEM_TOTAL_INSTALLEE"])){MEM_TOTAL_INSTALLEE();exit;}
if(isset($_GET["mylinux"])){mylinux();exit;}
if(isset($_GET["syslog_purge-nas"])){syslog_purge_to_nas();exit;}
if(isset($_GET["test-a-route"])){test_a_route();exit;}
if(isset($_GET["hostname-g"])){hostname_g();exit;}
if(isset($_GET["ucarp-status-service"])){ucarp_status_service();exit;}
if(isset($_GET["create-user"])){create_user();exit;}
if(isset($_GET["create-user-progress"])){create_user_progress();exit;}
if(isset($_GET["group-delete"])){group_delete();exit;}

if(isset($_GET["ip-to-mac"])){ip_to_mac();exit;}
if(isset($_GET["proc-net-dev"])){proc_net_dev();exit;}
if(isset($_GET["system-text"])){system_text();exit;}
if(isset($_GET["fsarray"])){fsarray();exit;}
if(isset($_GET["gethostbyname"])){gethostbyname2();exit;}
if(isset($_GET["start-syslog-db"])){start_syslog_db();exit;}
if(isset($_GET["move-system"])){move_system();exit;}
if(isset($_GET["artica-update"])){artica_update();exit;}
if(isset($_GET["dns-linker"])){dns_linker();exit;}
if(isset($_GET["swap-init"])){swap_init();exit;}
if(isset($_GET["dirdir"])){dirdir();exit;}
if(isset($_GET["process1"])){process1();exit;}
if(isset($_GET["restart-ldap"])){restart_ldap();exit;}
if(isset($_GET["all-services"])){all_services();exit;}
if(isset($_GET["generic-start"])){generic_start();exit;}
if(isset($_GET["parse-blocked"])){parse_blocked();exit;}
if(isset($_GET["meminfo"])){meminfo();exit;}
if(isset($_GET["HugePages"])){HugePages();exit;}
if(isset($_GET["zoneinfo-set"])){zone_info_set();exit;}
if(isset($_GET["uidNumber"])){uidNumber();exit;}
if(isset($_GET["tune2fs-values"])){tune2fs_values();exit;}
if(isset($_GET["INODES_MAX"])){INODES_MAX();exit;}
if(isset($_GET["HardDriveDiskSizeMB"])){HardDriveDiskSizeMB();exit;}
if(isset($_GET["TOTAL_MEMORY_MB"])){TOTAL_MEMORY_MB();exit;}
if(isset($_GET["archiverlogs"])){archiverlogs();exit;}

if(isset($_GET["wizard-execute"])){wizard_execute();exit;}
if(isset($_GET["ucarp-compile"])){ucarp_compile();exit;}
if(isset($_GET["ucarp-status"])){ucarp_status();exit;}
if(isset($_GET["ucarp-start-tenir"])){ucarp_start();exit;}
if(isset($_GET["ucarp-stop-tenir"])){ucarp_stop();exit;}
if(isset($_GET["syslogdb-restart"])){syslogdb_restart();exit;}
if(isset($_GET["syslogdb-status"])){syslogdb_status();exit;}
if(isset($_GET["syslogdb-query"])){syslogdb_query();exit;}
if(isset($_GET["logrotate-query"])){logrotate_query();exit;}
if(isset($_GET["BuildCSR"])){BuildCSR();exit;}
if(isset($_GET["SYSTEMS_ALL_PARTITIONS"])){SYSTEMS_ALL_PARTITIONS();exit;}
if(isset($_GET["apply-patch"])){APPLY_PATCH();exit;}
if(isset($_GET["apply-soft"])){APPLY_SOFT();exit;}
if(isset($_GET["syslogarchive-logs"])){syslogarchive_logs();exit;}
if(isset($_GET["vlans-delete"])){vlans_delete();exit;}
if(isset($_GET["routes-apply-perform"])){routes_apply_perform();exit;}
if(isset($_GET["routes-show"])){routes_show();exit;}
if(isset($_GET["virtip-delete"])){virtip_delete();exit;}
if(isset($_GET["ifconfig-show"])){ifconfig_show();exit;}
if(isset($_GET["ifconfig-initd"])){ifconfig_initd();exit;}
if(isset($_GET["bridge-delete"])){bridge_delete();exit;}
if(isset($_GET["ifconfig-initdcontent"])){ifconfig_initdcontent();exit;}
if(isset($_GET["network-initdcontent"])){ifconfig_save_initdcontent();exit;}
if(isset($_GET["artica-ifup"])){artica_ifup();exit;}
if(isset($_GET["etchosts-default"])){etchosts_default();exit;}
if(isset($_GET["etchosts-build"])){etchosts_build();exit;}
if(isset($_GET["rsync-debian-status"])){rsync_debian_status();exit;}
if(isset($_GET["DF_SATUS_K"])){DF_SATUS_K();exit;}
if(isset($_GET["debian_version"])){debian_version();exit;}
if(isset($_GET["refresh-index-ini"])){refresh_index_ini();exit;}
if(isset($_GET["uncompress-root"])){uncompress_root();exit;}
if(isset($_GET["arp-resolve"])){arp_resolve();exit;}
if(isset($_GET["backup-restore-new"])){backup_restore();exit;}
if(isset($_GET["nmap-scan-single"])){nmap_scan_single();exit;}
if(isset($_GET["ntopng-installed"])){ntopng_installed();exit;}
if(isset($_GET["ntopng-restart"])){ntopng_restart();exit;}
if(isset($_GET["ntopng-status"])){ntopng_status();exit;}
if(isset($_GET["netdata-status"])){netdata_status();exit;}

if(isset($_GET["set-apache-perms"])){set_apache_perms();exit;}
if(isset($_GET["copytocache"])){copytocache();exit;}
if(isset($_GET["refresh-logs-storefiles"])){refresh_logs_storefiles();exit;}
if(isset($_GET["ping-host"])){ping_host();exit;}
if(isset($_GET["etc-timezone"])){etc_timezone();exit;}
if(isset($_GET["ucarp-isactive"])){ucarp_isactive();exit;}
if(isset($_GET["ps-mem"])){ps_mem();exit;}
if(isset($_GET["empty-swap"])){empty_swap();exit;}
if(isset($_GET["force-databases"])){force_databases();exit;}


if(isset($_GET["install-cluster-master"])){install_cluster_master();exit;}
if(isset($_GET["uninstall-cluster-master"])){uninstall_cluster_master();exit;}


if(isset($_GET["overcommit-mem"])){overcommit_mem();exit;}



if(isset($_GET["installv2"])){installv2();exit;}
if(isset($_GET["roolback-sp"])){roolback_sp();exit;}
if(isset($_GET["kernel-events"])){searchlogs_kernel();exit;}
if(isset($_GET["delete-all-sps"])){delete_all_sp_js();exit;}
if(isset($_GET["roolback-global"])){roolback_global();exit;}
if(isset($_GET["upgrade-php-47"])){upgrade_php_47();exit;}

if(isset($_GET["TrackAdmins-install"])){trackadmin_install();exit;}
if(isset($_GET["TrackAdmins-uninstall"])){trackadmin_uninstall();exit;}
if(isset($_GET["uprade-samba"])){upgrade_samba();exit;}
if(isset($_GET["uprade-openldap"])){upgrade_openldap();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}
writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function reset_all():bool{
    $unix=new unix();
    $unix->framework_execute("exec.reset.php --confirm --byconsole","system.reset.progress","system.reset.progress.log");
    return true;
}
function reset_rrd(){
    $unix=new unix();
    $base="/home/artica/rrd";
    $rm=$unix->find_program("rm");
    shell_exec("$rm -rf $base/*");
    shell_exec("$rm -rf /usr/share/artica-postfix/img/squid/*");
    if(is_file("/etc/artica-postfix/pids/exec.rrd.php.scan_squid.pid")){
        @unlink("/etc/artica-postfix/pids/exec.rrd.php.scan_squid.pid");
    }
    $unix->framework_exec("exec.rrd.php");
    $unix->framework_exec("exec.webconsole.objects.php");
}


function force_databases():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.convert-to-sqlite.php --force");
}

function artica_notifs_restart():bool{
    $unix=new unix();
    $unix->framework_exec("exec.smtpd.php --restart");
    return true;
}
function system_user_chpasswd():bool{
    $main=unserialize(base64_decode($_GET["system-user-pass"]));
    $user=$main[0];
    $password=$main[1];
    $unix=new unix();
    $chpasswd=$unix->find_program("chpasswd");
    $echo=$unix->find_program("echo");
    $passwd=$unix->shellEscapeChars($password);

    $cmd="$echo $user:$passwd | $chpasswd 2>&1";
    exec("$cmd",$results);
    foreach ($results as $line){
        writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
    }
    return true;
}


function system_user_add():bool{
    $main=unserialize(base64_decode($_GET["system-user-add"]));
    $user=$main[0];
    $password=$main[1];
    $unix=new unix();
    if(!$unix->SystemCreateUser($user,$user,"/bin/bash","/home/$user")){
        @file_put_contents(PROGRESS_DIR."/useradd-$user","FALSE");
        @file_put_contents(PROGRESS_DIR."/useradd-$user.log",@implode("<br>",$GLOBALS["SystemCreateUser"]));
        return false;
    }
    if(!is_dir("/home/$user")){
        @mkdir("/home/$user/.ssh",0700,true);
    }
    if(!is_file("/home/$user/.ssh/authorized_keys")){
        @touch("/home/$user/.ssh/authorized_keys");
    }
    $chpasswd=$unix->find_program("chpasswd");
    $echo=$unix->find_program("echo");
    $passwd=$unix->shellEscapeChars($password);

    $cmd="$echo $user:$passwd | $chpasswd 2>&1";
    exec("$cmd",$results);
    foreach ($results as $line){
        writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
    }

    @chown("/home/$user",$user);
    @chown("/home/$user/.ssh",$user);
    @chown("/home/$user/.ssh/authorized_keys",$user);
    @chmod("/home/$user/.ssh/authorized_keys",0600);
    @file_put_contents(PROGRESS_DIR."/useradd-$user","TRUE");
    return true;
}


function roolback_global():bool{
    $unix=new unix();
    $version=$_GET["roolback-global"];
    $unix->framework_execute("exec.nightly.php --rollback $version","roolback.progress","roolback.progress.txt");
    return true;
}
function upgrade_samba(){
    $unix=new unix();
    $unix->framework_execute("exec.samba.upgrade.php --upgrade","exec.samba.upgrade.progress","exec.samba.upgrade.txt");
}
function upgrade_openldap(){
    $unix=new unix();
    $unix->framework_execute("exec.openldap.upgrade.php --upgrade","exec.openldap.upgrade.progress","exec.openldap.upgrade.txt");
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
    $cmd="$grep --binary-files=text -i -E '$search' $source_file 2>&1|$tail -n $rp >$target_file 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec("$cmd");

}

function arp_resolve(){
	$ip=$_GET["arp-resolve"];
	$unix=new unix();
	$arp=$unix->find_program("arp");
	$mac = shell_exec("$arp -an $ip 2>&1");
	preg_match('/..:..:..:..:..:../',$mac , $matches);
	$mac = @$matches[0];
	echo "<articadatascgi>$mac</articadatascgi>";
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

function uncompress_root(){
	$unix=new unix();
	$SQUID=false;
	$php5=$unix->LOCATE_PHP5_BIN();
	$tar=$unix->find_program("tar");
	$nohup=$unix->find_program("nohup");
	$filename=$_GET["uncompress-root"];
	
	if(preg_match("#^squid.*?#", $filename)){$SQUID=TRUE;}
	
	$FilePath="/usr/share/artica-postfix/ressources/conf/upload/$filename";

	if(!is_file($FilePath)){
		writelogs_framework("$FilePath -> no such file",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: $FilePath no such file")))."</articadatascgi>";
	}
	
	
	$cmd="$tar  -tvvf $FilePath 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	foreach ($results as $num=>$line){
		if(preg_match("#Unrecognized archive#i", $line)){
			@unlink($FilePath);
			echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: Unrecognized archive format")))."</articadatascgi>";
			return;
		}
		
		if(preg_match("#Archive Format:.*?null.*?Compression: none#i", $line)){
			@unlink($FilePath);
			echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: Corrupted archive format")))."</articadatascgi>";
			return;
		}	

		if(preg_match("#Error exit delayed from previous errors#i", $line)){
			@unlink($FilePath);
			echo "<articadatascgi>".base64_encode(serialize(array("R"=>false,"T"=>"{failed}: exit delayed from previous errors")))."</articadatascgi>";
			return;
		}		

		
		writelogs_framework($line,__FUNCTION__,__FILE__,__LINE__);
	}
	
	$cmd="$tar -xhf $FilePath -C / 2>&1";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	
	echo "<articadatascgi>".base64_encode(serialize(array("R"=>true,"T"=>"{success}")))."</articadatascgi>";
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	
	if($SQUID){
		shell_exec("$nohup /etc/init.d/squid restart --force >/dev/null 2>&1 &");
		shell_exec("$nohup /etc/init.d/ufdb restart --force >/dev/null 2>&1 &");
	}
	
}

function debian_version(){
	if(!is_file("/etc/debian_version")){return;}
	$ver=trim(@file_get_contents("/etc/debian_version"));
	preg_match("#^([0-9]+)\.#",$ver,$re);
	if(preg_match("#squeeze\/sid#",$ver)){return 6;}
	$Major=$re[1];
	if(!is_numeric($Major)){return;}
	
	echo "<articadatascgi>$Major</articadatascgi>";
	
}

function hostname_g(){
	$unix=new unix();
	$hostname=$unix->hostname_g();
	if($hostname==null){$hostname="localhost.localdomain";}
	echo "<articadatascgi>$hostname</articadatascgi>";
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

function TOTAL_MEMORY_MB(){
	$unix=new unix();
	echo "<articadatascgi>". $unix->TOTAL_MEMORY_MB()."</articadatascgi>";
}



function SYSTEMS_ALL_PARTITIONS(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode(serialize($unix->SYSTEMS_ALL_PARTITIONS()))."</articadatascgi>";
}

function DF_SATUS_K(){
	$unix=new unix();
	echo "<articadatascgi>". base64_encode(serialize($unix->DF_SATUS_K($_GET["DF_SATUS_K"])))."</articadatascgi>";	
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

function dirdir(){
	$path=base64_decode($_GET["dirdir"]);
	$unix=new unix();
	$array=$unix->dirdir($path);
	echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";
}

function process1(){
	$unix=new unix();
	$unix->Process1(true);
}

function routes_show(){
	$unix=new unix();
	$ip=$unix->find_program("ip");
	exec("$ip route show 2>&1",$results);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
}

function restart_ldap(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	
	$EnableOpenLDAP=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableOpenLDAP"));
	if($EnableOpenLDAP==0){return;}
	
	
	$cmd=trim("$php /usr/share/artica-postfix/exec.initslapd.php >/dev/null 2>&1");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);		
	shell_exec($cmd);
	$cmd=trim("$nohup $php /etc/init.d/slapd restart --framework=". basename(__FILE__)." >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function parse_blocked(){

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

function HugePages(){
	
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.HugePages.php >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
}
function zone_info_set(){
    $unix       = new unix();
    $nohup      = $unix->find_program("nohup");
	$zone       = base64_decode($_GET["zoneinfo-set"]);
	$sourcefile = "/usr/share/zoneinfo/$zone";
	if(!is_file($sourcefile)){
		echo "<articadatascgi>". $sourcefile ." not found !</articadatascgi>";
		writelogs_framework("$sourcefile no such file!!",__FUNCTION__,__FILE__,__LINE__);
		return;
	}
	writelogs_framework("$sourcefile -> /etc/localtime",__FUNCTION__,__FILE__,__LINE__);
	@unlink("/etc/localtime");
	@copy($sourcefile, "/etc/localtime");
	@file_put_contents("/etc/timezone", $zone);
    shell_exec("$nohup /usr/sbin/dpkg-reconfigure -f noninteractive tzdata >/dev/null 2>&1 &");
	echo "<articadatascgi>$sourcefile defined OK</articadatascgi>";
}

function uidNumber(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup $php /usr/share/artica-postfix/exec.uidMember.php >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function tune2fs_values(){
	$unix=new unix();
	if(isset($_GET["dirscan"])){
		$dirscan=base64_decode($_GET["dirscan"]);
		$unix->dirdir($dirscan);
	}
	$dev=base64_decode($_GET["tune2fs-values"]);
	
	echo "<articadatascgi>". base64_encode(serialize($unix->tune2fs_values($dev)))."</articadatascgi>";
}

function INODES_MAX(){
	$unix=new unix();
	$dev=base64_decode($_GET["dev"]);
	$INODES_MAX=$_GET["INODES_MAX"];
	$INODE_SIZE=$_GET["INODE_SIZE"];
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

function ucarp_compile(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$nohup /etc/init.d/artica-failover restart >/dev/null 2>&1 &");
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}

function ucarp_status(){
	$unix=new unix();
	$eth=$_GET["ucarp-status"];
	$pgrep=$unix->find_program("pgrep");
	$ucarp_bin=$unix->find_program("ucarp");
	if($eth<>null){$eth=".*?--interface=$eth";}
	
	$pid=$unix->PIDOF_PATTERN("$ucarp_bin$eth");
	writelogs_framework("$pid = PIDOF_PATTERN($ucarp_bin$eth)",__FUNCTION__,__FILE__,__LINE__);
	if(!$unix->process_exists($pid)){
		writelogs_framework("$pid = NOT IN MEMORY",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>". base64_encode(serialize(array()))."</articadatascgi>";	
		return;
	}
	writelogs_framework("$pid =OK",__FUNCTION__,__FILE__,__LINE__);
	$pidtim=$unix->PROCCESS_TIME_MIN($pid);
	echo "<articadatascgi>". base64_encode(serialize(array("PID"=>$pid,"TIME"=>$pidtim)))."</articadatascgi>";
	
	
}

function ucarp_status_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.status.php --ucarp --nowachdog";
	exec($cmd,$results);
	writelogs_framework($cmd." ->".count($results)." lines",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>". base64_encode(implode("\n",$results))."</articadatascgi>";
	
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

function BuildCSR(){
	$unix=new unix();
	$commonName=$_GET["BuildCSR"];
	writelogs_framework("commonName = $commonName",__FUNCTION__,__FILE__,__LINE__);
	$commonName=str_replace('*', "_ALL_", $commonName);
	$php=$unix->LOCATE_PHP5_BIN();
	$cmd="$php /usr/share/artica-postfix/exec.openssl.php --BuildCSR $commonName 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";
}

function syslogdb_status(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();	
	$cmd="$php5 /usr/share/artica-postfix/exec.status.php --syslog-db --nowachdog";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	exec($cmd,$results);
	echo "<articadatascgi>". base64_encode(@implode("\n",$results))."</articadatascgi>";

}
function APPLY_PATCH(){
	$filename="/usr/share/artica-postfix/ressources/conf/upload/{$_GET["apply-patch"]}";
	if(!is_file($filename)){
		echo "<articadatascgi>". base64_encode(serialize(array("$filename no such file")))."</articadatascgi>";
		return;
	}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	exec("$tar -xvf $filename -C /usr/share/artica-postfix/ 2>&1",$results);
	@unlink($filename);
	$results[]="Done...";
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	
}
function APPLY_SOFT(){
	$filename="/usr/share/artica-postfix/ressources/conf/upload/{$_GET["apply-soft"]}";
	if(!is_file($filename)){
		echo "<articadatascgi>". base64_encode(serialize(array("$filename no such file")))."</articadatascgi>";
		return;
	}
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$tar=$unix->find_program("tar");
	$results[]="Copy to $filename to /root ";
	@copy($filename, "/root/" .basename($filename));
	@unlink($filename);
	chdir("/root");
	exec("$tar -xvf /root/".basename($filename)." -C / 2>&1",$results);
	$results[]="Done...";
	
	if(preg_match("#^nginx-#", $filename)){
		$results[]="Ask to restarting nginx";
		shell_exec("$nohup /etc/init.d/nginx restart >/dev/null 2>&1 &");
	}
	
	@unlink($filename);
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	shell_exec("$nohup /etc/init.d/artica-status reload >/dev/null 2>&1 &");
	shell_exec("$nohup /etc/init.d/monit restart  >/dev/null 2>&1 &");
	$unix->Process1(true);
	
	
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




function vlans_delete(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --vlans-delete {$_GET["vlans-delete"]} >/dev/null 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}
function virtip_delete(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --virtip-delete {$_GET["virtip-delete"]} >/dev/null 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
}


function routes_apply_perform(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
if(!is_dir('/usr/share/artica-postfix/ressources/logs/web')){@mkdir('/usr/share/artica-postfix/ressources/logs/web',0755,true);}
	$cmd="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --main-routes >/usr/share/artica-postfix/ressources/logs/web/routes-apply.log 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	@chmod(PROGRESS_DIR."/routes-apply.log", 0777);
}

function ifconfig_show(){
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig -a 2>&1",$results);
	$results[]="\n\t***************\n";
	$ip=$unix->find_program("ip");
	exec("$ip link show 2>&1",$results);
	$results[]="\n\t***************\n";	
	exec("$ip route 2>&1",$results);
	$results[]="\n\t***************\n";	
	
	$f=explode("\n",@file_get_contents("/etc/iproute2/rt_tables"));
    foreach ($f as $line){
		if(!preg_match("#^([0-9]+)\s+(.+)#", $line,$re)){continue;}
		$table_num=$re[1];
		$tablename=$re[2];
		if($table_num==0){continue;}
		if($table_num>252){continue;}
		$results[]="\n\t***** Table route $table_num named $tablename *****\n";
		exec("$ip route show table $table_num 2>&1",$results);
		$results[]="\n\t***************\n";
	}
	
	
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}
function ifconfig_initd(){
	$unix=new unix();
	$results=explode("\n",@file_get_contents("/etc/init.d/artica-ifup"));
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";
	
}
function ifconfig_initdcontent(){
	$unix=new unix();
	$results=explode("\n",@file_get_contents("/etc/init.d/artica-ifup-content.sh"));
	echo "<articadatascgi>". base64_encode(serialize($results))."</articadatascgi>";

}
function ifconfig_save_initdcontent(){
	$data=base64_decode($_GET["network-initdcontent"]);
	@file_put_contents("/etc/init.d/artica-ifup-content.sh", $data."\n");
	@chmod("/etc/init.d/artica-ifup-content.sh",0755);
}

function bridge_delete(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --bridge-delete {$_GET["bridge-delete"]} >/dev/null 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}
function artica_ifup(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	ToSyslog("kernel: [  Artica-Net] start Network [artica-ifup] (".basename(__FILE__)."/".__LINE__.")" );
	shell_exec("$nohup /usr/sbin/artica-phpfpm-service -restart-network --script=system.php/artica_ifup >/dev/null 2>&1 &");
}

function etchosts_default(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts-defaults >/dev/null 2>&1";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
}

function ToSyslog($text){
	if(!function_exists("syslog")){return;}
	$file=basename(__FILE__);
	$LOG_SEV=LOG_INFO;
	openlog("framework", LOG_PID , LOG_SYSLOG);
	syslog($LOG_SEV, $text);
	closelog();
}

function etchosts_build(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/etchosts.progress";
	$GLOBALS["CACHEFILE"]=$GLOBALS["PROGRESS_FILE"];
	$GLOBALS["LOGSFILES"]=PROGRESS_DIR."/etchosts.progress.txt";
	
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);
	
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.virtuals-ip.php --hosts >{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);

	if(is_file("/etc/init.d/dnsdist")){
        $cmd="$nohup /etc/init.d/dnsdist restart >/dev/null 2>&1 &";
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
    }
    if(is_file("/etc/init.d/unbound")){
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
    }
    if(is_file("/etc/init.d/squid-dns")){
        $cmd="$nohup $php5 /usr/share/artica-postfix/exec.squid.dns.php --reload --force >/dev/null 2>&1 &";
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
    }

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

function gethostbyname2(){
	$host=$_GET["gethostbyname"];
	$unix=new unix();
	$dig=$unix->find_program("dig");
	writelogs_framework("$dig $host 2>&1}",__FUNCTION__,__FILE__,__LINE__);
	exec("$dig $host 2>&1",$results);
    foreach ($results as $line){
		if(preg_match("#[0-9]+\s+IN\s+A\s+([0-9\.]+)#", $line,$re)){
			writelogs_framework("$host -> {$re[1]}",__FUNCTION__,__FILE__,__LINE__);
			echo "<articadatascgi>{$re[1]}</articadatascgi>";
		}
	}
	
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

function proc_net_dev(){
	$data=@file_get_contents("/proc/net/dev");
	echo "<articadatascgi>".base64_encode($data)."</articadatascgi>";
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
function group_adduser():bool{
    $unix=new unix();
    $pattern=unserialize(base64_decode($_GET["group-adduser"]));
    $gpname=$pattern[0];
    $user=$pattern[1];
    $fname=PROGRESS_DIR."/linkuser.$gpname.$user";
    if(is_file($fname)){@unlink($fname);}
    if(!$unix->SystemAddUserToGroup($user,$gpname)){
        @file_put_contents($fname,@implode("\n",$GLOBALS["SystemCreateUser"]));
        return false;
    }
    return true;

}

function group_deluser():bool{
    $unix=new unix();
    $pattern=unserialize(base64_decode($_GET["group-deluser"]));
    $gpname=$pattern[0];
    $user=$pattern[1];
    $gpasswd=$unix->find_program("gpasswd");
    shell_exec("$gpasswd -d \"$user\" \"$gpname\"");
    return true;
}

function group_add():bool{
    $unix=new unix();
    $groupadd=$unix->find_program("groupadd");
    $group=$_GET["group-add"];
    $fname=PROGRESS_DIR."/groupadd.$group";
    shell_exec("$groupadd \"$group\" >$fname 2>&1");
    return true;
}
function group_delete():bool{
    $unix=new unix();
    $groupdel=$unix->find_program("groupdel");
    $group=$_GET["group-delete"];
    $fname=PROGRESS_DIR."/groupdel.$group";
    shell_exec("$groupdel \"$group\" >$fname 2>&1");
    return true;
}

function create_user(){
	$data=$_GET["create-user"];
	@mkdir("/usr/share/artica-postfix/ressources/logs/web/create-users",0755,true);
	$filename=md5($data);
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/create-users/$filename", $data);
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	exec("$php5 /usr/share/artica-postfix/exec.create-user.php --create $filename 2>&1",$results);
	echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";
}

function create_user_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$GLOBALS["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/create-user.progress";
	$GLOBALS["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/create-user.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	@touch($GLOBALS["PROGRESS_FILE"]);
	@touch($GLOBALS["LOG_FILE"]);
	@chmod($GLOBALS["PROGRESS_FILE"], 0755);
	@chmod($GLOBALS["LOG_FILE"], 0755);
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.create-user.php --progress >{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
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


function test_a_route(){
	$unix=new unix();
	$item=$_GET["test-a-route"];
	
	if(!$unix->isIPAddress($item)){
		$item=gethostbyname($item);
	}
	$results[]="Testing route for $item";
	$unix=new unix();
	$ip=$unix->find_program("ip");
	$cmd="$ip route get $item 2>&1";
	writelogs_framework($cmd);
	exec($cmd,$results);
	echo "<articadatascgi>".base64_encode(@implode("\n", $results))."</articadatascgi>";
}


function syslog_purge_to_nas(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.logrotate.php --purge-nas >/dev/null 2>&1 &";
	writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
}
function MEM_TOTAL_INSTALLEE(){
	$unix=new unix();
	$MEM_TOTAL_INSTALLEE=$unix->MEM_TOTAL_INSTALLEE();
	echo "<articadatascgi>$MEM_TOTAL_INSTALLEE</articadatascgi>";
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
function upgrade_php_47():bool{
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    @chmod("/usr/share/artica-postfix/bin/php-upgrade",0755);
    shell_exec("$nohup /usr/share/artica-postfix/bin/php-upgrade >/dev/null 2>&1 &");
    return true;
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

function mylinux(){
	$unix=new unix();
	$ARRAY["LINUX_CODE_NAME"]=trim($unix->LINUX_CODE_NAME());
	$ARRAY["LINUX_DISTRIBUTION"]=trim($unix->LINUX_DISTRIBUTION());
	$ARRAY["LINUX_VERS"]=trim($unix->LINUX_VERS());
	$ARRAY["LINUX_ARCHITECTURE"]=trim($unix->LINUX_ARCHITECTURE());
	echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
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

function installv2(){
    $fileuploaded=null;
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.installsoft.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.installsoft.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"], 0755);
	@chmod($GLOBALS["LOGSFILES"], 0755);


	if(isset($_GET["file-uploaded"])) {
        $fileuploaded = " \"/usr/share/artica-postfix/ressources/conf/upload/{$_GET["file-uploaded"]}\" ";
    }
	
	
	$product=$_GET["product"];
	$key=$_GET["key"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	
	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.installv2.php --install \"$product\" \"$key\"{$fileuploaded} >>{$GLOBALS["LOGSFILES"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);	
	
	
}

function CREATE_NEW_UUID(){
    if(is_file("/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED")){@unlink("/etc/artica-postfix/settings/Daemons/SYSTEMID_CREATED");}
    $unix=new unix();
    $unix->CREATE_NEW_UUID();
}
function MII_TOOLS(){
	$unix=new unix();
	$eth=$_GET["eth"];
	$miitool=$unix->find_program("mii-tool");
	if(!is_file($miitool)){
		$ARRAY["STATUS"]=false;
		$ARRAY["ERROR"]="mii-tool no such binary";
        writelogs_framework("mii-tool no such binary",__FUNCTION__,__FILE__,__LINE__);
		echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
		return false;
	}
    writelogs_framework("$miitool -v $eth 2>&1",__FUNCTION__,__FILE__,__LINE__);
	exec("$miitool -v $eth 2>&1",$results);
	foreach ($results as $num=>$line){
        writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
		if(preg_match("#failed#", $line)){
			$ARRAY["STATUS"]=false;
			$ARRAY["ERROR"]="$line";
			echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
			return false;
		}
		if(preg_match("#$eth:\s+(.+)#", $line,$re)){
			$ARRAY["STATUS"]=true;
			$ARRAY["INFOS"]=$line;
			$ARRAY["AUTONEG"]=0;
			$ARRAY["FLOWC"]=0;
			if(preg_match("#flow-control#", $line)){
				$ARRAY["FLOWC"]=1;
			}
			
			continue;
		}
		
		if(preg_match("#product info:\s+(.+)#", $line,$re)){
			$ARRAY["PRODUCT"]=trim($re[1]);
		}
		
		if(preg_match("#autonegotiation.*?enabled#", $line,$re)){
			$ARRAY["AUTONEG"]=1;
		}
		
		if(preg_match("#capabilities:\s+(.+)#",$line,$re)){
			$cap=explode(" ",$re[1]);
			foreach ($cap as $b){
				if(trim($b)==null){continue;}
				$ARRAY["CAP"][$b]=true;
			}
		}
		
	}
	
	echo "<articadatascgi>".base64_encode(serialize($ARRAY))."</articadatascgi>";
	
}
function MII_TOOLS_SAVE(){
	$unix=new unix();
	$flow_control=$_GET["flow-control"];
	$autonegotiation=$_GET["autonegotiation"];
	$duptype=$_GET["duptype"];
	$eth=$_GET["MII-TOOL"];
	$miitool=$unix->find_program("mii-tool");
	if(!is_file($miitool)){return;}
	if($flow_control==1){$flow_control_text=" flow-control";}
	
	if($autonegotiation==1){
	$cmd="$miitool --force=$duptype $eth";
	}else{
		$cmd="$miitool --advertise=$duptype $eth";
	}
	writelogs_framework("$cmd",__FUNCTION__,__LINE__);
	echo "<articadatascgi>".base64_encode(shell_exec($cmd))."</articadatascgi>";
}
function install_artica_tgz(){
	$filename=$_GET["filename"];
	$unix=new unix();
    $unix->framework_execute("exec.artica.update.manu.php \"$filename\"", "artica.install.progress","artica.install.progress.txt");
    $size=@filesize("/usr/share/artica-postfix/ressources/conf/upload/$filename");
    $size=$size/1024;
    $size=$size/1024;
    $size=round($size,2);
    eventsAPIREST("[FRAMEWORK]: $filename == $size MB",__LINE__);


}
function eventsAPIREST($text,$line=0){
    if($line>0){$text="$text [$line]";}
    $LOG_SEV=LOG_INFO;
    if(function_exists("openlog")){openlog("REST_API", LOG_PID , LOG_SYSLOG);}
    if(function_exists("syslog")){ syslog($LOG_SEV, $text);}
    if(function_exists("closelog")){closelog();}
}

function install_artica_key(){
	$filename=$_GET["filename"];
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$GLOBALS["PROGRESS_FILE"]=PROGRESS_DIR."/artica.keyfile.progress";
	$GLOBALS["LOG_FILE"]=PROGRESS_DIR."/artica.keyfile.progress.txt";
	@unlink($GLOBALS["PROGRESS_FILE"]);
	@unlink($GLOBALS["LOG_FILE"]);
	$array["POURC"]=0;
	$array["TEXT"]="{please_wait}";
	@file_put_contents($GLOBALS["PROGRESS_FILE"], serialize($array));
	@chmod($GLOBALS["PROGRESS_FILE"],0755);
	@unlink($GLOBALS["LOG_FILE"]);
	@file_put_contents($GLOBALS["LOG_FILE"], "Please Wait....\n");
	@chmod($GLOBALS["LOG_FILE"], 0755);



	$cmd="$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --install-key \"$filename\" >>{$GLOBALS["LOG_FILE"]} 2>&1 &";
	writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
	shell_exec($cmd);
	
	
}

function set_apache_perms(){
	$unix=new unix();
	$APACHE=$unix->APACHE_SRC_ACCOUNT();
	@mkdir("/etc/artica-postfix/settings/Daemons",0755,true);
	@mkdir("/usr/share/artica-postfix/ressources/logs",0755,true);
	$unix->chown_func($APACHE,null,"/etc/artica-postfix/settings/Daemons");
	$unix->chown_func($APACHE,null,"/usr/share/artica-postfix/ressources/logs");
	$unix->chown_func($APACHE,null,"/etc/artica-postfix/settings/Daemons/*");
	$unix->chown_func($APACHE,null,"/usr/share/artica-postfix/ressources/logs/*");
	$unix->chmod_func(0755, "/etc/artica-postfix/settings/Daemons/*");
	$unix->chmod_func(0755, "/usr/share/artica-postfix/ressources/logs/*");
}

function copytocache(){
	$unix=new unix();
	$path=$_GET["copytocache"];
	if(!is_file($path)){echo "<articadatascgi>No such file</articadatascgi>";
	writelogs("$path -> No such file");
	return;}
	$basename=basename($path);
	
	writelogs("COPY $path -> /usr/share/artica-postfix/ressources/logs/$basename");
	
	if(is_file("/usr/share/artica-postfix/ressources/logs/$basename")){@unlink("/usr/share/artica-postfix/ressources/logs/$basename");}
	if(!copy($path, "/usr/share/artica-postfix/ressources/logs/$basename")){
		echo "<articadatascgi>Copy failed</articadatascgi>";return;}
	$APACHE=$unix->APACHE_SRC_ACCOUNT();
	$APACHE_GROUP=$unix->APACHE_SRC_GROUP();
	$unix->chown_func($APACHE,$APACHE_GROUP,"/usr/share/artica-postfix/ressources/logs/$basename");
	$unix->chmod_func(0755, "/usr/share/artica-postfix/ressources/logs/$basename");
	
		
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




function dnsperf_progress(){
		$unix=new unix();
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
	
		$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/admin.dashboard.dnsperfs.progress";
		$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/admin.dashboard.dnsperfs.progress.txt";
		@unlink($GLOBALS["CACHEFILE"]);
		@unlink($GLOBALS["LOGSFILES"]);
		@touch($GLOBALS["CACHEFILE"]);
		@touch($GLOBALS["LOGSFILES"]);
		@chmod($GLOBALS["CACHEFILE"], 0755);
		@chmod($GLOBALS["LOGSFILES"], 0755);
		$cmd="$nohup $php5 /usr/share/artica-postfix/exec.dnsperf.php --force >{$GLOBALS["LOGSFILES"]} 2>&1 &";
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

function change_new_uuid(){
	$unix=new unix();
	$chattr=$unix->find_program("chattr");
	shell_exec("$chattr -i /etc/artica-postfix/settings/Daemons/SYSTEMID");
	$uuid=trim($unix->gen_uuid());
	if(strlen($uuid)>5){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEMID", $uuid);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEMID_CREATED", time());
		@chmod("/etc/artica-postfix/settings/Daemons/SYSTEMID", 0777);
		shell_exec("$chattr +i /etc/artica-postfix/settings/Daemons/SYSTEMID");
	
	}
	
}

function overcommit_mem(){

    $ARRAY["PROGRESS_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.progress";
    $ARRAY["LOG_FILE"]="/usr/share/artica-postfix/ressources/logs/web/system.memory.progress.txt";
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");

    @unlink($ARRAY["PROGRESS_FILE"]);
    @unlink($ARRAY["LOG_FILE"]);
    @touch($ARRAY["PROGRESS_FILE"]);
    @touch($ARRAY["LOG_FILE"]);
    @chmod($ARRAY["PROGRESS_FILE"],0777);
    @chmod($ARRAY["LOG_FILE"],0777);
    system("$nohup $php5 /usr/share/artica-postfix/exec.overcommit.memory.php >{$ARRAY["LOG_FILE"]} 2>&1 &");
    writelogs_framework("$nohup $php5 /usr/share/artica-postfix/exec.overcommit.memory.php >{$ARRAY["LOG_FILE"]} 2>&1 & ",__FUNCTION__,__FILE__,__LINE__);
}

function sysctl_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");

	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/sysctl.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/sysctl.progress.log";

	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);	
	system("$nohup $php5 /usr/share/artica-postfix/exec.sysctl.php --restart --force >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	writelogs_framework("$nohup $php5 /usr/share/artica-postfix/exec.sysctl.php --restart --force >{$GLOBALS["LOGSFILES"]} 2>&1 & ",__FUNCTION__,__FILE__,__LINE__);	
}

function dirs_monitors_execute(){
		$unix=new unix();
		$php5=$unix->LOCATE_PHP5_BIN();
		$nohup=$unix->find_program("nohup");
		
		$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/system.dirmon.progress";
		$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/system.dirmon.progress.txt";


		@unlink($GLOBALS["CACHEFILE"]);
		@unlink($GLOBALS["LOGSFILES"]);
		@touch($GLOBALS["CACHEFILE"]);
		@touch($GLOBALS["LOGSFILES"]);
		@chmod($GLOBALS["CACHEFILE"],0777);
		@chmod($GLOBALS["LOGSFILES"],0777);
		system("$nohup $php5 /usr/share/artica-postfix/exec.philesight.php --directories --force >{$GLOBALS["LOGSFILES"]} 2>&1 &");
		writelogs_framework("$nohup $php5 /usr/share/artica-postfix/exec.philesight.php --directories --force >{$GLOBALS["LOGSFILES"]} 2>&1 & ",__FUNCTION__,__FILE__,__LINE__);
}

function dashboard_refresh(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/admin.refresh.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/admin.refresh.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	system("$nohup $php5 /usr/share/artica-postfix/exec.squid.interface-size.php --force --progress >{$GLOBALS["LOGSFILES"]} 2>&1 &");	
	
	
}




function php_snmp_progress(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/php-snmp.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/php-snmp.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	system("$nohup $php5 /usr/share/artica-postfix/exec.lighttpd.php --php-snmp >{$GLOBALS["LOGSFILES"]} 2>&1 &");
}
function install_cluster_master(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/artica.cluster.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/artica.cluster.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.cluster.php --install >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    system($cmd);
}
function uninstall_cluster_master(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    $nohup=$unix->find_program("nohup");
    $GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/artica.cluster.progress";
    $GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/artica.cluster.log";
    @unlink($GLOBALS["CACHEFILE"]);
    @unlink($GLOBALS["LOGSFILES"]);
    @touch($GLOBALS["CACHEFILE"]);
    @touch($GLOBALS["LOGSFILES"]);
    @chmod($GLOBALS["CACHEFILE"],0777);
    @chmod($GLOBALS["LOGSFILES"],0777);
    $cmd="$nohup $php5 /usr/share/artica-postfix/exec.cluster.php --uninstall >{$GLOBALS["LOGSFILES"]} 2>&1 &";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    system($cmd);
}






function phpmyadmin_install(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/phpmyadmin.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/phpmyadmin.progress.log";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	system("$nohup $php5 /usr/share/artica-postfix/exec.install-phpmyadmin.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
		
	
}
function upgradev10(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$nohup=$unix->find_program("nohup");
	
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/web/upgradev10.progress";
	$GLOBALS["LOGSFILES"]="/usr/share/artica-postfix/ressources/logs/web/upgradev10.progress.txt";
	@unlink($GLOBALS["CACHEFILE"]);
	@unlink($GLOBALS["LOGSFILES"]);
	@touch($GLOBALS["CACHEFILE"]);
	@touch($GLOBALS["LOGSFILES"]);
	@chmod($GLOBALS["CACHEFILE"],0777);
	@chmod($GLOBALS["LOGSFILES"],0777);
	system("$nohup $php5 /usr/share/artica-postfix/exec.squid.upgradev10.php >{$GLOBALS["LOGSFILES"]} 2>&1 &");
	
		
}



function disks_parent_of(){
	$unix=new unix();
	$disk=$unix->DISK_GET_PARENT_PART($_GET["disk-parent-of"]);
	writelogs_framework("{$_GET["disk-parent-of"]} = $disk",__FUNCTION__,__FILE__,__LINE__);
	echo "<articadatascgi>".base64_encode($disk)."</articadatascgi>";
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

function ping_host(){
	$ipfrom=$_GET["ipfrom"];
	$ipto=$_GET["ipto"];
	$unix=new unix();
	if(!$unix->PingHostCMD($ipto,$ipfrom)){
		echo "<articadatascgi>FALSE</articadatascgi>";
		return;
	}
	echo "<articadatascgi>TRUE</articadatascgi>";

}
function etc_timezone(){
	$content=trim(@file_get_contents("/etc/timezone"));
	echo "<articadatascgi>$content</articadatascgi>";
}

function ucarp_isactive(){
	
	$unix=new unix();
	$ifconfig=$unix->find_program("ifconfig");
	exec("$ifconfig -a 2>&1",$results);
	$interface=null;
	$ipaddr=null;
	foreach ($results as $num=>$ligne){
		if(preg_match("#(.+?):ucarp.*?HWaddr\s+(.+)#",$ligne,$re)){
			$interface=$re[1];
			$MAC=$re[2];
			continue;
		}
		if($interface<>null){
			if(preg_match("#inet\s+addr:([0-9\.]+)\s+Bcast:#",$ligne,$re)){
				$ipaddr=$re[1];
				break;
			}
				
		}
	
	}
	if($interface==null){return;}
	echo "<articadatascgi>". base64_encode(serialize(array("NIC"=>$interface,"MAC"=>$MAC,"IP"=>$ipaddr)))."</articadatascgi>";
	
	
}

function phpmyadmin_installed(){
	if(!is_file("/usr/share/phpmyadmin/index.php")){
		echo "<articadatascgi>FALSE</articadatascgi>";
		return;
	}
	
	echo "<articadatascgi>TRUE</articadatascgi>";
}
function phpmyadmin_version(){
	
	$f=explode("\n",@file_get_contents("/usr/share/phpmyadmin/libraries/Config.class.php"));
	foreach ($f as $num=>$ligne){
		if(preg_match("#PMA_VERSION.*?([0-9\.]+)#", $ligne,$re)){
			echo "<articadatascgi>{$re[1]}</articadatascgi>";
			return;
		}
		
	}
	
}

function  NetDiscover_Restart(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	shell_exec("/etc/init.d/netdiscover restart");
	
}
function modinfo(){
	$unix=new unix();
	$modinfo=$unix->find_program("modinfo");
	$file=exec("modinfo --field=filename {$_GET["modinfo"]} 2>&1");
	if(is_file($file)){echo "<articadatascgi>TRUE</articadatascgi>";}
	
	
	
	
}

function ChangePerformance(){
	$unix=new unix();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();	
	$tmpf=$unix->FILE_TEMP().".sh";
	$H[]="#!/bin/sh";
	$H[]="PATH=/usr/local/sbin:/usr/local/bin:/sbin:/bin:/usr/sbin:/usr/bin";
	$H[]="/usr/sbin/artica-phpfpm-service -nsswitch";
	$H[]="/etc/init.d/slapd restart";
	$H[]="/etc/init.d/artica-memcache restart";
	$H[]="/etc/init.d/artica-syslog restart";
	$H[]="/etc/init.d/mysql restart --force --framework=byhand";
	$H[]="rm /etc/artica-postfix/CPU_NUMBER";
	$H[]="rm -f $tmpf";
	
	$H[]="";
	@file_put_contents($tmpf, @implode("\n", $H));
	@chmod($tmpf, 0755);
	writelogs_framework($tmpf ,__FUNCTION__,__FILE__,__LINE__);
	shell_exec("$nohup $tmpf >/dev/null 2>&1 &");
}

function make_www_writable(){
	$dir=$_GET["make-writable"];
	if(!is_dir($dir)){return;}
	@chmod($dir, 0777);
	
}
function ps_mem(){
	
	exec("/usr/share/artica-postfix/bin/ps_mem.py -s 2>&1",$results);
    $MEM=array();
foreach ($results as $num=>$line){
	if(!preg_match("#[0-9\.]+\s+[A-Z-a-z]+.*?[0-9\.]+\s+[A-Z-a-z]+\s+=\s+([0-9\.]+)\s+([A-Z-a-z]+)\s+(.+)#", $line,$re)){continue;}
	$MEMORY=$re[1];
	$UNIT=$re[2];
	$PROG=trim($re[3]);
	if($UNIT=="KiB"){continue;}
	//writelogs_framework($PROG,__FUNCTION__,__FILE__,__LINE__);
	
	if($UNIT=="KiB"){$MEMORY=$MEMORY/1024;}
	if($UNIT=="GiB"){$MEMORY=$MEMORY*1024;}
	$MEMORY=round($MEMORY);
	if(preg_match("#\/influxd -pidfile#", $PROG)){$PROG="BigData service";}
	if(preg_match("#exec\.logfile_daemon\.php#", $PROG)){$PROG="Proxy Access Logger";}
	if(preg_match("#\/ufdbcatdd -c #", $PROG)){$PROG="Categories service";}
	if(preg_match("#smbd -D#", $PROG)){$PROG="Samba service";}
	if(preg_match("#memcached\.pid#", $PROG)){$PROG="Memory Cache service";}
	if(preg_match("#slapd\.conf -u#", $PROG)){$PROG="OpenLDAP server";}
	if(preg_match("#monit\.state#", $PROG)){$PROG="System Watchdog";}
	if(preg_match("#\(ssl_crtd\)#", $PROG)){$PROG="Proxy SSL Client";}
	if(preg_match("#\(ntlm_auth\)#", $PROG)){$PROG="NTLM Authenticator";}
	if(preg_match("#\(squid-[0-9]+\)#", $PROG)){$PROG="Proxy Service";}
	if(preg_match("#\(squid-coord-[0-9]+\)#", $PROG)){$PROG="Proxy Service";}
	if(preg_match("#sshd:#", $PROG)){$PROG="OpenSSH server";}
	if(preg_match("#\/ufdbgclient\.php#", $PROG)){$PROG="Web Filtering client";}
	if(preg_match("#suricata#", $PROG)){$PROG="IDS service";}
	if(preg_match("#\/external_acl_response\.php#", $PROG)){$PROG="Proxy File Watcher";}
	if(preg_match("#\/external_acl_squid\.php#", $PROG)){$PROG="Proxy ACLs Watcher";}
	if(preg_match("#\/external_acl_squid_ldap\.#", $PROG)){$PROG="Proxy Active Directory Watcher";}
	if(preg_match("#\/exec.ufdbguard-tail\.php#", $PROG)){$PROG="Web Filtering Watcher";}
	if(preg_match("#\/opt\/squidsql#", $PROG)){$PROG="MySQL for Proxy";}
	if(preg_match("#\/var\/run\/mysqld\/mysqld\.sock#", $PROG)){$PROG="MySQL Server";}
	if(preg_match("#\/exec.cache-logs\.php#", $PROG)){$PROG="Proxy Real-time Watchdog";}
	if(preg_match("#\/exec\.syslog\.php#", $PROG)){$PROG="System Watchdog";}
	if(preg_match("#\/bin\/ufdbguardd#", $PROG)){$PROG="Web Filtering Service";}
	if(preg_match("#\/exec\.status\.php#", $PROG)){$PROG="Services Watchdog";}
	if(preg_match("#\/exec.auth-tail\.php#", $PROG)){$PROG="Authentication Watchdog";}
	if(preg_match("#bin\/apache2#", $PROG)){$PROG="Web Service";}
	if(preg_match("#winbindd -D#", $PROG)){$PROG="Winbind Daemon";}
	if(preg_match("#apache2\.conf -k start#", $PROG)){$PROG="Web Service";}
	if(preg_match("#exec\.hypercache-tail#", $PROG)){$PROG="HyperCache Tail logger";}
	if(preg_match("#exec\.web-community-filter\.php#", $PROG)){$PROG="Cloud Update process";}
	if(preg_match("#tmp --log-warnings=2 --default-storage-engine=myisam#", $PROG)){$PROG="MySQL Server";}
	if(preg_match("#clamd.*?clamd.conf#", $PROG)){$PROG="ClamAV Service";}
	if(preg_match("#fail2ban.sock#", $PROG)){$PROG="Fail2ban Service";}
	if(preg_match("#rsyslogd\s+-c#", $PROG)){$PROG="Syslog Service";}
	if(strpos($PROG, "/")>0){
		
		if(strpos($PROG, " ")>0){$TR=explode(" ",$PROG);$PROG=$TR[0];}
	}
	$PROG=basename($PROG);
	$PROG=str_replace("(", "", $PROG);
	$PROG=str_replace(")", "", $PROG);
	
	if($PROG=="php5"){$PROG="php";}
	if(preg_match("#^squid-#", $PROG)){$PROG="Proxy Service";}
	if(!isset($MEM[$PROG])){$MEM[$PROG]=$MEMORY;}else{$MEM[$PROG]=$MEM[$PROG]+$MEMORY;}
}
	
	@file_put_contents("/usr/share/artica-postfix/ressources/logs/web/ps_mem.array", serialize($MEM));
	
	
}


function  DirectoriesMonitorSchedules(){
	
	
	$DirectoriesMonitorH=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DirectoriesMonitorH");
	$DirectoriesMonitorM=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DirectoriesMonitorM");
	$schedule="$DirectoriesMonitorM $DirectoriesMonitorH * * *";
	$unix=new unix();
	writelogs_framework("/etc/cron.d/DirectoriesMonitor -> $schedule" ,__FUNCTION__,__FILE__,__LINE__);
	$unix->Popuplate_cron_make("DirectoriesMonitor",$schedule,"exec.philesight.php --directories");
	shell_exec("/etc/init.d/cron reload &");
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

function remove_directory(){
	
	
	
}

function create_directory(){
	$unix=new unix();
	$directory=urldecode(base64_decode($_GET["create-directory"]));
	writelogs_framework("Create directory [$directory]" ,__FUNCTION__,__FILE__,__LINE__);
	$mkdir=$unix->find_program("mkdir");
	$cmod=$unix->find_program("chmod");
	$directory=$unix->shellEscapeChars($directory);
	system("$mkdir -p \"$directory\"");
	writelogs_framework("$mkdir -p \"$directory\"" ,__FUNCTION__,__FILE__,__LINE__);
	system("$cmod 0755 \"$directory\"");
}



function dhtest(){
	$cmdline=base64_decode($_GET["dhtest"]);
	chmod("/usr/share/artica-postfix/bin/check_dhcp", 0755);
	writelogs_framework("/usr/share/artica-postfix/bin/check_dhcp $cmdline >/usr/share/artica-postfix/ressources/logs/web/dhtest.results" ,__FUNCTION__,__FILE__,__LINE__);
	system("/usr/share/artica-postfix/bin/check_dhcp $cmdline >/usr/share/artica-postfix/ressources/logs/web/dhtest.results 2>&1");
	
}

function phpldapadmin_installed(){
	
	if(is_file("/usr/share/phpldapadmin/index.php")){
		echo "<articadatascgi>TRUE</articadatascgi>";
		
	}
}

