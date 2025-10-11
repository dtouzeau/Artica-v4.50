<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}

    if(isset($_GET["add-volume-js"])){add_volume_js();exit;}
    if(isset($_GET["add-volume-popup"])){add_volume_popup();exit;}
    if(isset($_POST["VOLUME"])){add_volume_save();exit;}

    if(isset($_GET["delete-volume-js"])){delete_volume_js();exit;}
    if(isset($_POST["delete-container"])){delete_container_perform();exit;}

    if(isset($_GET["link-container-js"])){link_container_js();exit;}
    if(isset($_GET["link-container-popup"])){link_container_popup();exit;}
    if(isset($_POST["link-image"])){link_container_save();exit;}

    if(isset($_GET["stop-container"])){stop_container();exit;}
    if(isset($_GET["start-container"])){start_container();exit;}

    if(isset($_GET["info-container"])){info_container_js();exit;}
    if(isset($_GET["info-container-popup"])){info_container_popup();exit;}
    if(isset($_GET["info-container-tab"])){info_container_tab();exit;}
    if(isset($_GET["info-container-details"])){info_container_details();exit;}

    if(isset($_GET["prune-volume-js"])){prune_volumes_js();exit;}
    if(isset($_POST["prune-volumes"])){prune_volume_perform();exit;}

    if(isset($_GET["shellinaboxd-install"])){shellinaboxd_install();exit;}
    if(isset($_POST["shellinaboxd-install"])){shellinaboxd_install_perform();exit;}

    if(isset($_GET["table"])){table();exit;}
    if(isset($_GET["search"])){search();exit;}
	if(isset($_GET["service-status"])){services_status();exit;}
	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}


	
page();

function prune_volumes_js(){
    $tpl=new template_admin();
    $function=$_GET["function"];
    return $tpl->js_confirm_delete("{rm_unused_vols}","prune-volumes","yes","$function()");
}
function prune_volume_perform(){
    admin_tracks("Prune all unused volumes...");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?prune-volumes=yes");
}

function delete_volume_js():bool{
    $tpl=new template_admin();
    $volume=$_GET["delete-volume-js"];
    $md=$_GET["md"];
    $deletejs=$tpl->framework_buildjs(
        "docker.php?remove-volume=$volume",
        "docker.rm.$volume.progress",
        "docker.rm.$volume.log",
        "progress-docker-volumes",
        "$('#$md').remove()",null,null,"AsDockerAdmin"

    );


    return $tpl->js_confirm_delete("{remove} {volume} $volume","delete-volume",$volume,$deletejs);
}
function delete_volume_perform():bool{
    $image=$_POST["delete-volume"];
    return admin_tracks("Remove docker volume $image");
}
function start_container():bool{
    $tpl=new template_admin();
    $image=$_GET["start-container"];
    $function=$_GET["function-main"];
    $imageenc=urlencode($image);
    $md5=md5($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?start-container=$imageenc&md5=$md5");


    $tfile=PROGRESS_DIR."/docker.$md5.start";
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));

        }
    }

    header("content-type: application/x-javascript");
    echo "BootstrapDialog1.close();\n";
    echo "$function();";
    return admin_tracks("Starting $image docker container...");
}
function info_container_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $info=$_GET["info-container"];
    $infoenc=urlencode($info);
    $function_main=$_GET["function-main"];
    $json=json_decode(base64_decode($info));
    $Names=$json->Names;
    return $tpl->js_dialog($Names,"$page?info-container-tab=$infoenc&function-main=$function_main");
}

