<?php

include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["listfiles"])){listfiles();exit;}



function listfiles(){

    if(!$f=opendir("/etc/cron.d")){
        return;
    }

    if (!$handle = opendir("/etc/cron.d")) {return;}
    while (false !== ($filename = readdir($handle))) {
        if ($filename == ".") {
            continue;
        }
        if ($filename == "..") {
            continue;
        }
        $array[$filename] = true;
    }

    @file_put_contents("/usr/share/artica-postfix/ressources/logs/web/cron.lists",serialize($array));
    @chmod("/usr/share/artica-postfix/ressources/logs/web/cron.lists",0755);
	
}


