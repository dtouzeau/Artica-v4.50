<?php
if(function_exists("posix_getuid")){if(posix_getuid()<>0){die("Cannot be used in web server mode\n\n");}}
include_once("/usr/share/artica-postfix/ressources/class.sockets.inc");
include_once("/usr/share/artica-postfix/ressources/class.docker.inc");
include_once("/usr/share/artica-postfix/ressources/class.webconsole.params.inc");
$GLOBALS["CLASS_SOCKETS"]=new sockets();
$GLOBALS["CLASS_SOCKETS"]->heads_exec_root($argv);


if(!isset($argv[1])){die();}

if($argv[1]=="--build-images"){create_perimeter();exit;}
if($argv[1]=="--delete-perimeter"){delete_perimeter($argv[2]);exit;}
if($argv[1]=="--update-webfrontend"){update_frontend_webadm($argv[2]);exit;}
if($argv[1]=="--run-webadmin"){run_frontend_webadm($argv[2]);exit;}
if($argv[1]=="--nginx-group"){run_frontend_to_nginx($argv[2]);exit;}
if($argv[1]=="--remove-group"){delete_group($argv[2]);exit;}
if($argv[1]=="--create-group"){create_group();exit;}
if($argv[1]=="--create-node"){create_backend_node($argv[2]);exit;}
if($argv[1]=="--check-group-instances"){check_group_instances($argv[2]);exit;}
if($argv[1]=="--group-config"){run_frontend_webadm_infos($argv[2]);exit;}



if($argv[1]=="--sync-webadmin"){sync_frontend_to_nginx();exit;}
if($argv[1]=="--install-images"){install_images();exit;}



