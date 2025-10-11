<?php
//SP 125
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/ressources/class.ini.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__)."/framework/frame.class.inc");
include_once(dirname(__FILE__)."/ressources/class.user.inc");
include_once(dirname(__FILE__)."/ressources/class.ldap.inc");
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.maincf.multi.inc');
$GLOBALS["AS_ROOT"]=true;
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="SALS Auth Daemon";
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}


$GLOBALS["ARGVS"]=implode(" ",$argv);
if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
if($argv[1]=="--build"){build();exit();}


function install(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();

    if(is_file("/usr/lib/x86_64-linux-gnu/sasl2/libs.tar.gz")){
        $tar=$unix->find_program("tar");
        shell_exec("$tar xf /usr/lib/x86_64-linux-gnu/sasl2/libs.tar.gz -C /usr/lib/x86_64-linux-gnu/sasl2/");
        @unlink("/usr/lib/x86_64-linux-gnu/sasl2/libs.tar.gz");
        if(is_file("/etc/init.d/slapd")){shell_exec("/etc/init.d/slapd restart");}
    }

    $INITD_PATH="/etc/init.d/saslauthd";
    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:          saslauthd";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: saslauthd daemon";
    $f[]="# chkconfig: 2345 11 89";
    $f[]="# description: Extensible, configurable saslauthd daemon";
    $f[]="### END INIT INFO";
    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="   $php /usr/share/artica-postfix/exec.saslauthd.php --start \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="   $php /usr/share/artica-postfix/exec.saslauthd.php --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="   $php /usr/share/artica-postfix/exec.saslauthd.php --restart \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reload)";
    $f[]="   $php /usr/share/artica-postfix/exec.saslauthd.php --restart \$2 \$3";
    $f[]="	 exit 0";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "saslauthd: [INFO] Writing $INITD_PATH with new config\n";
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
function sasalauthd_enabled():int{
    $EnablePostfix=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix");
    if($EnablePostfix==0){return 0;}
    $main=new maincf_multi();
    $PostFixSmtpSaslEnable=intval($main->GET("PostFixSmtpSaslEnable"));
    $PostfixActiveDirectory=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectory"));
    $EnableActiveDirectoryFeature=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableActiveDirectoryFeature"));
    $cyrus_imapd_installed=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("cyrus_imapd_installed"));
    if($EnableActiveDirectoryFeature==0){$PostfixActiveDirectory=0;}
    if($PostfixActiveDirectory==1){$PostFixSmtpSaslEnable=1;}
    if($cyrus_imapd_installed==1){$PostFixSmtpSaslEnable=1;}
    return $PostFixSmtpSaslEnable;
}

function auto_install(){


    $PostFixSmtpSaslEnable=sasalauthd_enabled();


    if($PostFixSmtpSaslEnable==1){
        if(!is_file("/etc/init.d/saslauthd")){
            squid_admin_mysql(1,"{installing} {APP_SASLAUTHD}",null,__FILE__,__LINE__);
            install();

        }
        return true;
    }

    if($PostFixSmtpSaslEnable==0){
        if(is_file("/etc/init.d/saslauthd")){
            squid_admin_mysql(1,"{uninstalling} {APP_SASLAUTHD}",null,__FILE__,__LINE__);
            uninstall();
        }
        return true;
    }

    return false;

}

