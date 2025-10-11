<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
$GLOBALS["YESCGROUP"]=true;
include_once(dirname(__FILE__).'/ressources/class.sockets.inc');
include_once(dirname(__FILE__).'/ressources/class.docker.inc');
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if(isset($argv[1])){
    if($argv[1]=="--stop"){stop();exit();}
    if($argv[1]=="--start"){start();exit();}
    if($argv[1]=="--restart"){restart();exit();}
    if($argv[1]=="--install"){install();exit();}
    if($argv[1]=="--uninstall"){uninstall();exit();}
    if($argv[1]=="--move-workdir"){move_working_directory();exit();}
    if($argv[1]=="--impport-image"){import_image($argv[2],$argv[3]);exit;}
    if($argv[1]=="--export-container"){export_container($argv[2],$argv[3]);exit;}
    if($argv[1]=="--download-container"){pull($argv[2],$argv[3]);exit;}
    if($argv[1]=="--remove-container"){container_remove($argv[2]);exit;}
    if($argv[1]=="--remove-volume"){volume_remove($argv[2]);exit;}


    if($argv[1]=="--list-images"){list_images();exit;}
    if($argv[1]=="--list-images-running"){images_running();exit;}

    if($argv[1]=="--commit"){commit_image($argv[2]);exit;}

    if($argv[1]=="--shellinabox-install"){shellinaboxd_install($argv[2]);}
    if($argv[1]=="--shellinaboxd-start"){shellinaboxd_start($argv[2]);exit;}
    if($argv[1]=="--shellinaboxd-stop"){shellinaboxd_stop($argv[2]);exit;}
    if($argv[1]=="--shellinaboxd-restart"){shellinaboxd_restart($argv[2]);exit;}
    if($argv[1]=="--shellinaboxd-scan"){shellinaboxd_scan();exit;}

    if($argv[1]=="--stop-container"){stop_container($argv[2]);exit;}
    if($argv[1]=="--link-container"){container_link($argv[2],$argv[3]);}
    if($argv[1]=="--isyatch"){isYatchInstalled();exit;}
    if($argv[1]=="--yacht-remove"){yacht_remove();exit;}
    if($argv[1]=="--yacht-install"){yacht_install();exit;}
    if($argv[1]=="--docker-stats"){docker_stats();exit;}
    if($argv[1]=="--volumeloopback-install"){volumeloopback_install();}
    if($argv[1]=="--volumeloopback-uninstall"){volumeloopback_uninstall();}
    if($argv[1]=="--plugins"){plugins_list();exit;}
    if($argv[1]=="--container-entrypoint"){docker_change_entrypoint();exit;}
}

function shellinaboxd_pid($ID):int{
    $unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/docker.shellinabox.$ID.pid");
    if($unix->process_exists($pid)){return $pid;}
    return $unix->PIDOF_PATTERN("shellinaboxd.*?docker.shellinabox.$ID.pid");
}
function shellinaboxd_out($ID,$text):bool{
    $prefix="ShellInaBox:$ID......: ".date("H:i:s");
    echo "$prefix $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("dockerd", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, "ShellInABox:$ID: $text");
    closelog();
    return true;
}
function shellinaboxd_start($ID):bool{
    if($ID==null){return false;}
    $unix=new unix();
    $pid=shellinaboxd_pid($ID);

    if($unix->process_exists($pid)){
        $timepid=$unix->PROCCESS_TIME_MIN($pid);
        shellinaboxd_out($ID,"Service already started $pid since {$timepid}Mn..");
        @file_put_contents("/var/run/docker.shellinabox.$ID.pid", $pid);
        return true;
    }
    $shellinaboxd=$unix->find_program("shellinaboxd");


    $pid=shellinaboxd_pid($ID);
    if($unix->process_exists($pid)){
        $time=$unix->PROCESS_TTL($pid);
        shellinaboxd_out($ID,"Shellinaboxd already running $pid since $time");
        return true;
    }


    $docker=$unix->find_program("docker");
    $t[]="$shellinaboxd";
    $t[]="--background=/var/run/docker.shellinabox.$ID.pid";
    $t[]="--disable-ssl";
    $t[]="--numeric";
    $t[]="--localhost-only";
    $t[]="--user=0";
    $t[]="--group=0";
    $t[]="--no-beep --service='/:root:root:/:$docker exec --workdir /root -it $ID /bin/bash'";
    $t[]="--unixdomain-only=/var/run/docker.shellinabox.$ID.sock:www-data:www-data:0755";
    $cmd=@implode(" ", $t);
    $sh=$unix->sh_command($cmd);
    shellinaboxd_out($ID,"Starting shellinaboxd for instance $ID");
    $unix->go_exec($sh);

    for($i=1;$i<5;$i++){
        shellinaboxd_out($ID,"Starting, Waiting $i/5");
        sleep(1);
        $pid=shellinaboxd_pid($ID);
        if($unix->process_exists($pid)){break;}
    }

    $pid=shellinaboxd_pid($ID);
    if($unix->process_exists($pid)){
        shellinaboxd_out($ID,"Starting, Success PID $pid");
        return true;

    }
    shellinaboxd_out($ID,"Starting Failed");
    shellinaboxd_out($ID,"$cmd");
    return false;

}

function restart():bool{
	$unix=new unix();
	$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
	$pid=$unix->get_pid_from_file($pidfile);
	if($unix->process_exists($pid,basename(__FILE__))){
		$time=$unix->PROCCESS_TIME_MIN($pid);
		_out("Already Artica task running PID $pid since {$time}mn");
		return false;
	}


	@file_put_contents($pidfile, getmypid());

    build_progress("{stopping}",50);
	if(!stop(true)){
        return build_progress("{stopping} {failed}",110);
    }
	sleep(1);
    build_progress("{starting}",70);
	if(!start(true)){
        return build_progress("{starting} {failed}",110);
    }

    return build_progress("{starting} {success}",100);
	
}
function pull_progress($text,$pourc,$md5):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"docker.install.$md5");
}


function export_container_progress($text,$pourc,$ID):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"docker.export.$ID");
}
function export_container($Name,$ID):bool{
    $unix=new unix();

    $pid=$unix->PIDOF_PATTERN("docker export $ID");
    if($unix->process_exists($pid)){
        export_container_progress("{exporting} $Name already running $pid",110,$ID);
        return true;
    }

    export_container_progress("{exporting} $Name",20,$ID);
    $docker=$unix->find_program("docker");
    $nohup=$unix->find_program("nohup");
    $tdir="/home/docker-export";
    $tfile="$tdir/$ID";
    if(!is_dir($tdir)){
        @mkdir($tdir,0755,true);
        @chown($tdir,"www-data");
    }

    if(is_file($tfile)){
        export_container_progress("{exporting} $Name {done}",100,$ID);
        return true;
    }

    $gzip=$unix->find_program("gzip");
    shell_exec("$nohup $docker export $ID | $gzip -9 >$tfile &");
    sleep(1);
    $prc=20;
    for ($i=0;$i<500;$i++){
        $pid=$unix->PIDOF_PATTERN("docker export $ID");
        if($unix->process_exists($pid)){
            $prc++;
            if($prc>98){$prc=98;}
            $size=$unix->FormatBytes(filesize($tfile)/1024);
            export_container_progress("{exporting} $Name - $size",$prc,$ID);
            sleep(5);
            continue;
        }
        break;
    }
    return export_container_progress("{exporting} $Name {done}",100,$ID);
}