function build_progressiadminimage($prc,$text,$ID):bool{
    $unix=new unix();return $unix->framework_progress($prc,$text,"docker.admin.image.$ID");
}
function build_progress($prc,$text):bool{
    $unix=new unix();return $unix->framework_progress($prc,$text,"docker.perimeter");
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
function delete_perimeter($ID):bool{
    if(intval($ID)==0){
        return build_progress(110,"{failed} Wrong ID");
    }
    $unix=new unix();
    $dock=new dockerd();
    $Name=$dock->PerimeterName($ID);
    build_progress(20,"{remove} $Name");
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $ligne=$q->mysqli_fetch_array("SELECT * FROM frontends WHERE ID='$ID'");
    $php=$unix->LOCATE_PHP5_BIN();

    $ContainersList=$dock->ContainersListByTag("com.articatech.artica.scope.$ID");
    foreach ($ContainersList as $uuid=>$ContName){
        build_progress(25,"{remove} $ContName");
        _out("Removing container $ContName as part of scope $ID");
        shell_exec("$php /usr/share/artica-postfix/exec.docker.php --remove-container \"$uuid\"");
    }

    $frontendimageid=$ligne["frontendimageid"];
    $adminimageid=$ligne["adminimageid"];
    $ContainersList=$dock->ContainersListImageName($frontendimageid,true);
    foreach ($ContainersList as $uuid=>$ContName){
        build_progress(27,"{remove} $ContName");
        _out("Removing container $ContName as part of image $frontendimageid");
        shell_exec("$php /usr/share/artica-postfix/exec.docker.php --remove-container \"$uuid\"");
    }
    if($frontendimageid<>null){
        build_progress(30,"{remove} {image}...[$frontendimageid]");
        if(!$dock->unix_remove_image($frontendimageid)){
            return build_progress(110,"{remove} $frontendimageid {failed}");
        }
    }
    if($adminimageid<>null){
        build_progress(30,"{remove} {image}...[$adminimageid]");
        if(!$dock->unix_remove_image($adminimageid)){
            return build_progress(110,"{remove} $adminimageid {failed}");
        }
    }

    $c=35;
    $results=$q->QUERY_SQL("SELECT ID FROM groups WHERE frontend_id=$ID");
    foreach ($results as $index=>$ligne){
        $gpid=$ligne["ID"];
        $c++;
        if($c>95){$c=95;}
        build_progress($c,"{remove} {group}...[$gpid]");
        $c=delete_group($gpid,$c);
    }

    $workdir="/home/artica/DocksClients/$ID";
    if(is_dir($workdir)){
        $rm=$unix->find_program("rm");
        shell_exec("$rm -rf $workdir");
    }

    $q->QUERY_SQL("DELETE FROM frontends WHERE ID=$ID");
    return build_progress(100,"{remove} {perimeter} $Name {success}...");

}

function sync_frontend_to_nginx():bool{
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $results=$q->QUERY_SQL("SELECT ID FROM groups");
    foreach ($results as $index=>$ligne){
        run_frontend_to_nginx($ligne["ID"]);
    }
    return true;
}

function run_frontend_to_nginx($GroupID):bool{
    $unix=new unix();
    $dockgp=new dockerd_groups($GroupID);
    $FrontendID=$dockgp->GetPermimeterID();
    $dock=new dockerd();
    $ContainersList=$dock->ContainersListByTag("com.articatech.artica.scope.$FrontendID.webadm.$GroupID",true);
    $ContainersID=$ContainersList[0];

    $IPAddress=null;
    $NetworkInfo=$dock->GetContainerNetworks($ContainersID,true);
    foreach ($NetworkInfo as $ids=>$array){
        if(!isset($array["IPAddress"])){continue;}
        $IPAddress=$array["IPAddress"];
        break;
    }
    $md51=null;
    $conf="/usr/local/ArticaWebConsole/webplugins/$GroupID.conf";
    if(is_file($conf)){
        $md51=md5_file($conf);
    }

    $f[]="location ^~ /$ContainersID/{";
    $f[]="\tproxy_set_header        Host \$host;";
    $f[]="\tproxy_set_header        X-Real-IP \$remote_addr;";
    $f[]="\tproxy_set_header        X-Forwarded-For \$proxy_add_x_forwarded_for;";
    $f[]="\tproxy_set_header        X-Forwarded-Proto \$scheme;";
    $f[]="\tproxy_set_header        X-ARTICA-SUBFOLDER $ContainersID;";
    $f[]="\tproxy_pass          https://$IPAddress:9000/;";
    $f[]="\tproxy_redirect  https://$IPAddress:9000/ /$ContainersID/;";
    $f[]="\tproxy_redirect  / /$ContainersID/;";
    $f[]="\tproxy_read_timeout  90;";
    $f[]="}";
    @file_put_contents($conf,@implode("\n",$f));
    _out("$conf [DONE]");
    $md52=md5_file($conf);
    if($md52==$md51){return true;}
    _out("Reload master console");
    $unix->RELOAD_WEBCONSOLE();
    return true;
}
function update_frontend_webadm_progress($prc,$text,$ContainerID):bool{
    $unix=new unix();return $unix->framework_progress($prc,$text,"docker.update.$ContainerID");
}
function update_frontend_webadm($ContainerID):bool{
    $unix=new unix();
    $docker=$unix->find_program("docker");
    update_frontend_webadm_progress(10,"{stopping} $ContainerID",$ContainerID);
    system("$docker container stop $ContainerID");
    update_frontend_webadm_progress(11,"{copy} docker-agent",$ContainerID);
    system("$docker container cp /usr/share/artica-postfix/bin/docker-client $ContainerID:/usr/sbin/docker-client");
    system("$docker container cp /usr/share/artica-postfix/VERSION $ContainerID:/usr/share/artica-postfix/");

    $dirs[]="/usr/share/artica-postfix";
    $dirs[]="/usr/share/artica-postfix/ressources";
    $dirs[]="/usr/share/artica-postfix/framework";
    $pr = 11;
    foreach ($dirs as $directory) {
            $pr++;
            if ($pr > 95) {
                $pr = 95;
            }
            update_frontend_webadm_progress($pr, "{installing} $directory", $ContainerID);
            $cmd="$docker container cp $directory/. $ContainerID:$directory/";
            echo $cmd."\n";
            system($cmd);

        }

    update_frontend_webadm_progress(96,"{starting} $ContainerID",$ContainerID);
    system("$docker container start $ContainerID");
    return update_frontend_webadm_progress(100,"{success}",$ContainerID);
}

function delete_group_progress($prc,$text,$gpid):bool{
    $unix=new unix();return $unix->framework_progress($prc,$text,"docker.perimeter.delete.group.$gpid");
}
function create_group_progress($prc,$text):bool{
    $unix=new unix();return $unix->framework_progress($prc,$text,"docker.perimeter.create.group");
}
function create_group():bool{
    $gpid=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("PERIMETER_GROUP_CREATED"));
    if($gpid==0){
        return create_group_progress(110,"Unable to find groupid");
    }

    create_group_progress(30,"Run Web administration instance");
    if( run_frontend_webadm($gpid) ){
        create_group_progress(110,"Run Web administration instance {failed}");
    }
    return create_group_progress(100,"{success}");
}
function check_group_instances($GroupID){

    $gpdock=new dockerd_groups($GroupID);
    $MaxInstances=intval($gpdock->Get("MaxInstances"));
    if($MaxInstances==0){$MaxInstances=1;}
    echo "Max Instances = $MaxInstances\n";


}

