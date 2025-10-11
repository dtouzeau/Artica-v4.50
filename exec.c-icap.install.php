<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.ccurl.inc');
$GLOBALS["WITH_CLAMAV"]=false;

if(preg_match("#--with-clamav#",@implode("",$argv))){
    $GLOBALS["WITH_CLAMAV"]=true;
}

if($argv[1]=="--install"){install();exit;}
if($argv[1]=="--migration"){migration();exit;}
if($argv[1]=="--uninstall"){uninstall();exit;}
if($argv[1]=="--connect"){connect();exit;}
if($argv[1]=="--disconnect"){disconnect();exit;}
if($argv[1]=="--monit"){install_monit();exit;}
if($argv[1]=="--check-port"){check_port();exit;}
if($argv[1]=="--cicap-checks"){c_icap_checks();}
if($argv[1]=="--kwts-install"){kwts_install();exit;}
if($argv[1]=="--kwts-uninstall"){kwts_uninstall();exit;}
if($argv[1]=="--kwts-check"){kwts_checks();exit;}
if($argv[1]=="--kwts-monit"){kwts_monit();exit;}
if($argv[1]=="--install-clamav"){install_clamav();}
if($argv[1]=="--uninstall-clamav"){uninstall_clamav();}
if($argv[1]=="--install-sandbox"){install_sandbox();}
if($argv[1]=="--uninstall-sandbox"){uninstall_sandbox();}
if($argv[1]=="--syslog-sandbox"){install_sandbox_syslog();exit;}



function kwts_install_progress($prc,$text):bool{
    $unix=new unix();
    $unix->framework_progress($prc,$text,"kwts.progress");
    return true;
}

function kwts_install(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KWTSEnabled", 1);
    kwts_install_progress(20,"{reconfiguring}");
    kwts_monit();
    kwts_install_progress(50,"{reconfiguring}");
    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
    kwts_install_progress(100,"{done}");
}

function kwts_monit(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $mfile  = "/etc/monit/conf.d/APP_KWTS_ICAP.monitrc";
    $KWTSIPAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSIPAddr");
    if($KWTSIPAddr==null){return false;}
    $KWTSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSPort"));
    $KWTSReqMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSReqMode"));
    if(is_file($mfile)){@unlink($mfile);}
    $f[]="check host APP_KWTS_ICAP with address $KWTSIPAddr";
    $f[]="\tif failed";
    $f[]="\t\tport $KWTSPort";
    $f[]="\t\ttype TCP and";
    $f[]="\t\tsend \"OPTIONS icap://$KWTSIPAddr:$KWTSPort/$KWTSReqMode ICAP/1.0\\n\\n\"";
    $f[]="\t\texpect \"200 OK\"";
    $f[]="\t\tsend \"QUIT\\n\"";
    $f[]="\tthen exec \"/usr/sbin/kwts-monit.sh\"";
    $f[]="";
    @file_put_contents($mfile,@implode("\n",$f));

    $sh[]="#!/bin/sh";
    $sh[]="$php ".__FILE__." --kwts-check";
    $sh[]="exit 0";
    @file_put_contents("/usr/sbin/kwts-monit.sh",@implode("\n",$sh));
    @chmod("/usr/sbin/kwts-monit.sh",0755);
    shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    return true;
}