function pull($imagename,$md5):bool{
    $unix=new unix();
    $tail=$unix->find_program("tail");
    pull_progress("{downloading} $imagename",20,$md5);
    $docker=$unix->find_program("docker");
    $nohup=$unix->find_program("nohup");
    $logile=PROGRESS_DIR."/docker.install.$md5.log";
    echo "$docker pull $imagename\n";
    shell_exec("$nohup $docker pull $imagename >>$logile 2>&1 &");
    sleep(1);
    $prc=20;
    for ($i=0;$i<80;$i++){
        $pid=$unix->PIDOF_PATTERN("pull $imagename");
        if($unix->process_exists($pid)){
            $prc++;
            $rr=exec("$tail -n 1 $logile");
            pull_progress("$rr",$prc,$md5);
            sleep(2);
            continue;
        }
        break;
    }

    $f=explode("\n",@file_get_contents($logile));
    foreach ($f as $line){
        if(preg_match("#Error response from daemon#i",$line)){
            echo $line ."\n";
            return pull_progress("$line",110,$md5);
        }

    }
        //Error response from daemon

    $list_images=list_images();
    if(!isset($list_images[$imagename])){
        return pull_progress("{downloading} $imagename {failed}",110,$md5);

    }

    pull_progress("{downloading} $imagename {done}",100,$md5);
    return true;
}