function delete_group($GroupID,$progress=0):int{
    if($progress==0){$progress=10;}
    $unix=new unix();
    $php=$unix->LOCATE_PHP5_BIN();
    $dock=new dockerd();
    $dockgp=new dockerd_groups($GroupID);
    $FrontendID=$dockgp->GetPermimeterID();
    delete_group_progress(15,"{remove} $GroupID on Frontend $FrontendID",$GroupID);
    $ContainersList=$dock->ContainersListByTag("com.articatech.artica.scope.$FrontendID.webadm.$GroupID");

    echo count($ContainersList)." WebAdm Containers for Group $GroupID and Perimeter $FrontendID\n";

    foreach ($ContainersList as $uuid=>$ContName){
        $progress++;
        build_progress($progress,"{remove} $ContName");
        delete_group_progress(50,"Removing container $ContName as part of group $GroupID",$GroupID);
        _out("Removing container $ContName as part of group $GroupID");
        shell_exec("$php /usr/share/artica-postfix/exec.docker.php --remove-container \"$uuid\"");
    }

    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $rm=$unix->find_program("rm");
    $workdir="/home/artica/DocksClients/$FrontendID/$GroupID";
    if(is_dir($workdir)){
        delete_group_progress(80,"Removing $workdir",$GroupID);
        shell_exec("$rm -rf $workdir");
    }

    $conf="/usr/local/ArticaWebConsole/webplugins/$GroupID.conf";
    if(is_file($conf)){
        @unlink($conf);
        $unix->RELOAD_WEBCONSOLE();
    }

    $q->QUERY_SQL("DELETE FROM groups_params WHERE groupid=$GroupID");
    $q->QUERY_SQL("DELETE FROM groups WHERE ID=$GroupID");
    delete_group_progress(100,"Removing $GroupID {done}",$GroupID);
    return $progress;
}

function install_images_progress($prc,$text):bool{
    $unix=new unix();
    return $unix->framework_progress($prc,$text,"docker.articatech.images.progress");
}
function install_images():bool{

    install_images_progress(15,"{installing} 1/2");

    if(!install_image_backend()){
        install_images_progress(110,"Creating {image} artica_backend:0 {failed}");
        return false;
    }

    install_images_progress(50,"{installing} 2/2");
    if(!install_image_WebAdmin()){
        install_images_progress(110,"Creating {image} artica_webadm:0 {failed}");
        return false;
    }
    install_images_progress(100,"{installing} 2/2 {success}");
    return true;
}