function kwts_uninstall(){
    $unix   = new unix();
    $php    = $unix->LOCATE_PHP5_BIN();
    $mfile  = "/etc/monit/conf.d/APP_KWTS_ICAP.monitrc";
    kwts_install_progress(20,"{reconfiguring}");

    if(is_file($mfile)){
        @unlink($mfile);
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
    if(!is_file("/usr/sbin/kwts-monit.sh")){@unlink("/usr/sbin/kwts-monit.sh");}
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KWTSEnabled", 0);
    kwts_install_progress(50,"{reconfiguring}");
    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
    kwts_install_progress(100,"{done}");
    //c-icap-client -i 192.168.1.34 -p 1344 -s av/reqmod -method REQMOD -h";
}

function c_icap_local_interface():string{
    $unix               = new unix();
    $CICAPListenInterface = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface");
    if($CICAPListenInterface==null){$CICAPListenInterface="lo";}
    $ListenAddress  = $unix->InterfaceToIPv4($CICAPListenInterface);
    if($ListenAddress==null){$ListenAddress="127.0.0.1";}
    return $ListenAddress;
}

function migration(){
    $unix=new unix();
    $unix->remove_service("/etc/init.d/c-icap-access");
    $unix->remove_service("/etc/init.d/c-icap-watchdog");
    $unix->framework_exec("exec.squid.disable.php --cache-logs");
    $unix->framework_exec("exec.c-icap.php --reconfigure");
}

function c_icap_checks(){
    $unix               = new unix();
    $CICAPListenInterface = $GLOBALS["CLASS_SOCKETS"]->GET_INFO("CICAPListenInterface");
    if($CICAPListenInterface==null){$CICAPListenInterface="lo";}
    $ListenAddress  =   c_icap_local_interface();
    $cicapclient        = $unix->find_program("c-icap-client");
    exec("$cicapclient -i $ListenAddress -p 1345 -s webfilter -method REQMOD 2>&1",$results);

    $MAIN=parse_icap_proto($results);
    if(isset($MAIN["ERROR"])){
        squid_admin_mysql(0,"Connection to ICAP HTTP Security service $ListenAddress:1345 failed (see content)",
            @implode("\n",$results),__FILE__,__LINE__);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CICAPCHK_INFOS",serialize($MAIN));
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CICAPCHK_INFOS",serialize($MAIN));
    return true;

}

function parse_icap_proto($results):array{
    $MAIN=array();
    foreach ($results as $line){
        if(preg_match("#ICAP.*?200 OK#i",$line)){$cnx=true;continue;}
        if(preg_match("#(failed|timedout)#",$line)){
            $MAIN["ERROR"]=$line;
            return $MAIN;
        }
        if(preg_match("#ISTag:(.*)#i",$line,$re)){
            $ISTag=trim($re[1]);
            $ISTag=str_replace('"',"",$ISTag);
            continue;
        }
        if(preg_match("#Service:(.*)#i",$line,$re)){
            $Service=trim($re[1]);
            $Service=str_replace('"',"",$Service);
        }

    }

    $MAIN["SERVICE"]=$Service;
    $MAIN["ISTag"]=$ISTag;
    return $MAIN;
}

function kwts_checks(){
    $unix=new unix();
    $KWTSIPAddr=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSIPAddr");
    if($KWTSIPAddr==null){return false;}
    $KWTSPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSPort"));
    $KWTSReqMode=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("KWTSReqMode"));
    $cicapclient=$unix->find_program("c-icap-client");
    exec("$cicapclient -i $KWTSIPAddr -p $KWTSPort -s $KWTSReqMode -method REQMOD 2>&1",$results);

    $MAIN=parse_icap_proto($results);
    if(isset($MAIN["ERROR"])){
        squid_admin_mysql(0,"Connection to Kaspersky Web traffic Security $KWTSIPAddr:$KWTSPort failed (see content)",
            @implode("\n",$results),__FILE__,__LINE__);
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("KWTS_INFOS",serialize($MAIN));
    return true;
}

function uninstall_clamav(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavInCiCap", 0);
    build_progress_install("{uninstall_service} 3/3",30);
    system("/usr/sbin/artica-phpfpm-service -uninstall-clamd");
    $unix->remove_service("/etc/init.d/clamav-daemon");
    build_progress_install("{uninstall_service} 3/3",40);
    $unix->remove_service("/etc/init.d/clamav-freshclam");
    build_progress_install("{uninstall_service} 3/3",50);
    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
    build_progress_install("{uninstall_service} {done}",100);
}

function install_clamav(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavInCiCap", 1);
    build_progress_install("{install_service} 1/3",10);
    system("/usr/sbin/artica-phpfpm-service -install-clamd");
    clamav_daemon();

    build_progress_install("{install_service} 2/3",30);
    clamav_freshclam();
    build_progress_install("{install_service} 3/3",50);
    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
    build_progress_install("{install_service} {done}",100);
}
function install_sandbox(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CICAPEnableSandBox", 1);
    build_progress_install("{install_service} 1/3",10);
    shell_exec("$php /usr/share/artica-postfix/exec.c-icap.php --reconfigure");
    build_progress_install("{install_service} 2/3",30);
    install_sandbox_syslog();
    build_progress_install("{install_service} 3/3",50);
    $unix->Popuplate_cron_make("icap-sandbox","* * * * *","exec.c-icap.sandbox.php");
    $unix->Popuplate_cron_make("icap-sbclean","* */4 * * *","exec.c-icap.sandbox.php --clean");
    UNIX_RESTART_CRON();

    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
    build_progress_install("{install_service} {done}",100);
}
function install_sandbox_syslog(){
    $tfile      = "/etc/rsyslog.d/cicap_sandbox.conf";
    $oldmd      = md5_file($tfile);
    echo "$tfile [$oldmd]\n";
    $rules_icap = BuildRemoteSyslogs("icap-sandbox");
    $action=buildlocalsyslogfile("/var/log/squid/icap-sandbox.log");


    $h[]="# Saved on ".date("Y-m-d H:i:s");
    $h[]="if  (\$programname =='ArticaSandBox') then {";
    $h[]="\t$action";
    $h[]="\t$rules_icap";
    $h[]="\t\t& stop";
    $h[]="}";
    $h[]="";
    @file_put_contents($tfile,@implode("\n",$h));
    $newmd      = md5_file($tfile);
    echo "$tfile [OK]\n";
    if($oldmd<>$newmd) {
        $unix=new unix();$unix->RESTART_SYSLOG(true);
    }


}



function uninstall_sandbox(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $rm=$unix->find_program("rm");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CICAPEnableSandBox", 0);
    build_progress_install("{uninstall_service} 1/3",10);
    shell_exec("$php /usr/share/artica-postfix/exec.c-icap.php --reconfigure");
    if(is_file("/etc/cron.d/icap-sandbox")){@unlink("/etc/cron.d/icap-sandbox");}
    if(is_file("/etc/cron.d/icap-sbclean")){@unlink("/etc/cron.d/icap-sbclean");}

    UNIX_RESTART_CRON();
    if(is_dir("/home/artica/squid/sandbox")) {
        shell_exec("$rm -rf /home/artica/squid/sandbox/*");
    }

    build_progress_install("{uninstall_service} 2/3",30);
    build_progress_install("{uninstall_service} 3/3",50);
    shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
    build_progress_install("{uninstall_service} {done}",100);
    $q=new postgres_sql();
    $q->QUERY_SQL("DROP TABLE cicap_sandbox");
}



function install():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled", 1);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableeCapClamav", 0);
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();

    if($GLOBALS["WITH_CLAMAV"]){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavInCiCap", 1);
        if(!is_file("/etc/init.d/clamav-daemon")){
            build_progress_install("{installing} {APP_CLAMAV}",10);
            shell_exec("/usr/sbin/artica-phpfpm-service -install-clamd");
        }

    }

    if(is_file("/etc/init.d/clamav-daemon")){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavInCiCap", 1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableClamavInCiCap", 0);
    }

    build_progress_install("{checking_dependencys}",15);
    if(!file_exists("/usr/lib/x86_64-linux-gnu/libbrotlicommon.so.1")){
        shell_exec("/usr/bin/apt-get install libbrotli1");
    }

	build_progress_install("{install_service} 1/5",20);
	install_cicap();

	build_progress_install("{install_service} 4/5",25);
    install_monit();
	
	build_progress_install("{configuring} 1/2",40);
    system("$php /usr/share/artica-postfix/exec.c-icap.php --build");
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=1 WHERE ID=1");
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=2 WHERE ID=2");
	
	build_progress_install("{start_service} 1/6",45);
	if(is_file("/etc/init.d/clamav-daemon")) {
        system("/etc/init.d/clamav-daemon start");
        build_progress_install("{start_service} 2/6", 50);
    }

	build_progress_install("{start_service} 3/6",55);
	system("/etc/init.d/c-icap-access start");
	build_progress_install("{start_service} 4/6",60);
    shell_exec("$php /usr/share/artica-postfix/exec.c-icap.php --reconfigure");
    system("/etc/init.d/c-icap start");
	build_progress_install("{start_service} 5/6",65);
	if(is_file("/etc/init.d/clamav-freshclam")) {
        system("/etc/init.d/clamav-freshclam start");
    }
    $unix->Popuplate_cron_make("cicap-threats","*/2 * * * *",basename(__FILE__)." --threats");
	build_progress_install("{restart_service} 6/6",65);
    $unix->reload_monit();
	build_progress_install("{configuring} 2/2",70);
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php --icap-silent");
	build_progress_install("{done}",100);
	cluster_mode();
    return true;
}

