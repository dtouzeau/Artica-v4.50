<?php
	include_once(dirname(__FILE__).'/ressources/class.templates.inc'); 
	include_once('ressources/class.users.menus.inc');
	if(isset($_GET["verbose"])){$GLOBALS["VERBOSE"]=true;ini_set('display_errors', 1);ini_set('error_reporting', E_ALL);ini_set('error_prepend_string',null);ini_set('error_append_string',null);}
	$user=new usersMenus();
	if(!$user->AsAnAdministratorGeneric){die("DIE " .__FILE__." Line: ".__LINE__);}

    if(isset($_GET["table-start"])){table_start();exit;}
	if(isset($_GET["table"])){table();exit;}
	if(isset($_GET["service-status-docker"])){services_docker_status();exit;}
    if(isset($_GET["service-status-containerd"])){services_containerd_status();exit;}
    if(isset($_GET["service-status-kubelet"])){services_kubelet_status();exit;}


	if(isset($_GET["service-toolbox"])){services_toolbox();exit;}
    if(isset($_GET["system-clean"])){system_clean_js();exit;}
    if(isset($_POST["system-clean"])){system_clean_perform();exit;}

    if(isset($_GET["DockerYacht-remove"])){DockerYacht_remove();exit;}
    if(isset($_POST["DockerYacht-remove"])){DockerYacht_remove_confirm();exit;}

    if(isset($_GET["DockerYacht-install"])){DockerYacht_install();exit;}
    if(isset($_POST["DockerYacht-install"])){DockerYacht_install_confirm();exit;}

    if(isset($_GET["VolumeLoopback-install"])){VolumeLoopback_install();exit;}
    if(isset($_POST["VolumeLoopback-install"])){VolumeLoopback_install_confirm();exit;}

    if(isset($_GET["VolumeLoopback-uninstall"])){VolumeLoopback_uninstall();exit;}
    if(isset($_POST["VolumeLoopback-uninstall"])){VolumeLoopback_uninstall_confirm();exit;}

    if(isset($_GET["docker-config-locked"])){docker_config_locked();exit;}
    if(isset($_GET["section-workdir-js"])){section_workingdir_js();exit;}
    if(isset($_GET["section-workdir-popup"])){section_workingdir_popup();exit;}

    if(isset($_GET["section-export-js"])){section_export_js();exit;}
    if(isset($_GET["section-export-popup"])){section_export_popup();exit;}
    if(isset($_POST["DockerExportTime"])){section_export_save();exit;}



    if(isset($_POST["WorkDir"])){section_workingdir_save();exit;}
	
page();

function system_clean_js():bool{
    $tpl=new template_admin();
    return $tpl->js_confirm_execute("{docker_system_prune}","system-clean","yes");
}
function system_clean_perform():bool{
    $GLOBALS["CLASS_SOCKETS"]->getFrameWork("docker.php?clean-system=yes");
    return admin_tracks("Clean docker prune system cache");
}