function container_remove_progress($prc,$text,$ID):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker.rm.$ID.progress");
}
function container_link_progress($prc,$text,$md5):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker.link.$md5");
}
function container_link($image,$md5):bool{
    $unix=new unix();

    $DockerContainersSettings=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerContainersSettings"));
    if(!isset($DockerContainersSettings[$image])){$DockerContainersSettings[$image]=array();}


    if(!isset($ContainersSettings["HOSTNAME"])){
        $ContainersSettings["HOSTNAME"]=null;
    }
    if(!isset($ContainersSettings["NAME"])){
        $ContainersSettings["NAME"]=null;
    }



    $docker=new dockerd();


    if( $ContainersSettings["NAME"]<>null){
        $array["name"]=$ContainersSettings["NAME"];
    }
    if( $ContainersSettings["HOSTNAME"]<>null){
        $array["hostname"]=$ContainersSettings["HOSTNAME"];
    }
    if($ContainersSettings["CHANGEENTRYPOINT"]==1){
        if($ContainersSettings["ENTRYPOINT"]<>null){
            $array["entrypoint"]=$ContainersSettings["ENTRYPOINT"];

        }
        if($ContainersSettings["USETAIL"]==1){
            $array["entrypoint"]="/usr/bin/tail -f /dev/null";
        }


    }
    $array["md5_out"]=$md5;
    $array["image"]=$image;

    $tfile=PROGRESS_DIR."/docker.$md5.run";
    $LL=array();
    container_link_progress(20,"{link} $image",$md5);
    if(!$docker->unix_run_container($array)){
        return container_link_progress(110,"{link} $image {failed}",$md5);

    }

    _out("Link image $image ".@implode(" ",$LL));
    $results=explode("\n",@file_get_contents($tfile));
    container_link_progress(50,"{link} $image",$md5);

    foreach ($results as $index=>$line){
        if(preg_match("#See.*?--help#",$line)){
            unset($results[$index]);
            continue;
        }
        _out("Link image: $line");
        writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
    }

    @file_put_contents($tfile,@implode("\n",$results));
    @chmod($tfile,0755);
    container_link_progress(100,"{link} $image",$md5);
    return true;

}
function yacht_progress($prc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker-yacht.progress");
}
function yacht_remove():bool{
    isYatchInstalled();
    $DockerYachtID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerYachtID"));
    yacht_progress("{APP_YACHT} {remove}",20);
    _out("Removing Container Yatch $DockerYachtID");
    if($DockerYachtID==null){
        yacht_progress("{APP_YACHT} {remove} {failed} No ID",110);
        return false;
    }
    $unix=new unix();
    yacht_progress("{APP_YACHT} {remove}",30);
    $docker=$unix->find_program("docker");
    exec("$docker rm -f $DockerYachtID 2>&1",$results);
    foreach ($results as $line){
        _out("YACHT: (remove) $line");
    }

    if(!isYatchInstalled()){
        if(is_file("/etc/nginx/docker/yacht.conf")){@unlink("/etc/nginx/docker/yacht.conf");}
        return yacht_progress("{APP_YACHT} {remove} {success}",100);
    }
    return yacht_progress("{APP_YACHT} {remove} {failed}",110);

}
function yacht_nginx():bool{
    $f=array();
    @file_put_contents("/etc/nginx/docker/yacht.conf",@implode("\n",$f));
    shell_exec("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/webconsole.conf -s reload");
    return true;
}
function volumeloopback_uninstall():bool{
    yacht_progress("{DockerVolumeLoopback} {uninstalling}",30);

    $plugins=plugins_list();
    if(!isset($plugins["ashald/docker-volume-loopback:latest"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack", 0);
        return yacht_progress("{DockerVolumeLoopback} {success}", 100);
    }

    $unix=new unix();
    $docker=$unix->find_program("docker");
    yacht_progress("{DockerVolumeLoopback} {remove}", 20);
    system("$docker plugin \"disable ashald/docker-volume-loopback:latest\" -f");
    yacht_progress("{DockerVolumeLoopback} {remove}", 50);
    system("$docker plugin rm \"ashald/docker-volume-loopback:latest\" -f");

    $plugins=plugins_list();
    if(!isset($plugins["ashald/docker-volume-loopback:latest"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack", 0);
        return yacht_progress("{DockerVolumeLoopback} {success}", 100);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack",1);
    return yacht_progress("{DockerVolumeLoopback} {failed}", 110);

}
function volumeloopback_install():bool{
    yacht_progress("{DockerVolumeLoopback} {installing}",30);

    $plugins=plugins_list();
    if(isset($plugins["ashald/docker-volume-loopback:latest"])) {
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack", 1);
        return yacht_progress("{DockerVolumeLoopback} {success}", 100);
    }

    $unix=new unix();
    $docker=$unix->find_program("docker");
    $tail=$unix->find_program('tail');
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $docker plugin install ashald/docker-volume-loopback --grant-all-permissions >/var/log/yacht.install 2>&1 &";
    _out($cmd);

    shell_exec($cmd);
    for($i=30;$i<100;$i++){
        $pid=$unix->PIDOF_PATTERN("docker plugin.*?docker-volume-loopback");
        if($unix->process_exists($pid)){
            $line=exec("$tail -n 1 /var/log/yacht.install 2>&1");
            _out($line);
            yacht_progress("{DockerVolumeLoopback} $line",$i);
            sleep(2);
            continue;
        }
        break;
    }
    $plugins=plugins_list();
    if(isset($plugins["ashald/docker-volume-loopback:latest"])) {
        if(!is_dir("/home/docker/docker-volume-loopback")){@mkdir("/home/docker/docker-volume-loopback",0755,true);}
        yacht_progress("{DockerVolumeLoopback} $line",80);
        shell_exec("$docker plugin disable ashald/docker-volume-loopback");
        yacht_progress("{DockerVolumeLoopback} $line",85);
        shell_exec("$docker plugin set ashald/docker-volume-loopback DATA_DIR=\"/home/docker/docker-volume-loopback\"");
        yacht_progress("{DockerVolumeLoopback} $line",90);
        shell_exec("$docker plugin enable ashald/docker-volume-loopback");
        yacht_progress("{DockerVolumeLoopback} $line",95);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack", 1);
        return yacht_progress("{DockerVolumeLoopback} {success}", 100);
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack",0);
    return yacht_progress("{DockerVolumeLoopback} {failed}", 110);
}
function yacht_install():bool{
    if(isYatchInstalled()){
        yacht_nginx();
        return yacht_progress("{APP_YACHT} {install} {success}",100);

    }
    $unix=new unix();
    yacht_progress("{APP_YACHT} {installing}",30);
    $docker=$unix->find_program("docker");
    $tail=$unix->find_program('tail');
    $nohup=$unix->find_program("nohup");
    $cmd="$nohup $docker run -d -p 0.0.0.0:3545:8000 -v /var/run/docker.sock:/var/run/docker.sock -v yacht:/config --name ArticaYacht selfhostedpro/yacht >/var/log/yacht.install 2>&1 &";
    shell_exec($cmd);
    for($i=30;$i<100;$i++){
        $pid=$unix->PIDOF_PATTERN("docker run.*?ArticaYacht");
        if($unix->process_exists($pid)){
            $line=exec("$tail -n 1 /var/log/yacht.install 2>&1");
            _out($line);
            yacht_progress("{APP_YACHT} $line",$i);
            sleep(2);
            continue;
        }
        break;

    }
    if(isYatchInstalled()){
        yacht_progress("{APP_YACHT} {success}",100);
        yacht_nginx();
        return true;
    }

    return yacht_progress("{APP_YACHT} {failed}",110);
}

function psids():array{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $MAIN=array();
    exec("$docker ps -a --no-trunc --format json 2>&1",$results);
    foreach ($results as $line) {
        $line = trim($line);
        $json = json_decode($line);
        $ID = $json->ID;
        $MAIN[$ID] = true;
    }
    return $MAIN;
}

function isYatchInstalled():bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    exec("$docker ps -a --no-trunc --format json 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        $json=json_decode($line);
        $Image=$json->Image;
        if($Image<>"selfhostedpro/yacht"){continue;}
        $Ports=$json->Ports;
        $Names=$json->Names;
        $ID=$json->ID;
        if($Names<>"ArticaYacht"){continue;}
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerYachtInstalled",1);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerYachtID",$ID);
        if(preg_match("#^.*?:([0-9]+)->([0-9]+)#",$Ports,$re)){
            $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerYachtPort",$re[1]);
        }
        shellinaboxd_scan();
        return true;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerYachtInstalled",0);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerYachtID","");
    shellinaboxd_scan();
    return false;

}


function volume_remove($volume):bool{
    $unix=new unix();
    if($volume==null){
        container_remove_progress(110,"{remove} {failed} NO volume specified",$volume);
        return false;
    }
    container_remove_progress(10,"{remove} $volume",$volume);
    $docker=$unix->find_program("docker");
    exec("$docker volume rm -f $volume 2>&1",$results);
    foreach ($results as $line){
        container_remove_progress(20,"$line",$volume);
    }
    return container_remove_progress(100,"{remove}...",$volume);

}

function container_remove($ID):bool{
    $unix=new unix();
    if($ID==null){
        container_remove_progress(110,"{remove} {failed} NO ID",$ID);
        return false;
    }
    container_remove_progress(10,"{remove} $ID",$ID);
    $docker=$unix->find_program("docker");
    exec("$docker rm -f $ID 2>&1",$results);
    foreach ($results as $line){
        container_remove_progress(20,"$line",$ID);
    }
    container_remove_progress(30,"{remove}...",$ID);
    if(is_file("/etc/nginx/docker/$ID.conf")){
        @unlink("/etc/nginx/docker/$ID.conf");
        shell_exec("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/webconsole.conf -s reload");
    }

    container_remove_progress(40,"{remove}...",$ID);
    shellinaboxd_remove($ID);
    isYatchInstalled();
    clean_cache();
    container_remove_progress(100,"{remove} {success}...",$ID);
    return true;
}
function clean_cache():bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    shell_exec("$rm -f ".PROGRESS_DIR."/docker.*");
    shell_exec("$rm -f ".PROGRESS_DIR."/image.*");
    shell_exec("$rm -f ".PROGRESS_DIR."/inspect.*");
    return true;
}
function shellinaboxd_monit($ID):bool{
    $unix=new unix();
    @unlink("/etc/monit/conf.d/$ID.monitrc");
    $f[]="check process $ID with pidfile /var/run/docker.shellinabox.$ID.pid";
    $f[]="\tstart program = \"/etc/init.d/dock-container-$ID start\"";
    $f[]="\tstop program = \"/etc/init.d/dock-container-$ID stop\"";
    $f[]="\tif failed unixsocket /var/run/docker.shellinabox.$ID.sock then restart";
    $f[]="";

    @file_put_contents("/etc/monit/conf.d/$ID.monitrc", @implode("\n", $f));
    $unix->reload_monit();
    return true;
}
function shellinaboxd_nginx($ID):bool{
    $f[]="\tlocation /$ID/ {";
    $f[]="\t\tif (\$http_cookie !~ \"shellinaboxCooKie=[0-9]\") {";
    $f[]="\t\t\treturn 403;";
    $f[]="\t}";
    $f[]="\t\tproxy_pass http://unix:/var/run/docker.shellinabox.$ID.sock:/;";
    $f[]="\t\tproxy_redirect default;";
    $f[]="\t\tproxy_set_header Host \$host;";
    $f[]="\t\tproxy_set_header X-Real-IP \$remote_addr;";
    $f[]="\t\tproxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;";
    $f[]="\t\tclient_max_body_size 10m;";
    $f[]="\t\tclient_body_buffer_size 128k;";
    $f[]="\t\tproxy_connect_timeout 90;";
    $f[]="\t\tproxy_send_timeout 90;";
    $f[]="\t\tproxy_read_timeout 90;";
    $f[]="\t\tproxy_buffer_size 4k;";
    $f[]="\t\tproxy_buffers 4 32k;";
    $f[]="\t\tproxy_busy_buffers_size 64k;";
    $f[]="\t\tproxy_temp_file_write_size 64k;";
    $f[]="\t}";

    if(!is_dir("/etc/nginx/docker")){
        @mkdir("/etc/nginx/docker",0755,true);
    }
    @file_put_contents("/etc/nginx/docker/$ID.conf",@implode("\n",$f));
    shell_exec("/usr/local/ArticaWebConsole/sbin/artica-webconsole -c /etc/artica-postfix/webconsole.conf -s reload");
    return true;
}

function shellinaboxd_scan():bool{
    $psids=psids();

    $plugins=plugins_list();
    if(isset($plugins["ashald/docker-volume-loopback:latest"])){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack",1);
    }else{
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockVolumeLoopBack",0);
    }



    $data=scandir("/etc/init.d");
    foreach ($data as $filename){

        if(!preg_match("#dock-container-(.+)#",$filename,$re)){continue;}
        $ID=$re[1];

        if(!isset($psids[$ID])){
           _out("Uninstall ShellInBox for container $ID (not exists)");
            shellinaboxd_remove($ID);
        }
    }
    return true;

}
function shellinaboxd_remove($ID):bool{
    $unix=new unix();
    $WEB_INITD_PATH = "/etc/init.d/dock-container-$ID";
    $MONIT_PATH = "/etc/monit/conf.d/$ID.monitrc";
    if (is_file($WEB_INITD_PATH)) {
        $unix->remove_service($WEB_INITD_PATH);
    }
    if (is_file($MONIT_PATH)) {
        @unlink($MONIT_PATH);
        $unix->reload_monit();
    }
    return true;
}

function shellinaboxd_install($ID):bool{
    if($ID==null){return false;}
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/dock-container-$ID";
    $php5script="exec.docker.php";
    $daemonbinLog="Docker Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         docker-web-service-$ID";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --shellinaboxd-start $ID \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --shellinaboxd-stop $ID \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --shellinaboxd-restart $ID \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }
    shell_exec("$INITD_PATH start");
    shellinaboxd_nginx($ID);
    shellinaboxd_monit($ID);
    return true;

}

function build_progress($text,$pourc):bool{
    $unix=new unix();
    return $unix->framework_progress($pourc,$text,"docker.progress");
}

function install():bool{
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDockerService",1);
    build_progress("{installing}",20);
    create_service();
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $SERVICES["/etc/init.d/k5start"]="/usr/sbin/artica-phpfpm-service -uninstall-k5start";
    $SERVICES["/etc/init.d/glances"]="exec.glances.php --uninstall";
    $SERVICES["/etc/init.d/proxy-pac"]="/usr/sbin/artica-phpfpm-service -uninstall-proxypac";
    $SERVICES["/etc/init.d/ufdb"]="/usr/sbin/artica-phpfpm-service -uninstall-ufdb";
    $SERVICES["/etc/init.d/postfix"]="exec.postfix-install.php --uninstall";
    $SERVICES["/etc/init.d/squid-logger"]="exec.squid-logger.php --uninstall";
    $SERVICES["/etc/init.d/squid"]="/usr/sbin/artica-phpfpm-service -uninstall-proxy";
    $SERVICES["/etc/init.d/nginx"]="/usr/sbin/artica-phpfpm-service -nginx-uninstall";
    $SERVICES["/etc/init.d/dnsfilterd"]="exec.dnsfilterd.php --uninstall";
    $SERVICES["/etc/init.d/c-icap"]="/usr/sbin/artica-phpfpm-service -uninstall-cicap";
    $SERVICES["/etc/init.d/clamav-daemon"]="/usr/sbin/artica-phpfpm-service -uninstall-clamd";
    $SERVICES["/etc/init.d/web-error-page"]="/usr/sbin/artica-phpfpm-service -uninstall-weberror";
    $d=20;
    foreach ($SERVICES as $init=>$cmd){
        if(!is_file($init)){continue;}
        $d++;
        $ServName=basename($init);
        if($d>50){$d=50;}
        build_progress("{uninstalling} $ServName",$d);
        if (strpos($cmd,"artica-phpfpm-service")>1){
            shell_exec($cmd);
            continue;
        }

        shell_exec("$php /usr/share/artica-postfix/$cmd");
    }

    $unix->Popuplate_cron_make("docker-stats","*/3 * * * *",basename(__FILE__)." --docker-stats");
    build_progress("{starting}",50);
    start();
    build_progress("{starting}",60);

    docker_stats_dirsize();
    build_progress("{success}",100);
    return true;
}
function plugins_list():array{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    exec("$docker plugin ls --no-trunc --format json 2>&1",$results);
    $MAIN=array();
    foreach ($results as $line){
        $json=json_decode($line);
        $Name=$json->Name;
        $ID=$json->ID;
        $MAIN[$Name]=$ID;
    }
    return $MAIN;
}


function uninstall():bool{
    $unix=new unix();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("EnableDockerService",0);
    build_progress("{uninstalling}",20);

    if(is_file("/usr/sbin/cgroupfs-mount")){@unlink("/usr/sbin/cgroupfs-mount");}
    if(is_file("/usr/sbin/cgroupfs-umount")){@unlink("/usr/sbin/cgroupfs-umount");}

    $unix->Popuplate_cron_delete("docker-stats");
    $DockerDataRoot=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRoot"));
    if($DockerDataRoot==null){$DockerDataRoot="/home/docker";}


    $subdirs[]="buildkit";
    $subdirs[]="containerd";
    $subdirs[]="containers";
    $subdirs[]="docker-volume-loopback";
    $subdirs[]="engine-id";
    $subdirs[]="image";
    $subdirs[]="network";
    $subdirs[]="overlay2";
    $subdirs[]="plugins";
    $subdirs[]="rrd";
    $subdirs[]="runtimes";
    $subdirs[]="swarm";
    $subdirs[]="tmp";
    $subdirs[]="volumes";
    foreach ($subdirs as $directory){
        $DIRS[]="$DockerDataRoot/$directory";
    }

    $rm=$unix->find_program("rm");
    $DIRS[]="/home/docker";
    $DIRS[]="/home/docker-export";

    foreach ($DIRS as $directory){
        build_progress("{removing} $directory",30);
        shell_exec("$rm -rf $directory");
    }

    $INITD_PATH="/etc/init.d/docker";
    $MONIT_PATH="/etc/monit/conf.d/APP_DOCKER.monitrc";
    $unix->remove_service($INITD_PATH);

    $data=scandir("/etc/init.d");
    foreach ($data as $filename){
        if(!preg_match("#dock-container-(.+)#",$filename,$re)){continue;}
        $ID=$re[1];
        shellinaboxd_remove($ID);
    }


    if(is_file($MONIT_PATH)){
        @unlink($MONIT_PATH);
        $unix->MONIT_RELOAD();
    }

    build_progress("{uninstalling} {success}",100);
    return true;
}




function create_service(){
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $INITD_PATH="/etc/init.d/docker";
    $php5script="exec.docker.php";
    $daemonbinLog="Docker Daemon";



    $f[]="#!/bin/sh";
    $f[]="### BEGIN INIT INFO";
    $f[]="# Provides:         docker-service";
    $f[]="# Required-Start:    \$local_fs \$syslog";
    $f[]="# Required-Stop:     \$local_fs \$syslog";
    $f[]="# Should-Start:";
    $f[]="# Should-Stop:";
    $f[]="# Default-Start:     3 4 5";
    $f[]="# Default-Stop:      0 1 6";
    $f[]="# Short-Description: $daemonbinLog";
    $f[]="# chkconfig: - 80 75";
    $f[]="# description: $daemonbinLog";
    $f[]="### END INIT INFO";

    $f[]="case \"\$1\" in";
    $f[]=" start)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --start \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  stop)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --stop \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" restart)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --restart \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]=" reconfigure)";
    $f[]="    $php /usr/share/artica-postfix/$php5script --build \$2 \$3";
    $f[]="    ;;";
    $f[]="";
    $f[]="  *)";
    $f[]="    echo \"Usage: \$0 {start|stop|restart|reconfigure} (+ '--verbose' for more infos)\"";
    $f[]="    exit 1";
    $f[]="    ;;";
    $f[]="esac";
    $f[]="exit 0\n";


    echo "$daemonbinLog: [INFO] Writing $INITD_PATH with new config\n";
    @unlink($INITD_PATH);
    @file_put_contents($INITD_PATH, @implode("\n", $f));
    @chmod($INITD_PATH,0755);

    if(is_file('/usr/sbin/update-rc.d')){
        shell_exec("/usr/sbin/update-rc.d -f " .basename($INITD_PATH)." defaults >/dev/null 2>&1");
    }

    if(is_file('/sbin/chkconfig')){
        shell_exec("/sbin/chkconfig --add " .basename($INITD_PATH)." >/dev/null 2>&1");
        shell_exec("/sbin/chkconfig --level 345 " .basename($INITD_PATH)." on >/dev/null 2>&1");
    }


}
function _out($text):bool{
    $prefix="dockerd......: ".date("H:i:s");
    echo "$prefix $text\n";
    if(!function_exists("openlog")){return false;}
    openlog("dockerd", LOG_PID , LOG_SYSLOG);
    syslog(LOG_INFO, $text);
    closelog();
    return true;
}
function start($aspid=false):bool{
	$unix=new unix();
	$Masterbin=$unix->find_program("dockerd");

	if(!is_file($Masterbin)){
		_out("Dockerd not installed");
		return false;
	}

	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Already Artica [starting] task running PID $pid since {$time}mn");
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}
	
	$pid=PID_NUM();

    $DockerDataRootTemp=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRootTemp"));
    if(strlen($DockerDataRootTemp)>3){
        _out("Moving directory in progress...");
        return move_working_directory();

    }

	if($unix->process_exists($pid)){
		$timepid=$unix->PROCCESS_TIME_MIN($pid);
		_out("Service already started $pid since {$timepid}Mn...");
		return false;
	}

    shell_exec("/usr/sbin/articarest -start-docker debug");

	$pid=PID_NUM();
	if($unix->process_exists($pid)) {
        _out("Success PID $pid");
        return true;

    }
    _out("Failed");
    return false;



}
function ismounted():bool{
    $datas = explode("\n", @file_get_contents("/proc/mounts"));
    foreach ($datas as $val) {
        if (preg_match("#cgroup.*?\/cpu\s+cgroup#", $val)) {
            return true;
        }
    }
    return false;
}



