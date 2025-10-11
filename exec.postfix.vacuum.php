<?php

if(!isset($GLOBALS["CLASS_SOCKETS"])){
    if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}
    $GLOBALS["CLASS_SOCKETS"]=new sockets();
}


include_once(dirname(__FILE__).'/ressources/class.os.system.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');
include_once(dirname(__FILE__)."/framework/frame.class.inc");



$EnablePostfix=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnablePostfix"));



if(is_file("/etc/cron.d/vacuumdb-postfix")){
    if($EnablePostfix==0){
        @unlink("/etc/cron.d/vacuumdb-postfix");
        system("/etc/init.d/cron reload");
        return;
    }

}
if($EnablePostfix==0){
    echo "EnablePostfix == $EnablePostfix -> ABORTING\n";
    return;
}

$f[]="#!/bin/sh";
$f[]="/usr/local/ArticaStats/bin/vacuumdb --full --jobs=5 --dbname=proxydb --no-password --username=ArticaStats --dbname=proxydb --host='/var/run/ArticaStats' --table=smtplog || true";
$f[]="exit 0";

@file_put_contents("/usr/sbin/vacuumdb-postfix.sh",@implode("\n",$f)."\n");
echo "/usr/sbin/vacuumdb-postfix.sh OK\n";
@chmod("/usr/sbin/vacuumdb-postfix.sh",0755);
$unix=new unix();
$unix->Popuplate_cron_make("vacuumdb-postfix","15 0 * * *","/usr/sbin/vacuumdb-postfix.sh");
system("/etc/init.d/cron reload");