function page():bool{
    $page=CurrentPageName();
    $tpl=new template_admin();
    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");

    $html=$tpl->page_header("{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {status}",
        ico_docker,
        "{APP_DOCKER_EXPLAIN}",
        "$page?table-start=yes","docker-status","progress-docker-restart",false,"table-docker-status");


    if(isset($_GET["main-page"])){
        $tpl=new template_admin("Artica: Docker status",$html);
        echo $tpl->build_firewall();
        return true;
    }

    $tpl=new templates();
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function services_docker_status():bool{
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $page=CurrentPageName();
    $status_file=PROGRESS_DIR."/docker.status";
    $ini->loadFile($status_file);
    $jsrestart=$tpl->framework_buildjs("docker.php?restart=yes",
        "docker.progress","docker.progress.logs",
        "progress-docker-restart","LoadAjaxTiny('table-start-docker','$page?table=yes');",null,null,"AsDockerAdmin");
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_DOCKER",$jsrestart);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function services_containerd_status():bool{
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $page=CurrentPageName();
    $status_file=PROGRESS_DIR."/docker.status";
    $ini->loadFile($status_file);
    $jsrestart=$tpl->framework_buildjs("docker.php?restart=yes",
        "docker.progress","docker.progress.logs",
        "progress-docker-restart","LoadAjaxTiny('table-start-docker','$page?table=yes');",null,null,"AsDockerAdmin");
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_CONTAINERD",$jsrestart);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function services_kubelet_status():bool{
    $tpl=new template_admin();
    $ini=new Bs_IniHandler();
    $page=CurrentPageName();
    $status_file=PROGRESS_DIR."/docker.status";
    $ini->loadFile($status_file);
    $jsrestart=$tpl->framework_buildjs("docker.php?restart=yes",
        "docker.progress","docker.progress.logs",
        "progress-docker-restart","LoadAjaxTiny('table-start-docker','$page?table=yes');",null,null,"AsDockerAdmin");
    $html[]=$tpl->SERVICE_STATUS($ini, "APP_KUBELET",$jsrestart);
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}

function table_start():bool{
    $page=CurrentPageName();
    echo "<div id='table-start-docker'></div>";
    echo "<script>LoadAjax('table-start-docker','$page?table=yes')</script>";
    return true;
}
function table():bool{
	$tpl=new template_admin();
	$page=CurrentPageName();
	$t=time();
    $sock=new sockets();
    $sock->REST_API("/docker/allstatus");
	$html="<table style='width:100%;margin-top:10px'>
	<tr>
		<td valign='top' style='width:1%' nowrap>
			<div id='service-status-docker'></div>
        </td>
        <td valign='top' style='width:1%' nowrap>
			<div id='service-status-containerd'></div>
        </td>
        <td valign='top' style='width:1%' nowrap>
			<div id='service-status-kubelet'></div>
        </td>
	</tr>
	</table>
	<script>
		LoadAjaxTiny('service-status-docker','$page?service-status-docker=yes');
		LoadAjaxTiny('service-status-containerd','$page?service-status-containerd=yes');
        LoadAjaxTiny('service-status-kubelet','$page?service-status-kubelet=yes');		
	</script>";
	echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function docker_config_locked():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DockerDataRootSize_text=null;
    $DockerDataRoot=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRoot"));
    $DockerDataRootTemp=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRootTemp"));
    $DockerDataRootSize=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRootSize"));
    $DockerExportTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerExportTime"));
    if($DockerExportTime==0){$DockerExportTime=60;}
    if($DockerDataRoot==null){$DockerDataRoot="/home/docker";}
    if($DockerDataRootSize>1024){
        $DockerDataRootSize_text="&nbsp;&nbsp;<small>(".FormatBytes($DockerDataRootSize/1024).")</small>";
    }

    if(strlen($DockerDataRootTemp)==0) {
        $tpl->table_form_field_js("Loadjs('$page?section-workdir-js=yes')","AsDockerAdmin");
        $tpl->table_form_field_text("{working_directory}", $DockerDataRoot.$DockerDataRootSize_text, ico_folder);
    }else{
        $tpl->table_form_field_js("");
        $tpl->table_form_field_text("{working_directory}", $DockerDataRootTemp, ico_refresh);
    }
    $tpl->table_form_field_js("Loadjs('$page?section-export-js=yes')","AsDockerAdmin");
    $tpl->table_form_field_text("{export} ({ttl})", "/home/docker-export: $DockerExportTime {minutes}", ico_clock_desk);

    echo $tpl->table_form_compile();
    return true;
}
function section_workingdir_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
   return $tpl->js_dialog("{working_directory}","$page?section-workdir-popup=yes");
}
function section_export_js():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    return $tpl->js_dialog("{export} {ttl}","$page?section-export-popup=yes");
}
function section_js():string{
    $page=CurrentPageName();
    return "BootstrapDialog1.close();LoadAjaxTiny('docker-config-locked','$page?docker-config-locked=yes');";
}
function section_export_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DockerExportTime=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerExportTime"));
    if($DockerExportTime==0){$DockerExportTime=60;}

    $form[]=$tpl->field_numeric("DockerExportTime","{ttl} ({minutes})",$DockerExportTime);
    $html[]=$tpl->form_outside(null,$form,"{DockerExportTime}","{apply}","LoadAjaxTiny('docker-config-locked','$page?docker-config-locked=yes');","AsDockerAdmin");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_export_save():bool{
    $tpl=new template_admin();
    $tpl->SAVE_POSTs();
    return true;
}
function section_workingdir_popup():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $DockerDataRoot=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRoot"));
    if($DockerDataRoot==null){$DockerDataRoot="/home/docker";}

    $html[]="<div id='docker-workdir-progress'></div>";
    $form[]=$tpl->field_browse_directory("WorkDir","{working_directory}",$DockerDataRoot);

    $jsafter=$tpl->framework_buildjs(
        "docker.php?move-workdir=yes",
        "docker.workdir.progress","docker.workdir.log",
        "docker-workdir-progress",section_js(),null,null,"AsDockerAdmin"
    );

    $html[]=$tpl->form_outside(null,$form,null,"{apply}","LoadAjaxTiny('docker-config-locked','$page?docker-config-locked=yes');$jsafter","AsDockerAdmin");
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}
function section_workingdir_save():bool{
    $tpl=new template_admin();
    $tpl->CLEAN_POST();
    $DockerDataRoot=trim($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerDataRoot"));
    if($DockerDataRoot==null){$DockerDataRoot="/home/docker";}
    if($DockerDataRoot==$_POST["WorkDir"]){return true;}
    admin_tracks("Change the docker working directory from $DockerDataRoot to {$_POST["WorkDir"]}");
    $GLOBALS["CLASS_SOCKETS"]->SET_INFO("DockerDataRootTemp",$_POST["WorkDir"]);
    return true;
}





function DockerYacht_remove():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("docker.php?yacht-remove=yes",
        "docker-yacht.progress",
        "docker-yacht.log",
        "progress-docker-restart",
        "LoadAjaxSilent('table-docker-status','$page?table=yes')",null,null,"AsDockerAdmin"
    );
    return $tpl->js_confirm_delete("{APP_YACHT}","DockerYacht-remove","yes",$js);
}
function VolumeLoopback_uninstall():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("docker.php?volumeloopback-remove=yes",
        "docker-yacht.progress",
        "docker-yacht.log",
        "progress-docker-restart",
        "LoadAjaxSilent('table-docker-status','$page?table=yes')",null,null,"AsDockerAdmin"
    );
    return $tpl->js_confirm_delete("{DockerVolumeLoopback}","VolumeLoopback-uninstall","yes",$js);
}
function DockerYacht_install():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("docker.php?yacht-install=yes",
        "docker-yacht.progress",
        "docker-yacht.log",
        "progress-docker-restart",
        "LoadAjaxSilent('table-docker-status','$page?table=yes')",null,null,"AsDockerAdmin"
    );
    return $tpl->js_confirm_execute("{install}: {APP_YACHT}","DockerYacht-install","yes",$js);

}
function VolumeLoopback_install():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $js=$tpl->framework_buildjs("docker.php?volumeloopback-install=yes",
        "docker-yacht.progress",
        "docker-yacht.log",
        "progress-docker-restart",
        "LoadAjaxSilent('table-docker-status','$page?table=yes')",null,null,"AsDockerAdmin"
    );
    return $tpl->js_confirm_execute("{install}: {DockerVolumeLoopback}","VolumeLoopback-install","yes",$js);

}
function VolumeLoopback_install_confirm():bool{
    return admin_tracks("Install Volume Loopback docker plugin");
}

