<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["MONIT"]=false;
$GLOBALS["OUTPUT"]=false;
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
	if(preg_match("#--no-reload#",implode(" ",$argv))){$GLOBALS["NORELOAD"]=true;}
	if(preg_match("#--monit#",implode(" ",$argv))){$GLOBALS["MONIT"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}

if($argv[1]=="--uninstall"){uninstall_service();exit();}
if($argv[1]=="--install"){install_service();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--build"){$GLOBALS["OUTPUT"]=true;build_nginx_config();exit();}

function build_progress_install($text,$pourc){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile=PROGRESS_DIR."/xapian.install.prg";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function install_service(){
	
		$unix=new unix();
		$php=$unix->LOCATE_PHP5_BIN();
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableXapianSearch", 1);
		build_progress_install("{installing}....",15);
		build_nginx_config();
		build_progress_install("{installing}....",30);
		system("/usr/sbin/artica-phpfpm-service -reload-webconsole -debug");
		build_progress_install("{starting_service}....",35);
		
		system("/etc/init.d/artica-phpfpm reload");
		build_progress_install("{starting_service}....",40);
		if(!start(true)){
			$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableXapianSearch", 0);
			system("/usr/sbin/artica-phpfpm-service -reload-webconsole -debug");
			build_progress_install("{starting_service} {failed}....",110);
			
			return;
		}
		
		build_progress_install("{starting_service} {success}....",100);
		build_service();
}

function uninstall_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$rm=$unix->find_program("rm");
	build_progress_install("{uninstalling}....",15);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableXapianSearch", 0);
	remove_service("/etc/init.d/xapian-web");
	if(is_file("/etc/artica-postfix/xapian-web.conf")){@unlink("/etc/artica-postfix/xapian-web.conf");}
	build_progress_install("{uninstalling}....",30);
	system("/usr/sbin/artica-phpfpm-service -reload-webconsole -debug");
	if(is_file("/var/log/lighttpd/xapian-error.log")){@unlink("/var/log/lighttpd/xapian-error.log");}
	if(is_file("/etc/monit/conf.d/APP_XAPIAN_WEB.monitrc")){
		@unlink("/etc/monit/conf.d/APP_XAPIAN_WEB.monitrc");
		system("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	
	if(is_dir("/home/omindex-databases")){
		system("$rm -rfv /home/omindex-databases");
	}
	if(is_dir("/home/xapian/mounts")){rmdir("/home/xapian/mounts");}
	
	build_progress_install("{uninstalling}....",40);
	system("/etc/init.d/artica-phpfpm reload");
	
	$q=new mysql();
	build_progress_install("{uninstalling}....",50);
	$q->QUERY_SQL("TRUNCATE TABLE xapian_folders","artica_backup");
	$q->DELETE_TABLE("xapian_folders", "artica_backup");
	
	build_progress_install("{uninstalling}....",100);
	
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function nginx_pid(){
	
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/xapian-webconsole.pid");
	if($unix->process_exists($pid)){return $pid;}
	return $unix->PIDOF_PATTERN("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/xapian-web.conf");
}

function restart(){
	build_progress_install("{stopping_service}....",15);
	stop();
	build_progress_install("{reconfiguring}....",50);
	build_nginx_config();
	if(!start(true)){
		build_progress_install("{starting_service} {failed}....",110);
		return;
	}
	build_progress_install("{starting_service} {success}....",100);
	
}

function start(){

	$unix=new unix();
	$GLOBALS["SERVICE_NAME"]="InstantSearch Web console (Main)";



	$pid=nginx_pid();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Web Service Already running PID $pid\n";}
		if($GLOBALS["MONIT"]){system("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");}
		@file_put_contents("/var/run/xapian-webconsole.pid", $pid);
		return true;

	}
	
	$XapianSearchPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchPort"));
	if($XapianSearchPort==0){$XapianSearchPort=5600;}
	
	$unix->KILL_PROCESSES_BY_PORT($XapianSearchPort);
	
	
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} starting service on port $XapianSearchPort....\n";}
	$cmdline="/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/xapian-web.conf >/dev/null 2>&1 &";
	system("$cmdline");

	for($i=0;$i<8;$i++){
		sleep(1);
		$pid=nginx_pid();
		if($unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting $i/8...\n";}
		
	}

	$pid=nginx_pid();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Web Service running PID $pid\n";}
		if($GLOBALS["MONIT"]){system("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");}
		return true;
	}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Web Service FAILED!!!!\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} $cmdline\n";}


}

function build_service(){
	$unix=new unix();
	$f[]="check process APP_XAPIAN_WEB with pidfile /var/run/xapian-webconsole.pid";
	$f[]="\tstart program = \"/etc/init.d/xapian-web start --monit\"";
	$f[]="\tstop program = \"/etc/init.d/xapian-web stop --monit\"";
	$f[]="\tif 5 restarts within 10 cycles then timeout";
	$f[]="";
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} monitoring Artica Web Console...\n";}
	@file_put_contents("/etc/monit/conf.d/APP_XAPIAN_WEB.monitrc", @implode("\n", $f));
	$f=array();
	
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/xapian-web";
	$php5script=basename(__FILE__);
	$daemonbinLog="Artica XAPIAN Daemon";
	
	
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         xapian-web";
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
	
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload >/dev/null 2>&1");
}


function build_nginx_config(){
	$unix=new unix();
	$XapianSearchPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchPort"));
	if($XapianSearchPort==0){$XapianSearchPort=5600;}
	$XapianSearchInterface=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("XapianSearchInterface"));
	$LISTENIP="0.0.0.0";
	
	if($XapianSearchInterface<>null){
		$NETWORK_ALL_INTERFACES=$unix->NETWORK_ALL_INTERFACES();
		if(isset($NETWORK_ALL_INTERFACES[$XapianSearchInterface]["IPADDR"])){
			if($NETWORK_ALL_INTERFACES[$XapianSearchInterface]["IPADDR"]<>null){
				$LISTENIP=$NETWORK_ALL_INTERFACES[$XapianSearchInterface]["IPADDR"];
			}
		}
	}
	
	echo "Starting......: ".date("H:i:s")." Listen on TCP: $XapianSearchPort\n";
	
	$f[]="user www-data;";
	$f[]="worker_processes auto;";
	$f[]="events {";
	$f[]="	worker_connections  768;";
	$f[]="}";
	$f[]="pid /var/run/xapian-webconsole.pid;";
	$f[]="error_log  /var/log/lighttpd/xapian-error.log debug;";
	$f[]="http {";
	$f[]="	sendfile on;";
	$f[]="	tcp_nopush on;";
	$f[]="	tcp_nodelay on;";
	$f[]="	keepalive_timeout 65;";
	$f[]="	types_hash_max_size 2048;";
	$f[]="	default_type application/octet-stream;";
	$f[]="	client_max_body_size      2000M;";
	$f[]="	client_body_buffer_size   128k;";
	$f[]="";
	$f[]="";
	$f[]="types {";
	$f[]="    text/html                             html htm shtml;";
	$f[]="    text/css                              css;";
	$f[]="    text/xml                              xml;";
	$f[]="    image/gif                             gif;";
	$f[]="    image/jpeg                            jpeg jpg;";
	$f[]="    application/javascript                js;";
	$f[]="    application/atom+xml                  atom;";
	$f[]="    application/rss+xml                   rss;";
	$f[]="";
	$f[]="    text/mathml                           mml;";
	$f[]="    text/plain                            txt;";
	$f[]="    text/vnd.sun.j2me.app-descriptor      jad;";
	$f[]="    text/vnd.wap.wml                      wml;";
	$f[]="    text/x-component                      htc;";
	$f[]="";
	$f[]="    image/png                             png;";
	$f[]="    image/tiff                            tif tiff;";
	$f[]="    image/vnd.wap.wbmp                    wbmp;";
	$f[]="    image/x-icon                          ico;";
	$f[]="    image/x-jng                           jng;";
	$f[]="    image/x-ms-bmp                        bmp;";
	$f[]="    image/svg+xml                         svg svgz;";
	$f[]="    image/webp                            webp;";
	$f[]="";
	$f[]="    application/font-woff                 woff;";
	$f[]="    application/java-archive              jar war ear;";
	$f[]="    application/json                      json;";
	$f[]="    application/mac-binhex40              hqx;";
	$f[]="    application/msword                    doc;";
	$f[]="    application/pdf                       pdf;";
	$f[]="    application/postscript                ps eps ai;";
	$f[]="    application/rtf                       rtf;";
	$f[]="    application/vnd.apple.mpegurl         m3u8;";
	$f[]="    application/vnd.ms-excel              xls;";
	$f[]="    application/vnd.ms-fontobject         eot;";
	$f[]="    application/vnd.ms-powerpoint         ppt;";
	$f[]="    application/vnd.wap.wmlc              wmlc;";
	$f[]="    application/vnd.google-earth.kml+xml  kml;";
	$f[]="    application/vnd.google-earth.kmz      kmz;";
	$f[]="    application/x-7z-compressed           7z;";
	$f[]="    application/x-cocoa                   cco;";
	$f[]="    application/x-java-archive-diff       jardiff;";
	$f[]="    application/x-java-jnlp-file          jnlp;";
	$f[]="    application/x-makeself                run;";
	$f[]="    application/x-perl                    pl pm;";
	$f[]="    application/x-pilot                   prc pdb;";
	$f[]="    application/x-rar-compressed          rar;";
	$f[]="    application/x-redhat-package-manager  rpm;";
	$f[]="    application/x-sea                     sea;";
	$f[]="    application/x-shockwave-flash         swf;";
	$f[]="    application/x-stuffit                 sit;";
	$f[]="    application/x-tcl                     tcl tk;";
	$f[]="    application/x-x509-ca-cert            der pem crt;";
	$f[]="    application/x-xpinstall               xpi;";
	$f[]="    application/xhtml+xml                 xhtml;";
	$f[]="    application/xspf+xml                  xspf;";
	$f[]="    application/zip                       zip;";
	$f[]="";
	$f[]="    application/octet-stream              bin exe dll;";
	$f[]="    application/octet-stream              deb;";
	$f[]="    application/octet-stream              dmg;";
	$f[]="    application/octet-stream              iso img;";
	$f[]="    application/octet-stream              msi msp msm;";
	$f[]="";
	$f[]="    application/vnd.openxmlformats-officedocument.wordprocessingml.document    docx;";
	$f[]="    application/vnd.openxmlformats-officedocument.spreadsheetml.sheet          xlsx;";
	$f[]="    application/vnd.openxmlformats-officedocument.presentationml.presentation  pptx;";
	$f[]="";
	$f[]="    audio/midi                            mid midi kar;";
	$f[]="    audio/mpeg                            mp3;";
	$f[]="    audio/ogg                             ogg;";
	$f[]="    audio/x-m4a                           m4a;";
	$f[]="    audio/x-realaudio                     ra;";
	$f[]="";
	$f[]="    video/3gpp                            3gpp 3gp;";
	$f[]="    video/mp2t                            ts;";
	$f[]="    video/mp4                             mp4;";
	$f[]="    video/mpeg                            mpeg mpg;";
	$f[]="    video/quicktime                       mov;";
	$f[]="    video/webm                            webm;";
	$f[]="    video/x-flv                           flv;";
	$f[]="    video/x-m4v                           m4v;";
	$f[]="    video/x-mng                           mng;";
	$f[]="    video/x-ms-asf                        asx asf;";
	$f[]="    video/x-ms-wmv                        wmv;";
	$f[]="    video/x-msvideo                       avi;";
	$f[]="}";
	$f[]="";
	$f[]="";
	
	$f[]="server {";
	$f[]="	listen $LISTENIP:$XapianSearchPort;";
	$f[]="  server_name _;";
	$f[]="  index xapian.search.php;";
	$f[]="  root /usr/share/artica-postfix/;";

	$f[]="";
	$f[]="	location ~ [^/]\.php(/|\$) {";
	$f[]="		fastcgi_split_path_info ^(.+?\.php)(/.*)\$;";
	$f[]="		if (!-f \$document_root\$fastcgi_script_name) {";
	$f[]="			return 404;";
	$f[]="		}";
	$f[]="		fastcgi_pass 	   unix:/var/run/XapianSearchWeb.sock;";
	$f[]="		fastcgi_buffers 8 16k;";
	$f[]="		fastcgi_buffer_size 32k;";
	$f[]="		fastcgi_read_timeout 300;";
	$f[]="		fastcgi_connect_timeout 300;";
	$f[]="		fastcgi_send_timeout 300;";
	$f[]="		fastcgi_param   QUERY_STRING             \$query_string;";
	$f[]="		fastcgi_param   REQUEST_METHOD           \$request_method;";
	$f[]="		fastcgi_param   CONTENT_TYPE             \$content_type;";
	$f[]="		fastcgi_param   CONTENT_LENGTH           \$content_length;";
	$f[]="";
	$f[]="		fastcgi_param   SCRIPT_FILENAME          \$document_root\$fastcgi_script_name;";
	$f[]="		fastcgi_param   SCRIPT_NAME              \$fastcgi_script_name;";
	$f[]="		fastcgi_param   PATH_INFO                \$fastcgi_path_info;";
	$f[]="		fastcgi_param   PATH_TRANSLATED          \$document_root\$fastcgi_script_name;";
	$f[]="		fastcgi_param   REQUEST_URI              \$request_uri;";
	$f[]="		fastcgi_param   DOCUMENT_URI             \$document_uri;";
	$f[]="		fastcgi_param   DOCUMENT_ROOT            \$document_root;";
	$f[]="		fastcgi_param   SERVER_PROTOCOL          \$server_protocol;";
	$f[]="";
	$f[]="		fastcgi_param   GATEWAY_INTERFACE       CGI/1.1;";
	$f[]="		fastcgi_param   SERVER_SOFTWARE         Artica/1.0;";
	$f[]="";
	$f[]="		fastcgi_param   REMOTE_ADDR              \$remote_addr;";
	$f[]="		fastcgi_param   REMOTE_PORT              \$remote_port;";
	$f[]="		fastcgi_param   SERVER_ADDR              \$server_addr;";
	$f[]="		fastcgi_param   SERVER_PORT              \$server_port;";
	$f[]="		fastcgi_param   SERVER_NAME              \$server_name;";
	$f[]="";
	$f[]="		fastcgi_param   HTTPS                    \$https;";
	$f[]="";
	$f[]="#		PHP only, required if PHP was built with --enable-force-cgi-redirect";
	$f[]="		fastcgi_param   REDIRECT_STATUS         200;";
	$f[]="	}";
	$f[]="}";
	$f[]="";
	$f[]="";
	$f[]="}";
	$f[]="";
	@file_put_contents("/etc/artica-postfix/xapian-web.conf", @implode("\n", $f));
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: /etc/artica-postfix/xapian-web.conf done\n";}
}
function stop($aspid=false){

	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}


	$GLOBALS["SERVICE_NAME"]="InstantSearch Web console (Main)";
	$pid=nginx_pid();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} already stopped...\n";}
		return;
	}

	$pid=nginx_pid();
	if($GLOBALS["MONIT"]){
		@file_put_contents("/var/run/xapian-webconsole.pid",$pid);
		return;
	}

	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");



	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=nginx_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=nginx_pid();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=nginx_pid();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} success...\n";}
		return;
	}else{
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} failed...\n";}
	}
}