function uninstall(){
    remove_service("/etc/init.d/saslauthd");
    $f[]="/etc/default/saslauthd";
    $f[]="/etc/saslauthd.conf";
    $f[]="/usr/local/etc/saslauthd.conf";
    $f[]="/etc/postfix/sasl/smtpd.conf";
    $f[]="/usr/lib/sasl2/smtpd.conf";
    $f[]="/etc/imapd.conf";

    foreach ($f as $file){
        if(is_file($file)){@unlink($file);}
    }
    $f=array();
    $f[]="/usr/lib/x86_64-linux-gnu/sasl2/libldapdb.so";
    $f[]="/usr/lib/x86_64-linux-gnu/sasl2/libldapdb.so.2";
    $f[]="/usr/lib/x86_64-linux-gnu/sasl2/libldapdb.so.2.0.25";

    foreach ($f as $file){
        if(is_file($file)){$t[]=$file;}
    }

    $suffix=@implode(" ",$t);
    if(is_file("/usr/lib/x86_64-linux-gnu/sasl2/libldapdb.so")){
        $unix=new unix();
        $tar=$unix->find_program("tar");
        system("cd /usr/lib/x86_64-linux-gnu/sasl2");
        chdir("/usr/lib/x86_64-linux-gnu/sasl2");
        shell_exec("$tar -czf /usr/lib/x86_64-linux-gnu/sasl2/libs.tar.gz $suffix");

        foreach ($f as $path){
            if(is_file($path)){@unlink($path);}
        }

        if(is_file("/etc/init.d/slapd")){
            shell_exec("/etc/init.d/slapd restart");
        }

    }



}

function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");

    }

    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function restart() {
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Already Artica task running PID $pid since {$time}mn\n";}
        build_progress(110,"Already Artica task running $pid");
		return;
	}
	@file_put_contents($pidfile, getmypid());
    build_progress(20,"{stopping_service}");
	stop(true);
	sleep(1);
    build_progress(50,"{reconfiguring}");
	build();
    build_progress(80,"{starting_service}");
	start(true);
    build_progress(100,"{starting_service} {done}");

}

function start($aspid=false){
	$unix=new unix();
	$sock=new sockets();
	$Masterbin=$unix->find_program("saslauthd");
	$instances=5;
	if(!is_file($Masterbin)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}, saslauthd not installed\n";}
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

	if($unix->MEM_TOTAL_INSTALLEE()<624288){
		$instances=2;
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} not enough memory instances = $instances\n";}
		return;
	}
	
	$EnableDaemon=sasalauthd_enabled();



	if($EnableDaemon==0){
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service disabled (see Postfix/Cyrus)\n";
		stop();
		return;
	}



	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Service already started $pid since {$timepid}Mn...\n";}
		return;
	}


	$ln=$unix->find_program("ln");
	$chmod=$unix->find_program("chmod");
	$EnableVirtualDomainsInMailBoxes=$sock->GET_INFO("EnableVirtualDomainsInMailBoxes");
	$SaslAuthdConfigured=$sock->GET_INFO("SaslAuthdConfigured");
	$CyrusToAD=$sock->GET_INFO("CyrusToAD");
	if(!is_numeric($EnableVirtualDomainsInMailBoxes)){$EnableVirtualDomainsInMailBoxes=0;}
	if(!is_numeric($SaslAuthdConfigured)){$SaslAuthdConfigured=0;}
	if(!is_numeric($CyrusToAD)){$CyrusToAD=0;}

	
	@mkdir("/var/run/saslauthd",0755,true);
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} EnableVirtualDomainsInMailBoxes = $EnableVirtualDomainsInMailBoxes\n";}
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CyrusToAD = $CyrusToAD\n";}	
	
	$mech="ldap";
	if($EnableVirtualDomainsInMailBoxes==1){
		$moinsr='-r ';
	}
	
	if($CyrusToAD==1){
		$mech='pam';
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} saslauthd enable pam authentifications\n";}
		shell_exec($unix->LOCATE_PHP5_BIN()+' /usr/share/artica-postfix/exec.cyrus.php --kinit >/dev/null 2>&1');
	}
	
	if(!$SaslAuthdConfigured){build();$sock->SET_INFO("SaslAuthdConfigured",1);}
	
	$cmd="$Masterbin $moinsr -a $mech -c -m /var/run/saslauthd -n $instances -V";
	if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service\n";}
	shell_exec($cmd);




	for($i=1;$i<5;$i++){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} waiting $i/5\n";}
		sleep(1);
		$pid=PID_NUM();
		if($unix->process_exists($pid)){break;}
	}

	$pid=PID_NUM();
	if($unix->process_exists($pid)){
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Success PID $pid\n";}
		if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} symlink from /var/run/saslauthd to /var/run/sasl2\n";}
		shell_exec("$ln -sf /var/run/saslauthd /var/run/sasl2 >/dev/null 2>&1");
		@mkdir('/var/spool/postfix/var',0755,true);
		shell_exec("$ln -sf /var/run /var/spool/postfix/var/run >/dev/null 2>&1");
		shell_exec("$chmod 0755 /var/run/saslauthd >/dev/null 2>&1");
		shell_exec("$chmod 0777 /var/run/saslauthd/* >/dev/null 2>&1");
		
		

	}else{
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Failed\n";
		echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} $cmd\n";
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
	if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service Shutdown pid $pid...\n";}
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} service waiting pid:$pid $i/5...\n";}
		sleep(1);
		unix_system_kill_force($pid);
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