function info_container_tab():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $info=$_GET["info-container-tab"];
    $infoenc=urlencode($info);
    $function_main=$_GET["function-main"];
    $json=json_decode(base64_decode($info));
    $ID=$json->ID;
    $Names=$json->Names;
    $array[$Names]="$page?info-container-popup=$infoenc&function-main=$function_main";
    $array["{details}"]="$page?info-container-details=$ID&function-main=$function_main";
    echo $tpl->tabs_default($array);
    return true;
}
function info_container_parse($array){
    $html[]="<table id='table2' class=\"table table-stripped\">";
    $xvals=array();
    foreach ($array as $key=>$val){

        if(is_numeric($key)){
            $xvals[]=$val;
            continue;
        }
        $html[]="<tr>";

        if(count($xvals)>0){
            $html[]="<tr>";
            $html[]="<td>&nbsp;</td>";
            $html[]="<td id='line.".__LINE__."'>". @implode(" ",$xvals)."</td>";
            $html[]="</tr>";
            $xvals=array();
            continue;
        }

        $key=strtolower($key);
        $html[]="<td style='vertical-align: top'>{{$key}}:</td>";
        if(is_object($val)){
            $html[]="<td>". info_container_parse($val)."</td>";
            $html[]="</tr>";
            continue;
        }
        if(is_array($val)){
            $html[]="<td>". info_container_parse($val)."</td>";
            $html[]="</tr>";
            continue;
        }


        $html[]="<td id='line.".__LINE__."'><strong id='line.".__LINE__."'>$val</strong></td>";
        $html[]="</tr>";
    }
    if(count($xvals)>0) {
        $html[] = "<tr>";
        $html[] = "<td>&nbsp;</td>";
        $html[] = "<td id='line.".__LINE__."'>" . @implode(", ", $xvals) . "</td>";
        $html[] = "</tr>";
    }


    $html[]="</table>";
    $html[]="<!-- FINISH -->";
    return @implode("\n",$html);
}
function info_container_details():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $ID=$_GET["info-container-details"];
    $function_main=$_GET["function-main"];
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?container-details=$ID");
    $tfile=PROGRESS_DIR."/docker.$ID.details";
    $datas=@file_get_contents($tfile);
    $html1=array();
    $json=json_decode($datas);
    //var_dump($json);
    //var_dump($json);
    $html[]="<table id='table1' class=\"table table-stripped\" style='margin-top:10px'>";
    foreach ($json[0] as $key=>$val){
        if(!is_array($key)){
            if(is_object($val)){
                $html[]=info_container_parse($val);
                continue;
            }

            if(!is_array($val)){
                $key=strtolower($key);
                $top[]="<tr>";
                $top[]="<td>{{$key}}:</td>";
                $top[]="<td><strong id='line.".__LINE__."'>$val</strong></td>";
                $top[]="</tr>";
                continue;
            }
            $html[]="<!-- line.".__LINE__." -->";
            $html[]=info_container_parse($val);
            continue;
        }else{
            if(is_array($val)){
                $html[]="<!-- line.".__LINE__." -->";
                $html[]=info_container_parse($val);
                continue;
            }
        }

        $key=strtolower($key);
        $html[]="<tr>";
        $html[]="<td id='line.".__LINE__."'>{{$key}}:</td>";
        $html[]="<td id='line.".__LINE__."'><strong>$val</strong></td>";
        $html[]="</tr>";


    }
    $html[]="<tr><td colspan='2' id='line.".__LINE__."'></td></tr>";
    $html[]="</table>";
    if(count($top)>0){
        $html1[]="<table id='table1' class=\"table table-stripped\" style='margin-top:10px'>";
        $html1[]=@implode("\n",$top);
        $html1[]="</table>";
    }
    $stop=@implode("\n",$html1);
    echo $tpl->_ENGINE_parse_body($stop.@implode("\n",$html));
    return true;
}

