<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));
if($EnableIntelCeleron==1){die("EnableIntelCeleron==1\n");}
include_once(dirname(__FILE__)."/ressources/class.sockets.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__)."/ressources/class.ccurl.inc");
include_once(dirname(__FILE__)."/ressources/class.influx.inc");

if(isset($argv[1])){
    if($argv[1]=="--network"){
        if(system_is_overloaded(__FILE__)){die();}
        lshw_network();
        die();
    }
}

start();
function start(){
    lshw_network();
}
function lshw_network():bool{
    $unix=new unix();
    $unix->Popuplate_cron_delete("lshw-network");
    @unlink(__FILE__);
    return true;
}

?>