function PID_NUM(){
	$unix=new unix();
	$pid=$unix->get_pid_from_file(PID_PATH());
	if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("saslauthd");
	return $unix->PIDOF($Masterbin);

}
//#########################################################################################
function PID_PATH(){
	if(is_file('/var/run/saslauthd/saslauthd.pid')){return('/var/run/saslauthd/saslauthd.pid');}
	if(is_file('/var/run/saslauthd.pid')){return('/var/run/saslauthd.pid');}
	if(is_file('/var/run/saslauthd/saslauthd.pid')){return('/var/run/saslauthd/saslauthd.pid');}
}
//#########################################################################################
function saslauthd_conf(){
	if(is_file("/etc/saslauthd.conf")){return "/etc/saslauthd.conf";}
	if(is_file("/usr/local/etc/saslauthd.conf")){return "/usr/local/etc/saslauthd.conf";}
	return "/etc/saslauthd.conf";


}

function mech_list() {

    $EnableMechLogin=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechLogin");
    $EnableMechPlain=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechPlain");
    $EnableMechDigestMD5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechDigestMD5");
    $EnableMechCramMD5=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableMechCramMD5");
    if(!is_numeric($EnableMechLogin)){$EnableMechLogin=1;}
    if(!is_numeric($EnableMechPlain)){$EnableMechPlain=1;}

    if(!is_numeric($EnableMechDigestMD5)){$EnableMechDigestMD5=0;}
    if(!is_numeric($EnableMechCramMD5)){$EnableMechCramMD5=0;}

    if($EnableMechLogin==1){$mech_list[]="LOGIN";}
    if($EnableMechPlain==1){$mech_list[]="PLAIN";}
    if($EnableMechDigestMD5==1){$mech_list[]="DIGEST-MD5";}
    if($EnableMechCramMD5==1){$mech_list[]="CRAM-MD5";}

    return @implode(" ", $mech_list);

}

function build_progress($prc,$txt){
    $unix=new unix();
    $unix->framework_progress($prc,$txt,"saslauth.progress");
}

