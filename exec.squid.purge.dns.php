<?php

$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
$GLOBALS["BYPASS"]=true;
$GLOBALS["DEBUG_INFLUX_VERBOSE"]=true;
$GLOBALS["REBUILD"]=false;
$GLOBALS["OLD"]=false;
$GLOBALS["FORCE"]=false;
$GLOBALS["DEBUG_MEM"]=false;
$GLOBALS["VERBOSE"]=false;
$GLOBALS["NODHCP"]=true;
$GLOBALS["PROGRESS"]=false;
$GLOBALS["BASEDIR"]="/usr/share/artica-postfix/ressources/interface-cache";
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(is_array($argv)){
	if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
	if(preg_match("#--old#",implode(" ",$argv))){$GLOBALS["OLD"]=true;}
	if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
	if(preg_match("#--rebuild#",implode(" ",$argv))){$GLOBALS["REBUILD"]=true;}
	if(preg_match("#--progress#",implode(" ",$argv))){$GLOBALS["PROGRESS"]=true;}
}
ini_set('display_errors', 1);
ini_set('html_errors',0);
ini_set('display_errors', 1);
ini_set('error_reporting', E_ALL);


if($GLOBALS["VERBOSE"]){"echo Loading...\n";}
include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
include_once(dirname(__FILE__).'/framework/class.unix.inc');if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");





purge();





function build_progress($text,$pourc){
    $cachefile="/usr/share/artica-postfix/ressources/logs/squid.dns.purge.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}



function purge(){

    $unix=new unix();
    $squidbin=$unix->LOCATE_SQUID_BIN();
    if(!is_file($squidbin)){exit();}
    $SQUIDEnable=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable");
    if($SQUIDEnable==0){
        build_progress("Proxy is not enabled",110);
        return;
    }

    if(is_file("/etc/init.d/unbound")){
        $i=0;
        $unbound_control=$unix->find_program("unbound-control");
        $ll=explode(",","A, AAAA, MX, PTR, NS, SOA, CNAME, DNAME, SRV, NAPTR");
        foreach ($ll as $cc){
            $cc=trim($cc);
            $i++;
            build_progress("{removing} {dns_cache} $cc",10+$i);
            shell_exec("$unbound_control flush $cc");
            sleep(1);
        }

    }

    build_progress("{restarting_service}",20);
    shell_exec("/usr/sbin/artica-phpfpm-service -restart-proxy");
    build_progress("{starting_service} {success}",70);
    sleep(1);
    $php=$unix->LOCATE_PHP5_BIN();
    build_progress("{refresh_all_status}",95);
    shell_exec("$php /usr/share/artica-postfix/exec.squid.php.storedir.php --force");
    build_progress("{refresh_all_status} {done}",100);
}








		