function uninstall_monit(){
	$RELOAD=false;
	if(is_file("/etc/monit/conf.d/APP_CICAP.monitrc")){
		@unlink("/etc/monit/conf.d/APP_CICAP.monitrc");
		$RELOAD=true;
	}
	
	if(is_file("/etc/monit/conf.d/APP_CLAMAV.monitrc")){
		@unlink("/etc/monit/conf.d/APP_CLAMAV.monitrc");
		$RELOAD=true;
	}
	
	if($RELOAD){shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");}
}

function check_port(){

    $ListenAddress=c_icap_local_interface();


    $fp=@fsockopen($ListenAddress, 1345, $errno, $errstr, 5);
    if(!$fp){
        squid_admin_mysql(1,"Local ICAP service failed to listen $ListenAddress:1345 with error $errno $errstr [ {action} = {restart} ]",
            null,__FILE__,__LINE__);
        shell_exec("/etc/init.d/c-icap restart");
        return false;
    }
    fclose($fp);
    return true;
}

function install_monit(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();

    $ListenAddress=c_icap_local_interface();


	@unlink("/etc/monit/conf.d/APP_C_ICAP.monitrc");
	$f[]="check process APP_C_ICAP with pidfile /var/run/c-icap/c-icap.pid";
	$f[]="\tstart program = \"/etc/init.d/c-icap start\"";
    $f[]="\tif failed host $ListenAddress port 1345 then exec \"$php ".__FILE__." --check-port";
	$f[]="";
	@file_put_contents("/etc/monit/conf.d/APP_CICAP.monitrc", @implode("\n", $f));
	

}

function connect(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install("{configuring} 1/2",40);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapClusterConnect", 1);
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=1 WHERE ID=1");
	if(!$q->ok){
		echo $q->mysql_error;
		build_progress_install("{failed2}",110);
		return;
	}
	
	$ligneSQL=$q->mysqli_fetch_array("SELECT `enabled` FROM c_icap_services WHERE ID=1");
	if($ligneSQL["enabled"]==0){
		echo "Enabled == 0 for ID = 1 ???\n";
		build_progress_install("{failed2}",110);
		return;
	}
	echo "Enabled == 1 for ID = 1 [OK]\n";
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=1,zOrder=2 WHERE ID=2");
	build_progress_install("{configuring} 2/2",80);
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php");
	build_progress_install("{done}",100);
	cluster_mode();
}

function disconnect(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install("{configuring} 1/2",40);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapClusterConnect", 0);
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=1");
	if(!$q->ok){
		echo $q->mysql_error;
		build_progress_install("{failed2}",110);
		return;
	}
	
	$ligneSQL=mysqli_fetch_array($q->QUERY_SQL("SELECT `enabled` FROM c_icap_services WHERE ID=1"));
	if($ligneSQL["enabled"]==1){
		echo "Enabled == 0 for ID = 1 ???\n";
		build_progress_install("{failed2}",110);
		return;
	}
	echo "Enabled == 0 for ID = 1 [OK]\n";
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0 WHERE ID=2");
	build_progress_install("{configuring} 2/2",80);
	system("$php /usr/share/artica-postfix/exec.squid.global.access.php");
	build_progress_install("{done}",100);	
	cluster_mode();
	
}





function uninstall(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	build_progress_install("{configuring} 1/2",5);
	$q=new lib_sqlite("/home/artica/SQLITE/proxy.db");
	
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0,zOrder=1 WHERE ID=1");
	$q->QUERY_SQL("UPDATE c_icap_services SET enabled=0,zOrder=2 WHERE ID=2");
	
	$GLOBALS["CLASS_SOCKETS"]->SET_INFO("CicapEnabled", 0);
	
	build_progress_install("{remove_service} 1/5",10);
	remove_service("/etc/init.d/c-icap");
	uninstall_monit();
	build_progress_install("{remove_service} 2/5",20);
	remove_service("/etc/init.d/c-icap-watchdog");
	build_progress_install("{remove_service} 3/5",30);
	remove_service("/etc/init.d/c-icap-access");
	build_progress_install("{remove_service} 4/5",40);
	remove_service("/etc/init.d/clamav-daemon");
	build_progress_install("{remove_service} 5/5",50);
	remove_service("/etc/init.d/clamav-freshclam");


    if(is_file("/etc/cron.d/cicap-threats")){
        @unlink("/etc/cron.d/cicap-threats");
        $unix->go_exec("/etc/init.d/cron reload");
    }

	build_progress_install("{configuring} 2/2",70);
    if(is_file("/etc/init.d/squid")) {
        system("$php /usr/share/artica-postfix/exec.squid.php --build --force");
    }
	build_progress_install("{done}",100);
	cluster_mode();
}

function build_progress_install($text,$pourc):bool{
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"cicap.install.progress");
    return true;
}