function build_active_directory(){
    $unix=new unix();
    $PostfixActiveDirectoryCNX=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectoryCNX"));
    $ActiveDirectoryConnections=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ActiveDirectoryConnections"));
    $HASH=$ActiveDirectoryConnections[$PostfixActiveDirectoryCNX];

    if (!isset($HASH["LDAP_PORT"])) {$HASH["LDAP_PORT"] = 389;}
    if (!isset($HASH["LDAP_SSL"])) {$HASH["LDAP_SSL"] = 0;}
    if($HASH["LDAP_SSL"]==1){$HASH["LDAP_PORT"]=636;}
    if($HASH["LDAP_PORT"]==636){
        $HASH["LDAP_SSL"]=1;
    }
    if( $HASH["LDAP_PORT"] == 389 ){$HASH["LDAP_PORT"] = 0;}
    if( $HASH["LDAP_PORT"] == 636 ){$HASH["LDAP_PORT"] = 0;}
    $host="{$HASH["LDAP_SERVER"]}";
    if(isset($HASH["ADNETIPADDR"])){
        if($HASH["ADNETIPADDR"]<>null){$host=$HASH["ADNETIPADDR"];}
    }


    $dnuser=$HASH["LDAP_DN"];
    $dnpassword=$HASH["LDAP_PASSWORD"];
    $suffix=$HASH["LDAP_SUFFIX"];
    if($HASH["LDAP_PORT"]>0){
        $host="{$HASH["LDAP_SERVER"]}:{$HASH["LDAP_PORT"]}";
    }

    $proto="ldap";
    if($HASH["LDAP_SSL"]==1){$proto="ldaps";}
    $mechlist=mech_list();
    $f[]="pwcheck_method: saslauthd";
    $f[]="mech_list: plain login";
    $f[]="";
    $f[]="ldap_servers: $proto://$host";
    $f[]="ldap_search_base: $suffix";
    $f[]="ldap_timeout: 10";
    $f[]="ldap_filter: sAMAccountName=%U";
    $f[]="ldap_bind_dn: $dnuser";
    $f[]="ldap_password: $dnpassword";
    $f[]="ldap_deref: never";
    $f[]="ldap_restart: yes";
    $f[]="ldap_scope: sub";
    $f[]="ldap_use_sasl: no";
    $f[]="ldap_start_tls: no";
    $f[]="ldap_version: 3";
    $f[]="ldap_auth_method: bind";
    $f[]="mech_list: $mechlist";
    $f[]="minimum_layer: 0";
    $f[]="log_level: 10";
    $f[]="ldap_debug: 9";
    $f[]="";
    if(!is_dir("/etc/postfix/sasl")) {
        @mkdir('/etc/postfix/sasl', 0755);
    }
    $tfiles[]="/etc/postfix/sasl/smtpd.conf";
    $tfiles[]="/etc/saslauthd.conf";
    foreach ($tfiles as $fname) {
        echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} $fname [Active Directory]\n";
        @file_put_contents($fname, @implode("\n", $f));
    }
    if(!is_file("/usr/lib/sasl2/smtpd.conf")){
        $ln=$unix->find_program("ln");
        shell_exec("$ln -s /etc/postfix/sasl/smtpd.conf  /usr/lib/sasl2/smtpd.conf >/dev/null 2>&1");
    }
    return true;
}



function build(){

	$unix=new unix();
    $PostfixActiveDirectory=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("PostfixActiveDirectory");
    if($PostfixActiveDirectory==1){
        return build_active_directory();
    }
    auto_install();
    $mechlist=mech_list();

	$ldap=new clladp();
	$conf[]="ldap_servers: ldap://$ldap->ldap_host:$ldap->ldap_port/";
	$conf[]="ldap_version: 3";
	$conf[]="ldap_search_base: dc=organizations,$ldap->suffix";
	$conf[]="ldap_scope: sub";
	$conf[]="ldap_filter: uid=%u";
	$conf[]="ldap_auth_method: bind";
	$conf[]="ldap_bind_dn: cn=$ldap->ldap_admin,$ldap->suffix";
	$conf[]="ldap_password: $ldap->ldap_password";
	$conf[]="ldap_timeout: 10";
	$conf[]="";
	echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} ldap://$ldap->ldap_host:$ldap->ldap_port\n";
	@file_put_contents(saslauthd_conf(),@implode("\n",$conf));	
	echo "Stopping......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]}  $mechlist\n";
	
	$f[]="pwcheck_method: saslauthd";
	$f[]="mech_list: $mechlist";
	$f[]="minimum_layer: 0";
	$f[]="log_level: 5";

    if(!is_dir("/etc/postfix/sasl")) {
        @mkdir('/etc/postfix/sasl', 0755);
    }
    $tfiles[]="/etc/postfix/sasl/smtpd.conf";
    $tfiles[]="/etc/saslauthd.conf";
    foreach ($tfiles as $fname) {
        echo "Starting......: " . date("H:i:s") . " [INIT]: {$GLOBALS["TITLENAME"]} $fname [LDAP]\n";
        @file_put_contents($fname, @implode("\n", $f));
    }

	if(!is_file("/usr/lib/sasl2/smtpd.conf")){
		$ln=$unix->find_program("ln"); 
		shell_exec("$ln -s /etc/postfix/sasl/smtpd.conf  /usr/lib/sasl2/smtpd.conf >/dev/null 2>&1"); 
	}
	
	
	return true;
	
}
