<?php

class fail2ban{
	
	
	function __construct(){
		
		
		
	}
	

	
	function buildinit(){
		$unix=new unix();
		$daemonbinLog="Fail2ban daemon";
		$INITD_PATH="/etc/init.d/fail2ban";
		$php5script="exec.fail2ban.php";
		$php=$unix->LOCATE_PHP5_BIN();
		$touch=$unix->find_program("touch");
		$f[]="#!/bin/sh";
		$f[]="### BEGIN INIT INFO";
		$f[]="# Provides:          fail2ban";
		$f[]="# Required-Start:    \$local_fs \$remote_fs";
		$f[]="# Required-Stop:     \$local_fs \$remote_fs";
		$f[]="# Should-Start:      \$time \$network \$syslog iptables firehol shorewall ipmasq";
		$f[]="# Should-Stop:       \$network \$syslog iptables firehol shorewall ipmasq";
		$f[]="# Default-Start:     3 4 5";
		$f[]="# Default-Stop:      0 1 6";
		$f[]="# Short-Description: Start/stop fail2ban";
		$f[]="# Description:       Start/stop fail2ban, a daemon scanning the log files and";
		$f[]="#                    banning potential attackers.";
		$f[]="### END INIT INFO";
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
		$f[]="";
		$f[]=" reconfigure)";
		$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]=" reload)";
		$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
		$f[]="    ;;";
		$f[]="";
		$f[]="  *)";
		$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
		$f[]="    exit 1";
		$f[]="    ;;";
		$f[]="esac";
		$f[]="exit 0\n";
		$f[]="";
	
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
	
	}
	
	
	
}

