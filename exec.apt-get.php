<?php
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
$GLOBALS["VERBOSE"]         = false;
$GLOBALS["FORCE"]           = false;
$GLOBALS["CLASS_SOCKETS"]   = new sockets();

if(preg_match("#--verbose#",implode(" ",$argv))){
    $GLOBALS["VERBOSE"]=true;
    ini_set('display_errors', 1);
    ini_set('error_reporting', E_ALL);
    ini_set('error_prepend_string',null);
    ini_set('error_append_string',null);
}

if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}


include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.system.network.inc");
include_once(dirname(__FILE__)."/ressources/class.os.system.inc");
include_once(dirname(__FILE__)."/ressources/class.mysql.inc");
include_once(dirname(__FILE__)."/ressources/class.templates.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');


$_GET["APT-GET"]="/usr/bin/apt-get";
if($GLOBALS["VERBOSE"]){echo "Checks $argv[1]\n";}
if($argv[1]=='--deb-collection'){INSERT_DEB_PACKAGES();exit();}
if($argv[1]=='--phpfpm'){php_fpm();exit();}
if($argv[1]=='--phpfpm-daemon'){php_fpm(true);exit();}
if($argv[1]=='--nginx'){exit();}
if($argv[1]=='--pkg-upgrade'){UPGRADE_FROM_INTERFACE();exit();}
if($argv[1]=='--remove-systemd'){removeSystemd();exit();}

if($argv[1]=='--print-upgrade'){print_upgrade();exit();}
if($argv[1]=='--upgrade'){debian_upgrade();exit();}
if($argv[1]=='--dist-upgrade'){debian_dist_upgrade();exit();}
if($argv[1]=='--install'){install_package($argv[2]);exit();}
if($argv[1]=="--grubpc"){check_grubpc();exit;}
if($argv[1]=="--systemd-reboot"){removeSystemd_reboot();exit;}

if(system_is_overloaded(basename(__FILE__))){squid_admin_mysql(1, "{OVERLOADED_SYSTEM}, aborting the task...", ps_report(), __FILE__, __LINE__);exit();}

if(!is_file($_GET["APT-GET"])){
	if(is_file("/usr/bin/yum")){CheckYum();exit();}
	exit();
}




$unix=new unix();
$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".md5($argv[1]).".pid";
$pid=$unix->get_pid_from_file($pidfile);
if($unix->process_exists($pid,basename(__FILE__))){
	$timefile=$unix->file_time_min($pidfile);
	//$text,$function,$file,$line,$category,$taskid=0
	system_admin_events(basename(__FILE__).": Already executed pid $pid since $timefile minutes.. aborting the process","MAIN",__FILE__,__LINE__,"update");
	exit();
}
@unlink($pidfile);
@file_put_contents($pidfile, getmypid());

if($argv[1]=='--update'){GetUpdates();exit();}
if($argv[1]=='--upgrade'){UPGRADE();exit();}
if($argv[1]=='--clean-upgrade'){clean_upgrade();exit();}


function clean_upgrade(){
	@unlink("/etc/artica-postfix/apt.upgrade.cache");
	@unlink(PROGRESS_DIR."/debian.update.html");
	$q=new mysql();
	$q->QUERY_SQL("TRUNCATE TABLE syspackages_updt","artica_backup");
	echo "Packages \"to upgrade\" list as been flushed....\n";
	return true;
}


function build_progress_install($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/php7install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
	
}

function install_package($packagename=null){
	if(trim($packagename)==null){
		build_progress_install("{failed}... no package to install",110);
	}
	
	build_progress_install("$packagename... Sources List...",10);

	$unix=new unix();
	$sock=new sockets();
	$php=$unix->LOCATE_PHP5_BIN();
	
	
	
	$unix->DEBIAN_INSTALL_PACKAGE($packagename);
	
	
	build_progress_install("{building_collection}",15);
	system("$php /usr/share/artica-postfix/exec.status.php --process1");
	
	
	if(preg_match("#^php[0-9]+#", $packagename)){
		build_progress_install("PHP.INI",89);
		system("/usr/sbin/artica-phpfpm-service -phpini -debug");
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("need_to_reboot_webconsole", 1);
		build_progress_install("{building_collection}",90);
        shell_exec("/usr/sbin/artica-phpfpm-service -deb-collection");
		build_progress_install("{need_to_reboot_webconsole}",100);
		return;
	}
	
	if(preg_match("#^(clamav-daemon|clamav-freshclam)#", $packagename)){
		$EnableFreshClam=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableFreshClam"));
		if($EnableFreshClam==0){remove_service("/etc/init.d/clamav-freshclam");}
		if($sock->EnableClamavDaemon()==0){remove_service("/etc/init.d/clamav-daemon");}
	}
	
	
	
	build_progress_install("{building_collection}",90);
    shell_exec("/usr/sbin/artica-phpfpm-service -deb-collection");
	build_progress_install("{success}",100);
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function removeSystemd_progress($text,$pourc){
    $filename=PROGRESS_DIR."/systemd.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    echo "[$pourc]: $text\n";
    @file_put_contents($filename, serialize($array));
    @chmod($filename,0777);

}



function removeSystemd_reboot(){
    $unix           = new unix();
    $aptget         = $unix->find_program("apt-get");
    $tfile          = "/etc/boot.d/systemd";
    shell_exec("DEBIAN_FRONTEND=noninteractive $aptget remove systemd -y >>/var/log/remove-systemd.log 2>&1");
    @unlink($tfile);
    system("/usr/sbin/artica-phpfpm-service -apt-mark-hold");
    squid_admin_mysql(2,"systemd was successfully removed.");
}

function removeSystemd(){
	//http://without-systemd.org/wiki/index.php/How_to_remove_systemd_from_a_Debian_jessie/sid_installation


    removeSystemd_progress("{installing}",20);

    $unix           = new unix();
    $aptget         = $unix->find_program("apt-get");
    $php            = $unix->LOCATE_PHP5_BIN();
    $php5script     = __FILE__;

    removeSystemd_progress("{installing}",30);

//libnss-systemd
    if(is_file("/etc/init.d/dbus")){
        $ar[]="DEBIAN_FRONTEND=noninteractive";
        $ar[]="$aptget -o Dpkg::Options::=\"--force-confnew\"";
        $ar[]="-fuy remove dbus >>/var/log/remove-systemd.log 2>&1";
        $cmd=@implode(" ",$ar);
        $unix->ToSyslog($cmd,"REMOVE-SYSTEMD");
        shell_exec($cmd);

    }
    $ar=array();
    $ar[]="DEBIAN_FRONTEND=noninteractive";
    $ar[]="$aptget -o Dpkg::Options::=\"--force-confnew\"";
    $ar[]="-fuy install sysvinit-core sysvinit-utils >>/var/log/remove-systemd.log 2>&1";
    $cmd=@implode(" ",$ar);
    $unix->ToSyslog($cmd,"REMOVE-SYSTEMD");
    shell_exec($cmd);

    $f=explode("\n",@file_get_contents("/var/log/remove-systemd.log"));
    squid_admin_mysql(2,"Turn to init system services result",@implode("\n",$f),__FILE__,__LINE__);

    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        echo "$line\n";
    }

    if(!check_systemd()){
        removeSystemd_progress("{installing} {failed}",110);
    }

    $tfile="/etc/boot.d/systemd";
    if(!is_dir("/etc/boot.d")){@mkdir("/etc/boot.d",0755,true);}
    $h=array();
    $h[]="#!/bin/sh";
    $h[]="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin:/usr/share/artica-postfix/bin";
    $h[]="echo \"Finish the systemd uninstall task...\" >>/var/log/remove-systemd.log";
    $h[]="$php $php5script --systemd-reboot >>/var/log/remove-systemd.log 2>&1";
    $h[]="if [ ! -f $tfile ]; then";
    $h[]="\treboot";
    $h[]="fi";
    $h[]="";
    @file_put_contents($tfile,@implode("\n",$h));
    @chmod($tfile,0755);
    shell_exec("/bin/chown root:root $tfile");
    removeSystemd_progress("{installing} $tfile {success}",100);

	//apt-get remove –purge –auto-remove systemd

	
}
function UPGRADE_FROM_INTERFACE(){
    $unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$timefile=$unix->file_time_min($pidfile);
		echo "Already executed pid $pid since $timefile minutes.. aborting the process";
		exit();
	}
    check_grubpc();
    system("/usr/sbin/artica-phpfpm-service -deb-upgrade-packages");

}



