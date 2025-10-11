<?php
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.tcpip.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
$GLOBALS["OUTPUT"]=true;
$GLOBALS["TITLENAME"]="Glances";
if($argv[1]=="--connect"){connect($argv[2]);exit;}
if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--stop"){stop();exit;}
if($argv[1]=="--start"){start();exit;}
if($argv[1]=="--restart"){restart();exit;}
if($argv[1]=="--testjoin"){Testjoin();exit;}
if($argv[1]=="--license"){save_lic($argv[2]);exit;}
if($argv[1]=="--unjoin"){disconnect();exit;}



function Testjoin():bool{
    $unix=new unix();
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $SafeGuardAdCnxID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeGuardAdCnxID"));
    $AD=$ActiveDirectoryConnections[$SafeGuardAdCnxID];
    $preflight="/opt/quest/bin/preflight";
    $Tarray=array();
    if(!isset($AD["LDAP_SERVER"])){
        $Tarray["STATUS"]=false;
        $Tarray["INFOS"][]=array("Connection $SafeGuardAdCnxID Corrupted",false);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafeGuardAdStatus",serialize($Tarray));
        return false;
    }

    $LDAP_DN=$AD["LDAP_DN"];
    $LDAP_PASSWORD=$AD["LDAP_PASSWORD"];
    if(strlen($LDAP_PASSWORD)<3){
        $Tarray["STATUS"]=false;
        $Tarray["INFOS"][]=array("Connection $SafeGuardAdCnxID Password not set",false);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafeGuardAdStatus",serialize($Tarray));
        return false;
    }
    $LDAP_PASSWORD=$unix->shellEscapeChars($LDAP_PASSWORD);


    if(strpos($LDAP_DN,"@")>0){
        $tb=explode("@",$LDAP_DN);
        $LDAP_DN=$tb[0];

    }
    $cmd="$preflight -w \"$LDAP_PASSWORD\" -u $LDAP_DN --verbose 2>&1";
    exec($cmd,$results);
    $CHECK=true;
    foreach ($results as $line) {
        $line = trim($line);
        if (preg_match("#^(.+?)\s+(Success|Failure)#i", $line, $re)) {
            $sline = $re[1];
            $xres = true;
            if (trim(strtolower($re[2])) == "failure") {
                $xres = false;
                $CHECK = false;
            }
            $Tarray["INFOS"][] = array($sline, $xres);

        }
    }
        $Tarray["STATUS"]=$CHECK;
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafeGuardAdStatus",serialize($Tarray));
        return true;
}
function save_lic($encoded_file):bool{
    $unix=new unix();
    $filepath=base64_decode($encoded_file);
    list($results,$LDAP_DN,$LDAP_PASSWORD,$DOMAIN)=AdxInfos();
    if(!$results){return false;}
    $out=PROGRESS_DIR."/vastool-addlicense";
    $LDAP_PASSWORD=$unix->shellEscapeChars($LDAP_PASSWORD);
    $filepath=$unix->shellEscapeChars($filepath);
    if(!is_file($filepath)){
        @file_put_contents($out,"ERROR: $filepath no such file\n");
        return false;
    }


    $cmd="/opt/quest/bin/vastool -d 6 -u $LDAP_DN -w \"$LDAP_PASSWORD\" license add \"$filepath\" >$out 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    //@unlink($filepath);
    return true;
}

function AdxInfos():array{
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $SafeGuardAdCnxID=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SafeGuardAdCnxID"));
    $DOMAIN=null;
    $AD=$ActiveDirectoryConnections[$SafeGuardAdCnxID];
    if(!isset($AD["LDAP_SERVER"])){
        build_progress(110,"Corrupted connection");
        return array(false,null,null,null);
    }
    $LDAP_DN=$AD["LDAP_DN"];
    $LDAP_PASSWORD=$AD["LDAP_PASSWORD"];
    if(strlen($LDAP_PASSWORD)<3){
        build_progress(110,"Corrupted password");
        return array(false,null,null,null);
    }
    if(strpos($LDAP_DN,"@")>0){
        $tb=explode("@",$LDAP_DN);
        $LDAP_DN=$tb[0];
        $DOMAIN=$tb[1];
    }

    return array(true,$LDAP_DN,$LDAP_PASSWORD,$DOMAIN);

}

