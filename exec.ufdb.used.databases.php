<?php
$GLOBALS["FORCE"]=false;
if(!isset($GLOBALS["CLASS_SOCKETS"])){if(!class_exists("sockets")){include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");}$GLOBALS["CLASS_SOCKETS"]=new sockets();}
ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);

if(isset($argv[1])) {
    if ($argv[1] == "--users") {
        $GLOBALS["FORCE"]=true;
        ExecUserLists();
        exit;
    }

}
function ExecUserLists(){
    if(!class_exists("unix")) {include_once(dirname(__FILE__) . '/framework/class.unix.inc');}
    $unix=new unix();
    $execuserlist_path=array();
    $stampFile="/etc/artica-postfix/pids/ExecUserLists.time";
    if(! $GLOBALS["FORCE"]) {
        $sttl = $unix->file_time_min($stampFile);
        if ($sttl < 3) {
            echo "Refresh Users List:.......{$sttl}mn - Waiting 3mn minimal\n";
            return;
        }
    }
    @unlink($stampFile);
    @file_put_contents($stampFile,time());
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdUsersUsed", serialize(array()));

    if(!is_file("/etc/squid3/ufdbGuard.conf")){return;}
    $f=explode("\n",@file_get_contents("/etc/squid3/ufdbGuard.conf"));
    $RefreshUserList=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("RefreshUserList"));


    $TTL=0;
    if($RefreshUserList==0){$RefreshUserList=15;}
    if($RefreshUserList<5){$RefreshUserList=5;}
    if($RefreshUserList>1440){$RefreshUserList=1440;}
    $RefreshUserList=$RefreshUserList+3;

    echo "Refresh Users List:.......{$RefreshUserList}mn\n";


    foreach ($f as $ligne) {
        $ligne = trim($ligne);
        if ($ligne == null) {
            continue;
        }
        if (preg_match("#^\##", $ligne)) {
            continue;
        }
        if (preg_match("#^execuserlist\s+\"(.+?)\"#", $ligne, $re)) {
            $execuserlist_path[] = trim($re[1]);
            continue;
        }
    }
    if(count($execuserlist_path)==0){return;}

    $EXEC_USERS=array();

    if(count($execuserlist_path)>0){
        foreach ($execuserlist_path as $OriginalPath){
            echo $OriginalPath."\n";
            $ModifiedPath=str_replace("/","+",$OriginalPath);
            $ModifiedPath=str_replace(" ","_",$ModifiedPath);
            $ModifiedPath="/var/lib/squidguard/cache.execlists/$ModifiedPath";
            $EXEC_USERS[$OriginalPath]=$ModifiedPath;
        }


    }

    $MUSTRESTART=false;
    if(count($EXEC_USERS)==0){return;}
    $NUMBER_OF_USERS=0;
    foreach ($EXEC_USERS as $cmdline=>$filepath){
        $filetemp=$unix->FILE_TEMP();
        echo "$cmdline >$filetemp\n";
        shell_exec("$cmdline >$filetemp 2>&1");
        $flogs=array();


        $CountLinesSource=$unix->COUNT_LINES_OF_FILE($filetemp);
        echo ".............: Source $CountLinesSource\n";
        $DestCountLine=$unix->COUNT_LINES_OF_FILE($filepath);
        echo ".............: Destination $DestCountLine ";
        $TTL=$unix->file_time_min($filepath);
        echo "{$TTL}Mn\n";

        $flogs[]="$cmdline : {$CountLinesSource} User(s)";
        $flogs[]="$filepath : {$DestCountLine} User(s)";
        $flogs[]="TTL : {$TTL}mn/{$RefreshUserList}mn";

        if($CountLinesSource==0){
            @unlink($filetemp);
            continue;
        }

        if($CountLinesSource==$DestCountLine){
            @unlink($filetemp);
            $NUMBER_OF_USERS=$NUMBER_OF_USERS+$CountLinesSource;
            continue;
        }

        if($TTL>$RefreshUserList){

            squid_admin_mysql(1,"Web-Filtering Users has not been refreshed {$TTL}mn/{$RefreshUserList}Mn [action=Save Users/restart]",@implode("\n",$flogs),__FILE__,__LINE__);

              @unlink($filepath);
              @copy($filetemp,$filepath);
              @chmod($filepath,0755);
              @chown($filepath,"squid");
              @chgrp($filepath,"squid");
              @unlink($filetemp);
              $MUSTRESTART=True;
              continue;

        }


        if($DestCountLine==0){
            squid_admin_mysql(1,"Web-Filtering Fatal no users in cache (must have $CountLinesSource) [action=Save/restart]",
                @implode("\n",$flogs),
                __FILE__,__LINE__);
            @unlink($filepath);
            @copy($filetemp,$filepath);
            @chown($filepath,"squid");
            @chgrp($filepath,"squid");
            @chmod($filepath,0755);
            @unlink($filetemp);
            $MUSTRESTART=True;
            continue;
        }

        $NUMBER_OF_USERS=$NUMBER_OF_USERS+$DestCountLine;

    }

    if($MUSTRESTART) {
        system("/etc/init.d/ufdb restart");
    }

    $ARRAY["NUMBER_OF_USERS"]=$NUMBER_OF_USERS;
    $ARRAY["TTL"]=$TTL;
    $ARRAY["REFRESH"]=$RefreshUserList-3;

    @unlink("/etc/artica-postfix/settings/Daemons/UfdUsersUsed");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("UfdUsersUsed", serialize($ARRAY));



}

