<?php
include_once(dirname(__FILE__)."/frame.class.inc"); 
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["build"])){build();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["delete-dbs"])){delete_databases();exit;}

writelogs_framework("unable to understand query...",__FUNCTION__,__FILE__,__LINE__);	

function install(){
	$unix=new unix();
    $unix->framework_execute("exec.smokeping.php --install","smokeping.progress","smokeping.log");
}
function status(){
    $MAIN=array();
    $f=explode("\n",@file_get_contents("/etc/smokeping/config.d/Targets"));
    foreach ($f as $line){
        if(!preg_match("#\+\+section_([0-9]+)#",$line,$re)){continue;}
        $MAIN[$re[1]]=true;
    }
    @file_put_contents(PROGRESS_DIR."/smoke_ping.targets",serialize($MAIN));
    $unix=new unix();
    $unix->framework_exec("exec.status.php --smokeping");
}
function uninstall(){
    $unix = new unix();
    $unix->framework_execute("exec.smokeping.php --uninstall", "smokeping.progress", "smokeping.log");
}
function build(){
    $unix = new unix();
    $unix->framework_execute("exec.smokeping.php --build", "smokeping.progress", "smokeping.log");
}
function restart(){
    $unix = new unix();
    $unix->framework_execute("exec.smokeping.php --restart", "smokeping.progress", "smokeping.log");
}
function delete_databases(){
    $ID=intval($_GET["delete-dbs"]);
    $fdir="/var/lib/smokeping";
    $handle = opendir($fdir);if(!$handle){return false;}
    while (false !== ($filename = readdir($handle))) {
        if($filename=="."){continue;}
        if($filename==".."){continue;}
        if($filename=="Local"){continue;}
        if($filename=="__cgi"){continue;}
        if($filename=="__sortercache"){continue;}
        $BaseDir="$fdir/$filename";
        if(!is_dir($BaseDir)){continue;}
        $handle2 = opendir($BaseDir);
        if(!$handle2){continue;}
        while (false !== ($filename_target = readdir($handle2))) {
            if($filename_target=="."){continue;}
            if($filename_target==".."){continue;}
            if($filename_target=="section_{$ID}.rrd"){
                @unlink("$BaseDir/$filename_target");
                return true;
            }
        }
    }
return true;
}