function install_image_WebAdmin():bool{
    $unix=new unix();
    $TEMP_PATH=$unix->TEMP_DIR()."/webadm-0";

    install_images_progress(60,"Creating {image} artica_webadm:0...");
    $rm=$unix->find_program("rm");


    if(!is_dir($TEMP_PATH)){@mkdir($TEMP_PATH);}
    file_put_contents("$TEMP_PATH/docker-method.conf","webadm");

    @copy("/usr/share/artica-postfix/bin/docker-client","$TEMP_PATH/docker-client");
    $f[]="FROM articatech/artica:adm1";
    $f[]="LABEL \"com.articatech.artica.type\"=\"ADM\" \"com.articatech.artica.scope\"=\"0\"";
    $f[]="EXPOSE 9000/tcp";
    $f[]="COPY --chmod=0755 docker-client /usr/sbin/docker-client";
    $f[]="COPY --chmod=0644 docker-method.conf /etc/docker-method.conf";
    $f[]="ENTRYPOINT [\"/usr/sbin/docker-client\"]";
    $f[]="";
    @file_put_contents($TEMP_PATH."/Dockerfile",@implode("\n",$f));
    $docker=$unix->find_program("docker");
    @touch("$TEMP_PATH/uuid.conf");
    $nohup=$unix->find_program("nohup");
    $logfile="$TEMP_PATH/image.log";
    $cmd="$nohup $docker build --iidfile $TEMP_PATH/uuid.conf --tag artica_webadm:0 $TEMP_PATH >$logfile 2>&1 &";
    //echo "$cmd\n";
    shell_exec($cmd);
    $tail=$unix->find_program("tail");
    $start=61;
    while (true){
        $pid=$unix->PIDOF_PATTERN("docker build.*?artica_webadm:0");
        if(!$unix->process_exists($pid)){break;}
        $start++;
        if($start>90){$start=90;}
        $tt=array();
        exec("$tail -n 1 $logfile",$tt);
        if(is_null($tt[0])){$tt[0]="{please_wait}";}
        install_images_progress($start,$tt[0]);
        sleep(5);
    }

    $results=explode("\n",@file_get_contents($logfile));
    foreach ($results as $line){
        echo $line."\n";
        if(preg_match("#error#i",$line)){
            echo $line."\n";
            shell_exec("$rm -rf $TEMP_PATH");
            return false;
        }
    }
    $uuid=trim(@file_get_contents("$TEMP_PATH/uuid.conf"));

    if($uuid==null){
        echo "No UUID provided!\n";
        shell_exec("$rm -rf $TEMP_PATH");
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DOCKER_WEBADM_IMAGEID",$uuid);
    shell_exec("$rm -rf $TEMP_PATH");
    return true;

}
function install_image_backend():bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $dock=new dockerd();
    $dock->create_databases();
    $ID=0;
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    install_images_progress(20,"Creating {image} artica_backend:0...");
    $TEMP_PATH=$unix->TEMP_DIR()."/Docker-backend";
    if(!is_dir($TEMP_PATH)){@mkdir($TEMP_PATH,0755,true);}
    file_put_contents("$TEMP_PATH/docker-method.conf","backend");
    @copy("/usr/share/artica-postfix/bin/docker-client","$TEMP_PATH/docker-client");
    $f[]="FROM articatech/artica:v1";
    $f[]="LABEL \"com.articatech.artica.type\"=\"BCK\" \"com.articatech.artica.scope\"=\"0\" \"com.articatech.artica.groupe\"=\"0\"";
    $f[]="COPY --chmod=0755 docker-client /usr/sbin/docker-client";
    $f[]="COPY --chmod=0644 docker-method.conf /etc/docker-method.conf";
    $f[]="ENTRYPOINT [\"/usr/sbin/docker-client\"]";
    @file_put_contents($TEMP_PATH."/Dockerfile",@implode("\n",$f));
    $docker=$unix->find_program("docker");
    @touch("$TEMP_PATH/uuid.conf");
    $cmd="$docker build --iidfile $TEMP_PATH/uuid.conf --tag artica_backend:0 $TEMP_PATH 2>&1";
    exec($cmd,$results);
    foreach ($results as $line){
        echo $line."\n";
        if(preg_match("#error#i",$line)){
            return false;
        }
    }

    $uuid=trim(@file_get_contents("$TEMP_PATH/uuid.conf"));
    if($uuid==null){
        echo "No UUID provided!\n";
        echo "Creating {image} artica_backend {failed}\n";
        return false;
    }
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DOCKER_BACKEND_IMAGEID",$uuid);
    shell_exec("$rm -rf $TEMP_PATH");
    return install_images_progress(50,"{success}");

}
function create_perimeter():bool{
    $unix=new unix();
    $rm=$unix->find_program("rm");
    $TEMP_PATH=$unix->TEMP_DIR()."/Docker-frontend";
    if(!is_dir($TEMP_PATH)){@mkdir($TEMP_PATH,0755,true);}
    $dock=new dockerd();
    $dock->create_databases();
    $q=new lib_sqlite("/home/artica/SQLITE/docker.db");
    $DOCKER_PERIMETER_SAVE=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOCKER_PERIMETER_SAVE"));
    $name=$q->sqlite_escape_string2($DOCKER_PERIMETER_SAVE["Name"]);
    if($name==null){
        return build_progress(110,"Invalid perimeter name");

    }
    $time=time();
    $zmd5=md5($name.$time);
    $NetID=$DOCKER_PERIMETER_SAVE["NetID"];
    build_progress(10,"Creating {perimeter} $name");
    $q->QUERY_SQL("INSERT INTO frontends (name,zmd5,networkid,created) VALUES ('$name','$zmd5','$NetID','$time')");
    if(!$q->ok){
        echo $q->mysql_error."\n";
        return build_progress(110,"Creating {perimeter} {failed} SQL Error");
    }

    $ligne=$q->mysqli_fetch_array("SELECT ID FROM frontends WHERE zmd5='$zmd5'");
    $ID=$ligne["ID"];

    build_progress(20,"Creating {image} Web Administration:$ID...");
    build_progress(60,"Creating {container} admin$ID...");
    return build_progress(100,"{success}");

}
function create_frontend_progress($prc,$text,$perid):bool{
    $unix=new unix();return $unix->framework_progress($prc,$text,"docker.perimeter.create.$perid");

}