function proxy_options():string{
    $unix=new unix();
    $ini=new Bs_IniHandler();
    $datas=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaProxySettings");
    $ArticaProxyServerEnabled=0;
    $ArticaProxyServerUsername=null;
    $ArticaProxyServerUserPassword=null;
    $ArticaProxyServerName=null;
    $ArticaProxyServerPort=3128;

    if(trim($datas)<>null){
        $ini->loadString($datas);
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerEnabled"])){$ini->_params["PROXY"]["ArticaProxyServerEnabled"]=0;}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerName"])){$ini->_params["PROXY"]["ArticaProxyServerName"]="";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerPort"])){$ini->_params["PROXY"]["ArticaProxyServerPort"]="3128";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerUsername"])){$ini->_params["PROXY"]["ArticaProxyServerUsername"]="";}
        if(!isset($ini->_params["PROXY"]["ArticaProxyServerUserPassword"])){$ini->_params["PROXY"]["ArticaProxyServerUserPassword"]="";}


        $ArticaProxyServerEnabled=$ini->_params["PROXY"]["ArticaProxyServerEnabled"];
        $ArticaProxyServerName=$ini->_params["PROXY"]["ArticaProxyServerName"];
        $ArticaProxyServerPort=$ini->_params["PROXY"]["ArticaProxyServerPort"];
        $ArticaProxyServerUsername=trim($ini->_params["PROXY"]["ArticaProxyServerUsername"]);
        $ArticaProxyServerUserPassword=$ini->_params["PROXY"]["ArticaProxyServerUserPassword"];

    }

    $userPP=null;
    if($ArticaProxyServerEnabled==1){
        if($ArticaProxyServerUsername<>null){
            $userPP="$ArticaProxyServerUsername:$ArticaProxyServerUserPassword@";
        }
        return "--http-proxy http://$userPP@$ArticaProxyServerName:$ArticaProxyServerPort";
    }
    $squidbin=$unix->LOCATE_SQUID_BIN();
    if(!is_file($squidbin)){return "--no-proxy \".\"";}
    if(!is_file("/etc/init.d/squid")){return "--no-proxy \".\"";}
    $SQUIDEnable=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SQUIDEnable"));
    if($SQUIDEnable==0) {return "--no-proxy \".\"";}
    $SquidMgrListenPort = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("SquidMgrListenPort"));
    return "--http-proxy http://127.0.0.1:$SquidMgrListenPort";

}
function shellinaboxd_restart($ID):bool{
    if($ID==null){return false;}
    shellinaboxd_stop($ID);
    shellinaboxd_start($ID);
    return true;
}
function shellinaboxd_stop($ID):bool{
    if($ID==null){return false;}
    $unix=new unix();
    $pid=shellinaboxd_pid($ID);

    if(!$unix->process_exists($pid)){
        shellinaboxd_out($ID,"Service already stopped...");
        shell_exec("/usr/sbin/cgroupfs-umount");
        return true;
    }
    $pid=shellinaboxd_pid($ID);
    shellinaboxd_out($ID,"Stopping service Shutdown pid $pid...");
    unix_system_kill($pid);
    for($i=0;$i<5;$i++){
        $pid=shellinaboxd_pid($ID);
        if(!$unix->process_exists($pid)){break;}
        shellinaboxd_out($ID,"Stopping: service waiting pid:$pid $i/5...");
        sleep(1);
    }

    $pid=shellinaboxd_pid($ID);
    if(!$unix->process_exists($pid)){
        shellinaboxd_out($ID,"Stopping service success...");
        return true;
    }

    shellinaboxd_out($ID,"Stopping service shutdown - force - pid $pid...");
    unix_system_kill_force($pid);
    for($i=0;$i<5;$i++){
        $pid=shellinaboxd_pid($ID);
        if(!$unix->process_exists($pid)){break;}
        shellinaboxd_out($ID,"Stopping service waiting pid:$pid $i/5...");
        sleep(1);
    }

    if($unix->process_exists($pid)){
        shellinaboxd_out($ID,"Stopping service failed...");
        return false;
    }

    return true;

}
function stop($aspid=false):bool{
	$unix=new unix();
	if(!$aspid){
		$pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".".__FUNCTION__.".pid";
		$pid=$unix->get_pid_from_file($pidfile);
		if($unix->process_exists($pid,basename(__FILE__))){
			$time=$unix->PROCCESS_TIME_MIN($pid);
			_out("Service Already Artica task running PID $pid since {$time}mn");
			return false;
		}
		@file_put_contents($pidfile, getmypid());
	}

	$pid=PID_NUM();


	if(!$unix->process_exists($pid)){
		_out("Service already stopped...");
        shell_exec("/usr/sbin/cgroupfs-umount");
		return true;
	}
	$pid=PID_NUM();
    _out("Stopping service Shutdown pid $pid...");
	unix_system_kill($pid);
	for($i=0;$i<5;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
		_out("Stopping: service waiting pid:$pid $i/5...");
		sleep(1);
	}

	$pid=PID_NUM();
	if(!$unix->process_exists($pid)){
		_out("Stopping service success...");
        shell_exec("/usr/sbin/cgroupfs-umount");
		return true;
	}

	_out("Stopping service shutdown - force - pid $pid...");
	unix_system_kill_force($pid);
	for($i=0;$i<120;$i++){
		$pid=PID_NUM();
		if(!$unix->process_exists($pid)){break;}
        _out("Stopping service waiting pid:$pid $i/120...");
		sleep(1);
	}

	if($unix->process_exists($pid)){
        _out("Stopping service failed...");
		return false;
	}
    shell_exec("/usr/sbin/cgroupfs-umount");
    return true;

}
function stop_container_progress($prc,$text,$ID):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker.$ID.stop");
}