function disconnect():bool{
    $unix=new unix();
    $vastool="/opt/quest/bin/vastool";


    list($results,$LDAP_DN,$LDAP_PASSWORD,$DOMAIN)=AdxInfos();
    if(!$results){return false;}


    if(!isset($AD["LDAP_SERVER"])){
        build_progress(110,"Corrupted connection");
        return false;
    }

    if(strlen($LDAP_PASSWORD)<3){
        build_progress(110,"Corrupted password");
        return false;
    }

    $LDAP_PASSWORD=$unix->shellEscapeChars($LDAP_PASSWORD);
    $echo=$unix->find_program("echo");

    if(strpos($LDAP_DN,"@")>0){
        $tb=explode("@",$LDAP_DN);
        $LDAP_DN=$tb[0];

    }
    $tempsh=$unix->FILE_TEMP()."sh";
    $temptxt=$unix->FILE_TEMP()."txt";
    $rm=$unix->find_program("rm");
    build_progress(50,"{unjoin}");

    $sh[]="#!/bin/sh";
    $sh[]="$echo \"$LDAP_PASSWORD\"|$vastool -u $LDAP_DN -s unjoin -f -l  >$temptxt 2>&1";
    $sh[]="$rm -f $tempsh\n";
    @file_put_contents($tempsh,@implode("\n",$sh));
    $f=explode("\n",@file_get_contents($temptxt));
    @unlink($temptxt);
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        echo "$line\n";
        if(preg_match("#Successfully unjoined from#",$line)){
            build_progress(100,"{success}");
            return true;
        }
        if(preg_match("#ERROR:(.+?)#",$line,$re)){
            build_progress(110,$re[1]);
            echo "-------------------------------\n".@implode("\n",$f)."\n----------------------------\n";
            return true;
        }
    }

    build_progress(100,"{success}");
    return true;


}

function connect($Index):bool{

    $unix=new unix();
    $vastool="/opt/quest/bin/vastool";
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));


    $AD=$ActiveDirectoryConnections[$Index];
    if(!isset($AD["LDAP_SERVER"])){
        build_progress(110,"Corrupted connection");
        return false;
    }


    $LDPSERV=$AD["LDAP_SERVER"];
    $LDAP_DN=$AD["LDAP_DN"];
    $LDAP_PASSWORD=$AD["LDAP_PASSWORD"];
    if(strlen($LDAP_PASSWORD)<3){
        build_progress(110,"Corrupted password");
        return false;
    }
    $DOMAIN=null;
    $LDAP_PASSWORD=$unix->shellEscapeChars($LDAP_PASSWORD);
    $echo=$unix->find_program("echo");

    if(strpos($LDAP_DN,"@")>0){
        $tb=explode("@",$LDAP_DN);
        $LDAP_DN=$tb[0];
        $DOMAIN=$tb[1];
    }
    echo "Using account <$LDAP_DN>\n";
    $tempsh=$unix->FILE_TEMP()."sh";
    $temptxt=$unix->FILE_TEMP()."txt";
    $hostname=php_uname('n');
    $rm=$unix->find_program("rm");
     build_progress(50,"{connecting}");
    $cmd="$echo \"$LDAP_PASSWORD\"|$vastool -u $LDAP_DN -s join -f  -n $hostname $DOMAIN $LDPSERV >$temptxt 2>&1";
echo $cmd."\n";
    $sh[]="#!/bin/sh";
    $sh[]=$cmd;
    $sh[]="$rm -f $tempsh\n";
    @file_put_contents($tempsh,@implode("\n",$sh));
    @chmod($tempsh,0755);
    shell_exec($tempsh);
    $f=explode("\n",@file_get_contents($temptxt));
    @unlink($temptxt);
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        echo "$line\n";
        if(preg_match("#Joining computer to the domain as host\/.*?OK#",$line)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("SafeGuardAdCnxID",$Index);
            build_progress(100,"{success}");
            return true;
        }
        if(preg_match("#ERROR:(.+?)#",$line,$re)){
            build_progress(110,$re[1]);
            echo "-------------------------------\n".@implode("\n",$f)."\n----------------------------\n";
            return true;
        }
    }

    build_progress(100,"{success} - - ?");
    return true;

}

function remove_systemd(){
    $f[]="/usr/lib/systemd/system/glances.service";
    $f[]="/var/lib/systemd/deb-systemd-helper-enabled/glances.service.dsh-also";
    $f[]="/var/lib/systemd/deb-systemd-helper-enabled/multi-user.target.wants/glances.service";
    foreach ($f as $file){
        if(is_file($file)){
            @unlink($file);
        }
    }
}




function build_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"vasd.progress");
}
function restart_progress($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"restart-glances.progress");
}


