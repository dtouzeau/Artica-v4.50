<?php
include_once(dirname(__FILE__)."/frame.class.inc");
include_once(dirname(__FILE__)."/class.unix.inc");
if(isset($_GET["install"])){install();exit;}
if(isset($_GET["uninstall"])){uninstall();exit;}
if(isset($_GET["status"])){status();exit;}
if(isset($_GET["restart"])){restart();exit;}
if(isset($_GET["search-images"])){search_images();exit;}
if(isset($_GET["list-images"])){list_images();exit;}
if(isset($_GET["remove-image"])){remove_image();exit;}
if(isset($_GET["remove-container"])){remove_container();exit;}
if(isset($_GET["ps"])){docker_ps();exit;}
if(isset($_GET["download-container"])){download_image();exit;}
if(isset($_GET["link-container"])){link_container();exit;}
if(isset($_GET["stop-container"])){stop_container();exit;}
if(isset($_GET["start-container"])){start_container();exit;}
if(isset($_GET["unpause-container"])){unpause_container();exit;}
if(isset($_GET["shellinabox-install"])){shellinabox_install();exit;}
if(isset($_GET["container-details"])){details_container();exit;}
if(isset($_GET["container-delnet"])){network_delete_container();exit;}
if(isset($_GET["volumes-list"])){list_volumes();exit;}
if(isset($_GET["yacht-remove"])){yacht_remove();exit;}
if(isset($_GET["yacht-install"])){yacht_install();exit;}
if(isset($_GET["volumeloopback-install"])){volumeloopback_install();exit;}
if(isset($_GET["volumeloopback-remove"])){volumeloopback_remove();exit;}
if(isset($_GET["volumes-add"])){volume_add();exit;}
if(isset($_GET["volumes-inspect"])){volume_inspect();exit;}
if(isset($_GET["remove-volume"])){volume_delete();exit;}
if(isset($_GET["move-workdir"])){move_workdir();exit;}
if(isset($_GET["events"])){docker_events();exit;}
if(isset($_GET["commit"])){commit_image();exit;}
if(isset($_GET["image-history"])){history_image();exit;}
if(isset($_GET["image-inspect"])){inspect_image();exit;}
if(isset($_GET["image-inspect-name"])){inspect_image_by_name();exit;}
if(isset($_GET["export-container"])){export_container();exit;}
if(isset($_GET["export-container-progress"])){export_container_progress();exit;}
if(isset($_GET["container-id"])){container_id();exit;}
if(isset($_GET["image-uploaded"])){upload_image();exit;}
if(isset($_GET["changetag-image"])){imagename_image();exit;}
if(isset($_GET["image-childs"])){image_check_childs();exit;}
if(isset($_GET["prune-volumes"])){prune_volumes();exit;}
if(isset($_GET["container-rename"])){rename_container();exit;}
if(isset($_GET["network-list"])){network_list();exit;}
if(isset($_GET["network-inspect"])){network_inspect();exit;}
if(isset($_GET["network-add"])){network_create();exit;}
if(isset($_GET["network-connect"])){network_connect();exit;}
if(isset($_GET["add-perimeter"])){perimeter_save();exit;}
if(isset($_GET["delete-perimeter"])){perimeter_delete();exit;}
if(isset($_GET["container-list-tag"])){list_containers_bytag();exit;}
if(isset($_GET["container-list-image"])){list_containers_image();exit;}
if(isset($_GET["update-web-frontend"])){perimeter_update_webfrontend();exit;}
if(isset($_GET["install-artica-images"])){install_artica_images();exit;}

if(isset($_GET["create-frontendimage"])){perimeter_create_frontendimage();exit;}
if(isset($_GET["group-config"])){group_create_config();exit;}



if(isset($_GET["create-frontend"])){perimeter_create_frontend();exit;}
if(isset($_GET["run-webfrontend"])){perimeter_create_webfrontend();exit;}
if(isset($_GET["remove-perimeter-group"])){perimeter_remove_group();exit;}
if(isset($_GET["create-perimeter-group"])){perimeter_create_group();exit;}
if(isset($_GET["clean-cache"])){clean_cache();exit;}
if(isset($_GET["clean-system"])){clean_system();exit;}

writelogs_framework("unable to understand query...".serialize($_GET),__FUNCTION__,__FILE__,__LINE__);

function group_create_config():bool{
    $gpid=$_GET["group-config"];
    $unix=new unix();
    return $unix->framework_exec("exec.docker.frontend.php --group-config $gpid");
}

