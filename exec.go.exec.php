<?php
include_once(dirname(__FILE__) . '/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"] = new sockets();
$GLOBALS["GENPROGGNAME"] = "go.exec.progress";
$GLOBALS["TITLENAME"] = "Go Exec";
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
if ($argv[1] == "--update") {$GLOBALS["OUTPUT"] = true;update_version();exit();}
if ($argv[1] == "--forker") {$GLOBALS["OUTPUT"] = true;forker();exit();}
if ($argv[1] == "--init") {create_service();exit();}
if ($argv[1] == "--monit") {build_monit();exit();}

run();
function run(){
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("Go_Exec_Enable", 1);
    create_service();
    console_setup();
    build_monit();
    build_syslog();
    update_version();
}

function create_service(){
    $unix=new unix();
    $INITD_PATH = "/etc/init.d/go-exec";
    $daemonbinLog = "{$GLOBALS["TITLENAME"]}";
    $php=$unix->LOCATE_PHP5_BIN();
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          go-exec";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     2 3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: Go Exec script version [1.1]";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: Go Exec";
    $f[]="### END INIT INFO";
    $f[]="";
    $f[]=". /lib/lsb/init-functions";
    $f[]="";
    $f[]="BINARY=\"/bin/go-exec\"";
    $f[]="LC_ALL=C";
    $f[]="PATH=\"/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin\"";
    $f[]="";
    $f[]="";
    $f[]="getpid(){";
    $f[]="	if [  -f /var/run/go-exec.pid ]; then";
    $f[]="		PID=\$(cat /var/run/go-exec.pid)";
    $f[]="		STRLEN=\${#PID}";
    $f[]="		";
    $f[]="		if [ \"\$STRLEN\" -gt 0 ]; then ";
    $f[]="			if [ -f \"/proc/\$PID/cmdline\" ]; then";
    $f[]="				echo \$PID";
    $f[]="				return \$PID";
    $f[]="			fi";
    $f[]="		fi";
    $f[]="	fi";
    $f[]="	";
    $f[]="	PID=\"\$(pidof -s \$BINARY)\"";
    $f[]="	STRLEN=\${#PID}";
    $f[]="	if [ \"\$STRLEN\" -gt 0 ]; then ";
    $f[]="		if [ -f \"/proc/\$PID/cmdline\" ]; then";
    $f[]="			echo \$PID";
    $f[]="			return \$PID";
    $f[]="		fi";
    $f[]="	fi";
    $f[]="	";
    $f[]="	echo \"0\"";
    $f[]="	return \"0\"";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="do_start() {";
    $f[]= " $php ".__FILE__." --init >/dev/null || true";
    $f[]="	pidnum=\$(getpid)";
    $f[]="	    if [ \"\$pidnum\" -gt \"0\" ]; then ";
    $f[]="	    	log_action_msg \"Daemon is currently running PID \$pidnum\"";
    $f[]="	    	exit 0";
    $f[]="	   fi";
    $f[]="";
    $f[]="	log_action_begin_msg \"Starting Go Executor\"";
    $f[]="	/bin/nohup \$BINARY >/dev/null 2>&1 &";
    $f[]="	";
    $f[]="	for i in 1 2 3 4 5";
    $f[]="	do";
    $f[]="		sleep 1";
    $f[]="		pidnum=\$(getpid)";
    $f[]="		if [ \"\$pidnum\" -gt \"0\" ]; then ";
    $f[]="		    	log_action_end_msg 0";
    $f[]="	    		exit 0";
    $f[]="	   	fi";
    $f[]="		";
    $f[]="	done";
    $f[]="	log_action_end_msg 1";
    $f[]="	exit 1";
    $f[]="";
    $f[]="}";
    $f[]="do_stop(){";
    $f[]="";
    $f[]="	pidnum=\$(getpid)";
    $f[]="	    if [ \"\$pidnum\" -eq \"0\" ]; then ";
    $f[]="	    	log_action_begin_msg \"Daemon is currently stopped\"";
    $f[]="	    	log_action_end_msg 0";
    $f[]="	    	return 0";
    $f[]="	   fi";
    $f[]="";
    $f[]=" 	  log_action_begin_msg \"Stopping Go Executor PID \$pidnum\"";
    $f[]="	  kill -9 \$pidnum";
    $f[]=" 	  for i in 1 2 3 4 5";
    $f[]="		do";
    $f[]="		pidnum=\$(getpid)";
    $f[]="		if [ \"\$pidnum\" -eq \"0\" ]; then ";
    $f[]=" 		 log_action_end_msg 0";
    $f[]=" 		 return 0";
    $f[]=" 		fi";
    $f[]=" 		sleep 1";
    $f[]="	done";
    $f[]="	log_action_end_msg 1";
    $f[]="	return 1";
    $f[]="";
    $f[]="}";
    $f[]="";
    $f[]="";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    do_start";
    $f[]="  ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="     do_stop";
    $f[]="  ;;";
    $f[]="";
    $f[]="  restart|force-reload)";
    $f[]="     do_stop";
    $f[]="     pidnum=\$(getpid)";
    $f[]="     if [ \"\$pidnum\" -eq \"0\" ]; then ";
    $f[]="      $php ".__FILE__." --update >/dev/null 2>&1 || true";
    $f[]="      do_start";
    $f[]="     fi";
    $f[]="     exit 1";
    $f[]="     ";
    $f[]=" ;;";
    $f[]="  status)";
    $f[]="";
    $f[]="	    pidnum=\$(getpid)";
    $f[]="	    if [ \"\$pidnum\" -gt \"0\" ]; then ";
    $f[]="	    	echo \"Daemon is currently running PID \$pidnum\"";
    $f[]="	    else";
    $f[]="	    	echo \"PID sent is \$pidnum, daemon not running\"";
    $f[]="	    fi";
    $f[]="    ;;";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|status}\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0";
    $f[]="";

    $INITD_PATH_MD51=md5_file($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH, 0755);
    $INITD_PATH_MD52=md5_file($INITD_PATH);
    if($INITD_PATH_MD51 == $INITD_PATH_MD52){return true;}
    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";




    if (is_file('/usr/sbin/update-rc.d')) {
        shell_exec("/usr/sbin/update-rc.d -f " . basename($INITD_PATH) . " defaults >/dev/null 2>&1");
        shell_exec("/usr/sbin/update-rc.d -f " . basename($INITD_PATH) . " remove");

    }

    if (is_file('/sbin/chkconfig')) {
        shell_exec("/sbin/chkconfig --add " . basename($INITD_PATH) . " >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " . basename($INITD_PATH) . " on >/dev/null 2>&1");
    }
    return true;
}