function info_container_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $info=$_GET["info-container-popup"];
    $function_main=$_GET["function-main"];
    $json=json_decode(base64_decode($info));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\" style='margin-top:10px'>";
    foreach ($json as $key=>$val){
        if($key=="Labels"){
            $tb=explode(",",$val);
            foreach ($tb as $sline){
                $html[]="<tr>";
                $sline=str_replace("org.opencontainers.image.","",$sline);
                if(preg_match("#(.+?)=(.*)#",$sline,$re)){
                    if($re[1]=="events"){$re[1]="events2";}
                    if($re[1]=="title"){$re[1]="title2";}
                    if(strpos($re[1],".")==0){$re[1]="{".$re[1]."}";}
                    $html[]="<td><strong>{$re[1]}</strong>:</td>";
                    $html[]="<td><strong>{$re[2]}</strong></td>";
                    $html[]="<tr>";
                    continue;
                }
                $html[]="<td colspan='2'><strong>$sline</strong></td>";
                
            }
            continue;
        }
        $key=strtolower($key);
        $html[]="<tr>";
        $html[]="<td>{{$key}}:</td>";
        $html[]="<td><strong>$val</strong></td>";
        $html[]="</tr>";


    }
    $html[]="</table>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function stop_container():bool{
    $tpl=new template_admin();
    $image=$_GET["stop-container"];
    $ID=$_GET["ID"];
    $function=$_GET["function-main"];
    $imageenc=urlencode($image);
    $md5=md5($image);

    $js=$tpl->framework_buildjs(
        "docker.php?stop-container=$ID",
        "docker.$ID.stop",
        "docker.$ID.stop.log",
        "progress-docker-volumes","$function();","$function();",null,null,"AsDockerAdmin"
    );

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?stop-container=$ID");


    $tfile=PROGRESS_DIR."/docker.$md5.stop";
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));

        }
    }
    header("content-type: application/x-javascript");
    echo $js;
    return admin_tracks("Stopping $image docker container...");
}

