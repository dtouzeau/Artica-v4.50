<?php

function CIESCACHE_PID_NUM(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file('/var/run/categories-cache/categories-cache.pid');
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    return $GLOBALS["CLASS_UNIX"]->PIDOF("/usr/bin/categories-cache");

}

function CIESCACHE_STATUS():string{

    $EnableCategoriesCache=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableCategoriesCache"));

    $l[]="[APP_CATEGORIES_CACHE]";
    $l[]="service_name=APP_CATEGORIES_CACHE";
    $l[]="service_cmd=/etc/init.d/categories-cache";
    $l[]="family=system";
    $l[]="watchdog_features=1";
    $l[]="service_disabled=$EnableCategoriesCache";

    if($EnableCategoriesCache==0) {
        if (is_file("/etc/init.d/categories-cache")) {
            squid_admin_mysql(1, "{APP_CATEGORIES_CACHE} {remove_this_prog}", null, __FILE__, __LINE__);
            $cmd = trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.categories-cache.php --install");
            shell_exec2($cmd);
            @file_put_contents(PROGRESS_DIR."/categories-cache.status",@implode("\n",$l));

        }
        return implode("\n", $l);
    }

    if($EnableCategoriesCache==1) {
        if (!is_file("/etc/init.d/categories-cache")) {
            squid_admin_mysql(1, "{APP_CATEGORIES_CACHE} {INSTALL_THIS_SERVICE}", null, __FILE__, __LINE__);
            $cmd = trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.categories-cache.php --install");
            shell_exec2($cmd);
            @file_put_contents(PROGRESS_DIR."/categories-cache.status",@implode("\n",$l));
            return implode("\n", $l);

        }
    }




    $master_pid=CIESCACHE_PID_NUM();

    if(!$GLOBALS["CLASS_UNIX"]->process_exists($master_pid)) {
        if (!$GLOBALS["DISABLE_WATCHDOG"]) {
            if ($GLOBALS["CLASS_UNIX"]->ServerRunSince() > 3) {
                squid_admin_mysql(0,
                    "{warning} {APP_CATEGORIES_CACHE} {service_stopped} [{action}={start}]", null, __FILE__, __LINE__);
            }
            $cmd = trim("{$GLOBALS["NICE"]}{$GLOBALS["PHP5"]} /usr/share/artica-postfix/exec.categories-cache.php --start");
            shell_exec2($cmd);
        }

        $l[] = "master_pid=$master_pid";
        $l[] = "running=0";
        $l[] = "installed=1";
        $l[] = "";
        @file_put_contents(PROGRESS_DIR."/categories-cache.status",@implode("\n",$l));
        return implode("\n", $l);
    }

    $l[]="running=1";
    $l[]=GetMemoriesOf($master_pid);
    $l[]="";
    @file_put_contents(PROGRESS_DIR."/categories-cache.status",@implode("\n",$l));
    return implode("\n",$l);

}

