<?php
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);

startx();

function startx(){
    $unix=new unix();
    $hostname=php_uname("n");
    $IPS=$unix->NETWORK_ALL_INTERFACES(true);

    foreach ($IPS as $ipaddr=>$none){
        if(preg_match("#^127\.0\.0#",$ipaddr)){continue;}
        $unix->add_EtcHosts($hostname,$ipaddr);
        squid_admin_mysql(1,"Adding $ipaddr for $hostname in host file",null,__FILE__,__LINE__);
        return true;
    }
return true;

}