<?php
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["BYSCRIPT"]=false;
$GLOBALS["MONIT"]=false;
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;$GLOBALS["debug"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
if(preg_match("#--script#",implode(" ",$argv),$re)){$GLOBALS["BYSCRIPT"]=true;}
if(preg_match("#--monit#",implode(" ",$argv),$re)){$GLOBALS["MONIT"]=true;}

$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.settings.inc');


	$GLOBALS["ARGVS"]=implode(" ",$argv);
    if($argv[1]=="--migration"){$GLOBALS["OUTPUT"]=true;migration();exit();}
    if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
	if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;migration();exit();}
	if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--reload"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--status"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--monit"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--status2"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--install"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--uninstall"){$GLOBALS["OUTPUT"]=true;uninstall();exit();}
	if($argv[1]=="--sync"){uninstall();exit;}

function LIGHTTPD_PID(){
    $unix=new unix();
    $pid=$unix->get_pid_from_file('/var/run/lighttpd/framework.pid');
    if($unix->process_exists($pid)){return $pid;}
    $lighttpd_bin=$unix->find_program("lighttpd");
    return $unix->PIDOF_PATTERN($lighttpd_bin." -f /etc/artica-postfix/framework.conf");
}
function stop($aspid=false){
    $unix=new unix();



    $pid=LIGHTTPD_PID();
    squid_admin_mysql(1,"Stopping old Artica Framework service...",null,__FILE__,__LINE__);
    if(!$unix->process_exists($pid)){
        if($GLOBALS["OUTPUT"]){echo "Stopping......: ".date("H:i:s")." [INIT]: Framework service already stopped...\n";}
        return;
    }
    $pid=LIGHTTPD_PID();

    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=LIGHTTPD_PID();
        if(!$unix->process_exists($pid)){break;}
        sleep(1);
    }

}
function migration(){
    stop();
    if(is_file("/usr/bin/systemctl")){shell_exec("/usr/bin/systemctl disable artica-framework");}
    if(is_file('/usr/sbin/update-rc.d')){shell_exec("/usr/sbin/update-rc.d -f artica-framework remove >/dev/null 2>&1");}
    if(is_file("/etc/init.d/artica-framework")) {
        @unlink("/etc/init.d/artica-framework");
    }
    shell_exec("/etc/init.d/artica-phpfpm restart");
    if(is_file("/etc/monit/conf.d/APP_FRAMEWORK.monitrc")){
        @unlink("/etc/monit/conf.d/APP_FRAMEWORK.monitrc");
        shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
    }
}




function uninstall(){
    if(!isset($GLOBALS["BYSCRIPT"])) {
        $unix = new unix();
        if (is_file("/etc/init.d/artica-framework")) {
            echo "Removing service artica-framework\n";
            $unix->remove_service("/etc/init.d/artica-framework");
            $unix->framework_exec("exec.php-fpm.php --restart");
        }

        if (is_file("/etc/monit/conf.d/APP_FRAMEWORK.monitrc")) {
            echo "Removing monit service";
            @unlink("/etc/monit/conf.d/APP_FRAMEWORK.monitrc");
            shell_exec("/usr/bin/monit -c /etc/monit/monitrc -p /var/run/monit/monit.pid reload");
        }
    }
	
}