function install(){
	$unix=new unix();
	build_progress("{installing}",20);
	
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){ $unix->DEBIAN_INSTALL_PACKAGE("python3-bottle");}
	
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){
		echo "/usr/lib/python3/dist-packages/bottle.py no such file ( see apt-get install python3-bottle)\n";
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 0);
		build_progress("{failed}",110);
		return;
	}
	
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 1);
	prepare();
	build_progress("{installing}",50);
	glances_service();
	build_progress("{installing}",60);
	monit();
	schedule();
	build_progress("{starting}",70);
	system("/etc/init.d/glances start");
	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 0);
		prepare();
		build_progress("{failed}",110);
		return;
	}
	build_progress("{success}",100);
	
}


function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file("/var/run/glances/glances.pid");
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("glances");
	return $unix->PIDOF_PATTERN($Masterbin);

}
function uninstall(){
	build_progress("{uninstalling}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableGlances", 0);
	prepare();
	if(is_file("/etc/monit/conf.d/APP_GLANCES.monitrc")){
		@unlink("/etc/monit/conf.d/APP_GLANCES.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	@unlink("/etc/cron.d/glances-restart");
	system("/etc/init.d/cron reload");
	build_progress("{stopping_service}",70);
    remove_service("/etc/init.d/glances");
    build_progress("{success}",100);
}


function monit(){
	$f[]="check process APP_GLANCES with pidfile /var/run/glances/glances.pid";
	$f[]="start program = \"/etc/init.d/glances start\"";
	$f[]="stop program = \"/etc/init.d/glances stop\"";
	$f[]="if 5 restarts within 5 cycles then timeout";
	@file_put_contents("/etc/monit/conf.d/APP_GLANCES.monitrc", @implode("\n", $f));
	shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
}
function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


function glances_service(){
	
	
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$glances_parth=$unix->find_program("glances");
	
	$f[]="#! /bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:          glances";
	$f[]="# Required-Start:    \$remote_fs \$local_fs \$network";
	$f[]="# Required-Stop:     \$remote_fs \$local_fs \$network";
	$f[]="# Default-Start:     2 3 4 5";
	$f[]="# Default-Stop:      0 1 6";
	$f[]="# Short-Description: Starts and daemonize Glances server";
	$f[]="# Description:       Starts and daemonize Glances server";
	$f[]="### END INIT INFO";
	$f[]="";
	$f[]="# Author: Geoffroy Youri Berret <efrim@azylum.org>";
	$f[]="";
	$f[]="# PATH should only include /usr/* if it runs after the mountnfs.sh script";
	$f[]="PATH=/sbin:/usr/sbin:/bin:/usr/bin";
	$f[]="DESC=\"Glances server\"";
	$f[]="NAME=glances";
	$f[]="USER=\$NAME";
	$f[]="DAEMON=\"$glances_parth\"";
	$f[]="PIDFILE=\"/var/run/glances/glances.pid\"";
	$f[]="CONF=\"/etc/glances/glances.conf\"";
	$f[]="DAEMON_ARGS=\"-C \$CONF -s\"";
	$f[]="";
	$f[]="# Exit if the package is not installed";
	$f[]="[ -x \"\$DAEMON\" ] || exit 0";
	$f[]="";
	$f[]="# Read configuration variable file if it is present";
	$f[]="[ -r /etc/default/\$NAME ] && . /etc/default/\$NAME";
	$f[]="";
	$f[]="# Define LSB log_* functions.";
	$f[]="# Depend on lsb-base (>= 3.2-14) to ensure that this file is present";
	$f[]="# and status_of_proc is working.";
	$f[]=". /lib/lsb/init-functions";
	$f[]="";
	$f[]="# Ensure /run/glances is there, cf. Debian policy 9.4.1";
	$f[]="# http://www.debian.org/doc/debian-policy/ch-opersys.html#s-fhs-run";
	$f[]="if [ ! -d \"$(dirname \$PIDFILE)\" ]; then";
	$f[]="    mkdir \"$(dirname \$PIDFILE)\"";
	$f[]="    chown \$USER:\$USER \"$(dirname \$PIDFILE)\"";
	$f[]="    chmod 755 \"$(dirname \$PIDFILE)\"";
	$f[]="fi";
	$f[]="";
	$f[]="#";
	$f[]="# Function that starts the daemon/service";
	$f[]="#";
	$f[]="do_start()";
	$f[]="{";
	$f[]="    log_daemon_msg \"Starting \$DESC\" \"\$NAME \"";
	$f[]="    $php ".__FILE__." --prepare";
	$f[]="";
	$f[]="    if [ \"\$RUN\" != \"true\" ]; then";
	$f[]="        log_action_msg \"Not starting glances: disabled by /etc/default/\$NAME\".";
	$f[]="        exit 0";
	$f[]="    fi";
	$f[]="";
	$f[]="    $php ".__FILE__." --start";
	$f[]="    return 0";
	$f[]="}";
	$f[]="";
	$f[]="#";
	$f[]="# Function that stops the daemon/service";
	$f[]="#";
	$f[]="do_stop()";
	$f[]="{";
	$f[]="    log_daemon_msg \"Stopping \$DESC\" \"\$NAME \"";
	$f[]="    $php ".__FILE__." --stop";
	$f[]="    return 0";
	$f[]="}";
	$f[]="";
	$f[]="case \"$1\" in";
	$f[]="  start)";
	$f[]="    do_start";
	$f[]="    case \"$?\" in";
	$f[]="        0|1) log_end_msg 0 ;;";
	$f[]="        2) log_end_msg 1 ;;";
	$f[]="    esac";
	$f[]="    ;;";
	$f[]="  stop)";
	$f[]="    do_stop";
	$f[]="    case \"$?\" in";
	$f[]="        0) log_end_msg 0 ;;";
	$f[]="        1) log_end_msg 1 ;;";
	$f[]="    esac";
	$f[]="    ;;";
	$f[]="  status)";
	$f[]="    status_of_proc -p \"\$PIDFILE\" \"\$DAEMON\" \"\$NAME\"";
	$f[]="    ;;";
	$f[]="  restart|force-reload)";
	$f[]="    do_stop";
	$f[]="    case \"$?\" in";
	$f[]="      0)";
	$f[]="        log_end_msg 0";
	$f[]="        do_start";
	$f[]="        case \"$?\" in";
	$f[]="            0) log_end_msg 0 ;;";
	$f[]="            *) log_end_msg 1 ;; # Failed to start";
	$f[]="        esac";
	$f[]="        ;;";
	$f[]="      *)";
	$f[]="        # Failed to stop";
	$f[]="        if [ \"\$RUN\" != \"true\" ]; then";
	$f[]="            log_action_msg \"disabled by /etc/default/\$NAME\"";
	$f[]="            log_end_msg 0";
	$f[]="        else";
	$f[]="            log_end_msg 1";
	$f[]="        fi";
	$f[]="        ;;";
	$f[]="    esac";
	$f[]="    ;;";
	$f[]="  *)";
	$f[]="    echo \"Usage: invoke-rc.d \$NAME {start|stop|status|restart|force-reload}\" >&2";
	$f[]="    exit 3";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="";
	
	$INITD_PATH="/etc/init.d/glances";
	echo "Glances: [INFO] Writing $INITD_PATH with new config\n";
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

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("glances");

	if(is_file("/usr/local/bin/glances")){
        $Masterbin="/usr/local/bin/glances";
    }

	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, glances not installed\n";}
		return;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		@file_put_contents("/var/run/glances/glances.pid", $pid);
		return;
	}
	$EnableGlances=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableGlances"));
	
	if($EnableGlances==0){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see EnableArpDaemon)\n";}
		return;
	}
	
	
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){ $unix->DEBIAN_INSTALL_PACKAGE("python3-bottle");}

	
	if(!is_file("/usr/lib/python3/dist-packages/bottle.py")){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} python3-bottle no such installed package\n";}
		return;
	}
	
	$php5=$unix->LOCATE_PHP5_BIN();
	$sysctl=$unix->find_program("sysctl");
	$echo=$unix->find_program("echo");
	$nohup=$unix->find_program("nohup");

	
	

	$f[]="$nohup $Masterbin -w --bind 127.0.0.1 --theme-white";
	$cmd=@implode(" ", $f) ." >/dev/null 2>&1 &";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}

	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){
			@file_put_contents("/var/run/glances/glances.pid", $pid);
			break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		@file_put_contents("/var/run/glances/glances.pid", $pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}

	}else{
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";}
	}


}

function stop($aspid=false){
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Already Artica task running PID $pid since {$time}mn\n";}
			return;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service already stopped...\n";}
		return;
	}
	$pid=PID_NUM();
	$nohup=$unix->find_program("nohup");
	$php5=$unix->LOCATE_PHP5_BIN();
	$kill=$unix->find_program("kill");




	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
		return;
	}

	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
	unix_system_kill_force($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
	}

	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
		return;
	}

}