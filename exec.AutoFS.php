<?php
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NORELOAD"]=false;
$GLOBALS["PROGRESS"]=false;
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
	if($GLOBALS["VERBOSE"]){ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);}
}

if(isset($argv[1])){
    if($argv[1]=="--reload"){reload_progress();exit();}
    if($argv[1]=="--uninstall"){uninstall_service();exit();}
    if($argv[1]=="--install"){install_service();exit();}
    if($argv[1]=="--count"){Autocount();exit();}
    if($argv[1]=="--davfs"){davfs();exit();}
    if($argv[1]=="--default"){autofs_default();exit();}
    if($argv[1]=="--checks"){Checks();exit();}
    if($argv[1]=="--restart-progress"){$GLOBALS["PROGRESS"]=true;ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);restart_progress();exit();}

    if($argv[1]=="--reconfigure"){reconfigure();exit();}
    if($argv[1]=="--start"){start();exit();}
    if($argv[1]=="--stop"){stop();exit();}
    if($argv[1]=="--restart"){restart_progress();exit();}

}


function build_progress_install($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"autofs.install.progress");
    return true;
}
function reconfigure():bool{
    create_service();

    $unix=new unix();
    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        _out("Reloading $pid");
        unix_system_HUP($pid);
        return true;
    }
    return false;

}


function reload_progress():bool{
	$GLOBALS["PROGRESS"]=true;
	build_progress_rs("{checking_configuration}",10);
	build_progress_rs("{configuring}",20);
	Checks();
	build_progress_rs("{reloading_service}",35);
	system("/etc/init.d/autofs restart");
	build_progress_rs("{done}",100);
    return true;
}






function _out($text):bool{
    echo date("H:i:s")." [INIT]: AutoFS $text\n";
    $LOG_SEV = LOG_INFO;
    if (!function_exists("openlog")) {return false;}
    openlog("automount", LOG_PID, LOG_SYSLOG);
    syslog($LOG_SEV, $text);
    closelog();
    return true;
}

function PID_NUM():int{
    $pid_path=null;
    $unix=new unix();
    $master_pid=0;
    if (is_file("/var/run/autofs-running")) {
        $pid_path = "/var/run/autofs-running";
    }
    if ($pid_path == null) {
        if (is_file("/var/run/automount.pid")) {
            $pid_path = "/var/run/automount.pid";
        }
    }

    if ($pid_path <> null) {
        $master_pid = $unix->get_pid_from_file($pid_path);
    }

    if($unix->process_exists($master_pid)){return $master_pid;}

    $binpath=$unix->find_program("automount");
    $master_pid=$unix->PIDOF($binpath);
    if($unix->process_exists($master_pid)){return $master_pid;}
    $master_pid=$unix->PIDOF_PATTERN($binpath);
    if($unix->process_exists($master_pid)){return $master_pid;}
    return 0;
}




function reload():bool{
    $unix=new unix();
    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        _out("Reloading PID $pid");
        $unix->KILL_PROCESS($pid,1);
        return true;
    }

    return start(true);
}