function remove_service($INITD_PATH){
	if(!is_file($INITD_PATH)){return;}
	system("$INITD_PATH stop");
	if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");}
	if(is_file('/sbin/chkconfig')){shell_exec("/sbin/chkconfig --del " .basename($INITD_PATH)." >/dev/null 2>&1");}
	if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}

function install_cicap():bool{
	$unix=new unix();
	$binpath=$unix->find_program("c-icap");
	$INITD_PATH="/etc/init.d/c-icap";

	if(!is_file($binpath)){
		if(is_file($INITD_PATH)){
			if(is_file('/usr/sbin/update-rc.d')){
				shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." remove >/dev/null 2>&1");
			}
				
			if(is_file('/sbin/chkconfig')){
				shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
				shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." off >/dev/null 2>&1");
			}
			@unlink($INITD_PATH);
		}

	}


	$php=$unix->LOCATE_PHP5_BIN();

	$INITD_PATH="/etc/init.d/c-icap";
	$php5script="exec.c-icap.php";
	$daemonbinLog="C-ICAP For Artica";
	$daemon_path=$unix->find_program("nginx");


	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:         artica-cicap";
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
	$f[]=" reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure|reload} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


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
    return true;
}


function clamav_daemon(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/clamav-daemon";
	$php5script="exec.clamd.php";
	$daemonbinLog="Clam AntiVirus userspace daemon";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        clamav-daemon";
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
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --force-reload \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-database)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-database \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-log)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-log \$2 \$3";
	$f[]="    ;;";

	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: $INITD_PATH {start|stop|restart|force-reload|reload-log|reload-database|status} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


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

