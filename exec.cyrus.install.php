<?php



function create_cyrus_imapd_service(){
	if(is_file("/etc/artica-postfix/WEBSECURITY_APPLIANCE")){return;}
	$unix=new unix();
	$daemon_path=$unix->find_program("cyrmaster");
	if(!is_file($daemon_path)){return;}
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/cyrus-imapd";
	$php5script="exec.cyrus-imapd.php";
	$daemonbinLog="";


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         cyrus-common cyrus-imapd";
	$f[]="# Required-Start:    \$local_fs \$syslog";
	$f[]="# Required-Stop:     \$local_fs \$syslog";
	$f[]="# Should-Start:";
	$f[]="# Should-Stop:";
	$f[]="# Default-Start:     3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Common init system for cyrus IMAP/POP3 daemons";
	$f[]="# chkconfig: - 80 75";
	$f[]="# description: Common init system for cyrus IMAP/POP3 daemons";
	$f[]="### END INIT INFO";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="NAME=cyrmaster";
	$f[]="DAEMON=\"/usr/sbin/\${NAME}\"";
	$f[]="PIDFILE=\"/var/run/\${NAME}.pid\"";
	$f[]="DESC=\"Cyrus IMAPd\"";
	$f[]="# Check if Cyrus is installed (vs. removed but not purged)";
	$f[]="test -x \"\$DAEMON\" || exit 0";
	$f[]="LC_ALL=C";
	$f[]="export LC_ALL";
	$f[]="";

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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reload} --verbose for more infos\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


	echo "Cyrus-imapd: [INFO] Writing $INITD_PATH with new config\n";
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

}