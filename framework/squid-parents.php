<?php
if(!defined("PROGRESS_DIR")){define("PROGRESS_DIR","/usr/share/artica-postfix/ressources/logs/web");}
ini_set('error_reporting', E_ALL);
if(isset($_GET["verbose"])){
    ini_set('display_errors', 1);
    ini_set('html_errors',0);
    ini_set('display_errors', 1);

    $GLOBALS["VERBOSE"]=true;
}
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");

if(isset($_GET["rules-status"])){rules_status();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);



function rules_status(){
    $ACTIVE_RULES=array();
    $f=explode("\n",@file_get_contents("/etc/squid3/acls_peer.conf"));
    foreach ($f as $line){

        if(preg_match("#\s+([0-9]+)\]\s+#",$line,$re)){
            $ACTIVE_RULES[$re[1]]=true;
        }

    }

    @file_put_contents(PROGRESS_DIR."/cache.peer.status.arr",serialize($ACTIVE_RULES));
    @chmod(PROGRESS_DIR."/cache.peer.status.arr",0755);
}