function start($aspid=false,$crp=0):bool{
    $unix=new unix();
    $Masterbin=$unix->find_program("automount");
    $AutoFSEnabled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("AutoFSEnabled"));

    if($AutoFSEnabled==0){
        _out("Starting: Fatal, service is not enabled");
        return false;
    }

    if(!$aspid){
        $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
        $pid=$unix->get_pid_from_file($pidfile);
        if($unix->process_exists($pid,basename(__FILE__))){
            $time=$unix->PROCCESS_TIME_MIN($pid);
           _out("Starting: Already Artica task running PID $pid since {$time}mn");
            return false;
        }
        @file_put_contents($pidfile, getmypid());
    }

    $pid=PID_NUM();

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        _out("Starting: Service already started $pid since {$timepid}Mn");
        @file_put_contents("/var/run/automount.pid", $pid);
        return true;
    }
    $Masterbin=$unix->find_program("automount");
    @chmod($Masterbin,0755);
    $tmpfile=$unix->FILE_TEMP();
    $cmd[]=$Masterbin;
    $cmd[]="--pid-file /var/run/automount.pid";
    $cmd[]="--force";
    $cmd[]="--timeout 60";
    $cmd[]="--negative-timeout 30 >$tmpfile 2>&1";

    _out("Starting service");
    $cmdline=@implode(" ",$cmd);
    $sh=$unix->sh_command($cmdline);
   shell_exec($sh);




    for($i=1;$i<5;$i++){
        _out("Starting waiting $i/5");
        sleep(1);
        $pid=PID_NUM();
        if($unix->process_exists($pid)){break;}
    }

    $pid=PID_NUM();
    if($unix->process_exists($pid)){
        _out("Starting Success PID $pid");
        $f=explode("\n",@file_get_contents($tmpfile));
        foreach ($f as $line){
            $line=trim($line);
            if($line==null){continue;}
            _out("Starting $line");
        }
        return true;
    }

    _out("Starting failed: $cmdline");
    $f=explode("\n",@file_get_contents($tmpfile));
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out("Starting failed: $line");
    }
    return false;
}

function stop($aspid=false,$crp=0):bool{
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
        build_progress_rs("{stopping_service}...",60);
        _out("service already stopped...");
        return true;
    }
    $pid=PID_NUM();

    if($GLOBALS["MONIT"]){
        $pid=PID_NUM();
        if($unix->process_exists($pid)){
            @file_put_contents("/var/run/automount.pid", $pid);
            return true;
        }
    }


    _out("Stopping service Shutdown pid $pid..");
    unix_system_kill($pid);

    if($GLOBALS["FORCE"]){unix_system_kill_force($pid);}
    for($i=0;$i<5;$i++){
        $crp++;
        build_progress_rs("{stopping_service}...",$crp);
        $pid=PID_NUM();
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    $pid=PID_NUM();
    if(!$unix->process_exists($pid)){
        $crp++;
        build_progress_rs("{stopping_service}...",$crp);
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service success...\n";}
        return true;
    }

    if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service shutdown - force - pid $pid...\n";}
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=PID_NUM();
        $crp++;
        build_progress_rs("{stopping_service}...",$crp);
        if(!$unix->process_exists($pid)){break;}
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
        sleep(1);
    }

    if($unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service failed...\n";}
        return false;
    }
    $crp++;
    build_progress_rs("{stopping_service}...",$crp);
    return true;

}