function clamav_freshclam(){
	$unix=new unix();
	$php=$unix->LOCATE_PHP5_BIN();
	$INITD_PATH="/etc/init.d/clamav-freshclam";
	$php5script="exec.freshclam.php";
	$daemonbinLog="Clam AntiVirus userspace daemon";
	$Provides="clamav-freshclam";
	$daemonbinLog="Clam AntiVirus virus database updater";



	$f[]="#!/bin/sh";
	$f[]="### BEGIN INIT INFO";
	$f[]="# Provides:        $Provides";
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
	$f[]="  skip)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
	$f[]="    ;;";
	$f[]="  status)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" restart)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
	$f[]="    ;;";
	$f[]="";
	$f[]=" reconfigure)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" build)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
	$f[]="    ;;";
	$f[]=" force-reload)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --force-reload \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-database)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-database \$2 \$3";
	$f[]="    ;;";
	$f[]=" reload-log)";
	$f[]="    $php /usr/share/artica-postfix/$php5script --reload-log \$2 \$3";
	$f[]="    ;;";

	$f[]="";
	$f[]="  *)";
	$f[]="    echo \"Usage: $INITD_PATH {no-daemon|start|stop|restart|force-reload|reload-log|skip|status} (+ '--verbose' for more infos)\"";
	$f[]="    exit 1";
	$f[]="    ;;";
	$f[]="esac";
	$f[]="exit 0\n";


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