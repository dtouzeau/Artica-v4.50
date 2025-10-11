<?php


function adagent_pid(){
    $pid_path="/var/run/adagent.pid";
    if(!is_file($pid_path)){
        $binpath=$GLOBALS["CLASS_UNIX"]->find_program("ad-agent-lbl");
        return $GLOBALS["CLASS_UNIX"]->PIDOF($binpath);
    }
    $pids=explode("\n",@file_get_contents($pid_path));
    while (list ($index, $line) = each ($pids)){
        $line=str_replace("\r", "", $line);
        $line=str_replace("\n", "", $line);
        if(!is_numeric(trim($line))){continue;}
        if($GLOBALS["VERBOSE"]){echo "$pid_path = $line\n";}
        if($GLOBALS["CLASS_UNIX"]->process_exists($line)){
            $PPID=$GLOBALS["CLASS_UNIX"]->PPID_OF($line);
            if($GLOBALS["VERBOSE"]){echo "$line ->running PPID:$PPID\n";}
            return $PPID;
        }
    }

}


function adagent(){

    if($GLOBALS["VERBOSE"]){echo __LINE__."]********* adagent() *********\n";}
    $binpath=$GLOBALS["CLASS_UNIX"]->find_program("ad-agent-lbl");

    $l[]="[APP_ADAGENT_LBL]";
    $l[]="service_name=APP_ADAGENT_LBL";
    if(!is_file($binpath)){
        if($GLOBALS["VERBOSE"]){echo  __LINE__."] ********* adagent no such file...*********\n";}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ADAGENT_INSTALLED", 0);
        $l[]="running=0\ninstalled=0";
        return @implode("\n", $l);

    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("ADAGENT_INSTALLED", 1);
    $EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    $HaproxyVersion=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ADAGENT_VERSION");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("HaproxyVersion", $HaproxyVersion);

    if($EnableHaProxy==0){
        if($GLOBALS["VERBOSE"]){echo __LINE__."]********* adagent NOT ENABLED *********\n";}
        if(is_file("/etc/init.d/adagent")){
            squid_admin_mysql(1, "Removingadagent startup script (not enabled)", null,__FILE__,__LINE__);
            $cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.php --uninstall");
            shell_exec2($cmd);
        }
        return;
    }



    if(!is_file("/etc/init.d/adagent")){
        squid_admin_mysql(0, "Install adagent startup script (enabled)", null,__FILE__,__LINE__);
        if($GLOBALS["VERBOSE"]){echo  __LINE__."] ********* /etc/init.d/adagent no such file *********\n";}
        $cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.php --install");
        shell_exec2($cmd);
    }
    $pid_path="/var/run/adagent.pid";



    $EnableIntelCeleron=intval(@file_get_contents("/etc/artica-postfix/settings/Daemons/EnableIntelCeleron"));

    $l[]="master_version=$HaproxyVersion";
    $l[]="service_cmd=/etc/init.d/adagent";
    $l[]="service_disabled=$EnableHaProxy";
    $l[]="pid_path=$pid_path";
    $l[]="watchdog_features=1";
    $l[]="family=network";
    if($GLOBALS["VERBOSE"]){echo __LINE__."]********* adagentCronStatistics() *********\n";}
    ADAgentCronStatistics();

    if($EnableHaProxy==0){
        $l[]="";return implode("\n",$l);
    }
    $PPID=adagent_pid();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
        if(!$GLOBALS["DISABLE_WATCHDOG"]){
            squid_admin_mysql(0, "adagent service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
            $cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.php --start >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
        $l[]="";
        return implode("\n",$l);

    }

    if(!$GLOBALS["DISABLE_WATCHDOG"]){
        $unix=new unix();
        if(!$unix->is_socket("/var/run/adagent.stat")){
            squid_admin_mysql(0, "adagent socket /var/run/adagent.stat not running [ {action} = {restart} ]", null,__FILE__,__LINE__);
            $cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.php --restart >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }
    }

    if($EnableIntelCeleron==0){
        $pidtime="/etc/artica-postfix/pids/exec.ad-agent.events-parser.php.scan.time";
        $TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
        if($TimeExec>5){
            $cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.events-parser.php >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

        $pidtime="/usr/share/artica-postfix/ressources/interface-cache/ADAGENT_DAY";
        $TimeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidtime);
        if($TimeExec>15){
            $cmd=trim("{$GLOBALS["NICE"]} {$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.dashboard.php >/dev/null 2>&1 &");
            shell_exec2($cmd);
        }

    }


    $l[]=GetMemoriesOf($PPID,"APP_ADAGENT_LBL");
    $l[]="";


    return implode("\n",$l);return;
}

//========================================================================================================================================================
function ADAgentCronStatistics(){
    $ENABLED=true;
    $SquidPerformance=0;
    $SquidStatsMonthQueue=0;

    // if > 1 then no stats $SquidPerformance
    $InfluxSyslogRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxSyslogRemote"));
    $InfluxUseRemote=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("InfluxUseRemote"));
    $EnableHaProxy=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableExternalACLADAgent"));
    if($InfluxUseRemote==0){$InfluxSyslogRemote=0;}

    if(is_file("/etc/artica-postfix/STATS_APPLIANCE")){
        $InfluxUseRemote=0;
        $SquidPerformance=0;

    }

    if($InfluxUseRemote==1){$ENABLED=false;}
    if($EnableHaProxy==0){$ENABLED=false;}

    if($ENABLED){
        if(!is_file("/etc/cron.d/adagent-monthly")){
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("haproxy-monthly","30 1 * * *","exec.ad-agent.stats.compress.php");
            shell_exec2("/etc/init.d/cron reload");
        }
        if(!is_file("/etc/cron.d/adagent-yearly")){
            $GLOBALS["CLASS_UNIX"]->Popuplate_cron_make("haproxy-yearly","15 3 1 * *","exec.ad-agent.stats.compress.php --compress-year");
            shell_exec2("/etc/init.d/cron reload");
        }


        ADAgentCronStatistics_run();
        ADAgentCronStatistics_year_run();
    }

    if(!$ENABLED){
        if(is_file("/etc/cron.d/adagent-monthly")){
            @unlink("/etc/cron.d/adagent-monthly");
            shell_exec2("/etc/init.d/cron reload");
        }
        if(is_file("/etc/cron.d/adagent-yearly")){
            @unlink("/etc/cron.d/adagent-yearly");
            shell_exec2("/etc/init.d/cron reload");
        }

    }

}
function ADAgentCronStatistics_run(){
    if(!is_file("/etc/artica-postfix/settings/Daemons/adagentStatsMonthQueue")){return;}
    $SquidStatsMonthQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("adagentStatsMonthQueue"));
    if($SquidStatsMonthQueue==0){return;}
    if(system_is_overloaded()){return;}
    $pidfile="/etc/artica-postfix/pids/adagentStatsMonthQueue.pid";
    $pidTime="/etc/artica-postfix/pids/adagentStatsMonthQueue.time";
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
    $timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
    if($timeExec<15){return;}
    $cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.stats.compress.php >/dev/null 2>&1 &";
    shell_exec2(trim($cmd));
}
function ADAgentCronStatistics_year_run(){

    $cmd="{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.ad-agent.stats.compress.php --compress-year >/dev/null 2>&1 &";
    if(!is_file("/etc/artica-postfix/settings/Daemons/adagentStatsYearQueue")){return;}
    $HaproxyStatsYearQueue=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("adagentStatsYearQueue"));
    if($HaproxyStatsYearQueue==0){return;}
    if(system_is_overloaded()){return;}
    $pidfile="/etc/artica-postfix/pids/adagentStatsYearQueue.pid";
    $pidTime="/etc/artica-postfix/pids/adagentStatsYearQueue.time";
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file($pidfile);
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return;}
    $timeExec=$GLOBALS["CLASS_UNIX"]->file_time_min($pidTime);
    if($timeExec<15){return;}
    shell_exec2(trim($cmd));
}