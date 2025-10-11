<?php
include_once(dirname(__FILE__).'/framework/frame.class.inc');
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.system.network.inc');
include_once(dirname(__FILE__).'/ressources/class.hosts.inc');
$GLOBALS["TITLENAME"]="Passive asset detection system";
	if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;$GLOBALS["OUTPUT"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}

install();



function build_progress($pourc,$text){
	$date=date("Y-m-d H:i:s");
	$GLOBALS["CACHEFILE"]="/usr/share/artica-postfix/ressources/logs/statscom.client.progress";
	echo "$date: [{$pourc}%] $text\n";
	$array["POURC"]=$pourc;
	$array["TEXT"]=$text;
	@file_put_contents($GLOBALS["CACHEFILE"], serialize($array));
	@chmod($GLOBALS["CACHEFILE"],0755);
}
function install(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $title="{installing}";
    $EnableStatsComRemoteSyslog=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableStatsComRemoteSyslog"));

    if(!$unix->CORP_LICENSE()){
        build_progress(110,"$title {failed} {license_error}");
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableStatsComRemoteSyslog",0);
        shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
        return;
    }


    if($EnableStatsComRemoteSyslog==0){
        $title="{uninstalling}";
    }
    //

    $f=explode("\n",@file_get_contents("/etc/squid3/logging.conf"));
    $LOGGIN_SQUID=false;
    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#^logformat statscom#",$line)){
            $LOGGIN_SQUID=true;
            break;
        }
    }

    if($EnableStatsComRemoteSyslog==1){
       build_progress(50,"$title {reconfigure} {APP_SQUID}");
       shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");

        if(!is_file("/etc/rsyslog.d/artica-statscom.conf")){
            build_progress(110,"$title {reconfigure} {APP_SQUID} {failed}");
            return;
        }


    }
    if($EnableStatsComRemoteSyslog==0){
        if($LOGGIN_SQUID){
            build_progress(50,"$title {reconfigure} {APP_SQUID}");
            shell_exec("$php /usr/share/artica-postfix/exec.squid.global.access.php --logging");
        }
    }


    $unix=new unix();$unix->RESTART_SYSLOG(true);
    build_progress(100,"$title {success}");
}