<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");


function build_progress_install($text,$pourc){
    $echotext=$text;
    echo "Starting......: ".date("H:i:s")." {$pourc}% $echotext\n";
    $cachefile="/usr/share/artica-postfix/ressources/logs/apparmor.install.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}


if($argv[1]=="--uninstall"){uninstall_service();exit();}
if($argv[1]=="--install"){install_service();exit();}


function install_service(){
    $INITD_PATH="/etc/init.d/apparmor";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableAppArmor", 1);
    build_progress_install("{install_service}",30);
    create_service();
    build_progress_install("{install_service}",40);
    build();
    build_progress_install("{starting_service}",50);
    shell_exec("$INITD_PATH start");
    build_progress_install("{done}",100);
}
function uninstall_service(){
    $INITD_PATH="/etc/init.d/apparmor";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableAppArmor", 0);
    remove_service($INITD_PATH);
    build_progress_install("{done}",100);
}


function create_service():bool{
    $INITD_PATH="/etc/init.d/apparmor";
    $f[]="#!/bin/sh";
    $f[]="# ----------------------------------------------------------------------";
    $f[]="#    Copyright (c) 1999, 2000, 2001, 2002, 2003, 2004, 2005, 2006, 2007";
    $f[]="#     NOVELL (All rights reserved)";
    $f[]="#    Copyright (c) 2008, 2009 Canonical, Ltd.";
    $f[]="#";
    $f[]="#    This program is free software; you can redistribute it and/or";
    $f[]="#    modify it under the terms of version 2 of the GNU General Public";
    $f[]="#    License published by the Free Software Foundation.";
    $f[]="#";
    $f[]="#    This program is distributed in the hope that it will be useful,";
    $f[]="#    but WITHOUT ANY WARRANTY; without even the implied warranty of";
    $f[]="#    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the";
    $f[]="#    GNU General Public License for more details.";
    $f[]="#";
    $f[]="#    You should have received a copy of the GNU General Public License";
    $f[]="#    along with this program; if not, contact Novell, Inc.";
    $f[]="# ----------------------------------------------------------------------";
    $f[]="# Authors:";
    $f[]="#  Steve Beattie <steve.beattie@canonical.com>";
    $f[]="#  Kees Cook <kees@ubuntu.com>";
    $f[]="#";
    $f[]="# /etc/init.d/apparmor";
    $f[]="#";
    $f[]="# Note: \"Required-Start: \$local_fs\" implies that the cache may not be available";
    $f[]="# yet when /var is on a remote filesystem. The worst consequence this should";
    $f[]="# have is slowing down the boot.";
    $f[]="#";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides: apparmor";
    $f[]="# Required-Start: \$local_fs";
    $f[]="# Required-Stop: umountfs";
    $f[]="# Default-Start: S";
    $f[]="# Default-Stop:";
    $f[]="# Short-Description: AppArmor initialization";
    $f[]="# Description: AppArmor init script. This script loads all AppArmor profiles.";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]="APPARMOR_FUNCTIONS=/lib/apparmor/rc.apparmor.functions";
    $f[]="";
    $f[]="# Functions needed by rc.apparmor.functions";
    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="aa_action() {";
    $f[]="	STRING=\$1";
    $f[]="	shift";
    $f[]="	\$*";
    $f[]="	rc=\$?";
    $f[]="	if [ \$rc -eq 0 ] ; then";
    $f[]="		aa_log_success_msg \$\"\$STRING \"";
    $f[]="	else";
    $f[]="		aa_log_failure_msg \$\"\$STRING \"";
    $f[]="	fi";
    $f[]="	return \$rc";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_action_start() {";
    $f[]="	log_action_begin_msg \$@";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_action_end() {";
    $f[]="	log_action_end_msg \$@";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_success_msg() {";
    $f[]="	log_success_msg \$@";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_warning_msg() {";
    $f[]="	log_warning_msg \$@";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_failure_msg() {";
    $f[]="	log_failure_msg \$@";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_skipped_msg() {";
    $f[]="	if [ -n \"\$1\" ]; then";
    $f[]="		log_warning_msg \"\${1}: Skipped.\"";
    $f[]="	fi";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_daemon_msg() {";
    $f[]="	log_daemon_msg \$@";
    $f[]="}";
    $f[]="";
    $f[]="aa_log_end_msg() {";
    $f[]="	log_end_msg \$@";
    $f[]="}";
    $f[]="";
    $f[]="# Source AppArmor function library";
    $f[]="if [ -f \"\${APPARMOR_FUNCTIONS}\" ]; then";
    $f[]="	. \${APPARMOR_FUNCTIONS}";
    $f[]="else";
    $f[]="	aa_log_failure_msg \"Unable to find AppArmor initscript functions\"";
    $f[]="	exit 1";
    $f[]="fi";
    $f[]="";
    $f[]="usage() {";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reload|force-reload|status}\"";
    $f[]="}";
    $f[]="";
    $f[]="test -x \${PARSER} || exit 0 # by debian policy";
    $f[]="# LSM is built-in, so it is either there or not enabled for this boot";
    $f[]="test -d /sys/module/apparmor || exit 0";
    $f[]="";
    $f[]="# do not perform start/stop/reload actions when running from liveCD";
    $f[]="test -d /rofs/etc/apparmor.d && exit 0";
    $f[]="";
    $f[]="rc=255";
    $f[]="case \"\$1\" in";
    $f[]="	start)";
    $f[]="		if [ -x /usr/bin/systemd-detect-virt ] && systemd-detect-virt --quiet --container && ! is_container_with_internal_policy; then";
    $f[]="			aa_log_daemon_msg \"Not starting AppArmor in container\"";
    $f[]="			aa_log_end_msg 0";
    $f[]="			exit 0";
    $f[]="		fi";
    $f[]="		apparmor_start";
    $f[]="		rc=\$?";
    $f[]="		;;";
    $f[]="	restart|reload|force-reload)";
    $f[]="		if [ -x /usr/bin/systemd-detect-virt ] && systemd-detect-virt --quiet --container && ! is_container_with_internal_policy; then";
    $f[]="			aa_log_daemon_msg \"Not starting AppArmor in container\"";
    $f[]="			aa_log_end_msg 0";
    $f[]="			exit 0";
    $f[]="		fi";
    $f[]="		apparmor_restart";
    $f[]="		rc=\$?";
    $f[]="		;;";
    $f[]="	stop)";
    $f[]="		aa_log_daemon_msg \"Leaving AppArmor profiles loaded\"";
    $f[]="		cat >&2 <<EOM";
    $f[]="No profiles have been unloaded.";
    $f[]="";
    $f[]="Unloading profiles will leave already running processes permanently";
    $f[]="unconfined, which can lead to unexpected situations.";
    $f[]="";
    $f[]="To set a process to complain mode, use the command line tool";
    $f[]="'aa-complain'. To really tear down all profiles, run 'aa-teardown'.\"";
    $f[]="EOM";
    $f[]="		;;";
    $f[]="	status)";
    $f[]="		apparmor_status";
    $f[]="		rc=\$?";
    $f[]="		;;";
    $f[]="	*)";
    $f[]="		usage";
    $f[]="		rc=1";
    $f[]="		;;";
    $f[]="	esac";
    $f[]="exit \$rc\n";

    @file_put_contents("$INITD_PATH",@implode("\n"),$f);

        echo "[INFO] Writing $INITD_PATH with new config\n";
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

function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
    if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


function build():bool{
    if (!is_dir("/etc/apparmor.d")) {
        @mkdir("/etc/apparmor.d", 0755, true);
    }
    $f[] = "/usr/sbin/mysqld flags=(complain) {";
    $f[] = "  #include <abstractions/base>";
    $f[] = "  #include <abstractions/nameservice>";
    $f[] = "  #include <abstractions/user-tmp>";
    $f[] = "  #include <abstractions/mysql>";
    $f[] = "  #include <abstractions/winbind>";
    $f[] = "";
    $f[] = "  capability dac_override,";
    $f[] = "  capability sys_resource,";
    $f[] = "  capability setgid,";
    $f[] = "  capability setuid,";
    $f[] = "";
    $f[] = "  network tcp,";
    $f[] = "";
    $f[] = "  /etc/hosts.allow r,";
    $f[] = "  /etc/hosts.deny r,";
    $f[] = "";
    $f[] = "  /etc/mysql/*.pem r,";
    $f[] = "  /etc/mysql/conf.d/ r,";
    $f[] = "  /etc/mysql/conf.d/* r,";
    $f[] = "  /etc/mysql/my.cnf r,";
    $f[] = "  /usr/sbin/mysqld mr,";
    $f[] = "  /usr/share/mysql/** r,";
    $f[] = "  /var/log/mysql.log rw,";
    $f[] = "  /var/log/mysql.err rw,";
    $f[] = "  /var/lib/mysql/ r,";
    $f[] = "  /var/lib/mysql/** rwk,";
    $f[] = "  /var/log/mysql/ r,";
    $f[] = "  /var/log/mysql/* rw,";
    $f[] = "  /var/run/mysqld/mysqld.pid w,";
    $f[] = "  /var/run/mysqld/mysqld.sock w,";
    $f[] = " /var/run/mysqld/ r,";
    $f[] = " /var/run/mysqld/** rwk,";
    $f[] = " /sys/devices/system/cpu/ r,";
    $f[] = "}\n";

    @file_put_contents("/etc/apparmor.d/usr.sbin.mysqld", @implode("\n", $f));
    return true;
}
?>