function DockerYacht_install_confirm():bool{
    return admin_tracks("Install YACHT docker container");
}
function DockerYacht_remove_confirm():bool{
    return admin_tracks("Remove YACHT docker container");
}
function VolumeLoopback_uninstall_confirm():bool{
    return admin_tracks("Remove Volume Loopback docker plugin");
}

function services_toolbox():bool{
    $tpl=new template_admin();
    $page=CurrentPageName();
    $users=new usersMenus();
    $DockerAdmin=$users->AsDockerAdmin;
    $DockerYachtInstalled=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerYachtInstalled"));
    $DockVolumeLoopBack=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockVolumeLoopBack"));

    $topbuttons=array();
    if($DockerAdmin) {
        $topbuttons[] = array("Loadjs('$page?system-clean=yes')", ico_trash, "{clean}");
    }
    $btn=array();
    if($DockerYachtInstalled==0){
        if($DockerAdmin) {
            $btn[0]["js"] = "Loadjs('$page?DockerYacht-install=yes');";
            $btn[0]["name"] = "{install}";
            $btn[0]["icon"] = ico_cd;
        }
        $DockerYachtWidget=$tpl->widget_grey("{APP_YACHT}","{not_installed}",$btn);
    }else{
        if($DockerAdmin) {
            $btn[0]["js"] = "Loadjs('$page?DockerYacht-remove=yes');";
            $btn[0]["name"] = "{uninstall}";
            $btn[0]["icon"] = ico_trash;
        }
        $DockerYachtWidget=$tpl->widget_vert("{APP_YACHT}","{installed}",$btn);
        $DockerYachtPort=intval($GLOBALS["CLASS_SOCKETS"]->GET_INFO("DockerYachtPort"));
        if($DockerYachtPort>0){
            $SERVER_NAME=$_SERVER["SERVER_NAME"];
            $topbuttons[] = array("s_PopUpFull('http://$SERVER_NAME:$DockerYachtPort','1024','900');",ico_dashboard,"{APP_YACHT}");

        }

    }

    if($DockVolumeLoopBack==0){
        if($DockerAdmin) {
            $btn[0]["js"] = "Loadjs('$page?VolumeLoopback-install=yes');";
            $btn[0]["name"] = "{install}";
            $btn[0]["icon"] = ico_cd;
        }
        $DockerVolumeLoopback=$tpl->widget_grey("{DockerVolumeLoopback}","{not_installed}",$btn);

    }else{
        if($DockerAdmin) {
            $btn[0]["js"] = "Loadjs('$page?VolumeLoopback-uninstall=yes');";
            $btn[0]["name"] = "{uninstall}";
            $btn[0]["icon"] = ico_trash;
        }
        $DockerVolumeLoopback=$tpl->widget_vert("{DockerVolumeLoopback}","{installed}",$btn);
    }






    $APP_DOCKER_VERSION=$GLOBALS["CLASS_SOCKETS"]->GET_INFO("APP_DOCKER_VERSION");
    $TINY_ARRAY["TITLE"]="{APP_DOCKER} $APP_DOCKER_VERSION &raquo;&raquo; {status}";
    $TINY_ARRAY["ICO"]=ico_docker;
    $TINY_ARRAY["EXPL"]="{APP_DOCKER_EXPLAIN}";
    $TINY_ARRAY["BUTTONS"]=$tpl->table_buttons($topbuttons);
    $jstiny="Loadjs('fw.progress.php?tiny-page=".urlencode(base64_encode(serialize($TINY_ARRAY)))."');";

    $html[]="<table style='width:100%'>";
    $html[]="<tr>";
    $html[]="<td style='width:33%'>$DockerYachtWidget</td>";
    $html[]="<td style='width:33%'>$DockerVolumeLoopback</td>";
    $html[]="</tr>";
    $html[]="<table>";
    $html[]="<script>$jstiny</script>";
    echo $tpl->_ENGINE_parse_body($html);
    return true;
}