function build_monit():bool{
    $srcmd5=null;
    $unix=new unix();
    $monit_file = "/etc/monit/conf.d/go-exec.monitrc";
    $EnableGoExecuter = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("Go_Exec_Enable"));
    if ($EnableGoExecuter == 0) {
        if (is_file($monit_file)) {
            @unlink($monit_file);
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
            return true;
        }
    }


    $scriptfile="/usr/share/artica-postfix/bin/monit-exec -start-goexec";

    $sh=array();
    $scriptfile_restart="/usr/sbin/go-exec-restart.sh";
    $sh[]="#!/bin/sh";
    $sh[]="/etc/init.d/go-exec stop --monit";
    $sh[]="$scriptfile";
    $sh[]="";
    @file_put_contents($scriptfile_restart,@implode("\n",$sh));
    @chmod($scriptfile_restart,0755);


    $srcfile = "/etc/monit/conf.d/go-exec.monitrc";
    $GoExecServerIP = "127.0.0.1";
    $GoExecServerPort = 3334;
    if(is_file($srcfile)){$srcmd5 = md5_file($srcfile);}

    $f[] = "check process APP_GO_EXEC with pidfile /var/run/go-exec.pid";
    $f[] = "\tstart program = \"$scriptfile\"";
    $f[] = "\tstop program = \"/etc/init.d/go-exec stop --monit\"";
    $f[] = "\trestart program = \"$scriptfile_restart\"";
    $f[] = "\tif failed host $GoExecServerIP port $GoExecServerPort type tcp then restart";
    $f[] = "";

    @file_put_contents($monit_file, @implode("\n", $f));
    if (!is_file($monit_file)) {echo "$monit_file failed !!!\n";}

    echo "Monit: [INFO] Writing $monit_file\n";
    $srcdest = md5_file($srcfile);
    if ($srcdest == $srcmd5) {return true;}
    $unix->MONIT_RELOAD();
    return true;
}