function create_backend_node($groupid){

    $unix=new unix();
    $gpdock=new dockerd_groups($groupid);
    $PermimeterID=$gpdock->GetPermimeterID();
    $GetPerimeterParams=$gpdock->GetPerimeterParams();
    $NetID=$GetPerimeterParams["networkid"];
    $frontendimageid=$GetPerimeterParams["frontendimageid"];

    $TEMP_PATH=$unix->TEMP_DIR()."/node-$groupid";
    if(!is_dir($TEMP_PATH)){@mkdir($TEMP_PATH,0755,true);}
    $rm=$unix->find_program("rm");

    $PermiterPath="/home/artica/DocksClients/$PermimeterID";
    $workdir="$PermiterPath/$groupid";
    if(!is_dir($workdir)){@mkdir($workdir,0755,true);}
    $NginxBuilder=$workdir."/nginx";
    $BackendID=0;
    $array["COPY"]["/usr/share/artica-postfix/bin/docker-client"]="/usr/sbin/docker-client";
    $array["MOUNTS_BIND"]["$NginxBuilder"]="/etc/nginx";
    $array["MOUNTS_BIND"]["$PermiterPath"]="/etc/artica-perimeter";
    $array["entrypoint"]="/usr/sbin/docker-client";
    $array["name"]="back-$PermimeterID-$groupid-$BackendID";
    $array["network"]=$NetID;
    $array["image"]=$frontendimageid;
    $array["LABELS"]["com.articatech.artica.type.BACK"]="BACK";
    $array["LABELS"]["com.articatech.artica.adm.$groupid"]=$groupid;
    $array["LABELS"]["com.articatech.artica.scope.$PermimeterID"]=$PermimeterID;
    $array["LABELS"]["com.articatech.artica.group.$groupid"]=$groupid;
    $array["LABELS"]["com.articatech.artica.scope.$PermimeterID.backend.$groupid"]=$BackendID;
    $array["EXPOSE"][]="9505/tcp";
    $array["EXPOSE"][]="80/tcp";
    $array["EXPOSE"][]="443/tcp";
    $array["ENV"]["ARTICA_METHOD"]="backend";

    $dock=new dockerd();
    if(!$dock->unix_run_container($array)){
        _out("Container backend back-$PermimeterID-$groupid-$BackendID $dock->mysql_error");
        return false;
    }

    return true;

}







