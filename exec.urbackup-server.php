<?php
$GLOBALS["YESCGROUP"]=true;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="ARP Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');



$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){stop();exit();}
if($argv[1]=="--start"){start();exit();}
if($argv[1]=="--restart"){restart();exit();}
if($argv[1]=="--install"){install();exit();}
if($argv[1]=="--uninstall"){uninstall();exit();}
if($argv[1]=="--nginx"){nginx_configuration();exit();}
if($argv[1]=="--scan"){scan();exit();}



function scan():bool{
    return true;
}
function restart():bool {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
		return false;
	}
	@file_put_contents($pidfile, getmypid());
    build_progress("{stopping}",10);
	stop(true);
	sleep(1);
    build_progress("{starting}",50);
	if(start(true)){
        return build_progress("{success}",100);
    }
    return build_progress("{failed}",110);
	
}

function build_progress($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"urbackup.progress");
}

function install():bool{

    if(!is_file("/usr/bin/urbackupsrv")){
        return build_progress("{failed} /usr/bin/urbackupsrv no such binary",110);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_URBACKUP_INSTALLED", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableURBackup",1);
    if(!is_dir("/home/artica/urbackup/sqlite")){
        @mkdir("/home/artica/urbackup/sqlite");
    }
    
    build_progress("{installing}",20);
    $unix=new unix();
    if(!$unix->CreateUnixUser("urbackup","urbackup")){
        return build_progress("{failed} {user} urbackup",110);
    }
    
    $unix->Popuplate_cron_make("urbackup","*/10 * * * *",basename(__FILE__)." --scan");
    create_service();
    build_syslog();
    build_monit();
    build_progress("{starting}",50);
    start();
    return build_progress("{success}",100);
}
function build_monit():bool{
    $srcmd5=null;
    $monit_file = "/etc/monit/conf.d/APP_URBACKUP.monitrc";
    $PIDFILE_PATH=PIDFILE_PATH();
    $ServerIP="127.0.0.1";
    $ServerPort=55414;
    $INITD_PATH=INITD_PATH();

    if (is_file($monit_file)) {
        $srcmd5=md5_file($monit_file);
    }

    $f[] = "check process APP_URBACKUP with pidfile $PIDFILE_PATH";
    $f[] = "\tstart program = \"$INITD_PATH start --monit\"";
    $f[] = "\tstop program = \"$INITD_PATH stop --monit\"";
    $f[] = "\trestart program = \"$INITD_PATH restart --monit\"";
    $f[] = "\tif failed host $ServerIP port $ServerPort type tcp then restart";
    $f[] = "";

    @file_put_contents($monit_file, @implode("\n", $f));
    $srcdest = md5_file($monit_file);
    if ($srcdest == $srcmd5) {return true;}
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    return true;
}

function uninstall():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableURBackup",0);
    build_progress("{uninstalling}",20);
    $INITD_PATH="/etc/init.d/urbackupsrv";
    $target_syslog_file="/etc/rsyslog.d/01_urbackup.conf";
    $monit_file = "/etc/monit/conf.d/APP_URBACKUP.monitrc";
    $unix=new unix();
    $unix->Popuplate_cron_delete("urbackup");
    $unix->DeleteUnixUser("urbackup","urbackup");
    build_progress("{uninstalling}",50);
    $unix->remove_monit_file($monit_file);

    $unix->remove_service($INITD_PATH);
    if(is_file($target_syslog_file)){
        @unlink($target_syslog_file);
        $unix->RESTART_SYSLOG();
    }
    build_progress("{uninstalling}",60);
    $fs[]="/var/log/urbackupserver.log";
    $fs[]="/var/log/urbackup-server.log";
    foreach ($fs as $fname){
        if(is_file($fname)){
            @unlink($fname);
        }
    }
    $nginx_file="/usr/local/ArticaWebConsole/webplugins/urbackup.conf";
    if(is_file($nginx_file)){
        @unlink($nginx_file);
        $unix->RELOAD_WEBCONSOLE();
    }

    $nginx_file2 = "/usr/local/ArticaWebConsole/webplugins/webservices.conf";
    if(is_file($nginx_file2)){
        @unlink($nginx_file2);
        $unix->RELOAD_WEBCONSOLE();
    }


    return build_progress("{uninstalling} {success}",100);
}