function build_syslog(){
    $unix=new unix();
    if(!is_file("/var/log/go-shield/exec.log")){@touch("/var/log/go-shield/exec.log");}
    $conf="/etc/rsyslog.d/go-exec.conf";
    $md5_start=null;
    if(is_file($conf)){$md5_start=md5_file($conf);}
    $h[]="if  (\$programname =='go-exec') then {";
    $h[]=buildlocalsyslogfile("/var/log/go-shield/exec.log");
    $h[]="& stop";
    $h[]="}";
    @file_put_contents("/etc/rsyslog.d/go-exec.conf",@implode("\n", $h));
    $md5_end=md5_file($conf);
    if($md5_end<>$md5_start) {
        $unix->RESTART_SYSLOG(true);
    }

}

function console_setup(){
    $f[]="#!/bin/sh";
    $f[]="/usr/bin/nohup /etc/init.d/go-exec start >/dev/null 2>&1|| true";
    $f[]="do_configure=no";
    $f[]="case \"`uname 2>/dev/null`\" in";
    $f[]="    *FreeBSD*)";
    $f[]="        do_configure=yes";
    $f[]="        ;;";
    $f[]="    *) # assuming Linux with udev";
    $f[]="";
    $f[]="        # Skip only the first time (i.e. when the system boots)";
    $f[]="        [ ! -f /run/console-setup/boot_completed ] || do_configure=yes";
    $f[]="        mkdir -p /run/console-setup";
    $f[]="        > /run/console-setup/boot_completed";
    $f[]="        ";
    $f[]="        [ /etc/console-setup/cached_setup_terminal.sh -nt /etc/default/keyboard ] || do_configure=yes";
    $f[]="        [ /etc/console-setup/cached_setup_terminal.sh -nt /etc/default/console-setup ] || do_configure=yes";
    $f[]="        ;;";
    $f[]="esac";
    $f[]="";
    $f[]="if [ \"\$do_configure\" = no ]; then";
    $f[]="    :";
    $f[]="else";
    $f[]="    if [ -f /etc/default/locale ]; then";
    $f[]="        # In order to permit auto-detection of the charmap when";
    $f[]="        # console-setup-mini operates without configuration file.";
    $f[]="        . /etc/default/locale";
    $f[]="        export LANG";
    $f[]="    fi";
    $f[]="    setupcon --save";
    $f[]="fi";
    $f[]="";
    @file_put_contents("/lib/console-setup/console-setup.sh",@implode("\n",$f));
    @chmod("/lib/console-setup/console-setup.sh",0755);
    return true;
}

function  goserver_dst():string{
  return "/bin/go-exec";
}

function update_version()
{
    $unix = new unix();
    $ARROOT = ARTICA_ROOT;
    $goserver_src = "$ARROOT/bin/go-shield/exec/go-exec";
    $goserver_dst = goserver_dst();
    $cpbin = $unix->find_program("cp");
    $goserver_src_md5 = md5_file($goserver_src);
    $goserver_dst_md5 = md5_file($goserver_dst);

    if ($goserver_src_md5 == $goserver_dst_md5) {
        $go_shield_server_src       = "/usr/share/artica-postfix/bin/go-shield/exec/go-exec";
        $go_shield_server_dst       = "/bin/go-exec";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_SRC",md5_file($go_shield_server_src));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_DST",md5_file($go_shield_server_dst));
        echo "[OK]: $goserver_src_md5 == $goserver_dst_md5 - Already updated\n";
        forker();
        return false;
    }

    if(is_file("/etc/init.d/go-exec")){
        echo "Stopping Service...\n";
        shell_exec("/etc/init.d/go-exec stop");
    }
    if (is_file($goserver_dst)) {@unlink($goserver_dst);}
    $prc = 40;
    $UPDATED = false;
    echo "[OK]: $goserver_src_md5 != $goserver_dst_md5 - Updating, please wait\n";
    for ($i = 1; $i < 30; $i++) {
        $i++;
        $prc++;
        shell_exec("$cpbin -f $goserver_src $goserver_dst");
        sleep(1);
        $goserver_dst_md5 = md5_file($goserver_dst);
        if ($goserver_dst_md5 == $goserver_src_md5) {
            $UPDATED = true;
            break;
        }

    }
    @chmod($goserver_dst,0755);
    if ($UPDATED) {
        squid_admin_mysql(1, "The {$GLOBALS["TITLENAME"]} as been updated using md5 : $goserver_src",
            null, __FILE__, __LINE__);
    }
    $go_shield_server_src       = "/usr/share/artica-postfix/bin/go-shield/exec/go-exec";
    $go_shield_server_dst       = "/bin/go-exec";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_SRC",md5_file($go_shield_server_src));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_DST",md5_file($go_shield_server_dst));
    forker();
    if(is_file("/etc/init.d/go-exec")){
        echo "Create Service...\n";
        create_service();
        echo "Starting Service...\n";
        shell_exec("/etc/init.d/go-exec start");
    }
    return true;
}

