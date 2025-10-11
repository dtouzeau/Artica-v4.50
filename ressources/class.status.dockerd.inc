<?php
function dockerd_pid(){
    $pid=$GLOBALS["CLASS_UNIX"]->get_pid_from_file("/var/run/docker/docker.pid");
    if($GLOBALS["CLASS_UNIX"]->process_exists($pid)){return $pid;}
    $Masterbin=$GLOBALS["CLASS_UNIX"]->find_program("dockerd");
    return $GLOBALS["CLASS_UNIX"]->PIDOF($Masterbin);
}

function dockerd():string{

    $status_file=PROGRESS_DIR."/dockerd.status";
	if($GLOBALS["VERBOSE"]){echo __LINE__."]********* dockerd() *********\n";}
	$binpath=$GLOBALS["CLASS_UNIX"]->find_program("dockerd");
	
	$l[]="[APP_DOCKER]";
	$l[]="service_name=APP_DOCKER";
	
	if(!is_file($binpath)){
		if($GLOBALS["VERBOSE"]){echo  __LINE__."] ********* dockerd no such file...*********\n";}
		$GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DOCKER_INSTALLED", 0);
		$l[]="running=0\ninstalled=0";
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
		return @implode("\n", $l);
		
	}
    $EnableDockerService=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("EnableDockerService"));

    if($EnableDockerService==0){
        if(is_file("/etc/init.d/docker")){
            squid_admin_mysql(0, "Docker Dameon service {installed} [{action}={uninstall}]",
                "EnableDockerService si set to 0 has the init.d exists",__FILE__,__LINE__);
            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.docker.php --uninstall");
        }
        $l[]="running=0\ninstalled=0";
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
        return @implode("\n", $l);
    }

    if(!is_file("/etc/init.d/docker")){
        squid_admin_mysql(0, "Docker Dameon service {not_installed} [{action}={install}]",
            "EnableDockerService si set to 1 has the init.d doesn`t exists",__FILE__,__LINE__);
        $GLOBALS["CLASS_UNIX"]->framework_exec("exec.docker.php --install");
        $l[]="running=0\ninstalled=0";
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
        return @implode("\n", $l);
    }


    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("APP_DOCKER_INSTALLED", 1);
	$Version=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");

	$l[]="master_version=$Version";
	$l[]="service_cmd=/etc/init.d/docker";
	$l[]="service_disabled=1";
	$l[]="pid_path=/var/run/docker.pid";
	$l[]="watchdog_features=1";
	$l[]="family=network";

	$PPID=dockerd_pid();
	
	if(!$GLOBALS["CLASS_UNIX"]->process_exists($PPID)){
		if(!$GLOBALS["DISABLE_WATCHDOG"]){
			if($GLOBALS["CLASS_UNIX"]->ServerRunSince()>3){
                squid_admin_mysql(0, "Docker Dameon service {stopped} [{action}={start}]", null,__FILE__,__LINE__);
            }

            $GLOBALS["CLASS_UNIX"]->framework_exec("exec.docker.php --start");
            $GLOBALS["CLASS_UNIX"]->RESTART_SYSLOG(true);
		}
		$l[]="";
        @file_put_contents($status_file,@implode("\n",$l));
        @chmod($status_file,0755);
		return implode("\n",$l);
	}

	
	$l[]=GetMemoriesOf($PPID,"APP_DOCKER");
	$l[]="";

    @file_put_contents($status_file,@implode("\n",$l));
    @chmod($status_file,0755);
	return implode("\n",$l);

}

//==================================================================================================================