function build_syslog():bool{
    $md51=null;
    $target_file="/etc/rsyslog.d/01_urbackup.conf";
    $logfile="/var/log/urbackup-server.log";
    if(is_file($target_file)){$md51=md5_file($target_file);}
    $f[]="# UrBackup parse file";
    $f[]="input(type=\"imfile\" file=\"$logfile\" Tag=\"urbackupserver\" reopenOnTruncate=\"on\")";
    $f[]="";
    $f[]="if  (\$programname =='urbackupserver') then {";
    $f[]= BuildRemoteSyslogs("urbackupserver","urbackupserver");
    $f[] ="\t-/var/log/urbackupserver.log";
    $f[] ="\t&stop";
    $f[]="\t}";
    $f[]="";

    @file_put_contents($target_file,implode("\n",$f));
    $md52=md5_file($target_file);
    if($md51==$md52){
        return true;
    }
    $unix=new unix();
    return $unix->RESTART_SYSLOG();

}

function INITD_PATH():string{
    return "/etc/init.d/urbackupsrv";
}


function create_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH=INITD_PATH();
    $php5script=basename(__FILE__);
    $daemonbinLog="URBACKUP Daemon";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         artica-arpd";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
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
function _out($text){
    echo "Service.......: ".date("H:i:s")." [INIT]: UrBackup Server $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return true;}
    openlog("urbackupserver", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}


function start($aspid=false):bool{
	$unix=new unix();
	$Masterbin=$unix->find_program("urbackupsrv");

	if(!is_file($Masterbin)){
	    _out("urbackupsrv not installed");
		return false;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Already Artica task running PID $pid since {$time}mn");
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NUM();

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		return _out("Service already started $pid since {$timepid}Mn...");

	}
	$EnableURBackup=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableURBackup"));

	if($EnableURBackup==0){
		_out("Service disabled (see EnableURBackup)");
		return uninstall();
	}

    build_syslog();
    nginx_configuration();

    $paths[]="/var/urbackup";
    $paths[]="/usr/share/urbackup";
    $paths[]="/home/artica/urbackup/sqlite";

    foreach ($paths as $directory){

        _out("Starting: Checking permissions on $directory");
        if(!is_dir($directory)){
            @mkdir($directory,0744,true);
        }
        @chown($directory,"urbackup");
        @chgrp($directory,"urbackup");
        @chmod($directory,0744);
    }

    chmod("/usr/share/urbackup",0755);



	$f[]="$Masterbin";
    $f[]="run";
    $f[]="--daemon";
    $f[]="--pidfile /var/run/urbackupsrv.pid";
	$f[]="--logfile /var/log/urbackup-server.log";
    $f[]="--internet-only";
    $f[]="--http-port 55414";
    $f[]="--fastcgi-port 55413";
    $f[]="--loglevel warn";
    $f[]="--user urbackup";
    $f[]="--sqlite-tmpdir /home/artica/urbackup/sqlite";

	$cmd=@implode(" ", $f);
    _out("Starting: service...");
	if(!$unix->go_exec($cmd)){
        _out("Starting failed (go-exec)");
        return false;
    }

	for($i=1;$i<5;$i++){
        _out("Starting Waiting $i/5");
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
        return _out("Success PID $pid");
		
	}
    _out("Starting failed with $cmd");
    return false;


}
function nginx_configuration():bool{
    $md51                       = null;
    $unix                       = new unix();
    $nginx_file                 = "/usr/local/ArticaWebConsole/webplugins/urbackup.conf";
    $nginx_file2                 = "/usr/local/ArticaWebConsole/webservices/urbackup.conf";
    $UrBackupPort               = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrBackupPort"));
    $UrBackupPortInterface      = trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrBackupPortInterface"));
    $UrBackupPortSSL            = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("UrBackupPortSSL"));

    $MDS[]=$nginx_file;
    $MDS[]=$nginx_file2;

    if ($UrBackupPort == 0) {
        $UrBackupPort = 9290;
    }
    $MD5_FIRST=array();
    foreach ($MDS as $fname){
        if (is_file($fname)){
            $MD5_FIRST[$fname]=md5_file($fname);
        }
    }


    $f[] = "location ^~ /urbackup/x {";
    $f[] = nginx_fast_cgi();
    $f[] = "\tfastcgi_pass 127.0.0.1:55413;";
    $f[] = "}";
    $f[] = "location ^~ /urbackup/ {";
    $f[] = "\talias /usr/share/urbackup/www/;";
    $f[] = "\tindex index.htm;";
    $f[] = "}";
    @file_put_contents($nginx_file,@implode("\n",$f));
    $f=array();

    $f[] = "server {";

    if ($UrBackupPortSSL == 0) {
        $f[] = "listen\t$UrBackupPort;";
    }
    $f[] = "server_name  _;";
    //listen 443 ssl http2;
    //server_name urbackup.hwdomain.io;
    //ssl_certificate           /etc/letsencrypt/live/urbackup.hwdomain.io/fullchain.pem;
    //ssl_certificate_key       /etc/letsencrypt/live/urbackup.hwdomain.io/privkey.pem;
    //ssl_prefer_server_ciphers on;
    //ssl_protocols TLSv1.2 TLSv1.3;
    //ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384;

    $f[] = "\tlocation   / {";
    $f[] = "\t\tproxy_set_header        Host \$host;";
    $f[] = "\t\tproxy_set_header        X-Real-IP \$remote_addr;";
    $f[] = "\t\tproxy_set_header        X-Forwarded-For \$proxy_add_x_forwarded_for;";
    $f[] = "\t\tproxy_set_header        X-Forwarded-Proto \$scheme;";
    $f[] = "\t\tproxy_pass          http://127.0.0.1:55415;";
    $f[] = "\t\tproxy_read_timeout  90;";
    if ($UrBackupPortSSL == 1) {
        $f[] = "\t\tproxy_redirect      http://127.0.0.1:55414 https://urbackup.hwdomain.io;";
        $f[] = "\t}";
    }
    $f[] = "\t}";
    $f[] = "}\n";
    @file_put_contents($nginx_file2,@implode("\n",$f));

    $RELOAD=false;
    foreach ($MD5_FIRST as $filename=>$md51){
        $md52=md5_file($filename);
        if($md52==$md51){
            continue;
        }
        $RELOAD=true;
    }


    if(!$RELOAD){
        _out("$nginx_file no changes");
        return true;
    }
    _out("$nginx_file Reloading web console");
    $unix->RELOAD_WEBCONSOLE();
    return true;

}
function nginx_fast_cgi():string{

    $f[]="\tfastcgi_param  QUERY_STRING       \$query_string;";
    $f[]="\tfastcgi_param  REQUEST_METHOD     \$request_method;";
    $f[]="\tfastcgi_param  CONTENT_TYPE       \$content_type;";
    $f[]="\tfastcgi_param  CONTENT_LENGTH     \$content_length;";
    $f[]="";
    $f[]="\tfastcgi_param  SCRIPT_NAME        \$fastcgi_script_name;";
    $f[]="\tfastcgi_param  REQUEST_URI        \$request_uri;";
    $f[]="\tfastcgi_param  DOCUMENT_URI       \$document_uri;";
    $f[]="\tfastcgi_param  DOCUMENT_ROOT      \$document_root;";
    $f[]="\tfastcgi_param  SERVER_PROTOCOL    \$server_protocol;";
    $f[]="\tfastcgi_param  REQUEST_SCHEME     \$scheme;";
    $f[]="\tfastcgi_param  HTTPS              \$https if_not_empty;";
    $f[]="";
    $f[]="\tfastcgi_param  GATEWAY_INTERFACE  CGI/1.1;";
    $f[]="\tfastcgi_param  SERVER_SOFTWARE    nginx/\$nginx_version;";
    $f[]="";
    $f[]="\tfastcgi_param  REMOTE_ADDR        \$remote_addr;";
    $f[]="\tfastcgi_param  REMOTE_PORT        \$remote_port;";
    $f[]="\tfastcgi_param  SERVER_ADDR        \$server_addr;";
    $f[]="\tfastcgi_param  SERVER_PORT        \$server_port;";
    $f[]="\tfastcgi_param  SERVER_NAME        \$server_name;";
    $f[]="";
    $f[]="# PHP only, required if PHP was built with --enable-force-cgi-redirect";
    $f[]="\tfastcgi_param  REDIRECT_STATUS    200;";
    return @implode("\n",$f);
}

function stop($aspid=false):bool{
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Stopping service Already Artica task running PID $pid since {$time}mn");
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
        return _out("Stopping service already stopped...");
	}
	$pid=PID_NUM();
    _out("Stopping service Shutdown pid $pid...");
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
        _out("Stopping service waiting pid:$pid $i/5...");
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		return _out("Stopping service success...");

	}

	echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";
		sleep(1);
	}

	if($unix->process_exists($pid)){
		_out("Stopping service service failed...");
        return false;

	}

    return _out("Stopping service service Success...");

}
function PIDFILE_PATH():string{
    return "/var/run/urbackupsrv.pid";
}

function PID_NUM():int{
	$unix=new unix();
    $pid=$unix->get_pid_from_file(PIDFILE_PATH());
    if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("urbackupsrv");
	return $unix->PIDOF($Masterbin);
}
?>