function move_workdir():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --move-workdir","docker.workdir.progress","docker.workdir.log");
}
function perimeter_save():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.frontend.php --build-images","docker.perimeter","docker.perimeter.log");
}
function perimeter_update_webfrontend(){
    $unix=new unix();
    $WebContainerID=$_GET["update-web-frontend"];
    return $unix->framework_execute("exec.docker.frontend.php --update-webfrontend \"$WebContainerID\"",
        "docker.update.$WebContainerID","docker.update.$WebContainerID.log");
}
function perimeter_remove_group():bool{
    $unix=new unix();
    $gpid=intval($_GET["remove-perimeter-group"]);
    return $unix->framework_execute("exec.docker.frontend.php --remove-group $gpid",
        "docker.perimeter.delete.group.$gpid","docker.perimeter.delete.group.$gpid.log");
}
function perimeter_create_group():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.frontend.php --create-group",
        "docker.perimeter.create.group",
        "docker.perimeter.create.group.log");
}
function perimeter_create_frontend():bool{
    $unix=new unix();
    $ID=$_GET["create-frontend"];
    return $unix->framework_execute("exec.docker.frontend.php --run-frontend $ID","docker.perimeter.create.$ID","docker.perimeter.create.$ID.log");
}
function perimeter_create_webfrontend():bool{
    $gpid=intval($_GET["run-webfrontend"]);
    $unix=new unix();
    return $unix->framework_execute("exec.docker.frontend.php --run-webadmin $gpid","docker.perimeter.create.$gpid","docker.perimeter.create.$gpid.log");
}


function perimeter_create_frontendimage():bool{
    $unix=new unix();
    $ID=$_GET["create-frontendimage"];
    return $unix->framework_execute("exec.docker.frontend.php --build-frontend $ID",
        "docker.admin.image.$ID",
        "docker.admin.image.$ID.log",);
}

function install_artica_images():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.frontend.php --install-images",
        "docker.articatech.images.progress",
        "docker.articatech.images.log");

}


function perimeter_delete():bool{
    $unix=new unix();
    $ID=$_GET["delete-perimeter"];
    return $unix->framework_execute("exec.docker.frontend.php --delete-perimeter $ID","docker.perimeter","docker.perimeter.log");
}
function network_delete_container():bool{
    $ID=$_GET["container-delnet"];
    $NetID=$_GET["net"];
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $tfile=PROGRESS_DIR."/docker.netdel.$NetID.$ID";
    $cmd="$docker network disconnect -f $NetID $ID >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);

    $tfile=PROGRESS_DIR."/inspect.network.$NetID";
    if(is_file($tfile)){@unlink($tfile);}
    $tfile=PROGRESS_DIR."/docker.$ID.details";
    if(is_file($tfile)){@unlink($tfile);}
    return true;

}
function network_list():bool{
    $tfile=PROGRESS_DIR."/docker.network.list";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker network ls --no-trunc --format json >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return true;
}
function network_inspect():bool{
    $ID=$_GET["network-inspect"];
    $tfile=PROGRESS_DIR."/inspect.network.$ID";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker network inspect \"$ID\" --format json >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return true;

}
function network_connect():bool{
    $ID=$_GET["network-connect"];
    $IDNet=$_GET["net"];
    $tfile=PROGRESS_DIR."/docker.network.connect.$ID.$IDNet";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker network connect $IDNet $ID >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);

    $tfile=PROGRESS_DIR."/inspect.network.$IDNet";
    if(is_file($tfile)){@unlink($tfile);}
    $tfile=PROGRESS_DIR."/docker.$ID.details";
    if(is_file($tfile)){@unlink($tfile);}
    return true;
}
function network_create():bool{
    $POST=unserialize(base64_decode($_GET["network-add"]));
    $md5=$_GET["md5"];

    $tfile=PROGRESS_DIR."/docker.network.add.$md5";
    $Name=$POST["Name"];
    $subnet=$POST["subnet"];
    $range=$POST["range"];
    $gateway=$POST["gateway"];
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd[]="$docker network create --driver=bridge --subnet=$subnet";
    if($range<>null){
        $cmd[]="--ip-range=$range";
    }
    if($gateway<>null){
        $cmd[]="--gateway=$gateway";
    }
    $cmd[]="\"$Name\" >$tfile 2>&1";
    $cmdline=@implode(" ",$cmd);
    shell_exec($cmdline);
    writelogs_framework($cmdline,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return true;
}


function history_image():bool{
    $image=$_GET["image-history"];
    $md5=$_GET["md5"];
    $tfile=PROGRESS_DIR."/history.$md5";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker image history \"$image\" --no-trunc --format json >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return true;
}
function prune_volumes():bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker volume prune --all --force 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    return true;
}
function rename_container():bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $ID=$_GET["container-rename"];
    $name=$_GET["name"];
    $tfile=PROGRESS_DIR."/docker.$ID.rename";
    $cmd="$docker container rename $ID \"$name\" >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return true;
}
function inspect_image():bool{
    $image=$_GET["image-inspect"];
    $tfile=PROGRESS_DIR."/inspect.$image";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker image inspect \"$image\" --format json >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return true;
}
function upload_image():bool{
    $filename=$_GET["image-uploaded"];
    $md5=$_GET["md5"];
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --impport-image \"$filename\" \"$md5\"",
        "docker.image.$md5.progress",
        "docker.image.$md5.log");
}
function imagename_image():bool{
    $md5=$_GET["md5"];
    $ID=$_GET["changetag-image"];
    $name=$_GET["name"];
    $tfile=PROGRESS_DIR."/changetag.$md5";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker image tag \"$ID\" \"$name\" >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return $unix->framework_exec("exec.docker.php --list-images");
}
function inspect_image_by_name():bool{
    $image=$_GET["image-inspect-name"];
    $md5=$_GET["md5"];
    $tfile=PROGRESS_DIR."/inspect.image.$md5";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker image inspect \"$image\" --format json >$tfile 2>&1";
    shell_exec($cmd);
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    chown($tfile,"www-data");
    chmod($tfile,0644);
    return true;
}
function commit_image():bool{
    $unix=new unix();
    $DockerID=$_GET["commit"];
    return $unix->framework_execute("exec.docker.php --commit \"$DockerID\"","docker.commit.$DockerID","docker.commit.$DockerID.log");
}

