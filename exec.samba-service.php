<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["TITLENAME"]="SMB/CIFS daemon";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.privileges.inc');
include_once(dirname(__FILE__).'/ressources/class.samba.kerb.inc');
include_once(dirname(__FILE__).'/ressources/class.autofs.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");



if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}
if(isset($argv[1])){
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;reload();exit();}
	if($argv[1]=="--uninstall"){uninstall_service();exit();}
	if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;install_service();exit();}
	if($argv[1]=="--count"){Autocount();exit();}
	if($argv[1]=="--davfs"){davfs();exit();}
	if($argv[1]=="--default"){autofs_default();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
	if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit;}
    if($argv[1]=="--promote"){$GLOBALS["OUTPUT"]=true;promote();exit;}
	if($argv[1]=="--build"){build();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
	if($argv[1]=="--start-nmbd"){$GLOBALS["OUTPUT"]=true;$GLOBALS["TITLENAME"]="NetBIOS name server";start_nmbd();exit();}
	if($argv[1]=="--stop-nmbd"){$GLOBALS["OUTPUT"]=true;$GLOBALS["TITLENAME"]="NetBIOS name server";stop_nmbd();exit();}
	if($argv[1]=="--restart-nmbd"){$GLOBALS["OUTPUT"]=true;$GLOBALS["TITLENAME"]="NetBIOS name server";restart_nmbd();exit();}
	
	
	
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
	echo "Starting......: {$GLOBALS["TITLENAME"]} ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/samba.install.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}


function install_service(){
    $unix=new unix();
    $php5=$unix->LOCATE_PHP5_BIN();
    build_progress_install("{enable_feature}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSamba", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PromoteSamba", 0);

	if(!is_file("/etc/artica-postfix/settings/Daemons/SambaDisableNetbios")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SambaDisableNetbios", 1);}

    if(is_file($unix->SLAPD_INITD_PATH())){
        build_progress_install("{uninstall} {APP_OPENLDAP}",25);
        system("/usr/sbin/artica-phpfpm-service -uninstall-ldap");
    }


	build_progress_install("{install_service}",30);
	create_samba_service();
	build_progress_install("{install_service}",50);
	build_monit();
	build_progress_install("{restart_service}",60);
	restart();
	build_progress_install("{success}",100);
}

function promote(){
    $unix=new unix();
    build_progress_install("{enable_feature}",20);
    $workgroup=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaWorkgroup"));
    $SambaType=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaRole"));
    $hostname=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("myhostname"));

    echo "Promote this server as : $SambaType inside workgroup $workgroup whith hostname $hostname\n";

    $SambaPassword=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaPassword"));
    $SambaPassword=$unix->shellEscapeChars($SambaPassword);
    $tb=explode(".",$hostname);
    unset($tb[0]);
    $domain=@implode(".",$tb);
    @unlink("/etc/samba/smb.conf");
    $f[]="[libdefaults]";
    $f[]="default_realm = ".strtoupper($domain);
    $f[]="dns_lookup_kdc = true";
	$f[]="dns_lookup_realm = false";
	@file_put_contents("/etc/krb5.conf",@implode("\n",$f));
    $samba_tool=$unix->find_program("samba-tool");

    build_progress_install("{enable_feature}",50);

    exec("$samba_tool domain provision --use-rfc2307 --realm=".strtoupper($domain)." --domain $workgroup --server-role=$SambaType --adminpass=$SambaPassword 2>&1",$results);
    $SID=null;
    foreach ($results as $line){
        echo "Result: $line\n";
        if(preg_match("#DOMAIN SID:\s+([A-Z0-9\-]+)#",$line,$re)){$SID=$re[1];}

    }

    $files[]="/var/lib/samba/private/sam.ldb";
    $files[]="/var/lib/samba/private/share.ldb";
    $files[]="/var/lib/samba/private/secrets.ldb";
    $files[]="/var/lib/samba/private/idmap.ldb";



    foreach ($files as $path){
        if(!is_file($path)){
            echo "$path no such file\n";
            build_progress_install("{failed}",110);
            return;
        }
    }

    if($SID==null){
        build_progress_install("{failed}",110);

    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PromoteSamba",1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SambaSID",$SID);

    build_progress_install("{success}",100);

}

function install_nmbd(){
	create_nmbd_service();
	build_monit_nmbd();
	start_nmbd();
}

function restart_nmbd(){
	stop_nmbd();
	start_nmbd();
}
function uninstall_nmbd(){
	remove_service("/etc/init.d/samba-nmbd");
	@unlink("/etc/monit/conf.d/APP_SAMBA_NMBD.monitrc");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}
function  uninstall_service(){
	build_progress_install("{APP_SAMBA}: {disable_feature}",20);
	$unix=new unix();
    $rm=$unix->find_program("rm");
	$EnableWsusOffline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWsusOffline"));
	if($EnableWsusOffline==1){
		echo "WSUS Offline is used by Samba service, please disable WSUS Offline First.\n";
		build_progress_install("{failed}",110);
		return;
	}

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSamba", 0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("PromoteSamba", 0);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableSamba", 0);

	build_progress_install("{APP_SAMBA}: {uninstall_service}",30);
	remove_service("/etc/init.d/samba");
	remove_service("/etc/init.d/samba-nmbd");
	@unlink("/etc/monit/conf.d/APP_SAMBA_SMBD.monitrc");
	@unlink("/etc/monit/conf.d/APP_SAMBA_NMBD.monitrc");
	@unlink("/var/log/samba/log.smbd");
	@unlink("/var/log/samba/log.nmbd");

    $files[]="/var/lib/samba/private/sam.ldb";
    $files[]="/var/lib/samba/private/share.ldb";
    $files[]="/var/lib/samba/private/secrets.ldb";
    $files[]="/var/lib/samba/private/idmap.ldb";
    $files[]="/var/lib/samba/private/encrypted_secrets.key";
    $files[]="/var/lib/samba/private/hklm.ldb";
    $files[]="/var/lib/samba/private/idmap.ldb";
    $files[]="/var/lib/samba/private/krb5.conf";
    $files[]="/var/lib/samba/private/netlogon_creds_cli.tdb";
    $files[]="/var/lib/samba/private/passdb.tdb";
    $files[]="/var/lib/samba/private/privilege.ldb";
    $files[]="/var/lib/samba/private/sam.ldb";
    $files[]="/var/lib/samba/private/secrets.ldb";
    $files[]="/var/lib/samba/private/secrets.tdb";
    $files[]="/var/lib/samba/private/share.ldb";
    $directories[]="/var/lib/samba/private/sam.ldb.d";
    $directories[]="/var/lib/samba/private/tls";


    foreach ($files as $path){
        if(is_file($path)){
            echo "Removing $path\n";
            @unlink($path);}
    }
    foreach ($directories as $path){
        if(is_dir($path)){
            echo "Removing directory $path\n";
            shell_exec("$rm -rf $path");
        }
    }




	build_progress_install("{success}",100);
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function build_monit(){
	$f[]="check process APP_SAMBA_SMBD with pidfile /var/run/samba/smbd.pid";
	$f[]="\tstart program = \"/etc/init.d/samba start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/samba stop --monit\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_SAMBA_SMBD.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_SAMBA_SMBD.monitrc")){
		echo "/etc/monit/conf.d/APP_SAMBA_SMBD.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	build_progress_install("/etc/monit/conf.d/APP_SAMBA_SMBD.monitrc done",55);
}
function build_monit_nmbd(){
	$f[]="check process APP_SAMBA_NMBD with pidfile /var/run/samba/nmbd.pid";
	$f[]="\tstart program = \"/etc/init.d/samba start-nmbd --monit\"";
	$f[]="\tstop program = \"/etc/init.d/samba stop-nmbd --monit\"";

	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_SAMBA_NMBD.monitrc", @implode("\n", $f));
	if(!is_file("/etc/monit/conf.d/APP_SAMBA_NMBD.monitrc")){
		echo "/etc/monit/conf.d/APP_SAMBA_NMBD.monitrc failed !!!\n";
	}
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
	build_progress_install("/etc/monit/conf.d/APP_SAMBA_NMBD.monitrc done",55);
}
function restart(){
	
	stop(true);
	build();
	start(true);
	
	
}

function reload(){
	$unix=new unix();
	build_progress_install("{apply_parameters}",20);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {building_settings}...\n";}
	build();
	$OK=false;
	$smbcontrol=$unix->find_program("smbcontrol");
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ping...\n";}
	exec("$smbcontrol smbd ping 2>&1",$results);
	foreach ($results as $line){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $line\n";}
		if(preg_match("#PONG from pid#i", $line)){
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} OK PONG\n";}
			$OK=true;
		}
	}
	build_progress_install("{apply_parameters}",30);
	$SambaDisableNetbios=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaDisableNetbios"));
	
	if($SambaDisableNetbios==1){if(is_file("/etc/init.d/samba-nmbd")){uninstall_nmbd();} }
	if($SambaDisableNetbios==0){if(!is_file("/etc/init.d/samba-nmbd")){install_nmbd();} }
	
	if($OK){
		build_progress_install("{reloading}",50);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} reloading...\n";}
		system("$smbcontrol smbd reload-config");
		if($SambaDisableNetbios==0){system("$smbcontrol nmbd reload-config");}
		build_progress_install("{apply_parameters} {done}",100);
		return;
	}
	
	build_progress_install("{apply_parameters} {failed}",110);
}

