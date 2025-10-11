<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
$GLOBALS["CLASS_UNIX"]=new unix();
if(isset($_GET["nb-modules-loaded"])){NB_MODULES_LOADED();exit;}
if(isset($_GET["nb-modules-list"])){NB_MODULES_lIST();exit;}

function NB_MODULES_LOADED(){
    $lsmod=$GLOBALS["CLASS_UNIX"]->find_program("lsmod");
    $grep=$GLOBALS["CLASS_UNIX"]->find_program("grep");
    $wc=$GLOBALS["CLASS_UNIX"]->find_program("wc");
    exec("$lsmod |$grep -Ei \"^(.+?)\s+[0-9]+\"|$wc -l >/usr/share/artica-postfix/ressources/logs/web/NB_MODULES_LOADED 2>&1",$results);


}
function NB_MODULES_lIST(){
    $lsmod=$GLOBALS["CLASS_UNIX"]->find_program("lsmod");
    $grep=$GLOBALS["CLASS_UNIX"]->find_program("grep");
    $wc=$GLOBALS["CLASS_UNIX"]->find_program("wc");
    exec("$lsmod |$grep -Ei \"^(.+?)\s+[0-9]+\" >/usr/share/artica-postfix/ressources/logs/web/NB_MODULES_LIST 2>&1",$results);


}