function run_frontend_webadm_infos($gpid):bool{
    $dock=new dockerd();
    $gprs=new dockerd_groups($gpid);
    $ArticaHttpsPort        = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpsPort"));
    $ArticaHttpUseSSL       = intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("ArticaHttpUseSSL"));



    $frontend_id=$gprs->GetPermimeterID();
    $PerimeterName=$gprs->GetPerimeterName();
    $DockerDir="/home/artica/DocksClients/$frontend_id/$gpid/Docker";
    if(!is_dir($DockerDir)){@mkdir($DockerDir,0755,true);}
    $DockInfos["perimeter"]=$frontend_id;
    $DockInfos["groupid"]=$gpid;
    $DockInfos["permietername"]=$PerimeterName;
    $DockInfos["groupname"]=$gprs->GroupNameFull();
    $DockInfos["ArticaHttpsPort"]=$ArticaHttpsPort;
    $DockInfos["ArticaHttpUseSSL"]=$ArticaHttpUseSSL;
    $DockInfos["API_KEY"]=md5($frontend_id.$gpid.time());
    $DockInfos["groupname"]=$gprs->GroupNameFull();
    $MaxInstances=intval($gprs->Get("MaxInstances"));
    if($MaxInstances==0){$MaxInstances=1;}
    $DockInfos["MaxInstances"]=$MaxInstances;
    echo "Saving $DockerDir/info.json\n";
    @file_put_contents("$DockerDir/info.json",serialize($DockInfos));
    return true;
}
function run_frontend_webadm($gpid):bool{
    $dock=new dockerd();
    $gprs=new dockerd_groups($gpid);
    $frontend_id=$gprs->GetPermimeterID();
    $PerimeterName=$gprs->GetPerimeterName();
    $GetPerimeterParams=$gprs->GetPerimeterParams();
    $NetID=$GetPerimeterParams["networkid"];
    $adminimageid=$GetPerimeterParams["adminimageid"];
    create_frontend_progress(20,"{create} Web-admin - webadm$gpid for $PerimeterName",$gpid);
    $workdir="/home/artica/DocksClients/$frontend_id/$gpid";
    if(!is_dir($workdir)){@mkdir($workdir,0755,true);}
    $ldap_settings=$workdir."/ldap_settings";
    $ArticaStatsDB=$workdir."/ArticaStatsDB";
    $NginxBuilder=$workdir."/nginx";
    $DockerDir=$workdir."/Docker";
    if(!is_dir($ldap_settings)){@mkdir($ldap_settings,0755,true);}
    if(!is_dir($ArticaStatsDB)){@mkdir($ArticaStatsDB,0755,true);}
    if(!is_dir($NginxBuilder)){@mkdir($NginxBuilder,0755,true);}
    if(!is_dir($DockerDir)){@mkdir($DockerDir,0755,true);}
    $Manager=$gprs->Get("manager");
    if($Manager==null){
        $Manager=serialize(array("USER"=>"Manager","PASS"=>"secret"));
    }
    $MM=unserialize($Manager);
    @file_put_contents("$ldap_settings/admin",$MM["USER"]);
    @file_put_contents("$ldap_settings/password",$MM["PASS"]);

    $array["COPY"]["/usr/share/artica-postfix/bin/docker-client"]="/usr/sbin/docker-client";
    $array["MOUNTS_BIND"]["$DockerDir"]="/usr/share/artica-postfix/Docker|ro";
    $array["MOUNTS_BIND"]["$ldap_settings"]="/etc/artica-postfix/ldap_settings";
    $array["MOUNTS_BIND"]["$ArticaStatsDB"]="/home/ArticaStatsDB";
    $array["MOUNTS_BIND"]["$NginxBuilder"]="/etc/nginx";
    $array["MOUNTS_BIND"]["/var/run/docker.sock"]="/var/run/docker.sock";
    $DOCKER_WEBADM_IMAGEID=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DOCKER_WEBADM_IMAGEID"));

    $array["RESTART"]=true;
    $array["entrypoint"]="/usr/sbin/docker-client";
    $array["name"]="webadm$gpid";
    $array["network"]=$NetID;
    $array["image"]=$DOCKER_WEBADM_IMAGEID;
    $array["LABELS"]["com.articatech.artica.type.ADM"]="ADM";
    $array["LABELS"]["com.articatech.artica.adm.$gpid"]=$gpid;
    $array["LABELS"]["com.articatech.artica.scope.$frontend_id"]=$frontend_id;
    $array["LABELS"]["com.articatech.artica.group.$gpid"]=$gpid;
    $array["LABELS"]["com.articatech.artica.scope.$frontend_id.webadm.$gpid"]=$gpid;
    $array["EXPOSE"][]="9505/tcp";


    create_frontend_progress(30,"{create} {APP_NGINX_CONSOLE}",$gpid);
    if(!$dock->unix_run_container($array)){
        _out("Container webadm$gpid $dock->mysql_error");
        create_frontend_progress(110,"Creating {container} webadm$gpid {failed}",$gpid);
        return false;
    }


    $ContainerID=$dock->container_id;
    create_frontend_progress(35,"{stop} $ContainerID",$gpid);
    if(!$dock->unix_stop_container($ContainerID)){
        echo $dock->mysql_error."\n";
        return create_frontend_progress(110,"{failed}",$gpid);
    }
    create_frontend_progress(40,"{update} $ContainerID",$gpid);
    if(!$dock->unix_copy_container("/usr/share/artica-postfix/bin/docker-client","/usr/sbin/docker-client",$ContainerID)){
        echo $dock->mysql_error."\n";
        return create_frontend_progress(110,"{update} {failed}",$gpid);
    }
    create_frontend_progress(35,"{start} $ContainerID",$gpid);
    if(!$dock->unix_start_container($ContainerID)){
        _out("Container webadm$gpid $dock->mysql_error");
        create_frontend_progress(110,"{starting} {container} webadm$gpid {failed}",$gpid);
        return false;
    }

    _out("Success creating WebAdmin container scope $dock->container_id for group $gpid");
    run_frontend_webadm_infos($gpid);
    run_frontend_to_nginx($gpid);
    return create_frontend_progress(100,"{success}",$gpid);

}



