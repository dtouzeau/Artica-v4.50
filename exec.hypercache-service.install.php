<?php
sftp://root@37.187.156.120/home/www.artica.fr/download/hypercache-builder.tar.gz

$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="InfluxDB Daemon";
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MIGRATION"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;
$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--migration#",implode(" ",$argv),$re)){$GLOBALS["MIGRATION"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');



if($argv[1]=="--tailer"){install_hypercache_tail();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--remove"){uninstall_full();exit();}



function build_progress($pourc,$text){
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	echo "[$pourc]: $text\n";
	@file_put_contents(PROGRESS_DIR."/hypercache.progress", serialize($array));
	@chmod(PROGRESS_DIR."/hypercache.progress",0755);
}

function uninstall(){
	$unix=new unix();
	remove_service("/etc/init.d/hypercache-service");
	remove_service("/etc/init.d/hypercache-tail");
	@unlink("/etc/monit/conf.d/APP_HYPERCACHETAIL.monitrc");
	
	build_progress(80, "{reconfigure_proxy_service}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHyperCacheProxy",0);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --parents");
	build_progress(95, "{restart_status_service}");
    $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(100, "{success}");
}

function uninstall_full(){
	$unix=new unix();
	remove_service("/etc/init.d/hypercache-service");
	@unlink("/etc/monit/conf.d/APP_HYPERCACHETAIL.monitrc");
	remove_service("/etc/init.d/hypercache-tail");
	$rm=$unix->find_program("rm");
	$q=new mysql_squid_builder();
	$results=$q->QUERY_SQL("SELECT directory FROM hypercache_caches");
	while($ligne=@mysqli_fetch_array($results,MYSQLI_ASSOC)){
		$directory=$ligne["directory"];
		build_progress(30, "{remove} $directory");
		if(!is_dir($directory)){continue;}
		system("$rm -rfv $directory");
	}
	build_progress(80, "{reconfigure_proxy_service}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHyperCacheProxy",0);
	$php=$unix->LOCATE_PHP5_BIN();
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --parents");
	build_progress(95, "{restart_status_service}");
	system("/etc/init.d/artica-status restart");
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	build_progress(100, "{success}");
	
}

function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

	}

	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install(){
	
	
	$md5="8909fb1d40e235799d25eb00722536c5";
	$unix=new unix();
	$tar=$unix->find_program("tar");
	$rm=$unix->find_program("rm");
	
	if(!is_file("/usr/local/HyperCache/sbin/hypercache-service")){
	
		build_progress(15, "{downloading}");
		$curl=new ccurl("http://articatech.net/download/hypercache-builder.tar.gz");
		$curl->Timeout=2400;
		$curl->WriteProgress=true;
		$curl->ProgressFunction="download_hypercache_progress";
		$filetemp=$unix->FILE_TEMP().".tar.gz";
		if(!$curl->GetFile($filetemp)){
			build_progress(110, "{downloading} {failed2}");
			@unlink($filetemp);
			return;
		}
		
		$md5New=md5_file($filetemp);
		if($md5New<>$md5){
			build_progress(110, "{downloading} {failed2} {corrupted}");
			@unlink($filetemp);
			return;
		}
		
		build_progress(45, "{extracting}");
		system("$tar -xhf $filetemp -C /");
		squid_admin_mysql(0, "Downloading HyperCache Proxy service success [action=notify]", __FILE__,__LINE__);
	}
		
		if(!is_file("/usr/local/HyperCache/sbin/hypercache-service")){	
			build_progress(110, "{failed} {not_installed}");
			return;
		}
		
		
		$HyperCacheStoragePath=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("HyperCacheStoragePath");
		if($HyperCacheStoragePath==null){$HyperCacheStoragePath="/home/artica/proxy-cache";}
		if(is_dir($HyperCacheStoragePath)){system("$rm -rfv $HyperCacheStoragePath");}
		$DirFiles=$unix->DirFiles("/usr/share/squid3","HyperCacheQueue-.*?\.db$");
		while (list ($database, $none) = each ($DirFiles) ){@unlink($database);}
		$q=new mysql_squid_builder();
		
		
		if($q->TABLE_EXISTS("artica_caches_mirror")){
			$q->QUERY_SQL("TRUNCATE TABLE artica_caches_mirror");
		}
		
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HyperCacheServiceInstalled", 1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableHyperCacheProxy",1);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("HTTrackInSquid",0);
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("SquidEnforceRules",0);
		remove_service("/etc/init.d/hypercache-web");
		
		
		build_progress(50, "{creating_service}");
		
		install_hypercache_server();
		install_hypercache_tail();
		build_progress(55, "{starting_service}");
		system("/etc/init.d/hypercache-service restart");
		system("/etc/init.d/hypercache-tail restart");
		
		build_progress(60, "{reconfigure_proxy_service}");
		$php=$unix->LOCATE_PHP5_BIN();
		squid_admin_mysql(0, "Installing HyperCache Proxy service success [action=reload]", __FILE__,__LINE__);
		system("$php /usr/share/artica-postfix/exec.squid.global.access.php --parents");
		
		build_progress(95, "{restart_status_service}");
        $unix->framework_exec("/usr/sbin/artica-phpfpm-service -restart-artica-status");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
		build_progress(100, "{success}");
		
}
function install_hypercache_server(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          hypercache-service";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: HyperCache Parent Proxy Daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable HyperCache PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-server.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-server.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-server.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-server.php --reload \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/hypercache-service";
	echo "HYPERCACHE: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

	return true;

}
function install_hypercache_tail(){

	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          hypercache-tail";
	$f[]="# Required-Start:    \$local_fs \$syslog \$network \$time";
	$f[]="# Required-Stop:     \$local_fs";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: HyperCache Tailer Daemon";
	$f[]="# chkconfig: 2345 11 89";
	$f[]="# description: Extensible, configurable HyperCache PROXY daemon";
	$f[]="### END INIT INFO";
	$f[]="case \"\$1\" in";
	$f[]=" start)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-tailer.php --start \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  stop)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-tailer.php --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-tailer.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reload)";
	$f[]="   $php /usr/share/artica-postfix/exec.hypercache-tailer.php --restart \$2 \$3";
	$f[]="	 exit 0";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";

	$INITD_PATH="/etc/init.d/hypercache-tail";
	echo "HYPERCACHE: [INFO] Writing $INITD_PATH with new config\n";
	@unlink($INITD_PATH);
	@file_put_contents($INITD_PATH, @implode("\n", $f));
	@chmod($INITD_PATH,0755);

	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
	}

	if(is_file('/sbin/chkconfig')){
		shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
		shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
	}

	return true;

}

function download_hypercache_progress( $download_size, $downloaded_size, $upload_size, $uploaded_size ){
	if(!isset($GLOBALS["previousProgress"])){$GLOBALS["previousProgress"]= 0;}


	if ( $download_size == 0 ){
		$progress = 0;
	}else{
		$progress = round( $downloaded_size * 100 / $download_size );
	}

	if ( $progress > $GLOBALS["previousProgress"]){
		if($progress==20){build_progress(21, "{downloading}");}
		if($progress==50){build_progress(25, "{downloading}");}
		if($progress==70){build_progress(30, "{downloading}");}
		if($progress==80){build_progress(35, "{downloading}");}
		if($progress==90){build_progress(40, "{downloading}");}
		if($progress==99){build_progress(45, "{downloading}");}
		echo "Downloading: ". $progress."%, please wait...\n";
		$GLOBALS["previousProgress"]=$progress;
	}
}