function NetBiosName(){
	$unix=new unix();
	$myhostname=$unix->hostname_g();
	if(strpos($myhostname, ".")>0){
		$tt=explode(".",$myhostname);
		$myhostname=$tt[0];
	}
	$myhostname=strtoupper($myhostname);
	if(strlen($myhostname)>19){$myhostname=substr($myhostname,0,19);}
}

function build(){
	$unix=new unix();
	
	$EnableKerbAuth=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableKerbAuth"));
	$SambaNetbiosName=strtoupper(trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaNetbiosName")));
	$SambaServerString=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaServerString"));
	$SambaDisableNetbios=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaDisableNetbios"));
	$workgroup=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaWorkgroup"));
	$SambaEnableEditPosixExtension=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaEnableEditPosixExtension"));
	$SambaInterfaces=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaInterfaces"));
	if(!is_file("/etc/artica-postfix/settings/Daemons/SambaClientNTLMv2")){$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SambaClientNTLMv2", 1);}
	
	
	if($SambaNetbiosName==null){
		$SambaNetbiosName=NetBiosName();
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SambaNetbiosName", $SambaNetbiosName);
	}

	
	
	$f[]="[global]";
	if($EnableKerbAuth==1){
		$smbkerb=new samba_kerb();
		$f[]=$smbkerb->buildPart();
	}else{
		$workgroup=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SambaWorkgroup"));
		if($workgroup==null){$workgroup="WORKGROUP";}
		$f[]="\tnetbios name = $SambaNetbiosName";
		$f[]="\tworkgroup = $workgroup";
		$f[]="\tdns proxy = no";
		$f[]="\tclient ntlmv2 auth = Yes";
		$f[]="\tclient lanman auth = No";
		$f[]="\tserver role = standalone server";
		$f[]="\tpassdb backend = tdbsam";
		$f[]="\tobey pam restrictions = yes";
		$f[]="\tmap to guest = Bad Password";
		$f[]="\tlog level = 10";
		$f[]="\tsecurity = user";
		$f[]="\tguest account = nobody";

		
		
	}
	if($SambaServerString<>null){$f[]="\tserver string = $SambaServerString";}
	if($SambaDisableNetbios==1){$f[]="\tdisable netbios = Yes";}else{$f[]="\tdisable netbios = No";}
	
	if($$SambaEnableEditPosixExtension==1){
		$conf[]="\n\n#Samba and the Editposix/Trusted Ldapsam extension";
		$conf[]="\tldapsam:trusted=yes";
		$conf[]="\tldapsam:editposix=yes";
	}
	
	$f[]="\tlog file = /var/log/samba/log.%m";
	$f[]="\tpanic action = /usr/share/samba/panic-action %d";
	$f[]="\tusershare allow guests = yes";
	if($SambaInterfaces<>null){
		$SambaInterfaces=str_replace(",", " ", $SambaInterfaces);
		$f[]="\tbind interfaces only = yes";
		$f[]="\tinterfaces = $SambaInterfaces";
	}
	
	
	$EnableWsusOffline=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableWsusOffline"));
	if($EnableWsusOffline==1){
		$wsusofflineStorageDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("wsusofflineStorageDir"));
		if($wsusofflineStorageDir==null){$wsusofflineStorageDir="/usr/share/wsusoffline";}
		$f[]="[wsus]";
		$f[]="\tguest ok = yes";
		$f[]="\tguest only = yes";
		$f[]="\tread only = yes";
		$f[]="\tavailable = yes";
		$f[]="\tbrowsable = yes";
		$f[]="\tguest account = nobody";
		$f[]="\tpath = $wsusofflineStorageDir/client";
	}
	
	
	$f[]="";
	@file_put_contents("/etc/samba/smb.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} /etc/samba/smb.conf done\n";}
	
}
function create_nmbd_service(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$dirname=dirname(__FILE__);
	$filename=basename(__FILE__);
	$chmod=$unix->find_program("chmod");
	$INIT_FILE="/etc/init.d/samba-nmbd";
	$conf[]="#! /bin/sh";
	$conf[]="# /etc/init.d/samba-nmbd";
	$conf[]="#";
	$conf[]="# Samba Debian init script";
	$conf[]="#";
	$conf[]="### BEGIN INIT INFO";
	$conf[]="# Provides:          smbd";
	$conf[]="# Required-Start:    \$syslog";
	$conf[]="# Required-Stop:     \$syslog";
	$conf[]="# Should-Start:      \$local_fs";
	$conf[]="# Should-Stop:       \$local_fs";
	$conf[]="# Default-Start:     3 4 5";
	$conf[]="# Default-Stop:      1";
	$conf[]="# Short-Description: Launch samba NetBIOS name server";
	$conf[]="# Description:       Launch samba NetBIOS name server";
	$conf[]="### END INIT INFO";
	$conf[]="";
	$conf[]="case \"\$1\" in";
	$conf[]=" start)";
	$conf[]="    $php5 $dirname/$filename --start-nmbd \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="  stop)";
	$conf[]="    $php5 $dirname/$filename --stop-nmdb \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" restart)";
	$conf[]="	  $php5 $dirname/$filename --restart-nmbd \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" reload)";
	$conf[]="     $php5 $dirname/$filename --reload \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="";
	$conf[]="  *)";
	$conf[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$conf[]="    exit 1";
	$conf[]="    ;;";
	$conf[]="esac";
	$conf[]="exit 0\n";
	@file_put_contents($INIT_FILE,@implode("\n",$conf));
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");

	shell_exec("$chmod +x $INIT_FILE >/dev/null 2>&1");
	if(is_file($debianbin)){
		shell_exec("$debianbin -f ".basename($INIT_FILE)." defaults >/dev/null 2>&1");
		return;
	}
	if(is_file($redhatbin)){
		shell_exec("$redhatbin --add ".basename($INIT_FILE)." >/dev/null 2>&1");
		shell_exec("$redhatbin --level 2345 ".basename($INIT_FILE)." on >/dev/null 2>&1");
	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $INIT_FILE success...\n";}

}

function create_samba_service(){
	
	remove_service("/etc/init.d/samba-ad-dc");
	remove_service("/etc/init.d/smbd");
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$dirname=dirname(__FILE__);
	$filename=basename(__FILE__);
	$chmod=$unix->find_program("chmod");
	$INIT_FILE="/etc/init.d/samba";
	$conf[]="#! /bin/sh";
	$conf[]="# /etc/init.d/samba";
	$conf[]="#";
	$conf[]="# Samba Debian init script";
	$conf[]="#";
	$conf[]="### BEGIN INIT INFO";
	$conf[]="# Provides:          smbd";
	$conf[]="# Required-Start:    \$syslog";
	$conf[]="# Required-Stop:     \$syslog";
	$conf[]="# Should-Start:      \$local_fs";
	$conf[]="# Should-Stop:       \$local_fs";
	$conf[]="# Default-Start:     3 4 5";
	$conf[]="# Default-Stop:      1";
	$conf[]="# Short-Description: Launch samba server";
	$conf[]="# Description:       Launch samba server";
	$conf[]="### END INIT INFO";
	$conf[]="";
	$conf[]="case \"\$1\" in";
	$conf[]=" start)";
	$conf[]="    $php5 $dirname/$filename --start \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="  stop)";
	$conf[]="    $php5 $dirname/$filename --stop \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" restart)";
	$conf[]="	  $php5 $dirname/$filename --restart \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]=" reload)";
	$conf[]="     $php5 $dirname/$filename --reload \$1 \$2";
	$conf[]="    ;;";
	$conf[]="";
	$conf[]="";
	$conf[]="  *)";
	$conf[]="    echo \"Usage: \$0 {start|stop|restart|reload}\"";
	$conf[]="    exit 1";
	$conf[]="    ;;";
	$conf[]="esac";
	$conf[]="exit 0\n";
	@file_put_contents($INIT_FILE,@implode("\n",$conf));
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	
	shell_exec("$chmod +x $INIT_FILE >/dev/null 2>&1");
	if(is_file($debianbin)){
		shell_exec("$debianbin -f ".basename($INIT_FILE)." defaults >/dev/null 2>&1");
		return;
	}
	if(is_file($redhatbin)){
		shell_exec("$redhatbin --add ".basename($INIT_FILE)." >/dev/null 2>&1");
		shell_exec("$redhatbin --level 2345 ".basename($INIT_FILE)." on >/dev/null 2>&1");
	}
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: $INIT_FILE success...\n";}
	
}