function install():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --install","docker.progress","docker.progress.logs");
}
function restart():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --restart","docker.progress","docker.progress.logs");
}
function uninstall():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --uninstall","docker.progress","docker.progress.logs");
}
function remove_container():bool{
    $unix=new unix();
    $container=$_GET["remove-container"];
    return $unix->framework_execute("exec.docker.php --remove-container \"$container\"",
        "docker.rm.$container.progress",
        "docker.rm.$container.log");
}
function export_container():bool{
    $unix=new unix();
    $container_name=$_GET["export-container"];
    $container_id=$_GET["ID"];

    return $unix->framework_execute("exec.docker.php --export-container \"$container_name\" \"$container_id\"",
        "docker.export.$container_id",
        "docker.export.$container_id.log");
}
function container_id():bool{
    $unix=new unix();
    $container_name=$_GET["container-id"];
    $md5=$_GET["md5"];
    $docker=$unix->find_program("docker");
    $tfile=PROGRESS_DIR."/docker.$md5.details";
    $cmd="$docker ps --filter name=\"$container_name\" --format json --no-trunc >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    @chown($tfile,"www-data");
    return true;
}
function export_container_progress():bool{
    $unix=new unix();
    $container_id=$_GET["export-container-progress"];
    $pid=$unix->PIDOF_PATTERN("docker export $container_id");
    $tfile=PROGRESS_DIR."/docker.$container_id.export.run";
    if($unix->process_exists($pid)){
        @file_put_contents($tfile,"1");
        @chown($tfile,"www-data");
        return true;
    }
    @file_put_contents($tfile,"0");
    @chown($tfile,"www-data");
    return false;

}