function forker(){
    $unix = new unix();
    $ARROOT = ARTICA_ROOT;
    $goserver_src = "$ARROOT/bin/go-shield/exec/go-forker";
    $goserver_dst = "/bin/go-forker";
    $cpbin = $unix->find_program("cp");
    $goserver_src_md5 = md5_file($goserver_src);
    $goserver_dst_md5 = md5_file($goserver_dst);

    if ($goserver_src_md5 == $goserver_dst_md5) {
        $go_shield_server_src       = "/usr/share/artica-postfix/bin/go-shield/exec/go-forker";
        $go_shield_server_dst       = "/bin/go-forker";
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_SRC",md5_file($go_shield_server_src));
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_DST",md5_file($go_shield_server_dst));
        echo "[OK]: $goserver_src_md5 == $goserver_dst_md5 - Already updated\n";
        return false;
    }


    if (is_file($goserver_dst)) {
        @unlink($goserver_dst);
    }
    $prc = 40;
    $UPDATED = false;
    echo "[OK]: $goserver_src_md5 != $goserver_dst_md5 - Updating, please wait\n";
    for ($i = 1; $i < 30; $i++) {
        $i++;
        $prc++;
        shell_exec("$cpbin -f $goserver_src $goserver_dst");
        sleep(1);
        $goserver_dst_md5 = md5_file($goserver_dst);
        if ($goserver_dst_md5 == $goserver_src_md5) {
            $UPDATED = true;
            break;
        }

    }
    @chmod($goserver_dst,0755);
    if ($UPDATED) {
        squid_admin_mysql(1, "The {$GLOBALS["TITLENAME"]} as been updated using md5 : $goserver_src",
            null, __FILE__, __LINE__);
    }
    $go_shield_server_src       = "/usr/share/artica-postfix/bin/go-shield/exec/go-forker";
    $go_shield_server_dst       = "/bin/go-forker";
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_SRC",md5_file($go_shield_server_src));
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("GO_SHIELD_SERVER_DST",md5_file($go_shield_server_dst));

    return true;
}

function build_cron(){
    $unix=new unix();
    $RESTART=false;
    $fname=basename(__FILE__);

    $files[]="go-exec-update";
    $MD5START=array();$MD5END=array();
    foreach ($files as $cronf){
        if(!is_file("/etc/cron.d/$cronf")){continue;}
        $MD5START[$cronf]=md5_file("/etc/cron.d/$cronf");
    }

    if(!is_file("/etc/cron.d/go-exec-update")) {
        $unix->Popuplate_cron_make("go-exec-update", "* */2 * * *", "$fname --update");
    }




    foreach ($files as $cronf){
        if(!is_file("/etc/cron.d/$cronf")){continue;}
        $cronf_md5=md5_file("/etc/cron.d/$cronf");
        if(!isset($MD5START[$cronf])){$MD5START[$cronf]=null;}
        if($cronf_md5<>$MD5START[$cronf]){
            $RESTART=true;
        }
    }

    if($RESTART){
        $unix->ToSyslog("Restarting cron service for Reputation service scheduled tasks",false,"go-exec-update");
        UNIX_RESTART_CRON();
    }

}

