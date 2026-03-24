<?php
ini_set('display_errors', 1);ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["YESCGROUP"]=true;
$GLOBALS["PEITYMAX"]=100;
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once(dirname(__FILE__).'/ressources/class.template-admin.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.os.system.tools.inc');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if(!Build_pid_func(__FILE__,"MAIN")){
    events(basename(__FILE__)." Already executed.. aborting the process");
    die("DIE Already executed.. aborting the process in" .__FILE__." Line: ".__LINE__);
}

if(system_is_overloaded()){
    events("{OVERLOADED_SYSTEM}, web console object will be not updated....");
    die(0);
}
$GLOBALS["PEITYCONF"]="{ width:255,fill: [\"#eeeeee\"],stroke:\"#18a689\",strokeWidth: 2 }";

zexec();

function zexec():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_TIME",time());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("WEBCONSOLE_SYS_BAND",bandwidth());
    return true;
}





function bandwidth():string{
    $tpl                    = new template_admin();

    if(!is_file("/usr/share/artica-postfix/ressources/logs/speed/latest")){
        return "";
    }
    $ligne=unserialize(@file_get_contents("/usr/share/artica-postfix/ressources/logs/speed/latest"));
    $download=$ligne["download"];
    $upload=$ligne["upload"];
    $public_ip=$ligne["public_ip"];
    $isp=$ligne["isp"];
    $country=$ligne["country"];
    $tt=array();
    if(strlen($public_ip)>2){
        $tt[]=$public_ip."<br>";
    }
    if(strlen($isp)>2){
        $tt[]="($isp";
    }
    if(strlen($country)>2){
        $tt[]="/$country";
    }
    if(count($tt)>2){
        $tt[]=")";
    }
    if(count($tt)==0){
        $tt[]="{report}";
    }
    $subtitle=$tpl->td_href(@implode(" ",$tt),"{statistics}","Loadjs('fw.system.status.bandwidth.php')");
    $title_load="<span style='color:#337AB7'>$subtitle</span>";
    $html[]="                <div class=\"ibox float-e-margins\">";
    $html[]="                    <div class=\"ibox-title\">";
    $html[]="                        <span class=\"label label-primary pull-right\">OK</span>";
    $html[]="                        <h5>{bandwidth}</h5>";
    $html[]="                    </div>";
    $html[]="                    <div class=\"ibox-content\">";
    $html[]="                        <h1 class=\"no-margins\">{$download}Mbps </h1>";
    $html[]="                        <div class=\"font-bold text-success\">Upload: {$upload}Mbps <i class=\"fa fa-upload\"></i></div>";
    $html[]="                        <small>$title_load</small>";
    $html[]="                    </div>";
    $html[]="                </div>";
    return @implode($html);

}











function events($text){$LOG_SEV=LOG_INFO;openlog("artica-system", LOG_PID , LOG_SYSLOG);syslog($LOG_SEV, $text);closelog();}