function volume_delete():bool{
    $unix=new unix();
    $volume=$_GET["remove-volume"];
    return $unix->framework_execute("exec.docker.php --remove-volume \"$volume\"",
        "docker.rm.$volume.progress",
        "docker.rm.$volume.log");
}
function details_container():bool{
    $unix=new unix();
    $ID=$_GET["container-details"];
    $tfile=PROGRESS_DIR."/docker.$ID.details";
    $docker=$unix->find_program("docker");
    $cmd="$docker inspect --format json $ID >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    return true;
}
function docker_events():bool{
    $unix=new unix();
    $grep=$unix->find_program("grep");
    $tail=$unix->find_program("tail");
    $TERM=null;
    $MAIN=unserialize(base64_decode($_GET["events"]));
    list($date,$TERM,$max)=$unix->syslog_pattern($MAIN);

    $search="$date.*?$TERM";
    $search=str_replace(".*?.*?",".*?",$search);
    $cmd="$grep --binary-files=text -i -E '$search' /var/log/docker/docker.log |$tail -n $max >/usr/share/artica-postfix/ressources/logs/web/docker.syslog 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    @file_put_contents(PROGRESS_DIR."/docker.syslog.pattern", $search);
    shell_exec($cmd);
    return true;
}
function volume_add():bool{
    $unix=new unix();
    $VOLUME=$_GET["volumes-add"];
    $size=intval($_GET["size"]);
    $docker=$unix->find_program("docker");
    if($size>0){
        $cmd="$docker volume create -d \"ashald/docker-volume-loopback\" $VOLUME -o sparse=true -o fs=ext4 -o size={$size}Gib";
    }else{
        $cmd="$docker volume create $VOLUME";
    }
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    return true;
}
function volume_inspect():bool{
    $ID=$_GET["volumes-inspect"];
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker volume inspect $ID 2>&1";
    exec("$cmd",$results);
    writelogs_framework("{$_GET["volumes-inspect"]}:$cmd",__FUNCTION__,__FILE__,__LINE__);
    $tfile=PROGRESS_DIR."/volume-$ID.inspect";
    @file_put_contents($tfile,@implode("\n",$results));
    @chmod($tfile,0755);
    return true;
}
function shellinabox_install():bool{
    $ID=$_GET["shellinabox-install"];
    $unix=new unix();
    return $unix->framework_exec("exec.docker.php --shellinabox-install $ID");
}
function stop_container():bool{
    $ID=$_GET["stop-container"];
    $unix=new unix();
    clean_container_cache($ID);
    return $unix->framework_execute("exec.docker.php --stop-container \"$ID\"",
        "docker.$ID.stop",
        "docker.$ID.stop.log");
}
function yacht_remove():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --yacht-remove",
        "docker-yacht.progress",
        "docker-yacht.log");
}
function yacht_install():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --yacht-install",
        "docker-yacht.progress",
        "docker-yacht.log");
}
function volumeloopback_install():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --volumeloopback-install",
        "docker-yacht.progress",
        "docker-yacht.log");
}
function volumeloopback_remove():bool{
    $unix=new unix();
    return $unix->framework_execute("exec.docker.php --volumeloopback-uninstall",
        "docker-yacht.progress",
        "docker-yacht.log");
}
function unpause_container():bool{
    $unix=new unix();
    $md5=$_GET["md5"];
    $container=$_GET["unpause-container"];
    $docker=$unix->find_program("docker");
    $cmd="$docker container unpause $container 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$results);
    foreach ($results as $index=>$line){
        if(preg_match("#See.*?--help#",$line)){
            unset($results[$index]);
            continue;
        }
        writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
    }
    $tfile=PROGRESS_DIR."/docker.$md5.unpause";
    @file_put_contents($tfile,@implode("\n",$results));
    @chmod($tfile,0755);
    clean_container_cache($container);
    return true;
}
function start_container():bool{
    $unix=new unix();
    $md5=$_GET["md5"];
    $container=$_GET["start-container"];
    $docker=$unix->find_program("docker");
    clean_container_cache($container);
    $cmd="$docker container cp /usr/share/artica-postfix/bin/docker-client $container:/usr/sbin/docker-client";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$results);
    foreach ($results as $line){
        writelogs_framework("CP: $line",__FUNCTION__,__FILE__,__LINE__);
    }
    $results=array();
    $cmd="$docker start $container 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    exec($cmd,$results);
    foreach ($results as $index=>$line){
        if(preg_match("#See.*?--help#",$line)){
            unset($results[$index]);
            continue;
        }
        writelogs_framework("$line",__FUNCTION__,__FILE__,__LINE__);
    }
    $tfile=PROGRESS_DIR."/docker.$md5.start";


    @file_put_contents($tfile,@implode("\n",$results));
    @chmod($tfile,0755);
    $unix->framework_exec("exec.docker.frontend.php --sync-webadmin");
    return true;
}
function clean_container_cache($ID):bool{
    $path="/usr/share/artica-postfix/ressources/logs/web/docker.$ID.details";
    if(is_file($path)){@unlink($path);return true;}
    clean_cache();
    return false;
}