function sftp($automountInformation):string{
    $autofs=new autofs();
    $unix=new unix();
    if(!isset($GLOBALS["CURLFTP_COUNT"])){$GLOBALS["CURLFTP_COUNT"]=0;}
    $GLOBALS["CURLFTP_COUNT"]=$GLOBALS["CURLFTP_COUNT"]+1;
    $ARRAY=$autofs->parseCommand($automountInformation);
    $Keys=array("HOSTNAME","USERNAME","PASSWORD","USEPROXY",
        "PROXYSERVER","PROXYPORT","PROXYTYPE","PROXYUSERNAME","PROXYPASSWORD","FTPTLS");
    foreach ($Keys as $key){
        if(!isset($ARRAY[$key])){$ARRAY[$key]=null;}

    }


    if(isset($ARRAY["AUTOFS"])){
        if(preg_match("#sftp:(.+)#",$ARRAY["AUTOFS"],$re)){
            $SECOND_ARRAY=$unix->unserializeb64($re[1]);
        }
    }
    foreach ($ARRAY as $key=>$val){
        if($val==null){
            if(isset($SECOND_ARRAY[$key])){
                if($SECOND_ARRAY[$key]<>null){
                    echo "Warning $key is null, using the second backup array...\n";
                    $ARRAY[$key]=$SECOND_ARRAY[$key];
                }
            }
        }
    }

    $opts[]="fuse";
    $HOSTNAME =$ARRAY["HOSTNAME"];
	$USERNAME = trim($ARRAY["USERNAME"]);
	$PASSWORD = $ARRAY["PASSWORD"];
	$USEPROXY = intval($ARRAY["USEPROXY"]);
    $PROXYSERVER=$ARRAY["PROXYSERVER"];
    $PROXYPORT=$ARRAY["PROXYPORT"];
	$PROXYTYPE=$ARRAY["PROXYTYPE"];
    $FTPTLS=intval($ARRAY["FTPTLS"]);

    $PROXYUSERNAME=$ARRAY["PROXYUSERNAME"];
    $PROXYPASSWORD=$ARRAY["PROXYPASSWORD"];

    $USERNAME=str_replace("@","%40",$USERNAME);
    $USERNAME=str_replace("$","%24",$USERNAME);

    $PASSWORD=str_replace("@","%40",$PASSWORD);
    $PASSWORD=str_replace("$","%24",$PASSWORD);
    //$PASSWORD=$unix->shellEscapeChars($PASSWORD);
    $PROXYPASSWORD=$unix->shellEscapeChars($PROXYPASSWORD);
    if(!isset($ARRAY["FTPSSL"])){$ARRAY["FTPSSL"]=0;}

    $proto="ftp";
    if($ARRAY["FTPSSL"]==1){
        $FTPTLS=1;
        $proto="ftps";
    }

    $host[]="$proto://";

    if($USERNAME<>null){
        $host[]="$USERNAME\:$PASSWORD\@";

    }
    $host[]=$HOSTNAME;


    if($USEPROXY==0){
        $opts[]="proxy=";
    }else{
        $opts[]="proxy=$PROXYSERVER:$PROXYPORT -o $PROXYTYPE";
        if($PROXYUSERNAME<>null){
            $opts[]="proxy_user=$PROXYUSERNAME:$PROXYPASSWORD";
        }
    }



    $opts[]="connect_timeout=3";
    if($FTPTLS==1) {
        $opts[] = "ssl_try";
    }
    $opts[]="no_verify_hostname,no_verify_peer,allow_other";



    $fstype=@implode(",",$opts);

    return "-fstype=$fstype :curlftpfs\#".@implode("",$host);

}

function sshfs($automountInformation):string{
    $unix=new unix();
    if(!isset($GLOBALS["SSHFS_COUNT"])){$GLOBALS["SSHFS_COUNT"]=0;}
    $autofs=new autofs();
    $ARRAY=$autofs->parseCommand($automountInformation);
    $GLOBALS["SSHFS_COUNT"]=$GLOBALS["SSHFS_COUNT"]+1;
    if(!is_dir("/etc/sshfs")){@mkdir("/etc/sshfs",true);}
    $sshfcount=$GLOBALS["SSHFS_COUNT"];
    $config="/etc/sshfs/sshfs.$sshfcount.conf";
    $IdentityFile="/etc/sshfs/sshfs.$sshfcount.pub";
    $mount_file="/sbin/mount.sshfs.$sshfcount";
    $SSH_SERVER=$ARRAY["SSH_SERVER"];
    $SSH_PORT=intval($ARRAY["SSH_PORT"]);
    if($SSH_PORT==0){$SSH_PORT=22;}
    if(!isset($ARRAY["SSH_USER"])){
        $ARRAY["SSH_USER"]="root";
    }
    if($ARRAY["SSH_USER"]==null){$ARRAY["SSH_USER"]="root";}
    $SSH_REMOTE_PATH= $ARRAY["SSH_REMOTE_PATH"];
    $PRIVKEY=$ARRAY["PRIVKEY"];
    $SSH_USER=$ARRAY["SSH_USER"];

    _out("sshfs $SSH_USER@$SSH_SERVER:$SSH_PORT $SSH_REMOTE_PATH");
    if(strlen($PRIVKEY)<50){
        _out("Error: sshfs No prive key, aborting");
        return "";
    }

    $f[]="Host $SSH_SERVER";
    $f[]="\tPort $SSH_PORT";
    $f[]="\tHostName $SSH_SERVER";
    $f[]="\tUser $SSH_USER";
    $f[]="\tPubKeyAuthentication yes";
    $f[]="\tIdentityFile $IdentityFile";
    $f[]="";
    @file_put_contents($IdentityFile,$PRIVKEY."\n");
    @chmod($IdentityFile,0600);
    @file_put_contents($config,@implode("\n",$f));
    $f=array();
    $sshfs=$unix->find_program("sshfs");
    $f[]="#!/bin/sh";
    $f[]="TARGET=$2";
    //$f[]="\t/usr/bin/logger -i -t automount \"sshfs:$SSH_SERVER to target \$TARGET\"";
    $f[]="return=`$sshfs -o reconnect -o compression=yes -F $config $SSH_SERVER:$SSH_REMOTE_PATH \$TARGET`";

    $f[]="if [ \${#return} -gt 0 ]; then";
    $f[]="\t/usr/bin/logger -i -t automount \"sshfs:$SSH_SERVER \$return\"";
    $f[]="fi";
    $f[]="";
    $f[]="";
    @file_put_contents($mount_file,@implode("\n",$f));
    @chmod($mount_file,0755);
    return "-fstype=sshfs.$sshfcount,rw,nodev,nonempty,noatime,allow_other,max_read=65536 :$SSH_REMOTE_PATH";
}











