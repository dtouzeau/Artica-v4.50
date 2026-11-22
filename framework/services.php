<?php
// stamp 01/11/2026
// Patch License.
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}if(!isset($GLOBALS["ARTICALOGDIR"])){$GLOBALS["ARTICALOGDIR"]=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaLogDir"); if($GLOBALS["ARTICALOGDIR"]==null){ $GLOBALS["ARTICALOGDIR"]="/var/log/artica-postfix"; } }
if(isset($_GET["nsswitch"])){nsswitch();exit;}
if(isset($_GET["cache-pages"])){cache_pages();exit;}

if(isset($_GET["reload-adagent"])){reload_adagent();exit;}
if(isset($_GET["system-defrag"])){system_defrag();exit;}

if(isset($_GET["license-migration"])){license_migration();exit;}
if(isset($_GET["license-register"])){register_license();exit;}
if(isset($_GET["restart-instant-messaging"])){restart_instant_messaging();exit;}

if(isset($_GET["restart-freshclam"])){restart_freshclam();exit;}

if(isset($_GET["admin-events"])){admin_events();exit;}

if(isset($_GET["reload-pure-ftpd"])){pureftpd_reload();exit;}

if(isset($_GET["mysql-ocs"])){mysql_ocs();exit;}
if(isset($_GET["SessionPathInMemoryInfos"])){SessionPathInMemoryInfos();exit;}
if(isset($_GET["ARTICA-MAKE"])){ARTICA_MAKE_STATUS();exit;}
if(isset($_GET["export-etc-artica"])){export_etc_artica();exit;}

foreach ($_GET as $num=>$line){$f[]="$num=$line";}

writelogs_framework("unable to understand query !!!!!!!!!!!..." .@implode(",",$f),"main()",__FILE__,__LINE__);
die("DIE " .__FILE__." Line: ".__LINE__);


function system_defrag(){
    $unix=new unix();
    $shutdown=$unix->find_program("shutdown");

    exec("$shutdown -rF now 2>&1",$results);
    writelogs_framework("$shutdown -rF now",__FUNCTION__,__FILE__,__LINE__);
    foreach ($results as $num=>$val){
        writelogs_framework("$val",__FUNCTION__,__FILE__,__LINE__);
    }

}


function ARTICA_MAKE_STATUS(){
    $unix=new unix();
    $pgrep=$unix->find_program("pgrep");
    $master_pid=0;
    exec("$pgrep -l -f \"bin/artica-make\"",$results);
    foreach ($results as $num=>$line){
        if(preg_match("#pgrep#", $line)){continue;}
        if(preg_match("#^([0-9]+)\s+sh\s+#", $line,$re)){continue;}
        if(preg_match("#^([0-9]+)\s+.+?artica-make\s+([A-Z\_0-9]+)#", $line,$re)){
            $pid=$re[1];
            $time=$unix->PROCESS_TTL_TEXT($pid);
            $SOFT=$re[2];
            $array[$SOFT]=$time;
        }
    }
    echo "<articadatascgi>". base64_encode(serialize($array))."</articadatascgi>";

}





function restart_freshclam(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    if(is_file("/etc/init.d/clamav-freshclam")){
        $cmd=trim("$nohup /etc/init.d/clamav-freshclam restart >/dev/null 2>&1 &");
        shell_exec($cmd);
    }
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}

function restart_instant_messaging(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart ejabberd >/dev/null 2>&1 &");
    shell_exec($cmd);
    $cmd=trim("$nohup /etc/init.d/artica-postfix restart pymsnt >/dev/null 2>&1 &");
    shell_exec($cmd);
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
}


function nsswitch(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd=trim("/usr/sbin/artica-phpfpm-service -nsswitch");
    @mkdir("/etc/artica-postfix/pids");
    $timeFile="/etc/artica-postfix/pids/nsswitch.time";

    if($unix->file_time_min($timeFile)>10){
        @unlink($timeFile);
        @file_put_contents($timeFile, time());
        shell_exec($cmd);
        writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    }
}
function mysql_ocs(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.mysql.build.php --checks >/dev/null 2>&1");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
}




function SessionPathInMemoryInfos(){
    $unix=new unix();
    $array=$unix->SessionPathInMemoryInfos();
    $finale=base64_encode(serialize($array));
    echo "<articadatascgi>$finale</articadatascgi>";

}

function reload_adagent(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $cmd=trim($nohup." ".$unix->LOCATE_PHP5_BIN(). " /usr/share/artica-postfix/exec.ad-agent.php --reload >/dev/null 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}



function license_migration(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();

    $CACHEFILE="/usr/share/artica-postfix/ressources/logs/web/artica.license.progress";
    $LOGSFILES="/usr/share/artica-postfix/ressources/logs/web/artica_license.txt";
    @unlink($CACHEFILE);
    @unlink($LOGSFILES);
    @touch($LOGSFILES);
    @touch($CACHEFILE);
    @chmod($CACHEFILE,0755);
    @chmod($LOGSFILES,0755);

    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic --migration >$LOGSFILES 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

}

function register_license():bool{
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php5=$unix->LOCATE_PHP5_BIN();


    $CACHEFILE="/usr/share/artica-postfix/ressources/logs/artica.license.progress";
    $LOGSFILES="/usr/share/artica-postfix/ressources/logs/web/artica_license.txt";

    @unlink($CACHEFILE);
    @unlink($LOGSFILES);
    @touch($LOGSFILES);
    @touch($CACHEFILE);
    @chmod($CACHEFILE,0755);
    @chmod($LOGSFILES,0755);
    @file_put_contents("/etc/artica-postfix/settings/Daemons/NewLicServer",1);
    $cmd=trim("$nohup $php5 /usr/share/artica-postfix/exec.web-community-filter.php --register-lic --email >$LOGSFILES 2>&1 &");
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}


function admin_events(){
    $serialize=base64_decode($_GET["admin-events"]);
    $md5=md5($serialize);
    if(!is_dir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events")){@mkdir("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events",0755,true);}
    @file_put_contents("{$GLOBALS["ARTICALOGDIR"]}/system_admin_events/$md5.log", $serialize);
}



function export_etc_artica(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    shell_exec("$php /usr/share/artica-postfix/exec.export-artica-settings.php");

}



function cache_pages(){
    $unix=new unix();
    $nohup=$unix->find_program("nohup");
    $php=$unix->LOCATE_PHP5_BIN();

    @chmod("/usr/bin/php /usr/share/artica-postfix/exec.status.php --process1",0755);
    $cmd="$nohup /usr/bin/php /usr/share/artica-postfix/exec.status.php --process1 --force --verbose --".time()." >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);

    $cmd="$nohup $php /usr/share/artica-postfix/exec.squid.interface-size.php --force >/dev/null 2>&1 &";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);


}