function isrunning_container($ID):bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    exec("$docker ps -q --no-trunc 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==$ID){return true;}
    }

    return false;

}

function stop_container($ID):bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");

    stop_container_progress(10,"{stopping}....",$ID);
    if(!isrunning_container($ID)){
        return stop_container_progress(100,"{success}....",$ID);
    }
    $nohup=$unix->find_program("nohup");

    $cmd="$nohup $docker stop $ID >/dev/null 2>&1 &";

    system($cmd);

    for ($i=1;$i<21;$i++){
        if(!isrunning_container($ID)){
            return stop_container_progress(100,"{success}....",$ID);
        }
        stop_container_progress(50,"{stopping} {waiting} $i/20....",$ID);
        sleep(1);
    }
    if(!isrunning_container($ID)){
        docker_stats();
        return stop_container_progress(100,"{success}....",$ID);
    }
    docker_stats();
    return stop_container_progress(110,"{failed}....",$ID);
}


function chroupfs_mount():bool{
    $unix=new unix();
    $mount=$unix->find_program("mount");
    $rmdir=$unix->find_program("rmdir");
    $mountpoint=$unix->find_program("mountpoint");
    $umount=$unix->find_program("umount");
    $echo=$unix->find_program("echo");
    $awk=$unix->find_program("awk");

    $f[]="#!/bin/sh";
    $f[]="# Copyright 2011 Canonical, Inc";
    $f[]="#           2014 Tianon Gravi";
    $f[]="# Author: Serge Hallyn <serge.hallyn@canonical.com>";
    $f[]="#         Tianon Gravi <tianon@debian.org>";
    $f[]="set -e";
    $f[]="";
    $f[]="# for simplicity this script provides no flexibility";
    $f[]="";
    $f[]="# if cgroup is mounted by fstab, don't run";
    $f[]="# don't get too smart - bail on any uncommented entry with 'cgroup' in it";
    $f[]="if grep -v '^#' /etc/fstab | grep -q cgroup; then";
    $f[]="	echo 'cgroups mounted from fstab, not mounting /sys/fs/cgroup'";
    $f[]="	exit 0";
    $f[]="fi";
    $f[]="";
    $f[]="# kernel provides cgroups?";
    $f[]="if [ ! -e /proc/cgroups ]; then";
    $f[]="	exit 0";
    $f[]="fi";
    $f[]="";
    $f[]="# if we don't even have the directory we need, something else must be wrong";
    $f[]="if [ ! -d /sys/fs/cgroup ]; then";
    $f[]="	exit 0";
    $f[]="fi";
    $f[]="";
    $f[]="# mount /sys/fs/cgroup if not already done";
    $f[]="if ! mountpoint -q /sys/fs/cgroup; then";
    $f[]="	$mount -t tmpfs -o uid=0,gid=0,mode=0755 cgroup /sys/fs/cgroup";
    $f[]="fi";
    $f[]="";
    $f[]="cd /sys/fs/cgroup";
    $f[]="";
    $f[]="# get/mount list of enabled cgroup controllers";
    $f[]="for sys in \$($awk '!/^#/ { if (\$4 == 1) print \$1 }' /proc/cgroups); do";
    $f[]="	mkdir -p \$sys";
    $f[]="	if ! $mountpoint -q \$sys; then";
    $f[]="		if ! $mount -n -t cgroup -o \$sys cgroup \$sys; then";
    $f[]="			$rmdir \$sys || true";
    $f[]="		fi";
    $f[]="	fi";
    $f[]="done";
    $f[]="";
    $f[]="# enable cgroups memory hierarchy, like systemd does (and lxc/docker desires)";
    $f[]="# https://github.com/systemd/systemd/blob/v245/src/core/cgroup.c#L2983";
    $f[]="# https://bugs.debian.org/940713";
    $f[]="if [ -e /sys/fs/cgroup/memory/memory.use_hierarchy ]; then";
    $f[]="	$echo 1 > /sys/fs/cgroup/memory/memory.use_hierarchy";
    $f[]="fi";
    $f[]="";
    $f[]="exit 0";
    @file_put_contents("/usr/sbin/cgroupfs-mount",@implode("\n",$f));
    @chmod("/usr/sbin/cgroupfs-mount",0755);
    $f=array();
    $f[]="#!/bin/sh";
    $f[]="# Copyright 2011 Canonical, Inc";
    $f[]="#           2014 Tianon Gravi";
    $f[]="# Author: Serge Hallyn <serge.hallyn@canonical.com>";
    $f[]="#         Tianon Gravi <tianon@debian.org>";
    $f[]="set -e";
    $f[]="";
    $f[]="# we don't care to move tasks around gratuitously - just umount the cgroups";
    $f[]="";
    $f[]="# if we don't even have the directory we need, something else must be wrong";
    $f[]="if [ ! -d /sys/fs/cgroup ]; then";
    $f[]="	exit 0";
    $f[]="fi";
    $f[]="";
    $f[]="# if /sys/fs/cgroup is not mounted, we don't bother";
    $f[]="if ! $mountpoint -q /sys/fs/cgroup; then";
    $f[]="	exit 0";
    $f[]="fi";
    $f[]="";
    $f[]="cd /sys/fs/cgroup";
    $f[]="";
    $f[]="for sys in *; do";
    $f[]="	if $mountpoint -q \$sys; then";
    $f[]="		$umount \$sys";
    $f[]="	fi";
    $f[]="	if [ -d \$sys ]; then";
    $f[]="		$rmdir \$sys || true";
    $f[]="	fi";
    $f[]="done";
    $f[]="";
    $f[]="exit 0";
    $f[]="";
    @file_put_contents("/usr/sbin/cgroupfs-umount",@implode("\n",$f));
    @chmod("/usr/sbin/cgroupfs-umount",0755);

    return true;

}
function PID_NUM():int{
	$unix=new unix();
    $pid=$unix->get_pid_from_file("/var/run/docker/docker.pid");
    if($unix->process_exists($pid)){return $pid;}
	$Masterbin=$unix->find_program("dockerd");
	return $unix->PIDOF($Masterbin);
}
function images_running():bool{
    $dock=new dockerd();
    $MAIN=$dock->unix_images_running();
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerImagesContainer",serialize($MAIN));
    return true;
}