function link_container():bool{
    $unix=new unix();
    $imagemd5=$_GET["md5"];
    $container=$_GET["link-container"];

    return $unix->framework_execute("exec.docker.php --link-container \"$container\" $imagemd5",
    "docker.link.$imagemd5","docker.link.$imagemd5.log");



}
function docker_ps():bool{
    $unix=new unix();
    $tfile=PROGRESS_DIR."/docker.ps.json";
    $docker=$unix->find_program("docker");
    $cmd="$docker ps -a --no-trunc --format json >$tfile 2>&1";
    writelogs_framework("$cmd",__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    return true;
}

function image_check_childs():bool{
    $unix=new unix();
    $ID=$_GET["image-childs"];
    $xargs="/usr/bin/xargs";
    $grep="/bin/grep";
    $docker=$unix->find_program("docker");
    $tfile=PROGRESS_DIR."/image.assoc.$ID";
    $cmd="$docker images -a -q --filter since=$ID | $xargs docker inspect --format='{{.Id}} {{.Parent}}' | $grep $ID >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    @chown($tfile,"www-data");
    return true;
}

function remove_image():bool{
    $unix=new unix();
    $container=$_GET["remove-image"];
    $docker=$unix->find_program("docker");
    $md5=$_GET["md5"];
    $tfile=PROGRESS_DIR."/image.delete.$md5";
    $php=$unix->LOCATE_PHP5_BIN();
    $cmd="$docker image rm --force $container >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    @chown($tfile,"www-data");
    clean_cache();
    shell_exec("$php /usr/share/artica-postfix/exec.docker.php --list-images");
    return true;
}
function download_image():bool{
    $unix=new unix();
    $container=$_GET["download-container"];
    $md5=$_GET["md5"];
    return $unix->framework_execute("exec.docker.php --download-container \"$container\" $md5",
        "docker.install.$md5",
        "docker.install.$md5.log");
}
function status():bool{
    $unix=new unix();
    return $unix->framework_exec("exec.status.php --dockerd");
}
function list_images():bool {
    $unix=new unix();
    return $unix->framework_exec("exec.docker.php --list-images");
}
function clean_cache():bool{
$unix=new unix();
    $rm=$unix->find_program("rm");
    shell_exec("$rm -f ".PROGRESS_DIR."/docker.*");
    shell_exec("$rm -f ".PROGRESS_DIR."/image.*");
    shell_exec("$rm -f ".PROGRESS_DIR."/inspect.*");
    return true;
}
function list_containers_image():bool{
        $image=$_GET["container-list-image"];
        $tfile=PROGRESS_DIR."/docker.ContainersListImageName.$image";
        $unix=new unix();
        $docker=$unix->find_program("docker");
        $cmd="$docker container ps -a --filter=ancestor=$image --no-trunc --format json >$tfile 2>&1";
        writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
        shell_exec($cmd);
        @chmod($tfile,0755);
        @chown($tfile,"www-data");
        return true;
}
function list_containers_bytag():bool{
    $tag=base64_decode($_GET["container-list-tag"]);
    $tagname_md5=md5($tag);
    $tfile=PROGRESS_DIR."/docker.ContainersListByTag.$tagname_md5";
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker container ls -a --filter label=$tag --no-trunc --format json >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    @chown($tfile,"www-data");
    return true;

}
function list_volumes(){
    $unix=new unix();
    $tfile=PROGRESS_DIR."/docker.volumes.json";
    $docker=$unix->find_program("docker");
    $cmd="$docker volume ls --format json >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    return true;
}

function search_images():bool{
    $unix=new unix();
    $pattern=$_GET["search-images"];
    $md5=$_GET["md5"];
    $docker=$unix->find_program("docker");
    $tfile=PROGRESS_DIR."/docker.images.search.$md5.json";
    if(is_file($tfile)){
        writelogs_framework("$tfile already exists",__FUNCTION__,__FILE__,__LINE__);
        @chmod($tfile,0755);
        return true;}
    $cmd="$docker search \"$pattern\" --format '{{json .}}' --no-trunc --limit 50 >$tfile 2>&1";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    @chmod($tfile,0755);
    return true;
}
function clean_system():bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    $cmd="$docker system prune --all --force";
    writelogs_framework($cmd,__FUNCTION__,__FILE__,__LINE__);
    shell_exec($cmd);
    clean_cache();
    return true;
}