function COUNT_REPOS(){
	$q=new lib_sqlite("/home/artica/SQLITE/dpkg.db");
	return $q->COUNT_ROWS("debian_packages");
}



function INSERT_DEB_PACKAGES(){

	if(!is_file("/usr/bin/dpkg")){exit();}
	@unlink("/home/artica/SQLITE/dpkg.db");
	
	$q=new lib_sqlite("/home/artica/SQLITE/dpkg.db");
	
	$sql="CREATE TABLE IF NOT EXISTS `debian_packages` (
			  `ID` INTEGER PRIMARY KEY AUTOINCREMENT,
			  `package_name` text UNIQUE,
			  `package_version` text,
			  `package_info` text,
			  `package_description` text,
			  `package_status` text )";
	$q->QUERY_SQL($sql);
	if(!$q->ok){echo $q->mysql_error."\n";return;}
	
	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();	
	shell_exec("/usr/bin/dpkg -l >$tmpf 2>&1");
	$datas=@file_get_contents($tmpf);
	@unlink($tmpf);
	$tbl=explode("\n",$datas);
	
	$prefix="INSERT INTO debian_packages(package_status,package_name,package_version,package_info,package_description) VALUES ";
	$c=0;$prc=5;
	$MaxCount=count($tbl);
	foreach ($tbl as $num=>$val){
		if($val==null){continue;}
		$c++;


		
		if(!preg_match("#^([a-z]+)\s+(.+?)\s+(.+?)\s+(.+)#",$val,$re)){
				if($GLOBALS["VERBOSE"]){echo "No match $val\n";}
				continue;
		}
		
		$newprc=round(($c/$MaxCount)*100);
		if($newprc>4){
			if($newprc<97){
				if($newprc>$prc){
					echo "{$newprc}%\n";
					build_progress_install("{building_collection} {$newprc}%",95);
					$prc=$newprc;
				}
				}
			}
		
		
			$content=sqlite_escape_string2($re[4]);
			$pname=sqlite_escape_string2($re[2]);
			$package_description=sqlite_escape_string2(PACKAGE_EXTRA_INFO($pname));
            $q->QUERY_SQL("$prefix ('{$re[1]}','$pname','{$re[3]}','$content','$package_description')");
	}

	
}

