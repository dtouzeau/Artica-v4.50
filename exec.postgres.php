<?php
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["FORCE"]=false;
$GLOBALS["RECONFIGURE"]=false;
$GLOBALS["SWAPSTATE"]=false;
$GLOBALS["NOSQUIDOUTPUT"]=true;
$GLOBALS["TITLENAME"]="PostgreSQL Daemon";
$GLOBALS["SCHEDULE_ID"]=0;
if(preg_match("#--output#",implode(" ",$argv))){$GLOBALS["OUTPUT"]=true;}
if(preg_match("#schedule-id=([0-9]+)#",implode(" ",$argv),$re)){$GLOBALS["SCHEDULE_ID"]=$re[1];}
if(preg_match("#--force#",implode(" ",$argv),$re)){$GLOBALS["FORCE"]=true;}
if(preg_match("#--reconfigure#",implode(" ",$argv),$re)){$GLOBALS["RECONFIGURE"]=true;}
$GLOBALS["AS_ROOT"]=true;
include_once(dirname(__FILE__).'/ressources/class.ldap.inc');
include_once(dirname(__FILE__).'/ressources/class.squid.inc');
include_once(dirname(__FILE__).'/ressources/class.mysql.inc');
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.system.nics.inc');
include_once(dirname(__FILE__).'/ressources/class.postgres.inc');

$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);
$DisablePostGres=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DisablePostGres"));

if(isset($argv[1])){
    if($argv[1]=="--install"){install_postgres();exit();}
    if($argv[1]=="--uninstall"){remove_postgres();exit();}


}

if($DisablePostGres==1){
    echo "PostreSQL is disabled\n";
    die();
}




$GLOBALS["ARGVS"]=implode(" ",$argv);
if(isset($argv[1])){
    if($argv[1]=="--stop"){$GLOBALS["OUTPUT"]=true;stop();exit();}
    if($argv[1]=="--start"){$GLOBALS["OUTPUT"]=true;start();exit();}
    if($argv[1]=="--restart"){$GLOBALS["OUTPUT"]=true;restart();exit();}
    if($argv[1]=="--tables"){$GLOBALS["OUTPUT"]=true;checktables();exit();}
    if($argv[1]=="--dbsize"){$GLOBALS["OUTPUT"]=true;InfluxDbSize();exit();}
    if($argv[1]=="--install"){install_postgres();exit();}
    if($argv[1]=="--restart-progress"){$GLOBALS["OUTPUT"]=true;restart();exit();}
    if($argv[1]=="--remove-database"){$GLOBALS["OUTPUT"]=true;remove_database();exit();}
    if($argv[1]=="--build"){exit();}
    if($argv[1]=="--upgrade-backup"){$GLOBALS["OUTPUT"]=true;upgrade_backup();exit();}
    if($argv[1]=="--upgrade-restore"){$GLOBALS["OUTPUT"]=true;upgrade_restore();exit();}
}
echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} Could not understand ?\n";

function restart():bool{
return true;
}

function build_progress_restart($text,$pourc){
    $unix=new unix();
    $unix->framework_progress($pourc,$text,"postgresql.progress");
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $text\n";}
    $cachefile="/usr/share/artica-postfix/ressources/logs/postgres.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);
}

function checktables(){
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} checktables...\n";
    $pg=new postgres_sql();
    echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} CREATE_TABLES...\n";
    $pg->CREATE_TABLES();

}
function remove_postgres(){
    system("/usr/sbin/artica-phpfpm-service -uninstall-postgresql");
    return true;
}
function start($aspid=false){
    system("/usr/sbin/artica-phpfpm-service -start-postgresql");
    return true;
}
function stop(){
    system("/usr/sbin/artica-phpfpm-service -stop-postgresql");
}

function build_progress_remove($text,$pourc){

    if($GLOBALS["OUTPUT"]){echo "Remove........: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $text\n";}
    $cachefile="/usr/share/artica-postfix/ressources/logs/postgres.remove.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}





function upgrade_backup(){
    $unix=new unix();
    $su=$unix->find_program("su");
    $targetFilename="/home/artica/postresql/backup.db";

    if(is_file($targetFilename)){
        @unlink($targetFilename);
    }

    if(!is_dir("/home/artica/postresql")){
        @mkdir("/home/artica/postresql",0755);
    }
    @chmod("/home/artica/postresql",0755);
    @chown("/home/artica/postresql","ArticaStats");
    @chgrp("/home/artica/postresql","ArticaStats");
    $cmdline="$su -c \"/usr/local/ArticaStats/bin/pg_dumpall -c --if-exists -S ArticaStats -f $targetFilename -h /var/run/ArticaStats\" ArticaStats";
    $results[]=$cmdline;
    exec($cmdline,$results);
    foreach ($results as $line){
        echo "Backup database: $line";
    }
}
function upgrade_restore(){
    $dsn =  "-h /var/run/ArticaStats";
    $targetFilename="/home/artica/postresql/backup.db";
    $cmdline="/usr/local/ArticaStats/bin/psql $dsn -U ArticaStats proxydb -f $targetFilename 2>&1";
    $results[]=$cmdline;
    exec($cmdline,$results);
    foreach ($results as $line){
        echo "Restore database: $line\n";
    }
    if(is_file($targetFilename)){
        @unlink($targetFilename);
    }
}







function Postgres_Version(){

    exec("/usr/local/ArticaStats/bin/postgres -V",$results);
    foreach ($results as $line){

        if(preg_match("#postgres.*?\s+([0-9]+)#",$line,$re)){return intval($re[1]);}
    }

    return 9;

}








function build_progress_vacuumdb($text,$pourc){
    if($GLOBALS["OUTPUT"]){echo "Starting......: ".date("H:i:s")." [INIT]: {$GLOBALS["TITLENAME"]} {$pourc}% $text\n";}
    $cachefile="/usr/share/artica-postfix/ressources/logs/squid.statistics.purge.progress";
    $array["POURC"]=$pourc;
    $array["TEXT"]=$text;
    @file_put_contents($cachefile, serialize($array));
    @chmod($cachefile,0755);

}
function vacuumdb(){


}



function install_postgres():bool{
    system("/usr/sbin/artica-phpfpm-service -install-postgresql");
    return true;
}


?>