function start_nmbd($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("nmbd");
	
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, nmbd not installed\n";}
		return;
	}
	
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NMBD_NUM();	
	
	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
	
	$cmd="$Masterbin --daemon";
	
	shell_exec($cmd);
	
	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NMBD_NUM();
		if($unix->process_exists($pid)){break;}
	}
	
	$pid=PID_NMBD_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
	
	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}	
	
	
}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("smbd");

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, smbd not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}


	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");
    winbindd_privileges();
	$cmd="$Masterbin --daemon";

	
	
	shell_exec($cmd);

	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}
function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");


	$smbcontrol=$unix->find_program("smbcontrol");
	system("$smbcontrol smbd shutdown");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}
function stop_nmbd($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NMBD_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NMBD_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");


	$smbcontrol=$unix->find_program("smbcontrol");
	system("$smbcontrol nmbd shutdown");

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NMBD_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NMBD_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NMBD_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}

function PID_NUM(){

	$unix=new unix();
	$Masterbin=$unix->find_program("smbd");
	$pid=$unix->get_pid_from_file("/var/run/samba/smbd.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($Masterbin);

}

function PID_NMBD_NUM(){

	$unix=new unix();
	$Masterbin=$unix->find_program("nmbd");
	$pid=$unix->get_pid_from_file("/var/run/samba/nmbd.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF($Masterbin);

}
?>