function PACKAGE_EXTRA_INFO($pname):string{
	$unix=new unix();
	$tmpf=$unix->FILE_TEMP();		
	shell_exec("/usr/bin/dpkg-query -p $pname >$tmpf 2>&1");
	$datas=@file_get_contents($tmpf);
	@unlink($tmpf);
    return $datas;
}

function UPGRADE($noupdate=false){
if(system_is_overloaded(basename(__FILE__))){
    squid_admin_mysql(1, "{$GLOBALS["SYSTEM_INTERNAL_LOAD"]}: {OVERLOADED_SYSTEM}, aborting task",
        ps_report(),__FILE__,__LINE__);
	exit();
}	
	
	$called=null;if(function_exists("debug_backtrace")){$trace=debug_backtrace();if(isset($trace[1])){$called=" called by ". basename($trace[1]["file"])." {$trace[1]["function"]}() line {$trace[1]["line"]}";}}
	
	squid_admin_mysql(2, "Running UPGRADE $called",null,__FILE__,__LINE__);
	@unlink(PROGRESS_DIR."/debian.update.html");
	$unix=new unix();
	$sock=new sockets();
	$EnableRebootAfterUpgrade=$sock->GET_INFO("EnableRebootAfterUpgrade");
	if(!is_numeric($EnableRebootAfterUpgrade)){$EnableRebootAfterUpgrade=0;}
	$tmpf=$unix->FILE_TEMP();		
	$txt="PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/X11R6/bin\n";
	$txt=$txt."echo \$PATH >$tmpf 2>&1\n";
	$txt=$txt."rm -f $tmpf\n";

$tmpf=$unix->FILE_TEMP();	
@file_put_contents($tmpf,$txt);
@chmod($tmpf,'0777');
shell_exec($tmpf);

$tmpf=$unix->FILE_TEMP();
$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\"   update >$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);


$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\"   --yes install -f >$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);


$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\"   --yes upgrade >>$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);

$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\"   --yes dist-upgrade >>$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);

$cmd="DEBIAN_FRONTEND=noninteractive {$_GET["APT-GET"]} -o Dpkg::Options::=\"--force-confnew\"   --yes autoremove >>$tmpf 2>&1";
system_admin_events($cmd,__FUNCTION__,__FILE__,__LINE__,"system-update");
shell_exec($cmd);

$datas=@file_get_contents($tmpf);
$datassql=addslashes($datas);


$q=new mysql();
$sql="INSERT IGNORE INTO debian_packages_logs(zDate,package_name,events,install_type) VALUES(NOW(),'artica-upgrade','$datassql','upgrade');";
$q->QUERY_SQL($sql,"artica_backup");  	
@unlink('/etc/artica-postfix/apt.upgrade.cache');
send_email_events("Debian/Ubuntu System upgrade operation",$datas,"update");

if($EnableRebootAfterUpgrade==1){
	squid_admin_mysql(0,"Rebooting after upgrade operation",
	"reboot command has been performed",__FILE__,__LINE__);
	shell_exec("reboot");
}

}






