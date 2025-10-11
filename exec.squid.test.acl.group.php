<?php
include_once(dirname(__FILE__).'/ressources/class.mysql.squid.builder.php');
include_once(dirname(__FILE__).'/ressources/class.mysql.xapian.builder.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.checks.inc');
include_once(dirname(__FILE__) . '/framework/class.unix.inc');
include_once(dirname(__FILE__) . '/framework/frame.class.inc');
include_once(dirname(__FILE__) . '/framework/class.settings.inc');

ini_set('display_errors', 1);	ini_set('html_errors',0);ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);
$GLOBALS["NOCHECK"]=false;
$GLOBALS["RELOAD"]=false;
$GLOBALS["DEBUG_INCLUDES"]=false;
$GLOBALS["CLUSTER"]=false;
$GLOBALS["ARGVS"]=implode(" ",$argv);
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
if(preg_match("#--includes#",implode(" ",$argv))){$GLOBALS["DEBUG_INCLUDES"]=true;}
if(preg_match("#--reload#",implode(" ",$argv),$re)){$GLOBALS["RELOAD"]=true;}
if(preg_match("#--force#",implode(" ",$argv))){$GLOBALS["FORCE"]=true;}
if(preg_match("#--verbose#",implode(" ",$argv))){$GLOBALS["VERBOSE"]=true;}
if(preg_match("#--nochek#",implode(" ",$argv))){$GLOBALS["NOCHECK"]=true;}
if(preg_match("#--cluster#",implode(" ",$argv))){$GLOBALS["CLUSTER"]=true;}

if($argv[1]=="--whitelists"){

    Test_whitelists();exit;
}

if($argv[1]=="--group"){

    test_groupid($argv[2]);
}


function test_groupid($gpid){

    $acls=new squid_acls();


    $items=$acls->GetItems($gpid);






}


function Test_whitelists(){

    $unix=new unix();

    $tempsquid=$unix->TEMP_DIR()."/squid.conf";
    $temppid=$unix->TEMP_DIR()."/squid.pid";
    $port=rand(200,5000);
@unlink("/usr/share/artica-postfix/ressources/logs/squidAsFailed.txt");
    $f[]="pid_filename	$temppid";
    $f[]="http_port 0.0.0.0:$port";
    $f[]="cache_effective_user squid";
    $f[]="include /etc/squid3/acls_whitelist.conf";
    $f[]="";
    @file_put_contents($tempsquid,@implode("\n",$f));
    $squid_checks=new squid_checks($tempsquid);
    if(!$squid_checks->squid_parse(true)){
       echo "Failed....\n";
       @file_get_contents("/usr/share/artica-postfix/ressources/logs/squidAsFailed.txt",@implode("\n"),$squid_checks->results);
        return;
    }




}