function link_container_js():bool{
    $image=$_GET["link-container-js"];
    $imageenc=urlencode($_GET["link-container-js"]);
    $function=$_GET["function-main"];
    $tpl=new template_admin();
    $page=CurrentPageName();

    return $tpl->js_dialog("$image","$page?link-container-popup=$imageenc&function-main=$function");

}
function link_container_popup():bool{
    $image=$_GET["link-container-popup"];
    $imagemd5=md5($image);
    $imageenc=urlencode($image);
    $function=$_GET["function-main"];
    $tpl=new template_admin();

    $data=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerContainersSettings");
    $DockerContainersSettings=unserialize($data);
    if(!isset($DockerContainersSettings[$image])){$DockerContainersSettings[$image]=array();}

    $ContainersSettings=$DockerContainersSettings[$image];

    if(!isset($ContainersSettings["HOSTNAME"])){
        $ContainersSettings["HOSTNAME"]=null;
    }
    if(!isset($ContainersSettings["NAME"])){
        $ContainersSettings["NAME"]=null;
    }

    $html[]="<div id='bb_$imagemd5' style='margin:10px'></div>";
    $form[]=$tpl->field_hidden("link-image",$image);
    $form[]=$tpl->field_text("HOSTNAME","{hostname}",$ContainersSettings["HOSTNAME"]);
    $form[]=$tpl->field_text("NAME","{container_name}",$ContainersSettings["NAME"]);

    $js=$tpl->framework_buildjs("docker.php?link-container=$imageenc&md5=$imagemd5",
        "docker.link.$imagemd5","docker.link.$imagemd5.log","bb_$imagemd5",
        "BootstrapDialog1.close();$function();","BootstrapDialog1.close();$function();",null,"AsDockerAdmin"
    );
    $security="AsDockerAdmin";
    $html[]=$tpl->form_outside($image, $form,null,"{import}",$js,$security,true);
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function link_container_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $image=$_POST["link-image"];

    foreach ($_POST as $key=>$val) {
        writelogs("Saving $key == $val",__FUNCTION__,__FILE__,__LINE__);
    }


    $_POST["NAME"]=str_replace([' ',',','&',';','%','/','\\','*','|','(',')','{','}','[',']','=',':','#','~','>','<','?','!','$','¤','"','+'],'',$_POST["NAME"]);

    $_POST["HOSTNAME"]=str_replace([' ',',','&',';','%','/','\\','*','|','(',')','{','}','[',']','=',':','#','~','>','<','?','!','$','¤','"','+'],'',$_POST["HOSTNAME"]);

    if($_POST["NAME"]<>null) {
        if (!preg_match("#^[a-zA-Z0-9][a-zA-Z0-9_.-]+$#", $_POST["NAME"])) {
            $tpl->post_error("{$_POST["NAME"]} {invalid}");
            return false;
        }
    }
    $DockerContainersSettings=unserialize($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerContainersSettings"));
    foreach ($_POST as $key=>$val) {
        $LOG[]="$key=$val";
        $DockerContainersSettings[$image][$key] = $val;
    }

    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerContainersSettings",serialize($DockerContainersSettings));
    admin_tracks("Link docker $image into container ".@implode(", ",$LOG));
    return true;
}


function link_container_js2():bool{
    $tpl=new template_admin();
    $image=$_GET["link-container-js"];
    $function=$_GET["function-main"];
    $imageenc=urlencode($image);
    $md5=md5($image);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?link-container=$imageenc&md5=$md5");


    $tfile=PROGRESS_DIR."/docker.$md5.run";
    $results=explode("\n",@file_get_contents($tfile));
    foreach ($results as $line){
        if(preg_match("#Error\s+#",$line)){
            return $tpl->js_error(@implode("<br>",$results));

        }
    }

    header("content-type: application/x-javascript");
    echo "BootstrapDialog1.close();\n";
    echo "$function();";
    return admin_tracks("Run a new docker container from an image $image");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");

    $html=$tpl->page_header("{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {volumes}",
        ico_hd,
        "{APP_DOCKER_EXPLAIN}",
        "$page?table=yes","docker-volumes","progress-docker-volumes",false,"table-docker-containers");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker containers",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}


function add_volume_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $function=$_GET["function"];
    return $tpl->js_dialog("{new_volume}","$page?add-volume-popup=yes&function-main=$function");
}
function add_volume_popup():bool{
    $tpl=new template_admin();
    $function_main=$_GET["function-main"];
    $DockVolumeLoopBack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockVolumeLoopBack"));

    $form[]=$tpl->field_text("VOLUME","{name}",null,true);
    if($DockVolumeLoopBack==1) {
        $form[] = $tpl->field_numeric("Size", "{LVMS_SIZE} ({optional} - Gib)", 5, false);
    }
    $html[]=$tpl->form_outside("{new_volume}",$form,null,"{add}","$function_main()","AsDockerAdmin");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function add_volume_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $Size=0;
    if(isset($_POST["Size"])){
        $Size=$_POST["Size"];
    }
    $VOLUME=$_POST["VOLUME"];
    $VOLUME=str_replace([' ',',','&',';','%','/','\\','*','|','(',')','{','}','[',']','=',':','#','~','>','<','?','!','$','¤','"','+'],'',$VOLUME);
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?volumes-add=$VOLUME&size=$Size");
    admin_tracks("Add new docker volume $VOLUME size=$Size");
    return true;
}


function table():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    echo $tpl->search_block($page);
    return true;
}
function search():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
    $users=new usersMenus();
	$t=time();
    $topbuttons=array();
    $function_main=$_GET["function"];
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {volumes}";
    $TINY_ARRAY["ICO"]=ico_hd;
    $TINY_ARRAY["EXPL"]="{APP_DOCKER_EXPLAIN}";

    if($users->AsDockerAdmin) {
        $topbuttons[] = array("Loadjs('$page?add-volume-js=yes&function=$function_main')", ico_plus, "{new_volume}");
        $topbuttons[] = array("Loadjs('$page?prune-volume-js=yes&function=$function_main')", ico_trash, "{rm_unused_vols}");


    }
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?volumes-list=yes");
    $tfile=PROGRESS_DIR."/docker.volumes.json";
    if(!is_file($tfile)){
        echo $tpl->FATAL_ERROR_SHOW_128("$tfile not exists, framework error");
        return true;
    }
    $f=explode("\n",@file_get_contents($tfile));

    $html[]="<table id='table-fireqos-interfaces' class=\"table table-stripped\">";
    $html[]="<thead>";
    $html[]="<tr>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{driver}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text'>{name}</th>";

    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' colspan='2'>{destination}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>{created}</th>";
    $html[]="<th data-sortable=true class='text-capitalize' data-type='text' width=1% nowrap>DEL</th>";
    $html[]="</tr>";
    $html[]="</thead>";
    $html[]="<tbody>";
    $TRCLASS=null;
    $page=CurrentPageName();

    $tooltips["exited"]="<label class='label label-danger'>{stopped}</label>";
    $tooltips["running"]="<label class='label label-primary'>{running}</label>";



    foreach ($f as $line){
        $line=trim($line);
        if($line==null){continue;}
        if($TRCLASS=="footable-odd"){$TRCLASS=null;}else{$TRCLASS="footable-odd";}
        $md=md5($line);
        $json=json_decode($line);

        $zDate="-";
        $Driver=$json->Driver;
        $Mountpoint=$json->Mountpoint;
        $Name=$json->Name;
        $NameEnc=urlencode($Name);
        $tfile = PROGRESS_DIR . "/volume-$NameEnc.inspect";
        $CreatedAt=0;
        if(strpos($Driver,"docker-volume-loopback")>0){
            $Driver="volume-loopback";
        }

        if($Driver=="volume-loopback") {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?volumes-inspect=$NameEnc");
            $json2 = json_decode(@file_get_contents($tfile));
            $CreatedAt=strtotime($json2[0]->CreatedAt);
            $Mountpoint=$json2[0]->Mountpoint;
            $Mountpoint_size=$json2[0]->Options->size;
            $Currentsize=intval($json2[0]->Status->{"size-allocated"});
            $CurrentsizeText=FormatBytes($Currentsize/1024);
            $Mountpoint = "$Mountpoint $CurrentsizeText/$Mountpoint_size";
        }
        if($Driver=="local") {
            $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?volumes-inspect=$NameEnc");
            $json2 = json_decode(@file_get_contents($tfile));
            $CreatedAt=strtotime($json2[0]->CreatedAt);
        }

        $base64=urlencode(base64_encode($line));
        $info=$tpl->icon_list("Loadjs('$page?info-container=$base64&function-main=$function_main')");
        if($CreatedAt>0) {
            $zDate = $tpl->time_to_date($CreatedAt, true);
        }

        $buton=$tpl->icon_delete("Loadjs('$page?delete-volume-js=$NameEnc&function-main=$function_main&md=$md')","AsDockerAdmin");
        if($Ports<>null){$Ports="($Ports)";}
        $html[]="<tr class='$TRCLASS' id='$md'>";
        $html[]="<td width='1%'><li class='".ico_hd."'></li></td>";
        $html[]="<td width='1%' nowrap>$Driver</td>";
        $html[]="<td nowrap><strong>$Name</strong></td>";
        $html[]="<td width='1%' nowrap><i class=\"fa-solid fa-arrow-right-long-to-line\"></i></td>";
        $html[]="<td  nowrap>$Mountpoint</td>";
        $html[]="<td width='1%' class='left' nowrap>$zDate</td>";
        $html[]="<td width='1%' class='center' nowrap>$buton</td>";
        $html[]="</tr>";



    }
    $html[]="</tbody>";
    $html[]="</table>";
    $html[]="<script>";
    $html[]=$jstiny;
    $html[]="NoSpinner();\n".@implode("\n",$tpl->ICON_SCRIPTS);
    $html[]="</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;

}
function shellinaboxd_install():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $Name=$_GET["shellinaboxd-install"];
    $function_main=$_GET["function-main"];
    $data=base64_encode(serialize($_GET));
    return $tpl->js_confirm_execute("{ask_install_web_console}: $Name","shellinaboxd-install",$data,"$function_main()");
}
function shellinaboxd_install_perform():bool{
    $data=unserialize(base64_decode($_POST["shellinaboxd-install"]));

    $ID=$data["ID"];
    if($ID==null){
        echo "ID is Null\n{$_POST["shellinaboxd-install"]}";
        return false;
    }
    $Name=unserialize($data["shellinaboxd-install"]);
    admin_tracks("Installing ShellInaBox for docker instance $ID/$Name");
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?shellinabox-install=$ID");
    return true;
}


function services_status(){
    $tpl=new template_admin();
	$ini=new Bs_IniHandler();
	$page=CurrentPageName();
    $status_file=PROGRESS_DIR."/dockerd.status";
    $t=intval($_GET["service-status"]);
	$GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?status=yes");
	$ini->loadFile($status_file);

    $jsrestart=$tpl->framework_buildjs("docker.php?restart=yes",
        "docker.progress","docker.progress.logs",
        "progress-docker-restart","LoadAjaxTiny('$t-status','$page?service-status=$t');",null,null,"AsDockerAdmin");


    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DOCKER",$jsrestart);

	
	echo $tpl->_ENGINE_parse_body($html);
}

function services_toolbox():bool{
return false;
}