function exim_remove(){
	$unix=new unix();
	$f[]="/usr/lib/exim4/exim4";
	$f[]="/usr/sbin/exim";
	$f[]="/usr/sbin/exim4";
	$f[]="/etc/init.d/exim4";
	$f[]="/usr/sbin/exim";
	
	$removeexim=false;
	foreach ( $f as $val ){
		if(is_file($val)){
			$removeexim=true;
		}
		
	}

    system("/usr/sbin/artica-phpfpm-service -apt-mark-hold");
	$eximp[]="exim4";
	$eximp[]="exim4-base";
	$eximp[]="exim4-config";
	$eximp[]="exim4-daemon-light";
	
	if($removeexim){
		$aptget=$unix->find_program("apt-get");
		$echo=$unix->find_program("echo");
		$dpkg=$unix->find_program("dpkg");
        foreach ($eximp as $val){
			shell_exec("$echo $val hold|$dpkg --set-selections");
		}
        $cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\"   -y remove exim* 2>&1";
		shell_exec($cmd);
	}
	sendmail_remove();
}

function sendmail_remove(){
	$unix=new unix();
	$aptget=$unix->find_program("apt-get");
	
	if(is_file("/etc/init.d/xmail")){
		if(is_file($aptget)){
			$cmd="DEBIAN_FRONTEND=noninteractive $aptget -o Dpkg::Options::=\"--force-confnew\"   -y remove xmail* 2>&1";
			shell_exec($cmd);
		}
	}
	
	
	$f[]="/etc/init.d/sendmail";
	$f[]="/etc/init.d/xmail";
	
	
	
	$removeexim=false;
	foreach ( $f as $val ){
		if(is_file($val)){
			shell_exec("$val stop");
			shell_exec("/usr/sbin/update-rc.d -f ".basename($val)." remove");
			@unlink($val);
		}
	
	}
	
}
function check_files_dirs(){
    $dirs[]="/var/lib/dpkg/updates";
    $dirs[]="/var/lib/dpkg/info";
    $dirs[]="/var/lib/dpkg/config";
    $dirs[]=PROGRESS_DIR."";
    $dirs[]="/etc/artica-postfix";
    foreach ($dirs as $directory){
        if(is_dir($directory)){continue;}
        @mkdir($directory,0755,true);
    }

    if(!is_file("/var/lib/dpkg/status")){@touch("/var/lib/dpkg/status");}
    if(!is_file("/var/lib/dpkg/available")){@touch("/var/lib/dpkg/available");}

    $del[]="/var/lib/dpkg/updates/DEBIAN_INSTALL_PACKAGE_PROXY";
    foreach ($del as $file_to_delete){
        if(!is_file($file_to_delete)){continue;}
        @unlink($file_to_delete);
    }
}





function CheckYum(){
	@unlink(PROGRESS_DIR."/debian.update.html");
	exec("/usr/bin/yum check-updates 2>&1",$results);
	foreach ($results as $num=>$val){
	if(preg_match("#(.+?)\s+(.+?)\s+updates#", $val,$re)){$p[$re[1]]=true;$packages[]=$re[1];}}
		
	$count=count($p);
	if($count>0){
		@file_put_contents("/etc/artica-postfix/apt.upgrade.cache",implode("\n",$packages));
		$text="You can perform upgrade of linux packages for\n".@file_get_contents("/etc/artica-postfix/apt.upgrade.cache");
		send_email_events("new upgrade $count packages(s) ready",$text,"update");
		


	}
	
}



function php_fpm($aspid=false){
	

	
}

function build_progress($text,$pourc){
	$filename=PROGRESS_DIR."/aptget.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents($filename, serialize($array));
	@chmod($filename,0777);

}
function debian_dist_upgrade():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    system("/usr/sbin/artica-phpfpm-service -deb-dist-upgrade debug");
    system("/usr/sbin/artica-phpfpm-service -phpini -debug");
    system("$php /usr/share/artica-postfix/exec.pip.php --collection");
    return true;
}