function list_images():array{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    exec("$docker images --all --no-trunc --format json 2>&1",$results);
    $dock=new dockerd();
    $MAIN=array();
    foreach ($results as $line){
        $Repository=null;
        $json=json_decode($line);
        $Containers=$json->Containers;
        $CreatedAt=$json->CreatedAt;
        $CreatedSince=$json->CreatedSince;
        $Digest=$json->Digest;
        $ID=$json->ID;
        $Repository=trim($json->Repository);
        $Tag=$json->Tag;
        if($Tag<>null){
            $Repository=$Repository.":$Tag";
        }

        if($Repository=="<none>"){$Repository=null;}
        echo "[$Repository] ".strlen($Repository) ." --> in $ID\n";

        if($Repository==null){
            $ImageName=$dock->GetImageNameInfos($ID,true);
            if($ImageName==null){
                echo "Repository missing in $ID\n";
            }
            $Repository="$ID|$ImageName";
        }

        $SharedSize=$json->SharedSize;
        $Size=$json->Size;

        $UniqueSize=$json->UniqueSize;
        $VirtualSize=$json->VirtualSize;
        if($GLOBALS["VERBOSE"]){
            var_dump($json);
        }


        $MAIN[$Repository]=array(
            "Containers"=>$Containers,
            "CreatedAt"=>$CreatedAt,
            "CreatedSince"=>$CreatedSince,
            "Digest"=>$Digest,
            "ID"=>$ID,
            "SharedSize"=>$SharedSize,
            "Size"=>$Size,
            "Tag"=>$Tag,
            "UniqueSize"=>$UniqueSize,
            "VirtualSize"=>$VirtualSize
        );
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerImagesList",serialize($MAIN));
    images_running();
    return $MAIN;
}
function docker_stats_dirsize($AsCache=true):bool{
    $unix=new unix();
    $DockerDataRoot=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRoot"));
    if($DockerDataRoot==null){$DockerDataRoot="/home/docker";}
    $DockerDataRootSize=$unix->DIRSIZE_BYTES($DockerDataRoot,$AsCache);
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerDataRootSize",$DockerDataRootSize);
    return true;
}
function move_working_directory_progress($prc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker.workdir.progress");
}
function move_working_directory():bool{
    $unix=new unix();
    $KeySource="DockerDataRoot";
    $KeyTemp="DockerDataRootTemp";
    $MoveDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO($KeyTemp));
    $SourceDir=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO($KeySource));
    $RestartTask="/etc/init.d/docker restart";
    $StopTask="/etc/init.d/docker stop";
    if($SourceDir==null){$SourceDir="/home/docker";}
    $pidfile="/etc/artica-postfix/pids/".basename(__FILE__).".move_working_directory.pid";



    if($MoveDir==null){
        echo "No Directory to move...\n";
        move_working_directory_progress(100,"{success}");
        return true;
    }
    if($MoveDir==$SourceDir){
        echo "Same $SourceDir -> $MoveDir directory\n";
        move_working_directory_progress(100,"{success}");
        return true;
    }
    $rsync=$unix->find_program("rsync");

    $myPid=getmypid();
    $unix=new unix();
    $pid=$unix->get_pid_from_file($pidfile);
    _out("Checking old pid: $pid");
    if($unix->process_exists($pid,basename(__FILE__))){
        _out("[$myPid]: Already PID $pid exists for moving $SourceDir to $MoveDir directory, aborting");
        move_working_directory_progress(100,"{success}");
        return false;
    }

    move_working_directory_progress(10,"{move} $SourceDir - $MoveDir");

    @file_put_contents($pidfile,getmypid());
    $pid=$unix->PIDOF_PATTERN("rsync.*?remove-source-files");
    if($unix->process_exists($pid)){
        move_working_directory_progress(100,"{success}");
        echo "An another instance already running...\n";
        return true;
    }


    if(!is_dir($SourceDir)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($KeySource,$MoveDir);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($KeyTemp,"");
        shell_exec($RestartTask);
        return true;
    }

    move_working_directory_progress(50,"{please_wait}");
    stop(false);
    if(!is_dir($MoveDir)){@mkdir($MoveDir,0755,true);}
    $TEMP_DIR=$unix->TEMP_DIR();
    $find=$unix->find_program("find");
    $cmds[]="$rsync";
    $cmds[]="-avzh";
    $cmds[]="--temp-dir=$TEMP_DIR";
    $cmds[]="--remove-source-files";
    $cmds[]="$SourceDir/ $MoveDir/";
    $cmds[]="2>&1";
    $cmdline=@implode(" ",$cmds);
    echo $cmdline."\n";
    move_working_directory_progress(60,"{please_wait}");
    shell_exec($StopTask);

    exec($cmdline,$results);
    if($unix->check_rsync_error($results)){
        echo "Rsync error !!! [".$GLOBALS["check_rsync_error"]."]";
        move_working_directory_progress(110,"{failed} {$GLOBALS["check_rsync_error"]}");
        return false;
    }
    $umount=$unix->find_program("umount");
    move_working_directory_progress(70,"{please_wait}");
    $dirs=$unix->MOUNTED_DIRS("$SourceDir/overlay2");
    if(count($dirs)>0){
        foreach ($dirs as $mounted){
            echo "Unmount $mounted\n";
            shell_exec("$umount -l $mounted");
        }
    }
    $dirs=$unix->MOUNTED_DIRS("$SourceDir/overlay2");

    if(count($dirs)>0){
        foreach ($dirs as $mounted){
            echo "Unmount $mounted\n";
            shell_exec("$umount -l $mounted");
        }
    }
    $dirs=$unix->MOUNTED_DIRS("$SourceDir/plugins");
    if(count($dirs)>0){
        foreach ($dirs as $mounted){
            echo "Unmount $mounted\n";
            shell_exec("$umount -l $mounted");
        }
    }

    $mounted=$unix->MOUNTED_DIR($SourceDir);
    echo "$SourceDir mounted on $mounted\n";
    if(strlen($mounted)>1) {
        echo "Unmount $mounted\n";
        shell_exec("$umount -l $mounted");
    }



    $cmdline="$find $SourceDir -type d -empty -delete";
    echo $cmdline."\n";
    system($cmdline);

    if(!is_dir($SourceDir)){
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($KeySource,$MoveDir);
        $GLOBALS["CLASS_SOCKETS"]->SET_INFO($KeyTemp,"");
        move_working_directory_progress(90,"{restarting}");
        shell_exec($RestartTask);
        move_working_directory_progress(100,"{success}");
        return true;

    }
    move_working_directory_progress(110,"{failed}");
    return true;
}
function import_image_progress($prc,$text,$md5):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker.image.$md5.progress");
}
function import_image($filename,$md5){
    $unix=new unix();
    $ext=null;
    import_image_progress(10,"{import} $filename",$md5);
    $Src="/usr/share/artica-postfix/ressources/conf/upload/$filename";
    if(!is_file($Src)){
        return import_image_progress(110,"{import} $filename no such file",$md5);
    }
    $ImageName=null;
    if(preg_match("#^(.+?)\.tar\.gz$#",$filename,$re)){
        $ext="tar.gz";
        $ImageName=$re[1];
    }
    if($ImageName==null) {
        if (preg_match("#^(.+?)\.tar$#", $filename, $re)) {
            $ext = "tar";
            $ImageName = $re[1];
        }
    }
    if($ImageName==null){
        if(preg_match("#^(.+?)\.gz$#",$filename,$re)) {
            $ext = "gz";
            $ImageName = $re[1];
        }
    }

    if($ext==null){
        @unlink($Src);
        return import_image_progress(110,"{import} $filename wrong extension name",$md5);
    }
    $ImageName=str_replace(array(".",":","%","#"),"_",$ImageName);


    _out("Importing $filename into an image file");
    $tmp=$unix->TEMP_DIR()."/".$filename;
    import_image_progress(10,"{moving} $filename",$md5);
    if(!@copy($Src,$tmp)){
        @unlink($Src);
        if(is_file($tmp)){@unlink($tmp);}
        return import_image_progress(110,"{moving} $filename {failed}",$md5);
    }
    if(!is_file($tmp)){
        return import_image_progress(110,"$tmp no such file",$md5);
    }
    $tmp=$unix->shellEscapeChars($tmp);
    @unlink($Src);
    $docker=$unix->find_program("docker");
    $date=date("Y-m-d H:i:s");
    $cmd="$docker import --message \"Imported on $date\" \"$tmp\" 2>&1";

    _out($cmd);
    import_image_progress(50,"{please_wait}",$md5);
    exec($cmd,$results);
    _out("Importing $filename terminated");
    @unlink($tmp);
    $newsha=null;
    import_image_progress(60,"{done}",$md5);
    foreach ($results as $line){
        if(is_null($line)){continue;}
        $line=trim($line);
        if($line==null){continue;}
        if(preg_match("#(error|invalid)#i",$line)){
            return import_image_progress(110,"{failed} $line",$md5);
        }
        if(preg_match("#sha256:(.+?)#",$line,$re)){
            _out("Importing $filename sha=[$line]");
            $newsha="sha256:".$re[1];
            continue;
        }
        _out($line);
    }

    if($newsha==null){
        return import_image_progress(110,"{failed} no sha256",$md5);
    }

    import_image_progress(80,"$filename -> $ImageName:new",$md5);
    $cmd="$docker image tag $newsha $ImageName:new";
    _out($cmd);
    $results=array();
    exec("$cmd 2>&1",$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out($line);
    }
    import_image_progress(90,"$filename -> $ImageName:new",$md5);
    list_images();

    import_image_progress(100,"{success} $filename",$md5);
    return true;

}

