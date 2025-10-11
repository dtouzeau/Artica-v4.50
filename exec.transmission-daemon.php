<?php
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');
include_once(dirname(__FILE__) . '/ressources/class.freeweb.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');

if($argv[1]=="--build"){build();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--install"){install();exit;}

function restart(){
	$unix=new unix();
	$php5=$unix->LOCATE_PHP5_BIN();
	$cmd=trim("$php5 /usr/share/artica-postfix/exec.initslapd.php --transmission-daemon >/dev/null 2>&1");
	
	shell_exec($cmd);
	shell_exec("/etc/init.d/transmission-daemon stop");
	shell_exec("/etc/init.d/transmission-daemon start");

	
}
function build_progress($pourc,$text){
	$echotext=$text;
	echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
	$cachefile="/usr/share/artica-postfix/ressources/logs/TransMissionDaemon.progress";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($cachefile, serialize($array));
	@chmod($cachefile,0755);
}

function uninstall(){
	$TransMissionDaemonDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TransMissionDaemonDir"));
	if($TransMissionDaemonDir==null){$TransMissionDaemonDir="/home/transmission-daemon";}
	
	build_progress(50, "{uninstall}");
	remove_service("/etc/init.d/transmission-daemon");
	build_progress(90, "{uninstall}");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableTransMissionDaemon", 0);
	if(is_dir($TransMissionDaemonDir)){shell_exec("rm -rf $TransMissionDaemonDir");}
	build_progress(100, "{uninstall} {done}");
}
function install(){
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableTransMissionDaemon", 1);
	build_progress(50, "{install}");
	install_service();
	build_progress(90, "{restarting}");
	restart();
	build_progress(100, "{install} {done}");
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}
function install_service(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$sock=new sockets();
	$TransMissionDaemonDir=$sock->GET_INFO("TransMissionDaemonDir");
	if($TransMissionDaemonDir==null){$TransMissionDaemonDir="/home/transmission-daemon";}

	@mkdir("$TransMissionDaemonDir/downloads",0755,true);

	@mkdir("/var/run/transmission-daemon",0755,true);
	@chown("/var/run/transmission-daemon", "debian-transmission");
	@chgrp("/var/run/transmission-daemon", "debian-transmission");

	@mkdir("$TransMissionDaemonDir/config",0755,true);
	@chown($TransMissionDaemonDir, "debian-transmission");
	@chgrp($TransMissionDaemonDir, "debian-transmission");

	@chown("$TransMissionDaemonDir/downloads", "debian-transmission");
	@chgrp("$TransMissionDaemonDir/downloads", "debian-transmission");
	@chown("$TransMissionDaemonDir/config", "debian-transmission");
	@chgrp("$TransMissionDaemonDir/config", "debian-transmission");

	$f[]="#!/bin/sh -e";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          transmission-daemon";
	$f[]="# Required-Start:    \$local_fs \$remote_fs \$network";
	$f[]="# Required-Stop:     \$local_fs \$remote_fs \$network";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Start or stop the transmission-daemon.";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="NAME=transmission-daemon";
	$f[]="DAEMON=/usr/bin/\$NAME";
	$f[]="USER=debian-transmission";
	$f[]="STOP_TIMEOUT=30";
	$f[]="";
	$f[]="export PATH=\"\${PATH:+\$PATH:}/sbin\"";
	$f[]="";
	$f[]="[ -x \$DAEMON ] || exit 0";
	$f[]="";

	$f[]="";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";

	$f[]="if [ ! -f \"/etc/artica-postfix/settings/Daemons/EnableTransMissionDaemon\" ]; then";
	$f[]="\tlog_daemon_msg \"Starting \$DESC (Not enabled!)\" \"\$NAME\"";
	$f[]="\texit 0";
	$f[]="fi";
	$f[]="OPTIONS=\"--config-dir /etc/transmission-daemon --download-dir $TransMissionDaemonDir/downloads --no-incomplete-dir --pid-file /var/run/transmission-daemon/transmission-daemon.pid\"";
	$f[]="EnableTransMissionDaemon=`cat /etc/artica-postfix/settings/Daemons/EnableTransMissionDaemon`";

	$f[]="if [ \$EnableTransMissionDaemon -eq 0 ]; then";
	$f[]="\tlog_daemon_msg \"Starting \$DESC (Not enabled!)\" \"\$NAME\"";
	$f[]="\tlog_daemon_msg \"DONE.....\" \"\$NAME\"";
	$f[]="\texit 0";
	$f[]="fi";


	$f[]="start_daemon () {";
	$f[]="    if [ \$EnableTransMissionDaemon != 1 ]; then";
	$f[]="        log_progress_msg \"(disabled)\"";
	$f[]="		log_end_msg 255 || true";
	$f[]="    else    ";
	$f[]="        start-stop-daemon --start --chuid \$USER \$START_STOP_OPTIONS --exec \$DAEMON -- \$OPTIONS || log_end_msg \$?";
	$f[]="	log_end_msg 0";
	$f[]="    fi";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="    start)";
	$f[]="		  $php /usr/share/artica-postfix/exec.transmission-daemon.php --build || true";
	$f[]="        log_daemon_msg \"Starting bittorrent daemon\" \"\$NAME\"";
	$f[]="        start_daemon";
	$f[]="        ;;";
	$f[]="    stop)";
	$f[]="        log_daemon_msg \"Stopping bittorrent daemon\" \"\$NAME\"";
	$f[]="        start-stop-daemon --stop --quiet --exec \$DAEMON --retry \$STOP_TIMEOUT --oknodo || log_end_msg \$?";
	$f[]="        log_end_msg 0";
	$f[]="        ;;";
	$f[]="    reload)";
	$f[]="        log_daemon_msg \"Reloading bittorrent daemon\" \"\$NAME\"";
	$f[]="		  $php /usr/share/artica-postfix/exec.transmission-daemon.php --build || true";
	$f[]="        start-stop-daemon --stop --quiet --exec \$DAEMON --oknodo --signal 1 || log_end_msg \$?";
	$f[]="        log_end_msg 0";
	$f[]="        ;;";
	$f[]="    restart|force-reload)";
	$f[]="        log_daemon_msg \"Restarting bittorrent daemon\" \"\$NAME\"";
	$f[]="		  $php /usr/share/artica-postfix/exec.transmission-daemon.php --build || true";
	$f[]="        start-stop-daemon --stop --quiet --exec \$DAEMON --retry \$STOP_TIMEOUT --oknodo || log_end_msg \$?";
	$f[]="        start_daemon";
	$f[]="        ;;";
	$f[]="    status)";
	$f[]="        status_of_proc \"\$DAEMON\" \"\$NAME\" && exit 0 || exit \$?";
	$f[]="        ;;";
	$f[]="    *)";
	$f[]="        log_action_msg \"Usage: /etc/init.d/\$NAME {start|stop|reload|force-reload|restart|status}\" || true";
	$f[]="        exit 2";
	$f[]="        ;;";
	$f[]="esac";
	$f[]="";
	$f[]="exit 0";
	$f[]="";

	@file_put_contents("/etc/init.d/transmission-daemon", @implode("\n", $f));
	@chmod("/etc/init.d/transmission-daemon",0755);





	if(is_file('/usr/sbin/update-rc.d')){
		shell_exec('/usr/sbin/update-rc.d -f transmission-daemon >/dev/null 2>&1');

	}

	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: bittorrent daemon success...\n";}

}

function build(){
	
	
	$sock=new sockets();
	$EnableTransMissionDaemon=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableTransMissionDaemon"));
	$TransMissionDaemonDir=$sock->GET_INFO("TransMissionDaemonDir");
	if($TransMissionDaemonDir==null){$TransMissionDaemonDir="/home/transmission-daemon/downloads";}
	
	@mkdir($TransMissionDaemonDir,0755,true);
	@chown($TransMissionDaemonDir, "debian-transmission");
	@chgrp($TransMissionDaemonDir, "debian-transmission");
	
	
	$ldap=new clladp();
	$TransMissionDaemonListen=$sock->GET_INFO("TransMissionDaemonListen");
	if($TransMissionDaemonListen==null){$TransMissionDaemonListen="0.0.0.0";}
	$TransMissionDaemonPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("TransMissionDaemonPort"));
	if($TransMissionDaemonPort==0){$TransMissionDaemonPort=9091;}
	
	$f[]="{";
	$f[]="    \"alt-speed-down\": 50, ";
	$f[]="    \"alt-speed-enabled\": false, ";
	$f[]="    \"alt-speed-time-begin\": 540, ";
	$f[]="    \"alt-speed-time-day\": 127, ";
	$f[]="    \"alt-speed-time-enabled\": false, ";
	$f[]="    \"alt-speed-time-end\": 1020, ";
	$f[]="    \"alt-speed-up\": 50, ";
	$f[]="    \"bind-address-ipv4\": \"$TransMissionDaemonListen\", ";
	$f[]="    \"bind-address-ipv6\": \"::\", ";
	$f[]="    \"blocklist-enabled\": false, ";
	$f[]="    \"blocklist-url\": \"http://www.example.com/blocklist\", ";
	$f[]="    \"cache-size-mb\": 4, ";
	$f[]="    \"dht-enabled\": true, ";
	$f[]="    \"download-dir\": \"/home/transmission-daemon/downloads\", ";
	$f[]="    \"download-limit\": 100, ";
	$f[]="    \"download-limit-enabled\": 0, ";
	$f[]="    \"download-queue-enabled\": true, ";
	$f[]="    \"download-queue-size\": 5, ";
	$f[]="    \"encryption\": 1, ";
	$f[]="    \"idle-seeding-limit\": 30, ";
	$f[]="    \"idle-seeding-limit-enabled\": false, ";
	$f[]="    \"incomplete-dir\": \"/root/Downloads\", ";
	$f[]="    \"incomplete-dir-enabled\": false, ";
	$f[]="    \"lpd-enabled\": false, ";
	$f[]="    \"max-peers-global\": 200, ";
	$f[]="    \"message-level\": 2, ";
	$f[]="    \"peer-congestion-algorithm\": \"\", ";
	$f[]="    \"peer-limit-global\": 240, ";
	$f[]="    \"peer-limit-per-torrent\": 60, ";
	$f[]="    \"peer-port\": 51413, ";
	$f[]="    \"peer-port-random-high\": 65535, ";
	$f[]="    \"peer-port-random-low\": 49152, ";
	$f[]="    \"peer-port-random-on-start\": false, ";
	$f[]="    \"peer-socket-tos\": \"default\", ";
	$f[]="    \"pex-enabled\": true, ";
	$f[]="    \"port-forwarding-enabled\": false, ";
	$f[]="    \"preallocation\": 1, ";
	$f[]="    \"prefetch-enabled\": 1, ";
	$f[]="    \"queue-stalled-enabled\": true, ";
	$f[]="    \"queue-stalled-minutes\": 30, ";
	$f[]="    \"ratio-limit\": 2, ";
	$f[]="    \"ratio-limit-enabled\": false, ";
	$f[]="    \"rename-partial-files\": true, ";
	$f[]="    \"rpc-authentication-required\": true, ";
	$f[]="    \"rpc-bind-address\": \"$TransMissionDaemonListen\", ";
	$f[]="    \"rpc-enabled\": true, ";
	$f[]="    \"rpc-password\": \"$ldap->ldap_password\", ";
	$f[]="    \"rpc-port\": $TransMissionDaemonPort, ";
	$f[]="    \"rpc-url\": \"/\", ";
	$f[]="    \"rpc-username\": \"$ldap->ldap_admin\", ";
	$f[]="    \"rpc-whitelist\": \"\", ";
	$f[]="    \"rpc-whitelist-enabled\": false, ";
	$f[]="    \"scrape-paused-torrents-enabled\": true, ";
	$f[]="    \"script-torrent-done-enabled\": false, ";
	$f[]="    \"script-torrent-done-filename\": \"\", ";
	$f[]="    \"seed-queue-enabled\": false, ";
	$f[]="    \"seed-queue-size\": 10, ";
	$f[]="    \"speed-limit-down\": 100, ";
	$f[]="    \"speed-limit-down-enabled\": false, ";
	$f[]="    \"speed-limit-up\": 100, ";
	$f[]="    \"speed-limit-up-enabled\": false, ";
	$f[]="    \"start-added-torrents\": true, ";
	$f[]="    \"trash-original-torrent-files\": false, ";
	$f[]="    \"umask\": 18, ";
	$f[]="    \"upload-limit\": 100, ";
	$f[]="    \"upload-limit-enabled\": 0, ";
	$f[]="    \"upload-slots-per-torrent\": 14, ";
	$f[]="    \"utp-enabled\": true";
	$f[]="}";
	$f[]="";	
	
	echo "\nConfiguring bittorrent daemon /etc/transmission-daemon/settings.json DONE.\n";
	@file_put_contents("/etc/transmission-daemon/settings.json", @implode("\n", $f));
	@chown("/etc/transmission-daemon/settings.json", "debian-transmission");
	@chgrp("/etc/transmission-daemon/settings.json", "debian-transmission");
	
}