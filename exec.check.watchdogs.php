<?php
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
$GLOBALS["YESCGROUP"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
include_once(dirname(__FILE__).'/ressources/class.monit.inc');
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}

checksall();




function checksall():bool{
    $unix           = new unix();
    $pidfile        = "/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
    $pid            = $unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid,basename(__FILE__))){return false;}
    if(!$GLOBALS["VERBOSE"]) {
        $timexec = $unix->file_time_min($pidfile);
        if ($timexec < 2) {
            echo "NEED AT LEAST 2mn\n";
            return false;
        }
    }
    if(is_file($pidfile)){@unlink($pidfile);}
    @file_put_contents($pidfile, getmypid());

    if($GLOBALS["VERBOSE"]){echo "Check Artica\n";}
    check_artica();
    if($GLOBALS["VERBOSE"]){echo "Check Web API\n";}
    check_ad_rest();
    return true;
}
function check_artica():bool{
    $unix       = new unix();
    $master_pid = check_artica_pid();
    if($unix->process_exists($master_pid)){return true;}
    shell_exec("/etc/init.d/artica-status start");
    return false;

}

function ad_rest_pid():int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/active-directory-rest.pid");
    if($unix->process_exists($pid)){return $pid;}
    $pid=$unix->PIDOF_PATTERN("active-directory-rest.py");
    if($unix->process_exists($pid)){return $pid;}
    $pid=$unix->PIDOF_PATTERN("\[articarest\]");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF("/usr/sbin/articarest");
}

function check_ad_rest():bool{
    $INITD_PATH                 = "/etc/init.d/artica-ad-rest";
    $MONIT_PATH                 = "/etc/monit/conf.d/APP_ACTIVE_DIRECTORY_REST.monitrc";
    $MONIT_WATCHDOG             = "/etc/monit/conf.d/APP_REST_WATCHDOG.monitrc";
    $unix=new unix();
    $NOHUP = $unix->find_program("nohup");

    if(!is_file("/usr/sbin/artica-phpfpm-service")) {
        if (is_file("/usr/share/artica-postfix/bin/articarest")) {
            copy("/usr/share/artica-postfix/bin/articarest", "/usr/sbin/artica-phpfpm-service");
            @chmod("/usr/sbin/artica-phpfpm-service", 0755);
        }
    }


    if(!is_file($INITD_PATH)){
        squid_admin_mysql(0,"REST API Watchdog service not installed [action={install}]",null,__FILE__,__LINE__);
        $unix->ToSyslog("Installing REST API Watchdog service ($INITD_PATH not found)",false,"watchdog-of-watchdog");
        shell_exec("/usr/share/artica-postfix/bin/artwatch -install");
        return true;
    }
    if(!is_file($MONIT_PATH)){
        $unix->ToSyslog("Installing REST API service ($MONIT_PATH not found )",false,"watchdog-of-watchdog");
        squid_admin_mysql(0,"REST API service not installed [action={install}]",null,__FILE__,__LINE__);
        shell_exec("/usr/sbin/artica-phpfpm-service -install-restmonit");
        return true;
    }
    if(!is_file($MONIT_WATCHDOG)){
        $unix->ToSyslog("Installing REST API service ($MONIT_WATCHDOG not found)",false,"watchdog-of-watchdog");
        squid_admin_mysql(0,"REST API Watchdog service not correctly installed [action={install}]",null,__FILE__,__LINE__);
        shell_exec("/usr/share/artica-postfix/bin/artwatch -monit");
        if(is_file("/etc/init.d/artica-ad-watchdog")){
            shell_exec("$NOHUP /etc/init.d/artica-ad-watchdog start >/dev/null 2>&1 &");
        }
        return true;
    }

    if(!is_file("/usr/sbin/artica-phpfpm-service")){
        if(is_file("/usr/share/artica-postfix/bin/articarest")){
            copy("/usr/share/artica-postfix/bin/articarest","/usr/sbin/artica-phpfpm-service");
            @chmod("/usr/sbin/artica-phpfpm-service",0755);
            $unix->ToSyslog("Installing REST API service and start it",false,"watchdog-of-watchdog");
            if (is_file("/etc/systemd/system/artica-ad-rest.service")){
                $systemctl=$unix->find_program("systemctl");
                shell_exec("$systemctl start artica-ad-rest.service");
            }else{
                shell_exec("$NOHUP /usr/sbin/artica-phpfpm-service -start-me >/dev/null 2>&1 &");
            }

            return true;
        }
        return true;
    }


    $master_pid = ad_rest_pid();
    $unix->ToSyslog("Starting REST API service ($MONIT_WATCHDOG not found)",false,"watchdog-of-watchdog");
    if($unix->process_exists($master_pid)){return true;}
    squid_admin_mysql(0,"REST API service not started [action=start]",null,__FILE__,__LINE__);
    shell_exec("/etc/init.d/artica-ad-rest start");
    return true;
}

function check_artica_pid():int{
    $unix       = new unix();
    $php5       = $unix->LOCATE_PHP5_BIN();
    $pidfile    = "/etc/artica-postfix/exec.status.php.pid";
    $pid=$unix->get_pid_from_file($pidfile);
    if($unix->process_exists($pid)){return intval($pid);}
    return intval($unix->PIDOF_PATTERN("$php5\s+". dirname(__FILE__)."/exec.status.php"));

}