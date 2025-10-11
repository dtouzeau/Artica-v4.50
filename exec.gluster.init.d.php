<?php
include_once(dirname(__FILE__) . '/framework/class.unix.inc'); 
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');

if(is_array($argv)){
	if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--reinstall#",implode(" ",$argv))){$GLOBALS["REINSTALL"]=true;}
	if(preg_match("#--no-httpd-conf#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_CONF"]=true;}
	if(preg_match("#--noreload#",implode(" ",$argv))){$GLOBALS["NO_HTTPD_RELOAD"]=true;}
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}

DebianMode();


function DebianMode(){
	$unix=new unix();
	
	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          glusterd";
	$f[]="# Required-Start:    \$local_fs \$network";
	$f[]="# Required-Stop:     \$local_fs \$network";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Gluster File System service for volume management";
	$f[]="# Description:       Gluster File System service for volume management";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="# Author: Chris AtLee <chris@atlee.ca>";
	$f[]="# Patched by: Matthias Albert < matthias@linux4experts.de>";
	$f[]="";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="NAME=glusterd";
	$f[]="SCRIPTNAME=/etc/init.d/\$NAME";
	$f[]="DAEMON=/usr/sbin/\$NAME";
	$f[]="PIDFILE=/var/run/\$NAME.pid";
	$f[]="GLUSTERD_OPTS=\"\"";
	$f[]="PID=`test -f \$PIDFILE && cat \$PIDFILE`";
	$f[]="";
	$f[]="";
	$f[]="# Gracefully exit if the package has been removed.";
	$f[]="test -x \$DAEMON || exit 0";
	$f[]="";
	$f[]="# Load the VERBOSE setting and other rcS variables";
	$f[]=". /lib/init/vars.sh";
	$f[]="";
	$f[]="# Define LSB log_* functions.";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="";
	$f[]="do_start()";
	$f[]="{";
	$f[]="    pidofproc -p \$PIDFILE \$DAEMON >/dev/null";
	$f[]="    status=\$?";
	$f[]="    if [ \$status -eq 0 ]; then";
	$f[]="      log_success_msg \"glusterd service is already running with pid \$PID\"";
	$f[]="    else";
	$f[]="		mkdir -p /var/log/glusterfs";
	$f[]="      log_daemon_msg \"Starting glusterd service\" \"glusterd\"";
	$f[]="      start-stop-daemon --start --quiet --oknodo --pidfile \$PIDFILE --startas \$DAEMON -- -p \$PIDFILE \$GLUSTERD_OPTS";
	$f[]="      log_end_msg \$?";
	$f[]="      start_daemon -p \$PIDFILE \$DAEMON -f \$CONFIGFILE";
	$f[]="      return \$?";
	$f[]="    fi";
	$f[]="}";
	$f[]="";
	$f[]="do_stop()";
	$f[]="{";
	$f[]="    log_daemon_msg \"Stopping glusterd service\" \"glusterd\"";
	$f[]="    start-stop-daemon --stop --quiet --oknodo --pidfile \$PIDFILE";
	$f[]="    log_end_msg \$?";
	$f[]="    rm -f \$PIDFILE";
	$f[]="    killproc -p \$PIDFILE \$DAEMON";
	$f[]="    return \$?";
	$f[]="}";
	$f[]="";
	$f[]="do_status()";
	$f[]="{";
	$f[]="     pidofproc -p \$PIDFILE \$DAEMON >/dev/null";
	$f[]="     status=\$?";
	$f[]="     if [ \$status -eq 0 ]; then";
	$f[]="       log_success_msg \"glusterd service is running with pid \$PID\"";
	$f[]="     else";
	$f[]="       log_failure_msg \"glusterd service is not running.\"";
	$f[]="     fi";
	$f[]="     exit \$status";
	$f[]="}";
	$f[]="";
	$f[]="case \"\$1\" in";
	$f[]="  start)";
	$f[]="        do_start";
	$f[]="        ;;";
	$f[]="  stop)";
	$f[]="        do_stop";
	$f[]="        ;;";
	$f[]="  status)";
	$f[]="        do_status;";
	$f[]="        ;;";
	$f[]="  restart|force-reload)";
	$f[]="        do_stop";
	$f[]="        sleep 2";
	$f[]="        do_start";
	$f[]="        ;;";
	$f[]="  *)";
	$f[]="        echo \"Usage: \$SCRIPTNAME {start|stop|status|restart|force-reload}\" >&2";
	$f[]="        exit 3";
	$f[]="        ;;";
	$f[]="esac";
	@file_put_contents("/etc/init.d/gluster", @implode("\n", $f));	
	$debianbin=$unix->find_program("update-rc.d");
	$redhatbin=$unix->find_program("chkconfig");
	$chmod=$unix->find_program("chmod");
	shell_exec("$chmod +x /etc/init.d/gluster >/dev/null 2>&1");
	if(is_file($debianbin)){
		
		shell_exec("$debianbin -f gluster defaults >/dev/null 2>&1");
	}

}