<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}

if(isset($argv[1])){
    if($argv[1]=="--uninstall"){uninstall();exit;}

}


function uninstall(){
    $unix=new unix();
    $INITD_PATH="/etc/init.d/mosquitto";
    $unix->remove_service($INITD_PATH);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("MosquittoEnabled",0);


}

function build_progress($prc,$text){
    $unix=new unix();
    $unix->framework_progress($prc,$text);
}

function install(){
    build_progress(20,"{installing} {APP_MOSQUITTO}");

}

function create_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="#! /bin/sh";
    $f[]="";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:		mosquitto";
    $f[]="# Required-Start:	\$remote_fs \$syslog";
    $f[]="# Required-Stop:	\$remote_fs \$syslog";
    $f[]="# Default-Start:	2 3 4 5";
    $f[]="# Default-Stop:		0 1 6";
    $f[]="# Short-Description:	mosquitto MQTT v3.1 message broker";
    $f[]="# Description: ";
    $f[]="#  This is a message broker that supports version 3.1 of the MQ Telemetry";
    $f[]="#  Transport (MQTT) protocol.";
    $f[]="#  ";
    $f[]="#  MQTT provides a method of carrying out messaging using a publish/subscribe";
    $f[]="#  model. It is lightweight, both in terms of bandwidth usage and ease of";
    $f[]="#  implementation. This makes it particularly useful at the edge of the network";
    $f[]="#  where a sensor or other simple device may be implemented using an arduino for";
    $f[]="#  example.";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="set -e";
    $f[]="";
    $f[]="PIDFILE=/var/run/mosquitto.pid";
    $f[]="DAEMON=/usr/sbin/mosquitto";
    $f[]="";
    $f[]="# /etc/init.d/mosquitto: start and stop the mosquitto MQTT message broker";
    $f[]="";
    $f[]="test -x \${DAEMON} || exit 0";
    $f[]="";
    $f[]="umask 022";
    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="# Are we running from init?";
    $f[]="run_by_init() {";
    $f[]="    ([ \"\$previous\" ] && [ \"\$runlevel\" ]) || [ \"\$runlevel\" = S ]";
    $f[]="}";
    $f[]="";
    $f[]="export PATH=\"\${PATH:+\$PATH:}/usr/sbin:/sbin\"";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]="  start)";
    $f[]="	if init_is_upstart; then";
    $f[]="	    exit 1";
    $f[]="	fi";
    $f[]="	log_daemon_msg \"Starting network daemon:\" \"mosquitto\"";
    $f[]="	if start-stop-daemon --start --quiet --oknodo --background  --make-pidfile --pidfile \${PIDFILE} --exec \${DAEMON} -- -c /etc/mosquitto/mosquitto.conf ; then";
    $f[]="	    log_end_msg 0";
    $f[]="	else";
    $f[]="	    log_end_msg 1";
    $f[]="	fi";
    $f[]="	;;";
    $f[]="  stop)";
    $f[]="	if init_is_upstart; then";
    $f[]="	    exit 0";
    $f[]="	fi";
    $f[]="	log_daemon_msg \"Stopping network daemon:\" \"mosquitto\"";
    $f[]="	if start-stop-daemon --stop --quiet --oknodo --pidfile \${PIDFILE}; then";
    $f[]="	    log_end_msg 0";
    $f[]="	    rm -f \${PIDFILE}";
    $f[]="	else";
    $f[]="	    log_end_msg 1";
    $f[]="	fi";
    $f[]="	;;";
    $f[]="";
    $f[]="";
    $f[]="  reload|force-reload)";
    $f[]="	if init_is_upstart; then";
    $f[]="	    exit 1";
    $f[]="	fi";
    $f[]=" $php ".__FILE__. " --build >/dev/null 2>&1";
    $f[]="	log_daemon_msg \"Reloading network daemon configuration:\" \"mosquitto\"";
    $f[]="        if start-stop-daemon --stop --signal HUP --quiet --oknodo --pidfile \$PIDFILE; then";
    $f[]="            log_end_msg 0";
    $f[]="        else";
    $f[]="            log_end_msg 1";
    $f[]="        fi	";
    $f[]="	;;";
    $f[]="";
    $f[]="  restart)";
    $f[]="	if init_is_upstart; then";
    $f[]="	    exit 1";
    $f[]="	fi";
    $f[]="	log_daemon_msg \"Restarting network daemon:\" \"mosquitto\"";
    $f[]="	if start-stop-daemon --stop --quiet --oknodo --retry 30 --pidfile \${PIDFILE}; then";
    $f[]="	    rm -f \${PIDFILE}";
    $f[]="	fi";
    $f[]=" $php ".__FILE__. " --build >/dev/null 2>&1";
    $f[]="	if start-stop-daemon --start --quiet --oknodo --background --make-pidfile --pidfile \${PIDFILE} --exec \${DAEMON} -- -c /etc/mosquitto/mosquitto.conf ; then";
    $f[]="	    log_end_msg 0";
    $f[]="	else";
    $f[]="	    log_end_msg 1";
    $f[]="	fi";
    $f[]="	;;";
    $f[]="";
    $f[]="  try-restart)";
    $f[]="	if init_is_upstart; then";
    $f[]="	    exit 1";
    $f[]="	fi";
    $f[]="	log_daemon_msg \"Restarting Mosquitto message broker\" \"mosquitto\"";
    $f[]="	set +e";
    $f[]="	start-stop-daemon --stop --quiet --retry 30 --pidfile \${PIDFILE}";
    $f[]="	RET=\"\$?\"";
    $f[]="	set -e";
    $f[]="	case \$RET in";
    $f[]="	    0)";
    $f[]="		# old daemon stopped";
    $f[]="		rm -f \${PIDFILE}";
    $f[]="		if start-stop-daemon --start --quiet --oknodo --background --make-pidfile --pidfile \${PIDFILE} --exec \${DAEMON} -- -c /etc/mosquitto/mosquitto.conf ; then";
    $f[]="		    log_end_msg 0";
    $f[]="		else";
    $f[]="		    log_end_msg 1";
    $f[]="		fi";
    $f[]="		;;";
    $f[]="	    1)";
    $f[]="		# daemon not running";
    $f[]="		log_progress_msg \"(not running)\"";
    $f[]="		log_end_msg 0";
    $f[]="		;;";
    $f[]="	    *)";
    $f[]="		# failed to stop";
    $f[]="		log_progress_msg \"(failed to stop)\"";
    $f[]="		log_end_msg 1";
    $f[]="		;;";
    $f[]="	esac";
    $f[]="	;;";
    $f[]="";
    $f[]="  status)";
    $f[]="	if init_is_upstart; then";
    $f[]="	    exit 1";
    $f[]="	fi";
    $f[]="	status_of_proc -p \${PIDFILE} \${DAEMON} mosquitto && exit 0 || exit \$?";
    $f[]="	;;";
    $f[]="";
    $f[]="  *)";
    $f[]="	log_action_msg \"Usage: /etc/init.d/mosquitto {start|stop|reload|force-reload|restart|try-restart|status}\"";
    $f[]="	exit 1";
    $f[]="esac";
    $f[]="";
    $f[]="exit 0";
    $f[]="";
}