function commit_image_progress($prc,$text,$ID):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker.commit.$ID");
}
function commit_image($DockerID):bool{
    if(strlen($DockerID)<2){
        return commit_image_progress(110,"No docker ID!",$DockerID);
    }

    commit_image_progress(10,"Commit: $DockerID",$DockerID);
    $commit=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerCommit-$DockerID"));
    if(!isset($commit["image"])){$commit["image"]=null;}
    if(!isset($commit["author"])){$commit["author"]=null;}
    if(!isset($commit["message"])){$commit["message"]=null;}
    if(!isset($commit["changes"])){$commit["changes"]=null;}
    if(strlen(trim($commit["image"]))<3){
        return commit_image_progress(110,"{image}: {$commit["image"]} false",$DockerID);
    }
    $commit["image"]=trim($commit["image"]);
    $commit["changes"]=trim($commit["changes"]);
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd[]="$docker commit";
    if(strlen($commit["author"])>2){
        $cmd[]="--author \"{$commit["author"]}\"";
    }
    if(strlen($commit["message"])>2){
        $commit["message"]=str_replace('"',"",$commit["message"]);
        $cmd[]="--message \"{$commit["message"]}\"";
    }
    if(strlen($commit["changes"])>2){
        $cmd[]="--change '{$commit["changes"]}'";
    }
    $cmd[]="\"$DockerID\" \"{$commit["image"]}\" 2>&1";

    commit_image_progress(50,"{image}: {$commit["image"]} {building}",$DockerID);
    $cmdline=@implode(" ",$cmd);
    _out("Commit $DockerID to {$commit["image"]}");
    exec($cmdline,$results);
    foreach ($results as $line){
        $line=trim($line);
        if($line==null){continue;}
        _out($line);
        if(preg_match("#error#",$line)){
            _out($cmdline);
            return commit_image_progress(110,"{image}: {$commit["image"]} {failed} in command-line",$DockerID);
        }

    }

    $Main=list_images();
    if(!isset($Main[ $commit["image"] ] ) ){
        _out($cmdline);
        return commit_image_progress(110,"{image}: {$commit["image"]} {failed}",$DockerID);
    }
    return commit_image_progress(100,"{image}: {$commit["image"]} {success}",$DockerID);
}
function docker_clean_export_dir():bool{
    $tdir="/home/docker-export";
    $unix=new unix();
    if(!is_dir($tdir)){
        @mkdir($tdir,0755,true);
        @chown($tdir,"www-data");
    }
    $files=scandir($tdir);
    $DockerExportTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerExportTime"));
    if($DockerExportTime==0){$DockerExportTime=60;}

    foreach ($files as $fname){
        if($fname=="."){continue;}
        if($fname==".."){continue;}
        $tim=$unix->file_time_min("$tdir/$fname");
        if($tim>$DockerExportTime){
            _out("Cleaning Exported container $tdir/$fname");
            @unlink("$tdir/$fname");
        }
    }
    return true;
}
function docker_change_entrypoint(){
    $fpath="/media/dockerhd/containers/2d872fd701d1f93a02e564cd1641b8c2ca4f230d01d5f172d71f6ed5186376ce/config.v2.json";
    $data=@file_get_contents($fpath);
    $json=json_decode($data);
    print_r($json->Config->Entrypoint);
    $json->Config->Entrypoint=array();
    $s[]="/usr/bin/tail";
    $s[]="-f";
    $s[]="/dev/null";
    foreach ($s as $line){
        $json->Config->Entrypoint[]=$line;
    }

    print_r($json->Config->Entrypoint);
    @file_put_contents($fpath,json_encode($json));
}



