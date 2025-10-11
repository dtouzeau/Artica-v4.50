<?php
// SP 127
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/ressources/class.user.inc');
include_once(dirname(__FILE__) . '/ressources/class.ini.inc');
include_once(dirname(__FILE__) . '/ressources/class.strongswan.inc');
include_once(dirname(__FILE__) . '/ressources/class.mysql.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/ressources/class.system.network.inc');
include_once(dirname(__FILE__) . '/ressources/class.tcpip.inc');

xrun();

function xrun(){

    $unix=new unix();
    build_progress("{disable_service}",15);
    $strongswan=new strongswan();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableStrongswanServer",0);
    $strongswan->Save(true);
    build_progress("{building_configuration}",50);
    $php=$unix->LOCATE_PHP5_BIN();
    $RELOAD_CRON=False;

    build_progress("{stopping_service}",60);


    //system("$php /usr/share/artica-postfix/exec.strongswan.php --stop");
    remove_service("/etc/init.d/ipsec");
    remove_service("/etc/init.d/ipsec-stats");
    build_progress("{removing_startup_scripts}",70);
    if(is_file("/etc/monit/conf.d/APP_STRONGSWAN.monitrc")){
        @unlink("/etc/monit/conf.d/APP_STRONGSWAN.monitrc");
        $unix->reload_monit();
    }
    if(is_file("/etc/monit/conf.d/APP_STRONGSWAN_VICI.monitrc")){
        @unlink("/etc/monit/conf.d/APP_STRONGSWAN_VICI.monitrc");
        $unix->reload_monit();
    }
    if(is_file("/etc/monit/conf.d/APP_STRONGSWAN_VICI_STATS.monitrc")){
        @unlink("/etc/monit/conf.d/APP_STRONGSWAN_VICI_STATS.monitrc");
        $unix->reload_monit();
    }

    build_progress("{restarting_artica_status}",90);
    system("/etc/init.d/artica-status restart --force");

    build_progress("{done}",100);

}

function build_progress($text,$pourc){
    $GLOBALS["CACHEFILE"]=PROGRESS_DIR."/strongswan.enable.progress";
    echo "[{$pourc}%] $text\n";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
    @chmod($GLOBALS["CACHEFILE"],0755);
    if($GLOBALS["OUTPUT"]){sleep(1);}
}

function remove_service($INITD_PATH){
    if(!is_file($INITD_PATH)){return;}
    system("$INITD_PATH stop");
    system("ipsec stop");

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f ipsec remove >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --del ipsec >/dev/null 2>&1");

    }

    if(is_file($INITD_PATH)){@unlink($INITD_PATH);}
}


?>