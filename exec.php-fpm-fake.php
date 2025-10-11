#!/usr/bin/php
<?php

$GLOBALS["VERBOSE"]=false;
$GLOBALS["DEBUG"]=false;;
$GLOBALS["FORCE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["DEBUG"]=true;$GLOBALS["VERBOSE"]=true;
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
if(preg_match("#--reload#",implode(" ",$argv))){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
include_once(dirname(__FILE__) . '/ressources/class.users.menus.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
$GLOBALS["SERVICE_NAME"]="Artica Web console (FPM)";

xstart();


function xstart(){

    if(is_file("/etc/init.d/php7.3-fpm")){

        if(!is_patched("/etc/init.d/php7.3-fpm")){
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Creating fake /etc/init.d/php7.3-fpm initd\n";
            create_fake_service("/etc/init.d/php7.3-fpm");
        }else{
            echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} /etc/init.d/php7.3-fpm OK\n";
        }

    }




}

function is_patched($init){

    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["SERVICE_NAME"]} Checking $init\n";
    $t=explode("\n",@file_get_contents($init));
    foreach ($t as $line){
        $line=trim($line);
        if(preg_match("#Author:\s+#",$line)){return false;}
        if(preg_match("#Modified by Artica#",$line)){return true;}
    }

}


function create_fake_service($INITD_PATH){

        shell_exec("$INITD_PATH stop >/dev/null 2>&1 &");

        $f[]="#! /bin/sh";
        $f[]="#";
        $f[]="";
        $f[]="### BEGIN INIT INFO";
        $f[]="# Provides: ".basename($INITD_PATH);
        $f[]="# Required-Start: \$network \$remote_fs";
        $f[]="# Required-Stop: \$network \$remote_fs";
        $f[]="# Default-Start: 2 3 4 5";
        $f[]="# Default-Stop: 0 1 6";
        $f[]="# Short-Description: starts ".basename($INITD_PATH);
        $f[]="# Description: Starts The PHP FastCGI Process Manager Daemon";
        $f[]="### END INIT INFO";
        $f[]="";
        $f[]="# Modified by Artica";
        $f[]="#";

        $f[]="";
        $f[]="start() {";
        $f[]="	return 0";
        $f[]="}";
        $f[]="";
        $f[]="stop() {";
        $f[]="	return 0";
        $f[]="}";
        $f[]="";
        $f[]="reload() {";
        $f[]="	return 0";
        $f[]="}";
        $f[]="";
        $f[]="forcestart() {";
        $f[]="	OPTIONS=\"\$OPTIONS --force\"";
        $f[]="	start";
        $f[]="}";
        $f[]="";
        $f[]="case \"\$1\" in";
        $f[]="	start|stop|status|restart|reload|force-reload)";
        $f[]="		start";
        $f[]="		;;";
        $f[]="	*)";
        $f[]="		echo \"Usage: \$0 {start|stop|status|restart|reload|force-reload}\"";
        $f[]="		exit 1";
        $f[]="		;;";
        $f[]="esac";
        $f[]="";


        @unlink($INITD_PATH);
        @file_put_contents($INITD_PATH, @implode("\n", $f));



}