function check_systemd(){
    $unix=new unix();
    $dpkg=$unix->find_program("dpkg-query");
    exec("$dpkg -W -f='\${db:Status-Abbrev}||\${binary:Package}\n' sysvinit-core 2>&1",$results);

    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(strpos($line,"||")==0){continue;}
        $tb=explode("||",$line);
        $status=strtolower(trim($tb[0]));
        $name=strtolower(trim($tb[1]));
        echo "$name ----> $status\n";
        if($name<>"sysvinit-core"){continue;}
        if($status=="ii"){
            echo "check_systemd --> SYSTEMD_REMOVED YES\n";
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEMD_REMOVED","YES");
            return true;
        }else{
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEMD_REMOVED","NO");
            return false;
        }
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SYSTEMD_REMOVED","NO");
    echo "check_systemd --> SYSTEMD_REMOVED NO\n";
    return false;
}
function check_grubpc_prog($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text,"clone.progress");
}

function check_grubpc():bool{
    $unix   = new unix();
    check_grubpc_prog(10,"{checking}");
    check_files_dirs();
    check_grubpc_prog(20,"{checking}");
    check_systemd();
    check_grubpc_prog(30,"{checking}");
    system("/usr/sbin/artica-phpfpm-service -apt-mark-hold");
    check_grubpc_prog(60,"{checking}");
    check_grubpc_device_error();
    check_grubpc_prog(100,"{checking}");
    return true;
}
function check_grubpc_device_error(){
    $VMWARE_HOST=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("VMWARE_HOST");
    if($VMWARE_HOST==1){
        if(is_file("/etc/artica-postfix/GRUBPC_DEVICE_ERROR")){
            @unlink("/etc/artica-postfix/GRUBPC_DEVICE_ERROR");
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GRUBPC_DEVICE_ERROR","OK");
        return true;
    }
    $DISK=array();
    $getdisks=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("GetDisks");
    if(strlen($getdisks)<5){
        exec("/usr/sbin/artica-phpfpm-service -getdisks 2>&1",$results);
        foreach ($results as $line){
            $line=trim($line);
            if($line==null){continue;}
            if($GLOBALS["VERBOSE"]){echo "[OK] ** Found dev $line\n";}
            $DISK[$line]=true;
        }
    }else{
        $results=explode("|",$getdisks);
        foreach ($results as $line){
            $DISK[$line]=true;
        }
    }





    $unix=new unix();
    $results=array();
    exec("/usr/bin/debconf-show grub-pc 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(!preg_match("#install_devices:\s+(.+)#",$line,$re)) {
            if ($GLOBALS["VERBOSE"]) {
                echo "No matches [$line] #install_devices:\s+(.+)#\n";
            }
            continue;
        }
        $GRUBPC_DEVICE=trim($re[1]);
        echo "[OK] ** Found <$GRUBPC_DEVICE>\n";
        if(isset($DISK[$GRUBPC_DEVICE])){
            $unix->ToSyslog("$GRUBPC_DEVICE is a DISK [OK]",false,"verif-cloning");
            echo "[OK] ** PROCEDURE IS GOOD $GRUBPC_DEVICE is a DISK\n";
            break;
        }
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GRUBPC_DEVICE",trim($re[1]));


        if(!is_link($GRUBPC_DEVICE)) {
            $unix->ToSyslog("** ALERT ** $GRUBPC_DEVICE is not a link! set GRUBPC_DEVICE_ERROR to disk",false,"verif-cloning");
            echo "[ALERT] ** $GRUBPC_DEVICE is not a link!\n";
            @file_put_contents("/etc/artica-postfix/GRUBPC_DEVICE_ERROR", "$GRUBPC_DEVICE");
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GRUBPC_DEVICE_ERROR", "DISK");
            check_grubpc_prog(100,"{checking}");
            return false;
        }

    }

    if(is_file("/etc/artica-postfix/GRUBPC_DEVICE_ERROR")){
        @unlink("/etc/artica-postfix/GRUBPC_DEVICE_ERROR");
    }
    $unix->ToSyslog("SET GRUBPC_DEVICE_ERROR to OK",false,"verif-cloning");
    echo "SET GRUBPC_DEVICE_ERROR to OK\n";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GRUBPC_DEVICE_ERROR","OK");
    return true;
}


function debian_upgrade():bool{
    system("/usr/sbin/artica-phpfpm-service -deb-upgrade-packages -debug");
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
	system("/usr/sbin/artica-phpfpm-service -phpini -debug");
	system("$php /usr/share/artica-postfix/exec.pip.php --collection");
    system("/usr/sbin/artica-phpfpm-service -need-restart");
    return true;

}


function print_upgrade($force=false){
    check_grubpc();
    system("/usr/sbin/artica-phpfpm-service -deb-upgrade -debug");
}

?>