function install_service(){
	build_progress_install("{enable_feature}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AutoFSEnabled", 1);
	build_progress_install("{install_service}",30);
	create_service();
	Autocount();
    build_tables();
	build_progress_install("{restarting_service}",50);
	restart_progress();
	build_progress_install("{restarting_service}",80);
	system("/etc/init.d/artica-status restart --force");
	
	build_progress_install("{success}",100);
}



function  uninstall_service(){
    $unix=new unix();
	build_progress_install("{disable_feature}",20);
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("AutoFSEnabled", 0);
	build_progress_install("{uninstall_service}",30);
	$unix->remove_service("/etc/init.d/autofs");
	build_progress_install("{restarting_service}",80);
	system("/etc/init.d/artica-status restart --force");
	if(is_file("/etc/monit/conf.d/APP_AUTOFS.monitrc")){
		@unlink("/etc/monit/conf.d/APP_AUTOFS.monitrc");
		shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
	}
	@unlink("/home/artica/SQLITE/autofs.db");
	build_progress_install("{success}",100);
	
}





function create_service():bool{
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/autofs";
    $php5script=basename(__FILE__);
    $daemonbinLog="Automount Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         autofs";
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
    $f[]=" reload)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
    $f[]="    ;;";
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

    $md51=null;
    if(is_file($INITD_PATH)){
        $md51=md5_file($INITD_PATH);
    }

    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);
    $md52=md5_file($INITD_PATH);

    if($md51==$md52){
        return true;
    }

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }

    $BaseNameInit=basename($INITD_PATH);
    $systemdFile="/etc/systemd/system/$BaseNameInit.service";
    $systemd[]="[Unit]";
    $systemd[]="Description=$BaseNameInit";
    $systemd[]="DefaultDependencies=no";
    $systemd[]="";
    $systemd[]="[Service]";
    $systemd[]="Restart=no";
    $systemd[]="PIDFile=/var/run/automount.pid";
    $systemd[]="ExecStart=$php /usr/share/artica-postfix/$php5script --start";
    $systemd[]="ExecStop=$php /usr/share/artica-postfix/$php5script --stop";
    $systemd[]="";
    $systemd[]="[Install]";
    $systemd[]="WantedBy=default.target";
    @file_put_contents($systemdFile,@implode("\n",$systemd));
    if(is_file("/usr/bin/systemctl")){
        shell_exec("/usr/bin/systemctl enable $BaseNameInit");
    }

    return true;


}




?>