function docker_stats():bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $DockerDataRoot=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRoot"));
    if($DockerDataRoot==null){$DockerDataRoot="/home/docker";}

    exec("$docker stats --no-trunc --format json --no-stream 2>&1",$results);
    $rrdtool=$unix->find_program("rrdtool");
    if(!is_dir("$DockerDataRoot/rrd")){
        @mkdir("$DockerDataRoot/rrd",0755,true);
    }
    $unix->Popuplate_cron_make("docker-stats","*/3 * * * *",basename(__FILE__)." --docker-stats");
    docker_stats_dirsize();
    docker_clean_export_dir();

    $MAIN=array();
    foreach ($results as $line){
        $json=json_decode($line);
        $BlockIO=$json->BlockIO;
        $CPUPerc=$json->CPUPerc;
        $ID=$json->ID;
        $MemPerc=$json->MemPerc;
        $MemUsage=$json->MemUsage;
        $NetIO=$json->NetIO;
        $PIDs=$json->PIDs;
        $rrd_base="$DockerDataRoot/rrd/$ID.rrd";
        if(preg_match("#([0-9\.]+)#",$MemPerc,$re)){$MemPerc=$re[1];}
        if(preg_match("#([0-9\.]+)#",$CPUPerc,$re)){$CPUPerc=$re[1];}

        if(!is_file("$rrd_base")) {
            shell_exec("$rrdtool create $rrd_base -s 300"
                ." DS:cpu:GAUGE:600:0:100 DS:mem:GAUGE:600:0:100"
                ." RRA:AVERAGE:0.5:1:2000 RRA:AVERAGE:0.5:6:2000 RRA:AVERAGE:0.5:24:4000"
                ." RRA:AVERAGE:0.5:288:1000 RRA:MIN:0.5:1:2000 RRA:MIN:0.5:6:2000"
                ." RRA:MIN:0.5:24:4000 RRA:MIN:0.5:288:1000 RRA:MAX:0.5:1:2000"
                ." RRA:MAX:0.5:6:2000 RRA:MAX:0.5:24:4000 RRA:MAX:0.5:288:1000");

        }
        system("$rrdtool update $rrd_base N:$CPUPerc:$MemPerc");
        $MAIN[$ID]["MemPerc"]=$MemPerc;
        $MAIN[$ID]["CPUPerc"]=$CPUPerc;
        $MAIN[$ID]["MemUsage"]=$MemUsage;
        $MAIN[$ID]["NetIO"]=$NetIO;
        $MAIN[$ID]["PIDs"]=$PIDs;
        $MAIN[$ID]["BlockIO"]=$BlockIO;


    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerContainersStats",serialize($MAIN));

    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $results=$q->QUERY_SQL("SELECT frontend_id,ID FROM groups");
    foreach ($results as $index=>$ligne){
        $frontend_id=$ligne["frontend_id"];
        $gpid=$ligne["ID"];
        $workdir="/home/artica/DocksClients/$frontend_id/$gpid/Docker";
        if(!is_dir($workdir)){@mkdir($workdir,0755,true);}
        echo "Saving $workdir/stats.json\n";
        @file_put_contents("$workdir/stats.json",serialize($MAIN));
    }


    return true;

}

?>